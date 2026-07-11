<?php
// Detalle de Lote - LIMS Laboratory specimen crush & report versioning (Premium Redesign)
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

    .lote-box { background: white; padding: 25px; border-radius: 12px; box-shadow: 0 4px 20px rgba(0,0,0,0.02); border: 1px solid var(--color-slate-200); margin-bottom: 25px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .grid-3 { display: grid; grid-template-columns: repeat(3, 1fr); gap: 20px; }
    
    .form-group { margin-bottom: 16px; display: flex; flex-direction: column; gap: 6px; }
    .form-control { padding: 10px 14px; border: 1px solid var(--color-slate-300); border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: all 0.2s; color: var(--color-slate-800); }
    .form-control:focus { border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.1); }

    .tabla-rupturas { width: 100%; border-collapse: separate; border-spacing: 0; margin-top: 15px; font-size: 14px; }
    .tabla-rupturas th { background-color: var(--color-slate-50); color: var(--color-slate-600); padding: 14px 16px; text-align: left; font-weight: 600; border-bottom: 2px solid var(--color-slate-200); font-size: 11px; text-transform: uppercase; letter-spacing: 0.5px; }
    .tabla-rupturas td { padding: 14px 16px; border-bottom: 1px solid var(--color-slate-100); vertical-align: middle; color: var(--color-slate-700); }
    .tabla-rupturas tbody tr:hover { background-color: var(--color-slate-50); }

    .status-alert { display: inline-flex; align-items: center; gap: 6px; padding: 4px 10px; border-radius: 20px; font-weight: 700; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; }
    .status-cumple { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .status-alerta { background-color: #fef2f2; color: #b91c1c; border: 1px solid #fee2e2; }
    .status-pendiente { background-color: var(--color-slate-100); color: var(--color-slate-600); border: 1px solid var(--color-slate-200); }

    .badge-estado { padding: 5px 12px; border-radius: 20px; font-size: 11px; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-block; }
    .estado-Pendiente { background-color: #fef3c7; color: #b45309; border: 1px solid #fde68a; }
    .estado-Revisado { background-color: #eff6ff; color: #1e40af; border: 1px solid #dbeafe; }
    .estado-Aprobado { background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; }
    .estado-Rechazado { background-color: #fee2e2; color: #b91c1c; border: 1px solid #fecaca; }

    .modal-premium { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .modal-premium-content { background-color: #fff; margin: 4% auto; padding: 30px; border: 1px solid var(--color-slate-200); width: 60%; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: slideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1); }

    .btn-accion { border: none; background: none; cursor: pointer; padding: 8px 16px; border-radius: 6px; font-size: 13px; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 8px; font-weight: 600; }
    .btn-os { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .btn-os:hover { background-color: #dbeafe; transform: translateY(-1px); }
    .btn-recepcion { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .btn-recepcion:hover { background-color: #dcfce7; transform: translateY(-1px); }
    .btn-detalle { background-color: var(--color-slate-100); color: var(--color-slate-700); border: 1px solid var(--color-slate-200); }
    .btn-detalle:hover { background-color: var(--color-slate-200); transform: translateY(-1px); }

    .info-label { font-size: 11px; color: var(--color-slate-600); font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: block; margin-bottom: 4px; }
    .info-value { font-size: 14.5px; color: var(--color-slate-900); font-weight: 600; }
</style>

<div class="header-flex" style="margin-bottom: 25px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0; color: var(--color-slate-900); font-family: 'Outfit', sans-serif; font-size: 24px; font-weight: 700; display: flex; align-items: center; gap: 10px;"><i class="fa-solid fa-flask-vial" style="color: var(--cycsa-azul);"></i> Control Técnico de Muestra</h2>
        <p style="color: var(--color-slate-600); margin-top: 5px; font-size: 14px;">
            Código de Laboratorio: <strong style="color: #0369a1; font-family: monospace; font-size: 15px;"><?= htmlspecialchars($lote['codigo_muestra'], ENT_QUOTES, 'UTF-8') ?></strong> 
            &nbsp;|&nbsp; 
            Código de Campo: <strong style="color: var(--color-slate-700); font-family: monospace; font-size: 15px;"><?= htmlspecialchars($lote['codigo_campo'], ENT_QUOTES, 'UTF-8') ?></strong>
        </p>
    </div>
    <a href="/Cycsa/publico/operaciones" class="btn-accion btn-detalle" style="padding: 10px 18px;"><i class="fa-solid fa-arrow-left"></i> Volver al Panel</a>
</div>

<!-- Alertas -->
<?php if (!empty($exito)): ?>
    <div class="alert alert-exito" style="background-color: #f0fdf4; color: #166534; padding: 14px 20px; border-radius: 8px; border: 1px solid #bbf7d0; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500;">
        <i class="fa-solid fa-circle-check" style="font-size: 16px;"></i> <?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>
<?php if (!empty($error)): ?>
    <div class="alert alert-error" style="background-color: #fef2f2; color: #991b1b; padding: 14px 20px; border-radius: 8px; border: 1px solid #fecaca; margin-bottom: 20px; display: flex; align-items: center; gap: 10px; font-weight: 500;">
        <i class="fa-solid fa-circle-xmark" style="font-size: 16px;"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<!-- 1. BARRERA DE CONFIDENCIALIDAD: VISTA CIEGA DE LABORATORIO -->
<?php if ($esTecnico): ?>
    <div style="background-color: #fef3c7; border: 1px solid #fde68a; color: #92400e; padding: 14px 18px; border-radius: 8px; font-size: 13.5px; font-weight: 600; margin-bottom: 25px; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-eye-slash" style="font-size: 18px;"></i>
        <strong>OPERACIÓN CIEGA ACTIVA (ISO 17025):</strong> La información de identificación del cliente y el proyecto está oculta en este rol para preservar la imparcialidad.
    </div>
<?php else: ?>
    <!-- VISTA CLIENTE COMPLETA (Atención/Coordinación/Conta) -->
    <div class="lote-box">
        <h4 style="font-family:'Outfit'; font-size:15px; font-weight:700; color:var(--cycsa-azul); border-bottom: 1px solid var(--color-slate-200); padding-bottom: 8px; margin-top: 0; margin-bottom: 15px;"><i class="fa-solid fa-user-tie"></i> Información del Cliente y O/S</h4>
        <div class="grid-3">
            <div>
                <span class="info-label">Cliente / Razón Social</span>
                <span class="info-value"><?= htmlspecialchars($lote['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div>
                <span class="info-label">Atención A</span>
                <span class="info-value"><?= htmlspecialchars($lote['atencion_a'] ?? '—', ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div>
                <span class="info-label">Orden de Servicio / Cotización</span>
                <span class="info-value" style="color:var(--cycsa-azul); font-family:monospace; font-weight:700;"><?= htmlspecialchars($lote['codigo_os'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
        <div class="grid-2" style="margin-top: 15px;">
            <div>
                <span class="info-label">Nombre del Proyecto</span>
                <span class="info-value"><?= htmlspecialchars($lote['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
            <div>
                <span class="info-label">Dirección del Proyecto</span>
                <span class="info-value"><?= htmlspecialchars($lote['direccion_proyecto'], ENT_QUOTES, 'UTF-8') ?></span>
            </div>
        </div>
    </div>
<?php endif; ?>

<!-- 2. ESPECIFICACIONES TÉCNICAS DEL LOTE -->
<div class="lote-box">
    <h4 style="font-family:'Outfit'; font-size:15px; font-weight:700; color:var(--cycsa-azul); border-bottom: 1px solid var(--color-slate-200); padding-bottom: 8px; margin-top: 0; margin-bottom: 15px;"><i class="fa-solid fa-cube"></i> Especificaciones del Lote</h4>
    <div class="grid-3">
        <div>
            <span class="info-label">Elemento Estructural</span>
            <span class="info-value"><?= htmlspecialchars($lote['nombre_lote'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div>
            <span class="info-label">Fecha de Moldeo / Casing</span>
            <span class="info-value"><?= htmlspecialchars($lote['fecha_moldeo'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div>
            <span class="info-label">Diseño de Resistencia Objetivo</span>
            <span class="info-value" style="font-family:monospace; font-weight: 700;"><?= htmlspecialchars($lote['diseno_resistencia'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
    <div class="grid-3" style="margin-top: 15px;">
        <div>
            <span class="info-label">Revenimiento (Slump)</span>
            <span class="info-value"><?= htmlspecialchars($lote['revenimiento_in'] ? $lote['revenimiento_in'].' in' : '—', ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($lote['revenimiento_cm'] ? $lote['revenimiento_cm'].' cm' : '—', ENT_QUOTES, 'UTF-8') ?>)</span>
        </div>
        <div>
            <span class="info-label">Temperatura Concreto</span>
            <span class="info-value"><?= htmlspecialchars($lote['temperatura_c'] ? $lote['temperatura_c'].' °C' : '—', ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <div>
            <span class="info-label">Método / Procedimiento</span>
            <span class="info-value"><?= htmlspecialchars($lote['procedimiento_muestreo'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    </div>
</div>

<!-- NUEVA SECCIÓN: CAPTURA MATRICIAL DINÁMICA DE ENSAYOS DE LA O/S -->
<div class="lote-box">
    <h4 style="font-family:'Outfit'; font-size:15px; font-weight:700; color:var(--cycsa-azul); border-bottom: 1px solid var(--color-slate-200); padding-bottom: 8px; margin-top: 0; margin-bottom: 15px;"><i class="fa-solid fa-table-cells"></i> Ensayos Solicitados y Matrices de Resultados</h4>
    <div style="overflow-x: auto; border: 1px solid var(--color-slate-200); border-radius: 10px;">
        <table class="tabla-rupturas">
            <thead>
                <tr>
                    <th>Servicio / Ensayo</th>
                    <th>Norma / ASTM</th>
                    <th>Formato Documento</th>
                    <th>Cantidad</th>
                    <th style="text-align: right;">Captura LIMS</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($itemsOS as $it): ?>
                <tr>
                    <td style="font-weight: 600; color: var(--color-slate-900);"><?= htmlspecialchars($it['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="color: var(--color-slate-600); font-family: monospace; font-size:12.5px;"><?= htmlspecialchars($it['norma_astm'] ?: 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 500;"><?= htmlspecialchars($it['formato_nombre'] ?: 'Sin formato técnico', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 600;"><?= number_format($it['cantidad'], 0) ?></td>
                    <td style="text-align: right; white-space: nowrap;">
                        <?php if (!empty($it['archivo_markdown'])): 
                            $tieneResultados = !empty($it['resultados_json']) && $it['resultados_json'] !== '[]';
                        ?>
                            <?php if ($tieneResultados): ?>
                                <span class="badge-estado" style="background-color: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; margin-right: 8px; font-size: 11px; padding: 4px 8px; font-weight: 600;"><i class="fa-solid fa-circle-check"></i> Con Resultados</span>
                            <?php else: ?>
                                <span class="badge-estado" style="background-color: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; margin-right: 8px; font-size: 11px; padding: 4px 8px; font-weight: 600;"><i class="fa-solid fa-hourglass"></i> Pendiente</span>
                            <?php endif; ?>

                            <button class="btn-accion <?= $tieneResultados ? 'btn-recepcion' : 'btn-os' ?>" onclick="abrirModalResultados(<?= $it['id'] ?>, '<?= htmlspecialchars($it['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?>', '<?= $it['archivo_markdown'] ?>', <?= htmlspecialchars($it['resultados_json'] ?: '[]', ENT_QUOTES, 'UTF-8') ?>)">
                                <i class="fa-solid fa-list-check"></i> <?= $tieneResultados ? 'Editar Matriz' : 'Capturar Matriz' ?>
                            </button>
                            <a href="/Cycsa/publico/cotizaciones/imprimir-reporte-item?id_detalle=<?= $it['id'] ?>" target="_blank" class="btn-accion btn-detalle" style="margin-left: 5px; font-weight: 600;" title="Imprimir reporte de este ensayo">
                                <i class="fa-solid fa-print"></i> PDF
                            </a>
                        <?php else: ?>
                            <span style="color: var(--color-slate-600); font-size:12px; font-style:italic;">Servicio Operativo</span>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 3. ESPECÍMENES E HITOS DE RUPTURA (CONCRETO) -->
<div class="lote-box">
    <h4 style="font-family:'Outfit'; font-size:15px; font-weight:700; color:var(--cycsa-azul); border-bottom: 1px solid var(--color-slate-200); padding-bottom: 8px; margin-top: 0; margin-bottom: 15px;"><i class="fa-solid fa-list-ol"></i> Cronograma de Rupturas por Edades (Compresión)</h4>
    <div style="overflow-x: auto; border: 1px solid var(--color-slate-200); border-radius: 10px;">
        <table class="tabla-rupturas">
            <thead>
                <tr>
                    <th>Cilindro</th>
                    <th>Edad (Días)</th>
                    <th>Fecha Programada</th>
                    <th>Fecha Ensaye</th>
                    <th>Carga (Lbs)</th>
                    <th>Área (in²)</th>
                    <th>Esfuerzo PSI</th>
                    <th>Esfuerzo Kg/cm²</th>
                    <th>% Diseño</th>
                    <th>Estado / Alerta</th>
                    <th style="text-align: right;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($especimenes as $e): 
                    $est = $e['estado'];
                    $esAlerta = ($est == 'Completado' && !$e['cumple_norma']);
                ?>
                <tr style="<?= $esAlerta ? 'background-color: #fdf2f2;' : '' ?>">
                    <td style="font-weight: 700; font-family: monospace; color: var(--cycsa-azul);"><?= htmlspecialchars($e['identificador_especimen'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($e['edad_dias'], ENT_QUOTES, 'UTF-8') ?>d</td>
                    <td><?= htmlspecialchars($e['fecha_programada'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= $e['fecha_ensaye_real'] ? htmlspecialchars($e['fecha_ensaye_real'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                    <td style="font-family: monospace;"><?= $e['carga_lbs'] ? number_format($e['carga_lbs'], 1) : '—' ?></td>
                    <td style="font-family: monospace;"><?= $e['area_in2'] ? number_format($e['area_in2'], 3) : '—' ?></td>
                    <td style="font-weight: 600; font-family: monospace;"><?= $e['resistencia_psi'] ? number_format($e['resistencia_psi'], 0) : '—' ?></td>
                    <td style="font-weight: 600; font-family: monospace;"><?= $e['resistencia_kgcm2'] ? number_format($e['resistencia_kgcm2'], 1) : '—' ?></td>
                    <td style="font-weight: 700; color: <?= $esAlerta ? '#b91c1c' : '#475569' ?>; font-family: monospace;"><?= $e['porcentaje_diseno'] ? number_format($e['porcentaje_diseno'], 1).'%' : '—' ?></td>
                    <td>
                        <?php if ($est == 'Completado'): ?>
                            <?php if ($e['cumple_norma']): ?>
                                <span class="status-alert status-cumple"><i class="fa-solid fa-check-circle"></i> Cumple</span>
                            <?php else: ?>
                                <span class="status-alert status-alerta" title="El resultado no llega al <?= $e['porcentaje_minimo_esperado'] ?>% mínimo esperado para esta edad"><i class="fa-solid fa-triangle-exclamation"></i> Alerta</span>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="status-alert status-pendiente"><i class="fa-solid fa-clock"></i> Pendiente</span>
                        <?php endif; ?>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <?php if ($est == 'Programado' || $est == 'Listo para Ensaye'): ?>
                            <button class="btn-accion btn-recepcion" onclick="abrirRupturaModal(<?= $e['id'] ?>, '<?= $e['identificador_especimen'] ?>', <?= $e['edad_dias'] ?>)">
                                <i class="fa-solid fa-hammer"></i> Romper
                            </button>
                        <?php else: ?>
                            <button class="btn-accion btn-detalle" onclick="abrirRupturaModal(<?= $e['id'] ?>, '<?= $e['identificador_especimen'] ?>', <?= $e['edad_dias'] ?>, '<?= $e['carga_lbs'] ?>', '<?= $e['area_in2'] ?>')" title="Editar resultado">
                                <i class="fa-solid fa-edit"></i> Editar
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- 4. APARTADO DE CONTROL DE REPORTES Y VERSIONAMIENTO -->
<?php if (!$esTecnico): ?>
<div class="lote-box">
    <div style="display:flex; justify-content:space-between; align-items:center; border-bottom: 1px solid var(--color-slate-200); padding-bottom: 8px; margin-bottom: 15px;">
        <h4 style="font-family:'Outfit'; font-size:15px; font-weight:700; color:var(--cycsa-azul); margin: 0;"><i class="fa-solid fa-file-pdf"></i> Historial de Informes y Versionado</h4>
        <button class="btn-accion btn-os" onclick="abrirInformeModal()"><i class="fa-solid fa-file-circle-plus"></i> Generar Versión / Reporte</button>
    </div>
    
    <div style="overflow-x: auto; border: 1px solid var(--color-slate-200); border-radius: 10px;">
        <table class="tabla-rupturas">
            <thead>
                <tr>
                    <th>Código Informe</th>
                    <th>Versión</th>
                    <th>Tipo</th>
                    <th>Fecha Generación</th>
                    <th>Aprobación</th>
                    <th>Motivo de Reemplazo (Versionado)</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($historial as $h): ?>
                <tr>
                    <td style="font-family: monospace; font-weight: 700; color: var(--color-slate-800);"><?= htmlspecialchars($h['codigo_completo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 700; text-align: center;">v<?= htmlspecialchars($h['version'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 500;">
                        <?= htmlspecialchars($h['tipo_informe'], ENT_QUOTES, 'UTF-8') ?>
                        <?= ($h['tipo_informe'] === 'Parcial' && !empty($h['edad_evaluada'])) ? ' (' . $h['edad_evaluada'] . ' días)' : '' ?>
                    </td>
                    <td><?= htmlspecialchars($h['fecha_generacion'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="badge-estado estado-<?= str_replace(' ', '-', $h['estado_aprobacion']) ?>"><?= htmlspecialchars($h['estado_aprobacion'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td style="font-size:12.5px; color:var(--color-slate-600); font-style:italic; max-width: 250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;"><?= $h['motivo_reemplazo'] ? htmlspecialchars($h['motivo_reemplazo'], ENT_QUOTES, 'UTF-8') : '—' ?></td>
                    <td style="text-align: right; white-space: nowrap;">
                        <div style="display: flex; gap: 6px; justify-content: flex-end; align-items: center;">
                            <a href="/Cycsa/publico/informes/descargar?id=<?= $h['id'] ?>" class="btn-accion btn-detalle" target="_blank" style="padding: 6px 12px;"><i class="fa-solid fa-download"></i> Descargar PDF</a>
                            
                            <?php if ($h['estado_aprobacion'] === 'Pendiente'): ?>
                                <form method="POST" action="/Cycsa/publico/operaciones/cambiar-estado-informe" style="display:inline; margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="id_informe" value="<?= $h['id'] ?>">
                                    <input type="hidden" name="id_lote" value="<?= $lote['id'] ?>">
                                    <input type="hidden" name="nuevo_estado" value="Revisado">
                                    <button type="submit" class="btn-accion btn-os" style="padding: 6px 12px;" title="Marcar como Revisado"><i class="fa-solid fa-check"></i> Revisar</button>
                                </form>
                                <form method="POST" action="/Cycsa/publico/operaciones/cambiar-estado-informe" style="display:inline; margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="id_informe" value="<?= $h['id'] ?>">
                                    <input type="hidden" name="id_lote" value="<?= $lote['id'] ?>">
                                    <input type="hidden" name="nuevo_estado" value="Rechazado">
                                    <button type="submit" class="btn-accion btn-detalle" style="padding: 6px 12px; background-color: #fdf2f2; color: #b91c1c; border-color: #fde2e2;" title="Rechazar"><i class="fa-solid fa-xmark"></i> Rechazar</button>
                                </form>
                            <?php elseif ($h['estado_aprobacion'] === 'Revisado'): ?>
                                <form method="POST" action="/Cycsa/publico/operaciones/cambiar-estado-informe" style="display:inline; margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="id_informe" value="<?= $h['id'] ?>">
                                    <input type="hidden" name="id_lote" value="<?= $lote['id'] ?>">
                                    <input type="hidden" name="nuevo_estado" value="Aprobado">
                                    <button type="submit" class="btn-accion btn-recepcion" style="padding: 6px 12px;" title="Aprobar e Imprimir"><i class="fa-solid fa-thumbs-up"></i> Aprobar</button>
                                </form>
                                <form method="POST" action="/Cycsa/publico/operaciones/cambiar-estado-informe" style="display:inline; margin: 0;">
                                    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
                                    <input type="hidden" name="id_informe" value="<?= $h['id'] ?>">
                                    <input type="hidden" name="id_lote" value="<?= $lote['id'] ?>">
                                    <input type="hidden" name="nuevo_estado" value="Rechazado">
                                    <button type="submit" class="btn-accion btn-detalle" style="padding: 6px 12px; background-color: #fdf2f2; color: #b91c1c; border-color: #fde2e2;" title="Rechazar"><i class="fa-solid fa-xmark"></i> Rechazar</button>
                                </form>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($historial)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 30px; color: var(--color-slate-600);">No se han generado reportes PDF de ensayos para este lote.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<!-- MODAL REGISTRAR RUPTURA (TÉCNICO) -->
<div id="modalRuptura" class="modal-premium">
    <div class="modal-premium-content" style="width: 35%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: var(--color-slate-900); font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700;">Ingreso de Cargas: Cilindro <span id="lbl_especimen" style="color:var(--cycsa-azul);"></span></h3>
            <button onclick="cerrarRupturaModal()" class="btn-cerrar">&times;</button>
        </div>
        
        <form id="formRuptura" method="POST" action="/Cycsa/publico/operaciones/guardar-ruptura" onsubmit="enviarRupturaAsync(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_ensayo" id="rup_id_ensayo">
            <input type="hidden" name="id_lote" value="<?= $lote['id'] ?>">

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: var(--color-slate-700);">Carga Última Aplicada (Lbs)</label>
                <input type="number" step="0.01" name="carga_lbs" id="rup_carga" required placeholder="Ej: 85200.00" class="form-control">
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: var(--color-slate-700);">Área de Sección Transversal (in²)</label>
                <input type="number" step="0.001" name="area_in2" id="rup_area" required placeholder="Ej: 28.274" class="form-control" value="28.274">
                <span style="font-size: 11.5px; color: var(--color-slate-600); margin-top: 2px;"><i class="fa-solid fa-circle-info"></i> <strong>Tip de Área:</strong> Estándar 6"x12" = <strong>28.274</strong> | Estándar 4"x8" = <strong>12.566</strong></span>
            </div>

            <div id="ruptura-status-message" style="margin-top: 10px; font-size: 13px; display: none;"></div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 25px;">
                <button type="button" onclick="cerrarRupturaModal()" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid var(--color-slate-300); font-weight: 600; color: var(--color-slate-600); width: auto; padding: 10px 20px;">Cancelar</button>
                <button type="submit" id="btnGuardarRuptura" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600; width: auto; padding: 10px 24px;">Guardar Datos</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL CAPTURA MATRICIAL DINÁMICA DE ENSAYOS -->
<div id="modalResultadosEnsayo" class="modal-premium">
    <div class="modal-premium-content" style="width: 75%; max-height: 90vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 id="modal-titulo-ensayo" style="margin: 0; color: var(--color-slate-900); font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700;">Capturar Resultados del Ensayo</h3>
            <button onclick="cerrarModalResultados()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/cotizaciones/guardar-resultados-item" onsubmit="prepararJsonAntesDeEnviar(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_detalle" id="modal-id-detalle">
            <input type="hidden" name="id_cotizacion" value="<?= $lote['id_cotizacion'] ?>">
            <input type="hidden" name="redireccionar_a" value="/Cycsa/publico/operaciones/detalle-lote?id_lote=<?= $lote['id'] ?>">
            <input type="hidden" name="resultados_json" id="modal-resultados-json" value="">
            
            <p style="color: var(--color-slate-600); font-size: 13.5px; margin-bottom: 15px;">Ingrese los valores correspondientes en la matriz del ensayo de laboratorio. Deje celdas vacías si no requiere usarlas.</p>
            
            <div style="overflow-x: auto; width: 100%; border: 1px solid var(--color-slate-200); border-radius: 10px; margin-bottom: 20px;">
                <table class="tabla-rupturas" style="margin-top: 0; margin-bottom: 0;" id="tabla-captura-resultados">
                    <thead id="tabla-captura-header">
                        <!-- Columnas dinámicas -->
                    </thead>
                    <tbody id="tabla-captura-body">
                        <!-- Filas de inputs -->
                    </tbody>
                </table>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px;">
                <button type="button" onclick="cerrarModalResultados()" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid var(--color-slate-300); font-weight: 600; color: var(--color-slate-600); width: auto; padding: 10px 20px;">Cancelar</button>
                <button type="submit" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600; width: auto; padding: 10px 24px;">Guardar Resultados</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL GENERAR INFORME / REPORTES -->
<div id="modalInforme" class="modal-premium">
    <div class="modal-premium-content" style="width: 40%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px;">
            <h3 style="margin: 0; color: var(--color-slate-900); font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700;">Generar Versión de Reporte</h3>
            <button onclick="cerrarInformeModal()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/operaciones/generar-informe" onsubmit="return validarFormularioInforme(event)">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_lote" value="<?= $lote['id'] ?>">

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: var(--color-slate-700);">Seleccionar Ensayo para el Reporte</label>
                <select name="id_detalle" id="inf_id_detalle" required class="form-control" style="background-color: white;">
                    <option value="">-- Seleccionar ensayo --</option>
                    <?php foreach ($itemsOS as $it): ?>
                        <option value="<?= $it['id'] ?>" data-json='<?= htmlspecialchars($it['resultados_json'] ?: '[]', ENT_QUOTES, 'UTF-8') ?>'><?= htmlspecialchars($it['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: var(--color-slate-700);">Tipo de Informe</label>
                <select name="tipo_informe" id="inf_tipo_informe" required class="form-control" style="background-color: white;">
                    <option value="Parcial">Informe Parcial (Resultados por edad actual)</option>
                    <option value="Consolidado">Informe Consolidado Final (Lote completo)</option>
                </select>
            </div>

            <div class="form-group" id="grupo-edad-filtro" style="display: none;">
                <label style="font-weight: 600; font-size: 13px; color: var(--color-slate-700);">Edad a Reportar (Días)</label>
                <select name="edad_filtro" id="inf_edad_filtro" class="form-control" style="background-color: white;">
                    <option value="">-- Seleccionar edad --</option>
                    <?php 
                    $edadesUnicas = [];
                    foreach ($especimenes as $e) {
                        if ($e['edad_dias'] > 0 && !in_array($e['edad_dias'], $edadesUnicas)) {
                            $edadesUnicas[] = $e['edad_dias'];
                        }
                    }
                    sort($edadesUnicas);
                    foreach ($edadesUnicas as $edad):
                    ?>
                        <option value="<?= $edad ?>"><?= $edad ?> días</option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group" id="grupo-motivo" style="display: none;">
                <label style="font-weight: 600; font-size: 13px; color: var(--color-slate-700);">Motivo de Reemplazo (Nueva Versión)</label>
                <textarea name="motivo_reemplazo" id="inf_motivo" rows="2" placeholder="Describa el motivo del por qué genera una nueva versión para el cliente..." class="form-control"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 20px;">
                <button type="button" onclick="cerrarInformeModal()" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid var(--color-slate-300); font-weight: 600; color: var(--color-slate-600); width: auto; padding: 10px 20px;">Cancelar</button>
                <button type="submit" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600; width: auto; padding: 10px 24px;">Generar PDF & Versionar</button>
            </div>
        </form>
    </div>
</div>

<script>
    const rupModal = document.getElementById('modalRuptura');
    const modalResultados = document.getElementById('modalResultadosEnsayo');
    const FORMATOS_SCHEMA = <?= $formatosSchemaJson ?>;
    let columnasActuales = [];

    function abrirRupturaModal(idEnsayo, espName, edad, carga = '', area = '28.274') {
        document.getElementById('rup_id_ensayo').value = idEnsayo;
        document.getElementById('lbl_especimen').innerText = espName + ' (' + edad + ' días)';
        document.getElementById('rup_carga').value = carga;
        document.getElementById('rup_area').value = area;
        
        // Reset status message
        const msgDiv = document.getElementById('ruptura-status-message');
        msgDiv.style.display = 'none';
        msgDiv.className = '';
        msgDiv.innerText = '';
        
        rupModal.style.display = 'block';
    }

    function cerrarRupturaModal() {
        rupModal.style.display = 'none';
    }

    // AJAX Save for Rupture Specimen
    function enviarRupturaAsync(event) {
        event.preventDefault();
        
        const form = document.getElementById('formRuptura');
        const submitBtn = document.getElementById('btnGuardarRuptura');
        const msgDiv = document.getElementById('ruptura-status-message');
        
        submitBtn.disabled = true;
        submitBtn.innerText = 'Guardando...';
        
        msgDiv.style.display = 'block';
        msgDiv.className = 'status-alert status-pendiente';
        msgDiv.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Procesando resultados y validando con la norma estándar...';
        
        const formData = new FormData(form);
        
        fetch(form.action, {
            method: 'POST',
            body: formData,
            headers: {
                'X-Requested-With': 'XMLHttpRequest'
            }
        })
        .then(response => response.json())
        .then(data => {
            if (data.status === 'success') {
                msgDiv.className = 'status-alert status-cumple';
                msgDiv.innerHTML = '<i class="fa-solid fa-circle-check"></i> ' + data.message + ' Recargando valores...';
                setTimeout(() => {
                    window.location.reload();
                }, 800);
            } else if (data.status === 'warning') {
                // Regresión detectada — se guardó pero necesita revisión
                msgDiv.className = 'status-alert status-alerta';
                msgDiv.style.display = 'block';
                msgDiv.style.flexDirection = 'column';
                msgDiv.style.gap = '6px';
                msgDiv.innerHTML = '<div style="display:flex;align-items:center;gap:6px;"><i class="fa-solid fa-triangle-exclamation"></i> <strong>ALERTA DE REGRESIÓN DE RESISTENCIA</strong></div>' +
                    '<div style="font-size:12px; margin-top:4px;">' + data.message + '</div>' +
                    '<div style="font-size:11px; margin-top:6px; color:#92400e; font-weight:600;"><i class="fa-solid fa-eye"></i> Dato guardado. Requiere revisión del supervisor. Recargando...</div>';
                setTimeout(() => {
                    window.location.reload();
                }, 4000);
            } else {
                submitBtn.disabled = false;
                submitBtn.innerText = 'Guardar Datos';
                msgDiv.className = 'status-alert status-alerta';
                msgDiv.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> ' + data.message;
            }
        })
        .catch(err => {
            submitBtn.disabled = false;
            submitBtn.innerText = 'Guardar Datos';
            msgDiv.className = 'status-alert status-alerta';
            msgDiv.innerHTML = '<i class="fa-solid fa-circle-xmark"></i> Ocurrió un error en la conexión.';
            console.error(err);
        });
    }

    function abrirModalResultados(idDetalle, nombreEnsayo, archivoMarkdown, resultadosJson) {
        document.getElementById('modal-id-detalle').value = idDetalle;
        document.getElementById('modal-titulo-ensayo').innerText = 'Capturar Resultados: ' + nombreEnsayo;
        
        const schema = FORMATOS_SCHEMA[archivoMarkdown] || { columns: [] };
        columnasActuales = schema.columns;
        
        if (columnasActuales.length === 0) {
            columnasActuales = ["Código laboratorio", "Nombre muestra", "Resultado"];
        }

        // Render header
        const headerRow = document.createElement('tr');
        columnasActuales.forEach(col => {
            const th = document.createElement('th');
            th.innerText = col;
            th.style.padding = '12px 10px';
            th.style.fontSize = '11.5px';
            th.style.backgroundColor = 'var(--color-slate-50)';
            th.style.color = 'var(--color-slate-600)';
            th.style.borderBottom = '2px solid var(--color-slate-200)';
            headerRow.appendChild(th);
        });
        const headerContainer = document.getElementById('tabla-captura-header');
        headerContainer.innerHTML = '';
        headerContainer.appendChild(headerRow);

        // Parse existing rows or generate 5 empty rows
        let filasExistentes = [];
        try {
            if (typeof resultadosJson === 'string') {
                filasExistentes = JSON.parse(resultadosJson);
            } else if (Array.isArray(resultadosJson)) {
                filasExistentes = resultadosJson;
            }
        } catch (e) {
            filasExistentes = [];
        }

        if (!Array.isArray(filasExistentes)) filasExistentes = [];

        // Build 5 rows of inputs
        const bodyContainer = document.getElementById('tabla-captura-body');
        bodyContainer.innerHTML = '';
        
        for (let r = 0; r < 5; r++) {
            const rowData = filasExistentes[r] || {};
            const tr = document.createElement('tr');
            
            columnasActuales.forEach(col => {
                const td = document.createElement('td');
                td.style.padding = '8px';
                
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'form-control';
                input.style.width = '100%';
                input.style.padding = '8px 12px';
                input.style.fontSize = '13.5px';
                input.style.boxSizing = 'border-box';
                input.style.borderRadius = '6px';
                input.style.border = '1px solid var(--color-slate-300)';
                input.value = rowData[col] || '';
                input.dataset.col = col;
                
                td.appendChild(input);
                tr.appendChild(td);
            });
            bodyContainer.appendChild(tr);
        }

        modalResultados.style.display = 'block';
    }

    function cerrarModalResultados() {
        modalResultados.style.display = 'none';
    }

    function prepararJsonAntesDeEnviar(event) {
        const filas = [];
        const rows = document.querySelectorAll('#tabla-captura-body tr');
        
        rows.forEach(tr => {
            const rowData = {};
            let tieneDatos = false;
            const inputs = tr.querySelectorAll('input');
            
            inputs.forEach(input => {
                const col = input.dataset.col;
                const val = input.value.trim();
                rowData[col] = val;
                if (val !== '') {
                    tieneDatos = true;
                }
            });
            
            if (tieneDatos) {
                filas.push(rowData);
            }
        });
        
        document.getElementById('modal-resultados-json').value = JSON.stringify(filas);
    }

    window.addEventListener('click', (e) => {
        if (e.target === rupModal) cerrarRupturaModal();
        if (e.target === modalResultados) cerrarModalResultados();
        if (e.target === document.getElementById('modalInforme')) cerrarInformeModal();
    });
</script>

<script>
    const infModal = document.getElementById('modalInforme');
    
    function abrirInformeModal() {
        infModal.style.display = 'block';
        checkVersionReason();
    }
    
    function cerrarInformeModal() {
        infModal.style.display = 'none';
    }
    
    const historialInformes = <?= json_encode($historial) ?>;

    document.getElementById('inf_id_detalle')?.addEventListener('change', checkVersionReason);
    document.getElementById('inf_tipo_informe')?.addEventListener('change', checkVersionReason);
    document.getElementById('inf_edad_filtro')?.addEventListener('change', checkVersionReason);
    
    function checkVersionReason() {
        const idDetalle = document.getElementById('inf_id_detalle').value;
        const tipo = document.getElementById('inf_tipo_informe').value;
        const grupoMotivo = document.getElementById('grupo-motivo');
        const grupoEdad = document.getElementById('grupo-edad-filtro');
        
        // Mostrar filtro de edad si el informe es Parcial
        if (tipo === 'Parcial') {
            grupoEdad.style.display = 'block';
            document.getElementById('inf_edad_filtro').required = true;
        } else {
            grupoEdad.style.display = 'none';
            document.getElementById('inf_edad_filtro').required = false;
        }
        
        const edadFiltro = document.getElementById('inf_edad_filtro').value;
        
        let tienePrevio = false;
        for (let i = 0; i < historialInformes.length; i++) {
            const h = historialInformes[i];
            if (h.tipo_informe === tipo) {
                if (tipo === 'Parcial') {
                    if (h.edad_evaluada == edadFiltro && edadFiltro !== '') {
                        tienePrevio = true;
                        break;
                    }
                } else {
                    if (h.edad_evaluada === null || h.edad_evaluada === '') {
                        tienePrevio = true;
                        break;
                    }
                }
            }
        }
        
        if (tienePrevio) {
            grupoMotivo.style.display = 'block';
            document.getElementById('inf_motivo').required = true;
        } else {
            grupoMotivo.style.display = 'none';
            document.getElementById('inf_motivo').required = false;
        }
    }
    
    function validarFormularioInforme(event) {
        const select = document.getElementById('inf_id_detalle');
        const opt = select.options[select.selectedIndex];
        
        const textoEnsayo = opt.text.toLowerCase();
        // Evitar validación de matriz JSON para ensayos de resistencia y rupturas de concreto, mortero, núcleos, flexión, bloques, adoquines, etc.
        const esRotura = textoEnsayo.includes('compresión') || textoEnsayo.includes('flexión') || 
                          textoEnsayo.includes('mortero') || textoEnsayo.includes('núcleo') || 
                          textoEnsayo.includes('concreto') || textoEnsayo.includes('adoquín') || 
                          textoEnsayo.includes('bloque') || textoEnsayo.includes('ladrillo');
        
        if (esRotura) {
            // Para roturas de concreto, permitimos continuar sin validar matriz
            return true;
        }

        const jsonStr = opt.getAttribute('data-json') || '[]';
        let filas = [];
        try {
            filas = JSON.parse(jsonStr);
        } catch(e) {
            filas = [];
        }
        
        if (filas.length === 0) {
            alert('Atención: No puede generar un informe para un ensayo que no tiene resultados cargados. Capture la matriz de resultados primero.');
            event.preventDefault();
            return false;
        }
        return true;
    }
</script>
