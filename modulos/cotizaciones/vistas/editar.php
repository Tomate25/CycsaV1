<style>
    .seccion-form { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 20px; border-top: 4px solid var(--cycsa-azul); }
    .seccion-titulo { margin: 0 0 20px 0; color: #333; font-size: 18px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    
    .form-group { margin-bottom: 15px; }
    .form-label { display: block; margin-bottom: 8px; color: #495057; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-control { width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px; transition: border-color 0.2s; }
    .form-control:focus { outline: none; border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.1); }
    
    .tabla-detalles { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .tabla-detalles th { background: #f8f9fa; padding: 10px; text-align: left; font-size: 12px; color: #6c757d; text-transform: uppercase; border-bottom: 2px solid #dee2e6; }
    .tabla-detalles td { padding: 10px; border-bottom: 1px solid #e9ecef; vertical-align: top; }
    
    .btn-remover { color: #dc3545; background: none; border: none; cursor: pointer; font-size: 16px; padding: 5px; transition: color 0.2s; }
    .btn-remover:hover { color: #a71d2a; }
    
    .caja-totales { background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; width: 300px; margin-left: auto; }
    .fila-total { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 15px; color: #495057; }
    .fila-total.gran-total { font-size: 20px; font-weight: 700; color: var(--cycsa-azul); border-top: 2px solid #dee2e6; padding-top: 10px; margin-top: 10px; margin-bottom: 0; }

    /* Modal Estilos Premium */
    .modal-premium-bg { display: none; position: fixed; z-index: 2000; left: 0; top: 0; width: 100%; height: 100%; background-color: rgba(0,0,0,0.5); backdrop-filter: blur(4px); justify-content: center; align-items: center; }
    .modal-premium-content { background-color: white; padding: 25px; border-radius: 10px; width: 90%; max-width: 850px; max-height: 85vh; overflow: hidden; display: flex; flex-direction: column; box-shadow: 0 10px 25px rgba(0,0,0,0.15); animation: modalSlideIn 0.25s ease; }
    @keyframes modalSlideIn { from { transform: translateY(-20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
    .modal-header { display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #edf2f7; padding-bottom: 15px; margin-bottom: 15px; }
    .modal-title { font-size: 18px; font-weight: 700; color: #2d3748; margin: 0; }
    .modal-close { background: none; border: none; font-size: 24px; color: #a0aec0; cursor: pointer; transition: color 0.2s; line-height: 1; }
    .modal-close:hover { color: #4a5568; }
    .modal-search-wrapper { display: flex; gap: 15px; margin-bottom: 15px; }
    .modal-tabla-container { flex: 1; overflow-y: auto; border: 1px solid #e2e8f0; border-radius: 6px; margin-bottom: 20px; }
    .modal-tabla { width: 100%; border-collapse: collapse; font-size: 13px; text-align: left; }
    .modal-tabla th { position: sticky; top: 0; background: #f8fafc; padding: 12px 15px; text-align: left; color: #718096; border-bottom: 2px solid #e2e8f0; z-index: 10; font-weight: 700; text-transform: uppercase; }
    .modal-tabla td { padding: 12px 15px; border-bottom: 1px solid #edf2f7; color: #4a5568; vertical-align: middle; }
    .modal-tabla tr:hover { background-color: #f7fafc; }
    .modal-footer { display: flex; justify-content: flex-end; gap: 15px; border-top: 1px solid #edf2f7; padding-top: 15px; }
    .modal-tabla tr.selected-row { background-color: #e6f6ff !important; }
</style>

<div style="margin-bottom: 20px;">
    <a href="/Cycsa/publico/cotizaciones/detalle?id=<?= $cotizacion['id'] ?>" style="color: #6c757d; text-decoration: none; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Volver al Detalle</a>
</div>

<h2 style="margin: 0 0 20px 0; color: #333; font-size: 22px;">Editar Cotización <?= htmlspecialchars($cotizacion['codigo'], ENT_QUOTES, 'UTF-8') ?></h2>

<form action="/Cycsa/publico/cotizaciones/editar?id=<?= $cotizacion['id'] ?>" method="POST" id="form-cotizacion">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
    <input type="hidden" name="id" value="<?= $cotizacion['id'] ?>">

    <div class="seccion-form">
        <h3 class="seccion-titulo"><i class="fa-solid fa-address-card"></i> Datos Generales</h3>
        
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Cliente (Lectura)</label>
                <input type="text" class="form-control" value="<?= htmlspecialchars($cotizacion['cliente_nombre'] ?? '', ENT_QUOTES, 'UTF-8') ?>" readonly style="background: #e9ecef;">
            </div>
            
            <div class="form-group">
                <label class="form-label">Atención a (Contacto) *</label>
                <input type="text" name="atencion_a" class="form-control" value="<?= htmlspecialchars($cotizacion['atencion_a'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Nombre del Proyecto *</label>
                <input type="text" name="nombre_proyecto" class="form-control" value="<?= htmlspecialchars($cotizacion['nombre_proyecto'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
            
            <div class="form-group">
                <label class="form-label">Dirección Exacta del Proyecto *</label>
                <input type="text" name="direccion_proyecto" class="form-control" value="<?= htmlspecialchars($cotizacion['direccion_proyecto'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
        </div>
    </div>

    <div class="seccion-form">
        <h3 class="seccion-titulo"><i class="fa-solid fa-handshake"></i> Condiciones Comerciales</h3>
        
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Condición de Pago *</label>
                <select name="condicion_pago" class="form-control" required>
                    <option value="">Seleccionar...</option>
                    <?php 
                        $condiciones = [
                            "100% por adelantado",
                            "50% anticipo 50% contra entrega",
                            "40% anticipo 60% contra entrega",
                            "60% anticipo 40% contra entrega",
                            "100% contra entrega",
                            "Tramite de pago 7 días",
                            "Tramite de pago 15 días",
                            "Tramite de pago 30 días"
                        ];
                        foreach($condiciones as $cond):
                    ?>
                        <option value="<?= $cond ?>" <?= isset($cotizacion['condicion_pago']) && $cotizacion['condicion_pago'] === $cond ? 'selected' : '' ?>><?= $cond ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Tiempo de Entrega *</label>
                <input type="text" name="tiempo_entrega" class="form-control" value="<?= htmlspecialchars($cotizacion['tiempo_entrega'] ?? '', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>

            <div class="form-group">
                <label class="form-label">Vigencia de la Oferta *</label>
                <input type="text" name="vigencia_oferta" class="form-control" value="<?= htmlspecialchars($cotizacion['vigencia_oferta'] ?? '15 días calendario', ENT_QUOTES, 'UTF-8') ?>" required>
            </div>
        </div>
    </div>

    <div class="seccion-form">
        <h3 class="seccion-titulo"><i class="fa-solid fa-flask"></i> Detalle de Ensayos / Servicios</h3>
        
        <table class="tabla-detalles" id="tabla-ensayos">
            <thead>
                <tr>
                    <th style="width: 50%;">Descripción del Ensayo</th>
                    <th style="width: 15%;">Cantidad</th>
                    <th style="width: 20%;">Precio Unit. (C$)</th>
                    <th style="width: 10%;">Subtotal</th>
                    <th style="width: 5%; text-align: center;"><i class="fa-solid fa-trash"></i></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($detalles as $det): ?>
                <tr>
                    <td>
                        <input type="hidden" name="ensayo_id_producto[]" value="<?= htmlspecialchars($det['id_producto'] ?? '', ENT_QUOTES, 'UTF-8') ?>" class="prod-id-input">
                        <input type="text" name="ensayo_desc[]" class="form-control" value="<?= htmlspecialchars($det['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?>" required list="productos-datalist" onchange="completarPrecio(this)">
                    </td>
                    <td><input type="number" name="ensayo_cant[]" class="form-control cant-input" step="0.01" min="0.01" value="<?= $det['cantidad'] ?>" required oninput="calcularFila(this)"></td>
                    <td><input type="number" name="ensayo_precio[]" class="form-control precio-input" step="0.01" min="0" value="<?= $det['precio_unitario'] ?>" required oninput="calcularFila(this)"></td>
                    <td style="vertical-align: middle; font-weight: 600;" class="subtotal-texto">C$ <?= number_format($det['subtotal'], 2) ?></td>
                    <td style="text-align: center; vertical-align: middle;">
                        <button type="button" class="btn-remover" onclick="eliminarFila(this)"><i class="fa-solid fa-xmark"></i></button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>

        <button type="button" onclick="agregarFila()" style="background: #e9ecef; border: 1px solid #ced4da; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: 600; color: #495057; transition: background 0.2s;">
            <i class="fa-solid fa-plus"></i> Fila Vacía
        </button>
        <button type="button" onclick="abrirModalCatalogo()" style="background: var(--cycsa-azul); color: white; border: none; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; transition: background 0.2s; margin-left: 10px; box-shadow: 0 4px 6px rgba(16, 52, 135, 0.15);">
            <i class="fa-solid fa-magnifying-glass"></i> Buscar en Catálogo (221 Items)
        </button>

        <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
            <div class="caja-totales">
                <div class="fila-total">
                    <span>Subtotal:</span>
                    <span id="txt-subtotal">C$ 0.00</span>
                </div>
                <div class="fila-total">
                    <span>IVA (15%):</span>
                    <span id="txt-iva">C$ 0.00</span>
                </div>
                <div class="fila-total gran-total">
                    <span>TOTAL:</span>
                    <span id="txt-total">C$ 0.00</span>
                </div>
                
                <input type="hidden" name="subtotal_general" id="input-subtotal" value="<?= $cotizacion['subtotal'] ?>">
                <input type="hidden" name="impuesto_general" id="input-iva" value="<?= $cotizacion['impuesto'] ?>">
                <input type="hidden" name="total_general" id="input-total" value="<?= $cotizacion['total'] ?>">
            </div>
        </div>
    </div>

    <div style="display: flex; gap: 15px; justify-content: flex-end; margin-bottom: 50px;">
        <a href="/Cycsa/publico/cotizaciones/detalle?id=<?= $cotizacion['id'] ?>" style="padding: 12px 25px; border-radius: 4px; text-decoration: none; color: #6c757d; font-weight: 500; background: #f8f9fa; border: 1px solid #ddd;">Cancelar</a>
        <button type="submit" style="background: var(--cycsa-azul); color: white; border: none; padding: 12px 25px; border-radius: 4px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; font-size: 15px; box-shadow: 0 4px 6px rgba(16, 52, 135, 0.2);">
            <i class="fa-solid fa-save"></i> Guardar Cambios y Re-enviar
        </button>
    </div>
</form>

<script>
    const formatoMoneda = new Intl.NumberFormat('es-NI', { style: 'currency', currency: 'NIO' });

    function agregarFila() {
        const tbody = document.querySelector('#tabla-ensayos tbody');
        const nuevaFila = document.createElement('tr');
        
        nuevaFila.innerHTML = `
            <td>
                <input type="hidden" name="ensayo_id_producto[]" value="" class="prod-id-input">
                <input type="text" name="ensayo_desc[]" class="form-control" required placeholder="Descripción del ensayo" list="productos-datalist" onchange="completarPrecio(this)">
            </td>
            <td><input type="number" name="ensayo_cant[]" class="form-control cant-input" step="0.01" min="0.01" value="1" required oninput="calcularFila(this)"></td>
            <td><input type="number" name="ensayo_precio[]" class="form-control precio-input" step="0.01" min="0" value="0.00" required oninput="calcularFila(this)"></td>
            <td style="vertical-align: middle; font-weight: 600;" class="subtotal-texto">C$ 0.00</td>
            <td style="text-align: center; vertical-align: middle;">
                <button type="button" class="btn-remover" onclick="eliminarFila(this)"><i class="fa-solid fa-xmark"></i></button>
            </td>
        `;
        tbody.appendChild(nuevaFila);
        actualizarBotonesRemover();
    }

    function eliminarFila(boton) {
        const fila = boton.closest('tr');
        fila.remove();
        actualizarBotonesRemover();
        calcularTotalesGenerales();
    }

    function actualizarBotonesRemover() {
        const botones = document.querySelectorAll('.btn-remover');
        if (botones.length === 1) {
            botones[0].disabled = true;
            botones[0].style.opacity = '0.3';
        } else {
            botones.forEach(btn => {
                btn.disabled = false;
                btn.style.opacity = '1';
            });
        }
    }

    function calcularFila(input) {
        const fila = input.closest('tr');
        const cant = parseFloat(fila.querySelector('.cant-input').value) || 0;
        const precio = parseFloat(fila.querySelector('.precio-input').value) || 0;
        const subtotal = cant * precio;
        
        fila.querySelector('.subtotal-texto').textContent = formatoMoneda.format(subtotal).replace('NIO', 'C$');
        calcularTotalesGenerales();
    }

    function calcularTotalesGenerales() {
        let subtotalGeneral = 0;
        
        const filas = document.querySelectorAll('#tabla-ensayos tbody tr');
        filas.forEach(fila => {
            const cant = parseFloat(fila.querySelector('.cant-input').value) || 0;
            const precio = parseFloat(fila.querySelector('.precio-input').value) || 0;
            subtotalGeneral += (cant * precio);
        });

        const iva = subtotalGeneral * 0.15;
        const totalFinal = subtotalGeneral + iva;

        document.getElementById('txt-subtotal').textContent = formatoMoneda.format(subtotalGeneral).replace('NIO', 'C$');
        document.getElementById('txt-iva').textContent = formatoMoneda.format(iva).replace('NIO', 'C$');
        document.getElementById('txt-total').textContent = formatoMoneda.format(totalFinal).replace('NIO', 'C$');

        document.getElementById('input-subtotal').value = subtotalGeneral.toFixed(2);
        document.getElementById('input-iva').value = iva.toFixed(2);
        document.getElementById('input-total').value = totalFinal.toFixed(2);
    }

    function completarPrecio(input) {
        const fila = input.closest('tr');
        const valor = input.value.trim();
        const datalist = document.getElementById('productos-datalist');
        const opcion = Array.from(datalist.options).find(opt => opt.value === valor);
        
        if (opcion) {
            const idProd = opcion.getAttribute('data-id') || '';
            const idInput = fila.querySelector('.prod-id-input');
            if (idInput) idInput.value = idProd;
            
            const precio = parseFloat(opcion.getAttribute('data-precio')) || 0;
            const precioInput = fila.querySelector('.precio-input');
            precioInput.value = precio.toFixed(2);
            calcularFila(precioInput);
        } else {
            const idInput = fila.querySelector('.prod-id-input');
            if (idInput) idInput.value = '';
        }
    }

    // --- FUNCIONALIDADES DEL MODAL PREMIUM ---
    function abrirModalCatalogo() {
        document.getElementById('modal-catalogo').style.display = 'flex';
        document.getElementById('modal-search-input').focus();
    }

    function cerrarModalCatalogo() {
        document.getElementById('modal-catalogo').style.display = 'none';
        // Limpiar búsquedas
        document.getElementById('modal-search-input').value = '';
        document.getElementById('modal-filter-cat').value = '';
        filtrarProductosModal();
    }

    function filtrarProductosModal() {
        const query = document.getElementById('modal-search-input').value.toLowerCase().trim();
        const cat = document.getElementById('modal-filter-cat').value.toLowerCase().trim();
        const rows = document.querySelectorAll('#modal-tabla-productos tbody tr');
        
        rows.forEach(row => {
            const text = row.getAttribute('data-text') || '';
            const rowCat = row.getAttribute('data-cat') || '';
            
            const matchesQuery = text.includes(query);
            const matchesCat = cat === '' || rowCat === cat;
            
            if (matchesQuery && matchesCat) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function seleccionarTodosModal(masterCb) {
        const visibleCheckboxes = document.querySelectorAll('#modal-tabla-productos tbody tr:not([style*="display: none"]) .modal-prod-checkbox');
        visibleCheckboxes.forEach(cb => {
            cb.checked = masterCb.checked;
        });
        actualizarContadorModal();
    }

    function toggleFilaCheck(row, event) {
        if (event.target.classList.contains('modal-prod-checkbox')) {
            actualizarContadorModal();
            return;
        }
        const cb = row.querySelector('.modal-prod-checkbox');
        if (cb) {
            cb.checked = !cb.checked;
            actualizarContadorModal();
        }
    }

    function actualizarFilasSeleccionadas() {
        const rows = document.querySelectorAll('#modal-tabla-productos tbody tr');
        rows.forEach(row => {
            const cb = row.querySelector('.modal-prod-checkbox');
            if (cb && cb.checked) {
                row.classList.add('selected-row');
            } else {
                row.classList.remove('selected-row');
            }
        });
    }

    function actualizarContadorModal() {
        const count = document.querySelectorAll('.modal-prod-checkbox:checked').length;
        document.getElementById('modal-btn-add').textContent = `Añadir Seleccionados (${count})`;
        actualizarFilasSeleccionadas();
    }

    function escapeHtml(text) {
        return text
            .replace(/&/g, "&amp;")
            .replace(/</g, "&lt;")
            .replace(/>/g, "&gt;")
            .replace(/"/g, "&quot;")
            .replace(/'/g, "&#039;");
    }

    function agregarFilaConDatos(descripcion, precio, idProducto = '') {
        const tbody = document.querySelector('#tabla-ensayos tbody');
        
        // Si solo hay una fila y está vacía, la sobrescribimos
        const filas = tbody.querySelectorAll('tr');
        if (filas.length === 1) {
            const idInput = filas[0].querySelector('.prod-id-input');
            const descInput = filas[0].querySelector('input[name="ensayo_desc[]"]');
            const precioInput = filas[0].querySelector('input[name="ensayo_precio[]"]');
            if (descInput && descInput.value.trim() === "" && (parseFloat(precioInput.value) || 0) === 0) {
                if (idInput) idInput.value = idProducto;
                descInput.value = descripcion;
                precioInput.value = precio.toFixed(2);
                calcularFila(precioInput);
                return;
            }
        }
        
        const nuevaFila = document.createElement('tr');
        nuevaFila.innerHTML = `
            <td>
                <input type="hidden" name="ensayo_id_producto[]" value="${escapeHtml(idProducto.toString())}" class="prod-id-input">
                <input type="text" name="ensayo_desc[]" class="form-control" required placeholder="Descripción del ensayo" list="productos-datalist" onchange="completarPrecio(this)" value="${escapeHtml(descripcion)}">
            </td>
            <td><input type="number" name="ensayo_cant[]" class="form-control cant-input" step="0.01" min="0.01" value="1" required oninput="calcularFila(this)"></td>
            <td><input type="number" name="ensayo_precio[]" class="form-control precio-input" step="0.01" min="0" value="${precio.toFixed(2)}" required oninput="calcularFila(this)"></td>
            <td style="vertical-align: middle; font-weight: 600;" class="subtotal-texto">C$ ${formatoMoneda.format(precio).replace('NIO', 'C$')}</td>
            <td style="text-align: center; vertical-align: middle;">
                <button type="button" class="btn-remover" onclick="eliminarFila(this)"><i class="fa-solid fa-xmark"></i></button>
            </td>
        `;
        tbody.appendChild(nuevaFila);
        actualizarBotonesRemover();
        
        // Ejecutar los cálculos de fila
        const nuevoPrecioInput = nuevaFila.querySelector('.precio-input');
        calcularFila(nuevoPrecioInput);
    }

    function agregarSeleccionados() {
        const checkboxes = document.querySelectorAll('.modal-prod-checkbox:checked');
        if (checkboxes.length === 0) {
            cerrarModalCatalogo();
            return;
        }
        
        checkboxes.forEach(cb => {
            const id = cb.getAttribute('data-id') || '';
            const nombre = cb.getAttribute('data-nombre');
            const precio = parseFloat(cb.getAttribute('data-precio')) || 0;
            agregarFilaConDatos(nombre, precio, id);
            cb.checked = false; // reset
        });
        
        document.getElementById('modal-select-all').checked = false;
        actualizarContadorModal();
        cerrarModalCatalogo();
    }

    // Inicializar los cálculos al cargar
    document.addEventListener('DOMContentLoaded', () => {
        actualizarBotonesRemover();
        calcularTotalesGenerales();
    });
</script>

<datalist id="productos-datalist">
    <?php foreach ($productos as $prod): ?>
        <?php 
        $nombre = !empty($prod['nombre_comercial']) ? $prod['nombre_comercial'] : $prod['ensayo_servicio'];
        if (!empty($prod['codigo_servicio'])) {
            $nombre = $prod['codigo_servicio'] . ' - ' . $nombre;
        }
        ?>
        <option value="<?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>" data-precio="<?= $prod['precio'] ?>" data-id="<?= $prod['id'] ?>">
    <?php endforeach; ?>
</datalist>

<!-- MODAL DE BÚSQUEDA Y SELECCIÓN DE ENSAYOS -->
<div class="modal-premium-bg" id="modal-catalogo" style="display:none;">
    <div class="modal-premium-content">
        <div class="modal-header">
            <h4 class="modal-title"><i class="fa-solid fa-flask"></i> Buscar Ensayos y Servicios (Catálogo Cycsa)</h4>
            <button type="button" class="modal-close" onclick="cerrarModalCatalogo()">&times;</button>
        </div>
        <div class="modal-search-wrapper">
            <input type="text" id="modal-search-input" class="form-control" placeholder="Buscar por código, nombre de ensayo, norma ASTM..." oninput="filtrarProductosModal()">
            <select id="modal-filter-cat" class="form-control" style="max-width: 250px;" onchange="filtrarProductosModal()">
                <option value="">Todas las Matrices</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="modal-tabla-container">
            <table class="modal-tabla" id="modal-tabla-productos">
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;"><input type="checkbox" id="modal-select-all" onchange="seleccionarTodosModal(this)"></th>
                        <th style="width: 15%;">Código</th>
                        <th style="width: 50%;">Descripción / Ensayo</th>
                        <th style="width: 18%;">Matriz / Tipo</th>
                        <th style="width: 12%; text-align: right;">Precio</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $prod): ?>
                        <?php 
                        $nombre_completo = !empty($prod['nombre_comercial']) ? $prod['nombre_comercial'] : $prod['ensayo_servicio'];
                        $busqueda_val = strtolower($nombre_completo . ' ' . ($prod['codigo_servicio'] ?? '') . ' ' . ($prod['norma_astm'] ?? '') . ' ' . ($prod['matriz_tipo'] ?? ''));
                        
                        $nombre_opcion = $nombre_completo;
                        if (!empty($prod['codigo_servicio'])) {
                            $nombre_opcion = $prod['codigo_servicio'] . ' - ' . $nombre_completo;
                        }
                        ?>
                        <tr style="cursor: pointer;" onclick="toggleFilaCheck(this, event)" data-text="<?= htmlspecialchars($busqueda_val, ENT_QUOTES, 'UTF-8') ?>" data-cat="<?= htmlspecialchars(strtolower($prod['matriz_tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td style="text-align: center;">
                                <input type="checkbox" class="modal-prod-checkbox" data-id="<?= $prod['id'] ?>" data-nombre="<?= htmlspecialchars($nombre_opcion, ENT_QUOTES, 'UTF-8') ?>" data-precio="<?= $prod['precio'] ?>" onchange="actualizarContadorModal()">
                            </td>
                            <td style="font-family: monospace; font-weight: bold; color: #2d3748;"><?= htmlspecialchars($prod['codigo_servicio'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <strong style="color: #2d3748;"><?= htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if (!empty($prod['norma_astm'])): ?>
                                    <div style="font-size: 11px; color: var(--cycsa-azul); margin-top: 2px;"><i class="fa-solid fa-scroll"></i> Norma:  <?= htmlspecialchars($prod['norma_astm'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span style="background: #ebf8ff; color: #2b6cb0; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; display: inline-block;"><?= htmlspecialchars($prod['matriz_tipo'] ?? 'Otros', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td style="text-align: right; font-weight: bold; color: #2d3748;">C$ <?= number_format($prod['precio'], 2) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <button type="button" class="form-control" style="background: #f8f9fa; border: 1px solid #ddd; padding: 10px 20px; border-radius: 4px; color: #4a5568; font-size: 14px; font-weight: 600; width: auto; cursor: pointer;" onclick="cerrarModalCatalogo()">Cancelar</button>
            <button type="button" id="modal-btn-add" class="btn-premium-azul" style="padding: 10px 20px; font-size: 14px;" onclick="agregarSeleccionados()">Añadir Seleccionados (0)</button>
        </div>
    </div>
</div>