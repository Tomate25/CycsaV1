<?php
/**
 * Vista para Configurar y Procesar Nueva Nómina (TAREA-050)
 */
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb de Navegación -->
    <?php
    $migas = [
        ['label' => 'Nómina', 'url' => '/Cycsa/publico/nomina'],
        ['label' => 'Nóminas y Planillas', 'url' => '/Cycsa/publico/nomina'],
        ['label' => 'Procesar Nueva Nómina']
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <!-- Header y Acción -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-calculator text-primary me-2"></i>Procesar Nueva Nómina
            </h1>
            <p class="text-muted mb-0 small">Cálculo de deducciones de ley (INSS 7%, IR Ley 822), amortización de anticipos y provisiones patronales.</p>
        </div>
        <div>
            <a href="/Cycsa/publico/nomina" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="fa-solid fa-arrow-left me-1"></i>Volver a Nóminas
            </a>
        </div>
    </div>

    <!-- Barra de Subnavegación del Módulo -->
    <?php require __DIR__ . '/parciales/subnav_nomina.php'; ?>

    <!-- Formulario de Nómina -->
    <form method="POST" action="/Cycsa/publico/nomina/procesar" id="formProcesarNomina">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <input type="hidden" name="empleados" id="empleadosJson" value="">

        <!-- Cabecera del Periodo -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3 text-dark">
                    <i class="fa-solid fa-calendar-check text-primary me-2"></i>1. Parámetros del Periodo de Nómina
                </h5>
                <div class="row g-3">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Código de Nómina *</label>
                        <input type="text" name="codigo" id="codigoNomina" class="form-control form-control-sm font-monospace fw-bold" value="<?= htmlspecialchars($sugerenciaCodigo) ?>" required>
                        <div class="form-text small">Identificador único consecutivo del lote de nómina.</div>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Periodo Inicio *</label>
                        <input type="date" name="periodo_inicio" id="periodoInicio" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaInicioDefecto) ?>" required>
                    </div>
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Periodo Fin *</label>
                        <input type="date" name="periodo_fin" id="periodoFin" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaFinDefecto) ?>" required>
                    </div>
                </div>
            </div>
        </div>

        <!-- Tabla de Colaboradores e Imputación de Horas Extras y Anticipos -->
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0 text-dark">
                    <i class="fa-solid fa-users text-primary me-2"></i>2. Detalle de Colaboradores a Procesar (<?= count($empleados) ?> Activos)
                </h5>
                <button type="button" class="btn btn-sm btn-outline-primary" onclick="recalcularTotales()">
                    <i class="fa-solid fa-rotate me-1"></i>Actualizar Cálculos
                </button>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" id="tablaDetallesNomina">
                        <thead class="table-light">
                            <tr>
                                <th class="ps-3" style="width: 40px;">
                                    <input type="checkbox" id="checkTodos" checked onchange="toggleTodos(this)">
                                </th>
                                <th>Colaborador</th>
                                <th>Depto</th>
                                <th class="text-end" style="width: 140px;">Salario Base (C$)</th>
                                <th class="text-end" style="width: 130px;">H. Extras (C$)</th>
                                <th class="text-end">Devengado</th>
                                <th class="text-end">INSS Lab (7%)</th>
                                <th class="text-end">IR Salarios</th>
                                <th class="text-end" style="width: 130px;">Desc. Anticipo</th>
                                <th class="text-end pe-3">Neto a Pagar</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($empleados)): ?>
                                <tr>
                                    <td colspan="10" class="text-center py-5 text-muted">
                                        No hay empleados en estado activo para procesar nómina.
                                    </td>
                                </tr>
                            <?php else: ?>
                                <?php foreach ($empleados as $idx => $emp): ?>
                                    <tr class="fila-empleado" data-id="<?= $emp['id'] ?>" data-saldo-ant="<?= (float)$emp['saldo_anticipos'] ?>">
                                        <td class="ps-3">
                                            <input type="checkbox" class="check-emp" checked onchange="recalcularTotales()">
                                        </td>
                                        <td>
                                            <div class="fw-semibold text-dark"><?= htmlspecialchars($emp['nombre']) ?></div>
                                            <div class="small text-muted font-monospace"><?= htmlspecialchars($emp['codigo']) ?> — <?= htmlspecialchars($emp['puesto']) ?></div>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= htmlspecialchars($emp['departamento_codigo']) ?>
                                            </span>
                                        </td>
                                        <td class="text-end">
                                            <input type="number" step="0.01" class="form-control form-control-sm text-end font-monospace input-salario" value="<?= number_format($emp['salario_base'], 2, '.', '') ?>" oninput="recalcularTotales()">
                                        </td>
                                        <td class="text-end">
                                            <input type="number" step="0.01" class="form-control form-control-sm text-end font-monospace input-he" value="0.00" oninput="recalcularTotales()">
                                        </td>
                                        <td class="text-end font-monospace fw-bold col-devengado">
                                            C$ <?= number_format($emp['salario_base'], 2) ?>
                                        </td>
                                        <td class="text-end font-monospace text-danger col-inss">
                                            C$ <?= number_format($emp['salario_base'] * 0.07, 2) ?>
                                        </td>
                                        <td class="text-end font-monospace text-danger col-ir">
                                            C$ 0.00
                                        </td>
                                        <td class="text-end">
                                            <?php
                                            $sAnt = (float)$emp['saldo_anticipos'];
                                            ?>
                                            <input type="number" step="0.01" class="form-control form-control-sm text-end font-monospace input-anticipo" value="<?= number_format($sAnt, 2, '.', '') ?>" max="<?= $sAnt ?>" oninput="recalcularTotales()" title="Saldo pendiente total: C$ <?= number_format($sAnt, 2) ?>">
                                            <?php if ($sAnt > 0): ?>
                                                <div class="text-muted small font-monospace" style="font-size: 10px;">Saldo: <?= number_format($sAnt, 2) ?></div>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end pe-3 font-monospace fw-bold text-success col-neto">
                                            C$ 0.00
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Resumen de Totales y Cargas Patronales -->
        <div class="row g-3 mb-4">
            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark text-uppercase small mb-3">
                            <i class="fa-solid fa-receipt text-primary me-2"></i>Resumen Salarial y Deducciones
                        </h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Total Devengado (Salario Base + HE):</span>
                            <span class="fw-bold font-monospace" id="kpiDevengado">C$ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Deducción INSS Laboral (7%):</span>
                            <span class="text-danger font-monospace" id="kpiInssLab">C$ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Retención IR Salarios (DGI):</span>
                            <span class="text-danger font-monospace" id="kpiIrLab">C$ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Amortización Anticipos de Salario:</span>
                            <span class="text-danger font-monospace" id="kpiAnticipos">C$ 0.00</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-0">
                            <span class="fw-bold text-dark">Total Neto a Desembolsar:</span>
                            <span class="fw-bold font-monospace text-success fs-5" id="kpiNeto">C$ 0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="col-md-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body p-4">
                        <h6 class="fw-bold text-dark text-uppercase small mb-3">
                            <i class="fa-solid fa-building-columns text-primary me-2"></i>Provisiones Laborales y Cargas Patronales
                        </h6>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">INSS Patronal (22.5%):</span>
                            <span class="fw-semibold font-monospace" id="kpiInssPat">C$ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">INATEC Patronal (2%):</span>
                            <span class="fw-semibold font-monospace" id="kpiInatec">C$ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Provisión Vacaciones (8.33%):</span>
                            <span class="fw-semibold font-monospace" id="kpiVacaciones">C$ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Provisión Treceavo Mes / Aguinaldo (8.33%):</span>
                            <span class="fw-semibold font-monospace" id="kpiAguinaldo">C$ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between mb-2">
                            <span class="text-muted">Provisión Indemnización Laboral (8.33%):</span>
                            <span class="fw-semibold font-monospace" id="kpiIndemnizacion">C$ 0.00</span>
                        </div>
                        <hr class="my-2">
                        <div class="d-flex justify-content-between mb-0">
                            <span class="fw-bold text-dark">Costo Total Empresa (Devengado + Cargas + Prov):</span>
                            <span class="fw-bold font-monospace text-primary fs-5" id="kpiCostoTotal">C$ 0.00</span>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="d-flex justify-content-end gap-2 mb-5">
            <a href="/Cycsa/publico/nomina" class="btn btn-outline-secondary">Cancelar</a>
            <button type="submit" class="btn btn-primary px-4 fw-bold" id="btnConfirmarProcesar">
                <i class="fa-solid fa-check-double me-2"></i>Procesar Nómina y Asentar en Contabilidad
            </button>
        </div>
    </form>
</div>

<script>
function calcularIr(devengado, inssLab) {
    const baseMensual = Math.max(0, devengado - inssLab);
    const baseAnual = baseMensual * 12;
    let irAnual = 0;
    if (baseAnual > 500000) {
        irAnual = 82500 + (baseAnual - 500000) * 0.30;
    } else if (baseAnual > 350000) {
        irAnual = 45000 + (baseAnual - 350000) * 0.25;
    } else if (baseAnual > 200000) {
        irAnual = 15000 + (baseAnual - 200000) * 0.20;
    } else if (baseAnual > 100000) {
        irAnual = (baseAnual - 100000) * 0.15;
    } else {
        irAnual = 0;
    }
    return Math.round((irAnual / 12) * 100) / 100;
}

function recalcularTotales() {
    let totDevengado = 0;
    let totInssLab = 0;
    let totIrLab = 0;
    let totAnticipos = 0;
    let totNeto = 0;
    let totInssPat = 0;
    let totInatec = 0;
    let totVac = 0;
    let totAguin = 0;
    let totIndem = 0;

    const filas = document.querySelectorAll('.fila-empleado');
    const empleadosPayload = [];

    filas.forEach(fila => {
        const check = fila.querySelector('.check-emp');
        if (!check || !check.checked) return;

        const empId = parseInt(fila.getAttribute('data-id'));
        const salario = parseFloat(fila.querySelector('.input-salario').value) || 0;
        const he = parseFloat(fila.querySelector('.input-he').value) || 0;
        const antInput = fila.querySelector('.input-anticipo');
        let anticipo = parseFloat(antInput ? antInput.value : 0) || 0;

        const devengado = Math.round((salario + he) * 100) / 100;
        const inssLab = Math.round((devengado * 0.07) * 100) / 100;
        const irLab = calcularIr(devengado, inssLab);

        const netoPrevio = Math.max(0, devengado - inssLab - irLab);
        if (anticipo > netoPrevio) {
            anticipo = netoPrevio;
            if (antInput) antInput.value = anticipo.toFixed(2);
        }

        const deducciones = Math.round((inssLab + irLab + anticipo) * 100) / 100;
        const neto = Math.round((devengado - deducciones) * 100) / 100;

        // Patronales y Provisiones
        const inssPat = Math.round((devengado * 0.225) * 100) / 100;
        const inatec = Math.round((devengado * 0.02) * 100) / 100;
        const vac = Math.round((devengado * 0.0833) * 100) / 100;
        const aguin = Math.round((devengado * 0.0833) * 100) / 100;
        const indem = Math.round((devengado * 0.0833) * 100) / 100;

        // Actualizar UI en fila
        fila.querySelector('.col-devengado').textContent = 'C$ ' + devengado.toLocaleString('es-NI', {minimumFractionDigits: 2});
        fila.querySelector('.col-inss').textContent = 'C$ ' + inssLab.toLocaleString('es-NI', {minimumFractionDigits: 2});
        fila.querySelector('.col-ir').textContent = 'C$ ' + irLab.toLocaleString('es-NI', {minimumFractionDigits: 2});
        fila.querySelector('.col-neto').textContent = 'C$ ' + neto.toLocaleString('es-NI', {minimumFractionDigits: 2});

        // Sumas globales
        totDevengado += devengado;
        totInssLab += inssLab;
        totIrLab += irLab;
        totAnticipos += anticipo;
        totNeto += neto;
        totInssPat += inssPat;
        totInatec += inatec;
        totVac += vac;
        totAguin += aguin;
        totIndem += indem;

        empleadosPayload.push({
            empleado_id: empId,
            salario_base: salario,
            horas_extras_monto: he,
            anticipos_descontados: anticipo,
            ir_laboral: irLab
        });
    });

    // Guardar payload para el submit
    document.getElementById('empleadosJson').value = JSON.stringify(empleadosPayload);

    // Actualizar KPIs de Resumen
    document.getElementById('kpiDevengado').textContent = 'C$ ' + totDevengado.toLocaleString('es-NI', {minimumFractionDigits: 2});
    document.getElementById('kpiInssLab').textContent = 'C$ ' + totInssLab.toLocaleString('es-NI', {minimumFractionDigits: 2});
    document.getElementById('kpiIrLab').textContent = 'C$ ' + totIrLab.toLocaleString('es-NI', {minimumFractionDigits: 2});
    document.getElementById('kpiAnticipos').textContent = 'C$ ' + totAnticipos.toLocaleString('es-NI', {minimumFractionDigits: 2});
    document.getElementById('kpiNeto').textContent = 'C$ ' + totNeto.toLocaleString('es-NI', {minimumFractionDigits: 2});

    document.getElementById('kpiInssPat').textContent = 'C$ ' + totInssPat.toLocaleString('es-NI', {minimumFractionDigits: 2});
    document.getElementById('kpiInatec').textContent = 'C$ ' + totInatec.toLocaleString('es-NI', {minimumFractionDigits: 2});
    document.getElementById('kpiVacaciones').textContent = 'C$ ' + totVac.toLocaleString('es-NI', {minimumFractionDigits: 2});
    document.getElementById('kpiAguinaldo').textContent = 'C$ ' + totAguin.toLocaleString('es-NI', {minimumFractionDigits: 2});
    document.getElementById('kpiIndemnizacion').textContent = 'C$ ' + totIndem.toLocaleString('es-NI', {minimumFractionDigits: 2});

    const costoTotal = totDevengado + totInssPat + totInatec + totVac + totAguin + totIndem;
    document.getElementById('kpiCostoTotal').textContent = 'C$ ' + costoTotal.toLocaleString('es-NI', {minimumFractionDigits: 2});
}

function toggleTodos(masterCheck) {
    document.querySelectorAll('.check-emp').forEach(cb => {
        cb.checked = masterCheck.checked;
    });
    recalcularTotales();
}

document.addEventListener('DOMContentLoaded', () => {
    recalcularTotales();
});

document.getElementById('formProcesarNomina').addEventListener('submit', function(e) {
    const jsonVal = document.getElementById('empleadosJson').value;
    if (!jsonVal || jsonVal === '[]') {
        e.preventDefault();
        alert('Debe seleccionar al menos un colaborador para procesar la nómina.');
        return;
    }
    if (!confirm('¿Confirma el procesamiento de la nómina? Se registrarán las deducciones y se generará automáticamente el asiento contable balanceado.')) {
        e.preventDefault();
    }
});
</script>
