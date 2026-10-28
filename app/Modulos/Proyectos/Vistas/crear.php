<?php
/**
 * Vista: app/Modulos/Proyectos/Vistas/crear.php
 * TAREA-053: Formulario de Registro / Edición de Proyectos y Órdenes de Obra.
 */
$esEdicion = !empty($proyecto['id']);
$actionUrl = $esEdicion ? '/Cycsa/publico/proyectos/actualizar' : '/Cycsa/publico/proyectos/guardar';
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb de Navegación -->
    <?php
    $migas = [
        ['label' => 'Proyectos', 'url' => '/Cycsa/publico/proyectos'],
        ['label' => 'Órdenes de Obra', 'url' => '/Cycsa/publico/proyectos'],
        ['label' => !empty($esEdicion) ? 'Editar Proyecto' : 'Nuevo Proyecto']
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <!-- Header y Acción -->
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid fa-helmet-safety text-primary me-2"></i><?= !empty($esEdicion) ? 'Editar Proyecto' : 'Nuevo Proyecto / Orden de Obra' ?>
            </h1>
            <p class="text-muted mb-0 small">Definición de obra civil, cliente asociado, fechas planificadas y presupuesto asignado.</p>
        </div>
        <div>
            <a href="/Cycsa/publico/proyectos" class="btn btn-outline-secondary btn-sm px-3 shadow-sm">
                <i class="fa-solid fa-arrow-left me-1"></i>Volver a Proyectos
            </a>
        </div>
    </div>

    <!-- Barra de Subnavegación del Módulo -->
    <?php require __DIR__ . '/parciales/subnav_proyectos.php'; ?>

    <!-- Mensajes Flash -->
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-4">
            <form method="POST" action="<?= $actionUrl ?>" class="needs-validation">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                <?php if ($esEdicion): ?>
                    <input type="hidden" name="id" value="<?= (int)$proyecto['id'] ?>">
                <?php endif; ?>

                <div class="row g-3">
                    <!-- Código del Proyecto -->
                    <div class="col-12 col-md-4">
                        <label for="codigo" class="form-label fw-semibold">Código del Proyecto <span class="text-danger">*</span></label>
                        <input type="text" class="form-control font-monospace" id="codigo" name="codigo" 
                               value="<?= htmlspecialchars($proyecto['codigo'] ?? $sugerenciaCodigo ?? '') ?>" 
                               required placeholder="Ej: PRY-2026-001" <?= $esEdicion ? 'readonly' : '' ?>>
                        <div class="form-text">Identificador mnemónico único para imputación contable.</div>
                    </div>

                    <!-- Nombre del Proyecto -->
                    <div class="col-12 col-md-8">
                        <label for="nombre" class="form-label fw-semibold">Nombre del Proyecto u Obra <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="nombre" name="nombre" 
                               value="<?= htmlspecialchars($proyecto['nombre'] ?? '') ?>" 
                               required placeholder="Ej: Construcción Puente Río Grande - Tramo 2">
                    </div>

                    <!-- Cliente Asignado -->
                    <div class="col-12 col-md-6">
                        <label for="cliente_id" class="form-label fw-semibold">Cliente / Contratante</label>
                        <select class="form-select" id="cliente_id" name="cliente_id">
                            <option value="">-- Sin Cliente Asignado (Obra Interna) --</option>
                            <?php foreach ($clientes as $cli): ?>
                                <option value="<?= $cli['id'] ?>" <?= (($proyecto['cliente_id'] ?? '') == $cli['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($cli['nombre_razon_social']) ?> (<?= htmlspecialchars($cli['identificacion'] ?? 'S/ID') ?>)
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Asociar al cliente para trazabilidad y facturación.</div>
                    </div>

                    <!-- Ubicación -->
                    <div class="col-12 col-md-6">
                        <label for="ubicacion" class="form-label fw-semibold">Ubicación / Tramo de Obra</label>
                        <input type="text" class="form-control" id="ubicacion" name="ubicacion" 
                               value="<?= htmlspecialchars($proyecto['ubicacion'] ?? '') ?>" 
                               placeholder="Ej: Km 42 Carretera Norte, Tipitapa">
                    </div>

                    <!-- Presupuesto Estimado -->
                    <div class="col-12 col-md-4">
                        <label for="presupuesto_estimado" class="form-label fw-semibold">Presupuesto Estimado (C$)</label>
                        <div class="input-group">
                            <span class="input-group-text bg-light">C$</span>
                            <input type="number" step="0.01" min="0" class="form-control text-end" id="presupuesto_estimado" name="presupuesto_estimado" 
                                   value="<?= htmlspecialchars($proyecto['presupuesto_estimado'] ?? '0.00') ?>" placeholder="0.00">
                        </div>
                        <div class="form-text">Límite financiero para el control de desviaciones.</div>
                    </div>

                    <!-- Fecha Inicio -->
                    <div class="col-12 col-md-4">
                        <label for="fecha_inicio" class="form-label fw-semibold">Fecha de Inicio <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" id="fecha_inicio" name="fecha_inicio" 
                               value="<?= htmlspecialchars($proyecto['fecha_inicio'] ?? date('Y-m-d')) ?>" required>
                    </div>

                    <!-- Fecha Fin Estimada -->
                    <div class="col-12 col-md-4">
                        <label for="fecha_fin" class="form-label fw-semibold">Fecha de Fin Estimada</label>
                        <input type="date" class="form-control" id="fecha_fin" name="fecha_fin" 
                               value="<?= htmlspecialchars($proyecto['fecha_fin'] ?? '') ?>">
                    </div>

                    <!-- Estado Inicial -->
                    <div class="col-12 col-md-6">
                        <label for="estado" class="form-label fw-semibold">Estado del Proyecto</label>
                        <select class="form-select" id="estado" name="estado">
                            <option value="planificado" <?= (($proyecto['estado'] ?? 'planificado') === 'planificado') ? 'selected' : '' ?>>Planificado</option>
                            <option value="en_ejecucion" <?= (($proyecto['estado'] ?? '') === 'en_ejecucion') ? 'selected' : '' ?>>En Ejecución (Activo)</option>
                            <option value="completado" <?= (($proyecto['estado'] ?? '') === 'completado') ? 'selected' : '' ?>>Completado</option>
                            <option value="suspendido" <?= (($proyecto['estado'] ?? '') === 'suspendido') ? 'selected' : '' ?>>Suspendido</option>
                        </select>
                    </div>

                    <!-- Cuenta Contable Obras en Proceso -->
                    <div class="col-12 col-md-6">
                        <label for="cuenta_obra_proceso_id" class="form-label fw-semibold">Cuenta Contable (Obras en Proceso)</label>
                        <select class="form-select" id="cuenta_obra_proceso_id" name="cuenta_obra_proceso_id">
                            <option value="">-- Automática por Defecto (10104 - Obras en Proceso) --</option>
                            <?php foreach ($cuentasObras as $c): ?>
                                <option value="<?= $c['id'] ?>" <?= (($proyecto['cuenta_obra_proceso_id'] ?? $cuentaDefault) == $c['id']) ? 'selected' : '' ?>>
                                    <?= htmlspecialchars($c['codigo']) ?> - <?= htmlspecialchars($c['nombre']) ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                        <div class="form-text">Cuenta de mayor para capitalización o costo de obra.</div>
                    </div>
                </div>

                <hr class="my-4">

                <div class="d-flex justify-content-end gap-2">
                    <a href="/Cycsa/publico/proyectos" class="btn btn-outline-secondary px-4">
                        Cancelar
                    </a>
                    <button type="submit" class="btn btn-primary px-4">
                        <i class="fa-solid fa-save me-1"></i><?= $esEdicion ? 'Actualizar Proyecto' : 'Guardar Proyecto' ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
