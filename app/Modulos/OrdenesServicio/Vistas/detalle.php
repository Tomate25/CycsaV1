<?php
// Vista oficial del documento ORDEN DE SERVICIO (CYCSA-RG-FM-39 V1)
$contactos = [];
if (!empty($os['contactos_json'])) {
    $contactos = is_array($os['contactos_json']) ? $os['contactos_json'] : (json_decode($os['contactos_json'], true) ?: []);
}
?>
<style>
    .os-wrapper { background: #f8fafc; padding: 30px 15px; }
    .os-container { 
        max-width: 850px; 
        margin: 0 auto; 
        background: white; 
        padding: 45px 50px; 
        border-radius: 4px; 
        box-shadow: 0 10px 30px rgba(0,0,0,0.06); 
        font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; 
        color: #111; 
        position: relative;
        line-height: 1.4;
    }
    
    .os-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 25px; }
    .os-logo-box { display: flex; align-items: center; gap: 12px; }
    .os-logo-box img { max-height: 65px; object-fit: contain; }
    
    .os-header-right { text-align: right; }
    .os-main-title { font-size: 22px; font-weight: 800; color: #103487; letter-spacing: 0.5px; margin: 0; }
    .os-doc-code { font-size: 13px; font-weight: 700; color: #333; margin-top: 3px; }
    
    /* Rejilla de Información de la O/S */
    .os-meta-grid { 
        display: grid; 
        grid-template-columns: 1fr 1fr; 
        gap: 6px 20px; 
        font-size: 13px; 
        margin-bottom: 25px; 
    }
    .os-meta-row { display: flex; gap: 8px; }
    .os-meta-label { font-weight: 700; color: #000; min-width: 110px; }
    .os-meta-value { color: #222; font-weight: 400; flex: 1; }

    /* Tabla de Ensayos CYCSA-RG-FM-39 V1 */
    .os-table-border { width: 100%; border-collapse: collapse; margin-bottom: 25px; font-size: 13px; border: 1.5px solid #000; }
    .os-table-border th { background: #ffffff; color: #000; font-weight: 700; text-align: center; padding: 8px; border: 1px solid #000; font-size: 13px; }
    .os-table-border td { padding: 8px 12px; border: 1px solid #000; font-size: 13px; }

    /* Notas y Contactos */
    .os-section-notes { font-size: 12px; color: #111; line-height: 1.5; margin-bottom: 35px; }
    .os-section-notes p { margin: 0 0 6px 0; }
    
    .os-contacts-list { margin-top: 10px; margin-bottom: 15px; }
    .os-contacts-list div { margin-bottom: 3px; }

    /* Firmas Oficiales de la O/S */
    .os-signatures-grid { 
        display: grid; 
        grid-template-columns: repeat(4, 1fr); 
        gap: 15px; 
        margin-top: 50px; 
        margin-bottom: 30px;
        text-align: center; 
        font-size: 11px; 
        color: #111; 
    }
    .os-sig-col { border-top: 1px solid #000; padding-top: 6px; font-weight: 500; min-height: 60px; display: flex; flex-direction: column; justify-content: flex-start; }

    /* Pie de página Oficial */
    .os-footer-line { border-top: 3px solid #103487; margin-top: 35px; padding-top: 10px; display: flex; justify-content: space-between; font-size: 10.5px; color: #103487; font-weight: 600; line-height: 1.4; }

    .btn-action-bar { max-width: 850px; margin: 0 auto 15px auto; display: flex; justify-content: space-between; align-items: center; }
    .btn-cycsa { display: inline-flex; align-items: center; gap: 6px; border: 1px solid transparent; padding: 8px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .btn-cycsa-primary { background: var(--cycsa-azul); color: white; }
    .btn-cycsa-primary:hover { background: #0c2766; color: white; }
    .btn-cycsa-secondary { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .btn-cycsa-secondary:hover { background: #cbd5e1; color: #0f172a; }
    .btn-cycsa-warning { background: #f59e0b; color: white; }
    .btn-cycsa-warning:hover { background: #d97706; color: white; }
    .btn-cycsa-success { background: #10b981; color: white; }
    .btn-cycsa-success:hover { background: #059669; color: white; }

    @media print {
        .no-print { display: none !important; }
        .os-wrapper { padding: 0; background: white; }
        .os-container { box-shadow: none; padding: 0; max-width: 100%; }
        @page { margin: 15mm; }
    }
</style>

<div class="os-wrapper">
    <!-- BARRA SUPERIOR DE ACCIONES -->
    <div class="btn-action-bar no-print">
        <a href="/Cycsa/publico/ordenes-servicio" class="btn-cycsa btn-cycsa-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver a Órdenes
        </a>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-cycsa btn-cycsa-primary">
                <i class="fa-solid fa-print"></i> Imprimir Orden de Servicio
            </button>
            <?php if (!empty($os['requiere_muestreo']) && $os['estado'] === 'Pendiente de Muestreo'): ?>
                <a href="/Cycsa/publico/ordenes-servicio/programar-muestreo?id=<?= $os['id'] ?>" class="btn-cycsa btn-cycsa-warning">
                    <i class="fa-solid fa-calendar-days"></i> Logística de Muestreo
                </a>
            <?php else: ?>
                <a href="/Cycsa/publico/hojas-servicio?id_os=<?= $os['id'] ?>" class="btn-cycsa btn-cycsa-success">
                    <i class="fa-solid fa-file-circle-check"></i> Ir a Hoja de Servicio CYCSA RT-FM-13
                </a>
            <?php endif; ?>
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
                <span class="os-meta-value"><?= htmlspecialchars($os['elaborado_por'] ?: 'Tiana Grillo') ?></span>
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

            <div class="os-meta-row" style="grid-column: span 2;">
                <span class="os-meta-label">Proyecto:</span>
                <span class="os-meta-value"><?= htmlspecialchars($os['nombre_proyecto']) ?></span>
            </div>
            <div class="os-meta-row" style="grid-column: span 2;">
                <span class="os-meta-label">Forma de pago:</span>
                <span class="os-meta-value"><?= htmlspecialchars($os['forma_pago'] ?: 'Pago contra entrega.') ?></span>
            </div>
        </div>

        <!-- TABLA DE ENSAYOS / SERVICIOS -->
        <table class="os-table-border">
            <thead>
                <tr>
                    <th style="width: 80px;">Línea</th>
                    <th>Descripción</th>
                    <th style="width: 110px;">Cantidad</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($os['ensayos'])): ?>
                    <?php foreach ($os['ensayos'] as $idx => $ensayo): ?>
                        <tr>
                            <td style="text-align: center; font-weight: 700;"><?= $idx + 1 ?></td>
                            <td>
                                <strong><?= htmlspecialchars($ensayo['codigo_servicio'] ?? 'CYCSA-PE-07') ?></strong> <?= htmlspecialchars($ensayo['descripcion_ensayo'] ?? $ensayo['nombre_ensayo'] ?? '') ?>
                            </td>
                            <td style="text-align: center; font-weight: 700;">
                                <?= number_format($ensayo['cantidad'], 1) ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td style="text-align: center; font-weight: 700;">1</td>
                        <td>CYCSA-PE-07 Determinación de la resistencia del concreto.</td>
                        <td style="text-align: center; font-weight: 700;">1.0</td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- NOTAS Y CONDICIONES -->
        <div class="os-section-notes">
            <p><strong>Nota:</strong></p>
            <p>***Los informes de ensayo se entregan únicamente en formato digital (.PDF). Serán enviados al correo del contacto designado por el cliente.</p>
            <br>
            <p>Condiciones de las muestras: CYCSA-PE-07: Cilindros con dimensiones estándar de 4"X 8" ó 6" X 12" sin alteración física, en caso de encontrarse se notifica al cliente cualquier anomalía antes de proceder.</p>

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

            <p style="margin-top: 10px;">Cotización #<?= htmlspecialchars($os['cotizacion_codigo']) ?></p>
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
