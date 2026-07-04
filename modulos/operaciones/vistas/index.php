<?php
// Operations index view - LIMS Dashboard
?>
<style>
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .tabla-cycsa th { background-color: #f8fafc; color: #475569; padding: 12px 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
    .tabla-cycsa td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
    .tabla-cycsa tbody tr:hover { background-color: #f8fafc; }
    
    .badge-prioridad { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .prioridad-Alta { background-color: #fee2e2; color: #dc2626; }
    .prioridad-Media { background-color: #ffedd5; color: #d97706; }
    .prioridad-Normal { background-color: #f0fdf4; color: #166534; }
    
    .badge-estado { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .estado-Abierta { background-color: #eff6ff; color: #1e40af; }
    .estado-En-Proceso { background-color: #fef3c7; color: #d97706; }
    .estado-Completada { background-color: #dcfce7; color: #15803d; }
    .estado-Facturada { background-color: #f1f5f9; color: #475569; }

    .estado-Registrado { background-color: #e0f2fe; color: #0369a1; }
    .estado-En-Laboratorio { background-color: #fef3c7; color: #d97706; }
    .estado-Finalizado { background-color: #dcfce7; color: #15803d; }
    
    .modal-premium { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .modal-premium-content { background-color: #fff; margin: 4% auto; padding: 30px; border: 1px solid #e2e8f0; width: 50%; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: slideDown 0.3s ease; }
    
    @keyframes slideDown {
        from { transform: translateY(-30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .form-group { margin-bottom: 16px; display: flex; flex-direction: column; gap: 6px; }
    .form-control { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; }
    .form-control:focus { border-color: var(--cycsa-azul); }
    
    .btn-accion { border: none; background: none; cursor: pointer; padding: 8px 14px; border-radius: 6px; font-size: 13px; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; }
    .btn-os { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .btn-os:hover { background-color: #dbeafe; }
    .btn-recepcion { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .btn-recepcion:hover { background-color: #dcfce7; }
    .btn-detalle { background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
    .btn-detalle:hover { background-color: #e2e8f0; }
    
    .alert { padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .alert-exito { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .alert-error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    
    .tab-btn { padding: 8px 16px; border-radius: 6px; border: none; font-weight: 500; font-size: 14px; cursor: pointer; transition: all 0.2s; }
    .tab-btn-active { background-color: var(--cycsa-azul); color: white; }
    .tab-btn-inactive { background-color: #f1f5f9; color: #475569; }
    .tab-btn-inactive:hover { background-color: #e2e8f0; }

    .tab-content { display: none; }
    .tab-content-active { display: block; }
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

    <div class="header-flex" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;">Panel de Operaciones LIMS</h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 14px;">Gestión de Órdenes de Servicio, ingreso de muestras correlativas y cargue técnico de rupturas.</p>
        </div>
        
        <div class="actions-flex" style="display: flex; gap: 10px;">
            <form method="GET" action="/Cycsa/publico/operaciones" style="display: flex;">
                <input type="text" name="q" placeholder="Buscar..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" style="padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px 0 0 6px; font-family: 'Inter', sans-serif; width: 250px; outline: none; font-size: 14px;">
                <button type="submit" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-left: none; padding: 10px 18px; border-radius: 0 6px 6px 0; cursor: pointer; color: #475569; font-size: 14px;"><i class="fa-solid fa-magnifying-glass"></i></button>
            </form>
        </div>
    </div>

    <!-- Pestañas LIMS -->
    <div style="display: flex; justify-content: space-between; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 25px; align-items: center;">
        <div class="tabs-container" style="display: flex; gap: 10px;">
            <button class="tab-btn tab-btn-active" onclick="switchTab('tab-os', this)"><i class="fa-solid fa-receipt" style="margin-right: 6px;"></i> Órdenes de Servicio</button>
            <button class="tab-btn tab-btn-inactive" onclick="switchTab('tab-muestras', this)"><i class="fa-solid fa-vials" style="margin-right: 6px;"></i> Muestras en Lab</button>
            <button class="tab-btn tab-btn-inactive" onclick="switchTab('tab-cots', this)"><i class="fa-solid fa-file-invoice" style="margin-right: 6px;"></i> Cotizaciones por Iniciar</button>
        </div>
        <a href="/Cycsa/publico/operaciones/calendario" class="tab-btn tab-btn-inactive" style="text-decoration: none; display: inline-flex; align-items: center;"><i class="fa-solid fa-calendar-days" style="margin-right: 6px;"></i> Calendario de Rupturas</a>
    </div>

    <!-- CONTENIDO PESTAÑA: ÓRDENES DE SERVICIO -->
    <div id="tab-os" class="tab-content tab-content-active">
        <h3 style="font-family:'Outfit'; color:#0f172a; margin-bottom:15px; font-size:16px;">Órdenes de Servicio Activas</h3>
        <div style="overflow-x: auto;">
            <table class="tabla-cycsa">
                <thead>
                    <tr>
                        <th>Código O/S</th>
                        <th>Cliente</th>
                        <th>Proyecto</th>
                        <th>Tipo Contrato</th>
                        <th>Fecha de Emisión</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordenes as $o): ?>
                    <tr>
                        <td style="font-family: monospace; font-size: 13.5px; font-weight: 700; color: var(--cycsa-azul);"><?= htmlspecialchars($o['codigo_os'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($o['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($o['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div style="font-size: 11.5px; color: #64748b; margin-top: 2px;">Coty: <?= htmlspecialchars($o['cot_codigo'], ENT_QUOTES, 'UTF-8') ?></div>
                            
                            <!-- Listado de Ensayos / Productos y sus estados de recepción -->
                            <div style="margin-top: 8px; display: flex; flex-direction: column; gap: 4px; border-top: 1px dashed #e2e8f0; padding-top: 6px;">
                                <?php foreach ($o['items'] as $item): ?>
                                    <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; gap: 20px;">
                                        <span style="color: #475569; font-weight: 500; text-overflow: ellipsis; overflow: hidden; white-space: nowrap; max-width: 250px;" title="<?= htmlspecialchars($item['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?>">
                                            <i class="fa-solid fa-flask" style="font-size: 10px; color: #94a3b8; margin-right: 4px;"></i>
                                            <?= htmlspecialchars($item['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?>
                                        </span>
                                        <?php if (!empty($item['codigo_muestra'])): ?>
                                            <a href="/Cycsa/publico/operaciones/detalle-lote?id_lote=<?= $item['id_lote'] ?>" style="background: #e0f2fe; color: #0369a1; padding: 2px 6px; border-radius: 4px; font-weight: 700; font-family: monospace; text-decoration: none; font-size: 10.5px; display: inline-flex; align-items: center; gap: 4px;" title="Ver lote en Laboratorio">
                                                <?= htmlspecialchars($item['codigo_muestra'], ENT_QUOTES, 'UTF-8') ?> <i class="fa-solid fa-chevron-right" style="font-size: 8px;"></i>
                                            </a>
                                        <?php else: ?>
                                            <a href="/Cycsa/publico/operaciones/recepcion?id_os=<?= $o['id'] ?>&id_detalle=<?= $item['id_detalle'] ?>" style="background: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-weight: 600; text-decoration: none; font-size: 10.5px; display: inline-flex; align-items: center; gap: 4px;" title="Recibir muestra para este ensayo">
                                                <i class="fa-solid fa-plus" style="font-size: 8px;"></i> Recibir
                                            </a>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                        <td>
                            <span style="font-weight:600; font-size:12px; color: <?= $o['tipo_contrato'] == 'Mensual' ? '#7c3aed' : '#475569' ?>;">
                                <?= htmlspecialchars($o['tipo_contrato'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td><?= htmlspecialchars($o['fecha_emision'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge-estado estado-<?= str_replace(' ', '-', $o['estado']) ?>"><?= htmlspecialchars($o['estado'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="/Cycsa/publico/operaciones/recepcion?id_os=<?= $o['id'] ?>" class="btn-accion btn-recepcion" title="Registrar Recepción de Muestra">
                                <i class="fa-solid fa-plus-circle"></i> Recibir General
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ordenes)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">No hay órdenes de servicio activas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- CONTENIDO PESTAÑA: MUESTRAS EN LABORATORIO -->
    <div id="tab-muestras" class="tab-content">
        <h3 style="font-family:'Outfit'; color:#0f172a; margin-bottom:15px; font-size:16px;">Muestras Ingresadas al Laboratorio</h3>
        <div style="overflow-x: auto;">
            <table class="tabla-cycsa">
                <thead>
                    <tr>
                        <th>Código Lab</th>
                        <th>Código Campo</th>
                        <th>Lote / Elemento</th>
                        <th>Fecha de Moldeo</th>
                        <th>Fecha de Recepción</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recepciones as $r): ?>
                    <tr>
                        <td style="font-family: monospace; font-size: 13.5px; font-weight: 700; color: #0369a1;"><?= htmlspecialchars($r['codigo_muestra'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-family: monospace;"><?= htmlspecialchars($r['codigo_campo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-weight: 600;"><?= htmlspecialchars($r['nombre_lote'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($r['fecha_moldeo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($r['fecha_recepcion'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="badge-estado estado-<?= str_replace(' ', '-', $r['estado']) ?>"><?= htmlspecialchars($r['estado'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="/Cycsa/publico/operaciones/detalle-lote?id_lote=<?= $r['id'] ?>" class="btn-accion btn-detalle" title="Ver lote y cargar resultados">
                                <i class="fa-solid fa-flask"></i> Ver Lote & Rupturas
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recepciones)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">No hay muestras registradas en el laboratorio.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- CONTENIDO PESTAÑA: COTIZACIONES PENDIENTES -->
    <div id="tab-cots" class="tab-content">
        <h3 style="font-family:'Outfit'; color:#0f172a; margin-bottom:15px; font-size:16px;">Cotizaciones Aprobadas Pendientes de O/S</h3>
        <div style="overflow-x: auto;">
            <table class="tabla-cycsa">
                <thead>
                    <tr>
                        <th>Código Coty</th>
                        <th>Cliente</th>
                        <th>Proyecto</th>
                        <th>Fecha de Aprobación</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cotizaciones as $c): ?>
                    <tr>
                        <td style="font-family: monospace; font-size: 13.5px; font-weight: 600;"><?= htmlspecialchars($c['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="font-weight: 500;"><?= htmlspecialchars($c['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <div style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($c['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td><?= htmlspecialchars($c['fecha_creacion'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="text-align: right; white-space: nowrap;">
                            <button class="btn-accion btn-os" onclick="abrirCrearOSModal(<?= $c['id'] ?>, '<?= $c['codigo'] ?>')">
                                <i class="fa-solid fa-folder-plus"></i> Generar O/S
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($cotizaciones)): ?>
                    <tr>
                        <td colspan="5" style="text-align: center; padding: 40px; color: #64748b;">No hay cotizaciones pendientes de emitir orden de servicio.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- MODAL CREAR ORDEN DE SERVICIO -->
<div id="modalCrearOS" class="modal-premium">
    <div class="modal-premium-content" style="width: 45%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700;">Generar Orden de Servicio</h3>
            <button onclick="cerrarCrearOSModal()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/operaciones/crear-os">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_cotizacion" id="os_id_cotizacion">
            
            <div class="grid-2">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Cotización Seleccionada</label>
                    <input type="text" id="os_codigo_coty" readonly class="form-control" style="background-color: #f1f5f9; font-weight: 700; font-family: monospace;">
                </div>

                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Modalidad / Tipo de Contrato</label>
                    <select name="tipo_contrato" required class="form-control" style="background-color: white;">
                        <option value="Puntual">Trabajo Puntual (Facturación normal)</option>
                        <option value="Mensual">Contrato Mensual Abierto (Consolidado fin de mes)</option>
                    </select>
                </div>
            </div>

            <h4 style="font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); margin-top:20px; margin-bottom:12px; border-bottom:1px solid #f1f5f9; padding-bottom:6px;"><i class="fa-solid fa-truck"></i> Programación de Muestreo (Opcional)</h4>
            
            <div class="grid-2">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha Programada de Muestreo</label>
                    <input type="date" name="fecha_muestreo" class="form-control">
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Hora Programada de Muestreo</label>
                    <input type="time" name="hora_muestreo" class="form-control">
                </div>
            </div>

            <div class="grid-2" style="margin-top: 10px;">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Técnico de Campo Asignado</label>
                    <input type="text" name="tecnico_muestreo" placeholder="Nombre del técnico" class="form-control">
                </div>
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Vehículo de Campo Asignado</label>
                    <input type="text" name="vehiculo_muestreo" placeholder="Placa o modelo" class="form-control">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 25px;">
                <button type="button" onclick="cerrarCrearOSModal()" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b;">Cancelar</button>
                <button type="submit" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600; padding: 10px 24px;">Generar O/S</button>
            </div>
        </form>
    </div>
</div>

<script>
    const osModal = document.getElementById('modalCrearOS');

    function switchTab(tabId, btn) {
        // Ocultar todos los contenidos de pestañas
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('tab-content-active'));
        // Quitar estado activo de todos los botones
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('tab-btn-active');
            b.classList.add('tab-btn-inactive');
        });

        // Mostrar pestaña seleccionada
        document.getElementById(tabId).classList.add('tab-content-active');
        // Activar botón seleccionado
        btn.classList.add('tab-btn-active');
        btn.classList.remove('tab-btn-inactive');
    }

    function abrirCrearOSModal(idCoty, codigoCoty) {
        document.getElementById('os_id_cotizacion').value = idCoty;
        document.getElementById('os_codigo_coty').value = codigoCoty;
        osModal.style.display = 'block';
    }

    function cerrarCrearOSModal() {
        osModal.style.display = 'none';
    }

    window.addEventListener('click', (e) => {
        if (e.target === osModal) cerrarCrearOSModal();
    });
</script>
