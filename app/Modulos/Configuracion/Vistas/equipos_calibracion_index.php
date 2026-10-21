<?php
/**
 * Vista: Módulo de Equipos, Calibración, Picnómetros (RT-FM-56) y Correcciones Parametrizables ISO/IEC 17025
 */
?>
<div class="container-fluid py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold mb-1">
                <i class="fas fa-balance-scale text-primary me-2"></i>
                Equipos, Calibraciones y Parámetros Normativos (ISO/IEC 17025)
            </h2>
            <p class="text-muted mb-0">
                Gestión centralizada de coeficientes de calibración (PRENSA-01, FACTOR-LD-C39, ESCL-01, Moldes, Balanzas),
                Calibración de Picnómetros (RT-FM-56) y validación de correcciones de fórmulas por Calidad.
            </p>
        </div>
        <div>
            <a href="/Cycsa/publico/plantillas" class="btn btn-outline-secondary btn-sm">
                <i class="fas fa-layer-group me-1"></i> Ir a Plantillas Dinámicas
            </a>
        </div>
    </div>

    <?php if (!empty($exito)): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($exito) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <?php if (!empty($error)): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle me-2"></i><?= htmlspecialchars($error) ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
        </div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-4" id="tabsEquiposCalibracion" role="tablist">
        <li class="nav-item" role="presentation">
            <button class="nav-link active fw-semibold" id="tab-equipos-btn" data-bs-toggle="tab" data-bs-target="#tab-equipos" type="button" role="tab">
                <i class="fas fa-cogs me-1"></i> Equipos y Constantes (<?= count($equipos ?? []) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="tab-picno-btn" data-bs-toggle="tab" data-bs-target="#tab-picno" type="button" role="tab">
                <i class="fas fa-flask me-1"></i> Calibración de Picnómetros RT-FM-56 (<?= count($picnometros ?? []) ?>)
            </button>
        </li>
        <li class="nav-item" role="presentation">
            <button class="nav-link fw-semibold" id="tab-corr-btn" data-bs-toggle="tab" data-bs-target="#tab-corr" type="button" role="tab">
                <i class="fas fa-clipboard-check me-1"></i> Correcciones Parametrizables Calidad (<?= count($correcciones ?? []) ?>)
            </button>
        </li>
    </ul>

    <div class="tab-content">
        <!-- PESTAÑA 1: EQUIPOS Y CONSTANTES -->
        <div class="tab-pane fade show active" id="tab-equipos" role="tabpanel">
            <div class="row g-3">
                <?php foreach ($equipos ?? [] as $eq): ?>
                    <div class="col-md-6">
                        <div class="card shadow-sm h-100 border-0">
                            <div class="card-header bg-light d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge bg-primary me-2"><?= htmlspecialchars($eq['codigo']) ?></span>
                                    <strong><?= htmlspecialchars($eq['nombre']) ?></strong>
                                </div>
                                <span class="badge bg-secondary"><?= htmlspecialchars($eq['categoria']) ?></span>
                            </div>
                            <div class="card-body">
                                <div class="small text-muted mb-2">
                                    <strong>Fuente / Norma:</strong> <?= htmlspecialchars($eq['norma'] ?? 'N/A') ?>
                                </div>
                                <?php if (!empty($eq['constantes'])): ?>
                                    <div class="table-responsive">
                                        <table class="table table-sm table-bordered align-middle mb-0">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Clave</th>
                                                    <th>Referencia (ref_equipo)</th>
                                                    <th>Valor Vigente</th>
                                                    <th>Unidad</th>
                                                    <th>Actualizar</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php foreach ($eq['constantes'] as $c): ?>
                                                    <tr>
                                                        <td><code><?= htmlspecialchars($c['clave']) ?></code></td>
                                                        <td class="small text-muted"><?= htmlspecialchars($c['ref_equipo_alias'] ?? '') ?></td>
                                                        <td class="fw-bold"><?= htmlspecialchars((string)($c['valor_numerico'] ?? 'JSON')) ?></td>
                                                        <td><?= htmlspecialchars($c['unidad'] ?? '-') ?></td>
                                                        <td>
                                                            <form method="POST" action="/Cycsa/publico/equipos-calibracion/guardar-constante" class="d-flex gap-1">
                                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                                                <input type="hidden" name="equipo_id" value="<?= (int)$eq['id'] ?>">
                                                                <input type="hidden" name="clave" value="<?= htmlspecialchars($c['clave']) ?>">
                                                                <input type="hidden" name="ref_equipo_alias" value="<?= htmlspecialchars($c['ref_equipo_alias'] ?? '') ?>">
                                                                <input type="number" step="any" name="valor_numerico" value="<?= htmlspecialchars((string)($c['valor_numerico'] ?? '')) ?>" class="form-control form-control-sm" style="width: 110px;">
                                                                <button type="submit" class="btn btn-sm btn-outline-primary" title="Guardar constante">
                                                                    <i class="fas fa-save"></i>
                                                                </button>
                                                            </form>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                <?php else: ?>
                                    <p class="text-muted small mb-0">Equipo registrado en catálogo (constantes en metadatos / certificado).</p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PESTAÑA 2: PICNÓMETROS RT-FM-56 -->
        <div class="tab-pane fade" id="tab-picno" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light d-flex justify-content-between align-items-center">
                    <h5 class="mb-0 fw-bold">Catálogo de Picnómetros Aforados (RT-FM-56 — ASTM D854)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Código Picnómetro</th>
                                    <th>Masa Vacío Promedio (g)</th>
                                    <th>Volumen Calibrado Promedio (cm³ / ml)</th>
                                    <th>Coef. Expansión Térmica (°C⁻¹)</th>
                                    <th>Fecha Evaluación</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($picnometros ?? [] as $pic): ?>
                                    <tr>
                                        <td><span class="badge bg-info text-dark"><?= htmlspecialchars($pic['identificacion']) ?></span></td>
                                        <td><?= number_format((float)$pic['masa_picnometro_vacio_g'], 2) ?> g</td>
                                        <td class="fw-bold"><?= number_format((float)$pic['volumen_calibrado_cm3'], 4) ?> ml</td>
                                        <td><?= htmlspecialchars((string)$pic['coef_expansion_termica']) ?></td>
                                        <td><?= htmlspecialchars($pic['fecha_calibracion'] ?? 'N/A') ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <!-- PESTAÑA 3: CORRECCIONES PARAMETRIZABLES -->
        <div class="tab-pane fade" id="tab-corr" role="tabpanel">
            <div class="card shadow-sm border-0">
                <div class="card-header bg-light">
                    <h5 class="mb-0 fw-bold">Control de Correcciones de Hojas de Cálculo ("Dudosas" / Confirmación Calidad)</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Formato</th>
                                    <th>Código</th>
                                    <th>Estado</th>
                                    <th>Original en Excel</th>
                                    <th>Fórmula Corregida</th>
                                    <th>Modo Activo</th>
                                    <th>Acción Calidad</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($correcciones ?? [] as $corr): ?>
                                    <tr class="<?= !empty($corr['es_dudosa']) ? 'table-warning' : '' ?>">
                                        <td><strong><?= htmlspecialchars($corr['codigo_formato']) ?></strong></td>
                                        <td><code><?= htmlspecialchars($corr['correccion_id']) ?></code></td>
                                        <td>
                                            <?php if (!empty($corr['es_dudosa'])): ?>
                                                <span class="badge bg-warning text-dark">Dudosa (Validar Calidad)</span>
                                            <?php else: ?>
                                                <span class="badge bg-success">Fija Validada</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="small"><?= htmlspecialchars($corr['original_excel'] ?? '') ?></td>
                                        <td class="small"><?= htmlspecialchars($corr['corregido'] ?? '') ?></td>
                                        <td>
                                            <?php if (!empty($corr['usar_corregido'])): ?>
                                                <span class="badge bg-primary">Fórmula Corregida</span>
                                            <?php else: ?>
                                                <span class="badge bg-secondary">Original Excel</span>
                                            <?php endif; ?>
                                        </td>
                                        <td>
                                            <form method="POST" action="/Cycsa/publico/equipos-calibracion/actualizar-correccion" class="d-inline">
                                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
                                                <input type="hidden" name="codigo_formato" value="<?= htmlspecialchars($corr['codigo_formato']) ?>">
                                                <input type="hidden" name="correccion_id" value="<?= htmlspecialchars($corr['correccion_id']) ?>">
                                                <input type="hidden" name="usar_corregido" value="<?= !empty($corr['usar_corregido']) ? '0' : '1' ?>">
                                                <input type="hidden" name="confirmado_calidad" value="1">
                                                <button type="submit" class="btn btn-sm btn-outline-dark">
                                                    Cambiar a <?= !empty($corr['usar_corregido']) ? 'Original' : 'Corregido' ?>
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
