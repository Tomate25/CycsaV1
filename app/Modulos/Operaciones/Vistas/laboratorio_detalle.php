<?php
// Laboratorio Detalle Muestra - Blind Specimen crush & dynamic matrix capturing
?>
<style>
    .lab-box { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 20px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    .form-group { margin-bottom: 14px; display: flex; flex-direction: column; gap: 4px; }
    .form-control { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 13.5px; outline: none; }
    .form-control:focus { border-color: var(--cycsa-azul); }

    .tabla-rupturas { width: 100%; border-collapse: collapse; margin-top: 15px; }
    .tabla-rupturas th { background-color: #f8fafc; color: #475569; padding: 12px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0; font-size: 11.5px; text-transform: uppercase; }
    .tabla-rupturas td { padding: 12px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; font-size: 13.5px; color: #334155; }
    .tabla-rupturas tbody tr:hover { background-color: #f8fafc; }

    .status-alert { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; text-transform: uppercase; }
    .status-cumple { background-color: #dcfce7; color: #15803d; }
    .status-alerta { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }
    .status-pendiente { background-color: #f1f5f9; color: #475569; }

    .modal-premium { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .modal-premium-content { background-color: #fff; margin: 4% auto; padding: 25px; border: 1px solid #e2e8f0; width: 65%; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }

    .btn-accion { border: none; background: none; cursor: pointer; padding: 8px 14px; border-radius: 6px; font-size: 13px; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; }
    .btn-os { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .btn-os:hover { background-color: #dbeafe; }
    .btn-recepcion { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .btn-recepcion:hover { background-color: #dcfce7; }
    .btn-detalle { background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
    .btn-detalle:hover { background-color: #e2e8f0; }
</style>

<div class="header-flex" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;">Hoja de Trabajo Ciega</h2>
        <p style="color: #64748b; margin-top: 5px; font-size: 14px;">Código de Laboratorio: <strong style="color: #0369a1; font-family: monospace;"><?= htmlspecialchars($lote['codigo_muestra'], ENT_QUOTES, 'UTF-8') ?></strong> | Código de Campo: <strong style="color: #475569; font-family: monospace;"><?= htmlspecialchars($lote['codigo_campo'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    </div>
    <a href="/Cycsa/publico/laboratorio" class="form-control" style="text-decoration: none; background: #f1f5f9; border: 1px solid #cbd5e1; font-weight: 600; color: #475569; display: inline-flex; align-items: center; gap: 6px; height: auto;"><i class="fa-solid fa-arrow-left"></i> Volver al Panel</a>
</div>

<!-- Alertas -->
<?php if (!empty($exito)): ?>
    <div class="alert alert-exito" style="background-color: #f0fdf4; color: #166534; padding: 12px; border-radius: 6px; border: 1px solid #bbf7d0; margin-bottom: 15px;">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-error" style="background-color: #fef2f2; color: #991b1b; padding: 12px; border-radius: 6px; border: 1px solid #fecaca; margin-bottom: 15px;">
        <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<div style="background-color: #fef3c7; border: 1px solid #fde68a; color: #92400e; padding: 12px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 500; margin-bottom: 20px; display: flex; align-items: center; gap: 10px;">
    <i class="fa-solid fa-eye-slash" style="font-size: 16px;"></i>
    <strong>IMPARCIALIDAD BLINDADA (ISO 17025):</strong> Los datos comerciales de esta cotización y del cliente se encuentran ocultos.
</div>

<!-- 1. ESPECIFICACIONES TÉCNICAS DEL LOTE -->
<div class="lab-box">
    <h4 style="font-family:'Outfit'; font-size:15px; font-weight:700; color:var(--cycsa-azul); border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 15px;"><i class="fa-solid fa-cube"></i> Especificaciones de la Muestra</h4>
    <div class="grid-3">
        <div>
            <span style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Identificación / Lote</span><br>
            <strong style="color:#1e293b;"><?= htmlspecialchars($lote['nombre_lote'], ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div>
            <span style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Fecha de Moldeo / Fabricación</span><br>
            <strong><?= htmlspecialchars(date('d/m/Y', strtotime($lote['fecha_moldeo'])), ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
        <div>
            <span style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Resistencia de Diseño Objetivo</span><br>
            <strong style="color:#1e293b; font-family:monospace;"><?= htmlspecialchars($lote['diseno_resistencia'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></strong>
        </div>
    </div>
    <div class="grid-3" style="margin-top: 15px;">
        <div>
            <span style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Revenimiento (Slump)</span><br>
            <span><?= htmlspecialchars($lote['revenimiento_in'] ? $lote['revenimiento_in'].' in' : '—', ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($lote['revenimiento_cm'] ? $lote['revenimiento_cm'].' cm' : '—', ENT_QUOTES, 'UTF-8') ?>)</span>
        </div>
        <div>
            <span style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Temperatura Muestra</span><br>
            <span><?= htmlspecialchars($lote['temperatura_c'] ? $lote['temperatura_c'].' °C' : '—', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div>
            <span style="font-size:11px; color:#64748b; font-weight:600; text-transform:uppercase;">Procedimiento de Muestreo</span><br>
            <span><?= htmlspecialchars($lote['procedimiento_muestreo'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
</div>

<!-- 2. CAPTURA MATRICIAL DINÁMICA DE RESULTADOS GENERALES -->
<div class="lab-box">
    <h4 style="font-family:'Outfit'; font-size:15px; font-weight:700; color:var(--cycsa-azul); border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 15px;"><i class="fa-solid fa-table-cells"></i> Registro de Resultados (Matriz de Ensayo)</h4>
    <div style="overflow-x: auto;">
        <table class="tabla-rupturas">
            <thead>
                <tr>
                    <th>Nombre de Ensayo</th>
                    <th>Norma de Referencia</th>
                    <th>Formato Técnico</th>
                    <th style="text-align: right;">Acciones de Laboratorio</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itemsOS as $it): ?>
                <tr>
                    <td style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($it['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="color: #64748b; font-family: monospace; font-size:12.5px;"><?= htmlspecialchars($it['norma_astm'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 500;"><?= htmlspecialchars($it['formato_nombre'] ?: 'Sin formato técnico', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="text-align: right; white-space: nowrap;">
                        <?php if (!empty($it['archivo_markdown'])): 
                            $tieneResultados = !empty($it['resultados_json']) && $it['resultados_json'] !== '[]';
                        ?>
                            <?php if ($tieneResultados): ?>
                                <span class="badge-estado" style="background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; margin-right: 8px; font-size: 11px; padding: 4px 8px; font-weight: 600;"><i class="fa-solid fa-circle-check"></i> Con Resultados</span>
                            <?php else: ?>
                                <span class="badge-estado" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; margin-right: 8px; font-size: 11px; padding: 4px 8px; font-weight: 600;"><i class="fa-solid fa-hourglass"></i> Pendiente</span>
                            <?php endif; ?>

                            <button class="btn-accion <?= $tieneResultados ? 'btn-recepcion' : 'btn-os' ?>" onclick="abrirModalResultados(<?= $it['id'] ?>, '<?= htmlspecialchars($it['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?>', '<?= $it['archivo_markdown'] ?>', <?= htmlspecialchars($it['resultados_json'] ?: '[]', ENT_QUOTES, 'UTF-8') ?>)">
                                <i class="fa-solid fa-list-check"></i> <?= $tieneResultados ? 'Editar Resultados' : 'Capturar Resultados' ?>
                            </button>
                        <?php else: ?>
                            <span style="color: #94a3b8; font-size:12px; font-style:italic;">Servicio de Ensayo General</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 3. ESPECÍMENES E HITOS DE RUPTURA (SOLO CONCRETO / EDADES EVALUADAS) -->
<?php 
// Filtrar si hay especímenes con edades > 0
$tieneEdades = false;
foreach ($especimenes as $e) {
    if ($e['edad_dias'] > 0) { $tieneEdades = true; break; }
}
if ($tieneEdades):
?>
<div class="lab-box">
    <h4 style="font-family:'Outfit'; font-size:15px; font-weight:700; color:var(--cycsa-azul); border-bottom: 1px solid #e2e8f0; padding-bottom: 8px; margin-bottom: 15px;"><i class="fa-solid fa-list-ol"></i> Hitos de Ruptura de Cilindros por Edad</h4>
    <div style="overflow-x: auto;">
        <table class="tabla-rupturas">
            <thead>
                <tr>
                    <th>Cilindro</th>
                    <th>Especificación / Ensayo</th>
                    <th>Edad Evaluada</th>
                    <th>Fecha Programada</th>
                    <th>Fecha Ensaye Real</th>
                    <th>Carga Última (Lbs)</th>
                    <th>Área Transversal (in²)</th>
                    <th>Esfuerzo PSI</th>
                    <th>Esfuerzo Kg/cm²</th>
                    <th>% Diseño</th>
                    <th>Estado de Ruptura</th>
                    <th style="text-align: right;">Cargar Ruptura</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($especimenes as $e): 
                    if ($e['edad_dias'] == 0) continue; // Muestra general
                    $est = $e['estado'];
                    $esAlerta = ($est == 'Completado' && !$e['cumple_norma']);
                ?>
                <tr style="<?= $esAlerta ? 'background-color: #fef2f2;' : '' ?>">
                    <td style="font-weight: 700; font-family: monospace; color: var(--cycsa-azul);"><?= htmlspecialchars($e['identificador_especimen'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 600; color: #334155;"><?= htmlspecialchars($e['nombre_ensayo'] ?? 'Resistencia del Concreto', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($e['edad_dias'], ENT_QUOTES, 'UTF-8') ?>d</td>
                    <td><?= htmlspecialchars(date('d/m/Y', strtotime($e['fecha_programada'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $e['fecha_ensaye_real'] ? htmlspecialchars(date('d/m/Y', strtotime($e['fecha_ensaye_real'])), ENT_QUOTES, 'UTF-8') : '—' ?></td>
                    <td style="font-family: monospace;"><?= $e['carga_lbs'] ? number_format($e['carga_lbs'], 1) : '—' ?></td>
                    <td style="font-family: monospace;"><?= $e['area_in2'] ? number_format($e['area_in2'], 3) : '—' ?></td>
                    <td style="font-weight: 600; font-family: monospace;"><?= $e['resistencia_psi'] ? number_format($e['resistencia_psi'], 0) : '—' ?></td>
                    <td style="font-weight: 600; font-family: monospace;"><?= $e['resistencia_kgcm2'] ? number_format($e['resistencia_kgcm2'], 1) : '—' ?></td>
                    <td style="font-weight: 700; color: <?= $esAlerta ? '#b91c1c' : '#475569' ?>; font-family: monospace;"><?= $e['porcentaje_diseno'] ? number_format($e['porcentaje_diseno'], 1).'%' : '—' ?></td>
                    <td>
                        <?php if ($est == 'Completado'): ?>
                            <?php if ($e['cumple_norma']): ?>
                                <span class="status-alert status-cumple"><i class="fa-solid fa-check-circle"></i> Cumple</span>
                            <?php else: ?>
                                <span class="status-alert status-alerta" title="El resultado no llega al <?= $e['porcentaje_minimo_esperado'] ?>% mínimo esperado"><i class="fa-solid fa-triangle-exclamation"></i> Alerta</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="status-alert status-pendiente"><i class="fa-solid fa-clock"></i> Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <?php if ($est == 'Programado' || $est == 'Listo para Ensaye'): ?>
                            <button class="btn-accion btn-recepcion" onclick="abrirRupturaModal(<?= $e['id'] ?>, '<?= htmlspecialchars($e['identificador_especimen'], ENT_QUOTES, 'UTF-8') ?>', <?= $e['edad_dias'] ?>, '', '', '<?= htmlspecialchars($e['nombre_ensayo'] ?? '', ENT_QUOTES, 'UTF-8') ?>')">
                                <i class="fa-solid fa-hammer"></i> Romper
                            </button>
                        <?php else: ?>
                            <button class="btn-accion btn-detalle" onclick="abrirRupturaModal(<?= $e['id'] ?>, '<?= htmlspecialchars($e['identificador_especimen'], ENT_QUOTES, 'UTF-8') ?>', <?= $e['edad_dias'] ?>, '<?= $e['carga_lbs'] ?>', '<?= $e['area_in2'] ?>', '<?= htmlspecialchars($e['nombre_ensayo'] ?? '', ENT_QUOTES, 'UTF-8') ?>')" title="Editar resultado">
                                <i class="fa-solid fa-edit"></i>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- 4. APARTADO DE CONTROL DE REPORTES Y VERSIONAMIENTO -->
<div class="lab-box">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--border-light); padding-bottom: 8px; margin-bottom: 15px;">
        <h4 style="font-family:'Outfit'; font-size:15px; font-weight:700; color:var(--cycsa-azul); margin: 0;"><i class="fa-solid fa-file-pdf"></i> Historial de Informes y Versionado</h4>
        <button class="btn-accion btn-os" onclick="abrirInformeModal()"><i class="fa-solid fa-file-circle-plus"></i> Generar Versión / Reporte</button>
    </div>
    
    <div style="overflow-x: auto; border: 1px solid var(--border-light); border-radius: 10px;">
        <table class="tabla-rupturas">
            <thead>
                <tr>
                    <th>Código Informe</th>
                    <th>Versión</th>
                    <th>Tipo</th>
                    <th>Fecha Generación</th>
                    <th>Aprobación</th>
                    <th>Motivo de Reemplazo (Versionado)</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historial as $h): ?>
                <tr>
                    <td style="font-family: monospace; font-weight: 700; color: #334155;"><?= htmlspecialchars($h['codigo_completo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 700; text-align: center;">v<?= htmlspecialchars($h['version'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 500;">
                        <?= htmlspecialchars($h['tipo_informe'], ENT_QUOTES, 'UTF-8') ?>
                        <?= ($h['tipo_informe'] === 'Parcial' && !empty($h['edad_evaluada'])) ? ' (' . $h['edad_evaluada'] . ' días)' : '' ?>
                    </td>
                    <td><?= htmlspecialchars($h['fecha_generacion'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="badge-estado estado-<?= str_replace(' ', '-', $h['estado_aprobacion']) ?>"><?= htmlspecialchars($h['estado_aprobacion'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td style="font-size:12.5px; color:#64748b; font-style:italic; max-width: 250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= $h['motivo_reemplazo'] ? htmlspecialchars($h['motivo_reemplazo'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                    <td style="text-align: right; white-space: nowrap;">
                        <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                            <?php 
                            $esTecnico = ($_SESSION['usuario_rol'] ?? 0) == 6;
                            if (!$esTecnico): 
                            ?>
                                <a href="/Cycsa/publico/informes/descargar?id=<?= codificarId($h['id']) ?>" class="btn-accion btn-detalle" target="_blank" style="padding: 6px 12px;"><i class="fa-solid fa-download"></i> Descargar PDF</a>
                                
                                <?php if ($h['estado_aprobacion'] === 'Pendiente'): ?>
                                    <form method="POST" action="/Cycsa/publico/operaciones/cambiar-estado-informe" style="display:inline; margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="id_informe" value="<?= codificarId($h['id']) ?>">
                                        <input type="hidden" name="id_lote" value="<?= codificarId($lote['id']) ?>">
                                        <input type="hidden" name="redireccionar_a" value="/Cycsa/publico/laboratorio/detalle-muestra?id_lote=<?= codificarId($lote['id']) ?>">
                                        <input type="hidden" name="nuevo_estado" value="Revisado">
                                        <button type="submit" class="btn-accion btn-os" style="padding: 6px 12px;" title="Marcar como Revisado"><i class="fa-solid fa-check"></i> Revisar</button>
                                    </form>
                                <?php elseif ($h['estado_aprobacion'] === 'Revisado'): ?>
                                    <form method="POST" action="/Cycsa/publico/operaciones/cambiar-estado-informe" style="display:inline; margin: 0;">
                                        <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                        <input type="hidden" name="id_informe" value="<?= $h['id'] ?>">
                                        <input type="hidden" name="id_lote" value="<?= $lote['id'] ?>">
                                        <input type="hidden" name="redireccionar_a" value="/Cycsa/publico/laboratorio/detalle-muestra?id_lote=<?= $lote['id'] ?>">
                                        <input type="hidden" name="nuevo_estado" value="Aprobado">
                                        <button type="submit" class="btn-accion btn-recepcion" style="padding: 6px 12px;" title="Aprobar"><i class="fa-solid fa-thumbs-up"></i> Aprobar</button>
                                    </form>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="font-size: 12px; color: #64748b; font-style: italic;"><i class="fa-solid fa-lock"></i> PDF Resguardado</span>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($historial)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: #64748b;">No se han generado reportes para este lote todavía.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL REGISTRAR RUPTURA -->
<div id="modalRuptura" class="modal-premium">
    <div class="modal-premium-content" style="width: 35%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700;">Ingreso de Cargas: <span id="lbl_tipo_especimen">Cilindro</span> <span id="lbl_especimen" style="color:var(--cycsa-azul);"></span> <span id="lbl_especimen_ensayo" style="color:#64748b; font-size:12px; font-weight:500; display:block; margin-top:4px;"></span></h3>
            <button onclick="cerrarRupturaModal()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/laboratorio/guardar-ruptura">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_ensayo" id="rup_id_ensayo">
            <input type="hidden" name="id_lote" value="<?= $lote['id'] ?>">

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Carga Última Aplicada (Lbs)</label>
                <input type="number" step="0.01" name="carga_lbs" id="rup_carga" required placeholder="Ej: 85200.00" class="form-control">
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Área de Sección Transversal (in²)</label>
                <input type="number" step="0.001" name="area_in2" id="rup_area" required placeholder="Ej: 28.274" class="form-control" value="28.274">
                <span style="font-size: 11px; color: #64748b; margin-top: 2px;"><i class="fa-solid fa-circle-info"></i> <strong>Tip de Área:</strong> Estándar 6"x12" = <strong>28.274</strong> | Estándar 4"x8" = <strong>12.566</strong> | Mortero 2" = <strong>4.000</strong></span>
            </div>

            <div id="rup_helper_note" style="margin-top: 15px; margin-bottom: 10px; padding: 10px 12px; border-radius: 6px; background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; font-size: 12px; line-height: 1.4; display: block;">
                <!-- Sugerencia dinámica en JS -->
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="cerrarRupturaModal()" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b;">Cancelar</button>
                <button type="submit" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600;">Guardar Datos</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CAPTURA MATRICIAL DINÁMICA DE ENSAYOS -->
<div id="modalResultadosEnsayo" class="modal-premium">
    <div class="modal-premium-content" style="width: 75%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 id="modal-titulo-ensayo" style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700;">Capturar Resultados</h3>
            <button onclick="cerrarModalResultados()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/cotizaciones/guardar-resultados-item" onsubmit="prepararJsonAntesDeEnviar(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_detalle" id="modal-id-detalle">
            <input type="hidden" name="id_cotizacion" value="<?= $lote['id_cotizacion'] ?? 0 ?>">
            <input type="hidden" name="redireccionar_a" value="/Cycsa/publico/laboratorio/detalle-muestra?id_lote=<?= $lote['id'] ?>">
            <input type="hidden" name="resultados_json" id="modal-resultados-json" value="">
            
            <p style="color: #64748b; font-size: 13px; margin-bottom: 15px;">Ingrese los valores correspondientes en la matriz del ensayo de laboratorio. Deje celdas vacías si no requiere usarlas.</p>
            
            <div id="container-limites-material" style="display:none; margin-bottom:15px; background-color: #f8fafc; border: 1px solid #e2e8f0; padding: 12px; border-radius: 6px; display: flex; align-items: center; gap: 10px;">
                <label style="font-weight: 600; font-size: 13px; color: #334155; margin: 0;">Aplicar Límites por Tipo de Material Geotécnico:</label>
                <select id="select-limites-material" class="form-control" style="background-color:white; width: auto; display:inline-block;" onchange="aplicarLimitesMaterial(this.value)">
                    <option value="">-- Seleccionar material --</option>
                    <option value="Arena colchon">Arena colchón</option>
                    <option value="Material Cero">Material Cero</option>
                    <option value="Material 1 1/2">Material 1 1/2"</option>
                    <option value="Material 1">Material 1"</option>
                    <option value="Material 3/4">Material 3/4"</option>
                    <option value="Material 1/2">Material 1/2"</option>
                    <option value="Material 3/8">Material 3/8"</option>
                    <option value="Suelo">Suelo</option>
                    <option value="Selecto">Selecto</option>
                    <option value="Selecto relleno tipo 1">Selecto relleno tipo 1</option>
                    <option value="Selecto relleno tipo 2">Selecto relleno tipo 2</option>
                    <option value="Mezcla relleno 1-2">Mezcla relleno 1-2</option>
                    <option value="Selecto Base A">Selecto Base A</option>
                    <option value="Selecto Base B">Selecto Base B</option>
                    <option value="Selecto Base C">Selecto Base C</option>
                    <option value="Selecto Base D">Selecto Base D</option>
                    <option value="Sub base A-1">Sub base A-1</option>
                </select>
            </div>
            
            <div style="overflow-x: auto; width: 100%; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 20px;">
                <table class="tabla-rupturas" style="margin-bottom: 0;" id="tabla-captura-resultados">
                    <thead id="tabla-captura-header">
                        <!-- Columnas dinámicas -->
                    </thead>
                    <tbody id="tabla-captura-body">
                        <!-- Filas de inputs -->
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center;">
                <button type="button" class="form-control" style="cursor: pointer; background: #f8fafc; border: 1px solid #cbd5e1; font-weight: 600; color: #475569; width: auto; padding: 10px 20px; display: inline-flex; align-items: center; gap: 6px;" onclick="agregarFilaResultados()"><i class="fa-solid fa-plus"></i> Agregar Fila</button>
                <div style="display: flex; gap: 12px;">
                    <button type="button" onclick="cerrarModalResultados()" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b; width: auto; padding: 10px 20px;">Cancelar</button>
                    <button type="submit" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600; width: auto; padding: 10px 24px;">Guardar Resultados</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL GENERAR INFORME / REPORTES -->
<div id="modalInforme" class="modal-premium">
    <div class="modal-premium-content" style="width: 40%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700;">Generar Versión de Reporte</h3>
            <button onclick="cerrarInformeModal()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/operaciones/generar-informe" onsubmit="return validarFormularioInforme(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_lote" value="<?= $lote['id'] ?>">
            <input type="hidden" name="redireccionar_a" value="/Cycsa/publico/laboratorio/detalle-muestra?id_lote=<?= $lote['id'] ?>">

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Seleccionar Ensayo para el Reporte</label>
                <select name="id_detalle" id="inf_id_detalle" required class="form-control" style="background-color: white;">
                    <option value="">-- Seleccionar ensayo --</option>
                    <?php foreach ($itemsOS as $it): ?>
                        <option value="<?= $it['id'] ?>" data-json='<?= htmlspecialchars($it['resultados_json'] ?: '[]', ENT_QUOTES, 'UTF-8') ?>'><?= htmlspecialchars($it['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Tipo de Informe</label>
                <select name="tipo_informe" id="inf_tipo_informe" required class="form-control" style="background-color: white;">
                    <option value="Parcial">Informe Parcial (Resultados por edad actual)</option>
                    <option value="Consolidado">Informe Consolidado Final (Lote completo)</option>
                </select>
            </div>

            <div class="form-group" id="grupo-edad-filtro" style="display: none;">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Edad a Reportar (Días)</label>
                <select name="edad_filtro" id="inf_edad_filtro" class="form-control" style="background-color: white;">
                    <option value="">-- Seleccionar edad --</option>
                    <?php 
                    $edadesUnicas = [];
                    foreach ($especimenes as $e) {
                        if ($e['edad_dias'] > 0 && !in_array($e['edad_dias'], $edadesUnicas)) {
                            $edadesUnicas[] = $e['edad_dias'];
                        }
                    }
                    sort($edadesUnicas);
                    foreach ($edadesUnicas as $edad):
                    ?>
                        <option value="<?= $edad ?>"><?= $edad ?> días</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="grupo-motivo" style="display: none;">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Motivo de Reemplazo (Nueva Versión)</label>
                <textarea name="motivo_reemplazo" id="inf_motivo" rows="2" placeholder="Describa el motivo del por qué genera una nueva versión..." class="form-control"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="cerrarInformeModal()" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b; width: auto; padding: 10px 20px;">Cancelar</button>
                <button type="submit" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600; width: auto; padding: 10px 24px;">Generar PDF & Versionar</button>
            </div>
        </form>
    </div>
</div>

<script>
    const rupModal = document.getElementById('modalRuptura');
    const modalResultados = document.getElementById('modalResultadosEnsayo');
    const FORMATOS_SCHEMA = <?= $formatosSchemaJson ?>;
    let columnasActuales = [];

    function abrirRupturaModal(idEnsayo, espName, edad, carga = '', area = '', nombreEnsayo = '') {
        document.getElementById('rup_id_ensayo').value = idEnsayo;
        document.getElementById('lbl_especimen').innerText = espName + ' (' + edad + ' días)';
        document.getElementById('rup_carga').value = carga;
        
        const ensayoSpan = document.getElementById('lbl_especimen_ensayo');
        if (ensayoSpan) {
            if (nombreEnsayo) {
                ensayoSpan.innerText = 'Ensayo: ' + nombreEnsayo;
                ensayoSpan.style.display = 'block';
            } else {
                ensayoSpan.style.display = 'none';
            }
        }

        // Determinar tipo de producto y sugerir área
        const txt = nombreEnsayo.toLowerCase();
        let defaultArea = '28.274'; // Cilindro concreto por defecto
        let suggestionHtml = '';
        let labelText = 'Cilindro';

        if (txt.includes('mortero')) {
            defaultArea = '4.000';
            suggestionHtml = '<i class="fa-solid fa-circle-info"></i> <strong>Sugerencia Mortero:</strong> Cubo de Mortero 2"x2" (Área = 4.000 in²).';
            labelText = 'Cubo (Mortero)';
        } else if (txt.includes('bloque') || txt.includes('adoquin') || txt.includes('adoquín') || txt.includes('ladrillo')) {
            defaultArea = ''; // Vacío para forzar ingreso
            suggestionHtml = '<i class="fa-solid fa-circle-info"></i> <strong>Sugerencia Mampostería:</strong> Ingrese el área transversal real calculada para este elemento.';
            labelText = 'Mampostería (Bloque/Adoquín)';
        } else if (txt.includes('núcleo') || txt.includes('nucleo')) {
            defaultArea = '12.566'; // Diámetro 4"
            suggestionHtml = '<i class="fa-solid fa-circle-info"></i> <strong>Sugerencia Núcleo:</strong> Núcleo de Concreto - Diámetro 4" (12.566 in²) o 3" (7.069 in²).';
            labelText = 'Núcleo';
        } else if (txt.includes('viga') || txt.includes('flexión') || txt.includes('flexion')) {
            defaultArea = '';
            suggestionHtml = '<i class="fa-solid fa-circle-info"></i> <strong>Sugerencia Viga:</strong> Flexión de Viga. Ingrese el área o carga según la norma del ensayo.';
            labelText = 'Viga';
        } else {
            defaultArea = '28.274';
            suggestionHtml = '<i class="fa-solid fa-circle-info"></i> <strong>Sugerencia Concreto:</strong> Cilindro de Concreto 6"x12" (Área = 28.274 in²) o 4"x8" (12.566 in²).';
            labelText = 'Cilindro';
        }

        // Si se está editando y ya tiene un área registrada, respetarla
        if (area !== '' && area !== null && area !== undefined && area != 0) {
            document.getElementById('rup_area').value = area;
        } else {
            document.getElementById('rup_area').value = defaultArea;
        }

        document.getElementById('rup_area').placeholder = defaultArea ? 'Ej: ' + defaultArea : 'Ej: 28.274';
        document.getElementById('lbl_tipo_especimen').innerText = labelText;
        
        const helperDiv = document.getElementById('rup_helper_note');
        if (helperDiv) {
            helperDiv.innerHTML = suggestionHtml;
        }

        rupModal.style.display = 'block';
    }

    function cerrarRupturaModal() {
        rupModal.style.display = 'none';
    }

    const DEFAULT_ROWS_BY_FORMAT = {
        "formato_de_granulometria_de_suelo.md": [
            { "Malla": "2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1 1/2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/4\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1/2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/8\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 4", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 8", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 10", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 16", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 20", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 30", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 40", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 50", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 60", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 80", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 100", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 140", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 200", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Fondo", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Pérdida lavado", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Suma", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Límite Líquido", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Límite Plástico", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "I.P", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" }
        ],
        "granulomnetria_de_agregados.md": [
            { "Malla": "2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1 1/2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/4\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1/2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/8\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 4", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 8", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 10", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 16", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 20", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 30", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 40", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 50", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 60", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 80", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 100", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 140", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 200", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Fondo", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Pérdida lavado", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Suma", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Límite Líquido", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Límite Plástico", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "I.P", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" }
        ]
    };

    const LIMITS_DB = {
        "Arena colchon": {
            "min": { "3/8\"": 100, "No. 4": 100, "No. 10": 85, "No. 200": 0 },
            "max": { "No. 200": 3 }
        },
        "Material Cero": {
            "min": { "1 1/2\"": 100, "1\"": 90, "3/4\"": 35, "1/2\"": 20, "3/8\"": 10, "No. 4": 0, "No. 8": 80, "No. 16": 50, "No. 30": 25, "No. 50": 5, "No. 100": 0, "No. 200": 0 },
            "max": { "1\"": 100, "3/4\"": 70, "1/2\"": 50, "3/8\"": 30, "No. 4": 100, "No. 8": 100, "No. 16": 85, "No. 30": 60, "No. 50": 30, "No. 100": 10, "No. 200": 5 }
        },
        "Material 1 1/2": {
            "min": { "2\"": 100, "1 1/2\"": 71, "1\"": 75, "3/4\"": 38, "1/2\"": 53, "3/8\"": 30, "No. 4": 25, "No. 10": 15, "No. 40": 8, "No. 200": 2 },
            "max": { "2\"": 100, "1\"": 100, "3/4\"": 100, "1/2\"": 77.5, "3/8\"": 55, "No. 4": 5, "No. 8": 0, "No. 200": 0 }
        },
        "Material 1": {
            "min": { "1\"": 100, "3/4\"": 90, "1/2\"": 55, "3/8\"": 40, "No. 4": 25, "No. 10": 15, "No. 40": 8, "No. 200": 2 },
            "max": { "1\"": 100, "3/4\"": 100, "1/2\"": 100, "3/8\"": 55, "No. 4": 10, "No. 8": 5, "No. 200": 0 }
        },
        "Material 3/4": {
            "min": { "3/4\"": 100, "1/2\"": 90, "3/8\"": 85, "No. 4": 0, "No. 8": 0, "No. 10": 0, "No. 16": 0, "No. 200": 0 },
            "max": { "3/4\"": 100, "1/2\"": 100, "3/8\"": 70, "No. 4": 15, "No. 8": 5, "No. 16": 0 }
        },
        "Material 1/2": {
            "min": { "1/2\"": 100, "3/8\"": 100, "No. 4": 10, "No. 8": 0, "No. 16": 0, "No. 200": 0 },
            "max": { "1/2\"": 100, "3/8\"": 100, "No. 4": 30, "No. 8": 10, "No. 16": 0 }
        },
        "Material 3/8": {
            "min": { "3/8\"": 100, "No. 4": 100, "No. 10": 100, "No. 16": 100, "No. 30": 100, "No. 40": 100, "No. 50": 100, "No. 60": 100, "No. 80": 100, "No. 100": 100, "No. 140": 100, "No. 200": 100 },
            "max": { "3/8\"": 100, "No. 4": 100, "No. 10": 100, "No. 16": 100, "No. 30": 100, "No. 40": 100, "No. 50": 100, "No. 60": 100, "No. 80": 100, "No. 100": 100, "No. 140": 100, "No. 200": 100 }
        },
        "Suelo": {
            "min": { "2\"": 100, "No. 4": 12, "No. 10": 7, "No. 40": 4, "No. 200": 0 },
            "max": { "2\"": 100, "No. 4": 40, "No. 10": 29, "No. 40": 21, "No. 200": 16 }
        },
        "Selecto": {
            "min": { "1\"": 75, "1/2\"": 50, "No. 4": 30, "No. 10": 20, "No. 40": 10, "No. 200": 0 },
            "max": { "1\"": 95, "1/2\"": 80, "No. 4": 65, "No. 10": 50, "No. 40": 35, "No. 200": 16 }
        },
        "Selecto relleno tipo 1": {
            "min": { "2\"": 100, "1\"": 75, "No. 4": 90, "No. 10": 75, "No. 40": 50, "No. 200": 0 },
            "max": { "2\"": 100, "1\"": 100, "No. 4": 100, "No. 10": 90, "No. 40": 65, "No. 200": 35 }
        },
        "Selecto relleno tipo 2": {
            "min": { "2\"": 100, "1\"": 65, "No. 4": 50, "No. 10": 30, "No. 40": 23, "No. 200": 0 },
            "max": { "2\"": 100, "1\"": 90, "No. 4": 80, "No. 10": 63, "No. 40": 46, "No. 200": 20 }
        },
        "Mezcla relleno 1-2": {
            "min": { "2\"": 97, "No. 4": 25, "No. 10": 15, "No. 40": 8, "No. 200": 2 },
            "max": { "2\"": 100, "No. 4": 55, "No. 10": 40, "No. 40": 20, "No. 200": 8 }
        },
        "Selecto Base A": {
            "min": { "No. 4": 30, "No. 10": 20, "No. 40": 15, "No. 200": 5 },
            "max": { "No. 4": 60, "No. 10": 45, "No. 40": 30, "No. 200": 15 }
        },
        "Selecto Base B": {
            "min": { "No. 4": 35, "No. 10": 25, "No. 40": 15, "No. 200": 5 },
            "max": { "No. 4": 65, "No. 10": 50, "No. 40": 30, "No. 200": 15 }
        },
        "Selecto Base C": {
            "min": { "No. 4": 50, "No. 10": 40, "No. 40": 25, "No. 200": 8 },
            "max": { "No. 4": 85, "No. 10": 70, "No. 40": 45, "No. 200": 15 }
        },
        "Selecto Base D": {
            "min": { "No. 4": 28, "No. 10": 22, "No. 200": 5 },
            "max": { "No. 4": 40, "No. 10": 52, "No. 200": 20 }
        },
        "Sub base A-1": {
            "min": { "2\"": 100, "1\"": 65, "No. 4": 28, "No. 10": 22, "No. 200": 5 },
            "max": { "2\"": 100, "1\"": 79, "No. 4": 40, "No. 10": 52, "No. 200": 20 }
        }
    };

    function aplicarLimitesMaterial(matName) {
        if (!matName) return;
        const limits = LIMITS_DB[matName];
        if (!limits) return;
        
        const rows = document.querySelectorAll('#tabla-captura-body tr');
        rows.forEach(tr => {
            const descInput = tr.querySelector('input[data-col="Malla"]');
            const minInput = tr.querySelector('input[data-col="Límite Mín"]');
            const maxInput = tr.querySelector('input[data-col="Límite Máx"]');
            if (descInput && minInput && maxInput) {
                const malla = descInput.value.trim();
                const minVal = limits.min[malla] !== undefined ? limits.min[malla] : '';
                const maxVal = limits.max[malla] !== undefined ? limits.max[malla] : '';
                minInput.value = minVal;
                maxInput.value = maxVal;
            }
        });
    }

    function recalculateGranulometria() {
        const rows = document.querySelectorAll('#tabla-captura-body tr');
        let totalSuma = 0;
        let rowsArray = [];
        
        rows.forEach(tr => {
            const rowData = {};
            const inputs = tr.querySelectorAll('input');
            inputs.forEach(input => {
                rowData[input.dataset.col] = input;
            });
            rowsArray.push(rowData);
        });
        
        // Sum weights
        rowsArray.forEach(row => {
            const malla = row['Malla'] ? row['Malla'].value.trim() : '';
            if (malla && !['Suma', 'Límite Líquido', 'Límite Plástico', 'I.P'].includes(malla)) {
                const wInput = row['P. Retenido parcial (gr)'];
                if (wInput) {
                    const w = parseFloat(wInput.value) || 0;
                    totalSuma += w;
                }
            }
        });
        
        // Suma row
        const sumaRow = rowsArray.find(r => r['Malla'] && r['Malla'].value.trim() === 'Suma');
        if (sumaRow && sumaRow['P. Retenido parcial (gr)']) {
            sumaRow['P. Retenido parcial (gr)'].value = totalSuma > 0 ? totalSuma.toFixed(4) : '';
            if (sumaRow['% Retenido parcial']) sumaRow['% Retenido parcial'].value = totalSuma > 0 ? '100.00' : '';
            if (sumaRow['% Acumulativo']) sumaRow['% Acumulativo'].value = totalSuma > 0 ? '100.00' : '';
            if (sumaRow['% que pasa la malla']) sumaRow['% que pasa la malla'].value = totalSuma > 0 ? '0.00' : '';
        }
        
        // Sieve percentages
        let accumPercent = 0;
        rowsArray.forEach(row => {
            const malla = row['Malla'] ? row['Malla'].value.trim() : '';
            if (malla && !['Suma', 'Límite Líquido', 'Límite Plástico', 'I.P'].includes(malla)) {
                const wInput = row['P. Retenido parcial (gr)'];
                const rpInput = row['% Retenido parcial'];
                const acInput = row['% Acumulativo'];
                const qpInput = row['% que pasa la malla'];
                
                if (wInput && wInput.value !== '') {
                    const w = parseFloat(wInput.value) || 0;
                    const percent = totalSuma > 0 ? (w / totalSuma) * 100 : 0;
                    if (rpInput) rpInput.value = percent.toFixed(2);
                    
                    accumPercent += percent;
                    if (acInput) acInput.value = accumPercent.toFixed(2);
                    if (qpInput) qpInput.value = Math.max(0, 100 - accumPercent).toFixed(2);
                } else {
                    if (rpInput) rpInput.value = '';
                    if (acInput) acInput.value = '';
                    if (qpInput) qpInput.value = '';
                }
            }
        });
        
        // IP calculation
        const llRow = rowsArray.find(r => r['Malla'] && r['Malla'].value.trim() === 'Límite Líquido');
        const lpRow = rowsArray.find(r => r['Malla'] && r['Malla'].value.trim() === 'Límite Plástico');
        const ipRow = rowsArray.find(r => r['Malla'] && r['Malla'].value.trim() === 'I.P');
        
        if (llRow && lpRow && ipRow) {
            const llVal = parseFloat(llRow['% que pasa la malla'] ? llRow['% que pasa la malla'].value : 0) || 0;
            const lpVal = parseFloat(lpRow['% que pasa la malla'] ? lpRow['% que pasa la malla'].value : 0) || 0;
            const ipVal = Math.max(0, llVal - lpVal);
            if (ipRow['% que pasa la malla']) {
                ipRow['% que pasa la malla'].value = (llVal > 0 || lpVal > 0) ? ipVal.toFixed(2) : '';
            }
        }

        // Validación Automática Condicional: Pérdida por Lavado (Máximo 0.30%)
        let alertaLavado = document.getElementById('alerta-lavado-norma');
        if (!alertaLavado) {
            alertaLavado = document.createElement('div');
            alertaLavado.id = 'alerta-lavado-norma';
            alertaLavado.style.marginTop = '15px';
            alertaLavado.style.padding = '12px 16px';
            alertaLavado.style.borderRadius = '8px';
            alertaLavado.style.fontSize = '13px';
            alertaLavado.style.fontWeight = '600';
            const tableBody = document.getElementById('tabla-captura-body');
            if (tableBody && tableBody.closest('form')) {
                tableBody.closest('form').insertBefore(alertaLavado, document.getElementById('modal-resultados-json'));
            }
        }

        const perdidaRow = rowsArray.find(r => r['Malla'] && (r['Malla'].value.trim().toLowerCase().includes('pérdida') || r['Malla'].value.trim().toLowerCase().includes('perdida')));
        if (perdidaRow) {
            const valPercent = parseFloat(perdidaRow['% Retenido parcial']?.value || 0);
            if (valPercent > 0.30) {
                alertaLavado.style.display = 'block';
                alertaLavado.style.backgroundColor = '#fef2f2';
                alertaLavado.style.color = '#991b1b';
                alertaLavado.style.border = '1px solid #fecaca';
                alertaLavado.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> <strong>RECHAZO AUTOMÁTICO DE NORMA:</strong> Pérdida por Lavado (' + valPercent.toFixed(2) + '%) supera el límite máximo permitido de 0.30%. El ensayo requiere repetición obligatoria.';
            } else if (valPercent > 0) {
                alertaLavado.style.display = 'block';
                alertaLavado.style.backgroundColor = '#f0fdf4';
                alertaLavado.style.color = '#166534';
                alertaLavado.style.border = '1px solid #bbf7d0';
                alertaLavado.innerHTML = '<i class="fa-solid fa-circle-check"></i> <strong>VALIDACIÓN CONFORME:</strong> Pérdida por Lavado (' + valPercent.toFixed(2) + '%) cumple dentro del máximo normado de 0.30%.';
            } else {
                alertaLavado.style.display = 'none';
            }
        }
    }

    function recalculateIP() {
        // Obsoleted but kept for legacy views
    }

    function agregarFilaResultados() {
        const bodyContainer = document.getElementById('tabla-captura-body');
        const tr = document.createElement('tr');
        
        columnasActuales.forEach(col => {
            const td = document.createElement('td');
            td.style.padding = '6px';
            
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control';
            input.style.width = '100%';
            input.style.padding = '6px 10px';
            input.style.fontSize = '13px';
            input.style.boxSizing = 'border-box';
            input.style.borderRadius = '4px';
            input.style.border = '1px solid #cbd5e1';
            input.value = '';
            input.dataset.col = col;
            
            td.appendChild(input);
            tr.appendChild(td);
        });
        bodyContainer.appendChild(tr);
    }

    function abrirModalResultados(idDetalle, nombreEnsayo, archivoMarkdown, resultadosJson) {
        document.getElementById('modal-id-detalle').value = idDetalle;
        document.getElementById('modal-titulo-ensayo').innerText = 'Capturar Resultados: ' + nombreEnsayo;
        
        const schema = FORMATOS_SCHEMA[archivoMarkdown] || { columns: [] };
        columnasActuales = schema.columns;
        
        if (columnasActuales.length === 0) {
            columnasActuales = ["Código laboratorio", "Nombre muestra", "Resultado"];
        }

        // Render header
        const headerRow = document.createElement('tr');
        columnasActuales.forEach(col => {
            const th = document.createElement('th');
            th.innerText = col;
            th.style.padding = '10px';
            th.style.fontSize = '11.5px';
            th.style.backgroundColor = '#f8fafc';
            headerRow.appendChild(th);
        });
        const headerContainer = document.getElementById('tabla-captura-header');
        headerContainer.innerHTML = '';
        headerContainer.appendChild(headerRow);

        // Parse existing rows
        let filasExistentes = [];
        try {
            if (typeof resultadosJson === 'string') {
                filasExistentes = JSON.parse(resultadosJson);
            } else if (Array.isArray(resultadosJson)) {
                filasExistentes = resultadosJson;
            }
        } catch (e) {
            filasExistentes = [];
        }

        if (!Array.isArray(filasExistentes)) filasExistentes = [];

        // Determine default rows if empty
        const defaultRows = DEFAULT_ROWS_BY_FORMAT[archivoMarkdown] || [];
        const totalRowsToShow = Math.max(filasExistentes.length, defaultRows.length, 5);

        // Show/hide limits select dropdown
        const divLimites = document.getElementById('container-limites-material');
        if (divLimites) {
            if (archivoMarkdown.includes('granulometria') || archivoMarkdown.includes('granulomnetria')) {
                divLimites.style.display = 'flex';
                document.getElementById('select-limites-material').value = '';
            } else {
                divLimites.style.display = 'none';
            }
        }

        // Build rows of inputs
        const bodyContainer = document.getElementById('tabla-captura-body');
        bodyContainer.innerHTML = '';
        
        for (let r = 0; r < totalRowsToShow; r++) {
            const rowData = filasExistentes[r] || defaultRows[r] || {};
            const tr = document.createElement('tr');
            
            columnasActuales.forEach(col => {
                const td = document.createElement('td');
                td.style.padding = '6px';
                
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.style.width = '100%';
                input.style.padding = '6px 10px';
                input.style.fontSize = '13px';
                input.style.boxSizing = 'border-box';
                input.style.borderRadius = '4px';
                input.style.border = '1px solid #cbd5e1';
                input.value = rowData[col] || '';
                input.dataset.col = col;
                
                // Formato granulometría
                const isGranulometria = (archivoMarkdown.includes('granulometria') || archivoMarkdown.includes('granulomnetria'));
                if (isGranulometria) {
                    const mallaName = rowData['Malla'] || '';
                    if (col === 'Malla') {
                        input.readOnly = true;
                        input.style.backgroundColor = '#f8fafc';
                        input.style.fontWeight = 'bold';
                    }
                    
                    if (['% Retenido parcial', '% Acumulativo'].includes(col)) {
                        input.readOnly = true;
                        input.style.backgroundColor = '#f1f5f9';
                    }
                    
                    if (col === '% que pasa la malla') {
                        if (['Límite Líquido', 'Límite Plástico'].includes(mallaName)) {
                            // Editable para LL y LP
                            input.style.fontWeight = 'bold';
                            input.addEventListener('input', recalculateGranulometria);
                        } else if (mallaName === 'I.P') {
                            input.readOnly = true;
                            input.style.backgroundColor = '#e2e8f0';
                            input.style.fontWeight = 'bold';
                        } else {
                            // Calculado
                            input.readOnly = true;
                            input.style.backgroundColor = '#f1f5f9';
                        }
                    }
                    
                    if (col === 'P. Retenido parcial (gr)') {
                        if (['Límite Líquido', 'Límite Plástico', 'I.P'].includes(mallaName)) {
                            input.readOnly = true;
                            input.style.backgroundColor = '#f1f5f9';
                            input.value = '—';
                        } else if (mallaName === 'Suma') {
                            input.readOnly = true;
                            input.style.backgroundColor = '#e2e8f0';
                            input.style.fontWeight = 'bold';
                        } else {
                            input.addEventListener('input', recalculateGranulometria);
                        }
                    }
                }
                
                td.appendChild(input);
                tr.appendChild(td);
            });
            bodyContainer.appendChild(tr);
        }

        // Bind auto-calculations on input changes (Legacy/fallback formats)
        const isGranulometria = (archivoMarkdown.includes('granulometria') || archivoMarkdown.includes('granulomnetria'));
        if (!isGranulometria) {
            bodyContainer.querySelectorAll('tr').forEach(tr => {
                const descInput = tr.querySelector('input[data-col="Descripción"]');
                const resInput = tr.querySelector('input[data-col="Resultado"]');
                if (descInput && resInput) {
                    const descVal = descInput.value.trim().toLowerCase();
                    if (descVal === 'i.p' || descVal === 'ip') {
                        resInput.readOnly = true;
                        resInput.style.backgroundColor = '#f1f5f9';
                        resInput.style.fontWeight = 'bold';
                    } else if (descVal === 'límite líquido' || descVal === 'límite liquido' || descVal === 'límite liquido.' || descVal === 'límite plástico' || descVal === 'límite plastico' || descVal === 'límite plástico.') {
                        resInput.addEventListener('input', recalculateIP);
                    }
                }
            });
        } else {
            recalculateGranulometria();
        }

        modalResultados.style.display = 'block';
    }

    function cerrarModalResultados() {
        modalResultados.style.display = 'none';
    }

    function prepararJsonAntesDeEnviar(event) {
        const filas = [];
        const rows = document.querySelectorAll('#tabla-captura-body tr');
        
        rows.forEach(tr => {
            const rowData = {};
            let tieneDatos = false;
            const inputs = tr.querySelectorAll('input');
            
            inputs.forEach(input => {
                const col = input.dataset.col;
                const val = input.value.trim();
                rowData[col] = val;
                if (val !== '') {
                    tieneDatos = true;
                }
            });
            
            if (tieneDatos) {
                filas.push(rowData);
            }
        });
        
        document.getElementById('modal-resultados-json').value = JSON.stringify(filas);
    }

    const infModal = document.getElementById('modalInforme');
    
    function abrirInformeModal() {
        infModal.style.display = 'block';
        checkVersionReason();
    }
    
    function cerrarInformeModal() {
        infModal.style.display = 'none';
    }
    
    const historialInformes = <?= json_encode($historial) ?>;

    document.getElementById('inf_id_detalle')?.addEventListener('change', checkVersionReason);
    document.getElementById('inf_tipo_informe')?.addEventListener('change', checkVersionReason);
    document.getElementById('inf_edad_filtro')?.addEventListener('change', checkVersionReason);
    
    function checkVersionReason() {
        const idDetalle = document.getElementById('inf_id_detalle').value;
        const tipo = document.getElementById('inf_tipo_informe').value;
        const grupoMotivo = document.getElementById('grupo-motivo');
        const grupoEdad = document.getElementById('grupo-edad-filtro');
        
        if (tipo === 'Parcial') {
            grupoEdad.style.display = 'block';
            document.getElementById('inf_edad_filtro').required = true;
        } else {
            grupoEdad.style.display = 'none';
            document.getElementById('inf_edad_filtro').required = false;
        }
        
        const edadFiltro = document.getElementById('inf_edad_filtro').value;
        
        let tienePrevio = false;
        for (let i = 0; i < historialInformes.length; i++) {
            const h = historialInformes[i];
            if (h.tipo_informe === tipo) {
                if (tipo === 'Parcial') {
                    if (h.edad_evaluada == edadFiltro && edadFiltro !== '') {
                        tienePrevio = true;
                        break;
                    }
                } else {
                    if (h.edad_evaluada === null || h.edad_evaluada === '') {
                        tienePrevio = true;
                        break;
                    }
                }
            }
        }
        
        if (tienePrevio) {
            grupoMotivo.style.display = 'block';
            document.getElementById('inf_motivo').required = true;
        } else {
            grupoMotivo.style.display = 'none';
            document.getElementById('inf_motivo').required = false;
        }
    }
    
    function validarFormularioInforme(event) {
        const select = document.getElementById('inf_id_detalle');
        const opt = select.options[select.selectedIndex];
        
        const textoEnsayo = opt.text.toLowerCase();
        const esRotura = textoEnsayo.includes('compresión') || textoEnsayo.includes('flexión') || 
                          textoEnsayo.includes('mortero') || textoEnsayo.includes('núcleo') || 
                          textoEnsayo.includes('concreto') || textoEnsayo.includes('adoquín') || 
                          textoEnsayo.includes('bloque') || textoEnsayo.includes('ladrillo');
        
        if (esRotura) {
            return true;
        }

        const jsonStr = opt.getAttribute('data-json') || '[]';
        let filas = [];
        try {
            filas = JSON.parse(jsonStr);
        } catch(e) {
            filas = [];
        }
        
        if (filas.length === 0) {
            alert('Atención: No puede generar un informe para un ensayo que no tiene resultados cargados. Capture la matriz de resultados primero.');
            event.preventDefault();
            return false;
        }
        return true;
    }

    window.addEventListener('click', (e) => {
        if (e.target === rupModal) cerrarRupturaModal();
        if (e.target === modalResultados) cerrarModalResultados();
        if (e.target === infModal) cerrarInformeModal();
    });
</script>
