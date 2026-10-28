<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use PDO;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Compras\Modelos\ComprasModelo;
use Cycsa\Modulos\Compras\Controladores\ComprasControlador;
use Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo;
use Cycsa\Modulos\Contabilidad\Modelos\ConfigCuentasModelo;
use Cycsa\Modulos\Configuracion\Servicios\MigradorServicio;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;

class ComprasUITest extends TestCase {

    private PDO $db;
    private ComprasModelo $comprasModelo;
    private ContabilidadModelo $contabilidadModelo;
    private ConfigCuentasModelo $configCuentasModelo;

    protected function setUp(): void {
        parent::setUp();
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }

        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Asegurar que las migraciones 012, 013 y 015 estén ejecutadas
        $db = $this->db;
        $ruta012 = dirname(__DIR__, 2) . '/database/migrations/012_compras_inventario_proveedores.php';
        if (file_exists($ruta012)) {
            ob_start();
            include $ruta012;
            ob_end_clean();
        }

        $ruta013 = dirname(__DIR__, 2) . '/database/migrations/013_puente_contable_config_cuentas.php';
        if (file_exists($ruta013)) {
            ob_start();
            include $ruta013;
            ob_end_clean();
        }

        $ruta015 = dirname(__DIR__, 2) . '/database/migrations/015_cxp_proveedores_enlace.php';
        if (file_exists($ruta015)) {
            ob_start();
            include $ruta015;
            ob_end_clean();
        }

        $this->comprasModelo = new ComprasModelo($this->db);
        $this->contabilidadModelo = new ContabilidadModelo();
        $this->configCuentasModelo = new ConfigCuentasModelo($this->db);

        // Configurar sesión de prueba
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_nombre'] = 'Admin Pruebas Compras';
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }

    /**
     * TAREA-047: Validación y guardado de proveedor con evaluación ISO 17025 §6.6.
     */
    public function testValidacionYGuardadoProveedorConEvaluacionIso17025(): void {
        $rucUnico = 'J03' . rand(10000000, 99999999);
        $razonSocial = 'Proveedor Calibración ISO Test S.A. ' . uniqid();

        $datosProv = [
            'tipo_id' => 'RUC',
            'identificacion' => $rucUnico,
            'razon_social' => $razonSocial,
            'contacto' => 'Ing. Juan Carlos Morales',
            'telefono' => '+505 8765-4321',
            'email' => 'calibraciones_' . uniqid() . '@proveedor.com',
            'banco' => 'BAC Credomatic',
            'cuenta_bancaria' => '360-987654-1',
            'plazo_pago_dias' => 45,
            'categoria' => 'calibracion',
            'estado' => 'activo'
        ];

        // 1. Guardar nuevo proveedor
        $provId = $this->comprasModelo->guardarProveedor($datosProv);
        $this->assertIsInt($provId);
        $this->assertGreaterThan(0, $provId);

        // 2. Recuperar y verificar estado inicial (sin evaluación)
        $prov = $this->comprasModelo->obtenerProveedorPorId($provId);
        $this->assertNotNull($prov, "El proveedor recién creado debe existir.");
        $this->assertSame($rucUnico, $prov['identificacion']);
        $this->assertSame($razonSocial, $prov['razon_social']);
        $this->assertSame(0, (int)$prov['evaluado'], "Inicialmente el proveedor no debe estar evaluado.");
        $this->assertNull($prov['fecha_evaluacion']);

        // 3. Evaluar proveedor bajo ISO/IEC 17025 §6.6
        $fechaEval = date('Y-m-d');
        $resEval = $this->comprasModelo->evaluarProveedor($provId, true, $fechaEval, 1);
        $this->assertTrue($resEval, "evaluarProveedor debe retornar true.");

        $provEvaluado = $this->comprasModelo->obtenerProveedorPorId($provId);
        $this->assertSame(1, (int)$provEvaluado['evaluado'], "El proveedor debe constar como evaluado/aprobado.");
        $this->assertSame($fechaEval, $provEvaluado['fecha_evaluacion']);

        // 4. Actualizar datos (UPDATE)
        $provEvaluado['contacto'] = 'Lic. María Fernanda Reyes';
        $provEvaluado['plazo_pago_dias'] = 60;
        $idActualizado = $this->comprasModelo->guardarProveedor($provEvaluado);
        $this->assertSame($provId, $idActualizado);

        $provModificado = $this->comprasModelo->obtenerProveedorPorId($provId);
        $this->assertSame('Lic. María Fernanda Reyes', $provModificado['contacto']);
        $this->assertSame(60, (int)$provModificado['plazo_pago_dias']);
        $this->assertSame(1, (int)$provModificado['evaluado'], "La evaluación debe mantenerse tras actualizar otros campos.");

        // 5. Filtro de búsqueda
        $filtrados = $this->comprasModelo->listarProveedores(['busqueda' => $rucUnico]);
        $this->assertNotEmpty($filtrados);
        $this->assertSame($rucUnico, $filtrados[0]['identificacion']);

        // 6. Desevaluar proveedor
        $this->comprasModelo->evaluarProveedor($provId, false, null, 1);
        $provDes = $this->comprasModelo->obtenerProveedorPorId($provId);
        $this->assertSame(0, (int)$provDes['evaluado']);
        $this->assertNull($provDes['fecha_evaluacion']);
    }

    /**
     * TAREA-047: Flujo de creación de OC con detalle, totales e impuestos, y transición a aprobada.
     */
    public function testFlujoCreacionOrdenCompraConTotalesImpuestosYAprobacion(): void {
        // Crear proveedor evaluado para la orden
        $provId = $this->comprasModelo->guardarProveedor([
            'tipo_id' => 'RUC',
            'identificacion' => 'J03' . rand(10000000, 99999999),
            'razon_social' => 'Proveedor Reactivos Laboratorio ' . uniqid(),
            'categoria' => 'insumos',
            'evaluado' => 1,
            'fecha_evaluacion' => date('Y-m-d'),
            'estado' => 'activo'
        ]);

        // Crear insumo de prueba en inventario si no existe
        $codigoInsumo = 'INS-TEST-' . rand(1000, 9999);
        $this->db->prepare("
            INSERT INTO inventario_insumos (codigo, nombre, cantidad, unidad_medida, stock_minimo)
            VALUES (:codigo, :nombre, 50, 'kg', 10)
            ON DUPLICATE KEY UPDATE id=LAST_INSERT_ID(id)
        ")->execute(['codigo' => $codigoInsumo, 'nombre' => 'Sulfato de Sodio Técnico']);
        $insumoId = (int)$this->db->lastInsertId();

        // 1. Guardar orden de compra con cabecera y 2 líneas de detalle
        $cabecera = [
            'proveedor_id' => $provId,
            'fecha' => date('Y-m-d'),
            'estado' => 'SOLICITADA'
        ];

        $detalles = [
            [
                'insumo_id' => $insumoId,
                'descripcion' => 'Sulfato de Sodio Grado Reactivo',
                'cantidad' => 10.0,
                'costo_unitario' => 120.0, // Subtotal: 1200.0, IVA: 180.0, Total: 1380.0
                'aplica_iva' => true,
            ],
            [
                'insumo_id' => null,
                'descripcion' => 'Servicio de calibración certificado',
                'cantidad' => 1.0,
                'costo_unitario' => 800.0, // Subtotal: 800.0, IVA: 0, Total: 800.0
                'aplica_iva' => false,
            ]
        ];

        $ocId = $this->comprasModelo->guardarOrdenCompra($cabecera, $detalles);
        $this->assertIsInt($ocId);
        $this->assertGreaterThan(0, $ocId);

        // 2. Verificar montos acumulados calculados automáticamente
        $oc = $this->comprasModelo->obtenerOrdenCompraPorId($ocId);
        $this->assertNotNull($oc);
        $this->assertSame('SOLICITADA', strtoupper($oc['estado']));
        $this->assertEquals(2000.00, (float)$oc['subtotal'], '', 0.01);
        $this->assertEquals(180.00, (float)$oc['iva'], '', 0.01);
        $this->assertEquals(2180.00, (float)$oc['total'], '', 0.01);

        $this->assertCount(2, $oc['detalles']);
        $this->assertEquals(1200.00, (float)$oc['detalles'][0]['subtotal'], '', 0.01);
        $this->assertEquals(180.00, (float)$oc['detalles'][0]['iva'], '', 0.01);
        $this->assertEquals(1380.00, (float)$oc['detalles'][0]['total'], '', 0.01);

        // 3. Transición de estado: Aprobación formal
        $resAprob = $this->comprasModelo->cambiarEstadoOrdenCompra($ocId, 'APROBADA', 1);
        $this->assertTrue($resAprob);

        $ocAprobada = $this->comprasModelo->obtenerOrdenCompraPorId($ocId);
        $this->assertSame('APROBADA', strtoupper($ocAprobada['estado']));
        $this->assertSame(1, (int)$ocAprobada['aprobado_por']);
        $this->assertNotNull($ocAprobada['fecha_aprobacion']);
    }

    /**
     * TAREA-047: Recepción parcial y total de líneas de compra con impacto en inventario.
     */
    public function testRecepcionParcialYTotalDeLineasDeCompra(): void {
        $provId = $this->comprasModelo->guardarProveedor([
            'tipo_id' => 'RUC',
            'identificacion' => 'J03' . rand(10000000, 99999999),
            'razon_social' => 'Distribuidora Materiales CYCSA ' . uniqid(),
            'evaluado' => 1,
            'estado' => 'activo'
        ]);

        $codA = 'CEMENTO-TEST-' . uniqid();
        $codB = 'ARENA-TEST-' . uniqid();

        $this->db->prepare("INSERT INTO inventario_insumos (codigo, nombre, cantidad, unidad_medida) VALUES (:c, 'Cemento Tipo I', 10, 'bolsa')")->execute(['c' => $codA]);
        $insumoA = (int)$this->db->lastInsertId();

        $this->db->prepare("INSERT INTO inventario_insumos (codigo, nombre, cantidad, unidad_medida) VALUES (:c, 'Arena Sílice', 20, 'kg')")->execute(['c' => $codB]);
        $insumoB = (int)$this->db->lastInsertId();

        // Crear OC con 10 bolsas de cemento y 20 kg de arena
        $ocId = $this->comprasModelo->guardarOrdenCompra([
            'proveedor_id' => $provId,
            'estado' => 'SOLICITADA'
        ], [
            ['insumo_id' => $insumoA, 'descripcion' => 'Cemento Tipo I', 'cantidad' => 10.0, 'costo_unitario' => 350.0, 'aplica_iva' => true],
            ['insumo_id' => $insumoB, 'descripcion' => 'Arena Sílice', 'cantidad' => 20.0, 'costo_unitario' => 50.0, 'aplica_iva' => false],
        ]);
        $this->comprasModelo->cambiarEstadoOrdenCompra($ocId, 'APROBADA', 1);

        $ocDet = $this->comprasModelo->obtenerOrdenCompraPorId($ocId);
        $lineaCemId = $ocDet['detalles'][0]['id'];
        $lineaAreId = $ocDet['detalles'][1]['id'];

        // 1. Recepción Parcial (5 bolsas de cemento y 10 kg de arena)
        $rec1Id = $this->comprasModelo->registrarRecepcionCompra($ocId, [
            'proveedor_id' => $provId,
            'numero_factura' => 'REM-PARCIAL-01',
            'fecha_recepcion' => date('Y-m-d'),
            'recibido_por' => 1,
            'observaciones' => 'Entrega parcial en bodega central'
        ], [
            [
                'orden_compra_detalle_id' => $lineaCemId,
                'insumo_id' => $insumoA,
                'cantidad_recibida' => 5.0,
                'costo_unitario' => 350.0,
                'lote' => 'LOT-CEM-01',
                'fecha_vencimiento' => date('Y-m-d', strtotime('+90 days'))
            ],
            [
                'orden_compra_detalle_id' => $lineaAreId,
                'insumo_id' => $insumoB,
                'cantidad_recibida' => 10.0,
                'costo_unitario' => 50.0,
                'lote' => 'LOT-ARE-01'
            ]
        ]);
        $this->assertGreaterThan(0, $rec1Id);

        // Verificar que la OC pase a RECIBIDA_PARCIAL
        $ocRec1 = $this->comprasModelo->obtenerOrdenCompraPorId($ocId);
        $this->assertSame('RECIBIDA_PARCIAL', strtoupper($ocRec1['estado']));
        $this->assertEquals(5.0, (float)$ocRec1['detalles'][0]['cantidad_recibida']);
        $this->assertEquals(10.0, (float)$ocRec1['detalles'][1]['cantidad_recibida']);

        // Verificar kárdex de inventario_movimientos
        $movs1 = $this->db->query("SELECT * FROM inventario_movimientos WHERE referencia_id = {$rec1Id} AND referencia_tipo = 'recepcion_compra'")->fetchAll(PDO::FETCH_ASSOC);
        $this->assertCount(2, $movs1);
        $this->assertSame('entrada_compra', $movs1[0]['tipo_movimiento']);

        // Verificar actualización de stock (10 + 5 = 15 bolsas de cemento; 20 + 10 = 30 kg arena)
        $stockCem = (float)$this->db->query("SELECT cantidad FROM inventario_insumos WHERE id = {$insumoA}")->fetchColumn();
        $this->assertEquals(15.0, $stockCem);

        // 2. Recepción Final (Restante: 5 bolsas cemento y 10 kg arena)
        $rec2Id = $this->comprasModelo->registrarRecepcionCompra($ocId, [
            'proveedor_id' => $provId,
            'numero_factura' => 'REM-FINAL-02',
            'fecha_recepcion' => date('Y-m-d'),
            'recibido_por' => 1,
            'observaciones' => 'Entrega final completada'
        ], [
            [
                'orden_compra_detalle_id' => $lineaCemId,
                'insumo_id' => $insumoA,
                'cantidad_recibida' => 5.0,
                'costo_unitario' => 350.0,
                'lote' => 'LOT-CEM-02'
            ],
            [
                'orden_compra_detalle_id' => $lineaAreId,
                'insumo_id' => $insumoB,
                'cantidad_recibida' => 10.0,
                'costo_unitario' => 50.0,
                'lote' => 'LOT-ARE-02'
            ]
        ]);
        $this->assertGreaterThan(0, $rec2Id);

        // Verificar que la OC pase automáticamente a RECIBIDA (completada al 100%)
        $ocRec2 = $this->comprasModelo->obtenerOrdenCompraPorId($ocId);
        $this->assertSame('RECIBIDA', strtoupper($ocRec2['estado']), "La orden debe marcarse como RECIBIDA al completarse todas sus líneas.");
        $this->assertEquals(10.0, (float)$ocRec2['detalles'][0]['cantidad_recibida']);
        $this->assertEquals(20.0, (float)$ocRec2['detalles'][1]['cantidad_recibida']);

        // Stock final: 15 + 5 = 20 cemento; 30 + 10 = 40 arena
        $stockFinalCem = (float)$this->db->query("SELECT cantidad FROM inventario_insumos WHERE id = {$insumoA}")->fetchColumn();
        $this->assertEquals(20.0, $stockFinalCem);
    }

    /**
     * TAREA-047: Inserción de CXP enlazada a proveedor_id y resolución de cuentas con ConfigCuentasModelo.
     */
    public function testInsercionCxpEnlazadaAProveedorIdYResolucionCuentas(): void {
        // Crear proveedor
        $razonSocial = 'Proveedor Concreto Premezclado ' . uniqid();
        $provId = $this->comprasModelo->guardarProveedor([
            'tipo_id' => 'RUC',
            'identificacion' => 'J03' . rand(10000000, 99999999),
            'razon_social' => $razonSocial,
            'evaluado' => 1,
            'estado' => 'activo'
        ]);

        // Crear OC y Recepción para vincular
        $ocId = $this->comprasModelo->guardarOrdenCompra([
            'proveedor_id' => $provId,
            'estado' => 'APROBADA'
        ], [
            ['insumo_id' => null, 'descripcion' => 'Aditivo acelerante', 'cantidad' => 2.0, 'costo_unitario' => 500.0, 'aplica_iva' => true]
        ]);

        $recId = $this->comprasModelo->registrarRecepcionCompra($ocId, [
            'proveedor_id' => $provId,
            'numero_factura' => 'F-ORIG-' . rand(1000, 9999)
        ], [
            ['orden_compra_detalle_id' => null, 'insumo_id' => 0, 'cantidad_recibida' => 2.0, 'costo_unitario' => 500.0]
        ]);

        $numFactura = 'FAC-TEST-' . uniqid();
        $monto = 1150.00;

        // Guardar CXP pasando proveedor_id sin proveedor_nombre para comprobar resolución automática
        $datosCxp = [
            'proveedor_id' => $provId,
            'proveedor_nombre' => '', // Nombre vacío para resolución automática
            'orden_compra_id' => $ocId,
            'recepcion_id' => $recId,
            'factura_numero' => $numFactura,
            'monto' => $monto,
            'fecha_emision' => date('Y-m-d'),
            'fecha_vencimiento' => date('Y-m-d', strtotime('+30 days')),
            'notas' => 'CXP enlazada a OC y Recepción de prueba'
        ];

        $resCxp = $this->contabilidadModelo->guardarCxp($datosCxp);
        $this->assertTrue($resCxp, "guardarCxp debe completarse con éxito.");

        // Verificar columnas en la tabla cuentas_por_pagar
        $stmtCxp = $this->db->prepare("SELECT * FROM cuentas_por_pagar WHERE factura_numero = :fn LIMIT 1");
        $stmtCxp->execute(['fn' => $numFactura]);
        $cxpGuardada = $stmtCxp->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($cxpGuardada, "El registro de CXP debe existir en la base de datos.");
        $this->assertSame($provId, (int)$cxpGuardada['proveedor_id'], "proveedor_id debe coincidir.");
        $this->assertSame($ocId, (int)$cxpGuardada['orden_compra_id'], "orden_compra_id debe coincidir.");
        $this->assertSame($recId, (int)$cxpGuardada['recepcion_id'], "recepcion_id debe coincidir.");
        $this->assertSame($razonSocial, $cxpGuardada['proveedor_nombre'], "proveedor_nombre debe haberse resuelto automáticamente desde proveedores.");

        // Verificar que el asiento contable se generó con la cuenta de PROVEEDORES de ConfigCuentasModelo
        $idCuentaProveedoresEsperada = ConfigCuentasModelo::obtenerIdPorClave('PROVEEDORES') ?? 121;
        $this->assertNotNull($idCuentaProveedoresEsperada);

        // Buscar el asiento registrado
        $asiento = $this->db->query("
            SELECT pd.id, pdd.id_cuenta_contable, pdd.haber 
            FROM partidas_diario pd
            JOIN partidas_diario_detalles pdd ON pd.id = pdd.id_partida
            WHERE pd.origen = 'CXP' AND pd.origen_id = {$cxpGuardada['id']} AND pdd.haber > 0
            LIMIT 1
        ")->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($asiento, "Debe existir un asiento contable en partida diaria para la CXP.");
        $this->assertSame($idCuentaProveedoresEsperada, (int)$asiento['id_cuenta_contable'], "El asiento contable de proveedores debe usar el ID dinámico de ConfigCuentasModelo.");
    }

    /**
     * TAREA-047: Verificación de sesión, CSRF y RBAC en ComprasControlador.
     */
    public function testControladorComprasSeguridadRbacYCsrf(): void {
        $controlador = new ComprasControlador($this->comprasModelo);

        $peticionMock = $this->createMock(Peticion::class);
        $peticionMock->method('esPost')->willReturn(true);
        $peticionMock->method('esAjax')->willReturn(true);
        $peticionMock->method('obtenerDatos')->willReturn([
            'csrf_token' => 'TOKEN_FALSO_INVALIDO',
            'identificacion' => 'J031234567890',
            'razon_social' => 'Proveedor No Permitido'
        ]);

        $respuesta = new Respuesta();

        // Probar que el controlador rechaza tokens CSRF incorrectos
        ob_start();
        $controlador->proveedoresGuardar($peticionMock, $respuesta);
        $salidaJson = ob_get_clean();

        $respuestaDecodificada = json_decode($salidaJson, true);
        $this->assertIsArray($respuestaDecodificada);
        $this->assertFalse($respuestaDecodificada['ok']);
        $this->assertStringContainsString('CSRF', $respuestaDecodificada['error']);
    }

    /**
     * TAREA-047: Flujo exitoso en ComprasControlador vía AJAX (Guardar Proveedor, Evaluar, Guardar OC, Aprobar OC, Recepcionar).
     */
    public function testControladorFlujoAjaxExitoso(): void {
        $controlador = new ComprasControlador($this->comprasModelo);
        $csrfValido = $_SESSION['csrf_token'];

        // 1. Guardar proveedor vía AJAX
        $reqProv = $this->createMock(Peticion::class);
        $reqProv->method('esPost')->willReturn(true);
        $reqProv->method('esAjax')->willReturn(true);
        $reqProv->method('obtenerDatos')->willReturn([
            'csrf_token' => $csrfValido,
            'identificacion' => 'J03' . rand(10000000, 99999999),
            'razon_social' => 'Proveedor AJAX Controller ' . uniqid(),
            'categoria' => 'insumos'
        ]);

        $res = new Respuesta();
        ob_start();
        $controlador->proveedoresGuardar($reqProv, $res);
        $outProv = ob_get_clean();
        $jsonProv = json_decode($outProv, true);

        $this->assertIsArray($jsonProv);
        $this->assertTrue($jsonProv['ok']);
        $this->assertGreaterThan(0, $jsonProv['id']);
        $provId = (int)$jsonProv['id'];

        // 2. Evaluar proveedor vía AJAX
        $reqEval = $this->createMock(Peticion::class);
        $reqEval->method('esPost')->willReturn(true);
        $reqEval->method('esAjax')->willReturn(true);
        $reqEval->method('obtenerDatos')->willReturn([
            'csrf_token' => $csrfValido,
            'id' => $provId,
            'evaluado' => '1',
            'fecha_evaluacion' => date('Y-m-d')
        ]);

        ob_start();
        $controlador->proveedoresEvaluar($reqEval, $res);
        $outEval = ob_get_clean();
        $jsonEval = json_decode($outEval, true);

        $this->assertIsArray($jsonEval);
        $this->assertTrue($jsonEval['ok']);
        $this->assertTrue($jsonEval['evaluado']);

        // 3. Guardar orden de compra vía AJAX
        $reqOc = $this->createMock(Peticion::class);
        $reqOc->method('esPost')->willReturn(true);
        $reqOc->method('esAjax')->willReturn(true);
        $reqOc->method('obtenerDatos')->willReturn([
            'csrf_token' => $csrfValido,
            'proveedor_id' => $provId,
            'detalles' => [
                [
                    'descripcion' => 'Ítem controlador de prueba',
                    'cantidad' => 4,
                    'costo_unitario' => 250,
                    'aplica_iva' => true
                ]
            ]
        ]);

        ob_start();
        $controlador->ordenesGuardar($reqOc, $res);
        $outOc = ob_get_clean();
        $jsonOc = json_decode($outOc, true);

        $this->assertIsArray($jsonOc);
        $this->assertTrue($jsonOc['ok']);
        $ocId = (int)$jsonOc['id'];
        $this->assertGreaterThan(0, $ocId);

        // 4. Aprobar orden vía AJAX
        $reqAprob = $this->createMock(Peticion::class);
        $reqAprob->method('esPost')->willReturn(true);
        $reqAprob->method('esAjax')->willReturn(true);
        $reqAprob->method('obtenerDatos')->willReturn([
            'csrf_token' => $csrfValido,
            'id' => $ocId
        ]);

        ob_start();
        $controlador->ordenesAprobar($reqAprob, $res);
        $outAprob = ob_get_clean();
        $jsonAprob = json_decode($outAprob, true);

        $this->assertIsArray($jsonAprob);
        $this->assertTrue($jsonAprob['ok']);
        $this->assertSame('APROBADA', $jsonAprob['estado']);

        // 5. Registrar recepción vía AJAX
        $reqRec = $this->createMock(Peticion::class);
        $reqRec->method('esPost')->willReturn(true);
        $reqRec->method('esAjax')->willReturn(true);
        $reqRec->method('obtenerDatos')->willReturn([
            'csrf_token' => $csrfValido,
            'orden_compra_id' => $ocId,
            'proveedor_id' => $provId,
            'numero_factura' => 'FAC-AJAX-01',
            'detalles' => [
                [
                    'insumo_id' => 0,
                    'cantidad_recibida' => 4,
                    'costo_unitario' => 250
                ]
            ]
        ]);

        ob_start();
        $controlador->recepcionesGuardar($reqRec, $res);
        $outRec = ob_get_clean();
        $jsonRec = json_decode($outRec, true);

        $this->assertIsArray($jsonRec);
        $this->assertTrue($jsonRec['ok']);
        $this->assertGreaterThan(0, $jsonRec['id']);
    }

    /**
     * TAREA-047: Validar métodos de consulta, catálogos e inexistencias en ComprasModelo.
     */
    public function testConsultasYFiltrosComprasModelo(): void {
        $this->assertNull($this->comprasModelo->obtenerProveedorPorId(99999999));
        $this->assertNull($this->comprasModelo->obtenerOrdenCompraPorId(99999999));
        $this->assertNull($this->comprasModelo->obtenerRecepcionPorId(99999999));

        $insumos = $this->comprasModelo->listarInsumosActivos();
        $this->assertIsArray($insumos);

        $cuentas = $this->comprasModelo->listarCuentasDetalle();
        $this->assertIsArray($cuentas);

        $ordenes = $this->comprasModelo->listarOrdenesCompra();
        $this->assertIsArray($ordenes);

        $recepciones = $this->comprasModelo->listarRecepciones();
        $this->assertIsArray($recepciones);
    }
}
