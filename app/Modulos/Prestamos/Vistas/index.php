<?php
/**
 * Vista de Listado de Préstamos Bancarios (TAREA-052).
 */
?>
<div class="container-fluid px-0">
    <!-- Encabezado -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-building-columns text-primary me-2"></i>Préstamos Bancarios
            </h1>
            <p class="text-muted mb-0 small">Control de financiamiento, amortización francesa (cuota nivelada), pasivos y pagos bancarios.</p>
        </div>
        <div>
            <a href="/Cycsa/publico/prestamos/crear" class="btn btn-primary btn-sm px-3 shadow-sm">
                <i class="fa-solid fa-plus me-1"></i>Nuevo Préstamo Bancario
            </a>
        </div>
    </div>

    <!-- Mensajes Flash -->
    <?php if (!empty($_SESSION['mensaje_exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($_SESSION['mensaje_exito']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['mensaje_exito']); ?>
    <?php endif; ?>

    <?php if (!empty($_SESSION['mensaje_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['mensaje_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['mensaje_error']); ?>
    <?php endif; ?>

    <!-- Tarjetas de Métricas -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 border-start border-primary border-4 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Monto Total Financiado</div>
                            <div class="h5 mb-0 fw-bold text-dark">
                                C$ <?= number_format($resumen['monto_total_prestado'] ?? 0, 2) ?>
                            </div>
                            <div class="text-muted small mt-1"><?= $resumen['total_prestamos'] ?? 0 ?> contratos registrados</div>
                        </div>
                        <div class="text-primary bg-primary bg-opacity-10 p-3 rounded-circle">
                            <i class="fa-solid fa-money-bill-wave fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 border-start border-danger border-4 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs fw-bold text-danger text-uppercase mb-1">Saldo Capital Activo</div>
                            <div class="h5 mb-0 fw-bold text-danger">
                                C$ <?= number_format($resumen['saldo_capital_vigente'] ?? 0, 2) ?>
                            </div>
                            <div class="text-muted small mt-1">Pasivo bancario exigible</div>
                        </div>
                        <div class="text-danger bg-danger bg-opacity-10 p-3 rounded-circle">
                            <i class="fa-solid fa-scale-unbalanced-flip fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 border-start border-warning border-4 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Cuotas Pendientes Mes</div>
                            <div class="h5 mb-0 fw-bold text-dark">
                                <?= $resumen['cuotas_pendientes_mes'] ?? 0 ?> cuotas
                            </div>
                            <div class="text-muted small mt-1">Total: C$ <?= number_format($resumen['monto_pendientes_mes'] ?? 0, 2) ?></div>
                        </div>
                        <div class="text-warning bg-warning bg-opacity-10 p-3 rounded-circle">
                            <i class="fa-solid fa-calendar-day fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 border-start border-success border-4 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Estado de Préstamos</div>
                            <div class="h5 mb-0 fw-bold text-dark">
                                <?= $resumen['activos'] ?? 0 ?> <span class="fs-6 fw-normal text-muted">Activos</span> / <?= $resumen['cancelados'] ?? 0 ?> <span class="fs-6 fw-normal text-muted">Saldados</span>
                            </div>
                            <div class="text-muted small mt-1">Trazabilidad contable al día</div>
                        </div>
                        <div class="text-success bg-success bg-opacity-10 p-3 rounded-circle">
                            <i class="fa-solid fa-circle-check fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros y Búsqueda -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="/Cycsa/publico/prestamos" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Buscar por código o institución financiera..." value="<?= htmlspecialchars($busqueda) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="estado" class="form-select form-select-sm" onchange="this.form.submit()">
                        <option value="">-- Todos los estados --</option>
                        <option value="activo" <?= $estadoFiltro === 'activo' ? 'selected' : '' ?>>Activos</option>
                        <option value="cancelado" <?= $estadoFiltro === 'cancelado' ? 'selected' : '' ?>>Cancelados / Saldados</option>
                        <option value="anulado" <?= $estadoFiltro === 'anulado' ? 'selected' : '' ?>>Anulados</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn btn-primary btn-sm w-100">
                        <i class="fa-solid fa-filter me-1"></i>Filtrar
                    </button>
                </div>
                <?php if (!empty($busqueda) || !empty($estadoFiltro)): ?>
                <div class="col-md-2">
                    <a href="/Cycsa/publico/prestamos" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="fa-solid fa-rotate-left me-1"></i>Limpiar
                    </a>
                </div>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Tabla de Préstamos -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase text-secondary small fw-bold">
                        <tr>
                            <th class="ps-3 py-3">Código</th>
                            <th>Institución Financiera</th>
                            <th class="text-end">Monto Préstamo</th>
                            <th class="text-end">Saldo Capital</th>
                            <th class="text-center">Tasa Anual</th>
                            <th class="text-center">Plazo</th>
                            <th class="text-center">Progreso</th>
                            <th class="text-center">Desembolso</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($prestamos)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-building-columns fa-2x mb-2 text-secondary opacity-50"></i>
                                <p class="mb-0">No se encontraron préstamos bancarios registrados.</p>
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($prestamos as $p): ?>
                                <?php
                                $totalCuotas = (int)($p['total_cuotas'] ?? $p['plazo_meses']);
                                $cuotasPagadas = (int)($p['cuotas_pagadas'] ?? 0);
                                $porcentaje = $totalCuotas > 0 ? round(($cuotasPagadas / $totalCuotas) * 100) : 0;
                                ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-dark">
                                        <a href="/Cycsa/publico/prestamos/detalle?id=<?= $p['id'] ?>" class="text-decoration-none text-primary">
                                            <?= htmlspecialchars($p['codigo']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($p['institucion_financiera']) ?></div>
                                        <div class="text-muted text-xs">
                                            <i class="fa-solid fa-piggy-bank me-1"></i><?= htmlspecialchars($p['banco_nombre'] ?? 'Banco') ?> (<?= htmlspecialchars($p['numero_cuenta'] ?? '') ?>)
                                        </div>
                                    </td>
                                    <td class="text-end fw-bold">
                                        C$ <?= number_format($p['monto_prestamo'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold <?= (float)$p['saldo_capital'] > 0 ? 'text-danger' : 'text-success' ?>">
                                        C$ <?= number_format($p['saldo_capital'], 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border fw-normal">
                                            <?= number_format($p['tasa_interes_anual'], 2) ?>%
                                        </span>
                                    </td>
                                    <td class="text-center text-muted">
                                        <?= $p['plazo_meses'] ?> meses
                                    </td>
                                    <td class="text-center" style="min-width: 110px;">
                                        <div class="d-flex align-items-center justify-content-center gap-1 mb-1">
                                            <span class="fw-bold text-xs"><?= $cuotasPagadas ?> / <?= $totalCuotas ?></span>
                                            <span class="text-muted text-xs">(<?= $porcentaje ?>%)</span>
                                        </div>
                                        <div class="progress" style="height: 6px;">
                                            <div class="progress-bar bg-<?= $porcentaje >= 100 ? 'success' : 'primary' ?>" role="progressbar" style="width: <?= $porcentaje ?>%"></div>
                                        </div>
                                    </td>
                                    <td class="text-center text-muted">
                                        <?= date('d/m/Y', strtotime($p['fecha_desembolso'])) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($p['estado'] === 'activo'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                                <i class="fa-solid fa-circle-play me-1"></i>Activo
                                            </span>
                                        <?php elseif ($p['estado'] === 'cancelado'): ?>
                                            <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">
                                                <i class="fa-solid fa-check-double me-1"></i>Cancelado
                                            </span>
                                        <?php else: ?>
                                            <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                                                <i class="fa-solid fa-ban me-1"></i>Anulado
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <a href="/Cycsa/publico/prestamos/detalle?id=<?= $p['id'] ?>" class="btn btn-outline-primary" title="Ver Cuotas y Amortización">
                                                <i class="fa-solid fa-table-list me-1"></i>Detalle
                                            </a>
                                            <?php if (!empty($p['id_asiento_desembolso'])): ?>
                                                <a href="/Cycsa/publico/contabilidad/diario?busqueda=<?= urlencode($p['partida_desembolso'] ?? '') ?>" class="btn btn-outline-secondary" title="Ver Asiento Contable Desembolso" target="_blank">
                                                    <i class="fa-solid fa-book-journal-whills"></i>
                                                </a>
                                            <?php endif; ?>
                                        </div>
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
