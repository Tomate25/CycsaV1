<?php
/**
 * Vista: app/Modulos/Proyectos/Vistas/index.php
 * TAREA-053: Listado Maestro de Proyectos y Órdenes de Obra.
 */
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb de Navegación -->
    <?php
    $migas = [
        ['label' => 'Proyectos', 'url' => '/Cycsa/publico/proyectos'],
        ['label' => 'Órdenes de Obra']
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <!-- Header y Acción Primaria -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-0 text-gray-800 fw-bold">
                <i class="fa-solid fa-helmet-safety text-primary me-2"></i>Proyectos y Órdenes de Obra
            </h1>
            <p class="text-muted mb-0 small">
                Control de obras civiles, presupuesto y agregación transversal de costos directos (Compras, Gastos, Kárdex, Mano de Obra).
            </p>
        </div>
        <div>
            <a href="/Cycsa/publico/proyectos/crear" class="btn btn-primary btn-sm px-3 shadow-sm">
                <i class="fa-solid fa-plus me-1"></i>Nuevo Proyecto
            </a>
        </div>
    </div>

    <!-- Barra de Subnavegación del Módulo -->
    <?php require __DIR__ . '/parciales/subnav_proyectos.php'; ?>

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

    <!-- Tarjetas de Estadísticas Globales -->
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Total Proyectos</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1"><?= (int)($estadisticas['total_proyectos'] ?? 0) ?></h3>
                        <span class="text-muted small"><?= (int)($estadisticas['planificados'] ?? 0) ?> planificados</span>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                        <i class="fa-solid fa-diagram-project fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">En Ejecución</span>
                        <h3 class="mb-0 fw-bold text-success mt-1"><?= (int)($estadisticas['en_ejecucion'] ?? 0) ?></h3>
                        <span class="text-muted small">Obras activas</span>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle">
                        <i class="fa-solid fa-person-digging fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Completados</span>
                        <h3 class="mb-0 fw-bold text-info mt-1"><?= (int)($estadisticas['completados'] ?? 0) ?></h3>
                        <span class="text-muted small">Finalizados</span>
                    </div>
                    <div class="bg-info-subtle text-info p-3 rounded-circle">
                        <i class="fa-solid fa-flag-checkered fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Presupuesto Global</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1">C$ <?= number_format($estadisticas['total_presupuesto'] ?? 0, 2) ?></h3>
                        <span class="text-muted small">Cartera estimada</span>
                    </div>
                    <div class="bg-warning-subtle text-warning p-3 rounded-circle">
                        <i class="fa-solid fa-coins fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros de Búsqueda -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="/Cycsa/publico/proyectos" class="row g-2 align-items-center">
                <div class="col-12 col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="busqueda" class="form-control border-start-0" placeholder="Buscar por código, nombre, ubicación o cliente..." value="<?= htmlspecialchars($filtros['busqueda'] ?? '') ?>">
                    </div>
                </div>
                <div class="col-6 col-md-3">
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">-- Todos los Estados --</option>
                        <option value="planificado" <?= ($filtros['estado'] ?? '') === 'planificado' ? 'selected' : '' ?>>Planificado</option>
                        <option value="en_ejecucion" <?= ($filtros['estado'] ?? '') === 'en_ejecucion' ? 'selected' : '' ?>>En Ejecución</option>
                        <option value="completado" <?= ($filtros['estado'] ?? '') === 'completado' ? 'selected' : '' ?>>Completado</option>
                        <option value="suspendido" <?= ($filtros['estado'] ?? '') === 'suspendido' ? 'selected' : '' ?>>Suspendido</option>
                    </select>
                </div>
                <div class="col-6 col-md-3">
                    <select name="cliente_id" class="form-select form-select-sm">
                        <option value="">-- Todos los Clientes --</option>
                        <?php foreach ($clientes as $cli): ?>
                            <option value="<?= $cli['id'] ?>" <?= ($filtros['cliente_id'] ?? '') == $cli['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($cli['nombre_razon_social']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-12 col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="fa-solid fa-filter me-1"></i>Filtrar
                    </button>
                    <?php if (!empty($filtros['busqueda']) || !empty($filtros['estado']) || !empty($filtros['cliente_id'])): ?>
                        <a href="/Cycsa/publico/proyectos" class="btn btn-sm btn-outline-secondary" title="Limpiar Filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Proyectos -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Código</th>
                            <th>Proyecto</th>
                            <th>Cliente</th>
                            <th>Ubicación</th>
                            <th class="text-end">Presupuesto</th>
                            <th class="text-end">Costo Acumulado</th>
                            <th style="min-width: 140px;">Consumo / Desviación</th>
                            <th class="text-center">Estado</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($proyectos)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-folder-open fa-2x mb-3 d-block text-secondary opacity-50"></i>
                                    No se encontraron proyectos u órdenes de obra registradas.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($proyectos as $p): ?>
                                <tr>
                                    <td class="ps-3 fw-bold font-monospace text-primary">
                                        <a href="/Cycsa/publico/proyectos/detalle?id=<?= $p['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($p['codigo']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($p['nombre']) ?></div>
                                        <small class="text-muted">Inicio: <?= htmlspecialchars($p['fecha_inicio']) ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($p['cliente_nombre'] ?? 'Sin asignar') ?>
                                    </td>
                                    <td>
                                        <small class="text-muted"><i class="fa-solid fa-location-dot me-1 text-danger opacity-75"></i><?= htmlspecialchars($p['ubicacion'] ?? 'No especificada') ?></small>
                                    </td>
                                    <td class="text-end fw-semibold text-dark">
                                        C$ <?= number_format($p['presupuesto_estimado'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold <?= ($p['costo_total'] > $p['presupuesto_estimado'] && $p['presupuesto_estimado'] > 0) ? 'text-danger' : 'text-primary' ?>">
                                        C$ <?= number_format($p['costo_total'], 2) ?>
                                    </td>
                                    <td>
                                        <div class="d-flex align-items-center gap-2">
                                            <div class="progress flex-grow-1" style="height: 6px;">
                                                <?php
                                                $pct = (float)$p['porcentaje_consumido'];
                                                $barColor = $pct > 100 ? 'bg-danger' : ($pct > 80 ? 'bg-warning' : 'bg-success');
                                                $barWidth = min(100, $pct);
                                                ?>
                                                <div class="progress-bar <?= $barColor ?>" style="width: <?= $barWidth ?>%;"></div>
                                            </div>
                                            <span class="small fw-semibold <?= $pct > 100 ? 'text-danger' : 'text-muted' ?>">
                                                <?= number_format($pct, 1) ?>%
                                            </span>
                                        </div>
                                        <small class="d-block text-muted" style="font-size: 0.75rem;">
                                            <?php if ($p['desviacion'] >= 0): ?>
                                                <span class="text-success"><i class="fa-solid fa-arrow-down me-1"></i>Disp: C$ <?= number_format($p['desviacion'], 2) ?></span>
                                            <?php else: ?>
                                                <span class="text-danger"><i class="fa-solid fa-arrow-up me-1"></i>Sobrecosto: C$ <?= number_format(abs($p['desviacion']), 2) ?></span>
                                            <?php endif; ?>
                                        </small>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($p['estado'] === 'en_ejecucion'): ?>
                                            <span class="badge bg-success-subtle text-success border border-success-subtle px-2 py-1">En Ejecución</span>
                                        <?php elseif ($p['estado'] === 'planificado'): ?>
                                            <span class="badge bg-warning-subtle text-warning border border-warning-subtle px-2 py-1">Planificado</span>
                                        <?php elseif ($p['estado'] === 'completado'): ?>
                                            <span class="badge bg-info-subtle text-info border border-info-subtle px-2 py-1">Completado</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary-subtle text-secondary border border-secondary-subtle px-2 py-1">Suspendido</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <a href="/Cycsa/publico/proyectos/detalle?id=<?= $p['id'] ?>" class="btn btn-outline-primary" title="Ver Costos Consolidados">
                                                <i class="fa-solid fa-chart-pie"></i>
                                            </a>
                                            <a href="/Cycsa/publico/proyectos/editar?id=<?= $p['id'] ?>" class="btn btn-outline-secondary" title="Editar Proyecto">
                                                <i class="fa-solid fa-pen"></i>
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
    </div>
</div>
