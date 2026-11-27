<?php
/**
 * Modal compartido para ampliar período de vigencia de la O/S y modificar/agregar productos
 * directamente desde la Hoja de Servicio (CYCSA-RT-FM-13).
 * Incluido tanto en OrdenesServicio/Vistas/index.php como en HojasServicio/Vistas/index.php.
 */
?>
<div id="modalAmpliarPeriodoProductosHS" style="display:none; position:fixed; inset:0; z-index:10030; background:rgba(15,23,42,0.65); backdrop-filter:blur(2px); align-items:center; justify-content:center; padding:18px; overflow-y:auto;">
    <div style="background:#ffffff; width:100%; max-width:920px; max-height:92vh; border-radius:12px; border:1px solid #cbd5e1; box-shadow:0 24px 60px rgba(15,23,42,0.3); display:flex; flex-direction:column; overflow:hidden;">
        <!-- Cabecera -->
        <div style="padding:14px 20px; background:#1F3864; color:#ffffff; display:flex; align-items:center; justify-content:space-between; gap:12px;">
            <div>
                <div style="font-size:15px; font-weight:700; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-calendar-plus"></i>
                    Ampliar Período y Modificar / Agregar Productos
                </div>
                <div style="font-size:12px; color:#dbeafe; margin-top:2px;">
                    Orden de Servicio <strong id="amp_hs_codigo_os">--</strong> · Los cambios se guardan en la O/S y se reflejan de inmediato en la Hoja de Servicio.
                </div>
            </div>
            <button type="button" onclick="cerrarModalAmpliarPeriodoProductosHS()" style="background:rgba(255,255,255,0.14); border:1px solid rgba(255,255,255,0.3); color:#ffffff; width:32px; height:32px; border-radius:8px; cursor:pointer; font-size:16px; line-height:1;">&times;</button>
        </div>

        <!-- Cuerpo con scroll -->
        <div style="padding:18px 20px; overflow-y:auto; flex:1; background:#f8fafc;">
            <div id="amp_hs_feedback" style="display:none; padding:10px 14px; border-radius:8px; margin-bottom:14px; font-size:13px; font-weight:600;"></div>

            <!-- SECCIÓN 1: AMPLIACIÓN DE PERÍODO -->
            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:14px 16px; margin-bottom:16px;">
                <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px; border-bottom:1px solid #f1f5f9; padding-bottom:8px; margin-bottom:12px;">
                    <div style="font-size:13.5px; font-weight:700; color:#1F3864;">
                        <i class="fa-solid fa-clock-rotate-left"></i> 1. Período de Vigencia de la O/S
                    </div>
                    <span id="amp_hs_estado_actual" style="font-size:11.5px; font-weight:700; padding:3px 9px; border-radius:6px; background:#eff6ff; color:#1d4ed8; border:1px solid #bfdbfe;">--</span>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(175px, 1fr)); gap:12px; align-items:end;">
                    <div>
                        <label style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">Agregar Meses (+)</label>
                        <div style="display:flex; gap:5px; align-items:center;">
                            <input type="number" id="amp_hs_meses_add" min="0" max="60" value="1" oninput="recalcularFechaFinAmpliacionHS()" style="width:76px; padding:7px 9px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; font-weight:700;">
                            <button type="button" onclick="aplicarPresetMesesAmpHS(1)" style="padding:5px 8px; border:1px solid #cbd5e1; background:#f1f5f9; border-radius:6px; font-size:11.5px; font-weight:700; cursor:pointer;">+1</button>
                            <button type="button" onclick="aplicarPresetMesesAmpHS(3)" style="padding:5px 8px; border:1px solid #cbd5e1; background:#f1f5f9; border-radius:6px; font-size:11.5px; font-weight:700; cursor:pointer;">+3</button>
                            <button type="button" onclick="aplicarPresetMesesAmpHS(6)" style="padding:5px 8px; border:1px solid #cbd5e1; background:#f1f5f9; border-radius:6px; font-size:11.5px; font-weight:700; cursor:pointer;">+6</button>
                        </div>
                    </div>
                    <div>
                        <label style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">Nueva Fecha de Finalización</label>
                        <input type="date" id="amp_hs_fecha_fin" style="width:100%; padding:7px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">Periodicidad</label>
                        <select id="amp_hs_periodicidad" style="width:100%; padding:7px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px; box-sizing:border-box;">
                            <option value="Mensual">Mensual</option>
                            <option value="Quincenal">Quincenal</option>
                            <option value="Bimestral">Bimestral</option>
                            <option value="Trimestral">Trimestral</option>
                            <option value="Semestral">Semestral</option>
                        </select>
                    </div>
                </div>
                <div style="margin-top:10px;">
                    <label style="display:block; font-size:11.5px; font-weight:700; color:#475569; margin-bottom:4px;">Motivo / Observación de la Ampliación (Opcional)</label>
                    <input type="text" id="amp_hs_motivo" placeholder="Ej: Extensión solicitada por el cliente para fase 2 de obra" style="width:100%; padding:7px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:12.5px; box-sizing:border-box;">
                </div>
            </div>

            <!-- SECCIÓN 2: MODIFICAR PRODUCTOS EXISTENTES -->
            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:14px 16px; margin-bottom:16px;">
                <div style="font-size:13.5px; font-weight:700; color:#1F3864; border-bottom:1px solid #f1f5f9; padding-bottom:8px; margin-bottom:10px;">
                    <i class="fa-solid fa-pen-to-square"></i> 2. Productos / Ensayos Actuales de la O/S (Modificar cantidades o descripción)
                </div>
                <div style="overflow-x:auto;">
                    <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
                        <thead>
                            <tr style="background:#f1f5f9; color:#334155; font-size:11px; text-transform:uppercase;">
                                <th style="padding:7px 8px; text-align:center; width:36px; border-bottom:1px solid #cbd5e1;">#</th>
                                <th style="padding:7px 8px; text-align:left; border-bottom:1px solid #cbd5e1;">Descripción del Producto / Ensayo</th>
                                <th style="padding:7px 8px; text-align:center; width:90px; border-bottom:1px solid #cbd5e1;">Unidad</th>
                                <th style="padding:7px 8px; text-align:center; width:100px; border-bottom:1px solid #cbd5e1;">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody id="amp_hs_tbody_existentes">
                            <!-- Filas inyectadas por JS -->
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- SECCIÓN 3: AGREGAR NUEVOS PRODUCTOS -->
            <div style="background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:14px 16px; margin-bottom:12px;">
                <div style="font-size:13.5px; font-weight:700; color:#1F3864; border-bottom:1px solid #f1f5f9; padding-bottom:8px; margin-bottom:10px;">
                    <i class="fa-solid fa-plus-circle"></i> 3. Agregar Nuevos Productos / Ensayos a la O/S
                </div>
                <div style="display:grid; grid-template-columns:1fr 2fr 95px auto; gap:8px; align-items:end; margin-bottom:10px;">
                    <div>
                        <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:4px;">Filtrar Catálogo</label>
                        <input type="text" id="amp_hs_filtro_cat" oninput="filtrarCatalogoAmpliacionHS(this.value)" placeholder="Buscar ensayo o norma..." style="width:100%; padding:7px 9px; border:1px solid #cbd5e1; border-radius:6px; font-size:12.5px; box-sizing:border-box;">
                    </div>
                    <div>
                        <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:4px;">Producto del Catálogo</label>
                        <select id="amp_hs_select_cat" style="width:100%; padding:7px 9px; border:1px solid #cbd5e1; border-radius:6px; font-size:12.5px; box-sizing:border-box;">
                            <option value="">-- Seleccionar producto --</option>
                        </select>
                    </div>
                    <div>
                        <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:4px;">Cantidad</label>
                        <input type="number" id="amp_hs_nueva_cant" min="1" step="1" value="1" style="width:100%; padding:7px 9px; border:1px solid #cbd5e1; border-radius:6px; font-size:12.5px; text-align:center; box-sizing:border-box;">
                    </div>
                    <div>
                        <button type="button" onclick="agregarProductoTemporalAmpHS()" style="padding:7px 14px; background:#1F3864; color:#ffffff; border:none; border-radius:6px; font-size:12.5px; font-weight:700; cursor:pointer; white-space:nowrap;">
                            <i class="fa-solid fa-plus"></i> Agregar
                        </button>
                    </div>
                </div>

                <div id="amp_hs_nuevos_wrap" style="display:none; margin-top:8px;">
                    <div style="font-size:11.5px; font-weight:700; color:#047857; margin-bottom:6px;">Productos nuevos por añadir al guardar:</div>
                    <table style="width:100%; border-collapse:collapse; font-size:12px; border:1px solid #bbf7d0; border-radius:6px;">
                        <thead>
                            <tr style="background:#f0fdf4; color:#065f46; font-size:11px; text-transform:uppercase;">
                                <th style="padding:6px 8px; text-align:left;">Producto / Ensayo</th>
                                <th style="padding:6px 8px; text-align:center; width:90px;">Unidad</th>
                                <th style="padding:6px 8px; text-align:center; width:85px;">Cant.</th>
                                <th style="padding:6px 8px; text-align:center; width:48px;"></th>
                            </tr>
                        </thead>
                        <tbody id="amp_hs_tbody_nuevos"></tbody>
                    </table>
                </div>
            </div>

            <!-- HISTORIAL BREVE -->
            <div id="amp_hs_historial_wrap" style="display:none; background:#ffffff; border:1px solid #e2e8f0; border-radius:10px; padding:12px 16px;">
                <div style="font-size:12px; font-weight:700; color:#475569; text-transform:uppercase; margin-bottom:6px;">
                    <i class="fa-solid fa-history"></i> Historial de Ampliaciones de esta O/S
                </div>
                <div id="amp_hs_historial_lista" style="font-size:12px; color:#334155; display:flex; flex-direction:column; gap:5px;"></div>
            </div>
        </div>

        <!-- Pie -->
        <div style="padding:12px 20px; background:#ffffff; border-top:1px solid #e2e8f0; display:flex; justify-content:flex-end; gap:10px;">
            <button type="button" onclick="cerrarModalAmpliarPeriodoProductosHS()" style="padding:8px 16px; background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; border-radius:7px; font-size:13px; font-weight:600; cursor:pointer;">
                Cancelar
            </button>
            <button type="button" id="amp_hs_btn_guardar" onclick="guardarAmpliacionPeriodoProductosHS()" style="padding:8px 18px; background:#1F3864; border:1px solid #1F3864; color:#ffffff; border-radius:7px; font-size:13px; font-weight:700; cursor:pointer;">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Ampliación y Productos
            </button>
        </div>
    </div>
</div>

<script>
window.ampHsState = {
    idOS: 0,
    codigoOS: '',
    periodoActual: null,
    detallesExistentes: [],
    catalogoProductos: [],
    ampliacionesHistorial: [],
    productosNuevos: []
};

function actualizarPanelPeriodoSidebarHS(data) {
    window.ampHsState.idOS = (data && data.os && data.os.id) ? parseInt(data.os.id, 10) : (typeof idOSModalActual !== 'undefined' ? idOSModalActual : 0);
    window.ampHsState.codigoOS = (data && data.os && data.os.codigo_os) ? data.os.codigo_os : (typeof codigoOSModalActual !== 'undefined' ? codigoOSModalActual : '');
    window.ampHsState.periodoActual = data ? (data.periodo_actual || null) : null;
    window.ampHsState.detallesExistentes = data ? (data.detalles_cotizacion_os || []) : [];
    window.ampHsState.catalogoProductos = data ? (data.catalogo_productos || []) : [];
    window.ampHsState.ampliacionesHistorial = data ? (data.ampliaciones_historial || []) : [];

    const elPeriodo = document.getElementById('ref_os_periodo_resumen');
    const elPeriodoFechas = document.getElementById('ref_os_periodo_fechas');
    if (!elPeriodo) return;

    const p = window.ampHsState.periodoActual || {};
    const vig = p.vigencia || {};
    if (p.es_multimes == 1 || vig.es_contrato_largo) {
        const durTxt = p.duracion_meses ? (p.duracion_meses + (parseInt(p.duracion_meses, 10) === 1 ? ' mes' : ' meses')) : 'Período activo';
        elPeriodo.innerText = (p.periodicidad || 'Mensual') + ' · ' + durTxt;
        elPeriodo.style.color = '#1F3864';
        if (elPeriodoFechas) {
            const ini = p.fecha_inicio || '--';
            const fin = p.fecha_fin || '--';
            let extra = '';
            if (typeof vig.dias_restantes === 'number') {
                extra = vig.dias_restantes < 0
                    ? ' (Vencido)'
                    : ' (' + vig.dias_restantes + ' días rest.)';
            }
            elPeriodoFechas.innerText = ini + ' → ' + fin + extra;
            elPeriodoFechas.style.display = 'block';
        }
    } else {
        elPeriodo.innerText = (p.tipo_contrato || 'Puntual') + ' (Sin período mensual)';
        elPeriodo.style.color = '#475569';
        if (elPeriodoFechas) {
            elPeriodoFechas.innerText = '';
            elPeriodoFechas.style.display = 'none';
        }
    }
}

function abrirModalAmpliarPeriodoProductosHS() {
    const st = window.ampHsState;
    if (!st.idOS) return;

    const modal = document.getElementById('modalAmpliarPeriodoProductosHS');
    if (!modal) return;

    document.getElementById('amp_hs_codigo_os').innerText = st.codigoOS || ('#' + st.idOS);
    const fb = document.getElementById('amp_hs_feedback');
    if (fb) { fb.style.display = 'none'; fb.innerText = ''; }

    const p = st.periodoActual || {};
    const estEl = document.getElementById('amp_hs_estado_actual');
    if (estEl) {
        if (p.es_multimes == 1) {
            estEl.innerText = 'Actual: ' + (p.periodicidad || 'Mensual') + (p.duracion_meses ? (' · ' + p.duracion_meses + ' meses') : '') + (p.fecha_fin ? (' (hasta ' + p.fecha_fin + ')') : '');
        } else {
            estEl.innerText = 'Actual: Puntual (al ampliar pasará a período mensual)';
        }
    }

    document.getElementById('amp_hs_meses_add').value = '1';
    document.getElementById('amp_hs_periodicidad').value = p.periodicidad || 'Mensual';
    document.getElementById('amp_hs_motivo').value = '';
    recalcularFechaFinAmpliacionHS();

    // Render productos existentes
    const tbodyEx = document.getElementById('amp_hs_tbody_existentes');
    tbodyEx.innerHTML = '';
    if (st.detallesExistentes.length > 0) {
        st.detallesExistentes.forEach((d, idx) => {
            const tr = document.createElement('tr');
            tr.setAttribute('data-id-detalle', d.id);
            tr.innerHTML = `
                <td style="padding:6px 8px; text-align:center; border-bottom:1px solid #f1f5f9; font-weight:700; color:#64748b;">${idx + 1}</td>
                <td style="padding:6px 8px; border-bottom:1px solid #f1f5f9;">
                    <input type="text" class="amp-ex-desc" value="${escapeHtmlAmpHS(d.descripcion_ensayo || '')}" style="width:100%; padding:5px 8px; border:1px solid #cbd5e1; border-radius:5px; font-size:12.5px; box-sizing:border-box;">
                </td>
                <td style="padding:6px 8px; text-align:center; border-bottom:1px solid #f1f5f9; color:#64748b;">${escapeHtmlAmpHS(d.unidad_medida || 'Unidad')}</td>
                <td style="padding:6px 8px; text-align:center; border-bottom:1px solid #f1f5f9;">
                    <input type="number" class="amp-ex-cant" min="0.01" step="any" value="${parseFloat(d.cantidad || 1)}" style="width:82px; padding:5px 7px; border:1px solid #cbd5e1; border-radius:5px; font-size:12.5px; text-align:center; font-weight:700;">
                </td>
            `;
            tbodyEx.appendChild(tr);
        });
    } else {
        tbodyEx.innerHTML = '<tr><td colspan="4" style="padding:12px; text-align:center; color:#94a3b8;">No hay productos registrados en la cotización de esta O/S.</td></tr>';
    }

    // Reset nuevos productos y poblar catálogo
    st.productosNuevos = [];
    renderProductosNuevosAmpHS();
    document.getElementById('amp_hs_filtro_cat').value = '';
    filtrarCatalogoAmpliacionHS('');

    // Render historial de ampliaciones
    const histWrap = document.getElementById('amp_hs_historial_wrap');
    const histList = document.getElementById('amp_hs_historial_lista');
    if (st.ampliacionesHistorial && st.ampliacionesHistorial.length > 0) {
        histWrap.style.display = 'block';
        histList.innerHTML = st.ampliacionesHistorial.map(h => {
            const fec = h.created_at ? String(h.created_at).substring(0, 16) : '';
            const finTxt = h.fecha_fin_nueva ? ('Fin: ' + h.fecha_fin_nueva) : '';
            const motTxt = h.motivo ? (' · ' + escapeHtmlAmpHS(h.motivo)) : '';
            return `<div style="padding:5px 8px; background:#f8fafc; border:1px solid #e2e8f0; border-radius:6px;"><strong>${escapeHtmlAmpHS(fec)}</strong> (${escapeHtmlAmpHS(h.usuario_nombre || 'Sistema')}) — ${escapeHtmlAmpHS(finTxt)}${motTxt}</div>`;
        }).join('');
    } else {
        histWrap.style.display = 'none';
        histList.innerHTML = '';
    }

    modal.style.display = 'flex';
}

function cerrarModalAmpliarPeriodoProductosHS() {
    const modal = document.getElementById('modalAmpliarPeriodoProductosHS');
    if (modal) modal.style.display = 'none';
}

function escapeHtmlAmpHS(str) {
    return String(str || '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;').replace(/"/g, '&quot;');
}

function aplicarPresetMesesAmpHS(m) {
    const el = document.getElementById('amp_hs_meses_add');
    if (el) {
        el.value = String(m);
        recalcularFechaFinAmpliacionHS();
    }
}

function recalcularFechaFinAmpliacionHS() {
    const p = window.ampHsState.periodoActual || {};
    const meses = Math.max(0, parseInt(document.getElementById('amp_hs_meses_add')?.value || '0', 10) || 0);
    const baseStr = p.fecha_fin || p.fecha_inicio || new Date().toISOString().slice(0, 10);
    const dt = new Date(baseStr + 'T00:00:00');
    if (!isNaN(dt.getTime())) {
        if (meses > 0) {
            dt.setMonth(dt.getMonth() + meses);
        }
        const ffEl = document.getElementById('amp_hs_fecha_fin');
        if (ffEl) ffEl.value = dt.toISOString().slice(0, 10);
    }
}

function filtrarCatalogoAmpliacionHS(q) {
    const sel = document.getElementById('amp_hs_select_cat');
    if (!sel) return;
    const term = String(q || '').toLowerCase().trim();
    const lista = window.ampHsState.catalogoProductos || [];
    sel.innerHTML = '<option value="">-- Seleccionar producto del catálogo --</option>';
    lista.forEach(p => {
        const nom = (p.nombre_comercial || p.ensayo_servicio || '').trim();
        const cod = (p.codigo_servicio || '').trim();
        const norma = (p.norma_astm || '').trim();
        const texto = (nom + (cod ? ' [' + cod + ']' : '') + (norma ? ' · ' + norma : '')).trim();
        if (term === '' || texto.toLowerCase().includes(term)) {
            const opt = document.createElement('option');
            opt.value = p.id;
            opt.textContent = texto + (p.unidad_medida ? (' (' + p.unidad_medida + ')') : '');
            sel.appendChild(opt);
        }
    });
}

function agregarProductoTemporalAmpHS() {
    const sel = document.getElementById('amp_hs_select_cat');
    const cantEl = document.getElementById('amp_hs_nueva_cant');
    const idProd = parseInt(sel?.value || '0', 10);
    const cant = Math.max(0.01, parseFloat(cantEl?.value || '1') || 1);
    if (idProd <= 0) {
        alert('Seleccione un producto del catálogo para agregar.');
        return;
    }
    const prod = (window.ampHsState.catalogoProductos || []).find(p => parseInt(p.id, 10) === idProd);
    if (!prod) return;
    window.ampHsState.productosNuevos.push({
        id_producto: idProd,
        descripcion_ensayo: (prod.nombre_comercial || prod.ensayo_servicio || 'Ensayo').trim(),
        unidad_medida: prod.unidad_medida || 'Unidad',
        precio_unitario: parseFloat(prod.precio || 0),
        cantidad: cant
    });
    if (sel) sel.value = '';
    if (cantEl) cantEl.value = '1';
    renderProductosNuevosAmpHS();
}

function quitarProductoTemporalAmpHS(idx) {
    window.ampHsState.productosNuevos.splice(idx, 1);
    renderProductosNuevosAmpHS();
}

function renderProductosNuevosAmpHS() {
    const wrap = document.getElementById('amp_hs_nuevos_wrap');
    const tbody = document.getElementById('amp_hs_tbody_nuevos');
    const arr = window.ampHsState.productosNuevos || [];
    if (!wrap || !tbody) return;
    if (arr.length === 0) {
        wrap.style.display = 'none';
        tbody.innerHTML = '';
        return;
    }
    wrap.style.display = 'block';
    tbody.innerHTML = arr.map((p, idx) => `
        <tr>
            <td style="padding:6px 8px; border-top:1px solid #dcfce7; font-weight:600; color:#0f172a;">${escapeHtmlAmpHS(p.descripcion_ensayo)}</td>
            <td style="padding:6px 8px; border-top:1px solid #dcfce7; text-align:center; color:#475569;">${escapeHtmlAmpHS(p.unidad_medida)}</td>
            <td style="padding:6px 8px; border-top:1px solid #dcfce7; text-align:center; font-weight:700;">${p.cantidad}</td>
            <td style="padding:6px 8px; border-top:1px solid #dcfce7; text-align:center;">
                <button type="button" onclick="quitarProductoTemporalAmpHS(${idx})" style="background:#fee2e2; color:#991b1b; border:none; border-radius:4px; padding:2px 7px; cursor:pointer; font-weight:700;">&times;</button>
            </td>
        </tr>
    `).join('');
}

function guardarAmpliacionPeriodoProductosHS() {
    const st = window.ampHsState;
    const btn = document.getElementById('amp_hs_btn_guardar');
    const fb = document.getElementById('amp_hs_feedback');

    const productosModificar = [];
    document.querySelectorAll('#amp_hs_tbody_existentes tr[data-id-detalle]').forEach(tr => {
        const idDet = parseInt(tr.getAttribute('data-id-detalle') || '0', 10);
        const desc = tr.querySelector('.amp-ex-desc')?.value?.trim() || '';
        const cant = parseFloat(tr.querySelector('.amp-ex-cant')?.value || '1');
        if (idDet > 0 && !isNaN(cant) && cant > 0) {
            productosModificar.push({ id_detalle: idDet, descripcion_ensayo: desc, cantidad: cant });
        }
    });

    const csrfInput = document.querySelector('#form-hoja-solicitud input[name="csrf_token"]') || document.querySelector('input[name="csrf_token"]');
    const csrfToken = csrfInput ? csrfInput.value : '';
    const idHojaActual = parseInt(document.getElementById('hs_id_hoja')?.value || '0', 10) || null;

    const payload = {
        csrf_token: csrfToken,
        id_os: st.idOS,
        id_hoja: idHojaActual,
        meses_adicionales: Math.max(0, parseInt(document.getElementById('amp_hs_meses_add')?.value || '0', 10) || 0),
        fecha_fin: document.getElementById('amp_hs_fecha_fin')?.value || '',
        periodicidad: document.getElementById('amp_hs_periodicidad')?.value || 'Mensual',
        motivo: document.getElementById('amp_hs_motivo')?.value?.trim() || '',
        productos_modificar: productosModificar,
        productos_agregar: st.productosNuevos
    };

    if (btn) { btn.disabled = true; btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Guardando...'; }
    if (fb) { fb.style.display = 'none'; }

    fetch('/Cycsa/publico/hojas-servicio/ampliar-periodo-productos', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken
        },
        body: JSON.stringify(payload)
    })
    .then(r => r.json())
    .then(res => {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Ampliación y Productos'; }
        if (res.status === 'success') {
            cerrarModalAmpliarPeriodoProductosHS();
            // Recargar los datos de la Hoja de Servicio abierta para reflejar el nuevo período y productos
            if (typeof abrirModalHojaSolicitud === 'function') {
                abrirModalHojaSolicitud(st.idOS, st.codigoOS, idHojaActual || 0, false);
            }
            if (typeof Swal !== 'undefined') {
                Swal.fire({ icon: 'success', title: 'Ampliación aplicada', text: res.message || 'Período y productos actualizados.', timer: 2200, showConfirmButton: false });
            }
        } else {
            if (fb) {
                fb.style.display = 'block';
                fb.style.background = '#fef2f2';
                fb.style.border = '1px solid #fecaca';
                fb.style.color = '#991b1b';
                fb.innerText = res.message || 'No se pudo guardar la ampliación.';
            }
        }
    })
    .catch(err => {
        if (btn) { btn.disabled = false; btn.innerHTML = '<i class="fa-solid fa-floppy-disk"></i> Guardar Ampliación y Productos'; }
        if (fb) {
            fb.style.display = 'block';
            fb.style.background = '#fef2f2';
            fb.style.border = '1px solid #fecaca';
            fb.style.color = '#991b1b';
            fb.innerText = 'Error de conexión al guardar la ampliación.';
        }
    });
}
</script>
