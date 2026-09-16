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

        $idOS = (int)($_GET['id_os'] ?? ($_GET['id'] ?? 0));
        if ($idOS > 0) {
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . $idOS);
            return;
        }

        $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
        return;
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

        $lugarMuestreo = $osCompleta['programacion_muestreo']['lugar_muestreo'] ?? '';
        $cantEstimadaProg = (int)($osCompleta['programacion_muestreo']['cantidad_muestras_est'] ?? 0);

        $detectado = $this->detectarParametrosYNaturaleza($osCompleta['ensayos'] ?? []);

        $nombreCliente = !empty($osCompleta['cliente_nombre']) ? $osCompleta['cliente_nombre'] : ($os['cliente_nombre'] ?? '');
        $direccionProyecto = !empty($osCompleta['direccion_proyecto']) ? $osCompleta['direccion_proyecto'] : (!empty($osCompleta['cliente_direccion']) ? $osCompleta['cliente_direccion'] : ($os['direccion_proyecto'] ?? ''));
        $telefonoCliente = !empty($osCompleta['cliente_telefono']) ? $osCompleta['cliente_telefono'] : ($os['cliente_telefono'] ?? '');
        $emailCliente = !empty($osCompleta['cliente_email']) ? $osCompleta['cliente_email'] : ($os['cliente_email'] ?? '');
        $atencionA = !empty($osCompleta['atencion_a']) ? $osCompleta['atencion_a'] : (!empty($os['atencion_a']) ? $os['atencion_a'] : $nombreCliente);
        
        $procedenciaPunto = !empty($lugarMuestreo) ? $lugarMuestreo : (!empty($direccionProyecto) ? $direccionProyecto : ($osCompleta['nombre_proyecto'] ?? $os['nombre_proyecto'] ?? ''));

        $fechaLlegadaLab = date('Y-m-d H:i');
        if (!empty($osCompleta['programacion_muestreo']['fecha_finalizacion'])) {
            $fechaLlegadaLab = date('Y-m-d H:i', strtotime($osCompleta['programacion_muestreo']['fecha_finalizacion']));
        } elseif (!empty($osCompleta['programacion_muestreo']['fecha_llegada'])) {
            $fechaLlegadaLab = date('Y-m-d H:i', strtotime($osCompleta['programacion_muestreo']['fecha_llegada']));
        }

        if (!$hoja) {
            $hoja = array_merge([
                'id_os' => $idOS,
                'codigo_documento' => 'CYCSA-RT-FM-13',
                'numero_registro' => sprintf("%05d", $idOS),
                'nombre_empresa_o_cliente' => $nombreCliente,
                'razon_social' => $nombreCliente,
                'direccion_proyecto' => $direccionProyecto,
                'telefono' => $telefonoCliente,
                'correo_electronico' => $emailCliente,
                'nombre_persona_entrega_muestra' => $atencionA,
                'naturaleza_muestra' => $detectado['naturaleza_muestra_str'],
                'procedencia_punto_muestreo' => $procedenciaPunto,
                'nombre_persona_toma_muestra' => !empty($tecnicoMuestreo) ? $tecnicoMuestreo : (($os['requiere_muestreo'] === 0 || $os['requiere_muestreo'] === '0') ? 'Cliente / Entregada por Cliente' : ''),
                'fecha_hora_toma_muestra' => !empty($fechaToma) ? date('Y-m-d H:i', strtotime($fechaToma)) : date('Y-m-d H:i'),
                'muestras_json' => '[]',
                'analisis_adicionales' => '',
                'observaciones' => '',
                'nombre_recibe_cycsa' => $_SESSION['usuario_nombre'] ?? '',
                'firma_recibe_cycsa' => 0,
                'firma_cliente' => 0,
                'fecha_hora_llegada_laboratorio' => $fechaLlegadaLab
            ], $detectado['flags']);
        } else {
            if (empty($hoja['codigo_documento'])) $hoja['codigo_documento'] = 'CYCSA-RT-FM-13';
            if (empty($hoja['numero_registro'])) $hoja['numero_registro'] = sprintf("%05d", $idOS);
            if (empty($hoja['nombre_empresa_o_cliente'])) $hoja['nombre_empresa_o_cliente'] = $nombreCliente;
            if (empty($hoja['razon_social'])) $hoja['razon_social'] = $nombreCliente;
            if (empty($hoja['direccion_proyecto'])) $hoja['direccion_proyecto'] = $direccionProyecto;
            if (empty($hoja['telefono'])) $hoja['telefono'] = $telefonoCliente;
            if (empty($hoja['correo_electronico']) && !empty($emailCliente)) $hoja['correo_electronico'] = $emailCliente;
            if (empty($hoja['nombre_persona_entrega_muestra'])) $hoja['nombre_persona_entrega_muestra'] = $atencionA;
            if (empty($hoja['nombre_persona_toma_muestra']) && !empty($tecnicoMuestreo)) $hoja['nombre_persona_toma_muestra'] = $tecnicoMuestreo;
            if (empty($hoja['procedencia_punto_muestreo'])) $hoja['procedencia_punto_muestreo'] = $procedenciaPunto;
            if (empty($hoja['naturaleza_muestra'])) $hoja['naturaleza_muestra'] = $detectado['naturaleza_muestra_str'];
        }

        $esCampo = !empty($osCompleta['programacion_muestreo']) || ($osCompleta['requiere_muestreo'] ?? $os['requiere_muestreo']) === 1 || ($osCompleta['requiere_muestreo'] ?? $os['requiere_muestreo']) === '1';
        $prefijoMuestra = $esCampo ? 'MC' : 'MS';
        $tipoOrigen = $esCampo ? 'campo' : 'laboratorio';
        $cantSugerida = $esCampo ? ($cantEstimadaProg > 0 ? $cantEstimadaProg : 1) : 1;

        $respuesta->enviarJson([
            'status' => 'success',
            'hoja' => $hoja,
            'os' => [
                'id' => $os['id'],
                'codigo_os' => $os['codigo_os'],
                'requiere_muestreo' => $os['requiere_muestreo']
            ],
            'prefijo_muestra' => $prefijoMuestra,
            'tipo_origen' => $tipoOrigen,
            'cantidad_muestras_sugerida' => $cantSugerida,
            'lugar_muestreo' => $lugarMuestreo,
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
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            $modelo = new OperacionModelo();
            $os = $modelo->obtenerOSPorId($idOS);
            if (!$os) {
                $_SESSION['error'] = 'Orden de Servicio no encontrada.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            if ($os['estado'] === 'Estado 2: Revision') {
                $_SESSION['error'] = 'La Hoja de Servicio se encuentra en revisión de supervisor y no puede ser modificada hasta que sea observada.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            if (in_array($os['estado'], ['Estado 3: Ingreso Directo', 'Estado 3A: Programacion Muestreo', 'Muestreo Completado'])) {
                $_SESSION['error'] = 'La Hoja de Servicio ya ha sido aprobada formalmente y no admite modificaciones.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }
            
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

            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
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
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            $modelo = new OperacionModelo();
            $os = $modelo->obtenerOSPorId($idOS);
            if (!$os) {
                $_SESSION['error'] = 'Orden de Servicio no encontrada.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            if ($modelo->actualizarEstadoOS($idOS, 'Estado 2: Revision')) {
                $codigoTexto = $os['codigo_os'] . (!empty($os['cliente_nombre']) ? ' (' . $os['cliente_nombre'] . ')' : '');
                registrarBitacora('hojas_servicio', 'cambiar_estado', 'Orden de Servicio ' . $codigoTexto . ' enviada a revisión de supervisor.', $idOS);
                $_SESSION['exito'] = 'Hoja de Servicio enviada a revisión del supervisor correctamente.';
            } else {
                $_SESSION['error'] = 'Error al enviar la Hoja de Servicio a revisión.';
            }

            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
        }
    }

    public function procesarRevision(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        // Solo un Supervisor (Rol 3) o Administrador (Rol 1) puede procesar la revisión
        $rol = (int)($_SESSION['usuario_rol'] ?? 0);
        if ($rol !== 1 && $rol !== 3) {
            $_SESSION['error'] = 'No tiene permisos de supervisor para cambiar el estado de la Orden de Servicio.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
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
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            $modelo = new OperacionModelo();
            $os = $modelo->obtenerOSPorId($idOS);
            if (!$os) {
                $_SESSION['error'] = 'Orden de Servicio no encontrada.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
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

            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
        }
    }

    public function descargarSolicitudPDF(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        $idOS = (int)($_GET['id_os'] ?? 0);
        if ($idOS <= 0) {
            $_SESSION['error'] = 'Orden de Servicio inválida.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $modelo = new OperacionModelo();
        $os = $modelo->obtenerOSPorId($idOS);
        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
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
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
        }
    }

    /**
     * Analiza los ensayos contratados en la O/S y deduce automáticamente la naturaleza
     * de la muestra y los parámetros/análisis de la Hoja RT-FM-13 (Sección 3).
     */
    public function detectarParametrosYNaturaleza(array $ensayos): array {
        $nats = [];
        $flags = [
            'req_resistencia_concreto' => 0,
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
            'descripcion_otros_analisis' => ''
        ];

        foreach ($ensayos as $e) {
            $texto = mb_strtolower(
                ($e['descripcion_ensayo'] ?? '') . ' ' .
                ($e['nombre_ensayo'] ?? '') . ' ' .
                ($e['procedimiento'] ?? '') . ' ' .
                ($e['norma_astm'] ?? '') . ' ' .
                ($e['codigo_servicio'] ?? '') . ' ' .
                ($e['codigo_hoja_campo'] ?? '')
            );

            // Suelos
            if (strpos($texto, 'granulo') !== false) {
                $flags['req_granulometria'] = 1;
                if (strpos($texto, 'agregado') !== false) {
                    $nats['Agregados'] = true;
                } else {
                    $nats['Suelo'] = true;
                }
            }
            if (strpos($texto, 'atterberg') !== false || strpos($texto, 'consistencia') !== false || strpos($texto, 'límite') !== false || strpos($texto, 'limite') !== false || strpos($texto, 'd4318') !== false) {
                $flags['req_limites_atterberg'] = 1;
                $nats['Suelo'] = true;
            }
            if (strpos($texto, 'humedad') !== false || strpos($texto, 'd2216') !== false) {
                $flags['req_humedad'] = 1;
                $nats['Suelo'] = true;
            }
            if (strpos($texto, 'corte') !== false || strpos($texto, 'triaxial') !== false || strpos($texto, 'veleta') !== false || strpos($texto, 'd3080') !== false) {
                $flags['req_resistencia_corte'] = 1;
                $nats['Suelo'] = true;
            }
            if (strpos($texto, 'sucs') !== false || strpos($texto, 'clasificaci') !== false || strpos($texto, 'd2487') !== false || strpos($texto, 'aashto') !== false) {
                $flags['req_clasificacion_sucs_hr'] = 1;
                $nats['Suelo'] = true;
            }
            if (strpos($texto, 'proctor') !== false || strpos($texto, 'compactaci') !== false || strpos($texto, 'd698') !== false || strpos($texto, 'd1557') !== false) {
                $flags['req_proctor_sm'] = 1;
                $nats['Suelo'] = true;
            }
            if (strpos($texto, 'infiltraci') !== false || strpos($texto, 'porchet') !== false || strpos($texto, 'permeabil') !== false) {
                $flags['req_infiltracion'] = 1;
                $nats['Suelo'] = true;
            }
            if (strpos($texto, 'cbr') !== false || strpos($texto, 'rodamiento de california') !== false || strpos($texto, 'd1883') !== false) {
                $flags['req_cbr'] = 1;
                $nats['Suelo'] = true;
            }
            if (strpos($texto, 'densidad') !== false || strpos($texto, 'cono de arena') !== false || strpos($texto, 'densimetro') !== false || strpos($texto, 'densímetro') !== false || strpos($texto, 'd1556') !== false || strpos($texto, 'd6938') !== false) {
                $flags['req_densidad'] = 1;
                $nats['Suelo'] = true;
            }
            if (strpos($texto, 'spt') !== false || strpos($texto, 'sondeo') !== false || strpos($texto, 'shelby') !== false || strpos($texto, 'd1586') !== false) {
                $nats['Suelo'] = true;
            }

            // Adoquines
            $esAdoquin = (strpos($texto, 'adoqu') !== false || strpos($texto, 'c936') !== false);
            if ($esAdoquin) {
                $flags['req_resistencia_adoquin'] = 1;
                $nats['Adoquines'] = true;
            }

            // Bloques
            $esBloque = (strpos($texto, 'bloque') !== false || strpos($texto, 'c90') !== false || strpos($texto, 'mamposter') !== false);
            if ($esBloque) {
                $flags['req_resistencia_bloques'] = 1;
                $nats['Bloques'] = true;
            }

            // Concreto general (cilindros, vigas, núcleos, revenimiento, etc., excluyendo si es únicamente adoquín o bloque)
            if (!$esAdoquin && !$esBloque) {
                if (strpos($texto, 'compresi') !== false && (strpos($texto, 'concreto') !== false || strpos($texto, 'cilindro') !== false || strpos($texto, 'c39') !== false)) {
                    $flags['req_resistencia_concreto'] = 1;
                    $nats['Concreto'] = true;
                } elseif (strpos($texto, 'revenimiento') !== false || strpos($texto, 'escler') !== false || strpos($texto, 'c805') !== false || strpos($texto, 'c143') !== false || strpos($texto, 'c172') !== false || strpos($texto, 'c1064') !== false || strpos($texto, 'c42') !== false || strpos($texto, 'viga') !== false || (strpos($texto, 'concreto') !== false && strpos($texto, 'resistencia') !== false)) {
                    $flags['req_resistencia_concreto'] = 1;
                    $nats['Concreto'] = true;
                }
            }

            // Agregados
            if (strpos($texto, 'agregado') !== false || strpos($texto, 'arena') !== false || strpos($texto, 'grava') !== false || strpos($texto, 'c136') !== false || strpos($texto, 'c117') !== false || strpos($texto, 'c40') !== false || strpos($texto, 'c127') !== false || strpos($texto, 'c128') !== false) {
                $nats['Agregados'] = true;
            }

            // Otros materiales / Acero / etc.
            if (strpos($texto, 'acero') !== false || strpos($texto, 'ferroscan') !== false || strpos($texto, 'metal') !== false || strpos($texto, 'asfalto') !== false) {
                $flags['req_otros_materiales'] = 1;
                $nats['Otros materiales'] = true;
                if (empty($flags['descripcion_otros_analisis'])) {
                    $flags['descripcion_otros_analisis'] = $e['nombre_ensayo'] ?? $e['descripcion_ensayo'] ?? '';
                }
            }
        }

        if (empty($nats)) {
            $nats['Concreto'] = true;
            $flags['req_resistencia_concreto'] = 1;
        }

        return [
            'naturalezas' => array_keys($nats),
            'naturaleza_muestra_str' => implode(', ', array_keys($nats)),
            'flags' => $flags
        ];
    }
}
