<script src="https://cdn.jsdelivr.net/npm/apexcharts"></script>

<style>
    :root {
        --kpi-gradient-1: linear-gradient(135deg, #103487, #2563eb);
        --kpi-gradient-2: linear-gradient(135deg, #15803d, #22c55e);
        --kpi-gradient-3: linear-gradient(135deg, #b45309, #f59e0b);
        --kpi-gradient-4: linear-gradient(135deg, #0369a1, #0ea5e9);
        
        --card-shadow: 0 10px 30px -5px rgba(0, 0, 0, 0.05), 0 5px 15px -8px rgba(0, 0, 0, 0.05);
        --card-hover-shadow: 0 20px 40px -5px rgba(16, 52, 135, 0.08), 0 10px 20px -8px rgba(16, 52, 135, 0.08);
    }

    .dashboard-container {
        display: flex;
        flex-direction: column;
        gap: 25px;
        font-family: 'Inter', sans-serif;
    }

    /* KPI CARDS */
    .kpi-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
        gap: 20px;
    }

    .kpi-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        display: flex;
        align-items: center;
        justify-content: space-between;
        box-shadow: var(--card-shadow);
        border: 1px solid rgba(226, 232, 240, 0.8);
        transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
        position: relative;
        overflow: hidden;
        cursor: pointer;
    }

    .kpi-card:hover {
        transform: translateY(-5px);
        box-shadow: var(--card-hover-shadow);
        border-color: rgba(16, 52, 135, 0.2);
    }

    .kpi-card::before {
        content: '';
        position: absolute;
        top: 0;
        left: 0;
        width: 100%;
        height: 4px;
        background: var(--cycsa-azul);
    }

    .kpi-card.kpi-1::before { background: var(--kpi-gradient-1); }
    .kpi-card.kpi-2::before { background: var(--kpi-gradient-2); }
    .kpi-card.kpi-3::before { background: var(--kpi-gradient-3); }
    .kpi-card.kpi-4::before { background: var(--kpi-gradient-4); }

    .kpi-info {
        display: flex;
        flex-direction: column;
        gap: 6px;
    }

    .kpi-title {
        font-size: 13px;
        font-weight: 600;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        color: #64748b;
    }

    .kpi-value {
        font-size: 28px;
        font-weight: 700;
        color: #1e293b;
        line-height: 1.1;
    }

    .kpi-subtitle {
        font-size: 11px;
        color: #94a3b8;
    }

    .kpi-icon-wrapper {
        width: 52px;
        height: 52px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        transition: all 0.3s ease;
    }

    .kpi-card:hover .kpi-icon-wrapper {
        transform: scale(1.1) rotate(5deg);
    }

    .kpi-1 .kpi-icon-wrapper { background: rgba(16, 52, 135, 0.08); color: #103487; }
    .kpi-2 .kpi-icon-wrapper { background: rgba(21, 128, 61, 0.08); color: #15803d; }
    .kpi-3 .kpi-icon-wrapper { background: rgba(180, 83, 9, 0.08); color: #b45309; }
    .kpi-4 .kpi-icon-wrapper { background: rgba(3, 105, 161, 0.08); color: #0369a1; }

    .kpi-icon-wrapper i {
        font-size: 22px;
    }

    /* CHART GRIDS */
    .row-grid-2 {
        display: grid;
        grid-template-columns: 2fr 1fr;
        gap: 20px;
    }

    @media (max-width: 1024px) {
        .row-grid-2 {
            grid-template-columns: 1fr;
        }
    }

    .chart-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: var(--card-shadow);
        border: 1px solid rgba(226, 232, 240, 0.8);
        display: flex;
        flex-direction: column;
        gap: 15px;
    }

    .chart-card-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 1px solid #f1f5f9;
        padding-bottom: 15px;
    }

    .chart-card-title {
        font-size: 16px;
        font-weight: 700;
        color: #1e293b;
        display: flex;
        align-items: center;
        gap: 10px;
    }

    .chart-card-title i {
        color: var(--cycsa-azul);
    }

    /* TOP CLIENTS LIST */
    .top-clients-list {
        display: flex;
        flex-direction: column;
        gap: 16px;
        margin-top: 10px;
    }

    .client-item {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }

    .client-info {
        display: flex;
        justify-content: space-between;
        align-items: center;
    }

    .client-name {
        font-size: 14px;
        font-weight: 600;
        color: #334155;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
        max-width: 70%;
    }

    .client-amount {
        font-size: 14px;
        font-weight: 700;
        color: #0f172a;
    }

    .progress-bar-bg {
        width: 100%;
        height: 8px;
        background: #f1f5f9;
        border-radius: 4px;
        overflow: hidden;
    }

    .progress-bar-fill {
        height: 100%;
        background: linear-gradient(90deg, #103487, #3b82f6);
        border-radius: 4px;
        transition: width 1s cubic-bezier(0.4, 0, 0.2, 1);
        width: 0; /* Animated with JS or CSS */
    }

    /* RECENT ACTIVITIES TABLE */
    .recent-table-card {
        background: white;
        border-radius: 16px;
        padding: 24px;
        box-shadow: var(--card-shadow);
        border: 1px solid rgba(226, 232, 240, 0.8);
    }

    .table-responsive {
        overflow-x: auto;
        margin-top: 15px;
    }

    .premium-table {
        width: 100%;
        border-collapse: collapse;
        text-align: left;
    }

    .premium-table th {
        background: #f8fafc;
        color: #64748b;
        font-weight: 600;
        font-size: 12px;
        text-transform: uppercase;
        letter-spacing: 0.5px;
        padding: 14px 18px;
        border-bottom: 2px solid #e2e8f0;
    }

    .premium-table td {
        padding: 16px 18px;
        border-bottom: 1px solid #f1f5f9;
        color: #334155;
        font-size: 14px;
        vertical-align: middle;
    }

    .premium-table tr:last-child td {
        border-bottom: none;
    }

    .premium-table tr:hover td {
        background: #f8fafc;
    }

    /* STATUS BADGES */
    .badge-premium {
        padding: 6px 12px;
        border-radius: 8px;
        font-size: 12px;
        font-weight: 600;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    
    .badge-borrador { background-color: #f1f5f9; color: #475569; border: 1px solid #e2e8f0; }
    .badge-revision { background-color: #fef3c7; color: #d97706; border: 1px solid #fde68a; }
    .badge-observada { background-color: #fee2e2; color: #dc2626; border: 1px solid #fecaca; }
    .badge-aprobada-int { background-color: #dbeafe; color: #2563eb; border: 1px solid #bfdbfe; }
    .badge-enviada { background-color: #f3e8ff; color: #9333ea; border: 1px solid #e9d5ff; }
    .badge-aprobada-cli { background-color: #dcfce7; color: #16a34a; border: 1px solid #bbf7d0; }
    .badge-rechazada-cli { background-color: #ffe4e6; color: #e11d48; border: 1px solid #fecdd3; }

    .code-span {
        font-family: monospace;
        font-weight: 700;
        color: #103487;
    }
</style>

<div class="dashboard-container">
    
    <!-- Welcome Header -->
    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px; margin-bottom: 5px;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-size: 24px; font-weight: 800;">Panel de Control</h2>
            <p style="color: #64748b; margin-top: 4px; font-size: 14px;">Bienvenido de nuevo, <strong style="color: #103487;"><?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?></strong>. Aquí está el resumen de la actividad comercial.</p>
        </div>
        <div style="font-size: 13px; color: #64748b; background: white; padding: 8px 16px; border-radius: 30px; box-shadow: var(--card-shadow); border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 8px;">
            <span style="width: 8px; height: 8px; background: #22c55e; border-radius: 50%; display: inline-block;"></span>
            <span>Sistema Activo</span>
        </div>
    </div>

    <!-- KPI Grid -->
    <div class="kpi-grid">
        <!-- Cotizaciones Totales -->
        <div class="kpi-card kpi-1" onclick="window.location.href='/Cycsa/publico/cotizaciones?tab=todas'">
            <div class="kpi-info">
                <span class="kpi-title">Total Cotizado</span>
                <span class="kpi-value"><?= number_format($kpis['total_cotizaciones']) ?></span>
                <span class="kpi-subtitle">Propuestas generadas</span>
            </div>
            <div class="kpi-icon-wrapper">
                <i class="fa-solid fa-file-invoice-dollar"></i>
            </div>
        </div>

        <!-- Monto Aprobado -->
        <div class="kpi-card kpi-2" onclick="window.location.href='/Cycsa/publico/cotizaciones?tab=aprobadas'">
            <div class="kpi-info">
                <span class="kpi-title">Aprobado Cliente</span>
                <span class="kpi-value" style="font-size: 20px; margin-top: 3px; font-weight: 800;">C$ <?= number_format($kpis['total_monto_aprobado'], 2) ?></span>
                <span class="kpi-subtitle">Monto en cartera activa</span>
            </div>
            <div class="kpi-icon-wrapper">
                <i class="fa-solid fa-circle-check"></i>
            </div>
        </div>

        <!-- En Revisión -->
        <div class="kpi-card kpi-3" onclick="window.location.href='/Cycsa/publico/cotizaciones?tab=revision'">
            <div class="kpi-info">
                <span class="kpi-title">En Revisión</span>
                <span class="kpi-value"><?= number_format($kpis['total_en_revision']) ?></span>
                <span class="kpi-subtitle">Pendientes de firma interna</span>
            </div>
            <div class="kpi-icon-wrapper">
                <i class="fa-solid fa-clock-rotate-left"></i>
            </div>
        </div>

        <!-- Clientes Activos -->
        <div class="kpi-card kpi-4" onclick="window.location.href='/Cycsa/publico/clientes'">
            <div class="kpi-info">
                <span class="kpi-title">Clientes Activos</span>
                <span class="kpi-value"><?= number_format($kpis['total_clientes']) ?></span>
                <span class="kpi-subtitle">Clientes con cotizaciones</span>
            </div>
            <div class="kpi-icon-wrapper">
                <i class="fa-solid fa-address-book"></i>
            </div>
        </div>
    </div>

    <!-- Row 1: Area Chart & Donut Chart -->
    <div class="row-grid-2">
        <!-- Ventas Mensuales -->
        <div class="chart-card">
            <div class="chart-card-header">
                <span class="chart-card-title"><i class="fa-solid fa-chart-line"></i> Ventas Mensuales (Últimos 6 Meses)</span>
                <span style="font-size: 12px; color: #64748b; font-weight: 500;">Monto Mensual en C$</span>
            </div>
            <?php
            $total_monto_periodo = 0.0;
            foreach ($tendencia_mensual as $tm) {
                $total_monto_periodo += $tm['total'];
            }
            if ($total_monto_periodo === 0.0 && $kpis['total_cotizaciones'] === 0):
            ?>
                <div style="height: 320px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #64748b; gap: 10px;">
                    <i class="fa-solid fa-chart-line" style="font-size: 40px; opacity: 0.3;"></i>
                    <span>No hay cotizaciones registradas en los últimos 6 meses</span>
                </div>
            <?php else: ?>
                <div id="chart-mensual" style="min-height: 320px;"></div>
            <?php endif; ?>
        </div>

        <!-- Distribución de Estados -->
        <div class="chart-card">
            <div class="chart-card-header">
                <span class="chart-card-title"><i class="fa-solid fa-chart-pie"></i> Distribución de Estados</span>
            </div>
            <?php if (empty($distribucion_estados)): ?>
                <div style="height: 320px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #64748b; gap: 10px;">
                    <i class="fa-solid fa-chart-pie" style="font-size: 40px; opacity: 0.3;"></i>
                    <span>No hay cotizaciones para clasificar</span>
                </div>
            <?php else: ?>
                <div id="chart-estados" style="min-height: 320px; display: flex; align-items: center; justify-content: center;"></div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Row 2: Column Chart (Prioridad) & Top Clientes -->
    <div class="row-grid-2">
        <!-- Prioridades -->
        <div class="chart-card">
            <div class="chart-card-header">
                <span class="chart-card-title"><i class="fa-solid fa-triangle-exclamation"></i> Prioridad de Cotizaciones</span>
                <span style="font-size: 12px; color: #64748b; font-weight: 500;">Cantidad por prioridad</span>
            </div>
            <?php 
            $total_prioridades = array_sum($distribucion_prioridad);
            if ($total_prioridades === 0): 
            ?>
                <div style="height: 280px; display: flex; flex-direction: column; align-items: center; justify-content: center; color: #64748b; gap: 10px;">
                    <i class="fa-solid fa-triangle-exclamation" style="font-size: 40px; opacity: 0.3;"></i>
                    <span>No hay cotizaciones con prioridad registrada</span>
                </div>
            <?php else: ?>
                <div id="chart-prioridad" style="min-height: 280px;"></div>
            <?php endif; ?>
        </div>

        <!-- Top Clientes -->
        <div class="chart-card">
            <div class="chart-card-header">
                <span class="chart-card-title"><i class="fa-solid fa-crown"></i> Top Clientes</span>
                <span style="font-size: 12px; color: #64748b; font-weight: 500;">Por volumen total</span>
            </div>
            <div class="top-clients-list">
                <?php 
                $max_monto = 0.01; // Avoid division by zero
                foreach ($top_clientes as $tc) {
                    if ($tc['total_monto'] > $max_monto) {
                        $max_monto = (float) $tc['total_monto'];
                    }
                }
                ?>
                <?php foreach ($top_clientes as $tc): 
                    $porcentaje = ($max_monto > 0) ? ($tc['total_monto'] / $max_monto) * 100 : 0;
                ?>
                    <div class="client-item">
                        <div class="client-info">
                            <span class="client-name" title="<?= htmlspecialchars($tc['cliente'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($tc['cliente'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="client-amount">C$ <?= number_format($tc['total_monto'], 2) ?></span>
                        </div>
                        <div class="progress-bar-bg">
                            <div class="progress-bar-fill" style="width: <?= $porcentaje ?>%"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
                <?php if (empty($top_clientes)): ?>
                    <div style="text-align: center; color: #64748b; padding: 40px 0;">No hay datos de clientes disponibles.</div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Recent Quotations Card (Full Width) -->
    <div class="recent-table-card">
        <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #f1f5f9; padding-bottom: 15px;">
            <h3 style="font-size: 16px; font-weight: 700; color: #1e293b; margin: 0; display: flex; align-items: center; gap: 10px;">
                <i class="fa-solid fa-list-check" style="color: var(--cycsa-azul);"></i> Cotizaciones Recientes
            </h3>
            <a href="/Cycsa/publico/cotizaciones" style="color: var(--cycsa-azul); font-weight: 600; font-size: 13px; text-decoration: none; display: flex; align-items: center; gap: 4px;">
                Ver todas <i class="fa-solid fa-arrow-right"></i>
            </a>
        </div>
        
        <div class="table-responsive">
            <table class="premium-table">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Fecha de Creación</th>
                        <th>Total</th>
                        <th>Estado</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($recientes as $rec): ?>
                        <tr>
                            <td class="code-span"><?= htmlspecialchars($rec['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="font-weight: 600;"><?= htmlspecialchars($rec['cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="color: #64748b;"><?= date('d/m/Y h:i A', strtotime($rec['fecha_creacion'])) ?></td>
                            <td style="font-weight: 700; color: #0f172a;">C$ <?= number_format($rec['total'], 2) ?></td>
                            <td>
                                <?php 
                                    $claseBadge = 'badge-borrador';
                                    if ($rec['estado'] == 'En Revision') $claseBadge = 'badge-revision';
                                    if ($rec['estado'] == 'Observada') $claseBadge = 'badge-observada';
                                    if ($rec['estado'] == 'Aprobada Internamente') $claseBadge = 'badge-aprobada-int';
                                    if ($rec['estado'] == 'Enviada al Cliente') $claseBadge = 'badge-enviada';
                                    if ($rec['estado'] == 'Aprobada por Cliente') $claseBadge = 'badge-aprobada-cli';
                                    if ($rec['estado'] == 'Rechazada por Cliente') $claseBadge = 'badge-rechazada-cli';
                                ?>
                                <span class="badge-premium <?= $claseBadge ?>">
                                    <span style="width: 6px; height: 6px; border-radius: 50%; background: currentColor; display: inline-block;"></span>
                                    <?= htmlspecialchars($rec['estado'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    <?php if (empty($recientes)): ?>
                        <tr>
                            <td colspan="5" style="text-align: center; color: #64748b; padding: 30px;">No hay cotizaciones registradas.</td>
                        </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

</div>

<!-- ApexCharts Setup Script -->
<script>
document.addEventListener("DOMContentLoaded", function() {
    // Render progress bar animations
    setTimeout(function() {
        const bars = document.querySelectorAll('.progress-bar-fill');
        bars.forEach(bar => {
            const width = bar.style.width;
            bar.style.width = '0';
            setTimeout(() => {
                bar.style.width = width;
            }, 100);
        });
    }, 100);

    // 1. Ventas Mensuales (Area Chart)
    <?php if (!($total_monto_periodo === 0.0 && $kpis['total_cotizaciones'] === 0)): ?>
        <?php
        $trend_labels = [];
        $trend_totals = [];
        $trend_counts = [];
        foreach ($tendencia_mensual as $tm) {
            $trend_labels[] = $tm['nombre_mes'] . ' ' . substr($tm['mes'], 2, 2); // e.g. "Ene 26"
            $trend_totals[] = $tm['total'];
            $trend_counts[] = $tm['cantidad'];
        }
        ?>
        
        var optionsMensual = {
            series: [{
                name: 'Monto Cotizado (C$)',
                data: <?= json_encode($trend_totals) ?>
            }, {
                name: 'Cantidad de Cotizaciones',
                data: <?= json_encode($trend_counts) ?>
            }],
            chart: {
                height: 320,
                type: 'area',
                toolbar: { show: false },
                fontFamily: 'Inter, sans-serif'
            },
            dataLabels: { enabled: false },
            stroke: {
                curve: 'smooth',
                width: [3, 2]
            },
            colors: ['#103487', '#10b981'],
            fill: {
                type: 'gradient',
                gradient: {
                    shadeIntensity: 1,
                    opacityFrom: 0.45,
                    opacityTo: 0.05,
                    stops: [0, 90, 100]
                }
            },
            xaxis: {
                categories: <?= json_encode($trend_labels) ?>,
                labels: {
                    style: { colors: '#64748b', fontSize: '12px' }
                }
            },
            yaxis: [{
                title: { text: 'Monto en C$', style: { color: '#103487', fontWeight: 600 } },
                labels: {
                    style: { colors: '#64748b' },
                    formatter: function (val) {
                        return "C$ " + val.toLocaleString('es-NI', { minimumFractionDigits: 0, maximumFractionDigits: 0 });
                    }
                }
            }, {
                opposite: true,
                title: { text: 'Cantidad', style: { color: '#10b981', fontWeight: 600 } },
                labels: {
                    style: { colors: '#64748b' },
                    formatter: function (val) {
                        return Math.round(val);
                    }
                }
            }],
            tooltip: {
                y: {
                    formatter: function (val, { seriesIndex }) {
                        if (seriesIndex === 0) {
                            return "C$ " + val.toLocaleString('es-NI', { minimumFractionDigits: 2 });
                        }
                        return val + " cotizaciones";
                    }
                }
            },
            grid: { borderColor: '#f1f5f9' }
        };
        
        var chartMensual = new ApexCharts(document.querySelector("#chart-mensual"), optionsMensual);
        chartMensual.render();
    <?php endif; ?>

    // 2. Distribución de Estados (Donut Chart)
    <?php if (!empty($distribucion_estados)): ?>
        <?php
        $estado_labels = [];
        $estado_counts = [];
        foreach ($distribucion_estados as $de) {
            $estado_labels[] = $de['estado'];
            $estado_counts[] = (int) $de['cantidad'];
        }
        
        $color_map = [
            'Borrador' => '#94a3b8',
            'En Revision' => '#f59e0b',
            'Observada' => '#ef4444',
            'Aprobada Internamente' => '#3b82f6',
            'Enviada al Cliente' => '#a855f7',
            'Aprobada por Cliente' => '#10b981',
            'Rechazada por Cliente' => '#f43f5e'
        ];
        
        $donut_colors = [];
        foreach ($estado_labels as $lbl) {
            $donut_colors[] = $color_map[$lbl] ?? '#64748b';
        }
        ?>

        var optionsEstados = {
            series: <?= json_encode($estado_counts) ?>,
            chart: {
                type: 'donut',
                height: 320,
                fontFamily: 'Inter, sans-serif'
            },
            labels: <?= json_encode($estado_labels) ?>,
            colors: <?= json_encode($donut_colors) ?>,
            legend: {
                position: 'bottom',
                labels: { colors: '#64748b' }
            },
            dataLabels: {
                enabled: true,
                formatter: function (val, opts) {
                    return opts.w.config.series[opts.seriesIndex];
                }
            },
            plotOptions: {
                pie: {
                    donut: {
                        size: '70%',
                        labels: {
                            show: true,
                            total: {
                                show: true,
                                label: 'Total',
                                color: '#64748b',
                                formatter: function (w) {
                                    return w.globals.seriesTotals.reduce((a, b) => a + b, 0);
                                }
                            }
                        }
                    }
                }
            },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val + " cotizaciones";
                    }
                }
            }
        };

        var chartEstados = new ApexCharts(document.querySelector("#chart-estados"), optionsEstados);
        chartEstados.render();
    <?php endif; ?>

    // 3. Prioridad de Cotizaciones (Column Chart)
    <?php if ($total_prioridades > 0): ?>
        var optionsPrioridad = {
            series: [{
                name: 'Cotizaciones',
                data: [
                    <?= (int)$distribucion_prioridad['Alta'] ?>,
                    <?= (int)$distribucion_prioridad['Media'] ?>,
                    <?= (int)$distribucion_prioridad['Normal'] ?>
                ]
            }],
            chart: {
                type: 'bar',
                height: 280,
                toolbar: { show: false },
                fontFamily: 'Inter, sans-serif'
            },
            plotOptions: {
                bar: {
                    borderRadius: 8,
                    columnWidth: '45%',
                    distributed: true
                }
            },
            colors: ['#dc2626', '#f59e0b', '#3b82f6'],
            dataLabels: { enabled: false },
            legend: { show: false },
            xaxis: {
                categories: ['Alta', 'Media', 'Normal'],
                labels: {
                    style: { colors: '#64748b', fontSize: '13px', fontWeight: 600 }
                }
            },
            yaxis: {
                labels: {
                    style: { colors: '#64748b' },
                    formatter: function (val) {
                        return Math.round(val);
                    }
                }
            },
            grid: { borderColor: '#f1f5f9' },
            tooltip: {
                y: {
                    formatter: function (val) {
                        return val + " cotizaciones";
                    }
                }
            }
        };

        var chartPrioridad = new ApexCharts(document.querySelector("#chart-prioridad"), optionsPrioridad);
        chartPrioridad.render();
    <?php endif; ?>
});
</script>