<?php

namespace Cycsa\Modulos\HojasServicio\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Nucleo\Conexion;
use PDO;

class HojasServicioControlador extends ControladorBase {

    private function verificarSesion(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
    }

    private function verificarPermiso(Respuesta $respuesta, string $accion): void {
        if (!tienePermiso('operaciones', $accion)) {
            $_SESSION['error'] = 'No tiene permisos para realizar esta acción en Hojas de Servicio.';
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
    }

    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $busqueda = trim($_GET['buscar'] ?? '');
        $db = Conexion::obtenerInstancia();

        // 1. QUERY NUEVO (O/S sin Hoja de Solicitud registrada o en estado inicial)
        $sqlNuevo = "SELECT os.id, os.codigo_os, os.fecha_emision, os.estado, os.requiere_muestreo,
                            cot.codigo AS cot_codigo, cot.nombre_proyecto,
                            cli.nombre_razon_social AS cliente_nombre,
                            hs.id AS id_hoja, hs.codigo_documento,
                            pm.id AS id_pm, pm.estado_muestreo, pm.fecha_ida, pm.fecha_llegada,
                            tec.nombre AS tecnico_muestreo_nombre
                     FROM ordenes_servicio os
                     JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                     JOIN clientes cli ON cot.id_cliente = cli.id
                     LEFT JOIN hojas_solicitud hs ON hs.id_os = os.id
                     LEFT JOIN programacion_muestreo pm ON pm.id_orden_servicio = os.id
                     LEFT JOIN tecnicos tec ON pm.id_tecnico = tec.id
                     WHERE (os.estado IN ('Estado 1: Recepcion', 'Pendiente de Muestreo') OR hs.id IS NULL)
                       AND os.estado NOT IN ('Estado 2: Revision', 'Estado 2: Observada')";
        if ($busqueda !== '') {
            $sqlNuevo .= " AND (os.codigo_os LIKE :q1 OR cot.nombre_proyecto LIKE :q2 OR cli.nombre_razon_social LIKE :q3)";
        }
        $sqlNuevo .= " ORDER BY os.id DESC";
        $stmtNuevo = $db->prepare($sqlNuevo);
        if ($busqueda !== '') {
            $term = '%' . $busqueda . '%';
            $stmtNuevo->execute(['q1' => $term, 'q2' => $term, 'q3' => $term]);
        } else {
            $stmtNuevo->execute();
        }
        $nuevas = $stmtNuevo->fetchAll(PDO::FETCH_ASSOC);

        // 2. QUERY EN PROCESO (O/S enviadas a revisión o que fueron observadas)
        $sqlProceso = "SELECT os.id, os.codigo_os, os.fecha_emision, os.estado, os.motivo_observacion,
                              cot.codigo AS cot_codigo, cot.nombre_proyecto,
                              cli.nombre_razon_social AS cliente_nombre,
                              hs.codigo_documento, hs.id AS id_hoja
                       FROM ordenes_servicio os
                       JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                       JOIN clientes cli ON cot.id_cliente = cli.id
                       JOIN hojas_solicitud hs ON hs.id_os = os.id
                       WHERE os.estado IN ('Estado 2: Revision', 'Estado 2: Observada')";
        if ($busqueda !== '') {
            $sqlProceso .= " AND (os.codigo_os LIKE :q1 OR cot.nombre_proyecto LIKE :q2 OR cli.nombre_razon_social LIKE :q3)";
        }
        $sqlProceso .= " ORDER BY os.id DESC";
        $stmtProceso = $db->prepare($sqlProceso);
        if ($busqueda !== '') {
            $stmtProceso->execute(['q1' => $term, 'q2' => $term, 'q3' => $term]);
        } else {
            $stmtProceso->execute();
        }
        $proceso = $stmtProceso->fetchAll(PDO::FETCH_ASSOC);

        // 3. QUERY APROBADO (O/S con Hoja de Solicitud completada y aprobada)
        $sqlAprobado = "SELECT os.id, os.codigo_os, os.fecha_emision, os.estado,
                               cot.codigo AS cot_codigo, cot.nombre_proyecto,
                               cli.nombre_razon_social AS cliente_nombre,
                               hs.codigo_documento, hs.id AS id_hoja
                        FROM ordenes_servicio os
                        JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                        JOIN clientes cli ON cot.id_cliente = cli.id
                        JOIN hojas_solicitud hs ON hs.id_os = os.id
                        WHERE os.estado NOT IN ('Estado 1: Recepcion', 'Estado 2: Revision', 'Estado 2: Observada')";
        if ($busqueda !== '') {
            $sqlAprobado .= " AND (os.codigo_os LIKE :q1 OR cot.nombre_proyecto LIKE :q2 OR cli.nombre_razon_social LIKE :q3)";
        }
        $sqlAprobado .= " ORDER BY os.id DESC";
        $stmtAprobado = $db->prepare($sqlAprobado);
        if ($busqueda !== '') {
            $stmtAprobado->execute(['q1' => $term, 'q2' => $term, 'q3' => $term]);
        } else {
            $stmtAprobado->execute();
        }
        $aprobadas = $stmtAprobado->fetchAll(PDO::FETCH_ASSOC);

        // Cargar técnicos para autocompletar en el formulario
        $modelo = new OperacionModelo();
        $tecnicos = $modelo->obtenerTecnicosActivos();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('HojasServicio/Vistas/index', [
            'titulo' => 'Módulo Hojas de Servicio - CYCSA',
            'nuevas' => $nuevas,
            'proceso' => $proceso,
            'aprobadas' => $aprobadas,
            'tecnicos' => $tecnicos,
            'busqueda' => $busqueda,
            'id_os_auto' => (int)($_GET['id_os'] ?? 0)
        ]);
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

        $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS);
        
        $osModelo = new \Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo();
        $osCompleta = $osModelo->obtenerPorId($idOS);

        $tecnicoMuestreo = $os['tecnico_muestreo'] ?? '';
        $fechaToma = !empty($os['fecha_muestreo']) ? $os['fecha_muestreo'] . ' ' . ($os['hora_muestreo'] ?: '08:00:00') : '';

        if (!empty($osCompleta['programacion_muestreo'])) {
            if (empty($tecnicoMuestreo) && !empty($osCompleta['programacion_muestreo']['tecnico_nombre'])) {
                $tecnicoMuestreo = $osCompleta['programacion_muestreo']['tecnico_nombre'];
            }
            if (empty($fechaToma) && !empty($osCompleta['programacion_muestreo']['fecha_ida'])) {
                $fechaToma = $osCompleta['programacion_muestreo']['fecha_ida'];
            }
        }

        if (!$hoja) {
            $numCorrelativo = $modelo->obtenerSiguienteNumeroHojaSolicitud((int)$os['id_cotizacion']);
            $codigoDoc = "CYCSA-RT-FM-" . sprintf("%02d", $numCorrelativo);
            
            $hoja = [
                'id_os' => $idOS,
                'codigo_documento' => $codigoDoc,
                'nombre_empresa_o_cliente' => $os['cliente_nombre'] ?? '',
                'razon_social' => '',
                'direccion_proyecto' => $os['direccion_proyecto'] ?? '',
                'telefono' => $os['cliente_telefono'] ?? '',
                'correo_electronico' => $os['cliente_email'] ?? '',
                'nombre_persona_entrega_muestra' => !empty($os['atencion_a']) ? $os['atencion_a'] : ($os['cliente_nombre'] ?? ''),
                'naturaleza_muestra' => 'Concreto',
                'procedencia_punto_muestreo' => '',
                'nombre_persona_toma_muestra' => !empty($tecnicoMuestreo) ? $tecnicoMuestreo : 'Cliente / Entregada por Cliente',
                'fecha_hora_toma_muestra' => !empty($fechaToma) ? $fechaToma : date('Y-m-d H:i'),
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
                'nombre_recibe_cycsa' => $_SESSION['usuario_nombre'] ?? '',
                'firma_recibe_cycsa' => 0,
                'firma_cliente' => 0,
                'fecha_hora_llegada_laboratorio' => date('Y-m-d H:i')
            ];
        } else {
            if (empty($hoja['nombre_empresa_o_cliente'])) $hoja['nombre_empresa_o_cliente'] = $os['cliente_nombre'];
            if (empty($hoja['direccion_proyecto'])) $hoja['direccion_proyecto'] = $os['direccion_proyecto'];
            if (empty($hoja['telefono'])) $hoja['telefono'] = $os['cliente_telefono'];
            if (empty($hoja['correo_electronico']) && !empty($os['cliente_email'])) $hoja['correo_electronico'] = $os['cliente_email'];
            if (empty($hoja['nombre_persona_entrega_muestra'])) $hoja['nombre_persona_entrega_muestra'] = !empty($os['atencion_a']) ? $os['atencion_a'] : $os['cliente_nombre'];
            if (empty($hoja['nombre_persona_toma_muestra']) && !empty($tecnicoMuestreo)) $hoja['nombre_persona_toma_muestra'] = $tecnicoMuestreo;
        }

        $respuesta->enviarJson([
            'status' => 'success',
            'hoja' => $hoja,
            'os' => [
                'id' => $os['id'],
                'codigo_os' => $os['codigo_os']
            ],
            'os_referencia' => $osCompleta
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
                $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
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

            if ($modelo->guardarHojaSolicitud($datos)) {
                // Generar PDF y guardarlo en almacenamiento/solicitudes/
                $os = $modelo->obtenerOSPorId($idOS);
                $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS);
                
                require_once dirname(__DIR__, 4) . '/app/Helpers/funciones.php';
                $pdfContenido = generarHojaSolicitudPDF($hoja, $os);
                
                $dirPdf = dirname(__DIR__, 4) . '/almacenamiento/solicitudes';
                if (!file_exists($dirPdf)) {
                    mkdir($dirPdf, 0777, true);
                }
                $nombrePdf = "CYCSA-RT-FM-13-" . $os['codigo_os'] . ".pdf";
                file_put_contents($dirPdf . '/' . $nombrePdf, $pdfContenido);

                $codigoTexto = $os ? ($os['codigo_os'] . (!empty($os['cliente_nombre']) ? ' (' . $os['cliente_nombre'] . ')' : '')) : ('ID ' . $idOS);
                registrarBitacora('hojas_servicio', 'hoja_solicitud', 'Hoja de Solicitud CYCSA-RT-FM-13 guardada y PDF generado para Orden de Servicio ' . $codigoTexto, $idOS);
                
                // Si la O/S estaba "Observada", al guardar cambios la devolvemos a "Estado 1: Recepcion" para que puedan enviarla a revisión
                if ($os['estado'] === 'Estado 2: Observada') {
                    $modelo->actualizarEstadoOS($idOS, 'Estado 1: Recepcion');
                }

                $_SESSION['exito'] = 'Hoja de Solicitud de Servicio CYCSA-RT-FM-13 guardada exitosamente y PDF generado.';
            } else {
                $_SESSION['error'] = 'Error al registrar la Hoja de Solicitud.';
            }

            $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
        }
    }

    public function enviarRevision(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idOS = (int)($datos['id_os'] ?? 0);

            if ($idOS <= 0) {
                $_SESSION['error'] = 'Orden de Servicio inválida.';
                $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
                return;
            }

            $modelo = new OperacionModelo();
            $os = $modelo->obtenerOSPorId($idOS);
            if (!$os) {
                $_SESSION['error'] = 'Orden de Servicio no encontrada.';
                $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
                return;
            }

            if ($modelo->actualizarEstadoOS($idOS, 'Estado 2: Revision')) {
                $codigoTexto = $os['codigo_os'] . (!empty($os['cliente_nombre']) ? ' (' . $os['cliente_nombre'] . ')' : '');
                registrarBitacora('hojas_servicio', 'cambiar_estado', 'Orden de Servicio ' . $codigoTexto . ' enviada a revisión de supervisor.', $idOS);
                $_SESSION['exito'] = 'Hoja de Servicio enviada a revisión del supervisor correctamente.';
            } else {
                $_SESSION['error'] = 'Error al enviar la Hoja de Servicio a revisión.';
            }

            $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
        }
    }

    public function procesarRevision(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        // Solo un Supervisor (Rol 3) o Administrador (Rol 1) puede procesar la revisión
        $rol = (int)($_SESSION['usuario_rol'] ?? 0);
        if ($rol !== 1 && $rol !== 3) {
            $_SESSION['error'] = 'No tiene permisos de supervisor para cambiar el estado de la Orden de Servicio.';
            $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
            return;
        }

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idOS = (int)($datos['id_os'] ?? 0);
            $nuevoEstado = trim($datos['estado'] ?? '');
            $motivo = trim($datos['motivo_observacion'] ?? '');
            $reqMuestreo = isset($datos['requiere_muestreo']) ? (int)$datos['requiere_muestreo'] : 0;

            if ($idOS <= 0 || !in_array($nuevoEstado, ['Estado 3: Ingreso Directo', 'Estado 3A: Programacion Muestreo', 'Estado 2: Observada'])) {
                $_SESSION['error'] = 'Datos de revisión inválidos.';
                $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
                return;
            }

            $modelo = new OperacionModelo();
            $os = $modelo->obtenerOSPorId($idOS);
            if (!$os) {
                $_SESSION['error'] = 'Orden de Servicio no encontrada.';
                $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
                return;
            }

            $db = Conexion::obtenerInstancia();
            $exito = false;

            if ($nuevoEstado === 'Estado 2: Observada') {
                // Registrar observación
                $stmt = $db->prepare("UPDATE ordenes_servicio SET estado = 'Estado 2: Observada', motivo_observacion = :motivo WHERE id = :id");
                $exito = $stmt->execute(['motivo' => $motivo, 'id' => $idOS]);
                if ($exito) {
                    $codigoTexto = $os['codigo_os'] . (!empty($os['cliente_nombre']) ? ' (' . $os['cliente_nombre'] . ')' : '');
                    registrarBitacora('hojas_servicio', 'cambiar_estado', 'Orden de Servicio ' . $codigoTexto . ' observada por supervisor: ' . $motivo, $idOS);
                    $_SESSION['exito'] = 'La Hoja de Servicio ha sido observada y devuelta al emisor.';
                }
            } else {
                // Registrar aprobación (y actualizar requiere_muestreo)
                $stmt = $db->prepare("UPDATE ordenes_servicio SET estado = :estado, requiere_muestreo = :req, motivo_observacion = NULL WHERE id = :id");
                $exito = $stmt->execute(['estado' => $nuevoEstado, 'req' => $reqMuestreo, 'id' => $idOS]);
                if ($exito) {
                    $codigoTexto = $os['codigo_os'] . (!empty($os['cliente_nombre']) ? ' (' . $os['cliente_nombre'] . ')' : '');
                    registrarBitacora('hojas_servicio', 'cambiar_estado', 'Orden de Servicio ' . $codigoTexto . ' aprobada y pasada a estado: ' . $nuevoEstado, $idOS);
                    $_SESSION['exito'] = 'La Hoja de Servicio ha sido aprobada correctamente.';
                }
            }

            if (!$exito) {
                $_SESSION['error'] = 'Error al procesar la revisión de la Hoja de Servicio.';
            }

            $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
        }
    }

    public function descargarSolicitudPDF(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        $idOS = (int)($_GET['id_os'] ?? 0);
        if ($idOS <= 0) {
            $_SESSION['error'] = 'Orden de Servicio inválida.';
            $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
            return;
        }

        $modelo = new OperacionModelo();
        $os = $modelo->obtenerOSPorId($idOS);
        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
            return;
        }

        $nombrePdf = "CYCSA-RT-FM-13-" . $os['codigo_os'] . ".pdf";
        $rutaPdf = dirname(__DIR__, 4) . '/almacenamiento/solicitudes/' . $nombrePdf;

        if (file_exists($rutaPdf)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($rutaPdf) . '"');
            readfile($rutaPdf);
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
            $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
        }
    }
}
