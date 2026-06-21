<style>
    .seccion-form { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 20px; border-top: 4px solid var(--cycsa-azul); }
    .seccion-titulo { margin: 0 0 20px 0; color: #333; font-size: 18px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    
    .form-group { margin-bottom: 15px; }
    .form-label { display: block; margin-bottom: 8px; color: #495057; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-control { width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px; transition: border-color 0.2s; }
    .form-control:focus { outline: none; border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.1); }
    
    /* Estilos para la tabla dinámica de ensayos */
    .tabla-detalles { width: 100%; border-collapse: collapse; margin-bottom: 15px; }
    .tabla-detalles th { background: #f8f9fa; padding: 10px; text-align: left; font-size: 12px; color: #6c757d; text-transform: uppercase; border-bottom: 2px solid #dee2e6; }
    .tabla-detalles td { padding: 10px; border-bottom: 1px solid #e9ecef; vertical-align: top; }
    
    .btn-remover { color: #dc3545; background: none; border: none; cursor: pointer; font-size: 16px; padding: 5px; transition: color 0.2s; }
    .btn-remover:hover { color: #a71d2a; }
    
    /* Checkboxes de Leyendas */
    .leyenda-item { display: flex; align-items: flex-start; gap: 10px; margin-bottom: 12px; background: #f8f9fa; padding: 12px; border-radius: 6px; border: 1px solid #e9ecef; }
    .leyenda-item input[type="checkbox"] { margin-top: 3px; cursor: pointer; width: 16px; height: 16px; }
    .leyenda-texto { font-size: 13px; color: #495057; line-height: 1.5; cursor: pointer; }
    .leyenda-titulo { font-weight: 600; color: #333; display: block; margin-bottom: 4px; }
    
    .caja-totales { background: #f8f9fa; padding: 20px; border-radius: 8px; border: 1px solid #dee2e6; width: 300px; margin-left: auto; }
    .fila-total { display: flex; justify-content: space-between; margin-bottom: 10px; font-size: 15px; color: #495057; }
    .fila-total.gran-total { font-size: 20px; font-weight: 700; color: var(--cycsa-azul); border-top: 2px solid #dee2e6; padding-top: 10px; margin-top: 10px; margin-bottom: 0; }
</style>

<div style="margin-bottom: 20px;">
    <a href="/Cycsa/publico/cotizaciones" style="color: #6c757d; text-decoration: none; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Volver a la lista</a>
</div>

<form action="/Cycsa/publico/cotizaciones/crear" method="POST" id="form-cotizacion">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <div class="seccion-form">
        <h3 class="seccion-titulo"><i class="fa-solid fa-address-card"></i> Datos Generales</h3>
        
        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Cliente *</label>
                <select name="id_cliente" class="form-control" required>
                    <option value="">Seleccione un cliente de la base de datos...</option>
                    <?php foreach ($clientes as $cli): ?>
                        <option value="<?= $cli['id'] ?>"><?= htmlspecialchars($cli['nombre_razon_social'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($cli['identificacion'], ENT_QUOTES, 'UTF-8') ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            
            <div class="form-group">
                <label class="form-label">Atención a (Contacto) *</label>
                <input type="text" name="atencion_a" class="form-control" required placeholder="Nombre de la persona que recibe la cotización">
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Nombre del Proyecto *</label>
                <input type="text" name="nombre_proyecto" class="form-control" required placeholder="Ej: Construcción Oficinas Centrales">
            </div>
            
            <div class="form-group">
                <label class="form-label">Dirección Exacta del Proyecto *</label>
                <input type="text" name="direccion_proyecto" class="form-control" required placeholder="Ubicación física del proyecto">
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
                    <option value="100% por adelantado">100% por adelantado</option>
                    <option value="50% anticipo 50% contra entrega">50% anticipo 50% contra entrega</option>
                    <option value="40% anticipo 60% contra entrega">40% anticipo 60% contra entrega</option>
                    <option value="60% anticipo 40% contra entrega">60% anticipo 40% contra entrega</option>
                    <option value="100% contra entrega">100% contra entrega</option>
                    <option value="Tramite de pago 7 días">Tramite de pago 7 días</option>
                    <option value="Tramite de pago 15 días">Tramite de pago 15 días</option>
                    <option value="Tramite de pago 30 días">Tramite de pago 30 días</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Tiempo de Entrega *</label>
                <input type="text" name="tiempo_entrega" class="form-control" required placeholder="Ej: 5 a 7 días hábiles">
            </div>

            <div class="form-group">
                <label class="form-label">Vigencia de la Oferta *</label>
                <input type="text" name="vigencia_oferta" class="form-control" required placeholder="Ej: 15 días calendario" value="15 días calendario">
            </div>
        </div>
        
        <div class="grid-2" style="margin-top: 10px;">
            <div class="form-group">
                <label class="form-label">Prioridad</label>
                <select name="prioridad" class="form-control">
                    <option value="Normal">Normal</option>
                    <option value="Media">Media</option>
                    <option value="Alta">Alta</option>
                </select>
            </div>
            <div class="form-group">
                <label class="form-label">Fecha Límite de Entrega (Opcional)</label>
                <input type="date" name="fecha_limite" class="form-control">
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
                <tr>
                    <td><input type="text" name="ensayo_desc[]" class="form-control" required placeholder="Ej: Resistencia a la compresión cilindros"></td>
                    <td><input type="number" name="ensayo_cant[]" class="form-control cant-input" step="0.01" min="0.01" value="1" required oninput="calcularFila(this)"></td>
                    <td><input type="number" name="ensayo_precio[]" class="form-control precio-input" step="0.01" min="0" value="0.00" required oninput="calcularFila(this)"></td>
                    <td style="vertical-align: middle; font-weight: 600;" class="subtotal-texto">C$ 0.00</td>
                    <td style="text-align: center; vertical-align: middle;">
                        <button type="button" class="btn-remover" onclick="eliminarFila(this)" disabled title="No puedes eliminar la primera fila"><i class="fa-solid fa-xmark"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>

        <button type="button" onclick="agregarFila()" style="background: #e9ecef; border: 1px solid #ced4da; padding: 8px 15px; border-radius: 4px; cursor: pointer; font-weight: 600; color: #495057;">
            <i class="fa-solid fa-plus"></i> Agregar Ensayo
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
                
                <input type="hidden" name="subtotal_general" id="input-subtotal" value="0">
                <input type="hidden" name="impuesto_general" id="input-iva" value="0">
                <input type="hidden" name="total_general" id="input-total" value="0">
            </div>
        </div>
    </div>

    <div class="seccion-form">
        <h3 class="seccion-titulo"><i class="fa-solid fa-clipboard-list"></i> Leyendas y Notas Fijas (Para PDF)</h3>
        
        <label class="leyenda-item">
            <input type="checkbox" name="notas[concreto]" value="1">
            <span class="leyenda-texto">
                <span class="leyenda-titulo">Muestreo de Concreto (Cilindros)</span>
                Añade la leyenda sobre entrega de cilindros identificados (Nombre, Ubicación, Resistencia, Revenimiento) y dimensiones estándar CYCSA-PE-07 (4"x8" o 6"x12").
            </span>
        </label>

        <label class="leyenda-item">
            <input type="checkbox" name="notas[trae_muestra]" value="1">
            <span class="leyenda-texto">
                <span class="leyenda-titulo">Cliente trae muestra a laboratorio</span>
                Añade: "Cliente trae las muestras a Laboratorio CYCSA Km 83.5 Carretera León-Managua."
            </span>
        </label>

        <label class="leyenda-item">
            <input type="checkbox" name="notas[laboratorio_lleno]" value="1">
            <span class="leyenda-texto">
                <span class="leyenda-titulo">Condicionante de Tiempo (Laboratorio lleno)</span>
                Añade: "Los tiempos aplican a partir del ingreso... La disponibilidad deberá ser consultada al momento de la entrega."
            </span>
        </label>
        
        <label class="leyenda-item">
            <input type="checkbox" name="notas[minimo_muestreo]" value="1">
            <span class="leyenda-texto">
                <span class="leyenda-titulo">Programación de Muestreo Extra</span>
                Añade: "Mínimo para programar muestreo C$4,400.00 más movilización. Programación 2 días de anticipación."
            </span>
        </label>
    </div>

    <div style="display: flex; gap: 15px; justify-content: flex-end; margin-bottom: 50px;">
        <a href="/Cycsa/publico/cotizaciones" style="padding: 12px 25px; border-radius: 4px; text-decoration: none; color: #6c757d; font-weight: 500; background: #f8f9fa; border: 1px solid #ddd;">Cancelar</a>
        <button type="submit" style="background: var(--cycsa-azul); color: white; border: none; padding: 12px 25px; border-radius: 4px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; font-size: 15px; box-shadow: 0 4px 6px rgba(16, 52, 135, 0.2);">
            <i class="fa-solid fa-save"></i> Guardar Cotización
        </button>
    </div>
</form>

<script>
    // Formateador de moneda nicaragüense (C$)
    const formatoMoneda = new Intl.NumberFormat('es-NI', { style: 'currency', currency: 'NIO' });

    function agregarFila() {
        const tbody = document.querySelector('#tabla-ensayos tbody');
        const nuevaFila = document.createElement('tr');
        
        nuevaFila.innerHTML = `
            <td><input type="text" name="ensayo_desc[]" class="form-control" required placeholder="Descripción del ensayo"></td>
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
        calcularTotalesGenerales(); // Recalcular todo al borrar
    }

    function actualizarBotonesRemover() {
        const botones = document.querySelectorAll('.btn-remover');
        // Si solo hay 1 fila, deshabilitar el botón de borrar
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
        
        // Mostrar el subtotal en la fila actual
        fila.querySelector('.subtotal-texto').textContent = formatoMoneda.format(subtotal).replace('NIO', 'C$');
        
        // Actualizar la caja fuerte final
        calcularTotalesGenerales();
    }

    function calcularTotalesGenerales() {
        let subtotalGeneral = 0;
        
        // Sumar todas las filas
        const filas = document.querySelectorAll('#tabla-ensayos tbody tr');
        filas.forEach(fila => {
            const cant = parseFloat(fila.querySelector('.cant-input').value) || 0;
            const precio = parseFloat(fila.querySelector('.precio-input').value) || 0;
            subtotalGeneral += (cant * precio);
        });

        // Impuesto (15% IVA estándar)
        const iva = subtotalGeneral * 0.15;
        const totalFinal = subtotalGeneral + iva;

        // Mostrar en pantalla (Visual)
        document.getElementById('txt-subtotal').textContent = formatoMoneda.format(subtotalGeneral).replace('NIO', 'C$');
        document.getElementById('txt-iva').textContent = formatoMoneda.format(iva).replace('NIO', 'C$');
        document.getElementById('txt-total').textContent = formatoMoneda.format(totalFinal).replace('NIO', 'C$');

        // Guardar en campos ocultos para mandarlos por POST a PHP
        document.getElementById('input-subtotal').value = subtotalGeneral.toFixed(2);
        document.getElementById('input-iva').value = iva.toFixed(2);
        document.getElementById('input-total').value = totalFinal.toFixed(2);
    }
</script>