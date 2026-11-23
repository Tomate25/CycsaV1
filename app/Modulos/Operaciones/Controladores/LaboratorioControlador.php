<?php

namespace Cycsa\Modulos\Operaciones\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\Operaciones\Modelos\RoturasModelo;
use Cycsa\Modulos\Operaciones\Servicios\ConfigCalculoLabServicio;
use Cycsa\Nucleo\Conexion;
use PDO;

class LaboratorioControlador extends ControladorBase {

    private function verificarSesion(Respuesta $respuesta): void {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        if (!isset($_SESSION['usuario_id']) || (int)$_SESSION['usuario_id'] <= 0) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
    }

    private function obtenerUsuarioIdAutenticado(Peticion $peticion, Respuesta $respuesta): ?int {
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
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
            @session_start();
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

        $tabActiva = $_GET['tab'] ?? 'bandeja';
        if (!in_array($tabActiva, ['bandeja', 'programadas'], true)) {
            $tabActiva = 'bandeja';
        }

        // Filtros capturados por GET para la bandeja anónima de muestras nuevas
        $incluirCerradas = !empty($_GET['incluir_cerradas']) || (($estadoFiltro = trim($_GET['estado'] ?? '')) === 'cerrado');
        $filtros = [
            'busqueda' => trim($_GET['busqueda'] ?? ''),
            'estado' => trim($_GET['estado'] ?? ''),
            'tipo_ensayo' => trim($_GET['tipo_ensayo'] ?? ''),
            'rotura_hoy' => !empty($_GET['rotura_hoy']),
            'vencidas' => !empty($_GET['vencidas']),
            'fecha_desde' => trim($_GET['fecha_desde'] ?? ''),
            'fecha_hasta' => trim($_GET['fecha_hasta'] ?? ''),
            'incluir_cerradas' => $incluirCerradas,
        ];

        // Obtener listado de la vista SQL anónima v_lab_muestras (GET 100% libre de escrituras)
        $muestrasRaw = $roturasModelo->obtenerBandejaAnonima($filtros);
        if (!$incluirCerradas) {
            $muestrasRaw = array_values(array_filter($muestrasRaw, static function (array $item): bool {
                return ($item['estado_plan'] ?? '') !== 'cerrado';
            }));
        }

        // Agrupar muestras por Lote (id_hoja) para visualización consolidada ISO 17025 (las probetas van juntas)
        $muestras = [];
        $mapaLotes = [];
        foreach ($muestrasRaw as $m) {
            $idHojaM = (int)($m['id_hoja'] ?? 0);
            if ($idHojaM > 0) {
                if (!isset($mapaLotes[$idHojaM])) {
                    $idxLote = count($muestras);
                    $mapaLotes[$idHojaM] = $idxLote;
                    $mConsolidada = $m;
                    $mConsolidada['es_lote'] = true;
                    $mConsolidada['muestras_items'] = [$m];
                    $mConsolidada['codigos_lote'] = [$m['codigo_lab']];
                    $mConsolidada['total_especimenes_lote'] = (int)$m['total_especimenes'];
                    $mConsolidada['especimenes_ensayados_lote'] = (int)$m['especimenes_ensayados'];
                    $mConsolidada['proxima_rotura_lote'] = $m['proxima_rotura'];
                    $muestras[$idxLote] = $mConsolidada;
                } else {
                    $idxLote = $mapaLotes[$idHojaM];
                    $muestras[$idxLote]['muestras_items'][] = $m;
                    $muestras[$idxLote]['codigos_lote'][] = $m['codigo_lab'];
                    $muestras[$idxLote]['total_especimenes_lote'] += (int)$m['total_especimenes'];
                    $muestras[$idxLote]['especimenes_ensayados_lote'] += (int)$m['especimenes_ensayados'];
                    if (!empty($m['proxima_rotura'])) {
                        if (empty($muestras[$idxLote]['proxima_rotura_lote']) || $m['proxima_rotura'] < $muestras[$idxLote]['proxima_rotura_lote']) {
                            $muestras[$idxLote]['proxima_rotura_lote'] = $m['proxima_rotura'];
                        }
                    }
                }
            } else {
                $m['es_lote'] = false;
                $m['muestras_items'] = [$m];
                $m['codigos_lote'] = [$m['codigo_lab']];
                $m['total_especimenes_lote'] = (int)$m['total_especimenes'];
                $m['especimenes_ensayados_lote'] = (int)$m['especimenes_ensayados'];
                $m['proxima_rotura_lote'] = $m['proxima_rotura'];
                $muestras[] = $m;
            }
        }

        $hoy = date('Y-m-d');
        foreach ($muestras as &$itemG) {
            if (!empty($itemG['es_lote'])) {
                sort($itemG['codigos_lote'], SORT_NATURAL);
                $cantCods = count($itemG['codigos_lote']);
                $itemG['codigo_rango'] = ($cantCods > 1)
                    ? ($itemG['codigos_lote'][0] . ' al ' . $itemG['codigos_lote'][$cantCods - 1])
                    : $itemG['codigos_lote'][0];
                $itemG['proxima_rotura'] = $itemG['proxima_rotura_lote'];
                $itemG['total_especimenes'] = $itemG['total_especimenes_lote'];
                $itemG['especimenes_ensayados'] = $itemG['especimenes_ensayados_lote'];
                $itemG['total_muestras_lote'] = $cantCods;
                $itemG['es_vencida'] = (!empty($itemG['proxima_rotura']) && $itemG['proxima_rotura'] < $hoy && $itemG['estado_plan'] !== 'cerrado');
                $itemG['es_hoy'] = (!empty($itemG['proxima_rotura']) && $itemG['proxima_rotura'] === $hoy);
            }
            $itemG['es_nueva'] = (((int)($itemG['especimenes_ensayados'] ?? 0) === 0) && ($itemG['estado_plan'] ?? '') !== 'cerrado');
        }
        unset($itemG);

        if (empty($filtros['rotura_hoy']) && empty($filtros['vencidas'])) {
            usort($muestras, static function (array $a, array $b): int {
                return ((int)($b['muestra_id'] ?? 0)) <=> ((int)($a['muestra_id'] ?? 0));
            });
        }

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
                           vm.muestra_id, vm.material_descripcion, vm.diseno_resistencia, vm.plantilla_nombre,
                           vm.id_hoja, vm.total_muestras_lote, vm.codigo_campo
                    FROM plan_rotura_edades pre
                    JOIN plan_rotura_asignaciones pra ON pra.plan_edad_id = pre.id
                    JOIN especimenes esp ON pra.especimen_id = esp.id
                    JOIN planes_rotura pr ON pre.plan_rotura_id = pr.id
                    JOIN v_lab_muestras vm ON pr.muestra_id = vm.muestra_id
                    WHERE esp.estado IN ('programado', 'en_curado')
                      AND pre.fecha_programada BETWEEN CURRENT_DATE - INTERVAL 2 DAY AND CURRENT_DATE + INTERVAL 7 DAY
                    ORDER BY pre.fecha_programada ASC, vm.id_hoja ASC, esp.codigo_lab ASC";
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

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $rolUsuario = (int)($_SESSION['usuario_rol'] ?? 0);
        $puedeReidentificar = in_array($rolUsuario, [1, 2, 3], true);

        // Cargar datos técnicos desde la matriz de Operaciones (fuente de verdad para Edad, Temp, Área, Diámetros)
        $datosMatriz = $this->obtenerDatosMatrizPorMuestra($db, $detalle['muestra']);

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
            'datosMatriz' => $datosMatriz,
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
            $idHojaRedir = (int)($datos['id_hoja'] ?? 0);
            $motivo = trim($datos['motivo_modificacion'] ?? '');

            $nuevaEdad = (int)($datos['edad_dias'] ?? 0);
            $nuevaFecha = trim((string)($datos['fecha_programada'] ?? ''));
            $nuevasEdades = [];
            if ($nuevaEdad > 0) {
                $nuevasEdades['edad_dias'] = $nuevaEdad;
                if (!empty($nuevaFecha)) {
                    $nuevasEdades['fecha_programada'] = $nuevaFecha;
                }
            }

            if ($idHojaRedir <= 0 && $muestraId > 0) {
                $dbTmp = Conexion::obtenerInstancia();
                $stmtHRedir = $dbTmp->prepare("SELECT id_hoja FROM recepcion_muestras WHERE id = :id");
                $stmtHRedir->execute(['id' => $muestraId]);
                $idHojaRedir = (int)$stmtHRedir->fetchColumn();
            }

            // Auto-resolución de plan_edad_id si viene omitido pero existe asignación al espécimen
            if (empty($datos['plan_edad_id']) && !empty($datos['especimen_id'])) {
                $dbTmp = Conexion::obtenerInstancia();
                $stmtPE = $dbTmp->prepare("SELECT plan_edad_id FROM plan_rotura_asignaciones WHERE especimen_id = :id LIMIT 1");
                $stmtPE->execute(['id' => (int)$datos['especimen_id']]);
                $peFound = (int)$stmtPE->fetchColumn();
                if ($peFound > 0) {
                    $datos['plan_edad_id'] = $peFound;
                }
            }

            $roturasModelo = new RoturasModelo();
            $resultado = $roturasModelo->modificarPlanRoturaConAutorizacion($planId, $nuevasEdades, $usuarioId, $rolUsuario, $motivo);

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

            if ($idHojaRedir > 0) {
                $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $idHojaRedir);
            } else {
                $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-muestra?muestra_id=' . $muestraId);
            }
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
            $esAjax = $peticion->esAjax()
                || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
                || (($datos['formato_respuesta'] ?? '') === 'json');

            // Seguridad (Sección 10 Riesgo #4): eliminar cualquier parámetro de cálculo inyectado desde el formulario
            foreach (['divisor_kgcm2', 'usar_prensa', 'usar_factor_ld', 'redondear_psi_10', 'opciones_calculo', '_opciones_internas_test'] as $campoProtegido) {
                unset($datos[$campoProtegido], $_POST[$campoProtegido]);
                if (isset($datos['datos_crudos']) && is_array($datos['datos_crudos'])) {
                    unset($datos['datos_crudos'][$campoProtegido]);
                }
            }

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
            $idHojaRedir = (int)($datos['id_hoja'] ?? 0);

            if ($idHojaRedir <= 0 && $muestraId > 0) {
                $dbTmp = Conexion::obtenerInstancia();
                $stmtHRedir = $dbTmp->prepare("SELECT id_hoja FROM recepcion_muestras WHERE id = :id");
                $stmtHRedir->execute(['id' => $muestraId]);
                $idHojaRedir = (int)$stmtHRedir->fetchColumn();
            }

            // Auto-resolución de plan_edad_id si viene omitido pero existe asignación al espécimen
            if (empty($datos['plan_edad_id']) && !empty($datos['especimen_id'])) {
                $dbTmp = Conexion::obtenerInstancia();
                $stmtPE = $dbTmp->prepare("SELECT plan_edad_id FROM plan_rotura_asignaciones WHERE especimen_id = :id LIMIT 1");
                $stmtPE->execute(['id' => (int)$datos['especimen_id']]);
                $peFound = (int)$stmtPE->fetchColumn();
                if ($peFound > 0) {
                    $datos['plan_edad_id'] = $peFound;
                }
            }

            $roturasModelo = new RoturasModelo();
            $resultado = $roturasModelo->registrarRotura($datos, $usuarioId);

            if (!empty($resultado['exito'])) {
                try {
                    $db = Conexion::obtenerInstancia();
                    $stmtEspM = $db->prepare("SELECT esp.codigo_lab, esp.muestra_id, rm.id_hoja FROM especimenes esp JOIN recepcion_muestras rm ON esp.muestra_id = rm.id WHERE esp.id = :id");
                    $stmtEspM->execute(['id' => (int)($datos['especimen_id'] ?? 0)]);
                    $espInfo = $stmtEspM->fetch(PDO::FETCH_ASSOC);
                    if ($espInfo) {
                        if ($idHojaRedir <= 0 && !empty($espInfo['id_hoja'])) {
                            $idHojaRedir = (int)$espInfo['id_hoja'];
                        }
                        $crudosSync = is_array($datos['datos_crudos'] ?? null) ? $datos['datos_crudos'] : [];
                        sincronizarRoturasConMatriz($db, $idHojaRedir, 0, [[
                            'codigo_lab' => $espInfo['codigo_lab'],
                            'muestra_id' => (int)$espInfo['muestra_id'],
                            'carga' => (float)($resultado['carga'] ?? ($datos['carga'] ?? 0)),
                            'area' => (float)($resultado['area'] ?? ($datos['area'] ?? 0)),
                            'diametro_1' => $datos['diametro_1'] ?? ($crudosSync['diametro_1'] ?? null),
                            'diametro_2' => $datos['diametro_2'] ?? ($crudosSync['diametro_2'] ?? null),
                            'largo_1' => $datos['largo_1'] ?? ($crudosSync['largo_1'] ?? null),
                            'largo_2' => $datos['largo_2'] ?? ($crudosSync['largo_2'] ?? null),
                            'masa_kg' => $datos['masa_kg'] ?? ($crudosSync['masa_kg'] ?? null),
                            'peso_volumetrico' => $resultado['peso_volumetrico'] ?? null,
                            'edad_calculada' => $resultado['edad_calculada'] ?? null,
                            'esfuerzo_psi' => (float)($resultado['esfuerzo_psi'] ?? 0),
                            'esfuerzo_kgcm2' => (float)($resultado['esfuerzo_kgcm2'] ?? 0),
                            'fecha_real' => $datos['fecha_real'] ?? date('Y-m-d'),
                            'edad_dias' => (int)($resultado['edad_calculada'] ?? ($datos['edad_dias'] ?? 0))
                        ]]);
                        if ($idHojaRedir > 0) {
                            $roturasModelo->actualizarEtapaFlujoHojaPorRoturas($idHojaRedir);
                        }
                    }
                } catch (\Throwable $e) {
                    error_log('Error sincronizando rotura individual con matriz: ' . $e->getMessage());
                }
            }

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

            if ($idHojaRedir > 0) {
                $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $idHojaRedir);
            } else {
                $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-muestra?muestra_id=' . $muestraId);
            }
        }
    }

    /**
     * Registra los rompimientos periódicos de múltiples probetas del lote juntas en una sola operación (Batch / Captura en Línea).
     */
    public function registrarRoturasLote(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=bandeja');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $esAjax = $peticion->esAjax()
            || !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            || (($datos['formato_respuesta'] ?? '') === 'json');
        $idHoja = (int)($datos['id_hoja'] ?? 0);
        $redirectUrl = $idHoja > 0
            ? '/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $idHoja
            : '/Cycsa/publico/laboratorio?tab=bandeja';

        // Seguridad (Sección 10 Riesgo #4): eliminar parámetros de cálculo inyectados desde el formulario
        foreach (['divisor_kgcm2', 'usar_prensa', 'usar_factor_ld', 'redondear_psi_10', 'opciones_calculo', '_opciones_internas_test'] as $campoProtegido) {
            unset($datos[$campoProtegido], $_POST[$campoProtegido]);
        }

        if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido.'], 403);
                return;
            }
            $_SESSION['error'] = 'Token CSRF inválido.';
            $respuesta->redirigir($redirectUrl);
            return;
        }

        $usuarioId = $this->obtenerUsuarioIdAutenticado($peticion, $respuesta);
        if ($usuarioId === null) {
            return;
        }

        $roturasPayload = $datos['roturas'] ?? [];
        if (!is_array($roturasPayload) || empty($roturasPayload)) {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'No se recibieron datos de rompimientos para procesar.'], 422);
                return;
            }
            $_SESSION['error'] = 'No se enviaron probetas para registrar rompimientos.';
            $respuesta->redirigir($redirectUrl);
            return;
        }

        $db = Conexion::obtenerInstancia();
        $roturasModelo = new RoturasModelo();
        $exitosas = 0;
        $errores = [];
        $roturasSincronizar = [];

        foreach ($roturasPayload as $rItem) {
            if (!is_array($rItem)) {
                continue;
            }
            foreach (['divisor_kgcm2', 'usar_prensa', 'usar_factor_ld', 'redondear_psi_10', 'opciones_calculo', '_opciones_internas_test'] as $campoProtegido) {
                unset($rItem[$campoProtegido]);
                if (isset($rItem['datos_crudos']) && is_array($rItem['datos_crudos'])) {
                    unset($rItem['datos_crudos'][$campoProtegido]);
                }
            }

            $especimenId = (int)($rItem['especimen_id'] ?? 0);
            $planEdadId = (int)($rItem['plan_edad_id'] ?? 0);
            $carga = (float)($rItem['carga'] ?? 0);

            if ($especimenId > 0 && $planEdadId <= 0) {
                $stmtPE = $db->prepare("SELECT plan_edad_id FROM plan_rotura_asignaciones WHERE especimen_id = :id LIMIT 1");
                $stmtPE->execute(['id' => $especimenId]);
                $planEdadId = (int)$stmtPE->fetchColumn();
                $rItem['plan_edad_id'] = $planEdadId;
            }

            if ($especimenId <= 0 || $planEdadId <= 0 || $carga <= 0) {
                continue; // Omitir probetas sin carga ingresada
            }

            $res = $roturasModelo->registrarRotura($rItem, $usuarioId);
            if (!empty($res['exito'])) {
                $exitosas++;
                $stmtCod = $db->prepare("SELECT codigo_lab, muestra_id FROM especimenes WHERE id = :id");
                $stmtCod->execute(['id' => $especimenId]);
                $espRow = $stmtCod->fetch(PDO::FETCH_ASSOC);

                $crudosItem = is_array($rItem['datos_crudos'] ?? null) ? $rItem['datos_crudos'] : [];
                $roturasSincronizar[] = [
                    'codigo_lab' => $espRow['codigo_lab'] ?? '',
                    'muestra_id' => (int)($espRow['muestra_id'] ?? 0),
                    'carga' => (float)($res['carga'] ?? $carga),
                    'area' => (float)($res['area'] ?? ($rItem['area'] ?? 0)),
                    'diametro_1' => $rItem['diametro_1'] ?? ($crudosItem['diametro_1'] ?? null),
                    'diametro_2' => $rItem['diametro_2'] ?? ($crudosItem['diametro_2'] ?? null),
                    'largo_1' => $rItem['largo_1'] ?? ($crudosItem['largo_1'] ?? null),
                    'largo_2' => $rItem['largo_2'] ?? ($crudosItem['largo_2'] ?? null),
                    'masa_kg' => $rItem['masa_kg'] ?? ($crudosItem['masa_kg'] ?? null),
                    'peso_volumetrico' => $res['peso_volumetrico'] ?? null,
                    'edad_calculada' => $res['edad_calculada'] ?? null,
                    'esfuerzo_psi' => (float)($res['esfuerzo_psi'] ?? 0),
                    'esfuerzo_kgcm2' => (float)($res['esfuerzo_kgcm2'] ?? 0),
                    'fecha_real' => $rItem['fecha_real'] ?? date('Y-m-d H:i:s'),
                    'edad_dias' => (int)($res['edad_calculada'] ?? ($rItem['edad_dias'] ?? 0))
                ];
            } else {
                $errores[] = "Probeta #{$especimenId}: " . ($res['mensaje'] ?? 'Error desconocido');
            }
        }

        // Sincronizar todos los rompimientos registrados con la matriz técnica y actualizar etapa_flujo de la hoja
        if (!empty($roturasSincronizar)) {
            try {
                sincronizarRoturasConMatriz($db, $idHoja, 0, $roturasSincronizar);
                if ($idHoja > 0) {
                    $roturasModelo->actualizarEtapaFlujoHojaPorRoturas($idHoja);
                }
            } catch (\Throwable $e) {
                error_log('Error en sincronizarRoturasConMatriz en registrarRoturasLote: ' . $e->getMessage());
            }
        }

        $mensajeFinal = "Se registraron con éxito los rompimientos de {$exitosas} probeta(s) del lote y se sincronizaron con la Matriz Técnica.";
        if (!empty($errores)) {
            $mensajeFinal .= " (" . count($errores) . " probeta(s) con observaciones).";
        }

        if ($esAjax) {
            $respuesta->enviarJson([
                'status' => $exitosas > 0 ? 'success' : 'error',
                'message' => $exitosas > 0 ? $mensajeFinal : implode('; ', $errores),
                'exitosas' => $exitosas,
                'errores' => $errores
            ]);
            return;
        }

        if ($exitosas > 0) {
            $_SESSION['exito'] = $mensajeFinal;
        } else {
            $_SESSION['error'] = !empty($errores) ? implode('; ', $errores) : 'No se pudo registrar ninguna rotura. Verifique que la carga ingresada sea mayor a 0.';
        }

        $respuesta->redirigir($redirectUrl);
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
            $area = (float)($datos['area_in2'] ?? 0);
            if ($area <= 0 && !empty($datos['diametro_1']) && !empty($datos['diametro_2'])) {
                $area = round(ConfigCalculoLabServicio::calcularAreaCilindroIn2((float)$datos['diametro_1'], (float)$datos['diametro_2']), 4);
            }
            if ($area <= 0) {
                $_SESSION['error'] = 'Datos de ensayo incompletos: ingrese D1 y D2 (o el área medida) y una carga válida.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-muestra?id_lote=' . $idLote);
                return;
            }

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
            $idHojaRedir = (int)($datos['id_hoja'] ?? 0);
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

            if ($idHojaRedir > 0) {
                $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $idHojaRedir);
                return;
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
            $idHojaRedir = (int)($datos['id_hoja'] ?? 0);
            $nuevoEstado = trim($datos['nuevo_estado'] ?? '');
            $motivo = trim($datos['motivo_devolucion'] ?? '');
            $obsPendRaw = $datos['observaciones_pendientes'] ?? null;
            $obsPendientes = is_array($obsPendRaw)
                ? implode('; ', array_filter(array_map('trim', $obsPendRaw)))
                : ($obsPendRaw !== null ? trim((string)$obsPendRaw) : null);

            // Seguridad (Sección 10 Riesgo #1): derivar firmantes estrictamente del usuario autenticado en BD, NUNCA de $_POST['aprobado_nombre']
            unset($_POST['aprobado_nombre'], $_POST['revisado_nombre'], $_POST['revisado_por'], $datos['aprobado_nombre'], $datos['revisado_nombre'], $datos['revisado_por']);
            $nombreUsuarioReal = '';
            try {
                $dbUsr = Conexion::obtenerInstancia();
                $stmtUsr = $dbUsr->prepare("SELECT nombre FROM usuarios WHERE id = :id LIMIT 1");
                $stmtUsr->execute(['id' => $usuarioId]);
                $nomDb = $stmtUsr->fetchColumn();
                if ($nomDb !== false && trim((string)$nomDb) !== '') {
                    $nombreUsuarioReal = trim((string)$nomDb);
                }
            } catch (\Throwable $e) {
                // fallback a sesión
            }
            if ($nombreUsuarioReal === '') {
                $nombreUsuarioReal = trim((string)($_SESSION['usuario_nombre'] ?? ''));
            }

            $extraRevision = [
                'revisado_por' => $usuarioId,
                'revisado_nombre' => $nombreUsuarioReal,
                'aprobado_nombre' => $nombreUsuarioReal,
            ];
            if ($obsPendientes !== null) {
                $extraRevision['observaciones_pendientes'] = $obsPendientes;
            }

            $roturasModelo = new RoturasModelo();
            $resultado = $roturasModelo->procesarRevisionInforme($informeId, $nuevoEstado, $motivo, $usuarioId, $rolUsuario, $extraRevision);

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

            if ($idHojaRedir > 0) {
                $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $idHojaRedir);
                return;
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
            $idHojaRedir = (int)($datos['id_hoja'] ?? 0);

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

            if ($idHojaRedir > 0) {
                $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $idHojaRedir);
                return;
            }
            $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-muestra?muestra_id=' . $muestraId);
        }
    }

    /**
     * Emite los Informes Consolidados para todas las muestras del lote que ya tengan
     * todos sus parciales aprobados por Calidad.
     */
    public function generarInformeConsolidadoLote(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        $rolUsuario = (int)($_SESSION['usuario_rol'] ?? 0);
        $usuarioId = $this->obtenerUsuarioIdAutenticado($peticion, $respuesta);
        if ($usuarioId === null) {
            return;
        }

        if ($rolUsuario !== 1 && $rolUsuario !== 3) {
            $_SESSION['error'] = 'Acceso denegado: Solo Calidad o Administrador puede generar informes consolidados.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $idHoja = (int)($datos['id_hoja'] ?? 0);
            if ($idHoja <= 0) {
                $_SESSION['error'] = 'Identificador de lote no válido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $db = Conexion::obtenerInstancia();
            $stmtMuestras = $db->prepare("SELECT id, codigo_muestra AS codigo_lab FROM recepcion_muestras WHERE id_hoja = :id_hoja ORDER BY orden_en_lote ASC, id ASC");
            $stmtMuestras->execute(['id_hoja' => $idHoja]);
            $muestras = $stmtMuestras->fetchAll(PDO::FETCH_ASSOC);

            if (empty($muestras)) {
                $_SESSION['error'] = 'No se encontraron muestras en el lote.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $idHoja);
                return;
            }

            $roturasModelo = new RoturasModelo();
            $generados = 0;
            $errores = [];

            foreach ($muestras as $m) {
                $mId = (int)$m['id'];
                $stmtCons = $db->prepare("SELECT id FROM informes_rotura WHERE muestra_id = :mid AND tipo = 'consolidado' AND estado = 'aprobado' LIMIT 1");
                $stmtCons->execute(['mid' => $mId]);
                if ($stmtCons->fetchColumn()) {
                    continue; // Ya tiene consolidado aprobado
                }

                $res = $roturasModelo->generarInformeConsolidado($mId, $usuarioId, $rolUsuario);
                if ($res['exito']) {
                    $generados++;
                } else {
                    $errores[] = "{$m['codigo_lab']}: {$res['mensaje']}";
                }
            }

            if ($generados > 0) {
                $_SESSION['exito'] = "Se emitieron {$generados} informe(s) consolidado(s) exitosamente para el lote.";
                if (!empty($errores)) {
                    $_SESSION['error'] = 'Algunas muestras no pudieron consolidarse: ' . implode(' | ', $errores);
                }
            } else {
                $_SESSION['error'] = !empty($errores)
                    ? implode(' | ', $errores)
                    : 'No hubo muestras elegibles para emisión consolidada en este lote (deben estar todas ensayadas y con parciales aprobados).';
            }

            $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $idHoja);
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
            $idHojaRedir = (int)($datos['id_hoja'] ?? 0);
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

            if ($idHojaRedir > 0) {
                $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $idHojaRedir);
                return;
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
            $resultadoIngreso = $modelo->ingresarMuestrasHojaALaboratorio(
                $idOS,
                $idHoja,
                (int)($_SESSION['usuario_id'] ?? 1),
                true,
                $datos
            );

            if (!empty($resultadoIngreso['ok'])) {
                $hoja = $resultadoIngreso['hoja'] ?? null;
                $numHojaTexto = !empty($hoja['numero_registro']) ? " [Reg: {$hoja['numero_registro']}]" : "";
                $_SESSION['exito'] = "Muestras de la Hoja RT-FM-13{$numHojaTexto} ingresadas a Laboratorio con sus probetas agrupadas y programadas para ensayo.";

                $actualHojaId = (int)($resultadoIngreso['id_hoja'] ?? $idHoja);
                if ($actualHojaId > 0) {
                    $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $actualHojaId);
                    return;
                }
            } else {
                $_SESSION['error'] = $resultadoIngreso['mensaje'] ?? 'Ocurrió un error al registrar el ingreso de muestras al laboratorio.';
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
                registrarEventoOS('laboratorio', 'cambiar_estado', "Estado de Solicitud/OS #{$idOS} actualizado a {$nuevoEstado}" . (!empty($motivo) ? " con motivo: {$motivo}" : ""), $idOS, [
                    'datos' => ['estado' => $nuevoEstado],
                ]);
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

        $db = Conexion::obtenerInstancia();
        $idOS = (int)($_GET['id_os'] ?? 0);
        $idHoja = (int)($_GET['id_hoja'] ?? 0);
        $muestraId = (int)($_GET['muestra_id'] ?? 0);

        if ($muestraId > 0 && ($idOS <= 0 || $idHoja <= 0)) {
            $stmtRM = $db->prepare("SELECT id_os, id_hoja FROM recepcion_muestras WHERE id = :mid LIMIT 1");
            $stmtRM->execute(['mid' => $muestraId]);
            $rowRM = $stmtRM->fetch(PDO::FETCH_ASSOC);
            if ($rowRM) {
                if ($idOS <= 0 && !empty($rowRM['id_os'])) {
                    $idOS = (int)$rowRM['id_os'];
                }
                if ($idHoja <= 0 && !empty($rowRM['id_hoja'])) {
                    $idHoja = (int)$rowRM['id_hoja'];
                }
            }
        }

        if ($idHoja > 0 && $idOS <= 0) {
            $stmtHs = $db->prepare("SELECT id_os FROM hojas_solicitud WHERE id = :hid LIMIT 1");
            $stmtHs->execute(['hid' => $idHoja]);
            $idOS = (int)$stmtHs->fetchColumn();
        }

        if ($idOS <= 0) {
            $_SESSION['error'] = 'No se encontró la hoja o muestra para visualizar CYCSA-RT-FM-60.';
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

        $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS, $idHoja > 0 ? $idHoja : null);
        $ensayos = $modelo->filtrarDetallesPermitidos($idOS, $modelo->obtenerDetallesCotizacion((int)$os['id_cotizacion']));

        if ($hoja && !empty($hoja['id_detalle_cotizacion'])) {
            $idDetHoja = (int)$hoja['id_detalle_cotizacion'];
            $ensayosHoja = array_values(array_filter($ensayos, fn($e) => (int)($e['id'] ?? 0) === $idDetHoja));
            if (!empty($ensayosHoja)) {
                $ensayos = $ensayosHoja;
            } else {
                $stmtD = $db->prepare("SELECT cd.*, p.codigo_servicio, p.norma_astm, p.nombre_comercial, fe.archivo_markdown, fe.nombre AS formato_nombre 
                                       FROM cotizacion_detalles cd
                                       LEFT JOIN productos p ON cd.id_producto = p.id
                                       LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                                       WHERE cd.id = :id");
                $stmtD->execute(['id' => $idDetHoja]);
                $detHojaPrint = $stmtD->fetch(PDO::FETCH_ASSOC);
                if ($detHojaPrint) {
                    $ensayos = [$detHojaPrint];
                }
            }
        }

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
        $codigoFormato = strtoupper(trim((string)($datos['codigo_formato'] ?? '')));
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

    /**
     * Obtiene los datos técnicos capturados en la matriz de Operaciones para una muestra específica.
     */
    private function obtenerDatosMatrizPorMuestra(PDO $db, array $muestra): array {
        $codigoLab = trim((string)($muestra['codigo_lab'] ?? ($muestra['codigo_muestra'] ?? '')));
        $idHoja = (int)($muestra['id_hoja'] ?? 0);
        $idOs = (int)($muestra['id_os'] ?? 0);

        if (!empty($muestra['muestra_id']) && ($idHoja <= 0 || $idOs <= 0 || $codigoLab === '')) {
            $stmtRm = $db->prepare("SELECT id_os, id_hoja, codigo_muestra FROM recepcion_muestras WHERE id = :id");
            $stmtRm->execute(['id' => (int)$muestra['muestra_id']]);
            $rmInfo = $stmtRm->fetch(PDO::FETCH_ASSOC);
            if ($rmInfo) {
                $idOs = $idOs ?: (int)($rmInfo['id_os'] ?? 0);
                $idHoja = $idHoja ?: (int)($rmInfo['id_hoja'] ?? 0);
                if ($codigoLab === '') {
                    $codigoLab = trim((string)($rmInfo['codigo_muestra'] ?? ''));
                }
            }
        }

        $idDetalle = 0;
        if ($idHoja > 0) {
            $stmtH = $db->prepare("SELECT id_os, id_detalle_cotizacion FROM hojas_solicitud WHERE id = :id");
            $stmtH->execute(['id' => $idHoja]);
            $hRow = $stmtH->fetch(PDO::FETCH_ASSOC);
            if ($hRow) {
                $idOs = $idOs ?: (int)($hRow['id_os'] ?? 0);
                $idDetalle = (int)($hRow['id_detalle_cotizacion'] ?? 0);
            }
        }
        if ($idDetalle <= 0 && $idHoja > 0 && $codigoLab !== '') {
            try {
                $codBaseM = preg_replace('/-\d+$/', '', $codigoLab);
                $stmtHsdM = $db->prepare("
                    SELECT id_detalle_cotizacion
                    FROM hojas_solicitud_detalles
                    WHERE id_hoja_solicitud = :hid
                      AND (codigo_muestra = :cod OR codigo_muestra = :cod_base)
                      AND id_detalle_cotizacion IS NOT NULL AND id_detalle_cotizacion > 0
                    ORDER BY orden ASC, id ASC LIMIT 1
                ");
                $stmtHsdM->execute(['hid' => $idHoja, 'cod' => $codigoLab, 'cod_base' => $codBaseM]);
                $idDetalle = (int)$stmtHsdM->fetchColumn();
            } catch (\Throwable $e) {
            }
        }
        if ($idDetalle <= 0 && $idOs > 0) {
            $stmtCd = $db->prepare("
                SELECT cd.id FROM cotizacion_detalles cd
                INNER JOIN ordenes_servicio os ON os.id_cotizacion = cd.id_cotizacion
                WHERE os.id = :id_os
                ORDER BY cd.id ASC LIMIT 1
            ");
            $stmtCd->execute(['id_os' => $idOs]);
            $idDetalle = (int)$stmtCd->fetchColumn();
        }

        $detallesIds = [];
        if ($idDetalle > 0) {
            $detallesIds[$idDetalle] = true;
        }
        if ($idHoja > 0) {
            try {
                $stmtHsdD = $db->prepare("SELECT DISTINCT id_detalle_cotizacion FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = :hid AND id_detalle_cotizacion IS NOT NULL AND id_detalle_cotizacion > 0");
                $stmtHsdD->execute(['hid' => $idHoja]);
                foreach ($stmtHsdD->fetchAll(PDO::FETCH_COLUMN) as $didH) {
                    if ((int)$didH > 0) $detallesIds[(int)$didH] = true;
                }
            } catch (\Throwable $e) {
            }
        }

        $filasMatriz = [];
        $capturaTecnicaMatriz = [];
        foreach (array_keys($detallesIds) as $didBusq) {
            $stmtDet = $db->prepare("SELECT resultados_json FROM cotizacion_detalles WHERE id = :id");
            $stmtDet->execute(['id' => (int)$didBusq]);
            $rawJson = $stmtDet->fetchColumn();
            if (!empty($rawJson)) {
                $decoded = json_decode((string)$rawJson, true);
                if (is_array($decoded)) {
                    if (!empty($decoded['filas']) && is_array($decoded['filas'])) {
                        $filasMatriz = array_merge($filasMatriz, $decoded['filas']);
                    }
                    if (!empty($decoded['captura_tecnica']) && is_array($decoded['captura_tecnica'])) {
                        $capturaTecnicaMatriz = array_merge($capturaTecnicaMatriz, $decoded['captura_tecnica']);
                    }
                }
            }
        }

        return obtenerDatosMuestraDesdeMatriz($filasMatriz, $codigoLab, $capturaTecnicaMatriz);
    }

    /**
     * Vista de trabajo por Lote (Hoja RT-FM-13): muestra todas las N muestras de la hoja
     * y sus réplicas CR en la misma secuencia que la matriz, permitiendo confirmar el
     * plan de roturas en lote y precargando Edad, Temperatura y Área desde la matriz.
     */
    public function detalleLote(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $idHoja = (int)($_GET['id_hoja'] ?? 0);
        $muestraIdParam = (int)($_GET['muestra_id'] ?? 0);

        $db = Conexion::obtenerInstancia();
        if ($idHoja <= 0 && $muestraIdParam > 0) {
            $stmtM = $db->prepare("SELECT id_hoja FROM recepcion_muestras WHERE id = :id");
            $stmtM->execute(['id' => $muestraIdParam]);
            $idHoja = (int)$stmtM->fetchColumn();
        }

        if ($idHoja <= 0) {
            $_SESSION['error'] = 'Identificador de lote (Hoja RT-FM-13) no válido.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=bandeja');
            return;
        }

        $stmtHoja = $db->prepare("SELECT * FROM hojas_solicitud WHERE id = :id");
        $stmtHoja->execute(['id' => $idHoja]);
        $hoja = $stmtHoja->fetch(PDO::FETCH_ASSOC) ?: [];

        $idOs = (int)($hoja['id_os'] ?? 0);
        $idDetalle = (int)($hoja['id_detalle_cotizacion'] ?? 0);

        if ($idOs <= 0) {
            $stmtRmDet = $db->prepare("SELECT id_os FROM recepcion_muestras WHERE id_hoja = :id_hoja ORDER BY id ASC LIMIT 1");
            $stmtRmDet->execute(['id_hoja' => $idHoja]);
            $idOs = (int)$stmtRmDet->fetchColumn();
        }

        $detalleCotizacion = [];
        $filasMatriz = [];
        $capturaTecnicaMatriz = [];
        $detallesHojaIds = [];
        if ($idDetalle > 0) {
            $detallesHojaIds[$idDetalle] = true;
        }
        try {
            $stmtHsdMulti = $db->prepare("
                SELECT id_detalle_cotizacion
                FROM hojas_solicitud_detalles
                WHERE id_hoja_solicitud = :hid
                  AND id_detalle_cotizacion IS NOT NULL AND id_detalle_cotizacion > 0
                GROUP BY id_detalle_cotizacion
                ORDER BY MIN(orden) ASC, MIN(id) ASC
            ");
            $stmtHsdMulti->execute(['hid' => $idHoja]);
            foreach ($stmtHsdMulti->fetchAll(PDO::FETCH_COLUMN) as $didMulti) {
                if ((int)$didMulti > 0) $detallesHojaIds[(int)$didMulti] = true;
            }
        } catch (\Throwable $e) {
        }
        if (empty($detallesHojaIds) && $idOs > 0) {
            try {
                $stmtOsDets = $db->prepare("SELECT cd.id FROM cotizacion_detalles cd JOIN ordenes_servicio os ON os.id_cotizacion = cd.id_cotizacion WHERE os.id = :id_os ORDER BY cd.id ASC");
                $stmtOsDets->execute(['id_os' => $idOs]);
                foreach ($stmtOsDets->fetchAll(PDO::FETCH_COLUMN) as $didOs) {
                    if ((int)$didOs > 0) $detallesHojaIds[(int)$didOs] = true;
                }
            } catch (\Throwable $e) {
            }
        }

        foreach (array_keys($detallesHojaIds) as $didLote) {
            $stmtDet = $db->prepare("
                SELECT cd.*, p.nombre_comercial, p.norma_astm, p.codigo_servicio
                FROM cotizacion_detalles cd
                LEFT JOIN productos p ON cd.id_producto = p.id
                WHERE cd.id = :id
            ");
            $stmtDet->execute(['id' => (int)$didLote]);
            $detRow = $stmtDet->fetch(PDO::FETCH_ASSOC) ?: [];
            if (!empty($detRow)) {
                $actualEsCompactacion = !empty($detalleCotizacion)
                    && function_exists('esProductoCompactacionCampo')
                    && esProductoCompactacionCampo($detalleCotizacion);
                $nuevoEsCompactacion = function_exists('esProductoCompactacionCampo')
                    && esProductoCompactacionCampo($detRow);
                if (empty($detalleCotizacion) || ($actualEsCompactacion && !$nuevoEsCompactacion)) {
                    $detalleCotizacion = $detRow;
                    $idDetalle = (int)$didLote;
                }
            }
            if (!empty($detRow['resultados_json'])) {
                $resDecoded = json_decode((string)$detRow['resultados_json'], true);
                if (is_array($resDecoded)) {
                    if (!empty($resDecoded['filas']) && is_array($resDecoded['filas'])) {
                        $filasMatriz = array_merge($filasMatriz, $resDecoded['filas']);
                    }
                    if (!empty($resDecoded['captura_tecnica']) && is_array($resDecoded['captura_tecnica'])) {
                        $capturaTecnicaMatriz = array_merge($capturaTecnicaMatriz, $resDecoded['captura_tecnica']);
                    }
                }
            }
        }

        // 3. En GET: lectura limpia sin escrituras a BD
        $stmtLote = $db->prepare("
            SELECT * FROM v_lab_muestras
            WHERE id_hoja = :id_hoja
            ORDER BY orden_en_lote ASC, muestra_id ASC
        ");
        $stmtLote->execute(['id_hoja' => $idHoja]);
        $muestrasBase = $stmtLote->fetchAll(PDO::FETCH_ASSOC);

        if (empty($muestrasBase) && !empty($hoja)) {
            $etapaHojaActual = (string)($hoja['etapa_flujo'] ?? 'recepcion_borrador');
            $recCompActual = !empty($hoja['recepcion_completa']) || in_array($etapaHojaActual, ['recepcion_completa', 'en_ejecucion', 'tecnico_completo', 'matriz_en_revision', 'informe'], true);
            if ($recCompActual) {
                $opModSync = new OperacionModelo();
                $opModSync->ingresarMuestrasHojaALaboratorio($idHoja);
                $stmtLote->execute(['id_hoja' => $idHoja]);
                $muestrasBase = $stmtLote->fetchAll(PDO::FETCH_ASSOC);
            }
        }

        if (empty($muestrasBase)) {
            $etapaHojaActual = (string)($hoja['etapa_flujo'] ?? 'recepcion_borrador');
            $recCompActual = !empty($hoja['recepcion_completa']) || in_array($etapaHojaActual, ['recepcion_completa', 'en_ejecucion', 'tecnico_completo', 'matriz_en_revision', 'informe'], true);
            if (!$recCompActual && $idOs > 0) {
                $_SESSION['error'] = 'La recepción de la hoja aún está en borrador. Complete los datos de recepción y marque "Recepción Completa" para habilitar el lote en Laboratorio.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . $idOs . '&id_hoja=' . $idHoja);
                return;
            }
            if ($idDetalle > 0) {
                $respuesta->redirigir('/Cycsa/publico/operaciones/captura-matriz?id_detalle=' . $idDetalle);
                return;
            }
            $_SESSION['error'] = 'No se encontraron muestras recepcionadas en laboratorio para este lote.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=bandeja');
            return;
        }

        $roturasModelo = new RoturasModelo();
        $muestrasDetalladas = [];

        foreach ($muestrasBase as $mRow) {
            $mId = (int)$mRow['muestra_id'];
            $detAnon = $roturasModelo->obtenerDetalleMuestraAnonima($mId);
            $datosMat = obtenerDatosMuestraDesdeMatriz($filasMatriz, (string)$mRow['codigo_lab'], $capturaTecnicaMatriz);

            $muestrasDetalladas[] = [
                'muestra' => $detAnon['muestra'] ?? $mRow,
                'especimenes' => $detAnon['especimenes'] ?? [],
                'plan' => $detAnon['plan'] ?? null,
                'edades' => $detAnon['edades'] ?? [],
                'informes' => $detAnon['informes'] ?? [],
                'puede_consolidar' => $detAnon['puede_consolidar'] ?? false,
                'datos_matriz' => $datosMat
            ];
        }

        // Obtener la lista unificada de todos los informes técnicos emitidos para este lote
        $stmtInfLote = $db->prepare("
            SELECT ir.*, u.nombre AS aprobado_por_nombre, rm.codigo_muestra AS codigo_lab
            FROM informes_rotura ir
            JOIN recepcion_muestras rm ON ir.muestra_id = rm.id
            LEFT JOIN usuarios u ON ir.aprobado_por = u.id
            WHERE rm.id_hoja = :id_hoja
            ORDER BY ir.id DESC
        ");
        $stmtInfLote->execute(['id_hoja' => $idHoja]);
        $informesLote = $stmtInfLote->fetchAll(PDO::FETCH_ASSOC);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $rolUsuario = (int)($_SESSION['usuario_rol'] ?? 0);
        $puedeReidentificar = in_array($rolUsuario, [1, 2, 3], true);
        $numRegTexto = !empty($hoja['numero_registro']) ? $hoja['numero_registro'] : ('#' . $idHoja);

        $this->renderizar('operaciones/vistas/laboratorio_lote', [
            'titulo' => 'Lote de Laboratorio - Hoja Reg. ' . $numRegTexto,
            'id_hoja' => $idHoja,
            'hoja' => $hoja,
            'detalleCotizacion' => $detalleCotizacion,
            'muestrasLote' => $muestrasDetalladas,
            'informesLote' => $informesLote,
            'rolUsuario' => $rolUsuario,
            'puedeReidentificar' => $puedeReidentificar,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Guarda / confirma el Plan de Roturas (días de rompimiento) en lote para todas las
     * muestras asociadas a una Hoja RT-FM-13 (`id_hoja`).
     */
    public function guardarPlanRoturaLote(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=bandeja');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $esAjax = $peticion->esAjax();
        $idHoja = (int)($datos['id_hoja'] ?? 0);
        $redirectUrl = $idHoja > 0
            ? '/Cycsa/publico/laboratorio/detalle-lote?id_hoja=' . $idHoja
            : '/Cycsa/publico/laboratorio?tab=bandeja';

        if (!$this->verificarTokenCsrf($peticion, $respuesta, $datos, $redirectUrl)) {
            return;
        }

        $usuarioId = $this->obtenerUsuarioIdAutenticado($peticion, $respuesta);
        if ($usuarioId === null) {
            return;
        }

        if ($idHoja <= 0) {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Identificador de lote (id_hoja) inválido.'], 422);
                return;
            }
            $_SESSION['error'] = 'Identificador de lote inválido.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=bandeja');
            return;
        }

        $plantillaVersionId = !empty($datos['plantilla_version_id']) ? (int)$datos['plantilla_version_id'] : null;
        $edadesArr = $datos['edad_dias'] ?? [];
        $cantArr = $datos['cantidad'] ?? [];
        $edadesConfig = [];

        if (is_array($edadesArr)) {
            for ($i = 0; $i < count($edadesArr); $i++) {
                $d = (int)($edadesArr[$i] ?? 0);
                $c = (int)($cantArr[$i] ?? 1);
                if ($d > 0 && $c > 0) {
                    $edadesConfig[] = ['edad_dias' => $d, 'cantidad' => $c];
                }
            }
        }

        if (empty($edadesConfig)) {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Debe especificar al menos una edad de rompimiento válida para el lote.'], 422);
                return;
            }
            $_SESSION['error'] = 'Debe especificar al menos una edad de rompimiento válida para el lote.';
            $respuesta->redirigir($redirectUrl);
            return;
        }

        $db = Conexion::obtenerInstancia();
        $stmtMuestras = $db->prepare("
            SELECT id FROM recepcion_muestras
            WHERE id_hoja = :id_hoja
            ORDER BY orden_en_lote ASC, id ASC
        ");
        $stmtMuestras->execute(['id_hoja' => $idHoja]);
        $muestraIds = $stmtMuestras->fetchAll(PDO::FETCH_COLUMN);

        if (empty($muestraIds)) {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'No se encontraron muestras en el lote para aplicar el plan.'], 404);
                return;
            }
            $_SESSION['error'] = 'No se encontraron muestras en el lote para aplicar el plan.';
            $respuesta->redirigir($redirectUrl);
            return;
        }

        $roturasModelo = new RoturasModelo();
        $actualizadas = 0;
        $omitidas = 0;
        $ultimoError = '';

        foreach ($muestraIds as $mId) {
            $res = $roturasModelo->guardarPlanRotura((int)$mId, $plantillaVersionId, $edadesConfig, $usuarioId);
            if (!empty($res['exito'])) {
                $actualizadas++;
            } else {
                $omitidas++;
                $ultimoError = $res['mensaje'] ?? '';
            }
        }

        $mensajeFinal = "Plan de roturas aplicado al lote exitosamente ({$actualizadas} muestra(s) programada(s)" . ($omitidas > 0 ? ", {$omitidas} omitida(s) con roturas previas" : "") . ").";

        if ($esAjax) {
            $respuesta->enviarJson([
                'status' => $actualizadas > 0 ? 'success' : 'error',
                'message' => $actualizadas > 0 ? $mensajeFinal : ($ultimoError ?: 'No se pudo actualizar ninguna muestra del lote.'),
                'actualizadas' => $actualizadas,
                'omitidas' => $omitidas
            ]);
            return;
        }

        if ($actualizadas > 0) {
            $_SESSION['exito'] = $mensajeFinal;
        } else {
            $_SESSION['error'] = $ultimoError ?: 'No se pudo actualizar el plan de roturas del lote.';
        }
        $respuesta->redirigir($redirectUrl);
    }

    /**
     * Visualiza e imprime el Informe Oficial de Ensayo (RT-FM-22) para Laboratorio (ISO/IEC 17025).
     * Permite acceder por ID de informe de rotura (?id=X), por ID de Hoja de Solicitud (?id_hoja=X)
     * o por ID de partida (?id_detalle=X) sin requerir validación comercial de pagos.
     */
    public function verInforme(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $db = Conexion::obtenerInstancia();
        $informeId = (int)($_GET['id'] ?? 0);
        $idHoja = (int)($_GET['id_hoja'] ?? 0);
        $idDetalle = (int)($_GET['id_detalle'] ?? 0);
        $muestraId = (int)($_GET['muestra_id'] ?? 0);
        $especimenIdFiltro = (int)($_GET['especimen_id'] ?? 0);
        $roturaIdFiltro = (int)($_GET['rotura_id'] ?? 0);
        $sinLogo = !empty($_GET['sin_logo']);

        $informeData = null;
        if ($informeId > 0) {
            $stmtInf = $db->prepare("
                SELECT ir.*, rm.id_hoja, rm.id_os, rm.codigo_muestra, rm.codigo_campo, hs.id_detalle_cotizacion,
                       COALESCE(NULLIF(ir.revisado_nombre, ''), ur.nombre) AS revisado_por_nombre,
                       COALESCE(NULLIF(ir.aprobado_nombre, ''), ua.nombre) AS aprobado_por_nombre
                FROM informes_rotura ir
                LEFT JOIN recepcion_muestras rm ON ir.muestra_id = rm.id
                LEFT JOIN hojas_solicitud hs ON rm.id_hoja = hs.id
                LEFT JOIN usuarios ur ON ir.revisado_por = ur.id
                LEFT JOIN usuarios ua ON ir.aprobado_por = ua.id
                WHERE ir.id = :id
            ");
            $stmtInf->execute(['id' => $informeId]);
            $informeData = $stmtInf->fetch(PDO::FETCH_ASSOC);

            if ($informeData) {
                if ($idHoja <= 0 && !empty($informeData['id_hoja'])) {
                    $idHoja = (int)$informeData['id_hoja'];
                }
                if ($idDetalle <= 0 && !empty($informeData['id_detalle_cotizacion'])) {
                    $idDetalle = (int)$informeData['id_detalle_cotizacion'];
                }
            }
        }

        if ($muestraId <= 0 && ($especimenIdFiltro > 0 || $roturaIdFiltro > 0)) {
            if ($roturaIdFiltro > 0) {
                $stmtRMF = $db->prepare("SELECT esp.muestra_id FROM roturas rot JOIN especimenes esp ON rot.especimen_id = esp.id WHERE rot.id = :rid LIMIT 1");
                $stmtRMF->execute(['rid' => $roturaIdFiltro]);
                $muestraId = (int)$stmtRMF->fetchColumn();
            } elseif ($especimenIdFiltro > 0) {
                $stmtRMF = $db->prepare("SELECT muestra_id FROM especimenes WHERE id = :eid LIMIT 1");
                $stmtRMF->execute(['eid' => $especimenIdFiltro]);
                $muestraId = (int)$stmtRMF->fetchColumn();
            }
        }

        if ($idHoja <= 0 && $muestraId > 0) {
            $stmtM = $db->prepare("
                SELECT rm.id_hoja, hs.id_detalle_cotizacion 
                FROM recepcion_muestras rm
                LEFT JOIN hojas_solicitud hs ON rm.id_hoja = hs.id
                WHERE rm.id = :id
            ");
            $stmtM->execute(['id' => $muestraId]);
            $mRow = $stmtM->fetch(PDO::FETCH_ASSOC);
            if ($mRow) {
                $idHoja = (int)($mRow['id_hoja'] ?? 0);
                if ($idDetalle <= 0) {
                    $idDetalle = (int)($mRow['id_detalle_cotizacion'] ?? 0);
                }
            }
        }

        if ($idHoja > 0 && $idDetalle <= 0) {
            $stmtH = $db->prepare("SELECT id_detalle_cotizacion, id_os FROM hojas_solicitud WHERE id = :id");
            $stmtH->execute(['id' => $idHoja]);
            $hRow = $stmtH->fetch(PDO::FETCH_ASSOC);
            if ($hRow) {
                $idDetalle = (int)($hRow['id_detalle_cotizacion'] ?? 0);
            }
        }

        if ($idDetalle <= 0) {
            $_SESSION['error'] = 'No se pudo resolver la partida o ensayo correspondiente al informe solicitado.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=bandeja');
            return;
        }

        $stmtDet = $db->prepare("
            SELECT cd.id, cd.descripcion_ensayo, cd.codigo_servicio, cd.norma_astm, cd.resultados_json, cd.cantidad, cd.procedimiento, cd.condiciones_muestra,
                   os.id AS id_os, os.codigo_os, os.tecnico_muestreo, os.requiere_muestreo, os.fecha_muestreo, os.hora_muestreo, os.fecha_emision AS os_fecha_emision, os.fecha_registro_campo, os.created_at AS os_created_at,
                   os.forma_pago, COALESCE(os.condicion_comercial, cot.condicion_comercial, 'contado') AS condicion_comercial,
                   cot.id AS id_cotizacion, cot.codigo AS cot_codigo, cot.condicion_pago, cot.nombre_proyecto, cot.direccion_proyecto, cot.atencion_a,
                   COALESCE(cli_inf.id, cli.id) AS id_cliente, 
                   COALESCE(cli_inf.nombre_razon_social, cli.nombre_razon_social) AS cliente_nombre, 
                   COALESCE(cli_inf.nombre_razon_social, cli.nombre_razon_social) AS cliente_informe_nombre,
                   cli_fact.nombre_razon_social AS cliente_factura_nombre,
                   COALESCE(cli_inf.direccion, cli.direccion) AS cliente_direccion, 
                   COALESCE(cli_inf.email, cli.email) AS cliente_email, 
                   COALESCE(cli_inf.telefono, cli.telefono) AS cliente_telefono,
                   p.formato_id, p.nombre_comercial, p.ensayo_servicio, p.tipo_muestra AS prod_tipo_muestra, p.procedimiento_muestreo AS prod_procedimiento, p.norma_astm AS prod_norma_astm,
                   fe.nombre AS formato_nombre, fe.archivo_markdown, fe.codigo_formato AS codigo_documento, fe.procedimientos AS formato_procedimiento,
                   hs.procedencia_punto_muestreo, hs.nombre_persona_entrega_muestra, hs.fecha_hora_toma_muestra, hs.fecha_hora_llegada_laboratorio, hs.naturaleza_muestra, hs.observaciones,
                   rm.fecha_recepcion,
                   pm.fecha_ida AS pm_fecha_ida, pm.lugar_muestreo AS pm_lugar_muestreo
            FROM cotizacion_detalles cd
            JOIN ordenes_servicio os ON cd.id_cotizacion = os.id_cotizacion
            JOIN cotizaciones cot ON os.id_cotizacion = cot.id
            JOIN clientes cli ON cot.id_cliente = cli.id
            LEFT JOIN clientes cli_inf ON cli_inf.id = COALESCE(os.id_cliente_informe, cot.id_cliente_informe, cot.id_cliente)
            LEFT JOIN clientes cli_fact ON cli_fact.id = COALESCE(os.id_cliente_factura, cot.id_cliente_factura, cot.id_cliente)
            LEFT JOIN productos p ON cd.id_producto = p.id
            LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
            LEFT JOIN hojas_solicitud hs ON hs.id_os = os.id
            LEFT JOIN (SELECT id_os, MIN(fecha_recepcion) AS fecha_recepcion FROM recepcion_muestras GROUP BY id_os) rm ON rm.id_os = os.id
            LEFT JOIN (SELECT id_orden_servicio, MIN(fecha_ida) AS fecha_ida, MIN(lugar_muestreo) AS lugar_muestreo FROM programacion_muestreo GROUP BY id_orden_servicio) pm ON pm.id_orden_servicio = os.id
            WHERE cd.id = :id
            LIMIT 1
        ");
        $stmtDet->execute(['id' => $idDetalle]);
        $detalle = $stmtDet->fetch(PDO::FETCH_ASSOC);

        if (!$detalle) {
            $_SESSION['error'] = 'Partida o ensayo no encontrado.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=bandeja');
            return;
        }

        $detalle['id'] = (int)$idDetalle;
        $detalle['id_detalle'] = (int)$idDetalle;

        $hoja = null;
        if ($idHoja > 0) {
            $stmtHData = $db->prepare("SELECT * FROM hojas_solicitud WHERE id = :id");
            $stmtHData->execute(['id' => $idHoja]);
            $hoja = $stmtHData->fetch(PDO::FETCH_ASSOC) ?: null;
        }
        if (!$hoja) {
            $hoja = resolverHojaPorDetalle($db, (int)$detalle['id_os'], $idDetalle);
        }

        if ($hoja) {
            $detalle['procedencia_punto_muestreo'] = $hoja['procedencia_punto_muestreo'] ?? ($detalle['procedencia_punto_muestreo'] ?? '');
            $detalle['nombre_persona_entrega_muestra'] = $hoja['nombre_persona_entrega_muestra'] ?? ($detalle['nombre_persona_entrega_muestra'] ?? '');
            $detalle['fecha_hora_toma_muestra'] = $hoja['fecha_hora_toma_muestra'] ?? ($detalle['fecha_hora_toma_muestra'] ?? null);
            $detalle['fecha_hora_llegada_laboratorio'] = $hoja['fecha_hora_llegada_laboratorio'] ?? ($detalle['fecha_hora_llegada_laboratorio'] ?? null);
            $detalle['naturaleza_muestra'] = $hoja['naturaleza_muestra'] ?? ($detalle['naturaleza_muestra'] ?? '');
            $detalle['observaciones'] = $hoja['observaciones'] ?? ($detalle['observaciones'] ?? '');
            $detalle['id_hoja'] = (int)$hoja['id'];
            $detalle['numero_registro'] = $hoja['numero_registro'] ?? '';
            $idHojaResolv = (int)$hoja['id'];
        } else {
            $idHojaResolv = $idHoja;
        }

        $archivoMd = !empty($detalle['archivo_markdown']) ? $detalle['archivo_markdown'] : resolverArchivoMarkdownEnsayo($detalle);
        $detalle['archivo_markdown'] = $archivoMd;
        $schemaInfo = obtenerEsquemaPlantillaEnsayo($archivoMd, isset($detalle['formato_id']) ? (int)$detalle['formato_id'] : null);
        $formatosSchemaJson = json_encode([$archivoMd => $schemaInfo], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $columnas = !empty($schemaInfo['columns']) ? $schemaInfo['columns'] : [];
        if (empty($columnas) || count($columnas) <= 6) {
            $descLower = mb_strtolower(($detalle['descripcion_ensayo'] ?? '') . ' ' . $archivoMd, 'UTF-8');
            if (strpos($descLower, 'concreto') !== false || strpos($descLower, 'cilindro') !== false || strpos($descLower, 'compresi') !== false) {
                $columnas = [
                    "Código laboratorio", "Nombre muestra", "Área (in²)", "Reven. (in)", "Reven. (cm)",
                    "Temp. (°C)", "Carga (lb)", "Peso Volumétrico (kg/m³)", "Diseño de concreto (lb/in²)",
                    "R. Compresión (lb/in²)", "R. Compresión (kg/cm²)", "Fecha de Fabricación", "Fecha de Ensayo", "Edad (Días)"
                ];
            } else {
                $columnas = ["Código laboratorio", "Nombre muestra", "Área (in²)", "Carga (lb)", "R. Compresión (lb/in²)", "R. Compresión (kg/cm²)"];
            }
        }

        $prefijoMuestraOS = determinarPrefijoMuestraOS($detalle);
        $muestrasSeteadas = [];
        $sqlFiltroHoja = ($idHojaResolv > 0)
            ? " AND (rm.id_hoja = :id_hoja OR (rm.id_hoja IS NULL AND :id_hoja_fb = (SELECT MIN(hs_sub.id) FROM hojas_solicitud hs_sub WHERE hs_sub.id_os = :id_os_fb)))"
            : "";
        $paramsMuestras = ['id_os' => $detalle['id_os']];
        if ($idHojaResolv > 0) {
            $paramsMuestras['id_hoja'] = $idHojaResolv;
            $paramsMuestras['id_hoja_fb'] = $idHojaResolv;
            $paramsMuestras['id_os_fb'] = $detalle['id_os'];
        }
        $stmtMuestrasLab = $db->prepare("
            SELECT rm.codigo_muestra, rm.codigo_campo, lm.nombre_lote
            FROM recepcion_muestras rm
            LEFT JOIN lotes_muestras lm ON lm.id_recepcion = rm.id
            WHERE rm.id_os = :id_os {$sqlFiltroHoja}
            ORDER BY rm.id ASC
        ");
        $stmtMuestrasLab->execute($paramsMuestras);
        $muestrasLabList = $stmtMuestrasLab->fetchAll(PDO::FETCH_ASSOC);

        if (!empty($muestrasLabList)) {
            foreach ($muestrasLabList as $idx => $ml) {
                $muestrasSeteadas[] = [
                    'codigo_lab' => $ml['codigo_muestra'],
                    'codigo_campo' => $ml['codigo_campo'],
                    'nombre_muestra' => !empty($ml['nombre_lote']) ? $ml['nombre_lote'] : ($detalle['descripcion_ensayo'] . ' - Muestra ' . ($idx + 1))
                ];
            }
        } elseif ($hoja && !empty($hoja['muestras_json'])) {
            $mList = json_decode($hoja['muestras_json'], true) ?: [];
            foreach ($mList as $idx => $md) {
                $codLab = !empty($md['nombre_muestra']) ? trim($md['nombre_muestra']) : sprintf("{$prefijoMuestraOS}-%04d-%02d", $idx + 1, date('y'));
                $muestrasSeteadas[] = [
                    'codigo_lab' => $codLab,
                    'codigo_campo' => $codLab,
                    'nombre_muestra' => !empty($md['descripcion']) ? trim($md['descripcion']) : ($detalle['descripcion_ensayo'] . ' - Muestra ' . ($idx + 1))
                ];
            }
        }

        $metadatosGuardados = [];
        $decResultados = json_decode($detalle['resultados_json'] ?? '', true) ?: [];
        $versionActual = (int)($decResultados['version_actual'] ?? 1);
        $listaVersiones = isset($decResultados['versiones']) && is_array($decResultados['versiones']) ? $decResultados['versiones'] : [];
        if (!empty($decResultados['metadatos']) && is_array($decResultados['metadatos'])) {
            $metadatosGuardados = $decResultados['metadatos'];
        }

        $infoVersionImpresion = [
            'version' => $versionActual,
            'estado' => $decResultados['revision']['estado'] ?? 'pendiente',
            'es_actual' => true,
            'motivo_devolucion' => $decResultados['revision']['motivo_devolucion'] ?? null,
            'usuario_revisor' => $decResultados['revision']['usuario_revisor'] ?? null,
            'fecha' => $decResultados['revision']['fecha_revision'] ?? null
        ];

        $codigoInformePersonalizado = null;
        $tituloInformePersonalizado = null;
        $sha256Informe = null;
        $estadoInforme = null;
        $sustituyeA = null;
        $esDocumentoInterno = false;
        $revisadoPorNombre = '';
        $aprobadoPorNombre = '';
        $observacionesPendientes = '';

        if ($informeData) {
            $codigoInformePersonalizado = $informeData['numero'] ?? null;
            $sha256Informe = $informeData['sha256'] ?? null;
            $estadoInforme = $informeData['estado'] ?? null;
            $sustituyeA = $informeData['sustituye_a'] ?? null;
            $versionActual = (int)($informeData['version'] ?? 1);
            $infoVersionImpresion['version'] = $versionActual;
            $infoVersionImpresion['estado'] = $estadoInforme;
            $infoVersionImpresion['motivo_devolucion'] = $informeData['motivo_devolucion'] ?? null;
            $tituloInformePersonalizado = ($informeData['tipo'] === 'parcial')
                ? 'INFORME PARCIAL DE ENSAYO'
                : 'INFORME CONSOLIDADO DE ENSAYO';
            $revisadoPorNombre = trim((string)($informeData['revisado_por_nombre'] ?? ($informeData['revisado_nombre'] ?? '')));
            $aprobadoPorNombre = trim((string)($informeData['aprobado_por_nombre'] ?? ($informeData['aprobado_nombre'] ?? '')));
            $observacionesPendientes = trim((string)($informeData['observaciones_pendientes'] ?? ''));
            $esDocumentoInterno = !empty($informeData['es_interno'])
                || (($informeData['tipo'] ?? '') === 'parcial' && ConfigCalculoLabServicio::obtenerBool('informe_parcial_es_interno', false, $db));
        } else {
            if ($idHojaResolv > 0) {
                $stmtCheckConsol = $db->prepare("
                    SELECT ir.*,
                           COALESCE(NULLIF(ir.revisado_nombre, ''), ur.nombre) AS revisado_por_nombre,
                           COALESCE(NULLIF(ir.aprobado_nombre, ''), ua.nombre) AS aprobado_por_nombre
                    FROM informes_rotura ir
                    JOIN recepcion_muestras rm ON ir.muestra_id = rm.id
                    LEFT JOIN usuarios ur ON ir.revisado_por = ur.id
                    LEFT JOIN usuarios ua ON ir.aprobado_por = ua.id
                    WHERE rm.id_hoja = :id_hoja AND ir.tipo = 'consolidado' AND ir.estado != 'anulado'
                    ORDER BY ir.id DESC LIMIT 1
                ");
                $stmtCheckConsol->execute(['id_hoja' => $idHojaResolv]);
                $consolFound = $stmtCheckConsol->fetch(PDO::FETCH_ASSOC);
                if ($consolFound) {
                    $codigoInformePersonalizado = $consolFound['numero'];
                    $sha256Informe = $consolFound['sha256'];
                    $estadoInforme = $consolFound['estado'];
                    $sustituyeA = $consolFound['sustituye_a'] ?? null;
                    $tituloInformePersonalizado = 'INFORME CONSOLIDADO DE ENSAYO';
                    $revisadoPorNombre = trim((string)($consolFound['revisado_por_nombre'] ?? ''));
                    $aprobadoPorNombre = trim((string)($consolFound['aprobado_por_nombre'] ?? ''));
                    $observacionesPendientes = trim((string)($consolFound['observaciones_pendientes'] ?? ''));
                }
            }
        }

        // Fase 2.20: Filtrado estricto a espécimen(es) roto(s) para informe parcial o filtro ?especimen_id= / ?rotura_id=
        $esParcialOFiltrado = ($informeData && ($informeData['tipo'] ?? '') === 'parcial')
            || $especimenIdFiltro > 0
            || $roturaIdFiltro > 0;

        if ($esParcialOFiltrado) {
            $sqlRotFiltro = "
                SELECT rot.*, esp.id AS esp_id, esp.codigo_lab, esp.muestra_id,
                       rm.codigo_muestra, rm.codigo_campo,
                       lm.nombre_lote, lm.fecha_moldeo, lm.diseno_resistencia, lm.revenimiento_in, lm.temperatura_c,
                       pre.edad_dias AS plan_edad_dias
                FROM roturas rot
                JOIN especimenes esp ON rot.especimen_id = esp.id
                JOIN recepcion_muestras rm ON esp.muestra_id = rm.id
                LEFT JOIN lotes_muestras lm ON lm.id_recepcion = rm.id
                LEFT JOIN plan_rotura_edades pre ON rot.plan_edad_id = pre.id
                WHERE 1=1
            ";
            $paramsRotFiltro = [];
            if ($roturaIdFiltro > 0) {
                $sqlRotFiltro .= " AND rot.id = :rid";
                $paramsRotFiltro['rid'] = $roturaIdFiltro;
            } elseif ($especimenIdFiltro > 0) {
                $sqlRotFiltro .= " AND esp.id = :eid";
                $paramsRotFiltro['eid'] = $especimenIdFiltro;
            } elseif ($informeData) {
                $sqlRotFiltro .= " AND esp.muestra_id = :mid";
                $paramsRotFiltro['mid'] = (int)$informeData['muestra_id'];
                if (!empty($informeData['plan_edad_id'])) {
                    $sqlRotFiltro .= " AND rot.plan_edad_id = :peid";
                    $paramsRotFiltro['peid'] = (int)$informeData['plan_edad_id'];
                }
            }
            $sqlRotFiltro .= " ORDER BY rot.id ASC";

            $stmtRotFiltro = $db->prepare($sqlRotFiltro);
            $stmtRotFiltro->execute($paramsRotFiltro);
            $roturasFiltradas = $stmtRotFiltro->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($roturasFiltradas)) {
                $mapaRotPorCodigo = [];
                $codigosPermitidos = [];
                foreach ($roturasFiltradas as $rf) {
                    $cLab = strtoupper(trim((string)($rf['codigo_lab'] ?? '')));
                    $cMue = strtoupper(trim((string)($rf['codigo_muestra'] ?? '')));
                    $cBase = preg_replace('/-C\d+$/i', '', $cLab);
                    if ($cLab !== '') {
                        $mapaRotPorCodigo[$cLab] = $rf;
                        $codigosPermitidos[$cLab] = true;
                    }
                    if ($cMue !== '') {
                        $mapaRotPorCodigo[$cMue] = $rf;
                        $codigosPermitidos[$cMue] = true;
                    }
                    if ($cBase !== '') {
                        $mapaRotPorCodigo[$cBase] = $rf;
                        $codigosPermitidos[$cBase] = true;
                    }
                }

                $inyectarDatosRoturaEnFila = static function (array $fila, array $rf): array {
                    $fechaEns = !empty($rf['fecha_real']) ? date('Y-m-d', strtotime((string)$rf['fecha_real'])) : '';
                    $edadReal = $rf['edad_calculada'] ?? ($rf['plan_edad_dias'] ?? '');
                    $revIn = isset($rf['revenimiento_in']) && is_numeric($rf['revenimiento_in']) ? (float)$rf['revenimiento_in'] : null;
                    $revCm = $revIn !== null ? ConfigCalculoLabServicio::calcularRevenimientoCm($revIn) : null;

                    $fila['Área (in²)'] = isset($rf['area']) && (float)$rf['area'] > 0 ? round((float)$rf['area'], 3) : ($fila['Área (in²)'] ?? '');
                    $fila['Carga (lb)'] = isset($rf['carga']) && (float)$rf['carga'] > 0 ? round((float)$rf['carga'], 1) : ($fila['Carga (lb)'] ?? '');
                    if (isset($rf['peso_volumetrico']) && $rf['peso_volumetrico'] !== null && (float)$rf['peso_volumetrico'] > 0) {
                        $fila['Peso Volumétrico (kg/m³)'] = round((float)$rf['peso_volumetrico'], 0);
                    }
                    $fila['R. Compresión (lb/in²)'] = isset($rf['esfuerzo_psi']) ? round((float)$rf['esfuerzo_psi'], 0) : ($fila['R. Compresión (lb/in²)'] ?? '');
                    $fila['R. Compresión (kg/cm²)'] = isset($rf['esfuerzo_kgcm2']) ? round((float)$rf['esfuerzo_kgcm2'], 0) : ($fila['R. Compresión (kg/cm²)'] ?? '');
                    if ($fechaEns !== '') {
                        $fila['Fecha de Ensayo'] = $fechaEns;
                        if (array_key_exists('Fecha de Ruptura', $fila)) {
                            $fila['Fecha de Ruptura'] = $fechaEns;
                        }
                    }
                    if ($edadReal !== '' && $edadReal !== null) {
                        $fila['Edad (Días)'] = (int)$edadReal;
                    }
                    if (!empty($rf['tipo_falla'])) {
                        $fila['Tipo de Falla'] = (string)$rf['tipo_falla'];
                    }
                    if (empty($fila['Fecha de Fabricación']) && !empty($rf['fecha_moldeo'])) {
                        $fila['Fecha de Fabricación'] = (string)$rf['fecha_moldeo'];
                    }
                    if (empty($fila['Diseño de concreto (lb/in²)']) && !empty($rf['diseno_resistencia'])) {
                        $fila['Diseño de concreto (lb/in²)'] = (string)$rf['diseno_resistencia'];
                    }
                    if ($revIn !== null && (empty($fila['Reven. (in)']) || $fila['Reven. (in)'] === '-')) {
                        $fila['Reven. (in)'] = $revIn;
                        $fila['Reven. (cm)'] = $revCm;
                    }
                    if (isset($rf['temperatura_c']) && is_numeric($rf['temperatura_c']) && (empty($fila['Temp. (°C)']) || $fila['Temp. (°C)'] === '-')) {
                        $fila['Temp. (°C)'] = (float)$rf['temperatura_c'];
                    }
                    return $fila;
                };

                $filasOriginales = isset($decResultados['filas']) && is_array($decResultados['filas']) ? $decResultados['filas'] : [];
                $filasFiltradas = [];
                foreach ($filasOriginales as $fOrig) {
                    if (!is_array($fOrig)) {
                        continue;
                    }
                    $colCod = obtenerColumnaCodigoMuestra($fOrig);
                    $codF = strtoupper(trim((string)($colCod !== null ? ($fOrig[$colCod] ?? '') : ($fOrig['Código laboratorio'] ?? ($fOrig['codigo_lab'] ?? '')))));
                    $codFBase = preg_replace('/-C\d+$/i', '', $codF);
                    if ($codF !== '' && (isset($codigosPermitidos[$codF]) || isset($codigosPermitidos[$codFBase]))) {
                        $rfMatch = $mapaRotPorCodigo[$codF] ?? $mapaRotPorCodigo[$codFBase];
                        $filasFiltradas[] = $inyectarDatosRoturaEnFila($fOrig, $rfMatch);
                    }
                }

                // Si no hubo filas en matriz para las roturas filtradas, construirlas directamente desde `roturas`
                if (empty($filasFiltradas)) {
                    foreach ($roturasFiltradas as $rf) {
                        $filaNueva = [
                            'Código laboratorio' => (string)($rf['codigo_lab'] ?? $rf['codigo_muestra'] ?? ''),
                            'Nombre muestra' => (string)($rf['nombre_lote'] ?: ($rf['codigo_campo'] ?: $rf['codigo_muestra'])),
                            'Área (in²)' => '',
                            'Reven. (in)' => '-',
                            'Reven. (cm)' => '-',
                            'Temp. (°C)' => '-',
                            'Carga (lb)' => '',
                            'Peso Volumétrico (kg/m³)' => '',
                            'Diseño de concreto (lb/in²)' => '',
                            'R. Compresión (lb/in²)' => '',
                            'R. Compresión (kg/cm²)' => '',
                            'Fecha de Fabricación' => '',
                            'Fecha de Ensayo' => '',
                            'Edad (Días)' => '',
                        ];
                        $filasFiltradas[] = $inyectarDatosRoturaEnFila($filaNueva, $rf);
                    }
                }

                $decResultados['filas'] = $filasFiltradas;
                $detalle['resultados_json'] = json_encode($decResultados, JSON_UNESCAPED_UNICODE);
            }
        }

        $metadatos = resolverMetadatosEnsayo($detalle, $schemaInfo, $metadatosGuardados);

        // Corrección Bug En Vivo #3 (Sección 9): Fecha de emisión nunca puede ser anterior a la fecha máxima de ejecución del ensayo
        $normalizarFechaYmd = static function (?string $raw): string {
            $val = trim((string)$raw);
            if ($val === '' || $val === '-' || $val === '--') {
                return '';
            }
            if (preg_match('/^(\d{4}-\d{2}-\d{2})/', $val, $m)) {
                return $m[1];
            }
            if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})/', $val, $m)) {
                return "{$m[3]}-{$m[2]}-{$m[1]}";
            }
            $ts = strtotime($val);
            return $ts !== false ? date('Y-m-d', $ts) : '';
        };

        $maxFechaEjecucion = $normalizarFechaYmd((string)($metadatos['fecha_ejecucion'] ?? ''));
        $filasRevisarFecha = isset($decResultados['filas']) && is_array($decResultados['filas']) ? $decResultados['filas'] : [];
        foreach ($filasRevisarFecha as $fRev) {
            if (!is_array($fRev)) {
                continue;
            }
            foreach (['fecha_real', 'Fecha de Ensayo', 'Fecha Ensayo', 'Fecha de Ruptura', 'fecha_ensayo', 'fecha_ruptura'] as $kFecha) {
                if (!empty($fRev[$kFecha])) {
                    $fCand = $normalizarFechaYmd((string)$fRev[$kFecha]);
                    if ($fCand !== '' && ($maxFechaEjecucion === '' || $fCand > $maxFechaEjecucion)) {
                        $maxFechaEjecucion = $fCand;
                    }
                }
            }
        }

        try {
            if ($idHojaResolv > 0) {
                $stmtMaxRot = $db->prepare("
                    SELECT MAX(DATE(rot.fecha_real))
                    FROM roturas rot
                    JOIN especimenes esp ON rot.especimen_id = esp.id
                    JOIN recepcion_muestras rm ON esp.muestra_id = rm.id
                    WHERE rm.id_hoja = :id_hoja
                ");
                $stmtMaxRot->execute(['id_hoja' => $idHojaResolv]);
                $fRotMax = $normalizarFechaYmd((string)$stmtMaxRot->fetchColumn());
                if ($fRotMax !== '' && ($maxFechaEjecucion === '' || $fRotMax > $maxFechaEjecucion)) {
                    $maxFechaEjecucion = $fRotMax;
                }
            } elseif ($muestraId > 0) {
                $stmtMaxRot = $db->prepare("
                    SELECT MAX(DATE(rot.fecha_real))
                    FROM roturas rot
                    JOIN especimenes esp ON rot.especimen_id = esp.id
                    WHERE esp.muestra_id = :mid
                ");
                $stmtMaxRot->execute(['mid' => $muestraId]);
                $fRotMax = $normalizarFechaYmd((string)$stmtMaxRot->fetchColumn());
                if ($fRotMax !== '' && ($maxFechaEjecucion === '' || $fRotMax > $maxFechaEjecucion)) {
                    $maxFechaEjecucion = $fRotMax;
                }
            }
        } catch (\Throwable $e) {
            // Silencioso
        }

        if ($maxFechaEjecucion !== '') {
            $fechaEjecActual = $normalizarFechaYmd((string)($metadatos['fecha_ejecucion'] ?? ''));
            if ($fechaEjecActual === '' || $fechaEjecActual < $maxFechaEjecucion) {
                $metadatos['fecha_ejecucion'] = $maxFechaEjecucion;
            }
            $fechaEmisionActual = $normalizarFechaYmd((string)($metadatos['fecha_emision'] ?? ''));
            if ($fechaEmisionActual === '' || $fechaEmisionActual < $maxFechaEjecucion) {
                $metadatos['fecha_emision'] = $maxFechaEjecucion;
            }
        }

        $esLaboratorio = true;

        require dirname(__DIR__) . '/Vistas/matriz_print.php';
    }
}
