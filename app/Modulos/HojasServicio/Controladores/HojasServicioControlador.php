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
        if (!tienePermiso('recepcion', $accion) && !tienePermiso('ordenes_servicio', $accion) && !tienePermiso('operaciones', $accion)) {
            $_SESSION['error'] = 'No tiene permisos para realizar esta acción en Hojas de Servicio / Recepción.';
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
            $idDetalleCabeceraNueva = isset($_GET['id_detalle_cotizacion']) && (int)$_GET['id_detalle_cotizacion'] > 0
                ? (int)$_GET['id_detalle_cotizacion']
                : 0;
            $hoja = array_merge([
                'id' => 0,
                'id_os' => $idOS,
                'id_detalle_cotizacion' => $idDetalleCabeceraNueva,
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

        // Información de período/vigencia actual, ampliaciones históricas, productos actuales y catálogo para extensión desde Hoja de Servicio
        $vigenciaContrato = $osCompleta['vigencia_contrato'] ?? (function_exists('calcularVigenciaContratoOS') ? calcularVigenciaContratoOS($osCompleta ?: $os) : []);
        $periodoActual = [
            'es_multimes' => !empty($osCompleta['es_multimes']) ? 1 : 0,
            'tipo_contrato' => (string)($osCompleta['tipo_contrato'] ?? ($os['tipo_contrato'] ?? 'Puntual')),
            'duracion_meses' => !empty($osCompleta['duracion_meses']) ? (int)$osCompleta['duracion_meses'] : ($vigenciaContrato['duracion_meses'] ?? null),
            'fecha_inicio' => $osCompleta['fecha_inicio'] ?? ($os['fecha_inicio'] ?? null),
            'fecha_fin' => $osCompleta['fecha_fin'] ?? ($os['fecha_fin'] ?? null),
            'periodicidad' => (string)($osCompleta['periodicidad'] ?? ($os['periodicidad'] ?? 'Mensual')),
            'vigencia' => $vigenciaContrato,
        ];

        $detallesCotizacionOS = [];
        $catalogoProductos = [];
        $ampliacionesHistorial = [];
        try {
            $db = Conexion::obtenerInstancia();
            $idCotOS = (int)($osCompleta['id_cotizacion'] ?? ($os['id_cotizacion'] ?? 0));
            if ($idCotOS > 0) {
                $stmtDetCot = $db->prepare("SELECT cd.id, cd.id_producto, cd.descripcion_ensayo, cd.cantidad, cd.precio_unitario, cd.subtotal, COALESCE(cd.unidad_medida, p.unidad_medida, 'Unidad') AS unidad_medida, COALESCE(cd.procedimiento, p.procedimiento_muestreo, p.norma_astm) AS procedimiento, COALESCE(cd.codigo_servicio, p.codigo_servicio) AS codigo_servicio FROM cotizacion_detalles cd LEFT JOIN productos p ON cd.id_producto = p.id WHERE cd.id_cotizacion = :id_cot ORDER BY cd.id ASC");
                $stmtDetCot->execute(['id_cot' => $idCotOS]);
                $detallesCotizacionOS = $stmtDetCot->fetchAll(PDO::FETCH_ASSOC) ?: [];
            }

            $stmtCat = $db->query("SELECT id, nombre_comercial, ensayo_servicio, codigo_servicio, norma_astm, procedimiento_muestreo, COALESCE(unidad_medida, 'Unidad') AS unidad_medida, COALESCE(precio, 0) AS precio FROM productos WHERE activo = 1 ORDER BY COALESCE(NULLIF(nombre_comercial, ''), ensayo_servicio) ASC");
            $catalogoProductos = $stmtCat ? ($stmtCat->fetchAll(PDO::FETCH_ASSOC) ?: []) : [];

            $stmtAmp = $db->prepare("SELECT id, duracion_meses_anterior, duracion_meses_nueva, fecha_fin_anterior, fecha_fin_nueva, periodicidad, productos_cambio_json, motivo, usuario_nombre, created_at FROM ordenes_servicio_ampliaciones WHERE id_os = :id_os ORDER BY id DESC LIMIT 15");
            $stmtAmp->execute(['id_os' => $idOS]);
            $ampliacionesHistorial = $stmtAmp->fetchAll(PDO::FETCH_ASSOC) ?: [];
        } catch (\Throwable $e) {
            error_log('hojaSolicitudDatosAjax extras periodo/catalogo: ' . $e->getMessage());
        }

        $respuesta->enviarJson([
            'status' => 'success',
            'producto_hoja_id' => $idProductoHoja,
            'plantilla_sugerida_hoja' => $pltSugeridaHoja,
            'es_compactacion_hoja' => $esCompactacionHoja,
            'alerta_alcance_mensual' => $alertaAlcanceMensual,
            'periodo_actual' => $periodoActual,
            'vigencia_contrato' => $vigenciaContrato,
            'ampliaciones_historial' => $ampliacionesHistorial,
            'detalles_cotizacion_os' => $detallesCotizacionOS,
            'catalogo_productos' => $catalogoProductos,
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
                'tipo_contrato' => $os['tipo_contrato'] ?? 'Puntual',
                'es_multimes' => (int)($os['es_multimes'] ?? 0),
                'duracion_meses' => $os['duracion_meses'] ?? null,
                'fecha_inicio' => $os['fecha_inicio'] ?? null,
                'fecha_fin' => $os['fecha_fin'] ?? null,
                'periodicidad' => $os['periodicidad'] ?? 'Mensual'
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

    /**
     * Endpoint AJAX POST /hojas-servicio/ampliar-periodo-productos
     * Permite desde la Hoja de Servicio (RT-FM-13) ampliar el período de vigencia de la O/S
     * y simultáneamente modificar cantidades/descripciones de productos existentes o agregar nuevos productos.
     */
    public function ampliarPeriodoYProductos(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('recepcion', 'crear_editar') && !tienePermiso('ordenes_servicio', 'crear_editar') && !tienePermiso('operaciones', 'crear_editar')) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'No tiene permisos para ampliar el período o modificar productos.'], 403);
            return;
        }
        if (!$peticion->esPost()) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Método no permitido.'], 405);
            return;
        }

        $rawBody = file_get_contents('php://input');
        $jsonBody = ($rawBody !== false && trim($rawBody) !== '') ? json_decode($rawBody, true) : null;
        $datos = is_array($jsonBody) ? $jsonBody : $peticion->obtenerDatos();

        $csrf = (string)($datos['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? ''));
        if ($csrf === '' || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrf)) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Token de seguridad inválido o sesión expirada.'], 403);
            return;
        }

        $idOS = (int)($datos['id_os'] ?? 0);
        if ($idOS <= 0) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Orden de Servicio inválida.'], 422);
            return;
        }

        $res = $this->ejecutarAmpliacionPeriodoYProductos(
            $idOS,
            $datos,
            (int)($_SESSION['usuario_id'] ?? 0),
            (string)($_SESSION['usuario_nombre'] ?? 'Usuario')
        );

        if (empty($res['ok'])) {
            $respuesta->enviarJson([
                'status' => 'error',
                'message' => $res['message'] ?? 'No se pudo completar la ampliación.'
            ], (int)($res['http_code'] ?? 400));
            return;
        }

        $respuesta->enviarJson([
            'status' => 'success',
            'message' => $res['message'] ?? 'Período y productos actualizados correctamente.',
            'periodo_actual' => $res['periodo_actual'] ?? [],
            'vigencia_contrato' => $res['vigencia_contrato'] ?? [],
            'resumen_cambios' => $res['resumen_cambios'] ?? [],
        ]);
    }

    /**
     * Lógica transaccional reutilizable (y testeable) para ampliar el período de una O/S
     * y modificar/agregar productos en su cotización vinculada desde Hoja de Servicio.
     *
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    public function ejecutarAmpliacionPeriodoYProductos(
        int $idOS,
        array $payload,
        int $usuarioId = 0,
        string $usuarioNombre = 'Sistema'
    ): array {
        $db = Conexion::obtenerInstancia();
        $osModelo = new \Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo();
        $os = $osModelo->obtenerPorId($idOS);
        if (!$os) {
            return ['ok' => false, 'http_code' => 404, 'message' => 'Orden de Servicio no encontrada.'];
        }
        if (in_array((string)($os['estado'] ?? ''), ['Finalizado', 'Archivado', 'Cerrado'], true)) {
            return ['ok' => false, 'http_code' => 422, 'message' => 'La Orden de Servicio está cerrada o archivada y no admite ampliaciones.'];
        }

        $idCotizacion = (int)($os['id_cotizacion'] ?? 0);
        $idHoja = !empty($payload['id_hoja']) ? (int)$payload['id_hoja'] : null;
        $mesesAdicionales = isset($payload['meses_adicionales']) ? max(0, (int)$payload['meses_adicionales']) : 0;
        $fechaFinSolicitada = trim((string)($payload['fecha_fin'] ?? ''));
        $fechaInicioActual = !empty($os['fecha_inicio']) ? (string)$os['fecha_inicio'] : date('Y-m-d');
        $fechaFinAnterior = !empty($os['fecha_fin']) ? (string)$os['fecha_fin'] : null;
        $duracionAnterior = !empty($os['duracion_meses']) ? (int)$os['duracion_meses'] : null;
        $periodicidad = trim((string)($payload['periodicidad'] ?? ($os['periodicidad'] ?? 'Mensual')));
        if (!in_array($periodicidad, ['Mensual', 'Quincenal', 'Bimestral', 'Trimestral', 'Semestral'], true)) {
            $periodicidad = 'Mensual';
        }
        $motivo = trim((string)($payload['motivo'] ?? ''));

        // Calcular nueva fecha_fin y nueva duración
        $fechaFinNueva = $fechaFinAnterior;
        $duracionNueva = $duracionAnterior;
        $esMultimesNuevo = !empty($os['es_multimes']) ? 1 : 0;

        if ($fechaFinSolicitada !== '' && preg_match('/^\d{4}-\d{2}-\d{2}$/', $fechaFinSolicitada) === 1) {
            $fechaFinNueva = $fechaFinSolicitada;
            $esMultimesNuevo = 1;
            if ($mesesAdicionales > 0) {
                $duracionNueva = max(1, (int)($duracionAnterior ?? 0) + $mesesAdicionales);
            } elseif (function_exists('calcularVigenciaContratoOS')) {
                $vigCalc = calcularVigenciaContratoOS([
                    'es_multimes' => 1,
                    'tipo_contrato' => 'Marco',
                    'fecha_inicio' => $fechaInicioActual,
                    'fecha_fin' => $fechaFinNueva,
                ]);
                $duracionNueva = !empty($vigCalc['duracion_meses']) ? (int)$vigCalc['duracion_meses'] : max(1, (int)($duracionAnterior ?? 1));
            }
        } elseif ($mesesAdicionales > 0) {
            $esMultimesNuevo = 1;
            $baseFecha = ($fechaFinAnterior !== null && $fechaFinAnterior !== '') ? $fechaFinAnterior : $fechaInicioActual;
            $fechaFinNueva = function_exists('calcularFechaFinPorMesesOS')
                ? calcularFechaFinPorMesesOS($baseFecha, $mesesAdicionales)
                : date('Y-m-d', strtotime("+{$mesesAdicionales} months", strtotime($baseFecha)));
            $duracionNueva = max(1, (int)($duracionAnterior ?? 0) + $mesesAdicionales);
        }

        // Decodificar listas de productos a modificar y agregar
        $productosModificar = $payload['productos_modificar'] ?? [];
        if (is_string($productosModificar)) {
            $productosModificar = json_decode($productosModificar, true) ?: [];
        }
        $productosAgregar = $payload['productos_agregar'] ?? [];
        if (is_string($productosAgregar)) {
            $productosAgregar = json_decode($productosAgregar, true) ?: [];
        }

        $hayCambioPeriodo = ($esMultimesNuevo === 1 && ($fechaFinNueva !== $fechaFinAnterior || $duracionNueva !== $duracionAnterior || $mesesAdicionales > 0));
        $hayCambiosProductos = (!empty($productosModificar) || !empty($productosAgregar));

        if (!$hayCambioPeriodo && !$hayCambiosProductos) {
            return ['ok' => false, 'http_code' => 422, 'message' => 'Debe indicar una ampliación de período o al menos un cambio en productos.'];
        }

        $yaEnTransaccion = $db->inTransaction();
        $resumenCambios = [
            'modificados' => [],
            'agregados' => [],
        ];

        try {
            if (!$yaEnTransaccion) {
                $db->beginTransaction();
            }

            // 1. Actualizar período de la O/S (y reflejarlo en la cotización si aplica)
            if ($hayCambioPeriodo) {
                $okPeriodo = $osModelo->actualizarPeriodoContrato($idOS, [
                    'es_multimes' => 1,
                    'tipo_contrato' => 'Marco',
                    'duracion_meses' => $duracionNueva,
                    'fecha_inicio' => $fechaInicioActual,
                    'fecha_fin' => $fechaFinNueva,
                    'periodicidad' => $periodicidad,
                ]);
                if (!$okPeriodo) {
                    throw new \RuntimeException($osModelo->obtenerUltimoError() ?: 'No se pudo actualizar el período de la O/S.');
                }

                if ($idCotizacion > 0) {
                    try {
                        $stmtUpdCotPer = $db->prepare("UPDATE cotizaciones SET es_mensual = 1, duracion_meses = :dur, fecha_inicio_periodo = COALESCE(fecha_inicio_periodo, :fini), fecha_fin_periodo = :ffin, periodicidad = :per WHERE id = :id_cot");
                        $stmtUpdCotPer->execute([
                            'dur' => $duracionNueva,
                            'fini' => $fechaInicioActual,
                            'ffin' => $fechaFinNueva,
                            'per' => $periodicidad,
                            'id_cot' => $idCotizacion,
                        ]);
                    } catch (\Throwable $eCotPer) {
                        error_log('Sync periodo cotizaciones: ' . $eCotPer->getMessage());
                    }
                }
            }

            // 2. Modificar productos existentes en cotizacion_detalles
            $seModificoCotizacion = false;
            if ($idCotizacion > 0 && is_array($productosModificar) && $productosModificar !== []) {
                $stmtGetDet = $db->prepare("SELECT id, descripcion_ensayo, cantidad, precio_unitario FROM cotizacion_detalles WHERE id = :id AND id_cotizacion = :id_cot LIMIT 1");
                $stmtUpdDet = $db->prepare("UPDATE cotizacion_detalles SET descripcion_ensayo = :desc, cantidad = :cant, subtotal = :sub WHERE id = :id AND id_cotizacion = :id_cot");

                foreach ($productosModificar as $pm) {
                    if (!is_array($pm)) {
                        continue;
                    }
                    $idDet = (int)($pm['id_detalle'] ?? ($pm['id'] ?? 0));
                    if ($idDet <= 0) {
                        continue;
                    }
                    $stmtGetDet->execute(['id' => $idDet, 'id_cot' => $idCotizacion]);
                    $detActual = $stmtGetDet->fetch(PDO::FETCH_ASSOC);
                    if (!$detActual) {
                        continue;
                    }

                    $nuevaCant = isset($pm['cantidad']) ? max(0.01, (float)$pm['cantidad']) : (float)$detActual['cantidad'];
                    $nuevaDesc = (isset($pm['descripcion_ensayo']) && trim((string)$pm['descripcion_ensayo']) !== '')
                        ? trim((string)$pm['descripcion_ensayo'])
                        : (string)$detActual['descripcion_ensayo'];

                    $cantAnterior = (float)$detActual['cantidad'];
                    $descAnterior = (string)$detActual['descripcion_ensayo'];
                    if (abs($nuevaCant - $cantAnterior) < 0.0001 && $nuevaDesc === $descAnterior) {
                        continue;
                    }

                    $precioUnit = (float)$detActual['precio_unitario'];
                    $nuevoSub = round($nuevaCant * $precioUnit, 2);

                    $stmtUpdDet->execute([
                        'desc' => $nuevaDesc,
                        'cant' => $nuevaCant,
                        'sub' => $nuevoSub,
                        'id' => $idDet,
                        'id_cot' => $idCotizacion,
                    ]);
                    $seModificoCotizacion = true;
                    $resumenCambios['modificados'][] = [
                        'id_detalle' => $idDet,
                        'descripcion' => $nuevaDesc,
                        'cantidad_anterior' => $cantAnterior,
                        'cantidad_nueva' => $nuevaCant,
                    ];
                }
            }

            // 3. Agregar nuevos productos a cotizacion_detalles (y habilitarlos en whitelist si está activa)
            $nuevosIdsDetalle = [];
            if ($idCotizacion > 0 && is_array($productosAgregar) && $productosAgregar !== []) {
                $stmtProd = $db->prepare("SELECT id, nombre_comercial, ensayo_servicio, condiciones_muestra, procedimiento_muestreo, unidad_medida, codigo_servicio, norma_astm, observaciones, precio FROM productos WHERE id = :id LIMIT 1");
                $stmtInsDet = $db->prepare("INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, condiciones_muestra, procedimiento, unidad_medida, codigo_servicio, norma_astm, observaciones, cantidad, precio_unitario, subtotal) VALUES (:id_cot, :id_prod, :desc, :cond, :proc, :um, :cod, :norma, :obs, :cant, :precio, :sub)");

                foreach ($productosAgregar as $pa) {
                    if (!is_array($pa)) {
                        continue;
                    }
                    $idProd = !empty($pa['id_producto']) ? (int)$pa['id_producto'] : null;
                    $prodRow = null;
                    if ($idProd !== null && $idProd > 0) {
                        $stmtProd->execute(['id' => $idProd]);
                        $prodRow = $stmtProd->fetch(PDO::FETCH_ASSOC) ?: null;
                    }

                    $desc = trim((string)($pa['descripcion_ensayo'] ?? ''));
                    if ($desc === '' && $prodRow) {
                        $desc = trim((string)($prodRow['nombre_comercial'] ?: ($prodRow['ensayo_servicio'] ?? '')));
                    }
                    if ($desc === '') {
                        continue;
                    }

                    $cant = max(0.01, (float)($pa['cantidad'] ?? 1));
                    $precio = isset($pa['precio_unitario']) && $pa['precio_unitario'] !== ''
                        ? max(0.0, (float)$pa['precio_unitario'])
                        : (float)($prodRow['precio'] ?? 0);
                    $sub = round($cant * $precio, 2);

                    $stmtInsDet->execute([
                        'id_cot' => $idCotizacion,
                        'id_prod' => $prodRow ? (int)$prodRow['id'] : null,
                        'desc' => $desc,
                        'cond' => $pa['condiciones_muestra'] ?? ($prodRow['condiciones_muestra'] ?? null),
                        'proc' => $pa['procedimiento'] ?? ($prodRow['procedimiento_muestreo'] ?? ($prodRow['norma_astm'] ?? null)),
                        'um' => !empty($pa['unidad_medida']) ? trim((string)$pa['unidad_medida']) : (!empty($prodRow['unidad_medida']) ? (string)$prodRow['unidad_medida'] : 'Unidad'),
                        'cod' => $pa['codigo_servicio'] ?? ($prodRow['codigo_servicio'] ?? null),
                        'norma' => $pa['norma_astm'] ?? ($prodRow['norma_astm'] ?? null),
                        'obs' => $prodRow['observaciones'] ?? null,
                        'cant' => $cant,
                        'precio' => $precio,
                        'sub' => $sub,
                    ]);
                    $nuevoIdDet = (int)$db->lastInsertId();
                    if ($nuevoIdDet > 0) {
                        $nuevosIdsDetalle[] = $nuevoIdDet;
                    }
                    $seModificoCotizacion = true;
                    $resumenCambios['agregados'][] = [
                        'id_detalle' => $nuevoIdDet,
                        'id_producto' => $prodRow ? (int)$prodRow['id'] : null,
                        'descripcion' => $desc,
                        'cantidad' => $cant,
                        'precio_unitario' => $precio,
                    ];
                }
            }

            // 4. Si hay whitelist de ensayos permitidos activa en esta O/S, habilitar automáticamente los productos recién agregados
            if ($nuevosIdsDetalle !== []) {
                try {
                    $idsPermitidosActuales = $osModelo->obtenerIdsPermitidos($idOS);
                    if (is_array($idsPermitidosActuales) && $idsPermitidosActuales !== []) {
                        $stmtInsPerm = $db->prepare("INSERT IGNORE INTO orden_servicio_ensayos_permitidos (id_os, id_cotizacion_detalle, creado_por, creado_en) VALUES (:id_os, :id_det, :uid, NOW())");
                        foreach ($nuevosIdsDetalle as $nid) {
                            $stmtInsPerm->execute(['id_os' => $idOS, 'id_det' => $nid, 'uid' => $usuarioId > 0 ? $usuarioId : null]);
                        }
                    }
                } catch (\Throwable $ePerm) {
                    error_log('Auto-habilitar nuevos productos en whitelist O/S: ' . $ePerm->getMessage());
                }
            }

            // 5. Recalcular totales de la cotización si hubo cambios en productos
            if ($seModificoCotizacion && $idCotizacion > 0) {
                $stmtSum = $db->prepare("SELECT COALESCE(SUM(subtotal), 0) FROM cotizacion_detalles WHERE id_cotizacion = :id_cot");
                $stmtSum->execute(['id_cot' => $idCotizacion]);
                $nuevoSubtotal = round((float)$stmtSum->fetchColumn(), 2);

                $stmtCotMeta = $db->prepare("SELECT descuento, exonerado FROM cotizaciones WHERE id = :id_cot LIMIT 1");
                $stmtCotMeta->execute(['id_cot' => $idCotizacion]);
                $cotMeta = $stmtCotMeta->fetch(PDO::FETCH_ASSOC) ?: ['descuento' => 0, 'exonerado' => 0];
                $descVal = (float)($cotMeta['descuento'] ?? 0);
                $exonerado = !empty($cotMeta['exonerado']) ? 1 : 0;
                $baseImp = max(0.0, $nuevoSubtotal - $descVal);
                $nuevoImpuesto = $exonerado ? 0.00 : round($baseImp * 0.15, 2);
                $nuevoTotal = round($baseImp + $nuevoImpuesto, 2);

                $stmtUpdTot = $db->prepare("UPDATE cotizaciones SET subtotal = :sub, impuesto = :imp, total = :tot WHERE id = :id_cot");
                $stmtUpdTot->execute([
                    'sub' => $nuevoSubtotal,
                    'imp' => $nuevoImpuesto,
                    'tot' => $nuevoTotal,
                    'id_cot' => $idCotizacion,
                ]);
            }

            // 6. Registrar auditoría en ordenes_servicio_ampliaciones
            try {
                $stmtAmp = $db->prepare("INSERT INTO ordenes_servicio_ampliaciones (id_os, id_hoja, duracion_meses_anterior, duracion_meses_nueva, fecha_fin_anterior, fecha_fin_nueva, periodicidad, productos_cambio_json, motivo, usuario_id, usuario_nombre) VALUES (:id_os, :id_hoja, :dur_ant, :dur_nva, :ffin_ant, :ffin_nva, :per, :prods_json, :motivo, :uid, :unom)");
                $stmtAmp->execute([
                    'id_os' => $idOS,
                    'id_hoja' => $idHoja,
                    'dur_ant' => $duracionAnterior,
                    'dur_nva' => $duracionNueva,
                    'ffin_ant' => $fechaFinAnterior,
                    'ffin_nva' => $fechaFinNueva,
                    'per' => $periodicidad,
                    'prods_json' => json_encode($resumenCambios, JSON_UNESCAPED_UNICODE),
                    'motivo' => $motivo !== '' ? $motivo : null,
                    'uid' => $usuarioId > 0 ? $usuarioId : null,
                    'unom' => $usuarioNombre,
                ]);
            } catch (\Throwable $eAmp) {
                error_log('Registro ordenes_servicio_ampliaciones: ' . $eAmp->getMessage());
            }

            if (!$yaEnTransaccion && $db->inTransaction()) {
                $db->commit();
            }

            $osActualizada = $osModelo->obtenerPorId($idOS);
            $vigActualizada = $osActualizada['vigencia_contrato'] ?? (function_exists('calcularVigenciaContratoOS') ? calcularVigenciaContratoOS($osActualizada ?: []) : []);

            if (function_exists('registrarBitacora')) {
                $descBit = "Ampliación desde Hoja de Servicio en O/S " . ($os['codigo_os'] ?? "#{$idOS}");
                if ($hayCambioPeriodo) {
                    $descBit .= " · Período fin: " . ($fechaFinNueva ?? 'N/A') . " ({$duracionNueva} meses)";
                }
                if ($resumenCambios['modificados'] !== []) {
                    $descBit .= " · " . count($resumenCambios['modificados']) . " producto(s) modificado(s)";
                }
                if ($resumenCambios['agregados'] !== []) {
                    $descBit .= " · " . count($resumenCambios['agregados']) . " producto(s) agregado(s)";
                }
                registrarBitacora('hojas_servicio', 'ampliar_periodo_productos', $descBit, $idOS);
            }

            return [
                'ok' => true,
                'message' => 'Período y productos de la Orden de Servicio actualizados correctamente.',
                'periodo_actual' => [
                    'es_multimes' => !empty($osActualizada['es_multimes']) ? 1 : 0,
                    'tipo_contrato' => (string)($osActualizada['tipo_contrato'] ?? 'Marco'),
                    'duracion_meses' => !empty($osActualizada['duracion_meses']) ? (int)$osActualizada['duracion_meses'] : null,
                    'fecha_inicio' => $osActualizada['fecha_inicio'] ?? null,
                    'fecha_fin' => $osActualizada['fecha_fin'] ?? null,
                    'periodicidad' => (string)($osActualizada['periodicidad'] ?? 'Mensual'),
                    'vigencia' => $vigActualizada,
                ],
                'vigencia_contrato' => $vigActualizada,
                'resumen_cambios' => $resumenCambios,
            ];
        } catch (\Throwable $e) {
            if (!$yaEnTransaccion && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error en ejecutarAmpliacionPeriodoYProductos: ' . $e->getMessage());
            return ['ok' => false, 'http_code' => 500, 'message' => 'Error al aplicar la ampliación: ' . $e->getMessage()];
        }
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

            // Multi-producto RT-FM-13: id_detalle_cotizacion en cabecera es OPCIONAL.
            // Cada muestra puede definir su propio producto/ensayo (m_detalle_cotizacion[]) aquí o después en Datos de Recepción.
            $db = Conexion::obtenerInstancia();
            $idDetalleCotizacion = (int)($datos['id_detalle_cotizacion'] ?? 0);
            if ($idDetalleCotizacion <= 0 && $idHoja > 0) {
                $hojaExistente = $modelo->obtenerHojaSolicitudPorId($idHoja);
                if ($hojaExistente && !empty($hojaExistente['id_detalle_cotizacion'])) {
                    $idDetalleCotizacion = (int)$hojaExistente['id_detalle_cotizacion'];
                }
            }

            // Cargar mapa de productos/ensayos válidos de esta Orden de Servicio
            $stmtDetsOs = $db->prepare("
                SELECT cd.*, p.codigo_servicio, p.norma_astm, p.nombre_comercial, fe.archivo_markdown, fe.nombre AS formato_nombre
                FROM cotizacion_detalles cd
                LEFT JOIN productos p ON cd.id_producto = p.id
                LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                WHERE cd.id_cotizacion = :id_cot
                ORDER BY cd.id ASC
            ");
            $stmtDetsOs->execute(['id_cot' => (int)($os['id_cotizacion'] ?? 0)]);
            $detallesOSLista = $stmtDetsOs->fetchAll(PDO::FETCH_ASSOC) ?: [];
            $mapaDetallesOS = [];
            foreach ($detallesOSLista as $dOs) {
                $mapaDetallesOS[(int)$dOs['id']] = $dOs;
            }

            // Whitelist de la O/S
            $osModelo = new \Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo();
            $idsPermitidos = $osModelo->obtenerIdsPermitidos($idOS);

            $mDetalles = $datos['m_detalle_cotizacion'] ?? [];
            $detallesPorFila = [];
            $detallesUnicosMuestras = [];
            if (is_array($mDetalles)) {
                foreach ($mDetalles as $idxMd => $valMd) {
                    $detIdFila = (int)$valMd;
                    if ($detIdFila > 0) {
                        if (!isset($mapaDetallesOS[$detIdFila])) {
                            $_SESSION['error'] = 'Uno de los productos/ensayos asignados a las muestras no pertenece a esta Orden de Servicio.';
                            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                            return;
                        }
                        if ($idsPermitidos !== null && !in_array($detIdFila, $idsPermitidos, true)) {
                            $_SESSION['error'] = 'Uno de los ensayos asignados no está habilitado en la whitelist de esta Orden de Servicio.';
                            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                            return;
                        }
                        $detallesPorFila[$idxMd] = $detIdFila;
                        $detallesUnicosMuestras[$detIdFila] = true;
                    }
                }
            }

            $detalle = null;
            if ($idDetalleCotizacion > 0) {
                if (!isset($mapaDetallesOS[$idDetalleCotizacion])) {
                    $stmtDet = $db->prepare("SELECT cd.*, p.codigo_servicio, p.norma_astm, p.nombre_comercial, fe.archivo_markdown, fe.nombre AS formato_nombre 
                                             FROM cotizacion_detalles cd
                                             LEFT JOIN productos p ON cd.id_producto = p.id
                                             LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                                             WHERE cd.id = :id");
                    $stmtDet->execute(['id' => $idDetalleCotizacion]);
                    $detalle = $stmtDet->fetch(PDO::FETCH_ASSOC) ?: null;
                } else {
                    $detalle = $mapaDetallesOS[$idDetalleCotizacion];
                }
                if (!$detalle) {
                    $_SESSION['error'] = 'El producto o ensayo seleccionado no existe.';
                    $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                    return;
                }
                if ($idsPermitidos !== null && !in_array($idDetalleCotizacion, $idsPermitidos, true)) {
                    $_SESSION['error'] = 'El ensayo seleccionado no está habilitado en la whitelist de esta Orden de Servicio.';
                    $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                    return;
                }
            }

            // Si hay varios productos distintos en las muestras, la hoja es multi-producto (id_detalle_cotizacion = null en cabecera)
            if (count($detallesUnicosMuestras) > 1) {
                $idDetalleCotizacion = 0;
                $datos['id_detalle_cotizacion'] = null;
            } elseif ($idDetalleCotizacion <= 0) {
                $datos['id_detalle_cotizacion'] = null;
            } else {
                $datos['id_detalle_cotizacion'] = $idDetalleCotizacion;
            }

            // Producto de referencia para prefills y defaults cuando la hoja es multi-producto o sin asignar todavía
            $detalleRef = $detalle;
            if (!$detalleRef && !empty($detallesUnicosMuestras)) {
                $primerDetM = (int)array_key_first($detallesUnicosMuestras);
                $detalleRef = $mapaDetallesOS[$primerDetM] ?? null;
            }
            if (!$detalleRef && !empty($detallesOSLista)) {
                $detalleRef = $detallesOSLista[0];
            }

            $idProducto = (int)($detalleRef['id_producto'] ?? 0);
            $ensayoId = $idProducto > 0 ? $idProducto : 1;
            $datos['id_producto'] = ($idDetalleCotizacion > 0 && $idProducto > 0) ? $idProducto : null;
            $datos['ensayo_id'] = $ensayoId;

            // Prefill de flags y naturaleza en base al detalle seleccionado o los ensayos de la O/S
            $resFlags = $this->detectarParametrosYNaturaleza($detalle ? [$detalle] : $detallesOSLista);
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
                $detIdMuestra = !empty($detallesPorFila[$i]) ? (int)$detallesPorFila[$i] : ($idDetalleCotizacion > 0 ? $idDetalleCotizacion : null);
                $ensIdMuestra = !empty($mEnsayos[$i]) && (int)$mEnsayos[$i] > 0 ? (int)$mEnsayos[$i] : $ensayoId;
                if ($detIdMuestra && isset($mapaDetallesOS[$detIdMuestra]) && !empty($mapaDetallesOS[$detIdMuestra]['id_producto'])) {
                    $ensIdMuestra = (int)$mapaDetallesOS[$detIdMuestra]['id_producto'];
                }
                $identMuestras[] = [
                    'nombre_muestra' => $nom,
                    'descripcion' => trim($mDescripciones[$i] ?? ''),
                    'info_importante' => trim($mInfos[$i] ?? ''),
                    'id_detalle_cotizacion' => $detIdMuestra,
                    'ensayo_id' => $ensIdMuestra,
                    'plantilla_id' => (int)($mPlantillas[$i] ?? 0)
                ];
            }
            $datos['identificacion_muestras_json'] = json_encode($identMuestras);

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
                    $detIdMuestra = !empty($detallesPorFila[$i]) ? (int)$detallesPorFila[$i] : ($idDetalleCotizacion > 0 ? $idDetalleCotizacion : null);
                    $ensIdMuestra = !empty($mEnsayos[$i]) && (int)$mEnsayos[$i] > 0 ? (int)$mEnsayos[$i] : $ensayoId;
                    if ($detIdMuestra && isset($mapaDetallesOS[$detIdMuestra]) && !empty($mapaDetallesOS[$detIdMuestra]['id_producto'])) {
                        $ensIdMuestra = (int)$mapaDetallesOS[$detIdMuestra]['id_producto'];
                    }
                    $filasDetalles[] = [
                        'codigo_muestra' => $nom,
                        'descripcion_ubicacion' => trim($mDescripciones[$i] ?? ''),
                        'observacion_edad' => trim($mInfos[$i] ?? ''),
                        'id_detalle_cotizacion' => $detIdMuestra,
                        'ensayo_id' => $ensIdMuestra,
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

                // Sección 1 y 10: La RT-FM-13 recién guardada permanece en etapa 'recepcion_borrador' (recepcion_completa = 0)
                // y solo ingresa a Laboratorio cuando Recepción marca explícitamente "Recepción completa".
                $marcarCompletaExplicita = !empty($datos['recepcion_completa']) || !empty($datos['marcar_recepcion_completa']);
                if (
                    $marcarCompletaExplicita &&
                    $idHojaGuardada > 0 &&
                    !empty($muestrasSaneadas) &&
                    function_exists('esOrdenServicioAprobada') &&
                    esOrdenServicioAprobada((string)($os['estado'] ?? ''))
                ) {
                    try {
                        $db->prepare("
                            UPDATE hojas_solicitud
                            SET recepcion_completa = 1,
                                etapa_flujo = CASE WHEN etapa_flujo = 'recepcion_borrador' THEN 'recepcion_completa' ELSE etapa_flujo END,
                                fecha_recepcion_completa = COALESCE(fecha_recepcion_completa, NOW()),
                                recepcion_completada_por = COALESCE(recepcion_completada_por, :uid)
                            WHERE id = :id_hoja
                        ")->execute(['uid' => $usuarioId, 'id_hoja' => (int)$idHojaGuardada]);
                    } catch (\Throwable $e) {
                    }
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
        // Evaluar estrictamente sobre $infoFormato (del producto específico) para no mezclar con otros productos de una hoja multi-muestra
        $textoFormato = mb_strtolower(
            (string)($infoFormato['archivo_markdown'] ?? '') . ' ' .
            (string)($infoFormato['formato_nombre'] ?? '') . ' ' .
            (string)($infoFormato['codigo_formato'] ?? ''),
            'UTF-8'
        );
        if (trim($textoFormato) === '') {
            $textoFormato = mb_strtolower(
                (string)($hoja['descripcion_ensayo'] ?? '') . ' ' .
                (string)($hoja['codigo_servicio'] ?? '') . ' ' .
                (string)($hoja['nombre_comercial'] ?? ''),
                'UTF-8'
            );
        }

        $esCompactacion = false;
        foreach (['compactacion', 'compactación', 'densimetro', 'densímetro', 'cono_de_arena', 'cono de arena', 'reemplazo_de_agua', 'reemplazo de agua', 'd6938', 'd1556', 'd2167', 'rt-fm-22 b', 'rt-fm-22 c'] as $kw) {
            if (str_contains($textoFormato, $kw)) {
                $esCompactacion = true;
                break;
            }
        }

        if ($esCompactacion) {
            // Solo completar columnas de compactación faltantes si el esquema del formato no las trae ya
            // (evita duplicados como 'Humedad Óptima' vs 'Humedad Óptima ((% )(P/P))' o 'Compactación' vs 'Compactación ((%) (P/P))')
            $yaExisteSemantica = static function (string $claveObjetivo, array $claves): bool {
                if (isset($claves[$claveObjetivo])) {
                    return true;
                }
                foreach (array_keys($claves) as $kExist) {
                    if (str_starts_with($kExist, $claveObjetivo) || str_contains($kExist, $claveObjetivo)) {
                        return true;
                    }
                }
                return false;
            };

            $tieneMatrizCompactacionCompleta = count($base) >= 4;
            $columnasCompactacion = [
                ['nombre' => 'Capa (N°)', 'clave_sem' => 'capa', 'tipo' => 'text', 'step' => ''],
                ['nombre' => 'Espesor (cm)', 'clave_sem' => 'espesor', 'tipo' => 'number', 'step' => '0.01'],
                ['nombre' => 'Profundidad (cm)', 'clave_sem' => 'profundidad', 'tipo' => 'number', 'step' => '0.01'],
                ['nombre' => 'P.V.S Max (kg/m³)', 'clave_sem' => 'pvsmax', 'tipo' => 'number', 'step' => '0.01'],
                ['nombre' => 'Humedad Óptima', 'clave_sem' => 'humedadoptima', 'tipo' => 'number', 'step' => '0.01'],
                ['nombre' => 'P.V.S.Sitio (kg/m³)', 'clave_sem' => 'pvssitio', 'tipo' => 'number', 'step' => '0.01'],
                ['nombre' => 'Humedad sitio', 'clave_sem' => 'humedadsitio', 'tipo' => 'number', 'step' => '0.01'],
                ['nombre' => 'Compactación', 'clave_sem' => 'compactacion', 'tipo' => 'number', 'step' => '0.01'],
            ];
            if (!$tieneMatrizCompactacionCompleta) {
                $columnasCompactacion[] = ['nombre' => 'Fecha de muestreo', 'clave_sem' => 'fechademuestreo', 'tipo' => 'date', 'step' => ''];
            }
            foreach ($columnasCompactacion as $colC) {
                $kNorm = normalizarTextoClavePHP($colC['nombre']);
                if (!$yaExisteSemantica($colC['clave_sem'], $clavesExistentes) && !isset($clavesExistentes[$kNorm])) {
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
                'etapa_flujo' => $th['etapa_flujo'] ?? 'recepcion_borrador',
                'recepcion_completa' => (int)($th['recepcion_completa'] ?? 0),
                'fecha_recepcion_completa' => $th['fecha_recepcion_completa'] ?? null,
                'motivo_reapertura_recepcion' => $th['motivo_reapertura_recepcion'] ?? null,
                'autorizacion_urgente_supervisor' => (int)($th['autorizacion_urgente_supervisor'] ?? 0),
            ];
        }

        // Cargar todos los productos/ensayos habilitados de esta O/S para asignación por muestra en Atención al Cliente
        $osModeloRec = new \Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo();
        $idsPermitidosRec = $osModeloRec->obtenerIdsPermitidos($idOS);
        $stmtProdsOs = $db->prepare("
            SELECT cd.id, cd.id_producto, cd.descripcion_ensayo, cd.codigo_servicio, cd.norma_astm, cd.cantidad, cd.resultados_json
            FROM cotizacion_detalles cd
            WHERE cd.id_cotizacion = :id_cot
            ORDER BY cd.id ASC
        ");
        $stmtProdsOs->execute(['id_cot' => (int)($os['id_cotizacion'] ?? 0)]);
        $prodsOsRaw = $stmtProdsOs->fetchAll(PDO::FETCH_ASSOC) ?: [];
        $productosOS = [];
        $camposPorDetalle = [];
        $mapaDesdeMatriz = [];
        foreach ($prodsOsRaw as $prOs) {
            $pidDet = (int)$prOs['id'];
            if ($idsPermitidosRec !== null && !in_array($pidDet, $idsPermitidosRec, true)) {
                continue;
            }
            $infoPr = obtenerCamposRecepcionParaDetalle($db, $pidDet, !empty($prOs['id_producto']) ? (int)$prOs['id_producto'] : null);
            $camposPrEnriq = $this->enriquecerCamposRecepcionHoja($infoPr, $hoja);
            $camposPorDetalle[$pidDet] = $camposPrEnriq;
            $productosOS[] = [
                'id_detalle_cotizacion' => $pidDet,
                'id_producto' => (int)($prOs['id_producto'] ?? 0),
                'codigo_servicio' => (string)($prOs['codigo_servicio'] ?? ''),
                'descripcion_ensayo' => (string)($prOs['descripcion_ensayo'] ?? ($infoPr['formato_nombre'] ?: 'Ensayo')),
                'formato_nombre' => (string)($infoPr['formato_nombre'] ?? ''),
                'codigo_formato' => (string)($infoPr['codigo_formato'] ?? ''),
                'cantidad' => (float)($prOs['cantidad'] ?? 1),
                'campos_recepcion' => $camposPrEnriq,
                'campos_tecnico' => $infoPr['campos_tecnico'] ?? [],
            ];
            $decMat = is_string($prOs['resultados_json'] ?? null) ? (json_decode((string)$prOs['resultados_json'], true) ?: []) : [];
            $filasMat = isset($decMat['filas']) && is_array($decMat['filas']) ? $decMat['filas'] : (isset($decMat[0]) && is_array($decMat[0]) ? $decMat : []);
            foreach ($filasMat as $fm) {
                if (!is_array($fm)) continue;
                $codF = strtoupper(trim((string)($fm['Código laboratorio'] ?? ($fm['Codigo laboratorio'] ?? ($fm['codigo_lab'] ?? '')))));
                if ($codF !== '' && !isset($mapaDesdeMatriz[$codF])) {
                    $mapaDesdeMatriz[$codF] = $fm;
                }
            }
        }

        $infoFormato = obtenerCamposRecepcionParaDetalle(
            $db,
            !empty($hoja['id_detalle_cotizacion']) ? (int)$hoja['id_detalle_cotizacion'] : null,
            !empty($hoja['id_producto']) ? (int)$hoja['id_producto'] : null
        );
        $camposRecepcion = $this->enriquecerCamposRecepcionHoja($infoFormato, $hoja);
        $camposTecnico = $infoFormato['campos_tecnico'];

        // Si la hoja es multi-producto o sin producto fijo en cabecera, construir la unión de campos de los productos de la O/S
        $detalles = $pltModelo->obtenerDetallesPorHoja($idHoja);
        $detallesAsignadosEnHoja = [];
        foreach ($detalles as $dCheck) {
            $idDetAsig = (int)($dCheck['id_detalle_cotizacion'] ?? 0);
            if ($idDetAsig > 0) {
                $detallesAsignadosEnHoja[$idDetAsig] = true;
            }
        }
        if (empty($hoja['id_detalle_cotizacion']) || count($detallesAsignadosEnHoja) > 1 || (!empty($detallesAsignadosEnHoja) && !isset($detallesAsignadosEnHoja[(int)($hoja['id_detalle_cotizacion'] ?? 0)]))) {
            $fuentesUnion = !empty($detallesAsignadosEnHoja) ? array_keys($detallesAsignadosEnHoja) : array_keys($camposPorDetalle);
            $unionRec = [];
            $vistosRec = [];
            foreach ($fuentesUnion as $detIdU) {
                foreach (($camposPorDetalle[$detIdU] ?? []) as $cU) {
                    $kU = normalizarTextoClavePHP((string)($cU['nombre'] ?? ''));
                    if ($kU !== '' && !isset($vistosRec[$kU])) {
                        $vistosRec[$kU] = true;
                        $unionRec[] = $cU;
                    }
                }
            }
            if (!empty($unionRec)) {
                $camposRecepcion = $unionRec;
            }
            $unionTec = [];
            $vistosTec = [];
            foreach ($productosOS as $pOsU) {
                if (!empty($detallesAsignadosEnHoja) && !isset($detallesAsignadosEnHoja[$pOsU['id_detalle_cotizacion']])) {
                    continue;
                }
                foreach (($pOsU['campos_tecnico'] ?? []) as $tU) {
                    $ktU = normalizarTextoClavePHP((string)$tU);
                    if ($ktU !== '' && !isset($vistosTec[$ktU])) {
                        $vistosTec[$ktU] = true;
                        $unionTec[] = $tU;
                    }
                }
            }
            if (!empty($unionTec)) {
                $camposTecnico = $unionTec;
            }
        }

        // Precargar mapa de recepción existente
        $mapaRecepcion = construirMapaDatosRecepcionPorHoja($db, $idHoja);

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
                $idDetMuestraOut = (int)($d['id_detalle_cotizacion'] ?? 0);
                if ($idDetMuestraOut <= 0 && !empty($hoja['id_detalle_cotizacion'])) {
                    $idDetMuestraOut = (int)$hoja['id_detalle_cotizacion'];
                }
                $muestrasSalida[] = [
                    'codigo_muestra' => $cod,
                    'descripcion_ubicacion' => (string)($d['descripcion_ubicacion'] ?? ''),
                    'observacion_edad' => (string)($d['observacion_edad'] ?? ''),
                    'id_detalle_cotizacion' => $idDetMuestraOut,
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
                $idDetMuestraOut = (int)($m['id_detalle_cotizacion'] ?? 0);
                if ($idDetMuestraOut <= 0 && !empty($hoja['id_detalle_cotizacion'])) {
                    $idDetMuestraOut = (int)$hoja['id_detalle_cotizacion'];
                }
                $muestrasSalida[] = [
                    'codigo_muestra' => $cod,
                    'descripcion_ubicacion' => (string)($m['descripcion'] ?? ''),
                    'observacion_edad' => (string)($m['info_importante'] ?? ''),
                    'id_detalle_cotizacion' => $idDetMuestraOut,
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
                'descripcion_ensayo' => $hoja['descripcion_ensayo'] ?? (!empty($hoja['id_detalle_cotizacion']) ? ($infoFormato['formato_nombre'] ?: 'Ensayo') : 'Multi-producto / Por asignar'),
                'formato_nombre' => $infoFormato['formato_nombre'] ?? '',
                'codigo_formato' => $infoFormato['codigo_formato'] ?? '',
                'etapa_flujo' => $hoja['etapa_flujo'] ?? 'recepcion_borrador',
                'recepcion_completa' => (int)($hoja['recepcion_completa'] ?? 0),
                'fecha_recepcion_completa' => $hoja['fecha_recepcion_completa'] ?? null,
                'motivo_reapertura_recepcion' => $hoja['motivo_reapertura_recepcion'] ?? null,
                'autorizacion_urgente_supervisor' => (int)($hoja['autorizacion_urgente_supervisor'] ?? 0),
            ],
            'productos_os' => $productosOS,
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

        // Cargar productos válidos de la O/S para validar y resolver asignación por muestra
        $stmtProdsVal = $db->prepare("SELECT id, id_producto FROM cotizacion_detalles WHERE id_cotizacion = :id_cot");
        $stmtProdsVal->execute(['id_cot' => (int)($os['id_cotizacion'] ?? 0)]);
        $mapaDetAProd = [];
        foreach ($stmtProdsVal->fetchAll(PDO::FETCH_ASSOC) as $pv) {
            $mapaDetAProd[(int)$pv['id']] = (int)($pv['id_producto'] ?? 0);
        }

        // Destino de producto/ensayo por código de muestra (Atención al Cliente)
        $destinosPorCodigo = [];
        if (!empty($datos['muestras_destino_json']) && is_string($datos['muestras_destino_json'])) {
            $decDest = json_decode($datos['muestras_destino_json'], true);
            if (is_array($decDest)) {
                foreach ($decDest as $codD => $idDetVal) {
                    $codKeyD = strtoupper(trim((string)$codD));
                    $idDetInt = (int)$idDetVal;
                    if ($codKeyD === '') continue;
                    if ($idDetInt > 0 && isset($mapaDetAProd[$idDetInt])) {
                        $destinosPorCodigo[$codKeyD] = $idDetInt;
                    } elseif ($idDetInt <= 0) {
                        $destinosPorCodigo[$codKeyD] = 0;
                    }
                }
            }
        }

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

        // Si hay destinos por muestra, normalizar cada muestra contra los campos de SU producto asignado
        $cacheCamposDet = [];
        $datosPorCodigo = [];
        foreach ($entradaMuestras as $codMuestra => $valoresMuestra) {
            if (!is_array($valoresMuestra)) continue;
            $codKey = strtoupper(trim((string)$codMuestra));
            if ($codKey === '') continue;
            $camposM = $camposRecepcion;
            if (!empty($destinosPorCodigo[$codKey])) {
                $detIdM = (int)$destinosPorCodigo[$codKey];
                if (!isset($cacheCamposDet[$detIdM])) {
                    $infoM = obtenerCamposRecepcionParaDetalle($db, $detIdM, $mapaDetAProd[$detIdM] ?? null);
                    $cacheCamposDet[$detIdM] = $this->enriquecerCamposRecepcionHoja($infoM, $hoja);
                }
                if (!empty($cacheCamposDet[$detIdM])) {
                    $camposM = $cacheCamposDet[$detIdM];
                }
            }
            $datosPorCodigo[$codKey] = normalizarYCompletarDatosRecepcionMuestra($valoresMuestra, $camposM);
        }

        $tieneColDetCotHsd = false;
        try {
            $chkColHsd = $db->query("SHOW COLUMNS FROM hojas_solicitud_detalles LIKE 'id_detalle_cotizacion'");
            $tieneColDetCotHsd = (bool)($chkColHsd && $chkColHsd->fetch(PDO::FETCH_ASSOC));
        } catch (\Throwable $e) {
            $tieneColDetCotHsd = false;
        }

        $transaccionPropia = !$db->inTransaction();
        if ($transaccionPropia) {
            $db->beginTransaction();
        }
        try {
            // 1. Actualizar hojas_solicitud_detalles por código de muestra (datos de recepción + producto destino)
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

            if ($tieneColDetCotHsd && !empty($destinosPorCodigo)) {
                // Solo asignar destino a muestras que aún no tenían producto definido en RT-FM-13 (no permitir mover las ya definidas)
                $stmtUpdDest = $db->prepare("
                    UPDATE hojas_solicitud_detalles
                    SET id_detalle_cotizacion = :id_det_cot,
                        ensayo_id = COALESCE(NULLIF(:ens_id, 0), ensayo_id)
                    WHERE id_hoja_solicitud = :hid
                      AND UPPER(TRIM(codigo_muestra)) = :cod
                      AND (id_detalle_cotizacion IS NULL OR id_detalle_cotizacion = 0)
                ");
                foreach ($destinosPorCodigo as $codKeyD => $idDetDest) {
                    if ($idDetDest <= 0) {
                        continue;
                    }
                    $ensIdDest = !empty($mapaDetAProd[$idDetDest]) ? (int)$mapaDetAProd[$idDetDest] : 0;
                    $stmtUpdDest->execute([
                        'id_det_cot' => $idDetDest,
                        'ens_id' => $ensIdDest,
                        'hid' => $idHoja,
                        'cod' => $codKeyD,
                    ]);
                }
            }

            // 2. Actualizar muestras_json de la hoja conservando resto de metadatos, destino (sin sobrescribir si ya venía definido en RT-FM-13) y sincronizando Nombre muestra
            $muestrasArr = json_decode($hoja['muestras_json'] ?? '[]', true) ?: [];
            foreach ($muestrasArr as $idx => $mItem) {
                $codKey = strtoupper(trim((string)($mItem['nombre_muestra'] ?? ($mItem['codigo_muestra'] ?? ''))));
                $detPrevJson = (int)($mItem['id_detalle_cotizacion'] ?? 0);
                if ($codKey !== '' && $detPrevJson <= 0 && !empty($destinosPorCodigo[$codKey])) {
                    $idDetD = (int)$destinosPorCodigo[$codKey];
                    $muestrasArr[$idx]['id_detalle_cotizacion'] = $idDetD;
                    if (!empty($mapaDetAProd[$idDetD])) {
                        $muestrasArr[$idx]['ensayo_id'] = (int)$mapaDetAProd[$idDetD];
                    }
                }
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

            // 3. Sincronizar con matrices técnicas en Operaciones / Laboratorio (soporta multi-producto)
            sincronizarDatosRecepcionEnMatriz($db, $idHoja, $datosPorCodigo);

            // 3b. Sincronizar 'Nombre muestra' (y campos adicionales) hacia todos los cotizacion_detalles destino
            $detallesParaSync = [];
            if (!empty($hoja['id_detalle_cotizacion'])) {
                $detallesParaSync[(int)$hoja['id_detalle_cotizacion']] = true;
            }
            foreach ($destinosPorCodigo as $idDetDestSync) {
                if ((int)$idDetDestSync > 0) {
                    $detallesParaSync[(int)$idDetDestSync] = true;
                }
            }
            foreach (array_keys($detallesParaSync) as $idDetalleSync) {
                $stmtMatSync = $db->prepare("SELECT resultados_json FROM cotizacion_detalles WHERE id = :id LIMIT 1");
                $stmtMatSync->execute(['id' => (int)$idDetalleSync]);
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
                           ->execute(['json' => json_encode($decSync, JSON_UNESCAPED_UNICODE), 'id' => (int)$idDetalleSync]);
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

        if (!empty($datos['marcar_recepcion_completa'])) {
            $this->procesarCierreRecepcionHoja($db, $modelo, $hoja, $os, $datos, $datosPorCodigo, $respuesta, $esAjax);
            return;
        }

        if ($esAjax) {
            $respuesta->enviarJson([
                'status' => 'success',
                'message' => 'Datos de recepción guardados y sincronizados con Operaciones correctamente.',
                'id_hoja' => $idHoja,
                'muestras_con_datos' => $muestrasCompletas,
                'datos_por_codigo' => $datosPorCodigo,
                'etapa_flujo' => $hoja['etapa_flujo'] ?? 'recepcion_borrador',
                'recepcion_completa' => (int)($hoja['recepcion_completa'] ?? 0),
            ]);
            return;
        }

        $_SESSION['exito'] = 'Datos de recepción guardados y sincronizados con Operaciones para la Hoja RT-FM-13.';
        $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . $idOS);
    }

    /**
     * Marca una Hoja RT-FM-13 con Recepción Completa (etapa_flujo = 'recepcion_completa'),
     * valida sus campos obligatorios (o excepción urgente de supervisor) e ingresa las muestras al Laboratorio.
     */
    public function marcarRecepcionCompleta(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('recepcion', 'crear_editar') && !tienePermiso('ordenes_servicio', 'crear_editar') && !tienePermiso('operaciones', 'crear_editar')) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'No tiene permisos para completar la recepción.'], 403);
            return;
        }

        $esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

        if (!$peticion->esPost()) {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Método no permitido.'], 405);
            } else {
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            }
            return;
        }

        $datos = $peticion->obtenerDatos();
        if (!empty($datos['muestras_recepcion_json']) || !empty($datos['recepcion']) || !empty($datos['muestras_destino_json'])) {
            $_POST['marcar_recepcion_completa'] = 1;
            $this->guardarDatosRecepcion($peticion, $respuesta);
            return;
        }

        if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido o sesión expirada.'], 403);
            } else {
                $_SESSION['error'] = 'Token CSRF inválido o sesión expirada.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            }
            return;
        }

        $idHoja = (int)($datos['id_hoja'] ?? 0);
        $modelo = new OperacionModelo();
        $hoja = $idHoja > 0 ? $modelo->obtenerHojaSolicitudPorId($idHoja) : null;
        if (!$hoja) {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Hoja RT-FM-13 no encontrada.'], 404);
            } else {
                $_SESSION['error'] = 'Hoja RT-FM-13 no encontrada.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            }
            return;
        }

        $idOS = (int)$hoja['id_os'];
        $os = $modelo->obtenerOSPorId($idOS);
        if (!$os || !esOrdenServicioAprobada((string)($os['estado'] ?? ''))) {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'La Orden de Servicio debe estar aprobada para completar la recepción.'], 409);
            } else {
                $_SESSION['error'] = 'La Orden de Servicio debe estar aprobada para completar la recepción.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . $idOS);
            }
            return;
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        \Cycsa\Modulos\Configuracion\Servicios\MigradorServicio::asegurarTablas($db);
        $this->procesarCierreRecepcionHoja($db, $modelo, $hoja, $os, $datos, [], $respuesta, $esAjax);
    }

    private function procesarCierreRecepcionHoja(
        PDO $db,
        OperacionModelo $modelo,
        array $hoja,
        array $os,
        array $datos,
        array $datosPorCodigo,
        Respuesta $respuesta,
        bool $esAjax
    ): void {
        $idHoja = (int)$hoja['id'];
        $idOS = (int)$os['id'];
        $usuarioId = (int)($_SESSION['usuario_id'] ?? 1);
        $rolUsuario = (int)($_SESSION['usuario_rol'] ?? 0);

        $validacion = validarCamposObligatoriosRecepcionHoja($db, $idHoja, $datosPorCodigo);
        $forzarUrgente = !empty($datos['forzar_urgente']);
        $motivoUrgente = trim((string)($datos['motivo_urgente'] ?? ''));

        if (!$validacion['completo']) {
            if (!$forzarUrgente) {
                $msgFaltantes = 'Faltan datos obligatorios de recepción antes de ingresar al laboratorio: ' . implode(', ', $validacion['faltantes']);
                if ($esAjax) {
                    $respuesta->enviarJson([
                        'status' => 'error',
                        'code' => 'RECEPCION_INCOMPLETA',
                        'message' => $msgFaltantes,
                        'faltantes' => $validacion['faltantes'],
                    ], 422);
                    return;
                }
                $_SESSION['error'] = $msgFaltantes;
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . $idOS . '&recepcion_hoja=' . $idHoja);
                return;
            }

            $esSupervisor = in_array($rolUsuario, [1, 2, 3], true)
                || tienePermiso('recepcion', 'aprobar')
                || tienePermiso('operaciones', 'aprobar');
            if (!$esSupervisor) {
                $msgPerm = 'Solo un supervisor puede autorizar el ingreso urgente con datos de recepción pendientes.';
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => $msgPerm], 403);
                    return;
                }
                $_SESSION['error'] = $msgPerm;
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . $idOS);
                return;
            }
            if (mb_strlen($motivoUrgente, 'UTF-8') < 5) {
                $msgMot = 'Debe indicar el motivo obligatorio de autorización urgente del supervisor (mínimo 5 caracteres).';
                if ($esAjax) {
                    $respuesta->enviarJson(['status' => 'error', 'message' => $msgMot], 422);
                    return;
                }
                $_SESSION['error'] = $msgMot;
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . $idOS);
                return;
            }
        }

        $stmtUpHoja = $db->prepare("
            UPDATE hojas_solicitud
            SET recepcion_completa = 1,
                etapa_flujo = CASE WHEN etapa_flujo = 'recepcion_borrador' THEN 'recepcion_completa' ELSE etapa_flujo END,
                fecha_recepcion_completa = COALESCE(fecha_recepcion_completa, NOW()),
                recepcion_completada_por = :uid,
                autorizacion_urgente_supervisor = :urg,
                motivo_urgente_supervisor = :murg
            WHERE id = :id_hoja
        ");
        $stmtUpHoja->execute([
            'uid' => $usuarioId,
            'urg' => (!$validacion['completo'] && $forzarUrgente) ? 1 : 0,
            'murg' => (!$validacion['completo'] && $forzarUrgente) ? $motivoUrgente : null,
            'id_hoja' => $idHoja,
        ]);

        $resIngreso = $modelo->ingresarMuestrasHojaALaboratorio($idOS, $idHoja, $usuarioId, false);
        $descBitacora = "Recepción marcada como COMPLETA en Hoja RT-FM-13 #{$idHoja}";
        if (!$validacion['completo'] && $forzarUrgente) {
            $descBitacora .= " (Autorización urgente de supervisor: {$motivoUrgente})";
        }
        registrarEventoOS('hojas_servicio', 'marcar_recepcion_completa', $descBitacora, $idOS, [
            'entidad_tipo' => 'rtfm13',
            'entidad_id' => $idHoja,
            'id_referencia' => $idHoja,
            'datos' => [
                'etapa_flujo' => 'recepcion_completa',
                'recepcion_completa' => 1,
                'forzar_urgente' => $forzarUrgente ? 1 : 0,
                'motivo_urgente' => $motivoUrgente,
                'ingreso_lab' => $resIngreso,
            ],
        ]);

        if ($esAjax) {
            $respuesta->enviarJson([
                'status' => 'success',
                'message' => 'Recepción marcada como completa. Las muestras han ingresado oficialmente a Laboratorio y Operaciones.',
                'id_hoja' => $idHoja,
                'etapa_flujo' => 'recepcion_completa',
                'recepcion_completa' => 1,
                'ingreso_laboratorio' => $resIngreso,
            ]);
            return;
        }

        $_SESSION['exito'] = 'Recepción completada e ingreso a Laboratorio registrado para la Hoja RT-FM-13 #' . $idHoja . '.';
        $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . $idOS);
    }

    /**
     * Reabre la recepción de una Hoja RT-FM-13 (solo Supervisor con nota obligatoria en bitácora).
     */
    public function reabrirRecepcion(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        $esAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH'])
            || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false);

        $rolUsuario = (int)($_SESSION['usuario_rol'] ?? 0);
        $esSupervisor = in_array($rolUsuario, [1, 2, 3], true)
            || tienePermiso('recepcion', 'aprobar')
            || tienePermiso('operaciones', 'aprobar');

        if (!$esSupervisor) {
            $msg = 'Solo un supervisor puede reabrir la recepción de una Hoja RT-FM-13.';
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => $msg], 403);
            } else {
                $_SESSION['error'] = $msg;
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            }
            return;
        }

        if (!$peticion->esPost()) {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Método no permitido.'], 405);
            } else {
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            }
            return;
        }

        $datos = $peticion->obtenerDatos();
        if (!isset($datos['csrf_token']) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$datos['csrf_token'])) {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido o sesión expirada.'], 403);
            } else {
                $_SESSION['error'] = 'Token CSRF inválido o sesión expirada.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            }
            return;
        }

        $idHoja = (int)($datos['id_hoja'] ?? 0);
        $motivo = trim((string)($datos['motivo_reapertura'] ?? ($datos['motivo'] ?? '')));
        if ($idHoja <= 0 || mb_strlen($motivo, 'UTF-8') < 5) {
            $msg = 'Debe indicar un motivo válido de reapertura (mínimo 5 caracteres).';
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => $msg], 422);
            } else {
                $_SESSION['error'] = $msg;
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            }
            return;
        }

        $modelo = new OperacionModelo();
        $hoja = $modelo->obtenerHojaSolicitudPorId($idHoja);
        if (!$hoja) {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Hoja RT-FM-13 no encontrada.'], 404);
            } else {
                $_SESSION['error'] = 'Hoja RT-FM-13 no encontrada.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            }
            return;
        }

        $idOS = (int)$hoja['id_os'];
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        \Cycsa\Modulos\Configuracion\Servicios\MigradorServicio::asegurarTablas($db);

        $stmt = $db->prepare("
            UPDATE hojas_solicitud
            SET recepcion_completa = 0,
                etapa_flujo = 'recepcion_borrador',
                motivo_reapertura_recepcion = :motivo
            WHERE id = :id_hoja
        ");
        $stmt->execute([
            'motivo' => $motivo,
            'id_hoja' => $idHoja,
        ]);

        registrarEventoOS('hojas_servicio', 'reabrir_recepcion', "Recepción reabierta por supervisor en Hoja RT-FM-13 #{$idHoja}. Motivo: {$motivo}", $idOS, [
            'entidad_tipo' => 'rtfm13',
            'entidad_id' => $idHoja,
            'id_referencia' => $idHoja,
            'datos' => [
                'etapa_flujo' => 'recepcion_borrador',
                'recepcion_completa' => 0,
                'motivo_reapertura' => $motivo,
            ],
        ]);

        if ($esAjax) {
            $respuesta->enviarJson([
                'status' => 'success',
                'message' => 'Recepción reabierta en modo borrador correctamente.',
                'id_hoja' => $idHoja,
                'etapa_flujo' => 'recepcion_borrador',
                'recepcion_completa' => 0,
                'motivo_reapertura_recepcion' => $motivo,
            ]);
            return;
        }

        $_SESSION['exito'] = 'Recepción reabierta para la Hoja RT-FM-13 #' . $idHoja . '.';
        $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . $idOS . '&recepcion_hoja=' . $idHoja);
    }
}
