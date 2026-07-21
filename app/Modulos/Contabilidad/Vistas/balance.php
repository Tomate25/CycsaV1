<?php
// Balance General View

// Separate categories
$activos = [];
$pasivos = [];
$capital = [];

$totalActivos = 0.0;
$totalPasivos = 0.0;
$totalCapital = 0.0;
$totalIngresos = 0.0;
$totalEgresos = 0.0;

foreach ($saldos as $s) {
    // Only display accounts that have a non-zero balance OR are major accounts
    if ($s['saldo'] == 0 && $s['tipo'] === 'DETALLE') {
        continue;
    }
    
    $cat = $s['categoria'];
    if ($cat === 'ACTIVO') {
        $activos[] = $s;
        if ($s['id_padre'] === null) {
            $totalActivos += $s['saldo'];
        }
    } elseif ($cat === 'PASIVO') {
        $pasivos[] = $s;
        if ($s['id_padre'] === null) {
            $totalPasivos += $s['saldo'];
        }
    } elseif ($cat === 'CAPITAL') {
        $capital[] = $s;
        if ($s['id_padre'] === null) {
            $totalCapital += $s['saldo'];
        }
    } elseif ($cat === 'INGRESO') {
        if ($s['id_padre'] === null) {
            $totalIngresos += $s['saldo'];
        }
    } elseif ($cat === 'EGRESO') {
        if ($s['id_padre'] === null) {
            $totalEgresos += $s['saldo'];
        }
    }
}

// Net utility of the period
$utilidadPeriodo = $totalIngresos - $totalEgresos;

$totalPasivoMasCapital = $totalPasivos + $totalCapital + $utilidadPeriodo;
$diferenciaCuadre = abs($totalActivos - $totalPasivoMasCapital);
$estaCuadrado = $diferenciaCuadre <= 0.05;
?>
<style>
    .seccion-balance { margin-bottom: 30px; }
    .titulo-seccion-bal { background-color: #f1f5f9; color: #1e293b; padding: 10px 15px; font-family: 'Outfit', sans-serif; font-size: 15px; font-weight: 700; border-radius: 6px; text-transform: uppercase; margin-bottom: 12px; }
    
    .fila-cuenta { display: flex; justify-content: space-between; padding: 8px 15px; border-bottom: 1px solid #f1f5f9; font-size: 13.5px; align-items: center; }
    .fila-cuenta-mayor { font-weight: 700; color: #0f172a; border-bottom: 1.5px solid #e2e8f0; font-size: 14px; background-color: #f8fafc; }
    .fila-cuenta-detalle { color: #475569; padding-left: 35px; font-size: 13px; }
    
    .fila-total-seccion { display: flex; justify-content: space-between; padding: 12px 15px; background-color: #e2e8f0; font-weight: 800; color: #0f172a; border-radius: 6px; font-size: 14.5px; margin-top: 10px; }
    
    .cuadre-status-box { padding: 15px; border-radius: 8px; font-weight: 600; font-size: 14px; margin-bottom: 25px; display: flex; align-items: center; gap: 10px; justify-content: center; }
    .cuadre-ok { background-color: #dcfce7; color: #14532d; border: 1px solid #bbf7d0; }
    .cuadre-fail { background-color: #fee2e2; color: #7f1d1d; border: 1px solid #fecaca; }

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
        <form method="GET" action="/Cycsa/publico/contabilidad/balance" style="display: flex; gap: 10px; align-items: center; margin: 0;">
            <label style="font-size: 13px; font-weight: 600; color: #475569;">Ver Balance al:</label>
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
        <a href="/Cycsa/publico/contabilidad/balance" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13.5px; background-color: var(--cycsa-azul); color: white;"><i class="fa-solid fa-scale-balanced"></i> Balance General</a>
        <a href="/Cycsa/publico/contabilidad/resultados" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-chart-line"></i> Estado de Resultados</a>
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
            <h2 style="margin: 0; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700; color: var(--cycsa-azul);">BALANCE GENERAL</h2>
            <p style="margin: 5px 0 0 0; font-size: 12px; color: #475569; font-weight: 600; text-transform: uppercase;">Al <?= date('d \d\e F \d\e Y', strtotime($fechaHasta)) ?></p>
            <span style="font-size: 10px; background-color: #f1f5f9; color: #475569; padding: 2px 6px; border-radius: 4px; font-weight: 600; margin-top: 5px; display: inline-block;">Expresado en Córdobas (C$)</span>
        </div>
    </div>

    <!-- Caja de Estado del Cuadre -->
    <div class="cuadre-status-box <?= $estaCuadrado ? 'cuadre-ok' : 'cuadre-fail' ?> btn-print-bal">
        <?php if ($estaCuadrado): ?>
            <i class="fa-solid fa-circle-check" style="font-size: 16px;"></i>
            <span>¡Balance Cuadrado con Éxito! Total Activos (C$ <?= number_format($totalActivos, 2) ?>) = Total Pasivo + Capital (C$ <?= number_format($totalPasivoMasCapital, 2) ?>)</span>
        <?php else: ?>
            <i class="fa-solid fa-triangle-exclamation" style="font-size: 16px;"></i>
            <span>Descuadre en Balance detectado. Diferencia: C$ <?= number_format($diferenciaCuadre, 2) ?>. (Activo: C$ <?= number_format($totalActivos, 2) ?> vs Pasivo+Capital: C$ <?= number_format($totalPasivoMasCapital, 2) ?>)</span>
        <?php endif; ?>
    </div>

    <!-- SECCIÓN ACTIVO -->
    <div class="seccion-balance">
        <div class="titulo-seccion-bal">Activos</div>
        <?php if (empty($activos)): ?>
            <p style="padding-left: 15px; color: #94a3b8; font-style: italic; font-size: 13px;">No hay cuentas de Activo registradas.</p>
        <?php else: ?>
            <?php foreach ($activos as $a): ?>
                <div class="fila-cuenta <?= $a['tipo'] === 'MAYOR' ? 'fila-cuenta-mayor' : 'fila-cuenta-detalle' ?>">
                    <span><?= htmlspecialchars($a['codigo'] . ' - ' . $a['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span style="font-family: monospace; font-weight: 700;">C$ <?= number_format($a['saldo'], 2) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="fila-total-seccion">
            <span>TOTAL ACTIVOS</span>
            <span style="font-family: monospace;">C$ <?= number_format($totalActivos, 2) ?></span>
        </div>
    </div>

    <!-- SECCIÓN PASIVO -->
    <div class="seccion-balance">
        <div class="titulo-seccion-bal">Pasivos</div>
        <?php if (empty($pasivos)): ?>
            <p style="padding-left: 15px; color: #94a3b8; font-style: italic; font-size: 13px;">No hay cuentas de Pasivo registradas.</p>
        <?php else: ?>
            <?php foreach ($pasivos as $p): ?>
                <div class="fila-cuenta <?= $p['tipo'] === 'MAYOR' ? 'fila-cuenta-mayor' : 'fila-cuenta-detalle' ?>">
                    <span><?= htmlspecialchars($p['codigo'] . ' - ' . $p['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span style="font-family: monospace; font-weight: 700;">C$ <?= number_format($p['saldo'], 2) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        <div class="fila-total-seccion">
            <span>TOTAL PASIVOS</span>
            <span style="font-family: monospace;">C$ <?= number_format($totalPasivos, 2) ?></span>
        </div>
    </div>

    <!-- SECCIÓN CAPITAL -->
    <div class="seccion-balance">
        <div class="titulo-seccion-bal">Capital / Patrimonio</div>
        <?php if (empty($capital)): ?>
            <p style="padding-left: 15px; color: #94a3b8; font-style: italic; font-size: 13px;">No hay cuentas de Capital registradas.</p>
        <?php else: ?>
            <?php foreach ($capital as $c): ?>
                <div class="fila-cuenta <?= $c['tipo'] === 'MAYOR' ? 'fila-cuenta-mayor' : 'fila-cuenta-detalle' ?>">
                    <span><?= htmlspecialchars($c['codigo'] . ' - ' . $c['nombre'], ENT_QUOTES, 'UTF-8') ?></span>
                    <span style="font-family: monospace; font-weight: 700;">C$ <?= number_format($c['saldo'], 2) ?></span>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>
        
        <!-- Fila de Utilidad del Periodo (Calculada del Estado de Resultados) -->
        <div class="fila-cuenta fila-cuenta-mayor" style="border-top: 1.5px solid #cbd5e1; background-color: #fef8f8;">
            <span style="color: #0369a1;"><i class="fa-solid fa-chart-line" style="margin-right: 6px;"></i> Utilidad / Pérdida Neta del Ejercicio</span>
            <span style="font-family: monospace; font-weight: 700; color: #0369a1;">C$ <?= number_format($utilidadPeriodo, 2) ?></span>
        </div>
        
        <div class="fila-total-seccion">
            <span>TOTAL CAPITAL Y PATRIMONIO</span>
            <span style="font-family: monospace;">C$ <?= number_format($totalCapital + $utilidadPeriodo, 2) ?></span>
        </div>
    </div>

    <!-- Resumen Comparativo de Doble Entrada -->
    <div style="margin-top: 40px; border-top: 3px double #0f172a; padding-top: 15px; display: flex; justify-content: space-between; font-family: 'Outfit'; font-size: 15px; font-weight: 800; color: #0f172a;">
        <div>TOTAL ACTIVOS: <span style="font-family: monospace; color: var(--cycsa-azul);">C$ <?= number_format($totalActivos, 2) ?></span></div>
        <div style="text-align: right;">TOTAL PASIVO + CAPITAL: <span style="font-family: monospace; color: #059669;">C$ <?= number_format($totalPasivoMasCapital, 2) ?></span></div>
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
