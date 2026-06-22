<?php

namespace Cycsa\Modulos\Cotizaciones\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Cotizaciones\Modelos\CotizacionModelo;
use Cycsa\Modulos\Clientes\Modelos\ClienteModelo;
use Cycsa\Modulos\Productos\Modelos\ProductoModelo;

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
        
        $this->renderizar('cotizaciones/vistas/index', [
            'titulo' => 'Cotizaciones - Cycsa',
            'cotizaciones' => $cotizaciones,
            'busqueda' => $busqueda,
            'tabActual' => $tab
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
        $this->renderizar('cotizaciones/vistas/crear', [
            'titulo' => 'Nueva Cotización', 
            'clientes' => (new ClienteModelo())->obtenerTodos(),
            'productos' => $prodModelo->obtenerTodos(),
            'categorias' => $prodModelo->obtenerCategorias()
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
            $cabecera = [ 'codigo' => $modelo->generarCodigoUnico(), 'id_cliente' => $datos['id_cliente'], 'id_usuario_creador' => $_SESSION['usuario_id'], 'atencion_a' => trim($datos['atencion_a']), 'nombre_proyecto' => trim($datos['nombre_proyecto']), 'direccion_proyecto' => trim($datos['direccion_proyecto']), 'prioridad' => $datos['prioridad'] ?? 'Normal', 'fecha_limite' => !empty($datos['fecha_limite']) ? $datos['fecha_limite'] : null, 'condicion_pago' => $datos['condicion_pago'], 'tiempo_entrega' => trim($datos['tiempo_entrega']), 'vigencia_oferta' => trim($datos['vigencia_oferta']), 'configuracion_notas' => $notasJson, 'subtotal' => $datos['subtotal_general'], 'impuesto' => $datos['impuesto_general'], 'total' => $datos['total_general'] ];
            $detalles = $this->procesarDetalles($datos);
            if ($modelo->guardarCotizacionCompleta($cabecera, $detalles)) $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            else $respuesta->redirigir('/Cycsa/publico/cotizaciones/crear');
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

        $this->renderizar('cotizaciones/vistas/detalle', [
            'titulo' => 'Detalle', 
            'cotizacion' => $cotizacion, 
            'detalles' => $modelo->obtenerDetalles($id),
            'versiones' => $modelo->obtenerVersiones($id)
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
        if ($cot['estado'] !== 'Observada') { $respuesta->redirigir('/Cycsa/publico/cotizaciones/detalle?id='.$id); return; }
        $prodModelo = new ProductoModelo();
        $this->renderizar('cotizaciones/vistas/editar', [
            'cotizacion' => $cot, 
            'detalles' => $modelo->obtenerDetalles($id), 
            'clientes' => (new ClienteModelo())->obtenerTodos(),
            'productos' => $prodModelo->obtenerTodos(),
            'categorias' => $prodModelo->obtenerCategorias()
        ]);
    }

    public function actualizar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('cotizaciones', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            exit;
        }
        $datos = $peticion->obtenerDatos();
        $modelo = new CotizacionModelo();
        $cabecera = [ 'atencion_a' => trim($datos['atencion_a']), 'nombre_proyecto' => trim($datos['nombre_proyecto']), 'direccion_proyecto' => trim($datos['direccion_proyecto']), 'condicion_pago' => $datos['condicion_pago'], 'tiempo_entrega' => trim($datos['tiempo_entrega']), 'vigencia_oferta' => trim($datos['vigencia_oferta']), 'subtotal' => $datos['subtotal_general'], 'impuesto' => $datos['impuesto_general'], 'total' => $datos['total_general'] ];
        if ($modelo->actualizarCotizacionCompleta((int)$datos['id'], $cabecera, $this->procesarDetalles($datos))) $respuesta->redirigir('/Cycsa/publico/cotizaciones');
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
                $detalles = $modelo->obtenerDetalles($id);
                $pdfContenido = generarCotizacionPDF($cotizacion, $detalles);
                
                $destinatario = !empty($cotizacion['cliente_email']) ? $cotizacion['cliente_email'] : 'abdiasl085@gmail.com';
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
                
                enviarCorreo($destinatario, $titulo_correo, $mensaje, '', $adjuntos);
                
                // Registro local en logs de desarrollo
                $rutaLog = __DIR__ . '/../../../almacenamiento/logs/emails.log';
                if (!file_exists(dirname($rutaLog))) {
                    @mkdir(dirname($rutaLog), 0777, true);
                }
                $logMsg = "[" . date('Y-m-d H:i:s') . "] Cotización ENVIADA al Cliente (Aprobación). Destinatario: {$destinatario} | Cotización: {$cotizacion['codigo']} | Monto: C$ " . number_format($cotizacion['total'], 2) . "\n";
                @file_put_contents($rutaLog, $logMsg, FILE_APPEND);
            }
        } elseif ($datos['accion'] === 'observar') {
            $modelo->actualizarEstado($id, 'Observada', $_SESSION['usuario_id'], $datos['motivo_observacion'], null);
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
            if (empty($token)) {
                $token = bin2hex(random_bytes(32));
                $modelo->actualizarEstado($id, 'Enviada al Cliente', $cotizacion['id_usuario_revisor'] ?? $_SESSION['usuario_id'], $cotizacion['motivo_observacion'], $token);
            } elseif ($cotizacion['estado'] === 'Aprobada Internamente') {
                $modelo->actualizarEstado($id, 'Enviada al Cliente', $cotizacion['id_usuario_revisor'] ?? $_SESSION['usuario_id'], $cotizacion['motivo_observacion'], $token);
            }
            
            // Obtener datos actualizados y detalles para generar el PDF
            $cotizacion = $modelo->obtenerPorId($id);
            $detalles = $modelo->obtenerDetalles($id);
            $pdfContenido = generarCotizacionPDF($cotizacion, $detalles);
            
            $destinatario = !empty($cotizacion['cliente_email']) ? $cotizacion['cliente_email'] : 'abdiasl085@gmail.com';
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
            
            // Envío real mediante PHPMailer con adjunto PDF
            enviarCorreo($destinatario, $titulo_correo, $mensaje, '', $adjuntos);
            
            // Registro local
            $rutaLog = __DIR__ . '/../../../almacenamiento/logs/emails.log';
            if (!file_exists(dirname($rutaLog))) {
                @mkdir(dirname($rutaLog), 0777, true);
            }
            $logMsg = "[" . date('Y-m-d H:i:s') . "] Cotización ENVIADA al Cliente. Destinatario: {$destinatario} | Cotización: {$cotizacion['codigo']} | Monto: C$ " . number_format($cotizacion['total'], 2) . "\n";
            @file_put_contents($rutaLog, $logMsg, FILE_APPEND);
            
            $_SESSION['envio_exitoso'] = "¡Cotización enviada con éxito al correo del cliente ({$destinatario})!";
            
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
        
        // Si el estado ya es 'Aprobada por Cliente' o 'Rechazada por Cliente'
        if ($cotizacion['estado'] === 'Aprobada por Cliente' || $cotizacion['estado'] === 'Rechazada por Cliente') {
            $detalles = $modelo->obtenerDetalles($id);
            $this->renderizarSinLayout('cotizaciones/vistas/decision_cliente', [
                'titulo' => 'Cotización Procesada - CYCSA',
                'cotizacion' => $cotizacion,
                'detalles' => $detalles,
                'soloLectura' => true,
                'token' => $token
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
            'token' => $token
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
            if ($accion === 'aceptar') {
                $nuevoEstado = 'Aprobada por Cliente';
            } elseif ($accion === 'rechazar') {
                $nuevoEstado = 'Rechazada por Cliente';
                if (empty($motivo_rechazo)) {
                    $this->renderizarSinLayout('cotizaciones/vistas/decision_cliente', [
                        'titulo' => 'Error - CYCSA',
                        'cotizacion' => $cotizacion,
                        'detalles' => $modelo->obtenerDetalles($id),
                        'soloLectura' => false,
                        'csrf_token' => $_SESSION['client_csrf_token'],
                        'token' => $token,
                        'error' => 'Debe especificar el motivo del rechazo para poder procesar la solicitud.'
                    ]);
                    return;
                }
                $motivo = $motivo_rechazo;
            } else {
                $respuesta->establecerCodigoEstado(400);
                die("Acción no válida.");
            }
            
            $exito = $modelo->registrarDecisionCliente($id, $nuevoEstado, $motivo);
            
            if ($exito) {
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
                $destinatarioCreador = !empty($cotizacion['creador_email']) ? $cotizacion['creador_email'] : 'abdiasl085@gmail.com';
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
                
                enviarCorreo($destinatarioCreador, $tituloNotificacion, $mensajeCreador);
                
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
            }
            $respuesta->redirigir('/Cycsa/publico/cotizaciones/detalle?id=' . $id);
        }
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
                    'cantidad' => $cant, 
                    'precio' => $prec, 
                    'subtotal' => $cant * $prec
                ];
            }
        }
        return $detalles;
    }
}