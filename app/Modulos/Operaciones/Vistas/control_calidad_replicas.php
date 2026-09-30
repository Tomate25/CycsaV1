<?php
$informes = $informes ?? [];
$comparaciones = $comparaciones ?? [];

// Si se recibe $comparaciones de pruebas unitarias y no $informes, generar estructura para renderizar
if (empty($informes) && !empty($comparaciones)) {
    $agrupados = [];
    foreach ($comparaciones as $c) {
        $idDet = (int)($c['id'] ?? 0);
        if (!isset($agrupados[$idDet])) {
            $agrupados[$idDet] = [
                'detalle' => $c,
                'metaOficial' => [
                    'cliente_nombre' => $c['cliente_nombre'] ?? 'Cliente Confidencial',
                    'proyecto' => $c['nombre_proyecto'] ?? 'Proyecto General',
                    'cliente_direccion' => $c['cliente_direccion'] ?? 'Nicaragua',
                    'fecha_muestreo' => date('Y-m-d'),
                    'fecha_ingreso' => date('Y-m-d'),
                    'fecha_ejecucion' => date('Y-m-d'),
                    'fecha_emision' => date('Y-m-d'),
                    'tipo_muestra' => 'Especímenes',
                    'procedimiento_muestreo' => 'CYCSA-PE Oficial',
                    'muestra_tomada_por' => 'CYCSA Laboratorio',
                    'ubicacion' => 'Sitio de Proyecto',
                    'metodo_muestreo' => $c['norma_astm'] ?? 'Norma ASTM',
                    'codigo_formato' => 'CYCSA-RT-FM-22',
                    'ensayo_realizado' => $c['descripcion_ensayo'] ?? 'Ensayo Técnico'
                ],
                'codigoInformeConsecutivo' => 'CYCSA-INF-' . ($c['codigo_original'] ?? 'MS-0001-26'),
                'columnas' => ['Código laboratorio', 'Carga (lb)', 'Observaciones'],
                'filas' => [
                    ['Código laboratorio' => $c['codigo_original'] ?? 'MS-0001-26', 'Carga (lb)' => $c['diferencias']['Carga (lb)']['original'] ?? '—'],
                    ['Código laboratorio' => $c['codigo_replica'] ?? 'MS-0001-26-CR', '_es_replica' => true, '_muestra_origen' => $c['codigo_original'] ?? 'MS-0001-26', 'Carga (lb)' => $c['diferencias']['Carga (lb)']['replica'] ?? '—']
                ],
                'pares' => [$c]
            ];
        } else {
            $agrupados[$idDet]['pares'][] = $c;
        }
    }
    $informes = array_values($agrupados);
}

// Estadísticas generales
$totalPares = 0;
$conteos = ['pendiente' => 0, 'conforme' => 0, 'no_conforme' => 0];
foreach ($informes as $inf) {
    foreach ($inf['pares'] as $par) {
        $totalPares++;
        $est = $par['evaluacion']['estado'] ?? 'pendiente';
        $conteos[$est] = ($conteos[$est] ?? 0) + 1;
    }
}
?>

<style>
    .qc-page { max-width: 1400px; margin: 0 auto; padding-bottom: 60px; }
    
    /* Topbar & Header */
    .qc-head { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:24px; }
    .qc-title { margin:0; color:#0f172a; font-family:'Outfit',sans-serif; font-size:26px; font-weight:800; display:flex; align-items:center; gap:12px; }
    .qc-subtitle { margin:6px 0 0; color:#64748b; font-size:13.5px; }
    
    /* Resumen KPI */
    .qc-summary { display:grid; grid-template-columns:repeat(auto-fit, minmax(180px, 1fr)); gap:14px; margin-bottom:26px; }
    .qc-kpi { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:16px 20px; box-shadow:0 2px 10px rgba(15,23,42,.03); border-left:4px solid #0f766e; }
    .qc-kpi span { display:block; color:#64748b; font-size:11.5px; font-weight:800; text-transform:uppercase; letter-spacing:.6px; }
    .qc-kpi strong { display:block; color:#0f172a; font-size:26px; font-weight:800; margin-top:4px; font-family:'Outfit', sans-serif; }
    
    /* Tarjeta Hoja de Ensayo Completa */
    .informe-sheet-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-top: 5px solid #103487;
        border-radius: 12px;
        box-shadow: 0 4px 18px rgba(15, 23, 42, 0.05);
        margin-bottom: 35px;
        overflow: hidden;
    }
    
    .informe-sheet-header {
        background: #f8fafc;
        border-bottom: 2px solid #0f172a;
        padding: 20px 25px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }
    
    .informe-sheet-title {
        font-family: 'Outfit', sans-serif;
        font-size: 19px;
        font-weight: 800;
        color: #0f172a;
        text-transform: uppercase;
        margin: 0;
        letter-spacing: 0.5px;
    }
    
    .informe-consecutivo-box {
        font-family: 'Consolas', 'Courier New', monospace;
        font-size: 14px;
        font-weight: 800;
        color: #103487;
        margin-top: 4px;
        display: inline-block;
    }
    
    .informe-badge-os {
        background: #e0f2fe;
        color: #0369a1;
        font-weight: 800;
        font-family: monospace;
        padding: 4px 10px;
        border-radius: 6px;
        font-size: 12.5px;
        border: 1px solid #bae6fd;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .informe-badge-formato {
        background: #0f172a;
        color: #ffffff;
        font-weight: 800;
        font-family: monospace;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 12.5px;
        letter-spacing: 0.5px;
    }
    
    .btn-sheet-action {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #ffffff;
        color: #334155;
        border: 1px solid #cbd5e1;
        padding: 7px 14px;
        border-radius: 7px;
        font-size: 12.5px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s;
    }
    .btn-sheet-action:hover {
        background: #f1f5f9;
        color: #0f172a;
        border-color: #94a3b8;
    }

    /* Grilla de Metadatos Oficiales */
    .sheet-meta-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 10px 24px;
        padding: 18px 25px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        font-size: 12.5px;
    }
    @media(max-width: 900px) {
        .sheet-meta-grid { grid-template-columns: 1fr; gap: 8px; }
    }
    .sheet-meta-item {
        display: grid;
        grid-template-columns: 190px 1fr;
        align-items: baseline;
        gap: 10px;
        padding: 2px 0;
    }
    .sheet-meta-label {
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        font-size: 11px;
        letter-spacing: 0.3px;
    }
    .sheet-meta-val {
        color: #0f172a;
        font-weight: 600;
    }
    
    /* Contenedor de la Tabla Matriz Técnica */
    .sheet-table-section {
        padding: 22px 25px;
        background: #fafcff;
        border-bottom: 1px solid #e2e8f0;
    }
    .sheet-table-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
    }
    .sheet-table-heading h4 {
        margin: 0;
        font-size: 14.5px;
        font-weight: 800;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    
    .tabla-matriz-qc {
        width: 100%;
        border-collapse: collapse;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        overflow: hidden;
        font-size: 12px;
    }
    .tabla-matriz-qc th {
        background: #f1f5f9;
        color: #1e293b;
        font-weight: 800;
        padding: 10px 12px;
        border-bottom: 2px solid #cbd5e1;
        border-right: 1px solid #e2e8f0;
        text-align: left;
        white-space: nowrap;
    }
    .tabla-matriz-qc th:last-child { border-right: none; }
    .tabla-matriz-qc td {
        padding: 9px 12px;
        border-bottom: 1px solid #eef2f7;
        border-right: 1px solid #f1f5f9;
        color: #334155;
    }
    .tabla-matriz-qc td:last-child { border-right: none; }
    
    /* Fila destacada de Réplica */
    .tr-replica-row {
        background: #f0fdfa !important;
        font-weight: 600;
        border-left: 4px solid #0f766e;
    }
    .tr-replica-row td {
        background: #f0fdfa !important;
        color: #0f766e !important;
        border-bottom: 1px solid #ccfbf1 !important;
    }
    .badge-replica-tag {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #ccfbf1;
        color: #115e59;
        padding: 3px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 800;
        margin-left: 6px;
        font-family: inherit;
    }
    .badge-origen-tag {
        display: inline-block;
        font-size: 11px;
        color: #64748b;
        margin-top: 2px;
        font-style: italic;
    }

    /* Sección de Análisis Par a Par */
    .sheet-analysis-section {
        padding: 24px 25px;
        background: #ffffff;
    }
    .sheet-analysis-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(460px, 1fr));
        gap: 20px;
    }
    @media(max-width: 768px) {
        .sheet-analysis-grid { grid-template-columns: 1fr; }
    }
    
    .qc-pair-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        overflow: hidden;
        box-shadow: 0 2px 8px rgba(0,0,0,0.03);
    }
    .qc-pair-header {
        background: #f8fafc;
        border-bottom: 1px solid #e2e8f0;
        padding: 12px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
    }
    .qc-pair-codes {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .code-badge {
        font-family: monospace;
        font-weight: 800;
        font-size: 12px;
        padding: 4px 8px;
        border-radius: 6px;
        background: #e0f2fe;
        color: #075985;
    }
    .code-badge.rep {
        background: #ccfbf1;
        color: #115e59;
        border: 1px solid #99f6e4;
    }
    
    .qc-status-pill {
        font-size: 11px;
        font-weight: 800;
        text-transform: uppercase;
        padding: 4px 10px;
        border-radius: 12px;
    }
    .qc-status-pill.pendiente { background: #fef3c7; color: #92400e; }
    .qc-status-pill.conforme { background: #dcfce7; color: #166534; }
    .qc-status-pill.no_conforme { background: #fee2e2; color: #991b1b; }

    .qc-diff-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 12px;
    }
    .qc-diff-table th {
        background: #f8fafc;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        text-align: left;
        padding: 8px 12px;
        border-bottom: 1px solid #e2e8f0;
    }
    .qc-diff-table td {
        padding: 7px 12px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
    }
    
    .qc-eval-form {
        padding: 14px 16px;
        background: #fafcff;
        border-top: 1px solid #e2e8f0;
    }
    .qc-eval-form label {
        display: block;
        font-size: 11.5px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 4px;
    }
    .qc-eval-form select, .qc-eval-form textarea {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 8px 10px;
        font: inherit;
        font-size: 12.5px;
        margin-bottom: 10px;
    }
    .qc-eval-form textarea {
        min-height: 75px;
        resize: vertical;
    }
    .btn-save-qc {
        background: #0f766e;
        color: white;
        border: none;
        padding: 8px 16px;
        border-radius: 6px;
        font-weight: 700;
        font-size: 12.5px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        width: 100%;
        justify-content: center;
        transition: background 0.2s;
    }
    .btn-save-qc:hover {
        background: #115e59;
    }
    
    .qc-empty-state {
        background: #ffffff;
        border: 2px dashed #cbd5e1;
        border-radius: 12px;
        padding: 60px 20px;
        text-align: center;
        color: #64748b;
    }
</style>

<div class="qc-page">
    <!-- Encabezado Principal -->
    <div class="qc-head">
        <div>
            <h2 class="qc-title">
                <i class="fa-solid fa-code-compare" style="color:#0f766e;"></i>
                Control de Calidad: Informes de Ensayo y Réplicas
            </h2>
            <p class="qc-subtitle">
                Supervisión técnica de la matriz completa del cliente emitida por Hoja de Servicio, con visualización de todas las muestras y dictamen de réplicas (-CR).
            </p>
        </div>
        <div style="display:flex; gap:10px;">
            <a href="/Cycsa/publico/operaciones" class="btn-sheet-action">
                <i class="fa-solid fa-arrow-left"></i> Volver a Operaciones
            </a>
            <a href="/Cycsa/publico/panel" class="btn-sheet-action">
                <i class="fa-solid fa-cubes"></i> Panel Principal
            </a>
        </div>
    </div>

    <!-- Tarjetas de Resumen KPI -->
    <div class="qc-summary">
        <div class="qc-kpi">
            <span>Informes con Réplica</span>
            <strong><?= count($informes) ?></strong>
        </div>
        <div class="qc-kpi">
            <span>Total Muestras Evaluadas</span>
            <strong><?= $totalPares ?></strong>
        </div>
        <div class="qc-kpi" style="border-left-color:#f59e0b;">
            <span>Pendientes de Análisis</span>
            <strong style="color:#b45309;"><?= $conteos['pendiente'] ?></strong>
        </div>
        <div class="qc-kpi" style="border-left-color:#10b981;">
            <span>Conformes (Aprobadas)</span>
            <strong style="color:#15803d;"><?= $conteos['conforme'] ?></strong>
        </div>
        <div class="qc-kpi" style="border-left-color:#ef4444;">
            <span>No Conformes (Dispersas)</span>
            <strong style="color:#b91c1c;"><?= $conteos['no_conforme'] ?></strong>
        </div>
    </div>

    <?php if (empty($informes)): ?>
        <div class="qc-empty-state">
            <i class="fa-solid fa-vials" style="font-size:44px; color:#94a3b8; margin-bottom:14px;"></i>
            <h3 style="margin:0 0 8px 0; color:#334155; font-size:18px;">No hay informes con réplicas registradas</h3>
            <p style="margin:0; font-size:13.5px;">Cuando marque réplicas durante la captura de una matriz en Operaciones, el informe completo del cliente aparecerá aquí automáticamente para su análisis técnico.</p>
        </div>
    <?php endif; ?>

    <!-- Recorrido de Informes Oficiales Completos con sus Réplicas -->
    <?php foreach ($informes as $inf): 
        $det = $inf['detalle'];
        $meta = $inf['metaOficial'];
        $cols = $inf['columnas'];
        $filas = $inf['filas'];
        $pares = $inf['pares'];
    ?>
        <article class="informe-sheet-card">
            <!-- 1. Cabecera Oficial del Informe -->
            <div class="informe-sheet-header">
                <div>
                    <div style="display:flex; align-items:center; gap:10px; margin-bottom:6px;">
                        <span class="informe-badge-os">
                            <i class="fa-solid fa-file-contract"></i> <?= htmlspecialchars($det['codigo_os'] ?? 'O/S') ?>
                        </span>
                        <span class="informe-badge-formato">
                            <?= htmlspecialchars($meta['codigo_formato'] ?? 'CYCSA-RT-FM-22') ?>
                        </span>
                        <span style="font-size:11px; font-weight:700; color:#64748b;">
                            ISO/IEC 17025:2017
                        </span>
                    </div>
                    <h3 class="informe-sheet-title">
                        <?= htmlspecialchars($meta['ensayo_realizado'] ?? ($det['descripcion_ensayo'] ?? 'INFORME DE ENSAYO')) ?>
                    </h3>
                    <div class="informe-consecutivo-box">
                        <?= htmlspecialchars($inf['codigoInformeConsecutivo']) ?>
                    </div>
                </div>
                
                <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
                    <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= (int)$det['id'] ?>" class="btn-sheet-action" target="_blank" title="Abrir matriz en pestaña nueva">
                        <i class="fa-solid fa-pen-to-square"></i> Abrir Matriz
                    </a>
                    <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= (int)$det['id'] ?>" class="btn-sheet-action" target="_blank" title="Ver formato de impresión">
                        <i class="fa-solid fa-print"></i> Vista Imprimible
                    </a>
                </div>
            </div>

            <!-- 2. Metadatos Oficiales de la Hoja de Servicio / Cliente -->
            <div class="sheet-meta-grid">
                <div class="sheet-meta-item">
                    <span class="sheet-meta-label">Cliente:</span>
                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['cliente_nombre'] ?? 'Sin especificar') ?></span>
                </div>
                <div class="sheet-meta-item">
                    <span class="sheet-meta-label">Proyecto:</span>
                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['proyecto'] ?? 'Sin especificar') ?></span>
                </div>
                <div class="sheet-meta-item">
                    <span class="sheet-meta-label">Dirección / Sitio:</span>
                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['cliente_direccion'] ?? '—') ?></span>
                </div>
                <div class="sheet-meta-item">
                    <span class="sheet-meta-label">Ubicación Muestreo:</span>
                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['ubicacion'] ?? '—') ?></span>
                </div>
                <div class="sheet-meta-item">
                    <span class="sheet-meta-label">Fecha de Muestreo:</span>
                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['fecha_muestreo'] ?? '—') ?></span>
                </div>
                <div class="sheet-meta-item">
                    <span class="sheet-meta-label">Fecha de Ingreso:</span>
                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['fecha_ingreso'] ?? '—') ?></span>
                </div>
                <div class="sheet-meta-item">
                    <span class="sheet-meta-label">Fecha de Ejecución:</span>
                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['fecha_ejecucion'] ?? '—') ?></span>
                </div>
                <div class="sheet-meta-item">
                    <span class="sheet-meta-label">Fecha de Emisión:</span>
                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['fecha_emision'] ?? '—') ?></span>
                </div>
                <div class="sheet-meta-item">
                    <span class="sheet-meta-label">Tipo de Muestra:</span>
                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['tipo_muestra'] ?? '—') ?></span>
                </div>
                <div class="sheet-meta-item">
                    <span class="sheet-meta-label">Norma / Método:</span>
                    <span class="sheet-meta-val"><?= htmlspecialchars($meta['metodo_muestreo'] ?? ($det['norma_astm'] ?? '—')) ?></span>
                </div>
            </div>

            <!-- 3. Tabla Matriz Técnica Completa con TODAS las Muestras y Réplicas -->
            <div class="sheet-table-section">
                <div class="sheet-table-heading">
                    <h4>
                        <i class="fa-solid fa-table-cells" style="color:#103487;"></i>
                        Resultados de la Matriz Técnica Oficial (Todas las muestras del lote y sus réplicas)
                    </h4>
                    <span style="font-size:12px; color:#64748b;">
                        Total muestras en matriz: <strong><?= count($filas) ?></strong>
                    </span>
                </div>

                <div style="overflow-x:auto;">
                    <table class="tabla-matriz-qc">
                        <thead>
                            <tr>
                                <th style="width:40px; text-align:center;">#</th>
                                <?php foreach ($cols as $col): ?>
                                    <th><?= htmlspecialchars($col) ?></th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php 
                            $rIdx = 0;
                            foreach ($filas as $fila): 
                                $rIdx++;
                                $esRep = esFilaReplicaMatriz($fila);
                                $origen = $fila['_muestra_origen'] ?? '';
                            ?>
                                <tr class="<?= $esRep ? 'tr-replica-row' : '' ?>">
                                    <td style="text-align:center; font-weight:700; color:<?= $esRep ? '#0f766e' : '#64748b' ?>;">
                                        <?= $rIdx ?>
                                    </td>
                                    <?php foreach ($cols as $col): 
                                        $val = $fila[$col] ?? '';
                                        $esColLab = in_array($col, ['Código laboratorio', 'Codigo laboratorio', 'codigo_lab', 'codigo_muestra']);
                                        $esColNom = in_array($col, ['Nombre muestra', 'Nombre de muestra', 'nombre_muestra']);
                                    ?>
                                        <td>
                                            <?php if ($esColLab && $esRep): ?>
                                                <strong style="font-family:monospace;"><?= htmlspecialchars((string)$val) ?></strong>
                                                <span class="badge-replica-tag"><i class="fa-solid fa-vials"></i> RÉPLICA</span>
                                            <?php elseif ($esColNom && $esRep): ?>
                                                <?= htmlspecialchars((string)$val) ?>
                                                <?php if (!empty($origen)): ?>
                                                    <div class="badge-origen-tag">Cotejada contra <?= htmlspecialchars($origen) ?></div>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <?= htmlspecialchars((string)$val) ?>
                                            <?php endif; ?>
                                        </td>
                                    <?php endforeach; ?>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- 4. Sección de Control de Calidad: Análisis Par a Par y Dictamen Oficial -->
            <div class="sheet-analysis-section">
                <div style="margin-bottom:16px;">
                    <h4 style="margin:0 0 4px 0; color:#0f766e; font-size:15px; font-weight:800; display:flex; align-items:center; gap:8px;">
                        <i class="fa-solid fa-shield-halved"></i>
                        Análisis de Repetibilidad y Dictamen de Control de Calidad
                    </h4>
                    <p style="margin:0; font-size:12px; color:#64748b;">
                        Comparación de tolerancias entre cada muestra base y su réplica de control (-CR) según normas ASTM y criterios de aseguramiento de calidad.
                    </p>
                </div>

                <div class="sheet-analysis-grid">
                    <?php foreach ($pares as $par): 
                        $eval = $par['evaluacion'] ?? [];
                        $estado = in_array(($eval['estado'] ?? ''), ['pendiente','conforme','no_conforme'], true) ? $eval['estado'] : 'pendiente';
                    ?>
                        <div class="qc-pair-card">
                            <div class="qc-pair-header">
                                <div class="qc-pair-codes">
                                    <span class="code-badge"><?= htmlspecialchars($par['codigo_original']) ?></span>
                                    <i class="fa-solid fa-arrow-right" style="color:#94a3b8; font-size:11px;"></i>
                                    <span class="code-badge rep"><?= htmlspecialchars($par['codigo_replica']) ?></span>
                                </div>
                                <span class="qc-status-pill <?= $estado ?>">
                                    <?= $estado === 'no_conforme' ? 'No conforme' : ucfirst($estado) ?>
                                </span>
                            </div>

                            <!-- Tabla de Diferencias Numéricas -->
                            <div>
                                <table class="qc-diff-table">
                                    <thead>
                                        <tr>
                                            <th>Campo medible</th>
                                            <th>Original</th>
                                            <th>Réplica</th>
                                            <th>Diferencia</th>
                                            <th>Variación</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (empty($par['diferencias'])): ?>
                                            <tr>
                                                <td colspan="5" style="text-align:center; color:#94a3b8; padding:12px;">
                                                    Sin datos numéricos comparables o idénticos.
                                                </td>
                                            </tr>
                                        <?php else: ?>
                                            <?php foreach ($par['diferencias'] as $campo => $dif): ?>
                                                <tr>
                                                    <td style="font-weight:700; color:#1e293b;"><?= htmlspecialchars($campo) ?></td>
                                                    <td><?= htmlspecialchars((string)$dif['original']) ?></td>
                                                    <td><?= htmlspecialchars((string)$dif['replica']) ?></td>
                                                    <td><?= number_format((float)$dif['diferencia'], 4, '.', ',') ?></td>
                                                    <td>
                                                        <?php if ($dif['diferencia_porcentual'] === null): ?>
                                                            N/A
                                                        <?php else: 
                                                            $porc = (float)$dif['diferencia_porcentual'];
                                                            $colorVar = abs($porc) <= 5.0 ? '#15803d' : (abs($porc) <= 10.0 ? '#b45309' : '#b91c1c');
                                                        ?>
                                                            <strong style="color:<?= $colorVar ?>;">
                                                                <?= ($porc > 0 ? '+' : '') . number_format($porc, 2, '.', ',') ?>%
                                                            </strong>
                                                        <?php endif; ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Formulario de Dictamen -->
                            <form class="qc-eval-form" method="POST" action="/Cycsa/publico/control-calidad/evaluar-replica">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id_detalle" value="<?= (int)$det['id'] ?>">
                                <input type="hidden" name="codigo_original" value="<?= htmlspecialchars($par['codigo_original'], ENT_QUOTES, 'UTF-8') ?>">
                                
                                <label>Dictamen del Responsable de Calidad:</label>
                                <select name="estado" required>
                                    <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente de análisis</option>
                                    <option value="conforme" <?= $estado === 'conforme' ? 'selected' : '' ?>>Conforme (Dentro de tolerancias)</option>
                                    <option value="no_conforme" <?= $estado === 'no_conforme' ? 'selected' : '' ?>>No conforme (Discrepancia crítica)</option>
                                </select>
                                
                                <label>Conclusiones / Observaciones de Repetibilidad:</label>
                                <textarea name="observaciones" maxlength="2000" placeholder="Documente cumplimiento de tolerancias de la norma técnica..."><?= htmlspecialchars($eval['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                                
                                <?php if (!empty($eval['fecha'])): ?>
                                    <div style="font-size:11px; color:#64748b; margin-bottom:8px;">
                                        Última evaluación: <strong><?= htmlspecialchars($eval['usuario'] ?? '') ?></strong> (<?= htmlspecialchars($eval['fecha']) ?>)
                                    </div>
                                <?php endif; ?>

                                <button type="submit" class="btn-save-qc">
                                    <i class="fa-solid fa-shield-halved"></i> Guardar evaluación
                                </button>
                            </form>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </article>
    <?php endforeach; ?>
</div>
