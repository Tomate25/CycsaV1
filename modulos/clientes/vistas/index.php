<style>
    /* Premium Table & Layout Styling */
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 14px; background: white; border-radius: 8px; overflow: hidden; }
    .tabla-cycsa th { background-color: #f8fafc; color: #475569; padding: 14px 18px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
    .tabla-cycsa td { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #1e293b; }
    .tabla-cycsa tbody tr.row-main:hover { background-color: #f8fafc; cursor: pointer; }
    
    .badge-premium { padding: 4px 10px; border-radius: 12px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
    .badge-activo { background-color: #d1fae5; color: #065f46; }
    .badge-inactivo { background-color: #fee2e2; color: #991b1b; }
    
    .badge-tipo { background-color: #e0f2fe; color: #0369a1; }
    .badge-vendedor { background-color: #f1f5f9; color: #475569; font-weight: 500; }
    .badge-credito { background-color: #fef3c7; color: #92400e; }

    .btn-cycsa { display: inline-flex; align-items: center; gap: 6px; border: 1px solid transparent; padding: 8px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .btn-cycsa-primary { background: var(--cycsa-azul); color: white; font-size: 13.5px; padding: 10px 18px; }
    .btn-cycsa-primary:hover { background: #0c2766; transform: translateY(-1px); }
    .btn-cycsa-secondary { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .btn-cycsa-secondary:hover { background: #cbd5e1; color: #0f172a; }

    /* Collapsible Row CSS */
    .detail-row { display: none; background-color: #f8fafc; }
    .detail-container { padding: 20px 30px; border-bottom: 1px solid #edf2f7; animation: slideDown 0.2s ease-out; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    
    .detail-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 25px; }
    .detail-card { background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
    .detail-title { font-size: 12px; font-weight: 700; color: var(--cycsa-azul); text-transform: uppercase; letter-spacing: 0.5px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; margin-top: 0; margin-bottom: 12px; display: flex; align-items: center; gap: 8px; }
    .detail-item { font-size: 13px; margin-bottom: 8px; display: flex; justify-content: space-between; }
    .detail-label { color: #64748b; font-weight: 500; }
    .detail-value { color: #1e293b; font-weight: 600; text-align: right; }
    
    .search-bar-premium { display: flex; gap: 15px; align-items: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px; flex-wrap: wrap; }
    .form-control { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 13.5px; transition: border-color 0.2s; width: 100%; box-sizing: border-box; }
    .form-control:focus { outline: none; border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08); }
</style>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
    
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-size: 22px; font-family: 'Outfit', sans-serif; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-address-book" style="color: var(--cycsa-azul);"></i> Cartera de Clientes
            </h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 13.5px;">Administración y consulta del perfil de clientes comerciales y fiscales.</p>
        </div>
        
        <?php if (tienePermiso('clientes', 'crear_editar')): ?>
        <a href="/Cycsa/publico/clientes/crear" class="btn-cycsa btn-cycsa-primary">
            <i class="fa-solid fa-plus"></i> Registrar Cliente
        </a>
        <?php endif; ?>
    </div>

    <!-- Barra de Filtros / Buscador -->
    <form method="GET" action="/Cycsa/publico/clientes" class="search-bar-premium">
        <div style="flex: 1; min-width: 250px;">
            <input type="text" name="q" placeholder="Buscar por nombre, identificación, vendedor, clasificación..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control">
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn-cycsa btn-cycsa-primary" style="padding: 8px 16px;"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
            <?php if(!empty($busqueda)): ?>
                <a href="/Cycsa/publico/clientes" class="btn-cycsa btn-cycsa-secondary"><i class="fa-solid fa-xmark"></i> Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <div style="overflow-x: auto;">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th style="width: 60px;">ID</th>
                    <th>Nombre / Razón Social</th>
                    <th>RUC / Cédula</th>
                    <th>Tipo</th>
                    <th>Clasificación</th>
                    <th>Vendedor</th>
                    <th>Límite Crédito</th>
                    <th>Estado</th>
                    <th style="text-align: right; width: 130px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($clientes as $cliente): ?>
                <tr class="row-main" onclick="toggleDetailRow(<?= $cliente['id'] ?>)">
                    <td style="font-family: monospace; font-weight: 700; color: #64748b;"><?= $cliente['id'] ?></td>
                    <td style="font-weight: 600; color: #0f172a;">
                        <?= htmlspecialchars($cliente['nombre_razon_social'], ENT_QUOTES, 'UTF-8') ?>
                        <span style="font-size: 10px; color: #2563eb; border: 1px solid #bfdbfe; background-color: #eff6ff; padding: 1px 6px; border-radius: 3px; font-weight: 600; margin-left: 8px; text-transform: uppercase; display: inline-block;">Ver Ficha</span>
                    </td>
                    <td style="font-family: monospace;"><?= htmlspecialchars($cliente['identificacion'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="badge-premium badge-tipo">
                            <i class="fa-solid <?= $cliente['tipo_cliente'] === 'Natural' ? 'fa-user' : 'fa-building' ?>" style="font-size: 10px;"></i>
                            <?= htmlspecialchars($cliente['tipo_cliente'] ?: 'Jurídico', ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td style="font-weight: 500; font-size: 13px;"><?= htmlspecialchars($cliente['clasificacion'] ?: '-', ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <?php if ($cliente['vendedor']): ?>
                            <span class="badge-premium badge-vendedor">
                                <i class="fa-solid fa-user-tie" style="font-size: 10px; color: #64748b;"></i>
                                <?= htmlspecialchars(str_replace('CYCSA- FR12-003--', '', $cliente['vendedor']), ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        <?php else: ?>
                            -
                        <?php endif; ?>
                    </td>
                    <td>
                        <?php if ($cliente['limite_credito'] > 0): ?>
                            <span class="badge-premium badge-credito">
                                <?= $cliente['tipo_moneda'] == 2 ? '$' : 'C$' ?> <?= number_format($cliente['limite_credito'], 2) ?>
                            </span>
                        <?php else: ?>
                            <span style="color: #94a3b8; font-size: 13px;">Sin Crédito</span>
                        <?php endif; ?>
                    </td>
                    <td>
                        <span class="badge-premium <?= $cliente['activo'] == 1 ? 'badge-activo' : 'badge-inactivo' ?>">
                            <?= $cliente['activo'] == 1 ? 'Activo' : 'Inactivo' ?>
                        </span>
                    </td>
                    <td style="text-align: right;" onclick="event.stopPropagation();">
                        <?php if (tienePermiso('clientes', 'crear_editar')): ?>
                        <a href="/Cycsa/publico/clientes/editar?id=<?= $cliente['id'] ?>" class="btn-cycsa btn-cycsa-secondary" style="padding: 6px 12px; font-size: 12px;" title="Editar Ficha">
                            <i class="fa-solid fa-pen-to-square"></i> Editar
                        </a>
                        <?php endif; ?>
                    </td>
                </tr>
                
                <!-- Fila de Detalles Colapsable -->
                <tr id="detail-row-<?= $cliente['id'] ?>" class="detail-row">
                    <td colspan="9">
                        <div class="detail-container">
                            <div class="detail-grid">
                                
                                <!-- Card 1: Perfil Fiscal y General -->
                                <div class="detail-card">
                                    <h4 class="detail-title"><i class="fa-solid fa-id-card"></i> Datos Generales y Fiscales</h4>
                                    <div class="detail-item">
                                        <span class="detail-label">Código Cliente:</span>
                                        <span class="detail-value"><?= htmlspecialchars($cliente['codigo_cliente'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Sucursal Sede:</span>
                                        <span class="detail-value"><?= htmlspecialchars($cliente['sucursal_sede'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Clasificación:</span>
                                        <span class="detail-value"><?= htmlspecialchars($cliente['clasificacion'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Sub Clasificación:</span>
                                        <span class="detail-value"><?= htmlspecialchars($cliente['sub_clasificacion'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Cédula:</span>
                                        <span class="detail-value"><?= htmlspecialchars($cliente['numero_cedula'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">RUC:</span>
                                        <span class="detail-value"><?= htmlspecialchars($cliente['numero_ruc'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                </div>

                                <!-- Card 2: Condiciones y Crédito -->
                                <div class="detail-card">
                                    <h4 class="detail-title"><i class="fa-solid fa-coins"></i> Facturación y Crédito</h4>
                                    <div class="detail-item">
                                        <span class="detail-label">Cuenta CXC:</span>
                                        <span class="detail-value" style="font-size: 11.5px;"><?= htmlspecialchars($cliente['cuenta_cxc'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Cuenta CXP:</span>
                                        <span class="detail-value" style="font-size: 11.5px;"><?= htmlspecialchars($cliente['cuenta_cxp'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Exonerado IVA:</span>
                                        <span class="detail-value"><?= $cliente['exonerado_impuestos'] == 1 ? 'Sí' : 'No' ?></span>
                                    </div>
                                    <?php if ($cliente['exonerado_impuestos'] == 1): ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Cuenta Exonerados:</span>
                                        <span class="detail-value" style="font-size: 11.5px;"><?= htmlspecialchars($cliente['cuenta_ingresos_exonerados'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <div class="detail-item">
                                        <span class="detail-label">Exportación:</span>
                                        <span class="detail-value"><?= $cliente['exportacion'] == 1 ? 'Sí' : 'No' ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Moneda Predeterminada:</span>
                                        <span class="detail-value"><?= $cliente['tipo_moneda'] == 2 ? 'Dólares ($)' : 'Córdobas (C$)' ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Límite Crédito:</span>
                                        <span class="detail-value"><?= $cliente['tipo_moneda'] == 2 ? '$' : 'C$' ?> <?= number_format($cliente['limite_credito'], 2) ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Días de Crédito:</span>
                                        <span class="detail-value"><?= $cliente['dias_credito'] ?> días</span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Prórroga de Crédito:</span>
                                        <span class="detail-value"><?= $cliente['activar_prorroga_credito'] == 1 ? 'Activa' : 'Inactiva' ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Facturas Vencidas Permitidas:</span>
                                        <span class="detail-value"><?= $cliente['facturas_vencidas_permitidas'] ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Descuento Automático:</span>
                                        <span class="detail-value"><?= $cliente['descuento_automatico'] == 1 ? 'Sí (' . $cliente['porcentaje_descuento'] . '%)' : 'No' ?></span>
                                    </div>
                                </div>

                                <!-- Card 3: Datos de Contacto y Contacto POS -->
                                <div class="detail-card">
                                    <h4 class="detail-title"><i class="fa-solid fa-address-card"></i> Contacto y Notas</h4>
                                    <div class="detail-item">
                                        <span class="detail-label">Contacto (General):</span>
                                        <span class="detail-value"><?= htmlspecialchars($cliente['contacto'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Teléfono:</span>
                                        <span class="detail-value"><?= htmlspecialchars($cliente['telefono'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Correo Principal:</span>
                                        <span class="detail-value" style="font-size: 12px;"><?= htmlspecialchars($cliente['email'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Contacto Nombre:</span>
                                        <span class="detail-value"><?= htmlspecialchars($cliente['contacto_nombre'] ?: '-', ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars($cliente['contacto_apellido'] ?: '', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Cargo Contacto:</span>
                                        <span class="detail-value"><?= htmlspecialchars($cliente['contacto_cargo'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Correo Contacto:</span>
                                        <span class="detail-value" style="font-size: 12px;"><?= htmlspecialchars($cliente['contacto_correo'] ?: '-', ENT_QUOTES, 'UTF-8') ?></span>
                                    </div>
                                    <div class="detail-item">
                                        <span class="detail-label">Facturación Electrónica Correo:</span>
                                        <span class="detail-value"><?= $cliente['facturacion_correo'] == 1 ? 'Activada' : 'Desactivada' ?></span>
                                    </div>
                                </div>

                            </div>
                            
                            <!-- Address & Notes Bottom Banner -->
                            <div style="margin-top: 20px; padding-top: 15px; border-top: 1px solid #edf2f7; display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div>
                                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Dirección Física:</span>
                                    <div style="font-size: 13px; color: #1e293b; line-height: 1.5; font-weight: 500;">
                                        <?= htmlspecialchars($cliente['direccion'] ?: 'No registrada.', ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                                <div>
                                    <span style="font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; display: block; margin-bottom: 4px;">Notas Internas:</span>
                                    <div style="font-size: 13px; color: #64748b; font-style: italic; line-height: 1.5;">
                                        <?= htmlspecialchars($cliente['notes'] ?? ($cliente['notas'] ?? 'Sin observaciones.'), ENT_QUOTES, 'UTF-8') ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($clientes)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px; color: #94a3b8;">
                        <i class="fa-regular fa-folder-open" style="font-size: 24px; display: block; margin-bottom: 8px;"></i>
                        No se encontraron clientes registrados en la cartera.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    function toggleDetailRow(clienteId) {
        const detailRow = document.getElementById('detail-row-' + clienteId);
        if (detailRow.style.display === 'table-row') {
            detailRow.style.display = 'none';
        } else {
            // Close other details
            document.querySelectorAll('.detail-row').forEach(row => {
                row.style.display = 'none';
            });
            detailRow.style.display = 'table-row';
        }
    }
</script>

<?php
$bitacora_modulo_nombre = 'Clientes';
include __DIR__ . '/../../../plantillas/parciales/bitacora_modulo.php';
?>
