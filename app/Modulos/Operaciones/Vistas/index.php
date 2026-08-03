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
                        $totalItemsOS = count($o['items']);
                        $matricesLlenadas = 0;
                        foreach ($o['items'] as $it) {
                            if (!empty($it['resultados_json']) && $it['resultados_json'] !== '[]') {
                                $matricesLlenadas++;
                            }
                        }
                        $matrizCompleta = ($totalItemsOS > 0 && $matricesLlenadas === $totalItemsOS);
                        $tieneTecnico = !empty($o['tecnico_muestreo']);
                        $tieneHojaServicio = !empty($o['hoja_solicitud']);
                    ?>
                    <tr id="os-row-<?= $o['id'] ?>" data-detail-id="os-detail-<?= $o['id'] ?>">
                        <td style="text-align: center;">
                            <button class="btn-toggle-detail" onclick="toggleDetailOS(<?= $o['id'] ?>, this)" title="Ver Productos y Rellenar Matriz">
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
                            <?php if ($tieneTecnico): ?>
                                <div style="font-size: 12.5px; color: #1e293b;">
                                    <span style="font-weight: 700; color: var(--cycsa-azul);"><i class="fa-solid fa-user-check"></i> Técnico: <?= htmlspecialchars($o['tecnico_muestreo'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if (!empty($o['fecha_muestreo'])): ?>
                                        <br><span style="font-size: 11.5px; color: #64748b;"><i class="fa-solid fa-calendar-day"></i> Visita: <?= date('d/m/Y', strtotime($o['fecha_muestreo'])) ?> <?= $o['hora_muestreo'] ?></span>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <button type="button" 
                                        onclick="abrirModalMuestreo(<?= $o['id'] ?>, '<?= $o['codigo_os'] ?>')" 
                                        class="btn-accion btn-os" 
                                        style="padding: 6px 12px; font-size: 12px; background-color: #fffbeb; color: #b45309; border-color: #fef3c7;">
                                    <i class="fa-solid fa-user-plus"></i> Asignar Técnico de Visita
                                </button>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap; vertical-align: middle;">
                            <!-- BOTÓN ÚNICO: GENERAR HOJA DE LABORATORIO (Se habilita al rellenar matriz) -->
                            <?php if ($matrizCompleta): ?>
                                <a href="/Cycsa/publico/operaciones/recepcion?id_os=<?= $o['id'] ?>" 
                                   class="btn-accion btn-recepcion" 
                                   style="background-color: #10b981; color: white; border-color: #10b981; text-decoration: none; padding: 8px 16px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-paper-plane"></i> Generar Hoja de Laboratorio
                                </a>
                            <?php else: ?>
                                <button type="button" 
                                        onclick="intentarGenerarHojaLaboratorio(<?= $o['id'] ?>, '<?= $o['codigo_os'] ?>', <?= $tieneTecnico ? 'true' : 'false' ?>)" 
                                        class="btn-accion btn-detalle" 
                                        style="background-color: #f8fafc; color: #94a3b8; border-color: #cbd5e1; padding: 8px 16px; font-weight: 600; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                                    <i class="fa-solid fa-hourglass-half"></i> ⏳ Pendiente Llenar Matriz (<?= $matricesLlenadas ?>/<?= $totalItemsOS ?>)
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <!-- Sub-fila para detalles desplegables (Acordeón / Viñetas) -->
                    <tr class="detalle-os-row" id="os-detail-<?= $o['id'] ?>" style="display: none; background-color: #f8fafc;">
                        <td colspan="6" style="padding: 15px 25px; border-bottom: 1.5px solid #cbd5e1;">
                            <div class="detalle-os-card" style="background: white; border: 1px solid #cbd5e1; border-radius: 10px; padding: 18px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                                    <h4 style="margin: 0; font-family: 'Outfit'; font-size: 15px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                                        <i class="fa-solid fa-table-cells" style="color: var(--cycsa-azul);"></i> 
                                        Productos Cotizados - Rellenar Matriz Técnica de Campo
                                    </h4>
                                    <span style="font-size: 12px; color: #64748b; font-weight: 600;">
                                        Técnico: <strong><?= $tieneTecnico ? htmlspecialchars($o['tecnico_muestreo'], ENT_QUOTES, 'UTF-8') : 'Sin asignar' ?></strong>
                                    </span>
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <?php foreach ($o['items'] as $it): 
                                        $tieneRes = !empty($it['resultados_json']) && $it['resultados_json'] !== '[]';
                                    ?>
                                        <div style="display: flex; align-items: center; justify-content: space-between; background: #ffffff; border: 1px solid #e2e8f0; padding: 12px 16px; border-radius: 8px; transition: all 0.2s;">
                                            <div>
                                                <div style="font-weight: 700; color: #0f172a; font-size: 14px; display: flex; align-items: center; gap: 8px;">
                                                    <i class="fa-solid fa-flask" style="color: var(--cycsa-azul);"></i>
                                                    <?= htmlspecialchars($it['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?>
                                                </div>
                                                <?php if (!empty($it['norma_astm'])): ?>
                                                    <div style="font-size: 12px; color: #64748b; font-family: monospace; margin-top: 2px;">
                                                        Norma: <?= htmlspecialchars($it['norma_astm'], ENT_QUOTES, 'UTF-8') ?> &bull; Formato: <?= htmlspecialchars($it['formato_nombre'] ?? 'Matriz Técnica', ENT_QUOTES, 'UTF-8') ?>
                                                    </div>
                                                <?php endif; ?>
                                            </div>

                                            <div style="display: flex; align-items: center; gap: 12px;">
                                                <?php if ($tieneRes): ?>
                                                    <span style="background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; font-size: 11px; padding: 4px 10px; border-radius: 12px; font-weight: 700;">
                                                        <i class="fa-solid fa-circle-check"></i> CON RESULTADOS
                                                    </span>
                                                <?php else: ?>
                                                    <span style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 11px; padding: 4px 10px; border-radius: 12px; font-weight: 700;">
                                                        <i class="fa-solid fa-hourglass"></i> PENDIENTE MATRIZ
                                                    </span>
                                                <?php endif; ?>

                                                <?php if (!$tieneHojaServicio): ?>
                                                    <button type="button" 
                                                            onclick="intentarRellenarMatrizProducto(false, <?= $tieneTecnico ? 'true' : 'false' ?>, <?= $o['id'] ?>, '<?= $o['codigo_os'] ?>', <?= $it['id'] ?>)" 
                                                            class="btn-accion-hs btn-editar" 
                                                            style="padding: 7px 14px; font-size: 12.5px; font-weight: 700; border-radius: 6px; cursor: pointer; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca;">
                                                        <i class="fa-solid fa-lock"></i> Requiere Hoja de Servicio Primero
                                                    </button>
                                                <?php elseif (!$tieneTecnico): ?>
                                                    <button type="button" 
                                                            onclick="intentarRellenarMatrizProducto(true, false, <?= $o['id'] ?>, '<?= $o['codigo_os'] ?>', <?= $it['id'] ?>)" 
                                                            class="btn-accion-hs btn-editar" 
                                                            style="padding: 7px 14px; font-size: 12.5px; font-weight: 700; border-radius: 6px; cursor: pointer; background: #fffbeb; color: #b45309; border: 1px solid #fde68a;">
                                                        <i class="fa-solid fa-user-lock"></i> Asignar Técnico Primero
                                                    </button>
                                                <?php else: ?>
                                                    <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $it['id'] ?>" 
                                                       class="btn-accion-hs btn-registrar" 
                                                       style="text-decoration: none; padding: 7px 14px; font-size: 12.5px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                                                        <i class="fa-solid fa-pen-to-square"></i> <?= $tieneRes ? 'Editar Matriz' : 'Rellenar Matriz' ?>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
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

<!-- Supervisor decision modal moved to /hojas-servicio module -->

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
            
            <div class="form-group" style="margin-bottom:15px; background:#f8fafc; border:1px solid #e2e8f0; padding:12px; border-radius:8px;">
                <label style="font-weight:700; color:#1e293b; margin-bottom:8px; display:block;">Modalidad de Entrega / Muestreo:</label>
                <div style="display:flex; gap:20px;">
                    <label style="font-weight:600; cursor:pointer; font-size:13px; color:#0f172a; display:flex; align-items:center; gap:6px;">
                        <input type="radio" name="modalidad_muestreo" value="tecnico" checked onclick="toggleModalidadMuestreo('tecnico')">
                        👷‍♂️ Técnico CYCSA (Muestreo en Sitio)
                    </label>
                    <label style="font-weight:600; cursor:pointer; font-size:13px; color:#0f172a; display:flex; align-items:center; gap:6px;">
                        <input type="radio" name="modalidad_muestreo" value="cliente" onclick="toggleModalidadMuestreo('cliente')">
                        👤 Cliente Trajo Muestra a Lab
                    </label>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                <div class="form-group">
                    <label id="lbl_fecha_mues">Fecha de Muestreo / Recepción</label>
                    <input type="date" name="fecha_muestreo" id="input_fecha_mues" required class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="form-group">
                    <label id="lbl_hora_mues">Hora Exacta Registrada</label>
                    <input type="time" name="hora_muestreo" id="input_hora_mues" required class="form-control" value="<?= date('H:i') ?>">
                </div>
            </div>
            
            <div class="form-group" id="grp_tecnico_cycsa">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label>Técnico de Muestreo Asignado</label>
                    <a href="/Cycsa/publico/configuracion" style="font-size:11px; color:var(--cycsa-azul); text-decoration:underline;" target="_blank"><i class="fa-solid fa-plus-circle"></i> Gestionar Técnicos</a>
                </div>
                <select name="tecnico_muestreo_select" id="select_tecnico_muestreo" class="form-control" style="font-size:13px; padding:10px 14px;">
                    <option value="">-- Seleccionar Técnico --</option>
                    <?php foreach ($tecnicos as $t): ?>
                        <option value="<?= htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($t['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="grp_cliente_entrega" style="display:none;">
                <label>Nombre del Cliente / Entregante que trajo la Muestra</label>
                <input type="text" name="cliente_entrega_nombre" id="input_cliente_entrega" class="form-control" placeholder="Ej: Cliente (Entregado en Recepción por Ing. Carlos Ruiz)">
            </div>
            
            <div class="form-group" id="grp_vehiculo_cycsa">
                <div style="display:flex; justify-content:space-between; align-items:center;">
                    <label>Vehículo de Muestreo Asignado</label>
                    <a href="/Cycsa/publico/configuracion" style="font-size:11px; color:var(--cycsa-azul); text-decoration:underline;" target="_blank"><i class="fa-solid fa-plus-circle"></i> Gestionar Vehículos</a>
                </div>
                <select name="vehiculo_muestreo" id="select_vehiculo_muestreo" class="form-control" style="font-size:13px; padding:10px 14px;">
                    <option value="">-- Seleccionar Vehículo --</option>
                    <?php foreach ($vehiculos as $v): ?>
                        <?php 
                        $lblVehiculo = $v['placa'] . (!empty($v['marca']) ? ' - ' . $v['marca'] . ' ' . $v['modelo'] : '');
                        ?>
                        <option value="<?= htmlspecialchars($v['placa'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($lblVehiculo, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <input type="hidden" name="tecnico_muestreo" id="final_tecnico_muestreo" value="">

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="cerrarModalMuestreo()" class="btn-accion btn-detalle" style="cursor:pointer; margin:0;">Cancelar</button>
                <button type="submit" onclick="prepararSubmitMuestreo(event)" class="btn-accion btn-primary" style="cursor:pointer;">Guardar y Registrar en Hoja de Servicio</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModalidadMuestreo(tipo) {
        const grpTec = document.getElementById('grp_tecnico_cycsa');
        const grpCli = document.getElementById('grp_cliente_entrega');
        const grpVeh = document.getElementById('grp_vehiculo_cycsa');
        const selTec = document.getElementById('select_tecnico_muestreo');
        const selVeh = document.getElementById('select_vehiculo_muestreo');

        if (tipo === 'cliente') {
            grpTec.style.display = 'none';
            grpVeh.style.display = 'none';
            grpCli.style.display = 'block';
            selTec.removeAttribute('required');
            selVeh.removeAttribute('required');
        } else {
            grpTec.style.display = 'block';
            grpVeh.style.display = 'block';
            grpCli.style.display = 'none';
            selTec.setAttribute('required', 'required');
            selVeh.setAttribute('required', 'required');
        }
    }

    function prepararSubmitMuestreo(e) {
        const mod = document.querySelector('input[name="modalidad_muestreo"]:checked').value;
        const finalInput = document.getElementById('final_tecnico_muestreo');

        if (mod === 'cliente') {
            const nomCli = document.getElementById('input_cliente_entrega').value.trim();
            finalInput.value = nomCli !== '' ? ('Cliente: ' + nomCli) : 'Cliente (Entregado en Recepción)';
        } else {
            finalInput.value = document.getElementById('select_tecnico_muestreo').value;
        }
    }
</script>
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

<!-- Modal Hoja Solicitud moved to /hojas-servicio module -->

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

    function intentarGenerarHojaLaboratorio(idOS, codigoOS, tieneTecnico) {
        if (!tieneTecnico) {
            alert('Debe asignar primero un técnico muestreador de visita antes de continuar.');
            abrirModalMuestreo(idOS, codigoOS);
            return;
        }
        alert('Debe rellenar primero la matriz técnica de los productos cotizados para habilitar la Hoja de Laboratorio.');
        abrirModalMatrizEnsayosOS(idOS, codigoOS);
    }

    function intentarRellenarMatrizProducto(tieneHoja, tieneTecnico, idOS, codigoOS, idDetalle) {
        if (!tieneHoja) {
            alert('Debe llenar y registrar primero la Hoja de Servicio (CYCSA-RT-FM-13) en el módulo de Hojas de Servicio antes de rellenar la matriz técnica.');
            window.location.href = '/Cycsa/publico/hojas-servicio';
            return;
        }
        if (!tieneTecnico) {
            alert('Debe asignar primero un técnico muestreador de visita antes de rellenar la matriz del producto.');
            abrirModalMuestreo(idOS, codigoOS);
            return;
        }
        abrirModalMatrizEnsayosOS(idOS, codigoOS);
    }

    function toggleDetailOS(idOS, btn) {
        const detailRow = document.getElementById('os-detail-' + idOS);
        if (!detailRow) return;
        const icon = btn.querySelector('i');
        
        if (detailRow.style.display === 'none' || detailRow.style.display === '') {
            detailRow.style.display = 'table-row';
            if (icon) icon.className = 'fa-solid fa-chevron-down';
            btn.style.color = 'var(--cycsa-azul)';
            btn.style.backgroundColor = '#eff6ff';
        } else {
            detailRow.style.display = 'none';
            if (icon) icon.className = 'fa-solid fa-chevron-right';
            btn.style.color = 'var(--color-slate-600)';
            btn.style.backgroundColor = '';
        }
    }

    // (Modal Revisión movido a /hojas-servicio - ver HojasServicioControlador.php)

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

    // (Hoja Solicitud Modal movido a /hojas-servicio - ver HojasServicioControlador.php)

    window.addEventListener('click', (e) => {
        if (e.target === modMues) cerrarModalMuestreo();
        if (e.target === modField) cerrarModalHojaCampo();
        if (e.target === modQC) cerrarModalRevisionResultados();
        if (e.target === document.getElementById('modalMatrizEnsayosOS')) cerrarModalMatrizEnsayosOS();
    });

    function abrirModalMatrizEnsayosOS(idOS, code) {
        document.getElementById('m_matriz_codigo_os').innerText = code;
        document.getElementById('m_matriz_loading').style.display = 'block';
        document.getElementById('m_matriz_contenido').style.display = 'none';
        document.getElementById('modalMatrizEnsayosOS').style.display = 'block';

        fetch('/Cycsa/publico/operaciones/obtener-matriz-os?id_os=' + idOS)
            .then(res => res.json())
            .then(data => {
                document.getElementById('m_matriz_loading').style.display = 'none';
                if (data.status !== 'success' || !data.items) {
                    alert(data.message || 'Error al obtener matriz de la O/S');
                    return;
                }

                const tbody = document.getElementById('tbody-matriz-items-os');
                tbody.innerHTML = '';

                if (data.items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center; padding:20px; color:#64748b;">No hay ensayos registrados para esta Orden de Servicio.</td></tr>';
                } else {
                    const tieneTecnicoOS = !!(data.os && data.os.tecnico_muestreo);
                    data.items.forEach(it => {
                        const tr = document.createElement('tr');
                        tr.style.borderBottom = '1px solid #f1f5f9';
                        
                        const tieneResultados = it.resultados_json && it.resultados_json !== '[]';
                        const badgeHtml = tieneResultados 
                            ? '<span style="background-color:#dcfce7; color:#15803d; border:1px solid #bbf7d0; margin-right:8px; font-size:11px; padding:4px 8px; border-radius:12px; font-weight:700;"><i class="fa-solid fa-circle-check"></i> CON RESULTADOS</span>'
                            : '<span style="background-color:#f1f5f9; color:#475569; border:1px solid #cbd5e1; margin-right:8px; font-size:11px; padding:4px 8px; border-radius:12px; font-weight:700;"><i class="fa-solid fa-hourglass"></i> PENDIENTE</span>';

                        const btnMatrizHtml = tieneTecnicoOS
                            ? `<a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=${it.id}" class="btn-accion-hs btn-registrar" style="text-decoration:none; padding:7px 14px; font-size:12px; font-weight:700; border-radius:6px; display:inline-flex; align-items:center; gap:6px;"><i class="fa-solid fa-pen-to-square"></i> ${tieneResultados ? 'Editar Matriz' : 'Capturar Matriz'}</a>`
                            : `<button type="button" onclick="intentarRellenarMatrizProducto(false, ${idOS}, '${code}', ${it.id})" class="btn-accion-hs btn-editar" style="padding:7px 14px; font-size:12px; font-weight:700; border-radius:6px; cursor:pointer; background:#fffbeb; color:#b45309; border:1px solid #fde68a;"><i class="fa-solid fa-user-lock"></i> Asignar Técnico Primero</button>`;

                        tr.innerHTML = `
                            <td style="padding:12px 14px; font-weight:700; color:#0f172a;">${it.descripcion_ensayo}</td>
                            <td style="padding:12px 14px; color:#475569; font-family:monospace; font-size:12.5px;">${it.norma_astm || 'N/A'}</td>
                            <td style="padding:12px 14px; font-weight:500; color:#334155;">${it.formato_nombre || 'Matriz Técnica'}</td>
                            <td style="padding:12px 14px; font-weight:700; text-align:center;">${parseInt(it.cantidad || 1)}</td>
                            <td style="padding:12px 14px; text-align:right; white-space:nowrap;">
                                ${badgeHtml}
                                ${btnMatrizHtml}
                            </td>
                        `;
                        tbody.appendChild(tr);
                    });
                }

                document.getElementById('m_matriz_contenido').style.display = 'block';
            })
            .catch(err => {
                console.error(err);
                alert('Error al conectar con el servidor.');
                cerrarModalMatrizEnsayosOS();
            });
    }

    function escapeHtml(text) {
        return (text || '').replace(/'/g, "\\'").replace(/"/g, '&quot;');
    }
    function escapeJson(jsonStr) {
        if (!jsonStr) return "'[]'";
        return "'" + jsonStr.replace(/'/g, "\\'") + "'";
    }

    function cerrarModalMatrizEnsayosOS() {
        document.getElementById('modalMatrizEnsayosOS').style.display = 'none';
    }

    // Modal de Resultados Directos por Producto (Schemas de Ensayos CYCSA)
    const FORMATOS_SCHEMA_INDEX = {
        "compactacion_densimetro_nuclear.md": {"columns": ["Código laboratorio", "Nombre muestra", "Capa (N°)", "Espesor (cm)", "Profundidad (cm)", "P.V.S Max (kg/m³)", "Humedad Óptima ((% )(P/P))", "P.V.S.Sitio (kg/cm²)", "Humedad Sitio ((%) (P/P))", "Humedad Sitio (kg/m³)", "Compactación ((%) (P/P))", "Fecha de muestreo"]},
        "ensayos_varios.md": {"columns": ["Código laboratorio", "Nombre muestra", "Color", "PVSS (kg/m³)", "PVSC (kg/m³)", "Módulo de finura ((%) (P/P))", "Humedad Natural ((%) (P/P))", "Absorción ((%)(P/P))", "Gravedad Específica.", "Gravedad Específica (SSS)", "Gravedad Específica Aparente"]},
        "formato_de_compactacion_por_cono_de_arena.md": {"columns": ["Código laboratorio", "Nombre muestra", "Capa (N°)", "Espesor (cm)", "Profundidad (cm)", "P.V.S Max (kg/m³)", "Humedad Óptima ((% )(P/P))", "P.V.S.Sitio (kg/m³)", "Humedad sitio ((%) (P/P))", "Humedad Sitio (kg/m³)", "Compactación ((%) (P/P))", "Fecha de muestreo"]},
        "formato_de_compactacion_por_reemplazo_de_agua_no_acreditado.md": {"columns": ["Código laboratorio", "Nombre muestra", "Capa (N°)", "Espesor (cm)", "Profundidad (cm)", "P.V.S Max (kg/m³)", "Humedad Óptima (( %)(P/P))", "P.V.S.Sitio (kg/m³)", "Humedad sitio ((%) (P/P))", "Humedad Sitio (kg/m³)", "Compactación ((%) (P/P))", "Fecha de muestreo"]},
        "formato_de_equivalente_de_arena.md": {"columns": ["Código laboratorio", "Nombre muestra", "Equivalente de arena. ((%)(P/P))"]},
        "formato_de_granulometria_de_suelo.md": {"columns": ["Malla", "P. Retenido parcial (gr)", "% Retenido parcial", "% Acumulativo", "% que pasa la malla", "Límite Mín", "Límite Máx"]},
        "formato_de_lodo_concreto.md": {"columns": ["Código laboratorio", "Nombre muestra", "Área (cm²)", "Carga (kg)", "Peso Volumétrico (kg/m³)", "Diseño (kg/cm²)", "R. compresión. (kg/cm²)", "Fecha de Fabricación", "Fecha de Ensayo", "Edad (Días)"]},
        "formato_de_particulas_desmezurables.md": {"columns": ["Código laboratorio", "Nombre muestra", "Terrones de arcilla. ((%)(P/P))"]},
        "formato_de_resistencia_de_a_la_flexion.md": {"columns": ["Código laboratorio", "Nombre muestra", "Ancho Promedio (in)", "Espesor Promedio (in)", "Longitud de Apoyo (in)", "Reven. (in)", "Reven. (cm)", "Temp. (°C)", "Carga (lb)", "Peso volumétrico (kg/m³)", "Diseño MR (kg/cm²)", "Resistencia a la flexión. (kg/cm²)", "Fecha de Fabricación", "Fecha de Ensayo", "Edad (Días)"]},
        "formato_de_resistencia_de_bloques.md": {"columns": ["Descripción", "Método de ensayo", "Unidad", "Resultado", "Min", "Max"]},
        "formato_de_reveniemiento_y_temperatura.md": {"columns": ["Código laboratorio", "Nombre muestra", "Reven. (in)", "Reven. (cm)", "Temp. (°C)", "Diseño de concreto (lb/in²)", "Fecha de Fabricación", "Fecha de Finalización"]},
        "formato_de_suelo_cemento.md": {"columns": ["Código laboratorio", "Nombre muestra", "Color"]},
        "granulomnetria_de_agregados.md": {"columns": ["Malla", "P. Retenido parcial (gr)", "% Retenido parcial", "% Acumulativo", "% que pasa la malla", "Límite Mín", "Límite Máx"]},
        "pesos_volumetricos.md": {"columns": ["Código laboratorio", "Nombre muestra", "P.V.S.S (kg/m³)", "P.V.S.C (kg/m³)", "Humedad Natural (%)"]},
        "proctor_estandar.md": {"columns": ["Muestra", "P.V.S Max (kg/m³)", "Humedad Óptima (%)"]},
        "resistencia_de_adoquines.md": {"columns": ["Código laboratorio", "Nombre muestra", "Área (in²)", "Carga (lb)", "Peso Volumétrico (kg/m³)", "Diseño (lb/in²)", "R. Compresión (lb/in²)", "R. Compresión. (kg/cm²)", "Absorción ((%)(P/P))", "Fecha de Fabricación", "Fecha de Ensayo", "Edad (Días)"]},
        "resistencia_de_concreto.md": {"columns": ["Código laboratorio", "Nombre muestra", "Área (in²)", "Reven. (in)", "Reven. (cm)", "Temp. (°C)", "Carga (lb)", "Peso Volumétrico (kg/m³)", "Diseño de concreto (lb/in²)", "R. Compresión (lb/in²)", "R. Compresión (kg/cm²)", "Fecha de Fabricación", "Fecha de Ensayo", "Edad (Días)"]},
        "resistencia_de_ladrillo.md": {"columns": ["Código laboratorio", "Nombre muestra", "Área (cm²)", "Carga (kg)", "Peso Volumétrico (kg/m³)", "R. a la compresión (kg/cm²)", "Fecha de Fabricación", "Fecha de Ensayo", "Edad (Días)"]},
        "resistencia_de_martillo_suizo.md": {"columns": ["Código laboratorio", "Nombre muestra", "Diseño de Concreto (lb/in²)", "Forma de Prueba", "Promedio de Rebotes (N°)", "Estimación R. compresión (lb/in²)", "Estimación R. compresión (kg/cm²)", "Fecha de Fabricación", "Edad (Días)"]},
        "resistencia_de_mortero.md": {"columns": ["Código laboratorio", "Nombre muestra", "Área (in²)", "Carga (lb)", "Peso Volumétrico (kg/m³)", "Diseño de Mortero (lb/in²)", "R. Compresión (lb/in²)", "R. Compresión. (kg/cm²)", "Fecha de Fabricación", "Fecha de Ensayo", "Edad (Días)"]},
        "resistencia_de_nucleo_de_concreto.md": {"columns": ["Código laboratorio", "Nombre muestra", "Área (in²)", "Carga (lb)", "Peso Volumétrico (kg/m³)", "Diseño de concreto (lb/in²)", "R. Compresión (lb/in²)", "R. Compresión (kg/cm²)", "Fecha de Fabricación", "Fecha de Ruptura", "Edad (Días)"]}
    };

    let columnasDirectas = [];

    function abrirModalResultadosDirecto(idDetalle, nombreEnsayo, archivoMarkdown, resultadosJsonStr) {
        document.getElementById('m_res_id_detalle').value = idDetalle;
        document.getElementById('m_res_titulo_ensayo').innerText = 'Capturar Matriz Técnica: ' + nombreEnsayo;
        
        const schema = FORMATOS_SCHEMA_INDEX[archivoMarkdown] || { columns: [] };
        columnasDirectas = schema.columns;
        if (columnasDirectas.length === 0) {
            columnasDirectas = ["Código laboratorio", "Nombre muestra", "Lectura / Resultado"];
        }

        // Render Header
        const headerRow = document.createElement('tr');
        columnasDirectas.forEach(col => {
            const th = document.createElement('th');
            th.innerText = col;
            th.style.padding = '12px 10px';
            th.style.fontSize = '11.5px';
            th.style.backgroundColor = '#f8fafc';
            th.style.color = '#475569';
            th.style.borderBottom = '2px solid #e2e8f0';
            headerRow.appendChild(th);
        });
        const headerContainer = document.getElementById('tabla-directa-header');
        headerContainer.innerHTML = '';
        headerContainer.appendChild(headerRow);

        // Render Body
        const bodyContainer = document.getElementById('tabla-directa-body');
        bodyContainer.innerHTML = '';

        let data = [];
        try {
            data = (typeof resultadosJsonStr === 'string') ? JSON.parse(resultadosJsonStr) : (resultadosJsonStr || []);
        } catch(e) {
            data = [];
        }

        if (data.length === 0) {
            agregarFilaDirecta();
        } else {
            data.forEach(row => {
                const tr = document.createElement('tr');
                columnasDirectas.forEach(col => {
                    const td = document.createElement('td');
                    td.style.padding = '8px';
                    
                    const input = document.createElement('input');
                    input.type = 'text';
                    input.className = 'form-control';
                    input.style.width = '100%';
                    input.style.padding = '8px 12px';
                    input.style.fontSize = '13.5px';
                    input.style.borderRadius = '6px';
                    input.style.border = '1px solid #cbd5e1';
                    input.value = row[col] || '';
                    input.dataset.col = col;
                    
                    td.appendChild(input);
                    tr.appendChild(td);
                });
                bodyContainer.appendChild(tr);
            });
        }

        document.getElementById('modalResultadosDirecto').style.display = 'block';
    }

    function agregarFilaDirecta() {
        const bodyContainer = document.getElementById('tabla-directa-body');
        const tr = document.createElement('tr');
        
        columnasDirectas.forEach(col => {
            const td = document.createElement('td');
            td.style.padding = '8px';
            
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control';
            input.style.width = '100%';
            input.style.padding = '8px 12px';
            input.style.fontSize = '13.5px';
            input.style.borderRadius = '6px';
            input.style.border = '1px solid #cbd5e1';
            input.value = '';
            input.dataset.col = col;
            
            td.appendChild(input);
            tr.appendChild(td);
        });
        bodyContainer.appendChild(tr);
    }

    function cerrarModalResultadosDirecto() {
        document.getElementById('modalResultadosDirecto').style.display = 'none';
    }

    function guardarFormularioMatrizDirecta(e) {
        e.preventDefault();
        const bodyContainer = document.getElementById('tabla-directa-body');
        const rows = bodyContainer.querySelectorAll('tr');
        const data = [];
        
        rows.forEach(tr => {
            const inputs = tr.querySelectorAll('input');
            let rowObj = {};
            let hasValue = false;
            
            inputs.forEach(input => {
                const col = input.dataset.col;
                const val = input.value.trim();
                rowObj[col] = val;
                if (val !== '') hasValue = true;
            });
            
            if (hasValue) {
                data.push(rowObj);
            }
        });

        document.getElementById('m_res_json_input').value = JSON.stringify(data);
        
        const form = document.getElementById('form-matriz-directa');
        const formData = new FormData(form);

        fetch('/Cycsa/publico/operaciones/guardar-matriz-resultados', {
            method: 'POST',
            body: formData
        })
        .then(res => res.json())
        .then(resData => {
            if (resData.status === 'success') {
                alert('Matriz técnica del producto guardada correctamente.');
                cerrarModalResultadosDirecto();
                // Refrescar el modal de matrices
                const idOS = document.getElementById('rev_id_os') ? document.getElementById('rev_id_os').value : 0;
                if (idOS > 0) {
                    abrirModalMatrizEnsayosOS(idOS, document.getElementById('m_matriz_codigo_os').innerText);
                } else {
                    location.reload();
                }
            } else {
                alert(resData.message || 'Error al guardar la matriz.');
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error al guardar datos en el servidor.');
        });

        return false;
    }
</script>

<!-- MODAL MATRIZ DE ENSAYOS Y RESULTADOS POR PRODUCTO (MATCHING CAPTURA DE PANTALLA) -->
<div id="modalMatrizEnsayosOS" class="modal-premium" style="display:none; position:fixed; z-index:9999; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.4);">
    <div class="modal-premium-content" style="width: 75%; max-width:1050px; background:white; margin:4% auto; padding:25px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.15);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 12px;">
            <h3 style="margin: 0; color: #1e293b; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-table-cells" style="color:var(--cycsa-azul);"></i> 
                Ensayos Solicitados y Matrices de Resultados - <span id="m_matriz_codigo_os" style="color:var(--cycsa-azul);"></span>
            </h3>
            <button onclick="cerrarModalMatrizEnsayosOS()" class="btn-cerrar" style="background:#f1f5f9; border:none; color:#64748b; width:32px; height:32px; border-radius:50%; font-size:18px; cursor:pointer; display:flex; align-items:center; justify-content:center; transition:all 0.2s;" title="Cerrar modal">&times;</button>
        </div>

        <div id="m_matriz_loading" style="text-align:center; padding:30px; color:#64748b;">
            <i class="fa-solid fa-spinner fa-spin fa-2x"></i>
            <p style="margin-top:10px; font-weight:600;">Cargando lista de ensayos y matrices de productos...</p>
        </div>

        <div id="m_matriz_contenido" style="display:none;">
            <p style="font-size:13.5px; color:#64748b; margin-bottom:15px;">
                Haga clic en <strong>"Capturar Matriz"</strong> en el producto correspondiente para ingresar las lecturas de campo/laboratorio.
            </p>
            <div style="overflow-x: auto; border: 1px solid #e2e8f0; border-radius: 10px;">
                <table class="hs-table" style="width:100%; border-collapse:collapse;" id="tabla-matriz-os-body">
                    <thead>
                        <tr style="background:#f8fafc; border-bottom:2px solid #e2e8f0; text-align:left;">
                            <th style="padding:12px 14px; font-size:12px; color:#475569; font-weight:700;">SERVICIO / ENSAYO</th>
                            <th style="padding:12px 14px; font-size:12px; color:#475569; font-weight:700;">NORMA / ASTM</th>
                            <th style="padding:12px 14px; font-size:12px; color:#475569; font-weight:700;">FORMATO DOCUMENTO</th>
                            <th style="padding:12px 14px; font-size:12px; color:#475569; font-weight:700; text-align:center;">CANTIDAD</th>
                            <th style="padding:12px 14px; font-size:12px; color:#475569; font-weight:700; text-align:right;">CAPTURA LIMS</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-matriz-items-os">
                        <!-- Se llena dinámicamente -->
                    </tbody>
                </table>
            </div>
        </div>

        <div style="display:flex; justify-content:flex-end; margin-top:20px; border-top:1px solid #e2e8f0; padding-top:15px;">
            <button type="button" onclick="cerrarModalMatrizEnsayosOS()" class="btn-accion-hs btn-editar" style="padding:9px 24px; font-size:13.5px; font-weight:600; cursor:pointer; background:#f1f5f9; border:1px solid #cbd5e1; color:#334155; border-radius:6px;">Cerrar</button>
        </div>
    </div>
</div>

<!-- MODAL DE CAPTURA DE MATRIZ DE RESULTADOS TÉCNICOS DE CADA PRODUCTO -->
<div id="modalResultadosDirecto" class="modal-premium" style="display:none; position:fixed; z-index:10000; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5);">
    <div class="modal-premium-content" style="width: 85%; max-width:1150px; background:white; margin:3% auto; padding:25px; border-radius:12px; box-shadow:0 4px 20px rgba(0,0,0,0.2);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; border-bottom: 1.5px solid #cbd5e1; padding-bottom: 10px;">
            <h3 style="margin: 0; color: #1e293b; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700;" id="m_res_titulo_ensayo">Capturar Matriz Técnica</h3>
            <button type="button" onclick="cerrarModalResultadosDirecto()" class="btn-cerrar">&times;</button>
        </div>

        <form id="form-matriz-directa" onsubmit="return guardarFormularioMatrizDirecta(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_detalle" id="m_res_id_detalle" value="">
            <input type="hidden" name="resultados_json" id="m_res_json_input" value="">

            <p style="color: #64748b; font-size: 13.5px; margin-bottom: 15px;">Ingrese los valores correspondientes en la matriz del ensayo de laboratorio. Deje celdas vacías si no requiere usarlas.</p>

            <div style="overflow-x: auto; width: 100%; border: 1px solid #cbd5e1; border-radius: 10px; margin-bottom: 20px;">
                <table class="hs-table" style="margin-top: 0; margin-bottom: 0; width:100%; border-collapse:collapse;">
                    <thead id="tabla-directa-header">
                        <!-- Columnas dinámicas -->
                    </thead>
                    <tbody id="tabla-directa-body">
                        <!-- Filas de inputs -->
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #cbd5e1; padding-top: 15px; margin-top: 15px;">
                <button type="button" class="btn-accion-hs btn-editar" style="cursor: pointer; padding: 8px 16px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;" onclick="agregarFilaDirecta()"><i class="fa-solid fa-plus"></i> Agregar Fila</button>
                <div style="display: flex; gap: 12px;">
                    <button type="button" onclick="cerrarModalResultadosDirecto()" class="btn-accion-hs btn-editar" style="cursor: pointer; padding: 8px 20px; font-weight: 600;">Cancelar</button>
                    <button type="submit" class="btn-accion-hs btn-registrar" style="cursor: pointer; padding: 8px 24px; font-weight: 600;"><i class="fa-solid fa-save"></i> Guardar Matriz del Producto</button>
                </div>
            </div>
        </form>
    </div>
</div>

<?php
$bitacora_modulo_nombre = 'Operaciones LIMS';
include dirname(__DIR__, 3) . '/Views/parciales/bitacora_modulo.php';
?>


