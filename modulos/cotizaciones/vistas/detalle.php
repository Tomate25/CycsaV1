<style>
    .doc-container { background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); max-width: 900px; margin: 0 auto; }
    .doc-header { display: flex; justify-content: space-between; border-bottom: 2px solid var(--cycsa-azul); padding-bottom: 20px; margin-bottom: 20px; }
    .info-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 30px; }
    .info-box { background: #f8f9fa; padding: 15px; border-radius: 6px; border: 1px solid #e9ecef; }
    .info-label { font-size: 11px; text-transform: uppercase; color: #6c757d; font-weight: 600; margin-bottom: 5px; display: block; }
    .info-valor { font-size: 14px; color: #333; font-weight: 500; }
    
    .tabla-visual { width: 100%; border-collapse: collapse; margin-bottom: 30px; }
    .tabla-visual th { background: #f1f5f9; padding: 12px; text-align: left; font-size: 13px; color: #475569; }
    .tabla-visual td { padding: 12px; border-bottom: 1px solid #e2e8f0; font-size: 14px; }
    
    .panel-aprobacion { background: #fffbeb; border: 1px solid #fcd34d; padding: 20px; border-radius: 8px; margin-top: 30px; }
    .btn-aprobar { background: #10b981; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; }
    .btn-observar { background: #ef4444; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; }
    
    .badge { padding: 5px 12px; border-radius: 20px; font-size: 13px; font-weight: 600; }
</style>

<div class="doc-container">
    <div style="margin-bottom: 20px;">
        <a href="/Cycsa/publico/cotizaciones" style="color: #6c757d; text-decoration: none; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Volver a la lista</a>
    </div>

    <?php if (isset($_SESSION['envio_exitoso'])): ?>
        <div style="background: #ecfdf5; border: 1px solid #6ee7b7; color: #065f46; padding: 15px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; font-size: 14px;">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($_SESSION['envio_exitoso'], ENT_QUOTES, 'UTF-8') ?>
        </div>
        <?php unset($_SESSION['envio_exitoso']); ?>
    <?php endif; ?>

    <div class="doc-header">
        <div>
            <h2 style="margin: 0; color: var(--cycsa-azul); font-size: 24px;">Cotización <?= htmlspecialchars($cotizacion['codigo'], ENT_QUOTES, 'UTF-8') ?></h2>
            <p style="margin: 5px 0 0 0; color: #6c757d;">Versión <?= $cotizacion['version'] ?> | Generada el <?= date('d/m/Y', strtotime($cotizacion['fecha_creacion'])) ?></p>
        </div>
        <div style="text-align: right;">
            <span class="badge" style="background: #e2e8f0; color: #475569; border: 1px solid #cbd5e1;"><?= htmlspecialchars($cotizacion['estado'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>

    <div class="info-grid">
        <div class="info-box">
            <span class="info-label">Datos del Cliente</span>
            <div class="info-valor"><strong>Empresa:</strong> <?= htmlspecialchars($cotizacion['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="info-valor"><strong>RUC:</strong> <?= htmlspecialchars($cotizacion['cliente_ruc'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="info-valor"><strong>Atención a:</strong> <?= htmlspecialchars($cotizacion['atencion_a'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
        <div class="info-box">
            <span class="info-label">Datos del Proyecto</span>
            <div class="info-valor"><strong>Proyecto:</strong> <?= htmlspecialchars($cotizacion['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="info-valor"><strong>Dirección:</strong> <?= htmlspecialchars($cotizacion['direccion_proyecto'], ENT_QUOTES, 'UTF-8') ?></div>
            <div class="info-valor"><strong>Prioridad:</strong> <?= htmlspecialchars($cotizacion['prioridad'], ENT_QUOTES, 'UTF-8') ?></div>
        </div>
    </div>

    <h3 style="font-size: 16px; margin-bottom: 15px; color: #333; border-bottom: 1px solid #eee; padding-bottom: 5px;">Detalle de Ensayos y Servicios</h3>
    <table class="tabla-visual">
        <thead>
            <tr>
                <th>Descripción</th>
                <th>Cant.</th>
                <th>Precio Unit.</th>
                <th style="text-align: right;">Subtotal</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($detalles as $detalle): ?>
            <tr>
                <td><?= htmlspecialchars($detalle['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?></td>
                <td><?= $detalle['cantidad'] ?></td>
                <td>C$ <?= number_format($detalle['precio_unitario'], 2) ?></td>
                <td style="text-align: right; font-weight: 500;">C$ <?= number_format($detalle['subtotal'], 2) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div style="display: flex; justify-content: flex-end;">
        <div style="width: 300px; background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: right;">
            <div style="margin-bottom: 5px;">Subtotal: C$ <?= number_format($cotizacion['subtotal'], 2) ?></div>
            <div style="margin-bottom: 5px;">IVA (15%): C$ <?= number_format($cotizacion['impuesto'], 2) ?></div>
            <div style="font-size: 18px; font-weight: 700; color: var(--cycsa-azul); margin-top: 10px; border-top: 1px solid #dee2e6; padding-top: 10px;">
                TOTAL: C$ <?= number_format($cotizacion['total'], 2) ?>
            </div>
        </div>
    </div>

    <?php if ($cotizacion['estado'] == 'Borrador' && ($_SESSION['usuario_id'] == $cotizacion['id_usuario_creador'] || $_SESSION['usuario_rol'] == 1)): ?>
        <div style="background: #f8fafc; border: 1px solid #cbd5e1; padding: 20px; border-radius: 8px; margin-top: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h3 style="margin: 0 0 5px 0; color: #475569;"><i class="fa-solid fa-file-lines"></i> Cotización en Borrador</h3>
                <p style="margin: 0; font-size: 14px; color: #64748b;">Esta cotización se encuentra en borrador. Envíala a revisión para que la gerencia pueda revisarla y aprobarla.</p>
            </div>
            <form action="/Cycsa/publico/cotizaciones/enviar-revision" method="POST" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= $cotizacion['id'] ?>">
                <button type="submit" style="background: #4f46e5; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif;"><i class="fa-solid fa-paper-plane"></i> Enviar a Revisión</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if (($_SESSION['usuario_rol'] == 1) && ($cotizacion['estado'] == 'Borrador' || $cotizacion['estado'] == 'En Revision')): ?>
        <div class="panel-aprobacion">
            <h3 style="margin: 0 0 15px 0; color: #b45309;"><i class="fa-solid fa-clipboard-check"></i> Revisión de Gerencia</h3>
            <p style="font-size: 14px; color: #78350f; margin-bottom: 20px;">Revisa los datos comerciales. Si todo es correcto, apruébala para generar el PDF oficial. Si hay errores, devuélvela con observaciones.</p>
            
            <form action="/Cycsa/publico/cotizaciones/revision" method="POST" id="form-revision">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'], ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= $cotizacion['id'] ?>">
                <input type="hidden" name="accion" id="input-accion" value="">

                <textarea name="motivo_observacion" id="txt-motivo" placeholder="Escribe el motivo del rechazo u observación aquí..." style="width: 100%; padding: 10px; border: 1px solid #fcd34d; border-radius: 4px; font-family: 'Inter', sans-serif; display: none; margin-bottom: 15px;" rows="3"></textarea>

                <div style="display: flex; gap: 10px;">
                    <button type="button" class="btn-aprobar" onclick="enviarRevision('aprobar')"><i class="fa-solid fa-check"></i> Aprobar Cotización</button>
                    <button type="button" class="btn-observar" onclick="mostrarCajaObservacion()"><i class="fa-solid fa-xmark"></i> Devolver con Observación</button>
                </div>
            </form>
        </div>
    <?php endif; ?>

    <?php if ($cotizacion['estado'] == 'Aprobada Internamente'): ?>
        <div style="background: #ecfdf5; border: 1px solid #6ee7b7; padding: 20px; border-radius: 8px; margin-top: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h3 style="margin: 0 0 5px 0; color: #047857;"><i class="fa-solid fa-circle-check"></i> Cotización Aprobada</h3>
                <p style="margin: 0; font-size: 14px; color: #065f46;">Lista para ser enviada de manera formal al correo del cliente (<?= htmlspecialchars($cotizacion['cliente_email'] ?: 'abdiasl085@gmail.com', ENT_QUOTES, 'UTF-8') ?>).</p>
            </div>
            <form action="/Cycsa/publico/cotizaciones/enviar" method="POST" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= $cotizacion['id'] ?>">
                <button type="submit" style="background: #059669; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif;"><i class="fa-solid fa-paper-plane"></i> Enviar al Cliente</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if (in_array($cotizacion['estado'], ['Enviada al Cliente', 'Aprobada por Cliente', 'Rechazada por Cliente'])): ?>
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 20px; border-radius: 8px; margin-top: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h3 style="margin: 0 0 5px 0; color: #1e3a8a;"><i class="fa-solid fa-paper-plane"></i> Cotización Enviada</h3>
                <p style="margin: 0; font-size: 14px; color: #1e40af;">Esta cotización ya fue enviada formalmente al cliente (<?= htmlspecialchars($cotizacion['cliente_email'] ?: 'abdiasl085@gmail.com', ENT_QUOTES, 'UTF-8') ?>).</p>
            </div>
            <form action="/Cycsa/publico/cotizaciones/enviar" method="POST" style="margin: 0;" onsubmit="return confirm('¿Deseas volver a enviar el correo al cliente?');">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= $cotizacion['id'] ?>">
                <button type="submit" style="background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif;"><i class="fa-solid fa-arrows-rotate"></i> Re-enviar Correo</button>
            </form>
        </div>
    <?php endif; ?>
    <?php if ($cotizacion['estado'] == 'Observada' && $_SESSION['usuario_id'] == $cotizacion['id_usuario_creador']): ?>
        <div style="background: #fff1f2; border: 1px solid #fda4af; padding: 20px; border-radius: 8px; margin-top: 30px;">
            <h3 style="color: #9f1239; margin-top: 0;">⚠️ Cotización Observada</h3>
            <p style="margin-bottom: 0;"><strong>Motivo:</strong> <?= htmlspecialchars($cotizacion['motivo_observacion'], ENT_QUOTES, 'UTF-8') ?></p>
            <a href="/Cycsa/publico/cotizaciones/editar?id=<?= $cotizacion['id'] ?>" class="btn-aprobar" style="background: #e11d48; display: inline-block; text-decoration: none; margin-top: 15px;">
                <i class="fa-solid fa-pen-to-square"></i> Corregir y Re-enviar
            </a>
        </div>
    <?php endif; ?>
</div>

<script>
    function mostrarCajaObservacion() {
        const caja = document.getElementById('txt-motivo');
        if (caja.style.display === 'none') {
            caja.style.display = 'block';
            caja.required = true;
            caja.focus();
        } else {
            // Si ya está visible, significa que escribieron el motivo y quieren enviarlo
            if (caja.value.trim() === '') {
                alert('Debes escribir un motivo para devolver la cotización.');
                return;
            }
            enviarRevision('observar');
        }
    }

    function enviarRevision(accion) {
        if (accion === 'aprobar') {
            if (!confirm('¿Estás seguro de aprobar esta cotización? No podrá ser editada después.')) return;
        }
        document.getElementById('input-accion').value = accion;
        document.getElementById('form-revision').submit();
    }
</script>