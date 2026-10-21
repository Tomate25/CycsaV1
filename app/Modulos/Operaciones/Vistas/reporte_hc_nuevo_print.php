<?php
/**
 * Vista de Impresión Oficial para los 5 Formatos Nuevos de Hojas de Cálculo sin equivalente RT-FM-22:
 * - RT-FM-46 (CBR de Laboratorio - AASHTO T193 / ASTM D1883)
 * - RT-FM-56 (Certificado de Calibración de Picnómetros - ASTM D854)
 * - RT-FM-58 (Sanidad de Agregados por Intemperismo Acelerado - ASTM C88)
 * - RT-FM-62 (Prueba de Infiltración / Percolación de Suelo)
 * - RT-FM-64 (Ensayo de Tensión en Varillas de Acero de Refuerzo - ASTM A370 / A615)
 */
$reporte = $reporte ?? [];
$codigoReporte = htmlspecialchars($reporte['codigo_reporte_nuevo'] ?? 'CYCSA-RT-FM-22-NUEVO');
$tituloReporte = htmlspecialchars($reporte['titulo_reporte'] ?? 'INFORME TÉCNICO DE ENSAYO');
$codigoHc = htmlspecialchars($reporte['codigo_formato_hc'] ?? '');
$norma = htmlspecialchars($reporte['norma'] ?? 'ISO/IEC 17025:2017');
$columnas = $reporte['columnas'] ?? [];
$filas = $reporte['filas'] ?? [];
$agregaciones = $reporte['agregaciones'] ?? [];
$meta = $reporte['metadatos'] ?? [];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= $codigoReporte ?> - <?= $tituloReporte ?></title>
    <style>
        @page { size: letter landscape; margin: 12mm; }
        body { font-family: Arial, Helvetica, sans-serif; font-size: 11px; color: #1e293b; margin: 0; padding: 0; }
        .header-box { border: 2px solid #103487; border-radius: 6px; padding: 12px 16px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; }
        .brand { font-size: 16px; font-weight: 800; color: #103487; }
        .subbrand { font-size: 10px; color: #475569; margin-top: 2px; }
        .doc-meta { text-align: right; font-size: 10.5px; }
        .doc-title { background: #103487; color: #fff; text-align: center; font-weight: 700; padding: 7px 10px; border-radius: 4px; margin-bottom: 12px; font-size: 12px; letter-spacing: 0.4px; }
        .meta-grid { display: grid; grid-template-columns: repeat(4, 1fr); gap: 8px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 4px; padding: 10px; margin-bottom: 12px; }
        table.results { width: 100%; border-collapse: collapse; margin-bottom: 14px; }
        table.results th { background: #e2e8f0; color: #0f172a; font-weight: 700; border: 1px solid #94a3b8; padding: 6px 8px; text-align: center; font-size: 10.5px; }
        table.results td { border: 1px solid #cbd5e1; padding: 6px 8px; text-align: center; font-size: 10.5px; }
        .summary-box { border: 1px solid #94a3b8; border-radius: 4px; padding: 10px 14px; background: #f1f5f9; margin-bottom: 16px; }
        .signatures { display: grid; grid-template-columns: repeat(2, 1fr); gap: 40px; margin-top: 36px; text-align: center; }
        .sig-line { border-top: 1px solid #334155; padding-top: 6px; width: 70%; margin: 0 auto; font-weight: 600; }
        @media print { .no-print { display: none !important; } }
    </style>
</head>
<body>
    <div class="no-print" style="padding: 10px; background: #0f172a; color: #fff; text-align: right;">
        <button onclick="window.print()" style="padding: 6px 14px; background: #2563eb; color: #fff; border: none; border-radius: 4px; cursor: pointer; font-weight: bold;">Imprimir Informe Oficial</button>
    </div>

    <div class="header-box">
        <div>
            <div class="brand">CONSULTORÍA Y CONSTRUCCIÓN S.A. (CYCSA)</div>
            <div class="subbrand">Laboratorio de Control de Calidad de Materiales y Suelos &bull; ISO/IEC 17025:2017</div>
        </div>
        <div class="doc-meta">
            <div><strong>Código Formato:</strong> <?= $codigoReporte ?></div>
            <div><strong>Hoja Cálculo Origen:</strong> <?= $codigoHc ?></div>
            <div><strong>Norma de Referencia:</strong> <?= $norma ?></div>
        </div>
    </div>

    <div class="doc-title"><?= $tituloReporte ?></div>

    <div class="meta-grid">
        <div><strong>Código Muestra:</strong> <?= htmlspecialchars((string)($meta['codigo_muestra'] ?? 'MS-0001-26')) ?></div>
        <div><strong>Fecha de Ensayo:</strong> <?= htmlspecialchars((string)($meta['fecha_ensayo'] ?? date('Y-m-d'))) ?></div>
        <div><strong>Proyecto:</strong> <?= htmlspecialchars((string)($meta['nombre_proyecto'] ?? 'Control de Calidad Laboratorio')) ?></div>
        <div><strong>Cliente:</strong> <?= htmlspecialchars((string)($meta['cliente_nombre'] ?? 'CYCSA LIMS')) ?></div>
    </div>

    <table class="results">
        <thead>
            <tr>
                <?php foreach ($columnas as $col): ?>
                    <th><?= htmlspecialchars((string)$col) ?></th>
                <?php endforeach; ?>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($filas as $fila): ?>
                <tr>
                    <?php foreach ($columnas as $col): ?>
                        <td><?= htmlspecialchars((string)($fila[$col] ?? '-')) ?></td>
                    <?php endforeach; ?>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <?php if (!empty($agregaciones)): ?>
        <div class="summary-box">
            <strong>Resumen y Agregaciones del Ensayo:</strong>
            <ul style="margin: 6px 0 0 18px; padding: 0;">
                <?php foreach ($agregaciones as $k => $v): ?>
                    <li>
                        <strong><?= htmlspecialchars((string)$k) ?>:</strong>
                        <?= is_scalar($v) ? htmlspecialchars((string)(is_float($v) ? round($v, 3) : $v)) : htmlspecialchars(json_encode($v, JSON_UNESCAPED_UNICODE)) ?>
                    </li>
                <?php endforeach; ?>
            </ul>
        </div>
    <?php endif; ?>

    <div class="signatures">
        <div><div class="sig-line">Elaborado / Analista de Laboratorio</div></div>
        <div><div class="sig-line">Revisado y Aprobado / Responsable de Calidad</div></div>
    </div>
</body>
</html>
