<?php
// Catálogo de Cuentas Contables View
?>
<style>
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .tabla-cycsa th { background-color: #f8f9fa; color: #6c757d; padding: 12px 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; text-transform: uppercase; font-size: 12px; }
    .tabla-cycsa td { padding: 12px 15px; border-bottom: 1px solid #e9ecef; vertical-align: middle; color: #333; }
    .tabla-cycsa tbody tr:hover { background-color: #f8f9fa; }
    
    .badge-tipo { padding: 3px 8px; border-radius: 4px; font-size: 11px; font-weight: 600; text-transform: uppercase; }
    .badge-mayor { background-color: #e0f2fe; color: #0369a1; }
    .badge-detalle { background-color: #f0fdf4; color: #166534; }
    
    .badge-cat { padding: 4px 10px; border-radius: 20px; font-size: 11px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; }
    .badge-activo { background-color: #dcfce7; color: #14532d; }
    .badge-pasivo { background-color: #fee2e2; color: #7f1d1d; }
    .badge-capital { background-color: #fef9c3; color: #713f12; }
    .badge-ingreso { background-color: #e0e7ff; color: #312e81; }
    .badge-egreso { background-color: #ffedd5; color: #7c2d12; }

    /* Estilo para filas de cuentas de MAYOR de nivel superior */
    .row-nivel-1 { font-weight: 700; background-color: #f8fafc; }
    .row-nivel-2 { font-weight: 600; padding-left: 15px; }
    .row-nivel-3 { padding-left: 30px; }
    
    /* Modal styles */
    .modal-premium { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); }
    .modal-premium-content { background-color: #fff; margin: 5% auto; padding: 30px; border: 1px solid #e2e8f0; width: 50%; border-radius: 12px; box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04); animation: slideDown 0.3s cubic-bezier(0.16, 1, 0.3, 1); }
    
    @keyframes slideDown {
        from { transform: translateY(-30px); opacity: 0; }
        to { transform: translateY(0); opacity: 1; }
    }
    
    .form-group { margin-bottom: 20px; display: flex; flex-direction: column; gap: 6px; }
    .form-control { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 14px; outline: none; transition: border-color 0.2s; }
    .form-control:focus { border-color: var(--cycsa-azul); }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    
    .btn-cerrar { background: none; border: none; font-size: 20px; color: #94a3b8; cursor: pointer; transition: color 0.2s; }
    .btn-cerrar:hover { color: #475569; }

    /* Alert banners */
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

    <div class="header-flex" style="margin-bottom: 25px;">
        <div>
            <h2 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 22px; font-weight: 700;">Catálogo de Cuentas Contables</h2>
            <p style="color: #64748b; margin-top: 5px; font-size: 14px;">Plan y catálogo de cuentas estructurado por jerarquía contable.</p>
        </div>
        
        <div class="actions-flex">
            <!-- Buscador -->
            <form method="GET" action="/Cycsa/publico/contabilidad/cuentas" style="display: flex;">
                <input type="text" name="q" placeholder="Buscar por código o nombre..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" style="padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px 0 0 6px; font-family: 'Inter', sans-serif; width: 250px; outline: none; font-size: 14px;">
                <button type="submit" style="background: #f1f5f9; border: 1px solid #cbd5e1; border-left: none; padding: 10px 18px; border-radius: 0 6px 6px 0; cursor: pointer; color: #475569; font-size: 14px;"><i class="fa-solid fa-magnifying-glass"></i></button>
                <?php if(!empty($busqueda)): ?>
                    <a href="/Cycsa/publico/contabilidad/cuentas" style="margin-left: 10px; color: var(--cycsa-rojo); text-decoration: none; padding-top: 10px; font-size: 14px; font-weight: 500;"><i class="fa-solid fa-xmark"></i> Limpiar</a>
                <?php endif; ?>
            </form>

            <?php if (tienePermiso('contabilidad', 'crear_editar')): ?>
            <button id="btnAbrirModal" style="background: var(--cycsa-azul); color: white; border: none; padding: 11px 22px; border-radius: 6px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; text-decoration: none; display: flex; align-items: center; gap: 8px; transition: background 0.3s; margin-left: 10px; font-size: 14px;">
                <i class="fa-solid fa-plus"></i> Nueva Cuenta
            </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- Menú de pestañas secundarias para navegar en contabilidad -->
    <div class="tabs-container" style="display: flex; gap: 10px; border-bottom: 2px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 20px;">
        <a href="/Cycsa/publico/contabilidad/cuentas" class="tab-link" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 600; font-size: 14px; background-color: var(--cycsa-azul); color: white;"><i class="fa-solid fa-list-ol" style="margin-right: 6px;"></i> Catálogo de Cuentas</a>
        <a href="/Cycsa/publico/contabilidad/cxc" class="tab-link" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 14px; background-color: #f1f5f9; color: #475569; transition: background 0.2s;"><i class="fa-solid fa-file-invoice-dollar" style="margin-right: 6px;"></i> Cuentas por Cobrar (CXC)</a>
        <a href="/Cycsa/publico/contabilidad/cxp" class="tab-link" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 14px; background-color: #f1f5f9; color: #475569; transition: background 0.2s;"><i class="fa-solid fa-credit-card" style="margin-right: 6px;"></i> Cuentas por Pagar (CXP)</a>
        <a href="/Cycsa/publico/contabilidad/bancos" class="tab-link" style="padding: 8px 16px; border-radius: 6px; text-decoration: none; font-weight: 500; font-size: 14px; background-color: #f1f5f9; color: #475569; transition: background 0.2s;"><i class="fa-solid fa-building-columns" style="margin-right: 6px;"></i> Bancos y Chequera</a>
    </div>

    <!-- Tabla del Catálogo -->
    <div style="overflow-x: auto;">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th style="width: 120px;">Código</th>
                    <th>Nombre de la Cuenta</th>
                    <th style="width: 100px;">Tipo</th>
                    <th style="width: 120px;">Categoría</th>
                    <th>Cuenta Padre</th>
                    <th>Tipo Mayor / Detalle</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cuentas as $cta): 
                    // Determinar clase de indentación visual basada en la longitud del código
                    $len = strlen($cta['codigo']);
                    $rowClass = '';
                    $paddingStyle = '';
                    if ($len <= 2) {
                        $rowClass = 'row-nivel-1';
                    } elseif ($len <= 4) {
                        $rowClass = 'row-nivel-2';
                        $paddingStyle = 'padding-left: 20px;';
                    } else {
                        $rowClass = 'row-nivel-3';
                        $paddingStyle = 'padding-left: 40px;';
                    }
                    
                    // Categoría badge class
                    $catClass = 'badge-activo';
                    if ($cta['categoria'] === 'PASIVO') $catClass = 'badge-pasivo';
                    elseif ($cta['categoria'] === 'CAPITAL') $catClass = 'badge-capital';
                    elseif ($cta['categoria'] === 'INGRESO') $catClass = 'badge-ingreso';
                    elseif ($cta['categoria'] === 'EGRESO') $catClass = 'badge-egreso';
                ?>
                <tr class="<?= $rowClass ?>">
                    <td style="font-family: monospace; font-size: 13.5px;"><?= htmlspecialchars($cta['codigo'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="<?= $paddingStyle ?>"><?= htmlspecialchars($cta['nombre'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td>
                        <span class="badge-tipo <?= $cta['tipo'] === 'MAYOR' ? 'badge-mayor' : 'badge-detalle' ?>">
                            <?= htmlspecialchars($cta['tipo'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td>
                        <span class="badge-cat <?= $catClass ?>">
                            <?= htmlspecialchars($cta['categoria'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </td>
                    <td style="color: #64748b; font-size: 13px;">
                        <?= $cta['codigo_padre'] ? htmlspecialchars($cta['codigo_padre'] . ' / ' . $cta['nombre_padre'], ENT_QUOTES, 'UTF-8') : '<span style="color:#cbd5e1;">Ninguna</span>' ?>
                    </td>
                    <td style="color: #64748b; font-size: 13px;">
                        <?= htmlspecialchars($cta['tipo_cuenta_mayor'] ?? $cta['tipo_cuenta_detalle'] ?? '—', ENT_QUOTES, 'UTF-8') ?>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if (empty($cuentas)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 40px; color: #64748b;">No se encontraron cuentas contables en el catálogo.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<!-- MODAL REGISTRAR CUENTA -->
<div id="modalCuenta" class="modal-premium">
    <div class="modal-premium-content">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px;">
            <h3 style="margin: 0; color: #0f172a; font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 700;">Registrar Cuenta Contable</h3>
            <button id="btnCerrarModal" class="btn-cerrar">&times;</button>
        </div>
        
        <form method="POST" action="/Cycsa/publico/contabilidad/guardar-cuenta">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            
            <div class="grid-2">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Código de Cuenta</label>
                    <input type="text" name="codigo" required placeholder="Ej: 1010109" class="form-control">
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Nombre de la Cuenta</label>
                    <input type="text" name="nombre" required placeholder="Ej: BANCO LAFISE C$" class="form-control">
                </div>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Tipo de Cuenta</label>
                    <select name="tipo" required class="form-control" style="background-color: white;">
                        <option value="DETALLE">DETALLE (Transaccionable)</option>
                        <option value="MAYOR">MAYOR (Agrupadora)</option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Categoría</label>
                    <select name="categoria" required class="form-control" style="background-color: white;">
                        <option value="ACTIVO">ACTIVO</option>
                        <option value="PASIVO">PASIVO</option>
                        <option value="CAPITAL">CAPITAL</option>
                        <option value="INGRESO">INGRESO</option>
                        <option value="EGRESO">EGRESO (Costos / Gastos)</option>
                    </select>
                </div>
            </div>

            <div class="form-group">
                <label style="font-weight: 600; font-size: 13px; color: #334155;">Cuenta Padre (Opcional)</label>
                <select name="id_padre" class="form-control" style="background-color: white;">
                    <option value="">-- Seleccionar Cuenta Padre --</option>
                    <?php foreach ($cuentasMayor as $mayor): ?>
                        <option value="<?= $mayor['id'] ?>"><?= htmlspecialchars($mayor['codigo'] . ' - ' . $mayor['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid-2">
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Tipo Cuenta Mayor (Opcional)</label>
                    <input type="text" name="tipo_cuenta_mayor" placeholder="Ej: Efectivo recibido" class="form-control">
                </div>
                
                <div class="form-group">
                    <label style="font-weight: 600; font-size: 13px; color: #334155;">Tipo Cuenta Detalle (Opcional)</label>
                    <input type="text" name="tipo_cuenta_detalle" placeholder="Ej: Gastos e ingresos no monetarios" class="form-control">
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 12px; margin-top: 10px;">
                <button type="button" id="btnCancelar" class="form-control" style="cursor: pointer; background: #fff; border: 1px solid #cbd5e1; font-weight: 600; color: #64748b;">Cancelar</button>
                <button type="submit" class="form-control" style="cursor: pointer; background: var(--cycsa-azul); border: 1px solid var(--cycsa-azul); color: white; font-weight: 600; padding: 10px 24px;">Guardar Cuenta</button>
            </div>
        </form>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', () => {
        const modal = document.getElementById('modalCuenta');
        const btnAbrir = document.getElementById('btnAbrirModal');
        const btnCerrar = document.getElementById('btnCerrarModal');
        const btnCancelar = document.getElementById('btnCancelar');
        
        if (btnAbrir) {
            btnAbrir.addEventListener('click', () => {
                modal.style.display = 'block';
            });
        }
        
        const cerrarModal = () => {
            modal.style.display = 'none';
        };
        
        btnCerrar.addEventListener('click', cerrarModal);
        btnCancelar.addEventListener('click', cerrarModal);
        
        window.addEventListener('click', (e) => {
            if (e.target === modal) {
                cerrarModal();
            }
        });
    });
</script>
