<?php
// Cuentas por Pagar (CXP) View
// Calcular totales
$totalPendiente = 0;
$totalPagado = 0;
$totalRegistrado = 0;
foreach ($cxpList as $item) {
    $totalPendiente += $item['saldo'];
    $totalPagado += ($item['monto'] - $item['saldo']);
    $totalRegistrado += $item['monto'];
}
?>
<style>
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .tabla-cycsa th { background-color: #f8f9fa; color: #6c757d; padding: 12px 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; text-transform: uppercase; font-size: 12px; }
    .tabla-cycsa td { padding: 12px 15px; border-bottom: 1px solid #e9ecef; vertical-align: middle; color: #333; }
    .tabla-cycsa tbody tr:hover { background-color: #f8f9fa; }
    
    .badge-estado { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge-pendiente { background-color: #fef3c7; color: #d97706; }
    .badge-parcial { background-color: #dbeafe; color: #2563eb; }
    .badge-pagado { background-color: #dcfce7; color: #166534; }
    .badge-vencido { background-color: #fee2e2; color: #dc2626; }
    
    .kpi-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px; }
    .kpi-card { background: white; padding: 20px; border-radius: 10px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 15px; box-shadow: 0 1px 3px rgba(0,0,0,0.02); }
    .kpi-icon { width: 48px; height: 48px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
    
    .modal-premium { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .modal-premium-content { background-color: #fff; margin: 5% auto; padding: 30px; border: 1px solid #e2e8f0; width: 45%; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); animation: slideDown 0.3s ease; }
    
    @keyframes slideDown {
        from { transform: translateY(-30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .form-group { margin-bottom: 16px; display: flex; flex-direction: column; gap: 6px; }
    .form-control { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; }
    .form-control:focus { border-color: var(--cycsa-azul); }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
    
    .btn-accion { border: none; background: none; cursor: pointer; padding: 6px 12px; border-radius: 4px; font-size: 14px; transition: all 0.2s; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; font-weight: 500; }
    .btn-abono { background-color: #fff1f2; color: #9f1239; border: 1px solid #fecdd3; }
    .btn-abono:hover { background-color: #ffe4e6; }
    
    .alert { padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .alert-exito { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .alert-error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }
</style>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">

    <!-- Alertas -->
    <?php if (!empty($exito)): ?>
        <div class="alert alert-exito">
            <i class="fa-solid fa-circle-check"></i> <?= htmlspecialchars($exito, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="alert alert-error">
            <i class="fa-solid fa-circle-xmark"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
        </div>
    <?php endif; ?>

    <div class="header-flex" style="margin-bottom: 20px;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;">Cuentas por Pagar (CXP)</h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 14px;">Administración de facturas y obligaciones pendientes de pago con proveedores.</p>
        </div>
        
        <div class="actions-flex">
            <!-- Buscador -->
            <form method="GET" action="/Cycsa/publico/contabilidad/cxp" style="display: flex;">
                <input type="text" name="q" placeholder="Buscar por proveedor o factura..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" style="padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px 0 0 6px; font-family: 'Inter', sans-serif; width: 250px; outline: none; font-size: 14px;">
                <button type="submit" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-left: none; padding: 10px 18px; border-radius: 0 6px 6px 0; cursor: pointer; color: #475569; font-size: 14px;"><i class="fa-solid fa-magnifying-glass"></i></button>
                <?php if(!empty($busqueda)): ?>
                    <a href="/Cycsa/publico/contabilidad/cxp" style="margin-left: 10px; color: var(--cycsa-rojo); text-decoration: none; padding-top: 10px; font-size: 14px; font-weight: 500;"><i class="fa-solid fa-xmark"></i> Limpiar</a>
                <?php endif; ?>
            </form>

            <?php if (tienePermiso('contabilidad', 'crear_editar')): ?>
            <button id="btnAbrirModal" style="background: var(--cycsa-azul); color: white; border: none; padding: 11px 22px; border-radius: 6px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: background 0.3s; margin-left: 10px; font-size: 14px;">
                <i class="fa-solid fa-plus"></i> Registrar CXP
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Menú de pestañas secundarias para navegar en contabilidad -->
    <div class="tabs-container" style="display: flex; gap: 8px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="/Cycsa/publico/contabilidad/cuentas" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-list-ol"></i> Catálogo</a>
        <a href="/Cycsa/publico/contabilidad/diario" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-book"></i> Registro Diario</a>
        <a href="/Cycsa/publico/contabilidad/cxc" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-file-invoice-dollar"></i> Cobros (CXC)</a>
        <a href="/Cycsa/publico/contabilidad/cxp" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13.5px; background-color: var(--cycsa-azul); color: white;"><i class="fa-solid fa-credit-card"></i> Pagos (CXP)</a>
        <a href="/Cycsa/publico/contabilidad/bancos" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-building-columns"></i> Bancos</a>
        <a href="/Cycsa/publico/contabilidad/balance" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-scale-balanced"></i> Balance General</a>
        <a href="/Cycsa/publico/contabilidad/resultados" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-chart-line"></i> Estado de Resultados</a>
    </div>

    <!-- KPIs -->
    <div class="kpi-container">
        <div class="kpi-card">
            <div class="kpi-icon" style="background-color: #fce7f3; color: #be185d;"><i class="fa-solid fa-file-contract"></i></div>
            <div>
                <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Registrado</div>
                <div style="font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 4px;">C$ <?= number_format($totalRegistrado, 2, '.', ',') ?></div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background-color: #fee2e2; color: #b91c1c;"><i class="fa-solid fa-triangle-exclamation"></i></div>
            <div>
                <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Saldo por Pagar</div>
                <div style="font-size: 20px; font-weight: 700; color: #b91c1c; margin-top: 4px;">C$ <?= number_format($totalPendiente, 2, '.', ',') ?></div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background-color: #dcfce7; color: #15803d;"><i class="fa-solid fa-handshake"></i></div>
            <div>
                <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Monto Pagado</div>
                <div style="font-size: 20px; font-weight: 700; color: #15803d; margin-top: 4px;">C$ <?= number_format($totalPagado, 2, '.', ',') ?></div>
            </div>
        </div>
    </div>

    <!-- Tabla de Cuentas por Pagar -->
    <div style="overflow-x: auto;">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th>Proveedor</th>
                    <th>Factura N°</th>
                    <th>Cuenta Relacionada</th>
                    <th>Monto Original</th>
                    <th>Saldo Pendiente</th>
                    <th>Estado</th>
                    <th>Emisión</th>
                    <th>Vencimiento</th>
                    <?php if (tienePermiso('contabilidad', 'crear_editar')): ?>
                    <th style="text-align: right;">Acciones</th>
                    <?php endif; ?>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cxpList as $cxp): 
                    $estClass = 'badge-pendiente';
                    if ($cxp['estado'] === 'Parcial') $estClass = 'badge-parcial';
                    elseif ($cxp['estado'] === 'Pagado') $estClass = 'badge-pagado';
                    elseif ($cxp['estado'] === 'Vencido') $estClass = 'badge-vencido';
                ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($cxp['proveedor_nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-family: monospace; font-size: 13.5px;"><?= htmlspecialchars($cxp['factura_numero'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size: 13px; color: #475569;">
                        <?= $cxp['cuenta_codigo'] ? htmlspecialchars($cxp['cuenta_codigo'] . ' - ' . $cxp['cuenta_nombre'], ENT_QUOTES, 'UTF-8') : '—' ?>
                    </td>
                    <td style="font-weight: 600;">C$ <?= number_format($cxp['monto'], 2, '.', ',') ?></td>
                    <td style="font-weight: 600; color: <?= $cxp['saldo'] > 0 ? '#b91c1c' : '#15803d' ?>;">
                        C$ <?= number_format($cxp['saldo'], 2, '.', ',') ?>
                    </td>
                    <td>
                        <span class="badge-estado <?= $estClass ?>"><?= htmlspecialchars($cxp['estado'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td style="font-size: 13px; color: #64748b;"><?= htmlspecialchars($cxp['fecha_emision'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size: 13px; color: #64748b;"><?= htmlspecialchars($cxp['fecha_vencimiento'] ?? 'Sin Venc.', ENT_QUOTES, 'UTF-8') ?></td>
                    <?php if (tienePermiso('contabilidad', 'crear_editar')): ?>
                    <td style="text-align: right;">
                        <?php if ($cxp['saldo'] > 0): ?>
                            <button class="btn-accion btn-abono" onclick="abrirAbonoModal(<?= $cxp['id'] ?>, '<?= htmlspecialchars($cxp['factura_numero'], ENT_QUOTES, 'UTF-8') ?>', <?= $cxp['saldo'] ?>)" title="Pagar Factura">
                                <i class="fa-solid fa-money-bill-transfer"></i> Pagar
                            </button>
                        <?php else: ?>
                            <span style="color: #cbd5e1; font-size: 12px; font-style: italic;">Pagado</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($cxpList)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px; color: #64748b;">No se encontraron cuentas por pagar registradas.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVA CXP -->
<div id="modalCxp" class="modal-premium">
    <div class="modal-premium-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700;">Registrar Cuenta por Pagar</h3>
            <button id="btnCerrarModal" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/contabilidad/guardar-cxp">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Proveedor / Acreedor</label>
                <input type="text" name="proveedor_nombre" required placeholder="Ej: Holcim de Nicaragua S.A." class="form-control">
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Número de Factura / Doc</label>
                    <input type="text" name="factura_numero" required placeholder="Ej: F-9584" class="form-control">
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Monto Original (C$)</label>
                    <input type="number" name="monto" step="0.01" min="0.01" required placeholder="0.00" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Cuenta Contable Asociada</label>
                <input type="hidden" name="id_cuenta_contable" id="hidden_id_cuenta_contable" value="">
                <div style="display: flex; gap: 8px;">
                    <input type="text" id="display_cuenta_contable" class="form-control" placeholder="Ninguna cuenta seleccionada" readonly style="background:#f1f5f9; cursor:pointer;" onclick="abrirModalSeleccionCuenta()">
                    <button type="button" class="btn-premium-azul" style="padding: 0 16px; font-size: 13px; width: auto; font-weight: 600; cursor: pointer; white-space: nowrap; border-radius: 6px;" onclick="abrirModalSeleccionCuenta()">
                        <i class="fa-solid fa-magnifying-glass"></i> Buscar
                    </button>
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha de Emisión</label>
                    <input type="date" name="fecha_emision" required class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha de Vencimiento (Opcional)</label>
                    <input type="date" name="fecha_vencimiento" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Notas / Concepto</label>
                <textarea name="notas" rows="3" placeholder="Detalles de la compra de materiales o servicio contratado..." class="form-control"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px;">
                <button type="button" id="btnCancelar" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b;">Cancelar</button>
                <button type="submit" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600; padding: 10px 24px;">Guardar CXP</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL SELECCIONAR CUENTA CONTABLE -->
<div id="modalSeleccionCuenta" class="modal-premium" style="display: none; z-index: 2000;">
    <div class="modal-premium-content" style="max-width: 750px; margin-top: 50px;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700;">Seleccionar Cuenta Contable</h3>
            <button type="button" onclick="cerrarModalSeleccionCuenta()" class="btn-cerrar" style="border:none; background:none; font-size:22px; cursor:pointer; color:#64748b;">&times;</button>
        </div>
        
        <div style="margin-bottom: 15px;">
            <input type="text" id="buscar_cuenta_input" class="form-control" placeholder="Buscar cuenta por código o nombre..." style="width: 100%; box-sizing: border-box; padding: 12px 15px; font-size: 14px;">
        </div>
        
        <div style="max-height: 400px; overflow-y: auto; border: 1px solid #cbd5e1; border-radius: 6px; background: white;">
            <table class="tabla-cycsa" style="margin: 0; font-size: 13.5px; width: 100%;">
                <thead>
                    <tr style="background: #f1f5f9; border-bottom: 2px solid #cbd5e1;">
                        <th style="padding: 12px; text-align: left; color: #475569; font-weight: 700;">Código de Cuenta</th>
                        <th style="padding: 12px; text-align: left; color: #475569; font-weight: 700;">Nombre de la Cuenta</th>
                    </tr>
                </thead>
                <tbody id="lista_cuentas_tbody">
                    <!-- Filas filtradas dinámicamente -->
                </tbody>
            </table>
        </div>
        
        <div style="text-align: right; margin-top: 15px;">
            <button type="button" class="form-control" style="background: #f1f5f9; border: 1px solid #cbd5e1; width: auto; display: inline-block; padding: 8px 16px; cursor: pointer; color: #475569; font-weight: 600;" onclick="cerrarModalSeleccionCuenta()">Cerrar</button>
        </div>
    </div>
</div>

<!-- MODAL REGISTRAR PAGO (EGRESO/CHEQUE) -->
<div id="modalAbono" class="modal-premium">
    <div class="modal-premium-content" style="width: 35%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700;">Registrar Erogación / Pago</h3>
            <button onclick="cerrarAbonoModal()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/contabilidad/pagar-cxp">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_cxp" id="abono_id_cxp">
            
            <div style="background-color: #f8fafc; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #e2e8f0; font-size: 14px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="color: #64748b; font-weight: 500;">Factura Proveedor:</span>
                    <strong id="abono_factura_txt" style="color: #0f172a;">—</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b; font-weight: 500;">Saldo Pendiente:</span>
                    <strong id="abono_saldo_txt" style="color: #b91c1c;">C$ 0.00</strong>
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Monto a Pagar (C$)</label>
                <input type="number" name="monto_pago" id="abono_monto_pago" step="0.01" min="0.01" required placeholder="0.00" class="form-control">
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Retirar de Cuenta Bancaria</label>
                <select name="id_banco_cuenta" required class="form-control" style="background-color: white;">
                    <option value="">-- Seleccionar Banco --</option>
                    <?php foreach ($bancos as $bco): ?>
                        <option value="<?= $bco['id'] ?>"><?= htmlspecialchars($bco['banco_nombre'] . ' - Cta: ' . $bco['numero_cuenta'] . ' (' . $bco['moneda'] . ') - Saldo: ' . number_format($bco['saldo_actual'], 2, '.', ','), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Instrumento de Pago / Operación</label>
                <select name="tipo_transaccion_pago" required class="form-control" style="background-color: white;">
                    <option value="RETIRAR">Transferencia / Débito Bancario</option>
                    <option value="CHEQUE">Cheque de Chequera</option>
                </select>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Referencia / N° Cheque</label>
                    <input type="text" name="referencia" required placeholder="Ej: CHQ-2051 o Minuta-5" class="form-control">
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha de Pago</label>
                    <input type="date" name="fecha_pago" required class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 15px;">
                <button type="button" onclick="cerrarAbonoModal()" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b;">Cancelar</button>
                <button type="submit" class="form-control" style="cursor: pointer; background: #9f1239; border: 1px solid #9f1239; color: white; font-weight: 600; padding: 10px 24px;">Aplicar Erogación</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalCxp = document.getElementById('modalCxp');
        const btnAbrir = document.getElementById('btnAbrirModal');
        const btnCerrar = document.getElementById('btnCerrarModal');
        const btnCancelar = document.getElementById('btnCancelar');
        
        if (btnAbrir) {
            btnAbrir.addEventListener('click', () => {
                modalCxp.style.display = 'block';
            });
        }
        
        const cerrarModal = () => {
            modalCxp.style.display = 'none';
        };
        
        if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
        if (btnCancelar) btnCancelar.addEventListener('click', cerrarModal);
        
        window.addEventListener('click', (e) => {
            if (e.target === modalCxp) {
                cerrarModal();
            }
        });

        // Searchable Account Autocomplete component
        window.cuentasContables = [
            <?php foreach ($cuentasDetalle as $cta): ?>
            { id: <?= $cta['id'] ?>, codigo: <?= json_encode($cta['codigo']) ?>, nombre: <?= json_encode($cta['nombre']) ?> },
            <?php endforeach; ?>
        ];

        window.abrirModalSeleccionCuenta = function() {
            document.getElementById('modalSeleccionCuenta').style.display = 'block';
            document.getElementById('buscar_cuenta_input').value = '';
            renderSeleccionCuentas('');
            setTimeout(() => {
                document.getElementById('buscar_cuenta_input').focus();
            }, 100);
        };

        window.cerrarModalSeleccionCuenta = function() {
            document.getElementById('modalSeleccionCuenta').style.display = 'none';
        };

        window.seleccionarCuenta = function(id, codigo, nombre) {
            document.getElementById('hidden_id_cuenta_contable').value = id;
            document.getElementById('display_cuenta_contable').value = codigo + ' - ' + nombre;
            cerrarModalSeleccionCuenta();
        };

        window.renderSeleccionCuentas = function(filtro = '') {
            const tbody = document.getElementById('lista_cuentas_tbody');
            tbody.innerHTML = '';
            const q = filtro.toLowerCase().trim();
            
            const filtered = cuentasContables.filter(c => 
                c.codigo.toLowerCase().includes(q) || 
                c.nombre.toLowerCase().includes(q)
            );
            
            if (filtered.length === 0) {
                tbody.innerHTML = `<tr><td colspan="2" style="text-align: center; padding: 20px; color: #64748b; font-style: italic;">No se encontraron cuentas contables</td></tr>`;
                return;
            }
            
            filtered.forEach(c => {
                const tr = document.createElement('tr');
                tr.style.cursor = 'pointer';
                tr.title = 'Haga clic para seleccionar esta cuenta';
                tr.addEventListener('mouseenter', () => {
                    tr.style.backgroundColor = '#f8fafc';
                });
                tr.addEventListener('mouseleave', () => {
                    tr.style.backgroundColor = '';
                });
                tr.addEventListener('click', () => {
                    seleccionarCuenta(c.id, c.codigo, c.nombre);
                });
                tr.innerHTML = `
                    <td style="padding: 12px; font-family: monospace; font-weight: 700; color: var(--cycsa-azul); font-size: 14px;">${c.codigo}</td>
                    <td style="padding: 12px; font-weight: 600; color: #334155; font-size: 14px;">${c.nombre}</td>
                `;
                tbody.appendChild(tr);
            });
        };

        document.getElementById('buscar_cuenta_input').addEventListener('input', (e) => {
            renderSeleccionCuentas(e.target.value);
        });
    });

    const abonoModal = document.getElementById('modalAbono');

    function abrirAbonoModal(idCxp, factura, saldo) {
        document.getElementById('abono_id_cxp').value = idCxp;
        document.getElementById('abono_factura_txt').innerText = factura;
        document.getElementById('abono_saldo_txt').innerText = 'C$ ' + Number(saldo).toLocaleString('es-NI', {minimumFractionDigits: 2});
        document.getElementById('abono_monto_pago').max = saldo;
        document.getElementById('abono_monto_pago').value = saldo;
        abonoModal.style.display = 'block';
    }

    function cerrarAbonoModal() {
        abonoModal.style.display = 'none';
    }
</script>
