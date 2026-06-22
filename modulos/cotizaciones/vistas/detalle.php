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
    
    @media (max-width: 768px) {
        .doc-container { padding: 15px !important; }
        .doc-header { flex-direction: column; align-items: flex-start; gap: 10px; }
    }
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
    <div style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch; margin-bottom: 30px; border: 1px solid #e2e8f0; border-radius: 6px;">
        <table class="tabla-visual" style="margin-bottom: 0;">
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
                    <td>
                        <strong><?= htmlspecialchars($detalle['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?></strong>
                        <?php
                        $metaParts = [];
                        if (!empty($detalle['codigo_servicio'])) {
                            $metaParts[] = 'Código: <strong>' . htmlspecialchars($detalle['codigo_servicio'], ENT_QUOTES, 'UTF-8') . '</strong>';
                        }
                        if (!empty($detalle['norma_astm'])) {
                            $metaParts[] = 'Norma: <strong>' . htmlspecialchars($detalle['norma_astm'], ENT_QUOTES, 'UTF-8') . '</strong>';
                        }
                        if (!empty($detalle['formato_reporte'])) {
                            $metaParts[] = 'Formato Reporte: <strong>' . htmlspecialchars($detalle['formato_reporte'], ENT_QUOTES, 'UTF-8') . '</strong>';
                        }
                        ?>
                        <?php if (!empty($metaParts)): ?>
                            <div style="margin-top: 5px; padding-top: 3px; border-top: 1px dashed #e2e8f0; font-size: 11px; color: #475569;">
                                <?= implode(' &bull; ', $metaParts) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?= $detalle['cantidad'] ?></td>
                    <td>C$ <?= number_format($detalle['precio_unitario'], 2) ?></td>
                    <td style="text-align: right; font-weight: 500;">C$ <?= number_format($detalle['subtotal'], 2) ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="display: flex; justify-content: flex-end;">
        <div style="width: 300px; background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: right;">
            <div style="margin-bottom: 5px;">Subtotal: C$ <?= number_format($cotizacion['subtotal'], 2) ?></div>
            <div style="margin-bottom: 5px;">IVA (15%): C$ <?= number_format($cotizacion['impuesto'], 2) ?></div>
            <div style="font-size: 18px; font-weight: 700; color: var(--cycsa-azul); margin-top: 10px; border-top: 1px solid #dee2e6; padding-top: 10px;">
                TOTAL: C$ <?= number_format($cotizacion['total'], 2) ?>
            </div>
        </div>
    </div>

    <?php if (tienePermiso('cotizaciones', 'crear_editar') && $cotizacion['estado'] == 'Borrador' && ($_SESSION['usuario_id'] == $cotizacion['id_usuario_creador'] || $_SESSION['usuario_rol'] == 1)): ?>
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
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
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

    <?php if (tienePermiso('cotizaciones', 'crear_editar') && $cotizacion['estado'] == 'Aprobada Internamente'): ?>
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

    <?php if (tienePermiso('cotizaciones', 'crear_editar') && in_array($cotizacion['estado'], ['Enviada al Cliente', 'Aprobada por Cliente', 'Rechazada por Cliente'])): ?>
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
    <?php if (tienePermiso('cotizaciones', 'crear_editar') && $cotizacion['estado'] == 'Observada' && $_SESSION['usuario_id'] == $cotizacion['id_usuario_creador']): ?>
        <div style="background: #fff1f2; border: 1px solid #fda4af; padding: 20px; border-radius: 8px; margin-top: 30px;">
            <h3 style="color: #9f1239; margin-top: 0;">⚠️ Cotización Observada</h3>
            <p style="margin-bottom: 0;"><strong>Motivo:</strong> <?= htmlspecialchars($cotizacion['motivo_observacion'], ENT_QUOTES, 'UTF-8') ?></p>
            <a href="/Cycsa/publico/cotizaciones/editar?id=<?= $cotizacion['id'] ?>" class="btn-aprobar" style="background: #e11d48; display: inline-block; text-decoration: none; margin-top: 15px;">
                <i class="fa-solid fa-pen-to-square"></i> Corregir y Re-enviar
            </a>
        </div>
    <?php endif; ?>

    <!-- 📄 HISTORIAL DE VERSIONES -->
    <?php if (!empty($versiones) && count($versiones) > 0): ?>
        <div style="margin-top: 40px; border-top: 2px solid #e2e8f0; padding-top: 30px;">
            <h3 style="font-size: 18px; margin-bottom: 20px; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-code-branch" style="color: var(--cycsa-azul);"></i> Historial de Versiones y Cambios
            </h3>
            
            <div style="display: flex; flex-direction: column; gap: 15px;">
                <?php foreach ($versiones as $v): 
                    $datos = json_decode($v['datos_json'], true);
                    $fecha = date('d/m/Y h:i A', strtotime($v['fecha_creacion']));
                ?>
                    <div style="border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; background: white; box-shadow: 0 1px 3px rgba(0,0,0,0.02);">
                        <!-- Cabecera de la versión -->
                        <div onclick="toggleVersion(<?= $v['id'] ?>)" style="background: #f8fafc; padding: 15px 20px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none; flex-wrap: wrap; gap: 10px; border-bottom: 1px solid #e2e8f0;">
                            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                                <span style="background: var(--cycsa-azul); color: white; padding: 3px 10px; border-radius: 20px; font-weight: 700; font-size: 12px; text-transform: uppercase;">
                                    v<?= $v['version'] ?>
                                </span>
                                <strong style="font-size: 14px; color: #334155;"><?= htmlspecialchars($v['motivo_cambio'] ?: 'Cambio registrado', ENT_QUOTES, 'UTF-8') ?></strong>
                                <span style="font-size: 12px; color: #64748b;"><?= $fecha ?></span>
                            </div>
                            <div style="display: flex; align-items: center; gap: 15px;">
                                <span style="font-weight: 700; color: #0f172a; font-size: 14px;">Total: C$ <?= number_format($datos['total'] ?? 0, 2) ?></span>
                                <i class="fa-solid fa-chevron-down" id="icon-version-<?= $v['id'] ?>" style="color: #64748b; transition: transform 0.2s;"></i>
                            </div>
                        </div>
                        
                        <!-- Cuerpo de la versión (Desplegable) -->
                        <div id="body-version-<?= $v['id'] ?>" style="display: none; padding: 20px; border-top: 1px solid #edf2f7; background: #fff;">
                            <!-- Info general de la versión -->
                            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; margin-bottom: 20px; font-size: 13px;">
                                <div style="background: #f8fafc; padding: 10px 15px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    <span style="color: #64748b; font-weight: 500; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 2px;">Atención a:</span>
                                    <strong><?= htmlspecialchars($datos['atencion_a'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></strong>
                                </div>
                                <div style="background: #f8fafc; padding: 10px 15px; border-radius: 6px; border: 1px solid #e2e8f0;">
                                    <span style="color: #64748b; font-weight: 500; font-size: 11px; text-transform: uppercase; display: block; margin-bottom: 2px;">Condiciones Comerciales:</span>
                                    <strong>Pago:</strong> <?= htmlspecialchars($datos['condicion_pago'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?><br>
                                    <strong>Entrega:</strong> <?= htmlspecialchars($datos['tiempo_entrega'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?><br>
                                    <strong>Vigencia:</strong> <?= htmlspecialchars($datos['vigencia_oferta'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </div>
                            
                            <!-- Tabla de items de esta versión -->
                            <div style="overflow-x: auto; width: 100%; border: 1px solid #e2e8f0; border-radius: 6px;">
                                <table style="width: 100%; border-collapse: collapse; min-width: 600px; font-size: 13px; text-align: left;">
                                    <thead>
                                        <tr style="background: #f1f5f9;">
                                            <th style="padding: 10px 12px; font-weight: 600; color: #475569; border-bottom: 1px solid #cbd5e1;">Descripción del Ensayo / Servicio</th>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #475569; border-bottom: 1px solid #cbd5e1; width: 10%;">Cant.</th>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #475569; border-bottom: 1px solid #cbd5e1; width: 20%; text-align: right;">Precio Unit.</th>
                                            <th style="padding: 10px 12px; font-weight: 600; color: #475569; border-bottom: 1px solid #cbd5e1; width: 20%; text-align: right;">Subtotal</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php if (!empty($datos['detalles'])): ?>
                                            <?php foreach ($datos['detalles'] as $det): ?>
                                                <tr>
                                                    <td style="padding: 12px; border-bottom: 1px solid #e2e8f0; font-weight: 500; color: #334155;">
                                                        <?= htmlspecialchars($det['descripcion_ensayo'] ?? 'Servicio', ENT_QUOTES, 'UTF-8') ?>
                                                    </td>
                                                    <td style="padding: 12px; border-bottom: 1px solid #e2e8f0; color: #334155;">
                                                        <?= $det['cantidad'] ?>
                                                    </td>
                                                    <td style="padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: right; color: #334155;">
                                                        C$ <?= number_format($det['precio_unitario'], 2) ?>
                                                    </td>
                                                    <td style="padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 600; color: #334155;">
                                                        C$ <?= number_format($det['subtotal'], 2) ?>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        <?php else: ?>
                                            <tr>
                                                <td colspan="4" style="text-align: center; padding: 15px; color: #94a3b8;">Sin detalles registrados.</td>
                                            </tr>
                                        <?php endif; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
        
        <script>
            function toggleVersion(id) {
                const body = document.getElementById('body-version-' + id);
                const icon = document.getElementById('icon-version-' + id);
                if (body.style.display === 'none') {
                    body.style.display = 'block';
                    icon.style.transform = 'rotate(180deg)';
                } else {
                    body.style.display = 'none';
                    icon.style.transform = 'rotate(0deg)';
                }
            }
        </script>
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