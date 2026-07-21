<style>
    /* Premium Table Styling */
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; background: white; border-radius: 8px; overflow: hidden; }
    .tabla-cycsa th { background-color: #f8fafc; color: #475569; padding: 14px 18px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
    .tabla-cycsa td { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #1e293b; }
    .tabla-cycsa tbody tr:hover { background-color: #f8fafc; }
    
    .panel-premium { background: white; padding: 25px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border: 1px solid #edf2f7; margin-bottom: 30px; }
    
    /* Navigation Tabs */
    .tabs-container { display: flex; border-bottom: 1px solid #dee2e6; margin-bottom: 25px; gap: 5px; }
    .tab-link { padding: 12px 20px; text-decoration: none; color: #64748b; font-weight: 500; border-bottom: 2px solid transparent; transition: all 0.2s; font-size: 14px; display: flex; align-items: center; gap: 8px; font-family: 'Inter', sans-serif; }
    .tab-link:hover { color: var(--cycsa-azul); }
    .tab-link.active { color: var(--cycsa-azul); border-bottom-color: var(--cycsa-azul); font-weight: 600; }

    /* Action Buttons */
    .btn-cycsa { display: inline-flex; align-items: center; gap: 6px; border: 1px solid transparent; padding: 8px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .btn-cycsa-primary { background: var(--cycsa-azul); color: white; font-size: 13.5px; padding: 10px 18px; }
    .btn-cycsa-primary:hover { background: #0c2766; transform: translateY(-1px); }
    .btn-cycsa-danger { background: #fee2e2; color: #ef4444; border-color: #fca5a5; }
    .btn-cycsa-danger:hover { background: #ef4444; color: white; }
    .btn-cycsa-secondary { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .btn-cycsa-secondary:hover { background: #cbd5e1; color: #0f172a; }

    /* Modals styling */
    .modal-premium { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); }
    .modal-premium-content { background-color: white; margin: 10% auto; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; width: 450px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); animation: slideDown 0.25s ease-out; }
    
    @keyframes slideDown {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }

    .form-group { margin-bottom: 15px; }
    .form-group label { display: block; font-weight: 600; font-size: 13px; color: #334155; margin-bottom: 6px; }
    .form-control { width: 100%; padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; font-family: 'Inter', sans-serif; transition: all 0.2s; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.1); }
</style>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <a href="/Cycsa/publico/panel" style="color: #6c757d; text-decoration: none; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Volver al Panel</a>
        <h2 style="margin: 5px 0 0 0; color: #1e293b; font-weight: 700; font-size: 24px;">Configuración General de Catálogos</h2>
    </div>
</div>

<div class="tabs-container">
    <a href="/Cycsa/publico/configuracion?tab=comercial" class="tab-link <?= $tabActual === 'comercial' ? 'active' : '' ?>">
        <i class="fa-solid fa-credit-card"></i> Condiciones Comerciales
    </a>
    <a href="/Cycsa/publico/configuracion?tab=logistica" class="tab-link <?= $tabActual === 'logistica' ? 'active' : '' ?>">
        <i class="fa-solid fa-truck"></i> Logística y Muestreo
    </a>
</div>

<?php if ($tabActual === 'comercial'): ?>
    <!-- SECCIÓN: CONDICIONES COMERCIALES -->
    <div style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 15px; border-radius: 6px; margin-bottom: 25px; display: flex; gap: 12px; align-items: flex-start;">
        <i class="fa-solid fa-circle-info" style="color: #0284c7; font-size: 18px; margin-top: 2px;"></i>
        <div style="font-size: 13.5px; color: #0369a1; line-height: 1.5;">
            <strong>Área de Configuración de Cotizaciones:</strong> Modifica las opciones que se cargan de forma dinámica en los selectores y autocompletados de condiciones comerciales (Condición de Pago, Tiempo de Entrega y Vigencia de la Oferta) al crear o editar una cotización.
        </div>
    </div>

    <div class="panel-premium">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;">Listado de Condiciones Comerciales</h3>
                <p style="margin: 3px 0 0 0; font-size: 13px; color: #64748b;">Administra las condiciones predefinidas para la emisión de cotizaciones.</p>
            </div>
            <button onclick="abrirModal('modalNuevaCondicion')" class="btn-cycsa btn-cycsa-primary">
                <i class="fa-solid fa-plus"></i> Nueva Condición
            </button>
        </div>

        <div style="overflow-x: auto;">
            <table class="tabla-cycsa">
                <thead>
                    <tr>
                        <th>Tipo de Condición</th>
                        <th>Valor Registrado</th>
                        <th style="text-align: right; width: 220px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php 
                    $tieneCondiciones = false;
                    
                    $tiposLabels = [
                        'condicion_pago' => ['label' => 'Condición de Pago', 'icon' => 'fa-credit-card', 'color' => '#3b82f6', 'bg' => '#dbeafe', 'text' => '#1e40af'],
                        'tiempo_entrega' => ['label' => 'Tiempo de Entrega', 'icon' => 'fa-truck-ramp-box', 'color' => '#10b981', 'bg' => '#d1fae5', 'text' => '#065f46'],
                        'vigencia_oferta' => ['label' => 'Vigencia de Oferta', 'icon' => 'fa-calendar-check', 'color' => '#f59e0b', 'bg' => '#fef3c7', 'text' => '#92400e']
                    ];

                    $todasCondiciones = [];
                    foreach ($condiciones_pago as $c) { $c['tipo_key'] = 'condicion_pago'; $todasCondiciones[] = $c; }
                    foreach ($tiempos_entrega as $c) { $c['tipo_key'] = 'tiempo_entrega'; $todasCondiciones[] = $c; }
                    foreach ($vigencias_oferta as $c) { $c['tipo_key'] = 'vigencia_oferta'; $todasCondiciones[] = $c; }

                    usort($todasCondiciones, function($a, $b) {
                        return strcmp($a['tipo_key'], $b['tipo_key']);
                    });

                    if (!empty($todasCondiciones)):
                        $tieneCondiciones = true;
                        foreach ($todasCondiciones as $item):
                            $meta = $tiposLabels[$item['tipo_key']];
                    ?>
                        <tr>
                            <td>
                                <span style="background: <?= $meta['bg'] ?>; color: <?= $meta['text'] ?>; font-size: 11px; font-weight: 700; padding: 4px 10px; border-radius: 12px; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid <?= $meta['icon'] ?>" style="color: <?= $meta['color'] ?>;"></i>
                                    <?= $meta['label'] ?>
                                </span>
                            </td>
                            <td style="font-weight: 500; font-size: 13.5px;"><?= htmlspecialchars($item['valor'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="text-align: right; white-space: nowrap;">
                                <button type="button" class="btn-cycsa btn-cycsa-secondary" onclick="abrirEditarCondicion(<?= $item['id'] ?>, '<?= htmlspecialchars($item['valor'], ENT_QUOTES, 'UTF-8') ?>', '<?= $meta['label'] ?>')">
                                    <i class="fa-solid fa-pen-to-square"></i> Editar
                                </button>
                                <button type="button" class="btn-cycsa btn-cycsa-danger" onclick="eliminarCondicion(<?= $item['id'] ?>, '<?= htmlspecialchars($item['valor'], ENT_QUOTES, 'UTF-8') ?>')">
                                    <i class="fa-solid fa-trash-can"></i> Eliminar
                                </button>
                            </td>
                        </tr>
                    <?php 
                        endforeach; 
                    endif;

                    if (!$tieneCondiciones):
                    ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 40px; color: #94a3b8;"><i class="fa-regular fa-folder-open" style="font-size: 24px; display: block; margin-bottom: 8px;"></i> No se han registrado condiciones comerciales todavía.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php elseif ($tabActual === 'logistica'): ?>
    <!-- SECCIÓN: LOGÍSTICA Y MUESTREO -->
    <div style="background: #e0f2fe; border-left: 4px solid #0284c7; padding: 15px; border-radius: 6px; margin-bottom: 25px; display: flex; gap: 12px; align-items: flex-start;">
        <i class="fa-solid fa-circle-info" style="color: #0284c7; font-size: 18px; margin-top: 2px;"></i>
        <div style="font-size: 13.5px; color: #0369a1; line-height: 1.5;">
            <strong>Recursos Operativos de Muestreo:</strong> Registra los técnicos de laboratorio encargados del muestreo y la flota de vehículos o camiones disponibles. Estos se mostrarán en la programación de rutas de las Órdenes de Servicio en Operaciones.
        </div>
    </div>

    <!-- Panel de Técnicos -->
    <div class="panel-premium">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;"><i class="fa-solid fa-user-gear" style="color: #6366f1; margin-right: 8px;"></i> Técnicos de Muestreo</h3>
                <p style="margin: 3px 0 0 0; font-size: 13px; color: #64748b;">Listado de personal habilitado para la recolección de muestras en campo.</p>
            </div>
            <button onclick="abrirModal('modalNuevoTecnico')" class="btn-cycsa btn-cycsa-primary">
                <i class="fa-solid fa-plus"></i> Registrar Técnico
            </button>
        </div>

        <div style="overflow-x: auto;">
            <table class="tabla-cycsa">
                <thead>
                    <tr>
                        <th>Nombre del Técnico</th>
                        <th>Fecha de Registro</th>
                        <th style="text-align: right; width: 220px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($tecnicos)): ?>
                        <tr>
                            <td colspan="3" style="text-align: center; padding: 40px; color: #94a3b8;"><i class="fa-regular fa-folder-open" style="font-size: 24px; display: block; margin-bottom: 8px;"></i> No se han registrado técnicos todavía.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($tecnicos as $item): ?>
                            <tr>
                                <td style="font-weight: 600; color: #1e293b;"><?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="color: #64748b; font-size: 13px;"><?= date('d/m/Y H:i', strtotime($item['fecha_registro'])) ?></td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <button type="button" class="btn-cycsa btn-cycsa-secondary" onclick="abrirEditarTecnico(<?= $item['id'] ?>, '<?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="fa-solid fa-pen-to-square"></i> Editar
                                    </button>
                                    <button type="button" class="btn-cycsa btn-cycsa-danger" onclick="eliminarTecnico(<?= $item['id'] ?>, '<?= htmlspecialchars($item['nombre'], ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="fa-solid fa-trash-can"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Panel de Vehículos -->
    <div class="panel-premium" style="margin-top: 30px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
            <div>
                <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: #0f172a;"><i class="fa-solid fa-truck" style="color: #ec4899; margin-right: 8px;"></i> Flota de Vehículos / Camiones</h3>
                <p style="margin: 3px 0 0 0; font-size: 13px; color: #64748b;">Vehículos activos para el transporte de personal y muestras.</p>
            </div>
            <button onclick="abrirModal('modalNuevoVehiculo')" class="btn-cycsa btn-cycsa-primary">
                <i class="fa-solid fa-plus"></i> Registrar Vehículo
            </button>
        </div>

        <div style="overflow-x: auto;">
            <table class="tabla-cycsa">
                <thead>
                    <tr>
                        <th>Número de Placa</th>
                        <th>Marca</th>
                        <th>Modelo</th>
                        <th>Fecha de Registro</th>
                        <th style="text-align: right; width: 220px;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($vehiculos)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; padding: 40px; color: #94a3b8;"><i class="fa-regular fa-folder-open" style="font-size: 24px; display: block; margin-bottom: 8px;"></i> No se han registrado vehículos todavía.</td>
                        </tr>
                    <?php else: ?>
                        <?php foreach ($vehiculos as $item): ?>
                            <tr>
                                <td style="font-family: monospace; font-size: 14px; font-weight: 700; color: #0f172a;"><?= htmlspecialchars($item['placa'], ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="font-weight: 500;"><?= htmlspecialchars($item['marca'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="font-weight: 500;"><?= htmlspecialchars($item['modelo'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                                <td style="color: #64748b; font-size: 13px;"><?= date('d/m/Y H:i', strtotime($item['fecha_registro'])) ?></td>
                                <td style="text-align: right; white-space: nowrap;">
                                    <button type="button" class="btn-cycsa btn-cycsa-secondary" onclick="abrirEditarVehiculo(<?= $item['id'] ?>, '<?= htmlspecialchars($item['placa'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($item['marca'] ?: '', ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($item['modelo'] ?: '', ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="fa-solid fa-pen-to-square"></i> Editar
                                    </button>
                                    <button type="button" class="btn-cycsa btn-cycsa-danger" onclick="eliminarVehiculo(<?= $item['id'] ?>, '<?= htmlspecialchars($item['placa'], ENT_QUOTES, 'UTF-8') ?>')">
                                        <i class="fa-solid fa-trash-can"></i> Eliminar
                                    </button>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
<?php endif; ?>

<!-- MODAL: NUEVA CONDICIÓN COMERCIAL -->
<div id="modalNuevaCondicion" class="modal-premium">
    <div class="modal-premium-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-size: 16px; font-weight: 700;"><i class="fa-solid fa-plus-circle" style="color:var(--cycsa-azul);"></i> Agregar Condición Comercial</h3>
            <button onclick="cerrarModal('modalNuevaCondicion')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>
        <form onsubmit="agregarCondicion(event)">
            <div class="form-group">
                <label>Tipo de Condición</label>
                <select id="input-tipo-condicion" required class="form-control">
                    <option value="">-- Seleccione --</option>
                    <option value="condicion_pago">Condición de Pago</option>
                    <option value="tiempo_entrega">Tiempo de Entrega</option>
                    <option value="vigencia_oferta">Vigencia de Oferta</option>
                </select>
            </div>
            <div class="form-group">
                <label>Valor / Descripción de la Opción</label>
                <input type="text" id="input-valor-condicion" required class="form-control" placeholder="Ej: 30 días, 5 días hábiles, etc.">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="cerrarModal('modalNuevaCondicion')" class="btn-cycsa btn-cycsa-secondary">Cancelar</button>
                <button type="submit" class="btn-cycsa btn-cycsa-primary">Guardar Registro</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDITAR CONDICIÓN COMERCIAL -->
<div id="modalEditarCondicion" class="modal-premium">
    <div class="modal-premium-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-size: 16px; font-weight: 700;"><i class="fa-solid fa-pen-to-square" style="color:var(--cycsa-azul);"></i> Editar Condición Comercial</h3>
            <button onclick="cerrarModal('modalEditarCondicion')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>
        <form onsubmit="actualizarCondicion(event)">
            <input type="hidden" id="edit-id-condicion">
            <div class="form-group">
                <label>Tipo de Condición</label>
                <input type="text" id="edit-label-condicion" readonly class="form-control" style="background: #f1f5f9; font-weight: 600;">
            </div>
            <div class="form-group">
                <label>Valor / Descripción de la Opción</label>
                <input type="text" id="edit-valor-condicion" required class="form-control" placeholder="Ej: 30 días, 5 días hábiles, etc.">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="cerrarModal('modalEditarCondicion')" class="btn-cycsa btn-cycsa-secondary">Cancelar</button>
                <button type="submit" class="btn-cycsa btn-cycsa-primary">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: NUEVO TÉCNICO -->
<div id="modalNuevoTecnico" class="modal-premium">
    <div class="modal-premium-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-size: 16px; font-weight: 700;"><i class="fa-solid fa-user-plus" style="color:#6366f1;"></i> Registrar Técnico de Muestreo</h3>
            <button onclick="cerrarModal('modalNuevoTecnico')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>
        <form onsubmit="registrarTecnico(event)">
            <div class="form-group">
                <label>Nombre Completo del Técnico</label>
                <input type="text" id="input-nombre-tecnico" required class="form-control" placeholder="Ej: Juan Antonio Pérez">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="cerrarModal('modalNuevoTecnico')" class="btn-cycsa btn-cycsa-secondary">Cancelar</button>
                <button type="submit" class="btn-cycsa btn-cycsa-primary" style="background:#6366f1;">Guardar Registro</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDITAR TÉCNICO -->
<div id="modalEditarTecnico" class="modal-premium">
    <div class="modal-premium-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-size: 16px; font-weight: 700;"><i class="fa-solid fa-pen-to-square" style="color:#6366f1;"></i> Editar Técnico de Muestreo</h3>
            <button onclick="cerrarModal('modalEditarTecnico')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>
        <form onsubmit="actualizarTecnico(event)">
            <input type="hidden" id="edit-id-tecnico">
            <div class="form-group">
                <label>Nombre Completo del Técnico</label>
                <input type="text" id="edit-nombre-tecnico" required class="form-control" placeholder="Ej: Juan Antonio Pérez">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="cerrarModal('modalEditarTecnico')" class="btn-cycsa btn-cycsa-secondary">Cancelar</button>
                <button type="submit" class="btn-cycsa btn-cycsa-primary" style="background:#6366f1;">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: NUEVO VEHÍCULO -->
<div id="modalNuevoVehiculo" class="modal-premium">
    <div class="modal-premium-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-size: 16px; font-weight: 700;"><i class="fa-solid fa-truck" style="color:#ec4899;"></i> Registrar Vehículo en Flota</h3>
            <button onclick="cerrarModal('modalNuevoVehiculo')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>
        <form onsubmit="registrarVehiculo(event)">
            <div class="form-group">
                <label>Número de Placa (Requerido)</label>
                <input type="text" id="input-placa-vehiculo" required class="form-control" placeholder="Ej: M 345-212">
            </div>
            <div class="form-group">
                <label>Marca del Vehículo</label>
                <input type="text" id="input-marca-vehiculo" class="form-control" placeholder="Ej: Toyota">
            </div>
            <div class="form-group">
                <label>Modelo del Vehículo</label>
                <input type="text" id="input-modelo-vehiculo" class="form-control" placeholder="Ej: Hilux">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="cerrarModal('modalNuevoVehiculo')" class="btn-cycsa btn-cycsa-secondary">Cancelar</button>
                <button type="submit" class="btn-cycsa btn-cycsa-primary" style="background:#ec4899;">Guardar Registro</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL: EDITAR VEHÍCULO -->
<div id="modalEditarVehiculo" class="modal-premium">
    <div class="modal-premium-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: #0f172a; font-size: 16px; font-weight: 700;"><i class="fa-solid fa-pen-to-square" style="color:#ec4899;"></i> Editar Vehículo</h3>
            <button onclick="cerrarModal('modalEditarVehiculo')" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>
        <form onsubmit="actualizarVehiculo(event)">
            <input type="hidden" id="edit-id-vehiculo">
            <div class="form-group">
                <label>Número de Placa (Requerido)</label>
                <input type="text" id="edit-placa-vehiculo" required class="form-control" placeholder="Ej: M 345-212">
            </div>
            <div class="form-group">
                <label>Marca del Vehículo</label>
                <input type="text" id="edit-marca-vehiculo" class="form-control" placeholder="Ej: Toyota">
            </div>
            <div class="form-group">
                <label>Modelo del Vehículo</label>
                <input type="text" id="edit-modelo-vehiculo" class="form-control" placeholder="Ej: Hilux">
            </div>
            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="cerrarModal('modalEditarVehiculo')" class="btn-cycsa btn-cycsa-secondary">Cancelar</button>
                <button type="submit" class="btn-cycsa btn-cycsa-primary" style="background:#ec4899;">Guardar Cambios</button>
            </div>
        </form>
    </div>
</div>

<script>
    const csrfToken = "<?= $_SESSION['csrf_token'] ?>";

    // Modals Helpers
    function abrirModal(id) {
        document.getElementById(id).style.display = 'block';
    }
    
    function cerrarModal(id) {
        document.getElementById(id).style.display = 'none';
    }

    // Close modal clicking outside
    window.onclick = function(event) {
        if (event.target.classList.contains('modal-premium')) {
            event.target.style.display = "none";
        }
    }

    // --- POPULATE FOR EDIT ---
    function abrirEditarCondicion(id, valor, label) {
        document.getElementById('edit-id-condicion').value = id;
        document.getElementById('edit-valor-condicion').value = valor;
        document.getElementById('edit-label-condicion').value = label;
        abrirModal('modalEditarCondicion');
    }

    function abrirEditarTecnico(id, nombre) {
        document.getElementById('edit-id-tecnico').value = id;
        document.getElementById('edit-nombre-tecnico').value = nombre;
        abrirModal('modalEditarTecnico');
    }

    function abrirEditarVehiculo(id, placa, marca, modelo) {
        document.getElementById('edit-id-vehiculo').value = id;
        document.getElementById('edit-placa-vehiculo').value = placa;
        document.getElementById('edit-marca-vehiculo').value = marca;
        document.getElementById('edit-modelo-vehiculo').value = modelo;
        abrirModal('modalEditarVehiculo');
    }

    // --- ACTIONS COMERCIALES ---
    function agregarCondicion(event) {
        event.preventDefault();
        const tipoSelect = document.getElementById('input-tipo-condicion');
        const valorInput = document.getElementById('input-valor-condicion');
        
        const tipo = tipoSelect.value;
        const valor = valorInput.value.trim();
        if (!tipo || !valor) return;

        const form = event.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('tipo', tipo);
        formData.append('valor', valor);

        fetch('/Cycsa/publico/configuracion/agregar-ajax', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.error) {
                alert(data.error);
                return;
            }
            cerrarModal('modalNuevaCondicion');
            window.location.reload();
        })
        .catch(err => {
            btn.disabled = false;
            console.error(err);
            alert('Error de red al agregar la opción.');
        });
    }

    function actualizarCondicion(event) {
        event.preventDefault();
        const id = document.getElementById('edit-id-condicion').value;
        const valor = document.getElementById('edit-valor-condicion').value.trim();
        if (!id || !valor) return;

        const form = event.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('id', id);
        formData.append('valor', valor);

        fetch('/Cycsa/publico/configuracion/actualizar-ajax', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.error) {
                alert(data.error);
                return;
            }
            cerrarModal('modalEditarCondicion');
            window.location.reload();
        })
        .catch(err => {
            btn.disabled = false;
            console.error(err);
            alert('Error de red al actualizar.');
        });
    }

    function eliminarCondicion(id, valor) {
        if (!confirm(`¿Está seguro de que desea eliminar la opción comercial "${valor}"?`)) return;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('id', id);

        fetch('/Cycsa/publico/configuracion/eliminar-ajax', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            window.location.reload();
        })
        .catch(err => {
            console.error(err);
            alert('Error de red al eliminar la opción.');
        });
    }

    // --- ACTIONS TÉCNICOS ---
    function registrarTecnico(event) {
        event.preventDefault();
        const input = document.getElementById('input-nombre-tecnico');
        const nombre = input.value.trim();
        if (!nombre) return;

        const form = event.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('nombre', nombre);

        fetch('/Cycsa/publico/configuracion/agregar-tecnico-ajax', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.error) {
                alert(data.error);
                return;
            }
            cerrarModal('modalNuevoTecnico');
            window.location.reload();
        })
        .catch(err => {
            btn.disabled = false;
            console.error(err);
            alert('Error de red al registrar técnico.');
        });
    }

    function actualizarTecnico(event) {
        event.preventDefault();
        const id = document.getElementById('edit-id-tecnico').value;
        const nombre = document.getElementById('edit-nombre-tecnico').value.trim();
        if (!id || !nombre) return;

        const form = event.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('id', id);
        formData.append('nombre', nombre);

        fetch('/Cycsa/publico/configuracion/actualizar-tecnico-ajax', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.error) {
                alert(data.error);
                return;
            }
            cerrarModal('modalEditarTecnico');
            window.location.reload();
        })
        .catch(err => {
            btn.disabled = false;
            console.error(err);
            alert('Error de red al actualizar.');
        });
    }

    function eliminarTecnico(id, nombre) {
        if (!confirm(`¿Está seguro de que desea eliminar al técnico de muestreo "${nombre}"?`)) return;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('id', id);

        fetch('/Cycsa/publico/configuracion/eliminar-tecnico-ajax', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            window.location.reload();
        })
        .catch(err => {
            console.error(err);
            alert('Error de red al eliminar.');
        });
    }

    // --- ACTIONS VEHÍCULOS ---
    function registrarVehiculo(event) {
        event.preventDefault();
        const placaInput = document.getElementById('input-placa-vehiculo');
        const marcaInput = document.getElementById('input-marca-vehiculo');
        const modeloInput = document.getElementById('input-modelo-vehiculo');
        
        const placa = placaInput.value.trim();
        const marca = marcaInput.value.trim();
        const modelo = modeloInput.value.trim();
        
        if (!placa) return;

        const form = event.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('placa', placa);
        formData.append('marca', marca);
        formData.append('modelo', modelo);

        fetch('/Cycsa/publico/configuracion/agregar-vehiculo-ajax', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.error) {
                alert(data.error);
                return;
            }
            cerrarModal('modalNuevoVehiculo');
            window.location.reload();
        })
        .catch(err => {
            btn.disabled = false;
            console.error(err);
            alert('Error de red al registrar el vehículo.');
        });
    }

    function actualizarVehiculo(event) {
        event.preventDefault();
        const id = document.getElementById('edit-id-vehiculo').value;
        const placa = document.getElementById('edit-placa-vehiculo').value.trim();
        const marca = document.getElementById('edit-marca-vehiculo').value.trim();
        const modelo = document.getElementById('edit-modelo-vehiculo').value.trim();
        if (!id || !placa) return;

        const form = event.target;
        const btn = form.querySelector('button[type="submit"]');
        btn.disabled = true;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('id', id);
        formData.append('placa', placa);
        formData.append('marca', marca);
        formData.append('modelo', modelo);

        fetch('/Cycsa/publico/configuracion/actualizar-vehiculo-ajax', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            btn.disabled = false;
            if (data.error) {
                alert(data.error);
                return;
            }
            cerrarModal('modalEditarVehiculo');
            window.location.reload();
        })
        .catch(err => {
            btn.disabled = false;
            console.error(err);
            alert('Error de red al actualizar.');
        });
    }

    function eliminarVehiculo(id, placa) {
        if (!confirm(`¿Está seguro de que desea eliminar el vehículo con placa "${placa}" de la flota?`)) return;

        const formData = new FormData();
        formData.append('csrf_token', csrfToken);
        formData.append('id', id);

        fetch('/Cycsa/publico/configuracion/eliminar-vehiculo-ajax', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(data => {
            if (data.error) {
                alert(data.error);
                return;
            }
            window.location.reload();
        })
        .catch(err => {
            console.error(err);
            alert('Error de red al eliminar.');
        });
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }
</script>


<?php
$bitacora_modulo_nombre = 'Configuración';
include dirname(__DIR__, 3) . '/Views/parciales/bitacora_modulo.php';
?>
