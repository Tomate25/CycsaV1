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

    .search-input-wrapper { position: relative; display: flex; align-items: center; }
    .search-icon { position: absolute; left: 14px; color: #64748b; font-size: 14px; pointer-events: none; }

    /* Estilos del Calendario Premium */
    .calendario-container { background: #ffffff; border: 1px solid #cbd5e1; border-radius: 12px; padding: 0; margin-bottom: 25px; box-shadow: 0 4px 6px -1px rgb(0 0 0 / 0.05), 0 2px 4px -2px rgb(0 0 0 / 0.05); overflow: hidden; }
    .cal-toggle-header { display: flex; justify-content: space-between; align-items: center; cursor: pointer; user-select: none; padding: 18px 24px; transition: background-color 0.2s ease; }
    .cal-toggle-header:hover { background-color: #f8fafc; }
    .calendario-colapso-wrapper { max-height: 0; opacity: 0; overflow: hidden; transition: max-height 0.4s ease-out, opacity 0.3s ease-out; }
    .calendario-colapso-wrapper.abierto { max-height: 1200px; opacity: 1; }
    .calendario-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; }
    .calendario-titulo { font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 8px; }
    .calendario-nav { display: flex; gap: 8px; }
    .btn-nav-cal { background: #f1f5f9; border: 1px solid #cbd5e1; color: #334155; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px; transition: all 0.2s; }
    .btn-nav-cal:hover { background: #e2e8f0; color: #0f172a; }
    .calendario-grid { display: grid; grid-template-columns: repeat(7, 1fr); gap: 1px; background: #cbd5e1; border: 1px solid #cbd5e1; border-radius: 8px; overflow: hidden; }
    .dia-semana { background: #f8fafc; color: #64748b; font-weight: 600; text-align: center; padding: 8px; font-size: 11px; text-transform: uppercase; border-bottom: 1px solid #e2e8f0; }
    .dia-celda { background: #ffffff; min-height: 95px; padding: 6px; display: flex; flex-direction: column; position: relative; transition: all 0.2s ease; box-sizing: border-box; }
    .dia-celda:hover { background: #f8fafc; }
    .dia-celda.fuera-mes { background: #f8fafc; color: #cbd5e1; }
    .dia-celda.fuera-mes .dia-numero { color: #cbd5e1; }
    .dia-celda.hoy { background: #fef2f2; }
    .dia-numero { font-size: 12px; font-weight: 700; color: #475569; margin-bottom: 6px; align-self: flex-end; }
    .dia-celda.hoy .dia-numero { background: #ef4444; color: #ffffff; width: 22px; height: 22px; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 11px; }
    
    .dia-celda.tiene-eventos { background-color: #f0fdfa; } /* Light teal tint */
    .dia-celda.tiene-eventos:hover { background-color: #ccfbf1; border-color: #0d9488 !important; transform: scale(1.02); box-shadow: 0 10px 15px -3px rgba(13, 148, 136, 0.15); z-index: 10; }
    
    .eventos-lista { display: flex; flex-direction: column; gap: 3px; overflow-y: auto; max-height: 70px; width: 100%; box-sizing: border-box; }
    .evento-badge { font-size: 9.5px; font-weight: 700; padding: 2px 6px; border-radius: 4px; color: #ffffff !important; text-decoration: none; display: block; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; box-sizing: border-box; line-height: 1.3; }
    .evento-badge:hover { transform: scale(1.02); filter: brightness(0.95); }
    .evento-pendiente { background: #0284c7; } /* Sky 600 */
    .evento-hoy { background: #f97316; } /* Orange 500 */
    .evento-completado { background: #22c55e; } /* Green 500 */
    
    @keyframes modalFadeIn {
        from { opacity: 0; transform: scale(0.95); }
        to { opacity: 1; transform: scale(1); }
    }
</style>

<div class="header-flex" style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div>
        <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;">Portal de Laboratorio (LIMS)</h2>
        <p style="color: #64748b; margin-top: 5px; font-size: 14px;">Operación Ciega y Resguardo de Imparcialidad</p>
    </div>
    <div class="actions-flex">
        <div class="search-input-wrapper">
            <i class="fa-solid fa-magnifying-glass search-icon"></i>
            <input type="text" id="lab-search" placeholder="Filtrar en tiempo real..." class="form-control search-input" style="padding: 10px 16px 10px 38px !important; width: 300px; border-radius: 8px !important; border: 1px solid #cbd5e1; outline: none; font-size: 13.5px; font-family: 'Inter', sans-serif;">
        </div>
    </div>
</div>

<div class="lab-alert">
    <i class="fa-solid fa-user-shield" style="font-size: 20px;"></i>
    <div>
        <strong>POLÍTICA DE IMPARCIALIDAD ISO 17025:</strong> Este portal está diseñado para el uso exclusivo del personal de laboratorio. Toda la información de clientes, obras, precios y ofertas comerciales ha sido omitida para garantizar la confidencialidad absoluta de los ensayes.
    </div>
</div>

<!-- CALENDARIO INTERACTIVO DE CONTROL DE RUPTURAS (LIMS) - COLAPSIBLE -->
<div class="calendario-container">
    <div onclick="toggleCalendario()" class="cal-toggle-header">
        <h4 style="font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: var(--cycsa-azul); margin: 0; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-calendar-days" style="font-size: 18px; color: var(--cycsa-azul);"></i> 
            <span>Calendario Mensual de Rupturas LIMS</span>
            <span id="cal-contador-hoy" class="status-badge badge-today" style="margin-left: 8px; text-transform: none; display: none; font-size: 11px; padding: 2px 8px;"></span>
        </h4>
        <div style="display: flex; align-items: center; gap: 8px; color: #64748b; font-size: 13.5px; font-weight: 600;">
            <span id="cal-toggle-text">Mostrar Calendario</span>
            <i id="cal-toggle-icon" class="fa-solid fa-chevron-down" style="transition: transform 0.3s; font-size: 12px;"></i>
        </div>
    </div>
    
    <div id="calendario-colapso" class="calendario-colapso-wrapper">
        <div style="padding: 0 24px 24px 24px; border-top: 1px solid #cbd5e1; padding-top: 15px; margin-top: 2px;">
            <div class="calendario-header">
                <span id="cal-mes-año" style="font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; color: #1e293b;"></span>
                <div class="calendario-nav">
                    <button type="button" class="btn-nav-cal" onclick="navigateMonth(-1)"><i class="fa-solid fa-chevron-left"></i> Anter.</button>
                    <button type="button" class="btn-nav-cal" onclick="navigateMonth(1)">Sigu. <i class="fa-solid fa-chevron-right"></i></button>
                </div>
            </div>
            
            <div class="calendario-grid">
                <div class="dia-semana">Lun</div>
                <div class="dia-semana">Mar</div>
                <div class="dia-semana">Mié</div>
                <div class="dia-semana">Jue</div>
                <div class="dia-semana">Vie</div>
                <div class="dia-semana">Sáb</div>
                <div class="dia-semana">Dom</div>
            </div>
            <div class="calendario-grid" id="calendario-dias" style="border-top: none; border-radius: 0 0 8px 8px;">
                <!-- Se genera dinámicamente con JS -->
            </div>
            
            <div style="display: flex; justify-content: flex-start; gap: 20px; margin-top: 15px; font-size: 12px; font-weight: 600; color: #475569;">
                <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 12px; height: 12px; border-radius: 3px; display: inline-block; background: #0284c7;"></span> Programada</div>
                <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 12px; height: 12px; border-radius: 3px; display: inline-block; background: #f97316;"></span> Romper Hoy</div>
                <div style="display: flex; align-items: center; gap: 6px;"><span style="width: 12px; height: 12px; border-radius: 3px; display: inline-block; background: #22c55e;"></span> Ensaye Completado</div>
            </div>
        </div>
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
                    <th>Ensayo / Producto</th>
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
                    <td style="font-weight: 600; color: #475569;"><?= htmlspecialchars($r['nombre_ensayo'] ?: 'Ensayo General', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 700; font-family: monospace; color: var(--cycsa-azul);"><?= htmlspecialchars($r['identificador_especimen'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td><strong style="color: #7c3aed;"><?= htmlspecialchars($r['edad_dias'], ENT_QUOTES, 'UTF-8') ?> días</strong></td>
                    <td>
                        <span class="status-badge <?= $esHoy ? 'badge-today' : 'badge-upcoming' ?>">Listo para ruptura</span>
                    </td>
                    <td style="text-align: right; white-space: nowrap;">
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
                        <a href="/Cycsa/publico/laboratorio/detalle-muestra?id_lote=<?= codificarId($m['id_lote']) ?>" class="btn-accion btn-detalle" title="Cargar resultados en matriz ciega">
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

<!-- MODAL DETALLE DE RUPTURAS POR DÍA (LIMS) -->
<div id="modalDetalleDia" class="modal-premium" style="display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); align-items: center; justify-content: center;">
    <div class="modal-premium-content" style="background: white; border-radius: 12px; width: 50%; max-width: 650px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); overflow: hidden; animation: modalFadeIn 0.25s ease-out; border: 1px solid #e2e8f0;">
        <div style="display: flex; justify-content: space-between; align-items: center; padding: 18px 24px; border-bottom: 1px solid #e2e8f0; background: #f8fafc;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 16px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-circle-info" style="color: var(--cycsa-azul);"></i>
                <span>Detalle de Rupturas: <span id="det-fecha-titulo" style="color: #64748b;"></span></span>
            </h3>
            <button onclick="cerrarDetalleDiaModal()" style="border: none; background: none; font-size: 24px; cursor: pointer; color: #94a3b8; font-weight: 300;">&times;</button>
        </div>
        
        <div id="det-modal-body" style="padding: 24px; max-height: 70vh; overflow-y: auto;">
            <!-- Contenido dinámico generado por JS -->
        </div>
        
        <div style="display: flex; justify-content: flex-end; padding: 15px 24px; border-top: 1px solid #e2e8f0; background: #f8fafc;">
            <button type="button" onclick="cerrarDetalleDiaModal()" class="btn-accion btn-detalle" style="padding: 8px 20px; font-weight: 600;">Cerrar</button>
        </div>
    </div>
</div>

<script>
document.addEventListener('DOMContentLoaded', () => {
    const searchInput = document.getElementById('lab-search');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            const query = e.target.value.toLowerCase().trim();
            const tables = document.querySelectorAll('.tabla-cycsa');
            
            tables.forEach(table => {
                const rows = table.querySelectorAll('tbody tr');
                let hasVisibleRow = false;
                
                rows.forEach(row => {
                    if (row.classList.contains('no-results-row')) {
                        row.remove();
                        return;
                    }
                    if (row.cells.length === 1 && (row.textContent.includes('No hay') || row.textContent.includes('No se encontraron'))) {
                        return;
                    }
                    
                    const text = row.textContent.toLowerCase();
                    if (text.includes(query)) {
                        row.style.display = '';
                        hasVisibleRow = true;
                    } else {
                        row.style.display = 'none';
                    }
                });
                
                const existingNoResult = table.querySelector('.no-results-row');
                if (existingNoResult) {
                    existingNoResult.remove();
                }
                
                if (!hasVisibleRow && query !== '') {
                    const emptyRow = Array.from(rows).find(r => r.cells.length === 1 && r.textContent.includes('No hay'));
                    if (emptyRow) {
                        emptyRow.style.display = 'none';
                    }
                    
                    const colSpan = table.querySelectorAll('thead th').length;
                    const noResultRow = document.createElement('tr');
                    noResultRow.className = 'no-results-row';
                    noResultRow.innerHTML = `<td colspan="${colSpan}" style="text-align: center; padding: 25px; color: #64748b; font-style: italic;"><i class="fa-solid fa-circle-exclamation" style="margin-right: 6px;"></i> No se encontraron resultados para "${e.target.value}"</td>`;
                    table.querySelector('tbody').appendChild(noResultRow);
                } else {
                    const emptyRow = Array.from(rows).find(r => r.cells.length === 1 && r.textContent.includes('No hay'));
                    if (emptyRow && query === '') {
                        emptyRow.style.display = '';
                    }
                }
            });
        });
    }

    // --- LOGICA DEL CALENDARIO INTERACTIVO ---
    const eventosCal = <?= json_encode($eventosCalendario) ?>;
    let currentCalDate = new Date();
    
    function renderCalendar() {
        const grid = document.getElementById('calendario-dias');
        if (!grid) return;
        grid.innerHTML = '';
        
        const year = currentCalDate.getFullYear();
        const month = currentCalDate.getMonth();
        
        const meses = [
            "Enero", "Febrero", "Marzo", "Abril", "Mayo", "Junio",
            "Julio", "Agosto", "Septiembre", "Octubre", "Noviembre", "Diciembre"
        ];
        document.getElementById('cal-mes-año').innerText = meses[month] + " " + year;
        
        const firstDayOfMonth = new Date(year, month, 1);
        let startDayOfWeek = firstDayOfMonth.getDay();
        // Convert to Monday start: Monday = 0, ..., Sunday = 6
        startDayOfWeek = startDayOfWeek === 0 ? 6 : startDayOfWeek - 1;
        
        const totalDays = new Date(year, month + 1, 0).getDate();
        const prevMonthTotalDays = new Date(year, month, 0).getDate();
        
        // Render previous month padding days
        for (let i = startDayOfWeek - 1; i >= 0; i--) {
            const dayNum = prevMonthTotalDays - i;
            const cell = document.createElement('div');
            cell.className = 'dia-celda fuera-mes';
            cell.innerHTML = `<span class="dia-numero">${dayNum}</span>`;
            grid.appendChild(cell);
        }
        
        // Render current month days
        const today = new Date();
        for (let day = 1; day <= totalDays; day++) {
            const cell = document.createElement('div');
            const isToday = today.getDate() === day && today.getMonth() === month && today.getFullYear() === year;
            cell.className = 'dia-celda' + (isToday ? ' hoy' : '');
            
            const cellDateStr = `${year}-${String(month + 1).padStart(2, '0')}-${String(day).padStart(2, '0')}`;
            
            const dayEvents = eventosCal.filter(ev => ev.fecha_programada === cellDateStr);
            let eventsHtml = '';
            
            if (dayEvents.length > 0) {
                // Get unique OS (Orden de Servicio) codes to distinguish clients/orders
                const orders = [];
                dayEvents.forEach(ev => {
                    const osCode = ev.codigo_os || 'OS-General';
                    if (!orders.includes(osCode)) {
                        orders.push(osCode);
                    }
                });
                
                cell.classList.add('tiene-eventos');
                cell.style.cursor = 'pointer';
                cell.setAttribute('onclick', `abrirDetalleDia('${cellDateStr}')`);
                
                let badgeClass = 'evento-pendiente';
                const allCompleted = dayEvents.every(ev => ev.estado === 'Completado');
                if (allCompleted) {
                    badgeClass = 'evento-completado';
                } else if (isToday) {
                    badgeClass = 'evento-hoy';
                }
                
                let labelText = '';
                if (orders.length === 1) {
                    labelText = `${dayEvents.length} cil. - ${orders[0]}`;
                } else {
                    labelText = `${dayEvents.length} cil. (${orders.length} Órdenes)`;
                }
                
                eventsHtml = `<div class="eventos-lista">
                    <span class="evento-badge ${badgeClass}" style="display:block; font-size:9.5px; padding:3px 5px; border-radius:4px; font-weight:600; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;" title="Haga clic para ver detalles de las rupturas de este día.">
                        <i class="fa-solid fa-hammer"></i> ${labelText}
                    </span>
                </div>`;
            }
            
            cell.innerHTML = `<span class="dia-numero">${day}</span>${eventsHtml}`;
            grid.appendChild(cell);
        }
        
        // Render next month padding days to total 42 grid slots
        const totalRendered = startDayOfWeek + totalDays;
        const remaining = 42 - totalRendered;
        for (let i = 1; i <= remaining; i++) {
            const cell = document.createElement('div');
            cell.className = 'dia-celda fuera-mes';
            cell.innerHTML = `<span class="dia-numero">${i}</span>`;
            grid.appendChild(cell);
        }
    }

    window.abrirDetalleDia = function(dateStr) {
        const modal = document.getElementById('modalDetalleDia');
        if (!modal) return;
        
        const parts = dateStr.split('-');
        const formattedDate = parts[2] + '/' + parts[1] + '/' + parts[0];
        document.getElementById('det-fecha-titulo').innerText = formattedDate;
        
        const dayEvents = eventosCal.filter(ev => ev.fecha_programada === dateStr);
        const body = document.getElementById('det-modal-body');
        body.innerHTML = '';
        
        if (dayEvents.length === 0) {
            body.innerHTML = '<p style="text-align:center; color:#64748b; font-style:italic; padding:20px 0;">No hay rupturas programadas para este día.</p>';
            modal.style.display = 'flex';
            return;
        }
        
        const eventsByOrder = {};
        dayEvents.forEach(ev => {
            const osCode = ev.codigo_os || 'Orden General / Sin Código';
            if (!eventsByOrder[osCode]) {
                eventsByOrder[osCode] = [];
            }
            eventsByOrder[osCode].push(ev);
        });
        
        const osCodes = Object.keys(eventsByOrder);
        let html = '';
        
        html += `<div style="margin-bottom:15px; font-size:13px; color:#475569; font-weight:500;">
            <i class="fa-solid fa-list-check"></i> Encontradas <strong>${dayEvents.length} rupturas</strong> programadas pertenecientes a <strong>${osCodes.length} orden${osCodes.length > 1 ? 'es' : ''} de servicio</strong>.
        </div>`;
        
        osCodes.forEach(osCode => {
            const list = eventsByOrder[osCode];
            html += `
            <div class="client-group-card" style="border: 1px solid #e2e8f0; border-radius: 8px; margin-bottom: 18px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); overflow:hidden;">
                <div style="background: #f1f5f9; padding: 10px 15px; font-weight: 700; color: var(--cycsa-azul); font-size: 13px; display:flex; align-items:center; gap:8px; border-bottom: 1px solid #e2e8f0;">
                    <i class="fa-solid fa-file-contract"></i>
                    <span>Orden de Servicio: ${osCode}</span>
                </div>
                <div style="padding: 10px 15px;">
                    <table style="width:100%; border-collapse:collapse; font-size:12.5px;">
                        <thead>
                            <tr style="border-bottom: 1.5px solid #cbd5e1; text-align:left; color:#64748b; font-size:11px; text-transform:uppercase;">
                                <th style="padding:6px 0;">Cód. Lab</th>
                                <th style="padding:6px 0;">Cód. Campo</th>
                                <th style="padding:6px 0;">Cilindro</th>
                                <th style="padding:6px 0;">Edad</th>
                                <th style="padding:6px 0; text-align:right;">Estado</th>
                            </tr>
                        </thead>
                        <tbody>`;
            
            list.forEach(ev => {
                let badgeStyle = 'background-color:#eff6ff; color:#1e40af; border:1px solid #bfdbfe;';
                if (ev.estado === 'Completado') {
                    badgeStyle = 'background-color:#f0fdf4; color:#166534; border:1px solid #bbf7d0;';
                } else {
                    const todayStr = new Date().toISOString().split('T')[0];
                    if (dateStr === todayStr) {
                        badgeStyle = 'background-color:#fef2f2; color:#991b1b; border:1px solid #fecaca;';
                    }
                }
                
                html += `
                            <tr style="border-bottom:1px solid #f1f5f9; cursor:pointer;" onclick="location.href='/Cycsa/publico/laboratorio/detalle-muestra?id_lote=${ev.id_lote}'">
                                <td style="padding:8px 0; font-weight:700; color:#3b82f6;">${ev.codigo_muestra}</td>
                                <td style="padding:8px 0; color:#475569;">${ev.codigo_campo || '—'}</td>
                                <td style="padding:8px 0; font-weight:600; color:#1e293b;">Cilindro ${ev.identificador_especimen}</td>
                                <td style="padding:8px 0; font-weight:600; color:#6b7280;">${ev.edad_dias} días</td>
                                <td style="padding:8px 0; text-align:right;">
                                    <span class="status-badge" style="font-size:9.5px; font-weight:700; border-radius:12px; padding:2px 8px; ${badgeStyle}">${ev.estado}</span>
                                </td>
                            </tr>`;
            });
            
            html += `
                        </tbody>
                    </table>
                </div>
            </div>`;
        });
        
        body.innerHTML = html;
        modal.style.display = 'flex';
    };
    
    window.cerrarDetalleDiaModal = function() {
        const modal = document.getElementById('modalDetalleDia');
        if (modal) modal.style.display = 'none';
    };
    
    window.addEventListener('click', (e) => {
        const modal = document.getElementById('modalDetalleDia');
        if (e.target === modal) {
            cerrarDetalleDiaModal();
        }
    });
    
    window.navigateMonth = function(offset) {
        currentCalDate.setMonth(currentCalDate.getMonth() + offset);
        renderCalendar();
    };
    
    window.toggleCalendario = function() {
        const wrapper = document.getElementById('calendario-colapso');
        const icon = document.getElementById('cal-toggle-icon');
        const text = document.getElementById('cal-toggle-text');
        
        wrapper.classList.toggle('abierto');
        
        if (wrapper.classList.contains('abierto')) {
            icon.style.transform = 'rotate(180deg)';
            text.innerText = 'Ocultar Calendario';
            renderCalendar();
        } else {
            icon.style.transform = 'rotate(0deg)';
            text.innerText = 'Mostrar Calendario';
        }
    };
    
    function actualizarContadorHoy() {
        const today = new Date();
        const todayStr = `${today.getFullYear()}-${String(today.getMonth() + 1).padStart(2, '0')}-${String(today.getDate()).padStart(2, '0')}`;
        const todayEventsCount = eventosCal.filter(ev => ev.fecha_programada === todayStr && ev.estado !== 'Completado').length;
        
        const countBadge = document.getElementById('cal-contador-hoy');
        if (countBadge) {
            if (todayEventsCount > 0) {
                countBadge.innerText = `${todayEventsCount} para hoy`;
                countBadge.style.display = 'inline-block';
            } else {
                countBadge.style.display = 'none';
            }
        }
    }
    
    actualizarContadorHoy();
});
</script>
