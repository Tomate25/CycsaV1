<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo;
use Cycsa\Modulos\Contabilidad\Modelos\ConfigCuentasModelo;
use Cycsa\Modulos\Contabilidad\Controladores\ContabilidadControlador;
use Cycsa\Modulos\Configuracion\Servicios\MigradorServicio;
use PDO;

class CxpLineasDetalleTest extends TestCase {

    private PDO $db;
    private ContabilidadModelo $modelo;
    private array $cxpCreadas = [];
    private array $asientosCreados = [];

    protected function setUp(): void {
        parent::setUp();
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Asegurar que la migración 016 haya corrido
        $ruta016 = dirname(__DIR__, 2) . '/database/migrations/016_cxp_lineas_detalle_gastos.php';
        if (file_exists($ruta016)) {
            $db = $this->db;
            ob_start();
            include $ruta016;
            ob_end_clean();
        }

        $this->modelo = new ContabilidadModelo();

        // Configurar sesión para pruebas de controlador
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['csrf_token'] = 'test_csrf_token_cxp_lineas_' . uniqid();
    }

    protected function tearDown(): void {
        // Limpieza de datos creados en las pruebas
        if (!empty($this->cxpCreadas)) {
            $ids = implode(',', array_map('intval', $this->cxpCreadas));
            try {
                $this->db->exec("DELETE FROM cuentas_por_pagar_detalle WHERE cxp_id IN ($ids)");
                $this->db->exec("DELETE FROM cuentas_por_pagar WHERE id IN ($ids)");
            } catch (\Throwable $e) {}
        }

        if (!empty($this->asientosCreados)) {
            $ids = implode(',', array_map('intval', $this->asientosCreados));
            try {
                $this->db->exec("DELETE FROM partidas_diario_detalles WHERE id_partida IN ($ids)");
                $this->db->exec("DELETE FROM partidas_diario WHERE id IN ($ids)");
            } catch (\Throwable $e) {}
        }

        parent::tearDown();
    }

    /**
     * TAREA-049: Validar que la migración 016 cree la tabla cuentas_por_pagar_detalle con las columnas requeridas
     * y que MigradorServicio lo integre bajo FASE 10 de forma segura e idempotente.
     */
    public function testMigracion016CreaTablaYMigradorServicioIdempotente(): void {
        $tablas = $this->db->query("SHOW TABLES LIKE 'cuentas_por_pagar_detalle'")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertNotEmpty($tablas, "La tabla 'cuentas_por_pagar_detalle' debe existir en la base de datos.");

        $cols = $this->db->query("SHOW COLUMNS FROM `cuentas_por_pagar_detalle`")->fetchAll(PDO::FETCH_COLUMN);
        $colsLower = array_map('strtolower', $cols);

        $columnasEsperadas = [
            'id', 'cxp_id', 'cuenta_contable_id', 'concepto',
            'monto', 'iva', 'retencion_ir', 'retencion_alcaldia',
            'total', 'created_at', 'updated_at'
        ];

        foreach ($columnasEsperadas as $col) {
            $this->assertContains($col, $colsLower, "La columna '{$col}' debe existir en cuentas_por_pagar_detalle.");
        }

        // Ejecución idempotente en MigradorServicio
        $resMig = MigradorServicio::asegurarTablas($this->db);
        $this->assertIsArray($resMig);
        $this->assertArrayNotHasKey('error', $resMig, "MigradorServicio no debe arrojar errores con migración 016.");
    }

    /**
     * TAREA-049: Validar creación de CXP con múltiples partidas de gasto distintas
     * (ej. papelería 6010501 + viáticos 6010508 + CIF 5010308).
     */
    public function testCreacionCxpConMultiplesPartidasDeGasto(): void {
        // Resolver cuentas contables requeridas dinámicamente
        $stmtCta = $this->db->prepare("SELECT id FROM cuentas_contables WHERE codigo = :codigo AND tipo = 'DETALLE' LIMIT 1");

        $stmtCta->execute(['codigo' => '6010501']); // Papelería / Gastos de oficina
        $idCta1 = (int)($stmtCta->fetchColumn() ?: 306);

        $stmtCta->execute(['codigo' => '6010508']); // Viáticos / Botiquín / Gastos grales
        $idCta2 = (int)($stmtCta->fetchColumn() ?: 313);

        $stmtCta->execute(['codigo' => '5010308']); // CIF Materiales Lab
        $idCta3 = (int)($stmtCta->fetchColumn() ?: 261);

        $this->assertGreaterThan(0, $idCta1);
        $this->assertGreaterThan(0, $idCta2);
        $this->assertGreaterThan(0, $idCta3);

        $numFactura = 'FAC-TEST-' . rand(10000, 99999);
        $cabecera = [
            'proveedor_nombre'  => 'Proveedor Multi-Partidas S.A.',
            'factura_numero'    => $numFactura,
            'fecha_emision'     => date('Y-m-d'),
            'fecha_vencimiento' => date('Y-m-d', strtotime('+15 days')),
            'notas'             => 'Prueba de CXP con desglose multi-partidas TAREA-049'
        ];

        $lineas = [
            [
                'cuenta_contable_id' => $idCta1,
                'concepto'           => 'Compra de papelería e insumos de oficina',
                'monto'              => 500.00,
                'iva'                => 75.00,
                'retencion_ir'       => 10.00, // 2% IR
                'retencion_alcaldia' => 5.00,  // 1% Alcaldía
                'total'              => 560.00
            ],
            [
                'cuenta_contable_id' => $idCta2,
                'concepto'           => 'Gastos de viáticos y suministros de botiquín',
                'monto'              => 300.00,
                'iva'                => 45.00,
                'retencion_ir'       => 6.00,  // 2% IR
                'retencion_alcaldia' => 3.00,  // 1% Alcaldía
                'total'              => 336.00
            ],
            [
                'cuenta_contable_id' => $idCta3,
                'concepto'           => 'Reactivos químicos y materiales CIF de laboratorio',
                'monto'              => 1200.00,
                'iva'                => 180.00,
                'retencion_ir'       => 24.00, // 2% IR
                'retencion_alcaldia' => 12.00, // 1% Alcaldía
                'total'              => 1344.00
            ],
        ];

        $resultado = $this->modelo->guardarCxpConDetalle($cabecera, $lineas);

        $this->assertTrue($resultado['exito'], "guardarCxpConDetalle debe completarse con éxito: " . ($resultado['mensaje'] ?? ''));
        $this->assertGreaterThan(0, $resultado['cxp_id']);
        $this->assertGreaterThan(0, $resultado['asiento_id']);
        $this->assertSame(3, $resultado['lineas_guardadas']);

        $cxpId = (int)$resultado['cxp_id'];
        $asientoId = (int)$resultado['asiento_id'];
        $this->cxpCreadas[] = $cxpId;
        $this->asientosCreados[] = $asientoId;

        // Verificar registro de cabecera en cuentas_por_pagar
        $stmtCxp = $this->db->prepare("SELECT * FROM cuentas_por_pagar WHERE id = :id");
        $stmtCxp->execute(['id' => $cxpId]);
        $cxp = $stmtCxp->fetch(PDO::FETCH_ASSOC);
        $this->assertNotEmpty($cxp);
        $this->assertSame($numFactura, $cxp['factura_numero']);
        $this->assertSame('Pendiente', $cxp['estado']);
        $this->assertEquals(2240.00, (float)$cxp['monto'], '', 0.01); // 560 + 336 + 1344
        $this->assertEquals(2240.00, (float)$cxp['saldo'], '', 0.01);

        // Verificar partidas en cuentas_por_pagar_detalle usando obtenerDetalleCxp
        $partidasDetalle = $this->modelo->obtenerDetalleCxp($cxpId);
        $this->assertCount(3, $partidasDetalle);

        $this->assertSame($idCta1, (int)$partidasDetalle[0]['cuenta_contable_id']);
        $this->assertEquals(500.00, (float)$partidasDetalle[0]['monto'], '', 0.01);
        $this->assertEquals(75.00, (float)$partidasDetalle[0]['iva'], '', 0.01);
        $this->assertEquals(10.00, (float)$partidasDetalle[0]['retencion_ir'], '', 0.01);
        $this->assertEquals(5.00, (float)$partidasDetalle[0]['retencion_alcaldia'], '', 0.01);
        $this->assertEquals(560.00, (float)$partidasDetalle[0]['total'], '', 0.01);

        $this->assertSame($idCta2, (int)$partidasDetalle[1]['cuenta_contable_id']);
        $this->assertEquals(300.00, (float)$partidasDetalle[1]['monto'], '', 0.01);
        $this->assertEquals(45.00, (float)$partidasDetalle[1]['iva'], '', 0.01);
        $this->assertEquals(6.00, (float)$partidasDetalle[1]['retencion_ir'], '', 0.01);
        $this->assertEquals(3.00, (float)$partidasDetalle[1]['retencion_alcaldia'], '', 0.01);
        $this->assertEquals(336.00, (float)$partidasDetalle[1]['total'], '', 0.01);

        $this->assertSame($idCta3, (int)$partidasDetalle[2]['cuenta_contable_id']);
        $this->assertEquals(1200.00, (float)$partidasDetalle[2]['monto'], '', 0.01);
        $this->assertEquals(180.00, (float)$partidasDetalle[2]['iva'], '', 0.01);
        $this->assertEquals(24.00, (float)$partidasDetalle[2]['retencion_ir'], '', 0.01);
        $this->assertEquals(12.00, (float)$partidasDetalle[2]['retencion_alcaldia'], '', 0.01);
        $this->assertEquals(1344.00, (float)$partidasDetalle[2]['total'], '', 0.01);
    }

    /**
     * TAREA-049: Validar aplicación correcta de IVA acreditable y retenciones fiscales
     * y el balance exacto Debe = Haber en el asiento compuesto generado.
     */
    public function testBalanceExactoDebeHaberEnAsientoGenerado(): void {
        $stmtCta = $this->db->prepare("SELECT id FROM cuentas_contables WHERE codigo = :codigo LIMIT 1");
        $stmtCta->execute(['codigo' => '6010504']);
        $idCtaGasto1 = (int)($stmtCta->fetchColumn() ?: 309);

        $stmtCta->execute(['codigo' => '5010308']);
        $idCtaGasto2 = (int)($stmtCta->fetchColumn() ?: 261);

        $numFactura = 'FAC-BAL-' . rand(10000, 99999);
        $cabecera = [
            'proveedor_nombre' => 'Proveedor Fiscal S.A.',
            'factura_numero'   => $numFactura,
            'fecha_emision'    => date('Y-m-d'),
        ];

        // Línea 1 con IVA y Retenciones
        // Subtotal: 1000.00, IVA: 150.00, Ret IR (2%): 20.00, Ret Alc (1%): 10.00 => Total neto: 1120.00
        // Línea 2 con IVA y Retenciones
        // Subtotal: 800.00, IVA: 120.00, Ret IR (2%): 16.00, Ret Alc (1%): 8.00 => Total neto: 896.00
        $lineas = [
            [
                'cuenta_contable_id' => $idCtaGasto1,
                'concepto'           => 'Línea de materiales administrativos',
                'monto'              => 1000.00,
                'iva'                => 150.00,
                'retencion_ir'       => 20.00,
                'retencion_alcaldia' => 10.00,
            ],
            [
                'cuenta_contable_id' => $idCtaGasto2,
                'concepto'           => 'Línea de insumos técnicos de laboratorio',
                'monto'              => 800.00,
                'iva'                => 120.00,
                'retencion_ir'       => 16.00,
                'retencion_alcaldia' => 8.00,
            ],
        ];

        $res = $this->modelo->guardarCxpConDetalle($cabecera, $lineas);
        $this->assertTrue($res['exito']);

        $asientoId = (int)$res['asiento_id'];
        $this->cxpCreadas[] = (int)$res['cxp_id'];
        $this->asientosCreados[] = $asientoId;

        // Consultar líneas del asiento contable generado
        $detallesAsiento = $this->modelo->obtenerAsientoDetalles($asientoId);
        $this->assertNotEmpty($detallesAsiento);

        $totalDebe = 0.0;
        $totalHaber = 0.0;
        $debesPorCuenta = [];
        $haberesPorCuenta = [];

        foreach ($detallesAsiento as $lineaAsiento) {
            $d = (float)$lineaAsiento['debe'];
            $h = (float)$lineaAsiento['haber'];
            $ctaId = (int)$lineaAsiento['id_cuenta_contable'];

            $totalDebe += $d;
            $totalHaber += $h;

            if ($d > 0) $debesPorCuenta[$ctaId] = ($debesPorCuenta[$ctaId] ?? 0) + $d;
            if ($h > 0) $haberesPorCuenta[$ctaId] = ($haberesPorCuenta[$ctaId] ?? 0) + $h;
        }

        // Comprobación estricta de partida doble: round(SUM(debe), 2) === round(SUM(haber), 2)
        $this->assertEquals(round($totalDebe, 2), round($totalHaber, 2), "El asiento contable debe tener balance exacto Debe = Haber.");
        $this->assertEquals(2070.00, round($totalDebe, 2)); // Subtotales (1000 + 800) + IVA (150 + 120) = 2070.00
        $this->assertEquals(2070.00, round($totalHaber, 2)); // Ret IR (36) + Ret Alc (18) + Proveedores (2016) = 2070.00

        // Verificar que el Debe contiene los subtotales de gasto
        $this->assertArrayHasKey($idCtaGasto1, $debesPorCuenta);
        $this->assertEquals(1000.00, $debesPorCuenta[$idCtaGasto1], '', 0.01);
        $this->assertArrayHasKey($idCtaGasto2, $debesPorCuenta);
        $this->assertEquals(800.00, $debesPorCuenta[$idCtaGasto2], '', 0.01);

        // Verificar que el Debe contiene la suma de IVA Acreditable (270.00)
        $idCuentaIva = ConfigCuentasModelo::obtenerIdPorClave('IVA_ACREDITABLE', $this->db) ?? 206;
        if ($idCuentaIva === 206) {
            $stmtC = $this->db->query("SELECT id FROM cuentas_contables WHERE codigo = '1010601' LIMIT 1");
            $idCuentaIva = (int)($stmtC->fetchColumn() ?: 206);
        }
        $this->assertArrayHasKey($idCuentaIva, $debesPorCuenta, "El IVA acreditable debe figurar en el Debe.");
        $this->assertEquals(270.00, $debesPorCuenta[$idCuentaIva], '', 0.01);

        // Verificar que el Haber contiene Retención IR (36.00)
        $idCuentaIr = ConfigCuentasModelo::obtenerIdPorClave('IR_FUENTE_2', $this->db) ?? 154;
        if ($idCuentaIr === 154) {
            $stmtC = $this->db->query("SELECT id FROM cuentas_contables WHERE codigo = '2010502' LIMIT 1");
            $idCuentaIr = (int)($stmtC->fetchColumn() ?: 154);
        }
        $this->assertArrayHasKey($idCuentaIr, $haberesPorCuenta, "La retención IR debe figurar en el Haber.");
        $this->assertEquals(36.00, $haberesPorCuenta[$idCuentaIr], '', 0.01);

        // Verificar que el Haber contiene Retención Alcaldía 1% (18.00)
        $idCuentaAlc = ConfigCuentasModelo::obtenerIdPorClave('RETENCION_ALCALDIA_1', $this->db)
            ?? ConfigCuentasModelo::obtenerIdPorClave('RETENCION_ALCALDIA', $this->db)
            ?? 153;
        if ($idCuentaAlc === 153) {
            $stmtC = $this->db->query("SELECT id FROM cuentas_contables WHERE codigo = '2010507' LIMIT 1");
            $idCuentaAlc = (int)($stmtC->fetchColumn() ?: 153);
        }
        $this->assertArrayHasKey($idCuentaAlc, $haberesPorCuenta, "La retención de alcaldía debe figurar en el Haber.");
        $this->assertEquals(18.00, $haberesPorCuenta[$idCuentaAlc], '', 0.01);

        // Verificar que el Haber contiene Proveedores por el neto (2016.00)
        $idCuentaProv = ConfigCuentasModelo::obtenerIdPorClave('PROVEEDORES', $this->db) ?? 121;
        $this->assertArrayHasKey($idCuentaProv, $haberesPorCuenta, "Proveedores debe figurar en el Haber por el saldo neto.");
        $this->assertEquals(2016.00, $haberesPorCuenta[$idCuentaProv], '', 0.01);
    }

    /**
     * TAREA-049: Validar rechazo controlado si alguna cuenta contable no existe.
     * Integridad transaccional ACID: no debe quedar ningún registro huérfano.
     */
    public function testRechazoControladoSiCuentaNoExiste(): void {
        $numFacturaInvalida = 'FAC-ERR-NO-EXISTE-' . rand(10000, 99999);
        $cuentaInexistenteId = 999999; // ID no existente

        $cabecera = [
            'proveedor_nombre' => 'Proveedor Inexistente',
            'factura_numero'   => $numFacturaInvalida,
            'fecha_emision'    => date('Y-m-d')
        ];

        $lineas = [
            [
                'cuenta_contable_id' => $cuentaInexistenteId,
                'concepto'           => 'Partida con cuenta falsa',
                'monto'              => 100.00,
                'iva'                => 15.00
            ]
        ];

        $resultado = $this->modelo->guardarCxpConDetalle($cabecera, $lineas);

        $this->assertFalse($resultado['exito'], "Debe rechazar la operación cuando una cuenta no existe.");
        $this->assertStringContainsString("no existe", strtolower($resultado['mensaje']));

        // Verificar que no se insertó nada en la BD (Rollback ACID)
        $stmtCxp = $this->db->prepare("SELECT COUNT(*) FROM cuentas_por_pagar WHERE factura_numero = :fn");
        $stmtCxp->execute(['fn' => $numFacturaInvalida]);
        $this->assertSame(0, (int)$stmtCxp->fetchColumn(), "No debe existir registro en cuentas_por_pagar tras rollback.");
    }

    /**
     * TAREA-049: Validar rechazo controlado si alguna cuenta contable es de tipo MAYOR (no DETALLE).
     */
    public function testRechazoControladoSiCuentaNoEsDetalle(): void {
        // Obtener una cuenta de tipo MAYOR (ej. 601 o 50103)
        $stmtMayor = $this->db->query("SELECT id, codigo, nombre FROM cuentas_contables WHERE tipo = 'MAYOR' LIMIT 1");
        $cuentaMayor = $stmtMayor->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($cuentaMayor, "Debe existir al menos una cuenta de tipo MAYOR para la prueba.");
        $idCuentaMayor = (int)$cuentaMayor['id'];

        $numFacturaMayor = 'FAC-ERR-MAYOR-' . rand(10000, 99999);
        $cabecera = [
            'proveedor_nombre' => 'Proveedor Cuenta Mayor',
            'factura_numero'   => $numFacturaMayor,
            'fecha_emision'    => date('Y-m-d')
        ];

        $lineas = [
            [
                'cuenta_contable_id' => $idCuentaMayor,
                'concepto'           => 'Partida imputada a cuenta de tipo MAYOR',
                'monto'              => 250.00,
                'iva'                => 37.50
            ]
        ];

        $resultado = $this->modelo->guardarCxpConDetalle($cabecera, $lineas);

        $this->assertFalse($resultado['exito'], "Debe rechazar la operación cuando una cuenta no es de tipo DETALLE.");
        $this->assertStringContainsString("no es de tipo detalle", strtolower($resultado['mensaje']));

        // Verificar que no se insertó nada en la BD
        $stmtCxp = $this->db->prepare("SELECT COUNT(*) FROM cuentas_por_pagar WHERE factura_numero = :fn");
        $stmtCxp->execute(['fn' => $numFacturaMayor]);
        $this->assertSame(0, (int)$stmtCxp->fetchColumn(), "No debe existir registro en cuentas_por_pagar.");
    }

    /**
     * TAREA-049: Validar creación de CXP con partidas multi-línea vía AJAX en ContabilidadControlador.
     */
    public function testControladorGuardarCxpConDetalleViaAjax(): void {
        $controlador = new ContabilidadControlador();

        $stmtCta = $this->db->query("SELECT id FROM cuentas_contables WHERE tipo = 'DETALLE' LIMIT 1");
        $idCta = (int)$stmtCta->fetchColumn();

        $numFactura = 'FAC-AJAX-' . rand(10000, 99999);
        $lineas = [
            [
                'cuenta_contable_id' => $idCta,
                'concepto'           => 'Insumo oficina AJAX',
                'monto'              => 400.00,
                'iva'                => 60.00,
                'retencion_ir'       => 8.00,
                'retencion_alcaldia' => 4.00,
            ]
        ];

        $peticionMock = $this->createMock(Peticion::class);
        $peticionMock->method('esPost')->willReturn(true);
        $peticionMock->method('esAjax')->willReturn(true);
        $peticionMock->method('obtenerDatos')->willReturn([
            'csrf_token'        => $_SESSION['csrf_token'],
            'proveedor_nombre'  => 'Proveedor Controller AJAX S.A.',
            'factura_numero'    => $numFactura,
            'fecha_emision'     => date('Y-m-d'),
            'lineas'            => $lineas
        ]);

        $respuesta = new Respuesta();

        ob_start();
        $controlador->guardarCxp($peticionMock, $respuesta);
        $salida = ob_get_clean();

        $json = json_decode($salida, true);
        $this->assertIsArray($json, "La salida del controlador en modo AJAX debe ser un JSON válido: " . $salida);
        $this->assertTrue($json['ok']);
        $this->assertTrue($json['exito']);
        $this->assertGreaterThan(0, $json['cxp_id']);
        $this->assertEquals(448.00, (float)$json['total_neto'], '', 0.01);

        $this->cxpCreadas[] = (int)$json['cxp_id'];
        if (!empty($json['asiento_id'])) {
            $this->asientosCreados[] = (int)$json['asiento_id'];
        }
    }

    /**
     * TAREA-049: Validar endpoint de consulta de detalle de CXP vía API / AJAX.
     */
    public function testControladorConsultaDetalleCxpViaApi(): void {
        // Crear una CXP previa con partidas
        $stmtCta = $this->db->query("SELECT id FROM cuentas_contables WHERE tipo = 'DETALLE' LIMIT 1");
        $idCta = (int)$stmtCta->fetchColumn();

        $numFactura = 'FAC-QUERY-' . rand(10000, 99999);
        $cabecera = [
            'proveedor_nombre' => 'Proveedor Query Detalle',
            'factura_numero'   => $numFactura,
            'fecha_emision'    => date('Y-m-d')
        ];
        $lineas = [
            [
                'cuenta_contable_id' => $idCta,
                'concepto'           => 'Partida comprobada vía API',
                'monto'              => 350.00,
                'iva'                => 52.50,
                'retencion_ir'       => 7.00,
                'retencion_alcaldia' => 3.50
            ]
        ];

        $resGuardar = $this->modelo->guardarCxpConDetalle($cabecera, $lineas);
        $this->assertTrue($resGuardar['exito']);
        $cxpId = (int)$resGuardar['cxp_id'];
        $this->cxpCreadas[] = $cxpId;
        if (!empty($resGuardar['asiento_id'])) {
            $this->asientosCreados[] = (int)$resGuardar['asiento_id'];
        }

        // Consultar vía controlador
        $controlador = new ContabilidadControlador();
        $peticionMock = $this->createMock(Peticion::class);
        $peticionMock->method('obtenerParametro')->with('id')->willReturn((string)$cxpId);

        $respuesta = new Respuesta();

        ob_start();
        $controlador->detalleCxp($peticionMock, $respuesta);
        $salida = ob_get_clean();

        $json = json_decode($salida, true);
        $this->assertIsArray($json);
        $this->assertTrue($json['ok']);
        $this->assertSame($cxpId, (int)$json['cxp_id']);
        $this->assertCount(1, $json['lineas']);
        $this->assertEquals(350.00, (float)$json['lineas'][0]['monto'], '', 0.01);
        $this->assertEquals(52.50, (float)$json['lineas'][0]['iva'], '', 0.01);
        $this->assertEquals(7.00, (float)$json['lineas'][0]['retencion_ir'], '', 0.01);
        $this->assertEquals(3.50, (float)$json['lineas'][0]['retencion_alcaldia'], '', 0.01);
        $this->assertEquals(392.00, (float)$json['lineas'][0]['total'], '', 0.01);
    }

    /**
     * TAREA-049: Validar aplicación de retención IR 10% (Servicios Profesionales).
     */
    public function testRetencionIr10PorcientoServiciosProfesionales(): void {
        $stmtCta = $this->db->query("SELECT id FROM cuentas_contables WHERE tipo = 'DETALLE' AND codigo LIKE '601%' LIMIT 1");
        $idCta = (int)$stmtCta->fetchColumn();

        $numFactura = 'FAC-IR10-' . rand(10000, 99999);
        $cabecera = [
            'proveedor_nombre'   => 'Consultor Profesional Externo',
            'factura_numero'     => $numFactura,
            'fecha_emision'      => date('Y-m-d'),
            'tipo_retencion_ir'  => 10 // 10% IR
        ];

        // Subtotal: 10,000.00, IVA 15%: 1,500.00, Ret IR 10%: 1,000.00, Ret Alcaldía 1%: 100.00 => Neto: 10,400.00
        $lineas = [
            [
                'cuenta_contable_id' => $idCta,
                'concepto'           => 'Honorarios por peritaje técnico',
                'monto'              => 10000.00,
                'iva'                => 1500.00,
                'retencion_ir'       => 1000.00,
                'retencion_alcaldia' => 100.00,
                'tipo_retencion_ir'  => 10
            ]
        ];

        $res = $this->modelo->guardarCxpConDetalle($cabecera, $lineas);
        $this->assertTrue($res['exito']);

        $asientoId = (int)$res['asiento_id'];
        $this->cxpCreadas[] = (int)$res['cxp_id'];
        $this->asientosCreados[] = $asientoId;

        // Verificar cuenta contable del asiento para IR 10%
        $detallesAsiento = $this->modelo->obtenerAsientoDetalles($asientoId);
        $cuentaIr10Esperada = ConfigCuentasModelo::obtenerIdPorClave('IR_FUENTE_10', $this->db) ?? 151;

        $haberes = [];
        foreach ($detallesAsiento as $da) {
            if ((float)$da['haber'] > 0) {
                $haberes[(int)$da['id_cuenta_contable']] = (float)$da['haber'];
            }
        }

        $this->assertArrayHasKey($cuentaIr10Esperada, $haberes, "El asiento debe registrar el Haber en la cuenta de IR 10%.");
        $this->assertEquals(1000.00, $haberes[$cuentaIr10Esperada], '', 0.01);
    }

    /**
     * TAREA-049: Validar creación de CXP con detalle vía formulario Web convencional (redirección y sesión).
     */
    public function testControladorGuardarCxpConDetalleViaWebForm(): void {
        $controlador = new ContabilidadControlador();

        $stmtCta = $this->db->query("SELECT id FROM cuentas_contables WHERE tipo = 'DETALLE' LIMIT 1");
        $idCta = (int)$stmtCta->fetchColumn();

        $numFactura = 'FAC-WEB-' . rand(10000, 99999);
        $lineas = [
            [
                'cuenta_contable_id' => $idCta,
                'concepto'           => 'Partida formulario web',
                'monto'              => 200.00,
                'iva'                => 30.00,
                'retencion_ir'       => 4.00,
                'retencion_alcaldia' => 2.00,
            ]
        ];

        $peticionMock = $this->createMock(Peticion::class);
        $peticionMock->method('esPost')->willReturn(true);
        $peticionMock->method('esAjax')->willReturn(false); // Modo Web Form
        $peticionMock->method('obtenerDatos')->willReturn([
            'csrf_token'        => $_SESSION['csrf_token'],
            'proveedor_nombre'  => 'Proveedor Web Form S.A.',
            'factura_numero'    => $numFactura,
            'fecha_emision'     => date('Y-m-d'),
            'lineas_json'       => json_encode($lineas) // Enviar vía JSON string de formulario
        ]);

        $respuestaMock = $this->createMock(Respuesta::class);
        $respuestaMock->expects($this->once())
            ->method('redirigir')
            ->with('/Cycsa/publico/contabilidad/cxp');

        $controlador->guardarCxp($peticionMock, $respuestaMock);

        $this->assertArrayHasKey('exito', $_SESSION);
        $this->assertStringContainsString('exitosamente', $_SESSION['exito']);

        // Limpiar registro creado
        $stmtCxp = $this->db->prepare("SELECT id FROM cuentas_por_pagar WHERE factura_numero = :fn LIMIT 1");
        $stmtCxp->execute(['fn' => $numFactura]);
        $cxpId = (int)$stmtCxp->fetchColumn();
        if ($cxpId > 0) {
            $this->cxpCreadas[] = $cxpId;
            $stmtAs = $this->db->prepare("SELECT id FROM partidas_diario WHERE origen = 'CXP' AND origen_id = :id");
            $stmtAs->execute(['id' => $cxpId]);
            $asId = (int)$stmtAs->fetchColumn();
            if ($asId > 0) {
                $this->asientosCreados[] = $asId;
            }
        }
    }

    /**
     * TAREA-049: Validar rechazo si se envían líneas vacías o montos menores o iguales a cero.
     */
    public function testRechazoSiLineasVaciasOMontoCero(): void {
        $numFactura = 'FAC-ZERO-' . rand(10000, 99999);
        $cabecera = [
            'proveedor_nombre' => 'Proveedor Validación',
            'factura_numero'   => $numFactura,
            'fecha_emision'    => date('Y-m-d')
        ];

        // 1. Array vacío
        $resVacio = $this->modelo->guardarCxpConDetalle($cabecera, []);
        $this->assertFalse($resVacio['exito']);
        $this->assertStringContainsString('al menos una partida', strtolower($resVacio['mensaje']));

        // 2. Monto 0.00
        $stmtCta = $this->db->query("SELECT id FROM cuentas_contables WHERE tipo = 'DETALLE' LIMIT 1");
        $idCta = (int)$stmtCta->fetchColumn();

        $resCero = $this->modelo->guardarCxpConDetalle($cabecera, [
            ['cuenta_contable_id' => $idCta, 'concepto' => 'Gasto cero', 'monto' => 0.00]
        ]);
        $this->assertFalse($resCero['exito']);
        $this->assertStringContainsString('mayor a 0', strtolower($resCero['mensaje']));
    }
}
