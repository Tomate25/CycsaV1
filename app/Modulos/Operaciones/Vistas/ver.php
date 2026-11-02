<?php
// Dedicated full-page view for a single Operation (O/S)
$rolSesion = (int)($_SESSION['usuario_rol'] ?? 0);
$esSupervisor = in_array($rolSesion, [1, 2, 3]) || tienePermiso('operaciones', 'aprobar') || tienePermiso('control_calidad', 'aprobar');

$cliInfNom = $o['cliente_informe_nombre'] ?? ($o['cliente_nombre'] ?? 'Cliente General');
$cliFactNom = $o['cliente_factura_nombre'] ?? ($o['cliente_nombre'] ?? 'Cliente General');
$condComAct = strtolower(trim($o['condicion_comercial_activa'] ?? $o['condicion_comercial'] ?? $o['condicion_pago'] ?? $o['forma_pago'] ?? 'contado'));
$esCreditoFila = ($condComAct === 'credito');

$totalItemsOS = count($o['items'] ?? []);
$matricesLlenadas = 0;
foreach ($o['items'] as $it) {
    if (!empty($it['resultados_json']) && $it['resultados_json'] !== '[]') {
        $matricesLlenadas++;
    }
}
$matrizCompleta = ($totalItemsOS > 0 && $matricesLlenadas === $totalItemsOS);
$tieneTecnico = !empty($o['tecnico_muestreo']);
$tieneHojaServicio = !empty($o['hoja_solicitud']);
$requiereMuestreo = !empty($o['requiere_muestreo']);
$tieneMuestrasAceptadas = !empty($o['muestras_aceptadas_lab']) && $o['muestras_aceptadas_lab'] > 0;
$esSoloCompactacion = esOrdenSoloCompactacion($o['items']);
?>
<style>
    :root {
        --cycsa-azul: #103487;
        --cycsa-azul-hover: #0c2766;
    }
    .lims-ver-container {
        max-width: 1300px;
        margin: 0 auto;
        padding: 20px;
    }
    .lims-step-chip {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12.5px;
        font-weight: 700;
        white-space: nowrap;
        border: 1px solid transparent;
        user-select: none;
    }
    .step-ok { background-color: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .step-warn { background-color: #fffbeb; color: #b45309; border-color: #fde68a; }
    .step-info { background-color: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
    .step-muted { background-color: #f8fafc; color: #64748b; border-color: #e2e8f0; }
    .step-ready { background-color: #f0fdf4; color: #15803d; border-color: #86efac; box-shadow: 0 0 0 1px #86efac; }

    .modal-premium { display: none; position: fixed; z-index: 10000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .modal-premium-content { background-color: #fff; margin: 4% auto; padding: 30px; border: 1px solid #cbd5e1; width: 45%; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
</style>

<div class="lims-ver-container">

    <?php if (!empty($exito)): ?>
        <div class="alert alert-success d-flex align-items-center gap-2 mb-3" style="border-radius: 8px; font-size: 13.5px; padding: 12px 18px; background: #ecfdf5; border: 1px solid #a7f3d0; color: #065f46;">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($exito) ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-danger d-flex align-items-center gap-2 mb-3" style="border-radius: 8px; font-size: 13.5px; padding: 12px 18px; background: #fef2f2; border: 1px solid #fecaca; color: #991b1b;">
            <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>

    <!-- BARRA SUPERIOR DE RETORNO Y TÍTULO -->
    <div style="background: white; border: 1px solid #cbd5e1; border-radius: 10px; padding: 18px 24px; margin-bottom: 22px; box-shadow: 0 2px 6px rgba(0,0,0,0.03); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 16px;">
        <div>
            <a href="/Cycsa/publico/operaciones" style="color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-arrow-left"></i> Volver a la Lista de Operaciones
            </a>
            <div style="display: flex; align-items: center; gap: 12px; margin-top: 6px; flex-wrap: wrap;">
                <h1 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800; display: flex; align-items: center; gap: 10px;">
                    <i class="fa-solid fa-folder-open" style="color: var(--cycsa-azul);"></i> Operación: <span style="font-family: monospace; color: var(--cycsa-azul);"><?= htmlspecialchars($o['codigo_os']) ?></span>
                </h1>
                <span class="badge bg-primary-subtle text-primary border border-primary-subtle" style="font-size: 12px; font-weight: 700; padding: 5px 10px; border-radius: 20px;">
                    <?= htmlspecialchars($o['estado'] ?? 'En Proceso') ?>
                </span>
            </div>
            <div style="font-size: 13px; color: #64748b; margin-top: 4px;">
                Proyecto: <strong style="color: #1e293b;"><?= htmlspecialchars($o['nombre_proyecto'] ?? 'Sin Proyecto') ?></strong> &bull; Cotización: <strong><?= htmlspecialchars($o['cot_codigo'] ?? '') ?></strong> &bull; Emisión: <?= !empty($o['fecha_emision']) ? date('d/m/Y', strtotime($o['fecha_emision'])) : 'N/A' ?>
            </div>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <?php if ($tieneHojaServicio): ?>
                <a href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= $o['id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm" style="font-weight: 600; padding: 7px 14px; border-radius: 6px;">
                    <i class="fa-solid fa-file-pdf text-info me-1"></i> Ver RT-FM-13 (PDF)
                </a>
            <?php else: ?>
                <a href="/Cycsa/publico/ordenes-servicio?id_os=<?= $o['id'] ?>" class="btn btn-warning btn-sm text-dark fw-bold" style="padding: 7px 14px; border-radius: 6px;">
                    <i class="fa-solid fa-file-signature me-1"></i> Registrar RT-FM-13
                </a>
            <?php endif; ?>

            <?php if ($esSupervisor && !in_array($o['estado'], ['Estado 7: Revision Resultados', 'Finalizado', 'Archivado', 'Cerrado'])): ?>
                <button type="button" onclick="abrirModalRevisionResultados(<?= (int)$o['id'] ?>, '<?= htmlspecialchars(addslashes($o['codigo_os'])) ?>')" class="btn btn-primary btn-sm fw-bold" style="padding: 7px 16px; border-radius: 6px; background-color: #0284c7; border-color: #0369a1;">
                    <i class="fa-solid fa-clipboard-check me-1"></i> Aprobar Revisión Técnica
                </button>
            <?php endif; ?>

            <?php if (!empty($o['puede_cerrar'])): ?>
                <button type="button" onclick="abrirModalCerrarOS(<?= $o['id'] ?>, '<?= htmlspecialchars(addslashes($o['codigo_os'])) ?>')" class="btn btn-success btn-sm fw-bold" style="padding: 7px 16px; border-radius: 6px;">
                    <i class="fa-solid fa-circle-check me-1"></i> Aprobar y Archivar Orden
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- TRACKER DE PROGRESO EN 4 PASOS -->
    <div style="background: white; border: 1px solid #cbd5e1; border-radius: 10px; padding: 14px 20px; margin-bottom: 22px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 12px;">
        <div style="font-size: 13px; font-weight: 700; color: #334155;">
            <i class="fa-solid fa-route text-primary me-1"></i> Ciclo de Vida LIMS:
        </div>
        <div style="display: flex; gap: 8px; flex-wrap: wrap;">
            <!-- PASO 1: RT-FM-13 -->
            <?php if ($tieneHojaServicio): ?>
                <span class="lims-step-chip step-ok"><i class="fa-solid fa-circle-check"></i> 1 RT-FM-13 Registrada</span>
            <?php else: ?>
                <span class="lims-step-chip step-warn"><i class="fa-solid fa-clock"></i> 1 RT-FM-13 Pendiente</span>
            <?php endif; ?>

            <!-- PASO 2: MATRIZ -->
            <?php if ($matrizCompleta): ?>
                <span class="lims-step-chip step-ok"><i class="fa-solid fa-circle-check"></i> 2 Matrices Completas (<?= $matricesLlenadas ?>/<?= $totalItemsOS ?>)</span>
            <?php elseif ($matricesLlenadas > 0): ?>
                <span class="lims-step-chip step-warn"><i class="fa-solid fa-pen-ruler"></i> 2 Matriz en Captura (<?= $matricesLlenadas ?>/<?= $totalItemsOS ?>)</span>
            <?php else: ?>
                <span class="lims-step-chip step-muted"><i class="fa-solid fa-hourglass-start"></i> 2 Matriz Pendiente</span>
            <?php endif; ?>

            <!-- PASO 3: APROBACIÓN -->
            <?php if ($o['tecnico_100']): ?>
                <span class="lims-step-chip step-ok"><i class="fa-solid fa-circle-check"></i> 3 Supervisión 100% Aprobada</span>
            <?php elseif ($o['ensayos_con_resultados'] > 0): ?>
                <span class="lims-step-chip step-info"><i class="fa-solid fa-magnifying-glass"></i> 3 En Revisión Técnica (<?= $o['ensayos_aprobados'] ?>/<?= $o['total_ensayos'] ?>)</span>
            <?php else: ?>
                <span class="lims-step-chip step-muted"><i class="fa-solid fa-hourglass"></i> 3 Aprobación Pendiente</span>
            <?php endif; ?>

            <!-- PASO 4: CIERRE -->
            <?php if (in_array($o['estado'], ['Finalizado', 'Archivado', 'Cerrado'])): ?>
                <span class="lims-step-chip step-ok"><i class="fa-solid fa-box-archive"></i> 4 Orden Archivada</span>
            <?php elseif (!empty($o['puede_cerrar'])): ?>
                <span class="lims-step-chip step-ready"><i class="fa-solid fa-flag-checkered"></i> 4 Lista para Cierre</span>
            <?php else: ?>
                <span class="lims-step-chip step-muted"><i class="fa-solid fa-lock"></i> 4 Cierre Bloqueado</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- TABS DE NAVEGACIÓN PRINCIPALES (RESUMEN / ENSAYOS / CIERRE) -->
    <div style="background: #f1f5f9; padding: 5px; border-radius: 10px; display: flex; gap: 6px; margin-bottom: 22px;">
        <button type="button" class="btn btn-sm btn-tab-full active" onclick="cambiarTabFull(this, 'tab-full-resumen')" style="flex: 1; padding: 10px 16px; font-size: 13.5px; font-weight: 700; border-radius: 8px; background: var(--cycsa-azul, #103487); color: white; border: none;">
            <i class="fa-solid fa-circle-info me-1"></i> Resumen General
        </button>
        <button type="button" class="btn btn-sm btn-tab-full" onclick="cambiarTabFull(this, 'tab-full-ensayos')" style="flex: 1; padding: 10px 16px; font-size: 13.5px; font-weight: 700; border-radius: 8px; background: transparent; color: #475569; border: none;">
            <i class="fa-solid fa-flask text-info me-1"></i> Ensayos Técnicos (<?= $totalItemsOS ?>)
        </button>
        <button type="button" class="btn btn-sm btn-tab-full" onclick="cambiarTabFull(this, 'tab-full-informes')" style="flex: 1; padding: 10px 16px; font-size: 13.5px; font-weight: 700; border-radius: 8px; background: transparent; color: #475569; border: none;">
            <i class="fa-solid fa-file-shield text-primary me-1"></i> Informes y Versiones (<?= count($informes_unificados ?? []) ?>)
        </button>
        <button type="button" class="btn btn-sm btn-tab-full" onclick="cambiarTabFull(this, 'tab-full-cierre')" style="flex: 1; padding: 10px 16px; font-size: 13.5px; font-weight: 700; border-radius: 8px; background: transparent; color: #475569; border: none;">
            <i class="fa-solid fa-shield-halved <?= $o['puede_cerrar'] ? 'text-success' : 'text-warning' ?> me-1"></i> Cierre LIMS
        </button>
    </div>

    <!-- CONTENEDOR DE PESTAÑAS -->
    <div class="lims-tab-content-wrapper">
        <!-- PESTAÑA 1: RESUMEN GENERAL -->
        <div id="tab-full-resumen" class="tab-pane-full" style="display: block;">
            <div style="background: white; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 22px; margin-bottom: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <h3 style="margin: 0 0 16px 0; font-size: 16px; font-weight: 700; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                    <i class="fa-solid fa-address-card text-primary me-2"></i> Identidad y Aspectos Comerciales
                </h3>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 18px; font-size: 13.5px;">
                    <div>
                        <span style="font-size: 11.5px; text-transform: uppercase; font-weight: 700; color: #64748b;">Cliente para Informe:</span>
                        <div style="font-weight: 700; color: #0f172a; margin-top: 4px; font-size: 14.5px;">
                            <i class="fa-solid fa-file-contract text-primary me-1"></i> <?= htmlspecialchars($cliInfNom, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <div>
                        <span style="font-size: 11.5px; text-transform: uppercase; font-weight: 700; color: #64748b;">Cliente a Facturar:</span>
                        <div style="font-weight: 600; color: #334155; margin-top: 4px; font-size: 14px;">
                            <i class="fa-solid fa-receipt text-secondary me-1"></i> <?= htmlspecialchars($cliFactNom, ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <div>
                        <span style="font-size: 11.5px; text-transform: uppercase; font-weight: 700; color: #64748b;">Proyecto y Cotización:</span>
                        <div style="color: #334155; margin-top: 4px;">
                            <strong><?= htmlspecialchars($o['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?></strong> &bull; <?= htmlspecialchars($o['cot_codigo'], ENT_QUOTES, 'UTF-8') ?>
                        </div>
                    </div>
                    <div>
                        <span style="font-size: 11.5px; text-transform: uppercase; font-weight: 700; color: #64748b;">Condición Comercial:</span>
                        <div style="margin-top: 4px; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span class="badge" style="background: <?= $esCreditoFila ? '#eff6ff' : '#f8fafc' ?>; color: <?= $esCreditoFila ? '#1d4ed8' : '#475569' ?>; border: 1px solid <?= $esCreditoFila ? '#bfdbfe' : '#e2e8f0' ?>; font-size: 12px; padding: 4px 10px;">
                                <i class="fa-solid <?= $esCreditoFila ? 'fa-credit-card' : 'fa-money-bill' ?>"></i> <?= ucfirst($condComAct) ?>
                            </span>
                            <?php if (!$esCreditoFila && !$o['comercial_100'] && (float)($o['saldo_os'] ?? 0) > 0): ?>
                                <span class="badge bg-warning-subtle text-dark border border-warning" style="font-size: 11.5px; padding: 4px 10px;">
                                    <i class="fa-solid fa-triangle-exclamation text-warning me-1"></i> Saldo pendiente: C$ <?= number_format((float)$o['saldo_os'], 2) ?>
                                </span>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>

            <div style="background: white; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 22px; margin-bottom: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">
                        <i class="fa-solid fa-truck-ramp-box text-primary me-2"></i> Logística y Custodia de Muestras
                    </h3>
                    <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 12px; padding: 4px 10px;">
                        <?= $requiereMuestreo ? 'Muestreo en Campo' : 'Recepción Lab Central' ?>
                    </span>
                </div>
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 14px; font-size: 13.5px;">
                    <div>
                        <span style="font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase;">Modalidad:</span>
                        <?php if ($esSoloCompactacion): ?>
                            <span class="badge bg-info-subtle text-info border border-info-subtle ms-1">Ensayo In Situ (Sin custodia física)</span>
                        <?php elseif ($requiereMuestreo): ?>
                            <span class="badge bg-success-subtle text-success border border-success-subtle ms-1">Muestreo en Campo por Técnico CYCSA</span>
                        <?php else: ?>
                            <span class="badge bg-primary-subtle text-primary border border-primary-subtle ms-1">Muestra Entregada por Cliente</span>
                        <?php endif; ?>
                    </div>
                    <div style="font-size: 13px; color: #475569; font-weight: 600;">
                        <?php if ($esSoloCompactacion): ?>
                            Responsable: <strong class="text-info">Operaciones / Terreno</strong>
                        <?php elseif ($requiereMuestreo): ?>
                            Técnico Asignado: <strong class="text-primary"><?= $tieneTecnico ? htmlspecialchars($o['tecnico_muestreo'], ENT_QUOTES, 'UTF-8') : 'Sin Asignar' ?></strong>
                            <?php if (!$tieneTecnico): ?>
                                <button type="button" class="btn btn-primary btn-sm ms-2" onclick="abrirModalMuestreo(<?= $o['id'] ?>, '<?= htmlspecialchars(addslashes($o['codigo_os'])) ?>')" style="padding: 2px 10px; font-size: 11.5px;">Asignar</button>
                            <?php endif; ?>
                        <?php else: ?>
                            Recepción: <strong class="text-success">Ventanilla Central (MS)</strong>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- PESTAÑA 2: ENSAYOS TÉCNICOS -->
        <div id="tab-full-ensayos" class="tab-pane-full" style="display: none;">
            <div class="lims-ensayos-cards-list d-flex flex-column gap-3 mb-4">
                <?php foreach ($o['items'] as $it): 
                    $esCompactacion = esItemCompactacion($it);
                ?>
                    <div class="card shadow-sm border" style="border-color: #cbd5e1; border-radius: 8px; overflow: hidden; background: white;">
                        <div class="card-header bg-white py-3 px-3 d-flex justify-content-between align-items-center flex-wrap gap-2 border-bottom">
                            <div>
                                <div class="fw-bold text-dark d-flex align-items-center gap-2" style="font-size: 15px;">
                                    <i class="fa-solid fa-flask" style="color: var(--cycsa-azul);"></i>
                                    <?= htmlspecialchars($it['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?>
                                </div>
                                <div class="text-secondary mt-1" style="font-size: 12.5px; font-family: monospace;">
                                    Norma: <strong><?= htmlspecialchars($it['norma_astm'] ?? 'N/A') ?></strong> &bull; Formato: <?= htmlspecialchars($it['formato_nombre'] ?? 'Matriz Técnica') ?> &bull; Cantidad: <strong><?= (int)($it['cantidad'] ?? 1) ?></strong>
                                </div>
                            </div>
                        </div>
                        <div class="card-body py-2 px-3 bg-light">
                            <?php include __DIR__ . '/parciales/acciones_ensayo.php'; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PESTAÑA 3: CIERRE LIMS -->
        <div id="tab-full-cierre" class="tab-pane-full" style="display: none;">
            <div style="background: white; border: 1.5px solid <?= $o['puede_cerrar'] ? '#86efac' : '#cbd5e1' ?>; border-radius: 10px; padding: 24px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="font-family: 'Outfit'; font-size: 16px; font-weight: 700; color: #0f172a; display: flex; align-items: center; gap: 10px; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px;">
                    <i class="fa-solid fa-shield-halved" style="color: <?= $o['puede_cerrar'] ? '#16a34a' : '#64748b' ?>; font-size: 18px;"></i>
                    Doble Validación de Cierre LIMS y Archivo Histórico
                </div>
                <div style="font-size: 13.5px; color: #475569; display: flex; gap: 20px; flex-wrap: wrap; margin-bottom: 14px;">
                    <span>
                        <i class="fa-solid <?= $o['tecnico_100'] ? 'fa-circle-check text-success' : 'fa-clock text-warning' ?>"></i>
                        <strong>100% Técnico:</strong> <?= $o['tecnico_100'] ? 'Aprobado formalmente por supervisión' : ($o['ensayos_aprobados'] . '/' . $o['total_ensayos'] . ' ensayos aprobados') ?>
                    </span>
                    <span>
                        <i class="fa-solid <?= $o['comercial_100'] ? 'fa-circle-check text-success' : 'fa-clock text-warning' ?>"></i>
                        <strong>100% Comercial (<?= ucfirst(htmlspecialchars($condComAct)) ?>):</strong> 
                        <?php if ($esCreditoFila): ?>
                            <?= $o['cxc'] !== null ? 'Factura/CxC documentada' : 'Pendiente generar CxC' ?>
                        <?php else: ?>
                            <?= $o['comercial_100'] ? 'Factura cancelada (C$ 0.00)' : ('Saldo pendiente C$ ' . number_format((float)($o['saldo_os'] ?? 0), 2)) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if (!$o['puede_cerrar']): ?>
                    <div style="font-size: 12.5px; color: #b45309; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 16px; margin-bottom: 18px;">
                        <i class="fa-solid fa-circle-exclamation me-1"></i>
                        <?= !$o['tecnico_100'] ? 'Faltan ensayos por aprobar formalmente por supervisión técnica (' . $o['ensayos_aprobados'] . '/' . $o['total_ensayos'] . ' aprobados).' : ($esCreditoFila ? 'Falta generar la cuenta por cobrar en el sistema.' : 'Saldo comercial pendiente de cobro (condición Contado: C$ ' . number_format((float)($o['saldo_os'] ?? 0), 2) . ').') ?>
                    </div>
                <?php endif; ?>
                <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                    <?php if (in_array($o['estado'], ['Finalizado', 'Archivado', 'Cerrado'])): ?>
                        <span class="badge bg-secondary" style="font-size: 13px; padding: 10px 18px; border-radius: 20px;">
                            <i class="fa-solid fa-box-archive me-1"></i> Orden Archivada en Histórico
                        </span>
                    <?php elseif (!empty($o['puede_cerrar'])): ?>
                        <button type="button" 
                                onclick="abrirModalCerrarOS(<?= $o['id'] ?>, '<?= htmlspecialchars(addslashes($o['codigo_os'])) ?>')" 
                                class="btn btn-success" 
                                style="height: 40px; font-size: 13.5px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px; background-color: #16a34a; border-color: #15803d;">
                            <i class="fa-solid fa-circle-check"></i> Aprobar y Archivar Orden
                        </button>
                    <?php else: ?>
                        <button type="button" 
                                disabled 
                                class="btn btn-outline-secondary" 
                                style="height: 40px; font-size: 13px; font-weight: 600; border-radius: 6px; cursor: not-allowed; opacity: 0.7;" 
                                title="Cierre bloqueado: Requiere 100% de ensayos aprobados y cobro comercial liquidado.">
                            <i class="fa-solid fa-lock"></i> Cierre LIMS Bloqueado
                        </button>
                        <?php if (!$o['tecnico_100']): ?>
                            <button type="button" 
                                    onclick="cambiarTabFull(document.querySelectorAll('.btn-tab-full')[1], 'tab-full-ensayos')" 
                                    class="btn btn-outline-primary" 
                                    style="height: 40px; font-size: 13px; font-weight: 600; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                                <i class="fa-solid fa-flask me-1"></i> Ir a Ensayos para Aprobar (<?= $o['ensayos_aprobados'] ?>/<?= $o['total_ensayos'] ?>)
                            </button>
                        <?php endif; ?>
                    <?php endif; ?>
                    <?php if ($esSupervisor && !in_array($o['estado'], ['Estado 7: Revision Resultados', 'Finalizado', 'Archivado', 'Cerrado'])): ?>
                        <button type="button" 
                                onclick="abrirModalRevisionResultados(<?= (int)$o['id'] ?>, '<?= htmlspecialchars(addslashes($o['codigo_os'])) ?>')" 
                                class="btn btn-outline-info" 
                                style="height: 40px; font-size: 13px; font-weight: 600; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; color: #0284c7; border-color: #0284c7;">
                            <i class="fa-solid fa-clipboard-check me-1"></i> Aprobar Revisión Técnica O/S
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- PESTAÑA 4: INFORMES Y VERSIONES (LAB P2) -->
        <div id="tab-full-informes" class="tab-pane-full" style="display: none;">
            <!-- SUBSECCIÓN 1: INFORMES UNIFICADOS -->
            <div style="background: white; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 22px; margin-bottom: 20px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; flex-wrap: wrap; gap: 10px;">
                    <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #1e293b;">
                        <i class="fa-solid fa-file-shield text-primary me-2"></i> Informes de Ensayo Oficiales (Fachada Unificada)
                    </h3>
                    <span class="badge bg-primary-subtle text-primary border" style="font-size: 12px; padding: 4px 10px;">
                        <?= count($informes_unificados ?? []) ?> informe(s) emitido(s)
                    </span>
                </div>

                <?php if (empty($informes_unificados)): ?>
                    <div style="text-align: center; padding: 32px 20px; background: #f8fafc; border-radius: 8px; border: 1px dashed #cbd5e1;">
                        <i class="fa-solid fa-file-circle-question text-muted" style="font-size: 32px; margin-bottom: 10px; display: block;"></i>
                        <span style="font-size: 13.5px; color: #64748b; font-weight: 600;">No se han emitido informes formales aún para esta orden de servicio.</span>
                        <div style="font-size: 12px; color: #94a3b8; margin-top: 4px;">Los informes parciales y consolidados generados desde Laboratorio aparecerán automáticamente aquí con trazabilidad SHA-256.</div>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover align-middle" style="margin-bottom: 0; font-size: 13px;">
                            <thead class="table-light" style="font-size: 12px; text-transform: uppercase; color: #64748b;">
                                <tr>
                                    <th>Código Informe</th>
                                    <th>Tipo</th>
                                    <th>Versión</th>
                                    <th>Estado</th>
                                    <th>Fecha Emisión</th>
                                    <th>Firma SHA-256</th>
                                    <th class="text-end">Documento</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($informes_unificados as $inf): ?>
                                    <tr>
                                        <td class="fw-bold font-monospace text-primary">
                                            <?= htmlspecialchars($inf['numero'] ?? 'S/N') ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-light text-dark border">
                                                <?= strtoupper(htmlspecialchars($inf['tipo'] ?? 'general')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info border">
                                                v<?= (int)($inf['version'] ?? 1) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?php if (($inf['estado'] ?? '') === 'aprobado'): ?>
                                                <span class="badge bg-success-subtle text-success border border-success">Aprobado</span>
                                            <?php elseif (($inf['estado'] ?? '') === 'anulado'): ?>
                                                <span class="badge bg-danger-subtle text-danger border border-danger">Anulado</span>
                                            <?php else: ?>
                                                <span class="badge bg-warning-subtle text-warning border border-warning"><?= htmlspecialchars(ucfirst($inf['estado'] ?? 'Pendiente')) ?></span>
                                            <?php endif; ?>
                                        </td>
                                        <td style="color: #64748b; font-size: 12px;">
                                            <?= htmlspecialchars($inf['fecha_aprobacion'] ?? ($inf['fecha_emision'] ?? '—')) ?>
                                        </td>
                                        <td>
                                            <?php if (!empty($inf['sha256'])): ?>
                                                <span class="font-monospace text-muted" style="font-size: 11px;" title="<?= htmlspecialchars($inf['sha256']) ?>">
                                                    <i class="fa-solid fa-fingerprint text-secondary me-1"></i><?= substr($inf['sha256'], 0, 10) ?>…
                                                </span>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 11px;">Pendiente</span>
                                            <?php endif; ?>
                                        </td>
                                        <td class="text-end">
                                            <?php if (!empty($inf['pdf'])): ?>
                                                <a href="/Cycsa/publico/<?= htmlspecialchars($inf['pdf']) ?>" target="_blank" class="btn btn-sm btn-outline-primary" style="padding: 2px 8px; font-size: 12px;">
                                                    <i class="fa-solid fa-file-pdf text-danger me-1"></i> PDF
                                                </a>
                                            <?php else: ?>
                                                <span class="text-muted" style="font-size: 11px;">No adjunto</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>

            <!-- SUBSECCIÓN 2: CONSOLIDADO MULTI-MUESTRA Y HISTORIAL VERSIONES -->
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(360px, 1fr)); gap: 20px;">
                <!-- CONSOLIDADO MULTI-MUESTRA -->
                <div style="background: white; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 22px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                    <h3 style="margin: 0 0 14px 0; font-size: 15px; font-weight: 700; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <i class="fa-solid fa-layer-group text-primary me-2"></i> Resumen Consolidado Multi-Muestra
                    </h3>
                    <div style="font-size: 13px; color: #475569; margin-bottom: 12px;">
                        Total Muestras Conectadas: <strong><?= (int)($consolidado_lab['total_muestras'] ?? 0) ?></strong> &bull; Versión Consolidada: <strong>v<?= (int)($consolidado_lab['version_consolidado'] ?? 1) ?></strong>
                    </div>
                    <?php if (empty($consolidado_lab['muestras'])): ?>
                        <div style="padding: 16px; background: #f8fafc; border-radius: 6px; font-size: 12.5px; color: #64748b; text-align: center;">
                            No hay especímenes de rotura vinculados a esta orden todavía.
                        </div>
                    <?php else: ?>
                        <div style="max-height: 260px; overflow-y: auto;">
                            <?php foreach ($consolidado_lab['muestras'] as $m): ?>
                                <div style="padding: 10px 12px; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 8px; font-size: 12.5px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span class="fw-bold text-dark font-monospace"><?= htmlspecialchars($m['codigo_muestra'] ?? 'Muestra') ?></span>
                                        <span class="badge bg-secondary-subtle text-secondary border"><?= count($m['especimenes'] ?? []) ?> especímenes</span>
                                    </div>
                                    <div style="color: #64748b; font-size: 11.5px; margin-top: 3px;">
                                        <?= htmlspecialchars($m['observaciones'] ?? 'Sin observaciones') ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>

                <!-- HISTORIAL VERSIONES CRIPTOGRÁFICO -->
                <div style="background: white; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 22px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                    <h3 style="margin: 0 0 14px 0; font-size: 15px; font-weight: 700; color: #1e293b; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                        <i class="fa-solid fa-clock-rotate-left text-primary me-2"></i> Historial de Versiones e Integridad
                    </h3>
                    <?php if (empty($historial_versiones_lab)): ?>
                        <div style="padding: 16px; background: #f8fafc; border-radius: 6px; font-size: 12.5px; color: #64748b; text-align: center;">
                            Sin historial de reemisiones registrado. Versión inicial activa.
                        </div>
                    <?php else: ?>
                        <div style="max-height: 260px; overflow-y: auto;">
                            <?php foreach ($historial_versiones_lab as $ver): ?>
                                <div style="padding: 10px 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; margin-bottom: 8px; font-size: 12.5px;">
                                    <div style="display: flex; justify-content: space-between; align-items: center;">
                                        <span class="fw-bold text-success">Versión <?= (int)($ver['version'] ?? 1) ?></span>
                                        <span class="text-muted" style="font-size: 11px;"><?= htmlspecialchars(substr($ver['fecha'] ?? '', 0, 16)) ?></span>
                                    </div>
                                    <div style="color: #166534; font-size: 11.5px; margin-top: 2px;">
                                        <strong>Motivo:</strong> <?= htmlspecialchars($ver['motivo'] ?? 'Emisión Oficial') ?>
                                    </div>
                                    <?php if (!empty($ver['sha256'])): ?>
                                        <div class="font-monospace text-muted" style="font-size: 10.5px; margin-top: 3px;" title="<?= htmlspecialchars($ver['sha256']) ?>">
                                            <i class="fa-solid fa-shield-halved me-1 text-success"></i><?= substr($ver['sha256'], 0, 16) ?>…
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- ========================================================================= -->
<!-- MODALES COMPARTIDOS DE OPERACIONES LIMS                                    -->
<!-- ========================================================================= -->

<!-- 1. MODAL SUPERVISOR: CIERRE DEFINITIVO / APROBAR Y ARCHIVAR ORDEN -->
<div id="modalConfirmarCierreOS" class="modal-premium" style="display: none;">
    <div class="modal-premium-content" style="width: 480px; max-width: 95vw;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700;">
                <i class="fa-solid fa-circle-check text-success me-1"></i> Aprobar y Cierre Definitivo de O/S
            </h3>
            <button type="button" onclick="cerrarModalCierreOS()" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/cerrar-operacion">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_os" id="cierre_id_os" value="<?= (int)$o['id'] ?>">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/ver?id_os=<?= (int)$o['id'] ?>">
            <p style="font-size: 13.5px; color: #475569; margin-bottom: 16px; line-height: 1.5;">
                ¿Confirma el cierre y archivo formal de la orden <strong id="cierre_codigo_os" style="color: #0f3b68;"><?= htmlspecialchars($o['codigo_os']) ?></strong>? La orden pasará formalmente a la bandeja de <strong>Histórico / Archivo LIMS</strong>.
            </p>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="cerrarModalCierreOS()" class="btn btn-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-success btn-sm fw-bold">Aprobar y Archivar Orden</button>
            </div>
        </form>
    </div>
</div>

<!-- 2. MODAL SUPERVISOR: REVISIÓN Y APROBACIÓN TÉCNICA DE LA ORDEN -->
<div id="modalRevisionResultados" class="modal-premium" style="display: none;">
    <div class="modal-premium-content" style="width: 520px; max-width: 95vw;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700;">
                <i class="fa-solid fa-clipboard-check text-primary me-1"></i> Revisión Técnica de Calidad: <span id="qc_codigo_os" style="color:var(--cycsa-azul);"><?= htmlspecialchars($o['codigo_os']) ?></span>
            </h3>
            <button type="button" onclick="cerrarModalRevisionResultados()" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/procesar-revision-resultados" id="qc-form">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_os" id="qc_id_os" value="<?= (int)$o['id'] ?>">
            <input type="hidden" name="decision" id="qc_decision" value="Aprobar">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/ver?id_os=<?= (int)$o['id'] ?>">

            <p style="font-size: 13.5px; color:#475569; margin-bottom: 16px; line-height: 1.5;">
                Como supervisor de calidad, valide si los resultados cargados por el personal técnico cumplen con los estándares y tolerancias para autorizar la orden a estado <strong>Revisión Resultados Aprobada</strong>.
            </p>

            <div class="form-group" id="qc-group-obs" style="display:none; margin-bottom: 16px;">
                <label style="font-weight: 700; font-size: 12.5px; color: #b91c1c; display: block; margin-bottom: 6px;">Detalle los Resultados Observados (Requerido para devolución):</label>
                <textarea name="motivo_observacion" id="qc_motivo" class="form-control" rows="3" style="width:100%; box-sizing:border-box; border:1px solid #f87171; border-radius:6px; padding:8px 10px; font-size:13px;" placeholder="Ej: Corregir valor de carga en la probeta B de 7 días..."></textarea>
            </div>

            <div style="display: flex; justify-content: space-between; gap: 12px; margin-top: 20px; border-top:1px solid #f1f5f9; padding-top:14px;">
                <button type="button" onclick="ejecutarQCObservar()" class="btn btn-outline-danger btn-sm fw-bold"><i class="fa-solid fa-rotate-left"></i> Devolver / Observar</button>
                <div style="display:flex; gap:8px;">
                    <button type="button" onclick="cerrarModalRevisionResultados()" class="btn btn-secondary btn-sm">Cancelar</button>
                    <button type="button" onclick="ejecutarQCAprobar()" class="btn btn-success btn-sm fw-bold" id="btn-qc-aprobar" style="background:#10b981; border-color:#10b981;">
                        <i class="fa-solid fa-circle-check"></i> Aprobar Revisión Técnica
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- 3. MODAL SUPERVISOR: APROBAR MATRIZ DE ENSAYO INDIVIDUAL -->
<div id="modalAprobarMatrizIndex" class="modal-premium" style="display:none;">
    <div class="modal-premium-content" style="width: 480px; max-width: 95vw;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
            <h3 style="margin:0; color:#1e293b; font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Aprobar Matriz Técnica
            </h3>
            <button type="button" onclick="cerrarModalAprobarEnIndex()" style="background:none; border:none; font-size:22px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <p style="font-size:13.5px; color:#475569; margin:0 0 10px 0; line-height:1.5;">
            ¿Confirma la aprobación técnica y de calidad de los resultados y cálculos para este ensayo?
        </p>
        <p style="font-size:13px; color:#0f172a; font-weight:700; margin:0 0 15px 0;">
            Ensayo: <span id="aprob_index_ensayo" style="color:var(--cycsa-azul);"></span>
        </p>
        <form method="POST" action="/Cycsa/publico/operaciones/aprobar-matriz-producto">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_detalle" id="aprob_index_id_detalle" value="">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/ver?id_os=<?= (int)$o['id'] ?>">
            
            <div style="margin-bottom:16px;">
                <label style="font-size:12.5px; font-weight:600; color:#334155; display:block; margin-bottom:6px;">Nota de Aprobación (Opcional):</label>
                <input type="text" name="nota_aprobacion" class="form-control" style="width:100%; box-sizing:border-box; padding:9px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;" placeholder="Ej: Conforme con tolerancias y especificaciones ASTM.">
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="cerrarModalAprobarEnIndex()" class="btn btn-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-success btn-sm fw-bold" style="background:#059669; border-color:#059669;">
                    <i class="fa-solid fa-check"></i> Aprobar Matriz
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 4. MODAL SUPERVISOR: DEVOLVER MATRIZ DE ENSAYO INDIVIDUAL -->
<div id="modalDevolverMatrizIndex" class="modal-premium" style="display:none;">
    <div class="modal-premium-content" style="width: 520px; max-width: 95vw;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #fee2e2; padding-bottom:12px;">
            <h3 style="margin:0; color:#b91c1c; font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-rotate-left" style="color:#dc2626;"></i> Devolver Matriz Técnica (Observaciones)
            </h3>
            <button type="button" onclick="cerrarModalDevolverEnIndex()" style="background:none; border:none; font-size:22px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <p style="font-size:13px; color:#475569; margin:0 0 10px 0;">
            Ensayo: <strong id="dev_index_ensayo" style="color:#0f172a;"></strong>
        </p>
        <p style="font-size:13px; color:#475569; margin:0 0 15px 0;">
            Indique las observaciones técnicas para que el laboratorista realice los ajustes necesarios.
        </p>
        <form method="POST" action="/Cycsa/publico/operaciones/devolver-matriz-producto">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_detalle" id="dev_index_id_detalle" value="">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/ver?id_os=<?= (int)$o['id'] ?>">
            
            <div style="margin-bottom:16px;">
                <label style="font-size:12.5px; font-weight:700; color:#1e293b; display:block; margin-bottom:6px;">Motivo u Observaciones Técnicas * :</label>
                <textarea name="motivo_devolucion" required rows="4" style="width:100%; box-sizing:border-box; padding:10px 12px; border:1.5px solid #f87171; border-radius:6px; font-size:13.5px; font-family:inherit; resize:vertical;" placeholder="Ej: Corregir el valor de carga en la probeta #2. Se observa discrepancia con la aguja de la prensa."></textarea>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="cerrarModalDevolverEnIndex()" class="btn btn-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-danger btn-sm fw-bold" style="background:#dc2626; border-color:#dc2626;">
                    <i class="fa-solid fa-rotate-left"></i> Devolver al Laboratorio
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 5. MODAL: VER OBSERVACIÓN DE DEVOLUCIÓN -->
<div id="modalVerObservacionDevolucion" class="modal-premium" style="display:none;">
    <div class="modal-premium-content" style="width: 480px; max-width: 95vw; border-color: #fecaca;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #fee2e2; padding-bottom:12px;">
            <h3 style="margin:0; color:#b91c1c; font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-comment-dots" style="color:#dc2626;"></i> Observación del Supervisor
            </h3>
            <button type="button" onclick="cerrarModalVerObservacionDevolucion()" style="background:none; border:none; font-size:22px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <p style="font-size:13px; color:#475569; margin:0 0 10px 0;">
            Ensayo: <strong id="ver_obs_ensayo" style="color:#0f172a;"></strong>
        </p>
        <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:8px; padding:14px; margin-bottom:16px;">
            <p id="ver_obs_motivo" style="font-size:13.5px; color:#991b1b; margin:0; line-height:1.5; white-space:pre-wrap;"></p>
        </div>
        <div style="font-size:12px; color:#64748b; margin-bottom:18px;">
            Devuelto por: <strong id="ver_obs_usuario"></strong> el <span id="ver_obs_fecha"></span>
        </div>
        <div style="display:flex; justify-content:flex-end;">
            <button type="button" onclick="cerrarModalVerObservacionDevolucion()" class="btn btn-secondary btn-sm">Cerrar</button>
        </div>
    </div>
</div>

<!-- 6. MODAL: ENVIAR MATRIZ EN PDF AL CLIENTE -->
<div id="modalEnviarMatriz" class="modal-premium" style="display:none;">
    <div class="modal-premium-content" style="width: 500px; max-width: 95vw;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
            <h3 style="margin:0; color:#1e293b; font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-paper-plane text-success"></i> Enviar Matriz al Cliente
            </h3>
            <button type="button" onclick="cerrarModalEnviarMatriz()" style="background:none; border:none; font-size:22px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/enviar-matriz-cliente">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_detalle" id="env_index_id_detalle" value="">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/ver?id_os=<?= (int)$o['id'] ?>">
            
            <div style="margin-bottom:14px;">
                <label style="font-size:12.5px; font-weight:600; color:#334155; display:block; margin-bottom:4px;">Ensayo:</label>
                <div id="env_index_ensayo" style="font-weight:700; color:#0f172a; font-size:13.5px;"></div>
            </div>
            <div style="margin-bottom:14px;">
                <label style="font-size:12.5px; font-weight:600; color:#334155; display:block; margin-bottom:4px;">Cliente:</label>
                <input type="text" id="env_index_cliente" name="cliente_nombre" class="form-control" style="width:100%; box-sizing:border-box; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;" readonly>
            </div>
            <div style="margin-bottom:16px;">
                <label style="font-size:12.5px; font-weight:600; color:#334155; display:block; margin-bottom:4px;">Correo Destinatario * :</label>
                <input type="email" id="env_index_email" name="destinatario_email" class="form-control" style="width:100%; box-sizing:border-box; padding:8px 10px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;" required>
            </div>
            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="cerrarModalEnviarMatriz()" class="btn btn-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-success btn-sm fw-bold">
                    <i class="fa-solid fa-paper-plane me-1"></i> Enviar Correo
                </button>
            </div>
        </form>
    </div>
</div>

<!-- 7. MODAL: REEMISIÓN ADMIN (BASE-N) -->
<div id="modalCorreccionAdminIndex" class="modal-premium" style="display:none;">
    <div class="modal-premium-content" style="width: 540px; max-width: 95vw;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
            <h3 style="margin:0; color:#1e293b; font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-pen-ruler" style="color:#d97706;"></i> Reemitir Informe (Corrección Administrativa)
            </h3>
            <button type="button" onclick="cerrarModalCorreccionAdminIndex()" style="background:none; border:none; font-size:22px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <p style="font-size:13px; color:#0f172a; font-weight:700; margin:0 0 10px 0;">
            Ensayo: <span id="corr_index_ensayo_nombre" style="color:var(--cycsa-azul);"></span>
        </p>
        <form method="POST" action="/Cycsa/publico/operaciones/solicitar-correccion-admin">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_detalle" id="corr_index_id_detalle" value="">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/ver?id_os=<?= (int)$o['id'] ?>">

            <div style="background:#fffbeb; border:1px solid #fde68a; color:#92400e; padding:10px 14px; border-radius:8px; font-size:12.5px; margin-bottom:14px; line-height:1.4;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Se emitirá una nueva versión correlativa <strong>(ej: MS-XXXX-26-1)</strong> que sustituirá a la versión previa. La matriz pasará a <strong>EN REVISIÓN</strong> para su re-aprobación obligatoria.
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:4px;">
                    Motivo obligatorio de la corrección: <span style="color:#dc2626;">*</span>
                </label>
                <textarea name="motivo_correccion" id="corr_index_motivo" rows="2" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 10px; font-size:13px; font-family:inherit; box-sizing:border-box;" placeholder="Ej. Corrección de razón social / proyecto a solicitud del cliente..." required></textarea>
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:4px;">
                    Nombre o Razón Social (para el informe):
                </label>
                <input type="text" name="cliente_nombre" id="corr_index_cliente" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 10px; font-size:13px; box-sizing:border-box;">
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:4px;">
                    Proyecto:
                </label>
                <input type="text" name="nombre_proyecto" id="corr_index_proyecto" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 10px; font-size:13px; box-sizing:border-box;">
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="cerrarModalCorreccionAdminIndex()" class="btn btn-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-warning btn-sm fw-bold" style="background:#d97706; border-color:#d97706; color:white;">
                    <i class="fa-solid fa-file-pen me-1"></i> Guardar y Reemitir
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function cambiarTabFull(btn, tabId) {
        document.querySelectorAll('.btn-tab-full').forEach(b => {
            b.classList.remove('active');
            b.style.background = 'transparent';
            b.style.color = '#475569';
        });
        if (btn) {
            btn.classList.add('active');
            btn.style.background = 'var(--cycsa-azul, #103487)';
            btn.style.color = 'white';
        }

        document.querySelectorAll('.tab-pane-full').forEach(p => p.style.display = 'none');
        const target = document.getElementById(tabId);
        if (target) target.style.display = 'block';
    }

    function abrirModalCerrarOS(idOS, codigoOS) {
        const idElem = document.getElementById('cierre_id_os');
        const codeElem = document.getElementById('cierre_codigo_os');
        if (idElem) idElem.value = idOS;
        if (codeElem && codigoOS) codeElem.innerText = codigoOS;
        const modal = document.getElementById('modalConfirmarCierreOS');
        if (modal) modal.style.display = 'block';
    }

    function cerrarModalCierreOS() {
        const modal = document.getElementById('modalConfirmarCierreOS');
        if (modal) modal.style.display = 'none';
    }

    // Modal QC Revisión Técnica
    function abrirModalRevisionResultados(id, code) {
        const idElem = document.getElementById('qc_id_os');
        const codeElem = document.getElementById('qc_codigo_os');
        if (idElem) idElem.value = id;
        if (codeElem && code) codeElem.innerText = code;
        const group = document.getElementById('qc-group-obs');
        if (group) group.style.display = 'none';
        const motivo = document.getElementById('qc_motivo');
        if (motivo) motivo.value = '';
        const btnAprob = document.getElementById('btn-qc-aprobar');
        if (btnAprob) btnAprob.style.display = 'inline-flex';
        const modal = document.getElementById('modalRevisionResultados');
        if (modal) modal.style.display = 'block';
    }

    function cerrarModalRevisionResultados() {
        const modal = document.getElementById('modalRevisionResultados');
        if (modal) modal.style.display = 'none';
    }

    function ejecutarQCAprobar() {
        const dec = document.getElementById('qc_decision');
        if (dec) dec.value = 'Aprobar';
        const form = document.getElementById('qc-form');
        if (form) form.submit();
    }

    function ejecutarQCObservar() {
        const group = document.getElementById('qc-group-obs');
        if (!group) return;
        if (group.style.display === 'none') {
            group.style.display = 'block';
            const motivo = document.getElementById('qc_motivo');
            if (motivo) motivo.focus();
            const btnAprob = document.getElementById('btn-qc-aprobar');
            if (btnAprob) btnAprob.style.display = 'none';
        } else {
            const motivo = document.getElementById('qc_motivo');
            if (!motivo || !motivo.value.trim()) {
                alert('Debe indicar un motivo u observación si desea devolver los resultados.');
                return;
            }
            const dec = document.getElementById('qc_decision');
            if (dec) dec.value = 'Rechazar';
            const form = document.getElementById('qc-form');
            if (form) form.submit();
        }
    }

    // Modal Aprobación Individual de Matriz
    function abrirModalAprobarEnIndex(idDetalle, nombreEnsayo) {
        const idElem = document.getElementById('aprob_index_id_detalle');
        const nomElem = document.getElementById('aprob_index_ensayo');
        if (idElem) idElem.value = idDetalle;
        if (nomElem) nomElem.innerText = nombreEnsayo;
        const modal = document.getElementById('modalAprobarMatrizIndex');
        if (modal) modal.style.display = 'block';
    }

    function cerrarModalAprobarEnIndex() {
        const modal = document.getElementById('modalAprobarMatrizIndex');
        if (modal) modal.style.display = 'none';
    }

    // Modal Devolución Individual de Matriz
    function abrirModalDevolverEnIndex(idDetalle, nombreEnsayo) {
        const idElem = document.getElementById('dev_index_id_detalle');
        const nomElem = document.getElementById('dev_index_ensayo');
        if (idElem) idElem.value = idDetalle;
        if (nomElem) nomElem.innerText = nombreEnsayo;
        const modal = document.getElementById('modalDevolverMatrizIndex');
        if (modal) modal.style.display = 'block';
    }

    function cerrarModalDevolverEnIndex() {
        const modal = document.getElementById('modalDevolverMatrizIndex');
        if (modal) modal.style.display = 'none';
    }

    // Modal Ver Observación
    function verObservacionDevolucion(idDetalle, nombreEnsayo, motivo, usuario, fecha) {
        const ensElem = document.getElementById('ver_obs_ensayo');
        const motElem = document.getElementById('ver_obs_motivo');
        const usrElem = document.getElementById('ver_obs_usuario');
        const fecElem = document.getElementById('ver_obs_fecha');
        if (ensElem) ensElem.innerText = nombreEnsayo;
        if (motElem) motElem.innerText = motivo || 'Sin observaciones registradas.';
        if (usrElem) usrElem.innerText = usuario || 'Supervisor';
        if (fecElem) fecElem.innerText = fecha || '';
        const modal = document.getElementById('modalVerObservacionDevolucion');
        if (modal) modal.style.display = 'block';
    }

    function cerrarModalVerObservacionDevolucion() {
        const modal = document.getElementById('modalVerObservacionDevolucion');
        if (modal) modal.style.display = 'none';
    }

    // Modal Enviar Matriz al Cliente
    function abrirModalEnviarMatriz(idDetalle, nombreEnsayo, clienteNombre, clienteEmail, codigoOS) {
        const idElem = document.getElementById('env_index_id_detalle');
        const ensElem = document.getElementById('env_index_ensayo');
        const cliElem = document.getElementById('env_index_cliente');
        const emlElem = document.getElementById('env_index_email');
        if (idElem) idElem.value = idDetalle;
        if (ensElem) ensElem.innerText = nombreEnsayo;
        if (cliElem) cliElem.value = clienteNombre || '';
        if (emlElem) emlElem.value = clienteEmail || '';
        const modal = document.getElementById('modalEnviarMatriz');
        if (modal) modal.style.display = 'block';
    }

    function cerrarModalEnviarMatriz() {
        const modal = document.getElementById('modalEnviarMatriz');
        if (modal) modal.style.display = 'none';
    }

    // Modal Corrección Administrativa / Reemisión
    function abrirModalCorreccionAdminIndex(idDetalle, nombreEnsayo, cliente, proyecto, ubicacion) {
        const idElem = document.getElementById('corr_index_id_detalle');
        const ensElem = document.getElementById('corr_index_ensayo_nombre');
        const motElem = document.getElementById('corr_index_motivo');
        const cliElem = document.getElementById('corr_index_cliente');
        const proElem = document.getElementById('corr_index_proyecto');
        if (idElem) idElem.value = idDetalle;
        if (ensElem) ensElem.innerText = nombreEnsayo || '';
        if (motElem) motElem.value = '';
        if (cliElem) cliElem.value = cliente || '';
        if (proElem) proElem.value = proyecto || '';
        const modal = document.getElementById('modalCorreccionAdminIndex');
        if (modal) modal.style.display = 'block';
    }

    function cerrarModalCorreccionAdminIndex() {
        const modal = document.getElementById('modalCorreccionAdminIndex');
        if (modal) modal.style.display = 'none';
    }

    // Cerrar modales con clic fuera
    window.addEventListener('click', function(e) {
        const modales = [
            'modalConfirmarCierreOS',
            'modalRevisionResultados',
            'modalAprobarMatrizIndex',
            'modalDevolverMatrizIndex',
            'modalVerObservacionDevolucion',
            'modalEnviarMatriz',
            'modalCorreccionAdminIndex'
        ];
        modales.forEach(function(mId) {
            const m = document.getElementById(mId);
            if (m && e.target === m) {
                m.style.display = 'none';
            }
        });
    });
</script>
