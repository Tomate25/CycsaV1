<?php
// Vista principal - Listado de Órdenes de Servicio (CYCSA ERP Premium Style)
?>
<style>
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13.5px; background: white; border-radius: 8px; overflow: hidden; }
    .tabla-cycsa th { background-color: #f8fafc; color: #475569; padding: 14px 18px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
    .tabla-cycsa td { padding: 14px 18px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #1e293b; }
    .tabla-cycsa tbody tr:hover { background-color: #f8fafc; }
    
    .badge-premium { padding: 5px 12px; border-radius: 12px; font-size: 11px; font-weight: 700; display: inline-flex; align-items: center; gap: 6px; }
    .badge-muestreo-no { background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
    .badge-muestreo-si { background-color: #e0f2fe; color: #0369a1; border: 1px solid #7dd3fc; }
    .badge-estado-pendiente { background-color: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .badge-estado-recepcion { background-color: #dcfce7; color: #15803d; border: 1px solid #86efac; }
    .badge-estado-completado { background-color: #dbeafe; color: #1d4ed8; border: 1px solid #93c5fd; }

    .btn-cycsa { display: inline-flex; align-items: center; gap: 6px; border: 1px solid transparent; padding: 8px 14px; border-radius: 6px; font-size: 12.5px; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .btn-cycsa-primary { background: var(--cycsa-azul); color: white; font-size: 13.5px; padding: 10px 18px; }
    .btn-cycsa-primary:hover { background: #0c2766; color: white; transform: translateY(-1px); }
    .btn-cycsa-secondary { background: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .btn-cycsa-secondary:hover { background: #cbd5e1; color: #0f172a; }
    .btn-cycsa-warning { background: #f59e0b; color: white; }
    .btn-cycsa-warning:hover { background: #d97706; color: white; }
    .btn-cycsa-success { background: #10b981; color: white; }
    .btn-cycsa-success:hover { background: #059669; color: white; }

    .search-bar-premium { display: flex; gap: 15px; align-items: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px; flex-wrap: wrap; }
    .form-control-cycsa { padding: 9px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 13.5px; transition: border-color 0.2s; width: 100%; box-sizing: border-box; }
    .form-control-cycsa:focus { outline: none; border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08); }
</style>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.04); margin-bottom: 30px;">
    
    <!-- CABECERA -->
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-size: 22px; font-family: 'Outfit', sans-serif; font-weight: 700; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-file-contract" style="color: var(--cycsa-azul);"></i> Órdenes de Servicio
            </h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 13.5px; margin-bottom: 0;">
                Gestión, consulta e impresión del formato oficial <strong>CYCSA-RG-FM-39 V1</strong>
            </p>
        </div>
    </div>

    <!-- BUSCADOR -->
    <form method="GET" action="/Cycsa/publico/ordenes-servicio" class="search-bar-premium">
        <div style="flex: 1; min-width: 250px;">
            <input type="text" name="q" placeholder="Buscar por código O/S, cotización, cliente o proyecto..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control-cycsa">
        </div>
        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn-cycsa btn-cycsa-primary"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
            <?php if(!empty($busqueda)): ?>
                <a href="/Cycsa/publico/ordenes-servicio" class="btn-cycsa btn-cycsa-secondary"><i class="fa-solid fa-xmark"></i> Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- TABLA DE REGISTROS -->
    <div style="overflow-x: auto;">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th style="width: 160px;">Código O/S</th>
                    <th style="width: 140px;">Cotización</th>
                    <th>Cliente / Proyecto</th>
                    <th style="width: 120px;">Fecha Emisión</th>
                    <th style="width: 160px;">Muestreo Campo</th>
                    <th style="width: 150px;">Estado</th>
                    <th style="text-align: right; width: 220px;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($ordenes)): ?>
                    <?php foreach ($ordenes as $os): ?>
                        <tr>
                            <td style="font-family: monospace; font-weight: 700; color: var(--cycsa-azul); font-size: 14px;">
                                <?= htmlspecialchars($os['codigo_os']) ?>
                            </td>
                            <td>
                                <span style="background: #f1f5f9; color: #475569; padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 12px; border: 1px solid #e2e8f0;">
                                    <?= htmlspecialchars($os['cotizacion_codigo'] ?? 'N/A') ?>
                                </span>
                            </td>
                            <td>
                                <div style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($os['cliente_nombre']) ?></div>
                                <div style="color: #64748b; font-size: 12px; margin-top: 2px;"><?= htmlspecialchars($os['nombre_proyecto'] ?? '') ?></div>
                            </td>
                            <td style="color: #475569; font-weight: 500;">
                                <?= date('d/m/Y', strtotime($os['fecha_emision'])) ?>
                            </td>
                            <td>
                                <?php if (!empty($os['requiere_muestreo'])): ?>
                                    <span class="badge-premium badge-muestreo-si">
                                        <i class="fa-solid fa-truck-pickup"></i> Sí (En campo)
                                    </span>
                                    <?php if (!empty($os['tecnico_nombre'])): ?>
                                        <div style="color: #64748b; font-size: 11px; margin-top: 3px;">Téc: <?= htmlspecialchars($os['tecnico_nombre']) ?></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="badge-premium badge-muestreo-no">
                                        <i class="fa-solid fa-flask"></i> No (En laboratorio)
                                    </span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php
                                $badgeClass = 'badge-muestreo-no';
                                if ($os['estado'] === 'Pendiente de Muestreo') $badgeClass = 'badge-estado-pendiente';
                                elseif ($os['estado'] === 'Estado 1: Recepcion') $badgeClass = 'badge-estado-recepcion';
                                elseif ($os['estado'] === 'Muestreo Completado') $badgeClass = 'badge-estado-completado';
                                ?>
                                <span class="badge-premium <?= $badgeClass ?>">
                                    <?= htmlspecialchars($os['estado']) ?>
                                </span>
                            </td>
                            <td style="text-align: right;">
                                <div style="display: flex; gap: 6px; justify-content: flex-end;">
                                    <a href="/Cycsa/publico/ordenes-servicio/detalle?id=<?= $os['id'] ?>" class="btn-cycsa btn-cycsa-secondary" title="Ver e Imprimir Documento Oficial CYCSA-RG-FM-39 V1" style="padding: 6px 12px; font-size: 12px;">
                                        <i class="fa-solid fa-print" style="color: var(--cycsa-azul);"></i> Ver / Imprimir O/S
                                    </a>
                                    <?php if (!empty($os['requiere_muestreo']) && $os['estado'] === 'Pendiente de Muestreo'): ?>
                                        <a href="/Cycsa/publico/ordenes-servicio/programar-muestreo?id=<?= $os['id'] ?>" class="btn-cycsa btn-cycsa-warning" title="Programar Logística de Muestreo" style="padding: 6px 10px; font-size: 12px;">
                                            <i class="fa-solid fa-truck-pickup"></i> Logística
                                        </a>
                                    <?php endif; ?>
                                    <a href="/Cycsa/publico/hojas-servicio?id_os=<?= $os['id'] ?>" class="btn-cycsa btn-cycsa-success" title="Ir a la Hoja de Servicio CYCSA-RT-FM-13" style="padding: 6px 12px; font-size: 12px;">
                                        <i class="fa-solid fa-file-circle-check"></i> Hoja RT-FM-13
                                    </a>
                                </div>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td colspan="7" style="text-align: center; color: #64748b; padding: 30px;">
                            <i class="fa-solid fa-folder-open" style="font-size: 24px; margin-bottom: 8px; display: block; opacity: 0.5;"></i>
                            No se encontraron Órdenes de Servicio registradas.
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
