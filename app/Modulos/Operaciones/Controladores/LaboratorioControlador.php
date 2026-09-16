<?php

namespace Cycsa\Modulos\Operaciones\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use PDO;

class LaboratorioControlador extends ControladorBase {

    private function verificarSesion(Respuesta $respuesta): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
    }

    private function verificarPermiso(Respuesta $respuesta, string $accion = 'ver'): void {
        if (!tienePermiso('laboratorio', $accion)) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
    }

    /**
     * Dashboard del laboratorio: Solicitudes Entrantes (ISO 17025) y Laboratorio Operativo (Rupturas y Matrices).
     */
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $modelo = new OperacionModelo();

        // 1. Obtener todas las Órdenes de Servicio para el Tablero Kanban LIMS (Muestra Ciega ISO 17025)
        $sqlAll = "SELECT os.id AS id_os, os.codigo_os, os.fecha_emision, os.estado AS estado_os,
                          os.requiere_muestreo, os.tipo_contrato, os.atencion_a, os.nombre_proyecto, os.motivo_observacion,
                          hs.id AS id_hoja, hs.codigo_documento AS hoja_codigo,
                          hs.nombre_persona_toma_muestra, hs.fecha_hora_toma_muestra,
                          hs.muestras_json, hs.naturaleza_muestra, hs.procedencia_punto_muestreo,
                          hs.observaciones, hs.fecha_creacion AS hoja_fecha_creacion,
                          cot.id AS id_cotizacion, cot.codigo AS cotizacion_codigo,
                          (SELECT COUNT(*) FROM recepcion_muestras rm WHERE rm.id_os = os.id) AS total_recibidas_lab
                   FROM ordenes_servicio os
                   LEFT JOIN hojas_solicitud hs ON hs.id_os = os.id
                   LEFT JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                   ORDER BY os.id DESC";
        $stmtAll = $db->query($sqlAll);
        $todasOrdenes = $stmtAll->fetchAll(PDO::FETCH_ASSOC);

        $kanban = [
            'recien_llegadas' => [],
            'en_revision' => [],
            'aceptadas_lab' => [],
            'finalizadas' => []
        ];

        foreach ($todasOrdenes as &$o) {
            $o['ensayos'] = $modelo->obtenerDetallesCotizacion((int)$o['id_cotizacion']);
            
            // Si la orden contiene exclusivamente ensayos in situ de compactación, no requiere recepción física de muestras en lab
            if (!empty($o['ensayos']) && esOrdenSoloCompactacion($o['ensayos'])) {
                continue;
            }

            $o['muestras_declaradas'] = !empty($o['muestras_json']) ? (json_decode($o['muestras_json'], true) ?: []) : [];
            $o['total_muestras_declaradas'] = count($o['muestras_declaradas']);

            // Muestras ingresadas formalmente en lab
            $stmtCodigos = $db->prepare("SELECT rm.codigo_muestra, rm.codigo_campo, rm.fecha_recepcion, lm.id AS id_lote FROM recepcion_muestras rm LEFT JOIN lotes_muestras lm ON lm.id_recepcion = rm.id WHERE rm.id_os = :id_os");
            $stmtCodigos->execute(['id_os' => $o['id_os']]);
            $o['muestras_ingresadas'] = $stmtCodigos->fetchAll(PDO::FETCH_ASSOC);

            $estado = $o['estado_os'];
            $recibidas = (int)$o['total_recibidas_lab'];

            if ($estado === 'Estado 2: Revision') {
                $kanban['en_revision'][] = $o;
            } elseif ($estado === 'Finalizado' || $estado === 'Estado 7: Revision Resultados') {
                $kanban['finalizadas'][] = $o;
            } elseif ($recibidas > 0 || in_array($estado, ['Estado 4: Ingreso Laboratorio', 'Estado 5: Solicitud Tecnicos'])) {
                $kanban['aceptadas_lab'][] = $o;
            } else {
                $kanban['recien_llegadas'][] = $o;
            }
        }
        unset($o);

        // 2. Obtener listado de muestras ingresadas activas en custodia
        $sqlMuestras = "SELECT lm.id AS id_lote, rm.id AS id_recepcion, rm.codigo_muestra, rm.codigo_campo, lm.nombre_lote,
                               lm.fecha_moldeo, rm.fecha_recepcion, rm.estado, os.codigo_os, os.id AS id_os,
                               (SELECT descripcion_ensayo FROM cotizacion_detalles cd WHERE cd.id = (SELECT id_detalle_cotizacion FROM ensayo_edades ee WHERE ee.id_lote = lm.id LIMIT 1)) AS nombre_ensayo
                        FROM lotes_muestras lm
                        JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                        JOIN ordenes_servicio os ON rm.id_os = os.id
                        ORDER BY lm.id DESC LIMIT 100";
        $stmtMuestras = $db->query($sqlMuestras);
        $muestras = $stmtMuestras->fetchAll(PDO::FETCH_ASSOC);

        // 3. Obtener rupturas programadas para los próximos 7 días (ciego)
        $sqlProximas = "SELECT ee.id, ee.identificador_especimen, ee.edad_dias, ee.fecha_programada,
                               rm.codigo_muestra, rm.codigo_campo, lm.id AS id_lote,
                               cd.descripcion_ensayo AS nombre_ensayo
                        FROM ensayo_edades ee
                        JOIN lotes_muestras lm ON ee.id_lote = lm.id
                        JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                        LEFT JOIN cotizacion_detalles cd ON ee.id_detalle_cotizacion = cd.id
                        WHERE ee.estado IN ('Programado', 'Listo para Ensaye')
                          AND ee.edad_dias > 0
                          AND ee.fecha_programada BETWEEN CURRENT_DATE - INTERVAL 2 DAY AND CURRENT_DATE + INTERVAL 7 DAY
                        ORDER BY ee.fecha_programada ASC, rm.codigo_muestra ASC";
        $stmtProx = $db->query($sqlProximas);
        $rupturasProgramadas = $stmtProx->fetchAll(PDO::FETCH_ASSOC);

        // 4. Obtener rupturas para el calendario
        $sqlCalendario = "SELECT ee.id, ee.identificador_especimen, ee.edad_dias, ee.fecha_programada,
                                 ee.estado, rm.codigo_muestra, rm.codigo_campo, lm.id AS id_lote,
                                 cd.descripcion_ensayo AS nombre_ensayo,
                                 os.codigo_os
                          FROM ensayo_edades ee
                          JOIN lotes_muestras lm ON ee.id_lote = lm.id
                          JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                          JOIN ordenes_servicio os ON rm.id_os = os.id
                          LEFT JOIN cotizacion_detalles cd ON ee.id_detalle_cotizacion = cd.id
                          WHERE ee.fecha_programada BETWEEN CURRENT_DATE - INTERVAL 60 DAY AND CURRENT_DATE + INTERVAL 60 DAY
                          ORDER BY ee.fecha_programada ASC";
        $stmtCal = $db->query($sqlCalendario);
        $eventosCalendario = $stmtCal->fetchAll(PDO::FETCH_ASSOC);

        foreach ($eventosCalendario as &$ev) {
            if (isset($ev['id_lote'])) {
                $ev['id_lote'] = codificarId($ev['id_lote']);
            }
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('operaciones/vistas/laboratorio_dashboard', [
            'titulo' => 'Portal de Laboratorio LIMS (ISO 17025)',
            'kanban' => $kanban,
            'muestras' => $muestras,
            'rupturas' => $rupturasProgramadas,
            'eventosCalendario' => $eventosCalendario,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Detalle ciego de una muestra en laboratorio para cargar datos.
     */
    public function detalleMuestra(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $idLote = (int)($_GET['id_lote'] ?? 0);
        if ($idLote <= 0) {
            $_SESSION['error'] = 'Muestra inválida.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

        // 1. Obtener datos del lote y recepción (estrictamente técnicos)
        $sqlLote = "SELECT lm.*, rm.codigo_muestra, rm.codigo_campo, rm.fecha_recepcion
                    FROM lotes_muestras lm
                    JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                    WHERE lm.id = :id_lote";
        $stmt = $db->prepare($sqlLote);
        $stmt->execute(['id_lote' => $idLote]);
        $lote = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lote) {
            $_SESSION['error'] = 'Muestra no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        // 2. Obtener especímenes del lote
        $modelo = new OperacionModelo();
        $especimenes = $modelo->obtenerDetallesLote($idLote);
        $historial = $modelo->obtenerHistorialInformes($idLote);

        // Obtener el id_detalle_cotizacion asociado a este lote desde ensayo_edades
        $stmtDetalleLote = $db->prepare("SELECT DISTINCT id_detalle_cotizacion FROM ensayo_edades WHERE id_lote = :id_lote LIMIT 1");
        $stmtDetalleLote->execute(['id_lote' => $idLote]);
        $idDetalleCotizacionAsociado = $stmtDetalleLote->fetchColumn();

        // 3. Obtener únicamente el ensayo cotizado relacionado y asociado a este lote (ciego)
        $sqlItems = "SELECT cd.id, cd.descripcion_ensayo, cd.norma_astm, fe.archivo_markdown, 
                            fe.nombre AS formato_nombre, cd.resultados_json, cd.id_cotizacion
                     FROM cotizacion_detalles cd
                     JOIN lotes_muestras lm ON lm.id = :id_lote
                     JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                     JOIN ordenes_servicio os ON rm.id_os = os.id
                     LEFT JOIN productos p ON cd.id_producto = p.id
                     LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                     WHERE cd.id_cotizacion = os.id_cotizacion AND cd.id = :id_det_cot";
        $stmtItems = $db->prepare($sqlItems);
        $stmtItems->execute([
            'id_lote' => $idLote,
            'id_det_cot' => $idDetalleCotizacionAsociado
        ]);
        $itemsOS = $stmtItems->fetchAll(PDO::FETCH_ASSOC);

        // Cargar esquemas JSON
        $schemaPath = dirname(__DIR__, 4) . '/database/ensayos/formatos_schema.json';
        $formatosSchemaJson = file_exists($schemaPath) ? file_get_contents($schemaPath) : '{}';

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('operaciones/vistas/laboratorio_detalle', [
            'titulo' => 'Hoja de Trabajo Ciega - Muestra ' . $lote['codigo_muestra'],
            'lote' => $lote,
            'especimenes' => $especimenes,
            'historial' => $historial,
            'itemsOS' => $itemsOS,
            'formatosSchemaJson' => $formatosSchemaJson,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Guarda la carga de rotura de un cilindro.
     */
    public function guardarRuptura(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
                return;
            }

            $idEnsayo = (int)($datos['id_ensayo'] ?? 0);
            $idLote = (int)($datos['id_lote'] ?? 0);
            $carga = (float)($datos['carga_lbs'] ?? 0);
            $area = (float)($datos['area_in2'] ?? 28.274);

            if ($idEnsayo <= 0) {
                $_SESSION['error'] = 'Identificador de especímen inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio');
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
     * Procesa la aceptación técnica y recepción de muestras en el laboratorio (conforme a ISO 17025).
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

            // 🔒 Validar que no existan ensayos ya realizados o validados antes de re-procesar
            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $stmtCheck = $db->prepare("SELECT COUNT(*) FROM ensayo_edades ee 
                                       JOIN lotes_muestras lm ON ee.id_lote = lm.id 
                                       JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id 
                                       WHERE rm.id_os = :id_os AND (ee.ensayado = 1 OR ee.resistencia_calculada_psi > 0)");
            $stmtCheck->execute(['id_os' => $idOS]);
            if ((int)$stmtCheck->fetchColumn() > 0) {
                $_SESSION['error'] = 'No se pueden re-procesar las muestras: esta Orden de Servicio ya cuenta con ensayos ejecutados o validados.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=solicitudes');
                return;
            }

            // Obtener estrictamente las muestras declaradas en la Hoja RT-FM-13
            $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS);
            $muestrasDeclaradasRT = [];
            if ($hoja && !empty($hoja['muestras_json'])) {
                $muestrasDeclaradasRT = json_decode($hoja['muestras_json'], true) ?: [];
            }

            $servicios = $modelo->obtenerDetallesCotizacion((int)$os['id_cotizacion']);
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
                        'revenimiento_in' => '',
                        'revenimiento_cm' => '',
                        'temperatura_c' => '',
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

            $payloadRecepcion = [
                'id_os' => $idOS,
                'limpiar_previas' => true,
                'id_detalle_cotizacion' => $idDetalleCotizacion,
                'fecha_recepcion' => !empty($datos['fecha_recepcion']) ? $datos['fecha_recepcion'] : date('Y-m-d H:i:s'),
                'recibido_por' => trim($datos['recibido_por'] ?? ($_SESSION['usuario_nombre'] ?? 'Laboratorista')),
                'entregado_por' => trim($datos['entregado_por'] ?? ($os['cliente_nombre'] ?? 'Cliente')),
                'observaciones' => trim($datos['observaciones'] ?? 'Muestras aceptadas bajo estricta conformidad técnica con la Hoja CYCSA-RT-FM-13 (ISO 17025)'),
                'muestras_recibidas_json' => json_encode($muestrasPayload)
            ];

            if ($modelo->registrarRecepcion($payloadRecepcion)) {
                $_SESSION['exito'] = 'Muestras aceptadas e ingresadas formalmente al Laboratorio. Se asignaron los códigos técnicos (MS-XXXX-26).';
            } else {
                $_SESSION['error'] = 'Ocurrió un error al registrar el ingreso de muestras al laboratorio.';
            }

            $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=solicitudes');
        }
    }

    /**
     * Imprime la Hoja de Solicitud de Laboratorio de Materiales y Suelo conforme a ISO 17025.
     */
    public function imprimirHojaSolicitud(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $idOS = (int)($_GET['id_os'] ?? 0);
        if ($idOS <= 0) {
            $_SESSION['error'] = 'Orden de Servicio inválida.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $modelo = new OperacionModelo();

        $os = $modelo->obtenerOSPorId($idOS);
        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS);
        $ensayos = $modelo->obtenerDetallesCotizacion((int)$os['id_cotizacion']);

        // Obtener la recepción y las muestras registradas en lab
        $stmtRec = $db->prepare("SELECT * FROM recepcion_muestras WHERE id_os = :id_os ORDER BY id DESC LIMIT 1");
        $stmtRec->execute(['id_os' => $idOS]);
        $recepcion = $stmtRec->fetch(PDO::FETCH_ASSOC);

        $stmtMuestras = $db->prepare("SELECT rm.codigo_muestra, rm.codigo_campo, rm.fecha_recepcion, lm.fecha_moldeo AS fecha_elaboracion,
                                             (SELECT descripcion_ensayo FROM cotizacion_detalles cd WHERE cd.id = (SELECT id_detalle_cotizacion FROM ensayo_edades ee WHERE ee.id_lote = lm.id LIMIT 1)) AS nombre_ensayo
                                      FROM recepcion_muestras rm
                                      LEFT JOIN lotes_muestras lm ON lm.id_recepcion = rm.id
                                      WHERE rm.id_os = :id_os
                                      ORDER BY rm.id ASC");
        $stmtMuestras->execute(['id_os' => $idOS]);
        $muestrasLote = $stmtMuestras->fetchAll(PDO::FETCH_ASSOC);

        // Si aún no se ha recepcionado formalmente, construimos la lista tentativa desde la Hoja RT-FM-13
        if (empty($muestrasLote) && $hoja && !empty($hoja['muestras_json'])) {
            $declaradas = json_decode($hoja['muestras_json'], true) ?: [];
            $anioShort = date('y');
            $siguienteCorr = $modelo->obtenerSiguienteConsecutivoMuestra((int)date('Y'));

            foreach ($declaradas as $idx => $dec) {
                $muestrasLote[] = [
                    'codigo_muestra' => sprintf("MS-%04d-%02d", $siguienteCorr + $idx, $anioShort),
                    'codigo_campo' => $dec['nombre_muestra'] ?? ('MC-' . ($idx + 1)),
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
            'muestrasLote' => $muestrasLote
        ]);
    }

    /**
     * Permite avanzar o cambiar el estado de una Orden de Servicio en el Tablero Kanban (Enviar a Revisión, Observar, etc.)
     */
    public function cambiarEstadoKanban(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idOS = (int)($datos['id_os'] ?? 0);
            $nuevoEstado = trim($datos['nuevo_estado'] ?? '');
            $motivo = trim($datos['motivo_observacion'] ?? '');
            $esAjax = !empty($datos['ajax']) || (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

            if ($idOS <= 0 || empty($nuevoEstado)) {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'Datos insuficientes.']);
                    return;
                }
                $_SESSION['error'] = 'Datos insuficientes para cambiar estado.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=kanban');
                return;
            }

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido.']);
                    return;
                }
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=kanban');
                return;
            }

            // 🔒 Validar estado contra la lista permitida de transiciones Kanban
            $estadosPermitidos = [
                'Estado 1: Recepcion',
                'Estado 2: Revision',
                'Estado 2: Observada',
                'Estado 4: Ingreso Laboratorio',
                'Estado 5: Solicitud Tecnicos',
                'Estado 7: Revision Resultados',
                'Finalizado'
            ];

            if (!in_array($nuevoEstado, $estadosPermitidos, true)) {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'Estado de destino no válido.']);
                    return;
                }
                $_SESSION['error'] = 'Estado de destino no válido.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=kanban');
                return;
            }

            // Si el estado es "Observada", exigir motivo
            if ($nuevoEstado === 'Estado 2: Observada' && empty($motivo)) {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'Debe especificar el motivo de la observación.']);
                    return;
                }
                $_SESSION['error'] = 'Debe especificar el motivo de la observación.';
                $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=kanban');
                return;
            }

            // Para finalizar ordenes o emitir resultados, verificar privilegios de supervisión/dirección
            if (in_array($nuevoEstado, ['Finalizado', 'Estado 7: Revision Resultados'], true)) {
                $rol = $_SESSION['rol'] ?? '';
                if (!in_array($rol, ['Super Administrador', 'Administrador', 'Jefe de Laboratorio', 'Supervisor', 'Director Técnico'], true)) {
                    if ($esAjax) {
                        $respuesta->enviarJson(['status' => 'error', 'message' => 'No cuenta con autorización de supervisión técnica para emitir resultados o finalizar esta orden.']);
                        return;
                    }
                    $_SESSION['error'] = 'No cuenta con autorización de supervisión técnica para emitir resultados o finalizar esta orden.';
                    $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=kanban');
                    return;
                }
            }

            $modelo = new OperacionModelo();
            $ok = $modelo->actualizarEstadoOS($idOS, $nuevoEstado, !empty($motivo) ? $motivo : null);

            if ($ok) {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'success', 'message' => 'Estado de la solicitud actualizado a: ' . $nuevoEstado]);
                    return;
                }
                $_SESSION['exito'] = 'Estado de la solicitud actualizado correctamente a: ' . $nuevoEstado;
            } else {
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => 'No se pudo actualizar el estado de la Orden.']);
                    return;
                }
                $_SESSION['error'] = 'No se pudo actualizar el estado de la Orden.';
            }

            $respuesta->redirigir('/Cycsa/publico/laboratorio?tab=kanban');
        }
    }
}
