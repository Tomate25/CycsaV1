<?php
/**
 * Vista Principal de Activos Fijos - CYCSA ERP (TAREA-051)
 */
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb de Navegación -->
    <?php
    $migas = [
        ['label' => 'Activos Fijos', 'url' => '/Cycsa/publico/activos-fijos'],
        ['label' => 'Control de Activos']
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <!-- Header y Acción Primaria -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-boxes-stacked text-primary me-2"></i>Gestión de Activos Fijos
            </h1>
            <p class="text-muted mb-0 small">Registro, control de valor en libros y depreciación mensual en línea recta con imputación contable.</p>
        </div>
        <div>
            <a href="/Cycsa/publico/activos-fijos/crear" class="btn btn-primary btn-sm px-3 shadow-sm">
                <i class="fa-solid fa-plus me-1"></i>Registrar Activo Fijo
            </a>
        </div>
    </div>

    <!-- Barra de Subnavegación del Módulo -->
    <?php require __DIR__ . '/parciales/subnav_activos.php'; ?>

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
    $totalActivos = count($activos);
    $totalValorAdquisicion = array_sum(array_column($activos, 'valor_adquisicion'));
    $totalDepAcumulada = array_sum(array_column($activos, 'depreciacion_acumulada'));
    $totalValorLibros = array_sum(array_column($activos, 'valor_en_libros'));
    ?>
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Total Activos</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1"><?= $totalActivos ?></h3>
                        <span class="text-muted small">Registros inventariados</span>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                        <i class="fa-solid fa-cubes fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Costo Histórico</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1">C$ <?= number_format($totalValorAdquisicion, 2) ?></h3>
                        <span class="text-muted small">Valor de adquisición</span>
                    </div>
                    <div class="bg-info-subtle text-info p-3 rounded-circle">
                        <i class="fa-solid fa-coins fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Dep. Acumulada</span>
                        <h3 class="mb-0 fw-bold text-danger mt-1">C$ <?= number_format($totalDepAcumulada, 2) ?></h3>
                        <span class="text-muted small">Desgaste amortizado</span>
                    </div>
                    <div class="bg-danger-subtle text-danger p-3 rounded-circle">
                        <i class="fa-solid fa-chart-line-down fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Valor en Libros</span>
                        <h3 class="mb-0 fw-bold text-success mt-1">C$ <?= number_format($totalValorLibros, 2) ?></h3>
                        <span class="text-muted small">Patrimonio neto actual</span>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle">
                        <i class="fa-solid fa-scale-balanced fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="/Cycsa/publico/activos-fijos" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0">
                            <i class="fa-solid fa-magnifying-glass text-muted"></i>
                        </span>
                        <input type="text" name="q" class="form-control form-control-sm border-start-0" placeholder="Buscar por código o descripción..." value="<?= htmlspecialchars($busqueda) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="categoria" class="form-select form-select-sm">
                        <option value="">-- Todas las Categorías --</option>
                        <option value="maquinaria_equipo" <?= $categoriaFiltro === 'maquinaria_equipo' ? 'selected' : '' ?>>Maquinaria y Equipo</option>
                        <option value="edificios" <?= $categoriaFiltro === 'edificios' ? 'selected' : '' ?>>Edificios</option>
                        <option value="vehiculos" <?= $categoriaFiltro === 'vehiculos' ? 'selected' : '' ?>>Vehículos</option>
                        <option value="muebles_enseres" <?= $categoriaFiltro === 'muebles_enseres' ? 'selected' : '' ?>>Muebles y Enseres</option>
                        <option value="equipo_computo" <?= $categoriaFiltro === 'equipo_computo' ? 'selected' : '' ?>>Equipo de Cómputo</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">-- Todos los Estados --</option>
                        <option value="activo" <?= $estadoFiltro === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="depreciado" <?= $estadoFiltro === 'depreciado' ? 'selected' : '' ?>>Depreciado</option>
                        <option value="en_mantenimiento" <?= $estadoFiltro === 'en_mantenimiento' ? 'selected' : '' ?>>En Mantenimiento</option>
                        <option value="de_baja" <?= $estadoFiltro === 'de_baja' ? 'selected' : '' ?>>De Baja</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="fa-solid fa-filter me-1"></i>Filtrar
                    </button>
                    <?php if ($busqueda !== '' || $categoriaFiltro !== '' || $estadoFiltro !== ''): ?>
                        <a href="/Cycsa/publico/activos-fijos" class="btn btn-sm btn-outline-secondary" title="Limpiar filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla Principal de Activos -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-list-check text-primary me-2"></i>Catálogo de Activos Fijos
            </h5>
            <span class="badge bg-light text-dark border">
                <?= count($activos) ?> activos encontrados
            </span>
        </div>
        <div class="card-body p-0">
            <?php if (empty($activos)): ?>
                <div class="text-center py-5">
                    <div class="text-muted mb-3">
                        <i class="fa-solid fa-box-open fa-3x"></i>
                    </div>
                    <h5 class="fw-bold text-muted">No se encontraron activos fijos</h5>
                    <p class="text-muted small">No hay activos registrados que coincidan con los criterios de búsqueda.</p>
                    <a href="/Cycsa/publico/activos-fijos/crear" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-plus me-1"></i>Registrar el primer activo
                    </a>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="ps-3">Código</th>
                                <th>Descripción / Categoría</th>
                                <th>Adquisición</th>
                                <th class="text-end">Costo Adq.</th>
                                <th class="text-end">Dep. Acumulada</th>
                                <th class="text-end">Valor en Libros</th>
                                <th class="text-center">Cuota Mensual</th>
                                <th class="text-center">Estado</th>
                                <th class="text-end pe-3">Acciones</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($activos as $a): ?>
                                <?php
                                $badgeCat = match($a['categoria']) {
                                    'edificios' => 'bg-secondary',
                                    'vehiculos' => 'bg-info text-dark',
                                    'maquinaria_equipo' => 'bg-primary',
                                    'muebles_enseres' => 'bg-warning text-dark',
                                    'equipo_computo' => 'bg-dark',
                                    default => 'bg-secondary'
                                };
                                $nomCat = match($a['categoria']) {
                                    'edificios' => 'Edificios',
                                    'vehiculos' => 'Vehículos',
                                    'maquinaria_equipo' => 'Maquinaria y Equipo',
                                    'muebles_enseres' => 'Muebles y Enseres',
                                    'equipo_computo' => 'Equipo de Cómputo',
                                    default => ucfirst($a['categoria'])
                                };
                                $badgeEstado = match($a['estado']) {
                                    'activo' => 'bg-success',
                                    'depreciado' => 'bg-secondary',
                                    'en_mantenimiento' => 'bg-warning text-dark',
                                    'de_baja' => 'bg-danger',
                                    default => 'bg-light text-dark'
                                };
                                ?>
                                <tr>
                                    <td class="ps-3">
                                        <a href="/Cycsa/publico/activos-fijos/detalle?id=<?= $a['id'] ?>" class="fw-bold font-monospace text-decoration-none">
                                            <?= htmlspecialchars($a['codigo']) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($a['descripcion']) ?></div>
                                        <span class="badge <?= $badgeCat ?> py-1 px-2" style="font-size: 11px;">
                                            <?= $nomCat ?>
                                        </span>
                                    </td>
                                    <td>
                                        <div><i class="fa-regular fa-calendar me-1 text-muted"></i><?= htmlspecialchars($a['fecha_adquisicion']) ?></div>
                                        <span class="text-muted" style="font-size: 11px;"><?= $a['vida_util_meses'] ?> meses vida útil</span>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        C$ <?= number_format($a['valor_adquisicion'], 2) ?>
                                    </td>
                                    <td class="text-end text-danger fw-semibold">
                                        C$ <?= number_format($a['depreciacion_acumulada'], 2) ?>
                                        <div class="progress mt-1" style="height: 4px;">
                                            <div class="progress-bar bg-danger" role="progressbar" style="width: <?= $a['porcentaje_depreciado'] ?>%"></div>
                                        </div>
                                    </td>
                                    <td class="text-end text-success fw-bold">
                                        C$ <?= number_format($a['valor_en_libros'], 2) ?>
                                        <div class="text-muted" style="font-size: 10px;">Res: C$ <?= number_format($a['valor_residual'], 2) ?></div>
                                    </td>
                                    <td class="text-center font-monospace">
                                        C$ <?= number_format($a['cuota_mensual'], 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $badgeEstado ?> py-1 px-2 text-uppercase" style="font-size: 11px;">
                                            <?= str_replace('_', ' ', $a['estado']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <a href="/Cycsa/publico/activos-fijos/detalle?id=<?= $a['id'] ?>" class="btn btn-outline-secondary" title="Ficha Técnica">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <a href="/Cycsa/publico/activos-fijos/editar?id=<?= $a['id'] ?>" class="btn btn-outline-secondary" title="Editar">
                                                <i class="fa-solid fa-pen-to-square"></i>
                                            </a>
                                            <button type="button" class="btn btn-outline-danger" title="Eliminar / Dar de baja" onclick="confirmarEliminar(<?= $a['id'] ?>, '<?= htmlspecialchars($a['codigo'], ENT_QUOTES) ?>')">
                                                <i class="fa-solid fa-trash-can"></i>
                                            </button>
                                        </div>
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

<!-- Modal Eliminar / Dar de Baja -->
<div class="modal fade" id="modalEliminarActivo" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <form method="POST" action="/Cycsa/publico/activos-fijos/eliminar">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" id="eliminarActivoId" value="">
                <div class="modal-header">
                    <h5 class="modal-title fw-bold text-danger">
                        <i class="fa-solid fa-triangle-exclamation me-2"></i>Eliminar / Dar de Baja Activo
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p class="mb-2">¿Está seguro de que desea eliminar o dar de baja el activo <strong id="eliminarActivoCodigo"></strong>?</p>
                    <p class="text-muted small mb-0">Si el activo ya cuenta con depreciaciones históricas contabilizadas, pasará automáticamente al estado <strong>De Baja</strong> para preservar la integridad de los libros contables.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-danger">Confirmar Acción</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function confirmarEliminar(id, codigo) {
    document.getElementById('eliminarActivoId').value = id;
    document.getElementById('eliminarActivoCodigo').textContent = codigo;
    new bootstrap.Modal(document.getElementById('modalEliminarActivo')).show();
}
</script>
