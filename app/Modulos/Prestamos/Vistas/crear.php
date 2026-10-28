<?php
/**
 * Vista de Registro y Simulación de Préstamo Bancario (TAREA-052).
 */
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb de Navegación -->
    <?php
    $migas = [
        ['label' => 'Préstamos', 'url' => '/Cycsa/publico/prestamos'],
        ['label' => 'Préstamos Bancarios', 'url' => '/Cycsa/publico/prestamos'],
        ['label' => 'Nuevo Préstamo']
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <!-- Encabezado y Acción -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-calculator text-primary me-2"></i>Nuevo Préstamo Bancario
            </h1>
            <p class="text-muted mb-0 small">Simulación de amortización cuota nivelada (método francés) y registro contable de desembolso.</p>
        </div>
        <div>
            <a href="/Cycsa/publico/prestamos" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="fa-solid fa-arrow-left me-1"></i>Volver a Préstamos
            </a>
        </div>
    </div>

    <!-- Barra de Subnavegación del Módulo -->
    <?php require __DIR__ . '/parciales/subnav_prestamos.php'; ?>

    <!-- Mensajes Flash -->
    <?php if (!empty($_SESSION['mensaje_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show shadow-sm" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['mensaje_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
        <?php unset($_SESSION['mensaje_error']); ?>
    <?php endif; ?>

    <form method="POST" action="/Cycsa/publico/prestamos/guardar" id="formPrestamo">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">

        <div class="row g-4">
            <!-- Formulario de Parámetros -->
            <div class="col-lg-5">
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-sliders text-primary me-2"></i>Parámetros Financieros
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Código de Préstamo *</label>
                                <input type="text" name="codigo" id="codigoPrestamo" class="form-control form-control-sm font-monospace fw-bold" value="<?= htmlspecialchars($sugerenciaCodigo) ?>" required>
                                <div class="form-text text-xs">Identificador único del crédito.</div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Institución Financiera *</label>
                                <input type="text" name="institucion_financiera" id="institucionFinanciera" class="form-control form-control-sm" placeholder="Ej. BANPRO, BAC, LAFISE..." list="listaBancos" required>
                                <datalist id="listaBancos">
                                    <option value="BANPRO">
                                    <option value="BAC CREDOMATIC">
                                    <option value="BANCO LAFISE BANCENTRO">
                                    <option value="BANCO BDF">
                                    <option value="BANCO FICOHSA">
                                </datalist>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Monto del Préstamo (C$) *</label>
                                <div class="input-group input-group-sm">
                                    <span class="input-group-text bg-light">C$</span>
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]+)?" name="monto_prestamo" id="montoPrestamo" class="form-control form-control-sm fw-bold text-end" placeholder="0.00" required autocomplete="off">
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Tasa Interés Anual (%) *</label>
                                <div class="input-group input-group-sm">
                                    <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]+)?" name="tasa_interes_anual" id="tasaInteresAnual" class="form-control form-control-sm text-end" placeholder="12.00" value="12.00" required autocomplete="off">
                                    <span class="input-group-text bg-light">%</span>
                                </div>
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Plazo (Meses) *</label>
                                <input type="text" inputmode="numeric" pattern="[0-9]+" name="plazo_meses" id="plazoMeses" class="form-control form-control-sm text-end" placeholder="12" value="12" required autocomplete="off">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Fecha Desembolso *</label>
                                <input type="date" name="fecha_desembolso" id="fechaDesembolso" class="form-control form-control-sm" value="<?= htmlspecialchars($fechaDefecto) ?>" required>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Cuenta Bancaria Receptora *</label>
                                <select name="cuenta_banco_id" id="cuentaBancoId" class="form-select form-select-sm" required>
                                    <option value="">-- Seleccionar cuenta bancaria --</option>
                                    <?php foreach ($cuentasBancarias as $b): ?>
                                        <option value="<?= $b['id'] ?>">
                                            <?= htmlspecialchars($b['banco_nombre']) ?> - <?= htmlspecialchars($b['numero_cuenta']) ?> (Saldo: <?= $b['moneda'] ?> <?= number_format($b['saldo_actual'], 2) ?>)
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text text-xs">El monto desembolsado incrementará el saldo de esta cuenta bancaria.</div>
                            </div>

                            <div class="col-12">
                                <label class="form-label small fw-bold">Cuenta Pasivo Contable</label>
                                <select name="cuenta_pasivo_id" id="cuentaPasivoId" class="form-select form-select-sm">
                                    <option value="">-- Automático por defecto (config_cuentas: PRESTAMOS_BANCARIOS) --</option>
                                    <?php foreach ($cuentasPasivo as $cp): ?>
                                        <option value="<?= $cp['id'] ?>">
                                            <?= htmlspecialchars($cp['codigo']) ?> - <?= htmlspecialchars($cp['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                                <div class="form-text text-xs">Cuenta contable acreditada en el asiento de desembolso (Haber: Pasivo Préstamo).</div>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="simularAmortizacion()">
                                <i class="fa-solid fa-chart-line me-1"></i>Calcular Simulación
                            </button>
                            <button type="submit" class="btn btn-primary btn-sm fw-bold shadow-sm" id="btnGuardar">
                                <i class="fa-solid fa-check-circle me-1"></i>Registrar y Desembolsar Préstamo
                            </button>
                        </div>
                    </div>
                </div>

                <!-- Resumen Financiero -->
                <div class="card border-0 shadow-sm bg-light">
                    <div class="card-body p-3">
                        <h6 class="fw-bold mb-3 text-secondary">
                            <i class="fa-solid fa-circle-info me-1"></i>Resumen Proyectado (Método Francés)
                        </h6>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted small">Cuota Mensual Nivelada:</span>
                            <span class="fw-bold text-dark small" id="lblCuotaMensual">C$ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted small">Total Capital a Amortizar:</span>
                            <span class="fw-bold text-dark small" id="lblTotalCapital">C$ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 border-bottom">
                            <span class="text-muted small">Total Intereses Proyectados:</span>
                            <span class="fw-bold text-danger small" id="lblTotalInteres">C$ 0.00</span>
                        </div>
                        <div class="d-flex justify-content-between py-1 pt-2">
                            <span class="fw-bold text-dark small">Costo Total del Crédito:</span>
                            <span class="fw-bold text-primary small" id="lblTotalPagar">C$ 0.00</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Tabla de Amortización Proyectada -->
            <div class="col-lg-7">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-table text-primary me-2"></i>Tabla de Amortización Preliminar
                        </h5>
                        <span class="badge bg-primary bg-opacity-10 text-primary border border-primary border-opacity-25" id="lblBadgeCuotas">
                            0 cuotas
                        </span>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive" style="max-height: 580px; overflow-y: auto;">
                            <table class="table table-hover table-striped align-middle mb-0" id="tablaAmortizacion">
                                <thead class="table-light text-uppercase text-secondary small fw-bold sticky-top">
                                    <tr>
                                        <th class="ps-3 py-2 text-center"># Cuota</th>
                                        <th class="text-center">Vencimiento</th>
                                        <th class="text-end">Cuota Total</th>
                                        <th class="text-end">Capital</th>
                                        <th class="text-end">Interés</th>
                                        <th class="text-end pe-3">Saldo Capital</th>
                                    </tr>
                                </thead>
                                <tbody class="small text-muted" id="cuerpoTablaAmortizacion">
                                    <tr>
                                        <td colspan="6" class="text-center py-5 text-muted">
                                            <i class="fa-solid fa-calculator fa-2x mb-2 text-secondary opacity-50"></i>
                                            <p class="mb-0">Ingrese el monto, tasa y plazo para simular la tabla de amortización.</p>
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function simularAmortizacion() {
    const monto = parseFloat(document.getElementById('montoPrestamo').value.replace(',', '.')) || 0;
    const tasa = parseFloat(document.getElementById('tasaInteresAnual').value.replace(',', '.')) || 0;
    const plazo = parseInt(document.getElementById('plazoMeses').value) || 0;
    const fecha = document.getElementById('fechaDesembolso').value;

    if (monto <= 0 || plazo <= 0) {
        return;
    }

    const formData = new FormData();
    formData.append('monto_prestamo', monto);
    formData.append('tasa_interes_anual', tasa);
    formData.append('plazo_meses', plazo);
    formData.append('fecha_desembolso', fecha);
    formData.append('es_ajax', '1');

    fetch('/Cycsa/publico/prestamos/simular-ajax', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(data => {
        if (!data.ok) {
            alert(data.error || 'Error al calcular amortización.');
            return;
        }

        document.getElementById('lblCuotaMensual').innerText = 'C$ ' + Number(data.cuota_mensual).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('lblTotalCapital').innerText = 'C$ ' + Number(data.total_capital).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('lblTotalInteres').innerText = 'C$ ' + Number(data.total_interes).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('lblTotalPagar').innerText = 'C$ ' + Number(data.total_pagar).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
        document.getElementById('lblBadgeCuotas').innerText = data.cuotas.length + ' cuotas';

        const tbody = document.getElementById('cuerpoTablaAmortizacion');
        tbody.innerHTML = '';

        data.cuotas.forEach(c => {
            const tr = document.createElement('tr');
            tr.innerHTML = `
                <td class="ps-3 text-center fw-bold text-dark">${c.numero_cuota}</td>
                <td class="text-center">${c.fecha_vencimiento}</td>
                <td class="text-end fw-bold text-primary">C$ ${Number(c.cuota_total).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="text-end text-dark">C$ ${Number(c.amortizacion_capital).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="text-end text-danger">C$ ${Number(c.interes).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                <td class="text-end pe-3 fw-semibold text-dark">C$ ${Number(c.saldo_pendiente).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
            `;
            tbody.appendChild(tr);
        });
    })
    .catch(err => {
        console.error("Error en simulación:", err);
    });
}

// Disparar simulación al cambiar montos o plazos
document.getElementById('montoPrestamo').addEventListener('input', simularAmortizacion);
document.getElementById('tasaInteresAnual').addEventListener('input', simularAmortizacion);
document.getElementById('plazoMeses').addEventListener('input', simularAmortizacion);
document.getElementById('fechaDesembolso').addEventListener('change', simularAmortizacion);
</script>
