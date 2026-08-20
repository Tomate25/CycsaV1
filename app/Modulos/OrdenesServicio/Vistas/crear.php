<?php
// Vista para crear Orden de Servicio CYCSA-RG-FM-39 V1 (CYCSA ERP Premium Style)
?>
<style>
    .cycsa-card { background: white; border-radius: 12px; border: 1px solid #e2e8f0; box-shadow: 0 4px 15px rgba(0,0,0,0.03); padding: 25px; margin-bottom: 25px; }
    .cycsa-card-title { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: var(--cycsa-azul); border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; }
    
    .form-grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .form-grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    .form-group-cycsa { margin-bottom: 15px; }
    .form-group-cycsa label { font-size: 12px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 6px; }
    .form-control-cycsa { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 13.5px; box-sizing: border-box; transition: all 0.2s; }
    .form-control-cycsa:focus { outline: none; border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08); }
    .form-control-readonly { background-color: #f8fafc; font-weight: 600; color: #1e293b; }

    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 13.5px; background: white; border-radius: 8px; overflow: hidden; border: 1px solid #e2e8f0; }
    .tabla-cycsa th { background-color: #f8fafc; color: #475569; padding: 12px 16px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 11px; }
    .tabla-cycsa td { padding: 12px 16px; border-bottom: 1px solid #f1f5f9; color: #1e293b; }

    .btn-cycsa { display: inline-flex; align-items: center; justify-content: center; gap: 8px; border: 1px solid transparent; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .btn-cycsa-primary { background: var(--cycsa-azul); color: white; }
    .btn-cycsa-primary:hover { background: #0c2766; color: white; }
    .btn-cycsa-secondary { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .btn-cycsa-secondary:hover { background: #cbd5e1; color: #0f172a; }

    /* Estilo de la tarjeta de decisión */
    .bifurcacion-box { background: #f0f9ff; border: 2px solid #0284c7; border-radius: 12px; padding: 25px; margin-bottom: 30px; }
    .radio-option-card { background: white; border: 2px solid #cbd5e1; border-radius: 8px; padding: 16px 20px; cursor: pointer; display: flex; align-items: center; gap: 12px; font-weight: 700; font-size: 14.5px; transition: all 0.2s; }
    .radio-option-card:hover { border-color: var(--cycsa-azul); background: #f8fafc; }
    .radio-option-card.selected-no { border-color: #ef4444; background: #fff5f5; color: #991b1b; }
    .radio-option-card.selected-si { border-color: #10b981; background: #f0fdf4; color: #065f46; }
</style>

<div style="max-width: 1050px; margin: 0 auto; padding-bottom: 40px;">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-size: 22px; font-family: 'Outfit', sans-serif; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-file-contract" style="color: var(--cycsa-azul);"></i> Generar Orden de Servicio
            </h2>
            <p style="color: #64748b; margin-top: 4px; font-size: 13.5px; margin-bottom: 0;">
                Formato Oficial <strong>CYCSA-RG-FM-39 V1</strong>
            </p>
        </div>
        <a href="/Cycsa/publico/cotizaciones/detalle?id=<?= codificarId($cotizacion['id']) ?>" class="btn-cycsa btn-cycsa-secondary">
            <i class="fa-solid fa-arrow-left"></i> Volver a Cotización
        </a>
    </div>

    <form action="/Cycsa/publico/ordenes-servicio/guardar" method="POST">
        <input type="hidden" name="id_cotizacion" value="<?= $cotizacion['id'] ?>">
        <input type="hidden" name="id_cliente" value="<?= $cotizacion['id_cliente'] ?>">

        <!-- DATOS PRINCIPALES DE LA O/S -->
        <div class="cycsa-card">
            <div class="cycsa-card-title">
                <i class="fa-solid fa-circle-info"></i> 1. Encabezado de la Orden de Servicio
            </div>
            
            <div class="form-grid-3">
                <div class="form-group-cycsa">
                    <label>Doc. Número (Código O/S):</label>
                    <input type="text" name="codigo_os" class="form-control-cycsa form-control-readonly" value="<?= htmlspecialchars($codigo_os) ?>" readonly style="color: var(--cycsa-azul);">
                </div>
                <div class="form-group-cycsa">
                    <label>Fecha de Emisión:</label>
                    <input type="date" name="fecha_emision" class="form-control-cycsa" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="form-group-cycsa">
                    <label>Cotización Origen:</label>
                    <input type="text" class="form-control-cycsa form-control-readonly" value="<?= htmlspecialchars($cotizacion['codigo']) ?> (v<?= $cotizacion['version'] ?>)" readonly>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group-cycsa">
                    <label>Cliente (Razón Social):</label>
                    <input type="text" class="form-control-cycsa form-control-readonly" value="<?= htmlspecialchars($cotizacion['cliente_nombre']) ?>" readonly>
                </div>
                <div class="form-group-cycsa">
                    <label>Cédula / RUC:</label>
                    <input type="text" class="form-control-cycsa form-control-readonly" value="<?= htmlspecialchars($cotizacion['cliente_ruc'] ?? 'N/A') ?>" readonly>
                </div>
            </div>

            <div class="form-grid-2">
                <div class="form-group-cycsa">
                    <label>Atención a (Contacto Principal):</label>
                    <input type="text" name="atencion_a" class="form-control-cycsa" value="<?= htmlspecialchars($cotizacion['atencion_a'] ?? '') ?>" required>
                </div>
                <div class="form-group-cycsa">
                    <label>Forma de Pago:</label>
                    <input type="text" name="forma_pago" class="form-control-cycsa" value="<?= htmlspecialchars($cotizacion['condicion_pago'] ?? 'Pago contra entrega') ?>">
                </div>
            </div>

            <div class="form-group-cycsa">
                <label>Proyecto:</label>
                <textarea name="nombre_proyecto" class="form-control-cycsa" rows="2" required><?= htmlspecialchars($cotizacion['nombre_proyecto'] ?? '') ?></textarea>
            </div>
        </div>

        <!-- TABLA DE ENSAYOS INCLUIDOS -->
        <div class="cycsa-card">
            <div class="cycsa-card-title">
                <i class="fa-solid fa-vial"></i> 2. Ensayos y Servicios Contratados
            </div>
            
            <table class="tabla-cycsa">
                <thead>
                    <tr>
                        <th style="width: 70px; text-align: center;">Línea</th>
                        <th>Código y Descripción del Ensayo / Servicio</th>
                        <th style="width: 120px; text-align: center;">Cantidad</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (!empty($detalles)): ?>
                        <?php foreach ($detalles as $index => $det): ?>
                            <tr>
                                <td style="text-align: center; font-weight: 700; color: #64748b;"><?= $index + 1 ?></td>
                                <td>
                                    <strong style="color: var(--cycsa-azul);"><?= htmlspecialchars($det['codigo_servicio'] ?? 'CYCSA-PE') ?></strong>: 
                                    <?= htmlspecialchars($det['descripcion_ensayo'] ?? $det['nombre_ensayo'] ?? '') ?>
                                    <?php if (!empty($det['norma_astm'])): ?>
                                        <span style="background: #e0f2fe; color: #0369a1; padding: 2px 8px; border-radius: 10px; font-size: 11px; font-weight: 600; margin-left: 8px; border: 1px solid #7dd3fc;"><?= htmlspecialchars($det['norma_astm']) ?></span>
                                    <?php endif; ?>
                                </td>
                                <td style="text-align: center; font-weight: 700; color: #0f172a;">
                                    <?= number_format($det['cantidad'], 1) ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <tr>
                            <td colspan="3" style="text-align: center; color: #64748b; padding: 20px;">No hay ensayos registrados en la cotización.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- BIFURCACIÓN DE DECISIÓN (PASO 2) -->
        <div class="bifurcacion-box">
            <h3 style="margin: 0 0 10px 0; font-family: 'Outfit', sans-serif; font-size: 18px; color: #0369a1; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-route"></i> 3. Decisión de Logística (Bifurcación del Flujo)
            </h3>
            <p style="color: #475569; font-size: 13.5px; margin-bottom: 20px;">
                Defina si el laboratorio requiere enviar técnicos a realizar la toma o recolección de muestras en campo.
            </p>

            <label style="font-size: 14px; font-weight: 700; color: #0f172a; display: block; margin-bottom: 12px;">
                ¿Se requiere muestreo en campo?
            </label>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                <label class="radio-option-card selected-no" id="lbl-no">
                    <input type="radio" name="requiere_muestreo" value="0" checked onchange="actualizarBifurcacion(0)">
                    <i class="fa-solid fa-flask" style="font-size: 18px;"></i>
                    <div>
                        <div>NO (El cliente trae las muestras)</div>
                        <div style="font-weight: 400; font-size: 12px; opacity: 0.8; margin-top: 2px;">Redirige a la Hoja de Servicio inmediatamente</div>
                    </div>
                </label>

                <label class="radio-option-card" id="lbl-si">
                    <input type="radio" name="requiere_muestreo" value="1" onchange="actualizarBifurcacion(1)">
                    <i class="fa-solid fa-truck-pickup" style="font-size: 18px;"></i>
                    <div>
                        <div>SÍ (Requiere salida de campo)</div>
                        <div style="font-weight: 400; font-size: 12px; opacity: 0.8; margin-top: 2px;">Redirige a la vista de Programación de Muestreo</div>
                    </div>
                </label>
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; gap: 12px;">
            <a href="/Cycsa/publico/cotizaciones/detalle?id=<?= codificarId($cotizacion['id']) ?>" class="btn-cycsa btn-cycsa-secondary">
                Cancelar
            </a>
            <button type="submit" class="btn-cycsa btn-cycsa-primary" style="padding: 12px 28px; font-size: 15px;">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Orden de Servicio y Continuar
            </button>
        </div>
    </form>
</div>

<script>
    function actualizarBifurcacion(val) {
        const lblNo = document.getElementById('lbl-no');
        const lblSi = document.getElementById('lbl-si');
        if (val === 1) {
            lblSi.className = 'radio-option-card selected-si';
            lblNo.className = 'radio-option-card';
        } else {
            lblNo.className = 'radio-option-card selected-no';
            lblSi.className = 'radio-option-card';
        }
    }
</script>
