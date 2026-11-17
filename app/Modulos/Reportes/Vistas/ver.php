<?php
$slug = (string)($slug ?? 'cotizaciones');
$reporte = $reporte ?? [];
$meta = $reporte['meta'] ?? [];
$filtros = $reporte['filtros'] ?? [];
$kpis = $reporte['kpis'] ?? [];
$columns = $reporte['columns'] ?? [];
$rows = $reporte['rows'] ?? [];
$subvistas = $reporte['subvistas'] ?? [];
$estadosFiltro = $reporte['estados_filtro'] ?? [];
$navegacionModulos = $navegacionModulos ?? [];

$queryExcel = http_build_query([
    'modulo' => $slug,
    'desde' => $filtros['desde'] ?? '',
    'hasta' => $filtros['hasta'] ?? '',
    'estado' => $filtros['estado'] ?? '',
    'q' => $filtros['q'] ?? '',
    'subvista' => $filtros['subvista'] ?? '',
]);

$tienePdfs = false;
foreach ($rows as $rw) {
    if (!empty($rw['_pdfs'])) {
        $tienePdfs = true;
        break;
    }
}
?>
<style>
    .rep-view-wrap { max-width: 1400px; margin: 0 auto; display: flex; flex-direction: column; gap: 18px; }
    .rep-top-bar {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 14px;
        padding: 16px 20px; display: flex; justify-content: space-between; align-items: center;
        flex-wrap: wrap; gap: 14px; box-shadow: 0 4px 14px -6px rgba(15, 23, 42, 0.08);
    }
    .rep-back-link {
        color: #475569; text-decoration: none; font-size: 13px; font-weight: 700;
        display: inline-flex; align-items: center; gap: 6px; margin-bottom: 4px;
    }
    .rep-back-link:hover { color: #103487; }
    .rep-title-row { display: flex; align-items: center; gap: 12px; }
    .rep-mod-badge {
        width: 42px; height: 42px; border-radius: 11px; color: #fff;
        background: var(--mod-color, #103487); display: flex; align-items: center; justify-content: center; font-size: 18px;
    }
    .rep-title-row h2 { margin: 0; font-size: 19px; font-weight: 800; color: #0f172a; }
    .rep-title-row small { display: block; font-size: 12.5px; color: #64748b; }

    .rep-top-actions { display: flex; align-items: center; gap: 10px; flex-wrap: wrap; }
    .rep-select-mod {
        height: 38px; border: 1px solid #cbd5e1; border-radius: 8px; padding: 0 12px;
        font-size: 13px; font-weight: 600; color: #0f172a; background: #f8fafc;
    }
    .btn-excel-export {
        display: inline-flex; align-items: center; gap: 8px; height: 38px; padding: 0 16px;
        border-radius: 8px; background: #15803d; color: #fff; font-weight: 700; font-size: 13px;
        text-decoration: none; border: none; box-shadow: 0 4px 10px -3px rgba(21, 128, 61, 0.4);
    }
    .btn-excel-export:hover { background: #166534; color: #fff; }

    .rep-kpi-grid-4 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; }
    .rep-kpi-box {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 16px 18px;
        display: flex; justify-content: space-between; align-items: center; gap: 12px;
        border-top: 4px solid var(--kpi-c, #103487);
        box-shadow: 0 4px 14px -8px rgba(15, 23, 42, 0.08);
    }
    .rep-kpi-box .lbl { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; }
    .rep-kpi-box .val { font-size: 22px; font-weight: 800; color: #0f172a; margin: 3px 0; }
    .rep-kpi-box .sub { font-size: 11.5px; color: #475569; }
    .rep-kpi-box .ico {
        width: 42px; height: 42px; border-radius: 10px; background: #f8fafc; color: var(--kpi-c, #103487);
        display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0;
    }

    .rep-subtabs { display: flex; gap: 8px; flex-wrap: wrap; }
    .rep-subtab {
        padding: 8px 14px; border-radius: 9px; font-size: 12.5px; font-weight: 700;
        text-decoration: none; border: 1px solid #cbd5e1; background: #fff; color: #334155;
    }
    .rep-subtab.activo { background: #103487; color: #fff; border-color: #103487; }

    .rep-filters-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 14px 18px;
        display: flex; gap: 12px; align-items: flex-end; flex-wrap: wrap;
    }
    .rep-f-group { display: flex; flex-direction: column; gap: 4px; min-width: 140px; }
    .rep-f-group label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; }
    .rep-f-group input, .rep-f-group select {
        height: 36px; border: 1px solid #cbd5e1; border-radius: 7px; padding: 0 10px; font-size: 13px;
    }

    .rep-table-card {
        background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden;
        box-shadow: 0 6px 20px -10px rgba(15, 23, 42, 0.08);
    }
    .rep-table-head-bar {
        padding: 12px 18px; background: #f8fafc; border-bottom: 1px solid #e2e8f0;
        display: flex; justify-content: space-between; align-items: center; font-size: 13px; font-weight: 700; color: #334155;
    }
    .rep-table-scroll { overflow-x: auto; }
    .rep-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .rep-table thead th {
        background: #103487; color: #fff; font-weight: 700; text-align: left;
        padding: 10px 12px; white-space: nowrap; font-size: 12px;
    }
    .rep-table tbody td {
        padding: 9px 12px; border-bottom: 1px solid #f1f5f9; color: #1e293b; vertical-align: middle;
    }
    .rep-table tbody tr:hover { background: #f8fafc; }
    .rep-num { text-align: right; font-variant-numeric: tabular-nums; white-space: nowrap; font-weight: 600; }
    .rep-badge {
        display: inline-block; padding: 3px 9px; border-radius: 999px; font-size: 11px; font-weight: 700;
        background: #e2e8f0; color: #334155; white-space: nowrap;
    }
    .rep-badge.ok { background: #dcfce7; color: #166534; }
    .rep-badge.warn { background: #fef3c7; color: #92400e; }
    .rep-badge.danger { background: #fee2e2; color: #991b1b; }
    .rep-badge.info { background: #dbeafe; color: #1e40af; }
    .rep-pdf-btn {
        display: inline-flex; align-items: center; gap: 5px; padding: 4px 9px; border-radius: 6px;
        font-size: 11px; font-weight: 700; color: #fff; text-decoration: none; margin: 2px 3px 2px 0;
        white-space: nowrap;
    }
    .rep-pdf-btn:hover { opacity: 0.9; color: #fff; }
</style>

<div class="rep-view-wrap">
    <div class="rep-top-bar">
        <div>
            <a href="/Cycsa/publico/reportes" class="rep-back-link">
                <i class="fa-solid fa-arrow-left"></i> Volver al Cajón de Reportes
            </a>
            <div class="rep-title-row">
                <div class="rep-mod-badge" style="--mod-color: <?= htmlspecialchars($meta['color'] ?? '#103487', ENT_QUOTES, 'UTF-8') ?>;">
                    <i class="<?= htmlspecialchars($meta['icono'] ?? 'fa-solid fa-chart-bar', ENT_QUOTES, 'UTF-8') ?>"></i>
                </div>
                <div>
                    <h2><?= htmlspecialchars($meta['nombre'] ?? 'Reporte', ENT_QUOTES, 'UTF-8') ?></h2>
                    <small><?= htmlspecialchars($meta['desc'] ?? '', ENT_QUOTES, 'UTF-8') ?></small>
                </div>
            </div>
        </div>

        <div class="rep-top-actions">
            <?php if (!empty($navegacionModulos)): ?>
            <select class="rep-select-mod" onchange="if(this.value) window.location.href='/Cycsa/publico/reportes/ver?modulo=' + encodeURIComponent(this.value);">
                <?php foreach ($navegacionModulos as $navSlug => $navMeta): ?>
                <option value="<?= htmlspecialchars($navSlug, ENT_QUOTES, 'UTF-8') ?>" <?= $navSlug === $slug ? 'selected' : '' ?>>
                    <?= htmlspecialchars($navMeta['nombre'], ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>

            <a href="/Cycsa/publico/reportes/exportar-excel?<?= htmlspecialchars($queryExcel, ENT_QUOTES, 'UTF-8') ?>" class="btn-excel-export">
                <i class="fa-solid fa-file-excel"></i> Exportar Excel (.xlsx)
            </a>
        </div>
    </div>

    <!-- TARJETAS KPI -->
    <div class="rep-kpi-grid-4">
        <?php foreach ($kpis as $k): ?>
        <div class="rep-kpi-box" style="--kpi-c: <?= htmlspecialchars($k['color'] ?? '#103487', ENT_QUOTES, 'UTF-8') ?>;">
            <div>
                <div class="lbl"><?= htmlspecialchars($k['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="val"><?= htmlspecialchars($k['valor'] ?? '0', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="sub"><?= htmlspecialchars($k['subtitulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="ico">
                <i class="<?= htmlspecialchars($k['icono'] ?? 'fa-solid fa-chart-simple', ENT_QUOTES, 'UTF-8') ?>"></i>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- SUBVISTAS SI APLICAN -->
    <?php if (!empty($subvistas)): ?>
    <?php $subActual = $filtros['subvista'] !== '' ? $filtros['subvista'] : array_key_first($subvistas); ?>
    <div class="rep-subtabs">
        <?php foreach ($subvistas as $subKey => $subLbl): ?>
        <a href="/Cycsa/publico/reportes/ver?modulo=<?= urlencode($slug) ?>&subvista=<?= urlencode($subKey) ?>"
           class="rep-subtab <?= $subKey === $subActual ? 'activo' : '' ?>">
            <?= htmlspecialchars($subLbl, ENT_QUOTES, 'UTF-8') ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- FILTROS -->
    <form method="GET" action="/Cycsa/publico/reportes/ver" class="rep-filters-card">
        <input type="hidden" name="modulo" value="<?= htmlspecialchars($slug, ENT_QUOTES, 'UTF-8') ?>">
        <?php if (!empty($filtros['subvista'])): ?>
        <input type="hidden" name="subvista" value="<?= htmlspecialchars($filtros['subvista'], ENT_QUOTES, 'UTF-8') ?>">
        <?php endif; ?>

        <div class="rep-f-group">
            <label for="f_desde">Desde</label>
            <input type="date" id="f_desde" name="desde" value="<?= htmlspecialchars($filtros['desde'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="rep-f-group">
            <label for="f_hasta">Hasta</label>
            <input type="date" id="f_hasta" name="hasta" value="<?= htmlspecialchars($filtros['hasta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <?php if (!empty($estadosFiltro)): ?>
        <div class="rep-f-group">
            <label for="f_estado">Estado</label>
            <select id="f_estado" name="estado">
                <option value="">Todos</option>
                <?php foreach ($estadosFiltro as $estOpc): ?>
                <option value="<?= htmlspecialchars($estOpc, ENT_QUOTES, 'UTF-8') ?>" <?= ($filtros['estado'] ?? '') === $estOpc ? 'selected' : '' ?>>
                    <?= htmlspecialchars($estOpc, ENT_QUOTES, 'UTF-8') ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>
        <?php endif; ?>
        <div class="rep-f-group" style="flex: 1; min-width: 220px;">
            <label for="f_q">Buscar (Cliente, Código, Proyecto, Muestra...)</label>
            <input type="text" id="f_q" name="q" value="<?= htmlspecialchars($filtros['q'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Escriba para filtrar resultados...">
        </div>
        <div style="display:flex; gap:8px;">
            <button type="submit" class="btn-cycsa btn-cycsa-primary" style="height:36px;">
                <i class="fa-solid fa-filter"></i> Filtrar
            </button>
            <a href="/Cycsa/publico/reportes/ver?modulo=<?= urlencode($slug) ?><?= !empty($filtros['subvista']) ? '&subvista=' . urlencode($filtros['subvista']) : '' ?>"
               class="btn-cycsa btn-cycsa-secondary" style="height:36px;">
                Limpiar
            </a>
        </div>
    </form>

    <!-- TABLA DE SEGUIMIENTO -->
    <div class="rep-table-card">
        <div class="rep-table-head-bar">
            <span><i class="fa-solid fa-table-list me-1" style="color:#103487;"></i> Registros encontrados: <strong><?= count($rows) ?></strong></span>
            <span style="font-size:12px; color:#64748b;">Los filtros aplicados también se reflejan en el archivo Excel (.xlsx)</span>
        </div>

        <div class="rep-table-scroll">
            <table class="rep-table">
                <thead>
                    <tr>
                        <?php foreach ($columns as $col): ?>
                        <th style="<?= in_array($col['type'] ?? '', ['money', 'number'], true) ? 'text-align:right;' : '' ?>">
                            <?= htmlspecialchars($col['label'], ENT_QUOTES, 'UTF-8') ?>
                        </th>
                        <?php endforeach; ?>
                        <?php if ($tienePdfs): ?>
                        <th>Documentos / PDFs</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($rows)): ?>
                    <tr>
                        <td colspan="<?= count($columns) + ($tienePdfs ? 1 : 0) ?>" style="text-align:center; padding:34px; color:#64748b;">
                            <i class="fa-solid fa-inbox" style="font-size:26px; display:block; margin-bottom:8px;"></i>
                            No hay registros que coincidan con los filtros seleccionados.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($rows as $r): ?>
                        <tr>
                            <?php foreach ($columns as $col):
                                $k = $col['key'];
                                $tipo = $col['type'] ?? 'text';
                                $val = $r[$k] ?? '';
                            ?>
                                <?php if ($tipo === 'money'): ?>
                                    <td class="rep-num">C$ <?= number_format((float)$val, 2) ?></td>
                                <?php elseif ($tipo === 'number'): ?>
                                    <td class="rep-num"><?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?></td>
                                <?php elseif ($tipo === 'badge'):
                                    $vLow = mb_strtolower((string)$val, 'UTF-8');
                                    $bCls = 'info';
                                    if (preg_match('/aprobad|pagad|finaliz|complet|vigente|activo|disponible|calificado|recibida/u', $vLow)) {
                                        $bCls = 'ok';
                                    } elseif (preg_match('/rechaz|anulad|devuelt|vencid|bajo|inactivo/u', $vLow)) {
                                        $bCls = 'danger';
                                    } elseif (preg_match('/pendiente|borrador|observad|parcial|revision|campo|programad/u', $vLow)) {
                                        $bCls = 'warn';
                                    }
                                ?>
                                    <td><span class="rep-badge <?= $bCls ?>"><?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?></span></td>
                                <?php else: ?>
                                    <td><?= htmlspecialchars((string)$val, ENT_QUOTES, 'UTF-8') ?></td>
                                <?php endif; ?>
                            <?php endforeach; ?>

                            <?php if ($tienePdfs): ?>
                            <td style="white-space:nowrap;">
                                <?php foreach (($r['_pdfs'] ?? []) as $pdf): ?>
                                <a href="<?= htmlspecialchars($pdf['url'], ENT_QUOTES, 'UTF-8') ?>"
                                   target="_blank"
                                   class="rep-pdf-btn"
                                   style="background: <?= htmlspecialchars($pdf['color'] ?? '#103487', ENT_QUOTES, 'UTF-8') ?>;">
                                    <i class="<?= htmlspecialchars($pdf['icon'] ?? 'fa-solid fa-file-pdf', ENT_QUOTES, 'UTF-8') ?>"></i>
                                    <?= htmlspecialchars($pdf['label'], ENT_QUOTES, 'UTF-8') ?>
                                </a>
                                <?php endforeach; ?>
                            </td>
                            <?php endif; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
