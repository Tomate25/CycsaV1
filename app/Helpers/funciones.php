<?php

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dompdf\Dompdf;
use Dompdf\Options;
if (!function_exists('array_is_list')) {
    function array_is_list(array $arr): bool {
        if ($arr === []) {
            return true;
        }
        return array_keys($arr) === range(0, count($arr) - 1);
    }
}
/**
 * Envía un correo electrónico utilizando PHPMailer.
 * Si MAIL_HOST está configurado en .env, usa SMTP (ideal para desarrollo local).
 * Si no, usa la función mail() nativa (ideal para producción en Bluehost).
 */
function enviarCorreo(string $para, string $asunto, string $cuerpoHTML, string $cuerpoTexto = '', array $adjuntos = []): bool {
    $mail = new PHPMailer(true);

    try {
        // Cargar configuración unificada desde config/mail.php si existe
        $mailConfig = [];
        $rutaConfigMail = dirname(__DIR__, 2) . '/config/mail.php';
        if (file_exists($rutaConfigMail)) {
            $mailConfig = require $rutaConfigMail;
        }

        // Configuraciones generales
        $mail->CharSet = 'UTF-8';
        $remitenteCorreo = $_ENV['MAIL_FROM_ADDRESS'] ?? $_ENV['MAIL_FROM'] ?? ($mailConfig['from']['address'] ?? 'notificaciones@cycsanicaragua.com');
        $remitenteNombre = $_ENV['MAIL_FROM_NAME'] ?? $_ENV['APP_NAME'] ?? ($mailConfig['from']['name'] ?? 'CYCSA ERP');

        $mail->setFrom($remitenteCorreo, $remitenteNombre);
        $mail->Sender = $remitenteCorreo; // Parámetro -f para cPanel sendmail/Exim
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
            } elseif (isset($adjunto['ruta']) && file_exists($adjunto['ruta'])) {
                $mail->addAttachment($adjunto['ruta'], $adjunto['nombre'] ?? '');
            }
        }

        // Configuración de transporte
        $mailHost = $_ENV['MAIL_HOST'] ?? ($mailConfig['host'] ?? '');
        $mailUser = $_ENV['MAIL_USER'] ?? ($mailConfig['username'] ?? '');
        $mailPass = $_ENV['MAIL_PASS'] ?? ($mailConfig['password'] ?? '');
        $mailPort = (int)($_ENV['MAIL_PORT'] ?? ($mailConfig['port'] ?? 587));
        $mailSecure = strtolower($_ENV['MAIL_ENCRYPTION'] ?? $_ENV['MAIL_SECURE'] ?? ($mailConfig['encryption'] ?? 'tls'));

        if (!empty($mailHost)) {
            $mail->isSMTP();
            $mail->Host       = $mailHost;
            $mail->SMTPAuth   = !empty($mailUser) && !empty($mailPass);
            if ($mail->SMTPAuth) {
                $mail->Username = $mailUser;
                $mail->Password = $mailPass;
            }
            
            if ($mailSecure === 'ssl' || $mailPort === 465) {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_SMTPS;
                $mail->Port       = 465;
            } else {
                $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
                $mail->Port       = $mailPort > 0 ? $mailPort : 587;
            }

            // Opciones SSL permisivas para certificados de hosting compartido
            $mail->SMTPOptions = [
                'ssl' => [
                    'verify_peer' => false,
                    'verify_peer_name' => false,
                    'allow_self_signed' => true
                ]
            ];
        } else {
            // Uso de la función mail() local en servidores de hosting
            $mail->isMail();
        }

        return $mail->send();
    } catch (Exception $e) {
        $logDir = dirname(__DIR__, 2) . '/storage/logs';
        if (!is_dir($logDir)) {
            @mkdir($logDir, 0777, true);
        }
        $logMsg = "[" . date('Y-m-d H:i:s') . "] Fallo al enviar correo a {$para}: " . $mail->ErrorInfo . " | Excepción: " . $e->getMessage() . "\n";
        @file_put_contents($logDir . '/mail_errors.log', $logMsg, FILE_APPEND);
        error_log($logMsg);
        return false;
    }
}

/**
 * Extrae el contenido de un archivo DOCX de Microsoft Word y lo convierte a HTML limpio
 * soportando encabezados, párrafos, tablas, negrita/cursiva/subrayado, alineación
 * y embebiendo automáticamente todas las imágenes internas como base64.
 */
function extraerContenidoDocxAHtml(string $rutaDocx): string {
    if (!file_exists($rutaDocx) || !is_readable($rutaDocx)) {
        return '';
    }

    $zip = new \ZipArchive();
    if ($zip->open($rutaDocx) !== true) {
        return '';
    }

    // 1. Mapear rId a imágenes internas desde word/_rels/document.xml.rels
    $mediaMap = [];
    $relsXml = $zip->getFromName('word/_rels/document.xml.rels');
    if ($relsXml !== false) {
        $relsDoc = new \DOMDocument();
        @$relsDoc->loadXML($relsXml);
        foreach ($relsDoc->getElementsByTagName('Relationship') as $rel) {
            $rId = $rel->getAttribute('Id');
            $target = $rel->getAttribute('Target');
            if (stripos($target, 'media/') !== false) {
                $imgFilename = basename($target);
                $imgData = $zip->getFromName('word/media/' . $imgFilename);
                if ($imgData !== false) {
                    $ext = strtolower(pathinfo($imgFilename, PATHINFO_EXTENSION));
                    $mime = match($ext) {
                        'png' => 'image/png',
                        'webp' => 'image/webp',
                        'gif' => 'image/gif',
                        default => 'image/jpeg'
                    };
                    $mediaMap[$rId] = 'data:' . $mime . ';base64,' . base64_encode($imgData);
                }
            }
        }
    }

    // 2. Extraer y procesar word/document.xml
    $docXml = $zip->getFromName('word/document.xml');
    $zip->close();
    if ($docXml === false) {
        return '';
    }

    $doc = new \DOMDocument();
    @$doc->loadXML($docXml);
    $body = $doc->getElementsByTagName('body')->item(0);
    if (!$body) {
        return '';
    }

    $xpath = new \DOMXPath($doc);

    $procesarParrafo = function(\DOMElement $pNode) use ($mediaMap, $xpath): string {
        $pAlign = 'left';
        $pPr = $pNode->getElementsByTagName('pPr')->item(0);
        $isHeading = false;
        if ($pPr) {
            $jc = $pPr->getElementsByTagName('jc')->item(0);
            if ($jc && $jc->hasAttribute('w:val')) {
                $val = strtolower($jc->getAttribute('w:val'));
                if (in_array($val, ['center', 'right', 'both', 'justify'])) {
                    $pAlign = ($val === 'both' || $val === 'justify') ? 'justify' : $val;
                }
            }
            $pStyle = $pPr->getElementsByTagName('pStyle')->item(0);
            if ($pStyle && $pStyle->hasAttribute('w:val')) {
                $styleVal = strtolower($pStyle->getAttribute('w:val'));
                if (str_contains($styleVal, 'heading') || str_contains($styleVal, 'title') || str_contains($styleVal, 'titulo')) {
                    $isHeading = true;
                }
            }
        }

        $pHtml = '';
        $tieneContenido = false;
        $runs = $xpath->query('.//w:r | .//w:drawing | .//w:pict', $pNode);

        foreach ($runs as $item) {
            if ($item->nodeName === 'w:drawing' || $item->nodeName === 'w:pict') {
                $blips = $xpath->query('.//*[@r:embed or @r:id]', $item);
                foreach ($blips as $blip) {
                    $rId = $blip->getAttribute('r:embed') ?: $blip->getAttribute('r:id');
                    if (!empty($rId) && isset($mediaMap[$rId])) {
                        $pHtml .= '<div style="text-align: center; margin: 6px 0;"><img src="' . $mediaMap[$rId] . '" style="max-width: 95%; max-height: 16cm; height: auto; border: 1px solid #cbd5e1;"></div>';
                        $tieneContenido = true;
                    }
                }
            } elseif ($item->nodeName === 'w:r') {
                $blips = $xpath->query('.//*[@r:embed or @r:id]', $item);
                if ($blips->length > 0) {
                    foreach ($blips as $blip) {
                        $rId = $blip->getAttribute('r:embed') ?: $blip->getAttribute('r:id');
                        if (!empty($rId) && isset($mediaMap[$rId])) {
                            $pHtml .= '<div style="text-align: center; margin: 6px 0;"><img src="' . $mediaMap[$rId] . '" style="max-width: 95%; max-height: 16cm; height: auto; border: 1px solid #cbd5e1;"></div>';
                            $tieneContenido = true;
                        }
                    }
                }

                $rPr = $item->getElementsByTagName('rPr')->item(0);
                $isBold = false;
                $isItalic = false;
                $isUnderline = false;
                $colorHex = '';
                if ($rPr) {
                    $isBold = $rPr->getElementsByTagName('b')->length > 0;
                    $isItalic = $rPr->getElementsByTagName('i')->length > 0;
                    $isUnderline = $rPr->getElementsByTagName('u')->length > 0;
                    $colorNode = $rPr->getElementsByTagName('color')->item(0);
                    if ($colorNode && $colorNode->hasAttribute('w:val')) {
                        $c = $colorNode->getAttribute('w:val');
                        if ($c !== 'auto' && preg_match('/^[0-9A-Fa-f]{6}$/', $c)) {
                            $colorHex = '#' . $c;
                        }
                    }
                }

                $tNodes = $item->getElementsByTagName('t');
                $runText = '';
                foreach ($tNodes as $t) {
                    $runText .= $t->nodeValue;
                }
                $brNodes = $item->getElementsByTagName('br');
                if ($brNodes->length > 0) {
                    $runText .= str_repeat("\n", $brNodes->length);
                }

                if ($runText !== '') {
                    $safeText = htmlspecialchars($runText, ENT_QUOTES, 'UTF-8');
                    $safeText = nl2br($safeText);
                    $styleParts = [];
                    if (!empty($colorHex)) {
                        $styleParts[] = "color: {$colorHex}";
                    }
                    $styleAttr = !empty($styleParts) ? ' style="' . implode(';', $styleParts) . '"' : '';

                    $formatted = $safeText;
                    if ($isBold) $formatted = "<strong>{$formatted}</strong>";
                    if ($isItalic) $formatted = "<em>{$formatted}</em>";
                    if ($isUnderline) $formatted = "<u>{$formatted}</u>";
                    if ($styleAttr) $formatted = "<span{$styleAttr}>{$formatted}</span>";

                    $pHtml .= $formatted;
                    $tieneContenido = true;
                }
            }
        }

        if (!$tieneContenido) return '';

        $tag = $isHeading ? 'h3' : 'p';
        $styleExtra = $isHeading
            ? 'margin-top: 10px; margin-bottom: 4px; color: #103487; font-size: 11px; text-align: ' . $pAlign . ';'
            : 'margin: 3px 0; text-align: ' . $pAlign . '; font-size: 9px; line-height: 1.35;';

        return "<{$tag} style=\"{$styleExtra}\">{$pHtml}</{$tag}>";
    };

    $procesarTabla = function(\DOMElement $tblNode) use ($procesarParrafo): string {
        $htmlTbl = '<table style="width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 8.5px;">';
        $rows = $tblNode->getElementsByTagName('tr');
        $isFirstRow = true;
        foreach ($rows as $tr) {
            $htmlTbl .= '<tr>';
            $cells = $tr->getElementsByTagName('tc');
            foreach ($cells as $tc) {
                $cellHtml = '';
                foreach ($tc->getElementsByTagName('p') as $p) {
                    $cellHtml .= $procesarParrafo($p);
                }
                $cellTag = $isFirstRow ? 'th' : 'td';
                $cellStyle = $isFirstRow
                    ? 'border: 1px solid #cbd5e1; padding: 4px 6px; background-color: #f1f5f9; font-weight: bold; text-align: left;'
                    : 'border: 1px solid #cbd5e1; padding: 4px 6px; vertical-align: top;';
                $htmlTbl .= "<{$cellTag} style=\"{$cellStyle}\">{$cellHtml}</{$cellTag}>";
            }
            $htmlTbl .= '</tr>';
            $isFirstRow = false;
        }
        $htmlTbl .= '</table>';
        return $htmlTbl;
    };

    $htmlFinal = '';
    foreach ($body->childNodes as $child) {
        if ($child->nodeName === 'w:p') {
            $htmlFinal .= $procesarParrafo($child);
        } elseif ($child->nodeName === 'w:tbl') {
            $htmlFinal .= $procesarTabla($child);
        }
    }

    return $htmlFinal;
}

/**
 * Extrae las hojas de cálculo de un archivo Excel (.xlsx / .xls) y las convierte a tablas HTML limpias.
 */
function extraerExcelAHtml(string $rutaExcel): string {
    if (!file_exists($rutaExcel) || !is_readable($rutaExcel)) {
        return '';
    }

    $cmdPython = 'python -c "import openpyxl, html, sys; wb = openpyxl.load_workbook(sys.argv[1], data_only=True); out = [];
for s in wb.sheetnames[:3]:
    ws = wb[s]
    out.append(f\'<h4 style=\"color:#103487; margin: 8px 0 3px 0; font-size: 10px;\">Hoja: {html.escape(s)}</h4>\')
    out.append(\'<table style=\"width:100%; border-collapse:collapse; margin-bottom:10px; font-size:8px;\">\')
    rows = list(ws.iter_rows(values_only=True))
    if not rows: continue
    out.append(\'<thead><tr>\')
    for cell in rows[0]:
        val = html.escape(str(cell) if cell is not None else \"\")
        out.append(f\'<th style=\"border:1px solid #cbd5e1; padding:3px 5px; background:#f1f5f9; text-align:left; font-weight:bold;\">{val}</th>\')
    out.append(\'</tr></thead><tbody>\')
    for row in rows[1:100]:
        if not any(c is not None for c in row): continue
        out.append(\'<tr>\')
        for cell in row:
            val = html.escape(str(cell) if cell is not None else \"\")
            out.append(f\'<td style=\"border:1px solid #cbd5e1; padding:3px 5px;\">{val}</td>\')
        out.append(\'</tr>\')
    out.append(\'</tbody></table>\')
print(\"\".join(out))" ' . escapeshellarg($rutaExcel);

    $salida = [];
    $retCode = 1;
    @exec($cmdPython, $salida, $retCode);
    if ($retCode === 0 && !empty($salida)) {
        return implode("\n", $salida);
    }

    return '';
}

/**
 * Convierte un archivo CSV en una tabla HTML limpia y formateada.
 */
function extraerCsvAHtml(string $rutaCsv): string {
    if (!file_exists($rutaCsv) || !is_readable($rutaCsv)) {
        return '';
    }

    $handle = @fopen($rutaCsv, 'r');
    if (!$handle) return '';

    $html = '<table style="width: 100%; border-collapse: collapse; margin: 8px 0; font-size: 8px;">';
    $first = true;
    $count = 0;
    while (($data = fgetcsv($handle, 2000, ',')) !== false && $count < 100) {
        $tag = $first ? 'th' : 'td';
        $style = $first 
            ? 'border: 1px solid #cbd5e1; padding: 3px 5px; background: #f1f5f9; font-weight: bold; text-align: left;'
            : 'border: 1px solid #cbd5e1; padding: 3px 5px;';
        $html .= '<tr>';
        foreach ($data as $cell) {
            $html .= "<{$tag} style=\"{$style}\">" . htmlspecialchars($cell, ENT_QUOTES, 'UTF-8') . "</{$tag}>";
        }
        $html .= '</tr>';
        $first = false;
        $count++;
    }
    fclose($handle);
    $html .= '</table>';
    return $html;
}

/**
 * Agrega numeración oficial "Página X de Y" en el pie de página de todas las páginas
 * generadas por Dompdf utilizando su Canvas nativo con cálculo dinámico de dimensiones
 * y tarjeta tipo badge protectora en fondo blanco de alto contraste.
 *
 * @param \Dompdf\Dompdf $dompdf Instancia de Dompdf tras ejecutar render()
 * @param string $formato Texto a mostrar, soporta {PAGE_NUM} y {PAGE_COUNT}
 * @param float $bottomMargin Distancia en puntos desde el borde inferior de la hoja
 * @param float $rightMargin Distancia en puntos desde el borde derecho de la hoja
 * @param float $tamanoFuente Tamaño de fuente en puntos (por defecto 9.0 pt)
 * @param array $color Color RGB normalizado [r, g, b] entre 0 y 1 (por defecto Azul CYCSA [0.06, 0.20, 0.53])
 * @param bool $conFondo Si es true, dibuja una pastilla/badge blanca protectora con borde
 */
function agregarNumeracionPaginasDompdf(
    \Dompdf\Dompdf $dompdf,
    string $formato = 'Página {PAGE_NUM} de {PAGE_COUNT}',
    float $bottomMargin = 26,
    float $rightMargin = 40,
    float $tamanoFuente = 9.0,
    array $color = [0.06, 0.20, 0.53],
    bool $conFondo = true
): void {
    $canvas = $dompdf->getCanvas();
    $canvas->page_script(function (int $pageNumber, int $pageCount, $canvas, $fontMetrics) use ($formato, $bottomMargin, $rightMargin, $tamanoFuente, $color, $conFondo) {
        $font = $fontMetrics->getFont('Helvetica', 'bold');
        $text = str_replace(['{PAGE_NUM}', '{PAGE_COUNT}'], [$pageNumber, $pageCount], $formato);
        $textWidth = $fontMetrics->getTextWidth($text, $font, $tamanoFuente);
        $x = $canvas->get_width() - $textWidth - $rightMargin;
        $y = $canvas->get_height() - $bottomMargin;

        if ($conFondo) {
            $padX = 8;
            $padY = 4;
            $canvas->filled_rectangle($x - $padX, $y - $padY, $textWidth + ($padX * 2), $tamanoFuente + ($padY * 2), [1, 1, 1]);
            $canvas->rectangle($x - $padX, $y - $padY, $textWidth + ($padX * 2), $tamanoFuente + ($padY * 2), [0.75, 0.80, 0.88], 0.75);
        }

        $canvas->text($x, $y, $text, $font, $tamanoFuente, $color);
    });
}

/**
 * Fusiona un archivo PDF adjunto al final del PDF binario principal generado por Dompdf
 * y aplica una numeración unificada continua ("Página X de Y") a todas las hojas resultantes
 * utilizando la biblioteca pypdf/reportlab de Python. Si ocurre algún error, retorna el PDF principal intacto.
 */
function fusionarPdfConAdjunto(string $pdfPrincipalBytes, string $rutaPdfAdjunto): string {
    if (!file_exists($rutaPdfAdjunto) || !is_readable($rutaPdfAdjunto)) {
        return $pdfPrincipalBytes;
    }

    $cacheDir = dirname(__DIR__, 2) . '/storage/cache';
    if (!is_dir($cacheDir)) {
        @mkdir($cacheDir, 0777, true);
    }

    $tempPrincipal = $cacheDir . '/quote_' . uniqid() . '.pdf';
    $tempSalida = $cacheDir . '/merged_' . uniqid() . '.pdf';

    file_put_contents($tempPrincipal, $pdfPrincipalBytes);

    $scriptPython = __DIR__ . '/fusionar_y_enumerar.py';
    if (file_exists($scriptPython)) {
        $cmdPython = 'python ' . escapeshellarg($scriptPython) . ' merge ' 
            . escapeshellarg($tempPrincipal) . ' ' . escapeshellarg($rutaPdfAdjunto) . ' ' . escapeshellarg($tempSalida);
    } else {
        $cmdPython = 'python -c "import sys; from pypdf import PdfWriter; w = PdfWriter(); w.append(sys.argv[1]); w.append(sys.argv[2]); w.write(sys.argv[3])" ' 
            . escapeshellarg($tempPrincipal) . ' ' . escapeshellarg($rutaPdfAdjunto) . ' ' . escapeshellarg($tempSalida);
    }

    $salida = [];
    $retCode = 1;
    @exec($cmdPython, $salida, $retCode);

    if ($retCode === 0 && file_exists($tempSalida) && filesize($tempSalida) > 0) {
        $mergedBytes = file_get_contents($tempSalida);
        @unlink($tempPrincipal);
        @unlink($tempSalida);
        return $mergedBytes;
    }

    @unlink($tempPrincipal);
    if (file_exists($tempSalida)) {
        @unlink($tempSalida);
    }

    return $pdfPrincipalBytes;
}

/**
 * Procesa el archivo adjunto de una cotización para incluirlo en la generación de PDF.
 * Soporta DOCX (extrayendo formato, textos e imágenes), imágenes (JPG, PNG, WEBP), texto plano y PDF.
 *
 * @param array $cotizacion
 * @param string $logoHtml
 * @return array{html: string, ruta_pdf: ?string}
 */
function procesarArchivoAdjuntoCotizacion(array $cotizacion, string $logoHtml = ''): array {
    $resultado = ['html' => '', 'ruta_pdf' => null];

    if (empty($cotizacion['archivo_adjunto'])) {
        return $resultado;
    }

    $relPath = ltrim($cotizacion['archivo_adjunto'], '/\\');
    $rutaAdjunto = dirname(__DIR__, 2) . '/publico/' . $relPath;
    if (!file_exists($rutaAdjunto)) {
        $rutaAdjunto = dirname(__DIR__, 2) . '/' . $relPath;
    }

    if (!file_exists($rutaAdjunto) || !is_file($rutaAdjunto)) {
        return $resultado;
    }

    $ext = strtolower(pathinfo($rutaAdjunto, PATHINFO_EXTENSION));
    $nombreArchivo = htmlspecialchars(basename($rutaAdjunto), ENT_QUOTES, 'UTF-8');
    $codigo = htmlspecialchars($cotizacion['codigo'] ?? '', ENT_QUOTES, 'UTF-8');
    $proyectoNombre = htmlspecialchars($cotizacion['nombre_proyecto'] ?? '', ENT_QUOTES, 'UTF-8');
    $version = (string)($cotizacion['version'] ?? 1);

    if ($ext === 'docx') {
        $contenidoDocx = extraerContenidoDocxAHtml($rutaAdjunto);
        if (!empty(trim($contenidoDocx))) {
            $resultado['html'] = "
            <div style=\"page-break-before: always;\">
                <div class=\"header-box\">
                    <table style=\"width: 100%;\">
                        <tr>
                            <td style=\"width: 48%; vertical-align: bottom;\">
                                {$logoHtml}
                                <div style=\"font-size: 8.5px; font-weight: bold; color: #1e293b; margin-bottom: 2px;\">Cód. Doc CYCSA-RG-FM-31 Documento Complementario Adjunto</div>
                                <span style=\"font-size: 7.5px; color: #64748b; font-weight: bold; text-transform: uppercase;\">Laboratorio de Ensayos y Control de Calidad</span>
                            </td>
                            <td style=\"width: 52%; text-align: right; vertical-align: bottom;\">
                                <span style=\"font-size: 13px; font-weight: bold; color: #103487; text-transform: uppercase;\">Anexo Adjunto a Cotización</span><br>
                                <span style=\"font-size: 14px; font-weight: bold; color: #1e293b;\">{$codigo}</span>
                                <span style=\"font-size: 8.5px; color: #64748b; margin-left: 8px;\">Proyecto: {$proyectoNombre} &bull; Archivo: {$nombreArchivo}</span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div style=\"font-size: 8.5px; line-height: 1.4; color: #1e293b;\">
                    {$contenidoDocx}
                </div>
            </div>";
        }
    } elseif (in_array($ext, ['jpg', 'jpeg', 'png', 'webp', 'gif'])) {
        $imgData = @file_get_contents($rutaAdjunto);
        if ($imgData !== false) {
            $mime = match($ext) {
                'png' => 'image/png',
                'webp' => 'image/webp',
                'gif' => 'image/gif',
                default => 'image/jpeg'
            };
            $b64 = 'data:' . $mime . ';base64,' . base64_encode($imgData);
            $resultado['html'] = "
            <div style=\"page-break-before: always;\">
                <div class=\"header-box\">
                    <table style=\"width: 100%;\">
                        <tr>
                            <td style=\"width: 48%; vertical-align: bottom;\">
                                {$logoHtml}
                                <div style=\"font-size: 8.5px; font-weight: bold; color: #1e293b; margin-bottom: 2px;\">Cód. Doc CYCSA-RG-FM-31 Documento Complementario Adjunto</div>
                                <span style=\"font-size: 7.5px; color: #64748b; font-weight: bold; text-transform: uppercase;\">Laboratorio de Ensayos y Control de Calidad</span>
                            </td>
                            <td style=\"width: 52%; text-align: right; vertical-align: bottom;\">
                                <span style=\"font-size: 13px; font-weight: bold; color: #103487; text-transform: uppercase;\">Anexo Gráfico Adjunto</span><br>
                                <span style=\"font-size: 14px; font-weight: bold; color: #1e293b;\">{$codigo}</span>
                                <span style=\"font-size: 8.5px; color: #64748b; margin-left: 8px;\">Proyecto: {$proyectoNombre} &bull; Archivo: {$nombreArchivo}</span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div style=\"text-align: center; margin-top: 15px;\">
                    <img src=\"{$b64}\" style=\"max-width: 100%; max-height: 22cm; height: auto; border: 1px solid #cbd5e1; border-radius: 4px;\">
                </div>
            </div>";
        }
    } elseif ($ext === 'pdf') {
        $resultado['ruta_pdf'] = $rutaAdjunto;
    } elseif ($ext === 'xlsx' || $ext === 'xls') {
        $htmlExcel = extraerExcelAHtml($rutaAdjunto);
        if (!empty($htmlExcel)) {
            $resultado['html'] = "
            <div style=\"page-break-before: always;\">
                <div class=\"header-box\">
                    <table style=\"width: 100%;\">
                        <tr>
                            <td style=\"width: 48%; vertical-align: bottom;\">
                                {$logoHtml}
                                <div style=\"font-size: 8.5px; font-weight: bold; color: #1e293b; margin-bottom: 2px;\">Cód. Doc CYCSA-RG-FM-31 Documento Complementario Adjunto</div>
                                <span style=\"font-size: 7.5px; color: #64748b; font-weight: bold; text-transform: uppercase;\">Laboratorio de Ensayos y Control de Calidad</span>
                            </td>
                            <td style=\"width: 52%; text-align: right; vertical-align: bottom;\">
                                <span style=\"font-size: 13px; font-weight: bold; color: #103487; text-transform: uppercase;\">Anexo Hoja de Cálculo Adjunta</span><br>
                                <span style=\"font-size: 14px; font-weight: bold; color: #1e293b;\">{$codigo}</span>
                                <span style=\"font-size: 8.5px; color: #64748b; margin-left: 8px;\">Proyecto: {$proyectoNombre} &bull; Archivo: {$nombreArchivo}</span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div style=\"font-size: 8.5px; line-height: 1.4; color: #1e293b;\">
                    {$htmlExcel}
                </div>
            </div>";
        }
    } elseif ($ext === 'csv') {
        $htmlCsv = extraerCsvAHtml($rutaAdjunto);
        if (!empty($htmlCsv)) {
            $resultado['html'] = "
            <div style=\"page-break-before: always;\">
                <div class=\"header-box\">
                    <table style=\"width: 100%;\">
                        <tr>
                            <td style=\"width: 48%; vertical-align: bottom;\">
                                {$logoHtml}
                                <div style=\"font-size: 8.5px; font-weight: bold; color: #1e293b; margin-bottom: 2px;\">Cód. Doc CYCSA-RG-FM-31 Documento Complementario Adjunto</div>
                                <span style=\"font-size: 7.5px; color: #64748b; font-weight: bold; text-transform: uppercase;\">Laboratorio de Ensayos y Control de Calidad</span>
                            </td>
                            <td style=\"width: 52%; text-align: right; vertical-align: bottom;\">
                                <span style=\"font-size: 13px; font-weight: bold; color: #103487; text-transform: uppercase;\">Anexo de Datos CSV Adjunto</span><br>
                                <span style=\"font-size: 14px; font-weight: bold; color: #1e293b;\">{$codigo}</span>
                                <span style=\"font-size: 8.5px; color: #64748b; margin-left: 8px;\">Proyecto: {$proyectoNombre} &bull; Archivo: {$nombreArchivo}</span>
                            </td>
                        </tr>
                    </table>
                </div>
                <div style=\"font-size: 8.5px; line-height: 1.4; color: #1e293b;\">
                    {$htmlCsv}
                </div>
            </div>";
        }
    } elseif ($ext === 'txt') {
        $txtData = @file_get_contents($rutaAdjunto);
        if ($txtData !== false) {
            $safeTxt = htmlspecialchars($txtData, ENT_QUOTES, 'UTF-8');
            $resultado['html'] = "
            <div style=\"page-break-before: always;\">
                <div class=\"header-box\">
                    <table style=\"width: 100%;\">
                        <tr>
                            <td style=\"width: 48%; vertical-align: bottom;\">
                                {$logoHtml}
                                <div style=\"font-size: 8.5px; font-weight: bold; color: #1e293b; margin-bottom: 2px;\">Cód. Doc CYCSA-RG-FM-31 Documento Complementario Adjunto</div>
                                <span style=\"font-size: 7.5px; color: #64748b; font-weight: bold; text-transform: uppercase;\">Laboratorio de Ensayos y Control de Calidad</span>
                            </td>
                            <td style=\"width: 52%; text-align: right; vertical-align: bottom;\">
                                <span style=\"font-size: 13px; font-weight: bold; color: #103487; text-transform: uppercase;\">Anexo de Texto Adjunto</span><br>
                                <span style=\"font-size: 14px; font-weight: bold; color: #1e293b;\">{$codigo}</span>
                                <span style=\"font-size: 8.5px; color: #64748b; margin-left: 8px;\">Proyecto: {$proyectoNombre} &bull; Archivo: {$nombreArchivo}</span>
                            </td>
                        </tr>
                    </table>
                </div>
                <pre style=\"font-family: monospace; font-size: 8.5px; line-height: 1.4; color: #1e293b; white-space: pre-wrap; background: #f8fafc; border: 1px solid #cbd5e1; padding: 10px; border-radius: 4px;\">{$safeTxt}</pre>
            </div>";
        }
    }

    // Fallback universal: Si el archivo no es un PDF y no generó HTML visual directo,
    // generar la Hoja Oficial de Constancia de Documento Digital Adjunto
    if (empty($resultado['html']) && empty($resultado['ruta_pdf'])) {
        $tamano = filesize($rutaAdjunto);
        $tamanoFormateado = $tamano >= 1048576 
            ? number_format($tamano / 1048576, 2) . ' MB' 
            : number_format($tamano / 1024, 1) . ' KB';
        $sha256 = hash_file('sha256', $rutaAdjunto);

        $resultado['html'] = "
        <div style=\"page-break-before: always;\">
            <div class=\"header-box\">
                <table style=\"width: 100%;\">
                    <tr>
                        <td style=\"width: 48%; vertical-align: bottom;\">
                            {$logoHtml}
                            <div style=\"font-size: 8.5px; font-weight: bold; color: #1e293b; margin-bottom: 2px;\">Cód. Doc CYCSA-RG-FM-31 Documento Complementario Adjunto</div>
                            <span style=\"font-size: 7.5px; color: #64748b; font-weight: bold; text-transform: uppercase;\">Laboratorio de Ensayos y Control de Calidad</span>
                        </td>
                        <td style=\"width: 52%; text-align: right; vertical-align: bottom;\">
                            <span style=\"font-size: 13px; font-weight: bold; color: #103487; text-transform: uppercase;\">Constancia de Documento Adjunto</span><br>
                            <span style=\"font-size: 14px; font-weight: bold; color: #1e293b;\">{$codigo}</span>
                            <span style=\"font-size: 8.5px; color: #64748b; margin-left: 8px;\">Proyecto: {$proyectoNombre}</span>
                        </td>
                    </tr>
                </table>
            </div>

            <div style=\"background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 16px; margin-top: 15px;\">
                <table style=\"width: 100%; border-collapse: collapse; font-size: 9px;\">
                    <tr>
                        <td style=\"padding: 6px; color: #64748b; width: 32%; border-bottom: 1px solid #e2e8f0;\">Nombre del Documento:</td>
                        <td style=\"padding: 6px; font-weight: bold; color: #0f172a; border-bottom: 1px solid #e2e8f0;\">{$nombreArchivo}</td>
                    </tr>
                    <tr>
                        <td style=\"padding: 6px; color: #64748b; border-bottom: 1px solid #e2e8f0;\">Formato / Tipo de Archivo:</td>
                        <td style=\"padding: 6px; font-weight: bold; text-transform: uppercase; color: #1e40af; border-bottom: 1px solid #e2e8f0;\">.{$ext}</td>
                    </tr>
                    <tr>
                        <td style=\"padding: 6px; color: #64748b; border-bottom: 1px solid #e2e8f0;\">Tamaño en Disco:</td>
                        <td style=\"padding: 6px; color: #334155; border-bottom: 1px solid #e2e8f0;\">{$tamanoFormateado}</td>
                    </tr>
                    <tr>
                        <td style=\"padding: 6px; color: #64748b; border-bottom: 1px solid #e2e8f0;\">Huella Digital (SHA-256):</td>
                        <td style=\"padding: 6px; font-family: monospace; font-size: 7.5px; color: #475569; border-bottom: 1px solid #e2e8f0;\">{$sha256}</td>
                    </tr>
                    <tr>
                        <td style=\"padding: 6px; color: #64748b;\">Estado de Custodia:</td>
                        <td style=\"padding: 6px; color: #16a34a; font-weight: bold;\">&bull; Resguardado y Vinculado en Expediente Digital CYCSA</td>
                    </tr>
                </table>
                <div style=\"margin-top: 16px; font-size: 8px; color: #475569; line-height: 1.4; border-top: 1px dashed #cbd5e1; padding-top: 10px;\">
                    <strong>Nota de Validez Oficial:</strong> El archivo complementario arriba acreditado ha sido adjuntado de manera formal por el personal técnico a la presente cotización comercial. El archivo fuente reposa íntegro e inalterable en los servidores del sistema ERP & LIMS para auditorías, verificación y trazabilidad de calidad.
                </div>
            </div>
        </div>";
    }

    return $resultado;
}

/**
 * Genera el contenido binario de una cotización en formato PDF usando Dompdf.
 */
function generarCotizacionPDF(array $cotizacion, array $detalles): string {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $options->set('tempDir', dirname(__DIR__, 2) . '/storage/cache');
    $options->set('fontCache', dirname(__DIR__, 2) . '/storage/cache');
    $dompdf = new Dompdf($options);

    $logoPath = dirname(__DIR__, 2) . '/publico/img/logo_cycsa.jpg';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
    }

    $bgPath = dirname(__DIR__, 2) . '/publico/img/hoja_vertical.jpg';
    $bgBase64 = '';
    if (file_exists($bgPath)) {
        $bgBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($bgPath));
    }

    $qrPath = dirname(__DIR__, 2) . '/publico/img/qr_terminos.png';
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

    $bodyStyle = "margin: 0; padding: 0;";
    $pageMarginTop = "1.5cm";
    $pageMarginBottom = "1.5cm";
    $pageMarginLeft = "1.5cm";
    $pageMarginRight = "1.5cm";
    $fondoHtml = "";

    if (!empty($bgBase64)) {
        $pageMarginTop = "4.6cm";
        $pageMarginBottom = "3.4cm";
        $pageMarginLeft = "1.8cm";
        $pageMarginRight = "1.8cm";
        $fondoHtml = "<div class=\"hoja-fondo\"><img src=\"{$bgBase64}\" style=\"width: 100%; height: 100%;\"></div>";
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
            <td style=\"text-align: right; color: #dc2626; padding: 3px 6px; font-size: 9px;\">Descuento:</td>
            <td style=\"text-align: right; font-weight: bold; color: #dc2626; padding: 3px 6px; font-size: 9px;\">- {$simboloMoneda} {$descuentoVal}</td>
        </tr>
        <tr>
            <td style=\"text-align: right; color: #64748b; padding: 3px 6px; font-size: 9px;\">Sub-total:</td>
            <td style=\"text-align: right; font-weight: bold; padding: 3px 6px; font-size: 9px;\">{$simboloMoneda} {$neto}</td>
        </tr>";
    }

    $ivaLabel = "Impuesto (15%):";
    if (isset($cotizacion['exonerado']) && (int)$cotizacion['exonerado'] === 1) {
        $exNo = !empty($cotizacion['exoneracion_no']) ? " (No. " . htmlspecialchars($cotizacion['exoneracion_no'], ENT_QUOTES, 'UTF-8') . ")" : "";
        $ivaLabel = "Impuesto Exonerado{$exNo}:";
    }

    $rowsHtml = '';
    foreach ($detalles as $index => $det) {
        $numLinea = $index + 1;
        $desc = htmlspecialchars($det['descripcion_ensayo'] ?? ($det['ensayo_nombre'] ?? 'Servicio de Ensayo'), ENT_QUOTES, 'UTF-8');
        $descComercial = htmlspecialchars($det['nombre_comercial'] ?? '', ENT_QUOTES, 'UTF-8');
        
        $descHtml = "<strong>{$desc}</strong>";
        if (!empty($descComercial) && $descComercial !== $desc) {
            $descHtml .= "<br><span style=\"color: #64748b; font-size: 8px;\">Comercial: {$descComercial}</span>";
        }

        $condiciones = htmlspecialchars($det['condiciones_muestra'] ?? '', ENT_QUOTES, 'UTF-8');
        $condHtml = !empty($condiciones) 
            ? "<div style=\"background: #fffbeb; border: 1px solid #fef3c7; padding: 2px 4px; border-radius: 2px; color: #78350f;\">{$condiciones}</div>"
            : '<span style="color: #94a3b8; font-style: italic;">Estándar</span>';

        $procedimientoText = htmlspecialchars($det['procedimiento'] ?? ($det['norma_astm'] ?? 'Norma ASTM'), ENT_QUOTES, 'UTF-8');
        $unidadText = htmlspecialchars($det['unidad_medida'] ?? 'Ensayo', ENT_QUOTES, 'UTF-8');
        $cant = number_format($det['cantidad'], 0);
        $precio = number_format($det['precio_unitario'], 2, '.', ',');
        $sub = number_format($det['subtotal'], 2, '.', ',');

        $rowsHtml .= "
        <tr>
            <td style=\"border: 1px solid #cbd5e1; padding: 4px 3px; font-size: 8.5px; text-align: center; font-weight: bold; color: #0f172a;\">{$numLinea}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 4px 5px; font-size: 8.5px; vertical-align: top;\">{$descHtml}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 4px 5px; font-size: 8px; vertical-align: top;\">{$condHtml}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 4px 5px; font-size: 8px; font-family: monospace; color: #1e40af; font-weight: bold; vertical-align: top;\">{$procedimientoText}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 4px 3px; font-size: 8px; text-align: center; color: #334155; vertical-align: top;\">{$unidadText}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 4px 3px; font-size: 8.5px; text-align: center; font-weight: bold; vertical-align: top;\">{$cant}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 4px 5px; font-size: 8.5px; text-align: right; color: #334155; vertical-align: top;\">{$simboloMoneda} {$precio}</td>
            <td style=\"border: 1px solid #cbd5e1; padding: 4px 5px; font-size: 8.5px; text-align: right; font-weight: bold; color: #0f172a; vertical-align: top;\">{$simboloMoneda} {$sub}</td>
        </tr>";
    }

    $configNotas = json_decode($cotizacion['configuracion_notas'] ?? '', true) ?: [];
    $notasDisponibles = [
        'digital_pdf' => '<strong>Entrega Digital:</strong> Los informes de ensayo se entregan únicamente en formato digital (.PDF). Serán enviados al correo del contacto designado por el cliente.',
        'no_movilizacion' => '<strong>Movilización:</strong> No incluye movilización por traslado de muestras.',
        'entrega_laboratorio' => '<strong>Lugar de Entrega:</strong> Cliente toma las muestras y las entrega en Laboratorio CYCSA ubicado Km 83.5 Carretera León Managua.',
        'concreto' => '<strong>Muestreo de Concreto (Cilindros):</strong> El cliente deberá entregar los cilindros de concreto debidamente identificados (Nombre, Ubicación, Resistencia, Revenimiento) y de dimensiones estándar CYCSA-PE-07 (4"x8" o 6"x12").',
        'laboratorio_lleno' => '<strong>Condición de Tiempos:</strong> Los tiempos de entrega aplican a partir del ingreso de las muestras. La disponibilidad deberá ser consultada al momento de la entrega debido a variaciones en la carga del laboratorio.',
        'trae_muestra' => '<strong>Entrega de Muestras:</strong> El cliente traerá las muestras a las instalaciones del Laboratorio CYCSA Km 83.5 Carretera León-Managua.',
        'minimo_muestreo' => '<strong>Programación de Muestreo:</strong> Se requiere un cargo mínimo de C$ 4,400.00 más movilización para programar muestreos. Programación con un mínimo de 2 días hábiles de anticipación.'
    ];

    // Si no hay configuración de notas guardada, activar las 3 notas estándar por defecto
    if (empty($configNotas)) {
        $configNotas = [
            'digital_pdf' => 1,
            'no_movilizacion' => 1,
            'entrega_laboratorio' => 1
        ];
    }

    $htmlNotas = '';
    foreach ($configNotas as $clave => $seleccionada) {
        if ($seleccionada && isset($notasDisponibles[$clave])) {
            $htmlNotas .= "<li style=\"margin-bottom: 3px;\">{$notasDisponibles[$clave]}</li>";
        }
    }

    $notasSeccion = '';
    if (!empty($htmlNotas)) {
        $notasSeccion = "
        <div class=\"no-split\" style=\"margin-top: 6px; margin-bottom: 6px;\">
            <div style=\"background-color: #1F4E79; color: #ffffff; padding: 2.5px 6px; font-weight: bold; font-size: 8px; text-transform: uppercase; margin-bottom: 4px; border-radius: 2px; letter-spacing: 0.5px;\">
                Notas y condiciones de la cotización
            </div>
            <ul style=\"margin: 0; padding-left: 14px; font-size: 8px; color: #475569; line-height: 1.35;\">
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
        <div class=\"no-split\" style=\"margin-top: 8px; margin-bottom: 8px;\">
            <strong style=\"color: #103487; font-size: 8.5px; text-transform: uppercase; display: block; border-bottom: 1px solid #e2e8f0; padding-bottom: 2px; margin-bottom: 3px;\">Contactos de Seguimiento</strong>
            <div style=\"font-size: 8px; color: #475569; line-height: 1.3;\">
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
    $rawVersion = (int)($cotizacion['version'] ?? 1);
    $version = (string)($rawVersion > 0 ? $rawVersion : 1);
    $condicionPago = htmlspecialchars($cotizacion['condicion_pago'] ?? '', ENT_QUOTES, 'UTF-8');
    $tiempoEntrega = htmlspecialchars($cotizacion['tiempo_entrega'] ?? '', ENT_QUOTES, 'UTF-8');
    $vigenciaOferta = htmlspecialchars($cotizacion['vigencia_oferta'] ?? '', ENT_QUOTES, 'UTF-8');
    $creadorNombre = htmlspecialchars($cotizacion['creador_nombre'] ?? 'Asesor Comercial', ENT_QUOTES, 'UTF-8');
    $clienteEmail = htmlspecialchars($cotizacion['cliente_email'] ?? ($cotizacion['email'] ?? 'admon@cycsanic.com'), ENT_QUOTES, 'UTF-8');
    $clienteTel = htmlspecialchars($cotizacion['cliente_tel'] ?? ($cotizacion['telefono'] ?? '(505) 8209-6275'), ENT_QUOTES, 'UTF-8');

    // Procesar Anexo Técnico (Garantiza formatos oficiales CYCSA-RG-FM-31)
    $anexoTecnicoSeccion = '';
    $incluirAnexo = !empty($cotizacion['incluir_anexo_tecnico']) && !empty(trim($cotizacion['anexo_tecnico'] ?? ''));
    if ($incluirAnexo) {
        $anexoContenido = trim($cotizacion['anexo_tecnico'] ?? '');
        if (!empty($anexoContenido)) {
            $anexoTecnicoSeccion = "
            <div style=\"page-break-before: always;\">
                <div class=\"header-box\">
                    <table style=\"width: 100%;\">
                        <tr>
                            <td style=\"width: 48%; vertical-align: bottom;\">
                                {$logoHtml}
                                <div style=\"font-size: 8.5px; font-weight: bold; color: #1e293b; margin-bottom: 2px;\">Cód. Doc CYCSA-RG-FM-31 V2R1</div>
                                <span style=\"font-size: 7.5px; color: #64748b; font-weight: bold; text-transform: uppercase;\">Laboratorio de Ensayos y Control de Calidad</span>
                            </td>
                            <td style=\"width: 52%; text-align: right; vertical-align: bottom;\">
                                <span style=\"font-size: 13px; font-weight: bold; color: #103487; text-transform: uppercase;\">Anexo Técnico Oficial</span><br>
                                <span style=\"font-size: 14px; font-weight: bold; color: #1e293b;\">{$codigo}</span>
                                <span style=\"font-size: 8.5px; color: #64748b; margin-left: 8px;\">Proyecto: {$proyectoNombre} &bull; Versión: {$version}</span>
                            </td>
                        </tr>
                    </table>
                </div>

                <div style=\"font-size: 8.5px; line-height: 1.4; color: #1e293b;\">
                    {$anexoContenido}
                </div>
            </div>";
        }
    }

    // Procesar Documento Adjunto (DOCX, Imagen, PDF, TXT, etc.)
    $infoAdjunto = procesarArchivoAdjuntoCotizacion($cotizacion, $logoHtml);
    $anexoAdjuntoSeccion = $infoAdjunto['html'];
    $rutaPdfAdjuntoFusionar = $infoAdjunto['ruta_pdf'];

    $html = "
    <!DOCTYPE html>
    <html lang=\"es\">
    <head>
        <meta charset=\"UTF-8\">
        <style>
            @page {
                size: A4 portrait;
                margin-top: {$pageMarginTop};
                margin-bottom: {$pageMarginBottom};
                margin-left: {$pageMarginLeft};
                margin-right: {$pageMarginRight};
            }
            .hoja-fondo {
                position: fixed;
                top: -{$pageMarginTop};
                left: -{$pageMarginLeft};
                width: 21.0cm;
                height: 29.7cm;
                z-index: -1000;
            }
            body {
                font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
                color: #1e293b;
                line-height: 1.35;
                font-size: 9.5px;
                margin: 0;
                padding: 0;
            }
            .header-box {
                border-bottom: 2px solid #103487;
                padding-bottom: 4px;
                margin-bottom: 10px;
            }
            table {
                border-collapse: collapse;
                width: 100%;
            }
            .tabla-items {
                width: 100%;
                margin-bottom: 10px;
            }
            .tabla-items thead {
                display: table-header-group;
            }
            .tabla-items tr {
                page-break-inside: avoid;
            }
            .no-split {
                page-break-inside: avoid;
            }
            h3, h4 {
                page-break-after: avoid;
                page-break-inside: avoid;
            }
            ul, ol {
                page-break-inside: auto;
            }
            li {
                page-break-inside: avoid;
            }
            .totals-table td {
                padding: 3px 6px;
                font-size: 9px;
            }
        </style>
    </head>
    <body>
        {$fondoHtml}

        <!-- Header Box Page 1 -->
        <div class=\"header-box\">
            <table style=\"width: 100%;\">
                <tr>
                    <td style=\"width: 48%; vertical-align: bottom;\">
                        {$logoHtml}
                        <div style=\"font-size: 8.5px; font-weight: bold; color: #1e293b; margin-bottom: 2px;\">Cód. Doc CYCSA-RG-FM-31 V2R1</div>
                        <span style=\"font-size: 7.5px; color: #64748b; font-weight: bold; text-transform: uppercase;\">Laboratorio de Ensayos y Control de Calidad</span>
                    </td>
                    <td style=\"width: 52%; text-align: right; vertical-align: bottom;\">
                        <span style=\"font-size: 13px; font-weight: bold; color: #103487; text-transform: uppercase;\">Cotización de Servicio</span><br>
                        <span style=\"font-size: 14px; font-weight: bold; color: #1e293b;\">{$codigo}</span>
                        <span style=\"font-size: 8.5px; color: #64748b; margin-left: 8px;\">Versión: {$version}</span>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Metadatos Oficiales CYCSA (CYCSA-RG-FM-31 V2R1) -->
        <table style=\"width: 100%; border-collapse: collapse; margin-bottom: 6px; font-size: 8px; border: 1px solid #cbd5e1;\">
            <tr>
                <td style=\"width: 15%; font-weight: bold; color: #1e293b; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; background: #f8fafc;\">ELABORADO POR:</td>
                <td style=\"width: 35%; color: #103487; font-weight: bold; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #cbd5e1;\">{$creadorNombre}</td>
                <td style=\"width: 15%; font-weight: bold; color: #1e293b; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; background: #f8fafc;\">DOC. NÚMERO:</td>
                <td style=\"width: 35%; color: #1e293b; font-weight: bold; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0;\">{$codigo} <span style=\"font-size: 7.5px; color: #64748b; font-weight: normal;\">(v{$version})</span></td>
            </tr>
            <tr>
                <td style=\"font-weight: bold; color: #1e293b; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; background: #f8fafc;\">CLIENTE:</td>
                <td style=\"color: #1e293b; font-weight: bold; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #cbd5e1;\">{$clienteNombre}</td>
                <td style=\"font-weight: bold; color: #1e293b; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; background: #f8fafc;\">FECHA:</td>
                <td style=\"color: #1e293b; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0;\">{$fecha}</td>
            </tr>
            <tr>
                <td style=\"font-weight: bold; color: #1e293b; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; background: #f8fafc;\">ATENCIÓN A:</td>
                <td style=\"color: #1e293b; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #cbd5e1;\">{$atencionA}</td>
                <td style=\"font-weight: bold; color: #1e293b; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; background: #f8fafc;\">CÉDULA/RUC:</td>
                <td style=\"color: #1e293b; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0;\">{$clienteRuc}</td>
            </tr>
            <tr>
                <td style=\"font-weight: bold; color: #1e293b; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; background: #f8fafc;\">PROYECTO:</td>
                <td style=\"color: #1e293b; font-weight: bold; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #cbd5e1;\">{$proyectoNombre}</td>
                <td style=\"font-weight: bold; color: #1e293b; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #e2e8f0; background: #f8fafc;\">FORMA DE PAGO:</td>
                <td style=\"color: #1e293b; padding: 2.5px 5px; border-bottom: 1px solid #e2e8f0;\">{$condicionPago}</td>
            </tr>
            <tr>
                <td style=\"font-weight: bold; color: #1e293b; padding: 2.5px 5px; border-right: 1px solid #e2e8f0; background: #f8fafc;\">CORREO ELECTRÓNICO:</td>
                <td style=\"color: #1e293b; padding: 2.5px 5px; border-right: 1px solid #cbd5e1;\">{$clienteEmail}</td>
                <td style=\"font-weight: bold; color: #1e293b; padding: 2.5px 5px; border-right: 1px solid #e2e8f0; background: #f8fafc;\">TELÉFONO:</td>
                <td style=\"color: #1e293b; padding: 2.5px 5px;\">{$clienteTel}</td>
            </tr>
        </table>

        <!-- Banner Detalle de Servicios Cotizados -->
        <div style=\"background-color: #1F4E79; color: #ffffff; padding: 3px 6px; font-weight: bold; font-size: 8px; text-transform: uppercase; margin-bottom: 4px; border-radius: 2px; letter-spacing: 0.5px;\">
            Detalle de servicios cotizados
        </div>

        <!-- Table of Items (8 Columns) -->
        <table class=\"tabla-items\">
            <thead>
                <tr style=\"background-color: #f1f5f9;\">
                    <th style=\"border: 1px solid #cbd5e1; padding: 5px 3px; text-align: center; font-size: 8px; color: #475569; width: 4%;\">Línea</th>
                    <th style=\"border: 1px solid #cbd5e1; padding: 5px 5px; text-align: left; font-size: 8px; color: #475569; width: 26%;\">Descripción (Nombre comercial)</th>
                    <th style=\"border: 1px solid #cbd5e1; padding: 5px 5px; text-align: left; font-size: 8px; color: #475569; width: 22%;\">Condiciones de muestra</th>
                    <th style=\"border: 1px solid #cbd5e1; padding: 5px 5px; text-align: left; font-size: 8px; color: #475569; width: 14%;\">Procedimiento</th>
                    <th style=\"border: 1px solid #cbd5e1; padding: 5px 3px; text-align: center; font-size: 8px; color: #475569; width: 8%;\">Unidad</th>
                    <th style=\"border: 1px solid #cbd5e1; padding: 5px 3px; text-align: center; font-size: 8px; color: #475569; width: 6%;\">Cant.</th>
                    <th style=\"border: 1px solid #cbd5e1; padding: 5px 5px; text-align: right; font-size: 8px; color: #475569; width: 10%;\">Costo</th>
                    <th style=\"border: 1px solid #cbd5e1; padding: 5px 5px; text-align: right; font-size: 8px; color: #475569; width: 10%;\">Monto</th>
                </tr>
            </thead>
            <tbody>
                {$rowsHtml}
            </tbody>
        </table>

        <!-- Totales y Pago -->
        <div class=\"no-split\">
            <table style=\"width: 100%; margin-bottom: 8px;\">
                <tr>
                    <td style=\"width: 58%; vertical-align: top; padding-right: 15px;\">
                        <div style=\"background-color: #1F4E79; color: #ffffff; padding: 2.5px 6px; font-weight: bold; font-size: 8px; text-transform: uppercase; margin-bottom: 4px; border-radius: 2px; letter-spacing: 0.5px;\">
                            Datos de pago
                        </div>
                        <div style=\"font-size: 8px; color: #475569; line-height: 1.35;\">
                            <strong>Pago a nombre de CYC.S.A y/o depositar en las siguientes cuentas:</strong><br>
                            BANPRO: C$ 10010207085164 / $ 10010210874512<br>
                            BAC: C$ 357-02445-4 / $ 363259490<br>
                            LAFISE: C$ 550-2000-11<br>
                            RUC: J0310000073465. Cotización válida por 30 días.
                        </div>
                        <div style=\"margin-top: 6px;\">
                            <table style=\"border-collapse: collapse;\">
                                <tr>
                                    <td style=\"vertical-align: middle; padding-right: 6px;\">
                                        <span style=\"font-size: 7.5px; font-weight: bold; color: #475569; text-transform: uppercase; display: block; margin-bottom: 1px;\">Términos y Condiciones</span>
                                        <span style=\"font-size: 7px; color: #64748b;\">Escanea el código QR para ver las políticas oficiales de CYCSA.</span>
                                    </td>
                                    <td style=\"vertical-align: middle;\">
                                        <img src=\"{$qrBase64}\" style=\"height: 50px; width: 50px; border: 1px solid #cbd5e1; padding: 2px; background: white; border-radius: 4px;\">
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </td>
                    <td style=\"width: 42%; vertical-align: top;\">
                        <table class=\"totals-table\" style=\"width: 100%; border-collapse: collapse; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px;\">
                            <tr>
                                <td style=\"text-align: right; color: #64748b; padding: 3px 6px;\">Total Bruto:</td>
                                <td style=\"text-align: right; font-weight: bold; width: 90px; padding: 3px 6px;\">{$simboloMoneda} {$subtotal}</td>
                            </tr>
                            {$descuentoHtml}
                            <tr>
                                <td style=\"text-align: right; color: #64748b; padding: 3px 6px;\">{$ivaLabel}</td>
                                <td style=\"text-align: right; font-weight: bold; padding: 3px 6px;\">{$simboloMoneda} {$impuesto}</td>
                            </tr>
                            <tr style=\"background: #e6eefc; border-top: 1px solid #cbd5e1;\">
                                <td style=\"text-align: right; color: #103487; font-weight: bold; padding: 4px 6px;\">Total:</td>
                                <td style=\"text-align: right; color: #103487; font-weight: bold; padding: 4px 6px;\">{$simboloMoneda} {$total}</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Commercial Conditions -->
        <div class=\"no-split\" style=\"background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 4px; padding: 6px 10px; margin-bottom: 8px;\">
            <strong style=\"margin: 0 0 4px 0; color: #103487; font-size: 8.5px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; padding-bottom: 2px; display: block;\">Condiciones Comerciales</strong>
            <table style=\"width: 100%; font-size: 8px; border-collapse: collapse;\">
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
        <div class=\"no-split\" style=\"margin-top: 15px;\">
            <table style=\"width: 100%; border-collapse: collapse;\">
                <tr>
                    <td style=\"width: 45%; text-align: center; vertical-align: bottom;\">
                        <div style=\"border-top: 1px solid #cbd5e1; width: 85%; margin: 0 auto; padding-top: 3px; font-size: 8px; color: #475569;\">
                            <strong>Preparado por:</strong><br>
                            {$creadorNombre}<br>
                            CYCSA Laboratorio
                        </div>
                    </td>
                    <td style=\"width: 10%;\"></td>
                    <td style=\"width: 45%; text-align: center; vertical-align: bottom;\">
                        <div style=\"border-top: 1px solid #cbd5e1; width: 85%; margin: 0 auto; padding-top: 3px; font-size: 8px; color: #475569;\">
                            <strong>Aceptado por el Cliente:</strong><br>
                            Firma / Sello Autorizado<br>
                            Fecha: ____/____/______
                        </div>
                    </td>
                </tr>
            </table>
        </div>

        <!-- Technical Annex (if included) -->
        {$anexoTecnicoSeccion}

        <!-- Attached Document Annex (if uploaded) -->
        {$anexoAdjuntoSeccion}
    </body>
    </html>";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'portrait');
    $dompdf->render();

    $tienePdfAdjuntoParaFusionar = !empty($rutaPdfAdjuntoFusionar) && file_exists($rutaPdfAdjuntoFusionar);
    if (!$tienePdfAdjuntoParaFusionar) {
        $bottomMargin = !empty($bgBase64) ? 104 : 26;
        $rightMargin = !empty($bgBase64) ? 51 : 40;
        agregarNumeracionPaginasDompdf($dompdf, 'Página {PAGE_NUM} de {PAGE_COUNT}', $bottomMargin, $rightMargin, 9.0, [0.06, 0.20, 0.53], true);
    }

    $pdfSalida = $dompdf->output();

    if ($tienePdfAdjuntoParaFusionar) {
        $pdfSalida = fusionarPdfConAdjunto($pdfSalida, $rutaPdfAdjuntoFusionar);
    }

    return $pdfSalida;
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
function generarReporteEnsayoPDF(array $cotizacion, array $detalle, array $columnas, array $filas, string $codigoReporte = '', $version = null, ?string $observacionesSupervisor = null, int $ocultarCumplimiento = 0): string {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $options->set('tempDir', dirname(__DIR__, 2) . '/storage/cache');
    $options->set('fontCache', dirname(__DIR__, 2) . '/storage/cache');
    $dompdf = new Dompdf($options);

    $logoPath = dirname(__DIR__, 2) . '/publico/img/logo_cycsa.jpg';
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoBase64 = 'data:image/jpeg;base64,' . base64_encode(file_get_contents($logoPath));
    }

    $bgPath = dirname(__DIR__, 2) . '/publico/img/hoja_horizontal.jpg';
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

    // Filtrar columna de validación ("Cumple / No Cumple / Estado") si el cliente lo solicita
    if ($ocultarCumplimiento == 1) {
        $colsFiltradas = [];
        foreach ($columnas as $c) {
            $cLow = mb_strtolower(trim($c));
            if (strpos($cLow, 'estado') !== false || strpos($cLow, 'cumple') !== false || strpos($cLow, 'alerta') !== false) {
                continue;
            }
            $colsFiltradas[] = $c;
        }
        $columnas = $colsFiltradas;
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

    // Header table columns rendering
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

    $observacionesSupervisorHtml = '';
    if (!empty($observacionesSupervisor)) {
        $observacionesSupervisorHtml = "
        <div style=\"margin-top: 15px; padding: 8px 12px; background-color: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 8.5px;\">
            <strong style=\"color: #103487; text-transform: uppercase;\">Observaciones y Comentarios del Supervisor:</strong><br>
            <span style=\"color: #334155;\">" . nl2br(htmlspecialchars($observacionesSupervisor, ENT_QUOTES, 'UTF-8')) . "</span>
        </div>";
    }

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
        <table style=\"width: 100%; border-collapse: collapse; margin-bottom: 15px;\">
            <thead>
                <tr style=\"background-color: #f1f5f9;\">
                    {$theadHtml}
                </tr>
            </thead>
            <tbody>
                {$tbodyHtml}
            </tbody>
        </table>

        {$observacionesSupervisorHtml}

        {$graficoHtml}

        <!-- Footer terms -->
        <div style=\"font-size: 8px; color: #64748b; line-height: 1.3; margin-top: 15px; border-top: 1px solid #cbd5e1; padding-top: 6px;\">
            Consultoría y Construcción SA. CYCSA es responsable únicamente de la exactitud de los resultados realizados en las muestras recibidas y tomadas en campo. No se debe de reproducir este informe de ensayo sin la aprobación formal de Consultoría y Construcción SA. CYCSA.
        </div>

        <!-- Signatures -->
        <table style=\"width: 100%; margin-top: 35px; border-collapse: collapse;\">
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
                        <strong>Técnico / Supervisor de Calidad</strong><br>
                        Realizado / Revisado por Firma
                    </div>
                </td>
            </tr>
        </table>
    </body>
    </html>";

    $dompdf->loadHtml($html);
    $dompdf->setPaper('A4', 'landscape');
    $dompdf->render();

    $bottomMargin = !empty($bgBase64) ? 52 : 24;
    $rightMargin = !empty($bgBase64) ? 60 : 40;
    agregarNumeracionPaginasDompdf($dompdf, 'Página {PAGE_NUM} de {PAGE_COUNT}', $bottomMargin, $rightMargin, 9.0, [0.06, 0.20, 0.53], true);

    return $dompdf->output();
}

/**
 * Genera el contenido binario de la Matriz Técnica Oficial de Resultados en formato PDF (Letter Landscape) usando Dompdf,
 * incorporando el membrete oficial horizontal CYCSA, tabla de metadatos, banner de procedimiento y norma ASTM,
 * tabla de resultados técnicos calculados, notas de acreditación ISO/IEC 17025, firmas de responsabilidad técnica
 * y numeración protegida oficial.
 *
/**
 * Resuelve y unifica los metadatos oficiales del informe de ensayo (ISO/IEC 17025)
 * capturando datos dinámicos de los procesos anteriores (Cotización, OS, Muestreo, Recepción, Hoja de Campo)
 * o de los valores previamente guardados.
 */
function obtenerEsquemaPlantillaEnsayo(?string $archivoMd, ?int $formatoId = null): array {
    $ruta = dirname(__DIR__, 2) . '/database/ensayos/formatos_schema.json';
    $base = is_file($ruta) ? (json_decode(file_get_contents($ruta), true) ?: []) : [];
    $archivo = basename($archivoMd ?? '');
    $esquemaBase = $base[$archivo] ?? [];
    if (!$esquemaBase && $archivo !== '') {
        $normalizar = static fn(string $texto): string => strtolower(strtr($texto, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ñ' => 'n'
        ]));
        foreach ($base as $nombre => $esquema) {
            if ($normalizar($nombre) === $normalizar($archivo)) {
                $esquemaBase = $esquema;
                break;
            }
        }
    }
    if ($formatoId === null && $archivo === '') return $esquemaBase;
    try {
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        if ($formatoId !== null && $formatoId > 0) {
            $stmt = $db->prepare('SELECT configuracion_json FROM formatos_ensayos WHERE id = :id LIMIT 1');
            $stmt->execute(['id' => $formatoId]);
        } else {
            $stmt = $db->prepare('SELECT configuracion_json FROM formatos_ensayos WHERE archivo_markdown = :archivo LIMIT 1');
            $stmt->execute(['archivo' => $archivo]);
        }
        $json = $stmt->fetchColumn();
        $config = is_string($json) ? json_decode($json, true) : null;
        if (is_array($config) && $config) return array_replace($esquemaBase, $config);
    } catch (\Throwable $e) {
        // Instalaciones aún sin migrar siguen utilizando el esquema de fábrica.
    }
    return $esquemaBase;
}

function resolverMetadatosEnsayo(array $detalle, array $schemaInfo = [], array $metadatosGuardados = []): array {
    $clienteNom = !empty($metadatosGuardados['cliente_nombre']) ? $metadatosGuardados['cliente_nombre'] : (!empty($detalle['cliente_nombre']) ? $detalle['cliente_nombre'] : (!empty($detalle['nombre_razon_social']) ? $detalle['nombre_razon_social'] : ($detalle['atencion_a'] ?? 'Cliente Confidencial (ISO 17025)')));
    $clienteDir = !empty($metadatosGuardados['cliente_direccion']) ? $metadatosGuardados['cliente_direccion'] : (!empty($detalle['cliente_direccion']) ? $detalle['cliente_direccion'] : (!empty($detalle['direccion']) ? $detalle['direccion'] : ($detalle['direccion_proyecto'] ?? '')));
    $proyectoNom = !empty($metadatosGuardados['proyecto']) ? $metadatosGuardados['proyecto'] : (!empty($detalle['nombre_proyecto']) ? $detalle['nombre_proyecto'] : (!empty($detalle['proyecto']) ? $detalle['proyecto'] : 'Proyecto no especificado'));
    $fechaIngreso = !empty($metadatosGuardados['fecha_ingreso']) ? $metadatosGuardados['fecha_ingreso'] : (!empty($detalle['fecha_ingreso']) ? substr($detalle['fecha_ingreso'], 0, 10) : (!empty($detalle['fecha_recepcion']) ? substr($detalle['fecha_recepcion'], 0, 10) : (!empty($detalle['fecha_hora_llegada_laboratorio']) ? substr($detalle['fecha_hora_llegada_laboratorio'], 0, 10) : (!empty($detalle['fecha_registro_campo']) ? $detalle['fecha_registro_campo'] : substr($detalle['os_created_at'] ?? date('Y-m-d'), 0, 10)))));
    $fechaMuestreo = !empty($metadatosGuardados['fecha_muestreo']) ? $metadatosGuardados['fecha_muestreo'] : (!empty($detalle['fecha_muestreo']) ? substr($detalle['fecha_muestreo'], 0, 10) : (!empty($detalle['pm_fecha_ida']) ? substr($detalle['pm_fecha_ida'], 0, 10) : (!empty($detalle['fecha_hora_toma_muestra']) ? substr($detalle['fecha_hora_toma_muestra'], 0, 10) : date('Y-m-d'))));
    $fechaEjecucion = !empty($metadatosGuardados['fecha_ejecucion']) ? $metadatosGuardados['fecha_ejecucion'] : (!empty($detalle['fecha_ejecucion']) ? substr($detalle['fecha_ejecucion'], 0, 10) : date('Y-m-d'));
    $fechaEmision = !empty($metadatosGuardados['fecha_emision']) ? $metadatosGuardados['fecha_emision'] : (!empty($detalle['fecha_emision']) ? substr($detalle['fecha_emision'], 0, 10) : (!empty($detalle['os_fecha_emision']) ? substr($detalle['os_fecha_emision'], 0, 10) : date('Y-m-d')));
    $tipoMuestra = !empty($metadatosGuardados['tipo_muestra']) ? $metadatosGuardados['tipo_muestra'] : (!empty($detalle['tipo_muestra']) ? $detalle['tipo_muestra'] : (!empty($detalle['prod_tipo_muestra']) ? $detalle['prod_tipo_muestra'] : (!empty($schemaInfo['tipo_muestra']) ? $schemaInfo['tipo_muestra'] : (!empty($detalle['condiciones_muestra']) ? $detalle['condiciones_muestra'] : (!empty($detalle['naturaleza_muestra']) ? $detalle['naturaleza_muestra'] : 'Especímenes / Muestras')))));
    $muestraTomadaPor = !empty($metadatosGuardados['muestra_tomada_por']) ? $metadatosGuardados['muestra_tomada_por'] : (!empty($detalle['muestra_tomada_por']) ? $detalle['muestra_tomada_por'] : (!empty($detalle['requiere_muestreo']) ? 'Laboratorio - Consultoría y Construcción S.A. CYCSA.' : 'El Cliente y entrega en Laboratorio'));
    $procedimientoMuestreo = !empty($metadatosGuardados['procedimiento_muestreo']) ? $metadatosGuardados['procedimiento_muestreo'] : (!empty($detalle['procedimiento_muestreo']) ? $detalle['procedimiento_muestreo'] : (!empty($detalle['procedimiento']) ? $detalle['procedimiento'] : (!empty($detalle['prod_procedimiento']) ? $detalle['prod_procedimiento'] : (!empty($detalle['formato_procedimiento']) ? $detalle['formato_procedimiento'] : 'Aleatorio Procedimiento A (TD)'))));
    $ubicacion = !empty($metadatosGuardados['ubicacion']) ? $metadatosGuardados['ubicacion'] : (!empty($detalle['ubicacion']) ? $detalle['ubicacion'] : (!empty($detalle['procedencia_punto_muestreo']) ? $detalle['procedencia_punto_muestreo'] : (!empty($detalle['direccion_proyecto']) ? $detalle['direccion_proyecto'] : (!empty($detalle['pm_lugar_muestreo']) ? $detalle['pm_lugar_muestreo'] : 'Sitio de Proyecto'))));
    $ensayoRealizado = !empty($schemaInfo['ensayo_titulo']) ? $schemaInfo['ensayo_titulo'] : (!empty($metadatosGuardados['ensayo_realizado']) ? $metadatosGuardados['ensayo_realizado'] : (!empty($detalle['ensayo_realizado']) ? $detalle['ensayo_realizado'] : ($detalle['descripcion_ensayo'] ?? 'Ensayo de Laboratorio')));
    $metodoMuestreo = !empty($schemaInfo['metodo_muestreo']) ? $schemaInfo['metodo_muestreo'] : (!empty($metadatosGuardados['metodo_muestreo']) ? $metadatosGuardados['metodo_muestreo'] : (!empty($detalle['metodo_muestreo']) ? $detalle['metodo_muestreo'] : (!empty($detalle['norma_astm']) ? $detalle['norma_astm'] : (!empty($detalle['prod_norma_astm']) ? $detalle['prod_norma_astm'] : 'DE-ASTM D6938-23'))));
    $codigoFormato = !empty($schemaInfo['codigo_formato']) ? $schemaInfo['codigo_formato'] : (!empty($detalle['codigo_formato']) ? $detalle['codigo_formato'] : (!empty($detalle['codigo_documento']) ? $detalle['codigo_documento'] : 'CYCSA-RT-FM-22'));
    $logoAcreditacion = !empty($metadatosGuardados['logo_acreditacion']) ? $metadatosGuardados['logo_acreditacion'] : (!empty($detalle['logo_acreditacion']) ? $detalle['logo_acreditacion'] : '');

    return [
        'cliente_nombre' => $clienteNom,
        'cliente_direccion' => $clienteDir,
        'proyecto' => $proyectoNom,
        'fecha_ingreso' => $fechaIngreso,
        'fecha_muestreo' => $fechaMuestreo,
        'fecha_ejecucion' => $fechaEjecucion,
        'fecha_emision' => $fechaEmision,
        'tipo_muestra' => $tipoMuestra,
        'muestra_tomada_por' => $muestraTomadaPor,
        'procedimiento_muestreo' => $procedimientoMuestreo,
        'ubicacion' => $ubicacion,
        'ensayo_realizado' => $ensayoRealizado,
        'metodo_muestreo' => $metodoMuestreo,
        'codigo_formato' => $codigoFormato,
        'logo_acreditacion' => $logoAcreditacion,
    ];
}

/**
 * Genera el código institucional oficial del informe de ensayo conforme a la regla CYCSA:
 * - Consecutivos: CYCSA-INF-[TIPO]-[DESDE]-[HASTA]-[AÑO] (ej. CYCSA-INF-MS-0001-0005-26)
 * - No consecutivos: CYCSA-INF-[TIPO]-[NUM1], [NUM2], [NUM3]-[AÑO] (ej. CYCSA-INF-MS-0001, 0005, 0007, 0009-26)
 * - Muestra única: CYCSA-INF-[TIPO]-[NUM]-[AÑO] (ej. CYCSA-INF-MS-0001-26)
 * Detecta prefijos MS (Muestra Suelo / Simple) y MC (Muestra Concreto / Campo) y año de dos dígitos.
 */
function generarCodigoInformeEnsayo($datosMuestrasOFilas = [], ?string $fechaRef = null, ?string $tipoMuestra = null): string {
    $codigos = [];

    if (is_string($datosMuestrasOFilas)) {
        $dec = json_decode($datosMuestrasOFilas, true);
        if (is_array($dec)) {
            $datosMuestrasOFilas = $dec['filas'] ?? $dec;
        } else {
            $datosMuestrasOFilas = [$datosMuestrasOFilas];
        }
    }

    if (is_array($datosMuestrasOFilas)) {
        if (isset($datosMuestrasOFilas['filas']) && is_array($datosMuestrasOFilas['filas'])) {
            $datosMuestrasOFilas = $datosMuestrasOFilas['filas'];
        }
        foreach ($datosMuestrasOFilas as $item) {
            if (is_string($item)) {
                $codigos[] = trim($item);
            } elseif (is_array($item)) {
                $posibles = [
                    $item['codigo_lab'] ?? null,
                    $item['codigo_muestra'] ?? null,
                    $item['Código laboratorio'] ?? null,
                    $item['Codigo laboratorio'] ?? null,
                    $item['codigo_laboratorio'] ?? null,
                    $item['Nombre muestra'] ?? null,
                    $item['codigo_campo'] ?? null,
                ];
                $encontrado = false;
                foreach ($posibles as $val) {
                    if (!empty($val) && is_string($val)) {
                        if (preg_match('/(?:MS|MC)[-_]\d+/i', $val)) {
                            $codigos[] = trim($val);
                            $encontrado = true;
                            break;
                        }
                    }
                }
                if (!$encontrado) {
                    $codigoDirecto = $item['Código laboratorio'] ?? ($item['codigo_lab'] ?? ($item['codigo_muestra'] ?? null));
                    if (!empty($codigoDirecto) && is_string($codigoDirecto)) {
                        $codigos[] = trim($codigoDirecto);
                    }
                }
            }
        }
    }

    $anioRef = '26';
    if (!empty($fechaRef) && strtotime($fechaRef) !== false) {
        $anioRef = date('y', strtotime($fechaRef));
    } else {
        $anioRef = date('y');
    }

    $prefijoDefault = 'MS';
    if (!empty($tipoMuestra)) {
        $tipoLower = strtolower($tipoMuestra);
        if (strpos($tipoLower, 'concreto') !== false || strpos($tipoLower, 'cilindro') !== false || strpos($tipoLower, 'vigueta') !== false || strpos($tipoLower, 'nucleo') !== false || strpos($tipoLower, 'mortero') !== false) {
            $prefijoDefault = 'MC';
        }
    }

    $grupos = [];
    foreach ($codigos as $cod) {
        if (preg_match('/([A-Za-z]{2,3})[-_](\d+)(?:[-_](\d{2,4}))?/i', $cod, $matches)) {
            $pref = strtoupper($matches[1]);
            $num = (int)$matches[2];
            $anio = !empty($matches[3]) ? substr($matches[3], -2) : $anioRef;
            $grupos[$pref][$anio][] = $num;
        } elseif (preg_match('/(\d+)/', $cod, $matches)) {
            $num = (int)$matches[1];
            $grupos[$prefijoDefault][$anioRef][] = $num;
        }
    }

    if (empty($grupos)) {
        return "CYCSA-INF-{$prefijoDefault}-0001-{$anioRef}";
    }

    $bloquesInforme = [];
    foreach ($grupos as $pref => $anios) {
        foreach ($anios as $anio => $numeros) {
            $numeros = array_values(array_unique($numeros));
            sort($numeros, SORT_NUMERIC);
            $total = count($numeros);

            if ($total === 0) {
                continue;
            }

            if ($total === 1) {
                $numStr = sprintf('%04d', $numeros[0]);
                $bloquesInforme[] = "CYCSA-INF-{$pref}-{$numStr}-{$anio}";
                continue;
            }

            $esConsecutivo = true;
            for ($i = 0; $i < $total - 1; $i++) {
                if ($numeros[$i + 1] !== $numeros[$i] + 1) {
                    $esConsecutivo = false;
                    break;
                }
            }

            if ($esConsecutivo) {
                $desdeStr = sprintf('%04d', $numeros[0]);
                $hastaStr = sprintf('%04d', $numeros[$total - 1]);
                $bloquesInforme[] = "CYCSA-INF-{$pref}-{$desdeStr}-{$hastaStr}-{$anio}";
            } else {
                $numsStr = array_map(static fn($n) => sprintf('%04d', $n), $numeros);
                $lista = implode(', ', $numsStr);
                $bloquesInforme[] = "CYCSA-INF-{$pref}-{$lista}-{$anio}";
            }
        }
    }

    return !empty($bloquesInforme) ? implode(' / ', $bloquesInforme) : "CYCSA-INF-{$prefijoDefault}-0001-{$anioRef}";
}

/**
 * Genera el contenido binario de la Matriz Técnica Oficial de Resultados en formato PDF (Letter Landscape) usando Dompdf,
 * incorporando el membrete oficial horizontal CYCSA, tabla de metadatos, banner de procedimiento y norma ASTM,
 * tabla de resultados técnicos calculados, notas de acreditación ISO/IEC 17025, firmas de responsabilidad técnica
 * y numeración protegida oficial.
 *
 * @param array $detalle Registro de cotizacion_detalles con datos de la O/S y cliente
 * @param array $muestrasSeteadas Muestras por defecto si la matriz aún no tiene resultados (opcional)
 * @param array $columnas Nombres de columnas de ensayo (opcional)
 * @param string $formatosSchemaJson Contenido JSON de esquemas normativos (opcional)
 * @return string Bytes binarios del PDF generado
 */
function generarMatrizTecnicaPDF(array $detalle, array $muestrasSeteadas = [], array $columnas = [], string $formatosSchemaJson = ''): string {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $options->set('tempDir', dirname(__DIR__, 2) . '/storage/cache');
    $options->set('fontCache', dirname(__DIR__, 2) . '/storage/cache');
    $dompdf = new Dompdf($options);

    $bgPath = dirname(__DIR__, 2) . '/publico/img/hoja_membretada_horizontal.jpg';
    if (!file_exists($bgPath)) {
        $bgPath = dirname(__DIR__, 2) . '/publico/img/hoja_horizontal.jpg';
    }
    $bgBase64 = file_exists($bgPath) ? base64_encode(file_get_contents($bgPath)) : '';

    $archivoMd = $detalle['archivo_markdown'] ?? '';
    $schemaInfo = obtenerEsquemaPlantillaEnsayo($archivoMd, isset($detalle['formato_id']) ? (int)$detalle['formato_id'] : null);

    $codigoFormatoOficial = !empty($schemaInfo['codigo_formato']) ? $schemaInfo['codigo_formato'] : (!empty($detalle['codigo_documento']) ? $detalle['codigo_documento'] : 'CYCSA-RT-FM-22');
    $ensayoTituloOficial = !empty($schemaInfo['ensayo_titulo']) ? $schemaInfo['ensayo_titulo'] : ($detalle['descripcion_ensayo'] ?? 'Ensayo de Laboratorio');
    $metodoMuestreoOficial = !empty($schemaInfo['metodo_muestreo']) ? $schemaInfo['metodo_muestreo'] : (!empty($detalle['norma_astm']) ? $detalle['norma_astm'] : 'Norma ASTM / AASHTO Oficial');
    $tipoMuestraOficial = !empty($schemaInfo['tipo_muestra']) ? $schemaInfo['tipo_muestra'] : 'Especímenes / Muestras';
    $colMethods = $schemaInfo['column_methods'] ?? [];

    $disclaimerOficial = !empty($schemaInfo['disclaimer']) ? $schemaInfo['disclaimer'] : 'Consultoría y Construcción SA.CYCSA es responsable únicamente de la exactitud de los resultados realizados en las muestras recibidas y tomadas en campo. No se debe de reproducir este informe de ensayo sin la aprobación formal de Consultoría y Construcción SA. CYCSA. ** Información Proporcionada por el cliente y está fuera del alcance de la acreditación.';
    $notasOficiales = !empty($schemaInfo['notas']) && is_array($schemaInfo['notas']) ? $schemaInfo['notas'] : [];

    if (!array_key_exists('notas', $schemaInfo) && !empty($archivoMd)) {
        $rutaMdFallback = dirname(__DIR__, 2) . '/database/ensayos/' . $archivoMd;
        if (!file_exists($rutaMdFallback)) {
            $archSin = strtr(utf8_decode($archivoMd), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
            $rutaMdFallback = dirname(__DIR__, 2) . '/database/ensayos/' . $archSin;
        }
        if (file_exists($rutaMdFallback)) {
            $mdLines = file($rutaMdFallback, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            $collectingFallback = false;
            foreach ($mdLines as $lineaMd) {
                $lineaMd = trim($lineaMd);
                if (stripos($lineaMd, 'Consultoría y Construcción SA') !== false && stripos($lineaMd, 'es responsable únicamente') !== false) {
                    $disclaimerOficial = $lineaMd;
                    $collectingFallback = true;
                    continue;
                }
                if ($collectingFallback) {
                    if (preg_match('/^[-#]{2,}|Última Línea|Ing\.|Página/i', $lineaMd)) {
                        break;
                    }
                    if (!empty($lineaMd)) {
                        $partsFallback = preg_split('/(?<=[^\s])\s+(?=Nota(?:\s*\d+)?\s*:)/iu', $lineaMd);
                        foreach ($partsFallback as $pf) {
                            $pf = trim(preg_replace('/[-#]+$/', '', trim($pf)));
                            if (!empty($pf)) {
                                $notasOficiales[] = $pf;
                            }
                        }
                    }
                }
            }
        }
    }

    $columnas = $schemaInfo['columns'] ?? $columnas;
    if (empty($columnas)) {
        $columnas = ["Código laboratorio", "Nombre muestra", "Área (in²)", "Carga (lb)", "R. Compresión (lb/in²)", "R. Compresión (kg/cm²)"];
    }

    $resultados = [];
    $metadatosGuardados = [];
    if (!empty($detalle['resultados_json'])) {
        $decoded = json_decode($detalle['resultados_json'], true) ?: [];
        if (is_array($decoded)) {
            if (isset($decoded['filas']) && is_array($decoded['filas'])) {
                $resultados = $decoded['filas'];
                $metadatosGuardados = (isset($decoded['metadatos']) && is_array($decoded['metadatos'])) ? $decoded['metadatos'] : [];
            } elseif (isset($decoded['metadatos']) && is_array($decoded['metadatos'])) {
                $metadatosGuardados = $decoded['metadatos'];
                $resultados = [];
            } elseif (function_exists('array_is_list') ? array_is_list($decoded) : ($decoded === [] || array_keys($decoded) === range(0, count($decoded) - 1))) {
                $resultados = $decoded;
            } else {
                $sonClavesNumericas = true;
                foreach (array_keys($decoded) as $k) {
                    if (!is_numeric($k)) {
                        $sonClavesNumericas = false;
                        break;
                    }
                }
                $resultados = $sonClavesNumericas ? array_values($decoded) : [];
            }
        }
    }

    $resultados = filtrarFilasPublicasMatriz($resultados);
    $metaOficial = resolverMetadatosEnsayo($detalle, $schemaInfo, $metadatosGuardados);

    $codigoFormatoOficial = $metaOficial['codigo_formato'];
    $codigoFormatoOficial = htmlspecialchars($codigoFormatoOficial, ENT_QUOTES, 'UTF-8');
    $tituloInforme = htmlspecialchars($schemaInfo['titulo_informe'] ?? 'INFORME DE ENSAYO', ENT_QUOTES, 'UTF-8');
    $subtituloLaboratorio = htmlspecialchars($schemaInfo['subtitulo_laboratorio'] ?? 'Laboratorio de Ensayos y Control de Calidad', ENT_QUOTES, 'UTF-8');
    $firmanteNombre = htmlspecialchars($schemaInfo['firmante_nombre'] ?? 'Ing. Noel Quintana Lira', ENT_QUOTES, 'UTF-8');
    $firmanteCargo = htmlspecialchars($schemaInfo['firmante_cargo'] ?? 'Gerente General', ENT_QUOTES, 'UTF-8');
    $normaOficial = htmlspecialchars($schemaInfo['norma'] ?? ($detalle['norma_astm'] ?? ''), ENT_QUOTES, 'UTF-8');
    $ensayoTituloOficial = $metaOficial['ensayo_realizado'];
    $metodoMuestreoOficial = $metaOficial['metodo_muestreo'];
    $tipoMuestraOficial = $metaOficial['tipo_muestra'];

    $clienteNom = htmlspecialchars($metaOficial['cliente_nombre'], ENT_QUOTES, 'UTF-8');
    $clienteDir = htmlspecialchars($metaOficial['cliente_direccion'], ENT_QUOTES, 'UTF-8');
    $proyNom = htmlspecialchars($metaOficial['proyecto'], ENT_QUOTES, 'UTF-8');
    $fechaIngreso = htmlspecialchars($metaOficial['fecha_ingreso'], ENT_QUOTES, 'UTF-8');
    $fechaMuestreo = htmlspecialchars($metaOficial['fecha_muestreo'], ENT_QUOTES, 'UTF-8');
    $fechaEjecucion = htmlspecialchars($metaOficial['fecha_ejecucion'], ENT_QUOTES, 'UTF-8');
    $fechaEmision = htmlspecialchars($metaOficial['fecha_emision'], ENT_QUOTES, 'UTF-8');
    $muestraTomadaPor = htmlspecialchars($metaOficial['muestra_tomada_por'], ENT_QUOTES, 'UTF-8');
    $procedimientoMuestreo = htmlspecialchars($metaOficial['procedimiento_muestreo'], ENT_QUOTES, 'UTF-8');
    $ubicacion = htmlspecialchars($metaOficial['ubicacion'], ENT_QUOTES, 'UTF-8');
    $ensayoRealizado = nl2br(htmlspecialchars($metaOficial['ensayo_realizado'], ENT_QUOTES, 'UTF-8'));
    $metodoMuestreo = htmlspecialchars($metaOficial['metodo_muestreo'], ENT_QUOTES, 'UTF-8');
    $codigoOS = htmlspecialchars($detalle['codigo_os'] ?? 'OS-S/N', ENT_QUOTES, 'UTF-8');
    $tecnicoNom = htmlspecialchars(!empty($detalle['tecnico_muestreo']) ? $detalle['tecnico_muestreo'] : 'Personal Técnico Autorizado', ENT_QUOTES, 'UTF-8');
    $obs = !empty($detalle['observaciones']) ? htmlspecialchars($detalle['observaciones'], ENT_QUOTES, 'UTF-8') : 'Ensayos ejecutados bajo condiciones ambientales y parámetros establecidos en la norma técnica correspondiente. Equipos con calibración trazable vigente.';

    $datosParaCodigo = !empty($resultados) ? $resultados : (!empty($muestrasSeteadas) ? $muestrasSeteadas : ($detalle['resultados_json'] ?? []));
    $codigoInformeConsecutivo = htmlspecialchars(generarCodigoInformeEnsayo($datosParaCodigo, $metaOficial['fecha_muestreo'] ?? ($metaOficial['fecha_ingreso'] ?? null), $metaOficial['tipo_muestra'] ?? ($detalle['descripcion_ensayo'] ?? '')), ENT_QUOTES, 'UTF-8');

    if (empty($resultados) && !empty($muestrasSeteadas)) {
        foreach ($muestrasSeteadas as $ms) {
            $row = [];
            foreach ($columnas as $col) {
                if ($col === 'Código laboratorio' || $col === 'Codigo Lab') $row[$col] = $ms['codigo_lab'] ?? '';
                elseif ($col === 'Nombre muestra') $row[$col] = $ms['nombre_muestra'] ?? '';
                else $row[$col] = '';
            }
            $resultados[] = $row;
        }
    }

    $theadThs = '<th style="width: 18px; border: 1px solid #000; background: #f3f4f6; padding: 3px 2px; text-align: center;">#</th>';
    foreach ($columnas as $col) {
        $metodo = $colMethods[$col] ?? '';
        $theadThs .= '<th style="border: 1px solid #000; background: #f3f4f6; padding: 3px 2px; text-align: center; font-size: 7.5px;">' . htmlspecialchars($col, ENT_QUOTES, 'UTF-8') . (!empty($metodo) ? '<br><span style="font-size: 6.5px; font-weight: normal; color: #555;">' . htmlspecialchars($metodo, ENT_QUOTES, 'UTF-8') . '</span>' : '') . '</th>';
    }

    $tbodyTrs = '';
    if (!empty($resultados)) {
        $numFila = 0;
        foreach ($resultados as $fila) {
            $numFila++;
            $tbodyTrs .= '<tr>';
            $tbodyTrs .= '<td style="border: 1px solid #000; padding: 2.5px 2px; text-align: center; font-weight: bold;">' . $numFila . '</td>';
            foreach ($columnas as $col) {
                $val = is_array($fila) ? ($fila[$col] ?? ($fila[$schemaInfo['column_aliases'][$col] ?? ''] ?? '')) : '';
                $isCode = ($col === 'Código laboratorio' || $col === 'Codigo Lab');
                $isNum = is_numeric(str_replace(['%', ',', ' '], '', (string)$val)) && !empty($val);
                $align = $isNum ? 'right' : ($col === 'Nombre muestra' ? 'left' : 'center');
                $fontW = $isCode ? 'bold' : 'normal';
                $tbodyTrs .= '<td style="border: 1px solid #000; padding: 2.5px 3px; text-align: ' . $align . '; font-weight: ' . $fontW . ';">' . htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') . '</td>';
            }
            $tbodyTrs .= '</tr>';
        }
    } else {
        for ($k = 1; $k <= 4; $k++) {
            $tbodyTrs .= '<tr>';
            $tbodyTrs .= '<td style="border: 1px solid #000; padding: 2.5px 2px; text-align: center;">' . $k . '</td>';
            foreach ($columnas as $col) {
                $tbodyTrs .= '<td style="border: 1px solid #000; padding: 2.5px 3px; text-align: center; color: #777;">&mdash;</td>';
            }
            $tbodyTrs .= '</tr>';
        }
    }

    $bgCss = !empty($bgBase64) ? 'background-image: url("data:image/jpeg;base64,' . $bgBase64 . '"); background-size: 279.4mm 215.9mm; background-repeat: no-repeat;' : '';

    $notasHtml = '';
    if (!empty($notasOficiales)) {
        $notasHtml .= '<div class="notas-normativas">';
        foreach ($notasOficiales as $nota) {
            $notasHtml .= '<div class="nota-linea">' . htmlspecialchars($nota, ENT_QUOTES, 'UTF-8') . '</div>';
        }
        $notasHtml .= '</div>';
    }
    $disclaimerEsc = htmlspecialchars($disclaimerOficial, ENT_QUOTES, 'UTF-8');

    $logoAcredHtml = '';
    $leftZonaCabecera = '56mm';
    if (!empty($metaOficial['logo_acreditacion'])) {
        $urlAcred = $metaOficial['logo_acreditacion'];
        $cleanPath = preg_replace('#^/Cycsa/publico/#', '', $urlAcred);
        $cleanPath = preg_replace('#^/publico/#', '', $cleanPath);
        $cleanPath = ltrim($cleanPath, '/');
        $rutaAcredAbs = dirname(__DIR__, 2) . '/publico/' . $cleanPath;
        if (file_exists($rutaAcredAbs) && is_file($rutaAcredAbs)) {
            $extImg = strtolower(pathinfo($rutaAcredAbs, PATHINFO_EXTENSION));
            $mimeImg = ($extImg === 'svg') ? 'image/svg+xml' : (($extImg === 'png') ? 'image/png' : (($extImg === 'webp') ? 'image/webp' : 'image/jpeg'));
            $base64Acred = 'data:' . $mimeImg . ';base64,' . base64_encode((string)file_get_contents($rutaAcredAbs));
            $logoAcredHtml = '<div style="position: absolute; top: 6mm; left: 47mm; height: 26mm; width: 34mm; text-align: center;"><img src="' . $base64Acred . '" style="max-height: 24mm; max-width: 34mm; object-fit: contain;"></div>';
            $leftZonaCabecera = '83mm';
        }
    }

    $html = <<<HTML
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<style>
    @page {
        size: 279.4mm 215.9mm;
        margin: 0;
    }
    body {
        margin: 0;
        padding: 0;
        font-family: Arial, Helvetica, sans-serif;
        color: #000000;
        {$bgCss}
    }
    .zona-cabecera {
        position: absolute;
        top: 8mm;
        left: {$leftZonaCabecera};
        right: 14mm;
        height: 28mm;
    }
    .zona-cuerpo {
        position: absolute;
        top: 40mm;
        left: 14mm;
        right: 14mm;
        bottom: 32mm;
    }
    table.meta-informe-tbl {
        width: 100%;
        border-collapse: collapse;
        font-size: 7.5px;
        margin-bottom: 4px;
    }
    table.meta-informe-tbl td {
        padding: 1.2px 3px;
        vertical-align: top;
        font-size: 7.5px;
        color: #000000;
        line-height: 1.2;
    }
    .lbl-col {
        font-weight: bold;
        color: #000000;
        white-space: nowrap;
    }
    .val-col {
        color: #000000;
    }
    table.matriz-tbl {
        width: 100%;
        border-collapse: collapse;
        font-size: 7.2px;
        border: 1px solid #000;
        margin-bottom: 3px;
    }
    .bloque-normativo {
        margin-top: 2px;
        margin-bottom: 2px;
        font-size: 6.8px;
        line-height: 1.2;
    }
    .disclaimer-normativo {
        font-style: italic;
        color: #222222;
        text-align: justify;
        margin-bottom: 1.5px;
    }
    .notas-normativas {
        color: #000000;
        font-weight: normal;
    }
    .nota-linea {
        margin-bottom: 1px;
    }
    table.obs-tbl {
        width: 100%;
        border-collapse: collapse;
        margin-top: 2px;
        margin-bottom: 3px;
    }
    table.obs-tbl td {
        border: 1px solid #000;
        padding: 3px 6px;
        vertical-align: top;
        font-size: 7px;
    }
    .linea-cierre-doc {
        text-align: center;
        font-size: 7.5px;
        color: #333333;
        margin: 2px 0 3px 0;
        letter-spacing: 0.5px;
    }
    table.firmas-tbl {
        width: 100%;
        border-collapse: collapse;
        margin-top: 2px;
    }
    table.firmas-tbl td {
        width: 33.33%;
        text-align: center;
        vertical-align: top;
        padding: 0 10px;
    }
</style>
</head>
<body>

{$logoAcredHtml}

<div class="zona-cabecera">
    <table style="width: 100%; height: 18mm; border-collapse: collapse;">
        <tr>
            <td style="text-align: center; vertical-align: middle;">
                <div style="font-size: 15px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.5px;">{$tituloInforme}</div>
                <div style="font-family: 'Courier New', Courier, monospace; font-size: 11px; font-weight: bold; color: #103487; letter-spacing: 0.5px; margin-top: 2px; margin-bottom: 2px;">{$codigoInformeConsecutivo}</div>
                <div style="font-size: 8.5px; font-weight: bold; color: #475569; margin-top: 1px;">{$subtituloLaboratorio} &bull; O/S: {$codigoOS}</div>
                <div style="font-size: 7.5px; color: #475569;">Norma técnica: {$normaOficial}</div>
            </td>
            <td style="width: 50mm; text-align: right; vertical-align: middle;">
                <div style="border: 1.5px solid #000; padding: 2px 7px; font-weight: bold; font-size: 10px; display: inline-block; font-family: monospace;">{$codigoFormatoOficial}</div>
                <div style="font-size: 7.5px; font-weight: bold; color: #475569; margin-top: 2px;">ISO/IEC 17025:2017</div>
            </td>
        </tr>
    </table>
</div>

<div class="zona-cuerpo">
    <table class="meta-informe-tbl">
        <tr>
            <td class="lbl-col" style="width: 18%;">** Nombre del cliente:</td>
            <td class="val-col" style="width: 32%;">{$clienteNom}</td>
            <td class="lbl-col" style="width: 16%;">** Proyecto:</td>
            <td class="val-col" style="width: 34%;">{$proyNom}</td>
        </tr>
        <tr>
            <td class="lbl-col">** Dirección:</td>
            <td class="val-col">{$clienteDir}</td>
            <td class="lbl-col">** Fecha muestreo:</td>
            <td class="val-col">{$fechaMuestreo}</td>
        </tr>
        <tr>
            <td class="lbl-col">Fecha de ingreso:</td>
            <td class="val-col">{$fechaIngreso}</td>
            <td class="lbl-col">Fecha de ejecución:</td>
            <td class="val-col">{$fechaEjecucion}</td>
        </tr>
        <tr>
            <td class="lbl-col">Tipo de muestra:</td>
            <td class="val-col">{$tipoMuestraOficial}</td>
            <td class="lbl-col">Fecha de emisión:</td>
            <td class="val-col">{$fechaEmision}</td>
        </tr>
        <tr>
            <td class="lbl-col">** Procedimiento de muestreo:</td>
            <td class="val-col">{$procedimientoMuestreo}</td>
            <td class="lbl-col">Muestra tomada por:</td>
            <td class="val-col">{$muestraTomadaPor}</td>
        </tr>
        <tr>
            <td class="lbl-col" style="vertical-align: top;">Ensayo realizado:</td>
            <td class="val-col" style="vertical-align: top; line-height: 1.25;">{$ensayoRealizado}</td>
            <td class="lbl-col" style="vertical-align: top;">
                <div>** Ubicación:</div>
                <div style="margin-top: 10px;">Método de muestreo:</div>
            </td>
            <td class="val-col" style="vertical-align: top;">
                <div>{$ubicacion}</div>
                <div style="margin-top: 10px; font-weight: bold; font-family: monospace;">{$metodoMuestreo}</div>
            </td>
        </tr>
    </table>

    <table class="matriz-tbl">
        <thead><tr>{$theadThs}</tr></thead>
        <tbody>{$tbodyTrs}</tbody>
    </table>

    <div class="bloque-normativo">
        <div class="disclaimer-normativo">{$disclaimerEsc}</div>
        {$notasHtml}
    </div>

    <table class="obs-tbl">
        <tr>
            <td style="width: 50%;">
                <div style="font-weight: bold; border-bottom: 1px solid #cbd5e1; padding-bottom: 2px; margin-bottom: 3px; text-transform: uppercase;">Observaciones y Condiciones del Ensayo</div>
                <div style="line-height: 1.25; color: #1e293b;">{$obs}</div>
            </td>
            <td style="width: 50%;">
                <div style="font-weight: bold; border-bottom: 1px solid #cbd5e1; padding-bottom: 2px; margin-bottom: 3px; text-transform: uppercase;">Declaración de Conformidad e Imparcialidad (ISO/IEC 17025)</div>
                <div style="color: #333; line-height: 1.25;">Los resultados expresados corresponden única y exclusivamente a los especímenes y puntos sometidos a prueba. Prohibida la reproducción parcial sin autorización escrita de CYCSA.</div>
            </td>
        </tr>
    </table>

    <div class="linea-cierre-doc">-------------------------------- Última Línea ---------------------------------</div>

    <table class="firmas-tbl">
        <tr>
            <td>
                <div style="height: 14px;"></div>
                <div style="border-top: 1px solid #000; padding-top: 2px;">
                    <div style="font-weight: bold; font-size: 8px;">{$tecnicoNom}</div>
                    <div style="color: #444; font-size: 7px; text-transform: uppercase;">Ejecutado por (Técnico Responsable)</div>
                </div>
            </td>
            <td>
                <div style="height: 14px;"></div>
                <div style="border-top: 1px solid #000; padding-top: 2px;">
                    <div style="font-weight: bold; font-size: 8px;">Supervisión de Ensayos</div>
                    <div style="color: #444; font-size: 7px; text-transform: uppercase;">Revisado por (Supervisor de Área)</div>
                </div>
            </td>
            <td>
                <div style="height: 14px;"></div>
                <div style="border-top: 1px solid #000; padding-top: 2px;">
                    <div style="font-weight: bold; font-size: 8px;">{$firmanteNombre}</div>
                    <div style="color: #444; font-size: 7px; text-transform: uppercase;">Aprobado ({$firmanteCargo} / ISO 17025)</div>
                </div>
            </td>
        </tr>
    </table>
</div>

</body>
</html>
HTML;

    $dompdf->loadHtml($html);
    $dompdf->setPaper([0, 0, 792.0, 612.0], 'landscape');
    $dompdf->render();

    agregarNumeracionPaginasDompdf($dompdf, 'Página {PAGE_NUM} de {PAGE_COUNT}', 22, 40, 8.5, [0.06, 0.20, 0.53], true);

    return $dompdf->output();
}

/**
 * Genera el documento completo PDF (Propuesta Comercial + Reportes de Laboratorio de cada ensayo).
 */
function generarCotizacionCompletaPDF(array $cotizacion, array $detalles): string {
    $options = new Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $options->set('tempDir', dirname(__DIR__, 2) . '/storage/cache');
    $options->set('fontCache', dirname(__DIR__, 2) . '/storage/cache');
    $dompdf = new Dompdf($options);

    $fecha = date('d/m/Y', strtotime($cotizacion['fecha_creacion']));
    $subtotal = number_format($cotizacion['subtotal'], 2, '.', ',');
    $impuesto = number_format($cotizacion['impuesto'], 2, '.', ',');
    $total = number_format($cotizacion['total'], 2, '.', ',');

    $qrPath = dirname(__DIR__, 2) . '/publico/img/qr_terminos.png';
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
                        <tr><td style=\"color: #64748b; padding: 2px 0;\">Elaborado por:</td><td style=\"font-weight: bold; color: #103487;\">{$creadorNombre}</td></tr>
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

    $rutaSchema = __DIR__ . '/../../database/ensayos/formatos_schema.json';
    if (!file_exists($rutaSchema)) {
        $rutaSchema = __DIR__ . '/../datos_ensayos_markdown/formatos_schema.json';
    }
    $schemaData = file_exists($rutaSchema) ? json_decode(file_get_contents($rutaSchema), true) : [];

    // Append report sheets
    foreach ($detalles as $det) {
        if (!empty($det['formato_reporte']) && !empty($det['archivo_markdown'])) {
            $archivoMd = $det['archivo_markdown'];
            $columns = $schemaData[$archivoMd]['columns'] ?? ["Código laboratorio", "Nombre muestra", "Resultado"];
            $filas = json_decode($det['resultados_json'] ?? '', true) ?: [];
            if (isset($filas['filas'])) {
                $filas = $filas['filas'];
            }
            $resultados = is_array($filas) ? $filas : [];
            $resultados = filtrarFilasPublicasMatriz($resultados);
            $filas = $resultados;

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

            $formatoDisc = !empty($schemaData[$archivoMd]['disclaimer']) ? htmlspecialchars($schemaData[$archivoMd]['disclaimer'], ENT_QUOTES, 'UTF-8') : 'Consultoría y Construcción SA. CYCSA es responsable únicamente de la exactitud de los resultados realizados en las muestras recibidas y tomadas en campo. No se debe de reproducir este informe de ensayo sin la aprobación formal de Consultoría y Construcción SA. CYCSA.';
            $formatoNotas = !empty($schemaData[$archivoMd]['notas']) && is_array($schemaData[$archivoMd]['notas']) ? $schemaData[$archivoMd]['notas'] : [];
            $formatoNotasHtml = '';
            if (!empty($formatoNotas)) {
                $formatoNotasHtml = '<div style="margin-top: 4px; font-size: 8px; color: #334155; line-height: 1.3;">';
                foreach ($formatoNotas as $fn) {
                    $formatoNotasHtml .= '<div style="margin-bottom: 2px;">' . htmlspecialchars($fn, ENT_QUOTES, 'UTF-8') . '</div>';
                }
                $formatoNotasHtml .= '</div>';
            }

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

                <div style=\"font-size: 8px; color: #475569; line-height: 1.3; margin-top: 12px; border-top: 1px solid #cbd5e1; padding-top: 6px;\">
                    <div style=\"font-style: italic;\">{$formatoDisc}</div>
                    {$formatoNotasHtml}
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

    // Procesar Documento Adjunto (DOCX, Imagen, PDF, TXT, etc.)
    $infoAdjunto = procesarArchivoAdjuntoCotizacion($cotizacion, '');
    $anexoAdjuntoSeccion = $infoAdjunto['html'];
    $rutaPdfAdjuntoFusionar = $infoAdjunto['ruta_pdf'];

    $html .= $anexoAdjuntoSeccion . "</body></html>";

    $dompdf->loadHtml($html);
    $dompdf->render();

    $tienePdfAdjuntoParaFusionar = !empty($rutaPdfAdjuntoFusionar) && file_exists($rutaPdfAdjuntoFusionar);
    if (!$tienePdfAdjuntoParaFusionar) {
        agregarNumeracionPaginasDompdf($dompdf, 'Página {PAGE_NUM} de {PAGE_COUNT}', 26, 40, 9.0, [0.06, 0.20, 0.53], true);
    }

    $pdfSalida = $dompdf->output();

    if ($tienePdfAdjuntoParaFusionar) {
        $pdfSalida = fusionarPdfConAdjunto($pdfSalida, $rutaPdfAdjuntoFusionar);
    }

    return $pdfSalida;
}

/**
 * Genera el archivo PDF de la Hoja de Solicitud de Servicio CYCSA-RT-FM-13 usando Dompdf.
 */
function generarHojaSolicitudPDF(array $hoja, array $os): string {
    $options = new \Dompdf\Options();
    $options->set('isHtml5ParserEnabled', true);
    $options->set('isRemoteEnabled', false);
    $options->set('defaultFont', 'Arial');
    $options->set('tempDir', dirname(__DIR__, 2) . '/storage/cache');
    $options->set('fontCache', dirname(__DIR__, 2) . '/storage/cache');
    $dompdf = new \Dompdf\Dompdf($options);

    $logoPath = dirname(__DIR__, 2) . '/publico/img/logo_cycsa_rt_fm_13.png';
    if (!file_exists($logoPath)) {
        $logoPath = dirname(__DIR__, 2) . '/publico/img/logo_cycsa.jpg';
    }
    $logoBase64 = '';
    if (file_exists($logoPath)) {
        $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
    }

    $cb = function(bool $checked, string $label, string $extraUnderline = '', bool $alwaysUnderline = false): string {
        $box = $checked
            ? '<span style="display:inline-block; width:9px; height:9px; border:1px solid #4169e1; text-align:center; vertical-align:middle; line-height:8px; font-size:8px; font-family:\'DejaVu Sans\', sans-serif; margin-right:2px; color:#000000; font-weight:bold;">&#10003;</span>'
            : '<span style="display:inline-block; width:9px; height:9px; border:1px solid #4169e1; vertical-align:middle; margin-right:2px;"></span>';
        
        $underlineHtml = '';
        if (!empty($extraUnderline) || $alwaysUnderline) {
            $underlineHtml = ' <span style="display:inline-block; border-bottom:1px solid #4169e1; min-width:140px; color:#000; font-size:7.5pt; padding:0 2px;">' . htmlspecialchars($extraUnderline, ENT_QUOTES, 'UTF-8') . '&nbsp;</span>';
        }
        
        return '<span style="display:inline-block; margin-right:10px; font-size:7.5pt; color:#4169e1; vertical-align:middle;">' . $box . '<span style="vertical-align:middle;">' . $label . '</span>' . $underlineHtml . '</span>';
    };

    $naturalezaChecked = array_map('trim', explode(',', $hoja['naturaleza_muestra'] ?? ''));

    $llegadaRaw = $hoja['fecha_hora_llegada_laboratorio'] ?? '';
    $tsLlegada = strtotime($llegadaRaw);
    $fechaLlegadaStr = $tsLlegada ? date('Y-m-d', $tsLlegada) : $llegadaRaw;
    $horaLlegadaStr = $tsLlegada ? date('h:i a', $tsLlegada) : '';

    $muestras = json_decode($hoja['muestras_json'] ?? '[]', true) ?: [];
    $tablaMuestrasHtml = '';
    foreach ($muestras as $m) {
        $tablaMuestrasHtml .= '<tr>
            <td style="border:1px solid #4169e1; padding:2px 5px; font-size:7.5pt; color:#000;">' . htmlspecialchars($m['nombre_muestra'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
            <td style="border:1px solid #4169e1; padding:2px 5px; font-size:7.5pt; color:#000;">' . htmlspecialchars($m['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
            <td style="border:1px solid #4169e1; padding:2px 5px; font-size:7.5pt; color:#000;">' . htmlspecialchars($m['info_importante'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
        </tr>';
    }
    if (empty($tablaMuestrasHtml)) {
        $tablaMuestrasHtml = '<tr>
            <td style="border:1px solid #4169e1; padding:3px; font-size:7.5pt; text-align:center; color:#64748b;">—</td>
            <td style="border:1px solid #4169e1; padding:3px; font-size:7.5pt; text-align:center; color:#64748b;">—</td>
            <td style="border:1px solid #4169e1; padding:3px; font-size:7.5pt; text-align:center; color:#64748b;">—</td>
        </tr>';
    }

    $numRegistro = !empty($hoja['numero_registro']) ? $hoja['numero_registro'] : sprintf('%05d', $hoja['id_os'] ?? $os['id'] ?? 1);

    $html = '
    <!DOCTYPE html>
    <html lang="es">
    <head>
        <meta charset="UTF-8">
        <style>
            @page {
                margin: 15pt 25pt 15pt 25pt;
                size: letter portrait;
            }
            body {
                font-family: Arial, Helvetica, sans-serif;
                color: #000000;
                margin: 0;
                padding: 0;
                font-size: 8pt;
                line-height: 1.2;
            }
            table {
                border-collapse: collapse;
                width: 100%;
            }
            .outer-frame {
                border: 1.5px solid #4169e1;
                width: 100%;
            }
            .b-bottom {
                border-bottom: 1.5px solid #4169e1;
            }
            .b-bottom-thin {
                border-bottom: 1px solid #4169e1;
            }
            .b-right {
                border-right: 1px solid #4169e1;
            }
            .color-blue {
                color: #4169e1;
            }
            .bold {
                font-weight: bold;
            }
            .underline-cell {
                border-bottom: 1px solid #4169e1;
                color: #000000;
                padding-left: 3px;
            }
            .section-header {
                font-weight: bold;
                color: #4169e1;
                font-size: 8.5pt;
                padding: 2px 4px;
            }
            .sub-header {
                font-weight: bold;
                color: #4169e1;
                font-size: 7.8pt;
                padding: 1px 4px 1px 18pt;
            }
        </style>
    </head>
    <body>
        <div class="outer-frame">
            <!-- HEADER -->
            <table class="b-bottom">
                <tr>
                    <td style="width: 25%; padding: 3px 5px; vertical-align: middle;" class="b-right">
                        <table>
                            <tr>
                                <td style="width: 42px; vertical-align: middle;">
                                    ' . (!empty($logoBase64) ? '<img src="' . $logoBase64 . '" style="width: 38px; height: auto;">' : '<span style="font-size:16px; font-weight:bold; color:#4169e1;">CYCSA</span>') . '
                                </td>
                                <td style="vertical-align: middle; padding-left: 3px; font-size: 6.8pt; color: #4169e1; line-height: 1.15;">
                                    <strong>Consultoría y<br>Construcción S.A</strong><br>
                                    Km 83 carretera León-Managua<br>
                                    88516377, 88534238
                                </td>
                            </tr>
                        </table>
                    </td>
                    <td style="width: 45%; text-align: center; vertical-align: middle;" class="b-right">
                        <div style="font-family: \'Times New Roman\', Times, serif; font-size: 15pt; font-weight: bold; color: #4169e1; line-height: 1.1;">
                            HOJA DE SOLICITUD<br>DE SERVICIO
                        </div>
                    </td>
                    <td style="width: 30%; vertical-align: top; padding: 0;">
                        <table>
                            <tr>
                                <td style="padding: 1px 3px; font-size: 7pt; color: #4169e1; text-align: right;">
                                    A rellenar por el laboratorio
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 1px 3px; font-size: 8pt;">
                                    <table style="width: 100%;">
                                        <tr>
                                            <td style="color: #4169e1; font-size: 8pt;">Registro:</td>
                                            <td style="text-align: right; font-weight: bold; color: #000; font-size: 8.5pt;">' . htmlspecialchars($numRegistro, ENT_QUOTES, 'UTF-8') . '</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 1px 3px; font-size: 7pt; color: #4169e1; text-align: center;" class="b-bottom-thin">
                                    Fecha/Hora de llegada al Laboratorio
                                </td>
                            </tr>
                            <tr>
                                <td style="padding: 0;">
                                    <table>
                                        <tr>
                                            <td style="width: 50%; text-align: center; font-size: 7.5pt; padding: 2px 0; color: #000;" class="b-right">
                                                ' . htmlspecialchars($fechaLlegadaStr, ENT_QUOTES, 'UTF-8') . '
                                            </td>
                                            <td style="width: 50%; text-align: center; font-size: 7.5pt; padding: 2px 0; color: #000;">
                                                ' . htmlspecialchars($horaLlegadaStr, ENT_QUOTES, 'UTF-8') . '
                                            </td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- SECCIÓN 1: EMPRESA O CLIENTE -->
            <table class="b-bottom">
                <tr>
                    <td style="padding: 2px 5px;">
                        <table>
                            <tr>
                                <td class="bold color-blue" style="font-size: 8pt;">1. EMPRESA O CLIENTE QUE SOLICITA EL SERVICIO</td>
                                <td class="bold color-blue" style="font-size: 8pt; text-align: right;">Código de documento: ' . htmlspecialchars($hoja['codigo_documento'] ?? 'CYCSA-RT-FM-13', ENT_QUOTES, 'UTF-8') . '</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0 5px 3px 5px;">
                        <table style="font-size: 7.8pt;">
                            <tr>
                                <td style="width: 50px; color: #4169e1;">Nombre:</td>
                                <td class="underline-cell">' . htmlspecialchars($hoja['nombre_empresa_o_cliente'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                            </tr>
                            <tr>
                                <td style="color: #4169e1; padding-top: 2px;">Dirección:</td>
                                <td class="underline-cell" style="padding-top: 2px;">' . htmlspecialchars($hoja['direccion_proyecto'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding-top: 2px;">
                                    <table style="font-size: 7.8pt;">
                                        <tr>
                                            <td style="width: 50px; color: #4169e1;">Teléfono:</td>
                                            <td class="underline-cell" style="width: 150px;">' . htmlspecialchars($hoja['telefono'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                                            <td style="width: 105px; color: #4169e1; padding-left: 10px;">Correo electrónico:</td>
                                            <td class="underline-cell">' . htmlspecialchars($hoja['correo_electronico'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                            <tr>
                                <td colspan="2" style="padding-top: 2px;">
                                    <table style="font-size: 7.8pt;">
                                        <tr>
                                            <td style="width: 215px; color: #4169e1;">Nombre de la persona quien trae la muestra:</td>
                                            <td class="underline-cell">' . htmlspecialchars($hoja['nombre_persona_entrega_muestra'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                                        </tr>
                                    </table>
                                </td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- SECCIÓN 1: DATOS DE LA MUESTRA -->
            <table class="b-bottom">
                <tr>
                    <td class="section-header">1. DATOS DE LA MUESTRA</td>
                </tr>
                <tr>
                    <td class="sub-header">1.1 DENOMINACIÓN-DESCRIPCIÓN E IDENTIFICACIÓN DE LA MUESTRA</td>
                </tr>
                <tr>
                    <td style="padding: 0 5px 2px 20pt;">
                        <div style="color: #4169e1; font-weight: bold; font-size: 7.5pt; margin-bottom: 1px;">Naturaleza de la muestra</div>
                        <div>
                            ' . $cb(in_array('Concreto', $naturalezaChecked), 'Concreto') . '
                            ' . $cb(in_array('Bloques', $naturalezaChecked), 'Bloques') . '
                            ' . $cb(in_array('Suelo', $naturalezaChecked), 'Suelo') . '
                            ' . $cb(in_array('Adoquines', $naturalezaChecked), 'Adoquines') . '
                            ' . $cb(in_array('Agregados', $naturalezaChecked), 'Agregados') . '
                            ' . $cb(in_array('Otros materiales', $naturalezaChecked) || in_array('Otros', $naturalezaChecked), 'Otros materiales') . '
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 2px 5px 1px 20pt;">
                        <div style="color: #4169e1; font-weight: bold; font-size: 7.5pt;">Procedencia/ Punto de muestreo:</div>
                        <div style="color: #4169e1; font-size: 6.8pt; margin-bottom: 1px;">Describir la ubicación del punto donde se tomó la muestra asi como la ubicación del municipio o comarca.</div>
                        <div style="font-size: 7.5pt; color: #000; text-transform: uppercase;">' . htmlspecialchars($hoja['procedencia_punto_muestreo'] ?? '', ENT_QUOTES, 'UTF-8') . '</div>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 2px 5px 1px 20pt;">
                        <table style="font-size: 7.8pt;">
                            <tr>
                                <td style="width: 195px; color: #000;">Persona quien tomó la muestra:</td>
                                <td class="underline-cell">' . htmlspecialchars($hoja['nombre_persona_toma_muestra'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                            </tr>
                        </table>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 1px 5px 3px 5px;">
                        <table style="font-size: 7.8pt;">
                            <tr>
                                <td style="width: 235px; color: #4169e1; font-style: italic; font-weight: bold;">1.2 Fecha y hora en que se tomó la muestra:</td>
                                <td class="underline-cell">' . htmlspecialchars($hoja['fecha_hora_toma_muestra'] ?? '', ENT_QUOTES, 'UTF-8') . '</td>
                            </tr>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- SECCIÓN 2: IDENTIFICACIONES PROPIAS DE LA MUESTRA -->
            <table class="b-bottom">
                <tr>
                    <td class="section-header">2. IDENTIFICACIONES PROPIAS DE LA MUESTRA</td>
                </tr>
                <tr>
                    <td style="padding: 0;">
                        <table>
                            <thead>
                                <tr>
                                    <th style="border-top:1px solid #4169e1; border-bottom:1px solid #4169e1; border-right:1px solid #4169e1; width:35%; font-size:7.5pt; color:#4169e1; text-align:center; padding:2px;">Nombre de la muestra</th>
                                    <th style="border-top:1px solid #4169e1; border-bottom:1px solid #4169e1; border-right:1px solid #4169e1; width:25%; font-size:7.5pt; color:#4169e1; text-align:center; padding:2px;">Descripción</th>
                                    <th style="border-top:1px solid #4169e1; border-bottom:1px solid #4169e1; width:40%; font-size:7.5pt; color:#4169e1; text-align:center; padding:2px;">Informaciones importantes</th>
                                </tr>
                            </thead>
                            <tbody>
                                ' . $tablaMuestrasHtml . '
                            </tbody>
                        </table>
                    </td>
                </tr>
            </table>

            <!-- SECCIÓN 3: PARAMETROS SOLICITADOS -->
            <table>
                <tr>
                    <td class="section-header">3. PARAMETROS SOLICITADOS</td>
                </tr>
                <tr>
                    <td class="sub-header">3.1 MUESTRA DE CONCRETO, ADOQUINES, BLOQUES</td>
                </tr>
                <tr>
                    <td style="padding: 1px 5px 2px 20pt;">
                        ' . $cb(!empty($hoja['req_resistencia_concreto']), 'Resistencia de conc') . '
                        ' . $cb(!empty($hoja['req_resistencia_adoquin']), 'Resistencia de adoquin') . '
                        ' . $cb(!empty($hoja['req_resistencia_bloques']), 'Resistencia bloques') . '
                        ' . $cb(!empty($hoja['req_otros_concreto']), 'Otros', $hoja['req_otros_concreto'] ?? '', true) . '
                    </td>
                </tr>
                <tr>
                    <td class="sub-header">3.2 MUESTRAS DE SUELO</td>
                </tr>
                <tr>
                    <td style="padding: 1px 5px 1px 20pt;">
                        ' . $cb(!empty($hoja['req_granulometria']), 'Granulometria') . '
                        ' . $cb(!empty($hoja['req_limites_atterberg']), 'Límites de atterberg') . '
                        ' . $cb(!empty($hoja['req_humedad']), 'Humedad') . '
                        ' . $cb(!empty($hoja['req_resistencia_corte']), 'Resistencia al corte') . '
                        ' . $cb(!empty($hoja['req_clasificacion_sucs_hr']), 'Clasificación SUCS/HR') . '
                        ' . $cb(!empty($hoja['req_proctor_sm']), 'PROCTOR S/M') . '
                    </td>
                </tr>
                <tr>
                    <td style="padding: 1px 5px 2px 20pt;">
                        ' . $cb(!empty($hoja['req_infiltracion']), 'Infiltración') . '
                        ' . $cb(!empty($hoja['req_cbr']), 'CBR') . '
                        ' . $cb(!empty($hoja['req_densidad']), 'Densidad') . '
                        ' . $cb(!empty($hoja['req_otros_suelo']), 'Otros', $hoja['req_otros_suelo'] ?? '', true) . '
                    </td>
                </tr>
                <tr>
                    <td class="section-header" style="padding-top: 1px;">3.3 OTROS MATERIALES</td>
                </tr>
                <tr>
                    <td style="padding: 1px 5px 1px 18pt;">
                        ' . $cb(!empty($hoja['req_otros_materiales']), 'Otro', '') . '
                        <span style="display:inline-block; border-bottom:1px solid #4169e1; width:85%; vertical-align:middle;">&nbsp;</span>
                    </td>
                </tr>
                <tr>
                    <td style="padding: 1px 5px 0 5px; font-size: 7.5pt; color: #4169e1;">
                        * Si seleccionó la casilla otros, favor decir que análisis necesita
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0 5px 2px 18pt; font-size: 7.8pt; color: #000;">
                        ' . htmlspecialchars(!empty($hoja['descripcion_otros_analisis']) ? $hoja['descripcion_otros_analisis'] : 'No Aplica', ENT_QUOTES, 'UTF-8') . '
                    </td>
                </tr>
                <tr>
                    <td style="padding: 1px 5px 0 5px; font-size: 7.5pt; color: #4169e1;">
                        Analisis adicionales
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0 5px 2px 18pt; font-size: 7.8pt; color: #000;">
                        ' . htmlspecialchars(!empty($hoja['analisis_adicionales']) ? $hoja['analisis_adicionales'] : 'No Aplica', ENT_QUOTES, 'UTF-8') . '
                    </td>
                </tr>
                <tr>
                    <td style="padding: 1px 5px 0 5px; font-size: 7.5pt; color: #4169e1;">
                        Observaciones
                    </td>
                </tr>
                <tr>
                    <td style="padding: 0 5px 3px 5px; font-size: 7.8pt; color: #000;">
                        ' . htmlspecialchars(!empty($hoja['observaciones']) ? $hoja['observaciones'] : 'NINGUNA', ENT_QUOTES, 'UTF-8') . '
                    </td>
                </tr>
            </table>

            <!-- FOOTER FIRMAS -->
            <table style="border-top: 1px solid #4169e1;">
                <tr>
                    <td style="width: 50%; text-align: center; vertical-align: bottom; padding: 14px 5px 2px 5px;" class="b-right">
                        <div style="font-weight: normal; font-size: 8pt; color: #000; margin-bottom: 1px;">
                            ' . htmlspecialchars($hoja['nombre_recibe_cycsa'] ?? '', ENT_QUOTES, 'UTF-8') . '
                        </div>
                    </td>
                    <td style="width: 50%; text-align: center; vertical-align: bottom; padding: 14px 5px 2px 5px;">
                        <div style="font-weight: normal; font-size: 8pt; color: #000; margin-bottom: 1px;">
                            ' . htmlspecialchars($hoja['nombre_empresa_o_cliente'] ?? '', ENT_QUOTES, 'UTF-8') . '
                        </div>
                    </td>
                </tr>
                <tr>
                    <td style="width: 50%; text-align: center; padding: 2px 4px 3px 4px; font-size: 7pt; color: #4169e1; font-weight: bold; border-top: 1px solid #4169e1;" class="b-right">
                        PERSONA DE CYCSA QUIEN RECIBE LA MUESTRA
                    </td>
                    <td style="width: 50%; text-align: center; padding: 2px 4px 3px 4px; font-size: 7pt; color: #4169e1; font-weight: bold; border-top: 1px solid #4169e1;">
                        FIRMA DEL CLIENTE
                    </td>
                </tr>
                <tr>
                    <td colspan="2" style="border-top: 1px solid #4169e1; padding: 2px 4px; font-size: 6.5pt; color: #4169e1; line-height: 1.15; text-align: justify;">
                        *.- Con este documento doy fe, que todo lo escrito lo he revisado y que he quedado de mutuo acuerdo de los servicios que me ofrecerá CYCSA en la muestra. Cualquier cambio deberá ser notificado previo a CYCSA de cualquier forma escrita para su debido registro
                    </td>
                </tr>
            </table>
        </div>
    </body>
    </html>';

    $dompdf->loadHtml($html);
    $dompdf->render();

    agregarNumeracionPaginasDompdf($dompdf, 'Página {PAGE_NUM} de {PAGE_COUNT}', 14, 25, 8.5, [0.06, 0.20, 0.53], true);

    return $dompdf->output();
}

/**
 * LIMS ISO/IEC 17025: Limpia el código de muestra quitando sufijos de réplica (CR) o repetición (C1, C2...)
 * para emitir el informe oficial al cliente con el código base (Paso 18).
 */
function obtenerCodigoLimpioInforme(string $codigoMuestra): string {
    return preg_replace('/(CR|C\d+)$/i', '', trim($codigoMuestra));
}

/**
 * LIMS ISO/IEC 17025: Genera el código para réplicas de calidad (CR) o repeticiones (C1, C2...) (Pasos 14 y 17).
 */
function generarCodigoCalidadLIMS(string $codigoBase, string $tipo = 'replica', int $numeroRepeticion = 1): string {
    $codigoLimpio = obtenerCodigoLimpioInforme($codigoBase);
    if (strtolower($tipo) === 'replica') {
        return $codigoLimpio . 'CR';
    }
    return $codigoLimpio . 'C' . max(1, $numeroRepeticion);
}

/**
 * Codifica un ID numérico de forma reversible (Hashids).
 */
/**
 * Determina si un ensayo o ítem corresponde a compactación / densidad in situ
 * en donde no se emite solicitud de muestras de laboratorio físicas, sino que pasa directo
 * a Operaciones para llenado de matriz técnica.
 */
function esItemCompactacion(array $item): bool {
    $formatoId = (int)($item['formato_id'] ?? 0);
    // Formatos técnicos: 1 (Densímetro Nuclear), 3 (Cono de Arena), 4 (Reemplazo de Agua), 15 (Proctor Estándar / Modificado)
    if (in_array($formatoId, [1, 3, 4, 15])) {
        return true;
    }
    
    $archivoMd = $item['archivo_markdown'] ?? '';
    if (in_array($archivoMd, [
        'compactacion_densimetro_nuclear.md',
        'formato_de_compactacion_por_cono_de_arena.md',
        'formato_de_compactacion_por_reemplazo_de_agua_no_acreditado.md',
        'proctor_estandar.md'
    ])) {
        return true;
    }
    
    $texto = mb_strtolower(
        ($item['descripcion_ensayo'] ?? '') . ' ' . 
        ($item['nombre_comercial'] ?? '') . ' ' . 
        ($item['nombre_ensayo'] ?? '') . ' ' . 
        ($item['norma_astm'] ?? '') . ' ' . 
        ($item['formato_nombre'] ?? '') . ' ' .
        ($item['ensayo_servicio'] ?? '')
    );
    
    $patrones = [
        'compactac', 'proctor', 'cono de arena', 'densimetro', 
        'densímetro', 'reemplazo de agua', 'd698', 'd1557', 'd1556', 'd6938', 'd5030'
    ];
    
    foreach ($patrones as $p) {
        if (strpos($texto, $p) !== false) {
            return true;
        }
    }
    
    return false;
}

/**
 * Determina si una Orden de Servicio está compuesta únicamente por ensayos de compactación / in situ.
 */
function esOrdenSoloCompactacion(array $items): bool {
    if (empty($items)) return false;
    foreach ($items as $it) {
        if (!esItemCompactacion($it)) {
            return false;
        }
    }
    return true;
}

/**
 * Codifica un ID numérico de forma reversible (Hashids).
 */
function codificarId($id): string {
    if (empty($id)) return '';
    return \Cycsa\App\Helpers\HashHelper::codificar((int)$id);
}

/**
 * Decodifica un ID numérico de forma reversible. Retorna null si no es válido.
 */
function decodificarId($hash): ?int {
    if (empty($hash)) return null;
    // Si ya es un ID numérico puro (fallback de retrocompatibilidad), lo devolvemos directamente
    if (is_numeric($hash)) return (int)$hash;
    $res = \Cycsa\App\Helpers\HashHelper::decodificar((string)$hash);
    if ($res !== null && $res > 0) return $res;
    // Retrocompatibilidad con base64 simple (ej. base64_encode('1') => 'MQ==')
    $b64 = base64_decode((string)$hash, true);
    if ($b64 !== false && is_numeric($b64)) {
        return (int)$b64;
    }
    return null;
}

/**
 * Convierte un valor numérico a su representación en letras (Español / Córdobas o Dólares).
 */
function numeroALetras(float $numero, string $moneda = 'C$'): string {
    $enteros = floor($numero);
    $centavos = round(($numero - $enteros) * 100);
    
    $unidades = ['', 'un', 'dos', 'tres', 'cuatro', 'cinco', 'seis', 'siete', 'ocho', 'nueve', 'diez', 'once', 'doce', 'trece', 'catorce', 'quince', 'dieciséis', 'diecisiete', 'dieciocho', 'diecinueve'];
    $decenas = ['', 'diez', 'veinte', 'treinta', 'cuarenta', 'cincuenta', 'sesenta', 'setenta', 'ochenta', 'noventa'];
    $centenas = ['', 'ciento', 'doscientos', 'trescientos', 'cuatrocientos', 'quinientos', 'seiscientos', 'setecientos', 'ochocientos', 'novecientos'];

    $convertir3Cifras = function($n) use (&$convertir3Cifras, $unidades, $decenas, $centenas) {
        if ($n === 0) return '';
        if ($n === 100) return 'cien';
        $res = '';
        $c = floor($n / 100);
        $d = floor(($n % 100) / 10);
        $u = $n % 10;
        if ($c > 0) $res .= $centenas[$c] . ' ';
        $du = $n % 100;
        if ($du > 0) {
            if ($du < 20) {
                $res .= $unidades[$du] . ' ';
            } elseif ($du % 10 === 0) {
                $res .= $decenas[$d] . ' ';
            } elseif ($d === 2) {
                $res .= 'veinti' . $unidades[$u] . ' ';
            } else {
                $res .= $decenas[$d] . ' y ' . $unidades[$u] . ' ';
            }
        }
        return trim($res);
    };

    if ($enteros == 0) {
        $letras = 'cero';
    } elseif ($enteros < 1000) {
        $letras = $convertir3Cifras($enteros);
    } elseif ($enteros < 1000000) {
        $miles = floor($enteros / 1000);
        $resto = $enteros % 1000;
        $txtMiles = ($miles === 1.0 || $miles === 1) ? 'mil' : $convertir3Cifras($miles) . ' mil';
        $letras = trim($txtMiles . ' ' . $convertir3Cifras($resto));
    } else {
        $millones = floor($enteros / 1000000);
        $restoMill = $enteros % 1000000;
        $miles = floor($restoMill / 1000);
        $resto = $restoMill % 1000;
        $txtMill = ($millones === 1.0 || $millones === 1) ? 'un millón' : $convertir3Cifras($millones) . ' millones';
        $txtMiles = ($miles > 0) ? (($miles === 1.0 || $miles === 1) ? 'mil' : $convertir3Cifras($miles) . ' mil') : '';
        $letras = trim($txtMill . ' ' . $txtMiles . ' ' . $convertir3Cifras($resto));
    }

    $nomMoneda = ($moneda === '$' || strtoupper($moneda) === 'USD') ? 'DÓLARES NETOS' : 'CÓRDOBAS NETOS';
    return mb_strtoupper($letras, 'UTF-8') . " CON " . sprintf('%02d/100', $centavos) . " " . $nomMoneda;
}

/**
 * Obtiene el estado de revisión y control de calidad de una matriz técnica (cotizacion_detalles.resultados_json).
 *
 * Ciclo de vida:
 * - pendiente: Aún no se han guardado resultados.
 * - en_revision: Guardado por el laboratorista/técnico, pendiente de revisión y aprobación por supervisión.
 * - devuelta: Observada y devuelta por el supervisor al técnico para correcciones (con motivo_devolucion).
 * - aprobada: Aprobada formalmente por el supervisor. Habilita el envío oficial al cliente y cierre de calidad.
 *
 * @param string|null $resultadosJson
 * @return array
 */
function obtenerEstadoRevisionMatriz(?string $resultadosJson): array {
    $defecto = [
        'estado' => 'pendiente',
        'estado_label' => 'PENDIENTE MATRIZ',
        'badge_clase' => 'badge-pendiente',
        'badge_bg' => '#f1f5f9',
        'badge_color' => '#475569',
        'badge_border' => '#cbd5e1',
        'badge_icono' => 'fa-solid fa-hourglass',
        'fecha_envio' => null,
        'usuario_envio' => null,
        'fecha_revision' => null,
        'usuario_revisor' => null,
        'motivo_devolucion' => null,
        'puede_enviar_cliente' => false,
        'puede_aprobar' => false,
        'puede_devolver' => false,
        'tiene_resultados' => false,
        'historial' => []
    ];

    if (empty($resultadosJson) || $resultadosJson === '[]') {
        return $defecto;
    }

    $data = json_decode($resultadosJson, true);
    if (!is_array($data)) {
        return $defecto;
    }

    $filas = isset($data['filas']) ? $data['filas'] : (isset($data[0]) ? $data : []);
    $revision = isset($data['revision']) && is_array($data['revision']) ? $data['revision'] : [];

    // Si no hay filas y no hay revisión registrada, sigue pendiente
    if (empty($filas) && empty($revision)) {
        return $defecto;
    }

    $defecto['tiene_resultados'] = true;
    $estado = $revision['estado'] ?? 'en_revision';

    switch ($estado) {
        case 'aprobada':
            $label = 'APROBADA';
            $badgeClase = 'badge-aprobada';
            $badgeBg = '#dcfce7';
            $badgeColor = '#15803d';
            $badgeBorder = '#bbf7d0';
            $badgeIcono = 'fa-solid fa-circle-check';
            $puedeEnviar = true;
            $puedeAprobar = false;
            $puedeDevolver = false; // Sellada: Una vez aprobada formalmente no se muestra botón devolver en flujo normal
            break;

        case 'devuelta':
            $label = 'DEVUELTA';
            $badgeClase = 'badge-devuelta';
            $badgeBg = '#fee2e2';
            $badgeColor = '#b91c1c';
            $badgeBorder = '#fca5a5';
            $badgeIcono = 'fa-solid fa-circle-exclamation';
            $puedeEnviar = false;
            $puedeAprobar = true;
            $puedeDevolver = false;
            break;

        case 'en_revision':
        default:
            $estado = 'en_revision';
            $label = 'PENDIENTE DE REVISIÓN';
            $badgeClase = 'badge-revision';
            $badgeBg = '#fef3c7';
            $badgeColor = '#b45309';
            $badgeBorder = '#fde68a';
            $badgeIcono = 'fa-solid fa-clock-rotate-left';
            $puedeEnviar = false;
            $puedeAprobar = true;
            $puedeDevolver = true;
            break;
    }

    return [
        'estado' => $estado,
        'estado_label' => $label,
        'badge_clase' => $badgeClase,
        'badge_bg' => $badgeBg,
        'badge_color' => $badgeColor,
        'badge_border' => $badgeBorder,
        'badge_icono' => $badgeIcono,
        'fecha_envio' => $revision['fecha_envio'] ?? null,
        'usuario_envio' => $revision['usuario_envio'] ?? null,
        'fecha_revision' => $revision['fecha_revision'] ?? null,
        'usuario_revisor' => $revision['usuario_revisor'] ?? null,
        'motivo_devolucion' => $revision['motivo_devolucion'] ?? null,
        'puede_enviar_cliente' => $puedeEnviar,
        'puede_aprobar' => $puedeAprobar,
        'puede_devolver' => $puedeDevolver,
        'tiene_resultados' => true,
        'historial' => $revision['historial'] ?? []
    ];
}

/**
 * Devuelve la columna que contiene el código oficial de la muestra.
 */
function obtenerColumnaCodigoMuestra(array $fila): ?string {
    foreach (['Código laboratorio', 'Codigo laboratorio', 'codigo_lab', 'codigo_muestra'] as $columna) {
        if (array_key_exists($columna, $fila)) {
            return $columna;
        }
    }

    return null;
}

/**
 * Indica si una fila corresponde a una réplica interna de Control de Calidad.
 */
function esFilaReplicaMatriz(array $fila): bool {
    if (!empty($fila['_es_replica'])) {
        return true;
    }
    $columna = obtenerColumnaCodigoMuestra($fila);
    $codigo = $columna !== null ? trim((string)($fila[$columna] ?? '')) : '';
    return $codigo !== '' && preg_match('/-CR$/i', $codigo) === 1;
}

/**
 * Filtra las réplicas internas para documentos, PDF y comunicaciones al cliente.
 */
function filtrarFilasPublicasMatriz(array $filas): array {
    return array_values(array_filter($filas, static function ($fila): bool {
        return is_array($fila) && !esFilaReplicaMatriz($fila);
    }));
}

/**
 * Normaliza las filas de réplica de una matriz.
 * Solo admite una réplica por muestra original y fuerza el sufijo oficial -CR.
 */
function normalizarReplicasMatriz(array $filas): array {
    $codigosOriginales = [];
    foreach ($filas as $fila) {
        if (!is_array($fila)) {
            continue;
        }
        $columna = obtenerColumnaCodigoMuestra($fila);
        $codigo = $columna !== null ? trim((string)($fila[$columna] ?? '')) : '';
        $esReplica = esFilaReplicaMatriz($fila);
        if ($codigo !== '' && !$esReplica) {
            $codigosOriginales[strtoupper($codigo)] = $codigo;
        }
    }

    $resultado = [];
    $replicasAgregadas = [];
    foreach ($filas as $fila) {
        if (!is_array($fila)) {
            continue;
        }

        $columna = obtenerColumnaCodigoMuestra($fila);
        $codigo = $columna !== null ? trim((string)($fila[$columna] ?? '')) : '';
        $esReplica = esFilaReplicaMatriz($fila);
        if (!$esReplica) {
            unset($fila['_es_replica'], $fila['_muestra_origen']);
            $resultado[] = $fila;
            continue;
        }

        $origen = trim((string)($fila['_muestra_origen'] ?? preg_replace('/-CR$/i', '', $codigo)));
        $origenKey = strtoupper($origen);
        if ($columna === null || $origen === '' || !isset($codigosOriginales[$origenKey]) || isset($replicasAgregadas[$origenKey])) {
            continue;
        }

        $origenCanonico = $codigosOriginales[$origenKey];
        $fila[$columna] = $origenCanonico . '-CR';
        $fila['_es_replica'] = true;
        $fila['_muestra_origen'] = $origenCanonico;
        $resultado[] = $fila;
        $replicasAgregadas[$origenKey] = true;
    }

    return $resultado;
}

/**
 * Agrupa las filas normales y sus réplicas para el tablero de Control de Calidad.
 */
function obtenerParesControlCalidad(array $filas): array {
    $originales = [];
    $replicas = [];

    foreach (normalizarReplicasMatriz($filas) as $fila) {
        $columna = obtenerColumnaCodigoMuestra($fila);
        if ($columna === null) {
            continue;
        }
        $codigo = trim((string)($fila[$columna] ?? ''));
        if ($codigo === '') {
            continue;
        }
        if (!empty($fila['_es_replica'])) {
            $replicas[strtoupper((string)$fila['_muestra_origen'])] = $fila;
        } else {
            $originales[strtoupper($codigo)] = $fila;
        }
    }

    $pares = [];
    foreach ($replicas as $origenKey => $replica) {
        if (!isset($originales[$origenKey])) {
            continue;
        }
        $original = $originales[$origenKey];
        $columna = obtenerColumnaCodigoMuestra($original);
        $diferencias = [];
        foreach ($original as $campo => $valorOriginal) {
            if ($campo === $columna || str_starts_with((string)$campo, '_') || !array_key_exists($campo, $replica)) {
                continue;
            }
            $valorReplica = $replica[$campo];
            if (is_numeric($valorOriginal) && is_numeric($valorReplica) && (string)$valorOriginal !== '' && (string)$valorReplica !== '') {
                $base = (float)$valorOriginal;
                $rep = (float)$valorReplica;
                $diferencias[$campo] = [
                    'original' => $base,
                    'replica' => $rep,
                    'diferencia' => $rep - $base,
                    'diferencia_porcentual' => abs($base) > 0.0000001 ? (($rep - $base) / abs($base)) * 100 : null,
                ];
            }
        }
        $pares[] = [
            'codigo_original' => $original[$columna],
            'codigo_replica' => $replica[$columna],
            'original' => $original,
            'replica' => $replica,
            'diferencias' => $diferencias,
        ];
    }

    return $pares;
}

/**
 * Determina el prefijo oficial de muestra según la Orden de Servicio de CYCSA:
 * - 'MC': Muestra en Campo (requiere salida de campo / envío de técnicos).
 * - 'MS': Muestra en Sede (el cliente trae las muestras al laboratorio).
 *
 * @param array|object|null $os O datos que contengan 'requiere_muestreo' y/o 'tecnico_muestreo'
 * @return string 'MC' o 'MS'
 */
function determinarPrefijoMuestraOS($os): string {
    if (is_array($os)) {
        $req = $os['requiere_muestreo'] ?? null;
        $tec = trim($os['tecnico_muestreo'] ?? '');
    } elseif (is_object($os)) {
        $req = $os->requiere_muestreo ?? null;
        $tec = trim($os->tecnico_muestreo ?? '');
    } else {
        return 'MS';
    }

    $esCampo = ($req === 1 || $req === '1' || $req === true || !empty($tec));
    return $esCampo ? 'MC' : 'MS';
}
