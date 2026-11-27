<?php
// Vista oficial del documento ORDEN DE SERVICIO (CYCSA-RG-FM-39 V1R1) — Idéntica en Pantalla, Impresión y PDF Dompdf
$esModoPdfRgFm39 = !empty($esModoPdfRgFm39);
$membreteSrcRgFm39 = class_exists(\Cycsa\Modulos\Cotizaciones\Servicios\DocumentoCotizacionRgFm31Servicio::class)
    ? \Cycsa\Modulos\Cotizaciones\Servicios\DocumentoCotizacionRgFm31Servicio::resolverMembreteVerticalSrc($esModoPdfRgFm39)
    : '';

// Normalizar contactos estructurados (Nombre | Teléfono | Correo electrónico)
$contactosNormalizados = [];
$fuenteContactosOs = !empty($os['contactos_json']) ? $os['contactos_json'] : ($os['cotizacion_contactos'] ?? '');
if (!empty($fuenteContactosOs)) {
    $rawCont = is_array($fuenteContactosOs) ? $fuenteContactosOs : json_decode((string)$fuenteContactosOs, true);
    if (is_array($rawCont)) {
        foreach ($rawCont as $cItem) {
            if (is_array($cItem)) {
                $nomC = trim((string)($cItem['nombre'] ?? ''));
                $telC = trim((string)($cItem['telefono'] ?? ''));
                $corC = trim((string)($cItem['correo'] ?? ($cItem['email'] ?? '')));
                if ($nomC !== '' || $telC !== '' || $corC !== '') {
                    $contactosNormalizados[] = ['nombre' => $nomC ?: '—', 'telefono' => $telC ?: '—', 'correo' => $corC ?: '—'];
                }
            } elseif (is_string($cItem) && trim($cItem) !== '') {
                $contactosNormalizados[] = ['nombre' => trim($cItem), 'telefono' => '—', 'correo' => '—'];
            }
        }
    } elseif (is_string($fuenteContactosOs)) {
        foreach (array_filter(array_map('trim', preg_split('/\r?\n/', $fuenteContactosOs) ?: [])) as $lnC) {
            $partes = array_map('trim', explode('|', $lnC));
            $contactosNormalizados[] = [
                'nombre' => $partes[0] ?? $lnC,
                'telefono' => $partes[1] ?? '—',
                'correo' => $partes[2] ?? '—',
            ];
        }
    }
}
if (empty($contactosNormalizados)) {
    $contactosNormalizados[] = [
        'nombre' => trim((string)($os['atencion_a'] ?? '')) ?: 'N/A',
        'telefono' => trim((string)($os['cliente_telefono'] ?? '')) ?: '—',
        'correo' => trim((string)($os['cliente_email'] ?? '')) ?: '—',
    ];
}

// Documentos vinculados editables (solicitud/autorización y otros anexos)
$docsVincExtra = [];
if (!empty($os['documentos_vinculados_json'])) {
    $docsVincExtra = is_array($os['documentos_vinculados_json'])
        ? $os['documentos_vinculados_json']
        : (json_decode((string)$os['documentos_vinculados_json'], true) ?: []);
}

// Resolver notas oficiales del servicio
$notasServicioLista = [];
if (!empty(trim((string)($os['notas_condiciones'] ?? '')))) {
    foreach (array_filter(array_map('trim', preg_split('/\r?\n/', (string)$os['notas_condiciones']) ?: [])) as $lnN) {
        $notasServicioLista[] = $lnN;
    }
}
if (empty($notasServicioLista)) {
    $notasServicioLista = [
        '***Los informes de ensayo se entregan únicamente en formato digital (.PDF). Serán enviados al correo del contacto designado por el cliente.',
        '***No incluye movilización por traslado de muestras.',
        '*Cliente toma las muestras y las entrega en Laboratorio CYCSA ubicado Km 83.5 Carretera León Managua.',
    ];
}

// Condiciones de muestra heredadas de ensayos
$condicionesList = [];
foreach (($os['ensayos'] ?? []) as $ensCond) {
    $txtC = trim((string)($ensCond['condiciones_muestra'] ?? ''));
    if ($txtC !== '' && strtoupper($txtC) !== 'N/A') {
        $condicionesList[] = $txtC;
    }
}
$condicionesList = array_values(array_unique($condicionesList));

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
            $mimeLogo = ($extLogo === 'jpg' || $extLogo === 'jpeg') ? 'image/jpeg' : 'image/png';
            $bytesLogo = @file_get_contents($rutaLogo);
            if ($bytesLogo !== false && $bytesLogo !== '') {
                $logoSrcRgFm39 = 'data:' . $mimeLogo . ';base64,' . base64_encode($bytesLogo);
                break;
            }
        }
    }
}

$clienteMostrar = (string)(!empty($os['cliente_informe_nombre']) ? $os['cliente_informe_nombre'] : ($os['cliente_nombre'] ?? ''));
$direccionMostrar = (string)(!empty($os['direccion_cliente']) ? $os['direccion_cliente'] : ($os['cliente_direccion'] ?? 'N/A'));
$ubicacionMostrar = (string)(!empty($os['ubicacion_proyecto']) ? $os['ubicacion_proyecto'] : ($os['direccion_proyecto'] ?? 'N/A'));
$fechaEmisionFmt = !empty($os['fecha_emision']) ? date('d/m/Y', strtotime((string)$os['fecha_emision'])) : date('d/m/Y');
$cotCodigoTxt = (string)($os['cotizacion_codigo'] ?? 'N/A');
$cotVerTxt = (isset($os['cotizacion_version']) && $os['cotizacion_version'] !== '' && $os['cotizacion_version'] !== null)
    ? (' · Versión ' . (string)$os['cotizacion_version'])
    : '';
$cotIdEnc = !empty($os['id_cotizacion']) && function_exists('codificarId') ? codificarId((int)$os['id_cotizacion']) : (string)($os['id_cotizacion'] ?? '');
$informesVinculadosOs = $os['informes_vinculados'] ?? [];
$remisionesVinculadasOs = $os['remisiones_vinculadas'] ?? [];
$hojasServicioOs = $os['hojas_servicio'] ?? [];
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
    @page {
        size: letter portrait;
        margin: <?= ($esModoPdfRgFm39 && $membreteSrcRgFm39 !== '') ? '30mm 12mm 28mm 12mm' : '8mm 11mm' ?>;
    }
    <?php if ($esModoPdfRgFm39): ?>
    body {
        margin: 0;
        padding: 0;
        font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
        color: #111827;
        font-size: 9px;
        line-height: 1.3;
        background: transparent;
    }
    .os-membrete-fondo-pdf {
        position: fixed;
        top: -30mm;
        left: -12mm;
        width: 215.9mm;
        height: 279.4mm;
        z-index: -1000;
    }
    <?php endif; ?>
    .os-wrapper { background: #f8fafc; padding: 12px 0 32px 0; min-height: auto; }
    .os-container {
        width: 100%;
        max-width: 100%;
        margin: 0;
        background-color: <?= $esModoPdfRgFm39 ? 'transparent' : '#ffffff' ?>;
        <?= (!$esModoPdfRgFm39 && $membreteSrcRgFm39 !== '') ? "background-image: url('" . htmlspecialchars($membreteSrcRgFm39, ENT_QUOTES, 'UTF-8') . "'); background-size: 100% 100%; background-repeat: no-repeat; background-position: center top;" : '' ?>
        padding: <?= $esModoPdfRgFm39 ? '0' : ($membreteSrcRgFm39 !== '' ? '122px 42px 110px 42px' : '26px 32px') ?>;
        border-radius: <?= $esModoPdfRgFm39 ? '0' : '10px' ?>;
        border: <?= $esModoPdfRgFm39 ? '0' : '1px solid #e5e7eb' ?>;
        font-family: 'DejaVu Sans', 'Inter', Helvetica, Arial, sans-serif;
        color: #111827;
        line-height: <?= $esModoPdfRgFm39 ? '1.3' : '1.38' ?>;
        font-size: <?= $esModoPdfRgFm39 ? '9px' : '12.5px' ?>;
        box-sizing: border-box;
    }

    /* Encabezado oficial CYCSA-RG-FM-39 V1R1 (misma estructura que Cotización RG-FM-31 V2R1) */
    .os-header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: <?= $esModoPdfRgFm39 ? '6px' : '10px' ?>;
        border: 1.5px solid #1F3864;
        background: transparent;
    }
    .os-header-table td {
        padding: <?= $esModoPdfRgFm39 ? '3px 5px' : '6px 10px' ?>;
        vertical-align: middle;
        border: 1px solid #94a3b8;
        background: transparent;
    }
    .os-logo-img {
        height: <?= $esModoPdfRgFm39 ? '42px' : '54px' ?>;
        width: auto;
        display: block;
    }
    .os-company-name {
        font-size: <?= $esModoPdfRgFm39 ? '11px' : '15px' ?>;
        font-weight: 800;
        color: #1F3864;
        letter-spacing: 0.3px;
        margin: 0;
        text-align: center;
    }
    .os-company-ruc {
        font-size: <?= $esModoPdfRgFm39 ? '8px' : '11.5px' ?>;
        font-weight: 600;
        color: #374151;
        text-align: center;
        margin-top: 1px;
    }
    .os-main-title {
        font-size: <?= $esModoPdfRgFm39 ? '12px' : '17px' ?>;
        font-weight: 800;
        color: #1F3864;
        letter-spacing: 0.6px;
        margin: <?= $esModoPdfRgFm39 ? '3px 0 0 0' : '5px 0 0 0' ?>;
        padding-top: 3px;
        border-top: 1px solid #cbd5e1;
        text-align: center;
    }
    .os-doc-code-box {
        text-align: center;
        font-size: <?= $esModoPdfRgFm39 ? '7.8px' : '11px' ?>;
        color: #374151;
        line-height: 1.3;
        background: transparent;
    }
    .os-doc-code-box strong {
        color: #1F3864;
        font-size: <?= $esModoPdfRgFm39 ? '8.8px' : '12px' ?>;
        display: block;
    }

    /* Banners de sección uniformes con CYCSA-RG-FM-31 V2R1 y RG-FM-39 V1R1 */
    .os-sec-banner {
        background-color: rgba(31, 56, 100, 0.92);
        color: #ffffff;
        font-weight: 700;
        font-size: <?= $esModoPdfRgFm39 ? '8px' : '11px' ?>;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        padding: <?= $esModoPdfRgFm39 ? '3px 7px' : '6px 12px' ?>;
        margin: <?= $esModoPdfRgFm39 ? '5px 0 0 0' : '14px 0 0 0' ?>;
    }

    /* Tabla de metadatos 2 columnas (4 celdas) transparente */
    .os-meta-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: <?= $esModoPdfRgFm39 ? '4px' : '10px' ?>;
        font-size: <?= $esModoPdfRgFm39 ? '8.3px' : '12px' ?>;
        border: 1px solid #94a3b8;
        background: transparent;
    }
    .os-meta-table td {
        padding: <?= $esModoPdfRgFm39 ? '2.5px 5px' : '6px 10px' ?>;
        border: 1px solid #94a3b8;
        vertical-align: top;
        background: transparent;
    }
    .os-meta-k {
        background-color: rgba(248, 250, 252, 0.55) !important;
        font-weight: 700;
        color: #1F3864;
        width: 18%;
        white-space: nowrap;
    }
    .os-meta-v {
        color: #111827;
        width: 32%;
        background: transparent;
    }

    /* Tablas estructuradas de contenido transparentes */
    .os-table-border {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: <?= $esModoPdfRgFm39 ? '4px' : '10px' ?>;
        font-size: <?= $esModoPdfRgFm39 ? '8.2px' : '12px' ?>;
        border: 1px solid #94a3b8;
        background: transparent;
    }
    .os-table-border th {
        background-color: rgba(241, 245, 249, 0.6);
        color: #1F3864;
        font-weight: 700;
        text-align: center;
        padding: <?= $esModoPdfRgFm39 ? '3.5px 5px' : '7px 9px' ?>;
        border: 1px solid #94a3b8;
        font-size: <?= $esModoPdfRgFm39 ? '7.8px' : '11.5px' ?>;
    }
    .os-table-border td {
        padding: <?= $esModoPdfRgFm39 ? '3px 5px' : '7px 9px' ?>;
        border: 1px solid #94a3b8;
        vertical-align: top;
        color: #111827;
        background: transparent;
    }

    /* Notas y Condiciones transparentes */
    .os-notes-box {
        border: 1px solid #94a3b8;
        border-top: 0;
        padding: <?= $esModoPdfRgFm39 ? '4px 7px' : '10px 14px' ?>;
        font-size: <?= $esModoPdfRgFm39 ? '8px' : '12px' ?>;
        color: #1f2937;
        line-height: 1.35;
        margin-bottom: <?= $esModoPdfRgFm39 ? '4px' : '10px' ?>;
        background: transparent;
    }
    .os-notes-box p { margin: 0 0 2px 0; }

    /* Recuadro Automatizado de 4 Validaciones CYCSA-RG-FM-39 V1R1 */
    .os-signatures-table {
        width: 100%;
        border-collapse: collapse;
        border: 1px solid #94a3b8;
        table-layout: fixed;
        margin-bottom: <?= $esModoPdfRgFm39 ? '4px' : '12px' ?>;
        background: transparent;
        page-break-inside: avoid;
    }
    .os-signatures-table th.os-sig-group {
        background-color: rgba(31, 56, 100, 0.94);
        color: #ffffff;
        font-weight: 700;
        font-size: <?= $esModoPdfRgFm39 ? '7.8px' : '11px' ?>;
        letter-spacing: 0.4px;
        text-transform: uppercase;
        text-align: center;
        padding: <?= $esModoPdfRgFm39 ? '4px 5px' : '7px 8px' ?>;
        border: 1px solid #1F3864;
    }
    .os-signatures-table td {
        width: 25%;
        border: 1px solid #94a3b8;
        padding: <?= $esModoPdfRgFm39 ? '6px 5px 6px 5px' : '12px 10px 10px 10px' ?>;
        text-align: center;
        vertical-align: top;
        font-size: <?= $esModoPdfRgFm39 ? '7.6px' : '11.5px' ?>;
        color: #111827;
        line-height: 1.28;
        background: transparent;
    }
    .os-sig-role {
        font-weight: 600;
        color: #1e293b;
        min-height: <?= $esModoPdfRgFm39 ? '24px' : '38px' ?>;
        margin-bottom: <?= $esModoPdfRgFm39 ? '6px' : '12px' ?>;
    }
    .os-sig-badge-ok {
        color: #15803d;
        font-weight: 700;
        font-size: <?= $esModoPdfRgFm39 ? '7.4px' : '11px' ?>;
        margin-bottom: <?= $esModoPdfRgFm39 ? '4px' : '8px' ?>;
    }
    .os-sig-badge-pend {
        color: #64748b;
        font-style: italic;
        font-size: <?= $esModoPdfRgFm39 ? '7.2px' : '10.5px' ?>;
        margin-bottom: <?= $esModoPdfRgFm39 ? '4px' : '8px' ?>;
    }
    .os-sig-badge-warn {
        color: #b45309;
        font-weight: 700;
        font-size: <?= $esModoPdfRgFm39 ? '7.2px' : '10.5px' ?>;
        margin-bottom: <?= $esModoPdfRgFm39 ? '4px' : '8px' ?>;
    }
    .os-sig-icon {
        display: inline-block;
        width: <?= $esModoPdfRgFm39 ? '13px' : '18px' ?>;
        height: <?= $esModoPdfRgFm39 ? '13px' : '18px' ?>;
        line-height: <?= $esModoPdfRgFm39 ? '13px' : '18px' ?>;
        border-radius: 50%;
        border: 1.4px solid #15803d;
        color: #15803d;
        font-weight: 700;
        font-size: <?= $esModoPdfRgFm39 ? '8px' : '11px' ?>;
        margin-bottom: 2px;
    }
    .os-sig-line {
        border-top: 1px solid #475569;
        padding-top: 4px;
        font-weight: 700;
        color: #0f172a;
    }
    .os-sig-sub {
        font-weight: 500;
        color: #475569;
        font-size: <?= $esModoPdfRgFm39 ? '7px' : '10.5px' ?>;
        margin-top: 2px;
    }
    .os-sig-footnote {
        font-size: <?= $esModoPdfRgFm39 ? '6.8px' : '10.5px' ?>;
        color: #64748b;
        margin: -2px 0 <?= $esModoPdfRgFm39 ? '4px' : '10px' ?> 0;
        font-style: italic;
    }

    /* Pie de página institucional (misma estructura que Cotización RG-FM-31 V2R1) */
    .os-footer-table {
        width: 100%;
        border-collapse: collapse;
        border-top: 2px solid #1F3864;
        margin-top: <?= $esModoPdfRgFm39 ? '5px' : '14px' ?>;
        table-layout: fixed;
        background: transparent;
    }
    .os-footer-table td {
        padding: 4px 4px 0 4px;
        vertical-align: top;
        font-size: <?= $esModoPdfRgFm39 ? '7.5px' : '10.5px' ?>;
        color: #1F3864;
        font-weight: 600;
        line-height: 1.3;
        border: 0;
        background: transparent;
    }

    .btn-action-bar {
        width: 100%;
        margin: 0 0 14px 0;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 8px;
        padding: 10px 14px;
        border-radius: 10px;
        border: 1px solid #e5e7eb;
        background: #ffffff;
    }
    .os-btn-group-secondary {
        display: inline-flex;
        flex-wrap: wrap;
        gap: 8px;
        align-items: center;
    }

    @media print {
        .no-print { display: none !important; }
        .os-wrapper { padding: 0 !important; background: transparent !important; }
        .os-container { box-shadow: none !important; border: 0 !important; border-radius: 0 !important; padding: 0 !important; font-size: 9.5px !important; }
        .os-sec-banner { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        .os-meta-k, .os-table-border th { -webkit-print-color-adjust: exact; print-color-adjust: exact; }
        @page { margin: 10mm 12mm; }
    }
</style>
<?php if ($esModoPdfRgFm39): ?>
</head>
<body>
<?php if ($membreteSrcRgFm39 !== ''): ?>
<img src="<?= $membreteSrcRgFm39 ?>" class="os-membrete-fondo-pdf" alt="">
<?php endif; ?>
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
            <?php if (!empty($hojasServicioOs)): ?>
                <?php $primeraHojaDet = $hojasServicioOs[0]; ?>
                <a href="/Cycsa/publico/laboratorio/imprimir-solicitud?id_hoja=<?= (int)$primeraHojaDet['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-secondary" title="Imprimir Hoja RT-FM-60 de Laboratorio">
                    <i class="fa-solid fa-flask"></i> Hoja RT-FM-60
                </a>
            <?php endif; ?>
            <a href="/Cycsa/publico/remisiones/crear?id_os=<?= (int)($os['id'] ?? 0) ?>" class="btn-cycsa btn-cycsa-secondary" title="Generar Remisión de Entrega CYCSA-RG-FM-41">
                <i class="fa-solid fa-file-export"></i> Nueva Remisión
            </a>
            <?php if (in_array((int)($_SESSION['usuario_rol'] ?? 0), [1, 2, 3], true) || tienePermiso('operaciones', 'aprobar')): ?>
            <a href="/Cycsa/publico/operaciones?bandeja=supervisor&id_os=<?= (int)($os['id'] ?? 0) ?>" class="btn-cycsa btn-cycsa-secondary" title="Revisión y aprobación técnica unificada del supervisor en Operaciones">
                <i class="fa-solid fa-user-check"></i> Revisión Supervisor
            </a>
            <?php endif; ?>
            <?php if (!in_array($os['estado'] ?? '', ['Finalizado', 'Archivado', 'Cerrado'], true)): ?>
            <a href="/Cycsa/publico/ordenes-servicio/editar?id_os=<?= (int)($os['id'] ?? 0) ?>" class="btn-cycsa btn-cycsa-secondary">
                <i class="fa-solid fa-pen-to-square"></i> Editar O/S
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

    <?php
    $alertaAlcanceDet = $os['alerta_alcance_mensual'] ?? [];
    if (!empty($alertaAlcanceDet['tiene_alerta'])):
        $esDangerAlc = ($alertaAlcanceDet['nivel'] ?? 'warning') === 'danger';
    ?>
    <div class="no-print" style="width: 100%; margin: 0 0 14px 0; background: <?= $esDangerAlc ? '#fef2f2' : '#fffbeb' ?>; border: 1px solid <?= $esDangerAlc ? '#fca5a5' : '#fcd34d' ?>; color: <?= $esDangerAlc ? '#991b1b' : '#92400e' ?>; border-radius: 8px; padding: 12px 16px; font-size: 13px;">
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

    <?php if (!$esModoPdfRgFm39): ?>
    <?php
    $csrfValOs = htmlspecialchars((string)($csrfToken ?? ($_SESSION['csrf_token'] ?? '')), ENT_QUOTES, 'UTF-8');
    $autAdminFec = !empty($os['autorizado_admin_fecha']) ? (string)$os['autorizado_admin_fecha'] : '';
    $recLabFec = !empty($os['recibido_lab_fecha']) ? (string)$os['recibido_lab_fecha'] : '';
    $devSisFec = !empty($os['devuelto_sistema_fecha']) ? (string)$os['devuelto_sistema_fecha'] : '';
    $recAdminFinFec = !empty($os['recibido_admin_final_fecha']) ? (string)$os['recibido_admin_final_fecha'] : '';
    $tieneAlertaPlazoOs = !empty($os['estado_ejecucion_alerta']) && !empty($os['alerta_vencimiento_detalle']);
    $esEjecutadaOs = ($devSisFec !== '' || in_array((string)($os['estado'] ?? ''), ['Ejecutada', 'Finalizado', 'Cerrado', 'Archivado'], true));
    ?>

    <?php if (isset($_SESSION['exito'])): ?>
        <div class="no-print mb-3" style="background: #f0fdf4; border: 1px solid #86efac; color: #166534; border-radius: 8px; padding: 10px 14px; font-size: 13px;">
            <i class="fa-solid fa-circle-check me-1"></i> <?= htmlspecialchars((string)$_SESSION['exito'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['exito']); ?>
    <?php endif; ?>
    <?php if (isset($_SESSION['error'])): ?>
        <div class="no-print mb-3" style="background: #fef2f2; border: 1px solid #fca5a5; color: #991b1b; border-radius: 8px; padding: 10px 14px; font-size: 13px;">
            <i class="fa-solid fa-circle-exclamation me-1"></i> <?= htmlspecialchars((string)$_SESSION['error'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <?php if ($tieneAlertaPlazoOs): ?>
        <div class="no-print mb-3" style="background: #fef2f2; border: 1.5px solid #dc2626; color: #991b1b; border-radius: 8px; padding: 12px 16px; font-size: 13px;">
            <div style="font-weight: 700; margin-bottom: 4px;">
                <i class="fa-solid fa-bell"></i> Alerta de Plazo Mensual Finalizado con Actividades Pendientes
            </div>
            <div><?= htmlspecialchars((string)$os['alerta_vencimiento_detalle'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    <?php endif; ?>

    <!-- BARRA DE VALIDACIÓN DIGITAL CYCSA-RG-FM-39 V1R1 (CON PERMISOS POR CARGO) -->
    <div class="no-print" style="width: 100%; margin: 0 0 14px 0; background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
        <div style="font-size: 12.5px; color: #334155;">
            <strong style="color: #1F3864;"><i class="fa-solid fa-shield-halved me-1"></i> Validación Digital de la Orden (CYCSA-RG-FM-39 V1R1):</strong>
            Estado actual: <span class="cy-pill <?= $esEjecutadaOs ? 'cy-pill-ok' : 'cy-pill-pend' ?>"><?= htmlspecialchars((string)($os['estado'] ?? 'Pendiente'), ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div style="display: inline-flex; flex-wrap: wrap; gap: 8px; align-items: center;">
            <?php if ($autAdminFec === '' && !empty($puede_autorizar_admin)): ?>
                <form method="POST" action="/Cycsa/publico/ordenes-servicio/autorizar-admin" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= $csrfValOs ?>">
                    <input type="hidden" name="id_os" value="<?= (int)$os['id'] ?>">
                    <button type="submit" class="btn-cycsa btn-cycsa-primary" style="font-size: 12px; padding: 6px 12px;">
                        <i class="fa-solid fa-check-double"></i> 1. Autorizar (Administración)
                    </button>
                </form>
            <?php endif; ?>
            <?php if ($recLabFec === '' && !empty($puede_recibir_lab)): ?>
                <form method="POST" action="/Cycsa/publico/ordenes-servicio/recibir-laboratorio" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= $csrfValOs ?>">
                    <input type="hidden" name="id_os" value="<?= (int)$os['id'] ?>">
                    <button type="submit" class="btn-cycsa btn-cycsa-primary" style="font-size: 12px; padding: 6px 12px; background: #0f766e; border-color: #0f766e;">
                        <i class="fa-solid fa-clipboard-check"></i> 2. Confirmar Recepción (Lab / Recepción)
                    </button>
                </form>
            <?php endif; ?>
            <?php if ($esEjecutadaOs && $recAdminFinFec === '' && !empty($puede_recibir_admin_final)): ?>
                <form method="POST" action="/Cycsa/publico/ordenes-servicio/recibir-admin-final" style="margin:0;">
                    <input type="hidden" name="csrf_token" value="<?= $csrfValOs ?>">
                    <input type="hidden" name="id_os" value="<?= (int)$os['id'] ?>">
                    <button type="submit" class="btn-cycsa btn-cycsa-primary" style="font-size: 12px; padding: 6px 12px; background: #15803d; border-color: #15803d;">
                        <i class="fa-solid fa-stamp"></i> 4. Confirmar Recepción Definitiva (Administración)
                    </button>
                </form>
            <?php endif; ?>
            <?php if (!empty($puede_corregir_validacion) && ($autAdminFec !== '' || $recLabFec !== '' || $recAdminFinFec !== '')): ?>
                <button type="button" onclick="document.getElementById('modalCorreccionValidacionOS').style.display='flex'" class="btn-cycsa btn-cycsa-secondary" style="font-size: 12px; padding: 6px 12px;">
                    <i class="fa-solid fa-pen-ruler"></i> Corrección Autorizada
                </button>
            <?php endif; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- CONTENEDOR DEL DOCUMENTO OFICIAL CYCSA-RG-FM-39 V1R1 -->
    <div class="os-container">
        <!-- ENCABEZADO OFICIAL -->
        <table class="os-header-table">
            <tr>
                <td style="width: 24%; text-align: center;">
                    <img src="<?= htmlspecialchars($logoSrcRgFm39, ENT_QUOTES, 'UTF-8') ?>" alt="CYCSA" class="os-logo-img" style="margin: 0 auto;"<?= !$esModoPdfRgFm39 ? ' onerror="this.src=\'/Cycsa/publico/img/logo_cycsa_rt_fm_13.png\'; this.onerror=null;"' : '' ?>>
                </td>
                <td style="width: 50%; text-align: center;">
                    <div class="os-company-name">CONSULTORÍA Y CONSTRUCCIÓN S.A</div>
                    <div class="os-company-ruc">RUC: J0310000073465</div>
                    <h1 class="os-main-title">ORDEN DE SERVICIO</h1>
                </td>
                <td style="width: 26%;" class="os-doc-code-box">
                    Código de documento:
                    <strong>CYCSA-RG-FM-39 V1R1</strong>
                    <span>Versión O/S: v<?= (int)($os['version'] ?? 1) ?></span>
                </td>
            </tr>
        </table>

        <!-- SECCIÓN 1: DATOS DEL CLIENTE Y DE LA ORDEN -->
        <div class="os-sec-banner">DATOS DEL CLIENTE Y DE LA ORDEN</div>
        <table class="os-meta-table">
            <tr>
                <td class="os-meta-k">CLIENTE:</td>
                <td class="os-meta-v"><strong><?= htmlspecialchars($clienteMostrar, ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td class="os-meta-k">CÉDULA/RUC:</td>
                <td class="os-meta-v"><?= htmlspecialchars((string)($os['cliente_rfc'] ?: 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td class="os-meta-k">DIRECCIÓN:</td>
                <td class="os-meta-v"><?= htmlspecialchars($direccionMostrar, ENT_QUOTES, 'UTF-8') ?></td>
                <td class="os-meta-k">N.º DE ORDEN:</td>
                <td class="os-meta-v"><strong><?= htmlspecialchars((string)($os['codigo_os'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong> (v<?= (int)($os['version'] ?? 1) ?>)</td>
            </tr>
            <tr>
                <td class="os-meta-k">ATENCIÓN A:</td>
                <td class="os-meta-v"><?= htmlspecialchars((string)($os['atencion_a'] ?: 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="os-meta-k">FECHA:</td>
                <td class="os-meta-v"><?= htmlspecialchars($fechaEmisionFmt, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td class="os-meta-k">PROYECTO:</td>
                <td class="os-meta-v"><?= htmlspecialchars((string)($os['nombre_proyecto'] ?: 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="os-meta-k">ELABORADO POR:</td>
                <td class="os-meta-v"><?= htmlspecialchars((string)($os['elaborado_por'] ?: (($os['cotizacion_creador_nombre'] ?? '') ?: 'Personal Autorizado')), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td class="os-meta-k">CORREO ELECTRÓNICO:</td>
                <td class="os-meta-v"><?= htmlspecialchars((string)($os['cliente_email'] ?: 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="os-meta-k">TELÉFONO:</td>
                <td class="os-meta-v"><?= htmlspecialchars((string)($os['cliente_telefono'] ?: 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td class="os-meta-k">UBICACIÓN DEL PROYECTO:</td>
                <td class="os-meta-v"><?= htmlspecialchars($ubicacionMostrar, ENT_QUOTES, 'UTF-8') ?></td>
                <td class="os-meta-k">FORMA DE PAGO:</td>
                <td class="os-meta-v"><?= htmlspecialchars((string)($os['forma_pago'] ?: 'Pago contra entrega'), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td class="os-meta-k">COTIZACIÓN ORIGEN:</td>
                <td class="os-meta-v">Cotización origen #<?= htmlspecialchars($cotCodigoTxt, ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars($cotVerTxt, ENT_QUOTES, 'UTF-8') ?></td>
                <td class="os-meta-k">MODALIDAD:</td>
                <td class="os-meta-v">
                    <?php if (!empty($os['es_multimes']) || !empty($os['vigencia_contrato']['es_contrato_largo'])): ?>
                        <strong>Contrato Largo</strong> (<?= htmlspecialchars((string)($os['periodicidad'] ?? 'Mensual'), ENT_QUOTES, 'UTF-8') ?><?= !empty($os['vigencia_contrato']['duracion_meses']) ? ' · ' . (int)$os['vigencia_contrato']['duracion_meses'] . ' meses' : '' ?>)
                        <?php if (!empty($os['fecha_inicio']) && !empty($os['fecha_fin'])): ?>
                            · <?= date('d/m/Y', strtotime((string)$os['fecha_inicio'])) ?> — <?= date('d/m/Y', strtotime((string)$os['fecha_fin'])) ?>
                        <?php endif; ?>
                    <?php else: ?>
                        <?= htmlspecialchars((string)($os['tipo_contrato'] ?? 'Puntual'), ENT_QUOTES, 'UTF-8') ?>
                    <?php endif; ?>
                </td>
            </tr>
            <?php if (!empty($os['cliente_factura_nombre']) && $os['cliente_factura_nombre'] !== $clienteMostrar): ?>
            <tr>
                <td class="os-meta-k">CLIENTE (PAGADOR):</td>
                <td class="os-meta-v" colspan="3"><strong><?= htmlspecialchars((string)$os['cliente_factura_nombre'], ENT_QUOTES, 'UTF-8') ?></strong></td>
            </tr>
            <?php endif; ?>
        </table>

        <!-- SECCIÓN 2: DETALLE DE SERVICIOS CONTRATADOS (6 COLUMNAS) -->
        <div class="os-sec-banner">DETALLE DE SERVICIOS CONTRATADOS</div>
        <table class="os-table-border">
            <thead>
                <tr>
                    <th style="width: 6%;">Línea</th>
                    <th style="width: 39%; text-align: left;">Descripción del servicio</th>
                    <th style="width: 19%;">Método de ensayo</th>
                    <th style="width: 11%;">Unidad de medida</th>
                    <th style="width: 7%;">Cant.</th>
                    <th style="width: 18%;">Código de laboratorio</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($os['ensayos'])): ?>
                    <?php $lineaNum = 0; foreach ($os['ensayos'] as $ensayo): ?>
                        <?php
                        if (array_key_exists('permitido', $ensayo) && (int)$ensayo['permitido'] !== 1) {
                            continue;
                        }
                        $lineaNum++;
                        $codigosEnsayo = [];
                        foreach (($ensayo['codigos_laboratorio'] ?? []) as $codigoEnsayo) {
                            $codigoEnsayo = trim((string)$codigoEnsayo);
                            if ($codigoEnsayo !== '') {
                                $codigosEnsayo[$codigoEnsayo] = $codigoEnsayo;
                            }
                        }
                        $codigosEnsayo = array_values($codigosEnsayo);
                        $metodoEns = trim((string)($ensayo['procedimiento'] ?? ''));
                        if ($metodoEns === '') {
                            $metodoEns = trim((string)($ensayo['norma_astm'] ?? ''));
                        }
                        if ($metodoEns === '') {
                            $metodoEns = 'Pendiente de verificar';
                        }
                        $unidadEns = trim((string)($ensayo['unidad_medida'] ?? '')) ?: 'Unidad';
                        ?>
                        <tr>
                            <td style="text-align: center; font-weight: 700;"><?= $lineaNum ?></td>
                            <td>
                                <?= htmlspecialchars((string)($ensayo['descripcion_ensayo'] ?? $ensayo['nombre_ensayo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($ensayo['descripcion_adicional'])): ?>
                                    <div style="font-size: 90%; color: #4b5563; margin-top: 2px;">
                                        <?= nl2br(htmlspecialchars((string)$ensayo['descripcion_adicional'], ENT_QUOTES, 'UTF-8')) ?>
                                    </div>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;"><?= htmlspecialchars($metodoEns, ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="text-align: center;"><?= htmlspecialchars($unidadEns, ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="text-align: center; font-weight: 700;"><?= number_format((float)($ensayo['cantidad'] ?? 1), 1) ?></td>
                            <td style="text-align: center; font-family: monospace; font-weight: 600;">
                                <?php if ($codigosEnsayo !== []): ?>
                                    <?= implode(', ', array_map(static fn(string $c): string => '<span style="white-space:nowrap;">' . htmlspecialchars($c, ENT_QUOTES, 'UTF-8') . '</span>', $codigosEnsayo)) ?>
                                <?php else: ?>
                                    Pendiente de asignación
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td style="text-align: center; font-weight: 700;">1</td>
                        <td>Sin servicios registrados.</td>
                        <td style="text-align: center;">Pendiente de verificar</td>
                        <td style="text-align: center;">Unidad</td>
                        <td style="text-align: center; font-weight: 700;">1.0</td>
                        <td style="text-align: center;">Pendiente de asignación</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- SECCIÓN 3: INFORMES DE ENSAYO VINCULADOS -->
        <div class="os-sec-banner">INFORMES DE ENSAYO VINCULADOS</div>
        <table class="os-table-border">
            <thead>
                <tr>
                    <th style="width: 30%; text-align: left;">Informe vinculado</th>
                    <th style="width: 16%;">Estado</th>
                    <th style="width: 16%;">Fecha de emisión</th>
                    <th style="width: 16%;">Fecha de envío</th>
                    <th style="width: 22%;">Documento / enlace PDF</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($informesVinculadosOs)): ?>
                    <?php foreach ($informesVinculadosOs as $infV):
                        $infAprob = (int)($infV['aprobado'] ?? 0) === 1;
                        $infUrl = $infAprob ? trim((string)($infV['url_pdf'] ?? '')) : '';
                    ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 700;"><?= htmlspecialchars((string)($infV['codigo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align: center;"><?= htmlspecialchars((string)($infV['estado'] ?? 'Pendiente'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align: center;"><?= htmlspecialchars((string)(!empty($infV['fecha_emision']) ? $infV['fecha_emision'] : '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align: center;"><?= htmlspecialchars((string)(!empty($infV['fecha_envio']) ? $infV['fecha_envio'] : '—'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align: center;">
                            <?php if ($infUrl !== ''): ?>
                                <a href="<?= htmlspecialchars($infUrl, ENT_QUOTES, 'UTF-8') ?>" target="_blank" style="color: #1F3864; font-weight: 600;">Abrir PDF</a>
                            <?php else: ?>
                                —
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="5" style="text-align: center; color: #6b7280;">Pendiente de emisión de informes de ensayo.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- SECCIÓN 4: DOCUMENTOS VINCULADOS A LA ORDEN DE SERVICIO -->
        <div class="os-sec-banner">DOCUMENTOS VINCULADOS A LA ORDEN DE SERVICIO</div>
        <table class="os-table-border">
            <thead>
                <tr>
                    <th style="width: 40%; text-align: left;">Tipo de documento</th>
                    <th style="width: 35%; text-align: left;">Código / número / referencia</th>
                    <th style="width: 25%;">Documento PDF / enlace</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>Cotización de origen</td>
                    <td style="font-family: monospace; font-weight: 600;"><?= htmlspecialchars($cotCodigoTxt, ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align: center;">
                        <?php if ($cotIdEnc !== '' && !$esModoPdfRgFm39): ?>
                            <a href="/Cycsa/publico/cotizaciones/imprimir?id=<?= htmlspecialchars($cotIdEnc, ENT_QUOTES, 'UTF-8') ?>" target="_blank" style="color: #1F3864; font-weight: 600;">Ver PDF Cotización</a>
                        <?php else: ?>
                            <?= htmlspecialchars($cotCodigoTxt, ENT_QUOTES, 'UTF-8') ?>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php if (!empty($remisionesVinculadasOs)): ?>
                    <?php foreach ($remisionesVinculadasOs as $remV): ?>
                    <tr>
                        <td>Remisión de entrega de informes (CYCSA-RG-FM-41)</td>
                        <td style="font-family: monospace; font-weight: 600;"><?= htmlspecialchars((string)$remV['codigo_remision'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars((string)$remV['estado'], ENT_QUOTES, 'UTF-8') ?>)</td>
                        <td style="text-align: center;">
                            <?php if (!$esModoPdfRgFm39): ?>
                                <a href="/Cycsa/publico/remisiones/pdf?id=<?= (int)$remV['id'] ?>" target="_blank" style="color: #1F3864; font-weight: 600;">Ver PDF Remisión</a>
                            <?php else: ?>
                                <?= htmlspecialchars((string)$remV['codigo_remision'], ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td>Remisión de entrega de informes (CYCSA-RG-FM-41)</td>
                        <td style="color: #6b7280;">Pendiente</td>
                        <td style="text-align: center; color: #6b7280;">—</td>
                    </tr>
                <?php endif; ?>
                <?php if (!empty($hojasServicioOs)): ?>
                    <?php foreach ($hojasServicioOs as $hsV): ?>
                    <tr>
                        <td>Hoja de Servicio / Recepción (CYCSA-RT-FM-13)</td>
                        <td style="font-family: monospace; font-weight: 600;">Reg. <?= htmlspecialchars((string)($hsV['numero_registro'] ?: ('#' . $hsV['id'])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align: center;">
                            <?php if (!$esModoPdfRgFm39): ?>
                                <a href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= (int)$os['id'] ?>&id_hoja=<?= (int)$hsV['id'] ?>" target="_blank" style="color: #1F3864; font-weight: 600;">PDF RT-FM-13</a>
                            <?php else: ?>
                                RT-FM-13 Reg. <?= htmlspecialchars((string)($hsV['numero_registro'] ?: ('#' . $hsV['id'])), ENT_QUOTES, 'UTF-8') ?>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                <tr>
                    <td>Solicitud / autorización del cliente</td>
                    <td><?= htmlspecialchars((string)(!empty($docsVincExtra['solicitud_cliente']) ? $docsVincExtra['solicitud_cliente'] : '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align: center;">—</td>
                </tr>
                <tr>
                    <td>Otros anexos / registros técnicos</td>
                    <td><?= htmlspecialchars((string)(!empty($docsVincExtra['otros_anexos']) ? $docsVincExtra['otros_anexos'] : '—'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align: center;">—</td>
                </tr>
            </tbody>
        </table>

        <!-- SECCIÓN 5: NOTAS Y CONDICIONES DEL SERVICIO -->
        <div class="os-sec-banner">NOTAS Y CONDICIONES DEL SERVICIO</div>
        <div class="os-notes-box">
            <?php foreach ($notasServicioLista as $notaServ): ?>
                <p><?= htmlspecialchars($notaServ, ENT_QUOTES, 'UTF-8') ?></p>
            <?php endforeach; ?>
            <?php if (!empty($condicionesList)): ?>
                <p style="margin-top: 4px;"><strong>Condiciones de las muestras:</strong> <?= htmlspecialchars(implode('; ', $condicionesList), ENT_QUOTES, 'UTF-8') ?></p>
            <?php endif; ?>
        </div>

        <!-- SECCIÓN 6: CONTACTOS DEL CLIENTE -->
        <div class="os-sec-banner">CONTACTOS DEL CLIENTE</div>
        <table class="os-table-border">
            <thead>
                <tr>
                    <th style="width: 38%; text-align: left;">Nombre</th>
                    <th style="width: 24%;">Teléfono</th>
                    <th style="width: 38%; text-align: left;">Correo electrónico</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($contactosNormalizados as $contRow): ?>
                <tr>
                    <td><?= htmlspecialchars($contRow['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($contRow['telefono'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($contRow['correo'], ENT_QUOTES, 'UTF-8') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <!-- SECCIÓN 7: AUTORIZACIÓN Y RECEPCIÓN AUTOMATIZADA (CYCSA-RG-FM-39 V1R1 — 4 COLUMNAS) -->
        <?php
        $fmtValOs = static function (?string $f): string {
            return \Cycsa\Modulos\OrdenesServicio\Servicios\ValidacionOrdenServicioServicio::formatearFechaHoraOficial($f);
        };
        $autAdmNom = trim((string)($os['autorizado_admin_nombre'] ?? ''));
        $autAdmFecTxt = $fmtValOs($os['autorizado_admin_fecha'] ?? null);
        $recLabNom = trim((string)($os['recibido_lab_nombre'] ?? ''));
        $recLabFecTxt = $fmtValOs($os['recibido_lab_fecha'] ?? null);
        $devSisFecTxt = $fmtValOs($os['devuelto_sistema_fecha'] ?? null);
        $alertaPlazoVenc = !empty($os['estado_ejecucion_alerta']);
        $recAdmFinNom = trim((string)($os['recibido_admin_final_nombre'] ?? ''));
        $recAdmFinFecTxt = $fmtValOs($os['recibido_admin_final_fecha'] ?? null);
        ?>
        <div class="os-sec-banner">AUTORIZACIÓN Y RECEPCIÓN</div>
        <table class="os-signatures-table">
            <thead>
                <tr>
                    <th colspan="2" class="os-sig-group">ENTREGA DE LA ORDEN</th>
                    <th colspan="2" class="os-sig-group">DEVOLUCIÓN DE LA ORDEN EJECUTADA</th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <!-- 1. Autorizado por Administración -->
                    <td>
                        <div class="os-sig-role">Autorizado por Administración</div>
                        <?php if ($autAdmFecTxt !== ''): ?>
                            <div><span class="os-sig-icon">&#10003;</span></div>
                            <div class="os-sig-badge-ok">Validado en sistema</div>
                            <div class="os-sig-line"><?= htmlspecialchars($autAdmNom !== '' ? $autAdmNom : 'Usuario de Administración', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="os-sig-sub"><?= htmlspecialchars($autAdmFecTxt, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php else: ?>
                            <div class="os-sig-badge-pend">Pendiente de autorización</div>
                            <div class="os-sig-line">&mdash;</div>
                            <div class="os-sig-sub">Sin fecha registrada</div>
                        <?php endif; ?>
                    </td>
                    <!-- 2. Recibido por Coordinación de Laboratorio y/o Recepción -->
                    <td>
                        <div class="os-sig-role">Recibido por Coordinación de Laboratorio y/o Recepción de Muestras y Atención al Cliente</div>
                        <?php if ($recLabFecTxt !== ''): ?>
                            <div><span class="os-sig-icon">&#10003;</span></div>
                            <div class="os-sig-badge-ok">Validado en sistema</div>
                            <div class="os-sig-line"><?= htmlspecialchars($recLabNom !== '' ? $recLabNom : 'Usuario de Laboratorio', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="os-sig-sub"><?= htmlspecialchars($recLabFecTxt, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php else: ?>
                            <div class="os-sig-badge-pend">Pendiente de recepción</div>
                            <div class="os-sig-line">&mdash;</div>
                            <div class="os-sig-sub">Sin fecha registrada</div>
                        <?php endif; ?>
                    </td>
                    <!-- 3. Devuelto automáticamente por el sistema -->
                    <td>
                        <div class="os-sig-role">Devuelto automáticamente por el sistema</div>
                        <?php if ($devSisFecTxt !== ''): ?>
                            <div><span class="os-sig-icon">&#9881;</span></div>
                            <div class="os-sig-badge-ok">Orden ejecutada</div>
                            <div class="os-sig-line">Flujo completado</div>
                            <div class="os-sig-sub"><?= htmlspecialchars($devSisFecTxt, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php elseif ($alertaPlazoVenc): ?>
                            <div class="os-sig-badge-warn">&#9888; Plazo vencido con pendientes</div>
                            <div class="os-sig-line">En verificación</div>
                            <div class="os-sig-sub">Requiere cierre de informes</div>
                        <?php else: ?>
                            <div class="os-sig-badge-pend">En ejecución</div>
                            <div class="os-sig-line">&mdash;</div>
                            <div class="os-sig-sub">Automático al completar criterio</div>
                        <?php endif; ?>
                    </td>
                    <!-- 4. Recibido por Administración (Definitiva) -->
                    <td>
                        <div class="os-sig-role">Recibido por Administración</div>
                        <?php if ($recAdmFinFecTxt !== ''): ?>
                            <div><span class="os-sig-icon">&#10003;</span></div>
                            <div class="os-sig-badge-ok">Validado en sistema</div>
                            <div class="os-sig-line"><?= htmlspecialchars($recAdmFinNom !== '' ? $recAdmFinNom : 'Usuario de Administración', ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="os-sig-sub"><?= htmlspecialchars($recAdmFinFecTxt, ENT_QUOTES, 'UTF-8') ?></div>
                        <?php else: ?>
                            <div class="os-sig-badge-pend"><?= $devSisFecTxt !== '' ? 'Pendiente de confirmación' : 'Pendiente de devolución' ?></div>
                            <div class="os-sig-line">&mdash;</div>
                            <div class="os-sig-sub">Sin fecha registrada</div>
                        <?php endif; ?>
                    </td>
                </tr>
            </tbody>
        </table>
        <div class="os-sig-footnote">Los nombres, fechas y horas se obtienen automáticamente del sistema con trazabilidad inmutable (CYCSA-RG-FM-39 V1R1).</div>

        <!-- PIE DE PÁGINA INSTITUCIONAL -->
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

<!-- HISTORIAL INMUTABLE DE VALIDACIONES Y TRAZABILIDAD (CYCSA-RG-FM-39 V1R1) -->
<?php $historialValOs = $historial_validaciones ?? []; ?>
<div class="no-print" style="width: 100%; max-width: 100%; margin: 16px 0 16px 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 18px 22px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px; flex-wrap:wrap; gap:8px;">
        <h3 style="margin: 0; font-size: 15px; color: #1F3864; font-weight: 600;">
            <i class="fa-solid fa-shield-halved"></i> Historial de Validaciones y Trazabilidad (CYCSA-RG-FM-39 V1R1)
        </h3>
        <span class="cy-pill cy-pill-pend"><?= count($historialValOs) ?> registro(s)</span>
    </div>
    <?php if (!empty($historialValOs)): ?>
    <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
        <thead>
            <tr style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
                <th style="padding:8px 10px; text-align:left; width:155px;">Fecha y Hora</th>
                <th style="padding:8px 10px; text-align:left; width:150px;">Campo / Acción</th>
                <th style="padding:8px 10px; text-align:left; width:170px;">Usuario / Cargo</th>
                <th style="padding:8px 10px; text-align:left; width:145px;">Cambio Estado</th>
                <th style="padding:8px 10px; text-align:left;">Detalle / Motivo</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($historialValOs as $hVal): ?>
            <tr style="border-bottom:1px solid #f1f5f9;">
                <td style="padding:8px 10px; color:#4b5563;"><?= htmlspecialchars(\Cycsa\Modulos\OrdenesServicio\Servicios\ValidacionOrdenServicioServicio::formatearFechaHoraOficial($hVal['fecha_registro'] ?? null), ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:8px 10px;"><strong><?= htmlspecialchars((string)($hVal['campo_validacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong><br><span style="font-size:11px; color:#64748b;"><?= htmlspecialchars((string)($hVal['accion'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                <td style="padding:8px 10px;"><strong><?= htmlspecialchars((string)($hVal['usuario_nombre'] ?? 'Sistema'), ENT_QUOTES, 'UTF-8') ?></strong><br><span style="font-size:11px; color:#64748b;"><?= htmlspecialchars((string)($hVal['cargo_rol'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></td>
                <td style="padding:8px 10px; font-size:11.5px; color:#334155;"><?= htmlspecialchars((string)($hVal['estado_anterior'] ?? '—'), ENT_QUOTES, 'UTF-8') ?> &rarr; <strong><?= htmlspecialchars((string)($hVal['estado_nuevo'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong></td>
                <td style="padding:8px 10px; color:#374151;">
                    <?= htmlspecialchars((string)($hVal['motivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                    <?php if (!empty($hVal['valor_anterior'])): ?>
                        <div style="font-size:11px; color:#6b7280; margin-top:2px;">Anterior: <?= htmlspecialchars((string)$hVal['valor_anterior'], ENT_QUOTES, 'UTF-8') ?> &rarr; Nuevo: <?= htmlspecialchars((string)($hVal['valor_nuevo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="margin:0; font-size:13px; color:#6b7280;">Aún no se han registrado validaciones digitales en esta Orden de Servicio.</p>
    <?php endif; ?>
</div>

<?php if (!empty($puede_corregir_validacion)): ?>
<div id="modalCorreccionValidacionOS" class="no-print" style="display:none; position:fixed; inset:0; background:rgba(15,23,42,0.55); z-index:9999; align-items:center; justify-content:center; padding:16px;">
    <div style="background:#fff; border-radius:10px; width:100%; max-width:500px; padding:20px 24px; box-shadow:0 20px 40px rgba(0,0,0,0.2);">
        <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #e2e8f0; padding-bottom:10px; margin-bottom:14px;">
            <h4 style="margin:0; font-size:15px; font-weight:700; color:#1F3864;"><i class="fa-solid fa-pen-ruler me-1"></i> Corrección Autorizada de Validación</h4>
            <button type="button" onclick="document.getElementById('modalCorreccionValidacionOS').style.display='none'" style="background:none; border:none; font-size:20px; cursor:pointer; color:#64748b;">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/ordenes-servicio/corregir-validacion">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($csrfToken ?? ($_SESSION['csrf_token'] ?? '')), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_os" value="<?= (int)$os['id'] ?>">
            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px;">Campo de validación a corregir *</label>
                <select name="campo_validacion" required style="width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                    <option value="autorizado_admin">1. Autorizado por Administración</option>
                    <option value="recibido_lab">2. Recibido por Laboratorio / Recepción</option>
                    <option value="recibido_admin_final">4. Recibido por Administración (Definitiva)</option>
                </select>
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px;">Usuario responsable corregido *</label>
                <select name="nuevo_usuario_id" required style="width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;">
                    <?php foreach (($usuarios_activos_os ?? []) as $uAct): ?>
                        <option value="<?= (int)$uAct['id'] ?>" <?= ((int)$uAct['id'] === (int)($_SESSION['usuario_id'] ?? 0)) ? 'selected' : '' ?>>
                            <?= htmlspecialchars((string)$uAct['nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom:14px;">
                <label style="display:block; font-size:12.5px; font-weight:600; margin-bottom:4px;">Motivo obligatorio de la corrección (queda en historial) *</label>
                <textarea name="motivo" required rows="3" placeholder="Explique el motivo autorizado para la corrección..." style="width:100%; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;"></textarea>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:8px;">
                <button type="button" onclick="document.getElementById('modalCorreccionValidacionOS').style.display='none'" class="btn-cycsa btn-cycsa-secondary">Cancelar</button>
                <button type="submit" class="btn-cycsa btn-cycsa-primary">Registrar Corrección con Trazabilidad</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<!-- HISTORIAL DE VERSIONES DE LA O/S (2.5) -->
<?php $historialVersionesOs = $os['historial_versiones'] ?? []; ?>
<div class="no-print" style="width: 100%; max-width: 100%; margin: 16px 0 16px 0; background: #fff; border: 1px solid #e5e7eb; border-radius: 10px; padding: 18px 22px;">
    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:12px;">
        <h3 style="margin: 0; font-size: 15px; color: #1F3864; font-weight: 600;">
            <i class="fa-solid fa-code-branch"></i> Historial de Versiones de la O/S
        </h3>
        <span class="cy-pill cy-pill-pend">
            Versión Vigente: v<?= (int)($os['version'] ?? 1) ?>
        </span>
    </div>
    <?php if (!empty($os['motivo_cambio_version'])): ?>
        <div class="cy-note mb-3">
            <strong>Último motivo de modificación:</strong> <?= htmlspecialchars((string)$os['motivo_cambio_version'], ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($historialVersionesOs)): ?>
    <table style="width:100%; border-collapse:collapse; font-size:13px;">
        <thead>
            <tr style="background:#f9fafb; border-bottom:1px solid #e5e7eb;">
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
                <td style="padding:8px 10px;"><span class="cy-pill cy-pill-pend">v<?= (int)$verOs['version'] ?></span></td>
                <td style="padding:8px 10px; color:#4b5563;"><?= !empty($verOs['created_at']) ? date('d/m/Y H:i', strtotime($verOs['created_at'])) : '—' ?></td>
                <td style="padding:8px 10px; font-weight:600; color:#111827;"><?= htmlspecialchars((string)($verOs['usuario_nombre'] ?? 'Sistema'), ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:8px 10px; color:#374151;"><?= htmlspecialchars((string)($verOs['motivo_cambio'] ?: 'Modificación registrada'), ENT_QUOTES, 'UTF-8') ?></td>
                <td style="padding:8px 10px; color:#6b7280; font-size:12px;">
                    Proyecto: <strong><?= htmlspecialchars((string)($snapOs['nombre_proyecto'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></strong>
                    &bull; Atención: <?= htmlspecialchars((string)($snapOs['atencion_a'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                    &bull; Pago: <?= htmlspecialchars((string)($snapOs['forma_pago'] ?? '—'), ENT_QUOTES, 'UTF-8') ?>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php else: ?>
        <p style="margin:0; font-size:13px; color:#6b7280;">Esta Orden de Servicio se encuentra en su versión inicial (v<?= (int)($os['version'] ?? 1) ?>) sin revisiones anteriores archivadas.</p>
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
