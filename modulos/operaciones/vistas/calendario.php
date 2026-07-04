<?php
// Operations Calendar View - LIMS Ruptures Schedule
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
    
    .dia-celda { background-color: white; border: 1px solid #e2e8f0; border-radius: 8px; min-height: 130px; padding: 10px; display: flex; flex-direction: column; gap: 8px; position: relative; transition: all 0.2s ease; justify-content: flex-start; align-items: stretch; }
    .dia-celda:hover { border-color: #3b82f6; box-shadow: 0 10px 15px -3px rgba(59, 130, 246, 0.1), 0 4px 6px -4px rgba(59, 130, 246, 0.1); transform: translateY(-2px); z-index: 2; }
    .dia-fin-semana { background-color: #f8fafc; }
    .dia-vacio { background-color: #f8fafc; border: 1px solid #f1f5f9; border-radius: 8px; min-height: 130px; }
    
    .dia-numero { font-weight: 700; font-size: 13px; color: #64748b; margin-bottom: 2px; display: inline-block; width: 24px; height: 24px; text-align: center; line-height: 24px; border-radius: 50%; }
    .dia-hoy { background-color: var(--cycsa-azul); color: white; box-shadow: 0 2px 4px rgba(37, 99, 235, 0.3); }
    
    .event-tag { font-family: 'Inter', sans-serif; font-size: 11px; padding: 6px 8px; border-radius: 6px; cursor: pointer; display: flex; flex-direction: column; gap: 2px; border: 1px solid; transition: all 0.2s ease; box-shadow: 0 1px 2px rgba(0,0,0,0.02); text-decoration: none; }
    .event-tag:hover { transform: translateY(-1px); box-shadow: 0 4px 6px -1px rgba(0,0,0,0.08); }
    .event-ruptura { background-color: #fef2f2; color: #991b1b; border-color: #fecaca; border-left: 4px solid #ef4444; }
    .event-ruptura:hover { background-color: #fee2e2; border-color: #fca5a5; }

    .event-completado { background-color: #f0fdf4; color: #166534; border-color: #bbf7d0; border-left: 4px solid #22c55e; }
    .event-completado:hover { background-color: #dcfce7; border-color: #86efac; }
    
    .event-code { font-weight: 700; font-size: 10.5px; display: flex; align-items: center; gap: 4px; }
    .event-lote { font-weight: 600; color: #334155; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .event-details { color: #64748b; font-size: 10px; }
    
    .event-status-dot { width: 7px; height: 7px; border-radius: 50%; display: inline-block; }
    .status-Programado { background-color: #f59e0b; }
    .status-Completado { background-color: #10b981; }

    .calendario-legend { display: flex; flex-wrap: wrap; gap: 15px; margin-bottom: 20px; background: #fff; padding: 12px 18px; border-radius: 8px; border: 1px solid #e2e8f0; font-size: 12px; }
    .legend-item { display: flex; align-items: center; gap: 6px; font-weight: 500; color: #475569; }
</style>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
    
    <div class="header-flex" style="margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;">Calendario de Rupturas LIMS</h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 14px;">Muestra las rupturas de cilindros programadas según la fecha de moldeo y la edad del ensayo.</p>
        </div>
    </div>

    <!-- Pestañas secundarias -->
    <div class="tabs-container" style="display: flex; gap: 10px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 25px;">
        <a href="/Cycsa/publico/operaciones" class="tab-link" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 14px; background-color: #f1f5f9; color: #475569; transition: background 0.2s;"><i class="fa-solid fa-list-check" style="margin-right: 6px;"></i> Lista de Operaciones</a>
        <a href="/Cycsa/publico/operaciones/calendario" class="tab-link" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; background-color: var(--cycsa-azul); color: white;"><i class="fa-solid fa-calendar-days" style="margin-right: 6px;"></i> Calendario de Rupturas</a>
    </div>

    <!-- Leyenda de colores y estados -->
    <div class="calendario-legend">
        <div class="legend-item">
            <span style="display: inline-block; width: 12px; height: 12px; background-color: #fef2f2; border: 1px solid #fecaca; border-left: 3px solid #ef4444; border-radius: 3px;"></span>
            <span>Ruptura Programada</span>
        </div>
        <div class="legend-item">
            <span style="display: inline-block; width: 12px; height: 12px; background-color: #f0fdf4; border: 1px solid #bbf7d0; border-left: 3px solid #22c55e; border-radius: 3px;"></span>
            <span>Ruptura Completada</span>
        </div>
        <div style="width: 1px; background: #e2e8f0; align-self: stretch; margin: 0 5px;"></div>
        <div class="legend-item">
            <span class="event-status-dot status-Programado"></span>
            <span>Programado</span>
        </div>
        <div class="legend-item">
            <span class="event-status-dot status-Completado"></span>
            <span>Completado</span>
        </div>
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
        <div class="dia-semana" style="color: #ef4444;">Domingo</div>
        <div class="dia-semana">Lunes</div>
        <div class="dia-semana">Martes</div>
        <div class="dia-semana">Miércoles</div>
        <div class="dia-semana">Jueves</div>
        <div class="dia-semana">Viernes</div>
        <div class="dia-semana" style="color: #ef4444;">Sábado</div>

        <!-- Días vacíos al inicio -->
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
            $w = (int)date('w', strtotime(sprintf('%04d-%02d-%02d', $anio, $mes, $dia)));
            $esFinSemana = ($w === 0 || $w === 6);
        ?>
            <div class="dia-celda <?= $esFinSemana ? 'dia-fin-semana' : '' ?>">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <span class="dia-numero <?= $esHoy ? 'dia-hoy' : '' ?>">
                        <?= $dia ?>
                    </span>
                    <?php if (count($eventos) > 0): ?>
                        <span style="font-size: 10px; font-weight: 600; color: #475569; background: #e2e8f0; padding: 2px 6px; border-radius: 10px;" title="Cilindros programados hoy">
                            <?= count($eventos) ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <!-- Eventos del día -->
                <div style="display: flex; flex-direction: column; gap: 6px; flex: 1;">
                    <?php foreach ($eventos as $ev): 
                        $completado = $ev['estado'] === 'Completado';
                        $tagClass = $completado ? 'event-completado' : 'event-ruptura';
                        $statusClass = 'status-' . $ev['estado'];
                    ?>
                        <a href="/Cycsa/publico/operaciones/detalle-lote?id_lote=<?= $ev['id_ensayo'] ?>" class="event-tag <?= $tagClass ?>" title="Ensaye a los <?= $ev['edad_dias'] ?> días del lote <?= htmlspecialchars($ev['nombre_lote'], ENT_QUOTES, 'UTF-8') ?>">
                            <div style="display: flex; justify-content: space-between; align-items: center; width: 100%;">
                                <span class="event-code"><i class="fa-solid fa-hammer"></i> <?= htmlspecialchars($ev['codigo_muestra'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="event-status-dot <?= $statusClass ?>" title="Estado: <?= htmlspecialchars($ev['estado'], ENT_QUOTES, 'UTF-8') ?>"></span>
                            </div>
                            <div class="event-lote"><?= htmlspecialchars($ev['nombre_lote'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="event-details">Cilindro <strong><?= htmlspecialchars($ev['identificador_especimen'], ENT_QUOTES, 'UTF-8') ?></strong> a los <strong><?= $ev['edad_dias'] ?>d</strong></div>
                        </a>
                    <?php endforeach; ?>
                </div>
            </div>
        <?php endfor; ?>

        <!-- Celdas vacías al final -->
        <?php 
        $totalCeldas = $primerDiaSemana + $ultimoDia;
        $celdasRestantes = (7 - ($totalCeldas % 7)) % 7;
        for ($i = 0; $i < $celdasRestantes; $i++): 
        ?>
            <div class="dia-vacio"></div>
        <?php endfor; ?>
    </div>
</div>
