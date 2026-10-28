<?php
/**
 * Vista de Listado de Nóminas y Planillas (TAREA-050)
 */
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb de Navegación -->
    <?php
    $migas = [
        ['label' => 'Nómina', 'url' => '/Cycsa/publico/nomina'],
        ['label' => 'Nóminas y Planillas']
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <!-- Header y Acción Primaria -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Nóminas y Planillas Procesadas
            </h1>
            <p class="text-muted mb-0 small">Historial de periodos de nómina, cálculo de deducciones y provisión contable de cargas laborales.</p>
        </div>
        <div>
            <a href="/Cycsa/publico/nomina/crear" class="btn btn-primary btn-sm px-3 shadow-sm">
                <i class="fa-solid fa-calculator me-1"></i>Procesar Nueva Nómina
            </a>
        </div>
    </div>

    <!-- Barra de Subnavegación del Módulo -->
    <?php require __DIR__ . '/parciales/subnav_nomina.php'; ?>

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

    <!-- KPIs -->
    <?php
    $totalNominas = count($nominas);
    $totalDevengado = array_sum(array_column($nominas, 'total_devengado'));
    $totalDeducciones = array_sum(array_column($nominas, 'total_deducciones'));
    $totalNeto = array_sum(array_column($nominas, 'total_neto'));
    ?>
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Nóminas Registradas</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1"><?= $totalNominas ?></h3>
                        <span class="text-muted small">Periodos cerrados</span>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                        <i class="fa-solid fa-calendar-days fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Total Devengado</span>
                        <h3 class="mb-0 fw-bold text-primary mt-1">C$ <?= number_format($totalDevengado, 2) ?></h3>
                        <span class="text-muted small">Sueldos brutos</span>
                    </div>
                    <div class="bg-info-subtle text-info p-3 rounded-circle">
                        <i class="fa-solid fa-money-check-dollar fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Deducciones de Ley</span>
                        <h3 class="mb-0 fw-bold text-danger mt-1">C$ <?= number_format($totalDeducciones, 2) ?></h3>
                        <span class="text-muted small">INSS + IR + Anticipos</span>
                    </div>
                    <div class="bg-danger-subtle text-danger p-3 rounded-circle">
                        <i class="fa-solid fa-percent fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Neto Desembolsado</span>
                        <h3 class="mb-0 fw-bold text-success mt-1">C$ <?= number_format($totalNeto, 2) ?></h3>
                        <span class="text-muted small">Pagado a colaboradores</span>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle">
                        <i class="fa-solid fa-sack-dollar fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="/Cycsa/publico/nomina" class="row g-2 align-items-center">
                <div class="col-md-7">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Buscar por código de nómina o número de partida de diario..." value="<?= htmlspecialchars($busqueda) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">-- Todos los Estados --</option>
                        <option value="procesada" <?= $estadoFiltro === 'procesada' ? 'selected' : '' ?>>Procesadas</option>
                        <option value="pagada" <?= $estadoFiltro === 'pagada' ? 'selected' : '' ?>>Pagadas</option>
                        <option value="borrador" <?= $estadoFiltro === 'borrador' ? 'selected' : '' ?>>Borradores</option>
                        <option value="anulada" <?= $estadoFiltro === 'anulada' ? 'selected' : '' ?>>Anuladas</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="fa-solid fa-filter me-1"></i>Filtrar
                    </button>
                    <?php if ($busqueda !== '' || $estadoFiltro !== ''): ?>
                        <a href="/Cycsa/publico/nomina" class="btn btn-sm btn-outline-secondary" title="Limpiar Filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Nóminas -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Código</th>
                            <th>Periodo</th>
                            <th class="text-center">Colaboradores</th>
                            <th class="text-end">Total Devengado</th>
                            <th class="text-end">Total Deducciones</th>
                            <th class="text-end">Total Neto</th>
                            <th>Asiento Contable</th>
                            <th>Procesada Por</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($nominas)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-file-excel fa-2x mb-3 text-secondary"></i>
                                    <p class="mb-0">No se encontraron nóminas registradas.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($nominas as $nom): ?>
                                <tr>
                                    <td class="ps-3 font-monospace fw-bold text-primary">
                                        <a href="/Cycsa/publico/nomina/detalle?id=<?= $nom['id'] ?>" class="text-decoration-none">
                                            <?= htmlspecialchars($nom['codigo']) ?>
                                        </a>
                                    </td>
                                    <td class="small">
                                        <i class="fa-regular fa-calendar me-1 text-muted"></i>
                                        <?= htmlspecialchars($nom['periodo_inicio']) ?> al <?= htmlspecialchars($nom['periodo_fin']) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge bg-light text-dark border">
                                            <?= (int)$nom['cantidad_empleados'] ?> empleados
                                        </span>
                                    </td>
                                    <td class="text-end fw-bold font-monospace text-dark">
                                        C$ <?= number_format($nom['total_devengado'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold font-monospace text-danger">
                                        C$ <?= number_format($nom['total_deducciones'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold font-monospace text-success">
                                        C$ <?= number_format($nom['total_neto'], 2) ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($nom['asiento_numero'])): ?>
                                            <span class="badge bg-light text-primary border font-monospace">
                                                <i class="fa-solid fa-book me-1"></i><?= htmlspecialchars($nom['asiento_numero']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted">
                                        <?= htmlspecialchars($nom['procesada_por_nombre'] ?? 'Sistema') ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($nom['estado'] === 'procesada'): ?>
                                            <span class="badge bg-success">Procesada</span>
                                        <?php elseif ($nom['estado'] === 'pagada'): ?>
                                            <span class="badge bg-primary">Pagada</span>
                                        <?php elseif ($nom['estado'] === 'borrador'): ?>
                                            <span class="badge bg-warning text-dark">Borrador</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Anulada</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-3">
                                        <a href="/Cycsa/publico/nomina/detalle?id=<?= $nom['id'] ?>" class="btn btn-sm btn-outline-primary py-0 px-2" title="Ver Detalle y Planilla">
                                            <i class="fa-solid fa-eye"></i>
                                        </a>
                                        <?php if ($nom['estado'] !== 'anulada'): ?>
                                            <form method="POST" action="/Cycsa/publico/nomina/anular" style="display:inline;" onsubmit="return confirm('¿Está seguro de anular esta nómina? Se restaurarán los saldos de anticipos descontados.');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="id" value="<?= $nom['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Anular Nómina">
                                                    <i class="fa-solid fa-ban"></i>
                                                </button>
                                            </form>
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
