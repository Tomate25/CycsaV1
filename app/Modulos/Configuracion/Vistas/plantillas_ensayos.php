<?php
$esc = static fn($valor) => htmlspecialchars((string)$valor, ENT_QUOTES, 'UTF-8');
$totalHorizontales = 0;
$totalVerticales = 0;
$totalProductosVinculados = 0;
foreach ($plantillas as $p) {
    if (($p['orientacion'] ?? 'horizontal') === 'vertical') {
        $totalVerticales++;
    } else {
        $totalHorizontales++;
    }
    $totalProductosVinculados += (int)($p['total_productos'] ?? 0);
}
?>
<style>
    .plantillas-wrap { max-width: 1400px; margin: 0 auto; padding: 18px 24px 60px 24px; color: #1e293b; font-family: 'Inter', sans-serif; }
    
    /* Pestañas de Navegación Superior */
    .tabs-nav-top { display: flex; gap: 8px; margin-bottom: 22px; border-bottom: 2px solid #e2e8f0; padding-bottom: 1px; }
    .tab-nav-item {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 10px 18px;
        font-size: 13.5px;
        font-weight: 700;
        text-decoration: none;
        color: #64748b;
        border-bottom: 3px solid transparent;
        transition: all 0.2s;
    }
    .tab-nav-item:hover { color: #103487; }
    .tab-nav-item.active { color: #103487; border-bottom-color: #103487; }

    /* Encabezado y KPIs */
    .plantillas-head { display: flex; justify-content: space-between; align-items: center; gap: 18px; margin-bottom: 20px; flex-wrap: wrap; }
    .plantillas-head h1 { margin: 0 0 4px 0; font-size: 24px; font-weight: 800; color: #0f172a; font-family: 'Outfit', sans-serif; }
    .plantillas-head p { margin: 0; font-size: 13px; color: #64748b; }
    
    .kpi-row { display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 14px; margin-bottom: 22px; }
    .kpi-box { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 18px; display: flex; align-items: center; gap: 14px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
    .kpi-box-icon { width: 44px; height: 44px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 18px; flex-shrink: 0; }
    .kpi-box-val h3 { margin: 0; font-size: 20px; font-weight: 800; font-family: 'Outfit', sans-serif; color: #0f172a; }
    .kpi-box-val span { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; letter-spacing: 0.4px; }

    /* Barra de Herramientas y Filtros */
    .tools-bar { display: flex; justify-content: space-between; align-items: center; gap: 14px; margin-bottom: 16px; flex-wrap: wrap; }
    .search-input-wrap { position: relative; width: 320px; max-width: 100%; }
    .search-input-wrap i { position: absolute; left: 12px; top: 50%; transform: translateY(-50%); color: #94a3b8; font-size: 13px; }
    .search-input-wrap input { width: 100%; padding: 8px 12px 8px 34px; border: 1px solid #cbd5e1; border-radius: 7px; font-size: 13px; box-sizing: border-box; outline: none; }
    .search-input-wrap input:focus { border-color: #103487; box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08); }

    /* Botones Corporativos */
    .btn-action-main {
        display: inline-flex;
        align-items: center;
        gap: 7px;
        padding: 9px 16px;
        border-radius: 7px;
        font-size: 13px;
        font-weight: 700;
        text-decoration: none;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.2s;
    }
    .btn-create { background: #103487; color: #ffffff; }
    .btn-create:hover { background: #0c2766; color: #ffffff; transform: translateY(-1px); box-shadow: 0 4px 12px rgba(16, 52, 135, 0.2); }
    
    /* Tabla de Plantillas */
    .plantillas-panel { background: #fff; border: 1px solid #e2e8f0; border-radius: 12px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
    .plantillas-table { width: 100%; border-collapse: collapse; font-size: 13px; }
    .plantillas-table th, .plantillas-table td { padding: 13px 16px; text-align: left; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
    .plantillas-table th { background: #f8fafc; color: #475569; font-size: 11px; text-transform: uppercase; font-weight: 800; letter-spacing: 0.5px; border-bottom: 2px solid #e2e8f0; }
    .plantillas-table tr:hover td { background: #f8fafc; }

    /* Badges */
    .badge-code { background: #0f172a; color: #ffffff; font-family: monospace; font-size: 11.5px; font-weight: 700; padding: 4px 8px; border-radius: 4px; display: inline-block; }
    .badge-orient { display: inline-flex; align-items: center; gap: 5px; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 12px; }
    .badge-orient.horizontal { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .badge-orient.vertical { background: #f5f3ff; color: #6d28d9; border: 1px solid #ddd6fe; }
    .badge-products { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 12px; }

    /* Botones de Fila */
    .row-btn-group { display: inline-flex; gap: 6px; }
    .row-btn {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 11px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        cursor: pointer;
        border: 1px solid #cbd5e1;
        background: #ffffff;
        color: #334155;
        transition: all 0.15s;
        text-decoration: none;
    }
    .row-btn:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
    .row-btn.primary { background: #103487; color: #ffffff; border-color: #103487; }
    .row-btn.primary:hover { background: #0c2766; color: #ffffff; }
    .row-btn.pdf { background: #fef2f2; color: #dc2626; border-color: #fecaca; }
    .row-btn.pdf:hover { background: #fee2e2; color: #b91c1c; }

    /* Modal */
    .plantilla-modal { display: none; position: fixed; inset: 0; z-index: 1200; background: rgba(15,23,42,.7); padding: 18px; overflow: auto; }
    .plantilla-modal.open { display: block; }
    .plantilla-dialog { max-width: 1040px; margin: 20px auto; background: #fff; border-radius: 14px; box-shadow: 0 24px 65px rgba(0,0,0,.25); overflow: hidden; display: flex; flex-direction: column; }
    .plantilla-dialog-head { display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; border-bottom: 1.5px solid #e2e8f0; background: #0f172a; color: #ffffff; }
    .plantilla-dialog-head h2 { margin: 0; font-size: 18px; font-weight: 800; font-family: 'Outfit', sans-serif; display: flex; align-items: center; gap: 10px; }
    .plantilla-dialog-head button { background: transparent; border: 0; font-size: 26px; cursor: pointer; color: #94a3b8; line-height: 1; }
    .plantilla-dialog-head button:hover { color: #ffffff; }
    
    .plantilla-tabs { display: flex; gap: 4px; padding: 12px 24px 0; overflow-x: auto; border-bottom: 1px solid #e2e8f0; background: #f8fafc; }
    .plantilla-tabs button { border: 0; background: transparent; padding: 10px 14px; white-space: nowrap; cursor: pointer; color: #64748b; font-size: 13px; font-weight: 700; border-bottom: 3px solid transparent; transition: all 0.2s; }
    .plantilla-tabs button:hover { color: #103487; }
    .plantilla-tabs button.active { color: #103487; border-bottom-color: #103487; background: #ffffff; border-radius: 6px 6px 0 0; }
    
    .plantilla-body { padding: 22px 24px; max-height: 60vh; overflow-y: auto; }
    .plantilla-pane { display: none; }
    .plantilla-pane.active { display: block; }
    
    .plantilla-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; }
    .plantilla-field { display: flex; flex-direction: column; gap: 5px; font-size: 12px; font-weight: 700; color: #475569; }
    .plantilla-field.wide { grid-column: 1 / -1; }
    .plantilla-field input, .plantilla-field textarea, .plantilla-field select, .plantilla-list input { width: 100%; box-sizing: border-box; padding: 9px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font: inherit; color: #1e293b; background: #ffffff; outline: none; }
    .plantilla-field input:focus, .plantilla-field textarea:focus, .plantilla-field select:focus, .plantilla-list input:focus { border-color: #103487; box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08); }
    .plantilla-field textarea { min-height: 80px; resize: vertical; }
    
    /* Columnas Reordenables */
    .plantilla-list { display: grid; gap: 8px; margin-top: 10px; }
    .plantilla-row { display: grid; grid-template-columns: 80px 110px minmax(0, 1.4fr) minmax(0, 1fr) auto; gap: 8px; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 6px 10px; }
    .plantilla-row.nota { grid-template-columns: 1fr auto; }
    .col-order-btns { display: inline-flex; gap: 4px; }
    .btn-order { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 4px; padding: 4px 7px; cursor: pointer; font-size: 11px; color: #475569; }
    .btn-order:hover { background: #e2e8f0; color: #0f172a; }
    .btn-delete-row { background: #fef2f2; border: 1px solid #fecaca; color: #dc2626; border-radius: 6px; padding: 6px 10px; cursor: pointer; font-weight: 700; font-size: 12px; }
    .btn-delete-row:hover { background: #fee2e2; }
    .plantilla-add { display: inline-flex; align-items: center; gap: 6px; margin-top: 12px; background: #eff6ff; border: 1px dashed #93c5fd; color: #1d4ed8; padding: 8px 14px; border-radius: 6px; font-weight: 700; font-size: 12.5px; cursor: pointer; }
    .plantilla-add:hover { background: #dbeafe; }

    /* Ensayos Vinculados Checklist */
    .prods-picker-box { max-height: 220px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px; background: #f8fafc; display: grid; gap: 8px; }
    .prod-picker-item { display: flex; align-items: center; gap: 10px; font-size: 12.5px; background: #ffffff; border: 1px solid #e2e8f0; padding: 8px 12px; border-radius: 6px; cursor: pointer; }
    .prod-picker-item input[type="checkbox"] { width: 16px; height: 16px; cursor: pointer; }
    .prod-picker-item:hover { border-color: #103487; background: #f0f7ff; }

    .plantilla-actions { display: flex; justify-content: space-between; align-items: center; gap: 12px; padding: 16px 24px; border-top: 1.5px solid #e2e8f0; background: #f8fafc; flex-wrap: wrap; }
    .restablecer-plantilla { background: #ffffff; border: 1px solid #fca5a5; color: #b91c1c; padding: 9px 14px; border-radius: 7px; font-weight: 700; font-size: 13px; cursor: pointer; }
    .restablecer-plantilla:hover { background: #fef2f2; }
    .guardar-plantilla { background: #103487; border: 1px solid #103487; color: #ffffff; padding: 9px 20px; border-radius: 7px; font-weight: 700; font-size: 13px; cursor: pointer; transition: all 0.2s; }
    .guardar-plantilla:hover { background: #0c2766; box-shadow: 0 4px 12px rgba(16, 52, 135, 0.2); }
    .btn-preview-pdf { background: #ffffff; border: 1px solid #cbd5e1; color: #334155; padding: 9px 14px; border-radius: 7px; font-weight: 700; font-size: 13px; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; text-decoration: none; }
    .btn-preview-pdf:hover { background: #f1f5f9; color: #0f172a; border-color: #94a3b8; }
    .plantilla-status { padding: 4px 24px; color: #b91c1c; font-size: 12.5px; font-weight: 600; }

    /* Estilos del Visualizador de Fórmulas y Cálculos */
    .col-pill { display: inline-flex; align-items: center; justify-content: center; padding: 5px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; white-space: nowrap; user-select: none; }
    .col-pill.calc { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
    .col-pill.input { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
    .col-pill.generic { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }

    .formulas-wrap { display: flex; flex-direction: column; gap: 16px; }
    .formulas-banner { background: linear-gradient(135deg, #f0f9ff 0%, #e0f2fe 100%); border: 1px solid #bae6fd; border-radius: 10px; padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; gap: 16px; }
    .formulas-banner h3 { margin: 0 0 4px 0; font-size: 15px; color: #0369a1; }
    .formulas-banner p { margin: 0; font-size: 12px; color: #334155; line-height: 1.45; }
    .formulas-summary-bar { display: flex; gap: 8px; flex-shrink: 0; }
    .formula-stat-pill { background: #fff; padding: 6px 12px; border-radius: 6px; border: 1px solid #cbd5e1; font-size: 12px; font-weight: 700; color: #334155; }
    .formula-stat-pill.activa { background: #ecfdf5; border-color: #86efac; color: #065f46; }

    .formulas-cards-grid { display: grid; gap: 14px; }
    .formula-card { background: #fff; border: 1px solid #e2e8f0; border-radius: 10px; padding: 14px 16px; transition: all .2s ease; }
    .formula-card.activa { border-color: #10b981; box-shadow: 0 3px 12px rgba(16, 185, 129, 0.08); background: #fbfdfc; }
    .formula-card.inactiva { background: #f8fafc; border-color: #e2e8f0; opacity: 0.72; }
    .formula-card-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 8px; }
    .formula-card-title { font-size: 13.5px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 8px; }
    .formula-badge-norma { background: #e2e8f0; color: #334155; font-size: 10.5px; font-weight: 700; padding: 2px 7px; border-radius: 4px; font-family: monospace; }
    .formula-badge-status { padding: 3px 10px; border-radius: 20px; font-size: 11px; font-weight: 700; }
    .formula-badge-status.activa { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
    .formula-badge-status.inactiva { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }
    .formula-desc { font-size: 12px; color: #475569; margin: 0 0 10px 0; }
    .math-equation-box { background: #0f172a; color: #38bdf8; padding: 10px 14px; border-radius: 6px; font-family: 'Consolas', 'Courier New', monospace; font-size: 12px; margin: 8px 0; line-height: 1.5; }
    .math-equation-box code { display: block; }
    .formula-flow { display: flex; flex-wrap: wrap; align-items: center; gap: 8px; font-size: 11.5px; margin-top: 10px; padding-top: 10px; border-top: 1px dashed #e2e8f0; }
    .formula-flow-label { font-weight: 700; color: #475569; }
    .formula-pill-var { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; padding: 2px 7px; border-radius: 4px; font-weight: 600; }
    .formula-pill-res { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; padding: 2px 7px; border-radius: 4px; font-weight: 700; }
    .formula-arrow { color: #94a3b8; font-weight: 700; }

    @media (max-width: 768px) {
        .plantilla-grid { grid-template-columns: 1fr; }
        .plantilla-row { grid-template-columns: 1fr; }
        .tools-bar { flex-direction: column; align-items: stretch; }
        .search-input-wrap { width: 100%; }
    }
</style>

<div class="plantillas-wrap">
    
    <!-- PESTAÑAS DE NAVEGACIÓN SUPERIOR -->
    <div class="tabs-nav-top">
        <a href="/Cycsa/publico/configuracion/plantillas-ensayos" class="tab-nav-item active">
            <i class="fa-solid fa-file-pdf"></i> Formatos Oficiales RT-FM-22 (Informes PDF y Matrices)
        </a>
        <a href="/Cycsa/publico/plantillas" class="tab-nav-item">
            <i class="fa-solid fa-shapes"></i> Catálogo Dinámico ISO 17025
        </a>
        <a href="/Cycsa/publico/configuracion" class="tab-nav-item">
            <i class="fa-solid fa-gears"></i> Condiciones Comerciales
        </a>
    </div>

    <!-- ENCABEZADO -->
    <div class="plantillas-head">
        <div>
            <h1>
                <i class="fa-solid fa-file-invoice" style="color:#103487;"></i>
                Gestor de Formatos de Ensayo e Informes Oficiales (RT-FM-22)
            </h1>
            <p>
                Modifica y crea formatos técnicos oficiales. Todos los cambios se reflejan inmediatamente en la captura de matrices, impresión y PDF oficial con los 14 metadatos obligatorios ISO/IEC 17025.
            </p>
        </div>
        <div>
            <button type="button" class="btn-action-main btn-create" onclick="abrirModalCrear()">
                <i class="fa-solid fa-plus"></i> Crear Nuevo Formato de Ensayo
            </button>
        </div>
    </div>

    <!-- TARJETAS DE RESUMEN KPI -->
    <div class="kpi-row">
        <div class="kpi-box">
            <div class="kpi-box-icon" style="background:#eff6ff; color:#1d4ed8;">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div class="kpi-box-val">
                <h3><?= count($plantillas) ?></h3>
                <span>Formatos Oficiales</span>
            </div>
        </div>
        <div class="kpi-box">
            <div class="kpi-box-icon" style="background:#f0fdf4; color:#15803d;">
                <i class="fa-solid fa-arrows-left-right"></i>
            </div>
            <div class="kpi-box-val">
                <h3><?= $totalHorizontales ?></h3>
                <span>Orientación Horizontal</span>
            </div>
        </div>
        <div class="kpi-box">
            <div class="kpi-box-icon" style="background:#f5f3ff; color:#6d28d9;">
                <i class="fa-solid fa-arrows-up-down"></i>
            </div>
            <div class="kpi-box-val">
                <h3><?= $totalVerticales ?></h3>
                <span>Orientación Vertical</span>
            </div>
        </div>
        <div class="kpi-box">
            <div class="kpi-box-icon" style="background:#fffbeb; color:#b45309;">
                <i class="fa-solid fa-vial"></i>
            </div>
            <div class="kpi-box-val">
                <h3><?= $totalProductosVinculados ?></h3>
                <span>Ensayos Asociados</span>
            </div>
        </div>
    </div>

    <!-- HERRAMIENTAS Y BÚSQUEDA -->
    <div class="tools-bar">
        <div class="search-input-wrap">
            <i class="fa-solid fa-magnifying-glass"></i>
            <input type="text" id="filtro-formatos" placeholder="Buscar formato, código o norma..." oninput="filtrarTablaFormatos()">
        </div>
        <div style="font-size:12.5px; color:#64748b;">
            Mostrando <strong id="conteo-visibles" style="color:#0f172a;"><?= count($plantillas) ?></strong> formatos configurados
        </div>
    </div>

    <!-- TABLA DE FORMATOS -->
    <div class="plantillas-panel">
        <table class="plantillas-table" id="tabla-formatos">
            <thead>
                <tr>
                    <th>Ensayo / Nombre del Formato</th>
                    <th>Código Oficial</th>
                    <th>Orientación</th>
                    <th>Norma / Método</th>
                    <th>Columnas</th>
                    <th>Ensayos Catálogo</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach ($plantillas as $plantilla): ?>
                <tr data-nombre="<?= strtolower($esc($plantilla['nombre'])) ?>" data-codigo="<?= strtolower($esc($plantilla['codigo_vigente'])) ?>" data-norma="<?= strtolower($esc($plantilla['norma'])) ?>">
                    <td>
                        <strong style="color:#0f172a; font-size:13.5px;"><?= $esc($plantilla['nombre']) ?></strong>
                        <div style="font-size:11.5px; color:#64748b; font-family:monospace; margin-top:2px;">
                            <?= $esc($plantilla['archivo_markdown']) ?> &bull; <?= $esc($plantilla['version_formato']) ?>
                        </div>
                    </td>
                    <td>
                        <span class="badge-code"><?= $esc($plantilla['codigo_vigente']) ?></span>
                    </td>
                    <td>
                        <span class="badge-orient <?= ($plantilla['orientacion'] ?? 'horizontal') === 'vertical' ? 'vertical' : 'horizontal' ?>">
                            <i class="fa-solid <?= ($plantilla['orientacion'] ?? 'horizontal') === 'vertical' ? 'fa-file' : 'fa-file-lines' ?>"></i>
                            <?= ucfirst($plantilla['orientacion'] ?? 'horizontal') ?>
                        </span>
                    </td>
                    <td>
                        <span style="font-size:12px; color:#334155; font-weight:600;"><?= $esc($plantilla['norma'] ?: 'N/A') ?></span>
                    </td>
                    <td>
                        <span style="font-size:12px; color:#475569; font-weight:600;"><?= (int)($plantilla['columnas_count'] ?? 0) ?> cols</span>
                    </td>
                    <td>
                        <span class="badge-products">
                            <i class="fa-solid fa-link"></i> <?= (int)($plantilla['total_productos'] ?? 0) ?> vinculados
                        </span>
                    </td>
                    <td style="text-align:right;">
                        <div class="row-btn-group">
                            <button type="button" class="row-btn primary" onclick="abrirPlantilla(<?= (int)$plantilla['id'] ?>)" title="Configurar formato y columnas">
                                <i class="fa-solid fa-pen-to-square"></i> Configurar
                            </button>
                            <button type="button" class="row-btn" onclick="duplicarFormato(<?= (int)$plantilla['id'] ?>, '<?= addslashes($esc($plantilla['nombre'])) ?>')" title="Crear copia editable">
                                <i class="fa-solid fa-clone"></i> Duplicar
                            </button>
                            <a href="/Cycsa/publico/configuracion/plantillas-ensayos/preview-pdf?id=<?= (int)$plantilla['id'] ?>" target="_blank" class="row-btn pdf" title="Ver informe de prueba">
                                <i class="fa-solid fa-file-pdf"></i> PDF
                            </a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL EDITOR / CREADOR DE FORMATO -->
<div id="plantilla-modal" class="plantilla-modal" role="dialog" aria-modal="true" aria-labelledby="plantilla-titulo">
    <div class="plantilla-dialog">
        <div class="plantilla-dialog-head">
            <h2 id="plantilla-titulo">
                <i class="fa-solid fa-file-pen"></i>
                <span>Configurar Formato</span>
            </h2>
            <button type="button" onclick="cerrarPlantilla()" aria-label="Cerrar">&times;</button>
        </div>
        
        <div class="plantilla-tabs">
            <button type="button" class="active" data-tab="documento"><i class="fa-solid fa-id-card"></i> Control Documental</button>
            <button type="button" data-tab="columnas"><i class="fa-solid fa-table-columns"></i> Columnas de Matriz</button>
            <button type="button" data-tab="formulas"><i class="fa-solid fa-calculator"></i> Fórmulas y cálculos</button>
            <button type="button" data-tab="notas"><i class="fa-solid fa-certificate"></i> Leyendas y Notas (ISO 17025)</button>
            <button type="button" data-tab="firmas"><i class="fa-solid fa-signature"></i> Firmas</button>
            <button type="button" data-tab="productos"><i class="fa-solid fa-link"></i> Ensayos Vinculados</button>
        </div>

        <div class="plantilla-body">
            
            <!-- 1. CONTROL DOCUMENTAL -->
            <div class="plantilla-pane active" data-pane="documento">
                <div class="plantilla-grid">
                    <label class="plantilla-field wide">
                        Nombre del Formato en Sistema
                        <input type="text" id="campo-nombre-formato" placeholder="Ej: CYCSA-PE-07 Resistencia de Concreto" maxlength="255">
                    </label>
                    <label class="plantilla-field">
                        Código Oficial de Encabezado (Casilla Superior Derecha)
                        <input data-key="codigo_formato" placeholder="Ej: CYCSA-RT-FM-22 A V1R2" maxlength="100">
                    </label>
                    <label class="plantilla-field">
                        Versión Documental
                        <input data-key="version_documento" placeholder="Ej: V1R2" maxlength="50">
                    </label>
                    <label class="plantilla-field">
                        Orientación del Informe Oficial
                        <select data-key="orientacion">
                            <option value="horizontal">Horizontal (Letter Landscape - 279 &times; 216 mm)</option>
                            <option value="vertical">Vertical (Letter Portrait - 216 &times; 279 mm)</option>
                        </select>
                    </label>
                    <label class="plantilla-field">
                        Título del Informe
                        <input data-key="titulo_informe" placeholder="INFORME DE ENSAYO" maxlength="120">
                    </label>
                    <label class="plantilla-field wide">
                        Subtítulo del Laboratorio
                        <input data-key="subtitulo_laboratorio" placeholder="Laboratorio de Ensayos y Control de Calidad" maxlength="180">
                    </label>
                    <label class="plantilla-field wide">
                        Título del Ensayo y Procedimientos Internos (Banner Oficial)
                        <textarea data-key="ensayo_titulo" placeholder="CYCSA-PE-07 Determinación de la Resistencia del Concreto..."></textarea>
                    </label>
                    <label class="plantilla-field">
                        Norma ASTM / AASHTO Oficial
                        <input data-key="norma" placeholder="Ej: DE-ASTM C39/C39M-21" maxlength="255">
                    </label>
                    <label class="plantilla-field">
                        Método de Muestreo Oficial
                        <input data-key="metodo_muestreo" placeholder="Ej: DE-ASTM C31/ASTM C172M-17" maxlength="255">
                    </label>
                    <label class="plantilla-field wide">
                        Tipo de Muestra por Defecto
                        <input data-key="tipo_muestra" placeholder="Ej: Cilindros de Concreto" maxlength="255">
                    </label>
                </div>
            </div>

            <!-- 2. COLUMNAS DE MATRIZ -->
            <div class="plantilla-pane" data-pane="columnas">
                <div style="background:#eff6ff; border:1px solid #bfdbfe; border-radius:8px; padding:12px 16px; margin-bottom:14px; font-size:12.5px; color:#1e40af; line-height:1.5;">
                    <i class="fa-solid fa-circle-info"></i>
                    <strong>Configuración de Columnas Técnicas:</strong> Reordena las columnas usando los botones <span style="font-weight:bold;">▲ / ▼</span>. El método ASTM/PE se imprimirá directamente en la fila de norma del reporte. Las columnas calculadas por fórmulas operan de forma automática durante la captura técnica.
                </div>
                <div id="plantilla-columnas" class="plantilla-list"></div>
                <button type="button" class="plantilla-add" onclick="agregarColumna()">
                    <i class="fa-solid fa-plus"></i> Agregar Nueva Columna
                </button>
            </div>

            <!-- 3. FÓRMULAS NORMATIVAS -->
            <div class="plantilla-pane" data-pane="formulas">
                <div class="formulas-wrap">
                    <div class="formulas-banner">
                        <div>
                            <h3><i class="fa-solid fa-square-root-variable"></i> Motor de Cálculos y Fórmulas Normativas</h3>
                            <p>Estas fórmulas de ingeniería operan automáticamente en tiempo real durante la captura técnica según las normas ASTM / AASHTO / CYCSA (ej. ASTM C39). Los resultados calculados se identifican con la etiqueta <span class="col-pill calc">Cálculo Automático</span>, se protegen contra errores manuales y se transfieren a la impresión y PDF oficial.</p>
                        </div>
                        <div id="formulas-conteo-pills" class="formulas-summary-bar"></div>
                    </div>
                    <div id="plantilla-formulas-lista" class="formulas-cards-grid"></div>
                </div>
            </div>

            <!-- 4. LEYENDAS Y NOTAS -->
            <div class="plantilla-pane" data-pane="notas">
                <label class="plantilla-field wide">
                    Descargo de Responsabilidad y Declaración de Imparcialidad (Pie Oficial ISO/IEC 17025)
                    <textarea data-key="disclaimer" style="min-height:90px;"></textarea>
                </label>
                <div style="margin-top:16px;">
                    <strong style="font-size:13px; color:#334155;">Notas Técnicas y Equipos Utilizados:</strong>
                    <p style="margin:4px 0 10px 0; font-size:12px; color:#64748b;">Cada nota se imprimirá numerada en la sección inferior del informe técnico oficial.</p>
                    <div id="plantilla-notas" class="plantilla-list"></div>
                    <button type="button" class="plantilla-add" onclick="agregarNota()">
                        <i class="fa-solid fa-plus"></i> Agregar Nota Técnica
                    </button>
                </div>
            </div>

            <!-- 5. FIRMAS -->
            <div class="plantilla-pane" data-pane="firmas">
                <div class="plantilla-grid">
                    <label class="plantilla-field">
                        Firmante Oficial Responsable
                        <input data-key="firmante_nombre" placeholder="Ing. Noel Quintana Lira" maxlength="150">
                    </label>
                    <label class="plantilla-field">
                        Cargo del Firmante
                        <input data-key="firmante_cargo" placeholder="Gerente General" maxlength="150">
                    </label>
                </div>
            </div>

            <!-- 6. PRODUCTOS / ENSAYOS VINCULADOS -->
            <div class="plantilla-pane" data-pane="productos">
                <p style="margin:0 0 10px 0; font-size:12.5px; color:#475569;">
                    Selecciona los ensayos del catálogo de productos que utilizan este formato para capturar y emitir sus matrices técnicas:
                </p>
                <div class="prods-picker-box" id="plantilla-productos-box">
                    <?php if (!empty($productos)): ?>
                        <?php foreach ($productos as $prod): ?>
                            <label class="prod-picker-item">
                                <input type="checkbox" class="prod-check" value="<?= (int)$prod['id'] ?>" data-actual-formato="<?= (int)($prod['formato_id'] ?? 0) ?>">
                                <div>
                                    <strong style="color:#0f172a;"><?= $esc($prod['no_item']) ?> - <?= $esc($prod['nombre_comercial']) ?></strong>
                                    <div style="font-size:11.5px; color:#64748b;"><?= $esc($prod['ensayo_servicio']) ?></div>
                                </div>
                            </label>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div style="color:#64748b; font-size:13px; text-align:center; padding:15px;">No hay productos en el catálogo.</div>
                    <?php endif; ?>
                </div>
            </div>

        </div>

        <div id="plantilla-status" class="plantilla-status" role="status"></div>

        <div class="plantilla-actions">
            <div style="display:flex; gap:10px; align-items:center;">
                <button type="button" class="btn-preview-pdf" id="btn-modal-preview" onclick="verVistaPreviaPDF()">
                    <i class="fa-solid fa-file-pdf" style="color:#dc2626;"></i> Vista Previa PDF
                </button>
                <button type="button" class="restablecer-plantilla" id="btn-modal-restablecer" onclick="restablecerPlantilla()">
                    <i class="fa-solid fa-rotate-left"></i> Restablecer de Fábrica
                </button>
            </div>
            <div style="display:flex; gap:10px; align-items:center;">
                <button type="button" class="row-btn" onclick="cerrarPlantilla()">Cancelar</button>
                <button type="button" class="guardar-plantilla" id="btn-guardar-principal" onclick="guardarPlantilla()">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Cambios
                </button>
            </div>
        </div>

    </div>
</div>

<script>
const plantillaBaseUrl = '/Cycsa/publico/configuracion/plantillas-ensayos';
const plantillaCsrf = <?= json_encode($_SESSION['csrf_token'] ?? '', JSON_UNESCAPED_UNICODE) ?>;
let plantillaId = null;
let modoCrear = false;
let plantillaConfig = null;
const modalPlantilla = document.getElementById('plantilla-modal');
const estadoPlantilla = document.getElementById('plantilla-status');
const campoPlantilla = key => modalPlantilla.querySelector('[data-key="' + key + '"]');

// Catálogo Normativo de Fórmulas y Reglas de Cálculo del Laboratorio
const CATALOGO_FORMULAS = [
    {
        id: 'compresion',
        nombre: 'Resistencia a la Compresión (Esfuerzo Unitario)',
        norma: 'ASTM C39 / ASTM C109 / ASTM C42 (CYCSA-PE-07)',
        inputs: ['Carga (lb)', 'Área (in²)'],
        inputsAlt: [['Carga (lb)', 'Carga (kg)'], ['Área (in²)', 'Área (cm²)']],
        outputs: ['R. Compresión (lb/in²)', 'R. Compresión (kg/cm²)'],
        outputsAlt: ['R. Compresión (kg/cm²)', 'R. Compresión. (kg/cm²)', 'R. compresión. (kg/cm²)', 'R. a la compresión (kg/cm²)', 'Estimación R. compresión (kg/cm²)', 'R. Compresión (lb/in²)'],
        ecuaciones: [
            'σ [psi] = Carga (lb) / Área (in²)',
            'R. Compresión (kg/cm²) = σ [psi] × 0.070307  (o directo kg / cm²)'
        ],
        descripcion: 'Calcula el esfuerzo de rotura axial en probetas y especímenes cilíndricos, cúbicos o prismáticos, convirtiendo a kg/cm² automáticamente.'
    },
    {
        id: 'flexion',
        nombre: 'Resistencia a la Flexión (Módulo de Ruptura MR)',
        norma: 'ASTM C78 / AASHTO T97 (CYCSA-PE-30)',
        inputs: ['Carga (lb)', 'Longitud de Apoyo (in)', 'Ancho Promedio (in)', 'Espesor Promedio (in)'],
        outputs: ['Resistencia a la flexión. (kg/cm²)'],
        ecuaciones: [
            'MR [psi] = ( Carga × Longitud Apoyo ) / ( Ancho × Espesor² )',
            'MR (kg/cm²) = MR [psi] × 0.070307'
        ],
        descripcion: 'Calcula el módulo de ruptura en viguetas de concreto con carga en el tercio medio de la luz.'
    },
    {
        id: 'compactacion',
        nombre: 'Grado de Compactación en Campo (%)',
        norma: 'ASTM D1556 / ASTM D6938 (CYCSA-PE-20 / PE-25)',
        inputs: ['P.V.S Max (kg/m³)', 'P.V.S.Sitio (kg/m³)'],
        inputsAlt: [['P.V.S Max (kg/m³)', '**P.V.S Max (kg/m³)'], ['P.V.S.Sitio (kg/m³)', 'P.V.S.Sitio (kg/cm²)']],
        outputs: ['Compactación ((%) (P/P))'],
        ecuaciones: [
            '% Compactación = ( P.V.S. Sitio / P.V.S. Máximo Proctor ) × 100'
        ],
        descripcion: 'Relación porcentual de compactación entre la densidad seca en sitio y la densidad seca máxima del ensayo Proctor.'
    },
    {
        id: 'granulometria',
        nombre: 'Curva Granulométrica y Tamizado en Cascada',
        norma: 'ASTM C136 / ASTM D422 / ASTM C117 (CYCSA-PE-16)',
        inputs: ['P. Retenido parcial (gr)'],
        outputs: ['% Retenido parcial', '% Acumulativo', '% que pasa la malla'],
        ecuaciones: [
            'Masa Total Σ = ∑ (P. Retenido parcial por malla)',
            '% Retenido Parcial = ( Wi / Masa Total ) × 100',
            '% Acumulativo = ∑ % Retenido Parcial acumulado',
            '% Que Pasa = 100 - % Acumulativo',
            'Control de Calidad: Pérdida por lavado > 0.30% activa RECHAZO AUTOMÁTICO'
        ],
        descripcion: 'Distribución porcentual de tamaños de partículas por tamices con cálculo acumulativo continuo y verificación normativa de lavado.'
    },
    {
        id: 'atterberg',
        nombre: 'Índice de Plasticidad (Límites de Consistencia)',
        norma: 'ASTM D4318 / AASHTO T89 / T90 (CYCSA-PE-17)',
        inputs: ['Límite Líquido', 'Límite Plástico'],
        outputs: ['I.P'],
        ecuaciones: [
            'IP = Límite Líquido (LL) - Límite Plástico (LP)'
        ],
        descripcion: 'Diferencia numérica entre el límite líquido y plástico para clasificación de suelos (SUCS / AASHTO).'
    },
    {
        id: 'revenimiento',
        nombre: 'Conversión Dimensional de Revenimiento (Slump)',
        norma: 'ASTM C143 / AASHTO T119 (CYCSA-PE-09)',
        inputs: ['Reven. (in)'],
        outputs: ['Reven. (cm)'],
        ecuaciones: [
            'Revenimiento (cm) = Revenimiento (in) × 2.54'
        ],
        descripcion: 'Convierte en tiempo real la medición de revenimiento o asentamiento de pulgadas a centímetros.'
    },
    {
        id: 'edades',
        nombre: 'Cálculo Cronológico de Edades de Especímenes',
        norma: 'ASTM C31 / ASTM C192',
        inputs: ['Fecha de Fabricación', 'Fecha de Ensayo'],
        inputsAlt: [['Fecha de Fabricación'], ['Fecha de Ensayo', 'Fecha de Ruptura', 'Fecha de Finalización']],
        outputs: ['Edad (Días)'],
        ecuaciones: [
            'Edad (Días) = Diferencia en días enteros entre (Fecha Ensayo / Ruptura - Fecha Fabricación)'
        ],
        descripcion: 'Cálculo automático de la edad de curado del espécimen a partir de las fechas registradas en campo y laboratorio.'
    }
];

function escapeHtml(str) {
    return String(str || '').replace(/[&<>"']/g, m => ({
        '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'
    })[m]);
}

function filtrarTablaFormatos() {
    const q = (document.getElementById('filtro-formatos')?.value || '').trim().toLowerCase();
    const filas = document.querySelectorAll('#tabla-formatos tbody tr');
    let visibles = 0;
    filas.forEach(f => {
        const txt = (f.dataset.nombre || '') + ' ' + (f.dataset.codigo || '') + ' ' + (f.dataset.norma || '');
        const coincide = q === '' || txt.includes(q);
        f.style.display = coincide ? '' : 'none';
        if (coincide) visibles++;
    });
    const cnt = document.getElementById('conteo-visibles');
    if (cnt) cnt.textContent = visibles;
}

function clasificarColumna(nombre) {
    const n = (nombre || '').trim().toLowerCase();
    if (!n) return { tipo: 'generic', label: 'Columna', title: 'Columna estándar' };

    const esCalculada = [
        'r. compresión', 'r. compresion', 'r. a la compresión', 'estimación r. compresión',
        'compactación', 'compactacion',
        'resistencia a la flexión', 'resistencia a la flexion',
        'reven. (cm)',
        'edad (días)', 'edad (dias)',
        '% retenido parcial', '% acumulativo', '% que pasa la malla', 'i.p'
    ].some(k => n.includes(k));

    if (esCalculada) {
        return { tipo: 'calc', label: 'Calculada', title: 'Resultado calculado automáticamente por fórmula' };
    }

    const esInput = [
        'carga (lb)', 'carga (kg)', 'área (in²)', 'area (in²)', 'área (cm²)', 'area (cm²)',
        'p.v.s max', 'p.v.s. max', 'p.v.s.sitio', 'p.v.s. sitio',
        'longitud de apoyo', 'ancho promedio', 'espesor promedio',
        'p. retenido parcial (gr)', 'reven. (in)',
        'fecha de fabricación', 'fecha de fabricacion', 'fecha de ensayo', 'fecha de ruptura',
        'límite líquido', 'limite liquido', 'límite plástico', 'limite plastico'
    ].some(k => n.includes(k));

    if (esInput) {
        return { tipo: 'input', label: 'Entrada', title: 'Variable técnica requerida para cálculos automáticos' };
    }

    return { tipo: 'generic', label: 'Dato', title: 'Campo de información o registro manual' };
}

function actualizarPillColumna(pill, nombre) {
    if (!pill) return;
    const info = clasificarColumna(nombre);
    pill.className = 'col-pill ' + info.tipo;
    pill.textContent = info.label;
    pill.title = info.title;
}

function mostrarTabPlantilla(tab) {
    modalPlantilla.querySelectorAll('[data-tab]').forEach(el => el.classList.toggle('active', el.dataset.tab === tab));
    modalPlantilla.querySelectorAll('[data-pane]').forEach(el => el.classList.toggle('active', el.dataset.pane === tab));
    if (tab === 'formulas') {
        actualizarVisualizacionFormulas();
    }
}
modalPlantilla.querySelectorAll('[data-tab]').forEach(el => el.addEventListener('click', () => mostrarTabPlantilla(el.dataset.tab)));

function moverFila(btn, dir) {
    const fila = btn.closest('.plantilla-row');
    const container = fila.parentElement;
    if (dir === 'arriba' && fila.previousElementSibling) {
        container.insertBefore(fila, fila.previousElementSibling);
    } else if (dir === 'abajo' && fila.nextElementSibling) {
        container.insertBefore(fila.nextElementSibling, fila);
    }
    actualizarVisualizacionFormulas();
}

function crearFilaColumna(container, nombre = '', metodo = '') {
    const fila = document.createElement('div');
    fila.className = 'plantilla-row';
    fila.dataset.originalName = nombre;

    const orderWrap = document.createElement('div');
    orderWrap.className = 'col-order-btns';
    orderWrap.innerHTML = `
        <button type="button" class="btn-order" onclick="moverFila(this, 'arriba')" title="Mover arriba">▲</button>
        <button type="button" class="btn-order" onclick="moverFila(this, 'abajo')" title="Mover abajo">▼</button>
    `;
    fila.appendChild(orderWrap);

    const pill = document.createElement('span');
    actualizarPillColumna(pill, nombre);
    fila.appendChild(pill);

    const inputNombre = document.createElement('input');
    inputNombre.type = 'text';
    inputNombre.value = nombre;
    inputNombre.placeholder = 'Nombre de columna (ej. Carga (lb))';
    inputNombre.maxLength = 120;
    inputNombre.addEventListener('input', () => {
        actualizarPillColumna(pill, inputNombre.value.trim());
        actualizarVisualizacionFormulas();
    });
    fila.appendChild(inputNombre);

    const inputMetodo = document.createElement('input');
    inputMetodo.type = 'text';
    inputMetodo.value = metodo;
    inputMetodo.placeholder = 'Método ASTM / PE (opcional)';
    inputMetodo.maxLength = 120;
    fila.appendChild(inputMetodo);

    const btnQuitar = document.createElement('button');
    btnQuitar.type = 'button';
    btnQuitar.className = 'btn-delete-row';
    btnQuitar.textContent = '✕';
    btnQuitar.title = 'Eliminar columna';
    btnQuitar.onclick = () => {
        fila.remove();
        actualizarVisualizacionFormulas();
    };
    fila.appendChild(btnQuitar);

    container.appendChild(fila);
}

function agregarColumna(nombre = '', metodo = '') {
    crearFilaColumna(document.getElementById('plantilla-columnas'), nombre, metodo);
    actualizarVisualizacionFormulas();
}

function agregarNota(nota = '') {
    const container = document.getElementById('plantilla-notas');
    const fila = document.createElement('div');
    fila.className = 'plantilla-row nota';

    const inputNota = document.createElement('input');
    inputNota.type = 'text';
    inputNota.value = nota;
    inputNota.placeholder = 'Nota técnica (ej. Nota 1: Muestra ensayada con equipo TROXLER 3440...)';
    inputNota.maxLength = 1000;
    fila.appendChild(inputNota);

    const btnQuitar = document.createElement('button');
    btnQuitar.type = 'button';
    btnQuitar.className = 'btn-delete-row';
    btnQuitar.textContent = '✕';
    btnQuitar.title = 'Eliminar nota';
    btnQuitar.onclick = () => fila.remove();
    fila.appendChild(btnQuitar);

    container.appendChild(fila);
}

function actualizarVisualizacionFormulas() {
    const listaContainer = document.getElementById('plantilla-formulas-lista');
    const conteoContainer = document.getElementById('formulas-conteo-pills');
    if (!listaContainer) return;

    const colInputs = document.querySelectorAll('#plantilla-columnas .plantilla-row input:first-of-type');
    const colsActuales = Array.from(colInputs).map(i => i.value.trim());
    const colsActualesLower = colsActuales.map(c => c.toLowerCase());
    const isGranulo = (plantillaConfig?.archivo_markdown || '').includes('granulo');

    let activasCount = 0;
    let inputsCount = 0;
    let outputsCount = 0;

    const cardsHtml = [];

    CATALOGO_FORMULAS.forEach(f => {
        let inputsPresentes = false;
        if (f.id === 'granulometria') {
            inputsPresentes = isGranulo || colsActualesLower.some(c => c.includes('retenido'));
        } else if (f.inputsAlt) {
            inputsPresentes = f.inputsAlt.every(grupo => grupo.some(col => colsActualesLower.includes(col.toLowerCase())));
        } else {
            inputsPresentes = f.inputs.every(col => colsActualesLower.includes(col.toLowerCase()));
        }

        let outputsPresentes = false;
        if (f.id === 'granulometria') {
            outputsPresentes = isGranulo || colsActualesLower.some(c => c.includes('%'));
        } else if (f.outputsAlt) {
            outputsPresentes = f.outputsAlt.some(col => colsActualesLower.includes(col.toLowerCase()));
        } else {
            outputsPresentes = f.outputs.some(col => colsActualesLower.includes(col.toLowerCase()));
        }

        let estado = 'inactiva';
        let badgeText = 'No aplica a este formato';
        if (inputsPresentes && outputsPresentes) {
            estado = 'activa';
            badgeText = 'Activa en este formato';
            activasCount++;
        } else if (inputsPresentes || outputsPresentes || (f.id === 'granulometria' && isGranulo)) {
            estado = 'activa';
            badgeText = 'Activa en este formato';
            activasCount++;
        }

        const equationsHtml = f.ecuaciones.map(eq => `<code>${escapeHtml(eq)}</code>`).join('');
        const inputsPills = f.inputs.map(inp => `<span class="formula-pill-var">${escapeHtml(inp)}</span>`).join(' + ');
        const outputsPills = f.outputs.map(out => `<span class="formula-pill-res">${escapeHtml(out)}</span>`).join(', ');

        cardsHtml.push(`
            <div class="formula-card ${estado}">
                <div class="formula-card-head">
                    <h4 class="formula-card-title">
                        ${escapeHtml(f.nombre)}
                        <span class="formula-badge-norma">${escapeHtml(f.norma)}</span>
                    </h4>
                    <span class="formula-badge-status ${estado}">${badgeText}</span>
                </div>
                <p class="formula-desc">${escapeHtml(f.descripcion)}</p>
                <div class="math-equation-box">
                    ${equationsHtml}
                </div>
                <div class="formula-flow">
                    <span class="formula-flow-label">Variables de Entrada:</span>
                    ${inputsPills}
                    <span class="formula-arrow">&rarr;</span>
                    <span class="formula-flow-label">Resultado Automático:</span>
                    ${outputsPills}
                </div>
            </div>
        `);
    });

    colsActuales.forEach(c => {
        const cls = clasificarColumna(c).tipo;
        if (cls === 'calc') outputsCount++;
        if (cls === 'input') inputsCount++;
    });

    if (conteoContainer) {
        conteoContainer.innerHTML = `
            <div class="formula-stat-pill activa">${activasCount} fórmulas activas</div>
            <div class="formula-stat-pill">${inputsCount} variables de entrada</div>
            <div class="formula-stat-pill">${outputsCount} columnas calculadas</div>
        `;
    }

    listaContainer.innerHTML = cardsHtml.join('');
}

async function abrirPlantilla(id) {
    modoCrear = false;
    plantillaId = id;
    estadoPlantilla.textContent = '';
    document.getElementById('btn-modal-restablecer').style.display = 'inline-block';
    document.getElementById('btn-modal-preview').style.display = 'inline-flex';
    document.getElementById('btn-guardar-principal').innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Cambios en Formato';
    
    try {
        const response = await fetch(plantillaBaseUrl + '/obtener-ajax?id=' + encodeURIComponent(id), {credentials: 'same-origin'});
        const data = await response.json();
        if (!response.ok || !data.success) { alert(data.error || 'No se pudo cargar el formato.'); return; }
        
        plantillaConfig = data.plantilla.configuracion || {};
        plantillaConfig.archivo_markdown = data.plantilla.archivo_markdown;
        
        document.getElementById('plantilla-titulo').innerHTML = `<i class="fa-solid fa-pen-to-square"></i> <span>Configurar Formato: ${escapeHtml(data.plantilla.nombre)}</span>`;
        document.getElementById('campo-nombre-formato').value = data.plantilla.nombre || '';
        
        modalPlantilla.querySelectorAll('[data-key]').forEach(el => {
            el.value = plantillaConfig[el.dataset.key] || '';
        });

        const columnas = document.getElementById('plantilla-columnas');
        columnas.replaceChildren();
        (plantillaConfig.columns || []).forEach(nombre => {
            crearFilaColumna(columnas, nombre, (plantillaConfig.column_methods || {})[nombre] || '');
        });

        const notas = document.getElementById('plantilla-notas');
        notas.replaceChildren();
        (plantillaConfig.notas || []).forEach(nota => agregarNota(nota));

        // Marcar productos asociados
        const checks = document.querySelectorAll('.prod-check');
        checks.forEach(chk => {
            chk.checked = (parseInt(chk.dataset.actualFormato, 10) === id);
        });

        actualizarVisualizacionFormulas();
        mostrarTabPlantilla('documento');
        modalPlantilla.classList.add('open');
    } catch (e) {
        alert('Error al comunicar con el servidor.');
    }
}

function abrirModalCrear() {
    modoCrear = true;
    plantillaId = null;
    estadoPlantilla.textContent = '';
    document.getElementById('btn-modal-restablecer').style.display = 'none';
    document.getElementById('btn-modal-preview').style.display = 'none';
    document.getElementById('btn-guardar-principal').innerHTML = '<i class="fa-solid fa-plus"></i> Crear Nuevo Formato';
    document.getElementById('plantilla-titulo').innerHTML = `<i class="fa-solid fa-plus-circle"></i> <span>Crear Nuevo Formato de Ensayo</span>`;
    
    document.getElementById('campo-nombre-formato').value = '';
    
    plantillaConfig = {
        codigo_formato: 'CYCSA-RT-FM-22 A V1R1',
        version_documento: 'V1R1',
        titulo_informe: 'INFORME DE ENSAYO',
        subtitulo_laboratorio: 'Laboratorio de Ensayos y Control de Calidad',
        ensayo_titulo: '',
        norma: 'ASTM',
        metodo_muestreo: 'DE-ASTM',
        tipo_muestra: 'Muestra Estándar',
        orientacion: 'horizontal',
        disclaimer: 'Consultoría y Construcción SA.CYCSA es responsable únicamente de la exactitud de los resultados realizados en las muestras recibidas y tomadas en campo. No se debe de reproducir este informe de ensayo sin la aprobación formal de Consultoría y Construcción SA. CYCSA.  ** Información Proporcionada por el cliente.',
        firmante_nombre: 'Ing. Noel Quintana Lira',
        firmante_cargo: 'Gerente General',
        columns: ['Código laboratorio', 'Nombre muestra', 'Resultado', 'Observaciones'],
        column_methods: {},
        notas: ['Nota: Ensayo realizado en las instalaciones fijas de Consultoría y Construcción SA. CYCSA.']
    };

    modalPlantilla.querySelectorAll('[data-key]').forEach(el => {
        el.value = plantillaConfig[el.dataset.key] || '';
    });

    const columnas = document.getElementById('plantilla-columnas');
    columnas.replaceChildren();
    plantillaConfig.columns.forEach(c => crearFilaColumna(columnas, c, ''));

    const notas = document.getElementById('plantilla-notas');
    notas.replaceChildren();
    plantillaConfig.notas.forEach(n => agregarNota(n));

    document.querySelectorAll('.prod-check').forEach(chk => { chk.checked = false; });

    actualizarVisualizacionFormulas();
    mostrarTabPlantilla('documento');
    modalPlantilla.classList.add('open');
}

function cerrarPlantilla() { modalPlantilla.classList.remove('open'); }
modalPlantilla.addEventListener('click', e => { if (e.target === modalPlantilla) cerrarPlantilla(); });
document.addEventListener('keydown', e => { if (e.key === 'Escape') cerrarPlantilla(); });

function recogerPlantilla() {
    const config = {...(plantillaConfig || {})};
    modalPlantilla.querySelectorAll('[data-key]').forEach(el => { config[el.dataset.key] = el.value.trim(); });
    
    config.columns = [];
    config.column_methods = {};
    config.column_aliases = {};
    document.querySelectorAll('#plantilla-columnas .plantilla-row').forEach(fila => {
        const inputs = fila.querySelectorAll('input');
        if (inputs.length >= 2) {
            const clave = inputs[0].value.trim();
            const metodo = inputs[1].value.trim();
            if (clave !== '') {
                config.columns.push(clave);
                if (metodo !== '') config.column_methods[clave] = metodo;
                const original = fila.dataset.originalName;
                if (original && original !== clave) {
                    config.column_aliases[clave] = (plantillaConfig.column_aliases || {})[original] || original;
                }
            }
        }
    });

    config.notas = [...document.querySelectorAll('#plantilla-notas .plantilla-row input')].map(el => el.value.trim()).filter(Boolean);
    return config;
}

function recogerProductosSeleccionados() {
    return Array.from(document.querySelectorAll('.prod-check:checked')).map(c => c.value);
}

async function guardarPlantilla() {
    const nombre = document.getElementById('campo-nombre-formato').value.trim();
    if (!nombre) {
        alert('Por favor ingresa un nombre para el formato de ensayo.');
        mostrarTabPlantilla('documento');
        document.getElementById('campo-nombre-formato').focus();
        return;
    }

    const config = recogerPlantilla();
    if (config.columns.length === 0) {
        alert('El formato debe contener al menos una columna de datos.');
        mostrarTabPlantilla('columnas');
        return;
    }

    const prods = recogerProductosSeleccionados();
    const endpoint = modoCrear ? '/crear' : '/guardar';

    estadoPlantilla.textContent = 'Guardando formato...';
    try {
        const params = new URLSearchParams({
            csrf_token: plantillaCsrf,
            nombre: nombre,
            configuracion_json: JSON.stringify(config)
        });
        if (!modoCrear && plantillaId) {
            params.append('id', plantillaId);
        }
        prods.forEach(pId => params.append('productos_ids[]', pId));

        const response = await fetch(plantillaBaseUrl + endpoint, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: params
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            estadoPlantilla.textContent = data.error || 'No se pudo guardar el formato.';
            return;
        }
        location.reload();
    } catch (e) {
        estadoPlantilla.textContent = 'Error de comunicación con el servidor.';
    }
}

async function duplicarFormato(id, nombre) {
    if (!confirm(`¿Deseas crear una copia editable del formato "${nombre}"?`)) return;
    try {
        const response = await fetch(plantillaBaseUrl + '/duplicar', {
            method: 'POST',
            credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({id: id, csrf_token: plantillaCsrf})
        });
        const data = await response.json();
        if (!response.ok || !data.success) {
            alert(data.error || 'No se pudo duplicar el formato.');
            return;
        }
        location.reload();
    } catch (e) {
        alert('Error al duplicar el formato.');
    }
}

function verVistaPreviaPDF() {
    if (!plantillaId) {
        alert('Debes guardar el formato primero para generar la vista previa PDF.');
        return;
    }
    window.open(plantillaBaseUrl + '/preview-pdf?id=' + encodeURIComponent(plantillaId), '_blank');
}

function restablecerPlantilla() {
    if (!plantillaId) return;
    if (confirm('¿Restablecer este formato al esquema oficial de fábrica? Se perderán las modificaciones manuales.')) {
        fetch(plantillaBaseUrl + '/restablecer', {
            method: 'POST', credentials: 'same-origin',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: new URLSearchParams({id: plantillaId, csrf_token: plantillaCsrf})
        }).then(r => r.json()).then(data => {
            if (data.success) location.reload();
            else alert(data.error || 'Error al restablecer.');
        }).catch(() => alert('Error de red.'));
    }
}
</script>
