<style>
    .ventas-wrap { max-width: 1500px; margin: 0 auto; }
    .ventas-head { display:flex; justify-content:space-between; gap:16px; align-items:center; flex-wrap:wrap; margin-bottom:20px; }
    .ventas-tabs { display:flex; gap:8px; flex-wrap:wrap; margin:16px 0; }
    .ventas-tab { text-decoration:none; padding:9px 14px; border:1px solid #cbd5e1; border-radius:20px; color:#475569; background:#fff; font-size:12px; font-weight:700; }
    .ventas-tab.active { color:#fff; background:#103487; border-color:#103487; }
    .ventas-card { background:#fff; border:1px solid #e2e8f0; border-radius:12px; box-shadow:0 2px 8px rgba(15,23,42,.05); overflow:hidden; }
    .ventas-table { width:100%; border-collapse:collapse; }
    .ventas-table th { background:#f8fafc; color:#475569; font-size:11px; text-transform:uppercase; letter-spacing:.04em; text-align:left; padding:12px; border-bottom:1px solid #e2e8f0; }
    .ventas-table td { padding:13px 12px; border-bottom:1px solid #f1f5f9; font-size:12.5px; vertical-align:middle; }
    .estado-venta { display:inline-flex; gap:5px; align-items:center; padding:4px 9px; border-radius:20px; font-size:11px; font-weight:800; }
    .estado-pagado { background:#dcfce7; color:#15803d; border:1px solid #bbf7d0; }
    .estado-parcial { background:#fef3c7; color:#b45309; border:1px solid #fde68a; }
    .estado-pendiente { background:#f1f5f9; color:#475569; border:1px solid #cbd5e1; }
    .btn-venta { border:0; border-radius:7px; padding:7px 11px; cursor:pointer; font-weight:700; font-size:11.5px; text-decoration:none; display:inline-flex; gap:5px; align-items:center; }
    .btn-cobrar { background:#103487; color:#fff; }
    .btn-factura { background:#eef2ff; color:#3730a3; border:1px solid #c7d2fe; }
    .venta-modal { display:none; position:fixed; z-index:10020; inset:0; background:rgba(15,23,42,.56); overflow:auto; }
    .venta-modal-card { background:#fff; width:min(680px,94%); margin:4vh auto; border-radius:14px; padding:24px; box-shadow:0 24px 60px rgba(15,23,42,.3); }
    .venta-grid { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
    .venta-field label { display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:5px; }
    .venta-field input,.venta-field select { box-sizing:border-box; width:100%; padding:9px 11px; border:1px solid #cbd5e1; border-radius:7px; }
    @media(max-width:700px){ .venta-grid{grid-template-columns:1fr;} }
</style>

<div class="ventas-wrap">
    <div class="ventas-head">
        <div>
            <h2 style="margin:0;color:#0f172a;font-family:'Outfit';"><i class="fa-solid fa-chart-line" style="color:#103487;"></i> Ventas, Facturación y Cobro</h2>
            <p style="margin:5px 0 0;color:#64748b;font-size:13px;">Gestión comercial de órdenes de servicio, facturas, cobros y saldos.</p>
        </div>
        <form method="GET" action="/Cycsa/publico/ventas" style="display:flex;gap:8px;">
            <input type="hidden" name="estado" value="<?= htmlspecialchars($estado) ?>">
            <input name="q" value="<?= htmlspecialchars($busqueda) ?>" placeholder="O/S, cliente o factura..." style="padding:9px 12px;border:1px solid #cbd5e1;border-radius:7px;min-width:240px;">
            <button class="btn-venta btn-cobrar"><i class="fa-solid fa-search"></i> Buscar</button>
        </form>
    </div>

    <?php if (!empty($exito)): ?><div class="alert alert-success"><?= htmlspecialchars($exito) ?></div><?php endif; ?>
    <?php if (!empty($error)): ?><div class="alert alert-danger"><?= htmlspecialchars($error) ?></div><?php endif; ?>

    <div class="ventas-tabs">
        <?php foreach (['pendientes'=>'Pendientes','parciales'=>'Cobros parciales','pagadas'=>'Pagadas','todas'=>'Todas'] as $clave => $etiqueta): ?>
            <a class="ventas-tab <?= $estado === $clave ? 'active' : '' ?>" href="/Cycsa/publico/ventas?estado=<?= $clave ?>">
                <?= $etiqueta ?> <span>(<?= (int)($conteos[$clave] ?? 0) ?>)</span>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="ventas-card" style="overflow-x:auto;">
        <table class="ventas-table">
            <thead><tr><th>Orden / Factura</th><th>Cliente / Proyecto</th><th>Monto</th><th>Saldo</th><th>Estado</th><th style="text-align:right;">Acciones</th></tr></thead>
            <tbody>
            <?php foreach ($ordenes as $orden):
                $pagada = $orden['estado_pago'] === 'Pagado' && (float)$orden['saldo'] <= .01;
                $parcial = $orden['estado_pago'] === 'Parcial';
            ?>
                <tr>
                    <td><strong style="color:#103487;font-family:monospace;"><?= htmlspecialchars($orden['codigo_os']) ?></strong><br><span style="color:#64748b;font-family:monospace;"><?= htmlspecialchars($orden['factura_numero']) ?></span></td>
                    <td><strong><?= htmlspecialchars($orden['cliente_nombre']) ?></strong><br><span style="color:#64748b;"><?= htmlspecialchars($orden['nombre_proyecto'] ?? '') ?></span></td>
                    <td><strong>C$ <?= number_format((float)$orden['monto_factura'], 2) ?></strong></td>
                    <td style="color:<?= (float)$orden['saldo'] > 0 ? '#b91c1c' : '#15803d' ?>;font-weight:800;">C$ <?= number_format((float)$orden['saldo'], 2) ?></td>
                    <td><span class="estado-venta <?= $pagada ? 'estado-pagado' : ($parcial ? 'estado-parcial' : 'estado-pendiente') ?>"><i class="fa-solid <?= $pagada ? 'fa-circle-check' : 'fa-clock' ?>"></i><?= htmlspecialchars($orden['estado_pago']) ?></span></td>
                    <td style="text-align:right;white-space:nowrap;">
                        <a class="btn-venta btn-factura" target="_blank" href="/Cycsa/publico/ventas/imprimir-factura?id_os=<?= (int)$orden['id'] ?>"><i class="fa-solid fa-print"></i> Factura</a>
                        <?php if (!$pagada): ?>
                            <button class="btn-venta btn-cobrar" type="button" onclick='abrirVenta(<?= json_encode([
                                'id'=>(int)$orden['id'], 'os'=>$orden['codigo_os'], 'factura'=>$orden['factura_numero'],
                                'saldo'=>(float)$orden['saldo'], 'cliente'=>$orden['cliente_nombre']
                            ], JSON_HEX_APOS|JSON_HEX_QUOT) ?>)'><i class="fa-solid fa-cash-register"></i> <?= $parcial ? 'Registrar abono' : 'Facturar / cobrar' ?></button>
                        <?php endif; ?>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($ordenes)): ?><tr><td colspan="6" style="text-align:center;padding:38px;color:#64748b;">No hay órdenes en esta bandeja comercial.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<div id="modalVenta" class="venta-modal">
    <div class="venta-modal-card">
        <div style="display:flex;justify-content:space-between;align-items:center;border-bottom:1px solid #e2e8f0;padding-bottom:12px;margin-bottom:18px;">
            <div><h3 style="margin:0;color:#103487;">Facturación y cobro</h3><small id="ventaResumen" style="color:#64748b;"></small></div>
            <button type="button" onclick="cerrarVenta()" style="font-size:25px;border:0;background:none;cursor:pointer;">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/ventas/procesar-facturacion">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '') ?>">
            <input type="hidden" name="id_os" id="ventaIdOs">
            <div class="venta-grid">
                <div class="venta-field"><label>Número de factura</label><input name="factura_numero" id="ventaFactura" required></div>
                <div class="venta-field"><label>Monto a registrar (C$)</label><input type="number" min="0.01" step="0.01" name="monto" id="ventaMonto" required></div>
                <div class="venta-field"><label>Método</label><select name="metodo_pago" id="ventaMetodo" onchange="cambiarMetodoVenta()"><option value="efectivo">Efectivo</option><option value="transferencia">Transferencia</option><option value="credito">Crédito</option></select></div>
                <div class="venta-field"><label>Fecha</label><input type="date" name="fecha" value="<?= date('Y-m-d') ?>" required></div>
                <div class="venta-field" id="ventaBanco" style="display:none;"><label>Cuenta bancaria receptora</label><select name="id_banco_cuenta" id="ventaBancoSelect"><option value="">Seleccione...</option><?php foreach ($bancos as $banco): ?><option value="<?= (int)$banco['id'] ?>"><?= htmlspecialchars($banco['banco_nombre'].' - '.$banco['numero_cuenta'].' ('.$banco['moneda'].')') ?></option><?php endforeach; ?></select></div>
                <div class="venta-field" id="ventaReferencia" style="display:none;"><label>Referencia bancaria</label><input name="referencia"></div>
                <div class="venta-field" id="ventaCredito" style="display:none;"><label>Plazo de crédito (días)</label><input type="number" min="1" max="180" name="dias_credito" value="30"></div>
            </div>
            <div style="display:flex;justify-content:flex-end;gap:10px;margin-top:22px;border-top:1px solid #e2e8f0;padding-top:16px;">
                <button type="button" class="btn-venta" onclick="cerrarVenta()">Cancelar</button>
                <button type="submit" class="btn-venta btn-cobrar"><i class="fa-solid fa-check"></i> Procesar en Ventas</button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirVenta(datos) {
    document.getElementById('ventaIdOs').value = datos.id;
    document.getElementById('ventaFactura').value = datos.factura;
    document.getElementById('ventaMonto').value = Number(datos.saldo || 0).toFixed(2);
    document.getElementById('ventaResumen').textContent = datos.os + ' · ' + datos.cliente;
    document.getElementById('ventaMetodo').value = 'efectivo';
    cambiarMetodoVenta();
    document.getElementById('modalVenta').style.display = 'block';
}
function cerrarVenta(){ document.getElementById('modalVenta').style.display = 'none'; }
function cambiarMetodoVenta(){
    const metodo = document.getElementById('ventaMetodo').value;
    document.getElementById('ventaBanco').style.display = metodo === 'transferencia' ? 'block' : 'none';
    document.getElementById('ventaReferencia').style.display = metodo === 'transferencia' ? 'block' : 'none';
    document.getElementById('ventaCredito').style.display = metodo === 'credito' ? 'block' : 'none';
    document.getElementById('ventaBancoSelect').required = metodo === 'transferencia';
}
</script>
