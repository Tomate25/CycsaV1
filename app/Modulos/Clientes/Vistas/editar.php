<style>
    .form-section-card { background: white; padding: 25px; border-radius: 8px; border: 1px solid #e2e8f0; box-shadow: 0 1px 3px rgba(0,0,0,0.02); margin-bottom: 25px; }
    .section-title { font-size: 14px; font-weight: 700; color: var(--cycsa-azul); text-transform: uppercase; letter-spacing: 0.5px; margin-top: 0; margin-bottom: 20px; border-bottom: 2px solid #f1f5f9; padding-bottom: 8px; display: flex; align-items: center; gap: 8px; }
    
    .form-grid-3 { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; }
    .form-grid-2 { display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 20px; }
    .form-group { display: flex; flex-direction: column; gap: 6px; }
    .form-group label { font-size: 13px; font-weight: 600; color: #475569; }
    
    .form-control { padding: 10px 14px; border: 1px solid #cbd5e1; border-radius: 6px; font-family: 'Inter', sans-serif; font-size: 13.5px; transition: all 0.2s; box-sizing: border-box; width: 100%; }
    .form-control:focus { outline: none; border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.1); }
    
    select.form-control {
        appearance: none;
        -webkit-appearance: none;
        -moz-appearance: none;
        background-image: url("data:image/svg+xml;utf8,<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%23475569' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'><polyline points='6 9 12 15 18 9'></polyline></svg>");
        background-repeat: no-repeat;
        background-position: right 14px center;
        background-size: 16px;
        padding-right: 40px !important;
        cursor: pointer;
        background-color: white;
    }

    /* Custom Search Trigger Styling */
    .custom-select-trigger {
        display: flex;
        justify-content: space-between;
        align-items: center;
        padding: 11px 14px;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        background: white;
        cursor: pointer;
        font-size: 13.5px;
        font-family: 'Inter', sans-serif;
        transition: all 0.2s;
        min-height: 41px;
        box-sizing: border-box;
    }
    .custom-select-trigger:hover {
        border-color: var(--cycsa-azul);
        box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.05);
    }
    .custom-select-trigger span {
        color: #1e293b;
        font-weight: 500;
    }
    .cuenta-option-item {
        padding: 12px 15px;
        border-bottom: 1px solid #f1f5f9;
        cursor: pointer;
        font-size: 13px;
        font-family: 'Inter', sans-serif;
        transition: all 0.15s;
        color: #334155;
        font-weight: 500;
    }
    .cuenta-option-item:hover {
        background-color: #eff6ff;
        color: var(--cycsa-azul);
        font-weight: 600;
        padding-left: 18px;
    }
    
    .checkbox-group { display: flex; align-items: center; gap: 10px; padding: 10px 0; }
    .checkbox-group input { width: 18px; height: 18px; cursor: pointer; }
    .checkbox-group label { font-size: 13.5px; font-weight: 600; color: #475569; cursor: pointer; }

    .btn-cycsa { display: inline-flex; align-items: center; gap: 6px; border: 1px solid transparent; padding: 10px 20px; border-radius: 6px; font-size: 14px; font-weight: 600; font-family: 'Inter', sans-serif; cursor: pointer; transition: all 0.2s; text-decoration: none; }
    .btn-cycsa-primary { background: var(--cycsa-azul); color: white; }
    .btn-cycsa-primary:hover { background: #0c2766; transform: translateY(-1px); }
    .btn-cycsa-secondary { background: #f8fafc; color: #64748b; border-color: #e2e8f0; }
    .btn-cycsa-secondary:hover { background: #cbd5e1; color: #0f172a; }

    /* Modal design adjustments for search panel */
    .modal-premium { display: none; position: fixed; z-index: 1000; left: 0; top: 0; width: 100%; height: 100%; overflow: auto; background-color: rgba(15, 23, 42, 0.4); backdrop-filter: blur(4px); }
    .modal-premium-content { background-color: white; margin: 8% auto; padding: 25px; border-radius: 12px; border: 1px solid #e2e8f0; width: 500px; box-shadow: 0 20px 25px -5px rgba(0,0,0,0.1), 0 10px 10px -5px rgba(0,0,0,0.04); animation: slideDown 0.2s ease-out; }
</style>

<div style="max-width: 1000px; margin: 0 auto 20px auto; display: flex; justify-content: space-between; align-items: center;">
    <div>
        <a href="/Cycsa/publico/clientes" style="color: #64748b; text-decoration: none; font-size: 14px; font-weight: 500;"><i class="fa-solid fa-arrow-left"></i> Volver a la lista</a>
        <h2 style="margin: 5px 0 0 0; color: #0f172a; font-size: 22px; font-family: 'Outfit', sans-serif; font-weight: 700;">Editar Cliente #<?= htmlspecialchars($cliente['id'] ?? '', ENT_QUOTES, 'UTF-8') ?></h2>
    </div>
</div>

<?php if (isset($error)): ?>
    <div style="max-width: 1000px; margin: 0 auto 20px auto; background-color: #fee2e2; color: #991b1b; padding: 12px 15px; border-radius: 6px; font-size: 14px; font-weight: 500; border-left: 4px solid #ef4444; display: flex; align-items: center; gap: 10px;">
        <i class="fa-solid fa-circle-exclamation"></i>
        <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
    </div>
<?php endif; ?>

<form action="/Cycsa/publico/clientes/editar?id=<?= codificarId($cliente['id']) ?>" method="POST" style="max-width: 1000px; margin: 0 auto 40px auto;">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id" value="<?= codificarId($cliente['id']) ?>">

    <!-- SECCIÓN 1: INFORMACIÓN GENERAL -->
    <div class="form-section-card">
        <h3 class="section-title"><i class="fa-solid fa-user-pen"></i> Información General del Cliente</h3>
        <div class="form-grid-3" style="margin-bottom: 20px;">
            <div class="form-group">
                <label>Tipo de Cliente *</label>
                <select name="tipo_cliente" id="tipo_cliente" required class="form-control" onchange="toggleTipoCliente(this.value)">
                    <option value="Jurídico" <?= ($cliente['tipo_cliente'] ?? '') === 'Jurídico' ? 'selected' : '' ?>>Jurídico</option>
                    <option value="Natural" <?= ($cliente['tipo_cliente'] ?? '') === 'Natural' ? 'selected' : '' ?>>Natural</option>
                    <option value="Extranjero Jurídico" <?= ($cliente['tipo_cliente'] ?? '') === 'Extranjero Jurídico' ? 'selected' : '' ?>>Extranjero Jurídico</option>
                </select>
            </div>
            <div class="form-group">
                <label>Código de Cliente</label>
                <input type="text" name="codigo_cliente" value="<?= htmlspecialchars($cliente['codigo_cliente'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: CLI-203">
            </div>
            <div class="form-group">
                <label id="lbl-nombre-cliente">Nombre / Razón Social *</label>
                <input type="text" name="nombre_cliente" required value="<?= htmlspecialchars($cliente['nombre_cliente'] ?? $cliente['nombre_razon_social'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: Aceitera El Real S.A.">
            </div>
        </div>

        <div class="form-grid-3" id="seccion-apellidos" style="display: none; margin-bottom: 20px;">
            <div class="form-group">
                <label>Primer Apellido</label>
                <input type="text" name="primer_apellido" value="<?= htmlspecialchars($cliente['primer_apellido'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: Pérez">
            </div>
            <div class="form-group">
                <label>Segundo Apellido</label>
                <input type="text" name="segundo_apellido" value="<?= htmlspecialchars($cliente['segundo_apellido'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: Gómez">
            </div>
            <div class="form-group">
                <label>Sucursal Sede</label>
                <input type="text" name="sucursal_sede" value="<?= htmlspecialchars($cliente['sucursal_sede'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: Sede León">
            </div>
        </div>

        <div class="form-grid-3" style="margin-bottom: 20px;">
            <div class="form-group">
                <label>Clasificación</label>
                <input type="text" name="clasificacion" value="<?= htmlspecialchars($cliente['clasificacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: VIP, Frecuente, etc.">
            </div>
            <div class="form-group">
                <label>Sub Clasificación</label>
                <input type="text" name="sub_clasificacion" value="<?= htmlspecialchars($cliente['sub_clasificacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: Corporativo">
            </div>
            <div class="form-group">
                <label>Vendedor</label>
                <input type="text" name="vendedor" value="<?= htmlspecialchars($cliente['vendedor'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: CYCSA- FR12-003--Tiana Grillo">
            </div>
        </div>

        <div class="form-grid-3" style="margin-bottom: 20px;">
            <div class="form-group">
                <label>Número Cédula</label>
                <input type="text" name="numero_cedula" value="<?= htmlspecialchars($cliente['numero_cedula'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: 001-200590-0012A">
            </div>
            <div class="form-group">
                <label>Número RUC</label>
                <input type="text" name="numero_ruc" value="<?= htmlspecialchars($cliente['numero_ruc'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: J0310000000980">
            </div>
            <div class="form-group">
                <label>Contacto Principal</label>
                <input type="text" name="contacto" value="<?= htmlspecialchars($cliente['contacto'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: Ing. Milder Gutierrez">
            </div>
        </div>

        <div class="form-grid-3" style="margin-bottom: 20px;">
            <div class="form-group">
                <label>Teléfono</label>
                <input type="text" name="telefono" value="<?= htmlspecialchars($cliente['telefono'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: +505 8888 8888">
            </div>
            <div class="form-group">
                <label>Fax</label>
                <input type="text" name="fax" value="<?= htmlspecialchars($cliente['fax'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: 2222-3333">
            </div>
            <div class="form-group">
                <label>Correo Electrónico Principal</label>
                <input type="email" name="email" value="<?= htmlspecialchars($cliente['email'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: admin@empresa.com">
            </div>
        </div>

        <div class="form-grid-2">
            <div class="form-group">
                <label>Dirección Física</label>
                <textarea name="direccion" rows="2" class="form-control" placeholder="Dirección exacta del cliente..."><?= htmlspecialchars($cliente['direccion'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
            <div class="form-group">
                <label>Notas Internas / Observaciones</label>
                <textarea name="notas" rows="2" class="form-control" placeholder="Notas sobre facturación, despacho u observaciones especiales..."><?= htmlspecialchars($cliente['notas'] ?? $cliente['notes'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: DATOS CONTABLES Y CRÉDITO -->
    <div class="form-section-card">
        <h3 class="section-title"><i class="fa-solid fa-file-invoice-dollar"></i> Cuentas Contables y Crédito</h3>
        
        <div class="form-grid-3" style="margin-bottom: 20px;">
            <div class="form-group">
                <label>Cuenta por Cobrar *</label>
                <input type="hidden" name="cuenta_cxc" id="input-cuenta_cxc" value="<?= htmlspecialchars($cliente['cuenta_cxc'] ?? '1010201 / CLIENTES NACIONALES', ENT_QUOTES, 'UTF-8') ?>">
                <div class="custom-select-trigger" onclick="abrirBuscadorCuentas('cuenta_cxc')">
                    <span id="label-cuenta_cxc"><?= htmlspecialchars($cliente['cuenta_cxc'] ?? '1010201 / CLIENTES NACIONALES', ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="fa-solid fa-chevron-down" style="color: #64748b; font-size: 12px;"></i>
                </div>
            </div>
            <div class="form-group">
                <label>Cuenta por Pagar *</label>
                <input type="hidden" name="cuenta_cxp" id="input-cuenta_cxp" value="<?= htmlspecialchars($cliente['cuenta_cxp'] ?? '2010303 / ANTICIPO CLIENTES', ENT_QUOTES, 'UTF-8') ?>">
                <div class="custom-select-trigger" onclick="abrirBuscadorCuentas('cuenta_cxp')">
                    <span id="label-cuenta_cxp"><?= htmlspecialchars($cliente['cuenta_cxp'] ?? '2010303 / ANTICIPO CLIENTES', ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="fa-solid fa-chevron-down" style="color: #64748b; font-size: 12px;"></i>
                </div>
            </div>
            <input type="hidden" name="tipo_moneda" value="1">
        </div>

        <div class="form-grid-3" style="margin-bottom: 20px;">
            <div class="form-group">
                <label>Límite de Crédito</label>
                <input type="text" name="limite_credito" value="<?= htmlspecialchars(number_format($cliente['limite_credito'] ?? 0.00, 2, '.', ''), ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: 20,000.00">
            </div>
            <div class="form-group">
                <label>Días de Crédito</label>
                <input type="number" name="dias_credito" value="<?= htmlspecialchars($cliente['dias_credito'] ?? '0', ENT_QUOTES, 'UTF-8') ?>" class="form-control">
            </div>
            <div class="form-group">
                <label>No. de Facturas Vencidas Permitidas</label>
                <input type="number" name="facturas_vencidas_permitidas" value="<?= htmlspecialchars($cliente['facturas_vencidas_permitidas'] ?? '0', ENT_QUOTES, 'UTF-8') ?>" class="form-control">
            </div>
        </div>

        <div class="form-grid-3" style="margin-bottom: 20px;">
            <div class="form-group">
                <label>Descuento %</label>
                <input type="number" step="0.01" name="porcentaje_descuento" value="<?= htmlspecialchars($cliente['porcentaje_descuento'] ?? '0.00', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: 5.00">
            </div>
            <div class="form-group" id="grupo-cuenta-exonerado" style="display: none;">
                <label>Cuenta para Ingresos Exonerados</label>
                <input type="hidden" name="cuenta_ingresos_exonerados" id="input-cuenta_ingresos_exonerados" value="<?= htmlspecialchars($cliente['cuenta_ingresos_exonerados'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <div class="custom-select-trigger" onclick="abrirBuscadorCuentas('cuenta_ingresos_exonerados')">
                    <span id="label-cuenta_ingresos_exonerados"><?= htmlspecialchars($cliente['cuenta_ingresos_exonerados'] ?: '-- Seleccione Cuenta Exonerados --', ENT_QUOTES, 'UTF-8') ?></span>
                    <i class="fa-solid fa-chevron-down" style="color: #64748b; font-size: 12px;"></i>
                </div>
            </div>
            <div class="form-group">
                <label>Estado del Cliente *</label>
                <select name="activo" class="form-control">
                    <option value="1" <?= ($cliente['activo'] ?? 1) == 1 ? 'selected' : '' ?>>Activo</option>
                    <option value="0" <?= ($cliente['activo'] ?? 1) == 0 ? 'selected' : '' ?>>Inactivo</option>
                </select>
            </div>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 15px; border-top: 1px solid #f1f5f9; padding-top: 15px;">
            <div class="checkbox-group">
                <input type="checkbox" name="exonerado_impuestos" id="exonerado_impuestos" value="1" <?= ($cliente['exonerado_impuestos'] ?? 0) == 1 ? 'checked' : '' ?> onchange="toggleExonerado(this.checked)">
                <label for="exonerado_impuestos">Exonerado de Impuestos</label>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" name="exportacion" id="exportacion" value="1" <?= ($cliente['exportacion'] ?? 0) == 1 ? 'checked' : '' ?>>
                <label for="exportacion">Cliente de Exportación</label>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" name="activar_prorroga_credito" id="activar_prorroga_credito" value="1" <?= ($cliente['activar_prorroga_credito'] ?? 0) == 1 ? 'checked' : '' ?>>
                <label for="activar_prorroga_credito">Activar Prórroga de Crédito</label>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" name="descuento_automatico" id="descuento_automatico" value="1" <?= ($cliente['descuento_automatico'] ?? 0) == 1 ? 'checked' : '' ?>>
                <label for="descuento_automatico">Descuento Automático</label>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" name="predeterminado_pos" id="predeterminado_pos" value="1" <?= ($cliente['predeterminado_pos'] ?? 0) == 1 ? 'checked' : '' ?>>
                <label for="predeterminado_pos">Predeterminado POS</label>
            </div>
            <div class="checkbox-group">
                <input type="checkbox" name="facturacion_correo" id="facturacion_correo" value="1" <?= ($cliente['facturacion_correo'] ?? 0) == 1 ? 'checked' : '' ?>>
                <label for="facturacion_correo">Facturación por Correo</label>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 3: PERSONA DE CONTACTO -->
    <div class="form-section-card">
        <h3 class="section-title"><i class="fa-solid fa-users"></i> Detalle de Persona de Contacto</h3>
        <div class="form-grid-2" style="margin-bottom: 20px;">
            <div class="form-group">
                <label>Nombre(s) del Contacto</label>
                <input type="text" name="contacto_nombre" value="<?= htmlspecialchars($cliente['contacto_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: Ana Teresa">
            </div>
            <div class="form-group">
                <label>Apellido(s) del Contacto</label>
                <input type="text" name="contacto_apellido" value="<?= htmlspecialchars($cliente['contacto_apellido'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: Ríos Mejía">
            </div>
        </div>
        <div class="form-grid-2">
            <div class="form-group">
                <label>Cargo del Contacto</label>
                <input type="text" name="contacto_cargo" value="<?= htmlspecialchars($cliente['contacto_cargo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: Gerente Administrativo">
            </div>
            <div class="form-group">
                <label>Correo Electrónico de Contacto</label>
                <input type="email" name="contacto_correo" value="<?= htmlspecialchars($cliente['contacto_correo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="form-control" placeholder="Ej: admon@empresa.com">
            </div>
        </div>
    </div>

    <!-- BOTONES DE ACCIÓN -->
    <div style="display: flex; gap: 15px; justify-content: flex-end;">
        <a href="/Cycsa/publico/clientes" class="btn-cycsa btn-cycsa-secondary">Cancelar</a>
        <button type="submit" class="btn-cycsa btn-cycsa-primary">Guardar Cambios</button>
    </div>
</form>

<!-- MODAL: BUSCADOR DE CUENTAS CONTABLES -->
<div id="panel-buscador-cuentas" class="modal-premium">
    <div class="modal-premium-content" style="width: 550px; max-height: 80vh; display: flex; flex-direction: column; box-sizing: border-box;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px;">
            <h4 style="margin: 0; font-weight: 700; color: #0f172a; font-size: 15px; font-family: 'Outfit', sans-serif;"><i class="fa-solid fa-magnifying-glass" style="color: var(--cycsa-azul);"></i> Seleccionar Cuenta Contable</h4>
            <button type="button" onclick="cerrarBuscadorCuentas()" style="background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8; line-height: 1;">&times;</button>
        </div>
        <input type="text" id="busqueda-cuenta-input" class="form-control" placeholder="Escriba código o nombre de la cuenta para buscar..." oninput="filtrarCuentasBusqueda(this.value)" style="margin-bottom: 15px;">
        <div id="lista-cuentas-resultados" style="flex: 1; overflow-y: auto; max-height: 300px; border: 1px solid #e2e8f0; border-radius: 6px; background: #f8fafc;">
            <!-- Cuentas inyectadas por JS -->
        </div>
    </div>
</div>

<script>
    const cuentasContables = <?= json_encode($cuentas_contables ?? []) ?>;
    let targetCuentaField = '';

    function abrirBuscadorCuentas(fieldName) {
        targetCuentaField = fieldName;
        document.getElementById('busqueda-cuenta-input').value = '';
        filtrarCuentasBusqueda('');
        document.getElementById('panel-buscador-cuentas').style.display = 'block';
        
        setTimeout(() => {
            document.getElementById('busqueda-cuenta-input').focus();
        }, 100);
    }

    function cerrarBuscadorCuentas() {
        document.getElementById('panel-buscador-cuentas').style.display = 'none';
    }

    function filtrarCuentasBusqueda(query) {
        const term = query.toLowerCase().trim();
        const listContainer = document.getElementById('lista-cuentas-resultados');
        listContainer.innerHTML = '';
        
        const filtered = cuentasContables.filter(cta => {
            return cta.codigo.toLowerCase().includes(term) || cta.nombre.toLowerCase().includes(term);
        });
        
        if (filtered.length === 0) {
            listContainer.innerHTML = '<div style="padding: 15px; color: #94a3b8; text-align: center; font-size: 13px; font-family: \'Inter\';">No se encontraron cuentas contables.</div>';
            return;
        }
        
        filtered.forEach(cta => {
            const valStr = cta.codigo + ' / ' + cta.nombre;
            const displayStr = cta.codigo + ' - ' + cta.nombre;
            
            const div = document.createElement('div');
            div.className = 'cuenta-option-item';
            div.textContent = displayStr;
            div.onclick = () => seleccionarCuentaContable(valStr);
            listContainer.appendChild(div);
        });
    }

    function seleccionarCuentaContable(value) {
        document.getElementById('input-' + targetCuentaField).value = value;
        document.getElementById('label-' + targetCuentaField).textContent = value || '-- Seleccione Cuenta --';
        cerrarBuscadorCuentas();
    }

    // Cerrar modal haciendo clic afuera
    window.addEventListener('click', (event) => {
        const modal = document.getElementById('panel-buscador-cuentas');
        if (event.target === modal) {
            cerrarBuscadorCuentas();
        }
    });

    function toggleTipoCliente(tipo) {
        const lblNombre = document.getElementById('lbl-nombre-cliente');
        const secApellidos = document.getElementById('seccion-apellidos');
        
        if (tipo === 'Natural') {
            lblNombre.textContent = "Nombre del Cliente *";
            secApellidos.style.display = 'grid';
        } else {
            lblNombre.textContent = "Nombre / Razón Social *";
            secApellidos.style.display = 'none';
        }
    }

    function toggleExonerado(isExonerado) {
        const secExonerado = document.getElementById('grupo-cuenta-exonerado');
        if (isExonerado) {
            secExonerado.style.display = 'flex';
        } else {
            secExonerado.style.display = 'none';
            document.getElementById('input-cuenta_ingresos_exonerados').value = '';
            document.getElementById('label-cuenta_ingresos_exonerados').textContent = '-- Seleccione Cuenta Exonerados --';
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        toggleTipoCliente(document.getElementById('tipo_cliente').value);
        toggleExonerado(document.getElementById('exonerado_impuestos').checked);
    });
</script>