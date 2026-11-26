<?php
// Vista oficial del documento CYCSA-RG-FM-41: Remisión de Entrega de Informes de Ensayos
$esModoPdfRgFm41 = !empty($esModoPdfRgFm41);
$remision = $remision ?? [];
$detalles = is_array($remision['detalles'] ?? null) ? $remision['detalles'] : [];
$csrfToken = $csrfToken ?? ($_SESSION['csrf_token'] ?? '');

// Resolver logo oficial en base64 para garantizar fidelidad 100% en Dompdf y pantalla
$logoSrcRgFm41 = '/Cycsa/publico/img/logo.png';
if ($esModoPdfRgFm41) {
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
                $logoSrcRgFm41 = 'data:' . $mimeLogo . ';base64,' . base64_encode($bytesLogo);
                break;
            }
        }
    }
}

$modalidadTexto = (($remision['modalidad_entrega'] ?? 'laboratorio') === 'domicilio')
    ? 'Entrega a Domicilio'
    : 'Entrega en Laboratorio';
$fechaEntregaFmt = !empty($remision['fecha_entrega'])
    ? date('d/m/Y H:i', strtotime((string)$remision['fecha_entrega']))
    : date('d/m/Y H:i');
$estadoRem = (string)($remision['estado'] ?? 'Emitida');
$pillClase = match ($estadoRem) {
    'Entregada' => 'cy-pill-ok',
    'Emitida' => 'cy-pill-warn',
    'Anulada' => 'cy-pill-danger',
    default => 'cy-pill-pend',
};
?>
<?php if ($esModoPdfRgFm41): ?>
<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<title>Remisión <?= htmlspecialchars((string)($remision['codigo_remision'] ?? ''), ENT_QUOTES, 'UTF-8') ?> — CYCSA-RG-FM-41</title>
<?php else: ?>
<link rel="stylesheet" href="/Cycsa/publico/css/cycsa-sobrio.css">
<link rel="stylesheet" href="/Cycsa/publico/css/remisiones-sobrio.css">
<?php endif; ?>
<style>
    @page { margin: 12mm 14mm; }
    <?php if ($esModoPdfRgFm41): ?>
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
    .rg41-paper {
        width: 100%;
        background: #ffffff;
        padding: 28px 36px;
        border: 1px solid #E5E7EB;
        border-radius: 10px;
        color: #111827;
        font-family: 'DejaVu Sans', Helvetica, Arial, sans-serif;
        font-size: 13px;
        line-height: 1.45;
        box-sizing: border-box;
    }
    .rg41-header-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16px;
        border-bottom: 2px solid #1F3864;
    }
    .rg41-header-table td {
        padding: 0 0 10px 0;
        vertical-align: middle;
        border: 0;
    }
    .rg41-logo-img {
        height: 56px;
        width: auto;
        display: block;
    }
    .rg41-main-title {
        font-size: 18px;
        font-weight: 700;
        color: #1F3864;
        margin: 0;
        letter-spacing: 0.2px;
    }
    .rg41-doc-code {
        font-size: 12px;
        font-weight: 700;
        color: #374151;
        margin-top: 4px;
    }
    .rg41-code-badge {
        display: inline-block;
        margin-top: 4px;
        padding: 2px 8px;
        border: 1px solid #1F3864;
        color: #1F3864;
        font-size: 12px;
        font-weight: 700;
        border-radius: 4px;
    }
    .rg41-meta-table {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16px;
        font-size: 12.5px;
        border: 1px solid #CBD5E1;
    }
    .rg41-meta-table td {
        padding: 6px 10px;
        border: 1px solid #CBD5E1;
        vertical-align: top;
    }
    .rg41-meta-k {
        width: 18%;
        background: #F9FAFB;
        font-weight: 700;
        color: #1F3864;
    }
    .rg41-meta-v {
        width: 32%;
        color: #111827;
    }
    .rg41-table-border {
        width: 100%;
        border-collapse: collapse;
        margin-bottom: 16px;
        font-size: 12.5px;
        border: 1.5px solid #111827;
    }
    .rg41-table-border th {
        background: #F3F4F6;
        color: #111827;
        font-weight: 700;
        text-align: left;
        padding: 7px 10px;
        border: 1px solid #111827;
        font-size: 11.5px;
        text-transform: uppercase;
    }
    .rg41-table-border td {
        padding: 7px 10px;
        border: 1px solid #111827;
        vertical-align: top;
    }
    .rg41-notes-box {
        border: 1px solid #CBD5E1;
        background: #F9FAFB;
        padding: 10px 12px;
        margin-bottom: 18px;
        font-size: 12px;
        color: #1F2937;
    }
    .rg41-anulada-banner {
        border: 1.5px solid #B91C1C;
        background: #FEF2F2;
        color: #991B1B;
        padding: 10px 14px;
        margin-bottom: 14px;
        font-size: 12.5px;
        font-weight: 700;
    }
    .rg41-signatures-table {
        width: 100%;
        border-collapse: separate;
        border-spacing: 16px 0;
        margin: 26px -16px 18px -16px;
        table-layout: fixed;
    }
    .rg41-signatures-table td {
        width: 50%;
        border: 1px solid #CBD5E1;
        padding: 10px 14px 12px 14px;
        vertical-align: top;
        font-size: 11.5px;
        color: #111827;
    }
    .rg41-sig-title {
        font-weight: 700;
        color: #1F3864;
        border-bottom: 1px solid #E5E7EB;
        padding-bottom: 5px;
        margin-bottom: 8px;
        text-transform: uppercase;
        font-size: 11px;
    }
    .rg41-sig-line {
        margin-top: 28px;
        border-top: 1px solid #111827;
        padding-top: 4px;
        text-align: center;
        font-size: 11px;
        color: #374151;
    }
    .rg41-footer-table {
        width: 100%;
        border-collapse: collapse;
        border-top: 2px solid #1F3864;
        margin-top: 18px;
        table-layout: fixed;
    }
    .rg41-footer-table td {
        padding: 8px 4px 0 4px;
        vertical-align: top;
        font-size: 10px;
        color: #1F3864;
        font-weight: 600;
        line-height: 1.35;
        border: 0;
    }
    @media print {
        .no-print { display: none !important; }
        .rg41-paper {
            border: 0 !important;
            border-radius: 0 !important;
            padding: 0 !important;
            font-size: 11px !important;
        }
        .rg41-logo-img { height: 48px !important; }
        .rg41-main-title { font-size: 16px !important; }
        .rg41-meta-table, .rg41-table-border { font-size: 10.5px !important; }
        .rg41-footer-table td { font-size: 9px !important; }
    }
</style>
<?php if ($esModoPdfRgFm41): ?>
</head>
<body>
<?php endif; ?>

<?php if (!$esModoPdfRgFm41): ?>
<div class="container-fluid px-0 py-0 cy-rem-wrap">
    <?php if (isset($_SESSION['exito'])): ?>
        <div class="cy-note mb-3 d-flex align-items-center gap-2 no-print" role="status">
            <span class="cy-pill cy-pill-ok">Listo</span>
            <span style="color: var(--cy-text);"><?= htmlspecialchars((string)$_SESSION['exito'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php unset($_SESSION['exito']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="cy-note mb-3 d-flex align-items-center gap-2 no-print" role="alert">
            <span class="cy-pill cy-pill-danger">Aviso</span>
            <span style="color: var(--cy-text);"><?= htmlspecialchars((string)$_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Barra sobria de acciones -->
    <div class="cy-rem-action-bar no-print">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <a href="/Cycsa/publico/remisiones" class="btn btn-outline-secondary btn-sm">&larr; Volver</a>
            <span class="fw-semibold"><?= htmlspecialchars((string)($remision['codigo_remision'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
            <span class="cy-pill <?= htmlspecialchars($pillClase, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($estadoRem, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div class="cy-rem-header-actions">
            <?php if ($estadoRem === 'Emitida'): ?>
                <button type="button" class="btn btn-primary btn-sm" data-bs-toggle="modal" data-bs-target="#modalMarcarEntregada">
                    Marcar Entregada
                </button>
            <?php endif; ?>
            <?php if ($estadoRem !== 'Anulada'): ?>
                <button type="button" class="btn-cy-danger-outline btn-sm" data-bs-toggle="modal" data-bs-target="#modalAnularRemision">
                    Anular
                </button>
            <?php endif; ?>
            <a href="/Cycsa/publico/remisiones/pdf?id=<?= (int)($remision['id'] ?? 0) ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                Descargar PDF Oficial
            </a>
            <button type="button" onclick="window.print()" class="btn btn-outline-secondary btn-sm">
                Imprimir
            </button>
        </div>
    </div>
<?php endif; ?>

    <div class="rg41-paper">
        <!-- Encabezado Oficial CYCSA-RG-FM-41 -->
        <table class="rg41-header-table">
            <tr>
                <td style="width: 34%; text-align: left;">
                    <img src="<?= htmlspecialchars($logoSrcRgFm41, ENT_QUOTES, 'UTF-8') ?>" alt="CYCSA" class="rg41-logo-img">
                </td>
                <td style="width: 66%; text-align: right;">
                    <h1 class="rg41-main-title">REMISIÓN DE ENTREGA DE INFORMES DE ENSAYOS</h1>
                    <div class="rg41-doc-code">Cód. Doc. CYCSA-RG-FM-41 &middot; Control PT-13 Ver. 5 Rev. 2 (ISO/IEC 17025:2017)</div>
                    <div class="rg41-code-badge">No. <?= htmlspecialchars((string)($remision['codigo_remision'] ?? ''), ENT_QUOTES, 'UTF-8') ?></div>
                </td>
            </tr>
        </table>

        <?php if ($estadoRem === 'Anulada'): ?>
        <div class="rg41-anulada-banner">
            DOCUMENTO ANULADO — Motivo: <?= htmlspecialchars((string)($remision['motivo_anulacion'] ?: 'Sin motivo especificado'), ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php endif; ?>

        <!-- Metadatos del Cliente y Entrega -->
        <table class="rg41-meta-table">
            <tr>
                <td class="rg41-meta-k">Cliente / Empresa:</td>
                <td class="rg41-meta-v"><?= htmlspecialchars((string)($remision['empresa_nombre'] ?: ($remision['cliente_razon_social'] ?? '')), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="rg41-meta-k">Fecha / Hora:</td>
                <td class="rg41-meta-v"><?= htmlspecialchars($fechaEntregaFmt, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td class="rg41-meta-k">Orden de Servicio:</td>
                <td class="rg41-meta-v"><?= htmlspecialchars((string)($remision['codigo_os'] ?: 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="rg41-meta-k">Modalidad:</td>
                <td class="rg41-meta-v"><?= htmlspecialchars($modalidadTexto, ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
            <tr>
                <td class="rg41-meta-k">Proyecto:</td>
                <td class="rg41-meta-v"><?= htmlspecialchars((string)($remision['nombre_proyecto'] ?: 'N/A'), ENT_QUOTES, 'UTF-8') ?></td>
                <td class="rg41-meta-k">Estado:</td>
                <td class="rg41-meta-v"><strong><?= htmlspecialchars($estadoRem, ENT_QUOTES, 'UTF-8') ?></strong></td>
            </tr>
            <tr>
                <td class="rg41-meta-k">Dirección Entrega:</td>
                <td class="rg41-meta-v" colspan="3"><?= htmlspecialchars((string)($remision['direccion_entrega'] ?: ($remision['cliente_direccion'] ?: 'Instalaciones Laboratorio CYCSA, León')), ENT_QUOTES, 'UTF-8') ?></td>
            </tr>
        </table>

        <!-- Tabla de Informes Entregados -->
        <table class="rg41-table-border">
            <thead>
                <tr>
                    <th style="width: 44px; text-align: center;">No.</th>
                    <th style="width: 190px;">Código de Informe</th>
                    <th>Descripción del Ensayo / Informe de Resultados</th>
                    <th style="width: 140px; text-align: center;">Formato de Entrega</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($detalles)): ?>
                    <?php foreach ($detalles as $idx => $det): ?>
                    <tr>
                        <td style="text-align: center; font-weight: 700;"><?= $idx + 1 ?></td>
                        <td style="font-weight: 700;"><?= htmlspecialchars((string)($det['codigo_informe'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars((string)($det['descripcion_ensayo'] ?: 'Informe de ensayo de laboratorio'), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align: center;"><?= htmlspecialchars((string)($det['formato_entrega'] ?? 'Digital PDF'), ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="4" style="text-align: center;">Sin informes registrados en esta remisión.</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Observaciones y Constancia -->
        <div class="rg41-notes-box">
            <div><strong>Observaciones:</strong> <?= htmlspecialchars((string)($remision['observaciones'] ?: 'Se hace entrega conforme de los informes detallados en la presente remisión.'), ENT_QUOTES, 'UTF-8') ?></div>
            <div style="margin-top: 6px; color: #4B5563;">
                Constancia: El receptor confirma haber recibido a entera satisfacción los informes de ensayos listados en este documento CYCSA-RG-FM-41. Los resultados corresponden únicamente a los ítems ensayados conforme a ISO/IEC 17025:2017.
            </div>
        </div>

        <!-- Cajas de Firmas Oficiales (Entregado por / Recibido por) -->
        <table class="rg41-signatures-table">
            <tr>
                <td>
                    <div class="rg41-sig-title">Entregado por (CYCSA)</div>
                    <div><strong>Nombre:</strong> <?= htmlspecialchars((string)($remision['entregado_por'] ?: ($remision['usuario_nombre'] ?: 'Personal Autorizado CYCSA')), ENT_QUOTES, 'UTF-8') ?></div>
                    <div><strong>Cargo / Área:</strong> Recepción / Coordinación de Laboratorio</div>
                    <div><strong>Fecha y Hora:</strong> <?= htmlspecialchars($fechaEntregaFmt, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="rg41-sig-line">Firma y Sello de Entrega</div>
                </td>
                <td>
                    <div class="rg41-sig-title">Recibido por (Cliente / Autorizado)</div>
                    <div><strong>Nombre:</strong> <?= htmlspecialchars((string)($remision['persona_recibe'] ?: '_________________________________'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div><strong>Cédula:</strong> <?= htmlspecialchars((string)($remision['cedula_recibe'] ?: '_________________________'), ENT_QUOTES, 'UTF-8') ?> &nbsp; <strong>Tel.:</strong> <?= htmlspecialchars((string)($remision['telefono_recibe'] ?: '________________'), ENT_QUOTES, 'UTF-8') ?></div>
                    <div><strong>Fecha y Hora:</strong> <?= $estadoRem === 'Entregada' ? htmlspecialchars($fechaEntregaFmt, ENT_QUOTES, 'UTF-8') : '____ / ____ / ______   ____:____' ?></div>
                    <div class="rg41-sig-line">Firma de Recibido Conforme</div>
                </td>
            </tr>
        </table>

        <!-- Pie de página Oficial CYCSA -->
        <table class="rg41-footer-table">
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

<?php if (!$esModoPdfRgFm41): ?>
    <!-- Modal: Marcar Entregada -->
    <div class="modal fade no-print" id="modalMarcarEntregada" tabindex="-1" aria-labelledby="modalMarcarEntregadaLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="/Cycsa/publico/remisiones/marcar-entregada">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int)($remision['id'] ?? 0) ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalMarcarEntregadaLabel">Confirmar Entrega de Remisión</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-3">
                            <label for="m_persona_recibe" class="form-label">Persona que recibe <span style="color: var(--cy-danger);">*</span></label>
                            <input type="text" class="form-control" id="m_persona_recibe" name="persona_recibe" required value="<?= htmlspecialchars((string)($remision['persona_recibe'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="m_cedula_recibe" class="form-label">Cédula / Identificación</label>
                            <input type="text" class="form-control" id="m_cedula_recibe" name="cedula_recibe" value="<?= htmlspecialchars((string)($remision['cedula_recibe'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-3">
                            <label for="m_telefono_recibe" class="form-label">Teléfono</label>
                            <input type="text" class="form-control" id="m_telefono_recibe" name="telefono_recibe" value="<?= htmlspecialchars((string)($remision['telefono_recibe'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                        </div>
                        <div class="mb-0">
                            <label for="m_fecha_entrega" class="form-label">Fecha y Hora de Recepción</label>
                            <input type="datetime-local" class="form-control" id="m_fecha_entrega" name="fecha_entrega" value="<?= date('Y-m-d\TH:i') ?>">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-primary btn-sm">Confirmar Entrega</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Anular Remisión -->
    <div class="modal fade no-print" id="modalAnularRemision" tabindex="-1" aria-labelledby="modalAnularRemisionLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <form method="POST" action="/Cycsa/publico/remisiones/anular">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= (int)($remision['id'] ?? 0) ?>">
                    <div class="modal-header">
                        <h5 class="modal-title" id="modalAnularRemisionLabel">Anular Remisión <?= htmlspecialchars((string)($remision['codigo_remision'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Cerrar"></button>
                    </div>
                    <div class="modal-body">
                        <div class="mb-0">
                            <label for="m_motivo_anulacion" class="form-label">Motivo de anulación <span style="color: var(--cy-danger);">*</span></label>
                            <textarea class="form-control" id="m_motivo_anulacion" name="motivo_anulacion" rows="3" required placeholder="Indique el motivo formal de anulación de esta remisión..."></textarea>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn-cy-danger-outline btn-sm">Confirmar Anulación</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
<?php else: ?>
</body>
</html>
<?php endif; ?>
