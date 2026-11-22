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

    /**
     * Capa de retrocompatibilidad HTTP 302: Redirige accesos legados a /hojas-servicio/crear hacia el flujo modal real en Órdenes de Servicio.
     */
    public function crearRedirect(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $idOS = (int)($_GET['id_os'] ?? ($_GET['id'] ?? 0));
        if ($idOS > 0) {
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . $idOS);
            return;
        }
        $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
    }

    /**
     * Capa de retrocompatibilidad HTTP 302: Redirige accesos legados a /hojas-servicio/detalle hacia la descarga oficial PDF de la Hoja RT-FM-13.
     */
    public function detalleRedirect(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $idOS = (int)($_GET['id_os'] ?? ($_GET['id'] ?? 0));
        if ($idOS > 0) {
            $respuesta->redirigir('/Cycsa/publico/hojas-servicio/descargar?id_os=' . $idOS);
            return;
        }
        $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
    }

    public function hojaSolicitudDatosAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        $idOS = (int)($_GET['id_os'] ?? 0);
        $idHoja = (int)($_GET['id_hoja'] ?? 0);
        $esNueva = !empty($_GET['nueva']);

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

        $todasHojas = $modelo->obtenerHojasSolicitudPorOS($idOS);
        $totalHojas = count($todasHojas);
        if ($totalHojas === 0) {
            $esNueva = true;
        }

        $hoja = null;
        if ($idHoja > 0) {
            $hoja = $modelo->obtenerHojaSolicitudPorId($idHoja);
        } elseif (!$esNueva && $totalHojas > 0) {
            $hoja = end($todasHojas);
        }
        
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

        $idDetalleParam = (int)($_GET['id_detalle_cotizacion'] ?? ($hoja['id_detalle_cotizacion'] ?? 0));

        // Mapear qué detalles ya tienen una hoja creada
        $detallesConHoja = [];
        foreach ($todasHojas as $th) {
            if (!empty($th['id_detalle_cotizacion'])) {
                $detallesConHoja[(int)$th['id_detalle_cotizacion']] = (int)$th['id'];
            }
        }

        $productosPermitidos = [];
        $detalleSeleccionado = null;
        if (!empty($osCompleta['ensayos']) && is_array($osCompleta['ensayos'])) {
            foreach ($osCompleta['ensayos'] as $ens) {
                if (array_key_exists('permitido', $ens) && (int)$ens['permitido'] !== 1) {
                    continue;
                }
                $idDet = (int)($ens['id'] ?? 0);
                $tieneHoja = isset($detallesConHoja[$idDet]);
                $idProd = (int)($ens['id_producto'] ?? ($ens['producto_id'] ?? 0));
                $pItem = [
                    'id_detalle_cotizacion' => $idDet,
                    'id_producto' => $idProd,
                    'ensayo_id' => $idProd,
                    'descripcion_ensayo' => $ens['descripcion_ensayo'] ?? ($ens['nombre_ensayo'] ?? ''),
                    'codigo_servicio' => $ens['codigo_servicio'] ?? '',
                    'norma_astm' => $ens['norma_astm'] ?? '',
                    'cantidad' => (float)($ens['cantidad'] ?? 1),
                    'tiene_hoja' => $tieneHoja,
                    'id_hoja_creada' => $detallesConHoja[$idDet] ?? null,
                    'es_compactacion' => $this->esProductoCompactacion($ens)
                ];
                $productosPermitidos[] = $pItem;

                if ($idDetalleParam > 0 && $idDet === $idDetalleParam) {
                    $detalleSeleccionado = $ens;
                }
            }
        }

        if (!$detalleSeleccionado && !empty($productosPermitidos)) {
            if ($esNueva) {
                foreach ($osCompleta['ensayos'] as $ens) {
                    if (!isset($detallesConHoja[(int)$ens['id']])) {
                        $detalleSeleccionado = $ens;
                        $idDetalleParam = (int)$ens['id'];
                        break;
                    }
                }
            }
            if (!$detalleSeleccionado && !empty($osCompleta['ensayos'])) {
                $detalleSeleccionado = $osCompleta['ensayos'][0];
                $idDetalleParam = (int)$detalleSeleccionado['id'];
            }
        }

        // Prefill de flags y naturaleza para este producto específico
        if ($detalleSeleccionado) {
            $detectado = $this->detectarParametrosYNaturaleza([$detalleSeleccionado]);
        } else {
            $detectado = $this->detectarParametrosYNaturaleza($osCompleta['ensayos'] ?? []);
        }

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

        $proximoNumeroRegistro = $modelo->obtenerSiguienteNumeroRegistroGlobal();

        if (!$hoja || $esNueva) {
            $hoja = array_merge([
                'id' => 0,
                'id_os' => $idOS,
                'id_detalle_cotizacion' => $idDetalleParam,
                'id_producto' => (int)($detalleSeleccionado['id_producto'] ?? 0),
                'ensayo_id' => (int)($detalleSeleccionado['id_producto'] ?? 0),
                'descripcion_ensayo' => $detalleSeleccionado['descripcion_ensayo'] ?? ($detalleSeleccionado['nombre_ensayo'] ?? ''),
                'codigo_servicio' => $detalleSeleccionado['codigo_servicio'] ?? '',
                'codigo_documento' => 'CYCSA-RT-FM-13',
                'numero_registro' => $proximoNumeroRegistro,
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
            if (empty($hoja['numero_registro'])) $hoja['numero_registro'] = str_pad((string)($hoja['id'] ?? $idOS), 2, '0', STR_PAD_LEFT);
            if (empty($hoja['nombre_empresa_o_cliente'])) $hoja['nombre_empresa_o_cliente'] = $nombreCliente;
            if (empty($hoja['razon_social'])) $hoja['razon_social'] = $nombreCliente;
            if (empty($hoja['direccion_proyecto'])) $hoja['direccion_proyecto'] = $direccionProyecto;
            if (empty($hoja['telefono'])) $hoja['telefono'] = $telefonoCliente;
            if (empty($hoja['correo_electronico']) && !empty($emailCliente)) $hoja['correo_electronico'] = $emailCliente;
            if (empty($hoja['nombre_persona_entrega_muestra'])) $hoja['nombre_persona_entrega_muestra'] = $atencionA;
            if (empty($hoja['nombre_persona_toma_muestra']) && !empty($tecnicoMuestreo)) $hoja['nombre_persona_toma_muestra'] = $tecnicoMuestreo;
            if (empty($hoja['procedencia_punto_muestreo'])) $hoja['procedencia_punto_muestreo'] = $procedenciaPunto;
            if (empty($hoja['naturaleza_muestra'])) $hoja['naturaleza_muestra'] = $detectado['naturaleza_muestra_str'];
            if (empty($hoja['id_detalle_cotizacion']) && $idDetalleParam > 0) {
                $hoja['id_detalle_cotizacion'] = $idDetalleParam;
            }
            if (empty($hoja['descripcion_ensayo']) && $detalleSeleccionado) {
                $hoja['descripcion_ensayo'] = $detalleSeleccionado['descripcion_ensayo'] ?? ($detalleSeleccionado['nombre_ensayo'] ?? '');
                $hoja['codigo_servicio'] = $detalleSeleccionado['codigo_servicio'] ?? '';
            }
        }

        $prefijoParam = strtoupper(trim($_GET['prefijo'] ?? ''));
        if ($prefijoParam === 'MC' || $prefijoParam === 'MS') {
            $prefijoMuestra = $prefijoParam;
        } else {
            $prefijoMuestra = determinarPrefijoMuestraOS($osCompleta ?: $os);
        }

        $esCampo = ($prefijoMuestra === 'MC');
        $tipoOrigen = $esCampo ? 'campo' : 'laboratorio';

        // Calcular muestras totales contratadas en la O/S vs las ya registradas en hojas previas
        $totalContratado = 0;
        if (!empty($osCompleta['ensayos']) && is_array($osCompleta['ensayos'])) {
            foreach ($osCompleta['ensayos'] as $ens) {
                if (array_key_exists('permitido', $ens) && (int)$ens['permitido'] !== 1) {
                    continue;
                }
                $totalContratado += (float)($ens['cantidad'] ?? 1);
            }
        }
        $totalYaRegistrado = 0;
        foreach ($todasHojas as $th) {
            // Si estamos visualizando/editando una hoja existente, no contamos las de esa hoja
            if (!$esNueva && $hoja && (int)$th['id'] === (int)($hoja['id'] ?? 0)) {
                continue;
            }
            $mArr = json_decode($th['muestras_json'] ?? '[]', true) ?: [];
            $totalYaRegistrado += count($mArr);
        }
        $disponiblesOS = max(0, (int)round($totalContratado - $totalYaRegistrado));

        if ($esNueva) {
            $cantSugerida = 0;
        } else {
            $muestrasActuales = json_decode($hoja['muestras_json'] ?? '[]', true) ?: [];
            $cantSugerida = count($muestrasActuales);
        }

        $siguienteConsecutivoMC = $modelo->obtenerSiguienteConsecutivoMuestra((int)date('Y'), 'MC', $idOS);
        $siguienteConsecutivoMS = $modelo->obtenerSiguienteConsecutivoMuestra((int)date('Y'), 'MS', $idOS);
        $siguienteConsecutivoMuestra = ($prefijoMuestra === 'MC') ? $siguienteConsecutivoMC : $siguienteConsecutivoMS;

        if ($esNueva) {
            if ($esCampo) {
                $hoja['condicion_muestreo_datos'] = 'Muestreo realizado por técnicos de CYCSA';
                if (!empty($tecnicoMuestreo)) {
                    $hoja['nombre_persona_toma_muestra'] = $tecnicoMuestreo;
                }
            } else {
                $hoja['condicion_muestreo_datos'] = 'Muestra tomada y entregada por el cliente';
                if (empty($hoja['nombre_persona_toma_muestra'])) {
                    $hoja['nombre_persona_toma_muestra'] = $atencionA ?: ($os['cliente_nombre'] ?? 'Cliente');
                }
            }
        }

        // Simplificar lista de todas las hojas para el selector del frontend
        $listaHojasFrontend = [];
        $seq = 1;
        foreach ($todasHojas as $th) {
            $mArr = json_decode($th['muestras_json'] ?? '[]', true) ?: [];
            $listaHojasFrontend[] = [
                'id' => (int)$th['id'],
                'numero_secuencial' => $seq++,
                'numero_registro' => $th['numero_registro'] ?? ('ID ' . $th['id']),
                'fecha_creacion' => $th['fecha_creacion'] ?? '',
                'fecha_toma' => $th['fecha_hora_toma_muestra'] ?? '',
                'toma_por' => $th['nombre_persona_toma_muestra'] ?? 'Cliente',
                'total_muestras' => count($mArr),
                'id_detalle_cotizacion' => (int)($th['id_detalle_cotizacion'] ?? 0),
                'id_producto' => (int)($th['id_producto'] ?? 0),
                'ensayo_id' => (int)($th['ensayo_id'] ?? 0),
                'descripcion_ensayo' => $th['descripcion_ensayo'] ?? '',
                'codigo_servicio' => $th['codigo_servicio'] ?? '',
                'nombre_comercial' => $th['nombre_comercial'] ?? ''
            ];
        }

        $pltModelo = new \Cycsa\Modulos\Configuracion\Modelos\PlantillaModelo();
        $clienteId = (int)($osCompleta['id_cliente'] ?? ($os['id_cliente'] ?? 0));
        $detallesRelacionales = ($hoja && !empty($hoja['id'])) ? $pltModelo->obtenerDetallesPorHoja((int)$hoja['id']) : [];

        // Obtener ensayos disponibles con sus plantillas aprobadas y sugerencias
        $ensayosOS = [];
        if (!empty($osCompleta['ensayos']) && is_array($osCompleta['ensayos'])) {
            foreach ($osCompleta['ensayos'] as $ens) {
                if (array_key_exists('permitido', $ens) && (int)$ens['permitido'] !== 1) {
                    continue;
                }
                $eId = (int)($ens['id_producto'] ?? ($ens['producto_id'] ?? ($ens['id'] ?? 1)));
                $eNom = $ens['nombre_ensayo'] ?? ($ens['ensayo_servicio'] ?? ($ens['descripcion_ensayo'] ?? 'Ensayo'));
                $ensayosOS[] = [
                    'id' => $eId,
                    'id_detalle' => (int)($ens['id'] ?? 0),
                    'nombre' => $eNom,
                    'cantidad' => (float)($ens['cantidad'] ?? 1),
                    'permitido' => 1,
                    'plantillas_disponibles' => $pltModelo->obtenerPlantillasDisponiblesParaEnsayo($eId, $clienteId),
                    'plantilla_sugerida' => $pltModelo->obtenerPlantillaSugerida($eId, $clienteId)
                ];
            }
        }

        if (empty($ensayosOS)) {
            $ensayosOS[] = [
                'id' => 1,
                'nombre' => 'Ensayos de Laboratorio',
                'cantidad' => 1,
                'plantillas_disponibles' => $pltModelo->obtenerPlantillasDisponiblesParaEnsayo(1, $clienteId),
                'plantilla_sugerida' => $pltModelo->obtenerPlantillaSugerida(1, $clienteId)
            ];
        }

        $idProductoHoja = (int)($detalleSeleccionado['id_producto'] ?? ($detalleSeleccionado['producto_id'] ?? ($hoja['id_producto'] ?? 0)));
        if ($idProductoHoja <= 0 && !empty($ensayosOS)) {
            $idProductoHoja = (int)($ensayosOS[0]['id'] ?? 1);
        }
        $pltSugeridaHoja = ($idProductoHoja > 0) ? $pltModelo->obtenerPlantillaSugerida($idProductoHoja, $clienteId) : null;
        $esCompactacionHoja = $detalleSeleccionado ? $this->esProductoCompactacion($detalleSeleccionado) : false;
        $alertaAlcanceMensual = $osModelo->verificarAlcanceMensual($idOS, $idProductoHoja);

        $respuesta->enviarJson([
            'status' => 'success',
            'producto_hoja_id' => $idProductoHoja,
            'plantilla_sugerida_hoja' => $pltSugeridaHoja,
            'es_compactacion_hoja' => $esCompactacionHoja,
            'alerta_alcance_mensual' => $alertaAlcanceMensual,
            'detalle_seleccionado' => $detalleSeleccionado,
            'hoja' => $hoja,
            'todas_las_hojas' => $listaHojasFrontend,
            'total_hojas' => $totalHojas,
            'id_hoja_actual' => (int)($hoja['id'] ?? 0),
            'es_nueva' => $esNueva,
            'os' => [
                'id' => $os['id'],
                'codigo_os' => $os['codigo_os'],
                'requiere_muestreo' => $os['requiere_muestreo'],
                'tipo_contrato' => $os['tipo_contrato'] ?? 'Puntual'
            ],
            'prefijo_muestra' => $prefijoMuestra,
            'siguiente_consecutivo_muestra' => $siguienteConsecutivoMuestra,
            'siguiente_consecutivo_mc' => $siguienteConsecutivoMC,
            'siguiente_consecutivo_ms' => $siguienteConsecutivoMS,
            'tipo_origen' => $tipoOrigen,
            'cantidad_muestras_sugerida' => $cantSugerida,
            'total_contratado' => $totalContratado,
            'total_registrado' => $totalYaRegistrado,
            'cantidad_disponible' => $disponiblesOS,
            'lugar_muestreo' => $lugarMuestreo,
            'os_referencia' => $osCompleta,
            'cliente_id' => $clienteId,
            'detalles_muestras_relacionales' => $detallesRelacionales,
            'ensayos_disponibles_os' => $ensayosOS,
            'productos_permitidos_os' => $productosPermitidos,
            'productos_permitidos' => $productosPermitidos
        ]);
    }

    public function guardarHojaSolicitud(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idOS = (int)($datos['id_os'] ?? 0);
            $idHoja = (int)($datos['id_hoja'] ?? 0);

            if ($idOS <= 0) {
                $_SESSION['error'] = 'Orden de Servicio inválida.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                $_SESSION['error'] = 'Token CSRF inválido o sesión expirada.';
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

            // Si la O/S ya está finalizada o archivada, no admite alteraciones
            if (in_array($os['estado'], ['Finalizado', 'Archivado', 'Cerrado'], true)) {
                $_SESSION['error'] = 'La Orden de Servicio está cerrada o archivada y no admite modificaciones.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            // Gate duro RT-FM-13: Validar que la O/S esté en estado de aprobación válido
            if (!esOrdenServicioAprobada($os['estado'] ?? null)) {
                $_SESSION['error'] = 'La Orden de Servicio debe estar en estado Aprobada para registrar o modificar Hojas de Solicitud (CYCSA-RT-FM-13).';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            // Exigir obligatorio $datos['id_detalle_cotizacion']. Si está vacío o es <= 0, rechazar
            $idDetalleCotizacion = (int)($datos['id_detalle_cotizacion'] ?? 0);
            if ($idDetalleCotizacion <= 0 && $idHoja > 0) {
                $hojaExistente = $modelo->obtenerHojaSolicitudPorId($idHoja);
                if ($hojaExistente && !empty($hojaExistente['id_detalle_cotizacion'])) {
                    $idDetalleCotizacion = (int)$hojaExistente['id_detalle_cotizacion'];
                    $datos['id_detalle_cotizacion'] = $idDetalleCotizacion;
                }
            }

            if ($idDetalleCotizacion <= 0) {
                $_SESSION['error'] = 'Debe seleccionar el producto o ensayo de la Orden de Servicio al que corresponde esta Hoja de Solicitud (CYCSA-RT-FM-13).';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            // Obtener id_producto y ensayo_id correspondientes a ese detalle cotizado desde cotizacion_detalles
            $db = Conexion::obtenerInstancia();
            $stmtDet = $db->prepare("SELECT cd.*, p.codigo_servicio, p.norma_astm, p.nombre_comercial, fe.archivo_markdown, fe.nombre AS formato_nombre 
                                     FROM cotizacion_detalles cd
                                     LEFT JOIN productos p ON cd.id_producto = p.id
                                     LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                                     WHERE cd.id = :id");
            $stmtDet->execute(['id' => $idDetalleCotizacion]);
            $detalle = $stmtDet->fetch(PDO::FETCH_ASSOC);

            if (!$detalle) {
                $_SESSION['error'] = 'El producto o ensayo seleccionado no existe.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            $idProducto = (int)($detalle['id_producto'] ?? 0);
            $ensayoId = $idProducto > 0 ? $idProducto : 1;
            $datos['id_producto'] = $idProducto;
            $datos['ensayo_id'] = $ensayoId;

            // Prefill de flags y naturaleza en base a ESE producto específico
            $resFlags = $this->detectarParametrosYNaturaleza([$detalle]);
            $flagsProducto = $resFlags['flags'] ?? [];

            if (empty($datos['naturaleza_muestra'])) {
                $datos['naturaleza_muestra'] = $resFlags['naturaleza_muestra_str'];
            }

            // ADDENDUM P0 (Abdia 2026-11-02): Parámetros libres en RT-FM-13.
            // Los checkboxes de req_* NO se deshabilitan ni se fuerzan a 0 por producto/ensayo.
            // El técnico marca lo que quiera. NO llamar a sanitizarCamposReqSegunFlags en el save path.
            if ($idHoja <= 0) {
                foreach ($flagsProducto as $k => $v) {
                    if (!empty($v) && empty($datos[$k])) {
                        $datos[$k] = $v;
                    }
                }
            }
            
            // Procesar tabla dinámica de especímenes y plantillas
            $identMuestras = [];
            $mNombres = $datos['m_nombre'] ?? [];
            $mDescripciones = $datos['m_desc'] ?? [];
            $mInfos = $datos['m_info'] ?? [];
            $mEnsayos = $datos['m_ensayo_id'] ?? [];
            $mPlantillas = $datos['m_plantilla_id'] ?? [];
            $plantillaGlobalId = (int)($datos['plantilla_global_id'] ?? 0);
            if ($plantillaGlobalId <= 0) {
                $pltMod = new \Cycsa\Modulos\Configuracion\Modelos\PlantillaModelo();
                // Usar estrictamente el producto/ensayo específico de esta hoja, nunca ensayosOS[0]
                $sug = $pltMod->obtenerPlantillaSugerida($ensayoId, (int)($os['id_cliente'] ?? 0));
                if (!empty($sug['id'])) {
                    $plantillaGlobalId = (int)$sug['id'];
                }
            }

            if ($plantillaGlobalId > 0) {
                for ($i = 0; $i < count($mNombres); $i++) {
                    if (empty($mPlantillas[$i]) || (int)$mPlantillas[$i] <= 0) {
                        $mPlantillas[$i] = $plantillaGlobalId;
                    }
                }
            }
            $mManuales = $datos['m_seleccion_manual'] ?? [];
            $mMotivos = $datos['m_motivo_cambio'] ?? [];
            $esRecepcionFisica = !empty($datos['confirmar_recepcion_lims']) || !empty($datos['firma_recibe_cycsa']);

            // Validación ISO 17025: la RT-FM-13 no se puede guardar como recibida si alguna muestra no tiene plantilla asignada
            if ($esRecepcionFisica) {
                for ($i = 0; $i < count($mNombres); $i++) {
                    $nom = trim($mNombres[$i] ?? '');
                    if (empty($nom)) continue;
                    $pId = (int)($mPlantillas[$i] ?? 0);
                    if ($pId <= 0) {
                        $_SESSION['error'] = 'Validación ISO 17025: La muestra "' . $nom . '" no tiene una plantilla de ensayo asignada. No se puede admitir la recepción física sin plantilla.';
                        $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                        return;
                    }
                }
            }
            
            for ($i = 0; $i < count($mNombres); $i++) {
                $nom = trim($mNombres[$i]);
                if (empty($nom)) continue;
                $identMuestras[] = [
                    'nombre_muestra' => $nom,
                    'descripcion' => trim($mDescripciones[$i] ?? ''),
                    'info_importante' => trim($mInfos[$i] ?? ''),
                    'ensayo_id' => !empty($mEnsayos[$i]) && (int)$mEnsayos[$i] > 0 ? (int)$mEnsayos[$i] : $ensayoId,
                    'plantilla_id' => (int)($mPlantillas[$i] ?? 0)
                ];
            }
            $datos['identificacion_muestras_json'] = json_encode($identMuestras);

            // Whitelist de la O/S: verificar que el producto seleccionado esté habilitado
            $osModelo = new \Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo();
            $idsPermitidos = $osModelo->obtenerIdsPermitidos($idOS);
            if ($idsPermitidos !== null && !in_array($idDetalleCotizacion, $idsPermitidos, true)) {
                $_SESSION['error'] = 'El ensayo seleccionado no está habilitado en la whitelist de esta Orden de Servicio.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            $idHojaGuardada = 0;
            if ($modelo->guardarHojaSolicitud($datos, $idHojaGuardada)) {
                $os = $modelo->obtenerOSPorId($idOS);
                $hoja = $modelo->obtenerHojaSolicitudPorId($idHojaGuardada) ?: $modelo->obtenerHojaSolicitudPorOS($idOS, $idHojaGuardada);
                
                // Guardar en tabla relacional formal hojas_solicitud_detalles
                $muestrasSaneadas = json_decode($hoja['muestras_json'] ?? '[]', true) ?: [];
                $filasDetalles = [];
                for ($i = 0; $i < count($mNombres); $i++) {
                    $nom = !empty($muestrasSaneadas[$i]['nombre_muestra'])
                        ? trim($muestrasSaneadas[$i]['nombre_muestra'])
                        : trim($mNombres[$i] ?? '');
                    if (empty($nom)) continue;
                    $filasDetalles[] = [
                        'codigo_muestra' => $nom,
                        'descripcion_ubicacion' => trim($mDescripciones[$i] ?? ''),
                        'observacion_edad' => trim($mInfos[$i] ?? ''),
                        'ensayo_id' => !empty($mEnsayos[$i]) && (int)$mEnsayos[$i] > 0 ? (int)$mEnsayos[$i] : $ensayoId,
                        'plantilla_id' => (int)($mPlantillas[$i] ?? 0),
                        'seleccion_manual' => !empty($mManuales[$i]) ? 1 : 0,
                        'motivo_cambio' => trim($mMotivos[$i] ?? '')
                    ];
                }
                $pltModelo = new \Cycsa\Modulos\Configuracion\Modelos\PlantillaModelo();
                $usuarioId = (int)($_SESSION['usuario_id'] ?? 0);
                if ($usuarioId <= 0) {
                    $isTestingEnv = (getenv('APP_ENV') === 'testing' || ($_ENV['APP_ENV'] ?? '') === 'testing');
                    if (!$isTestingEnv) {
                        $_SESSION['error'] = 'Sesión inválida o expirada.';
                        $respuesta->redirigir('/Cycsa/publico/login');
                        return;
                    }
                    $usuarioId = 1;
                }
                $pltModelo->guardarDetallesMuestrasHoja($idHojaGuardada, $filasDetalles, $usuarioId);

                if ($esRecepcionFisica) {
                    $pltModelo->bloquearYCongelarDetallesRecepcion($idHojaGuardada, $usuarioId);
                    registrarEventoOS('hojas_servicio', 'congelar_plantillas', "Plantillas congeladas y bloqueadas por recepción física en Hoja RT-FM-13 #{$idHojaGuardada}", $idOS, [
                        'entidad_tipo' => 'rtfm13',
                        'entidad_id' => (int)$idHojaGuardada,
                        'id_referencia' => (int)$idHojaGuardada,
                    ]);
                }

                require_once dirname(__DIR__, 4) . '/app/Helpers/funciones.php';
                $pdfContenido = generarHojaSolicitudPDF($hoja, $os);
                
                $dirPdf = dirname(__DIR__, 4) . '/almacenamiento/solicitudes';
                if (!file_exists($dirPdf)) {
                    mkdir($dirPdf, 0777, true);
                }

                // Guardar PDF individual por ID de hoja
                $nombrePdfHoja = "CYCSA-RT-FM-13-" . $os['codigo_os'] . "-H" . $hoja['id'] . ".pdf";
                file_put_contents($dirPdf . '/' . $nombrePdfHoja, $pdfContenido);

                // Guardar/Actualizar copia genérica para retrocompatibilidad
                $nombrePdfGenerico = "CYCSA-RT-FM-13-" . $os['codigo_os'] . ".pdf";
                file_put_contents($dirPdf . '/' . $nombrePdfGenerico, $pdfContenido);

                $numReg = $hoja['numero_registro'] ?? ('#' . $hoja['id']);
                $codigoTexto = $os['codigo_os'] . " (Hoja Reg: {$numReg})";
                registrarEventoOS('hojas_servicio', 'hoja_solicitud', 'Hoja de Solicitud CYCSA-RT-FM-13 guardada/actualizada y PDF generado para Orden de Servicio ' . $codigoTexto, $idOS, [
                    'entidad_tipo' => 'rtfm13',
                    'entidad_id' => (int)($hoja['id'] ?? $idHojaGuardada),
                    'datos' => ['id_hoja' => (int)($hoja['id'] ?? $idHojaGuardada)],
                ]);

                // Ingreso automático e idempotente de las muestras nuevas a Laboratorio (sin paso manual de Aceptar/Observar)
                if (
                    $idHojaGuardada > 0 &&
                    !empty($muestrasSaneadas) &&
                    function_exists('esOrdenServicioAprobada') &&
                    esOrdenServicioAprobada((string)($os['estado'] ?? ''))
                ) {
                    $modelo->ingresarMuestrasHojaALaboratorio($idOS, (int)$idHojaGuardada, $usuarioId, false);
                }
                
                // Nota: Si la O/S estaba "Observada", se mantiene su estado para que el usuario pueda enviarla a revisión formal con el botón correspondiente.
                if (!empty($datos['redirect_to'])) {
                    $redirectDestino = $datos['redirect_to'];
                } else {
                    $dbRec = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                    $infoRec = obtenerCamposRecepcionParaDetalle(
                        $dbRec,
                        !empty($hoja['id_detalle_cotizacion']) ? (int)$hoja['id_detalle_cotizacion'] : null,
                        !empty($hoja['id_producto']) ? (int)$hoja['id_producto'] : null
                    );
                    $tieneCamposRecepcion = !empty($infoRec['campos_recepcion']);
                    $redirectDestino = $tieneCamposRecepcion
                        ? '/Cycsa/publico/ordenes-servicio?id_os=' . $idOS . '&recepcion_hoja=' . (int)$idHojaGuardada
                        : '/Cycsa/publico/ordenes-servicio?id_os=' . $idOS;
                }
                $_SESSION['exito'] = 'Hoja de Solicitud de Servicio CYCSA-RT-FM-13 (Reg: ' . $numReg . ') guardada exitosamente y PDF generado. <a href="/Cycsa/publico/hojas-servicio/descargar?id_os=' . $idOS . '&id_hoja=' . $idHojaGuardada . '" target="_blank" class="alert-link text-decoration-underline ms-2"><i class="fa-solid fa-file-pdf"></i> Ver PDF Oficial</a>.';
                $respuesta->redirigir($redirectDestino);
                return;
            } else {
                $_SESSION['error'] = $modelo->obtenerUltimoError() ?: 'Error al registrar la Hoja de Solicitud.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }
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
            if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                $_SESSION['error'] = 'Token CSRF inválido o sesión expirada.';
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
                registrarEventoOS('hojas_servicio', 'cambiar_estado', 'Orden de Servicio ' . $codigoTexto . ' enviada a revisión de supervisor.', $idOS, [
                    'entidad_tipo' => 'rtfm13',
                    'datos' => ['estado' => 'Estado 2: Revision'],
                ]);
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
            $reqMuestreo = array_key_exists('requiere_muestreo', $datos)
                ? (int)$datos['requiere_muestreo']
                : null;

            if ($idOS <= 0 || !in_array($nuevoEstado, ['Estado 3: Ingreso Directo', 'Estado 3A: Programacion Muestreo', 'Estado 2: Observada'])) {
                $_SESSION['error'] = 'Datos de revisión inválidos.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
                $_SESSION['error'] = 'Token CSRF inválido o sesión expirada.';
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

            // Si el formulario de revisión no cambia explícitamente la modalidad,
            // conservar la decisión ya registrada durante la programación de campo.
            $reqMuestreo = $reqMuestreo ?? (int)($os['requiere_muestreo'] ?? 0);

            $db = Conexion::obtenerInstancia();
            $exito = false;

            if ($nuevoEstado === 'Estado 2: Observada') {
                // Registrar observación
                $stmt = $db->prepare("UPDATE ordenes_servicio SET estado = 'Estado 2: Observada', motivo_observacion = :motivo WHERE id = :id");
                $exito = $stmt->execute(['motivo' => $motivo, 'id' => $idOS]);
                if ($exito) {
                    $codigoTexto = $os['codigo_os'] . (!empty($os['cliente_nombre']) ? ' (' . $os['cliente_nombre'] . ')' : '');
                    registrarEventoOS('hojas_servicio', 'cambiar_estado', 'Orden de Servicio ' . $codigoTexto . ' observada por supervisor: ' . $motivo, $idOS, [
                        'entidad_tipo' => 'rtfm13',
                        'datos' => ['estado' => 'Estado 2: Observada', 'motivo' => $motivo],
                    ]);
                    $_SESSION['exito'] = 'La Hoja de Servicio ha sido observada y devuelta al emisor.';
                }
            } else {
                // Registrar aprobación (y actualizar requiere_muestreo)
                $stmt = $db->prepare("UPDATE ordenes_servicio SET estado = :estado, requiere_muestreo = :req, motivo_observacion = NULL WHERE id = :id");
                $exito = $stmt->execute(['estado' => $nuevoEstado, 'req' => $reqMuestreo, 'id' => $idOS]);
                if ($exito) {
                    $codigoTexto = $os['codigo_os'] . (!empty($os['cliente_nombre']) ? ' (' . $os['cliente_nombre'] . ')' : '');
                    registrarEventoOS('hojas_servicio', 'cambiar_estado', 'Orden de Servicio ' . $codigoTexto . ' aprobada y pasada a estado: ' . $nuevoEstado, $idOS, [
                        'entidad_tipo' => 'rtfm13',
                        'datos' => ['estado' => $nuevoEstado],
                    ]);
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
        $idHoja = (int)($_GET['id_hoja'] ?? 0);

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

        $hoja = null;
        if ($idHoja > 0) {
            $hoja = $modelo->obtenerHojaSolicitudPorId($idHoja);
        }
        if (!$hoja) {
            $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS);
        }
        if (!$hoja) {
            $_SESSION['error'] = 'No se encontró la Hoja de Servicio técnica solicitada.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $baseAlmacenamiento = realpath(dirname(__DIR__, 4) . '/almacenamiento');
        $codigoSanitizado = preg_replace('/[^a-zA-Z0-9_-]/', '_', $os['codigo_os']);
        
        // 1. Probar ruta específica con ID de hoja
        $nombrePdfHoja = "CYCSA-RT-FM-13-" . $codigoSanitizado . "-H" . $hoja['id'] . ".pdf";
        $rutaPdfHoja = dirname(__DIR__, 4) . '/almacenamiento/solicitudes/' . $nombrePdfHoja;
        if (file_exists($rutaPdfHoja)) {
            $rutaReal = realpath($rutaPdfHoja);
            if ($baseAlmacenamiento && $rutaReal && strpos($rutaReal, $baseAlmacenamiento) === 0) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . basename($rutaReal) . '"');
                readfile($rutaReal);
                exit;
            }
        }

        // 2. Probar ruta genérica
        $nombrePdfGenerico = "CYCSA-RT-FM-13-" . $codigoSanitizado . ".pdf";
        $rutaPdfGenerico = dirname(__DIR__, 4) . '/almacenamiento/solicitudes/' . $nombrePdfGenerico;
        if (file_exists($rutaPdfGenerico) && ($idHoja === 0 || count($modelo->obtenerHojasSolicitudPorOS($idOS)) <= 1)) {
            $rutaReal = realpath($rutaPdfGenerico);
            if ($baseAlmacenamiento && $rutaReal && strpos($rutaReal, $baseAlmacenamiento) === 0) {
                header('Content-Type: application/pdf');
                header('Content-Disposition: inline; filename="' . basename($rutaReal) . '"');
                readfile($rutaReal);
                exit;
            }
        }

        // 3. Generar al vuelo si no existiera físicamente
        require_once dirname(__DIR__, 4) . '/app/Helpers/funciones.php';
        $pdfContenido = generarHojaSolicitudPDF($hoja, $os);
        
        $dirPdf = dirname(__DIR__, 4) . '/almacenamiento/solicitudes';
        if (!file_exists($dirPdf)) {
            mkdir($dirPdf, 0777, true);
        }
        file_put_contents($rutaPdfHoja, $pdfContenido);
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="' . $nombrePdfHoja . '"');
        echo $pdfContenido;
        exit;
    }

    /**
     * Analiza los ensayos contratados en la O/S y deduce automáticamente la naturaleza
     * de la muestra y los parámetros/análisis de la Hoja RT-FM-13 (Sección 3).
     */
    /**
     * Sanea server-side los campos req_* desactivando categorías no permitidas en la whitelist.
     */
    /**
     * Determina si un detalle/producto corresponde a compactación (ASTM D6938, D1556, D698, D1557 o formato).
     */
    public function esProductoCompactacion(array $item): bool {
        return esItemCompactacion($item);
    }

    public function sanitizarCamposReqSegunFlags(array $datos, array $flagsPermitidos): array {
        $camposReq = [
            'req_resistencia_concreto', 'req_resistencia_adoquin', 'req_resistencia_bloques',
            'req_granulometria', 'req_limites_atterberg', 'req_humedad', 'req_resistencia_corte',
            'req_clasificacion_sucs_hr', 'req_proctor_sm', 'req_infiltracion', 'req_cbr', 'req_densidad',
            'req_otros_materiales'
        ];

        foreach ($camposReq as $campo) {
            if (!empty($datos[$campo]) && empty($flagsPermitidos[$campo])) {
                $datos[$campo] = 0;
            }
        }

        return $datos;
    }

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

    /**
     * 2.4: Enriquece los campos de recepción de una Hoja RT-FM-13 asegurando:
     * - 'Nombre muestra' editable por Atención al Cliente en cualquier formato.
     * - Columnas de recepción de compactación/densímetro cuando el formato corresponde a densidad in situ / compactación.
     *
     * @param array<string, mixed> $infoFormato
     * @param array<string, mixed> $hoja
     * @return array<int, array<string, mixed>>
     */
    private function enriquecerCamposRecepcionHoja(array $infoFormato, array $hoja): array {
        $base = is_array($infoFormato['campos_recepcion'] ?? null) ? $infoFormato['campos_recepcion'] : [];
        $clavesExistentes = [];
        foreach ($base as $c) {
            $nom = (string)($c['nombre'] ?? '');
            if ($nom !== '') {
                $clavesExistentes[normalizarTextoClavePHP($nom)] = true;
            }
        }

        // 1. Asegurar 'Nombre muestra' como primer campo editable de recepción
        $campos = [];
        if (!isset($clavesExistentes['nombremuestra']) && !isset($clavesExistentes['nombredelamuestra'])) {
            $campos[] = [
                'nombre' => 'Nombre muestra',
                'clave' => 'nombremuestra',
                'tipo' => 'text',
                'step' => '',
                'rol' => 'recepcion',
            ];
            $clavesExistentes['nombremuestra'] = true;
        }
        foreach ($base as $c) {
            $campos[] = $c;
        }

        // 2. Detectar formatos de compactación / densímetro / cono de arena / reemplazo de agua
        $textoFormato = mb_strtolower(
            (string)($infoFormato['archivo_markdown'] ?? '') . ' ' .
            (string)($infoFormato['formato_nombre'] ?? '') . ' ' .
            (string)($infoFormato['codigo_formato'] ?? '') . ' ' .
            (string)($hoja['descripcion_ensayo'] ?? '') . ' ' .
            (string)($hoja['codigo_servicio'] ?? '') . ' ' .
            (string)($hoja['nombre_comercial'] ?? ''),
            'UTF-8'
        );

        $esCompactacion = false;
        foreach (['compactacion', 'compactación', 'densimetro', 'densímetro', 'cono_de_arena', 'cono de arena', 'reemplazo_de_agua', 'reemplazo de agua', 'd6938', 'd1556', 'd2167', 'rt-fm-22 b', 'rt-fm-22 c'] as $kw) {
            if (str_contains($textoFormato, $kw)) {
                $esCompactacion = true;
                break;
            }
        }

        if ($esCompactacion) {
            $columnasCompactacion = [
                ['nombre' => 'Capa (N°)', 'tipo' => 'text', 'step' => ''],
                ['nombre' => 'Espesor (cm)', 'tipo' => 'number', 'step' => '0.01'],
                ['nombre' => 'Profundidad (cm)', 'tipo' => 'number', 'step' => '0.01'],
                ['nombre' => 'P.V.S Max (kg/m³)', 'tipo' => 'number', 'step' => '0.01'],
                ['nombre' => 'Humedad Óptima', 'tipo' => 'number', 'step' => '0.01'],
                ['nombre' => 'P.V.S.Sitio (kg/m³)', 'tipo' => 'number', 'step' => '0.01'],
                ['nombre' => 'Humedad sitio', 'tipo' => 'number', 'step' => '0.01'],
                ['nombre' => 'Compactación', 'tipo' => 'number', 'step' => '0.01'],
                ['nombre' => 'Fecha de muestreo', 'tipo' => 'date', 'step' => ''],
            ];
            foreach ($columnasCompactacion as $colC) {
                $kNorm = normalizarTextoClavePHP($colC['nombre']);
                if (!isset($clavesExistentes[$kNorm])) {
                    $campos[] = [
                        'nombre' => $colC['nombre'],
                        'clave' => $kNorm,
                        'tipo' => $colC['tipo'],
                        'step' => $colC['step'],
                        'rol' => 'recepcion',
                    ];
                    $clavesExistentes[$kNorm] = true;
                }
            }
        }

        return $campos;
    }

    /**
     * AJAX: Retorna los campos de Recepción (Órdenes de Servicio) para una Hoja RT-FM-13
     * específica y todas las N muestras declaradas en ella, junto al listado de todas las
     * RT-FM-13 de la O/S para navegar entre hojas sin cerrar el flujo.
     */
    public function datosRecepcionAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        $idHoja = (int)($_GET['id_hoja'] ?? 0);
        $idOS = (int)($_GET['id_os'] ?? 0);

        $modelo = new OperacionModelo();
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

        $hoja = null;
        if ($idHoja > 0) {
            $hoja = $modelo->obtenerHojaSolicitudPorId($idHoja);
            if ($hoja) {
                $idOS = (int)$hoja['id_os'];
            }
        } elseif ($idOS > 0) {
            $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS);
            if ($hoja) {
                $idHoja = (int)$hoja['id'];
            }
        }

        if (!$hoja || $idOS <= 0) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Hoja RT-FM-13 no encontrada.'], 404);
            return;
        }

        $os = $modelo->obtenerOSPorId($idOS);
        if (!$os) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Orden de Servicio no encontrada.'], 404);
            return;
        }

        $todasHojas = $modelo->obtenerHojasSolicitudPorOS($idOS);
        $pltModelo = new \Cycsa\Modulos\Configuracion\Modelos\PlantillaModelo();

        $resumenHojas = [];
        foreach ($todasHojas as $th) {
            $thId = (int)$th['id'];
            $infoTh = obtenerCamposRecepcionParaDetalle(
                $db,
                !empty($th['id_detalle_cotizacion']) ? (int)$th['id_detalle_cotizacion'] : null,
                !empty($th['id_producto']) ? (int)$th['id_producto'] : null
            );
            $camposRecTh = $infoTh['campos_recepcion'];
            $mapaRecTh = construirMapaDatosRecepcionPorHoja($db, $thId);
            $mListTh = $pltModelo->obtenerDetallesPorHoja($thId);
            if (empty($mListTh)) {
                $mListTh = json_decode($th['muestras_json'] ?? '[]', true) ?: [];
            }
            $totalM = count($mListTh);
            $completas = 0;
            if (!empty($camposRecTh)) {
                foreach ($mListTh as $mRow) {
                    $codM = strtoupper(trim((string)($mRow['codigo_muestra'] ?? ($mRow['nombre_muestra'] ?? ''))));
                    if ($codM !== '' && !empty($mapaRecTh[$codM])) {
                        $completas++;
                    }
                }
            }
            $resumenHojas[] = [
                'id' => $thId,
                'numero_registro' => $th['numero_registro'] ?? ('#' . $thId),
                'descripcion_ensayo' => $th['descripcion_ensayo'] ?? ($infoTh['formato_nombre'] ?: 'Ensayo'),
                'formato_nombre' => $infoTh['formato_nombre'] ?? '',
                'codigo_formato' => $infoTh['codigo_formato'] ?? '',
                'requiere_datos_recepcion' => !empty($camposRecTh),
                'total_muestras' => $totalM,
                'muestras_con_datos' => $completas,
            ];
        }

        $infoFormato = obtenerCamposRecepcionParaDetalle(
            $db,
            !empty($hoja['id_detalle_cotizacion']) ? (int)$hoja['id_detalle_cotizacion'] : null,
            !empty($hoja['id_producto']) ? (int)$hoja['id_producto'] : null
        );
        $camposRecepcion = $this->enriquecerCamposRecepcionHoja($infoFormato, $hoja);
        $camposTecnico = $infoFormato['campos_tecnico'];

        // Precargar mapa de recepción existente + fallback desde cotizacion_detalles si ya tenía valores
        $mapaRecepcion = construirMapaDatosRecepcionPorHoja($db, $idHoja);
        $mapaDesdeMatriz = [];
        if (!empty($hoja['id_detalle_cotizacion'])) {
            $stmtMat = $db->prepare("SELECT resultados_json FROM cotizacion_detalles WHERE id = :id LIMIT 1");
            $stmtMat->execute(['id' => (int)$hoja['id_detalle_cotizacion']]);
            $resJson = $stmtMat->fetchColumn();
            $decMat = is_string($resJson) ? (json_decode($resJson, true) ?: []) : [];
            $filasMat = isset($decMat['filas']) && is_array($decMat['filas']) ? $decMat['filas'] : (isset($decMat[0]) && is_array($decMat[0]) ? $decMat : []);
            foreach ($filasMat as $fm) {
                if (!is_array($fm)) continue;
                $codF = strtoupper(trim((string)($fm['Código laboratorio'] ?? ($fm['Codigo laboratorio'] ?? ($fm['codigo_lab'] ?? '')))));
                if ($codF !== '') {
                    $mapaDesdeMatriz[$codF] = $fm;
                }
            }
        }

        $detalles = $pltModelo->obtenerDetallesPorHoja($idHoja);
        $muestrasSalida = [];
        if (!empty($detalles)) {
            foreach ($detalles as $d) {
                $cod = trim((string)($d['codigo_muestra'] ?? ''));
                $codKey = strtoupper($cod);
                $datosGuardados = $mapaRecepcion[$codKey] ?? [];
                if (empty($datosGuardados) && isset($mapaDesdeMatriz[$codKey])) {
                    foreach ($camposRecepcion as $cr) {
                        $valMat = buscarValorEnFilaPHP($mapaDesdeMatriz[$codKey], (string)$cr['nombre']);
                        if ($valMat !== '') {
                            $datosGuardados[$cr['nombre']] = $valMat;
                        }
                    }
                }
                if (empty($datosGuardados['Nombre muestra'])) {
                    $nomFallback = isset($mapaDesdeMatriz[$codKey]) ? buscarValorEnFilaPHP($mapaDesdeMatriz[$codKey], 'Nombre muestra') : '';
                    if ($nomFallback === '' && !empty($d['descripcion_ubicacion'])) {
                        $nomFallback = trim((string)$d['descripcion_ubicacion']);
                    }
                    if ($nomFallback !== '') {
                        $datosGuardados['Nombre muestra'] = $nomFallback;
                    }
                }
                $muestrasSalida[] = [
                    'codigo_muestra' => $cod,
                    'descripcion_ubicacion' => (string)($d['descripcion_ubicacion'] ?? ''),
                    'observacion_edad' => (string)($d['observacion_edad'] ?? ''),
                    'datos_recepcion' => $datosGuardados,
                ];
            }
        } else {
            $mArr = json_decode($hoja['muestras_json'] ?? '[]', true) ?: [];
            foreach ($mArr as $m) {
                $cod = trim((string)($m['nombre_muestra'] ?? ($m['codigo_muestra'] ?? '')));
                if ($cod === '') continue;
                $codKey = strtoupper($cod);
                $datosGuardados = $mapaRecepcion[$codKey] ?? [];
                if (empty($datosGuardados) && isset($mapaDesdeMatriz[$codKey])) {
                    foreach ($camposRecepcion as $cr) {
                        $valMat = buscarValorEnFilaPHP($mapaDesdeMatriz[$codKey], (string)$cr['nombre']);
                        if ($valMat !== '') {
                            $datosGuardados[$cr['nombre']] = $valMat;
                        }
                    }
                }
                if (empty($datosGuardados['Nombre muestra'])) {
                    $nomFallback = isset($mapaDesdeMatriz[$codKey]) ? buscarValorEnFilaPHP($mapaDesdeMatriz[$codKey], 'Nombre muestra') : '';
                    if ($nomFallback === '' && !empty($m['nombre_muestra_cliente'])) {
                        $nomFallback = trim((string)$m['nombre_muestra_cliente']);
                    } elseif ($nomFallback === '' && !empty($m['descripcion'])) {
                        $nomFallback = trim((string)$m['descripcion']);
                    }
                    if ($nomFallback !== '') {
                        $datosGuardados['Nombre muestra'] = $nomFallback;
                    }
                }
                $muestrasSalida[] = [
                    'codigo_muestra' => $cod,
                    'descripcion_ubicacion' => (string)($m['descripcion'] ?? ''),
                    'observacion_edad' => (string)($m['info_importante'] ?? ''),
                    'datos_recepcion' => $datosGuardados,
                ];
            }
        }

        $respuesta->enviarJson([
            'status' => 'success',
            'os' => [
                'id' => (int)$os['id'],
                'codigo_os' => $os['codigo_os'],
                'estado' => $os['estado'] ?? '',
                'solo_lectura' => in_array((string)($os['estado'] ?? ''), ['Finalizado', 'Archivado', 'Cerrado'], true),
            ],
            'hoja' => [
                'id' => $idHoja,
                'numero_registro' => $hoja['numero_registro'] ?? ('#' . $idHoja),
                'descripcion_ensayo' => $hoja['descripcion_ensayo'] ?? ($infoFormato['formato_nombre'] ?: 'Ensayo'),
                'formato_nombre' => $infoFormato['formato_nombre'] ?? '',
                'codigo_formato' => $infoFormato['codigo_formato'] ?? '',
            ],
            'campos_recepcion' => $camposRecepcion,
            'campos_tecnico' => $camposTecnico,
            'muestras' => $muestrasSalida,
            'todas_las_hojas' => $resumenHojas,
        ]);
    }

    /**
     * Guarda los datos de Recepción capturados en Órdenes de Servicio por cada RT-FM-13
     * y para cada una de sus N muestras, sincronizando hacia la matriz técnica en Operaciones.
     */
    public function guardarDatosRecepcion(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        $esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

        $responderError = static function (string $mensaje, int $codigo = 400) use ($respuesta, $esAjax): void {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => $mensaje], $codigo);
                return;
            }
            $_SESSION['error'] = $mensaje;
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
        };

        if (!$peticion->esPost()) {
            $responderError('Método no permitido.', 405);
            return;
        }

        $datos = $peticion->obtenerDatos();
        if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
            $responderError('Token CSRF inválido o sesión expirada.', 403);
            return;
        }

        $idHoja = (int)($datos['id_hoja'] ?? 0);
        if ($idHoja <= 0) {
            $responderError('Hoja RT-FM-13 inválida.');
            return;
        }

        $modelo = new OperacionModelo();
        $hoja = $modelo->obtenerHojaSolicitudPorId($idHoja);
        if (!$hoja) {
            $responderError('Hoja RT-FM-13 no encontrada.', 404);
            return;
        }

        $idOS = (int)$hoja['id_os'];
        $os = $modelo->obtenerOSPorId($idOS);
        $estadoOS = (string)($os['estado'] ?? '');
        if (!$os || in_array($estadoOS, ['Finalizado', 'Archivado', 'Cerrado'], true)) {
            $responderError('La Orden de Servicio está cerrada o archivada y no admite modificaciones.', 409);
            return;
        }
        if (!esOrdenServicioAprobada($estadoOS)) {
            $responderError('La Orden de Servicio debe estar aprobada para registrar datos de recepción.', 409);
            return;
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        \Cycsa\Modulos\Configuracion\Servicios\MigradorServicio::asegurarTablas($db);

        $infoFormato = obtenerCamposRecepcionParaDetalle(
            $db,
            !empty($hoja['id_detalle_cotizacion']) ? (int)$hoja['id_detalle_cotizacion'] : null,
            !empty($hoja['id_producto']) ? (int)$hoja['id_producto'] : null
        );
        $camposRecepcion = $this->enriquecerCamposRecepcionHoja($infoFormato, $hoja);

        $entradaMuestras = [];
        if (!empty($datos['muestras_recepcion_json']) && is_string($datos['muestras_recepcion_json'])) {
            $decIn = json_decode($datos['muestras_recepcion_json'], true);
            if (is_array($decIn)) {
                $entradaMuestras = $decIn;
            }
        } elseif (!empty($datos['recepcion']) && is_array($datos['recepcion'])) {
            $entradaMuestras = $datos['recepcion'];
        }

        $datosPorCodigo = [];
        foreach ($entradaMuestras as $codMuestra => $valoresMuestra) {
            if (!is_array($valoresMuestra)) continue;
            $codKey = strtoupper(trim((string)$codMuestra));
            if ($codKey === '') continue;
            $datosPorCodigo[$codKey] = normalizarYCompletarDatosRecepcionMuestra($valoresMuestra, $camposRecepcion);
        }

        $transaccionPropia = !$db->inTransaction();
        if ($transaccionPropia) {
            $db->beginTransaction();
        }
        try {
            // 1. Actualizar hojas_solicitud_detalles por código de muestra (incluyendo Nombre muestra en descripcion_ubicacion)
            $stmtUpdDet = $db->prepare("
                UPDATE hojas_solicitud_detalles
                SET datos_recepcion_json = :drec,
                    descripcion_ubicacion = COALESCE(NULLIF(:nom_muestra, ''), descripcion_ubicacion)
                WHERE id_hoja_solicitud = :hid AND UPPER(TRIM(codigo_muestra)) = :cod
            ");
            foreach ($datosPorCodigo as $codKey => $datosNorm) {
                $nomMuestraVal = trim((string)($datosNorm['Nombre muestra'] ?? ''));
                $stmtUpdDet->execute([
                    'drec' => !empty($datosNorm) ? json_encode($datosNorm, JSON_UNESCAPED_UNICODE) : null,
                    'nom_muestra' => $nomMuestraVal,
                    'hid' => $idHoja,
                    'cod' => $codKey,
                ]);
            }

            // 2. Actualizar muestras_json de la hoja conservando resto de metadatos y sincronizando Nombre muestra
            $muestrasArr = json_decode($hoja['muestras_json'] ?? '[]', true) ?: [];
            foreach ($muestrasArr as $idx => $mItem) {
                $codKey = strtoupper(trim((string)($mItem['nombre_muestra'] ?? ($mItem['codigo_muestra'] ?? ''))));
                if ($codKey !== '' && array_key_exists($codKey, $datosPorCodigo)) {
                    if (!empty($datosPorCodigo[$codKey])) {
                        $muestrasArr[$idx]['datos_recepcion'] = $datosPorCodigo[$codKey];
                        if (!empty($datosPorCodigo[$codKey]['Nombre muestra'])) {
                            $muestrasArr[$idx]['descripcion'] = $datosPorCodigo[$codKey]['Nombre muestra'];
                            $muestrasArr[$idx]['nombre_muestra_cliente'] = $datosPorCodigo[$codKey]['Nombre muestra'];
                        }
                    } else {
                        unset($muestrasArr[$idx]['datos_recepcion']);
                    }
                }
            }
            $db->prepare("UPDATE hojas_solicitud SET muestras_json = :mjson WHERE id = :hid")
               ->execute(['mjson' => json_encode($muestrasArr, JSON_UNESCAPED_UNICODE), 'hid' => $idHoja]);

            // 3. Sincronizar con matriz técnica en Operaciones / Laboratorio si ya existe
            sincronizarDatosRecepcionEnMatriz($db, $idHoja, $datosPorCodigo);

            // 3b. Sincronizar 'Nombre muestra' (y campos de compactación adicionales) hacia filas ya creadas de cotizacion_detalles.resultados_json
            $idDetalleSync = (int)($hoja['id_detalle_cotizacion'] ?? 0);
            if ($idDetalleSync > 0) {
                $stmtMatSync = $db->prepare("SELECT resultados_json FROM cotizacion_detalles WHERE id = :id LIMIT 1");
                $stmtMatSync->execute(['id' => $idDetalleSync]);
                $resJsonSync = $stmtMatSync->fetchColumn();
                $decSync = is_string($resJsonSync) ? (json_decode($resJsonSync, true) ?: []) : [];
                $filasSync = isset($decSync['filas']) && is_array($decSync['filas']) ? $decSync['filas'] : [];
                if (!empty($filasSync)) {
                    $cambiosMat = false;
                    foreach ($filasSync as $idxF => $filaSync) {
                        if (!is_array($filaSync)) continue;
                        $codF = strtoupper(trim((string)($filaSync['Código laboratorio'] ?? ($filaSync['Codigo laboratorio'] ?? ($filaSync['codigo_lab'] ?? '')))));
                        if ($codF !== '' && !empty($datosPorCodigo[$codF])) {
                            if (!empty($datosPorCodigo[$codF]['Nombre muestra'])) {
                                $filasSync[$idxF]['Nombre muestra'] = $datosPorCodigo[$codF]['Nombre muestra'];
                                $cambiosMat = true;
                            }
                            foreach ($datosPorCodigo[$codF] as $kCol => $vCol) {
                                if ($vCol !== '' && array_key_exists($kCol, $filasSync[$idxF])) {
                                    $filasSync[$idxF][$kCol] = $vCol;
                                    $cambiosMat = true;
                                }
                            }
                        }
                    }
                    if ($cambiosMat) {
                        $decSync['filas'] = $filasSync;
                        $db->prepare("UPDATE cotizacion_detalles SET resultados_json = :json WHERE id = :id")
                           ->execute(['json' => json_encode($decSync, JSON_UNESCAPED_UNICODE), 'id' => $idDetalleSync]);
                    }
                }
            }

            if ($transaccionPropia && $db->inTransaction()) {
                $db->commit();
            }
        } catch (\Throwable $e) {
            if ($transaccionPropia && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error al guardar datos de recepción RT-FM-13 #' . $idHoja . ': ' . $e->getMessage());
            $responderError('No se pudieron guardar los datos de recepción: ' . $e->getMessage(), 500);
            return;
        }

        $muestrasCompletas = count(array_filter($datosPorCodigo, static fn($d) => !empty($d)));
        registrarEventoOS('hojas_servicio', 'datos_recepcion', "Datos de recepción actualizados en Hoja RT-FM-13 #{$idHoja} ({$muestrasCompletas} muestra(s))", $idOS, [
            'entidad_tipo' => 'rtfm13',
            'entidad_id' => $idHoja,
            'id_referencia' => $idHoja,
        ]);

        if ($esAjax) {
            $respuesta->enviarJson([
                'status' => 'success',
                'message' => 'Datos de recepción guardados y sincronizados con Operaciones correctamente.',
                'id_hoja' => $idHoja,
                'muestras_con_datos' => $muestrasCompletas,
                'datos_por_codigo' => $datosPorCodigo,
            ]);
            return;
        }

        $_SESSION['exito'] = 'Datos de recepción guardados y sincronizados con Operaciones para la Hoja RT-FM-13.';
        $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . $idOS);
    }
}
