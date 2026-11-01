<?php
// Dedicated full-page view for a single Operation (O/S)
$rolSesion = (int)($_SESSION['usuario_rol'] ?? 0);
$esSupervisor = in_array($rolSesion, [1, 2, 3]);

$cliInfNom = $o['cliente_informe_nombre'] ?? $o['cliente_nombre'];
$cliFactNom = $o['cliente_factura_nombre'] ?? $o['cliente_nombre'];
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
                Proyecto: <strong style="color: #1e293b;"><?= htmlspecialchars($o['nombre_proyecto']) ?></strong> &bull; Cotización: <strong><?= htmlspecialchars($o['cot_codigo']) ?></strong> &bull; Emisión: <?= date('d/m/Y', strtotime($o['fecha_emision'])) ?>
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

            <?php if ($o['puede_cerrar']): ?>
                <button type="button" onclick="abrirModalCerrarOS(<?= $o['id'] ?>, '<?= htmlspecialchars(addslashes($o['codigo_os'])) ?>')" class="btn btn-success btn-sm fw-bold" style="padding: 7px 16px; border-radius: 6px;">
                    <i class="fa-solid fa-lock me-1"></i> Finalizar y Archivar Orden
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
            <?php elseif ($o['puede_cerrar']): ?>
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
                            <?= $o['comercial_100'] ? 'Factura cancelada (C$ 0.00)' : ('Saldo pendiente C$ ' . number_format($o['saldo_os'], 2)) ?>
                        <?php endif; ?>
                    </span>
                </div>
                <?php if (!$o['puede_cerrar']): ?>
                    <div style="font-size: 12.5px; color: #b45309; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 12px 16px; margin-bottom: 18px;">
                        <i class="fa-solid fa-circle-exclamation me-1"></i>
                        <?= !$o['tecnico_100'] ? 'Faltan ensayos por aprobar formalmente por supervisión técnica.' : ($esCreditoFila ? 'Falta generar la cuenta por cobrar en el sistema.' : 'Saldo comercial pendiente de cobro (condición Contado).') ?>
                    </div>
                <?php endif; ?>
                <div>
                    <?php if (in_array($o['estado'], ['Finalizado', 'Archivado', 'Cerrado'])): ?>
                        <span class="badge bg-secondary" style="font-size: 13px; padding: 10px 18px; border-radius: 20px;">
                            <i class="fa-solid fa-box-archive me-1"></i> Orden Archivada en Histórico
                        </span>
                    <?php elseif ($o['puede_cerrar']): ?>
                        <button type="button" 
                                onclick="abrirModalCerrarOS(<?= $o['id'] ?>, '<?= htmlspecialchars(addslashes($o['codigo_os'])) ?>')" 
                                class="btn btn-success" 
                                style="height: 40px; font-size: 13.5px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 8px; background-color: #16a34a; border-color: #15803d;">
                            <i class="fa-solid fa-lock"></i> Finalizar y Archivar Orden
                        </button>
                    <?php else: ?>
                        <button type="button" 
                                disabled 
                                class="btn btn-outline-secondary" 
                                style="height: 40px; font-size: 13px; font-weight: 600; border-radius: 6px; cursor: not-allowed; opacity: 0.7;" 
                                title="Cierre bloqueado: Requiere 100% de ensayos aprobados y cobro comercial liquidado.">
                            <i class="fa-solid fa-lock"></i> Cierre LIMS Bloqueado
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- MODALES COMPARTIDOS -->
<!-- MODAL CIERRE -->
<div id="modalConfirmarCierreOS" class="modal-premium" style="display: none;">
    <div class="modal-premium-content" style="width: 480px; max-width: 95vw;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700;">
                <i class="fa-solid fa-box-archive text-success me-1"></i> Cierre Definitivo de O/S
            </h3>
            <button type="button" onclick="cerrarModalCierreOS()" style="background:none;border:none;font-size:20px;cursor:pointer;">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/cerrar-operacion">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_os" id="cierre_id_os" value="">
            <p style="font-size: 13.5px; color: #475569; margin-bottom: 16px;">
                ¿Confirma el cierre y archivo de la orden <strong id="cierre_codigo_os" style="color: #0f3b68;"></strong>? La orden pasará a la bandeja de <strong>Histórico / Archivo LIMS</strong>.
            </p>
            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" onclick="cerrarModalCierreOS()" class="btn btn-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-success btn-sm fw-bold">Confirmar Cierre LIMS</button>
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
        btn.classList.add('active');
        btn.style.background = 'var(--cycsa-azul, #103487)';
        btn.style.color = 'white';

        document.querySelectorAll('.tab-pane-full').forEach(p => p.style.display = 'none');
        const target = document.getElementById(tabId);
        if (target) target.style.display = 'block';
    }

    function abrirModalCerrarOS(idOS, codigoOS) {
        document.getElementById('cierre_id_os').value = idOS;
        document.getElementById('cierre_codigo_os').innerText = codigoOS;
        document.getElementById('modalConfirmarCierreOS').style.display = 'block';
    }

    function cerrarModalCierreOS() {
        document.getElementById('modalConfirmarCierreOS').style.display = 'none';
    }
</script>
