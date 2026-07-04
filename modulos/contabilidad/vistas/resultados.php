<?php
// Estado de Resultados View

$ingresos = [];
$costos = [];
$gastos = [];

$totalIngresos = 0.0;
$totalCostos = 0.0;
$totalGastos = 0.0;

foreach ($saldos as $s) {
    // Only display accounts that have a non-zero balance OR are major accounts
    if ($s['saldo'] == 0 && $s['tipo'] === 'DETALLE') {
        continue;
    }
    
    $cat = $s['categoria'];
    $code = $s['codigo'];
    
    if ($cat === 'INGRESO') {
        $ingresos[] = $s;
        if ($s['id_padre'] === null) {
            $totalIngresos += $s['saldo'];
        }
    } elseif ($cat === 'EGRESO') {
        // If code starts with '5', it is Costos
        if (strpos($code, '5') === 0) {
            $costos[] = $s;
            if ($s['id_padre'] === null) {
                $totalCostos += $s['saldo'];
            }
        } else {
            // Else it is Gastos (e.g. 6 or 8)
            $gastos[] = $s;
            if ($s['id_padre'] === null) {
                $totalGastos += $s['saldo'];
            }
        }
    }
}

// Calculate Net Income / Utility
$utilidadBruta = $totalIngresos - $totalCostos;
$utilidadNeta = $utilidadBruta - $totalGastos;
?>
<style>
    .seccion-resultados { margin-bottom: 25px; }
    .titulo-seccion-res { background-color: #f8fafc; color: #0f172a; padding: 8px 12px; font-family: 'Outfit', sans-serif; font-size: 14.5px; font-weight: 700; border-bottom: 2px solid #cbd5e1; text-transform: uppercase; margin-bottom: 10px; }
    
    .fila-cuenta { display: flex; justify-content: space-between; padding: 7px 12px; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; }
    .fila-cuenta-mayor { font-weight: 700; color: #0f172a; }
    .fila-cuenta-detalle { color: #475569; padding-left: 30px; font-size: 13px; }
    
    .fila-total-seccion { display: flex; justify-content: space-between; padding: 10px 12px; font-weight: 800; color: #0f172a; font-size: 14px; border-top: 1px solid #94a3b8; border-bottom: 1.5px solid #0f172a; margin: 5px 0 15px 0; }
    .fila-subtotal-res { display: flex; justify-content: space-between; padding: 12px 15px; background-color: #f1f5f9; font-weight: 800; color: #0f172a; border-radius: 6px; font-size: 14.5px; margin: 15px 0; border: 1px solid #e2e8f0; }

    @media print {
        header, .sidebar, .tabs-container, form, .btn-print-bal { display: none !important; }
        .main-content { margin-left: 0 !important; padding: 0 !important; width: 100% !important; }
        body { background: white !important; color: black !important; }
        .hoja-membretada { border: none !important; box-shadow: none !important; padding: 0 !important; }
    }
</style>

<div class="hoja-membretada" style="background: white; padding: 35px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); max-width: 900px; margin: 0 auto;">
    
    <!-- Filtros y Cabecera de Control (No se imprimen) -->
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #e2e8f0; padding-bottom: 15px; margin-bottom: 25px;" class="btn-print-bal">
        <form method="GET" action="/Cycsa/publico/contabilidad/resultados" style="display: flex; gap: 10px; align-items: center; margin: 0; flex-wrap: wrap;">
            <label style="font-size: 13px; font-weight: 600; color: #475569;">Rango de Fechas:</label>
            <input type="date" name="fecha_desde" value="<?= htmlspecialchars($fechaDesde, ENT_QUOTES, 'UTF-8') ?>" class="form-control" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
            <label style="font-size: 13px; font-weight: 600; color: #475569;">Al:</label>
            <input type="date" name="fecha_hasta" value="<?= htmlspecialchars($fechaHasta, ENT_QUOTES, 'UTF-8') ?>" class="form-control" style="padding: 6px 12px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13px;">
            <button type="submit" style="background: var(--cycsa-azul); color: white; border: none; padding: 7px 15px; border-radius: 6px; cursor: pointer; font-weight: 600; font-size: 13px;">Generar Reporte</button>
        </form>

        <button onclick="window.print()" style="background: #f1f5f9; border: 1px solid #cbd5e1; padding: 7px 15px; border-radius: 6px; cursor: pointer; font-weight: 600; color: #475569; display: flex; align-items: center; gap: 6px; font-size: 13px;">
            <i class="fa-solid fa-print"></i> Imprimir Reporte
        </button>
    </div>

    <!-- Menú de pestañas secundarias -->
    <div class="tabs-container" style="display: flex; gap: 8px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="/Cycsa/publico/contabilidad/cuentas" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-list-ol"></i> Catálogo</a>
        <a href="/Cycsa/publico/contabilidad/diario" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-book"></i> Registro Diario</a>
        <a href="/Cycsa/publico/contabilidad/cxc" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-file-invoice-dollar"></i> Cobros (CXC)</a>
        <a href="/Cycsa/publico/contabilidad/cxp" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-credit-card"></i> Pagos (CXP)</a>
        <a href="/Cycsa/publico/contabilidad/bancos" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-building-columns"></i> Bancos</a>
        <a href="/Cycsa/publico/contabilidad/balance" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-scale-balanced"></i> Balance General</a>
        <a href="/Cycsa/publico/contabilidad/resultados" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13.5px; background-color: var(--cycsa-azul); color: white;"><i class="fa-solid fa-chart-line"></i> Estado de Resultados</a>
    </div>

    <!-- Encabezado Membretado Oficial -->
    <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 3px double #0f172a; padding-bottom: 20px; margin-bottom: 20px;">
        <div style="display: flex; align-items: center; gap: 15px;">
            <img src="/Cycsa/publico/img/logo_cycsa.jpg" alt="Logo CYCSA" style="height: 65px; border-radius: 4px;">
            <div>
                <h1 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; color: #0f172a; letter-spacing: 0.5px;">CYCSA S.A.</h1>
                <p style="margin: 3px 0 0 0; font-size: 12px; color: #475569; font-weight: 500;">Construcción y Consultoría de Laboratorios S.A.</p>
                <p style="margin: 2px 0 0 0; font-size: 11px; color: #64748b;">Managua, Nicaragua | RUC: J0310000123456</p>
            </div>
        </div>
        <div style="text-align: right;">
            <h2 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 17px; font-weight: 700; color: var(--cycsa-azul);">ESTADO DE RESULTADOS</h2>
            <p style="margin: 5px 0 0 0; font-size: 11px; color: #475569; font-weight: 600; text-transform: uppercase;">Del <?= date('d/m/Y', strtotime($fechaDesde)) ?> al <?= date('d/m/Y', strtotime($fechaHasta)) ?></p>
            <span style="font-size: 10px; background-color: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-weight: 600; margin-top: 5px; display: inline-block;">Expresado en Córdobas (C$)</span>
        </div>
    </div>

    <!-- SECCIÓN INGRESOS -->
    <div class="seccion-resultados">
        <div class="titulo-seccion-res">Ingresos de Operación</div>
        <?php if (empty($ingresos)): ?>
            <p style="padding-left: 12px; color: #94a3b8; font-style: italic; font-size: 13px;">No hay ingresos registrados en este rango de fechas.</p>
        <?php else: ?>
            <?php foreach ($ingresos as $ing): ?>
                <div class="fila-cuenta <?= $ing['tipo'] === 'MAYOR' ? 'fila-cuenta-mayor' : 'fila-cuenta-detalle' ?>">
                    <span><?= htmlspecialchars($ing['codigo'] . ' - ' . $ing['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span style="font-family: monospace; font-weight: 600;">C$ <?= number_format($ing['saldo'], 2) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="fila-total-seccion">
            <span>INGRESOS OPERATIVOS BRUTOS</span>
            <span style="font-family: monospace;">C$ <?= number_format($totalIngresos, 2) ?></span>
        </div>
    </div>

    <!-- SECCIÓN COSTOS -->
    <div class="seccion-resultados">
        <div class="titulo-seccion-res">Costos de Operación / Producción</div>
        <?php if (empty($costos)): ?>
            <p style="padding-left: 12px; color: #94a3b8; font-style: italic; font-size: 13px;">No hay costos de operación registrados.</p>
        <?php else: ?>
            <?php foreach ($costos as $cos): ?>
                <div class="fila-cuenta <?= $cos['tipo'] === 'MAYOR' ? 'fila-cuenta-mayor' : 'fila-cuenta-detalle' ?>">
                    <span><?= htmlspecialchars($cos['codigo'] . ' - ' . $cos['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span style="font-family: monospace; font-weight: 600;">C$ <?= number_format($cos['saldo'], 2) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="fila-total-seccion">
            <span>TOTAL COSTOS DE OPERACIÓN</span>
            <span style="font-family: monospace;">C$ <?= number_format($totalCostos, 2) ?></span>
        </div>
    </div>

    <!-- SUBTOTAL: UTILIDAD BRUTA -->
    <div class="fila-subtotal-res">
        <span>UTILIDAD BRUTA</span>
        <span style="font-family: monospace; color: <?= $utilidadBruta >= 0 ? '#0f172a' : '#ef4444' ?>;">C$ <?= number_format($utilidadBruta, 2) ?></span>
    </div>

    <!-- SECCIÓN GASTOS -->
    <div class="seccion-resultados">
        <div class="titulo-seccion-res">Gastos de Administración y Generales</div>
        <?php if (empty($gastos)): ?>
            <p style="padding-left: 12px; color: #94a3b8; font-style: italic; font-size: 13px;">No hay gastos administrativos registrados.</p>
        <?php else: ?>
            <?php foreach ($gastos as $gas): ?>
                <div class="fila-cuenta <?= $gas['tipo'] === 'MAYOR' ? 'fila-cuenta-mayor' : 'fila-cuenta-detalle' ?>">
                    <span><?= htmlspecialchars($gas['codigo'] . ' - ' . $gas['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span style="font-family: monospace; font-weight: 600;">C$ <?= number_format($gas['saldo'], 2) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="fila-total-seccion">
            <span>TOTAL GASTOS ADMINISTRATIVOS Y GENERALES</span>
            <span style="font-family: monospace;">C$ <?= number_format($totalGastos, 2) ?></span>
        </div>
    </div>

    <!-- UTILIDAD NETA / EJERCICIO -->
    <div class="fila-subtotal-res" style="background-color: #0f172a; color: white; border-color: #0f172a; margin-top: 30px;">
        <span style="display: flex; align-items: center; gap: 8px;"><i class="fa-solid fa-scale-balanced"></i> UTILIDAD O PÉRDIDA NETA DEL EJERCICIO</span>
        <span style="font-family: monospace; font-size: 16px; font-weight: 900; color: <?= $utilidadNeta >= 0 ? '#10b981' : '#f87171' ?>;">C$ <?= number_format($utilidadNeta, 2) ?></span>
    </div>

    <!-- Firmas Autorizadas -->
    <div style="margin-top: 80px; display: grid; grid-template-columns: 1fr 1fr; gap: 80px; text-align: center;">
        <div>
            <div style="border-top: 1px solid #64748b; width: 80%; margin: 0 auto; padding-top: 8px;">
                <strong style="font-size: 13px; color: #1e293b;">Elaborado por:</strong>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">Responsable de Contabilidad</p>
            </div>
        </div>
        <div>
            <div style="border-top: 1px solid #64748b; width: 80%; margin: 0 auto; padding-top: 8px;">
                <strong style="font-size: 13px; color: #1e293b;">Autorizado por:</strong>
                <p style="margin: 4px 0 0 0; font-size: 12px; color: #64748b;">Gerencia General / Dirección</p>
            </div>
        </div>
    </div>

</div>
