<?php

declare(strict_types=1);

namespace Cycsa\Modulos\Cotizaciones\Servicios;

/**
 * Servicio unificado de renderizado del formato oficial CYCSA-RG-FM-31 V2R1
 * (Cotización de Servicios). Garantiza que la vista en pantalla (detalle.php)
 * y la salida PDF (Dompdf) compartan exactamente la misma estructura, estilos
 * y contenido.
 */
class DocumentoCotizacionRgFm31Servicio
{
    public const CODIGO_DOCUMENTO = 'CYCSA-RG-FM-31 V2R1';
    public const RUC_EMPRESA = 'J0310000073465';

    /**
     * Normaliza el campo `contactos` de la cotización a una lista uniforme de
     * filas `['nombre' => string, 'telefono' => string, 'correo' => string]`.
     * Soporta tanto JSON estructurado como texto plano heredado y aplica un
     * respaldo automático con los datos del cliente cuando está vacío.
     *
     * @param array<string, mixed> $cotizacion
     * @return array<int, array{nombre: string, telefono: string, correo: string}>
     */
    public static function normalizarContactos(array $cotizacion): array
    {
        $raw = trim((string)($cotizacion['contactos'] ?? ''));
        $contactos = [];

        if ($raw !== '') {
            $decoded = json_decode($raw, true);
            if (json_last_error() === JSON_ERROR_NONE && is_array($decoded)) {
                foreach ($decoded as $item) {
                    if (!is_array($item)) {
                        continue;
                    }
                    $nombre = trim((string)($item['nombre'] ?? ''));
                    $telefono = trim((string)($item['telefono'] ?? ''));
                    $correo = trim((string)($item['correo'] ?? ($item['email'] ?? '')));
                    if ($nombre !== '' || $telefono !== '' || $correo !== '') {
                        $contactos[] = [
                            'nombre' => $nombre !== '' ? $nombre : '—',
                            'telefono' => $telefono !== '' ? $telefono : '—',
                            'correo' => $correo !== '' ? $correo : '—',
                        ];
                    }
                }
                if ($contactos !== []) {
                    return $contactos;
                }
            }

            // Soporte flexible para texto plano heredado
            $lineas = preg_split('/\r\n|\r|\n/', $raw) ?: [];
            $actual = null;

            foreach ($lineas as $linea) {
                $linea = trim($linea);
                if ($linea === '') {
                    continue;
                }

                // Formato delimitado por | o ;
                if (str_contains($linea, '|') || str_contains($linea, ';')) {
                    if ($actual !== null) {
                        $contactos[] = $actual;
                        $actual = null;
                    }
                    $sep = str_contains($linea, '|') ? '|' : ';';
                    $partes = array_map('trim', explode($sep, $linea));
                    $nombre = $partes[0] ?? '';
                    $telefono = $partes[1] ?? '';
                    $correo = $partes[2] ?? '';
                    if ($nombre !== '' || $telefono !== '' || $correo !== '') {
                        $contactos[] = [
                            'nombre' => $nombre !== '' ? $nombre : '—',
                            'telefono' => $telefono !== '' ? $telefono : '—',
                            'correo' => $correo !== '' ? $correo : '—',
                        ];
                    }
                    continue;
                }

                // Extraer correo electrónico si existe en la línea
                $correoEncontrado = '';
                if (preg_match('/[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}/', $linea, $mMail)) {
                    $correoEncontrado = trim($mMail[0]);
                }

                // Extraer teléfono si existe en la línea
                $telEncontrado = '';
                $lineaSinCorreo = $correoEncontrado !== '' ? str_replace($correoEncontrado, '', $linea) : $linea;
                if (preg_match('/(?:\+?\d[\d\s\-().]{6,}\d)/', $lineaSinCorreo, $mTel)) {
                    $telEncontrado = trim($mTel[0]);
                }

                // Detectar línea de atributo para el contacto actual (Tel:, Correo:, Email:)
                $esLineaCorreo = (bool)preg_match('/^(?:correo(?:\s+electr[oó]nico)?|e-?mail)\s*[:\-]/iu', $linea);
                $esLineaTel = (bool)preg_match('/^(?:tel(?:[eé]fono)?|cel(?:ular)?|m[oó]vil|whatsapp)\s*[:\-]/iu', $linea);

                if ($esLineaCorreo && $actual !== null) {
                    $val = $correoEncontrado !== '' ? $correoEncontrado : trim((string)preg_replace('/^(?:correo(?:\s+electr[oó]nico)?|e-?mail)\s*[:\-]\s*/iu', '', $linea));
                    if ($val !== '') {
                        $actual['correo'] = $val;
                    }
                    continue;
                }

                if ($esLineaTel && $actual !== null) {
                    $val = $telEncontrado !== '' ? $telEncontrado : trim((string)preg_replace('/^(?:tel(?:[eé]fono)?|cel(?:ular)?|m[oó]vil|whatsapp)\s*[:\-]\s*/iu', '', $linea));
                    if ($val !== '') {
                        $actual['telefono'] = $val;
                    }
                    continue;
                }

                // Si la línea trae solo correo o solo teléfono y ya hay un contacto abierto sin ese dato
                $textoRestante = trim(str_replace([$correoEncontrado, $telEncontrado], '', $linea));
                $textoRestante = trim((string)preg_replace('/^(?:contacto\s*\d*\s*[:\-]\s*|nombre\s*[:\-]\s*)/iu', '', $textoRestante));
                $textoRestante = trim($textoRestante, " \t\n\r\0\x0B,-:/|");

                if ($textoRestante === '' && $actual !== null) {
                    if ($correoEncontrado !== '' && $actual['correo'] === '') {
                        $actual['correo'] = $correoEncontrado;
                    }
                    if ($telEncontrado !== '' && $actual['telefono'] === '') {
                        $actual['telefono'] = $telEncontrado;
                    }
                    continue;
                }

                if ($actual !== null) {
                    $contactos[] = $actual;
                }

                $actual = [
                    'nombre' => $textoRestante !== '' ? $textoRestante : $linea,
                    'telefono' => $telEncontrado,
                    'correo' => $correoEncontrado,
                ];
            }

            if ($actual !== null) {
                $contactos[] = $actual;
            }

            if ($contactos !== []) {
                return array_map(static function (array $c): array {
                    return [
                        'nombre' => $c['nombre'] !== '' ? $c['nombre'] : '—',
                        'telefono' => $c['telefono'] !== '' ? $c['telefono'] : '—',
                        'correo' => $c['correo'] !== '' ? $c['correo'] : '—',
                    ];
                }, $contactos);
            }
        }

        // Respaldo automático con los datos principales del cliente
        $nombreFallback = trim((string)($cotizacion['atencion_a'] ?? ''));
        if ($nombreFallback === '') {
            $nombreFallback = trim((string)($cotizacion['cliente_nombre'] ?? ''));
        }
        $telFallback = trim((string)($cotizacion['cliente_tel'] ?? ($cotizacion['telefono'] ?? '')));
        $correoFallback = trim((string)($cotizacion['cliente_email'] ?? ($cotizacion['email'] ?? '')));

        return [[
            'nombre' => $nombreFallback !== '' ? $nombreFallback : 'N/A',
            'telefono' => $telFallback !== '' ? $telFallback : 'N/A',
            'correo' => $correoFallback !== '' ? $correoFallback : 'N/A',
        ]];
    }

    /**
     * Resuelve el listado final de notas y condiciones de la cotización,
     * respetando los checkboxes guardados en `configuracion_notas`, la política
     * de descuento (Inciso 6.7 e) y posibles notas personalizadas.
     *
     * @param array<string, mixed> $cotizacion
     * @return array<int, string>
     */
    public static function resolverNotas(array $cotizacion): array
    {
        $configNotas = [];
        if (isset($cotizacion['configuracion_notas'])) {
            if (is_array($cotizacion['configuracion_notas'])) {
                $configNotas = $cotizacion['configuracion_notas'];
            } elseif (is_string($cotizacion['configuracion_notas']) && trim($cotizacion['configuracion_notas']) !== '') {
                $dec = json_decode($cotizacion['configuracion_notas'], true);
                if (is_array($dec)) {
                    $configNotas = $dec;
                }
            }
        }

        $autorizador = self::resolverAutorizadorDescuento($cotizacion, $configNotas);

        $catalogoNotas = [
            'digital_pdf' => '***Los informes de ensayo se entregan únicamente en formato digital (.PDF). Serán enviados al correo del contacto designado por el cliente.',
            'no_movilizacion' => '***No incluye movilización por traslado de muestras.',
            'entrega_laboratorio' => '*Cliente toma las muestras y las entrega en Laboratorio CYCSA ubicado Km 83.5 Carretera León Managua.',
            'concreto' => 'Muestreo de Concreto (Cilindros): El cliente deberá entregar los cilindros de concreto debidamente identificados (Nombre, Ubicación, Resistencia, Revenimiento) y de dimensiones estándar CYCSA-PE-07 (4"x8" o 6"x12").',
            'laboratorio_lleno' => 'Condición de Tiempos: Los tiempos de entrega aplican a partir del ingreso de las muestras. La disponibilidad deberá ser consultada al momento de la entrega debido a variaciones en la carga del laboratorio.',
            'trae_muestra' => 'Entrega de Muestras: El cliente traerá las muestras a las instalaciones del Laboratorio CYCSA Km 83.5 Carretera León-Managua.',
            'minimo_muestreo' => 'Programación de Muestreo: Se requiere un cargo mínimo de C$ 4,400.00 más movilización para programar muestreos. Programación con un mínimo de 2 días hábiles de anticipación.',
            'politica_descuento' => "Política de Descuento (Inciso 6.7 e): El descuento aplicado en la presente cotización cuenta con la debida aprobación y ha sido formalmente autorizado por: {$autorizador}.",
        ];

        $activarPoliticaDesc = !empty($configNotas['politica_descuento'])
            || ((float)($cotizacion['descuento'] ?? 0) > 0
                && (!isset($configNotas['politica_descuento']) || (string)$configNotas['politica_descuento'] === '1'));

        if ($activarPoliticaDesc) {
            $configNotas['politica_descuento'] = 1;
        }

        $clavesEstandarActivas = 0;
        foreach (array_keys($catalogoNotas) as $clave) {
            if ($clave === 'politica_descuento') {
                continue;
            }
            if (!empty($configNotas[$clave])) {
                $clavesEstandarActivas++;
            }
        }

        // Si no hay notas estándar guardadas, aplicar las 3 notas oficiales por defecto
        if ($clavesEstandarActivas === 0) {
            $configNotas['digital_pdf'] = 1;
            $configNotas['no_movilizacion'] = 1;
            $configNotas['entrega_laboratorio'] = 1;
        }

        $notasFinales = [];
        foreach ($catalogoNotas as $clave => $texto) {
            if (!empty($configNotas[$clave])) {
                $notasFinales[] = $texto;
            }
        }

        // Soportar notas personalizadas o adicionales si existen
        $camposExtra = [
            $configNotas['notas_personalizadas'] ?? null,
            $configNotas['notas_adicionales'] ?? null,
            $cotizacion['notas_personalizadas'] ?? null,
            $cotizacion['notas_adicionales'] ?? null,
        ];
        foreach ($camposExtra as $extra) {
            if (is_array($extra)) {
                foreach ($extra as $linea) {
                    $linea = trim((string)$linea);
                    if ($linea !== '') {
                        $notasFinales[] = $linea;
                    }
                }
            } elseif (is_string($extra) && trim($extra) !== '') {
                $lineas = preg_split('/\r\n|\r|\n/', trim($extra)) ?: [];
                foreach ($lineas as $linea) {
                    $linea = trim($linea);
                    if ($linea !== '') {
                        $notasFinales[] = $linea;
                    }
                }
            }
        }

        return $notasFinales;
    }

    /**
     * Resuelve el responsable de autorización de descuento según la Política 6.7 e.
     *
     * @param array<string, mixed> $cotizacion
     * @param array<string, mixed> $configNotas
     */
    public static function resolverAutorizadorDescuento(array $cotizacion, array $configNotas = []): string
    {
        if ($configNotas === [] && !empty($cotizacion['configuracion_notas'])) {
            $dec = is_array($cotizacion['configuracion_notas'])
                ? $cotizacion['configuracion_notas']
                : json_decode((string)$cotizacion['configuracion_notas'], true);
            if (is_array($dec)) {
                $configNotas = $dec;
            }
        }

        $autorizador = !empty($configNotas['descuento_autorizado_por'])
            ? trim((string)$configNotas['descuento_autorizado_por'])
            : '';

        if ($autorizador === '') {
            $montoDesc = (float)($cotizacion['descuento'] ?? 0);
            $subtotal = (float)($cotizacion['subtotal'] ?? 0);
            $pct = ($subtotal > 0) ? ($montoDesc / $subtotal) * 100 : 0;
            if ($pct > 10) {
                $autorizador = 'Gerencia General';
            } elseif ($pct > 5) {
                $autorizador = 'Gerencia Administrativa';
            } else {
                $autorizador = 'Política Comercial (Automático)';
            }
        }

        if ($autorizador === 'Otro' && !empty($configNotas['descuento_autorizado_otro'])) {
            $autorizador = trim((string)$configNotas['descuento_autorizado_otro']);
        }

        return $autorizador;
    }

    /**
     * Retorna las reglas CSS unificadas (`rgfm31-*`) compatibles tanto con
     * navegadores modernos como con Dompdf.
     */
    public static function renderEstilos(bool $esPdf = false): string
    {
        $fontBase = $esPdf ? '8.5px' : '13px';
        $fontSmall = $esPdf ? '7.5px' : '11.5px';
        $fontBanner = $esPdf ? '8.5px' : '12px';
        $fontTitle = $esPdf ? '12.5px' : '17px';
        $fontCompany = $esPdf ? '10.5px' : '15px';
        $padCell = $esPdf ? '3px 5px' : '7px 10px';
        $padItem = $esPdf ? '4px 4px' : '8px 9px';
        $padBanner = $esPdf ? '3.5px 7px' : '6px 12px';
        $logoHeight = $esPdf ? '46px' : '58px';
        $qrSize = $esPdf ? '46px' : '56px';
        $sectionGap = $esPdf ? '7px' : '16px';

        return <<<CSS
.rgfm31-doc {
    font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif;
    color: #0f172a;
    font-size: {$fontBase};
    line-height: 1.38;
    width: 100%;
    background: #ffffff;
}
.rgfm31-doc table {
    width: 100%;
    border-collapse: collapse;
}
.rgfm31-header-table {
    border: 1.5px solid #103487;
    margin-bottom: {$sectionGap};
}
.rgfm31-header-table td {
    padding: {$padCell};
    vertical-align: middle;
    border: 1px solid #cbd5e1;
}
.rgfm31-logo-cell {
    width: 22%;
    text-align: center;
}
.rgfm31-logo-img {
    max-height: {$logoHeight};
    max-width: 100%;
    width: auto;
    display: inline-block;
}
.rgfm31-title-cell {
    width: 54%;
    text-align: center;
}
.rgfm31-company-name {
    font-size: {$fontCompany};
    font-weight: 700;
    color: #103487;
    letter-spacing: 0.3px;
    line-height: 1.2;
}
.rgfm31-company-ruc {
    font-size: {$fontSmall};
    color: #475569;
    font-weight: 600;
    margin-top: 2px;
}
.rgfm31-doc-title {
    font-size: {$fontTitle};
    font-weight: 700;
    color: #103487;
    text-transform: uppercase;
    letter-spacing: 0.6px;
    margin-top: 5px;
    padding-top: 4px;
    border-top: 1px solid #cbd5e1;
}
.rgfm31-code-cell {
    width: 24%;
    text-align: center;
    background-color: #f8fafc;
}
.rgfm31-code-label {
    font-size: {$fontSmall};
    color: #475569;
    font-weight: 600;
    display: block;
    margin-bottom: 2px;
}
.rgfm31-code-value {
    font-size: {$fontBase};
    font-weight: 700;
    color: #103487;
    display: block;
}
.rgfm31-banner {
    background-color: #103487;
    color: #ffffff;
    font-size: {$fontBanner};
    font-weight: 700;
    text-transform: uppercase;
    letter-spacing: 0.5px;
    padding: {$padBanner};
    border: 1px solid #103487;
}
.rgfm31-section {
    margin-bottom: {$sectionGap};
}
.rgfm31-no-split {
    page-break-inside: avoid;
}
.rgfm31-meta-table td {
    border: 1px solid #cbd5e1;
    padding: {$padCell};
    vertical-align: top;
    font-size: {$fontBase};
}
.rgfm31-meta-k {
    background-color: #f1f5f9;
    color: #1e293b;
    font-weight: 700;
    width: 18%;
    white-space: nowrap;
}
.rgfm31-meta-v {
    color: #0f172a;
    width: 32%;
    word-wrap: break-word;
    overflow-wrap: break-word;
}
.rgfm31-items-table {
    border: 1px solid #cbd5e1;
}
.rgfm31-items-table thead {
    display: table-header-group;
}
.rgfm31-items-table th {
    background-color: #e8eef9;
    color: #103487;
    font-weight: 700;
    font-size: {$fontSmall};
    border: 1px solid #cbd5e1;
    padding: {$padItem};
    text-align: left;
    vertical-align: middle;
}
.rgfm31-items-table td {
    border: 1px solid #cbd5e1;
    padding: {$padItem};
    vertical-align: top;
    font-size: {$fontBase};
    word-wrap: break-word;
    overflow-wrap: break-word;
    white-space: normal;
}
.rgfm31-item-desc {
    font-weight: 700;
    color: #0f172a;
    white-space: normal;
    word-wrap: break-word;
    overflow-wrap: anywhere;
}
.rgfm31-item-extra {
    margin-top: 3px;
    font-size: {$fontSmall};
    color: #334155;
    line-height: 1.35;
    white-space: normal;
    word-wrap: break-word;
    overflow-wrap: anywhere;
}
.rgfm31-item-code {
    display: inline-block;
    margin-top: 3px;
    font-family: monospace;
    font-size: {$fontSmall};
    color: #475569;
    background-color: #f1f5f9;
    padding: 1px 4px;
    border-radius: 2px;
}
.rgfm31-method {
    font-family: monospace;
    color: #103487;
    font-weight: 600;
    font-size: {$fontSmall};
}
.rgfm31-summary-table {
    border: 1px solid #cbd5e1;
    border-top: none;
}
.rgfm31-summary-table td {
    vertical-align: top;
}
.rgfm31-bank-cell {
    width: 60%;
    padding: {$padCell};
    border-right: 1px solid #cbd5e1;
    font-size: {$fontSmall};
    color: #334155;
    line-height: 1.4;
}
.rgfm31-bank-title {
    font-weight: 700;
    color: #103487;
    text-transform: uppercase;
    font-size: {$fontSmall};
    margin-bottom: 3px;
}
.rgfm31-qr-img {
    width: {$qrSize};
    height: {$qrSize};
    border: 1px solid #cbd5e1;
    padding: 2px;
    background: #ffffff;
}
.rgfm31-totals-cell {
    width: 40%;
    padding: 0;
}
.rgfm31-totals-inner td {
    padding: {$padCell};
    border-bottom: 1px solid #e2e8f0;
    font-size: {$fontBase};
}
.rgfm31-totals-inner tr:last-child td {
    border-bottom: none;
}
.rgfm31-totals-label {
    text-align: right;
    color: #475569;
    font-weight: 600;
}
.rgfm31-totals-val {
    text-align: right;
    color: #0f172a;
    font-weight: 700;
    width: 44%;
    white-space: nowrap;
}
.rgfm31-totals-discount td {
    color: #b91c1c;
}
.rgfm31-totals-grand td {
    background-color: #e8eef9;
    color: #103487;
    font-weight: 700;
    border-top: 1.5px solid #103487;
}
.rgfm31-cond-table th {
    background-color: #f1f5f9;
    color: #103487;
    font-weight: 700;
    font-size: {$fontSmall};
    text-align: left;
    border: 1px solid #cbd5e1;
    padding: {$padCell};
}
.rgfm31-cond-table td {
    border: 1px solid #cbd5e1;
    padding: {$padCell};
    font-size: {$fontBase};
    color: #0f172a;
    font-weight: 600;
    width: 33.33%;
}
.rgfm31-notes-box {
    border: 1px solid #cbd5e1;
    border-top: none;
    padding: {$padCell};
    background-color: #ffffff;
}
.rgfm31-notes-list {
    margin: 0;
    padding-left: 16px;
    font-size: {$fontSmall};
    color: #334155;
    line-height: 1.45;
}
.rgfm31-notes-list li {
    margin-bottom: 3px;
}
.rgfm31-contacts-table th {
    background-color: #f1f5f9;
    color: #103487;
    font-weight: 700;
    font-size: {$fontSmall};
    text-align: left;
    border: 1px solid #cbd5e1;
    padding: {$padCell};
}
.rgfm31-contacts-table td {
    border: 1px solid #cbd5e1;
    padding: {$padCell};
    font-size: {$fontBase};
    color: #0f172a;
}
.rgfm31-sign-table {
    margin-top: {$sectionGap};
    margin-bottom: {$sectionGap};
}
.rgfm31-sign-table td {
    width: 46%;
    text-align: center;
    vertical-align: bottom;
    padding: {$padCell};
    font-size: {$fontSmall};
    color: #334155;
}
.rgfm31-sign-line {
    border-top: 1px solid #94a3b8;
    width: 86%;
    margin: 14px auto 0 auto;
    padding-top: 4px;
}
.rgfm31-footer-table {
    border-top: 2px solid #103487;
    margin-top: {$sectionGap};
}
.rgfm31-footer-table td {
    padding: 5px 4px 2px 4px;
    font-size: {$fontSmall};
    color: #475569;
    vertical-align: top;
}
.rgfm31-text-center { text-align: center !important; }
.rgfm31-text-right { text-align: right !important; }
.rgfm31-muted { color: #64748b; font-style: italic; }
CSS;
    }

    /**
     * Alias compatible de `renderCuerpo()` para integraciones existentes.
     *
     * @param array<string, mixed> $cotizacion
     * @param array<int, array<string, mixed>> $detalles
     */
    public static function renderHtmlCuerpo(
        array $cotizacion,
        array $detalles,
        bool $esPdf = false,
        ?string $qrBase64 = null
    ): string {
        return self::renderCuerpo($cotizacion, $detalles, $esPdf, $qrBase64);
    }

    /**
     * Renderiza el cuerpo HTML oficial completo del documento `CYCSA-RG-FM-31 V2R1`
     * usando tablas HTML puras 100% compatibles con navegador y Dompdf.
     *
     * @param array<string, mixed> $cotizacion
     * @param array<int, array<string, mixed>> $detalles
     */
    public static function renderCuerpo(
        array $cotizacion,
        array $detalles,
        bool $esPdf = false,
        ?string $qrBase64 = null
    ): string {
        $esc = static fn (mixed $v): string => htmlspecialchars((string)$v, ENT_QUOTES, 'UTF-8');
        $escMultilinea = static function (?string $v): string {
            if ($v === null || $v === '') {
                return '';
            }
            return nl2br(htmlspecialchars($v, ENT_QUOTES, 'UTF-8'));
        };

        // 1. Logo institucional
        $logoSrc = self::resolverLogoSrc($esPdf);
        $logoImgHtml = $logoSrc !== ''
            ? '<img src="' . $esc($logoSrc) . '" alt="CYCSA" class="rgfm31-logo-img">'
            : '<strong style="font-size: 16px; color: #103487;">CYCSA</strong>';

        // 2. Datos del cliente y cotización
        $codigo = trim((string)($cotizacion['codigo'] ?? ''));
        $versionNum = max(1, (int)($cotizacion['version'] ?? 1));
        $fechaRaw = (string)($cotizacion['fecha_creacion'] ?? '');
        $fechaFmt = $fechaRaw !== '' ? date('d/m/Y', strtotime($fechaRaw)) : date('d/m/Y');

        $clienteNombre = trim((string)($cotizacion['cliente_informe_nombre'] ?? ($cotizacion['cliente_nombre'] ?? '')));
        $clienteRuc = trim((string)($cotizacion['cliente_ruc'] ?? ''));
        $direccionCliente = trim((string)($cotizacion['direccion_cliente'] ?? ''));
        if ($direccionCliente === '') {
            $direccionCliente = trim((string)($cotizacion['cliente_direccion'] ?? ''));
        }
        $atencionA = trim((string)($cotizacion['atencion_a'] ?? ''));
        $proyectoNombre = trim((string)($cotizacion['nombre_proyecto'] ?? ''));
        $proyectoUbicacion = trim((string)($cotizacion['direccion_proyecto'] ?? ''));
        $clienteEmail = trim((string)($cotizacion['cliente_email'] ?? ($cotizacion['email'] ?? '')));
        $clienteTel = trim((string)($cotizacion['cliente_tel'] ?? ($cotizacion['telefono'] ?? '')));
        $creadorNombre = trim((string)($cotizacion['creador_nombre'] ?? '')) ?: 'Asesor Comercial';

        $simbolo = ((int)($cotizacion['tipo_moneda'] ?? 1) === 2) ? 'US$' : 'C$';

        // 3. Filas del detalle de servicios
        $filasHtml = '';
        if ($detalles === []) {
            $filasHtml = '<tr><td colspan="8" class="rgfm31-text-center rgfm31-muted">Sin servicios registrados.</td></tr>';
        } else {
            foreach (array_values($detalles) as $idx => $det) {
                $numLinea = $idx + 1;
                $descPlano = (string)($det['descripcion_ensayo'] ?? ($det['ensayo_nombre'] ?? 'Servicio de Ensayo'));
                $descHtml = '<div class="rgfm31-item-desc">' . $escMultilinea($descPlano) . '</div>';

                $descAdicional = (string)($det['descripcion_adicional'] ?? '');
                if ($descAdicional !== '') {
                    $descHtml .= '<div class="rgfm31-item-extra">' . $escMultilinea($descAdicional) . '</div>';
                }

                $codigoServicio = trim((string)($det['codigo_servicio'] ?? ''));
                if ($codigoServicio !== '') {
                    $descHtml .= '<span class="rgfm31-item-code">' . $esc($codigoServicio) . '</span>';
                }

                $condRaw = trim((string)($det['condiciones_muestra'] ?? ''));
                $condHtml = $condRaw !== ''
                    ? $escMultilinea($condRaw)
                    : '<span class="rgfm31-muted">Estándar</span>';

                $metodo = trim((string)($det['procedimiento'] ?? ''));
                if ($metodo === '') {
                    $metodo = trim((string)($det['norma_astm'] ?? ''));
                }
                if ($metodo === '') {
                    $metodo = 'N/A';
                }

                $unidad = trim((string)($det['unidad_medida'] ?? '')) ?: 'Unidad';
                $cantVal = (float)($det['cantidad'] ?? 0);
                $cantFmt = (floor($cantVal) == $cantVal)
                    ? number_format($cantVal, 0, '.', ',')
                    : rtrim(rtrim(number_format($cantVal, 2, '.', ','), '0'), '.');

                $precioFmt = number_format((float)($det['precio_unitario'] ?? 0), 2, '.', ',');
                $subFmt = number_format((float)($det['subtotal'] ?? 0), 2, '.', ',');

                $filasHtml .= '<tr>'
                    . '<td class="rgfm31-text-center"><strong>' . $numLinea . '</strong></td>'
                    . '<td>' . $descHtml . '</td>'
                    . '<td>' . $condHtml . '</td>'
                    . '<td class="rgfm31-method">' . $esc($metodo) . '</td>'
                    . '<td class="rgfm31-text-center">' . $esc($unidad) . '</td>'
                    . '<td class="rgfm31-text-center"><strong>' . $esc($cantFmt) . '</strong></td>'
                    . '<td class="rgfm31-text-right">' . $esc($simbolo) . ' ' . $precioFmt . '</td>'
                    . '<td class="rgfm31-text-right"><strong>' . $esc($simbolo) . ' ' . $subFmt . '</strong></td>'
                    . '</tr>';
            }
        }

        // 4. Totales y datos bancarios
        $subtotalBruto = (float)($cotizacion['subtotal'] ?? 0);
        $descuentoVal = (float)($cotizacion['descuento'] ?? 0);
        $subtotalNeto = max(0.0, $subtotalBruto - $descuentoVal);
        $impuestoVal = (float)($cotizacion['impuesto'] ?? 0);
        $totalVal = (float)($cotizacion['total'] ?? 0);

        $esExonerado = !empty($cotizacion['exonerado']) && (int)$cotizacion['exonerado'] === 1;
        $exNo = trim((string)($cotizacion['exoneracion_no'] ?? ''));
        $ivaLabel = $esExonerado
            ? ('Impuesto (Exonerado' . ($exNo !== '' ? ' · No. ' . $esc($exNo) : '') . '):')
            : 'Impuesto (IVA 15%):';

        $descuentoClass = $descuentoVal > 0 ? ' class="rgfm31-totals-discount"' : '';
        $descuentoDisplay = $descuentoVal > 0
            ? ('- ' . $esc($simbolo) . ' ' . number_format($descuentoVal, 2, '.', ','))
            : ($esc($simbolo) . ' 0.00');

        // QR compacto opcional
        $qrSrc = $qrBase64;
        if ($qrSrc === null) {
            $qrSrc = self::resolverQrSrc($esPdf);
        }
        $qrBloqueHtml = '';
        if ($qrSrc !== '') {
            $qrBloqueHtml = '<table style="margin-top: 6px; border: none;"><tr>'
                . '<td style="border: none; padding: 0 8px 0 0; vertical-align: middle;">'
                . '<strong style="color: #103487;">Términos y Condiciones:</strong><br>'
                . 'Escanea el código QR para consultar las políticas oficiales de CYCSA.'
                . '</td>'
                . '<td style="border: none; padding: 0; width: 60px; text-align: right; vertical-align: middle;">'
                . '<img src="' . $esc($qrSrc) . '" alt="QR Términos" class="rgfm31-qr-img">'
                . '</td>'
                . '</tr></table>';
        }

        // 5. Condiciones comerciales y modalidad / período de habilitación
        $condicionPago = trim((string)($cotizacion['condicion_pago'] ?? '')) ?: 'A convenir';
        $tiempoEntrega = trim((string)($cotizacion['tiempo_entrega'] ?? '')) ?: 'A convenir';
        $vigenciaOferta = trim((string)($cotizacion['vigencia_oferta'] ?? '')) ?: '30 días calendario';

        $esMensualCot = !empty($cotizacion['es_mensual']) && (int)$cotizacion['es_mensual'] === 1;
        if ($esMensualCot) {
            $durMeses = !empty($cotizacion['duracion_meses']) ? (int)$cotizacion['duracion_meses'] : null;
            $perCot = trim((string)($cotizacion['periodicidad'] ?? 'Mensual')) ?: 'Mensual';
            $partesPer = [$perCot];
            if ($durMeses !== null && $durMeses > 0) {
                $partesPer[] = $durMeses . ($durMeses === 1 ? ' mes' : ' meses');
            }
            $fIniPer = !empty($cotizacion['fecha_inicio_periodo']) ? date('d/m/Y', strtotime((string)$cotizacion['fecha_inicio_periodo'])) : '';
            $fFinPer = !empty($cotizacion['fecha_fin_periodo']) ? date('d/m/Y', strtotime((string)$cotizacion['fecha_fin_periodo'])) : '';
            $rangoPer = ($fIniPer !== '' && $fFinPer !== '') ? " ({$fIniPer} — {$fFinPer})" : '';
            $modalidadPeriodo = implode(' · ', $partesPer) . $rangoPer;
        } else {
            $modalidadPeriodo = 'Puntual (No mensual)';
        }

        // 6. Notas y condiciones
        $notas = self::resolverNotas($cotizacion);
        $notasItemsHtml = '';
        foreach ($notas as $nota) {
            $notasItemsHtml .= '<li>' . $esc($nota) . '</li>';
        }

        // 7. Contactos del cliente para seguimiento
        $contactos = self::normalizarContactos($cotizacion);
        $contactosFilasHtml = '';
        foreach ($contactos as $c) {
            $contactosFilasHtml .= '<tr>'
                . '<td>' . $esc($c['nombre']) . '</td>'
                . '<td>' . $esc($c['telefono']) . '</td>'
                . '<td>' . $esc($c['correo']) . '</td>'
                . '</tr>';
        }

        $codigoVersionHtml = $esc($codigo !== '' ? $codigo : 'BORRADOR') . ' (v' . $versionNum . ')';

        return '<div class="rgfm31-doc">'
            // 1. Encabezado oficial
            . '<table class="rgfm31-header-table">'
            . '<tr>'
            . '<td class="rgfm31-logo-cell">' . $logoImgHtml . '</td>'
            . '<td class="rgfm31-title-cell">'
            . '<div class="rgfm31-company-name">CONSULTORÍA Y CONSTRUCCIÓN S.A</div>'
            . '<div class="rgfm31-company-ruc">RUC: ' . self::RUC_EMPRESA . '</div>'
            . '<div class="rgfm31-doc-title">COTIZACIÓN DE SERVICIOS</div>'
            . '</td>'
            . '<td class="rgfm31-code-cell">'
            . '<span class="rgfm31-code-label">Código de documento:</span>'
            . '<span class="rgfm31-code-value">' . self::CODIGO_DOCUMENTO . '</span>'
            . '</td>'
            . '</tr>'
            . '</table>'

            // 2. Datos del cliente
            . '<div class="rgfm31-section">'
            . '<div class="rgfm31-banner">DATOS DEL CLIENTE</div>'
            . '<table class="rgfm31-meta-table">'
            . '<tr>'
            . '<td class="rgfm31-meta-k">CLIENTE:</td>'
            . '<td class="rgfm31-meta-v"><strong>' . $esc($clienteNombre !== '' ? $clienteNombre : 'N/A') . '</strong></td>'
            . '<td class="rgfm31-meta-k">CÉDULA/RUC:</td>'
            . '<td class="rgfm31-meta-v">' . $esc($clienteRuc !== '' ? $clienteRuc : 'N/A') . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td class="rgfm31-meta-k">DIRECCIÓN:</td>'
            . '<td class="rgfm31-meta-v">' . $esc($direccionCliente !== '' ? $direccionCliente : 'N/A') . '</td>'
            . '<td class="rgfm31-meta-k">COTIZACIÓN NÚMERO:</td>'
            . '<td class="rgfm31-meta-v"><strong>' . $codigoVersionHtml . '</strong></td>'
            . '</tr>'
            . '<tr>'
            . '<td class="rgfm31-meta-k">ATENCIÓN A:</td>'
            . '<td class="rgfm31-meta-v">' . $esc($atencionA !== '' ? $atencionA : 'N/A') . '</td>'
            . '<td class="rgfm31-meta-k">FECHA:</td>'
            . '<td class="rgfm31-meta-v">' . $esc($fechaFmt) . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td class="rgfm31-meta-k">PROYECTO:</td>'
            . '<td class="rgfm31-meta-v"><strong>' . $esc($proyectoNombre !== '' ? $proyectoNombre : 'N/A') . '</strong></td>'
            . '<td class="rgfm31-meta-k">UBICACIÓN DEL PROYECTO:</td>'
            . '<td class="rgfm31-meta-v">' . $esc($proyectoUbicacion !== '' ? $proyectoUbicacion : 'N/A') . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td class="rgfm31-meta-k">CORREO ELECTRÓNICO:</td>'
            . '<td class="rgfm31-meta-v">' . $esc($clienteEmail !== '' ? $clienteEmail : 'N/A') . '</td>'
            . '<td class="rgfm31-meta-k">TELÉFONO:</td>'
            . '<td class="rgfm31-meta-v">' . $esc($clienteTel !== '' ? $clienteTel : 'N/A') . '</td>'
            . '</tr>'
            . '</table>'
            . '</div>'

            // 3. Detalle de servicios cotizados + resumen bancario/totales
            . '<div class="rgfm31-section">'
            . '<div class="rgfm31-banner">DETALLE DE SERVICIOS COTIZADOS</div>'
            . '<table class="rgfm31-items-table">'
            . '<thead>'
            . '<tr>'
            . '<th class="rgfm31-text-center" style="width: 5%;">Línea</th>'
            . '<th style="width: 26%;">Descripción del servicio</th>'
            . '<th style="width: 19%;">Condiciones de muestra</th>'
            . '<th style="width: 13%;">Método de ensayo</th>'
            . '<th class="rgfm31-text-center" style="width: 9%;">Unidad de medida</th>'
            . '<th class="rgfm31-text-center" style="width: 6%;">Cant.</th>'
            . '<th class="rgfm31-text-right" style="width: 11%;">Precio Unitario (' . $esc($simbolo) . ')</th>'
            . '<th class="rgfm31-text-right" style="width: 11%;">Precio Total (' . $esc($simbolo) . ')</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody>' . $filasHtml . '</tbody>'
            . '</table>'

            // Bloque integrado de cuentas bancarias + totales
            . '<table class="rgfm31-summary-table rgfm31-no-split">'
            . '<tr>'
            . '<td class="rgfm31-bank-cell">'
            . '<div class="rgfm31-bank-title">DATOS DE PAGO</div>'
            . '<div>Los pagos deberán realizarse a nombre de Consultoría y Construcción, S.A. (CYCSA), mediante depósito o transferencia bancaria a las siguientes cuentas: '
            . '<strong>BANPRO:</strong> C$ 10010207085164 / US$ 10010210874512; '
            . '<strong>BAC:</strong> C$ 357-02445-4 / US$ 363259490; '
            . '<strong>LAFISE:</strong> C$ 550-2000-11. '
            . '<strong>RUC:</strong> ' . self::RUC_EMPRESA . '.</div>'
            . $qrBloqueHtml
            . '</td>'
            . '<td class="rgfm31-totals-cell">'
            . '<table class="rgfm31-totals-inner">'
            . '<tr>'
            . '<td class="rgfm31-totals-label">Total Bruto:</td>'
            . '<td class="rgfm31-totals-val">' . $esc($simbolo) . ' ' . number_format($subtotalBruto, 2, '.', ',') . '</td>'
            . '</tr>'
            . '<tr' . $descuentoClass . '>'
            . '<td class="rgfm31-totals-label">Descuento:</td>'
            . '<td class="rgfm31-totals-val">' . $descuentoDisplay . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td class="rgfm31-totals-label">Sub-total:</td>'
            . '<td class="rgfm31-totals-val">' . $esc($simbolo) . ' ' . number_format($subtotalNeto, 2, '.', ',') . '</td>'
            . '</tr>'
            . '<tr>'
            . '<td class="rgfm31-totals-label">' . $ivaLabel . '</td>'
            . '<td class="rgfm31-totals-val">' . $esc($simbolo) . ' ' . number_format($impuestoVal, 2, '.', ',') . '</td>'
            . '</tr>'
            . '<tr class="rgfm31-totals-grand">'
            . '<td class="rgfm31-totals-label">Total:</td>'
            . '<td class="rgfm31-totals-val">' . $esc($simbolo) . ' ' . number_format($totalVal, 2, '.', ',') . '</td>'
            . '</tr>'
            . '</table>'
            . '</td>'
            . '</tr>'
            . '</table>'
            . '</div>'

            // 4. Condiciones comerciales
            . '<div class="rgfm31-section rgfm31-no-split">'
            . '<div class="rgfm31-banner">CONDICIONES COMERCIALES</div>'
            . '<table class="rgfm31-cond-table">'
            . '<thead>'
            . '<tr>'
            . '<th>Condición de Pago</th>'
            . '<th>Tiempo de entrega</th>'
            . '<th>Vigencia de la oferta</th>'
            . '<th>Modalidad / Período habilitado</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody>'
            . '<tr>'
            . '<td>' . $esc($condicionPago) . '</td>'
            . '<td>' . $esc($tiempoEntrega) . '</td>'
            . '<td>' . $esc($vigenciaOferta) . '</td>'
            . '<td><strong>' . $esc($modalidadPeriodo) . '</strong></td>'
            . '</tr>'
            . '</tbody>'
            . '</table>'
            . '</div>'

            // 5. Notas y condiciones de la cotización
            . '<div class="rgfm31-section rgfm31-no-split">'
            . '<div class="rgfm31-banner">NOTAS Y CONDICIONES DE LA COTIZACIÓN</div>'
            . '<div class="rgfm31-notes-box">'
            . '<ul class="rgfm31-notes-list">' . $notasItemsHtml . '</ul>'
            . '</div>'
            . '</div>'

            // 6. Contactos del cliente para seguimiento
            . '<div class="rgfm31-section rgfm31-no-split">'
            . '<div class="rgfm31-banner">CONTACTOS DEL CLIENTE PARA SEGUIMIENTO</div>'
            . '<table class="rgfm31-contacts-table">'
            . '<thead>'
            . '<tr>'
            . '<th style="width: 40%;">Nombre</th>'
            . '<th style="width: 25%;">Teléfono</th>'
            . '<th style="width: 35%;">Correo electrónico</th>'
            . '</tr>'
            . '</thead>'
            . '<tbody>' . $contactosFilasHtml . '</tbody>'
            . '</table>'
            . '</div>'

            // Bloque de elaboración / aceptación
            . '<table class="rgfm31-sign-table rgfm31-no-split">'
            . '<tr>'
            . '<td>'
            . '<div class="rgfm31-sign-line">'
            . '<strong>ELABORADO POR:</strong> ' . $esc($creadorNombre) . '<br>'
            . 'Consultoría y Construcción, S.A. (CYCSA)'
            . '</div>'
            . '</td>'
            . '<td style="width: 8%;"></td>'
            . '<td>'
            . '<div class="rgfm31-sign-line">'
            . '<strong>ACEPTADO POR EL CLIENTE:</strong><br>'
            . 'Firma / Sello Autorizado · Fecha: ____/____/______'
            . '</div>'
            . '</td>'
            . '</tr>'
            . '</table>'

            // 7. Pie de página institucional CYCSA
            . '<table class="rgfm31-footer-table rgfm31-no-split">'
            . '<tr>'
            . '<td style="width: 38%;">Km 83.5 Carretera León - Managua<br>León, Nicaragua</td>'
            . '<td style="width: 34%;" class="rgfm31-text-center">Tel.: (505) 8209-6275 / 2311-5694<br>admon@cycsanic.com</td>'
            . '<td style="width: 28%;" class="rgfm31-text-right"><strong>Lic. MTI No. 6693</strong><br>www.cycsanic.com</td>'
            . '</tr>'
            . '</table>'
            . '</div>';
    }

    private static function resolverLogoSrc(bool $esPdf): string
    {
        $root = dirname(__DIR__, 4);
        $logoPng = $root . '/publico/img/logo.png';
        $logoJpg = $root . '/publico/img/logo_cycsa.jpg';

        if (!$esPdf) {
            if (is_file($logoPng)) {
                return '/Cycsa/publico/img/logo.png';
            }
            if (is_file($logoJpg)) {
                return '/Cycsa/publico/img/logo_cycsa.jpg';
            }
            return '';
        }

        if (is_file($logoPng)) {
            $bin = @file_get_contents($logoPng);
            if ($bin !== false && $bin !== '') {
                return 'data:image/png;base64,' . base64_encode($bin);
            }
        }
        if (is_file($logoJpg)) {
            $bin = @file_get_contents($logoJpg);
            if ($bin !== false && $bin !== '') {
                return 'data:image/jpeg;base64,' . base64_encode($bin);
            }
        }
        return '';
    }

    private static function resolverQrSrc(bool $esPdf): string
    {
        $root = dirname(__DIR__, 4);
        $qrPng = $root . '/publico/img/qr_terminos.png';
        if (!is_file($qrPng)) {
            return '';
        }
        if (!$esPdf) {
            return '/Cycsa/publico/img/qr_terminos.png';
        }
        $bin = @file_get_contents($qrPng);
        return ($bin !== false && $bin !== '') ? ('data:image/png;base64,' . base64_encode($bin)) : '';
    }
}
