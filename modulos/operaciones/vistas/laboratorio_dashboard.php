<?php
// Laboratorio Dashboard - Portal de Operación Ciega ISO 17025
?>
<style>
    .lab-alert { background-color: #eff6ff; border: 1px solid #bfdbfe; color: #1e40af; padding: 15px 20px; border-radius: 8px; margin-bottom: 25px; display: flex; align-items: center; gap: 12px; font-size: 14px; font-weight: 500; }
    .lab-box { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 25px; }
    .section-title { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: var(--cycsa-azul); margin: 0; padding-bottom: 8px; }
    .status-badge { display: inline-block; padding: 4px 8px; border-radius: 12px; font-weight: 600; font-size: 11px; text-transform: uppercase; }
    .badge-today { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
    .badge-upcoming { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .tabla-cycsa th { background-color: #f8fafc; color: #475569; padding: 12px 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #e2e8f0; text-transform: uppercase; font-size: 11px; letter-spacing: 0.5px; }
    .tabla-cycsa td { padding: 12px 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; color: #334155; }
    .tabla-cycsa tbody tr:hover { background-color: #f8fafc; }

    .btn-accion { border: none; background: none; cursor: pointer; padding: 8px 14px; border-radius: 6px; font-size: 13px; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-weight: 600; }
    .btn-os { background-color: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; }
    .btn-os:hover { background-color: #dbeafe; }
    .btn-recepcion { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .btn-recepcion:hover { background-color: #dcfce7; }
    .btn-detalle { background-color: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
    .btn-detalle:hover { background-color: #e2e8f0; }
</style>

<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;">Portal de Laboratorio (LIMS)</h2>
        <p style="color: #64748b; margin-top: 5px; font-size: 14px;">Operación Ciega y Resguardo de Imparcialidad</p>
    </div>
</div>

<div class="lab-alert">
    <i class="fa-solid fa-user-shield" style="font-size: 20px;"></i>
    <div>
        <strong>POLÍTICA DE IMPARCIALIDAD ISO 17025:</strong> Este portal está diseñado para el uso exclusivo del personal de laboratorio. Toda la información de clientes, obras, precios y ofertas comerciales ha sido omitida para garantizar la confidencialidad absoluta de los ensayes.
    </div>
</div>

<!-- 1. CALENDARIO DE RUPTURAS PROGRAMADAS -->
<div class="lab-box">
    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px; display: flex; justify-content: space-between; align-items: center;">
        <h4 class="section-title"><i class="fa-solid fa-calendar-check" style="margin-right: 6px; color:#ef4444;"></i> Ensayos de Ruptura Programados (Compresión)</h4>
        <span style="font-size:12px; font-weight:600; color:#64748b;">Próximos 7 días</span>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th>Fecha Programada</th>
                    <th>Código Lab</th>
                    <th>Código Campo</th>
                    <th>Cilindro / Identificador</th>
                    <th>Edad (Días)</th>
                    <th>Estado Tarea</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($rupturas as $r): 
                    $esHoy = (date('Y-m-d') === $r['fecha_programada']);
                ?>
                <tr style="<?= $esHoy ? 'background-color: #fef2f2;' : '' ?>">
                    <td style="font-weight: 700; color: <?= $esHoy ? '#b91c1c' : '#334155' ?>;">
                        <?= htmlspecialchars(date('d/m/Y', strtotime($r['fecha_programada'])), ENT_QUOTES, 'UTF-8') ?>
                        <?php if ($esHoy): ?>
                            <span class="status-badge badge-today" style="margin-left: 6px;">Hoy</span>
                        <?php endif; ?>
                    </td>
                    <td style="font-family: monospace; font-weight: 700; color: #0369a1;"><?= htmlspecialchars($r['codigo_muestra'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-family: monospace; color: #475569;"><?= htmlspecialchars($r['codigo_campo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($r['identificador_especimen'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><strong style="color: #7c3aed;"><?= htmlspecialchars($r['edad_dias'], ENT_QUOTES, 'UTF-8') ?> días</strong></td>
                    <td>
                        <span class="status-badge <?= $esHoy ? 'badge-today' : 'badge-upcoming' ?>">Listo para ruptura</span>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <a href="/Cycsa/publico/laboratorio/detalle-muestra?id_lote=<?= $r['id_lote'] ?>" class="btn-accion btn-os">
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

<!-- 2. HISTORIAL / LOTES REGISTRADOS EN LABORATORIO -->
<div class="lab-box">
    <div style="border-bottom: 1px solid #e2e8f0; padding-bottom: 10px; margin-bottom: 15px;">
        <h4 class="section-title"><i class="fa-solid fa-flask-vial" style="margin-right: 6px; color:var(--cycsa-azul);"></i> Lotes y Muestras Activas en Custodia</h4>
    </div>
    
    <div style="overflow-x: auto;">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th>Código Lab (Muestra)</th>
                    <th>Código Campo</th>
                    <th>Descripción / Identificación Lote</th>
                    <th>Fecha de Moldeo</th>
                    <th>Fecha de Recepción</th>
                    <th>Estado LIMS</th>
                    <th style="text-align: right;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($muestras as $m): ?>
                <tr>
                    <td style="font-family: monospace; font-size:14px; font-weight: 700; color: #0369a1;"><?= htmlspecialchars($m['codigo_muestra'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-family: monospace; color: #475569;"><?= htmlspecialchars($m['codigo_campo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 600;"><?= htmlspecialchars($m['nombre_lote'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars($m['fecha_moldeo'] ? date('d/m/Y', strtotime($m['fecha_moldeo'])) : '—', ENT_QUOTES, 'UTF-8') ?></td>
                    <td><?= htmlspecialchars(date('d/m/Y h:i A', strtotime($m['fecha_recepcion'])), ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="badge-estado estado-<?= str_replace(' ', '-', $m['estado']) ?>"><?= htmlspecialchars($m['estado'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
                        <a href="/Cycsa/publico/laboratorio/detalle-muestra?id_lote=<?= $m['id_lote'] ?>" class="btn-accion btn-detalle" title="Cargar resultados en matriz ciega">
                            <i class="fa-solid fa-list-check"></i> Hoja de Trabajo
                        </a>
                    </td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($muestras)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">No hay muestras registradas en el laboratorio.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
