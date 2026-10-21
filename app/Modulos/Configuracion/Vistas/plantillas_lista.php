<?php
$limpiarUtf8 = static function(?string $texto): string {
    if ($texto === null || $texto === '') return '';
    if (strpos($texto, 'Ã') !== false || strpos($texto, 'Â') !== false) {
        $reparado = @mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
        if ($reparado !== false && mb_check_encoding($reparado, 'UTF-8')) {
            $texto = $reparado;
        }
    }
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
};

$totalAprobadas = 0;
$totalRevision = 0;
$totalVariantes = 0;
if (!empty($plantillas)) {
    foreach ($plantillas as $p) {
        if ($p['estado'] === 'aprobada') $totalAprobadas++;
        if ($p['estado'] === 'en_revision' || $p['estado'] === 'borrador') $totalRevision++;
        if (!empty($p['cliente_id'])) $totalVariantes++;
    }
}
?>

<style>
    .plantillas-container {
        max-width: 1400px;
        margin: 0 auto;
        padding: 10px 5px 30px 5px;
        color: #1e293b;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
    }

    /* Encabezado */
    .head-banner {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 15px;
        margin-bottom: 20px;
        padding-bottom: 16px;
        border-bottom: 1.5px solid #e2e8f0;
    }
    .head-title-wrap h1 {
        font-family: 'Outfit', sans-serif;
        font-size: 23px;
        font-weight: 700;
        color: #103487;
        margin: 0 0 4px 0;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .head-title-wrap p {
        margin: 0;
        font-size: 13px;
        color: #64748b;
    }
    .head-actions {
        display: flex;
        gap: 10px;
        align-items: center;
    }

    /* Botones Corporativos CYCSA */
    .btn-cycsa {
        display: inline-flex;
        align-items: center;
        gap: 8px;
        padding: 9px 16px;
        border-radius: 6px;
        font-size: 13px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.18s ease;
        line-height: 1.2;
    }
    .btn-cycsa-primary {
        background-color: #103487;
        color: #ffffff;
        border-color: #103487;
    }
    .btn-cycsa-primary:hover {
        background-color: #0c2766;
        border-color: #0c2766;
        color: #ffffff;
        box-shadow: 0 4px 12px rgba(16, 52, 135, 0.2);
    }
    .btn-cycsa-secondary {
        background-color: #ffffff;
        color: #475569;
        border-color: #cbd5e1;
    }
    .btn-cycsa-secondary:hover {
        background-color: #f8fafc;
        color: #1e293b;
        border-color: #94a3b8;
    }

    /* Tarjetas KPI Resumen */
    .kpi-bar {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 15px;
        margin-bottom: 22px;
    }
    .kpi-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px 18px;
        display: flex;
        align-items: center;
        gap: 14px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .kpi-icon {
        width: 44px;
        height: 44px;
        border-radius: 8px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 18px;
        flex-shrink: 0;
    }
    .kpi-info h3 {
        margin: 0;
        font-size: 21px;
        font-weight: 800;
        font-family: 'Outfit', sans-serif;
        color: #0f172a;
        line-height: 1.1;
    }
    .kpi-info span {
        font-size: 11.5px;
        color: #64748b;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }

    /* Barra de Búsqueda y Filtros */
    .filter-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 16px 20px;
        margin-bottom: 22px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .filter-grid {
        display: grid;
        grid-template-columns: 2.2fr 2fr 1.3fr 1.5fr auto;
        gap: 12px;
        align-items: end;
    }
    @media (max-width: 1024px) {
        .filter-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    @media (max-width: 640px) {
        .filter-grid {
            grid-template-columns: 1fr;
        }
    }
    .filter-group label {
        display: block;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        color: #475569;
        margin-bottom: 5px;
        letter-spacing: 0.3px;
    }
    .filter-input, .filter-select {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 13px;
        color: #1e293b;
        background: #ffffff;
        outline: none;
        box-sizing: border-box;
        transition: border-color 0.15s, box-shadow 0.15s;
    }
    .filter-input:focus, .filter-select:focus {
        border-color: #103487;
        box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08);
    }

    /* Tabla Corporativa de Plantillas */
    .table-card {
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        overflow: hidden;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .table-responsive {
        width: 100%;
        overflow-x: auto;
    }
    .tabla-cycsa {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
        text-align: left;
    }
    .tabla-cycsa thead th {
        background: #f8fafc;
        color: #475569;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.6px;
        padding: 13px 16px;
        border-bottom: 2px solid #e2e8f0;
        white-space: nowrap;
    }
    .tabla-cycsa tbody td {
        padding: 13px 16px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: #1e293b;
    }
    .tabla-cycsa tbody tr:hover {
        background-color: #f8fafc;
    }

    /* Badges de Estado y Formato */
    .pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        line-height: 1.2;
    }
    .pill-aprobada {
        background: #ecfdf5;
        color: #065f46;
        border: 1px solid #a7f3d0;
    }
    .pill-revision {
        background: #fffbeb;
        color: #92400e;
        border: 1px solid #fde68a;
    }
    .pill-borrador {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
    }
    .pill-obsoleta {
        background: #fef2f2;
        color: #991b1b;
        border: 1px solid #fecaca;
    }
    .pill-variante {
        background: #f5f3ff;
        color: #5b21b6;
        border: 1px solid #ddd6fe;
    }
    .pill-general {
        background: #f0f9ff;
        color: #0369a1;
        border: 1px solid #bae6fd;
    }
    .pill-formato {
        background: #f8fafc;
        color: #334155;
        border: 1px solid #e2e8f0;
    }
    .pill-ona {
        background: #fff7ed;
        color: #c2410c;
        border: 1px solid #ffedd5;
    }

    /* Botones de Acción en Fila */
    .action-group {
        display: inline-flex;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        overflow: hidden;
    }
    .action-btn {
        background: #ffffff;
        border: none;
        border-right: 1px solid #cbd5e1;
        padding: 7px 11px;
        color: #475569;
        cursor: pointer;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        font-size: 12.5px;
        transition: all 0.15s;
    }
    .action-btn:last-child {
        border-right: none;
    }
    .action-btn:hover {
        background: #f1f5f9;
        color: #103487;
    }
    .action-btn.edit:hover {
        background: #e0f2fe;
        color: #0369a1;
    }
    .action-btn.clone:hover {
        background: #f5f3ff;
        color: #6d28d9;
    }

    /* Modal CYCSA Puro (Sin dependencias externas) */
    .cycsa-modal-backdrop {
        display: none;
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.65);
        backdrop-filter: blur(3px);
        z-index: 9999;
        justify-content: center;
        align-items: center;
        padding: 20px;
    }
    .cycsa-modal-dialog {
        background: #ffffff;
        border-radius: 8px;
        width: 100%;
        max-width: 550px;
        box-shadow: 0 20px 40px rgba(0,0,0,0.25);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        animation: modalAppear 0.2s ease-out;
    }
    .cycsa-modal-dialog.modal-xl {
        max-width: 950px;
        max-height: 90vh;
    }
    @keyframes modalAppear {
        from { opacity: 0; transform: translateY(-10px) scale(0.98); }
        to { opacity: 1; transform: translateY(0) scale(1); }
    }
    .cycsa-modal-header {
        background: #103487;
        color: #ffffff;
        padding: 14px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .cycsa-modal-header h4 {
        margin: 0;
        font-size: 15px;
        font-weight: 700;
        font-family: 'Outfit', sans-serif;
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .cycsa-modal-close {
        background: transparent;
        border: none;
        color: #ffffff;
        font-size: 20px;
        cursor: pointer;
        line-height: 1;
        opacity: 0.8;
    }
    .cycsa-modal-close:hover {
        opacity: 1;
    }
    .cycsa-modal-body {
        padding: 20px;
        overflow-y: auto;
    }
    .cycsa-modal-footer {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 12px 20px;
        display: flex;
        justify-content: flex-end;
        gap: 10px;
    }
</style>

<div class="plantillas-container">
    <!-- ENCABEZADO PRINCIPAL -->
    <div class="head-banner">
        <div class="head-title-wrap">
            <h1>
                <i class="fa-solid fa-shapes"></i>
                Catálogo de Plantillas de Ensayo (ISO/IEC 17025)
            </h1>
            <p>Formatos técnicos de laboratorio, control de versiones inmutables y variantes personalizadas por cliente.</p>
        </div>
        <div class="head-actions">
            <a href="/Cycsa/publico/configuracion" class="btn-cycsa btn-cycsa-secondary">
                <i class="fa-solid fa-sliders"></i> Configuración
            </a>
            <a href="/Cycsa/publico/plantillas/estudio" class="btn-cycsa btn-cycsa-primary">
                <i class="fa-solid fa-plus"></i> Diseñar Nueva Plantilla (Studio)
            </a>
        </div>
    </div>

    <!-- TARJETAS DE RESUMEN KPI -->
    <div class="kpi-bar">
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#eff6ff; color:#1d4ed8;">
                <i class="fa-solid fa-layer-group"></i>
            </div>
            <div class="kpi-info">
                <h3><?= count($plantillas ?? []) ?></h3>
                <span>Formatos Registrados</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#ecfdf5; color:#047857;">
                <i class="fa-solid fa-shield-check"></i>
            </div>
            <div class="kpi-info">
                <h3><?= $totalAprobadas ?></h3>
                <span>Aprobadas Oficiales</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#fffbeb; color:#b45309;">
                <i class="fa-solid fa-pen-to-square"></i>
            </div>
            <div class="kpi-info">
                <h3><?= $totalRevision ?></h3>
                <span>En Revisión / Borradores</span>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background:#f5f3ff; color:#6d28d9;">
                <i class="fa-solid fa-user-tag"></i>
            </div>
            <div class="kpi-info">
                <h3><?= $totalVariantes ?></h3>
                <span>Variantes por Cliente</span>
            </div>
        </div>
    </div>

    <!-- BARRA DE BÚSQUEDA Y FILTRADO -->
    <div class="filter-card">
        <form method="GET" action="/Cycsa/publico/plantillas" class="filter-grid">
            <div class="filter-group">
                <label><i class="fa-solid fa-magnifying-glass"></i> Búsqueda por Texto</label>
                <input type="text" name="buscar" class="filter-input" placeholder="Buscar por código, nombre o norma ASTM..." value="<?= $limpiarUtf8($filtros['buscar'] ?? '') ?>">
            </div>
            <div class="filter-group">
                <label><i class="fa-solid fa-flask"></i> Ensayo del Catálogo</label>
                <select name="ensayo_id" class="filter-select">
                    <option value="">-- Todos los Ensayos --</option>
                    <?php foreach ($ensayos as $ens): ?>
                        <option value="<?= (int)$ens['id'] ?>" <?= (!empty($filtros['ensayo_id']) && (int)$filtros['ensayo_id'] === (int)$ens['id']) ? 'selected' : '' ?>>
                            [<?= $limpiarUtf8($ens['no_item']) ?>] <?= $limpiarUtf8($ens['ensayo_servicio']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fa-solid fa-tag"></i> Estado</label>
                <select name="estado" class="filter-select">
                    <option value="">-- Todos los Estados --</option>
                    <option value="aprobada" <?= ($filtros['estado'] ?? '') === 'aprobada' ? 'selected' : '' ?>>Aprobada</option>
                    <option value="en_revision" <?= ($filtros['estado'] ?? '') === 'en_revision' ? 'selected' : '' ?>>En Revisión</option>
                    <option value="borrador" <?= ($filtros['estado'] ?? '') === 'borrador' ? 'selected' : '' ?>>Borrador</option>
                    <option value="obsoleta" <?= ($filtros['estado'] ?? '') === 'obsoleta' ? 'selected' : '' ?>>Obsoleta</option>
                </select>
            </div>
            <div class="filter-group">
                <label><i class="fa-solid fa-building"></i> Ámbito / Cliente</label>
                <select name="cliente_id" class="filter-select">
                    <option value="">-- Generales y Variantes --</option>
                    <option value="general" <?= ($filtros['cliente_id'] ?? '') === 'general' ? 'selected' : '' ?>>Solo Generales CYCSA</option>
                    <?php foreach ($clientes as $cli): ?>
                        <option value="<?= (int)$cli['id'] ?>" <?= (!empty($filtros['cliente_id']) && (int)$filtros['cliente_id'] === (int)$cli['id']) ? 'selected' : '' ?>>
                            Cliente: <?= $limpiarUtf8($cli['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="filter-group" style="display:flex; gap:8px;">
                <button type="submit" class="btn-cycsa btn-cycsa-primary" style="padding: 8px 14px;">
                    <i class="fa-solid fa-filter"></i> Filtrar
                </button>
                <a href="/Cycsa/publico/plantillas" class="btn-cycsa btn-cycsa-secondary" style="padding: 8px 12px;" title="Restablecer filtros">
                    <i class="fa-solid fa-rotate-left"></i>
                </a>
            </div>
        </form>
    </div>

    <!-- TABLA CORPORATIVA DE PLANTILLAS -->
    <div class="table-card">
        <div class="table-responsive">
            <table class="tabla-cycsa">
                <thead>
                    <tr>
                        <th style="width: 14%;">Código Formato</th>
                        <th style="width: 25%;">Nombre de Plantilla y Norma</th>
                        <th style="width: 20%;">Ensayo Base (Catálogo)</th>
                        <th style="width: 13%;">Ámbito / Cliente</th>
                        <th style="width: 11%; text-align:center;">Versión / Estado</th>
                        <th style="width: 9%; text-align:center;">Formato</th>
                        <th style="width: 8%; text-align:center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($plantillas)): ?>
                        <tr>
                            <td colspan="7" style="text-align:center; padding: 45px 20px; color: #64748b;">
                                <i class="fa-solid fa-folder-open" style="font-size: 32px; color: #94a3b8; display: block; margin-bottom: 10px;"></i>
                                No se encontraron plantillas que coincidan con los criterios de búsqueda.
                            </td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($plantillas as $plt): ?>
                            <tr>
                                <td>
                                    <strong style="font-family: 'Consolas', monospace; color: #103487; font-size: 12.5px;">
                                        <?= $limpiarUtf8($plt['codigo']) ?>
                                    </strong>
                                    <?php if (!empty($plt['es_por_defecto'])): ?>
                                        <div style="margin-top: 3px;">
                                            <span class="pill pill-general" style="font-size: 9.5px; padding: 1px 6px;">Por Defecto</span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div style="font-weight: 700; color: #0f172a; margin-bottom: 3px;">
                                        <?= $limpiarUtf8($plt['nombre']) ?>
                                    </div>
                                    <div style="font-size: 11.5px; color: #64748b;">
                                        <i class="fa-solid fa-book-bookmark" style="color: #94a3b8; margin-right: 4px;"></i>
                                        <?= $limpiarUtf8($plt['norma']) ?>
                                        <?php if (!empty($plt['procedimiento_interno'])): ?>
                                            <span style="margin-left: 6px; background: #f1f5f9; padding: 1px 6px; border-radius: 4px; font-size: 10px; border: 1px solid #e2e8f0;">
                                                <?= $limpiarUtf8($plt['procedimiento_interno']) ?>
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <span style="background: #f1f5f9; border: 1px solid #cbd5e1; border-radius: 4px; padding: 1px 6px; font-size: 10.5px; font-weight: 700; color: #475569;">
                                        [<?= $limpiarUtf8($plt['ensayo_item'] ?: '#') ?>]
                                    </span>
                                    <span style="font-size: 12px; color: #334155; margin-left: 4px;">
                                        <?= $limpiarUtf8($plt['ensayo_servicio'] ?: 'General') ?>
                                    </span>
                                </td>
                                <td>
                                    <?php if (!empty($plt['cliente_id'])): ?>
                                        <span class="pill pill-variante">
                                            <i class="fa-solid fa-user-tag"></i> <?= $limpiarUtf8($plt['cliente_nombre'] ?: 'Cliente') ?>
                                        </span>
                                        <?php if (!empty($plt['plantilla_padre_nombre'])): ?>
                                            <div style="font-size: 10px; color: #64748b; margin-top: 3px;">
                                                Base: <?= $limpiarUtf8($plt['plantilla_padre_nombre']) ?>
                                            </div>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <span class="pill pill-general">
                                            <i class="fa-solid fa-building-columns"></i> General CYCSA
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <strong style="font-family: 'Consolas', monospace; font-size: 12px; color: #1e293b;">
                                        <?= $limpiarUtf8($plt['version_actual']) ?>
                                    </strong>
                                    <div style="margin-top: 4px;">
                                        <?php if ($plt['estado'] === 'aprobada'): ?>
                                            <span class="pill pill-aprobada"><i class="fa-solid fa-check"></i> Aprobada</span>
                                        <?php elseif ($plt['estado'] === 'en_revision'): ?>
                                            <span class="pill pill-revision"><i class="fa-solid fa-clock"></i> En Revisión</span>
                                        <?php elseif ($plt['estado'] === 'borrador'): ?>
                                            <span class="pill pill-borrador"><i class="fa-solid fa-pencil"></i> Borrador</span>
                                        <?php else: ?>
                                            <span class="pill pill-obsoleta"><i class="fa-solid fa-ban"></i> Obsoleta</span>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td style="text-align: center;">
                                    <span class="pill pill-formato" title="Orientación de hoja oficial">
                                        <i class="fa-solid fa-file-<?= ($plt['orientacion'] === 'vertical') ? 'lines' : 'invoice' ?>"></i>
                                        <?= ucfirst($plt['orientacion']) ?>
                                    </span>
                                    <?php if (!empty($plt['incluir_sello_acreditacion'])): ?>
                                        <div style="margin-top: 4px;">
                                            <span class="pill pill-ona" title="Incluye Sello Oficial de Acreditación">
                                                <i class="fa-solid fa-certificate"></i> ONA
                                            </span>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center;">
                                    <div class="action-group">
                                        <button type="button" class="action-btn" onclick="verPreviewPlantilla(<?= (int)$plt['id'] ?>)" title="Ver Formato / Informe Oficial">
                                            <i class="fa-solid fa-eye"></i>
                                        </button>
                                        <a href="/Cycsa/publico/plantillas/estudio?id=<?= (int)$plt['id'] ?>" class="action-btn edit" title="Editar en Single-Screen Studio">
                                            <i class="fa-solid fa-pen-ruler"></i>
                                        </a>
                                        <button type="button" class="action-btn clone" onclick="abrirModalClonar(<?= (int)$plt['id'] ?>, '<?= addslashes($plt['nombre']) ?>')" title="Duplicar como variante para cliente">
                                            <i class="fa-solid fa-clone"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- MODAL DUPLICAR COMO VARIANTE -->
<div class="cycsa-modal-backdrop" id="modalDuplicarVariante">
    <div class="cycsa-modal-dialog">
        <div class="cycsa-modal-header">
            <h4><i class="fa-solid fa-clone"></i> Duplicar Plantilla como Variante</h4>
            <button type="button" class="cycsa-modal-close" onclick="cerrarModal('modalDuplicarVariante')">&times;</button>
        </div>
        <div class="cycsa-modal-body">
            <input type="hidden" id="clonar_plantilla_id" value="">
            <div style="margin-bottom: 14px;">
                <label style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:5px;">PLANTILLA BASE ORIGINAL:</label>
                <input type="text" id="clonar_nombre_original" class="filter-input" style="background:#f8fafc; font-weight:600;" readonly>
            </div>
            <div style="margin-bottom: 14px;">
                <label style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:5px;">CLIENTE DESTINATARIO (OBLIGATORIO):</label>
                <select id="clonar_cliente_id" class="filter-select" required>
                    <option value="">-- Seleccionar Cliente --</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= (int)$c['id'] ?>"><?= $limpiarUtf8($c['nombre']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div style="margin-bottom: 10px;">
                <label style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:5px;">NOMBRE DE LA VARIANTE (OPCIONAL):</label>
                <input type="text" id="clonar_nuevo_nombre" class="filter-input" placeholder="Ej: Granulometría - Consorcio Deportivo">
                <span style="font-size: 11px; color: #64748b; display: block; margin-top: 4px;">Si se deja vacío, el sistema le agregará el nombre del cliente automáticamente.</span>
            </div>
        </div>
        <div class="cycsa-modal-footer">
            <button type="button" class="btn-cycsa btn-cycsa-secondary" onclick="cerrarModal('modalDuplicarVariante')">Cancelar</button>
            <button type="button" class="btn-cycsa btn-cycsa-primary" onclick="confirmarClonacion()">
                <i class="fa-solid fa-copy"></i> Crear Variante en Studio
            </button>
        </div>
    </div>
</div>

<!-- MODAL PREVIEW FLOTANTE -->
<div class="cycsa-modal-backdrop" id="modalPreviewPlantilla">
    <div class="cycsa-modal-dialog modal-xl">
        <div class="cycsa-modal-header" style="background:#0f172a;">
            <h4><i class="fa-solid fa-file-contract"></i> Vista Previa de Formato de Ensayo e Informe Oficial</h4>
            <button type="button" class="cycsa-modal-close" onclick="cerrarModal('modalPreviewPlantilla')">&times;</button>
        </div>
        <div class="cycsa-modal-body" id="contenedorPreviewModal" style="padding: 0; min-height: 400px; background: #f8fafc;">
            <div style="text-align:center; padding: 60px 20px; color: #64748b;">
                <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 28px; color: #103487;"></i>
                <div style="margin-top: 12px; font-size: 13px;">Cargando estructura y membrete oficial...</div>
            </div>
        </div>
        <div class="cycsa-modal-footer">
            <button type="button" class="btn-cycsa btn-cycsa-secondary" onclick="cerrarModal('modalPreviewPlantilla')">Cerrar</button>
        </div>
    </div>
</div>

<script>
function abrirModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'flex';
}

function cerrarModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

function abrirModalClonar(id, nombre) {
    document.getElementById('clonar_plantilla_id').value = id;
    document.getElementById('clonar_nombre_original').value = nombre;
    document.getElementById('clonar_nuevo_nombre').value = '';
    abrirModal('modalDuplicarVariante');
}

function confirmarClonacion() {
    const id = document.getElementById('clonar_plantilla_id').value;
    const cid = document.getElementById('clonar_cliente_id').value;
    const nombre = document.getElementById('clonar_nuevo_nombre').value;

    if (!cid) {
        alert('Por favor seleccione el cliente destinatario.');
        return;
    }

    const csrf = '<?= $_SESSION['csrf_token'] ?? '' ?>';
    const fd = new FormData();
    fd.append('plantilla_id', id);
    fd.append('cliente_id', cid);
    fd.append('nombre_variante', nombre);
    fd.append('csrf_token', csrf);

    fetch('/Cycsa/publico/plantillas/duplicar-variante', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            window.location.href = data.redirect_url;
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(err => {
        alert('Error en la comunicación con el servidor.');
    });
}

function verPreviewPlantilla(id, vid) {
    const contenedor = document.getElementById('contenedorPreviewModal');
    contenedor.innerHTML = `
        <div style="text-align:center; padding: 60px 20px; color: #64748b;">
            <i class="fa-solid fa-circle-notch fa-spin" style="font-size: 28px; color: #103487;"></i>
            <div style="margin-top: 12px; font-size: 13px;">Cargando estructura y membrete oficial...</div>
        </div>
    `;
    abrirModal('modalPreviewPlantilla');

    let url = `/Cycsa/publico/plantillas/preview-modal?id=${id}`;
    if (vid) url += `&version_id=${vid}`;

    fetch(url)
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            contenedor.innerHTML = data.html;
        } else {
            contenedor.innerHTML = `<div style="padding: 30px; color: #b91c1c; text-align: center;">${data.message}</div>`;
        }
    })
    .catch(() => {
        contenedor.innerHTML = `<div style="padding: 30px; color: #b91c1c; text-align: center;">Error al cargar la vista previa.</div>`;
    });
}

// Cerrar modales con clic fuera
window.addEventListener('click', function(e) {
    if (e.target.classList && e.target.classList.contains('cycsa-modal-backdrop')) {
        e.target.style.display = 'none';
    }
});
</script>
