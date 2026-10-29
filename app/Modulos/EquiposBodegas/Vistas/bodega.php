<?php
/**
 * Equipos actualmente en una bodega.
 */
$estados = $estados ?? [];
$tipos = \Cycsa\Modulos\EquiposBodegas\Modelos\EquiposBodegasModelo::etiquetasTipo();
?>
<div class="container-fluid px-0">
    <?php
    $migas = [
        ['label' => 'Bodegas', 'url' => '/Cycsa/publico/equipos-bodegas'],
        ['label' => $bodega['codigo'] ?? 'Bodega'],
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-warehouse text-primary me-2"></i><?= htmlspecialchars($bodega['codigo']) ?>
                <span class="fw-normal">— <?= htmlspecialchars($bodega['nombre']) ?></span>
            </h1>
            <p class="text-muted mb-0 small">
                <?= htmlspecialchars($tipos[$bodega['tipo']] ?? $bodega['tipo']) ?>
                <?php if (!empty($bodega['direccion'])): ?>
                    · <?= htmlspecialchars($bodega['direccion']) ?>
                <?php endif; ?>
                <?php if (!empty($bodega['proyecto_id'])): ?>
                    · Proyecto
                    <a href="/Cycsa/publico/proyectos/detalle?id=<?= (int)$bodega['proyecto_id'] ?>">
                        <?= htmlspecialchars($bodega['proyecto_codigo'] ?? ('#' . $bodega['proyecto_id'])) ?>
                    </a>
                <?php endif; ?>
            </p>
        </div>
        <a href="/Cycsa/publico/equipos-bodegas" class="btn btn-outline-secondary btn-sm px-3">
            <i class="fa-solid fa-arrow-left me-1"></i>Bodegas
        </a>
    </div>

    <?php require __DIR__ . '/parciales/subnav_equipos_bodegas.php'; ?>

    <?php if (!empty($_SESSION['flash_exito'])): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_exito']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_exito']); ?>
    <?php endif; ?>
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-header bg-white d-flex justify-content-between align-items-center">
            <span class="fw-bold">Equipos en esta bodega</span>
            <?php if ((int)$bodega['activo'] === 1): ?>
                <span class="badge bg-success">Activa</span>
            <?php else: ?>
                <span class="badge bg-secondary">Inactiva — no recibe traslados</span>
            <?php endif; ?>
        </div>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <thead class="table-light">
                    <tr>
                        <th class="ps-3">Código</th>
                        <th>Equipo</th>
                        <th>Categoría</th>
                        <th>Estado</th>
                        <th>Desde</th>
                        <th class="pe-3"></th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($equipos)): ?>
                        <tr>
                            <td colspan="6" class="text-center text-muted py-4">No hay equipos en esta bodega.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($equipos as $eq): ?>
                            <tr>
                                <td class="ps-3 font-monospace fw-bold"><?= htmlspecialchars($eq['equipo_codigo']) ?></td>
                                <td><?= htmlspecialchars($eq['equipo_nombre']) ?></td>
                                <td><?= htmlspecialchars($eq['equipo_categoria'] ?? '') ?></td>
                                <td><?= htmlspecialchars($estados[$eq['estado']] ?? $eq['estado']) ?></td>
                                <td><small class="text-muted"><?= htmlspecialchars((string)$eq['desde']) ?></small></td>
                                <td class="pe-3 text-end">
                                    <a class="btn btn-sm btn-outline-primary" href="/Cycsa/publico/equipos-bodegas/equipo?id=<?= (int)$eq['equipo_id'] ?>">Ficha y traslado</a>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
