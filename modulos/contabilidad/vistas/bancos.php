<?php
// Bancos y Chequera View
?>
<style>
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .tabla-cycsa th { background-color: #f8f9fa; color: #6c757d; padding: 12px 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; text-transform: uppercase; font-size: 12px; }
    .tabla-cycsa td { padding: 12px 15px; border-bottom: 1px solid #e9ecef; vertical-align: middle; color: #333; }
    .tabla-cycsa tbody tr:hover { background-color: #f8f9fa; }
    
    .badge-tx { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .badge-deposito { background-color: #dcfce7; color: #15803d; }
    .badge-retiro { background-color: #fee2e2; color: #b91c1c; }
    .badge-cheque { background-color: #f3e8ff; color: #6b21a8; }
    .badge-transferencia { background-color: #e0e7ff; color: #3730a3; }
    
    .badge-est { padding: 2px 6px; border-radius: 12px; font-size: 10px; font-weight: 600; }
    .badge-emitido { background-color: #fef3c7; color: #d97706; }
    .badge-cobrado { background-color: #dcfce7; color: #166534; }
    .badge-anulado { background-color: #f3f4f6; color: #4b5563; text-decoration: line-through; }
    .badge-conciliado { background-color: #e0f2fe; color: #0369a1; }

    .layout-bancos { display: grid; grid-template-columns: 320px 1fr; gap: 30px; }
    
    .banco-card { background: white; border: 1px solid #e2e8f0; border-radius: 8px; padding: 18px; margin-bottom: 15px; cursor: pointer; transition: all 0.2s; position: relative; overflow: hidden; }
    .banco-card:hover { border-color: var(--cycsa-azul); box-shadow: 0 4px 12px rgba(16, 52, 135, 0.05); }
    .banco-card.seleccionado { border-color: var(--cycsa-azul); border-left: 4px solid var(--cycsa-azul); background-color: #f8fafc; }
    
    .banco-card-logo { width: 36px; height: 36px; border-radius: 6px; background-color: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 14px; font-weight: 700; color: #334155; }
    
    /* Modal styles */
    .modal-premium { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .modal-premium-content { background-color: #fff; margin: 5% auto; padding: 30px; border: 1px solid #e2e8f0; width: 40%; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); }
    
    .form-group { margin-bottom: 16px; display: flex; flex-direction: column; gap: 6px; }
    .form-control { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; }
    .form-control:focus { border-color: var(--cycsa-azul); }
    
    .alert { padding: 12px 20px; border-radius: 6px; margin-bottom: 20px; font-weight: 500; font-size: 14px; display: flex; align-items: center; gap: 10px; }
    .alert-exito { background-color: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; }
    .alert-error { background-color: #fef2f2; color: #991b1b; border: 1px solid #fecaca; }

    @media (max-width: 992px) {
        .layout-bancos { grid-template-columns: 1fr; }
    }
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
            <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;">Control Bancario y Chequeras</h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 14px;">Administración de cuentas corrientes, chequeras y libros auxiliares de bancos.</p>
        </div>
        
        <div class="actions-flex">
            <?php if (tienePermiso('contabilidad', 'crear_editar')): ?>
            <button id="btnAbrirModalTx" style="background: #f1f5f9; border: 1px solid #cbd5e1; color: #334155; padding: 11px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 8px; transition: background 0.3s; font-size: 14px;">
                <i class="fa-solid fa-money-bill-transfer"></i> Nueva Transacción
            </button>
            <button id="btnAbrirModalBco" style="background: var(--cycsa-azul); color: white; border: none; padding: 11px 22px; border-radius: 6px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; display: flex; align-items: center; gap: 8px; transition: background 0.3s; margin-left: 10px; font-size: 14px;">
                <i class="fa-solid fa-plus"></i> Registrar Banco
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Menú de pestañas secundarias para navegar en contabilidad -->
    <div class="tabs-container" style="display: flex; gap: 8px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px; flex-wrap: wrap;">
        <a href="/Cycsa/publico/contabilidad/cuentas" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-list-ol"></i> Catálogo</a>
        <a href="/Cycsa/publico/contabilidad/diario" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-book"></i> Registro Diario</a>
        <a href="/Cycsa/publico/contabilidad/cxc" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-file-invoice-dollar"></i> Cobros (CXC)</a>
        <a href="/Cycsa/publico/contabilidad/cxp" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-credit-card"></i> Pagos (CXP)</a>
        <a href="/Cycsa/publico/contabilidad/bancos" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 13.5px; background-color: var(--cycsa-azul); color: white;"><i class="fa-solid fa-building-columns"></i> Bancos</a>
        <a href="/Cycsa/publico/contabilidad/balance" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-scale-balanced"></i> Balance General</a>
        <a href="/Cycsa/publico/contabilidad/resultados" class="tab-link" style="padding: 8px 14px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 13.5px; background-color: #f1f5f9; color: #475569;"><i class="fa-solid fa-chart-line"></i> Estado de Resultados</a>
    </div>

    <!-- Layout de Bancos -->
    <div class="layout-bancos">
        
        <!-- Columna Izquierda: Cuentas -->
        <div>
            <h4 style="color: #0f172a; margin-bottom: 15px; font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 16px;">Cuentas Bancarias</h4>
            
            <a href="/Cycsa/publico/contabilidad/bancos" class="banco-card <?= $filtroBancoId === 0 ? 'seleccionado' : '' ?>" style="display: block; text-decoration: none; color: inherit;">
                <div style="display: flex; align-items: center; gap: 12px;">
                    <div class="banco-card-logo" style="background-color: var(--cycsa-azul); color: white;"><i class="fa-solid fa-globe"></i></div>
                    <div>
                        <strong style="font-size: 14px;">TODAS LAS CUENTAS</strong>
                        <div style="font-size: 12px; color: #64748b; margin-top: 2px;">Historial consolidador de movimientos</div>
                    </div>
                </div>
            </a>

            <?php foreach ($bancos as $bco): ?>
                <a href="/Cycsa/publico/contabilidad/bancos?banco_id=<?= $bco['id'] ?>" class="banco-card <?= $filtroBancoId === $bco['id'] ? 'seleccionado' : '' ?>" style="display: block; text-decoration: none; color: inherit;">
                    <div style="display: flex; align-items: center; justify-content: space-between;">
                        <div style="display: flex; align-items: center; gap: 12px;">
                            <div class="banco-card-logo">
                                <?= htmlspecialchars(substr($bco['banco_nombre'], 0, 3), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div>
                                <strong style="font-size: 14px;"><?= htmlspecialchars($bco['banco_nombre'], ENT_QUOTES, 'UTF-8') ?></strong>
                                <div style="font-size: 12px; color: #64748b; font-family: monospace; margin-top: 2px;">Cta: <?= htmlspecialchars($bco['numero_cuenta'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                        <div style="text-align: right;">
                            <strong style="font-size: 15px; color: var(--cycsa-azul);"><?= $bco['moneda'] ?> <?= number_format($bco['saldo_actual'], 2) ?></strong>
                            <div style="font-size: 11px; color: #94a3b8; margin-top: 2px;">Aux: <?= htmlspecialchars($bco['cuenta_codigo'] ?? 'S/A', ENT_QUOTES, 'UTF-8') ?></div>
                        </div>
                    </div>
                </a>
            <?php endforeach; ?>
        </div>
        
        <!-- Columna Derecha: Transacciones / Chequera -->
        <div>
            <h4 style="color: #0f172a; margin-bottom: 15px; font-family: 'Outfit', sans-serif; font-weight: 600; font-size: 16px;">
                <?= $filtroBancoId > 0 ? 'Libro Auxiliar / Chequera de la Cuenta' : 'Historial Consolidador de Transacciones' ?>
            </h4>
            
            <div style="overflow-x: auto; background: white; border: 1px solid #e2e8f0; border-radius: 8px;">
                <table class="tabla-cycsa" style="margin-top: 0;">
                    <thead>
                        <tr style="border-top: none;">
                            <th>Fecha</th>
                            <th>Tipo</th>
                            <th>Doc / Cheque N°</th>
                            <th>Beneficiario / Concepto</th>
                            <th>Monto</th>
                            <th>Estado</th>
                            <th>Banco Cuenta</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($transacciones as $tx): 
                            $txClass = 'badge-transferencia';
                            $sign = '';
                            $colorMonto = '#333';
                            if ($tx['tipo_transaccion'] === 'DEPOSITO') {
                                $txClass = 'badge-deposito';
                                $sign = '+ ';
                                $colorMonto = '#15803d';
                            } elseif ($tx['tipo_transaccion'] === 'RETIRO') {
                                $txClass = 'badge-retiro';
                                $sign = '- ';
                                $colorMonto = '#b91c1c';
                            } elseif ($tx['tipo_transaccion'] === 'CHEQUE') {
                                $txClass = 'badge-cheque';
                                $sign = '- ';
                                $colorMonto = '#6b21a8';
                            }
                            
                            $estClass = 'badge-cobrado';
                            if ($tx['estado'] === 'Emitido') $estClass = 'badge-emitido';
                            elseif ($tx['estado'] === 'Anulado') $estClass = 'badge-anulado';
                            elseif ($tx['estado'] === 'Conciliado') $estClass = 'badge-conciliado';
                        ?>
                        <tr>
                            <td style="font-size: 13px; color: #64748b;"><?= htmlspecialchars($tx['fecha'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <span class="badge-tx <?= $txClass ?>"><?= htmlspecialchars($tx['tipo_transaccion'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td style="font-family: monospace; font-size: 13.5px;"><?= htmlspecialchars($tx['numero_documento'] ?? '—', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <div style="font-weight: 500;"><?= htmlspecialchars($tx['beneficiario'] ?? 'S/B', ENT_QUOTES, 'UTF-8') ?></div>
                                <div style="font-size: 11.5px; color: #94a3b8; margin-top: 2px; max-width: 250px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($tx['descripcion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($tx['descripcion'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                                </div>
                            </td>
                            <td style="font-weight: 700; color: <?= $colorMonto ?>;">
                                <?= $sign ?><?= $tx['moneda'] ?> <?= number_format($tx['monto'], 2) ?>
                            </td>
                            <td>
                                <span class="badge-est <?= $estClass ?>"><?= htmlspecialchars($tx['estado'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td style="font-size: 12px; color: #64748b;">
                                <strong><?= htmlspecialchars($tx['banco_nombre'], ENT_QUOTES, 'UTF-8') ?></strong><br>
                                <span style="font-family: monospace; font-size: 11px;"><?= htmlspecialchars($tx['numero_cuenta'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                        
                        <?php if (empty($transacciones)): ?>
                        <tr>
                            <td colspan="7" style="text-align: center; padding: 40px; color: #64748b;">No se encontraron movimientos registrados en esta cuenta.</td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- MODAL NUEVO BANCO -->
<div id="modalBanco" class="modal-premium">
    <div class="modal-premium-content" style="width: 35%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700;">Registrar Cuenta Bancaria</h3>
            <button id="btnCerrarBco" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/contabilidad/guardar-banco">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Nombre del Banco</label>
                <input type="text" name="banco_nombre" required placeholder="Ej: BAC Credomatic" class="form-control">
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Número de Cuenta Corriente / Ahorros</label>
                <input type="text" name="numero_cuenta" required placeholder="Ej: 357-02445-4" class="form-control">
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Moneda</label>
                    <select name="moneda" required class="form-control" style="background-color: white;">
                        <option value="C$">Córdobas (C$)</option>
                        <option value="$">Dólares ($)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Saldo Inicial</label>
                    <input type="number" name="saldo_inicial" step="0.01" min="0.00" required value="0.00" class="form-control">
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Vincular Cuenta Contable Auxiliar</label>
                <select name="id_cuenta_contable" class="form-control" style="background-color: white;">
                    <option value="">-- Seleccionar Cuenta Detalle --</option>
                    <?php foreach ($cuentasDetalle as $cta): ?>
                        <option value="<?= $cta['id'] ?>"><?= htmlspecialchars($cta['codigo'] . ' - ' . $cta['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 15px;">
                <button type="button" id="btnCancelarBco" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b;">Cancelar</button>
                <button type="submit" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600; padding: 10px 24px;">Guardar Banco</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL NUEVA TRANSACCIÓN MANUAL -->
<div id="modalTransaccion" class="modal-premium">
    <div class="modal-premium-content" style="width: 38%;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700;">Registrar Operación Bancaria</h3>
            <button id="btnCerrarTx" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/contabilidad/guardar-transaccion">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Cuenta Bancaria Afectada</label>
                <select name="id_banco_cuenta" required class="form-control" style="background-color: white;">
                    <option value="">-- Seleccionar Cuenta --</option>
                    <?php foreach ($bancos as $bco): ?>
                        <option value="<?= $bco['id'] ?>" <?= $filtroBancoId === $bco['id'] ? 'selected' : '' ?>><?= htmlspecialchars($bco['banco_nombre'] . ' - Cta: ' . $bco['numero_cuenta'] . ' (' . $bco['moneda'] . ')', ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Tipo de Operación</label>
                    <select name="tipo_transaccion" required class="form-control" style="background-color: white;">
                        <option value="DEPOSITO">DEPÓSITO (Ingreso)</option>
                        <option value="RETIRO">RETIRO / DÉBITO (Egreso)</option>
                        <option value="CHEQUE">EMISIÓN DE CHEQUE (Egreso)</option>
                        <option value="TRANSFERENCIA">TRANSFERENCIA (Egreso)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Monto</label>
                    <input type="number" name="monto" step="0.01" min="0.01" required placeholder="0.00" class="form-control">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">N° Documento / Cheque</label>
                    <input type="text" name="numero_documento" placeholder="Ej: CHQ-5021 o MIN-9584" class="form-control">
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Fecha de Operación</label>
                    <input type="date" name="fecha" required class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Beneficiario / Remitente</label>
                <input type="text" name="beneficiario" placeholder="Nombre de la persona o concepto..." class="form-control">
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Descripción / Notas</label>
                <textarea name="descripcion" rows="3" placeholder="Detalles de la transacción..." class="form-control"></textarea>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 15px;">
                <button type="button" id="btnCancelarTx" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b;">Cancelar</button>
                <button type="submit" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600; padding: 10px 24px;">Registrar</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        // Modal Banco
        const modalBco = document.getElementById('modalBanco');
        const btnAbrirBco = document.getElementById('btnAbrirModalBco');
        const btnCerrarBco = document.getElementById('btnCerrarBco');
        const btnCancelarBco = document.getElementById('btnCancelarBco');
        
        if (btnAbrirBco) {
            btnAbrirBco.addEventListener('click', () => {
                modalBco.style.display = 'block';
            });
        }
        
        const cerrarBco = () => { modalBco.style.display = 'none'; };
        if (btnCerrarBco) btnCerrarBco.addEventListener('click', cerrarBco);
        if (btnCancelarBco) btnCancelarBco.addEventListener('click', cerrarBco);

        // Modal Transacción
        const modalTx = document.getElementById('modalTransaccion');
        const btnAbrirTx = document.getElementById('btnAbrirModalTx');
        const btnCerrarTx = document.getElementById('btnCerrarTx');
        const btnCancelarTx = document.getElementById('btnCancelarTx');
        
        if (btnAbrirTx) {
            btnAbrirTx.addEventListener('click', () => {
                modalTx.style.display = 'block';
            });
        }
        
        const cerrarTx = () => { modalTx.style.display = 'none'; };
        if (btnCerrarTx) btnCerrarTx.addEventListener('click', cerrarTx);
        if (btnCancelarTx) btnCancelarTx.addEventListener('click', cerrarTx);

        // Cerrar al hacer click fuera
        window.addEventListener('click', (e) => {
            if (e.target === modalBco) cerrarBco();
            if (e.target === modalTx) cerrarTx();
        });
    });
</script>
