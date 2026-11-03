<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use PDO;

class TestRespuestaCapturaSprintH1 extends Respuesta {
    public ?string $urlRedirigida = null;
    public function redirigir(string $url): void {
        $this->urlRedirigida = $url;
    }
}

/**
 * Sprint H.1 / ADDENDUM P0: Whitelist Real y Parámetros Libres en RT-FM-13
 *
 * Cobertura de pruebas unitarias:
 * 1. O/S legacy sin whitelist (null) permite todas las categorías (retrocompatibilidad total).
 * 2. O/S con whitelist parcial detecta correctamente flags sugeridos según los ensayos aprobados.
 * 3. Método de saneamiento server-side (conservado para compatibilidad y análisis directo).
 * 4. Script JS en vista index.php define BLOQUEAR_REQ_POR_WHITELIST = false y mantiene el.disabled = false.
 * 5. Guardar hoja nueva con un parámetro atípico respecto a ensayos permitidos persiste en BD.
 */
class SprintH1ReqWhitelistTest extends TestCase {

    private PDO $db;
    private OrdenServicioModelo $osModelo;
    private HojasServicioControlador $hojaControlador;
    private array $limpiezaIds = [
        'id_os' => 0,
        'id_cotizacion' => 0,
        'id_cliente' => 0,
        'id_hoja' => 0
    ];

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->osModelo = new OrdenServicioModelo();
        $this->hojaControlador = new HojasServicioControlador();
    }

    protected function tearDown(): void {
        if (!empty($this->limpiezaIds['id_hoja'])) {
            try {
                $this->db->exec("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = {$this->limpiezaIds['id_hoja']}");
                $this->db->exec("DELETE FROM hojas_solicitud WHERE id = {$this->limpiezaIds['id_hoja']}");
            } catch (\Throwable $e) {}
        }
        if (!empty($this->limpiezaIds['id_os'])) {
            try {
                $this->db->exec("DELETE FROM orden_servicio_ensayos_permitidos WHERE id_os = {$this->limpiezaIds['id_os']}");
                $this->db->exec("DELETE FROM hojas_solicitud WHERE id_os = {$this->limpiezaIds['id_os']}");
                $this->db->exec("DELETE FROM ordenes_servicio WHERE id = {$this->limpiezaIds['id_os']}");
            } catch (\Throwable $e) {}
        }
        if (!empty($this->limpiezaIds['id_cotizacion'])) {
            try {
                $this->db->exec("DELETE FROM cotizacion_detalles WHERE id_cotizacion = {$this->limpiezaIds['id_cotizacion']}");
                $this->db->exec("DELETE FROM cotizaciones WHERE id = {$this->limpiezaIds['id_cotizacion']}");
            } catch (\Throwable $e) {}
        }
        if (!empty($this->limpiezaIds['id_cliente'])) {
            try {
                $this->db->exec("DELETE FROM clientes WHERE id = {$this->limpiezaIds['id_cliente']}");
            } catch (\Throwable $e) {}
        }
        parent::tearDown();
    }

    public function testLegacyOSSinWhitelistPermiteTodasLasCategorias(): void {
        // ID ficticio sin registros en orden_servicio_ensayos_permitidos
        $idOSLegacy = 999998;
        $idsPermitidos = $this->osModelo->obtenerIdsPermitidos($idOSLegacy);

        $this->assertNull($idsPermitidos, 'Una O/S sin registros en la tabla de whitelist debe devolver null');
        $this->assertTrue(
            $this->osModelo->esEnsayoPermitido($idOSLegacy, 123),
            'En O/S legacy, cualquier ensayo debe considerarse permitido (allow-all)'
        );
    }

    public function testOSConWhitelistParcialHabilitaSoloCategoriasPermitidas(): void {
        // Ensayos simulados: Solo Concreto (C39)
        $ensayosConcreto = [
            [
                'id_producto' => 1,
                'nombre_ensayo' => 'Compresión de Cilindros de Concreto',
                'descripcion_ensayo' => 'Ensayo a compresión según norma ASTM C39',
                'norma_astm' => 'ASTM C39',
                'procedimiento' => 'Rotura de especímenes cilíndricos'
            ]
        ];

        $res = $this->hojaControlador->detectarParametrosYNaturaleza($ensayosConcreto);
        $flags = $res['flags'];

        $this->assertEquals(1, $flags['req_resistencia_concreto'], 'Concreto debe estar activo');
        $this->assertEquals(0, $flags['req_granulometria'], 'Granulometría no debe estar activa');
        $this->assertEquals(0, $flags['req_humedad'], 'Humedad no debe estar activa');
        $this->assertEquals(0, $flags['req_proctor_sm'], 'Proctor no debe estar activo');

        // Ensayos simulados: Solo Suelo (Granulometría + Límites)
        $ensayosSuelo = [
            [
                'id_producto' => 2,
                'nombre_ensayo' => 'Granulometría de Suelos',
                'descripcion_ensayo' => 'Análisis granulométrico por tamizado ASTM C136 / D422',
                'norma_astm' => 'ASTM D422',
                'procedimiento' => 'Tamizado'
            ],
            [
                'id_producto' => 3,
                'nombre_ensayo' => 'Límites de Atterberg',
                'descripcion_ensayo' => 'Límite líquido y plástico ASTM D4318',
                'norma_astm' => 'ASTM D4318',
                'procedimiento' => 'Copa de Casagrande'
            ]
        ];

        $resSuelo = $this->hojaControlador->detectarParametrosYNaturaleza($ensayosSuelo);
        $flagsSuelo = $resSuelo['flags'];

        $this->assertEquals(0, $flagsSuelo['req_resistencia_concreto'], 'Concreto no debe estar activo para suelos');
        $this->assertEquals(1, $flagsSuelo['req_granulometria'], 'Granulometría debe estar activa');
        $this->assertEquals(1, $flagsSuelo['req_limites_atterberg'], 'Límites de Atterberg debe estar activo');
    }

    public function testSaneamientoServerSideDesactivaCategoriasNoPermitidasEnHojaNueva(): void {
        $flagsPermitidos = [
            'req_resistencia_concreto' => 1,
            'req_granulometria' => 0
        ];

        $datosEnviados = [
            'req_resistencia_concreto' => 1,
            'req_granulometria' => 1 // Intento de activar ensayo no autorizado por whitelist
        ];

        // Invocación directa de la lógica real extraída en HojasServicioControlador
        $saneados = $this->hojaControlador->sanitizarCamposReqSegunFlags($datosEnviados, $flagsPermitidos);

        $this->assertEquals(1, $saneados['req_resistencia_concreto'], 'El ensayo autorizado debe mantenerse en 1');
        $this->assertEquals(0, $saneados['req_granulometria'], 'El ensayo no autorizado debe ser saneado a 0 por el controlador');
    }

    public function testVistaContieneMapeoYLogicaCompletaDeDeshabilitacion(): void {
        $viewPath = dirname(__DIR__, 2) . '/app/Modulos/OrdenesServicio/Vistas/index.php';
        $content = file_get_contents($viewPath);

        $this->assertStringContainsString('function aplicarFiltroWhitelistReq', $content);
        $this->assertStringContainsString('mapaCheckboxes', $content, 'Debe mapear los checkboxes por categoría');
        $this->assertStringContainsString('flagsPermitidos', $content, 'Debe evaluar flagsPermitidos dinámicamente');
        // ADDENDUM P0 (Abdia 2026-11-02): En RT-FM-13 los parámetros son de libre selección
        $this->assertStringContainsString('BLOQUEAR_REQ_POR_WHITELIST = false', $content, 'Debe definir flag de desbloqueo');
        $this->assertStringContainsString('el.disabled = false', $content, 'Debe mantener habilitadas las casillas');
        $this->assertStringNotContainsString('el.disabled = !estaPermitido', $content, 'No debe deshabilitar casillas por whitelist');
    }

    public function testGuardarHojaNuevaConParametroAtipicoPersisteEnBD(): void {
        // 1. Cliente
        $stmtCli = $this->db->prepare("INSERT INTO clientes (nombre_razon_social, numero_ruc, email, telefono) VALUES ('Cliente H1 Atipico SA', 'J0310000000004', 'h1atipico@test.local', '88880004')");
        $stmtCli->execute();
        $idCliente = (int)$this->db->lastInsertId();
        $this->limpiezaIds['id_cliente'] = $idCliente;

        // 2. Producto Concreto
        $stmtP1 = $this->db->query("SELECT id FROM productos WHERE nombre_comercial LIKE '%Concreto%' OR norma_astm LIKE '%C39%' LIMIT 1");
        $idProd = (int)$stmtP1->fetchColumn();
        if ($idProd <= 0) {
            $this->db->exec("INSERT INTO productos (codigo_servicio, nombre_comercial, norma_astm, formato_id, precio_base) VALUES ('CYCSA-RT-FM-22 A', 'Resistencia a Compresión Concreto', 'ASTM C39', 21, 500)");
            $idProd = (int)$this->db->lastInsertId();
        }

        // 3. Cotización y Detalle
        $stmtCot = $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, condicion_pago, estado, subtotal, total) VALUES (:cod, :cli, 1, 'Ing. Residente', 'Proyecto H1', 'Pago contra entrega', 'Aprobada por Cliente', 1000, 1150)");
        $stmtCot->execute(['cod' => 'COT-H1-' . uniqid(), 'cli' => $idCliente]);
        $idCotizacion = (int)$this->db->lastInsertId();
        $this->limpiezaIds['id_cotizacion'] = $idCotizacion;

        $stmtDet = $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, cantidad, precio_unitario, subtotal) VALUES (:cot, :prod, 'Concreto f\'c 280 (ASTM C39)', 'CYCSA-RT-FM-22 A', 'ASTM C39', 5, 500, 2500)");
        $stmtDet->execute(['cot' => $idCotizacion, 'prod' => $idProd]);
        $idDetalle = (int)$this->db->lastInsertId();

        // 4. Orden de Servicio Aprobada
        $idOS = $this->osModelo->crear([
            'codigo_os' => 'OS-H1-' . uniqid(),
            'id_cotizacion' => $idCotizacion,
            'id_cliente' => $idCliente,
            'id_cliente_factura' => $idCliente,
            'id_cliente_informe' => $idCliente,
            'atencion_a' => 'Ing. Residente',
            'nombre_proyecto' => 'Proyecto H1',
            'forma_pago' => 'Pago contra entrega',
            'condicion_comercial' => 'contado',
            'tipo_contrato' => 'Puntual',
            'estado' => 'Aprobada'
        ]);
        $this->db->exec("UPDATE ordenes_servicio SET estado = 'Aprobada' WHERE id = {$idOS}");
        $this->limpiezaIds['id_os'] = $idOS;

        // Whitelist sólo para el detalle de concreto
        $this->db->exec("INSERT INTO orden_servicio_ensayos_permitidos (id_os, id_cotizacion_detalle) VALUES ({$idOS}, {$idDetalle})");

        // 5. Configurar sesión y enviar POST con parámetros atípicos (req_humedad y req_granulometria en O/S de concreto)
        if (session_status() !== PHP_SESSION_ACTIVE) {
            @session_start();
        }
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_nombre'] = 'Admin Test H1';
        $_SESSION['csrf_token'] = 'csrf_h1_test_' . uniqid();
        $_SESSION['usuario_permisos'] = [
            'hojas_servicio' => ['crear_editar' => 1, 'ver' => 1],
            'ordenes_servicio' => ['crear_editar' => 1, 'ver' => 1]
        ];

        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_os' => $idOS,
            'id_hoja' => 0,
            'id_detalle_cotizacion' => $idDetalle,
            'numero_registro' => 'REG-H1-ATIPICO-' . uniqid(),
            'nombre_empresa_o_cliente' => 'Cliente H1 Atipico SA',
            'direccion_proyecto' => 'Sitio Obra',
            'telefono' => '88880004',
            'correo_electronico' => 'h1atipico@test.local',
            'nombre_persona_entrega_muestra' => 'Ing. Entrega',
            'procedencia_punto_muestreo' => 'Sitio Obra',
            'nombre_persona_toma_muestra' => 'Técnico Toma',
            'fecha_hora_toma_muestra' => date('Y-m-d H:i'),
            'nombre_recibe_cycsa' => 'Receptor Test',
            // Selección libre de parámetros solicitados (ADDENDUM P0)
            'req_resistencia_concreto' => 1,
            'req_humedad' => 1,
            'req_granulometria' => 1,
            'm_nombre' => ['MC-0077-26']
        ];

        $peticion = new Peticion();
        $respuesta = new TestRespuestaCapturaSprintH1();

        $this->hojaControlador->guardarHojaSolicitud($peticion, $respuesta);

        // 6. Verificar persistencia en base de datos
        $stmtH = $this->db->prepare("SELECT * FROM hojas_solicitud WHERE id_os = :os ORDER BY id DESC LIMIT 1");
        $stmtH->execute(['os' => $idOS]);
        $hoja = $stmtH->fetch(PDO::FETCH_ASSOC);

        $this->assertNotEmpty($hoja, 'Debe haberse insertado la hoja de solicitud.');
        $this->limpiezaIds['id_hoja'] = (int)$hoja['id'];

        $this->assertEquals(1, (int)$hoja['req_resistencia_concreto'], 'req_resistencia_concreto debe persistir en BD');
        $this->assertEquals(1, (int)$hoja['req_humedad'], 'El parámetro atípico req_humedad debe persistir en BD con ADDENDUM P0');
        $this->assertEquals(1, (int)$hoja['req_granulometria'], 'El parámetro atípico req_granulometria debe persistir en BD con ADDENDUM P0');
    }
}
