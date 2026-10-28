<?php
/**
 * Vista: app/Modulos/Proyectos/Vistas/detalle.php
 * TAREA-053: Detalle de Proyecto y Tarjetas de Costos por Cuadrante.
 */
$costos = $proyecto['costos'] ?? [];
$desglose = $costos['desglose'] ?? [];
$pct = (float)($costos['porcentaje_consumido'] ?? 0);
$presupuesto = (float)($costos['presupuesto_estimado'] ?? 0);
$costoTotal = (float)($costos['costo_total'] ?? 0);
$desviacion = (float)($costos['desviacion'] ?? 0);
$esSuperavit = $desviacion >= 0;

$compras = $desglose['compras'] ?? [];
$gastos = $desglose['gastos'] ?? [];
$insumos = $desglose['insumos'] ?? [];
$manoObra = $desglose['mano_obra'] ?? [];
?>
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h3 mb-0 text-gray-800 fw-bold">
                    <i class="fa-solid fa-helmet-safety text-primary me-2"></i><?= htmlspecialchars($proyecto['codigo']) ?> - <?= htmlspecialchars($proyecto['nombre']) ?>
                </h1>
                <?php if ($proyecto['estado'] === 'en_ejecucion'): ?>
                    <span class="badge bg-success">En Ejecución</span>
                <?php elseif ($proyecto['estado'] === 'planificado'): ?>
                    <span class="badge bg-warning text-dark">Planificado</span>
                <?php elseif ($proyecto['estado'] === 'completado'): ?>
                    <span class="badge bg-info">Completado</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Suspendido</span>
                <?php endif; ?>
            </div>
            <p class="text-muted mb-0 small">
                Cliente: <strong><?= htmlspecialchars($proyecto['cliente_nombre'] ?? 'Sin Cliente Asignado') ?></strong> 
                | Ubicación: <strong><?= htmlspecialchars($proyecto['ubicacion'] ?? 'No especificada') ?></strong>
                | Periodo: <?= htmlspecialchars($proyecto['fecha_inicio']) ?> al <?= htmlspecialchars($proyecto['fecha_fin'] ?? 'Indefinido') ?>
                | Cuenta Obra: <?= htmlspecialchars($proyecto['cuenta_codigo'] ?? '10104') ?> - <?= htmlspecialchars($proyecto['cuenta_nombre'] ?? 'Obras en Proceso') ?>
            </p>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <a href="/Cycsa/publico/proyectos" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-arrow-left me-1"></i>Regresar
            </a>
            <a href="/Cycsa/publico/proyectos/editar?id=<?= $proyecto['id'] ?>" class="btn btn-outline-primary btn-sm px-3">
                <i class="fa-solid fa-pen me-1"></i>Editar
            </a>

            <!-- Selector de Cambio de Estado Rápido -->
            <form method="POST" action="/Cycsa/publico/proyectos/cambiar-estado" class="d-inline">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <input type="hidden" name="id" value="<?= (int)$proyecto['id'] ?>">
                <select name="estado" class="form-select form-select-sm d-inline-block w-auto" onchange="this.form.submit()">
                    <option value="planificado" <?= $proyecto['estado'] === 'planificado' ? 'selected' : '' ?>>Planificado</option>
                    <option value="en_ejecucion" <?= $proyecto['estado'] === 'en_ejecucion' ? 'selected' : '' ?>>En Ejecución</option>
                    <option value="completado" <?= $proyecto['estado'] === 'completado' ? 'selected' : '' ?>>Completado</option>
                    <option value="suspendido" <?= $proyecto['estado'] === 'suspendido' ? 'selected' : '' ?>>Suspendido</option>
                </select>
            </form>

            <button type="button" class="btn btn-sm btn-outline-secondary px-3" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i>Imprimir Ficha
            </button>
        </div>
    </div>

    <!-- Mensajes Flash -->
    <?php if (!empty($_SESSION['flash_exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($_SESSION['flash_exito']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_exito']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- Resumen Presupuestario Maestro -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-4">
            <div class="row g-4 align-items-center">
                <div class="col-12 col-md-3 border-end">
                    <span class="text-muted small text-uppercase fw-bold">Presupuesto Estimado</span>
                    <h2 class="fw-bold text-dark mb-0 mt-1">C$ <?= number_format($presupuesto, 2) ?></h2>
                    <small class="text-muted">Techo contractual</small>
                </div>
                <div class="col-12 col-md-3 border-end">
                    <span class="text-muted small text-uppercase fw-bold">Costo Total Acumulado</span>
                    <h2 class="fw-bold mb-0 mt-1 <?= ($costoTotal > $presupuesto && $presupuesto > 0) ? 'text-danger' : 'text-primary' ?>">
                        C$ <?= number_format($costoTotal, 2) ?>
                    </h2>
                    <small class="text-muted">Imputación 4 cuadrantes</small>
                </div>
                <div class="col-12 col-md-3 border-end">
                    <span class="text-muted small text-uppercase fw-bold">Desviación Presupuestaria</span>
                    <h2 class="fw-bold mb-0 mt-1 <?= $esSuperavit ? 'text-success' : 'text-danger' ?>">
                        C$ <?= number_format(abs($desviacion), 2) ?>
                    </h2>
                    <span class="badge <?= $esSuperavit ? 'bg-success' : 'bg-danger' ?> mt-1">
                        <i class="fa-solid <?= $esSuperavit ? 'fa-arrow-trend-down me-1' : 'fa-arrow-trend-up me-1' ?>"></i>
                        <?= $esSuperavit ? 'Superávit / Margen Disponible' : 'Déficit / Sobrecosto' ?>
                    </span>
                </div>
                <div class="col-12 col-md-3">
                    <span class="text-muted small text-uppercase fw-bold">Ejecución Financiera</span>
                    <div class="d-flex align-items-baseline gap-2 mt-1">
                        <h2 class="fw-bold text-dark mb-0"><?= number_format($pct, 1) ?>%</h2>
                        <small class="text-muted">consumido</small>
                    </div>
                    <div class="progress mt-2" style="height: 10px;">
                        <?php
                        $barColor = $pct > 100 ? 'bg-danger' : ($pct > 80 ? 'bg-warning' : 'bg-success');
                        $barWidth = min(100, $pct);
                        ?>
                        <div class="progress-bar <?= $barColor ?>" style="width: <?= $barWidth ?>%;"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- TARJETAS DE COSTOS POR CUADRANTE (Fase 10 · Imputación Transversal) -->
    <h5 class="fw-bold text-gray-800 mb-3">
        <i class="fa-solid fa-cubes-stacked me-2 text-primary"></i>Desglose por Cuadrante de Costos Directos
    </h5>

    <div class="row g-3 mb-4">
        <!-- Cuadrante 1: Compras y OC -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 border-start border-primary border-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-bold">1. Compras & OC</span>
                    <div class="bg-primary-subtle text-primary p-2 rounded-3">
                        <i class="fa-solid fa-cart-shopping fa-lg"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">C$ <?= number_format($costos['compras_oc'], 2) ?></h3>
                <div class="d-flex justify-content-between align-items-center text-muted small mt-auto pt-2">
                    <span><?= count($compras) ?> partidas de compra</span>
                    <span class="fw-semibold text-primary">
                        <?= $costoTotal > 0 ? number_format(($costos['compras_oc'] / $costoTotal) * 100, 1) : 0 ?>% del costo
                    </span>
                </div>
            </div>
        </div>

        <!-- Cuadrante 2: Gastos Directos CXP -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 border-start border-success border-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-bold">2. Gastos Directos (CXP)</span>
                    <div class="bg-success-subtle text-success p-2 rounded-3">
                        <i class="fa-solid fa-file-invoice-dollar fa-lg"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">C$ <?= number_format($costos['gastos_directos'], 2) ?></h3>
                <div class="d-flex justify-content-between align-items-center text-muted small mt-auto pt-2">
                    <span><?= count($gastos) ?> facturas / gastos</span>
                    <span class="fw-semibold text-success">
                        <?= $costoTotal > 0 ? number_format(($costos['gastos_directos'] / $costoTotal) * 100, 1) : 0 ?>% del costo
                    </span>
                </div>
            </div>
        </div>

        <!-- Cuadrante 3: Insumos de Inventario / Kárdex -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 border-start border-warning border-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-bold">3. Insumos & Kárdex</span>
                    <div class="bg-warning-subtle text-warning p-2 rounded-3">
                        <i class="fa-solid fa-boxes-stacked fa-lg"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">C$ <?= number_format($costos['insumos_kardex'], 2) ?></h3>
                <div class="d-flex justify-content-between align-items-center text-muted small mt-auto pt-2">
                    <span><?= count($insumos) ?> despachos de bodega</span>
                    <span class="fw-semibold text-warning">
                        <?= $costoTotal > 0 ? number_format(($costos['insumos_kardex'] / $costoTotal) * 100, 1) : 0 ?>% del costo
                    </span>
                </div>
            </div>
        </div>

        <!-- Cuadrante 4: Mano de Obra y Nómina -->
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 h-100 p-3 border-start border-info border-4">
                <div class="d-flex align-items-center justify-content-between mb-2">
                    <span class="text-muted small text-uppercase fw-bold">4. Mano de Obra</span>
                    <div class="bg-info-subtle text-info p-2 rounded-3">
                        <i class="fa-solid fa-users-gear fa-lg"></i>
                    </div>
                </div>
                <h3 class="fw-bold text-dark mb-1">C$ <?= number_format($costos['mano_obra'], 2) ?></h3>
                <div class="d-flex justify-content-between align-items-center text-muted small mt-auto pt-2">
                    <span><?= count($manoObra) ?> asignaciones nómina</span>
                    <span class="fw-semibold text-info">
                        <?= $costoTotal > 0 ? number_format(($costos['mano_obra'] / $costoTotal) * 100, 1) : 0 ?>% del costo
                    </span>
                </div>
            </div>
        </div>
    </div>

    <!-- Pestañas de Detalle por Partida Imputada -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white border-bottom p-0">
            <ul class="nav nav-tabs card-header-tabs m-0 px-3 border-0" id="tabsCostos" role="tablist">
                <li class="nav-item">
                    <button class="nav-link active py-3 px-3 fw-semibold border-0 border-bottom border-primary border-3" id="tab-compras-btn" data-bs-toggle="tab" data-bs-target="#tab-compras" type="button">
                        <i class="fa-solid fa-cart-shopping me-1 text-primary"></i>Compras y OC (<?= count($compras) ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 px-3 fw-semibold text-muted border-0" id="tab-gastos-btn" data-bs-toggle="tab" data-bs-target="#tab-gastos" type="button">
                        <i class="fa-solid fa-file-invoice-dollar me-1 text-success"></i>Gastos Directos CXP (<?= count($gastos) ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 px-3 fw-semibold text-muted border-0" id="tab-insumos-btn" data-bs-toggle="tab" data-bs-target="#tab-insumos" type="button">
                        <i class="fa-solid fa-boxes-stacked me-1 text-warning"></i>Insumos & Kárdex (<?= count($insumos) ?>)
                    </button>
                </li>
                <li class="nav-item">
                    <button class="nav-link py-3 px-3 fw-semibold text-muted border-0" id="tab-nomina-btn" data-bs-toggle="tab" data-bs-target="#tab-nomina" type="button">
                        <i class="fa-solid fa-users-gear me-1 text-info"></i>Mano de Obra & Planilla (<?= count($manoObra) ?>)
                    </button>
                </li>
            </ul>
        </div>
        <div class="card-body p-0">
            <div class="tab-content" id="tabsCostosContent">
                <!-- Tab Compras -->
                <div class="tab-pane fade show active" id="tab-compras" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">OC #</th>
                                    <th>Fecha</th>
                                    <th>Proveedor</th>
                                    <th>Descripción / Ítem</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Costo Unit.</th>
                                    <th class="text-end pe-3">Total Imputado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($compras)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            No hay órdenes de compra imputadas a este proyecto.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($compras as $c): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold font-monospace text-primary">
                                                <?= htmlspecialchars($c['numero_orden'] ?? 'OC-LOTE-' . $c['orden_compra_id']) ?>
                                            </td>
                                            <td><small class="text-muted"><?= htmlspecialchars($c['orden_fecha'] ?? substr($c['created_at'], 0, 10)) ?></small></td>
                                            <td><?= htmlspecialchars($c['proveedor_nombre'] ?? 'Proveedor General') ?></td>
                                            <td><?= htmlspecialchars($c['descripcion'] ?? 'Material/Suministro') ?></td>
                                            <td class="text-end"><?= number_format((float)$c['cantidad'], 2) ?></td>
                                            <td class="text-end">C$ <?= number_format((float)$c['costo_unitario'], 2) ?></td>
                                            <td class="text-end pe-3 fw-bold text-dark">
                                                C$ <?= number_format((float)($c['total'] > 0 ? $c['total'] : $c['subtotal']), 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Gastos CXP -->
                <div class="tab-pane fade" id="tab-gastos" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Factura / CXP</th>
                                    <th>Fecha</th>
                                    <th>Proveedor</th>
                                    <th>Cuenta Contable</th>
                                    <th>Concepto de Gasto</th>
                                    <th class="text-end">Monto Neto</th>
                                    <th class="text-end pe-3">Total Imputado</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($gastos)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            No hay líneas de cuentas por pagar imputadas a este proyecto.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($gastos as $g): ?>
                                        <tr>
                                            <td class="ps-3 fw-bold font-monospace text-success">
                                                <?= htmlspecialchars($g['numero_factura'] ?? 'CXP-#' . $g['cxp_id']) ?>
                                            </td>
                                            <td><small class="text-muted"><?= htmlspecialchars($g['fecha_emision'] ?? substr($g['created_at'], 0, 10)) ?></small></td>
                                            <td><?= htmlspecialchars($g['proveedor_nombre'] ?? 'Acreedor') ?></td>
                                            <td><small class="badge bg-light text-dark border"><?= htmlspecialchars($g['cuenta_codigo'] ?? '') ?> - <?= htmlspecialchars($g['cuenta_nombre'] ?? 'Gasto Directo') ?></small></td>
                                            <td><?= htmlspecialchars($g['concepto']) ?></td>
                                            <td class="text-end">C$ <?= number_format((float)$g['monto'], 2) ?></td>
                                            <td class="text-end pe-3 fw-bold text-dark">
                                                C$ <?= number_format((float)($g['total'] > 0 ? $g['total'] : $g['monto']), 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Insumos / Kárdex -->
                <div class="tab-pane fade" id="tab-insumos" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Movimiento #</th>
                                    <th>Fecha</th>
                                    <th>Insumo / Material</th>
                                    <th>Tipo de Movimiento</th>
                                    <th class="text-end">Cantidad</th>
                                    <th class="text-end">Costo Unit.</th>
                                    <th class="text-end pe-3">Subtotal Consumido</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($insumos)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            No hay consumos de bodega o kárdex imputados a este proyecto.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($insumos as $in): ?>
                                        <tr>
                                            <td class="ps-3 font-monospace text-muted">#<?= $in['id'] ?></td>
                                            <td><small class="text-muted"><?= htmlspecialchars(substr($in['created_at'], 0, 10)) ?></small></td>
                                            <td>
                                                <strong><?= htmlspecialchars($in['insumo_nombre'] ?? 'Insumo #' . $in['insumo_id']) ?></strong>
                                                <small class="text-muted d-block font-monospace"><?= htmlspecialchars($in['insumo_codigo'] ?? '') ?></small>
                                            </td>
                                            <td>
                                                <span class="badge bg-warning-subtle text-warning border border-warning-subtle">
                                                    <?= htmlspecialchars($in['tipo_movimiento']) ?>
                                                </span>
                                            </td>
                                            <td class="text-end"><?= number_format((float)$in['cantidad'], 2) ?> <?= htmlspecialchars($in['unidad_medida'] ?? '') ?></td>
                                            <td class="text-end">C$ <?= number_format((float)$in['costo_unitario'], 4) ?></td>
                                            <td class="text-end pe-3 fw-bold text-dark">
                                                C$ <?= number_format((float)$in['subtotal'], 2) ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Tab Mano de Obra / Nómina -->
                <div class="tab-pane fade" id="tab-nomina" role="tabpanel">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th class="ps-3">Planilla / Nómina</th>
                                    <th>Periodo</th>
                                    <th>Colaborador</th>
                                    <th>Puesto / Función</th>
                                    <th class="text-end">Salario Base</th>
                                    <th class="text-end">Horas Extras</th>
                                    <th class="text-end pe-3">Total Devengado (Costo)</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($manoObra)): ?>
                                    <tr>
                                        <td colspan="7" class="text-center py-4 text-muted">
                                            No hay cargos de nómina o mano de obra imputados a este proyecto.
                                        </td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($manoObra as $mo): ?>
                                        <tr>
                                            <td class="ps-3 font-monospace text-info fw-bold">
                                                <?= htmlspecialchars($mo['nomina_codigo'] ?? 'NOM-#' . $mo['nomina_id']) ?>
                                            </td>
                                            <td><small class="text-muted"><?= htmlspecialchars($mo['periodo_inicio'] ?? '') ?> al <?= htmlspecialchars($mo['periodo_fin'] ?? '') ?></small></td>
                                            <td>
                                                <strong><?= htmlspecialchars($mo['empleado_nombre'] ?? 'Empleado #' . $mo['empleado_id']) ?></strong>
                                                <small class="text-muted d-block font-monospace"><?= htmlspecialchars($mo['empleado_cedula'] ?? '') ?></small>
                                            </td>
                                            <td><small class="text-muted"><?= htmlspecialchars($mo['empleado_puesto'] ?? 'Operario') ?></small></td>
                                            <td class="text-end">C$ <?= number_format((float)$mo['salario_base'], 2) ?></td>
                                            <td class="text-end">C$ <?= number_format((float)$mo['horas_extras_monto'], 2) ?></td>
                                            <td class="text-end pe-3 fw-bold text-dark">
                                                C$ <?= number_format((float)$mo['total_devengado'], 2) ?>
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
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const triggerTabList = [].slice.call(document.querySelectorAll('#tabsCostos button'));
    triggerTabList.forEach(function (triggerEl) {
        triggerEl.addEventListener('click', function (event) {
            triggerTabList.forEach(btn => {
                btn.classList.remove('active', 'border-primary', 'border-3');
                btn.classList.add('text-muted');
            });
            this.classList.add('active', 'border-primary', 'border-3');
            this.classList.remove('text-muted');
        });
    });
});
</script>
