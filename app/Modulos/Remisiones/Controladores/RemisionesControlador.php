<?php

declare(strict_types=1);

namespace Cycsa\Modulos\Remisiones\Controladores;

use Cycsa\Modulos\Remisiones\Modelos\RemisionModelo;
use Cycsa\Modulos\Remisiones\Modelos\RevisionPt13Modelo;
use Cycsa\Modulos\Remisiones\Servicios\DocumentoRgFm41Servicio;
use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Throwable;

/**
 * Controlador del módulo Remisiones de Entrega de Informes (CYCSA-RG-FM-41 / RLAB-MM-AAAA-N)
 * y Centro de Control PT-13 Ver. 5 Rev. 2 (ISO/IEC 17025:2017).
 */
class RemisionesControlador extends ControladorBase
{
    private function verificarSesion(Respuesta $respuesta): void
    {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    /**
     * Evalúa acceso al módulo permitiendo admin/supervisor/vendedor/asistente/recepción
     * y módulos operativos relacionados mediante tienePermiso().
     */
    private function tieneAccesoModulo(string $accion = 'ver'): bool
    {
        $rol = (int)($_SESSION['usuario_rol'] ?? 0);
        if ($rol === 1) {
            return true;
        }

        if (function_exists('tienePermiso')) {
            $modulos = ['remisiones', 'ordenes_servicio', 'operaciones', 'laboratorio', 'control_calidad', 'recepcion', 'cotizaciones'];
            foreach ($modulos as $mod) {
                if (tienePermiso($mod, $accion)) {
                    return true;
                }
            }
        }

        // Roles estándar operativos: 2=Supervisor, 3=Vendedor/Coordinación, 4=Asistente, 5=Recepción
        if (in_array($rol, [2, 3, 4, 5], true)) {
            return true;
        }

        return false;
    }

    private function validarCsrf(array $datos = []): bool
    {
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $csrfHeader = $headers['X-CSRF-TOKEN'] ?? $headers['X-Csrf-Token'] ?? $headers['x-csrf-token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '');
        $tokenRecibido = (string)($datos['csrf_token'] ?? $_POST['csrf_token'] ?? $csrfHeader);
        $tokenSesion = (string)($_SESSION['csrf_token'] ?? '');

        return $tokenRecibido !== '' && $tokenSesion !== '' && hash_equals($tokenSesion, $tokenRecibido);
    }

    /**
     * GET /remisiones — Listado sobrio de remisiones RG-FM-41 y acceso a Control PT-13.
     */
    public function index(Peticion $peticion, Respuesta $respuesta): void
    {
        $this->verificarSesion($respuesta);
        if (!$this->tieneAccesoModulo('ver')) {
            $_SESSION['error'] = 'No cuenta con permisos para acceder al módulo de Remisiones.';
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }

        $modelo = new RemisionModelo();
        $busqueda = trim((string)($_GET['q'] ?? ''));
        $tab = strtolower(trim((string)($_GET['tab'] ?? 'todas')));
        $modalidad = strtolower(trim((string)($_GET['modalidad'] ?? '')));

        $mapaEstadoTab = [
            'todas' => '',
            'emitidas' => 'Emitida',
            'entregadas' => 'Entregada',
            'anuladas' => 'Anulada',
        ];
        if (!array_key_exists($tab, $mapaEstadoTab)) {
            $tab = 'todas';
        }
        $estadoFiltro = $mapaEstadoTab[$tab];

        if (!in_array($modalidad, ['', 'laboratorio', 'domicilio'], true)) {
            $modalidad = '';
        }

        $remisiones = $modelo->obtenerTodas($busqueda, $estadoFiltro, $modalidad);
        $kpis = $modelo->obtenerResumenKpis();

        $this->renderizar('Remisiones/Vistas/index', [
            'titulo' => 'Remisiones de Entrega de Informes y Control PT-13 - CYCSA',
            'remisiones' => $remisiones,
            'kpis' => $kpis,
            'busqueda' => $busqueda,
            'tabActual' => $tab,
            'modalidadFiltro' => $modalidad,
            'csrfToken' => (string)($_SESSION['csrf_token'] ?? ''),
        ]);
    }

    /**
     * GET /remisiones/crear — Formulario para emitir nueva remisión CYCSA-RG-FM-41.
     */
    public function crear(Peticion $peticion, Respuesta $respuesta): void
    {
        $this->verificarSesion($respuesta);
        if (!$this->tieneAccesoModulo('crear_editar')) {
            $_SESSION['error'] = 'No cuenta con permisos para emitir remisiones.';
            $respuesta->redirigir('/Cycsa/publico/remisiones');
            return;
        }

        $modelo = new RemisionModelo();
        $ordenes = $modelo->obtenerOrdenesConInformes();
        $codigoSugerido = $modelo->generarCodigoRemision();

        $idOsPre = (int)($_GET['id_os'] ?? 0);
        $datosPre = $idOsPre > 0 ? $modelo->obtenerInformesDisponiblesPorOs($idOsPre) : ['orden' => null, 'informes' => []];

        $this->renderizar('Remisiones/Vistas/crear', [
            'titulo' => 'Nueva Remisión de Entrega (CYCSA-RG-FM-41) - CYCSA',
            'ordenes' => $ordenes,
            'codigoSugerido' => $codigoSugerido,
            'idOsPreseleccionada' => $idOsPre,
            'ordenPreseleccionada' => $datosPre['orden'],
            'informesPreseleccionados' => $datosPre['informes'],
            'entregadoPorDefault' => (string)($_SESSION['usuario_nombre'] ?? 'Recepción CYCSA'),
            'csrfToken' => (string)($_SESSION['csrf_token'] ?? ''),
        ]);
    }

    /**
     * POST /remisiones/guardar — Crea la remisión y sus detalles en transacción.
     */
    public function guardar(Peticion $peticion, Respuesta $respuesta): void
    {
        $this->verificarSesion($respuesta);
        if (!$this->tieneAccesoModulo('crear_editar')) {
            $_SESSION['error'] = 'No cuenta con permisos para emitir remisiones.';
            $respuesta->redirigir('/Cycsa/publico/remisiones');
            return;
        }

        $datos = $peticion->obtenerDatos();
        if (!$this->validarCsrf($datos)) {
            $_SESSION['error'] = 'Token de seguridad (CSRF) inválido o expirado. Intente nuevamente.';
            $respuesta->redirigir('/Cycsa/publico/remisiones/crear');
            return;
        }

        $codigos = is_array($datos['item_codigo_informe'] ?? null) ? $datos['item_codigo_informe'] : [];
        $descripciones = is_array($datos['item_descripcion_ensayo'] ?? null) ? $datos['item_descripcion_ensayo'] : [];
        $formatos = is_array($datos['item_formato_entrega'] ?? null) ? $datos['item_formato_entrega'] : [];
        $idsDetalle = is_array($datos['item_id_detalle_cotizacion'] ?? null) ? $datos['item_id_detalle_cotizacion'] : [];
        $idsControl = is_array($datos['item_id_informe_control'] ?? null) ? $datos['item_id_informe_control'] : [];
        $seleccionados = is_array($datos['item_incluir'] ?? null) ? $datos['item_incluir'] : null;

        $items = [];
        foreach ($codigos as $i => $codigo) {
            if ($seleccionados !== null && !in_array((string)$i, array_map('strval', $seleccionados), true)) {
                continue;
            }
            $codigoLimpio = trim((string)$codigo);
            if ($codigoLimpio === '') {
                continue;
            }
            $items[] = [
                'codigo_informe' => $codigoLimpio,
                'descripcion_ensayo' => trim((string)($descripciones[$i] ?? '')),
                'formato_entrega' => trim((string)($formatos[$i] ?? 'Digital PDF')),
                'id_detalle_cotizacion' => !empty($idsDetalle[$i]) ? (int)$idsDetalle[$i] : null,
                'id_informe_control' => !empty($idsControl[$i]) ? (int)$idsControl[$i] : null,
            ];
        }

        if ($items === []) {
            $_SESSION['error'] = 'Debe incluir al menos un informe válido en la remisión.';
            $respuesta->redirigir('/Cycsa/publico/remisiones/crear');
            return;
        }

        try {
            $modelo = new RemisionModelo();
            $idRemision = $modelo->crear([
                'id_os' => !empty($datos['id_os']) ? (int)$datos['id_os'] : null,
                'id_cliente' => !empty($datos['id_cliente']) ? (int)$datos['id_cliente'] : null,
                'empresa_nombre' => trim((string)($datos['empresa_nombre'] ?? '')),
                'modalidad_entrega' => trim((string)($datos['modalidad_entrega'] ?? 'laboratorio')),
                'fecha_entrega' => trim((string)($datos['fecha_entrega'] ?? '')),
                'persona_recibe' => trim((string)($datos['persona_recibe'] ?? '')),
                'cedula_recibe' => trim((string)($datos['cedula_recibe'] ?? '')),
                'telefono_recibe' => trim((string)($datos['telefono_recibe'] ?? '')),
                'direccion_entrega' => trim((string)($datos['direccion_entrega'] ?? '')),
                'entregado_por' => trim((string)($datos['entregado_por'] ?? ($_SESSION['usuario_nombre'] ?? ''))),
                'observaciones' => trim((string)($datos['observaciones'] ?? '')),
                'estado' => 'Emitida',
                'usuario_id' => (int)($_SESSION['usuario_id'] ?? 0),
            ], $items);

            $creada = $modelo->obtenerPorId($idRemision);
            $codigo = (string)($creada['codigo_remision'] ?? ('#' . $idRemision));

            if (function_exists('registrarBitacora')) {
                registrarBitacora(
                    'remisiones',
                    'crear',
                    "Remisión de entrega {$codigo} (CYCSA-RG-FM-41) emitida con " . count($items) . ' informe(s).',
                    $idRemision,
                    ['entidad_tipo' => 'remision_informe', 'entidad_id' => $idRemision, 'id_os' => !empty($datos['id_os']) ? (int)$datos['id_os'] : null]
                );
            }

            $_SESSION['exito'] = "Remisión {$codigo} emitida correctamente.";
            $respuesta->redirigir('/Cycsa/publico/remisiones/detalle?id=' . $idRemision);
        } catch (Throwable $e) {
            $_SESSION['error'] = 'No se pudo crear la remisión: ' . $e->getMessage();
            $respuesta->redirigir('/Cycsa/publico/remisiones/crear');
        }
    }

    /**
     * GET /remisiones/detalle?id= — Documento oficial CYCSA-RG-FM-41 y acciones operativas.
     */
    public function detalle(Peticion $peticion, Respuesta $respuesta): void
    {
        $this->verificarSesion($respuesta);
        if (!$this->tieneAccesoModulo('ver')) {
            $_SESSION['error'] = 'No cuenta con permisos para ver remisiones.';
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $modelo = new RemisionModelo();
        $remision = $modelo->obtenerPorId($id);

        if (!$remision) {
            $_SESSION['error'] = 'La remisión solicitada no existe.';
            $respuesta->redirigir('/Cycsa/publico/remisiones');
            return;
        }

        $this->renderizar('Remisiones/Vistas/detalle', [
            'titulo' => 'Remisión ' . $remision['codigo_remision'] . ' (CYCSA-RG-FM-41) - CYCSA',
            'remision' => $remision,
            'esModoPdfRgFm41' => false,
            'csrfToken' => (string)($_SESSION['csrf_token'] ?? ''),
        ]);
    }

    /**
     * GET /remisiones/pdf?id= — Descarga PDF oficial Dompdf del documento CYCSA-RG-FM-41.
     */
    public function descargarPdf(Peticion $peticion, Respuesta $respuesta): void
    {
        $this->verificarSesion($respuesta);
        if (!$this->tieneAccesoModulo('ver')) {
            $respuesta->establecerCodigoEstado(403);
            echo 'Acceso denegado.';
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $modelo = new RemisionModelo();
        $remision = $modelo->obtenerPorId($id);

        if (!$remision) {
            $_SESSION['error'] = 'Remisión no encontrada para generar PDF.';
            $respuesta->redirigir('/Cycsa/publico/remisiones');
            return;
        }

        $pdfBytes = DocumentoRgFm41Servicio::pdf($remision);
        $nombreArchivo = preg_replace('/[^A-Za-z0-9_-]/', '_', (string)$remision['codigo_remision']) . '_CYCSA-RG-FM-41.pdf';

        if (!headers_sent()) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
            header('Content-Length: ' . strlen($pdfBytes));
        }

        echo $pdfBytes;
        if (!defined('PHPUNIT_RUNNING')) {
            exit;
        }
    }

    /**
     * POST /remisiones/marcar-entregada — Confirma entrega efectiva con receptor y documento.
     */
    public function marcarEntregada(Peticion $peticion, Respuesta $respuesta): void
    {
        $this->verificarSesion($respuesta);
        if (!$this->tieneAccesoModulo('crear_editar')) {
            $_SESSION['error'] = 'No cuenta con permisos para actualizar remisiones.';
            $respuesta->redirigir('/Cycsa/publico/remisiones');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $id = (int)($datos['id'] ?? 0);

        if (!$this->validarCsrf($datos)) {
            $_SESSION['error'] = 'Token CSRF inválido o faltante.';
            $respuesta->redirigir('/Cycsa/publico/remisiones/detalle?id=' . $id);
            return;
        }

        $personaRecibe = trim((string)($datos['persona_recibe'] ?? ''));
        if ($personaRecibe === '') {
            $_SESSION['error'] = 'Debe indicar el nombre de la persona que recibe los informes.';
            $respuesta->redirigir('/Cycsa/publico/remisiones/detalle?id=' . $id);
            return;
        }

        $modelo = new RemisionModelo();
        $ok = $modelo->marcarEntregada(
            $id,
            $personaRecibe,
            trim((string)($datos['cedula_recibe'] ?? '')),
            trim((string)($datos['telefono_recibe'] ?? '')),
            trim((string)($datos['fecha_entrega'] ?? ''))
        );

        if ($ok) {
            if (function_exists('registrarBitacora')) {
                registrarBitacora(
                    'remisiones',
                    'entregar',
                    "Remisión #{$id} marcada como Entregada. Recibió: {$personaRecibe}.",
                    $id
                );
            }
            $_SESSION['exito'] = 'La remisión fue marcada como Entregada correctamente.';
        } else {
            $_SESSION['error'] = 'No se pudo marcar como entregada la remisión (verifique que no esté anulada).';
        }

        $respuesta->redirigir('/Cycsa/publico/remisiones/detalle?id=' . $id);
    }

    /**
     * POST /remisiones/anular — Anula una remisión con motivo documentado.
     */
    public function anular(Peticion $peticion, Respuesta $respuesta): void
    {
        $this->verificarSesion($respuesta);
        if (!$this->tieneAccesoModulo('crear_editar')) {
            $_SESSION['error'] = 'No cuenta con permisos para anular remisiones.';
            $respuesta->redirigir('/Cycsa/publico/remisiones');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $id = (int)($datos['id'] ?? 0);

        if (!$this->validarCsrf($datos)) {
            $_SESSION['error'] = 'Token CSRF inválido o faltante.';
            $respuesta->redirigir('/Cycsa/publico/remisiones/detalle?id=' . $id);
            return;
        }

        $motivo = trim((string)($datos['motivo_anulacion'] ?? ''));
        if ($motivo === '') {
            $_SESSION['error'] = 'Debe indicar el motivo de anulación de la remisión.';
            $respuesta->redirigir('/Cycsa/publico/remisiones/detalle?id=' . $id);
            return;
        }

        $modelo = new RemisionModelo();
        if ($modelo->anular($id, $motivo)) {
            if (function_exists('registrarBitacora')) {
                registrarBitacora(
                    'remisiones',
                    'anular',
                    "Remisión #{$id} anulada. Motivo: {$motivo}",
                    $id
                );
            }
            $_SESSION['exito'] = 'Remisión anulada correctamente.';
        } else {
            $_SESSION['error'] = 'No fue posible anular la remisión solicitada.';
        }

        $respuesta->redirigir('/Cycsa/publico/remisiones/detalle?id=' . $id);
    }

    /**
     * GET /remisiones/api-informes-os?id_os= — JSON con datos de O/S e informes disponibles.
     */
    public function apiInformesPorOs(Peticion $peticion, Respuesta $respuesta): void
    {
        $this->verificarSesion($respuesta);
        if (!$this->tieneAccesoModulo('ver')) {
            $respuesta->enviarJson(['ok' => false, 'error' => 'Acceso denegado.'], 403);
            return;
        }

        $idOs = (int)($_GET['id_os'] ?? 0);
        if ($idOs <= 0) {
            $respuesta->enviarJson(['ok' => false, 'error' => 'Parámetro id_os inválido.', 'orden' => null, 'informes' => []], 400);
            return;
        }

        $modelo = new RemisionModelo();
        $resultado = $modelo->obtenerInformesDisponiblesPorOs($idOs);

        if (!$resultado['orden']) {
            $respuesta->enviarJson(['ok' => false, 'error' => 'Orden de Servicio no encontrada.', 'orden' => null, 'informes' => []], 404);
            return;
        }

        $respuesta->enviarJson([
            'ok' => true,
            'orden' => $resultado['orden'],
            'informes' => $resultado['informes'],
        ]);
    }

    /**
     * GET /remisiones/revision-pt13 — Centro de Control PT-13 Ver. 5 Rev. 2 (3 etapas, CYCSA-RT-FM-65 y Anulados).
     */
    public function revisionPt13(Peticion $peticion, Respuesta $respuesta): void
    {
        $this->verificarSesion($respuesta);
        if (!$this->tieneAccesoModulo('ver')) {
            $_SESSION['error'] = 'No cuenta con permisos para acceder al Control PT-13.';
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }

        $modeloPt13 = new RevisionPt13Modelo();
        $busqueda = trim((string)($_GET['q'] ?? ''));
        $vistaTab = strtolower(trim((string)($_GET['seccion'] ?? 'revisiones')));
        if (!in_array($vistaTab, ['revisiones', 'anulados'], true)) {
            $vistaTab = 'revisiones';
        }

        $idDetalleSel = (int)($_GET['id_detalle'] ?? 0);
        $revisiones = $modeloPt13->obtenerListaRevisiones($busqueda);
        $anulados = $modeloPt13->obtenerInformesAnulados();

        $detalleActivo = null;
        if ($idDetalleSel > 0) {
            foreach ($revisiones as $rev) {
                if ((int)$rev['id_detalle_cotizacion'] === $idDetalleSel) {
                    $detalleActivo = $rev;
                    break;
                }
            }
        } elseif (!empty($revisiones)) {
            $detalleActivo = $revisiones[0];
        }

        $this->renderizar('Remisiones/Vistas/revision_pt13', [
            'titulo' => 'Control PT-13 Ver. 5 Rev. 2 y Checklist CYCSA-RT-FM-65 - CYCSA',
            'revisiones' => $revisiones,
            'anulados' => $anulados,
            'detalleActivo' => $detalleActivo,
            'checklistItems' => RevisionPt13Modelo::ITEMS_CHECKLIST_RT_FM_65,
            'busqueda' => $busqueda,
            'seccionActiva' => $vistaTab,
            'usuarioActualId' => (int)($_SESSION['usuario_id'] ?? 0),
            'usuarioActualNombre' => (string)($_SESSION['usuario_nombre'] ?? 'Usuario'),
            'csrfToken' => (string)($_SESSION['csrf_token'] ?? ''),
        ]);
    }

    /**
     * POST /remisiones/revision-pt13/guardar — Registra etapa de revisión PT-13 (tecnica | forma | calidad).
     */
    public function guardarRevisionPt13(Peticion $peticion, Respuesta $respuesta): void
    {
        $this->verificarSesion($respuesta);
        if (!$this->tieneAccesoModulo('crear_editar')) {
            $_SESSION['error'] = 'No cuenta con permisos para registrar revisiones PT-13.';
            $respuesta->redirigir('/Cycsa/publico/remisiones/revision-pt13');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $idDetalle = (int)($datos['id_detalle_cotizacion'] ?? 0);
        $urlRetorno = '/Cycsa/publico/remisiones/revision-pt13' . ($idDetalle > 0 ? '?id_detalle=' . $idDetalle : '');

        if (!$this->validarCsrf($datos)) {
            $_SESSION['error'] = 'Token CSRF inválido o expirado.';
            $respuesta->redirigir($urlRetorno);
            return;
        }

        if ($idDetalle <= 0) {
            $_SESSION['error'] = 'Debe seleccionar un ensayo/informe válido.';
            $respuesta->redirigir('/Cycsa/publico/remisiones/revision-pt13');
            return;
        }

        $etapa = strtolower(trim((string)($datos['etapa'] ?? 'tecnica')));
        $checklist = is_array($datos['checklist'] ?? null) ? $datos['checklist'] : [];
        $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
        $usuarioNombre = (string)($_SESSION['usuario_nombre'] ?? 'Usuario');

        $extras = [
            'codigo_informe' => trim((string)($datos['codigo_informe'] ?? '')),
            'es_laboratorio_externo' => !empty($datos['es_laboratorio_externo']) ? 1 : 0,
            'laboratorio_externo_nombre' => trim((string)($datos['laboratorio_externo_nombre'] ?? '')),
            'adiciones_desviaciones' => trim((string)($datos['adiciones_desviaciones'] ?? '')),
            'opiniones_interpretaciones' => trim((string)($datos['opiniones_interpretaciones'] ?? '')),
            'incertidumbre_medicion' => trim((string)($datos['incertidumbre_medicion'] ?? '')),
            'solicitud_simplificacion_ref' => trim((string)($datos['solicitud_simplificacion_ref'] ?? '')),
            'marcar_observado' => !empty($datos['marcar_observado']),
        ];

        $modelo = new RevisionPt13Modelo();

        if ($etapa === 'tecnica') {
            $res = $modelo->registrarRevisionTecnica(
                $idDetalle,
                $usuarioId,
                $usuarioNombre,
                $checklist,
                trim((string)($datos['obs_rev_tecnica'] ?? '')),
                $extras
            );
        } elseif ($etapa === 'forma') {
            $res = $modelo->registrarRevisionForma(
                $idDetalle,
                $usuarioId,
                $usuarioNombre,
                $checklist,
                trim((string)($datos['obs_rev_forma'] ?? '')),
                !empty($datos['marcar_observado'])
            );
        } elseif ($etapa === 'calidad') {
            $res = $modelo->autorizarYLiberar(
                $idDetalle,
                $usuarioId,
                $usuarioNombre,
                array_merge($extras, [
                    'checklist' => $checklist,
                    'es_unificacion_suplencia' => !empty($datos['es_unificacion_suplencia']) ? 1 : 0,
                    'suplencia_funciones' => trim((string)($datos['suplencia_funciones'] ?? '')),
                    'suplencia_motivo' => trim((string)($datos['suplencia_motivo'] ?? '')),
                    'suplencia_referencia_autorizacion' => trim((string)($datos['suplencia_referencia_autorizacion'] ?? '')),
                ])
            );
        } else {
            $res = ['exito' => false, 'error' => 'Etapa de revisión PT-13 no reconocida.'];
        }

        if (!empty($res['exito'])) {
            if (function_exists('registrarBitacora')) {
                registrarBitacora(
                    'remisiones',
                    'revision_pt13_' . $etapa,
                    "Control PT-13 etapa '{$etapa}' registrada para detalle #{$idDetalle}. Estado: " . ($res['estado_pt13'] ?? ''),
                    $idDetalle
                );
            }
            $_SESSION['exito'] = 'Control PT-13 actualizado correctamente. Estado actual: ' . ($res['estado_pt13'] ?? 'Registrado') . '.';
        } else {
            $_SESSION['error'] = (string)($res['error'] ?? 'No se pudo guardar la revisión PT-13.');
        }

        $respuesta->redirigir($urlRetorno);
    }

    /**
     * POST /remisiones/anular-informe — Registra anulación permanente de un código de informe (PT-13 Sec. 5.6 b).
     */
    public function anularInforme(Peticion $peticion, Respuesta $respuesta): void
    {
        $this->verificarSesion($respuesta);
        if (!$this->tieneAccesoModulo('crear_editar')) {
            $_SESSION['error'] = 'No cuenta con permisos para anular informes.';
            $respuesta->redirigir('/Cycsa/publico/remisiones/revision-pt13?seccion=anulados');
            return;
        }

        $datos = $peticion->obtenerDatos();
        if (!$this->validarCsrf($datos)) {
            $_SESSION['error'] = 'Token CSRF inválido o expirado.';
            $respuesta->redirigir('/Cycsa/publico/remisiones/revision-pt13?seccion=anulados');
            return;
        }

        $codigoInforme = trim((string)($datos['codigo_informe'] ?? ''));
        $idDetalle = !empty($datos['id_detalle_cotizacion']) ? (int)$datos['id_detalle_cotizacion'] : null;
        $idOs = !empty($datos['id_os']) ? (int)$datos['id_os'] : null;
        $motivo = trim((string)($datos['motivo'] ?? ''));

        $modelo = new RevisionPt13Modelo();
        $res = $modelo->anularInforme(
            $codigoInforme,
            $idDetalle,
            $idOs,
            $motivo,
            (int)($_SESSION['usuario_id'] ?? 0),
            (string)($_SESSION['usuario_nombre'] ?? 'Usuario')
        );

        if (!empty($res['exito'])) {
            if (function_exists('registrarBitacora')) {
                registrarBitacora(
                    'remisiones',
                    'anular_informe',
                    "Informe '{$codigoInforme}' anulado permanentemente (PT-13 Sec. 5.6 b). Motivo: {$motivo}",
                    $res['id'] ?? null
                );
            }
            $_SESSION['exito'] = "Informe '{$codigoInforme}' registrado como Anulado. Su código queda bloqueado de forma permanente.";
        } else {
            $_SESSION['error'] = (string)($res['error'] ?? 'No se pudo anular el informe.');
        }

        $respuesta->redirigir('/Cycsa/publico/remisiones/revision-pt13?seccion=anulados');
    }
}
