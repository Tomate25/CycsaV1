<?php
// Vista oficial del documento ORDEN DE SERVICIO (CYCSA-RG-FM-39 V1)
$esModoPdfRgFm39 = !empty($esModoPdfRgFm39);
$contactos = [];
if (!empty($os['contactos_json'])) {
    $contactos = is_array($os['contactos_json']) ? $os['contactos_json'] : (json_decode($os['contactos_json'], true) ?: []);
}

// Resolver logo oficial en base64 para garantizar fidelidad 100% en Dompdf y pantalla
$logoSrcRgFm39 = '/Cycsa/publico/img/logo.png';
if ($esModoPdfRgFm39) {
    $candidatosLogo = [
        dirname(__DIR__, 4) . '/publico/img/logo.png',
        dirname(__DIR__, 4) . '/publico/img/logo_cycsa_rt_fm_13.png',
        dirname(__DIR__, 4) . '/publico/img/logo_cycsa.jpg',
    ];
    foreach ($candidatosLogo as $rutaLogo) {
        if (is_file($rutaLogo) && is_readable($rutaLogo)) {
            $extLogo = strtolower(pathinfo($rutaLogo, PATHINFO_EXTENSION));
            $mimeLogo = $extLogo === 'jpg' || $extLogo === 'jpeg' ? 'image/jpeg' : 'image/png';
            $bytesLogo = @file_get_contents($rutaLogo);
            if ($bytesLogo !== false && $bytesLogo !== '') {
                $logoSrcRgFm39 = 'data:' . $mimeLogo . ';base64,' . base64_encode($bytesLogo);
                break;
            }
        }
    }
}

// Preparar filas de metadatos emparejadas en 2 columnas (100% compatible con navegador y Dompdf)
$metaItemsRgFm39 = [
    ['label' => 'Elaborado por:', 'value' => htmlspecialchars($os['elaborado_por'] ?: (($os['cotizacion_creador_nombre'] ?? '') ?: 'Personal Autorizado'), ENT_QUOTES, 'UTF-8')],
    ['label' => 'Doc. Número:', 'value' => htmlspecialchars((string)($os['codigo_os'] ?? ''), ENT_QUOTES, 'UTF-8')],
    ['label' => 'Cliente (Informe):', 'value' => htmlspecialchars((string)($os['cliente_informe_nombre'] ?: ($os['cliente_nombre'] ?? '')), ENT_QUOTES, 'UTF-8')],
    ['label' => 'Fecha:', 'value' => !empty($os['fecha_emision']) ? date('Y-m-d', strtotime($os['fecha_emision'])) : date('Y-m-d')],
];
if (!empty($os['cliente_factura_nombre']) && $os['cliente_factura_nombre'] !== ($os['cliente_informe_nombre'] ?: $os['cliente_nombre'])) {
    $metaItemsRgFm39[] = [
        'label' => 'Cliente (Pagador):',
        'value' => '<span style="color:#103487; font-weight:700;">' . htmlspecialchars((string)$os['cliente_factura_nombre'], ENT_QUOTES, 'UTF-8') . '</span>',
        'raw' => true,
    ];
}
$metaItemsRgFm39[] = ['label' => 'Atención a:', 'value' => htmlspecialchars((string)($os['atencion_a'] ?: 'N/A'), ENT_QUOTES, 'UTF-8')];
$metaItemsRgFm39[] = ['label' => 'Cédula/RUC:', 'value' => htmlspecialchars((string)($os['cliente_rfc'] ?: 'N/A'), ENT_QUOTES, 'UTF-8')];
$metaItemsRgFm39[] = ['label' => 'Proyecto:', 'value' => htmlspecialchars((string)($os['nombre_proyecto'] ?? ''), ENT_QUOTES, 'UTF-8')];
$metaItemsRgFm39[] = ['label' => 'Forma de pago:', 'value' => htmlspecialchars((string)($os['forma_pago'] ?: 'Pago contra entrega.'), ENT_QUOTES, 'UTF-8')];
$metaItemsRgFm39[] = ['label' => 'Correo electrónico:', 'value' => htmlspecialchars((string)($os['cliente_email'] ?: 'admon@cycsanic.com'), ENT_QUOTES, 'UTF-8')];
$metaItemsRgFm39[] = ['label' => 'Teléfono:', 'value' => htmlspecialchars((string)($os['cliente_telefono'] ?: '(505) 8209-6275'), ENT_QUOTES, 'UTF-8')];
if (!empty($os['es_multimes']) || !empty($os['vigencia_contrato']['es_contrato_largo'])) {
    $txtMod = 'Contrato Largo (' . htmlspecialchars((string)($os['periodicidad'] ?? 'Mensual'), ENT_QUOTES, 'UTF-8')
        . (!empty($os['vigencia_contrato']['duracion_meses']) ? ' · ' . (int)$os['vigencia_contrato']['duracion_meses'] . ' meses' : '') . ')';
    $txtFechasMod = (!empty($os['fecha_inicio']) && !empty($os['fecha_fin']))
        ? ' <span style="font-size:11px; color:#64748b;">(' . date('d/m/Y', strtotime($os['fecha_inicio'])) . ' &mdash; ' . date('d/m/Y', strtotime($os['fecha_fin'])) . ')</span>'
        : '';
    $metaItemsRgFm39[] = [
        'label' => 'Modalidad:',
        'value' => '<span style="background:#e0e7ff; color:#3730a3; padding:1px 7px; border-radius:4px; font-weight:700; font-size:11px; border:1px solid #c7d2fe;">' . $txtMod . '</span>' . $txtFechasMod,
        'raw' => true,
    ];
}
$metaFilasPares = array_chunk($metaItemsRgFm39, 2);
?>
<?php if ($esModoPdfRgFm39): ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Orden de Servicio <?= htmlspecialchars((string)($os['codigo_os'] ?? ''), ENT_QUOTES, 'UTF-8') ?></title>
<?php else: ?>
<link rel="stylesheet" href="/Cycsa/publico/css/cycsa-sobrio.css">
<link rel="stylesheet" href="/Cycsa/publico/css/ordenes-servicio-sobrio.css">
<?php endif; ?>
<style>
    @page { margin: 12mm 14mm; }
    <?php if ($esModoPdfRgFm39): ?>
    body {
        margin: 0;
        padding: 0;
        font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
        color: #111111;
        font-size: 11px;
        line-height: 1.4;
        background: #ffffff;
    }
    <?php endif; ?>
    .os-wrapper { background: #f1f5f9; padding: 18px 20px 40px 20px; min-height: 100vh; }
    .os-container { 
        width: 100%; max-width: 100%; 
        margin: 0; 
        background: white; 
        padding: 30px 42px; 
        border-radius: 8px; 
        box-shadow: 0 4px 18px rgba(0,0,0,0.06); 
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
        color: #111; 
        position: relative;
        line-height: 1.45;
        font-size: 13.5px;
        box-sizing: border-box;
    }
    
    /* Encabezado basado en tabla (100% fiel en pantalla, impresión y Dompdf) */
    .os-header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 18px;
        border-bottom: 2px solid #103487;
    }
    .os-header-table td {
        padding: 0 0 12px 0;
        vertical-align: middle;
        border: 0;
    }
    .os-logo-img {
        height: 58px;
        width: auto;
        display: block;
    }
    .os-header-right { text-align: right; }
    .os-main-title { font-size: 22px; font-weight: 800; color: #103487; letter-spacing: 0.5px; margin: 0; }
    .os-doc-code { font-size: 12.5px; font-weight: 700; color: #333; margin-top: 3px; }
    .os-ver-pill { display: inline-block; margin-left: 6px; background: #e0e7ff; color: #1e40af; border: 1px solid #93c5fd; padding: 1px 8px; border-radius: 10px; font-size: 11px; font-weight: 700; }
    
    /* Rejilla de Metadatos en 2 columnas mediante tabla (100% fiel en Dompdf) */
    .os-meta-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 18px;
        font-size: 13px;
    }
    .os-meta-table td {
        width: 50%;
        padding: 3.5px 14px 3.5px 0;
        vertical-align: top;
        border: 0;
    }
    .os-meta-label { font-weight: 700; color: #000; }
    .os-meta-value { color: #1e293b; font-weight: 400; }

    /* Tabla de Ensayos CYCSA-RG-FM-39 V1 */
    .os-table-border { width: 100%; border-collapse: collapse; margin-bottom: 18px; font-size: 13px; border: 1.5px solid #000; }
    .os-table-border th { background: #ffffff; color: #000; font-weight: 700; text-align: center; padding: 7px 10px; border: 1px solid #000; font-size: 12px; text-transform: uppercase; }
    .os-table-border td { padding: 7px 10px; border: 1px solid #000; font-size: 12.5px; }

    /* Notas y Contactos */
    .os-section-notes { font-size: 12.5px; color: #111; line-height: 1.5; margin-bottom: 22px; }
    .os-section-notes p { margin: 0 0 5px 0; }
    
    .os-contacts-list { margin-top: 8px; margin-bottom: 10px; font-size: 12.5px; }
    .os-contacts-list div { margin-bottom: 3px; }

    /* Firmas Oficiales en 4 columnas mediante tabla (100% fiel en Dompdf) */
    .os-signatures-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 14px 0;
        margin: 42px -14px 18px -14px;
        table-layout: fixed;
    }
    .os-signatures-table td {
        width: 25%;
        border-top: 1.5px solid #000;
        padding: 6px 4px 0 4px;
        text-align: center;
        vertical-align: top;
        font-size: 11px;
        font-weight: 600;
        color: #111;
        line-height: 1.35;
    }

    /* Pie de página Oficial en 3 columnas mediante tabla (100% fiel en 1 sola página en Dompdf) */
    .os-footer-table {
        width: 100%;
        border-collapse: collapse;
        border-top: 2.5px solid #103487;
        margin-top: 22px;
        table-layout: fixed;
    }
    .os-footer-table td {
        padding: 9px 4px 0 4px;
        vertical-align: top;
        font-size: 10.5px;
        color: #103487;
        font-weight: 600;
        line-height: 1.4;
        border: 0;
    }

    .btn-action-bar { 
        width: 100%; max-width: 100%; 
        margin: 0 0 14px 0; 
        display: flex; 
        justify-content: space-between; 
        align-items: center; 
        position: sticky;
        top: 10px;
        z-index: 100;
        background: rgba(241, 245, 249, 0.95);
        backdrop-filter: blur(4px);
        padding: 8px 12px;
        border-radius: 8px;
        border: 1px solid #e2e8f0;
    }
    .os-btn-group-secondary {
        display: inline-flex;
        gap: 8px;
        align-items: center;
    }
    .btn-cycsa { display: inline-flex; align-items: center; gap: 8px; border: 1px solid transparent; min-height: 36px; padding: 7px 16px; border-radius: 6px; font-size: 13px; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .btn-cycsa-primary { background: var(--cycsa-azul); color: white; }
    .btn-cycsa-primary:hover { background: #0c2766; color: white; }
    .btn-cycsa-secondary { background: white; color: #334155; border-color: #cbd5e1; }
    .btn-cycsa-secondary:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }

    /* MANTENER @MEDIA PRINT 100% INTACTO PARA IMPRESIÓN Y PDF */
    @media print {
        .no-print { display: none !important; }
        .os-wrapper { padding: 0 !important; background: transparent !important; min-height: auto !important; }
        .os-container { box-shadow: none !important; border: 0 !important; border-radius: 0 !important; padding: 0 !important; max-width: 100% !important; font-size: 11px !important; background: transparent !important; }
        .os-header-table { margin-bottom: 14px !important; }
        .os-header-table td { padding-bottom: 9px !important; }
        .os-logo-img { height: 52px !important; }
        .os-main-title { font-size: 20px !important; }
        .os-doc-code { font-size: 11.5px !important; }
        .os-meta-table { font-size: 11px !important; margin-bottom: 14px !important; }
        .os-meta-table td { padding: 2.5px 10px 2.5px 0 !important; }
        .os-table-border { font-size: 10.5px !important; margin-bottom: 14px !important; }
        .os-table-border th, .os-table-border td { font-size: 10.5px !important; padding: 5px 8px !important; }
        .os-section-notes { font-size: 10.5px !important; margin-bottom: 16px !important; }
        .os-contacts-list { font-size: 10.5px !important; }
        .os-signatures-table { margin: 38px -10px 14px -10px !important; border-spacing: 10px 0 !important; }
        .os-signatures-table td { font-size: 9.5px !important; }
        .os-footer-table { margin-top: 18px !important; }
        .os-footer-table td { font-size: 9px !important; padding-top: 7px !important; }
        @page { margin: 12mm 14mm; }
    }
</style>
<?php if ($esModoPdfRgFm39): ?>
</head>
<body>
<?php endif; ?>

<?php if (!$esModoPdfRgFm39): ?>
<div class="container-fluid px-0 py-0 cy-os-wrap">
<div class="os-wrapper">
    <!-- BARRA SUPERIOR DE ACCIONES -->
    <div class="btn-action-bar no-print">
        <a href="/Cycsa/publico/ordenes-servicio" class="btn-cycsa btn-cycsa-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver a Órdenes
        </a>
        <div class="os-btn-group-secondary">
            <?php if (!empty($os['hojas_servicio'])): ?>
                <?php $primeraHojaDet = $os['hojas_servicio'][0]; ?>
                <a href="/Cycsa/publico/laboratorio/imprimir-solicitud?id_hoja=<?= (int)$primeraHojaDet['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-secondary" title="Imprimir Hoja RT-FM-60 de Laboratorio">
                    <i class="fa-solid fa-flask"></i> Hoja RT-FM-60
                </a>
            <?php endif; ?>
            <?php if (in_array((int)($_SESSION['usuario_rol'] ?? 0), [1, 2, 3], true) || tienePermiso('operaciones', 'aprobar')): ?>
            <a href="/Cycsa/publico/operaciones?bandeja=supervisor&id_os=<?= (int)($os['id'] ?? 0) ?>" class="btn-cycsa btn-cycsa-secondary" title="Revisión y aprobación técnica unificada del supervisor en Operaciones">
                <i class="fa-solid fa-user-check"></i> Revisión Supervisor (Operaciones)
            </a>
            <?php endif; ?>
            <?php if (!in_array($os['estado'] ?? '', ['Finalizado', 'Archivado', 'Cerrado'], true)): ?>
            <a href="/Cycsa/publico/ordenes-servicio/editar?id_os=<?= (int)($os['id'] ?? 0) ?>" class="btn-cycsa btn-cycsa-secondary">
                <i class="fa-solid fa-pen-to-square"></i> Editar Orden de Servicio
            </a>
            <?php endif; ?>
            <?php if (!empty($mostrar_bitacora)): ?>
            <a href="/Cycsa/publico/ordenes-servicio/bitacora?id=<?= (int)($os['id'] ?? 0) ?>" class="btn-cycsa btn-cycsa-secondary">
                <i class="fa-solid fa-clock-rotate-left"></i> Bitácora
            </a>
            <?php endif; ?>
            <a href="/Cycsa/publico/ordenes-servicio/descargar-pdf?id=<?= (int)($os['id'] ?? 0) ?>" target="_blank" class="btn-cycsa btn-cycsa-secondary">
                <i class="fa-solid fa-file-pdf"></i> Descargar PDF Oficial
            </a>
            <button onclick="window.print()" class="btn-cycsa btn-cycsa-primary">
                <i class="fa-solid fa-print"></i> Imprimir Orden de Servicio
            </button>
        </div>
    </div>

    <!-- CONTENEDOR DEL DOCUMENTO CÓD. DOC CYCSA-RG-FM-39 V1 -->
    <?php
    $alertaAlcanceDet = $os['alerta_alcance_mensual'] ?? [];
    if (!empty($alertaAlcanceDet['tiene_alerta'])):
        $esDangerAlc = ($alertaAlcanceDet['nivel'] ?? 'warning') === 'danger';
    ?>
    <div class="no-print" style="width: 100%; max-width: 100%; margin: 0 0 14px 0; background: <?= $esDangerAlc ? '#fef2f2' : '#fffbeb' ?>; border: 1.5px solid <?= $esDangerAlc ? '#fca5a5' : '#fcd34d' ?>; color: <?= $esDangerAlc ? '#991b1b' : '#92400e' ?>; border-radius: 8px; padding: 12px 16px; font-size: 13px;">
        <div style="font-weight: 700; margin-bottom: 4px;">
            <i class="fa-solid fa-triangle-exclamation"></i> Alerta de Alcance / Vigencia Mensual de la O/S
        </div>
        <ul style="margin: 0; padding-left: 18px;">
            <?php foreach (($alertaAlcanceDet['mensajes'] ?? []) as $msgAlc): ?>
                <li><?= htmlspecialchars((string)$msgAlc, ENT_QUOTES, 'UTF-8') ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
    <?php endif; ?>
<?php endif; ?>
    <div class="os-container">
        <!-- ENCABEZADO OFICIAL -->
        <table class="os-header-table">
            <tr>
                <td style="width: 45%; text-align: left;">
                    <img src="<?= htmlspecialchars($logoSrcRgFm39, ENT_QUOTES, 'UTF-8') ?>" alt="CYCSA" class="os-logo-img"<?= !$esModoPdfRgFm39 ? ' onerror="this.src=\'/Cycsa/publico/img/logo_cycsa_rt_fm_13.png\'; this.onerror=null;"' : '' ?>>
                </td>
                <td style="width: 55%;" class="os-header-right">
                    <h1 class="os-main-title">ORDEN DE SERVICIO</h1>
                    <div class="os-doc-code">Cód. Doc CYCSA-RG-FM-39 V1 <span class="os-ver-pill">v<?= (int)($os['version'] ?? 1) ?></span></div>
                </td>
            </tr>
        </table>

        <!-- METADATOS DE LA ORDEN DE SERVICIO (2 COLUMNAS) -->
        <table class="os-meta-table">
            <?php foreach ($metaFilasPares as $parMeta): ?>
            <tr>
                <td>
                    <span class="os-meta-label"><?= $parMeta[0]['label'] ?></span>
                    <span class="os-meta-value"><?= !empty($parMeta[0]['raw']) ? $parMeta[0]['value'] : $parMeta[0]['value'] ?></span>
                </td>
                <td>
                    <?php if (isset($parMeta[1])): ?>
                        <span class="os-meta-label"><?= $parMeta[1]['label'] ?></span>
                        <span class="os-meta-value"><?= !empty($parMeta[1]['raw']) ? $parMeta[1]['value'] : $parMeta[1]['value'] ?></span>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </table>

        <!-- TABLA DE ENSAYOS / SERVICIOS -->
        <table class="os-table-border">
            <thead>
                <tr>
                    <th style="width: 80px;">Línea</th>
                    <th>Descripción</th>
                    <th style="width: 110px;">Cantidad</th>
                    <th style="width: 190px;">Código laboratorio</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $codigosOs = [];
                foreach (($os['codigos_muestra'] ?? []) as $codigoOs) {
                    $codigoOs = trim((string)$codigoOs);
                    if ($codigoOs !== '') {
                        $codigosOs[$codigoOs] = $codigoOs;
                    }
                }
                $codigosOs = array_values($codigosOs);
                ?>
                <?php if (!empty($os['ensayos'])): ?>
                    <?php foreach ($os['ensayos'] as $idx => $ensayo): ?>
                        <?php
                        if (array_key_exists('permitido', $ensayo) && (int)$ensayo['permitido'] !== 1) {
                            continue;
                        }
                        $codigosEnsayo = [];
                        foreach (($ensayo['codigos_laboratorio'] ?? []) as $codigoEnsayo) {
                            $codigoEnsayo = trim((string)$codigoEnsayo);
                            if ($codigoEnsayo !== '') {
                                $codigosEnsayo[$codigoEnsayo] = $codigoEnsayo;
                            }
                        }
                        $codigosEnsayo = array_values($codigosEnsayo);
                        ?>
                        <tr>
                            <td style="text-align: center; font-weight: 700;"><?= $idx + 1 ?></td>
                            <td>
                                <?= htmlspecialchars($ensayo['descripcion_ensayo'] ?? $ensayo['nombre_ensayo'] ?? '') ?>
                                <?php if (!empty($ensayo['descripcion_adicional'])): ?>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                                        <?= nl2br(htmlspecialchars($ensayo['descripcion_adicional'], ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center; font-weight: 700;">
                                <?= number_format($ensayo['cantidad'], 1) ?>
                            </td>
                            <td style="text-align: center; font-family: monospace; font-weight: 700;">
                                <?= $codigosEnsayo !== [] ? htmlspecialchars(implode(', ', $codigosEnsayo), ENT_QUOTES, 'UTF-8') : '—' ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td style="text-align: center; font-weight: 700;">1</td>
                        <td>Determinación de la resistencia del concreto.</td>
                        <td style="text-align: center; font-weight: 700;">1.0</td>
                        <td style="text-align: center;">—</td>
                    </tr>
                <?php endif; ?>
                <?php if ($codigosOs !== []): ?>
                    <tr>
                        <td style="text-align: center; font-weight: 700;">—</td>
                        <td>Códigos de laboratorio recepcionados (MS/MC)</td>
                        <td style="text-align: center; font-weight: 700;"><?= count($codigosOs) ?></td>
                        <td style="text-align: center; font-family: monospace; font-weight: 700;">
                            <?= htmlspecialchars(implode(', ', $codigosOs), ENT_QUOTES, 'UTF-8') ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- NOTAS Y CONDICIONES -->
        <div class="os-section-notes">
            <p><strong>Nota:</strong></p>
            <p>***Los informes de ensayo se entregan únicamente en formato digital (.PDF). Serán enviados al correo del contacto designado por el cliente.</p>
            <br>
            <?php
            $condicionesList = [];
            if (!empty($os['ensayos'])) {
                foreach ($os['ensayos'] as $ens) {
                    if (!empty(trim($ens['condiciones_muestra'] ?? ''))) {
                        $condicionesList[] = trim($ens['condiciones_muestra']);
                    }
                }
            }
            $condicionesList = array_unique($condicionesList);
            $condicionesTexto = !empty($condicionesList) 
                ? implode('; ', $condicionesList) 
                : 'Muestras con dimensiones estándar sin alteración física, en caso de encontrarse se notifica al cliente cualquier anomalía antes de proceder.';
            ?>
            <p>Condiciones de las muestras: <?= htmlspecialchars($condicionesTexto, ENT_QUOTES, 'UTF-8') ?></p>

            <div class="os-contacts-list">
                <?php if (!empty($contactos)): ?>
                    <?php foreach ($contactos as $cIdx => $cont): ?>
                        <div>-Contacto <?= $cIdx + 1 ?>: <?= htmlspecialchars(is_array($cont) ? ($cont['nombre'] ?? json_encode($cont)) : $cont) ?></div>
                    <?php endforeach; ?>
                <?php else: ?>
                    <div>-Contacto 1: <?= htmlspecialchars($os['atencion_a'] ?: 'Ing. Noel Hernández') ?></div>
                    <div>-Correo: <?= htmlspecialchars($os['cliente_email'] ?: 'cliente@cycsanic.com') ?></div>
                <?php endif; ?>
            </div>

            <p style="margin-top: 10px;">Cotización origen #<?= htmlspecialchars((string)($os['cotizacion_codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?><?php if (isset($os['cotizacion_version']) && $os['cotizacion_version'] !== '' && $os['cotizacion_version'] !== null): ?> · Versión <?= htmlspecialchars((string)$os['cotizacion_version'], ENT_QUOTES, 'UTF-8') ?><?php endif; ?></p>
        </div>

        <!-- FIRMAS (4 COLUMNAS COMPATIBLES CON DOMPDF Y PANTALLA) -->
        <table class="os-signatures-table">
            <tr>
                <td>Aprobado por Administración<br>Fecha:</td>
                <td>Recibido Por Recepción<br>Fecha:</td>
                <td>Entrega orden de servicio realizada<br>Recepción<br>Fecha:</td>
                <td>Recibe orden de servicio realizada<br>Administración<br>Fecha:</td>
            </tr>
        </table>

        <!-- PIE DE PÁGINA (3 COLUMNAS EN UNA SOLA LÍNEA) -->
        <table class="os-footer-table">
            <tr>
                <td style="width: 39%; text-align: left;">
                    <strong>Consultoría y Construcción S.A (CYCSA)</strong><br>
                    Km. 83 1/2 Carretera León - Managua, León, Nicaragua
                </td>
                <td style="width: 41%; text-align: center;">
                    (505) 2310-3988 / (505) 8851-6377<br>
                    gerencia@cycsanic.com | admon@cycsanic.com | calidad@cycsanic.com
                </td>
                <td style="width: 20%; text-align: right;">
                    Lic. MTI No. 6693<br>
                    www.cycsanic.com
                </td>
            </tr>
        </table>
    </div>
<?php if ($esModoPdfRgFm39): ?>
</body>
</html>
<?php else: ?>
</div>

<!-- HISTORIAL DE VERSIONES DE LA O/S (2.5) -->
<?php $historialVersionesOs = $os['historial_versiones'] ?? []; ?>
<div class="no-print" style="width: 100%; max-width: 100%; margin: 16px 0 16px 0; background: #fff; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px 22px; box-shadow: 0 2px 10px rgba(0,0,0,0.04);">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h3 style="margin: 0; font-size: 15px; color: #103487; font-weight: 700;">
            <i class="fa-solid fa-code-branch"></i> Historial de Versiones de la O/S
        </h3>
        <span style="font-size:12px; font-weight:700; background:#eff6ff; color:#1e40af; border:1px solid #bfdbfe; padding:3px 10px; border-radius:12px;">
            Versión Vigente: v<?= (int)($os['version'] ?? 1) ?>
        </span>
    </div>
    <?php if (!empty($os['motivo_cambio_version'])): ?>
        <div style="background:#fffbeb; border:1px solid #fde68a; border-radius:6px; padding:8px 12px; font-size:12.5px; color:#92400e; margin-bottom:12px;">
            <strong>Último motivo de modificación:</strong> <?= htmlspecialchars((string)$os['motivo_cambio_version'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($historialVersionesOs)): ?>
    <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
        <thead>
            <tr style="background:#f8fafc; border-bottom:1.5px solid #cbd5e1;">
                <th style="padding:8px 10px; text-align:left; width:80px;">Versión</th>
                <th style="padding:8px 10px; text-align:left; width:145px;">Fecha</th>
                <th style="padding:8px 10px; text-align:left; width:150px;">Usuario</th>
                <th style="padding:8px 10px; text-align:left;">Motivo de Cambio</th>
                <th style="padding:8px 10px; text-align:left;">Snapshot Anterior</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($historialVersionesOs as $verOs):
                $snapOs = json_decode((string)($verOs['snapshot_json'] ?? '{}'), true) ?: [];
            ?>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:8px 10px;"><span style="background:#64748b; color:#fff; padding:2px 8px; border-radius:4px; font-weight:700; font-size:11.5px;">v<?= (int)$verOs['version'] ?></span></td>
                <td style="padding:8px 10px; color:#475569;"><?= !empty($verOs['created_at']) ? date('d/m/Y H:i', strtotime($verOs['created_at'])) : '—' ?></td>
                <td style="padding:8px 10px; font-weight:600; color:#1e293b;"><?= htmlspecialchars((string)($verOs['usuario_nombre'] ?? 'Sistema'), ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:8px 10px; color:#334155;"><?= htmlspecialchars((string)($verOs['motivo_cambio'] ?: 'Modificación registrada'), ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:8px 10px; color:#64748b; font-size:11.5px;">
                    Proyecto: <strong><?= htmlspecialchars((string)($snapOs['nombre_proyecto'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong>
                    &bull; Atención: <?= htmlspecialchars((string)($snapOs['atencion_a'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                    &bull; Pago: <?= htmlspecialchars((string)($snapOs['forma_pago'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="margin:0; font-size:12.5px; color:#64748b;">Esta Orden de Servicio se encuentra en su versión inicial (v<?= (int)($os['version'] ?? 1) ?>) sin revisiones anteriores archivadas.</p>
    <?php endif; ?>
</div>
<?php if (!empty($mostrar_bitacora)): ?>
<div class="no-print" style="width: 100%; max-width: 100%; margin: 8px 0 40px 0;">
<?php
$bitacora_modulo_nombre = $bitacora_modulo_nombre ?? ('Orden de Servicio ' . ($os['codigo_os'] ?? ''));
$bitacora_limite = $bitacora_limite ?? 200;
$bitacora_abierta = $bitacora_abierta ?? false;
require dirname(__DIR__, 3) . '/Views/parciales/bitacora_modulo.php';
?>
</div>
<?php endif; ?>

</div>
<?php endif; ?>
