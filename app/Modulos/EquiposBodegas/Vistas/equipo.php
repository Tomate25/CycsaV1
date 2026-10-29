<?php
/**
 * Ubicación vigente e historial de traslados de un equipo.
 */
$estados = $estados ?? [];
$historial = $historial ?? [];
$bodegas = $bodegas ?? [];
$puedeTrasladar = ($ubicacion['estado'] ?? '') !== 'de_baja';
?>
<div class="container-fluid px-0">
    <?php
    $migas = [
        ['label' => 'Bodegas', 'url' => '/Cycsa/publico/equipos-bodegas'],
        ['label' => $ubicacion['bodega_codigo'] ?? 'Bodega', 'url' => '/Cycsa/publico/equipos-bodegas/bodega?id=' . (int)$ubicacion['bodega_id']],
        ['label' => $ubicacion['equipo_codigo'] ?? 'Equipo'],
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-dolly text-primary me-2"></i><?= htmlspecialchars($ubicacion['equipo_codigo']) ?>
            </h1>
            <p class="text-muted mb-0"><?= htmlspecialchars($ubicacion['equipo_nombre']) ?></p>
        </div>
        <a href="/Cycsa/publico/equipos-bodegas/bodega?id=<?= (int)$ubicacion['bodega_id'] ?>" class="btn btn-outline-secondary btn-sm px-3">
            <i class="fa-solid fa-arrow-left me-1"></i>Volver a la bodega
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

    <div class="row g-3">
        <div class="col-12 col-lg-5">
            <div class="card border-0 shadow-sm rounded-3 mb-3">
                <div class="card-header bg-white fw-bold">Ubicación actual</div>
                <div class="card-body">
                    <dl class="row mb-0 small">
                        <dt class="col-5 text-muted">Bodega</dt>
                        <dd class="col-7">
                            <a href="/Cycsa/publico/equipos-bodegas/bodega?id=<?= (int)$ubicacion['bodega_id'] ?>">
                                <?= htmlspecialchars($ubicacion['bodega_nombre']) ?>
                            </a>
                            <span class="text-muted font-monospace d-block"><?= htmlspecialchars($ubicacion['bodega_codigo']) ?></span>
                        </dd>
                        <dt class="col-5 text-muted">Estado</dt>
                        <dd class="col-7"><?= htmlspecialchars($estados[$ubicacion['estado']] ?? $ubicacion['estado']) ?></dd>
                        <dt class="col-5 text-muted">Desde</dt>
                        <dd class="col-7"><?= htmlspecialchars((string)$ubicacion['desde']) ?></dd>
                        <dt class="col-5 text-muted">Activo fijo</dt>
                        <dd class="col-7"><?= !empty($ubicacion['activo_fijo_id']) ? '#' . (int)$ubicacion['activo_fijo_id'] : 'Sin vínculo' ?></dd>
                    </dl>
                </div>
            </div>

            <div class="card border-0 shadow-sm rounded-3">
                <div class="card-header bg-white fw-bold">Registrar traslado</div>
                <div class="card-body">
                    <?php if (!$puedeTrasladar): ?>
                        <p class="text-muted mb-0">Este equipo está dado de baja y no admite traslados.</p>
                    <?php else: ?>
                        <form method="POST" action="/Cycsa/publico/equipos-bodegas/trasladar">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                            <input type="hidden" name="equipo_id" value="<?= (int)$ubicacion['equipo_id'] ?>">
                            <div class="mb-2">
                                <label class="form-label small">Bodega destino</label>
                                <select name="bodega_destino_id" class="form-select form-select-sm" required>
                                    <option value="">Seleccione</option>
                                    <?php foreach ($bodegas as $b): ?>
                                        <?php $esActual = (int)$b['id'] === (int)$ubicacion['bodega_id']; ?>
                                        <option value="<?= (int)$b['id'] ?>" <?= $esActual ? 'disabled' : '' ?>>
                                            <?= htmlspecialchars($b['codigo'] . ' — ' . $b['nombre']) ?><?= $esActual ? ' (actual)' : '' ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Fecha</label>
                                <input type="datetime-local" name="fecha_traslado" class="form-control form-control-sm" value="<?= htmlspecialchars(date('Y-m-d\TH:i')) ?>">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Motivo</label>
                                <input type="text" name="motivo" maxlength="255" required class="form-control form-control-sm" placeholder="Envío a obra, retorno a laboratorio, calibración externa">
                            </div>
                            <div class="mb-2">
                                <label class="form-label small">Documento de referencia</label>
                                <input type="text" name="documento_ref" maxlength="120" class="form-control form-control-sm" placeholder="Guía, vale u orden">
                            </div>
                            <div class="mb-3">
                                <label class="form-label small">Observaciones</label>
                                <textarea name="observaciones" rows="2" class="form-control form-control-sm"></textarea>
                            </div>
                            <button type="submit" class="btn btn-primary btn-sm w-100">
                                <i class="fa-solid fa-right-left me-1"></i>Trasladar
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <div class="col-12 col-lg-7">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-white fw-bold">Historial de traslados</div>
                <div class="table-responsive">
                    <table class="table table-sm table-hover align-middle mb-0">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3">Fecha</th>
                                <th>Origen</th>
                                <th>Destino</th>
                                <th>Quién</th>
                                <th class="pe-3">Motivo</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($historial)): ?>
                                <tr>
                                    <td colspan="5" class="text-center text-muted py-4">Sin movimientos registrados.</td>
                                </tr>
                            <?php else: ?>
                                <?php foreach (array_reverse($historial) as $mov): ?>
                                    <tr>
                                        <td class="ps-3"><small><?= htmlspecialchars((string)$mov['fecha_traslado']) ?></small></td>
                                        <td>
                                            <?php if ($mov['bodega_origen_id'] === null || $mov['bodega_origen_id'] === ''): ?>
                                                <span class="badge bg-light text-dark border">Alta</span>
                                            <?php else: ?>
                                                <?= htmlspecialchars($mov['origen_codigo'] ?? ('#' . $mov['bodega_origen_id'])) ?>
                                            <?php endif; ?>
                                        </td>
                                        <td><?= htmlspecialchars($mov['destino_codigo'] ?? '') ?></td>
                                        <td><?= htmlspecialchars($mov['usuario_nombre'] ?? ($mov['usuario_id'] ? ('#' . $mov['usuario_id']) : 'Sistema')) ?></td>
                                        <td class="pe-3">
                                            <?= htmlspecialchars((string)($mov['motivo'] ?? '')) ?>
                                            <?php if (!empty($mov['documento_ref'])): ?>
                                                <small class="text-muted d-block"><?= htmlspecialchars($mov['documento_ref']) ?></small>
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
</div>
