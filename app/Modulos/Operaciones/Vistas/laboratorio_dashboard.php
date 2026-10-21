<?php
// Laboratorio Dashboard - Portal de Gestión Técnica e Imparcialidad ISO/IEC 17025
$tabActiva = $_GET['tab'] ?? 'aprobacion';
if (!in_array($tabActiva, ['aprobacion', 'bandeja', 'programadas'])) {
    $tabActiva = 'aprobacion';
}

$solicitudesPorAprobar = $solicitudesPorAprobar ?? [];
$solicitudesAprobadasRecientes = $solicitudesAprobadasRecientes ?? [];
$muestras = $muestras ?? [];
$rupturas = $rupturas ?? [];
$kpis = $kpis ?? ['total' => 0, 'rotura_hoy' => 0, 'vencidas' => 0, 'activas' => 0, 'cerradas' => 0];
$filtros = $filtros ?? [];
$puedeReidentificar = $puedeReidentificar ?? false;

// Conteo de solicitudes por estado para KPIs de Aprobación
$conteoPendientes = count($solicitudesPorAprobar);
$conteoRecienLlegadas = 0;
$conteoEnRevision = 0;
$conteoObservadas = 0;

foreach ($solicitudesPorAprobar as $sol) {
    if (($sol['estado_os'] ?? '') === 'Estado 2: Revision') {
        $conteoEnRevision++;
    } elseif (($sol['estado_os'] ?? '') === 'Estado 2: Observada') {
        $conteoObservadas++;
    } else {
        $conteoRecienLlegadas++;
    }
}
?>
<style>
    :root {
        --cycsa-azul: #103487;
        --cycsa-azul-hover: #0c2766;
        --cycsa-rojo: #e31837;
        --color-success: #10b981;
        --color-warning: #f59e0b;
        --color-danger: #ef4444;
        --color-slate-50: #f8fafc;
        --color-slate-100: #f1f5f9;
        --color-slate-200: #e2e8f0;
        --color-slate-300: #cbd5e1;
        --color-slate-600: #475569;
        --color-slate-700: #334155;
        --color-slate-800: #1e293b;
    }

    .lab-box { background: white; padding: 22px; border-radius: 12px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 22px; border: 1px solid var(--color-slate-200); }
    .section-title { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: var(--cycsa-azul); margin: 0; padding-bottom: 8px; }
    
    /* Pestañas Superiores Estilo Pill */
    .lab-tabs-nav {
        display: flex;
        gap: 10px;
        margin-bottom: 20px;
        background: #f1f5f9;
        padding: 5px;
        border-radius: 10px;
        width: fit-content;
        border: 1px solid #e2e8f0;
    }
    .lab-tab-link {
        padding: 9px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 700;
        font-size: 13px;
        color: #475569;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .lab-tab-link.active {
        background: #103487;
        color: white;
        box-shadow: 0 4px 10px rgba(16, 52, 135, 0.25);
    }
    .lab-tab-link:not(.active):hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .tab-badge {
        padding: 2px 7px;
        border-radius: 10px;
        font-size: 11px;
        font-weight: 800;
    }
    .tab-badge-active { background: rgba(255, 255, 255, 0.25); color: white; }
    .tab-badge-inactive { background: #cbd5e1; color: #1e293b; }

    /* KPIs Cards */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 15px;
        margin-bottom: 22px;
    }
    @media (max-width: 992px) {
        .kpi-grid { grid-template-columns: repeat(2, 1fr); }
    }
    .kpi-card {
        background: white;
        border: 1px solid var(--color-slate-200);
        border-radius: 10px;
        padding: 16px 18px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-shadow: 0 2px 6px rgba(0,0,0,0.02);
    }
    .kpi-num { font-size: 24px; font-weight: 800; font-family: 'Outfit', sans-serif; line-height: 1; margin-top: 4px; }
    .kpi-label { font-size: 12px; font-weight: 600; text-transform: uppercase; color: #64748b; letter-spacing: 0.5px; }

    /* Tabla Corporativa */
    .tabla-lab {
        width: 100%;
        border-collapse: collapse;
        font-size: 13px;
    }
    .tabla-lab th {
        background: #f8fafc;
        color: #475569;
        font-weight: 700;
        padding: 12px 14px;
        text-align: left;
        border-bottom: 2px solid #e2e8f0;
        text-transform: uppercase;
        font-size: 11.5px;
        letter-spacing: 0.5px;
    }
    .tabla-lab td {
        padding: 12px 14px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        color: #334155;
    }
    .tabla-lab tbody tr {
        transition: background-color 0.15s;
    }
    .tabla-lab tbody tr:hover {
        background-color: #f8fafc;
    }
    .tabla-lab tbody tr.fila-vencida {
        background-color: #fff5f5;
    }
    .tabla-lab tbody tr.fila-vencida:hover {
        background-color: #fee2e2;
    }
    .tabla-lab tbody tr.fila-hoy {
        background-color: #fefce8;
    }

    /* Badges de Estado */
    .badge-lab {
        display: inline-flex;
        align-items: center;
        gap: 5px;
        padding: 3px 9px;
        border-radius: 20px;
        font-size: 11px;
        font-weight: 700;
        text-transform: uppercase;
    }
    .badge-vencida { background: #fee2e2; color: #b91c1c; border: 1px solid #fca5a5; }
    .badge-hoy { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
    .badge-activo { background: #e0f2fe; color: #0369a1; border: 1px solid #bae6fd; }
    .badge-cerrado { background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; }
    .badge-curado { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .badge-observada { background: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    /* Barra de Filtros */
    .filtros-bar {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px 18px;
        margin-bottom: 20px;
        display: flex;
        flex-wrap: wrap;
        gap: 12px;
        align-items: flex-end;
    }
    .filtro-item { display: flex; flex-direction: column; gap: 4px; }
    .filtro-item label { font-size: 11px; font-weight: 700; text-transform: uppercase; color: #64748b; }
    .filtro-input { padding: 7px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px; outline: none; background: white; }
    .filtro-input:focus { border-color: var(--cycsa-azul); }

    .btn-filtro {
        padding: 7px 14px;
        border-radius: 6px;
        border: 1px solid #cbd5e1;
        background: white;
        font-size: 12.5px;
        font-weight: 600;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        text-decoration: none;
        color: #334155;
        transition: all 0.2s;
    }
    .btn-filtro.activo {
        background: #103487;
        color: white;
        border-color: #103487;
    }
    .btn-filtro-vencida.activo {
        background: #ef4444;
        color: white;
        border-color: #ef4444;
    }

    /* Botones Acciones */
    .btn-accion {
        border: none;
        background: none;
        cursor: pointer;
        padding: 6px 12px;
        border-radius: 6px;
        font-size: 12px;
        font-weight: 700;
        transition: all 0.2s;
        text-decoration: none;
        display: inline-flex;
        align-items: center;
        gap: 5px;
    }
    .btn-detalle { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .btn-detalle:hover { background: #dbeafe; }
    .btn-reidentificar { background: #fffbeb; color: #b45309; border: 1px solid #fde68a; }
    .btn-reidentificar:hover { background: #fef3c7; }
    .btn-aprobar { background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; }
    .btn-aprobar:hover { background: #d1fae5; color: #065f46; transform: translateY(-1px); }
    .btn-observar { background: #fef2f2; color: #b91c1c; border: 1px solid #fecaca; }
    .btn-observar:hover { background: #fee2e2; color: #991b1b; }
    .btn-revision { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .btn-revision:hover { background: #dbeafe; }
    .btn-os { background: #f8fafc; color: #0369a1; border: 1px solid #bae6fd; font-weight: 700; }
    .btn-os:hover { background: #e0f2fe; color: #0284c7; }

    .tag-especimen-pill {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
        padding: 2px 7px;
        border-radius: 4px;
        font-family: monospace;
        font-weight: 700;
        font-size: 11px;
        display: inline-block;
        margin: 2px 3px 2px 0;
    }

    /* Modal */
    .modal-lab { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .modal-lab-content { background: white; margin: 5% auto; padding: 25px 30px; border-radius: 12px; width: 90%; max-width: 620px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); border: 1px solid #e2e8f0; }
</style>

<div class="header-flex" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 800;">Portal de Laboratorio (LIMS)</h2>
        <p style="color: #64748b; margin-top: 4px; font-size: 13.5px;">Aprobación de Solicitudes, Custodia Técnica y Programa de Roturas bajo ISO/IEC 17025 (Cláusulas 4.1 y 4.2)</p>
    </div>
    <div style="display: flex; gap: 10px;">
        <a href="/Cycsa/publico/laboratorio?tab=<?= htmlspecialchars($tabActiva) ?>" class="btn-filtro"><i class="fa-solid fa-arrows-rotate"></i> Actualizar</a>
    </div>
</div>

<!-- Alerta Oficial de Imparcialidad ISO 17025 -->
<div style="background-color: #f0f9ff; border: 1px solid #bae6fd; color: #0369a1; padding: 12px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-size: 13px; font-weight: 500;">
    <i class="fa-solid fa-user-shield" style="font-size: 22px; color: #0284c7;"></i>
    <div>
        <strong>MODO ANÓNIMO CIEGO ISO/IEC 17025:</strong> Las solicitudes y muestras son gestionadas estrictamente por su <strong>código técnico de documento y código oficial de probeta</strong>. La información comercial, clientes y cotizaciones está completamente aislada de los roles técnicos.
    </div>
</div>

<!-- Notificaciones Flash -->
<?php if (!empty($exito)): ?>
    <div style="background-color: #f0fdf4; color: #166534; padding: 12px 16px; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 15px; font-size: 13.5px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div style="background-color: #fef2f2; color: #991b1b; padding: 12px 16px; border-radius: 8px; border: 1px solid #fecaca; margin-bottom: 15px; font-size: 13.5px; display: flex; align-items: center; gap: 8px;">
        <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<!-- PESTAÑAS PRINCIPALES DEL MÓDULO -->
<div class="lab-tabs-nav">
    <a href="?tab=aprobacion" class="lab-tab-link <?= $tabActiva === 'aprobacion' ? 'active' : '' ?>">
        <i class="fa-solid fa-clipboard-check"></i> 
        <span>1. Solicitudes por Aprobar</span>
        <span class="tab-badge <?= $tabActiva === 'aprobacion' ? 'tab-badge-active' : 'tab-badge-inactive' ?>">
            <?= count($solicitudesPorAprobar) ?>
        </span>
    </a>
    <a href="?tab=bandeja" class="lab-tab-link <?= $tabActiva === 'bandeja' ? 'active' : '' ?>">
        <i class="fa-solid fa-table-list"></i> 
        <span>2. Bandeja Anónima de Laboratorio</span>
        <span class="tab-badge <?= $tabActiva === 'bandeja' ? 'tab-badge-active' : 'tab-badge-inactive' ?>">
            <?= count($muestras) ?>
        </span>
    </a>
    <a href="?tab=programadas" class="lab-tab-link <?= $tabActiva === 'programadas' ? 'active' : '' ?>">
        <i class="fa-solid fa-calendar-check"></i> 
        <span>3. Próximas Roturas (7 días)</span>
        <span class="tab-badge <?= $tabActiva === 'programadas' ? 'tab-badge-active' : 'tab-badge-inactive' ?>">
            <?= count($rupturas) ?>
        </span>
    </a>
</div>

<!-- =========================================================================
     PESTAÑA 1 (PRIMERA): APARTADO PARA APROBAR SOLICITUDES DE LABORATORIO
     ========================================================================= -->
<?php if ($tabActiva === 'aprobacion'): ?>

    <!-- Tarjetas KPI de Solicitudes Entrantes -->
    <div class="kpi-grid">
        <div class="kpi-card" style="border-left: 4px solid var(--cycsa-azul);">
            <div>
                <div class="kpi-label">Pendientes de Aprobación</div>
                <div class="kpi-num" style="color: var(--cycsa-azul);"><?= $conteoPendientes ?></div>
            </div>
            <i class="fa-solid fa-inbox" style="font-size: 24px; color: #94a3b8;"></i>
        </div>
        <div class="kpi-card" style="border-left: 4px solid #f59e0b;">
            <div>
                <div class="kpi-label">Recién Llegadas</div>
                <div class="kpi-num" style="color: #d97706;"><?= $conteoRecienLlegadas ?></div>
            </div>
            <i class="fa-solid fa-clock" style="font-size: 24px; color: #f59e0b;"></i>
        </div>
        <div class="kpi-card" style="border-left: 4px solid #3b82f6;">
            <div>
                <div class="kpi-label">En Revisión Técnica</div>
                <div class="kpi-num" style="color: #2563eb;"><?= $conteoEnRevision ?></div>
            </div>
            <i class="fa-solid fa-magnifying-glass" style="font-size: 24px; color: #3b82f6;"></i>
        </div>
        <div class="kpi-card" style="border-left: 4px solid #ef4444;">
            <div>
                <div class="kpi-label">Devueltas / Observadas</div>
                <div class="kpi-num" style="color: #dc2626;"><?= $conteoObservadas ?></div>
            </div>
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 24px; color: #ef4444;"></i>
        </div>
    </div>

    <!-- Barra de Filtros para Solicitudes por Aprobar -->
    <form method="GET" action="/Cycsa/publico/laboratorio" class="filtros-bar">
        <input type="hidden" name="tab" value="aprobacion">

        <div class="filtro-item" style="flex: 2; min-width: 200px;">
            <label><i class="fa-solid fa-magnifying-glass"></i> Búsqueda por Solicitud / Ensayo / Matriz</label>
            <input type="text" name="buscar_solicitud" class="filtro-input" placeholder="Ej. CYCSA-RT-FM-60, OS-2026, Compresión..." value="<?= htmlspecialchars($filtroAprobacion ?? '') ?>">
        </div>

        <div class="filtro-item" style="flex: 1; min-width: 170px;">
            <label>Estado de Solicitud</label>
            <select name="estado_aprobacion" class="filtro-input">
                <option value="">-- Todos los Estados --</option>
                <option value="recibidas" <?= ($estadoAprobacion ?? '') === 'recibidas' ? 'selected' : '' ?>>Recién Llegadas (Estado 1)</option>
                <option value="revision" <?= ($estadoAprobacion ?? '') === 'revision' ? 'selected' : '' ?>>En Revisión Técnica (Estado 2)</option>
                <option value="observadas" <?= ($estadoAprobacion ?? '') === 'observadas' ? 'selected' : '' ?>>Devueltas / Observadas</option>
            </select>
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn-filtro activo"><i class="fa-solid fa-filter"></i> Filtrar</button>
            <a href="?tab=aprobacion&estado_aprobacion=recibidas" class="btn-filtro <?= ($estadoAprobacion ?? '') === 'recibidas' ? 'activo' : '' ?>"><i class="fa-solid fa-clock"></i> Recién Llegadas</a>
            <a href="?tab=aprobacion&estado_aprobacion=observadas" class="btn-filtro <?= ($estadoAprobacion ?? '') === 'observadas' ? 'activo' : '' ?>" style="color:#b91c1c;"><i class="fa-solid fa-triangle-exclamation"></i> Observadas</a>
            <a href="/Cycsa/publico/laboratorio?tab=aprobacion" class="btn-filtro" title="Limpiar"><i class="fa-solid fa-eraser"></i></a>
        </div>
    </form>

    <!-- Tabla Principal de Solicitudes Pendientes de Aprobación -->
    <div class="lab-box" style="padding: 0; overflow: hidden; margin-bottom: 25px;">
        <div style="padding: 16px 20px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #fafafa;">
            <div>
                <h3 class="section-title" style="margin: 0; font-size: 15px;">
                    <i class="fa-solid fa-inbox" style="margin-right: 6px; color: var(--cycsa-azul);"></i> Solicitudes Pendientes de Aprobación e Ingreso Físico
                </h3>
                <span style="font-size: 12px; color: #64748b;">Muestras declaradas en la Hoja RT-FM-13 a la espera de verificación física en laboratorio.</span>
            </div>
            <span class="badge-lab badge-activo"><?= count($solicitudesPorAprobar) ?> Solicitudes</span>
        </div>

        <div style="overflow-x: auto;">
            <table class="tabla-lab">
                <thead>
                    <tr>
                        <th>Solicitud / Hoja</th>
                        <th>Material / Matriz</th>
                        <th>Ensayos Solicitados</th>
                        <th>Muestras Declaradas</th>
                        <th>Fecha Toma / Moldeo</th>
                        <th>Estado Actual</th>
                        <th style="text-align: right; min-width: 290px;">Acciones Técnicas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudesPorAprobar as $sol): 
                        $esDevuelta = ($sol['estado_os'] === 'Estado 2: Observada') || !empty($sol['motivo_observacion']);
                        $enRevision = ($sol['estado_os'] === 'Estado 2: Revision');
                        $codigoVisible = !empty($sol['hoja_codigo']) ? $sol['hoja_codigo'] : $sol['codigo_os'];
                    ?>
                    <tr style="<?= $esDevuelta ? 'background-color: #fff8f8;' : ($enRevision ? 'background-color: #f8fafc;' : '') ?>">
                        <td>
                            <strong style="color: #103487; font-family: monospace; font-size: 13.5px; display: block;">
                                <i class="fa-solid fa-file-waveform" style="color: var(--cycsa-azul); margin-right: 4px;"></i>
                                <?= htmlspecialchars($codigoVisible, ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($sol['numero_registro'])): ?>
                                    <span style="font-size: 10px; background: #e0f2fe; color: #0369a1; padding: 1px 6px; border-radius: 4px; font-weight: 700; margin-left: 4px;">Reg: <?= htmlspecialchars($sol['numero_registro']) ?></span>
                                <?php endif; ?>
                            </strong>
                            <span style="font-size: 11px; color: #64748b;">
                                <?php if (!empty($sol['requiere_muestreo'])): ?>
                                    <span style="color: #0369a1; font-weight: 600;"><i class="fa-solid fa-truck-pickup"></i> Muestreo Campo</span>
                                <?php else: ?>
                                    <span style="color: #047857; font-weight: 600;"><i class="fa-solid fa-flask"></i> Muestra Lab</span>
                                <?php endif; ?>
                                &bull; O/S: <?= htmlspecialchars($sol['codigo_os'] ?? '') ?>
                            </span>
                        </td>
                        <td>
                            <strong style="color: #1e293b;"><?= htmlspecialchars($sol['naturaleza_muestra'] ?: 'Material General', ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if (!empty($sol['procedencia_punto_muestreo'])): ?>
                                <span style="display: block; font-size: 11px; color: #64748b;">Punto: <?= htmlspecialchars($sol['procedencia_punto_muestreo']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: #334155;">
                                <?= htmlspecialchars($sol['ensayos'][0]['descripcion_ensayo'] ?? 'Ensayo Estándar', ENT_QUOTES, 'UTF-8') ?>
                            </span>
                            <?php if (count($sol['ensayos']) > 1): ?>
                                <span style="font-size: 11px; color: #64748b; display: block;">+<?= count($sol['ensayos']) - 1 ?> ensayo(s) adicional(es)</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="color: #1e293b; font-size: 13px;"><?= (int)$sol['total_muestras_declaradas'] ?> probeta(s)</strong>
                            <div>
                                <?php if (!empty($sol['muestras_declaradas'])): ?>
                                    <?php foreach (array_slice($sol['muestras_declaradas'], 0, 4) as $md): ?>
                                        <span class="tag-especimen-pill"><?= htmlspecialchars($md['nombre_muestra'] ?? 'M') ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($sol['muestras_declaradas']) > 4): ?>
                                        <span style="font-size: 10.5px; color: #64748b;">+<?= count($sol['muestras_declaradas']) - 4 ?> más</span>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span style="font-size: 11.5px; color: #94a3b8; font-style: italic;">Sin detalle</span>
                                <?php endif; ?>
                            </div>
                        </td>
                        <td>
                            <?php if (!empty($sol['fecha_hora_toma_muestra'])): ?>
                                <strong><?= htmlspecialchars(date('d/m/Y', strtotime($sol['fecha_hora_toma_muestra'])), ENT_QUOTES, 'UTF-8') ?></strong>
                                <span style="display: block; font-size: 11px; color: #64748b;"><?= htmlspecialchars(date('H:i', strtotime($sol['fecha_hora_toma_muestra']))) ?> hrs</span>
                            <?php else: ?>
                                <span><?= htmlspecialchars(date('d/m/Y', strtotime($sol['fecha_emision'])), ENT_QUOTES, 'UTF-8') ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($esDevuelta): ?>
                                <span class="badge-lab badge-observada"><i class="fa-solid fa-triangle-exclamation"></i> Observada</span>
                                <?php if (!empty($sol['motivo_observacion'])): ?>
                                    <div style="font-size: 11px; color: #b91c1c; margin-top: 3px; max-width: 220px; line-height: 1.2;">
                                        <strong>Motivo:</strong> <?= htmlspecialchars($sol['motivo_observacion']) ?>
                                    </div>
                                <?php endif; ?>
                            <?php elseif ($enRevision): ?>
                                <span class="badge-lab badge-activo"><i class="fa-solid fa-magnifying-glass"></i> En Revisión</span>
                            <?php else: ?>
                                <span class="badge-lab badge-hoy"><i class="fa-solid fa-clock"></i> Recién Llegada</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap;">
                            <!-- Formato de Hoja RT-FM-60 (PRESERVADO Y PROMINENTE) -->
                            <a href="/Cycsa/publico/laboratorio/imprimir-solicitud?id_os=<?= (int)$sol['id_os'] ?><?= !empty($sol['id_hoja']) ? '&id_hoja=' . (int)$sol['id_hoja'] : '' ?>" target="_blank" class="btn-accion btn-os" title="Ver e imprimir formato oficial de Solicitud de Laboratorio CYCSA-RT-FM-60">
                                <i class="fa-solid fa-file-pdf"></i> Hoja RT-FM-60
                            </a>

                            <!-- Botón Aprobar e Ingresar a Lab -->
                            <button type="button" class="btn-accion btn-aprobar" onclick="abrirModalAprobar(<?= (int)$sol['id_os'] ?>, '<?= htmlspecialchars($codigoVisible, ENT_QUOTES) ?>', <?= (int)$sol['total_muestras_declaradas'] ?>, <?= (int)($sol['ensayos'][0]['id'] ?? 0) ?>, <?= (int)($sol['id_hoja'] ?? 0) ?>)" title="Aprobar e ingresar físicamente al laboratorio (Asigna códigos oficiales MS/MC)">
                                <i class="fa-solid fa-circle-check"></i> Aprobar Muestra
                            </button>

                            <!-- Botón Enviar a Revisión (si está en Estado 1) -->
                            <?php if (!$enRevision && !$esDevuelta): ?>
                                <form method="POST" action="/Cycsa/publico/laboratorio/cambiar-estado" style="display:inline;" onsubmit="return confirm('¿Pasar la solicitud a Revisión Técnica?');">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="id_os" value="<?= (int)$sol['id_os'] ?>">
                                    <input type="hidden" name="nuevo_estado" value="Estado 2: Revision">
                                    <button type="submit" class="btn-accion btn-revision" title="Marcar como En Revisión Técnica">
                                        <i class="fa-solid fa-eye"></i> Revisar
                                    </button>
                                </form>
                            <?php endif; ?>

                            <!-- Botón Observar / Devolver -->
                            <button type="button" class="btn-accion btn-observar" onclick="abrirModalObservar(<?= (int)$sol['id_os'] ?>, '<?= htmlspecialchars($codigoVisible, ENT_QUOTES) ?>')" title="Observar o devolver solicitud con motivo justificado">
                                <i class="fa-solid fa-triangle-exclamation"></i> Observar
                            </button>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($solicitudesPorAprobar)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">
                            <i class="fa-solid fa-circle-check" style="font-size: 36px; color: #10b981; display: block; margin-bottom: 10px;"></i>
                            <strong>No hay solicitudes pendientes de aprobación en este momento.</strong>
                            <p style="margin-top: 4px; font-size: 12.5px;">Todas las solicitudes entrantes han sido aprobadas o están en custodia en la Bandeja Anónima.</p>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Sección Complementaria: Solicitudes Aprobadas Recientemente -->
    <?php if (!empty($solicitudesAprobadasRecientes)): ?>
    <div class="lab-box" style="padding: 0; overflow: hidden;">
        <div style="padding: 14px 20px; border-bottom: 1px solid #e2e8f0; background: #f8fafc; display: flex; justify-content: space-between; align-items: center;">
            <h4 style="margin: 0; font-size: 13.5px; font-weight: 700; color: #475569;">
                <i class="fa-solid fa-clock-rotate-left" style="margin-right: 6px;"></i> Solicitudes Aprobadas Recientemente (Últimas <?= count($solicitudesAprobadasRecientes) ?>)
            </h4>
            <a href="?tab=bandeja" style="font-size: 12px; font-weight: 700; color: var(--cycsa-azul); text-decoration: none;">Ver Bandeja Anónima &rarr;</a>
        </div>
        <div style="overflow-x: auto;">
            <table class="tabla-lab" style="font-size: 12.5px;">
                <thead>
                    <tr>
                        <th>Solicitud</th>
                        <th>Material</th>
                        <th>Ensayos</th>
                        <th>Muestras en Lab</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($solicitudesAprobadasRecientes as $rec): ?>
                    <tr>
                        <td style="font-family: monospace; font-weight: 700; color: #103487;">
                            <?= htmlspecialchars(!empty($rec['hoja_codigo']) ? $rec['hoja_codigo'] : $rec['codigo_os']) ?>
                            <?php if (!empty($rec['numero_registro'])): ?>
                                <span style="font-size: 10px; background: #e0f2fe; color: #0369a1; padding: 1px 5px; border-radius: 4px; font-weight: 700;">Reg: <?= htmlspecialchars($rec['numero_registro']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td><?= htmlspecialchars($rec['naturaleza_muestra'] ?: 'Material General') ?></td>
                        <td><?= htmlspecialchars($rec['ensayos'][0]['descripcion_ensayo'] ?? 'Ensayo Estándar') ?></td>
                        <td><span class="badge-lab badge-curado"><i class="fa-solid fa-flask"></i> <?= (int)$rec['total_recibidas_lab'] ?> lote(s) en custodia</span></td>
                        <td><span class="badge-lab badge-activo">Aceptada en Lab</span></td>
                        <td style="text-align: right; white-space: nowrap;">
                            <a href="/Cycsa/publico/laboratorio/imprimir-solicitud?id_os=<?= (int)$rec['id_os'] ?><?= !empty($rec['id_hoja']) ? '&id_hoja=' . (int)$rec['id_hoja'] : '' ?>" target="_blank" class="btn-accion btn-os" title="Ver formato oficial RT-FM-60">
                                <i class="fa-solid fa-file-pdf"></i> Hoja RT-FM-60
                            </a>
                            <a href="?tab=bandeja" class="btn-accion btn-detalle" title="Ir a la Bandeja Anónima">
                                <i class="fa-solid fa-table-list"></i> Bandeja
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

<!-- =========================================================================
     PESTAÑA 2: BANDEJA ANÓNIMA DE LABORATORIO
     ========================================================================= -->
<?php elseif ($tabActiva === 'bandeja'): ?>

    <!-- Tarjetas KPI de Laboratorio Operativo -->
    <div class="kpi-grid">
        <div class="kpi-card" style="border-left: 4px solid var(--cycsa-azul);">
            <div>
                <div class="kpi-label">Muestras Registradas</div>
                <div class="kpi-num" style="color: var(--cycsa-azul);"><?= (int)$kpis['total'] ?></div>
            </div>
            <i class="fa-solid fa-flask" style="font-size: 24px; color: #94a3b8;"></i>
        </div>
        <div class="kpi-card" style="border-left: 4px solid #f59e0b;">
            <div>
                <div class="kpi-label">Roturas Programadas Hoy</div>
                <div class="kpi-num" style="color: #d97706;"><?= (int)$kpis['rotura_hoy'] ?></div>
            </div>
            <i class="fa-solid fa-calendar-day" style="font-size: 24px; color: #f59e0b;"></i>
        </div>
        <div class="kpi-card" style="border-left: 4px solid #ef4444;">
            <div>
                <div class="kpi-label">Probetas Vencidas</div>
                <div class="kpi-num" style="color: #dc2626;"><?= (int)$kpis['vencidas'] ?></div>
            </div>
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 24px; color: #ef4444;"></i>
        </div>
        <div class="kpi-card" style="border-left: 4px solid #10b981;">
            <div>
                <div class="kpi-label">Planes Activos</div>
                <div class="kpi-num" style="color: #059669;"><?= (int)$kpis['activas'] ?></div>
            </div>
            <i class="fa-solid fa-cubes-stacked" style="font-size: 24px; color: #10b981;"></i>
        </div>
    </div>

    <!-- Barra de Filtros de la Bandeja Anónima -->
    <form method="GET" action="/Cycsa/publico/laboratorio" class="filtros-bar">
        <input type="hidden" name="tab" value="bandeja">

        <div class="filtro-item" style="flex: 2; min-width: 200px;">
            <label><i class="fa-solid fa-magnifying-glass"></i> Búsqueda por Código Lab / Material</label>
            <input type="text" name="busqueda" class="filtro-input" placeholder="Ej. MS-0012-26, Cilindro, Concreto..." value="<?= htmlspecialchars($filtros['busqueda'] ?? '') ?>">
        </div>

        <div class="filtro-item" style="flex: 1; min-width: 150px;">
            <label>Estado</label>
            <select name="estado" class="filtro-input">
                <option value="">-- Todos los Estados --</option>
                <option value="Registrado" <?= ($filtros['estado'] ?? '') === 'Registrado' ? 'selected' : '' ?>>Registrado</option>
                <option value="activo" <?= ($filtros['estado'] ?? '') === 'activo' ? 'selected' : '' ?>>Plan Activo</option>
                <option value="cerrado" <?= ($filtros['estado'] ?? '') === 'cerrado' ? 'selected' : '' ?>>Plan Cerrado / Concluido</option>
            </select>
        </div>

        <div class="filtro-item" style="flex: 1; min-width: 150px;">
            <label>Tipo Ensayo / Norma</label>
            <input type="text" name="tipo_ensayo" class="filtro-input" placeholder="Ej. Compresión, ASTM..." value="<?= htmlspecialchars($filtros['tipo_ensayo'] ?? '') ?>">
        </div>

        <div class="filtro-item">
            <label>Fecha Desde</label>
            <input type="date" name="fecha_desde" class="filtro-input" value="<?= htmlspecialchars($filtros['fecha_desde'] ?? '') ?>">
        </div>

        <div class="filtro-item">
            <label>Fecha Hasta</label>
            <input type="date" name="fecha_hasta" class="filtro-input" value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? '') ?>">
        </div>

        <div style="display: flex; gap: 8px;">
            <button type="submit" class="btn-filtro activo"><i class="fa-solid fa-filter"></i> Filtrar</button>
            <a href="?tab=bandeja&rotura_hoy=1" class="btn-filtro <?= !empty($filtros['rotura_hoy']) ? 'activo' : '' ?>"><i class="fa-solid fa-calendar-day"></i> Rotura Hoy</a>
            <a href="?tab=bandeja&vencidas=1" class="btn-filtro btn-filtro-vencida <?= !empty($filtros['vencidas']) ? 'activo' : '' ?>" style="color:#b91c1c; border-color:#fca5a5;"><i class="fa-solid fa-clock-rotate-left"></i> Vencidas</a>
            <a href="/Cycsa/publico/laboratorio?tab=bandeja" class="btn-filtro" title="Limpiar"><i class="fa-solid fa-eraser"></i></a>
        </div>
    </form>

    <!-- Tabla Principal de la Bandeja Anónima -->
    <div class="lab-box" style="padding: 0; overflow: hidden;">
        <div style="overflow-x: auto;">
            <table class="tabla-lab">
                <thead>
                    <tr>
                        <th>Código Lab</th>
                        <th>Tipo Material / Lote</th>
                        <th>Ensayo / Plantilla</th>
                        <th>Especímenes</th>
                        <th>Fecha Moldeo</th>
                        <th>Fecha Ingreso</th>
                        <th>Próxima Rotura</th>
                        <th>Estado</th>
                        <th style="text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($muestras as $m): 
                        $claseFila = '';
                        if ($m['es_vencida']) {
                            $claseFila = 'fila-vencida';
                        } elseif ($m['es_hoy']) {
                            $claseFila = 'fila-hoy';
                        }
                    ?>
                    <tr class="<?= $claseFila ?>" onclick="window.location.href='/Cycsa/publico/laboratorio/detalle-muestra?muestra_id=<?= (int)$m['muestra_id'] ?>'">
                        <td style="font-family: monospace; font-weight: 800; font-size: 13.5px; color: #0369a1;">
                            <i class="fa-solid fa-cube" style="color: var(--cycsa-azul); margin-right: 4px;"></i>
                            <?= htmlspecialchars($m['codigo_lab'], ENT_QUOTES, 'UTF-8') ?>
                            <?php if (!empty($m['codigo_campo']) && $m['codigo_campo'] !== $m['codigo_lab']): ?>
                                <span style="display: block; font-size: 11px; color: #64748b; font-weight: 500;">Campo: <?= htmlspecialchars($m['codigo_campo']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="color: #1e293b;"><?= htmlspecialchars($m['material_descripcion'], ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if (!empty($m['diseno_resistencia'])): ?>
                                <span style="display: block; font-size: 11px; color: #64748b;">f'c: <?= htmlspecialchars($m['diseno_resistencia']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span style="font-weight: 600; color: #334155;"><?= htmlspecialchars($m['plantilla_nombre'] ?: 'Ensayo General') ?></span>
                            <?php if (!empty($m['norma_referencia'])): ?>
                                <span style="display: block; font-size: 11px; color: #64748b; font-family: monospace;"><?= htmlspecialchars($m['norma_referencia']) ?></span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <strong style="font-size: 13px; color: #1e293b;"><?= (int)$m['total_especimenes'] ?></strong>
                            <span style="font-size: 11.5px; color: #64748b;">(<?= (int)$m['especimenes_ensayados'] ?> ensayados)</span>
                        </td>
                        <td><?= htmlspecialchars(date('d/m/Y', strtotime($m['fecha_moldeo'])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars(date('d/m/Y H:i', strtotime($m['fecha_ingreso'])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <?php if (!empty($m['proxima_rotura'])): ?>
                                <strong><?= htmlspecialchars(date('d/m/Y', strtotime($m['proxima_rotura'])), ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if ($m['es_vencida']): ?>
                                    <span class="badge-lab badge-vencida" style="margin-left: 4px;"><i class="fa-solid fa-triangle-exclamation"></i> Vencida</span>
                                <?php elseif ($m['es_hoy']): ?>
                                    <span class="badge-lab badge-hoy" style="margin-left: 4px;"><i class="fa-solid fa-bell"></i> Hoy</span>
                                <?php endif; ?>
                            <?php else: ?>
                                <span style="color: #94a3b8; font-style: italic;">Sin programar</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <?php if ($m['estado_plan'] === 'activo'): ?>
                                <span class="badge-lab badge-activo">Plan Activo</span>
                            <?php elseif ($m['estado_plan'] === 'cerrado'): ?>
                                <span class="badge-lab badge-cerrado">Cerrado</span>
                            <?php else: ?>
                                <span class="badge-lab badge-curado">En Curado</span>
                            <?php endif; ?>
                        </td>
                        <td style="text-align: right; white-space: nowrap;" onclick="event.stopPropagation();">
                            <a href="/Cycsa/publico/laboratorio/detalle-muestra?muestra_id=<?= (int)$m['muestra_id'] ?>" class="btn-accion btn-detalle" title="Abrir detalle de la muestra y plan de roturas">
                                <i class="fa-solid fa-folder-open"></i> Detalle
                            </a>
                            <?php if ($puedeReidentificar): ?>
                                <button type="button" class="btn-accion btn-reidentificar" onclick="abrirModalReidentificar('<?= htmlspecialchars($m['codigo_lab'], ENT_QUOTES, 'UTF-8') ?>', <?= (int)$m['muestra_id'] ?>)" title="Reidentificación auditada bajo ISO 17025">
                                    <i class="fa-solid fa-shield-halved"></i> Reidentificar
                                </button>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($muestras)): ?>
                    <tr>
                        <td colspan="9" style="text-align: center; padding: 40px; color: #64748b;">
                            <i class="fa-solid fa-inbox" style="font-size: 32px; color: #cbd5e1; display: block; margin-bottom: 10px;"></i>
                            No se encontraron muestras en la bandeja con los filtros seleccionados.
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<!-- =========================================================================
     PESTAÑA 3: PRÓXIMAS ROTURAS (7 DÍAS)
     ========================================================================= -->
<?php elseif ($tabActiva === 'programadas'): ?>

    <div class="lab-box">
        <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <h4 class="section-title"><i class="fa-solid fa-calendar-check" style="margin-right: 6px; color:#ef4444;"></i> Cronograma de Rupturas Periódicas (Próximos 7 días)</h4>
            <span style="font-size: 12px; color: #64748b;">Modo Ciego ISO 17025</span>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="tabla-lab">
                <thead>
                    <tr>
                        <th>Fecha Programada</th>
                        <th>Código Espécimen</th>
                        <th>Material</th>
                        <th>Dimensiones</th>
                        <th>Edad (Días)</th>
                        <th>Tolerancia ASTM C39</th>
                        <th>f'c Diseño</th>
                        <th style="text-align: right;">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rupturas as $r): 
                        $esHoy = (date('Y-m-d') === $r['fecha_programada']);
                        $esVencida = ($r['fecha_programada'] < date('Y-m-d'));
                    ?>
                    <tr style="<?= $esVencida ? 'background-color:#fee2e2;' : ($esHoy ? 'background-color:#fef3c7;' : '') ?>">
                        <td>
                            <strong><?= htmlspecialchars(date('d/m/Y', strtotime($r['fecha_programada'])), ENT_QUOTES, 'UTF-8') ?></strong>
                            <?php if ($esVencida): ?>
                                <span class="badge-lab badge-vencida">Vencida</span>
                            <?php elseif ($esHoy): ?>
                                <span class="badge-lab badge-hoy">Hoy</span>
                            <?php endif; ?>
                        </td>
                        <td style="font-family: monospace; font-weight: 800; color: #0369a1;"><?= htmlspecialchars($r['codigo_lab'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($r['material_descripcion'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td><?= htmlspecialchars($r['dimensiones'] ?: '15 x 30 cm', ENT_QUOTES, 'UTF-8') ?></td>
                        <td><strong style="color: #7c3aed;"><?= (int)$r['edad_dias'] ?> días</strong></td>
                        <td>&plusmn; <?= (int)$r['tolerancia_horas'] ?> hrs</td>
                        <td><?= htmlspecialchars($r['diseno_resistencia'] ?: 'N/A') ?></td>
                        <td style="text-align: right;">
                            <a href="/Cycsa/publico/laboratorio/detalle-muestra?muestra_id=<?= (int)$r['muestra_id'] ?>" class="btn-accion btn-detalle">
                                <i class="fa-solid fa-hammer"></i> Ensayar
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rupturas)): ?>
                    <tr>
                        <td colspan="8" style="text-align: center; padding: 35px; color: #64748b;">No hay ensayos programados para los próximos días.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<!-- =========================================================================
     MODAL DE APROBACIÓN E INGRESO FÍSICO A LABORATORIO (ISO 17025)
     ========================================================================= -->
<div id="modalAprobarMuestra" class="modal-lab">
    <div class="modal-lab-content">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #047857; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-check"></i> Aprobación e Ingreso Físico a Laboratorio
            </h3>
            <button onclick="cerrarModalAprobar()" style="border: none; background: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 8px; padding: 12px; font-size: 12.5px; color: #065f46; margin-bottom: 15px;">
            <i class="fa-solid fa-circle-info"></i> <strong>Custodia y Trazabilidad (ISO 17025):</strong> Al aprobar la solicitud, las muestras físicas ingresan a custodia de laboratorio. Se les asignará correlativo oficial ciego (<strong>MS-XXXX-26</strong> o <strong>MC-XXXX-26</strong>) y se crearán automáticamente los especímenes para el programa de roturas periódicas.
        </div>

        <form method="POST" action="/Cycsa/publico/laboratorio/aceptar-muestra">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" id="aprob_id_os" name="id_os" value="">
            <input type="hidden" id="aprob_id_hoja" name="id_hoja" value="">
            <input type="hidden" id="aprob_id_detalle_cotizacion" name="id_detalle_cotizacion" value="">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Código de Solicitud / Hoja:</label>
                    <div id="aprob_codigo_display" style="font-family: monospace; font-size: 14px; font-weight: 800; color: #0369a1; padding: 8px 12px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px;"></div>
                </div>
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Probetas Declaradas:</label>
                    <div id="aprob_cant_display" style="font-size: 14px; font-weight: 800; color: #1e293b; padding: 8px 12px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px;"></div>
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 14px;">
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Fecha y Hora de Recepción:</label>
                    <input type="datetime-local" name="fecha_recepcion" class="filtro-input" style="width: 100%; box-sizing: border-box;" value="<?= date('Y-m-d\TH:i') ?>" required>
                </div>
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Recibido en Laboratorio Por:</label>
                    <input type="text" name="recibido_por" class="filtro-input" style="width: 100%; box-sizing: border-box;" value="<?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Laboratorista') ?>" required>
                </div>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Entregado Por (Transporte / Muestreador):</label>
                <input type="text" name="entregado_por" class="filtro-input" style="width: 100%; box-sizing: border-box;" value="Transporte / Técnico de Muestreo" required>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Observaciones Físicas de Recepción:</label>
                <textarea name="observaciones" rows="2" class="filtro-input" style="width: 100%; box-sizing: border-box;" placeholder="Ej. Especímenes cilíndricos recibidos en buen estado físico, sin fisuras aparentes.">Muestras aceptadas bajo estricta conformidad técnica con la Hoja CYCSA-RT-FM-13 (ISO 17025)</textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-filtro" onclick="cerrarModalAprobar()">Cancelar</button>
                <button type="submit" class="btn-accion btn-aprobar" style="font-size: 13px; padding: 8px 18px;">
                    <i class="fa-solid fa-check-double"></i> Confirmar Aprobación e Ingreso a Lab
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================================
     MODAL PARA OBSERVAR / DEVOLVER SOLICITUD
     ========================================================================= -->
<div id="modalObservarSolicitud" class="modal-lab">
    <div class="modal-lab-content" style="max-width: 520px;">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #b91c1c; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-triangle-exclamation"></i> Observar / Devolver Solicitud
            </h3>
            <button onclick="cerrarModalObservar()" style="border: none; background: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <form method="POST" action="/Cycsa/publico/laboratorio/cambiar-estado">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" id="obs_id_os" name="id_os" value="">
            <input type="hidden" name="nuevo_estado" value="Estado 2: Observada">

            <div style="margin-bottom: 14px;">
                <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Solicitud a Devolver:</label>
                <div id="obs_codigo_display" style="font-family: monospace; font-size: 14px; font-weight: 800; color: #b91c1c; padding: 8px 12px; background: #fff5f5; border: 1px solid #fecaca; border-radius: 6px;"></div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 11.5px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Motivo Justificado de Observación (Obligatorio):</label>
                <textarea name="motivo_observacion" rows="3" class="filtro-input" style="width: 100%; box-sizing: border-box;" placeholder="Ej. Probetas incompletas, falta firma de muestreo, probeta dañada en traslado..." required></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-filtro" onclick="cerrarModalObservar()">Cancelar</button>
                <button type="submit" class="btn-accion btn-observar" style="font-size: 13px; padding: 8px 18px;">
                    <i class="fa-solid fa-triangle-exclamation"></i> Registrar Observación y Devolver
                </button>
            </div>
        </form>
    </div>
</div>

<!-- =========================================================================
     MODAL DE REIDENTIFICACIÓN AUDITADA (ISO 17025 Cláusula 4.1 y 4.2)
     ========================================================================= -->
<?php if ($puedeReidentificar): ?>
<div id="modalReidentificar" class="modal-lab">
    <div class="modal-lab-content">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">
            <h3 style="margin: 0; font-size: 16px; font-weight: 800; color: #b45309; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-shield-halved"></i> Reidentificación de Muestra (Auditoría ISO 17025)
            </h3>
            <button onclick="cerrarModalReidentificar()" style="border: none; background: none; font-size: 20px; cursor: pointer; color: #64748b;">&times;</button>
        </div>

        <div style="background: #fffbeb; border: 1px solid #fef3c7; border-radius: 8px; padding: 12px; font-size: 12.5px; color: #92400e; margin-bottom: 15px;">
            <i class="fa-solid fa-triangle-exclamation"></i> <strong>Aviso de Trazabilidad:</strong> Esta acción levanta el blindaje técnico de imparcialidad. Su usuario, rol, fecha/hora exacta, dirección IP y el motivo ingresado quedarán registrados de manera inmutable en la bitácora oficial de auditoría.
        </div>

        <form id="formReidentificar" onsubmit="ejecutarReidentificacion(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" id="reid_codigo_lab" name="codigo_lab" value="">
            <input type="hidden" id="reid_muestra_id" name="muestra_id" value="">

            <div style="margin-bottom: 14px;">
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Código de Laboratorio:</label>
                <div id="reid_codigo_display" style="font-family: monospace; font-size: 16px; font-weight: 800; color: #0369a1; padding: 8px 12px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px;"></div>
            </div>

            <div style="margin-bottom: 16px;">
                <label style="font-size: 12px; font-weight: 700; color: #475569; display: block; margin-bottom: 4px;">Motivo Justificado de Reidentificación (Obligatorio):</label>
                <textarea id="reid_motivo" name="motivo" rows="3" class="filtro-input" style="width: 100%; box-sizing: border-box;" placeholder="Ej. Auditoría de organismo acreditador ONA / Consulta formal por reclamo de cliente..." required></textarea>
            </div>

            <div id="reid_resultado" style="display: none; margin-bottom: 15px; padding: 14px; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 8px; font-size: 13px;">
                <!-- Rellenado dinámicamente vía AJAX -->
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px;">
                <button type="button" class="btn-filtro" onclick="cerrarModalReidentificar()">Cancelar</button>
                <button type="submit" id="btn_submit_reid" class="btn-accion btn-reidentificar" style="font-size: 13px; padding: 8px 16px;">
                    <i class="fa-solid fa-key"></i> Autorizar y Consultar
                </button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<script>
function abrirModalAprobar(idOS, codigoDoc, cantMuestras, idDetalleCot, idHoja) {
    document.getElementById('aprob_id_os').value = idOS;
    document.getElementById('aprob_id_hoja').value = idHoja || '';
    document.getElementById('aprob_codigo_display').innerText = codigoDoc;
    document.getElementById('aprob_cant_display').innerText = cantMuestras + ' probeta(s) declarada(s)';
    document.getElementById('aprob_id_detalle_cotizacion').value = idDetalleCot || '';
    document.getElementById('modalAprobarMuestra').style.display = 'block';
}

function cerrarModalAprobar() {
    document.getElementById('modalAprobarMuestra').style.display = 'none';
}

function abrirModalObservar(idOS, codigoDoc) {
    document.getElementById('obs_id_os').value = idOS;
    document.getElementById('obs_codigo_display').innerText = codigoDoc;
    document.getElementById('modalObservarSolicitud').style.display = 'block';
}

function cerrarModalObservar() {
    document.getElementById('modalObservarSolicitud').style.display = 'none';
}

function abrirModalReidentificar(codigoLab, muestraId) {
    const modal = document.getElementById('modalReidentificar');
    if (!modal) return;
    document.getElementById('reid_codigo_lab').value = codigoLab;
    document.getElementById('reid_muestra_id').value = muestraId;
    document.getElementById('reid_codigo_display').innerText = codigoLab;
    document.getElementById('reid_motivo').value = '';
    document.getElementById('reid_resultado').style.display = 'none';
    document.getElementById('reid_resultado').innerHTML = '';
    modal.style.display = 'block';
}

function cerrarModalReidentificar() {
    const modal = document.getElementById('modalReidentificar');
    if (modal) modal.style.display = 'none';
}

function ejecutarReidentificacion(e) {
    e.preventDefault();
    const codigoLab = document.getElementById('reid_codigo_lab').value;
    const muestraId = document.getElementById('reid_muestra_id').value;
    const motivo = document.getElementById('reid_motivo').value.trim();
    const token = document.querySelector('input[name="csrf_token"]').value;

    if (motivo.length < 5) {
        alert('Debe ingresar un motivo justificado de al menos 5 caracteres.');
        return;
    }

    const btn = document.getElementById('btn_submit_reid');
    btn.disabled = true;
    btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Auditando...';

    const formData = new FormData();
    formData.append('csrf_token', token);
    formData.append('codigo_lab', codigoLab);
    formData.append('muestra_id', muestraId);
    formData.append('motivo', motivo);

    fetch('/Cycsa/publico/laboratorio/reidentificar', {
        method: 'POST',
        body: formData
    })
    .then(r => r.json())
    .then(res => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-key"></i> Autorizar y Consultar';

        const box = document.getElementById('reid_resultado');
        box.style.display = 'block';

        if (res.status === 'success' && res.datos) {
            box.style.borderColor = '#10b981';
            box.style.background = '#f0fdf4';
            box.innerHTML = `
                <div style="font-weight: 800; color: #166534; margin-bottom: 8px;">
                    <i class="fa-solid fa-circle-check"></i> Reidentificación Aprobada y Auditada
                </div>
                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 12px;">
                    <div><strong>Cliente / Razón Social:</strong> <br><span style="color:#0f172a; font-weight:700;">${res.datos.cliente}</span></div>
                    <div><strong>RUC / Identificación:</strong> <br><span>${res.datos.ruc || '--'}</span></div>
                    <div><strong>Proyecto / Obra:</strong> <br><span>${res.datos.proyecto || '--'}</span></div>
                    <div><strong>Orden de Servicio:</strong> <br><span style="font-family:monospace; font-weight:700;">${res.datos.codigo_os}</span></div>
                    <div><strong>Contacto:</strong> <br><span>${res.datos.contacto || '--'}</span></div>
                    <div><strong>Ubicación Proyecto:</strong> <br><span>${res.datos.direccion_proyecto || '--'}</span></div>
                </div>
            `;
        } else {
            box.style.borderColor = '#ef4444';
            box.style.background = '#fef2f2';
            box.innerHTML = `<span style="color:#b91c1c; font-weight:700;"><i class="fa-solid fa-circle-xmark"></i> ${res.message || 'Error al reidentificar muestra.'}</span>`;
        }
    })
    .catch(err => {
        btn.disabled = false;
        btn.innerHTML = '<i class="fa-solid fa-key"></i> Autorizar y Consultar';
        alert('Error en la comunicación con el servidor.');
    });
}

// Cerrar modales al hacer clic fuera del contenido
window.onclick = function(event) {
    const modalA = document.getElementById('modalAprobarMuestra');
    const modalO = document.getElementById('modalObservarSolicitud');
    const modalR = document.getElementById('modalReidentificar');
    if (event.target === modalA) modalA.style.display = 'none';
    if (event.target === modalO) modalO.style.display = 'none';
    if (event.target === modalR) modalR.style.display = 'none';
}
</script>
