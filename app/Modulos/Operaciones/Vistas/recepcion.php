<?php
// Recepcion form view - LIMS Sample Reception
?>
<style>
    .form-box { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-top: 15px; }
    .form-section-title { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: var(--cycsa-azul); border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 20px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 15px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    .form-group { margin-bottom: 16px; display: flex; flex-direction: column; gap: 6px; }
    .form-control { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; }
    .form-control:focus { border-color: var(--cycsa-azul); }
    
    .age-row { display: flex; align-items: center; gap: 15px; padding: 10px; background-color: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 8px; }
    
    .btn-action-mini { border: none; background: none; cursor: pointer; padding: 6px 10px; border-radius: 4px; font-size: 12px; font-weight: 600; display: inline-flex; align-items: center; gap: 4px; }
    .btn-add { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .btn-add:hover { background-color: #dbeafe; }
    .btn-del { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .btn-del:hover { background-color: #fee2e2; }

    /* Custom Search Select Premium */
    .custom-select-wrapper { position: relative; width: 100%; }
    .custom-select-trigger {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 10px 14px;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-family: 'Inter', sans-serif;
        font-size: 14px;
        color: #1e293b;
        cursor: pointer;
        transition: all 0.2s;
        min-height: 42px;
        box-sizing: border-box;
    }
    .custom-select-trigger:hover {
        border-color: var(--cycsa-azul);
    }
    .custom-select-trigger:focus, .custom-select-trigger.active {
        border-color: var(--cycsa-azul);
        box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.1);
        outline: none;
    }
    .custom-select-dropdown {
        position: absolute;
        top: 100%;
        left: 0;
        right: 0;
        background: #fff;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
        z-index: 100;
        margin-top: 5px;
        display: none;
        overflow: hidden;
        animation: slideDown 0.18s cubic-bezier(0.16, 1, 0.3, 1);
    }
    .custom-select-search-box {
        padding: 8px;
        border-bottom: 1px solid #f1f5f9;
        background: #f8fafc;
    }
    .custom-select-search {
        width: 100%;
        padding: 8px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 13px;
        outline: none;
        box-sizing: border-box;
    }
    .custom-select-search:focus {
        border-color: var(--cycsa-azul);
    }
    .custom-select-options-list {
        max-height: 240px;
        overflow-y: auto;
        padding: 4px 0;
    }
    .custom-select-option {
        padding: 10px 14px;
        cursor: pointer;
        display: flex;
        align-items: center;
        justify-content: space-between;
        font-size: 13.5px;
        color: #334155;
        transition: all 0.15s;
        border-bottom: 1px solid #f8fafc;
    }
    .custom-select-option:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .custom-select-option.selected {
        background: #eff6ff;
        color: var(--cycsa-azul);
        font-weight: 600;
    }
    .custom-select-option.disabled {
        background: #f8fafc;
        color: #94a3b8;
        cursor: not-allowed;
        font-style: italic;
    }
    .custom-select-option.disabled:hover {
        background: #f8fafc;
    }
    .custom-select-badge {
        font-size: 10.5px;
        font-weight: 700;
        padding: 3px 8px;
        border-radius: 20px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .badge-available { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .badge-locked { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }
</style>

<div class="header-flex" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;">Recepción e Ingreso de Muestras</h2>
        <p style="color: #64748b; margin-top: 5px; font-size: 14px;">Asociando muestra a la Orden de Servicio: <strong style="color: var(--cycsa-azul); font-family: monospace;"><?= htmlspecialchars($os['codigo_os'], ENT_QUOTES, 'UTF-8') ?></strong></p>
    </div>
    <a href="/Cycsa/publico/operaciones" class="form-control" style="text-decoration: none; background: #f1f5f9; border: 1px solid #cbd5e1; font-weight: 600; color: #475569; display: inline-flex; align-items: center; gap: 6px; height: auto;"><i class="fa-solid fa-arrow-left"></i> Volver</a>
</div>

<form method="POST" action="/Cycsa/publico/operaciones/guardar-recepcion">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="id_os" value="<?= $os['id'] ?>">

    <!-- 0. MUESTRAS DECLARADAS EN LA ORDEN DE SERVICIO -->
    <?php if (!empty($muestrasDeclaradas)): ?>
        <div class="form-box" style="border-color: #cbd5e1; background-color: #f8fafc; margin-bottom: 20px;">
            <h4 class="form-section-title" style="color: #475569; border-bottom-color: #e2e8f0; margin-bottom: 10px;">
                <i class="fa-solid fa-list-check" style="margin-right: 6px;"></i> 
                Muestras / Especímenes Declarados en la Orden de Servicio
            </h4>
            <p style="font-size: 13.5px; color: #64748b; margin-bottom: 15px;">
                Estas son las muestras ingresadas durante la planificación de la O/S. Haga clic en <strong>"Cargar Muestra"</strong> para autocompletar el formulario de recepción.
            </p>
            <div style="overflow-x: auto;">
                <table style="width: 100%; border-collapse: collapse; background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; margin-bottom: 10px;">
                    <thead>
                        <tr style="background-color: #f1f5f9; border-bottom: 2px solid #e2e8f0;">
                            <th style="padding: 10px 12px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Código de Campo / Muestra</th>
                            <th style="padding: 10px 12px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Descripción</th>
                            <th style="padding: 10px 12px; text-align: left; font-size: 12px; font-weight: 700; color: #475569;">Información Importante</th>
                            <th style="padding: 10px 12px; text-align: center; font-size: 12px; font-weight: 700; color: #475569;">Estado LIMS</th>
                            <th style="padding: 10px 12px; text-align: center; font-size: 12px; font-weight: 700; color: #475569;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($muestrasDeclaradas as $md): 
                            $nombre = htmlspecialchars($md['nombre_muestra'] ?? '', ENT_QUOTES, 'UTF-8');
                            $desc = htmlspecialchars($md['descripcion'] ?? '', ENT_QUOTES, 'UTF-8');
                            $info = htmlspecialchars($md['info_importante'] ?? '', ENT_QUOTES, 'UTF-8');
                            $recibida = !empty($md['recibida']);
                        ?>
                            <tr style="border-bottom: 1px solid #f1f5f9;">
                                <td style="padding: 10px 12px; font-weight: 600; color: var(--cycsa-azul); font-family: monospace; font-size: 13.5px;"><?= $nombre ?></td>
                                <td style="padding: 10px 12px; font-size: 13px; color: #334155;"><?= $desc ?></td>
                                <td style="padding: 10px 12px; font-size: 13px; color: #64748b; font-style: italic;"><?= $info ?></td>
                                <td style="padding: 10px 12px; text-align: center;">
                                    <?php if ($recibida): ?>
                                        <span style="display: inline-block; background-color: #d1fae5; color: #065f46; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 20px; border: 1px solid #a7f3d0;">
                                            <i class="fa-solid fa-circle-check"></i> Recibida (<?= htmlspecialchars($md['codigo_muestra'], ENT_QUOTES, 'UTF-8') ?>)
                                        </span>
                                    <?php else: ?>
                                        <span style="display: inline-block; background-color: #fef3c7; color: #92400e; font-size: 11px; font-weight: 700; padding: 4px 8px; border-radius: 20px; border: 1px solid #fde68a;">
                                            <i class="fa-solid fa-clock"></i> Pendiente
                                        </span>
                                    <?php endif; ?>
                                </td>
                                <td style="padding: 10px 12px; text-align: center;">
                                    <?php if (!$recibida): ?>
                                        <button type="button" 
                                                onclick="cargarMuestraDeclarada('<?= addslashes($nombre) ?>', '<?= addslashes($desc) ?>', '<?= addslashes($info) ?>')" 
                                                class="form-control" 
                                                style="padding: 4px 10px; font-size: 11px; background-color: var(--cycsa-azul); border-color: var(--cycsa-azul); color: white; cursor: pointer; display: inline-block; width: auto; height: auto;">
                                            <i class="fa-solid fa-cloud-arrow-down"></i> Cargar Muestra
                                        </button>
                                    <?php else: ?>
                                        <span style="font-size: 11px; color: #94a3b8;">—</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>

    <!-- 1. DATOS DE LOGÍSTICA -->
    <div class="form-box">
        <h4 class="form-section-title"><i class="fa-solid fa-truck-ramp-box" style="margin-right: 6px;"></i> Datos de Logística y Recepción</h4>
        <div class="grid-3">
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Cliente</label>
                <input type="text" readonly class="form-control" style="background-color: #f1f5f9; font-weight: 600;" value="<?= htmlspecialchars($os['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?> (RUC/Ced: <?= htmlspecialchars($os['cliente_ruc'], ENT_QUOTES, 'UTF-8') ?>)">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Atención A</label>
                <input type="text" readonly class="form-control" style="background-color: #f1f5f9; font-weight: 600;" value="<?= htmlspecialchars($os['atencion_a'] ?? '—', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Proyecto / Obra</label>
                <input type="text" readonly class="form-control" style="background-color: #f1f5f9; font-weight: 600;" value="<?= htmlspecialchars($os['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="grid-3" style="margin-top: 10px;">
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Tipo / Origen de Muestra</label>
                <select name="tipo_muestra" required class="form-control" style="background-color: #f8fafc; font-weight: 600; border-color: #93c5fd;">
                    <option value="Laboratorio" selected>Laboratorio (MS-XXXX-YY) - Consecutivo Automático</option>
                    <option value="Campo">Campo (CAM-YY-XXXX) - Muestreo In-Situ Inmutable</option>
                </select>
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Código de Campo / Referencia (Sincronizado Consecutivo)</label>
                <input type="text" name="codigo_campo" id="codigo_campo" required placeholder="Ej: MC-2026-0001" class="form-control" value="<?= htmlspecialchars($codigoCampoAuto ?? '', ENT_QUOTES, 'UTF-8') ?>" style="font-weight: 600; color: #103487; background-color: #f0f9ff; border-color: #7dd3fc;">
                <span style="font-size: 11px; color: #64748b; margin-top: 2px;">Precargado automáticamente según la Hoja de Campo o el consecutivo MC-AÑO-XXXX de la O/S.</span>
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">ID del Cilindro / Especímen Individual</label>
                <input type="text" name="id_cilindro" id="id_cilindro" placeholder="Ej: MC10 - MC12 (3 Cilindros)" class="form-control">
            </div>
        </div>

        <div class="grid-3" style="margin-top: 10px;">
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Entregado Por (Nombre cliente/chofer/técnico)</label>
                <?php
                $valorDefectoEntregado = '';
                if (!empty($os['tecnico_muestreo'])) {
                    $valorDefectoEntregado = $os['tecnico_muestreo'];
                } elseif (!empty($hoja_solicitud['nombre_persona_entrega_muestra'])) {
                    $valorDefectoEntregado = $hoja_solicitud['nombre_persona_entrega_muestra'];
                }
                ?>
                <input type="text" name="entregado_por" placeholder="Nombre completo" class="form-control" value="<?= htmlspecialchars($valorDefectoEntregado, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha/Hora Recepción</label>
                <input type="datetime-local" name="fecha_recepcion" required class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
            </div>
            <div class="form-group" style="justify-content: center;">
                <label style="font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 4px;">Auditoría / Control de Calidad</label>
                <label style="display: flex; align-items: center; gap: 8px; font-size: 13px; font-weight: 600; color: #1e40af; background-color: #eff6ff; padding: 8px 12px; border-radius: 6px; border: 1px solid #bfdbfe; cursor: pointer;">
                    <input type="checkbox" name="is_qa_qc" value="1">
                    <i class="fa-solid fa-microscope"></i> Es Muestra para Control de Calidad (QA/QC - Réplica)
                </label>
            </div>
        </div>

        <div class="form-group">
            <label style="font-weight: 600; font-size: 13px; color: #334155;">Observaciones Iniciales de Recepción</label>
            <textarea name="observaciones" rows="2" placeholder="Escribe detalles del estado de las muestras al recibirse..." class="form-control"></textarea>
        </div>

        <div style="background-color: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 8px 12px; border-radius: 6px; font-size: 12px; font-weight: 500;">
            <i class="fa-solid fa-lock"></i> <strong>INMUTABILIDAD AUTOMÁTICA:</strong> Al guardar, el código consecutivo de la muestra será generado de forma automática y sellado. No requiere manipulación manual.
        </div>
    </div>

    <!-- 2. DATOS TÉCNICOS DEL LOTE Y PARÁMETROS -->
    <div class="form-box" id="seccion-especificaciones-lote">
        <h4 class="form-section-title"><i class="fa-solid fa-flask" style="margin-right: 6px;"></i> Especificaciones Técnicas y Parámetros del Lote</h4>
        <div class="grid-2">
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Identificación del Lote / Elemento Estructural</label>
                <input type="text" name="nombre_lote" id="input_nombre_lote" required placeholder="Ej: Columnas Eje C - Nivel 2 (MC10-MC12)" class="form-control">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha de Moldeo / Fabricación (T0)</label>
                <input type="date" name="fecha_moldeo" id="input_fecha_moldeo" required class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <!-- Diferenciación explícita entre Parámetros del Cliente vs Datos del Ensayo en Obra -->
        <div style="margin-top: 12px; font-size: 12px; font-weight: 700; color: #0369a1; text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px dashed #cbd5e1; padding-bottom: 4px;">
            <i class="fa-solid fa-sliders"></i> Parámetros de Especificación del Cliente vs. Datos Medidos en Campo
        </div>

        <div class="grid-3" style="margin-top: 10px;">
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #0369a1;">Resistencia de Diseño Objetivo (Cliente/Proyecto)</label>
                <input type="text" name="diseno_resistencia" id="input_diseno_resistencia" required placeholder="Ej: 3000 PSI / 210 kg/cm²" class="form-control" style="border-color: #93c5fd;">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Revenimiento (Slump) Medido en Campo</label>
                <div style="display: flex; gap: 8px;">
                    <input type="number" step="0.01" name="revenimiento_in" placeholder="Pulgadas" class="form-control" style="width: 50%;">
                    <input type="number" step="0.01" name="revenimiento_cm" placeholder="CM" class="form-control" style="width: 50%;">
                </div>
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Temperatura Concreto en Obra (°C)</label>
                <input type="number" step="0.1" name="temperatura_c" placeholder="Grados Celsius" class="form-control">
            </div>
        </div>

        <div class="grid-2" style="margin-top: 10px;">
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Procedimiento de Muestreo</label>
                <input type="text" name="procedimiento_muestreo" class="form-control" value="ASTM C172 / CYCSA-PE-07">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Servicio Cotizado Asociado</label>
                <select name="id_detalle_cotizacion" id="id_detalle_cotizacion" required class="form-control" style="background-color: white;" onchange="toggleEdadesSection()">
                    <option value="">-- Seleccione el servicio facturado --</option>
                    <?php foreach ($servicios as $s): 
                        $cantidadFacturada = max(1, (int)($s['cantidad_facturada'] ?? $s['cantidad'] ?? 1));
                        $recibidos = (int)($s['total_recibidos'] ?? 0);
                        $yaRecibido = (!empty($s['ya_recibido']) && $s['id'] != ($idDetalle ?? 0));
                        
                        $lblEstado = '';
                        if ($recibidos > 0) {
                            if ($yaRecibido) {
                                $lblEstado = ' (✔ COMPLETADO: ' . $recibidos . '/' . $cantidadFacturada . ' - Muestra: ' . $s['codigo_muestra'] . ')';
                            } else {
                                $lblEstado = ' (PARTICULAR: Recibidas ' . $recibidos . '/' . $cantidadFacturada . ' Muestras)';
                            }
                        } else {
                            $lblEstado = ' (PENDIENTE DE INGRESAR)';
                        }
                    ?>
                        <option value="<?= $s['id'] ?>" 
                                data-formato-id="<?= $s['formato_id'] ?>" 
                                <?= ($s['id'] == ($idDetalle ?? 0)) ? 'selected' : '' ?>
                                <?= $yaRecibido ? 'disabled style="color: #94a3b8; background-color: #f1f5f9;"' : '' ?>>
                            <?= htmlspecialchars($s['codigo_servicio'] ? $s['codigo_servicio'] . ' - ' : '', ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars($s['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?> [Cant. Facturada: <?= $cantidadFacturada ?>]<?= $lblEstado ?>
                        </option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>
    </div>

    <!-- 3. DETALLE DE EDADES DE ROTURA (ESPECÍMENES) -->
    <div class="form-box" id="seccion-edades-rotura">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 15px;">
            <h4 style="font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: var(--cycsa-azul); margin: 0;"><i class="fa-solid fa-calendar-days"></i> Programación de Edades de Rotura</h4>
            <button type="button" class="btn-action-mini btn-add" onclick="agregarFilaEdad()"><i class="fa-solid fa-plus"></i> Agregar Edad Personalizada</button>
        </div>
        <p style="font-size: 13px; color: #64748b; margin-bottom: 15px;">Defina las edades de ensayo requeridas e ingrese los nombres o identificadores de los cilindros (si son varios, sepárelos por comas, ej: C, D).</p>

        <div id="contenedor-edades">
            <!-- Fila por defecto: 3 días -->
            <div class="age-row">
                <div style="font-weight: 600; font-size: 13.5px; color: #334155; width: 140px;">Edad de Rotura (Días):</div>
                <input type="number" name="edades_dias[]" required class="form-control" style="width: 80px;" value="3" min="1">
                <div style="font-weight: 600; font-size: 13.5px; color: #334155; width: 140px; margin-left: 15px;">Cilindros / Identificadores:</div>
                <input type="text" name="edades_identificadores[]" required placeholder="Ej: A" class="form-control" style="flex: 1;" value="A">
                <button type="button" class="btn-action-mini btn-del" style="margin-left: 15px;" onclick="eliminarFilaEdad(this)"><i class="fa-solid fa-trash"></i></button>
            </div>

            <!-- Fila por defecto: 7 días -->
            <div class="age-row">
                <div style="font-weight: 600; font-size: 13.5px; color: #334155; width: 140px;">Edad de Rotura (Días):</div>
                <input type="number" name="edades_dias[]" required class="form-control" style="width: 80px;" value="7" min="1">
                <div style="font-weight: 600; font-size: 13.5px; color: #334155; width: 140px; margin-left: 15px;">Cilindros / Identificadores:</div>
                <input type="text" name="edades_identificadores[]" required placeholder="Ej: B" class="form-control" style="flex: 1;" value="B">
                <button type="button" class="btn-action-mini btn-del" style="margin-left: 15px;" onclick="eliminarFilaEdad(this)"><i class="fa-solid fa-trash"></i></button>
            </div>

            <!-- Fila por defecto: 28 días -->
            <div class="age-row">
                <div style="font-weight: 600; font-size: 13.5px; color: #334155; width: 140px;">Edad de Rotura (Días):</div>
                <input type="number" name="edades_dias[]" required class="form-control" style="width: 80px;" value="28" min="1">
                <div style="font-weight: 600; font-size: 13.5px; color: #334155; width: 140px; margin-left: 15px;">Cilindros / Identificadores:</div>
                <input type="text" name="edades_identificadores[]" required placeholder="Ej: C, D" class="form-control" style="flex: 1;" value="C, D">
                <button type="button" class="btn-action-mini btn-del" style="margin-left: 15px;" onclick="eliminarFilaEdad(this)"><i class="fa-solid fa-trash"></i></button>
            </div>

            <!-- Fila por defecto: 56 días (Testigo) -->
            <div class="age-row">
                <div style="font-weight: 600; font-size: 13.5px; color: #334155; width: 140px;">Edad de Rotura (Días):</div>
                <input type="number" name="edades_dias[]" required class="form-control" style="width: 80px;" value="56" min="1">
                <div style="font-weight: 600; font-size: 13.5px; color: #334155; width: 140px; margin-left: 15px;">Cilindros / Identificadores:</div>
                <input type="text" name="edades_identificadores[]" required placeholder="Ej: Testigo" class="form-control" style="flex: 1;" value="Testigo">
                <button type="button" class="btn-action-mini btn-del" style="margin-left: 15px;" onclick="eliminarFilaEdad(this)"><i class="fa-solid fa-trash"></i></button>
            </div>
        </div>
    </div>

    <!-- BOTONES DE ACCIÓN -->
    <div style="display: flex; justify-content: flex-end; gap: 15px; margin-top: 25px; margin-bottom: 40px;">
        <a href="/Cycsa/publico/operaciones" class="form-control" style="text-decoration: none; cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b; text-align: center; width: 140px; padding: 12px 0;">Cancelar</a>
        <button type="submit" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600; width: 220px; padding: 12px 0;"><i class="fa-solid fa-save"></i> Registrar Muestras</button>
    </div>
</form>

<script>
    function toggleEdadesSection() {
        const select = document.getElementById('id_detalle_cotizacion');
        const selectedOption = select.options[select.selectedIndex];
        const formatoId = selectedOption ? selectedOption.getAttribute('data-formato-id') : '';
        
        const agesSection = document.getElementById('seccion-edades-rotura');
        
        // Formato ID 17 = Resistencia de Concreto, 9 = Flexión, 20 = Mortero, 21 = Núcleo, 10 = Bloques, 16 = Adoquines, 18 = Ladrillo, 19 = Esclerómetro
        const esConcreto = (formatoId === '17' || formatoId === '9' || formatoId === '20' || formatoId === '21' || formatoId === '10' || formatoId === '16' || formatoId === '18' || formatoId === '19');
        
        // Mantener la sección siempre visible en pantalla para evitar bloqueos
        agesSection.style.display = 'block';
        
        if (esConcreto) {
            // Especificaciones de concreto requeridas
            document.getElementById('input_nombre_lote').required = true;
            document.getElementById('input_fecha_moldeo').required = true;
            document.getElementById('input_diseno_resistencia').required = true;
            
            // Habilitar campos requeridos en edades
            agesSection.querySelectorAll('input').forEach(i => {
                i.disabled = false;
                i.required = true;
            });
        } else {
            // Especificaciones opcionales para otros ensayos
            document.getElementById('input_nombre_lote').required = false;
            document.getElementById('input_fecha_moldeo').required = false;
            document.getElementById('input_diseno_resistencia').required = false;
            
            // Dejar campos como opcionales pero activos si el usuario desea usarlos
            agesSection.querySelectorAll('input').forEach(i => {
                i.disabled = false;
                i.required = false;
            });
        }
    }

    function agregarFilaEdad() {
        const container = document.getElementById('contenedor-edades');
        const newRow = document.createElement('div');
        newRow.className = 'age-row';
        newRow.innerHTML = `
            <div style="font-weight: 600; font-size: 13.5px; color: #334155; width: 140px;">Edad de Rotura (Días):</div>
            <input type="number" name="edades_dias[]" required class="form-control" style="width: 80px;" min="1">
            <div style="font-weight: 600; font-size: 13.5px; color: #334155; width: 140px; margin-left: 15px;">Cilindros / Identificadores:</div>
            <input type="text" name="edades_identificadores[]" required placeholder="Ej: E" class="form-control" style="flex: 1;">
            <button type="button" class="btn-action-mini btn-del" style="margin-left: 15px;" onclick="eliminarFilaEdad(this)"><i class="fa-solid fa-trash"></i></button>
        `;
        container.appendChild(newRow);
    }

    function eliminarFilaEdad(button) {
        const row = button.closest('.age-row');
        const container = document.getElementById('contenedor-edades');
        if (container.children.length > 1) {
            row.remove();
        } else {
            alert('Debe tener al menos una edad de rotura registrada para la muestra.');
        }
    }

    // Inicializar al cargar para el valor preseleccionado
    document.addEventListener('DOMContentLoaded', () => {
        toggleEdadesSection();
        
        // --- CUSTOM SELECT PREMIUM CON BUSCADOR ---
        const select = document.getElementById('id_detalle_cotizacion');
        if (!select) return;

        // Ocultar select original
        select.style.display = 'none';

        // Crear contenedor principal
        const wrapper = document.createElement('div');
        wrapper.className = 'custom-select-wrapper';
        
        // Crear disparador (botón visible)
        const trigger = document.createElement('div');
        trigger.className = 'custom-select-trigger';
        trigger.tabIndex = 0;
        trigger.innerHTML = '<span class="trigger-label" style="font-weight: 500;">-- Seleccione el servicio facturado --</span> <i class="fa-solid fa-chevron-down" style="font-size: 11px; color: #64748b;"></i>';
        
        // Crear dropdown contenedor
        const dropdown = document.createElement('div');
        dropdown.className = 'custom-select-dropdown';
        
        // Crear caja de búsqueda
        const searchBox = document.createElement('div');
        searchBox.className = 'custom-select-search-box';
        const searchInput = document.createElement('input');
        searchInput.type = 'text';
        searchInput.placeholder = 'Buscar servicio o norma (ej: Concreto, ASTM)...';
        searchInput.className = 'custom-select-search';
        searchBox.appendChild(searchInput);
        dropdown.appendChild(searchBox);
        
        // Lista de opciones
        const optionsList = document.createElement('div');
        optionsList.className = 'custom-select-options-list';
        dropdown.appendChild(optionsList);
        
        wrapper.appendChild(trigger);
        wrapper.appendChild(dropdown);
        select.parentNode.insertBefore(wrapper, select);

        // Rellenar opciones personalizadas
        const originalOptions = Array.from(select.options);
        
        originalOptions.forEach((opt, idx) => {
            if (idx === 0) return; // Omitir el placeholder inicial
            
            const isOptDisabled = opt.disabled;
            const formatoId = opt.getAttribute('data-formato-id');
            const labelText = opt.text;
            const isSelected = opt.selected;
            
            const optionDiv = document.createElement('div');
            optionDiv.className = 'custom-select-option';
            if (isOptDisabled) optionDiv.classList.add('disabled');
            if (isSelected) {
                optionDiv.classList.add('selected');
                trigger.querySelector('.trigger-label').innerText = labelText;
                trigger.querySelector('.trigger-label').style.fontWeight = '600';
            }
            
            optionDiv.dataset.value = opt.value;
            optionDiv.dataset.formatoId = formatoId;
            
            let badgeHtml = '';
            if (isOptDisabled) {
                badgeHtml = '<span class="custom-select-badge badge-locked"><i class="fa-solid fa-lock"></i> Recibido</span>';
            } else {
                badgeHtml = '<span class="custom-select-badge badge-available"><i class="fa-solid fa-circle-check"></i> Disponible</span>';
            }
            
            optionDiv.innerHTML = `
                <div style="display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-flask" style="color: var(--cycsa-azul); font-size: 12.5px;"></i>
                    <span style="font-weight: 500;">${labelText}</span>
                </div>
                ${badgeHtml}
            `;
            
            optionDiv.addEventListener('click', (e) => {
                if (isOptDisabled) return;
                
                // Desmarcar anteriores y marcar actual
                optionsList.querySelectorAll('.custom-select-option').forEach(o => o.classList.remove('selected'));
                optionDiv.classList.add('selected');
                
                // Sincronizar select original y disparar evento de cambio
                select.value = opt.value;
                select.dispatchEvent(new Event('change'));
                
                // Actualizar disparador visual
                trigger.querySelector('.trigger-label').innerText = labelText;
                trigger.querySelector('.trigger-label').style.fontWeight = '600';
                
                // Cerrar
                dropdown.style.display = 'none';
                trigger.classList.remove('active');
            });
            
            optionsList.appendChild(optionDiv);
        });

        // Evento toggle al presionar disparador
        trigger.addEventListener('click', (e) => {
            e.stopPropagation();
            const isOpen = dropdown.style.display === 'block';
            
            // Cerrar otros dropdowns si hubieran
            dropdown.style.display = isOpen ? 'none' : 'block';
            trigger.classList.toggle('active', !isOpen);
            
            if (!isOpen) {
                searchInput.value = '';
                optionsList.querySelectorAll('.custom-select-option').forEach(o => o.style.display = 'flex');
                setTimeout(() => searchInput.focus(), 50);
            }
        });

        // Soporte para navegación de teclado en el trigger
        trigger.addEventListener('keydown', (e) => {
            if (e.key === 'Enter' || e.key === ' ') {
                e.preventDefault();
                trigger.click();
            }
        });

        // Filtrado en tiempo real (Buscador AJAX-like)
        searchInput.addEventListener('input', () => {
            const query = searchInput.value.toLowerCase().trim();
            optionsList.querySelectorAll('.custom-select-option').forEach(optionDiv => {
                const text = optionDiv.innerText.toLowerCase();
                if (text.includes(query)) {
                    optionDiv.style.display = 'flex';
                } else {
                    optionDiv.style.display = 'none';
                }
            });
        });

        // Cerrar al hacer clic fuera del control
        window.addEventListener('click', (e) => {
            if (!wrapper.contains(e.target)) {
                dropdown.style.display = 'none';
                trigger.classList.remove('active');
            }
        });
    });

    // Autocompletar datos de muestra declarada en la O/S
    function cargarMuestraDeclarada(nombre, descripcion, info) {
        document.getElementById('codigo_campo').value = nombre;
        
        let loteText = descripcion;
        if (info && info.trim() !== '') {
            loteText += ' - ' + info;
        }
        document.getElementById('input_nombre_lote').value = loteText;
        
        // Autocompletar también ID Cilindro
        document.getElementById('id_cilindro').value = nombre;
        
        // Foco y scroll suave al campo de código de campo
        const targetInput = document.getElementById('codigo_campo');
        targetInput.focus();
        targetInput.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
</script>
