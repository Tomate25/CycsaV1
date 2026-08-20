<?php
// Vista para la Programación de Muestreo en Campo (CYCSA ERP Premium Style)
$pm = $os['programacion_muestreo'] ?? null;
?>
<style>
    .cycsa-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); padding: 25px; margin-bottom: 25px; }
    .cycsa-card-title { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: var(--cycsa-azul); border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
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
</style>

<div style="max-width: 950px; margin: 0 auto; padding-bottom: 40px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-size: 22px; font-family: 'Outfit', sans-serif; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-truck-pickup" style="color: var(--cycsa-azul);"></i> Programación de Muestreo en Campo
            </h2>
            <p style="color: #64748b; margin-top: 4px; font-size: 13.5px; margin-bottom: 0;">
                Orden de Servicio: <strong style="color: var(--cycsa-azul);"><?= htmlspecialchars($os['codigo_os']) ?></strong>
            </p>
        </div>
        <a href="/Cycsa/publico/ordenes-servicio/detalle?id=<?= $os['id'] ?>" class="btn-cycsa btn-cycsa-secondary">
            <i class="fa-solid fa-eye"></i> Ver Orden de Servicio
        </a>
    </div>

    <!-- TARJETA DE RESUMEN -->
    <div class="cycsa-card" style="background: #f8fafc;">
        <div class="form-grid-2">
            <div>
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">CLIENTE:</span>
                <div style="font-size: 15px; font-weight: 700; color: #0f172a; margin-top: 2px;"><?= htmlspecialchars($os['cliente_nombre']) ?></div>
            </div>
            <div>
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">ESTADO ACTUAL:</span>
                <div style="margin-top: 2px;">
                    <span class="badge-status">
                        <i class="fa-solid fa-clock"></i> <?= htmlspecialchars($os['estado']) ?>
                    </span>
                </div>
            </div>
            <div style="grid-column: span 2;">
                <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase;">PROYECTO:</span>
                <div style="font-size: 14px; color: #334155; margin-top: 2px;"><?= htmlspecialchars($os['nombre_proyecto']) ?></div>
            </div>
        </div>
    </div>

    <!-- FORMULARIO DE LOGÍSTICA DE MUESTREO -->
    <div class="cycsa-card">
        <div class="cycsa-card-title">
            <i class="fa-solid fa-calendar-days"></i> Asignación de Fechas, Técnico y Vehículo
        </div>

        <form action="/Cycsa/publico/ordenes-servicio/guardar-muestreo" method="POST">
            <input type="hidden" name="id_os" value="<?= $os['id'] ?>">

            <div class="form-grid-2">
                <div class="form-group-cycsa">
                    <label>Fecha y Hora Programada de Ida:</label>
                    <input type="datetime-local" name="fecha_ida" class="form-control-cycsa" 
                           value="<?= !empty($pm['fecha_ida']) ? date('Y-m-d\TH:i', strtotime($pm['fecha_ida'])) : date('Y-m-d\TH:00') ?>" required>
                </div>
                <div class="form-group-cycsa">
                    <label>Fecha y Hora Programada de Llegada:</label>
                    <input type="datetime-local" name="fecha_llegada" class="form-control-cycsa" 
                           value="<?= !empty($pm['fecha_llegada']) ? date('Y-m-d\TH:i', strtotime($pm['fecha_llegada'])) : date('Y-m-d\TH:00', strtotime('+4 hours')) ?>" required>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group-cycsa">
                    <label>Técnico Ensayador / Muestreador:</label>
                    <select name="id_tecnico" class="form-control-cycsa" required>
                        <option value="">-- Seleccionar Técnico --</option>
                        <?php foreach ($tecnicos as $tec): ?>
                            <option value="<?= $tec['id'] ?>" <?= (!empty($pm['id_tecnico']) && $pm['id_tecnico'] == $tec['id']) ? 'selected' : '' ?>>
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
                    <label>Tiempo Estimado de Trabajo en Campo (Horas):</label>
                    <input type="number" step="0.5" name="horas_espera_requeridas" class="form-control-cycsa" value="<?= htmlspecialchars($os['horas_espera_requeridas'] ?? 4) ?>" placeholder="Ej: 4.5">
                </div>
                <div class="form-group-cycsa">
                    <label>Instrucciones de Retorno:</label>
                    <input type="text" class="form-control-cycsa" value="Entrega directa al laboratorio para RT-FM-13" readonly style="background:#f8fafc; color:#64748b;">
                </div>
            </div>

            <div class="form-group-cycsa">
                <label>Observaciones o Instrucciones de Campo:</label>
                <textarea name="observaciones_campo" class="form-control-cycsa" rows="3" placeholder="Detalles de ubicación, puntos de muestra, equipo especial, etc."><?= htmlspecialchars($pm['observaciones_campo'] ?? '') ?></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end;">
                <button type="submit" class="btn-cycsa btn-cycsa-primary">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar Programación
                </button>
            </div>
        </form>
    </div>

    <!-- TARJETA FINALIZACIÓN DE MUESTREO (PASO 3 Y REDIRECCIÓN A HOJAS DE SERVICIO) -->
    <div class="cycsa-card" style="border-top: 4px solid #10b981; text-align: center; padding: 30px;">
        <h3 style="margin: 0 0 10px 0; font-family: 'Outfit', sans-serif; font-size: 18px; color: #065f46; display: flex; align-items: center; justify-content: center; gap: 10px;">
            <i class="fa-solid fa-circle-check" style="color: #10b981;"></i> Finalización de Muestreo en Campo
        </h3>
        <p style="color: #475569; font-size: 13.5px; max-width: 650px; margin: 0 auto 20px auto; line-height: 1.5;">
            Al hacer clic en <strong>"Muestreo Finalizado"</strong>, el estado de la Orden de Servicio cambiará a completado y el sistema lo redirigirá inmediatamente al módulo de <strong>Hojas de Servicio (CYCSA RT-FM-13)</strong> con los datos de esta orden autocompletados.
        </p>

        <form action="/Cycsa/publico/ordenes-servicio/finalizar-muestreo" method="POST" onsubmit="return confirm('¿Confirma que el muestreo en campo ha finalizado y desea proceder a llenar la Hoja de Servicio?');">
            <input type="hidden" name="id_os" value="<?= $os['id'] ?>">
            <button type="submit" class="btn-cycsa btn-cycsa-success" style="padding: 14px 32px; font-size: 16px; font-weight: 700; box-shadow: 0 4px 12px rgba(16, 185, 129, 0.25);">
                <i class="fa-solid fa-file-circle-check"></i> Muestreo Finalizado <i class="fa-solid fa-arrow-right"></i>
            </button>
        </form>
    </div>
</div>
