<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;

/**
 * Envía un correo electrónico utilizando PHPMailer.
 * Si MAIL_HOST está configurado en .env, usa SMTP (ideal para desarrollo local).
 * Si no, usa la función mail() nativa (ideal para producción en Bluehost).
 */
function enviarCorreo(string $para, string $asunto, string $cuerpoHTML, string $cuerpoTexto = '', array $adjuntos = []): bool {
    $mail = new PHPMailer(true);

    try {
        // Configuraciones generales
        $mail->CharSet = 'UTF-8';
        $remitenteCorreo = $_ENV['MAIL_FROM'] ?? 'noreply@cycsa.com';
        $remitenteNombre = $_ENV['APP_NAME'] ?? 'CYCSA';
        $mail->setFrom($remitenteCorreo, $remitenteNombre);
        $mail->addAddress($para);

        // Contenido
        $mail->isHTML(true);
        $mail->Subject = $asunto;
        $mail->Body    = $cuerpoHTML;
        if (!empty($cuerpoTexto)) {
            $mail->AltBody = $cuerpoTexto;
        }

        // Agregar archivos adjuntos
        foreach ($adjuntos as $adjunto) {
            if (isset($adjunto['contenido'])) {
                $mail->addStringAttachment($adjunto['contenido'], $adjunto['nombre'] ?? 'documento.pdf');
            } elseif (isset($adjunto['ruta'])) {
                $mail->addAttachment($adjunto['ruta'], $adjunto['nombre'] ?? '');
            }
        }

        // Configuración de transporte
        $mailHost = $_ENV['MAIL_HOST'] ?? '';
        if (!empty($mailHost)) {
            $mail->isSMTP();
            $mail->Host       = $mailHost;
            $mail->SMTPAuth   = true;
            $mail->Username   = $_ENV['MAIL_USER'] ?? '';
            $mail->Password   = $_ENV['MAIL_PASS'] ?? '';
            
            $seguridad = strtolower($_ENV['MAIL_SECURE'] ?? '');
            if ($seguridad === 'ssl') {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = $_ENV['MAIL_PORT'] ?? 465;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $_ENV['MAIL_PORT'] ?? 587;
            }
        } else {
            // Uso de la función mail() local en servidores de hosting (Bluehost)
            $mail->isMail();
        }

        return $mail->send();
    } catch (Exception $e) {
        error_log("Error al enviar correo mediante PHPMailer: " . $mail->ErrorInfo);
        return false;
    }
}

/**
 * Genera el contenido binario de una cotización en formato PDF usando Dompdf.
 */
function generarCotizacionPDF(array $cotizacion, array $detalles): string {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $dompdf = new Dompdf($options);

    $fecha = date('d/m/Y', strtotime($cotizacion['fecha_creacion']));
    $subtotal = number_format($cotizacion['subtotal'], 2);
    $impuesto = number_format($cotizacion['impuesto'], 2);
    $total = number_format($cotizacion['total'], 2);

    $rowsHtml = '';
    foreach ($detalles as $det) {
        $desc = htmlspecialchars($det['descripcion_ensayo'] ?? '', ENT_QUOTES, 'UTF-8');
        $cant = number_format($det['cantidad'], 2);
        $precio = number_format($det['precio_unitario'], 2);
        $sub = number_format($det['subtotal'], 2);

        $metaHtml = '';
        if (!empty($det['codigo_servicio'])) {
            $metaHtml .= "<span style=\"color:#475569; font-size:9px;\">Código: <strong>" . htmlspecialchars($det['codigo_servicio'], ENT_QUOTES, 'UTF-8') . "</strong></span>";
        }
        if (!empty($det['norma_astm'])) {
            if ($metaHtml) $metaHtml .= " &bull; ";
            $metaHtml .= "<span style=\"color:#475569; font-size:9px;\">Norma: <strong>" . htmlspecialchars($det['norma_astm'], ENT_QUOTES, 'UTF-8') . "</strong></span>";
        }
        if (!empty($det['formato_reporte'])) {
            if ($metaHtml) $metaHtml .= " &bull; ";
            $metaHtml .= "<span style=\"color:#475569; font-size:9px;\">Formato Reporte: <strong>" . htmlspecialchars($det['formato_reporte'], ENT_QUOTES, 'UTF-8') . "</strong></span>";
        }
        
        if ($metaHtml) {
            $desc = "<strong>{$desc}</strong><div style=\"margin-top: 3px; padding-top: 2px; border-top: 1px dashed #e2e8f0; font-size: 9px;\">{$metaHtml}</div>";
        }

        $rowsHtml .= "
        <tr>
            <td style=\"border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 11px;\">{$desc}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 11px; text-align: right;\">{$cant}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 11px; text-align: right;\">C$ {$precio}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 11px; text-align: right; font-weight: bold;\">C$ {$sub}</td>
        </tr>";
    }

    $configNotas = json_decode($cotizacion['configuracion_notas'] ?? '', true) ?: [];
    $notasDisponibles = [
        'concreto' => '<strong>Muestreo de Concreto (Cilindros):</strong> El cliente deberá entregar los cilindros de concreto debidamente identificados (Nombre, Ubicación, Resistencia, Revenimiento) y de dimensiones estándar CYCSA-PE-07 (4"x8" o 6"x12").',
        'trae_muestra' => '<strong>Entrega de Muestras:</strong> El cliente traerá las muestras a las instalaciones del Laboratorio CYCSA Km 83.5 Carretera León-Managua.',
        'laboratorio_lleno' => '<strong>Condición de Tiempos:</strong> Los tiempos de entrega aplican a partir del ingreso de las muestras. La disponibilidad deberá ser consultada al momento de la entrega debido a variaciones en la carga del laboratorio.',
        'minimo_muestreo' => '<strong>Programación de Muestreo:</strong> Se requiere un cargo mínimo de C$ 4,400.00 más movilización para programar muestreos. Programación con un mínimo de 2 días hábiles de anticipación.'
    ];

    $htmlNotas = '';
    foreach ($configNotas as $clave => $seleccionada) {
        if ($seleccionada && isset($notasDisponibles[$clave])) {
            $htmlNotas .= "<li style=\"margin-bottom: 5px;\">{$notasDisponibles[$clave]}</li>";
        }
    }

    $notasSeccion = '';
    if (!empty($htmlNotas)) {
        $notasSeccion = "
        <div style=\"margin-top: 15px;\">
            <h4 style=\"margin: 0 0 5px 0; color: #103487; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px;\">Notas y Leyendas de Servicio</h4>
            <ul style=\"margin: 0; padding-left: 15px; font-size: 9px; color: #475569; line-height: 1.3;\">
                {$htmlNotas}
            </ul>
        </div>";
    }

    $clienteNombre = htmlspecialchars($cotizacion['cliente_nombre'] ?? '', ENT_QUOTES, 'UTF-8');
    $clienteRuc = htmlspecialchars($cotizacion['cliente_ruc'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
    $atencionA = htmlspecialchars($cotizacion['atencion_a'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
    $proyectoNombre = htmlspecialchars($cotizacion['nombre_proyecto'] ?? '', ENT_QUOTES, 'UTF-8');
    $proyectoDireccion = htmlspecialchars($cotizacion['direccion_proyecto'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
    $prioridad = htmlspecialchars($cotizacion['prioridad'] ?? 'Normal', ENT_QUOTES, 'UTF-8');
    $codigo = htmlspecialchars($cotizacion['codigo'] ?? '', ENT_QUOTES, 'UTF-8');
    $version = htmlspecialchars($cotizacion['version'] ?? '1', ENT_QUOTES, 'UTF-8');
    $condicionPago = htmlspecialchars($cotizacion['condicion_pago'] ?? '', ENT_QUOTES, 'UTF-8');
    $tiempoEntrega = htmlspecialchars($cotizacion['tiempo_entrega'] ?? '', ENT_QUOTES, 'UTF-8');
    $vigenciaOferta = htmlspecialchars($cotizacion['vigencia_oferta'] ?? '', ENT_QUOTES, 'UTF-8');
    $creadorNombre = htmlspecialchars($cotizacion['creador_nombre'] ?? 'Asesor Comercial', ENT_QUOTES, 'UTF-8');

    $html = "
    <!DOCTYPE html>
    <html lang=\"es\">
    <head>
        <meta charset=\"UTF-8\">
        <style>
            body {
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                color: #1e293b;
                line-height: 1.4;
                font-size: 11px;
                margin: 0;
                padding: 0;
            }
            .totals-table td {
                padding: 4px 8px;
                font-size: 11px;
            }
        </style>
    </head>
    <body>
        <!-- Header Table -->
        <table style=\"width: 100%; border-bottom: 2px solid #103487; padding-bottom: 10px; margin-bottom: 15px; border-collapse: collapse;\">
            <tr>
                <td style=\"width: 60%; vertical-align: top;\">
                    <span style=\"font-size: 24px; font-weight: bold; color: #103487;\">CYCSA</span><br>
                    <span style=\"font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold;\">Laboratorio de Ensayos y Control de Calidad</span><br>
                    <span style=\"font-size: 8px; color: #64748b; line-height: 1.2;\">
                        Km 83.5 Carretera León-Managua, León, Nicaragua<br>
                        Teléfono: +505 2244-1234 | Correo: info@cycsalabs.com
                    </span>
                </td>
                <td style=\"width: 40%; text-align: right; vertical-align: top;\">
                    <span style=\"font-size: 14px; font-weight: bold; color: #103487;\">COTIZACIÓN DE SERVICIO</span><br>
                    <span style=\"font-size: 16px; font-weight: bold; color: #1e293b; margin: 2px 0; display: block;\">{$codigo}</span><br>
                    <span style=\"font-size: 9px; color: #64748b;\">Versión: {$version} | Fecha: {$fecha}</span>
                </td>
            </tr>
        </table>

        <!-- Info Cards Side by Side -->
        <table style=\"width: 100%; margin-bottom: 15px; border-collapse: collapse;\">
            <tr>
                <td style=\"width: 50%; padding-right: 8px; vertical-align: top;\">
                    <div style=\"background: #f8fafc; padding: 10px; border: 1px solid #e2e8f0; border-radius: 4px;\">
                        <h4 style=\"margin: 0 0 6px 0; color: #103487; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; font-size: 10px; text-transform: uppercase;\">Datos del Cliente</h4>
                        <span style=\"font-size: 9px; color: #64748b;\">CLIENTE:</span> <strong style=\"color: #1e293b;\">{$clienteNombre}</strong><br>
                        <span style=\"font-size: 9px; color: #64748b;\">RUC / CÉDULA:</span> <span style=\"color: #1e293b;\">{$clienteRuc}</span><br>
                        <span style=\"font-size: 9px; color: #64748b;\">ATENCIÓN A:</span> <span style=\"color: #1e293b;\">{$atencionA}</span>
                    </div>
                </td>
                <td style=\"width: 50%; padding-left: 8px; vertical-align: top;\">
                    <div style=\"background: #f8fafc; padding: 10px; border: 1px solid #e2e8f0; border-radius: 4px;\">
                        <h4 style=\"margin: 0 0 6px 0; color: #103487; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px; font-size: 10px; text-transform: uppercase;\">Datos del Proyecto</h4>
                        <span style=\"font-size: 9px; color: #64748b;\">PROYECTO:</span> <strong style=\"color: #1e293b;\">{$proyectoNombre}</strong><br>
                        <span style=\"font-size: 9px; color: #64748b;\">DIRECCIÓN:</span> <span style=\"color: #1e293b;\">{$proyectoDireccion}</span><br>
                        <span style=\"font-size: 9px; color: #64748b;\">PRIORIDAD:</span> <span style=\"color: #1e293b;\">{$prioridad}</span>
                    </div>
                </td>
            </tr>
        </table>

        <!-- Table of Items -->
        <table style=\"width: 100%; border-collapse: collapse; margin-bottom: 15px;\">
            <thead>
                <tr style=\"background-color: #f1f5f9;\">
                    <th style=\"border: 1px solid #cbd5e1; padding: 6px 8px; text-align: left; font-size: 10px; color: #475569; text-transform: uppercase; font-weight: bold;\">Descripción del Ensayo / Servicio</th>
                    <th style=\"border: 1px solid #cbd5e1; padding: 6px 8px; text-align: right; font-size: 10px; color: #475569; text-transform: uppercase; font-weight: bold; width: 50px;\">Cant.</th>
                    <th style=\"border: 1px solid #cbd5e1; padding: 6px 8px; text-align: right; font-size: 10px; color: #475569; text-transform: uppercase; font-weight: bold; width: 90px;\">Precio Unit.</th>
                    <th style=\"border: 1px solid #cbd5e1; padding: 6px 8px; text-align: right; font-size: 10px; color: #475569; text-transform: uppercase; font-weight: bold; width: 110px;\">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                {$rowsHtml}
            </tbody>
        </table>

        <!-- Financial Totals -->
        <table style=\"width: 100%; border-collapse: collapse; margin-bottom: 15px;\">
            <tr>
                <td style=\"width: 60%;\"></td>
                <td style=\"width: 40%; vertical-align: top;\">
                    <table class=\"totals-table\" style=\"width: 100%; border-collapse: collapse; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px;\">
                        <tr>
                            <td style=\"text-align: right; color: #64748b;\">Subtotal:</td>
                            <td style=\"text-align: right; font-weight: bold; width: 110px;\">C$ {$subtotal}</td>
                        </tr>
                        <tr>
                            <td style=\"text-align: right; color: #64748b;\">IVA (15%):</td>
                            <td style=\"text-align: right; font-weight: bold;\">C$ {$impuesto}</td>
                        </tr>
                        <tr style=\"background: #e6eefc; border-top: 1px solid #cbd5e1;\">
                            <td style=\"text-align: right; color: #103487; font-weight: bold; padding: 6px 8px;\">TOTAL:</td>
                            <td style=\"text-align: right; color: #103487; font-weight: bold; padding: 6px 8px;\">C$ {$total}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Commercial Conditions -->
        <div style=\"background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 8px 12px; margin-bottom: 15px;\">
            <h4 style=\"margin: 0 0 6px 0; color: #103487; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px;\">Condiciones Comerciales</h4>
            <table style=\"width: 100%; font-size: 10px; border-collapse: collapse;\">
                <tr>
                    <td style=\"width: 33.3%;\"><span style=\"color: #64748b;\">Condición de Pago:</span><br><strong>{$condicionPago}</strong></td>
                    <td style=\"width: 33.3%;\"><span style=\"color: #64748b;\">Tiempo de Entrega:</span><br><strong>{$tiempoEntrega}</strong></td>
                    <td style=\"width: 33.4%;\"><span style=\"color: #64748b;\">Vigencia de Oferta:</span><br><strong>{$vigenciaOferta}</strong></td>
                </tr>
            </table>
        </div>

        <!-- Notes and Legends -->
        {$notasSeccion}

        <!-- Signature Section -->
        <table style=\"width: 100%; margin-top: 35px; border-collapse: collapse;\">
            <tr>
                <td style=\"width: 45%; text-align: center; vertical-align: bottom;\">
                    <div style=\"border-top: 1px solid #cbd5e1; width: 85%; margin: 0 auto; padding-top: 4px; font-size: 9px; color: #475569;\">
                        <strong>Preparado por:</strong><br>
                        {$creadorNombre}<br>
                        CYCSA Laboratorio
                    </div>
                </td>
                <td style=\"width: 10%;\"></td>
                <td style=\"width: 45%; text-align: center; vertical-align: bottom;\">
                    <div style=\"border-top: 1px solid #cbd5e1; width: 85%; margin: 0 auto; padding-top: 4px; font-size: 9px; color: #475569;\">
                        <strong>Aceptado por el Cliente:</strong><br>
                        Firma / Sello Autorizado<br>
                        Fecha: ____/____/______
                    </div>
                </td>
            </tr>
        </table>
    </body>
    </html>";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();
    return $dompdf->output();
}

/**
 * Verifica si el usuario logueado tiene permiso para acceder a un módulo o realizar una acción.
 */
function tienePermiso(string $modulo, string $accion = 'ver'): bool {
    if (!isset($_SESSION['usuario_rol'])) {
        return false;
    }
    
    // Administrador (Rol 1) siempre tiene acceso total
    if ($_SESSION['usuario_rol'] == 1) {
        return true;
    }
    
    // Si no es Administrador ni Vendedor, denegar
    if ($_SESSION['usuario_rol'] != 2) {
        return false;
    }
    
    // Gestión de usuarios es estrictamente para Administradores
    if ($modulo === 'usuarios') {
        return false;
    }
    
    $permisos = $_SESSION['usuario_permisos'] ?? [];
    if (is_string($permisos)) {
        $permisos = json_decode($permisos, true);
    }
    
    return isset($permisos[$modulo][$accion]) && ($permisos[$modulo][$accion] == 1 || $permisos[$modulo][$accion] === true);
}

/**
 * Retorna la URL base del sitio de forma dinámica según el host actual.
 */
function obtenerBaseUrl(): string {
    if (isset($_SERVER['HTTP_HOST'])) {
        $protocolo = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') ? 'https' : 'http';
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        $dir = dirname($scriptPath);
        $dir = str_replace('\\', '/', $dir);
        $dir = rtrim($dir, '/');
        return "{$protocolo}://{$_SERVER['HTTP_HOST']}{$dir}";
    }
    return $_ENV['APP_URL'] ?? 'http://localhost/Cycsa/publico';
}
