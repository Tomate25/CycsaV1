<?php
// Vista imprimible de la Lista de Chequeo Oficial CYCSA-RT-FM-40 B
$pm = is_array($os['programacion_muestreo'] ?? null) ? $os['programacion_muestreo'] : [];
$chk = !empty($pm['checklist_json']) ? json_decode($pm['checklist_json'], true) : [];

$logoBase64 = '';
$logoPath = __DIR__ . '/../../../../publico/img/logo.png';
if (file_exists($logoPath)) {
    $logoBase64 = 'data:image/png;base64,' . base64_encode(file_get_contents($logoPath));
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>CYCSA-RT-FM-40 B - Lista de Chequeo Muestreo <?= htmlspecialchars($os['codigo_os'] ?? '') ?></title>
    <style>
        @page {
            size: letter portrait;
            margin: 10mm 12mm;
        }
        @media print {
            .no-print { display: none !important; }
            body { padding: 0 !important; background: white !important; }
            .page-container { padding: 0 !important; box-shadow: none !important; }
        }
        @media screen {
            body {
                background: #f1f5f9;
                padding: 20px 10px;
            }
            .page-container {
                max-width: 850px;
                margin: 0 auto;
                background: white;
                padding: 30px;
                border-radius: 8px;
                box-shadow: 0 4px 20px rgba(0,0,0,0.1);
            }
        }
        body {
            font-family: 'Arial', sans-serif;
            font-size: 9.5pt;
            color: #000;
            margin: 0;
            padding: 0;
        }
        .header-table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .header-table td {
            border: 1.5px solid #000;
            padding: 4px 6px;
            vertical-align: middle;
        }
        .logo-img {
            max-height: 40px;
            display: block;
            margin: 0 auto;
        }
        .title-doc {
            text-align: center;
            font-size: 10pt;
            font-weight: bold;
            line-height: 1.2;
        }
        .meta-header-box {
            font-size: 8pt;
            line-height: 1.3;
        }
        .info-strip {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8.5pt;
        }
        .info-strip td {
            border: 1px solid #000;
            padding: 3px 6px;
        }
        .section-title {
            background: #e2e8f0;
            font-weight: bold;
            text-align: center;
            font-size: 8.5pt;
            padding: 3px 0;
            border: 1px solid #000;
            text-transform: uppercase;
        }
        .table-verif {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
            font-size: 8pt;
        }
        .table-verif th, .table-verif td {
            border: 1px solid #000;
            padding: 2.5px 5px;
            text-align: left;
        }
        .table-verif th {
            background: #f1f5f9;
            font-weight: bold;
            text-align: center;
        }
        .grid-check-tables {
            display: table;
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 8px;
        }
        .col-check-table {
            display: table-cell;
            width: 50%;
            vertical-align: top;
        }
        .col-check-table:first-child {
            padding-right: 4px;
        }
        .col-check-table:last-child {
            padding-left: 4px;
        }
        .table-items {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5pt;
        }
        .table-items th, .table-items td {
            border: 1px solid #000;
            padding: 2px 4px;
        }
        .table-items th {
            background: #f1f5f9;
            text-align: center;
            font-weight: bold;
        }
        .check-box-print {
            width: 12px;
            height: 12px;
            border: 1px solid #000;
            display: inline-block;
            text-align: center;
            line-height: 11px;
            font-weight: bold;
            font-size: 7.5pt;
        }
        .footer-obs {
            border: 1px solid #000;
            padding: 4px 6px;
            font-size: 8pt;
            min-height: 30px;
        }
        .no-print {
            margin-bottom: 12px;
            background: #103487;
            color: white;
            padding: 8px 16px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            border-radius: 6px;
        }
        @media print {
            .no-print { display: none; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="max-width: 850px; margin: 0 auto 15px auto; background: #1e293b; color: white; padding: 10px 16px; display: flex; justify-content: space-between; align-items: center; border-radius: 6px; box-shadow: 0 2px 8px rgba(0,0,0,0.15);">
        <div>
            <strong>Formato Oficial CYCSA-RT-FM-40 B</strong> - Lista de Chequeo para Muestreos de Compactación (DN)
        </div>
        <div>
            <button onclick="window.print()" style="background: #10b981; color: white; font-weight: bold; padding: 7px 16px; border: none; border-radius: 4px; cursor: pointer; font-size: 13px;">
                🖨️ Imprimir Formato
            </button>
            <button onclick="window.close()" style="background: #ef4444; color: white; font-weight: bold; padding: 7px 16px; border: none; border-radius: 4px; cursor: pointer; margin-left: 8px; font-size: 13px;">
                ✕ Cerrar
            </button>
        </div>
    </div>

    <div class="page-container">

    <!-- CABECERA INSTITUCIONAL CYCSA -->
    <table class="header-table">
        <tr>
            <td style="width: 22%; text-align: center;">
                <?php if (!empty($logoBase64)): ?>
                    <img src="<?= $logoBase64 ?>" alt="CYCSA" class="logo-img">
                <?php else: ?>
                    <img src="/Cycsa/img/logo.png" alt="CYCSA" class="logo-img" onerror="this.outerHTML='<strong>CYCSA</strong>'">
                <?php endif; ?>
            </td>
            <td style="width: 53%; text-align: center;">
                <div class="title-doc">LISTA DE CHEQUEO PARA MUESTREOS DE COMPACTACIÓN (DN)</div>
            </td>
            <td style="width: 25%;">
                <div class="meta-header-box">
                    <strong>Código:</strong> CYCSA-RT-FM-40 B<br>
                    <strong>Edición:</strong> 1<br>
                    <strong>Revisión:</strong> 2<br>
                    <strong>O/S:</strong> <?= htmlspecialchars($os['codigo_os'] ?? '') ?>
                </div>
            </td>
        </tr>
    </table>

    <!-- DATOS BÁSICOS DEL SERVICIO EN CAMPO -->
    <table class="info-strip">
        <tr>
            <td style="width: 50%;">
                <strong>Fecha de Muestreo:</strong> 
                <?= !empty($pm['fecha_ida']) ? date('d/m/Y H:i', strtotime($pm['fecha_ida'])) : '___/___/______' ?>
            </td>
            <td style="width: 50%;">
                <strong>Lugar de Muestreo:</strong> 
                <?= htmlspecialchars(!empty($pm['lugar_muestreo']) ? $pm['lugar_muestreo'] : ($os['nombre_proyecto'] ?? '')) ?>
            </td>
        </tr>
        <tr>
            <td>
                <strong>Cantidad de muestras:</strong> 
                <?= htmlspecialchars(!empty($pm['cantidad_muestras_est']) ? $pm['cantidad_muestras_est'] : '___') ?> Para pruebas de compactación
            </td>
            <td>
                <strong>No. de Personas (muestreadores):</strong> 
                <?= htmlspecialchars($pm['num_muestreadores'] ?? 1) ?> &nbsp;|&nbsp; 
                <strong>Técnico:</strong> <?= htmlspecialchars($pm['tecnico_nombre'] ?? 'Por asignar') ?>
            </td>
        </tr>
    </table>

    <!-- VERIFICACIÓN DE RECURSOS ANTES DEL MUESTREO -->
    <table class="table-verif">
        <thead>
            <tr>
                <th colspan="4" style="background:#e2e8f0; text-transform:uppercase;">VERIFICACIÓN DE RECURSOS ANTES DEL MUESTREO</th>
            </tr>
            <tr>
                <th style="width:30%;">Responsabilidad</th>
                <th style="width:30%;">Nombre</th>
                <th style="width:20%;">Fecha y hora</th>
                <th style="width:20%;">Firma</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Preparación de Materiales</td>
                <td><?= htmlspecialchars($chk['antes_prep_nombre'] ?? ($pm['tecnico_nombre'] ?? '')) ?></td>
                <td><?= htmlspecialchars($chk['antes_prep_fecha'] ?? '') ?></td>
                <td style="text-align:center;"><?= !empty($chk['antes_prep_firma']) ? 'Firmado digitalmente' : '_______________' ?></td>
            </tr>
            <tr>
                <td>Verificación de Materiales</td>
                <td><?= htmlspecialchars($chk['antes_verif_nombre'] ?? '') ?></td>
                <td><?= htmlspecialchars($chk['antes_verif_fecha'] ?? '') ?></td>
                <td style="text-align:center;"><?= !empty($chk['antes_verif_firma']) ? 'Firmado digitalmente' : '_______________' ?></td>
            </tr>
            <tr>
                <td>Recibe Materiales</td>
                <td><?= htmlspecialchars($chk['antes_recibe_nombre'] ?? ($pm['tecnico_nombre'] ?? '')) ?></td>
                <td><?= htmlspecialchars($chk['antes_recibe_fecha'] ?? '') ?></td>
                <td style="text-align:center;"><?= !empty($chk['antes_recibe_firma']) ? 'Firmado digitalmente' : '_______________' ?></td>
            </tr>
        </tbody>
    </table>

    <!-- TABLAS PARALELAS: PROTECCIÓN/SEGURIDAD Y TOMA DE MUESTRAS -->
    <div class="grid-check-tables">
        <!-- COLUMNA 1: SEGURIDAD -->
        <div class="col-check-table">
            <table class="table-items">
                <thead>
                    <tr>
                        <th colspan="5" style="background:#e2e8f0;">PARA PROTECCIÓN Y SEGURIDAD</th>
                    </tr>
                    <tr>
                        <th rowspan="2" style="width:44%;">Materiales</th>
                        <th colspan="2" style="width:28%;">Antes</th>
                        <th colspan="2" style="width:28%;">Post</th>
                    </tr>
                    <tr>
                        <th>Cant</th>
                        <th>✓</th>
                        <th>Cant</th>
                        <th>✓</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $itemsSeguridad = [
                        'Mascarilla', 'Lentes de Seguridad', 'Casco', 'Chaleco', 
                        'Guantes Nitrilo', 'Botas de Cuero', 'Botas de Hule', 
                        'Tapones de oído', 'Documentos de acceso al lugar', 
                        'Carnet de Seguro', 'Colilla de INSS', 'Cuña de vehículo', 
                        'Cinta luminosa de vehículo'
                    ];
                    foreach ($itemsSeguridad as $idx => $item):
                        $k = 'seg_' . $idx;
                        $cantA = $chk[$k . '_cant_a'] ?? '';
                        $chkA = !empty($chk[$k . '_chk_a']);
                        $cantP = $chk[$k . '_cant_p'] ?? '';
                        $chkP = !empty($chk[$k . '_chk_p']);
                    ?>
                    <tr>
                        <td><?= $item ?></td>
                        <td style="text-align:center;"><?= htmlspecialchars($cantA) ?></td>
                        <td style="text-align:center;"><span class="check-box-print"><?= $chkA ? 'X' : '' ?></span></td>
                        <td style="text-align:center;"><?= htmlspecialchars($cantP) ?></td>
                        <td style="text-align:center;"><span class="check-box-print"><?= $chkP ? 'X' : '' ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

        <!-- COLUMNA 2: TOMA DE MUESTRAS -->
        <div class="col-check-table">
            <table class="table-items">
                <thead>
                    <tr>
                        <th colspan="5" style="background:#e2e8f0;">PARA TOMA DE MUESTRAS</th>
                    </tr>
                    <tr>
                        <th rowspan="2" style="width:44%;">Materiales</th>
                        <th colspan="2" style="width:28%;">Antes</th>
                        <th colspan="2" style="width:28%;">Post</th>
                    </tr>
                    <tr>
                        <th>Cant</th>
                        <th>✓</th>
                        <th>Cant</th>
                        <th>✓</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $itemsMuestras = [
                        'Densímetro Nuclear', 'Base de parafina', 'Placa guía para pin', 
                        'Mazo de hierro', 'Pin de penetración', 'Mariposa para extraer pin', 
                        'Cargador de densímetro', 'Caja de densímetro', 'Mecate para aseguran caja', 
                        'Dosímetro personal', 'Dosímetro de equipo', 'Conos de seguridad', 
                        'Rótulos de advertencia radiación', 'Hoja de campo', 'Tabla de apoyo', 
                        'Proctor a utilizar'
                    ];
                    foreach ($itemsMuestras as $idx => $item):
                        $k = 'mue_' . $idx;
                        $cantA = $chk[$k . '_cant_a'] ?? '';
                        $chkA = !empty($chk[$k . '_chk_a']);
                        $cantP = $chk[$k . '_cant_p'] ?? '';
                        $chkP = !empty($chk[$k . '_chk_p']);
                    ?>
                    <tr>
                        <td><?= $item ?></td>
                        <td style="text-align:center;"><?= htmlspecialchars($cantA) ?></td>
                        <td style="text-align:center;"><span class="check-box-print"><?= $chkA ? 'X' : '' ?></span></td>
                        <td style="text-align:center;"><?= htmlspecialchars($cantP) ?></td>
                        <td style="text-align:center;"><span class="check-box-print"><?= $chkP ? 'X' : '' ?></span></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- VERIFICACIÓN DE RECURSOS DESPUÉS DEL MUESTREO -->
    <table class="table-verif">
        <thead>
            <tr>
                <th colspan="4" style="background:#e2e8f0; text-transform:uppercase;">VERIFICACIÓN DE RECURSOS DESPUÉS DEL MUESTREO</th>
            </tr>
            <tr>
                <th style="width:30%;">Responsabilidad</th>
                <th style="width:30%;">Nombre</th>
                <th style="width:20%;">Fecha y hora</th>
                <th style="width:20%;">Firma</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td>Recibe Materiales</td>
                <td><?= htmlspecialchars($chk['post_recibe_nombre'] ?? '') ?></td>
                <td><?= htmlspecialchars($chk['post_recibe_fecha'] ?? '') ?></td>
                <td style="text-align:center;"><?= !empty($chk['post_recibe_firma']) ? 'Firmado digitalmente' : '_______________' ?></td>
            </tr>
            <tr>
                <td>Entrega de Materiales</td>
                <td><?= htmlspecialchars($chk['post_entrega_nombre'] ?? ($pm['tecnico_nombre'] ?? '')) ?></td>
                <td><?= htmlspecialchars($chk['post_entrega_fecha'] ?? '') ?></td>
                <td style="text-align:center;"><?= !empty($chk['post_entrega_firma']) ? 'Firmado digitalmente' : '_______________' ?></td>
            </tr>
        </tbody>
    </table>

    <!-- OBSERVACIONES Y VEHÍCULO -->
    <div class="footer-obs">
        <strong>Observaciones:</strong> 
        Vehículo: <u><?= htmlspecialchars(!empty($pm['placa']) ? ($pm['marca'] . ' ' . $pm['modelo'] . ' - ' . $pm['placa']) : (!empty($os['vehiculo_muestreo']) ? $os['vehiculo_muestreo'] : 'LE-3050')) ?></u>
        &nbsp;|&nbsp; 
        <?= htmlspecialchars($pm['observaciones_campo'] ?? 'Sin observaciones adicionales.') ?>
    </div>

    </div><!-- /.page-container -->

</body>
</html>