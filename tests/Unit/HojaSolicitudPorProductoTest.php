<?php



namespace Tests\Unit;



use PHPUnit\Framework\TestCase;

use Cycsa\Nucleo\Conexion;

use Cycsa\Nucleo\Peticion;

use Cycsa\Nucleo\Respuesta;

use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;

use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;

use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;

use PDO;



class TestRespuestaCaptura extends Respuesta {

    public string $urlRedirigida = '';

    public ?array $jsonRespuesta = null;

    public int $codigoRespuesta = 200;



    public function redirigir(string $url): void {

        $this->urlRedirigida = $url;

    }



    public function enviarJson(array $datos, int $codigoEstado = 200): void {

        $this->codigoRespuesta = $codigoEstado;

        $this->jsonRespuesta = $datos;

    }

}



class HojaSolicitudPorProductoTest extends TestCase {



    private PDO $db;

    private OperacionModelo $opModelo;

    private OrdenServicioModelo $osModelo;

    private HojasServicioControlador $controlador;



    private int $idCliente = 0;

    private int $idCotizacion = 0;

    private int $idOS = 0;

    private int $idDetalleConcreto = 0;

    private int $idDetalleSuelo = 0;

    private int $idDetalleCompactacion = 0;

    private int $idProdConcreto = 0;

    private int $idProdSuelo = 0;

    private int $idProdCompactacion = 0;



    private array $hojasCreadas = [];

    private array $secuenciasBackup = [];



    protected function setUp(): void {

        parent::setUp();

        $this->db = Conexion::obtenerInstancia();

        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);



        $this->opModelo = new OperacionModelo();

        $this->osModelo = new OrdenServicioModelo();

        $this->controlador = new HojasServicioControlador();



        if (session_status() === PHP_SESSION_NONE) {

            @session_start();

        }

        $_SESSION = [];

        $_POST = [];

        $_GET = [];

        $_SERVER['REQUEST_METHOD'] = 'POST';

        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';

        $_SERVER['SCRIPT_NAME'] = '/Cycsa/publico/index.php';



        // 0. Usuario creador

        $stmtUser = $this->db->query("SELECT id FROM usuarios LIMIT 1");

        $idUser = (int)$stmtUser->fetchColumn();

        if ($idUser <= 0) {

            $this->db->exec("INSERT INTO usuarios (id_rol, nombre, email, password, activo) VALUES (1, 'Admin RT13', 'admin_rt13@test.local', 'hash', 1)");

            $idUser = (int)$this->db->lastInsertId();

        }



        $_SESSION['usuario_id'] = $idUser;

        $_SESSION['usuario_rol'] = 1;

        $_SESSION['usuario_nombre'] = 'Supervisor Test AGY-RT13';

        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));



        // Backup secuencias

        try {

            $this->secuenciasBackup = $this->db->query("SELECT anio, tipo_muestra, ultimo_correlativo FROM secuencias_muestras")->fetchAll(PDO::FETCH_ASSOC);

        } catch (\Throwable $e) {

            $this->secuenciasBackup = [];

        }



        // 1. Cliente

        $stmtCli = $this->db->query("SELECT id FROM clientes LIMIT 1");

        $this->idCliente = (int)$stmtCli->fetchColumn();

        if ($this->idCliente <= 0) {

            $this->db->exec("INSERT INTO clientes (nombre_razon_social, numero_ruc, email, telefono) VALUES ('Cliente Unit Test RT13', 'J0310000000001', 'rt13@test.local', '22223333')");

            $this->idCliente = (int)$this->db->lastInsertId();

        }



        // 2. Productos: buscar o insertar Concreto, Suelo y Compactación

        // Concreto

        $stmtP1 = $this->db->query("SELECT id FROM productos WHERE nombre_comercial LIKE '%Concreto%' OR norma_astm LIKE '%C39%' LIMIT 1");

        $this->idProdConcreto = (int)$stmtP1->fetchColumn();

        if ($this->idProdConcreto <= 0) {

            $this->db->exec("INSERT INTO productos (codigo_servicio, nombre_comercial, norma_astm, formato_id, precio_base) VALUES ('CYCSA-RT-FM-22 A', 'Resistencia a Compresión de Cilindros de Concreto', 'ASTM C39/C39M', 21, 500)");

            $this->idProdConcreto = (int)$this->db->lastInsertId();

        }



        // Suelo

        $stmtP2 = $this->db->query("SELECT id FROM productos WHERE (nombre_comercial LIKE '%Granulo%' OR nombre_comercial LIKE '%Proctor%') AND id != {$this->idProdConcreto} LIMIT 1");

        $this->idProdSuelo = (int)$stmtP2->fetchColumn();

        if ($this->idProdSuelo <= 0) {

            $this->db->exec("INSERT INTO productos (codigo_servicio, nombre_comercial, norma_astm, formato_id, precio_base) VALUES ('CYCSA-RT-FM-22 F', 'Granulometría y Límites de Suelos', 'ASTM D6913 / ASTM D4318', 21, 750)");

            $this->idProdSuelo = (int)$this->db->lastInsertId();

        }



        // Compactación (Densímetro Nuclear o Cono de Arena)

        $stmtP3 = $this->db->query("SELECT id FROM productos WHERE (norma_astm LIKE '%D6938%' OR norma_astm LIKE '%D1556%' OR nombre_comercial LIKE '%Densidad%') AND id NOT IN ({$this->idProdConcreto}, {$this->idProdSuelo}) LIMIT 1");

        $this->idProdCompactacion = (int)$stmtP3->fetchColumn();

        if ($this->idProdCompactacion <= 0) {

            $this->db->exec("INSERT INTO productos (codigo_servicio, nombre_comercial, norma_astm, formato_id, precio_base) VALUES ('CYCSA-RT-FM-22 B', 'Densidad y Humedad In Situ por Densímetro Nuclear', 'ASTM D6938', 1, 1200)");

            $this->idProdCompactacion = (int)$this->db->lastInsertId();

        }



        // 3. Cotización con 3 ítems

        $codigoCot = 'COT-RT13-' . uniqid();

        $stmtCot = $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total) VALUES (:cod, :cli, :user, 'Ing. Residente', 'Proyecto Alpha RT13', 'Pago contra entrega', 'Aprobada por Cliente', 2450, 2817.50)");

        $stmtCot->execute(['cod' => $codigoCot, 'cli' => $this->idCliente, 'user' => $idUser]);

        $this->idCotizacion = (int)$this->db->lastInsertId();



        // Detalle 1: Concreto

        $stmtD1 = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal) VALUES (:cot, :prod, 'Concreto f\'c 280 kg/cm² (ASTM C39)', 'CYCSA-RT-FM-22 A', 'ASTM C39', 6, 500, 3000)");

        $stmtD1->execute(['cot' => $this->idCotizacion, 'prod' => $this->idProdConcreto]);

        $this->idDetalleConcreto = (int)$this->db->lastInsertId();



        // Detalle 2: Suelo

        $stmtD2 = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal) VALUES (:cot, :prod, 'Granulometría y Proctor Modificado de Suelos', 'CYCSA-RT-FM-22 F', 'ASTM D6913 / ASTM D1557', 2, 750, 1500)");

        $stmtD2->execute(['cot' => $this->idCotizacion, 'prod' => $this->idProdSuelo]);

        $this->idDetalleSuelo = (int)$this->db->lastInsertId();



        // Detalle 3: Compactación

        $stmtD3 = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal) VALUES (:cot, :prod, 'Densidad de Campo In Situ (ASTM D6938)', 'CYCSA-RT-FM-22 B', 'ASTM D6938', 10, 1200, 12000)");

        $stmtD3->execute(['cot' => $this->idCotizacion, 'prod' => $this->idProdCompactacion]);

        $this->idDetalleCompactacion = (int)$this->db->lastInsertId();



        // 4. Orden de Servicio Aprobada

        $codigoOS = 'OS-RT13-' . uniqid();

        $this->idOS = $this->osModelo->crear([

            'codigo_os' => $codigoOS,

            'id_cotizacion' => $this->idCotizacion,

            'id_cliente' => $this->idCliente,

            'id_cliente_factura' => $this->idCliente,

            'id_cliente_informe' => $this->idCliente,

            'atencion_a' => 'Ing. Residente',

            'nombre_proyecto' => 'Proyecto Alpha RT13',

            'forma_pago' => 'Pago contra entrega',

            'condicion_comercial' => 'contado',

            'tipo_contrato' => 'Puntual',

            'estado' => 'Aprobada'

        ]);

        $this->db->exec("UPDATE ordenes_servicio SET estado = 'Aprobada' WHERE id = {$this->idOS}");



        // Whitelist: Habilitar los 3 detalles

        $this->db->exec("INSERT IGNORE INTO orden_servicio_ensayos_permitidos (id_os, id_cotizacion_detalle) VALUES ({$this->idOS}, {$this->idDetalleConcreto}), ({$this->idOS}, {$this->idDetalleSuelo}), ({$this->idOS}, {$this->idDetalleCompactacion})");

    }



    protected function tearDown(): void {

        // Limpiar hojas creadas

        if (!empty($this->hojasCreadas)) {

            $inHojas = implode(',', array_map('intval', $this->hojasCreadas));

            try {

                $this->db->exec("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud IN ({$inHojas})");

                $this->db->exec("DELETE FROM hojas_solicitud WHERE id IN ({$inHojas})");

            } catch (\Throwable $e) {}

        }



        // Limpiar O/S y Cotización

        if ($this->idOS > 0) {

            try {

                $this->db->exec("DELETE FROM orden_servicio_ensayos_permitidos WHERE id_os = {$this->idOS}");

                $this->db->exec("DELETE FROM hojas_solicitud WHERE id_os = {$this->idOS}");

                $this->db->exec("DELETE FROM ordenes_servicio WHERE id = {$this->idOS}");

            } catch (\Throwable $e) {}

        }

        if ($this->idCotizacion > 0) {

            try {

                $this->db->exec("DELETE FROM cotizacion_detalles WHERE id_cotizacion = {$this->idCotizacion}");

                $this->db->exec("DELETE FROM cotizaciones WHERE id = {$this->idCotizacion}");

            } catch (\Throwable $e) {}

        }



        // Restaurar secuencias

        if (!empty($this->secuenciasBackup)) {

            try {

                $this->db->exec("DELETE FROM secuencias_muestras");

                $stmtInsert = $this->db->prepare("INSERT INTO secuencias_muestras (anio, tipo_muestra, ultimo_correlativo) VALUES (:anio, :tipo, :corr)");

                foreach ($this->secuenciasBackup as $row) {

                    $stmtInsert->execute(['anio' => $row['anio'], 'tipo' => $row['tipo_muestra'], 'corr' => $row['ultimo_correlativo']]);

                }

            } catch (\Throwable $e) {}

        }



        parent::tearDown();

    }



    /**

     * Prueba 1: Intento de guardar hoja RT-FM-13 sin id_detalle_cotizacion es rechazado

     * con el mensaje oficial exigido.

     */

    public function testIntentoGuardarHojaSinDetalleCotizacionEsRechazado(): void {

        $_POST = [

            'csrf_token' => $_SESSION['csrf_token'],

            'id_os' => $this->idOS,

            'id_hoja' => 0,

            'id_detalle_cotizacion' => 0, // VACÍO / <= 0

            'numero_registro' => 'REG-ERR-01',

            'nombre_empresa_o_cliente' => 'Cliente Test',

            'direccion_proyecto' => 'Managua, Nicaragua',

            'telefono' => '88887777',

            'correo_electronico' => 'test@cycsa.local',

            'nombre_persona_entrega_muestra' => 'Ing. Entrega',

            'procedencia_punto_muestreo' => 'Sitio Obra',

            'nombre_persona_toma_muestra' => 'Tecnico Muestreo',

            'fecha_hora_toma_muestra' => date('Y-m-d H:i'),

            'nombre_recibe_cycsa' => 'Supervisor Lab'

        ];



        $peticion = new Peticion();

        $respuesta = new TestRespuestaCaptura();



        $this->controlador->guardarHojaSolicitud($peticion, $respuesta);



        // Debe registrar error en sesión

        $this->assertNotEmpty($_SESSION['error'] ?? null);

        $this->assertEquals(

            'Debe seleccionar el producto o ensayo de la Orden de Servicio al que corresponde esta Hoja de Solicitud (CYCSA-RT-FM-13).',

            $_SESSION['error']

        );



        // Ninguna hoja debe haberse insertado

        $hojas = $this->opModelo->obtenerHojasSolicitudPorOS($this->idOS);

        $this->assertCount(0, $hojas);

    }



    /**

     * Prueba 2: Guardar hoja RT-FM-13 con id_detalle_cotizacion válido persiste

     * id_detalle_cotizacion, id_producto y ensayo_id, y los getters retornan datos del producto.

     */

    public function testGuardarHojaConDetalleCotizacionValidoPersisteCampos(): void {

        $_POST = [

            'csrf_token' => $_SESSION['csrf_token'],

            'id_os' => $this->idOS,

            'id_hoja' => 0,

            'id_detalle_cotizacion' => $this->idDetalleConcreto,

            'numero_registro' => 'REG-OK-CONCRETO',

            'nombre_empresa_o_cliente' => 'Cliente Concreto S.A.',

            'direccion_proyecto' => 'Proyecto Alpha',

            'telefono' => '88889999',

            'correo_electronico' => 'concreto@alpha.local',

            'nombre_persona_entrega_muestra' => 'Ing. Concreto',

            'procedencia_punto_muestreo' => 'Losa Nivel 2',

            'nombre_persona_toma_muestra' => 'Técnico Concreto',

            'fecha_hora_toma_muestra' => date('Y-m-d H:i'),

            'nombre_recibe_cycsa' => 'Receptor Lab',

            'm_nombre' => ['MC-0010-26', 'MC-0011-26'],

            'm_desc' => ['Cilindro 1 Losa', 'Cilindro 2 Losa'],

            'm_info' => ['7 días', '28 días']

        ];



        $peticion = new Peticion();

        $respuesta = new TestRespuestaCaptura();



        $this->controlador->guardarHojaSolicitud($peticion, $respuesta);



        $this->assertEmpty($_SESSION['error'] ?? null, 'No debe haber error al guardar con detalle válido.');

        $this->assertNotEmpty($_SESSION['exito'] ?? null, 'Debe establecerse mensaje de éxito.');



        $hojas = $this->opModelo->obtenerHojasSolicitudPorOS($this->idOS);

        $this->assertCount(1, $hojas);

        $hoja = $hojas[0];

        $this->hojasCreadas[] = (int)$hoja['id'];



        // Verificar persistencia de campos en hojas_solicitud

        $this->assertEquals($this->idDetalleConcreto, (int)$hoja['id_detalle_cotizacion']);

        $this->assertEquals($this->idProdConcreto, (int)$hoja['id_producto']);

        $this->assertEquals($this->idProdConcreto, (int)$hoja['ensayo_id']);



        // Verificar que obtenerHojaSolicitudPorId incluya información del producto

        $hojaPorId = $this->opModelo->obtenerHojaSolicitudPorId((int)$hoja['id']);

        $this->assertNotNull($hojaPorId);

        $this->assertEquals("Concreto f'c 280 kg/cm² (ASTM C39)", $hojaPorId['descripcion_ensayo']);

        $this->assertEquals('CYCSA-RT-FM-22 A', $hojaPorId['codigo_servicio']);

        $this->assertNotEmpty($hojaPorId['nombre_comercial']);

    }



    /**

     * Prueba 3: 1 Cotización con 2 productos distintos genera 2 hojas RT-FM-13

     * independientes vinculadas a sus respectivos productos.

     */

    public function testCotizacionConDosProductosDistintosGeneraDosHojasIndependientes(): void {

        // Hoja 1 para Concreto

        $datosHoja1 = [

            'id_os' => $this->idOS,

            'id_hoja' => 0,

            'id_detalle_cotizacion' => $this->idDetalleConcreto,

            'id_producto' => $this->idProdConcreto,

            'ensayo_id' => $this->idProdConcreto,

            'numero_registro' => 'REG-PROD-CONC',

            'nombre_empresa_o_cliente' => 'Cliente Multi-Producto',

            'direccion_proyecto' => 'Obra Km 12',

            'telefono' => '88880001',

            'correo_electronico' => 'obra@multi.local',

            'nombre_persona_entrega_muestra' => 'Ing. Concreto',

            'procedencia_punto_muestreo' => 'Vigas Principales',

            'nombre_persona_toma_muestra' => 'Técnico CYCSA',

            'fecha_hora_toma_muestra' => date('Y-m-d H:i'),

            'nombre_recibe_cycsa' => 'Receptor Lab',

            'identificacion_muestras_json' => json_encode([

                ['nombre_muestra' => 'MC-0020-26', 'descripcion' => 'Cilindro Viga 1', 'ensayo_id' => $this->idProdConcreto],

                ['nombre_muestra' => 'MC-0021-26', 'descripcion' => 'Cilindro Viga 2', 'ensayo_id' => $this->idProdConcreto]

            ])

        ];



        $idHoja1 = 0;

        $ok1 = $this->opModelo->guardarHojaSolicitud($datosHoja1, $idHoja1);

        $this->assertTrue($ok1, 'Hoja 1 debe guardarse exitosamente.');

        $this->hojasCreadas[] = $idHoja1;



        // Hoja 2 para Suelos

        $datosHoja2 = [

            'id_os' => $this->idOS,

            'id_hoja' => 0,

            'id_detalle_cotizacion' => $this->idDetalleSuelo,

            'id_producto' => $this->idProdSuelo,

            'ensayo_id' => $this->idProdSuelo,

            'numero_registro' => 'REG-PROD-SUELO',

            'nombre_empresa_o_cliente' => 'Cliente Multi-Producto',

            'direccion_proyecto' => 'Obra Km 12',

            'telefono' => '88880001',

            'correo_electronico' => 'obra@multi.local',

            'nombre_persona_entrega_muestra' => 'Ing. Geotecnia',

            'procedencia_punto_muestreo' => 'Calicata C-1 Prof 1.50m',

            'nombre_persona_toma_muestra' => 'Técnico CYCSA',

            'fecha_hora_toma_muestra' => date('Y-m-d H:i'),

            'nombre_recibe_cycsa' => 'Receptor Lab',

            'identificacion_muestras_json' => json_encode([

                ['nombre_muestra' => 'MC-0030-26', 'descripcion' => 'Muestra Suelo Calicata 1', 'ensayo_id' => $this->idProdSuelo]

            ])

        ];



        $idHoja2 = 0;

        $ok2 = $this->opModelo->guardarHojaSolicitud($datosHoja2, $idHoja2);

        $this->assertTrue($ok2, 'Hoja 2 debe guardarse exitosamente.');

        $this->hojasCreadas[] = $idHoja2;



        $this->assertNotEquals($idHoja1, $idHoja2, 'Las dos hojas deben tener IDs independientes.');



        // Obtener todas las hojas de la O/S

        $todas = $this->opModelo->obtenerHojasSolicitudPorOS($this->idOS);

        $this->assertCount(2, $todas);



        $h1 = $this->opModelo->obtenerHojaSolicitudPorId($idHoja1);

        $h2 = $this->opModelo->obtenerHojaSolicitudPorId($idHoja2);



        $this->assertEquals($this->idDetalleConcreto, (int)$h1['id_detalle_cotizacion']);

        $this->assertEquals($this->idProdConcreto, (int)$h1['id_producto']);

        $this->assertStringContainsString('Concreto', $h1['descripcion_ensayo']);



        $this->assertEquals($this->idDetalleSuelo, (int)$h2['id_detalle_cotizacion']);

        $this->assertEquals($this->idProdSuelo, (int)$h2['id_producto']);

        $this->assertStringContainsString('Granulometría', $h2['descripcion_ensayo']);



        // Verificar aislamiento de muestras

        $m1 = json_decode($h1['muestras_json'], true);

        $m2 = json_decode($h2['muestras_json'], true);

        $this->assertCount(2, $m1);

        $this->assertCount(1, $m2);

        $this->assertStringStartsWith('MC-', $m1[0]['nombre_muestra']);

        $this->assertStringStartsWith('MC-', $m2[0]['nombre_muestra']);
        $this->assertNotEquals($m1[0]['nombre_muestra'], $m2[0]['nombre_muestra']);

    }



    /**

     * Prueba 4: Prefill de flags y naturaleza según producto específico.

     * Concreto activa req_resistencia_concreto; Suelo activa req_granulometria/proctor.

     */

    public function testPrefillDeFlagsSegunProducto(): void {

        // 1. Probar detección para Concreto

        $itemConcreto = [

            'descripcion_ensayo' => 'Concreto f\'c 280 kg/cm² (ASTM C39)',

            'nombre_ensayo' => 'Resistencia de Concreto',

            'norma_astm' => 'ASTM C39',

            'codigo_servicio' => 'CYCSA-RT-FM-22 A'

        ];

        $resConcreto = $this->controlador->detectarParametrosYNaturaleza([$itemConcreto]);



        $this->assertEquals(1, $resConcreto['flags']['req_resistencia_concreto']);

        $this->assertEquals(0, $resConcreto['flags']['req_granulometria']);

        $this->assertEquals(0, $resConcreto['flags']['req_proctor_sm']);

        $this->assertContains('Concreto', $resConcreto['naturalezas']);



        // 2. Probar detección para Suelos / Proctor

        $itemSuelo = [

            'descripcion_ensayo' => 'Granulometría y Proctor Modificado de Suelos',

            'nombre_ensayo' => 'Ensayo de Suelo',

            'norma_astm' => 'ASTM D6913 / ASTM D1557',

            'codigo_servicio' => 'CYCSA-RT-FM-22 F'

        ];

        $resSuelo = $this->controlador->detectarParametrosYNaturaleza([$itemSuelo]);



        $this->assertEquals(0, $resSuelo['flags']['req_resistencia_concreto']);

        $this->assertEquals(1, $resSuelo['flags']['req_granulometria']);

        $this->assertEquals(1, $resSuelo['flags']['req_proctor_sm']);

        $this->assertContains('Suelo', $resSuelo['naturalezas']);



        // 3. Probar que al guardar una hoja de Concreto, el controlador sanea los flags desactivando los de suelo

        $_POST = [

            'csrf_token' => $_SESSION['csrf_token'],

            'id_os' => $this->idOS,

            'id_hoja' => 0,

            'id_detalle_cotizacion' => $this->idDetalleConcreto,

            'numero_registro' => 'REG-FLAGS-SAN',

            'nombre_empresa_o_cliente' => 'Cliente Flags',

            'direccion_proyecto' => 'Sitio Obra',

            'telefono' => '88880002',

            'correo_electronico' => 'flags@test.local',

            'nombre_persona_entrega_muestra' => 'Ing. Flags',

            'procedencia_punto_muestreo' => 'Sitio Obra',

            'nombre_persona_toma_muestra' => 'Técnico',

            'fecha_hora_toma_muestra' => date('Y-m-d H:i'),

            'nombre_recibe_cycsa' => 'Receptor',

            // Intentar colar flags de suelo en una hoja de concreto

            'req_granulometria' => 1,

            'req_proctor_sm' => 1,

            'm_nombre' => ['MC-0040-26']

        ];



        $peticion = new Peticion();

        $respuesta = new TestRespuestaCaptura();

        $this->controlador->guardarHojaSolicitud($peticion, $respuesta);



        $hojas = $this->opModelo->obtenerHojasSolicitudPorOS($this->idOS);

        $hojaGuardada = end($hojas);

        $this->hojasCreadas[] = (int)$hojaGuardada['id'];



        // ADDENDUM P0 (Abdia 2026-11-02): En RT-FM-13, parámetros solicitados (req_*) son libres.
        $this->assertEquals(1, (int)$hojaGuardada['req_resistencia_concreto'], 'Debe pre-llenar req_resistencia_concreto para concreto.');
        $this->assertEquals(1, (int)$hojaGuardada['req_granulometria'], 'Con ADDENDUM P0 debe persistir parámetro atípico de suelo marcado libremente.');
        $this->assertEquals(1, (int)$hojaGuardada['req_proctor_sm'], 'Con ADDENDUM P0 debe persistir proctor marcado libremente.');
    }



    /**

     * Prueba 5: Detección de compactación -> enrutamiento hacia Operaciones (/operaciones/ver?id_os=X).

     */

    public function testDeteccionCompactacionRedirigeAOperaciones(): void {

        // Verificar que el método esProductoCompactacion identifique correctamente

        $stmtDetComp = $this->db->prepare("SELECT cd.*, p.codigo_servicio, p.norma_astm, p.nombre_comercial, fe.archivo_markdown, fe.nombre AS formato_nombre 

                                            FROM cotizacion_detalles cd

                                            LEFT JOIN productos p ON cd.id_producto = p.id

                                            LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id

                                            WHERE cd.id = :id");

        $stmtDetComp->execute(['id' => $this->idDetalleCompactacion]);

        $detComp = $stmtDetComp->fetch(PDO::FETCH_ASSOC);



        $esComp = $this->controlador->esProductoCompactacion($detComp);

        $this->assertTrue($esComp, 'Debe detectar el ensayo como compactación.');



        $_POST = [

            'csrf_token' => $_SESSION['csrf_token'],

            'id_os' => $this->idOS,

            'id_hoja' => 0,

            'id_detalle_cotizacion' => $this->idDetalleCompactacion,

            'numero_registro' => 'REG-COMP-01',

            'nombre_empresa_o_cliente' => 'Cliente Compactación S.A.',

            'direccion_proyecto' => 'Tramo Carretero Km 45',

            'telefono' => '88880003',

            'correo_electronico' => 'compacta@test.local',

            'nombre_persona_entrega_muestra' => 'Ing. Suelos',

            'procedencia_punto_muestreo' => 'Subrasante Estación 10+200',

            'nombre_persona_toma_muestra' => 'Técnico Nuclear',

            'fecha_hora_toma_muestra' => date('Y-m-d H:i'),

            'nombre_recibe_cycsa' => 'Operador Lab',

            'm_nombre' => ['MC-0050-26'],

            'm_desc' => ['Punto 1 Subrasante']

        ];



        $peticion = new Peticion();

        $respuesta = new TestRespuestaCaptura();



        $this->controlador->guardarHojaSolicitud($peticion, $respuesta);



        $hojas = $this->opModelo->obtenerHojasSolicitudPorOS($this->idOS);

        $hojaGuardada = end($hojas);

        $this->hojasCreadas[] = (int)$hojaGuardada['id'];



        // Redirección hacia el módulo de Órdenes de Servicio
        $this->assertStringContainsString('/ordenes-servicio', $respuesta->urlRedirigida);

        // Mensaje de éxito estándar sin forzar texto de Operaciones LIMS
        $this->assertNotEmpty($_SESSION['exito'] ?? null);
        $this->assertStringNotContainsString('Operaciones LIMS', $_SESSION['exito']);
        $this->assertStringContainsString('Ver PDF Oficial', $_SESSION['exito']);

    }



    /**

     * Prueba 6: Endpoint AJAX /hojas-servicio/datos retorna productos_permitidos_os

     * con id_detalle_cotizacion, descripcion_ensayo, codigo_servicio y tiene_hoja.

     */

    public function testEndpointAjaxRetornaProductosPermitidosConEstadoDeHoja(): void {

        // Crear una hoja previa para Concreto

        $idHojaExistente = 0;

        $this->opModelo->guardarHojaSolicitud([

            'id_os' => $this->idOS,

            'id_hoja' => 0,

            'id_detalle_cotizacion' => $this->idDetalleConcreto,

            'id_producto' => $this->idProdConcreto,

            'ensayo_id' => $this->idProdConcreto,

            'numero_registro' => 'REG-AJAX-01',

            'nombre_empresa_o_cliente' => 'Cliente Ajax',

            'm_json' => json_encode([['nombre_muestra' => 'MC-0060-26']])

        ], $idHojaExistente);

        $this->hojasCreadas[] = $idHojaExistente;



        $_SERVER['REQUEST_METHOD'] = 'GET';

        $_GET = [

            'id_os' => $this->idOS,

            'nueva' => '1'

        ];



        $peticion = new Peticion();

        $respuesta = new TestRespuestaCaptura();



        // Ejecutar endpoint AJAX

        ob_start();

        $this->controlador->hojaSolicitudDatosAjax($peticion, $respuesta);

        $output = ob_get_clean();



        $data = json_decode($output, true);

        if (!$data && $respuesta->jsonRespuesta) {

            $data = $respuesta->jsonRespuesta;

        }



        $this->assertIsArray($data, 'Debe retornar un JSON válido.');

        $this->assertEquals('success', $data['status'] ?? '');



        // Debe incluir productos_permitidos_os

        $this->assertArrayHasKey('productos_permitidos_os', $data);

        $productos = $data['productos_permitidos_os'];

        $this->assertNotEmpty($productos);



        $prodConcreto = null;

        $prodSuelo = null;

        foreach ($productos as $p) {

            if ((int)$p['id_detalle_cotizacion'] === $this->idDetalleConcreto) {

                $prodConcreto = $p;

            }

            if ((int)$p['id_detalle_cotizacion'] === $this->idDetalleSuelo) {

                $prodSuelo = $p;

            }

        }



        $this->assertNotNull($prodConcreto, 'Debe incluir el producto concreto.');

        $this->assertNotNull($prodSuelo, 'Debe incluir el producto suelo.');



        // Concreto ya tiene hoja creada

        $this->assertTrue($prodConcreto['tiene_hoja']);

        $this->assertEquals($idHojaExistente, $prodConcreto['id_hoja_creada']);



        // Suelo NO tiene hoja creada aún

        $this->assertFalse($prodSuelo['tiene_hoja']);

        $this->assertNull($prodSuelo['id_hoja_creada']);



        $this->assertNotEmpty($prodConcreto['descripcion_ensayo']);

        $this->assertNotEmpty($prodConcreto['codigo_servicio']);

    }

}

