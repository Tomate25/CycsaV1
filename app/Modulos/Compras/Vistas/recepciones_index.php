<?php
// Vista: Listado y Registro de Recepciones de Compra
$totalRec = count($recepciones);
$ordenesCompletadas = 0;
foreach ($recepciones as $r) {
    if (($r['estado'] ?? '') === 'completada') $ordenesCompletadas++;
}
?>
<div class="container-fluid py-3">

    <!-- Encabezado y Acciones -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-boxes-packing text-primary me-2"></i>Recepciones de Compra
            </h1>
            <p class="text-muted small mb-0">Recepción técnica de insumos en bodega, control de lotes y alimentación de kárdex</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/Cycsa/publico/compras/proveedores" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-truck-field me-1"></i>Proveedores
            </a>
            <a href="/Cycsa/publico/compras/ordenes" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-file-invoice-dollar me-1"></i>Órdenes de Compra
            </a>
            <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalRecepcion" onclick="abrirModalNuevaRecepcion()">
                <i class="fa-solid fa-plus me-1"></i>Registrar Recepción
            </button>
        </div>
    </div>

    <!-- Alertas -->
    <?php if (!empty($exito)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Tarjetas KPI -->
    <div class="row g-3 mb-4">
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100 py-2">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Recepciones</div>
                            <div class="h4 mb-0 fw-bold text-gray-800"><?= $totalRec ?></div>
                        </div>
                        <div class="text-primary bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="fa-solid fa-dolly fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100 py-2">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Recepciones Completadas</div>
                            <div class="h4 mb-0 fw-bold text-gray-800"><?= $ordenesCompletadas ?></div>
                        </div>
                        <div class="text-success bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="fa-solid fa-clipboard-check fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-4 col-md-6">
            <div class="card border-0 shadow-sm border-start border-info border-4 h-100 py-2">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Órdenes Aprobadas Pendientes</div>
                            <div class="h4 mb-0 fw-bold text-gray-800"><?= count($ordenesAprobadas ?? []) ?></div>
                        </div>
                        <div class="text-info bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="fa-solid fa-clock-rotate-left fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="/Cycsa/publico/compras/recepciones" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Buscar por # recepción o factura..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="proveedor_id" class="form-select form-select-sm">
                        <option value="">Todos los Proveedores</option>
                        <?php foreach ($proveedores as $pv): ?>
                            <option value="<?= $pv['id'] ?>" <?= ($proveedorId ?? '') == $pv['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pv['razon_social']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos los Estados</option>
                        <option value="completada" <?= ($estado ?? '') === 'completada' ? 'selected' : '' ?>>Completada</option>
                        <option value="anulada" <?= ($estado ?? '') === 'anulada' ? 'selected' : '' ?>>Anulada</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary btn-sm flex-fill">
                        <i class="fa-solid fa-filter me-1"></i>Filtrar
                    </button>
                    <?php if (!empty($busqueda) || !empty($proveedorId) || !empty($estado)): ?>
                        <a href="/Cycsa/publico/compras/recepciones" class="btn btn-outline-danger btn-sm" title="Limpiar filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla Principal de Recepciones -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3"># Recepción</th>
                            <th>Fecha</th>
                            <th>Orden de Compra</th>
                            <th>Proveedor</th>
                            <th>N° Factura</th>
                            <th>Recibido Por</th>
                            <th>Observaciones</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($recepciones)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-box-open fa-2x mb-2 d-block"></i>
                                    No se encontraron recepciones de compra registradas.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($recepciones as $rc): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-primary">
                                        REC-<?= str_pad($rc['id'], 5, '0', STR_PAD_LEFT) ?>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($rc['fecha_recepcion'] ?? date('Y-m-d')) ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($rc['orden_compra_numero'])): ?>
                                            <a href="/Cycsa/publico/compras/ordenes/detalle?id=<?= $rc['orden_compra_id'] ?>" class="fw-semibold text-decoration-none">
                                                OC-<?= str_pad($rc['orden_compra_numero'], 5, '0', STR_PAD_LEFT) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">Directa (S/N)</span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            <?= htmlspecialchars($rc['proveedor_nombre'] ?? '—') ?>
                                        </div>
                                        <?php if (!empty($rc['proveedor_identificacion'])): ?>
                                            <div class="text-muted text-xs">RUC: <?= htmlspecialchars($rc['proveedor_identificacion']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <i class="fa-regular fa-file-lines me-1"></i><?= htmlspecialchars($rc['numero_factura'] ?: 'S/N') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($rc['recibido_por_nombre'] ?? 'Bodeguero') ?>
                                    </td>
                                    <td class="text-muted">
                                        <?= htmlspecialchars($rc['observaciones'] ?: '—') ?>
                                    </td>
                                    <td>
                                        <?php if (($rc['estado'] ?? '') === 'completada'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                                <i class="fa-solid fa-check me-1"></i>Completada
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                                Anulada
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL: REGISTRAR RECEPCIÓN FÍSICA -->
<div class="modal fade" id="modalRecepcion" tabindex="-1" aria-labelledby="modalRecepcionLabel" aria-hidden="true">
    <div class="modal-dialog modal-xl">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="/Cycsa/publico/compras/recepciones/guardar" id="formRecepcion">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalRecepcionLabel">
                        <i class="fa-solid fa-boxes-packing text-primary me-2"></i>Registrar Recepción de Compra
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3 mb-4">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Orden de Compra Aprobada</label>
                            <select name="orden_compra_id" id="rec_orden_id" class="form-select form-select-sm" onchange="seleccionarOrdenParaRecepcion()">
                                <option value="">-- Sin Orden Previa / Recepción Directa --</option>
                                <?php foreach ($ordenesAprobadas as $ocA): ?>
                                    <option value="<?= $ocA['id'] ?>" 
                                            data-proveedor-id="<?= $ocA['proveedor_id'] ?>"
                                            <?= (!empty($ordenSeleccionada['id']) && $ordenSeleccionada['id'] == $ocA['id']) ? 'selected' : '' ?>>
                                        OC-<?= str_pad($ocA['id'], 5, '0', STR_PAD_LEFT) ?> - <?= htmlspecialchars($ocA['proveedor_nombre'] ?? $ocA['proveedor'] ?? '') ?> (C$ <?= number_format((float)$ocA['total'], 2) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Proveedor *</label>
                            <select name="proveedor_id" id="rec_proveedor_id" class="form-select form-select-sm" required>
                                <option value="">-- Seleccione Proveedor --</option>
                                <?php foreach ($proveedores as $pv): ?>
                                    <option value="<?= $pv['id'] ?>" <?= (!empty($ordenSeleccionada['proveedor_id']) && $ordenSeleccionada['proveedor_id'] == $pv['id']) ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($pv['razon_social']) ?> (<?= htmlspecialchars($pv['identificacion']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Número de Factura / Remisión</label>
                            <input type="text" name="numero_factura" id="rec_factura" class="form-control form-control-sm" placeholder="Ej: F-0012485">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Fecha de Recepción *</label>
                            <input type="date" name="fecha_recepcion" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Observaciones / Inspección Técnica (ISO 17025)</label>
                            <input type="text" name="observaciones" class="form-control form-control-sm" placeholder="Conformidad física, embalaje, sellos de lote y empaque intactos">
                        </div>
                    </div>

                    <!-- Tabla de Ítems a Recibir -->
                    <div class="d-flex justify-content-between align-items-center mb-2">
                        <h6 class="fw-bold text-dark m-0"><i class="fa-solid fa-list-check me-1 text-primary"></i>Insumos y Lotes Recibidos</h6>
                        <button type="button" class="btn btn-outline-primary btn-sm" onclick="agregarFilaRecepcion()">
                            <i class="fa-solid fa-plus me-1"></i>Agregar Insumo
                        </button>
                    </div>

                    <div class="table-responsive border rounded">
                        <table class="table table-sm table-bordered align-middle mb-0">
                            <thead class="table-light small text-muted text-uppercase">
                                <tr>
                                    <th style="width: 30%;">Insumo / Reactivo *</th>
                                    <th style="width: 15%;" class="text-end">Cant. Recibida *</th>
                                    <th style="width: 15%;" class="text-end">Costo Unit.</th>
                                    <th style="width: 18%;">Lote Proveedor</th>
                                    <th style="width: 14%;">Fecha Venc.</th>
                                    <th style="width: 8%;" class="text-center">Quitar</th>
                                </tr>
                            </thead>
                            <tbody id="tbodyRecepcion">
                                <!-- Filas dinámicas -->
                            </tbody>
                        </table>
                    </div>
                </div>
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4 fw-semibold">
                        <i class="fa-solid fa-dolly me-1"></i>Confirmar Recepción en Inventario
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<div id="insumosCatalogoDataRec" style="display: none;">
    <?= json_encode($insumos ?? [], JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>
</div>
<div id="ordenSeleccionadaData" style="display: none;">
    <?= json_encode($ordenSeleccionada ?? null, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>
</div>

<script>
let catalogoInsumosRec = [];
try {
    catalogoInsumosRec = JSON.parse(document.getElementById('insumosCatalogoDataRec').textContent || '[]');
} catch(e) {
    catalogoInsumosRec = [];
}

let contadorFilaRec = 0;

function abrirModalNuevaRecepcion() {
    document.getElementById('tbodyRecepcion').innerHTML = '';
    agregarFilaRecepcion();
}

function agregarFilaRecepcion(datos = null) {
    contadorFilaRec++;
    const tbody = document.getElementById('tbodyRecepcion');
    const tr = document.createElement('tr');
    tr.id = 'tr_rec_' + contadorFilaRec;

    let options = '<option value="">-- Seleccionar Insumo --</option>';
    catalogoInsumosRec.forEach(ins => {
        const sel = (datos && datos.insumo_id == ins.id) ? 'selected' : '';
        options += `<option value="${ins.id}" ${sel}>${ins.codigo} - ${ins.nombre} (${ins.unidad_medida})</option>`;
    });

    const ocDetId = datos ? (datos.orden_compra_detalle_id || '') : '';
    const cantVal = datos ? (datos.cantidad_recibida || datos.cantidad || 1) : 1;
    const costoVal = datos ? (datos.costo_unitario || 0) : 0;
    const loteVal = datos ? (datos.lote || '') : '';
    const vencVal = datos ? (datos.fecha_vencimiento || '') : '';

    tr.innerHTML = `
        <td>
            <input type="hidden" name="rec_oc_detalle_id[]" value="${ocDetId}">
            <select name="rec_insumo_id[]" class="form-select form-select-sm" required>
                ${options}
            </select>
        </td>
        <td>
            <input type="number" step="0.01" min="0.01" name="rec_cantidad[]" class="form-control form-control-sm text-end" value="${cantVal}" required>
        </td>
        <td>
            <input type="number" step="0.01" min="0" name="rec_costo[]" class="form-control form-control-sm text-end" value="${costoVal}">
        </td>
        <td>
            <input type="text" name="rec_lote[]" class="form-control form-control-sm" placeholder="Ej: LOT-2026-X" value="${loteVal}">
        </td>
        <td>
            <input type="date" name="rec_vencimiento[]" class="form-control form-control-sm" value="${vencVal}">
        </td>
        <td class="text-center">
            <button type="button" class="btn btn-outline-danger btn-sm py-0 px-2" onclick="document.getElementById('tr_rec_${contadorFilaRec}').remove()">
                <i class="fa-solid fa-trash-can"></i>
            </button>
        </td>
    `;
    tbody.appendChild(tr);
}

function seleccionarOrdenParaRecepcion() {
    const sel = document.getElementById('rec_orden_id');
    const opt = sel.options[sel.selectedIndex];
    if (opt && opt.dataset.proveedorId) {
        document.getElementById('rec_proveedor_id').value = opt.dataset.proveedorId;
    }
}

document.addEventListener('DOMContentLoaded', () => {
    try {
        const ordenSel = JSON.parse(document.getElementById('ordenSeleccionadaData').textContent || 'null');
        if (ordenSel && ordenSel.id) {
            const modalEl = document.getElementById('modalRecepcion');
            const modal = new bootstrap.Modal(modalEl);
            document.getElementById('tbodyRecepcion').innerHTML = '';
            if (ordenSel.detalles && ordenSel.detalles.length > 0) {
                ordenSel.detalles.forEach(d => {
                    const pendiente = Math.max(0, parseFloat(d.cantidad) - parseFloat(d.cantidad_recibida || 0));
                    if (pendiente > 0) {
                        agregarFilaRecepcion({
                            orden_compra_detalle_id: d.id,
                            insumo_id: d.insumo_id,
                            cantidad_recibida: pendiente,
                            costo_unitario: d.costo_unitario
                        });
                    }
                });
            } else {
                agregarFilaRecepcion();
            }
            modal.show();
        }
    } catch(e) {}
});
</script>
