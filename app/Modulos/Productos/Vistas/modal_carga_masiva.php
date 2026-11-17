<style>
    #modalCargaMasivaProductos {
        display: none;
        position: fixed;
        inset: 0;
        z-index: 10050;
        padding: 28px;
        background: rgba(15, 23, 42, .65);
        align-items: center;
        justify-content: center;
        opacity: 1 !important;
        overflow-y: auto;
    }
    #modalCargaMasivaProductos.activo {
        display: flex !important;
        opacity: 1 !important;
        pointer-events: auto !important;
    }
    #modalCargaMasivaProductos .cm-dialog {
        width: min(1180px, 100%);
        max-height: 92vh;
        margin: auto;
        transform: none !important;
    }
    #modalCargaMasivaProductos .cm-content {
        max-height: 92vh;
        display: flex;
        flex-direction: column;
        overflow: hidden;
        background: #fff;
        border-radius: 12px;
        box-shadow: 0 24px 70px rgba(15, 23, 42, .35);
    }
    #modalCargaMasivaProductos .cm-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        gap: 15px;
        padding: 16px 20px;
        background: #103487;
        color: #fff;
    }
    #modalCargaMasivaProductos .cm-title { margin: 0; font-size: 17px; font-weight: 700; display: flex; align-items: center; gap: 10px; }
    #modalCargaMasivaProductos .cm-close { border: 0; background: transparent; color: #fff; font-size: 24px; line-height: 1; cursor: pointer; padding: 0 6px; }
    #modalCargaMasivaProductos .cm-body { padding: 20px; overflow: auto; }
    #modalCargaMasivaProductos .cm-footer { display: flex; justify-content: flex-end; gap: 9px; padding: 12px 20px; background: #f8fafc; border-top: 1px solid #e2e8f0; }
    #modalCargaMasivaProductos .cm-grid-2 { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 14px; margin-bottom: 18px; }
    #modalCargaMasivaProductos .cm-kpis { display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; margin-bottom: 14px; text-align: center; }
    #modalCargaMasivaProductos .cm-card { padding: 16px; border: 1px solid #e2e8f0; border-radius: 9px; background: #f8fafc; display: flex; flex-direction: column; justify-content: space-between; gap: 10px; }
    #modalCargaMasivaProductos .cm-kpi-card { padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; box-shadow: 0 1px 3px rgba(15,23,42,.04); }
    #modalCargaMasivaProductos .cm-btn { display: inline-flex; align-items: center; justify-content: center; gap: 6px; border: 1px solid transparent; border-radius: 6px; padding: 8px 13px; cursor: pointer; text-decoration: none; font-size: 12.5px; font-weight: 700; transition: all .15s ease; }
    #modalCargaMasivaProductos .cm-btn-primary { background: #103487; color: #fff; }
    #modalCargaMasivaProductos .cm-btn-primary:hover { background: #1e40af; color: #fff; }
    #modalCargaMasivaProductos .cm-btn-success { background: #047857; color: #fff; }
    #modalCargaMasivaProductos .cm-btn-success:hover { background: #065f46; color: #fff; }
    #modalCargaMasivaProductos .cm-btn-secondary { background: #64748b; color: #fff; }
    #modalCargaMasivaProductos .cm-btn-outline { border-color: #cbd5e1; color: #334155; background: #fff; }
    #modalCargaMasivaProductos .cm-btn-outline:hover { background: #f1f5f9; color: #0f172a; }
    #modalCargaMasivaProductos .cm-btn:disabled { opacity: .55; cursor: not-allowed; }
    #modalCargaMasivaProductos .table-responsive { overflow: auto; max-height: 380px; border: 1px solid #e2e8f0; border-radius: 8px; background: #fff; }
    #modalCargaMasivaProductos table { width: 100%; border-collapse: collapse; }
    #modalCargaMasivaProductos th, #modalCargaMasivaProductos td { padding: 8px; border-bottom: 1px solid #e2e8f0; text-align: left; vertical-align: top; }
    #modalCargaMasivaProductos th { position: sticky; top: 0; background: #1e293b; color: #fff; z-index: 2; }
    @media (max-width: 820px) {
        #modalCargaMasivaProductos { padding: 10px; }
        #modalCargaMasivaProductos .cm-grid-2 { grid-template-columns: 1fr; }
        #modalCargaMasivaProductos .cm-kpis { grid-template-columns: repeat(2, 1fr); }
    }
</style>

<!-- Modal de Carga Masiva de Productos / Ensayos -->
<div id="modalCargaMasivaProductos" role="dialog" aria-modal="true" aria-labelledby="modalCargaMasivaLabel" aria-hidden="true" onclick="cerrarModalCargaMasiva(event)">
    <div class="cm-dialog" onclick="event.stopPropagation()">
        <div class="cm-content">
            <div class="cm-header">
                <h5 class="cm-title" id="modalCargaMasivaLabel">
                    <i class="fas fa-file-excel text-warning"></i>
                    <span>Carga Masiva y Actualización de Productos (Excel .xlsx / CSV)</span>
                </h5>
                <button type="button" class="cm-close" onclick="cerrarModalCargaMasiva()" aria-label="Cerrar">&times;</button>
            </div>
            <div class="cm-body">
                <!-- Paso 1 y 2 -->
                <div class="cm-grid-2">
                    <div class="cm-card">
                        <div>
                            <h6 class="fw-bold text-dark d-flex align-items-center gap-2 mb-2">
                                <i class="fas fa-download text-primary"></i> 1. Catálogo Oficial con Identificadores
                            </h6>
                            <p class="text-muted small mb-0">
                                Descargue todos los productos actuales con sus IDs y <code>Codigo_AGN</code> para editar precios y normas en Excel, o descargue la plantilla de ejemplo.
                            </p>
                        </div>
                        <div class="d-flex flex-column gap-2">
                            <a href="/Cycsa/publico/productos/exportar-catalogo-csv" class="cm-btn cm-btn-primary w-100" title="Abre directamente ordenado en columnas en Excel">
                                <i class="fas fa-file-excel"></i> Exportar Catálogo Completo con IDs (.csv)
                            </a>
                            <a href="/Cycsa/publico/productos/descargar-plantilla" class="cm-btn cm-btn-outline w-100" title="Descargar plantilla de ejemplo">
                                <i class="fas fa-file-csv"></i> Descargar Plantilla Vacía (.csv)
                            </a>
                        </div>
                    </div>

                    <div class="cm-card">
                        <div>
                            <h6 class="fw-bold text-dark d-flex align-items-center gap-2 mb-2">
                                <i class="fas fa-upload text-success"></i> 2. Subir Archivo Excel (.xlsx) o CSV
                            </h6>
                            <p class="text-muted small mb-2">
                                Seleccione un archivo <code>.xlsx</code> (ej. Lista de Precios CYCSA) o <code>.csv</code>. El sistema cotejará por <code>ID</code>, <code>Codigo_AGN</code>, <code>No_Item</code> y <code>Nombre Comercial</code>.
                            </p>
                            <input type="file" id="inputArchivoCsvProductos" class="form-control form-control-sm" accept=".csv,.xlsx,text/csv,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet">
                        </div>
                        <div>
                            <button type="button" id="btnPrevisualizarCsv" class="cm-btn cm-btn-success w-100" disabled>
                                <i class="fas fa-search"></i> Previsualizar y Validar
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Spinner de Carga -->
                <div id="spinnerCargaMasiva" class="text-center py-4 d-none">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <p class="text-muted fw-bold mb-0">Analizando archivo y validando cambios...</p>
                </div>

                <!-- Resumen de Validación -->
                <div id="contenedorResumenCarga" class="d-none mb-3">
                    <div id="bannerHojaOrigen" class="small text-muted mb-2 d-none"></div>
                    <div class="cm-kpis">
                        <div class="cm-kpi-card">
                            <div class="text-muted small text-uppercase fw-bold">Total Filas</div>
                            <div id="resumenTotalFilas" class="h4 mb-0 fw-bold text-dark">0</div>
                        </div>
                        <div class="cm-kpi-card">
                            <div class="text-muted small text-uppercase fw-bold text-primary">Modificados</div>
                            <div id="resumenActualizaciones" class="h4 mb-0 fw-bold text-primary">0</div>
                        </div>
                        <div class="cm-kpi-card">
                            <div class="text-muted small text-uppercase fw-bold text-secondary">Sin Cambios</div>
                            <div id="resumenSinCambios" class="h4 mb-0 fw-bold text-secondary">0</div>
                        </div>
                        <div class="cm-kpi-card">
                            <div class="text-muted small text-uppercase fw-bold text-success">Nuevos</div>
                            <div id="resumenNuevos" class="h4 mb-0 fw-bold text-success">0</div>
                        </div>
                        <div class="cm-kpi-card">
                            <div class="text-muted small text-uppercase fw-bold text-danger">Errores</div>
                            <div id="resumenErrores" class="h4 mb-0 fw-bold text-danger">0</div>
                        </div>
                    </div>

                    <!-- Tabla de Previsualización -->
                    <div class="table-responsive">
                        <table class="table table-hover table-sm align-middle mb-0 font-monospace" style="font-size: 0.84rem;">
                            <thead>
                                <tr>
                                    <th style="width: 55px;">Fila</th>
                                    <th style="width: 110px;">Acción</th>
                                    <th style="width: 130px;">Ítem / AGN</th>
                                    <th>Nombre Comercial / Ensayo</th>
                                    <th style="width: 110px;">Matriz</th>
                                    <th style="width: 130px;">Precio</th>
                                    <th style="width: 110px;">Estatus</th>
                                    <th>Cambios / Detalle</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPrevisualizacionCarga"></tbody>
                        </table>
                    </div>
                </div>

                <!-- Mensaje de Resultado Final -->
                <div id="alertaResultadoCarga" class="alert d-none mt-3 mb-0" role="alert"></div>
            </div>
            <div class="cm-footer">
                <button type="button" class="cm-btn cm-btn-secondary" onclick="cerrarModalCargaMasiva()">Cerrar</button>
                <button type="button" id="btnConfirmarCargaMasiva" class="cm-btn cm-btn-primary d-none">
                    <i class="fas fa-check-circle"></i> Confirmar y Aplicar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function abrirModalCargaMasiva() {
    const modal = document.getElementById('modalCargaMasivaProductos');
    if (!modal) return;
    modal.classList.add('activo');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function cerrarModalCargaMasiva(event) {
    const modal = document.getElementById('modalCargaMasivaProductos');
    if (!modal) return;
    if (event && event.target !== modal) return;
    modal.classList.remove('activo');
    modal.setAttribute('aria-hidden', 'true');
    document.body.style.overflow = '';
}

document.addEventListener('DOMContentLoaded', function() {
    const inputCsv = document.getElementById('inputArchivoCsvProductos');
    const btnPrevisualizar = document.getElementById('btnPrevisualizarCsv');
    const btnConfirmar = document.getElementById('btnConfirmarCargaMasiva');
    const spinner = document.getElementById('spinnerCargaMasiva');
    const contenedorResumen = document.getElementById('contenedorResumenCarga');
    const bannerHoja = document.getElementById('bannerHojaOrigen');
    const tbody = document.getElementById('tbodyPrevisualizacionCarga');
    const alertaResultado = document.getElementById('alertaResultadoCarga');

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') cerrarModalCargaMasiva();
    });

    let tokenLoteActual = null;
    let datosCargaActual = null;
    const escapeHtml = value => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    const parseJsonSafe = async (res) => {
        const raw = await res.text();
        try {
            return JSON.parse(raw);
        } catch (e) {
            throw new Error(raw ? raw.replace(/<[^>]+>/g, ' ').trim().slice(0, 200) : `HTTP ${res.status}`);
        }
    };

    if (inputCsv) {
        inputCsv.addEventListener('change', function() {
            btnPrevisualizar.disabled = !this.files || this.files.length === 0;
            contenedorResumen.classList.add('d-none');
            btnConfirmar.classList.add('d-none');
            alertaResultado.classList.add('d-none');
            tokenLoteActual = null;
            datosCargaActual = null;
        });
    }

    if (btnPrevisualizar) {
        btnPrevisualizar.addEventListener('click', function() {
            if (!inputCsv.files || inputCsv.files.length === 0) return;

            const archivo = inputCsv.files[0];
            const formData = new FormData();
            formData.append('archivo_csv', archivo);
            formData.append('csrf_token', '<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>');

            spinner.classList.remove('d-none');
            contenedorResumen.classList.add('d-none');
            btnConfirmar.classList.add('d-none');
            alertaResultado.classList.add('d-none');
            btnPrevisualizar.disabled = true;

            fetch('/Cycsa/publico/productos/previsualizar-carga', {
                method: 'POST',
                body: formData
            })
            .then(parseJsonSafe)
            .then(data => {
                spinner.classList.add('d-none');
                btnPrevisualizar.disabled = false;

                if (!data.exito) {
                    alertaResultado.className = 'alert alert-danger mt-3 mb-0';
                    alertaResultado.textContent = data.error || 'Ocurrió un error al procesar el archivo.';
                    alertaResultado.classList.remove('d-none');
                    return;
                }

                tokenLoteActual = data.token_lote || null;
                datosCargaActual = data;

                if (bannerHoja) {
                    if (data.hoja_origen) {
                        bannerHoja.innerHTML = `<i class="fas fa-table me-1 text-primary"></i> Hoja detectada: <strong>${escapeHtml(data.hoja_origen)}</strong> · Archivo: <strong>${escapeHtml(data.archivo || archivo.name)}</strong>`;
                        bannerHoja.classList.remove('d-none');
                    } else {
                        bannerHoja.classList.add('d-none');
                    }
                }

                const modificados = data.modificados ?? data.actualizaciones ?? 0;
                const sinCambios = data.sin_cambios ?? 0;
                const nuevos = data.nuevos ?? 0;
                const errores = data.errores ?? 0;
                const total = data.total_filas ?? 0;

                document.getElementById('resumenTotalFilas').textContent = total;
                document.getElementById('resumenActualizaciones').textContent = `${modificados} de ${total}`;
                document.getElementById('resumenSinCambios').textContent = sinCambios;
                document.getElementById('resumenNuevos').textContent = nuevos;
                document.getElementById('resumenErrores').textContent = errores;

                tbody.innerHTML = '';
                data.filas.forEach(f => {
                    const tr = document.createElement('tr');
                    if (!f.es_valida) {
                        tr.className = 'table-danger';
                    }

                    let badgeAccion = '';
                    if (!f.es_valida) {
                        badgeAccion = '<span class="badge bg-danger"><i class="fas fa-times me-1"></i> Error</span>';
                    } else if (f.accion === 'crear') {
                        badgeAccion = '<span class="badge bg-success"><i class="fas fa-plus me-1"></i> Nuevo</span>';
                    } else if (f.accion === 'sin_cambios') {
                        badgeAccion = '<span class="badge bg-secondary"><i class="fas fa-check me-1"></i> Sin cambios</span>';
                    } else {
                        badgeAccion = '<span class="badge bg-primary"><i class="fas fa-edit me-1"></i> Modificado</span>';
                    }

                    let precioHtml = `C$ ${parseFloat(f.datos.precio || 0).toFixed(2)}`;
                    if (f.diferencias && f.diferencias.precio) {
                        precioHtml = `<span class="text-decoration-line-through text-muted small">C$ ${parseFloat(f.diferencias.precio.anterior).toFixed(2)}</span><br><strong class="text-success">C$ ${parseFloat(f.diferencias.precio.nuevo).toFixed(2)}</strong>`;
                    }

                    let estadoDetalle = '';
                    if (f.errores && f.errores.length > 0) {
                        estadoDetalle = `<span class="text-danger fw-bold">${f.errores.map(escapeHtml).join('<br>')}</span>`;
                    } else if (f.accion === 'actualizar') {
                        const listaCambios = [];
                        if (f.diferencias) {
                            for (const [campo, dif] of Object.entries(f.diferencias)) {
                                if (campo === 'precio') {
                                    listaCambios.push(`Precio: C$ ${parseFloat(dif.anterior).toFixed(2)} ➔ C$ ${parseFloat(dif.nuevo).toFixed(2)}`);
                                } else {
                                    const etiqueta = dif.etiqueta || campo;
                                    listaCambios.push(`${escapeHtml(etiqueta)}: "${escapeHtml(dif.anterior)}" ➔ "${escapeHtml(dif.nuevo)}"`);
                                }
                            }
                        }
                        const textoCambios = listaCambios.length > 0 ? listaCambios.slice(0, 3).join('; ') + (listaCambios.length > 3 ? ` (+${listaCambios.length - 3} más)` : '') : 'Modificación de datos';
                        estadoDetalle = `<span class="text-primary small fw-bold">${textoCambios}</span><br><span class="text-muted small">ID: ${f.producto_existente_id}</span>`;
                    } else if (f.accion === 'sin_cambios') {
                        estadoDetalle = `<span class="text-muted small"><i class="fas fa-check text-muted me-1"></i> Idéntico en base de datos (ID: ${f.producto_existente_id})</span>`;
                    } else {
                        estadoDetalle = '<span class="text-success small fw-bold"><i class="fas fa-plus-circle me-1"></i> Listo para insertar</span>';
                    }

                    const agnTxt = f.datos.codigo_agn ? `<br><span class="text-muted small">${escapeHtml(f.datos.codigo_agn)}</span>` : '';
                    const itemCodigo = f.producto_existente_id
                        ? `<span class="badge bg-light text-dark border">ID ${f.producto_existente_id}</span> #${escapeHtml(f.datos.no_item || '-')}${agnTxt}`
                        : `#${escapeHtml(f.datos.no_item || '-')}${agnTxt}`;

                    tr.innerHTML = `
                        <td>${escapeHtml(f.fila_excel)}</td>
                        <td>${badgeAccion}</td>
                        <td>${itemCodigo}</td>
                        <td>
                            <strong>${escapeHtml(f.datos.nombre_comercial)}</strong>
                            ${f.datos.norma_astm ? `<br><span class="text-muted small">${escapeHtml(f.datos.norma_astm)}</span>` : ''}
                        </td>
                        <td>${escapeHtml(f.datos.matriz_tipo || '-')}</td>
                        <td>${precioHtml}</td>
                        <td>${escapeHtml(f.datos.estatus)}</td>
                        <td>${estadoDetalle}</td>
                    `;
                    tbody.appendChild(tr);
                });

                contenedorResumen.classList.remove('d-none');
                if (data.validas > 0 && tokenLoteActual) {
                    btnConfirmar.classList.remove('d-none');
                }
            })
            .catch(err => {
                spinner.classList.add('d-none');
                btnPrevisualizar.disabled = false;
                alertaResultado.className = 'alert alert-danger mt-3 mb-0';
                alertaResultado.textContent = 'Error al previsualizar el archivo: ' + err.message;
                alertaResultado.classList.remove('d-none');
            });
        });
    }

    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', function() {
            if (!tokenLoteActual || !datosCargaActual) return;

            const mod = datosCargaActual.modificados ?? datosCargaActual.actualizaciones ?? 0;
            const sin = datosCargaActual.sin_cambios ?? 0;
            const nue = datosCargaActual.nuevos ?? 0;
            const tot = datosCargaActual.total_filas ?? 0;

            const mensajeConfirm = `¿Confirma que desea aplicar los cambios en el Inventario de Productos?\n\n` +
                `• ${mod} producto(s) modificados de ${tot} analizados en el archivo\n` +
                `• ${nue} producto(s) nuevos a crear\n` +
                `• ${sin} producto(s) sin modificaciones`;

            if (!confirm(mensajeConfirm)) {
                return;
            }

            const payload = new URLSearchParams();
            payload.append('csrf_token', '<?= htmlspecialchars($_SESSION["csrf_token"] ?? "", ENT_QUOTES, "UTF-8") ?>');
            payload.append('token_lote', tokenLoteActual);

            btnConfirmar.disabled = true;
            spinner.classList.remove('d-none');
            alertaResultado.classList.add('d-none');

            fetch('/Cycsa/publico/productos/confirmar-carga', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded'
                },
                body: payload.toString()
            })
            .then(parseJsonSafe)
            .then(data => {
                spinner.classList.add('d-none');
                btnConfirmar.disabled = false;

                if (data.exito) {
                    const actualizados = data.actualizados ?? data.modificados ?? 0;
                    const creados = data.creados ?? 0;
                    const sinCambios = data.sin_cambios ?? 0;
                    const totalAnalizados = data.total ?? (actualizados + creados + sinCambios);

                    alertaResultado.className = 'alert alert-success mt-3 mb-0';
                    alertaResultado.innerHTML = `<strong><i class="fas fa-check-circle me-1"></i> ¡Carga Masiva Exitosa!</strong><br>` +
                        `Se actualizaron <strong>${actualizados}</strong> producto(s) modificados (de <strong>${totalAnalizados}</strong> en el archivo). ` +
                        `Se crearon <strong>${creados}</strong> producto(s) nuevos y <strong>${sinCambios}</strong> se mantuvieron sin cambios. Redirigiendo al historial...`;
                    alertaResultado.classList.remove('d-none');
                    btnConfirmar.classList.add('d-none');

                    setTimeout(() => {
                        window.location.href = '/Cycsa/publico/productos?historial=1#bitacoraModuloPanel';
                    }, 1600);
                } else {
                    alertaResultado.className = 'alert alert-danger mt-3 mb-0';
                    alertaResultado.textContent = data.error || 'Ocurrió un error al guardar los productos.';
                    alertaResultado.classList.remove('d-none');
                }
            })
            .catch(err => {
                spinner.classList.add('d-none');
                btnConfirmar.disabled = false;
                alertaResultado.className = 'alert alert-danger mt-3 mb-0';
                alertaResultado.textContent = 'Error de conexión con el servidor: ' + err.message;
                alertaResultado.classList.remove('d-none');
            });
        });
    }
});
</script>
