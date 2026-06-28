<?php
// Operations Calendar View
$nombresMeses = [
    1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
    5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
    9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre'
];

// Calcular mes anterior y posterior
$prevMes = $mes - 1;
$prevAnio = $anio;
if ($prevMes < 1) {
    $prevMes = 12;
    $prevAnio--;
}

$nextMes = $mes + 1;
$nextAnio = $anio;
if ($nextMes > 12) {
    $nextMes = 1;
    $nextAnio++;
}
?>
<style>
    .calendario-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; background-color: #f8fafc; padding: 15px 20px; border-radius: 8px; border: 1px solid #e2e8f0; }
    .btn-mes { background-color: white; border: 1px solid #cbd5e1; padding: 8px 16px; border-radius: 6px; text-decoration: none; color: #475569; font-weight: 600; font-size: 13.5px; transition: all 0.2s; display: flex; align-items: center; gap: 6px; }
    .btn-mes:hover { background-color: #f1f5f9; color: var(--cycsa-azul); border-color: var(--cycsa-azul); }
    
    .grid-calendario { display: grid; grid-template-columns: repeat(7, 1fr); gap: 10px; }
    .dia-semana { background-color: #f1f5f9; text-align: center; font-weight: 700; padding: 10px 0; border-radius: 4px; color: #475569; font-size: 12px; text-transform: uppercase; letter-spacing: 0.5px; }
    
    .dia-celda { background-color: white; border: 1px solid #e2e8f0; border-radius: 8px; min-height: 110px; padding: 8px; display: flex; flex-direction: column; gap: 5px; position: relative; transition: all 0.2s; }
    .dia-celda:hover { border-color: #cbd5e1; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
    .dia-vacio { background-color: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; min-height: 110px; }
    
    .dia-numero { font-weight: 700; font-size: 14px; color: #64748b; margin-bottom: 4px; display: inline-block; width: 24px; height: 24px; text-align: center; line-height: 24px; border-radius: 50%; }
    .dia-hoy { background-color: var(--cycsa-azul); color: white; }
    
    .event-tag { font-size: 11px; padding: 4px 8px; border-radius: 4px; font-weight: 600; cursor: pointer; display: flex; align-items: center; gap: 5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; border-left: 3px solid; transition: transform 0.1s; }
    .event-tag:hover { transform: scale(1.02); }
    .event-entrega { background-color: #f0fdf4; color: #166534; border-left-color: #22c55e; border: 1px solid #bbf7d0; }
    .event-seguimiento { background-color: #eff6ff; color: #1e40af; border-left-color: #3b82f6; border: 1px solid #bfdbfe; }
    
    .modal-premium { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .modal-premium-content { background-color: #fff; margin: 4% auto; padding: 30px; border: 1px solid #e2e8f0; width: 48%; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
    .btn-cerrar { background: none; border: none; font-size: 20px; color: #94a3b8; cursor: pointer; }
    .btn-cerrar:hover { color: #475569; }
    .form-control { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; }
    
    .tabla-detalle-items { width: 100%; border-collapse: collapse; margin-top: 15px; font-size: 13px; }
    .tabla-detalle-items th { background-color: #f8fafc; color: #475569; padding: 10px; font-weight: 600; border-bottom: 2px solid #e2e8f0; text-align: left; }
    .tabla-detalle-items td { padding: 10px; border-bottom: 1px solid #f1f5f9; color: #334155; }
</style>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
    
    <div class="header-flex" style="margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;">Calendario Operativo</h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 14px;">Calendario mensual de despachos, entregas y seguimientos de proyectos.</p>
        </div>
    </div>

    <!-- Pestañas secundarias -->
    <div class="tabs-container" style="display: flex; gap: 10px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 25px;">
        <a href="/Cycsa/publico/operaciones" class="tab-link" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 14px; background-color: #f1f5f9; color: #475569; transition: background 0.2s;"><i class="fa-solid fa-list-check" style="margin-right: 6px;"></i> Lista de Operaciones</a>
        <a href="/Cycsa/publico/operaciones/calendario" class="tab-link" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; background-color: var(--cycsa-azul); color: white;"><i class="fa-solid fa-calendar-days" style="margin-right: 6px;"></i> Calendario de Entregas</a>
    </div>

    <!-- Cabecera del Calendario (Navegación de Meses) -->
    <div class="calendario-header">
        <a href="/Cycsa/publico/operaciones/calendario?mes=<?= $prevMes ?>&anio=<?= $prevAnio ?>" class="btn-mes">
            <i class="fa-solid fa-chevron-left"></i> Anterior
        </a>
        
        <h3 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700; color: #0f172a;">
            <?= $nombresMeses[$mes] ?> de <?= $anio ?>
        </h3>
        
        <a href="/Cycsa/publico/operaciones/calendario?mes=<?= $nextMes ?>&anio=<?= $nextAnio ?>" class="btn-mes">
            Siguiente <i class="fa-solid fa-chevron-right"></i>
        </a>
    </div>

    <!-- Grid del Calendario -->
    <div class="grid-calendario">
        <!-- Días de la semana -->
        <div class="dia-semana">Domingo</div>
        <div class="dia-semana">Lunes</div>
        <div class="dia-semana">Martes</div>
        <div class="dia-semana">Miércoles</div>
        <div class="dia-semana">Jueves</div>
        <div class="dia-semana">Viernes</div>
        <div class="dia-semana">Sábado</div>

        <!-- Celdas vacías al inicio -->
        <?php for ($i = 0; $i < $primerDiaSemana; $i++): ?>
            <div class="dia-vacio"></div>
        <?php endfor; ?>

        <!-- Días del mes -->
        <?php 
        $hoyDia = (int)date('d');
        $hoyMes = (int)date('m');
        $hoyAnio = (int)date('Y');
        
        for ($dia = 1; $dia <= $ultimoDia; $dia++): 
            $esHoy = ($dia === $hoyDia && $mes === $hoyMes && $anio === $hoyAnio);
            $eventos = $eventosPorDia[$dia] ?? [];
        ?>
            <div class="dia-celda">
                <div>
                    <span class="dia-numero <?= $esHoy ? 'dia-hoy' : '' ?>">
                        <?= $dia ?>
                    </span>
                </div>
                
                <!-- Eventos del día -->
                <div style="display: flex; flex-direction: column; gap: 4px; flex: 1; overflow-y: auto;">
                    <?php foreach ($eventos as $ev): 
                        $tagClass = $ev['tipo_evento'] === 'entrega' ? 'event-entrega' : 'event-seguimiento';
                        $icon = $ev['tipo_evento'] === 'entrega' ? 'fa-truck' : 'fa-calendar-check';
                        $prefijo = $ev['tipo_evento'] === 'entrega' ? 'ENTREGA:' : 'SEGUIM.:';
                    ?>
                        <div class="event-tag <?= $tagClass ?>" onclick="verDetalleOperaciones(<?= $ev['id_cotizacion'] ?>)" title="<?= $prefijo ?> <?= htmlspecialchars($ev['cot_codigo'] . ' - ' . $ev['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?>">
                            <i class="fa-solid <?= $icon ?>"></i> 
                            <span style="font-weight: 700;"><?= htmlspecialchars($ev['cot_codigo'], ENT_QUOTES, 'UTF-8') ?></span> 
                            <span><?= htmlspecialchars($ev['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?></span>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endfor; ?>

        <!-- Celdas vacías al final para completar la fila de 7 columnas -->
        <?php 
        $totalCeldas = $primerDiaSemana + $ultimoDia;
        $celdasRestantes = (7 - ($totalCeldas % 7)) % 7;
        for ($i = 0; $i < $celdasRestantes; $i++): 
        ?>
            <div class="dia-vacio"></div>
        <?php endfor; ?>
    </div>
</div>

<!-- MODAL VER DETALLES SIN PRECIOS -->
<div id="modalDetalle" class="modal-premium">
    <div class="modal-premium-content" style="width: 48%; max-height: 85vh; overflow-y: auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 19px; font-weight: 700;">Detalles de la Operación</h3>
            <button onclick="cerrarDetalleModal()" class="btn-cerrar">&times;</button>
        </div>
        
        <!-- Banner informativo de exclusión de precios -->
        <div style="background-color: #f0fdfa; border: 1px solid #5eead4; color: #0f766e; padding: 10px 15px; border-radius: 6px; font-size: 13px; font-weight: 500; margin-bottom: 20px; display: flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-shield-halved"></i> <strong>Vista de Operaciones:</strong> Información comercial y precios omitidos.
        </div>

        <div id="detalle_contenido" style="font-size: 13.5px; line-height: 1.5; color: #334155;">
            <!-- Cargado vía AJAX -->
            <div style="text-align: center; padding: 40px; color: #94a3b8;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px;"></i><br><br>
                Cargando información...
            </div>
        </div>

        <div style="display: flex; justify-content: flex-end; margin-top: 25px;">
            <button type="button" onclick="cerrarDetalleModal()" class="form-control" style="cursor: pointer; background: #f1f5f9; border: 1px solid #cbd5e1; font-weight: 600; color: #475569; width: 120px; text-align: center; padding: 8px 0;">Cerrar</button>
        </div>
    </div>
</div>

<script>
    const detModal = document.getElementById('modalDetalle');

    function verDetalleOperaciones(idCot) {
        detModal.style.display = 'block';
        document.getElementById('detalle_contenido').innerHTML = `
            <div style="text-align: center; padding: 40px; color: #94a3b8;">
                <i class="fa-solid fa-spinner fa-spin" style="font-size: 24px;"></i><br><br>
                Cargando información...
            </div>
        `;

        fetch('/Cycsa/publico/operaciones/detalle-ajax?id=' + idCot)
            .then(res => res.json())
            .then(data => {
                if (data.error) {
                    document.getElementById('detalle_contenido').innerHTML = `<div style="color:red; text-align:center; padding:20px;">${data.error}</div>`;
                    return;
                }

                const cot = data.cotizacion;
                const items = data.detalles;

                let html = `
                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                        <div>
                            <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Código Cotización</div>
                            <strong>${cot.cot_codigo}</strong>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Cliente</div>
                            <strong>${cot.cliente_nombre}</strong>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Proyecto</div>
                            <strong>${cot.nombre_proyecto}</strong>
                        </div>
                        <div>
                            <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Atención A</div>
                            <strong>${cot.atencion_a ? cot.atencion_a : '—'}</strong>
                        </div>
                    </div>

                    <div style="margin-bottom: 20px;">
                        <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Dirección del Proyecto</div>
                        <div style="background-color: #f8fafc; padding: 10px; border-radius: 6px; border: 1px solid #e2e8f0; margin-top: 4px;">
                            ${cot.direccion_proyecto}
                        </div>
                    </div>

                    <div style="display:grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px; background: #f8fafc; padding: 15px; border-radius: 8px;">
                        <div>
                            <strong>Condiciones de Pago:</strong><br>
                            <span style="color:#475569;">${cot.condicion_pago}</span>
                        </div>
                        <div>
                            <strong>Tiempo de Entrega:</strong><br>
                            <span style="color:#475569;">${cot.tiempo_entrega ? cot.tiempo_entrega : '—'}</span>
                        </div>
                    </div>

                    <h4 style="margin-top: 25px; margin-bottom: 10px; font-family:'Outfit'; color:#0f172a; border-bottom: 2px solid #f1f5f9; padding-bottom: 6px;">Materiales y Ensayos a Procesar</h4>
                    <table class="tabla-detalle-items">
                        <thead>
                            <tr>
                                <th style="width:100px;">Código</th>
                                <th>Ensayo / Servicio</th>
                                <th style="width:120px;">Norma / ASTM</th>
                                <th style="width:80px; text-align:right;">Cantidad</th>
                            </tr>
                        </thead>
                        <tbody>
                `;

                items.forEach(it => {
                    html += `
                        <tr>
                            <td style="font-family:monospace;">${it.codigo_servicio ? it.codigo_servicio : '—'}</td>
                            <td style="font-weight:500;">${it.descripcion_ensayo}</td>
                            <td style="color:#64748b;">${it.norma_astm ? it.norma_astm : 'N/A'}</td>
                            <td style="text-align:right; font-weight:600;">${Number(it.cantidad).toFixed(0)}</td>
                        </tr>
                    `;
                });

                html += `
                        </tbody>
                    </table>

                    ${cot.notas_operativas ? `
                    <div style="margin-top: 25px; background: #fffbeb; border: 1px solid #fef3c7; padding: 15px; border-radius: 8px; color: #b45309;">
                        <strong><i class="fa-solid fa-triangle-exclamation"></i> Notas e Instrucciones de Operación:</strong>
                        <p style="margin-top: 6px; font-size: 13px;">${cot.notas_operativas}</p>
                    </div>
                    ` : ''}
                `;

                document.getElementById('detalle_contenido').innerHTML = html;
            })
            .catch(err => {
                document.getElementById('detalle_contenido').innerHTML = `<div style="color:red; text-align:center; padding:20px;">Error al obtener detalles: ${err}</div>`;
            });
    }

    function cerrarDetalleModal() {
        detModal.style.display = 'none';
    }

    window.addEventListener('click', (e) => {
        if (e.target === detModal) cerrarDetalleModal();
    });
</script>
