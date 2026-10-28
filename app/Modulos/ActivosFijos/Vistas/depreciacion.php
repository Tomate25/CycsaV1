<?php
/**
 * Vista de Ejecución y Monitoreo de Depreciación Mensual de Activos Fijos - CYCSA ERP (TAREA-051)
 */
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb de Navegación -->
    <?php
    $migas = [
        ['label' => 'Activos Fijos', 'url' => '/Cycsa/publico/activos-fijos'],
        ['label' => 'Depreciación Mensual']
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-calculator text-primary me-2"></i>Depreciación Mensual Automatizada
            </h1>
            <p class="text-muted mb-0 small">
                Cálculo en línea recta, amortización mensual y generación automática de asientos en partida doble (Gasto vs Depreciación Acumulada).
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/Cycsa/publico/activos-fijos" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-boxes-stacked me-1"></i>Ver Catálogo de Activos
            </a>
        </div>
    </div>

    <!-- Barra de Subnavegación del Módulo -->
    <?php require __DIR__ . '/parciales/subnav_activos.php'; ?>


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

    <!-- Panel de Control del Proceso de Depreciación -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-header bg-white py-3">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-calendar-days text-primary me-2"></i>Procesar Periodo Mensual
            </h5>
        </div>
        <div class="card-body p-4">
            <form method="POST" action="/Cycsa/publico/activos-fijos/depreciacion/ejecutar" id="formEjecutarDepreciacion">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <div class="row g-3 align-items-end">
                    <div class="col-md-4">
                        <label class="form-label small fw-bold">Seleccionar Periodo Mensual *</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light"><i class="fa-regular fa-calendar text-primary"></i></span>
                            <input type="month" name="periodo" id="periodoInput" class="form-control fw-bold" 
                                   value="<?= htmlspecialchars($periodoFiltro ?: $periodoDefecto) ?>" required>
                        </div>
                        <div class="form-text small">Formato estándar YYYY-MM para cierre contable de fin de mes.</div>
                    </div>
                    <div class="col-md-8 d-flex gap-2 justify-content-md-end flex-wrap">
                        <button type="button" class="btn btn-outline-primary px-3" onclick="abrirSimulador()">
                            <i class="fa-solid fa-wand-magic-sparkles me-1"></i>Simular y Previsualizar Asiento
                        </button>
                        <button type="button" class="btn btn-primary px-4 fw-bold" onclick="confirmarEjecucion()">
                            <i class="fa-solid fa-bolt me-1"></i>Ejecutar Depreciación y Contabilizar
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <!-- Filtro y Tabla de Histórico de Depreciaciones -->
    <div class="card border-0 shadow-sm">
        <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
            <h5 class="fw-bold mb-0 text-dark">
                <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i>Historial de Depreciaciones Procesadas
            </h5>
            <form method="GET" action="/Cycsa/publico/activos-fijos/depreciacion" class="d-flex gap-2">
                <input type="month" name="periodo" class="form-control form-control-sm" value="<?= htmlspecialchars($periodoFiltro) ?>">
                <button type="submit" class="btn btn-sm btn-outline-primary">Filtrar</button>
                <?php if ($periodoFiltro !== ''): ?>
                    <a href="/Cycsa/publico/activos-fijos/depreciacion" class="btn btn-sm btn-outline-secondary">Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
        <div class="card-body p-0">
            <?php if (empty($historico)): ?>
                <div class="text-center py-5">
                    <div class="text-muted mb-3">
                        <i class="fa-solid fa-hourglass-empty fa-3x"></i>
                    </div>
                    <h5 class="fw-bold text-muted">No se registran depreciaciones procesadas</h5>
                    <p class="text-muted small">Seleccione un periodo arriba y ejecute la depreciación mensual automatizada.</p>
                </div>
            <?php else: ?>
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0 text-nowrap">
                        <thead class="table-light small text-uppercase">
                            <tr>
                                <th class="ps-3">Periodo</th>
                                <th>Fecha Proceso</th>
                                <th>Activo Fijo</th>
                                <th>Categoría</th>
                                <th class="text-end">Monto Depreciado</th>
                                <th class="text-end">Dep. Acumulada Nueva</th>
                                <th class="text-end">Valor en Libros</th>
                                <th class="text-center pe-3">Asiento Contable</th>
                            </tr>
                        </thead>
                        <tbody class="small">
                            <?php foreach ($historico as $h): ?>
                                <tr>
                                    <td class="ps-3 fw-bold font-monospace text-primary">
                                        <?= htmlspecialchars($h['periodo']) ?>
                                    </td>
                                    <td>
                                        <i class="fa-regular fa-calendar-check me-1 text-muted"></i>
                                        <?= htmlspecialchars($h['fecha_proceso']) ?>
                                    </td>
                                    <td>
                                        <a href="/Cycsa/publico/activos-fijos/detalle?id=<?= $h['activo_fijo_id'] ?>" class="fw-bold text-decoration-none">
                                            <?= htmlspecialchars($h['activo_codigo']) ?>
                                        </a>
                                        <div class="text-muted small"><?= htmlspecialchars($h['activo_descripcion']) ?></div>
                                    </td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= str_replace('_', ' ', $h['activo_categoria']) ?>
                                        </span>
                                    </td>
                                    <td class="text-end text-danger fw-bold font-monospace">
                                        C$ <?= number_format($h['monto_depreciacion'], 2) ?>
                                    </td>
                                    <td class="text-end fw-semibold">
                                        C$ <?= number_format($h['depreciacion_acumulada_nueva'], 2) ?>
                                    </td>
                                    <td class="text-end text-success fw-bold">
                                        C$ <?= number_format($h['valor_en_libros'], 2) ?>
                                    </td>
                                    <td class="text-center pe-3">
                                        <?php if (!empty($h['id_asiento'])): ?>
                                            <a href="/Cycsa/publico/contabilidad/diario" class="badge bg-primary text-decoration-none py-1 px-2">
                                                <i class="fa-solid fa-receipt me-1"></i><?= htmlspecialchars($h['num_partida'] ?? ('#' . $h['id_asiento'])) ?>
                                            </a>
                                        <?php else: ?>
                                            <span class="badge bg-secondary">Sin asiento</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Modal de Simulación y Previsualización -->
<div class="modal fade" id="modalSimulador" tabindex="-1">
    <div class="modal-dialog modal-xl modal-dialog-centered modal-dialog-scrollable">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title fw-bold">
                    <i class="fa-solid fa-wand-magic-sparkles me-2"></i>Previsualización de Depreciación y Asiento
                </h5>
                <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body p-4" id="cuerpoSimulacion">
                <div class="text-center py-5" id="spinnerSimulacion">
                    <div class="spinner-border text-primary" role="status"></div>
                    <p class="text-muted mt-2">Calculando cuotas y saldos de activos fijos...</p>
                </div>
                <div id="resultadoSimulacion" style="display: none;">
                    <div class="row g-3 mb-4">
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small text-uppercase fw-bold">Activos a Depreciar</span>
                                <h3 class="fw-bold text-dark mt-1" id="simTotalActivos">0</h3>
                                <span class="text-muted small" id="simPeriodoTexto">Periodo</span>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="p-3 bg-light rounded-3 border">
                                <span class="text-muted small text-uppercase fw-bold">Monto Total a Depreciar</span>
                                <h3 class="fw-bold text-danger mt-1" id="simMontoTotal">C$ 0.00</h3>
                                <span class="text-muted small">Cargado a Gasto / CIF contra Dep. Acumulada</span>
                            </div>
                        </div>
                    </div>

                    <h6 class="fw-bold text-dark mb-3">Detalle por Activo Fijo:</h6>
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle text-nowrap">
                            <thead class="table-light small">
                                <tr>
                                    <th>Código</th>
                                    <th>Descripción</th>
                                    <th class="text-end">Costo Adq.</th>
                                    <th class="text-end">Dep. Actual</th>
                                    <th class="text-end text-danger">Cuota Mes</th>
                                    <th class="text-end">Nueva Dep. Acum.</th>
                                    <th class="text-end text-success">Nuevo Valor Libros</th>
                                </tr>
                            </thead>
                            <tbody id="simTablaDetalle" class="small"></tbody>
                        </table>
                    </div>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cerrar Previsualización</button>
                <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="ejecutarDesdeModal()">
                    <i class="fa-solid fa-bolt me-1"></i>Confirmar y Ejecutar Ahora
                </button>
            </div>
        </div>
    </div>
</div>

<!-- Modal Confirmación de Ejecución -->
<div class="modal fade" id="modalConfirmarEjecucion" tabindex="-1">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title fw-bold text-primary">
                    <i class="fa-solid fa-circle-question me-2"></i>Confirmar Depreciación Mensual
                </h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <p>¿Está seguro de que desea ejecutar y contabilizar la depreciación mensual para el periodo <strong id="lblConfirmPeriodo"></strong>?</p>
                <ul class="text-muted small mb-0">
                    <li>Se actualizará la depreciación acumulada y el valor en libros de todos los activos activos.</li>
                    <li>Los activos que alcancen su valor residual pasarán automáticamente al estado <em>depreciado</em>.</li>
                    <li>Se registrará automáticamente la partida de diario contable balanceada en partida doble.</li>
                </ul>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                <button type="button" class="btn btn-sm btn-primary fw-bold" onclick="document.getElementById('formEjecutarDepreciacion').submit()">
                    Sí, Ejecutar y Contabilizar
                </button>
            </div>
        </div>
    </div>
</div>

<script>
function abrirSimulador() {
    const periodo = document.getElementById('periodoInput').value;
    if (!periodo) {
        alert('Por favor seleccione un periodo válido (YYYY-MM).');
        return;
    }

    const modal = new bootstrap.Modal(document.getElementById('modalSimulador'));
    modal.show();

    document.getElementById('spinnerSimulacion').style.display = 'block';
    document.getElementById('resultadoSimulacion').style.display = 'none';

    fetch('/Cycsa/publico/activos-fijos/depreciacion/simular-ajax', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: 'periodo=' + encodeURIComponent(periodo)
    })
    .then(r => r.json())
    .then(data => {
        document.getElementById('spinnerSimulacion').style.display = 'none';
        if (!data.ok) {
            alert('Error en simulación: ' + (data.error || 'Ocurrió un error.'));
            modal.hide();
            return;
        }

        const res = data.resultado;
        document.getElementById('simTotalActivos').textContent = res.total_activos;
        document.getElementById('simPeriodoTexto').textContent = 'Periodo ' + res.periodo;
        document.getElementById('simMontoTotal').textContent = 'C$ ' + res.total_depreciacion.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

        const tbody = document.getElementById('simTablaDetalle');
        tbody.innerHTML = '';

        if (res.detalles.length === 0) {
            tbody.innerHTML = '<tr><td colspan="7" class="text-center py-3 text-muted">No existen activos pendientes de depreciar en este periodo.</td></tr>';
        } else {
            res.detalles.forEach(d => {
                const tr = document.createElement('tr');
                tr.innerHTML = `
                    <td class="fw-bold font-monospace">${d.codigo}</td>
                    <td>${d.descripcion}</td>
                    <td class="text-end">C$ ${d.valor_adquisicion.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end">C$ ${d.depreciacion_acumulada_actual.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end text-danger fw-bold">C$ ${d.monto_depreciacion.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end">C$ ${d.depreciacion_acumulada_nueva.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                    <td class="text-end text-success fw-bold">C$ ${d.valor_en_libros_nuevo.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2})}</td>
                `;
                tbody.appendChild(tr);
            });
        }

        document.getElementById('resultadoSimulacion').style.display = 'block';
    })
    .catch(err => {
        document.getElementById('spinnerSimulacion').style.display = 'none';
        alert('Error al conectar con el servidor: ' + err.message);
        modal.hide();
    });
}

function confirmarEjecucion() {
    const periodo = document.getElementById('periodoInput').value;
    if (!periodo) {
        alert('Por favor seleccione un periodo válido (YYYY-MM).');
        return;
    }
    document.getElementById('lblConfirmPeriodo').textContent = periodo;
    new bootstrap.Modal(document.getElementById('modalConfirmarEjecucion')).show();
}

function ejecutarDesdeModal() {
    bootstrap.Modal.getInstance(document.getElementById('modalSimulador')).hide();
    confirmarEjecucion();
}
</script>
