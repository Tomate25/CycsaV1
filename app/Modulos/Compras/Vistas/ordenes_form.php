<?php
// Vista: Formulario de Creación / Edición de Orden de Compra
?>
<div class="container-fluid py-3">

    <!-- Encabezado y Navegación -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-file-circle-plus text-primary me-2"></i>Nueva Orden de Compra
            </h1>
            <p class="text-muted small mb-0">Emisión de requisición y orden de compra formal a proveedores evaluados</p>
        </div>
        <div>
            <a href="/Cycsa/publico/compras/ordenes" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i>Volver al Listado
            </a>
        </div>
    </div>

    <!-- Alertas -->
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <form method="POST" action="/Cycsa/publico/compras/ordenes/guardar" id="formOrdenCompra">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id" value="<?= !empty($orden['id']) ? (int)$orden['id'] : '' ?>">

        <!-- Datos de Cabecera -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light py-3">
                <h6 class="m-0 fw-bold text-dark"><i class="fa-solid fa-circle-info me-2 text-primary"></i>Datos Generales de la Orden</h6>
            </div>
            <div class="card-body p-4">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label small fw-semibold">Proveedor *</label>
                        <select name="proveedor_id" id="proveedor_id" class="form-select form-select-sm" required onchange="actualizarInfoProveedor()">
                            <option value="">-- Seleccione Proveedor Calificado --</option>
                            <?php foreach ($proveedores as $pv): ?>
                                <option value="<?= $pv['id'] ?>" 
                                        data-identificacion="<?= htmlspecialchars($pv['identificacion']) ?>"
                                        data-contacto="<?= htmlspecialchars($pv['contacto'] ?? '') ?>"
                                        data-plazo="<?= htmlspecialchars($pv['plazo_pago_dias'] ?? 30) ?>"
                                        data-evaluado="<?= !empty($pv['evaluado']) ? '1' : '0' ?>"
                                        <?= (!empty($orden['proveedor_id']) && $orden['proveedor_id'] == $pv['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($pv['razon_social']) ?> (<?= htmlspecialchars($pv['identificacion']) ?>)
                                    <?= !empty($pv['evaluado']) ? '✓ [ISO 17025]' : '⚠ [No Eval]' ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div id="proveedor_aviso" class="form-text small"></div>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Fecha de Emisión *</label>
                        <input type="date" name="fecha" class="form-control form-control-sm" value="<?= !empty($orden['fecha']) ? htmlspecialchars($orden['fecha']) : date('Y-m-d') ?>" required>
                    </div>

                    <div class="col-md-3">
                        <label class="form-label small fw-semibold">Estado</label>
                        <select name="estado" class="form-select form-select-sm" readonly>
                            <option value="SOLICITADA" selected>SOLICITADA (Borrador / Pendiente Aprobación)</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- Líneas de Detalle -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-light py-3 d-flex justify-content-between align-items-center">
                <h6 class="m-0 fw-bold text-dark"><i class="fa-solid fa-list-check me-2 text-primary"></i>Ítems e Insumos Solicitados</h6>
                <button type="button" class="btn btn-outline-primary btn-sm" onclick="agregarLineaDetalle()">
                    <i class="fa-solid fa-plus me-1"></i>Agregar Línea
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-bordered align-middle mb-0" id="tablaDetalles">
                        <thead class="table-light small text-muted text-uppercase">
                            <tr>
                                <th style="width: 25%;">Insumo / Catálogo</th>
                                <th style="width: 25%;">Descripción *</th>
                                <th style="width: 10%;" class="text-end">Cantidad *</th>
                                <th style="width: 12%;" class="text-end">Costo Unitario *</th>
                                <th style="width: 8%;" class="text-center">IVA 15%</th>
                                <th style="width: 12%;" class="text-end">Subtotal</th>
                                <th style="width: 8%;" class="text-center">Quitar</th>
                            </tr>
                        </thead>
                        <tbody id="tbodyDetalles">
                            <!-- Filas dinámicas -->
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white p-4">
                <div class="row align-items-center">
                    <div class="col-md-7 text-muted small">
                        <i class="fa-solid fa-shield-halved text-success me-1"></i>
                        Las compras aprobadas podrán recibirse en bodega mediante el módulo de recepciones físicas actualizando el kárdex e inventario de lotes automáticamente.
                    </div>
                    <div class="col-md-5">
                        <div class="card bg-light border-0 p-3">
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">Subtotal:</span>
                                <span class="fw-bold font-monospace" id="txtSubtotal">C$ 0.00</span>
                                <input type="hidden" name="subtotal" id="inputSubtotal" value="0">
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">IVA (15%):</span>
                                <span class="fw-bold font-monospace text-muted" id="txtIva">C$ 0.00</span>
                                <input type="hidden" name="iva" id="inputIva" value="0">
                            </div>
                            <hr class="my-2">
                            <div class="d-flex justify-content-between">
                                <span class="fw-bold fs-6 text-dark">TOTAL GENERAL:</span>
                                <span class="fw-bold fs-5 text-primary font-monospace" id="txtTotal">C$ 0.00</span>
                                <input type="hidden" name="total" id="inputTotal" value="0">
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="/Cycsa/publico/compras/ordenes" class="btn btn-secondary px-4">Cancelar</a>
            <button type="submit" class="btn btn-primary px-4 fw-semibold">
                <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Orden de Compra
            </button>
        </div>
    </form>
</div>

<!-- Template Oculto de Insumos para JS -->
<div id="insumosCatalogoData" style="display: none;">
    <?= json_encode($insumos ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>
</div>

<script>
let catalogoInsumos = [];
try {
    const rawData = document.getElementById('insumosCatalogoData').textContent;
    catalogoInsumos = JSON.parse(rawData);
} catch(e) {
    catalogoInsumos = [];
}

let filaContador = 0;

function actualizarInfoProveedor() {
    const sel = document.getElementById('proveedor_id');
    const opt = sel.options[sel.selectedIndex];
    const aviso = document.getElementById('proveedor_aviso');
    if (!opt || !opt.value) {
        aviso.innerHTML = '';
        return;
    }
    const evaluado = opt.dataset.evaluado === '1';
    const plazo = opt.dataset.plazo || '30';
    if (evaluado) {
        aviso.innerHTML = '<span class="text-success"><i class="fa-solid fa-check-circle me-1"></i>Proveedor calificado bajo norma ISO 17025 §6.6. Plazo: ' + plazo + ' días.</span>';
    } else {
        aviso.innerHTML = '<span class="text-warning text-dark"><i class="fa-solid fa-triangle-exclamation me-1"></i>Atención: Proveedor no evaluado aún para ISO 17025. Se recomienda realizar calificación técnica.</span>';
    }
}

function agregarLineaDetalle(datos = null) {
    filaContador++;
    const tbody = document.getElementById('tbodyDetalles');
    const tr = document.createElement('tr');
    tr.id = 'fila_' + filaContador;

    let optionsInsumos = '<option value="">-- Personalizado / Otro --</option>';
    catalogoInsumos.forEach(ins => {
        const sel = (datos && datos.insumo_id == ins.id) ? 'selected' : '';
        optionsInsumos += `<option value="${ins.id}" data-nombre="${ins.nombre}" data-unidad="${ins.unidad_medida}" ${sel}>${ins.codigo} - ${ins.nombre}</option>`;
    });

    const descVal = datos ? (datos.descripcion || '') : '';
    const cantVal = datos ? (datos.cantidad || 1) : 1;
    const costoVal = datos ? (datos.costo_unitario || 0) : 0;
    const aplicaIva = datos ? (datos.aplica_iva ? 'checked' : '') : 'checked';

    tr.innerHTML = `
        <td>
            <select name="item_insumo_id[]" class="form-select form-select-sm" onchange="seleccionarInsumoFila(${filaContador}, this)">
                ${optionsInsumos}
            </select>
        </td>
        <td>
            <input type="text" name="item_descripcion[]" id="desc_${filaContador}" class="form-control form-control-sm" required placeholder="Descripción del ítem" value="${descVal}">
        </td>
        <td>
            <input type="number" step="0.01" min="0.01" name="item_cantidad[]" id="cant_${filaContador}" class="form-control form-control-sm text-end" required value="${cantVal}" oninput="calcularTotales()">
        </td>
        <td>
            <input type="number" step="0.01" min="0" name="item_costo[]" id="costo_${filaContador}" class="form-control form-control-sm text-end" required value="${costoVal}" oninput="calcularTotales()">
        </td>
        <td class="text-center">
            <input type="checkbox" name="item_aplica_iva[]" id="iva_${filaContador}" class="form-check-input" value="1" ${aplicaIva} onchange="calcularTotales()">
        </td>
        <td class="text-end font-monospace fw-semibold" id="subtotal_${filaContador}">
            C$ 0.00
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="eliminarFila(${filaContador})">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </td>
    `;

    tbody.appendChild(tr);
    calcularTotales();
}

function seleccionarInsumoFila(filaId, selectElement) {
    const opt = selectElement.options[selectElement.selectedIndex];
    const descInput = document.getElementById('desc_' + filaId);
    if (opt && opt.value && opt.dataset.nombre) {
        descInput.value = opt.dataset.nombre + (opt.dataset.unidad ? ' (' + opt.dataset.unidad + ')' : '');
    }
}

function eliminarFila(filaId) {
    const tr = document.getElementById('fila_' + filaId);
    if (tr) {
        tr.remove();
        calcularTotales();
    }
}

function calcularTotales() {
    let subtotalGen = 0.0;
    let ivaGen = 0.0;

    const filas = document.querySelectorAll('#tbodyDetalles tr');
    filas.forEach(row => {
        const id = row.id.split('_')[1];
        const cant = parseFloat(document.getElementById('cant_' + id)?.value) || 0;
        const costo = parseFloat(document.getElementById('costo_' + id)?.value) || 0;
        const aplicaIva = document.getElementById('iva_' + id)?.checked || false;

        const subtotalLinea = Math.round((cant * costo) * 100) / 100;
        const ivaLinea = aplicaIva ? Math.round((subtotalLinea * 0.15) * 100) / 100 : 0.0;

        const tdSub = document.getElementById('subtotal_' + id);
        if (tdSub) {
            tdSub.textContent = 'C$ ' + subtotalLinea.toFixed(2);
        }

        subtotalGen += subtotalLinea;
        ivaGen += ivaLinea;
    });

    const totalGen = subtotalGen + ivaGen;

    document.getElementById('txtSubtotal').textContent = 'C$ ' + subtotalGen.toFixed(2);
    document.getElementById('inputSubtotal').value = subtotalGen.toFixed(2);

    document.getElementById('txtIva').textContent = 'C$ ' + ivaGen.toFixed(2);
    document.getElementById('inputIva').value = ivaGen.toFixed(2);

    document.getElementById('txtTotal').textContent = 'C$ ' + totalGen.toFixed(2);
    document.getElementById('inputTotal').value = totalGen.toFixed(2);
}

// Inicializar con al menos una fila
document.addEventListener('DOMContentLoaded', () => {
    agregarLineaDetalle();
});
</script>
