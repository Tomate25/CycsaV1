<?php
// Vista: Listado de Órdenes de Compra (OC)
$totalOc = count($ordenes);
$solicitadas = 0;
$aprobadas = 0;
$recibidas = 0;
$montoTotal = 0.0;

foreach ($ordenes as $o) {
    $montoTotal += (float)($o['total'] ?? 0);
    $st = strtoupper($o['estado'] ?? '');
    if ($st === 'SOLICITADA' || $st === 'PENDIENTE') $solicitadas++;
    elseif ($st === 'APROBADA') $aprobadas++;
    elseif ($st === 'RECIBIDA' || $st === 'RECIBIDA_PARCIAL') $recibidas++;
}
?>
<div class="container-fluid py-3">

    <!-- Encabezado y Acciones -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Órdenes de Compra
            </h1>
            <p class="text-muted small mb-0">Gestión de adquisiciones, aprobación y enlace a inventario y cuentas por pagar</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/Cycsa/publico/compras/proveedores" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-truck-field me-1"></i>Proveedores
            </a>
            <a href="/Cycsa/publico/compras/recepciones" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-boxes-packing me-1"></i>Recepciones
            </a>
            <a href="/Cycsa/publico/compras/ordenes/crear" class="btn btn-primary btn-sm px-3">
                <i class="fa-solid fa-plus me-1"></i>Nueva Orden de Compra
            </a>
        </div>
    </div>

    <!-- Alertas -->
    <?php if (!empty($exito)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-check me-2"></i><?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-circle-exclamation me-2"></i><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <!-- Tarjetas KPI -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100 py-2">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Órdenes</div>
                            <div class="h4 mb-0 fw-bold text-gray-800"><?= $totalOc ?></div>
                            <div class="text-muted text-xs mt-1">C$ <?= number_format($montoTotal, 2) ?></div>
                        </div>
                        <div class="text-primary bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="fa-solid fa-receipt fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm border-start border-warning border-4 h-100 py-2">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Por Aprobar</div>
                            <div class="h4 mb-0 fw-bold text-gray-800"><?= $solicitadas ?></div>
                        </div>
                        <div class="text-warning bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="fa-solid fa-hourglass-half fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm border-start border-info border-4 h-100 py-2">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Aprobadas</div>
                            <div class="h4 mb-0 fw-bold text-gray-800"><?= $aprobadas ?></div>
                        </div>
                        <div class="text-info bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="fa-solid fa-circle-check fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm border-start border-success border-4 h-100 py-2">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Recibidas en Bodega</div>
                            <div class="h4 mb-0 fw-bold text-gray-800"><?= $recibidas ?></div>
                        </div>
                        <div class="text-success bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="fa-solid fa-dolly fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="/Cycsa/publico/compras/ordenes" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Buscar por # OC o proveedor..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="proveedor_id" class="form-select form-select-sm">
                        <option value="">Todos los Proveedores</option>
                        <?php foreach ($proveedores as $pv): ?>
                            <option value="<?= $pv['id'] ?>" <?= ($proveedorId ?? '') == $pv['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($pv['razon_social']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos los Estados</option>
                        <option value="SOLICITADA" <?= ($estado ?? '') === 'SOLICITADA' ? 'selected' : '' ?>>SOLICITADA</option>
                        <option value="APROBADA" <?= ($estado ?? '') === 'APROBADA' ? 'selected' : '' ?>>APROBADA</option>
                        <option value="RECIBIDA_PARCIAL" <?= ($estado ?? '') === 'RECIBIDA_PARCIAL' ? 'selected' : '' ?>>RECIBIDA PARCIAL</option>
                        <option value="RECIBIDA" <?= ($estado ?? '') === 'RECIBIDA' ? 'selected' : '' ?>>RECIBIDA</option>
                        <option value="ANULADA" <?= ($estado ?? '') === 'ANULADA' ? 'selected' : '' ?>>ANULADA</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary btn-sm flex-fill">
                        <i class="fa-solid fa-filter me-1"></i>Filtrar
                    </button>
                    <?php if (!empty($busqueda) || !empty($estado) || !empty($proveedorId)): ?>
                        <a href="/Cycsa/publico/compras/ordenes" class="btn btn-outline-danger btn-sm" title="Limpiar filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla Principal de Órdenes -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3"># OC</th>
                            <th>Fecha</th>
                            <th>Proveedor</th>
                            <th class="text-end">Subtotal</th>
                            <th class="text-end">IVA (15%)</th>
                            <th class="text-end">Total</th>
                            <th>Estado</th>
                            <th>Aprobación</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($ordenes)): ?>
                            <tr>
                                <td colspan="9" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-file-circle-question fa-2x mb-2 d-block"></i>
                                    No se encontraron órdenes de compra registradas.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($ordenes as $oc): ?>
                                <tr>
                                    <td class="ps-3">
                                        <a href="/Cycsa/publico/compras/ordenes/detalle?id=<?= $oc['id'] ?>" class="fw-bold text-primary text-decoration-none">
                                            OC-<?= str_pad($oc['id'], 5, '0', STR_PAD_LEFT) ?>
                                        </a>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($oc['fecha'] ?? date('Y-m-d')) ?>
                                    </td>
                                    <td>
                                        <div class="fw-semibold text-dark">
                                            <?= htmlspecialchars($oc['proveedor_nombre'] ?? $oc['proveedor'] ?? '—') ?>
                                        </div>
                                        <?php if (!empty($oc['proveedor_identificacion'])): ?>
                                            <div class="text-muted text-xs">RUC: <?= htmlspecialchars($oc['proveedor_identificacion']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end font-monospace">
                                        C$ <?= number_format((float)($oc['subtotal'] ?? 0), 2) ?>
                                    </td>
                                    <td class="text-end font-monospace text-muted">
                                        C$ <?= number_format((float)($oc['iva'] ?? 0), 2) ?>
                                    </td>
                                    <td class="text-end font-monospace fw-bold text-dark">
                                        C$ <?= number_format((float)($oc['total'] ?? 0), 2) ?>
                                    </td>
                                    <td>
                                        <?php
                                        $badgeSt = match(strtoupper($oc['estado'] ?? 'SOLICITADA')) {
                                            'SOLICITADA', 'PENDIENTE' => 'bg-warning text-dark',
                                            'APROBADA' => 'bg-info text-white',
                                            'RECIBIDA_PARCIAL' => 'bg-primary text-white',
                                            'RECIBIDA' => 'bg-success text-white',
                                            'ANULADA' => 'bg-danger text-white',
                                            default => 'bg-secondary text-white'
                                        };
                                        ?>
                                        <span class="badge <?= $badgeSt ?> px-2 py-1 text-uppercase">
                                            <?= htmlspecialchars($oc['estado'] ?? 'SOLICITADA') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php if (!empty($oc['aprobado_por_nombre'])): ?>
                                            <div class="text-success small fw-semibold"><i class="fa-solid fa-check me-1"></i><?= htmlspecialchars($oc['aprobado_por_nombre']) ?></div>
                                            <?php if (!empty($oc['fecha_aprobacion'])): ?>
                                                <div class="text-muted text-xs"><?= htmlspecialchars(substr($oc['fecha_aprobacion'], 0, 10)) ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="text-muted text-xs">Pendiente</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end pe-3">
                                        <div class="btn-group btn-group-sm">
                                            <a href="/Cycsa/publico/compras/ordenes/detalle?id=<?= $oc['id'] ?>" class="btn btn-outline-secondary" title="Ver Detalle">
                                                <i class="fa-solid fa-eye"></i>
                                            </a>
                                            <?php if (in_array(strtoupper($oc['estado'] ?? ''), ['SOLICITADA', 'PENDIENTE'])): ?>
                                                <form method="POST" action="/Cycsa/publico/compras/ordenes/aprobar" class="d-inline" onsubmit="return confirm('¿Aprobar la Orden de Compra OC-<?= str_pad($oc['id'], 5, '0', STR_PAD_LEFT) ?>?');">
                                                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                                    <input type="hidden" name="id" value="<?= $oc['id'] ?>">
                                                    <button type="submit" class="btn btn-outline-success" title="Aprobar Orden">
                                                        <i class="fa-solid fa-check"></i>
                                                    </button>
                                                </form>
                                            <?php endif; ?>
                                            <?php if (in_array(strtoupper($oc['estado'] ?? ''), ['APROBADA', 'RECIBIDA_PARCIAL'])): ?>
                                                <a href="/Cycsa/publico/compras/recepciones/crear?orden_compra_id=<?= $oc['id'] ?>" class="btn btn-outline-primary" title="Registrar Recepción">
                                                    <i class="fa-solid fa-boxes-packing"></i>
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
