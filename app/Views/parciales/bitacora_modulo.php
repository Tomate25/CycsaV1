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
    'aprobar'                    => 'Aprobación O/S',
    'finalizar_muestreo'         => 'Muestreo Finalizado',
    'omitir_espera'              => 'Omitir Espera',
    'aprobar_revision_resultados'=> 'Aprobación de Resultados',
    'guardar_matriz'             => 'Guardar Matriz',
    'aprobar_matriz'             => 'Aprobar Matriz',
    'devolver_matriz'            => 'Devolver Matriz',
    'congelar_plantillas'        => 'Congelar Plantillas RT-FM-13',
    'cambio_plantilla'           => 'Cambio de Plantilla',
    'cerrar_orden'               => 'Cierre de O/S',
    'enviar_matriz_cliente'      => 'Envío Matriz al Cliente',
    'alerta_regresion'           => 'Alerta de Regresión',
    'correccion_administrativa'  => 'Corrección Administrativa',
    'subir_logo_acreditacion'    => 'Logo de Acreditación',
    'eliminar_logo_acreditacion' => 'Retiro de Logo',
    'importacion_masiva'         => 'Carga Masiva',
    'aprobada'                   => 'Matriz Aprobada',
    'devuelta'                   => 'Matriz Devuelta',
    'enviado_revision'           => 'Enviado a Revisión',
    'corregido_y_reenviado'      => 'Corregido y Reenviado',
];

// — Determinar clase de badge e ícono —
if (!function_exists('bitacora_badge_info')) {
    function bitacora_badge_info(string $accion): array
    {
        $a = mb_strtolower($accion);

        if (str_contains($a, 'congelar'))                                       return ['bitacora-badge-gray',    'fa-lock'];
        if (str_contains($a, 'importacion') || str_contains($a, 'masiva'))      return ['bitacora-badge-blue',    'fa-file-excel'];
        if (str_contains($a, 'cerrar') || str_contains($a, 'finalizar'))        return ['bitacora-badge-emerald', 'fa-box-archive'];
        if (str_contains($a, 'hoja_') || $a === 'emitir_solicitud')             return ['bitacora-badge-blue',    'fa-file-lines'];
        if (str_contains($a, 'guardar') || str_contains($a, 'omitir'))          return ['bitacora-badge-amber',   'fa-floppy-disk'];
        if (str_contains($a, 'regresion') || str_contains($a, 'regresión'))     return ['bitacora-badge-red',     'fa-triangle-exclamation'];
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
// Limitamos para optimizar la carga. Quien incluye el parcial puede subir el tope.
$bitacora_limite = isset($bitacora_limite) ? (int)$bitacora_limite : 50;
if ($bitacora_limite <= 0) {
    $bitacora_limite = 50;
}
$bitacora_logs = array_slice($bitacora_logs, 0, $bitacora_limite);
$bitacora_abierta = !empty($bitacora_abierta);

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
        overflow: visible;
    }
    .bitacora-panel .bitacora-content.bitacora-open {
        display: block;
    }
    .cm-hist-actions { display:flex; flex-wrap:wrap; align-items:center; gap:8px; margin-top:10px; }
    .cm-hist-pill { font-size:11px; font-weight:700; padding:3px 9px; border-radius:999px; display:inline-flex; align-items:center; gap:5px; border:1px solid transparent; }
    .cm-hist-btn { font-size:11.5px; font-weight:700; padding:5px 11px; border-radius:6px; border:1px solid #cbd5e1; background:#fff; color:#1e293b; cursor:pointer; display:inline-flex; align-items:center; gap:6px; }
    .cm-hist-btn:hover { background:#eff6ff; border-color:#93c5fd; color:#103487; }
    .cm-hist-detail { display:none; margin-top:12px; background:#fff; border:1px solid #cbd5e1; border-radius:8px; padding:12px; }
    .cm-hist-detail.visible { display:block; }
    .cm-hist-tabs { display:flex; flex-wrap:wrap; gap:6px; border-bottom:1px solid #e2e8f0; padding-bottom:8px; margin-bottom:10px; }
    .cm-hist-tab { font-size:11.5px; font-weight:700; padding:5px 10px; border-radius:6px; border:1px solid #e2e8f0; background:#f8fafc; color:#475569; cursor:pointer; }
    .cm-hist-tab.active { background:#103487; color:#fff; border-color:#103487; }
    .cm-hist-pane { display:none; max-height:360px; overflow:auto; }
    .cm-hist-pane.active { display:block; }
    .cm-hist-table { width:100%; border-collapse:collapse; font-size:11.5px; }
    .cm-hist-table th { background:#f1f5f9; color:#334155; padding:6px 8px; text-align:left; border-bottom:1px solid #cbd5e1; position:sticky; top:0; }
    .cm-hist-table td { padding:6px 8px; border-bottom:1px solid #edf2f7; vertical-align:top; color:#1e293b; }
    
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
<div class="bitacora-panel" id="bitacoraModuloPanel">
    <!-- Botón Toggle Principal -->
    <button type="button" class="bitacora-toggle-btn<?= $bitacora_abierta ? ' active' : '' ?>" onclick="toggleBitacoraPanel()">
        <i class="fa-solid fa-clock-rotate-left"></i>
        Historial de Actividad (<?= count($bitacora_logs) ?> registros)
        <i class="fa-solid fa-chevron-down bitacora-chevron"></i>
    </button>

    <!-- Contenido Flujo de Actividades -->
    <div id="bitacoraContent" class="bitacora-content<?= $bitacora_abierta ? ' bitacora-open' : '' ?>">
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
                    $entidadTxt = trim((string)($log['entidad_tipo'] ?? ''));
                    if (!empty($log['entidad_id'])) {
                        $entidadTxt .= ' #' . (int)$log['entidad_id'];
                    }
                    $rolTxt = trim((string)($log['usuario_rol'] ?? ''));
                    $rolNombre = $rolTxt !== '' && function_exists('obtenerNombreRol') ? obtenerNombreRol($rolTxt) : $rolTxt;
                    $fuenteTxt = (string)($log['fuente'] ?? '');
                    $busqueda_metadata = strtolower($usuario . ' ' . $rolTxt . ' ' . $rolNombre . ' ' . $accion_label . ' ' . $accion_raw . ' ' . $descripcion . ' ' . $fecha . ' ' . $entidadTxt . ' ' . $fuenteTxt);
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
                                    <?php if ($rolNombre !== ''): ?>
                                        <span style="font-weight:600; color:#475569; background:#f1f5f9; padding:1px 7px; border-radius:12px; font-size:11px; border:1px solid #cbd5e1;" data-rol="rol <?= htmlspecialchars($rolTxt, ENT_QUOTES, 'UTF-8') ?>">· <?= htmlspecialchars($rolNombre, ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php endif; ?>
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
                                <?php if ($entidadTxt !== ''): ?>
                                    <span class="bitacora-badge bitacora-badge-gray"><?= htmlspecialchars($entidadTxt, ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                                <?php if ($fuenteTxt === 'json'): ?>
                                    <span class="bitacora-badge bitacora-badge-amber">Historial matriz</span>
                                <?php endif; ?>
                                <span class="bitacora-msg"><?= $descripcion ?></span>
                                <?php if (!empty($log['ip'])): ?>
                                    <span class="bitacora-ip-tag" title="Dirección IP de conexión">
                                        <i class="fa-solid fa-network-wired"></i>
                                        <?= htmlspecialchars($log['ip']) ?>
                                    </span>
                                <?php endif; ?>
                            </div>
                            <?php
                            $cargaDetalle = null;
                            if ($accion_raw === 'importacion_masiva' && !empty($log['datos_json'])) {
                                $dec = json_decode((string)$log['datos_json'], true);
                                if (is_array($dec) && (isset($dec['agregados']) || isset($dec['modificados']) || isset($dec['desactivados']))) {
                                    $cargaDetalle = $dec;
                                }
                            }
                            if ($cargaDetalle !== null):
                                $logIdSafe = (int)($log['id'] ?? rand(1000, 99999));
                                $tot = $cargaDetalle['totales'] ?? [];
                                $listaMod = is_array($cargaDetalle['modificados'] ?? null) ? $cargaDetalle['modificados'] : [];
                                $listaAgr = is_array($cargaDetalle['agregados'] ?? null) ? $cargaDetalle['agregados'] : [];
                                $listaDes = is_array($cargaDetalle['desactivados'] ?? null) ? $cargaDetalle['desactivados'] : [];
                                $archivoSubido = (string)($cargaDetalle['archivo'] ?? 'Archivo');
                            ?>
                                <div class="cm-hist-actions">
                                    <span class="cm-hist-pill" style="background:#eff6ff;color:#1d4ed8;border-color:#bfdbfe;" title="Archivo cargado"><i class="fa-solid fa-file-excel"></i> <?= htmlspecialchars($archivoSubido, ENT_QUOTES, 'UTF-8') ?></span>
                                    <span class="cm-hist-pill" style="background:#dcfce7;color:#166534;border-color:#bbf7d0;">+<?= count($listaAgr) ?> agregados</span>
                                    <span class="cm-hist-pill" style="background:#fef3c7;color:#92400e;border-color:#fde68a;">~<?= count($listaMod) ?> modificados</span>
                                    <span class="cm-hist-pill" style="background:#f1f5f9;color:#475569;border-color:#cbd5e1;">=<?= (int)($tot['sin_cambios'] ?? 0) ?> sin cambios</span>
                                    <?php if (!empty($listaDes)): ?>
                                        <span class="cm-hist-pill" style="background:#fee2e2;color:#991b1b;border-color:#fecaca;">-<?= count($listaDes) ?> desactivados</span>
                                    <?php endif; ?>
                                    <button type="button" class="cm-hist-btn" onclick="toggleDetalleCargaMasiva(<?= $logIdSafe ?>)">
                                        <i class="fa-solid fa-list-check"></i> Ver detalle de cambios
                                    </button>
                                    <button type="button" class="cm-hist-btn" onclick="descargarReporteCargaMasiva(<?= $logIdSafe ?>)">
                                        <i class="fa-solid fa-download"></i> Descargar reporte (.csv)
                                    </button>
                                </div>

                                <div id="cm-hist-detail-<?= $logIdSafe ?>" class="cm-hist-detail" data-payload="<?= htmlspecialchars((string)$log['datos_json'], ENT_QUOTES, 'UTF-8') ?>">
                                    <div class="cm-hist-tabs">
                                        <button type="button" class="cm-hist-tab active" onclick="cambiarTabCargaMasiva(<?= $logIdSafe ?>, 'modificados', this)">Modificados (<?= count($listaMod) ?>)</button>
                                        <button type="button" class="cm-hist-tab" onclick="cambiarTabCargaMasiva(<?= $logIdSafe ?>, 'agregados', this)">Agregados (<?= count($listaAgr) ?>)</button>
                                        <?php if (!empty($listaDes)): ?>
                                            <button type="button" class="cm-hist-tab" onclick="cambiarTabCargaMasiva(<?= $logIdSafe ?>, 'desactivados', this)">Desactivados (<?= count($listaDes) ?>)</button>
                                        <?php endif; ?>
                                    </div>

                                    <div id="cm-pane-<?= $logIdSafe ?>-modificados" class="cm-hist-pane active">
                                        <?php if (empty($listaMod)): ?>
                                            <div class="text-muted small">No hubo productos modificados en esta carga.</div>
                                        <?php else: ?>
                                            <table class="cm-hist-table">
                                                <thead><tr><th>Ítem / ID</th><th>Producto</th><th>Qué cambió (Anterior ➔ Nuevo)</th></tr></thead>
                                                <tbody>
                                                <?php foreach ($listaMod as $m): ?>
                                                    <tr>
                                                        <td style="white-space:nowrap;"><strong>#<?= htmlspecialchars((string)($m['no_item'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong><br><small style="color:#64748b;">ID <?= (int)($m['id'] ?? 0) ?><?= !empty($m['codigo_agn']) ? ' · ' . htmlspecialchars((string)$m['codigo_agn'], ENT_QUOTES, 'UTF-8') : '' ?></small></td>
                                                        <td><strong><?= htmlspecialchars((string)($m['nombre_comercial'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                                        <td>
                                                            <?php foreach (($m['cambios'] ?? []) as $c): ?>
                                                                <div style="margin-bottom:3px;">
                                                                    <span style="font-weight:700;color:#103487;"><?= htmlspecialchars((string)($c['etiqueta'] ?? $c['campo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>:</span>
                                                                    <span style="color:#991b1b;text-decoration:line-through;"><?= htmlspecialchars((string)($c['anterior'] ?? ''), ENT_QUOTES, 'UTF-8') ?: '(vacío)' ?></span>
                                                                    ➔
                                                                    <span style="color:#047857;font-weight:600;"><?= htmlspecialchars((string)($c['nuevo'] ?? ''), ENT_QUOTES, 'UTF-8') ?: '(vacío)' ?></span>
                                                                </div>
                                                            <?php endforeach; ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>

                                    <div id="cm-pane-<?= $logIdSafe ?>-agregados" class="cm-hist-pane">
                                        <?php if (empty($listaAgr)): ?>
                                            <div class="text-muted small">No se agregaron productos nuevos en esta carga.</div>
                                        <?php else: ?>
                                            <table class="cm-hist-table">
                                                <thead><tr><th>Ítem</th><th>Código AGN</th><th>Producto</th><th>Matriz</th><th>Precio</th><th>Estatus</th></tr></thead>
                                                <tbody>
                                                <?php foreach ($listaAgr as $a): ?>
                                                    <tr>
                                                        <td><strong>#<?= htmlspecialchars((string)($a['no_item'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></strong><br><small style="color:#64748b;">ID <?= (int)($a['id'] ?? 0) ?></small></td>
                                                        <td style="font-family:monospace;"><?= htmlspecialchars((string)($a['codigo_agn'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td><strong><?= htmlspecialchars((string)($a['nombre_comercial'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong></td>
                                                        <td><?= htmlspecialchars((string)($a['matriz_tipo'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                                        <td style="color:#047857;font-weight:700;white-space:nowrap;">C$ <?= number_format((float)($a['precio'] ?? 0), 2) ?></td>
                                                        <td><?= htmlspecialchars((string)($a['estatus'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                                    </tr>
                                                <?php endforeach; ?>
                                                </tbody>
                                            </table>
                                        <?php endif; ?>
                                    </div>

                                    <?php if (!empty($listaDes)): ?>
                                    <div id="cm-pane-<?= $logIdSafe ?>-desactivados" class="cm-hist-pane">
                                        <table class="cm-hist-table">
                                            <thead><tr><th>ID / Ítem</th><th>Código</th><th>Producto</th><th>Precio</th><th>Motivo</th></tr></thead>
                                            <tbody>
                                            <?php foreach ($listaDes as $d): ?>
                                                <tr>
                                                    <td>ID <?= (int)($d['id'] ?? 0) ?> · #<?= htmlspecialchars((string)($d['no_item'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td style="font-family:monospace;"><?= htmlspecialchars((string)($d['codigo_servicio'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td><?= htmlspecialchars((string)($d['nombre_comercial'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                                    <td>C$ <?= number_format((float)($d['precio'] ?? 0), 2) ?></td>
                                                    <td><?= htmlspecialchars((string)($d['motivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?></td>
                                                </tr>
                                            <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    </div>
                                    <?php endif; ?>
                                </div>
                            <?php endif; ?>
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

    function toggleDetalleCargaMasiva(logId) {
        const box = document.getElementById('cm-hist-detail-' + logId);
        if (!box) return;
        box.classList.toggle('visible');
    }

    function cambiarTabCargaMasiva(logId, tabName, btnEl) {
        const box = document.getElementById('cm-hist-detail-' + logId);
        if (!box) return;
        box.querySelectorAll('.cm-hist-tab').forEach(b => b.classList.remove('active'));
        box.querySelectorAll('.cm-hist-pane').forEach(p => p.classList.remove('active'));
        if (btnEl) btnEl.classList.add('active');
        const pane = document.getElementById(`cm-pane-${logId}-${tabName}`);
        if (pane) pane.classList.add('active');
    }

    function descargarReporteCargaMasiva(logId) {
        const box = document.getElementById('cm-hist-detail-' + logId);
        if (!box) return;
        let payload;
        try {
            payload = JSON.parse(box.getAttribute('data-payload') || '{}');
        } catch (e) {
            alert('No se pudo leer el detalle de la carga masiva.');
            return;
        }
        const esc = v => '"' + String(v ?? '').replaceAll('"', '""').replace(/[\r\n]+/g, ' ') + '"';
        const filas = [['Tipo_Cambio', 'ID_Producto', 'No_Item', 'Codigo_AGN', 'Nombre_Comercial', 'Campo', 'Valor_Anterior', 'Valor_Nuevo']];

        (payload.agregados || []).forEach(a => {
            filas.push(['AGREGADO', a.id || '', a.no_item || '', a.codigo_agn || '', a.nombre_comercial || '', 'Registro Nuevo', '', `Precio C$ ${Number(a.precio || 0).toFixed(2)} | ${a.estatus || ''}`]);
        });
        (payload.modificados || []).forEach(m => {
            (m.cambios || []).forEach(c => {
                filas.push(['MODIFICADO', m.id || '', m.no_item || '', m.codigo_agn || '', m.nombre_comercial || '', c.etiqueta || c.campo || '', c.anterior || '', c.nuevo || '']);
            });
        });
        (payload.desactivados || []).forEach(d => {
            filas.push(['DESACTIVADO', d.id || '', d.no_item || '', d.codigo_servicio || '', d.nombre_comercial || '', 'Estado', 'Activo', d.motivo || 'Inactivo']);
        });

        const csv = '\uFEFFsep=;\r\n' + filas.map(r => r.map(esc).join(';')).join('\r\n');
        const blob = new Blob([csv], { type: 'text/csv;charset=utf-8;' });
        const url = URL.createObjectURL(blob);
        const link = document.createElement('a');
        link.href = url;
        link.download = `CYCSA_Historial_Carga_Masiva_${logId}.csv`;
        document.body.appendChild(link);
        link.click();
        link.remove();
        URL.revokeObjectURL(url);
    }
</script>
