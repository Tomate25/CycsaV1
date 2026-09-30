<style>
    #modalCargaMasivaProductos { display:none; position:fixed; inset:0; z-index:10000; padding:28px; background:rgba(15,23,42,.65); align-items:center; justify-content:center; }
    #modalCargaMasivaProductos.activo { display:flex; }
    #modalCargaMasivaProductos .modal-dialog { width:min(1180px,100%); max-height:92vh; }
    #modalCargaMasivaProductos .modal-content { max-height:92vh; display:flex; flex-direction:column; overflow:hidden; background:#fff; border-radius:12px; box-shadow:0 24px 70px rgba(15,23,42,.35); }
    #modalCargaMasivaProductos .modal-header { display:flex; justify-content:space-between; align-items:center; gap:15px; padding:16px 20px; background:#103487; color:#fff; }
    #modalCargaMasivaProductos .modal-title { margin:0; font-size:17px; }
    #modalCargaMasivaProductos .btn-close { border:0; background:transparent; color:#fff; font-size:24px; cursor:pointer; }
    #modalCargaMasivaProductos .modal-body { padding:20px; overflow:auto; }
    #modalCargaMasivaProductos .modal-footer { display:flex; justify-content:flex-end; gap:9px; padding:12px 20px; background:#f8fafc; border-top:1px solid #e2e8f0; }
    #modalCargaMasivaProductos .row { display:grid; grid-template-columns:repeat(2,minmax(0,1fr)); gap:14px; }
    #modalCargaMasivaProductos .row .row { grid-template-columns:repeat(4,minmax(0,1fr)); }
    #modalCargaMasivaProductos .p-3 { padding:16px; }
    #modalCargaMasivaProductos .p-2 { padding:10px; }
    #modalCargaMasivaProductos .border { border:1px solid #e2e8f0; }
    #modalCargaMasivaProductos .rounded, #modalCargaMasivaProductos .rounded-3 { border-radius:9px; }
    #modalCargaMasivaProductos .bg-light { background:#f8fafc; }
    #modalCargaMasivaProductos .bg-white { background:#fff; }
    #modalCargaMasivaProductos .d-none { display:none!important; }
    #modalCargaMasivaProductos .d-flex { display:flex; }
    #modalCargaMasivaProductos .flex-column { flex-direction:column; }
    #modalCargaMasivaProductos .justify-content-between { justify-content:space-between; }
    #modalCargaMasivaProductos .align-items-center { align-items:center; }
    #modalCargaMasivaProductos .gap-2 { gap:8px; }
    #modalCargaMasivaProductos .mb-0 { margin-bottom:0; }
    #modalCargaMasivaProductos .mb-2 { margin-bottom:8px; }
    #modalCargaMasivaProductos .mb-3 { margin-bottom:14px; }
    #modalCargaMasivaProductos .mb-4 { margin-bottom:18px; }
    #modalCargaMasivaProductos .mt-3 { margin-top:14px; }
    #modalCargaMasivaProductos .small { font-size:12px; }
    #modalCargaMasivaProductos .text-muted { color:#64748b; }
    #modalCargaMasivaProductos .fw-bold { font-weight:750; }
    #modalCargaMasivaProductos .w-100 { width:100%; }
    #modalCargaMasivaProductos .form-control { width:100%; box-sizing:border-box; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; background:#fff; }
    #modalCargaMasivaProductos .btn { display:inline-flex; align-items:center; justify-content:center; gap:5px; border:1px solid transparent; border-radius:6px; padding:8px 12px; cursor:pointer; text-decoration:none; font-size:12px; }
    #modalCargaMasivaProductos .btn-primary { background:#103487; color:#fff; }
    #modalCargaMasivaProductos .btn-success { background:#047857; color:#fff; }
    #modalCargaMasivaProductos .btn-secondary { background:#64748b; color:#fff; }
    #modalCargaMasivaProductos .btn-outline-primary { border-color:#103487; color:#103487; background:#fff; }
    #modalCargaMasivaProductos .btn:disabled { opacity:.55; cursor:not-allowed; }
    #modalCargaMasivaProductos .table-responsive { overflow:auto; max-height:380px; }
    #modalCargaMasivaProductos table { width:100%; border-collapse:collapse; }
    #modalCargaMasivaProductos th, #modalCargaMasivaProductos td { padding:8px; border-bottom:1px solid #e2e8f0; text-align:left; vertical-align:top; }
    #modalCargaMasivaProductos th { position:sticky; top:0; background:#1e293b; color:#fff; }
    #modalCargaMasivaProductos .text-center { text-align:center; }
    #modalCargaMasivaProductos .alert { padding:11px 13px; border-radius:7px; }
    #modalCargaMasivaProductos .alert-danger { color:#991b1b; background:#fee2e2; }
    #modalCargaMasivaProductos .alert-success { color:#166534; background:#dcfce7; }
    @media(max-width:760px){ #modalCargaMasivaProductos{padding:10px} #modalCargaMasivaProductos .row,#modalCargaMasivaProductos .row .row{grid-template-columns:1fr} }
</style>

<!-- Modal de Carga Masiva de Productos / Ensayos -->
<div class="modal fade" id="modalCargaMasivaProductos" tabindex="-1" aria-labelledby="modalCargaMasivaLabel" aria-hidden="true" onclick="cerrarModalCargaMasiva(event)">
    <div class="modal-dialog modal-xl modal-dialog-scrollable">
        <div class="modal-content border-0 shadow-lg">
            <div class="modal-header bg-primary text-white py-3">
                <h5 class="modal-title d-flex align-items-center gap-2" id="modalCargaMasivaLabel">
                    <i class="fas fa-file-excel fa-lg text-warning"></i>
                    <span>Carga Masiva y Actualización de Productos (CSV / Excel)</span>
                </h5>
                <button type="button" class="btn-close btn-close-white" onclick="cerrarModalCargaMasiva()" aria-label="Cerrar">&times;</button>
            </div>
            <div class="modal-body p-4">
                <!-- Paso 1: Descargar Plantilla y Seleccionar Archivo -->
                <div class="row g-3 mb-4">
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100 d-flex flex-column justify-content-between">
                            <div>
                                <h6 class="fw-bold text-dark d-flex align-items-center gap-2 mb-2">
                                    <i class="fas fa-download text-primary"></i> 1. Plantilla Oficial
                                </h6>
                                <p class="text-muted small mb-3">
                                    Descargue la plantilla oficial con el formato estándar UTF-8 y ejemplos reales del catálogo de CYCSA para preparar o editar sus precios y datos técnicos.
                                </p>
                            </div>
                            <div>
                                <a href="/Cycsa/publico/productos/descargar-plantilla" class="btn btn-outline-primary btn-sm w-100 fw-bold">
                                    <i class="fas fa-file-csv me-1"></i> Descargar Plantilla Oficial (.csv)
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="p-3 bg-light rounded-3 border h-100 d-flex flex-column justify-content-between">
                            <div>
                                <h6 class="fw-bold text-dark d-flex align-items-center gap-2 mb-2">
                                    <i class="fas fa-upload text-success"></i> 2. Subir Archivo Modificado
                                </h6>
                                <p class="text-muted small mb-2">
                                    Seleccione su archivo CSV guardado. El sistema cotejará por <code>No_Item</code> y <code>Nombre Comercial</code> para actualizar existentes o crear nuevos.
                                </p>
                                <input type="file" id="inputArchivoCsvProductos" class="form-control form-control-sm mb-2" accept=".csv,text/csv">
                            </div>
                            <div class="d-flex gap-2">
                                <button type="button" id="btnPrevisualizarCsv" class="btn btn-success btn-sm w-100 fw-bold" disabled>
                                    <i class="fas fa-search me-1"></i> Previsualizar y Validar
                                </button>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Spinner de Carga -->
                <div id="spinnerCargaMasiva" class="text-center py-5 d-none">
                    <div class="spinner-border text-primary mb-2" role="status"></div>
                    <p class="text-muted fw-bold mb-0">Analizando y validando datos de cada fila...</p>
                </div>

                <!-- Resumen de Validación -->
                <div id="contenedorResumenCarga" class="d-none mb-3">
                    <div class="row g-2 text-center mb-3">
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-white shadow-sm">
                                <div class="text-muted small text-uppercase fw-bold">Total Filas</div>
                                <div id="resumenTotalFilas" class="h4 mb-0 fw-bold text-dark">0</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-white shadow-sm">
                                <div class="text-muted small text-uppercase fw-bold">Nuevos a Crear</div>
                                <div id="resumenNuevos" class="h4 mb-0 fw-bold text-success">0</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-white shadow-sm">
                                <div class="text-muted small text-uppercase fw-bold">Actualizaciones</div>
                                <div id="resumenActualizaciones" class="h4 mb-0 fw-bold text-primary">0</div>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="p-2 border rounded bg-white shadow-sm">
                                <div class="text-muted small text-uppercase fw-bold">Errores</div>
                                <div id="resumenErrores" class="h4 mb-0 fw-bold text-danger">0</div>
                            </div>
                        </div>
                    </div>

                    <!-- Tabla de Previsualización -->
                    <div class="table-responsive border rounded bg-white" style="max-height: 380px;">
                        <table class="table table-hover table-sm align-middle mb-0 font-monospace" style="font-size: 0.85rem;">
                            <thead class="table-dark sticky-top">
                                <tr>
                                    <th style="width: 50px;">Fila</th>
                                    <th style="width: 110px;">Acción</th>
                                    <th style="width: 80px;">Ítem</th>
                                    <th>Nombre Comercial / Ensayo</th>
                                    <th style="width: 120px;">Matriz</th>
                                    <th style="width: 140px;">Precio</th>
                                    <th style="width: 120px;">Estatus</th>
                                    <th>Observaciones / Errores</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyPrevisualizacionCarga">
                                <!-- Se inyecta dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Mensaje de Resultado Final -->
                <div id="alertaResultadoCarga" class="alert d-none mt-3" role="alert"></div>
            </div>
            <div class="modal-footer bg-light py-2">
                <button type="button" class="btn btn-secondary btn-sm" onclick="cerrarModalCargaMasiva()">Cerrar</button>
                <button type="button" id="btnConfirmarCargaMasiva" class="btn btn-primary btn-sm fw-bold px-4 d-none">
                    <i class="fas fa-check-circle me-1"></i> Confirmar y Aplicar Cambios
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function abrirModalCargaMasiva() {
    const modal = document.getElementById('modalCargaMasivaProductos');
    modal.classList.add('activo');
    modal.setAttribute('aria-hidden', 'false');
    document.body.style.overflow = 'hidden';
}

function cerrarModalCargaMasiva(event) {
    const modal = document.getElementById('modalCargaMasivaProductos');
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
    const tbody = document.getElementById('tbodyPrevisualizacionCarga');
    const alertaResultado = document.getElementById('alertaResultadoCarga');

    document.addEventListener('keydown', function(event) {
        if (event.key === 'Escape') cerrarModalCargaMasiva();
    });

    let tokenLoteActual = null;
    const escapeHtml = value => String(value ?? '')
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');

    if (inputCsv) {
        inputCsv.addEventListener('change', function() {
            btnPrevisualizar.disabled = !this.files || this.files.length === 0;
            contenedorResumen.classList.add('d-none');
            btnConfirmar.classList.add('d-none');
            alertaResultado.classList.add('d-none');
        });
    }

    if (btnPrevisualizar) {
        btnPrevisualizar.addEventListener('click', function() {
            if (!inputCsv.files || inputCsv.files.length === 0) return;

            const archivo = inputCsv.files[0];
            const formData = new FormData();
            formData.append('archivo_csv', archivo);
            formData.append('csrf_token', '<?= $_SESSION["csrf_token"] ?? "" ?>');

            spinner.classList.remove('d-none');
            contenedorResumen.classList.add('d-none');
            btnConfirmar.classList.add('d-none');
            alertaResultado.classList.add('d-none');
            btnPrevisualizar.disabled = true;

            fetch('/Cycsa/publico/productos/previsualizar-carga', {
                method: 'POST',
                body: formData
            })
            .then(res => res.json())
            .then(data => {
                spinner.classList.add('d-none');
                btnPrevisualizar.disabled = false;

                if (!data.exito) {
                    alertaResultado.className = 'alert alert-danger mt-3';
                    alertaResultado.textContent = data.error || 'Ocurrió un error al procesar el archivo.';
                    alertaResultado.classList.remove('d-none');
                    return;
                }

                tokenLoteActual = data.token_lote || null;

                document.getElementById('resumenTotalFilas').textContent = data.total_filas;
                document.getElementById('resumenNuevos').textContent = data.nuevos;
                document.getElementById('resumenActualizaciones').textContent = data.actualizaciones;
                document.getElementById('resumenErrores').textContent = data.errores;

                tbody.innerHTML = '';
                data.filas.forEach(f => {
                    const tr = document.createElement('tr');
                    if (!f.es_valida) {
                        tr.className = 'table-danger';
                    }

                    let badgeAccion = '';
                    if (!f.es_valida) {
                        badgeAccion = '<span class="badge bg-danger">Error</span>';
                    } else if (f.accion === 'crear') {
                        badgeAccion = '<span class="badge bg-success">Nuevo</span>';
                    } else {
                        badgeAccion = '<span class="badge bg-primary">Actualizar</span>';
                    }

                    let precioHtml = `C$ ${parseFloat(f.datos.precio || 0).toFixed(2)}`;
                    if (f.diferencias && f.diferencias.precio) {
                        precioHtml = `<span class="text-decoration-line-through text-muted small">C$ ${parseFloat(f.diferencias.precio.anterior).toFixed(2)}</span><br><strong class="text-success">C$ ${parseFloat(f.diferencias.precio.nuevo).toFixed(2)}</strong>`;
                    }

                    let estadoDetalle = '';
                    if (f.errores && f.errores.length > 0) {
                        estadoDetalle = `<span class="text-danger fw-bold">${f.errores.map(escapeHtml).join('<br>')}</span>`;
                    } else if (f.accion === 'actualizar') {
                        estadoDetalle = `<span class="text-muted small">Modifica ID: ${f.producto_existente_id}</span>`;
                    } else {
                        estadoDetalle = '<span class="text-success small">Listo para insertar</span>';
                    }

                    tr.innerHTML = `
                        <td>${escapeHtml(f.fila_excel)}</td>
                        <td>${badgeAccion}</td>
                        <td>${escapeHtml(f.datos.no_item || '-')}</td>
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
                if (data.validas > 0) {
                    btnConfirmar.classList.remove('d-none');
                }
            })
            .catch(err => {
                spinner.classList.add('d-none');
                btnPrevisualizar.disabled = false;
                alertaResultado.className = 'alert alert-danger mt-3';
                alertaResultado.textContent = 'Error de conexión con el servidor: ' + err.message;
                alertaResultado.classList.remove('d-none');
            });
        });
    }

    if (btnConfirmar) {
        btnConfirmar.addEventListener('click', function() {
            if (!tokenLoteActual) return;

            if (!confirm('¿Confirma que desea aplicar los cambios en el Inventario de Productos? Se crearán los productos nuevos y se actualizarán los existentes según la vista previa.')) {
                return;
            }

            const payload = new URLSearchParams();
            payload.append('csrf_token', '<?= $_SESSION["csrf_token"] ?? "" ?>');
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
            .then(res => res.json())
            .then(data => {
                spinner.classList.add('d-none');
                btnConfirmar.disabled = false;

                if (data.exito) {
                    alertaResultado.className = 'alert alert-success mt-3';
                    alertaResultado.innerHTML = `<strong><i class="fas fa-check-circle me-1"></i> ¡Carga Masiva Exitosa!</strong><br>Se crearon <strong>${data.creados}</strong> productos nuevos y se actualizaron <strong>${data.actualizados}</strong> productos existentes.`;
                    alertaResultado.classList.remove('d-none');
                    btnConfirmar.classList.add('d-none');

                    setTimeout(() => {
                        window.location.reload();
                    }, 2000);
                } else {
                    alertaResultado.className = 'alert alert-danger mt-3';
                    alertaResultado.textContent = data.error || 'Ocurrió un error al guardar los productos.';
                    alertaResultado.classList.remove('d-none');
                }
            })
            .catch(err => {
                spinner.classList.add('d-none');
                btnConfirmar.disabled = false;
                alertaResultado.className = 'alert alert-danger mt-3';
                alertaResultado.textContent = 'Error de conexión con el servidor: ' + err.message;
                alertaResultado.classList.remove('d-none');
            });
        });
    }
});
</script>
