<?php

namespace Tests\Unit;

use Cycsa\Modulos\Cotizaciones\Controladores\CotizacionesControlador;
use Cycsa\Modulos\Operaciones\Controladores\OperacionesControlador;
use Cycsa\Modulos\Operaciones\Servicios\ConfigCalculoLabServicio;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use PDO;
use PHPUnit\Framework\TestCase;

class TestRespuestaPropuestaFlujo extends Respuesta {
    public string $urlRedirigida = '';
    public ?array $jsonRespuesta = null;
    public int $codigoRespuesta = 200;

    public function redirigir(string $url): void {
        $this->urlRedirigida = $url;
    }

    public function enviarJson(array $datos, int $codigoHttp = 200): void {
        $this->jsonRespuesta = $datos;
        $this->codigoRespuesta = $codigoHttp;
    }
}

class PropuestaFlujoOperacionesLaboratorioTest extends TestCase {
    private PDO $db;
    private array $idsCleanup = [
        'roturas' => [],
        'plan_rotura_edades' => [],
        'planes_rotura' => [],
        'especimenes' => [],
        'recepcion_muestras' => [],
        'hojas' => [],
        'os' => [],
        'detalles' => [],
        'cotizaciones' => [],
    ];

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_nombre'] = 'Supervisor QA Propuesta';
        $_SESSION['csrf_token'] = 'csrf_propuesta_flujo_2026';
        unset($_SESSION['usuario_permisos'], $_SESSION['error'], $_SESSION['exito']);
        $_POST = [];
        $_GET = [];
    }

    protected function tearDown(): void {
        try {
            foreach ($this->idsCleanup['roturas'] as $rid) {
                $this->db->prepare("DELETE FROM roturas WHERE id = :id")->execute(['id' => $rid]);
            }
            foreach ($this->idsCleanup['plan_rotura_edades'] as $peid) {
                $this->db->prepare("DELETE FROM plan_rotura_edades WHERE id = :id")->execute(['id' => $peid]);
            }
            foreach ($this->idsCleanup['planes_rotura'] as $prid) {
                $this->db->prepare("DELETE FROM planes_rotura WHERE id = :id")->execute(['id' => $prid]);
            }
            foreach ($this->idsCleanup['especimenes'] as $espid) {
                $this->db->prepare("DELETE FROM especimenes WHERE id = :id")->execute(['id' => $espid]);
            }
            foreach ($this->idsCleanup['recepcion_muestras'] as $rmid) {
                $this->db->prepare("DELETE FROM recepcion_muestras WHERE id = :id")->execute(['id' => $rmid]);
            }
            foreach ($this->idsCleanup['hojas'] as $hid) {
                $this->db->prepare("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = :id")->execute(['id' => $hid]);
                $this->db->prepare("DELETE FROM hojas_solicitud WHERE id = :id")->execute(['id' => $hid]);
            }
            foreach ($this->idsCleanup['os'] as $oid) {
                $this->db->prepare("DELETE FROM ordenes_servicio WHERE id = :id")->execute(['id' => $oid]);
            }
            foreach ($this->idsCleanup['detalles'] as $did) {
                $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id = :id")->execute(['id' => $did]);
            }
            foreach ($this->idsCleanup['cotizaciones'] as $cid) {
                $this->db->prepare("DELETE FROM cotizaciones WHERE id = :id")->execute(['id' => $cid]);
            }
        } catch (\Throwable $e) {
        }
        unset($_SESSION['usuario_permisos'], $_SESSION['error'], $_SESSION['exito']);
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_rol'] = 1;
        $_POST = [];
        $_GET = [];
        parent::tearDown();
    }

    private function crearFixtureFlujoCompleto(array $filaInicial = []): array {
        $idCliente = (int)$this->db->query("SELECT id FROM clientes LIMIT 1")->fetchColumn();
        if ($idCliente <= 0) {
            $this->db->exec("INSERT INTO clientes (nombre_razon_social, email) VALUES ('Cliente Propuesta', 'propuesta@cycsa.test')");
            $idCliente = (int)$this->db->lastInsertId();
        }
        $idUsuario = (int)$this->db->query("SELECT id FROM usuarios LIMIT 1")->fetchColumn() ?: 1;
        $uid = uniqid();

        $this->db->prepare("INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, nombre_proyecto, estado, subtotal, impuesto, total) VALUES (:cod, :cli, :uid, 'Proyecto Propuesta Flujo', 'Aprobada por Cliente', 1000, 150, 1150)")
            ->execute(['cod' => 'COT-PF-' . $uid, 'cli' => $idCliente, 'uid' => $idUsuario]);
        $idCot = (int)$this->db->lastInsertId();
        $this->idsCleanup['cotizaciones'][] = $idCot;

        $filaBase = array_merge([
            'Código laboratorio' => 'MC-7701-26',
            'Nombre muestra' => 'Columna Eje B-3',
            'Reven. (in)' => '4',
            'Reven. (cm)' => '10',
            'Temp. (°C)' => '27.0',
            'Diseño de concreto (lb/in²)' => '4000',
            'Fecha de Fabricación' => '2026-11-01',
            'Fecha de Ensayo' => '2026-11-29',
            'Edad (Días)' => '28',
            'Área (in²)' => '',
            'Carga (lb)' => '',
        ], $filaInicial);

        $this->db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, descripcion_ensayo, norma_astm, cantidad, precio_unitario, subtotal, resultados_json) VALUES (:cot, 'Resistencia de Concreto', 'ASTM C39', 1, 500, 500, :json)")
            ->execute([
                'cot' => $idCot,
                'json' => json_encode([
                    'version_actual' => 1,
                    'filas' => [$filaBase],
                    'metadatos' => [
                        'cliente_nombre' => 'Cliente Propuesta',
                        'proyecto' => 'Proyecto Propuesta Flujo',
                    ],
                ], JSON_UNESCAPED_UNICODE),
            ]);
        $idDet = (int)$this->db->lastInsertId();
        $this->idsCleanup['detalles'][] = $idDet;

        $this->db->prepare("INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, id_cliente, nombre_proyecto, estado, fecha_emision) VALUES (:os, :cot, :cli, 'Proyecto Propuesta Flujo', 'En Proceso', CURRENT_DATE)")
            ->execute(['os' => 'OS-PF-' . $uid, 'cot' => $idCot, 'cli' => $idCliente]);
        $idOs = (int)$this->db->lastInsertId();
        $this->idsCleanup['os'][] = $idOs;

        $this->db->prepare("INSERT INTO hojas_solicitud (id_os, id_detalle_cotizacion, numero_registro, etapa_flujo, recepcion_completa, muestras_json) VALUES (:os, :det, :reg, 'recepcion_borrador', 0, :mj)")
            ->execute([
                'os' => $idOs,
                'det' => $idDet,
                'reg' => 'REG-PF-' . $uid,
                'mj' => json_encode([['nombre_muestra' => 'MC-7701-26', 'descripcion' => 'Columna Eje B-3']]),
            ]);
        $idHoja = (int)$this->db->lastInsertId();
        $this->idsCleanup['hojas'][] = $idHoja;

        return [
            'id_cot' => $idCot,
            'id_detalle' => $idDet,
            'id_os' => $idOs,
            'id_hoja' => $idHoja,
            'id_usuario' => $idUsuario,
        ];
    }

    /**
     * 1) Clasificación de columnas por origen (Tabla oficial CYCSA Recepción vs Técnico):
     *    - Recepción (Órdenes de Servicio): Nombre muestra, Reven. (in), Reven. (cm), Temp. (°C), Diseño, Fecha de Fabricación, Fecha de Ensayo, Edad (Días)
     *    - Técnico (Operaciones): Área (in²), Carga (lb), Peso Volumétrico (kg/m³), R. Compresión (lb/in²), R. Compresión (kg/cm²)
     */
    public function testClasificacionColumnasPorOrigen(): void {
        $this->assertSame('identidad', clasificarColumnaRecepcionOTecnico('Código laboratorio', 'resistencia_de_concreto.md'));
        foreach (['Nombre muestra', 'Reven. (in)', 'Reven. (cm)', 'Temp. (°C)', 'Diseño de concreto (lb/in²)', 'Fecha de Fabricación', 'Fecha de Ensayo', 'Edad (Días)'] as $colRec) {
            $this->assertSame('recepcion', clasificarColumnaRecepcionOTecnico($colRec, 'resistencia_de_concreto.md'), "La columna {$colRec} corresponde a Recepción (Órdenes de Servicio).");
        }

        // Columnas exclusivas del Técnico en Operaciones
        foreach (['Área (in²)', 'Carga (lb)', 'Peso Volumétrico (kg/m³)', 'R. Compresión (lb/in²)', 'R. Compresión (kg/cm²)'] as $colTec) {
            $origenTec = clasificarColumnaRecepcionOTecnico($colTec, 'resistencia_de_concreto.md');
            $this->assertSame('tecnico', $origenTec, "La columna {$colTec} corresponde exclusivamente al Técnico en Operaciones.");
        }
    }

    /**
     * 2) Ciclo completo de 6 etapas:
     *    recepcion_borrador -> recepcion_completa -> en_ejecucion -> tecnico_completo -> matriz_en_revision -> informe
     *    y retorno a en_ejecucion en devolverMatrizProducto().
     */
    public function testCicloCompletoSeisEtapasYDevolucionAEnEjecucion(): void {
        $fx = $this->crearFixtureFlujoCompleto([
            'Área (in²)' => '28.27',
            'Carga (lb)' => '113000',
            'R. Compresión (lb/in²)' => '4000',
            'R. Compresión (kg/cm²)' => '282',
        ]);

        // Etapa 1 inicial: recepcion_borrador
        $etapa1 = (string)$this->db->query("SELECT etapa_flujo FROM hojas_solicitud WHERE id = {$fx['id_hoja']}")->fetchColumn();
        $this->assertSame('recepcion_borrador', $etapa1);

        // Etapa 2: recepcion_completa
        $this->db->prepare("UPDATE hojas_solicitud SET recepcion_completa = 1, etapa_flujo = 'recepcion_completa', fecha_recepcion_completa = NOW() WHERE id = :id")
            ->execute(['id' => $fx['id_hoja']]);
        $etapa2 = (string)$this->db->query("SELECT etapa_flujo FROM hojas_solicitud WHERE id = {$fx['id_hoja']}")->fetchColumn();
        $this->assertSame('recepcion_completa', $etapa2);

        // Etapa 3: en_ejecucion
        $this->db->prepare("UPDATE hojas_solicitud SET etapa_flujo = 'en_ejecucion' WHERE id = :id")
            ->execute(['id' => $fx['id_hoja']]);
        $etapa3 = (string)$this->db->query("SELECT etapa_flujo FROM hojas_solicitud WHERE id = {$fx['id_hoja']}")->fetchColumn();
        $this->assertSame('en_ejecucion', $etapa3);

        // Etapa 4: tecnico_completo
        $this->db->prepare("UPDATE hojas_solicitud SET etapa_flujo = 'tecnico_completo' WHERE id = :id")
            ->execute(['id' => $fx['id_hoja']]);
        $etapa4 = (string)$this->db->query("SELECT etapa_flujo FROM hojas_solicitud WHERE id = {$fx['id_hoja']}")->fetchColumn();
        $this->assertSame('tecnico_completo', $etapa4);

        // Etapa 5: matriz_en_revision mediante enviarMatrizARevision()
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_detalle' => $fx['id_detalle'],
            'ajax' => 1,
        ];
        $ctrl = new OperacionesControlador();
        $resp5 = new TestRespuestaPropuestaFlujo();
        $ctrl->enviarMatrizARevision(new Peticion(), $resp5);

        $this->assertNotNull($resp5->jsonRespuesta);
        $this->assertTrue($resp5->jsonRespuesta['exito']);
        $etapa5 = (string)$this->db->query("SELECT etapa_flujo FROM hojas_solicitud WHERE id = {$fx['id_hoja']}")->fetchColumn();
        $this->assertSame('matriz_en_revision', $etapa5);

        // Devolución por supervisor -> regresa a 'en_ejecucion'
        $_SESSION['usuario_rol'] = 3;
        $_SESSION['usuario_nombre'] = 'Ing. Supervisor Calidad';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_detalle' => $fx['id_detalle'],
            'motivo_devolucion' => 'Verificar lectura del espécimen 1 en prensa.',
        ];
        $respDev = new TestRespuestaPropuestaFlujo();
        $ctrl->devolverMatrizProducto(new Peticion(), $respDev);

        $etapaDev = (string)$this->db->query("SELECT etapa_flujo FROM hojas_solicitud WHERE id = {$fx['id_hoja']}")->fetchColumn();
        $this->assertSame('en_ejecucion', $etapaDev);

        // Reenvío a revisión y Aprobación Única del Supervisor -> pasa a 'informe'
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_detalle' => $fx['id_detalle'],
            'ajax' => 1,
        ];
        $ctrl->enviarMatrizARevision(new Peticion(), new TestRespuestaPropuestaFlujo());

        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_detalle' => $fx['id_detalle'],
            'nota_aprobacion' => 'Aprobación única conforme.',
        ];
        $respApr = new TestRespuestaPropuestaFlujo();
        $ctrl->aprobarMatrizProducto(new Peticion(), $respApr);

        $etapa6 = (string)$this->db->query("SELECT etapa_flujo FROM hojas_solicitud WHERE id = {$fx['id_hoja']}")->fetchColumn();
        $this->assertSame('informe', $etapa6);
    }

    /**
     * 3) Cero área prellenada '14.137' / '28.274' / '12.566' cuando Carga (lb) está vacía.
     */
    public function testCeroAreaNominal14137CuandoCargaEstaVacia(): void {
        $datosMuestra = obtenerDatosMuestraDesdeMatriz([
            ['Código laboratorio' => 'MC-0200-26', 'Nombre muestra' => 'Cilindro 4x8'],
        ], 'MC-0200-26');
        $this->assertNull($datosMuestra['area']);

        $fx = $this->crearFixtureFlujoCompleto([
            'Área (in²)' => '14.137',
            'Carga (lb)' => '',
        ]);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_detalle' => $fx['id_detalle'],
            'resultados_json' => json_encode([
                'filas' => [[
                    'Código laboratorio' => 'MC-7701-26',
                    'Nombre muestra' => 'Columna Eje B-3',
                    'Área (in²)' => '14.137',
                    'Carga (lb)' => '',
                ]],
            ], JSON_UNESCAPED_UNICODE),
        ];

        $ctrl = new OperacionesControlador();
        $resp = new TestRespuestaPropuestaFlujo();
        $ctrl->guardarMatrizProductoPOST(new Peticion(), $resp);

        $raw = (string)$this->db->query("SELECT resultados_json FROM cotizacion_detalles WHERE id = {$fx['id_detalle']}")->fetchColumn();
        $dec = json_decode($raw, true);
        $this->assertSame('', (string)($dec['filas'][0]['Área (in²)'] ?? ''), 'No debe persistir 14.137 cuando Carga (lb) está vacía');
    }

    /**
     * 4) Protección servidor en guardarMatrizProductoPOST:
     *    - Recalcula Reven. (cm) desde Reven. (in) ignorando valor falsificado
     *    - Re-aplica valores autoritativos de roturas del laboratorio para que no puedan ser sobrescritos desde la matriz
     */
    public function testProteccionServidorColumnasCalculadasYRoturasLaboratorio(): void {
        $fx = $this->crearFixtureFlujoCompleto();

        // Insertar recepcion_muestras, especimenes y rotura real en laboratorio para esta hoja
        $this->db->prepare("INSERT INTO recepcion_muestras (id_os, id_hoja, codigo_muestra, codigo_campo, fecha_recepcion, recibido_por, estado) VALUES (:id_os, :id_hoja, 'MC-7701-26', 'MC-7701-26', NOW(), :uid, 'En Laboratorio')")
            ->execute(['id_os' => $fx['id_os'], 'id_hoja' => $fx['id_hoja'], 'uid' => $fx['id_usuario']]);
        $idRm = (int)$this->db->lastInsertId();
        $this->idsCleanup['recepcion_muestras'][] = $idRm;

        $this->db->prepare("INSERT INTO especimenes (muestra_id, codigo_lab, tipo, dimensiones, fecha_moldeo, estado) VALUES (:mid, 'MC-7701-26-C1', 'cilindro_concreto', '15 x 30 cm', '2026-11-01', 'ensayado')")
            ->execute(['mid' => $idRm]);
        $idEsp = (int)$this->db->lastInsertId();
        $this->idsCleanup['especimenes'][] = $idEsp;

        $this->db->prepare("INSERT INTO planes_rotura (muestra_id, estado, creado_por) VALUES (:mid, 'activo', :uid)")
            ->execute(['mid' => $idRm, 'uid' => $fx['id_usuario']]);
        $idPlan = (int)$this->db->lastInsertId();
        $this->idsCleanup['planes_rotura'][] = $idPlan;

        $this->db->prepare("INSERT INTO plan_rotura_edades (plan_rotura_id, edad_dias, cantidad, fecha_programada, tolerancia_horas, estado) VALUES (:pid, 28, 1, '2026-11-29', 20, 'completado')")
            ->execute(['pid' => $idPlan]);
        $idPlanEdad = (int)$this->db->lastInsertId();
        $this->idsCleanup['plan_rotura_edades'][] = $idPlanEdad;

        $this->db->prepare("INSERT INTO roturas (especimen_id, plan_edad_id, fecha_real, edad_calculada, carga, area, peso_volumetrico, esfuerzo_psi, esfuerzo_kgcm2, porcentaje_fc, tecnico_id) VALUES (:esp, :peid, '2026-11-29', 28, 113097, 28.27, 2410, 4000, 282, 100, :uid)")
            ->execute(['esp' => $idEsp, 'peid' => $idPlanEdad, 'uid' => $fx['id_usuario']]);
        $idRot = (int)$this->db->lastInsertId();
        $this->idsCleanup['roturas'][] = $idRot;

        // Intentar sobrescribir Reven. (cm), Carga (lb), Área (in²) y R. Compresión desde guardarMatrizProductoPOST
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_detalle' => $fx['id_detalle'],
            'enviar_a_revision' => '1',
            'resultados_json' => json_encode([
                'filas' => [[
                    'Código laboratorio' => 'MC-7701-26',
                    'Nombre muestra' => 'Columna Eje B-3',
                    'Reven. (in)' => '4.5',
                    'Reven. (cm)' => '999',
                    'Área (in²)' => '10.00',
                    'Carga (lb)' => '5000',
                    'Peso Volumétrico (kg/m³)' => '1500',
                    'R. Compresión (lb/in²)' => '500',
                    'R. Compresión (kg/cm²)' => '35',
                    'Fecha de Fabricación' => '2026-11-01',
                    'Fecha de Ensayo' => '2026-11-05',
                    'Edad (Días)' => '4',
                ]],
            ], JSON_UNESCAPED_UNICODE),
        ];

        $ctrl = new OperacionesControlador();
        $resp = new TestRespuestaPropuestaFlujo();
        $ctrl->guardarMatrizProductoPOST(new Peticion(), $resp);

        $raw = (string)$this->db->query("SELECT resultados_json FROM cotizacion_detalles WHERE id = {$fx['id_detalle']}")->fetchColumn();
        $dec = json_decode($raw, true);
        $fila = $dec['filas'][0];

        // Reven. (cm) recalculado de 4.5 in -> 11.5 cm
        $this->assertSame('11.5', (string)$fila['Reven. (cm)']);
        // Valores autoritativos de roturas prevalecen sobre el intento de manipulación
        $this->assertSame('113097', (string)$fila['Carga (lb)']);
        $this->assertSame('28.27', (string)$fila['Área (in²)']);
        $this->assertSame('2410', (string)$fila['Peso Volumétrico (kg/m³)']);
        $this->assertSame('4000', (string)$fila['R. Compresión (lb/in²)']);
        $this->assertSame('282', (string)$fila['R. Compresión (kg/cm²)']);
        $this->assertSame('2026-11-29', (string)$fila['Fecha de Ensayo']);
        $this->assertSame('28', (string)$fila['Edad (Días)']);

        $etapa = (string)$this->db->query("SELECT etapa_flujo FROM hojas_solicitud WHERE id = {$fx['id_hoja']}")->fetchColumn();
        $this->assertSame('matriz_en_revision', $etapa);
    }

    /**
     * 5) Blindaje contra suplantación de firmas en aprobarMatrizProducto() y anti-autoaprobación en CotizacionesControlador::puedeAprobarInternamente()
     */
    public function testBlindajeFirmasSupervisorYAntiAutoaprobacionCotizaciones(): void {
        $fx = $this->crearFixtureFlujoCompleto([
            'Área (in²)' => '28.27',
            'Carga (lb)' => '112000',
            'R. Compresión (lb/in²)' => '3960',
        ]);

        $_SESSION['usuario_rol'] = 3;
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_nombre'] = 'Ing. Supervisor Real';
        $_SERVER['REQUEST_METHOD'] = 'POST';

        // Enviar POST intentando falsificar usuario_revisor y usuario_aprobador
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_detalle' => $fx['id_detalle'],
            'usuario_revisor' => 'FALSIFICADO POR POST',
            'usuario_aprobador' => 'APROBADOR FALSIFICADO',
            'nota_aprobacion' => 'Aprobado tras verificación.',
        ];

        $ctrl = new OperacionesControlador();
        $resp = new TestRespuestaPropuestaFlujo();
        $ctrl->aprobarMatrizProducto(new Peticion(), $resp);

        $raw = (string)$this->db->query("SELECT resultados_json FROM cotizacion_detalles WHERE id = {$fx['id_detalle']}")->fetchColumn();
        $dec = json_decode($raw, true);

        $this->assertSame('aprobada', $dec['revision']['estado']);
        $this->assertNotSame('FALSIFICADO POR POST', $dec['revision']['usuario_revisor']);
        $this->assertNotSame('APROBADOR FALSIFICADO', $dec['revision']['usuario_aprobador']);
        $this->assertSame('Ing. Supervisor Real', $dec['revision']['usuario_revisor']);
        $this->assertSame('Ing. Supervisor Real', $dec['revision']['usuario_aprobador']);

        // Anti-autoaprobación de cotizaciones: un vendedor (rol 4, id 42) creador de la cotización NO puede autoaprobarla
        ConfigCalculoLabServicio::guardar('cotizacion_responsable_interno_id', '');
        $_SESSION['usuario_permisos'] = ['cotizaciones' => ['aprobar' => 1, 'ver' => 1]];
        $cotCreadaPorVendedor = [
            'id' => 999,
            'id_usuario_creador' => 42,
            'id_responsable_interno' => null,
        ];
        $this->assertFalse(
            CotizacionesControlador::puedeAprobarInternamente($cotCreadaPorVendedor, 42, 4),
            'El creador no-admin de una cotización no debe poder autoaprobar su propia cotización'
        );
        // Un supervisor/gerente distinto (id 15, rol 3) con permiso sí puede aprobarla
        $this->assertTrue(
            CotizacionesControlador::puedeAprobarInternamente($cotCreadaPorVendedor, 15, 3),
            'Otro usuario con permiso de aprobación sí puede aprobar la cotización'
        );
        // Admin (rol 1) siempre puede aprobar
        $this->assertTrue(
            CotizacionesControlador::puedeAprobarInternamente($cotCreadaPorVendedor, 42, 1),
            'Admin (rol 1) sí puede aprobar'
        );
    }

    /**
     * 6) Verificación de permisos en capturaMatrizProducto / guardarMatrizProductoPOST
     *    y marcadores de Bandejas por Rol, Botón "Siguiente paso", Barra de 3 Secciones y Solo Lectura en vistas.
     */
    public function testPermisosMatrizYVistasBandejasPorRolSiguientePasoYSoloLectura(): void {
        $fx = $this->crearFixtureFlujoCompleto();

        // Usuario sin permisos de operaciones, laboratorio ni recepción es redirigido a /panel
        $_SESSION['usuario_rol'] = 9;
        $_SESSION['usuario_permisos'] = [];
        $_GET['id_detalle'] = $fx['id_detalle'];

        $ctrl = new OperacionesControlador();
        $respVer = new TestRespuestaPropuestaFlujo();
        $ctrl->capturaMatrizProducto(new Peticion(), $respVer);
        $this->assertSame('/Cycsa/publico/panel', $respVer->urlRedirigida);

        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_POST = [
            'csrf_token' => $_SESSION['csrf_token'],
            'id_detalle' => $fx['id_detalle'],
            'resultados_json' => '[]',
        ];
        $respGuardar = new TestRespuestaPropuestaFlujo();
        $ctrl->guardarMatrizProductoPOST(new Peticion(), $respGuardar);
        $this->assertSame('/Cycsa/publico/panel', $respGuardar->urlRedirigida);

        // Verificar contenido de vistas
        $root = dirname(__DIR__, 2);
        $htmlIndex = file_get_contents($root . '/app/Modulos/Operaciones/Vistas/index.php');
        $htmlCaptura = file_get_contents($root . '/app/Modulos/Operaciones/Vistas/captura_matriz.php');
        $htmlVer = file_get_contents($root . '/app/Modulos/Operaciones/Vistas/ver.php');

        // Bandejas por rol y 5 pasos en index.php
        $this->assertStringContainsString('① Bandeja de Recepción', $htmlIndex);
        $this->assertStringContainsString('② Bandeja del Técnico', $htmlIndex);
        $this->assertStringContainsString('③ Bandeja del Supervisor', $htmlIndex);
        $this->assertStringContainsString('④ Bandeja de Informes', $htmlIndex);
        $this->assertStringContainsString('1. RT-FM-13', $htmlIndex);
        $this->assertStringContainsString('2. Recepción Completa', $htmlIndex);
        $this->assertStringContainsString('3. Ejecución Lab', $htmlIndex);
        $this->assertStringContainsString('4. Revisión Supervisor', $htmlIndex);
        $this->assertStringContainsString('5. Informe PDF', $htmlIndex);
        $this->assertStringContainsString('Siguiente paso:', $htmlIndex);

        // Captura técnica integrada directamente en captura_matriz.php (sin barra de salto a Laboratorio)
        $this->assertStringContainsString('id="seccion-captura-tecnica-lab"', $htmlCaptura);
        $this->assertStringContainsString('id="tabla-captura-tecnica-lab"', $htmlCaptura);
        $this->assertStringContainsString('Calculado · Solo lectura', $htmlCaptura);
        $this->assertStringContainsString('Enviar a Revisión del Supervisor', $htmlCaptura);
        $this->assertStringContainsString('Plegar columnas de recepción', $htmlCaptura);
        $this->assertStringNotContainsString('Ir a Captura en Línea del Lote (Laboratorio)', $htmlCaptura);
        $this->assertStringNotContainsString('② Ejecución Técnica (Laboratorio)', $htmlCaptura);
        $this->assertStringNotContainsString('② Ejecución Técnica (Laboratorio)', $htmlVer);
    }
}
