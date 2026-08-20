<?php
$rutaSchema = dirname(__DIR__, 3) . '/database/ensayos/formatos_schema.json';
$formatosSchemaJson = file_exists($rutaSchema) ? file_get_contents($rutaSchema) : '{}';
?>
<style>
    .doc-container { max-width: 1250px; margin: 0 auto; background: white; padding: 40px; border-radius: 8px; box-shadow: 0 4px 15px rgba(0,0,0,0.05); }
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
        
        .modal-premium { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
        .modal-premium-content { background-color: #fff; margin: 10% auto; padding: 30px; border: 1px solid #e2e8f0; width: 420px; border-radius: 12px; text-align: left; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
        .btn-cerrar { background: none; border: none; font-size: 20px; color: #94a3b8; cursor: pointer; }
        .btn-cerrar:hover { color: #475569; }
        .form-group { margin-bottom: 16px; display: flex; flex-direction: column; gap: 6px; }
        .form-control { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; }
        .form-control:focus { border-color: var(--cycsa-azul); }
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
        <div style="text-align: right; display: flex; align-items: center; gap: 10px; justify-content: flex-end;">
            <?php if (in_array($cotizacion['estado'], ['Aprobada por Cliente', 'Aprobada Internamente', 'Enviada al Cliente'])): ?>
                <a href="/Cycsa/publico/ordenes-servicio/crear?id_cotizacion=<?= $cotizacion['id'] ?>" style="background-color: #103487; color: white; border: none; padding: 6px 15px; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; font-family: 'Inter', sans-serif; box-shadow: 0 2px 4px rgba(16, 52, 135, 0.2);">
                    <i class="fa-solid fa-file-contract"></i> Generar O/S (CYCSA-RG-FM-39 V1)
                </a>
            <?php endif; ?>
            <a href="/Cycsa/publico/cotizaciones/imprimir?id=<?= codificarId($cotizacion['id']) ?>" target="_blank" style="background-color: #e31837; color: white; border: none; padding: 6px 15px; border-radius: 4px; cursor: pointer; font-size: 13px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px; text-decoration: none; font-family: 'Inter', sans-serif; box-shadow: 0 2px 4px rgba(227, 24, 55, 0.2);">
                <i class="fa-solid fa-file-pdf"></i> Imprimir PDF
            </a>
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

    <?php if ($cotizacion['estado'] === 'Aprobada por Cliente'): ?>
        <div class="info-box" style="grid-column: span 2; background-color: #f8fafc; border: 1px solid #cbd5e1; margin-bottom: 30px; padding: 20px;">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">
                <span class="info-label" style="font-size: 12px; font-weight: 700; color: var(--cycsa-azul); margin: 0; display: flex; align-items: center; gap: 8px; text-transform: uppercase;">
                    <i class="fa-solid fa-gears"></i> Seguimiento Logístico y Operaciones
                </span>
                <?php if (tienePermiso('operaciones', 'crear_editar')): ?>
                    <button type="button" onclick="abrirProgramacionCotizacion()" style="background-color: var(--cycsa-azul); color: white; border: none; padding: 6px 12px; border-radius: 4px; cursor: pointer; font-size: 12px; font-weight: 600; display: flex; align-items: center; gap: 5px; font-family: 'Inter', sans-serif;">
                        <i class="fa-solid fa-calendar-plus"></i> Programar
                    </button>
                <?php endif; ?>
            </div>
            
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px;">
                <div>
                    <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; display: block; margin-bottom: 3px;">Día de Entrega</span>
                    <span style="font-size: 14px; font-weight: 700; color: #166534;">
                        <i class="fa-solid fa-truck" style="margin-right: 4px; opacity: 0.8;"></i>
                        <?= $cotizacion['fecha_entrega'] ? date('d/m/Y', strtotime($cotizacion['fecha_entrega'])) : '<span style="color:#94a3b8; font-weight:normal;">Sin definir</span>' ?>
                    </span>
                </div>
                
                <div>
                    <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; display: block; margin-bottom: 3px;">Día de Seguimiento</span>
                    <span style="font-size: 14px; font-weight: 700; color: #2563eb;">
                        <i class="fa-solid fa-calendar-check" style="margin-right: 4px; opacity: 0.8;"></i>
                        <?= $cotizacion['fecha_seguimiento'] ? date('d/m/Y', strtotime($cotizacion['fecha_seguimiento'])) : '<span style="color:#94a3b8; font-weight:normal;">Sin definir</span>' ?>
                    </span>
                </div>
                
                <div>
                    <span style="font-size: 11px; text-transform: uppercase; color: #64748b; font-weight: 600; display: block; margin-bottom: 3px;">Estado Operativo</span>
                    <span class="badge" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 12px; padding: 3px 10px; display: inline-block; margin-top: 2px;">
                        <?= htmlspecialchars($cotizacion['estado_operativo'] ?? 'Pendiente', ENT_QUOTES, 'UTF-8') ?>
                    </span>
                </div>
            </div>
            
            <?php if (!empty($cotizacion['notas_operativas'])): ?>
                <div style="margin-top: 15px; background: #fffbeb; border: 1px solid #fef3c7; padding: 12px; border-radius: 6px; color: #b45309; font-size: 13px;">
                    <strong>Notas e Instrucciones de Operación:</strong>
                    <p style="margin-top: 4px; margin-bottom: 0; color: #78350f;"><?= nl2br(htmlspecialchars($cotizacion['notas_operativas'], ENT_QUOTES, 'UTF-8')) ?></p>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>

    <h3 style="font-size: 16px; margin-bottom: 15px; color: #333; border-bottom: 1px solid #eee; padding-bottom: 5px;">Detalle de Ensayos y Servicios</h3>
    <div style="overflow-x: auto; width: 100%; -webkit-overflow-scrolling: touch; margin-bottom: 30px; border: 1px solid #e2e8f0; border-radius: 6px;">
        <table class="tabla-visual" style="margin-bottom: 0;">
            <thead>
                <tr>
                    <th>Descripción</th>
                    <th>Cant.</th>
                    <th>Precio Unit.</th>
                    <th>Subtotal</th>
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
                        if (!empty($detalle['observaciones'])) {
                            $metaParts[] = 'Tiempo Entrega: <strong>' . htmlspecialchars($detalle['observaciones'], ENT_QUOTES, 'UTF-8') . '</strong>';
                        }
                        ?>
                        <?php if (!empty($metaParts)): ?>
                            <div style="margin-top: 5px; padding-top: 3px; border-top: 1px dashed #e2e8f0; font-size: 11px; color: #475569;">
                                <?= implode(' &bull; ', $metaParts) ?>
                            </div>
                        <?php endif; ?>
                    </td>
                    <td><?= $detalle['cantidad'] ?></td>
                    <td>C$ <?= number_format($detalle['precio_unitario'], 2, '.', ',') ?></td>
                    <td>C$ <?= number_format($detalle['subtotal'], 2, '.', ',') ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div style="display: flex; justify-content: flex-end;">
        <div style="width: 350px; background: #f8f9fa; padding: 15px; border-radius: 6px; text-align: right;">
            <div style="margin-bottom: 5px;">Subtotal: C$ <?= number_format($cotizacion['subtotal'], 2, '.', ',') ?></div>
            <?php if ((float)($cotizacion['descuento'] ?? 0) > 0): ?>
                <div style="margin-bottom: 5px; color: #dc2626;">Descuento: -C$ <?= number_format($cotizacion['descuento'], 2, '.', ',') ?></div>
            <?php endif; ?>
            <div style="margin-bottom: 5px;">
                IVA (15%): C$ <?= number_format($cotizacion['impuesto'], 2, '.', ',') ?>
                <?php if ((int)($cotizacion['exonerado'] ?? 0)): ?>
                    <span style="font-size: 11px; color: #16a34a; font-weight: 600; display: block;">(Exonerado<?= !empty($cotizacion['exoneracion_no']) ? ' - Aval: ' . htmlspecialchars($cotizacion['exoneracion_no'], ENT_QUOTES, 'UTF-8') : '' ?>)</span>
                <?php endif; ?>
            </div>
            <div style="font-size: 18px; font-weight: 700; color: var(--cycsa-azul); margin-top: 10px; border-top: 1px solid #dee2e6; padding-top: 10px;">
                TOTAL: C$ <?= number_format($cotizacion['total'], 2, '.', ',') ?>
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
                <input type="hidden" name="id" value="<?= codificarId($cotizacion['id']) ?>">
                <button type="submit" style="background: #4f46e5; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif;"><i class="fa-solid fa-paper-plane"></i> Enviar a Revisión</button>
            </form>
        </div>
    <?php endif; ?>

    <?php if (($_SESSION['usuario_rol'] == 1) && ($cotizacion['estado'] == 'Borrador' || $cotizacion['estado'] == 'En Revision')): ?>
        <div class="panel-aprobacion">
            <h3 style="margin: 0 0 15px 0; color: #b45309;"><i class="fa-solid fa-clipboard-check"></i> Revisión de Gerencia</h3>
            <p style="font-size: 14px; color: #78350f; margin-bottom: 20px;">Revisa los datos comerciales. Si todo es correcto, apruébala para generar el PDF oficial. Si hay errores, devuélvela con observaciones.</p>
            
            <?php if (!empty($cotizacion['motivo_rechazo_cliente'])): ?>
                <div style="background: #fff5f5; border: 1px solid #feb2b2; padding: 15px; border-radius: 6px; margin-bottom: 20px; color: #9b2c2c; font-size: 13.5px; border-left: 4px solid #ef4444; text-align: left;">
                    <strong><i class="fa-solid fa-circle-exclamation"></i> Devuelta por el Cliente (Lo que no le parece):</strong><br>
                    <span style="display: block; margin-top: 5px; color: #4a5568; font-style: italic;">"<?= htmlspecialchars($cotizacion['motivo_rechazo_cliente'], ENT_QUOTES, 'UTF-8') ?>"</span>
                </div>
            <?php endif; ?>
            
            <form action="/Cycsa/publico/cotizaciones/revision" method="POST" id="form-revision">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= codificarId($cotizacion['id']) ?>">
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
        <?php $tieneEmail = !empty($cotizacion['cliente_email']); ?>
        <div style="background: <?= $tieneEmail ? '#ecfdf5' : '#fffbeb' ?>; border: 1px solid <?= $tieneEmail ? '#6ee7b7' : '#fde68a' ?>; padding: 20px; border-radius: 8px; margin-top: 30px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
            <div>
                <h3 style="margin: 0 0 5px 0; color: <?= $tieneEmail ? '#047857' : '#b45309' ?>;"><i class="fa-solid <?= $tieneEmail ? 'fa-circle-check' : 'fa-triangle-exclamation' ?>"></i> <?= $tieneEmail ? 'Cotización Aprobada' : 'Cliente sin Correo Registrado' ?></h3>
                <p style="margin: 0; font-size: 14px; color: <?= $tieneEmail ? '#065f46' : '#78350f' ?>;">
                    <?php if ($tieneEmail): ?>
                        Lista para ser enviada de manera formal al correo del cliente (<?= htmlspecialchars($cotizacion['cliente_email'], ENT_QUOTES, 'UTF-8') ?>).
                    <?php else: ?>
                        Esta cotización puede ser entregada en físico. Al continuar, se registrará el envío manual en el sistema.
                    <?php endif; ?>
                </p>
            </div>
            <form action="/Cycsa/publico/cotizaciones/enviar" method="POST" style="margin: 0;">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id" value="<?= codificarId($cotizacion['id']) ?>">
                <?php if ($tieneEmail): ?>
                    <button type="submit" style="background: #059669; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif;"><i class="fa-solid fa-paper-plane"></i> Enviar al Cliente</button>
                <?php else: ?>
                    <button type="submit" style="background: #d97706; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif;"><i class="fa-solid fa-hand-holding-hand"></i> Registrar Envío Manual</button>
                <?php endif; ?>
            </form>
        </div>
    <?php endif; ?>

    <?php if (tienePermiso('cotizaciones', 'crear_editar') && in_array($cotizacion['estado'], ['Enviada al Cliente', 'Aprobada por Cliente'])): ?>
        <?php $tieneEmail = !empty($cotizacion['cliente_email']); ?>
        <div style="background: #eff6ff; border: 1px solid #bfdbfe; padding: 20px; border-radius: 8px; margin-top: 30px; display: flex; flex-direction: column; gap: 15px;">
            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; width: 100%;">
                <div>
                    <h3 style="margin: 0 0 5px 0; color: #1e3a8a;"><i class="fa-solid fa-paper-plane"></i> Cotización Enviada</h3>
                    <p style="margin: 0; font-size: 14px; color: #1e40af;">
                        <?php if ($tieneEmail): ?>
                            Esta cotización ya fue enviada formalmente al cliente (<?= htmlspecialchars($cotizacion['cliente_email'], ENT_QUOTES, 'UTF-8') ?>).
                        <?php else: ?>
                            Esta cotización fue registrada como enviada de forma física/manual (El cliente no tiene correo registrado).
                        <?php endif; ?>
                    </p>
                </div>
                <?php if ($tieneEmail): ?>
                    <form action="/Cycsa/publico/cotizaciones/enviar" method="POST" style="margin: 0;" onsubmit="return confirm('¿Deseas volver a enviar el correo al cliente?');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= codificarId($cotizacion['id']) ?>">
                        <button type="submit" style="background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 4px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif;"><i class="fa-solid fa-arrows-rotate"></i> Re-enviar Correo</button>
                    </form>
                <?php endif; ?>
            </div>
            
            <?php if ($cotizacion['estado'] == 'Enviada al Cliente'): ?>
                <div style="border-top: 1px solid #bfdbfe; padding-top: 15px; margin-top: 5px;">
                    <h4 style="margin: 0 0 10px 0; color: #1e3a8a; font-size: 14px;"><i class="fa-solid fa-user-tie"></i> Acciones Administrativas (En nombre del cliente)</h4>
                    <p style="font-size: 13px; color: #4b5563; margin-bottom: 15px;">Si el cliente no puede aceptar/rechazar en línea por su cuenta, puedes registrar la decisión por él desde aquí.</p>
                    
                    <form action="/Cycsa/publico/cotizaciones/decision-administrativa" method="POST" id="form-admin-decision" style="display: flex; flex-direction: column; gap: 10px; max-width: 550px; margin: 0;">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= codificarId($cotizacion['id']) ?>">
                        <input type="hidden" name="accion" id="admin-accion" value="">
                        
                        <div id="admin-motivo-container" style="display: none; margin-bottom: 10px; width: 100%;">
                            <label style="font-size: 12px; font-weight: 600; color: #b91c1c; display: block; margin-bottom: 5px;">Motivo del Rechazo:</label>
                            <textarea name="motivo_rechazo" id="admin-motivo" placeholder="Escriba el motivo por el cual el cliente rechaza la cotización..." style="width: 100%; padding: 8px; border: 1px solid #fda4af; border-radius: 4px; font-family: 'Inter', sans-serif;" rows="2"></textarea>
                        </div>
                        
                        <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                            <button type="button" onclick="confirmarDecisionAdmin('aceptar')" style="background: #10b981; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 13px;"><i class="fa-solid fa-thumbs-up"></i> Aprobar en nombre del Cliente</button>
                            <button type="button" id="btn-admin-rechazar-init" onclick="mostrarRechazoAdmin()" style="background: #ef4444; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 13px;"><i class="fa-solid fa-thumbs-down"></i> Rechazar en nombre del Cliente</button>
                            <button type="button" id="btn-admin-rechazar-confirm" onclick="confirmarDecisionAdmin('rechazar')" style="background: #b91c1c; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 13px; display: none;"><i class="fa-solid fa-circle-check"></i> Confirmar Rechazo</button>
                            <button type="button" id="btn-admin-cancelar" onclick="cancelarRechazoAdmin()" style="background: #6b7280; color: white; border: none; padding: 8px 16px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 13px; display: none;">Cancelar</button>
                        </div>

                        <!-- MODAL DE CONFIRMACIÓN ADMINISTRATIVO -->
                        <div id="modalAdminAprobar" class="modal-premium">
                            <div class="modal-premium-content" style="width: 450px;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
                                    <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700;">Confirmar Aprobación</h3>
                                    <button type="button" onclick="cerrarAdminAprobarModal()" class="btn-cerrar">&times;</button>
                                </div>
                                
                                <p style="color: #64748b; font-size: 13.5px; margin-bottom: 25px;">¿Está seguro de que desea aprobar esta cotización en nombre del cliente? Al confirmar, se creará la Orden de Servicio automáticamente y comenzará el flujo operativo en el laboratorio.</p>
                                
                                <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 20px;">
                                    <button type="button" onclick="cerrarAdminAprobarModal()" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b; width: auto; padding: 8px 16px; margin: 0;">Cancelar</button>
                                    <button type="button" onclick="enviarAprobacionAdminDirecta()" class="form-control" style="cursor: pointer; background: #10b981; border: 1px solid #10b981; color: white; font-weight: 600; width: auto; padding: 8px 20px; margin: 0;">Confirmar y Aprobar</button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            <?php endif; ?>
        </div>
    <?php endif; ?>
    <?php 
    $mostrarCajaAlerta = false;
    $tituloAlerta = '';
    $motivoAlerta = '';
    $claseAlerta = '';
    
    if (tienePermiso('cotizaciones', 'crear_editar') && ($_SESSION['usuario_id'] == $cotizacion['id_usuario_creador'] || $_SESSION['usuario_rol'] == 1)) {
        if ($cotizacion['estado'] === 'Observada') {
            $mostrarCajaAlerta = true;
            $tituloAlerta = '⚠️ Cotización Observada (Interna)';
            $motivoAlerta = $cotizacion['motivo_observacion'];
            $claseAlerta = 'background: #fffbeb; border: 1px solid #fcd34d; color: #b45309;';
        } elseif ($cotizacion['estado'] === 'Rechazada por Cliente') {
            $mostrarCajaAlerta = true;
            $tituloAlerta = '⚠️ Cotización Devuelta / Rechazada por el Cliente';
            $motivoAlerta = $cotizacion['motivo_rechazo_cliente'];
            $claseAlerta = 'background: #fff1f2; border: 1px solid #fda4af; color: #9f1239;';
        }
    }
    
    if ($mostrarCajaAlerta): ?>
        <div style="padding: 20px; border-radius: 8px; margin-top: 30px; <?= $claseAlerta ?>">
            <h3 style="margin-top: 0; font-size: 16px; font-weight: 700;"><?= $tituloAlerta ?></h3>
            <p style="margin-bottom: 0; font-size: 14px;"><strong>Motivo indicado:</strong> <?= htmlspecialchars($motivoAlerta ?? 'No especificado', ENT_QUOTES, 'UTF-8') ?></p>
            <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 15px;">
                <a href="/Cycsa/publico/cotizaciones/editar?id=<?= codificarId($cotizacion['id']) ?>" class="btn-aprobar" style="background: #e11d48; display: inline-block; text-decoration: none; font-family: 'Inter', sans-serif; margin-top: 0;">
                    <i class="fa-solid fa-pen-to-square"></i> Corregir y Re-enviar
                </a>
                <?php if ($cotizacion['estado'] === 'Rechazada por Cliente'): ?>
                    <form action="/Cycsa/publico/cotizaciones/enviar" method="POST" style="margin: 0;" onsubmit="return confirm('¿Deseas volver a enviar esta cotización al cliente sin realizar cambios? Se archivará la versión actual V<?= $cotizacion['version'] ?> y se generará una nueva versión V<?= ($cotizacion['version'] + 1) ?>.');">
                        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                        <input type="hidden" name="id" value="<?= codificarId($cotizacion['id']) ?>">
                        <button type="submit" class="btn-aprobar" style="background: #2563eb; cursor: pointer; border: none; font-family: 'Inter', sans-serif;">
                            <i class="fa-solid fa-paper-plane"></i> Volver a Enviar (Nueva Versión)
                        </button>
                    </form>
                <?php endif; ?>
            </div>
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
                                <span style="font-weight: 700; color: #0f172a; font-size: 14px;">Total: C$ <?= number_format($datos['total'] ?? 0, 2, '.', ',') ?></span>
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
                                                        <?php
                                                        $metaPartsV = [];
                                                        if (!empty($det['codigo_servicio'])) {
                                                            $metaPartsV[] = 'Código: <strong>' . htmlspecialchars($det['codigo_servicio'], ENT_QUOTES, 'UTF-8') . '</strong>';
                                                        }
                                                        if (!empty($det['norma_astm'])) {
                                                            $metaPartsV[] = 'Norma: <strong>' . htmlspecialchars($det['norma_astm'], ENT_QUOTES, 'UTF-8') . '</strong>';
                                                        }
                                                        if (!empty($det['formato_reporte'])) {
                                                            $metaPartsV[] = 'Formato Reporte: <strong>' . htmlspecialchars($det['formato_reporte'], ENT_QUOTES, 'UTF-8') . '</strong>';
                                                        }
                                                        if (!empty($det['observaciones'])) {
                                                            $metaPartsV[] = 'Tiempo Entrega: <strong>' . htmlspecialchars($det['observaciones'], ENT_QUOTES, 'UTF-8') . '</strong>';
                                                        }
                                                        ?>
                                                        <?php if (!empty($metaPartsV)): ?>
                                                            <div style="margin-top: 5px; padding-top: 3px; border-top: 1px dashed #e2e8f0; font-size: 11px; color: #475569;">
                                                                <?= implode(' &bull; ', $metaPartsV) ?>
                                                            </div>
                                                        <?php endif; ?>
                                                    </td>
                                                    <td style="padding: 12px; border-bottom: 1px solid #e2e8f0; color: #334155;">
                                                        <?= $det['cantidad'] ?>
                                                    </td>
                                                    <td style="padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: right; color: #334155;">
                                                        C$ <?= number_format($det['precio_unitario'], 2, '.', ',') ?>
                                                    </td>
                                                    <td style="padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: right; font-weight: 600; color: #334155;">
                                                        C$ <?= number_format($det['subtotal'], 2, '.', ',') ?>
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

    function mostrarRechazoAdmin() {
        document.getElementById('admin-motivo-container').style.display = 'block';
        document.getElementById('admin-motivo').required = true;
        document.getElementById('admin-motivo').focus();
        
        document.getElementById('btn-admin-rechazar-init').style.display = 'none';
        document.getElementById('btn-admin-rechazar-confirm').style.display = 'inline-block';
        document.getElementById('btn-admin-cancelar').style.display = 'inline-block';
    }

    function cancelarRechazoAdmin() {
        document.getElementById('admin-motivo-container').style.display = 'none';
        document.getElementById('admin-motivo').required = false;
        document.getElementById('admin-motivo').value = '';
        
        document.getElementById('btn-admin-rechazar-init').style.display = 'inline-block';
        document.getElementById('btn-admin-rechazar-confirm').style.display = 'none';
        document.getElementById('btn-admin-cancelar').style.display = 'none';
    }

    function abrirAdminAprobarModal() {
        document.getElementById('modalAdminAprobar').style.display = 'block';
    }

    function cerrarAdminAprobarModal() {
        document.getElementById('modalAdminAprobar').style.display = 'none';
    }

    function enviarAprobacionAdminDirecta() {
        cerrarAdminAprobarModal();
        document.getElementById('admin-accion').value = 'aceptar';
        document.getElementById('form-admin-decision').submit();
    }

    function confirmarDecisionAdmin(accion) {
        if (accion === 'aceptar') {
            abrirAdminAprobarModal();
        } else if (accion === 'rechazar') {
            const motivo = document.getElementById('admin-motivo').value.trim();
            if (motivo === '') {
                alert('Por favor, especifique el motivo por el cual el cliente rechaza la cotización.');
                return;
            }
            if (!confirm('¿Está seguro de RECHAZAR esta cotización en nombre del cliente? Esto registrará el motivo indicado.')) return;
            document.getElementById('admin-accion').value = accion;
            document.getElementById('form-admin-decision').submit();
        }
    }
</script>

<!-- MODAL PROGRAMAR OPERATIVO DESDE DETALLE -->
<?php if ($cotizacion['estado'] === 'Aprobada por Cliente' && tienePermiso('operaciones', 'crear_editar')): ?>
<div id="modalProgCotizacion" class="modal-premium">
    <div class="modal-premium-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700;">Programación Operativa</h3>
            <button onclick="cerrarProgramacionCotizacion()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/operaciones/guardar">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_cotizacion" value="<?= $cotizacion['id'] ?>">
            <input type="hidden" name="redireccionar_a" value="<?= htmlspecialchars($_SERVER['REQUEST_URI'], ENT_QUOTES, 'UTF-8') ?>">
            
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha de Entrega</label>
                <input type="date" name="fecha_entrega" value="<?= $cotizacion['fecha_entrega'] ?>" class="form-control">
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha de Seguimiento</label>
                <input type="date" name="fecha_seguimiento" value="<?= $cotizacion['fecha_seguimiento'] ?>" class="form-control">
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Estado Operativo</label>
                <select name="estado_operativo" required class="form-control" style="background-color: white;">
                    <option value="Pendiente" <?= ($cotizacion['estado_operativo'] ?? 'Pendiente') === 'Pendiente' ? 'selected' : '' ?>>Pendiente</option>
                    <option value="En Proceso" <?= ($cotizacion['estado_operativo'] ?? '') === 'En Proceso' ? 'selected' : '' ?>>En Proceso</option>
                    <option value="Entregado" <?= ($cotizacion['estado_operativo'] ?? '') === 'Entregado' ? 'selected' : '' ?>>Entregado</option>
                    <option value="Cancelado" <?= ($cotizacion['estado_operativo'] ?? '') === 'Cancelado' ? 'selected' : '' ?>>Cancelado</option>
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Notas Operativas</label>
                <textarea name="notas_operativas" rows="3" placeholder="Instrucciones para despacho o ruta..." class="form-control"><?= htmlspecialchars($cotizacion['notas_operativas'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 15px;">
                <button type="button" onclick="cerrarProgramacionCotizacion()" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b;">Cancelar</button>
                <button type="submit" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600; padding: 10px 24px;">Guardar</button>
            </div>
        </form>
    </div>
</div>

<script>
    const modalProgCot = document.getElementById('modalProgCotizacion');
    
    function abrirProgramacionCotizacion() {
        modalProgCot.style.display = 'block';
    }
    
    function cerrarProgramacionCotizacion() {
        modalProgCot.style.display = 'none';
    }
    
    window.addEventListener('click', (e) => {
        if (e.target === modalProgCot) {
            cerrarProgramacionCotizacion();
        }
        const modalAdminAprobar = document.getElementById('modalAdminAprobar');
        if (modalAdminAprobar && e.target === modalAdminAprobar) {
            cerrarAdminAprobarModal();
        }
    });
</script>
<?php endif; ?>