<?php
// Operations index view
?>
<style>
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .tabla-cycsa th { background-color: #f8f9fa; color: #6c757d; padding: 12px 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; text-transform: uppercase; font-size: 12px; }
    .tabla-cycsa td { padding: 12px 15px; border-bottom: 1px solid #e9ecef; vertical-align: middle; color: #333; }
    .tabla-cycsa tbody tr:hover { background-color: #f8f9fa; }
    
    .badge-prioridad { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .prioridad-Alta { background-color: #fee2e2; color: #dc2626; }
    .prioridad-Media { background-color: #ffedd5; color: #d97706; }
    .prioridad-Normal { background-color: #f0fdf4; color: #166534; }
    
    .badge-estado { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .estado-Pendiente { background-color: #f1f5f9; color: #475569; }
    .estado-En-Proceso { background-color: #dbeafe; color: #2563eb; }
    .estado-Entregado { background-color: #dcfce7; color: #166534; }
    .estado-Cancelado { background-color: #fee2e2; color: #dc2626; }
    
    .modal-premium { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .modal-premium-content { background-color: #fff; margin: 4% auto; padding: 30px; border: 1px solid #e2e8f0; width: 50%; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: slideDown 0.3s ease; }
    
    @keyframes slideDown {
        from { transform: translateY(-30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .form-group { margin-bottom: 16px; display: flex; flex-direction: column; gap: 6px; }
    .form-control { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; }
    .form-control:focus { border-color: var(--cycsa-azul); }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    
    .btn-accion { border: none; background: none; cursor: pointer; padding: 6px 12px; border-radius: 4px; font-size: 13px; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; }
    .btn-editar { background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
    .btn-editar:hover { background-color: #e2e8f0; }
    .btn-detalle { background-color: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .btn-detalle:hover { background-color: #bae6fd; }
    
    .alert { padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .alert-exito { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .alert-error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    
    /* Detalle table */
    .tabla-detalle-items { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
    .tabla-detalle-items th { background-color: #f8fafc; color: #475569; padding: 10px; font-weight: 600; border-bottom: 2px solid #e2e8f0; text-align: left; }
    .tabla-detalle-items td { padding: 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
</style>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">

    <!-- Alertas -->
    <?php if (!empty($exito)): ?>
        <div class="alert alert-exito">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="header-flex" style="margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;">Operaciones del Sistema</h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 14px;">Seguimiento operativo y programación de entregas de cotizaciones aprobadas.</p>
        </div>
        
        <div class="actions-flex">
            <!-- Buscador -->
            <form method="GET" action="/Cycsa/publico/operaciones" style="display: flex;">
                <input type="text" name="q" placeholder="Buscar por código, proyecto..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" style="padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px 0 0 6px; font-family: 'Inter', sans-serif; width: 250px; outline: none; font-size: 14px;">
                <button type="submit" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-left: none; padding: 10px 18px; border-radius: 0 6px 6px 0; cursor: pointer; color: #475569; font-size: 14px;"><i class="fa-solid fa-magnifying-glass"></i></button>
                <?php if(!empty($busqueda)): ?>
                    <a href="/Cycsa/publico/operaciones" style="margin-left: 10px; color: var(--cycsa-rojo); text-decoration: none; padding-top: 10px; font-size: 14px; font-weight: 500;"><i class="fa-solid fa-xmark"></i> Limpiar</a>
                <?php endif; ?>
            </form>
        </div>
    </div>

    <!-- Pestañas secundarias -->
    <div class="tabs-container" style="display: flex; gap: 10px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 25px;">
        <a href="/Cycsa/publico/operaciones" class="tab-link" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; background-color: var(--cycsa-azul); color: white;"><i class="fa-solid fa-list-check" style="margin-right: 6px;"></i> Lista de Operaciones</a>
        <a href="/Cycsa/publico/operaciones/calendario" class="tab-link" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 14px; background-color: #f1f5f9; color: #475569; transition: background 0.2s;"><i class="fa-solid fa-calendar-days" style="margin-right: 6px;"></i> Calendario de Entregas</a>
    </div>

    <!-- Tabla de Operaciones -->
    <div style="overflow-x: auto;">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th>Cotización</th>
                    <th>Cliente</th>
                    <th>Proyecto</th>
                    <th>Prioridad</th>
                    <th>Día de Entrega</th>
                    <th>Seguimiento</th>
                    <th>Estado Operativo</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($operaciones as $op): 
                    $est = $op['estado_operativo'] ?: 'Pendiente';
                    $estNorm = str_replace(' ', '-', $est);
                ?>
                <tr>
                    <td style="font-family: monospace; font-size: 13.5px; font-weight: 600;"><?= htmlspecialchars($op['cot_codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 500;"><?= htmlspecialchars($op['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($op['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?></div>
                        <div style="font-size: 11.5px; color: #64748b; margin-top: 2px; max-width: 250px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">Dir: <?= htmlspecialchars($op['direccion_proyecto'], ENT_QUOTES, 'UTF-8') ?></div>
                    </td>
                    <td>
                        <span class="badge-prioridad prioridad-<?= $op['prioridad'] ?>"><?= htmlspecialchars($op['prioridad'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td style="font-weight: 600; color: <?= $op['fecha_entrega'] ? '#166534' : '#64748b' ?>;">
                        <i class="fa-solid fa-truck" style="font-size: 12px; margin-right: 4px; opacity: 0.7;"></i>
                        <?= $op['fecha_entrega'] ? htmlspecialchars($op['fecha_entrega'], ENT_QUOTES, 'UTF-8') : '<span style="color:#cbd5e1; font-weight:normal;">Sin definir</span>' ?>
                    </td>
                    <td style="font-weight: 600; color: <?= $op['fecha_seguimiento'] ? '#2563eb' : '#64748b' ?>;">
                        <i class="fa-solid fa-calendar-check" style="font-size: 12px; margin-right: 4px; opacity: 0.7;"></i>
                        <?= $op['fecha_seguimiento'] ? htmlspecialchars($op['fecha_seguimiento'], ENT_QUOTES, 'UTF-8') : '<span style="color:#cbd5e1; font-weight:normal;">Sin definir</span>' ?>
                    </td>
                    <td>
                        <span class="badge-estado estado-<?= $estNorm ?>"><?= htmlspecialchars($est, ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <button class="btn-accion btn-detalle" onclick="verDetalleOperaciones(<?= $op['id_cotizacion'] ?>)" title="Ver Detalles">
                            <i class="fa-solid fa-eye"></i> Detalle
                        </button>
                        <?php if (tienePermiso('operaciones', 'crear_editar')): ?>
                            <button class="btn-accion btn-editar" onclick="abrirProgramacionModal(<?= $op['id_cotizacion'] ?>, '<?= $op['fecha_entrega'] ?>', '<?= $op['fecha_seguimiento'] ?>', '<?= htmlspecialchars($op['estado_operativo'] ?? 'Pendiente', ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(json_encode($op['notas_operativas'] ?? ''), ENT_QUOTES, 'UTF-8') ?>')" style="margin-left: 5px;" title="Programar Fechas">
                                <i class="fa-solid fa-calendar-plus"></i> Programar
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($operaciones)): ?>
                <tr>
                    <td colspan="8" style="text-align: center; padding: 40px; color: #64748b;">No se encontraron operaciones aprobadas o pendientes de seguimiento.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL PROGRAMAR OPERATIVO -->
<div id="modalProgramacion" class="modal-premium">
    <div class="modal-premium-content" style="width: 35%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700;">Programación Operativa</h3>
            <button onclick="cerrarProgramacionModal()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/operaciones/guardar">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_cotizacion" id="prog_id_cotizacion">
            
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha de Entrega</label>
                <input type="date" name="fecha_entrega" id="prog_fecha_entrega" class="form-control">
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha de Seguimiento</label>
                <input type="date" name="fecha_seguimiento" id="prog_fecha_seguimiento" class="form-control">
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Estado Operativo</label>
                <select name="estado_operativo" id="prog_estado_operativo" required class="form-control" style="background-color: white;">
                    <option value="Pendiente">Pendiente</option>
                    <option value="En Proceso">En Proceso</option>
                    <option value="Entregado">Entregado</option>
                    <option value="Cancelado">Cancelado</option>
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Notas Operativas (Instrucciones)</label>
                <textarea name="notas_operativas" id="prog_notas_operativas" rows="4" placeholder="Especificaciones para despacho, rutas o técnicos..." class="form-control"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 15px;">
                <button type="button" onclick="cerrarProgramacionModal()" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b;">Cancelar</button>
                <button type="submit" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600; padding: 10px 24px;">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL VER DETALLES SIN PRECIOS -->
<div id="modalDetalle" class="modal-premium">
    <div class="modal-premium-content" style="width: 48%; max-height: 85vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 19px; font-weight: 700;">Detalles de la Operación</h3>
            <button onclick="cerrarDetalleModal()" class="btn-cerrar">&times;</button>
        </div>
        
        <!-- Banner informativo de exclusión de precios -->
        <div style="background-color: #f0fdfa; border: 1px solid #5eead4; color: #0f766e; padding: 10px 15px; border-radius: 6px; font-size: 13px; font-weight: 500; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-shield-halved"></i> <strong>Vista de Operaciones:</strong> Información comercial y precios omitidos.
        </div>

        <div id="detalle_contenido" style="font-size: 13.5px; line-height: 1.5; color: #334155;">
            <!-- Cargado vía AJAX -->
            <div style="text-align: center; padding: 40px; color: #94a3b8;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px;"></i><br><br>
                Cargando información...
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 25px;">
            <button type="button" onclick="cerrarDetalleModal()" class="form-control" style="cursor: pointer; background: #f1f5f9; border: 1px solid #cbd5e1; font-weight: 600; color: #475569; width: 120px; text-align: center; padding: 8px 0;">Cerrar</button>
        </div>
    </div>
</div>

<script>
    const progModal = document.getElementById('modalProgramacion');
    const detModal = document.getElementById('modalDetalle');

    function abrirProgramacionModal(idCot, fEnt, fSeg, est, notas) {
        document.getElementById('prog_id_cotizacion').value = idCot;
        document.getElementById('prog_fecha_entrega').value = fEnt ? fEnt : '';
        document.getElementById('prog_fecha_seguimiento').value = fSeg ? fSeg : '';
        document.getElementById('prog_estado_operativo').value = est;
        
        // Decodificar notas
        try {
            const notasClean = JSON.parse(notas);
            document.getElementById('prog_notas_operativas').value = notasClean;
        } catch(e) {
            document.getElementById('prog_notas_operativas').value = '';
        }
        
        progModal.style.display = 'block';
    }

    function cerrarProgramacionModal() {
        progModal.style.display = 'none';
    }

    function verDetalleOperaciones(idCot) {
        detModal.style.display = 'block';
        document.getElementById('detalle_contenido').innerHTML = `
            <div style="text-align: center; padding: 40px; color: #94a3b8;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px;"></i><br><br>
                Cargando información...
            </div>
        `;

        fetch('/Cycsa/publico/operaciones/detalle-ajax?id=' + idCot)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    document.getElementById('detalle_contenido').innerHTML = `<div style="color:red; text-align:center; padding:20px;">${data.error}</div>`;
                    return;
                }

                const cot = data.cotizacion;
                const items = data.detalles;

                let html = `
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Código Cotización</div>
                            <strong>${cot.cot_codigo}</strong>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Cliente</div>
                            <strong>${cot.cliente_nombre}</strong>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Proyecto</div>
                            <strong>${cot.nombre_proyecto}</strong>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Atención A</div>
                            <strong>${cot.atencion_a ? cot.atencion_a : '—'}</strong>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Dirección del Proyecto</div>
                        <div style="background-color: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; margin-top: 4px;">
                            ${cot.direccion_proyecto}
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; background: #f8fafc; padding: 15px; border-radius: 8px;">
                        <div>
                            <strong>Condiciones de Pago:</strong><br>
                            <span style="color:#475569;">${cot.condicion_pago}</span>
                        </div>
                        <div>
                            <strong>Tiempo de Entrega:</strong><br>
                            <span style="color:#475569;">${cot.tiempo_entrega ? cot.tiempo_entrega : '—'}</span>
                        </div>
                    </div>

                    <h4 style="margin-top: 25px; margin-bottom: 10px; font-family:'Outfit'; color:#0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 6px;">Materiales y Ensayos a Procesar</h4>
                    <table class="tabla-detalle-items">
                        <thead>
                            <tr>
                                <th style="width:100px;">Código</th>
                                <th>Ensayo / Servicio</th>
                                <th style="width:120px;">Norma / ASTM</th>
                                <th style="width:80px; text-align:right;">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                items.forEach(it => {
                    html += `
                        <tr>
                            <td style="font-family:monospace;">${it.codigo_servicio ? it.codigo_servicio : '—'}</td>
                            <td style="font-weight:500;">${it.descripcion_ensayo}</td>
                            <td style="color:#64748b;">${it.norma_astm ? it.norma_astm : 'N/A'}</td>
                            <td style="text-align:right; font-weight:600;">${Number(it.cantidad).toFixed(0)}</td>
                        </tr>
                    `;
                });

                html += `
                        </tbody>
                    </table>

                    ${cot.notas_operativas ? `
                    <div style="margin-top: 25px; background: #fffbeb; border: 1px solid #fef3c7; padding: 15px; border-radius: 8px; color: #b45309;">
                        <strong><i class="fa-solid fa-triangle-exclamation"></i> Notas e Instrucciones de Operación:</strong>
                        <p style="margin-top: 6px; font-size: 13px;">${cot.notas_operativas}</p>
                    </div>
                    ` : ''}
                `;

                document.getElementById('detalle_contenido').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('detalle_contenido').innerHTML = `<div style="color:red; text-align:center; padding:20px;">Error al obtener detalles: ${err}</div>`;
            });
    }

    function cerrarDetalleModal() {
        detModal.style.display = 'none';
    }

    window.addEventListener('click', (e) => {
        if (e.target === progModal) cerrarProgramacionModal();
        if (e.target === detModal) cerrarDetalleModal();
    });
</script>
