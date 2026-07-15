<?php

namespace Cycsa\Modulos\Operaciones\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
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

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Obtener cotizaciones aprobadas listas para generar O/S, y las O/S activas
        $cotizacionesParaOS = $modelo->obtenerCotizacionesParaOS($busqueda);
        $ordenesActivas = $modelo->obtenerOSActivas($busqueda);
        $db = Conexion::obtenerInstancia();
        $stmtCxcCodes = $db->query("SELECT factura_numero, estado, saldo FROM cuentas_por_cobrar");
        $cxcRecords = $stmtCxcCodes->fetchAll(PDO::FETCH_ASSOC);
        $cxcMap = [];
        foreach ($cxcRecords as $r) {
            $cxcMap[$r['factura_numero']] = $r;
        }
        
        foreach ($ordenesActivas as &$o) {
            $o['items'] = $modelo->obtenerItemsOS((int)$o['id']);
            $o['hoja_solicitud'] = $modelo->obtenerHojaSolicitudPorOS((int)$o['id']);
        }
        
        $bitacora_logs = obtenerBitacoraModulo('operaciones');

        $this->renderizar('operaciones/vistas/index', [
            'titulo' => 'Operaciones LIMS - Cycsa',
            'cotizaciones' => $cotizacionesParaOS,
            'ordenes' => $ordenesActivas,
            'busqueda' => $busqueda,
            'tecnicos' => $modelo->obtenerTecnicosActivos(),
            'vehiculos' => $modelo->obtenerVehiculosActivos(),
            'cxcMap' => $cxcMap,
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

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
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
        
        // Mapear el estado de recepción a cada servicio
        foreach ($servicios as &$s) {
            $s['ya_recibido'] = false;
            $s['codigo_muestra'] = null;
            foreach ($itemsOS as $item) {
                if ((int)$item['id_detalle'] === (int)$s['id'] && !empty($item['codigo_muestra'])) {
                    $s['ya_recibido'] = true;
                    $s['codigo_muestra'] = $item['codigo_muestra'];
                    break;
                }
            }
        }
        unset($s);

        $hojaSolicitud = $modelo->obtenerHojaSolicitudPorOS($idOS);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('operaciones/vistas/recepcion', [
            'titulo' => 'Recepción de Muestras - LIMS',
            'os' => $os,
            'servicios' => $servicios,
            'idDetalle' => $idDetalle,
            'hoja_solicitud' => $hojaSolicitud,
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

        // Cargar todos los ensayos cotizados de esta O/S para poder capturar sus matrices
        $stmtItems = $db->prepare("SELECT cd.*, fe.archivo_markdown, fe.nombre AS formato_nombre
                                   FROM cotizacion_detalles cd
                                   LEFT JOIN productos p ON cd.id_producto = p.id
                                   LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                                   WHERE cd.id_cotizacion = :id_cot");
        $stmtItems->execute(['id_cot' => $lote['id_cotizacion']]);
        $itemsOS = $stmtItems->fetchAll(\PDO::FETCH_ASSOC);

        // Cargar el JSON del esquema de los formatos
        $schemaPath = __DIR__ . '/../../../datos_ensayos_markdown/formatos_schema.json';
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
            $stmtCount = $db->prepare("SELECT COUNT(*) FROM ensayo_edades WHERE id_lote = :id_lote AND identificador_especimen != 'Muestra' AND edad_dias > 0");
            $stmtCount->execute(['id_lote' => $idLote]);
            $esEnsayoEdades = ((int)$stmtCount->fetchColumn() > 0);

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
                $loteData = $stmtLote->fetch(PDO::FETCH_ASSOC);

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
                $filas = json_decode($detalle['resultados_json'] ?? '', true) ?: [];
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

            // Generación real del PDF
            require_once __DIR__ . '/../../../ayudantes/funciones.php';
            $pdfContenido = generarReporteEnsayoPDF($cotizacion, $detalle, $columnas, $filas, $codigoCompleto, $version);

            // Guardar archivo PDF en disco
            $rutaCarpeta = __DIR__ . '/../../../almacenamiento/informes';
            if (!file_exists($rutaCarpeta)) {
                mkdir($rutaCarpeta, 0777, true);
            }
            $nombrePdf = $codigoCompleto . ".pdf";
            $rutaPdfFisica = $rutaCarpeta . '/' . $nombrePdf;
            file_put_contents($rutaPdfFisica, $pdfContenido);

            // Guardar registro en base de datos
            $modelo = new OperacionModelo();
            $rutaRelativa = 'almacenamiento/informes/' . $nombrePdf;
            $exitoId = $modelo->registrarInforme($idLote, $codigoInforme, $version, $codigoCompleto, $tipoInforme, $edadEvaluadaDb, $motivoReemplazo, $rutaRelativa);

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

        // Servir el archivo PDF
        $rutaPdf = __DIR__ . '/../../../' . $informe['ruta_archivo_pdf'];
        if (file_exists($rutaPdf)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($rutaPdf) . '"');
            readfile($rutaPdf);
            exit;
        } else {
            die("El archivo PDF físico del informe no se encuentra en el servidor.");
        }
    }

    private function obtenerColumnasFormato(?string $archivo_markdown): array {
        if (empty($archivo_markdown)) return [];
        $rutaJson = __DIR__ . '/../../../datos_ensayos_markdown/formatos_schema.json';
        if (file_exists($rutaJson)) {
            $data = json_decode(file_get_contents($rutaJson), true);
            return $data[$archivo_markdown]['columns'] ?? [];
        }
        return [];
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
                registrarBitacora('operaciones', 'cambiar_estado', 'Orden de Servicio ID ' . $idOS . ' cambiada al estado: ' . $estado);
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
                registrarBitacora('operaciones', 'programar_muestreo', 'Muestreo programado para O/S ID ' . $idOS);
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

            $modelo = new OperacionModelo();
            if ($modelo->registrarHojaCampo($idOS, $codigo, $operador, $notas)) {
                registrarBitacora('operaciones', 'hoja_campo', 'Hoja de Campo registrada (CYCSA-RT-FM-07) para O/S ID ' . $idOS);
                $_SESSION['exito'] = 'Hoja de Campo CYCSA-RT-FM-07 guardada. Iniciado período obligatorio de 24 horas.';
            } else {
                $_SESSION['error'] = 'Error al guardar la hoja de campo.';
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
        $siguienteConsecutivo = $modelo->obtenerSiguienteConsecutivoMuestra($anioActual);

        // Obtener cliente y proyecto predeterminados de la O/S si es nueva hoja
        if (!$hoja) {
            $hoja = [
                'id_os' => $idOS,
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
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('operaciones/vistas/hoja_solicitud_form', [
            'titulo' => 'Hoja de Solicitud de Servicio CYCSA-RT-FM-13',
            'os' => $os,
            'hoja' => $hoja,
            'siguienteConsecutivo' => $siguienteConsecutivo,
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

            if ($modelo->guardarHojaSolicitud($datos)) {
                // Generar PDF y guardarlo en almacenamiento/solicitudes/
                $os = $modelo->obtenerOSPorId($idOS);
                $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS);
                
                require_once __DIR__ . '/../../../ayudantes/funciones.php';
                $pdfContenido = generarHojaSolicitudPDF($hoja, $os);
                
                $dirPdf = __DIR__ . '/../../../almacenamiento/solicitudes';
                if (!file_exists($dirPdf)) {
                    mkdir($dirPdf, 0777, true);
                }
                $nombrePdf = "CYCSA-RT-FM-13-" . $os['codigo_os'] . ".pdf";
                file_put_contents($dirPdf . '/' . $nombrePdf, $pdfContenido);

                registrarBitacora('operaciones', 'hoja_solicitud', 'Hoja de Solicitud CYCSA-RT-FM-13 guardada y PDF generado para O/S ID ' . $idOS);
                $_SESSION['exito'] = 'Hoja de Solicitud de Servicio CYCSA-RT-FM-13 guardada exitosamente y PDF generado. Estado de la O/S actualizado.';
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
                registrarBitacora('operaciones', 'emitir_solicitud', 'Solicitud emitida a técnicos para O/S ID ' . $idOS);
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
                registrarBitacora('operaciones', 'enviar_revision_resultados', 'Resultados de ensayos enviados a revisión para O/S ID ' . $idOS);
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
            
            if ($decision === 'Aprobar') {
                $exito = $modelo->actualizarEstadoOS($idOS, 'Finalizado', null);
                if ($exito) {
                    registrarBitacora('operaciones', 'finalizar_os', 'Orden de Servicio ID ' . $idOS . ' aprobada y finalizada.');
                    $_SESSION['exito'] = 'Resultados de ensayos aprobados y orden de servicio marcada como Finalizada.';
                }
            } else {
                if (empty($motivo)) {
                    $_SESSION['error'] = 'Debe indicar un motivo de rechazo si observa los resultados.';
                    $respuesta->redirigir('/Cycsa/publico/operaciones');
                    return;
                }
                $exito = $modelo->actualizarEstadoOS($idOS, 'Estado 6: Ejecucion Ensayos', $motivo);
                if ($exito) {
                    registrarBitacora('operaciones', 'observar_resultados', 'Resultados de O/S ID ' . $idOS . ' observados: ' . $motivo);
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

        $nombrePdf = "CYCSA-RT-FM-13-" . $os['codigo_os'] . ".pdf";
        $rutaPdf = __DIR__ . '/../../../almacenamiento/solicitudes/' . $nombrePdf;

        if (file_exists($rutaPdf)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($rutaPdf) . '"');
            readfile($rutaPdf);
            exit;
        } else {
            // Si el archivo no existe físicamente pero los datos están en BD, lo generamos al vuelo
            $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS);
            if ($hoja) {
                require_once __DIR__ . '/../../../ayudantes/funciones.php';
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

        $hoja = $modelo->obtenerHojaSolicitudPorOS($idOS);
        
        // Si no existe, creamos los valores predeterminados
        if (!$hoja) {
            $numCorrelativo = $modelo->obtenerSiguienteNumeroHojaSolicitud((int)$os['id_cotizacion']);
            $codigoDoc = "CYCSA-RT-FM-" . sprintf("%02d", $numCorrelativo);
            
            $hoja = [
                'id_os' => $idOS,
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
        $siguienteConsecutivo = $modelo->obtenerSiguienteConsecutivoMuestra($anioActual);

        $respuesta->enviarJson([
            'status' => 'success',
            'os' => $os,
            'hoja' => $hoja,
            'siguiente_consecutivo' => $siguienteConsecutivo,
            'anio_actual' => $anioActual
        ]);
    }
}
