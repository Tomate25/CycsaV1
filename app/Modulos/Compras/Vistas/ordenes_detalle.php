<?php
// Vista: Detalle de Orden de Compra (OC)
$detalles = $orden['detalles'] ?? [];
$totalPedidas = 0.0;
$totalRecibidas = 0.0;

foreach ($detalles as $d) {
    $totalPedidas += (float)($d['cantidad'] ?? 0);
    $totalRecibidas += (float)($d['cantidad_recibida'] ?? 0);
}

$porcentajeRecibido = $totalPedidas > 0 ? min(100, round(($totalRecibidas / $totalPedidas) * 100)) : 0;
?>
<div class="container-fluid py-3">

    <!-- Breadcrumb de Navegación -->
    <?php
    $migas = [
        ['label' => 'Compras', 'url' => '/Cycsa/publico/compras/ordenes'],
        ['label' => 'Órdenes de Compra', 'url' => '/Cycsa/publico/compras/ordenes'],
        ['label' => 'Orden #OC-' . str_pad($orden['id'], 5, '0', STR_PAD_LEFT)]
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <!-- Encabezado y Acciones -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 mb-0 text-gray-800 fw-bold">
                    Orden de Compra #OC-<?= str_pad($orden['id'], 5, '0', STR_PAD_LEFT) ?>
                </h1>
                <?php
                $badgeSt = match(strtoupper($orden['estado'] ?? 'SOLICITADA')) {
                    'SOLICITADA', 'PENDIENTE' => 'bg-warning text-dark',
                    'APROBADA' => 'bg-info text-white',
                    'RECIBIDA_PARCIAL' => 'bg-primary text-white',
                    'RECIBIDA' => 'bg-success text-white',
                    'ANULADA' => 'bg-danger text-white',
                    default => 'bg-secondary text-white'
                };
                ?>
                <span class="badge <?= $badgeSt ?> px-3 py-1 fs-6 text-uppercase">
                    <?= htmlspecialchars($orden['estado'] ?? 'SOLICITADA') ?>
                </span>
            </div>
            <p class="text-muted small mb-0 mt-1">Fecha de registro: <?= htmlspecialchars($orden['fecha'] ?? date('Y-m-d')) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="/Cycsa/publico/compras/ordenes" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i>Listado de Órdenes
            </a>
            <button type="button" class="btn btn-outline-dark btn-sm" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i>Imprimir
            </button>
            <?php if (in_array(strtoupper($orden['estado'] ?? ''), ['SOLICITADA', 'PENDIENTE'])): ?>
                <form method="POST" action="/Cycsa/publico/compras/ordenes/aprobar" class="d-inline" onsubmit="return confirm('¿Confirma la aprobación formal de esta Orden de Compra?');">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id" value="<?= $orden['id'] ?>">
                    <button type="submit" class="btn btn-success btn-sm px-3">
                        <i class="fa-solid fa-check me-1"></i>Aprobar Orden
                    </button>
                </form>
            <?php endif; ?>
            <?php if (in_array(strtoupper($orden['estado'] ?? ''), ['APROBADA', 'RECIBIDA_PARCIAL'])): ?>
                <a href="/Cycsa/publico/compras/recepciones/crear?orden_compra_id=<?= $orden['id'] ?>" class="btn btn-primary btn-sm px-3">
                    <i class="fa-solid fa-boxes-packing me-1"></i>Registrar Recepción Física
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Barra de Subnavegación del Módulo -->
    <?php require __DIR__ . '/parciales/subnav_compras.php'; ?>

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

    <!-- Resumen de Datos -->
    <div class="row g-3 mb-4">
        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light py-2">
                    <h6 class="m-0 fw-bold small text-dark"><i class="fa-solid fa-truck-field me-1 text-primary"></i>Datos del Proveedor</h6>
                </div>
                <div class="card-body">
                    <div class="h5 fw-bold text-dark mb-1">
                        <?= htmlspecialchars($orden['proveedor_nombre'] ?? $orden['proveedor'] ?? '—') ?>
                    </div>
                    <?php if (!empty($orden['proveedor_identificacion'])): ?>
                        <div class="text-muted small mb-2">RUC: <?= htmlspecialchars($orden['proveedor_identificacion']) ?></div>
                    <?php endif; ?>
                    <ul class="list-unstyled small mb-0 text-muted">
                        <?php if (!empty($orden['proveedor_contacto'])): ?>
                            <li><i class="fa-solid fa-user me-2"></i>Contacto: <?= htmlspecialchars($orden['proveedor_contacto']) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($orden['proveedor_telefono'])): ?>
                            <li><i class="fa-solid fa-phone me-2"></i>Teléfono: <?= htmlspecialchars($orden['proveedor_telefono']) ?></li>
                        <?php endif; ?>
                        <?php if (!empty($orden['proveedor_email'])): ?>
                            <li><i class="fa-regular fa-envelope me-2"></i>Email: <?= htmlspecialchars($orden['proveedor_email']) ?></li>
                        <?php endif; ?>
                    </ul>
                </div>
            </div>
        </div>

        <div class="col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-header bg-light py-2">
                    <h6 class="m-0 fw-bold small text-dark"><i class="fa-solid fa-clipboard-check me-1 text-primary"></i>Auditoría y Despacho</h6>
                </div>
                <div class="card-body">
                    <div class="row g-2 small mb-3">
                        <div class="col-6">
                            <span class="text-muted d-block">Fecha de Emisión:</span>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($orden['fecha'] ?? '—') ?></span>
                        </div>
                        <div class="col-6">
                            <span class="text-muted d-block">Aprobado Por:</span>
                            <span class="fw-semibold text-dark"><?= htmlspecialchars($orden['aprobado_por_nombre'] ?? 'Pendiente') ?></span>
                        </div>
                        <?php if (!empty($orden['fecha_aprobacion'])): ?>
                            <div class="col-12">
                                <span class="text-muted d-block">Fecha / Hora de Aprobación:</span>
                                <span class="fw-semibold text-dark"><?= htmlspecialchars($orden['fecha_aprobacion']) ?></span>
                            </div>
                        <?php endif; ?>
                    </div>

                    <div>
                        <div class="d-flex justify-content-between small text-muted mb-1">
                            <span>Avance de Recepción Física:</span>
                            <span class="fw-bold"><?= $porcentajeRecibido ?>%</span>
                        </div>
                        <div class="progress" style="height: 10px;">
                            <div class="progress-bar <?= $porcentajeRecibido >= 100 ? 'bg-success' : 'bg-primary' ?>" 
                                 role="progressbar" style="width: <?= $porcentajeRecibido ?>%;" aria-valuenow="<?= $porcentajeRecibido ?>" aria-valuemin="0" aria-valuemax="100"></div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Ítems Solicitados -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-light py-3">
            <h6 class="m-0 fw-bold text-dark"><i class="fa-solid fa-cubes me-2 text-primary"></i>Ítems e Insumos en la Orden</h6>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3" style="width: 5%;">#</th>
                            <th style="width: 35%;">Descripción del Insumo / Servicio</th>
                            <th class="text-end" style="width: 12%;">Cant. Pedida</th>
                            <th class="text-end" style="width: 12%;">Cant. Recibida</th>
                            <th class="text-end" style="width: 12%;">Costo Unitario</th>
                            <th class="text-end" style="width: 10%;">IVA</th>
                            <th class="text-end pe-3" style="width: 14%;">Total</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($detalles)): ?>
                            <tr>
                                <td colspan="7" class="text-center py-4 text-muted">
                                    No hay líneas de detalle registradas en esta orden.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php $num = 1; foreach ($detalles as $det): ?>
                                <tr>
                                    <td class="ps-3 text-muted"><?= $num++ ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($det['descripcion']) ?></div>
                                        <?php if (!empty($det['insumo_codigo'])): ?>
                                            <div class="text-muted text-xs">Cód: <?= htmlspecialchars($det['insumo_codigo']) ?> | <?= htmlspecialchars($det['unidad_medida'] ?? 'unidad') ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-end font-monospace">
                                        <?= number_format((float)$det['cantidad'], 2) ?>
                                    </td>
                                    <td class="text-end font-monospace">
                                        <?php
                                        $cantP = (float)$det['cantidad'];
                                        $cantR = (float)$det['cantidad_recibida'];
                                        $badgeColor = ($cantR >= $cantP) ? 'text-success' : ($cantR > 0 ? 'text-primary' : 'text-muted');
                                        ?>
                                        <span class="fw-bold <?= $badgeColor ?>">
                                            <?= number_format($cantR, 2) ?>
                                        </span>
                                    </td>
                                    <td class="text-end font-monospace">
                                        C$ <?= number_format((float)$det['costo_unitario'], 2) ?>
                                    </td>
                                    <td class="text-end font-monospace text-muted">
                                        C$ <?= number_format((float)($det['iva'] ?? 0), 2) ?>
                                    </td>
                                    <td class="text-end font-monospace fw-bold pe-3">
                                        C$ <?= number_format((float)$det['total'], 2) ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <div class="card-footer bg-white p-4">
            <div class="row justify-content-end">
                <div class="col-md-4">
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">Subtotal:</span>
                        <span class="fw-bold font-monospace">C$ <?= number_format((float)($orden['subtotal'] ?? 0), 2) ?></span>
                    </div>
                    <div class="d-flex justify-content-between mb-2 small">
                        <span class="text-muted">IVA (15%):</span>
                        <span class="fw-bold font-monospace text-muted">C$ <?= number_format((float)($orden['iva'] ?? 0), 2) ?></span>
                    </div>
                    <hr class="my-2">
                    <div class="d-flex justify-content-between">
                        <span class="fw-bold fs-6 text-dark">TOTAL GENERAL:</span>
                        <span class="fw-bold fs-5 text-primary font-monospace">C$ <?= number_format((float)($orden['total'] ?? 0), 2) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
