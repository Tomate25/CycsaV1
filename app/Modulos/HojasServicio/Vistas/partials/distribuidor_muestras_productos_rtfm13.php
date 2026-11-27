<?php
/**
 * Partial reutilizable: Distribuidor Inteligente de Muestras por Producto para CYCSA-RT-FM-13
 * Se incluye tanto en OrdenesServicio/Vistas/index.php como en HojasServicio/Vistas/index.php.
 *
 * Permite:
 *  1. Generar/ajustar rápidamente N muestras (ej. 16 muestras) con códigos correlativos MC/MS.
 *  2. Indicar cuántas muestras van a cada producto habilitado de la O/S (con buscador + filtro ">0" + botón "Resto").
 *  3. Aplicar la distribución en 1 clic asignando cada muestra a su producto destino (m_detalle_cotizacion[] + m_ensayo_id[]).
 *  4. Seleccionar filas puntuales por checkbox en la tabla y reasignarlas en lote a cualquier producto.
 *  5. Ver en vivo el resumen por producto (conteo + rango de códigos de muestra).
 */
?>
<div id="rtfm13_distribuidor_wrapper" style="background:#f8fafc; border:1.5px solid #cbd5e1; border-radius:10px; padding:12px 14px; margin-bottom:14px;">
    <!-- Fila 1: Generación rápida de cantidad total + contadores vivos + botón expandir/ocultar -->
    <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <span style="font-size:12.5px; font-weight:700; color:#1F3864;">
                <i class="fa-solid fa-sliders me-1"></i> Distribución de Muestras por Producto
            </span>
            <span id="dist_pill_total" style="background:#e0e7ff; color:#1F3864; font-size:11px; font-weight:700; padding:3px 9px; border-radius:12px;">
                Total: 0
            </span>
            <span id="dist_pill_asignadas" style="background:#dcfce7; color:#166534; font-size:11px; font-weight:700; padding:3px 9px; border-radius:12px;">
                Asignadas: 0
            </span>
            <span id="dist_pill_pendientes" style="background:#fef3c7; color:#92400e; font-size:11px; font-weight:700; padding:3px 9px; border-radius:12px; display:none;">
                Sin asignar: 0
            </span>
        </div>

        <div style="display:flex; align-items:center; gap:8px; flex-wrap:wrap;">
            <div style="display:inline-flex; align-items:center; gap:5px; background:#ffffff; border:1px solid #cbd5e1; border-radius:7px; padding:3px 8px;">
                <label for="dist_total_muestras_input" style="font-size:11.5px; font-weight:600; color:#475569; margin:0; white-space:nowrap;">Cant. muestras:</label>
                <input type="number" id="dist_total_muestras_input" min="1" max="200" value="1" style="width:62px; padding:3px 6px; border:1px solid #cbd5e1; border-radius:5px; font-size:12px; font-weight:700; text-align:center; color:#0f172a;">
                <button type="button" onclick="generarCantidadMuestrasRTFM13()" style="background:#1F3864; color:#ffffff; border:none; border-radius:5px; padding:4px 10px; font-size:11.5px; font-weight:600; cursor:pointer; white-space:nowrap;" title="Crea o ajusta el total de muestras en la tabla">
                    <i class="fa-solid fa-layer-group me-1"></i> Generar
                </button>
            </div>
            <button type="button" id="btn_toggle_panel_distribuidor" onclick="togglePanelDistribuidorRTFM13()" style="background:#ffffff; color:#1F3864; border:1.5px solid #1F3864; border-radius:7px; padding:4px 11px; font-size:11.5px; font-weight:700; cursor:pointer; white-space:nowrap;">
                <i class="fa-solid fa-table-cells-large me-1"></i> Distribuir por Producto
            </button>
        </div>
    </div>

    <!-- Resumen compacto en vivo de qué muestras van a cada producto -->
    <div id="dist_resumen_pills" style="display:none; margin-top:8px; padding-top:8px; border-top:1px dashed #cbd5e1; flex-wrap:wrap; gap:6px; align-items:center;">
    </div>

    <!-- Panel interactivo de asignación por cantidades (ideal cuando hay muchos productos, ej. 20 ensayos mensuales) -->
    <div id="dist_panel_productos" style="display:none; margin-top:10px; padding-top:10px; border-top:1.5px solid #cbd5e1;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:8px; margin-bottom:8px;">
            <div style="position:relative; flex:1; min-width:220px;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:10px; top:50%; transform:translateY(-50%); color:#94a3b8; font-size:12px;"></i>
                <input type="text" id="dist_buscar_producto" placeholder="Buscar producto o norma ASTM (ej: C39, Proctor, Densímetro, Humedad)..." oninput="filtrarListaDistribuidorRTFM13()" autocomplete="off" style="width:100%; padding:6px 10px 6px 30px; border:1px solid #cbd5e1; border-radius:6px; font-size:12.5px; background:#ffffff;">
            </div>
            <div style="display:inline-flex; gap:6px; align-items:center; flex-wrap:wrap;">
                <button type="button" id="dist_filtro_todos" onclick="cambiarFiltroDistribuidorRTFM13('todos')" style="padding:4px 10px; border-radius:14px; font-size:11.5px; font-weight:700; border:1px solid #1F3864; background:#1F3864; color:#ffffff; cursor:pointer;">
                    Todos (<span id="dist_cnt_prod_todos">0</span>)
                </button>
                <button type="button" id="dist_filtro_activos" onclick="cambiarFiltroDistribuidorRTFM13('activos')" style="padding:4px 10px; border-radius:14px; font-size:11.5px; font-weight:700; border:1px solid #cbd5e1; background:#ffffff; color:#475569; cursor:pointer;">
                    Con muestras (<span id="dist_cnt_prod_activos">0</span>)
                </button>
            </div>
        </div>

        <div id="dist_lista_productos_grid" style="max-height:245px; overflow-y:auto; border:1px solid #e2e8f0; border-radius:8px; background:#ffffff; padding:6px;">
            <!-- Renderizado dinámicamente por JS -->
        </div>

        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px; margin-top:10px; padding-top:8px; border-top:1px solid #e2e8f0;">
            <div id="dist_estado_suma" style="font-size:12px; color:#334155; font-weight:600;">
                Suma asignada en el panel: <strong>0</strong> muestra(s)
            </div>
            <div style="display:inline-flex; gap:8px; flex-wrap:wrap;">
                <button type="button" onclick="limpiarCantidadesDistribuidorRTFM13()" style="background:#ffffff; color:#475569; border:1px solid #cbd5e1; border-radius:6px; padding:5px 12px; font-size:12px; font-weight:600; cursor:pointer;">
                    Limpiar cantidades
                </button>
                <button type="button" onclick="aplicarDistribucionProductosRTFM13()" style="background:#15803d; color:#ffffff; border:none; border-radius:6px; padding:6px 14px; font-size:12px; font-weight:700; cursor:pointer; box-shadow:0 2px 6px rgba(21,128,61,0.2);">
                    <i class="fa-solid fa-check-double me-1"></i> Aplicar distribución a las muestras
                </button>
            </div>
        </div>
    </div>

    <!-- Barra contextual de asignación en lote para muestras marcadas con checkbox en la tabla -->
    <div id="dist_barra_lote_seleccionadas" style="display:none; margin-top:10px; padding:8px 12px; background:#eff6ff; border:1.5px solid #3b82f6; border-radius:8px; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:8px;">
        <div style="font-size:12px; font-weight:700; color:#1e3a8a;">
            <i class="fa-solid fa-check-square me-1"></i> <span id="dist_cnt_sel_lote">0</span> muestra(s) seleccionada(s)
        </div>
        <div style="display:flex; align-items:center; gap:6px; flex-wrap:wrap; flex:1; justify-content:flex-end;">
            <select id="dist_select_producto_lote" style="max-width:340px; min-width:200px; padding:5px 8px; border:1px solid #93c5fd; border-radius:6px; font-size:12px; background:#ffffff; color:#0f172a;">
                <option value="">-- Seleccionar producto destino --</option>
            </select>
            <button type="button" onclick="asignarSeleccionadasAProductoRTFM13()" style="background:#1d4ed8; color:#ffffff; border:none; border-radius:6px; padding:5px 12px; font-size:12px; font-weight:700; cursor:pointer; white-space:nowrap;">
                Aplicar a seleccionadas
            </button>
            <button type="button" onclick="desmarcarTodasMuestrasRTFM13()" style="background:#ffffff; color:#475569; border:1px solid #cbd5e1; border-radius:6px; padding:5px 10px; font-size:11.5px; cursor:pointer;">
                Quitar selección
            </button>
        </div>
    </div>
</div>

<script>
(function () {
    window._distFiltroModo = 'todos';
    window._distCantidadesMap = {};

    function escDistHtml(str) {
        return String(str || '')
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    function normalizarTextoDist(str) {
        return String(str || '')
            .toLowerCase()
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .trim();
    }

    function obtenerProductosTecnicosRTFM13() {
        const lista = Array.isArray(window.productosPermitidosActuales) ? window.productosPermitidosActuales : [];
        const tecnicos = lista.filter(p => parseInt(p.id_producto || 0, 10) > 0);
        return tecnicos.length > 0 ? tecnicos : lista;
    }

    function obtenerFilasMuestrasRTFM13() {
        const tbody = document.getElementById('hs-tbody-muestras');
        if (!tbody) return [];
        return Array.from(tbody.querySelectorAll('tr')).filter(tr => tr.id !== 'fila-sin-muestras' && tr.querySelector('input[name="m_nombre[]"]'));
    }

    function etiquetaProductoDist(p) {
        const desc = String(p.descripcion_ensayo || p.nombre_comercial || 'Ensayo').trim();
        const cod = String(p.codigo_servicio || '').trim();
        const norma = String(p.norma_astm || '').trim();
        let extra = [];
        if (cod && cod.toUpperCase() !== 'N/A') extra.push(cod);
        if (norma && norma.toUpperCase() !== 'N/A' && !desc.toLowerCase().includes(norma.toLowerCase())) extra.push(norma);
        return { desc, sub: extra.join(' · ') };
    }

    window.inicializarDistribuidorProductosRTFM13 = function (abrirSiMultiples = true) {
        const prods = obtenerProductosTecnicosRTFM13();
        const wrapper = document.getElementById('rtfm13_distribuidor_wrapper');
        if (!wrapper) return;

        // Poblar dropdown de asignación por lote
        const selLote = document.getElementById('dist_select_producto_lote');
        if (selLote) {
            let htmlOpt = '<option value="">-- Seleccionar producto destino --</option>';
            prods.forEach(p => {
                const detId = parseInt(p.id_detalle_cotizacion || 0, 10);
                const prodId = parseInt(p.id_producto || 0, 10);
                const etq = etiquetaProductoDist(p);
                htmlOpt += `<option value="${detId}" data-prod-id="${prodId}">${escDistHtml(etq.desc + (etq.sub ? ' [' + etq.sub + ']' : ''))}</option>`;
            });
            selLote.innerHTML = htmlOpt;
        }

        // Inicializar mapa de cantidades leyendo lo que ya tienen las filas de la tabla
        window._distCantidadesMap = {};
        const filas = obtenerFilasMuestrasRTFM13();
        filas.forEach(tr => {
            const sel = tr.querySelector('select[name="m_detalle_cotizacion[]"]');
            const detId = sel ? parseInt(sel.value || '0', 10) : 0;
            if (detId > 0) {
                window._distCantidadesMap[detId] = (window._distCantidadesMap[detId] || 0) + 1;
            }
        });

        const inpTotal = document.getElementById('dist_total_muestras_input');
        if (inpTotal) {
            inpTotal.value = String(Math.max(1, filas.length));
        }

        renderizarListaDistribuidorRTFM13();
        sincronizarResumenDistribuidorRTFM13();

        // Si hay más de 1 producto habilitado (ej. servicio mensual con 20 ensayos), abrir automáticamente el distribuidor
        const panel = document.getElementById('dist_panel_productos');
        const btn = document.getElementById('btn_toggle_panel_distribuidor');
        if (panel && abrirSiMultiples) {
            const abrir = prods.length > 1;
            panel.style.display = abrir ? 'block' : 'none';
            if (btn) {
                btn.innerHTML = abrir
                    ? '<i class="fa-solid fa-chevron-up me-1"></i> Ocultar distribuidor'
                    : '<i class="fa-solid fa-table-cells-large me-1"></i> Distribuir por Producto';
            }
        }
    };

    window.togglePanelDistribuidorRTFM13 = function () {
        const panel = document.getElementById('dist_panel_productos');
        const btn = document.getElementById('btn_toggle_panel_distribuidor');
        if (!panel) return;
        const abrir = (panel.style.display === 'none' || panel.style.display === '');
        panel.style.display = abrir ? 'block' : 'none';
        if (btn) {
            btn.innerHTML = abrir
                ? '<i class="fa-solid fa-chevron-up me-1"></i> Ocultar distribuidor'
                : '<i class="fa-solid fa-table-cells-large me-1"></i> Distribuir por Producto';
        }
        if (abrir) {
            renderizarListaDistribuidorRTFM13();
        }
    };

    function renderizarListaDistribuidorRTFM13() {
        const cont = document.getElementById('dist_lista_productos_grid');
        if (!cont) return;
        const prods = obtenerProductosTecnicosRTFM13();

        const cntTodosEl = document.getElementById('dist_cnt_prod_todos');
        if (cntTodosEl) cntTodosEl.innerText = String(prods.length);

        if (prods.length === 0) {
            cont.innerHTML = '<div style="padding:14px; text-align:center; color:#64748b; font-size:12px;">No hay productos técnicos habilitados en esta O/S.</div>';
            actualizarEstadoSumaDistribuidor();
            return;
        }

        let html = '<div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(310px, 1fr)); gap:6px;">';
        prods.forEach(p => {
            const detId = parseInt(p.id_detalle_cotizacion || 0, 10);
            const prodId = parseInt(p.id_producto || 0, 10);
            const cant = parseInt(window._distCantidadesMap[detId] || 0, 10) || 0;
            const etq = etiquetaProductoDist(p);
            const searchTxt = normalizarTextoDist(etq.desc + ' ' + etq.sub);
            const activoStyle = cant > 0
                ? 'border:1.5px solid #16a34a; background:#f0fdf4;'
                : 'border:1px solid #e2e8f0; background:#ffffff;';

            html += `
                <div class="dist-prod-card" data-det-id="${detId}" data-prod-id="${prodId}" data-search="${escDistHtml(searchTxt)}"
                     style="${activoStyle} border-radius:7px; padding:7px 9px; display:flex; justify-content:space-between; align-items:center; gap:8px; transition:all 0.15s;">
                    <div style="min-width:0; flex:1;">
                        <div style="font-size:12px; font-weight:700; color:#0f172a; line-height:1.25; word-break:break-word;">${escDistHtml(etq.desc)}</div>
                        ${etq.sub ? `<div style="font-size:10.5px; color:#64748b; margin-top:2px;">${escDistHtml(etq.sub)}</div>` : ''}
                    </div>
                    <div style="display:inline-flex; align-items:center; gap:3px; flex-shrink:0;">
                        <button type="button" onclick="ajustarCantidadProductoDist(${detId}, -1)" style="width:24px; height:26px; border:1px solid #cbd5e1; background:#f8fafc; border-radius:5px; font-weight:700; cursor:pointer; color:#334155;">−</button>
                        <input type="number" min="0" max="200" value="${cant}" id="dist_qty_${detId}"
                               oninput="setCantidadProductoDist(${detId}, this.value)"
                               onfocus="if(this.value==='0') this.value='';"
                               onblur="if(this.value==='') { this.value='0'; setCantidadProductoDist(${detId}, 0); }"
                               style="width:44px; height:26px; text-align:center; border:1px solid #cbd5e1; border-radius:5px; font-size:12px; font-weight:700; color:#0f172a;">
                        <button type="button" onclick="ajustarCantidadProductoDist(${detId}, 1)" style="width:24px; height:26px; border:1px solid #cbd5e1; background:#f8fafc; border-radius:5px; font-weight:700; cursor:pointer; color:#334155;">+</button>
                        <button type="button" onclick="asignarRestoAProductoDist(${detId})" title="Asignar las muestras restantes a este producto" style="height:26px; padding:0 6px; border:1px solid #bae6fd; background:#f0f9ff; color:#0369a1; border-radius:5px; font-size:10.5px; font-weight:700; cursor:pointer;">Resto</button>
                    </div>
                </div>
            `;
        });
        html += '</div>';
        cont.innerHTML = html;

        filtrarListaDistribuidorRTFM13();
        actualizarEstadoSumaDistribuidor();
    }

    window.setCantidadProductoDist = function (detId, val) {
        const n = Math.max(0, parseInt(val || '0', 10) || 0);
        window._distCantidadesMap[detId] = n;
        const card = document.querySelector(`.dist-prod-card[data-det-id="${detId}"]`);
        if (card) {
            card.style.border = n > 0 ? '1.5px solid #16a34a' : '1px solid #e2e8f0';
            card.style.background = n > 0 ? '#f0fdf4' : '#ffffff';
        }
        actualizarEstadoSumaDistribuidor();
    };

    window.ajustarCantidadProductoDist = function (detId, delta) {
        const cur = parseInt(window._distCantidadesMap[detId] || 0, 10) || 0;
        const next = Math.max(0, cur + delta);
        const inp = document.getElementById('dist_qty_' + detId);
        if (inp) inp.value = String(next);
        setCantidadProductoDist(detId, next);
    };

    window.asignarRestoAProductoDist = function (detId) {
        const inpTot = document.getElementById('dist_total_muestras_input');
        const filasActuales = obtenerFilasMuestrasRTFM13().length;
        const objetivo = Math.max(filasActuales, parseInt((inpTot && inpTot.value) ? inpTot.value : '0', 10) || 0);
        let sumaOtros = 0;
        Object.keys(window._distCantidadesMap).forEach(k => {
            if (parseInt(k, 10) !== detId) {
                sumaOtros += (parseInt(window._distCantidadesMap[k] || 0, 10) || 0);
            }
        });
        const resto = Math.max(0, objetivo - sumaOtros);
        const inp = document.getElementById('dist_qty_' + detId);
        if (inp) inp.value = String(resto);
        setCantidadProductoDist(detId, resto);
    };

    window.cambiarFiltroDistribuidorRTFM13 = function (modo) {
        window._distFiltroModo = modo;
        const bAll = document.getElementById('dist_filtro_todos');
        const bAct = document.getElementById('dist_filtro_activos');
        if (bAll) {
            bAll.style.background = modo === 'todos' ? '#1F3864' : '#ffffff';
            bAll.style.color = modo === 'todos' ? '#ffffff' : '#475569';
            bAll.style.borderColor = modo === 'todos' ? '#1F3864' : '#cbd5e1';
        }
        if (bAct) {
            bAct.style.background = modo === 'activos' ? '#15803d' : '#ffffff';
            bAct.style.color = modo === 'activos' ? '#ffffff' : '#475569';
            bAct.style.borderColor = modo === 'activos' ? '#15803d' : '#cbd5e1';
        }
        filtrarListaDistribuidorRTFM13();
    };

    window.filtrarListaDistribuidorRTFM13 = function () {
        const qInp = document.getElementById('dist_buscar_producto');
        const q = normalizarTextoDist(qInp ? qInp.value : '');
        const tokens = q ? q.split(/\s+/).filter(Boolean) : [];
        document.querySelectorAll('.dist-prod-card').forEach(card => {
            const detId = parseInt(card.getAttribute('data-det-id') || '0', 10);
            const cant = parseInt(window._distCantidadesMap[detId] || 0, 10) || 0;
            const hay = card.getAttribute('data-search') || '';
            const matchQ = tokens.length === 0 || tokens.every(t => hay.includes(t));
            const matchModo = (window._distFiltroModo !== 'activos') || cant > 0;
            card.style.display = (matchQ && matchModo) ? 'flex' : 'none';
        });
    };

    function actualizarEstadoSumaDistribuidor() {
        let suma = 0;
        let prodsActivos = 0;
        Object.keys(window._distCantidadesMap).forEach(k => {
            const v = parseInt(window._distCantidadesMap[k] || 0, 10) || 0;
            if (v > 0) {
                suma += v;
                prodsActivos++;
            }
        });
        const cntActEl = document.getElementById('dist_cnt_prod_activos');
        if (cntActEl) cntActEl.innerText = String(prodsActivos);

        const filasActuales = obtenerFilasMuestrasRTFM13().length;
        const inpTot = document.getElementById('dist_total_muestras_input');
        const objetivo = Math.max(filasActuales, parseInt((inpTot && inpTot.value) ? inpTot.value : '0', 10) || 0);
        const estEl = document.getElementById('dist_estado_suma');
        if (estEl) {
            if (suma === 0) {
                estEl.innerHTML = `Indique cuántas muestras van a cada producto (muestras actuales en tabla: <strong>${filasActuales}</strong>).`;
            } else if (suma > filasActuales) {
                const faltan = suma - filasActuales;
                estEl.innerHTML = `Suma distribuida: <strong style="color:#15803d;">${suma} muestra(s)</strong> en ${prodsActivos} producto(s). <span style="color:#0369a1;">Al aplicar se crearán automáticamente ${faltan} muestra(s) nueva(s).</span>`;
            } else if (suma < objetivo) {
                estEl.innerHTML = `Suma distribuida: <strong style="color:#b45309;">${suma} de ${objetivo}</strong> muestra(s) (${objetivo - suma} quedarán sin asignar).`;
            } else {
                estEl.innerHTML = `Suma distribuida: <strong style="color:#15803d;">${suma} de ${filasActuales} muestra(s)</strong> listas en ${prodsActivos} producto(s).`;
            }
        }
    }

    window.limpiarCantidadesDistribuidorRTFM13 = function () {
        window._distCantidadesMap = {};
        document.querySelectorAll('.dist-prod-card').forEach(card => {
            const detId = parseInt(card.getAttribute('data-det-id') || '0', 10);
            const inp = document.getElementById('dist_qty_' + detId);
            if (inp) inp.value = '0';
            card.style.border = '1px solid #e2e8f0';
            card.style.background = '#ffffff';
        });
        actualizarEstadoSumaDistribuidor();
    };

    window.generarCantidadMuestrasRTFM13 = function () {
        const inp = document.getElementById('dist_total_muestras_input');
        const objetivo = Math.max(1, Math.min(200, parseInt((inp && inp.value) ? inp.value : '1', 10) || 1));
        if (inp) inp.value = String(objetivo);

        const filas = obtenerFilasMuestrasRTFM13();
        if (filas.length < objetivo) {
            const crear = objetivo - filas.length;
            for (let i = 0; i < crear; i++) {
                if (typeof agregarFilaMuestraModal === 'function') {
                    agregarFilaMuestraModal();
                }
            }
        } else if (filas.length > objetivo) {
            const sobrantes = filas.length - objetivo;
            let removidas = 0;
            for (let i = filas.length - 1; i >= 0 && removidas < sobrantes; i--) {
                const blq = filas[i].querySelector('input[name="m_bloqueado[]"]');
                if (blq && blq.value === '1') continue;
                filas[i].remove();
                removidas++;
            }
            if (typeof actualizarContadorEspecimenes === 'function') actualizarContadorEspecimenes();
        }
        sincronizarResumenDistribuidorRTFM13();
        actualizarEstadoSumaDistribuidor();
    };

    function asignarProductoAFilaTR(tr, detId, prodId) {
        const sel = tr.querySelector('select[name="m_detalle_cotizacion[]"]');
        if (sel && !sel.disabled) {
            sel.value = detId > 0 ? String(detId) : '';
            if (prodId <= 0 && sel.selectedIndex >= 0) {
                const opt = sel.options[sel.selectedIndex];
                prodId = opt ? parseInt(opt.getAttribute('data-prod-id') || '0', 10) : 0;
            }
        }
        const inpEns = tr.querySelector('input[name="m_ensayo_id[]"]');
        if (inpEns && prodId > 0) {
            inpEns.value = String(prodId);
        }
    }

    window.aplicarDistribucionProductosRTFM13 = function () {
        const prods = obtenerProductosTecnicosRTFM13();
        const plan = [];
        let sumaTotal = 0;
        prods.forEach(p => {
            const detId = parseInt(p.id_detalle_cotizacion || 0, 10);
            const prodId = parseInt(p.id_producto || 0, 10);
            const cant = parseInt(window._distCantidadesMap[detId] || 0, 10) || 0;
            if (detId > 0 && cant > 0) {
                for (let k = 0; k < cant; k++) {
                    plan.push({ detId, prodId });
                }
                sumaTotal += cant;
            }
        });

        if (sumaTotal <= 0) {
            alert('Indique al menos 1 muestra en alguno de los productos antes de aplicar la distribución.');
            return;
        }

        // Asegurar suficientes filas no bloqueadas en la tabla
        let filas = obtenerFilasMuestrasRTFM13();
        let editables = filas.filter(tr => {
            const blq = tr.querySelector('input[name="m_bloqueado[]"]');
            return !blq || blq.value !== '1';
        });

        if (editables.length < sumaTotal) {
            const faltan = sumaTotal - editables.length;
            for (let i = 0; i < faltan; i++) {
                if (typeof agregarFilaMuestraModal === 'function') {
                    agregarFilaMuestraModal();
                }
            }
            filas = obtenerFilasMuestrasRTFM13();
            editables = filas.filter(tr => {
                const blq = tr.querySelector('input[name="m_bloqueado[]"]');
                return !blq || blq.value !== '1';
            });
        }

        // Asignar en orden exacto según las cantidades definidas
        editables.forEach((tr, idx) => {
            if (idx < plan.length) {
                asignarProductoAFilaTR(tr, plan[idx].detId, plan[idx].prodId);
            } else {
                asignarProductoAFilaTR(tr, 0, 0);
            }
        });

        const inpTot = document.getElementById('dist_total_muestras_input');
        if (inpTot) inpTot.value = String(filas.length);

        sincronizarResumenDistribuidorRTFM13();
        actualizarEstadoSumaDistribuidor();
    };

    window.sincronizarResumenDistribuidorRTFM13 = function () {
        const filas = obtenerFilasMuestrasRTFM13();
        const total = filas.length;
        let asignadas = 0;
        const conteoPorDet = {};
        const codigosPorDet = {};
        const prods = obtenerProductosTecnicosRTFM13();
        const mapaProds = {};
        prods.forEach(p => {
            mapaProds[parseInt(p.id_detalle_cotizacion || 0, 10)] = p;
        });

        filas.forEach(tr => {
            const sel = tr.querySelector('select[name="m_detalle_cotizacion[]"]');
            const detId = sel ? parseInt(sel.value || '0', 10) : 0;
            const cod = (tr.querySelector('input[name="m_nombre[]"]')?.value || '').trim();
            if (detId > 0) {
                asignadas++;
                conteoPorDet[detId] = (conteoPorDet[detId] || 0) + 1;
                if (!codigosPorDet[detId]) codigosPorDet[detId] = [];
                if (cod) codigosPorDet[detId].push(cod);
            }
        });
        const pendientes = Math.max(0, total - asignadas);

        const pTot = document.getElementById('dist_pill_total');
        const pAsig = document.getElementById('dist_pill_asignadas');
        const pPend = document.getElementById('dist_pill_pendientes');
        if (pTot) pTot.innerText = `Total: ${total}`;
        if (pAsig) {
            pAsig.innerText = `Asignadas: ${asignadas}/${total}`;
            pAsig.style.background = (total > 0 && pendientes === 0) ? '#dcfce7' : '#e0f2fe';
            pAsig.style.color = (total > 0 && pendientes === 0) ? '#166534' : '#0369a1';
        }
        if (pPend) {
            pPend.style.display = pendientes > 0 ? 'inline-block' : 'none';
            pPend.innerText = `Sin asignar: ${pendientes}`;
        }

        const resumenEl = document.getElementById('dist_resumen_pills');
        if (resumenEl) {
            const keys = Object.keys(conteoPorDet);
            if (keys.length === 0) {
                resumenEl.style.display = 'none';
                resumenEl.innerHTML = '';
            } else {
                resumenEl.style.display = 'flex';
                let htmlRes = '<span style="font-size:11px; font-weight:700; color:#475569; margin-right:2px;">Resumen asignado:</span>';
                keys.forEach(k => {
                    const detId = parseInt(k, 10);
                    const p = mapaProds[detId];
                    const nom = p ? String(p.descripcion_ensayo || p.nombre_comercial || ('Producto #' + detId)) : ('Producto #' + detId);
                    const nomCorto = nom.length > 42 ? (nom.slice(0, 40) + '…') : nom;
                    const cods = codigosPorDet[detId] || [];
                    const rango = cods.length > 1 ? ` (${cods[0]} → ${cods[cods.length - 1]})` : (cods.length === 1 ? ` (${cods[0]})` : '');
                    htmlRes += `<span style="background:#ffffff; border:1px solid #86efac; color:#166534; font-size:11px; font-weight:600; padding:3px 9px; border-radius:12px;" title="${escDistHtml(nom + rango)}"><strong>${conteoPorDet[detId]}×</strong> ${escDistHtml(nomCorto)}${escDistHtml(rango)}</span>`;
                });
                resumenEl.innerHTML = htmlRes;
            }
        }

        // Mantener sincronizados los checkboxes de lote
        actualizarBarraSeleccionLoteRTFM13();
    };

    window.toggleSeleccionTodasMuestrasRTFM13 = function (masterChk) {
        const checked = !!(masterChk && masterChk.checked);
        document.querySelectorAll('#hs-tbody-muestras .chk-sel-muestra-rtfm13').forEach(c => {
            if (!c.disabled) c.checked = checked;
        });
        actualizarBarraSeleccionLoteRTFM13();
    };

    window.desmarcarTodasMuestrasRTFM13 = function () {
        const master = document.getElementById('chk_sel_todas_muestras_rtfm13');
        if (master) master.checked = false;
        document.querySelectorAll('#hs-tbody-muestras .chk-sel-muestra-rtfm13').forEach(c => {
            c.checked = false;
        });
        actualizarBarraSeleccionLoteRTFM13();
    };

    window.actualizarBarraSeleccionLoteRTFM13 = function () {
        const marcadas = document.querySelectorAll('#hs-tbody-muestras .chk-sel-muestra-rtfm13:checked');
        const barra = document.getElementById('dist_barra_lote_seleccionadas');
        const cntEl = document.getElementById('dist_cnt_sel_lote');
        if (cntEl) cntEl.innerText = String(marcadas.length);
        if (barra) barra.style.display = marcadas.length > 0 ? 'flex' : 'none';
    };

    window.asignarSeleccionadasAProductoRTFM13 = function () {
        const selLote = document.getElementById('dist_select_producto_lote');
        if (!selLote || !selLote.value) {
            alert('Seleccione un producto destino para asignar a las muestras marcadas.');
            return;
        }
        const detId = parseInt(selLote.value, 10) || 0;
        const opt = selLote.options[selLote.selectedIndex];
        const prodId = opt ? (parseInt(opt.getAttribute('data-prod-id') || '0', 10) || 0) : 0;

        document.querySelectorAll('#hs-tbody-muestras .chk-sel-muestra-rtfm13:checked').forEach(chk => {
            const tr = chk.closest('tr');
            if (tr) {
                asignarProductoAFilaTR(tr, detId, prodId);
                chk.checked = false;
            }
        });

        const master = document.getElementById('chk_sel_todas_muestras_rtfm13');
        if (master) master.checked = false;

        // Re-sincronizar mapa de cantidades con la tabla actual
        window._distCantidadesMap = {};
        obtenerFilasMuestrasRTFM13().forEach(tr => {
            const sel = tr.querySelector('select[name="m_detalle_cotizacion[]"]');
            const d = sel ? parseInt(sel.value || '0', 10) : 0;
            if (d > 0) window._distCantidadesMap[d] = (window._distCantidadesMap[d] || 0) + 1;
        });
        renderizarListaDistribuidorRTFM13();
        sincronizarResumenDistribuidorRTFM13();
    };
})();
</script>
