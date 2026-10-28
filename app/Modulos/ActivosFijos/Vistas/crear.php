<?php
/**
 * Vista de Formulario de Alta y Edición de Activos Fijos - CYCSA ERP (TAREA-051)
 */
$esEdicion = $esEdicion ?? false;
$activo = $activo ?? [];
?>
<div class="container-fluid px-0">
    <!-- Breadcrumb de Navegación -->
    <?php
    $migas = [
        ['label' => 'Activos Fijos', 'url' => '/Cycsa/publico/activos-fijos'],
        ['label' => !empty($esEdicion) ? 'Editar Activo' : 'Registrar Activo Fijo']
    ];
    require dirname(__DIR__, 3) . '/Views/parciales/breadcrumb.php';
    ?>

    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-2">
        <div>
            <h1 class="h3 mb-1 text-gray-800 fw-bold">
                <i class="fa-solid <?= $esEdicion ? 'fa-pen-to-square' : 'fa-plus-circle' ?> text-primary me-2"></i>
                <?= $esEdicion ? 'Editar Activo Fijo: ' . htmlspecialchars($activo['codigo']) : 'Registrar Nuevo Activo Fijo' ?>
            </h1>
            <p class="text-muted mb-0 small">
                Definición de parámetros técnicos, vida útil, valores de adquisición y cuentas contables para depreciación sistemática.
            </p>
        </div>
        <div class="d-flex gap-2">
            <a href="/Cycsa/publico/activos-fijos" class="btn btn-outline-secondary btn-sm px-3">
                <i class="fa-solid fa-arrow-left me-1"></i>Regresar al Catálogo
            </a>
        </div>
    </div>

    <!-- Barra de Subnavegación del Módulo -->
    <?php require __DIR__ . '/parciales/subnav_activos.php'; ?>


    <!-- Mensajes Flash -->
    <?php if (!empty($_SESSION['flash_error'])): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($_SESSION['flash_error']) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
        <?php unset($_SESSION['flash_error']); ?>
    <?php endif; ?>

    <form method="POST" action="/Cycsa/publico/activos-fijos/<?= $esEdicion ? 'actualizar' : 'guardar' ?>" id="formActivoFijo">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken) ?>">
        <?php if ($esEdicion): ?>
            <input type="hidden" name="id" value="<?= (int)$activo['id'] ?>">
        <?php endif; ?>

        <div class="row g-4">
            <!-- Columna Izquierda: Datos del Activo -->
            <div class="col-lg-8">
                <!-- Tarjeta 1: Información General -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-tag text-primary me-2"></i>1. Identificación y Clasificación
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Código del Activo *</label>
                                <input type="text" name="codigo" id="codigo" class="form-control form-control-sm font-monospace fw-bold text-uppercase" 
                                       value="<?= htmlspecialchars($activo['codigo'] ?? $sugerenciaCodigo) ?>" required>
                                <div class="form-text small">Identificador único (ej. AF-MAQ-001).</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Categoría *</label>
                                <select name="categoria" id="categoria" class="form-select form-select-sm fw-semibold" onchange="aplicarSugerenciaCategoria()" required>
                                    <?php $catActual = $activo['categoria'] ?? 'maquinaria_equipo'; ?>
                                    <option value="maquinaria_equipo" <?= $catActual === 'maquinaria_equipo' ? 'selected' : '' ?>>Maquinaria y Equipo (10 años)</option>
                                    <option value="edificios" <?= $catActual === 'edificios' ? 'selected' : '' ?>>Edificios (20 años)</option>
                                    <option value="vehiculos" <?= $catActual === 'vehiculos' ? 'selected' : '' ?>>Vehículos (5 años)</option>
                                    <option value="muebles_enseres" <?= $catActual === 'muebles_enseres' ? 'selected' : '' ?>>Muebles y Enseres (10 años)</option>
                                    <option value="equipo_computo" <?= $catActual === 'equipo_computo' ? 'selected' : '' ?>>Equipo de Cómputo (3 años)</option>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Estado *</label>
                                <select name="estado" id="estado" class="form-select form-select-sm" required>
                                    <?php $estActual = $activo['estado'] ?? 'activo'; ?>
                                    <option value="activo" <?= $estActual === 'activo' ? 'selected' : '' ?>>Activo</option>
                                    <option value="depreciado" <?= $estActual === 'depreciado' ? 'selected' : '' ?>>Depreciado</option>
                                    <option value="en_mantenimiento" <?= $estActual === 'en_mantenimiento' ? 'selected' : '' ?>>En Mantenimiento</option>
                                    <option value="de_baja" <?= $estActual === 'de_baja' ? 'selected' : '' ?>>De Baja</option>
                                </select>
                            </div>
                            <div class="col-12">
                                <label class="form-label small fw-bold">Descripción Completa del Activo *</label>
                                <textarea name="descripcion" id="descripcion" rows="2" class="form-control form-control-sm" placeholder="Ej: Prensa Hidráulica Digital Automática 2000 kN para rotura de cilindros..." required><?= htmlspecialchars($activo['descripcion'] ?? '') ?></textarea>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta 2: Parámetros Financieros y Vida Útil -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-calculator text-primary me-2"></i>2. Valores de Adquisición y Depreciación
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Fecha de Adquisición *</label>
                                <input type="date" name="fecha_adquisicion" id="fechaAdquisicion" class="form-control form-control-sm" 
                                       value="<?= htmlspecialchars($activo['fecha_adquisicion'] ?? date('Y-m-d')) ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Vida Útil (en Meses) *</label>
                                <input type="number" name="vida_util_meses" id="vidaUtilMeses" class="form-control form-control-sm" 
                                       min="1" max="600" value="<?= (int)($activo['vida_util_meses'] ?? 120) ?>" oninput="recalcularPrevisualizacion()" required>
                                <div class="form-text small" id="ayudaAnios">Equivale a 10 años.</div>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Valor de Adquisición (C$) *</label>
                                <input type="number" step="0.01" name="valor_adquisicion" id="valorAdquisicion" class="form-control form-control-sm fw-bold" 
                                       placeholder="0.00" value="<?= htmlspecialchars($activo['valor_adquisicion'] ?? '') ?>" oninput="recalcularPrevisualizacion()" required>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Valor Residual / Desecho (C$)</label>
                                <input type="number" step="0.01" name="valor_residual" id="valorResidual" class="form-control form-control-sm" 
                                       placeholder="0.00" value="<?= htmlspecialchars($activo['valor_residual'] ?? '0.00') ?>" oninput="recalcularPrevisualizacion()">
                                <div class="form-text small">Valor final no depreciable estimado al terminar la vida útil.</div>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label small fw-bold">Depreciación Acumulada Inicial (C$)</label>
                                <input type="number" step="0.01" name="depreciacion_acumulada" id="depreciacionAcumulada" class="form-control form-control-sm text-danger" 
                                       placeholder="0.00" value="<?= htmlspecialchars($activo['depreciacion_acumulada'] ?? '0.00') ?>" oninput="recalcularPrevisualizacion()">
                                <div class="form-text small">Monto ya amortizado previo al registro en el sistema (0 si es nuevo).</div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tarjeta 3: Puente Contable Personalizado (Opcional) -->
                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3">
                        <h5 class="fw-bold mb-0 text-dark">
                            <i class="fa-solid fa-receipt text-primary me-2"></i>3. Imputación Contable (Opcional - Mapeo Automático)
                        </h5>
                    </div>
                    <div class="card-body p-4">
                        <p class="text-muted small mb-3">
                            Si no se seleccionan cuentas específicas, el sistema asociará automáticamente las cuentas estándar según la categoría configurada en el Catálogo y config_cuentas.
                        </p>
                        <div class="row g-3">
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Cuenta de Activo</label>
                                <select name="cuenta_activo_id" class="form-select form-select-sm">
                                    <option value="">-- Automático por Categoría --</option>
                                    <?php foreach ($cuentasDetalle as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= (!empty($activo['cuenta_activo_id']) && $activo['cuenta_activo_id'] == $c['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['codigo'] . ' - ' . $c['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Cuenta Dep. Acumulada (Haber)</label>
                                <select name="cuenta_depreciacion_acum_id" class="form-select form-select-sm">
                                    <option value="">-- Automático por Categoría --</option>
                                    <?php foreach ($cuentasDetalle as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= (!empty($activo['cuenta_depreciacion_acum_id']) && $activo['cuenta_depreciacion_acum_id'] == $c['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['codigo'] . ' - ' . $c['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-4">
                                <label class="form-label small fw-bold">Cuenta Gasto Depreciación (Debe)</label>
                                <select name="cuenta_gasto_depreciacion_id" class="form-select form-select-sm">
                                    <option value="">-- Automático por Categoría --</option>
                                    <?php foreach ($cuentasDetalle as $c): ?>
                                        <option value="<?= $c['id'] ?>" <?= (!empty($activo['cuenta_gasto_depreciacion_id']) && $activo['cuenta_gasto_depreciacion_id'] == $c['id']) ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($c['codigo'] . ' - ' . $c['nombre']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Columna Derecha: Tarjeta de Resumen y Cálculo en Vivo -->
            <div class="col-lg-4">
                <div class="card border-0 shadow-sm sticky-top" style="top: 20px;">
                    <div class="card-header bg-primary text-white py-3">
                        <h6 class="fw-bold mb-0">
                            <i class="fa-solid fa-chart-pie me-2"></i>Cálculo Proyectado (Línea Recta)
                        </h6>
                    </div>
                    <div class="card-body p-4">
                        <div class="mb-3">
                            <span class="text-muted small text-uppercase fw-bold">Base Depreciable</span>
                            <h4 class="fw-bold text-dark mt-1" id="lblBaseDepreciable">C$ 0.00</h4>
                            <span class="text-muted small">(Valor Adquisición - Valor Residual)</span>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <span class="text-muted small text-uppercase fw-bold">Cuota Mensual Estimada</span>
                            <h3 class="fw-bold text-primary mt-1" id="lblCuotaMensual">C$ 0.00</h3>
                            <span class="text-muted small">Monto a depreciar cada mes</span>
                        </div>
                        <hr>
                        <div class="mb-3">
                            <span class="text-muted small text-uppercase fw-bold">Valor Inicial en Libros</span>
                            <h4 class="fw-bold text-success mt-1" id="lblValorLibros">C$ 0.00</h4>
                            <span class="text-muted small">(Valor Adquisición - Dep. Inicial)</span>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-primary py-2 fw-bold">
                                <i class="fa-solid fa-floppy-disk me-1"></i>
                                <?= $esEdicion ? 'Actualizar Activo Fijo' : 'Guardar Activo Fijo' ?>
                            </button>
                            <a href="/Cycsa/publico/activos-fijos" class="btn btn-outline-secondary btn-sm py-2">
                                Cancelar
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </form>
</div>

<script>
function aplicarSugerenciaCategoria() {
    const cat = document.getElementById('categoria').value;
    const mesesInput = document.getElementById('vidaUtilMeses');
    const ayudaAnios = document.getElementById('ayudaAnios');
    
    let meses = 120;
    if (cat === 'edificios') meses = 240;
    else if (cat === 'vehiculos') meses = 60;
    else if (cat === 'maquinaria_equipo') meses = 120;
    else if (cat === 'muebles_enseres') meses = 120;
    else if (cat === 'equipo_computo') meses = 36;

    mesesInput.value = meses;
    ayudaAnios.textContent = `Equivale a ${(meses / 12).toFixed(1)} años.`;
    recalcularPrevisualizacion();
}

function recalcularPrevisualizacion() {
    const valorAdq = parseFloat(document.getElementById('valorAdquisicion').value) || 0;
    const valorRes = parseFloat(document.getElementById('valorResidual').value) || 0;
    const depAcum = parseFloat(document.getElementById('depreciacionAcumulada').value) || 0;
    const meses = parseInt(document.getElementById('vidaUtilMeses').value) || 1;

    const baseDepreciable = Math.max(0, valorAdq - valorRes);
    const cuotaMensual = (meses > 0) ? (baseDepreciable / meses) : 0;
    const valorLibros = Math.max(0, valorAdq - depAcum);

    document.getElementById('lblBaseDepreciable').textContent = 'C$ ' + baseDepreciable.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('lblCuotaMensual').textContent = 'C$ ' + cuotaMensual.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
    document.getElementById('lblValorLibros').textContent = 'C$ ' + valorLibros.toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});

    document.getElementById('ayudaAnios').textContent = `Equivale a ${(meses / 12).toFixed(1)} años.`;
}

// Inicializar al cargar
document.addEventListener('DOMContentLoaded', function() {
    recalcularPrevisualizacion();
});
</script>
