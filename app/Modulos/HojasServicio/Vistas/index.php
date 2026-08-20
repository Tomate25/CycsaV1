<?php
// Vista Premium del Módulo de Hojas de Servicio (CYCSA-RT-FM-13)
?>

<!-- Estilos Específicos Premium para Hojas de Servicio -->
<style>
    .hs-card {
        background: white;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0, 0, 0, 0.03);
        padding: 24px;
        margin-bottom: 25px;
    }

    .badge-hs {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 5px 10px;
        border-radius: 9999px;
        font-size: 11px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
    }
    
    .badge-nuevo { background: #eff6ff; color: #1e90ff; border: 1px solid #bfdbfe; }
    .badge-borrador { background: #f8fafc; color: #64748b; border: 1px solid #e2e8f0; }
    .badge-revision { background: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
    .badge-observada { background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-aprobado { background: #ecfdf5; color: #10b981; border: 1px solid #a7f3d0; }

    /* Estilos de pestañas (Tabs) */
    .hs-tabs {
        display: flex;
        gap: 10px;
        border-bottom: 2px solid #e2e8f0;
        padding-bottom: 1px;
        margin-bottom: 20px;
    }

    .hs-tab-btn {
        background: none;
        border: none;
        padding: 12px 20px;
        font-size: 14.5px;
        font-weight: 600;
        color: #64748b;
        cursor: pointer;
        border-bottom: 3px solid transparent;
        transition: all 0.2s;
        display: flex;
        align-items: center;
        gap: 8px;
    }

    .hs-tab-btn:hover {
        color: var(--cycsa-azul);
    }

    .hs-tab-btn.active {
        color: var(--cycsa-azul);
        border-bottom-color: var(--cycsa-azul);
    }

    .hs-tab-pane {
        display: none;
    }

    .hs-tab-pane.active {
        display: block;
    }

    /* Tabla Estilizada */
    .hs-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .hs-table th {
        background: #f8fafc;
        padding: 14px 16px;
        font-size: 11.5px;
        font-weight: 700;
        text-transform: uppercase;
        color: #475569;
        border-bottom: 1.5px solid #e2e8f0;
        letter-spacing: 0.5px;
    }

    .hs-table td {
        padding: 16px;
        font-size: 13.5px;
        color: #1e293b;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }

    .hs-table tr:hover td {
        background: #f8fafc;
    }

    /* Botonera */
    .btn-accion-hs {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 7px 12px;
        font-size: 12.5px;
        font-weight: 600;
        border-radius: 6px;
        border: 1px solid transparent;
        cursor: pointer;
        transition: all 0.2s;
        text-decoration: none;
    }

    .btn-registrar { background: var(--cycsa-azul); color: white; }
    .btn-registrar:hover { background: var(--cycsa-azul-hover); }
    
    .btn-editar { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .btn-editar:hover { background: #e2e8f0; }

    .btn-enviar { background: #3b82f6; color: white; }
    .btn-enviar:hover { background: #2563eb; }

    .btn-aprobar { background: #10b981; color: white; }
    .btn-aprobar:hover { background: #059669; }

    .btn-pdf { background: #ef4444; color: white; }
    .btn-pdf:hover { background: #dc2626; }

    /* Estilos del Formulario Modal */
    .form-group {
        margin-bottom: 15px;
    }
    .form-group label {
        display: block;
        font-weight: 600;
        font-size: 12.5px;
        color: #475569;
        margin-bottom: 6px;
    }
    .form-control {
        width: 100%;
        padding: 9px 12px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        font-size: 13.5px;
        outline: none;
        color: #1e293b;
        transition: border-color 0.2s;
    }
    .form-control:focus {
        border-color: var(--cycsa-azul);
        box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.1);
    }
    .btn-cerrar {
        background: none;
        border: none;
        font-size: 24px;
        cursor: pointer;
        color: #64748b;
        transition: color 0.2s;
    }
    .btn-cerrar:hover {
        color: #ef4444;
    }
</style>

<!-- Cabecera del Módulo -->
<div class="header-flex" style="margin-bottom: 25px;">
    <div>
        <h2 style="font-family:'Outfit'; font-size:24px; font-weight:800; color:var(--cycsa-azul); margin:0;">
            <i class="fa-solid fa-file-signature"></i> Control de Hojas de Servicio (CYCSA-RT-FM-13)
        </h2>
        <p style="color:#64748b; margin-top:5px; font-size:14px;">
            Gestione el registro, revisión de datos comerciales/muestras y aprobación por parte de la supervisión antes del ingreso a laboratorio.
        </p>
    </div>
    
    <!-- Barra de Búsqueda -->
    <div class="filtro-barra">
        <form method="GET" action="/Cycsa/publico/hojas-servicio" style="display:flex; gap:10px;">
            <div class="search-input-wrapper" style="position:relative; width: 300px;">
                <i class="fa-solid fa-magnifying-glass" style="position:absolute; left:12px; top:12px; color:#94a3b8;"></i>
                <input type="text" name="buscar" value="<?= htmlspecialchars($busqueda) ?>" placeholder="Buscar por O/S, proyecto o cliente..." class="form-control" style="padding-left:35px; height: 40px;">
            </div>
            <button type="submit" class="btn-accion-hs btn-registrar" style="height: 40px; padding: 0 18px;"><i class="fa-solid fa-search"></i> Buscar</button>
            <?php if (!empty($busqueda)): ?>
                <a href="/Cycsa/publico/hojas-servicio" class="btn-accion-hs btn-editar" style="height: 40px; display:inline-flex; align-items:center;"><i class="fa-solid fa-circle-xmark"></i> Limpiar</a>
            <?php endif; ?>
        </form>
    </div>
</div>

<!-- Alertas de Sesión -->
<?php if (isset($_SESSION['exito'])): ?>
    <div style="background:#f0fdf4; border:1px solid #bbf7d0; color:#166534; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-size:14px; font-weight:500;">
        <i class="fa-solid fa-circle-check" style="margin-right:8px;"></i> <?= $_SESSION['exito'] ?>
    </div>
    <?php unset($_SESSION['exito']); ?>
<?php endif; ?>
<?php if (isset($_SESSION['error'])): ?>
    <div style="background:#fef2f2; border:1px solid #fecaca; color:#991b1b; padding:12px 18px; border-radius:8px; margin-bottom:20px; font-size:14px; font-weight:500;">
        <i class="fa-solid fa-circle-xmark" style="margin-right:8px;"></i> <?= $_SESSION['error'] ?>
    </div>
    <?php unset($_SESSION['error']); ?>
<?php endif; ?>

<!-- Tabs de Navegación -->
<div class="hs-tabs">
    <button class="hs-tab-btn active" onclick="switchTab('tab-nuevas', this)">
        <i class="fa-solid fa-file-circle-plus"></i> Nuevas O/S <span style="background:#3b82f6; color:white; font-size:10px; padding:2px 6px; border-radius:10px; margin-left:4px;"><?= count($nuevas) ?></span>
    </button>
    <button class="hs-tab-btn" onclick="switchTab('tab-proceso', this)">
        <i class="fa-solid fa-hourglass-half"></i> En Proceso / Revisión <span style="background:#d97706; color:white; font-size:10px; padding:2px 6px; border-radius:10px; margin-left:4px;"><?= count($proceso) ?></span>
    </button>
    <button class="hs-tab-btn" onclick="switchTab('tab-aprobadas', this)">
        <i class="fa-solid fa-circle-check"></i> Hojas Aprobadas <span style="background:#10b981; color:white; font-size:10px; padding:2px 6px; border-radius:10px; margin-left:4px;"><?= count($aprobadas) ?></span>
    </button>
</div>

<!-- CONTENEDORES DE CONTENIDO (PANELES) -->

<!-- 1. TAB: NUEVAS (PENDIENTES) -->
<div id="tab-nuevas" class="hs-tab-pane active hs-card">
    <?php if (empty($nuevas)): ?>
        <div style="text-align:center; padding:40px 0; color:#64748b;">
            <i class="fa-solid fa-circle-info" style="font-size:32px; color:#94a3b8; margin-bottom:12px;"></i>
            <p style="font-size:14px; font-weight:500;">No hay nuevas Órdenes de Servicio pendientes de registrar Hoja de Solicitud.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="hs-table">
                <thead>
                    <tr>
                        <th>Código O/S</th>
                        <th>Cotización</th>
                        <th>Cliente / Empresa</th>
                        <th>Proyecto</th>
                        <th>Fecha Creación</th>
                        <th>Estado O/S</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($nuevas as $n): ?>
                        <?php
                        // Determinar estado de muestreo para Casos A, B y C
                        $estadoMuestreo = 'sin_decidir'; // Caso A: Nueva orden (Preguntar si requiere muestreo o ingreso directo)
                        
                        if (!empty($n['id_pm'])) {
                            if (in_array($n['estado_muestreo'], ['Programado', 'En Proceso', 'En Campo'])) {
                                $estadoMuestreo = 'en_proceso'; // Caso B: Técnico asignado en campo
                            } else {
                                $estadoMuestreo = 'finalizado'; // Caso C: Muestreo completado
                            }
                        } elseif (!empty($n['id_hoja'])) {
                            $estadoMuestreo = 'finalizado'; // Caso C: Ya cuenta con hoja registrada
                        }
                        ?>
                        <tr>
                            <td><strong style="color:var(--cycsa-azul); font-family:monospace;"><?= htmlspecialchars($n['codigo_os']) ?></strong></td>
                            <td><span style="font-family:monospace;"><?= htmlspecialchars($n['cot_codigo']) ?></span></td>
                            <td><strong><?= htmlspecialchars($n['cliente_nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($n['nombre_proyecto']) ?></td>
                            <td><?= date('d/m/Y', strtotime($n['fecha_emision'])) ?></td>
                            <td>
                                <?php if (empty($n['id_hoja'])): ?>
                                    <?php if ($estadoMuestreo === 'en_proceso'): ?>
                                        <span class="badge-hs" style="background:#fef3c7; color:#92400e; border:1px solid #fde68a;">
                                            <i class="fa-solid fa-truck-pickup"></i> Muestreo en Campo
                                        </span>
                                        <?php if (!empty($n['tecnico_muestreo_nombre'])): ?>
                                            <div style="font-size:11px; color:#64748b; margin-top:2px;">Téc: <?= htmlspecialchars($n['tecnico_muestreo_nombre']) ?></div>
                                        <?php endif; ?>
                                    <?php elseif ($estadoMuestreo === 'finalizado'): ?>
                                        <span class="badge-hs" style="background:#dcfce7; color:#15803d; border:1px solid #86efac;">
                                            <i class="fa-solid fa-check-circle"></i> Muestreo Finalizado
                                        </span>
                                    <?php else: ?>
                                        <span class="badge-hs badge-nuevo"><i class="fa-solid fa-plus-circle"></i> Nuevo</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge-hs badge-borrador"><i class="fa-solid fa-pen-to-square"></i> Borrador</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right; display:flex; justify-content:flex-end; gap:8px;">
                                <?php if (tienePermiso('operaciones', 'crear_editar')): ?>
                                    <?php if (empty($n['id_hoja'])): ?>
                                        <button type="button" class="btn-accion-hs btn-registrar"
                                                data-id-os="<?= $n['id'] ?>"
                                                data-codigo-os="<?= htmlspecialchars($n['codigo_os']) ?>"
                                                data-estado-muestreo="<?= $estadoMuestreo ?>"
                                                data-tecnico="<?= htmlspecialchars($n['tecnico_muestreo_nombre'] ?? '') ?>"
                                                data-fecha-ida="<?= !empty($n['fecha_ida']) ? date('d/m/Y H:i', strtotime($n['fecha_ida'])) : '' ?>"
                                                data-fecha-llegada="<?= !empty($n['fecha_llegada']) ? date('d/m/Y H:i', strtotime($n['fecha_llegada'])) : '' ?>"
                                                onclick="iniciarRegistroHojaRTFM13(this)">
                                            <i class="fa-solid fa-file-circle-plus"></i> Registrar Hoja RT-FM-13
                                        </button>
                                    <?php else: ?>
                                        <a href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= $n['id'] ?>" target="_blank" class="btn-accion-hs btn-pdf" title="Ver PDF preliminar de la Hoja de Servicio">
                                            <i class="fa-solid fa-file-pdf"></i> Ver PDF
                                        </a>
                                        <button type="button" class="btn-accion-hs btn-editar" onclick="abrirModalHojaSolicitud(<?= $n['id'] ?>, '<?= $n['codigo_os'] ?>')">
                                            <i class="fa-solid fa-edit"></i> Editar
                                        </button>
                                        <form method="POST" action="/Cycsa/publico/hojas-servicio/enviar-revision" style="margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="id_os" value="<?= $n['id'] ?>">
                                            <button type="submit" class="btn-accion-hs btn-enviar">
                                                <i class="fa-solid fa-paper-plane"></i> Enviar a Revisión
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- 2. TAB: EN PROCESO / REVISIÓN -->
<div id="tab-proceso" class="hs-tab-pane hs-card">
    <?php if (empty($proceso)): ?>
        <div style="text-align:center; padding:40px 0; color:#64748b;">
            <i class="fa-solid fa-circle-info" style="font-size:32px; color:#94a3b8; margin-bottom:12px;"></i>
            <p style="font-size:14px; font-weight:500;">No hay Hojas de Servicio en proceso de edición o revisión.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="hs-table">
                <thead>
                    <tr>
                        <th>Código O/S</th>
                        <th>Código Hoja</th>
                        <th>Cliente / Empresa</th>
                        <th>Proyecto</th>
                        <th>Fase Actual / Estado</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($proceso as $p): ?>
                        <tr>
                            <td><strong style="color:var(--cycsa-azul); font-family:monospace;"><?= htmlspecialchars($p['codigo_os']) ?></strong></td>
                            <td><span style="font-family:monospace; font-weight:600; color:#475569;"><?= htmlspecialchars($p['codigo_documento']) ?></span></td>
                            <td><strong><?= htmlspecialchars($p['cliente_nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($p['nombre_proyecto']) ?></td>
                            <td>
                                <?php if ($p['estado'] === 'Estado 1: Recepcion'): ?>
                                    <span class="badge-hs badge-borrador"><i class="fa-solid fa-pen-to-square"></i> Borrador</span>
                                <?php elseif ($p['estado'] === 'Estado 2: Revision'): ?>
                                    <span class="badge-hs badge-revision"><i class="fa-solid fa-hourglass-half"></i> En Revisión Supervisor</span>
                                <?php elseif ($p['estado'] === 'Estado 2: Observada'): ?>
                                    <span class="badge-hs badge-observada"><i class="fa-solid fa-triangle-exclamation"></i> Observada</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align:right; display:flex; justify-content:flex-end; gap:8px;">
                                <!-- PDF Oficial Previo -->
                                <a href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= $p['id'] ?>" target="_blank" class="btn-accion-hs btn-pdf" title="Ver PDF preliminar de la Hoja de Servicio">
                                    <i class="fa-solid fa-file-pdf"></i> Ver PDF
                                </a>

                                <?php if ($p['estado'] === 'Estado 1: Recepcion' || $p['estado'] === 'Estado 2: Observada'): ?>
                                    <!-- Editar Borrador u Observada -->
                                    <?php if (tienePermiso('operaciones', 'crear_editar')): ?>
                                        <button type="button" class="btn-accion-hs btn-editar" onclick="abrirModalHojaSolicitud(<?= $p['id'] ?>, '<?= $p['codigo_os'] ?>')">
                                            <i class="fa-solid fa-edit"></i> Editar
                                        </button>
                                        
                                        <!-- Enviar a Revisión de Supervisor -->
                                        <form method="POST" action="/Cycsa/publico/hojas-servicio/enviar-revision" style="margin:0;">
                                            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                            <input type="hidden" name="id_os" value="<?= $p['id'] ?>">
                                            <button type="submit" class="btn-accion-hs btn-enviar">
                                                <i class="fa-solid fa-paper-plane"></i> Enviar a Revisión
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                <?php elseif ($p['estado'] === 'Estado 2: Revision'): ?>
                                    <!-- Decisión Supervisor (Solo Rol 1 o 3) -->
                                    <?php if (in_array($_SESSION['usuario_rol'] ?? 0, [1, 3])): ?>
                                        <button type="button" class="btn-accion-hs btn-aprobar" onclick="abrirModalRevision(<?= $p['id'] ?>, '<?= $p['codigo_os'] ?>')">
                                            <i class="fa-solid fa-check"></i> Decisión Supervisor
                                        </button>
                                    <?php else: ?>
                                        <span style="font-size:12.5px; color:#64748b; font-style:italic;"><i class="fa-solid fa-lock"></i> Bloqueado en revisión</span>
                                    <?php endif; ?>
                                <?php endif; ?>
                            </tr>
                            <?php if ($p['estado'] === 'Estado 2: Observada' && !empty($p['motivo_observacion'])): ?>
                                <!-- Fila Expandida de Observación -->
                                <tr>
                                    <td colspan="6" style="background:#fff5f5; border-left:4px solid #ef4444; padding: 10px 16px; font-size:12.5px; color:#b91c1c;">
                                        <strong><i class="fa-solid fa-comment-dots"></i> Observaciones del Supervisor:</strong> <?= htmlspecialchars($p['motivo_observacion']) ?>
                                    </td>
                                </tr>
                            <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- 3. TAB: HOJAS APROBADAS (FLUJO EN CURSO / FINALIZADO) -->
<div id="tab-aprobadas" class="hs-tab-pane hs-card">
    <?php if (empty($aprobadas)): ?>
        <div style="text-align:center; padding:40px 0; color:#64748b;">
            <i class="fa-solid fa-circle-info" style="font-size:32px; color:#94a3b8; margin-bottom:12px;"></i>
            <p style="font-size:14px; font-weight:500;">No hay Hojas de Servicio aprobadas todavía.</p>
        </div>
    <?php else: ?>
        <div style="overflow-x:auto;">
            <table class="hs-table">
                <thead>
                    <tr>
                        <th>Código O/S</th>
                        <th>Código Hoja</th>
                        <th>Cliente / Empresa</th>
                        <th>Proyecto</th>
                        <th>Estado Operativo O/S</th>
                        <th style="text-align:right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($aprobadas as $a): ?>
                        <tr>
                            <td><strong style="color:var(--cycsa-azul); font-family:monospace;"><?= htmlspecialchars($a['codigo_os']) ?></strong></td>
                            <td><span style="font-family:monospace; font-weight:600; color:#475569;"><?= htmlspecialchars($a['codigo_documento']) ?></span></td>
                            <td><strong><?= htmlspecialchars($a['cliente_nombre']) ?></strong></td>
                            <td><?= htmlspecialchars($a['nombre_proyecto']) ?></td>
                            <td>
                                <span class="badge-hs badge-aprobado"><i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($a['estado']) ?></span>
                            </td>
                            <td style="text-align:right;">
                                <a href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= $a['id'] ?>" target="_blank" class="btn-accion-hs btn-pdf">
                                    <i class="fa-solid fa-file-pdf"></i> PDF Oficial
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    <?php endif; ?>
</div>

<!-- ==========================================
     MODALES DENTRO DEL MÓDULO DE HOJAS
     ========================================== -->

<!-- MODAL REGISTRAR/EDITAR HOJA DE SOLICITUD DE SERVICIO (CYCSA-RT-FM-13) CON REFERENCIA O/S -->
<div id="modalHojaSolicitud" class="modal-premium" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5); backdrop-filter:blur(4px);">
    <div class="modal-premium-content" style="width: 95%; max-width: 1350px; max-height: 90vh; overflow-y: auto; padding: 25px 30px; background: white; margin: 2% auto; border-radius: 12px; box-shadow: 0 10px 30px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 2px solid #e2e8f0; padding-bottom: 12px;">
            <div>
                <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700; display:flex; align-items:center; gap:10px;">
                    <i class="fa-solid fa-file-signature" style="color:var(--cycsa-azul);"></i> Hoja de Solicitud de Servicio (Ingreso CYCSA-RT-FM-13)
                </h3>
                <p style="margin: 3px 0 0 0; font-size: 13px; color: #64748b;">
                    Orden de Servicio Vinculada: <strong id="hs_codigo_os_label" style="color:var(--cycsa-azul); font-family:monospace; font-size:14px;"></strong>
                </p>
            </div>
            <button onclick="cerrarModalHojaSolicitud()" class="btn-cerrar" style="font-size:26px; border:none; background:none; cursor:pointer; color:#64748b;">&times;</button>
        </div>
        
        <div id="loading-hoja-solicitud" style="text-align:center; padding: 50px; display:none;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size:36px; color:var(--cycsa-azul);"></i>
            <p style="color:#64748b; margin-top:12px; font-size:14px; font-weight:600;">Cargando datos de la O/S y plantilla RT-FM-13...</p>
        </div>

        <div id="wrapper-split-rt-fm-13" style="display:none; grid-template-columns: 360px 1fr; gap: 25px; align-items: flex-start;">
            
            <!-- PANEL LATERAL IZQUIERDO: REFERENCIA VISUAL DE LA ORDEN DE SERVICIO (SOLO LECTURA) -->
            <div style="background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 10px; padding: 18px; position: sticky; top: 0; max-height: 75vh; overflow-y: auto; box-shadow: 0 2px 8px rgba(0,0,0,0.03);">
                <div style="display:flex; align-items:center; justify-content:space-between; border-bottom: 1.5px solid #cbd5e1; padding-bottom: 8px; margin-bottom: 12px;">
                    <span style="font-family:'Outfit'; font-weight:700; color:var(--cycsa-azul); font-size:13px; text-transform:uppercase; display:flex; align-items:center; gap:6px;">
                        <i class="fa-solid fa-file-contract"></i> Referencia O/S (CYCSA-RG-FM-39)
                    </span>
                    <span id="ref_os_badge_estado" style="font-size:10px; font-weight:700; background:#e0f2fe; color:#0369a1; padding:2px 8px; border-radius:10px; text-transform:uppercase;">Solo Lectura</span>
                </div>

                <div style="font-size: 12.5px; color: #334155; display: flex; flex-direction: column; gap: 8px;">
                    <div><strong style="color:#0f172a;">Cliente:</strong> <span id="ref_os_cliente">--</span></div>
                    <div><strong style="color:#0f172a;">RUC / Cédula:</strong> <span id="ref_os_rfc" style="font-family:monospace;">--</span></div>
                    <div><strong style="color:#0f172a;">Atención a:</strong> <span id="ref_os_atencion">--</span></div>
                    <div><strong style="color:#0f172a;">Proyecto:</strong> <span id="ref_os_proyecto">--</span></div>
                    <div><strong style="color:#0f172a;">Cotización:</strong> <span id="ref_os_cotizacion" style="font-family:monospace;">--</span></div>
                    <div><strong style="color:#0f172a;">Forma de Pago:</strong> <span id="ref_os_pago">--</span></div>
                </div>

                <!-- Logística de Campo si aplica -->
                <div id="ref_os_logistica_box" style="display:none; background:white; border:1px solid #93c5fd; border-radius:8px; padding:10px; margin-top:12px;">
                    <strong style="font-size:11px; color:#0369a1; text-transform:uppercase; display:block; margin-bottom:4px;">
                        <i class="fa-solid fa-truck-pickup"></i> Logística de Muestreo en Campo
                    </strong>
                    <div style="font-size:11.5px; color:#1e293b;">
                        <div><strong>Técnico:</strong> <span id="ref_os_tecnico"></span></div>
                        <div><strong>Vehículo:</strong> <span id="ref_os_vehiculo"></span></div>
                        <div><strong>Fechas:</strong> <span id="ref_os_fechas"></span></div>
                    </div>
                </div>

                <!-- Tabla de Ensayos Solicitados por el Cliente -->
                <div style="margin-top: 15px;">
                    <strong style="font-size:11.5px; color:#0f172a; text-transform:uppercase; display:flex; align-items:center; gap:6px; margin-bottom:6px;">
                        <i class="fa-solid fa-vials" style="color:var(--cycsa-azul);"></i> Ensayos Solicitados (O/S)
                    </strong>
                    <table style="width:100%; border-collapse:collapse; background:white; border:1px solid #cbd5e1; border-radius:6px; overflow:hidden;">
                        <thead>
                            <tr style="background:#f1f5f9; font-size:11px; text-transform:uppercase; color:#475569;">
                                <th style="padding:6px; text-align:left; border-bottom:1px solid #cbd5e1;">Código</th>
                                <th style="padding:6px; text-align:left; border-bottom:1px solid #cbd5e1;">Ensayo / Norma</th>
                                <th style="padding:6px; text-align:center; border-bottom:1px solid #cbd5e1; width:45px;">Cant</th>
                            </tr>
                        </thead>
                        <tbody id="ref_os_tbody_ensayos">
                            <!-- Filas inyectadas por JS -->
                        </tbody>
                    </table>
                </div>

                <div style="margin-top: 12px; font-size: 11px; color: #64748b; background: white; border: 1px solid #e2e8f0; border-radius: 6px; padding: 8px;">
                    <i class="fa-solid fa-lightbulb" style="color:#f59e0b;"></i> <em>Referencia para cotejar los especímenes recibidos y los análisis que deben marcarse en la sección 3.</em>
                </div>
            </div>

            <!-- PANEL DERECHO: FORMULARIO EDITABLE RT-FM-13 -->
            <form method="POST" action="/Cycsa/publico/hojas-servicio/guardar" id="form-hoja-solicitud" style="display:block;">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                <input type="hidden" name="id_os" id="hs_id_os">

            <!-- 1. METADATOS Y CONTROL INTERNO -->
            <div style="font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px; margin-top: 15px;"><i class="fa-solid fa-clipboard-check"></i> 1. Metadatos y Control Interno</div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom: 15px;">
                <div class="form-group">
                    <label>Fecha/Hora de Llegada al Lab</label>
                    <input type="datetime-local" name="fecha_hora_llegada_laboratorio" id="hs_fecha_llegada" required class="form-control" style="font-size:13px; padding:8px 12px;">
                </div>
                <div class="form-group">
                    <label>Código del Documento</label>
                    <input type="text" name="codigo_documento" id="hs_codigo_documento" readonly class="form-control" style="background:#f1f5f9; font-weight:700; font-size:13px; padding:8px 12px;" value="CYCSA-RT-FM-13">
                </div>
            </div>

            <!-- 2. DATOS DEL CLIENTE -->
            <div style="font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;"><i class="fa-solid fa-user-tie"></i> 1. Empresa o Cliente que Solicita el Servicio</div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-bottom: 12px;">
                <div class="form-group">
                    <label>Nombre Empresa/Cliente</label>
                    <input type="text" name="nombre_empresa_o_cliente" id="hs_nombre_empresa" required class="form-control" style="font-size:13px; padding:8px 12px;">
                </div>
                <div class="form-group">
                    <label>Razón Social</label>
                    <input type="text" name="razon_social" id="hs_razon_social" class="form-control" style="font-size:13px; padding:8px 12px;" placeholder="Ej: CYCSA S.A. / Persona Natural">
                </div>
                <div class="form-group">
                    <label>Dirección Proyecto/Obra</label>
                    <input type="text" name="direccion_proyecto" id="hs_direccion" required class="form-control" style="font-size:13px; padding:8px 12px;">
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-bottom: 15px;">
                <div class="form-group">
                    <label>Teléfono</label>
                    <input type="text" name="telefono" id="hs_telefono" required class="form-control" style="font-size:13px; padding:8px 12px;">
                </div>
                <div class="form-group">
                    <label>Correo Electrónico</label>
                    <input type="email" name="correo_electronico" id="hs_email" required class="form-control" style="font-size:13px; padding:8px 12px;">
                </div>
                <div class="form-group">
                    <label>Nombre de quien trae la muestra</label>
                    <input type="text" name="nombre_persona_entrega_muestra" id="hs_persona_entrega" required class="form-control" style="font-size:13px; padding:8px 12px;">
                </div>
            </div>

            <!-- 3. DATOS DE LA MUESTRA -->
            <div style="font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;"><i class="fa-solid fa-flask-vial"></i> 1. Datos de la Muestra (Sección 1.1 y 1.2)</div>
            <div class="form-group" style="margin-bottom: 12px;">
                <label>Naturaleza de la Muestra (Seleccione todas las que apliquen)</label>
                <div style="display:flex; flex-wrap:wrap; gap:10px; margin-top:5px;">
                    <?php foreach (['Concreto', 'Bloques', 'Suelo', 'Adoquines', 'Agregados', 'Otros materiales'] as $nat): ?>
                        <label style="display:flex; align-items:center; gap:6px; background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; padding:6px 12px; font-size:13px; cursor:pointer;">
                            <input type="checkbox" name="naturaleza_muestra[]" value="<?= $nat ?>" class="hs-nat-checkbox"> <?= $nat ?>
                        </label>
                    <?php endforeach; ?>
                </div>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-bottom: 15px;">
                <div class="form-group">
                    <label>Procedencia/ Punto de muestreo</label>
                    <input type="text" name="procedencia_punto_muestreo" id="hs_procedencia" required class="form-control" style="font-size:13px; padding:8px 12px;">
                </div>
                <div class="form-group">
                    <label>¿Quién tomó la muestra?</label>
                    <select id="hs_tipo_toma_select" class="form-control" style="font-size:13px; padding:8px 12px; margin-bottom:8px; font-weight:600; color:var(--cycsa-azul);" onchange="seleccionarTipoTomaMuestra(this.value)">
                        <option value="tecnico">👷‍♂️ Técnico Muestreador CYCSA</option>
                        <option value="cliente">👤 Cliente / Entregada por Cliente</option>
                        <option value="otro">✏️ Otro / Escribir Nombre Personalizado</option>
                    </select>

                    <div id="wrapper_select_tecnico" style="margin-bottom:8px;">
                        <select id="hs_select_tecnico_cycsa" class="form-control" style="font-size:13px; padding:8px 12px; background:#f0f9ff; border-color:#93c5fd; font-weight:500;" onchange="alCambiarTecnicoSelect(this.value)">
                            <option value="">-- Seleccione Técnico CYCSA --</option>
                            <?php if (!empty($tecnicos)): ?>
                                <?php foreach ($tecnicos as $t): ?>
                                    <?php $nomTec = htmlspecialchars($t['nombre'] ?? $t['nombre_tecnico'] ?? ''); ?>
                                    <option value="<?= $nomTec ?>"><?= $nomTec ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>

                    <input type="text" name="nombre_persona_toma_muestra" id="hs_persona_toma" required class="form-control" style="font-size:13px; padding:8px 12px;" placeholder="Nombre de quien tomó la muestra">
                </div>
                <div class="form-group">
                    <label>Fecha y hora de toma muestra</label>
                    <input type="datetime-local" name="fecha_hora_toma_muestra" id="hs_fecha_toma" required class="form-control" style="font-size:13px; padding:8px 12px;">
                </div>
            </div>

            <!-- 4. IDENTIFICACIONES PROPIAS (TABLA DINÁMICA) -->
            <div style="display:flex; justify-content:space-between; align-items:center; font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;">
                <span><i class="fa-solid fa-list-ol"></i> 2. Identificaciones Propias de la Muestra (Especímenes)</span>
                <button type="button" class="btn-accion-hs btn-registrar" style="padding:4px 8px; font-size:11px; cursor:pointer;" onclick="agregarFilaMuestraModal()"><i class="fa-solid fa-plus"></i> Agregar Muestra</button>
            </div>
            <table class="hs-table" style="width:100%; border-collapse:collapse; margin-bottom:20px;" id="hs-tabla-muestras">
                <thead>
                    <tr style="background:#f8fafc;">
                        <th style="width:25%; font-size:12px; padding:8px;">Identificación del Espécimen</th>
                        <th style="width:40%; font-size:12px; padding:8px;">Elemento Estructural / Ubicación</th>
                        <th style="width:25%; font-size:12px; padding:8px;">Observación / Edad Muestreo</th>
                        <th style="width:10%; font-size:12px; padding:8px; text-align:center;">Acción</th>
                    </tr>
                </thead>
                <tbody id="hs-tbody-muestras">
                    <!-- Filas inyectadas por JS -->
                </tbody>
            </table>

            <!-- 5. ANÁLISIS REQUERIDOS (CHECKBOXES) -->
            <div style="font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;"><i class="fa-solid fa-vial"></i> 3. Análisis Solicitados (Marcar todos los que apliquen)</div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:20px; margin-bottom:15px;">
                <!-- Sección Concreto -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:12px; border-radius:8px;">
                    <strong style="font-size:13px; color:#1e293b; display:block; margin-bottom:8px; border-bottom:1px solid #cbd5e1; padding-bottom:4px;"><i class="fa-solid fa-cubes"></i> 3.1 Matrices Cementicias (Concreto)</strong>
                    <div style="display:flex; flex-direction:column; gap:6px;">
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer;">
                            <input type="checkbox" name="req_resistencia_concreto" value="1" id="hs_req_resistencia_concreto"> Resistencia a Compresión (Cilindros/Núcleos)
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer;">
                            <input type="checkbox" name="req_resistencia_adoquin" value="1" id="hs_req_resistencia_adoquin"> Resistencia a Compresión Adoquines
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:13px; cursor:pointer;">
                            <input type="checkbox" name="req_resistencia_bloques" value="1" id="hs_req_resistencia_bloques"> Resistencia a Compresión de Bloques
                        </label>
                        <div class="form-group" style="margin-top:5px; margin-bottom:0;">
                            <label style="font-size:11px; font-weight:600; margin-bottom:2px;">Otros Ensayos de Concreto</label>
                            <input type="text" name="req_otros_concreto" id="hs_req_otros_concreto" class="form-control" style="font-size:12px; padding:6px 10px;" placeholder="Ej: Revenimiento adicional, flexión...">
                        </div>
                    </div>
                </div>

                <!-- Sección Suelo -->
                <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:12px; border-radius:8px;">
                    <strong style="font-size:13px; color:#1e293b; display:block; margin-bottom:8px; border-bottom:1px solid #cbd5e1; padding-bottom:4px;"><i class="fa-solid fa-mountain"></i> 3.2 Geotecnia y Mecánica de Suelos</strong>
                    <div style="display:grid; grid-template-columns:1fr 1fr; gap:6px 10px;">
                        <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer;">
                            <input type="checkbox" name="req_granulometria" value="1" id="hs_req_granulometria"> Granulometría
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer;">
                            <input type="checkbox" name="req_limites_atterberg" value="1" id="hs_req_limites_atterberg"> Límites Atterberg
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer;">
                            <input type="checkbox" name="req_humedad" value="1" id="hs_req_humedad"> Contenido Humedad
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer;">
                            <input type="checkbox" name="req_resistencia_corte" value="1" id="hs_req_resistencia_corte"> Resistencia Corte
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer;">
                            <input type="checkbox" name="req_clasificacion_sucs_hr" value="1" id="hs_req_clasificacion_sucs_hr"> Clasificación SUCS/HR
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer;">
                            <input type="checkbox" name="req_proctor_sm" value="1" id="hs_req_proctor_sm"> Proctor SM
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer;">
                            <input type="checkbox" name="req_infiltracion" value="1" id="hs_req_infiltracion"> Ensayo Infiltración
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer;">
                            <input type="checkbox" name="req_cbr" value="1" id="hs_req_cbr"> CBR Completo
                        </label>
                        <label style="display:flex; align-items:center; gap:8px; font-size:12.5px; cursor:pointer;">
                            <input type="checkbox" name="req_densidad" value="1" id="hs_req_densidad"> Densidad de Campo
                        </label>
                    </div>
                    <div class="form-group" style="margin-top:8px; margin-bottom:0;">
                        <label style="font-size:11px; font-weight:600; margin-bottom:2px;">Otros Suelos</label>
                        <input type="text" name="req_otros_suelo" id="hs_req_otros_suelo" class="form-control" style="font-size:12px; padding:6px 10px;" placeholder="Ej: Expansión libre, permeabilidad...">
                    </div>
                </div>
            </div>

            <!-- Sección Otros Materiales -->
            <div style="background:#f8fafc; border:1px solid #e2e8f0; padding:12px; border-radius:8px; margin-bottom:15px;">
                <div style="display:flex; justify-content:space-between; align-items:center; border-bottom:1px solid #cbd5e1; padding-bottom:4px; margin-bottom:8px;">
                    <label style="display:flex; align-items:center; gap:8px; font-size:13px; font-weight:700; color:#1e293b; cursor:pointer; margin:0;">
                        <input type="checkbox" name="req_otros_materiales" value="1" id="hs_req_otros_materiales"> 3.3 Ensayos Especiales / Otros Materiales (Acero, Asfalto, etc.)
                    </label>
                </div>
                <div class="form-group" style="margin-bottom:0;">
                    <textarea name="descripcion_otros_analisis" id="hs_descripcion_otros" class="form-control" rows="2" style="font-size:13px;" placeholder="Describa el ensayo especial solicitado..."></textarea>
                </div>
            </div>

            <!-- 6. CIERRE, OBSERVACIONES Y FIRMAS -->
            <div style="font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;"><i class="fa-solid fa-signature"></i> Campos Finales / Cierre</div>
            <div class="form-group" style="margin-bottom:12px;">
                <label>Análisis adicionales</label>
                <textarea name="analisis_adicionales" id="hs_analisis_adicionales" class="form-control" rows="2" style="font-size:13px;" placeholder="Instrucciones adicionales para el laboratorio..."></textarea>
            </div>
            <div class="form-group" style="margin-bottom:15px;">
                <label>Observaciones</label>
                <textarea name="observaciones" id="hs_observaciones" class="form-control" rows="2" style="font-size:13px;" placeholder="Novedades observadas en la recepción..."></textarea>
            </div>
            <div style="display:grid; grid-template-columns:1fr 1fr 1fr; gap:15px; margin-bottom:15px;">
                <div class="form-group">
                    <label>Persona CYCSA que Recibe</label>
                    <input type="text" name="nombre_recibe_cycsa" id="hs_nombre_recibe" required class="form-control" style="font-size:13px; padding:8px 12px;" placeholder="Nombre completo">
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div style="display:flex; align-items:center; height:38px;">
                        <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer; margin:0;">
                            <input type="checkbox" name="firma_recibe_cycsa" value="1" id="hs_firma_recibe_cycsa"> ¿Firma digitalizada receptor?
                        </label>
                    </div>
                </div>
                <div class="form-group">
                    <label>&nbsp;</label>
                    <div style="display:flex; align-items:center; height:38px;">
                        <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer; margin:0;">
                            <input type="checkbox" name="firma_cliente" value="1" id="hs_firma_cliente"> ¿Firma digitalizada cliente?
                        </label>
                    </div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; border-top:1px solid #cbd5e1; padding-top:15px;">
                <button type="button" onclick="cerrarModalHojaSolicitud()" class="btn-accion-hs btn-editar" style="padding:8px 20px; font-size:13px;">Cancelar</button>
                <button type="submit" class="btn-accion-hs btn-registrar" style="padding:8px 20px; font-size:13px;"><i class="fa-solid fa-save"></i> Guardar y Generar PDF</button>
            </div>
        </form>
        </div>
    </div>
</div>

<!-- MODAL DECISIÓN SUPERVISOR (Aprobar o Rehusar la Hoja de Servicio) -->
<div id="modalRevision" class="modal-premium" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">
    <div class="modal-premium-content" style="width: 40%; max-width:500px; background:white; margin:10% auto; padding:25px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #1e293b; font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700;">Revisión de Hoja de Servicio: <span id="rev_codigo_os" style="color:var(--cycsa-azul);"></span></h3>
            <button onclick="cerrarModalRevision()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/hojas-servicio/procesar-revision" id="form-revision-os">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_os" id="rev_id_os">
            <input type="hidden" name="estado" id="rev_nuevo_estado" value="">
            
            <div class="form-group" id="group-muestreo-check">
                <label style="font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 8px;">¿Requiere Muestreo en Campo?</label>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px;">
                    <label style="border: 2px solid #cbd5e1; border-radius: 8px; padding: 12px; cursor: pointer; text-align: center; display: block; background: white;" id="card-muestreo-si" onclick="setRequiereMuestreo(1)">
                        <input type="radio" name="requiere_muestreo" value="1" style="display:none;" id="radio-muestreo-si">
                        <div style="font-weight: 700; font-size: 14px; color: #1e293b;">SÍ REQUIERE</div>
                        <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Ruta de Campo (Estado 3A)</div>
                    </label>
                    <label style="border: 2px solid #cbd5e1; border-radius: 8px; padding: 12px; cursor: pointer; text-align: center; display: block; background: white;" id="card-muestreo-no" onclick="setRequiereMuestreo(0)">
                        <input type="radio" name="requiere_muestreo" value="0" style="display:none;" id="radio-muestreo-no">
                        <div style="font-weight: 700; font-size: 14px; color: #1e293b;">NO REQUIERE</div>
                        <div style="font-size: 10px; color: #64748b; margin-top: 2px;">Ingreso Directo (Estado 3)</div>
                    </label>
                </div>
            </div>

            <div class="form-group" id="group-motivo-obs" style="display:none; margin-top: 15px;">
                <label style="font-weight: 700; font-size: 13px; color: #b91c1c; display: block; margin-bottom: 5px;">Indique el Motivo de Observación / Corrección (Requerido)</label>
                <textarea name="motivo_observacion" id="rev_motivo" class="form-control" rows="3" placeholder="Ej: Los diámetros de vigas son incorrectos en la descripción, corregir..."></textarea>
            </div>

            <div style="display: flex; justify-content: space-between; gap: 15px; margin-top: 25px; border-top:1px solid #cbd5e1; padding-top:15px;">
                <button type="button" onclick="ejecutarObservacionOS()" class="btn-accion-hs btn-pdf" style="cursor:pointer;"><i class="fa-solid fa-triangle-exclamation"></i> Observar / Rechazar</button>
                <div style="display:flex; gap:10px;">
                    <button type="button" onclick="cerrarModalRevision()" class="btn-accion-hs btn-editar" style="cursor:pointer;">Cancelar</button>
                    <button type="button" onclick="ejecutarAprobacionOS()" class="btn-accion-hs btn-registrar" id="btn-aprobar-submit" style="display:none; cursor:pointer;"><i class="fa-solid fa-circle-check"></i> Aprobar y Enviar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<script>
    // Navegación entre pestañas (Tabs)
    function switchTab(tabId, btn) {
        document.querySelectorAll('.hs-tab-pane').forEach(p => p.classList.remove('active'));
        document.querySelectorAll('.hs-tab-btn').forEach(b => b.classList.remove('active'));
        
        document.getElementById(tabId).classList.add('active');
        btn.classList.add('active');
    }

    // Modal de Hoja de Solicitud (CYCSA-RT-FM-13)
    const modHS = document.getElementById('modalHojaSolicitud');
    const hsLoading = document.getElementById('loading-hoja-solicitud');
    const hsForm = document.getElementById('form-hoja-solicitud');

    let siguienteConsecutivoMuestra = 1;
    let anioActualMuestra = new Date().getFullYear();

    // Detecta el máximo consecutivo usado en las filas actuales del tbody
    function recalcularConsecutivoMuestra() {
        const tbody = document.getElementById('hs-tbody-muestras');
        if (!tbody) return;
        let max = 0;
        tbody.querySelectorAll('input[name="m_nombre[]"]').forEach(input => {
            const val = input.value || '';
            // Soporta formatos: MC-NNN-YYYY o CYCSA-M-YYYY-NNNN
            const m1 = val.match(/^MC-(\d+)-\d{4}$/);
            const m2 = val.match(/^CYCSA-M-\d{4}-(\d+)$/);
            const num = m1 ? parseInt(m1[1]) : (m2 ? parseInt(m2[1]) : 0);
            if (num > max) max = num;
        });
        siguienteConsecutivoMuestra = max + 1;
    }

    // Intercepción Inteligente del clic en "Registrar Hoja RT-FM-13" (Casos A, B y C)
    function iniciarRegistroHojaRTFM13(btnOrId, codeParam, estadoParam, tecParam, idaParam, llegParam) {
        let idOS, codigoOS, estadoMuestreo, tecnico, fechaIda, fechaLlegada;

        if (typeof btnOrId === 'object' && btnOrId !== null) {
            idOS = btnOrId.getAttribute('data-id-os');
            codigoOS = btnOrId.getAttribute('data-codigo-os');
            estadoMuestreo = btnOrId.getAttribute('data-estado-muestreo') || 'sin_decidir';
            tecnico = btnOrId.getAttribute('data-tecnico') || '';
            fechaIda = btnOrId.getAttribute('data-fecha-ida') || '';
            fechaLlegada = btnOrId.getAttribute('data-fecha-llegada') || '';
        } else {
            idOS = btnOrId;
            codigoOS = codeParam || 'O/S #' + idOS;
            estadoMuestreo = estadoParam || 'sin_decidir';
            tecnico = tecParam || '';
            fechaIda = idaParam || '';
            fechaLlegada = llegParam || '';
        }

        // =========================================================================
        // CASO B: MUESTREO EN PROCESO (El técnico se encuentra en campo)
        // =========================================================================
        if (estadoMuestreo === 'en_proceso') {
            if (typeof Swal !== 'undefined') {
                Swal.fire({
                    title: 'Muestreo en campo en proceso',
                    html: `
                        <div style="text-align:left; font-size:13.5px; color:#475569; margin-top:8px; line-height:1.5;">
                            <div style="background:#fef3c7; border:1px solid #fde68a; border-radius:8px; padding:12px; margin-bottom:14px;">
                                <strong style="color:#92400e; display:block; margin-bottom:4px;"><i class="fa-solid fa-truck-pickup"></i> Estado: Técnico en Campo</strong>
                                <div style="font-size:12.5px; color:#78350f;">
                                    ${tecnico ? `<div><strong>Técnico:</strong> ${tecnico}</div>` : ''}
                                    ${fechaIda ? `<div><strong>Salida:</strong> ${fechaIda}</div>` : ''}
                                    ${fechaLlegada ? `<div><strong>Retorno estimado:</strong> ${fechaLlegada}</div>` : ''}
                                </div>
                            </div>
                            <p style="font-size:14px; font-weight:700; color:#0f172a; margin-bottom:6px;">¿El técnico ya regresó al laboratorio con los especímenes?</p>
                            <p style="font-size:12px; color:#64748b; margin:0;">Al confirmar, la orden se marcará como finalizada y podrá ingresar la Hoja RT-FM-13 inmediatamente.</p>
                        </div>
                    `,
                    icon: 'info',
                    showCancelButton: true,
                    showDenyButton: true,
                    confirmButtonText: '<i class="fa-solid fa-circle-check"></i> Sí, finalizar muestreo',
                    confirmButtonColor: '#10b981',
                    denyButtonText: '<i class="fa-solid fa-pen-to-square"></i> Ver / Editar Logística',
                    denyButtonColor: '#103487',
                    cancelButtonText: 'Aún en campo (Cerrar)',
                    cancelButtonColor: '#94a3b8',
                    reverseButtons: false,
                    focusConfirm: true
                }).then((result) => {
                    if (result.isConfirmed) {
                        // Finalizar muestreo vía Ajax
                        Swal.fire({
                            title: 'Finalizando muestreo...',
                            allowOutsideClick: false,
                            didOpen: () => { Swal.showLoading(); }
                        });

                        const formData = new FormData();
                        formData.append('id_os', idOS);
                        formData.append('ajax', '1');

                        fetch('/Cycsa/publico/ordenes-servicio/finalizar-muestreo', {
                            method: 'POST',
                            body: formData
                        })
                        .then(r => r.json())
                        .then(res => {
                            if (res.status === 'success') {
                                Swal.close();
                                if (typeof btnOrId === 'object' && btnOrId !== null) {
                                    btnOrId.setAttribute('data-estado-muestreo', 'finalizado');
                                }
                                // Abrir directamente el modal RT-FM-13
                                abrirModalHojaSolicitud(idOS, codigoOS);
                            } else {
                                Swal.fire('Error', res.message || 'No se pudo finalizar el muestreo.', 'error');
                            }
                        })
                        .catch(err => {
                            console.error(err);
                            Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
                        });
                    } else if (result.isDenied) {
                        window.location.href = '/Cycsa/publico/ordenes-servicio/programar-muestreo?id=' + idOS;
                    }
                });
            } else {
                if (confirm("El técnico está en campo.\n\n¿El técnico ya regresó y desea FINALIZAR el muestreo para abrir la Hoja RT-FM-13?")) {
                    window.location.href = '/Cycsa/publico/ordenes-servicio/programar-muestreo?id=' + idOS;
                }
            }
            return;
        }

        // =========================================================================
        // CASO C: MUESTREO FINALIZADO O INGRESO DIRECTO
        // =========================================================================
        if (estadoMuestreo === 'finalizado' || estadoMuestreo === 'no_aplica') {
            abrirModalHojaSolicitud(idOS, codigoOS);
            return;
        }

        // =========================================================================
        // CASO A: SIN DECIDIR / NUEVA ORDEN
        // =========================================================================
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Se requiere muestreo en campo?',
                html: `
                    <div style="text-align:left; font-size:13.5px; color:#475569; margin-top:8px; line-height:1.5;">
                        <div style="background:#f1f5f9; padding:10px 14px; border-radius:6px; border:1px solid #e2e8f0; margin-bottom:12px;">
                            <strong style="color:#0f172a;">Orden de Servicio:</strong> <span style="color:var(--cycsa-azul); font-family:monospace; font-weight:700;">${codigoOS}</span>
                        </div>
                        <p style="margin-bottom:8px;">Seleccione el flujo operativo para esta Orden de Servicio:</p>
                        <ul style="margin-left:18px; font-size:12.5px; color:#64748b;">
                            <li><strong>Sí, programar logística:</strong> Asignar técnico, vehículo y fechas de salida a campo.</li>
                            <li><strong>No, ingreso directo:</strong> Abrir el formulario RT-FM-13 para especímenes entregados en laboratorio.</li>
                        </ul>
                    </div>
                `,
                icon: 'question',
                showCancelButton: true,
                showDenyButton: true,
                confirmButtonText: '<i class="fa-solid fa-truck-pickup"></i> Sí, programar logística',
                confirmButtonColor: '#103487',
                denyButtonText: '<i class="fa-solid fa-flask"></i> No, ingreso directo',
                denyButtonColor: '#10b981',
                cancelButtonText: 'Cancelar',
                cancelButtonColor: '#94a3b8',
                reverseButtons: false,
                focusDeny: true,
                allowOutsideClick: false
            }).then((result) => {
                if (result.isConfirmed) {
                    // CAMINO SÍ: Redirigir a programar logística (el técnico sale a campo)
                    window.location.href = '/Cycsa/publico/ordenes-servicio/programar-muestreo?id=' + idOS;
                } else if (result.isDenied) {
                    // CAMINO NO: Registrar ingreso directo y abrir modal RT-FM-13
                    if (typeof btnOrId === 'object' && btnOrId !== null) {
                        btnOrId.setAttribute('data-estado-muestreo', 'no_aplica');
                    }
                    fetch('/Cycsa/publico/ordenes-servicio/marcar-ingreso-directo', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                        body: 'id_os=' + encodeURIComponent(idOS)
                    }).catch(console.error);

                    abrirModalHojaSolicitud(idOS, codigoOS);
                }
            });
        } else {
            if (confirm("¿Se requiere muestreo en campo para la Orden " + codigoOS + "?\n\n- Aceptar (OK): SÍ, programar logística.\n- Cancelar: NO, ingreso directo al formulario RT-FM-13.")) {
                window.location.href = '/Cycsa/publico/ordenes-servicio/programar-muestreo?id=' + idOS;
            } else {
                abrirModalHojaSolicitud(idOS, codigoOS);
            }
        }
    }

    function abrirModalHojaSolicitud(idOS, code) {
        document.getElementById('hs_codigo_os_label').innerText = code;
        modHS.style.display = 'block';
        hsLoading.style.display = 'block';
        const splitWrapper = document.getElementById('wrapper-split-rt-fm-13');
        if (splitWrapper) splitWrapper.style.display = 'none';

        // Limpiar especímenes y resetear contador
        document.getElementById('hs-tbody-muestras').innerHTML = '';
        siguienteConsecutivoMuestra = 1;

        fetch('/Cycsa/publico/hojas-servicio/datos?id_os=' + idOS)
            .then(res => {
                if (!res.ok) {
                    throw new Error('Respuesta HTTP ' + res.status + ' (' + res.statusText + ')');
                }
                return res.json();
            })
            .then(data => {
                if (data.status === 'error') {
                    alert(data.message || 'Error al obtener datos.');
                    cerrarModalHojaSolicitud();
                    return;
                }
                
                const hoja = data.hoja;

                // 1. POBLAR PANEL LATERAL DE REFERENCIA VISUAL O/S (CYCSA-RG-FM-39)
                if (data.os_referencia) {
                    const osRef = data.os_referencia;
                    document.getElementById('ref_os_cliente').innerText = osRef.cliente_nombre || 'N/A';
                    document.getElementById('ref_os_rfc').innerText = osRef.cliente_rfc || 'N/A';
                    document.getElementById('ref_os_atencion').innerText = osRef.atencion_a || 'N/A';
                    document.getElementById('ref_os_proyecto').innerText = osRef.nombre_proyecto || 'N/A';
                    document.getElementById('ref_os_cotizacion').innerText = (osRef.cotizacion_codigo || 'N/A') + (osRef.cotizacion_version ? ' (v' + osRef.cotizacion_version + ')' : '');
                    document.getElementById('ref_os_pago').innerText = osRef.forma_pago || 'Pago contra entrega';
                    
                    const badgeEst = document.getElementById('ref_os_badge_estado');
                    if (badgeEst) badgeEst.innerText = osRef.estado || 'Solo Lectura';

                    // Logística de Muestreo en Campo
                    const logBox = document.getElementById('ref_os_logistica_box');
                    if (osRef.programacion_muestreo) {
                        logBox.style.display = 'block';
                        document.getElementById('ref_os_tecnico').innerText = osRef.programacion_muestreo.tecnico_nombre || 'Asignado';
                        document.getElementById('ref_os_vehiculo').innerText = ((osRef.programacion_muestreo.marca || '') + ' ' + (osRef.programacion_muestreo.modelo || '') + ' (' + (osRef.programacion_muestreo.placa || '') + ')').trim() || 'N/A';
                        document.getElementById('ref_os_fechas').innerText = (osRef.programacion_muestreo.fecha_ida ? osRef.programacion_muestreo.fecha_ida.substring(0, 16) : '') + ' a ' + (osRef.programacion_muestreo.fecha_llegada ? osRef.programacion_muestreo.fecha_llegada.substring(0, 16) : '');
                    } else {
                        logBox.style.display = 'none';
                    }

                    // Ensayos Solicitados por el Cliente en la O/S
                    const tbodyEns = document.getElementById('ref_os_tbody_ensayos');
                    tbodyEns.innerHTML = '';
                    if (osRef.ensayos && osRef.ensayos.length > 0) {
                        osRef.ensayos.forEach(ens => {
                            const tr = document.createElement('tr');
                            tr.innerHTML = `
                                <td style="padding:6px; border-bottom:1px solid #e2e8f0; font-weight:700; color:var(--cycsa-azul); font-size:11.5px;">${ens.codigo_servicio || 'CYCSA-PE'}</td>
                                <td style="padding:6px; border-bottom:1px solid #e2e8f0; font-size:11.5px;">
                                    ${ens.nombre_ensayo || ens.descripcion_ensayo || ''}
                                    ${ens.norma_astm ? '<div style="font-size:10px; color:#0369a1; font-weight:600; margin-top:2px;">Norma: ' + ens.norma_astm + '</div>' : ''}
                                </td>
                                <td style="padding:6px; border-bottom:1px solid #e2e8f0; text-align:center; font-weight:700; font-size:11.5px;">${parseFloat(ens.cantidad || 0).toFixed(1)}</td>
                            `;
                            tbodyEns.appendChild(tr);
                        });
                    } else {
                        tbodyEns.innerHTML = '<tr><td colspan="3" style="text-align:center; color:#64748b; font-size:11px; padding:10px;">Sin ensayos registrados</td></tr>';
                    }
                }

                // 2. LLENAR FORMULARIO EDITABLE CYCSA-RT-FM-13
                document.getElementById('hs_id_os').value = hoja.id_os;
                document.getElementById('hs_codigo_documento').value = hoja.codigo_documento || 'CYCSA-RT-FM-13';
                document.getElementById('hs_fecha_llegada').value = hoja.fecha_hora_llegada_laboratorio ? hoja.fecha_hora_llegada_laboratorio.replace(' ', 'T') : '';
                document.getElementById('hs_nombre_empresa').value = hoja.nombre_empresa_o_cliente || '';
                document.getElementById('hs_razon_social').value = hoja.razon_social || '';
                document.getElementById('hs_direccion').value = hoja.direccion_proyecto || '';
                document.getElementById('hs_telefono').value = hoja.telefono || '';
                document.getElementById('hs_email').value = hoja.correo_electronico || '';
                document.getElementById('hs_persona_entrega').value = hoja.nombre_persona_entrega_muestra || '';
                document.getElementById('hs_procedencia').value = hoja.procedencia_punto_muestreo || '';
                const valToma = hoja.nombre_persona_toma_muestra || '';
                document.getElementById('hs_persona_toma').value = valToma;

                const selTec = document.getElementById('hs_select_tecnico_cycsa');
                const tipoSelect = document.getElementById('hs_tipo_toma_select');
                const wrapperTec = document.getElementById('wrapper_select_tecnico');

                let matchTecnico = false;
                if (selTec) {
                    Array.from(selTec.options).forEach(opt => {
                        if (opt.value && opt.value.trim() === valToma.trim()) {
                            matchTecnico = true;
                            opt.selected = true;
                        }
                    });
                }

                if (matchTecnico) {
                    tipoSelect.value = 'tecnico';
                    wrapperTec.style.display = 'block';
                    document.getElementById('hs_persona_toma').readOnly = false;
                } else if (valToma.includes('Cliente') || valToma.includes('cliente')) {
                    tipoSelect.value = 'cliente';
                    wrapperTec.style.display = 'none';
                    document.getElementById('hs_persona_toma').readOnly = true;
                } else if (valToma !== '') {
                    tipoSelect.value = 'otro';
                    wrapperTec.style.display = 'none';
                    document.getElementById('hs_persona_toma').readOnly = false;
                } else {
                    tipoSelect.value = 'tecnico';
                    wrapperTec.style.display = 'block';
                    document.getElementById('hs_persona_toma').readOnly = false;
                }

                document.getElementById('hs_fecha_toma').value = hoja.fecha_hora_toma_muestra ? hoja.fecha_hora_toma_muestra.replace(' ', 'T') : '';
                
                // Naturalezas
                const natureList = (hoja.naturaleza_muestra || '').split(',');
                document.querySelectorAll('.hs-nat-checkbox').forEach(cb => {
                    cb.checked = natureList.includes(cb.value);
                });

                // Parámetros Concreto
                document.getElementById('hs_req_resistencia_concreto').checked = parseInt(hoja.req_resistencia_concreto) === 1;
                document.getElementById('hs_req_resistencia_adoquin').checked = parseInt(hoja.req_resistencia_adoquin) === 1;
                document.getElementById('hs_req_resistencia_bloques').checked = parseInt(hoja.req_resistencia_bloques) === 1;
                document.getElementById('hs_req_otros_concreto').value = hoja.req_otros_concreto || '';

                // Parámetros Suelo
                document.getElementById('hs_req_granulometria').checked = parseInt(hoja.req_granulometria) === 1;
                document.getElementById('hs_req_limites_atterberg').checked = parseInt(hoja.req_limites_atterberg) === 1;
                document.getElementById('hs_req_humedad').checked = parseInt(hoja.req_humedad) === 1;
                document.getElementById('hs_req_resistencia_corte').checked = parseInt(hoja.req_resistencia_corte) === 1;
                document.getElementById('hs_req_clasificacion_sucs_hr').checked = parseInt(hoja.req_clasificacion_sucs_hr) === 1;
                document.getElementById('hs_req_proctor_sm').checked = parseInt(hoja.req_proctor_sm) === 1;
                document.getElementById('hs_req_infiltracion').checked = parseInt(hoja.req_infiltracion) === 1;
                document.getElementById('hs_req_cbr').checked = parseInt(hoja.req_cbr) === 1;
                document.getElementById('hs_req_densidad').checked = parseInt(hoja.req_densidad) === 1;
                document.getElementById('hs_req_otros_suelo').value = hoja.req_otros_suelo || '';

                // Otros
                document.getElementById('hs_req_otros_materiales').checked = parseInt(hoja.req_otros_materiales) === 1;
                document.getElementById('hs_descripcion_otros').value = hoja.descripcion_otros_analisis || '';

                // Footer
                document.getElementById('hs_analisis_adicionales').value = hoja.analisis_adicionales || '';
                document.getElementById('hs_observaciones').value = hoja.observaciones || '';
                document.getElementById('hs_nombre_recibe').value = hoja.nombre_recibe_cycsa || '';
                document.getElementById('hs_firma_recibe_cycsa').checked = parseInt(hoja.firma_recibe_cycsa) === 1;
                document.getElementById('hs_firma_cliente').checked = parseInt(hoja.firma_cliente) === 1;

                // Especímenes / muestras
                let muestras = [];
                try {
                    muestras = JSON.parse(hoja.muestras_json || '[]');
                } catch(e) {
                    muestras = [];
                }

                if (muestras.length === 0) {
                    agregarFilaMuestraModal('', 'Cilindros de concreto', 'Estándar');
                } else {
                    muestras.forEach(m => {
                        agregarFilaMuestraModal(m.nombre_muestra, m.descripcion, m.info_importante);
                    });
                    recalcularConsecutivoMuestra();
                }

                hsLoading.style.display = 'none';
                if (splitWrapper) splitWrapper.style.display = 'grid';
            })
            .catch(err => {
                console.error(err);
                alert('Error al obtener datos del servidor.');
                cerrarModalHojaSolicitud();
            });
    }

    function cerrarModalHojaSolicitud() {
        modHS.style.display = 'none';
    }

    function seleccionarTipoTomaMuestra(val) {
        const personaInput = document.getElementById('hs_persona_toma');
        const wrapperTecnico = document.getElementById('wrapper_select_tecnico');
        const selectTecnico = document.getElementById('hs_select_tecnico_cycsa');
        
        if (val === 'cliente') {
            wrapperTecnico.style.display = 'none';
            personaInput.value = 'Cliente / Entregada por Cliente';
            personaInput.readOnly = true;
        } else if (val === 'tecnico') {
            wrapperTecnico.style.display = 'block';
            personaInput.readOnly = false;
            if (selectTecnico.value) {
                personaInput.value = selectTecnico.value;
            } else {
                personaInput.value = '';
            }
        } else if (val === 'otro') {
            wrapperTecnico.style.display = 'none';
            personaInput.readOnly = false;
            if (personaInput.value.includes('Cliente') || Array.from(selectTecnico.options).some(o => o.value && o.value === personaInput.value)) {
                personaInput.value = '';
            }
            personaInput.focus();
        }
    }

    function alCambiarTecnicoSelect(val) {
        const personaInput = document.getElementById('hs_persona_toma');
        personaInput.value = val;
    }

    function agregarFilaMuestraModal(nombre = '', desc = '', info = '') {
        const tbody = document.getElementById('hs-tbody-muestras');
        
        // Si desc o info no son provistos explícitamente, copiar de la última fila o usar valores por defecto
        if (tbody) {
            const lastDescInput = tbody.querySelector('tr:last-child input[name="m_desc[]"]');
            const lastInfoInput = tbody.querySelector('tr:last-child input[name="m_info[]"]');
            
            if (desc === '') {
                desc = (lastDescInput && lastDescInput.value.trim() !== '') ? lastDescInput.value : 'Cilindros de concreto';
            }
            if (info === '') {
                info = (lastInfoInput && lastInfoInput.value.trim() !== '') ? lastInfoInput.value : 'Estándar';
            }
        } else {
            if (desc === '') desc = 'Cilindros de concreto';
            if (info === '') info = 'Estándar';
        }

        if (nombre === '') {
            // Recalcular antes de generar por si el usuario borró filas
            recalcularConsecutivoMuestra();
            // Generar código en formato MC-NNN-YYYY (mismo que el sistema anterior)
            const consecutiveStr = String(siguienteConsecutivoMuestra).padStart(3, '0');
            nombre = `MC-${consecutiveStr}-${anioActualMuestra}`;
            siguienteConsecutivoMuestra++;
        }

        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="padding:8px;"><input type="text" name="m_nombre[]" value="${nombre}" class="form-control" style="font-size:12.5px; padding:6px 10px; font-weight:600; font-family:monospace;" required></td>
            <td style="padding:8px;"><input type="text" name="m_desc[]" value="${desc}" class="form-control" style="font-size:12.5px; padding:6px 10px;" required placeholder="Ej: Columnas Eje C-3"></td>
            <td style="padding:8px;"><input type="text" name="m_info[]" value="${info}" class="form-control" style="font-size:12.5px; padding:6px 10px;" placeholder="Ej: Edad 7d / Edad 28d"></td>
            <td style="padding:8px; text-align:center;"><button type="button" class="btn-accion-hs btn-pdf" style="padding:5px 8px; font-size:11px;" onclick="eliminarFilaMuestraModal(this)"><i class="fa-solid fa-trash"></i></button></td>
        `;
        tbody.appendChild(tr);
    }

    function eliminarFilaMuestraModal(btn) {
        btn.closest('tr').remove();
    }

    // Modal de Revisión por Supervisor
    const modRev = document.getElementById('modalRevision');

    function abrirModalRevision(id, code) {
        document.getElementById('rev_id_os').value = id;
        document.getElementById('rev_codigo_os').innerText = code;
        document.getElementById('radio-muestreo-si').checked = false;
        document.getElementById('radio-muestreo-no').checked = false;
        document.getElementById('card-muestreo-si').style.borderColor = '#cbd5e1';
        document.getElementById('card-muestreo-si').style.background = 'white';
        document.getElementById('card-muestreo-no').style.borderColor = '#cbd5e1';
        document.getElementById('card-muestreo-no').style.background = 'white';
        
        document.getElementById('btn-aprobar-submit').style.display = 'none';
        document.getElementById('group-motivo-obs').style.display = 'none';
        document.getElementById('rev_motivo').value = '';
        modRev.style.display = 'block';

        // Pre-seleccionar SÍ o NO requiere muestreo según lo indicado en la Hoja de Servicio
        fetch('/Cycsa/publico/hojas-servicio/datos?id_os=' + id)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'success' && data.hoja) {
                    const pToma = data.hoja.nombre_persona_toma_muestra || '';
                    if (pToma.toLowerCase().includes('cliente')) {
                        setRequiereMuestreo(0);
                    } else if (pToma.trim() !== '') {
                        setRequiereMuestreo(1);
                    }
                }
            })
            .catch(e => console.error(e));
    }

    function cerrarModalRevision() {
        modRev.style.display = 'none';
    }

    function setRequiereMuestreo(val) {
        document.getElementById('group-motivo-obs').style.display = 'none';
        document.getElementById('btn-aprobar-submit').style.display = 'inline-flex';
        
        if (val === 1) {
            document.getElementById('radio-muestreo-si').checked = true;
            document.getElementById('card-muestreo-si').style.borderColor = 'var(--cycsa-azul)';
            document.getElementById('card-muestreo-si').style.background = '#eff6ff';
            document.getElementById('card-muestreo-no').style.borderColor = '#cbd5e1';
            document.getElementById('card-muestreo-no').style.background = 'white';
            
            // Si requiere, irá a Programación de Muestreo (Estado 3A)
            document.getElementById('rev_nuevo_estado').value = 'Estado 3A: Programacion Muestreo';
        } else {
            document.getElementById('radio-muestreo-no').checked = true;
            document.getElementById('card-muestreo-no').style.borderColor = 'var(--cycsa-azul)';
            document.getElementById('card-muestreo-no').style.background = '#eff6ff';
            document.getElementById('card-muestreo-si').style.borderColor = '#cbd5e1';
            document.getElementById('card-muestreo-si').style.background = 'white';
            
            // Si no requiere, irá a Ingreso Directo (Estado 3)
            document.getElementById('rev_nuevo_estado').value = 'Estado 3: Ingreso Directo';
        }
    }

    function ejecutarObservacionOS() {
        const groupObs = document.getElementById('group-motivo-obs');
        if (groupObs.style.display === 'none') {
            groupObs.style.display = 'block';
            document.getElementById('rev_motivo').focus();
            document.getElementById('btn-aprobar-submit').style.display = 'none';
            
            document.getElementById('card-muestreo-si').style.borderColor = '#cbd5e1';
            document.getElementById('card-muestreo-si').style.background = 'white';
            document.getElementById('card-muestreo-no').style.borderColor = '#cbd5e1';
            document.getElementById('card-muestreo-no').style.background = 'white';
        } else {
            const motivo = document.getElementById('rev_motivo').value.trim();
            if (motivo === '') {
                alert('Por favor, indique la razón de la observación.');
                document.getElementById('rev_motivo').focus();
                return;
            }
            document.getElementById('rev_nuevo_estado').value = 'Estado 2: Observada';
            document.getElementById('form-revision-os').submit();
        }
    }

    function ejecutarAprobacionOS() {
        document.getElementById('form-revision-os').submit();
    }

    <?php if (!empty($id_os_auto)): ?>
    document.addEventListener("DOMContentLoaded", function() {
        if (typeof abrirModalHojaSolicitud === 'function') {
            abrirModalHojaSolicitud(<?= (int)$id_os_auto ?>, 'O/S #<?= (int)$id_os_auto ?>');
        }
    });
    <?php endif; ?>
</script>
