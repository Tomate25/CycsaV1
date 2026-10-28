<?php
/**
 * Vista de Detalle de Nómina y Planilla Procesada (TAREA-050)
 */
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb de Navegación -->
    <?php
    $migas = [
        ['label' => 'Nómina', 'url' => '/Cycsa/publico/nomina'],
        ['label' => 'Nóminas y Planillas', 'url' => '/Cycsa/publico/nomina'],
        ['label' => 'Detalle ' . ($nomina['codigo'] ?? '')]
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <!-- Header y Acciones -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <div class="d-flex align-items-center gap-2 mb-1">
                <h1 class="h3 mb-0 text-gray-800 fw-bold">
                    <i class="fa-solid fa-file-invoice text-primary me-2"></i>Nómina <?= htmlspecialchars($nomina['codigo']) ?>
                </h1>
                <?php if ($nomina['estado'] === 'procesada'): ?>
                    <span class="badge bg-success">Procesada</span>
                <?php elseif ($nomina['estado'] === 'pagada'): ?>
                    <span class="badge bg-primary">Pagada</span>
                <?php elseif ($nomina['estado'] === 'borrador'): ?>
                    <span class="badge bg-warning text-dark">Borrador</span>
                <?php else: ?>
                    <span class="badge bg-secondary">Anulada</span>
                <?php endif; ?>
            </div>
            <p class="text-muted mb-0 small">
                Periodo: <strong><?= htmlspecialchars($nomina['periodo_inicio']) ?></strong> al <strong><?= htmlspecialchars($nomina['periodo_fin']) ?></strong> 
                | Procesada por: <?= htmlspecialchars($nomina['procesada_por_nombre'] ?? 'Administrador') ?> 
                | Fecha: <?= htmlspecialchars($nomina['created_at']) ?>
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/Cycsa/publico/nomina" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-arrow-left me-1"></i>Regresar
            </a>
            <?php if (!empty($nomina['asiento_numero'])): ?>
                <a href="/Cycsa/publico/contabilidad/asientos" class="btn btn-outline-primary btn-sm px-3 font-monospace" title="Ver Asiento Contable">
                    <i class="fa-solid fa-book-journal-whills me-1"></i><?= htmlspecialchars($nomina['asiento_numero']) ?>
                </a>
            <?php endif; ?>
            <button type="button" class="btn btn-sm btn-outline-secondary px-3" onclick="window.print()">
                <i class="fa-solid fa-print me-1"></i>Imprimir Planilla
            </button>
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

    <!-- KPIs de la Nómina -->
    <?php
    $detalles = $nomina['detalles'] ?? [];
    $sumInssPat = array_sum(array_column($detalles, 'inss_patronal'));
    $sumInatec = array_sum(array_column($detalles, 'inatec_patronal'));
    $sumVac = array_sum(array_column($detalles, 'vacaciones_provision'));
    $sumAguin = array_sum(array_column($detalles, 'aguinaldo_provision'));
    $sumIndem = array_sum(array_column($detalles, 'indemnizacion_provision'));
    $totalProvisionesCargas = $sumInssPat + $sumInatec + $sumVac + $sumAguin + $sumIndem;
    ?>
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Total Devengado</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1">C$ <?= number_format($nomina['total_devengado'], 2) ?></h3>
                        <span class="text-muted small"><?= count($detalles) ?> colaboradores</span>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                        <i class="fa-solid fa-money-bill-wave fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Deducciones de Ley</span>
                        <h3 class="mb-0 fw-bold text-danger mt-1">C$ <?= number_format($nomina['total_deducciones'], 2) ?></h3>
                        <span class="text-muted small">INSS Lab + IR + Anticipos</span>
                    </div>
                    <div class="bg-danger-subtle text-danger p-3 rounded-circle">
                        <i class="fa-solid fa-hand-holding-dollar fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Total Neto a Pagar</span>
                        <h3 class="mb-0 fw-bold text-success mt-1">C$ <?= number_format($nomina['total_neto'], 2) ?></h3>
                        <span class="text-muted small">Desembolso efectivo</span>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle">
                        <i class="fa-solid fa-sack-dollar fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Cargas & Provisiones</span>
                        <h3 class="mb-0 fw-bold text-info mt-1">C$ <?= number_format($totalProvisionesCargas, 2) ?></h3>
                        <span class="text-muted small">INSS Pat + Provisiones</span>
                    </div>
                    <div class="bg-info-subtle text-info p-3 rounded-circle">
                        <i class="fa-solid fa-piggy-bank fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Tabla de Planilla Detallada -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-table-list text-primary me-2"></i>Desglose Individual de Colaboradores
            </h5>
        </div>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0 font-monospace" style="font-size: 12.5px;">
                    <thead class="table-light text-nowrap font-sans-serif">
                        <tr>
                            <th class="ps-3">Colaborador</th>
                            <th>Depto</th>
                            <th class="text-end">Salario Base</th>
                            <th class="text-end">H. Extras</th>
                            <th class="text-end text-primary">Devengado</th>
                            <th class="text-end text-danger">INSS Lab (7%)</th>
                            <th class="text-end text-danger">IR Salarios</th>
                            <th class="text-end text-danger">Anticipos</th>
                            <th class="text-end text-danger">Tot. Deduc</th>
                            <th class="text-end text-success fw-bold">Neto a Pagar</th>
                            <th class="text-end text-muted">INSS Pat (22.5%)</th>
                            <th class="text-end text-muted">INATEC (2%)</th>
                            <th class="text-end text-muted">Vac (8.33%)</th>
                            <th class="text-end text-muted">Aguin (8.33%)</th>
                            <th class="text-end pe-3 text-muted">Indem (8.33%)</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($detalles as $d): ?>
                            <tr>
                                <td class="ps-3 font-sans-serif">
                                    <div class="fw-bold text-dark"><?= htmlspecialchars($d['empleado_nombre']) ?></div>
                                    <div class="text-muted small"><?= htmlspecialchars($d['empleado_codigo']) ?> — <?= htmlspecialchars($d['empleado_puesto']) ?></div>
                                </td>
                                <td class="font-sans-serif">
                                    <span class="badge bg-light text-dark border"><?= htmlspecialchars($d['departamento_codigo']) ?></span>
                                </td>
                                <td class="text-end"><?= number_format($d['salario_base'], 2) ?></td>
                                <td class="text-end"><?= number_format($d['horas_extras_monto'], 2) ?></td>
                                <td class="text-end fw-bold text-primary"><?= number_format($d['total_devengado'], 2) ?></td>
                                <td class="text-end text-danger"><?= number_format($d['inss_laboral'], 2) ?></td>
                                <td class="text-end text-danger"><?= number_format($d['ir_laboral'], 2) ?></td>
                                <td class="text-end text-danger"><?= number_format($d['anticipos_descontados'], 2) ?></td>
                                <td class="text-end fw-bold text-danger"><?= number_format($d['total_deducciones'], 2) ?></td>
                                <td class="text-end fw-bold text-success fs-6"><?= number_format($d['neto_a_pagar'], 2) ?></td>
                                <td class="text-end text-muted"><?= number_format($d['inss_patronal'], 2) ?></td>
                                <td class="text-end text-muted"><?= number_format($d['inatec_patronal'], 2) ?></td>
                                <td class="text-end text-muted"><?= number_format($d['vacaciones_provision'], 2) ?></td>
                                <td class="text-end text-muted"><?= number_format($d['aguinaldo_provision'], 2) ?></td>
                                <td class="text-end pe-3 text-muted"><?= number_format($d['indemnizacion_provision'], 2) ?></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                    <tfoot class="table-light fw-bold text-nowrap">
                        <tr>
                            <td class="ps-3 font-sans-serif" colspan="2">TOTALES GENERALES</td>
                            <td class="text-end"><?= number_format(array_sum(array_column($detalles, 'salario_base')), 2) ?></td>
                            <td class="text-end"><?= number_format(array_sum(array_column($detalles, 'horas_extras_monto')), 2) ?></td>
                            <td class="text-end text-primary"><?= number_format($nomina['total_devengado'], 2) ?></td>
                            <td class="text-end text-danger"><?= number_format(array_sum(array_column($detalles, 'inss_laboral')), 2) ?></td>
                            <td class="text-end text-danger"><?= number_format(array_sum(array_column($detalles, 'ir_laboral')), 2) ?></td>
                            <td class="text-end text-danger"><?= number_format(array_sum(array_column($detalles, 'anticipos_descontados')), 2) ?></td>
                            <td class="text-end text-danger"><?= number_format($nomina['total_deducciones'], 2) ?></td>
                            <td class="text-end text-success fs-6"><?= number_format($nomina['total_neto'], 2) ?></td>
                            <td class="text-end text-muted"><?= number_format($sumInssPat, 2) ?></td>
                            <td class="text-end text-muted"><?= number_format($sumInatec, 2) ?></td>
                            <td class="text-end text-muted"><?= number_format($sumVac, 2) ?></td>
                            <td class="text-end text-muted"><?= number_format($sumAguin, 2) ?></td>
                            <td class="text-end pe-3 text-muted"><?= number_format($sumIndem, 2) ?></td>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>
