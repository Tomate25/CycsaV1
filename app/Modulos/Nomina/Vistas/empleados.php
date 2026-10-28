<?php
/**
 * Vista de Gestión de Empleados y Salarios (TAREA-050)
 */
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb de Navegación -->
    <?php
    $migas = [
        ['label' => 'Nómina', 'url' => '/Cycsa/publico/nomina'],
        ['label' => 'Colaboradores']
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <!-- Header y Acción Primaria -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-users text-primary me-2"></i>Colaboradores y Salarios
            </h1>
            <p class="text-muted mb-0 small">Gestión de planilla activa, salarios base y configuración de deducciones legales (INSS/IR).</p>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalEmpleado">
                <i class="fa-solid fa-user-plus me-1"></i>Nuevo Colaborador
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

    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <!-- KPIs -->
    <?php
    $totalEmp = count($empleados);
    $activosEmp = count(array_filter($empleados, fn($e) => $e['estado'] === 'activo'));
    $labEmp = count(array_filter($empleados, fn($e) => $e['departamento_codigo'] === 'LAB'));
    $totalMasaSalarial = array_sum(array_column($empleados, 'salario_base'));
    ?>
    <div class="row g-3 mb-4">
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Total Empleados</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1"><?= $totalEmp ?></h3>
                        <span class="badge bg-success-subtle text-success small mt-1"><?= $activosEmp ?> Activos</span>
                    </div>
                    <div class="bg-primary-subtle text-primary p-3 rounded-circle">
                        <i class="fa-solid fa-users fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Personal Laboratorio</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1"><?= $labEmp ?></h3>
                        <span class="text-muted small">Mano de Obra Directa</span>
                    </div>
                    <div class="bg-info-subtle text-info p-3 rounded-circle">
                        <i class="fa-solid fa-flask-vial fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Personal Administrativo</span>
                        <h3 class="mb-0 fw-bold text-dark mt-1"><?= ($totalEmp - $labEmp) ?></h3>
                        <span class="text-muted small">Admin & Ventas</span>
                    </div>
                    <div class="bg-warning-subtle text-warning p-3 rounded-circle">
                        <i class="fa-solid fa-briefcase fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-12 col-sm-6 col-xl-3">
            <div class="card border-0 shadow-sm rounded-3 p-3">
                <div class="d-flex align-items-center justify-content-between">
                    <div>
                        <span class="text-muted small text-uppercase fw-bold">Masa Salarial Base</span>
                        <h3 class="mb-0 fw-bold text-primary mt-1">C$ <?= number_format($totalMasaSalarial, 2) ?></h3>
                        <span class="text-muted small">Total mensual base</span>
                    </div>
                    <div class="bg-success-subtle text-success p-3 rounded-circle">
                        <i class="fa-solid fa-money-bill-wave fa-xl"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Búsqueda y Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="/Cycsa/publico/nomina/empleados" class="row g-2 align-items-center">
                <div class="col-md-5">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-white border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Buscar por nombre, código, cédula o puesto..." value="<?= htmlspecialchars($busqueda) ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <select name="departamento_id" class="form-select form-select-sm">
                        <option value="">-- Todos los Departamentos --</option>
                        <?php foreach ($departamentos as $dep): ?>
                            <option value="<?= $dep['id'] ?>" <?= $deptoFiltro == $dep['id'] ? 'selected' : '' ?>>
                                <?= htmlspecialchars($dep['nombre']) ?> (<?= htmlspecialchars($dep['codigo']) ?>)
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">-- Todos los Estados --</option>
                        <option value="activo" <?= $estadoFiltro === 'activo' ? 'selected' : '' ?>>Activos</option>
                        <option value="inactivo" <?= $estadoFiltro === 'inactivo' ? 'selected' : '' ?>>Inactivos</option>
                        <option value="liquidado" <?= $estadoFiltro === 'liquidado' ? 'selected' : '' ?>>Liquidados</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-sm btn-primary w-100">
                        <i class="fa-solid fa-filter me-1"></i>Filtrar
                    </button>
                    <?php if ($busqueda !== '' || $estadoFiltro !== '' || $deptoFiltro > 0): ?>
                        <a href="/Cycsa/publico/nomina/empleados" class="btn btn-sm btn-outline-secondary" title="Limpiar Filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla de Empleados -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light">
                        <tr>
                            <th class="ps-3">Código</th>
                            <th>Colaborador</th>
                            <th>Cédula</th>
                            <th>Departamento</th>
                            <th>Puesto</th>
                            <th class="text-end">Salario Base</th>
                            <th class="text-end">Saldo Anticipo</th>
                            <th>Ingreso</th>
                            <th class="text-center">Estado</th>
                            <th class="text-center pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($empleados)): ?>
                            <tr>
                                <td colspan="10" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-user-slash fa-2x mb-3 text-secondary"></i>
                                    <p class="mb-0">No se encontraron empleados registrados con los criterios seleccionados.</p>
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($empleados as $emp): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-primary font-monospace small"><?= htmlspecialchars($emp['codigo']) ?></td>
                                    <td>
                                        <div class="fw-semibold text-dark"><?= htmlspecialchars($emp['nombre']) ?></div>
                                    </td>
                                    <td class="text-muted small"><?= htmlspecialchars($emp['cedula'] ?? '—') ?></td>
                                    <td>
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($emp['departamento_nombre']) ?>
                                        </span>
                                    </td>
                                    <td class="small"><?= htmlspecialchars($emp['puesto']) ?></td>
                                    <td class="text-end fw-bold text-dark font-monospace">
                                        C$ <?= number_format($emp['salario_base'], 2) ?>
                                    </td>
                                    <td class="text-end font-monospace">
                                        <?php if ((float)$emp['saldo_anticipos'] > 0): ?>
                                            <span class="badge bg-danger-subtle text-danger" title="Anticipo pendiente">
                                                C$ <?= number_format($emp['saldo_anticipos'], 2) ?>
                                            </span>
                                        <?php else: ?>
                                            <span class="text-muted small">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="small text-muted"><?= htmlspecialchars($emp['fecha_ingreso']) ?></td>
                                    <td class="text-center">
                                        <?php if ($emp['estado'] === 'activo'): ?>
                                            <span class="badge bg-success">Activo</span>
                                        <?php elseif ($emp['estado'] === 'inactivo'): ?>
                                            <span class="badge bg-secondary">Inactivo</span>
                                        <?php else: ?>
                                            <span class="badge bg-dark">Liquidado</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center pe-3">
                                        <button class="btn btn-sm btn-outline-primary py-0 px-2" title="Editar Empleado" onclick='editarEmpleado(<?= json_encode($emp) ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i>
                                        </button>
                                        <button class="btn btn-sm btn-outline-info py-0 px-2" title="Conceder Anticipo" onclick="abrirAnticipoRapido(<?= (int)$emp['id'] ?>, '<?= addslashes($emp['nombre']) ?>')">
                                            <i class="fa-solid fa-hand-holding-dollar"></i>
                                        </button>
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

<!-- Modal Empleado -->
<div class="modal fade" id="modalEmpleado" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form method="POST" action="/Cycsa/publico/nomina/empleados/guardar" id="formEmpleado">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
                <input type="hidden" name="id" id="emp_id" value="">
                
                <div class="modal-header">
                    <h5 class="modal-title fw-bold" id="modalEmpleadoTitulo">
                        <i class="fa-solid fa-user-plus text-primary me-2"></i>Registrar Nuevo Colaborador
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                
                <div class="modal-body">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Código Interno</label>
                            <input type="text" name="codigo" id="emp_codigo" class="form-control form-control-sm font-monospace" placeholder="Automático (EMP-XXXX)">
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-bold">Nombre Completo *</label>
                            <input type="text" name="nombre" id="emp_nombre" class="form-control form-control-sm" required placeholder="Nombres y Apellidos">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Cédula de Identidad</label>
                            <input type="text" name="cedula" id="emp_cedula" class="form-control form-control-sm" placeholder="001-XXXXXX-XXXXX">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Departamento *</label>
                            <select name="departamento_id" id="emp_departamento_id" class="form-select form-select-sm" required>
                                <option value="">-- Seleccionar --</option>
                                <?php foreach ($departamentos as $dep): ?>
                                    <option value="<?= $dep['id'] ?>"><?= htmlspecialchars($dep['nombre']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Puesto / Cargo *</label>
                            <input type="text" name="puesto" id="emp_puesto" class="form-control form-control-sm" required placeholder="Ej: Técnico de Laboratorio">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Salario Base Mensual (C$) *</label>
                            <input type="number" step="0.01" name="salario_base" id="emp_salario_base" class="form-control form-control-sm font-monospace" required placeholder="0.00">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Fecha de Ingreso *</label>
                            <input type="date" name="fecha_ingreso" id="emp_fecha_ingreso" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-bold">Estado</label>
                            <select name="estado" id="emp_estado" class="form-select form-select-sm">
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                                <option value="liquidado">Liquidado</option>
                            </select>
                        </div>
                        <div class="col-12">
                            <label class="form-label small fw-bold">Cuenta de Anticipo Específica (Opcional)</label>
                            <select name="cuenta_anticipo_id" id="emp_cuenta_anticipo_id" class="form-select form-select-sm">
                                <option value="">-- Por defecto (1010310 ADELANTOS DE SALARIO) --</option>
                                <?php foreach ($cuentasDetalle as $c): ?>
                                    <?php if (str_starts_with($c['codigo'], '10103')): ?>
                                        <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['codigo']) ?> - <?= htmlspecialchars($c['nombre']) ?></option>
                                    <?php endif; ?>
                                <?php endforeach; ?>
                            </select>
                            <div class="form-text small">Permite asignar una subcuenta contable personal si el empleado cuenta con una en el catálogo 10103xx.</div>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-sm btn-secondary" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-sm btn-primary">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Colaborador
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function nuevoEmpleado() {
    document.getElementById('modalEmpleadoTitulo').innerHTML = '<i class="fa-solid fa-user-plus text-primary me-2"></i>Registrar Nuevo Colaborador';
    document.getElementById('emp_id').value = '';
    document.getElementById('emp_codigo').value = '';
    document.getElementById('emp_nombre').value = '';
    document.getElementById('emp_cedula').value = '';
    document.getElementById('emp_departamento_id').value = '';
    document.getElementById('emp_puesto').value = '';
    document.getElementById('emp_salario_base').value = '';
    document.getElementById('emp_fecha_ingreso').value = '<?= date('Y-m-d') ?>';
    document.getElementById('emp_estado').value = 'activo';
    document.getElementById('emp_cuenta_anticipo_id').value = '';
}

function editarEmpleado(emp) {
    document.getElementById('modalEmpleadoTitulo').innerHTML = '<i class="fa-solid fa-user-pen text-primary me-2"></i>Editar Colaborador: ' + emp.nombre;
    document.getElementById('emp_id').value = emp.id;
    document.getElementById('emp_codigo').value = emp.codigo;
    document.getElementById('emp_nombre').value = emp.nombre;
    document.getElementById('emp_cedula').value = emp.cedula || '';
    document.getElementById('emp_departamento_id').value = emp.departamento_id;
    document.getElementById('emp_puesto').value = emp.puesto;
    document.getElementById('emp_salario_base').value = emp.salario_base;
    document.getElementById('emp_fecha_ingreso').value = emp.fecha_ingreso;
    document.getElementById('emp_estado').value = emp.estado;
    document.getElementById('emp_cuenta_anticipo_id').value = emp.cuenta_anticipo_id || '';
    
    var modal = new bootstrap.Modal(document.getElementById('modalEmpleado'));
    modal.show();
}

function abrirAnticipoRapido(empleadoId, nombre) {
    window.location.href = '/Cycsa/publico/nomina/anticipos?empleado_id=' + empleadoId;
}
</script>
