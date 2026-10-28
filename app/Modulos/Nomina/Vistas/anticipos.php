<?php
/**
 * Vista de Anticipos y Préstamos a Empleados (TAREA-050)
 */
?>
<div class="container-fluid px-0">
    <!-- Header -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i>Anticipos a Empleados
            </h1>
            <p class="text-muted mb-0 small">Registro de adelantos de salario, amortización automática en nómina y enlace a tesorería.</p>
        </div>
        <div class="d-flex gap-2">
            <a href="/Cycsa/publico/nomina/empleados" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-users me-1"></i>Ver Colaboradores
            </a>
            <a href="/Cycsa/publico/nomina" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-file-invoice-dollar me-1"></i>Nóminas
            </a>
            <button type="button" class="btn btn-primary btn-sm px-3" data-bs-toggle="modal" data-bs-target="#modalAnticipo">
                <i class="fa-solid fa-plus me-1"></i>Conceder Anticipo
            </button>
        </div>
    </div>

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
    $totalOtorgado = array_sum(array_column($anticipos, 'monto'));
    $totalSaldoPendiente = array_sum(array_column($anticipos, 'saldo'));
    $totalDescontado = $totalOtorgado - $totalSaldoPendiente;
    $pendientesCount = count(array_filter($anticipos, fn($a) => in_array($a['estado'], ['pendiente', 'descontado_parcial'])));
    ?>
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Total Otorgado</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1">C$ <?= number_format($totalOtorgado, 2) ?></h3>
                        <span class="text-muted small"><?= count($anticipos) ?> registros totales</span>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                        <i class="fa-solid fa-wallet fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Saldo por Recuperar</span>
                        <h3 class="mb-0 fw-bold text-danger mt-1">C$ <?= number_format($totalSaldoPendiente, 2) ?></h3>
                        <span class="badge bg-danger-subtle text-danger small mt-1"><?= $pendientesCount ?> por amortizar</span>
                    </div>
                    <div class="bg-danger-subtle text-danger p-3 rounded-circle">
                        <i class="fa-solid fa-clock-rotate-left fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Total Amortizado</span>
                        <h3 class="mb-0 fw-bold text-success mt-1">C$ <?= number_format($totalDescontado, 2) ?></h3>
                        <span class="text-muted small">Descontado en nóminas</span>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle">
                        <i class="fa-solid fa-circle-check fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Tasa de Recuperación</span>
                        <h3 class="mb-0 fw-bold text-info mt-1">
                            <?= $totalOtorgado > 0 ? round(($totalDescontado / $totalOtorgado) * 100, 1) : 0 ?>%
                        </h3>
                        <span class="text-muted small">Efectividad de descuento</span>
                    </div>
                    <div class="bg-info-subtle text-info p-3 rounded-circle">
                        <i class="fa-solid fa-chart-pie fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="/Cycsa/publico/nomina/anticipos" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Buscar por empleado o motivo..." value="<?= htmlspecialchars($busqueda) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="empleado_id" class="form-select form-select-sm">
                        <option value="">-- Todos los Colaboradores --</option>
                        <?php foreach ($empleados as $emp): ?>
                            <option value="<?= $emp['id'] ?>" <?= $empleadoFiltro == $emp['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($emp['nombre']) ?> (<?= htmlspecialchars($emp['codigo']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">-- Todos los Estados --</option>
                        <option value="pendiente" <?= $estadoFiltro === 'pendiente' ? 'selected' : '' ?>>Pendientes</option>
                        <option value="descontado_parcial" <?= $estadoFiltro === 'descontado_parcial' ? 'selected' : '' ?>>Parcialmente Descontados</option>
                        <option value="descontado_total" <?= $estadoFiltro === 'descontado_total' ? 'selected' : '' ?>>Totalmente Descontados</option>
                        <option value="anulado" <?= $estadoFiltro === 'anulado' ? 'selected' : '' ?>>Anulados</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="fa-solid fa-filter me-1"></i>Filtrar
                    </button>
                    <?php if ($busqueda !== '' || $estadoFiltro !== '' || $empleadoFiltro > 0): ?>
                        <a href="/Cycsa/publico/nomina/anticipos" class="btn btn-sm btn-outline-secondary" title="Limpiar Filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Anticipos -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">ID</th>
                            <th>Colaborador</th>
                            <th>Departamento</th>
                            <th>Fecha</th>
                            <th class="text-end">Monto Original</th>
                            <th class="text-end">Saldo Pendiente</th>
                            <th>Motivo / Observaciones</th>
                            <th>Asiento Contable</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($anticipos)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-receipt fa-2x mb-3 text-secondary"></i>
                                    <p class="mb-0">No hay anticipos registrados según el filtro seleccionado.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($anticipos as $ant): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-muted font-monospace small">#<?= $ant['id'] ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($ant['empleado_nombre']) ?></div>
                                        <div class="small text-muted font-monospace"><?= htmlspecialchars($ant['empleado_codigo']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($ant['departamento_nombre']) ?>
                                        </span>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($ant['fecha']) ?></td>
                                    <td class="text-end fw-bold font-monospace text-dark">
                                        C$ <?= number_format($ant['monto'], 2) ?>
                                    </td>
                                    <td class="text-end fw-bold font-monospace <?= (float)$ant['saldo'] > 0 ? 'text-danger' : 'text-success' ?>">
                                        C$ <?= number_format($ant['saldo'], 2) ?>
                                    </td>
                                    <td class="small text-muted" style="max-width: 250px;">
                                        <?= htmlspecialchars($ant['motivo'] ?? '—') ?>
                                    </td>
                                    <td>
                                        <?php if (!empty($ant['asiento_numero'])): ?>
                                            <span class="badge bg-light text-primary border font-monospace">
                                                <i class="fa-solid fa-book-journal-whills me-1"></i><?= htmlspecialchars($ant['asiento_numero']) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($ant['estado'] === 'pendiente'): ?>
                                            <span class="badge bg-warning text-dark">Pendiente</span>
                                        <?php elseif ($ant['estado'] === 'descontado_parcial'): ?>
                                            <span class="badge bg-info text-dark">Descontado Parcial</span>
                                        <?php elseif ($ant['estado'] === 'descontado_total'): ?>
                                            <span class="badge bg-success">Descontado Total</span>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Anulado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-3">
                                        <?php if ($ant['estado'] !== 'descontado_total' && $ant['estado'] !== 'anulado'): ?>
                                            <form method="POST" action="/Cycsa/publico/nomina/anticipos/anular" style="display:inline;" onsubmit="return confirm('¿Está seguro de anular este anticipo?');">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                                                <input type="hidden" name="id" value="<?= $ant['id'] ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-danger py-0 px-2" title="Anular Anticipo">
                                                    <i class="fa-solid fa-ban"></i>
                                                </button>
                                            </form>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
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

<!-- Modal Conceder Anticipo -->
<div class="modal fade" id="modalAnticipo" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <form method="POST" action="/Cycsa/publico/nomina/anticipos/guardar">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold">
                        <i class="fa-solid fa-hand-holding-dollar text-primary me-2"></i>Registrar Anticipo a Colaborador
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-12">
                            <label class="form-label small fw-bold">Colaborador Beneficiario *</label>
                            <select name="empleado_id" class="form-select form-select-sm" required>
                                <option value="">-- Seleccionar Colaborador --</option>
                                <?php foreach ($empleados as $emp): ?>
                                    <option value="<?= $emp['id'] ?>" <?= $empleadoFiltro == $emp['id'] ? 'selected' : '' ?>>
                                        <?= htmlspecialchars($emp['nombre']) ?> - Salario: C$ <?= number_format($emp['salario_base'], 2) ?> (<?= htmlspecialchars($emp['departamento_nombre']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Monto del Anticipo (C$) *</label>
                            <input type="number" step="0.01" name="monto" class="form-control form-control-sm font-monospace" required placeholder="0.00">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-bold">Fecha de Desembolso *</label>
                            <input type="date" name="fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Cuenta Bancaria de Salida (Opcional)</label>
                            <select name="id_banco_cuenta" class="form-select form-select-sm">
                                <option value="">-- Registrar sin egreso bancario inmediato --</option>
                                <?php foreach ($bancos as $bco): ?>
                                    <option value="<?= $bco['id'] ?>">
                                        <?= htmlspecialchars($bco['banco_nombre']) ?> - Cta: <?= htmlspecialchars($bco['numero_cuenta']) ?> (Saldo: <?= number_format($bco['saldo_actual'], 2) ?> <?= htmlspecialchars($bco['moneda']) ?>)
                                    </option>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small">Si selecciona una cuenta bancaria, se descontará automáticamente el saldo de banco y se creará la partida contable de egreso.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Motivo / Concepto</label>
                            <textarea name="motivo" class="form-control form-control-sm" rows="2" placeholder="Ej: Adelanto de quincena por emergencia médica personal"></textarea>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Conceder Anticipo
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
