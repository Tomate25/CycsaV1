<?php

namespace Tests\Unit;

use PDO;
use Exception;
use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Inventario\Modelos\InventarioModelo;

/**
 * Suite de pruebas unitarias para TAREA-048: Inventario, Kárdex, Lotes,
 * Mínimos, Vencimientos y Consumo por Ensayo PEPS/FIFO.
 */
class InventarioKardexTest extends TestCase {

    private PDO $db;
    private InventarioModelo $modelo;
    private array $insumosCreados = [];
    private array $lotesCreados = [];

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Asegurar que la migración 012 ha sido ejecutada
        ob_start();
        require_once dirname(__DIR__, 2) . '/database/migrations/012_compras_inventario_proveedores.php';
        ob_end_clean();

        $this->modelo = new InventarioModelo();
    }

    protected function tearDown(): void {
        // Limpieza de datos de prueba creados
        if (!empty($this->insumosCreados)) {
            $ids = implode(',', array_map('intval', array_unique($this->insumosCreados)));
            $this->db->exec("DELETE FROM inventario_movimientos WHERE insumo_id IN ({$ids})");
            $this->db->exec("DELETE FROM inventario_lotes WHERE insumo_id IN ({$ids})");
            $this->db->exec("DELETE FROM inventario_insumos WHERE id IN ({$ids})");
        }
        if (!empty($this->lotesCreados)) {
            $idsLotes = implode(',', array_map('intval', array_unique($this->lotesCreados)));
            $this->db->exec("DELETE FROM inventario_movimientos WHERE lote_id IN ({$idsLotes})");
            $this->db->exec("DELETE FROM inventario_lotes WHERE id IN ({$idsLotes})");
        }
        parent::tearDown();
    }

    /**
     * CP-INV-01: Verificación de cálculo de existencias y detección de alerta de stock mínimo.
     */
    public function testCpInv01CalculoExistenciasYAlertaStockMinimo(): void {
        // 1. Crear insumo con stock_minimo = 25
        $codigo = 'TEST-INS-01-' . bin2hex(random_bytes(4));
        $insumoId = $this->modelo->crearInsumo([
            'codigo' => $codigo,
            'nombre' => 'Sulfato de Cobre Test ' . $codigo,
            'cantidad' => 0,
            'unidad_medida' => 'kg',
            'stock_minimo' => 25.0,
        ]);
        $this->insumosCreados[] = $insumoId;

        // Con 0 lotes, saldo debe ser 0 y alerta_stock_minimo debe ser true (0 <= 25)
        $insumos = $this->modelo->listarInsumos($codigo);
        $this->assertNotEmpty($insumos);
        $this->assertEquals(0.0, (float)$insumos[0]['saldo']);
        $this->assertTrue((bool)$insumos[0]['alerta_stock_minimo']);

        // 2. Agregar un lote activo con saldo = 15 (< 25)
        $loteId1 = $this->modelo->crearLote([
            'insumo_id' => $insumoId,
            'lote' => 'LOT-01-A-' . bin2hex(random_bytes(3)),
            'cantidad_inicial' => 15.0,
            'saldo' => 15.0,
            'costo_unitario' => 10.50,
            'estado' => 'activo',
        ]);
        $this->lotesCreados[] = $loteId1;

        $insumos = $this->modelo->listarInsumos($codigo);
        $this->assertEquals(15.0, (float)$insumos[0]['saldo']);
        $this->assertTrue((bool)$insumos[0]['alerta_stock_minimo'], 'Con 15 kg y mínimo 25 kg la alerta debe estar activa');

        // 3. Agregar un segundo lote activo con saldo = 20 (Total saldo = 35 > 25)
        $loteId2 = $this->modelo->crearLote([
            'insumo_id' => $insumoId,
            'lote' => 'LOT-01-B-' . bin2hex(random_bytes(3)),
            'cantidad_inicial' => 20.0,
            'saldo' => 20.0,
            'costo_unitario' => 11.00,
            'estado' => 'activo',
        ]);
        $this->lotesCreados[] = $loteId2;

        $insumos = $this->modelo->listarInsumos($codigo);
        $this->assertEquals(35.0, (float)$insumos[0]['saldo']);
        $this->assertFalse((bool)$insumos[0]['alerta_stock_minimo'], 'Con 35 kg y mínimo 25 kg la alerta debe estar inactiva');

        // 4. Si un lote se marca como agotado con saldo 0, no debe sumar al saldo activo
        $this->db->exec("UPDATE inventario_lotes SET saldo = 0, estado = 'agotado' WHERE id = {$loteId2}");
        $this->modelo->sincronizarStockInsumo($insumoId);

        $insumos = $this->modelo->listarInsumos($codigo);
        $this->assertEquals(15.0, (float)$insumos[0]['saldo']);
        $this->assertTrue((bool)$insumos[0]['alerta_stock_minimo']);
    }

    /**
     * CP-INV-02: Consumo por ensayo con despacho PEPS/FIFO entre múltiples lotes,
     * actualización de saldos, cambio a 'agotado' y generación de movimientos en kardex.
     */
    public function testCpInv02ConsumoEnsayoPepsFifoMultiplesLotes(): void {
        // 1. Insumo para reactivos de ensayo de rotura
        $codigo = 'TEST-INS-02-' . bin2hex(random_bytes(4));
        $insumoId = $this->modelo->crearInsumo([
            'codigo' => $codigo,
            'nombre' => 'Azufre para Cabeceo Test ' . $codigo,
            'cantidad' => 30.0,
            'unidad_medida' => 'kg',
            'stock_minimo' => 5.0,
        ]);
        $this->insumosCreados[] = $insumoId;

        $fechaLote1 = date('Y-m-d', strtotime('+15 days')); // Vence primero
        $fechaLote2 = date('Y-m-d', strtotime('+60 days')); // Vence después

        // Lote 1: 10 kg a $5.00/kg
        $loteId1 = $this->modelo->crearLote([
            'insumo_id' => $insumoId,
            'lote' => 'LOT-PEPS-1-' . bin2hex(random_bytes(3)),
            'fecha_vencimiento' => $fechaLote1,
            'cantidad_inicial' => 10.0,
            'saldo' => 10.0,
            'costo_unitario' => 5.00,
            'estado' => 'activo',
        ]);
        $this->lotesCreados[] = $loteId1;

        // Lote 2: 20 kg a $8.00/kg
        $loteId2 = $this->modelo->crearLote([
            'insumo_id' => $insumoId,
            'lote' => 'LOT-PEPS-2-' . bin2hex(random_bytes(3)),
            'fecha_vencimiento' => $fechaLote2,
            'cantidad_inicial' => 20.0,
            'saldo' => 20.0,
            'costo_unitario' => 8.00,
            'estado' => 'activo',
        ]);
        $this->lotesCreados[] = $loteId2;

        // 2. Ejecutar consumo de 15 kg para Ensayo #450 ('RT-FM-61 Cilindros')
        // PEPS/FIFO debe consumir:
        // - 10 kg de Lote 1 a $5.00 = $50.00 -> Lote 1 saldo = 0, estado = 'agotado'
        // - 5 kg de Lote 2 a $8.00 = $40.00 -> Lote 2 saldo = 15, estado = 'activo'
        // Costo total esperado = $90.00
        $resultado = $this->modelo->registrarConsumoEnsayo(
            $insumoId,
            15.0,
            450,
            'RT-FM-61',
            1,
            'Ensayo de rotura de 5 cilindros serie A'
        );

        $this->assertTrue($resultado['exito']);
        $this->assertEquals(15.0, (float)$resultado['cantidad_consumida']);
        $this->assertEquals(90.0, (float)$resultado['costo_total']);
        $this->assertCount(2, $resultado['lotes_afectados']);

        // Verificar detalle primer lote consumido (Lote 1 agotado)
        $this->assertEquals($loteId1, $resultado['lotes_afectados'][0]['lote_id']);
        $this->assertEquals(10.0, (float)$resultado['lotes_afectados'][0]['cantidad']);
        $this->assertEquals(5.00, (float)$resultado['lotes_afectados'][0]['costo_unitario']);
        $this->assertEquals(50.0, (float)$resultado['lotes_afectados'][0]['subtotal']);
        $this->assertEquals(0.0, (float)$resultado['lotes_afectados'][0]['saldo_nuevo']);
        $this->assertEquals('agotado', $resultado['lotes_afectados'][0]['estado']);

        // Verificar detalle segundo lote consumido (Lote 2 con saldo remanente)
        $this->assertEquals($loteId2, $resultado['lotes_afectados'][1]['lote_id']);
        $this->assertEquals(5.0, (float)$resultado['lotes_afectados'][1]['cantidad']);
        $this->assertEquals(8.00, (float)$resultado['lotes_afectados'][1]['costo_unitario']);
        $this->assertEquals(40.0, (float)$resultado['lotes_afectados'][1]['subtotal']);
        $this->assertEquals(15.0, (float)$resultado['lotes_afectados'][1]['saldo_nuevo']);
        $this->assertEquals('activo', $resultado['lotes_afectados'][1]['estado']);

        // Verificar estado persistido en BD de Lote 1
        $lote1Bd = $this->modelo->obtenerLote($loteId1);
        $this->assertEquals(0.0, (float)$lote1Bd['saldo']);
        $this->assertEquals('agotado', $lote1Bd['estado']);

        // Verificar estado persistido en BD de Lote 2
        $lote2Bd = $this->modelo->obtenerLote($loteId2);
        $this->assertEquals(15.0, (float)$lote2Bd['saldo']);
        $this->assertEquals('activo', $lote2Bd['estado']);

        // Verificar Kárdex
        $kardex = $this->modelo->listarKardex($insumoId);
        $this->assertCount(2, $kardex);
        foreach ($kardex as $mov) {
            $this->assertEquals('consumo_ensayo', $mov['tipo_movimiento']);
            $this->assertEquals('ensayo', $mov['referencia_tipo']);
            $this->assertEquals(450, (int)$mov['referencia_id']);
        }

        // Probar excepción si se requiere más de lo disponible (quedan 15, pedir 20)
        try {
            $this->modelo->registrarConsumoEnsayo($insumoId, 20.0, 451, 'RT-FM-61', 1);
            $this->fail('Se esperaba una Exception por stock insuficiente.');
        } catch (Exception $e) {
            $this->assertStringContainsString('insuficiente', strtolower($e->getMessage()));
        }
    }

    /**
     * CP-INV-03: Detección de lotes próximos a vencer (< 30 días) y ejecución de ajustes positivos y negativos de kardex.
     */
    public function testCpInv03VencimientosYAjustesKardex(): void {
        $codigo = 'TEST-INS-03-' . bin2hex(random_bytes(4));
        $insumoId = $this->modelo->crearInsumo([
            'codigo' => $codigo,
            'nombre' => 'Reactivo Químico pH Test ' . $codigo,
            'cantidad' => 33.0,
            'unidad_medida' => 'frasco',
            'stock_minimo' => 10.0,
        ]);
        $this->insumosCreados[] = $insumoId;

        $fechaVencido = date('Y-m-d', strtotime('-5 days'));
        $fechaPorVencer = date('Y-m-d', strtotime('+12 days'));
        $fechaVigente = date('Y-m-d', strtotime('+90 days'));

        // Lote Vencido (saldo 5)
        $loteVencidoId = $this->modelo->crearLote([
            'insumo_id' => $insumoId,
            'lote' => 'LOT-VENCIDO-' . bin2hex(random_bytes(3)),
            'fecha_vencimiento' => $fechaVencido,
            'cantidad_inicial' => 5.0,
            'saldo' => 5.0,
            'costo_unitario' => 20.0,
            'estado' => 'activo',
        ]);
        $this->lotesCreados[] = $loteVencidoId;

        // Lote Por Vencer (saldo 8)
        $lotePorVencerId = $this->modelo->crearLote([
            'insumo_id' => $insumoId,
            'lote' => 'LOT-POR-VENCER-' . bin2hex(random_bytes(3)),
            'fecha_vencimiento' => $fechaPorVencer,
            'cantidad_inicial' => 8.0,
            'saldo' => 8.0,
            'costo_unitario' => 22.0,
            'estado' => 'activo',
        ]);
        $this->lotesCreados[] = $lotePorVencerId;

        // Lote Vigente (saldo 20)
        $loteVigenteId = $this->modelo->crearLote([
            'insumo_id' => $insumoId,
            'lote' => 'LOT-VIGENTE-' . bin2hex(random_bytes(3)),
            'fecha_vencimiento' => $fechaVigente,
            'cantidad_inicial' => 20.0,
            'saldo' => 20.0,
            'costo_unitario' => 25.0,
            'estado' => 'activo',
        ]);
        $this->lotesCreados[] = $loteVigenteId;

        // 1. Validar detección de caducidades en listarLotes()
        $lotes = $this->modelo->listarLotes($insumoId);
        $mapLotes = [];
        foreach ($lotes as $l) {
            $mapLotes[$l['id']] = $l;
        }

        $this->assertTrue($mapLotes[$loteVencidoId]['vencido']);
        $this->assertFalse($mapLotes[$loteVencidoId]['por_vencer']);
        $this->assertEquals('vencido', $mapLotes[$loteVencidoId]['alerta_vencimiento']);

        $this->assertFalse($mapLotes[$lotePorVencerId]['vencido']);
        $this->assertTrue($mapLotes[$lotePorVencerId]['por_vencer']);
        $this->assertEquals('por_vencer', $mapLotes[$lotePorVencerId]['alerta_vencimiento']);
        $this->assertLessThanOrEqual(30, $mapLotes[$lotePorVencerId]['dias_para_vencer']);

        $this->assertFalse($mapLotes[$loteVigenteId]['vencido']);
        $this->assertFalse($mapLotes[$loteVigenteId]['por_vencer']);
        $this->assertEquals('vigente', $mapLotes[$loteVigenteId]['alerta_vencimiento']);

        // 2. Validar obtenerAlertas()
        $alertas = $this->modelo->obtenerAlertas();
        $this->assertGreaterThanOrEqual(1, $alertas['conteo_por_vencer']);
        $this->assertGreaterThanOrEqual(1, $alertas['conteo_vencidos']);

        // 3. Probar Ajuste Positivo (+10 frascos en Lote Vigente)
        // Saldo previo = 20, nuevo saldo = 30
        $ajustePos = $this->modelo->registrarAjuste(
            $insumoId,
            $loteVigenteId,
            'ajuste_positivo',
            10.0,
            25.0,
            1,
            'Reingreso de frascos sobrantes de muestreo'
        );
        $this->assertTrue($ajustePos['exito']);
        $this->assertEquals(20.0, (float)$ajustePos['saldo_anterior']);
        $this->assertEquals(30.0, (float)$ajustePos['saldo_nuevo']);

        $loteActualizado = $this->modelo->obtenerLote($loteVigenteId);
        $this->assertEquals(30.0, (float)$loteActualizado['saldo']);

        // 4. Probar Ajuste Negativo (-12 frascos en Lote Vigente)
        // Saldo previo = 30, nuevo saldo = 18
        $ajusteNeg = $this->modelo->registrarAjuste(
            $insumoId,
            $loteVigenteId,
            'ajuste_negativo',
            12.0,
            25.0,
            1,
            'Merma por daño en transporte'
        );
        $this->assertTrue($ajusteNeg['exito']);
        $this->assertEquals(30.0, (float)$ajusteNeg['saldo_anterior']);
        $this->assertEquals(18.0, (float)$ajusteNeg['saldo_nuevo']);

        $loteActualizado = $this->modelo->obtenerLote($loteVigenteId);
        $this->assertEquals(18.0, (float)$loteActualizado['saldo']);

        // 5. Verificar movimientos registrados en Kárdex
        $kardex = $this->modelo->listarKardex($insumoId);
        $tiposMov = array_column($kardex, 'tipo_movimiento');
        $this->assertContains('ajuste_positivo', $tiposMov);
        $this->assertContains('ajuste_negativo', $tiposMov);
    }

    /**
     * Prueba adicional: Consumo FIFO cuando dos lotes tienen la misma fecha de caducidad.
     * Debe priorizar el lote ingresado primero (id ASC).
     */
    public function testConsumoFifoMismaFechaUsaIdAscendente(): void {
        $codigo = 'TEST-FIFO-ID-' . bin2hex(random_bytes(4));
        $insumoId = $this->modelo->crearInsumo([
            'codigo' => $codigo,
            'nombre' => 'Insumo FIFO ID ' . $codigo,
            'cantidad' => 20.0,
            'unidad_medida' => 'kg',
            'stock_minimo' => 5.0,
        ]);
        $this->insumosCreados[] = $insumoId;

        $mismaFecha = date('Y-m-d', strtotime('+45 days'));

        $lotePrimero = $this->modelo->crearLote([
            'insumo_id' => $insumoId,
            'lote' => 'LOT-FIRST-' . bin2hex(random_bytes(3)),
            'fecha_vencimiento' => $mismaFecha,
            'cantidad_inicial' => 10.0,
            'saldo' => 10.0,
            'costo_unitario' => 10.00,
            'estado' => 'activo',
        ]);
        $this->lotesCreados[] = $lotePrimero;

        $loteSegundo = $this->modelo->crearLote([
            'insumo_id' => $insumoId,
            'lote' => 'LOT-SECOND-' . bin2hex(random_bytes(3)),
            'fecha_vencimiento' => $mismaFecha,
            'cantidad_inicial' => 10.0,
            'saldo' => 10.0,
            'costo_unitario' => 12.00,
            'estado' => 'activo',
        ]);
        $this->lotesCreados[] = $loteSegundo;

        // Consumir 8 unidades: debe tomarlas íntegramente de $lotePrimero
        $res = $this->modelo->registrarConsumoEnsayo($insumoId, 8.0, 999, 'RT-FM-25', 1);
        $this->assertCount(1, $res['lotes_afectados']);
        $this->assertEquals($lotePrimero, $res['lotes_afectados'][0]['lote_id']);
        $this->assertEquals(2.0, (float)$res['lotes_afectados'][0]['saldo_nuevo']);

        // El segundo lote debe seguir intacto con 10.0
        $lote2 = $this->modelo->obtenerLote($loteSegundo);
        $this->assertEquals(10.0, (float)$lote2['saldo']);
    }

    /**
     * Prueba adicional: Ajuste positivo sin lote especificado genera automáticamente lote de ajuste.
     */
    public function testAjustePositivoSinLoteCreaLoteAutomatico(): void {
        $codigo = 'TEST-AUTO-' . bin2hex(random_bytes(4));
        $insumoId = $this->modelo->crearInsumo([
            'codigo' => $codigo,
            'nombre' => 'Insumo Auto Lote ' . $codigo,
            'cantidad' => 0.0,
            'unidad_medida' => 'l',
            'stock_minimo' => 2.0,
        ]);
        $this->insumosCreados[] = $insumoId;

        $res = $this->modelo->registrarAjuste(
            $insumoId,
            null,
            'ajuste_positivo',
            14.5,
            7.50,
            1,
            'Ingreso inicial por ajuste sin lote previo'
        );

        $this->assertTrue($res['exito']);
        $this->assertGreaterThan(0, $res['lote_id']);
        $this->assertStringStartsWith('AJUSTE-', $res['lote_codigo']);
        $this->assertEquals(14.5, (float)$res['saldo_nuevo']);
        $this->lotesCreados[] = $res['lote_id'];

        $insumo = $this->modelo->obtenerInsumo($insumoId);
        $this->assertEquals(14.5, (float)$insumo['saldo']);
    }

    /**
     * Prueba adicional: Ajuste negativo que excede el saldo debe arrojar excepción controlada.
     */
    public function testAjusteNegativoSaldoInsuficienteLanzaExcepcion(): void {
        $codigo = 'TEST-EXCESS-' . bin2hex(random_bytes(4));
        $insumoId = $this->modelo->crearInsumo([
            'codigo' => $codigo,
            'nombre' => 'Insumo Saldo Test ' . $codigo,
            'cantidad' => 5.0,
            'unidad_medida' => 'un',
            'stock_minimo' => 1.0,
        ]);
        $this->insumosCreados[] = $insumoId;

        $loteId = $this->modelo->crearLote([
            'insumo_id' => $insumoId,
            'lote' => 'LOT-EXCESS-1',
            'fecha_vencimiento' => null,
            'cantidad_inicial' => 5.0,
            'saldo' => 5.0,
            'costo_unitario' => 1.00,
            'estado' => 'activo',
        ]);
        $this->lotesCreados[] = $loteId;

        $this->expectException(Exception::class);
        $this->expectExceptionMessageMatches('/Saldo insuficiente/i');

        $this->modelo->registrarAjuste(
            $insumoId,
            $loteId,
            'ajuste_negativo',
            10.0,
            1.00,
            1,
            'Intento de rebaja mayor al saldo'
        );
    }

    /**
     * Prueba del endpoint JSON alertasJson del controlador.
     */
    public function testControladorAlertasJson(): void {
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_rol'] = 1; // Administrador

        $controlador = new \Cycsa\Modulos\Inventario\Controladores\InventarioControlador();
        $peticion = new \Cycsa\Nucleo\Peticion();
        $respuesta = new \Cycsa\Nucleo\Respuesta();

        ob_start();
        $controlador->alertasJson($peticion, $respuesta);
        $salida = ob_get_clean();

        $this->assertNotEmpty($salida);
        $json = json_decode($salida, true);
        $this->assertIsArray($json);
        $this->assertTrue($json['ok']);
        $this->assertArrayHasKey('alertas', $json);
        $this->assertArrayHasKey('conteo_bajo_stock', $json['alertas']);
        $this->assertArrayHasKey('conteo_por_vencer', $json['alertas']);
        $this->assertArrayHasKey('conteo_vencidos', $json['alertas']);
    }
}
