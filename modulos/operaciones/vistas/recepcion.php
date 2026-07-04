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
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Código de Campo (Hoja de Campo)</label>
                <input type="text" name="codigo_campo" required placeholder="Ej: MC-02" class="form-control">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Entregado Por (Nombre cliente/chofer)</label>
                <input type="text" name="entregado_por" placeholder="Nombre completo" class="form-control">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha/Hora Recepción</label>
                <input type="datetime-local" name="fecha_recepcion" required class="form-control" value="<?= date('Y-m-d\TH:i') ?>">
            </div>
        </div>

        <div class="form-group">
            <label style="font-weight: 600; font-size: 13px; color: #334155;">Observaciones Iniciales de Recepción</label>
            <textarea name="observaciones" rows="2" placeholder="Escribe detalles del estado de las muestras al recibirse..." class="form-control"></textarea>
        </div>
    </div>

    <!-- 2. DATOS TÉCNICOS DEL LOTE -->
    <div class="form-box" id="seccion-especificaciones-lote">
        <h4 class="form-section-title"><i class="fa-solid fa-flask" style="margin-right: 6px;"></i> Especificaciones Técnicas del Lote de Concreto</h4>
        <div class="grid-2">
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Identificación del Lote / Elemento Estructural</label>
                <input type="text" name="nombre_lote" id="input_nombre_lote" required placeholder="Ej: Columnas Eje C - Nivel 2" class="form-control">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha de Moldeo / Fabricación (T0)</label>
                <input type="date" name="fecha_moldeo" id="input_fecha_moldeo" required class="form-control" value="<?= date('Y-m-d') ?>">
            </div>
        </div>

        <div class="grid-3" style="margin-top: 10px;">
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Resistencia de Diseño Objetivo</label>
                <input type="text" name="diseno_resistencia" id="input_diseno_resistencia" required placeholder="Ej: 3000 PSI / 210 kg/cm²" class="form-control">
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Revenimiento (Slump) Medido</label>
                <div style="display: flex; gap: 8px;">
                    <input type="number" step="0.01" name="revenimiento_in" placeholder="Pulgadas" class="form-control" style="width: 50%;">
                    <input type="number" step="0.01" name="revenimiento_cm" placeholder="CM" class="form-control" style="width: 50%;">
                </div>
            </div>
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Temperatura Concreto (°C)</label>
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
                    <?php foreach ($servicios as $s): ?>
                        <option value="<?= $s['id'] ?>" data-formato-id="<?= $s['formato_id'] ?>" <?= ($s['id'] == ($idDetalle ?? 0)) ? 'selected' : '' ?>><?= htmlspecialchars($s['codigo_servicio'] ? $s['codigo_servicio'] . ' - ' : '', ENT_QUOTES, 'UTF-8') ?><?= htmlspecialchars($s['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?></option>
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
        const specsSection = document.getElementById('seccion-especificaciones-lote');
        
        // Formato ID 17 = Resistencia de Concreto, 9 = Flexión, 20 = Mortero, 21 = Núcleo
        const esConcreto = (formatoId === '17' || formatoId === '9' || formatoId === '20' || formatoId === '21');
        
        if (esConcreto) {
            agesSection.style.display = 'block';
            
            // Habilitar campos requeridos en edades
            agesSection.querySelectorAll('input').forEach(i => {
                i.disabled = false;
                i.required = true;
            });
            
            // Especificaciones de concreto requeridas
            document.getElementById('input_nombre_lote').required = true;
            document.getElementById('input_fecha_moldeo').required = true;
            document.getElementById('input_diseno_resistencia').required = true;
        } else {
            // Es otro ensayo (ej: Proctor) que no usa cilindros ni rotura de edades
            agesSection.style.display = 'none';
            
            // Deshabilitar campos para que no se envíen
            agesSection.querySelectorAll('input').forEach(i => {
                i.disabled = true;
                i.required = false;
            });
            
            // Especificaciones no requeridas
            document.getElementById('input_nombre_lote').required = false;
            document.getElementById('input_fecha_moldeo').required = false;
            document.getElementById('input_diseno_resistencia').required = false;
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
    });
</script>
