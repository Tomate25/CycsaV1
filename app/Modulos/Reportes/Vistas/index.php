<?php
$grupos = $grupos ?? [];
$kpisGlobales = $kpis_globales ?? [];
$totalModulos = (int)($total_modulos ?? 0);
?>
<style>
    .rep-hub-wrap { max-width: 1320px; margin: 0 auto; display: flex; flex-direction: column; gap: 24px; }
    .rep-hero {
        background: linear-gradient(135deg, #0f172a 0%, #103487 60%, #1d4ed8 100%);
        color: #fff;
        border-radius: 18px;
        padding: 26px 30px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 20px;
        flex-wrap: wrap;
        box-shadow: 0 16px 36px -12px rgba(16, 52, 135, 0.35);
    }
    .rep-hero h1 { margin: 0; font-size: 24px; font-weight: 800; letter-spacing: -0.02em; display: flex; align-items: center; gap: 12px; }
    .rep-hero p { margin: 6px 0 0; font-size: 13.5px; color: rgba(255,255,255,0.85); max-width: 680px; }
    .rep-search-box {
        background: rgba(255,255,255,0.14);
        border: 1px solid rgba(255,255,255,0.28);
        border-radius: 12px;
        padding: 8px 14px;
        display: flex;
        align-items: center;
        gap: 10px;
        min-width: 280px;
    }
    .rep-search-box input {
        background: transparent;
        border: none;
        color: #fff;
        outline: none;
        width: 100%;
        font-size: 13.5px;
    }
    .rep-search-box input::placeholder { color: rgba(255,255,255,0.72); }

    .rep-kpi-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(230px, 1fr)); gap: 16px; }
    .rep-kpi-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        padding: 18px 20px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        gap: 14px;
        box-shadow: 0 6px 18px -8px rgba(15, 23, 42, 0.08);
        position: relative;
        overflow: hidden;
    }
    .rep-kpi-card::before { content: ''; position: absolute; top: 0; left: 0; width: 100%; height: 4px; background: #103487; }
    .rep-kpi-card.kpi-1::before { background: linear-gradient(90deg, #d97706, #f59e0b); }
    .rep-kpi-card.kpi-2::before { background: linear-gradient(90deg, #ea580c, #fb923c); }
    .rep-kpi-card.kpi-3::before { background: linear-gradient(90deg, #059669, #10b981); }
    .rep-kpi-card.kpi-4::before { background: linear-gradient(90deg, #db2777, #ec4899); }

    .rep-kpi-label { font-size: 11.5px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.04em; color: #64748b; }
    .rep-kpi-val { font-size: 26px; font-weight: 800; color: #0f172a; line-height: 1.15; margin: 4px 0; }
    .rep-kpi-sub { font-size: 11.5px; color: #475569; }
    .rep-kpi-ico {
        width: 48px; height: 48px; border-radius: 12px;
        background: #eff6ff; color: #103487;
        display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;
    }

    .rep-group-title {
        font-size: 13px; font-weight: 800; text-transform: uppercase; letter-spacing: 0.06em;
        color: #334155; margin: 4px 0 12px; display: flex; align-items: center; gap: 8px;
        border-bottom: 1.5px solid #e2e8f0; padding-bottom: 8px;
    }
    .rep-drawer-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(305px, 1fr)); gap: 18px; }
    .rep-app-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        border-radius: 16px;
        padding: 20px;
        display: flex;
        align-items: flex-start;
        gap: 16px;
        text-decoration: none;
        color: inherit;
        transition: all 0.22s ease;
        box-shadow: 0 6px 20px -8px rgba(15, 23, 42, 0.07);
        position: relative;
        overflow: hidden;
    }
    .rep-app-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 18px 34px -10px rgba(16, 52, 135, 0.16);
        border-color: #93c5fd;
    }
    .rep-app-ico {
        width: 54px; height: 54px; border-radius: 14px;
        background: var(--app-color, linear-gradient(135deg, #103487, #2563eb));
        color: #fff;
        display: flex; align-items: center; justify-content: center;
        font-size: 22px; flex-shrink: 0;
        box-shadow: 0 8px 16px -6px rgba(15, 23, 42, 0.25);
    }
    .rep-app-body { flex: 1; min-width: 0; }
    .rep-app-head { display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 5px; }
    .rep-app-name { font-size: 15px; font-weight: 800; color: #0f172a; }
    .rep-app-count {
        background: #f1f5f9; color: #0f172a; border: 1px solid #cbd5e1;
        padding: 2px 9px; border-radius: 999px; font-size: 11px; font-weight: 800; white-space: nowrap;
    }
    .rep-app-desc { margin: 0 0 10px; font-size: 12.5px; color: #475569; line-height: 1.45; }
    .rep-app-tags { display: flex; gap: 6px; flex-wrap: wrap; }
    .rep-tag {
        font-size: 10.5px; font-weight: 700; padding: 2px 8px; border-radius: 6px;
        display: inline-flex; align-items: center; gap: 4px;
    }
    .rep-tag-xlsx { background: #dcfce7; color: #166534; }
    .rep-tag-pdf { background: #fee2e2; color: #991b1b; }
    .rep-tag-kpi { background: #eff6ff; color: #1e40af; }
</style>

<div class="rep-hub-wrap">
    <div class="rep-hero">
        <div>
            <h1><i class="fa-solid fa-chart-pie"></i> Centro de Reportes &amp; Trazabilidad CYCSA</h1>
            <p>
                Cajón unificado de mini-módulos con tarjetas KPI, filtros dinámicos, descarga nativa en <strong>Excel (.xlsx)</strong> y acceso directo a los documentos oficiales PDF (Cotizaciones, RG-FM-39, RT-FM-13, RT-FM-40 B, RT-FM-60, RT-FM-22, Facturas, Compras y Asientos).
            </p>
        </div>
        <div class="rep-search-box">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="buscar-mini-modulo" placeholder="Filtrar reporte (ej. RT-FM-13, 40 B, 60, Compras...)" autocomplete="off">
        </div>
    </div>

    <?php if (!empty($kpisGlobales)): ?>
    <div class="rep-kpi-grid">
        <?php foreach ($kpisGlobales as $k): ?>
        <div class="rep-kpi-card <?= htmlspecialchars($k['clase'] ?? 'kpi-1', ENT_QUOTES, 'UTF-8') ?>">
            <div>
                <div class="rep-kpi-label"><?= htmlspecialchars($k['titulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="rep-kpi-val"><?= htmlspecialchars($k['valor'] ?? '0', ENT_QUOTES, 'UTF-8') ?></div>
                <div class="rep-kpi-sub"><?= htmlspecialchars($k['subtitulo'] ?? '', ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="rep-kpi-ico">
                <i class="<?= htmlspecialchars($k['icono'] ?? 'fa-solid fa-chart-simple', ENT_QUOTES, 'UTF-8') ?>"></i>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <?php if (empty($grupos)): ?>
        <div style="background:#fff; border:1px dashed #cbd5e1; border-radius:14px; padding:40px; text-align:center; color:#64748b;">
            <i class="fa-solid fa-lock" style="font-size:32px; margin-bottom:10px; display:block;"></i>
            No tienes mini-módulos habilitados bajo tus permisos actuales (<?= $totalModulos ?> disponibles).
        </div>
    <?php else: ?>
        <?php foreach ($grupos as $nombreGrupo => $tarjetas): ?>
        <div class="rep-group-section">
            <div class="rep-group-title">
                <i class="fa-solid fa-layer-group" style="color:#103487;"></i>
                <span><?= htmlspecialchars($nombreGrupo, ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div class="rep-drawer-grid">
                <?php foreach ($tarjetas as $t): ?>
                <a href="/Cycsa/publico/reportes/ver?modulo=<?= urlencode((string)$t['slug']) ?>"
                   class="rep-app-card"
                   data-search="<?= htmlspecialchars(mb_strtolower($t['nombre'] . ' ' . $t['desc'] . ' ' . $nombreGrupo, 'UTF-8'), ENT_QUOTES, 'UTF-8') ?>"
                   style="--app-color: <?= htmlspecialchars($t['color'], ENT_QUOTES, 'UTF-8') ?>;">
                    <div class="rep-app-ico">
                        <i class="<?= htmlspecialchars($t['icono'], ENT_QUOTES, 'UTF-8') ?>"></i>
                    </div>
                    <div class="rep-app-body">
                        <div class="rep-app-head">
                            <span class="rep-app-name"><?= htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="rep-app-count"><?= (int)($t['total_registros'] ?? 0) ?> reg.</span>
                        </div>
                        <p class="rep-app-desc"><?= htmlspecialchars($t['desc'], ENT_QUOTES, 'UTF-8') ?></p>
                        <div class="rep-app-tags">
                            <span class="rep-tag rep-tag-kpi"><i class="fa-solid fa-gauge-high"></i> KPIs</span>
                            <span class="rep-tag rep-tag-xlsx"><i class="fa-solid fa-file-excel"></i> Excel .xlsx</span>
                            <?php if (in_array($t['slug'], ['cotizaciones', 'ventas', 'ordenes_servicio', 'rtfm13', 'fm40b', 'laboratorio', 'operaciones'], true)): ?>
                            <span class="rep-tag rep-tag-pdf"><i class="fa-solid fa-file-pdf"></i> Con PDFs</span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
(function() {
    const inp = document.getElementById('buscar-mini-modulo');
    if (!inp) return;
    inp.addEventListener('input', function() {
        const term = (this.value || '').trim().toLowerCase();
        document.querySelectorAll('.rep-group-section').forEach(sec => {
            let visibles = 0;
            sec.querySelectorAll('.rep-app-card').forEach(card => {
                const hay = (card.dataset.search || '').includes(term);
                card.style.display = hay ? 'flex' : 'none';
                if (hay) visibles++;
            });
            sec.style.display = visibles > 0 ? 'block' : 'none';
        });
    });
})();
</script>
