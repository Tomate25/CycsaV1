<?php
/**
 * Vista de Detalle y Pagos de Cuotas de Préstamo Bancario (TAREA-052).
 */
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb de Navegación -->
    <?php
    $migas = [
        ['label' => 'Préstamos', 'url' => '/Cycsa/publico/prestamos'],
        ['label' => 'Préstamos Bancarios', 'url' => '/Cycsa/publico/prestamos'],
        ['label' => 'Detalle ' . ($prestamo['codigo'] ?? '')]
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <!-- Encabezado y Acciones -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2">
                <h1 class="h3 mb-0 text-gray-800 fw-bold">
                    <i class="fa-solid fa-file-invoice-dollar text-primary me-2"></i>Préstamo <?= htmlspecialchars($prestamo['codigo']) ?>
                </h1>
                <?php if ($prestamo['estado'] === 'activo'): ?>
                    <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                        <i class="fa-solid fa-circle-play me-1"></i>Activo
                    </span>
                <?php elseif ($prestamo['estado'] === 'cancelado'): ?>
                    <span class="badge bg-secondary bg-opacity-10 text-secondary border border-secondary border-opacity-25 px-2 py-1">
                        <i class="fa-solid fa-check-double me-1"></i>Cancelado
                    </span>
                <?php else: ?>
                    <span class="badge bg-danger bg-opacity-10 text-danger border border-danger border-opacity-25 px-2 py-1">
                        <i class="fa-solid fa-ban me-1"></i>Anulado
                    </span>
                <?php endif; ?>
            </div>
            <p class="text-muted mb-0 small"><?= htmlspecialchars($prestamo['institucion_financiera']) ?> &bull; Desembolsado el <?= date('d/m/Y', strtotime($prestamo['fecha_desembolso'])) ?></p>
        </div>
        <div class="d-flex gap-2">
            <a href="/Cycsa/publico/prestamos" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-arrow-left me-1"></i>Regresar
            </a>
            <?php if (!empty($prestamo['id_asiento_desembolso'])): ?>
                <a href="/Cycsa/publico/contabilidad/diario?busqueda=<?= urlencode($prestamo['partida_desembolso'] ?? '') ?>" class="btn btn-outline-primary btn-sm px-3" target="_blank">
                    <i class="fa-solid fa-book-journal-whills me-1"></i>Partida Desembolso (<?= htmlspecialchars($prestamo['partida_desembolso'] ?? 'PD') ?>)
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Barra de Subnavegación del Módulo -->
    <?php require __DIR__ . '/parciales/subnav_prestamos.php'; ?>


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

    <!-- Resumen de Métricas del Préstamo -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-xs fw-bold text-muted text-uppercase mb-1">Monto Original Financiado</div>
                    <div class="h5 mb-0 fw-bold text-dark">
                        C$ <?= number_format($prestamo['monto_prestamo'], 2) ?>
                    </div>
                    <div class="text-muted text-xs mt-1">Tasa: <?= number_format($prestamo['tasa_interes_anual'], 2) ?>% anual &bull; <?= $prestamo['plazo_meses'] ?> meses</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-xs fw-bold text-danger text-uppercase mb-1">Saldo Capital Pendiente</div>
                    <div class="h5 mb-0 fw-bold text-danger">
                        C$ <?= number_format($prestamo['saldo_capital'], 2) ?>
                    </div>
                    <div class="text-muted text-xs mt-1">Pasivo exigible en libros</div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <div class="text-xs fw-bold text-success text-uppercase mb-1">Capital Pagado</div>
                    <div class="h5 mb-0 fw-bold text-success">
                        C$ <?= number_format($prestamo['capital_pagado'] ?? 0, 2) ?>
                    </div>
                    <div class="text-muted text-xs mt-1">Intereses pagados: C$ <?= number_format($prestamo['intereses_pagados'] ?? 0, 2) ?></div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm h-100">
                <div class="card-body p-3">
                    <?php
                    $totalCuotas = (int)($prestamo['total_cuotas'] ?? $prestamo['plazo_meses']);
                    $cuotasPagadas = (int)($prestamo['cuotas_pagadas'] ?? 0);
                    $porcentaje = $totalCuotas > 0 ? round(($cuotasPagadas / $totalCuotas) * 100) : 0;
                    ?>
                    <div class="text-xs fw-bold text-primary text-uppercase mb-1">Progreso de Amortización</div>
                    <div class="h5 mb-1 fw-bold text-dark">
                        <?= $cuotasPagadas ?> de <?= $totalCuotas ?> cuotas (<?= $porcentaje ?>%)
                    </div>
                    <div class="progress" style="height: 6px;">
                        <div class="progress-bar bg-<?= $porcentaje >= 100 ? 'success' : 'primary' ?>" role="progressbar" style="width: <?= $porcentaje ?>%"></div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Cuotas y Amortización -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-list-ol text-primary me-2"></i>Calendario de Cuotas y Pagos
            </h5>
            <span class="text-muted small">Método Francés (Cuota Nivelada)</span>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-uppercase text-secondary small fw-bold">
                        <tr>
                            <th class="ps-3 py-3 text-center"># Cuota</th>
                            <th class="text-center">Vencimiento</th>
                            <th class="text-end">Cuota Total</th>
                            <th class="text-end">Amortización Capital</th>
                            <th class="text-end">Interés</th>
                            <th class="text-end">Saldo Pendiente</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center">Fecha Pago</th>
                            <th class="text-center">Asiento Pago</th>
                            <th class="text-center pe-3">Acción</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($cuotas)): ?>
                        <tr>
                            <td colspan="10" class="text-center py-5 text-muted">
                                No se encontraron cuotas para este préstamo.
                            </td>
                        </tr>
                        <?php else: ?>
                            <?php foreach ($cuotas as $c): ?>
                                <tr class="<?= $c['estado'] === 'pagada' ? 'table-light text-muted' : '' ?>">
                                    <td class="ps-3 text-center fw-bold text-dark">
                                        <?= $c['numero_cuota'] ?>
                                    </td>
                                    <td class="text-center">
                                        <?= date('d/m/Y', strtotime($c['fecha_vencimiento'])) ?>
                                    </td>
                                    <td class="text-end fw-bold text-primary">
                                        C$ <?= number_format($c['cuota_total'], 2) ?>
                                    </td>
                                    <td class="text-end text-dark">
                                        C$ <?= number_format($c['amortizacion_capital'], 2) ?>
                                    </td>
                                    <td class="text-end text-danger">
                                        C$ <?= number_format($c['interes'], 2) ?>
                                    </td>
                                    <td class="text-end fw-semibold text-dark">
                                        C$ <?= number_format($c['saldo_pendiente'], 2) ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if ($c['estado'] === 'pagada'): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                                <i class="fa-solid fa-check me-1"></i>Pagada
                                            </span>
                                        <?php else: ?>
                                            <?php
                                            $vencida = strtotime($c['fecha_vencimiento']) < strtotime(date('Y-m-d'));
                                            ?>
                                            <span class="badge bg-<?= $vencida ? 'danger' : 'warning' ?> bg-opacity-10 text-<?= $vencida ? 'danger' : 'warning' ?> border border-<?= $vencida ? 'danger' : 'warning' ?> border-opacity-25 px-2 py-1">
                                                <i class="fa-solid fa-clock me-1"></i><?= $vencida ? 'Vencida' : 'Pendiente' ?>
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center text-muted">
                                        <?= !empty($c['fecha_pago']) ? date('d/m/Y', strtotime($c['fecha_pago'])) : '-' ?>
                                    </td>
                                    <td class="text-center">
                                        <?php if (!empty($c['id_asiento_pago'])): ?>
                                            <a href="/Cycsa/publico/contabilidad/diario?busqueda=<?= urlencode($c['partida_pago'] ?? '') ?>" class="badge bg-primary bg-opacity-10 text-primary text-decoration-none border border-primary border-opacity-25" target="_blank" title="Ver Partida de Diario">
                                                <i class="fa-solid fa-receipt me-1"></i><?= htmlspecialchars($c['partida_pago'] ?? 'PD') ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="text-muted">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-3">
                                        <?php if ($c['estado'] === 'pendiente' && $prestamo['estado'] === 'activo'): ?>
                                            <button type="button" class="btn btn-sm btn-success px-2 py-1" onclick="abrirModalPago(<?= htmlspecialchars(json_encode($c)) ?>)">
                                                <i class="fa-solid fa-hand-holding-dollar me-1"></i>Pagar
                                            </button>
                                        <?php else: ?>
                                            <span class="text-muted small">&bull;</span>
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

<!-- Modal para Registrar Pago de Cuota -->
<div class="modal fade" id="modalPagoCuota" tabindex="-1" aria-labelledby="modalPagoLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="/Cycsa/publico/prestamos/pagar-cuota" id="formPagarCuota">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="prestamo_id" value="<?= $prestamo['id'] ?>">
                <input type="hidden" name="cuota_id" id="modalCuotaId" value="">

                <div class="modal-header bg-success text-white">
                    <h5 class="modal-title fw-bold" id="modalPagoLabel">
                        <i class="fa-solid fa-money-check-dollar me-2"></i>Pagar Cuota #<span id="modalNumCuota"></span>
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <!-- Resumen de Cuota -->
                    <div class="bg-light p-3 rounded mb-3 border">
                        <div class="d-flex justify-content-between mb-1">
                            <span class="text-muted small">Cuota Total a Debitar:</span>
                            <span class="fw-bold text-primary fs-6" id="modalCuotaTotal">C$ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between text-xs mb-1">
                            <span class="text-muted">&bull; Amortización de Capital (D: Pasivo):</span>
                            <span class="fw-semibold text-dark" id="modalAmortizacion">C$ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between text-xs">
                            <span class="text-muted">&bull; Gastos Financieros Intereses (D: Gasto):</span>
                            <span class="fw-semibold text-danger" id="modalInteres">C$ 0.00</span>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Fecha de Pago *</label>
                        <input type="date" name="fecha_pago" id="modalFechaPago" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaDefecto) ?>" required>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-bold">Cuenta Bancaria para Débito *</label>
                        <select name="cuenta_banco_id" id="modalCuentaBancoId" class="form-select form-select-sm" required>
                            <?php foreach ($cuentasBancarias as $b): ?>
                                <option value="<?= $b['id'] ?>" <?= $b['id'] == $prestamo['cuenta_banco_id'] ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($b['banco_nombre']) ?> - <?= htmlspecialchars($b['numero_cuenta']) ?> (Saldo: <?= $b['moneda'] ?> <?= number_format($b['saldo_actual'], 2) ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text text-xs">Se registrará automáticamente la transacción de retiro y el débito contable (Haber: Banco).</div>
                    </div>

                    <div class="alert alert-info py-2 px-3 small mb-0">
                        <i class="fa-solid fa-circle-info me-1"></i>El sistema generará el asiento contable balanceado en estricta partida doble y descontará el capital del préstamo.
                    </div>
                </div>
                <div class="modal-footer bg-light py-2">
                    <button type="button" class="btn btn-outline-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success btn-sm px-4 fw-bold">
                        <i class="fa-solid fa-check me-1"></i>Confirmar Pago
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
let modalPagoInstancia = null;

function abrirModalPago(cuota) {
    document.getElementById('modalCuotaId').value = cuota.id;
    document.getElementById('modalNumCuota').innerText = cuota.numero_cuota;
    document.getElementById('modalCuotaTotal').innerText = 'C$ ' + Number(cuota.cuota_total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('modalAmortizacion').innerText = 'C$ ' + Number(cuota.amortizacion_capital).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('modalInteres').innerText = 'C$ ' + Number(cuota.interes).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    if (!modalPagoInstancia) {
        modalPagoInstancia = new bootstrap.Modal(document.getElementById('modalPagoCuota'));
    }
    modalPagoInstancia.show();
}
</script>
