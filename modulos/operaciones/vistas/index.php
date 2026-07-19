<?php
// Operations index view - LIMS Dashboard (Premium Redesign)
?>
<style>
    :root {
        --cycsa-azul: #103487;
        --cycsa-azul-hover: #0c2766;
        --cycsa-rojo: #e31837;
        --color-success: #10b981;
        --color-success-hover: #059669;
        --color-warning: #f59e0b;
        --color-danger: #ef4444;
        --color-slate-50: #f8fafc;
        --color-slate-100: #f1f5f9;
        --color-slate-200: #e2e8f0;
        --color-slate-300: #cbd5e1;
        --color-slate-600: #475569;
        --color-slate-700: #334155;
        --color-slate-800: #1e293b;
        --color-slate-900: #0f172a;
    }

    .tabla-cycsa { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 10px; font-size: 14px; }
    .tabla-cycsa th { background-color: var(--color-slate-50); color: var(--color-slate-600); padding: 14px 16px; text-align: left; font-weight: 600; border-bottom: 2px solid var(--color-slate-200); text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
    .tabla-cycsa td { padding: 14px 16px; border-bottom: 1px solid var(--color-slate-100); vertical-align: middle; color: var(--color-slate-700); transition: background-color 0.2s; }
    .tabla-cycsa tbody tr:not(.detalle-os-row):hover { background-color: var(--color-slate-50); }
    
    .badge-prioridad { padding: 4px 8px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.3px; display: inline-block; }
    .prioridad-Alta { background-color: #fee2e2; color: #b91c1c; }
    .prioridad-Media { background-color: #ffedd5; color: #c2410c; }
    .prioridad-Normal { background-color: #dcfce7; color: #15803d; }
    
    .badge-estado { padding: 5px 12px; border-radius: 20px; font-size: 10px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; border: 1px solid transparent; }
    
    /* Estado Badge Colors */
    .estado-Estado-1-Recepcion { background-color: #f1f5f9; color: #475569; border-color: #cbd5e1; }
    .estado-Estado-2-Revision { background-color: #eff6ff; color: #1e40af; border-color: #bfdbfe; }
    .estado-Estado-2-Observada { background-color: #fef2f2; color: #b91c1c; border-color: #fecaca; }
    .estado-Estado-3-Ingreso-Directo { background-color: #ecfdf5; color: #047857; border-color: #a7f3d0; }
    .estado-Estado-3A-Programacion-Muestreo { background-color: #fef3c7; color: #d97706; border-color: #fde68a; }
    .estado-Estado-3B-Ejecucion-Muestreo { background-color: #fffbeb; color: #b45309; border-color: #fef3c7; }
    .estado-Estado-3C-Espera-Muestreo { background-color: #faf5ff; color: #6b21a8; border-color: #e9d5ff; }
    .estado-Estado-4-Ingreso-Laboratorio { background-color: #ecfeff; color: #0891b2; border-color: #c5f6fa; }
    .estado-En-Proceso { background-color: #fef3c7; color: #d97706; border-color: #fde68a; }
    .estado-Estado-5-Solicitud-Tecnicos { background-color: #f0fdf4; color: #166534; border-color: #bbf7d0; }
    .estado-Estado-6-Ejecucion-Ensayos { background-color: #fff7ed; color: #c2410c; border-color: #ffedd5; }
    .estado-Estado-7-Revision-Resultados { background-color: #e0f2fe; color: #0369a1; border-color: #bae6fd; }
    .estado-Finalizado { background-color: #dcfce7; color: #15803d; border-color: #bbf7d0; }
    
    .modal-premium { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .modal-premium-content { background-color: #fff; margin: 4% auto; padding: 30px; border: 1px solid var(--color-slate-200); width: 45%; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: slideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    
    @keyframes slideDown {
        from { transform: translateY(-20px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .form-group { margin-bottom: 16px; display: flex; flex-direction: column; gap: 6px; }
    .form-group > label {
        font-size: 13px;
        font-weight: 600;
        color: var(--color-slate-700);
        min-height: 32px;
        display: flex;
        align-items: flex-end;
        margin-bottom: 2px;
    }
    .form-control { padding: 10px 14px; border: 1px solid var(--color-slate-300); border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: all 0.2s; color: var(--color-slate-800); }
    .form-control:focus { border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.1); }
    
    .btn-accion { border: none; background: none; cursor: pointer; padding: 8px 14px; border-radius: 6px; font-size: 12.5px; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; }
    .btn-os { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .btn-os:hover { background-color: #dbeafe; transform: translateY(-1px); }
    .btn-recepcion { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .btn-recepcion:hover { background-color: #dcfce7; transform: translateY(-1px); }
    .btn-detalle { background-color: var(--color-slate-100); color: var(--color-slate-700); border: 1px solid var(--color-slate-200); }
    .btn-detalle:hover { background-color: var(--color-slate-200); transform: translateY(-1px); }
    .btn-danger { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .btn-danger:hover { background-color: #fee2e2; }
    .btn-primary { background-color: var(--cycsa-azul); color: white; border: 1px solid var(--cycsa-azul); }
    .btn-primary:hover { background-color: var(--cycsa-azul-hover); color: white; transform: translateY(-1px); }
    
    .alert { padding: 14px 20px; border-radius: 8px; margin-bottom: 20px; font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .alert-exito { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .alert-error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    
    .tab-btn { padding: 10px 20px; border-radius: 8px; border: none; font-weight: 600; font-size: 14px; cursor: pointer; transition: all 0.2s; display: inline-flex; align-items: center; gap: 8px; }
    .tab-btn-active { background-color: var(--cycsa-azul); color: white; box-shadow: 0 4px 6px -1px rgba(16, 52, 135, 0.2); }
    .tab-btn-inactive { background-color: var(--color-slate-100); color: var(--color-slate-600); }
    .tab-btn-inactive:hover { background-color: var(--color-slate-200); color: var(--color-slate-800); }

    .tab-content { display: none; }
    .tab-content-active { display: block; }

    .btn-toggle-detail { border: none; background: none; color: var(--color-slate-600); cursor: pointer; padding: 6px; font-size: 14px; transition: all 0.2s; border-radius: 4px; display: flex; align-items: center; justify-content: center; }
    .btn-toggle-detail:hover { background-color: var(--color-slate-200); color: var(--color-slate-900); }
    
    .detalle-os-card { background: white; border: 1px solid var(--color-slate-200); border-radius: 10px; padding: 20px; box-shadow: inset 0 2px 4px rgba(0,0,0,0.02), 0 4px 6px -1px rgba(0,0,0,0.05); }
    
    .search-input-wrapper { position: relative; display: flex; align-items: center; }
    .search-icon { position: absolute; left: 14px; color: var(--color-slate-600); font-size: 14px; pointer-events: none; }
    .search-input { padding: 10px 16px 10px 38px !important; width: 300px; border-radius: 8px !important; }

    .phase-alert {
        display: flex;
        align-items: flex-start;
        gap: 10px;
        padding: 10px 14px;
        border-radius: 6px;
        font-size: 12.5px;
        margin-top: 5px;
        border: 1px solid transparent;
        line-height: 1.4;
    }
</style>

<div style="background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.02);">

    <!-- Alertas -->
    <?php if (!empty($exito)): ?>
        <div class="alert alert-exito">
            <i class="fa-solid fa-circle-check" style="font-size: 16px;"></i> <?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-xmark" style="font-size: 16px;"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="header-flex" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div>
            <h2 style="margin: 0; color: var(--color-slate-900); font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-gears" style="color: var(--cycsa-azul);"></i> Panel de Operaciones LIMS</h2>
            <p style="color: var(--color-slate-600); margin-top: 5px; font-size: 14px;">Control de Calidad, Ruta de Muestreo, Ingreso Técnico y Cierre de Ensayos.</p>
        </div>
        
        <div class="actions-flex">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="lims-search" placeholder="Filtrar en tiempo real..." class="form-control search-input">
            </div>
        </div>
    </div>

    <!-- Pestañas LIMS -->
    <div style="display: flex; justify-content: space-between; border-bottom: 2px solid var(--color-slate-100); padding-bottom: 14px; margin-bottom: 25px; align-items: center; flex-wrap: wrap; gap: 15px;">
        <div class="tabs-container" style="display: flex; gap: 10px; flex-wrap: wrap;">
            <button class="tab-btn tab-btn-active" onclick="switchTab('tab-os', this)"><i class="fa-solid fa-receipt"></i> Órdenes de Servicio (Flujo de Calidad)</button>
        </div>
        <a href="/Cycsa/publico/operaciones/calendario" class="tab-btn tab-btn-inactive" style="text-decoration: none;"><i class="fa-solid fa-calendar-days"></i> Calendario de Rupturas</a>
    </div>

    <!-- CONTENIDO PESTAÑA: ÓRDENES DE SERVICIO -->
    <div id="tab-os" class="tab-content tab-content-active">
        <h3 style="font-family:'Outfit'; color:var(--color-slate-900); margin-bottom:15px; font-size:16px; font-weight:700;">Seguimiento del Proceso de Ensayos</h3>
        <div style="overflow-x: auto; border: 1px solid var(--color-slate-200); border-radius: 10px;">
            <table class="tabla-cycsa">
                <thead>
                    <tr>
                        <th style="width: 40px;"></th>
                        <th>Código O/S</th>
                        <th>Cliente / Proyecto</th>
                        <th>Fecha Emisión</th>
                        <th>Fase y Estado Actual</th>
                        <th style="text-align: right;">Acción Siguiente</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordenes as $o): 
                        $totalItems = count($o['items']);
                        $receivedItems = 0;
                        foreach ($o['items'] as $item) {
                            if (!empty($item['codigo_muestra'])) {
                                $receivedItems++;
                            }
                        }
                    ?>
                    <tr id="os-row-<?= $o['id'] ?>" data-detail-id="os-detail-<?= $o['id'] ?>">
                        <td style="text-align: center;">
                            <button class="btn-toggle-detail" onclick="toggleDetailOS(<?= $o['id'] ?>, this)" title="Ver Ensayos">
                                <i class="fa-solid fa-chevron-right"></i>
                            </button>
                        </td>
                        <td style="font-family: monospace; font-size: 13.5px; font-weight: 700; color: var(--cycsa-azul);">
                            <?= htmlspecialchars($o['codigo_os'], ENT_QUOTES, 'UTF-8') ?>
                        </td>
                        <td>
                            <div style="font-weight: 600; color: var(--color-slate-800);"><?= htmlspecialchars($o['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div style="font-size: 11.5px; color: var(--color-slate-600); margin-top: 2px;">Proyecto: <?= htmlspecialchars($o['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?> &bull; Coty: <?= htmlspecialchars($o['cot_codigo'], ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td><?= date('d/m/Y', strtotime($o['fecha_emision'])) ?></td>
                        <td>
                            <span class="badge-estado estado-<?= str_replace([' ', ':'], '-', $o['estado']) ?>"><?= htmlspecialchars($o['estado'], ENT_QUOTES, 'UTF-8') ?></span>
                                                 <!-- DETALLE EXPLICATIVO DE LA FASE -->
                            <?php if ($o['estado'] === 'Estado 1: Recepcion'): ?>
                                <?php if (empty($o['hoja_solicitud'])): ?>
                                    <div class="phase-alert" style="background:#fef2f2; border-color:#fecaca; color:#b91c1c;">
                                        <i class="fa-solid fa-circle-exclamation"></i> <strong>PENDIENTE:</strong> Registrar la Hoja de Solicitud de Servicio (CYCSA-RT-FM) para poder enviar a revisión.
                                    </div>
                                <?php else: ?>
                                    <div class="phase-alert" style="background:#f8fafc; border-color:#e2e8f0; color:#475569;">
                                        <i class="fa-solid fa-circle-check"></i> Hoja <?= htmlspecialchars($o['hoja_solicitud']['codigo_documento'] ?? 'CYCSA-RT-FM', ENT_QUOTES, 'UTF-8') ?> registrada. <a href="/Cycsa/publico/operaciones/descargar-solicitud?id_os=<?= $o['id'] ?>" target="_blank" style="color:var(--cycsa-azul); font-weight:700; text-decoration:underline;">Ver PDF</a>. Listo para enviar a revisión de supervisor.
                                    </div>
                                <?php endif; ?>
                            <?php elseif ($o['estado'] === 'Estado 2: Revision'): ?>
                                <div class="phase-alert" style="background:#eff6ff; border-color:#bfdbfe; color:#1e40af;">
                                    <i class="fa-solid fa-circle-info"></i> Pendiente de aprobación de supervisor. Se validará si requiere muestreo en campo. <a href="/Cycsa/publico/operaciones/descargar-solicitud?id_os=<?= $o['id'] ?>" target="_blank" style="color:var(--cycsa-azul); font-weight:700; text-decoration:underline;">Ver PDF</a>
                                </div>
                            <?php elseif ($o['estado'] === 'Estado 2: Observada'): ?>
                                <div class="phase-alert" style="background:#fef2f2; border-color:#fecaca; color:#b91c1c;">
                                    <i class="fa-solid fa-circle-xmark"></i> <strong>OS OBSERVADA:</strong> <?= htmlspecialchars($o['motivo_observacion'] ?? 'Sin motivo indicado.', ENT_QUOTES, 'UTF-8') ?>. Edite la hoja de solicitud y re-envíe a revisión.
                                </div>
                            <?php elseif ($o['estado'] === 'Estado 3: Ingreso Directo'): ?>
                                <div class="phase-alert" style="background:#f0fdf4; border-color:#bbf7d0; color:#15803d;">
                                    <i class="fa-solid fa-circle-check"></i> <strong>RUTA DIRECTA:</strong> No requiere muestreo en campo. Listo para ingreso técnico.
                                </div>
                            <?php elseif ($o['estado'] === 'Estado 3A: Programacion Muestreo'): ?>
                                <div class="phase-alert" style="background:#fffbeb; border-color:#fef3c7; color:#b45309;">
                                    <i class="fa-solid fa-circle-info"></i> <strong>RUTA DE CAMPO:</strong> Requiere programar fecha, técnico y vehículo de muestreo.
                                </div>
                            <?php elseif ($o['estado'] === 'Estado 3B: Ejecucion Muestreo'): ?>
                                <div class="phase-alert" style="background:#fff7ed; border-color:#ffedd5; color:#c2410c;">
                                    <i class="fa-solid fa-truck"></i> Programación: <?= date('d/m/Y', strtotime($o['fecha_muestreo'])) ?> <?= $o['hora_muestreo'] ?> &bull; Técnico: <?= htmlspecialchars($o['tecnico_muestreo'], ENT_QUOTES, 'UTF-8') ?> &bull; Pendiente registrar hoja CYCSA-RT-FM-07.
                                </div>
                            <?php elseif ($o['estado'] === 'Estado 3C: Espera Muestreo'): ?>
                                <?php
                                $fechaRegistro = strtotime($o['fecha_registro_campo']);
                                $segundosTranscurridos = time() - $fechaRegistro;
                                $horasTranscurridas = $segundosTranscurridos / 3600;
                                $horasRequeridas = (isset($o['horas_espera_requeridas']) && $o['horas_espera_requeridas'] !== null) ? (int)$o['horas_espera_requeridas'] : 24;
                                $esperaCompletada = $horasTranscurridas >= $horasRequeridas;
                                $segundosRestantes = max(0, ($horasRequeridas * 3600) - $segundosTranscurridos);
                                $horasRestantes = floor($segundosRestantes / 3600);
                                $minutosRestantes = floor(($segundosRestantes % 3600) / 60);
                                ?>
                                <?php if ($esperaCompletada || $horasRequeridas == 0): ?>
                                    <div class="phase-alert" style="background:#ecfdf5; border-color:#a7f3d0; color:#047857;">
                                        <i class="fa-solid fa-hourglass-end"></i> Período de espera (<?= $horasRequeridas ?>h) completado. Muestras listas para recolección e ingreso.
                                    </div>
                                <?php else: ?>
                                    <div class="phase-alert" style="background:#faf5ff; border-color:#e9d5ff; color:#6b21a8;">
                                        <i class="fa-solid fa-hourglass-half"></i> Período de espera de <?= $horasRequeridas ?>h activo. Restan: <strong><?= $horasRestantes ?>h <?= $minutosRestantes ?>m</strong> para ingresar.
                                    </div>
                                <?php endif; ?>
                            <?php elseif ($o['estado'] === 'Estado 4: Ingreso Laboratorio'): ?>
                                <div class="phase-alert" style="background:#ecfeff; border-color:#c5f6fa; color:#0891b2;">
                                    <i class="fa-solid fa-flask"></i> Muestras en laboratorio. Pendiente registrar ingreso y programación de edades/rupturas.
                                </div>
                            <?php elseif ($o['estado'] === 'En Proceso'): ?>
                                <div class="phase-alert" style="background:#fffbeb; border-color:#fef3c7; color:#b45309;">
                                    <i class="fa-solid fa-spinner fa-spin"></i> <strong>RECEPCIÓN EN PROCESO:</strong> Se han registrado algunas muestras. Pendiente recibir el resto de muestras facturadas.
                                </div>
                            <?php elseif ($o['estado'] === 'Estado 5: Solicitud Tecnicos'): ?>
                                <div class="phase-alert" style="background:#f0fdf4; border-color:#bbf7d0; color:#166534;">
                                    <i class="fa-solid fa-file-pdf"></i> Hoja <?= htmlspecialchars($o['hoja_solicitud']['codigo_documento'] ?? 'CYCSA-RT-FM', ENT_QUOTES, 'UTF-8') ?> registrada. <a href="/Cycsa/publico/operaciones/descargar-solicitud?id_os=<?= $o['id'] ?>" target="_blank" style="color:var(--cycsa-azul); font-weight:700; text-decoration:underline;">Ver PDF de Solicitud</a>.
                                </div>
                            <?php elseif ($o['estado'] === 'Estado 6: Ejecucion Ensayos'): ?>
                                <div class="phase-alert" style="background:#fff7ed; border-color:#ffedd5; color:#c2410c;">
                                    <i class="fa-solid fa-hammer"></i> Ensayos técnicos de laboratorio en ejecución. Blindaje comercial activo.
                                    <?php if (!empty($o['motivo_observacion'])): ?>
                                        <br><strong style="color:var(--color-danger);">OBSERVADA:</strong> <?= htmlspecialchars($o['motivo_observacion'], ENT_QUOTES, 'UTF-8') ?>
                                    <?php endif; ?>
                                </div>
                            <?php elseif ($o['estado'] === 'Estado 7: Revision Resultados'): ?>
                                <div class="phase-alert" style="background:#e0f2fe; border-color:#bae6fd; color:#0369a1;">
                                    <i class="fa-solid fa-magnifying-glass"></i> Resultados capturados. Esperando revisión de calidad por el supervisor.
                                </div>
                            <?php endif; ?>

                            <?php if (!empty($o['hoja_campo_codigo'])): ?>
                                <div style="margin-top: 8px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px; padding: 10px 14px; font-size: 12.5px; color: #334155;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px; border-bottom: 1px solid #e2e8f0; padding-bottom: 4px;">
                                        <strong style="color: #103487; display: flex; align-items: center; gap: 6px; font-family: 'Outfit', sans-serif;">
                                            <i class="fa-solid fa-lock" style="color: #64748b;"></i> HOJA DE CAMPO SELLADA: <span style="font-family: monospace; color: #103487; font-weight: 800; background: #e0f2fe; padding: 2px 8px; border-radius: 4px;"><?= htmlspecialchars($o['hoja_campo_codigo'], ENT_QUOTES, 'UTF-8') ?></span>
                                        </strong>
                                        <span style="background: #e2e8f0; color: #334155; font-size: 11px; padding: 2px 8px; border-radius: 12px; font-weight: 700;">
                                            <i class="fa-solid fa-clock"></i> Tiempo Configurado: <?= (int)($o['horas_espera_requeridas'] ?? 24) ?>h
                                        </span>
                                    </div>
                                    <div style="display: flex; gap: 15px; flex-wrap: wrap; color: #475569; font-size: 12px; margin-top: 4px;">
                                        <span><i class="fa-solid fa-user-gear" style="color:#103487;"></i> <strong>Operador:</strong> <?= htmlspecialchars($o['hoja_campo_operador'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
                                        <span><i class="fa-solid fa-calendar-check" style="color:#103487;"></i> <strong>Fecha Registro:</strong> <?= !empty($o['fecha_registro_campo']) ? date('d/m/Y H:i', strtotime($o['fecha_registro_campo'])) : '—' ?></span>
                                        <?php if (!empty($o['hoja_campo_notas'])): ?>
                                            <span><i class="fa-solid fa-comment-dots" style="color:#103487;"></i> <strong>Notas:</strong> <?= htmlspecialchars($o['hoja_campo_notas'], ENT_QUOTES, 'UTF-8') ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap; vertical-align: middle;">
                            <!-- BOTÓN DE FACTURACIÓN / CXC (Según diagrama de flujo) -->
                            <?php 
                            $cxcInfo = $cxcMap[$o['cot_codigo']] ?? null;
                            if ($cxcInfo): 
                            ?>
                                <a href="/Cycsa/publico/contabilidad/cxc?q=<?= urlencode($o['cot_codigo']) ?>" 
                                   class="btn-accion btn-detalle" 
                                   style="background-color: #10b981; color: white; border: 1px solid #10b981; padding: 7px 12px; font-size:12.5px; font-weight:600; text-decoration:none; display:inline-block; margin-right: 5px; border-radius: 4px;" 
                                   title="Ver estado de pago de la factura en contabilidad">
                                    <i class="fa-solid fa-circle-check"></i> Facturado (<?= $cxcInfo['estado'] ?>)
                                </a>
                            <?php else: ?>
                                <a href="/Cycsa/publico/contabilidad/cxc?prefill_cli=<?= $o['cliente_id'] ?>&prefill_fact=<?= urlencode($o['cot_codigo']) ?>&prefill_monto=<?= $o['cot_total'] ?>&prefill_notes=<?= urlencode('Facturación de Orden de Servicio ' . $o['codigo_os'] . ' - Proyecto: ' . $o['nombre_proyecto']) ?>" 
                                   class="btn-accion btn-os" 
                                   style="background-color: #f59e0b; color: white; border: 1px solid #f59e0b; padding: 7px 12px; font-size:12.5px; font-weight:600; text-decoration:none; display:inline-block; margin-right: 5px; border-radius: 4px;" 
                                   title="Registrar factura en cuentas por cobrar para iniciar proceso de cobro">
                                    <i class="fa-solid fa-file-invoice-dollar"></i> Facturar CXC
                                </a>
                            <?php endif; ?>

                            <!-- BOTONES DE ACCIÓN SEGÚN ESTADO -->
                            <form method="POST" action="/Cycsa/publico/operaciones/actualizar-estado" style="display:inline;">
                                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                <input type="hidden" name="id_os" value="<?= $o['id'] ?>">

                                <?php if ($o['estado'] === 'Estado 1: Recepcion'): ?>
                                    <?php if (empty($o['hoja_solicitud'])): ?>
                                        <button type="button" onclick="abrirModalHojaSolicitud(<?= $o['id'] ?>, '<?= $o['codigo_os'] ?>')" class="btn-accion btn-recepcion" style="padding: 7px 12px; font-size:12.5px; cursor:pointer;"><i class="fa-solid fa-file-circle-plus"></i> Registrar CYCSA-RT-FM</button>
                                    <?php else: ?>
                                        <input type="hidden" name="estado" value="Estado 2: Revision">
                                        <button type="submit" class="btn-accion btn-primary" style="padding: 7px 12px; font-size:12.5px; margin-right: 5px; cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600;"><i class="fa-solid fa-paper-plane"></i> Enviar a Revisión</button>
                                        <button type="button" onclick="abrirModalHojaSolicitud(<?= $o['id'] ?>, '<?= $o['codigo_os'] ?>')" class="btn-accion btn-detalle" style="padding: 7px 12px; font-size:12.5px; cursor:pointer;"><i class="fa-solid fa-edit"></i> Editar <?= htmlspecialchars($o['hoja_solicitud']['codigo_documento'] ?? 'CYCSA-RT-FM', ENT_QUOTES, 'UTF-8') ?></button>
                                    <?php endif; ?>
                                
                                <?php elseif ($o['estado'] === 'Estado 2: Revision'): ?>
                                    <?php if (in_array($_SESSION['usuario_rol'] ?? 0, [1, 3])): ?>
                                        <button type="button" class="btn-accion btn-recepcion" onclick="abrirModalRevision(<?= $o['id'] ?>, '<?= $o['codigo_os'] ?>')"><i class="fa-solid fa-check"></i> Decisión Supervisor</button>
                                    <?php else: ?>
                                        <span style="font-size:12px; color:var(--color-slate-600);"><i class="fa-solid fa-hourglass-half"></i> En Revisión</span>
                                    <?php endif; ?>
                                
                                <?php elseif ($o['estado'] === 'Estado 2: Observada'): ?>
                                    <input type="hidden" name="estado" value="Estado 2: Revision">
                                    <button type="submit" class="btn-accion btn-primary" style="padding: 7px 12px; font-size:12.5px; margin-right: 5px; cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600;"><i class="fa-solid fa-arrows-rotate"></i> Corregir y Re-enviar</button>
                                    <button type="button" onclick="abrirModalHojaSolicitud(<?= $o['id'] ?>, '<?= $o['codigo_os'] ?>')" class="btn-accion btn-detalle" style="padding: 7px 12px; font-size:12.5px; cursor:pointer;"><i class="fa-solid fa-edit"></i> Editar <?= htmlspecialchars($o['hoja_solicitud']['codigo_documento'] ?? 'CYCSA-RT-FM', ENT_QUOTES, 'UTF-8') ?></button>
                                
                                <?php elseif ($o['estado'] === 'Estado 3: Ingreso Directo'): ?>
                                    <a href="/Cycsa/publico/operaciones/recepcion?id_os=<?= $o['id'] ?>" class="btn-accion btn-recepcion"><i class="fa-solid fa-plus-circle"></i> Recibir Muestras</a>
                                
                                <?php elseif ($o['estado'] === 'Estado 3A: Programacion Muestreo'): ?>
                                    <button type="button" class="btn-accion btn-os" onclick="abrirModalMuestreo(<?= $o['id'] ?>, '<?= $o['codigo_os'] ?>')"><i class="fa-solid fa-calendar-plus"></i> Programar Muestreo</button>
                                
                                <?php elseif ($o['estado'] === 'Estado 3B: Ejecucion Muestreo'): ?>
                                    <?php 
                                    $autoTecnico = !empty($o['tecnico_muestreo']) ? $o['tecnico_muestreo'] : ($_SESSION['usuario_nombre'] ?? '');
                                    $autoCodigoDoc = 'CYCSA-RT-FM-07-' . sprintf('%04d', $o['id']);
                                    $autoHoras = isset($o['horas_espera_requeridas']) ? (int)$o['horas_espera_requeridas'] : 24;
                                    ?>
                                    <button type="button" class="btn-accion btn-recepcion" onclick="abrirModalHojaCampo(<?= $o['id'] ?>, '<?= $o['codigo_os'] ?>', '<?= htmlspecialchars($autoTecnico, ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($autoCodigoDoc, ENT_QUOTES, 'UTF-8') ?>', <?= $autoHoras ?>)"><i class="fa-solid fa-file-contract"></i> Registrar CYCSA-RT-FM-07</button>
                                
                                <?php elseif ($o['estado'] === 'Estado 3C: Espera Muestreo'): ?>
                                    <a href="/Cycsa/publico/operaciones/recepcion?id_os=<?= $o['id'] ?>" class="btn-accion btn-recepcion"><i class="fa-solid fa-truck-pickup"></i> Recolectar e Ingresar</a>
                                    <?php if (!$esperaCompletada && $horasRequeridas > 0): ?>
                                        <button type="submit" formaction="/Cycsa/publico/operaciones/omitir-espera" class="btn-accion btn-os" style="background:#fff7ed; color:#c2410c; border-color:#ffedd5;" onclick="return confirm('¿Desea omitir las <?= $horasRestantes ?>h <?= $minutosRestantes ?>m restantes e ingresar inmediatamente las muestras?');" title="Liberar tiempo de espera inmediatamente"><i class="fa-solid fa-forward-step"></i> Omitir Espera (<?= $horasRestantes ?>h)</button>
                                    <?php endif; ?>
                                
                                <?php elseif ($o['estado'] === 'Estado 4: Ingreso Laboratorio'): ?>
                                    <a href="/Cycsa/publico/operaciones/recepcion?id_os=<?= $o['id'] ?>" class="btn-accion btn-recepcion"><i class="fa-solid fa-flask"></i> Recibir en Lab</a>
                                
                                <?php elseif ($o['estado'] === 'En Proceso'): ?>
                                    <a href="/Cycsa/publico/operaciones/recepcion?id_os=<?= $o['id'] ?>" class="btn-accion btn-recepcion"><i class="fa-solid fa-plus-circle"></i> Recibir Pendientes</a>
                                
                                <?php elseif ($o['estado'] === 'Estado 5: Solicitud Tecnicos'): ?>
                                    <button type="submit" formaction="/Cycsa/publico/operaciones/emitir-solicitud" class="btn-accion btn-recepcion"><i class="fa-solid fa-paper-plane"></i> Emitir a Técnicos</button>
                                
                                <?php elseif ($o['estado'] === 'Estado 6: Ejecucion Ensayos'): ?>
                                    <button type="submit" formaction="/Cycsa/publico/operaciones/enviar-revision-resultados" class="btn-accion btn-os"><i class="fa-solid fa-share-from-square"></i> Enviar a Revisión de Resultados</button>
                                
                                <?php elseif ($o['estado'] === 'Estado 7: Revision Resultados'): ?>
                                    <?php if (in_array($_SESSION['usuario_rol'] ?? 0, [1, 3])): ?>
                                        <button type="button" class="btn-accion btn-recepcion" onclick="abrirModalRevisionResultados(<?= $o['id'] ?>, '<?= $o['codigo_os'] ?>')"><i class="fa-solid fa-graduation-cap"></i> Revisar Calidad</button>
                                    <?php else: ?>
                                        <span style="font-size:12px; color:var(--color-slate-600);"><i class="fa-solid fa-hourglass-half"></i> En Revisión de Calidad</span>
                                    <?php endif; ?>
                                
                                <?php elseif ($o['estado'] === 'Finalizado'): ?>
                                    <span style="color:var(--color-success); font-weight:700;"><i class="fa-solid fa-check-double"></i> OS Completada</span>
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                    
                    <!-- Sub-fila para detalles desplegables -->
                    <tr class="detalle-os-row" id="os-detail-<?= $o['id'] ?>" style="display: none; background-color: var(--color-slate-50);">
                        <td colspan="6" style="padding: 15px 25px; border-bottom: 1px solid var(--color-slate-200);">
                            <div class="detalle-os-card">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; flex-wrap: wrap; gap: 15px;">
                                    <div>
                                        <h4 style="margin: 0; font-family: 'Outfit'; font-size: 14px; font-weight: 700; color: var(--color-slate-900);"><i class="fa-solid fa-circle-info" style="color: var(--cycsa-azul);"></i> Estado de Recepción y Control de Ensayos LIMS</h4>
                                        <p style="margin: 3px 0 0 0; font-size: 12.5px; color: var(--color-slate-600);">Muestras ingresadas al laboratorio: <strong><?= $receivedItems ?></strong> de <strong><?= $totalItems ?></strong> totales solicitadas.</p>
                                    </div>
                                </div>
                                
                                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; flex-wrap: wrap;">
                                    <!-- Columna: Pendientes -->
                                    <div style="border-right: 1px solid var(--color-slate-100); padding-right: 10px;">
                                        <h5 style="margin: 0 0 10px 0; font-size: 12px; color: var(--color-danger); text-transform: uppercase; font-weight: 700; display: flex; align-items: center; gap: 6px;"><i class="fa-solid fa-clock"></i> Pendientes de Recepción</h5>
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <?php 
                                            $hasPending = false;
                                            foreach ($o['items'] as $item): 
                                                if (empty($item['codigo_muestra'])):
                                                    $hasPending = true;
                                            ?>
                                                <div style="display: flex; align-items: center; justify-content: space-between; background: #fff5f5; border: 1px solid #fed7d7; padding: 8px 12px; border-radius: 6px; font-size: 13px;">
                                                    <span style="color: #991b1b; font-weight: 500;"><i class="fa-solid fa-flask" style="font-size: 10.5px; margin-right: 6px; color: #f87171;"></i> <?= htmlspecialchars($item['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <?php if (in_array($o['estado'], ['Estado 3: Ingreso Directo', 'Estado 4: Ingreso Laboratorio', 'Estado 3C: Espera Muestreo', 'En Proceso'])): ?>
                                                        <a href="/Cycsa/publico/operaciones/recepcion?id_os=<?= $o['id'] ?>&id_detalle=<?= $item['id_detalle'] ?>" class="btn-accion btn-recepcion" style="padding: 4px 10px; font-size: 11px; font-family: 'Inter', sans-serif;"><i class="fa-solid fa-plus-circle"></i> Recibir</a>
                                                    <?php endif; ?>
                                                </div>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            if (!$hasPending):
                                            ?>
                                                <div style="color: var(--color-slate-600); font-size: 12.5px; font-style: italic; padding: 8px 0;"><i class="fa-solid fa-circle-check" style="color: var(--color-success); margin-right: 6px;"></i> Todas las muestras de esta O/S han sido recibidas y registradas.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                    
                                    <!-- Columna: Recibidas -->
                                    <div>
                                        <h5 style="margin: 0 0 10px 0; font-size: 12px; color: var(--color-success); text-transform: uppercase; font-weight: 700; display: flex; align-items: center; gap: 6px;"><i class="fa-solid fa-flask-vial"></i> Muestras en Laboratorio</h5>
                                        <div style="display: flex; flex-direction: column; gap: 8px;">
                                            <?php 
                                            $hasReceived = false;
                                            foreach ($o['items'] as $item): 
                                                if (!empty($item['codigo_muestra'])):
                                                    $hasReceived = true;
                                            ?>
                                                <div style="display: flex; align-items: center; justify-content: space-between; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 8px 12px; border-radius: 6px; font-size: 13px;">
                                                    <span style="color: #15803d; font-weight: 500;"><i class="fa-solid fa-flask" style="font-size: 10.5px; margin-right: 6px; color: #4ade80;"></i> <?= htmlspecialchars($item['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?></span>
                                                    <a href="/Cycsa/publico/operaciones/detalle-lote?id_lote=<?= $item['id_lote'] ?>" class="btn-accion btn-detalle" style="padding: 4px 10px; font-size: 11px; background: #e0f2fe; color: #0369a1; border-color: #bae6fd; font-family: monospace; font-weight: 700;" title="Ver lote y rupturas en Laboratorio">
                                                        <?= htmlspecialchars($item['codigo_muestra'], ENT_QUOTES, 'UTF-8') ?> <i class="fa-solid fa-chevron-right" style="font-size: 8px;"></i>
                                                    </a>
                                                </div>
                                            <?php 
                                                endif;
                                            endforeach; 
                                            if (!$hasReceived):
                                            ?>
                                                <div style="color: var(--color-slate-600); font-size: 12.5px; font-style: italic; padding: 8px 0;"><i class="fa-solid fa-circle-exclamation" style="margin-right: 6px; color: var(--color-warning);"></i> Ninguna muestra ingresada en el laboratorio todavía.</div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($ordenes)): ?>
                    <tr>
                        <td colspan="6" style="text-align: center; padding: 40px; color: var(--color-slate-600);">No se encontraron órdenes de servicio activas.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>



</div>

<!-- MODAL DECISIÓN SUPERVISOR (Fase 1 a Fase 2) -->
<div id="modalRevision" class="modal-premium">
    <div class="modal-premium-content" style="width: 40%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: var(--color-slate-900); font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700;">Revisión de Orden de Servicio: <span id="rev_codigo_os" style="color:var(--cycsa-azul);"></span></h3>
            <button onclick="cerrarModalRevision()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/operaciones/actualizar-estado" id="form-revision-os">
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

            <div style="display: flex; justify-content: space-between; gap: 15px; margin-top: 25px; border-top:1px solid var(--color-slate-100); padding-top:15px;">
                <button type="button" onclick="ejecutarObservacionOS()" class="btn-accion btn-danger" style="cursor:pointer;"><i class="fa-solid fa-triangle-exclamation"></i> Observar / Rechazar</button>
                <div style="display:flex; gap:10px;">
                    <button type="button" onclick="cerrarModalRevision()" class="btn-accion btn-detalle" style="cursor:pointer; margin:0;">Cancelar</button>
                    <button type="button" onclick="ejecutarAprobacionOS()" class="btn-accion btn-primary" id="btn-aprobar-submit" style="display:none; cursor:pointer;"><i class="fa-solid fa-circle-check"></i> Aprobar y Continuar</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL PROGRAMACIÓN MUESTREO (Fase 2) -->
<div id="modalMuestreo" class="modal-premium">
    <div class="modal-premium-content" style="width: 40%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: var(--color-slate-900); font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700;">Programación de Muestreo: <span id="mues_codigo_os" style="color:var(--cycsa-azul);"></span></h3>
            <button onclick="cerrarModalMuestreo()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/operaciones/programar-muestreo">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_os" id="mues_id_os">
            
            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label>Fecha de Muestreo</label>
                    <input type="date" name="fecha_muestreo" required class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label>Hora de Muestreo</label>
                    <input type="time" name="hora_muestreo" class="form-control" value="08:00">
                </div>
            </div>
            
            <div class="form-group">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label>Técnico de Muestreo Asignado</label>
                    <a href="/Cycsa/publico/configuracion" style="font-size:11px; color:var(--cycsa-azul); text-decoration:underline;" target="_blank"><i class="fa-solid fa-plus-circle"></i> Gestionar Técnicos</a>
                </div>
                <select name="tecnico_muestreo" required class="form-control" style="font-size:13px; padding:10px 14px;">
                    <option value="">-- Seleccionar Técnico --</option>
                    <?php foreach ($tecnicos as $t): ?>
                        <option value="<?= htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label>Vehículo de Muestreo Asignado</label>
                    <a href="/Cycsa/publico/configuracion" style="font-size:11px; color:var(--cycsa-azul); text-decoration:underline;" target="_blank"><i class="fa-solid fa-plus-circle"></i> Gestionar Vehículos</a>
                </div>
                <select name="vehiculo_muestreo" required class="form-control" style="font-size:13px; padding:10px 14px;">
                    <option value="">-- Seleccionar Vehículo --</option>
                    <?php foreach ($vehiculos as $v): ?>
                        <?php 
                        $lblVehiculo = $v['placa'] . (!empty($v['marca']) ? ' - ' . $v['marca'] . ' ' . $v['modelo'] : '');
                        ?>
                        <option value="<?= htmlspecialchars($v['placa'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($lblVehiculo, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="cerrarModalMuestreo()" class="btn-accion btn-detalle" style="cursor:pointer; margin:0;">Cancelar</button>
                <button type="submit" class="btn-accion btn-primary" style="cursor:pointer;">Guardar y Programar</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL REGISTRAR HOJA DE CAMPO (CYCSA-RT-FM-07) -->
<div id="modalHojaCampo" class="modal-premium">
    <div class="modal-premium-content" style="width: 40%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: var(--color-slate-900); font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700;">Registrar Hoja de Campo (CYCSA-RT-FM-07): <span id="field_codigo_os" style="color:var(--cycsa-azul);"></span></h3>
            <button onclick="cerrarModalHojaCampo()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/operaciones/guardar-hoja-campo">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_os" id="field_id_os">
            
            <div style="background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 10px 14px; border-radius: 8px; font-size: 12px; margin-bottom: 18px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-wand-magic-sparkles"></i>
                <span><strong>AUTOCAPTURA DE CAMPO:</strong> El código consecutivo y el operador asignado han sido precargados automáticamente. Puede ajustar las horas de espera libremente (ej: 11h, 12h, 24h) o colocar 0h para ingreso inmediato.</span>
            </div>

            <div class="form-group">
                <label>Código de Hoja de Campo (Autogenerado consecutivo)</label>
                <input type="text" name="hoja_campo_codigo" id="field_hoja_campo_codigo" required class="form-control" placeholder="Ej: CYCSA-RT-FM-07-001">
            </div>
            
            <div class="form-group">
                <label>Operador / Muestreador Responsable (Autocapturado)</label>
                <input type="text" name="hoja_campo_operador" id="field_hoja_campo_operador" list="lista-tecnicos-cycsa" required class="form-control" placeholder="Seleccione técnico o escriba nombre completo">
            </div>

            <div class="form-group">
                <label style="font-weight: 600; color: #1e293b;">Horas de Espera Requeridas (Elegibles libremente)</label>
                <input type="number" name="horas_espera_requeridas" id="field_horas_espera" class="form-control" value="24" min="0" max="168" placeholder="Ej: 24, 11, 12, 6...">
                <span style="font-size: 11.5px; color: #64748b; margin-top: 3px; display: block;">Puede indicar 24h por defecto, 11h, 12h, 6h o las horas exactas que el equipo decida. Si indica 0h, el ingreso se habilita de inmediato.</span>
            </div>
            
            <div class="form-group">
                <label>Notas / Observaciones del Muestreo</label>
                <textarea name="hoja_campo_notas" id="field_hoja_campo_notas" class="form-control" rows="2" placeholder="Describa el estado climático, novedades del sitio u otras notas..."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="cerrarModalHojaCampo()" class="btn-accion btn-detalle" style="cursor:pointer; margin:0;">Cancelar</button>
                <button type="submit" class="btn-accion btn-primary" style="cursor:pointer;"><i class="fa-solid fa-save"></i> Guardar Hoja de Campo</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL REVISIÓN DE RESULTADOS CALIDAD (Fase 4) -->
<div id="modalRevisionResultados" class="modal-premium">
    <div class="modal-premium-content" style="width: 40%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: var(--color-slate-900); font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700;">Revisión de Calidad (Resultados): O/S <span id="qc_codigo_os" style="color:var(--cycsa-azul);"></span></h3>
            <button onclick="cerrarModalRevisionResultados()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/operaciones/procesar-revision-resultados">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_os" id="qc_id_os">
            <input type="hidden" name="decision" id="qc_decision" value="">
            
            <p style="font-size: 13.5px; color:#475569; margin-bottom: 20px;">Como supervisor de laboratorio, valide si los resultados cargados por el técnico cumplen la normativa correspondiente para proceder con el cierre.</p>

            <div class="form-group" id="qc-group-obs" style="display:none;">
                <label style="font-weight: 700; font-size: 13px; color: #b91c1c; display: block; margin-bottom: 5px;">Detalle los Resultados Observados (Requerido para devolución)</label>
                <textarea name="motivo_observacion" id="qc_motivo" class="form-control" rows="3" placeholder="Ej: Hay un error en la carga del cilindro B de 7 días, re-evaluar..."></textarea>
            </div>

            <div style="display: flex; justify-content: space-between; gap: 15px; margin-top: 25px; border-top:1px solid var(--color-slate-100); padding-top:15px;">
                <button type="button" onclick="ejecutarQCObservar()" class="btn-accion btn-danger" style="cursor:pointer;"><i class="fa-solid fa-triangle-exclamation"></i> Devolver / Observar</button>
                <div style="display:flex; gap:10px;">
                    <button type="button" onclick="cerrarModalRevisionResultados()" class="btn-accion btn-detalle" style="cursor:pointer; margin:0;">Cancelar</button>
                    <button type="button" onclick="ejecutarQCAprobar()" class="btn-accion btn-primary" id="btn-qc-aprobar" style="background:#10b981; border-color:#10b981; cursor:pointer;"><i class="fa-solid fa-circle-check"></i> Aprobar y Cierre O/S</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL REGISTRAR/EDITAR HOJA DE SOLICITUD DE SERVICIO (CYCSA-RT-FM-13) -->
<div id="modalHojaSolicitud" class="modal-premium" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">
    <div class="modal-premium-content" style="width: 80%; max-width: 850px; max-height: 85vh; overflow-y: auto; padding: 25px 35px; background: white; margin: 5% auto; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1px solid var(--color-slate-100); padding-bottom: 12px;">
            <div>
                <h3 style="margin: 0; color: var(--color-slate-900); font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700;">Hoja de Solicitud de Servicio (Ingreso)</h3>
                <p style="margin: 3px 0 0 0; font-size: 12px; color: var(--color-slate-500);">Documento CYCSA-RT-FM-13 vinculado a la O/S: <strong id="hs_codigo_os_label" style="color:var(--cycsa-azul);"></strong></p>
            </div>
            <button onclick="cerrarModalHojaSolicitud()" class="btn-cerrar" style="background:none; border:none; font-size:24px; cursor:pointer; color:#64748b;">&times;</button>
        </div>
        
        <div id="loading-hoja-solicitud" style="display:none; text-align:center; padding: 40px;">
            <i class="fa-solid fa-spinner fa-spin" style="font-size:32px; color:var(--cycsa-azul);"></i>
            <p style="color:var(--color-slate-600); margin-top:10px; font-size:14px;">Cargando datos de la O/S...</p>
        </div>

        <form method="POST" action="/Cycsa/publico/operaciones/guardar-hoja-solicitud" id="form-hoja-solicitud" style="display:none;">
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
                    <input type="text" name="codigo_documento" id="hs_codigo_documento" readonly class="form-control" style="background:#f1f5f9; font-weight:700; font-size:13px; padding:8px 12px;" value="CYCSA-RT-FM-01">
                </div>
            </div>

            <!-- 2. DATOS DEL CLIENTE -->
            <div style="font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;"><i class="fa-solid fa-user-tie"></i> 1. Empresa o Cliente que Solicita el Servicio</div>
            <div style="display:grid; grid-template-columns:1fr 1fr; gap:15px; margin-bottom: 12px;">
                <div class="form-group">
                    <label>Nombre Empresa/Cliente</label>
                    <input type="text" name="nombre_empresa_o_cliente" id="hs_nombre_empresa" required class="form-control" style="font-size:13px; padding:8px 12px;">
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
                    <label>Persona quien tomó la muestra</label>
                    <input type="text" name="nombre_persona_toma_muestra" id="hs_persona_toma" required class="form-control" style="font-size:13px; padding:8px 12px;">
                </div>
                <div class="form-group">
                    <label>Fecha y hora de toma muestra</label>
                    <input type="datetime-local" name="fecha_hora_toma_muestra" id="hs_fecha_toma" required class="form-control" style="font-size:13px; padding:8px 12px;">
                </div>
            </div>

            <!-- 4. IDENTIFICACIONES PROPIAS (TABLA DINÁMICA) -->
            <div style="display:flex; justify-content:space-between; align-items:center; font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;">
                <span><i class="fa-solid fa-list-ol"></i> 2. Identificaciones Propias de la Muestra (Especímenes)</span>
                <button type="button" class="btn-accion btn-os" style="padding:4px 8px; font-size:11px; cursor:pointer;" onclick="agregarFilaMuestraModal()"><i class="fa-solid fa-plus"></i> Agregar Muestra</button>
            </div>
            <table class="tabla-cycsa" style="width:100%; border-collapse:collapse; margin-bottom:20px;" id="hs-tabla-muestras">
                <thead>
                    <tr style="background:#f1f5f9;">
                        <th style="font-size:12px; padding:8px; text-align:left;">Nombre de la muestra</th>
                        <th style="font-size:12px; padding:8px; text-align:left;">Descripción</th>
                        <th style="font-size:12px; padding:8px; text-align:left;">Informaciones importantes</th>
                        <th style="width:40px; padding:8px; text-align:center;"></th>
                    </tr>
                </thead>
                <tbody id="hs-tbody-muestras">
                    <!-- Filas dinámicas se insertan aquí -->
                </tbody>
            </table>

            <!-- 5. PARÁMETROS SOLICITADOS -->
            <div style="font-family:'Outfit'; font-size:14px; font-weight:700; color:var(--cycsa-azul); border-bottom:1.5px solid #e2e8f0; padding-bottom:4px; margin-bottom:12px;"><i class="fa-solid fa-vials"></i> 3. Parámetros Solicitados</div>
            
            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:15px; margin-bottom:15px;">
                <h5 style="margin: 0 0 10px 0; font-family:'Outfit'; font-size:13.5px; color:var(--cycsa-azul);">3.1 Muestra de Concreto, Adoquines, Bloques</h5>
                <div style="display:flex; gap:15px; flex-wrap:wrap; margin-bottom:10px;">
                    <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                        <input type="checkbox" name="req_resistencia_concreto" value="1" id="hs_req_resistencia_concreto"> Resistencia de conc
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                        <input type="checkbox" name="req_resistencia_adoquin" value="1" id="hs_req_resistencia_adoquin"> Resistencia de adoquin
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                        <input type="checkbox" name="req_resistencia_bloques" value="1" id="hs_req_resistencia_bloques"> Resistencia bloques
                    </label>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;">Otros Concreto (Especificar)</label>
                    <input type="text" name="req_otros_concreto" id="hs_req_otros_concreto" class="form-control" style="font-size:13px; padding:8px 12px;" placeholder="Ej: Revenimiento, flexión...">
                </div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:15px; margin-bottom:15px;">
                <h5 style="margin: 0 0 10px 0; font-family:'Outfit'; font-size:13.5px; color:var(--cycsa-azul);">3.2 Muestras de Suelo</h5>
                <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(180px, 1fr)); gap:10px; margin-bottom:10px;">
                    <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                        <input type="checkbox" name="req_granulometria" value="1" id="hs_req_granulometria"> Granulometría
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                        <input type="checkbox" name="req_limites_atterberg" value="1" id="hs_req_limites_atterberg"> Límites de atterberg
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                        <input type="checkbox" name="req_humedad" value="1" id="hs_req_humedad"> Humedad
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                        <input type="checkbox" name="req_resistencia_corte" value="1" id="hs_req_resistencia_corte"> Resistencia al corte
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                        <input type="checkbox" name="req_clasificacion_sucs_hr" value="1" id="hs_req_clasificacion_sucs_hr"> Clasificación SUCS/HR
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                        <input type="checkbox" name="req_proctor_sm" value="1" id="hs_req_proctor_sm"> PROCTOR S/M
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                        <input type="checkbox" name="req_infiltracion" value="1" id="hs_req_infiltracion"> Infiltración
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                        <input type="checkbox" name="req_cbr" value="1" id="hs_req_cbr"> CBR
                    </label>
                    <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer;">
                        <input type="checkbox" name="req_densidad" value="1" id="hs_req_densidad"> Densidad
                    </label>
                </div>
                <div class="form-group">
                    <label style="font-size:12px;">Otros Suelos (Especificar)</label>
                    <input type="text" name="req_otros_suelo" id="hs_req_otros_suelo" class="form-control" style="font-size:13px; padding:8px 12px;" placeholder="Ej: Expansión, permeabilidad...">
                </div>
            </div>

            <div style="background:#f8fafc; border:1px solid #e2e8f0; border-radius:8px; padding:15px; margin-bottom:15px;">
                <h5 style="margin: 0 0 10px 0; font-family:'Outfit'; font-size:13.5px; color:var(--cycsa-azul);">3.3 Otros Materiales</h5>
                <label style="display:flex; align-items:center; gap:6px; font-size:13px; cursor:pointer; margin-bottom:10px;">
                    <input type="checkbox" name="req_otros_materiales" value="1" id="hs_req_otros_materiales"> Otro
                </label>
                <div class="form-group">
                    <label style="font-size:12px;">Detalle qué análisis necesita</label>
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

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px; border-top:1px solid var(--color-slate-100); padding-top:15px;">
                <button type="button" onclick="cerrarModalHojaSolicitud()" class="btn-accion btn-detalle" style="padding:8px 20px; font-size:13px; cursor:pointer;">Cancelar</button>
                <button type="submit" class="btn-accion btn-primary" style="padding:8px 20px; font-size:13px; cursor:pointer;"><i class="fa-solid fa-save"></i> Guardar y Generar PDF</button>
            </div>
        </form>
    </div>
</div>

<script>
    // Tab functionality
    function switchTab(tabId, btn) {
        document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('tab-content-active'));
        document.querySelectorAll('.tab-btn').forEach(b => {
            b.classList.remove('tab-btn-active');
            b.classList.add('tab-btn-inactive');
        });
        document.getElementById(tabId).classList.add('tab-content-active');
        btn.classList.add('tab-btn-active');
        btn.classList.remove('tab-btn-inactive');
        document.getElementById('lims-search').value = '';
        filtrarTabla('');
    }

    // Detail dropdown
    function toggleDetailOS(id, btn) {
        const detailRow = document.getElementById('os-detail-' + id);
        const parentRow = document.getElementById('os-row-' + id);
        const toggleBtn = parentRow.querySelector('.btn-toggle-detail');
        const icon = toggleBtn.querySelector('i');
        
        if (detailRow.style.display === 'none') {
            detailRow.style.display = 'table-row';
            icon.className = 'fa-solid fa-chevron-down';
            parentRow.style.backgroundColor = 'var(--color-slate-50)';
            toggleBtn.style.color = 'var(--cycsa-azul)';
            toggleBtn.style.backgroundColor = 'var(--color-slate-200)';
        } else {
            detailRow.style.display = 'none';
            icon.className = 'fa-solid fa-chevron-right';
            parentRow.style.backgroundColor = '';
            toggleBtn.style.color = 'var(--color-slate-600)';
            toggleBtn.style.backgroundColor = '';
        }
    }

    // Modal Revision Initial
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
            document.getElementById('card-muestreo-si').style.background = 'var(--primary-light)';
            document.getElementById('card-muestreo-no').style.borderColor = '#cbd5e1';
            document.getElementById('card-muestreo-no').style.background = 'white';
            
            // Si requiere, irá a Programación Muestreo
            document.getElementById('rev_nuevo_estado').value = 'Estado 3A: Programacion Muestreo';
        } else {
            document.getElementById('radio-muestreo-no').checked = true;
            document.getElementById('card-muestreo-no').style.borderColor = 'var(--cycsa-azul)';
            document.getElementById('card-muestreo-no').style.background = 'var(--primary-light)';
            document.getElementById('card-muestreo-si').style.borderColor = '#cbd5e1';
            document.getElementById('card-muestreo-si').style.background = 'white';
            
            // Si no requiere, irá a Ingreso Directo
            document.getElementById('rev_nuevo_estado').value = 'Estado 3: Ingreso Directo';
        }
    }
    function ejecutarObservacionOS() {
        const groupObs = document.getElementById('group-motivo-obs');
        if (groupObs.style.display === 'none') {
            groupObs.style.display = 'block';
            document.getElementById('rev_motivo').focus();
            document.getElementById('btn-aprobar-submit').style.display = 'none';
            
            // Quitar estilos de tarjetas
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

    // Modal Programar Muestreo
    const modMues = document.getElementById('modalMuestreo');
    function abrirModalMuestreo(id, code) {
        document.getElementById('mues_id_os').value = id;
        document.getElementById('mues_codigo_os').innerText = code;
        modMues.style.display = 'block';
    }
    function cerrarModalMuestreo() {
        modMues.style.display = 'none';
    }

    // Modal Hoja de Campo (CYCSA-RT-FM-07)
    const modField = document.getElementById('modalHojaCampo');
    function abrirModalHojaCampo(id, code, tecnico = '', autoCodigo = '', horas = 24) {
        document.getElementById('field_id_os').value = id;
        document.getElementById('field_codigo_os').innerText = code;
        if (autoCodigo) document.getElementById('field_hoja_campo_codigo').value = autoCodigo;
        if (tecnico) document.getElementById('field_hoja_campo_operador').value = tecnico;
        if (horas !== undefined && horas !== null) document.getElementById('field_horas_espera').value = horas;
        modField.style.display = 'block';
    }
    function cerrarModalHojaCampo() {
        modField.style.display = 'none';
    }

    // Modal QC Revisión Resultados
    const modQC = document.getElementById('modalRevisionResultados');
    function abrirModalRevisionResultados(id, code) {
        document.getElementById('qc_id_os').value = id;
        document.getElementById('qc_codigo_os').innerText = code;
        document.getElementById('qc-group-obs').style.display = 'none';
        document.getElementById('qc_motivo').value = '';
        document.getElementById('btn-qc-aprobar').style.display = 'inline-flex';
        modQC.style.display = 'block';
    }
    function cerrarModalRevisionResultados() {
        modQC.style.display = 'none';
    }
    function ejecutarQCAprobar() {
        document.getElementById('qc_decision').value = 'Aprobar';
        modQC.querySelector('form').submit();
    }
    function ejecutarQCObservar() {
        const group = document.getElementById('qc-group-obs');
        if (group.style.display === 'none') {
            group.style.display = 'block';
            document.getElementById('qc_motivo').focus();
            document.getElementById('btn-qc-aprobar').style.display = 'none';
        } else {
            const motivo = document.getElementById('qc_motivo').value.trim();
            if (motivo === '') {
                alert('Debe detallar los resultados observados para devolver el expediente.');
                document.getElementById('qc_motivo').focus();
                return;
            }
            document.getElementById('qc_decision').value = 'Rechazar';
            modQC.querySelector('form').submit();
        }
    }

    // Real-time search
    document.getElementById('lims-search').addEventListener('input', function(e) {
        filtrarTabla(e.target.value);
    });

    function filtrarTabla(query) {
        const val = query.toLowerCase().trim();
        const activeTab = document.querySelector('.tab-content-active');
        if (!activeTab) return;
        
        const rows = activeTab.querySelectorAll('tbody tr:not(.detalle-os-row)');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            const detailId = row.getAttribute('data-detail-id');
            
            if (text.includes(val)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
                if (detailId) {
                    const detailRow = document.getElementById(detailId);
                    if (detailRow) detailRow.style.display = 'none';
                    const toggleBtn = row.querySelector('.btn-toggle-detail');
                    if (toggleBtn) {
                        const icon = toggleBtn.querySelector('i');
                        if (icon) icon.className = 'fa-solid fa-chevron-right';
                        toggleBtn.style.color = 'var(--color-slate-600)';
                        toggleBtn.style.backgroundColor = '';
                    }
                }
            }
        });
    }

    // Hoja Solicitud Modal
    const modHS = document.getElementById('modalHojaSolicitud');
    const hsLoading = document.getElementById('loading-hoja-solicitud');
    const hsForm = document.getElementById('form-hoja-solicitud');

    function abrirModalHojaSolicitud(idOS, code) {
        document.getElementById('hs_codigo_os_label').innerText = code;
        modHS.style.display = 'block';
        hsLoading.style.display = 'block';
        hsForm.style.display = 'none';

        // Limpiar tabla dinámica de especímenes
        document.getElementById('hs-tbody-muestras').innerHTML = '';

        fetch('/Cycsa/publico/operaciones/hoja-solicitud-datos?id_os=' + idOS)
            .then(res => res.json())
            .then(data => {
                if (data.status === 'error') {
                    alert(data.message);
                    cerrarModalHojaSolicitud();
                    return;
                }
                
                const hoja = data.hoja;
                const os = data.os;

                // Llenar campos
                document.getElementById('hs_id_os').value = hoja.id_os;
                document.getElementById('hs_codigo_documento').value = hoja.codigo_documento || 'CYCSA-RT-FM-01';
                document.getElementById('hs_fecha_llegada').value = hoja.fecha_hora_llegada_laboratorio || '';
                document.getElementById('hs_nombre_empresa').value = hoja.nombre_empresa_o_cliente || '';
                document.getElementById('hs_direccion').value = hoja.direccion_proyecto || '';
                document.getElementById('hs_telefono').value = hoja.telefono || '';
                document.getElementById('hs_email').value = hoja.correo_electronico || '';
                document.getElementById('hs_persona_entrega').value = hoja.nombre_persona_entrega_muestra || '';
                document.getElementById('hs_procedencia').value = hoja.procedencia_punto_muestreo || '';
                document.getElementById('hs_persona_toma').value = hoja.nombre_persona_toma_muestra || '';
                document.getElementById('hs_fecha_toma').value = hoja.fecha_hora_toma_muestra || '';
                
                // Checkboxes de naturaleza
                const natureList = (hoja.naturaleza_muestra || '').split(',');
                document.querySelectorAll('.hs-nat-checkbox').forEach(cb => {
                    cb.checked = natureList.includes(cb.value);
                });

                // Parámetros de concreto
                document.getElementById('hs_req_resistencia_concreto').checked = parseInt(hoja.req_resistencia_concreto) === 1;
                document.getElementById('hs_req_resistencia_adoquin').checked = parseInt(hoja.req_resistencia_adoquin) === 1;
                document.getElementById('hs_req_resistencia_bloques').checked = parseInt(hoja.req_resistencia_bloques) === 1;
                document.getElementById('hs_req_otros_concreto').value = hoja.req_otros_concreto || '';

                // Parámetros de suelos
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

                // Footer / Cierre
                document.getElementById('hs_analisis_adicionales').value = hoja.analisis_adicionales || '';
                document.getElementById('hs_observaciones').value = hoja.observaciones || '';
                document.getElementById('hs_nombre_recibe').value = hoja.nombre_recibe_cycsa || '';
                document.getElementById('hs_firma_recibe_cycsa').checked = parseInt(hoja.firma_recibe_cycsa) === 1;
                document.getElementById('hs_firma_cliente').checked = parseInt(hoja.firma_cliente) === 1;

                // Cargar filas de especímenes
                let muestras = [];
                try {
                    muestras = JSON.parse(hoja.muestras_json || '[]');
                } catch(e) {
                    muestras = [];
                }
                
                siguienteConsecutivoMuestra = data.siguiente_consecutivo;
                anioActualMuestra = data.anio_actual;

                if (muestras.length === 0) {
                    agregarFilaMuestraModal('', 'Cilindros de concreto', 'Estándar');
                } else {
                    muestras.forEach(m => {
                        agregarFilaMuestraModal(m.nombre_muestra, m.descripcion, m.info_importante);
                    });
                }

                hsLoading.style.display = 'none';
                hsForm.style.display = 'block';
            })
            .catch(err => {
                console.error(err);
                alert('Error al conectar con el servidor.');
                cerrarModalHojaSolicitud();
            });
    }

    function cerrarModalHojaSolicitud() {
        modHS.style.display = 'none';
    }

    let siguienteConsecutivoMuestra = 1;
    let anioActualMuestra = new Date().getFullYear();

    function agregarFilaMuestraModal(nombre = '', desc = '', info = '') {
        if (nombre === '') {
            nombre = 'MC-' + String(siguienteConsecutivoMuestra).padStart(3, '0') + '-' + anioActualMuestra;
            siguienteConsecutivoMuestra++;
        }
        if (desc === '') {
            desc = 'Cilindros de concreto';
        }
        if (info === '') {
            info = 'Estándar';
        }
        const tbody = document.getElementById('hs-tbody-muestras');
        const tr = document.createElement('tr');
        tr.innerHTML = `
            <td style="padding:6px;"><input type="text" name="m_nombre[]" readonly class="form-control" style="width:100%; box-sizing:border-box; font-size:12.5px; padding:6px 10px; background:#f1f5f9; cursor:not-allowed;" value="${nombre}"></td>
            <td style="padding:6px;"><input type="text" name="m_desc[]" required class="form-control" style="width:100%; box-sizing:border-box; font-size:12.5px; padding:6px 10px;" value="${desc}"></td>
            <td style="padding:6px;"><input type="text" name="m_info[]" class="form-control" style="width:100%; box-sizing:border-box; font-size:12.5px; padding:6px 10px;" value="${info}"></td>
            <td style="width:40px; text-align:center; padding:6px;"><button type="button" class="btn-action btn-danger" style="padding:4px 8px; font-size:11px; cursor:pointer;" onclick="eliminarFilaMuestraModal(this)">&times;</button></td>
        `;
        tbody.appendChild(tr);
    }

    function eliminarFilaMuestraModal(btn) {
        const tbody = document.getElementById('hs-tbody-muestras');
        if (tbody.children.length > 1) {
            btn.closest('tr').remove();
        } else {
            alert('Debe ingresar al menos un espécimen.');
        }
    }

    window.addEventListener('click', (e) => {
        if (e.target === modRev) cerrarModalRevision();
        if (e.target === modMues) cerrarModalMuestreo();
        if (e.target === modField) cerrarModalHojaCampo();
        if (e.target === modQC) cerrarModalRevisionResultados();
        if (e.target === modHS) cerrarModalHojaSolicitud();
    });
</script>

<?php
$bitacora_modulo_nombre = 'Operaciones LIMS';
include __DIR__ . '/../../../plantillas/parciales/bitacora_modulo.php';
?>
