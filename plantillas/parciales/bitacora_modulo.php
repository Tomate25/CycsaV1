<?php
/**
 * Parcial: Bitácora de Módulo (Línea de Tiempo Interactiva)
 *
 * Variables esperadas:
 *   $bitacora_logs          - array de filas de la tabla bitacora
 *   $bitacora_modulo_nombre - nombre legible del módulo (string)
 */

// — Mapeo de acciones a nombres legibles —
$bitacora_acciones_nombre = [
    'crear'                      => 'Creación',
    'editar'                     => 'Edición',
    'actualizar'                 => 'Edición',
    'eliminar'                   => 'Eliminación',
    'desactivar'                 => 'Desactivación',
    'login'                      => 'Inicio Sesión',
    'login_failed'               => 'Intento Fallido',
    'logout'                     => 'Cierre Sesión',
    'enviar_revision'            => 'Envío a Revisión',
    'aprobar_gerencia'           => 'Aprobación Gerencia',
    'devolver_gerencia'          => 'Devolución Gerencia',
    'enviar_cliente'             => 'Envío a Cliente',
    'volver_enviar_rechazada'    => 'Re-envío (V. Nueva)',
    'aprobar_cliente'            => 'Aprobado por Cliente',
    'rechazar_cliente'           => 'Rechazado por Cliente',
    'aprobar_admin_cliente'      => 'Aprobado por Admin',
    'rechazar_admin_cliente'     => 'Rechazado por Admin',
    'editar_reenviar'            => 'Corregido y Re-enviado',
    'crear_os'                   => 'Creación O/S',
    'cambiar_estado'             => 'Cambio de Estado',
    'programar_muestreo'         => 'Programación Muestreo',
    'hoja_campo'                 => 'Hoja de Campo',
    'hoja_solicitud'             => 'Hoja de Solicitud',
    'emitir_solicitud'           => 'Emisión Solicitud',
    'enviar_revision_resultados' => 'Envío Revisión Resultados',
    'finalizar_os'               => 'O/S Finalizada',
    'observar_resultados'        => 'Resultados Observados',
    'crear_banco'                => 'Registro Banco',
    'crear_tx_banco'             => 'Transacción Bancaria',
    'crear_asiento'              => 'Asiento Contable',
    'sincronizar_diario'         => 'Sincronización Diario',
    'pago_cxc'                   => 'Cobro CXC',
    'crear_cxp'                  => 'Cuenta por Pagar',
    'pago_cxp'                   => 'Pago CXP',
    'crear_rol'                  => 'Creación Rol',
    'editar_rol'                 => 'Edición Rol',
    'eliminar_rol'               => 'Eliminación Rol',
];

// — Determinar clase de badge e ícono —
if (!function_exists('bitacora_badge_info')) {
    function bitacora_badge_info(string $accion): array
    {
        $a = mb_strtolower($accion);

        if (str_contains($a, 'crear'))                                          return ['bitacora-badge-green',   'fa-plus'];
        if (str_contains($a, 'editar') || str_contains($a, 'actualizar'))       return ['bitacora-badge-amber',   'fa-pen'];
        if (str_contains($a, 'eliminar') || str_contains($a, 'desactivar'))     return ['bitacora-badge-red',     'fa-trash-can'];
        if (str_contains($a, 'aprobar'))                                        return ['bitacora-badge-emerald', 'fa-check'];
        if (str_contains($a, 'rechazar') || str_contains($a, 'devolver') || str_contains($a, 'observar')) return ['bitacora-badge-red', 'fa-xmark'];
        if (str_contains($a, 'enviar'))                                         return ['bitacora-badge-blue',    'fa-paper-plane'];
        if (str_contains($a, 'login'))                                          return ['bitacora-badge-blue',    'fa-right-to-bracket'];
        if (str_contains($a, 'logout'))                                         return ['bitacora-badge-gray',    'fa-right-from-bracket'];

        return ['bitacora-badge-gray', 'fa-arrow-pointer'];
    }
}

if (!isset($bitacora_logs) || !is_array($bitacora_logs)) {
    $bitacora_logs = [];
}
// Limitamos a 50 para optimizar rendimiento de carga inicial
$bitacora_logs = array_slice($bitacora_logs, 0, 50);

if (!isset($bitacora_modulo_nombre)) {
    $bitacora_modulo_nombre = 'Módulo';
}
?>

<!-- ======================== BITÁCORA – Estilos ======================== -->
<style>
    .bitacora-panel {
        margin-top: 35px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        box-shadow: 0 4px 12px rgba(0,0,0,0.03);
        overflow: hidden;
    }
    
    .bitacora-panel .bitacora-toggle-btn {
        width: 100%;
        display: flex;
        align-items: center;
        gap: 10px;
        padding: 16px 22px;
        background: #f8fafc;
        border: none;
        border-bottom: 1px solid #e2e8f0;
        cursor: pointer;
        font-size: 14.5px;
        font-weight: 600;
        color: #334155;
        font-family: 'Inter', sans-serif;
        transition: background .2s ease;
    }
    .bitacora-panel .bitacora-toggle-btn:hover {
        background: #f1f5f9;
    }
    .bitacora-panel .bitacora-toggle-btn i.fa-clock-rotate-left {
        color: var(--cycsa-azul);
        font-size: 16px;
    }
    .bitacora-panel .bitacora-toggle-btn .bitacora-chevron {
        margin-left: auto;
        transition: transform .3s ease;
        color: #94a3b8;
        font-size: 12px;
    }
    .bitacora-panel .bitacora-toggle-btn.active .bitacora-chevron {
        transform: rotate(180deg);
    }

    .bitacora-panel .bitacora-content {
        display: none; /* Empieza colapsado */
        overflow: hidden;
    }
    .bitacora-panel .bitacora-content.bitacora-open {
        display: block;
        animation: bitacoraSlideDown .3s ease forwards;
    }
    
    @keyframes bitacoraSlideDown {
        from { opacity: 0; max-height: 0; }
        to   { opacity: 1; max-height: 3000px; }
    }

    /* Buscador del historial */
    .bitacora-search-box {
        padding: 15px 22px 5px 22px;
        position: relative;
    }
    .bitacora-search-box input {
        width: 100%;
        box-sizing: border-box;
        padding: 10px 14px 10px 38px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        font-size: 13px;
        font-family: 'Inter', sans-serif;
        transition: all 0.2s;
    }
    .bitacora-search-box input:focus {
        outline: none;
        border-color: var(--cycsa-azul);
        box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.08);
    }
    .bitacora-search-box i {
        position: absolute;
        left: 34px;
        top: 23px;
        color: #94a3b8;
        font-size: 14px;
    }

    /* Línea de Tiempo */
    .bitacora-timeline {
        padding: 15px 22px 25px 22px;
        position: relative;
    }
    .bitacora-timeline::before {
        content: '';
        position: absolute;
        top: 20px;
        bottom: 20px;
        left: 31px;
        width: 2px;
        background: #e2e8f0;
    }
    .bitacora-item {
        display: flex;
        gap: 15px;
        margin-bottom: 15px;
        position: relative;
    }
    .bitacora-item:last-child {
        margin-bottom: 0;
    }
    .bitacora-marker {
        width: 20px;
        height: 20px;
        border-radius: 50%;
        background: #f1f5f9;
        border: 2px solid #cbd5e1;
        display: flex;
        align-items: center;
        justify-content: center;
        z-index: 1;
        flex-shrink: 0;
        margin-top: 6px;
    }
    .bitacora-marker i {
        font-size: 10px;
        color: #64748b;
    }
    .bitacora-info {
        flex: 1;
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 10px 14px;
        transition: all 0.15s ease;
    }
    .bitacora-info:hover {
        background: #f1f5f9;
        border-color: #cbd5e1;
        transform: translateY(-1px);
    }
    .bitacora-header-line {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 6px;
        flex-wrap: wrap;
        gap: 8px;
    }
    .bitacora-user-name {
        font-size: 12.5px;
        font-weight: 600;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .bitacora-user-name i {
        color: #94a3b8;
        font-size: 14px;
    }
    .bitacora-time-badge {
        font-size: 11.5px;
        color: #64748b;
        font-weight: 500;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .bitacora-desc-line {
        font-size: 13px;
        color: #475569;
        display: flex;
        align-items: center;
        gap: 8px;
        flex-wrap: wrap;
    }
    .bitacora-badge {
        font-size: 10.5px;
        font-weight: 700;
        padding: 2px 8px;
        border-radius: 20px;
        text-transform: uppercase;
        display: inline-flex;
        align-items: center;
        gap: 4px;
    }
    .bitacora-badge-green   { background: #dcfce7; color: #166534; }
    .bitacora-badge-amber   { background: #fef3c7; color: #92400e; }
    .bitacora-badge-red     { background: #fee2e2; color: #991b1b; }
    .bitacora-badge-emerald { background: #d1fae5; color: #065f46; }
    .bitacora-badge-blue    { background: #dbeafe; color: #1e40af; }
    .bitacora-badge-gray    { background: #f1f5f9; color: #475569; }

    .bitacora-msg {
        color: #334155;
        font-weight: 500;
        line-height: 1.4;
    }
    .bitacora-ip-tag {
        font-size: 11px;
        background: #e2e8f0;
        color: #475569;
        padding: 1px 6px;
        border-radius: 4px;
        margin-left: auto;
        display: flex;
        align-items: center;
        gap: 4px;
    }

    .bitacora-empty {
        display: flex;
        flex-direction: column;
        align-items: center;
        justify-content: center;
        padding: 48px 24px;
        color: #94a3b8;
        text-align: center;
    }
    .bitacora-empty i { font-size: 38px; margin-bottom: 12px; color: #cbd5e1; }
    .bitacora-empty p { font-size: 14px; margin: 0; }
</style>

<!-- ======================== BITÁCORA – HTML ======================== -->
<div class="bitacora-panel">
    <!-- Botón Toggle Principal -->
    <button type="button" class="bitacora-toggle-btn" onclick="toggleBitacoraPanel()">
        <i class="fa-solid fa-clock-rotate-left"></i>
        Historial de Actividad (<?= count($bitacora_logs) ?> registros)
        <i class="fa-solid fa-chevron-down bitacora-chevron"></i>
    </button>

    <!-- Contenido Flujo de Actividades -->
    <div id="bitacoraContent" class="bitacora-content">
        <?php if (empty($bitacora_logs)): ?>
            <div class="bitacora-empty">
                <i class="fa-solid fa-inbox"></i>
                <p>No se han registrado actividades en este módulo.</p>
            </div>
        <?php else: ?>
            <!-- Barra de Búsqueda de Actividad -->
            <div class="bitacora-search-box">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" id="filtro-bitacora-input" placeholder="Buscar por usuario, acción, descripción o fecha..." oninput="filtrarBitacora(this.value)">
            </div>

            <!-- Flujo de la Línea de Tiempo -->
            <div class="bitacora-timeline">
                <?php foreach ($bitacora_logs as $log):
                    $accion_raw = $log['accion'] ?? '';
                    [$badgeClass, $badgeIcon] = bitacora_badge_info($accion_raw);
                    $accion_label = $bitacora_acciones_nombre[$accion_raw] ?? ucfirst(str_replace('_', ' ', $accion_raw));

                    // Corregir variables de fecha y usuario basándonos en la BD real
                    $usuario = htmlspecialchars($log['usuario_nombre'] ?? 'Sistema/Anónimo');
                    $descripcion = htmlspecialchars($log['descripcion'] ?? '—');
                    
                    $fecha = '—';
                    if (!empty($log['fecha_creacion'])) {
                        try {
                            $dt = new DateTime($log['fecha_creacion']);
                            $fecha = $dt->format('d/m/Y h:i:s a'); // Formato am/pm legible
                        } catch (Exception $e) {
                            $fecha = htmlspecialchars($log['fecha_creacion']);
                        }
                    }

                    // Metadata para búsqueda
                    $busqueda_metadata = strtolower($usuario . ' ' . $accion_label . ' ' . $descripcion . ' ' . $fecha);
                ?>
                    <div class="bitacora-item" data-text="<?= htmlspecialchars($busqueda_metadata, ENT_QUOTES, 'UTF-8') ?>">
                        <div class="bitacora-marker">
                            <i class="fa-solid <?= $badgeIcon ?>"></i>
                        </div>
                        <div class="bitacora-info">
                            <div class="bitacora-header-line">
                                <span class="bitacora-user-name">
                                    <i class="fa-solid fa-user-circle"></i>
                                    <?= $usuario ?>
                                </span>
                                <span class="bitacora-time-badge">
                                    <i class="fa-regular fa-clock"></i>
                                    <?= $fecha ?>
                                </span>
                            </div>
                            <div class="bitacora-desc-line">
                                <span class="bitacora-badge <?= $badgeClass ?>">
                                    <?= htmlspecialchars($accion_label) ?>
                                </span>
                                <span class="bitacora-msg"><?= $descripcion ?></span>
                                <?php if (!empty($log['ip'])): ?>
                                    <span class="bitacora-ip-tag" title="Dirección IP de conexión">
                                        <i class="fa-solid fa-network-wired"></i>
                                        <?= htmlspecialchars($log['ip']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>
</div>

<!-- ======================== BITÁCORA – JS ======================== -->
<script>
    function toggleBitacoraPanel() {
        var content = document.getElementById('bitacoraContent');
        var btn     = content.previousElementSibling;

        if (content.classList.contains('bitacora-open')) {
            content.classList.remove('bitacora-open');
            content.style.display = 'none';
            btn.classList.remove('active');
        } else {
            content.style.display = 'block';
            void content.offsetHeight; // Forzar reflow
            content.classList.add('bitacora-open');
            btn.classList.add('active');
        }
    }

    function filtrarBitacora(query) {
        const term = query.toLowerCase().trim();
        const items = document.querySelectorAll('.bitacora-item');
        let visibles = 0;
        
        items.forEach(item => {
            const text = item.getAttribute('data-text') || '';
            if (text.includes(term)) {
                item.style.display = 'flex';
                visibles++;
            } else {
                item.style.display = 'none';
            }
        });
        
        // Manejo del mensaje "No se encontraron resultados"
        const listContainer = document.querySelector('.bitacora-timeline');
        const emptyMsg = document.getElementById('bitacora-filtro-empty');
        
        if (visibles === 0 && listContainer) {
            if (!emptyMsg) {
                const div = document.createElement('div');
                div.id = 'bitacora-filtro-empty';
                div.style.padding = '30px 20px';
                div.style.textAlign = 'center';
                div.style.color = '#94a3b8';
                div.style.fontSize = '13.5px';
                div.style.fontFamily = "'Inter', sans-serif";
                div.innerHTML = '<i class="fa-solid fa-magnifying-glass" style="font-size: 24px; display: block; margin-bottom: 10px; color: #cbd5e1;"></i>No se encontraron registros que coincidan con la búsqueda.';
                listContainer.appendChild(div);
            }
        } else {
            if (emptyMsg) {
                emptyMsg.remove();
            }
        }
    }
</script>
