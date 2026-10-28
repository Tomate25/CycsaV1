<?php
/**
 * Vista Principal: Resumen de Stock, Insumos y Badges de Alerta (TAREA-048)
 */
?>
<style>
    .inv-kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
        gap: 16px;
        margin-bottom: 24px;
    }
    .inv-kpi-card {
        background: #ffffff;
        border-radius: 10px;
        padding: 18px 20px;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        border: 1px solid #e2e8f0;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }
    .inv-kpi-title {
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        color: #64748b;
        letter-spacing: 0.5px;
        margin-bottom: 6px;
    }
    .inv-kpi-value {
        font-size: 26px;
        font-weight: 800;
        color: #0f172a;
        line-height: 1;
    }
    .inv-kpi-icon {
        width: 48px;
        height: 48px;
        border-radius: 10px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 20px;
    }
    .icon-blue { background: #eff6ff; color: #2563eb; }
    .icon-amber { background: #fffbeb; color: #d97706; }
    .icon-red { background: #fef2f2; color: #dc2626; }
    .icon-green { background: #f0fdf4; color: #16a34a; }

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
    .badge-ok { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-alerta { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; animation: pulse 2s infinite; }

    @keyframes pulse {
        0%, 100% { opacity: 1; }
        50% { opacity: 0.75; }
    }


</style>

<div class="content-header mb-3">
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
        <div>
            <h2 class="fw-bold mb-1" style="color: #0f172a;">
                <i class="fa-solid fa-boxes-stacked me-2" style="color: #0284c7;"></i>Inventario y Kárdex
            </h2>
            <p class="text-muted small mb-0">Control de existencias, gestión de lotes, vencimientos y consumo por ensayo PEPS/FIFO (ISO 17025)</p>
        </div>
        <div class="d-flex gap-2">
            <button class="btn btn-primary" onclick="abrirModalAjuste()">
                <i class="fa-solid fa-plus-minus me-1"></i>Registrar Ajuste
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

<!-- Tarjetas KPI de Estado de Inventario -->
<div class="inv-kpi-grid">
    <div class="inv-kpi-card">
        <div>
            <div class="inv-kpi-title">Total Insumos</div>
            <div class="inv-kpi-value"><?= count($insumos) ?></div>
        </div>
        <div class="inv-kpi-icon icon-blue">
            <i class="fa-solid fa-cubes"></i>
        </div>
    </div>
    <div class="inv-kpi-card">
        <div>
            <div class="inv-kpi-title">Bajo Stock Mínimo</div>
            <div class="inv-kpi-value" style="color: <?= ($alertas['conteo_bajo_stock'] ?? 0) > 0 ? '#dc2626' : '#16a34a' ?>;">
                <?= $alertas['conteo_bajo_stock'] ?? 0 ?>
            </div>
        </div>
        <div class="inv-kpi-icon icon-red">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
    </div>
    <div class="inv-kpi-card">
        <div>
            <div class="inv-kpi-title">Por Vencer (&le; 30d)</div>
            <div class="inv-kpi-value" style="color: <?= ($alertas['conteo_por_vencer'] ?? 0) > 0 ? '#d97706' : '#0f172a' ?>;">
                <?= $alertas['conteo_por_vencer'] ?? 0 ?>
            </div>
        </div>
        <div class="inv-kpi-icon icon-amber">
            <i class="fa-solid fa-clock"></i>
        </div>
    </div>
    <div class="inv-kpi-card">
        <div>
            <div class="inv-kpi-title">Lotes Vencidos</div>
            <div class="inv-kpi-value" style="color: <?= ($alertas['conteo_vencidos'] ?? 0) > 0 ? '#dc2626' : '#16a34a' ?>;">
                <?= $alertas['conteo_vencidos'] ?? 0 ?>
            </div>
        </div>
        <div class="inv-kpi-icon icon-red">
            <i class="fa-solid fa-calendar-xmark"></i>
        </div>
    </div>
</div>

<!-- Pestañas de Navegación del Módulo -->
<div class="inv-tabs">
    <a href="/Cycsa/publico/inventario" class="inv-tab-item active">
        <i class="fa-solid fa-layer-group"></i>Resumen de Stock e Insumos
    </a>
    <a href="/Cycsa/publico/inventario/lotes" class="inv-tab-item">
        <i class="fa-solid fa-tags"></i>Control de Lotes y Caducidades
        <?php if (($alertas['conteo_por_vencer'] ?? 0) + ($alertas['conteo_vencidos'] ?? 0) > 0): ?>
            <span class="badge bg-danger rounded-pill"><?= ($alertas['conteo_por_vencer'] ?? 0) + ($alertas['conteo_vencidos'] ?? 0) ?></span>
        <?php endif; ?>
    </a>
    <a href="/Cycsa/publico/inventario/kardex" class="inv-tab-item">
        <i class="fa-solid fa-clock-rotate-left"></i>Kárdex de Movimientos
    </a>
</div>

<!-- Barra de Búsqueda y Filtros -->
<div class="bg-white p-3 rounded-3 shadow-sm border mb-4">
    <form method="GET" action="/Cycsa/publico/inventario" class="row g-2 align-items-center">
        <div class="col-md-5">
            <div class="input-group">
                <span class="input-group-text bg-light"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                <input type="text" name="q" class="form-control" placeholder="Buscar por código o nombre de insumo..." value="<?= htmlspecialchars($filtro ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
        <div class="col-md-3 d-flex gap-2">
            <button type="submit" class="btn btn-outline-primary">Filtrar</button>
            <?php if (!empty($filtro)): ?>
                <a href="/Cycsa/publico/inventario" class="btn btn-outline-secondary">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tabla de Insumos -->
<div class="bg-white rounded-3 shadow-sm border overflow-hidden p-3">
    <div class="table-responsive">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th style="width: 120px;">Código</th>
                    <th>Insumo / Reactivo</th>
                    <th style="width: 120px;">Unidad</th>
                    <th style="width: 140px; text-align: right;">Saldo Actual</th>
                    <th style="width: 140px; text-align: right;">Stock Mínimo</th>
                    <th style="width: 160px; text-align: center;">Estado Alerta</th>
                    <th style="width: 180px; text-align: center;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($insumos)): ?>
                    <tr>
                        <td colspan="7" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-inbox fa-2x mb-2 d-block"></i>
                            No se encontraron insumos registrados en el inventario.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($insumos as $ins): ?>
                        <tr>
                            <td>
                                <span class="badge bg-secondary font-monospace"><?= htmlspecialchars($ins['codigo'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <div class="fw-bold text-dark"><?= htmlspecialchars($ins['nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                                <small class="text-muted">Lotes activos: <?= (int)($ins['total_lotes_activos'] ?? 0) ?></small>
                            </td>
                            <td><?= htmlspecialchars($ins['unidad_medida'] ?? 'unidad', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="text-align: right;">
                                <span class="fw-bold fs-6 <?= $ins['alerta_stock_minimo'] ? 'text-danger' : 'text-success' ?>">
                                    <?= number_format((float)$ins['saldo'], 2) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <span class="text-muted"><?= number_format((float)$ins['stock_minimo'], 2) ?></span>
                            </td>
                            <td style="text-align: center;">
                                <?php if ($ins['alerta_stock_minimo']): ?>
                                    <span class="badge-pill badge-alerta">
                                        <i class="fa-solid fa-triangle-exclamation"></i> Stock Bajo
                                    </span>
                                <?php else: ?>
                                    <span class="badge-pill badge-ok">
                                        <i class="fa-solid fa-check"></i> Normal
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <div class="btn-group btn-group-sm">
                                    <a href="/Cycsa/publico/inventario/lotes?insumo_id=<?= $ins['id'] ?>" class="btn btn-outline-secondary" title="Ver Lotes">
                                        <i class="fa-solid fa-tags"></i>
                                    </a>
                                    <a href="/Cycsa/publico/inventario/kardex?insumo_id=<?= $ins['id'] ?>" class="btn btn-outline-secondary" title="Ver Kárdex">
                                        <i class="fa-solid fa-clock-rotate-left"></i>
                                    </a>
                                    <button type="button" class="btn btn-outline-primary" title="Ajuste de Stock" onclick="prepararAjuste(<?= $ins['id'] ?>, '<?= htmlspecialchars(addslashes($ins['nombre']), ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="fa-solid fa-plus-minus"></i>
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

<!-- Modal para Registrar Ajuste Manual (Bootstrap 5) -->
<div class="modal fade" id="modalAjuste" tabindex="-1" aria-labelledby="modalAjusteLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="/Cycsa/publico/inventario/ajuste">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="modalAjusteLabel"><i class="fa-solid fa-plus-minus me-2"></i>Registrar Ajuste de Inventario</h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Insumo / Reactivo *</label>
                        <select name="insumo_id" id="modal_insumo_id" class="form-select" required>
                            <option value="">-- Seleccione un Insumo --</option>
                            <?php foreach ($insumos as $ins): ?>
                                <option value="<?= $ins['id'] ?>">
                                    [<?= htmlspecialchars($ins['codigo']) ?>] <?= htmlspecialchars($ins['nombre']) ?> (Saldo: <?= number_format($ins['saldo'], 2) ?> <?= htmlspecialchars($ins['unidad_medida']) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
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
                        <label class="form-label fw-bold small text-muted">Costo Unitario ($) (Opcional en Salida)</label>
                        <input type="number" step="0.0001" min="0" name="costo_unitario" class="form-control" placeholder="0.0000">
                    </div>
                    <div class="mb-3">
                        <label class="form-label fw-bold small text-muted">Motivo / Justificación *</label>
                        <textarea name="motivo" class="form-control" rows="2" required placeholder="Ej: Conteo físico periódico, merma por rotura de envase, reactivo vencido..."></textarea>
                    </div>
                </div>
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-3 fw-bold">
                        <i class="fa-solid fa-check me-1"></i>Registrar en Kárdex
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

function prepararAjuste(insumoId, insumoNombre) {
    const select = document.getElementById('modal_insumo_id');
    if (select) {
        select.value = insumoId;
    }
    abrirModalAjuste();
}
</script>
