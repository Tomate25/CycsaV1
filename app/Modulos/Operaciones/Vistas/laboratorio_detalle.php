<?php
// Laboratorio Detalle Muestra - Programa de Roturas Periódicas e Imparcialidad ISO 17025
use Cycsa\Modulos\Operaciones\Servicios\ConfigCalculoLabServicio;

$divisorKgCm2Lab = ConfigCalculoLabServicio::obtenerFloat('cilindros_divisor_kgcm2', 14.2029);
$redondearPsi10Lab = ConfigCalculoLabServicio::obtenerBool('cilindros_redondear_psi_10', true);
$redondearPv10Lab = ConfigCalculoLabServicio::obtenerBool('cilindros_redondear_pv_10', true);

$totalEspecimenes = count($especimenes ?? []);
$totalEnsayados = 0;
foreach (($especimenes ?? []) as $esp) {
    if ($esp['estado'] === 'ensayado') {
        $totalEnsayados++;
    }
}
$planActivo = !empty($plan);
$puedeEditarPlan = ($totalEnsayados === 0);

$edadesAstmEstandarDet = [1, 3, 7, 14, 28, 56, 90];
$resolverToleranciaAstmRealDet = static function (int $edadDias, $tolRaw) use ($edadesAstmEstandarDet): float {
    $edadEfectiva = $edadDias > 0 ? $edadDias : 28;
    $tolFloat = is_numeric($tolRaw) ? (float)$tolRaw : 0.0;
    if ($tolFloat <= 0.0 || (abs($tolFloat - 24.0) < 0.01 && in_array($edadEfectiva, $edadesAstmEstandarDet, true))) {
        return ConfigCalculoLabServicio::obtenerToleranciaHorasAstmC39($edadEfectiva);
    }
    return $tolFloat;
};

if (isset($datosMatriz) && is_array($datosMatriz)) {
    $d1MatDet = (!empty($datosMatriz['diametro_1']) && (float)$datosMatriz['diametro_1'] > 0) ? (float)$datosMatriz['diametro_1'] : 0.0;
    $d2MatDet = (!empty($datosMatriz['diametro_2']) && (float)$datosMatriz['diametro_2'] > 0) ? (float)$datosMatriz['diametro_2'] : 0.0;
    if ($d1MatDet <= 0.0 || $d2MatDet <= 0.0) {
        if ($totalEnsayados === 0 || in_array(rtrim(rtrim(number_format((float)($datosMatriz['area'] ?? 0), 4, '.', ''), '0'), '.'), ['14.137', '14.1372', '28.274', '28.2743', '12.566', '12.5664'], true)) {
            $datosMatriz['area'] = '';
        }
    } else {
        $datosMatriz['area'] = round(ConfigCalculoLabServicio::calcularAreaCilindroIn2($d1MatDet, $d2MatDet), 3);
    }
}
?>
<style>
    :root {
        --cycsa-azul: #103487;
        --cycsa-azul-hover: #0c2766;
        --cycsa-rojo: #e31837;
        --color-success: #10b981;
        --color-warning: #f59e0b;
        --color-danger: #ef4444;
        --color-slate-50: #f8fafc;
        --color-slate-100: #f1f5f9;
        --color-slate-200: #e2e8f0;
        --color-slate-300: #cbd5e1;
        --color-slate-600: #475569;
        --color-slate-700: #334155;
        --color-slate-800: #1e293b;
    }

    .lab-box { background: white; padding: 22px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 22px; border: 1px solid var(--color-slate-200); }
    .grid-4 { display: grid; grid-template-columns: repeat(4, 1fr); gap: 16px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 16px; }
    @media (max-width: 900px) {
        .grid-4, .grid-3 { grid-template-columns: 1fr 1fr; }
    }
    @media (max-width: 600px) {
        .grid-4, .grid-3 { grid-template-columns: 1fr; }
    }

    .tabla-lab { width: 100%; border-collapse: collapse; font-size: 13px; margin-top: 10px; }
    .tabla-lab th { background: #f8fafc; color: #475569; font-weight: 700; padding: 10px 12px; text-align: left; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
    .tabla-lab td { padding: 10px 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
    .tabla-lab tbody tr:hover { background-color: #f8fafc; }

    .badge-lab { display: inline-flex; align-items: center; gap: 4px; padding: 3px 8px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
    .badge-ensayado { background: #dcfce7; color: #166534; border: 1px solid #bbf7d0; }
    .badge-programado { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .badge-curado { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
    .badge-testigo { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .badge-aprobado { background: #dcfce7; color: #166534; }
    .badge-revision { background: #fef3c7; color: #92400e; }
    .badge-devuelto { background: #fee2e2; color: #991b1b; }

    .btn-accion { border: none; background: none; cursor: pointer; padding: 6px 12px; border-radius: 6px; font-size: 12px; font-weight: 700; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; }
    .btn-primary-lab { background: #103487; color: white; border: 1px solid #103487; }
    .btn-primary-lab:hover { background: #0c2766; }
    .btn-success-lab { background: #10b981; color: white; border: 1px solid #059669; }
    .btn-success-lab:hover { background: #059669; }
    .btn-warning-lab { background: #f59e0b; color: white; border: 1px solid #d97706; }
    .btn-secondary-lab { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
    .btn-secondary-lab:hover { background: #e2e8f0; }

    .modal-lab { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .modal-lab-content { background: white; margin: 3% auto; padding: 25px 30px; border-radius: 12px; width: 92%; max-width: 680px; max-height: 90vh; overflow-y: auto; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.15); border: 1px solid #e2e8f0; box-sizing: border-box; }
</style>

<!-- Encabezado Ciego -->
<div class="header-flex" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800;">Hoja de Trabajo Ciega &bull; Ensayo de CompresiÃ³n</h2>
        <p style="color: #64748b; margin-top: 4px; font-size: 13.5px;">
            CÃ³digo de Laboratorio: <strong style="color: #0369a1; font-family: monospace; font-size: 15px;"><?= htmlspecialchars($muestra['codigo_lab']) ?></strong>
            <?php if (!empty($muestra['codigo_campo'])): ?>
                | Campo: <strong style="color: #475569; font-family: monospace;"><?= htmlspecialchars($muestra['codigo_campo']) ?></strong>
            <?php endif; ?>
        </p>
    </div>
    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
        <a href="/Cycsa/publico/laboratorio/imprimir-solicitud?muestra_id=<?= (int)$muestra['muestra_id'] ?>" target="_blank" class="btn-accion btn-secondary-lab" title="Ver e imprimir Hoja de Solicitud de Laboratorio CYCSA-RT-FM-60">
            <i class="fa-solid fa-print text-primary"></i> Hoja RT-FM-60
        </a>
        <?php if (!empty($muestra['id_hoja'])): ?>
            <a href="/Cycsa/publico/laboratorio/detalle-lote?id_hoja=<?= (int)$muestra['id_hoja'] ?>" class="btn-accion btn-primary-lab">
                <i class="fa-solid fa-layer-group"></i> Ver Lote Completo (<?= (int)($muestra['total_muestras_lote'] ?? 1) ?>)
            </a>
        <?php endif; ?>
        <a href="/Cycsa/publico/laboratorio" class="btn-accion btn-secondary-lab"><i class="fa-solid fa-arrow-left"></i> Volver a Bandeja</a>
        <?php if ($puedeReidentificar): ?>
            <button type="button" class="btn-accion btn-warning-lab" onclick="abrirModalReidentificar('<?= htmlspecialchars($muestra['codigo_lab']) ?>', <?= (int)$muestra['muestra_id'] ?>)">
                <i class="fa-solid fa-shield-halved"></i> Reidentificar (AuditorÃ­a)
            </button>
        <?php endif; ?>
    </div>
</div>

<!-- Notificaciones Flash -->
<?php if (!empty($exito)): ?>
    <div style="background-color: #f0fdf4; color: #166534; padding: 12px 16px; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 15px; font-size: 13.5px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div style="background-color: #fef2f2; color: #991b1b; padding: 12px 16px; border-radius: 8px; border: 1px solid #fecaca; margin-bottom: 15px; font-size: 13.5px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<!-- 1. ESPECIFICACIONES TÃCNICAS DEL LOTE -->
<div class="lab-box">
    <h4 style="font-family:'Outfit'; font-size:15px; font-weight:700; color:var(--cycsa-azul); border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 15px;">
        <i class="fa-solid fa-cube"></i> Datos TÃ©cnicos de la Muestra
    </h4>
    <div class="grid-4">
        <div>
            <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Material / Lote:</span><br>
            <strong style="color:#1e293b;"><?= htmlspecialchars($muestra['material_descripcion']) ?></strong>
        </div>
        <div>
            <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Fecha de Moldeo / Toma:</span><br>
            <strong style="color:#1e293b;"><?= htmlspecialchars(date('d/m/Y', strtotime($muestra['fecha_moldeo']))) ?></strong>
        </div>
        <div>
            <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Resistencia DiseÃ±o (f'c):</span><br>
            <strong style="color:#0369a1; font-family:monospace;"><?= htmlspecialchars($muestra['diseno_resistencia'] ?: 'No especificada') ?></strong>
        </div>
        <div>
            <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Fecha RecepciÃ³n Lab:</span><br>
            <strong style="color:#1e293b;"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($muestra['fecha_ingreso']))) ?></strong>
        </div>
    </div>
    <div class="grid-3" style="margin-top: 15px;">
        <div>
            <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Plantilla TÃ©cnica Vinculada:</span><br>
            <span style="font-weight: 600;"><?= htmlspecialchars($muestra['plantilla_nombre'] ?: 'General') ?> (<?= htmlspecialchars($muestra['norma_referencia'] ?: 'ASTM C39') ?>)</span>
        </div>
        <div>
            <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Revenimiento (Slump):</span><br>
            <span><?= htmlspecialchars($muestra['revenimiento_in'] ? $muestra['revenimiento_in'] . ' in' : '--') ?></span>
        </div>
        <div>
            <span style="font-size:11px; color:#64748b; font-weight:700; text-transform:uppercase;">Procedimiento de Muestreo:</span><br>
            <span><?= htmlspecialchars($muestra['procedimiento_muestreo'] ?: 'ISO 17025') ?></span>
        </div>
    </div>
    <?php
        $dmVista = $datosMatriz ?? [];
        $tempVista = $dmVista['temperatura_c'] ?? ($muestra['temperatura_c'] ?? null);
        $areaVista = (!empty($dmVista['area']) && (float)$dmVista['area'] > 0) ? (float)$dmVista['area'] : null;
        $edadMatVista = $dmVista['edad_dias'] ?? null;
    ?>
    <div class="grid-4" style="margin-top: 15px; padding-top: 12px; border-top: 1px dashed #cbd5e1; background: #f8fafc; padding: 10px 12px; border-radius: 8px;">
        <div>
            <span style="font-size:11px; color:#0369a1; font-weight:700; text-transform:uppercase;"><i class="fa-solid fa-table-cells"></i> Edad en Matriz:</span><br>
            <strong style="color:#1e293b;"><?= $edadMatVista !== null ? ((int)$edadMatVista . ' días') : 'Según Plan' ?></strong>
        </div>
        <div>
            <span style="font-size:11px; color:#0369a1; font-weight:700; text-transform:uppercase;"><i class="fa-solid fa-temperature-half"></i> Temperatura (Matriz):</span><br>
            <strong style="color:#1e293b;"><?= $tempVista !== null && $tempVista !== '' ? (htmlspecialchars((string)$tempVista) . ' °C') : '--' ?></strong>
        </div>
        <div>
            <span style="font-size:11px; color:#0369a1; font-weight:700; text-transform:uppercase;"><i class="fa-solid fa-vector-square"></i> Área Transversal (Matriz):</span><br>
            <strong style="color:#1e293b; font-family:monospace;"><?= $areaVista !== null ? (number_format($areaVista, 3) . ' in²') : '--' ?></strong>
        </div>
        <div>
            <span style="font-size:11px; color:#0369a1; font-weight:700; text-transform:uppercase;"><i class="fa-solid fa-ruler-combined"></i> Diámetros / Masa (Matriz):</span><br>
            <span style="font-size:12px; color:#334155;">
                D: <?= !empty($dmVista['diametro_1']) ? htmlspecialchars((string)$dmVista['diametro_1']) : '--' ?> / <?= !empty($dmVista['diametro_2']) ? htmlspecialchars((string)$dmVista['diametro_2']) : '--' ?> cm
                | M: <?= !empty($dmVista['masa_kg']) ? (htmlspecialchars((string)$dmVista['masa_kg']) . ' kg') : '--' ?>
            </span>
        </div>
    </div>
</div>

<!-- 2. PROGRAMA DE ROTURAS PERIÃDICAS -->
<div class="lab-box">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">
        <div>
            <h4 style="font-family:'Outfit'; font-size:15px; font-weight:700; color:var(--cycsa-azul); margin: 0;">
                <i class="fa-solid fa-calendar-days"></i> Programa de Roturas PeriÃ³dicas
            </h4>
            <span style="font-size:12px; color:#64748b;">EspecÃ­menes Disponibles: <strong><?= $totalEspecimenes ?></strong> | Ensayados: <strong><?= $totalEnsayados ?></strong></span>
        </div>
        <div>
            <?php if (!$planActivo): ?>
                <button type="button" class="btn-accion btn-primary-lab" onclick="abrirModalConfigurarPlan()">
                    <i class="fa-solid fa-plus-circle"></i> Configurar Plan de Roturas
                </button>
            <?php elseif ($puedeEditarPlan): ?>
                <button type="button" class="btn-accion btn-secondary-lab" onclick="abrirModalConfigurarPlan()">
                    <i class="fa-solid fa-pen-to-square"></i> Editar Plan (0 roturas)
                </button>
            <?php elseif ($rolUsuario === 1 || $rolUsuario === 3): ?>
                <button type="button" class="btn-accion btn-warning-lab" onclick="abrirModalModificarPlanAutorizado()">
                    <i class="fa-solid fa-shield"></i> Modificar Plan (Calidad)
                </button>
            <?php endif; ?>
        </div>
    </div>

    <?php if ($planActivo && !empty($edades)): ?>
        <table class="tabla-lab">
            <thead>
                <tr>
                    <th>Edad (DÃ­as)</th>
                    <th>EspecÃ­menes Asignados</th>
                    <th>Fecha Programada</th>
                    <th>Tolerancia ASTM C39</th>
                    <th>Estado</th>
                    <th>Informe Parcial</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($edades as $ed): 
                    $completada = ($ed['estado'] === 'completado');
                    $rotos = (int)$ed['especimenes_rotos'];
                    $asignados = (int)$ed['especimenes_asignados'];
                ?>
                <tr>
                    <td><strong style="color: #7c3aed; font-size: 14px;"><?= (int)$ed['edad_dias'] ?> dÃ­as</strong></td>
                    <td>
                        <strong style="color:#0f172a;"><?= $rotos ?> / <?= $asignados ?></strong> ensayados
                    </td>
                    <td>
                        <strong><?= htmlspecialchars(date('d/m/Y', strtotime($ed['fecha_programada']))) ?></strong>
                    </td>
                    <td>&plusmn; <?= rtrim(rtrim(number_format($resolverToleranciaAstmRealDet((int)($ed['edad_dias'] ?? 28), $ed['tolerancia_horas'] ?? null), 2, '.', ''), '0'), '.') ?> hrs</td>
                    <td>
                        <?php if ($completada): ?>
                            <span class="badge-lab badge-ensayado"><i class="fa-solid fa-circle-check"></i> Completada</span>
                        <?php elseif ($rotos > 0): ?>
                            <span class="badge-lab badge-programado"><i class="fa-solid fa-spinner"></i> En Proceso</span>
                        <?php else: ?>
                            <span class="badge-lab badge-curado"><i class="fa-solid fa-clock"></i> Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if (!empty($ed['informe_parcial_numero'])): ?>
                            <a href="/Cycsa/publico/laboratorio/ver-informe?id=<?= (int)$ed['informe_parcial_id'] ?>" target="_blank" style="font-family: monospace; font-weight: 700; color: #0369a1; text-decoration: underline;" title="Ver/Imprimir Informe Parcial">
                                <?= htmlspecialchars($ed['informe_parcial_numero']) ?>
                            </a>
                            <?php if ($ed['informe_parcial_estado'] === 'aprobado'): ?>
                                <span class="badge-lab badge-aprobado">Aprobado</span>
                            <?php elseif ($ed['informe_parcial_estado'] === 'devuelto'): ?>
                                <span class="badge-lab badge-devuelto">Devuelto</span>
                            <?php else: ?>
                                <span class="badge-lab badge-revision">En Revisión</span>
                            <?php endif; ?>
                            <a href="/Cycsa/publico/laboratorio/ver-informe?id=<?= (int)$ed['informe_parcial_id'] ?>" target="_blank" class="btn-accion btn-secondary-lab" style="padding: 2px 6px; font-size: 10px;" title="Ver/Imprimir Informe">
                                <i class="fa-solid fa-eye"></i> Ver
                            </a>
                        <?php else: ?>
                            <span style="color:#94a3b8; font-style:italic;">Sin informe emitido</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <?php if ($completada && empty($ed['informe_parcial_id'])): ?>
                            <form method="POST" action="/Cycsa/publico/laboratorio/generar-informe-parcial" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="muestra_id" value="<?= (int)$muestra['muestra_id'] ?>">
                                <input type="hidden" name="plan_edad_id" value="<?= (int)$ed['id'] ?>">
                                <button type="submit" class="btn-accion btn-primary-lab" title="Generar Informe Parcial Ciego">
                                    <i class="fa-solid fa-file-circle-plus"></i> Generar Parcial
                                </button>
                            </form>
                        <?php elseif (!empty($ed['informe_parcial_id']) && ($rolUsuario === 1 || $rolUsuario === 3) && $ed['informe_parcial_estado'] === 'en_revision'): ?>
                            <!-- AprobaciÃ³n de Calidad -->
                            <form method="POST" action="/Cycsa/publico/laboratorio/procesar-revision-informe" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="informe_id" value="<?= (int)$ed['informe_parcial_id'] ?>">
                                <input type="hidden" name="muestra_id" value="<?= (int)$muestra['muestra_id'] ?>">
                                <input type="hidden" name="nuevo_estado" value="aprobado">
                                <button type="submit" class="btn-accion btn-success-lab" title="Aprobar Informe Parcial">
                                    <i class="fa-solid fa-thumbs-up"></i> Aprobar
                                </button>
                            </form>
                            <button type="button" class="btn-accion btn-warning-lab" onclick="abrirModalDevolverInforme(<?= (int)$ed['informe_parcial_id'] ?>)" title="Devolver con observaciones">
                                <i class="fa-solid fa-rotate-left"></i> Devolver
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php else: ?>
        <div style="text-align: center; padding: 30px; background: #f8fafc; border: 1px dashed #cbd5e1; border-radius: 8px;">
            <i class="fa-solid fa-calendar-xmark" style="font-size: 28px; color: #94a3b8; display: block; margin-bottom: 8px;"></i>
            <span style="color: #64748b; font-weight: 600;">Esta muestra aÃºn no tiene un Plan de Roturas definido.</span><br>
            <button type="button" class="btn-accion btn-primary-lab" style="margin-top: 10px;" onclick="abrirModalConfigurarPlan()">
                <i class="fa-solid fa-plus-circle"></i> Configurar Plan de Roturas Ahora
            </button>
        </div>
    <?php endif; ?>
</div>

<!-- 3. DETALLE DE PROBETAS / ESPECÃMENES -->
<div class="lab-box">
    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
        <h4 style="font-family:'Outfit'; font-size:15px; font-weight:700; color:var(--cycsa-azul); margin: 0;">
            <i class="fa-solid fa-vial"></i> EspecÃ­menes y Registro de Ensayos
        </h4>
        <span style="font-size:12px; color:#64748b;">Ensayados: <?= $totalEnsayados ?> de <?= $totalEspecimenes ?></span>
    </div>

    <table class="tabla-lab">
        <thead>
            <tr>
                <th>CÃ³digo Probeta</th>
                <th>Dimensiones</th>
                <th>Edad Asignada</th>
                <th>Fecha Prog.</th>
                <th>Carga</th>
                <th>Ãrea</th>
                <th>Esfuerzo PSI</th>
                <th>Esfuerzo kg/cmÂ²</th>
                <th>% f'c</th>
                <th>Falla</th>
                <th>Estado</th>
                <th style="text-align: right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($especimenes ?? []) as $esp): 
                $ensayado = ($esp['estado'] === 'ensayado');
            ?>
            <tr>
                <td style="font-family: monospace; font-weight: 800; color: #0369a1;">
                    <?= htmlspecialchars($esp['codigo_lab']) ?>
                </td>
                <td><?= htmlspecialchars($esp['dimensiones'] ?: '15 x 30 cm') ?></td>
                <td>
                    <?php if (!empty($esp['edad_dias'])): ?>
                        <strong style="color: #7c3aed;"><?= (int)$esp['edad_dias'] ?> dÃ­as</strong>
                    <?php else: ?>
                        <span style="color: #94a3b8; font-style: italic;">Sin edad</span>
                    <?php endif; ?>
                </td>
                <td>
                    <?= !empty($esp['fecha_programada']) ? htmlspecialchars(date('d/m/Y', strtotime($esp['fecha_programada']))) : '--' ?>
                </td>
                <td>
                    <?= $ensayado ? '<strong>' . number_format((float)$esp['carga'], 1) . '</strong> lb' : '--' ?>
                </td>
                <td>
                    <?= $ensayado ? number_format((float)$esp['area'], 3) . ' inÂ²' : '--' ?>
                </td>
                <td style="font-family: monospace; font-weight: 700;">
                    <?= $ensayado ? number_format((float)$esp['esfuerzo_psi'], 1) : '--' ?>
                </td>
                <td style="font-family: monospace; font-weight: 700; color: #047857;">
                    <?= $ensayado ? number_format((float)$esp['esfuerzo_kgcm2'], 1) : '--' ?>
                </td>
                <td>
                    <?php if ($ensayado && !empty($esp['porcentaje_fc'])): ?>
                        <strong style="color: <?= (float)$esp['porcentaje_fc'] >= 100 ? '#166534' : '#b45309' ?>;">
                            <?= number_format((float)$esp['porcentaje_fc'], 1) ?>%
                        </strong>
                    <?php else: ?>
                        --
                    <?php endif; ?>
                </td>
                <td>
                    <?= $ensayado ? htmlspecialchars($esp['tipo_falla'] ?: 'Tipo 1') : '--' ?>
                </td>
                <td>
                    <?php if ($ensayado): ?>
                        <span class="badge-lab badge-ensayado"><i class="fa-solid fa-check"></i> Ensayado</span>
                        <?php if (!empty($esp['fuera_tolerancia'])): ?>
                            <span class="badge-lab" style="background:#fee2e2; color:#b91c1c; margin-top:3px; display:block;" title="<?= htmlspecialchars($esp['justificacion'] ?? '') ?>">Fuera Tol.</span>
                        <?php endif; ?>
                    <?php elseif ($esp['estado'] === 'programado'): ?>
                        <span class="badge-lab badge-programado">Programado</span>
                    <?php elseif ($esp['estado'] === 'testigo'): ?>
                        <span class="badge-lab badge-testigo">Testigo</span>
                    <?php else: ?>
                        <span class="badge-lab badge-curado">En Curado</span>
                    <?php endif; ?>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                    <?php if (!$ensayado && !empty($esp['plan_edad_id'])):
                        $tolEspDetalle = $resolverToleranciaAstmRealDet((int)($esp['edad_dias'] ?: 28), $esp['tolerancia_horas'] ?? null);
                    ?>
                        <button type="button" class="btn-accion btn-primary-lab" onclick="abrirModalRegistrarRotura(<?= (int)$esp['id'] ?>, '<?= htmlspecialchars($esp['codigo_lab']) ?>', <?= (int)$esp['plan_edad_id'] ?>, <?= (int)$esp['edad_dias'] ?>, '<?= $esp['fecha_programada'] ?>', <?= $tolEspDetalle ?>)">
                            <i class="fa-solid fa-hammer"></i> Ensayar
                        </button>
                    <?php endif; ?>
                    <a href="/Cycsa/publico/laboratorio/etiqueta-probeta?id=<?= (int)$esp['id'] ?>" target="_blank" class="btn-accion btn-secondary-lab" title="Imprimir etiqueta oficial con QR">
                        <i class="fa-solid fa-qrcode"></i> Etiqueta
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 4. INFORMES Y CONSOLIDADO FINAL -->
<div class="lab-box">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">
        <div>
            <h4 style="font-family:'Outfit'; font-size:15px; font-weight:700; color:var(--cycsa-azul); margin: 0;">
                <i class="fa-solid fa-file-pdf"></i> Informes de Ensayo (INF-YYYY-XXXX)
            </h4>
            <span style="font-size:12px; color:#64748b;">Consolidado habilitado estrictamente al aprobarse todos los parciales</span>
        </div>
        <div style="display:flex; gap:10px; align-items:center; flex-wrap: wrap;">
            <?php if (!empty($muestra['id_hoja'])): ?>
                <a href="/Cycsa/publico/laboratorio/ver-informe?id_hoja=<?= (int)$muestra['id_hoja'] ?>" target="_blank" class="btn-accion btn-primary-lab" style="font-size: 12.5px; padding: 6px 14px;" title="Ver Reporte Oficial del Lote">
                    <i class="fa-solid fa-eye"></i> Ver Reporte Oficial
                </a>
            <?php endif; ?>
            <?php if (!empty($puede_consolidar)): ?>
                <form method="POST" action="/Cycsa/publico/laboratorio/generar-informe-consolidado" style="display:inline;">
                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                    <input type="hidden" name="muestra_id" value="<?= (int)$muestra['muestra_id'] ?>">
                    <button type="submit" class="btn-accion btn-success-lab">
                        <i class="fa-solid fa-award"></i> Emitir Informe Consolidado Final
                    </button>
                </form>
            <?php else: ?>
                <button type="button" class="btn-accion btn-secondary-lab" disabled style="opacity: 0.6; cursor: not-allowed;" title="Bloqueado: Todos los informes parciales deben estar completados y aprobados por Calidad.">
                    <i class="fa-solid fa-lock"></i> Consolidado Bloqueado (Parciales Pendientes)
                </button>
            <?php endif; ?>
        </div>
    </div>

    <table class="tabla-lab">
        <thead>
            <tr>
                <th>Número de Informe</th>
                <th>Tipo</th>
                <th>Versión</th>
                <th>Fecha Emisión</th>
                <th>Estado Calidad</th>
                <th>Aprobado Por</th>
                <th>Observaciones / Devolución</th>
                <th style="text-align: right;">Acciones</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach (($informes ?? []) as $inf): ?>
            <tr>
                <td style="font-family: monospace; font-weight: 800; color: #0369a1;">
                    <a href="/Cycsa/publico/laboratorio/ver-informe?id=<?= (int)$inf['id'] ?>" target="_blank" style="color: #0369a1; text-decoration: underline;" title="Ver/Imprimir Informe">
                        <?= htmlspecialchars($inf['numero']) ?>
                    </a>
                </td>
                <td><strong style="text-transform: capitalize;"><?= htmlspecialchars($inf['tipo']) ?></strong></td>
                <td>v<?= (int)$inf['version'] ?></td>
                <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($inf['created_at']))) ?></td>
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
                <td><?= htmlspecialchars($inf['aprobado_por_nombre'] ?: 'Gerencia de Calidad') ?></td>
                <td style="font-size: 12px; color: #64748b;">
                    <?= htmlspecialchars($inf['motivo_reemision'] ? ('Sustituido: ' . $inf['motivo_reemision']) : ($inf['motivo_devolucion'] ?: ($inf['observaciones'] ?: '--'))) ?>
                </td>
                <td style="text-align: right; white-space: nowrap;">
                    <div style="display: inline-flex; gap: 5px; align-items: center; justify-content: flex-end;">
                        <a href="/Cycsa/publico/laboratorio/ver-informe?id=<?= (int)$inf['id'] ?>" target="_blank" class="btn-accion btn-primary-lab" style="padding: 4px 10px; font-size: 11.5px; font-weight: 700;" title="Ver/Imprimir informe oficial">
                            <i class="fa-solid fa-eye"></i> Ver Informe
                        </a>
                        <?php if ($inf['estado'] === 'aprobado' && ($rolUsuario === 1 || $rolUsuario === 3)): ?>
                            <button type="button" onclick="abrirModalReemitirInforme(<?= (int)$inf['id'] ?>, '<?= htmlspecialchars($inf['numero']) ?>')" class="btn-accion btn-warning-lab" style="padding: 4px 10px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;" title="Reemitir informe oficial (N+1) según ISO/IEC 17025">
                                <i class="fa-solid fa-arrows-rotate"></i> Reemitir
                            </button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($informes)): ?>
            <tr>
                <td colspan="8" style="text-align: center; padding: 25px; color: #64748b;">No se han emitido informes para esta muestra aún.</td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- =========================================================================
     MODALES INTERACTIVOS
     ========================================================================= -->

<!-- Modal 1: Configurar Plan de Roturas -->
<div id="modalPlanRotura" class="modal-lab">
    <div class="modal-lab-content">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: var(--cycsa-azul);">
                <i class="fa-solid fa-calendar-plus"></i> Configurar Plan de Roturas PeriÃ³dicas
            </h3>
            <button onclick="cerrarModalPlanRotura()" style="border: none; background: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <form method="POST" action="/Cycsa/publico/laboratorio/guardar-plan-rotura">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="muestra_id" value="<?= (int)$muestra['muestra_id'] ?>">

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 15px;">
                <div style="font-size: 13px; font-weight: 700; color: #334155; margin-bottom: 4px;">
                    Especímenes: <span id="lbl_total_disp" style="color:var(--cycsa-azul);"><?= $totalEspecimenes > 0 ? ($totalEspecimenes . ' disponibles en recepción') : 'Se generarán automáticamente según el plan' ?></span>
                </div>
                <div style="font-size: 12px; color: #64748b;">
                    Defina las edades en días y cuántas probetas ensayar en cada fecha. <?= $totalEspecimenes > 0 ? 'Los sobrantes quedarán registrados como testigos.' : 'El plan creará las probetas necesarias correlativas con su código de muestra.' ?>
                </div>
            </div>

            <div id="contenedor_edades_inputs" style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 15px;">
                <div class="fila-edad-item" style="display: flex; gap: 10px; align-items: center;">
                    <div style="flex: 2;">
                        <label style="font-size: 11px; font-weight: 700; color: #64748b;">Edad (DÃ­as):</label>
                        <select name="edad_dias[]" class="form-control" style="width: 100%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px;" onchange="recalcularPlanEspecimenes()">
                            <option value="3">3 dÃ­as</option>
                            <option value="7" selected>7 dÃ­as</option>
                            <option value="14">14 dÃ­as</option>
                            <option value="28">28 dÃ­as</option>
                            <option value="90">90 dÃ­as</option>
                        </select>
                    </div>
                    <div style="flex: 2;">
                        <label style="font-size: 11px; font-weight: 700; color: #64748b;">Cantidad Probetas:</label>
                        <input type="text" inputmode="numeric" pattern="[0-9]+" autocomplete="off" name="cantidad[]" class="form-control input-cant-plan" min="1" value="1" style="width: 100%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px;" oninput="recalcularPlanEspecimenes()">
                    </div>
                    <div style="flex: 1; margin-top: 18px;">
                        <button type="button" class="btn-accion btn-secondary-lab" onclick="eliminarFilaEdad(this)" style="color: #ef4444;"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
                <div class="fila-edad-item" style="display: flex; gap: 10px; align-items: center;">
                    <div style="flex: 2;">
                        <label style="font-size: 11px; font-weight: 700; color: #64748b;">Edad (DÃ­as):</label>
                        <select name="edad_dias[]" class="form-control" style="width: 100%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px;" onchange="recalcularPlanEspecimenes()">
                            <option value="3">3 dÃ­as</option>
                            <option value="7">7 dÃ­as</option>
                            <option value="14">14 dÃ­as</option>
                            <option value="28" selected>28 dÃ­as</option>
                            <option value="90">90 dÃ­as</option>
                        </select>
                    </div>
                    <div style="flex: 2;">
                        <label style="font-size: 11px; font-weight: 700; color: #64748b;">Cantidad Probetas:</label>
                        <input type="text" inputmode="numeric" pattern="[0-9]+" autocomplete="off" name="cantidad[]" class="form-control input-cant-plan" min="1" value="2" style="width: 100%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px;" oninput="recalcularPlanEspecimenes()">
                    </div>
                    <div style="flex: 1; margin-top: 18px;">
                        <button type="button" class="btn-accion btn-secondary-lab" onclick="eliminarFilaEdad(this)" style="color: #ef4444;"><i class="fa-solid fa-trash"></i></button>
                    </div>
                </div>
            </div>

            <button type="button" class="btn-accion btn-secondary-lab" onclick="agregarFilaEdad()" style="margin-bottom: 15px;">
                <i class="fa-solid fa-plus"></i> Agregar otra Edad
            </button>

            <!-- Sumatoria y Testigos en tiempo real -->
            <div style="background: #f1f5f9; padding: 10px 14px; border-radius: 8px; margin-bottom: 20px; font-size: 12.5px;">
                <?php if ($totalEspecimenes > 0): ?>
                    Asignadas: <strong id="lbl_suma_asignada" style="color: #103487;">3</strong> / <?= $totalEspecimenes ?> | Sobrantes como Testigo: <strong id="lbl_sobrantes_testigo" style="color: #059669;"><?= max(0, $totalEspecimenes - 3) ?></strong>
                <?php else: ?>
                    Total de probetas a generar y ensayar: <strong id="lbl_suma_asignada" style="color: #103487;">3</strong>
                <?php endif; ?>
                <div id="alerta_exceso" style="display: none; color: #b91c1c; font-weight: 700; margin-top: 4px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Error: Asigne al menos una probeta al plan.
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-accion btn-secondary-lab" onclick="cerrarModalPlanRotura()">Cancelar</button>
                <button type="submit" id="btn_guardar_plan" class="btn-accion btn-primary-lab">
                    <i class="fa-solid fa-check"></i> Guardar y Activar Plan
                </button>
            </div>
        </form>
    </div>
</div>

<!-- Modal 2: Registrar Ensayo de Rotura -->
<div id="modalRegistrarRotura" class="modal-lab">
    <div class="modal-lab-content" style="max-width: 780px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px; margin-bottom: 16px;">
            <h3 style="margin: 0; font-size: 17px; font-weight: 800; color: var(--cycsa-azul); display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-hammer"></i> Captura de Rotura: <span id="rot_esp_codigo" style="font-family: monospace; color:#0369a1;"></span>
            </h3>
            <button type="button" onclick="cerrarModalRegistrarRotura()" style="border: none; background: none; font-size: 24px; cursor: pointer; color: #64748b; line-height: 1;">&times;</button>
        </div>

        <form method="POST" action="/Cycsa/publico/laboratorio/registrar-rotura" onsubmit="validarEnvioRotura(event)" style="margin: 0;">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="muestra_id" value="<?= (int)$muestra['muestra_id'] ?>">
            <input type="hidden" id="rot_especimen_id" name="especimen_id" value="">
            <input type="hidden" id="rot_plan_edad_id" name="plan_edad_id" value="">
            <input type="hidden" id="rot_fecha_prog" value="">
            <input type="hidden" id="rot_fecha_moldeo" value="<?= htmlspecialchars((string)($muestra['fecha_moldeo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" id="rot_tol_horas" value="20">

            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 14px; margin-bottom: 14px;">
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Fecha y Hora Real de Ensayo:</label>
                    <input type="datetime-local" id="rot_fecha_real" name="fecha_real" class="form-control" style="width: 100%; box-sizing: border-box; height: 38px; font-size: 13px;" value="<?= date('Y-m-d\TH:i') ?>" onchange="verificarToleranciaTiempo(); calcularEsfuerzoEnVivo();" required>
                </div>
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Tipo de Falla (ASTM C39):</label>
                    <select name="tipo_falla" class="form-control" style="width: 100%; box-sizing: border-box; height: 38px; font-size: 13px;">
                        <option value="Tipo 1 (Cono bien formado)">Tipo 1 (Cono bien formado)</option>
                        <option value="Tipo 2 (Cono y división)">Tipo 2 (Cono y división)</option>
                        <option value="Tipo 3 (División columnar)">Tipo 3 (División columnar)</option>
                        <option value="Tipo 4 (Fractura diagonal)">Tipo 4 (Fractura diagonal)</option>
                        <option value="Tipo 5 (Fractura en esquinas)">Tipo 5 (Fractura en esquinas)</option>
                    </select>
                </div>
            </div>

            <!-- Dimensiones y Masa RT-FM-61 (directos en modal, sin área prellenada 28.274) -->
            <div style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 12px 14px; margin-bottom: 14px;">
                <div style="font-size: 11.5px; font-weight: 800; color: #103487; margin-bottom: 8px;">
                    <i class="fa-solid fa-ruler-combined"></i> Dimensiones, Masa y Carga del Espécimen (RT-FM-61)
                </div>
                <div style="display: grid; grid-template-columns: repeat(5, minmax(0, 1fr)); gap: 10px; margin-bottom: 12px;">
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">D1 (cm):</label>
                        <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]+)?" autocomplete="off" name="datos_crudos[diametro_1]" id="hc_d1" class="form-control" style="width: 100%; box-sizing: border-box; font-size: 12.5px; height: 36px;" placeholder="Ej. 10.10" oninput="recalcularDesdeHc61()">
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">D2 (cm):</label>
                        <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]+)?" autocomplete="off" name="datos_crudos[diametro_2]" id="hc_d2" class="form-control" style="width: 100%; box-sizing: border-box; font-size: 12.5px; height: 36px;" placeholder="Ej. 10.10" oninput="recalcularDesdeHc61()">
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">L1 (cm):</label>
                        <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]+)?" autocomplete="off" name="datos_crudos[largo_1]" id="hc_l1" class="form-control" style="width: 100%; box-sizing: border-box; font-size: 12.5px; height: 36px;" placeholder="Ej. 20.15" oninput="recalcularDesdeHc61()">
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">L2 (cm):</label>
                        <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]+)?" autocomplete="off" name="datos_crudos[largo_2]" id="hc_l2" class="form-control" style="width: 100%; box-sizing: border-box; font-size: 12.5px; height: 36px;" placeholder="Ej. 20.15" oninput="recalcularDesdeHc61()">
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 3px;">Masa (kg):</label>
                        <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]+)?" autocomplete="off" name="datos_crudos[masa_kg]" id="hc_masa" class="form-control" style="width: 100%; box-sizing: border-box; font-size: 12.5px; height: 36px;" placeholder="Ej. 3.820" oninput="recalcularDesdeHc61()">
                    </div>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                    <div>
                        <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Carga de Rotura (lb):</label>
                        <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]+)?" autocomplete="off" id="rot_carga" name="carga" class="form-control" style="width: 100%; box-sizing: border-box; font-size: 15px; font-weight: 700; height: 38px;" placeholder="Ej. 44840" oninput="calcularEsfuerzoEnVivo()" required>
                    </div>
                    <div>
                        <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Área Transversal (in²) <span style="font-weight:500; color:#64748b;">(auto desde D1 y D2)</span>:</label>
                        <?php
                            $d1PreDet = (!empty($datosMatriz['diametro_1']) && (float)$datosMatriz['diametro_1'] > 0) ? (float)$datosMatriz['diametro_1'] : 0.0;
                            $d2PreDet = (!empty($datosMatriz['diametro_2']) && (float)$datosMatriz['diametro_2'] > 0) ? (float)$datosMatriz['diametro_2'] : 0.0;
                            $areaPrefillModal = ($d1PreDet > 0.0 && $d2PreDet > 0.0 && !empty($datosMatriz['area']) && (float)$datosMatriz['area'] > 0) ? (string)$datosMatriz['area'] : '';
                        ?>
                        <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]+)?" autocomplete="off" id="rot_area" name="area" class="form-control" style="width: 100%; box-sizing: border-box; height: 38px; font-size: 13px; background:#f1f5f9;" value="<?= htmlspecialchars($areaPrefillModal, ENT_QUOTES, 'UTF-8') ?>" placeholder="Ingrese D1 y D2" readonly required>
                    </div>
                </div>
            </div>

            <!-- Opciones avanzadas HC (Formato alternativo / Lectura Prensa PRENSA-01) -->
            <details style="background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px;">
                <summary style="font-size: 12px; font-weight: 700; color: #475569; cursor: pointer; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-sliders"></i> Opciones avanzadas (Formato HC alternativo / Calibración PRENSA-01)
                </summary>
                <div style="margin-top: 10px; display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Formato HC:</label>
                        <select name="datos_crudos[codigo_formato]" id="hc_codigo_formato" class="form-control" style="width: 100%; box-sizing: border-box; font-size: 12px; height: 36px;">
                            <option value="">-- Directo (RT-FM-61 oficial) --</option>
                            <option value="RT-FM-61">RT-FM-61 (Cilindro C39 + PRENSA-01)</option>
                            <option value="RT-FM-36">RT-FM-36 (Bloques C140)</option>
                            <option value="RT-FM-38">RT-FM-38 (Vigas C78)</option>
                            <option value="RT-FM-57">RT-FM-57 (Cubos Mortero C109)</option>
                            <option value="RT-FM-63">RT-FM-63 (Núcleos/Adoquín)</option>
                            <option value="RT-FM-64">RT-FM-64 (Acero A615)</option>
                        </select>
                    </div>
                    <div>
                        <label style="font-size: 11px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Lectura Prensa sin corregir (lbf) — opcional:</label>
                        <input type="text" inputmode="decimal" pattern="[0-9]+([.,][0-9]+)?" autocomplete="off" name="datos_crudos[lectura_carga_lbf]" id="hc_lectura_lbf" class="form-control" style="width: 100%; box-sizing: border-box; font-size: 12px; height: 36px;" placeholder="Ej. 91932" oninput="recalcularDesdeHc61()">
                    </div>
                </div>
            </details>

            <!-- Previsualización de Cálculos en Vivo (RT-FM-61 oficial) -->
            <div style="background: #f8fafc; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 15px; display: grid; grid-template-columns: repeat(3, 1fr); gap: 10px; text-align: center;">
                <div style="background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px;">
                    <div style="font-size: 10.5px; color: #64748b; font-weight: 700; text-transform: uppercase;">Área (in²):</div>
                    <div id="calc_area_in2" style="font-size: 16px; font-weight: 800; font-family: monospace; color: #1e293b; margin-top: 2px;">--</div>
                </div>
                <div style="background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px;">
                    <div style="font-size: 10.5px; color: #64748b; font-weight: 700; text-transform: uppercase;">Volumen (m³):</div>
                    <div id="calc_vol_m3" style="font-size: 15px; font-weight: 800; font-family: monospace; color: #1e293b; margin-top: 2px;">--</div>
                </div>
                <div style="background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px;">
                    <div style="font-size: 10.5px; color: #64748b; font-weight: 700; text-transform: uppercase;">Peso Vol. (kg/m³):</div>
                    <div id="calc_pv" style="font-size: 16px; font-weight: 800; font-family: monospace; color: #0f766e; margin-top: 2px;">--</div>
                </div>
                <div style="background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px;">
                    <div style="font-size: 10.5px; color: #64748b; font-weight: 700; text-transform: uppercase;">Edad Real:</div>
                    <div id="calc_edad_dias" style="font-size: 16px; font-weight: 800; font-family: monospace; color: #7c3aed; margin-top: 2px;">--</div>
                </div>
                <div style="background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px;">
                    <div style="font-size: 10.5px; color: #64748b; font-weight: 700; text-transform: uppercase;">Esfuerzo PSI (a 10):</div>
                    <div id="calc_psi" style="font-size: 18px; font-weight: 800; font-family: monospace; color: #0369a1; margin-top: 2px;">--</div>
                </div>
                <div style="background: white; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px;">
                    <div style="font-size: 10.5px; color: #64748b; font-weight: 700; text-transform: uppercase;">Esfuerzo kgf/cm²:</div>
                    <div id="calc_kgcm2" style="font-size: 18px; font-weight: 800; font-family: monospace; color: #047857; margin-top: 2px;">--</div>
                </div>
            </div>

            <!-- Alerta y Justificación de Tolerancia Temporal -->
            <div id="box_justificacion_tolerancia" style="display: none; background: #fff1f2; border: 1.5px solid #fecaca; border-radius: 8px; padding: 14px; margin-bottom: 15px;">
                <div style="font-size: 13px; font-weight: 800; color: #991b1b; margin-bottom: 4px; display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> ENSAYO FUERA DE TOLERANCIA ASTM C39 (<span id="rot_alerta_tolerancia">&plusmn;20 hrs</span>)
                </div>
                <div style="font-size: 13px; color: #881337; margin-bottom: 8px; line-height: 1.4;">
                    La fecha/hora seleccionada excede la ventana de tolerancia admisible para esta edad (&plusmn;<span id="rot_tol_hrs_txt">20</span> hrs). Es mandatorio registrar una justificación técnica para el informe.
                </div>
                <textarea id="rot_justificacion" name="justificacion" rows="2" class="form-control" style="width: 100%; box-sizing: border-box; resize: vertical; padding: 8px 10px; font-size: 13px;" placeholder="Justificación técnica por ensayo extemporáneo..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px; border-top: 1px solid #e2e8f0; padding-top: 14px;">
                <button type="button" class="btn-accion btn-secondary-lab" onclick="cerrarModalRegistrarRotura()" style="padding: 9px 18px; font-size: 13px; font-weight: 600;">Cancelar</button>
                <button type="submit" class="btn-accion btn-primary-lab" style="padding: 9px 20px; font-size: 13px; font-weight: 700;">
                    <i class="fa-solid fa-check"></i> Registrar Rotura
                </button>
            </div>
        </form>
    </div>
</div>
<!-- Modal 3: Devolver Informe Parcial (Calidad) -->
<div id="modalDevolverInforme" class="modal-lab">
    <div class="modal-lab-content" style="max-width: 480px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">
            <h3 style="margin: 0; font-size: 15px; font-weight: 800; color: #991b1b;">
                <i class="fa-solid fa-rotate-left"></i> Devolver Informe Parcial a Laboratorio
            </h3>
            <button onclick="cerrarModalDevolverInforme()" style="border: none; background: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <form method="POST" action="/Cycsa/publico/laboratorio/procesar-revision-informe">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" id="dev_informe_id" name="informe_id" value="">
            <input type="hidden" name="muestra_id" value="<?= (int)$muestra['muestra_id'] ?>">
            <input type="hidden" name="nuevo_estado" value="devuelto">

            <div style="margin-bottom: 15px;">
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Motivo de DevoluciÃ³n / ObservaciÃ³n TÃ©cnica:</label>
                <textarea name="motivo_devolucion" rows="3" class="form-control" style="width: 100%; box-sizing: border-box;" placeholder="Especifique el motivo de rechazo o correcciÃ³n requerida..." required></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-accion btn-secondary-lab" onclick="cerrarModalDevolverInforme()">Cancelar</button>
                <button type="submit" class="btn-accion btn-warning-lab">
                    <i class="fa-solid fa-paper-plane"></i> Registrar DevoluciÃ³n
                </button>
            </div>
        </form>
    </div>
</div>

<script>
// Manejo del Plan de Roturas
const TOTAL_ESPECIMENES = <?= (int)$totalEspecimenes ?>;

function abrirModalConfigurarPlan() {
    const m = document.getElementById('modalPlanRotura');
    if (m) m.style.display = 'block';
    recalcularPlanEspecimenes();
}

function abrirModalModificarPlanAutorizado() {
    abrirModalConfigurarPlan();
}

function cerrarModalPlanRotura() {
    const m = document.getElementById('modalPlanRotura');
    if (m) m.style.display = 'none';
}

function agregarFilaEdad() {
    const c = document.getElementById('contenedor_edades_inputs');
    const div = document.createElement('div');
    div.className = 'fila-edad-item';
    div.style = 'display: flex; gap: 10px; align-items: center;';
    div.innerHTML = `
        <div style="flex: 2;">
            <label style="font-size: 11px; font-weight: 700; color: #64748b;">Edad (DÃ­as):</label>
            <select name="edad_dias[]" class="form-control" style="width: 100%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px;" onchange="recalcularPlanEspecimenes()">
                <option value="1">1 dÃ­a</option>
                <option value="3">3 dÃ­as</option>
                <option value="7">7 dÃ­as</option>
                <option value="14">14 dÃ­as</option>
                <option value="28" selected>28 dÃ­as</option>
                <option value="90">90 dÃ­as</option>
            </select>
        </div>
        <div style="flex: 2;">
            <label style="font-size: 11px; font-weight: 700; color: #64748b;">Cantidad Probetas:</label>
            <input type="text" inputmode="numeric" pattern="[0-9]+" autocomplete="off" name="cantidad[]" class="form-control input-cant-plan" min="1" value="1" style="width: 100%; padding: 6px 10px; border: 1px solid #cbd5e1; border-radius: 6px;" oninput="recalcularPlanEspecimenes()">
        </div>
        <div style="flex: 1; margin-top: 18px;">
            <button type="button" class="btn-accion btn-secondary-lab" onclick="eliminarFilaEdad(this)" style="color: #ef4444;"><i class="fa-solid fa-trash"></i></button>
        </div>
    `;
    c.appendChild(div);
    recalcularPlanEspecimenes();
}

function eliminarFilaEdad(btn) {
    const fila = btn.closest('.fila-edad-item');
    if (fila) {
        fila.remove();
        recalcularPlanEspecimenes();
    }
}

function recalcularPlanEspecimenes() {
    const inputs = document.querySelectorAll('.input-cant-plan');
    let suma = 0;
    inputs.forEach(i => {
        suma += parseInt(i.value || 0, 10);
    });

    const lblSuma = document.getElementById('lbl_suma_asignada');
    if (lblSuma) lblSuma.innerText = suma;

    const lblSobrantes = document.getElementById('lbl_sobrantes_testigo');
    if (lblSobrantes) {
        const sobrantes = TOTAL_ESPECIMENES > 0 ? Math.max(0, TOTAL_ESPECIMENES - suma) : 0;
        lblSobrantes.innerText = sobrantes;
    }

    const alerta = document.getElementById('alerta_exceso');
    const btnGuardar = document.getElementById('btn_guardar_plan');
    if (suma <= 0) {
        alerta.style.display = 'block';
        alerta.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> Error: Asigne al menos una probeta al plan.';
        btnGuardar.disabled = true;
        btnGuardar.style.opacity = '0.5';
    } else {
        alerta.style.display = 'none';
        btnGuardar.disabled = false;
        btnGuardar.style.opacity = '1';
    }
}

// Manejo de la Rotura de Probeta (precargado con fuente de verdad de la Matriz)
const DATOS_MATRIZ_MUESTRA = <?= json_encode($datosMatriz ?? [], JSON_UNESCAPED_UNICODE) ?>;
const LAB_DIVISOR_KGCM2 = <?= json_encode($divisorKgCm2Lab) ?>;
const LAB_REDONDEAR_PSI_10 = <?= $redondearPsi10Lab ? 'true' : 'false' ?>;
const LAB_REDONDEAR_PV_10 = <?= $redondearPv10Lab ? 'true' : 'false' ?>;

function obtenerToleranciaAstmC39DetJs(edadDias, tolRaw) {
    const edad = parseInt(edadDias || 28, 10);
    const tabla = {1: 0.5, 3: 2, 7: 6, 14: 12, 28: 20, 56: 36, 90: 48};
    const tol = parseFloat(tolRaw || 0);
    if (!tol || tol <= 0 || (Math.abs(tol - 24) < 0.01 && tabla[edad] !== undefined)) {
        return tabla[edad] !== undefined ? tabla[edad] : Math.max(2, Math.round(edad * 0.75 * 100) / 100);
    }
    return tol;
}

function abrirModalRegistrarRotura(espId, codigoLab, planEdadId, edadDias, fechaProg, tolHoras) {
    const tolNorm = obtenerToleranciaAstmC39DetJs(edadDias, tolHoras);
    document.getElementById('rot_especimen_id').value = espId;
    document.getElementById('rot_esp_codigo').innerText = codigoLab;
    document.getElementById('rot_plan_edad_id').value = planEdadId;
    document.getElementById('rot_fecha_prog').value = fechaProg;
    document.getElementById('rot_tol_horas').value = tolNorm;
    const tolTxt = document.getElementById('rot_tol_hrs_txt');
    if (tolTxt) tolTxt.innerText = tolNorm;
    const alertaTol = document.getElementById('rot_alerta_tolerancia');
    if (alertaTol) alertaTol.innerText = '±' + tolNorm + ' hrs';

    const dm = DATOS_MATRIZ_MUESTRA || {};
    document.getElementById('rot_carga').value = (dm.carga && dm.carga > 0) ? dm.carga : '';
    const fechaRealDet = document.getElementById('rot_fecha_real');
    if (fechaRealDet) {
        const fHoraMat = String(dm.fecha_ensayo_hora || '').trim();
        const fDiaMat = String(dm.fecha_ensayo || fechaProg || '').trim().slice(0, 10);
        if (fHoraMat && fHoraMat.includes('T')) {
            fechaRealDet.value = fHoraMat.slice(0, 16);
        } else if (fHoraMat && /^\d{4}-\d{2}-\d{2}\s+\d{2}:\d{2}/.test(fHoraMat)) {
            fechaRealDet.value = fHoraMat.slice(0, 16).replace(' ', 'T');
        } else if (fDiaMat && /^\d{4}-\d{2}-\d{2}$/.test(fDiaMat)) {
            fechaRealDet.value = fDiaMat + 'T08:00';
        }
    }
    const selFallaDet = document.querySelector('#modalRegistrarRotura select[name="tipo_falla"]');
    if (selFallaDet && dm.tipo_falla) {
        selFallaDet.value = dm.tipo_falla;
    }
    const areaInput = document.getElementById('rot_area');
    if (dm.diametro_1 > 0 && dm.diametro_2 > 0) {
        const dProm = (parseFloat(dm.diametro_1) + parseFloat(dm.diametro_2)) / 2.0;
        areaInput.value = ((Math.PI * Math.pow(dProm / 2.0, 2)) / 6.4516).toFixed(3);
    } else {
        areaInput.value = '';
    }
    areaInput.readOnly = true;

    if (document.getElementById('hc_d1')) document.getElementById('hc_d1').value = (dm.diametro_1 && dm.diametro_1 > 0) ? dm.diametro_1 : '';
    if (document.getElementById('hc_d2')) document.getElementById('hc_d2').value = (dm.diametro_2 && dm.diametro_2 > 0) ? dm.diametro_2 : '';
    if (document.getElementById('hc_l1')) document.getElementById('hc_l1').value = (dm.largo_1 && dm.largo_1 > 0) ? dm.largo_1 : '';
    if (document.getElementById('hc_l2')) document.getElementById('hc_l2').value = (dm.largo_2 && dm.largo_2 > 0) ? dm.largo_2 : '';
    if (document.getElementById('hc_masa')) document.getElementById('hc_masa').value = (dm.masa_kg && dm.masa_kg > 0) ? dm.masa_kg : '';

    document.getElementById('calc_psi').innerText = '--';
    document.getElementById('calc_kgcm2').innerText = '--';
    document.getElementById('box_justificacion_tolerancia').style.display = 'none';
    document.getElementById('rot_justificacion').value = '';

    if (dm.diametro_1 > 0 && dm.diametro_2 > 0) {
        recalcularDesdeHc61();
    } else {
        calcularEsfuerzoEnVivo();
    }
    verificarToleranciaTiempo();
    document.getElementById('modalRegistrarRotura').style.display = 'block';
}

function cerrarModalRegistrarRotura() {
    document.getElementById('modalRegistrarRotura').style.display = 'none';
}

function calcularEsfuerzoEnVivo() {
    const carga = parseFloat(document.getElementById('rot_carga')?.value || 0);
    const area = parseFloat(document.getElementById('rot_area')?.value || 0);
    const l1 = parseFloat(document.getElementById('hc_l1')?.value || 0);
    const l2 = parseFloat(document.getElementById('hc_l2')?.value || 0);
    const masa = parseFloat(document.getElementById('hc_masa')?.value || 0);
    const fechaMol = document.getElementById('rot_fecha_moldeo')?.value || '';
    const fechaReal = document.getElementById('rot_fecha_real')?.value || '';

    const elArea = document.getElementById('calc_area_in2');
    const elVol = document.getElementById('calc_vol_m3');
    const elPv = document.getElementById('calc_pv');
    const elEdad = document.getElementById('calc_edad_dias');
    const elPsi = document.getElementById('calc_psi');
    const elKg = document.getElementById('calc_kgcm2');

    if (elArea) elArea.innerText = area > 0 ? area.toFixed(3) : '--';

    const lProm = (l1 > 0 && l2 > 0) ? ((l1 + l2) / 2.0) : (l1 > 0 ? l1 : l2);
    if (area > 0 && lProm > 0) {
        const volM3 = (area * 6.4516 * lProm) / 1000000.0;
        if (elVol) elVol.innerText = volM3.toFixed(6);
        if (masa > 0) {
            const pvRaw = masa / volM3;
            const pv = LAB_REDONDEAR_PV_10 ? (Math.round(pvRaw / 10.0) * 10.0) : Math.round(pvRaw);
            if (elPv) elPv.innerText = pv.toFixed(0);
        } else if (elPv) {
            elPv.innerText = '--';
        }
    } else {
        if (elVol) elVol.innerText = '--';
        if (elPv) elPv.innerText = '--';
    }

    if (elEdad) {
        if (fechaMol && fechaReal) {
            const d1 = new Date(String(fechaMol).slice(0, 10) + 'T00:00:00');
            const d2 = new Date(String(fechaReal).slice(0, 10) + 'T00:00:00');
            const diff = (!isNaN(d1.getTime()) && !isNaN(d2.getTime())) ? Math.round((d2.getTime() - d1.getTime()) / 86400000) : null;
            elEdad.innerText = (diff !== null && diff >= 0) ? (diff + ' días') : '--';
        } else {
            elEdad.innerText = '--';
        }
    }

    if (carga > 0 && area > 0) {
        const psiBruto = carga / area;
        const psi = LAB_REDONDEAR_PSI_10 ? (Math.round(psiBruto / 10.0) * 10.0) : parseFloat(psiBruto.toFixed(2));
        const kgcm2 = Math.round(psi / LAB_DIVISOR_KGCM2);
        if (elPsi) elPsi.innerText = psi.toFixed(0) + ' psi';
        if (elKg) elKg.innerText = kgcm2.toFixed(0) + ' kgf/cm²';
    } else {
        if (elPsi) elPsi.innerText = '--';
        if (elKg) elKg.innerText = '--';
    }
}

function recalcularDesdeHc61() {
    const d1 = parseFloat(document.getElementById('hc_d1')?.value || 0);
    const d2 = parseFloat(document.getElementById('hc_d2')?.value || 0);
    const lectura = parseFloat(document.getElementById('hc_lectura_lbf')?.value || 0);
    const fmtSelect = document.getElementById('hc_codigo_formato');

    if (fmtSelect && !fmtSelect.value && lectura > 0) {
        fmtSelect.value = 'RT-FM-61';
    }

    if (d1 > 0 && d2 > 0) {
        const dProm = (d1 + d2) / 2.0;
        const areaIn2 = (Math.PI * Math.pow(dProm / 2.0, 2)) / 6.4516;
        document.getElementById('rot_area').value = areaIn2.toFixed(3);
    } else {
        document.getElementById('rot_area').value = '';
    }

    if (lectura > 0) {
        const cargaReal = -4.6765 * Math.pow(lectura / 100000.0, 3) * 10.0
                        + 2.5071 * Math.pow(lectura / 10000.0, 2)
                        + 0.99583 * lectura
                        + 3.7217;
        document.getElementById('rot_carga').value = cargaReal.toFixed(1);
    }

    calcularEsfuerzoEnVivo();
}

function verificarToleranciaTiempo() {
    const fechaRealStr = document.getElementById('rot_fecha_real').value;
    const fechaProgStr = document.getElementById('rot_fecha_prog').value;
    const tolHoras = parseFloat(document.getElementById('rot_tol_horas').value || 20);

    if (!fechaRealStr || !fechaProgStr) return;

    const tReal = new Date(fechaRealStr).getTime();
    const tProg = new Date(fechaProgStr + 'T08:00:00').getTime();
    const difHoras = Math.abs(tReal - tProg) / (1000 * 3600);

    const box = document.getElementById('box_justificacion_tolerancia');
    if (difHoras > tolHoras) {
        box.style.display = 'block';
        document.getElementById('rot_justificacion').required = true;
    } else {
        box.style.display = 'none';
        document.getElementById('rot_justificacion').required = false;
    }
}

function validarEnvioRotura(e) {
    const box = document.getElementById('box_justificacion_tolerancia');
    if (box.style.display !== 'none') {
        const just = document.getElementById('rot_justificacion').value.trim();
        if (just.length < 5) {
            e.preventDefault();
            alert('El ensayo se encuentra fuera de tolerancia ASTM C39. Debe ingresar una justificaciÃ³n tÃ©cnica obligatoria.');
            document.getElementById('rot_justificacion').focus();
            return false;
        }
    }
}

// Modal Devolver Informe
function abrirModalDevolverInforme(informeId) {
    document.getElementById('dev_informe_id').value = informeId;
    document.getElementById('modalDevolverInforme').style.display = 'block';
}

function cerrarModalDevolverInforme() {
    const modal = document.getElementById('modalDevolverInforme');
    if (modal) modal.style.display = 'none';
}
</script>

<!-- Modal 4: Reemitir Informe (Calidad / N+1) -->
<div id="modalReemitirInforme" class="modal-lab">
    <div class="modal-lab-content" style="max-width: 500px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">
            <h3 style="margin: 0; color: #1e293b; font-size: 16px; font-weight: 700;">
                <i class="fa-solid fa-arrows-rotate" style="color:#d97706;"></i> Reemitir Informe Oficial (ISO/IEC 17025)
            </h3>
            <button type="button" onclick="cerrarModalReemitirInforme()" style="border: none; background: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <form method="POST" action="/Cycsa/publico/laboratorio/reemitir-informe">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" id="reem_informe_id" name="informe_id" value="">
            <input type="hidden" name="muestra_id" value="<?= (int)$muestra['muestra_id'] ?>">

            <div style="background:#fffbeb; border:1px solid #fde68a; color:#92400e; padding:10px 14px; border-radius:6px; font-size:12.5px; margin-bottom:12px;">
                <i class="fa-solid fa-triangle-exclamation"></i> El informe actual <strong id="reem_informe_numero"></strong> quedará <strong>ANULADO</strong> y se generará una nueva versión que lo sustituye formalmente en el expediente.
            </div>

            <label style="display: block; font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 5px;">
                Motivo obligatorio de la reemisión:
            </label>
            <textarea name="motivo_reemision" rows="3" class="form-control" style="width: 100%; box-sizing: border-box; resize: vertical; padding: 8px 10px; font-size: 13px;" placeholder="Detalle la justificación técnica o administrativa..." required></textarea>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 15px;">
                <button type="button" class="btn-accion btn-secondary-lab" onclick="cerrarModalReemitirInforme()">Cancelar</button>
                <button type="submit" class="btn-accion btn-warning-lab">
                    <i class="fa-solid fa-arrows-rotate"></i> Reemitir y Sustituir
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalReemitirInforme(id, numero) {
    const idElem = document.getElementById('reem_informe_id');
    const numElem = document.getElementById('reem_informe_numero');
    if (idElem) idElem.value = id;
    if (numElem) numElem.innerText = numero;
    const modal = document.getElementById('modalReemitirInforme');
    if (modal) modal.style.display = 'block';
}

function cerrarModalReemitirInforme() {
    const modal = document.getElementById('modalReemitirInforme');
    if (modal) modal.style.display = 'none';
}

// Cerrar modales al hacer clic fuera del contenido
window.addEventListener('click', function(e) {
    const modales = [
        'modalPlanRotura',
        'modalRegistrarRotura',
        'modalDevolverInforme',
        'modalReemitirInforme'
    ];
    modales.forEach(function(mId) {
        const m = document.getElementById(mId);
        if (m && e.target === m) {
            m.style.display = 'none';
        }
    });
});
</script>
