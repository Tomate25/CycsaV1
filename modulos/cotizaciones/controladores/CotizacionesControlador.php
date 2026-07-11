<?php

namespace Cycsa\Modulos\Cotizaciones\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Cotizaciones\Modelos\CotizacionModelo;
use Cycsa\Modulos\Clientes\Modelos\ClienteModelo;
use Cycsa\Modulos\Productos\Modelos\ProductoModelo;
use Cycsa\Modulos\Configuracion\Modelos\ConfiguracionModelo;

class CotizacionesControlador extends ControladorBase {
    
    private function verificarSesion(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) { $respuesta->redirigir('/Cycsa/publico/login'); exit; }
    }

    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('cotizaciones', 'ver')) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
        $modelo = new CotizacionModelo();
        
        $busqueda = $_GET['q'] ?? '';
        $tab = $_GET['tab'] ?? '';
        
        // Tab por defecto según rol
        if (empty($tab)) {
            $tab = ($_SESSION['usuario_rol'] == 1) ? 'revision' : 'borradores';
        }
        
        $todas = $modelo->obtenerTodas($busqueda);
        $cotizaciones = [];
        
        foreach ($todas as $cot) {
            $esPropietario = ($cot['id_usuario_creador'] == $_SESSION['usuario_id']);
            $esAdmin = ($_SESSION['usuario_rol'] == 1);
            
            // Si es la pestaña 'todas', se listan todas las cotizaciones de la base de datos
            if ($tab === 'todas') {
                $cotizaciones[] = $cot;
                continue;
            }
            
            // Para pestañas específicas, si no es admin y no es propietario, no se incluye
            if (!$esAdmin && !$esPropietario) {
                continue;
            }
            
            if ($tab === 'borradores') {
                if ($cot['estado'] === 'Borrador') {
                    $cotizaciones[] = $cot;
                }
            } elseif ($tab === 'revision') {
                if ($cot['estado'] === 'En Revision') {
                    $cotizaciones[] = $cot;
                }
            } elseif ($tab === 'observadas') {
                if ($cot['estado'] === 'Observada') {
                    $cotizaciones[] = $cot;
                }
            } elseif ($tab === 'aprobadas') {
                $estadosAprobados = ['Aprobada Internamente', 'Enviada al Cliente', 'Aprobada por Cliente', 'Rechazada por Cliente'];
                if (in_array($cot['estado'], $estadosAprobados)) {
                    $cotizaciones[] = $cot;
                }
            }
        }
        
        $bitacora_logs = obtenerBitacoraModulo('cotizaciones');

        $this->renderizar('cotizaciones/vistas/index', [
            'titulo' => 'Cotizaciones - Cycsa',
            'cotizaciones' => $cotizaciones,
            'busqueda' => $busqueda,
            'tabActual' => $tab,
            'bitacora_logs' => $bitacora_logs
        ]);
    }

    public function crear(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('cotizaciones', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            exit;
        }
        if (empty($_SESSION['csrf_token'])) $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        $prodModelo = new ProductoModelo();
        $configModelo = new ConfiguracionModelo();
        $this->renderizar('cotizaciones/vistas/crear', [
            'titulo' => 'Nueva Cotización', 
            'clientes' => (new ClienteModelo())->obtenerTodos(),
            'productos' => $prodModelo->obtenerTodos(),
            'categorias' => $prodModelo->obtenerCategorias(),
            'condiciones_pago' => $configModelo->obtenerPorTipo('condicion_pago'),
            'tiempos_entrega' => $configModelo->obtenerPorTipo('tiempo_entrega'),
            'vigencias_oferta' => $configModelo->obtenerPorTipo('vigencia_oferta')
        ]);
    }

    public function guardar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('cotizaciones', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            exit;
        }
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) { $respuesta->redirigir('/Cycsa/publico/cotizaciones/crear'); return; }
            $modelo = new CotizacionModelo();
            $notasJson = isset($datos['notas']) ? json_encode($datos['notas']) : null;
            $cabecera = [
                'codigo' => $modelo->generarCodigoUnico(),
                'id_cliente' => $datos['id_cliente'],
                'tipo_moneda' => isset($datos['tipo_moneda']) ? (int)$datos['tipo_moneda'] : 1,
                'id_usuario_creador' => $_SESSION['usuario_id'],
                'atencion_a' => trim($datos['atencion_a']),
                'nombre_proyecto' => trim($datos['nombre_proyecto']),
                'direccion_proyecto' => trim($datos['direccion_proyecto']),
                'prioridad' => $datos['prioridad'] ?? 'Normal',
                'fecha_limite' => !empty($datos['fecha_limite']) ? $datos['fecha_limite'] : null,
                'condicion_pago' => $datos['condicion_pago'],
                'tiempo_entrega' => trim($datos['tiempo_entrega']),
                'vigencia_oferta' => trim($datos['vigencia_oferta']),
                'configuracion_notas' => $notesJson ?? $notasJson,
                'contactos' => isset($datos['contactos']) ? trim($datos['contactos']) : null,
                'subtotal' => (float)$datos['subtotal_general'],
                'descuento' => isset($datos['descuento']) ? (float)$datos['descuento'] : 0.00,
                'exonerado' => isset($datos['exonerado']) ? (int)$datos['exonerado'] : 0,
                'exoneracion_no' => !empty($datos['exoneracion_no']) ? trim($datos['exoneracion_no']) : null,
                'impuesto' => (float)$datos['impuesto_general'],
                'total' => (float)$datos['total_general'],
                'fecha_entrega' => !empty($datos['fecha_entrega']) ? $datos['fecha_entrega'] : null,
                'fecha_seguimiento' => !empty($datos['fecha_seguimiento']) ? $datos['fecha_seguimiento'] : null
            ];
            $detalles = $this->procesarDetalles($datos);
            if ($modelo->guardarCotizacionCompleta($cabecera, $detalles)) {
                registrarBitacora('cotizaciones', 'crear', 'Creada cotización borrador: ' . $cabecera['codigo']);
                $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            } else {
                $respuesta->redirigir('/Cycsa/publico/cotizaciones/crear');
            }
        }
    }

    public function detalle(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('cotizaciones', 'ver')) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
        $id = (int)($_GET['id'] ?? 0);
        $modelo = new CotizacionModelo();
        $cotizacion = $modelo->obtenerPorId($id);
        if (!$cotizacion) { $respuesta->redirigir('/Cycsa/publico/cotizaciones'); return; }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $modeloContabilidad = new \Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo();
        $bancos = $modeloContabilidad->obtenerCuentasBancarias();

        $this->renderizar('cotizaciones/vistas/detalle', [
            'titulo' => 'Detalle', 
            'cotizacion' => $cotizacion, 
            'detalles' => $modelo->obtenerDetalles($id),
            'versiones' => $modelo->obtenerVersiones($id),
            'bancos' => $bancos
        ]);
    }

    public function editar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('cotizaciones', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            exit;
        }
        $id = (int)($_GET['id'] ?? 0);
        $modelo = new CotizacionModelo();
        $cot = $modelo->obtenerPorId($id);
        if ($cot['estado'] !== 'Observada' && $cot['estado'] !== 'Rechazada por Cliente') { $respuesta->redirigir('/Cycsa/publico/cotizaciones/detalle?id='.$id); return; }
        $prodModelo = new ProductoModelo();
        $configModelo = new ConfiguracionModelo();
        $this->renderizar('cotizaciones/vistas/editar', [
            'cotizacion' => $cot, 
            'detalles' => $modelo->obtenerDetalles($id), 
            'clientes' => (new ClienteModelo())->obtenerTodos(),
            'productos' => $prodModelo->obtenerTodos(),
            'categorias' => $prodModelo->obtenerCategorias(),
            'condiciones_pago' => $configModelo->obtenerPorTipo('condicion_pago'),
            'tiempos_entrega' => $configModelo->obtenerPorTipo('tiempo_entrega'),
            'vigencias_oferta' => $configModelo->obtenerPorTipo('vigencia_oferta')
        ]);
    }

    public function actualizar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('cotizaciones', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            exit;
        }
        $datos = $peticion->obtenerDatos();
        $id = (int)$datos['id'];
        $modelo = new CotizacionModelo();

        // 1. Obtener estado previo para saber si era rechazada por el cliente
        $cotizacionPrev = $modelo->obtenerPorId($id);
        $eraRechazada = ($cotizacionPrev && $cotizacionPrev['estado'] === 'Rechazada por Cliente');

        $notasJson = isset($datos['notas']) ? json_encode($datos['notas']) : null;
        $cabecera = [
            'id_cliente' => $datos['id_cliente'],
            'tipo_moneda' => isset($datos['tipo_moneda']) ? (int)$datos['tipo_moneda'] : 1,
            'atencion_a' => trim($datos['atencion_a']),
            'nombre_proyecto' => trim($datos['nombre_proyecto']),
            'direccion_proyecto' => trim($datos['direccion_proyecto']),
            'condicion_pago' => $datos['condicion_pago'],
            'tiempo_entrega' => trim($datos['tiempo_entrega']),
            'vigencia_oferta' => trim($datos['vigencia_oferta']),
            'configuracion_notas' => $notasJson,
            'contactos' => isset($datos['contactos']) ? trim($datos['contactos']) : null,
            'subtotal' => (float)$datos['subtotal_general'],
            'descuento' => isset($datos['descuento']) ? (float)$datos['descuento'] : 0.00,
            'exonerado' => isset($datos['exonerado']) ? (int)$datos['exonerado'] : 0,
            'exoneracion_no' => !empty($datos['exoneracion_no']) ? trim($datos['exoneracion_no']) : null,
            'impuesto' => (float)$datos['impuesto_general'],
            'total' => (float)$datos['total_general'],
            'fecha_entrega' => !empty($datos['fecha_entrega']) ? $datos['fecha_entrega'] : null,
            'fecha_seguimiento' => !empty($datos['fecha_seguimiento']) ? $datos['fecha_seguimiento'] : null
        ];
        if ($modelo->actualizarCotizacionCompleta($id, $cabecera, $this->procesarDetalles($datos))) {
            $cot = $modelo->obtenerPorId($id);

            if ($eraRechazada) {
                // Registrar en la bitácora
                registrarBitacora('cotizaciones', 'editar_reenviar', 'Corregida y re-enviada cotización al cliente: ' . $cot['codigo'] . ' (Nueva Versión: ' . $cot['version'] . ')', $id);

                // Enviar el correo electrónico con el nuevo PDF de forma automática
                $detalles = $modelo->obtenerDetalles($id);
                $pdfContenido = generarCotizacionPDF($cot, $detalles);

                $destinatario = !empty($cot['cliente_email']) ? $cot['cliente_email'] : '';
                $titulo_correo = "Cotización Oficial Corregida - CYCSA - " . $cot['codigo'];
                $token = $cot['token_seguridad'];
                $urlDecision = obtenerBaseUrl() . "/cotizaciones/decision-cliente?id={$id}&token={$token}";

                $mensaje = "
                <html>
                <head>
                  <title>Cotización Oficial Corregida CYCSA</title>
                </head>
                <body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\">
                  <div style=\"max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;\">
                    <h2 style=\"color: #103487; border-bottom: 2px solid #103487; padding-bottom: 10px;\">Envío de Cotización Corregida</h2>
                    <p>Estimado Cliente <strong>" . htmlspecialchars($cot['cliente_nombre'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                    <p>Le hacemos llegar la propuesta económica corregida y actualizada bajo el código de cotización <strong>" . htmlspecialchars($cot['codigo'], ENT_QUOTES, 'UTF-8') . "</strong> (Versión " . $cot['version'] . ").</p>
                    
                    <div style=\"background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #e9ecef;\">
                        <p style=\"margin: 5px 0;\"><strong>Código de Oferta:</strong> " . htmlspecialchars($cot['codigo'], ENT_QUOTES, 'UTF-8') . "</p>
                        <p style=\"margin: 5px 0;\"><strong>Versión:</strong> " . $cot['version'] . "</p>
                        <p style=\"margin: 5px 0;\"><strong>Proyecto:</strong> " . htmlspecialchars($cot['nombre_proyecto'], ENT_QUOTES, 'UTF-8') . "</p>
                        <p style=\"margin: 5px 0;\"><strong>Monto Total:</strong> C$ " . number_format($cot['total'], 2) . "</p>
                    </div>
                    
                    <div style=\"background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 8px; margin: 25px 0; text-align: center;\">
                        <h3 style=\"margin-top: 0; color: #1e293b; font-family: Arial, sans-serif;\">¿Desea aceptar o rechazar esta nueva propuesta?</h3>
                        <p style=\"color: #64748b; font-size: 14px; margin-bottom: 20px;\">Puede revisar los detalles completos de la cotización y registrar su decisión en línea de forma segura haciendo clic en el siguiente botón:</p>
                        <a href=\"" . htmlspecialchars($urlDecision, ENT_QUOTES, 'UTF-8') . "\" style=\"background-color: #103487; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block; box-shadow: 0 4px 6px -1px rgba(16, 52, 135, 0.2);\">Revisar y Decidir en Línea</a>
                    </div>
                    
                    <p>Por favor revise las condiciones. Quedamos a la espera de su confirmación para proceder con la orden.</p>
                    <hr style=\"border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;\">
                    <p style=\"font-size: 14px; font-weight: bold; color: #103487;\">CYCSA Laboratorio de Ensayos</p>
                  </div>
                </body>
                </html>
                ";

                $adjuntos = [
                    [
                        'contenido' => $pdfContenido,
                        'nombre' => "Cotizacion_{$cot['codigo']}_V{$cot['version']}.pdf"
                    ]
                ];

                if (!empty($destinatario)) {
                    enviarCorreo($destinatario, $titulo_correo, $mensaje, '', $adjuntos);

                    // Registro local en logs
                    $rutaLog = __DIR__ . '/../../../almacenamiento/logs/emails.log';
                    if (!file_exists(dirname($rutaLog))) {
                        @mkdir(dirname($rutaLog), 0777, true);
                    }
                    $logMsg = "[" . date('Y-m-d H:i:s') . "] Cotización CORREGIDA Y RE-ENVIADA. Destinatario: {$destinatario} | Cotización: {$cot['codigo']} | Versión: {$cot['version']} | Monto: C$ " . number_format($cot['total'], 2) . "\n";
                    @file_put_contents($rutaLog, $logMsg, FILE_APPEND);

                    $_SESSION['envio_exitoso'] = "¡Cotización corregida (V" . $cot['version'] . ") enviada automáticamente al cliente!";
                } else {
                    // Registro local en logs
                    $rutaLog = __DIR__ . '/../../../almacenamiento/logs/emails.log';
                    if (!file_exists(dirname($rutaLog))) {
                        @mkdir(dirname($rutaLog), 0777, true);
                    }
                    $logMsg = "[" . date('Y-m-d H:i:s') . "] Cotización CORREGIDA (Entrega Manual - Sin correo). Cotización: {$cot['codigo']} | Versión: {$cot['version']} | Monto: C$ " . number_format($cot['total'], 2) . "\n";
                    @file_put_contents($rutaLog, $logMsg, FILE_APPEND);

                    $_SESSION['envio_exitoso'] = "¡Cotización corregida (V" . $cot['version'] . ") guardada (Cliente sin correo registrado)!";
                }
            } else {
                registrarBitacora('cotizaciones', 'editar', 'Modificada/Corregida cotización: ' . $cot['codigo'] . ' (estado: En Revision)', $id);
            }
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
        }
    }

    public function procesarRevision(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('cotizaciones', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            exit;
        }
        $datos = $peticion->obtenerDatos();
        $modelo = new CotizacionModelo();
        $id = (int)$datos['id'];
        
        if ($datos['accion'] === 'aprobar') {
            $token = bin2hex(random_bytes(32));
            $modelo->actualizarEstado($id, 'Enviada al Cliente', $_SESSION['usuario_id'], null, $token);
            
            // Obtener datos completos para enviar el correo
            $cotizacion = $modelo->obtenerPorId($id);
            if ($cotizacion) {
                registrarBitacora('cotizaciones', 'aprobar_gerencia', 'Aprobada cotización por Gerencia: ' . $cotizacion['codigo'] . ' (enviada al cliente)', $id);
                $detalles = $modelo->obtenerDetalles($id);
                $pdfContenido = generarCotizacionPDF($cotizacion, $detalles);
                
                $destinatario = !empty($cotizacion['cliente_email']) ? $cotizacion['cliente_email'] : '';
                $titulo_correo = "Cotización Oficial - CYCSA - " . $cotizacion['codigo'];
                $urlDecision = obtenerBaseUrl() . "/cotizaciones/decision-cliente?id={$id}&token={$token}";
                
                $mensaje = "
                <html>
                <head>
                  <title>Cotización Oficial CYCSA</title>
                </head>
                <body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\">
                  <div style=\"max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;\">
                    <h2 style=\"color: #103487; border-bottom: 2px solid #103487; padding-bottom: 10px;\">Envío de Cotización Oficial</h2>
                    <p>Estimado Cliente <strong>" . htmlspecialchars($cotizacion['cliente_nombre'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                    <p>Adjunto a este mensaje le hacemos llegar la propuesta económica formalizada bajo el código de cotización <strong>" . htmlspecialchars($cotizacion['codigo'], ENT_QUOTES, 'UTF-8') . "</strong>.</p>
                    
                    <div style=\"background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #e9ecef;\">
                        <p style=\"margin: 5px 0;\"><strong>Código de Oferta:</strong> " . htmlspecialchars($cotizacion['codigo'], ENT_QUOTES, 'UTF-8') . "</p>
                        <p style=\"margin: 5px 0;\"><strong>Proyecto:</strong> " . htmlspecialchars($cotizacion['nombre_proyecto'], ENT_QUOTES, 'UTF-8') . "</p>
                        <p style=\"margin: 5px 0;\"><strong>Monto Total:</strong> C$ " . number_format($cotizacion['total'], 2) . "</p>
                        <p style=\"margin: 5px 0;\"><strong>Condición de Pago:</strong> " . htmlspecialchars($cotizacion['condicion_pago'], ENT_QUOTES, 'UTF-8') . "</p>
                        <p style=\"margin: 5px 0;\"><strong>Tiempo de Entrega:</strong> " . htmlspecialchars($cotizacion['tiempo_entrega'], ENT_QUOTES, 'UTF-8') . "</p>
                        <p style=\"margin: 5px 0;\"><strong>Vigencia de Oferta:</strong> " . htmlspecialchars($cotizacion['vigencia_oferta'], ENT_QUOTES, 'UTF-8') . "</p>
                    </div>
                    
                    <div style=\"background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 8px; margin: 25px 0; text-align: center;\">
                        <h3 style=\"margin-top: 0; color: #1e293b; font-family: Arial, sans-serif;\">¿Desea aceptar o rechazar esta propuesta?</h3>
                        <p style=\"color: #64748b; font-size: 14px; margin-bottom: 20px;\">Puede revisar los detalles completos de la cotización y registrar su decisión en línea de forma segura haciendo clic en el siguiente botón:</p>
                        <a href=\"" . htmlspecialchars($urlDecision, ENT_QUOTES, 'UTF-8') . "\" style=\"background-color: #103487; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block; box-shadow: 0 4px 6px -1px rgba(16, 52, 135, 0.2);\">Revisar y Decidir en Línea</a>
                        <p style=\"font-size: 12px; color: #94a3b8; margin-top: 15px; margin-bottom: 0;\">Si el botón no funciona, copie y pegue este enlace en su navegador:<br><a href=\"" . htmlspecialchars($urlDecision, ENT_QUOTES, 'UTF-8') . "\" style=\"color: #103487; text-decoration: underline;\">" . htmlspecialchars($urlDecision, ENT_QUOTES, 'UTF-8') . "</a></p>
                    </div>
                    
                    <p>Por favor revise las condiciones. Quedamos a la espera de su confirmación para proceder con la orden.</p>
                    <hr style=\"border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;\">
                    <p style=\"font-size: 14px; font-weight: bold; color: #103487;\">CYCSA Laboratorio de Ensayos</p>
                  </div>
                </body>
                </html>
                ";
                
                $adjuntos = [
                    [
                        'contenido' => $pdfContenido,
                        'nombre' => "Cotizacion_{$cotizacion['codigo']}.pdf"
                    ]
                ];
                
                if (!empty($destinatario)) {
                    enviarCorreo($destinatario, $titulo_correo, $mensaje, '', $adjuntos);
                    
                    // Registro local en logs de desarrollo
                    $rutaLog = __DIR__ . '/../../../almacenamiento/logs/emails.log';
                    if (!file_exists(dirname($rutaLog))) {
                        @mkdir(dirname($rutaLog), 0777, true);
                    }
                    $logMsg = "[" . date('Y-m-d H:i:s') . "] Cotización ENVIADA al Cliente (Aprobación). Destinatario: {$destinatario} | Cotización: {$cotizacion['codigo']} | Monto: C$ " . number_format($cotizacion['total'], 2) . "\n";
                    @file_put_contents($rutaLog, $logMsg, FILE_APPEND);
                } else {
                    // Registro local en logs de desarrollo
                    $rutaLog = __DIR__ . '/../../../almacenamiento/logs/emails.log';
                    if (!file_exists(dirname($rutaLog))) {
                        @mkdir(dirname($rutaLog), 0777, true);
                    }
                    $logMsg = "[" . date('Y-m-d H:i:s') . "] Cotización Aprobada por Gerencia (Entrega Manual - Sin correo). Cotización: {$cotizacion['codigo']} | Monto: C$ " . number_format($cotizacion['total'], 2) . "\n";
                    @file_put_contents($rutaLog, $logMsg, FILE_APPEND);
                }
            }
        } elseif ($datos['accion'] === 'observar') {
            $modelo->actualizarEstado($id, 'Observada', $_SESSION['usuario_id'], $datos['motivo_observacion'], null);
            $cot = $modelo->obtenerPorId($id);
            registrarBitacora('cotizaciones', 'devolver_gerencia', 'Devuelta cotización con observaciones por Gerencia: ' . $cot['codigo'] . ' - Motivo: ' . $datos['motivo_observacion'], $id);
        }
        
        $respuesta->redirigir('/Cycsa/publico/cotizaciones/detalle?id='.$id);
    }

    public function enviarCliente(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('cotizaciones', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            exit;
        }
        
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $id = (int)($datos['id'] ?? 0);
            $modelo = new CotizacionModelo();
            $cotizacion = $modelo->obtenerPorId($id);
            
            if (!$cotizacion) {
                $respuesta->redirigir('/Cycsa/publico/cotizaciones');
                return;
            }
            
            // Si el estado es 'Aprobada Internamente', generamos/aseguramos token y actualizamos a 'Enviada al Cliente'
            $token = $cotizacion['token_seguridad'];
            $esRechazada = ($cotizacion['estado'] === 'Rechazada por Cliente');
            
            if ($esRechazada) {
                // Si la cotización estaba rechazada, al re-enviarla directamente se versiona y se envía
                $exito = $modelo->volverEnviarRechazada($id);
                if (!$exito) {
                    $_SESSION['envio_exitoso'] = "Error al procesar el re-envío de la cotización.";
                    $respuesta->redirigir('/Cycsa/publico/cotizaciones/detalle?id=' . $id);
                    return;
                }
                // Recargar cotización para obtener nueva versión, token, etc.
                $cotizacion = $modelo->obtenerPorId($id);
                $token = $cotizacion['token_seguridad'];
            } elseif (empty($token)) {
                $token = bin2hex(random_bytes(32));
                $modelo->actualizarEstado($id, 'Enviada al Cliente', $cotizacion['id_usuario_revisor'] ?? $_SESSION['usuario_id'], $cotizacion['motivo_observacion'], $token);
            } elseif ($cotizacion['estado'] === 'Aprobada Internamente') {
                $modelo->actualizarEstado($id, 'Enviada al Cliente', $cotizacion['id_usuario_revisor'] ?? $_SESSION['usuario_id'], $cotizacion['motivo_observacion'], $token);
            }
            
            // Obtener datos actualizados y detalles para generar el PDF
            $cotizacion = $modelo->obtenerPorId($id);
            $detalles = $modelo->obtenerDetalles($id);
            $pdfContenido = generarCotizacionPDF($cotizacion, $detalles);
            
            $destinatario = !empty($cotizacion['cliente_email']) ? $cotizacion['cliente_email'] : '';
            $titulo_correo = "Cotización Oficial - CYCSA - " . $cotizacion['codigo'];
            if ($cotizacion['version'] > 0) {
                $titulo_correo .= " (V" . $cotizacion['version'] . ")";
            }
            
            $urlDecision = obtenerBaseUrl() . "/cotizaciones/decision-cliente?id={$id}&token={$token}";
            
            $mensaje = "
            <html>
            <head>
              <title>Cotización Oficial CYCSA</title>
            </head>
            <body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\">
              <div style=\"max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;\">
                <h2 style=\"color: #103487; border-bottom: 2px solid #103487; padding-bottom: 10px;\">Envío de Cotización Oficial</h2>
                <p>Estimado Cliente <strong>" . htmlspecialchars($cotizacion['cliente_nombre'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                <p>Adjunto a este mensaje le hacemos llegar la propuesta económica formalizada bajo el código de cotización <strong>" . htmlspecialchars($cotizacion['codigo'], ENT_QUOTES, 'UTF-8') . "</strong>" . ($cotizacion['version'] > 0 ? " (Versión " . $cotizacion['version'] . ")" : "") . ".</p>
                
                <div style=\"background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #e9ecef;\">
                    <p style=\"margin: 5px 0;\"><strong>Código de Oferta:</strong> " . htmlspecialchars($cotizacion['codigo'], ENT_QUOTES, 'UTF-8') . "</p>
                    " . ($cotizacion['version'] > 0 ? "<p style=\"margin: 5px 0;\"><strong>Versión:</strong> " . $cotizacion['version'] . "</p>" : "") . "
                    <p style=\"margin: 5px 0;\"><strong>Proyecto:</strong> " . htmlspecialchars($cotizacion['nombre_proyecto'], ENT_QUOTES, 'UTF-8') . "</p>
                    <p style=\"margin: 5px 0;\"><strong>Monto Total:</strong> C$ " . number_format($cotizacion['total'], 2) . "</p>
                    <p style=\"margin: 5px 0;\"><strong>Condición de Pago:</strong> " . htmlspecialchars($cotizacion['condicion_pago'], ENT_QUOTES, 'UTF-8') . "</p>
                    <p style=\"margin: 5px 0;\"><strong>Tiempo de Entrega:</strong> " . htmlspecialchars($cotizacion['tiempo_entrega'], ENT_QUOTES, 'UTF-8') . "</p>
                    <p style=\"margin: 5px 0;\"><strong>Vigencia de Oferta:</strong> " . htmlspecialchars($cotizacion['vigencia_oferta'], ENT_QUOTES, 'UTF-8') . "</p>
                </div>
                
                <div style=\"background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 25px; border-radius: 8px; margin: 25px 0; text-align: center;\">
                    <h3 style=\"margin-top: 0; color: #1e293b; font-family: Arial, sans-serif;\">¿Desea aceptar o rechazar esta propuesta?</h3>
                    <p style=\"color: #64748b; font-size: 14px; margin-bottom: 20px;\">Puede revisar los detalles completos de la cotización y registrar su decisión en línea de forma segura haciendo clic en el siguiente botón:</p>
                    <a href=\"" . htmlspecialchars($urlDecision, ENT_QUOTES, 'UTF-8') . "\" style=\"background-color: #103487; color: #ffffff; padding: 12px 28px; text-decoration: none; border-radius: 6px; font-weight: 600; display: inline-block; box-shadow: 0 4px 6px -1px rgba(16, 52, 135, 0.2);\">Revisar y Decidir en Línea</a>
                    <p style=\"font-size: 12px; color: #94a3b8; margin-top: 15px; margin-bottom: 0;\">Si el botón no funciona, copie y pegue este enlace en su navegador:<br><a href=\"" . htmlspecialchars($urlDecision, ENT_QUOTES, 'UTF-8') . "\" style=\"color: #103487; text-decoration: underline;\">" . htmlspecialchars($urlDecision, ENT_QUOTES, 'UTF-8') . "</a></p>
                </div>
                
                <p>Por favor revise las condiciones. Quedamos a la espera de su confirmación para proceder con la orden.</p>
                <hr style=\"border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;\">
                <p style=\"font-size: 14px; font-weight: bold; color: #103487;\">CYCSA Laboratorio de Ensayos</p>
              </div>
            </body>
            </html>
            ";
            
            $nombrePdf = "Cotizacion_{$cotizacion['codigo']}";
            if ($cotizacion['version'] > 0) {
                $nombrePdf .= "_V{$cotizacion['version']}";
            }
            $nombrePdf .= ".pdf";
            
            $adjuntos = [
                [
                    'contenido' => $pdfContenido,
                    'nombre' => $nombrePdf
                ]
            ];
            
            if (!empty($destinatario)) {
                // Envío real mediante PHPMailer con adjunto PDF
                enviarCorreo($destinatario, $titulo_correo, $mensaje, '', $adjuntos);
                
                if ($esRechazada) {
                    registrarBitacora('cotizaciones', 'volver_enviar_rechazada', 'Re-enviada cotización al cliente (Nueva Versión: ' . $cotizacion['version'] . '): ' . $cotizacion['codigo'], $id);
                    $_SESSION['envio_exitoso'] = "¡Cotización re-enviada con éxito al cliente (Versión " . $cotizacion['version'] . ")!";
                } else {
                    registrarBitacora('cotizaciones', 'enviar_cliente', 'Enviada cotización al cliente: ' . $cotizacion['codigo'] . ' (correo: ' . $destinatario . ')', $id);
                    $_SESSION['envio_exitoso'] = "¡Cotización enviada con éxito al correo del cliente ({$destinatario})!";
                }
                
                // Registro local
                $rutaLog = __DIR__ . '/../../../almacenamiento/logs/emails.log';
                if (!file_exists(dirname($rutaLog))) {
                    @mkdir(dirname($rutaLog), 0777, true);
                }
                $logMsg = "[" . date('Y-m-d H:i:s') . "] Cotización ENVIADA al Cliente. Destinatario: {$destinatario} | Cotización: {$cotizacion['codigo']} | Versión: {$cotizacion['version']} | Monto: C$ " . number_format($cotizacion['total'], 2) . "\n";
                @file_put_contents($rutaLog, $logMsg, FILE_APPEND);
            } else {
                if ($esRechazada) {
                    registrarBitacora('cotizaciones', 'volver_enviar_rechazada', 'Re-enviada cotización al cliente (Entrega Manual - Sin correo): ' . $cotizacion['codigo'], $id);
                    $_SESSION['envio_exitoso'] = "¡Cotización marcada como re-enviada para Entrega Manual (Sin correo registrado)!";
                } else {
                    registrarBitacora('cotizaciones', 'enviar_cliente', 'Marcada cotización como enviada (Entrega Manual - Sin correo): ' . $cotizacion['codigo'], $id);
                    $_SESSION['envio_exitoso'] = "¡Cotización marcada como Enviada para Entrega Manual (Sin correo registrado)!";
                }
                
                // Registro local
                $rutaLog = __DIR__ . '/../../../almacenamiento/logs/emails.log';
                if (!file_exists(dirname($rutaLog))) {
                    @mkdir(dirname($rutaLog), 0777, true);
                }
                $logMsg = "[" . date('Y-m-d H:i:s') . "] Cotización Marcada como Enviada (Entrega Manual - Sin correo). Cotización: {$cotizacion['codigo']} | Versión: {$cotizacion['version']} | Monto: C$ " . number_format($cotizacion['total'], 2) . "\n";
                @file_put_contents($rutaLog, $logMsg, FILE_APPEND);
            }
            
            $respuesta->redirigir('/Cycsa/publico/cotizaciones/detalle?id=' . $id);
            return;
        }
    }

    public function decisionCliente(Peticion $peticion, Respuesta $respuesta): void {
        $id = (int)($_GET['id'] ?? 0);
        $token = $_GET['token'] ?? '';
        
        $modelo = new CotizacionModelo();
        $cotizacion = $modelo->obtenerPorId($id);
        
        // Validaciones de seguridad
        if (!$cotizacion || empty($token) || empty($cotizacion['token_seguridad']) || !hash_equals($cotizacion['token_seguridad'], $token)) {
            $respuesta->establecerCodigoEstado(403);
            $this->renderizarSinLayout('cotizaciones/vistas/decision_cliente', [
                'titulo' => 'Acceso Denegado - CYCSA',
                'error' => 'El enlace de decisión no es válido, ha expirado o no cuenta con los permisos necesarios.'
            ]);
            return;
        }
        
        $modeloContabilidad = new \Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo();
        $bancos = $modeloContabilidad->obtenerCuentasBancarias();

        // Si el estado ya es 'Aprobada por Cliente' o 'Rechazada por Cliente'
        if ($cotizacion['estado'] === 'Aprobada por Cliente' || $cotizacion['estado'] === 'Rechazada por Cliente') {
            $detalles = $modelo->obtenerDetalles($id);
            $this->renderizarSinLayout('cotizaciones/vistas/decision_cliente', [
                'titulo' => 'Cotización Procesada - CYCSA',
                'cotizacion' => $cotizacion,
                'detalles' => $detalles,
                'soloLectura' => true,
                'token' => $token,
                'bancos' => $bancos
            ]);
            return;
        }
        
        // Generar CSRF token
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        $_SESSION['client_csrf_token'] = bin2hex(random_bytes(32));
        
        $detalles = $modelo->obtenerDetalles($id);
        $this->renderizarSinLayout('cotizaciones/vistas/decision_cliente', [
            'titulo' => 'Decisión de Cotización - CYCSA',
            'cotizacion' => $cotizacion,
            'detalles' => $detalles,
            'soloLectura' => false,
            'csrf_token' => $_SESSION['client_csrf_token'],
            'token' => $token,
            'bancos' => $bancos
        ]);
    }

    public function procesarDecisionCliente(Peticion $peticion, Respuesta $respuesta): void {
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $id = (int)($datos['id'] ?? 0);
            $token = $datos['token'] ?? '';
            $accion = $datos['accion'] ?? '';
            $motivo_rechazo = trim($datos['motivo_rechazo'] ?? '');
            $csrf_token = $datos['csrf_token'] ?? '';
            
            // 1. Validar CSRF
            $sessionCsrf = $_SESSION['client_csrf_token'] ?? '';
            if (empty($csrf_token) || empty($sessionCsrf) || !hash_equals($sessionCsrf, $csrf_token)) {
                $respuesta->establecerCodigoEstado(403);
                $this->renderizarSinLayout('cotizaciones/vistas/decision_cliente', [
                    'titulo' => 'Error de Validación - CYCSA',
                    'error' => 'Falló la validación de seguridad (CSRF). Por favor, refresque la página e intente nuevamente.'
                ]);
                return;
            }
            
            // 2. Validar Cotización y Token
            $modelo = new CotizacionModelo();
            $cotizacion = $modelo->obtenerPorId($id);
            
            if (!$cotizacion || empty($token) || empty($cotizacion['token_seguridad']) || !hash_equals($cotizacion['token_seguridad'], $token)) {
                $respuesta->establecerCodigoEstado(403);
                $this->renderizarSinLayout('cotizaciones/vistas/decision_cliente', [
                    'titulo' => 'Acceso Denegado - CYCSA',
                    'error' => 'El enlace de decisión no es válido, ha expirado o no cuenta con los permisos necesarios.'
                ]);
                return;
            }
            
            // Si el estado ya es 'Aprobada por Cliente' o 'Rechazada por Cliente'
            if ($cotizacion['estado'] === 'Aprobada por Cliente' || $cotizacion['estado'] === 'Rechazada por Cliente') {
                $respuesta->redirigir("/Cycsa/publico/cotizaciones/decision-cliente?id={$id}&token={$token}");
                return;
            }
            
            // 3. Ejecutar Acción
            $nuevoEstado = '';
            $motivo = null;
            $metodo_pago = $datos['metodo_pago'] ?? null;
            $id_banco_cuenta = !empty($datos['id_banco_cuenta']) ? (int)$datos['id_banco_cuenta'] : null;
            $referencia_pago = !empty($datos['referencia_pago']) ? trim($datos['referencia_pago']) : null;
            $porcentaje_pago_inmediato = isset($datos['porcentaje_pago_inmediato']) ? (float)$datos['porcentaje_pago_inmediato'] : 100.00;
            $monto_pago_inmediato = isset($datos['monto_pago_inmediato']) ? (float)$datos['monto_pago_inmediato'] : 0.00;
            $monto_credito = isset($datos['monto_credito']) ? (float)$datos['monto_credito'] : 0.00;
            $efectivo_recibido = !empty($datos['efectivo_recibido']) ? (float)$datos['efectivo_recibido'] : null;
            $efectivo_vuelto = !empty($datos['efectivo_vuelto']) ? (float)$datos['efectivo_vuelto'] : null;
            $dias_credito = !empty($datos['dias_credito']) ? (int)$datos['dias_credito'] : 30;

            if ($accion === 'aceptar') {
                $nuevoEstado = 'Aprobada por Cliente';
            } elseif ($accion === 'rechazar') {
                $nuevoEstado = 'Rechazada por Cliente';
                if (empty($motivo_rechazo)) {
                    $modeloContabilidad = new \Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo();
                    $bancos = $modeloContabilidad->obtenerCuentasBancarias();
                    
                    $this->renderizarSinLayout('cotizaciones/vistas/decision_cliente', [
                        'titulo' => 'Error - CYCSA',
                        'cotizacion' => $cotizacion,
                        'detalles' => $modelo->obtenerDetalles($id),
                        'soloLectura' => false,
                        'csrf_token' => $_SESSION['client_csrf_token'],
                        'token' => $token,
                        'bancos' => $bancos,
                        'error' => 'Debe especificar el motivo del rechazo para poder procesar la solicitud.'
                    ]);
                    return;
                }
                $motivo = $motivo_rechazo;
            } else {
                $respuesta->establecerCodigoEstado(400);
                die("Acción no válida.");
            }
            
            $exito = $modelo->registrarDecisionCliente(
                $id, 
                $nuevoEstado, 
                $motivo, 
                $metodo_pago, 
                $id_banco_cuenta, 
                $referencia_pago,
                $porcentaje_pago_inmediato,
                $monto_pago_inmediato,
                $monto_credito,
                $efectivo_recibido,
                $efectivo_vuelto,
                $dias_credito
            );
            
            if ($exito) {
                // Registrar en la bitácora de base de datos
                $descAccion = ($accion === 'aceptar') 
                    ? 'Cotización aprobada en línea por el cliente' 
                    : 'Cotización rechazada en línea por el cliente (Motivo: ' . $motivo . ')';
                registrarBitacora('cotizaciones', ($accion === 'aceptar' ? 'aprobar_cliente' : 'rechazar_cliente'), $descAccion . ': ' . $cotizacion['codigo'], $id);
                
                // 4. Registrar auditoría en log
                $rutaLog = __DIR__ . '/../../../almacenamiento/logs/auditoria_clientes.log';
                if (!file_exists(dirname($rutaLog))) {
                    @mkdir(dirname($rutaLog), 0777, true);
                }
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
                $userAgent = $_SERVER['HTTP_USER_AGENT'] ?? 'Desconocido';
                $fecha = date('Y-m-d H:i:s');
                $logMsg = "[{$fecha}] Cotización ID: {$id} | Código: {$cotizacion['codigo']} | Acción: {$nuevoEstado} | Motivo: " . ($motivo ?? 'N/A') . " | IP: {$ip} | UA: {$userAgent}\n";
                @file_put_contents($rutaLog, $logMsg, FILE_APPEND);
                
                // 5. Enviar correo de notificación al creador de la cotización
                $destinatarioCreador = !empty($cotizacion['creador_email']) ? $cotizacion['creador_email'] : '';
                $tituloNotificacion = "Decisión del Cliente: Cotización " . $cotizacion['codigo'] . " - " . $nuevoEstado;
                $motivoHtml = '';
                if ($accion === 'rechazar') {
                    $motivoHtml = "
                    <div style=\"background: #fff5f5; padding: 15px; border-radius: 6px; border-left: 4px solid #e53e3e; margin-top: 15px;\">
                        <p style=\"margin: 0; font-weight: bold; color: #c53030;\">Motivo del rechazo:</p>
                        <p style=\"margin: 5px 0 0 0; color: #2d3748;\">" . htmlspecialchars($motivo, ENT_QUOTES, 'UTF-8') . "</p>
                    </div>";
                }
                
                $badgeColor = ($accion === 'aceptar') ? '#10b981' : '#ef4444';
                $badgeText = ($accion === 'aceptar') ? 'APROBADA POR EL CLIENTE' : 'RECHAZADA POR EL CLIENTE';
                
                $mensajeCreador = "
                <html>
                <head>
                  <title>Decisión del Cliente</title>
                </head>
                <body style=\"font-family: Arial, sans-serif; line-height: 1.6; color: #333;\">
                  <div style=\"max-width: 600px; margin: 0 auto; padding: 20px; border: 1px solid #e2e8f0; border-radius: 8px;\">
                    <h2 style=\"color: #103487; border-bottom: 2px solid #103487; padding-bottom: 10px;\">Decisión del Cliente</h2>
                    <p>Estimado <strong>" . htmlspecialchars($cotizacion['creador_nombre'], ENT_QUOTES, 'UTF-8') . "</strong>,</p>
                    <p>Le notificamos que el cliente ha registrado su decisión sobre la cotización <strong>" . htmlspecialchars($cotizacion['codigo'], ENT_QUOTES, 'UTF-8') . "</strong>.</p>
                    
                    <div style=\"text-align: center; margin: 25px 0;\">
                        <span style=\"background-color: {$badgeColor}; color: white; padding: 10px 20px; border-radius: 30px; font-weight: bold; font-size: 14px; display: inline-block; text-transform: uppercase;\">
                            {$badgeText}
                        </span>
                    </div>
                    
                    <div style=\"background: #f8f9fa; padding: 15px; border-radius: 6px; margin: 20px 0; border: 1px solid #e9ecef;\">
                        <p style=\"margin: 5px 0;\"><strong>Código:</strong> " . htmlspecialchars($cotizacion['codigo'], ENT_QUOTES, 'UTF-8') . "</p>
                        <p style=\"margin: 5px 0;\"><strong>Cliente:</strong> " . htmlspecialchars($cotizacion['cliente_nombre'], ENT_QUOTES, 'UTF-8') . "</p>
                        <p style=\"margin: 5px 0;\"><strong>Proyecto:</strong> " . htmlspecialchars($cotizacion['nombre_proyecto'], ENT_QUOTES, 'UTF-8') . "</p>
                        <p style=\"margin: 5px 0;\"><strong>Total:</strong> C$ " . number_format($cotizacion['total'], 2) . "</p>
                        <p style=\"margin: 5px 0;\"><strong>Fecha y Hora:</strong> {$fecha}</p>
                    </div>
                    {$motivoHtml}
                    
                    <p>Puede ver el detalle completo en el panel administrativo de CYCSA.</p>
                    <hr style=\"border: none; border-top: 1px solid #e2e8f0; margin: 20px 0;\">
                    <p style=\"font-size: 14px; font-weight: bold; color: #103487;\">CYCSA Laboratorio de Ensayos</p>
                  </div>
                </body>
                </html>
                ";
                
                if (!empty($destinatarioCreador)) {
                    enviarCorreo($destinatarioCreador, $tituloNotificacion, $mensajeCreador);
                }
                
                // 6. Renderizar pantalla de confirmación exitosa
                $this->renderizarSinLayout('cotizaciones/vistas/decision_cliente', [
                    'titulo' => 'Decisión Registrada con Éxito - CYCSA',
                    'cotizacion' => $cotizacion,
                    'confirmacionExito' => true,
                    'accion_exitosa' => $accion,
                    'motivo' => $motivo
                ]);
            } else {
                $respuesta->establecerCodigoEstado(500);
                die("Error interno al registrar la decisión.");
            }
        }
    }

    public function enviarRevision(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('cotizaciones', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            exit;
        }
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $id = (int)($datos['id'] ?? 0);
            
            // CSRF Check
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $respuesta->redirigir('/Cycsa/publico/cotizaciones/detalle?id=' . $id);
                return;
            }
            
            $modelo = new CotizacionModelo();
            $cot = $modelo->obtenerPorId($id);
            if ($cot && $cot['estado'] === 'Borrador') {
                $modelo->actualizarEstado($id, 'En Revision', $cot['id_usuario_revisor'] ?? $_SESSION['usuario_id'], $cot['motivo_observacion'], $cot['token_seguridad']);
                registrarBitacora('cotizaciones', 'enviar_revision', 'Cotización enviada a revisión de Gerencia: ' . $cot['codigo'], $id);
            }
             $respuesta->redirigir('/Cycsa/publico/cotizaciones/detalle?id=' . $id);
        }
    }

    public function procesarDecisionAdministrativa(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('cotizaciones', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            exit;
        }

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $id = (int)($datos['id'] ?? 0);
            $accion = $datos['accion'] ?? '';
            $motivo_rechazo = trim($datos['motivo_rechazo'] ?? '');
            
            // Validar CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $respuesta->redirigir('/Cycsa/publico/cotizaciones/detalle?id=' . $id);
                return;
            }

            $modelo = new CotizacionModelo();
            $cotizacion = $modelo->obtenerPorId($id);

            if (!$cotizacion || $cotizacion['estado'] !== 'Enviada al Cliente') {
                $respuesta->redirigir('/Cycsa/publico/cotizaciones/detalle?id=' . $id);
                return;
            }

            $nuevoEstado = '';
            $motivo = null;
            $metodo_pago = $datos['metodo_pago'] ?? null;
            $id_banco_cuenta = !empty($datos['id_banco_cuenta']) ? (int)$datos['id_banco_cuenta'] : null;
            $referencia_pago = !empty($datos['referencia_pago']) ? trim($datos['referencia_pago']) : null;
            $porcentaje_pago_inmediato = isset($datos['porcentaje_pago_inmediato']) ? (float)$datos['porcentaje_pago_inmediato'] : 100.00;
            $monto_pago_inmediato = isset($datos['monto_pago_inmediato']) ? (float)$datos['monto_pago_inmediato'] : 0.00;
            $monto_credito = isset($datos['monto_credito']) ? (float)$datos['monto_credito'] : 0.00;
            $efectivo_recibido = !empty($datos['efectivo_recibido']) ? (float)$datos['efectivo_recibido'] : null;
            $efectivo_vuelto = !empty($datos['efectivo_vuelto']) ? (float)$datos['efectivo_vuelto'] : null;
            $dias_credito = !empty($datos['dias_credito']) ? (int)$datos['dias_credito'] : 30;

            if ($accion === 'aceptar') {
                $nuevoEstado = 'Aprobada por Cliente';
            } elseif ($accion === 'rechazar') {
                $nuevoEstado = 'Rechazada por Cliente';
                if (empty($motivo_rechazo)) {
                    $_SESSION['envio_exitoso'] = 'Error: Debe especificar el motivo del rechazo.';
                    $respuesta->redirigir('/Cycsa/publico/cotizaciones/detalle?id=' . $id);
                    return;
                }
                $motivo = $motivo_rechazo;
            } else {
                $respuesta->establecerCodigoEstado(400);
                die("Acción no válida.");
            }

            $exito = $modelo->registrarDecisionCliente(
                $id, 
                $nuevoEstado, 
                $motivo, 
                $metodo_pago, 
                $id_banco_cuenta, 
                $referencia_pago,
                $porcentaje_pago_inmediato,
                $monto_pago_inmediato,
                $monto_credito,
                $efectivo_recibido,
                $efectivo_vuelto,
                $dias_credito
            );

            if ($exito) {
                // Registrar en la bitácora de base de datos
                $descAccion = ($accion === 'aceptar') 
                    ? 'Cotización aprobada por el Administrador en nombre del cliente' 
                    : 'Cotización rechazada por el Administrador en nombre del cliente (Motivo: ' . $motivo . ')';
                registrarBitacora('cotizaciones', ($accion === 'aceptar' ? 'aprobar_admin_cliente' : 'rechazar_admin_cliente'), $descAccion . ': ' . $cotizacion['codigo'], $id);

                // Registrar auditoría en log
                $rutaLog = __DIR__ . '/../../../almacenamiento/logs/auditoria_clientes.log';
                if (!file_exists(dirname($rutaLog))) {
                    @mkdir(dirname($rutaLog), 0777, true);
                }
                $ip = $_SERVER['REMOTE_ADDR'] ?? 'Desconocida';
                $fecha = date('Y-m-d H:i:s');
                $usuarioNombre = $_SESSION['usuario_nombre'] ?? 'Administrador/Vendedor';
                $usuarioId = $_SESSION['usuario_id'] ?? 0;
                
                $logMsg = "[{$fecha}] Cotización ID: {$id} | Código: {$cotizacion['codigo']} | Acción: {$nuevoEstado} (APROBADO EN NOMBRE DEL CLIENTE por {$usuarioNombre} ID: {$usuarioId}) | Motivo: " . ($motivo ?? 'N/A') . " | IP Admin: {$ip}\n";
                @file_put_contents($rutaLog, $logMsg, FILE_APPEND);

                $_SESSION['envio_exitoso'] = "¡Se ha registrado la decisión del cliente ({$nuevoEstado}) correctamente!";
            } else {
                $_SESSION['envio_exitoso'] = "Error al intentar actualizar la decisión de la cotización.";
            }

            $respuesta->redirigir('/Cycsa/publico/cotizaciones/detalle?id=' . $id);
        }
    }

    public function obtenerBitacoraAjax(Peticion $peticion, Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->enviarJson(['error' => 'No autorizado'], 403);
            return;
        }
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            $respuesta->enviarJson([]);
            return;
        }
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $stmt = $db->prepare("SELECT id, usuario_nombre, accion, descripcion, ip, fecha_creacion FROM bitacora WHERE modulo = 'cotizaciones' AND id_referencia = :id ORDER BY id ASC");
        $stmt->execute(['id' => $id]);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        foreach ($logs as &$log) {
            $log['fecha_amigable'] = date('d/m/Y h:i A', strtotime($log['fecha_creacion']));
        }
        
        $respuesta->enviarJson($logs);
    }

    public function imprimir(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('cotizaciones', 'ver')) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
        $id = (int)($_GET['id'] ?? 0);
        $completo = (int)($_GET['completo'] ?? 0);

        $modelo = new CotizacionModelo();
        $cotizacion = $modelo->obtenerPorId($id);
        if (!$cotizacion) { 
            $respuesta->redirigir('/Cycsa/publico/cotizaciones'); 
            return; 
        }
        
        $detalles = $modelo->obtenerDetalles($id);
        
        if ($completo === 1) {
            $pdfContenido = generarCotizacionCompletaPDF($cotizacion, $detalles);
        } else {
            $pdfContenido = generarCotizacionPDF($cotizacion, $detalles);
        }
        
        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Cotizacion_' . $cotizacion['codigo'] . ($completo ? '_Completa' : '') . '.pdf"');
        echo $pdfContenido;
        exit;
    }

    private function procesarDetalles($datos): array {
        $detalles = [];
        for ($i = 0; $i < count($datos['ensayo_desc'] ?? []); $i++) {
            if (!empty(trim($datos['ensayo_desc'][$i]))) {
                $id_prod = !empty($datos['ensayo_id_producto'][$i]) ? (int)$datos['ensayo_id_producto'][$i] : null;
                $cant = (float)$datos['ensayo_cant'][$i]; $prec = (float)$datos['ensayo_precio'][$i];
                $detalles[] = [
                    'id_producto' => $id_prod,
                    'descripcion' => trim($datos['ensayo_desc'][$i]), 
                    'codigo_servicio' => !empty(trim($datos['ensayo_codigo'][$i] ?? '')) ? trim($datos['ensayo_codigo'][$i]) : null,
                    'norma_astm' => !empty(trim($datos['ensayo_norma'][$i] ?? '')) ? trim($datos['ensayo_norma'][$i]) : null,
                    'formato_reporte' => !empty(trim($datos['ensayo_formato'][$i] ?? '')) ? trim($datos['ensayo_formato'][$i]) : null,
                    'observaciones' => !empty(trim($datos['ensayo_obs'][$i] ?? '')) ? trim($datos['ensayo_obs'][$i]) : null,
                    'cantidad' => $cant, 
                    'precio' => $prec, 
                    'subtotal' => $cant * $prec
                ];
            }
        }
        return $detalles;
    }

    public function guardarResultadosItem(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('cotizaciones', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            exit;
        }

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $id_detalle = (int)($datos['id_detalle'] ?? 0);
            $id_cotizacion = (int)($datos['id_cotizacion'] ?? 0);
            $resultados_json = $datos['resultados_json'] ?? '';

            $redir = !empty($datos['redireccionar_a']) ? $datos['redireccionar_a'] : '/Cycsa/publico/cotizaciones/detalle?id=' . $id_cotizacion;

            if ($id_detalle <= 0) {
                $_SESSION['error'] = 'Detalle inválido.';
                $respuesta->redirigir($redir);
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir($redir);
                return;
            }

            // Update database
            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $stmt = $db->prepare("UPDATE cotizacion_detalles SET resultados_json = :json WHERE id = :id");
            $exito = $stmt->execute(['json' => $resultados_json, 'id' => $id_detalle]);

            if ($exito) {
                $_SESSION['exito'] = 'Resultados de ensayo guardados exitosamente.';
            } else {
                $_SESSION['error'] = 'Error al guardar los resultados de ensayo.';
            }

            $respuesta->redirigir($redir);
        }
    }

    public function imprimirReporteItem(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('cotizaciones', 'ver')) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }

        $id_detalle = (int)($_GET['id_detalle'] ?? 0);
        if ($id_detalle <= 0) {
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            return;
        }

        $modelo = new CotizacionModelo();
        $detalle = $modelo->obtenerDetallePorId($id_detalle);
        if (!$detalle) {
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            return;
        }

        $cotizacion = $modelo->obtenerPorId((int)$detalle['id_cotizacion']);
        if (!$cotizacion) {
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            return;
        }

        // Get format columns
        $columnas = $this->obtenerColumnasFormato($detalle['archivo_markdown']);
        if (empty($columnas)) {
            $columnas = ["Código laboratorio", "Nombre muestra", "Resultado"];
        }

        // Parse saved rows
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $stmtCount = $db->prepare("SELECT COUNT(*) FROM ensayo_edades WHERE id_detalle_cotizacion = :id_detalle");
        $stmtCount->execute(['id_detalle' => $id_detalle]);
        $esEnsayoEdades = ((int)$stmtCount->fetchColumn() > 0);

        if ($esEnsayoEdades) {
            $stmtLoteId = $db->prepare("SELECT id_lote FROM ensayo_edades WHERE id_detalle_cotizacion = :id_detalle LIMIT 1");
            $stmtLoteId->execute(['id_detalle' => $id_detalle]);
            $idLote = (int)$stmtLoteId->fetchColumn();

            $stmtLote = $db->prepare("SELECT lm.*, rm.codigo_muestra, rm.codigo_campo 
                                      FROM lotes_muestras lm
                                      JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                                      WHERE lm.id = :id_lote");
            $stmtLote->execute(['id_lote' => $idLote]);
            $loteData = $stmtLote->fetch(PDO::FETCH_ASSOC);

            $stmtEsp = $db->prepare("SELECT * FROM ensayo_edades WHERE id_detalle_cotizacion = :id_detalle ORDER BY edad_dias ASC, identificador_especimen ASC");
            $stmtEsp->execute(['id_detalle' => $id_detalle]);
            $especimenesList = $stmtEsp->fetchAll(PDO::FETCH_ASSOC);

            $filas = [];
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

        $pdfContenido = generarReporteEnsayoPDF($cotizacion, $detalle, $columnas, $filas);

        header('Content-Type: application/pdf');
        header('Content-Disposition: inline; filename="Reporte_' . str_replace(' ', '_', $detalle['formato_nombre']) . '_' . $cotizacion['codigo'] . '.pdf"');
        echo $pdfContenido;
        exit;
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
}