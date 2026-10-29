<?php
// Vista oficial del documento ORDEN DE SERVICIO (CYCSA-RG-FM-39 V1)
$contactos = [];
if (!empty($os['contactos_json'])) {
    $contactos = is_array($os['contactos_json']) ? $os['contactos_json'] : (json_decode($os['contactos_json'], true) ?: []);
}
?>
<style>
    .os-wrapper { background: #f1f5f9; padding: 35px 20px; min-height: 100vh; }
    .os-container { 
        max-width: 960px; 
        margin: 0 auto; 
        background: white; 
        padding: 50px 60px; 
        border-radius: 8px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.08); 
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
        color: #111; 
        position: relative;
        line-height: 1.5;
        font-size: 14px;
    }
    
    .os-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 28px; border-bottom: 2px solid #103487; padding-bottom: 18px; }
    .os-logo-box { display: flex; align-items: center; gap: 14px; }
    .os-logo-box img { max-height: 70px; object-fit: contain; }
    
    .os-header-right { text-align: right; }
    .os-main-title { font-size: 24px; font-weight: 800; color: #103487; letter-spacing: 0.5px; margin: 0; }
    .os-doc-code { font-size: 14px; font-weight: 700; color: #333; margin-top: 4px; }
    
    /* Rejilla de Información de la O/S (Escala 100% Nítida) */
    .os-meta-grid { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 8px 28px; 
        font-size: 14px; 
        margin-bottom: 28px; 
    }
    .os-meta-row { display: flex; gap: 10px; }
    .os-meta-label { font-weight: 700; color: #000; min-width: 120px; }
    .os-meta-value { color: #1e293b; font-weight: 400; flex: 1; }

    /* Tabla de Ensayos CYCSA-RG-FM-39 V1 */
    .os-table-border { width: 100%; border-collapse: collapse; margin-bottom: 28px; font-size: 14px; border: 1.5px solid #000; }
    .os-table-border th { background: #ffffff; color: #000; font-weight: 700; text-align: center; padding: 10px 12px; border: 1px solid #000; font-size: 13.5px; text-transform: uppercase; }
    .os-table-border td { padding: 10px 14px; border: 1px solid #000; font-size: 13.5px; }

    /* Notas y Contactos */
    .os-section-notes { font-size: 13px; color: #111; line-height: 1.6; margin-bottom: 35px; }
    .os-section-notes p { margin: 0 0 6px 0; }
    
    .os-contacts-list { margin-top: 12px; margin-bottom: 16px; font-size: 13.5px; }
    .os-contacts-list div { margin-bottom: 4px; }

    /* Firmas Oficiales de la O/S */
    .os-signatures-grid { 
        display: grid; 
        grid-template-columns: repeat(4, 1fr); 
        gap: 20px; 
        margin-top: 55px; 
        margin-bottom: 30px;
        text-align: center; 
        font-size: 12px; 
        color: #111; 
    }
    .os-sig-col { border-top: 1.5px solid #000; padding-top: 8px; font-weight: 600; min-height: 65px; display: flex; flex-direction: column; justify-content: flex-start; }

    /* Pie de página Oficial */
    .os-footer-line { border-top: 3px solid #103487; margin-top: 35px; padding-top: 12px; display: flex; justify-content: space-between; font-size: 11.5px; color: #103487; font-weight: 600; line-height: 1.45; }

    .btn-action-bar { max-width: 960px; margin: 0 auto 18px auto; display: flex; justify-content: space-between; align-items: center; }
    .btn-cycsa { display: inline-flex; align-items: center; gap: 8px; border: 1px solid transparent; min-height: 38px; padding: 8px 18px; border-radius: 6px; font-size: 13.5px; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .btn-cycsa-primary { background: var(--cycsa-azul); color: white; }
    .btn-cycsa-primary:hover { background: #0c2766; color: white; }
    .btn-cycsa-secondary { background: white; color: #334155; border-color: #cbd5e1; }
    .btn-cycsa-secondary:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }

    /* MANTENER @MEDIA PRINT 100% INTACTO PARA IMPRESIÓN Y PDF */
    @media print {
        .no-print { display: none !important; }
        .os-wrapper { padding: 0 !important; background: white !important; min-height: auto !important; }
        .os-container { box-shadow: none !important; padding: 0 !important; max-width: 100% !important; font-size: 11.5px !important; }
        .os-header { margin-bottom: 15px !important; padding-bottom: 10px !important; }
        .os-meta-grid { font-size: 11.5px !important; gap: 4px 15px !important; margin-bottom: 15px !important; }
        .os-table-border { font-size: 11px !important; margin-bottom: 15px !important; }
        .os-table-border th, .os-table-border td { font-size: 11px !important; padding: 5px 8px !important; }
        .os-section-notes { font-size: 10.5px !important; margin-bottom: 20px !important; }
        .os-signatures-grid { font-size: 10px !important; margin-top: 35px !important; margin-bottom: 15px !important; gap: 10px !important; }
        .os-footer-line { font-size: 9.5px !important; margin-top: 20px !important; }
        @page { margin: 12mm 15mm; }
    }
</style>

<div class="os-wrapper">
    <!-- BARRA SUPERIOR DE ACCIONES -->
    <div class="btn-action-bar no-print">
        <a href="/Cycsa/publico/ordenes-servicio" class="btn-cycsa btn-cycsa-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver a Órdenes
        </a>
        <div style="display: flex; gap: 10px;">
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
    <div class="os-container">
        <!-- ENCABEZADO -->
        <div class="os-header">
            <div class="os-logo-box">
                <img src="/Cycsa/publico/img/logo.png" alt="CYCSA" onerror="this.src='/Cycsa/publico/img/logo_cycsa.png'; this.onerror=null;">
            </div>
            <div class="os-header-right">
                <h1 class="os-main-title">ORDEN DE SERVICIO</h1>
                <div class="os-doc-code">Cód. Doc CYCSA-RG-FM-39 V1</div>
            </div>
        </div>

        <!-- METADATOS DE LA ORDEN DE SERVICIO -->
        <div class="os-meta-grid">
            <div class="os-meta-row">
                <span class="os-meta-label">Elaborado por:</span>
                <span class="os-meta-value"><?= htmlspecialchars($os['elaborado_por'] ?: (($os['cotizacion_creador_nombre'] ?? '') ?: 'Personal Autorizado'), ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="os-meta-row">
                <span class="os-meta-label">Doc. Número:</span>
                <span class="os-meta-value"><?= htmlspecialchars($os['codigo_os']) ?></span>
            </div>

            <div class="os-meta-row">
                <span class="os-meta-label">Cliente:</span>
                <span class="os-meta-value"><?= htmlspecialchars($os['cliente_nombre']) ?></span>
            </div>
            <div class="os-meta-row">
                <span class="os-meta-label">Fecha:</span>
                <span class="os-meta-value"><?= date('Y-m-d', strtotime($os['fecha_emision'])) ?></span>
            </div>

            <div class="os-meta-row">
                <span class="os-meta-label">Atención a :</span>
                <span class="os-meta-value"><?= htmlspecialchars($os['atencion_a'] ?: 'N/A') ?></span>
            </div>
            <div class="os-meta-row">
                <span class="os-meta-label">Cédula/RUC:</span>
                <span class="os-meta-value"><?= htmlspecialchars($os['cliente_rfc'] ?: 'N/A') ?></span>
            </div>

            <div class="os-meta-row">
                <span class="os-meta-label">Proyecto:</span>
                <span class="os-meta-value"><?= htmlspecialchars($os['nombre_proyecto']) ?></span>
            </div>
            <div class="os-meta-row">
                <span class="os-meta-label">Forma de pago:</span>
                <span class="os-meta-value"><?= htmlspecialchars($os['forma_pago'] ?: 'Pago contra entrega.') ?></span>
            </div>

            <div class="os-meta-row">
                <span class="os-meta-label">Correo electrónico:</span>
                <span class="os-meta-value"><?= htmlspecialchars($os['cliente_email'] ?: 'admon@cycsanic.com') ?></span>
            </div>
            <div class="os-meta-row">
                <span class="os-meta-label">Teléfono:</span>
                <span class="os-meta-value"><?= htmlspecialchars($os['cliente_telefono'] ?: '(505) 8209-6275') ?></span>
            </div>
        </div>

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

        <?php $informesVinculados = $os['informes_vinculados'] ?? []; ?>
        <table class="os-table-border">
            <thead>
                <tr>
                    <th>Informe vinculado</th>
                    <th style="width: 140px;">Estado</th>
                    <th style="width: 140px;">Documento</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($informesVinculados)): ?>
                    <?php foreach ($informesVinculados as $informe): ?>
                        <?php
                        $informeAprobado = (int)($informe['aprobado'] ?? 0) === 1;
                        $urlInforme = $informeAprobado ? trim((string)($informe['url_pdf'] ?? '')) : '';
                        ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 700;">
                                <?= htmlspecialchars((string)($informe['codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td style="text-align: center;">
                                <?= htmlspecialchars((string)($informe['estado'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($urlInforme !== ''): ?>
                                    <a href="<?= htmlspecialchars($urlInforme, ENT_QUOTES, 'UTF-8') ?>">Abrir PDF</a>
                                <?php else: ?>
                                    —
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="3">Sin informes vinculados.</td>
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

        <!-- FIRMAS -->
        <div class="os-signatures-grid">
            <div class="os-sig-col">
                Aprobado por Administración<br>Fecha:
            </div>
            <div class="os-sig-col">
                Recibido Por Recepción<br>Fecha:
            </div>
            <div class="os-sig-col">
                Entrega orden de servicio realizada<br>Recepción<br>Fecha:
            </div>
            <div class="os-sig-col">
                Recibe orden de servicio realizada<br>Administración<br>Fecha:
            </div>
        </div>

        <!-- PIE DE PÁGINA -->
        <div class="os-footer-line">
            <div>
                <strong>Consultoría y Construcción S.A (CYCSA)</strong><br>
                <i class="fa-solid fa-location-dot"></i> Km. 83 1/2 Carretera León - Managua, León, Nicaragua
            </div>
            <div>
                (505) 2310-3988 / (505) 8851-6377<br>
                gerencia@cycsanic.com | admon@cycsanic.com | calidad@cycsanic.com
            </div>
            <div style="text-align: right;">
                Lic. MTI No. 6693<br>
                www.cycsanic.com
            </div>
        </div>
    </div>
</div>
<?php if (!empty($mostrar_bitacora)): ?>
<div class="no-print" style="max-width: 960px; margin: 8px auto 40px auto;">
<?php
$bitacora_modulo_nombre = $bitacora_modulo_nombre ?? ('Orden de Servicio ' . ($os['codigo_os'] ?? ''));
$bitacora_limite = $bitacora_limite ?? 200;
$bitacora_abierta = $bitacora_abierta ?? false;
require dirname(__DIR__, 3) . '/Views/parciales/bitacora_modulo.php';
?>
</div>
<?php endif; ?>
