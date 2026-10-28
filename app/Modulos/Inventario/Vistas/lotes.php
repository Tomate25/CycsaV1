<?php
/**
 * Vista de Lotes y Control de Vencimientos (TAREA-048)
 */
?>
<style>
    .inv-tabs {
        display: flex;
        gap: 8px;
        border-bottom: 2px solid #e2e8f0;
        margin-bottom: 20px;
    }
    .inv-tab-item {
        padding: 10px 18px;
        font-size: 14px;
        font-weight: 600;
        color: #64748b;
        text-decoration: none;
        border-bottom: 3px solid transparent;
        margin-bottom: -2px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .inv-tab-item:hover { color: #0284c7; }
    .inv-tab-item.active {
        color: #0284c7;
        border-bottom-color: #0284c7;
    }

    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .tabla-cycsa th { background-color: #f8f9fa; color: #475569; padding: 12px 14px; text-align: left; font-weight: 700; border-bottom: 2px solid #cbd5e1; text-transform: uppercase; font-size: 11px; }
    .tabla-cycsa td { padding: 12px 14px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; color: #1e293b; }
    .tabla-cycsa tbody tr:hover { background-color: #f1f5f9; }

    .badge-pill {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 4px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.4px;
    }
    .badge-vigente { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-por-vencer { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-vencido { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }
    .badge-sin-fecha { background: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; }


</style>

<div class="content-header mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-1" style="color: #0f172a;">
                <i class="fa-solid fa-tags me-2" style="color: #0284c7;"></i>Control de Lotes y Caducidades
            </h2>
            <p class="text-muted small mb-0">Gestión de lotes activos, caducidad operativa (&le; 30 días) y despacho PEPS/FIFO</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" onclick="abrirModalAjuste()">
                <i class="fa-solid fa-plus-minus me-1"></i>Ajuste de Lote
            </button>
        </div>
    </div>
</div>

<!-- Alertas Flash -->
<?php if (!empty($exito)): ?>
    <div class="alert alert-success alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
        <i class="fa-solid fa-circle-check"></i>
        <div><?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-danger alert-dismissible fade show d-flex align-items-center gap-2 mb-3" role="alert">
        <i class="fa-solid fa-triangle-exclamation"></i>
        <div><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
<?php endif; ?>

<!-- Pestañas de Navegación del Módulo -->
<div class="inv-tabs">
    <a href="/Cycsa/publico/inventario" class="inv-tab-item">
        <i class="fa-solid fa-layer-group"></i>Resumen de Stock e Insumos
    </a>
    <a href="/Cycsa/publico/inventario/lotes" class="inv-tab-item active">
        <i class="fa-solid fa-tags"></i>Control de Lotes y Caducidades
        <?php if (($alertas['conteo_por_vencer'] ?? 0) + ($alertas['conteo_vencidos'] ?? 0) > 0): ?>
            <span class="badge bg-danger rounded-pill"><?= ($alertas['conteo_por_vencer'] ?? 0) + ($alertas['conteo_vencidos'] ?? 0) ?></span>
        <?php endif; ?>
    </a>
    <a href="/Cycsa/publico/inventario/kardex" class="inv-tab-item">
        <i class="fa-solid fa-clock-rotate-left"></i>Kárdex de Movimientos
    </a>
</div>

<!-- Selector de Insumo para Filtrar Lotes -->
<div class="bg-white p-3 rounded-3 shadow-sm border mb-4">
    <form method="GET" action="/Cycsa/publico/inventario/lotes" class="row g-2 align-items-center">
        <div class="col-md-6">
            <label class="form-label small fw-bold text-muted mb-1">Filtrar por Insumo:</label>
            <select name="insumo_id" class="form-select" onchange="this.form.submit()">
                <option value="0">-- Todos los Insumos --</option>
                <?php foreach ($insumos as $ins): ?>
                    <option value="<?= $ins['id'] ?>" <?= $insumoId == $ins['id'] ? 'selected' : '' ?>>
                        [<?= htmlspecialchars($ins['codigo']) ?>] <?= htmlspecialchars($ins['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-6 d-flex align-items-end gap-2">
            <?php if ($insumoId > 0): ?>
                <a href="/Cycsa/publico/inventario/lotes" class="btn btn-outline-secondary">Ver Todos</a>
                <a href="/Cycsa/publico/inventario/kardex?insumo_id=<?= $insumoId ?>" class="btn btn-outline-primary">
                    <i class="fa-solid fa-clock-rotate-left me-1"></i>Ver Kárdex de este Insumo
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tabla de Lotes -->
<div class="bg-white rounded-3 shadow-sm border overflow-hidden p-3">
    <div class="table-responsive">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th style="width: 80px;">ID</th>
                    <th>Insumo</th>
                    <th style="width: 140px;">Lote #</th>
                    <th style="width: 130px;">Vencimiento</th>
                    <th style="width: 160px; text-align: center;">Estado Caducidad</th>
                    <th style="width: 120px; text-align: right;">Costo Unit.</th>
                    <th style="width: 120px; text-align: right;">Saldo</th>
                    <th style="width: 100px; text-align: center;">Estado</th>
                    <th style="width: 120px; text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($lotes)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-tags fa-2x mb-2 d-block"></i>
                            No se encontraron lotes registrados para el criterio seleccionado.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($lotes as $lot): ?>
                        <tr>
                            <td class="text-muted">#<?= $lot['id'] ?></td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace me-1"><?= htmlspecialchars($lot['insumo_codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="fw-semibold"><?= htmlspecialchars($lot['insumo_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <span class="badge bg-secondary font-monospace"><?= htmlspecialchars($lot['lote'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <?= !empty($lot['fecha_vencimiento']) ? htmlspecialchars($lot['fecha_vencimiento']) : '<span class="text-muted">N/A</span>' ?>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($lot['vencido']): ?>
                                    <span class="badge-pill badge-vencido">
                                        <i class="fa-solid fa-calendar-xmark"></i> Vencido
                                    </span>
                                <?php elseif ($lot['por_vencer']): ?>
                                    <span class="badge-pill badge-por-vencer">
                                        <i class="fa-solid fa-clock"></i> <?= $lot['dias_para_vencer'] ?> días
                                    </span>
                                <?php elseif (!empty($lot['fecha_vencimiento'])): ?>
                                    <span class="badge-pill badge-vigente">
                                        <i class="fa-solid fa-check"></i> Vigente
                                    </span>
                                <?php else: ?>
                                    <span class="badge-pill badge-sin-fecha">Sin Caducidad</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right;">$<?= number_format((float)$lot['costo_unitario'], 4) ?></td>
                            <td style="text-align: right;">
                                <span class="fw-bold <?= $lot['saldo'] > 0 ? 'text-dark' : 'text-muted' ?>">
                                    <?= number_format((float)$lot['saldo'], 2) ?> <?= htmlspecialchars($lot['unidad_medida'] ?? '') ?>
                                </span>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($lot['estado'] === 'activo'): ?>
                                    <span class="badge bg-success">Activo</span>
                                <?php elseif ($lot['estado'] === 'agotado'): ?>
                                    <span class="badge bg-secondary">Agotado</span>
                                <?php else: ?>
                                    <span class="badge bg-danger">Vencido</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div class="btn-group btn-group-sm">
                                    <button type="button" class="btn btn-outline-primary" title="Ajustar Lote" onclick="prepararAjusteLote(<?= $lot['insumo_id'] ?>, <?= $lot['id'] ?>, '<?= htmlspecialchars(addslashes($lot['lote']), ENT_QUOTES, 'UTF-8') ?>', <?= (float)$lot['costo_unitario'] ?>)">
                                        <i class="fa-solid fa-plus-minus"></i>
                                    </button>
                                    <a href="/Cycsa/publico/inventario/kardex?insumo_id=<?= $lot['insumo_id'] ?>" class="btn btn-outline-secondary" title="Ver Kárdex">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal para Ajuste sobre Lote Específico (Bootstrap 5) -->
<div class="modal fade" id="modalAjuste" tabindex="-1" aria-labelledby="modalAjusteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="/Cycsa/publico/inventario/ajuste">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="lote_id" id="modal_lote_id" value="">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="modalAjusteLabel"><i class="fa-solid fa-plus-minus me-2"></i>Ajustar Saldo de Lote</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Insumo *</label>
                        <select name="insumo_id" id="modal_insumo_id" class="form-select" required>
                            <option value="">-- Seleccione Insumo --</option>
                            <?php foreach ($insumos as $ins): ?>
                                <option value="<?= $ins['id'] ?>" <?= $insumoId == $ins['id'] ? 'selected' : '' ?>>
                                    [<?= htmlspecialchars($ins['codigo']) ?>] <?= htmlspecialchars($ins['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div id="infoLoteWrapper" class="alert alert-secondary py-2 small mb-3" style="display: none;">
                        <strong>Lote seleccionado:</strong> <span id="labelLoteNombre"></span>
                    </div>
                    <div class="row g-2 mb-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Tipo de Ajuste *</label>
                            <select name="tipo_ajuste" class="form-select" required>
                                <option value="ajuste_positivo">Ajuste Positivo (+ Entrada)</option>
                                <option value="ajuste_negativo">Ajuste Negativo (- Salida / Merma)</option>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold small text-muted">Cantidad *</label>
                            <input type="number" step="0.01" min="0.01" name="cantidad" class="form-control" required placeholder="0.00">
                        </div>
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Costo Unitario ($)</label>
                        <input type="number" step="0.0001" min="0" name="costo_unitario" id="modal_costo_unitario" class="form-control" placeholder="0.0000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Motivo / Justificación *</label>
                        <textarea name="motivo" class="form-control" rows="2" required placeholder="Ej: Conteo físico, vencimiento, desecho..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">
                        <i class="fa-solid fa-check me-1"></i>Guardar Ajuste
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let modalAjusteInstancia = null;

function abrirModalAjuste() {
    if (!modalAjusteInstancia) {
        modalAjusteInstancia = new bootstrap.Modal(document.getElementById('modalAjuste'));
    }
    modalAjusteInstancia.show();
}

function prepararAjusteLote(insumoId, loteId, loteCodigo, costoUnitario) {
    document.getElementById('modal_insumo_id').value = insumoId;
    document.getElementById('modal_lote_id').value = loteId;
    document.getElementById('labelLoteNombre').innerText = loteCodigo;
    document.getElementById('infoLoteWrapper').style.display = 'block';
    if (costoUnitario > 0) {
        document.getElementById('modal_costo_unitario').value = costoUnitario;
    }
    abrirModalAjuste();
}
</script>
