<?php
$modulosAmigables = [
    'cotizaciones' => 'Cotizaciones',
    'usuarios' => 'Usuarios',
    'clientes' => 'Clientes',
    'productos' => 'Catálogo Ensayos'
];

$accionesAmigables = [
    'crear' => 'Creación',
    'editar' => 'Edición',
    'actualizar' => 'Edición',
    'eliminar' => 'Eliminación',
    'desactivar' => 'Desactivación',
    'login' => 'Inicio Sesión',
    'login_failed' => 'Intento Fallido',
    'logout' => 'Cierre Sesión',
    'enviar_revision' => 'Envío a Revisión',
    'aprobar_gerencia' => 'Aprobación Gerencia',
    'devolver_gerencia' => 'Devolución Gerencia',
    'enviar_cliente' => 'Envío a Cliente',
    'volver_enviar_rechazada' => 'Re-envío (V. Nueva)',
    'aprobar_cliente' => 'Aprobado por Cliente',
    'rechazar_cliente' => 'Rechazado por Cliente',
    'aprobar_admin_cliente' => 'Aprobado por Admin',
    'rechazar_admin_cliente' => 'Rechazado por Admin',
    'editar_reenviar' => 'Corregido y Re-enviado'
];

/**
 * LIMS Lógica Visibilidad: Convierte descripciones técnicas con ID de BD (ej: 'O/S ID 1')
 * a formatos legibles amigables con el código oficial (ej: 'Orden de Servicio OS-2026-001 (Cliente)').
 */
function formatearDescripcionBitacora(array $log): string {
    $desc = $log['descripcion'];
    
    // Convertir O/S ID X o Orden de Servicio ID X
    if (preg_match('/(Orden de Servicio|O\/S)\s+ID\s+(\d+)/i', $desc, $matches)) {
        $idOS = (int)$matches[2];
        static $osCache = [];
        if ($idOS > 0) {
            if (!isset($osCache[$idOS])) {
                try {
                    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                    $stmt = $db->prepare("SELECT codigo_os, cliente_nombre FROM ordenes_servicio WHERE id = :id LIMIT 1");
                    $stmt->execute(['id' => $idOS]);
                    $osCache[$idOS] = $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
                } catch (\Exception $e) {
                    $osCache[$idOS] = false;
                }
            }
            if ($osCache[$idOS]) {
                $codigoOS = $osCache[$idOS]['codigo_os'];
                $clienteOS = !empty($osCache[$idOS]['cliente_nombre']) ? ' (Cliente: ' . $osCache[$idOS]['cliente_nombre'] . ')' : '';
                $desc = preg_replace('/(Orden de Servicio|O\/S)\s+ID\s+\d+/i', 'Orden de Servicio ' . $codigoOS . $clienteOS, $desc);
            }
        }
    }

    // Convertir Cotización ID X
    if (preg_match('/(Cotización|Cotizacion)\s+ID\s+(\d+)/i', $desc, $matches)) {
        $idCot = (int)$matches[2];
        static $cotCache = [];
        if ($idCot > 0) {
            if (!isset($cotCache[$idCot])) {
                try {
                    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                    $stmt = $db->prepare("SELECT codigo_cotizacion, cliente_nombre FROM cotizaciones WHERE id = :id LIMIT 1");
                    $stmt->execute(['id' => $idCot]);
                    $cotCache[$idCot] = $stmt->fetch(PDO::FETCH_ASSOC) ?: false;
                } catch (\Exception $e) {
                    $cotCache[$idCot] = false;
                }
            }
            if ($cotCache[$idCot]) {
                $codigoCot = $cotCache[$idCot]['codigo_cotizacion'];
                $clienteCot = !empty($cotCache[$idCot]['cliente_nombre']) ? ' (Cliente: ' . $cotCache[$idCot]['cliente_nombre'] . ')' : '';
                $desc = preg_replace('/(Cotización|Cotizacion)\s+ID\s+\d+/i', 'Cotización ' . $codigoCot . $clienteCot, $desc);
            }
        }
    }

    return $desc;
}
?>
<style>
    /* Premium Stats Cards CSS */
    .stats-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px; }
    .stat-card { background: white; padding: 20px; border-radius: 8px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.02), 0 2px 4px -1px rgba(0, 0, 0, 0.02); transition: transform 0.2s, box-shadow 0.2s; }
    .stat-card:hover { transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.05), 0 4px 6px -2px rgba(0, 0, 0, 0.05); }
    .stat-icon { width: 46px; height: 46px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 18px; }
    .stat-icon.blue { background-color: #eff6ff; color: #2563eb; }
    .stat-icon.green { background-color: #ecfdf5; color: #059669; }
    .stat-icon.purple { background-color: #faf5ff; color: #7c3aed; }
    .stat-icon.red { background-color: #fef2f2; color: #dc2626; }
    .stat-info { display: flex; flex-direction: column; }
    .stat-value { font-size: 24px; font-weight: 700; color: #0f172a; line-height: 1; margin-bottom: 4px; font-family: 'Outfit', sans-serif; }
    .stat-label { font-size: 11px; color: #64748b; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }

    /* Collapsible Row Detail CSS */
    .row-clickable { cursor: pointer; transition: background 0.15s; }
    .row-clickable:hover { background-color: #f8fafc !important; }
    .detail-row { display: none; background-color: #f8fafc; }
    .detail-container { padding: 15px 25px; border-bottom: 1px solid #edf2f7; animation: slideDown 0.2s ease-out; }
    @keyframes slideDown { from { opacity: 0; transform: translateY(-5px); } to { opacity: 1; transform: translateY(0); } }
    .detail-card { background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); display: grid; grid-template-columns: 2fr 1fr; gap: 20px; }
    .detail-left { display: flex; flex-direction: column; gap: 12px; }
    .detail-right { border-left: 1px solid #edf2f7; padding-left: 20px; display: flex; flex-direction: column; gap: 12px; }
    .detail-item { font-size: 13px; }
    .detail-label { font-weight: 700; color: #64748b; font-size: 10.5px; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 3px; }
    .detail-value { color: #1e293b; font-weight: 500; }
    
    .btn-detail-link { background-color: #eff6ff; color: #2563eb; padding: 6px 12px; border-radius: 4px; font-size: 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; transition: background 0.2s; border: 1px solid #bfdbfe; width: fit-content; margin-top: 5px; }
    .btn-detail-link:hover { background-color: #dbeafe; }

    /* Search Bar & Table */
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .tabla-cycsa th { background-color: #f8fafc; color: #475569; padding: 12px 15px; text-align: left; font-weight: 700; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
    .tabla-cycsa td { padding: 12px 15px; border-bottom: 1px solid #edf2f7; vertical-align: middle; color: #333; font-size: 13.5px; }
    
    .badge-modulo { background-color: #e0f2fe; color: #0369a1; padding: 4px 10px; border-radius: 12px; font-size: 10.5px; font-weight: 600; text-transform: uppercase; display: inline-block; }
    .badge-accion { background-color: #f1f5f9; color: #334155; padding: 4px 10px; border-radius: 12px; font-size: 10.5px; font-weight: 600; border: 1px solid #cbd5e1; display: inline-flex; align-items: center; gap: 4px; }
    
    .filtro-barra { display: flex; gap: 15px; align-items: center; background: #f8fafc; padding: 15px; border-radius: 8px; border: 1px solid #e2e8f0; margin-bottom: 20px; flex-wrap: wrap; }
    .filtro-grupo { display: flex; flex-direction: column; gap: 5px; }
    .filtro-label { font-size: 11px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-control { padding: 8px 12px; border: 1px solid #cbd5e1; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 13.5px; transition: border-color 0.2s; }
    .form-control:focus { outline: none; border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08); }
    
    .btn-premium { background: var(--cycsa-azul); color: white; border: none; padding: 9px 18px; border-radius: 4px; cursor: pointer; font-weight: 600; font-size: 13px; font-family: 'Inter', sans-serif; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; transition: background 0.2s; }
    .btn-premium:hover { background: var(--cycsa-azul-hover); }
    .btn-secondary { background: #64748b; color: white; }
    .btn-secondary:hover { background: #475569; }

    .truncate-desc { max-width: 350px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; display: inline-block; vertical-align: middle; }
    .info-badge-click { font-size: 10px; color: #2563eb; border: 1px solid #bfdbfe; background-color: #eff6ff; padding: 1px 6px; border-radius: 3px; font-weight: 600; margin-left: 8px; display: inline-block; vertical-align: middle; text-transform: uppercase; letter-spacing: 0.2px; }

    @media(max-width: 768px) {
        .detail-card { grid-template-columns: 1fr; gap: 15px; }
        .detail-right { border-left: none; padding-left: 0; }
        .truncate-desc { max-width: 200px; }
    }
</style>

<!-- KPI Metrics Section -->
<div class="stats-container">
    <div class="stat-card">
        <div class="stat-icon blue">
            <i class="fa-solid fa-list-check"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['total']) ?></span>
            <span class="stat-label">Movimientos totales</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon green">
            <i class="fa-solid fa-calendar-day"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['hoy']) ?></span>
            <span class="stat-label">Actividad hoy</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon purple">
            <i class="fa-solid fa-users-gear"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['usuarios_activos']) ?></span>
            <span class="stat-label">Usuarios activos</span>
        </div>
    </div>
    <div class="stat-card">
        <div class="stat-icon red">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>
        <div class="stat-info">
            <span class="stat-value"><?= number_format($stats['criticos']) ?></span>
            <span class="stat-label">Eventos Críticos</span>
        </div>
    </div>
</div>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-size: 20px; font-family: 'Outfit', sans-serif; font-weight: 700; display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-shield-halved" style="color: var(--cycsa-azul);"></i> Bitácora de Auditoría</h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 13.5px;">Supervise en tiempo real las operaciones y el historial de cambios de datos.</p>
        </div>
    </div>

    <!-- Barra de Filtros -->
    <form method="GET" action="/Cycsa/publico/panel/bitacora" class="filtro-barra">
        <div class="filtro-grupo" style="flex: 1; min-width: 200px;">
            <label class="filtro-label">Buscar término</label>
            <input type="text" name="q" value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por descripción, acción, IP..." class="form-control">
        </div>
        
        <div class="filtro-grupo" style="width: 180px;">
            <label class="filtro-label">Módulo</label>
            <select name="modulo" class="form-control">
                <option value="">Todos</option>
                <?php foreach ($modulos_disponibles as $mod): 
                    $modLabel = $modulosAmigables[$mod] ?? ucwords(str_replace('_', ' ', $mod));
                ?>
                    <option value="<?= $mod ?>" <?= ($modulo_seleccionado === $mod) ? 'selected' : '' ?>><?= htmlspecialchars($modLabel, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtro-grupo" style="width: 180px;">
            <label class="filtro-label">Usuario</label>
            <select name="usuario" class="form-control">
                <option value="">Todos</option>
                <?php foreach ($usuarios_disponibles as $usr): ?>
                    <option value="<?= $usr['id'] ?>" <?= ($usuario_seleccionado == $usr['id']) ? 'selected' : '' ?>><?= htmlspecialchars($usr['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="filtro-grupo" style="display: flex; flex-direction: row; gap: 10px; margin-top: auto; padding-top: 5px;">
            <button type="submit" class="btn-premium"><i class="fa-solid fa-filter"></i> Filtrar</button>
            <a href="/Cycsa/publico/panel/bitacora" class="btn-premium btn-secondary"><i class="fa-solid fa-arrows-rotate"></i> Limpiar</a>
        </div>
    </form>

    <!-- Table of logs -->
    <div style="overflow-x: auto;">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th style="width: 140px;">Fecha y Hora</th>
                    <th style="width: 150px;">Usuario</th>
                    <th style="width: 110px;">Módulo</th>
                    <th style="width: 130px;">Acción</th>
                    <th>Descripción</th>
                    <th style="width: 80px;">IP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($logs as $log): ?>
                    <!-- Main row (truncated info, clickable) -->
                    <tr class="row-clickable" onclick="toggleDetailRow(<?= $log['id'] ?>)">
                        <td style="color: #475569; font-weight: 500;"><?= date('d/m/Y h:i A', strtotime($log['fecha_creacion'])) ?></td>
                        <td style="font-weight: 600; color: #1e293b;">
                            <?= htmlspecialchars($log['usuario_nombre'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <?php
                            $moduloLimpio = $modulosAmigables[$log['modulo']] ?? ucwords(str_replace('_', ' ', $log['modulo']));
                            ?>
                            <span class="badge-modulo"><?= htmlspecialchars($moduloLimpio, ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td>
                            <?php
                            $actionIcon = 'fa-arrow-pointer';
                            $actionColor = '#475569';
                            $action = $log['accion'];
                            if (strpos($action, 'crear') !== false) {
                                $actionIcon = 'fa-plus-circle';
                                $actionColor = '#059669';
                            } elseif (strpos($action, 'editar') !== false || strpos($action, 'actualizar') !== false) {
                                $actionIcon = 'fa-pen-to-square';
                                $actionColor = '#d97706';
                            } elseif (strpos($action, 'eliminar') !== false || strpos($action, 'desactivar') !== false || strpos($action, 'deactivate') !== false) {
                                $actionIcon = 'fa-trash-can';
                                $actionColor = '#dc2626';
                            } elseif (strpos($action, 'login') !== false) {
                                $actionIcon = 'fa-right-to-bracket';
                                $actionColor = '#2563eb';
                            } elseif (strpos($action, 'logout') !== false) {
                                $actionIcon = 'fa-right-from-bracket';
                                $actionColor = '#64748b';
                            } elseif (strpos($action, 'aprobar') !== false) {
                                $actionIcon = 'fa-circle-check';
                                $actionColor = '#10b981';
                            } elseif (strpos($action, 'rechazar') !== false || strpos($action, 'observar') !== false || strpos($action, 'devolver') !== false) {
                                $actionIcon = 'fa-circle-xmark';
                                $actionColor = '#dc2626';
                            }

                            $accionLimpia = $accionesAmigables[$action] ?? ucwords(str_replace('_', ' ', $action));
                            ?>
                            <span class="badge-accion" style="border-left: 3px solid <?= $actionColor ?>;">
                                <i class="fa-solid <?= $actionIcon ?>" style="color: <?= $actionColor ?>; font-size: 10px;"></i>
                                <?= htmlspecialchars($accionLimpia, ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <?php $descFormateada = formatearDescripcionBitacora($log); ?>
                        <td style="color: #334155; font-weight: 500;">
                            <span class="truncate-desc"><?= htmlspecialchars($descFormateada, ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="info-badge-click">Detalles</span>
                        </td>
                        <td style="font-family: monospace; color: #64748b; font-size: 12px;"><?= htmlspecialchars($log['ip'], ENT_QUOTES, 'UTF-8') ?></td>
                    </tr>
                    
                    <!-- Collapsible Details Row -->
                    <tr id="detail-row-<?= $log['id'] ?>" class="detail-row">
                        <td colspan="6">
                            <div class="detail-container">
                                <div class="detail-card">
                                    <div class="detail-left">
                                        <div class="detail-item">
                                            <span class="detail-label">Descripción Completa del Movimiento</span>
                                            <div class="detail-value" style="font-size: 14px; line-height: 1.5; color: #0f172a;">
                                                <?= htmlspecialchars($descFormateada, ENT_QUOTES, 'UTF-8') ?>
                                            </div>
                                        </div>
                                        
                                        <?php if ($log['id_referencia']): ?>
                                            <div class="detail-item">
                                                <span class="detail-label">Entidad de Referencia</span>
                                                <div class="detail-value">
                                                    <?php if ($log['modulo'] === 'cotizaciones'): ?>
                                                        <br>
                                                        <a href="/Cycsa/publico/cotizaciones/detalle?id=<?= $log['id_referencia'] ?>" class="btn-detail-link">
                                                            <i class="fa-solid fa-eye"></i> Ver Cotización Relacionada
                                                        </a>
                                                    <?php elseif ($log['modulo'] === 'usuarios'): ?>
                                                        <br>
                                                        <a href="/Cycsa/publico/usuarios" class="btn-detail-link">
                                                            <i class="fa-solid fa-users"></i> Ir a Gestión de Usuarios
                                                        </a>
                                                    <?php elseif ($log['modulo'] === 'clientes'): ?>
                                                        <br>
                                                        <a href="/Cycsa/publico/clientes" class="btn-detail-link">
                                                            <i class="fa-solid fa-address-book"></i> Ir a Gestión de Clientes
                                                        </a>
                                                    <?php elseif ($log['modulo'] === 'productos'): ?>
                                                        <br>
                                                        <a href="/Cycsa/publico/productos" class="btn-detail-link">
                                                            <i class="fa-solid fa-flask"></i> Ir a Catálogo de Ensayos
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                    <div class="detail-right">
                                        <div class="detail-item">
                                            <span class="detail-label">Operador / Usuario</span>
                                            <div class="detail-value">
                                                <strong><?= htmlspecialchars($log['usuario_nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                                                <?php if (!$log['id_usuario']): ?>
                                                    <span style="display: block; font-size: 12px; color: #a1a1aa; font-style: italic;">Acción anónima (Cliente o Proceso Automático)</span>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Dirección IP del Dispositivo</span>
                                            <div class="detail-value" style="font-family: monospace; font-size: 13px; display: flex; align-items: center; gap: 8px;">
                                                <?= htmlspecialchars($log['ip'], ENT_QUOTES, 'UTF-8') ?>
                                                <button onclick="copiarIP('<?= $log['ip'] ?>', event)" class="btn-detail-link" style="margin: 0; padding: 2px 6px; font-size: 11px;">
                                                    <i class="fa-regular fa-copy"></i> Copiar
                                                </button>
                                            </div>
                                        </div>
                                        <div class="detail-item">
                                            <span class="detail-label">Fecha y Hora Precisa</span>
                                            <div class="detail-value" style="font-size: 12px;">
                                                <?= date('d/m/Y h:i:s A', strtotime($log['fecha_creacion'])) ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                <?php endforeach; ?>
                
                <?php if(empty($logs)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #64748b; font-weight: 500;">
                        <i class="fa-solid fa-circle-info" style="font-size: 20px; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>
                        No se encontraron registros de bitácora con los filtros especificados.
                    </td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
    // Toggle detail collapsible row
    function toggleDetailRow(logId) {
        const detailRow = document.getElementById('detail-row-' + logId);
        if (detailRow.style.display === 'table-row') {
            detailRow.style.display = 'none';
        } else {
            // Close other detail rows first (optional, for a clean accordion effect)
            document.querySelectorAll('.detail-row').forEach(row => {
                row.style.display = 'none';
            });
            detailRow.style.display = 'table-row';
        }
    }

    // Copy IP address utility
    function copiarIP(ip, event) {
        event.stopPropagation(); // Avoid triggering parent row click
        navigator.clipboard.writeText(ip).then(() => {
            alert('¡Dirección IP copiada al portapapeles!');
        }).catch(err => {
            console.error('Error al copiar la IP: ', err);
        });
    }
</script>
