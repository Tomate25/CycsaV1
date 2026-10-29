<?php
/**
 * Listado de bodegas de equipos de laboratorio.
 */
$tipos = $tipos ?? [];
$filtros = $filtros ?? [];
?>
<div class="container-fluid px-0">
    <?php
    $migas = [
        ['label' => 'Equipos', 'url' => '/Cycsa/publico/equipos-calibracion'],
        ['label' => 'Bodegas y traslados'],
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-warehouse text-primary me-2"></i>Bodegas de equipos
            </h1>
            <p class="text-muted mb-0 small">Laboratorio central, obras y talleres. Cada traslado queda con responsable, fecha, origen y destino.</p>
        </div>
        <a href="/Cycsa/publico/equipos-calibracion" class="btn btn-outline-secondary btn-sm px-3">
            <i class="fa-solid fa-balance-scale me-1"></i>Calibración
        </a>
    </div>

    <?php require __DIR__ . '/parciales/subnav_equipos_bodegas.php'; ?>

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

    <div class="row g-3">
        <div class="col-12 col-lg-8">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white py-3">
                    <form method="GET" action="/Cycsa/publico/equipos-bodegas" class="row g-2 align-items-end">
                        <div class="col-md-5">
                            <label class="form-label small text-muted mb-1">Buscar</label>
                            <input type="text" name="busqueda" value="<?= htmlspecialchars((string)($filtros['busqueda'] ?? '')) ?>" class="form-control form-control-sm" placeholder="Código, nombre o dirección">
                        </div>
                        <div class="col-md-3">
                            <label class="form-label small text-muted mb-1">Tipo</label>
                            <select name="tipo" class="form-select form-select-sm">
                                <option value="">Todos</option>
                                <?php foreach ($tipos as $clave => $etiqueta): ?>
                                    <option value="<?= htmlspecialchars($clave) ?>" <?= (($filtros['tipo'] ?? '') === $clave) ? 'selected' : '' ?>><?= htmlspecialchars($etiqueta) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label class="form-label small text-muted mb-1">Estado</label>
                            <select name="activo" class="form-select form-select-sm">
                                <option value="">Todas</option>
                                <option value="1" <?= (($filtros['activo'] ?? '') === '1') ? 'selected' : '' ?>>Activas</option>
                                <option value="0" <?= (($filtros['activo'] ?? '') === '0') ? 'selected' : '' ?>>Inactivas</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-sm btn-primary w-100">Filtrar</button>
                        </div>
                    </form>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Código</th>
                                <th>Nombre</th>
                                <th>Tipo</th>
                                <th>Proyecto</th>
                                <th class="text-end">Equipos</th>
                                <th>Activa</th>
                                <th class="pe-3"></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($bodegas)): ?>
                                <tr>
                                    <td colspan="7" class="text-center text-muted py-4">No hay bodegas que coincidan con el filtro.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($bodegas as $bodega): ?>
                                    <tr>
                                        <td class="ps-3 fw-bold font-monospace text-primary"><?= htmlspecialchars($bodega['codigo']) ?></td>
                                        <td>
                                            <?= htmlspecialchars($bodega['nombre']) ?>
                                            <?php if (!empty($bodega['direccion'])): ?>
                                                <small class="text-muted d-block"><?= htmlspecialchars($bodega['direccion']) ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td><span class="badge bg-light text-dark border"><?= htmlspecialchars($tipos[$bodega['tipo']] ?? $bodega['tipo']) ?></span></td>
                                        <td>
                                            <?php if (!empty($bodega['proyecto_id'])): ?>
                                                <a href="/Cycsa/publico/proyectos/detalle?id=<?= (int)$bodega['proyecto_id'] ?>" class="text-decoration-none">
                                                    <?= htmlspecialchars($bodega['proyecto_codigo'] ?? ('#' . $bodega['proyecto_id'])) ?>
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted">—</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end fw-semibold"><?= (int)($bodega['equipos_count'] ?? 0) ?></td>
                                        <td>
                                            <?php if ((int)$bodega['activo'] === 1): ?>
                                                <span class="badge bg-success">Activa</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Inactiva</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="pe-3 text-end">
                                            <a href="/Cycsa/publico/equipos-bodegas/bodega?id=<?= (int)$bodega['id'] ?>" class="btn btn-sm btn-outline-primary">Ver</a>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-4">
            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white fw-bold">Nueva bodega</div>
                <div class="card-body">
                    <form method="POST" action="/Cycsa/publico/equipos-bodegas/guardar-bodega">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                        <div class="mb-2">
                            <label class="form-label small">Código</label>
                            <input type="text" name="codigo" maxlength="50" required class="form-control form-control-sm" placeholder="TALLER-01">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Nombre</label>
                            <input type="text" name="nombre" maxlength="200" required class="form-control form-control-sm">
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Tipo</label>
                            <select name="tipo" class="form-select form-select-sm">
                                <?php foreach ($tipos as $clave => $etiqueta): ?>
                                    <?php if ($clave === 'proyecto') continue; ?>
                                    <option value="<?= htmlspecialchars($clave) ?>"><?= htmlspecialchars($etiqueta) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="mb-2">
                            <label class="form-label small">Dirección</label>
                            <input type="text" name="direccion" maxlength="255" class="form-control form-control-sm">
                        </div>
                        <div class="form-check mb-3">
                            <input type="hidden" name="activo" value="0">
                            <input class="form-check-input" type="checkbox" name="activo" value="1" id="bodegaActiva" checked>
                            <label class="form-check-label" for="bodegaActiva">Activa para recibir equipos</label>
                        </div>
                        <button type="submit" class="btn btn-primary btn-sm w-100">
                            <i class="fa-solid fa-plus me-1"></i>Registrar bodega
                        </button>
                    </form>
                    <p class="text-muted small mt-3 mb-0">La bodega de una obra se abre desde el detalle del proyecto. Hay una sola bodega por proyecto.</p>
                </div>
            </div>
        </div>
    </div>
</div>
