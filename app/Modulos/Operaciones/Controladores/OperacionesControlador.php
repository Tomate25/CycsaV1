<?php

namespace Cycsa\Modulos\Operaciones\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\Operaciones\Modelos\CierreOperacionLims;
use Cycsa\Nucleo\Conexion;
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
        
        if (($_SESSION['usuario_rol'] ?? 0) == 6) {
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        $this->verificarPermiso($respuesta, 'ver');

        $modelo = new OperacionModelo();
        $busqueda = $_GET['q'] ?? '';
        $tabActiva = $_GET['tab'] ?? 'activas';
        if (!in_array($tabActiva, ['activas', 'muestreo', 'ensayos', 'historico'], true)) {
            $tabActiva = 'activas';
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

        foreach ($ordenesActivas as &$o) {
            $o['items'] = $modelo->obtenerItemsOS((int)$o['id']);
            $o['hoja_solicitud'] = $modelo->obtenerHojaSolicitudPorOS((int)$o['id']);

            // Verificar si ya cuenta con muestras aceptadas en laboratorio
            $stmtRecCount = $db->prepare("SELECT COUNT(*) FROM recepcion_muestras WHERE id_os = :id_os");
            $stmtRecCount->execute(['id_os' => $o['id']]);
            $o['muestras_aceptadas_lab'] = (int)$stmtRecCount->fetchColumn();

            // Vincular estado y datos de facturación (CXC)
            $facturaNum = 'FAC-' . ($o['cot_codigo'] ?? '');
            $o['factura_numero'] = $facturaNum;
            $o['cxc'] = $cxcMap[$facturaNum] ?? null;

            // Calcular métricas de progreso de calidad y cierre (Técnico + Comercial)
            $totalEnsayos = count($o['items']);
            $ensayosAprobados = 0;
            $ensayosConResultados = 0;
            foreach ($o['items'] as &$it) {
                $it['revision_info'] = obtenerEstadoRevisionMatriz($it['resultados_json'] ?? null);
                if ($it['revision_info']['estado'] === 'aprobada') {
                    $ensayosAprobados++;
                }
                if ($it['revision_info']['tiene_resultados']) {
                    $ensayosConResultados++;
                }
            }
            unset($it);

            $o['total_ensayos'] = $totalEnsayos;
            $o['ensayos_aprobados'] = $ensayosAprobados;
            $o['ensayos_con_resultados'] = $ensayosConResultados;
            $o['tecnico_100'] = ($totalEnsayos > 0 && $ensayosAprobados === $totalEnsayos);

            $montoOS = (float)($o['cot_total'] ?? 0.0);
            $saldoOS = $o['cxc'] ? (float)$o['cxc']['saldo'] : $montoOS;
            $estadoPago = $o['cxc'] ? $o['cxc']['estado'] : 'Pendiente';
            $o['comercial_100'] = ($o['cxc'] !== null && $estadoPago === 'Pagado' && $saldoOS <= 0.01);
            $o['saldo_os'] = $saldoOS;
            $o['estado_pago'] = $estadoPago;

            // Condición explícita de cierre (100% técnico + 100% comercial)
            $o['puede_cerrar'] = ($o['tecnico_100'] && $o['comercial_100'] && !in_array($o['estado'], ['Finalizado', 'Archivado', 'Cerrado']));

            if (!$o['tecnico_100']) {
                $conteoEnsayosPendientes++;
            }
        }
        unset($o);

        $conteosTabs['ensayos'] = $conteoEnsayosPendientes;

        if ($tabActiva === 'ensayos') {
            $ordenesActivas = array_values(array_filter($ordenesActivas, fn($o) => !$o['tecnico_100']));
        }
        
        $bitacora_logs = obtenerBitacoraModulo('operaciones');

        $this->renderizar('operaciones/vistas/index', [
            'titulo' => 'Operaciones LIMS - Cycsa',
            'cotizaciones' => $cotizacionesParaOS,
            'ordenes' => $ordenesActivas,
            'busqueda' => $busqueda,
            'tabActiva' => $tabActiva,
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
                registrarBitacora('operaciones', 'crear_os', 'Orden de Servicio creada: ' . $codigoOS . ' con programación de muestreo.');
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

        // Obtener los detalles de la cotización para saber qué servicios/ensayos de compresión se cobraron
        $servicios = $modelo->obtenerDetallesCotizacion((int)$os['id_cotizacion']);
        
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
        $siguienteConsecutivo = $modelo->obtenerSiguienteConsecutivoMuestra((int)$anio, $prefijoMuestraOS);
        $codigoCampoAuto = !empty($os['hoja_campo_codigo']) ? $os['hoja_campo_codigo'] : $prefijoMuestraOS . '-' . sprintf("%03d", $siguienteConsecutivo) . '-' . $anio;

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

            if ($modelo->registrarRecepcion($datos)) {
                $_SESSION['exito'] = 'Recepción de muestras registrada exitosamente. Se ha generado la Hoja de Solicitud de Análisis Ciega para laboratorio.';
            } else {
                $_SESSION['error'] = 'Error al registrar la recepción de muestras.';
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
        
        if (($_SESSION['usuario_rol'] ?? 0) == 6) {
            $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-muestra?id_lote=' . $idLote);
            return;
        }

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
            $columnas = $this->obtenerColumnasFormato($detalle['archivo_markdown']);
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
        
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            die("ID de informe no válido.");
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $stmt = $db->prepare("SELECT ic.*, cot.condicion_pago, cot.codigo AS cot_codigo
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

        // Restricción de pago: Si el cliente es de CONTADO y tiene saldo pendiente en cuentas_por_cobrar
        if ($informe['condicion_pago'] === 'Contado') {
            $stmtCxC = $db->prepare("SELECT estado FROM cuentas_por_cobrar WHERE factura_numero = :codigo");
            $stmtCxC->execute(['codigo' => $informe['cot_codigo']]);
            $cxcEstado = $stmtCxC->fetchColumn();
            
            // Si tiene registro de cobro y no está Pagado, bloquear la descarga del PDF
            if ($cxcEstado && $cxcEstado !== 'Pagado') {
                $_SESSION['error'] = "Descarga denegada: Este cliente paga de Contado y posee saldo pendiente de pago.";
                $respuesta->redirigir('/Cycsa/publico/operaciones/detalle-lote?id_lote=' . $informe['id_lote']);
                return;
            }
        }

        // Servir el archivo PDF asegurando contención en el directorio almacenamiento/ (anti-path traversal)
        $baseAlmacenamiento = realpath(dirname(__DIR__, 4) . '/almacenamiento');
        $rutaPdf = dirname(__DIR__, 4) . '/' . ltrim(str_replace('\\', '/', $informe['ruta_archivo_pdf']), '/');
        $rutaReal = realpath($rutaPdf);

        if ($baseAlmacenamiento && $rutaReal && strpos($rutaReal, $baseAlmacenamiento) === 0 && file_exists($rutaReal)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($rutaReal) . '"');
            readfile($rutaReal);
            exit;
        } else {
            die("El archivo PDF físico del informe no se encuentra en el servidor o la ruta es inválida.");
        }
    }

    private function obtenerColumnasFormato(?string $archivo_markdown): array {
        return obtenerEsquemaPlantillaEnsayo($archivo_markdown)['columns'] ?? [];
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
                registrarBitacora('operaciones', 'cambiar_estado', 'Orden de Servicio ' . $codigoTexto . ' cambiada al estado: ' . $estado, $idOS);
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
                registrarBitacora('operaciones', 'programar_muestreo', 'Muestreo programado para Orden de Servicio ' . $codigoTexto, $idOS);
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
                registrarBitacora('operaciones', 'hoja_campo', 'Hoja de Campo registrada (CYCSA-RT-FM-07) para Orden de Servicio ' . $codigoTexto, $idOS);
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
                registrarBitacora('operaciones', 'omitir_espera', 'Período de espera liberado/omitido por supervisión para ' . $codigoTexto, $idOS);
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

        $detalles = $modelo->obtenerDetallesCotizacion((int)$os['id_cotizacion']);
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
                registrarBitacora('operaciones', 'hoja_solicitud', 'Hoja de Solicitud CYCSA-RT-FM-13 guardada y PDF generado para Orden de Servicio ' . $codigoTexto, $idOS);
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
                registrarBitacora('operaciones', 'emitir_solicitud', 'Solicitud emitida a técnicos para Orden de Servicio ' . $codigoTexto, $idOS);
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
                registrarBitacora('operaciones', 'enviar_revision_resultados', 'Resultados de ensayos enviados a revisión para Orden de Servicio ' . $codigoTexto, $idOS);
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
            if (!in_array($_SESSION['usuario_rol'] ?? 0, [1, 3])) {
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
                    registrarBitacora('operaciones', 'aprobar_revision_resultados', 'Revisión general de resultados aprobada para Orden de Servicio ' . $codigoTexto . '.', $idOS);
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
                    registrarBitacora('operaciones', 'observar_resultados', 'Resultados de Orden de Servicio ' . $codigoTexto . ' observados: ' . $motivo, $idOS);
                    $_SESSION['exito'] = 'Resultados rechazados y devueltos a ejecución de ensayos con la respectiva observación.';
                }
            }

            if (!$exito) {
                $_SESSION['error'] = 'Error al procesar la revisión de resultados.';
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones');
        }
    }

    public function descargarSolicitudPDF(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
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
            $nuevoNumReg = sprintf("%05d-%02d", $idOS, $totalHojas + 1);
            
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
        foreach ($items as &$it) {
            $it['revision_info'] = obtenerEstadoRevisionMatriz($it['resultados_json'] ?? null);
        }
        unset($it);

        $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS);
        $tieneHoja = !empty($hoja);

        $respuesta->enviarJson([
            'status' => 'success',
            'os' => $os,
            'items' => $items,
            'tiene_hoja' => $tieneHoja
        ]);
    }

    public function capturaMatrizProducto(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
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
                   cot.id AS id_cotizacion, cot.nombre_proyecto, cot.direccion_proyecto, cot.atencion_a,
                   cli.id AS id_cliente, cli.nombre_razon_social AS cliente_nombre, cli.direccion AS cliente_direccion, cli.email AS cliente_email, cli.telefono AS cliente_telefono,
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

        if (!$detalle) {
            $_SESSION['error'] = 'Producto o ensayo no encontrado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        // 1. Verificar que exista la Hoja de Servicio (CYCSA-RT-FM-13) registrada
        $stmtHoja = $db->prepare("SELECT id FROM hojas_solicitud WHERE id_os = :id_os LIMIT 1");
        $stmtHoja->execute(['id_os' => $detalle['id_os']]);
        $hojaExistente = $stmtHoja->fetchColumn();

        $esCompactacion = esItemCompactacion($detalle);

        // 1. Obtener muestras aceptadas formalmente en el laboratorio (recepcion_muestras)
        $stmtMuestrasLab = $db->prepare("
            SELECT rm.codigo_muestra, rm.codigo_campo, lm.nombre_lote
            FROM recepcion_muestras rm
            LEFT JOIN lotes_muestras lm ON lm.id_recepcion = rm.id
            WHERE rm.id_os = :id_os
            ORDER BY rm.id ASC
        ");
        $stmtMuestrasLab->execute(['id_os' => $detalle['id_os']]);
        $muestrasLabList = $stmtMuestrasLab->fetchAll(PDO::FETCH_ASSOC);

        // Control de Trazabilidad (ISO 17025):
        // Para ensayos convencionales de laboratorio con muestras físicas en custodia se requiere aceptación en Lab.
        // Para COMPACTACIONES / ENSAYOS IN SITU no se emite solicitud de muestras a laboratorio: pasan directo a llenado de matriz técnica en Operaciones.
        if (!$esCompactacion && empty($muestrasLabList)) {
            $_SESSION['error'] = 'Las muestras de la Orden de Servicio (' . ($detalle['codigo_os'] ?? 'O/S') . ') aún no han sido aceptadas e ingresadas en el Laboratorio. Primero debe realizar la aceptación técnica en el módulo de Laboratorio para asignar los códigos oficiales (MS-XXXX-26).';
            $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=kanban');
            return;
        }

        $columnas = $this->obtenerColumnasFormato($detalle['archivo_markdown']);
        if (empty($columnas)) {
            $columnas = ["Código laboratorio", "Nombre muestra", "Área (in²)", "Carga (lb)", "R. Compresión (lb/in²)", "R. Compresión (kg/cm²)"];
        }

        $muestrasSeteadas = [];
        if (!empty($muestrasLabList)) {
            foreach ($muestrasLabList as $idx => $ml) {
                $muestrasSeteadas[] = [
                    'codigo_lab' => $ml['codigo_muestra'],
                    'codigo_campo' => $ml['codigo_campo'],
                    'nombre_muestra' => !empty($ml['nombre_lote']) ? $ml['nombre_lote'] : ($detalle['descripcion_ensayo'] . ' - Muestra ' . ($idx + 1))
                ];
            }
        } else {
            // Caso especial: Ensayos in situ / Compactación sin muestras físicas en lab
            // Requiere OBLIGATORIAMENTE que exista al menos una Hoja RT-FM-13 registrada
            $modeloOp = new \Cycsa\Modulos\Operaciones\Modelos\OperacionModelo();
            $hojas = $modeloOp->obtenerHojasSolicitudPorOS((int)$detalle['id_os']);
            $muestrasDeclaradas = [];
            foreach ($hojas as $h) {
                $mList = json_decode($h['muestras_json'] ?? '[]', true) ?: [];
                foreach ($mList as $mItem) {
                    $muestrasDeclaradas[] = $mItem;
                }
            }

            // Si la O/S no tiene registrada ninguna Hoja RT-FM-13: Redirigir limpiamente a O/S sin alertas intrusivas
            if (empty($muestrasDeclaradas)) {
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . (int)$detalle['id_os']);
                return;
            }

            $prefijoMuestraOS = determinarPrefijoMuestraOS($detalle);

            foreach ($muestrasDeclaradas as $idx => $md) {
                $codLab = !empty($md['nombre_muestra']) ? $md['nombre_muestra'] : sprintf("{$prefijoMuestraOS}-%04d-%02d", $idx + 1, date('y'));
                $muestrasSeteadas[] = [
                    'codigo_lab' => $codLab,
                    'codigo_campo' => $codLab,
                    'nombre_muestra' => !empty($md['descripcion']) ? $md['descripcion'] : ($detalle['descripcion_ensayo'] . ' - Muestra ' . ($idx + 1))
                ];
            }
        }

        $schemaInfo = obtenerEsquemaPlantillaEnsayo($detalle['archivo_markdown'] ?? null, isset($detalle['formato_id']) ? (int)$detalle['formato_id'] : null);
        $formatosSchemaJson = json_encode([$detalle['archivo_markdown'] ?? '' => $schemaInfo], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT);

        $metadatosGuardados = [];
        $decResultados = json_decode($detalle['resultados_json'] ?? '', true) ?: [];
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
                        'filas' => $vItem['filas'] ?? [],
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
        }

        $metadatos = resolverMetadatosEnsayo($detalle, $schemaInfo, $metadatosGuardados);

        $this->renderizar('operaciones/vistas/captura_matriz', [
            'titulo' => 'Captura de Matriz Técnica - ' . $detalle['descripcion_ensayo'],
            'detalle' => $detalle,
            'columnas' => $columnas,
            'muestrasSeteadas' => $muestrasSeteadas,
            'formatosSchemaJson' => $formatosSchemaJson,
            'metadatos' => $metadatos,
            'schemaInfo' => $schemaInfo,
            'versionActual' => $versionActual,
            'versionSolicitada' => $versionSolicitada,
            'esHistorica' => $esHistorica,
            'listaVersiones' => $listaVersiones,
            'versionActivaInfo' => $versionActivaInfo,
            'prefijoMuestraOS' => $prefijoMuestraOS
        ]);
    }

    public function guardarMatrizProductoPOST(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
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
            $stmtDet = $db->prepare("SELECT id, id_cotizacion, descripcion_ensayo, resultados_json FROM cotizacion_detalles WHERE id = :id LIMIT 1");
            $stmtDet->execute(['id' => $idDetalle]);
            $detalleActual = $stmtDet->fetch(PDO::FETCH_ASSOC);

            if (!$detalleActual) {
                $_SESSION['error'] = 'Detalle de ensayo no encontrado.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            // Desempaquetar filas y metadatos existentes
            $filasDecodificadas = json_decode($resultadosJson, true) ?: [];
            if (isset($filasDecodificadas['filas'])) {
                $filasDecodificadas = $filasDecodificadas['filas'];
            }
            $filasDecodificadas = normalizarReplicasMatriz(is_array($filasDecodificadas) ? $filasDecodificadas : []);

            $decExistente = json_decode($detalleActual['resultados_json'] ?? '', true) ?: [];
            $metadatos = !empty($datos['metadatos']) && is_array($datos['metadatos'])
                ? $datos['metadatos']
                : ($decExistente['metadatos'] ?? []);

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

            $payloadCompleto = [
                'version_actual' => $versionActual,
                'filas' => $filasDecodificadas,
                'metadatos' => $metadatos,
                'revision' => $nuevaRevision,
                'versiones' => $versionesExistentes,
                'control_calidad' => isset($decExistente['control_calidad']) && is_array($decExistente['control_calidad'])
                    ? $decExistente['control_calidad']
                    : []
            ];
            $resultadosJsonFinal = json_encode($payloadCompleto, JSON_UNESCAPED_UNICODE);

            $stmt = $db->prepare("UPDATE cotizacion_detalles SET resultados_json = :json WHERE id = :id");
            $stmt->execute([
                'json' => $resultadosJsonFinal,
                'id' => $idDetalle
            ]);

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
            } elseif (!isset($metadatos['logo_acreditacion']) && !empty($decExistente['metadatos']['logo_acreditacion'])) {
                $metadatos['logo_acreditacion'] = $decExistente['metadatos']['logo_acreditacion'];
            }

            registrarBitacora(
                'operaciones',
                'guardar_matriz',
                "Matriz técnica guardada como Pendiente de revisión para '{$detalleActual['descripcion_ensayo']}' (Detalle #{$idDetalle})",
                $idDetalle
            );

            $_SESSION['exito'] = 'Matriz técnica guardada correctamente con estado Pendiente de revisión.';
            $redir = !empty($datos['redirect_to']) ? $datos['redirect_to'] : '/Cycsa/publico/operaciones';
            $respuesta->redirigir($redir);
        }
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

                registrarBitacora(
                    'operaciones',
                    'subir_logo_acreditacion',
                    "Asignado sello/logo de acreditación para '{$rowDet['descripcion_ensayo']}' (Detalle #{$idDetalle})",
                    $idDetalle
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

                registrarBitacora(
                    'operaciones',
                    'eliminar_logo_acreditacion',
                    "Retirado sello/logo de acreditación para '{$rowDet['descripcion_ensayo']}' (Detalle #{$idDetalle})",
                    $idDetalle
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
        if (!in_array($_SESSION['usuario_rol'] ?? 0, [1, 2, 3])) {
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

            $decoded = json_decode($detalle['resultados_json'] ?? '', true) ?: [];
            $filas = isset($decoded['filas']) ? $decoded['filas'] : (isset($decoded[0]) ? $decoded : []);
            $metadatos = $decoded['metadatos'] ?? [];
            $revision = isset($decoded['revision']) && is_array($decoded['revision']) ? $decoded['revision'] : [];
            $historial = $revision['historial'] ?? [];

            $usuarioSesion = $_SESSION['usuario_nombre'] ?? 'Supervisor';
            $usuarioIdSesion = (int)($_SESSION['usuario_id'] ?? 0);
            $fechaActual = date('Y-m-d H:i:s');

            $historial[] = [
                'accion' => 'aprobada',
                'fecha' => $fechaActual,
                'usuario' => $usuarioSesion,
                'usuario_id' => $usuarioIdSesion,
                'nota' => !empty($nota) ? $nota : 'Matriz técnica aprobada satisfactoriamente conforme a requisitos.'
            ];

            $revision['estado'] = 'aprobada';
            $revision['fecha_revision'] = $fechaActual;
            $revision['usuario_revisor'] = $usuarioSesion;
            $revision['usuario_revisor_id'] = $usuarioIdSesion;
            $revision['nota_aprobacion'] = $nota;
            $revision['motivo_devolucion'] = null;
            $revision['historial'] = $historial;

            $versiones = isset($decoded['versiones']) && is_array($decoded['versiones']) ? $decoded['versiones'] : [];
            $versionActual = (int)($decoded['version_actual'] ?? 1);

            $payload = [
                'version_actual' => $versionActual,
                'filas' => $filas,
                'metadatos' => $metadatos,
                'revision' => $revision,
                'versiones' => $versiones
            ];

            $stmtUpdate = $db->prepare("UPDATE cotizacion_detalles SET resultados_json = :json WHERE id = :id");
            $stmtUpdate->execute([
                'json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'id' => $idDetalle
            ]);

            registrarBitacora(
                'operaciones',
                'aprobar_matriz',
                "Matriz técnica APROBADA para '{$detalle['descripcion_ensayo']}' en O/S {$detalle['codigo_os']}" . (!empty($nota) ? " Nota: {$nota}" : ''),
                $detalle['id_os']
            );

            $_SESSION['exito'] = "Matriz técnica aprobada con éxito. Ya se encuentra habilitada para emisión y envío al cliente.";
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
        if (!in_array($_SESSION['usuario_rol'] ?? 0, [1, 2, 3])) {
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
                'versiones' => $versiones
            ];

            $stmtUpdate = $db->prepare("UPDATE cotizacion_detalles SET resultados_json = :json WHERE id = :id");
            $stmtUpdate->execute([
                'json' => json_encode($payload, JSON_UNESCAPED_UNICODE),
                'id' => $idDetalle
            ]);

            registrarBitacora(
                'operaciones',
                'devolver_matriz',
                "Matriz técnica DEVUELTA para '{$detalle['descripcion_ensayo']}' en O/S {$detalle['codigo_os']}. Observación: {$motivo}",
                $detalle['id_os']
            );

            $_SESSION['exito'] = "Matriz técnica devuelta al laboratorio con las observaciones registradas.";
            $redir = $this->resolverRedireccionSegura($datos['redirect_to'] ?? null, '/Cycsa/publico/operaciones');
            $respuesta->redirigir($redir);
        }
    }

    /**
     * Cierra y archiva formalmente una Orden de Servicio (Doble validación: 100% Técnico + 100% Comercial).
     */
    public function cerrarOperacion(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        // Roles autorizados: 1: Superadmin, 2: Administrador, 3: Supervisor
        if (!in_array($_SESSION['usuario_rol'] ?? 0, [1, 2, 3])) {
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
                $error = CierreOperacionLims::cerrar(
                    $items,
                    $cxc ?: null,
                    fn(string $estado): bool => $modelo->actualizarEstadoOS($idOS, $estado, 'Cierre formal 100% técnico y comercial por supervisión'),
                    fn(): bool => registrarBitacora('operaciones', 'cerrar_orden', "Orden de Servicio {$os['codigo_os']} finalizada y archivada formalmente (100% Ensayos Aprobados y Pagada).", $idOS)
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
     * Vista de Impresión Oficial con Membrete Horizontal CYCSA para cualquier Matriz Técnica
     */
    public function imprimirMatrizProducto(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

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
                   cot.id AS id_cotizacion, cot.nombre_proyecto, cot.direccion_proyecto, cot.atencion_a,
                   cli.id AS id_cliente, cli.nombre_razon_social AS cliente_nombre, cli.direccion AS cliente_direccion, cli.email AS cliente_email, cli.telefono AS cliente_telefono,
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

        if (!$detalle) {
            $_SESSION['error'] = 'Producto o ensayo no encontrado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $columnas = $this->obtenerColumnasFormato($detalle['archivo_markdown']);
        if (empty($columnas)) {
            $columnas = ["Código laboratorio", "Nombre muestra", "Área (in²)", "Carga (lb)", "R. Compresión (lb/in²)", "R. Compresión (kg/cm²)"];
        }

        $esCompactacion = esItemCompactacion($detalle);

        $stmtMuestrasLab = $db->prepare("
            SELECT rm.codigo_muestra, rm.codigo_campo, lm.nombre_lote
            FROM recepcion_muestras rm
            LEFT JOIN lotes_muestras lm ON lm.id_recepcion = rm.id
            WHERE rm.id_os = :id_os
            ORDER BY rm.id ASC
        ");
        $stmtMuestrasLab->execute(['id_os' => $detalle['id_os']]);
        $muestrasLabList = $stmtMuestrasLab->fetchAll(PDO::FETCH_ASSOC);

        $muestrasSeteadas = [];
        if (!empty($muestrasLabList)) {
            foreach ($muestrasLabList as $idx => $ml) {
                $muestrasSeteadas[] = [
                    'codigo_lab' => $ml['codigo_muestra'],
                    'codigo_campo' => $ml['codigo_campo'],
                    'nombre_muestra' => !empty($ml['nombre_lote']) ? $ml['nombre_lote'] : ($detalle['descripcion_ensayo'] . ' - Muestra ' . ($idx + 1))
                ];
            }
        } else {
            $modeloOp = new \Cycsa\Modulos\Operaciones\Modelos\OperacionModelo();
            $hojas = $modeloOp->obtenerHojasSolicitudPorOS((int)$detalle['id_os']);
            $muestrasDeclaradas = [];
            foreach ($hojas as $h) {
                $mList = json_decode($h['muestras_json'] ?? '[]', true) ?: [];
                foreach ($mList as $mItem) {
                    $muestrasDeclaradas[] = $mItem;
                }
            }

            $prefijoMuestraOS = determinarPrefijoMuestraOS($detalle);

            if (!empty($muestrasDeclaradas)) {
                foreach ($muestrasDeclaradas as $idx => $md) {
                    $codLab = !empty($md['nombre_muestra']) ? $md['nombre_muestra'] : sprintf("{$prefijoMuestraOS}-%04d-%02d", $idx + 1, date('y'));
                    $muestrasSeteadas[] = [
                        'codigo_lab' => $codLab,
                        'codigo_campo' => $codLab,
                        'nombre_muestra' => !empty($md['descripcion']) ? $md['descripcion'] : ($detalle['descripcion_ensayo'] . ' - Muestra ' . ($idx + 1))
                    ];
                }
            } else {
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

        // Renderizado directo sin layout maestro para impresión limpia
        require dirname(__DIR__) . '/Vistas/matriz_print.php';
    }

    /**
     * Descarga o visualización directa del PDF oficial de la Matriz Técnica con membrete horizontal CYCSA
     */
    public function descargarMatrizPDF(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

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
                   cot.id AS id_cotizacion, cot.nombre_proyecto, cot.direccion_proyecto, cot.atencion_a,
                   cli.id AS id_cliente, cli.nombre_razon_social AS cliente_nombre, cli.direccion AS cliente_direccion, cli.email AS cliente_email, cli.telefono AS cliente_telefono,
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

        if (!$detalle) {
            $_SESSION['error'] = 'Producto o ensayo no encontrado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $columnas = $this->obtenerColumnasFormato($detalle['archivo_markdown']);
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

        $pdfBytes = generarMatrizTecnicaPDF($detalle, $muestrasSeteadas, $columnas);

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
        $this->verificarPermiso($respuesta, 'ver');

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
                   cot.id AS id_cotizacion, cot.codigo AS cot_codigo, cot.nombre_proyecto, cot.direccion_proyecto, cot.atencion_a,
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

        if (!$detalle) {
            $_SESSION['error'] = 'Producto o ensayo no encontrado.';
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

        // Destinatario: tomar el especificado en el formulario o el registrado en la base de datos del cliente
        $destinatario = trim($datos['destinatario'] ?? ($detalle['cliente_email'] ?? ''));
        if (empty($destinatario) || !filter_var($destinatario, FILTER_VALIDATE_EMAIL)) {
            $_SESSION['error'] = 'El cliente no tiene un correo electrónico válido registrado para el envío (' . htmlspecialchars($destinatario) . '). Por favor especifique una dirección válida.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        // Generar PDF idéntico a la impresión oficial con membrete horizontal
        $columnas = $this->obtenerColumnasFormato($detalle['archivo_markdown']);
        $muestrasSeteadas = [];
        $pdfBytes = generarMatrizTecnicaPDF($detalle, $muestrasSeteadas, $columnas);

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

        $enviado = enviarCorreo($destinatario, $asunto, $cuerpoHTML, '', $adjuntos);

        if ($enviado) {
            registrarBitacora('operaciones', 'enviar_matriz_cliente', 'Enviado informe de resultados de matriz (' . $detalle['codigo_os'] . ' - ' . $detalle['descripcion_ensayo'] . ') al correo: ' . $destinatario, $idDetalle);
            
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

        $retorno = $_GET['retorno'] ?? '';
        if ($retorno === 'print') {
            $respuesta->redirigir('/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=' . $idDetalle);
        } else {
            $respuesta->redirigir('/Cycsa/publico/operaciones');
        }
    }

    /**
     * Tablero de comparación entre muestras originales y réplicas de control (-CR).
     */
    public function controlCalidadReplicas(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('operaciones', 'ver') && !tienePermiso('laboratorio', 'ver')) {
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
        if (!tienePermiso('operaciones', 'ver') && !tienePermiso('laboratorio', 'ver')) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $idDetalle = (int)($peticion->obtenerParametro('id_detalle') ?? $peticion->obtenerParametro('id') ?? 0);
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
        if (!tienePermiso('operaciones', 'ver') && !tienePermiso('laboratorio', 'ver')) {
            $respuesta->redirigir('/Cycsa/publico/panel');
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
