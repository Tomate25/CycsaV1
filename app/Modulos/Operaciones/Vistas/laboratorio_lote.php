<?php
/**
 * Vista de Laboratorio por Lote (Hoja RT-FM-13) - ISO/IEC 17025
 * Muestra todas las N muestras de la hoja y sus réplicas CR en la misma secuencia que la matriz técnica.
 * Cada fila corresponde a una muestra o réplica con su edad y fecha individual de rotura.
 */
$totalMuestrasLote = count($muestrasLote ?? []);
$totalReplicasCr = 0;
$mapaMatrizJs = [];
$probetasPendientesLote = [];

foreach (($muestrasLote ?? []) as $itemLote) {
    $mRow = $itemLote['muestra'] ?? [];
    $mId = (int)($mRow['muestra_id'] ?? 0);
    $esRep = !empty($mRow['is_qa_qc']) || ($mRow['replica_codigo'] ?? '') === '-CR' || str_ends_with((string)($mRow['codigo_lab'] ?? ''), '-CR');
    if ($esRep) {
        $totalReplicasCr++;
    }
    $mapaMatrizJs[$mId] = $itemLote['datos_matriz'] ?? [];

    $dmRow = $itemLote['datos_matriz'] ?? [];
    $areaDef = (!empty($dmRow['area']) && (float)$dmRow['area'] > 0) ? (float)$dmRow['area'] : '';
    foreach (($itemLote['especimenes'] ?? []) as $espItem) {
        if (($espItem['estado'] ?? '') !== 'ensayado' && !empty($espItem['plan_edad_id'])) {
            $probetasPendientesLote[] = [
                'muestra_id' => $mId,
                'especimen_id' => (int)$espItem['id'],
                'codigo_lab' => (string)$espItem['codigo_lab'],
                'plan_edad_id' => (int)$espItem['plan_edad_id'],
                'edad_dias' => (int)($espItem['edad_dias'] ?? ($dmRow['edad_dias'] ?? 0)),
                'fecha_programada' => (string)($espItem['fecha_programada'] ?? ($dmRow['fecha_rotura_programada'] ?? '')),
                'tolerancia_horas' => (int)($espItem['tolerancia_horas'] ?? 24),
                'area' => $areaDef,
                'carga_precargada' => ''
            ];
        }
    }
}

$numRegHoja = !empty($hoja['numero_registro']) ? $hoja['numero_registro'] : ('#' . (int)($id_hoja ?? 0));
$ensayoTitulo = !empty($detalleCotizacion['descripcion_item'])
    ? $detalleCotizacion['descripcion_item']
    : (!empty($detalleCotizacion['nombre_comercial'])
        ? $detalleCotizacion['nombre_comercial']
        : (!empty($muestrasLote[0]['muestra']['plantilla_nombre']) ? $muestrasLote[0]['muestra']['plantilla_nombre'] : 'Resistencia a Compresión del Concreto'));
$normaTitulo = !empty($detalleCotizacion['norma_astm'])
    ? $detalleCotizacion['norma_astm']
    : (!empty($muestrasLote[0]['muestra']['norma_referencia']) ? $muestrasLote[0]['muestra']['norma_referencia'] : 'ASTM C39');

// Métricas de rompimientos periódicos y estado de consolidación del lote
$totalProbetas = 0;
$totalEnsayadas = 0;
$totalParcialesAprobados = 0;
$totalParcialesEmitidos = 0;
$puedeConsolidarTodoElLote = ($totalMuestrasLote > 0);

foreach (($muestrasLote ?? []) as $itemLote) {
    $espList = $itemLote['especimenes'] ?? [];
    $esp0 = !empty($espList[0]) ? $espList[0] : null;
    $totalProbetas++;
    if (!empty($esp0['estado']) && $esp0['estado'] === 'ensayado') {
        $totalEnsayadas++;
    }
    if (empty($itemLote['puede_consolidar'])) {
        $puedeConsolidarTodoElLote = false;
    }
    foreach (($itemLote['edades'] ?? []) as $edL) {
        if (!empty($edL['informe_parcial_id'])) {
            $totalParcialesEmitidos++;
            if (($edL['informe_parcial_estado'] ?? '') === 'aprobado') {
                $totalParcialesAprobados++;
            }
        }
    }
}
?>
<style>
    :root {
        --cycsa-azul: #103487;
        --cycsa-azul-hover: #0c2766;
        --cycsa-rojo: #e31837;
    }
    .lab-box { background: white; padding: 22px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 22px; border: 1px solid #e2e8f0; }
    .tabla-lab { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 8px; }
    .tabla-lab th { background: #f8fafc; color: #475569; font-weight: 700; padding: 10px 12px; text-align: left; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 11px; white-space: nowrap; }
    .tabla-lab td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
    .tabla-lab tr:hover td { background-color: #f8fafc; }
    .badge-lab { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .badge-ensayado { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .badge-aprobado { background: #dcfce7; color: #15803d; border: 1px solid #86efac; }
    .badge-devuelto { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
    .badge-revision { background: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .badge-programado { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .badge-curado { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .badge-vencido { background: #fee2e2; color: #991b1b; border: 1px solid #fca5a5; }
    .badge-replica { background: #fef3c7; color: #b45309; border: 1px solid #f59e0b; }
    .btn-accion { border: none; background: none; cursor: pointer; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
    .btn-primary-lab { background: #103487; color: white; border: 1px solid #103487; }
    .btn-primary-lab:hover { background: #0c2766; color: white; }
    .btn-success-lab { background: #10b981; color: white; border: 1px solid #059669; }
    .btn-success-lab:hover { background: #059669; color: white; }
    .btn-warning-lab { background: #f59e0b; color: white; border: 1px solid #d97706; }
    .btn-warning-lab:hover { background: #d97706; color: white; }
    .btn-secondary-lab { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
    .btn-secondary-lab:hover { background: #e2e8f0; }
    .btn-accion[disabled] { opacity: 0.6; cursor: not-allowed; }
    .modal-lab { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .modal-lab-content { background: white; margin: 3% auto; padding: 25px 30px; border-radius: 12px; width: 92%; max-width: 720px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15); border: 1px solid #e2e8f0; box-sizing: border-box; }
</style>

<!-- Encabezado del Lote -->
<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800;">
            <i class="fa-solid fa-layer-group" style="color: var(--cycsa-azul); margin-right: 6px;"></i>
            Hoja de Trabajo por Lote &bull; RT-FM-13 Reg: <?= htmlspecialchars((string)$numRegHoja, ENT_QUOTES, 'UTF-8') ?>
        </h2>
        <p style="color: #64748b; margin-top: 4px; font-size: 13.5px;">
            Ensayo: <strong style="color: #1e293b;"><?= htmlspecialchars((string)$ensayoTitulo, ENT_QUOTES, 'UTF-8') ?></strong>
            (<?= htmlspecialchars((string)$normaTitulo, ENT_QUOTES, 'UTF-8') ?>)
            | Total en Lote: <strong style="color: #0369a1;"><?= $totalMuestrasLote ?> muestra(s)</strong>
            <?php if ($totalReplicasCr > 0): ?>
                (<span style="color: #b45309; font-weight: 700;"><?= $totalReplicasCr ?> Réplica(s) CR</span>)
            <?php endif; ?>
        </p>
    </div>
    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <?php if (!empty($probetasPendientesLote)): ?>
            <button type="button" class="btn-accion btn-primary-lab" onclick="abrirModalRoturaLoteBatch()" style="font-size: 13px; padding: 7px 14px;">
                <i class="fa-solid fa-hammer"></i> Ensayar Lote en Conjunto (<?= count($probetasPendientesLote) ?> Probetas Juntas)
            </button>
        <?php endif; ?>
        <a href="/Cycsa/publico/laboratorio?tab=bandeja" class="btn-accion btn-secondary-lab">
            <i class="fa-solid fa-arrow-left"></i> Volver a Bandeja Anónima
        </a>
    </div>
</div>

<!-- Alerta de Imparcialidad ISO 17025 -->
<div style="background-color: #fefce8; border: 1px solid #fef08a; color: #854d0e; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-size: 13px; font-weight: 500;">
    <i class="fa-solid fa-eye-slash" style="font-size: 18px; color: #ca8a04;"></i>
    <div>
        <strong>RESGUARDO DE IMPARCIALIDAD Y CONFORMIDAD (ISO 17025 Cláusulas 4.1 y 4.2):</strong> Cada fila del lote corresponde exactamente a una fila de la matriz técnica de la RT-FM-13. Las fechas de rompimiento son tomadas directamente de Operaciones sin exposición comercial.
    </div>
</div>

<!-- Notificaciones Flash -->
<?php if (!empty($exito)): ?>
    <div style="background-color: #f0fdf4; color: #166534; padding: 12px 16px; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 15px; font-size: 13.5px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars((string)$exito, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div style="background-color: #fef2f2; color: #991b1b; padding: 12px 16px; border-radius: 8px; border: 1px solid #fecaca; margin-bottom: 15px; font-size: 13.5px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<!-- TABLA ÚNICA DE MUESTRAS DEL LOTE (ORDEN EXACTO DE LA MATRIZ TÉCNICA) -->
<div class="lab-box" style="padding: 16px; overflow-x: auto;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
        <h4 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 800; color: var(--cycsa-azul);">
            <i class="fa-solid fa-list-ol"></i> Probetas y Ensayos Programados del Lote
        </h4>
        <span style="font-size: 12px; color: #64748b;">
            Secuencia definida en Operaciones &bull; 1 probeta por fila
        </span>
    </div>

    <table class="tabla-lab">
        <thead>
            <tr>
                <th style="width: 30px;">#</th>
                <th>Código Muestra / Probeta</th>
                <th>Tipo / Réplica</th>
                <th>Moldeo</th>
                <th>Edad (Matriz)</th>
                <th>Fecha Programada</th>
                <th>Temp (°C)</th>
                <th>Área (in²)</th>
                <th>Tamaño</th>
                <th>Estado</th>
                <th>Rompimiento (lb / PSI)</th>
                <th>Informe Parcial</th>
                <th style="text-align: right; width: 140px;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php 
            $hoyStr = date('Y-m-d');
            foreach (($muestrasLote ?? []) as $idxSec => $item): 
                $m = $item['muestra'] ?? [];
                $mId = (int)($m['muestra_id'] ?? 0);
                $especimenes = $item['especimenes'] ?? [];
                $esp = !empty($especimenes[0]) ? $especimenes[0] : null;
                $dm = $item['datos_matriz'] ?? [];
                $esReplica = !empty($m['is_qa_qc']) || ($m['replica_codigo'] ?? '') === '-CR' || str_ends_with((string)($m['codigo_lab'] ?? ''), '-CR');
                
                $codLab = (string)($esp['codigo_lab'] ?? ($m['codigo_lab'] ?? ''));
                $tempMuestra = $dm['temperatura_c'] ?? ($m['temperatura_c'] ?? null);
                
                $tamanoCil = $dm['tamano_cilindro'] ?? (!empty($esp['dimensiones']) ? $esp['dimensiones'] : null);
                $areaVal = !empty($dm['area']) ? (float)$dm['area'] : (!empty($esp['area']) ? (float)$esp['area'] : null);
                
                $edadDias = $dm['edad_dias'] ?? (!empty($esp['edad_dias']) ? (int)$esp['edad_dias'] : null);
                $fechaProg = $dm['fecha_rotura_programada'] ?? (!empty($esp['fecha_programada']) ? (string)$esp['fecha_programada'] : null);
                $fechaMoldeo = $dm['fecha_fabricacion'] ?? (!empty($m['fecha_moldeo']) ? (string)$m['fecha_moldeo'] : null);
                
                $ensayado = (!empty($esp['estado']) && $esp['estado'] === 'ensayado');
                $planEdadId = !empty($esp['plan_edad_id']) ? (int)$esp['plan_edad_id'] : 0;
                $planId = !empty($item['plan']['id']) ? (int)$item['plan']['id'] : 0;
                $espId = !empty($esp['id']) ? (int)$esp['id'] : 0;
                
                $tieneProgramacion = ($edadDias !== null && $edadDias > 0) || !empty($fechaProg);
                $esVencido = (!$ensayado && !empty($fechaProg) && $fechaProg < $hoyStr);

                // Localizar informe parcial de la muestra/edad
                $infParcial = null;
                foreach (($item['edades'] ?? []) as $edRow) {
                    if ($planEdadId > 0 && (int)$edRow['id'] === $planEdadId) {
                        $infParcial = $edRow;
                        break;
                    }
                }
                if (!$infParcial && !empty($item['edades'][0])) {
                    $infParcial = $item['edades'][0];
                }
                $infParcialId = !empty($infParcial['informe_parcial_id']) ? (int)$infParcial['informe_parcial_id'] : 0;
                $infParcialNum = (string)($infParcial['informe_parcial_numero'] ?? '');
                $infParcialEst = (string)($infParcial['informe_parcial_estado'] ?? '');
            ?>
            <tr style="<?= $esReplica ? 'background-color: #fffdf5;' : '' ?>">
                <td style="font-weight: 800; color: #64748b; font-size: 11px; white-space: nowrap;">
                    #<?= $idxSec + 1 ?> en Matriz
                </td>
                <td style="font-family: monospace; font-size: 13.5px; font-weight: 800; color: #0369a1; white-space: nowrap;">
                    <i class="fa-solid fa-cube" style="color: #64748b; font-size: 11px;"></i>
                    <?= htmlspecialchars($codLab, ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td>
                    <?php if ($esReplica): ?>
                        <span class="badge-lab badge-replica" title="Réplica Control de Calidad (-CR)"><i class="fa-solid fa-clone"></i> Réplica Control de Calidad (-CR)</span>
                    <?php else: ?>
                        <span style="font-size: 12px; color: #64748b;">Normal</span>
                    <?php endif; ?>
                </td>
                <td style="font-size: 12px;">
                    <?= !empty($fechaMoldeo) ? htmlspecialchars(date('d/m/Y', strtotime($fechaMoldeo))) : '<span style="color:#94a3b8;">--</span>' ?>
                </td>
                <td>
                    <?php if ($edadDias !== null && $edadDias > 0): ?>
                        <strong style="color: #7c3aed; font-size: 13px;"><?= (int)$edadDias ?> días</strong>
                    <?php else: ?>
                        <span style="color: #b45309; font-size: 11.5px; font-weight: 600;"><i class="fa-solid fa-hourglass-start"></i> Pendiente</span>
                    <?php endif; ?>
                </td>
                <td style="font-size: 12.5px;">
                    <?php if (!empty($fechaProg)): ?>
                        <strong style="<?= $esVencido ? 'color:#dc2626;' : 'color:#1e293b;' ?>">
                            <?= htmlspecialchars(date('d/m/Y', strtotime($fechaProg))) ?>
                        </strong>
                    <?php else: ?>
                        <span style="color: #94a3b8; font-style: italic;">Sin programar</span>
                    <?php endif; ?>
                </td>
                <td style="font-size: 12px;">
                    <?= ($tempMuestra !== null && $tempMuestra !== '') ? (htmlspecialchars((string)$tempMuestra) . ' °C') : '<span style="color:#94a3b8;">--</span>' ?>
                </td>
                <td style="font-family: monospace; font-size: 12.5px;">
                    <?= ($areaVal !== null && $areaVal > 0) ? number_format($areaVal, 3) : '<span style="color:#94a3b8;">--</span>' ?>
                </td>
                <td style="font-size: 11.5px; color: #475569;">
                    <?= !empty($tamanoCil) ? htmlspecialchars($tamanoCil) : '<span style="color:#94a3b8;">--</span>' ?>
                </td>
                <td>
                    <?php if ($ensayado): ?>
                        <span class="badge-lab badge-ensayado"><i class="fa-solid fa-check"></i> Ensayado</span>
                    <?php elseif ($esVencido): ?>
                        <span class="badge-lab badge-vencido"><i class="fa-solid fa-triangle-exclamation"></i> Vencido</span>
                    <?php elseif ($tieneProgramacion): ?>
                        <span class="badge-lab badge-programado">Programado</span>
                    <?php else: ?>
                        <span class="badge-lab badge-curado" title="Pendiente de definir fecha o edad en Operaciones">Pendiente Operaciones</span>
                    <?php endif; ?>
                </td>
                <td style="font-size: 12px; white-space: nowrap;">
                    <?php if ($ensayado): ?>
                        <div style="line-height: 1.35;">
                            <strong style="color: #047857; font-family: monospace; font-size: 13px;">
                                <?= number_format((float)($esp['esfuerzo_psi'] ?? 0), 0) ?> PSI
                            </strong>
                            <span style="font-size: 11px; color: #64748b;">(<?= number_format((float)($esp['esfuerzo_kgcm2'] ?? 0), 1) ?> kg/cm²)</span>
                            <div style="font-size: 11px; color: #475569;">
                                Carga: <strong><?= number_format((float)($esp['carga'] ?? 0), 0) ?> lb</strong>
                                <?php if (!empty($esp['tipo_falla'])): ?>
                                    &bull; <span style="font-size: 10.5px; color: #64748b;" title="<?= htmlspecialchars((string)$esp['tipo_falla'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars(explode('(', (string)$esp['tipo_falla'])[0]) ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    <?php else: ?>
                        <span style="color:#94a3b8; font-size: 11.5px;">--</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?php if ($ensayado): ?>
                        <?php if (!empty($infParcialId)): ?>
                            <div style="display: flex; flex-direction: column; gap: 3px;">
                                <span style="font-family: monospace; font-weight: 800; color: #0369a1; font-size: 12px; white-space: nowrap;">
                                    <i class="fa-solid fa-file-lines" style="color: #64748b; font-size: 10.5px;"></i> <?= htmlspecialchars($infParcialNum, ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <div>
                                    <?php if ($infParcialEst === 'aprobado'): ?>
                                        <span class="badge-lab badge-aprobado"><i class="fa-solid fa-check"></i> Aprobado</span>
                                    <?php elseif ($infParcialEst === 'devuelto'): ?>
                                        <span class="badge-lab badge-devuelto"><i class="fa-solid fa-xmark"></i> Devuelto</span>
                                    <?php else: ?>
                                        <span class="badge-lab badge-revision"><i class="fa-solid fa-clock"></i> En Revisión</span>
                                    <?php endif; ?>
                                </div>
                                <?php if ($infParcialEst === 'en_revision' && ($rolUsuario === 1 || $rolUsuario === 3)): ?>
                                    <div style="display: inline-flex; gap: 4px; margin-top: 2px;">
                                        <form method="POST" action="/Cycsa/publico/laboratorio/procesar-revision-informe" style="display:inline;">
                                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            <input type="hidden" name="id_hoja" value="<?= (int)$id_hoja ?>">
                                            <input type="hidden" name="muestra_id" value="<?= $mId ?>">
                                            <input type="hidden" name="informe_id" value="<?= $infParcialId ?>">
                                            <input type="hidden" name="nuevo_estado" value="aprobado">
                                            <button type="submit" class="btn-accion btn-success-lab" style="padding: 2px 7px; font-size: 10px;" title="Aprobar Informe Parcial">
                                                <i class="fa-solid fa-check"></i> Aprobar
                                            </button>
                                        </form>
                                        <button type="button" class="btn-accion btn-warning-lab" style="padding: 2px 7px; font-size: 10px;" onclick="abrirModalDevolverInforme(<?= $infParcialId ?>, '<?= htmlspecialchars($infParcialNum, ENT_QUOTES) ?>')" title="Devolver con observaciones">
                                            <i class="fa-solid fa-rotate-left"></i> Devolver
                                        </button>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php else: ?>
                            <form method="POST" action="/Cycsa/publico/laboratorio/generar-informe-parcial" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id_hoja" value="<?= (int)$id_hoja ?>">
                                <input type="hidden" name="muestra_id" value="<?= $mId ?>">
                                <input type="hidden" name="plan_edad_id" value="<?= $planEdadId ?: (!empty($infParcial['id']) ? (int)$infParcial['id'] : 0) ?>">
                                <button type="submit" class="btn-accion btn-primary-lab" style="padding: 4px 9px; font-size: 11px; white-space: nowrap;" title="Emitir Informe Parcial Ciego según ISO/IEC 17025">
                                    <i class="fa-solid fa-file-circle-plus"></i> Sacar Parcial
                                </button>
                            </form>
                        <?php endif; ?>
                    <?php else: ?>
                        <span style="color:#94a3b8; font-style:italic; font-size:11.5px;">Pendiente rotura</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                    <div style="display: inline-flex; gap: 5px; align-items: center; justify-content: flex-end;">
                        <?php if ($ensayado): ?>
                            <span style="font-size: 11.5px; font-weight: 700; color: #047857;" title="Rompimiento registrado">
                                <i class="fa-solid fa-circle-check"></i> Ensayado
                            </span>
                        <?php elseif ($tieneProgramacion && $planEdadId > 0 && $espId > 0): ?>
                            <button type="button" class="btn-accion btn-primary-lab" onclick="abrirModalRegistrarRoturaLote(<?= $mId ?>, <?= $espId ?>, '<?= htmlspecialchars($codLab, ENT_QUOTES) ?>', <?= $planEdadId ?>, <?= (int)($edadDias ?: 28) ?>, '<?= htmlspecialchars((string)$fechaProg, ENT_QUOTES) ?>', 24)">
                                <i class="fa-solid fa-hammer"></i> Romper
                            </button>
                        <?php else: ?>
                            <button type="button" class="btn-accion btn-secondary-lab" disabled title="Debe definir la edad y fecha de rotura en la matriz técnica de Operaciones antes de ensayar.">
                                <i class="fa-solid fa-lock"></i> Romper
                            </button>
                        <?php endif; ?>

                        <?php if ($puedeReidentificar && $planId > 0 && !$ensayado): ?>
                            <button type="button" class="btn-accion btn-secondary-lab" onclick="abrirModalOverride(<?= $mId ?>, <?= $planId ?>, '<?= htmlspecialchars($codLab, ENT_QUOTES) ?>', <?= (int)($edadDias ?: 0) ?>, '<?= htmlspecialchars((string)$fechaProg, ENT_QUOTES) ?>')" title="Ajustar fecha o edad bajo autorización">
                                <i class="fa-solid fa-pen-to-square"></i>
                            </button>
                        <?php endif; ?>

                        <a href="/Cycsa/publico/laboratorio/detalle-muestra?muestra_id=<?= $mId ?>" class="btn-accion btn-secondary-lab" title="Detalle individual / auditoría de la muestra">
                            <i class="fa-solid fa-up-right-from-square"></i>
                        </a>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($muestrasLote)): ?>
            <tr>
                <td colspan="13" style="text-align: center; padding: 25px; color: #64748b;">
                    No se encontraron muestras registradas para este lote.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- 4. ROMPIMIENTO CONSOLIDADO E INFORMES OFICIALES DEL LOTE (RT-FM-13) -->
<div class="lab-box">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
        <div>
            <h4 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 800; color: var(--cycsa-azul); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-file-shield" style="color: #0284c7;"></i>
                Rompimiento Consolidado e Informes Oficiales del Lote
            </h4>
            <span style="font-size: 12.5px; color: #64748b;">
                Progreso del lote: <strong><?= $totalEnsayadas ?> de <?= $totalProbetas ?> probetas ensayadas</strong> &bull;
                <strong><?= $totalParcialesAprobados ?> informes parciales aprobados por Calidad</strong>
            </span>
        </div>
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <?php if ($puedeConsolidarTodoElLote && ($rolUsuario === 1 || $rolUsuario === 3)): ?>
                <form method="POST" action="/Cycsa/publico/laboratorio/generar-informe-consolidado-lote" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <input type="hidden" name="id_hoja" value="<?= (int)$id_hoja ?>">
                    <button type="submit" class="btn-accion btn-success-lab" style="font-size: 13px; padding: 7px 16px;">
                        <i class="fa-solid fa-award"></i> Emitir Informe Consolidado Final del Lote
                    </button>
                </form>
            <?php else: ?>
                <button type="button" class="btn-accion btn-secondary-lab" disabled style="opacity: 0.65; cursor: not-allowed; font-size: 12.5px; padding: 7px 14px;" title="Bloqueado: Para emitir el consolidado final, todas las probetas del lote deben estar ensayadas y sus informes parciales deben estar APROBADOS por Calidad (Roles 1 o 3).">
                    <i class="fa-solid fa-lock"></i> Consolidado Bloqueado (<?= ($totalProbetas - $totalEnsayadas) > 0 ? (($totalProbetas - $totalEnsayadas) . ' rotura(s) pendiente(s)') : (($totalProbetas - $totalParcialesAprobados) . ' parcial(es) pendiente(s) de aprobación') ?>)
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabla Unificada de Informes del Lote -->
    <div style="overflow-x: auto;">
        <table class="tabla-lab">
            <thead>
                <tr>
                    <th>Muestra / Probeta</th>
                    <th>Número de Informe</th>
                    <th>Tipo</th>
                    <th>Versión</th>
                    <th>Fecha Emisión</th>
                    <th>Estado Calidad</th>
                    <th>Aprobado Por</th>
                    <th>Observaciones / Motivo</th>
                    <th>Integridad SHA-256</th>
                    <th style="text-align: right; width: 140px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (($informesLote ?? []) as $inf): 
                    $esConsolidado = ($inf['tipo'] === 'consolidado');
                ?>
                <tr>
                    <td style="font-family: monospace; font-weight: 800; color: #0369a1; font-size: 12.5px;">
                        <?= htmlspecialchars((string)($inf['codigo_lab'] ?? 'LOTE'), ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td style="font-family: monospace; font-weight: 800; color: #0f172a; font-size: 13px;">
                        <?= htmlspecialchars((string)$inf['numero'], ENT_QUOTES, 'UTF-8') ?>
                    </td>
                    <td>
                        <?php if ($esConsolidado): ?>
                            <span class="badge-lab" style="background:#e0e7ff; color:#3730a3; border:1px solid #c7d2fe;">
                                <i class="fa-solid fa-award"></i> Consolidado Final
                            </span>
                        <?php else: ?>
                            <span class="badge-lab badge-programado">
                                <i class="fa-solid fa-file-lines"></i> Parcial Periódico
                            </span>
                        <?php endif; ?>
                    </td>
                    <td style="font-weight: 700;">v<?= (int)$inf['version'] ?></td>
                    <td style="font-size: 12px;"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($inf['created_at']))) ?></td>
                    <td>
                        <?php if ($inf['estado'] === 'aprobado'): ?>
                            <span class="badge-lab badge-aprobado"><i class="fa-solid fa-check"></i> Aprobado</span>
                        <?php elseif ($inf['estado'] === 'devuelto'): ?>
                            <span class="badge-lab badge-devuelto"><i class="fa-solid fa-xmark"></i> Devuelto</span>
                        <?php elseif ($inf['estado'] === 'anulado'): ?>
                            <span class="badge-lab badge-devuelto" style="background:#fee2e2; color:#991b1b;"><i class="fa-solid fa-ban"></i> Anulado (Sustituido)</span>
                        <?php else: ?>
                            <span class="badge-lab badge-revision"><i class="fa-solid fa-clock"></i> En Revisión</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-size: 12px;"><?= htmlspecialchars((string)($inf['aprobado_por_nombre'] ?: 'Gerencia de Calidad')) ?></td>
                    <td style="font-size: 11.5px; color: #64748b; max-width: 220px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">
                        <?= htmlspecialchars((string)($inf['motivo_reemision'] ? ('Sustituido: ' . $inf['motivo_reemision']) : ($inf['motivo_devolucion'] ?: ($inf['observaciones'] ?: '--')))) ?>
                    </td>
                    <td style="font-family: monospace; font-size: 11px; color: #64748b;">
                        <?php if (!empty($inf['sha256'])): ?>
                            <span title="<?= htmlspecialchars((string)$inf['sha256'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars(substr((string)$inf['sha256'], 0, 10)) ?>&hellip;
                            </span>
                        <?php else: ?>
                            <span style="color:#94a3b8;">--</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <?php if ($inf['estado'] === 'en_revision' && ($rolUsuario === 1 || $rolUsuario === 3)): ?>
                            <form method="POST" action="/Cycsa/publico/laboratorio/procesar-revision-informe" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id_hoja" value="<?= (int)$id_hoja ?>">
                                <input type="hidden" name="muestra_id" value="<?= (int)$inf['muestra_id'] ?>">
                                <input type="hidden" name="informe_id" value="<?= (int)$inf['id'] ?>">
                                <input type="hidden" name="nuevo_estado" value="aprobado">
                                <button type="submit" class="btn-accion btn-success-lab" style="padding: 4px 8px; font-size: 11px;" title="Aprobar Informe Oficial">
                                    <i class="fa-solid fa-thumbs-up"></i> Aprobar
                                </button>
                            </form>
                            <button type="button" class="btn-accion btn-warning-lab" style="padding: 4px 8px; font-size: 11px;" onclick="abrirModalDevolverInforme(<?= (int)$inf['id'] ?>, '<?= htmlspecialchars((string)$inf['numero'], ENT_QUOTES) ?>')" title="Devolver con observaciones técnicas">
                                <i class="fa-solid fa-rotate-left"></i> Devolver
                            </button>
                        <?php elseif ($inf['estado'] === 'aprobado' && ($rolUsuario === 1 || $rolUsuario === 3)): ?>
                            <button type="button" onclick="abrirModalReemitirInforme(<?= (int)$inf['id'] ?>, '<?= htmlspecialchars((string)$inf['numero'], ENT_QUOTES) ?>')" class="btn-accion btn-warning-lab" style="padding: 4px 8px; font-size: 11px;" title="Reemitir informe oficial (N+1) según ISO/IEC 17025">
                                <i class="fa-solid fa-arrows-rotate"></i> Reemitir
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($informesLote)): ?>
                <tr>
                    <td colspan="10" style="text-align: center; padding: 25px; color: #64748b; font-size: 13px;">
                        <i class="fa-solid fa-file-circle-xmark" style="font-size: 24px; color: #94a3b8; display: block; margin-bottom: 6px;"></i>
                        No se han emitido informes para este lote aún. Realice los rompimientos periódicos y emita los informes parciales para iniciar el expediente técnico.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- Modal Captura de Rotura Individual -->
<div id="modalRegistrarRotura" class="modal-lab">
    <div class="modal-lab-content">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: var(--cycsa-azul);">
                <i class="fa-solid fa-hammer"></i> Captura de Rotura: <span id="rot_esp_codigo" style="font-family: monospace; color:#0369a1;"></span>
            </h3>
            <button type="button" onclick="cerrarModalRegistrarRotura()" style="border: none; background: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <form method="POST" action="/Cycsa/publico/laboratorio/registrar-rotura">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_hoja" value="<?= (int)$id_hoja ?>">
            <input type="hidden" id="rot_muestra_id" name="muestra_id" value="">
            <input type="hidden" id="rot_especimen_id" name="especimen_id" value="">
            <input type="hidden" id="rot_plan_edad_id" name="plan_edad_id" value="">
            <input type="hidden" id="rot_edad_dias" name="edad_dias" value="">
            <input type="hidden" id="rot_fecha_prog" value="">
            <input type="hidden" id="rot_tol_horas" value="24">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Fecha y Hora Real:</label>
                    <input type="datetime-local" id="rot_fecha_real" name="fecha_real" class="form-control" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px;" value="<?= date('Y-m-d\TH:i') ?>" onchange="verificarToleranciaTiempoLote()" required>
                </div>
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Tipo de Falla (ASTM C39):</label>
                    <select name="tipo_falla" class="form-control" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px;">
                        <option value="Tipo 1 (Cono bien formado)">Tipo 1 (Cono bien formado)</option>
                        <option value="Tipo 2 (Cono y división)">Tipo 2 (Cono y división)</option>
                        <option value="Tipo 3 (División columnar)">Tipo 3 (División columnar)</option>
                        <option value="Tipo 4 (Fractura diagonal)">Tipo 4 (Fractura diagonal)</option>
                        <option value="Tipo 5 (Fractura en esquinas)">Tipo 5 (Fractura en esquinas)</option>
                    </select>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Carga de Rotura (lb): *</label>
                    <input type="text" inputmode="decimal" id="rot_carga" name="carga" class="form-control" style="width: 100%; height: 38px; font-weight: 800; font-family: monospace; font-size: 15px; border: 1.5px solid #0369a1; border-radius: 6px; padding: 4px 8px;" value="" placeholder="Ej. 65400" oninput="calcularEsfuerzoLote()" required>
                </div>
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Área Transversal (in²): *</label>
                    <input type="text" inputmode="decimal" id="rot_area" name="area" class="form-control" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px;" value="" placeholder="Ej. 28.274" oninput="calcularEsfuerzoLote()" required>
                </div>
            </div>

            <details style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 12px 16px; margin-bottom: 14px;">
                <summary style="font-size: 12.5px; font-weight: 700; color: #103487; cursor: pointer;">
                    <i class="fa-solid fa-calculator"></i> Dimensiones adicionales y Masa (RT-FM-61)
                </summary>
                <div style="margin-top: 12px; display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px;">
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569;">Diámetro 1 (cm):</label>
                        <input type="text" name="datos_crudos[diametro_1]" id="hc_d1" class="form-control" style="width: 100%; height: 34px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px;" oninput="recalcularAreaLote()">
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569;">Diámetro 2 (cm):</label>
                        <input type="text" name="datos_crudos[diametro_2]" id="hc_d2" class="form-control" style="width: 100%; height: 34px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px;" oninput="recalcularAreaLote()">
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569;">Longitud 1 (cm):</label>
                        <input type="text" name="datos_crudos[largo_1]" id="hc_l1" class="form-control" style="width: 100%; height: 34px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px;">
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569;">Longitud 2 (cm):</label>
                        <input type="text" name="datos_crudos[largo_2]" id="hc_l2" class="form-control" style="width: 100%; height: 34px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px;">
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569;">Masa (kg):</label>
                        <input type="text" name="datos_crudos[masa_kg]" id="hc_masa" class="form-control" style="width: 100%; height: 34px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px;">
                    </div>
                </div>
            </details>

            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px; margin-bottom: 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; text-align: center;">
                <div>
                    <span style="font-size: 11px; color: #64748b; font-weight: 700;">Esfuerzo Calculado PSI:</span>
                    <div id="calc_psi" style="font-size: 18px; font-weight: 800; font-family: monospace; color: #0369a1;">--</div>
                </div>
                <div>
                    <span style="font-size: 11px; color: #64748b; font-weight: 700;">Esfuerzo Calculado kg/cm²:</span>
                    <div id="calc_kgcm2" style="font-size: 18px; font-weight: 800; font-family: monospace; color: #047857;">--</div>
                </div>
            </div>

            <!-- Alerta y Justificación de Tolerancia Temporal (ASTM C39) -->
            <div id="box_justificacion_tolerancia_lote" style="display: none; background: #fff1f2; border: 1.5px solid #fecaca; border-radius: 8px; padding: 12px 14px; margin-bottom: 14px;">
                <div style="font-size: 11.5px; font-weight: 800; color: #991b1b; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> ENSAYO FUERA DE TOLERANCIA ASTM C39
                </div>
                <div style="font-size: 11.5px; color: #881337; margin-bottom: 6px; line-height: 1.35;">
                    La fecha/hora seleccionada excede la ventana admisible para esta edad (±<span id="rot_tol_hrs_txt">24</span> hrs). Ingrese una justificación técnica obligatoria.
                </div>
                <textarea id="rot_justificacion" name="justificacion" rows="2" class="form-control" style="width: 100%; box-sizing: border-box; resize: vertical; padding: 6px 8px; font-size: 12px; border: 1px solid #fca5a5; border-radius: 6px;" placeholder="Justificación técnica por ensayo extemporáneo..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-accion btn-secondary-lab" onclick="cerrarModalRegistrarRotura()">Cancelar</button>
                <button type="submit" class="btn-accion btn-primary-lab">
                    <i class="fa-solid fa-check"></i> Registrar Rotura
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Override de Plan de Rotura (Roles 1, 2 y 3) -->
<div id="modalOverridePlan" class="modal-lab">
    <div class="modal-lab-content" style="max-width: 520px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: var(--cycsa-azul);">
                <i class="fa-solid fa-pen-to-square"></i> Ajustar Programación: <span id="over_codigo" style="font-family: monospace; color:#0369a1;"></span>
            </h3>
            <button type="button" onclick="cerrarModalOverride()" style="border: none; background: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <form method="POST" action="/Cycsa/publico/laboratorio/modificar-plan-rotura">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_hoja" value="<?= (int)$id_hoja ?>">
            <input type="hidden" id="over_muestra_id" name="muestra_id" value="">
            <input type="hidden" id="over_plan_id" name="plan_id" value="">

            <div style="margin-bottom: 12px;">
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Nueva Edad (Días): *</label>
                <input type="number" min="1" max="365" id="over_edad" name="edad_dias" class="form-control" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px; font-weight: 700;" required>
            </div>

            <div style="margin-bottom: 12px;">
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Fecha Programada de Ensayo:</label>
                <input type="date" id="over_fecha" name="fecha_programada" class="form-control" style="width: 100%; height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 4px 8px;">
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Motivo Técnico de Modificación: * (Mín. 10 caracteres)</label>
                <textarea name="motivo_modificacion" id="over_motivo" class="form-control" rows="3" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px; font-size: 12.5px;" placeholder="Explique la causa técnica o solicitud del cliente para la traza de auditoría..." required minlength="10"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-accion btn-secondary-lab" onclick="cerrarModalOverride()">Cancelar</button>
                <button type="submit" class="btn-accion btn-primary-lab">
                    <i class="fa-solid fa-check"></i> Guardar Ajuste
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Captura de Rompimientos en Lote (Batch) -->
<div id="modalRoturaLoteBatch" class="modal-lab">
    <div class="modal-lab-content" style="max-width: 960px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: var(--cycsa-azul); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-hammer"></i> Captura de Rompimientos del Lote (<?= count($probetasPendientesLote) ?> Probetas Juntas)
            </h3>
            <button type="button" onclick="cerrarModalRoturaLoteBatch()" style="border: none; background: none; font-size: 24px; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <div style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 10px 14px; font-size: 12.5px; color: #1e40af; margin-bottom: 15px;">
            <i class="fa-solid fa-circle-info"></i> Ingrese la <strong>Carga de Rotura (lb)</strong> obtenida en prensa para cada una de las probetas del lote. Al guardar, los resultados se sincronizarán directamente con la Matriz Técnica.
        </div>

        <form method="POST" action="/Cycsa/publico/laboratorio/registrar-roturas-lote">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_hoja" value="<?= (int)$id_hoja ?>">

            <div style="overflow-x: auto; margin-bottom: 16px;">
                <table class="tabla-lab" style="font-size: 12.5px;">
                    <thead>
                        <tr>
                            <th>Probeta</th>
                            <th>Edad</th>
                            <th>Fecha Prog.</th>
                            <th style="width: 140px;">Carga Rotura (lb) *</th>
                            <th style="width: 105px;">Área (in²) *</th>
                            <th style="width: 110px;">Esfuerzo PSI</th>
                            <th style="width: 110px;">Esfuerzo kg/cm²</th>
                            <th>Tipo Falla</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($probetasPendientesLote as $bIdx => $pEsp): ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 800; color: #0369a1;">
                                <?= htmlspecialchars($pEsp['codigo_lab'], ENT_QUOTES, 'UTF-8') ?>
                                <input type="hidden" name="roturas[<?= $bIdx ?>][especimen_id]" value="<?= (int)$pEsp['especimen_id'] ?>">
                                <input type="hidden" name="roturas[<?= $bIdx ?>][plan_edad_id]" value="<?= (int)$pEsp['plan_edad_id'] ?>">
                                <input type="hidden" name="roturas[<?= $bIdx ?>][edad_dias]" value="<?= (int)$pEsp['edad_dias'] ?>">
                                <input type="hidden" name="roturas[<?= $bIdx ?>][fecha_real]" value="<?= date('Y-m-d H:i:s') ?>">
                            </td>
                            <td><strong style="color: #7c3aed;"><?= (int)$pEsp['edad_dias'] ?>d</strong></td>
                            <td><?= !empty($pEsp['fecha_programada']) ? date('d/m/Y', strtotime($pEsp['fecha_programada'])) : '--' ?></td>
                            <td>
                                <input type="number" step="0.1" min="1" name="roturas[<?= $bIdx ?>][carga]" class="form-control batch-carga" data-idx="<?= $bIdx ?>" style="width: 100%; height: 34px; font-weight: 800; font-family: monospace; padding: 4px 8px; border: 1.5px solid #0369a1; border-radius: 6px;" value="" placeholder="Ej. 65000" oninput="calcularBatchRow(<?= $bIdx ?>)" required>
                            </td>
                            <td>
                                <input type="number" step="0.001" min="0.01" name="roturas[<?= $bIdx ?>][area]" class="form-control batch-area" data-idx="<?= $bIdx ?>" style="width: 100%; height: 34px; padding: 4px 8px; border: 1px solid #cbd5e1; border-radius: 6px;" value="<?= !empty($pEsp['area']) ? number_format((float)$pEsp['area'], 3, '.', '') : '' ?>" placeholder="28.274" oninput="calcularBatchRow(<?= $bIdx ?>)" required>
                            </td>
                            <td>
                                <span id="batch_psi_<?= $bIdx ?>" style="font-family: monospace; font-weight: 700; color: #0369a1;">--</span>
                            </td>
                            <td>
                                <span id="batch_kgcm2_<?= $bIdx ?>" style="font-family: monospace; font-weight: 700; color: #047857;">--</span>
                            </td>
                            <td>
                                <select name="roturas[<?= $bIdx ?>][tipo_falla]" class="form-control" style="width: 100%; height: 34px; font-size: 11.5px; border-radius: 6px;">
                                    <option value="Tipo 1 (Cono bien formado)">Tipo 1 (Cono)</option>
                                    <option value="Tipo 2 (Cono y división)">Tipo 2 (Cono/div)</option>
                                    <option value="Tipo 3 (División columnar)">Tipo 3 (Columnar)</option>
                                    <option value="Tipo 4 (Fractura diagonal)">Tipo 4 (Diagonal)</option>
                                    <option value="Tipo 5 (Fractura en esquinas)">Tipo 5 (Esquinas)</option>
                                </select>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-accion btn-secondary-lab" onclick="cerrarModalRoturaLoteBatch()">Cancelar</button>
                <button type="submit" class="btn-accion btn-primary-lab" style="font-size: 13.5px; padding: 8px 20px;">
                    <i class="fa-solid fa-check-double"></i> Guardar Rompimientos del Lote
                </button>
            </div>
        </form>
    </div>
</div>

<script>
const MAPA_MATRIZ_LOTE = <?= json_encode($mapaMatrizJs, JSON_UNESCAPED_UNICODE) ?>;

function abrirModalRoturaLoteBatch() {
    document.querySelectorAll('.batch-carga').forEach(input => {
        const idx = input.getAttribute('data-idx');
        if (idx !== null) calcularBatchRow(idx);
    });
    document.getElementById('modalRoturaLoteBatch').style.display = 'block';
}

function cerrarModalRoturaLoteBatch() {
    document.getElementById('modalRoturaLoteBatch').style.display = 'none';
}

function calcularBatchRow(idx) {
    const inputCarga = document.querySelector(`.batch-carga[data-idx="${idx}"]`);
    const inputArea = document.querySelector(`.batch-area[data-idx="${idx}"]`);
    if (!inputCarga || !inputArea) return;
    const c = parseFloat(inputCarga.value || 0);
    const a = parseFloat(inputArea.value || 0);
    const psiSpan = document.getElementById(`batch_psi_${idx}`);
    const kgcm2Span = document.getElementById(`batch_kgcm2_${idx}`);
    if (c > 0 && a > 0) {
        const psi = c / a;
        const kgcm2 = psi * 0.070307;
        if (psiSpan) psiSpan.innerText = psi.toFixed(1) + ' PSI';
        if (kgcm2Span) kgcm2Span.innerText = kgcm2.toFixed(1) + ' kg/cm²';
    } else {
        if (psiSpan) psiSpan.innerText = '--';
        if (kgcm2Span) kgcm2Span.innerText = '--';
    }
}

function abrirModalRegistrarRoturaLote(muestraId, espId, codigoLab, planEdadId, edadDias, fechaProg, tolHoras) {
    document.getElementById('rot_muestra_id').value = muestraId;
    document.getElementById('rot_especimen_id').value = espId;
    document.getElementById('rot_esp_codigo').innerText = codigoLab;
    document.getElementById('rot_plan_edad_id').value = planEdadId;
    document.getElementById('rot_edad_dias').value = edadDias;
    document.getElementById('rot_fecha_prog').value = fechaProg;
    document.getElementById('rot_tol_horas').value = tolHoras;

    const dm = MAPA_MATRIZ_LOTE[muestraId] || {};
    // Cero precarga de carga en rotura individual: la carga es resultado del laboratorio
    document.getElementById('rot_carga').value = '';
    
    // Área por tamaño de cilindro si la matriz no la trae
    let areaVal = '';
    if (dm.area && parseFloat(dm.area) > 0) {
        areaVal = parseFloat(dm.area).toFixed(3);
    } else if (dm.tamano_cilindro && dm.tamano_cilindro.includes('4x8')) {
        areaVal = '12.566';
    } else if (dm.tamano_cilindro && dm.tamano_cilindro.includes('6x12')) {
        areaVal = '28.274';
    }
    document.getElementById('rot_area').value = areaVal;

    document.getElementById('hc_d1').value = (dm.diametro_1 && dm.diametro_1 > 0) ? dm.diametro_1 : '';
    document.getElementById('hc_d2').value = (dm.diametro_2 && dm.diametro_2 > 0) ? dm.diametro_2 : '';
    document.getElementById('hc_l1').value = (dm.largo_1 && dm.largo_1 > 0) ? dm.largo_1 : '';
    document.getElementById('hc_l2').value = (dm.largo_2 && dm.largo_2 > 0) ? dm.largo_2 : '';
    document.getElementById('hc_masa').value = (dm.masa_kg && dm.masa_kg > 0) ? dm.masa_kg : '';

    const justElem = document.getElementById('rot_justificacion');
    if (justElem) justElem.value = '';
    const tolTxt = document.getElementById('rot_tol_hrs_txt');
    if (tolTxt) tolTxt.innerText = tolHoras || 24;

    calcularEsfuerzoLote();
    verificarToleranciaTiempoLote();
    document.getElementById('modalRegistrarRotura').style.display = 'block';
}

function cerrarModalRegistrarRotura() {
    document.getElementById('modalRegistrarRotura').style.display = 'none';
}

function recalcularAreaLote() {
    const d1 = parseFloat(document.getElementById('hc_d1')?.value || 0);
    const d2 = parseFloat(document.getElementById('hc_d2')?.value || 0);
    if (d1 > 0 && d2 > 0) {
        const a1 = Math.PI * d1 * d1 / 4.0;
        const a2 = Math.PI * d2 * d2 / 4.0;
        const areaIn2 = ((a1 + a2) / 2.0) / 6.4516;
        document.getElementById('rot_area').value = areaIn2.toFixed(3);
    }
    calcularEsfuerzoLote();
}

function verificarToleranciaTiempoLote() {
    const fechaRealStr = document.getElementById('rot_fecha_real')?.value;
    const fechaProgStr = document.getElementById('rot_fecha_prog')?.value;
    const tolHoras = parseFloat(document.getElementById('rot_tol_horas')?.value || 24);

    const box = document.getElementById('box_justificacion_tolerancia_lote');
    const justArea = document.getElementById('rot_justificacion');
    if (!box || !justArea) return;

    if (!fechaRealStr || !fechaProgStr) {
        box.style.display = 'none';
        justArea.required = false;
        return;
    }

    const tReal = new Date(fechaRealStr).getTime();
    const tProg = new Date(fechaProgStr + 'T08:00:00').getTime();
    const difHoras = Math.abs(tReal - tProg) / (1000 * 3600);

    if (difHoras > tolHoras) {
        box.style.display = 'block';
        justArea.required = true;
    } else {
        box.style.display = 'none';
        justArea.required = false;
    }
}

function calcularEsfuerzoLote() {
    const carga = parseFloat(document.getElementById('rot_carga').value || 0);
    const area = parseFloat(document.getElementById('rot_area').value || 0);
    if (carga > 0 && area > 0) {
        const psi = carga / area;
        const kgcm2 = psi * 0.070307;
        document.getElementById('calc_psi').innerText = psi.toFixed(1) + ' PSI';
        document.getElementById('calc_kgcm2').innerText = kgcm2.toFixed(1) + ' kg/cm²';
    } else {
        document.getElementById('calc_psi').innerText = '--';
        document.getElementById('calc_kgcm2').innerText = '--';
    }
}

function abrirModalOverride(muestraId, planId, codigoLab, edadActual, fechaActual) {
    document.getElementById('over_muestra_id').value = muestraId;
    document.getElementById('over_plan_id').value = planId;
    document.getElementById('over_codigo').innerText = codigoLab;
    document.getElementById('over_edad').value = edadActual > 0 ? edadActual : '';
    document.getElementById('over_fecha').value = fechaActual || '';
    document.getElementById('over_motivo').value = '';
    document.getElementById('modalOverridePlan').style.display = 'block';
}

function cerrarModalOverride() {
    document.getElementById('modalOverridePlan').style.display = 'none';
}

function abrirModalDevolverInforme(informeId, numero) {
    const idElem = document.getElementById('dev_informe_id');
    const numElem = document.getElementById('dev_informe_numero');
    if (idElem) idElem.value = informeId;
    if (numElem) numElem.innerText = numero;
    const modal = document.getElementById('modalDevolverInforme');
    if (modal) modal.style.display = 'block';
}

function cerrarModalDevolverInforme() {
    const modal = document.getElementById('modalDevolverInforme');
    if (modal) modal.style.display = 'none';
}

function abrirModalReemitirInforme(informeId, numero) {
    const idElem = document.getElementById('reem_informe_id');
    const numElem = document.getElementById('reem_informe_numero');
    if (idElem) idElem.value = informeId;
    if (numElem) numElem.innerText = numero;
    const modal = document.getElementById('modalReemitirInforme');
    if (modal) modal.style.display = 'block';
}

function cerrarModalReemitirInforme() {
    const modal = document.getElementById('modalReemitirInforme');
    if (modal) modal.style.display = 'none';
}
</script>

<!-- Modal Devolver Informe Parcial (Calidad / Roles 1 y 3) -->
<div id="modalDevolverInforme" class="modal-lab">
    <div class="modal-lab-content" style="max-width: 480px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">
            <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: #991b1b;">
                <i class="fa-solid fa-rotate-left"></i> Devolver Informe a Laboratorio
            </h3>
            <button onclick="cerrarModalDevolverInforme()" style="border: none; background: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <form method="POST" action="/Cycsa/publico/laboratorio/procesar-revision-informe">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_hoja" value="<?= (int)$id_hoja ?>">
            <input type="hidden" id="dev_informe_id" name="informe_id" value="">
            <input type="hidden" name="nuevo_estado" value="devuelto">

            <div style="background: #fef2f2; border: 1px solid #fecaca; color: #991b1b; padding: 10px 14px; border-radius: 6px; font-size: 12.5px; margin-bottom: 12px;">
                <i class="fa-solid fa-circle-exclamation"></i> Devolución del informe <strong id="dev_informe_numero"></strong> para subsanación técnica en laboratorio.
            </div>

            <div style="margin-bottom: 15px;">
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Motivo de Devolución / Observación Técnica: *</label>
                <textarea name="motivo_devolucion" rows="3" class="form-control" style="width: 100%; box-sizing: border-box; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px; font-size: 12.5px;" placeholder="Especifique el motivo técnico del rechazo o corrección requerida..." required minlength="5"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-accion btn-secondary-lab" onclick="cerrarModalDevolverInforme()">Cancelar</button>
                <button type="submit" class="btn-accion btn-warning-lab">
                    <i class="fa-solid fa-paper-plane"></i> Registrar Devolución
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Reemitir Informe (Calidad / Roles 1 y 3 / N+1 ISO 17025) -->
<div id="modalReemitirInforme" class="modal-lab">
    <div class="modal-lab-content" style="max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">
            <h3 style="margin: 0; color: #1e293b; font-size: 16px; font-weight: 800;">
                <i class="fa-solid fa-arrows-rotate" style="color:#d97706;"></i> Reemitir Informe Oficial (ISO/IEC 17025)
            </h3>
            <button type="button" onclick="cerrarModalReemitirInforme()" style="border: none; background: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <form method="POST" action="/Cycsa/publico/laboratorio/reemitir-informe">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_hoja" value="<?= (int)$id_hoja ?>">
            <input type="hidden" id="reem_informe_id" name="informe_id" value="">

            <div style="background:#fffbeb; border:1px solid #fde68a; color:#92400e; padding:10px 14px; border-radius:6px; font-size:12.5px; margin-bottom:12px;">
                <i class="fa-solid fa-triangle-exclamation"></i> El informe actual <strong id="reem_informe_numero"></strong> quedará <strong>ANULADO</strong> y se generará una nueva versión que lo sustituye formalmente en el expediente.
            </div>

            <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 5px;">
                Motivo obligatorio de la reemisión: *
            </label>
            <textarea name="motivo_reemision" rows="3" class="form-control" style="width: 100%; box-sizing: border-box; resize: vertical; padding: 8px 10px; font-size: 13px; border: 1px solid #cbd5e1; border-radius: 6px;" placeholder="Detalle la justificación técnica o administrativa..." required minlength="5"></textarea>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px;">
                <button type="button" class="btn-accion btn-secondary-lab" onclick="cerrarModalReemitirInforme()">Cancelar</button>
                <button type="submit" class="btn-accion btn-warning-lab">
                    <i class="fa-solid fa-arrows-rotate"></i> Reemitir y Sustituir
                </button>
            </div>
        </form>
    </div>
</div>
