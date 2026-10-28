<?php
// Vista: Maestro de Proveedores (ISO/IEC 17025 §6.6)
$totalProv = count($proveedores);
$evaluadosCount = 0;
$activosCount = 0;
foreach ($proveedores as $p) {
    if (!empty($p['evaluado'])) $evaluadosCount++;
    if (($p['estado'] ?? '') === 'activo') $activosCount++;
}
$pendientesEval = $totalProv - $evaluadosCount;
?>
<div class="container-fluid py-3">

    <!-- Breadcrumb de Navegación -->
    <?php
    $migas = [
        ['label' => 'Compras', 'url' => '/Cycsa/publico/compras/ordenes'],
        ['label' => 'Maestro de Proveedores']
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <!-- Encabezado y Acción Primaria -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-truck-field text-primary me-2"></i>Maestro de Proveedores
            </h1>
            <p class="text-muted small mb-0">Control de proveedores externos calificados según norma <strong>ISO/IEC 17025:2017 §6.6</strong></p>
        </div>
        <div>
            <button type="button" class="btn btn-primary btn-sm px-3 shadow-sm" data-bs-toggle="modal" data-bs-target="#modalProveedor" onclick="abrirModalNuevoProveedor()">
                <i class="fa-solid fa-plus me-1"></i>Nuevo Proveedor
            </button>
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

    <!-- Tarjetas KPI -->
    <div class="row g-3 mb-4">
        <div class="col-xl-3 col-md-6">
            <div class="card border-0 shadow-sm border-start border-primary border-4 h-100 py-2">
                <div class="card-body">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <div class="text-xs fw-bold text-primary text-uppercase mb-1">Total Proveedores</div>
                            <div class="h4 mb-0 fw-bold text-gray-800"><?= $totalProv ?></div>
                        </div>
                        <div class="text-primary bg-primary bg-opacity-10 rounded-circle p-3">
                            <i class="fa-solid fa-users-gear fa-lg"></i>
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
                            <div class="text-xs fw-bold text-success text-uppercase mb-1">Evaluados ISO 17025</div>
                            <div class="h4 mb-0 fw-bold text-gray-800"><?= $evaluadosCount ?></div>
                        </div>
                        <div class="text-success bg-success bg-opacity-10 rounded-circle p-3">
                            <i class="fa-solid fa-certificate fa-lg"></i>
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
                            <div class="text-xs fw-bold text-warning text-uppercase mb-1">Pendientes Evaluación</div>
                            <div class="h4 mb-0 fw-bold text-gray-800"><?= $pendientesEval ?></div>
                        </div>
                        <div class="text-warning bg-warning bg-opacity-10 rounded-circle p-3">
                            <i class="fa-solid fa-triangle-exclamation fa-lg"></i>
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
                            <div class="text-xs fw-bold text-info text-uppercase mb-1">Proveedores Activos</div>
                            <div class="h4 mb-0 fw-bold text-gray-800"><?= $activosCount ?></div>
                        </div>
                        <div class="text-info bg-info bg-opacity-10 rounded-circle p-3">
                            <i class="fa-solid fa-circle-check fa-lg"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <div class="card border-0 shadow-sm mb-4">
        <div class="card-body p-3">
            <form method="GET" action="/Cycsa/publico/compras/proveedores" class="row g-2 align-items-center">
                <div class="col-md-4">
                    <div class="input-group input-group-sm">
                        <span class="input-group-text bg-light border-end-0"><i class="fa-solid fa-magnifying-glass text-muted"></i></span>
                        <input type="text" name="q" class="form-control border-start-0" placeholder="Buscar por RUC, razón social o contacto..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
                <div class="col-md-2">
                    <select name="categoria" class="form-select form-select-sm">
                        <option value="">Todas las Categorías</option>
                        <option value="insumos" <?= ($categoria ?? '') === 'insumos' ? 'selected' : '' ?>>Insumos / Reactivos</option>
                        <option value="calibracion" <?= ($categoria ?? '') === 'calibracion' ? 'selected' : '' ?>>Calibración / Metrología</option>
                        <option value="equipos" <?= ($categoria ?? '') === 'equipos' ? 'selected' : '' ?>>Equipos / Mantenimiento</option>
                        <option value="servicios" <?= ($categoria ?? '') === 'servicios' ? 'selected' : '' ?>>Servicios Generales</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="evaluado" class="form-select form-select-sm">
                        <option value="">Evaluación ISO 17025</option>
                        <option value="1" <?= ($evaluado ?? '') === '1' ? 'selected' : '' ?>>Aprobado / Evaluado</option>
                        <option value="0" <?= ($evaluado ?? '') === '0' ? 'selected' : '' ?>>Pendiente / No Evaluado</option>
                    </select>
                </div>
                <div class="col-md-2">
                    <select name="estado" class="form-select form-select-sm">
                        <option value="">Todos los Estados</option>
                        <option value="activo" <?= ($estado ?? '') === 'activo' ? 'selected' : '' ?>>Activo</option>
                        <option value="inactivo" <?= ($estado ?? '') === 'inactivo' ? 'selected' : '' ?>>Inactivo</option>
                        <option value="bloqueado" <?= ($estado ?? '') === 'bloqueado' ? 'selected' : '' ?>>Bloqueado</option>
                    </select>
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-secondary btn-sm flex-fill">
                        <i class="fa-solid fa-filter me-1"></i>Filtrar
                    </button>
                    <?php if (!empty($busqueda) || !empty($categoria) || !empty($estado) || ($evaluado ?? '') !== ''): ?>
                        <a href="/Cycsa/publico/compras/proveedores" class="btn btn-outline-danger btn-sm" title="Limpiar filtros">
                            <i class="fa-solid fa-xmark"></i>
                        </a>
                    <?php endif; ?>
                </div>
            </form>
        </div>
    </div>

    <!-- Tabla Principal de Proveedores -->
    <div class="card border-0 shadow-sm">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-light text-muted small text-uppercase">
                        <tr>
                            <th class="ps-3">Identificación</th>
                            <th>Razón Social</th>
                            <th>Contacto / Teléfono</th>
                            <th>Categoría</th>
                            <th>Plazo</th>
                            <th>Evaluación ISO 17025 §6.6</th>
                            <th>Estado</th>
                            <th class="text-end pe-3">Acciones</th>
                        </tr>
                    </thead>
                    <tbody class="small">
                        <?php if (empty($proveedores)): ?>
                            <tr>
                                <td colspan="8" class="text-center py-4 text-muted">
                                    <i class="fa-solid fa-box-open fa-2x mb-2 d-block"></i>
                                    No se encontraron proveedores registrados con los criterios seleccionados.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($proveedores as $prov): ?>
                                <tr>
                                    <td class="ps-3">
                                        <span class="badge bg-light text-dark border">
                                            <?= htmlspecialchars($prov['tipo_id'] ?? 'RUC') ?>
                                        </span>
                                        <div class="fw-bold mt-1"><?= htmlspecialchars($prov['identificacion']) ?></div>
                                    </td>
                                    <td>
                                        <div class="fw-bold text-dark"><?= htmlspecialchars($prov['razon_social']) ?></div>
                                        <?php if (!empty($prov['email'])): ?>
                                            <div class="text-muted small"><i class="fa-regular fa-envelope me-1"></i><?= htmlspecialchars($prov['email']) ?></div>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <div><?= htmlspecialchars($prov['contacto'] ?: '—') ?></div>
                                        <?php if (!empty($prov['telefono'])): ?>
                                            <span class="text-muted small"><i class="fa-solid fa-phone me-1"></i><?= htmlspecialchars($prov['telefono']) ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <span class="badge bg-secondary bg-opacity-10 text-secondary text-uppercase px-2 py-1">
                                            <?= htmlspecialchars($prov['categoria'] ?? 'insumos') ?>
                                        </span>
                                    </td>
                                    <td>
                                        <span class="fw-semibold"><?= (int)($prov['plazo_pago_dias'] ?? 30) ?></span> días
                                    </td>
                                    <td>
                                        <?php if (!empty($prov['evaluado'])): ?>
                                            <span class="badge bg-success bg-opacity-10 text-success border border-success border-opacity-25 px-2 py-1">
                                                <i class="fa-solid fa-check-circle me-1"></i>Aprobado
                                            </span>
                                            <?php if (!empty($prov['fecha_evaluacion'])): ?>
                                                <div class="text-muted text-xs mt-1">Eval: <?= htmlspecialchars($prov['fecha_evaluacion']) ?></div>
                                            <?php endif; ?>
                                        <?php else: ?>
                                            <span class="badge bg-warning bg-opacity-10 text-warning text-dark border border-warning border-opacity-25 px-2 py-1">
                                                <i class="fa-solid fa-clock me-1"></i>Pendiente
                                            </span>
                                        <?php endif; ?>
                                    </td>
                                    <td>
                                        <?php
                                        $estadoClase = match($prov['estado'] ?? 'activo') {
                                            'activo' => 'bg-success',
                                            'inactivo' => 'bg-secondary',
                                            'bloqueado' => 'bg-danger',
                                            default => 'bg-secondary'
                                        };
                                        ?>
                                        <span class="badge <?= $estadoClase ?> text-uppercase"><?= htmlspecialchars($prov['estado'] ?? 'activo') ?></span>
                                    </td>
                                    <td class="text-end pe-3">
                                        <button type="button" class="btn btn-outline-info btn-sm me-1" title="Evaluar Proveedor (ISO 17025 §6.6)"
                                                onclick='abrirModalEvaluar(<?= json_encode($prov, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'>
                                            <i class="fa-solid fa-clipboard-check"></i>
                                        </button>
                                        <button type="button" class="btn btn-outline-primary btn-sm" title="Editar Proveedor"
                                                onclick='abrirModalEditarProveedor(<?= json_encode($prov, JSON_HEX_TAG|JSON_HEX_APOS|JSON_HEX_QUOT|JSON_HEX_AMP) ?>)'>
                                            <i class="fa-solid fa-pen-to-square"></i>
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

<!-- MODAL: REGISTRAR / EDITAR PROVEEDOR -->
<div class="modal fade" id="modalProveedor" tabindex="-1" aria-labelledby="modalProveedorLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="/Cycsa/publico/compras/proveedores/guardar">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" id="prov_id" value="">
                
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalProveedorLabel">
                        <i class="fa-solid fa-truck-field text-primary me-2"></i>Registrar Proveedor
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Tipo Identificación *</label>
                            <select name="tipo_id" id="prov_tipo_id" class="form-select form-select-sm" required>
                                <option value="RUC">RUC</option>
                                <option value="cedula">Cédula</option>
                                <option value="extranjero">Extranjero / Pasaporte</option>
                            </select>
                        </div>
                        <div class="col-md-8">
                            <label class="form-label small fw-semibold">Número de Identificación / RUC *</label>
                            <input type="text" name="identificacion" id="prov_identificacion" class="form-select-sm form-control" required placeholder="Ej: J0310000000000">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Razón Social / Nombre Comercial *</label>
                            <input type="text" name="razon_social" id="prov_razon_social" class="form-select-sm form-control" required placeholder="Nombre legal de la empresa">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Persona de Contacto</label>
                            <input type="text" name="contacto" id="prov_contacto" class="form-select-sm form-control" placeholder="Nombre del representante">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Teléfono</label>
                            <input type="text" name="telefono" id="prov_telefono" class="form-select-sm form-control" placeholder="+505 8888-8888">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Correo Electrónico</label>
                            <input type="email" name="email" id="prov_email" class="form-select-sm form-control" placeholder="ventas@proveedor.com">
                        </div>

                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Categoría *</label>
                            <select name="categoria" id="prov_categoria" class="form-select form-select-sm" required>
                                <option value="insumos">Insumos y Reactivos</option>
                                <option value="calibracion">Calibración y Metrología</option>
                                <option value="equipos">Equipos y Repuestos</option>
                                <option value="servicios">Servicios Generales</option>
                            </select>
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Plazo de Pago (Días)</label>
                            <input type="text" inputmode="numeric" pattern="[0-9]+" autocomplete="off" name="plazo_pago_dias" id="prov_plazo_pago" class="form-select-sm form-control" value="30" min="0">
                        </div>
                        <div class="col-md-4">
                            <label class="form-label small fw-semibold">Estado *</label>
                            <select name="estado" id="prov_estado" class="form-select form-select-sm" required>
                                <option value="activo">Activo</option>
                                <option value="inactivo">Inactivo</option>
                                <option value="bloqueado">Bloqueado</option>
                            </select>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Banco</label>
                            <input type="text" name="banco" id="prov_banco" class="form-select-sm form-control" placeholder="Ej: BAC Credomatic">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label small fw-semibold">Número de Cuenta Bancaria</label>
                            <input type="text" name="cuenta_bancaria" id="prov_cuenta_bancaria" class="form-select-sm form-control" placeholder="Ej: 360-123456-7">
                        </div>

                        <div class="col-md-12">
                            <label class="form-label small fw-semibold">Cuenta Contable (Detalle CXP)</label>
                            <select name="id_cuenta_contable" id="prov_id_cuenta" class="form-select form-select-sm">
                                <option value="">Por defecto (Cuentas por Pagar Proveedores)</option>
                                <?php foreach ($cuentasContables as $cta): ?>
                                    <option value="<?= $cta['id'] ?>">
                                        <?= htmlspecialchars($cta['codigo'] . ' - ' . $cta['nombre']) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm px-4">
                        <i class="fa-solid fa-floppy-disk me-1"></i>Guardar Proveedor
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- MODAL: EVALUACIÓN ISO/IEC 17025 §6.6 -->
<div class="modal fade" id="modalEvaluar" tabindex="-1" aria-labelledby="modalEvaluarLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content border-0 shadow">
            <form method="POST" action="/Cycsa/publico/compras/proveedores/evaluar">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" id="eval_prov_id" value="">
                
                <div class="modal-header bg-light">
                    <h5 class="modal-title fw-bold" id="modalEvaluarLabel">
                        <i class="fa-solid fa-certificate text-success me-2"></i>Evaluación ISO/IEC 17025 §6.6
                    </h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                
                <div class="modal-body p-4">
                    <div class="alert alert-info py-2 small mb-3">
                        <i class="fa-solid fa-info-circle me-1"></i>
                        La cláusula 6.6 exige evaluar, seleccionar, hacer seguimiento al desempeño y reevaluar a los proveedores externos que afecten las actividades de laboratorio.
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold text-muted">Proveedor:</label>
                        <div class="fw-bold fs-6 text-dark" id="eval_prov_nombre">—</div>
                        <div class="text-muted small" id="eval_prov_identificacion">—</div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Resultado de la Evaluación Técnica *</label>
                        <div class="form-check">
                            <input class="form-check-input" type="radio" name="evaluado" id="eval_si" value="1">
                            <label class="form-check-label text-success fw-semibold" for="eval_si">
                                <i class="fa-solid fa-check-circle me-1"></i>Aprobado / Calificado (Cumple requisitos)
                            </label>
                        </div>
                        <div class="form-check mt-2">
                            <input class="form-check-input" type="radio" name="evaluado" id="eval_no" value="0">
                            <label class="form-check-label text-danger fw-semibold" for="eval_no">
                                <i class="fa-solid fa-circle-xmark me-1"></i>No Aprobado / Pendiente de Calificación
                            </label>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label small fw-semibold">Fecha de Evaluación *</label>
                        <input type="date" name="fecha_evaluacion" id="eval_fecha" class="form-control form-control-sm" value="<?= date('Y-m-d') ?>" required>
                    </div>
                </div>
                
                <div class="modal-footer bg-light">
                    <button type="button" class="btn btn-secondary btn-sm" data-bs-dismiss="modal">Cerrar</button>
                    <button type="submit" class="btn btn-success btn-sm px-4">
                        <i class="fa-solid fa-stamp me-1"></i>Registrar Evaluación
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function abrirModalNuevoProveedor() {
    document.getElementById('modalProveedorLabel').innerHTML = '<i class="fa-solid fa-truck-field text-primary me-2"></i>Registrar Proveedor';
    document.getElementById('prov_id').value = '';
    document.getElementById('prov_tipo_id').value = 'RUC';
    document.getElementById('prov_identificacion').value = '';
    document.getElementById('prov_razon_social').value = '';
    document.getElementById('prov_contacto').value = '';
    document.getElementById('prov_telefono').value = '';
    document.getElementById('prov_email').value = '';
    document.getElementById('prov_categoria').value = 'insumos';
    document.getElementById('prov_plazo_pago').value = '30';
    document.getElementById('prov_estado').value = 'activo';
    document.getElementById('prov_banco').value = '';
    document.getElementById('prov_cuenta_bancaria').value = '';
    document.getElementById('prov_id_cuenta').value = '';
}

function abrirModalEditarProveedor(prov) {
    document.getElementById('modalProveedorLabel').innerHTML = '<i class="fa-solid fa-pen-to-square text-primary me-2"></i>Editar Proveedor #' + prov.id;
    document.getElementById('prov_id').value = prov.id;
    document.getElementById('prov_tipo_id').value = prov.tipo_id || 'RUC';
    document.getElementById('prov_identificacion').value = prov.identificacion || '';
    document.getElementById('prov_razon_social').value = prov.razon_social || '';
    document.getElementById('prov_contacto').value = prov.contacto || '';
    document.getElementById('prov_telefono').value = prov.telefono || '';
    document.getElementById('prov_email').value = prov.email || '';
    document.getElementById('prov_categoria').value = prov.categoria || 'insumos';
    document.getElementById('prov_plazo_pago').value = prov.plazo_pago_dias || '30';
    document.getElementById('prov_estado').value = prov.estado || 'activo';
    document.getElementById('prov_banco').value = prov.banco || '';
    document.getElementById('prov_cuenta_bancaria').value = prov.cuenta_bancaria || '';
    document.getElementById('prov_id_cuenta').value = prov.id_cuenta_contable || '';

    const modal = new bootstrap.Modal(document.getElementById('modalProveedor'));
    modal.show();
}

function abrirModalEvaluar(prov) {
    document.getElementById('eval_prov_id').value = prov.id;
    document.getElementById('eval_prov_nombre').textContent = prov.razon_social;
    document.getElementById('eval_prov_identificacion').textContent = (prov.tipo_id || 'RUC') + ': ' + prov.identificacion;

    if (prov.evaluado == 1) {
        document.getElementById('eval_si').checked = true;
    } else {
        document.getElementById('eval_no').checked = true;
    }

    if (prov.fecha_evaluacion) {
        document.getElementById('eval_fecha').value = prov.fecha_evaluacion;
    } else {
        document.getElementById('eval_fecha').value = new Date().toISOString().split('T')[0];
    }

    const modal = new bootstrap.Modal(document.getElementById('modalEvaluar'));
    modal.show();
}
</script>
