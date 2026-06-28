<?php
// Cuentas por Cobrar (CXC) View
// Calcular totales
$totalPendiente = 0;
$totalCobrado = 0;
$totalRegistrado = 0;
foreach ($cxcList as $item) {
    $totalPendiente += $item['saldo'];
    $totalCobrado += ($item['monto'] - $item['saldo']);
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
    .btn-abono { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .btn-abono:hover { background-color: #dcfce7; }
    
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
            <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;">Cuentas por Cobrar (CXC)</h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 14px;">Administración de facturas y cobros pendientes de clientes.</p>
        </div>
        
        <div class="actions-flex">
            <!-- Buscador -->
            <form method="GET" action="/Cycsa/publico/contabilidad/cxc" style="display: flex;">
                <input type="text" name="q" placeholder="Buscar por cliente o factura..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" style="padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px 0 0 6px; font-family: 'Inter', sans-serif; width: 250px; outline: none; font-size: 14px;">
                <button type="submit" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-left: none; padding: 10px 18px; border-radius: 0 6px 6px 0; cursor: pointer; color: #475569; font-size: 14px;"><i class="fa-solid fa-magnifying-glass"></i></button>
                <?php if(!empty($busqueda)): ?>
                    <a href="/Cycsa/publico/contabilidad/cxc" style="margin-left: 10px; color: var(--cycsa-rojo); text-decoration: none; padding-top: 10px; font-size: 14px; font-weight: 500;"><i class="fa-solid fa-xmark"></i> Limpiar</a>
                <?php endif; ?>
            </form>

            <?php if (tienePermiso('contabilidad', 'crear_editar')): ?>
            <button id="btnAbrirModal" style="background: var(--cycsa-azul); color: white; border: none; padding: 11px 22px; border-radius: 6px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: background 0.3s; margin-left: 10px; font-size: 14px;">
                <i class="fa-solid fa-plus"></i> Registrar CXC
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Pestañas secundarias -->
    <div class="tabs-container" style="display: flex; gap: 10px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 25px;">
        <a href="/Cycsa/publico/contabilidad/cuentas" class="tab-link" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 14px; background-color: #f1f5f9; color: #475569; transition: background 0.2s;"><i class="fa-solid fa-list-ol" style="margin-right: 6px;"></i> Catálogo de Cuentas</a>
        <a href="/Cycsa/publico/contabilidad/cxc" class="tab-link" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; background-color: var(--cycsa-azul); color: white;"><i class="fa-solid fa-file-invoice-dollar" style="margin-right: 6px;"></i> Cuentas por Cobrar (CXC)</a>
        <a href="/Cycsa/publico/contabilidad/cxp" class="tab-link" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 14px; background-color: #f1f5f9; color: #475569; transition: background 0.2s;"><i class="fa-solid fa-credit-card" style="margin-right: 6px;"></i> Cuentas por Pagar (CXP)</a>
        <a href="/Cycsa/publico/contabilidad/bancos" class="tab-link" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 14px; background-color: #f1f5f9; color: #475569; transition: background 0.2s;"><i class="fa-solid fa-building-columns" style="margin-right: 6px;"></i> Bancos y Chequera</a>
    </div>

    <!-- KPIs -->
    <div class="kpi-container">
        <div class="kpi-card">
            <div class="kpi-icon" style="background-color: #e0e7ff; color: #4338ca;"><i class="fa-solid fa-file-invoice"></i></div>
            <div>
                <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Total Registrado</div>
                <div style="font-size: 20px; font-weight: 700; color: #0f172a; margin-top: 4px;">C$ <?= number_format($totalRegistrado, 2) ?></div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background-color: #fef3c7; color: #d97706;"><i class="fa-solid fa-clock"></i></div>
            <div>
                <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Saldo Pendiente</div>
                <div style="font-size: 20px; font-weight: 700; color: #d97706; margin-top: 4px;">C$ <?= number_format($totalPendiente, 2) ?></div>
            </div>
        </div>
        <div class="kpi-card">
            <div class="kpi-icon" style="background-color: #dcfce7; color: #15803d;"><i class="fa-solid fa-circle-check"></i></div>
            <div>
                <div style="font-size: 12px; color: #64748b; font-weight: 600; text-transform: uppercase;">Monto Cobrado</div>
                <div style="font-size: 20px; font-weight: 700; color: #15803d; margin-top: 4px;">C$ <?= number_format($totalCobrado, 2) ?></div>
            </div>
        </div>
    </div>

    <!-- Tabla de Cuentas por Cobrar -->
    <div style="overflow-x: auto;">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th>Cliente</th>
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
                <?php foreach ($cxcList as $cxc): 
                    $estClass = 'badge-pendiente';
                    if ($cxc['estado'] === 'Parcial') $estClass = 'badge-parcial';
                    elseif ($cxc['estado'] === 'Pagado') $estClass = 'badge-pagado';
                    elseif ($cxc['estado'] === 'Vencido') $estClass = 'badge-vencido';
                ?>
                <tr>
                    <td style="font-weight: 600;"><?= htmlspecialchars($cxc['cliente_nombre'] ?? 'Cliente Desconocido', ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-family: monospace; font-size: 13.5px;"><?= htmlspecialchars($cxc['factura_numero'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size: 13px; color: #475569;">
                        <?= $cxc['cuenta_codigo'] ? htmlspecialchars($cxc['cuenta_codigo'] . ' - ' . $cxc['cuenta_nombre'], ENT_QUOTES, 'UTF-8') : '—' ?>
                    </td>
                    <td style="font-weight: 600;">C$ <?= number_format($cxc['monto'], 2) ?></td>
                    <td style="font-weight: 600; color: <?= $cxc['saldo'] > 0 ? '#d97706' : '#15803d' ?>;">
                        C$ <?= number_format($cxc['saldo'], 2) ?>
                    </td>
                    <td>
                        <span class="badge-estado <?= $estClass ?>"><?= htmlspecialchars($cxc['estado'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td style="font-size: 13px; color: #64748b;"><?= htmlspecialchars($cxc['fecha_emision'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-size: 13px; color: #64748b;"><?= htmlspecialchars($cxc['fecha_vencimiento'] ?? 'Sin Venc.', ENT_QUOTES, 'UTF-8') ?></td>
                    <?php if (tienePermiso('contabilidad', 'crear_editar')): ?>
                    <td style="text-align: right;">
                        <?php if ($cxc['saldo'] > 0): ?>
                            <button class="btn-accion btn-abono" onclick="abrirAbonoModal(<?= $cxc['id'] ?>, '<?= htmlspecialchars($cxc['factura_numero'], ENT_QUOTES, 'UTF-8') ?>', <?= $cxc['saldo'] ?>)" title="Registrar Cobro">
                                <i class="fa-solid fa-hand-holding-dollar"></i> Cobrar
                            </button>
                        <?php else: ?>
                            <span style="color: #cbd5e1; font-size: 12px; font-style: italic;">Completado</span>
                        <?php endif; ?>
                    </td>
                    <?php endif; ?>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($cxcList)): ?>
                <tr>
                    <td colspan="9" style="text-align: center; padding: 40px; color: #64748b;">No se encontraron cuentas por cobrar registradas.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL NUEVA CXC -->
<div id="modalCxc" class="modal-premium">
    <div class="modal-premium-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700;">Registrar Cuenta por Cobrar</h3>
            <button id="btnCerrarModal" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/contabilidad/guardar-cxc">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Cliente</label>
                <select name="id_cliente" required class="form-control" style="background-color: white;">
                    <option value="">-- Seleccionar Cliente --</option>
                    <?php foreach ($clientes as $cli): ?>
                        <option value="<?= $cli['id'] ?>"><?= htmlspecialchars($cli['nombre_razon_social'] . ' (' . ($cli['identificacion'] ?? 'Sin RUC') . ')', ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Número de Factura / Doc</label>
                    <input type="text" name="factura_numero" required placeholder="Ej: FAC-00125" class="form-control">
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Monto Original (C$)</label>
                    <input type="number" name="monto" step="0.01" min="0.01" required placeholder="0.00" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Cuenta Contable Asociada</label>
                <select name="id_cuenta_contable" class="form-control" style="background-color: white;">
                    <option value="">-- Seleccionar Cuenta Detalle --</option>
                    <?php foreach ($cuentasDetalle as $cta): ?>
                        <option value="<?= $cta['id'] ?>"><?= htmlspecialchars($cta['codigo'] . ' - ' . $cta['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
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
                <textarea name="notas" rows="3" placeholder="Detalles de la factura o proyecto..." class="form-control"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px;">
                <button type="button" id="btnCancelar" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b;">Cancelar</button>
                <button type="submit" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600; padding: 10px 24px;">Guardar CXC</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL REGISTRAR ABONO (COBRO) -->
<div id="modalAbono" class="modal-premium">
    <div class="modal-premium-content" style="width: 35%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 18px; font-weight: 700;">Registrar Cobro / Abono</h3>
            <button onclick="cerrarAbonoModal()" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/contabilidad/pagar-cxc">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_cxc" id="abono_id_cxc">
            
            <div style="background-color: #f8fafc; padding: 15px; border-radius: 6px; margin-bottom: 20px; border: 1px solid #e2e8f0; font-size: 14px;">
                <div style="display: flex; justify-content: space-between; margin-bottom: 6px;">
                    <span style="color: #64748b; font-weight: 500;">Factura Relacionada:</span>
                    <strong id="abono_factura_txt" style="color: #0f172a;">—</strong>
                </div>
                <div style="display: flex; justify-content: space-between;">
                    <span style="color: #64748b; font-weight: 500;">Saldo Pendiente:</span>
                    <strong id="abono_saldo_txt" style="color: #d97706;">C$ 0.00</strong>
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Monto a Cobrar (C$)</label>
                <input type="number" name="monto_pago" id="abono_monto_pago" step="0.01" min="0.01" required placeholder="0.00" class="form-control">
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Depositar en Cuenta Bancaria</label>
                <select name="id_banco_cuenta" required class="form-control" style="background-color: white;">
                    <option value="">-- Seleccionar Banco --</option>
                    <?php foreach ($bancos as $bco): ?>
                        <option value="<?= $bco['id'] ?>"><?= htmlspecialchars($bco['banco_nombre'] . ' - Cta: ' . $bco['numero_cuenta'] . ' (' . $bco['moneda'] . ') - Saldo: ' . number_format($bco['saldo_actual'], 2), ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Referencia / N° Deposito</label>
                    <input type="text" name="referencia" required placeholder="Ej: DEP-58472" class="form-control">
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha de Cobro</label>
                    <input type="date" name="fecha_pago" required class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 15px;">
                <button type="button" onclick="cerrarAbonoModal()" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b;">Cancelar</button>
                <button type="submit" class="form-control" style="cursor: pointer; background: #166534; border: 1px solid #166534; color: white; font-weight: 600; padding: 10px 24px;">Aplicar Cobro</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modalCxc = document.getElementById('modalCxc');
        const btnAbrir = document.getElementById('btnAbrirModal');
        const btnCerrar = document.getElementById('btnCerrarModal');
        const btnCancelar = document.getElementById('btnCancelar');
        
        if (btnAbrir) {
            btnAbrir.addEventListener('click', () => {
                modalCxc.style.display = 'block';
            });
        }
        
        const cerrarModal = () => {
            modalCxc.style.display = 'none';
        };
        
        if (btnCerrar) btnCerrar.addEventListener('click', cerrarModal);
        if (btnCancelar) btnCancelar.addEventListener('click', cerrarModal);
        
        window.addEventListener('click', (e) => {
            if (e.target === modalCxc) {
                cerrarModal();
            }
        });
    });

    const abonoModal = document.getElementById('modalAbono');

    function abrirAbonoModal(idCxc, factura, saldo) {
        document.getElementById('abono_id_cxc').value = idCxc;
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
