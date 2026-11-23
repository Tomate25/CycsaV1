<?php

namespace Cycsa\Modulos\Operaciones\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\Operaciones\Modelos\CierreOperacionLims;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Operaciones\Servicios\InformeLaboratorioServicio;
use PDO;

class OperacionesControlador extends ControladorBase {
    
    private function verificarSesion(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
    }

    private function verificarPermiso(Respuesta $respuesta, string $accion = 'ver'): void {
        if (!tienePermiso('operaciones', $accion)) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
    }

    /**
     * Muestra el panel principal de Operaciones LIMS.
     */
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (function_exists('esTecnicoCiegoISO17025') && esTecnicoCiegoISO17025()) {
            $respuesta->redirigir('/Cycsa/publico/captura-tecnica');
            return;
        }
        $this->verificarPermiso($respuesta, 'ver');

        $modelo = new OperacionModelo();
        $busqueda = $_GET['q'] ?? '';
        $tabActiva = $_GET['tab'] ?? 'activas';
        if (!in_array($tabActiva, ['activas', 'muestreo', 'ensayos', 'historico'], true)) {
            $tabActiva = 'activas';
        }
        $bandejaActiva = trim((string)($_GET['bandeja'] ?? 'todas'));
        if (!in_array($bandejaActiva, ['todas', 'recepcion', 'tecnico', 'supervisor', 'informes'], true)) {
            $bandejaActiva = 'todas';
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Obtener cotizaciones aprobadas listas para generar O/S, y las O/S según pestaña
        $cotizacionesParaOS = $modelo->obtenerCotizacionesParaOS($busqueda);
        $tabBusquedaModelo = $tabActiva === 'ensayos' ? 'activas' : $tabActiva;
        $ordenesActivas = $modelo->obtenerOSActivas($busqueda, $tabBusquedaModelo);
        $conteosTabs = $modelo->obtenerConteosTabsOS();
        
        $db = Conexion::obtenerInstancia();
        $stmtCxcCodes = $db->query("SELECT * FROM cuentas_por_cobrar");
        $cxcRecords = $stmtCxcCodes->fetchAll(PDO::FETCH_ASSOC);
        $cxcMap = [];
        foreach ($cxcRecords as $r) {
            $cxcMap[$r['factura_numero']] = $r;
        }

        $conteoEnsayosPendientes = 0;
        $conteoBandejas = [
            'todas' => 0,
            'recepcion' => 0,
            'tecnico' => 0,
            'supervisor' => 0,
            'informes' => 0,
        ];

        foreach ($ordenesActivas as &$o) {
            $o = $this->enriquecerOrdenBaseOperacion($db, $modelo, $o, $cxcMap);
            $o = array_merge($o, $this->construirStepperDeterministicoOS($db, $o, $o['items'], $o['hojas_solicitud']));
            $o = $this->completarEstadoComercialOS($o);

            $bandejaRol = (string)($o['bandeja_rol'] ?? 'recepcion');
            $conteoBandejas['todas']++;
            if (isset($conteoBandejas[$bandejaRol])) {
                $conteoBandejas[$bandejaRol]++;
            }

            if (empty($o['tecnico_100'])) {
                $conteoEnsayosPendientes++;
            }
        }
        unset($o);

        $conteosTabs['ensayos'] = $conteoEnsayosPendientes;

        if ($tabActiva === 'ensayos') {
            $ordenesActivas = array_values(array_filter($ordenesActivas, fn($o) => !$o['tecnico_100']));
        }
        if ($bandejaActiva !== 'todas') {
            $ordenesActivas = array_values(array_filter($ordenesActivas, fn($o) => ($o['bandeja_rol'] ?? '') === $bandejaActiva));
        }
        
        $bitacora_logs = obtenerBitacoraModulo('operaciones');

        $this->renderizar('operaciones/vistas/index', [
            'titulo' => 'Operaciones LIMS - Cycsa',
            'cotizaciones' => $cotizacionesParaOS,
            'ordenes' => $ordenesActivas,
            'busqueda' => $busqueda,
            'tabActiva' => $tabActiva,
            'bandejaActiva' => $bandejaActiva,
            'conteoBandejas' => $conteoBandejas,
            'conteosTabs' => $conteosTabs,
            'tecnicos' => $modelo->obtenerTecnicosActivos(),
            'vehiculos' => $modelo->obtenerVehiculosActivos(),
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
            'bitacora_logs' => $bitacora_logs
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Crea una Orden de Servicio (O/S) en base a una cotización.
     */
    public function crearOS(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new OperacionModelo();

            if (!isset($_SESSION['csrf_token'], $datos['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $idCotizacion = (int)($datos['id_cotizacion'] ?? 0);
            $tipoContrato = $datos['tipo_contrato'] ?? 'Puntual';
            $fechaM = $datos['fecha_muestreo'] ?? null;
            $horaM = $datos['hora_muestreo'] ?? null;
            $tecnicoM = $datos['tecnico_muestreo'] ?? null;
            $vehiculoM = $datos['vehiculo_muestreo'] ?? null;

            if ($idCotizacion <= 0) {
                $_SESSION['error'] = 'ID de cotización inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $codigoOS = $modelo->crearOS($idCotizacion, $tipoContrato, $fechaM, $horaM, $tecnicoM, $vehiculoM);
            if ($codigoOS) {
                $idOsCreada = (int)Conexion::obtenerInstancia()->lastInsertId();
                if ($idOsCreada <= 0) {
                    $stmtOsNueva = Conexion::obtenerInstancia()->prepare('SELECT id FROM ordenes_servicio WHERE codigo_os = :codigo ORDER BY id DESC LIMIT 1');
                    $stmtOsNueva->execute(['codigo' => $codigoOS]);
                    $idOsCreada = (int)$stmtOsNueva->fetchColumn();
                }
                registrarEventoOS('operaciones', 'crear_os', 'Orden de Servicio creada: ' . $codigoOS . ' con programación de muestreo.', $idOsCreada);
                $_SESSION['exito'] = "Orden de Servicio $codigoOS creada exitosamente.";
            } else {
                $_SESSION['error'] = 'Error al crear la Orden de Servicio.';
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones');
        }
    }

    /**
     * Muestra la pantalla para registrar la recepción de una muestra bajo una O/S.
     */
    public function recepcionForm(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        $idOS = (int)($_GET['id_os'] ?? 0);
        $idDetalle = (int)($_GET['id_detalle'] ?? 0);
        if ($idOS <= 0) {
            $_SESSION['error'] = 'Orden de Servicio inválida.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $modelo = new OperacionModelo();
        $os = $modelo->obtenerOSPorId($idOS);
        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        // Obtener los detalles de la cotización y dejar solo los ensayos habilitados en la O/S
        $servicios = $modelo->filtrarDetallesPermitidos($idOS, $modelo->obtenerDetallesCotizacion((int)$os['id_cotizacion']));
        
        // Obtener los ítems de la O/S para saber cuáles ya tienen recepción registrada
        $itemsOS = $modelo->obtenerItemsOS($idOS);
        
        // Mapear el estado de recepción a cada servicio (aislado estrictamente por O/S)
        foreach ($servicios as &$s) {
            $s['ya_recibido'] = false;
            $s['codigo_muestra'] = null;
            $s['total_recibidos'] = 0;
            $s['cantidad_facturada'] = max(1, (int)($s['cantidad'] ?? 1));
            
            foreach ($itemsOS as $item) {
                if ((int)$item['id_detalle'] === (int)$s['id']) {
                    $recibidos = (int)($item['total_recibidos'] ?? 0);
                    $s['total_recibidos'] = $recibidos;
                    $s['codigo_muestra'] = $item['codigo_muestra'];
                    
                    // Solo marcar como deshabilitado si ya se recibieron TODAS las muestras facturadas para esta O/S específica
                    if ($recibidos >= $s['cantidad_facturada']) {
                        $s['ya_recibido'] = true;
                    }
                    break;
                }
            }
        }
        unset($s);

        $hojasSolicitud = $modelo->obtenerHojasSolicitudPorOS($idOS);
        $hojaSolicitud = !empty($hojasSolicitud) ? end($hojasSolicitud) : null;
        
        $muestrasDeclaradas = [];
        foreach ($hojasSolicitud as $hsItem) {
            if (!empty($hsItem['muestras_json'])) {
                $mArr = json_decode($hsItem['muestras_json'], true) ?: [];
                foreach ($mArr as $mVal) {
                    $mVal['hoja_numero_registro'] = $hsItem['numero_registro'] ?? ('#' . $hsItem['id']);
                    $mVal['id_hoja'] = $hsItem['id'];
                    $muestrasDeclaradas[] = $mVal;
                }
            }
        }

        // Fetch already received samples for this OS to map status
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $stmtRM = $db->prepare("SELECT codigo_campo, codigo_muestra FROM recepcion_muestras WHERE id_os = :id_os");
        $stmtRM->execute(['id_os' => $idOS]);
        $rmList = $stmtRM->fetchAll(PDO::FETCH_ASSOC);

        $recibidasMap = [];
        foreach ($rmList as $rm) {
            $recibidasMap[trim($rm['codigo_campo'])] = $rm['codigo_muestra'];
        }

        // Enhance muestrasDeclaradas with received status
        foreach ($muestrasDeclaradas as &$md) {
            $nombreTrim = trim($md['nombre_muestra'] ?? '');
            if (isset($recibidasMap[$nombreTrim])) {
                $md['recibida'] = true;
                $md['codigo_muestra'] = $recibidasMap[$nombreTrim];
            } else {
                $md['recibida'] = false;
                $md['codigo_muestra'] = null;
            }
        }
        unset($md);

        $anio = date('Y');
        $prefijoMuestraOS = determinarPrefijoMuestraOS($os);
        $codigoCampoAuto = !empty($os['hoja_campo_codigo']) ? $os['hoja_campo_codigo'] : sprintf("{$prefijoMuestraOS}-%04d-%02d", $siguienteConsecutivo, date('y'));

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('operaciones/vistas/recepcion', [
            'titulo' => 'Recepción de Muestras - LIMS',
            'os' => $os,
            'servicios' => $servicios,
            'idDetalle' => $idDetalle,
            'hoja_solicitud' => $hojaSolicitud,
            'hojas_solicitud' => $hojasSolicitud,
            'codigoCampoAuto' => $codigoCampoAuto,
            'siguienteConsecutivo' => $siguienteConsecutivo,
            'prefijoMuestraOS' => $prefijoMuestraOS,
            'muestrasDeclaradas' => $muestrasDeclaradas,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Guarda la recepción de la muestra y genera la Hoja de Solicitud física.
     */
    public function guardarRecepcion(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new OperacionModelo();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $datos['recibido_por'] = (int)($_SESSION['usuario_id'] ?? 0);

            if ($modelo->registrarRecepcion($datos)) {
                $_SESSION['exito'] = 'Recepción de muestras registrada exitosamente. Se ha generado la Hoja de Solicitud de Análisis Ciega para laboratorio.';
            } else {
                $detalleRecepcion = $modelo->obtenerUltimoErrorRecepcion() ?? '';
                $_SESSION['error'] = str_contains($detalleRecepcion, 'no está habilitado')
                    ? $detalleRecepcion
                    : 'Error al registrar la recepción de muestras.';
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones');
        }
    }

    /**
     * Muestra la vista de calendario operativo enfocado en el cronograma de rupturas.
     */
    public function calendario(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $modelo = new OperacionModelo();

        // Obtener mes y año a mostrar
        $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
        $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

        if ($mes < 1 || $mes > 12) { $mes = (int)date('m'); }
        if ($anio < 2000 || $anio > 2100) { $anio = (int)date('Y'); }

        $fechaInicio = sprintf('%04d-%02d-01', $anio, $mes);
        $ultimoDia = date('t', strtotime($fechaInicio));
        $fechaFin = sprintf('%04d-%02d-%02d', $anio, $mes, $ultimoDia);

        // Obtener eventos del cronograma de rupturas
        $eventosRaw = $modelo->obtenerEventosCalendario($fechaInicio, $fechaFin);

        $eventosPorDia = [];
        for ($i = 1; $i <= $ultimoDia; $i++) {
            $eventosPorDia[$i] = [];
        }

        foreach ($eventosRaw as $ev) {
            $dia = (int)date('d', strtotime($ev['fecha_evento']));
            $eventosPorDia[$dia][] = $ev;
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('operaciones/vistas/calendario', [
            'titulo' => 'Calendario de Rupturas LIMS - Cycsa',
            'mes' => $mes,
            'anio' => $anio,
            'ultimoDia' => $ultimoDia,
            'eventosPorDia' => $eventosPorDia,
            'primerDiaSemana' => (int)date('w', strtotime($fechaInicio))
        ]);
    }

    /**
     * Muestra el detalle del lote y especímenes para cargar datos crudos y gestionar informes.
     */
    public function detalleLote(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        $idLote = (int)($_GET['id_lote'] ?? 0);
        


        $this->verificarPermiso($respuesta, 'ver');

        if ($idLote <= 0) {
            $_SESSION['error'] = 'Lote de muestra inválido.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        
        // Obtener datos del lote, recepción, cliente y O/S
        // El Técnico de Laboratorio NO debe ver la información del cliente.
        // Aplicamos la política ciega de visibilidad.
        $esTecnico = ($_SESSION['usuario_rol'] ?? 0) == 6; // Rol 6 = Técnico/Laboratorio

        $sqlLote = "SELECT lm.*, rm.codigo_muestra, rm.codigo_campo, rm.fecha_recepcion, os.codigo_os, os.id_cotizacion,
                           cot.nombre_proyecto, cot.direccion_proyecto, cot.atencion_a,
                           cli.nombre_razon_social AS cliente_nombre, cli.identificacion AS cliente_ruc
                    FROM lotes_muestras lm
                    JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                    JOIN ordenes_servicio os ON rm.id_os = os.id
                    JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                    JOIN clientes cli ON cot.id_cliente = cli.id
                    WHERE lm.id = :id_lote";
        
        $stmt = $db->prepare($sqlLote);
        $stmt->execute(['id_lote' => $idLote]);
        $lote = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$lote) {
            $_SESSION['error'] = 'Lote de muestra no encontrado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $modelo = new OperacionModelo();
        $especimenes = $modelo->obtenerDetallesLote($idLote);
        $historialInformes = $modelo->obtenerHistorialInformes($idLote);

        // Obtener el id_detalle_cotizacion asociado a este lote desde ensayo_edades
        $stmtDetalleLote = $db->prepare("SELECT DISTINCT id_detalle_cotizacion FROM ensayo_edades WHERE id_lote = :id_lote LIMIT 1");
        $stmtDetalleLote->execute(['id_lote' => $idLote]);
        $idDetalleCotizacionAsociado = $stmtDetalleLote->fetchColumn();

        // Cargar únicamente el ensayo cotizado de esta O/S asociado al lote para capturar su matriz
        $stmtItems = $db->prepare("SELECT cd.*, fe.archivo_markdown, fe.nombre AS formato_nombre
                                   FROM cotizacion_detalles cd
                                   LEFT JOIN productos p ON cd.id_producto = p.id
                                   LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                                   WHERE cd.id_cotizacion = :id_cot AND cd.id = :id_det_cot");
        $stmtItems->execute([
            'id_cot' => $lote['id_cotizacion'],
            'id_det_cot' => $idDetalleCotizacionAsociado
        ]);
        $itemsOS = $stmtItems->fetchAll(\PDO::FETCH_ASSOC);

        // Cargar el JSON del esquema de los formatos
        $schemaPath = dirname(__DIR__, 4) . '/database/ensayos/formatos_schema.json';
        $formatosSchemaJson = file_exists($schemaPath) ? file_get_contents($schemaPath) : '{}';

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('operaciones/vistas/detalle_lote', [
            'titulo' => 'Detalle del Lote - LIMS',
            'lote' => $lote,
            'especimenes' => $especimenes,
            'historial' => $historialInformes,
            'esTecnico' => $esTecnico,
            'itemsOS' => $itemsOS,
            'formatosSchemaJson' => $formatosSchemaJson,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Carga el resultado de ensaye (Ruptura física).
     */
    public function guardarRuptura(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new OperacionModelo();

            $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                if ($isAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido.']);
                    return;
                }
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $idEnsayo = (int)($datos['id_ensayo'] ?? 0);
            $idLote = (int)($datos['id_lote'] ?? 0);

            if ($idEnsayo <= 0) {
                if ($isAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'ID de ensayo inválido.']);
                    return;
                }
                $_SESSION['error'] = 'ID de ensayo inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $resultado = $modelo->guardarResultadoRuptura($idEnsayo, $datos);

            if ($resultado['exito']) {
                $statusAjax = $resultado['alerta_regresion'] ? 'warning' : 'success';
                if ($isAjax) {
                    $respuesta->enviarJson([
                        'status' => $statusAjax, 
                        'message' => $resultado['mensaje'],
                        'alerta_regresion' => $resultado['alerta_regresion']
                    ]);
                    return;
                }
                if ($resultado['alerta_regresion']) {
                    $_SESSION['exito'] = '⚠️ ' . $resultado['mensaje'];
                } else {
                    $_SESSION['exito'] = $resultado['mensaje'];
                }
            } else {
                if ($isAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => $resultado['mensaje']]);
                    return;
                }
                $_SESSION['error'] = $resultado['mensaje'];
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones/detalle-lote?id_lote=' . $idLote);
        }
    }

    /**
     * Genera e imprime un informe PDF (Parcial o Consolidado) y lo versiona.
     */
    public function generarInforme(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (function_exists('esTecnicoCiegoISO17025') && esTecnicoCiegoISO17025()) {
            $_SESSION['error'] = 'Acceso restringido (ISO/IEC 17025): el perfil técnico no puede generar ni visualizar informes con datos de cliente.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idLote = (int)($datos['id_lote'] ?? 0);
            $idDetalle = (int)($datos['id_detalle'] ?? 0);
            $tipoInforme = $datos['tipo_informe'] ?? 'Parcial';
            $motivoReemplazo = trim($datos['motivo_reemplazo'] ?? '');

            if ($idLote <= 0 || $idDetalle <= 0) {
                $_SESSION['error'] = 'Datos inválidos para generar informe.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones/detalle-lote?id_lote=' . $idLote);
                return;
            }

            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            
            // Obtener datos del detalle de cotización
            $stmtDet = $db->prepare("SELECT cd.*, p.nombre_comercial, fe.archivo_markdown, fe.nombre AS formato_nombre
                                     FROM cotizacion_detalles cd
                                     LEFT JOIN productos p ON cd.id_producto = p.id
                                     LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                                     WHERE cd.id = :id");
            $stmtDet->execute(['id' => $idDetalle]);
            $detalle = $stmtDet->fetch(PDO::FETCH_ASSOC);

            if (!$detalle) {
                $_SESSION['error'] = 'Detalle de ensayo no encontrado.';
                $respuesta->redirigir('/Cycsa/publico/operaciones/detalle-lote?id_lote=' . $idLote);
                return;
            }

            // Obtener datos de la cotización
            $stmtCot = $db->prepare("SELECT c.*, cli.nombre_razon_social AS cliente_nombre, cli.identificacion AS cliente_ruc, cli.telefono AS cliente_telefono
                                     FROM cotizaciones c
                                     JOIN clientes cli ON c.id_cliente = cli.id
                                     WHERE c.id = :id");
            $stmtCot->execute(['id' => $detalle['id_cotizacion']]);
            $cotizacion = $stmtCot->fetch(PDO::FETCH_ASSOC);

            // Generar PDF usando el helper existente
            $archivoMd = !empty($detalle['archivo_markdown']) ? $detalle['archivo_markdown'] : resolverArchivoMarkdownEnsayo($detalle);
        $detalle['archivo_markdown'] = $archivoMd;
        $columnas = $this->obtenerColumnasFormato($archivoMd, $detalle['descripcion_ensayo'] ?? '');
            if (empty($columnas)) {
                $columnas = ["Código laboratorio", "Nombre muestra", "Resultado"];
            }
            
            $filas = [];
            $archivoMd = $detalle['archivo_markdown'];
            
            // Determinar si es un ensayo basado en especímenes/roturas (si tiene especímenes reales en la base de datos)
            $formatosEdades = [
                'resistencia_de_concreto.md',
                'resistencia_de_mortero.md',
                'resistencia_de_nucleo_de_concreto.md',
                'formato_de_resistencia_de_a_la_flexion.md',
                'formato_de_resistencia_de_bloques.md',
                'resistencia_de_adoquines.md',
                'resistencia_de_ladrillo.md',
                'resistencia_de_martillo_suizo.md',
                'formato_de_lodo_concreto.md',
                'formato_de_reveniemiento_y_temperatura.md'
            ];
            $esEnsayoEdades = false;
            if (in_array($archivoMd, $formatosEdades)) {
                $stmtCount = $db->prepare("SELECT COUNT(*) FROM ensayo_edades WHERE id_lote = :id_lote AND identificador_especimen != 'Muestra' AND edad_dias > 0");
                $stmtCount->execute(['id_lote' => $idLote]);
                $esEnsayoEdades = ((int)$stmtCount->fetchColumn() > 0);
            }

             if ($esEnsayoEdades) {
                 $columnas = [
                     "Cilindro",
                     "Edad Evaluada",
                     "Fecha Programada",
                     "Fecha de Ensayo",
                     "Carga Última (Lbs)",
                     "Área Transversal (in²)",
                     "Esfuerzo PSI",
                     "Esfuerzo Kg/cm²",
                     "% Diseño",
                     "Estado / Alerta"
                 ];
                 // Obtener datos del lote y recepción
                $stmtLote = $db->prepare("SELECT lm.*, rm.codigo_muestra, rm.codigo_campo 
                                          FROM lotes_muestras lm
                                          JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                                          WHERE lm.id = :id_lote");
                $stmtLote->execute(['id_lote' => $idLote]);
                 $loteData = $stmtLote->fetch(PDO::FETCH_ASSOC) ?: [];

                // Obtener especímenes del lote
                $sqlEsp = "SELECT * FROM ensayo_edades WHERE id_lote = :id_lote";
                $paramsEsp = ['id_lote' => $idLote];
                
                // Si el informe es parcial y se especificó una edad
                $edadFiltro = (int)($datos['edad_filtro'] ?? 0);
                if ($tipoInforme === 'Parcial' && $edadFiltro > 0) {
                    $sqlEsp .= " AND edad_dias = :edad";
                    $paramsEsp['edad'] = $edadFiltro;
                }
                
                $sqlEsp .= " ORDER BY edad_dias ASC, identificador_especimen ASC";
                $stmtEsp = $db->prepare($sqlEsp);
                $stmtEsp->execute($paramsEsp);
                $especimenesList = $stmtEsp->fetchAll(PDO::FETCH_ASSOC);

                // Mapear especímenes a las columnas del formato
                foreach ($especimenesList as $esp) {
                    $fila = [];
                    foreach ($columnas as $col) {
                        $colLower = mb_strtolower(trim($col));
                        $val = '';
                        
                        if (strpos($colLower, 'código') !== false || strpos($colLower, 'codigo') !== false) {
                            $val = $loteData['codigo_muestra'] ?? '';
                        } elseif (strpos($colLower, 'nombre muestra') !== false || strpos($colLower, 'elemento') !== false || strpos($colLower, 'descripción') !== false || strpos($colLower, 'descripcion') !== false) {
                            $val = ($loteData['nombre_lote'] ?? '') . ' (' . ($esp['identificador_especimen'] ?? '') . ')';
                        } elseif (strpos($colLower, 'cilindro') !== false || strpos($colLower, 'especímen') !== false || strpos($colLower, 'especimen') !== false) {
                            $val = $esp['identificador_especimen'] ?? '';
                        } elseif (strpos($colLower, 'edad') !== false) {
                            $val = ($esp['edad_dias'] ?? '0') . ' días';
                        } elseif (strpos($colLower, 'fecha de fabricación') !== false || strpos($colLower, 'fabricacion') !== false || strpos($colLower, 'moldeo') !== false) {
                            $val = !empty($loteData['fecha_moldeo']) ? date('d/m/Y', strtotime($loteData['fecha_moldeo'])) : '';
                        } elseif (strpos($colLower, 'fecha programada') !== false || strpos($colLower, 'programada') !== false) {
                            $val = !empty($esp['fecha_programada']) ? date('d/m/Y', strtotime($esp['fecha_programada'])) : '—';
                        } elseif (strpos($colLower, 'fecha de ensayo') !== false || strpos($colLower, 'fecha de ruptura') !== false || strpos($colLower, 'ruptura') !== false || strpos($colLower, 'fecha ensaye') !== false || strpos($colLower, 'fecha de ensaye') !== false || strpos($colLower, 'ensaye real') !== false) {
                            $val = !empty($esp['fecha_ensaye_real']) ? date('d/m/Y', strtotime($esp['fecha_ensaye_real'])) : '—';
                        } elseif (strpos($colLower, 'carga') !== false) {
                            $val = $esp['carga_lbs'] ? number_format($esp['carga_lbs'], 1) : '—';
                        } elseif (strpos($colLower, 'área') !== false || strpos($colLower, 'area') !== false) {
                            $val = $esp['area_in2'] ? number_format($esp['area_in2'], 3) : '—';
                        } elseif (strpos($colLower, 'compresión (lb/in²)') !== false || strpos($colLower, 'compresión (psi)') !== false || strpos($colLower, 'psi') !== false || strpos($colLower, 'r. compresión') !== false || strpos($colLower, 'esfuerzo psi') !== false) {
                            $val = $esp['resistencia_psi'] ? number_format($esp['resistencia_psi'], 0) : '—';
                        } elseif (strpos($colLower, 'compresión (kg/cm²)') !== false || strpos($colLower, 'kg/cm²') !== false || strpos($colLower, 'resistencia.') !== false || strpos($colLower, 'compresión.') !== false || strpos($colLower, 'esfuerzo kg') !== false) {
                            $val = $esp['resistencia_kgcm2'] ? number_format($esp['resistencia_kgcm2'], 1) : '—';
                        } elseif (strpos($colLower, '%') !== false || strpos($colLower, 'porcentaje') !== false) {
                            $val = $esp['porcentaje_diseno'] ? number_format($esp['porcentaje_diseno'], 1) . '%' : '—';
                        } elseif (strpos($colLower, 'diseño') !== false || strpos($colLower, 'diseno') !== false) {
                            $val = $loteData['diseno_resistencia'] ?? '';
                        } elseif (strpos($colLower, 'reven.') !== false || strpos($colLower, 'slump') !== false) {
                            if (strpos($colLower, 'in') !== false) {
                                $val = $loteData['revenimiento_in'] ? $loteData['revenimiento_in'] . ' in' : '—';
                            } else {
                                $val = $loteData['revenimiento_cm'] ? $loteData['revenimiento_cm'] . ' cm' : '—';
                            }
                        } elseif (strpos($colLower, 'temp') !== false) {
                            $val = $loteData['temperatura_c'] ? $loteData['temperatura_c'] . ' °C' : '—';
                        } elseif (strpos($colLower, 'estado') !== false || strpos($colLower, 'cumple') !== false || strpos($colLower, 'alerta') !== false) {
                            if (($esp['estado'] ?? '') === 'Completado') {
                                $val = ($esp['cumple_norma'] ?? 0) ? 'Cumple' : 'Alerta';
                            } else {
                                $val = 'Pendiente';
                            }
                        }
                        
                        $fila[$col] = $val;
                    }
                    $filas[] = $fila;
                }
            } else {
                $resultadosGuardados = json_decode($detalle['resultados_json'] ?? '', true) ?: [];
                $filas = isset($resultadosGuardados['filas']) && is_array($resultadosGuardados['filas'])
                    ? $resultadosGuardados['filas']
                    : (array_is_list($resultadosGuardados) ? $resultadosGuardados : []);
            }

            // Versionado
            $edadFiltro = (int)($datos['edad_filtro'] ?? 0);
            $edadEvaluadaDb = ($tipoInforme === 'Parcial' && $edadFiltro > 0) ? $edadFiltro : null;

            $sqlVer = "SELECT MAX(version) FROM informes_control WHERE id_lote = :id_lote AND tipo_informe = :tipo_informe";
            $paramsVer = ['id_lote' => $idLote, 'tipo_informe' => $tipoInforme];
            if ($edadEvaluadaDb !== null) {
                $sqlVer .= " AND edad_evaluada = :edad";
                $paramsVer['edad'] = $edadEvaluadaDb;
            } else {
                $sqlVer .= " AND edad_evaluada IS NULL";
            }
            $stmtVer = $db->prepare($sqlVer);
            $stmtVer->execute($paramsVer);
            $maxVersion = $stmtVer->fetchColumn();
            
            $version = ($maxVersion === null) ? 0 : (int)$maxVersion + 1;
            $anio = date('Y');

            // Determinar código base
            if ($version > 0) {
                $sqlBase = "SELECT codigo_informe FROM informes_control WHERE id_lote = :id_lote AND tipo_informe = :tipo_informe";
                $paramsBase = ['id_lote' => $idLote, 'tipo_informe' => $tipoInforme];
                if ($edadEvaluadaDb !== null) {
                    $sqlBase .= " AND edad_evaluada = :edad";
                    $paramsBase['edad'] = $edadEvaluadaDb;
                } else {
                    $sqlBase .= " AND edad_evaluada IS NULL";
                }
                $sqlBase .= " ORDER BY version ASC LIMIT 1";
                $stmtBase = $db->prepare($sqlBase);
                $stmtBase->execute($paramsBase);
                $codigoInforme = $stmtBase->fetchColumn();
            } else {
                $stmtSec = $db->prepare("SELECT COUNT(DISTINCT codigo_informe) FROM informes_control WHERE YEAR(fecha_generacion) = :anio");
                $stmtSec->execute(['anio' => $anio]);
                $consecutivo = (int)$stmtSec->fetchColumn() + 1;
                $codigoInforme = sprintf("INF-%d-%04d", $anio, $consecutivo);
            }

            $codigoCompleto = sprintf("%s-%02d", $codigoInforme, $version);

            $observacionesSupervisor = trim($datos['observaciones_supervisor'] ?? '');
            $ocultarCumplimiento = !empty($datos['ocultar_columna_cumplimiento']) ? 1 : 0;

            // Generación real del PDF
            require_once dirname(__DIR__, 4) . '/app/Helpers/funciones.php';
            $pdfContenido = generarReporteEnsayoPDF($cotizacion, $detalle, $columnas, $filas, $codigoCompleto, $version, $observacionesSupervisor, $ocultarCumplimiento);

            // Guardar archivo PDF en disco
            $rutaCarpeta = dirname(__DIR__, 4) . '/almacenamiento/informes';
            if (!file_exists($rutaCarpeta)) {
                mkdir($rutaCarpeta, 0777, true);
            }
            $nombrePdf = $codigoCompleto . ".pdf";
            $rutaPdfFisica = $rutaCarpeta . '/' . $nombrePdf;
            file_put_contents($rutaPdfFisica, $pdfContenido);

            // Guardar registro en base de datos
            $modelo = new OperacionModelo();
            $rutaRelativa = 'almacenamiento/informes/' . $nombrePdf;
            $exitoId = $modelo->registrarInforme($idLote, $codigoInforme, $version, $codigoCompleto, $tipoInforme, $edadEvaluadaDb, $motivoReemplazo, $rutaRelativa, $observacionesSupervisor, $ocultarCumplimiento);

            if ($exitoId) {
                $_SESSION['exito'] = "Informe $codigoCompleto generado y versionado correctamente en PDF.";
            } else {
                $_SESSION['error'] = 'Error al registrar el informe generado.';
            }

            $redir = $datos['redireccionar_a'] ?? '/Cycsa/publico/operaciones/detalle-lote?id_lote=' . $idLote;
            $respuesta->redirigir($redir);
        }
    }

    /**
     * Permite a Coordinación revisar o aprobar un informe.
     */
    public function cambiarEstadoInforme(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idInforme = (int)($datos['id_informe'] ?? 0);
            $idLote = (int)($datos['id_lote'] ?? 0);
            $nuevoEstado = $datos['nuevo_estado'] ?? '';

            if ($idInforme <= 0 || $idLote <= 0 || !in_array($nuevoEstado, ['Revisado', 'Aprobado', 'Rechazado'])) {
                $_SESSION['error'] = 'Parámetros inválidos para cambiar estado de informe.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones/detalle-lote?id_lote=' . $idLote);
                return;
            }

            $modelo = new OperacionModelo();
            $exito = $modelo->cambiarEstadoInforme($idInforme, $nuevoEstado, $_SESSION['usuario_id']);

            if ($exito) {
                $_SESSION['exito'] = "El informe ha sido marcado como '$nuevoEstado' exitosamente.";
            } else {
                $_SESSION['error'] = 'Error al actualizar el estado de aprobación del informe.';
            }

            $redir = $datos['redireccionar_a'] ?? '/Cycsa/publico/operaciones/detalle-lote?id_lote=' . $idLote;
            $respuesta->redirigir($redir);
        }
    }

    public function descargarInforme(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (function_exists('esTecnicoCiegoISO17025') && esTecnicoCiegoISO17025()) {
            $_SESSION['error'] = 'Acceso restringido (ISO/IEC 17025): el perfil técnico no puede descargar informes con datos de cliente.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }
        
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            die("ID de informe no válido.");
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $stmt = $db->prepare("SELECT ic.*, cot.condicion_pago, cot.condicion_comercial, cot.codigo AS cot_codigo, os.forma_pago
                              FROM informes_control ic
                              JOIN lotes_muestras lm ON ic.id_lote = lm.id
                              JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                              JOIN ordenes_servicio os ON rm.id_os = os.id
                              JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                              WHERE ic.id = :id");
        $stmt->execute(['id' => $id]);
        $informe = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$informe) {
            die("Informe no encontrado.");
        }

        // Restricción de pago: Verificar autorización de pago / despacho unificada
        $authPago = verificarAutorizacionPagoEnvio($informe, $db);
        if (!$authPago['autorizado']) {
            $_SESSION['error'] = 'El cliente no ha pagado; no se pueden enviar resultados';
            $respuesta->redirigir('/Cycsa/publico/operaciones/detalle-lote?id_lote=' . $informe['id_lote']);
            return;
        }

        // Servir el archivo PDF asegurando contención en el directorio almacenamiento/ (anti-path traversal)
        $baseAlmacenamiento = realpath(dirname(__DIR__, 4) . '/almacenamiento');
        $rutaPdf = dirname(__DIR__, 4) . '/' . ltrim(str_replace('\\', '/', $informe['ruta_archivo_pdf']), '/');
        $rutaReal = realpath($rutaPdf);

        if ($baseAlmacenamiento && $rutaReal && strpos($rutaReal, $baseAlmacenamiento) === 0 && file_exists($rutaReal)) {
            if (!headers_sent()) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . basename($rutaReal) . '"');
            }
            readfile($rutaReal);
            if (!defined('PHPUNIT_RUNNING')) {
                exit;
            }
            return;
        } else {
            if (!defined('PHPUNIT_RUNNING')) {
                die("El archivo PDF físico del informe no se encuentra en el servidor o la ruta es inválida.");
            }
            return;
        }
    }

    private function obtenerColumnasFormato(?string $archivo_markdown, ?string $descripcion_ensayo = null): array {
        if (empty($archivo_markdown) && !empty($descripcion_ensayo)) {
            $desc = mb_strtolower($descripcion_ensayo, 'UTF-8');
            if ((strpos($desc, 'compresi') !== false && strpos($desc, 'concreto') !== false) || strpos($desc, 'cilindro') !== false) {
                $archivo_markdown = 'resistencia_de_concreto.md';
            } elseif (strpos($desc, 'dens') !== false || (strpos($desc, 'compacta') !== false && strpos($desc, 'nuclear') !== false)) {
                $archivo_markdown = 'compactacion_densimetro_nuclear.md';
            } elseif (strpos($desc, 'cono') !== false && strpos($desc, 'arena') !== false) {
                $archivo_markdown = 'formato_de_compactacion_por_cono_de_arena.md';
            } elseif (strpos($desc, 'granulo') !== false) {
                $archivo_markdown = 'analisis_granulometrico_agregados.md';
            }
        }
        $cols = obtenerEsquemaPlantillaEnsayo($archivo_markdown)['columns'] ?? [];
        if ((empty($cols) || count($cols) <= 6) && !empty($descripcion_ensayo)) {
            $desc = mb_strtolower($descripcion_ensayo, 'UTF-8');
            if ((strpos($desc, 'compresi') !== false && strpos($desc, 'concreto') !== false) || strpos($desc, 'cilindro') !== false) {
                $cols = [
                    "Código laboratorio", "Nombre muestra", "Área (in²)", "Reven. (in)", "Reven. (cm)",
                    "Temp. (°C)", "Carga (lb)", "Peso Volumétrico (kg/m³)", "Diseño de concreto (lb/in²)",
                    "R. Compresión (lb/in²)", "R. Compresión (kg/cm²)", "Fecha de Fabricación", "Fecha de Ensayo", "Edad (Días)"
                ];
            }
        }
        return $cols;
    }

    public function actualizarEstado(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idOS = (int)($datos['id_os'] ?? 0);
            $estado = $datos['estado'] ?? '';
            $motivo = !empty($datos['motivo_observacion']) ? $datos['motivo_observacion'] : null;
            $requiere = isset($datos['requiere_muestreo']) ? (int)$datos['requiere_muestreo'] : null;

            if ($idOS <= 0 || empty($estado)) {
                $_SESSION['error'] = 'Parámetros inválidos para cambiar el estado.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            // Validar que solo un Supervisor (Rol 3) o Administrador (Rol 1) realice aprobaciones/observaciones
            if (in_array($estado, ['Estado 3: Ingreso Directo', 'Estado 3A: Programacion Muestreo', 'Estado 2: Observada'])) {
                if (!in_array($_SESSION['usuario_rol'] ?? 0, [1, 3])) {
                    $_SESSION['error'] = 'No tiene permisos de supervisor para cambiar el estado de la Orden de Servicio.';
                    $respuesta->redirigir('/Cycsa/publico/operaciones');
                    return;
                }
            }

            $modelo = new OperacionModelo();
            if ($modelo->actualizarEstadoOS($idOS, $estado, $motivo, $requiere)) {
                $osInfo = $modelo->obtenerOSPorId($idOS);
                $codigoTexto = $osInfo ? ($osInfo['codigo_os'] . (!empty($osInfo['cliente_nombre']) ? ' (' . $osInfo['cliente_nombre'] . ')' : '')) : ('ID ' . $idOS);
                registrarEventoOS('operaciones', 'cambiar_estado', 'Orden de Servicio ' . $codigoTexto . ' cambiada al estado: ' . $estado, $idOS, ['datos' => ['estado' => $estado]]);
                $_SESSION['exito'] = 'Estado de la orden de servicio actualizado exitosamente.';
            } else {
                $_SESSION['error'] = 'Error al actualizar el estado de la orden de servicio.';
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones');
        }
    }

    public function procesarProgramarMuestreo(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idOS = (int)($datos['id_os'] ?? 0);
            $fecha = $datos['fecha_muestreo'] ?? '';
            $hora = $datos['hora_muestreo'] ?? '';
            $tecnico = $datos['tecnico_muestreo'] ?? '';
            $vehiculo = $datos['vehiculo_muestreo'] ?? '';

            if ($idOS <= 0 || empty($fecha) || empty($tecnico)) {
                $_SESSION['error'] = 'Debe indicar al menos la fecha y técnico asignado.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $modelo = new OperacionModelo();
            if ($modelo->programarMuestreo($idOS, $fecha, $hora, $tecnico, $vehiculo)) {
                $osInfo = $modelo->obtenerOSPorId($idOS);
                $codigoTexto = $osInfo ? ($osInfo['codigo_os'] . (!empty($osInfo['cliente_nombre']) ? ' (' . $osInfo['cliente_nombre'] . ')' : '')) : ('ID ' . $idOS);
                registrarEventoOS('operaciones', 'programar_muestreo', 'Muestreo programado para Orden de Servicio ' . $codigoTexto, $idOS);
                $_SESSION['exito'] = 'Programación de muestreo en campo guardada y estado actualizado.';
            } else {
                $_SESSION['error'] = 'Error al programar el muestreo.';
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones');
        }
    }

    public function guardarHojaCampo(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idOS = (int)($datos['id_os'] ?? 0);
            $codigo = $datos['hoja_campo_codigo'] ?? '';
            $operador = $datos['hoja_campo_operador'] ?? '';
            $notas = $datos['hoja_campo_notas'] ?? '';

            if ($idOS <= 0 || empty($codigo) || empty($operador)) {
                $_SESSION['error'] = 'Debe indicar el código de hoja de campo y operador.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $horasEspera = isset($datos['horas_espera_requeridas']) && $datos['horas_espera_requeridas'] !== '' ? (int)$datos['horas_espera_requeridas'] : 24;

            $modelo = new OperacionModelo();
            if ($modelo->registrarHojaCampo($idOS, $codigo, $operador, $notas, $horasEspera)) {
                $osInfo = $modelo->obtenerOSPorId($idOS);
                $codigoTexto = $osInfo ? ($osInfo['codigo_os'] . (!empty($osInfo['cliente_nombre']) ? ' (' . $osInfo['cliente_nombre'] . ')' : '')) : ('ID ' . $idOS);
                registrarEventoOS('operaciones', 'hoja_campo', 'Hoja de Campo registrada (CYCSA-RT-FM-07) para Orden de Servicio ' . $codigoTexto, $idOS);
                $_SESSION['exito'] = "Hoja de Campo CYCSA-RT-FM-07 guardada. Período de espera configurado a $horasEspera hora(s).";
            } else {
                $_SESSION['error'] = 'Error al guardar la hoja de campo.';
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones');
        }
    }

    public function omitirEsperaMuestreo(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idOS = (int)($datos['id_os'] ?? 0);

            if ($idOS <= 0) {
                $_SESSION['error'] = 'Orden de Servicio inválida.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $modelo = new OperacionModelo();
            if ($modelo->omitirEsperaMuestreo($idOS)) {
                $osInfo = $modelo->obtenerOSPorId($idOS);
                $codigoTexto = $osInfo ? ($osInfo['codigo_os'] . (!empty($osInfo['cliente_nombre']) ? ' (' . $osInfo['cliente_nombre'] . ')' : '')) : ('ID ' . $idOS);
                registrarEventoOS('operaciones', 'omitir_espera', 'Período de espera liberado/omitido por supervisión para ' . $codigoTexto, $idOS);
                $_SESSION['exito'] = 'Tiempo de espera liberado exitosamente. Las muestras ya pueden ser recepcionadas e ingresadas.';
            } else {
                $_SESSION['error'] = 'Error al liberar el tiempo de espera.';
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones');
        }
    }

    public function hojaSolicitudForm(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        $idOS = (int)($_GET['id_os'] ?? 0);
        if ($idOS <= 0) {
            $_SESSION['error'] = 'Orden de Servicio inválida.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $modelo = new OperacionModelo();
        $os = $modelo->obtenerOSPorId($idOS);
        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS);
        
        $anioActual = (int)date('Y');
        $prefijoMuestraOS = determinarPrefijoMuestraOS($os);
        $siguienteConsecutivo = $modelo->obtenerSiguienteConsecutivoMuestra($anioActual, $prefijoMuestraOS);
        $tecnicos = $modelo->obtenerTecnicosActivos();

        $detalles = $modelo->filtrarDetallesPermitidos($idOS, $modelo->obtenerDetallesCotizacion((int)$os['id_cotizacion']));
        $hsCtrl = new \Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador();
        $detectado = $hsCtrl->detectarParametrosYNaturaleza($detalles);

        $nombreCliente = !empty($os['cliente_nombre']) ? $os['cliente_nombre'] : '';
        $direccionProyecto = !empty($os['direccion_proyecto']) ? $os['direccion_proyecto'] : ($os['cliente_direccion'] ?? '');
        $atencionA = !empty($os['atencion_a']) ? $os['atencion_a'] : $nombreCliente;
        $procedenciaPunto = !empty($os['direccion_proyecto']) ? $os['direccion_proyecto'] : ($os['nombre_proyecto'] ?? '');

        // Obtener cliente y proyecto predeterminados de la O/S si es nueva hoja o si hay campos vacíos
        if (!$hoja) {
            $hoja = array_merge([
                'id_os' => $idOS,
                'nombre_empresa_o_cliente' => $nombreCliente,
                'razon_social' => $nombreCliente,
                'direccion_proyecto' => $direccionProyecto,
                'telefono' => $os['cliente_telefono'] ?? '',
                'correo_electronico' => $os['cliente_email'] ?? '',
                'nombre_persona_entrega_muestra' => $atencionA,
                'naturaleza_muestra' => $detectado['naturaleza_muestra_str'],
                'procedencia_punto_muestreo' => $procedenciaPunto,
                'nombre_persona_toma_muestra' => $os['tecnico_muestreo'] ?? '',
                'fecha_hora_toma_muestra' => !empty($os['fecha_muestreo']) ? $os['fecha_muestreo'] . ' ' . ($os['hora_muestreo'] ?: '08:00:00') : '',
                'muestras_json' => '[]',
                'analisis_adicionales' => '',
                'observaciones' => '',
                'nombre_recibe_cycsa' => $_SESSION['usuario_nombre'] ?? '',
                'firma_recibe_cycsa' => 0,
                'firma_cliente' => 0,
                'fecha_hora_llegada_laboratorio' => date('Y-m-d H:i')
            ], $detectado['flags']);
        } else {
            // Autocompletar datos del cliente si estaban vacíos
            if (empty($hoja['nombre_empresa_o_cliente'])) $hoja['nombre_empresa_o_cliente'] = $nombreCliente;
            if (empty($hoja['razon_social'])) $hoja['razon_social'] = $nombreCliente;
            if (empty($hoja['direccion_proyecto'])) $hoja['direccion_proyecto'] = $direccionProyecto;
            if (empty($hoja['telefono'])) $hoja['telefono'] = $os['cliente_telefono'] ?? '';
            if (empty($hoja['correo_electronico']) && !empty($os['cliente_email'])) $hoja['correo_electronico'] = $os['cliente_email'];
            if (empty($hoja['nombre_persona_entrega_muestra'])) $hoja['nombre_persona_entrega_muestra'] = $atencionA;
            if (empty($hoja['nombre_persona_toma_muestra']) && !empty($os['tecnico_muestreo'])) $hoja['nombre_persona_toma_muestra'] = $os['tecnico_muestreo'];
            if (empty($hoja['procedencia_punto_muestreo'])) $hoja['procedencia_punto_muestreo'] = $procedenciaPunto;
            if (empty($hoja['naturaleza_muestra'])) $hoja['naturaleza_muestra'] = $detectado['naturaleza_muestra_str'];
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('operaciones/vistas/hoja_solicitud_form', [
            'titulo' => 'Hoja de Solicitud de Servicio CYCSA-RT-FM-13',
            'os' => $os,
            'hoja' => $hoja,
            'tecnicos' => $tecnicos,
            'siguienteConsecutivo' => $siguienteConsecutivo,
            'prefijoMuestraOS' => $prefijoMuestraOS,
            'anioActual' => $anioActual
        ]);
    }

    public function guardarHojaSolicitud(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idOS = (int)($datos['id_os'] ?? 0);

            if ($idOS <= 0) {
                $_SESSION['error'] = 'Orden de Servicio inválida.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $modelo = new OperacionModelo();
            
            // Procesar tabla dinámica de especímenes
            $identMuestras = [];
            $mNombres = $datos['m_nombre'] ?? [];
            $mDescripciones = $datos['m_desc'] ?? [];
            $mInfos = $datos['m_info'] ?? [];
            
            for ($i = 0; $i < count($mNombres); $i++) {
                $nom = trim($mNombres[$i]);
                if (empty($nom)) continue;
                $identMuestras[] = [
                    'nombre_muestra' => $nom,
                    'descripcion' => trim($mDescripciones[$i] ?? ''),
                    'info_importante' => trim($mInfos[$i] ?? '')
                ];
            }
            $datos['identificacion_muestras_json'] = json_encode($identMuestras);

            $idHojaGuardada = 0;
            if ($modelo->guardarHojaSolicitud($datos, $idHojaGuardada)) {
                // Generar PDF y guardarlo en almacenamiento/solicitudes/
                $os = $modelo->obtenerOSPorId($idOS);
                $hoja = $modelo->obtenerHojaSolicitudPorId($idHojaGuardada) ?: $modelo->obtenerHojaSolicitudPorOS($idOS, $idHojaGuardada);
                
                require_once dirname(__DIR__, 4) . '/app/Helpers/funciones.php';
                $pdfContenido = generarHojaSolicitudPDF($hoja, $os);
                
                $dirPdf = dirname(__DIR__, 4) . '/almacenamiento/solicitudes';
                if (!file_exists($dirPdf)) {
                    mkdir($dirPdf, 0777, true);
                }

                // Guardar PDF individual por ID de hoja
                if (!empty($hoja['id'])) {
                    $nombrePdfHoja = "CYCSA-RT-FM-13-" . $os['codigo_os'] . "-H" . $hoja['id'] . ".pdf";
                    file_put_contents($dirPdf . '/' . $nombrePdfHoja, $pdfContenido);
                }

                // Guardar/Actualizar copia genérica para retrocompatibilidad
                $nombrePdf = "CYCSA-RT-FM-13-" . $os['codigo_os'] . ".pdf";
                file_put_contents($dirPdf . '/' . $nombrePdf, $pdfContenido);

                $numReg = $hoja['numero_registro'] ?? ('#' . ($hoja['id'] ?? ''));
                $codigoTexto = $os ? ($os['codigo_os'] . " (Hoja Reg: {$numReg})") : ('ID ' . $idOS);
                registrarEventoOS('operaciones', 'hoja_solicitud', 'Hoja de Solicitud CYCSA-RT-FM-13 guardada y PDF generado para Orden de Servicio ' . $codigoTexto, $idOS, [
                    'entidad_tipo' => 'rtfm13',
                    'entidad_id' => (int)($hoja['id'] ?? $idHojaGuardada),
                    'datos' => ['id_hoja' => (int)($hoja['id'] ?? $idHojaGuardada)],
                ]);
                $_SESSION['exito'] = 'Hoja de Solicitud de Servicio CYCSA-RT-FM-13 (Reg: ' . $numReg . ') guardada exitosamente y PDF generado.';
            } else {
                $_SESSION['error'] = 'Error al registrar la Hoja de Solicitud.';
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones');
        }
    }

    public function emitirSolicitud(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idOS = (int)($datos['id_os'] ?? 0);

            if ($idOS <= 0) {
                $_SESSION['error'] = 'Orden de Servicio inválida.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $modelo = new OperacionModelo();
            if ($modelo->actualizarEstadoOS($idOS, 'Estado 6: Ejecucion Ensayos')) {
                $osInfo = $modelo->obtenerOSPorId($idOS);
                $codigoTexto = $osInfo ? ($osInfo['codigo_os'] . (!empty($osInfo['cliente_nombre']) ? ' (' . $osInfo['cliente_nombre'] . ')' : '')) : ('ID ' . $idOS);
                registrarEventoOS('operaciones', 'emitir_solicitud', 'Solicitud emitida a técnicos para Orden de Servicio ' . $codigoTexto, $idOS, [
                    'entidad_tipo' => 'rtfm13',
                ]);
                $_SESSION['exito'] = 'Solicitud emitida a técnicos de laboratorio. Las muestras están disponibles para ejecución de ensayos.';
            } else {
                $_SESSION['error'] = 'Error al emitir la solicitud.';
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones');
        }
    }

    public function enviarRevisionResultados(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idOS = (int)($datos['id_os'] ?? 0);

            if ($idOS <= 0) {
                $_SESSION['error'] = 'Orden de Servicio inválida.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $modelo = new OperacionModelo();
            if ($modelo->actualizarEstadoOS($idOS, 'Estado 7: Revision Resultados')) {
                $osInfo = $modelo->obtenerOSPorId($idOS);
                $codigoTexto = $osInfo ? ($osInfo['codigo_os'] . (!empty($osInfo['cliente_nombre']) ? ' (' . $osInfo['cliente_nombre'] . ')' : '')) : ('ID ' . $idOS);
                registrarEventoOS('operaciones', 'enviar_revision_resultados', 'Resultados de ensayos enviados a revisión para Orden de Servicio ' . $codigoTexto, $idOS);
                $_SESSION['exito'] = 'Resultados enviados a revisión de calidad por el supervisor.';
            } else {
                $_SESSION['error'] = 'Error al enviar a revisión.';
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones');
        }
    }

    public function procesarRevisionResultados(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $rolActual = (int)($_SESSION['usuario_rol'] ?? 0);
            if (!in_array($rolActual, [1, 2, 3]) && !tienePermiso('operaciones', 'aprobar') && !tienePermiso('control_calidad', 'aprobar')) {
                $_SESSION['error'] = 'No tiene permisos de supervisor para realizar la revisión de calidad de resultados.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $datos = $peticion->obtenerDatos();
            $idOS = (int)($datos['id_os'] ?? 0);
            $decision = $datos['decision'] ?? '';
            $motivo = !empty($datos['motivo_observacion']) ? $datos['motivo_observacion'] : null;

            if ($idOS <= 0 || !in_array($decision, ['Aprobar', 'Rechazar'])) {
                $_SESSION['error'] = 'Decisión inválida para la revisión de resultados.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $modelo = new OperacionModelo();
            $osInfo = $modelo->obtenerOSPorId($idOS);
            $codigoTexto = $osInfo ? ($osInfo['codigo_os'] . (!empty($osInfo['cliente_nombre']) ? ' (' . $osInfo['cliente_nombre'] . ')' : '')) : ('ID ' . $idOS);
            
            if ($decision === 'Aprobar') {
                $exito = $modelo->actualizarEstadoOS($idOS, 'Estado 7: Revision Resultados', null);
                if ($exito) {
                    registrarEventoOS('operaciones', 'aprobar_revision_resultados', 'Revisión general de resultados aprobada para Orden de Servicio ' . $codigoTexto . '.', $idOS);
                    $_SESSION['exito'] = 'Revisión general aprobada. El cierre de la orden requiere matrices aprobadas y factura pagada.';
                }
            } else {
                if (empty($motivo)) {
                    $_SESSION['error'] = 'Debe indicar un motivo de rechazo si observa los resultados.';
                    $respuesta->redirigir('/Cycsa/publico/operaciones');
                    return;
                }
                $exito = $modelo->actualizarEstadoOS($idOS, 'Estado 6: Ejecucion Ensayos', $motivo);
                if ($exito) {
                    registrarEventoOS('operaciones', 'observar_resultados', 'Resultados de Orden de Servicio ' . $codigoTexto . ' observados: ' . $motivo, $idOS, [
                        'datos' => ['motivo' => $motivo],
                    ]);
                    $_SESSION['exito'] = 'Resultados rechazados y devueltos a ejecución de ensayos con la respectiva observación.';
                }
            }

            if (!$exito) {
                $_SESSION['error'] = 'Error al procesar la revisión de resultados.';
            }

            $redir = $this->resolverRedireccionSegura($datos['redirect_to'] ?? null, '/Cycsa/publico/operaciones');
            $respuesta->redirigir($redir);
        }
    }

    public function descargarSolicitudPDF(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (function_exists('esTecnicoCiegoISO17025') && esTecnicoCiegoISO17025()) {
            $_SESSION['error'] = 'Acceso restringido (ISO/IEC 17025): el perfil técnico no puede descargar solicitudes con datos de cliente.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }
        
        $idOS = (int)($_GET['id_os'] ?? 0);
        if ($idOS <= 0) {
            $_SESSION['error'] = 'Orden de Servicio inválida.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $modelo = new OperacionModelo();
        $os = $modelo->obtenerOSPorId($idOS);
        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $baseAlmacenamiento = realpath(dirname(__DIR__, 4) . '/almacenamiento');
        $codigoSanitizado = preg_replace('/[^a-zA-Z0-9_-]/', '_', $os['codigo_os']);
        $nombrePdf = "CYCSA-RT-FM-13-" . $codigoSanitizado . ".pdf";
        $rutaPdf = dirname(__DIR__, 4) . '/almacenamiento/solicitudes/' . $nombrePdf;
        $rutaReal = realpath($rutaPdf);

        if ($baseAlmacenamiento && $rutaReal && strpos($rutaReal, $baseAlmacenamiento) === 0 && file_exists($rutaReal)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($rutaReal) . '"');
            readfile($rutaReal);
            exit;
        } else {
            // Si el archivo no existe físicamente pero los datos están en BD, lo generamos al vuelo
            $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS);
            if ($hoja) {
                require_once dirname(__DIR__, 4) . '/app/Helpers/funciones.php';
                $pdfContenido = generarHojaSolicitudPDF($hoja, $os);
                
                // Guardarlo en almacenamiento para futuras descargas
                $dirPdf = dirname($rutaPdf);
                if (!file_exists($dirPdf)) {
                    mkdir($dirPdf, 0777, true);
                }
                file_put_contents($rutaPdf, $pdfContenido);
                
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . $nombrePdf . '"');
                echo $pdfContenido;
                exit;
            }
            
            $_SESSION['error'] = 'El PDF de la solicitud no ha sido generado y no se pudo crear.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
        }
    }

    public function hojaSolicitudDatosAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        $idOS = (int)($_GET['id_os'] ?? 0);
        if ($idOS <= 0) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Orden de Servicio inválida.']);
            return;
        }

        $modelo = new OperacionModelo();
        $os = $modelo->obtenerOSPorId($idOS);
        if (!$os) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Orden de Servicio no encontrada.']);
            return;
        }

        $idHoja = (int)($_GET['id_hoja'] ?? 0);
        $esNueva = !empty($_GET['nueva']);

        $todasLasHojas = $modelo->obtenerHojasSolicitudPorOS($idOS);
        $totalHojas = count($todasLasHojas);

        $listaHojasFrontend = [];
        foreach ($todasLasHojas as $idx => $hItem) {
            $cantM = !empty($hItem['identificacion_muestras_json']) 
                ? count(json_decode($hItem['identificacion_muestras_json'], true) ?: [])
                : (!empty($hItem['muestras_json']) ? count(json_decode($hItem['muestras_json'], true) ?: []) : 1);

            $listaHojasFrontend[] = [
                'id' => (int)$hItem['id'],
                'numero_secuencial' => $idx + 1,
                'numero_registro' => $hItem['numero_registro'] ?? ('#' . $hItem['id']),
                'codigo_documento' => $hItem['codigo_documento'] ?? 'CYCSA-RT-FM-13',
                'fecha_hora_toma' => $hItem['fecha_hora_toma_muestra'] ?? '',
                'total_muestras' => $cantM
            ];
        }

        if ($esNueva) {
            $hoja = null;
        } elseif ($idHoja > 0) {
            $hoja = $modelo->obtenerHojaSolicitudPorId($idHoja);
            if (!$hoja || (int)$hoja['id_os'] !== $idOS) {
                $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS);
            }
        } else {
            $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS);
        }
        
        // Si no existe, creamos los valores predeterminados
        if (!$hoja) {
            $numCorrelativo = $modelo->obtenerSiguienteNumeroHojaSolicitud((int)$os['id_cotizacion']);
            $codigoDoc = "CYCSA-RT-FM-" . sprintf("%02d", $numCorrelativo);
            $nuevoNumReg = $modelo->obtenerSiguienteNumeroRegistroGlobal();
            
            $hoja = [
                'id' => 0,
                'id_os' => $idOS,
                'numero_registro' => $nuevoNumReg,
                'codigo_documento' => $codigoDoc,
                'nombre_empresa_o_cliente' => $os['cliente_nombre'],
                'direccion_proyecto' => $os['direccion_proyecto'],
                'telefono' => $os['cliente_telefono'],
                'correo_electronico' => '',
                'nombre_persona_entrega_muestra' => '',
                'naturaleza_muestra' => 'Concreto',
                'procedencia_punto_muestreo' => '',
                'nombre_persona_toma_muestra' => $os['tecnico_muestreo'] ?? '',
                'fecha_hora_toma_muestra' => !empty($os['fecha_muestreo']) ? $os['fecha_muestreo'] . ' ' . ($os['hora_muestreo'] ?: '08:00:00') : '',
                'muestras_json' => '[]',
                'req_resistencia_concreto' => 1,
                'req_resistencia_adoquin' => 0,
                'req_resistencia_bloques' => 0,
                'req_otros_concreto' => '',
                'req_granulometria' => 0,
                'req_limites_atterberg' => 0,
                'req_humedad' => 0,
                'req_resistencia_corte' => 0,
                'req_clasificacion_sucs_hr' => 0,
                'req_proctor_sm' => 0,
                'req_infiltracion' => 0,
                'req_cbr' => 0,
                'req_densidad' => 0,
                'req_otros_suelo' => '',
                'req_otros_materiales' => 0,
                'descripcion_otros_analisis' => '',
                'analisis_adicionales' => '',
                'observaciones' => '',
                'nombre_recibe_cycsa' => '',
                'firma_recibe_cycsa' => 0,
                'firma_cliente' => 0,
                'fecha_hora_llegada_laboratorio' => date('Y-m-d H:i')
            ];
        } else {
            // Asegurarse de formatear fechas para inputs datetime-local
            if (!empty($hoja['fecha_hora_llegada_laboratorio'])) {
                $hoja['fecha_hora_llegada_laboratorio'] = date('Y-m-d\TH:i', strtotime($hoja['fecha_hora_llegada_laboratorio']));
            }
            if (!empty($hoja['fecha_hora_toma_muestra'])) {
                $hoja['fecha_hora_toma_muestra'] = date('Y-m-d\TH:i', strtotime($hoja['fecha_hora_toma_muestra']));
            }
        }

        $anioActual = (int)date('Y');
        $prefijoMuestraOS = determinarPrefijoMuestraOS($os);
        $siguienteConsecutivo = $modelo->obtenerSiguienteConsecutivoMuestra($anioActual, $prefijoMuestraOS);

        $respuesta->enviarJson([
            'status' => 'success',
            'os' => $os,
            'hoja' => $hoja,
            'todas_las_hojas' => $listaHojasFrontend,
            'total_hojas' => $totalHojas,
            'id_hoja_actual' => (int)($hoja['id'] ?? 0),
            'es_nueva' => $esNueva,
            'siguiente_consecutivo' => $siguienteConsecutivo,
            'anio_actual' => $anioActual
        ]);
    }

    public function ver(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (function_exists('esTecnicoCiegoISO17025') && esTecnicoCiegoISO17025()) {
            $respuesta->redirigir('/Cycsa/publico/captura-tecnica');
            return;
        }
        $this->verificarPermiso($respuesta, 'ver');

        $idOS = (int)($_GET['id_os'] ?? ($_GET['id'] ?? 0));
        if ($idOS <= 0) {
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $modelo = new OperacionModelo();
        $orden = method_exists($modelo, 'obtenerOSPorId') ? $modelo->obtenerOSPorId($idOS) : $modelo->obtenerPorId($idOS);
        if (!$orden) {
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $db = Conexion::obtenerInstancia();
        $orden = $this->enriquecerOrdenBaseOperacion($db, $modelo, $orden);
        $orden = array_merge($orden, $this->construirStepperDeterministicoOS($db, $orden, $orden['items'], $orden['hojas_solicitud']));
        $orden = $this->completarEstadoComercialOS($orden);

        $informeLabServicio = new InformeLaboratorioServicio();
        $informesUnificados = $informeLabServicio->obtenerInformesUnificadosOS($idOS);
        $consolidadoLab = $informeLabServicio->obtenerConsolidadoMultiMuestra($idOS);
        $historialVersionesLab = $informeLabServicio->obtenerHistorialVersiones($idOS);

        $this->renderizar('operaciones/vistas/ver', [
            'titulo' => 'Detalle de Operación - ' . $orden['codigo_os'],
            'o' => $orden,
            'orden' => $orden,
            'id_os' => $idOS,
            'informes_unificados' => $informesUnificados,
            'consolidado_lab' => $consolidadoLab,
            'historial_versiones_lab' => $historialVersionesLab,
            'tecnicos' => $modelo->obtenerTecnicosActivos(),
            'vehiculos' => $modelo->obtenerVehiculosActivos(),
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
            'bitacora_logs' => obtenerBitacoraModulo('operaciones')
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    public function detalleAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $idOS = (int)($_GET['id_os'] ?? ($_GET['id'] ?? 0));
        if ($idOS <= 0) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'ID de orden inválido']);
            return;
        }
        $modelo = new OperacionModelo();
        $orden = $modelo->obtenerPorId($idOS);
        if (!$orden) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Orden de servicio no encontrada']);
            return;
        }

        $db = Conexion::obtenerInstancia();
        $orden = $this->enriquecerOrdenBaseOperacion($db, $modelo, $orden);
        $orden = array_merge($orden, $this->construirStepperDeterministicoOS($db, $orden, $orden['items'], $orden['hojas_solicitud']));
        $orden = $this->completarEstadoComercialOS($orden);

        $respuesta->enviarJson([
            'status' => 'success',
            'orden' => $orden,
            'items' => $orden['items'],
            'hojas' => $orden['hojas_solicitud'],
        ]);
    }

    /**
     * Construye de forma determinística el stepper de 5 pasos, contadores reales,
     * pill de etapa, metadatos del panel lateral y siguiente acción de la O/S.
     */
    public function construirStepperDeterministicoOS(PDO $db, array $os, array $items = [], array $hojas = []): array {
        $idOS = (int)($os['id'] ?? 0);

        if (empty($items) && !empty($os['items']) && is_array($os['items'])) {
            $items = $os['items'];
        }
        if (empty($hojas)) {
            if (!empty($os['hojas_solicitud']) && is_array($os['hojas_solicitud'])) {
                $hojas = $os['hojas_solicitud'];
            } elseif (!empty($os['hoja_solicitud']) && is_array($os['hoja_solicitud'])) {
                $hojas = [$os['hoja_solicitud']];
            }
        }

        $totalHojas = count($hojas);
        $hojasRecepcionCompleta = 0;
        $idHojaPrincipal = 0;
        $idHojaIncompleta = 0;
        $mapaHojas = [];

        foreach ($hojas as $h) {
            if (!is_array($h)) {
                continue;
            }
            $hid = (int)($h['id'] ?? 0);
            if ($hid > 0) {
                $mapaHojas[$hid] = $h;
                $idHojaPrincipal = $hid;
            }
            $etapaH = trim((string)($h['etapa_flujo'] ?? ''));
            $recH = !empty($h['recepcion_completa'])
                || in_array($etapaH, ['recepcion_completa', 'en_ejecucion', 'tecnico_completo', 'matriz_en_revision', 'informe'], true);
            if ($recH) {
                $hojasRecepcionCompleta++;
            } elseif ($hid > 0 && $idHojaIncompleta === 0) {
                $idHojaIncompleta = $hid;
            }
        }
        if ($idHojaPrincipal === 0 && !empty($os['hoja_solicitud']['id'])) {
            $idHojaPrincipal = (int)$os['hoja_solicitud']['id'];
        }

        $totalMatrices = count($items);
        $matricesAprobadas = 0;
        $matricesCapturadas = 0;
        $matricesEnRevision = 0;
        $matricesDevueltas = 0;
        $ensayosConResultados = 0;

        $primerDetalleId = null;
        $primerDetalleRevisionId = null;
        $primerDetallePendienteId = null;
        $primerDetalleDevueltoId = null;
        $nombreEnsayoRevision = '';
        $nombreEnsayoPendiente = '';
        $nombreEnsayoDevuelto = '';

        foreach ($items as $it) {
            if (!is_array($it)) {
                continue;
            }
            $itId = (int)($it['id'] ?? ($it['id_detalle'] ?? 0));
            $itNombre = trim((string)($it['descripcion_ensayo'] ?? ''));
            if ($itId > 0 && $primerDetalleId === null) {
                $primerDetalleId = $itId;
            }

            $revInfo = (isset($it['revision_info']) && is_array($it['revision_info']))
                ? $it['revision_info']
                : (function_exists('obtenerEstadoRevisionMatriz')
                    ? obtenerEstadoRevisionMatriz($it['resultados_json'] ?? null)
                    : ['estado' => 'pendiente', 'tiene_resultados' => false]);

            $estadoRev = (string)($revInfo['estado'] ?? 'pendiente');
            if (!empty($revInfo['tiene_resultados'])) {
                $ensayosConResultados++;
            }

            if ($estadoRev === 'aprobada') {
                $matricesAprobadas++;
                $matricesCapturadas++;
            } elseif ($estadoRev === 'en_revision') {
                $matricesEnRevision++;
                $matricesCapturadas++;
                if ($itId > 0 && $primerDetalleRevisionId === null) {
                    $primerDetalleRevisionId = $itId;
                    $nombreEnsayoRevision = $itNombre;
                }
            } elseif ($estadoRev === 'devuelta') {
                $matricesDevueltas++;
                if ($itId > 0 && $primerDetalleDevueltoId === null) {
                    $primerDetalleDevueltoId = $itId;
                    $nombreEnsayoDevuelto = $itNombre;
                }
                if ($itId > 0 && $primerDetallePendienteId === null) {
                    $primerDetallePendienteId = $itId;
                    $nombreEnsayoPendiente = $itNombre;
                }
            } else {
                if ($estadoRev === 'borrador' && $this->matrizTieneResultadosTecnicosCapturados($it['resultados_json'] ?? null, (string)($it['archivo_markdown'] ?? ''))) {
                    $matricesCapturadas++;
                }
                if ($itId > 0 && $primerDetallePendienteId === null) {
                    $primerDetallePendienteId = $itId;
                    $nombreEnsayoPendiente = $itNombre;
                }
            }
        }

        $totalProbetas = 0;
        $probetasRotas = 0;
        $idHojaProbetasPendientes = 0;
        if ($idOS > 0) {
            try {
                $stmtProb = $db->prepare("
                    SELECT COALESCE(rm.id_hoja, 0) AS id_hoja,
                           COUNT(DISTINCT esp.id) AS total_probetas,
                           COUNT(DISTINCT CASE WHEN esp.estado = 'ensayado' OR rot.id IS NOT NULL THEN esp.id END) AS probetas_rotas
                    FROM especimenes esp
                    JOIN recepcion_muestras rm ON esp.muestra_id = rm.id
                    LEFT JOIN hojas_solicitud hs ON rm.id_hoja = hs.id
                    LEFT JOIN roturas rot ON rot.especimen_id = esp.id
                    WHERE (rm.id_os = :id_os1 OR hs.id_os = :id_os2)
                      AND (esp.estado IS NULL OR esp.estado != 'testigo')
                    GROUP BY COALESCE(rm.id_hoja, 0)
                    ORDER BY id_hoja ASC
                ");
                $stmtProb->execute(['id_os1' => $idOS, 'id_os2' => $idOS]);
                foreach ($stmtProb->fetchAll(PDO::FETCH_ASSOC) as $rowProb) {
                    $tp = (int)($rowProb['total_probetas'] ?? 0);
                    $pr = (int)($rowProb['probetas_rotas'] ?? 0);
                    $hidP = (int)($rowProb['id_hoja'] ?? 0);
                    $totalProbetas += $tp;
                    $probetasRotas += $pr;
                    if ($idHojaProbetasPendientes === 0 && $hidP > 0 && $pr < $tp) {
                        $idHojaProbetasPendientes = $hidP;
                    }
                }
            } catch (\Throwable $e) {
            }
        }
        if ($totalProbetas === 0 && isset($os['total_probetas'])) {
            $totalProbetas = max(0, (int)$os['total_probetas']);
            $probetasRotas = max(0, (int)($os['probetas_rotas'] ?? 0));
        }

        $informesEmitidos = 0;
        $cierreEmitido = in_array(trim((string)($os['estado'] ?? '')), ['Finalizado', 'Archivado', 'Cerrado'], true);
        if ($idOS > 0) {
            try {
                $stmtIr = $db->prepare("
                    SELECT COUNT(DISTINCT ir.id)
                    FROM informes_rotura ir
                    JOIN recepcion_muestras rm ON ir.muestra_id = rm.id
                    LEFT JOIN hojas_solicitud hs ON rm.id_hoja = hs.id
                    WHERE (rm.id_os = :id_os1 OR hs.id_os = :id_os2)
                      AND ir.estado = 'aprobado'
                ");
                $stmtIr->execute(['id_os1' => $idOS, 'id_os2' => $idOS]);
                $informesEmitidos += (int)$stmtIr->fetchColumn();
            } catch (\Throwable $e) {
            }

            try {
                $stmtIc = $db->prepare("
                    SELECT COUNT(DISTINCT ic.id)
                    FROM informes_control ic
                    JOIN lotes_muestras lm ON ic.id_lote = lm.id
                    JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                    LEFT JOIN hojas_solicitud hs ON rm.id_hoja = hs.id
                    WHERE (rm.id_os = :id_os1 OR hs.id_os = :id_os2)
                      AND ic.estado_aprobacion = 'Aprobado'
                ");
                $stmtIc->execute(['id_os1' => $idOS, 'id_os2' => $idOS]);
                $informesEmitidos += (int)$stmtIc->fetchColumn();
            } catch (\Throwable $e) {
            }

            try {
                $stmtBit = $db->prepare("
                    SELECT COUNT(*)
                    FROM bitacora
                    WHERE id_os = :id_os
                      AND modulo = 'operaciones'
                      AND accion IN ('enviar_matriz_cliente', 'cerrar_orden')
                ");
                $stmtBit->execute(['id_os' => $idOS]);
                $cntBit = (int)$stmtBit->fetchColumn();
                if ($cntBit > 0) {
                    $informesEmitidos += $cntBit;
                    $cierreEmitido = true;
                }
            } catch (\Throwable $e) {
            }
        }
        if ($informesEmitidos === 0 && isset($os['informes_emitidos'])) {
            $informesEmitidos = max(0, (int)$os['informes_emitidos']);
        }

        $esSoloComp = !empty($items) && function_exists('esOrdenSoloCompactacion')
            ? esOrdenSoloCompactacion($items)
            : false;

        // Evaluación determinística de los 5 pasos
        $paso1Hecho = ($totalHojas > 0);
        $paso1Texto = $paso1Hecho ? 'Emitida' : 'Pendiente';

        $paso2Hecho = ($totalHojas > 0) && ($hojasRecepcionCompleta >= $totalHojas || $esSoloComp);
        $paso2Texto = $paso2Hecho
            ? 'Completa'
            : ($totalHojas > 0 ? "{$hojasRecepcionCompleta} de {$totalHojas} hojas" : 'Pendiente');

        $paso3Hecho = $paso2Hecho && (
            $totalProbetas > 0
                ? ($probetasRotas >= $totalProbetas)
                : ($matricesCapturadas >= $totalMatrices && $totalMatrices > 0)
        );
        $paso3Texto = $paso3Hecho
            ? 'Completa'
            : ($totalProbetas > 0
                ? "{$probetasRotas} de {$totalProbetas} probetas"
                : "{$matricesCapturadas} de {$totalMatrices} matrices");

        $paso4Hecho = $paso3Hecho && ($totalMatrices > 0 && $matricesAprobadas >= $totalMatrices);
        $paso4Texto = $paso4Hecho
            ? 'Aprobada'
            : "{$matricesAprobadas} de {$totalMatrices} aprobadas";

        // REGLA ESTRICTA: Nunca se marca verde 'Informe' mientras haya matrices sin aprobar
        $paso5Hecho = $paso4Hecho
            && ($totalMatrices > 0 && $matricesAprobadas >= $totalMatrices)
            && ($informesEmitidos > 0 || $cierreEmitido);
        $paso5Texto = $paso5Hecho ? 'Emitido' : 'Pendiente';

        $stepperPasos = [
            [
                'numero' => 1,
                'clave' => 'rt_fm_13',
                'titulo' => 'RT-FM-13',
                'label' => 'RT-FM-13',
                'hecho' => $paso1Hecho,
                'estado_texto' => $paso1Texto,
                'clase' => '',
            ],
            [
                'numero' => 2,
                'clave' => 'recepcion',
                'titulo' => 'Recepción',
                'label' => 'Recepción',
                'hecho' => $paso2Hecho,
                'estado_texto' => $paso2Texto,
                'clase' => '',
            ],
            [
                'numero' => 3,
                'clave' => 'ejecucion',
                'titulo' => 'Ejecución',
                'label' => 'Ejecución',
                'hecho' => $paso3Hecho,
                'estado_texto' => $paso3Texto,
                'clase' => '',
            ],
            [
                'numero' => 4,
                'clave' => 'revision',
                'titulo' => 'Revisión',
                'label' => 'Revisión',
                'hecho' => $paso4Hecho,
                'estado_texto' => $paso4Texto,
                'clase' => '',
            ],
            [
                'numero' => 5,
                'clave' => 'informe',
                'titulo' => 'Informe',
                'label' => 'Informe',
                'hecho' => $paso5Hecho,
                'estado_texto' => $paso5Texto,
                'clase' => '',
            ],
        ];

        $todosAnterioresHechos = true;
        $currentAsignado = false;
        foreach ($stepperPasos as &$paso) {
            if ($todosAnterioresHechos && $paso['hecho']) {
                $paso['clase'] = 'is-done';
            } elseif (!$currentAsignado) {
                $paso['clase'] = 'is-current';
                $currentAsignado = true;
                $todosAnterioresHechos = false;
            } else {
                $paso['clase'] = '';
                $todosAnterioresHechos = false;
            }
        }
        unset($paso);

        // Estado agregado de etapa, pill, bandeja y siguiente acción
        $tecnico100 = ($totalMatrices > 0 && $matricesAprobadas === $totalMatrices);
        $urlVerOS = $idOS > 0 ? "/Cycsa/publico/operaciones/ver?id_os={$idOS}" : '/Cycsa/publico/operaciones';

        if ($matricesDevueltas > 0 && !$paso4Hecho) {
            $pillEtapaClase = 'cy-pill-danger';
            $pillEtapaTexto = 'Devuelto';
            $etapaFlujoOS = 'en_ejecucion';
            $bandejaRol = 'tecnico';
            $targetDet = $primerDetalleDevueltoId ?? $primerDetallePendienteId ?? $primerDetalleId;
            $sigAccionTitulo = 'Corregir matriz devuelta';
            $sigAccionSubtitulo = $nombreEnsayoDevuelto !== ''
                ? "Matriz observada por supervisión en {$nombreEnsayoDevuelto}; corregir y reenviar a revisión."
                : 'Matriz observada por supervisión; corregir y reenviar a revisión.';
            $sigAccionBotonTexto = 'Corregir matriz';
            $sigAccionUrl = $targetDet
                ? "/Cycsa/publico/operaciones/captura-matriz?id_detalle={$targetDet}"
                : $urlVerOS;
            $sigPasoTexto = 'Corregir Matriz Devuelta';
            $sigPasoUrl = $sigAccionUrl;
        } elseif (!$paso1Hecho) {
            $pillEtapaClase = 'cy-pill-pend';
            $pillEtapaTexto = 'Recepción en borrador';
            $etapaFlujoOS = 'sin_hoja';
            $bandejaRol = 'recepcion';
            $sigAccionTitulo = 'Emitir hoja RT-FM-13';
            $sigAccionSubtitulo = 'La orden aún no tiene hoja de solicitud de servicio RT-FM-13 registrada.';
            $sigAccionBotonTexto = 'Crear RT-FM-13';
            $sigAccionUrl = $idOS > 0 ? "/Cycsa/publico/ordenes-servicio?id_os={$idOS}" : '/Cycsa/publico/ordenes-servicio';
            $sigPasoTexto = 'Completar Recepción (RT-FM-13)';
            $sigPasoUrl = $sigAccionUrl;
        } elseif (!$paso2Hecho) {
            $pillEtapaClase = 'cy-pill-pend';
            $pillEtapaTexto = 'Recepción en borrador';
            $etapaFlujoOS = 'recepcion_borrador';
            $bandejaRol = 'recepcion';
            $idHojaRec = $idHojaIncompleta > 0 ? $idHojaIncompleta : $idHojaPrincipal;
            $sigAccionTitulo = 'Completar datos de recepción';
            $sigAccionSubtitulo = "{$hojasRecepcionCompleta} de {$totalHojas} hojas RT-FM-13 con recepción completa.";
            $sigAccionBotonTexto = 'Completar recepción';
            $sigAccionUrl = $idOS > 0
                ? ('/Cycsa/publico/ordenes-servicio?id_os=' . $idOS . ($idHojaRec > 0 ? '&id_hoja=' . $idHojaRec : ''))
                : '/Cycsa/publico/ordenes-servicio';
            $sigPasoTexto = 'Completar Recepción';
            $sigPasoUrl = $sigAccionUrl;
        } elseif (!$paso3Hecho) {
            $pillEtapaClase = 'cy-pill-warn';
            $pillEtapaTexto = 'En captura técnica';
            $etapaFlujoOS = 'en_ejecucion';
            $bandejaRol = 'tecnico';
            $targetDet = $primerDetallePendienteId ?? $primerDetalleId;
            $sigAccionTitulo = 'Documento en captura técnica';
            $sigAccionSubtitulo = $nombreEnsayoPendiente !== ''
                ? "En espera de datos técnicos para {$nombreEnsayoPendiente} ({$matricesCapturadas} de {$totalMatrices} documentos con captura)."
                : "{$matricesCapturadas} de {$totalMatrices} documentos con datos técnicos registrados.";
            $sigAccionBotonTexto = 'Ver documento';
            $sigAccionUrl = $targetDet
                ? "/Cycsa/publico/operaciones/documento?id_detalle={$targetDet}"
                : $urlVerOS;
            $sigPasoTexto = 'Ver documento';
            $sigPasoUrl = $sigAccionUrl;
        } elseif (!$paso4Hecho) {
            if ($matricesEnRevision > 0) {
                $pillEtapaClase = 'cy-pill-warn';
                $pillEtapaTexto = 'Documento en revisión';
                $etapaFlujoOS = 'matriz_en_revision';
                $bandejaRol = 'supervisor';
                $targetDet = $primerDetalleRevisionId ?? $primerDetalleId;
                $sigAccionTitulo = $nombreEnsayoRevision !== ''
                    ? "Documento listo para revisión: {$nombreEnsayoRevision}"
                    : 'Documento listo para revisión';
                $sigAccionSubtitulo = "{$matricesAprobadas} de {$totalMatrices} documentos aprobados ({$matricesEnRevision} listo para revisar).";
                $sigAccionBotonTexto = 'Revisar documento';
                $sigAccionUrl = $targetDet
                    ? "/Cycsa/publico/operaciones/documento?id_detalle={$targetDet}"
                    : $urlVerOS;
                $sigPasoTexto = 'Revisar documento';
                $sigPasoUrl = $sigAccionUrl;
            } else {
                $pillEtapaClase = 'cy-pill-warn';
                $pillEtapaTexto = 'En captura técnica';
                $etapaFlujoOS = ($matricesCapturadas >= $totalMatrices && $totalMatrices > 0) ? 'tecnico_completo' : 'en_ejecucion';
                $bandejaRol = 'tecnico';
                $targetDet = $primerDetallePendienteId ?? $primerDetalleId;
                $sigAccionTitulo = 'Documento en proceso técnico';
                $sigAccionSubtitulo = "{$matricesCapturadas} de {$totalMatrices} documentos con captura ({$matricesAprobadas} aprobados).";
                $sigAccionBotonTexto = 'Ver documento';
                $sigAccionUrl = $targetDet
                    ? "/Cycsa/publico/operaciones/documento?id_detalle={$targetDet}"
                    : $urlVerOS;
                $sigPasoTexto = 'Ver documento';
                $sigPasoUrl = $sigAccionUrl;
            }
        } else {
            $pillEtapaClase = 'cy-pill-ok';
            $pillEtapaTexto = 'Documento listo';
            $etapaFlujoOS = 'informe';
            $bandejaRol = 'informes';
            $urlInforme = $primerDetalleId
                ? "/Cycsa/publico/operaciones/documento?id_detalle={$primerDetalleId}"
                : $urlVerOS;
            if ($paso5Hecho) {
                $sigAccionTitulo = $cierreEmitido && in_array(trim((string)($os['estado'] ?? '')), ['Finalizado', 'Archivado', 'Cerrado'], true)
                    ? 'Orden finalizada y archivada'
                    : 'Documentos listos para ver';
                $sigAccionSubtitulo = "Los {$totalMatrices} documentos están aprobados y completos con todos los datos.";
                $sigAccionBotonTexto = 'Ver documento';
            } else {
                $sigAccionTitulo = 'Documentos listos para ver y entregar';
                $sigAccionSubtitulo = "Los {$totalMatrices} documentos están aprobados y listos para imprimir o enviar al cliente.";
                $sigAccionBotonTexto = 'Ver documento';
            }
            $sigAccionUrl = $urlInforme;
            $sigPasoTexto = 'Ver documento';
            $sigPasoUrl = $urlInforme;
        }

        $porcentajeMatrices = $totalMatrices > 0
            ? min(100, max(0, (int)round(($matricesCapturadas / $totalMatrices) * 100)))
            : 0;
        $avanceMatricesTexto = "{$matricesCapturadas} de {$totalMatrices} matrices";

        $clienteNombre = trim((string)($os['cliente_nombre'] ?? ($os['cliente_informe_nombre'] ?? 'Cliente'))) ?: 'Cliente';
        $nombreProyecto = trim((string)($os['nombre_proyecto'] ?? '')) ?: 'Sin proyecto';
        $codigoCotizacion = trim((string)($os['cot_codigo'] ?? ($os['codigo_cotizacion'] ?? ''))) ?: 'Sin cotización';
        $condicionPago = trim((string)($os['condicion_pago'] ?? ($os['forma_pago'] ?? '')));
        if ($condicionPago === '') {
            $condComRaw = strtolower(trim((string)($os['condicion_comercial'] ?? 'contado')));
            $condicionPago = $condComRaw === 'credito' ? 'Crédito' : 'Contado';
        }
        $lineaMetaPanel = (string)preg_replace('/\s+/', ' ', "{$clienteNombre} · {$nombreProyecto} · {$codigoCotizacion} · {$condicionPago}");

        return [
            'stepper_pasos' => $stepperPasos,
            'matrices_aprobadas_count' => (int)$matricesAprobadas,
            'matrices_capturadas_count' => (int)$matricesCapturadas,
            'matrices_total_count' => (int)$totalMatrices,
            'porcentaje_matrices' => $porcentajeMatrices,
            'avance_matrices_texto' => $avanceMatricesTexto,
            'probetas_rotas_count' => (int)$probetasRotas,
            'probetas_total_count' => (int)$totalProbetas,
            'hojas_completas_count' => (int)$hojasRecepcionCompleta,
            'hojas_total_count' => (int)$totalHojas,
            'informes_emitidos_count' => (int)$informesEmitidos,
            'pill_etapa_clase' => $pillEtapaClase,
            'pill_etapa_texto' => $pillEtapaTexto,
            'linea_meta_panel' => $lineaMetaPanel,
            'siguiente_accion_titulo' => $sigAccionTitulo,
            'siguiente_accion_subtitulo' => $sigAccionSubtitulo,
            'siguiente_accion_boton_texto' => $sigAccionBotonTexto,
            'siguiente_accion_url' => $sigAccionUrl,
            // Claves previas preservadas para retrocompatibilidad con tests y vistas
            'total_ensayos' => (int)$totalMatrices,
            'ensayos_aprobados' => (int)$matricesAprobadas,
            'ensayos_con_resultados' => (int)$ensayosConResultados,
            'ensayos_en_revision' => (int)$matricesEnRevision,
            'ensayos_devueltos' => (int)$matricesDevueltas,
            'tecnico_100' => $tecnico100,
            'etapa_flujo_os' => $etapaFlujoOS,
            'recepcion_completa_os' => $paso2Hecho ? 1 : 0,
            'bandeja_rol' => $bandejaRol,
            'siguiente_paso_texto' => $sigPasoTexto,
            'siguiente_paso_url' => $sigPasoUrl,
        ];
    }

    private function enriquecerOrdenBaseOperacion(PDO $db, OperacionModelo $modelo, array $orden, ?array $cxcMap = null): array {
        $idOS = (int)($orden['id'] ?? 0);
        $items = $idOS > 0 ? $modelo->obtenerItemsOS($idOS) : [];
        $items = array_values(array_filter($items, static function ($it): bool {
            return !is_array($it) || !array_key_exists('permitido', $it) || (int)$it['permitido'] === 1;
        }));
        foreach ($items as &$it) {
            $it['revision_info'] = obtenerEstadoRevisionMatriz($it['resultados_json'] ?? null);
        }
        unset($it);

        $orden['items'] = $items;
        $orden['hoja_solicitud'] = $idOS > 0 ? $modelo->obtenerHojaSolicitudPorOS($idOS) : null;
        $orden['hojas_solicitud'] = $idOS > 0 ? $modelo->obtenerHojasSolicitudPorOS($idOS) : [];

        $muestrasAceptadas = 0;
        if ($idOS > 0) {
            $stmtRecCount = $db->prepare("SELECT COUNT(*) FROM recepcion_muestras WHERE id_os = :id_os");
            $stmtRecCount->execute(['id_os' => $idOS]);
            $muestrasAceptadas = (int)$stmtRecCount->fetchColumn();
        }
        $orden['muestras_aceptadas_lab'] = $muestrasAceptadas;

        $facturaNum = 'FAC-' . ($orden['cot_codigo'] ?? '');
        $orden['factura_numero'] = $facturaNum;
        if ($cxcMap !== null) {
            $orden['cxc'] = $cxcMap[$facturaNum] ?? null;
        } else {
            $stmtCxc = $db->prepare("SELECT * FROM cuentas_por_cobrar WHERE factura_numero = :fn LIMIT 1");
            $stmtCxc->execute(['fn' => $facturaNum]);
            $orden['cxc'] = $stmtCxc->fetch(PDO::FETCH_ASSOC) ?: null;
        }

        return $orden;
    }

    private function completarEstadoComercialOS(array $orden): array {
        $montoOS = (float)($orden['cot_total'] ?? 0.0);
        $saldoOS = !empty($orden['cxc']) ? (float)$orden['cxc']['saldo'] : $montoOS;
        $estadoPago = !empty($orden['cxc']) ? (string)$orden['cxc']['estado'] : 'Pendiente';
        $condicionCom = strtolower(trim((string)($orden['condicion_comercial'] ?? $orden['condicion_pago'] ?? $orden['forma_pago'] ?? 'contado')));
        $esCredito = ($condicionCom === 'credito');

        if ($esCredito) {
            $orden['comercial_100'] = !empty($orden['cxc']);
        } else {
            $orden['comercial_100'] = (!empty($orden['cxc']) && $estadoPago === 'Pagado' && $saldoOS <= 0.01);
        }
        $orden['saldo_os'] = $saldoOS;
        $orden['estado_pago'] = $estadoPago;
        $orden['condicion_comercial_activa'] = $condicionCom;
        $orden['puede_cerrar'] = (!empty($orden['tecnico_100']) && $orden['comercial_100'] && !in_array($orden['estado'] ?? '', ['Finalizado', 'Archivado', 'Cerrado'], true));

        return $orden;
    }

    private function matrizTieneResultadosTecnicosCapturados(?string $resultadosJson, string $archivoMd = ''): bool {
        if (empty($resultadosJson) || $resultadosJson === '[]') {
            return false;
        }
        $dec = json_decode($resultadosJson, true);
        if (!is_array($dec)) {
            return false;
        }
        $filas = isset($dec['filas']) && is_array($dec['filas'])
            ? $dec['filas']
            : (array_is_list($dec) ? $dec : []);
        if (empty($filas)) {
            return false;
        }

        foreach ($filas as $fila) {
            if (!is_array($fila)) {
                continue;
            }
            $tieneFechaEnsayo = false;
            foreach ($fila as $colCheck => $valCheck) {
                $colCheckLow = mb_strtolower((string)$colCheck, 'UTF-8');
                if ((str_contains($colCheckLow, 'fecha de ensayo') || str_contains($colCheckLow, 'fecha de ruptura')) && trim((string)$valCheck) !== '') {
                    $tieneFechaEnsayo = true;
                    break;
                }
            }
            foreach ($fila as $col => $val) {
                $colStr = (string)$col;
                if ($colStr === '' || str_starts_with($colStr, '_')) {
                    continue;
                }
                if (trim((string)$val) === '') {
                    continue;
                }
                if (function_exists('clasificarColumnaRecepcionOTecnico')) {
                    $origen = clasificarColumnaRecepcionOTecnico($colStr, $archivoMd);
                    if (in_array($origen, ['tecnico', 'laboratorio'], true)) {
                        $colLow = mb_strtolower($colStr, 'UTF-8');
                        if (str_contains($colLow, 'reven')) {
                            continue;
                        }
                        if (str_contains($colLow, 'edad') && !$tieneFechaEnsayo) {
                            continue;
                        }
                        return true;
                    }
                    if ($origen === 'calculado') {
                        $colLow = mb_strtolower($colStr, 'UTF-8');
                        if (!str_contains($colLow, 'reven') && (!str_contains($colLow, 'edad') || $tieneFechaEnsayo)) {
                            return true;
                        }
                    }
                } else {
                    return true;
                }
            }
        }
        return false;
    }

    public function obtenerMatrizOSAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $idOS = (int)($_GET['id_os'] ?? 0);
        if ($idOS <= 0) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'ID de Orden de Servicio inválido.']);
            return;
        }

        $modelo = new OperacionModelo();
        $os = $modelo->obtenerOSPorId($idOS);
        if (!$os) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Orden de Servicio no encontrada.']);
            return;
        }

        $items = $modelo->obtenerItemsOS($idOS);
        $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS);
        $tieneHoja = !empty($hoja);
        $rolSesion = (int)($_SESSION['usuario_rol'] ?? 0);
        $esSupervisor = in_array($rolSesion, [1, 2, 3]);

        foreach ($items as &$it) {
            $it['revision_info'] = obtenerEstadoRevisionMatriz($it['resultados_json'] ?? null);
            $esCompactacion = esItemCompactacion($it);
            $tieneMuestrasAceptadas = !empty($os['muestras_aceptadas_lab']) && $os['muestras_aceptadas_lab'] > 0;
            $tieneHojaServicio = $tieneHoja;
            $o = $os;

            ob_start();
            include dirname(__DIR__) . '/Vistas/parciales/acciones_ensayo.php';
            $it['acciones_html'] = ob_get_clean();
        }
        unset($it);

        $respuesta->enviarJson([
            'status' => 'success',
            'os' => $os,
            'items' => $items,
            'tiene_hoja' => $tieneHoja
        ]);
    }

    /**
     * Limpia áreas nominales prellenadas ('14.137', '28.274', '28.2743', '12.566') cuando Carga (lb) está vacía.
     */
    private function limpiarAreasNominalesSinCarga(array $filas): array {
        $areasPrefillFalsas = ['14.137', '28.274', '28.2743', '12.566'];
        foreach ($filas as $idx => $fila) {
            if (!is_array($fila)) {
                continue;
            }
            $cargaStr = trim((string)($fila['Carga (lb)'] ?? ($fila['Carga (kg)'] ?? ($fila['Carga'] ?? ''))));
            $sinCarga = ($cargaStr === '' || $cargaStr === '—' || (is_numeric($cargaStr) && (float)$cargaStr <= 0));
            if ($sinCarga) {
                foreach (['Área (in²)', 'Area (in2)', 'Área (cm²)', 'area'] as $kArea) {
                    if (isset($fila[$kArea]) && in_array(trim((string)$fila[$kArea]), $areasPrefillFalsas, true)) {
                        $fila[$kArea] = '';
                    }
                }
            }
            $filas[$idx] = $fila;
        }
        return $filas;
    }

    public function capturaMatrizProducto(Peticion $peticion, Respuesta $respuesta, bool $modoSoloCapturaTecnica = false): void {
        $this->verificarSesion($respuesta);
        $modoSoloCapturaTecnica = $modoSoloCapturaTecnica || (($_GET['modo'] ?? '') === 'captura_tecnica');
        $tienePermisoVerMatriz = tienePermiso('operaciones', 'ver')
            || tienePermiso('operaciones', 'crear_editar')
            || tienePermiso('laboratorio', 'ver')
            || tienePermiso('recepcion', 'ver')
            || (!isset($_SESSION['usuario_permisos']) && in_array((int)($_SESSION['usuario_rol'] ?? 0), [1, 2, 3, 6], true));
        if (!$tienePermisoVerMatriz) {
            $_SESSION['error'] = 'No tiene permisos para acceder a la matriz técnica.';
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $idDetalle = (int)($_GET['id_detalle'] ?? 0);
        if ($idDetalle <= 0) {
            $_SESSION['error'] = 'ID de ensayo no especificado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $stmt = $db->prepare("
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
                   hs.etapa_flujo, hs.recepcion_completa, hs.fecha_recepcion_completa, hs.motivo_reapertura_recepcion, hs.autorizacion_urgente_supervisor,
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
        $stmt->execute(['id' => $idDetalle]);
        $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($detalle) {
            $detalle['id'] = (int)$idDetalle;
            $detalle['id_detalle'] = (int)$idDetalle;
        }

        if (!$detalle) {
            $_SESSION['error'] = 'Producto o ensayo no encontrado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        // Desbloqueo de captura técnica: Técnicos y laboratoristas capturan resultados libremente sin bloqueo de pago.
        // 1. Resolver la Hoja de Servicio (CYCSA-RT-FM-13) específica para este producto/ensayo
        $hoja = resolverHojaPorDetalle($db, (int)$detalle['id_os'], $idDetalle);

        if (!$hoja) {
            $_SESSION['error'] = 'Debe registrar primero la Hoja de Solicitud de Servicio (CYCSA-RT-FM-13) para este producto de la Orden de Servicio ' . ($detalle['codigo_os'] ?? 'O/S') . ' antes de acceder a la matriz técnica.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        // Hidratar metadatos específicos de la hoja en el detalle
        $detalle['procedencia_punto_muestreo'] = $hoja['procedencia_punto_muestreo'] ?? ($detalle['procedencia_punto_muestreo'] ?? '');
        $detalle['nombre_persona_entrega_muestra'] = $hoja['nombre_persona_entrega_muestra'] ?? ($detalle['nombre_persona_entrega_muestra'] ?? '');
        $detalle['fecha_hora_toma_muestra'] = $hoja['fecha_hora_toma_muestra'] ?? ($detalle['fecha_hora_toma_muestra'] ?? null);
        $detalle['fecha_hora_llegada_laboratorio'] = $hoja['fecha_hora_llegada_laboratorio'] ?? ($detalle['fecha_hora_llegada_laboratorio'] ?? null);
        $detalle['naturaleza_muestra'] = $hoja['naturaleza_muestra'] ?? ($detalle['naturaleza_muestra'] ?? '');
        $detalle['observaciones'] = $hoja['observaciones'] ?? ($detalle['observaciones'] ?? '');
        $detalle['id_hoja'] = (int)$hoja['id'];
        $detalle['numero_registro'] = $hoja['numero_registro'] ?? '';
        $etapaFlujo = !empty($hoja['etapa_flujo']) ? (string)$hoja['etapa_flujo'] : (string)($detalle['etapa_flujo'] ?? 'recepcion_borrador');
        $recepcionCompleta = isset($hoja['recepcion_completa']) ? (int)$hoja['recepcion_completa'] : (int)($detalle['recepcion_completa'] ?? 0);
        $detalle['etapa_flujo'] = $etapaFlujo;
        $detalle['recepcion_completa'] = $recepcionCompleta;
        $detalle['fecha_recepcion_completa'] = $hoja['fecha_recepcion_completa'] ?? ($detalle['fecha_recepcion_completa'] ?? null);
        $detalle['motivo_reapertura_recepcion'] = $hoja['motivo_reapertura_recepcion'] ?? ($detalle['motivo_reapertura_recepcion'] ?? null);
        $detalle['autorizacion_urgente_supervisor'] = isset($hoja['autorizacion_urgente_supervisor']) ? (int)$hoja['autorizacion_urgente_supervisor'] : (int)($detalle['autorizacion_urgente_supervisor'] ?? 0);

        $esCompactacion = esItemCompactacion($detalle);
        $idHojaResolv = (int)$hoja['id'];
        $prefijoMuestraOS = determinarPrefijoMuestraOS($detalle);
        $muestrasSeteadas = [];

        if ($esCompactacion) {
            // Regla de Visita CYCSA 2026-11-02 + Multi-producto RT-FM-13:
            // Las muestras/puntos se obtienen filtradas para este producto específico.
            $filasHsd = obtenerMuestrasHojaParaDetalle($db, (int)$detalle['id_os'], $idDetalle, $hoja);

            if (!empty($filasHsd)) {
                foreach ($filasHsd as $idx => $fh) {
                    $codLab = trim($fh['codigo_muestra'] ?? '');
                    if (empty($codLab)) {
                        $codLab = sprintf("{$prefijoMuestraOS}-%04d-%02d", $idx + 1, date('y'));
                    }
                    $muestrasSeteadas[] = [
                        'codigo_lab' => $codLab,
                        'codigo_campo' => $codLab,
                        'nombre_muestra' => !empty($fh['descripcion_ubicacion']) ? $fh['descripcion_ubicacion'] : ($detalle['descripcion_ensayo'] . ' - Muestra ' . ($idx + 1))
                    ];
                }
            }

            if (empty($muestrasSeteadas)) {
                $_SESSION['error'] = 'Para ensayos in situ de la Orden de Servicio ' . ($detalle['codigo_os'] ?? 'O/S') . ', la Hoja de Solicitud (CYCSA-RT-FM-13) debe incluir las muestras o puntos de muestreo asignados a este producto antes de capturar la matriz técnica.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . (int)$detalle['id_os']);
                return;
            }
        } else {
            // Muestras asignadas explícitamente a este producto desde RT-FM-13 (multi-producto o 1-a-1)
            $filasAsignadasDet = obtenerMuestrasHojaParaDetalle($db, (int)$detalle['id_os'], $idDetalle, $hoja);
            $codigosPermitidosDet = [];
            foreach ($filasAsignadasDet as $fa) {
                $ck = strtoupper(trim((string)($fa['codigo_muestra'] ?? '')));
                if ($ck !== '') {
                    $codigosPermitidosDet[$ck] = true;
                }
            }

            // Ensayos convencionales de laboratorio (concreto, suelo físico):
            // Muestras aceptadas formalmente en Laboratorio, filtradas por hoja y por asignación de producto.
            $stmtMuestrasLab = $db->prepare("
                SELECT rm.codigo_muestra, rm.codigo_campo, lm.nombre_lote
                FROM recepcion_muestras rm
                LEFT JOIN lotes_muestras lm ON lm.id_recepcion = rm.id
                WHERE rm.id_os = :id_os AND (rm.id_hoja = :id_hoja OR (rm.id_hoja IS NULL AND :id_hoja_fb = (SELECT MIN(hs_sub.id) FROM hojas_solicitud hs_sub WHERE hs_sub.id_os = :id_os_fb)))
                ORDER BY rm.id ASC
            ");
            $stmtMuestrasLab->execute([
                'id_os' => $detalle['id_os'],
                'id_hoja' => $idHojaResolv,
                'id_hoja_fb' => $idHojaResolv,
                'id_os_fb' => $detalle['id_os']
            ]);
            $muestrasLabList = $stmtMuestrasLab->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($muestrasLabList)) {
                foreach ($muestrasLabList as $idx => $ml) {
                    $ckLab = strtoupper(trim((string)($ml['codigo_muestra'] ?? '')));
                    if (!empty($codigosPermitidosDet) && $ckLab !== '' && !isset($codigosPermitidosDet[$ckLab])) {
                        continue;
                    }
                    $muestrasSeteadas[] = [
                        'codigo_lab' => $ml['codigo_muestra'],
                        'codigo_campo' => $ml['codigo_campo'],
                        'nombre_muestra' => !empty($ml['nombre_lote']) ? $ml['nombre_lote'] : ($detalle['descripcion_ensayo'] . ' - Muestra ' . ($idx + 1))
                    ];
                }
            }
            if (empty($muestrasSeteadas)) {
                if (!empty($filasAsignadasDet)) {
                    // Flujo Directo RT-FM-13 (Abdia): Operaciones / Captura Técnica carga las muestras asignadas a este producto.
                    foreach ($filasAsignadasDet as $idx => $fh) {
                        $codLab = trim((string)($fh['codigo_muestra'] ?? ''));
                        if (empty($codLab)) {
                            $codLab = sprintf("{$prefijoMuestraOS}-%04d-%02d", $idx + 1, date('y'));
                        }
                        $muestrasSeteadas[] = [
                            'codigo_lab' => $codLab,
                            'codigo_campo' => $codLab,
                            'nombre_muestra' => !empty($fh['descripcion_ubicacion']) ? $fh['descripcion_ubicacion'] : ($detalle['descripcion_ensayo'] . ' - Muestra ' . ($idx + 1))
                        ];
                    }
                } else {
                    $cantItems = max(1, (int)($detalle['cantidad'] ?? 1));
                    for ($idx = 0; $idx < $cantItems; $idx++) {
                        $codLab = sprintf("{$prefijoMuestraOS}-%04d-%02d", $idx + 1, date('y'));
                        $muestrasSeteadas[] = [
                            'codigo_lab' => $codLab,
                            'codigo_campo' => $codLab,
                            'nombre_muestra' => $detalle['descripcion_ensayo'] . ' - Muestra ' . ($idx + 1)
                        ];
                    }
                }
            }
        }

        if (empty($muestrasSeteadas)) {
            $_SESSION['error'] = 'No se encontraron muestras declaradas en la Hoja RT-FM-13 ni en recepción de laboratorio.';
            $respuesta->redirigir('/Cycsa/publico/operaciones/ver?id_os=' . (int)$detalle['id_os']);
            return;
        }

        $archivoMd = resolverArchivoMarkdownEnsayo($detalle);
        $detalle['archivo_markdown'] = $archivoMd;
        $columnas = $this->obtenerColumnasFormato($archivoMd);
        if (empty($columnas)) {
            $schemaInfoRes = obtenerEsquemaPlantillaEnsayo($archivoMd, isset($detalle['formato_id']) ? (int)$detalle['formato_id'] : null);
            $columnas = $schemaInfoRes['columns'] ?? [];
        }
        if (empty($columnas)) {
            $columnas = ["Código laboratorio", "Nombre muestra", "Área (in²)", "Carga (lb)", "R. Compresión (lb/in²)", "R. Compresión (kg/cm²)"];
        }

        $schemaInfo = obtenerEsquemaPlantillaEnsayo($detalle['archivo_markdown'] ?? null, isset($detalle['formato_id']) ? (int)$detalle['formato_id'] : null);
        $formatosSchemaJson = json_encode([$detalle['archivo_markdown'] ?? '' => $schemaInfo], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $metadatosGuardados = [];
        $decResultados = json_decode($detalle['resultados_json'] ?? '', true) ?: [];
        if (isset($decResultados['filas']) && is_array($decResultados['filas'])) {
            $decResultados['filas'] = $this->limpiarAreasNominalesSinCarga($decResultados['filas']);
            $detalle['resultados_json'] = json_encode($decResultados, JSON_UNESCAPED_UNICODE);
        } elseif (isset($decResultados[0]) && is_array($decResultados[0])) {
            $decResultados = $this->limpiarAreasNominalesSinCarga($decResultados);
            $detalle['resultados_json'] = json_encode($decResultados, JSON_UNESCAPED_UNICODE);
        }
        $versionActual = (int)($decResultados['version_actual'] ?? 1);
        $listaVersiones = isset($decResultados['versiones']) && is_array($decResultados['versiones']) ? $decResultados['versiones'] : [];
        $versionSolicitada = isset($_GET['version']) ? (int)$_GET['version'] : $versionActual;

        $esHistorica = false;
        $versionActivaInfo = null;

        if ($versionSolicitada > 0 && $versionSolicitada !== $versionActual) {
            foreach ($listaVersiones as $vItem) {
                if ((int)($vItem['version'] ?? 0) === $versionSolicitada) {
                    $versionActivaInfo = $vItem;
                    $esHistorica = true;
                    // Cargar snapshot histórico de la versión para visualización fiel
                    $detalle['resultados_json'] = json_encode([
                        'version_actual' => $versionActual,
                        'filas' => $this->limpiarAreasNominalesSinCarga(is_array($vItem['filas'] ?? null) ? $vItem['filas'] : []),
                        'metadatos' => $vItem['metadatos'] ?? [],
                        'revision' => [
                            'estado' => $vItem['estado'] ?? 'devuelta',
                            'usuario_revisor' => $vItem['usuario_revisor'] ?? '',
                            'fecha_revision' => $vItem['fecha'] ?? '',
                            'motivo_devolucion' => $vItem['motivo_devolucion'] ?? '',
                            'historial' => []
                        ],
                        'versiones' => $listaVersiones
                    ], JSON_UNESCAPED_UNICODE);

                    if (isset($vItem['metadatos']) && is_array($vItem['metadatos'])) {
                        $metadatosGuardados = $vItem['metadatos'];
                    }
                    break;
                }
            }
        } elseif (isset($decResultados['metadatos']) && is_array($decResultados['metadatos'])) {
            $metadatosGuardados = $decResultados['metadatos'];
        }

        $metadatos = resolverMetadatosEnsayo($detalle, $schemaInfo, $metadatosGuardados);

        $mapaDatosRecepcion = construirMapaDatosRecepcionPorHoja($db, $idHojaResolv);
        $muestrasRecepcionCompletas = 0;
        $areasPrefillFalsas = ['14.137', '28.274', '28.2743', '12.566'];
        foreach ($muestrasSeteadas as &$mSet) {
            $codKey = strtoupper(trim((string)($mSet['codigo_lab'] ?? ($mSet['codigo_campo'] ?? ''))));
            $dRec = ($codKey !== '' && !empty($mapaDatosRecepcion[$codKey])) ? $mapaDatosRecepcion[$codKey] : [];
            if (!empty($dRec)) {
                $cargaRec = trim((string)($dRec['Carga (lb)'] ?? ($dRec['Carga'] ?? '')));
                if ($cargaRec === '') {
                    foreach (['Área (in²)', 'Area (in2)', 'area'] as $kArRec) {
                        if (isset($dRec[$kArRec]) && in_array(trim((string)$dRec[$kArRec]), $areasPrefillFalsas, true)) {
                            unset($dRec[$kArRec]);
                        }
                    }
                }
            }
            $mSet['datos_recepcion'] = $dRec;
            if (!empty($dRec)) {
                $muestrasRecepcionCompletas++;
            }
        }
        unset($mSet);

        $clasificacionColumnas = clasificarColumnasMatrizPorRol($columnas, (string)$archivoMd);
        $tieneCamposRecepcion = in_array('recepcion', $clasificacionColumnas, true);
        $formatosRoturaLab = [
            'resistencia_de_concreto.md',
            'resistencia_de_mortero.md',
            'resistencia_de_adoquines.md',
            'formato_de_resistencia_de_bloques.md',
            'formato_de_resistencia_de_a_la_flexion.md',
        ];
        $esEnsayoRoturaLab = in_array((string)$archivoMd, $formatosRoturaLab, true);

        $hojaTecnicaConfig = \Cycsa\Modulos\Operaciones\Servicios\HojaTecnicaMatrizServicio::obtenerDefinicionPorFormato((string)$archivoMd);
        $capturaTecnicaInicial = \Cycsa\Modulos\Operaciones\Servicios\HojaTecnicaMatrizServicio::hidratarCapturaTecnicaInicial(
            $db,
            (string)$archivoMd,
            $detalle,
            $muestrasSeteadas,
            $decResultados,
            $idHojaResolv
        );

        // Protección de visibilidad estricta para técnicos (no supervisores):
        // Los técnicos en Operaciones SOLO ven sus columnas técnicas; nunca ven datos de Recepción (RT-FM-13 / O/S).
        $rolVistaMatriz = (int)($_SESSION['usuario_rol'] ?? 0);
        $permisosVistaCtrl = $_SESSION['usuario_permisos'] ?? [];
        if (is_string($permisosVistaCtrl)) {
            $permisosVistaCtrl = json_decode($permisosVistaCtrl, true) ?: [];
        }
        $tieneAprobarExplicitoCtrl = is_array($permisosVistaCtrl) && (
            !empty($permisosVistaCtrl['operaciones']['aprobar'])
            || !empty($permisosVistaCtrl['control_calidad']['aprobar'])
        );
        $esSupervisorVista = in_array($rolVistaMatriz, [1, 2, 3], true) || ($rolVistaMatriz !== 6 && $tieneAprobarExplicitoCtrl);

        if (!$esSupervisorVista || $modoSoloCapturaTecnica) {
            foreach ($muestrasSeteadas as &$mSetTec) {
                $mSetTec['datos_recepcion'] = [];
                $mSetTec['nombre_muestra'] = '';
            }
            unset($mSetTec);

            $decVistaTec = json_decode($detalle['resultados_json'] ?? '', true);
            if (is_array($decVistaTec)) {
                $filtrarFilaTecnico = static function (array $fila) use ($archivoMd): array {
                    $limpia = [];
                    foreach ($fila as $kCol => $vCol) {
                        $kStr = (string)$kCol;
                        if (str_starts_with($kStr, '_')) {
                            $limpia[$kStr] = $vCol;
                            continue;
                        }
                        if (clasificarColumnaRecepcionOTecnico($kStr, (string)$archivoMd) === 'recepcion') {
                            continue;
                        }
                        $limpia[$kStr] = $vCol;
                    }
                    return $limpia;
                };

                if (isset($decVistaTec['filas']) && is_array($decVistaTec['filas'])) {
                    $decVistaTec['filas'] = array_map(
                        static fn($f) => is_array($f) ? $filtrarFilaTecnico($f) : $f,
                        $decVistaTec['filas']
                    );
                } elseif (isset($decVistaTec[0]) && is_array($decVistaTec[0])) {
                    $decVistaTec = array_map(
                        static fn($f) => is_array($f) ? $filtrarFilaTecnico($f) : $f,
                        $decVistaTec
                    );
                }
                $detalle['resultados_json'] = json_encode($decVistaTec, JSON_UNESCAPED_UNICODE);
            }
        }

        $this->renderizar('operaciones/vistas/captura_matriz', [
            'titulo' => 'Captura de Matriz Técnica - ' . $detalle['descripcion_ensayo'],
            'detalle' => $detalle,
            'idDetalle' => $idDetalle,
            'columnas' => $columnas,
            'clasificacionColumnas' => $clasificacionColumnas,
            'tieneCamposRecepcion' => $tieneCamposRecepcion,
            'muestrasRecepcionCompletas' => $muestrasRecepcionCompletas,
            'muestrasSeteadas' => $muestrasSeteadas,
            'formatosSchemaJson' => $formatosSchemaJson,
            'metadatos' => $metadatos,
            'schemaInfo' => $schemaInfo,
            'versionActual' => $versionActual,
            'versionSolicitada' => $versionSolicitada,
            'esHistorica' => $esHistorica,
            'listaVersiones' => $listaVersiones,
            'versionActivaInfo' => $versionActivaInfo,
            'prefijoMuestraOS' => $prefijoMuestraOS,
            'etapaFlujo' => $etapaFlujo,
            'recepcionCompleta' => $recepcionCompleta,
            'esEnsayoRoturaLab' => $esEnsayoRoturaLab,
            'hojaTecnicaConfig' => $hojaTecnicaConfig,
            'capturaTecnicaInicial' => $capturaTecnicaInicial,
            'modoSoloCapturaTecnica' => $modoSoloCapturaTecnica,
        ]);
    }

    public function guardarMatrizProductoPOST(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $tienePermisoGuardarMatriz = tienePermiso('operaciones', 'crear_editar')
            || tienePermiso('laboratorio', 'crear_editar')
            || (!isset($_SESSION['usuario_permisos']) && in_array((int)($_SESSION['usuario_rol'] ?? 0), [1, 2, 3, 6], true));
        if (!$tienePermisoGuardarMatriz) {
            $_SESSION['error'] = 'No tiene permisos para guardar la matriz técnica.';
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idDetalle = (int)($datos['id_detalle'] ?? 0);
            $resultadosJson = $datos['resultados_json'] ?? '[]';

            if ($idDetalle <= 0) {
                $_SESSION['error'] = 'Detalle inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $stmtDet = $db->prepare("
                SELECT cd.id, cd.id_cotizacion, cd.descripcion_ensayo, cd.resultados_json,
                       p.formato_id, fe.archivo_markdown
                FROM cotizacion_detalles cd
                LEFT JOIN productos p ON cd.id_producto = p.id
                LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                WHERE cd.id = :id LIMIT 1
            ");
            $stmtDet->execute(['id' => $idDetalle]);
            $detalleActual = $stmtDet->fetch(PDO::FETCH_ASSOC);

            if (!$detalleActual) {
                $_SESSION['error'] = 'Detalle de ensayo no encontrado.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $archivoMdGuardar = !empty($detalleActual['archivo_markdown'])
                ? (string)$detalleActual['archivo_markdown']
                : resolverArchivoMarkdownEnsayo($detalleActual);

            // Candado Backend RT-FM-13: Validar que la O/S tenga registrada su hoja de solicitud
            $stmtOS = $db->prepare("SELECT id, codigo_os FROM ordenes_servicio WHERE id_cotizacion = :id_cot LIMIT 1");
            $stmtOS->execute(['id_cot' => $detalleActual['id_cotizacion']]);
            $osActual = $stmtOS->fetch(PDO::FETCH_ASSOC);
            if ($osActual) {
                $stmtHojaCheck = $db->prepare("SELECT id FROM hojas_solicitud WHERE id_os = :id_os LIMIT 1");
                $stmtHojaCheck->execute(['id_os' => $osActual['id']]);
                if (!$stmtHojaCheck->fetchColumn()) {
                    $_SESSION['error'] = 'Operación bloqueada: Debe registrar primero la Hoja de Solicitud CYCSA-RT-FM-13 oficial para ' . $osActual['codigo_os'] . ' antes de guardar la matriz técnica.';
                    $respuesta->redirigir('/Cycsa/publico/operaciones');
                    return;
                }
            }

            // Desempaquetar filas, captura técnica del laboratorio y metadatos existentes
            $rawJsonParsed = json_decode($resultadosJson, true) ?: [];
            $filasDecodificadas = isset($rawJsonParsed['filas']) ? $rawJsonParsed['filas'] : $rawJsonParsed;
            $filasDecodificadas = normalizarReplicasMatriz(is_array($filasDecodificadas) ? $filasDecodificadas : []);

            $decExistente = json_decode($detalleActual['resultados_json'] ?? '', true) ?: [];
            $capturaTecnicaFinal = (isset($decExistente['captura_tecnica']) && is_array($decExistente['captura_tecnica']))
                ? $decExistente['captura_tecnica']
                : [];
            if (isset($rawJsonParsed['captura_tecnica']) && is_array($rawJsonParsed['captura_tecnica'])) {
                $recalcTec = \Cycsa\Modulos\Operaciones\Servicios\HojaTecnicaMatrizServicio::recalcularDesdeCapturaTecnica(
                    $archivoMdGuardar,
                    $rawJsonParsed['captura_tecnica'],
                    $filasDecodificadas,
                    $db
                );
                $capturaTecnicaFinal = $recalcTec['captura_tecnica'];
                if (!empty($recalcTec['tiene_datos'])) {
                    $filasDecodificadas = normalizarReplicasMatriz($recalcTec['filas_matriz']);
                }
            }
            $filasPreviasExistentes = isset($decExistente['filas']) && is_array($decExistente['filas'])
                ? $decExistente['filas']
                : (isset($decExistente[0]) && is_array($decExistente[0]) ? $decExistente : []);

            $mapaRec = [];
            $mapaNombresOficiales = [];
            $hojaAssoc = null;
            if ($osActual) {
                $hojaAssoc = resolverHojaPorDetalle($db, (int)$osActual['id'], $idDetalle);
                if ($hojaAssoc && !empty($hojaAssoc['id'])) {
                    $mapaRec = construirMapaDatosRecepcionPorHoja($db, (int)$hojaAssoc['id']);
                    try {
                        $stmtHsdN = $db->prepare("SELECT codigo_muestra, descripcion_ubicacion FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = :hid");
                        $stmtHsdN->execute(['hid' => (int)$hojaAssoc['id']]);
                        foreach ($stmtHsdN->fetchAll(PDO::FETCH_ASSOC) as $rHsd) {
                            $cH = strtoupper(trim((string)($rHsd['codigo_muestra'] ?? '')));
                            if ($cH !== '' && !empty($rHsd['descripcion_ubicacion'])) {
                                $mapaNombresOficiales[$cH] = trim((string)$rHsd['descripcion_ubicacion']);
                            }
                        }
                    } catch (\Throwable $e) {
                    }
                }
            }

            // 2.4 Protección estricta servidor: técnico (o guardado desde módulo Captura Técnica) NUNCA puede modificar casillas de Recepción, Nombre muestra ni equipo densímetro
            $rolSesionGuardar = (int)($_SESSION['usuario_rol'] ?? 0);
            $esSupervisorGuardar = in_array($rolSesionGuardar, [1, 2, 3], true);
            $esSoloCapturaPost = !empty($datos['modo_solo_captura']);
            $protegerRecepcionYMeta = (!$esSupervisorGuardar || $esSoloCapturaPost);

            if ($protegerRecepcionYMeta) {
                $mapaFilasPrevias = [];
                foreach ($filasPreviasExistentes as $idxPrev => $fPrev) {
                    if (!is_array($fPrev)) continue;
                    $cPrev = strtoupper(trim((string)($fPrev['Código laboratorio'] ?? ($fPrev['Codigo laboratorio'] ?? ($fPrev['codigo_lab'] ?? '')))));
                    if ($cPrev !== '') {
                        $mapaFilasPrevias[$cPrev] = $fPrev;
                    }
                    $mapaFilasPrevias['__IDX_' . $idxPrev] = $fPrev;
                }

                foreach ($filasDecodificadas as $idxF => $filaItem) {
                    if (!is_array($filaItem)) continue;
                    $codF = strtoupper(trim((string)($filaItem['Código laboratorio'] ?? ($filaItem['Codigo laboratorio'] ?? ($filaItem['codigo_lab'] ?? '')))));
                    $codOrigen = !empty($filaItem['_muestra_origen']) ? strtoupper(trim((string)$filaItem['_muestra_origen'])) : '';
                    $filaPrev = ($codF !== '' && isset($mapaFilasPrevias[$codF]))
                        ? $mapaFilasPrevias[$codF]
                        : ($mapaFilasPrevias['__IDX_' . $idxF] ?? []);
                    $recMatch = ($codF !== '' && isset($mapaRec[$codF]))
                        ? $mapaRec[$codF]
                        : (($codOrigen !== '' && isset($mapaRec[$codOrigen])) ? $mapaRec[$codOrigen] : []);

                    // Restaurar o despojar todas las columnas de recepción enviadas por el técnico
                    foreach (array_keys($filaItem) as $kCol) {
                        $kStr = (string)$kCol;
                        if (str_starts_with($kStr, '_')) continue;
                        if (clasificarColumnaRecepcionOTecnico($kStr, $archivoMdGuardar) === 'recepcion') {
                            if (array_key_exists($kStr, $filaPrev) && trim((string)$filaPrev[$kStr]) !== '') {
                                $filaItem[$kStr] = $filaPrev[$kStr];
                            } elseif (!empty($recMatch) && buscarValorEnFilaPHP($recMatch, $kStr) !== '') {
                                $filaItem[$kStr] = buscarValorEnFilaPHP($recMatch, $kStr);
                            } elseif (normalizarTextoClavePHP($kStr) === 'nombremuestra' && $codF !== '' && isset($mapaNombresOficiales[$codF])) {
                                $filaItem[$kStr] = $mapaNombresOficiales[$codF];
                            } else {
                                unset($filaItem[$kStr]);
                            }
                        }
                    }

                    // Asegurar que columnas de recepción previamente guardadas no desaparezcan si el técnico no las envió
                    foreach ($filaPrev as $kPrev => $vPrev) {
                        $kPrevStr = (string)$kPrev;
                        if (str_starts_with($kPrevStr, '_')) continue;
                        if (clasificarColumnaRecepcionOTecnico($kPrevStr, $archivoMdGuardar) === 'recepcion' && trim((string)$vPrev) !== '') {
                            $filaItem[$kPrevStr] = $vPrev;
                        }
                    }
                    $filasDecodificadas[$idxF] = $filaItem;
                }
            }

            // Respaldo automático de datos de Recepción desde RT-FM-13 si alguna celda de recepción vino vacía
            if (!empty($mapaRec)) {
                foreach ($filasDecodificadas as $idxF => $filaItem) {
                    if (!is_array($filaItem)) continue;
                    $codF = strtoupper(trim((string)($filaItem['Código laboratorio'] ?? ($filaItem['Codigo laboratorio'] ?? ($filaItem['codigo_lab'] ?? '')))));
                    $codOrigen = !empty($filaItem['_muestra_origen']) ? strtoupper(trim((string)$filaItem['_muestra_origen'])) : '';
                    $recMatch = ($codF !== '' && isset($mapaRec[$codF]))
                        ? $mapaRec[$codF]
                        : (($codOrigen !== '' && isset($mapaRec[$codOrigen])) ? $mapaRec[$codOrigen] : null);
                    if ($recMatch) {
                        $filasDecodificadas[$idxF] = fusionarDatosRecepcionEnFilaMatriz($filaItem, $recMatch, [], false, $archivoMdGuardar);
                    }
                }
            }

            // Sección 5, 9 y 10: Protección servidor de columnas calculadas, limpieza de áreas nominales sin carga y autoridad de roturas del laboratorio
            $filasDecodificadas = $this->limpiarAreasNominalesSinCarga($filasDecodificadas);

            $roturasPorCodigo = [];
            $idHojaAssoc = (int)($hojaAssoc['id'] ?? 0);
            if ($idHojaAssoc > 0) {
                try {
                    $stmtRot = $db->prepare("
                        SELECT rm.codigo_muestra AS codigo_lab, esp.codigo_lab AS codigo_especimen,
                               r.carga, r.area, r.peso_volumetrico, r.esfuerzo_psi, r.esfuerzo_kgcm2,
                               r.fecha_real, r.edad_calculada
                        FROM roturas r
                        JOIN especimenes esp ON r.especimen_id = esp.id
                        JOIN recepcion_muestras rm ON esp.muestra_id = rm.id
                        WHERE rm.id_hoja = :id_hoja
                          AND r.carga IS NOT NULL AND r.carga > 0
                        ORDER BY r.id ASC
                    ");
                    $stmtRot->execute(['id_hoja' => $idHojaAssoc]);
                    foreach ($stmtRot->fetchAll(PDO::FETCH_ASSOC) as $rotRow) {
                        $cRot = strtoupper(trim((string)($rotRow['codigo_lab'] ?? '')));
                        if ($cRot !== '') {
                            $roturasPorCodigo[$cRot][] = $rotRow;
                        }
                        $cEsp = strtoupper(trim((string)($rotRow['codigo_especimen'] ?? '')));
                        if ($cEsp !== '' && $cEsp !== $cRot) {
                            $roturasPorCodigo[$cEsp][] = $rotRow;
                        }
                    }
                } catch (\Throwable $e) {
                }
            }

            $cursoresRotura = [];
            foreach ($filasDecodificadas as $idxF => $filaItem) {
                if (!is_array($filaItem)) {
                    continue;
                }
                // Recalcular Reven. (cm) desde Reven. (in) para evitar suplantación manual
                $revInRaw = trim((string)($filaItem['Reven. (in)'] ?? ''));
                if ($revInRaw !== '' && is_numeric($revInRaw) && (float)$revInRaw > 0) {
                    $cmCalc = \Cycsa\Modulos\Operaciones\Servicios\ConfigCalculoLabServicio::calcularRevenimientoCm((float)$revInRaw, $db);
                    $filaItem['Reven. (cm)'] = ((float)$cmCalc == (int)$cmCalc) ? (string)(int)$cmCalc : (string)$cmCalc;
                }

                // Recalcular Edad (Días) desde Fecha de Fabricación y Fecha de Ensayo si ambas existen
                $fFab = trim((string)($filaItem['Fecha de Fabricación'] ?? ''));
                $fEns = trim((string)($filaItem['Fecha de Ensayo'] ?? ($filaItem['Fecha de Ruptura'] ?? '')));
                if ($fFab !== '' && $fEns !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fFab) && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fEns)) {
                    $tsFab = strtotime($fFab);
                    $tsEns = strtotime($fEns);
                    if ($tsFab !== false && $tsEns !== false && $tsEns >= $tsFab) {
                        $filaItem['Edad (Días)'] = (string)(int)round(($tsEns - $tsFab) / 86400);
                    }
                }

                // Si existen roturas reales en laboratorio para esta muestra y NO se enviaron nuevas mediciones en captura_tecnica, prevalecen como fuente única de verdad
                $codF = strtoupper(trim((string)($filaItem['Código laboratorio'] ?? ($filaItem['Codigo laboratorio'] ?? ($filaItem['codigo_lab'] ?? '')))));
                if (empty($recalcTec['tiene_datos']) && $codF !== '' && !empty($roturasPorCodigo[$codF])) {
                    $idxCursor = $cursoresRotura[$codF] ?? 0;
                    $rotMatch = $roturasPorCodigo[$codF][$idxCursor] ?? end($roturasPorCodigo[$codF]);
                    $cursoresRotura[$codF] = $idxCursor + 1;
                    if (is_array($rotMatch)) {
                        if ($rotMatch['area'] !== null && (float)$rotMatch['area'] > 0) {
                            $filaItem['Área (in²)'] = (string)round((float)$rotMatch['area'], 2);
                        }
                        if ($rotMatch['carga'] !== null && (float)$rotMatch['carga'] > 0) {
                            $filaItem['Carga (lb)'] = (string)(int)round((float)$rotMatch['carga']);
                        }
                        if ($rotMatch['peso_volumetrico'] !== null && (float)$rotMatch['peso_volumetrico'] > 0) {
                            $filaItem['Peso Volumétrico (kg/m³)'] = (string)(int)round((float)$rotMatch['peso_volumetrico']);
                        }
                        if ($rotMatch['esfuerzo_psi'] !== null && (float)$rotMatch['esfuerzo_psi'] > 0) {
                            $filaItem['R. Compresión (lb/in²)'] = (string)(int)round((float)$rotMatch['esfuerzo_psi']);
                        }
                        if ($rotMatch['esfuerzo_kgcm2'] !== null && (float)$rotMatch['esfuerzo_kgcm2'] > 0) {
                            $filaItem['R. Compresión (kg/cm²)'] = (string)(int)round((float)$rotMatch['esfuerzo_kgcm2']);
                        }
                        if (!empty($rotMatch['fecha_real'])) {
                            $filaItem['Fecha de Ensayo'] = substr(trim((string)$rotMatch['fecha_real']), 0, 10);
                        }
                        $edadAuth = ($rotMatch['edad_calculada'] !== null && $rotMatch['edad_calculada'] !== '')
                            ? (int)$rotMatch['edad_calculada']
                            : (($rotMatch['edad_dias'] !== null && $rotMatch['edad_dias'] !== '') ? (int)$rotMatch['edad_dias'] : null);
                        if ($edadAuth !== null && $edadAuth > 0) {
                            $filaItem['Edad (Días)'] = (string)$edadAuth;
                        }
                    }
                }
                $filasDecodificadas[$idxF] = $filaItem;
            }

            $metaPost = !empty($datos['metadatos']) && is_array($datos['metadatos']) ? $datos['metadatos'] : [];
            $metaRawJson = !empty($rawJsonParsed['metadatos']) && is_array($rawJsonParsed['metadatos']) ? $rawJsonParsed['metadatos'] : [];
            $metaPrev = !empty($decExistente['metadatos']) && is_array($decExistente['metadatos']) ? $decExistente['metadatos'] : [];
            $metadatos = array_merge($metaPrev, $metaRawJson, $metaPost);

            // Persistir explícitamente nota_aditivos, observaciones_pendientes, disclaimer_personalizado, notas_informe y columnas_asterisco
            foreach (['nota_aditivos', 'observaciones_pendientes', 'disclaimer_personalizado'] as $campoMetaTexto) {
                if (array_key_exists($campoMetaTexto, $metaPost)) {
                    $metadatos[$campoMetaTexto] = trim((string)$metaPost[$campoMetaTexto]);
                } elseif (array_key_exists($campoMetaTexto, $metaRawJson)) {
                    $metadatos[$campoMetaTexto] = trim((string)$metaRawJson[$campoMetaTexto]);
                }
            }
            if (array_key_exists('notas_informe', $metaPost) || array_key_exists('notas_informe', $metaRawJson)) {
                $rawNotasMeta = array_key_exists('notas_informe', $metaPost) ? $metaPost['notas_informe'] : $metaRawJson['notas_informe'];
                if (is_string($rawNotasMeta)) {
                    $rawNotasMeta = preg_split('/\r\n|\r|\n/', $rawNotasMeta) ?: [];
                }
                $notasLimpiasMeta = [];
                if (is_array($rawNotasMeta)) {
                    foreach ($rawNotasMeta as $nLinea) {
                        $nLineaTrim = trim((string)$nLinea);
                        if ($nLineaTrim !== '') {
                            $notasLimpiasMeta[] = $nLineaTrim;
                        }
                    }
                }
                $metadatos['notas_informe'] = $notasLimpiasMeta;
                $metadatos['notas_informe_personalizadas'] = true;
            }
            if (array_key_exists('columnas_asterisco', $metadatos)) {
                if (is_string($metadatos['columnas_asterisco'])) {
                    $metadatos['columnas_asterisco'] = array_values(array_filter(array_map('trim', explode(',', $metadatos['columnas_asterisco'])), static fn($v) => $v !== ''));
                } elseif (is_array($metadatos['columnas_asterisco'])) {
                    $metadatos['columnas_asterisco'] = array_values(array_filter(array_map(static fn($v) => trim((string)$v), $metadatos['columnas_asterisco']), static fn($v) => $v !== ''));
                }
            }

            // 2.4 Si no es supervisor/admin o se guarda desde Captura Técnica, restaurar metadatos de cliente/recepción, notas oficiales y equipo densímetro desde BD previa
            if ($protegerRecepcionYMeta) {
                $clavesSoloSupervisor = [
                    'cliente_nombre', 'cliente_direccion', 'proyecto',
                    'fecha_ingreso', 'tipo_muestra', 'procedimiento_muestreo',
                    'fecha_muestreo', 'fecha_emision', 'muestra_tomada_por',
                    'ubicacion', 'metodo_muestreo', 'logo_acreditacion',
                    'equipo_marca', 'equipo_modelo', 'equipo_serie', 'equipo_codigo',
                    'densimetro_marca', 'densimetro_modelo', 'densimetro_serie', 'densimetro_codigo',
                    'muestra_tomada_con_equipo',
                    'disclaimer_personalizado', 'notas_informe', 'notas_informe_personalizadas',
                ];
                foreach ($clavesSoloSupervisor as $ce) {
                    if (array_key_exists($ce, $metaPrev)) {
                        $metadatos[$ce] = $metaPrev[$ce];
                    } else {
                        unset($metadatos[$ce]);
                    }
                }
            }

            // Procesar subida de logo de acreditación ANTES de persistir resultados_json
            if (isset($_FILES['logo_acreditacion_file']) && is_uploaded_file($_FILES['logo_acreditacion_file']['tmp_name'])) {
                $dirDestino = dirname(__DIR__, 4) . '/publico/uploads/acreditaciones';
                if (!is_dir($dirDestino)) mkdir($dirDestino, 0755, true);
                $ext = strtolower(pathinfo((string)$_FILES['logo_acreditacion_file']['name'], PATHINFO_EXTENSION));
                if (in_array($ext, ['png', 'jpg', 'jpeg', 'webp', 'svg'], true)) {
                    $nombreFinal = 'acred_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                    if (move_uploaded_file($_FILES['logo_acreditacion_file']['tmp_name'], $dirDestino . '/' . $nombreFinal)) {
                        $metadatos['logo_acreditacion'] = '/Cycsa/publico/uploads/acreditaciones/' . $nombreFinal;
                    }
                }
            } elseif (!isset($metadatos['logo_acreditacion']) && !empty($metaPrev['logo_acreditacion'])) {
                $metadatos['logo_acreditacion'] = $metaPrev['logo_acreditacion'];
            }

            $revisionExistente = isset($decExistente['revision']) && is_array($decExistente['revision'])
                ? $decExistente['revision']
                : [];
            $prevEstado = $revisionExistente['estado'] ?? 'pendiente';
            $historial = $revisionExistente['historial'] ?? [];

            $usuarioSesion = $_SESSION['usuario_nombre'] ?? 'Personal de Laboratorio';
            $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);
            $fechaActual = date('Y-m-d H:i:s');

            if ($prevEstado === 'devuelta') {
                $historial[] = [
                    'accion' => 'corregido_y_reenviado',
                    'fecha' => $fechaActual,
                    'usuario' => $usuarioSesion,
                    'usuario_id' => $usuarioIdSesion,
                    'nota' => 'Matriz corregida tras observación de supervisión y marcada Pendiente de revisión.'
                ];
            } else {
                $historial[] = [
                    'accion' => 'enviado_revision',
                    'fecha' => $fechaActual,
                    'usuario' => $usuarioSesion,
                    'usuario_id' => $usuarioIdSesion,
                    'nota' => 'Matriz técnica guardada con estado Pendiente de revisión.'
                ];
            }

            $nuevaRevision = [
                'estado' => 'en_revision',
                'fecha_envio' => $fechaActual,
                'usuario_envio' => $usuarioSesion,
                'usuario_envio_id' => $usuarioIdSesion,
                'fecha_revision' => null,
                'usuario_revisor' => null,
                'usuario_revisor_id' => null,
                'motivo_devolucion' => ($prevEstado === 'devuelta') ? ($revisionExistente['motivo_devolucion'] ?? null) : null,
                'historial' => $historial
            ];

            $versionesExistentes = isset($decExistente['versiones']) && is_array($decExistente['versiones'])
                ? $decExistente['versiones']
                : [];
            $versionActual = (int)($decExistente['version_actual'] ?? 1);

            // A1: Comparar pares de réplicas previos vs nuevos y resetear dictámenes obsoletos si cambiaron datos numéricos
            $filasPrevias = isset($decExistente['filas']) && is_array($decExistente['filas'])
                ? $decExistente['filas']
                : (isset($decExistente[0]) && is_array($decExistente[0]) ? $decExistente : []);
            $paresPrevios = obtenerParesControlCalidad($filasPrevias);
            $paresNuevos = obtenerParesControlCalidad($filasDecodificadas);
            $codigosModificados = detectarCambiosNumericosEnParesCC($paresPrevios, $paresNuevos);

            $ccExistente = isset($decExistente['control_calidad']) && is_array($decExistente['control_calidad'])
                ? $decExistente['control_calidad']
                : [];
            if (!isset($ccExistente['replicas']) || !is_array($ccExistente['replicas'])) {
                $ccExistente['replicas'] = [];
            }

            foreach ($codigosModificados as $codMod) {
                if (isset($ccExistente['replicas'][$codMod])) {
                    $estadoPrevCC = $ccExistente['replicas'][$codMod]['estado'] ?? 'pendiente';
                    if ($estadoPrevCC !== 'pendiente') {
                        $ccExistente['replicas'][$codMod]['estado'] = 'pendiente';
                        $ccExistente['replicas'][$codMod]['observaciones'] = 'Dictamen reiniciado automáticamente por modificación de datos numéricos en matriz.';
                        $ccExistente['replicas'][$codMod]['usuario'] = 'Sistema (Auto)';
                        $ccExistente['replicas'][$codMod]['fecha'] = $fechaActual;
                    }
                }
            }

            $payloadCompleto = [
                'version_actual' => $versionActual,
                'filas' => $filasDecodificadas,
                'captura_tecnica' => $capturaTecnicaFinal,
                'metadatos' => $metadatos,
                'revision' => $nuevaRevision,
                'versiones' => $versionesExistentes,
                'control_calidad' => $ccExistente,
                'usuario_captura_id' => $usuarioIdSesion,
                'usuario_captura_nombre' => $usuarioSesion
            ];
            $resultadosJsonFinal = json_encode($payloadCompleto, JSON_UNESCAPED_UNICODE);

            $stmt = $db->prepare("UPDATE cotizacion_detalles SET resultados_json = :json WHERE id = :id");
            $stmt->execute([
                'json' => $resultadosJsonFinal,
                'id' => $idDetalle
            ]);

            $idOsSync = is_array($osActual) ? (int)($osActual['id'] ?? 0) : 0;
            $idHojaSync = 0;
            if ($idOsSync > 0) {
                $hojaSync = $hojaAssoc ?: resolverHojaPorDetalle($db, $idOsSync, $idDetalle);
                $idHojaSync = $hojaSync ? (int)$hojaSync['id'] : 0;
                if (!empty($filasDecodificadas)) {
                    sincronizarReplicasMatrizConLab($db, $idOsSync, $idHojaSync, $idDetalle, $filasDecodificadas, $capturaTecnicaFinal);
                }
            }

            if (!empty($datos['enviar_a_revision'])) {
                try {
                    if ($idHojaSync > 0) {
                        $stmtEtapa = $db->prepare("UPDATE hojas_solicitud SET etapa_flujo = 'matriz_en_revision' WHERE id = :id_hoja");
                        $stmtEtapa->execute(['id_hoja' => $idHojaSync]);
                    } elseif ($idOsSync > 0) {
                        $stmtEtapa = $db->prepare("UPDATE hojas_solicitud SET etapa_flujo = 'matriz_en_revision' WHERE id_os = :id_os AND (id_detalle_cotizacion = :id_det OR id_detalle_cotizacion IS NULL)");
                        $stmtEtapa->execute(['id_os' => $idOsSync, 'id_det' => $idDetalle]);
                    }
                } catch (\Throwable $e) {
                }
            }

            $idOsMatriz = is_array($osActual) ? (int)($osActual['id'] ?? 0) : 0;
            if ($idOsMatriz <= 0) {
                $idOsMatriz = bitacoraIdOsPorDetalleEnsayo($idDetalle);
            }
            registrarEventoOS(
                'operaciones',
                'guardar_matriz',
                "Matriz técnica guardada como Pendiente de revisión para '{$detalleActual['descripcion_ensayo']}' (Detalle #{$idDetalle})",
                $idOsMatriz,
                [
                    'entidad_tipo' => 'matriz',
                    'entidad_id' => $idDetalle,
                    'id_referencia' => $idDetalle,
                    'datos' => ['id_detalle' => $idDetalle],
                ]
            );

            $_SESSION['exito'] = 'Matriz técnica guardada correctamente con estado Pendiente de revisión.';
            $redir = !empty($datos['redirect_to']) ? $datos['redirect_to'] : '/Cycsa/publico/operaciones';
            $respuesta->redirigir($redir);
        }
    }

    /**
     * Envía explícitamente la matriz técnica a revisión del supervisor (etapa_flujo = 'matriz_en_revision').
     * Ruta: POST /operaciones/enviar-matriz-revision
     */
    public function enviarMatrizARevision(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $tienePermiso = tienePermiso('operaciones', 'crear_editar')
            || tienePermiso('laboratorio', 'crear_editar')
            || (!isset($_SESSION['usuario_permisos']) && in_array((int)($_SESSION['usuario_rol'] ?? 0), [1, 2, 3, 6], true));
        if (!$tienePermiso) {
            $_SESSION['error'] = 'No tiene permisos para enviar la matriz a revisión.';
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $esAjax = !empty($datos['ajax'])
            || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower((string)$_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || str_contains(strtolower((string)($_SERVER['HTTP_ACCEPT'] ?? '')), 'application/json');

        if (isset($datos['csrf_token']) && !empty($_SESSION['csrf_token']) && !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
            if ($esAjax) {
                $respuesta->enviarJson(['exito' => false, 'error' => 'Token CSRF inválido.'], 403);
                return;
            }
            $_SESSION['error'] = 'Token CSRF inválido.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $idDetalle = (int)($datos['id_detalle'] ?? ($_GET['id_detalle'] ?? 0));
        $idHoja = (int)($datos['id_hoja'] ?? ($_GET['id_hoja'] ?? 0));

        if ($idDetalle <= 0 && $idHoja <= 0) {
            if ($esAjax) {
                $respuesta->enviarJson(['exito' => false, 'error' => 'Debe especificar id_detalle o id_hoja.'], 400);
                return;
            }
            $_SESSION['error'] = 'Debe especificar el ensayo o la hoja de solicitud a enviar a revisión.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $idOs = 0;

        if ($idHoja > 0) {
            $stmtH = $db->prepare("SELECT id, id_os, id_detalle_cotizacion FROM hojas_solicitud WHERE id = :id LIMIT 1");
            $stmtH->execute(['id' => $idHoja]);
            $hojaRow = $stmtH->fetch(PDO::FETCH_ASSOC);
            if ($hojaRow) {
                $idOs = (int)($hojaRow['id_os'] ?? 0);
                if ($idDetalle <= 0 && !empty($hojaRow['id_detalle_cotizacion'])) {
                    $idDetalle = (int)$hojaRow['id_detalle_cotizacion'];
                }
            }
        }

        if ($idDetalle <= 0 && $idOs > 0) {
            $stmtDetOs = $db->prepare("
                SELECT cd.id
                FROM cotizacion_detalles cd
                JOIN ordenes_servicio os ON os.id_cotizacion = cd.id_cotizacion
                WHERE os.id = :id_os
                ORDER BY cd.id ASC LIMIT 1
            ");
            $stmtDetOs->execute(['id_os' => $idOs]);
            $idDetalle = (int)$stmtDetOs->fetchColumn();
        }

        if ($idDetalle > 0 && $idHoja <= 0) {
            $stmtDet = $db->prepare("
                SELECT cd.id, os.id AS id_os
                FROM cotizacion_detalles cd
                JOIN ordenes_servicio os ON os.id_cotizacion = cd.id_cotizacion
                WHERE cd.id = :id LIMIT 1
            ");
            $stmtDet->execute(['id' => $idDetalle]);
            $detRow = $stmtDet->fetch(PDO::FETCH_ASSOC);
            if ($detRow) {
                $idOs = (int)($detRow['id_os'] ?? 0);
                $hojaRes = resolverHojaPorDetalle($db, $idOs, $idDetalle);
                if ($hojaRes && !empty($hojaRes['id'])) {
                    $idHoja = (int)$hojaRes['id'];
                }
            }
        }

        $fechaActual = date('Y-m-d H:i:s');
        $usuarioEnvio = $_SESSION['usuario_nombre'] ?? 'Técnico';
        $usuarioIdEnvio = (int)($_SESSION['usuario_id'] ?? 0);

        if ($idHoja > 0) {
            $stmtUpdH = $db->prepare("UPDATE hojas_solicitud SET etapa_flujo = 'matriz_en_revision' WHERE id = :id_hoja");
            $stmtUpdH->execute(['id_hoja' => $idHoja]);
        } elseif ($idDetalle > 0) {
            $stmtUpdH = $db->prepare("UPDATE hojas_solicitud SET etapa_flujo = 'matriz_en_revision' WHERE id_detalle_cotizacion = :id_det");
            $stmtUpdH->execute(['id_det' => $idDetalle]);
        }

        if ($idDetalle > 0) {
            $stmtCd = $db->prepare("SELECT resultados_json, descripcion_ensayo FROM cotizacion_detalles WHERE id = :id LIMIT 1");
            $stmtCd->execute(['id' => $idDetalle]);
            $cdRow = $stmtCd->fetch(PDO::FETCH_ASSOC);
            if ($cdRow) {
                $dec = json_decode($cdRow['resultados_json'] ?? '', true) ?: [];
                $filas = isset($dec['filas']) && is_array($dec['filas'])
                    ? $dec['filas']
                    : (isset($dec[0]) && is_array($dec[0]) ? $dec : []);
                $metadatos = isset($dec['metadatos']) && is_array($dec['metadatos']) ? $dec['metadatos'] : [];
                $revision = isset($dec['revision']) && is_array($dec['revision']) ? $dec['revision'] : [];
                $historial = isset($revision['historial']) && is_array($revision['historial']) ? $revision['historial'] : [];

                $historial[] = [
                    'accion' => 'enviado_revision',
                    'fecha' => $fechaActual,
                    'usuario' => $usuarioEnvio,
                    'usuario_id' => $usuarioIdEnvio,
                    'nota' => 'Matriz enviada a revisión del supervisor.'
                ];

                $revision['estado'] = 'en_revision';
                $revision['fecha_envio'] = $fechaActual;
                $revision['usuario_envio'] = $usuarioEnvio;
                $revision['usuario_envio_id'] = $usuarioIdEnvio;
                $revision['historial'] = $historial;

                $payload = [
                    'version_actual' => (int)($dec['version_actual'] ?? 1),
                    'filas' => $filas,
                    'metadatos' => $metadatos,
                    'revision' => $revision,
                    'versiones' => isset($dec['versiones']) && is_array($dec['versiones']) ? $dec['versiones'] : [],
                    'control_calidad' => isset($dec['control_calidad']) && is_array($dec['control_calidad']) ? $dec['control_calidad'] : [],
                    'usuario_captura_id' => $dec['usuario_captura_id'] ?? $usuarioIdEnvio,
                    'usuario_captura_nombre' => $dec['usuario_captura_nombre'] ?? $usuarioEnvio,
                ];

                $stmtUpdCd = $db->prepare("UPDATE cotizacion_detalles SET resultados_json = :json WHERE id = :id");
                $stmtUpdCd->execute([
                    'json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                    'id' => $idDetalle
                ]);

                if ($idOs > 0) {
                    registrarEventoOS(
                        'operaciones',
                        'enviar_matriz_revision',
                        "Matriz técnica enviada a revisión del supervisor para '{$cdRow['descripcion_ensayo']}' (Detalle #{$idDetalle})",
                        $idOs,
                        [
                            'entidad_tipo' => 'matriz',
                            'entidad_id' => $idDetalle,
                            'datos' => ['id_detalle' => $idDetalle, 'id_hoja' => $idHoja, 'etapa_flujo' => 'matriz_en_revision'],
                        ]
                    );
                }
            }
        }

        if ($esAjax) {
            $respuesta->enviarJson([
                'exito' => true,
                'estado' => 'en_revision',
                'etapa_flujo' => 'matriz_en_revision',
                'id_detalle' => $idDetalle,
                'id_hoja' => $idHoja,
                'mensaje' => 'Matriz enviada a revisión del supervisor correctamente.'
            ]);
            return;
        }

        $_SESSION['exito'] = 'Matriz enviada a revisión del supervisor correctamente.';
        $redir = !empty($datos['redirect_to'])
            ? $this->resolverRedireccionSegura($datos['redirect_to'], '/Cycsa/publico/operaciones')
            : ($idDetalle > 0 ? '/Cycsa/publico/operaciones/captura-matriz?id_detalle=' . $idDetalle : '/Cycsa/publico/operaciones');
        $respuesta->redirigir($redir);
    }

    /**
     * Sube y asocia un logotipo o sello de acreditación (ISO/IEC 17025) a la matriz técnica
     */
    public function subirLogoAcreditacionAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!$peticion->esPost()) {
            $respuesta->enviarJson(['exito' => false, 'error' => 'Método no permitido.'], 405);
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
            $respuesta->enviarJson(['exito' => false, 'error' => 'Token CSRF inválido o sesión expirada.'], 403);
            return;
        }

        if (!isset($_FILES['archivo_logo']) || !is_uploaded_file($_FILES['archivo_logo']['tmp_name'])) {
            $respuesta->enviarJson(['exito' => false, 'error' => 'No se recibió ningún archivo de imagen.'], 400);
            return;
        }

        $archivo = $_FILES['archivo_logo'];
        if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
            $respuesta->enviarJson(['exito' => false, 'error' => 'Error durante la subida del archivo.'], 400);
            return;
        }

        if (($archivo['size'] ?? 0) > 5 * 1024 * 1024) {
            $respuesta->enviarJson(['exito' => false, 'error' => 'El archivo supera el tamaño máximo permitido (5 MB).'], 413);
            return;
        }

        $ext = strtolower(pathinfo((string)($archivo['name'] ?? ''), PATHINFO_EXTENSION));
        $extPermitidas = ['png', 'jpg', 'jpeg', 'webp', 'svg'];
        if (!in_array($ext, $extPermitidas, true)) {
            $respuesta->enviarJson(['exito' => false, 'error' => 'Formato no permitido. Solo se permiten imágenes PNG, JPG, WEBP o SVG.'], 415);
            return;
        }

        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $archivo['tmp_name']);
        finfo_close($finfo);
        $mimesPermitidos = ['image/png', 'image/jpeg', 'image/webp', 'image/svg+xml'];
        if ($mime !== false && !in_array($mime, $mimesPermitidos, true) && $ext !== 'svg') {
            $respuesta->enviarJson(['exito' => false, 'error' => 'Tipo de archivo no válido.'], 415);
            return;
        }

        $dirDestino = dirname(__DIR__, 4) . '/publico/uploads/acreditaciones';
        if (!is_dir($dirDestino)) {
            mkdir($dirDestino, 0755, true);
        }

        $nombreFinal = 'acred_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $rutaFisica = $dirDestino . '/' . $nombreFinal;

        if (!move_uploaded_file($archivo['tmp_name'], $rutaFisica)) {
            $respuesta->enviarJson(['exito' => false, 'error' => 'No fue posible guardar el archivo en el servidor.'], 500);
            return;
        }

        $urlPublica = '/Cycsa/publico/uploads/acreditaciones/' . $nombreFinal;
        $idDetalle = (int)($_POST['id_detalle'] ?? 0);

        if ($idDetalle > 0) {
            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $stmtDet = $db->prepare("SELECT id, descripcion_ensayo, resultados_json FROM cotizacion_detalles WHERE id = :id LIMIT 1");
            $stmtDet->execute(['id' => $idDetalle]);
            $rowDet = $stmtDet->fetch(\PDO::FETCH_ASSOC);
            if ($rowDet) {
                $dec = json_decode($rowDet['resultados_json'] ?? '', true) ?: [];
                if (!is_array($dec)) {
                    $dec = [];
                }
                if (isset($dec['filas'])) {
                    if (!isset($dec['metadatos']) || !is_array($dec['metadatos'])) {
                        $dec['metadatos'] = [];
                    }
                } elseif (isset($dec['metadatos'])) {
                    if (!isset($dec['filas']) || !is_array($dec['filas'])) {
                        $dec['filas'] = [];
                    }
                } elseif (!empty($dec)) {
                    $dec = [
                        'filas' => array_values($dec),
                        'metadatos' => []
                    ];
                } else {
                    $dec = [
                        'filas' => [],
                        'metadatos' => []
                    ];
                }
                $dec['metadatos']['logo_acreditacion'] = $urlPublica;
                $stmtUpd = $db->prepare("UPDATE cotizacion_detalles SET resultados_json = :json WHERE id = :id");
                $stmtUpd->execute([
                    'json' => json_encode($dec, JSON_UNESCAPED_UNICODE),
                    'id' => $idDetalle
                ]);

                registrarEventoOS(
                    'operaciones',
                    'subir_logo_acreditacion',
                    "Asignado sello/logo de acreditación para '{$rowDet['descripcion_ensayo']}' (Detalle #{$idDetalle})",
                    bitacoraIdOsPorDetalleEnsayo($idDetalle),
                    [
                        'entidad_tipo' => 'matriz',
                        'entidad_id' => $idDetalle,
                        'id_referencia' => $idDetalle,
                    ]
                );
            }
        }

        $respuesta->enviarJson([
            'exito' => true,
            'mensaje' => 'Logotipo de acreditación subido y asignado correctamente.',
            'url' => $urlPublica
        ]);
    }

    /**
     * Retira el logotipo de acreditación de la matriz técnica
     */
    public function eliminarLogoAcreditacionAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!$peticion->esPost()) {
            $respuesta->enviarJson(['exito' => false, 'error' => 'Método no permitido.'], 405);
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
            $respuesta->enviarJson(['exito' => false, 'error' => 'Token CSRF inválido o expirado.'], 403);
            return;
        }

        $idDetalle = (int)($_POST['id_detalle'] ?? 0);
        if ($idDetalle > 0) {
            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $stmtDet = $db->prepare("SELECT id, descripcion_ensayo, resultados_json FROM cotizacion_detalles WHERE id = :id LIMIT 1");
            $stmtDet->execute(['id' => $idDetalle]);
            $rowDet = $stmtDet->fetch(\PDO::FETCH_ASSOC);
            if ($rowDet) {
                $dec = json_decode($rowDet['resultados_json'] ?? '', true) ?: [];
                if (isset($dec['metadatos']) && is_array($dec['metadatos'])) {
                    $dec['metadatos']['logo_acreditacion'] = '';
                }
                $stmtUpd = $db->prepare("UPDATE cotizacion_detalles SET resultados_json = :json WHERE id = :id");
                $stmtUpd->execute([
                    'json' => json_encode($dec, JSON_UNESCAPED_UNICODE),
                    'id' => $idDetalle
                ]);

                registrarEventoOS(
                    'operaciones',
                    'eliminar_logo_acreditacion',
                    "Retirado sello/logo de acreditación para '{$rowDet['descripcion_ensayo']}' (Detalle #{$idDetalle})",
                    bitacoraIdOsPorDetalleEnsayo($idDetalle),
                    [
                        'entidad_tipo' => 'matriz',
                        'entidad_id' => $idDetalle,
                        'id_referencia' => $idDetalle,
                    ]
                );
            }
        }

        $respuesta->enviarJson([
            'exito' => true,
            'mensaje' => 'Logotipo de acreditación retirado.'
        ]);
    }

    /**
     * Aprueba la matriz técnica de un ensayo (Supervisión / Control de Calidad).
     */
    public function aprobarMatrizProducto(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        // Roles autorizados: 1: Superadmin, 2: Administrador, 3: Supervisor
        $rolActual = (int)($_SESSION['usuario_rol'] ?? 0);
        if (!in_array($rolActual, [1, 2, 3]) && !tienePermiso('operaciones', 'aprobar') && !tienePermiso('control_calidad', 'aprobar')) {
            $_SESSION['error'] = 'No tiene permisos de supervisión para aprobar matrices de ensayo.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idDetalle = (int)($datos['id_detalle'] ?? 0);
            $nota = trim($datos['nota_aprobacion'] ?? '');

            // CSRF
            if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                $_SESSION['error'] = 'Token CSRF inválido o sesión expirada.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            if ($idDetalle <= 0) {
                $_SESSION['error'] = 'Detalle inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $stmt = $db->prepare("SELECT cd.*, os.codigo_os, os.id AS id_os 
                                  FROM cotizacion_detalles cd
                                  JOIN ordenes_servicio os ON cd.id_cotizacion = os.id_cotizacion
                                  WHERE cd.id = :id LIMIT 1");
            $stmt->execute(['id' => $idDetalle]);
            $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($detalle) {
            $detalle['id'] = (int)$idDetalle;
            $detalle['id_detalle'] = (int)$idDetalle;
        }

            if (!$detalle) {
                $_SESSION['error'] = 'Ensayo no encontrado.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $revInfo = obtenerEstadoRevisionMatriz($detalle['resultados_json'] ?? '');
            if (!$revInfo['tiene_resultados']) {
                $_SESSION['error'] = 'No se puede aprobar una matriz que aún no cuenta con resultados registrados.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            // A2: Candado de Control de Calidad: Si existen réplicas (-CR), exigir que todas estén CONFORME
            $ccInfo = obtenerEstadoControlCalidadMatriz($detalle['resultados_json'] ?? '');
            if (!$ccInfo['puede_aprobar']) {
                $_SESSION['error'] = 'No se puede aprobar la matriz técnica: ' . $ccInfo['motivo_bloqueo'];
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $decoded = json_decode($detalle['resultados_json'] ?? '', true) ?: [];
            $filas = isset($decoded['filas']) ? $decoded['filas'] : (isset($decoded[0]) ? $decoded : []);
            $metadatos = $decoded['metadatos'] ?? [];
            $revision = isset($decoded['revision']) && is_array($decoded['revision']) ? $decoded['revision'] : [];
            $historial = $revision['historial'] ?? [];

            $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);
            $nombreUsuarioDb = '';
            if ($usuarioIdSesion > 0) {
                try {
                    $stmtUsr = $db->prepare("SELECT nombre FROM usuarios WHERE id = :id LIMIT 1");
                    $stmtUsr->execute(['id' => $usuarioIdSesion]);
                    $nombreUsuarioDb = trim((string)($stmtUsr->fetchColumn() ?: ''));
                } catch (\Throwable $e) {
                    $nombreUsuarioDb = '';
                }
            }
            $nombreSesion = trim((string)($_SESSION['usuario_nombre'] ?? ''));
            if ($nombreUsuarioDb !== '' && ($nombreSesion === '' || $nombreSesion === 'Supervisor' || $nombreSesion === 'Supervisor QA Test')) {
                $nombreAprobadorReal = $nombreUsuarioDb;
            } else {
                $nombreAprobadorReal = $nombreSesion !== '' ? $nombreSesion : ($nombreUsuarioDb !== '' ? $nombreUsuarioDb : 'Supervisor');
            }
            $usuarioSesion = $nombreAprobadorReal;
            $fechaActual = date('Y-m-d H:i:s');

            $historial[] = [
                'accion' => 'aprobada',
                'fecha' => $fechaActual,
                'usuario' => $nombreAprobadorReal,
                'usuario_id' => $usuarioIdSesion,
                'nota' => !empty($nota) ? $nota : 'Matriz técnica aprobada satisfactoriamente conforme a requisitos.'
            ];

            // Sección 4 y 10 Riesgo #4: Aprobación única del supervisor + blindaje contra suplantación por POST
            $revision['estado'] = 'aprobada';
            $revision['fecha_revision'] = $revision['fecha_revision'] ?? $fechaActual;
            $revision['usuario_revisor'] = !empty($revision['usuario_revisor']) ? (string)$revision['usuario_revisor'] : $nombreAprobadorReal;
            $revision['usuario_revisor_id'] = !empty($revision['usuario_revisor_id']) ? (int)$revision['usuario_revisor_id'] : $usuarioIdSesion;
            $revision['usuario_aprobador'] = $nombreAprobadorReal;
            $revision['usuario_aprobador_id'] = $usuarioIdSesion;
            $revision['fecha_aprobacion'] = $fechaActual;
            $revision['nota_aprobacion'] = $nota;
            $obsPendientesApr = trim((string)($datos['observaciones_pendientes'] ?? ($metadatos['observaciones_pendientes'] ?? ($revision['observaciones_pendientes'] ?? ''))));
            $revision['observaciones_pendientes'] = $obsPendientesApr;
            if ($obsPendientesApr !== '') {
                $metadatos['observaciones_pendientes'] = $obsPendientesApr;
            }
            $revision['motivo_devolucion'] = null;
            $revision['historial'] = $historial;

            $versiones = isset($decoded['versiones']) && is_array($decoded['versiones']) ? $decoded['versiones'] : [];
            $versionActual = (int)($decoded['version_actual'] ?? 1);

            $payload = [
                'version_actual' => $versionActual,
                'filas' => $filas,
                'metadatos' => $metadatos,
                'revision' => $revision,
                'versiones' => $versiones,
                'control_calidad' => $decoded['control_calidad'] ?? [],
                'usuario_captura_id' => $decoded['usuario_captura_id'] ?? null,
                'usuario_captura_nombre' => $decoded['usuario_captura_nombre'] ?? null
            ];

            $stmtUpdate = $db->prepare("UPDATE cotizacion_detalles SET resultados_json = :json WHERE id = :id");
            $stmtUpdate->execute([
                'json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'id' => $idDetalle
            ]);

            try {
                $hojaApr = resolverHojaPorDetalle($db, (int)$detalle['id_os'], $idDetalle);
                if ($hojaApr && !empty($hojaApr['id'])) {
                    $db->prepare("UPDATE hojas_solicitud SET etapa_flujo = 'informe' WHERE id = :hid")
                        ->execute(['hid' => (int)$hojaApr['id']]);
                } else {
                    $db->prepare("UPDATE hojas_solicitud SET etapa_flujo = 'informe' WHERE id_detalle_cotizacion = :id_det OR (id_detalle_cotizacion IS NULL AND id_os = :id_os)")
                        ->execute(['id_det' => $idDetalle, 'id_os' => (int)$detalle['id_os']]);
                }
            } catch (\Throwable $e) {
            }

            registrarEventoOS(
                'operaciones',
                'aprobar_matriz',
                "Matriz técnica APROBADA para '{$detalle['descripcion_ensayo']}' en O/S {$detalle['codigo_os']}" . (!empty($nota) ? " Nota: {$nota}" : ''),
                (int)$detalle['id_os'],
                [
                    'entidad_tipo' => 'matriz',
                    'entidad_id' => $idDetalle,
                    'datos' => ['nota' => $nota, 'usuario_aprobador' => $usuarioSesion, 'observaciones_pendientes' => $obsPendientesApr],
                ]
            );

            $envioAutoMsg = '';
            $envioAutoActivo = \Cycsa\Modulos\Operaciones\Servicios\ConfigCalculoLabServicio::obtenerBool('informe_envio_auto_aprobar', false, $db)
                || !empty($_POST['enviar_auto_cliente']);
            if ($envioAutoActivo && !defined('PHPUNIT_RUNNING')) {
                if ($this->despacharCorreoMatrizAutomatico($db, $idDetalle)) {
                    $envioAutoMsg = ' El informe PDF también fue enviado automáticamente al cliente.';
                }
            }

            $_SESSION['exito'] = "Matriz técnica aprobada con éxito. Ya se encuentra habilitada para emisión y envío al cliente." . $envioAutoMsg;
            $redir = $this->resolverRedireccionSegura($datos['redirect_to'] ?? null, '/Cycsa/publico/operaciones');
            $respuesta->redirigir($redir);
        }
    }

    /**
     * Devuelve una matriz técnica al personal técnico por observaciones o correcciones.
     */
    public function devolverMatrizProducto(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        // Roles autorizados: 1: Superadmin, 2: Administrador, 3: Supervisor
        $rolActual = (int)($_SESSION['usuario_rol'] ?? 0);
        if (!in_array($rolActual, [1, 2, 3]) && !tienePermiso('operaciones', 'aprobar') && !tienePermiso('control_calidad', 'aprobar')) {
            $_SESSION['error'] = 'No tiene permisos de supervisión para devolver matrices de ensayo.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idDetalle = (int)($datos['id_detalle'] ?? 0);
            $motivo = trim($datos['motivo_devolucion'] ?? '');

            // CSRF
            if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                $_SESSION['error'] = 'Token CSRF inválido o sesión expirada.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            if ($idDetalle <= 0) {
                $_SESSION['error'] = 'Detalle inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            if (empty($motivo)) {
                $_SESSION['error'] = 'Debe indicar el motivo o las observaciones de la devolución técnica para que el personal de laboratorio sepa qué corregir.';
                $redir = $this->resolverRedireccionSegura($datos['redirect_to'] ?? null, '/Cycsa/publico/operaciones');
                $respuesta->redirigir($redir);
                return;
            }

            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $stmt = $db->prepare("SELECT cd.*, os.codigo_os, os.id AS id_os 
                                  FROM cotizacion_detalles cd
                                  JOIN ordenes_servicio os ON cd.id_cotizacion = os.id_cotizacion
                                  WHERE cd.id = :id LIMIT 1");
            $stmt->execute(['id' => $idDetalle]);
            $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($detalle) {
            $detalle['id'] = (int)$idDetalle;
            $detalle['id_detalle'] = (int)$idDetalle;
        }

            if (!$detalle) {
                $_SESSION['error'] = 'Ensayo no encontrado.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $decoded = json_decode($detalle['resultados_json'] ?? '', true) ?: [];
            $filas = isset($decoded['filas']) ? $decoded['filas'] : (isset($decoded[0]) ? $decoded : []);
            $metadatos = $decoded['metadatos'] ?? [];
            $revision = isset($decoded['revision']) && is_array($decoded['revision']) ? $decoded['revision'] : [];
            $historial = $revision['historial'] ?? [];

            $versiones = isset($decoded['versiones']) && is_array($decoded['versiones']) ? $decoded['versiones'] : [];
            $versionActual = (int)($decoded['version_actual'] ?? 1);

            $usuarioSesion = $_SESSION['usuario_nombre'] ?? 'Supervisor';
            $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);
            $fechaActual = date('Y-m-d H:i:s');

            $historial[] = [
                'accion' => 'devuelta',
                'version' => $versionActual,
                'fecha' => $fechaActual,
                'usuario' => $usuarioSesion,
                'usuario_id' => $usuarioIdSesion,
                'motivo' => $motivo
            ];

            // 1. Guardar snapshot inmutable de la versión actual que fue devuelta
            $versiones[] = [
                'version' => $versionActual,
                'fecha' => $fechaActual,
                'estado' => 'devuelta',
                'usuario_envio' => $revision['usuario_envio'] ?? 'Laboratorio',
                'usuario_revisor' => $usuarioSesion,
                'usuario_revisor_id' => $usuarioIdSesion,
                'motivo_devolucion' => $motivo,
                'filas' => $filas,
                'metadatos' => $metadatos
            ];

            $revision['estado'] = 'devuelta';
            $revision['fecha_revision'] = $fechaActual;
            $revision['usuario_revisor'] = $usuarioSesion;
            $revision['usuario_revisor_id'] = $usuarioIdSesion;
            $revision['motivo_devolucion'] = $motivo;
            $revision['historial'] = $historial;

            // 2. La versión de trabajo avanza a la siguiente para admitir correcciones independientes
            $nuevaVersionActual = $versionActual + 1;

            $payload = [
                'version_actual' => $nuevaVersionActual,
                'filas' => $filas,
                'metadatos' => $metadatos,
                'revision' => $revision,
                'versiones' => $versiones,
                'control_calidad' => $decoded['control_calidad'] ?? [],
                'usuario_captura_id' => $decoded['usuario_captura_id'] ?? null,
                'usuario_captura_nombre' => $decoded['usuario_captura_nombre'] ?? null
            ];

            $stmtUpdate = $db->prepare("UPDATE cotizacion_detalles SET resultados_json = :json WHERE id = :id");
            $stmtUpdate->execute([
                'json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'id' => $idDetalle
            ]);

            try {
                $hojaDev = resolverHojaPorDetalle($db, (int)$detalle['id_os'], $idDetalle);
                if ($hojaDev && !empty($hojaDev['id'])) {
                    $db->prepare("UPDATE hojas_solicitud SET etapa_flujo = 'en_ejecucion' WHERE id = :hid")
                        ->execute(['hid' => (int)$hojaDev['id']]);
                } else {
                    $db->prepare("UPDATE hojas_solicitud SET etapa_flujo = 'en_ejecucion' WHERE id_detalle_cotizacion = :id_det OR (id_detalle_cotizacion IS NULL AND id_os = :id_os)")
                        ->execute(['id_det' => $idDetalle, 'id_os' => (int)$detalle['id_os']]);
                }
            } catch (\Throwable $e) {
            }

            registrarEventoOS(
                'operaciones',
                'devolver_matriz',
                "Matriz técnica DEVUELTA para '{$detalle['descripcion_ensayo']}' en O/S {$detalle['codigo_os']}. Observación: {$motivo}",
                (int)$detalle['id_os'],
                [
                    'entidad_tipo' => 'matriz',
                    'entidad_id' => $idDetalle,
                    'datos' => ['motivo' => $motivo],
                ]
            );

            $_SESSION['exito'] = "Matriz técnica devuelta al laboratorio con las observaciones registradas.";
            $redir = $this->resolverRedireccionSegura($datos['redirect_to'] ?? null, '/Cycsa/publico/operaciones');
            $respuesta->redirigir($redir);
        }
    }

    /**
     * Solicita una corrección administrativa para una matriz técnica ya aprobada (Fase B TAREA-059).
     * Emite una nueva versión correlativa BASE-N que sustituye a la previa y pasa a estado 'en_revision'.
     */
    public function solicitarCorreccionAdminPOST(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        // Roles autorizados: 1: Superadmin, 2: Administrador, 3: Supervisor
        if (!in_array($_SESSION['usuario_rol'] ?? 0, [1, 2, 3])) {
            $_SESSION['error'] = 'No tiene permisos de supervisión para solicitar correcciones administrativas.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idDetalle = (int)($datos['id_detalle'] ?? 0);
            $motivo = trim($datos['motivo_correccion'] ?? ($datos['motivo'] ?? ''));

            if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                $_SESSION['error'] = 'Token CSRF inválido o sesión expirada.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            if ($idDetalle <= 0) {
                $_SESSION['error'] = 'Detalle de ensayo inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            if (empty($motivo)) {
                $_SESSION['error'] = 'Debe especificar el motivo obligatorio de la corrección administrativa.';
                $respuesta->redirigir('/Cycsa/publico/operaciones/captura-matriz?id_detalle=' . $idDetalle);
                return;
            }

            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $stmt = $db->prepare("SELECT cd.*, os.codigo_os, os.id AS id_os 
                                  FROM cotizacion_detalles cd
                                  JOIN ordenes_servicio os ON cd.id_cotizacion = os.id_cotizacion
                                  WHERE cd.id = :id LIMIT 1");
            $stmt->execute(['id' => $idDetalle]);
            $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($detalle) {
            $detalle['id'] = (int)$idDetalle;
            $detalle['id_detalle'] = (int)$idDetalle;
        }

            if (!$detalle) {
                $_SESSION['error'] = 'Ensayo no encontrado.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $decoded = json_decode($detalle['resultados_json'] ?? '', true) ?: [];
            $filas = isset($decoded['filas']) ? $decoded['filas'] : (isset($decoded[0]) ? $decoded : []);
            $metadatos = $decoded['metadatos'] ?? [];
            $revision = isset($decoded['revision']) && is_array($decoded['revision']) ? $decoded['revision'] : [];
            $historial = $revision['historial'] ?? [];
            $versiones = isset($decoded['versiones']) && is_array($decoded['versiones']) ? $decoded['versiones'] : [];
            $versionActual = (int)($decoded['version_actual'] ?? 1);

            $usuarioSesion = $_SESSION['usuario_nombre'] ?? 'Supervisor';
            $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);
            $fechaActual = date('Y-m-d H:i:s');

            $codigoBase = generarCodigoInformeEnsayo(
                $filas,
                $metadatos['fecha_muestreo'] ?? ($metadatos['fecha_ingreso'] ?? null),
                $metadatos['tipo_muestra'] ?? ($detalle['descripcion_ensayo'] ?? ''),
                $detalle['codigo_os'] ?? null
            );
            $codigoPrevio = ($versionActual > 1) ? "{$codigoBase}-" . ($versionActual - 1) : $codigoBase;
            $nuevaVersionActual = $versionActual + 1;
            $nuevoCodigoVersion = "{$codigoBase}-" . ($nuevaVersionActual - 1);

            // 1. Archivar versión previa como anulada/sustituida en el historial
            $versiones[] = [
                'version' => $versionActual,
                'codigo_documento' => $codigoPrevio,
                'fecha' => $fechaActual,
                'estado' => 'anulada',
                'sustituida_por_version' => $nuevaVersionActual,
                'motivo_reemision' => $motivo,
                'tipo_correccion' => 'administrativa',
                'usuario_anulo' => $usuarioSesion,
                'usuario_anulo_id' => $usuarioIdSesion,
                'filas' => $filas,
                'metadatos' => $metadatos
            ];

            // 2. Aplicar campos administrativos modificados
            $camposModificados = [];
            if (!empty($datos['cliente_nombre'])) {
                $camposModificados['cliente_nombre'] = ['prev' => $metadatos['cliente_nombre'] ?? '', 'nuevo' => trim($datos['cliente_nombre'])];
                $metadatos['cliente_nombre'] = trim($datos['cliente_nombre']);
            }
            if (!empty($datos['nombre_proyecto'])) {
                $camposModificados['proyecto'] = ['prev' => $metadatos['proyecto'] ?? '', 'nuevo' => trim($datos['nombre_proyecto'])];
                $metadatos['proyecto'] = trim($datos['nombre_proyecto']);
            }
            if (isset($datos['ubicacion'])) {
                $camposModificados['ubicacion'] = ['prev' => $metadatos['ubicacion'] ?? '', 'nuevo' => trim($datos['ubicacion'])];
                $metadatos['ubicacion'] = trim($datos['ubicacion']);
            }
            if (isset($datos['identificacion'])) {
                $camposModificados['identificacion'] = ['prev' => $metadatos['identificacion'] ?? '', 'nuevo' => trim($datos['identificacion'])];
                $metadatos['identificacion'] = trim($datos['identificacion']);
            }
            if (isset($datos['atencion_a'])) {
                $camposModificados['atencion_a'] = ['prev' => $metadatos['atencion_a'] ?? '', 'nuevo' => trim($datos['atencion_a'])];
                $metadatos['atencion_a'] = trim($datos['atencion_a']);
            }

            // 3. Pasar a estado 'en_revision' para exigir re-aprobación obligatoria
            $historial[] = [
                'accion' => 'correccion_administrativa',
                'version' => $nuevaVersionActual,
                'fecha' => $fechaActual,
                'usuario' => $usuarioSesion,
                'usuario_id' => $usuarioIdSesion,
                'motivo' => $motivo,
                'sustituye_a' => $codigoPrevio
            ];

            $revision['estado'] = 'en_revision';
            $revision['fecha_envio'] = $fechaActual;
            $revision['usuario_envio'] = $usuarioSesion;
            $revision['usuario_envio_id'] = $usuarioIdSesion;
            $revision['fecha_revision'] = null;
            $revision['usuario_revisor'] = null;
            $revision['usuario_revisor_id'] = null;
            $revision['nota_aprobacion'] = null;
            $revision['historial'] = $historial;

            $reemisionInfo = [
                'version' => $nuevaVersionActual,
                'codigo_version' => $nuevoCodigoVersion,
                'sustituye_a' => $codigoPrevio,
                'tipo_correccion' => 'administrativa',
                'motivo' => $motivo,
                'fecha' => $fechaActual,
                'solicitado_por' => $usuarioSesion,
                'solicitado_por_id' => $usuarioIdSesion,
                'campos_modificados' => $camposModificados
            ];

            $payload = [
                'version_actual' => $nuevaVersionActual,
                'filas' => $filas,
                'metadatos' => $metadatos,
                'revision' => $revision,
                'versiones' => $versiones,
                'reemision' => $reemisionInfo,
                'control_calidad' => $decoded['control_calidad'] ?? [],
                'usuario_captura_id' => $decoded['usuario_captura_id'] ?? null,
                'usuario_captura_nombre' => $decoded['usuario_captura_nombre'] ?? null
            ];

            $stmtUpdate = $db->prepare("UPDATE cotizacion_detalles SET resultados_json = :json WHERE id = :id");
            $stmtUpdate->execute([
                'json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'id' => $idDetalle
            ]);

            // 4. Registrar en tabla documentos_reemisiones (Migración 021)
            try {
                $stmtReem = $db->prepare("
                    INSERT INTO documentos_reemisiones (tipo_documento, documento_base_id, version, codigo_version, sustituye_a, tipo_correccion, motivo, campos_modificados_json, estado, solicitado_por)
                    VALUES ('matriz_ensayo', :base_id, :version, :codigo, :sustituye, 'administrativa', :motivo, :campos, 'en_revision', :solicita)
                ");
                $stmtReem->execute([
                    'base_id' => $idDetalle,
                    'version' => $nuevaVersionActual,
                    'codigo' => $nuevoCodigoVersion,
                    'sustituye' => $codigoPrevio,
                    'motivo' => $motivo,
                    'campos' => json_encode($camposModificados, JSON_UNESCAPED_UNICODE),
                    'solicita' => $usuarioIdSesion
                ]);
            } catch (\Exception $e) {
                error_log('Error registrando en documentos_reemisiones: ' . $e->getMessage());
            }

            registrarEventoOS(
                'operaciones',
                'correccion_administrativa',
                "Corrección administrativa solicitada para '{$detalle['descripcion_ensayo']}' en O/S {$detalle['codigo_os']}. Nueva versión v{$nuevaVersionActual} ({$nuevoCodigoVersion}) sustituye a {$codigoPrevio}. Motivo: {$motivo}",
                (int)$detalle['id_os'],
                [
                    'entidad_tipo' => 'matriz',
                    'entidad_id' => $idDetalle,
                    'datos' => ['version' => $nuevaVersionActual, 'codigo_version' => $nuevoCodigoVersion],
                ]
            );

            $_SESSION['exito'] = "Corrección administrativa registrada como Versión {$nuevaVersionActual} ({$nuevoCodigoVersion}), sustituyendo al informe {$codigoPrevio}. La matriz se encuentra en estado PENDIENTE DE REVISIÓN para su re-aprobación formal.";
            $respuesta->redirigir('/Cycsa/publico/operaciones/captura-matriz?id_detalle=' . $idDetalle);
        }
    }

    /**
     * Cierra y archiva formalmente una Orden de Servicio (Doble validación: 100% Técnico + 100% Comercial).
     */
    public function cerrarOperacion(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        // Roles autorizados: 1: Superadmin, 2: Administrador, 3: Supervisor
        $rolActual = (int)($_SESSION['usuario_rol'] ?? 0);
        if (!in_array($rolActual, [1, 2, 3]) && !tienePermiso('operaciones', 'aprobar') && !tienePermiso('operaciones', 'crear_editar')) {
            $_SESSION['error'] = 'No tiene permisos de supervisión para cerrar o archivar órdenes de servicio.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idOS = (int)($datos['id_os'] ?? 0);

            // CSRF
            if (!isset($_SESSION['csrf_token'], $datos['csrf_token']) || !hash_equals($_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            if ($idOS <= 0) {
                $_SESSION['error'] = 'Orden de Servicio inválida.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $modelo = new OperacionModelo();
            $os = $modelo->obtenerOSPorId($idOS);
            if (!$os) {
                $_SESSION['error'] = 'Orden de Servicio no encontrada.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            if (in_array($os['estado'], ['Finalizado', 'Archivado', 'Cerrado'], true)) {
                $_SESSION['error'] = 'La Orden de Servicio ya está cerrada.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $db = Conexion::obtenerInstancia();
            $facturaNum = 'FAC-' . ($os['cot_codigo'] ?? '');

            try {
                $db->beginTransaction();
                $stmtOS = $db->prepare("SELECT estado FROM ordenes_servicio WHERE id = :id FOR UPDATE");
                $stmtOS->execute(['id' => $idOS]);
                $estadoActual = $stmtOS->fetchColumn();
                if ($estadoActual === false || in_array($estadoActual, ['Finalizado', 'Archivado', 'Cerrado'], true)) {
                    $db->rollBack();
                    $_SESSION['error'] = 'La Orden de Servicio no existe o ya está cerrada.';
                    $respuesta->redirigir('/Cycsa/publico/operaciones');
                    return;
                }
                $items = $modelo->obtenerItemsOS($idOS);
                $stmtCxc = $db->prepare("SELECT saldo, estado FROM cuentas_por_cobrar WHERE factura_numero = :fact LIMIT 1 FOR UPDATE");
                $stmtCxc->execute(['fact' => $facturaNum]);
                $cxc = $stmtCxc->fetch(PDO::FETCH_ASSOC);
                $condicionCom = strtolower(trim($os['condicion_comercial'] ?? $os['forma_pago'] ?? 'contado'));
                $error = CierreOperacionLims::cerrar(
                    $items,
                    $cxc ?: null,
                    fn(string $estado): bool => $modelo->actualizarEstadoOS($idOS, $estado, 'Cierre formal 100% técnico y comercial por supervisión'),
                    fn(): bool => registrarEventoOS('operaciones', 'cerrar_orden', "Orden de Servicio {$os['codigo_os']} finalizada y archivada formalmente (100% Ensayos Aprobados - Condición: {$condicionCom}).", $idOS, [
                        'datos' => ['condicion_comercial' => $condicionCom],
                    ]),
                    $condicionCom
                );
                if ($error !== null) {
                    $db->rollBack();
                    $_SESSION['error'] = $error;
                } else {
                    $db->commit();
                    $_SESSION['exito'] = "Orden de Servicio {$os['codigo_os']} cerrada y archivada exitosamente en el Histórico LIMS.";
                }
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log('Error al cerrar O/S: ' . $e->getMessage());
                $_SESSION['error'] = 'Ocurrió un error al cerrar la orden.';
            }

            $redir = !empty($datos['redirect_to']) ? $datos['redirect_to'] : '/Cycsa/publico/operaciones?tab=historico';
            $respuesta->redirigir($redir);
        }
    }

    /**
     * Visualización del documento oficial RT-FM-22 ya terminado con TODOS los datos
     * (Encabezado + Recepción + Resultados Técnicos) dentro del módulo Operaciones.
     */
    public function verDocumentoTerminado(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (function_exists('esTecnicoCiegoISO17025') && esTecnicoCiegoISO17025()) {
            $respuesta->redirigir('/Cycsa/publico/captura-tecnica');
            return;
        }
        $puedeVerDoc = tienePermiso('operaciones', 'ver')
            || tienePermiso('ordenes_servicio', 'ver')
            || tienePermiso('recepcion', 'ver')
            || tienePermiso('control_calidad', 'ver');
        if (!$puedeVerDoc) {
            $_SESSION['error'] = 'No tiene permiso para visualizar el documento de ensayo.';
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $idDetalle = (int)($_GET['id_detalle'] ?? ($_GET['id'] ?? 0));
        if ($idDetalle <= 0) {
            $_SESSION['error'] = 'ID de ensayo no especificado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $stmt = $db->prepare("
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
                   hs.id AS id_hoja_asociada, hs.procedencia_punto_muestreo, hs.nombre_persona_entrega_muestra, hs.fecha_hora_toma_muestra, hs.fecha_hora_llegada_laboratorio, hs.naturaleza_muestra, hs.observaciones,
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
        $stmt->execute(['id' => $idDetalle]);
        $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$detalle) {
            $_SESSION['error'] = 'Ensayo o documento no encontrado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }
        $detalle['id'] = (int)$idDetalle;
        $archivoMd = resolverArchivoMarkdownEnsayo($detalle);
        $detalle['archivo_markdown'] = $archivoMd;
        $schemaInfo = obtenerEsquemaPlantillaEnsayo($archivoMd, isset($detalle['formato_id']) ? (int)$detalle['formato_id'] : null);
        $columnas = !empty($schemaInfo['columns']) && is_array($schemaInfo['columns'])
            ? $schemaInfo['columns']
            : ($this->obtenerColumnasFormato($archivoMd) ?: ['Código laboratorio', 'Nombre muestra', 'Área (in²)', 'Carga (lb)', 'R. Compresión (lb/in²)', 'R. Compresión (kg/cm²)']);

        $dec = json_decode((string)($detalle['resultados_json'] ?? ''), true) ?: [];
        $filasDoc = isset($dec['filas']) && is_array($dec['filas'])
            ? $dec['filas']
            : (isset($dec[0]) && is_array($dec[0]) ? $dec : []);
        $filasDoc = filtrarFilasPublicasMatriz($filasDoc);
        $metadatosGuardados = isset($dec['metadatos']) && is_array($dec['metadatos']) ? $dec['metadatos'] : [];
        $versionActual = (int)($dec['version_actual'] ?? 1);

        // Fusionar respaldo de datos de Recepción desde RT-FM-13 para garantizar el documento 100% completo
        $hojaRes = resolverHojaPorDetalle($db, (int)$detalle['id_os'], $idDetalle);
        $idHojaRes = (int)($hojaRes['id'] ?? ($detalle['id_hoja_asociada'] ?? 0));
        $muestrasAsigDoc = obtenerMuestrasHojaParaDetalle($db, (int)$detalle['id_os'], $idDetalle, $hojaRes);
        $mapaRecDoc = $idHojaRes > 0 ? construirMapaDatosRecepcionPorHoja($db, $idHojaRes) : [];
        foreach ($muestrasAsigDoc as $mAd) {
            $codM = strtoupper(trim((string)($mAd['codigo_muestra'] ?? '')));
            if ($codM !== '' && !empty($mAd['datos_recepcion_json'])) {
                $decM = json_decode((string)$mAd['datos_recepcion_json'], true);
                if (is_array($decM) && !empty($decM)) {
                    $mapaRecDoc[$codM] = array_merge($mapaRecDoc[$codM] ?? [], $decM);
                }
            }
        }

        // Si aún no hay filas guardadas en resultados_json pero ya existen muestras/datos de Recepción, poblarlas en vivo
        if (empty($filasDoc) && !empty($muestrasAsigDoc)) {
            $colCodDoc = in_array('Código laboratorio', $columnas, true) ? 'Código laboratorio' : (in_array('Codigo Lab', $columnas, true) ? 'Codigo Lab' : 'Código laboratorio');
            foreach ($muestrasAsigDoc as $mAd) {
                $codOrig = trim((string)($mAd['codigo_muestra'] ?? ''));
                $codKey = strtoupper($codOrig);
                if ($codKey === '') continue;
                $filaNueva = [];
                foreach ($columnas as $colItem) {
                    $filaNueva[(string)$colItem] = '';
                }
                $filaNueva[$colCodDoc] = $codOrig;
                $nomBase = trim((string)($mapaRecDoc[$codKey]['Nombre muestra'] ?? ($mAd['descripcion_ubicacion'] ?? '')));
                if ($nomBase !== '') {
                    $filaNueva['Nombre muestra'] = $nomBase;
                }
                if (!empty($mapaRecDoc[$codKey])) {
                    $filaNueva = fusionarDatosRecepcionEnFilaMatriz($filaNueva, $mapaRecDoc[$codKey], $columnas, true, (string)$archivoMd);
                }
                $filasDoc[] = $filaNueva;
            }
        } elseif (!empty($mapaRecDoc)) {
            foreach ($filasDoc as $idxD => $fD) {
                if (!is_array($fD)) continue;
                $codD = strtoupper(trim((string)($fD['Código laboratorio'] ?? ($fD['Codigo laboratorio'] ?? ($fD['codigo_lab'] ?? '')))));
                if ($codD !== '' && isset($mapaRecDoc[$codD])) {
                    $filasDoc[$idxD] = fusionarDatosRecepcionEnFilaMatriz($fD, $mapaRecDoc[$codD], $columnas, true, (string)$archivoMd);
                    if (!empty($mapaRecDoc[$codD]['Nombre muestra'])) {
                        $filasDoc[$idxD]['Nombre muestra'] = (string)$mapaRecDoc[$codD]['Nombre muestra'];
                    }
                }
            }
        }

        $metadatos = resolverMetadatosEnsayo($detalle, $schemaInfo, $metadatosGuardados);
        $codigoInformeConsecutivo = generarCodigoInformeEnsayo(
            $filasDoc,
            $metadatos['fecha_muestreo'] ?? ($metadatos['fecha_ingreso'] ?? null),
            $metadatos['tipo_muestra'] ?? ($detalle['descripcion_ensayo'] ?? ''),
            $detalle['codigo_os'] ?? null
        );

        $authPagoDoc = verificarAutorizacionPagoEnvio($detalle, $db);
        $pagoAutorizadoEnvio = !empty($authPagoDoc['autorizado']);

        $this->renderizar('operaciones/vistas/documento_terminado', [
            'titulo' => 'Documento Oficial - ' . $detalle['codigo_os'] . ' · ' . $detalle['descripcion_ensayo'],
            'detalle' => $detalle,
            'idDetalle' => $idDetalle,
            'schemaInfo' => $schemaInfo,
            'metadatos' => $metadatos,
            'columnasDocumento' => $columnas,
            'filasDocumento' => $filasDoc,
            'versionActual' => $versionActual,
            'codigoInformeConsecutivo' => $codigoInformeConsecutivo,
            'pagoAutorizadoEnvio' => $pagoAutorizadoEnvio,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Guarda exclusivamente la cláusula (disclaimer) y notas editables de una matriz/documento
     * en Operaciones sin alterar filas de resultados, captura técnica ni estado de revisión.
     */
    public function guardarNotasDocumentoPOST(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (function_exists('esTecnicoCiegoISO17025') && esTecnicoCiegoISO17025()) {
            $_SESSION['error'] = 'Acceso restringido (ISO/IEC 17025): el perfil técnico no puede editar notas del informe oficial.';
            $respuesta->redirigir('/Cycsa/publico/captura-tecnica');
            return;
        }
        $puedeEditarNotas = tienePermiso('operaciones', 'ver')
            || tienePermiso('operaciones', 'crear_editar')
            || tienePermiso('ordenes_servicio', 'ver')
            || tienePermiso('recepcion', 'ver')
            || tienePermiso('control_calidad', 'ver');
        if (!$puedeEditarNotas) {
            $_SESSION['error'] = 'No tiene permiso para editar las notas del documento.';
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $idDetalle = (int)($datos['id_detalle'] ?? 0);
        $redirectTo = trim((string)($datos['redirect_to'] ?? ''));
        $destinoSeguro = ($redirectTo !== '' && strpos($redirectTo, '/Cycsa/publico/') === 0)
            ? $redirectTo
            : ($idDetalle > 0 ? '/Cycsa/publico/operaciones/documento?id_detalle=' . $idDetalle : '/Cycsa/publico/operaciones');

        $csrfRecibido = (string)($datos['csrf_token'] ?? '');
        $csrfSesion = (string)($_SESSION['csrf_token'] ?? '');
        if ($csrfSesion !== '' && !hash_equals($csrfSesion, $csrfRecibido)) {
            $_SESSION['error'] = 'Token de seguridad inválido. Recargue la página e intente nuevamente.';
            $respuesta->redirigir($destinoSeguro);
            return;
        }

        if ($idDetalle <= 0) {
            $_SESSION['error'] = 'Ensayo no especificado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $stmt = $db->prepare("SELECT id, resultados_json FROM cotizacion_detalles WHERE id = :id LIMIT 1");
        $stmt->execute(['id' => $idDetalle]);
        $filaDetalle = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$filaDetalle) {
            $_SESSION['error'] = 'Ensayo no encontrado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $dec = json_decode((string)($filaDetalle['resultados_json'] ?? ''), true);
        if (!is_array($dec)) {
            $dec = ['filas' => [], 'metadatos' => []];
        } elseif (!isset($dec['filas']) && !isset($dec['metadatos'])) {
            $dec = ['filas' => array_values($dec), 'metadatos' => []];
        }
        if (!isset($dec['metadatos']) || !is_array($dec['metadatos'])) {
            $dec['metadatos'] = [];
        }

        $disclaimerPersonalizado = trim((string)($datos['disclaimer_personalizado'] ?? ''));
        $notasRaw = (string)($datos['notas_informe'] ?? '');
        $lineasNotas = preg_split('/\r\n|\r|\n/', $notasRaw) ?: [];
        $notasLimpias = [];
        foreach ($lineasNotas as $linea) {
            $lineaTrim = trim((string)$linea);
            if ($lineaTrim !== '') {
                $notasLimpias[] = $lineaTrim;
            }
        }

        $dec['metadatos']['disclaimer_personalizado'] = $disclaimerPersonalizado;
        $dec['metadatos']['notas_informe'] = $notasLimpias;
        $dec['metadatos']['notas_informe_personalizadas'] = true;

        $jsonFinal = json_encode($dec, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        $stmtUpd = $db->prepare("UPDATE cotizacion_detalles SET resultados_json = :json WHERE id = :id");
        $stmtUpd->execute([
            'json' => $jsonFinal,
            'id' => $idDetalle,
        ]);

        $_SESSION['exito'] = 'Notas y cláusula del informe guardadas correctamente.';
        $respuesta->redirigir($destinoSeguro);
    }

    /**
     * Vista de Impresión Oficial con Membrete Horizontal CYCSA para cualquier Matriz Técnica
     */
    public function imprimirMatrizProducto(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (function_exists('esTecnicoCiegoISO17025') && esTecnicoCiegoISO17025()) {
            $_SESSION['error'] = 'Acceso restringido (ISO/IEC 17025): el perfil técnico no puede imprimir informes ni visualizar datos de cliente.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $idDetalle = (int)($_GET['id_detalle'] ?? ($_GET['id'] ?? 0));
        if ($idDetalle <= 0) {
            $_SESSION['error'] = 'ID de ensayo no especificado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $stmt = $db->prepare("
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
        $stmt->execute(['id' => $idDetalle]);
        $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($detalle) {
            $detalle['id'] = (int)$idDetalle;
            $detalle['id_detalle'] = (int)$idDetalle;
        }

        if (!$detalle) {
            $_SESSION['error'] = 'Producto o ensayo no encontrado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $hoja = resolverHojaPorDetalle($db, (int)$detalle['id_os'], $idDetalle);
        if ($hoja) {
            $detalle['procedencia_punto_muestreo'] = $hoja['procedencia_punto_muestreo'] ?? ($detalle['procedencia_punto_muestreo'] ?? '');
            $detalle['nombre_persona_entrega_muestra'] = $hoja['nombre_persona_entrega_muestra'] ?? ($detalle['nombre_persona_entrega_muestra'] ?? '');
            $detalle['fecha_hora_toma_muestra'] = $hoja['fecha_hora_toma_muestra'] ?? ($detalle['fecha_hora_toma_muestra'] ?? null);
            $detalle['fecha_hora_llegada_laboratorio'] = $hoja['fecha_hora_llegada_laboratorio'] ?? ($detalle['fecha_hora_llegada_laboratorio'] ?? null);
            $detalle['naturaleza_muestra'] = $hoja['naturaleza_muestra'] ?? ($detalle['naturaleza_muestra'] ?? '');
            $detalle['observaciones'] = $hoja['observaciones'] ?? ($detalle['observaciones'] ?? '');
            $detalle['id_hoja'] = (int)$hoja['id'];
            $detalle['numero_registro'] = $hoja['numero_registro'] ?? '';
            $detalle['incluir_firma_adicional'] = (int)($hoja['incluir_firma_adicional'] ?? 0);
            $detalle['firma_adicional_nombre'] = trim((string)($hoja['firma_adicional_nombre'] ?? ''));
            $detalle['firma_adicional_cargo'] = trim((string)($hoja['firma_adicional_cargo'] ?? ''));
        }

        // La impresión interna siempre se permite; el gate de pago bloquea únicamente el envío al cliente
        $authPago = verificarAutorizacionPagoEnvio($detalle, $db);
        $pagoAutorizadoEnvio = !empty($authPago['autorizado']);

        $archivoMd = !empty($detalle['archivo_markdown']) ? $detalle['archivo_markdown'] : resolverArchivoMarkdownEnsayo($detalle);
        $detalle['archivo_markdown'] = $archivoMd;
        $columnas = $this->obtenerColumnasFormato($archivoMd, $detalle['descripcion_ensayo'] ?? '');
        if (empty($columnas)) {
            $columnas = ["Código laboratorio", "Nombre muestra", "Área (in²)", "Carga (lb)", "R. Compresión (lb/in²)", "R. Compresión (kg/cm²)"];
        }

        $hoja = resolverHojaPorDetalle($db, (int)$detalle['id_os'], $idDetalle);
        if ($hoja) {
            $detalle['procedencia_punto_muestreo'] = $hoja['procedencia_punto_muestreo'] ?? ($detalle['procedencia_punto_muestreo'] ?? '');
            $detalle['nombre_persona_entrega_muestra'] = $hoja['nombre_persona_entrega_muestra'] ?? ($detalle['nombre_persona_entrega_muestra'] ?? '');
            $detalle['fecha_hora_toma_muestra'] = $hoja['fecha_hora_toma_muestra'] ?? ($detalle['fecha_hora_toma_muestra'] ?? null);
            $detalle['fecha_hora_llegada_laboratorio'] = $hoja['fecha_hora_llegada_laboratorio'] ?? ($detalle['fecha_hora_llegada_laboratorio'] ?? null);
            $detalle['naturaleza_muestra'] = $hoja['naturaleza_muestra'] ?? ($detalle['naturaleza_muestra'] ?? '');
            $detalle['observaciones'] = $hoja['observaciones'] ?? ($detalle['observaciones'] ?? '');
            $detalle['id_hoja'] = (int)$hoja['id'];
            $detalle['numero_registro'] = $hoja['numero_registro'] ?? '';
        }

        $idHojaResolv = $hoja ? (int)$hoja['id'] : 0;
        $esCompactacion = esItemCompactacion($detalle);
        $prefijoMuestraOS = determinarPrefijoMuestraOS($detalle);
        $muestrasSeteadas = [];

        if ($esCompactacion && $idHojaResolv > 0) {
            $stmtHsd = $db->prepare("SELECT codigo_muestra, descripcion_ubicacion FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = :id_hoja ORDER BY orden ASC, id ASC");
            $stmtHsd->execute(['id_hoja' => $idHojaResolv]);
            $filasHsd = $stmtHsd->fetchAll(PDO::FETCH_ASSOC);

            if (!empty($filasHsd)) {
                foreach ($filasHsd as $idx => $fh) {
                    $codLab = trim($fh['codigo_muestra'] ?? '');
                    if (empty($codLab)) {
                        $codLab = sprintf("{$prefijoMuestraOS}-%04d-%02d", $idx + 1, date('y'));
                    }
                    $muestrasSeteadas[] = [
                        'codigo_lab' => $codLab,
                        'codigo_campo' => $codLab,
                        'nombre_muestra' => !empty($fh['descripcion_ubicacion']) ? $fh['descripcion_ubicacion'] : ($detalle['descripcion_ensayo'] . ' - Muestra ' . ($idx + 1))
                    ];
                }
            } else {
                $mList = json_decode($hoja['muestras_json'] ?? '[]', true) ?: [];
                foreach ($mList as $idx => $md) {
                    $codLab = !empty($md['nombre_muestra']) ? trim($md['nombre_muestra']) : sprintf("{$prefijoMuestraOS}-%04d-%02d", $idx + 1, date('y'));
                    $muestrasSeteadas[] = [
                        'codigo_lab' => $codLab,
                        'codigo_campo' => $codLab,
                        'nombre_muestra' => !empty($md['descripcion']) ? trim($md['descripcion']) : ($detalle['descripcion_ensayo'] . ' - Muestra ' . ($idx + 1))
                    ];
                }
            }
        } else {
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
            } else {
                $modeloOp = new \Cycsa\Modulos\Operaciones\Modelos\OperacionModelo();
                $siguienteCorr = $modeloOp->obtenerSiguienteConsecutivoMuestra((int)date('Y'), $prefijoMuestraOS);
                $anioShort = date('y');
                $cantMuestras = max(1, (int)($detalle['cantidad'] ?? 1));
                for ($k = 0; $k < $cantMuestras; $k++) {
                    $codigoOficial = sprintf("{$prefijoMuestraOS}-%04d-%02d", $siguienteCorr + $k, $anioShort);
                    $muestrasSeteadas[] = [
                        'codigo_lab' => $codigoOficial,
                        'codigo_campo' => $codigoOficial,
                        'nombre_muestra' => ($prefijoMuestraOS === 'MC' ? 'Muestra de campo #' : 'Muestra de laboratorio #') . ($k + 1)
                    ];
                }
            }
        }

        $schemaInfo = obtenerEsquemaPlantillaEnsayo($detalle['archivo_markdown'] ?? null, isset($detalle['formato_id']) ? (int)$detalle['formato_id'] : null);
        $formatosSchemaJson = json_encode([$detalle['archivo_markdown'] ?? '' => $schemaInfo], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $metadatosGuardados = [];
        $decResultados = json_decode($detalle['resultados_json'] ?? '', true) ?: [];
        $versionActual = (int)($decResultados['version_actual'] ?? 1);
        $listaVersiones = isset($decResultados['versiones']) && is_array($decResultados['versiones']) ? $decResultados['versiones'] : [];
        $versionSolicitada = isset($_GET['version']) ? (int)$_GET['version'] : (isset($_GET['v']) ? (int)$_GET['v'] : $versionActual);

        $esVersionHistorica = false;
        $infoVersionImpresion = [
            'version' => $versionActual,
            'estado' => $decResultados['revision']['estado'] ?? 'pendiente',
            'es_actual' => true,
            'motivo_devolucion' => $decResultados['revision']['motivo_devolucion'] ?? null,
            'usuario_revisor' => $decResultados['revision']['usuario_revisor'] ?? null,
            'fecha' => $decResultados['revision']['fecha_revision'] ?? null
        ];

        if ($versionSolicitada > 0 && $versionSolicitada !== $versionActual) {
            foreach ($listaVersiones as $vItem) {
                if ((int)($vItem['version'] ?? 0) === $versionSolicitada) {
                    $esVersionHistorica = true;
                    $infoVersionImpresion = [
                        'version' => (int)$vItem['version'],
                        'estado' => $vItem['estado'] ?? 'devuelta',
                        'es_actual' => false,
                        'motivo_devolucion' => $vItem['motivo_devolucion'] ?? null,
                        'usuario_revisor' => $vItem['usuario_revisor'] ?? null,
                        'fecha' => $vItem['fecha'] ?? null
                    ];
                    $detalle['resultados_json'] = json_encode([
                        'filas' => $vItem['filas'] ?? [],
                        'metadatos' => $vItem['metadatos'] ?? []
                    ], JSON_UNESCAPED_UNICODE);

                    if (isset($vItem['metadatos']) && is_array($vItem['metadatos'])) {
                        $metadatosGuardados = $vItem['metadatos'];
                    }
                    break;
                }
            }
        } elseif (!empty($decResultados['metadatos']) && is_array($decResultados['metadatos'])) {
            $metadatosGuardados = $decResultados['metadatos'];
        }

        $metadatos = resolverMetadatosEnsayo($detalle, $schemaInfo, $metadatosGuardados);
        $sinLogo = !empty($_GET['sin_logo']) && (string)$_GET['sin_logo'] !== '0';
        $sinLogoAcreditacion = !empty($_GET['sin_logo_acreditacion']) && (string)$_GET['sin_logo_acreditacion'] !== '0';
        $revisionPrint = isset($decResultados['revision']) && is_array($decResultados['revision']) ? $decResultados['revision'] : [];
        $revisadoPorNombre = trim((string)($revisionPrint['usuario_revisor'] ?? ($infoVersionImpresion['usuario_revisor'] ?? '')));
        $aprobadoPorNombre = trim((string)($revisionPrint['usuario_aprobador'] ?? $revisadoPorNombre));
        $observacionesPendientes = trim((string)($metadatos['observaciones_pendientes'] ?? ($revisionPrint['observaciones_pendientes'] ?? '')));

        // Renderizado directo sin layout maestro para impresión limpia
        require dirname(__DIR__) . '/Vistas/matriz_print.php';
    }

    /**
     * Descarga o visualización directa del PDF oficial de la Matriz Técnica con membrete horizontal CYCSA
     */
    public function descargarMatrizPDF(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (function_exists('esTecnicoCiegoISO17025') && esTecnicoCiegoISO17025()) {
            $_SESSION['error'] = 'Acceso restringido (ISO/IEC 17025): el perfil técnico no puede descargar informes PDF ni visualizar datos de cliente.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $idDetalle = (int)($_GET['id_detalle'] ?? ($_GET['id'] ?? 0));
        if ($idDetalle <= 0) {
            $_SESSION['error'] = 'ID de ensayo no especificado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $stmt = $db->prepare("
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
        $stmt->execute(['id' => $idDetalle]);
        $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($detalle) {
            $detalle['id'] = (int)$idDetalle;
            $detalle['id_detalle'] = (int)$idDetalle;
        }

        if (!$detalle) {
            $_SESSION['error'] = 'Producto o ensayo no encontrado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $hojaPdf = resolverHojaPorDetalle($db, (int)$detalle['id_os'], $idDetalle);
        if ($hojaPdf) {
            $detalle['procedencia_punto_muestreo'] = $hojaPdf['procedencia_punto_muestreo'] ?? ($detalle['procedencia_punto_muestreo'] ?? '');
            $detalle['nombre_persona_entrega_muestra'] = $hojaPdf['nombre_persona_entrega_muestra'] ?? ($detalle['nombre_persona_entrega_muestra'] ?? '');
            $detalle['fecha_hora_toma_muestra'] = $hojaPdf['fecha_hora_toma_muestra'] ?? ($detalle['fecha_hora_toma_muestra'] ?? null);
            $detalle['fecha_hora_llegada_laboratorio'] = $hojaPdf['fecha_hora_llegada_laboratorio'] ?? ($detalle['fecha_hora_llegada_laboratorio'] ?? null);
            $detalle['naturaleza_muestra'] = $hojaPdf['naturaleza_muestra'] ?? ($detalle['naturaleza_muestra'] ?? '');
            $detalle['observaciones'] = $hojaPdf['observaciones'] ?? ($detalle['observaciones'] ?? '');
            $detalle['id_hoja'] = (int)$hojaPdf['id'];
            $detalle['numero_registro'] = $hojaPdf['numero_registro'] ?? '';
            $detalle['incluir_firma_adicional'] = (int)($hojaPdf['incluir_firma_adicional'] ?? 0);
            $detalle['firma_adicional_nombre'] = trim((string)($hojaPdf['firma_adicional_nombre'] ?? ''));
            $detalle['firma_adicional_cargo'] = trim((string)($hojaPdf['firma_adicional_cargo'] ?? ''));
        }

        // La impresión interna siempre se permite; el gate de pago bloquea únicamente el envío al cliente
        $authPago = verificarAutorizacionPagoEnvio($detalle, $db);
        $pagoAutorizadoEnvio = !empty($authPago['autorizado']);

        $archivoMd = !empty($detalle['archivo_markdown']) ? $detalle['archivo_markdown'] : resolverArchivoMarkdownEnsayo($detalle);
        $detalle['archivo_markdown'] = $archivoMd;
        $columnas = $this->obtenerColumnasFormato($archivoMd, $detalle['descripcion_ensayo'] ?? '');
        $muestrasSeteadas = [];

        $decResultados = json_decode($detalle['resultados_json'] ?? '', true) ?: [];
        $versionActual = (int)($decResultados['version_actual'] ?? 1);
        $listaVersiones = isset($decResultados['versiones']) && is_array($decResultados['versiones']) ? $decResultados['versiones'] : [];
        $versionSolicitada = isset($_GET['version']) ? (int)$_GET['version'] : (isset($_GET['v']) ? (int)$_GET['v'] : $versionActual);

        if ($versionSolicitada > 0 && $versionSolicitada !== $versionActual) {
            foreach ($listaVersiones as $vItem) {
                if ((int)($vItem['version'] ?? 0) === $versionSolicitada) {
                    $detalle['resultados_json'] = json_encode([
                        'filas' => $vItem['filas'] ?? [],
                        'metadatos' => $vItem['metadatos'] ?? []
                    ], JSON_UNESCAPED_UNICODE);
                    break;
                }
            }
        }

        $sinLogo = !empty($_GET['sin_logo']) && (string)$_GET['sin_logo'] !== '0';
        $sinLogoAcreditacion = !empty($_GET['sin_logo_acreditacion']) && (string)$_GET['sin_logo_acreditacion'] !== '0';
        $pdfBytes = generarMatrizTecnicaPDF($detalle, $muestrasSeteadas, $columnas, '', [
            'sin_logo' => $sinLogo,
            'sin_logo_acreditacion' => $sinLogoAcreditacion,
        ]);

        $codigoLimpio = preg_replace('/[^a-zA-Z0-9_-]/', '_', $detalle['codigo_os']);
        $sufijoVer = ($versionSolicitada > 0) ? "_v{$versionSolicitada}" : "";
        $nombreArchivo = "Matriz_Tecnica_{$codigoLimpio}_{$idDetalle}{$sufijoVer}.pdf";

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $nombreArchivo . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
        header('Pragma: no-cache');
        header('Expires: 0');
        echo $pdfBytes;
        exit;
    }

    /**
     * Envía la Matriz Técnica Oficial con Resultados en PDF al correo electrónico del cliente
     */
    public function enviarMatrizCliente(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (function_exists('esTecnicoCiegoISO17025') && esTecnicoCiegoISO17025()) {
            $_SESSION['error'] = 'Acceso restringido (ISO/IEC 17025): el perfil técnico no puede enviar informes al cliente.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }
        $puedeVerEntrega = tienePermiso('operaciones', 'ver')
            || tienePermiso('ordenes_servicio', 'ver')
            || tienePermiso('recepcion', 'ver')
            || tienePermiso('control_calidad', 'ver');
        if (!$puedeVerEntrega) {
            $_SESSION['error'] = 'No tiene permiso para enviar informes al cliente.';
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }

        $datos = $peticion->esPost() ? $peticion->obtenerDatos() : $_GET;
        $idDetalle = (int)($datos['id_detalle'] ?? ($datos['id'] ?? 0));

        if ($idDetalle <= 0) {
            $_SESSION['error'] = 'ID de ensayo no especificado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        if ($peticion->esPost()) {
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token de seguridad inválido o sesión expirada.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $stmt = $db->prepare("
            SELECT cd.id, cd.descripcion_ensayo, cd.codigo_servicio, cd.norma_astm, cd.resultados_json, cd.cantidad, cd.procedimiento, cd.condiciones_muestra,
                   os.id AS id_os, os.codigo_os, os.tecnico_muestreo, os.requiere_muestreo, os.fecha_muestreo, os.hora_muestreo, os.fecha_emision AS os_fecha_emision, os.fecha_registro_campo, os.created_at AS os_created_at,
                   os.forma_pago, COALESCE(os.condicion_comercial, cot.condicion_comercial, 'contado') AS condicion_comercial,
                   cot.id AS id_cotizacion, cot.codigo AS cot_codigo, cot.condicion_pago, cot.condicion_comercial AS cot_condicion_comercial, cot.nombre_proyecto, cot.direccion_proyecto, cot.atencion_a,
                   cli.id AS cliente_id, cli.nombre_razon_social AS cliente_nombre, cli.direccion AS cliente_direccion, cli.email AS cliente_email, cli.telefono AS cliente_telefono,
                   p.formato_id, p.nombre_comercial, p.ensayo_servicio, p.tipo_muestra AS prod_tipo_muestra, p.procedimiento_muestreo AS prod_procedimiento, p.norma_astm AS prod_norma_astm,
                   fe.nombre AS formato_nombre, fe.archivo_markdown, fe.codigo_formato AS codigo_documento, fe.procedimientos AS formato_procedimiento,
                   hs.procedencia_punto_muestreo, hs.nombre_persona_entrega_muestra, hs.fecha_hora_toma_muestra, hs.fecha_hora_llegada_laboratorio, hs.naturaleza_muestra, hs.observaciones,
                   rm.fecha_recepcion,
                   pm.fecha_ida AS pm_fecha_ida, pm.lugar_muestreo AS pm_lugar_muestreo
            FROM cotizacion_detalles cd
            JOIN ordenes_servicio os ON cd.id_cotizacion = os.id_cotizacion
            JOIN cotizaciones cot ON os.id_cotizacion = cot.id
            JOIN clientes cli ON cot.id_cliente = cli.id
            LEFT JOIN productos p ON cd.id_producto = p.id
            LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
            LEFT JOIN hojas_solicitud hs ON hs.id_os = os.id
            LEFT JOIN (SELECT id_os, MIN(fecha_recepcion) AS fecha_recepcion FROM recepcion_muestras GROUP BY id_os) rm ON rm.id_os = os.id
            LEFT JOIN (SELECT id_orden_servicio, MIN(fecha_ida) AS fecha_ida, MIN(lugar_muestreo) AS lugar_muestreo FROM programacion_muestreo GROUP BY id_orden_servicio) pm ON pm.id_orden_servicio = os.id
            WHERE cd.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $idDetalle]);
        $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($detalle) {
            $detalle['id'] = (int)$idDetalle;
            $detalle['id_detalle'] = (int)$idDetalle;
        }

        if (!$detalle) {
            $_SESSION['error'] = 'Producto o ensayo no encontrado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $hoja = resolverHojaPorDetalle($db, (int)$detalle['id_os'], $idDetalle);
        if ($hoja) {
            $detalle['procedencia_punto_muestreo'] = $hoja['procedencia_punto_muestreo'] ?? ($detalle['procedencia_punto_muestreo'] ?? '');
            $detalle['nombre_persona_entrega_muestra'] = $hoja['nombre_persona_entrega_muestra'] ?? ($detalle['nombre_persona_entrega_muestra'] ?? '');
            $detalle['fecha_hora_toma_muestra'] = $hoja['fecha_hora_toma_muestra'] ?? ($detalle['fecha_hora_toma_muestra'] ?? null);
            $detalle['fecha_hora_llegada_laboratorio'] = $hoja['fecha_hora_llegada_laboratorio'] ?? ($detalle['fecha_hora_llegada_laboratorio'] ?? null);
            $detalle['naturaleza_muestra'] = $hoja['naturaleza_muestra'] ?? ($detalle['naturaleza_muestra'] ?? '');
            $detalle['observaciones'] = $hoja['observaciones'] ?? ($detalle['observaciones'] ?? '');
            $detalle['id_hoja'] = (int)$hoja['id'];
            $detalle['numero_registro'] = $hoja['numero_registro'] ?? '';
            $detalle['incluir_firma_adicional'] = (int)($hoja['incluir_firma_adicional'] ?? 0);
            $detalle['firma_adicional_nombre'] = trim((string)($hoja['firma_adicional_nombre'] ?? ''));
            $detalle['firma_adicional_cargo'] = trim((string)($hoja['firma_adicional_cargo'] ?? ''));
        }

        // Gate Único de Envío Oficial al Cliente / Autorización de Pago
        $authPago = verificarAutorizacionPagoEnvio($detalle, $db);
        if (!$authPago['autorizado']) {
            $_SESSION['error'] = 'El cliente no ha pagado; no se pueden enviar resultados';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        // VALIDACIÓN CRUCIAL: Solo se puede enviar si la matriz TIENE RESULTADOS
        $resultados = json_decode($detalle['resultados_json'] ?? '', true) ?: [];
        if (isset($resultados['filas']) && is_array($resultados['filas'])) {
            $resultados = $resultados['filas'];
        } elseif (isset($resultados['metadatos'])) {
            $resultados = [];
        }
        $resultados = filtrarFilasPublicasMatriz(is_array($resultados) ? $resultados : []);
        if (empty($resultados)) {
            $_SESSION['error'] = 'No se puede enviar el informe al cliente porque la matriz técnica aún no tiene resultados registrados.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        // VALIDACIÓN DE CONTROL DE CALIDAD: Solo se puede enviar si está APROBADA
        $revInfo = obtenerEstadoRevisionMatriz($detalle['resultados_json'] ?? '');
        if ($revInfo['estado'] !== 'aprobada') {
            $_SESSION['error'] = 'No se puede enviar el informe al cliente porque la matriz técnica se encuentra en estado "' . $revInfo['estado_label'] . '". Requiere aprobación técnica previa por un supervisor.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        // VALIDACIÓN DE RÉPLICAS CC: Solo se puede enviar si todas las réplicas fueron dictaminadas como CONFORME
        $ccInfo = obtenerEstadoControlCalidadMatriz($detalle['resultados_json'] ?? '');
        if (!$ccInfo['puede_aprobar']) {
            $_SESSION['error'] = 'No se puede enviar el informe al cliente: ' . $ccInfo['motivo_bloqueo'];
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        // Destinatario(s): soporta múltiples correos separados por coma o punto y coma
        $destinatarioRaw = trim((string)($datos['destinatario'] ?? ($detalle['cliente_email'] ?? '')));
        $destinatariosLista = parsearListaCorreos($destinatarioRaw);
        if (empty($destinatariosLista)) {
            $_SESSION['error'] = 'El cliente no tiene un correo electrónico válido registrado para el envío (' . htmlspecialchars($destinatarioRaw) . '). Por favor especifique una o más direcciones válidas separadas por coma.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }
        $destinatario = implode(', ', $destinatariosLista);

        // Generar PDF idéntico a la impresión oficial con membrete horizontal
        $archivoMd = !empty($detalle['archivo_markdown']) ? $detalle['archivo_markdown'] : resolverArchivoMarkdownEnsayo($detalle);
        $detalle['archivo_markdown'] = $archivoMd;
        $columnas = $this->obtenerColumnasFormato($archivoMd, $detalle['descripcion_ensayo'] ?? '');
        $muestrasSeteadas = [];
        $sinLogo = !empty($_GET['sin_logo']) && (string)$_GET['sin_logo'] !== '0';
        $sinLogoAcreditacion = !empty($_GET['sin_logo_acreditacion']) && (string)$_GET['sin_logo_acreditacion'] !== '0';
        $pdfBytes = generarMatrizTecnicaPDF($detalle, $muestrasSeteadas, $columnas, '', [
            'sin_logo' => $sinLogo,
            'sin_logo_acreditacion' => $sinLogoAcreditacion,
        ]);

        $codigoLimpio = preg_replace('/[^a-zA-Z0-9_-]/', '_', $detalle['codigo_os']);
        $nombrePdf = "Informe_Ensayo_{$codigoLimpio}_{$idDetalle}.pdf";

        $adjuntos = [
            [
                'contenido' => $pdfBytes,
                'nombre' => $nombrePdf
            ]
        ];

        $codigoDoc = !empty($detalle['codigo_documento']) ? $detalle['codigo_documento'] : 'CYCSA-RT-FM-22';
        $asunto = !empty(trim($datos['asunto'] ?? '')) 
            ? trim($datos['asunto']) 
            : ("Informe Oficial de Ensayo - " . $detalle['codigo_os'] . " - " . $detalle['descripcion_ensayo'] . " - CYCSA");

        $clienteNom = htmlspecialchars($detalle['cliente_nombre'] ?? 'Estimado Cliente');
        $proyNom = htmlspecialchars($detalle['nombre_proyecto'] ?? 'Proyecto');
        $ensayoNom = htmlspecialchars($detalle['descripcion_ensayo'] ?? 'Ensayo');
        $normaAstm = htmlspecialchars(!empty($detalle['norma_astm']) ? $detalle['norma_astm'] : 'ASTM Oficial');
        $codigoOS = htmlspecialchars($detalle['codigo_os'] ?? '');
        $tecnicoResp = htmlspecialchars(!empty($detalle['tecnico_muestreo']) ? $detalle['tecnico_muestreo'] : 'Personal Técnico Autorizado');
        $fechaToma = !empty($detalle['fecha_hora_toma_muestra']) ? date('d/m/Y H:i', strtotime($detalle['fecha_hora_toma_muestra'])) : date('d/m/Y');

        $cuerpoHTML = "
        <div style=\"max-width: 650px; margin: 0 auto; font-family: Arial, Helvetica, sans-serif; line-height: 1.6; color: #1e293b; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; background-color: #ffffff;\">
            <div style=\"background-color: #103487; color: #ffffff; padding: 20px 25px;\">
                <h2 style=\"margin: 0; font-size: 18px; letter-spacing: 0.5px;\">Consultoría y Construcción S.A. (CYCSA)</h2>
                <div style=\"font-size: 12px; opacity: 0.9; margin-top: 4px;\">Laboratorio de Ensayos de Materiales y Control de Calidad &bull; ISO/IEC 17025:2017</div>
            </div>
            
            <div style=\"padding: 25px;\">
                <h3 style=\"color: #103487; margin-top: 0; font-size: 16px; border-bottom: 2px solid #e2e8f0; padding-bottom: 8px;\">Informe Oficial de Resultados Técnicos</h3>
                <p style=\"font-size: 14px;\">Estimado Cliente <strong>{$clienteNom}</strong>,</p>
                <p style=\"font-size: 13.5px;\">Le notificamos formalmente que se han finalizado y procesado los ensayos técnicos correspondientes a su Orden de Servicio. Los resultados han sido debidamente revisados y validados.</p>
                
                <div style=\"background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 15px; margin: 18px 0;\">
                    <table style=\"width: 100%; font-size: 12.5px; border-collapse: collapse;\">
                        <tr>
                            <td style=\"padding: 5px 0; color: #64748b; width: 38%;\"><strong>No. Orden Servicio:</strong></td>
                            <td style=\"padding: 5px 0; font-family: monospace; font-weight: bold; color: #103487;\">{$codigoOS}</td>
                        </tr>
                        <tr>
                            <td style=\"padding: 5px 0; color: #64748b;\"><strong>Proyecto:</strong></td>
                            <td style=\"padding: 5px 0; font-weight: 600;\">{$proyNom}</td>
                        </tr>
                        <tr>
                            <td style=\"padding: 5px 0; color: #64748b;\"><strong>Ensayo / Servicio:</strong></td>
                            <td style=\"padding: 5px 0;\">{$ensayoNom}</td>
                        </tr>
                        <tr>
                            <td style=\"padding: 5px 0; color: #64748b;\"><strong>Norma Técnica:</strong></td>
                            <td style=\"padding: 5px 0; font-family: monospace; font-weight: 600;\">{$normaAstm}</td>
                        </tr>
                        <tr>
                            <td style=\"padding: 5px 0; color: #64748b;\"><strong>Código de Formato:</strong></td>
                            <td style=\"padding: 5px 0; font-family: monospace; font-weight: bold;\">{$codigoDoc}</td>
                        </tr>
                        <tr>
                            <td style=\"padding: 5px 0; color: #64748b;\"><strong>Responsable Técnico:</strong></td>
                            <td style=\"padding: 5px 0;\">{$tecnicoResp}</td>
                        </tr>
                        <tr>
                            <td style=\"padding: 5px 0; color: #64748b;\"><strong>Fecha de Registro:</strong></td>
                            <td style=\"padding: 5px 0;\">{$fechaToma}</td>
                        </tr>
                    </table>
                </div>

                <div style=\"background-color: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px; padding: 12px 16px; margin: 16px 0; font-size: 13px; color: #065f46; display: flex; align-items: center; gap: 10px;\">
                    <span style=\"font-size: 18px;\">📎</span>
                    <span><strong>Archivo Adjunto:</strong> Adjunto a este mensaje encontrará el documento oficial en formato PDF (<em>{$nombrePdf}</em>) con la matriz técnica completa de resultados, condiciones de ensayo y firmas correspondientes.</span>
                </div>

                <p style=\"font-size: 12.5px; color: #475569;\">Si requiere información complementaria o aclaración sobre los resultados obtenidos, nuestro equipo de aseguramiento de calidad se encuentra a su entera disposición.</p>
                
                <div style=\"margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 15px; font-size: 11.5px; color: #94a3b8;\">
                    Consultoría y Construcción S.A. (CYCSA) &bull; Km 83.5 Carretera León-Managua &bull; Tel: (505) 2310-3988 / (505) 8851-6377 &bull; Correo: gerencia@cycsanic.com
                </div>
            </div>
        </div>";

        $enviado = enviarCorreo($destinatariosLista, $asunto, $cuerpoHTML, '', $adjuntos);

        if ($enviado) {
            registrarEventoOS('operaciones', 'enviar_matriz_cliente', 'Enviado informe de resultados de matriz (' . $detalle['codigo_os'] . ' - ' . $detalle['descripcion_ensayo'] . ') al correo: ' . $destinatario, (int)($detalle['id_os'] ?? 0), [
                'entidad_tipo' => 'matriz',
                'entidad_id' => $idDetalle,
                'id_referencia' => $idDetalle,
                'datos' => ['destinatario' => $destinatario, 'destinatarios' => $destinatariosLista],
            ]);
            
            $logDir = dirname(__DIR__, 4) . '/storage/logs';
            if (!is_dir($logDir)) {
                @mkdir($logDir, 0777, true);
            }
            $logMsg = "[" . date('Y-m-d H:i:s') . "] Informe de Matriz ENVIADO al Cliente. Destinatario: {$destinatario} | O/S: {$detalle['codigo_os']} | Ensayo: {$detalle['descripcion_ensayo']} | Archivo: {$nombrePdf}\n";
            @file_put_contents($logDir . '/operaciones_emails.log', $logMsg, FILE_APPEND);

            $_SESSION['exito'] = "¡Informe de resultados en PDF enviado con éxito al correo del cliente ({$destinatario})!";
        } else {
            $_SESSION['error'] = "No se pudo conectar al servidor de correo saliente. Verifique la configuración SMTP o revise storage/logs/mail_errors.log.";
        }

        $redirectSeguro = trim((string)($datos['redirect_to'] ?? ''));
        $retorno = $_GET['retorno'] ?? '';
        if ($redirectSeguro !== '' && str_starts_with($redirectSeguro, '/Cycsa/publico/')) {
            $respuesta->redirigir($redirectSeguro);
        } elseif ($retorno === 'print') {
            $respuesta->redirigir('/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=' . $idDetalle);
        } else {
            $respuesta->redirigir('/Cycsa/publico/operaciones');
        }
    }

    /**
     * Despacho automático opcional al aprobar una matriz (configurable en config_calculo_lab.informe_envio_auto_aprobar).
     */
    private function despacharCorreoMatrizAutomatico(\PDO $db, int $idDetalle): bool {
        try {
            $stmt = $db->prepare("
                SELECT cd.*, os.id AS id_os, os.codigo_os, os.tecnico_muestreo, os.requiere_muestreo,
                       os.fecha_muestreo, os.forma_pago,
                       COALESCE(os.condicion_comercial, cot.condicion_comercial, 'contado') AS condicion_comercial,
                       cot.id AS id_cotizacion, cot.codigo AS cot_codigo, cot.condicion_pago, cot.nombre_proyecto,
                       cli.id AS cliente_id, cli.nombre_razon_social AS cliente_nombre, cli.email AS cliente_email,
                       p.formato_id, fe.archivo_markdown, fe.codigo_formato AS codigo_documento
                FROM cotizacion_detalles cd
                JOIN ordenes_servicio os ON cd.id_cotizacion = os.id_cotizacion
                JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                JOIN clientes cli ON cot.id_cliente = cli.id
                LEFT JOIN productos p ON cd.id_producto = p.id
                LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                WHERE cd.id = :id LIMIT 1
            ");
            $stmt->execute(['id' => $idDetalle]);
            $det = $stmt->fetch(\PDO::FETCH_ASSOC);
            if (!$det) {
                return false;
            }
            $authPago = verificarAutorizacionPagoEnvio($det, $db);
            if (!$authPago['autorizado']) {
                return false;
            }
            $dests = parsearListaCorreos((string)($det['cliente_email'] ?? ''));
            if (empty($dests)) {
                return false;
            }
            $archivoMd = !empty($det['archivo_markdown']) ? (string)$det['archivo_markdown'] : resolverArchivoMarkdownEnsayo($det);
            $det['archivo_markdown'] = $archivoMd;
            $columnas = $this->obtenerColumnasFormato($archivoMd, $det['descripcion_ensayo'] ?? '');
            $pdfBytes = generarMatrizTecnicaPDF($det, [], $columnas);
            $codigoLimpio = preg_replace('/[^a-zA-Z0-9_-]/', '_', (string)($det['codigo_os'] ?? 'OS'));
            $nombrePdf = "Informe_Ensayo_{$codigoLimpio}_{$idDetalle}.pdf";
            $asunto = "Informe Oficial de Ensayo - " . ($det['codigo_os'] ?? '') . " - " . ($det['descripcion_ensayo'] ?? '') . " - CYCSA";
            $cuerpo = "<p>Estimado cliente, adjuntamos el informe oficial de ensayo aprobado para su Orden de Servicio <strong>" . htmlspecialchars((string)($det['codigo_os'] ?? '')) . "</strong>.</p>";
            return enviarCorreo($dests, $asunto, $cuerpo, '', [['contenido' => $pdfBytes, 'nombre' => $nombrePdf]]);
        } catch (\Throwable $e) {
            error_log('Error en despacho automático de matriz: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Tablero de comparación entre muestras originales y réplicas de control (-CR).
     */
    public function controlCalidadReplicas(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('control_calidad', 'ver')) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $db = Conexion::obtenerInstancia();
        $stmt = $db->query("
            SELECT cd.id, cd.descripcion_ensayo, cd.norma_astm, cd.resultados_json, cd.formato_reporte,
                   cd.procedimiento, cd.condiciones_muestra,
                   p.formato_id, p.nombre_comercial, p.ensayo_servicio, p.tipo_muestra AS prod_tipo_muestra, p.procedimiento_muestreo AS prod_procedimiento, p.norma_astm AS prod_norma_astm,
                   fe.archivo_markdown, fe.nombre AS formato_nombre, fe.codigo_formato AS codigo_documento, fe.procedimientos AS formato_procedimiento,
                   os.id AS id_os, os.codigo_os, os.created_at AS os_created_at, os.fecha_muestreo, os.fecha_emision AS os_fecha_emision,
                   cot.nombre_proyecto, cot.direccion_proyecto, cot.id_cliente, cot.atencion_a,
                   cli.nombre_razon_social AS cliente_nombre, cli.direccion AS cliente_direccion
            FROM cotizacion_detalles cd
            LEFT JOIN productos p ON p.id = cd.id_producto
            LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
            JOIN cotizaciones cot ON cot.id = cd.id_cotizacion
            JOIN ordenes_servicio os ON os.id_cotizacion = cot.id
            JOIN clientes cli ON cli.id = cot.id_cliente
            WHERE cd.resultados_json LIKE '%-CR%'
            ORDER BY cd.id DESC
        ");

        $informes = [];
        $comparaciones = [];

        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $detalle) {
            $dec = json_decode($detalle['resultados_json'] ?? '', true) ?: [];
            $filas = isset($dec['filas']) && is_array($dec['filas']) ? $dec['filas'] : (isset($dec[0]) ? $dec : []);
            $metadatos = $dec['metadatos'] ?? [];
            $schemaInfo = obtenerEsquemaPlantillaEnsayo($detalle['archivo_markdown'] ?? null, isset($detalle['formato_id']) ? (int)$detalle['formato_id'] : null);
            $metaOficial = resolverMetadatosEnsayo($detalle, $schemaInfo, $metadatos);
            $columnas = $schemaInfo['columns'] ?? [];
            if (empty($columnas) && !empty($filas)) {
                $columnas = array_values(array_filter(array_keys($filas[0]), fn($c) => !str_starts_with($c, '_')));
            }
            $pares = obtenerParesControlCalidad($filas);
            if (empty($pares)) {
                continue;
            }
            $evaluaciones = $dec['control_calidad']['replicas'] ?? [];
            foreach ($pares as &$par) {
                $clave = strtoupper((string)$par['codigo_original']);
                $par['evaluacion'] = is_array($evaluaciones[$clave] ?? null)
                    ? $evaluaciones[$clave]
                    : ['estado' => 'pendiente', 'observaciones' => '', 'usuario' => '', 'fecha' => ''];
                $comparaciones[] = array_merge($detalle, $par);
            }
            unset($par);

            $detalle['revision_info'] = obtenerEstadoRevisionMatriz($detalle['resultados_json'] ?? '');
            $detalle['cc_info'] = obtenerEstadoControlCalidadMatriz($detalle['resultados_json'] ?? '');

            $codigoInformeConsecutivo = generarCodigoInformeEnsayo(
                $filas,
                $metaOficial['fecha_muestreo'] ?? ($metaOficial['fecha_ingreso'] ?? null),
                $metaOficial['tipo_muestra'] ?? ($detalle['descripcion_ensayo'] ?? '')
            );

            $informes[] = [
                'detalle' => $detalle,
                'schemaInfo' => $schemaInfo,
                'metaOficial' => $metaOficial,
                'codigoInformeConsecutivo' => $codigoInformeConsecutivo,
                'columnas' => $columnas,
                'filas' => $filas,
                'pares' => $pares
            ];
        }

        $this->renderizar('operaciones/vistas/control_calidad_replicas', [
            'titulo' => 'Control de Calidad de Réplicas (ISO/IEC 17025)',
            'informes' => $informes,
            'comparaciones' => $comparaciones,
        ]);
    }

    /**
     * Vista dedicada a pantalla completa para inspección técnica y dictamen de un control de calidad.
     */
    public function controlCalidadDetalle(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('control_calidad', 'ver')) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $idDetalle = (int)($_GET['id_detalle'] ?? $_GET['id'] ?? $peticion->obtenerParametro('id_detalle') ?? $peticion->obtenerParametro('id') ?? 0);
        if ($idDetalle <= 0) {
            $_SESSION['error'] = 'Debe especificar un control de calidad válido.';
            $respuesta->redirigir('/Cycsa/publico/control-calidad');
            return;
        }

        $db = Conexion::obtenerInstancia();
        $stmt = $db->prepare("
            SELECT cd.id, cd.descripcion_ensayo, cd.norma_astm, cd.resultados_json, cd.formato_reporte,
                   cd.procedimiento, cd.condiciones_muestra,
                   p.formato_id, p.nombre_comercial, p.ensayo_servicio, p.tipo_muestra AS prod_tipo_muestra, p.procedimiento_muestreo AS prod_procedimiento, p.norma_astm AS prod_norma_astm,
                   fe.archivo_markdown, fe.nombre AS formato_nombre, fe.codigo_formato AS codigo_documento, fe.procedimientos AS formato_procedimiento,
                   os.id AS id_os, os.codigo_os, os.created_at AS os_created_at, os.fecha_muestreo, os.fecha_emision AS os_fecha_emision,
                   cot.nombre_proyecto, cot.direccion_proyecto, cot.id_cliente, cot.atencion_a,
                   cli.nombre_razon_social AS cliente_nombre, cli.direccion AS cliente_direccion
            FROM cotizacion_detalles cd
            LEFT JOIN productos p ON p.id = cd.id_producto
            LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
            JOIN cotizaciones cot ON cot.id = cd.id_cotizacion
            JOIN ordenes_servicio os ON os.id_cotizacion = cot.id
            JOIN clientes cli ON cli.id = cot.id_cliente
            WHERE cd.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => $idDetalle]);
        $detalle = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($detalle) {
            $detalle['id'] = (int)$idDetalle;
            $detalle['id_detalle'] = (int)$idDetalle;
        }

        if (!$detalle) {
            $_SESSION['error'] = 'El control de calidad solicitado no fue encontrado.';
            $respuesta->redirigir('/Cycsa/publico/control-calidad');
            return;
        }

        $dec = json_decode($detalle['resultados_json'] ?? '', true) ?: [];
        $filas = isset($dec['filas']) && is_array($dec['filas']) ? $dec['filas'] : (isset($dec[0]) ? $dec : []);
        $metadatos = $dec['metadatos'] ?? [];
        $schemaInfo = obtenerEsquemaPlantillaEnsayo($detalle['archivo_markdown'] ?? null, isset($detalle['formato_id']) ? (int)$detalle['formato_id'] : null);
        $metaOficial = resolverMetadatosEnsayo($detalle, $schemaInfo, $metadatos);
        $columnas = $schemaInfo['columns'] ?? [];
        if (empty($columnas) && !empty($filas)) {
            $columnas = array_values(array_filter(array_keys($filas[0]), fn($c) => !str_starts_with($c, '_')));
        }
        $pares = obtenerParesControlCalidad($filas);
        $evaluaciones = $dec['control_calidad']['replicas'] ?? [];
        foreach ($pares as &$par) {
            $clave = strtoupper((string)$par['codigo_original']);
            $par['evaluacion'] = is_array($evaluaciones[$clave] ?? null)
                ? $evaluaciones[$clave]
                : ['estado' => 'pendiente', 'observaciones' => '', 'usuario' => '', 'fecha' => ''];
        }
        unset($par);

        $codigoInformeConsecutivo = generarCodigoInformeEnsayo(
            $filas,
            $metaOficial['fecha_muestreo'] ?? ($metaOficial['fecha_ingreso'] ?? null),
            $metaOficial['tipo_muestra'] ?? ($detalle['descripcion_ensayo'] ?? '')
        );

        $informe = [
            'detalle' => $detalle,
            'schemaInfo' => $schemaInfo,
            'metaOficial' => $metaOficial,
            'codigoInformeConsecutivo' => $codigoInformeConsecutivo,
            'columnas' => $columnas,
            'filas' => $filas,
            'pares' => $pares
        ];

        $this->renderizar('operaciones/vistas/control_calidad_detalle', [
            'titulo' => 'Control de Calidad: ' . $codigoInformeConsecutivo,
            'informe' => $informe,
            'detalle' => $detalle,
            'metaOficial' => $metaOficial,
            'codigoInformeConsecutivo' => $codigoInformeConsecutivo,
            'columnas' => $columnas,
            'filas' => $filas,
            'pares' => $pares
        ]);
    }

    /**
     * Guarda el dictamen del responsable de Control de Calidad sin alterar las lecturas.
     */
    public function evaluarReplicaControlCalidad(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        // A5: Permisos específicos para dictamen de Control de Calidad (roles 1-3 o permiso explícito)
        $rolSesion = (int)($_SESSION['usuario_rol'] ?? 0);
        $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);
        if (!in_array($rolSesion, [1, 2, 3]) && !tienePermiso('control_calidad', 'crear_editar') && !tienePermiso('control_calidad', 'evaluar')) {
            $_SESSION['error'] = 'No tiene permisos autorizados de Control de Calidad para emitir dictámenes.';
            $respuesta->redirigir('/Cycsa/publico/control-calidad');
            return;
        }

        $datos = $peticion->obtenerDatos();
        if (!isset($_SESSION['csrf_token'], $datos['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
            $_SESSION['error'] = 'La sesión de seguridad expiró. Recargue la página.';
            $respuesta->redirigir('/Cycsa/publico/control-calidad');
            return;
        }

        $idDetalle = (int)($datos['id_detalle'] ?? 0);
        $codigoOriginal = trim((string)($datos['codigo_original'] ?? ''));
        $estado = (string)($datos['estado'] ?? 'pendiente');
        $observaciones = trim((string)($datos['observaciones'] ?? ''));
        if (!in_array($estado, ['pendiente', 'conforme', 'no_conforme'], true)) {
            $estado = 'pendiente';
        }
        if (function_exists('mb_substr')) {
            $observaciones = mb_substr($observaciones, 0, 2000, 'UTF-8');
        } else {
            $observaciones = substr($observaciones, 0, 2000);
        }

        $db = Conexion::obtenerInstancia();
        $stmt = $db->prepare('SELECT resultados_json FROM cotizacion_detalles WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $idDetalle]);
        $jsonActual = $stmt->fetchColumn();
        $dec = json_decode((string)$jsonActual, true) ?: [];
        $filas = isset($dec['filas']) && is_array($dec['filas']) ? $dec['filas'] : (isset($dec[0]) ? $dec : []);

        // A5: Imparcialidad ISO/IEC 17025 (no auto-evaluación del usuario que capturó los resultados)
        $usuarioCapturaId = (int)($dec['usuario_captura_id'] ?? ($dec['revision']['usuario_envio_id'] ?? 0));
        if ($rolSesion !== 1 && $usuarioCapturaId > 0 && $usuarioCapturaId === $usuarioIdSesion) {
            $_SESSION['error'] = 'Por norma técnica ISO/IEC 17025 (imparcialidad e independencia técnica), el usuario que capturó los resultados de la matriz no puede emitir el dictamen de Control de Calidad.';
            $respuesta->redirigir('/Cycsa/publico/control-calidad/ver?id_detalle=' . $idDetalle);
            return;
        }

        $parValido = false;
        foreach (obtenerParesControlCalidad($filas) as $par) {
            if (strcasecmp((string)$par['codigo_original'], $codigoOriginal) === 0) {
                $codigoOriginal = (string)$par['codigo_original'];
                $parValido = true;
                break;
            }
        }
        if (!$parValido) {
            $_SESSION['error'] = 'No se encontró la relación entre la muestra original y su réplica.';
            $respuesta->redirigir('/Cycsa/publico/control-calidad');
            return;
        }

        if (!isset($dec['control_calidad']) || !is_array($dec['control_calidad'])) {
            $dec['control_calidad'] = [];
        }
        if (!isset($dec['control_calidad']['replicas']) || !is_array($dec['control_calidad']['replicas'])) {
            $dec['control_calidad']['replicas'] = [];
        }
        $dec['control_calidad']['replicas'][strtoupper($codigoOriginal)] = [
            'estado' => $estado,
            'observaciones' => $observaciones,
            'usuario' => $_SESSION['usuario_nombre'] ?? 'Control de Calidad',
            'usuario_id' => (int)($_SESSION['usuario_id'] ?? 0),
            'fecha' => date('Y-m-d H:i:s'),
        ];

        $upd = $db->prepare('UPDATE cotizacion_detalles SET resultados_json = :json WHERE id = :id');
        $upd->execute([
            'json' => json_encode($dec, JSON_UNESCAPED_UNICODE),
            'id' => $idDetalle,
        ]);
        registrarBitacora('control_calidad', 'evaluar_replica', "Réplica {$codigoOriginal}-CR evaluada como {$estado}", $idDetalle);
        $_SESSION['exito'] = 'Evaluación de la réplica guardada correctamente.';
        $respuesta->redirigir('/Cycsa/publico/control-calidad/ver?id_detalle=' . $idDetalle);
    }

    /**
     * Valida y sanitiza una URL de redirección para evitar vulnerabilidades de Open Redirect.
     */
    private function resolverRedireccionSegura(?string $redir, string $fallback = '/Cycsa/publico/operaciones'): string {
        if (!empty($redir) && is_string($redir)) {
            $cand = trim($redir);
            if (strpos($cand, '/') === 0 && strpos($cand, '//') !== 0 && strpos($cand, '/\\') !== 0 && !preg_match('#^https?://#i', $cand)) {
                return $cand;
            }
        }
        return $fallback;
    }
}
