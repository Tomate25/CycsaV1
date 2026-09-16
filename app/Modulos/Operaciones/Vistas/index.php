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
                        <th>Modalidad / Muestreo</th>
                        <th style="text-align: center;">Facturación / Cobro</th>
                        <th style="text-align: right;">Matriz Técnica</th>
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
                        $requiereMuestreo = !empty($o['requiere_muestreo']);
                        $tieneMuestrasAceptadas = !empty($o['muestras_aceptadas_lab']) && $o['muestras_aceptadas_lab'] > 0;
                        
                        $cxcOS = $o['cxc'] ?? null;
                        $montoOS = (float)($o['cot_total'] ?? 0.0);
                        $saldoOS = $cxcOS ? (float)$cxcOS['saldo'] : $montoOS;
                        $estadoOS = $cxcOS ? $cxcOS['estado'] : 'Pendiente';
                        $pagadaOS = ($estadoOS === 'Pagado' || $saldoOS <= 0.01);
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
                            <?php if ($requiereMuestreo): ?>
                                <div>
                                    <span style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 4px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                                        <i class="fa-solid fa-truck-pickup"></i> Muestreo en Campo
                                    </span>
                                    <?php if ($tieneTecnico): ?>
                                        <div style="font-size: 12px; color: #1e293b; margin-top: 4px;">
                                            <strong style="color: #103487;"><i class="fa-solid fa-user-check"></i> <?= htmlspecialchars($o['tecnico_muestreo'], ENT_QUOTES, 'UTF-8') ?></strong>
                                            <?php if (!empty($o['fecha_muestreo'])): ?>
                                                <br><span style="font-size: 11px; color: #64748b;"><i class="fa-solid fa-calendar-day"></i> <?= date('d/m/Y', strtotime($o['fecha_muestreo'])) ?> <?= htmlspecialchars($o['hora_muestreo'] ?? '') ?></span>
                                            <?php endif; ?>
                                        </div>
                                    <?php else: ?>
                                        <div style="margin-top: 5px;">
                                            <button type="button" 
                                                    onclick="abrirModalMuestreo(<?= $o['id'] ?>, '<?= $o['codigo_os'] ?>')" 
                                                    class="btn-accion btn-os" 
                                                    style="padding: 4px 8px; font-size: 11px; background-color: #fffbeb; color: #b45309; border-color: #fef3c7;">
                                                <i class="fa-solid fa-user-plus"></i> Asignar Técnico Dedicado
                                            </button>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            <?php else: ?>
                                <div>
                                    <span style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 4px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 5px;">
                                        <i class="fa-solid fa-building-user"></i> Entregada por Cliente
                                    </span>
                                    <div style="font-size: 11px; color: #64748b; margin-top: 3px;">
                                        <i class="fa-solid fa-store"></i> Ingreso Directo en Lab Central
                                    </div>
                                </div>
                            <?php endif; ?>
                        </td>

                        <!-- Columna Facturación y Cobro -->
                        <td style="text-align: center; vertical-align: middle; white-space: nowrap;">
                            <div style="display: inline-flex; align-items: center; gap: 6px;">
                                <?php if ($pagadaOS): ?>
                                    <span style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; padding: 4px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fa-solid fa-circle-check"></i> Pagada (<?= htmlspecialchars($o['factura_numero']) ?>)
                                    </span>
                                    <a href="/Cycsa/publico/operaciones/imprimir-factura?id_os=<?= $o['id'] ?>" target="_blank" class="btn-accion-hs btn-pdf" style="text-decoration: none; padding: 5px 9px; font-size: 11.5px; display: inline-flex; align-items: center; gap: 4px;" title="Ver e Imprimir Factura Oficial">
                                        <i class="fa-solid fa-print"></i> Factura
                                    </a>
                                <?php elseif ($estadoOS === 'Parcial'): ?>
                                    <span style="background: #fffbeb; border: 1px solid #fde68a; color: #b45309; padding: 4px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fa-solid fa-clock"></i> Parcial (Saldo: C$ <?= number_format($saldoOS, 2) ?>)
                                    </span>
                                    <button type="button" 
                                            onclick="abrirModalFacturarOS(<?= $o['id'] ?>, '<?= htmlspecialchars(addslashes($o['codigo_os'])) ?>', '<?= htmlspecialchars(addslashes($o['factura_numero'])) ?>', <?= $saldoOS ?>, '<?= htmlspecialchars(addslashes($o['cliente_nombre'])) ?>', '<?= htmlspecialchars(addslashes($o['condicion_pago'] ?? '')) ?>')" 
                                            class="btn-accion-hs btn-registrar" 
                                            style="padding: 5px 10px; font-size: 11.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;" 
                                            title="Cobrar Saldo Pendiente">
                                        <i class="fa-solid fa-money-bill-transfer"></i> Abonar
                                    </button>
                                <?php else: ?>
                                    <span style="background: #f8fafc; border: 1px solid #cbd5e1; color: #475569; padding: 4px 9px; border-radius: 20px; font-size: 11.5px; font-weight: 700; display: inline-flex; align-items: center; gap: 4px;">
                                        <i class="fa-solid fa-hourglass"></i> Pendiente (C$ <?= number_format($montoOS, 2) ?>)
                                    </span>
                                    <button type="button" 
                                            onclick="abrirModalFacturarOS(<?= $o['id'] ?>, '<?= htmlspecialchars(addslashes($o['codigo_os'])) ?>', '<?= htmlspecialchars(addslashes($o['factura_numero'])) ?>', <?= $saldoOS ?>, '<?= htmlspecialchars(addslashes($o['cliente_nombre'])) ?>', '<?= htmlspecialchars(addslashes($o['condicion_pago'] ?? '')) ?>')" 
                                            class="btn-accion-hs btn-registrar" 
                                            style="background-color: #0f3b68; border-color: #0f3b68; color: white; padding: 5px 10px; font-size: 11.5px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px;" 
                                            title="Facturar y Registrar Cobro Inmediato (Efectivo o Transferencia)">
                                        <i class="fa-solid fa-file-invoice-dollar"></i> Facturar
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>

                        <td style="text-align: right; white-space: nowrap; vertical-align: middle;">
                            <?php 
                            $esSoloCompactacion = esOrdenSoloCompactacion($o['items']);
                            ?>
                            <?php if ($esSoloCompactacion): ?>
                                <span style="background: #e0f2fe; border: 1px solid #bae6fd; color: #0369a1; padding: 5px 10px; border-radius: 6px; font-weight: 700; font-size: 11.5px; display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="fa-solid fa-gauge-high"></i> Compactación In Situ
                                </span>
                            <?php elseif ($tieneMuestrasAceptadas): ?>
                                <span style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; padding: 5px 10px; border-radius: 6px; font-weight: 700; font-size: 11.5px; display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="fa-solid fa-circle-check"></i> Aprobado Lab
                                </span>
                            <?php elseif ($tieneHojaServicio): ?>
                                <span style="background: #fffbeb; border: 1px solid #fde68a; color: #b45309; padding: 5px 10px; border-radius: 6px; font-weight: 700; font-size: 11.5px; display: inline-flex; align-items: center; gap: 5px;">
                                    <i class="fa-solid fa-flask"></i> En Custodia Lab
                                </span>
                            <?php endif; ?>

                            <?php if ($matrizCompleta): ?>
                                <span style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 5px 10px; border-radius: 6px; font-weight: 700; font-size: 11.5px; display: inline-flex; align-items: center; gap: 5px; margin-left: 6px;">
                                    <i class="fa-solid fa-check-double"></i> Matriz Completa (<?= $matricesLlenadas ?>/<?= $totalItemsOS ?>)
                                </span>
                            <?php else: ?>
                                <button type="button" 
                                        onclick="toggleDetailOS(<?= $o['id'] ?>, document.querySelector('#os-row-<?= $o['id'] ?> .btn-toggle-detail'))" 
                                        class="btn-accion btn-detalle" 
                                        style="background-color: #f8fafc; color: #475569; border-color: #cbd5e1; padding: 5px 10px; font-weight: 600; border-radius: 6px; cursor: pointer; display: inline-flex; align-items: center; gap: 5px; font-size: 11.5px; margin-left: 6px;">
                                    <i class="fa-solid fa-table-cells"></i> Ensayos (<?= $matricesLlenadas ?>/<?= $totalItemsOS ?>)
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    
                    <!-- Sub-fila para detalles desplegables (Acordeón / Viñetas) -->
                    <tr class="detalle-os-row" id="os-detail-<?= $o['id'] ?>" style="display: none; background-color: #f8fafc;">
                        <td colspan="7" style="padding: 15px 25px; border-bottom: 1.5px solid #cbd5e1;">
                            <div class="detalle-os-card" style="background: white; border: 1px solid #cbd5e1; border-radius: 10px; padding: 18px; box-shadow: 0 2px 6px rgba(0,0,0,0.03);">
                                
                                <!-- APARTADO DE FACTURACIÓN Y REGISTRO CONTABLE DIARIO -->
                                <div style="background: #ffffff; border: 1.5px solid <?= $pagadaOS ? '#a7f3d0' : '#cbd5e1' ?>; border-radius: 8px; padding: 14px 18px; margin-bottom: 16px; box-shadow: 0 1px 4px rgba(0,0,0,0.03);">
                                    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px; margin-bottom: 10px; flex-wrap: wrap; gap: 10px;">
                                        <div style="font-family: 'Outfit'; font-size: 14px; font-weight: 700; color: #0f3b68; display: flex; align-items: center; gap: 8px;">
                                            <i class="fa-solid fa-file-invoice-dollar" style="color: #059669; font-size: 17px;"></i>
                                            Apartado de Facturación y Registro Contable Diario
                                        </div>
                                        <div style="display: flex; align-items: center; gap: 8px;">
                                            <?php if ($pagadaOS): ?>
                                                <span style="background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857; padding: 4px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 700;">
                                                    <i class="fa-solid fa-circle-check"></i> Factura Pagada (100%)
                                                </span>
                                            <?php elseif ($estadoOS === 'Parcial'): ?>
                                                <span style="background: #fffbeb; border: 1px solid #fde68a; color: #b45309; padding: 4px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 700;">
                                                    <i class="fa-solid fa-clock"></i> Cobro Parcial (Pendiente: C$ <?= number_format($saldoOS, 2) ?>)
                                                </span>
                                            <?php else: ?>
                                                <span style="background: #f8fafc; border: 1px solid #cbd5e1; color: #475569; padding: 4px 10px; border-radius: 12px; font-size: 11.5px; font-weight: 700;">
                                                    <i class="fa-solid fa-clock"></i> Pendiente de Cobro
                                                </span>
                                            <?php endif; ?>
                                            <a href="/Cycsa/publico/operaciones/imprimir-factura?id_os=<?= $o['id'] ?>" target="_blank" class="btn-accion-hs btn-pdf" style="text-decoration: none; padding: 6px 12px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;">
                                                <i class="fa-solid fa-print"></i> Ver / Imprimir Factura Oficial
                                            </a>
                                            <?php if (!$pagadaOS): ?>
                                                <button type="button" onclick="abrirModalFacturarOS(<?= $o['id'] ?>, '<?= htmlspecialchars(addslashes($o['codigo_os'])) ?>', '<?= htmlspecialchars(addslashes($o['factura_numero'])) ?>', <?= $saldoOS ?>, '<?= htmlspecialchars(addslashes($o['cliente_nombre'])) ?>', '<?= htmlspecialchars(addslashes($o['condicion_pago'] ?? '')) ?>')" class="btn-accion-hs btn-registrar" style="background: #0f3b68; border-color: #0f3b68; color: white; padding: 6px 14px; font-size: 12px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                                                    <i class="fa-solid fa-cash-register"></i> Facturar / Cobrar Ahora
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    </div>

                                    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px; font-size: 12px;">
                                        <div>
                                            <span style="color: #64748b;">N° Factura:</span> 
                                            <strong style="font-family: monospace; color: #0f3b68;"><?= htmlspecialchars($o['factura_numero']) ?></strong>
                                        </div>
                                        <div>
                                            <span style="color: #64748b;">Monto Facturado:</span> 
                                            <strong style="color: #0f172a;">C$ <?= number_format($montoOS, 2) ?></strong>
                                        </div>
                                        <div>
                                            <span style="color: #64748b;">Saldo Pendiente:</span> 
                                            <strong style="color: <?= $saldoOS > 0 ? '#b91c1c' : '#059669' ?>;">C$ <?= number_format($saldoOS, 2) ?></strong>
                                        </div>
                                        <div>
                                            <span style="color: #64748b;">Condición de Pago:</span> 
                                            <span><?= htmlspecialchars($o['condicion_pago'] ?? 'Contado') ?></span>
                                        </div>
                                    </div>
                                    <?php if (!empty($cxcOS['notas'])): ?>
                                        <div style="margin-top: 8px; font-size: 11.5px; color: #475569; background: #f8fafc; border-radius: 4px; padding: 6px 10px; border-left: 3px solid #059669;">
                                            <i class="fa-solid fa-receipt" style="color: #059669;"></i> <?= htmlspecialchars($cxcOS['notas']) ?>
                                        </div>
                                    <?php endif; ?>
                                    <div style="margin-top: 6px; font-size: 11px; color: #64748b; font-style: italic;">
                                        <i class="fa-solid fa-info-circle"></i> La facturación y el cobro se pueden emitir en cualquier momento del proceso sin restricciones por ensayos o resultados pendientes.
                                    </div>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 8px;">
                                    <h4 style="margin: 0; font-family: 'Outfit'; font-size: 15px; font-weight: 700; color: #1e293b; display: flex; align-items: center; gap: 8px;">
                                        <i class="fa-solid fa-table-cells" style="color: var(--cycsa-azul);"></i> 
                                        Productos Cotizados - Matriz Técnica de Ensayos
                                    </h4>
                                    <span style="font-size: 12px; color: #64748b; font-weight: 600;">
                                        Modalidad: <strong><?= $requiereMuestreo ? 'Muestreo en Campo' : 'Ingreso Directo Lab' ?></strong>
                                    </span>
                                </div>

                                <div style="display: flex; justify-content: space-between; align-items: center; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                                    <div>
                                        <span style="font-size: 11.5px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.5px;">Origen de la Muestra:</span>
                                        <?php if ($esSoloCompactacion): ?>
                                            <span style="background: #e0f2fe; border: 1px solid #bae6fd; color: #0369a1; padding: 3px 8px; border-radius: 12px; font-weight: 700; font-size: 11px; margin-left: 6px;">
                                                <i class="fa-solid fa-gauge-high"></i> Ensayo In Situ (Sin muestra física a custodia de lab)
                                            </span>
                                        <?php elseif ($requiereMuestreo): ?>
                                            <span style="background: #f0fdf4; border: 1px solid #bbf7d0; color: #166534; padding: 3px 8px; border-radius: 12px; font-weight: 700; font-size: 11px; margin-left: 6px;">
                                                <i class="fa-solid fa-truck-pickup"></i> Muestreo en Campo por Técnico Dedicado (CYCSA)
                                            </span>
                                        <?php else: ?>
                                            <span style="background: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 3px 8px; border-radius: 12px; font-weight: 700; font-size: 11px; margin-left: 6px;">
                                                <i class="fa-solid fa-building-user"></i> Muestra Entregada Directamente por el Cliente
                                            </span>
                                        <?php endif; ?>
                                    </div>
                                    <div style="font-size: 12px; color: #64748b; font-weight: 600;">
                                        <?php if ($esSoloCompactacion): ?>
                                            Responsable: <strong style="color: #0369a1;">Operaciones / Ensayos en Terreno</strong>
                                        <?php elseif ($requiereMuestreo): ?>
                                            Técnico Asignado: <strong style="color: #103487;"><?= $tieneTecnico ? htmlspecialchars($o['tecnico_muestreo'], ENT_QUOTES, 'UTF-8') : 'Sin Asignar' ?></strong>
                                        <?php else: ?>
                                            Recepción: <strong style="color: #059669;">Ventanilla / Laboratorio Central (MS-XXXX-26)</strong>
                                        <?php endif; ?>
                                    </div>
                                </div>
                                
                                <div style="display: flex; flex-direction: column; gap: 10px;">
                                    <?php foreach ($o['items'] as $it): 
                                        $tieneRes = !empty($it['resultados_json']) && $it['resultados_json'] !== '[]';
                                        $esCompactacion = esItemCompactacion($it);
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

                                                <?php if ($esCompactacion): ?>
                                                    <!-- COMPACTACIÓN / DENSIDAD IN SITU: DIRECTO A LLENADO DE MATRIZ SIN SOLICITUD DE MUESTRAS -->
                                                    <span style="padding: 4px 8px; font-size: 11px; font-weight: 700; border-radius: 12px; background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; display: inline-flex; align-items: center; gap: 4px;">
                                                        <i class="fa-solid fa-gauge-high"></i> In Situ
                                                    </span>
                                                    <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $it['id'] ?>" 
                                                       class="btn-accion-hs btn-registrar" 
                                                       style="text-decoration: none; padding: 7px 14px; font-size: 12.5px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px; background-color:#103487; color:white;">
                                                        <i class="fa-solid fa-pen-to-square"></i> <?= $tieneRes ? 'Editar Matriz' : 'Rellenar Matriz' ?>
                                                    </a>
                                                    <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= $it['id'] ?>" 
                                                       target="_blank" 
                                                       class="btn-accion-hs btn-pdf" 
                                                       style="text-decoration: none; padding: 7px 11px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;" 
                                                       title="Imprimir Matriz Técnica Oficial con Membrete CYCSA">
                                                        <i class="fa-solid fa-print"></i> Imprimir
                                                    </a>
                                                    <?php if ($tieneRes): ?>
                                                        <button type="button" 
                                                                onclick="abrirModalEnviarMatriz(<?= (int)$it['id'] ?>, '<?= htmlspecialchars(addslashes($it['descripcion_ensayo']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($o['cliente_nombre']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($o['cliente_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($o['codigo_os']), ENT_QUOTES, 'UTF-8') ?>')" 
                                                                class="btn-accion-hs btn-enviar-cliente" 
                                                                style="padding: 7px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; background-color: #059669; color: white; border: 1px solid #047857; cursor: pointer;" 
                                                                title="Enviar Matriz Oficial en PDF al Correo del Cliente">
                                                            <i class="fa-solid fa-paper-plane"></i> Enviar al Cliente
                                                        </button>
                                                    <?php endif; ?>
                                                <?php elseif (!$tieneHojaServicio): ?>
                                                    <span style="padding: 6px 12px; font-size: 11.5px; font-weight: 700; border-radius: 6px; background: #fef2f2; color: #dc2626; border: 1px solid #fecaca; display: inline-flex; align-items: center; gap: 5px;">
                                                        <i class="fa-solid fa-lock"></i> Requiere Hoja RT-FM-13
                                                    </span>
                                                <?php elseif (!$tieneMuestrasAceptadas): ?>
                                                    <span style="padding: 6px 12px; font-size: 11.5px; font-weight: 700; border-radius: 6px; background: #fffbeb; color: #b45309; border: 1px solid #fde68a; display: inline-flex; align-items: center; gap: 5px;">
                                                        <i class="fa-solid fa-hourglass-half"></i> En Custodia / Pendiente Lab
                                                    </span>
                                                <?php else: ?>
                                                    <span style="padding: 4px 8px; font-size: 11px; font-weight: 700; border-radius: 12px; background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; display: inline-flex; align-items: center; gap: 4px;">
                                                        <i class="fa-solid fa-circle-check"></i> Aprobado Lab
                                                    </span>
                                                    <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $it['id'] ?>" 
                                                       class="btn-accion-hs btn-registrar" 
                                                       style="text-decoration: none; padding: 7px 14px; font-size: 12.5px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 6px;">
                                                        <i class="fa-solid fa-pen-to-square"></i> <?= $tieneRes ? 'Editar Matriz' : 'Rellenar Matriz' ?>
                                                    </a>
                                                    <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= $it['id'] ?>" 
                                                       target="_blank" 
                                                       class="btn-accion-hs btn-pdf" 
                                                       style="text-decoration: none; padding: 7px 11px; font-size: 12px; display: inline-flex; align-items: center; gap: 5px;" 
                                                       title="Imprimir Matriz Técnica Oficial con Membrete CYCSA">
                                                        <i class="fa-solid fa-print"></i> Imprimir
                                                    </a>
                                                    <?php if ($tieneRes): ?>
                                                        <button type="button" 
                                                                onclick="abrirModalEnviarMatriz(<?= (int)$it['id'] ?>, '<?= htmlspecialchars(addslashes($it['descripcion_ensayo']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($o['cliente_nombre']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($o['cliente_email'] ?? ''), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes($o['codigo_os']), ENT_QUOTES, 'UTF-8') ?>')" 
                                                                class="btn-accion-hs btn-enviar-cliente" 
                                                                style="padding: 7px 12px; font-size: 12px; font-weight: 700; border-radius: 6px; display: inline-flex; align-items: center; gap: 5px; background-color: #059669; color: white; border: 1px solid #047857; cursor: pointer;" 
                                                                title="Enviar Matriz Oficial en PDF al Correo del Cliente">
                                                            <i class="fa-solid fa-paper-plane"></i> Enviar al Cliente
                                                        </button>
                                                    <?php endif; ?>
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

    function intentarRellenarMatrizProducto(tieneHoja, tieneAceptacionLab, idOS, codigoOS, idDetalle) {
        if (!tieneHoja) {
            alert('Debe llenar y registrar primero la Hoja de Solicitud de Servicio (CYCSA-RT-FM-13) para la Orden ' + codigoOS + ' antes de rellenar la matriz.');
            window.location.href = '/Cycsa/publico/ordenes-servicio?id_os=' + idOS;
            return;
        }
        if (!tieneAceptacionLab) {
            if (confirm('Las muestras de la Orden ' + codigoOS + ' aún no han sido aceptadas e ingresadas en el Laboratorio.\n\n¿Desea ir al Portal de Laboratorio para aceptarlas y asignar los códigos oficiales (MS-XXXX-26)?')) {
                window.location.href = '/Cycsa/publico/laboratorio?tab=kanban';
            }
            return;
        }
        window.location.href = '/Cycsa/publico/operaciones/captura-matriz?id_detalle=' + idDetalle;
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

    function abrirModalEnviarMatriz(idDetalle, nombreEnsayo, clienteNombre, clienteEmail, codigoOS) {
        document.getElementById('envio_id_detalle').value = idDetalle;
        document.getElementById('envio_txt_ensayo').innerText = nombreEnsayo;
        document.getElementById('envio_txt_os').innerText = codigoOS;
        document.getElementById('envio_txt_cliente').innerText = clienteNombre;
        document.getElementById('envio_destinatario').value = clienteEmail || '';
        document.getElementById('envio_asunto').value = 'Informe Oficial de Ensayo - ' + codigoOS + ' - ' + nombreEnsayo + ' - CYCSA';
        document.getElementById('btn-previsualizar-pdf').href = '/Cycsa/publico/operaciones/descargar-matriz-pdf?id_detalle=' + idDetalle;
        
        const modal = document.getElementById('modalEnviarInformeCliente');
        if (modal) {
            modal.style.display = 'block';
        }
    }

    function cerrarModalEnviarMatriz() {
        const modal = document.getElementById('modalEnviarInformeCliente');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function prepararEnvioMatriz(form) {
        const btn = document.getElementById('btn-submit-envio');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando PDF por correo...';
        }
        return true;
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

<!-- MODAL DE ENVÍO DE INFORME OFICIAL DE RESULTADOS EN PDF AL CLIENTE -->
<div id="modalEnviarInformeCliente" class="modal-premium" style="display:none; position:fixed; z-index:10005; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5);">
    <div class="modal-premium-content" style="width: 50%; max-width:620px; background:white; margin:5% auto; padding:25px; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 12px;">
            <h3 style="margin: 0; color: #1e293b; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-paper-plane" style="color: #059669;"></i> Enviar Informe Oficial al Cliente
            </h3>
            <button type="button" onclick="cerrarModalEnviarMatriz()" class="btn-cerrar">&times;</button>
        </div>

        <form method="POST" action="/Cycsa/publico/operaciones/enviar-matriz-cliente" id="form-enviar-matriz-cliente" onsubmit="prepararEnvioMatriz(this)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_detalle" id="envio_id_detalle" value="">

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
                <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700; letter-spacing: 0.5px;">Resumen del Ensayo:</div>
                <div style="font-size: 14px; font-weight: 700; color: #0f172a; margin-top: 4px;" id="envio_txt_ensayo"></div>
                <div style="font-size: 12.5px; color: #475569; margin-top: 4px;">
                    O/S: <strong id="envio_txt_os" style="color: #103487; font-family: monospace;"></strong> &bull; 
                    Cliente: <strong id="envio_txt_cliente"></strong>
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 14px;">
                <label style="font-size: 13px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 6px;">
                    <i class="fa-solid fa-envelope" style="color: var(--cycsa-azul);"></i> Correo Electrónico del Cliente (Destinatario):
                </label>
                <input type="email" name="destinatario" id="envio_destinatario" class="form-control" required style="width: 100%; padding: 10px 14px; font-size: 13.5px; border-radius: 6px; border: 1.5px solid #cbd5e1; box-sizing: border-box;">
                <small style="color: #64748b; font-size: 11.5px; margin-top: 4px; display: block;">
                    * Correo cargado automáticamente del perfil del cliente. Puede editarlo o escribir una dirección diferente si lo requiere.
                </small>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="font-size: 13px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 6px;">
                    <i class="fa-solid fa-heading" style="color: var(--cycsa-azul);"></i> Asunto del Correo:
                </label>
                <input type="text" name="asunto" id="envio_asunto" class="form-control" required style="width: 100%; padding: 10px 14px; font-size: 13.5px; border-radius: 6px; border: 1.5px solid #cbd5e1; box-sizing: border-box;">
            </div>

            <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 12px 15px; margin-bottom: 20px; font-size: 12px; color: #065f46; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-file-pdf" style="font-size: 24px; color: #059669;"></i>
                <div>
                    <strong>Documento PDF Oficial Adjunto:</strong><br>
                    Se generará y adjuntará automáticamente la matriz de resultados con membrete horizontal CYCSA, metadatos, firmas y notas normativas ISO/IEC 17025.
                </div>
            </div>

            <div style="display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                <a href="javascript:void(0)" id="btn-previsualizar-pdf" target="_blank" class="btn-accion-hs btn-pdf" style="text-decoration: none; padding: 9px 15px; font-size: 12.5px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-eye"></i> Previsualizar PDF
                </a>

                <div style="display: flex; gap: 10px;">
                    <button type="button" onclick="cerrarModalEnviarMatriz()" class="btn-accion-hs btn-editar" style="padding: 9px 18px; font-size: 13px; cursor: pointer; border-radius: 6px;">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-submit-envio" class="btn-accion-hs btn-registrar" style="background-color: #059669; color: white; border: 1px solid #047857; padding: 9px 22px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; border-radius: 6px;">
                        <i class="fa-solid fa-paper-plane"></i> Enviar por Correo al Cliente
                    </button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL FACTURACIÓN Y COBRO INMEDIATO (EFECTIVO O TRANSFERENCIA) -->
<div id="modalFacturarOS" class="modal-premium" style="display:none; position:fixed; z-index:10006; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.5);">
    <div class="modal-premium-content" style="width: 50%; max-width:640px; background:white; margin:4% auto; padding:25px; border-radius:12px; box-shadow:0 8px 30px rgba(0,0,0,0.25);">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 18px; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 12px;">
            <h3 style="margin: 0; color: #0f3b68; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-file-invoice-dollar" style="color: #059669;"></i> Facturación y Cobro de Orden de Servicio
            </h3>
            <button type="button" onclick="cerrarModalFacturarOS()" class="btn-cerrar">&times;</button>
        </div>

        <form method="POST" action="/Cycsa/publico/operaciones/procesar-facturacion" id="form-facturar-os">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_os" id="fact_id_os" value="">

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div>
                        <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Orden de Servicio:</div>
                        <div style="font-size: 15px; font-weight: 800; color: #0f3b68; font-family: monospace;" id="fact_txt_os"></div>
                    </div>
                    <div style="text-align: right;">
                        <div style="font-size: 11px; color: #64748b; text-transform: uppercase; font-weight: 700;">Cliente:</div>
                        <div style="font-size: 13.5px; font-weight: 700; color: #1e293b;" id="fact_txt_cliente"></div>
                    </div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 16px;">
                <div class="form-group">
                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 5px;">
                        Número de Factura:
                    </label>
                    <input type="text" name="factura_numero" id="fact_input_factura_num" class="form-control" required style="font-family: monospace; font-weight: 700; color: #0f3b68; width: 100%; padding: 9px 12px; border: 1.5px solid #cbd5e1; border-radius: 6px;">
                </div>
                <div class="form-group">
                    <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 5px;">
                        Monto a Facturar / Cobrar (C$):
                    </label>
                    <input type="number" step="0.01" min="0.01" name="monto" id="fact_input_monto" class="form-control" required style="font-weight: 700; font-size: 15px; color: #0f172a; width: 100%; padding: 9px 12px; border: 1.5px solid #cbd5e1; border-radius: 6px;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 16px;">
                <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 6px;">
                    Método de Pago / Facturación:
                </label>
                <div style="display: flex; gap: 18px; background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0; flex-wrap: wrap;">
                    <label style="display: flex; align-items: center; gap: 6px; font-weight: 600; cursor: pointer; font-size: 13px;">
                        <input type="radio" name="metodo_pago" value="efectivo" checked onchange="toggleMetodoPagoFacturacion('efectivo')">
                        💵 Efectivo (Caja Principal)
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; font-weight: 600; cursor: pointer; font-size: 13px;">
                        <input type="radio" name="metodo_pago" value="transferencia" onchange="toggleMetodoPagoFacturacion('transferencia')">
                        🏦 Transferencia Bancaria
                    </label>
                    <label style="display: flex; align-items: center; gap: 6px; font-weight: 600; cursor: pointer; font-size: 13px;">
                        <input type="radio" name="metodo_pago" value="credito" onchange="toggleMetodoPagoFacturacion('credito')">
                        📑 Crédito
                    </label>
                </div>
            </div>

            <!-- Sección Transferencia Bancaria -->
            <div id="seccion-transferencia-banco" style="display: none; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 14px;">
                    <div class="form-group">
                        <label style="font-size: 12px; font-weight: 700; color: #166534; display: block; margin-bottom: 5px;">
                            Cuenta Bancaria Receptora:
                        </label>
                        <select name="id_banco_cuenta" id="fact_select_banco" class="form-control" style="width: 100%; padding: 9px 12px; font-size: 12.5px; border-radius: 6px; border: 1.5px solid #86efac;">
                            <option value="">-- Seleccionar Banco --</option>
                            <?php foreach (($bancos ?? []) as $b): ?>
                                <option value="<?= $b['id'] ?>"><?= htmlspecialchars($b['banco_nombre']) ?> - <?= htmlspecialchars($b['numero_cuenta']) ?> (<?= $b['moneda'] ?>)</option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label style="font-size: 12px; font-weight: 700; color: #166534; display: block; margin-bottom: 5px;">
                            N° Referencia / Transferencia:
                        </label>
                        <input type="text" name="referencia" id="fact_input_ref" class="form-control" placeholder="Ej: TRANS-982341" style="width: 100%; padding: 9px 12px; font-size: 12.5px; border-radius: 6px; border: 1.5px solid #86efac;">
                    </div>
                </div>
                <small style="color: #166534; font-size: 11px; margin-top: 6px; display: block;">
                    * Se incrementará el saldo de la cuenta bancaria y se registrará la transacción en Bancos y en el Libro Diario.
                </small>
            </div>

            <!-- Sección Efectivo -->
            <div id="seccion-efectivo-caja" style="background: #eff6ff; border: 1px solid #bfdbfe; border-radius: 8px; padding: 12px 14px; margin-bottom: 16px; font-size: 12px; color: #1e40af;">
                <i class="fa-solid fa-circle-info"></i> El cobro se debitará en <strong>Caja Principal (Cuenta 1010101)</strong> y se acreditará a <strong>Ingresos por Laboratorio (Cuenta 4010106)</strong> en el registro diario contable.
            </div>

            <!-- Sección Crédito -->
            <div id="seccion-credito-plazo" style="display: none; background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 14px; margin-bottom: 16px;">
                <div class="form-group">
                    <label style="font-size: 12px; font-weight: 700; color: #b45309; display: block; margin-bottom: 5px;">
                        Plazo de Crédito (Días):
                    </label>
                    <input type="number" name="dias_credito" id="fact_dias_credito" value="30" min="1" max="180" class="form-control" style="width: 120px; padding: 8px 12px; font-size: 13px; border-radius: 6px; border: 1.5px solid #fcd34d;">
                </div>
            </div>

            <div class="form-group" style="margin-bottom: 20px;">
                <label style="font-size: 12.5px; font-weight: 700; color: #1e293b; display: block; margin-bottom: 5px;">
                    Fecha de Facturación:
                </label>
                <input type="date" name="fecha" id="fact_input_fecha" value="<?= date('Y-m-d') ?>" class="form-control" required style="width: 100%; padding: 9px 12px; font-size: 13px; border-radius: 6px; border: 1.5px solid #cbd5e1;">
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; border-top: 1px solid #e2e8f0; padding-top: 16px;">
                <button type="button" onclick="cerrarModalFacturarOS()" class="btn-accion-hs btn-editar" style="padding: 9px 20px; font-size: 13px; cursor: pointer; border-radius: 6px;">
                    Cancelar
                </button>
                <button type="submit" class="btn-accion-hs btn-registrar" style="background: #0f3b68; border-color: #0f3b68; color: white; padding: 9px 24px; font-size: 13px; font-weight: 700; cursor: pointer; display: inline-flex; align-items: center; gap: 7px; border-radius: 6px;">
                    <i class="fa-solid fa-check-circle"></i> Emitir Factura y Registrar en Diario
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalFacturarOS(idOS, codigoOS, facturaNum, saldoPendiente, clienteNombre, condicionPago) {
        document.getElementById('fact_id_os').value = idOS;
        document.getElementById('fact_txt_os').innerText = codigoOS;
        document.getElementById('fact_txt_cliente').innerText = clienteNombre;
        document.getElementById('fact_input_factura_num').value = facturaNum;
        document.getElementById('fact_input_monto').value = parseFloat(saldoPendiente || 0).toFixed(2);
        document.getElementById('fact_input_fecha').value = new Date().toISOString().split('T')[0];

        // Reset radios
        const radEfectivo = document.querySelector('input[name="metodo_pago"][value="efectivo"]');
        if (radEfectivo) radEfectivo.checked = true;
        toggleMetodoPagoFacturacion('efectivo');

        const modal = document.getElementById('modalFacturarOS');
        if (modal) {
            modal.style.display = 'block';
        }
    }

    function cerrarModalFacturarOS() {
        const modal = document.getElementById('modalFacturarOS');
        if (modal) {
            modal.style.display = 'none';
        }
    }

    function toggleMetodoPagoFacturacion(metodo) {
        const secTransf = document.getElementById('seccion-transferencia-banco');
        const secEfec = document.getElementById('seccion-efectivo-caja');
        const secCred = document.getElementById('seccion-credito-plazo');
        const selBanco = document.getElementById('fact_select_banco');

        if (metodo === 'transferencia') {
            secTransf.style.display = 'block';
            secEfec.style.display = 'none';
            secCred.style.display = 'none';
            if (selBanco) selBanco.setAttribute('required', 'required');
        } else if (metodo === 'credito') {
            secTransf.style.display = 'none';
            secEfec.style.display = 'none';
            secCred.style.display = 'block';
            if (selBanco) selBanco.removeAttribute('required');
        } else {
            secTransf.style.display = 'none';
            secEfec.style.display = 'block';
            secCred.style.display = 'none';
            if (selBanco) selBanco.removeAttribute('required');
        }
    }
</script>

<?php
$bitacora_modulo_nombre = 'Operaciones LIMS';
include dirname(__DIR__, 3) . '/Views/parciales/bitacora_modulo.php';
?>


