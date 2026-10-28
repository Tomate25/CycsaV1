<?php
$limpiarUtf8 = static fn($v) => htmlspecialchars((string)($v ?? ''), ENT_QUOTES, 'UTF-8');
$esEdicion = !empty($plantilla['id']);
$plantillaId = (int)($plantilla['id'] ?? 0);
$orientacionActual = $plantilla['orientacion'] ?? 'horizontal';
$selloUrlActual = !empty($plantilla['sello_acreditacion_url']) ? $plantilla['sello_acreditacion_url'] : 'publico/img/sello_acreditacion_default.jpeg';
$estadoActual = $plantilla['estado'] ?? 'borrador';
?>

<style>
    .studio-container {
        max-width: 100%;
        height: calc(100vh - 75px);
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #f1f5f9;
        font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        color: #1e293b;
    }

    /* Barra Superior del Studio */
    .studio-topbar {
        background: #ffffff;
        border-bottom: 1.5px solid #e2e8f0;
        padding: 12px 20px;
        z-index: 10;
        flex-shrink: 0;
        box-shadow: 0 1px 3px rgba(0,0,0,0.02);
    }
    .topbar-grid-1 {
        display: grid;
        grid-template-columns: 2.2fr 2.5fr 2fr 1.5fr auto;
        gap: 12px;
        align-items: center;
        margin-bottom: 8px;
    }
    .topbar-grid-2 {
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
        padding-top: 8px;
        border-top: 1px solid #f1f5f9;
        font-size: 12px;
    }

    .form-group-sm label {
        display: block;
        font-size: 10px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        margin-bottom: 3px;
        letter-spacing: 0.3px;
    }
    .studio-input, .studio-select {
        width: 100%;
        padding: 6px 10px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 12.5px;
        color: #1e293b;
        background: #ffffff;
        outline: none;
        box-sizing: border-box;
        transition: border-color 0.15s;
    }
    .studio-input:focus, .studio-select:focus {
        border-color: #103487;
        box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08);
    }

    /* Botones Corporativos */
    .btn-cycsa-sm {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 600;
        text-decoration: none;
        cursor: pointer;
        border: 1px solid transparent;
        transition: all 0.15s;
        line-height: 1.2;
    }
    .btn-primary-corp { background: #103487; color: #ffffff; border-color: #103487; }
    .btn-primary-corp:hover { background: #0c2766; color: #ffffff; }
    .btn-secondary-corp { background: #ffffff; color: #475569; border-color: #cbd5e1; }
    .btn-secondary-corp:hover { background: #f8fafc; color: #1e293b; }
    .btn-success-corp { background: #059669; color: #ffffff; border-color: #059669; }
    .btn-success-corp:hover { background: #047857; color: #ffffff; }
    .btn-warning-corp { background: #d97706; color: #ffffff; border-color: #d97706; }
    .btn-warning-corp:hover { background: #b45309; color: #ffffff; }
    .btn-danger-corp { background: #dc2626; color: #ffffff; border-color: #dc2626; }
    .btn-danger-corp:hover { background: #b91c1c; color: #ffffff; }

    /* Estructura Central de 3 Columnas */
    .studio-content {
        display: flex;
        flex: 1;
        overflow: hidden;
    }

    /* Columna Izquierda: Variables y Unidades */
    .studio-left {
        width: 300px;
        flex-shrink: 0;
        background: #ffffff;
        border-right: 1.5px solid #e2e8f0;
        display: flex;
        flex-direction: column;
    }
    .left-header {
        padding: 12px 16px;
        border-bottom: 1px solid #e2e8f0;
        background: #f8fafc;
    }
    .left-header-top {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 8px;
    }
    .left-header-top span {
        font-size: 12px;
        font-weight: 700;
        color: #1e293b;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    .var-list-scroll {
        padding: 10px 12px;
        overflow-y: auto;
        flex: 1;
    }
    .var-pill {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 8px 10px;
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 12px;
        cursor: grab;
        margin-bottom: 7px;
        transition: all 0.15s;
    }
    .var-pill:hover {
        background: #f0f9ff;
        border-color: #0284c7;
        transform: translateX(2px);
    }
    .var-pill.calc { border-left: 4px solid #059669; }
    .var-pill.num { border-left: 4px solid #2563eb; }
    .var-pill.txt { border-left: 4px solid #7c3aed; }

    /* Columna Central: Lienzo de Secciones y Campos */
    .studio-center {
        flex: 1;
        overflow-y: auto;
        padding: 20px;
        background: #f8fafc;
    }
    .center-topbar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 16px;
    }
    .center-topbar h2 {
        margin: 0;
        font-size: 16px;
        font-weight: 700;
        font-family: 'Outfit', sans-serif;
        color: #0f172a;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .sec-card {
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 8px;
        margin-bottom: 16px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.03);
    }
    .sec-card-header {
        background: #f1f5f9;
        padding: 8px 14px;
        border-bottom: 1px solid #e2e8f0;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .sec-card-header input {
        background: transparent;
        border: none;
        font-weight: 700;
        font-size: 13.5px;
        color: #0f172a;
        outline: none;
        width: 300px;
    }
    .sec-dropzone {
        min-height: 90px;
        padding: 14px;
        display: flex;
        flex-wrap: wrap;
        gap: 10px;
        align-content: flex-start;
        background: #fafbfc;
    }

    .field-card {
        background: #ffffff;
        border: 1.5px solid #cbd5e1;
        border-radius: 6px;
        padding: 10px 12px;
        cursor: pointer;
        transition: all 0.15s;
        box-shadow: 0 1px 2px rgba(0,0,0,0.02);
    }
    .field-card:hover {
        border-color: #103487;
        box-shadow: 0 2px 6px rgba(16,52,135,0.12);
    }
    .field-card.selected {
        border-color: #103487;
        background: #eff6ff;
        box-shadow: 0 0 0 2px rgba(16, 52, 135, 0.2);
    }

    /* Columna Derecha: Inspector de Propiedades */
    .studio-right {
        width: 340px;
        flex-shrink: 0;
        background: #ffffff;
        border-left: 1.5px solid #e2e8f0;
        overflow-y: auto;
        padding: 18px 20px;
        display: flex;
        flex-direction: column;
    }
    .inspector-title {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
        font-family: 'Outfit', sans-serif;
        padding-bottom: 10px;
        border-bottom: 1px solid #e2e8f0;
        margin: 0 0 16px 0;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    /* Barra Inferior Fija */
    .studio-bottombar {
        background: #ffffff;
        border-top: 1.5px solid #e2e8f0;
        padding: 10px 20px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-shrink: 0;
    }

    /* Modales CYCSA Puros */
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
    }
    .cycsa-modal-dialog.modal-xl {
        max-width: 950px;
        max-height: 90vh;
    }
    .cycsa-modal-dialog.modal-preview {
        max-width: 1140px;
        width: 96vw;
        max-height: 94vh;
    }
    .cycsa-modal-header {
        background: #103487;
        color: #ffffff;
        padding: 12px 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
    }
    .cycsa-modal-header h4 {
        margin: 0;
        font-size: 14px;
        font-weight: 700;
        font-family: 'Outfit', sans-serif;
    }
    .cycsa-modal-close {
        background: transparent;
        border: none;
        color: #ffffff;
        font-size: 20px;
        cursor: pointer;
        line-height: 1;
    }
    .cycsa-modal-body {
        padding: 18px;
        overflow-y: auto;
    }
    .cycsa-modal-footer {
        background: #f8fafc;
        border-top: 1px solid #e2e8f0;
        padding: 10px 18px;
        display: flex;
        justify-content: flex-end;
        gap: 8px;
    }
</style>

<div class="studio-container">
    <input type="hidden" id="estudio_csrf_token" value="<?= htmlspecialchars($csrf_token ?? ($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
    <!-- BARRA SUPERIOR DE PARÁMETROS -->
    <div class="studio-topbar">
        <div class="topbar-grid-1">
            <div style="display:flex; align-items:center; gap:8px;">
                <a href="/Cycsa/publico/plantillas" class="btn-cycsa-sm btn-secondary-corp" title="Volver al Catálogo">
                    <i class="fa-solid fa-arrow-left"></i>
                </a>
                <div class="form-group-sm" style="flex:1;">
                    <label>Código Formato</label>
                    <input type="text" id="plt_codigo" class="studio-input" style="font-family:monospace; font-weight:700; color:#103487;" value="<?= $limpiarUtf8($plantilla['codigo'] ?? 'PL-CYCSA-RT-FM-22-NUEVA') ?>" required>
                </div>
            </div>

            <div class="form-group-sm">
                <label>Nombre del Formato Técnico</label>
                <input type="text" id="plt_nombre" class="studio-input" style="font-weight:700;" value="<?= $limpiarUtf8($plantilla['nombre'] ?? '') ?>" placeholder="Ej: Granulometría de Suelos y Límites" required>
            </div>

            <div class="form-group-sm">
                <label>Ensayo Base (Catálogo RG-LI-05)</label>
                <select id="plt_ensayo_id" class="studio-select" required>
                    <?php foreach ($ensayos as $e): ?>
                        <option value="<?= (int)$e['id'] ?>" data-norma="<?= $limpiarUtf8($e['norma_astm'] ?? '') ?>" data-proc="<?= $limpiarUtf8($e['procedimiento_muestreo'] ?? '') ?>" <?= (!empty($plantilla['ensayo_id']) && (int)$plantilla['ensayo_id'] === (int)$e['id']) ? 'selected' : '' ?>>
                            [<?= $limpiarUtf8($e['no_item']) ?>] <?= $limpiarUtf8($e['ensayo_servicio']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group-sm">
                <label>Norma Técnica Oficial</label>
                <input type="text" id="plt_norma" class="studio-input" value="<?= $limpiarUtf8($plantilla['norma'] ?? 'ASTM D6913') ?>">
            </div>

            <div style="display:flex; align-items:center; justify-content:flex-end; gap:6px;">
                <span style="font-family:monospace; background:#0f172a; color:#ffffff; padding:4px 8px; border-radius:4px; font-size:11px; font-weight:700;">
                    <?= $limpiarUtf8($plantilla['version_actual'] ?? 'Rev. 01') ?>
                </span>
                <span id="badgeEstado" style="padding:4px 8px; border-radius:4px; font-size:11px; font-weight:700; text-transform:uppercase; <?= ($estadoActual === 'aprobada') ? 'background:#ecfdf5; color:#065f46; border:1px solid #a7f3d0;' : (($estadoActual === 'en_revision') ? 'background:#fffbeb; color:#92400e; border:1px solid #fde68a;' : 'background:#f1f5f9; color:#475569; border:1px solid #cbd5e1;') ?>">
                    <?= strtoupper($estadoActual) ?>
                </span>
            </div>
        </div>

        <!-- SEGUNDA FILA: ORIENTACIÓN, SELLOS Y VARIANTES -->
        <div class="topbar-grid-2">
            <div style="display:flex; align-items:center; gap:12px;">
                <span style="font-weight:700; color:#475569;">Orientación de Hoja:</span>
                <div style="display:inline-flex; border:1px solid #cbd5e1; border-radius:6px; overflow:hidden;">
                    <button type="button" id="btnOriHoriz" class="btn-cycsa-sm <?= ($orientacionActual === 'horizontal') ? 'btn-primary-corp' : 'btn-secondary-corp' ?>" style="border-radius:0; border:none;" onclick="cambiarOrientacion('horizontal')">
                        <i class="fa-solid fa-file-invoice"></i> Horizontal
                    </button>
                    <button type="button" id="btnOriVert" class="btn-cycsa-sm <?= ($orientacionActual === 'vertical') ? 'btn-primary-corp' : 'btn-secondary-corp' ?>" style="border-radius:0; border:none; border-left:1px solid #cbd5e1;" onclick="cambiarOrientacion('vertical')">
                        <i class="fa-solid fa-file-lines"></i> Vertical
                    </button>
                </div>
                <input type="hidden" id="plt_orientacion" value="<?= $limpiarUtf8($orientacionActual) ?>">
            </div>

            <div style="display:flex; align-items:center; gap:10px;">
                <span style="font-weight:700; color:#475569;">Variante Cliente:</span>
                <select id="plt_cliente_id" class="studio-select" style="width:260px; font-size:12px;">
                    <option value="">-- Plantilla General (Sin cliente asignado) --</option>
                    <?php foreach ($clientes as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= (!empty($plantilla['cliente_id']) && (int)$plantilla['cliente_id'] === (int)$c['id']) ? 'selected' : '' ?>>
                            <?= $limpiarUtf8($c['nombre']) ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display:flex; align-items:center; gap:16px;">
                <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-weight:600; color:#475569;">
                    <input type="checkbox" id="plt_incluir_sello" <?= (!empty($plantilla['incluir_sello_acreditacion']) || !isset($plantilla['incluir_sello_acreditacion'])) ? 'checked' : '' ?>>
                    Sello de Acreditación ONA
                </label>
                <div style="display:flex; align-items:center; gap:6px; background:#f8fafc; border:1px solid #cbd5e1; padding:3px 6px; border-radius:6px;">
                    <img id="imgSelloPreview" src="/Cycsa/<?= $limpiarUtf8($selloUrlActual) ?>" alt="Sello" style="height:24px; max-width:70px; object-fit:contain;">
                    <input type="hidden" id="plt_sello_url" value="<?= $limpiarUtf8($selloUrlActual) ?>">
                    <button type="button" class="btn-cycsa-sm btn-secondary-corp" style="padding:2px 6px;" onclick="document.getElementById('inputSubirSello').click()" title="Subir otro sello">
                        <i class="fa-solid fa-upload"></i>
                    </button>
                    <button type="button" class="btn-cycsa-sm btn-secondary-corp" style="padding:2px 6px;" onclick="restaurarSelloDefault()" title="Restaurar sello predeterminado">
                        <i class="fa-solid fa-rotate-left"></i>
                    </button>
                    <input type="file" id="inputSubirSello" style="display:none;" accept="image/*" onchange="subirSelloArchivo(this)">
                </div>
                <label style="display:flex; align-items:center; gap:6px; cursor:pointer; font-weight:600; color:#475569;">
                    <input type="checkbox" id="plt_es_por_defecto" <?= !empty($plantilla['es_por_defecto']) ? 'checked' : '' ?>>
                    Predeterminada para este Ensayo
                </label>
            </div>
        </div>
    </div>

    <!-- CONTENIDO PRINCIPAL: 3 COLUMNAS -->
    <div class="studio-content">
        <!-- COLUMNA IZQUIERDA: CATÁLOGO DE VARIABLES -->
        <div class="studio-left">
            <div class="left-header">
                <div class="left-header-top">
                    <span><i class="fa-solid fa-database text-primary"></i> Variables y Unidades</span>
                    <button type="button" class="btn-cycsa-sm btn-primary-corp" onclick="abrirModal('modalNuevaVariable')" style="padding:3px 8px; font-size:11px;">
                        <i class="fa-solid fa-plus"></i> Nueva
                    </button>
                </div>
                <input type="text" id="buscarVar" class="studio-input" placeholder="Filtrar variables..." onkeyup="filtrarVariables()" style="font-size:11.5px; margin-bottom:8px;">
                <div style="background:#f1f5f9; border:1px solid #cbd5e1; border-radius:6px; padding:6px 8px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:3px;">
                        <span style="font-size:10px; font-weight:700; color:#475569; text-transform:uppercase;">
                            <i class="fa-solid fa-arrow-right-to-bracket text-primary"></i> Insertar en Sección:
                        </span>
                    </div>
                    <select id="selectSeccionDestino" class="studio-select" style="font-size:11.5px; padding:4px 6px; font-weight:600;" onchange="cambiarSeccionActiva(parseInt(this.value))">
                    </select>
                </div>
            </div>

            <div class="var-list-scroll" id="listaVariables">
                <?php foreach ($variables as $v): ?>
                    <div class="var-pill <?= ($v['tipo'] === 'calculado') ? 'calc' : (($v['tipo'] === 'numero') ? 'num' : 'txt') ?>" 
                         data-id="<?= (int)$v['id'] ?>"
                         data-codigo="<?= $limpiarUtf8($v['codigo']) ?>"
                         data-etiqueta="<?= $limpiarUtf8($v['etiqueta']) ?>"
                         data-tipo="<?= $limpiarUtf8($v['tipo']) ?>"
                         data-unidad="<?= $limpiarUtf8($v['unidad_simbolo'] ?? '') ?>"
                         data-decimales="<?= (int)($v['decimales'] ?? 2) ?>"
                         draggable="true" 
                         ondragstart="dragVariable(event)"
                         onclick="insertarVariableSeleccionada(<?= (int)$v['id'] ?>, '<?= addslashes($v['codigo']) ?>', '<?= addslashes($v['etiqueta']) ?>', '<?= addslashes($v['unidad_simbolo'] ?? '') ?>')">
                        <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; margin-right:6px;">
                            <strong style="display:block; font-family:monospace; color:#0f172a; font-size:11.5px;">{<?= $limpiarUtf8($v['codigo']) ?>}</strong>
                            <span style="font-size:10.5px; color:#64748b;"><?= $limpiarUtf8($v['etiqueta']) ?></span>
                        </div>
                        <span style="background:#ffffff; border:1px solid #cbd5e1; border-radius:4px; padding:1px 6px; font-size:10px; color:#475569; flex-shrink:0;">
                            <?= $limpiarUtf8($v['unidad_simbolo'] ?: $v['tipo']) ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- COLUMNA CENTRAL: LIENZO DE LA PLANTILLA -->
        <div class="studio-center" id="lienzoPlantilla">
            <div class="center-topbar">
                <h2><i class="fa-solid fa-layer-group text-primary"></i> Lienzo de Secciones y Campos</h2>
                <button type="button" class="btn-cycsa-sm btn-primary-corp" onclick="agregarSeccionLienzo()">
                    <i class="fa-solid fa-plus"></i> Agregar Sección
                </button>
            </div>

            <div id="contenedorSecciones">
                <!-- Secciones y campos renderizados dinámicamente por JavaScript -->
            </div>
        </div>

        <!-- COLUMNA DERECHA: INSPECTOR DE PROPIEDADES -->
        <div class="studio-right" id="inspectorPropiedades">
            <h3 class="inspector-title">
                <i class="fa-solid fa-sliders text-primary"></i> Propiedades del Campo
            </h3>

            <div id="inspectorVacio" style="text-align:center; padding:50px 10px; color:#64748b; font-size:12.5px;">
                <i class="fa-solid fa-arrow-pointer" style="font-size:28px; color:#cbd5e1; display:block; margin-bottom:12px;"></i>
                Seleccione un campo del lienzo central para configurar su fórmula, visibilidad, decimales y propiedades.
            </div>

            <div id="inspectorForm" style="display:none;">
                <div style="margin-bottom:12px;">
                    <label style="display:block; font-size:10.5px; font-weight:700; color:#64748b; margin-bottom:3px; text-transform:uppercase;">Variable Base:</label>
                    <input type="text" id="insp_codigo" class="studio-input" style="font-family:monospace; background:#f8fafc; font-weight:700; color:#103487;" readonly>
                </div>

                <div style="margin-bottom:12px;">
                    <label style="display:block; font-size:10.5px; font-weight:700; color:#64748b; margin-bottom:3px; text-transform:uppercase;">Etiqueta en Reporte:</label>
                    <input type="text" id="insp_etiqueta" class="studio-input" onchange="actualizarCampoSeleccionado()">
                </div>

                <div style="margin-bottom:12px;">
                    <label style="display:block; font-size:10.5px; font-weight:700; color:#64748b; margin-bottom:3px; text-transform:uppercase;">Ancho en Pantalla (Columnas):</label>
                    <select id="insp_ancho" class="studio-select" onchange="actualizarCampoSeleccionado()">
                        <option value="3">3 Columnas (25% - Cuarto de fila)</option>
                        <option value="4">4 Columnas (33% - Tercio de fila)</option>
                        <option value="6">6 Columnas (50% - Media fila)</option>
                        <option value="12">12 Columnas (100% - Fila completa)</option>
                    </select>
                </div>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
                    <div>
                        <label style="display:block; font-size:10.5px; font-weight:700; color:#64748b; margin-bottom:3px; text-transform:uppercase;">Decimales:</label>
                        <input type="text" inputmode="numeric" pattern="[0-9]+" autocomplete="off" id="insp_decimales" class="studio-input" min="0" max="6" value="2" onchange="actualizarCampoSeleccionado()">
                    </div>
                    <div>
                        <label style="display:block; font-size:10.5px; font-weight:700; color:#64748b; margin-bottom:3px; text-transform:uppercase;">Unidad Símbolo:</label>
                        <input type="text" id="insp_unidad" class="studio-input" style="background:#f8fafc;" readonly>
                    </div>
                </div>

                <div style="margin-bottom:10px;">
                    <label style="display:flex; align-items:center; gap:6px; font-size:12px; font-weight:600; cursor:pointer;">
                        <input type="checkbox" id="insp_visible" checked onchange="actualizarCampoSeleccionado()">
                        Visible en Informe Oficial PDF
                    </label>
                </div>

                <div style="margin-bottom:16px;">
                    <label style="display:flex; align-items:center; gap:6px; font-size:12px; font-weight:600; cursor:pointer;">
                        <input type="checkbox" id="insp_solo_lectura" onchange="actualizarCampoSeleccionado()">
                        Solo Lectura (Bloqueado para captura manual)
                    </label>
                </div>

                <!-- EDITOR DE FÓRMULAS SEGURO -->
                <div style="border-top:1px solid #e2e8f0; padding-top:14px; margin-top:14px;">
                    <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:6px;">
                        <label style="font-size:11px; font-weight:700; color:#047857; text-transform:uppercase;">
                            <i class="fa-solid fa-calculator"></i> Editor de Fórmula:
                        </label>
                        <button type="button" class="btn-cycsa-sm btn-secondary-corp" style="padding:2px 8px; font-size:11px;" onclick="validarFormulaActual()">
                            <i class="fa-solid fa-check"></i> Probar
                        </button>
                    </div>
                    <textarea id="insp_formula" class="studio-input" style="font-family:monospace; min-height:75px; font-size:12px;" placeholder="Ej: ROUND({carga_lb} / {area_in2}, 2)" onchange="actualizarCampoSeleccionado()"></textarea>
                    <div id="formulaStatus" style="font-size:11px; margin-top:4px;"></div>
                    <div style="font-size:10.5px; color:#64748b; margin-top:6px; line-height:1.4;">
                        Haz clic en una variable del panel izquierdo para insertar <code>{variable}</code> en la fórmula. Funciones seguras permitidas: ROUND, SQRT, POW, ABS, MIN, MAX.
                    </div>
                </div>

                <div style="border-top:1px solid #e2e8f0; padding-top:14px; margin-top:16px;">
                    <button type="button" class="btn-cycsa-sm btn-danger-corp" style="width:100%; justify-content:center; padding:8px 12px;" onclick="eliminarCampoSeleccionado()">
                        <i class="fa-solid fa-trash"></i> Eliminar Campo del Lienzo
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- BARRA INFERIOR FIJA -->
    <div class="studio-bottombar">
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="font-size:12px; color:#64748b;">Estado:</span>
            <span style="background:#f1f5f9; border:1px solid #cbd5e1; border-radius:4px; padding:2px 8px; font-size:11px; font-weight:700; text-transform:uppercase;">
                <?= ucfirst($estadoActual) ?>
            </span>
            <span id="mensajeGuardado" style="font-size:12px; font-weight:700; color:#059669; margin-left:10px;"></span>
        </div>

        <div style="display:flex; gap:8px; align-items:center;">
            <button type="button" class="btn-cycsa-sm btn-secondary-corp" onclick="abrirPreviewEnVivo()">
                <i class="fa-solid fa-eye"></i> Vista Previa en Vivo
            </button>
            <?php if ($esEdicion): ?>
                <button type="button" class="btn-cycsa-sm btn-secondary-corp" onclick="abrirModalClonar(<?= $plantillaId ?>, '<?= addslashes($plantilla['nombre'] ?? '') ?>')">
                    <i class="fa-solid fa-copy"></i> Duplicar como Variante
                </button>
            <?php endif; ?>
            <button type="button" class="btn-cycsa-sm btn-primary-corp" onclick="guardarPlantilla('borrador')">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Borrador
            </button>
            <?php if ($estadoActual !== 'aprobada'): ?>
                <button type="button" class="btn-cycsa-sm btn-warning-corp" onclick="guardarPlantilla('en_revision')">
                    <i class="fa-solid fa-paper-plane"></i> Enviar a Revisión
                </button>
            <?php endif; ?>
            <button type="button" class="btn-cycsa-sm btn-success-corp" onclick="aprobarPlantillaDefinitiva()">
                <i class="fa-solid fa-circle-check"></i> Aprobar Versión Oficial
            </button>
        </div>
    </div>
</div>

<!-- MODAL NUEVA VARIABLE EN LÍNEA -->
<div class="cycsa-modal-backdrop" id="modalNuevaVariable">
    <div class="cycsa-modal-dialog">
        <div class="cycsa-modal-header">
            <h4><i class="fa-solid fa-plus-circle"></i> Crear Nueva Variable Reutilizable</h4>
            <button type="button" class="cycsa-modal-close" onclick="cerrarModal('modalNuevaVariable')">&times;</button>
        </div>
        <div class="cycsa-modal-body">
            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:3px; text-transform:uppercase;">Código Único (minúsculas, sin espacios):</label>
                <input type="text" id="nvar_codigo" class="studio-input" style="font-family:monospace;" placeholder="ej: masa_seca_suelo" required>
            </div>
            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:3px; text-transform:uppercase;">Etiqueta Descriptiva:</label>
                <input type="text" id="nvar_etiqueta" class="studio-input" placeholder="ej: Masa Seca de Suelo en Tara" required>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:12px;">
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:3px; text-transform:uppercase;">Tipo de Variable:</label>
                    <select id="nvar_tipo" class="studio-select">
                        <option value="numero">Número</option>
                        <option value="calculado">Calculado f(x)</option>
                        <option value="texto">Texto</option>
                        <option value="fecha">Fecha</option>
                        <option value="lista">Lista Selección</option>
                    </select>
                </div>
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:3px; text-transform:uppercase;">Unidad de Medida:</label>
                    <select id="nvar_unidad_id" class="studio-select">
                        <option value="">-- Sin unidad --</option>
                        <?php foreach ($unidades as $u): ?>
                            <option value="<?= (int)$u['id'] ?>"><?= $limpiarUtf8($u['simbolo']) ?> (<?= $limpiarUtf8($u['nombre']) ?>)</option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:8px;">
                <div>
                    <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:3px; text-transform:uppercase;">Decimales:</label>
                    <input type="text" inputmode="numeric" pattern="[0-9]+" autocomplete="off" id="nvar_decimales" class="studio-input" value="2" min="0" max="6">
                </div>
                <div style="display:flex; align-items:flex-end; padding-bottom:6px;">
                    <label style="display:flex; align-items:center; gap:6px; font-size:12px; font-weight:600; cursor:pointer;">
                        <input type="checkbox" id="nvar_requerido" checked>
                        Requerido
                    </label>
                </div>
            </div>
        </div>
        <div class="cycsa-modal-footer">
            <button type="button" class="btn-cycsa-sm btn-secondary-corp" onclick="cerrarModal('modalNuevaVariable')">Cancelar</button>
            <button type="button" class="btn-cycsa-sm btn-primary-corp" onclick="guardarNuevaVariable()">Guardar Variable</button>
        </div>
    </div>
</div>

<!-- MODAL PREVIEW EN VIVO -->
<div class="cycsa-modal-backdrop" id="modalPreviewStudio">
    <div class="cycsa-modal-dialog modal-preview">
        <div class="cycsa-modal-header" style="background:#0f172a;">
            <h4><i class="fa-solid fa-file-contract"></i> Vista Previa en Vivo de la Plantilla</h4>
            <button type="button" class="cycsa-modal-close" onclick="cerrarModal('modalPreviewStudio')">&times;</button>
        </div>
        <div class="cycsa-modal-body" id="contenedorPreviewStudio" style="padding:0; min-height:400px; background:#f8fafc;">
            <!-- Inyectado vía AJAX -->
        </div>
        <div class="cycsa-modal-footer">
            <button type="button" class="btn-cycsa-sm btn-secondary-corp" onclick="cerrarModal('modalPreviewStudio')">Cerrar</button>
        </div>
    </div>
</div>

<script>
// Estado global del estudio
let plantillaIdActual = <?= $plantillaId ?>;
let secciones = <?= json_encode($plantilla['secciones'] ?? [
    ['nombre' => 'Identificación de Especímenes', 'tipo_layout' => 'formulario', 'campos' => []],
    ['nombre' => 'Parámetros y Cálculos', 'tipo_layout' => 'formulario', 'campos' => []]
], JSON_UNESCAPED_UNICODE) ?>;
let seccionActivaIdx = 0;
let campoSeleccionado = null; // { secIdx, cmpIdx }

document.addEventListener('DOMContentLoaded', () => {
    if (!Array.isArray(secciones) || secciones.length === 0) {
        secciones = [
            { nombre: 'Identificación de Especímenes', tipo_layout: 'formulario', campos: [] },
            { nombre: 'Parámetros y Cálculos', tipo_layout: 'formulario', campos: [] }
        ];
    }
    renderizarLienzo();
});

function abrirModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'flex';
}

function cerrarModal(id) {
    const el = document.getElementById(id);
    if (el) el.style.display = 'none';
}

function cambiarSeccionActiva(sIdx) {
    if (sIdx >= 0 && sIdx < secciones.length) {
        seccionActivaIdx = sIdx;
        renderizarLienzo();
    }
}

function sincronizarSelectorSecciones() {
    const sel = document.getElementById('selectSeccionDestino');
    if (!sel) return;
    sel.innerHTML = secciones.map((s, idx) => 
        `<option value="${idx}" ${idx === seccionActivaIdx ? 'selected' : ''}>Sec. ${idx + 1}: ${escapeHtml(s.nombre || 'Sin título')}</option>`
    ).join('');
}

function renderizarLienzo() {
    const contenedor = document.getElementById('contenedorSecciones');
    contenedor.innerHTML = '';

    if (secciones.length === 0) {
        secciones.push({
            nombre: 'Sección 1',
            tipo_layout: 'formulario',
            campos: []
        });
    }

    if (seccionActivaIdx >= secciones.length) {
        seccionActivaIdx = Math.max(0, secciones.length - 1);
    }
    if (seccionActivaIdx < 0) {
        seccionActivaIdx = 0;
    }

    sincronizarSelectorSecciones();

    secciones.forEach((sec, sIdx) => {
        const esActiva = (sIdx === seccionActivaIdx);
        const secDiv = document.createElement('div');
        secDiv.className = 'sec-card';
        if (esActiva) {
            secDiv.style.border = '2px solid #103487';
            secDiv.style.boxShadow = '0 0 0 1px #103487, 0 4px 12px rgba(16, 52, 135, 0.08)';
        }

        secDiv.innerHTML = `
            <div class="sec-card-header" style="${esActiva ? 'background:#eef4ff;' : ''}">
                <div style="display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-grip-vertical" style="color:#94a3b8;"></i>
                    <input type="text" value="${escapeHtml(sec.nombre)}" onchange="actualizarNombreSeccion(${sIdx}, this.value)" style="font-weight:700;">
                    ${esActiva ? '<span style="background:#103487; color:#ffffff; font-size:10px; font-weight:700; padding:2px 7px; border-radius:4px; text-transform:uppercase; letter-spacing:0.3px;">Sección Activa</span>' : ''}
                </div>
                <div style="display:flex; align-items:center; gap:6px;">
                    <button type="button" class="btn-cycsa-sm ${esActiva ? 'btn-primary-corp' : 'btn-secondary-corp'}" style="padding:2px 8px; font-size:11px;" onclick="cambiarSeccionActiva(${sIdx})" title="Marcar como sección donde se insertarán variables">
                        <i class="fa-solid fa-crosshairs"></i> ${esActiva ? 'Insertando aquí' : 'Insertar aquí'}
                    </button>
                    <span style="background:#ffffff; border:1px solid #cbd5e1; border-radius:4px; padding:2px 8px; font-size:11px; color:#64748b;">${(sec.campos || []).length} campos</span>
                    <button type="button" class="btn-cycsa-sm btn-danger-corp" style="padding:2px 6px; font-size:10.5px;" onclick="eliminarSeccion(${sIdx})" title="Eliminar Sección"><i class="fa-solid fa-trash"></i></button>
                </div>
            </div>
            <div class="sec-dropzone" ondragover="allowDrop(event)" ondrop="dropVariable(event, ${sIdx})" onclick="if(event.target === this) cambiarSeccionActiva(${sIdx})">
                ${(sec.campos || []).map((c, cIdx) => `
                    <div class="field-card ${campoSeleccionado && campoSeleccionado.secIdx === sIdx && campoSeleccionado.cmpIdx === cIdx ? 'selected' : ''}" 
                         style="width: calc(${c.ancho_cols ? (c.ancho_cols/12*100) : 33.33}% - 10px);"
                         onclick="seleccionarCampo(${sIdx}, ${cIdx}, event)">
                        <div style="display:flex; justify-content:space-between; align-items:flex-start; margin-bottom:4px;">
                            <span style="padding:2px 6px; border-radius:4px; font-size:10px; font-weight:700; ${c.formula ? 'background:#ecfdf5; color:#047857; border:1px solid #a7f3d0;' : 'background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;'}">
                                ${c.formula ? '<i class="fa-solid fa-calculator" style="margin-right:3px;"></i>f(x)' : '{' + escapeHtml(c.variable_codigo || 'var') + '}'}
                            </span>
                            <span style="font-size:10px; color:#64748b;">${escapeHtml(c.unidad_simbolo || '')}</span>
                        </div>
                        <div style="font-weight:700; color:#0f172a; font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">${escapeHtml(c.variable_etiqueta || '')}</div>
                        ${c.formula ? `<div style="font-size:10px; font-family:monospace; color:#64748b; overflow:hidden; text-overflow:ellipsis; white-space:nowrap; margin-top:2px;">${escapeHtml(c.formula)}</div>` : ''}
                    </div>
                `).join('')}
                ${(sec.campos || []).length === 0 ? `<div style="color:#94a3b8; font-size:12px; width:100%; text-align:center; padding:20px 0; cursor:pointer;" onclick="cambiarSeccionActiva(${sIdx})"><i class="fa-solid fa-plus-circle" style="margin-right:4px;"></i>Sección vacía. Haga clic para activarla y seleccione variables del catálogo izquierdo</div>` : ''}
            </div>
        `;
        contenedor.appendChild(secDiv);
    });
}

function agregarSeccionLienzo() {
    secciones.push({
        nombre: 'Nueva Sección ' + (secciones.length + 1),
        tipo_layout: 'formulario',
        campos: []
    });
    seccionActivaIdx = secciones.length - 1;
    renderizarLienzo();
}

function eliminarSeccion(sIdx) {
    if (secciones.length <= 1) {
        alert('Debe existir al menos una sección en la plantilla.');
        return;
    }
    if (confirm('¿Desea eliminar esta sección y todos sus campos asociados?')) {
        secciones.splice(sIdx, 1);
        if (seccionActivaIdx >= secciones.length) {
            seccionActivaIdx = Math.max(0, secciones.length - 1);
        }
        campoSeleccionado = null;
        actualizarInspector();
        renderizarLienzo();
    }
}

function actualizarNombreSeccion(sIdx, val) {
    if (secciones[sIdx]) {
        secciones[sIdx].nombre = val.trim() || ('Sección ' + (sIdx + 1));
        sincronizarSelectorSecciones();
    }
}

function insertarVariableSeleccionada(varId, codigo, etiqueta, unidad, forzarSecIdx = null) {
    const inpFormula = document.getElementById('insp_formula');
    if (document.activeElement === inpFormula) {
        const start = inpFormula.selectionStart || inpFormula.value.length;
        const end = inpFormula.selectionEnd || inpFormula.value.length;
        const currentVal = inpFormula.value;
        inpFormula.value = currentVal.substring(0, start) + `{${codigo}}` + currentVal.substring(end);
        actualizarCampoSeleccionado();
        return;
    }

    if (secciones.length === 0) {
        agregarSeccionLienzo();
    }

    let targetSec = (forzarSecIdx !== null && forzarSecIdx >= 0 && forzarSecIdx < secciones.length)
        ? forzarSecIdx
        : seccionActivaIdx;

    if (targetSec < 0 || targetSec >= secciones.length) {
        targetSec = 0;
    }

    secciones[targetSec].campos.push({
        variable_id: varId,
        variable_codigo: codigo,
        variable_etiqueta: etiqueta,
        unidad_simbolo: unidad,
        ancho_cols: 4,
        decimales: 2,
        visible_en_reporte: 1,
        solo_lectura: 0,
        formula: ''
    });

    seccionActivaIdx = targetSec;
    campoSeleccionado = {
        secIdx: targetSec,
        cmpIdx: secciones[targetSec].campos.length - 1
    };

    renderizarLienzo();
    actualizarInspector();
}

function seleccionarCampo(sIdx, cIdx, ev) {
    if (ev) ev.stopPropagation();
    seccionActivaIdx = sIdx;
    campoSeleccionado = { secIdx: sIdx, cmpIdx: cIdx };
    renderizarLienzo();
    actualizarInspector();
}

function actualizarInspector() {
    const boxVacio = document.getElementById('inspectorVacio');
    const boxForm = document.getElementById('inspectorForm');

    if (!campoSeleccionado || !secciones[campoSeleccionado.secIdx] || !secciones[campoSeleccionado.secIdx].campos[campoSeleccionado.cmpIdx]) {
        boxVacio.style.display = 'block';
        boxForm.style.display = 'none';
        return;
    }

    const c = secciones[campoSeleccionado.secIdx].campos[campoSeleccionado.cmpIdx];
    boxVacio.style.display = 'none';
    boxForm.style.display = 'block';

    document.getElementById('insp_codigo').value = '{' + (c.variable_codigo || '') + '}';
    document.getElementById('insp_etiqueta').value = c.variable_etiqueta || '';
    document.getElementById('insp_ancho').value = c.ancho_cols || 4;
    document.getElementById('insp_decimales').value = (c.decimales !== undefined) ? c.decimales : 2;
    document.getElementById('insp_unidad').value = c.unidad_simbolo || '';
    document.getElementById('insp_visible').checked = (c.visible_en_reporte !== 0);
    document.getElementById('insp_solo_lectura').checked = (c.solo_lectura === 1);
    document.getElementById('insp_formula').value = c.formula || '';
    document.getElementById('formulaStatus').innerHTML = '';
}

function actualizarCampoSeleccionado() {
    if (!campoSeleccionado || !secciones[campoSeleccionado.secIdx] || !secciones[campoSeleccionado.secIdx].campos[campoSeleccionado.cmpIdx]) return;

    const c = secciones[campoSeleccionado.secIdx].campos[campoSeleccionado.cmpIdx];
    c.variable_etiqueta = document.getElementById('insp_etiqueta').value;
    c.ancho_cols = parseInt(document.getElementById('insp_ancho').value) || 4;
    c.decimales = parseInt(document.getElementById('insp_decimales').value) || 0;
    c.visible_en_reporte = document.getElementById('insp_visible').checked ? 1 : 0;
    c.solo_lectura = document.getElementById('insp_solo_lectura').checked ? 1 : 0;
    c.formula = document.getElementById('insp_formula').value.trim();

    renderizarLienzo();
}

function eliminarCampoSeleccionado() {
    if (!campoSeleccionado) return;
    secciones[campoSeleccionado.secIdx].campos.splice(campoSeleccionado.cmpIdx, 1);
    campoSeleccionado = null;
    actualizarInspector();
    renderizarLienzo();
}

function validarFormulaActual() {
    const f = document.getElementById('insp_formula').value.trim();
    const stBox = document.getElementById('formulaStatus');
    if (!f) {
        stBox.innerHTML = '<span style="color:#64748b;">Sin fórmula (captura manual).</span>';
        return;
    }

    fetch('/Cycsa/publico/plantillas/validar-formula?formula=' + encodeURIComponent(f))
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            stBox.innerHTML = `<span style="color:#059669; font-weight:700;"><i class="fa-solid fa-check"></i> Fórmula válida. Variables: ${data.variables.join(', ')}</span>`;
        } else {
            stBox.innerHTML = `<span style="color:#dc2626; font-weight:700;"><i class="fa-solid fa-triangle-exclamation"></i> Error: ${data.message}</span>`;
        }
    })
    .catch(() => {
        stBox.innerHTML = '<span style="color:#dc2626;">Error al validar fórmula.</span>';
    });
}

function allowDrop(e) { e.preventDefault(); }

function dragVariable(e) {
    const pill = e.currentTarget;
    e.dataTransfer.setData('text/plain', JSON.stringify({
        id: pill.dataset.id,
        codigo: pill.dataset.codigo,
        etiqueta: pill.dataset.etiqueta,
        tipo: pill.dataset.tipo,
        unidad: pill.dataset.unidad,
        decimales: pill.dataset.decimales
    }));
}

function dropVariable(e, sIdx) {
    e.preventDefault();
    try {
        const data = JSON.parse(e.dataTransfer.getData('text/plain'));
        insertarVariableSeleccionada(data.id, data.codigo, data.etiqueta, data.unidad, sIdx);
    } catch (err) {
        console.error("Error al procesar arrastre:", err);
    }
}

function cambiarOrientacion(ori) {
    document.getElementById('plt_orientacion').value = ori;
    const btnH = document.getElementById('btnOriHoriz');
    const btnV = document.getElementById('btnOriVert');
    if (ori === 'horizontal') {
        btnH.className = 'btn-cycsa-sm btn-primary-corp';
        btnV.className = 'btn-cycsa-sm btn-secondary-corp';
    } else {
        btnH.className = 'btn-cycsa-sm btn-secondary-corp';
        btnV.className = 'btn-cycsa-sm btn-primary-corp';
    }
}

function subirSelloArchivo(input) {
    if (!input.files || !input.files[0]) return;
    const file = input.files[0];
    const fd = new FormData();
    fd.append('sello_archivo', file);

    fetch('/Cycsa/publico/plantillas/subir-sello-acreditacion', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            document.getElementById('imgSelloPreview').src = '/Cycsa/' + data.url;
            document.getElementById('plt_sello_url').value = data.url;
            alert('Sello de acreditación subido con éxito.');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Error en la comunicación con el servidor.'));
}

function restaurarSelloDefault() {
    const defaultUrl = 'publico/img/sello_acreditacion_default.jpeg';
    document.getElementById('imgSelloPreview').src = '/Cycsa/' + defaultUrl;
    document.getElementById('plt_sello_url').value = defaultUrl;
}

function filtrarVariables() {
    const q = document.getElementById('buscarVar').value.toLowerCase();
    document.querySelectorAll('#listaVariables .var-pill').forEach(el => {
        const txt = el.innerText.toLowerCase();
        el.style.display = txt.includes(q) ? 'flex' : 'none';
    });
}

function guardarNuevaVariable() {
    const cod = document.getElementById('nvar_codigo').value.trim();
    const eti = document.getElementById('nvar_etiqueta').value.trim();
    const tip = document.getElementById('nvar_tipo').value;
    const uid = document.getElementById('nvar_unidad_id').value;
    const dec = document.getElementById('nvar_decimales').value;
    const req = document.getElementById('nvar_requerido').checked ? 1 : 0;

    if (!cod || !eti) {
        alert('Código y etiqueta son requeridos.');
        return;
    }

    const fd = new FormData();
    fd.append('codigo', cod);
    fd.append('etiqueta', eti);
    fd.append('tipo', tip);
    fd.append('unidad_id', uid);
    fd.append('decimales', dec);
    fd.append('requerido', req);

    fetch('/Cycsa/publico/plantillas/crear-variable-ajax', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            const cList = document.getElementById('listaVariables');
            cList.innerHTML = data.variables.map(v => `
                <div class="var-pill ${v.tipo === 'calculado' ? 'calc' : (v.tipo === 'numero' ? 'num' : 'txt')}" 
                     data-id="${v.id}"
                     data-codigo="${escapeHtml(v.codigo)}"
                     data-etiqueta="${escapeHtml(v.etiqueta)}"
                     data-tipo="${escapeHtml(v.tipo)}"
                     data-unidad="${escapeHtml(v.unidad_simbolo || '')}"
                     draggable="true" 
                     ondragstart="dragVariable(event)"
                     onclick="insertarVariableSeleccionada(${v.id}, '${escapeHtml(v.codigo)}', '${escapeHtml(v.etiqueta)}', '${escapeHtml(v.unidad_simbolo || '')}')">
                    <div style="overflow:hidden; text-overflow:ellipsis; white-space:nowrap; margin-right:6px;">
                        <strong style="display:block; font-family:monospace; color:#0f172a; font-size:11.5px;">{${escapeHtml(v.codigo)}}</strong>
                        <span style="font-size:10.5px; color:#64748b;">${escapeHtml(v.etiqueta)}</span>
                    </div>
                    <span style="background:#ffffff; border:1px solid #cbd5e1; border-radius:4px; padding:1px 6px; font-size:10px; color:#475569;">${escapeHtml(v.unidad_simbolo || v.tipo)}</span>
                </div>
            `).join('');

            cerrarModal('modalNuevaVariable');
        } else {
            alert('Error: ' + data.message);
        }
    })
    .catch(() => alert('Error al guardar la variable.'));
}

function guardarPlantilla(nuevoEstado) {
    const codigo = document.getElementById('plt_codigo').value.trim();
    const nombre = document.getElementById('plt_nombre').value.trim();
    const ensayoId = document.getElementById('plt_ensayo_id').value;
    const norma = document.getElementById('plt_norma').value.trim();
    const ori = document.getElementById('plt_orientacion').value;
    const clienteId = document.getElementById('plt_cliente_id').value;
    const incSello = document.getElementById('plt_incluir_sello').checked ? 1 : 0;
    const selloUrl = document.getElementById('plt_sello_url').value;
    const esDef = document.getElementById('plt_es_por_defecto').checked ? 1 : 0;
    const csrfEl = document.getElementById('estudio_csrf_token');
    const csrf = csrfEl ? csrfEl.value : '<?= $_SESSION['csrf_token'] ?? '' ?>';

    if (!codigo || !nombre || !ensayoId) {
        alert('Por favor complete el código, nombre y ensayo base.');
        return;
    }

    const fd = new FormData();
    fd.append('id', plantillaIdActual);
    fd.append('codigo', codigo);
    fd.append('nombre', nombre);
    fd.append('ensayo_id', ensayoId);
    fd.append('norma', norma);
    fd.append('orientacion', ori);
    fd.append('cliente_id', clienteId);
    fd.append('incluir_sello_acreditacion', incSello);
    fd.append('sello_acreditacion_url', selloUrl);
    fd.append('es_por_defecto', esDef);
    fd.append('estado', nuevoEstado || 'borrador');
    fd.append('secciones_json', JSON.stringify(secciones));
    fd.append('csrf_token', csrf);
    fd.append('es_ajax', '1');

    const statusEl = document.getElementById('mensajeGuardado');
    if (statusEl) {
        statusEl.innerHTML = '<i class="fa-solid fa-spinner fa-spin" style="margin-right:4px;"></i>Guardando...';
    }

    fetch('/Cycsa/publico/plantillas/guardar', {
        method: 'POST',
        body: fd
    })
    .then(async (r) => {
        const texto = await r.text();
        let data;
        try {
            data = JSON.parse(texto);
        } catch (e) {
            console.error("Respuesta no JSON:", texto);
            throw new Error(texto.length > 200 ? texto.substring(0, 200) + '...' : texto);
        }
        return data;
    })
    .then(data => {
        if (data.status === 'success') {
            plantillaIdActual = data.plantilla_id;
            if (statusEl) {
                statusEl.innerHTML = '<i class="fa-solid fa-check" style="margin-right:4px;"></i>Guardado a las ' + new Date().toLocaleTimeString();
            }
            if (nuevoEstado === 'en_revision') {
                const b = document.getElementById('badgeEstado');
                if (b) {
                    b.style.background = '#fffbeb';
                    b.style.color = '#92400e';
                    b.innerText = 'EN_REVISION';
                }
            }
        } else {
            alert('Error al guardar: ' + (data.message || 'Verifique los datos'));
            if (statusEl) statusEl.innerHTML = '';
        }
    })
    .catch((err) => {
        console.error(err);
        alert('Error en la comunicación con el servidor: ' + (err.message || 'Fallo desconocido'));
        if (statusEl) statusEl.innerHTML = '';
    });
}

function aprobarPlantillaDefinitiva() {
    if (!plantillaIdActual) {
        alert('Primero guarde la plantilla para poder aprobarla.');
        return;
    }
    const motivo = prompt('Ingrese el motivo u observaciones de la aprobación técnica (ISO 17025):', 'Aprobación técnica de método oficial');
    if (!motivo) return;

    const fd = new FormData();
    fd.append('plantilla_id', plantillaIdActual);
    fd.append('nuevo_estado', 'aprobada');
    fd.append('motivo', motivo);

    fetch('/Cycsa/publico/plantillas/cambiar-estado', {
        method: 'POST',
        body: fd
    })
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            alert('Plantilla aprobada formalmente. Se ha generado una nueva versión inmutable con hash SHA-256.');
            window.location.reload();
        } else {
            alert('Error al aprobar: ' + data.message);
        }
    })
    .catch(() => alert('Error en el servidor.'));
}

function abrirPreviewEnVivo() {
    if (!plantillaIdActual) {
        alert('Guarde la plantilla primero para previsualizar el informe.');
        return;
    }
    const ori = document.getElementById('plt_orientacion')?.value || 'horizontal';
    const selloUrl = encodeURIComponent(document.getElementById('plt_sello_url')?.value || '');
    const incSello = document.getElementById('plt_incluir_sello')?.checked ? 1 : 0;

    const contenedor = document.getElementById('contenedorPreviewStudio');
    contenedor.innerHTML = '<div style="text-align:center; padding:60px 20px; color:#64748b;"><i class="fa-solid fa-spinner fa-spin" style="font-size:28px; color:#103487;"></i><div style="margin-top:10px;">Cargando vista previa...</div></div>';
    abrirModal('modalPreviewStudio');

    fetch(`/Cycsa/publico/plantillas/preview-modal?id=${plantillaIdActual}&orientacion=${ori}&incluir_sello=${incSello}&sello_url=${selloUrl}`)
    .then(r => r.json())
    .then(data => {
        if (data.status === 'success') {
            contenedor.innerHTML = data.html;
        } else {
            contenedor.innerHTML = `<div style="padding:30px; color:#dc2626; text-align:center;">${data.message}</div>`;
        }
    })
    .catch(() => {
        contenedor.innerHTML = '<div style="padding:30px; color:#dc2626; text-align:center;">Error al cargar la vista previa.</div>';
    });
}

function escapeHtml(str) {
    if (!str) return '';
    return String(str)
        .replace(/&/g, '&amp;')
        .replace(/</g, '&lt;')
        .replace(/>/g, '&gt;')
        .replace(/"/g, '&quot;')
        .replace(/'/g, '&#039;');
}
</script>
