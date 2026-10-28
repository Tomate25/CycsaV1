<?php
/**
 * Vista de Detalle y Ficha Técnica de Activo Fijo - CYCSA ERP (TAREA-051)
 */
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb de Navegación -->
    <?php
    $migas = [
        ['label' => 'Activos Fijos', 'url' => '/Cycsa/publico/activos-fijos'],
        ['label' => 'Ficha Técnica ' . ($activo['codigo'] ?? '')]
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-cube text-primary me-2"></i>Ficha de Activo: <?= htmlspecialchars($activo['codigo']) ?>
            </h1>
            <p class="text-muted mb-0 small"><?= htmlspecialchars($activo['descripcion']) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="/Cycsa/publico/activos-fijos" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-arrow-left me-1"></i>Regresar al Catálogo
            </a>
            <a href="/Cycsa/publico/activos-fijos/editar?id=<?= $activo['id'] ?>" class="btn btn-primary btn-sm px-3">
                <i class="fa-solid fa-pen-to-square me-1"></i>Editar Activo
            </a>
        </div>
    </div>

    <!-- Resumen de Métricas -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3">
                <span class="text-muted small text-uppercase fw-bold">Valor de Adquisición</span>
                <h4 class="fw-bold text-dark mt-1">C$ <?= number_format($activo['valor_adquisicion'], 2) ?></h4>
                <span class="text-muted small">Fecha: <?= htmlspecialchars($activo['fecha_adquisicion']) ?></span>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3">
                <span class="text-muted small text-uppercase fw-bold">Depreciación Acumulada</span>
                <h4 class="fw-bold text-danger mt-1">C$ <?= number_format($activo['depreciacion_acumulada'], 2) ?></h4>
                <div class="progress mt-1" style="height: 4px;">
                    <div class="progress-bar bg-danger" style="width: <?= $activo['porcentaje_depreciado'] ?>%"></div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3">
                <span class="text-muted small text-uppercase fw-bold">Valor en Libros Actual</span>
                <h4 class="fw-bold text-success mt-1">C$ <?= number_format($activo['valor_en_libros'], 2) ?></h4>
                <span class="text-muted small">Residual: C$ <?= number_format($activo['valor_residual'], 2) ?></span>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm p-3">
                <span class="text-muted small text-uppercase fw-bold">Cuota Mensual</span>
                <h4 class="fw-bold text-primary mt-1">C$ <?= number_format($activo['cuota_mensual'], 2) ?></h4>
                <span class="text-muted small"><?= $activo['meses_restantes'] ?> de <?= $activo['vida_util_meses'] ?> meses restantes</span>
            </div>
        </div>
    </div>

    <div class="row g-4 mb-4">
        <!-- Parámetros del Activo -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-circle-info text-primary me-2"></i>Parámetros Técnicos
                    </h5>
                </div>
                <div class="card-body p-4">
                    <table class="table table-borderless table-sm mb-0">
                        <tr>
                            <th class="text-muted w-50">Código:</th>
                            <td class="fw-bold font-monospace"><?= htmlspecialchars($activo['codigo']) ?></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Categoría:</th>
                            <td><span class="badge bg-primary text-uppercase"><?= str_replace('_', ' ', $activo['categoria']) ?></span></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Estado Actual:</th>
                            <td><span class="badge bg-secondary text-uppercase"><?= str_replace('_', ' ', $activo['estado']) ?></span></td>
                        </tr>
                        <tr>
                            <th class="text-muted">Vida Útil Total:</th>
                            <td><?= $activo['vida_util_meses'] ?> meses (<?= number_format($activo['vida_util_meses'] / 12, 1) ?> años)</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Meses Depreciados:</th>
                            <td><?= $activo['meses_depreciados'] ?> meses</td>
                        </tr>
                        <tr>
                            <th class="text-muted">Porcentaje Depreciado:</th>
                            <td class="fw-bold text-danger"><?= $activo['porcentaje_depreciado'] ?>%</td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>

        <!-- Cuentas Contables Imputadas -->
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-white py-3">
                    <h5 class="fw-bold mb-0 text-dark">
                        <i class="fa-solid fa-receipt text-primary me-2"></i>Puente Contable
                    </h5>
                </div>
                <div class="card-body p-4">
                    <div class="mb-3">
                        <span class="text-muted small fw-bold">Cuenta de Activo:</span>
                        <div class="fw-semibold text-dark mt-1">
                            <?= !empty($activo['cuenta_activo_codigo']) ? htmlspecialchars($activo['cuenta_activo_codigo'] . ' - ' . $activo['cuenta_activo_nombre']) : '<span class="text-muted fst-italic">Por defecto (Según categoría)</span>' ?>
                        </div>
                    </div>
                    <div class="mb-3">
                        <span class="text-muted small fw-bold">Cuenta Depreciación Acumulada (Haber):</span>
                        <div class="fw-semibold text-dark mt-1">
                            <?= !empty($activo['cuenta_dep_codigo']) ? htmlspecialchars($activo['cuenta_dep_codigo'] . ' - ' . $activo['cuenta_dep_nombre']) : '<span class="text-muted fst-italic">Por defecto (Según categoría)</span>' ?>
                        </div>
                    </div>
                    <div class="mb-0">
                        <span class="text-muted small fw-bold">Cuenta Gasto Depreciación (Debe):</span>
                        <div class="fw-semibold text-dark mt-1">
                            <?= !empty($activo['cuenta_gasto_codigo']) ? htmlspecialchars($activo['cuenta_gasto_codigo'] . ' - ' . $activo['cuenta_gasto_nombre']) : '<span class="text-muted fst-italic">Por defecto (Según categoría)</span>' ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Historial de Depreciaciones de este Activo -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-timeline text-primary me-2"></i>Historial de Depreciaciones del Activo
            </h5>
        </div>
        <div class="card-body p-0">
            <?php if (empty($depreciaciones)): ?>
                <div class="text-center py-4 text-muted">
                    <i class="fa-solid fa-clock-rotate-left fa-2x mb-2"></i>
                    <p class="small mb-0">Este activo aún no tiene amortizaciones mensuales registradas.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="ps-3">Periodo</th>
                                <th>Fecha Proceso</th>
                                <th class="text-end">Cuota Amortizada</th>
                                <th class="text-end">Dep. Acumulada Nueva</th>
                                <th class="text-end">Valor en Libros Resultante</th>
                                <th class="text-center pe-3">Asiento Contable</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($depreciaciones as $d): ?>
                                <tr>
                                    <td class="ps-3 fw-bold font-monospace text-primary"><?= htmlspecialchars($d['periodo']) ?></td>
                                    <td><?= htmlspecialchars($d['fecha_proceso']) ?></td>
                                    <td class="text-end text-danger fw-bold font-monospace">C$ <?= number_format($d['monto_depreciacion'], 2) ?></td>
                                    <td class="text-end">C$ <?= number_format($d['depreciacion_acumulada_nueva'], 2) ?></td>
                                    <td class="text-end text-success fw-bold">C$ <?= number_format($d['valor_en_libros'], 2) ?></td>
                                    <td class="text-center pe-3">
                                        <?php if (!empty($d['id_asiento'])): ?>
                                            <a href="/Cycsa/publico/contabilidad/diario" class="badge bg-primary text-decoration-none">
                                                <i class="fa-solid fa-receipt me-1"></i><?= htmlspecialchars($d['num_partida'] ?? ('#' . $d['id_asiento'])) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Sin asiento</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
