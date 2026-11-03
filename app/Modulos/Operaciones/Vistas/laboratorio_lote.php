<?php
/**
 * Vista de Laboratorio por Lote (Hoja RT-FM-13) - ISO/IEC 17025
 * Muestra todas las N muestras de la hoja y sus réplicas CR en la misma secuencia que la matriz,
 * permite confirmar los días de rompimiento en lote y precarga los datos técnicos desde la matriz.
 */
$totalMuestrasLote = count($muestrasLote ?? []);
$totalReplicasCr = 0;
$mapaMatrizJs = [];

foreach (($muestrasLote ?? []) as $itemLote) {
    $mRow = $itemLote['muestra'] ?? [];
    $mId = (int)($mRow['muestra_id'] ?? 0);
    if (!empty($mRow['is_qa_qc']) || ($mRow['replica_codigo'] ?? '') === '-CR' || str_ends_with((string)($mRow['codigo_lab'] ?? ''), '-CR')) {
        $totalReplicasCr++;
    }
    $mapaMatrizJs[$mId] = $itemLote['datos_matriz'] ?? [];
}

$numRegHoja = !empty($hoja['numero_registro']) ? $hoja['numero_registro'] : ('#' . (int)($id_hoja ?? 0));
$ensayoTitulo = $detalleCotizacion['descripcion_item']
    ?? ($detalleCotizacion['nombre_comercial']
    ?? ($muestrasLote[0]['muestra']['plantilla_nombre'] ?? 'Ensayo de Laboratorio'));
$normaTitulo = $detalleCotizacion['norma_astm']
    ?? ($muestrasLote[0]['muestra']['norma_referencia'] ?? 'ASTM C39');
?>
<style>
    :root {
        --cycsa-azul: #103487;
        --cycsa-azul-hover: #0c2766;
        --cycsa-rojo: #e31837;
    }
    .lab-box { background: white; padding: 22px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 22px; border: 1px solid #e2e8f0; }
    .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 14px; }
    @media (max-width: 900px) { .grid-4 { grid-template-columns: 1fr 1fr; } }
    @media (max-width: 600px) { .grid-4 { grid-template-columns: 1fr; } }
    .tabla-lab { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 8px; }
    .tabla-lab th { background: #f8fafc; color: #475569; font-weight: 700; padding: 9px 12px; text-align: left; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 11px; }
    .tabla-lab td { padding: 9px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
    .badge-lab { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .badge-ensayado { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .badge-programado { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .badge-curado { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
    .badge-replica { background: #fef3c7; color: #b45309; border: 1px solid #f59e0b; }
    .btn-accion { border: none; background: none; cursor: pointer; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
    .btn-primary-lab { background: #103487; color: white; border: 1px solid #103487; }
    .btn-primary-lab:hover { background: #0c2766; color: white; }
    .btn-success-lab { background: #10b981; color: white; border: 1px solid #059669; }
    .btn-secondary-lab { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
    .btn-secondary-lab:hover { background: #e2e8f0; }
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
    <div style="display: flex; gap: 10px;">
        <a href="/Cycsa/publico/laboratorio?tab=bandeja" class="btn-accion btn-secondary-lab">
            <i class="fa-solid fa-arrow-left"></i> Volver a Bandeja Anónima
        </a>
    </div>
</div>

<!-- Alerta de Imparcialidad ISO 17025 -->
<div style="background-color: #fefce8; border: 1px solid #fef08a; color: #854d0e; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-size: 13px; font-weight: 500;">
    <i class="fa-solid fa-eye-slash" style="font-size: 18px; color: #ca8a04;"></i>
    <div>
        <strong>RESGUARDO DE IMPARCIALIDAD (ISO 17025 Cláusulas 4.1 y 4.2):</strong> Las muestras del lote se listan en la misma secuencia de la matriz técnica, tomando Edad, Temperatura y Área desde Operaciones sin exponer datos comerciales ni de clientes.
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

<!-- 1. CONFIRMACIÓN DE DÍAS DE ROMPIMIENTO EN LOTE (BATCH) -->
<div class="lab-box" style="background: #f8fafc; border: 1.5px solid #cbd5e1;">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; border-bottom: 1px solid #cbd5e1; padding-bottom: 10px; margin-bottom: 14px;">
        <div>
            <h4 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 800; color: var(--cycsa-azul);">
                <i class="fa-solid fa-calendar-check"></i> Días de Rompimiento del Lote (Programación Batch)
            </h4>
            <span style="font-size: 12px; color: #64748b;">
                Defina o confirme las edades de rompimiento para todas las muestras del lote de una sola vez. (También puede ajustar una muestra individualmente desde su botón "Detalle Individual").
            </span>
        </div>
    </div>

    <form method="POST" action="/Cycsa/publico/laboratorio/guardar-plan-rotura-lote">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars((string)($_SESSION['csrf_token'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id_hoja" value="<?= (int)$id_hoja ?>">

        <div id="contenedor_edades_lote" style="display: flex; flex-wrap: wrap; gap: 12px; align-items: flex-end; margin-bottom: 12px;">
            <?php foreach (($edadesSugeridasLote ?? [['edad_dias' => 7, 'cantidad' => 1], ['edad_dias' => 14, 'cantidad' => 1], ['edad_dias' => 28, 'cantidad' => 1]]) as $edSug): 
                $dVal = (int)($edSug['edad_dias'] ?? 28);
                $cVal = max(1, (int)($edSug['cantidad'] ?? 1));
            ?>
            <div class="fila-edad-lote" style="display: flex; gap: 8px; align-items: flex-end; background: white; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1;">
                <div>
                    <label style="font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">Edad (Días):</label>
                    <input type="number" name="edad_dias[]" min="1" max="365" value="<?= $dVal ?>" style="width: 90px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 700;" required>
                </div>
                <div>
                    <label style="font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">Probetas / Muestra:</label>
                    <input type="number" name="cantidad[]" min="1" max="20" value="<?= $cVal ?>" style="width: 90px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
                </div>
                <button type="button" class="btn-accion btn-secondary-lab" onclick="eliminarEdadLote(this)" style="color: #ef4444; padding: 6px 8px;" title="Quitar edad">
                    <i class="fa-solid fa-trash"></i>
                </button>
            </div>
            <?php endforeach; ?>
        </div>

        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
            <button type="button" class="btn-accion btn-secondary-lab" onclick="agregarEdadLote()">
                <i class="fa-solid fa-plus"></i> Agregar Edad
            </button>
            <button type="submit" class="btn-accion btn-primary-lab">
                <i class="fa-solid fa-check-double"></i> Confirmar y Aplicar Días de Rompimiento al Lote (<?= $totalMuestrasLote ?> muestras)
            </button>
        </div>
    </form>
</div>

<!-- 2. LISTADO SECUENCIAL DE MUESTRAS DEL LOTE (MISMA SECUENCIA QUE LA MATRIZ) -->
<?php foreach (($muestrasLote ?? []) as $idxSec => $item): 
    $m = $item['muestra'] ?? [];
    $especimenes = $item['especimenes'] ?? [];
    $edades = $item['edades'] ?? [];
    $dm = $item['datos_matriz'] ?? [];
    $esReplica = !empty($m['is_qa_qc']) || ($m['replica_codigo'] ?? '') === '-CR' || str_ends_with((string)($m['codigo_lab'] ?? ''), '-CR');
    $tempMuestra = $dm['temperatura_c'] ?? ($m['temperatura_c'] ?? null);
    $areaMuestra = (!empty($dm['area']) && (float)$dm['area'] > 0) ? (float)$dm['area'] : 28.274;
    $edadMatriz = $dm['edad_dias'] ?? null;
?>
<div class="lab-box" style="<?= $esReplica ? 'border-left: 4px solid #f59e0b; background: #fffdf7;' : 'border-left: 4px solid #103487;' ?>">
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 12px;">
        <div>
            <span style="font-size: 11px; font-weight: 800; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; margin-right: 6px;">
                #<?= $idxSec + 1 ?> en Matriz
            </span>
            <strong style="font-family: monospace; font-size: 16px; color: #0369a1;">
                <i class="fa-solid fa-cube"></i> <?= htmlspecialchars((string)($m['codigo_lab'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
            </strong>
            <?php if ($esReplica): ?>
                <span class="badge-lab badge-replica" style="margin-left: 6px;">
                    <i class="fa-solid fa-clone"></i> Réplica Control de Calidad (-CR)
                </span>
            <?php endif; ?>
            <?php if (!empty($m['codigo_campo']) && $m['codigo_campo'] !== $m['codigo_lab']): ?>
                <span style="font-size: 12px; color: #64748b; margin-left: 8px;">Campo: <strong><?= htmlspecialchars((string)$m['codigo_campo'], ENT_QUOTES, 'UTF-8') ?></strong></span>
            <?php endif; ?>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <a href="/Cycsa/publico/laboratorio/detalle-muestra?muestra_id=<?= (int)$m['muestra_id'] ?>" class="btn-accion btn-secondary-lab" title="Abrir hoja de trabajo individual / override de esta muestra">
                <i class="fa-solid fa-sliders"></i> Detalle Individual / Override
            </a>
        </div>
    </div>

    <!-- Barra de Datos Técnicos desde la Matriz (Fuente de Verdad) -->
    <div class="grid-4" style="background: #f8fafc; padding: 10px 14px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 12px; font-size: 12.5px;">
        <div>
            <span style="font-size: 10.5px; color: #64748b; font-weight: 700; text-transform: uppercase;">Material / Moldeo:</span><br>
            <strong><?= htmlspecialchars((string)($m['material_descripcion'] ?? 'Concreto'), ENT_QUOTES, 'UTF-8') ?></strong>
            (<?= !empty($m['fecha_moldeo']) ? htmlspecialchars(date('d/m/Y', strtotime((string)$m['fecha_moldeo']))) : '--' ?>)
        </div>
        <div>
            <span style="font-size: 10.5px; color: #0369a1; font-weight: 700; text-transform: uppercase;"><i class="fa-solid fa-calendar-day"></i> Edad (Matriz / Plan):</span><br>
            <strong style="color: #7c3aed;">
                <?php if ($edadMatriz !== null): ?>
                    <?= (int)$edadMatriz ?> días (Matriz)
                <?php elseif (!empty($edades)): ?>
                    <?= implode(', ', array_map(fn($e) => (int)$e['edad_dias'] . 'd', $edades)) ?>
                <?php else: ?>
                    Pendiente
                <?php endif; ?>
            </strong>
        </div>
        <div>
            <span style="font-size: 10.5px; color: #0369a1; font-weight: 700; text-transform: uppercase;"><i class="fa-solid fa-temperature-half"></i> Temperatura (Matriz):</span><br>
            <strong><?= ($tempMuestra !== null && $tempMuestra !== '') ? (htmlspecialchars((string)$tempMuestra) . ' °C') : '--' ?></strong>
        </div>
        <div>
            <span style="font-size: 10.5px; color: #0369a1; font-weight: 700; text-transform: uppercase;"><i class="fa-solid fa-vector-square"></i> Área Transversal (Matriz):</span><br>
            <strong style="font-family: monospace;"><?= number_format($areaMuestra, 3) ?> in²</strong>
            <?php if (!empty($dm['diametro_1']) || !empty($dm['diametro_2'])): ?>
                <span style="font-size: 11px; color: #64748b;">(D: <?= htmlspecialchars((string)($dm['diametro_1'] ?? '')) ?>/<?= htmlspecialchars((string)($dm['diametro_2'] ?? '')) ?> cm)</span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Tabla de Especímenes de la Muestra -->
    <table class="tabla-lab">
        <thead>
            <tr>
                <th>Código Probeta</th>
                <th>Edad Asignada</th>
                <th>Fecha Prog.</th>
                <th>Carga (lb)</th>
                <th>Área (in²)</th>
                <th>Esfuerzo PSI</th>
                <th>Esfuerzo kg/cm²</th>
                <th>Estado</th>
                <th style="text-align: right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($especimenes as $esp): 
                $ensayado = (($esp['estado'] ?? '') === 'ensayado');
            ?>
            <tr>
                <td style="font-family: monospace; font-weight: 800; color: #0369a1;">
                    <?= htmlspecialchars((string)$esp['codigo_lab'], ENT_QUOTES, 'UTF-8') ?>
                </td>
                <td>
                    <?php if (!empty($esp['edad_dias'])): ?>
                        <strong style="color: #7c3aed;"><?= (int)$esp['edad_dias'] ?> días</strong>
                    <?php elseif ($edadMatriz !== null): ?>
                        <strong style="color: #7c3aed;"><?= (int)$edadMatriz ?> días (Matriz)</strong>
                    <?php else: ?>
                        <span style="color: #94a3b8; font-style: italic;">Sin edad</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= !empty($esp['fecha_programada']) ? htmlspecialchars(date('d/m/Y', strtotime((string)$esp['fecha_programada']))) : '--' ?>
                </td>
                <td>
                    <?= $ensayado ? '<strong>' . number_format((float)$esp['carga'], 1) . '</strong>' : (!empty($dm['carga']) ? '<span style="color:#0369a1;" title="Precargado en Matriz">' . number_format((float)$dm['carga'], 1) . ' (Matriz)</span>' : '--') ?>
                </td>
                <td>
                    <?= $ensayado ? number_format((float)$esp['area'], 3) : number_format($areaMuestra, 3) ?>
                </td>
                <td style="font-family: monospace; font-weight: 700;">
                    <?= $ensayado ? number_format((float)$esp['esfuerzo_psi'], 1) : '--' ?>
                </td>
                <td style="font-family: monospace; font-weight: 700; color: #047857;">
                    <?= $ensayado ? number_format((float)$esp['esfuerzo_kgcm2'], 1) : '--' ?>
                </td>
                <td>
                    <?php if ($ensayado): ?>
                        <span class="badge-lab badge-ensayado"><i class="fa-solid fa-check"></i> Ensayado</span>
                    <?php elseif (($esp['estado'] ?? '') === 'programado'): ?>
                        <span class="badge-lab badge-programado">Programado</span>
                    <?php else: ?>
                        <span class="badge-lab badge-curado">En Curado</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                    <?php if (!$ensayado && !empty($esp['plan_edad_id'])): ?>
                        <button type="button" class="btn-accion btn-primary-lab" onclick="abrirModalRegistrarRoturaLote(<?= (int)$m['muestra_id'] ?>, <?= (int)$esp['id'] ?>, '<?= htmlspecialchars((string)$esp['codigo_lab'], ENT_QUOTES) ?>', <?= (int)$esp['plan_edad_id'] ?>, <?= (int)$esp['edad_dias'] ?>, '<?= htmlspecialchars((string)$esp['fecha_programada'], ENT_QUOTES) ?>', <?= (int)($esp['tolerancia_horas'] ?? 24) ?>)">
                            <i class="fa-solid fa-hammer"></i> Capturar Rotura
                        </button>
                    <?php endif; ?>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($especimenes)): ?>
            <tr>
                <td colspan="9" style="text-align: center; padding: 15px; color: #64748b;">
                    Sin especímenes generados aún. Confirme arriba los <strong>Días de Rompimiento del Lote</strong> para generar y programar las probetas automáticamente.
                </td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>
<?php endforeach; ?>

<!-- Modal Captura de Rotura (Precargado con Fuente de Verdad de la Matriz por Muestra) -->
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
            <input type="hidden" id="rot_muestra_id" name="muestra_id" value="">
            <input type="hidden" id="rot_especimen_id" name="especimen_id" value="">
            <input type="hidden" id="rot_plan_edad_id" name="plan_edad_id" value="">
            <input type="hidden" id="rot_fecha_prog" value="">
            <input type="hidden" id="rot_tol_horas" value="24">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Fecha y Hora Real:</label>
                    <input type="datetime-local" id="rot_fecha_real" name="fecha_real" class="form-control" style="width: 100%; height: 38px;" value="<?= date('Y-m-d\TH:i') ?>" required>
                </div>
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Tipo de Falla (ASTM C39):</label>
                    <select name="tipo_falla" class="form-control" style="width: 100%; height: 38px;">
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
                    <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Carga de Rotura (lb):</label>
                    <input type="text" inputmode="decimal" id="rot_carga" name="carga" class="form-control" style="width: 100%; height: 38px; font-weight: 700;" oninput="calcularEsfuerzoLote()" required>
                </div>
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Área Transversal (in²) [Desde Matriz]:</label>
                    <input type="text" inputmode="decimal" id="rot_area" name="area" class="form-control" style="width: 100%; height: 38px;" value="28.274" oninput="calcularEsfuerzoLote()" required>
                </div>
            </div>

            <details style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 8px; padding: 12px 16px; margin-bottom: 14px;">
                <summary style="font-size: 12.5px; font-weight: 700; color: #103487; cursor: pointer;">
                    <i class="fa-solid fa-calculator"></i> Hoja de Cálculo RT-FM-61 (Dimensiones, Masa y Prensa precargadas desde Matriz)
                </summary>
                <div style="margin-top: 12px; display: grid; grid-template-columns: repeat(auto-fit, minmax(160px, 1fr)); gap: 10px;">
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569;">Diámetro 1 (cm):</label>
                        <input type="text" name="datos_crudos[diametro_1]" id="hc_d1" class="form-control" style="width: 100%;">
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569;">Diámetro 2 (cm):</label>
                        <input type="text" name="datos_crudos[diametro_2]" id="hc_d2" class="form-control" style="width: 100%;">
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569;">Longitud 1 (cm):</label>
                        <input type="text" name="datos_crudos[largo_1]" id="hc_l1" class="form-control" style="width: 100%;">
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569;">Longitud 2 (cm):</label>
                        <input type="text" name="datos_crudos[largo_2]" id="hc_l2" class="form-control" style="width: 100%;">
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569;">Masa (kg):</label>
                        <input type="text" name="datos_crudos[masa_kg]" id="hc_masa" class="form-control" style="width: 100%;">
                    </div>
                </div>
            </details>

            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px; margin-bottom: 14px; display: grid; grid-template-columns: 1fr 1fr; gap: 10px; text-align: center;">
                <div>
                    <span style="font-size: 11px; color: #64748b; font-weight: 700;">Esfuerzo PSI:</span>
                    <div id="calc_psi" style="font-size: 18px; font-weight: 800; font-family: monospace; color: #0369a1;">--</div>
                </div>
                <div>
                    <span style="font-size: 11px; color: #64748b; font-weight: 700;">Esfuerzo kg/cm²:</span>
                    <div id="calc_kgcm2" style="font-size: 18px; font-weight: 800; font-family: monospace; color: #047857;">--</div>
                </div>
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

<script>
const MAPA_MATRIZ_LOTE = <?= json_encode($mapaMatrizJs, JSON_UNESCAPED_UNICODE) ?>;

function agregarEdadLote() {
    const cont = document.getElementById('contenedor_edades_lote');
    const div = document.createElement('div');
    div.className = 'fila-edad-lote';
    div.style = 'display: flex; gap: 8px; align-items: flex-end; background: white; padding: 8px 12px; border-radius: 8px; border: 1px solid #cbd5e1;';
    div.innerHTML = `
        <div>
            <label style="font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">Edad (Días):</label>
            <input type="number" name="edad_dias[]" min="1" max="365" value="28" style="width: 90px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px; font-weight: 700;" required>
        </div>
        <div>
            <label style="font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">Probetas / Muestra:</label>
            <input type="number" name="cantidad[]" min="1" max="20" value="1" style="width: 90px; padding: 5px 8px; border: 1px solid #cbd5e1; border-radius: 6px;" required>
        </div>
        <button type="button" class="btn-accion btn-secondary-lab" onclick="eliminarEdadLote(this)" style="color: #ef4444; padding: 6px 8px;">
            <i class="fa-solid fa-trash"></i>
        </button>
    `;
    cont.appendChild(div);
}

function eliminarEdadLote(btn) {
    const filas = document.querySelectorAll('.fila-edad-lote');
    if (filas.length <= 1) return;
    const f = btn.closest('.fila-edad-lote');
    if (f) f.remove();
}

function abrirModalRegistrarRoturaLote(muestraId, espId, codigoLab, planEdadId, edadDias, fechaProg, tolHoras) {
    document.getElementById('rot_muestra_id').value = muestraId;
    document.getElementById('rot_especimen_id').value = espId;
    document.getElementById('rot_esp_codigo').innerText = codigoLab;
    document.getElementById('rot_plan_edad_id').value = planEdadId;
    document.getElementById('rot_fecha_prog').value = fechaProg;
    document.getElementById('rot_tol_horas').value = tolHoras;

    const dm = MAPA_MATRIZ_LOTE[muestraId] || {};
    document.getElementById('rot_carga').value = (dm.carga && dm.carga > 0) ? dm.carga : '';
    document.getElementById('rot_area').value = (dm.area && dm.area > 0) ? dm.area : '28.274';
    document.getElementById('hc_d1').value = (dm.diametro_1 && dm.diametro_1 > 0) ? dm.diametro_1 : '';
    document.getElementById('hc_d2').value = (dm.diametro_2 && dm.diametro_2 > 0) ? dm.diametro_2 : '';
    document.getElementById('hc_l1').value = (dm.largo_1 && dm.largo_1 > 0) ? dm.largo_1 : '';
    document.getElementById('hc_l2').value = (dm.largo_2 && dm.largo_2 > 0) ? dm.largo_2 : '';
    document.getElementById('hc_masa').value = (dm.masa_kg && dm.masa_kg > 0) ? dm.masa_kg : '';

    calcularEsfuerzoLote();
    document.getElementById('modalRegistrarRotura').style.display = 'block';
}

function cerrarModalRegistrarRotura() {
    document.getElementById('modalRegistrarRotura').style.display = 'none';
}

function calcularEsfuerzoLote() {
    const carga = parseFloat(document.getElementById('rot_carga').value || 0);
    const area = parseFloat(document.getElementById('rot_area').value || 28.274);
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
</script>
