<?php
// Vista para la Programación de Muestreo en Campo y Lista de Chequeo Oficial (CYCSA-RT-FM-40 B)
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$pm = $os['programacion_muestreo'] ?? null;
$chk = !empty($pm['checklist_json']) ? json_decode($pm['checklist_json'], true) : [];
?>
<style>
    .cycsa-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); padding: 25px; margin-bottom: 25px; }
    .cycsa-card-title { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: var(--cycsa-azul); border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px; display: flex; align-items: center; justify-content: space-between; }
    
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; }
    .form-group-cycsa { margin-bottom: 15px; }
    .form-group-cycsa label { font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px; }
    .form-control-cycsa { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 13.5px; box-sizing: border-box; transition: all 0.2s; }
    .form-control-cycsa:focus { outline: none; border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08); }

    .btn-cycsa { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid transparent; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .btn-cycsa-primary { background: var(--cycsa-azul); color: white; }
    .btn-cycsa-primary:hover { background: #0c2766; color: white; }
    .btn-cycsa-secondary { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .btn-cycsa-secondary:hover { background: #cbd5e1; color: #0f172a; }
    .btn-cycsa-success { background: #10b981; color: white; }
    .btn-cycsa-success:hover { background: #059669; color: white; }

    .badge-status { padding: 6px 14px; border-radius: 20px; font-weight: 700; font-size: 12px; background: #fef3c7; color: #92400e; border: 1px solid #fde68a; display: inline-flex; align-items: center; gap: 6px; }

    /* Estilos para la tabla interactiva del Checklist */
    .chk-table { width: 100%; border-collapse: collapse; font-size: 12.5px; }
    .chk-table th, .chk-table td { padding: 7px 10px; border: 1px solid #e2e8f0; vertical-align: middle; }
    .chk-table th { background: #f8fafc; font-weight: 700; color: #475569; text-transform: uppercase; font-size: 11px; }
    .chk-table tr:hover { background: #f8fafc; }
    .chk-input-cant { width: 60px; padding: 4px 6px; text-align: center; border: 1px solid #cbd5e1; border-radius: 4px; font-size: 12px; }
    .chk-checkbox { width: 18px; height: 18px; cursor: pointer; }
</style>

<div style="max-width: 1150px; margin: 0 auto; padding-bottom: 50px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-size: 22px; font-family: 'Outfit', sans-serif; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-truck-pickup" style="color: var(--cycsa-azul);"></i> Programación y Lista de Chequeo de Campo
            </h2>
            <p style="color: #64748b; margin-top: 4px; font-size: 13.5px; margin-bottom: 0;">
                Orden de Servicio: <strong style="color: var(--cycsa-azul); font-family:monospace; font-size:15px;"><?= htmlspecialchars($os['codigo_os']) ?></strong>
                &nbsp;|&nbsp; 
                Formato Oficial de Muestreo: <strong style="color:#0284c7;">CYCSA-RT-FM-40 B (Rev. 2)</strong>
            </p>
        </div>
        <div style="display: flex; gap: 10px;">
            <a href="/Cycsa/publico/ordenes-servicio/imprimir-checklist?id=<?= $os['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-secondary" title="Imprimir formato físico oficial">
                <i class="fa-solid fa-print"></i> Imprimir CYCSA-RT-FM-40 B
            </a>
            <a href="/Cycsa/publico/ordenes-servicio/detalle?id=<?= $os['id'] ?>" class="btn-cycsa btn-cycsa-secondary">
                <i class="fa-solid fa-eye"></i> Ver O/S
            </a>
        </div>
    </div>

    <!-- TARJETA DE RESUMEN DE ORDEN -->
    <div class="cycsa-card" style="background: #f8fafc;">
        <div class="form-grid-3">
            <div>
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">CLIENTE / EMPRESA:</span>
                <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 2px;"><?= htmlspecialchars($os['cliente_nombre']) ?></div>
            </div>
            <div>
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">PROYECTO:</span>
                <div style="font-size: 13.5px; color: #334155; margin-top: 2px;"><?= htmlspecialchars($os['nombre_proyecto']) ?></div>
            </div>
            <div>
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">ESTADO OPERATIVO:</span>
                <div style="margin-top: 2px;">
                    <span class="badge-status">
                        <i class="fa-solid fa-clock"></i> <?= htmlspecialchars($pm['estado_muestreo'] ?? $os['estado']) ?>
                    </span>
                </div>
            </div>
        </div>
    </div>

    <form action="/Cycsa/publico/ordenes-servicio/guardar-muestreo" method="POST" id="form-programacion-muestreo">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="id_os" value="<?= $os['id'] ?>">

        <!-- CARD 1: ASIGNACIÓN DE LOGÍSTICA DE CAMPO -->
        <div class="cycsa-card">
            <div class="cycsa-card-title">
                <span><i class="fa-solid fa-calendar-days"></i> 1. Logística y Asignación de Recursos en Campo</span>
            </div>

            <div class="form-grid-2">
                <div class="form-group-cycsa">
                    <label>Fecha y Hora Programada de Salida a Campo:</label>
                    <input type="datetime-local" name="fecha_ida" class="form-control-cycsa" 
                           value="<?= !empty($pm['fecha_ida']) ? date('Y-m-d\TH:i', strtotime($pm['fecha_ida'])) : date('Y-m-d\TH:00') ?>" required>
                </div>
                <div class="form-group-cycsa">
                    <label>Fecha y Hora Estimada de Retorno al Lab:</label>
                    <input type="datetime-local" name="fecha_llegada" class="form-control-cycsa" 
                           value="<?= !empty($pm['fecha_llegada']) ? date('Y-m-d\TH:i', strtotime($pm['fecha_llegada'])) : date('Y-m-d\TH:00', strtotime('+4 hours')) ?>" required>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group-cycsa">
                    <label>Técnico Muestreador Responsable:</label>
                    <select name="id_tecnico" class="form-control-cycsa" required id="select_tecnico_campo">
                        <option value="">-- Seleccionar Técnico --</option>
                        <?php foreach ($tecnicos as $tec): ?>
                            <option value="<?= $tec['id'] ?>" data-nombre="<?= htmlspecialchars($tec['nombre']) ?>" <?= (!empty($pm['id_tecnico']) && $pm['id_tecnico'] == $tec['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($tec['nombre']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="form-group-cycsa">
                    <label>Vehículo Asignado:</label>
                    <select name="id_vehiculo" class="form-control-cycsa" required>
                        <option value="">-- Seleccionar Vehículo --</option>
                        <?php foreach ($vehiculos as $veh): ?>
                            <option value="<?= $veh['id'] ?>" <?= (!empty($pm['id_vehiculo']) && $pm['id_vehiculo'] == $veh['id']) ? 'selected' : '' ?>>
                                <?= htmlspecialchars($veh['marca']) ?> <?= htmlspecialchars($veh['modelo']) ?> - Placa: <?= htmlspecialchars($veh['placa']) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group-cycsa">
                    <label>Lugar / Punto de Muestreo:</label>
                    <input type="text" name="lugar_muestreo" class="form-control-cycsa" 
                           value="<?= htmlspecialchars($pm['lugar_muestreo'] ?? $os['nombre_proyecto']) ?>" placeholder="Ubicación exacta o tramo">
                </div>
                <div class="form-group-cycsa">
                    <label>No. de Personas (Muestreadores):</label>
                    <input type="number" min="1" max="10" name="num_muestreadores" class="form-control-cycsa" 
                           value="<?= htmlspecialchars($pm['num_muestreadores'] ?? 1) ?>">
                </div>
            </div>

            <div class="form-group-cycsa" style="margin-bottom:0;">
                <label>Observaciones o Instrucciones de Campo:</label>
                <textarea name="observaciones_campo" class="form-control-cycsa" rows="2" placeholder="Detalles de acceso a la obra, punto de encuentro, etc."><?= htmlspecialchars($pm['observaciones_campo'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- CARD 2: LISTA DE CHEQUEO OFICIAL CYCSA-RT-FM-40 B -->
        <div class="cycsa-card" style="border-top: 4px solid var(--cycsa-azul);">
            <div class="cycsa-card-title">
                <div>
                    <i class="fa-solid fa-list-check" style="color: var(--cycsa-azul);"></i> 
                    2. Lista de Chequeo de Equipos y Recursos (CYCSA-RT-FM-40 B)
                </div>
                <div>
                    <button type="button" class="btn-cycsa btn-cycsa-secondary" style="font-size:12px; padding:6px 12px;" onclick="marcarTodoChecklistSalida()">
                        <i class="fa-solid fa-check-double" style="color:#10b981;"></i> Marcar Todo para Salida
                    </button>
                </div>
            </div>

            <!-- TABLAS PARALELAS: PROTECCIÓN Y TOMA DE MUESTRAS -->
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                
                <!-- COLUMNA A: PROTECCIÓN Y SEGURIDAD -->
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                    <div style="background:#eff6ff; color:#1e40af; font-weight:700; padding:10px 14px; font-size:13px; border-bottom:1px solid #bfdbfe;">
                        <i class="fa-solid fa-shield-halved"></i> PARA PROTECCIÓN Y SEGURIDAD
                    </div>
                    <table class="chk-table">
                        <thead>
                            <tr>
                                <th>Material / EPP</th>
                                <th style="text-align:center; width:65px;">Cant</th>
                                <th style="text-align:center; width:45px;" title="Antes del muestreo">Antes</th>
                                <th style="text-align:center; width:65px;">Cant</th>
                                <th style="text-align:center; width:45px;" title="Post-muestreo">Post</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $itemsSeguridad = [
                                'Mascarilla', 'Lentes de Seguridad', 'Casco', 'Chaleco', 
                                'Guantes Nitrilo', 'Botas de Cuero', 'Botas de Hule', 
                                'Tapones de oído', 'Documentos de acceso al lugar', 
                                'Carnet de Seguro', 'Colilla de INSS', 'Cuña de vehículo', 
                                'Cinta luminosa de vehículo'
                            ];
                            foreach ($itemsSeguridad as $idx => $item):
                                $k = 'seg_' . $idx;
                                $cantA = $chk[$k . '_cant_a'] ?? '';
                                $chkA = !empty($chk[$k . '_chk_a']);
                                $cantP = $chk[$k . '_cant_p'] ?? '';
                                $chkP = !empty($chk[$k . '_chk_p']);
                            ?>
                            <tr>
                                <td style="font-weight:600; color:#334155;"><?= $item ?></td>
                                <td style="text-align:center;">
                                    <input type="text" name="chk[<?= $k ?>_cant_a]" value="<?= htmlspecialchars($cantA) ?>" placeholder="-" class="chk-input-cant chk-cant-a">
                                </td>
                                <td style="text-align:center;">
                                    <input type="checkbox" name="chk[<?= $k ?>_chk_a]" value="1" <?= $chkA ? 'checked' : '' ?> class="chk-checkbox chk-box-a">
                                </td>
                                <td style="text-align:center;">
                                    <input type="text" name="chk[<?= $k ?>_cant_p]" value="<?= htmlspecialchars($cantP) ?>" placeholder="-" class="chk-input-cant">
                                </td>
                                <td style="text-align:center;">
                                    <input type="checkbox" name="chk[<?= $k ?>_chk_p]" value="1" <?= $chkP ? 'checked' : '' ?> class="chk-checkbox">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- COLUMNA B: TOMA DE MUESTRAS (DENSÍMETRO NUCLEAR) -->
                <div style="border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden;">
                    <div style="background:#fef3c7; color:#92400e; font-weight:700; padding:10px 14px; font-size:13px; border-bottom:1px solid #fde68a;">
                        <i class="fa-solid fa-radiation"></i> PARA TOMA DE MUESTRAS (COMPACTACIÓN / DN)
                    </div>
                    <table class="chk-table">
                        <thead>
                            <tr>
                                <th>Equipo / Accesorio</th>
                                <th style="text-align:center; width:65px;">Cant</th>
                                <th style="text-align:center; width:45px;" title="Antes del muestreo">Antes</th>
                                <th style="text-align:center; width:65px;">Cant</th>
                                <th style="text-align:center; width:45px;" title="Post-muestreo">Post</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php
                            $itemsMuestras = [
                                'Densímetro Nuclear', 'Base de parafina', 'Placa guía para pin', 
                                'Mazo de hierro', 'Pin de penetración', 'Mariposa para extraer pin', 
                                'Cargador de densímetro', 'Caja de densímetro', 'Mecate para aseguran caja', 
                                'Dosímetro personal', 'Dosímetro de equipo', 'Conos de seguridad', 
                                'Rótulos de advertencia radiación', 'Hoja de campo', 'Tabla de apoyo', 
                                'Proctor a utilizar'
                            ];
                            foreach ($itemsMuestras as $idx => $item):
                                $k = 'mue_' . $idx;
                                $cantA = $chk[$k . '_cant_a'] ?? '';
                                $chkA = !empty($chk[$k . '_chk_a']);
                                $cantP = $chk[$k . '_cant_p'] ?? '';
                                $chkP = !empty($chk[$k . '_chk_p']);
                            ?>
                            <tr>
                                <td style="font-weight:600; color:#334155;"><?= $item ?></td>
                                <td style="text-align:center;">
                                    <input type="text" name="chk[<?= $k ?>_cant_a]" value="<?= htmlspecialchars($cantA) ?>" placeholder="-" class="chk-input-cant chk-cant-a">
                                </td>
                                <td style="text-align:center;">
                                    <input type="checkbox" name="chk[<?= $k ?>_chk_a]" value="1" <?= $chkA ? 'checked' : '' ?> class="chk-checkbox chk-box-a">
                                </td>
                                <td style="text-align:center;">
                                    <input type="text" name="chk[<?= $k ?>_cant_p]" value="<?= htmlspecialchars($cantP) ?>" placeholder="-" class="chk-input-cant">
                                </td>
                                <td style="text-align:center;">
                                    <input type="checkbox" name="chk[<?= $k ?>_chk_p]" value="1" <?= $chkP ? 'checked' : '' ?> class="chk-checkbox">
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

            </div>

            <!-- SECCIÓN 3: CONTROL DE FIRMAS Y RESPONSABILIDAD ANTES DEL MUESTREO -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:15px; margin-bottom:20px;">
                <div style="font-weight:700; color:var(--cycsa-azul); font-size:13px; margin-bottom:10px;">
                    <i class="fa-solid fa-signature"></i> Verificación de Recursos Antes del Muestreo (Salida)
                </div>
                <div style="display:grid; grid-template-columns: repeat(3, 1fr); gap:15px;">
                    <div style="background:white; border:1px solid #cbd5e1; padding:10px; border-radius:6px;">
                        <strong style="font-size:11px; color:#475569; text-transform:uppercase;">1. Preparación de Materiales</strong>
                        <input type="text" name="chk[antes_prep_nombre]" value="<?= htmlspecialchars($chk['antes_prep_nombre'] ?? ($pm['tecnico_nombre'] ?? '')) ?>" class="form-control-cycsa" style="padding:6px; font-size:12px; margin-top:4px;" placeholder="Nombre responsable">
                        <label style="display:flex; align-items:center; gap:6px; font-size:11.5px; margin-top:6px; cursor:pointer;">
                            <input type="checkbox" name="chk[antes_prep_firma]" value="1" <?= !empty($chk['antes_prep_firma']) ? 'checked' : '' ?>> Firmar digitalmente
                        </label>
                    </div>
                    <div style="background:white; border:1px solid #cbd5e1; padding:10px; border-radius:6px;">
                        <strong style="font-size:11px; color:#475569; text-transform:uppercase;">2. Verificación de Materiales</strong>
                        <input type="text" name="chk[antes_verif_nombre]" value="<?= htmlspecialchars($chk['antes_verif_nombre'] ?? 'Coordinación Técnica') ?>" class="form-control-cycsa" style="padding:6px; font-size:12px; margin-top:4px;" placeholder="Nombre verificador">
                        <label style="display:flex; align-items:center; gap:6px; font-size:11.5px; margin-top:6px; cursor:pointer;">
                            <input type="checkbox" name="chk[antes_verif_firma]" value="1" <?= !empty($chk['antes_verif_firma']) ? 'checked' : '' ?>> Firmar digitalmente
                        </label>
                    </div>
                    <div style="background:white; border:1px solid #cbd5e1; padding:10px; border-radius:6px;">
                        <strong style="font-size:11px; color:#475569; text-transform:uppercase;">3. Recibe Materiales (Técnico)</strong>
                        <input type="text" name="chk[antes_recibe_nombre]" value="<?= htmlspecialchars($chk['antes_recibe_nombre'] ?? ($pm['tecnico_nombre'] ?? '')) ?>" class="form-control-cycsa" style="padding:6px; font-size:12px; margin-top:4px;" placeholder="Nombre técnico">
                        <label style="display:flex; align-items:center; gap:6px; font-size:11.5px; margin-top:6px; cursor:pointer;">
                            <input type="checkbox" name="chk[antes_recibe_firma]" value="1" <?= !empty($chk['antes_recibe_firma']) ? 'checked' : '' ?>> Firmar digitalmente
                        </label>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 4: CONTROL DE FIRMAS POST-MUESTREO (RETORNO) -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:15px; margin-bottom:20px;">
                <div style="font-weight:700; color:#065f46; font-size:13px; margin-bottom:10px;">
                    <i class="fa-solid fa-clipboard-check"></i> Verificación de Recursos Después del Muestreo (Retorno)
                </div>
                <div style="display:grid; grid-template-columns: 1fr 1fr; gap:15px;">
                    <div style="background:white; border:1px solid #cbd5e1; padding:10px; border-radius:6px;">
                        <strong style="font-size:11px; color:#475569; text-transform:uppercase;">1. Recibe Materiales (Laboratorio)</strong>
                        <input type="text" name="chk[post_recibe_nombre]" value="<?= htmlspecialchars($chk['post_recibe_nombre'] ?? '') ?>" class="form-control-cycsa" style="padding:6px; font-size:12px; margin-top:4px;" placeholder="Nombre quien recibe en lab">
                        <label style="display:flex; align-items:center; gap:6px; font-size:11.5px; margin-top:6px; cursor:pointer;">
                            <input type="checkbox" name="chk[post_recibe_firma]" value="1" <?= !empty($chk['post_recibe_firma']) ? 'checked' : '' ?>> Firmar digitalmente
                        </label>
                    </div>
                    <div style="background:white; border:1px solid #cbd5e1; padding:10px; border-radius:6px;">
                        <strong style="font-size:11px; color:#475569; text-transform:uppercase;">2. Entrega de Materiales (Técnico Muestreador)</strong>
                        <input type="text" name="chk[post_entrega_nombre]" value="<?= htmlspecialchars($chk['post_entrega_nombre'] ?? ($pm['tecnico_nombre'] ?? '')) ?>" class="form-control-cycsa" style="padding:6px; font-size:12px; margin-top:4px;" placeholder="Nombre técnico que entrega">
                        <label style="display:flex; align-items:center; gap:6px; font-size:11.5px; margin-top:6px; cursor:pointer;">
                            <input type="checkbox" name="chk[post_entrega_firma]" value="1" <?= !empty($chk['post_entrega_firma']) ? 'checked' : '' ?>> Firmar digitalmente
                        </label>
                    </div>
                </div>
            </div>

            <!-- BOTONES DE ACCIÓN DEL FORMULARIO -->
            <div style="display: flex; justify-content: space-between; align-items: center;">
                <a href="/Cycsa/publico/ordenes-servicio/imprimir-checklist?id=<?= $os['id'] ?>" target="_blank" class="btn-cycsa btn-cycsa-secondary">
                    <i class="fa-solid fa-print"></i> Vista Previa / Imprimir Formato Oficial
                </a>
                <div style="display:flex; gap:10px;">
                    <button type="submit" name="accion_muestreo" value="guardar" class="btn-cycsa btn-cycsa-primary">
                        <i class="fa-solid fa-floppy-disk"></i> Guardar Programación y Checklist
                    </button>
                </div>
            </div>
        </div>

        <!-- CARD 3: FINALIZACIÓN DE MUESTREO (RETORNO AL LAB PARA LLENAR CYCSA-RT-FM-13) -->
        <div class="cycsa-card" style="border-top: 4px solid #10b981; text-align: center; padding: 30px;">
            <h3 style="margin: 0 0 10px 0; font-family: 'Outfit', sans-serif; font-size: 18px; color: #065f46; display: flex; align-items: center; justify-content: center; gap: 10px;">
                <i class="fa-solid fa-circle-check" style="color: #10b981;"></i> Finalización de Muestreo en Campo
            </h3>
            <p style="color: #475569; font-size: 13.5px; max-width: 650px; margin: 0 auto 20px auto; line-height: 1.5;">
                Al hacer clic en <strong>"Finalizar Muestreo y Abrir Hoja RT-FM-13"</strong>, el sistema guardará todos los datos registrados en esta pantalla, registrará el retorno del técnico al laboratorio y lo redirigirá inmediatamente a la <strong>Hoja de Solicitud de Ensayos (CYCSA-RT-FM-13)</strong> con los datos de esta orden y el técnico precargados.
            </p>

            <button type="submit" name="accion_muestreo" value="finalizar" onclick="return confirm('¿Confirma que el muestreo en campo ha finalizado con éxito y desea registrar los especímenes en la Hoja RT-FM-13?');" class="btn-cycsa btn-cycsa-success" style="padding: 14px 32px; font-size: 15px; font-weight: 700; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25); cursor: pointer;">
                <i class="fa-solid fa-file-circle-check"></i> Finalizar Muestreo y Abrir Hoja RT-FM-13 <i class="fa-solid fa-arrow-right"></i>
            </button>
        </div>
    </form>

</div>

<script>
function marcarTodoChecklistSalida() {
    document.querySelectorAll('.chk-box-a').forEach(cb => {
        cb.checked = true;
    });
}
</script>