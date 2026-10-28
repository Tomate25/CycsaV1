<?php
/**
 * Vista de Trazabilidad de Movimientos e Historial Kárdex (TAREA-048)
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

    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13px; }
    .tabla-cycsa th { background-color: #f8f9fa; color: #475569; padding: 12px 12px; text-align: left; font-weight: 700; border-bottom: 2px solid #cbd5e1; text-transform: uppercase; font-size: 11px; }
    .tabla-cycsa td { padding: 10px 12px; border-bottom: 1px solid #e2e8f0; vertical-align: middle; color: #1e293b; }
    .tabla-cycsa tbody tr:hover { background-color: #f1f5f9; }

    .badge-mov {
        display: inline-flex;
        align-items: center;
        gap: 4px;
        padding: 3px 8px;
        border-radius: 6px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.3px;
    }
    .mov-entrada_compra { background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .mov-consumo_ensayo { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .mov-salida_consumo { background: #e0e7ff; color: #3730a3; border: 1px solid #c7d2fe; }
    .mov-ajuste_positivo { background: #d1fae5; color: #065f46; border: 1px solid #a7f3d0; }
    .mov-ajuste_negativo { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .mov-merma { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }
</style>

<div class="content-header mb-3">
    <!-- Breadcrumb de Navegación -->
    <?php
    $migas = [
        ['label' => 'Inventario', 'url' => '/Cycsa/publico/inventario'],
        ['label' => 'Kárdex de Movimientos']
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
        <div>
            <h2 class="fw-bold mb-1" style="color: #0f172a;">
                <i class="fa-solid fa-clock-rotate-left me-2" style="color: #0284c7;"></i>Kárdex de Movimientos
            </h2>
            <p class="text-muted small mb-0">Trazabilidad inmutable de entradas, salidas y consumos por ensayo (ISO 17025)</p>
        </div>
    </div>

    <!-- Barra de Subnavegación del Módulo -->
    <?php require __DIR__ . '/parciales/subnav_inventario.php'; ?>
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


<!-- Barra de Filtros de Kárdex -->
<div class="bg-white p-3 rounded-3 shadow-sm border mb-4">
    <form method="GET" action="/Cycsa/publico/inventario/kardex" class="row g-2 align-items-end">
        <div class="col-md-4">
            <label class="form-label small fw-bold text-muted mb-1">Insumo:</label>
            <select name="insumo_id" class="form-select form-select-sm">
                <option value="0">-- Todos los Insumos --</option>
                <?php foreach ($insumos as $ins): ?>
                    <option value="<?= $ins['id'] ?>" <?= $insumoId == $ins['id'] ? 'selected' : '' ?>>
                        [<?= htmlspecialchars($ins['codigo']) ?>] <?= htmlspecialchars($ins['nombre']) ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted mb-1">Fecha Desde:</label>
            <input type="date" name="fecha_desde" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaDesde ?? '') ?>">
        </div>
        <div class="col-md-3">
            <label class="form-label small fw-bold text-muted mb-1">Fecha Hasta:</label>
            <input type="date" name="fecha_hasta" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaHasta ?? '') ?>">
        </div>
        <div class="col-md-2 d-flex gap-2">
            <button type="submit" class="btn btn-sm btn-primary w-100">
                <i class="fa-solid fa-filter me-1"></i>Filtrar
            </button>
            <?php if ($insumoId > 0 || !empty($fechaDesde) || !empty($fechaHasta)): ?>
                <a href="/Cycsa/publico/inventario/kardex" class="btn btn-sm btn-outline-secondary" title="Limpiar Filtros">
                    <i class="fa-solid fa-xmark"></i>
                </a>
            <?php endif; ?>
        </div>
    </form>
</div>

<!-- Tabla de Movimientos de Kárdex -->
<div class="bg-white rounded-3 shadow-sm border overflow-hidden p-3">
    <div class="table-responsive">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th style="width: 70px;">ID</th>
                    <th style="width: 140px;">Fecha y Hora</th>
                    <th>Insumo</th>
                    <th style="width: 130px;">Lote #</th>
                    <th style="width: 140px; text-align: center;">Tipo Movimiento</th>
                    <th style="width: 100px; text-align: right;">Cantidad</th>
                    <th style="width: 100px; text-align: right;">Costo U.</th>
                    <th style="width: 90px; text-align: right;">Saldo Ant.</th>
                    <th style="width: 90px; text-align: right;">Saldo Nvo.</th>
                    <th style="width: 120px;">Referencia</th>
                    <th style="width: 120px;">Usuario</th>
                    <th>Observaciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (empty($movimientos)): ?>
                    <tr>
                        <td colspan="12" class="text-center py-4 text-muted">
                            <i class="fa-solid fa-clock-rotate-left fa-2x mb-2 d-block"></i>
                            No hay movimientos registrados en el Kárdex para los filtros aplicados.
                        </td>
                    </tr>
                <?php else: ?>
                    <?php foreach ($movimientos as $m): ?>
                        <tr>
                            <td class="text-muted">#<?= $m['id'] ?></td>
                            <td><small><?= htmlspecialchars($m['created_at']) ?></small></td>
                            <td>
                                <span class="badge bg-light text-dark border font-monospace me-1"><?= htmlspecialchars($m['insumo_codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="fw-semibold"><?= htmlspecialchars($m['insumo_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <?php if (!empty($m['lote_codigo'])): ?>
                                    <span class="badge bg-secondary font-monospace"><?= htmlspecialchars($m['lote_codigo']) ?></span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: center;">
                                <span class="badge-mov mov-<?= htmlspecialchars($m['tipo_movimiento']) ?>">
                                    <?= str_replace('_', ' ', htmlspecialchars($m['tipo_movimiento'])) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <span class="fw-bold <?= in_array($m['tipo_movimiento'], ['entrada_compra', 'ajuste_positivo']) ? 'text-success' : 'text-danger' ?>">
                                    <?= in_array($m['tipo_movimiento'], ['entrada_compra', 'ajuste_positivo']) ? '+' : '-' ?><?= number_format((float)$m['cantidad'], 2) ?>
                                </span>
                                <small class="text-muted"><?= htmlspecialchars($m['unidad_medida'] ?? '') ?></small>
                            </td>
                            <td style="text-align: right;">$<?= number_format((float)$m['costo_unitario'], 4) ?></td>
                            <td style="text-align: right;" class="text-muted"><?= number_format((float)$m['saldo_anterior'], 2) ?></td>
                            <td style="text-align: right;" class="fw-bold text-dark"><?= number_format((float)$m['saldo_nuevo'], 2) ?></td>
                            <td>
                                <?php if (!empty($m['referencia_tipo'])): ?>
                                    <span class="badge bg-light text-dark border">
                                        <?= htmlspecialchars($m['referencia_tipo']) ?> <?= !empty($m['referencia_id']) ? '#' . (int)$m['referencia_id'] : '' ?>
                                    </span>
                                <?php else: ?>
                                    <span class="text-muted">-</span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <small class="text-muted"><?= htmlspecialchars($m['usuario_nombre'] ?? 'Sistema') ?></small>
                            </td>
                            <td>
                                <small class="text-muted"><?= htmlspecialchars($m['observaciones'] ?? '') ?></small>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
