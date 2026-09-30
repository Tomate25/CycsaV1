<?php
$detalle = $detalle ?? ($informe['detalle'] ?? []);
$meta = $metaOficial ?? ($informe['metaOficial'] ?? []);
$cols = $columnas ?? ($informe['columnas'] ?? []);
$filas = $filas ?? ($informe['filas'] ?? []);
$pares = $pares ?? ($informe['pares'] ?? []);
$consecutivo = $codigoInformeConsecutivo ?? ($informe['codigoInformeConsecutivo'] ?? 'CYCSA-INF');
$idDet = (int)($detalle['id'] ?? 0);
?>

<style>
    .qc-detail-page { max-width: 1400px; margin: 0 auto; padding-bottom: 60px; }
    
    /* Top Bar */
    .qc-detail-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 16px;
        flex-wrap: wrap;
        margin-bottom: 20px;
    }
    .qc-detail-breadcrumb {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .btn-qc-back {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        background: #ffffff;
        color: #1e293b;
        border: 1px solid #cbd5e1;
        padding: 9px 16px;
        border-radius: 8px;
        font-size: 13.5px;
        font-weight: 700;
        text-decoration: none;
        transition: all 0.2s;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .btn-qc-back:hover {
        background: #f1f5f9;
        color: #103487;
        border-color: #94a3b8;
        transform: translateX(-2px);
    }

    /* Tarjeta Oficial Hoja de Ensayo Completa */
    .informe-sheet-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-top: 5px solid #103487;
        border-radius: 12px;
        box-shadow: 0 4px 20px rgba(15, 23, 42, 0.06);
        overflow: hidden;
    }
    .informe-sheet-header {
        background: #ffffff;
        border-bottom: 2px solid #0f172a;
        padding: 22px 28px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
    }
    .informe-sheet-title {
        font-family: 'Outfit', sans-serif;
        font-size: 20px;
        font-weight: 800;
        color: #0f172a;
        text-transform: uppercase;
        margin: 0;
        letter-spacing: 0.5px;
    }
    .informe-consecutivo-box {
        font-family: 'Consolas', 'Courier New', monospace;
        font-size: 15px;
        font-weight: 800;
        color: #103487;
        margin-top: 4px;
        display: inline-block;
    }
    .badge-premium {
        padding: 4px 10px;
        border-radius: 12px;
        font-size: 11.5px;
        font-weight: 700;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .badge-os { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; font-family: monospace; font-weight: 700; }
    .badge-formato { background-color: #0f172a; color: #ffffff; font-family: monospace; font-size: 12px; padding: 4px 10px; border-radius: 4px; }
    .badge-iso { background-color: #ccfbf1; color: #0f766e; border: 1px solid #99f6e4; font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 4px; }
    
    .btn-sheet-action {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        background: #ffffff;
        color: #334155;
        border: 1px solid #cbd5e1;
        padding: 8px 16px;
        border-radius: 7px;
        font-size: 13px;
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
        gap: 10px 30px;
        padding: 20px 28px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
        font-size: 13px;
    }
    @media(max-width: 900px) {
        .sheet-meta-grid { grid-template-columns: 1fr; gap: 8px; }
    }
    .sheet-meta-item {
        display: grid;
        grid-template-columns: 190px 1fr;
        align-items: baseline;
        gap: 12px;
        padding: 3px 0;
    }
    .sheet-meta-label {
        font-weight: 700;
        color: #475569;
        text-transform: uppercase;
        font-size: 11.5px;
        letter-spacing: 0.5px;
    }
    .sheet-meta-val {
        font-weight: 600;
        color: #0f172a;
        word-break: break-word;
    }

    /* Sección de Tabla de Matriz Oficial */
    .sheet-table-section {
        padding: 24px 28px;
        background: #ffffff;
        border-bottom: 1px solid #e2e8f0;
    }
    .sheet-table-heading {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
        flex-wrap: wrap;
        gap: 10px;
    }
    .sheet-table-heading h4 {
        margin: 0;
        font-size: 15.5px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .tabla-matriz-qc {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
    }
    .tabla-matriz-qc th {
        background: #0f172a;
        color: #ffffff;
        font-weight: 700;
        padding: 10px 14px;
        text-align: left;
        font-size: 11.5px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        border: 1px solid #1e293b;
        white-space: nowrap;
    }
    .tabla-matriz-qc td {
        padding: 9px 14px;
        border: 1px solid #e2e8f0;
        color: #1e293b;
        vertical-align: middle;
    }
    .tabla-matriz-qc tr.tr-replica-row {
        background-color: #f0fdfa !important;
        border-left: 5px solid #0f766e;
    }
    .badge-replica-tag {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        background: #ccfbf1;
        color: #0f766e;
        border: 1px solid #99f6e4;
        font-weight: 800;
        font-size: 10.5px;
        padding: 2px 7px;
        border-radius: 4px;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }

    /* Sección de Comparación Técnica & Dictamen */
    .sheet-qa-section {
        padding: 26px 28px;
        background: #fafaf9;
    }
    .sheet-qa-heading {
        margin-bottom: 20px;
    }
    .sheet-qa-heading h4 {
        margin: 0 0 4px 0;
        font-size: 16px;
        font-weight: 800;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .sheet-qa-heading p {
        margin: 0;
        font-size: 13px;
        color: #64748b;
    }

    .qc-pairs-container {
        display: grid;
        grid-template-columns: 1fr;
        gap: 20px;
    }
    .qc-pair-card {
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 10px;
        padding: 18px 22px;
        box-shadow: 0 2px 8px rgba(15, 23, 42, 0.03);
    }
    .qc-pair-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px solid #f1f5f9;
        flex-wrap: wrap;
        gap: 10px;
    }
    .qc-pair-codes {
        display: flex;
        align-items: center;
        gap: 10px;
        font-family: monospace;
        font-size: 13.5px;
    }
    .qc-diff-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        margin-bottom: 16px;
    }
    .qc-diff-table th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        font-size: 11.5px;
        text-transform: uppercase;
        text-align: left;
    }
    .qc-diff-table td {
        padding: 8px 12px;
        border: 1px solid #e2e8f0;
        color: #1e293b;
    }

    .qc-eval-form {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px 18px;
    }
    .qc-eval-form label {
        display: block;
        font-size: 12.5px;
        font-weight: 700;
        color: #334155;
        margin-bottom: 6px;
    }
    .qc-eval-form select, .qc-eval-form textarea {
        width: 100%;
        box-sizing: border-box;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 9px 12px;
        font-size: 13.5px;
        margin-bottom: 12px;
        font-family: inherit;
        background: #ffffff;
    }
    .qc-eval-form select:focus, .qc-eval-form textarea:focus {
        outline: none;
        border-color: #103487;
        box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08);
    }
    .btn-save-qc {
        background: #0f766e;
        color: #ffffff;
        border: none;
        padding: 9px 18px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: background 0.2s;
    }
    .btn-save-qc:hover {
        background: #115e59;
    }

    .badge-conforme { background-color: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .badge-pendiente { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .badge-no-conforme { background-color: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
</style>

<div class="qc-detail-page">
    <!-- Barra Superior de Navegación -->
    <div class="qc-detail-topbar">
        <div class="qc-detail-breadcrumb">
            <a href="/Cycsa/publico/control-calidad" class="btn-qc-back">
                <i class="fa-solid fa-arrow-left"></i> Volver a la Lista de Controles
            </a>
            <span style="color:#94a3b8; font-size:18px;">/</span>
            <span style="font-weight:700; color:#0f172a; font-size:14px; font-family:monospace;">
                <?= htmlspecialchars($consecutivo) ?>
            </span>
        </div>

        <div style="display:flex; align-items:center; gap:8px;">
            <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $idDet ?>" class="btn-sheet-action" target="_blank">
                <i class="fa-solid fa-pen-to-square"></i> Abrir Matriz (Operaciones)
            </a>
            <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= $idDet ?>" class="btn-sheet-action" target="_blank">
                <i class="fa-solid fa-print"></i> Vista Imprimible
            </a>
        </div>
    </div>

    <!-- Mensajes Flash de Sesión -->
    <?php if (!empty($_SESSION['exito'])): ?>
        <div style="background:#ecfdf5; border-left:4px solid #10b981; color:#065f46; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-size:13.5px; display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-circle-check"></i>
            <span><?= htmlspecialchars($_SESSION['exito']) ?></span>
            <?php unset($_SESSION['exito']); ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($_SESSION['error'])): ?>
        <div style="background:#fef2f2; border-left:4px solid #ef4444; color:#991b1b; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-size:13.5px; display:flex; align-items:center; gap:10px;">
            <i class="fa-solid fa-triangle-exclamation"></i>
            <span><?= htmlspecialchars($_SESSION['error']) ?></span>
            <?php unset($_SESSION['error']); ?>
        </div>
    <?php endif; ?>

    <!-- Tarjeta Oficial Completa de la Hoja de Ensayo a Pantalla Completa -->
    <article class="informe-sheet-card">
        
        <!-- 1. Cabecera Oficial del Informe -->
        <div class="informe-sheet-header">
            <div>
                <div style="display:flex; align-items:center; gap:10px; margin-bottom:8px; flex-wrap:wrap;">
                    <span class="badge-premium badge-os">
                        <i class="fa-solid fa-file-contract"></i> <?= htmlspecialchars($detalle['codigo_os'] ?? 'O/S') ?>
                    </span>
                    <span class="badge-formato">
                        <?= htmlspecialchars($meta['codigo_formato'] ?? 'CYCSA-RT-FM-22') ?>
                    </span>
                    <span class="badge-iso">
                        <i class="fa-solid fa-certificate"></i> Control Interno ISO/IEC 17025:2017
                    </span>
                </div>
                <h3 class="informe-sheet-title">
                    <?= htmlspecialchars($meta['ensayo_realizado'] ?? ($detalle['descripcion_ensayo'] ?? 'INFORME DE ENSAYO')) ?>
                </h3>
                <div class="informe-consecutivo-box">
                    <i class="fa-solid fa-hashtag"></i> <?= htmlspecialchars($consecutivo) ?>
                </div>
            </div>

            <div>
                <span style="font-size:12px; color:#64748b; font-weight:600;">
                    Total muestras en lote: <strong style="color:#0f172a; font-size:14px;"><?= count($filas) ?></strong>
                    &nbsp;•&nbsp;
                    Réplicas evaluadas: <strong style="color:#0f766e; font-size:14px;"><?= count($pares) ?></strong>
                </span>
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
                <span class="sheet-meta-val"><?= htmlspecialchars($meta['metodo_muestreo'] ?? ($detalle['norma_astm'] ?? '—')) ?></span>
            </div>
        </div>

        <!-- 3. Tabla Matriz Técnica Completa con TODAS las Muestras y Réplicas -->
        <div class="sheet-table-section">
            <div class="sheet-table-heading">
                <h4>
                    <i class="fa-solid fa-table-cells" style="color:#103487;"></i>
                    Resultados de la Matriz Técnica Oficial (Todas las muestras del lote y sus réplicas)
                </h4>
                <span style="font-size:12.5px; color:#64748b;">
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
                                    $isCodCol = ($col === 'Código laboratorio' || $col === 'No. Muestra' || $col === 'Muestra');
                                ?>
                                    <td>
                                        <?php if ($isCodCol && $esRep): ?>
                                            <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap;">
                                                <strong style="color:#0f766e;"><?= htmlspecialchars((string)$val) ?></strong>
                                                <span class="badge-replica-tag">
                                                    <i class="fa-solid fa-clone"></i> RÉPLICA DE <?= htmlspecialchars($origen) ?>
                                                </span>
                                            </div>
                                        <?php elseif ($isCodCol): ?>
                                            <strong><?= htmlspecialchars((string)$val) ?></strong>
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

        <!-- 4. Análisis de Tolerancias y Formulario de Dictamen -->
        <div class="sheet-qa-section">
            <div class="sheet-qa-heading">
                <h4>
                    <i class="fa-solid fa-chart-line" style="color:#0f766e;"></i>
                    Evaluación Analítica de Precisión / Repetibilidad (Muestras Originales vs Réplicas -CR)
                </h4>
                <p>
                    Cálculo automático de diferencias absolutas y variación porcentual para auditoría y aseguramiento de calidad (ISO/IEC 17025).
                </p>
            </div>

            <div class="qc-pairs-container">
                <?php foreach ($pares as $par): 
                    $eval = $par['evaluacion'] ?? ['estado' => 'pendiente', 'observaciones' => '', 'usuario' => '', 'fecha' => ''];
                    $estado = $eval['estado'] ?? 'pendiente';
                ?>
                    <div class="qc-pair-card">
                        <div class="qc-pair-header">
                            <div class="qc-pair-codes">
                                <span style="font-weight:700; color:#1e293b;">Original:</span>
                                <span style="background:#f1f5f9; padding:3px 10px; border-radius:4px; font-weight:700;"><?= htmlspecialchars($par['codigo_original']) ?></span>
                                <i class="fa-solid fa-arrow-right" style="color:#94a3b8; font-size:12px;"></i>
                                <span style="font-weight:700; color:#0f766e;">Réplica:</span>
                                <span style="background:#ccfbf1; color:#0f766e; padding:3px 10px; border-radius:4px; font-weight:800;"><?= htmlspecialchars($par['codigo_replica']) ?></span>
                            </div>

                            <div>
                                <?php if ($estado === 'conforme'): ?>
                                    <span class="badge-premium badge-conforme"><i class="fa-solid fa-circle-check"></i> Conforme</span>
                                <?php elseif ($estado === 'no_conforme'): ?>
                                    <span class="badge-premium badge-no-conforme"><i class="fa-solid fa-triangle-exclamation"></i> No Conforme</span>
                                <?php else: ?>
                                    <span class="badge-premium badge-pendiente"><i class="fa-solid fa-clock"></i> Pendiente</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Tabla de Variaciones Numéricas -->
                        <div style="overflow-x:auto;">
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
                            <input type="hidden" name="id_detalle" value="<?= (int)$detalle['id'] ?>">
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
                                <div style="font-size:11.5px; color:#64748b; margin-bottom:8px;">
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
</div>
