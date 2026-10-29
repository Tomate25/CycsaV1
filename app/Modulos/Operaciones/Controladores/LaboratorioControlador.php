<?php

namespace Cycsa\Modulos\Operaciones\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\Operaciones\Modelos\RoturasModelo;
use Cycsa\Nucleo\Conexion;
use PDO;

class LaboratorioControlador extends ControladorBase {

    private function verificarSesion(Respuesta $respuesta): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['usuario_id']) || (int)$_SESSION['usuario_id'] <= 0) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
    }

    private function obtenerUsuarioIdAutenticado(Peticion $peticion, Respuesta $respuesta): ?int {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $usuarioId = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
        if ($usuarioId <= 0) {
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Sesión no autenticada o expirada.'], 401);
            } else {
                $respuesta->redirigir('/Cycsa/publico/login');
            }
            return null;
        }
        return $usuarioId;
    }

    private function verificarTokenCsrf(Peticion $peticion, Respuesta $respuesta, array $datos = [], string $redirectUrl = '/Cycsa/publico/laboratorio'): bool {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $tokenSesion = (string)($_SESSION['csrf_token'] ?? '');
        $tokenRecibido = (string)($datos['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));

        if ($tokenSesion === '' || $tokenRecibido === '' || !hash_equals($tokenSesion, $tokenRecibido)) {
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido o sesión expirada.'], 403);
            } else {
                $_SESSION['error'] = 'Token CSRF inválido o sesión expirada.';
                $respuesta->redirigir($redirectUrl);
            }
            return false;
        }
        return true;
    }

    private function verificarPermiso(Respuesta $respuesta, string $accion = 'ver'): void {
        if (!tienePermiso('laboratorio', $accion)) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
    }

    /**
     * Bandeja Anónima de Laboratorio (ISO/IEC 17025 Cláusulas 4.1 y 4.2).
     * Muestra las muestras aprobadas identificadas ÚNICAMENTE por código técnico.
     */
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $roturasModelo = new RoturasModelo();
        $operacionModelo = new OperacionModelo();
        $db = Conexion::obtenerInstancia();

        $tabActiva = $_GET['tab'] ?? 'aprobacion';
        if (!in_array($tabActiva, ['aprobacion', 'bandeja', 'programadas'])) {
            $tabActiva = 'aprobacion';
        }

        // 1. Obtener solicitudes de servicio y hojas RT-FM-13 para el apartado de Aprobación/Recepción (ISO 17025)
        $sqlSolicitudes = "SELECT os.id AS id_os, os.codigo_os, os.fecha_emision, os.estado AS estado_os,
                                  os.requiere_muestreo, os.tipo_contrato, os.motivo_observacion,
                                  hs.id AS id_hoja, hs.codigo_documento AS hoja_codigo, hs.numero_registro,
                                  hs.fecha_hora_toma_muestra, hs.muestras_json, hs.naturaleza_muestra,
                                  hs.procedencia_punto_muestreo, hs.observaciones, hs.fecha_creacion AS hoja_fecha_creacion,
                                  cot.id AS id_cotizacion,
                                  (CASE 
                                      WHEN hs.id IS NOT NULL THEN (SELECT COUNT(*) FROM recepcion_muestras rm WHERE rm.id_os = os.id AND (rm.id_hoja = hs.id OR (rm.id_hoja IS NULL AND hs.id = (SELECT MIN(hs_sub.id) FROM hojas_solicitud hs_sub WHERE hs_sub.id_os = os.id))))
                                      ELSE (SELECT COUNT(*) FROM recepcion_muestras rm WHERE rm.id_os = os.id)
                                   END) AS total_recibidas_lab
                           FROM ordenes_servicio os
                           LEFT JOIN hojas_solicitud hs ON hs.id_os = os.id
                           LEFT JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                           WHERE os.estado NOT IN ('Cancelada')
                           ORDER BY os.id DESC, hs.id DESC";
        $stmtSol = $db->query($sqlSolicitudes);
        $todasOrdenes = $stmtSol->fetchAll(PDO::FETCH_ASSOC);

        $solicitudesPorAprobar = [];
        $solicitudesAprobadasRecientes = [];

        foreach ($todasOrdenes as $o) {
            $ensayos = $operacionModelo->filtrarDetallesPermitidos((int)$o['id_os'], $operacionModelo->obtenerDetallesCotizacion((int)$o['id_cotizacion']));
            // Omitir si la orden contiene exclusivamente ensayos in situ de compactación (no ingresan a laboratorio)
            if (!empty($ensayos) && esOrdenSoloCompactacion($ensayos)) {
                continue;
            }

            $o['ensayos'] = $ensayos;
            $o['muestras_declaradas'] = !empty($o['muestras_json']) ? (json_decode($o['muestras_json'], true) ?: []) : [];
            $o['total_muestras_declaradas'] = count($o['muestras_declaradas']);

            $recibidas = (int)$o['total_recibidas_lab'];
            $estado = $o['estado_os'];

            if ($recibidas === 0 && !in_array($estado, ['Finalizado', 'Cancelada'], true)) {
                $solicitudesPorAprobar[] = $o;
            } elseif ($recibidas > 0) {
                if (count($solicitudesAprobadasRecientes) < 15) {
                    $solicitudesAprobadasRecientes[] = $o;
                }
            }
        }

        // Filtro de búsqueda en la pestaña de aprobación
        $filtroAprobacion = trim($_GET['buscar_solicitud'] ?? '');
        $estadoAprobacion = trim($_GET['estado_aprobacion'] ?? '');
        if ($filtroAprobacion !== '' || $estadoAprobacion !== '') {
            $solicitudesPorAprobar = array_values(array_filter($solicitudesPorAprobar, function($item) use ($filtroAprobacion, $estadoAprobacion) {
                if ($estadoAprobacion !== '') {
                    if ($estadoAprobacion === 'recibidas' && $item['estado_os'] !== 'Estado 1: Recepcion') return false;
                    if ($estadoAprobacion === 'revision' && $item['estado_os'] !== 'Estado 2: Revision') return false;
                    if ($estadoAprobacion === 'observadas' && $item['estado_os'] !== 'Estado 2: Observada') return false;
                }
                if ($filtroAprobacion !== '') {
                    $haystack = mb_strtolower(($item['hoja_codigo'] ?? '') . ' ' . ($item['codigo_os'] ?? '') . ' ' . ($item['naturaleza_muestra'] ?? '') . ' ' . ($item['ensayos'][0]['descripcion_ensayo'] ?? ''));
                    if (!str_contains($haystack, mb_strtolower($filtroAprobacion))) {
                        return false;
                    }
                }
                return true;
            }));
        }

        // Filtros capturados por GET para la bandeja anónima
        $filtros = [
            'busqueda' => trim($_GET['busqueda'] ?? ''),
            'estado' => trim($_GET['estado'] ?? ''),
            'tipo_ensayo' => trim($_GET['tipo_ensayo'] ?? ''),
            'rotura_hoy' => !empty($_GET['rotura_hoy']),
            'vencidas' => !empty($_GET['vencidas']),
            'fecha_desde' => trim($_GET['fecha_desde'] ?? ''),
            'fecha_hasta' => trim($_GET['fecha_hasta'] ?? '')
        ];

        // Obtener listado de la vista SQL anónima v_lab_muestras
        $muestras = $roturasModelo->obtenerBandejaAnonima($filtros);

        // Métricas rápidas para KPIs superiores de la bandeja anónima
        $todas = $roturasModelo->obtenerBandejaAnonima([]);
        $kpis = [
            'total' => count($todas),
            'rotura_hoy' => 0,
            'vencidas' => 0,
            'activas' => 0,
            'cerradas' => 0
        ];
        $hoy = date('Y-m-d');
        foreach ($todas as $t) {
            if (!empty($t['proxima_rotura'])) {
                if ($t['proxima_rotura'] === $hoy) {
                    $kpis['rotura_hoy']++;
                } elseif ($t['proxima_rotura'] < $hoy && $t['estado_plan'] !== 'cerrado') {
                    $kpis['vencidas']++;
                }
            }
            if ($t['estado_plan'] === 'cerrado') {
                $kpis['cerradas']++;
            } else {
                $kpis['activas']++;
            }
        }

        // Rupturas programadas en los próximos 7 días (consulta técnica ciega)
        $sqlProx = "SELECT esp.id AS especimen_id, esp.codigo_lab, esp.dimensiones, esp.estado AS especimen_estado,
                           pre.id AS plan_edad_id, pre.edad_dias, pre.fecha_programada, pre.tolerancia_horas,
                           vm.muestra_id, vm.material_descripcion, vm.diseno_resistencia, vm.plantilla_nombre
                    FROM plan_rotura_edades pre
                    JOIN plan_rotura_asignaciones pra ON pra.plan_edad_id = pre.id
                    JOIN especimenes esp ON pra.especimen_id = esp.id
                    JOIN planes_rotura pr ON pre.plan_rotura_id = pr.id
                    JOIN v_lab_muestras vm ON pr.muestra_id = vm.muestra_id
                    WHERE esp.estado IN ('programado', 'en_curado')
                      AND pre.fecha_programada BETWEEN CURRENT_DATE - INTERVAL 2 DAY AND CURRENT_DATE + INTERVAL 7 DAY
                    ORDER BY pre.fecha_programada ASC, esp.codigo_lab ASC";
        $stmtProx = $db->query($sqlProx);
        $rupturasProgramadas = $stmtProx->fetchAll(PDO::FETCH_ASSOC);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $rolUsuario = (int)($_SESSION['usuario_rol'] ?? 0);
        $puedeReidentificar = in_array($rolUsuario, [1, 2, 3], true);

        $this->renderizar('operaciones/vistas/laboratorio_dashboard', [
            'titulo' => 'Portal de Laboratorio LIMS (ISO 17025)',
            'tabActiva' => $tabActiva,
            'solicitudesPorAprobar' => $solicitudesPorAprobar,
            'solicitudesAprobadasRecientes' => $solicitudesAprobadasRecientes,
            'filtroAprobacion' => $filtroAprobacion,
            'estadoAprobacion' => $estadoAprobacion,
            'muestras' => $muestras,
            'kpis' => $kpis,
            'filtros' => $filtros,
            'rupturas' => $rupturasProgramadas,
            'puedeReidentificar' => $puedeReidentificar,
            'rolUsuario' => $rolUsuario,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Endpoint AJAX para alimentar DataTables o búsquedas en tiempo real.
     * Retorna datos sanitizados sin ninguna propiedad de clientes ni O/S.
     */
    public function muestrasAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $roturasModelo = new RoturasModelo();
        $filtros = [
            'busqueda' => trim($_GET['busqueda'] ?? ''),
            'estado' => trim($_GET['estado'] ?? ''),
            'tipo_ensayo' => trim($_GET['tipo_ensayo'] ?? ''),
            'rotura_hoy' => !empty($_GET['rotura_hoy']),
            'vencidas' => !empty($_GET['vencidas']),
            'fecha_desde' => trim($_GET['fecha_desde'] ?? ''),
            'fecha_hasta' => trim($_GET['fecha_hasta'] ?? '')
        ];

        $muestras = $roturasModelo->obtenerBandejaAnonima($filtros);

        // Sanitización rigurosa de salida
        $sanitizadas = [];
        foreach ($muestras as $m) {
            $sanitizadas[] = [
                'muestra_id' => (int)$m['muestra_id'],
                'codigo_lab' => $m['codigo_lab'],
                'codigo_campo' => $m['codigo_campo'],
                'material_descripcion' => $m['material_descripcion'],
                'plantilla_nombre' => $m['plantilla_nombre'] ?: 'Sin Plantilla',
                'norma_referencia' => $m['norma_referencia'] ?: 'ISO 17025',
                'total_especimenes' => (int)$m['total_especimenes'],
                'especimenes_ensayados' => (int)$m['especimenes_ensayados'],
                'fecha_moldeo' => $m['fecha_moldeo'],
                'fecha_ingreso' => $m['fecha_ingreso'],
                'estado_plan' => $m['estado_plan'] ?: 'Sin Plan',
                'proxima_rotura' => $m['proxima_rotura'],
                'es_vencida' => (bool)$m['es_vencida'],
                'es_hoy' => (bool)$m['es_hoy'],
                'diseno_resistencia' => $m['diseno_resistencia'] ?: 'N/A'
            ];
        }

        $respuesta->enviarJson([
            'status' => 'success',
            'total' => count($sanitizadas),
            'data' => $sanitizadas
        ]);
    }

    /**
     * Detalle ciego de una muestra en laboratorio para carga técnica y programa de roturas.
     */
    public function detalleMuestra(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $idParam = (int)($_GET['id_lote'] ?? ($_GET['id'] ?? ($_GET['muestra_id'] ?? 0)));
        $codigoParam = trim($_GET['codigo'] ?? '');

        $db = Conexion::obtenerInstancia();
        $muestraId = 0;

        if ($idParam > 0) {
            // Comprobar si el ID corresponde directamente a recepcion_muestras o a lotes_muestras
            $stmtM = $db->prepare("SELECT id FROM recepcion_muestras WHERE id = :id");
            $stmtM->execute(['id' => $idParam]);
            $muestraId = (int)$stmtM->fetchColumn();

            if ($muestraId === 0) {
                $stmtL = $db->prepare("SELECT id_recepcion FROM lotes_muestras WHERE id = :id");
                $stmtL->execute(['id' => $idParam]);
                $muestraId = (int)$stmtL->fetchColumn();
            }
        } elseif (!empty($codigoParam)) {
            $stmtC = $db->prepare("SELECT id FROM recepcion_muestras WHERE codigo_muestra = :cod");
            $stmtC->execute(['cod' => $codigoParam]);
            $muestraId = (int)$stmtC->fetchColumn();
        }

        if ($muestraId <= 0) {
            $_SESSION['error'] = 'Muestra técnica no encontrada o identificador no válido.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        $roturasModelo = new RoturasModelo();
        $detalle = $roturasModelo->obtenerDetalleMuestraAnonima($muestraId);

        if (!$detalle) {
            $_SESSION['error'] = 'La muestra de laboratorio no fue localizada en el sistema.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        // Si la muestra no tiene especímenes en la tabla nueva, crearlos automáticamente según lote
        if (empty($detalle['especimenes'])) {
            $stmtLoteData = $db->prepare("SELECT fecha_moldeo, nombre_lote FROM lotes_muestras WHERE id_recepcion = :mid");
            $stmtLoteData->execute(['mid' => $muestraId]);
            $loteFila = $stmtLoteData->fetch(PDO::FETCH_ASSOC);

            // Contar cantidad de especímenes declarados en ensayo_edades o asignar default de 3
            $stmtCountEd = $db->prepare("SELECT COUNT(*) FROM ensayo_edades ee JOIN lotes_muestras lm ON ee.id_lote = lm.id WHERE lm.id_recepcion = :mid");
            $stmtCountEd->execute(['mid' => $muestraId]);
            $cantEsp = (int)$stmtCountEd->fetchColumn();
            if ($cantEsp <= 0) {
                $cantEsp = 3;
            }

            $roturasModelo->crearEspecimenesParaMuestra(
                $muestraId,
                $cantEsp,
                $loteFila['fecha_moldeo'] ?? date('Y-m-d'),
                'cilindro_concreto',
                '15 x 30 cm'
            );

            // Recargar detalle
            $detalle = $roturasModelo->obtenerDetalleMuestraAnonima($muestraId);
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $rolUsuario = (int)($_SESSION['usuario_rol'] ?? 0);
        $puedeReidentificar = in_array($rolUsuario, [1, 2, 3], true);

        $this->renderizar('operaciones/vistas/laboratorio_detalle', [
            'titulo' => 'Hoja de Trabajo Ciega - Muestra ' . $detalle['muestra']['codigo_lab'],
            'muestra' => $detalle['muestra'],
            'especimenes' => $detalle['especimenes'],
            'plan' => $detalle['plan'],
            'edades' => $detalle['edades'],
            'informes' => $detalle['informes'],
            'puede_consolidar' => $detalle['puede_consolidar'],
            'puedeReidentificar' => $puedeReidentificar,
            'rolUsuario' => $rolUsuario,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Guarda el Plan de Roturas para una muestra.
     */
    public function guardarPlanRotura(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $esAjax = $peticion->esAjax();

            // Validación CSRF
            if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido o sesión expirada.']);
                    return;
                }
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $muestraId = (int)($datos['muestra_id'] ?? 0);
            $plantillaVersionId = !empty($datos['plantilla_version_id']) ? (int)$datos['plantilla_version_id'] : null;
            $usuarioId = $this->obtenerUsuarioIdAutenticado($peticion, $respuesta);
            if ($usuarioId === null) {
                return;
            }

            // Reconstruir array de edades
            $edadesConfig = [];
            $edadesArr = $datos['edad_dias'] ?? [];
            $cantArr = $datos['cantidad'] ?? [];

            for ($i = 0; $i < count($edadesArr); $i++) {
                $d = (int)($edadesArr[$i] ?? 0);
                $c = (int)($cantArr[$i] ?? 0);
                if ($d > 0 && $c > 0) {
                    $edadesConfig[] = ['edad_dias' => $d, 'cantidad' => $c];
                }
            }

            $roturasModelo = new RoturasModelo();
            $resultado = $roturasModelo->guardarPlanRotura($muestraId, $plantillaVersionId, $edadesConfig, $usuarioId);

            if ($esAjax) {
                $respuesta->enviarJson([
                    'status' => $resultado['exito'] ? 'success' : 'error',
                    'message' => $resultado['mensaje']
                ]);
                return;
            }

            if ($resultado['exito']) {
                $_SESSION['exito'] = $resultado['mensaje'];
            } else {
                $_SESSION['error'] = $resultado['mensaje'];
            }

            $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-muestra?muestra_id=' . $muestraId);
        }
    }

    /**
     * Modifica un plan de roturas ya iniciado bajo autorización de Calidad/Supervisión.
     */
    public function modificarPlanRotura(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        $rolUsuario = (int)($_SESSION['usuario_rol'] ?? 0);
        $usuarioId = $this->obtenerUsuarioIdAutenticado($peticion, $respuesta);
        if ($usuarioId === null) {
            return;
        }
        $esAjax = $peticion->esAjax();

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();

            if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido.']);
                    return;
                }
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $planId = (int)($datos['plan_id'] ?? 0);
            $muestraId = (int)($datos['muestra_id'] ?? 0);
            $motivo = trim($datos['motivo_modificacion'] ?? '');

            $roturasModelo = new RoturasModelo();
            $resultado = $roturasModelo->modificarPlanRoturaConAutorizacion($planId, [], $usuarioId, $rolUsuario, $motivo);

            if ($esAjax) {
                $respuesta->enviarJson([
                    'status' => $resultado['exito'] ? 'success' : 'error',
                    'message' => $resultado['mensaje']
                ]);
                return;
            }

            if ($resultado['exito']) {
                $_SESSION['exito'] = $resultado['mensaje'];
            } else {
                $_SESSION['error'] = $resultado['mensaje'];
            }

            $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-muestra?muestra_id=' . $muestraId);
        }
    }

    /**
     * Registra el ensayo de rotura de un espécimen con cálculo y validación de tolerancias ASTM C39.
     */
    public function registrarRotura(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $esAjax = $peticion->esAjax();

            if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido.']);
                    return;
                }
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $usuarioId = $this->obtenerUsuarioIdAutenticado($peticion, $respuesta);
            if ($usuarioId === null) {
                return;
            }
            $muestraId = (int)($datos['muestra_id'] ?? 0);

            $roturasModelo = new RoturasModelo();
            $resultado = $roturasModelo->registrarRotura($datos, $usuarioId);

            if ($esAjax) {
                $respuesta->enviarJson([
                    'status' => $resultado['exito'] ? 'success' : 'error',
                    'requiere_justificacion' => $resultado['requiere_justificacion'] ?? false,
                    'message' => $resultado['mensaje'],
                    'datos' => $resultado
                ]);
                return;
            }

            if ($resultado['exito']) {
                $_SESSION['exito'] = $resultado['mensaje'];
            } else {
                $_SESSION['error'] = $resultado['mensaje'];
            }

            $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-muestra?muestra_id=' . $muestraId);
        }
    }

    /**
     * Guarda la carga de rotura tradicional (retrocompatibilidad).
     */
    public function guardarRuptura(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();

            if (!isset($_SESSION['csrf_token'], $datos['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $idEnsayo = (int)($datos['id_ensayo'] ?? 0);
            $idLote = (int)($datos['id_lote'] ?? 0);
            $carga = (float)($datos['carga_lbs'] ?? 0);
            $area = (float)($datos['area_in2'] ?? 28.274);

            $modelo = new OperacionModelo();
            $resultado = $modelo->guardarResultadoRuptura($idEnsayo, [
                'carga_lbs' => $carga,
                'area_in2' => $area
            ]);

            if ($resultado['exito']) {
                $_SESSION['exito'] = $resultado['mensaje'];
            } else {
                $_SESSION['error'] = $resultado['mensaje'];
            }

            $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-muestra?id_lote=' . $idLote);
        }
    }

    /**
     * Genera un informe parcial para una edad completada.
     */
    public function generarInformeParcial(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $esAjax = $peticion->esAjax();

            if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido.']);
                    return;
                }
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $muestraId = (int)($datos['muestra_id'] ?? 0);
            $planEdadId = (int)($datos['plan_edad_id'] ?? 0);
            $usuarioId = $this->obtenerUsuarioIdAutenticado($peticion, $respuesta);
            if ($usuarioId === null) {
                return;
            }

            $roturasModelo = new RoturasModelo();
            $resultado = $roturasModelo->generarInformeParcial($muestraId, $planEdadId, $usuarioId);

            if ($esAjax) {
                $respuesta->enviarJson([
                    'status' => $resultado['exito'] ? 'success' : 'error',
                    'message' => $resultado['mensaje'],
                    'numero' => $resultado['numero'] ?? ''
                ]);
                return;
            }

            if ($resultado['exito']) {
                $_SESSION['exito'] = $resultado['mensaje'];
            } else {
                $_SESSION['error'] = $resultado['mensaje'];
            }

            $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-muestra?muestra_id=' . $muestraId);
        }
    }

    /**
     * Procesa la revisión y aprobación/devolución de informes de ensayo (Rol Supervisor/Calidad/Admin).
     */
    public function procesarRevisionInforme(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        $rolUsuario = (int)($_SESSION['usuario_rol'] ?? 0);
        $usuarioId = $this->obtenerUsuarioIdAutenticado($peticion, $respuesta);
        if ($usuarioId === null) {
            return;
        }
        $esAjax = $peticion->esAjax();

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();

            if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido.']);
                    return;
                }
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $informeId = (int)($datos['informe_id'] ?? 0);
            $muestraId = (int)($datos['muestra_id'] ?? 0);
            $nuevoEstado = trim($datos['nuevo_estado'] ?? '');
            $motivo = trim($datos['motivo_devolucion'] ?? '');

            $roturasModelo = new RoturasModelo();
            $resultado = $roturasModelo->procesarRevisionInforme($informeId, $nuevoEstado, $motivo, $usuarioId, $rolUsuario);

            if ($esAjax) {
                $respuesta->enviarJson([
                    'status' => $resultado['exito'] ? 'success' : 'error',
                    'message' => $resultado['mensaje']
                ]);
                return;
            }

            if ($resultado['exito']) {
                $_SESSION['exito'] = $resultado['mensaje'];
            } else {
                $_SESSION['error'] = $resultado['mensaje'];
            }

            $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-muestra?muestra_id=' . $muestraId);
        }
    }

    /**
     * Emite el Informe Consolidado. Bloqueado si hay parciales pendientes o devueltos.
     */
    public function generarInformeConsolidado(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        $rolUsuario = (int)($_SESSION['usuario_rol'] ?? 0);
        $usuarioId = $this->obtenerUsuarioIdAutenticado($peticion, $respuesta);
        if ($usuarioId === null) {
            return;
        }
        $esAjax = $peticion->esAjax();

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();

            if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido.']);
                    return;
                }
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $muestraId = (int)($datos['muestra_id'] ?? 0);

            $roturasModelo = new RoturasModelo();
            $resultado = $roturasModelo->generarInformeConsolidado($muestraId, $usuarioId, $rolUsuario);

            if ($esAjax) {
                $respuesta->enviarJson([
                    'status' => $resultado['exito'] ? 'success' : 'error',
                    'message' => $resultado['mensaje'],
                    'numero' => $resultado['numero'] ?? ''
                ]);
                return;
            }

            if ($resultado['exito']) {
                $_SESSION['exito'] = $resultado['mensaje'];
            } else {
                $_SESSION['error'] = $resultado['mensaje'];
            }

            $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-muestra?muestra_id=' . $muestraId);
        }
    }

    /**
     * Reemisión de informe de ensayo (N+1) con invalidación controlada según ISO/IEC 17025.
     */
    public function reemitirInforme(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        $usuarioId = $this->obtenerUsuarioIdAutenticado($peticion, $respuesta);
        if ($usuarioId === null) {
            return;
        }
        $esAjax = $peticion->esAjax();

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();

            if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido.']);
                    return;
                }
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $informeId = (int)($datos['informe_id'] ?? 0);
            $muestraId = (int)($datos['muestra_id'] ?? 0);
            $motivo = trim($datos['motivo_reemision'] ?? ($datos['motivo'] ?? ''));

            $roturasModelo = new RoturasModelo();
            $resultado = $roturasModelo->reemitirInforme($informeId, $motivo, $usuarioId);

            if ($esAjax) {
                $respuesta->enviarJson([
                    'status' => $resultado['exito'] ? 'success' : 'error',
                    'message' => $resultado['mensaje'],
                    'nuevo_informe_id' => $resultado['nuevo_informe_id'] ?? null,
                    'nuevo_numero' => $resultado['nuevo_numero'] ?? '',
                    'nueva_version' => $resultado['nueva_version'] ?? 1
                ]);
                return;
            }

            if ($resultado['exito']) {
                $_SESSION['exito'] = $resultado['mensaje'];
            } else {
                $_SESSION['error'] = $resultado['mensaje'];
            }

            $redir = ($muestraId > 0)
                ? '/Cycsa/publico/laboratorio/detalle-muestra?muestra_id=' . $muestraId
                : '/Cycsa/publico/laboratorio';
            $respuesta->redirigir($redir);
        }
    }

    /**
     * Reidentificación blindada bajo ISO 17025 Cláusula 4.1 y 4.2.
     * Permitido para Administrador (1), Supervisor / Gerente de Calidad (3) o Comercial (2).
     * Prohibido para rol Técnico (6).
     * Registra en bitacora_reidentificacion y bitacora.
     */
    public function reidentificarMuestra(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();

            if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                $respuesta->enviarJson([
                    'status' => 'error',
                    'message' => 'Token CSRF inválido o sesión expirada.'
                ], 403);
                return;
            }

            $codigoLab = trim($datos['codigo_lab'] ?? ($datos['muestra_id'] ?? ''));
            $motivo = trim($datos['motivo'] ?? '');
            $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
            $usuarioNombre = trim($_SESSION['usuario_nombre'] ?? 'Usuario');
            $rolId = (int)($_SESSION['usuario_rol'] ?? 0);
            $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';

            $roturasModelo = new RoturasModelo();
            $res = $roturasModelo->reidentificarMuestra($codigoLab, $usuarioId, $usuarioNombre, $rolId, $ip, $motivo);

            $respuesta->enviarJson([
                'status' => $res['exito'] ? 'success' : 'error',
                'message' => $res['mensaje'],
                'datos' => $res['datos'] ?? null
            ], $res['codigo_http'] ?? 200);
        }
    }

    /**
     * Imprime la etiqueta física de probeta/cilindro conteniendo ÚNICAMENTE datos técnicos y código QR.
     * Cero datos de cliente o comerciales (ISO 17025).
     */
    public function imprimirEtiqueta(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $espId = (int)($_GET['id'] ?? 0);
        $db = Conexion::obtenerInstancia();

        $stmt = $db->prepare("
            SELECT esp.*, vm.material_descripcion, vm.diseno_resistencia, vm.plantilla_nombre, vm.norma_referencia
            FROM especimenes esp
            JOIN v_lab_muestras vm ON esp.muestra_id = vm.muestra_id
            WHERE esp.id = :id
        ");
        $stmt->execute(['id' => $espId]);
        $esp = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$esp) {
            $_SESSION['error'] = 'Espécimen no encontrado para emitir etiqueta.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        header('Content-Type: text/html; charset=utf-8');
        echo '<!DOCTYPE html>
        <html lang="es">
        <head>
            <meta charset="UTF-8">
            <title>Etiqueta Oficial de Espécimen - ' . htmlspecialchars($esp['codigo_lab']) . '</title>
            <style>
                @page { size: 100mm 50mm; margin: 0; }
                body { font-family: "Arial", sans-serif; margin: 0; padding: 6mm; color: #000; }
                .etiqueta-card { border: 2px solid #000; padding: 4mm; border-radius: 4px; display: flex; flex-direction: column; justify-content: space-between; height: 38mm; box-sizing: border-box; }
                .header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1.5px solid #000; padding-bottom: 2mm; margin-bottom: 2mm; }
                .titulo { font-size: 13pt; font-weight: 900; letter-spacing: 0.5px; }
                .codigo-lab { font-size: 16pt; font-weight: 900; font-family: monospace; }
                .body-grid { display: grid; grid-template-columns: 1fr auto; gap: 4mm; align-items: center; }
                .info-item { font-size: 8.5pt; margin-bottom: 1mm; }
                .info-item strong { text-transform: uppercase; }
                .qr-placeholder { width: 22mm; height: 22mm; border: 1px solid #333; display: flex; align-items: center; justify-content: center; font-size: 6pt; text-align: center; }
                .footer { font-size: 7pt; text-align: right; border-top: 1px solid #666; padding-top: 1mm; margin-top: 1mm; font-style: italic; }
            </style>
        </head>
        <body onload="window.print()">
            <div class="etiqueta-card">
                <div class="header">
                    <span class="titulo">CYCSA LIMS &bull; ISO/IEC 17025</span>
                    <span class="codigo-lab">' . htmlspecialchars($esp['codigo_lab']) . '</span>
                </div>
                <div class="body-grid">
                    <div>
                        <div class="info-item"><strong>Material:</strong> ' . htmlspecialchars($esp['material_descripcion']) . '</div>
                        <div class="info-item"><strong>Moldeo:</strong> ' . htmlspecialchars(date('d/m/Y', strtotime($esp['fecha_moldeo']))) . '</div>
                        <div class="info-item"><strong>f\'c Diseño:</strong> ' . htmlspecialchars($esp['diseno_resistencia'] ?: 'N/A') . '</div>
                        <div class="info-item"><strong>Dimensiones:</strong> ' . htmlspecialchars($esp['dimensiones']) . '</div>
                        <div class="info-item"><strong>Norma:</strong> ' . htmlspecialchars($esp['norma_referencia']) . '</div>
                    </div>
                    <div class="qr-placeholder">
                        <img src="https://api.qrserver.com/v1/create-qr-code/?size=85x85&data=' . urlencode(obtenerBaseUrl() . '/laboratorio/detalle-muestra?codigo=' . $esp['codigo_lab']) . '" alt="QR" style="width:100%;height:100%;">
                    </div>
                </div>
                <div class="footer">
                    MUESTRA CIEGA &bull; CLAÚSULAS 4.1 Y 4.2 &bull; IMPARCIALIDAD ESTRICTA
                </div>
            </div>
        </body>
        </html>';
        exit;
    }

    /**
     * Procesa la aceptación técnica y recepción de muestras en el laboratorio (ISO 17025).
     * Además crea automáticamente los especímenes técnicos en la tabla especimenes.
     */
    public function aceptarMuestraRapida(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $idOS = (int)($datos['id_os'] ?? 0);
            $idHoja = (int)($datos['id_hoja'] ?? 0);
            if ($idOS <= 0) {
                $_SESSION['error'] = 'Orden de Servicio inválida.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $modelo = new OperacionModelo();
            $os = $modelo->obtenerOSPorId($idOS);
            if (!$os) {
                $_SESSION['error'] = 'Orden de Servicio no encontrada.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $db = Conexion::obtenerInstancia();

            // Obtener las muestras declaradas en la Hoja RT-FM-13 correspondiente
            $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS, $idHoja > 0 ? $idHoja : null);
            $muestrasDeclaradasRT = [];
            if ($hoja && !empty($hoja['muestras_json'])) {
                $muestrasDeclaradasRT = json_decode($hoja['muestras_json'], true) ?: [];
            }

            $servicios = $modelo->filtrarDetallesPermitidos($idOS, $modelo->obtenerDetallesCotizacion((int)$os['id_cotizacion']));
            $idDetalleCotizacion = (int)($datos['id_detalle_cotizacion'] ?? ($servicios[0]['id'] ?? 0));
            $tipoMuestraCalculado = !empty($os['requiere_muestreo']) ? 'Campo' : 'Laboratorio';

            $muestrasPayload = [];
            if (!empty($muestrasDeclaradasRT)) {
                foreach ($muestrasDeclaradasRT as $idx => $mDecl) {
                    $nombreCampo = trim($mDecl['nombre_muestra'] ?? ('M-' . ($idx + 1)));
                    $descLote = trim($mDecl['descripcion'] ?? $nombreCampo);
                    
                    $muestrasPayload[] = [
                        'id_detalle_cotizacion' => $idDetalleCotizacion,
                        'tipo_muestra' => $tipoMuestraCalculado,
                        'id_cilindro' => $nombreCampo,
                        'codigo_campo' => $nombreCampo,
                        'is_qa_qc' => 0,
                        'nombre_lote' => $descLote,
                        'fecha_moldeo' => !empty($hoja['fecha_hora_toma_muestra']) ? date('Y-m-d', strtotime($hoja['fecha_hora_toma_muestra'])) : date('Y-m-d'),
                        'diseno_resistencia' => '',
                        'procedimiento_muestreo' => trim($datos['procedimiento_muestreo'] ?? 'ASTM / ISO 17025'),
                        'edades_dias' => [],
                        'edades_identificadores' => []
                    ];
                }
            } else {
                $muestrasPayload[] = [
                    'id_detalle_cotizacion' => $idDetalleCotizacion,
                    'tipo_muestra' => $tipoMuestraCalculado,
                    'id_cilindro' => 'M-01',
                    'codigo_campo' => 'M-01',
                    'is_qa_qc' => 0,
                    'nombre_lote' => 'Muestra General',
                    'fecha_moldeo' => date('Y-m-d'),
                    'procedimiento_muestreo' => 'ISO 17025'
                ];
            }

            $actualHojaId = $idHoja > 0 ? $idHoja : ($hoja['id'] ?? null);
            $payloadRecepcion = [
                'id_os' => $idOS,
                'id_hoja' => $actualHojaId,
                'limpiar_previas' => false,
                'id_detalle_cotizacion' => $idDetalleCotizacion,
                'tipo_muestra' => $tipoMuestraCalculado,
                'fecha_recepcion' => !empty($datos['fecha_recepcion']) ? $datos['fecha_recepcion'] : date('Y-m-d H:i:s'),
                'recibido_por' => (int)($_SESSION['usuario_id'] ?? 0),
                'entregado_por' => trim($datos['entregado_por'] ?? 'Cliente'),
                'observaciones' => trim($datos['observaciones'] ?? 'Muestras aceptadas bajo estricta conformidad técnica con la Hoja CYCSA-RT-FM-13 (ISO 17025)'),
                'muestras_recibidas_json' => json_encode($muestrasPayload)
            ];

            if ($modelo->registrarRecepcion($payloadRecepcion)) {
                // Crear automáticamente los especímenes en la tabla especimenes para las muestras que aún no los tienen
                $roturasModelo = new RoturasModelo();
                if ($actualHojaId) {
                    $stmtMuestrasCreadas = $db->prepare("SELECT rm.id, lm.fecha_moldeo 
                        FROM recepcion_muestras rm 
                        JOIN lotes_muestras lm ON lm.id_recepcion = rm.id 
                        WHERE rm.id_os = :id_os AND rm.id_hoja = :id_hoja
                          AND rm.id NOT IN (SELECT DISTINCT muestra_id FROM especimenes)");
                    $stmtMuestrasCreadas->execute(['id_os' => $idOS, 'id_hoja' => $actualHojaId]);
                } else {
                    $stmtMuestrasCreadas = $db->prepare("SELECT rm.id, lm.fecha_moldeo 
                        FROM recepcion_muestras rm 
                        JOIN lotes_muestras lm ON lm.id_recepcion = rm.id 
                        WHERE rm.id_os = :id_os
                          AND rm.id NOT IN (SELECT DISTINCT muestra_id FROM especimenes)");
                    $stmtMuestrasCreadas->execute(['id_os' => $idOS]);
                }
                $creadas = $stmtMuestrasCreadas->fetchAll(PDO::FETCH_ASSOC);

                foreach ($creadas as $c) {
                    $roturasModelo->crearEspecimenesParaMuestra((int)$c['id'], 3, $c['fecha_moldeo'] ?? date('Y-m-d'));
                }

                $prefijoAsignado = ($tipoMuestraCalculado === 'Campo') ? 'MC' : 'MS';
                $numHojaTexto = !empty($hoja['numero_registro']) ? " [Reg: {$hoja['numero_registro']}]" : "";
                $_SESSION['exito'] = "Muestras de la Hoja RT-FM-13{$numHojaTexto} aceptadas e ingresadas exitosamente a la Bandeja Anónima de Laboratorio ({$prefijoAsignado}-XXXX-" . date('y') . ").";
            } else {
                $detalleRecepcion = $modelo->obtenerUltimoErrorRecepcion() ?? '';
                $_SESSION['error'] = str_contains($detalleRecepcion, 'no está habilitado')
                    ? $detalleRecepcion
                    : 'Ocurrió un error al registrar el ingreso de muestras al laboratorio.';
            }

            $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=bandeja');
        }
    }

    /**
     * Permite avanzar o retroceder el estado de una solicitud en el apartado de Aprobación de Laboratorio.
     */
    public function cambiarEstadoKanban(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $esAjax = $peticion->esAjax();

            $tokenPost = $datos['csrf_token'] ?? '';
            $tokenSession = $_SESSION['csrf_token'] ?? '';

            if (empty($tokenPost) || empty($tokenSession) || !hash_equals($tokenSession, $tokenPost)) {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido o sesión expirada.']);
                    return;
                }
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=aprobacion');
                return;
            }

            $estadosPermitidos = [
                'Estado 1: Recepcion',
                'Estado 2: Revision',
                'Estado 2: Observada',
                'Estado 4: Ingreso Laboratorio',
                'Estado 5: Solicitud Tecnicos',
                'Estado 7: Revision Resultados',
                'Finalizado'
            ];

            $nuevoEstado = trim($datos['nuevo_estado'] ?? '');
            $motivo = trim($datos['motivo_observacion'] ?? '');
            $idOS = (int)($datos['id_os'] ?? 0);

            if (!in_array($nuevoEstado, $estadosPermitidos, true) || $idOS <= 0) {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'Estado de destino no válido.']);
                    return;
                }
                $_SESSION['error'] = 'Estado de destino no válido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=aprobacion');
                return;
            }

            $modelo = new OperacionModelo();
            if ($modelo->actualizarEstadoOS($idOS, $nuevoEstado, !empty($motivo) ? $motivo : null)) {
                registrarBitacora('laboratorio', 'cambiar_estado', "Estado de Solicitud/OS #{$idOS} actualizado a {$nuevoEstado}" . (!empty($motivo) ? " con motivo: {$motivo}" : ""), $idOS);
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'success', 'message' => 'Estado actualizado exitosamente.']);
                    return;
                }
                $_SESSION['exito'] = 'Estado de la solicitud actualizado correctamente a: ' . $nuevoEstado;
            } else {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'No se pudo actualizar el estado.']);
                    return;
                }
                $_SESSION['error'] = 'No se pudo actualizar el estado.';
            }

            $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=aprobacion');
        }
    }

    /**
     * Imprime la Hoja de Solicitud de Laboratorio de Materiales y Suelo conforme a ISO 17025.
     */
    public function imprimirHojaSolicitud(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $idOS = (int)($_GET['id_os'] ?? 0);
        $idHoja = (int)($_GET['id_hoja'] ?? 0);
        if ($idOS <= 0) {
            $_SESSION['error'] = 'Orden de Servicio inválida.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        $db = Conexion::obtenerInstancia();
        $modelo = new OperacionModelo();

        $os = $modelo->obtenerOSPorId($idOS);
        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS, $idHoja > 0 ? $idHoja : null);
        $ensayos = $modelo->filtrarDetallesPermitidos($idOS, $modelo->obtenerDetallesCotizacion((int)$os['id_cotizacion']));

        if ($idHoja > 0) {
            $stmtMin = $db->prepare("SELECT MIN(id) FROM hojas_solicitud WHERE id_os = :id_os");
            $stmtMin->execute(['id_os' => $idOS]);
            $minHojaId = (int)$stmtMin->fetchColumn();

            if ($idHoja === $minHojaId) {
                $stmtRec = $db->prepare("SELECT * FROM recepcion_muestras WHERE id_os = :id_os AND (id_hoja = :id_hoja OR id_hoja IS NULL) ORDER BY id DESC LIMIT 1");
            } else {
                $stmtRec = $db->prepare("SELECT * FROM recepcion_muestras WHERE id_os = :id_os AND id_hoja = :id_hoja ORDER BY id DESC LIMIT 1");
            }
            $stmtRec->execute(['id_os' => $idOS, 'id_hoja' => $idHoja]);
        } else {
            $stmtRec = $db->prepare("SELECT * FROM recepcion_muestras WHERE id_os = :id_os ORDER BY id DESC LIMIT 1");
            $stmtRec->execute(['id_os' => $idOS]);
        }
        $recepcion = $stmtRec->fetch(PDO::FETCH_ASSOC);

        if ($idHoja > 0) {
            $sqlHojaCond = ($idHoja === $minHojaId) ? "(rm.id_hoja = :id_hoja OR rm.id_hoja IS NULL)" : "rm.id_hoja = :id_hoja";
            $stmtMuestras = $db->prepare("
                SELECT rm.codigo_muestra, rm.codigo_campo, rm.fecha_recepcion, lm.fecha_moldeo AS fecha_elaboracion,
                       (SELECT descripcion_ensayo FROM cotizacion_detalles cd WHERE cd.id = (SELECT id_detalle_cotizacion FROM ensayo_edades ee WHERE ee.id_lote = lm.id LIMIT 1)) AS nombre_ensayo
                FROM recepcion_muestras rm
                LEFT JOIN lotes_muestras lm ON lm.id_recepcion = rm.id
                WHERE rm.id_os = :id_os AND {$sqlHojaCond}
                ORDER BY rm.id ASC
            ");
            $stmtMuestras->execute(['id_os' => $idOS, 'id_hoja' => $idHoja]);
        } else {
            $stmtMuestras = $db->prepare("
                SELECT rm.codigo_muestra, rm.codigo_campo, rm.fecha_recepcion, lm.fecha_moldeo AS fecha_elaboracion,
                       (SELECT descripcion_ensayo FROM cotizacion_detalles cd WHERE cd.id = (SELECT id_detalle_cotizacion FROM ensayo_edades ee WHERE ee.id_lote = lm.id LIMIT 1)) AS nombre_ensayo
                FROM recepcion_muestras rm
                LEFT JOIN lotes_muestras lm ON lm.id_recepcion = rm.id
                WHERE rm.id_os = :id_os
                ORDER BY rm.id ASC
            ");
            $stmtMuestras->execute(['id_os' => $idOS]);
        }
        $muestrasLote = $stmtMuestras->fetchAll(PDO::FETCH_ASSOC);

        // Si aún no se ha recepcionado formalmente, construimos la lista tentativa desde la Hoja RT-FM-13
        if (empty($muestrasLote) && $hoja && !empty($hoja['muestras_json'])) {
            $declaradas = json_decode($hoja['muestras_json'], true) ?: [];
            $anioShort = date('y');
            $prefijoMuestra = determinarPrefijoMuestraOS($os);
            $siguienteCorr = $modelo->obtenerSiguienteConsecutivoMuestra((int)date('Y'), $prefijoMuestra);

            foreach ($declaradas as $idx => $dec) {
                $muestrasLote[] = [
                    'codigo_muestra' => sprintf("{$prefijoMuestra}-%04d-%02d", $siguienteCorr + $idx, $anioShort),
                    'codigo_campo' => $dec['nombre_muestra'] ?? ($prefijoMuestra . '-' . ($idx + 1)),
                    'fecha_recepcion' => date('Y-m-d'),
                    'fecha_elaboracion' => !empty($hoja['fecha_hora_toma_muestra']) ? date('Y-m-d', strtotime($hoja['fecha_hora_toma_muestra'])) : date('Y-m-d'),
                    'nombre_ensayo' => $ensayos[0]['descripcion_ensayo'] ?? 'Ensayo Estándar'
                ];
            }
        }

        $this->renderizarSinLayout('operaciones/vistas/hoja_solicitud_laboratorio_print', [
            'os' => $os,
            'hoja' => $hoja,
            'ensayos' => $ensayos,
            'recepcion' => $recepcion,
            'muestrasLote' => $muestrasLote,
            'muestras' => $muestrasLote
        ]);
    }

    /**
     * Endpoint AJAX para evaluar en tiempo real cualquier Hoja de Cálculo (RT-FM-25 al RT-FM-64)
     * utilizando MotorHojasCalculoServicio y EvaluadorFormula.
     */
    public function calcularHcAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        if ($this->obtenerUsuarioIdAutenticado($peticion, $respuesta) === null) {
            return;
        }

        $datos = $peticion->obtenerDatos();
        if ($peticion->esPost() && !$this->verificarTokenCsrf($peticion, $respuesta, $datos)) {
            return;
        }

        $codigoFormato = strtoupper(trim((string)($datos['codigo_formato'] ?? 'RT-FM-61')));
        $entradas = isset($datos['entradas']) && is_array($datos['entradas']) ? $datos['entradas'] : $datos;

        try {
            $motor = new \Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio();
            if (!in_array($codigoFormato, $motor->listarCodigosFormatos(), true)) {
                $respuesta->enviarJson([
                    'status' => 'error',
                    'message' => 'Formato de hoja de cálculo no válido.'
                ], 422);
                return;
            }

            $resultado = $motor->evaluarEspecimen($codigoFormato, $entradas);
            $respuesta->enviarJson([
                'status' => 'success',
                'data' => $resultado
            ]);
        } catch (\Throwable $e) {
            error_log('LaboratorioControlador::calcularHcAjax error: ' . $e->getMessage());
            $respuesta->enviarJson([
                'status' => 'error',
                'message' => 'No fue posible procesar los datos de la hoja de cálculo. Verifique los valores ingresados.'
            ], 422);
        }
    }

    /**
     * Endpoint aditivo para evaluar y registrar una Hoja de Campo (RT-FM-54, RT-FM-55, RT-FM-62)
     * o Proctor (RT-FM-27) con enlace automático de PVSM y Humedad Óptima.
     */
    public function registrarHojaCampoHc(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        $usuarioId = $this->obtenerUsuarioIdAutenticado($peticion, $respuesta);
        if ($usuarioId === null) {
            return;
        }

        $datos = $peticion->obtenerDatos();
        if (!$this->verificarTokenCsrf($peticion, $respuesta, $datos)) {
            return;
        }

        $idOs = (int)($datos['id_os'] ?? 0);
        $codigoFormato = strtoupper(trim((string)($datos['codigo_formato'] ?? 'RT-FM-55')));
        $variante = trim((string)($datos['variante'] ?? 'ESTANDAR'));
        $encabezado = isset($datos['encabezado']) && is_array($datos['encabezado']) ? $datos['encabezado'] : [];
        $puntos = isset($datos['puntos']) && is_array($datos['puntos']) ? $datos['puntos'] : [];
        $proctorMuestraId = !empty($datos['proctor_muestra_id']) ? (int)$datos['proctor_muestra_id'] : null;
        $muestraId = !empty($datos['muestra_id']) ? (int)$datos['muestra_id'] : null;
        $idHoja = !empty($datos['id_hoja_solicitud']) ? (int)$datos['id_hoja_solicitud'] : null;

        if (!in_array($codigoFormato, ['RT-FM-27', 'RT-FM-54', 'RT-FM-55', 'RT-FM-62'], true)) {
            $respuesta->enviarJson([
                'status' => 'error',
                'message' => 'Formato de hoja de campo no válido.'
            ], 422);
            return;
        }

        try {
            $servicio = new \Cycsa\Modulos\Operaciones\Servicios\HojaCampoProctorServicio();
            if ($codigoFormato === 'RT-FM-27') {
                $res = $servicio->registrarResultadoProctor($idOs, $encabezado, $puntos, $muestraId, $idHoja, $usuarioId, $variante);
            } else {
                $res = $servicio->evaluarYRegistrarHojaCampo($idOs, $codigoFormato, $puntos, $encabezado, $proctorMuestraId, $muestraId, $idHoja, $usuarioId, $variante);
            }
            $respuesta->enviarJson([
                'status' => 'success',
                'data' => $res
            ]);
        } catch (\Throwable $e) {
            error_log('LaboratorioControlador::registrarHojaCampoHc error: ' . $e->getMessage());
            $respuesta->enviarJson([
                'status' => 'error',
                'message' => 'No fue posible registrar la hoja de campo. Verifique los datos ingresados.'
            ], 422);
        }
    }

    /**
     * Renderiza el informe imprimible oficial para los 5 formatos nuevos de Hojas de Cálculo
     * que no poseen equivalente RT-FM-22 (RT-FM-46 CBR, RT-FM-56 Picnómetros, RT-FM-58 Sanidad,
     * RT-FM-62 Infiltración, RT-FM-64 Acero) cargando exclusivamente registros reales de BD.
     */
    public function imprimirReporteHcNuevo(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        if ($this->obtenerUsuarioIdAutenticado($peticion, $respuesta) === null) {
            return;
        }

        $codigoFormato = strtoupper(trim((string)($_GET['formato'] ?? '')));
        $motor = new \Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio();
        $mapeador = new \Cycsa\Modulos\Operaciones\Servicios\MapeadorRtFm22Servicio($motor);

        if (!$mapeador->esFormatoReporteNuevoValido($codigoFormato)) {
            if ($peticion->esAjax()) {
                $respuesta->enviarJson([
                    'status' => 'error',
                    'message' => 'El formato solicitado no es un reporte válido.'
                ], 422);
                return;
            }
            $_SESSION['error'] = 'El formato solicitado no es un reporte válido.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        try {
            $reporte = $mapeador->construirReporteNuevoDesdeBaseDatos($codigoFormato, $_GET);
        } catch (\Throwable $e) {
            error_log('LaboratorioControlador::imprimirReporteHcNuevo error: ' . $e->getMessage());
            $reporte = null;
        }

        if ($reporte === null) {
            if ($peticion->esAjax()) {
                $respuesta->enviarJson([
                    'status' => 'error',
                    'message' => 'No se encontraron datos reales registrados en base de datos para emitir este informe.'
                ], 404);
                return;
            }
            $_SESSION['error'] = 'No se encontraron datos reales registrados en base de datos para emitir este informe.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        require dirname(__DIR__) . '/Vistas/reporte_hc_nuevo_print.php';
    }
}
