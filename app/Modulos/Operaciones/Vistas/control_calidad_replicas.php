<?php
$comparaciones = $comparaciones ?? [];
$conteos = ['pendiente' => 0, 'conforme' => 0, 'no_conforme' => 0];
foreach ($comparaciones as $item) {
    $estadoItem = $item['evaluacion']['estado'] ?? 'pendiente';
    $conteos[$estadoItem] = ($conteos[$estadoItem] ?? 0) + 1;
}
?>

<style>
    .qc-page { max-width: 1500px; margin: 0 auto; }
    .qc-head { display:flex; justify-content:space-between; align-items:center; gap:16px; flex-wrap:wrap; margin-bottom:20px; }
    .qc-title { margin:0; color:#0f172a; font-family:'Outfit',sans-serif; font-size:25px; }
    .qc-subtitle { margin:5px 0 0; color:#64748b; font-size:13px; }
    .qc-summary { display:grid; grid-template-columns:repeat(4,minmax(145px,1fr)); gap:12px; margin-bottom:20px; }
    .qc-kpi { background:#fff; border:1px solid #e2e8f0; border-radius:10px; padding:15px; box-shadow:0 2px 8px rgba(15,23,42,.04); }
    .qc-kpi span { display:block; color:#64748b; font-size:11px; font-weight:800; text-transform:uppercase; letter-spacing:.5px; }
    .qc-kpi strong { display:block; color:#0f172a; font-size:24px; margin-top:5px; }
    .qc-card { background:#fff; border:1px solid #dbe3ee; border-radius:12px; margin-bottom:18px; overflow:hidden; box-shadow:0 4px 14px rgba(15,23,42,.05); }
    .qc-card-head { padding:16px 18px; background:#f8fafc; border-bottom:1px solid #e2e8f0; display:flex; justify-content:space-between; gap:14px; flex-wrap:wrap; }
    .qc-code-pair { display:flex; align-items:center; gap:8px; flex-wrap:wrap; margin-top:7px; }
    .qc-code { font-family:Consolas,monospace; padding:5px 9px; border-radius:6px; background:#e0f2fe; color:#075985; font-weight:800; font-size:12px; }
    .qc-code.replica { background:#ccfbf1; color:#115e59; }
    .qc-status { align-self:flex-start; padding:6px 10px; border-radius:20px; font-size:11px; font-weight:900; text-transform:uppercase; }
    .qc-status.pendiente { background:#fef3c7; color:#92400e; }
    .qc-status.conforme { background:#dcfce7; color:#166534; }
    .qc-status.no_conforme { background:#fee2e2; color:#991b1b; }
    .qc-body { display:grid; grid-template-columns:minmax(0,1.65fr) minmax(300px,.75fr); gap:18px; padding:18px; }
    .qc-table-wrap { overflow-x:auto; border:1px solid #e2e8f0; border-radius:8px; }
    .qc-table { width:100%; border-collapse:collapse; font-size:12px; }
    .qc-table th { background:#f1f5f9; color:#334155; text-align:left; padding:9px 10px; border-bottom:1px solid #cbd5e1; white-space:nowrap; }
    .qc-table td { padding:8px 10px; border-bottom:1px solid #eef2f7; color:#334155; }
    .qc-table tr:last-child td { border-bottom:0; }
    .qc-form label { display:block; font-size:12px; font-weight:800; color:#334155; margin-bottom:5px; }
    .qc-form select,.qc-form textarea { width:100%; border:1px solid #cbd5e1; border-radius:7px; padding:9px 10px; font:inherit; color:#1e293b; background:#fff; }
    .qc-form textarea { min-height:115px; resize:vertical; }
    .qc-btn { width:100%; border:0; border-radius:8px; padding:10px 14px; background:#0f766e; color:#fff; font-weight:800; cursor:pointer; }
    .qc-empty { background:#fff; border:1px dashed #cbd5e1; border-radius:12px; padding:50px 25px; text-align:center; color:#64748b; }
    @media(max-width:900px){ .qc-summary{grid-template-columns:repeat(2,1fr)} .qc-body{grid-template-columns:1fr} }
</style>

<div class="qc-page">
    <div class="qc-head">
        <div>
            <h2 class="qc-title"><i class="fa-solid fa-code-compare" style="color:#0f766e"></i> Control de Calidad de Réplicas</h2>
            <p class="qc-subtitle">Comparación independiente entre cada muestra original y su réplica de control identificada con el sufijo -CR.</p>
        </div>
        <a href="/Cycsa/publico/panel" style="text-decoration:none;color:#475569;font-weight:700;font-size:13px;"><i class="fa-solid fa-arrow-left"></i> Volver a módulos</a>
    </div>

    <div class="qc-summary">
        <div class="qc-kpi"><span>Total de pares</span><strong><?= count($comparaciones) ?></strong></div>
        <div class="qc-kpi"><span>Pendientes</span><strong style="color:#b45309"><?= $conteos['pendiente'] ?></strong></div>
        <div class="qc-kpi"><span>Conformes</span><strong style="color:#15803d"><?= $conteos['conforme'] ?></strong></div>
        <div class="qc-kpi"><span>No conformes</span><strong style="color:#b91c1c"><?= $conteos['no_conforme'] ?></strong></div>
    </div>

    <?php if (empty($comparaciones)): ?>
        <div class="qc-empty">
            <i class="fa-solid fa-vials" style="font-size:38px;color:#94a3b8;margin-bottom:12px;"></i>
            <h3 style="margin:0 0 7px;color:#334155;">No hay réplicas registradas</h3>
            <p style="margin:0;">Las muestras seleccionadas como réplica en una matriz aparecerán aquí después de guardar.</p>
        </div>
    <?php endif; ?>

    <?php foreach ($comparaciones as $item):
        $evaluacion = $item['evaluacion'] ?? [];
        $estado = in_array(($evaluacion['estado'] ?? ''), ['pendiente','conforme','no_conforme'], true) ? $evaluacion['estado'] : 'pendiente';
    ?>
        <section class="qc-card">
            <div class="qc-card-head">
                <div>
                    <div style="font-size:12px;color:#64748b;font-weight:700;"><?= htmlspecialchars($item['codigo_os'] ?? '', ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($item['cliente_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <div style="font-size:15px;color:#0f172a;font-weight:800;margin-top:3px;"><?= htmlspecialchars($item['descripcion_ensayo'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="qc-code-pair">
                        <span class="qc-code"><?= htmlspecialchars($item['codigo_original'], ENT_QUOTES, 'UTF-8') ?></span>
                        <i class="fa-solid fa-arrow-right" style="color:#94a3b8"></i>
                        <span class="qc-code replica"><?= htmlspecialchars($item['codigo_replica'], ENT_QUOTES, 'UTF-8') ?></span>
                    </div>
                </div>
                <span class="qc-status <?= $estado ?>"><?= $estado === 'no_conforme' ? 'No conforme' : ucfirst($estado) ?></span>
            </div>
            <div class="qc-body">
                <div>
                    <div class="qc-table-wrap">
                        <table class="qc-table">
                            <thead><tr><th>Campo medible</th><th>Original</th><th>Réplica</th><th>Diferencia</th><th>Variación</th></tr></thead>
                            <tbody>
                            <?php if (empty($item['diferencias'])): ?>
                                <tr><td colspan="5" style="text-align:center;color:#94a3b8;padding:18px;">Complete los valores numéricos de ambas filas para calcular diferencias.</td></tr>
                            <?php else: ?>
                                <?php foreach ($item['diferencias'] as $campo => $dif): ?>
                                <tr>
                                    <td style="font-weight:700;"><?= htmlspecialchars($campo, ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$dif['original'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= htmlspecialchars((string)$dif['replica'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td><?= number_format((float)$dif['diferencia'], 4, '.', ',') ?></td>
                                    <td><?= $dif['diferencia_porcentual'] === null ? 'N/A' : number_format((float)$dif['diferencia_porcentual'], 2, '.', ',') . '%' ?></td>
                                </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                    <div style="margin-top:10px;font-size:11.5px;color:#64748b;">
                        Proyecto: <strong><?= htmlspecialchars($item['nombre_proyecto'] ?? 'Sin especificar', ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php if (!empty($item['norma_astm'])): ?> · Norma: <strong><?= htmlspecialchars($item['norma_astm'], ENT_QUOTES, 'UTF-8') ?></strong><?php endif; ?>
                    </div>
                </div>

                <form class="qc-form" method="POST" action="/Cycsa/publico/control-calidad/evaluar-replica">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_detalle" value="<?= (int)$item['id'] ?>">
                    <input type="hidden" name="codigo_original" value="<?= htmlspecialchars($item['codigo_original'], ENT_QUOTES, 'UTF-8') ?>">
                    <div style="margin-bottom:12px;">
                        <label>Dictamen del control</label>
                        <select name="estado" required>
                            <option value="pendiente" <?= $estado === 'pendiente' ? 'selected' : '' ?>>Pendiente de análisis</option>
                            <option value="conforme" <?= $estado === 'conforme' ? 'selected' : '' ?>>Conforme</option>
                            <option value="no_conforme" <?= $estado === 'no_conforme' ? 'selected' : '' ?>>No conforme</option>
                        </select>
                    </div>
                    <div style="margin-bottom:12px;">
                        <label>Análisis / observaciones</label>
                        <textarea name="observaciones" maxlength="2000" placeholder="Documente tolerancias, diferencias y conclusión técnica..."><?= htmlspecialchars($evaluacion['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <?php if (!empty($evaluacion['fecha'])): ?>
                        <div style="font-size:11px;color:#64748b;margin-bottom:10px;">Última evaluación: <?= htmlspecialchars($evaluacion['usuario'] ?? '', ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($evaluacion['fecha'], ENT_QUOTES, 'UTF-8') ?></div>
                    <?php endif; ?>
                    <button type="submit" class="qc-btn"><i class="fa-solid fa-shield-halved"></i> Guardar evaluación</button>
                </form>
            </div>
        </section>
    <?php endforeach; ?>
</div>
