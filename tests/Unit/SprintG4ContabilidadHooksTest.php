<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo;
use Cycsa\Modulos\Contabilidad\Modelos\ConfigCuentasModelo;
use Cycsa\Modulos\Compras\Modelos\ComprasModelo;
use Cycsa\Modulos\Inventario\Modelos\InventarioModelo;
use Cycsa\Nucleo\Conexion;
use PDO;

/**
 * Sprint G.4 & G.1b / Epic 2 & 3: Erradicación de hardcodes contables y Hooks automáticos G.4
 */
class SprintG4ContabilidadHooksTest extends TestCase {

    private PDO $db;
    private ContabilidadModelo $contaModelo;
    private ComprasModelo $comprasModelo;
    private InventarioModelo $inventarioModelo;
    private array $idsLimpieza = [
        'partidas' => [],
        'recepciones' => [],
        'insumos' => []
    ];

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->contaModelo = new ContabilidadModelo();
        $this->comprasModelo = new ComprasModelo();
        $this->inventarioModelo = new InventarioModelo();
    }

    protected function tearDown(): void {
        foreach ($this->idsLimpieza['partidas'] as $idPartida) {
            $this->db->exec("DELETE FROM partidas_diario_detalles WHERE id_partida = {$idPartida}");
            $this->db->exec("DELETE FROM partidas_diario WHERE id = {$idPartida}");
        }
        foreach ($this->idsLimpieza['recepciones'] as $idRec) {
            $this->db->exec("DELETE FROM recepciones_compra_detalle WHERE recepcion_id = {$idRec}");
            $this->db->exec("DELETE FROM recepciones_compra WHERE id = {$idRec}");
        }
        foreach ($this->idsLimpieza['insumos'] as $idIns) {
            $this->db->exec("DELETE FROM inventario_lotes WHERE insumo_id = {$idIns}");
            $this->db->exec("DELETE FROM inventario_movimientos WHERE insumo_id = {$idIns}");
            $this->db->exec("DELETE FROM inventario_insumos WHERE id = {$idIns}");
        }
        parent::tearDown();
    }

    public function testContabilidadModeloMetodosSinHardcodesMagicos(): void {
        $archivo = dirname(__DIR__, 2) . '/app/Modulos/Contabilidad/Modelos/ContabilidadModelo.php';
        $contenido = file_get_contents($archivo);

        $this->assertStringNotContainsString(
            "['id_cuenta_contable' => 206, 'debe' => 0.0, 'haber' => \$datos['monto']]",
            $contenido,
            'guardarCxc debe resolver la cuenta de ingresos dinámicamente, no hardcoded a 206'
        );

        $this->assertStringNotContainsString(
            "['id_cuenta_contable' => 121, 'debe' => \$monto, 'haber' => 0.0]",
            $contenido,
            'registrarPagoCxp debe resolver la cuenta de proveedores dinámicamente'
        );

        $this->assertStringNotContainsString(
            "\$incomeDefaultAcc = 206;",
            $contenido,
            'reconstruirDiario debe resolver la cuenta de ingresos con ConfigCuentas'
        );
    }

    public function testConfigCuentasModeloObtenerIdPorClaveOrFailLanzaExcepcionAudible(): void {
        // Clave existente debe retornar int > 0
        $idClientes = ConfigCuentasModelo::obtenerIdPorClaveOrFail('CLIENTES', $this->db);
        $this->assertIsInt($idClientes);
        $this->assertGreaterThan(0, $idClientes);

        // Clave inexistente debe lanzar RuntimeException audible, nunca fallar silencioso
        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage("Cuenta contable obligatoria no configurada en config_cuentas: 'CLAVE_INEXISTENTE_XYZ'");
        ConfigCuentasModelo::obtenerIdPorClaveOrFail('CLAVE_INEXISTENTE_XYZ', $this->db);
    }

    public function testRegistrarAnticipoProveedorGeneraAsientoBalanceado(): void {
        $monto = 2500.00;
        $idProveedor = 99991;
        $idBancoCuenta = 1;

        $idPartida = $this->contaModelo->registrarAnticipoProveedor(
            $idProveedor,
            $monto,
            $idBancoCuenta,
            'REF-ANTICIPO-TEST',
            date('Y-m-d')
        );

        $this->assertGreaterThan(0, $idPartida, 'Debe retornar el ID de la partida creada');
        $this->idsLimpieza['partidas'][] = $idPartida;

        $partida = $this->contaModelo->obtenerPartidaPorId($idPartida);
        $this->assertNotNull($partida);
        $this->assertEquals('ANTICIPO_PROV', $partida['origen']);

        $detalles = $this->contaModelo->obtenerDetallesPartida($idPartida);
        $this->assertCount(2, $detalles, 'El asiento de anticipo debe tener 2 líneas (Débito y Crédito)');

        $totalDebe = 0.0;
        $totalHaber = 0.0;
        foreach ($detalles as $det) {
            $totalDebe += (float)$det['debe'];
            $totalHaber += (float)$det['haber'];
        }

        $this->assertEquals($monto, $totalDebe, 'Total DEBE debe ser igual al monto del anticipo');
        $this->assertEquals($totalDebe, $totalHaber, 'DEBE == HABER estricto');
    }

    public function testComprasModeloRegistrarRecepcionCompraEjecutaHookRealYBalancea(): void {
        $cabecera = [
            'proveedor_id' => 1,
            'numero_factura' => 'FAC-HOOK-TEST-' . uniqid(),
            'fecha_recepcion' => date('Y-m-d'),
            'recibido_por' => 1,
            'observaciones' => 'Prueba unitaria hook G.4 recepción compra',
            'estado' => 'completada'
        ];

        $detalles = [
            [
                'orden_compra_detalle_id' => null,
                'insumo_id' => 0,
                'cantidad_recibida' => 5,
                'costo_unitario' => 200.00,
                'lote' => 'LOTE-TEST-01',
                'fecha_vencimiento' => null
            ]
        ];

        $recepcionId = $this->comprasModelo->registrarRecepcionCompra(0, $cabecera, $detalles);
        $this->assertGreaterThan(0, $recepcionId);
        $this->idsLimpieza['recepciones'][] = $recepcionId;

        // Verificar que el hook G.4 generó la partida contable correspondiente
        $stmtP = $this->db->prepare("SELECT id FROM partidas_diario WHERE origen = 'RECEPCION_COMPRA' AND origen_id = :ref LIMIT 1");
        $stmtP->execute(['ref' => $recepcionId]);
        $partidaId = (int)$stmtP->fetchColumn();

        $this->assertGreaterThan(0, $partidaId, 'La recepción debe haber creado automáticamente un asiento contable');
        $this->idsLimpieza['partidas'][] = $partidaId;

        $detallesPartida = $this->contaModelo->obtenerDetallesPartida($partidaId);
        $this->assertCount(2, $detallesPartida);

        $totalDebe = 0.0;
        $totalHaber = 0.0;
        foreach ($detallesPartida as $d) {
            $totalDebe += (float)$d['debe'];
            $totalHaber += (float)$d['haber'];
        }

        $this->assertEquals(1000.00, $totalDebe);
        $this->assertEquals(1000.00, $totalHaber);
    }

    public function testInventarioModeloRegistrarConsumoEnsayoEjecutaHookRealYBalancea(): void {
        // Crear insumo de prueba con lote activo
        $stmtIns = $this->db->prepare("
            INSERT INTO inventario_insumos (nombre, codigo, unidad_medida, cantidad)
            VALUES ('Reactivo Test G4', 'INS-G4-" . uniqid() . "', 'unidad', 10)
        ");
        $stmtIns->execute();
        $insumoId = (int)$this->db->lastInsertId();
        $this->idsLimpieza['insumos'][] = $insumoId;

        $stmtLote = $this->db->prepare("
            INSERT INTO inventario_lotes (insumo_id, lote, costo_unitario, cantidad_inicial, saldo, estado)
            VALUES (:insumo_id, 'LOTE-G4-TEST', 50.00, 10, 10, 'activo')
        ");
        $stmtLote->execute(['insumo_id' => $insumoId]);

        $ensayoId = 99994;
        $resultado = $this->inventarioModelo->registrarConsumoEnsayo(
            $insumoId,
            2.0, // 2 unidades * 50.0 = C$ 100.00
            $ensayoId,
            'Compresión Concreto',
            1,
            'Consumo test G.4'
        );

        $this->assertTrue($resultado['exito']);
        $this->assertEquals(100.00, (float)$resultado['costo_total']);

        // Verificar el asiento contable generado
        $stmtP = $this->db->prepare("SELECT id FROM partidas_diario WHERE origen = 'CONSUMO_INVENTARIO' AND origen_id = :ref LIMIT 1");
        $stmtP->execute(['ref' => $ensayoId]);
        $partidaId = (int)$stmtP->fetchColumn();

        $this->assertGreaterThan(0, $partidaId, 'El consumo debe haber creado automáticamente un asiento contable');
        $this->idsLimpieza['partidas'][] = $partidaId;

        $detallesPartida = $this->contaModelo->obtenerDetallesPartida($partidaId);
        $this->assertCount(2, $detallesPartida);
        $this->assertEquals(100.00, (float)$detallesPartida[0]['debe']);
        $this->assertEquals(100.00, (float)$detallesPartida[1]['haber']);
    }
}
