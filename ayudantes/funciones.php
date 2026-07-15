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

    $logoPath = __DIR__ . '/../publico/img/logo_cycsa.jpg';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
    }

    $bgPath = __DIR__ . '/../publico/img/hoja_vertical.jpg';
    $bgBase64 = '';
    if (file_exists($bgPath)) {
        $bgBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($bgPath));
    }

    $qrPath = __DIR__ . '/../publico/img/qr_terminos.png';
    $qrBase64 = '';
    if (file_exists($qrPath)) {
        $qrBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($qrPath));
    }

    $logoHtml = '';
    if (!extension_loaded('gd') || empty($bgBase64)) {
        if (!empty($logoBase64)) {
            $logoHtml = '<img src="' . $logoBase64 . '" style="height: 38px; margin-bottom: 4px;"><br>';
        } else {
            $logoHtml = '<span style="font-size: 24px; font-weight: bold; color: #103487;">CYCSA</span><br>';
        }
        $logoHtml .= '
            <span style="font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; display: block; margin-bottom: 2px;">Laboratorio de Ensayos y Control de Calidad</span>
            <span style="font-size: 7.5px; color: #64748b; line-height: 1.2;">
                Km 83.5 Carretera León-Managua, León, Nicaragua<br>
                Teléfono: +505 2244-1234 | Correo: info@cycsalabs.com
            </span>';
    }

    $bodyStyle = "margin: 0; padding: 1.5cm;";
    if (extension_loaded('gd') && !empty($bgBase64)) {
        $bodyStyle = "margin: 0; padding: 3.2cm 2.2cm 2.2cm 2.2cm; background-image: url('{$bgBase64}'); background-size: 100% 100%; background-repeat: no-repeat;";
    }

    $fecha = date('d/m/Y', strtotime($cotizacion['fecha_creacion']));
    $simboloMoneda = ((int)($cotizacion['tipo_moneda'] ?? 1) === 2) ? '$' : 'C$';
    $subtotal = number_format($cotizacion['subtotal'], 2, '.', ',');
    $impuesto = number_format($cotizacion['impuesto'], 2, '.', ',');
    $total = number_format($cotizacion['total'], 2, '.', ',');

    $descuentoMonto = (float)($cotizacion['descuento'] ?? 0);
    $descuentoHtml = '';
    $netoMonto = (float)$cotizacion['subtotal'] - $descuentoMonto;
    $neto = number_format($netoMonto, 2, '.', ',');
    if ($descuentoMonto > 0) {
        $descuentoVal = number_format($descuentoMonto, 2, '.', ',');
        $descuentoHtml = "
        <tr>
            <td style=\"text-align: right; color: #dc2626; padding: 4px 8px; font-size: 11px;\">Monto Descontado:</td>
            <td style=\"text-align: right; font-weight: bold; color: #dc2626; padding: 4px 8px; font-size: 11px;\">-{$simboloMoneda} {$descuentoVal}</td>
        </tr>
        <tr>
            <td style=\"text-align: right; color: #334155; padding: 4px 8px; font-size: 11px; font-weight: 600;\">Precio con Descuento:</td>
            <td style=\"text-align: right; font-weight: bold; color: #0f172a; padding: 4px 8px; font-size: 11px;\">{$simboloMoneda} {$neto}</td>
        </tr>";
    }

    $ivaLabel = "IVA (15%):";
    if ((int)($cotizacion['exonerado'] ?? 0)) {
        $exNo = !empty($cotizacion['exoneracion_no']) ? ' (' . htmlspecialchars($cotizacion['exoneracion_no'], ENT_QUOTES, 'UTF-8') . ')' : '';
        $ivaLabel = "IVA (Exonerado{$exNo}):";
    }

    $rowsHtml = '';
    foreach ($detalles as $det) {
        $descText = htmlspecialchars($det['descripcion_ensayo'] ?? '', ENT_QUOTES, 'UTF-8');
        $codigoServicio = !empty($det['codigo_servicio']) ? htmlspecialchars($det['codigo_servicio'], ENT_QUOTES, 'UTF-8') : 'N/A';
        
        $cant = number_format($det['cantidad'], 2, '.', ',');
        $precio = number_format($det['precio_unitario'], 2, '.', ',');
        $sub = number_format($det['subtotal'], 2, '.', ',');

        $metaHtml = '';
        if (!empty($det['observaciones'])) {
            $metaHtml .= "<span style=\"color:#475569; font-size:9px;\">Tiempo Entrega: <strong>" . htmlspecialchars($det['observaciones'], ENT_QUOTES, 'UTF-8') . "</strong></span>";
        }
        
        $desc = $descText;
        if ($metaHtml) {
            $desc = "<strong>{$desc}</strong><div style=\"margin-top: 3px; padding-top: 2px; border-top: 1px dashed #e2e8f0; font-size: 9px;\">{$metaHtml}</div>";
        } else {
            $desc = "<strong>{$desc}</strong>";
        }

        $rowsHtml .= "
        <tr>
            <td style=\"border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 10px; font-family: monospace; text-align: center; color: #334155; font-weight: bold;\">{$codigoServicio}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 11px;\">{$desc}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 11px; text-align: right;\">{$cant}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 11px; text-align: right;\">{$simboloMoneda} {$precio}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 11px; text-align: right; font-weight: bold;\">{$simboloMoneda} {$sub}</td>
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

    $contactosRaw = $cotizacion['contactos'] ?? '';
    $contactosHtml = '';
    if (!empty($contactosRaw)) {
        $lineas = explode("\n", $contactosRaw);
        $lineasHtml = '';
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea !== '') {
                $lineasHtml .= htmlspecialchars($linea, ENT_QUOTES, 'UTF-8') . '<br>';
            }
        }
        $contactosHtml = "
        <div style=\"margin-top: 12px; margin-bottom: 12px;\">
            <h4 style=\"margin: 0 0 4px 0; color: #103487; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 2px;\">Contactos de Seguimiento</h4>
            <div style=\"font-size: 9px; color: #475569; line-height: 1.35;\">
                {$lineasHtml}
            </div>
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
            @page {
                size: A4 portrait;
                margin: 0;
            }
            body {
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                color: #1e293b;
                line-height: 1.4;
                font-size: 11px;
                {$bodyStyle}
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
                    {$logoHtml}
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
                    <th style=\"border: 1px solid #cbd5e1; padding: 6px 8px; text-align: center; font-size: 10px; color: #475569; text-transform: uppercase; font-weight: bold; width: 18%;\">Código</th>
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
                <td style=\"width: 60%; vertical-align: top; padding-right: 20px;\">
                    <div style=\"font-size: 8.5px; color: #475569; line-height: 1.3;\">
                        <strong>Pago a nombre de CYC.S.A y/o depositar en las siguientes cuentas:</strong><br>
                        BANPRO: C$ 10010207085164 | $ 10010210874512<br>
                        BAC: C$ 357-02445-4 | $ 363259490<br>
                        LAFISE: C$ 550-2000-11<br>
                        RUC: J0310000073465 &bull; Validez de oferta: 30 días.
                    </div>
                    <div style=\"margin-top: 10px;\">
                        <table style=\"border-collapse: collapse;\">
                            <tr>
                                <td style=\"vertical-align: middle; padding-right: 8px;\">
                                    <span style=\"font-size: 8px; font-weight: bold; color: #475569; text-transform: uppercase; display: block; margin-bottom: 2px;\">Ver términos y condiciones del servicio</span>
                                    <span style=\"font-size: 7.5px; color: #64748b;\">Escanea para ver las políticas y términos oficiales de CYCSA.</span>
                                </td>
                                <td style=\"vertical-align: middle;\">
                                    <img src=\"{$qrBase64}\" style=\"height: 70px; width: 70px; border: 1px solid #cbd5e1; padding: 2px; background: white; border-radius: 4px;\">
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style=\"width: 40%; vertical-align: top;\">
                    <table class=\"totals-table\" style=\"width: 100%; border-collapse: collapse; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px;\">
                        <tr>
                            <td style=\"text-align: right; color: #64748b; padding: 4px 8px; font-size: 11px;\">Precio Base (Subtotal):</td>
                            <td style=\"text-align: right; font-weight: bold; width: 110px; padding: 4px 8px; font-size: 11px;\">{$simboloMoneda} {$subtotal}</td>
                        </tr>
                        {$descuentoHtml}
                        <tr>
                            <td style=\"text-align: right; color: #64748b; padding: 4px 8px; font-size: 11px;\">{$ivaLabel}</td>
                            <td style=\"text-align: right; font-weight: bold; padding: 4px 8px; font-size: 11px;\">{$simboloMoneda} {$impuesto}</td>
                        </tr>
                        <tr style=\"background: #e6eefc; border-top: 1px solid #cbd5e1;\">
                            <td style=\"text-align: right; color: #103487; font-weight: bold; padding: 6px 8px;\">TOTAL:</td>
                            <td style=\"text-align: right; color: #103487; font-weight: bold; padding: 6px 8px;\">{$simboloMoneda} {$total}</td>
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

        <!-- Project Contacts -->
        {$contactosHtml}

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
    $host = $_SERVER['HTTP_X_FORWARDED_HOST'] ?? $_SERVER['HTTP_HOST'] ?? null;
    if ($host) {
        $protocolo = 'http';
        if ((!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') || 
            (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')) {
            $protocolo = 'https';
        }
        $scriptPath = $_SERVER['SCRIPT_NAME'];
        $dir = dirname($scriptPath);
        $dir = str_replace('\\', '/', $dir);
        $dir = rtrim($dir, '/');
        return "{$protocolo}://{$host}{$dir}";
    }
    return $_ENV['APP_URL'] ?? 'http://localhost/Cycsa/publico';
}

/**
 * Registra una acción de auditoría en la tabla `bitacora`.
 */
function registrarBitacora(string $modulo, string $accion, string $descripcion, ?int $id_referencia = null): bool {
    try {
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        
        $id_usuario = $_SESSION['usuario_id'] ?? null;
        $usuario_nombre = $_SESSION['usuario_nombre'] ?? 'Sistema / Cliente';
        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        
        $sql = "INSERT INTO bitacora (id_usuario, usuario_nombre, modulo, accion, descripcion, id_referencia, ip) 
                VALUES (:id_usuario, :usuario_nombre, :modulo, :accion, :descripcion, :id_referencia, :ip)";
        
        $stmt = $db->prepare($sql);
        return $stmt->execute([
            'id_usuario' => $id_usuario,
            'usuario_nombre' => $usuario_nombre,
            'modulo' => $modulo,
            'accion' => $accion,
            'descripcion' => $descripcion,
            'id_referencia' => $id_referencia,
            'ip' => $ip
        ]);
    } catch (\Exception $e) {
        error_log("Error en bitácora: " . $e->getMessage());
        return false;
    }
}

/**
 * Obtiene los registros de bitácora filtrados por módulo.
 */
function obtenerBitacoraModulo(string $modulo, int $limite = 50): array {
    try {
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $sql = "SELECT * FROM bitacora WHERE modulo = :modulo ORDER BY id DESC LIMIT :limite";
        $stmt = $db->prepare($sql);
        $stmt->bindValue(':modulo', $modulo, \PDO::PARAM_STR);
        $stmt->bindValue(':limite', $limite, \PDO::PARAM_INT);
        $stmt->execute();
        return $stmt->fetchAll(\PDO::FETCH_ASSOC);
    } catch (\Exception $e) {
        error_log('Error al obtener bitácora del módulo: ' . $e->getMessage());
        return [];
    }
}

/**
 * Genera un gráfico de curva granulométrica en formato SVG vectorial nativo (escala semilogarítmica).
 */
function generarGraficoGranulometriaSVG(array $filas): string {
    $aperturas = [
        "2\"" => 50.0, "1 1/2\"" => 37.5, "1\"" => 25.0, "3/4\"" => 19.0, "1/2\"" => 12.5, "3/8\"" => 9.5,
        "No. 4" => 4.75, "No. 8" => 2.36, "No. 10" => 2.0, "No. 16" => 1.18, "No. 20" => 0.85, "No. 30" => 0.60,
        "No. 40" => 0.42, "No. 50" => 0.30, "No. 60" => 0.25, "No. 80" => 0.18, "No. 100" => 0.15, "No. 140" => 0.11,
        "No. 200" => 0.075
    ];

    $width = 540;
    $height = 260;
    $marginLeft = 50;
    $marginRight = 20;
    $marginTop = 20;
    $marginBottom = 40;

    $plotWidth = $width - $marginLeft - $marginRight;
    $plotHeight = $height - $marginTop - $marginBottom;

    $logMin = -2; // 0.01 mm
    $logMax = 2;  // 100 mm
    $logRange = $logMax - $logMin;

    // Grid lines for Y-axis (Porcentaje que pasa, 0% to 100%, every 10%)
    $svgGridY = '';
    for ($i = 0; $i <= 100; $i += 10) {
        $y = $marginTop + (1 - $i / 100) * $plotHeight;
        $svgGridY .= "<line x1=\"$marginLeft\" y1=\"$y\" x2=\"" . ($width - $marginRight) . "\" y2=\"$y\" stroke=\"#e2e8f0\" stroke-width=\"1\" />";
        $svgGridY .= "<text x=\"" . ($marginLeft - 8) . "\" y=\"" . ($y + 3) . "\" font-size=\"8\" text-anchor=\"end\" fill=\"#64748b\">$i%</text>";
    }

    // Grid lines for X-axis (Logarithmic, 0.01 to 100)
    $svgGridX = '';
    $labels = [0.01 => '0.01', 0.1 => '0.1', 1 => '1', 10 => '10', 100 => '100'];
    
    // Major cycles
    for ($c = $logMin; $c <= $logMax; $c++) {
        $val = pow(10, $c);
        $xPercent = ($c - $logMin) / $logRange;
        $x = $marginLeft + $xPercent * $plotWidth;
        
        $svgGridX .= "<line x1=\"$x\" y1=\"$marginTop\" x2=\"$x\" y2=\"" . ($height - $marginBottom) . "\" stroke=\"#cbd5e1\" stroke-width=\"1.5\" />";
        
        if (isset($labels[$val])) {
            $svgGridX .= "<text x=\"$x\" y=\"" . ($height - $marginBottom + 12) . "\" font-size=\"8\" text-anchor=\"middle\" fill=\"#64748b\">" . $labels[$val] . " mm</text>";
        }
        
        // Sub-grid lines (2 to 9)
        if ($c < $logMax) {
            for ($s = 2; $s <= 9; $s++) {
                $subVal = $s * $val;
                $logSub = log10($subVal);
                $subXPercent = ($logSub - $logMin) / $logRange;
                $subX = $marginLeft + $subXPercent * $plotWidth;
                $svgGridX .= "<line x1=\"$subX\" y1=\"$marginTop\" x2=\"$subX\" y2=\"" . ($height - $marginBottom) . "\" stroke=\"#f1f5f9\" stroke-width=\"0.8\" />";
            }
        }
    }

    // Parse data points
    $pointsSample = [];
    $pointsMin = [];
    $pointsMax = [];

    foreach ($filas as $fila) {
        // Find keys using case-insensitive lookup
        $mallaKey = '';
        foreach ($fila as $k => $v) {
            if (mb_strtolower(trim($k)) === 'malla') {
                $mallaKey = $k;
                break;
            }
        }
        if (empty($mallaKey)) continue;

        $mallaVal = trim($fila[$mallaKey]);
        if (isset($aperturas[$mallaVal])) {
            $apertureSize = $aperturas[$mallaVal];
            $logAperture = log10($apertureSize);
            $xPercent = ($logAperture - $logMin) / $logRange;
            $x = $marginLeft + $xPercent * $plotWidth;

            // Sample % que pasa
            $qpKey = '';
            foreach ($fila as $k => $v) {
                $kLower = mb_strtolower(trim($k));
                if (strpos($kLower, 'pasa') !== false || strpos($kLower, 'resultado') !== false) {
                    $qpKey = $k;
                    break;
                }
            }
            if ($qpKey && $fila[$qpKey] !== '' && $fila[$qpKey] !== null && $fila[$qpKey] !== '—') {
                $qpVal = floatval($fila[$qpKey]);
                $yPercent = $qpVal / 100;
                $y = $marginTop + (1 - $yPercent) * $plotHeight;
                $pointsSample[] = "$x,$y";
            }

            // Min limit
            $minKey = '';
            foreach ($fila as $k => $v) {
                $kLower = mb_strtolower(trim($k));
                if (strpos($kLower, 'mín') !== false || strpos($kLower, 'min') !== false) {
                    $minKey = $k;
                    break;
                }
            }
            if ($minKey && $fila[$minKey] !== '' && $fila[$minKey] !== null && $fila[$minKey] !== '—') {
                $minVal = floatval($fila[$minKey]);
                $yPercent = $minVal / 100;
                $y = $marginTop + (1 - $yPercent) * $plotHeight;
                $pointsMin[] = "$x,$y";
            }

            // Max limit
            $maxKey = '';
            foreach ($fila as $k => $v) {
                $kLower = mb_strtolower(trim($k));
                if (strpos($kLower, 'máx') !== false || strpos($kLower, 'max') !== false) {
                    $maxKey = $k;
                    break;
                }
            }
            if ($maxKey && $fila[$maxKey] !== '' && $fila[$maxKey] !== null && $fila[$maxKey] !== '—') {
                $maxVal = floatval($fila[$maxKey]);
                $yPercent = $maxVal / 100;
                $y = $marginTop + (1 - $yPercent) * $plotHeight;
                $pointsMax[] = "$x,$y";
            }
        }
    }

    $pathsSvg = '';

    // Draw Min limit line (Red dashed)
    if (count($pointsMin) > 1) {
        $pathsSvg .= "<polyline points=\"" . implode(' ', $pointsMin) . "\" fill=\"none\" stroke=\"#ef4444\" stroke-width=\"1.5\" stroke-dasharray=\"3,3\" />";
    }
    // Draw Max limit line (Red dashed)
    if (count($pointsMax) > 1) {
        $pathsSvg .= "<polyline points=\"" . implode(' ', $pointsMax) . "\" fill=\"none\" stroke=\"#ef4444\" stroke-width=\"1.5\" stroke-dasharray=\"3,3\" />";
    }
    // Draw Sample line (Blue solid thicker)
    if (count($pointsSample) > 1) {
        $pathsSvg .= "<polyline points=\"" . implode(' ', $pointsSample) . "\" fill=\"none\" stroke=\"#1e40af\" stroke-width=\"2.5\" />";
        // Draw points markers
        foreach ($pointsSample as $pt) {
            list($px, $py) = explode(',', $pt);
            $pathsSvg .= "<circle cx=\"$px\" cy=\"$py\" r=\"3\" fill=\"#1e40af\" />";
        }
    }

    return "
    <svg width=\"$width\" height=\"$height\" viewBox=\"0 0 $width $height\" style=\"display: block; margin: 10px auto; background-color: #ffffff;\">
        <!-- Axes background -->
        <rect x=\"$marginLeft\" y=\"$marginTop\" width=\"$plotWidth\" height=\"$plotHeight\" fill=\"none\" stroke=\"#1e293b\" stroke-width=\"1.5\" />
        <!-- Grid lines -->
        $svgGridY
        $svgGridX
        <!-- Paths -->
        $pathsSvg
        <!-- Legend -->
        <g transform=\"translate(" . ($marginLeft + 10) . ", " . ($height - 15) . ")\">
            <line x1=\"0\" y1=\"5\" x2=\"15\" y2=\"5\" stroke=\"#1e40af\" stroke-width=\"2.5\" />
            <circle cx=\"7.5\" cy=\"5\" r=\"2.5\" fill=\"#1e40af\" />
            <text x=\"20\" y=\"8\" font-size=\"8\" fill=\"#1e293b\" font-weight=\"bold\">Muestra</text>
            
            <line x1=\"80\" y1=\"5\" x2=\"95\" y2=\"5\" stroke=\"#ef4444\" stroke-width=\"1.5\" stroke-dasharray=\"3,3\" />
            <text x=\"100\" y=\"8\" font-size=\"8\" fill=\"#ef4444\" font-weight=\"bold\">Límites especificados</text>
        </g>
    </svg>
    ";
}

/**
 * Genera el reporte de ensayo PDF para un producto/ensayo específico usando Dompdf.
 */
function generarReporteEnsayoPDF(array $cotizacion, array $detalle, array $columnas, array $filas, string $codigoReporte = '', $version = null): string {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $dompdf = new Dompdf($options);

    $logoPath = __DIR__ . '/../publico/img/logo_cycsa.jpg';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
    }

    $bgPath = __DIR__ . '/../publico/img/hoja_horizontal.jpg';
    $bgBase64 = '';
    if (file_exists($bgPath)) {
        $bgBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($bgPath));
    }

    $logoHtml = '';
    if (!extension_loaded('gd') || empty($bgBase64)) {
        if (!empty($logoBase64)) {
            $logoHtml = '<img src="' . $logoBase64 . '" style="height: 38px; margin-bottom: 4px;"><br>';
        } else {
            $logoHtml = '<span style="font-size: 20px; font-weight: bold; color: #103487;">CYCSA</span><br>';
        }
        $logoHtml .= '
            <span style="font-size: 8px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold; display: block; margin-bottom: 2px;">Laboratorio de Ensayos y Control de Calidad</span>
            <span style="font-size: 7.5px; color: #64748b; line-height: 1.2;">
                Km 83.5 Carretera León-Managua, León, Nicaragua
            </span>';
    }

    $bodyStyle = "margin: 0; padding: 1.5cm;";
    $headerClass = '';
    $headerStyle = 'width: 100%; border-bottom: 2px solid #103487; padding-bottom: 8px; margin-bottom: 12px; border-collapse: collapse;';
    if (extension_loaded('gd') && !empty($bgBase64)) {
        $bodyStyle = "margin: 0; padding: 5.2cm 2.0cm 2.0cm 2.0cm; background-image: url('{$bgBase64}'); background-size: 100% 100%; background-repeat: no-repeat;";
        $headerClass = 'class="header-absolute"';
        $headerStyle = 'border-collapse: collapse; width: 45%;';
    }

    $graficoHtml = '';
    $archivoMarkdown = $detalle['archivo_markdown'] ?? '';
    $esGranulometriaReport = (strpos($archivoMarkdown, 'granulometria') !== false || strpos($archivoMarkdown, 'granulomnetria') !== false);
    if ($esGranulometriaReport) {
        $graficoHtml = "
        <div style=\"page-break-before: always; text-align: center; padding-top: 10px;\">
            <h4 style=\"margin-top: 0; color: #1e293b; font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 2px solid #103487; padding-bottom: 4px; display: inline-block;\">Curva de Distribución Granulométrica</h4>
            <div style=\"margin: 15px auto;\">
                " . generarGraficoGranulometriaSVG($filas) . "
            </div>
        </div>";
    }

    $fechaMuestreo = $cotizacion['fecha_creacion'] ? date('d/m/Y', strtotime($cotizacion['fecha_creacion'])) : date('d/m/Y');
    $fechaIngreso = date('d/m/Y', strtotime($cotizacion['fecha_creacion']));
    $fechaEjecucion = $cotizacion['fecha_entrega'] ? date('d/m/Y', strtotime($cotizacion['fecha_entrega'])) : date('d/m/Y');
    $fechaEmision = date('d/m/Y');

    // Header table columns rendering - Standard font-size for Landscape!
    $theadHtml = '';
    foreach ($columnas as $col) {
        $theadHtml .= "<th style=\"border: 1px solid #cbd5e1; padding: 4px 6px; text-align: left; font-size: 8.5px; color: #475569; text-transform: uppercase; font-weight: bold;\">" . htmlspecialchars($col, ENT_QUOTES, 'UTF-8') . "</th>";
    }

    // Body rows rendering
    $tbodyHtml = '';
    if (empty($filas)) {
        for ($i = 0; $i < 5; $i++) {
            $tbodyHtml .= "<tr>";
            foreach ($columnas as $col) {
                $tbodyHtml .= "<td style=\"border: 1px solid #cbd5e1; padding: 5px 6px; height: 14px;\">&nbsp;</td>";
            }
            $tbodyHtml .= "</tr>";
        }
    } else {
        foreach ($filas as $fila) {
            $tbodyHtml .= "<tr>";
            foreach ($columnas as $col) {
                $val = $fila[$col] ?? '';
                $tbodyHtml .= "<td style=\"border: 1px solid #cbd5e1; padding: 4px 6px; font-size: 8.5px;\">" . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . "</td>";
            }
            $tbodyHtml .= "</tr>";
        }
    }

    $clienteNombre = htmlspecialchars($cotizacion['cliente_nombre'] ?? '', ENT_QUOTES, 'UTF-8');
    $proyectoNombre = htmlspecialchars($cotizacion['nombre_proyecto'] ?? '', ENT_QUOTES, 'UTF-8');
    $direccionProyecto = htmlspecialchars($cotizacion['direccion_proyecto'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
    $tipoMuestra = htmlspecialchars($detalle['tipo_muestra'] ?? 'Suelo', ENT_QUOTES, 'UTF-8');
    $procedimientoMuestreo = htmlspecialchars($detalle['procedimiento_muestreo'] ?? 'Aleatorio', ENT_QUOTES, 'UTF-8');
    $ensayoRealizado = htmlspecialchars($detalle['descripcion_ensayo'] ?? '', ENT_QUOTES, 'UTF-8');
    $codigoFormato = htmlspecialchars($detalle['codigo_formato'] ?? 'CYCSA-RT-FM-22', ENT_QUOTES, 'UTF-8');
    $normaAstm = htmlspecialchars($detalle['norma_astm'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
    $nombreFormato = htmlspecialchars($detalle['formato_nombre'] ?? 'Informe de Ensayo', ENT_QUOTES, 'UTF-8');

    $html = "
    <!DOCTYPE html>
    <html lang=\"es\">
    <head>
        <meta charset=\"UTF-8\">
        <style>
            @page {
                size: A4 landscape;
                margin: 0;
            }
            body {
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                color: #1e293b;
                line-height: 1.4;
                font-size: 9.5px;
                {$bodyStyle}
            }
            .header-absolute {
                position: absolute;
                top: 1.2cm;
                right: 2.0cm;
                width: 45%;
                text-align: right;
            }
        </style>
    </head>
    <body>
        <!-- Header -->
        <table {$headerClass} style=\"{$headerStyle}\">
            <tr>
                <td style=\"width: 60%; vertical-align: top;\">
                    {$logoHtml}
                </td>
                <td style=\"text-align: right; vertical-align: top;\">
                    <span style=\"font-size: 12px; font-weight: bold; color: #103487;\">" . strtoupper($nombreFormato) . "</span><br>
                    <span style=\"font-size: 11px; font-weight: bold; color: #1e293b; margin: 2px 0; display: block;\">{$codigoFormato}</span>
                    " . (!empty($codigoReporte) ? "<span style=\"font-size: 10px; font-weight: bold; color: #ef4444; display: block; margin-top: 4px;\">Informe No: " . htmlspecialchars($codigoReporte, ENT_QUOTES, 'UTF-8') . "</span>" : "") . "
                </td>
            </tr>
        </table>

        <!-- Metadata Grid -->
        <table style=\"width: 100%; margin-bottom: 15px; border-collapse: collapse; font-size: 9px;\">
            <tr>
                <td style=\"width: 18%; padding: 3px 0; color: #64748b;\">Nombre del cliente:</td>
                <td style=\"width: 32%; padding: 3px 0; font-weight: bold;\">{$clienteNombre}</td>
                <td style=\"width: 18%; padding: 3px 0; color: #64748b;\">Proyecto:</td>
                <td style=\"width: 32%; padding: 3px 0; font-weight: bold;\">{$proyectoNombre}</td>
            </tr>
            <tr>
                <td style=\"padding: 3px 0; color: #64748b;\">Dirección:</td>
                <td style=\"padding: 3px 0;\">{$direccionProyecto}</td>
                <td style=\"padding: 3px 0; color: #64748b;\">Fecha muestreo:</td>
                <td style=\"padding: 3px 0;\">{$fechaMuestreo}</td>
            </tr>
            <tr>
                <td style=\"padding: 3px 0; color: #64748b;\">Fecha de ingreso:</td>
                <td style=\"padding: 3px 0;\">{$fechaIngreso}</td>
                <td style=\"padding: 3px 0; color: #64748b;\">Fecha de ejecución:</td>
                <td style=\"padding: 3px 0;\">{$fechaEjecucion}</td>
            </tr>
            <tr>
                <td style=\"padding: 3px 0; color: #64748b;\">Tipo de muestra:</td>
                <td style=\"padding: 3px 0;\">{$tipoMuestra}</td>
                <td style=\"padding: 3px 0; color: #64748b;\">Fecha de emisión:</td>
                <td style=\"padding: 3px 0;\">{$fechaEmision}</td>
            </tr>
            <tr>
                <td style=\"padding: 3px 0; color: #64748b;\">Procedimiento muestreo:</td>
                <td style=\"padding: 3px 0;\">{$procedimientoMuestreo}</td>
                <td style=\"padding: 3px 0; color: #64748b;\">Muestra tomada por:</td>
                <td style=\"padding: 3px 0;\">Laboratorio - Consultoría y Construcción S.A. CYCSA</td>
            </tr>
            <tr>
                <td style=\"padding: 3px 0; color: #64748b;\">Ensayo realizado:</td>
                <td style=\"padding: 3px 0; font-weight: bold;\" colspan=\"3\">{$ensayoRealizado} (Norma: {$normaAstm})</td>
            </tr>
        </table>

        <!-- Main Results Table -->
        <table style=\"width: 100%; border-collapse: collapse; margin-bottom: 20px;\">
            <thead>
                <tr style=\"background-color: #f1f5f9;\">
                    {$theadHtml}
                </tr>
            </thead>
            <tbody>
                {$tbodyHtml}
            </tbody>
        </table>

        {$graficoHtml}

        <!-- Footer terms -->
        <div style=\"font-size: 8px; color: #64748b; line-height: 1.3; margin-top: 15px; border-top: 1px solid #cbd5e1; padding-top: 6px;\">
            Consultoría y Construcción SA. CYCSA es responsable únicamente de la exactitud de los resultados realizados en las muestras recibidas y tomadas en campo. No se debe de reproducir este informe de ensayo sin la aprobación formal de Consultoría y Construcción SA. CYCSA.
        </div>

        <!-- Signatures -->
        <table style=\"width: 100%; margin-top: 40px; border-collapse: collapse;\">
            <tr>
                <td style=\"width: 50%; text-align: center;\">
                    <div style=\"border-top: 1px solid #cbd5e1; width: 60%; margin: 0 auto; padding-top: 4px; font-size: 9px;\">
                        <strong>Ing. Noel Quintana Lira</strong><br>
                        Gerente General<br>
                        CYCSA Laboratorio
                    </div>
                </td>
                <td style=\"width: 50%; text-align: center;\">
                    <div style=\"border-top: 1px solid #cbd5e1; width: 60%; margin: 0 auto; padding-top: 4px; font-size: 9px;\">
                        <strong>Técnico de Calidad</strong><br>
                        Realizado por / Firma
                    </div>
                </td>
            </tr>
        </table>
    </body>
    </html>";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();
    return $dompdf->output();
}

/**
 * Genera el documento completo PDF (Propuesta Comercial + Reportes de Laboratorio de cada ensayo).
 */
function generarCotizacionCompletaPDF(array $cotizacion, array $detalles): string {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $dompdf = new Dompdf($options);

    $fecha = date('d/m/Y', strtotime($cotizacion['fecha_creacion']));
    $subtotal = number_format($cotizacion['subtotal'], 2, '.', ',');
    $impuesto = number_format($cotizacion['impuesto'], 2, '.', ',');
    $total = number_format($cotizacion['total'], 2, '.', ',');

    $qrPath = __DIR__ . '/../publico/img/qr_terminos.png';
    $qrBase64 = '';
    if (file_exists($qrPath)) {
        $qrBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($qrPath));
    }

    $descuentoMonto = (float)($cotizacion['descuento'] ?? 0);
    $descuentoHtml = '';
    $netoMonto = (float)$cotizacion['subtotal'] - $descuentoMonto;
    $neto = number_format($netoMonto, 2, '.', ',');
    if ($descuentoMonto > 0) {
        $descuentoVal = number_format($descuentoMonto, 2, '.', ',');
        $descuentoHtml = "
        <tr>
            <td style=\"padding: 6px 10px; border-bottom: 1px solid #e2e8f0; color: #dc2626;\">Monto Descontado:</td>
            <td style=\"padding: 6px 10px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: bold; color: #dc2626;\">-C$ {$descuentoVal}</td>
        </tr>
        <tr>
            <td style=\"padding: 6px 10px; border-bottom: 1px solid #e2e8f0; color: #334155; font-weight: 600;\">Precio con Descuento:</td>
            <td style=\"padding: 6px 10px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: bold; color: #0f172a;\">C$ {$neto}</td>
        </tr>";
    }

    $ivaLabel = "IVA (15%):";
    if ((int)($cotizacion['exonerado'] ?? 0)) {
        $exNo = !empty($cotizacion['exoneracion_no']) ? ' (' . htmlspecialchars($cotizacion['exoneracion_no'], ENT_QUOTES, 'UTF-8') . ')' : '';
        $ivaLabel = "IVA (Exonerado{$exNo}):";
    }

    $rowsHtml = '';
    foreach ($detalles as $det) {
        $descText = htmlspecialchars($det['descripcion_ensayo'] ?? '', ENT_QUOTES, 'UTF-8');
        $codigoServicio = !empty($det['codigo_servicio']) ? htmlspecialchars($det['codigo_servicio'], ENT_QUOTES, 'UTF-8') : 'N/A';
        $cant = number_format($det['cantidad'], 2, '.', ',');
        $precio = number_format($det['precio_unitario'], 2, '.', ',');
        $sub = number_format($det['subtotal'], 2, '.', ',');

        $metaHtml = '';
        if (!empty($det['observaciones'])) {
            $metaHtml .= "<span style=\"color:#475569; font-size:9px;\">Tiempo Entrega: <strong>" . htmlspecialchars($det['observaciones'], ENT_QUOTES, 'UTF-8') . "</strong></span>";
        }
        
        $desc = $descText;
        if ($metaHtml) {
            $desc = "<strong>{$desc}</strong><div style=\"margin-top: 3px; padding-top: 2px; border-top: 1px dashed #e2e8f0; font-size: 9px;\">{$metaHtml}</div>";
        } else {
            $desc = "<strong>{$desc}</strong>";
        }

        $rowsHtml .= "
        <tr>
            <td style=\"border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 10px; font-family: monospace; text-align: center; color: #334155; font-weight: bold;\">{$codigoServicio}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 11px;\">{$desc}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 11px; text-align: right;\">{$cant}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 11px; text-align: right;\">C$ {$precio}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 6px 10px; font-size: 11px; text-align: right; font-weight: bold;\">C$ {$sub}</td>
        </tr>";
    }

    $configNotas = json_decode($cotizacion['configuracion_notes'] ?? $cotizacion['configuracion_notas'] ?? '', true) ?: [];
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

    $notesSeccion = '';
    if (!empty($htmlNotas)) {
        $notesSeccion = "
        <div style=\"margin-top: 15px;\">
            <h4 style=\"margin: 0 0 5px 0; color: #103487; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 3px;\">Notas y Leyendas de Servicio</h4>
            <ul style=\"margin: 0; padding-left: 15px; font-size: 9px; color: #475569; line-height: 1.3;\">
                {$htmlNotas}
            </ul>
        </div>";
    }

    $contactosRaw = $cotizacion['contactos'] ?? '';
    $contactosHtml = '';
    if (!empty($contactosRaw)) {
        $lineas = explode("\n", $contactosRaw);
        $lineasHtml = '';
        foreach ($lineas as $linea) {
            $linea = trim($linea);
            if ($linea !== '') {
                $lineasHtml .= htmlspecialchars($linea, ENT_QUOTES, 'UTF-8') . '<br>';
            }
        }
        $contactosHtml = "
        <div style=\"margin-top: 12px; margin-bottom: 12px;\">
            <h4 style=\"margin: 0 0 4px 0; color: #103487; font-size: 10px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 2px;\">Contactos de Seguimiento</h4>
            <div style=\"font-size: 9px; color: #475569; line-height: 1.35;\">
                {$lineasHtml}
            </div>
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
            @page {
                size: A4 portrait;
                margin: 1.5cm;
            }
            .page-landscape {
                page-break-before: always;
                width: 100%;
                clear: both;
            }
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
        <!-- Header -->
        <table style=\"width: 100%; border-bottom: 2px solid #103487; padding-bottom: 15px; margin-bottom: 20px; border-collapse: collapse;\">
            <tr>
                <td style=\"width: 50%; vertical-align: top;\">
                    <span style=\"font-size: 28px; font-weight: bold; color: #103487;\">CYCSA</span><br>
                    <span style=\"font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold;\">Consultoría y Construcción S.A.</span>
                </td>
                <td style=\"width: 50%; text-align: right; vertical-align: top;\">
                    <span style=\"font-size: 16px; font-weight: bold; color: #103487;\">PROPUESTA ECONÓMICA</span><br>
                    <span style=\"font-size: 12px; font-weight: bold; color: #e31837; margin: 3px 0; display: block;\">Oferta N°: {$codigo} (v{$version})</span>
                    <span style=\"font-size: 9px; color: #64748b;\">Fecha: {$fecha}</span>
                </td>
            </tr>
        </table>

        <!-- Client & Project Details -->
        <table style=\"width: 100%; border-collapse: collapse; margin-bottom: 25px;\">
            <tr>
                <td style=\"width: 48%; vertical-align: top; border: 1px solid #e2e8f0; padding: 12px; border-radius: 6px; background-color: #f8fafc;\">
                    <h4 style=\"margin: 0 0 8px 0; color: #103487; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px;\">Datos del Cliente</h4>
                    <table style=\"width: 100%; font-size: 10px;\">
                        <tr><td style=\"color: #64748b; padding: 2px 0;\">Cliente:</td><td style=\"font-weight: bold;\">{$clienteNombre}</td></tr>
                        <tr><td style=\"color: #64748b; padding: 2px 0;\">RUC:</td><td>{$clienteRuc}</td></tr>
                        <tr><td style=\"color: #64748b; padding: 2px 0;\">Atención a:</td><td>{$atencionA}</td></tr>
                    </table>
                </td>
                <td style=\"width: 4%;\"></td>
                <td style=\"width: 48%; vertical-align: top; border: 1px solid #e2e8f0; padding: 12px; border-radius: 6px; background-color: #f8fafc;\">
                    <h4 style=\"margin: 0 0 8px 0; color: #103487; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #cbd5e1; padding-bottom: 3px;\">Detalles del Proyecto</h4>
                    <table style=\"width: 100%; font-size: 10px;\">
                        <tr><td style=\"color: #64748b; padding: 2px 0;\">Proyecto:</td><td style=\"font-weight: bold;\">{$proyectoNombre}</td></tr>
                        <tr><td style=\"color: #64748b; padding: 2px 0;\">Ubicación:</td><td>{$proyectoDireccion}</td></tr>
                        <tr><td style=\"color: #64748b; padding: 2px 0;\">Prioridad:</td><td><span style=\"font-weight: bold;\">{$prioridad}</span></td></tr>
                    </table>
                </td>
            </tr>
        </table>

        <!-- Table of Items -->
        <table style=\"width: 100%; border-collapse: collapse; margin-bottom: 25px;\">
            <thead>
                <tr style=\"background-color: #103487; color: white;\">
                    <th style=\"border: 1px solid #cbd5e1; padding: 8px 10px; text-align: center; font-size: 11px; text-transform: uppercase; width: 18%;\">Código</th>
                    <th style=\"border: 1px solid #cbd5e1; padding: 8px 10px; text-align: left; font-size: 11px; text-transform: uppercase;\">Descripción del Ensayo / Servicio</th>
                    <th style=\"border: 1px solid #cbd5e1; padding: 8px 10px; text-align: right; font-size: 11px; text-transform: uppercase; width: 50px;\">Cant.</th>
                    <th style=\"border: 1px solid #cbd5e1; padding: 8px 10px; text-align: right; font-size: 11px; text-transform: uppercase; width: 90px;\">Precio Unit.</th>
                    <th style=\"border: 1px solid #cbd5e1; padding: 8px 10px; text-align: right; font-size: 11px; text-transform: uppercase; width: 110px;\">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                {$rowsHtml}
            </tbody>
        </table>

        <!-- Summary Totals -->
        <table class=\"totals-table\" style=\"width: 100%; border-collapse: collapse; margin-top: 15px;\">
            <tr>
                <td style=\"width: 60%; vertical-align: top; padding-right: 20px;\">
                    <table style=\"font-size: 9px; line-height: 1.4; color: #475569; margin-bottom: 8px;\">
                        <tr><td style=\"padding-right: 8px;\"><strong>Condición de Pago:</strong></td><td>{$condicionPago}</td></tr>
                        <tr><td style=\"padding-right: 8px;\"><strong>Tiempo de Entrega:</strong></td><td>{$tiempoEntrega}</td></tr>
                        <tr><td style=\"padding-right: 8px;\"><strong>Vigencia de Oferta:</strong></td><td>{$vigenciaOferta}</td></tr>
                    </table>
                    <div style=\"font-size: 8.2px; color: #475569; line-height: 1.25; border-top: 1px dashed #cbd5e1; padding-top: 6px; margin-bottom: 8px;\">
                        <strong>Pago a nombre de CYC.S.A y/o depositar en las siguientes cuentas:</strong><br>
                        BANPRO: C$ 10010207085164 | $ 10010210874512 &bull; BAC: C$ 357-02445-4 | $ 363259490<br>
                        LAFISE: C$ 550-2000-11 &bull; RUC: J0310000073465
                    </div>
                    <div>
                        <table style=\"border-collapse: collapse;\">
                            <tr>
                                <td style=\"vertical-align: middle; padding-right: 8px;\">
                                    <span style=\"font-size: 7.5px; font-weight: bold; color: #475569; text-transform: uppercase; display: block; margin-bottom: 1px;\">Ver términos y condiciones del servicio</span>
                                    <span style=\"font-size: 7px; color: #64748b;\">Escanea para ver las políticas y términos oficiales de CYCSA.</span>
                                </td>
                                <td style=\"vertical-align: middle;\">
                                    <img src=\"{$qrBase64}\" style=\"height: 60px; width: 60px; border: 1px solid #cbd5e1; padding: 1px; background: white; border-radius: 4px;\">
                                </td>
                            </tr>
                        </table>
                    </div>
                </td>
                <td style=\"width: 40%; vertical-align: top;\">
                    <table style=\"width: 100%; border-collapse: collapse; background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px;\">
                        <tr>
                            <td style=\"padding: 6px 10px; border-bottom: 1px solid #e2e8f0;\">Precio Base (Subtotal):</td>
                            <td style=\"padding: 6px 10px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: bold;\">C$ {$subtotal}</td>
                        </tr>
                        {$descuentoHtml}
                        <tr>
                            <td style=\"padding: 6px 10px; border-bottom: 1px solid #e2e8f0;\">{$ivaLabel}</td>
                            <td style=\"padding: 6px 10px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: bold;\">C$ {$impuesto}</td>
                        </tr>
                        <tr style=\"background-color: #f1f5f9;\">
                            <td style=\"padding: 8px 10px; font-weight: bold; color: #103487; font-size: 12px;\">TOTAL:</td>
                            <td style=\"padding: 8px 10px; font-weight: bold; color: #103487; font-size: 12px; text-align: right;\">C$ {$total}</td>
                        </tr>
                    </table>
                </td>
            </tr>
        </table>

        {$notesSeccion}

        <!-- Project Contacts -->
        {$contactosHtml}

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

    $rutaSchema = __DIR__ . '/../datos_ensayos_markdown/formatos_schema.json';
    $schemaData = file_exists($rutaSchema) ? json_decode(file_get_contents($rutaSchema), true) : [];

    // Append report sheets
    foreach ($detalles as $det) {
        if (!empty($det['formato_reporte']) && !empty($det['archivo_markdown'])) {
            $archivoMd = $det['archivo_markdown'];
            $columns = $schemaData[$archivoMd]['columns'] ?? ["Código laboratorio", "Nombre muestra", "Resultado"];
            $filas = json_decode($det['resultados_json'] ?? '', true) ?: [];

            // Render table columns
            $theadHtml = '';
            foreach ($columns as $col) {
                $theadHtml .= "<th style=\"border: 1px solid #cbd5e1; padding: 4px 5px; text-align: left; font-size: 7.5px; color: #475569; text-transform: uppercase; font-weight: bold;\">" . htmlspecialchars($col, ENT_QUOTES, 'UTF-8') . "</th>";
            }

            // Render table rows
            $tbodyHtml = '';
            if (empty($filas)) {
                for ($r = 0; $r < 5; $r++) {
                    $tbodyHtml .= "<tr>";
                    foreach ($columns as $col) {
                        $tbodyHtml .= "<td style=\"border: 1px solid #cbd5e1; padding: 6px 5px; height: 14px;\">&nbsp;</td>";
                    }
                    $tbodyHtml .= "</tr>";
                }
            } else {
                foreach ($filas as $fila) {
                    $tbodyHtml .= "<tr>";
                    foreach ($columns as $col) {
                        $val = $fila[$col] ?? '';
                        $tbodyHtml .= "<td style=\"border: 1px solid #cbd5e1; padding: 4px 5px; font-size: 7.5px;\">" . htmlspecialchars($val, ENT_QUOTES, 'UTF-8') . "</td>";
                    }
                    $tbodyHtml .= "</tr>";
                }
            }

            $tipoMuestra = htmlspecialchars($det['tipo_muestra'] ?? 'Suelo', ENT_QUOTES, 'UTF-8');
            $procedimientoMuestreo = htmlspecialchars($det['procedimiento_muestreo'] ?? 'Aleatorio', ENT_QUOTES, 'UTF-8');
            $ensayoRealizado = htmlspecialchars($det['descripcion_ensayo'] ?? '', ENT_QUOTES, 'UTF-8');
            $codigoFormato = htmlspecialchars($det['codigo_formato'] ?? $det['formato_reporte'], ENT_QUOTES, 'UTF-8');
            $normaAstm = htmlspecialchars($det['norma_astm'] ?? 'N/A', ENT_QUOTES, 'UTF-8');
            $nombreFormato = htmlspecialchars($det['formato_nombre'] ?? 'Informe de Ensayo', ENT_QUOTES, 'UTF-8');

            $html .= "
            <div class=\"page-landscape\">
                <table style=\"width: 100%; border-bottom: 2px solid #103487; padding-bottom: 8px; margin-bottom: 12px; border-collapse: collapse;\">
                    <tr>
                        <td style=\"width: 60%; vertical-align: top;\">
                            <span style=\"font-size: 20px; font-weight: bold; color: #103487;\">CYCSA</span><br>
                            <span style=\"font-size: 9px; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; font-weight: bold;\">Laboratorio de Ensayos y Control de Calidad</span><br>
                            <span style=\"font-size: 8px; color: #64748b; line-height: 1.2;\">Km 83.5 Carretera León-Managua, León, Nicaragua</span>
                        </td>
                        <td style=\"width: 40%; text-align: right; vertical-align: top;\">
                            <span style=\"font-size: 12px; font-weight: bold; color: #103487;\">" . strtoupper($nombreFormato) . "</span><br>
                            <span style=\"font-size: 11px; font-weight: bold; color: #1e293b; margin: 2px 0; display: block;\">{$codigoFormato}</span>
                        </td>
                    </tr>
                </table>

                <table style=\"width: 100%; margin-bottom: 15px; border-collapse: collapse; font-size: 9px;\">
                    <tr>
                        <td style=\"width: 18%; padding: 3px 0; color: #64748b;\">Nombre del cliente:</td>
                        <td style=\"width: 32%; padding: 3px 0; font-weight: bold;\">{$clienteNombre}</td>
                        <td style=\"width: 18%; padding: 3px 0; color: #64748b;\">Proyecto:</td>
                        <td style=\"width: 32%; padding: 3px 0; font-weight: bold;\">{$proyectoNombre}</td>
                    </tr>
                    <tr>
                        <td style=\"padding: 3px 0; color: #64748b;\">Dirección:</td>
                        <td style=\"padding: 3px 0;\">{$proyectoDireccion}</td>
                        <td style=\"padding: 3px 0; color: #64748b;\">Fecha muestreo:</td>
                        <td style=\"padding: 3px 0;\">{$fecha}</td>
                    </tr>
                    <tr>
                        <td style=\"padding: 3px 0; color: #64748b;\">Fecha de ingreso:</td>
                        <td style=\"padding: 3px 0;\">{$fecha}</td>
                        <td style=\"padding: 3px 0; color: #64748b;\">Fecha de ejecución:</td>
                        <td style=\"padding: 3px 0;\">{$fecha}</td>
                    </tr>
                    <tr>
                        <td style=\"padding: 3px 0; color: #64748b;\">Tipo de muestra:</td>
                        <td style=\"padding: 3px 0;\">{$tipoMuestra}</td>
                        <td style=\"padding: 3px 0; color: #64748b;\">Fecha de emisión:</td>
                        <td style=\"padding: 3px 0;\">" . date('d/m/Y') . "</td>
                    </tr>
                    <tr>
                        <td style=\"padding: 3px 0; color: #64748b;\">Procedimiento muestreo:</td>
                        <td style=\"padding: 3px 0;\">{$procedimientoMuestreo}</td>
                        <td style=\"padding: 3px 0; color: #64748b;\">Muestra tomada por:</td>
                        <td style=\"padding: 3px 0;\">Laboratorio - Consultoría y Construcción S.A. CYCSA</td>
                    </tr>
                    <tr>
                        <td style=\"padding: 3px 0; color: #64748b;\">Ensayo realizado:</td>
                        <td style=\"padding: 3px 0; font-weight: bold;\" colspan=\"3\">{$ensayoRealizado} (Norma: {$normaAstm})</td>
                    </tr>
                </table>

                <table style=\"width: 100%; border-collapse: collapse; margin-bottom: 20px;\">
                    <thead>
                        <tr style=\"background-color: #f1f5f9;\">
                            {$theadHtml}
                        </tr>
                    </thead>
                    <tbody>
                        {$tbodyHtml}
                    </tbody>
                </table>

                <div style=\"font-size: 8px; color: #64748b; line-height: 1.3; margin-top: 15px; border-top: 1px solid #cbd5e1; padding-top: 6px;\">
                    Consultoría y Construcción SA. CYCSA es responsable únicamente de la exactitud de los resultados realizados en las muestras recibidas y tomadas en campo. No se debe de reproducir este informe de ensayo sin la aprobación formal de Consultoría y Construcción SA. CYCSA.
                </div>

                <table style=\"width: 100%; margin-top: 40px; border-collapse: collapse;\">
                    <tr>
                        <td style=\"width: 50%; text-align: center;\">
                            <div style=\"border-top: 1px solid #cbd5e1; width: 60%; margin: 0 auto; padding-top: 4px; font-size: 9px;\">
                                <strong>Ing. Noel Quintana Lira</strong><br>
                                Gerente General<br>
                                CYCSA Laboratorio
                            </div>
                        </td>
                        <td style=\"width: 50%; text-align: center;\">
                            <div style=\"border-top: 1px solid #cbd5e1; width: 60%; margin: 0 auto; padding-top: 4px; font-size: 9px;\">
                                <strong>Técnico de Calidad</strong><br>
                                Realizado por / Firma
                            </div>
                        </td>
                    </tr>
                </table>
            </div>";
        }
    }

    $dompdf->loadHtml($html);
    $dompdf->render();
    return $dompdf->output();
}

/**
 * Genera el archivo PDF de la Hoja de Solicitud de Servicio CYCSA-RT-FM-13 usando Dompdf.
 */
function generarHojaSolicitudPDF(array $hoja, array $os): string {
    $options = new \Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $dompdf = new \Dompdf\Dompdf($options);

    $logoPath = __DIR__ . '/../publico/img/logo_cycsa.jpg';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
    }

    $logoHtml = '';
    if (!empty($logoBase64)) {
        $logoHtml = '<img src="' . $logoBase64 . '" style="height: 38px; float: left; margin-right: 15px;">';
    } else {
        $logoHtml = '<span style="font-size: 24px; font-weight: bold; color: #103487; float: left; margin-right: 15px;">CYCSA</span>';
    }

    $identificacionMuestras = json_decode($hoja['muestras_json'] ?? '[]', true) ?: [];
    
    $identRows = '';
    foreach ($identificacionMuestras as $m) {
        $identRows .= '
        <tr>
            <td style="border: 1px solid #cbd5e1; padding: 6px; font-size: 11px;">' . htmlspecialchars($m['nombre_muestra'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px; font-size: 11px;">' . htmlspecialchars($m['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
            <td style="border: 1px solid #cbd5e1; padding: 6px; font-size: 11px;">' . htmlspecialchars($m['info_importante'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
        </tr>';
    }
    if (empty($identRows)) {
        $identRows = '<tr><td colspan="3" style="border: 1px solid #cbd5e1; padding: 10px; font-size: 11px; text-align: center; color: #64748b;">No se especificaron especímenes individuales</td></tr>';
    }

    // Nature mapping
    $naturalezaChecked = explode(',', $hoja['naturaleza_muestra'] ?? '');
    $naturalezaHtml = '';
    foreach (['Concreto', 'Bloques', 'Suelo', 'Adoquines', 'Agregados', 'Otros'] as $n) {
        $checked = in_array($n, $naturalezaChecked) ? '[X]' : '[ ]';
        $naturalezaHtml .= '<span style="margin-right: 15px; font-size: 11px;"><strong>' . $checked . '</strong> ' . $n . '</span>';
    }

    // Parameters lists
    $concretoParams = [];
    if (!empty($hoja['req_resistencia_concreto'])) $concretoParams[] = 'Resistencia de conc';
    if (!empty($hoja['req_resistencia_adoquin'])) $concretoParams[] = 'Resistencia de adoquin';
    if (!empty($hoja['req_resistencia_bloques'])) $concretoParams[] = 'Resistencia bloques';
    if (!empty($hoja['req_otros_concreto'])) $concretoParams[] = 'Otros: ' . htmlspecialchars($hoja['req_otros_concreto'], ENT_QUOTES, 'UTF-8');
    
    $suelosParams = [];
    if (!empty($hoja['req_granulometria'])) $suelosParams[] = 'Granulometría';
    if (!empty($hoja['req_limites_atterberg'])) $suelosParams[] = 'Límites de atterberg';
    if (!empty($hoja['req_humedad'])) $suelosParams[] = 'Humedad';
    if (!empty($hoja['req_resistencia_corte'])) $suelosParams[] = 'Resistencia al corte';
    if (!empty($hoja['req_clasificacion_sucs_hr'])) $suelosParams[] = 'Clasificación SUCS/HR';
    if (!empty($hoja['req_proctor_sm'])) $suelosParams[] = 'PROCTOR S/M';
    if (!empty($hoja['req_infiltracion'])) $suelosParams[] = 'Infiltración';
    if (!empty($hoja['req_cbr'])) $suelosParams[] = 'CBR';
    if (!empty($hoja['req_densidad'])) $suelosParams[] = 'Densidad';
    if (!empty($hoja['req_otros_suelo'])) $suelosParams[] = 'Otros: ' . htmlspecialchars($hoja['req_otros_suelo'], ENT_QUOTES, 'UTF-8');

    $otrosParams = [];
    if (!empty($hoja['req_otros_materiales'])) $otrosParams[] = 'Otro';
    if (!empty($hoja['descripcion_otros_analisis'])) $otrosParams[] = 'Análisis necesario: ' . htmlspecialchars($hoja['descripcion_otros_analisis'], ENT_QUOTES, 'UTF-8');

    $paramsList = [];
    if (!empty($concretoParams)) $paramsList[] = '<strong>Muestra de Concreto, Adoquines, Bloques:</strong> ' . implode(', ', $concretoParams);
    if (!empty($suelosParams)) $paramsList[] = '<strong>Muestras de Suelo:</strong> ' . implode(', ', $suelosParams);
    if (!empty($otrosParams)) $paramsList[] = '<strong>Otros Materiales:</strong> ' . implode(', ', $otrosParams);

    $paramsText = '';
    foreach ($paramsList as $pl) {
        $paramsText .= '<div style="margin-bottom: 5px; font-size: 11px;">' . $pl . '</div>';
    }
    if (empty($paramsText)) {
        $paramsText = '<div style="font-size: 11px; color: #64748b; font-style: italic;">Ninguno seleccionado</div>';
    }

    $fechaLlegada = !empty($hoja['fecha_hora_llegada_laboratorio']) ? date('d/m/Y H:i', strtotime($hoja['fecha_hora_llegada_laboratorio'])) : '—';
    $fechaToma = !empty($hoja['fecha_hora_toma_muestra']) ? date('d/m/Y H:i', strtotime($hoja['fecha_hora_toma_muestra'])) : '—';

    $html = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <style>
            body { font-family: "Helvetica", "Arial", sans-serif; color: #1e293b; margin: 0; padding: 0.5cm; }
            .header-table { width: 100%; border-collapse: collapse; margin-bottom: 20px; }
            .header-table td { vertical-align: middle; }
            .title-doc { font-size: 15px; font-weight: bold; color: #103487; text-align: right; }
            .code-doc { font-size: 11px; color: #64748b; text-align: right; margin-top: 3px; font-weight: bold; }
            .section-title { font-size: 11px; font-weight: bold; text-transform: uppercase; color: #103487; background: #e6eefc; padding: 6px 10px; margin-top: 15px; margin-bottom: 8px; border-left: 3px solid #103487; }
            .info-table { width: 100%; border-collapse: collapse; margin-bottom: 10px; }
            .info-table td { padding: 4px 6px; font-size: 11px; }
            .info-label { font-weight: bold; color: #475569; width: 160px; }
            .info-value { color: #0f172a; border-bottom: 1px solid #cbd5e1; }
            .spec-table { width: 100%; border-collapse: collapse; margin-top: 8px; }
            .spec-table th { background: #f1f5f9; color: #475569; font-weight: bold; padding: 6px; font-size: 10px; text-transform: uppercase; border: 1px solid #cbd5e1; text-align: left; }
            .footer-table { width: 100%; border-collapse: collapse; margin-top: 35px; }
            .signature-box { border-top: 1px solid #cbd5e1; width: 80%; margin: 0 auto; text-align: center; padding-top: 5px; font-size: 10px; }
        </style>
    </head>
    <body>
        <table class="header-table">
            <tr>
                <td>
                    ' . $logoHtml . '
                    <div style="font-size: 14px; font-weight: bold; color: #1e293b; margin-top: 2px;">CYCSA S.A.</div>
                    <div style="font-size: 8px; color: #64748b; text-transform: uppercase;">Laboratorio de Control de Calidad</div>
                </td>
                <td style="text-align: right;">
                    <div class="title-doc">HOJA DE SOLICITUD DE SERVICIO</div>
                    <div class="code-doc">Código: ' . htmlspecialchars($hoja['codigo_documento'] ?? 'CYCSA-RT-FM-13', ENT_QUOTES, 'UTF-8') . '</div>
                    <div style="font-size: 11px; font-weight: bold; color: #475569; margin-top: 5px;">Orden de Servicio: ' . htmlspecialchars($os['codigo_os'], ENT_QUOTES, 'UTF-8') . '</div>
                </td>
            </tr>
        </table>

        <div class="section-title">1. Control Interno y Llegada</div>
        <table class="info-table">
            <tr>
                <td class="info-label">Fecha/Hora Llegada Lab:</td>
                <td class="info-value">' . $fechaLlegada . '</td>
            </tr>
        </table>

        <div class="section-title">2. Datos del Cliente (Solicitante)</div>
        <table class="info-table">
            <tr>
                <td class="info-label">Nombre Empresa/Cliente:</td>
                <td class="info-value" colspan="3">' . htmlspecialchars($hoja['nombre_empresa_o_cliente'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td class="info-label">Dirección Proyecto:</td>
                <td class="info-value" colspan="3">' . htmlspecialchars($hoja['direccion_proyecto'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td class="info-label">Teléfono:</td>
                <td class="info-value">' . htmlspecialchars($hoja['telefono'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                <td class="info-label" style="padding-left: 15px;">Correo Electrónico:</td>
                <td class="info-value">' . htmlspecialchars($hoja['correo_electronico'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td class="info-label">Entrega Muestra Por:</td>
                <td class="info-value" colspan="3">' . htmlspecialchars($hoja['nombre_persona_entrega_muestra'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
        </table>

        <div class="section-title">3. Datos de la Muestra (Información de Origen)</div>
        <div style="margin-bottom: 8px;">
            <span style="font-size: 11px; font-weight: bold; color: #475569; display: block; margin-bottom: 5px;">Naturaleza de la Muestra:</span>
            ' . $naturalezaHtml . '
        </div>
        <table class="info-table">
            <tr>
                <td class="info-label">Procedencia/Pto Muestreo:</td>
                <td class="info-value" colspan="3">' . htmlspecialchars($hoja['procedencia_punto_muestreo'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
            </tr>
            <tr>
                <td class="info-label">Nombre Persona Toma Muestra:</td>
                <td class="info-value">' . htmlspecialchars($hoja['nombre_persona_toma_muestra'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                <td class="info-label" style="padding-left: 15px;">Fecha/Hora Toma Muestra:</td>
                <td class="info-value">' . $fechaToma . '</td>
            </tr>
        </table>

        <div class="section-title">4. Identificación Propia de la Muestra</div>
        <table class="spec-table">
            <thead>
                <tr>
                    <th style="width: 30%;">Nombre / Identificador</th>
                    <th style="width: 40%;">Descripción Física</th>
                    <th style="width: 30%;">Información Adicional / Novedades</th>
                </tr>
            </thead>
            <tbody>
                ' . $identRows . '
            </tbody>
        </table>

        <div class="section-title">5. Parámetros de Ensayo Solicitados</div>
        <div style="padding: 5px 8px; border: 1px dashed #cbd5e1; border-radius: 4px; background: #fafafb;">
            ' . $paramsText . '
        </div>

        <div class="section-title">6. Análisis Adicionales y Observaciones</div>
        <div style="font-size: 11px; margin-bottom: 10px; line-height: 1.4;">
            <strong>Análisis Adicionales:</strong> ' . nl2br(htmlspecialchars($hoja['analisis_adicionales'] ?? 'Ninguno.', ENT_QUOTES, 'UTF-8')) . '
        </div>
        <div style="font-size: 11px; margin-bottom: 15px; line-height: 1.4;">
            <strong>Observaciones Generales:</strong> ' . nl2br(htmlspecialchars($hoja['observaciones'] ?? 'Ninguna.', ENT_QUOTES, 'UTF-8')) . '
        </div>

        <table class="footer-table">
            <tr>
                <td style="width: 50%;">
                    <div class="signature-box">
                        <strong>' . ($hoja['firma_cliente'] ? 'Firmado' : 'Pendiente de Firma') . '</strong><br>
                        Firma del Cliente
                    </div>
                </td>
                <td style="width: 50%;">
                    <div class="signature-box">
                        <strong>' . htmlspecialchars($hoja['nombre_recibe_cycsa'] ?: 'Pendiente', ENT_QUOTES, 'UTF-8') . '</strong><br>
                        Persona de CYCSA que Recibe la Muestra
                    </div>
                </td>
            </tr>
        </table>
    </body>
    </html>';

    $dompdf->loadHtml($html);
    $dompdf->render();
    return $dompdf->output();
}
