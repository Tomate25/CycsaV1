<?php
// Laboratorio Dashboard - Portal de Gestión Técnica ISO 17025 con Tablero Kanban
$tabActiva = $_GET['tab'] ?? 'kanban';
if (!in_array($tabActiva, ['kanban', 'operativo'])) {
    $tabActiva = 'kanban';
}

$totalKanban = count($kanban['recien_llegadas'] ?? []) + 
               count($kanban['en_revision'] ?? []) + 
               count($kanban['aceptadas_lab'] ?? []) + 
               count($kanban['finalizadas'] ?? []);
?>
<style>
    :root {
        --cycsa-azul: #103487;
        --cycsa-azul-hover: #0c2766;
        --cycsa-rojo: #e31837;
        --color-success: #10b981;
        --color-success-hover: #059669;
        --color-warning: #f59e0b;
        --color-slate-50: #f8fafc;
        --color-slate-100: #f1f5f9;
        --color-slate-200: #e2e8f0;
        --color-slate-300: #cbd5e1;
        --color-slate-600: #475569;
        --color-slate-700: #334155;
        --color-slate-800: #1e293b;
        --color-slate-900: #0f172a;
    }

    .lab-alert { background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 14px 18px; border-radius: 8px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px; font-size: 13.5px; font-weight: 500; }
    .lab-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 15px rgba(0,0,0,0.03); margin-bottom: 25px; border: 1px solid var(--color-slate-200); }
    .section-title { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: var(--cycsa-azul); margin: 0; padding-bottom: 8px; }
    
    /* Pestañas Superiores Estilo Pill */
    .lab-tabs-nav {
        display: flex;
        gap: 12px;
        margin-bottom: 22px;
        background: #f1f5f9;
        padding: 6px;
        border-radius: 10px;
        width: fit-content;
        border: 1px solid #e2e8f0;
    }
    .lab-tab-link {
        padding: 10px 22px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 700;
        font-size: 13.5px;
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
        padding: 2px 8px;
        border-radius: 12px;
        font-size: 11px;
        font-weight: 800;
    }
    .tab-badge-active { background: rgba(255, 255, 255, 0.25); color: white; }
    .tab-badge-inactive { background: #cbd5e1; color: #1e293b; }

    /* TABLERO KANBAN DE 4 COLUMNAS */
    .kanban-board {
        display: grid;
        grid-template-columns: repeat(4, 1fr);
        gap: 16px;
        align-items: start;
        margin-top: 15px;
    }
    @media (max-width: 1200px) {
        .kanban-board { grid-template-columns: repeat(2, 1fr); }
    }
    @media (max-width: 768px) {
        .kanban-board { grid-template-columns: 1fr; }
    }

    .kanban-column {
        background: #f8fafc;
        border: 1px solid #e2e8f0;
        border-radius: 10px;
        padding: 14px;
        min-height: 500px;
        display: flex;
        flex-direction: column;
    }
    .kanban-column-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding-bottom: 12px;
        margin-bottom: 14px;
        border-bottom: 2px solid #e2e8f0;
    }
    .kanban-col-title {
        font-family: 'Outfit', sans-serif;
        font-size: 14px;
        font-weight: 800;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        display: flex;
        align-items: center;
        gap: 6px;
    }
    .kanban-badge-count {
        font-size: 12px;
        font-weight: 800;
        padding: 2px 8px;
        border-radius: 12px;
        background: #e2e8f0;
        color: #334155;
    }

    .col-llegadas { border-top: 4px solid #f59e0b; }
    .col-revision { border-top: 4px solid #3b82f6; }
    .col-aceptadas { border-top: 4px solid #10b981; }
    .col-finalizadas { border-top: 4px solid #64748b; }

    .kanban-cards-list {
        display: flex;
        flex-direction: column;
        gap: 12px;
        flex-grow: 1;
    }

    .kanban-card {
        background: white;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        padding: 14px;
        box-shadow: 0 2px 5px rgba(0,0,0,0.03);
        transition: all 0.2s;
    }
    .kanban-card:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 14px rgba(0,0,0,0.06);
        border-color: #cbd5e1;
    }
    .kanban-card-top {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        margin-bottom: 8px;
    }
    .kanban-card-os {
        font-family: monospace;
        font-size: 14px;
        font-weight: 800;
        color: #103487;
    }
    .kanban-card-doc {
        font-size: 11px;
        font-weight: 600;
        color: #64748b;
    }

    .tag-especimen {
        background: #eff6ff;
        border: 1px solid #bfdbfe;
        color: #1e40af;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: monospace;
        font-weight: 700;
        font-size: 11px;
        display: inline-block;
        margin: 2px 2px 2px 0;
    }
    .tag-muestra-lab {
        background: #ecfdf5;
        border: 1px solid #a7f3d0;
        color: #047857;
        padding: 2px 6px;
        border-radius: 4px;
        font-family: monospace;
        font-weight: 800;
        font-size: 11.5px;
        display: inline-block;
        margin: 2px 2px 2px 0;
    }

    .kanban-card-actions {
        display: flex;
        gap: 6px;
        margin-top: 12px;
        padding-top: 10px;
        border-top: 1px solid #f1f5f9;
        flex-wrap: wrap;
    }

    .btn-accion { border: none; background: none; cursor: pointer; padding: 7px 12px; border-radius: 6px; font-size: 12px; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; font-weight: 600; }
    .btn-os { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .btn-os:hover { background-color: #dbeafe; }
    .btn-success-lab { background-color: #10b981; color: white; border: 1px solid #059669; }
    .btn-success-lab:hover { background-color: #059669; transform: translateY(-1px); }
    .btn-secondary-lab { background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
    .btn-secondary-lab:hover { background-color: #e2e8f0; }

    /* Modales */
    .modal-premium { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .modal-premium-content { background-color: #fff; margin: 4% auto; padding: 25px 30px; border: 1px solid var(--color-slate-200); width: 48%; max-width: 650px; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
</style>

<div class="header-flex" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;">Portal de Laboratorio (LIMS)</h2>
        <p style="color: #64748b; margin-top: 4px; font-size: 14px;">Gestión de Custodia, Rupturas y Ensayos Conforme a ISO 17025</p>
    </div>
</div>

<!-- NAVEGACIÓN DE PESTAÑAS -->
<div class="lab-tabs-nav">
    <a href="?tab=kanban" class="lab-tab-link <?= $tabActiva === 'kanban' ? 'active' : '' ?>">
        <i class="fa-solid fa-table-columns"></i> 
        <span>1. Tablero Kanban de Solicitudes (ISO 17025)</span>
        <span class="tab-badge <?= $tabActiva === 'kanban' ? 'tab-badge-active' : 'tab-badge-inactive' ?>">
            <?= $totalKanban ?>
        </span>
    </a>
    <a href="?tab=operativo" class="lab-tab-link <?= $tabActiva === 'operativo' ? 'active' : '' ?>">
        <i class="fa-solid fa-flask-vial"></i> 
        <span>2. Laboratorio Operativo (Rupturas y Matrices)</span>
        <span class="tab-badge <?= $tabActiva === 'operativo' ? 'tab-badge-active' : 'tab-badge-inactive' ?>">
            <?= count($muestras ?? []) ?>
        </span>
    </a>
</div>

<!-- =========================================================================
     APARTADO 1: TABLERO KANBAN DE SOLICITUDES Y CUSTODIA ISO 17025
     ========================================================================= -->
<?php if ($tabActiva === 'kanban'): ?>

    <div class="lab-alert">
        <i class="fa-solid fa-user-shield" style="font-size: 22px; color: #0284c7;"></i>
        <div>
            <strong>TABLERO DE CONTROL KANBAN (MODO CIEGO ISO 17025):</strong> Flujo técnico de muestras con resguardo de imparcialidad sin datos comerciales ni nombres de clientes. Las órdenes avanzan estrictamente por código de solicitud y código oficial de laboratorio (<strong>MS-XXXX-26</strong> o <strong>MC-XXXX-26</strong>).
        </div>
    </div>

    <div class="kanban-board">
        <!-- COLUMNA 1: RECIÉN LLEGADAS -->
        <div class="kanban-column col-llegadas">
            <div class="kanban-column-header">
                <div class="kanban-col-title" style="color: #d97706;">
                    <i class="fa-solid fa-inbox"></i> 1. Recién Llegadas
                </div>
                <span class="kanban-badge-count"><?= count($kanban['recien_llegadas'] ?? []) ?></span>
            </div>
            <div class="kanban-cards-list">
                <?php foreach (($kanban['recien_llegadas'] ?? []) as $sol): 
                    $esDevuelta = !empty($sol['motivo_observacion']) || $sol['estado_os'] === 'Estado 2: Observada';
                ?>
                    <div class="kanban-card" style="<?= $esDevuelta ? 'border-left: 3.5px solid #ef4444; background:#fffcfc;' : '' ?>">
                        <div class="kanban-card-top">
                            <div>
                                <div class="kanban-card-os" style="color:#103487; font-weight:800; font-size:13px;">
                                    <i class="fa-solid fa-file-waveform"></i> Solicitud: <?= htmlspecialchars($sol['hoja_codigo'] ?: 'CYCSA-RT-FM-60') ?>
                                </div>
                                <div class="kanban-card-doc">Ingreso Técnico Laboratorio</div>
                            </div>
                            <?php if ($esDevuelta): ?>
                                <span style="font-size: 10.5px; font-weight:700; background:#fee2e2; color:#b91c1c; padding:3px 8px; border-radius:12px; display:inline-flex; align-items:center; gap:4px;">
                                    <i class="fa-solid fa-triangle-exclamation"></i> Devuelta
                                </span>
                            <?php else: ?>
                                <span style="font-size: 10.5px; font-weight:700; background:#fef3c7; color:#92400e; padding:3px 7px; border-radius:12px;">Por Enviar</span>
                            <?php endif; ?>
                        </div>

                        <div style="font-size: 12.5px; margin: 6px 0;">
                            <div style="font-weight:700; color:#103487; display:flex; align-items:flex-start; gap:6px;">
                                <i class="fa-solid fa-flask" style="color:var(--cycsa-azul); margin-top:3px;"></i> 
                                <span><?= htmlspecialchars($sol['ensayos'][0]['descripcion_ensayo'] ?? 'Ensayo Técnico') ?></span>
                            </div>
                            <?php if (!empty($sol['naturaleza_muestra'])): ?>
                                <div style="font-size: 11px; color: #64748b; margin-top:2px;">
                                    <i class="fa-solid fa-layer-group"></i> Matriz: <?= htmlspecialchars($sol['naturaleza_muestra']) ?>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div style="font-size: 11.5px; margin: 4px 0;">
                            <span style="color:#64748b; font-size:11px;">Muestras Declaradas:</span>
                            <div>
                                <?php if (!empty($sol['muestras_declaradas'])): ?>
                                    <?php foreach ($sol['muestras_declaradas'] as $md): ?>
                                        <span class="tag-especimen"><?= htmlspecialchars($md['nombre_muestra'] ?? 'Muestra') ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-style:italic; font-size:11px;">1 Lote General</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- MOTIVO DE DEVOLUCIÓN VISIBLE Y RESALTADO -->
                        <?php if ($esDevuelta): ?>
                            <div style="background:#fff1f2; border:1.5px solid #fca5a5; border-radius:8px; padding:10px 12px; margin:8px 0;">
                                <div style="font-size:11px; font-weight:800; color:#991b1b; text-transform:uppercase; display:flex; align-items:center; gap:5px; margin-bottom:3px;">
                                    <i class="fa-solid fa-circle-exclamation" style="color:#e11d48;"></i> Motivo de Devolución:
                                </div>
                                <div style="font-size:12px; color:#881337; font-weight:600; line-height:1.4;">
                                    <?= nl2br(htmlspecialchars($sol['motivo_observacion'])) ?>
                                </div>
                            </div>
                        <?php endif; ?>

                        <!-- ACCIÓN ÚNICA: ENVIAR A REVISIÓN -->
                        <div class="kanban-card-actions">
                            <button type="button" class="btn-accion btn-os" style="width:100%; justify-content: center; font-size:12px; padding:7px 10px; font-weight:700;"
                                    onclick="enviarRevisionKanban(<?= $sol['id_os'] ?>, '<?= htmlspecialchars($sol['hoja_codigo'] ?: 'CYCSA-RT-FM-60') ?>')" title="Mandar la solicitud a revisión por supervisión">
                                <i class="fa-solid fa-paper-plane"></i> <?= $esDevuelta ? 'Reenviar a Revisión' : 'Enviar a Revisión' ?>
                            </button>
                        </div>

                        <div style="margin-top:8px; text-align:center;">
                            <a href="/Cycsa/publico/laboratorio/imprimir-solicitud?id_os=<?= $sol['id_os'] ?>" target="_blank" style="font-size:11.5px; color:#103487; text-decoration:none; font-weight:700; display:inline-flex; align-items:center; gap:5px;">
                                <i class="fa-solid fa-file-lines"></i> Ver Hoja Solicitud Lab (RT-FM-60)
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($kanban['recien_llegadas'])): ?>
                    <div style="text-align:center; padding:30px 10px; color:#94a3b8; font-size:12.5px;">Sin solicitudes pendientes</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- COLUMNA 2: EN REVISIÓN -->
        <div class="kanban-column col-revision">
            <div class="kanban-column-header">
                <div class="kanban-col-title" style="color: #2563eb;">
                    <i class="fa-solid fa-magnifying-glass"></i> 2. En Revisión
                </div>
                <span class="kanban-badge-count"><?= count($kanban['en_revision'] ?? []) ?></span>
            </div>
            <div class="kanban-cards-list">
                <?php foreach (($kanban['en_revision'] ?? []) as $sol): ?>
                    <div class="kanban-card" style="border-left: 3px solid #3b82f6;">
                        <div class="kanban-card-top">
                            <div>
                                <div class="kanban-card-os" style="color:#103487; font-weight:800; font-size:13px;">
                                    <i class="fa-solid fa-file-waveform"></i> Solicitud: <?= htmlspecialchars($sol['hoja_codigo'] ?: 'CYCSA-RT-FM-60') ?>
                                </div>
                                <div class="kanban-card-doc">Ingreso Técnico Laboratorio</div>
                            </div>
                            <span style="font-size: 10.5px; font-weight:700; background:#dbeafe; color:#1e40af; padding:3px 7px; border-radius:12px;">En Revisión</span>
                        </div>
                        <div style="font-size: 12.5px; margin: 6px 0;">
                            <div style="font-weight:700; color:#103487; display:flex; align-items:flex-start; gap:6px;">
                                <i class="fa-solid fa-flask" style="color:var(--cycsa-azul); margin-top:3px;"></i> 
                                <span><?= htmlspecialchars($sol['ensayos'][0]['descripcion_ensayo'] ?? 'Ensayo Técnico') ?></span>
                            </div>
                            <?php if (!empty($sol['naturaleza_muestra'])): ?>
                                <div style="font-size: 11px; color: #64748b; margin-top:2px;">
                                    <i class="fa-solid fa-layer-group"></i> Matriz: <?= htmlspecialchars($sol['naturaleza_muestra']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        <div style="font-size: 11.5px; margin: 4px 0;">
                            <span style="color:#64748b; font-size:11px;">Muestras a Evaluar:</span>
                            <div>
                                <?php if (!empty($sol['muestras_declaradas'])): ?>
                                    <?php foreach ($sol['muestras_declaradas'] as $md): ?>
                                        <span class="tag-especimen"><?= htmlspecialchars($md['nombre_muestra'] ?? 'Muestra') ?></span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span style="color:#94a3b8; font-style:italic; font-size:11px;">1 Lote General</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php if (!empty($sol['motivo_observacion'])): ?>
                            <div style="background:#fef2f2; border:1px solid #fecaca; border-radius:6px; padding:6px 8px; font-size:11px; color:#b91c1c; margin:6px 0;">
                                <strong>Obs previa:</strong> <?= htmlspecialchars($sol['motivo_observacion']) ?>
                            </div>
                        <?php endif; ?>

                        <!-- PASO 1: VISUALIZAR HOJA OFICIAL DE LABORATORIO RT-FM-60 -->
                        <div style="margin: 8px 0 6px 0;">
                            <a href="/Cycsa/publico/laboratorio/imprimir-solicitud?id_os=<?= $sol['id_os'] ?>" target="_blank" class="btn-accion" style="background:#eff6ff; color:#103487; border:1.5px solid #93c5fd; justify-content: center; font-size:12px; padding:7px 8px; font-weight:700; width:100%; box-sizing:border-box;">
                                <i class="fa-solid fa-file-circle-check"></i> 1. Visualizar Hoja Lab (RT-FM-60)
                            </a>
                        </div>

                        <!-- PASO 2: APROBACIÓN TÉCNICA O OBSERVACIÓN -->
                        <div class="kanban-card-actions" style="display:grid; grid-template-columns: 1fr 1fr; gap:6px;">
                            <button type="button" class="btn-accion" style="background:#fee2e2; color:#b91c1c; border:1px solid #fca5a5; justify-content: center; font-size:11.5px; padding:6px 6px;"
                                    onclick="observarSolicitudKanban(<?= $sol['id_os'] ?>, '<?= htmlspecialchars($sol['hoja_codigo'] ?: 'CYCSA-RT-FM-60') ?>')" title="Observar solicitud y devolver">
                                <i class="fa-solid fa-triangle-exclamation"></i> Observar
                            </button>
                            <button type="button" class="btn-accion btn-success-lab" style="justify-content: center; font-size:11.5px; padding:6px 6px; font-weight:700;"
                                    onclick="abrirModalAceptacionLab(<?= htmlspecialchars(json_encode($sol), ENT_QUOTES, 'UTF-8') ?>)" title="Aprobar y formalizar ingreso con códigos de laboratorio">
                                <i class="fa-solid fa-circle-check"></i> 2. Aprobar Lab
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($kanban['en_revision'])): ?>
                    <div style="text-align:center; padding:30px 10px; color:#94a3b8; font-size:12.5px;">Sin solicitudes en revisión</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- COLUMNA 3: MUESTRAS ACEPTADAS / EN LAB -->
        <div class="kanban-column col-aceptadas">
            <div class="kanban-column-header">
                <div class="kanban-col-title" style="color: #059669;">
                    <i class="fa-solid fa-flask-vial"></i> 3. Muestras Aceptadas
                </div>
                <span class="kanban-badge-count"><?= count($kanban['aceptadas_lab'] ?? []) ?></span>
            </div>
            <div class="kanban-cards-list">
                <?php foreach (($kanban['aceptadas_lab'] ?? []) as $sol): ?>
                    <div class="kanban-card" style="border-left: 3px solid #10b981;">
                        <div class="kanban-card-top">
                            <div>
                                <div class="kanban-card-os" style="color:#059669; font-weight:800; font-size:13px;">
                                    <i class="fa-solid fa-file-waveform"></i> Solicitud: <?= htmlspecialchars($sol['hoja_codigo'] ?: 'CYCSA-RT-FM-60') ?>
                                </div>
                                <div class="kanban-card-doc">Custodia Técnica de Muestras</div>
                            </div>
                            <span style="font-size: 10.5px; font-weight:700; background:#dcfce7; color:#15803d; padding:3px 7px; border-radius:12px;">En Custodia Lab</span>
                        </div>
                        <div style="font-size: 12.5px; margin: 6px 0;">
                            <div style="font-weight:700; color:#103487; display:flex; align-items:flex-start; gap:6px;">
                                <i class="fa-solid fa-flask" style="color:var(--cycsa-azul); margin-top:3px;"></i> 
                                <span><?= htmlspecialchars($sol['ensayos'][0]['descripcion_ensayo'] ?? 'Ensayo Técnico') ?></span>
                            </div>
                            <?php if (!empty($sol['naturaleza_muestra'])): ?>
                                <div style="font-size: 11px; color: #64748b; margin-top:2px;">
                                    <i class="fa-solid fa-layer-group"></i> Matriz: <?= htmlspecialchars($sol['naturaleza_muestra']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Códigos de Laboratorio Asignados -->
                        <div style="font-size: 11.5px; margin: 6px 0;">
                            <span style="color:#047857; font-weight:700; font-size:11px;"><i class="fa-solid fa-barcode"></i> Códigos Oficiales en Lab:</span>
                            <div style="margin-top: 2px;">
                                <?php if (!empty($sol['muestras_ingresadas'])): ?>
                                    <?php foreach ($sol['muestras_ingresadas'] as $mi): ?>
                                        <span class="tag-muestra-lab" title="Campo: <?= htmlspecialchars($mi['codigo_campo']) ?>">
                                            <?= htmlspecialchars($mi['codigo_muestra']) ?>
                                        </span>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <span class="tag-muestra-lab">MS-0001-26</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <!-- Acciones para Muestras Aceptadas en Laboratorio -->
                        <div class="kanban-card-actions" style="display:flex; flex-direction:column; gap:6px;">
                            <a href="/Cycsa/publico/laboratorio/imprimir-solicitud?id_os=<?= $sol['id_os'] ?>" target="_blank" class="btn-accion btn-secondary-lab" style="width: 100%; justify-content: center; font-weight: 700; font-size:11.5px;" title="Ver e Imprimir Hoja de Solicitud ISO 17025 (CYCSA-RT-FM-60)">
                                <i class="fa-solid fa-file-lines"></i> Hoja de Solicitud Lab (RT-FM-60)
                            </a>
                            <a href="/Cycsa/publico/laboratorio?tab=operativo" class="btn-accion btn-os" style="width: 100%; justify-content: center; font-weight: 700; font-size:11.5px;" title="Ir a Laboratorio Operativo para rupturas y matrices">
                                <i class="fa-solid fa-flask-vial"></i> Ir a Ensayos Operativos
                            </a>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($kanban['aceptadas_lab'])): ?>
                    <div style="text-align:center; padding:30px 10px; color:#94a3b8; font-size:12.5px;">Sin muestras en custodia</div>
                <?php endif; ?>
            </div>
        </div>

        <!-- COLUMNA 4: ENSAYOS FINALIZADOS -->
        <div class="kanban-column col-finalizadas">
            <div class="kanban-column-header">
                <div class="kanban-col-title" style="color: #475569;">
                    <i class="fa-solid fa-circle-check"></i> 4. Finalizadas
                </div>
                <span class="kanban-badge-count"><?= count($kanban['finalizadas'] ?? []) ?></span>
            </div>
            <div class="kanban-cards-list">
                <?php foreach (($kanban['finalizadas'] ?? []) as $sol): ?>
                    <div class="kanban-card" style="opacity: 0.85;">
                        <div class="kanban-card-top">
                            <div>
                                <div class="kanban-card-os" style="color:#475569; font-weight:800; font-size:13px;">
                                    <i class="fa-solid fa-file-waveform"></i> Solicitud: <?= htmlspecialchars($sol['hoja_codigo'] ?: 'CYCSA-RT-FM-60') ?>
                                </div>
                                <div class="kanban-card-doc"><?= htmlspecialchars($sol['ensayos'][0]['descripcion_ensayo'] ?? 'Ensayo Concluido') ?></div>
                            </div>
                            <span style="font-size: 10.5px; font-weight:700; background:#f1f5f9; color:#475569; padding:3px 7px; border-radius:12px;">Completada</span>
                        </div>
                        <div style="font-size: 11.5px; color:#64748b; margin-top:4px;">
                            Ensayos y matrices capturados satisfactoriamente.
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($kanban['finalizadas'])): ?>
                    <div style="text-align:center; padding:30px 10px; color:#94a3b8; font-size:12.5px;">Sin ensayos finalizados aún</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

<!-- =========================================================================
     APARTADO 2: LABORATORIO OPERATIVO (RUPTURAS Y MATRICES)
     ========================================================================= -->
<?php else: ?>

    <div class="lab-alert">
        <i class="fa-solid fa-user-shield" style="font-size: 20px;"></i>
        <div>
            <strong>POLÍTICA DE IMPARCIALIDAD ISO 17025:</strong> Este portal opera bajo modo ciego para resguardo de imparcialidad. Los analistas y técnicos registran lecturas, rupturas y matrices vinculadas estrictamente al código técnico de muestra (MS-XXXX-26).
        </div>
    </div>

    <!-- 1. RUPTURAS PROGRAMADAS (Próximos 7 días) -->
    <div class="lab-box">
        <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
            <h4 class="section-title"><i class="fa-solid fa-calendar-check" style="margin-right: 6px; color:#ef4444;"></i> Ensayos de Ruptura Programados (Compresión)</h4>
            <span style="font-size:12px; font-weight:600; color:#64748b;">Próximos 7 días</span>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="tabla-cycsa" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background:#f8fafc; text-align:left; font-size:11.5px; color:#475569;">
                        <th style="padding:10px;">Fecha Programada</th>
                        <th style="padding:10px;">Código Lab</th>
                        <th style="padding:10px;">Código Campo</th>
                        <th style="padding:10px;">Ensayo / Producto</th>
                        <th style="padding:10px;">Cilindro / Identificador</th>
                        <th style="padding:10px;">Edad (Días)</th>
                        <th style="padding:10px; text-align: right;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($rupturas as $r): 
                        $esHoy = (date('Y-m-d') === $r['fecha_programada']);
                    ?>
                    <tr style="<?= $esHoy ? 'background-color: #fef2f2;' : '' ?> border-bottom: 1px solid #f1f5f9;">
                        <td style="padding:10px; font-weight: 700; color: <?= $esHoy ? '#b91c1c' : '#334155' ?>;">
                            <?= htmlspecialchars(date('d/m/Y', strtotime($r['fecha_programada'])), ENT_QUOTES, 'UTF-8') ?>
                            <?php if ($esHoy): ?>
                                <span style="background:#ef4444; color:white; font-size:10px; padding:2px 6px; border-radius:10px; margin-left:6px;">Hoy</span>
                            <?php endif; ?>
                        </td>
                        <td style="padding:10px; font-family: monospace; font-weight: 700; color: #0369a1;"><?= htmlspecialchars($r['codigo_muestra'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px; font-family: monospace; color: #475569;"><?= htmlspecialchars($r['codigo_campo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px; font-weight: 600; color: #475569;"><?= htmlspecialchars($r['nombre_ensayo'] ?: 'Ensayo General', ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px; font-weight: 700; font-family: monospace; color: var(--cycsa-azul);"><?= htmlspecialchars($r['identificador_especimen'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px;"><strong style="color: #7c3aed;"><?= htmlspecialchars($r['edad_dias'], ENT_QUOTES, 'UTF-8') ?> días</strong></td>
                        <td style="padding:10px; text-align: right; white-space: nowrap;">
                            <a href="/Cycsa/publico/laboratorio/detalle-muestra?id_lote=<?= codificarId($r['id_lote']) ?>" class="btn-accion btn-os">
                                <i class="fa-solid fa-hammer"></i> Abrir Ensaye
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($rupturas)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 30px; color: #64748b;">No hay tareas de ruptura programadas para los próximos días.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- 2. LOTES Y MUESTRAS EN CUSTODIA (ENSAYOS EN PROCESO) -->
    <div class="lab-box">
        <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">
            <h4 class="section-title"><i class="fa-solid fa-flask-vial" style="margin-right: 6px; color:var(--cycsa-azul);"></i> Lotes y Muestras Activas en Custodia (Ensayos en Proceso)</h4>
        </div>
        
        <div style="overflow-x: auto;">
            <table class="tabla-cycsa" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background:#f8fafc; text-align:left; font-size:11.5px; color:#475569;">
                        <th style="padding:10px;">Código Lab (MS-XXXX-26)</th>
                        <th style="padding:10px;">Código Campo</th>
                        <th style="padding:10px;">Ensayo Asignado</th>
                        <th style="padding:10px;">Descripción / Identificación Lote</th>
                        <th style="padding:10px;">Fecha Recepción</th>
                        <th style="padding:10px;">Estado LIMS</th>
                        <th style="padding:10px; text-align: right;">Acción Técnica</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($muestras as $m): ?>
                    <tr style="border-bottom: 1px solid #f1f5f9;">
                        <td style="padding:10px; font-family: monospace; font-size:14px; font-weight: 800; color: #103487;"><?= htmlspecialchars($m['codigo_muestra'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px; font-family: monospace; color: #475569;"><?= htmlspecialchars($m['codigo_campo'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px; font-weight: 600; color: #0f172a;"><?= htmlspecialchars($m['nombre_ensayo'] ?: 'Ensayo de Laboratorio', ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px; color: #475569;"><?= htmlspecialchars($m['nombre_lote'], ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px;"><?= htmlspecialchars(date('d/m/Y H:i', strtotime($m['fecha_recepcion'])), ENT_QUOTES, 'UTF-8') ?></td>
                        <td style="padding:10px;">
                            <span style="background:#dcfce7; color:#166534; font-size:11px; font-weight:700; padding:3px 8px; border-radius:12px;"><?= htmlspecialchars($m['estado'], ENT_QUOTES, 'UTF-8') ?></span>
                        </td>
                        <td style="padding:10px; text-align: right; white-space: nowrap;">
                            <a href="/Cycsa/publico/laboratorio/detalle-muestra?id_lote=<?= codificarId($m['id_lote']) ?>" class="btn-accion btn-os" title="Cargar resultados en matriz de cálculo">
                                <i class="fa-solid fa-chart-simple"></i> Matriz / Hoja de Trabajo
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php if (empty($muestras)): ?>
                    <tr>
                        <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">No hay muestras activas en custodia en este momento.</td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

<?php endif; ?>

<!-- MODAL DE ACEPTACIÓN TÉCNICA DE MUESTRA EN LABORATORIO (ISO 17025) -->
<div id="modalAceptacionLab" class="modal-premium">
    <div class="modal-premium-content">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 14px; margin-bottom: 18px;">
            <h3 style="margin: 0; color: #103487; font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-inbox"></i> Aceptación Técnica de Muestras (ISO 17025)
            </h3>
            <button type="button" onclick="cerrarModalAceptacionLab()" style="border: none; background: none; font-size: 24px; cursor: pointer; color: #94a3b8;">&times;</button>
        </div>

        <form method="POST" action="/Cycsa/publico/laboratorio/aceptar-muestra">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_os" id="modal_id_os" value="">

            <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 12px; margin-bottom: 15px; font-size: 12.5px;">
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span>Solicitud Técnica:</span>
                    <strong id="modal_codigo_hoja_doc" style="font-family: monospace; color: #103487;"></strong>
                </div>
                <div style="display:flex; justify-content:space-between; margin-bottom:4px;">
                    <span>Documento Lab ISO 17025:</span>
                    <strong id="modal_codigo_hoja" style="font-family: monospace; color:#103487;">CYCSA-RT-FM-60</strong>
                </div>
                <div style="display:flex; justify-content:space-between;">
                    <span>Fecha Toma / Muestreo:</span>
                    <strong id="modal_fecha_toma"></strong>
                </div>
            </div>

            <!-- Ensayo al que se asocia la muestra -->
            <div style="margin-bottom: 15px;">
                <label style="font-size: 12.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">
                    Ensayo a Realizar:
                </label>
                <select name="id_detalle_cotizacion" id="modal_select_ensayo" class="form-control" style="width: 100%; padding: 8px 12px; font-size: 13px; border-radius: 6px; border: 1px solid #cbd5e1;" required>
                    <!-- Opciones dinámicas -->
                </select>
            </div>

            <!-- Especímenes a Registrar / Aceptar -->
            <div style="margin-bottom: 15px;">
                <label style="font-size: 12.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">
                    Especímenes / Muestras a Ingresar (Fijados en Solicitud CYCSA-RT-FM-60):
                </label>
                <div id="modal_lista_muestras_inputs" style="background: #f8fafc; padding: 12px; border-radius: 8px; border: 1px solid #cbd5e1;">
                    <!-- Se generan dinámicamente -->
                </div>
            </div>

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-bottom: 15px;">
                <div>
                    <label style="font-size: 12.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">
                        Fecha/Hora Llegada Lab:
                    </label>
                    <input type="datetime-local" name="fecha_recepcion" value="<?= date('Y-m-d\TH:i') ?>" class="form-control" style="width: 100%; padding: 8px 10px; font-size: 13px; border-radius: 6px; border: 1px solid #cbd5e1;" required>
                </div>
                <div>
                    <label style="font-size: 12.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">
                        Laboratorista Receptor:
                    </label>
                    <input type="text" name="recibido_por" value="<?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Laboratorista') ?>" class="form-control" style="width: 100%; padding: 8px 10px; font-size: 13px; border-radius: 6px; border: 1px solid #cbd5e1;" required>
                </div>
            </div>

            <div style="margin-bottom: 15px;">
                <label style="font-size: 12.5px; font-weight: 700; color: #334155; display: block; margin-bottom: 4px;">
                    Observaciones de Aceptación / Custodia ISO 17025:
                </label>
                <textarea name="observaciones" class="form-control" rows="2" style="width: 100%; padding: 8px 10px; font-size: 12.5px; border-radius: 6px; border: 1px solid #cbd5e1;" placeholder="Muestras recibidas en condiciones óptimas para ensaye conforme a norma."></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 15px;">
                <button type="button" onclick="cerrarModalAceptacionLab()" class="btn-accion btn-secondary-lab">Cancelar</button>
                <button type="submit" class="btn-accion btn-success-lab">
                    <i class="fa-solid fa-circle-check"></i> Confirmar Aceptación e Ingreso
                </button>
            </div>
        </form>
    </div>
</div>

<script>
    function abrirModalAceptacionLab(sol) {
        document.getElementById('modal_id_os').value = sol.id_os;
        document.getElementById('modal_codigo_hoja_doc').innerText = sol.hoja_codigo || 'CYCSA-RT-FM-60';
        document.getElementById('modal_codigo_hoja').innerText = 'CYCSA-RT-FM-60';
        document.getElementById('modal_fecha_toma').innerText = sol.fecha_hora_toma_muestra || 'N/A';

        // Llenar select de ensayos
        const selEns = document.getElementById('modal_select_ensayo');
        selEns.innerHTML = '';
        if (sol.ensayos && sol.ensayos.length > 0) {
            sol.ensayos.forEach(e => {
                const opt = document.createElement('option');
                opt.value = e.id;
                opt.innerText = (e.codigo_servicio ? e.codigo_servicio + ' - ' : '') + e.descripcion_ensayo;
                selEns.appendChild(opt);
            });
        }

        // Mostrar lista fija de especímenes declarados
        const container = document.getElementById('modal_lista_muestras_inputs');
        container.innerHTML = '';
        if (sol.muestras_declaradas && sol.muestras_declaradas.length > 0) {
            let html = '<div style="display:flex; flex-wrap:wrap; gap:8px;">';
            sol.muestras_declaradas.forEach((m, i) => {
                html += `
                    <div style="background:#ffffff; border:1px solid #cbd5e1; border-radius:6px; padding:6px 12px; font-size:12px; display:inline-flex; align-items:center; gap:6px;">
                        <strong style="color:#103487; font-family:monospace;">${m.nombre_muestra || ('M-' + (i+1))}</strong>
                        <span style="color:#64748b; font-size:11px;">(${m.descripcion || 'Muestra ' + (i+1)})</span>
                    </div>
                `;
            });
            html += '</div>';
            html += `<div style="font-size:11.5px; color:#059669; font-weight:600; margin-top:8px;"><i class="fa-solid fa-lock"></i> ${sol.muestras_declaradas.length} muestras fijadas por la Hoja de Solicitud Oficial CYCSA-RT-FM-60.</div>`;
            container.innerHTML = html;
        } else {
            container.innerHTML = `
                <div style="background:#ffffff; border:1px solid #cbd5e1; border-radius:6px; padding:6px 12px; font-size:12px; color:#103487; font-weight:700;">1 Lote General</div>
            `;
        }

        document.getElementById('modalAceptacionLab').style.display = 'block';
    }

    function cerrarModalAceptacionLab() {
        document.getElementById('modalAceptacionLab').style.display = 'none';
    }

    function enviarRevisionKanban(idOS, codigoSolicitud) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: '¿Enviar a Revisión?',
                text: `¿Desea enviar la Solicitud Técnica (${codigoSolicitud}) a Revisión técnica por el Supervisor de Laboratorio?`,
                icon: 'question',
                showCancelButton: true,
                confirmButtonColor: '#103487',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: '<i class="fa-solid fa-paper-plane"></i> Sí, Enviar a Revisión',
                cancelButtonText: 'Cancelar'
            }).then((result) => {
                if (result.isConfirmed) {
                    ejecutarCambioEstadoKanban(idOS, 'Estado 2: Revision');
                }
            });
        } else {
            if (confirm(`¿Desea enviar la Solicitud Técnica (${codigoSolicitud}) a Revisión técnica por el Supervisor?`)) {
                ejecutarCambioEstadoKanban(idOS, 'Estado 2: Revision');
            }
        }
    }

    function observarSolicitudKanban(idOS, codigoSolicitud) {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Observar Solicitud',
                text: `Indique el motivo de la observación para la Solicitud ${codigoSolicitud}:`,
                input: 'textarea',
                inputPlaceholder: 'Escriba las correcciones requeridas...',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#dc2626',
                cancelButtonColor: '#94a3b8',
                confirmButtonText: '<i class="fa-solid fa-triangle-exclamation"></i> Registrar Observación',
                cancelButtonText: 'Cancelar',
                inputValidator: (value) => {
                    if (!value || value.trim() === '') {
                        return 'Debe ingresar un motivo de observación.';
                    }
                }
            }).then((result) => {
                if (result.isConfirmed) {
                    ejecutarCambioEstadoKanban(idOS, 'Estado 2: Observada', result.value);
                }
            });
        } else {
            const motivo = prompt(`Indique el motivo de observación para la Solicitud ${codigoSolicitud}:`);
            if (motivo && motivo.trim() !== '') {
                ejecutarCambioEstadoKanban(idOS, 'Estado 2: Observada', motivo);
            }
        }
    }

    function ejecutarCambioEstadoKanban(idOS, nuevoEstado, motivo = '') {
        if (typeof Swal !== 'undefined') {
            Swal.fire({
                title: 'Actualizando tablero...',
                allowOutsideClick: false,
                didOpen: () => { Swal.showLoading(); }
            });
        }

        const formData = new FormData();
        formData.append('csrf_token', '<?= $_SESSION['csrf_token'] ?>');
        formData.append('id_os', idOS);
        formData.append('nuevo_estado', nuevoEstado);
        if (motivo) formData.append('motivo_observacion', motivo);
        formData.append('ajax', '1');

        fetch('/Cycsa/publico/laboratorio/cambiar-estado', {
            method: 'POST',
            body: formData
        })
        .then(r => r.json())
        .then(res => {
            if (res.status === 'success') {
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡Actualizado!',
                        text: res.message,
                        timer: 1200,
                        showConfirmButton: false
                    }).then(() => {
                        window.location.href = '/Cycsa/publico/laboratorio?tab=kanban';
                    });
                } else {
                    window.location.href = '/Cycsa/publico/laboratorio?tab=kanban';
                }
            } else {
                if (typeof Swal !== 'undefined') {
                    Swal.fire('Error', res.message || 'No se pudo actualizar el estado.', 'error');
                } else {
                    alert('Error: ' + (res.message || 'No se pudo actualizar el estado.'));
                }
            }
        })
        .catch(err => {
            console.error(err);
            if (typeof Swal !== 'undefined') {
                Swal.fire('Error', 'Error de comunicación con el servidor.', 'error');
            } else {
                alert('Error de comunicación con el servidor.');
            }
        });
    }
</script>