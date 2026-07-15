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
    
    .caja-totales { background: white; padding: 22px; border-radius: 12px; border: 1px solid #e2e8f0; width: 320px; margin-left: auto; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05), 0 2px 4px -1px rgba(0,0,0,0.025); }
    .fila-total { display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14.5px; color: #334155; }
    .fila-total.gran-total { font-size: 19px; font-weight: 700; color: var(--cycsa-azul); border-top: 2px solid #e2e8f0; padding-top: 14px; margin-top: 14px; margin-bottom: 0; }

    /* Switch Toggle Slider */
    .switch-toggle {
        position: relative;
        display: inline-block;
        width: 44px;
        height: 22px;
    }
    .switch-toggle input {
        opacity: 0;
        width: 0;
        height: 0;
    }
    .slider-toggle {
        position: absolute;
        cursor: pointer;
        top: 0;
        left: 0;
        right: 0;
        bottom: 0;
        background-color: #cbd5e1;
        transition: .3s;
        border-radius: 22px;
    }
    .slider-toggle:before {
        position: absolute;
        content: "";
        height: 16px;
        width: 16px;
        left: 3px;
        bottom: 3px;
        background-color: white;
        transition: .3s;
        border-radius: 50%;
    }
    .switch-toggle input:checked + .slider-toggle {
        background-color: var(--cycsa-azul);
    }
    .switch-toggle input:checked + .slider-toggle:before {
        transform: translateX(22px);
    }

    /* Moneda Toggle buttons */
    .moneda-toggle-group {
        display: flex;
        background: #f1f5f9;
        padding: 3px;
        border-radius: 6px;
        border: 1px solid #e2e8f0;
    }
    .btn-toggle-moneda {
        border: none;
        background: none;
        padding: 5px 12px;
        font-size: 12.5px;
        font-weight: 600;
        color: #64748b;
        border-radius: 4px;
        cursor: pointer;
        transition: all 0.2s;
    }
    .btn-toggle-moneda.active {
        background: white;
        color: var(--cycsa-azul);
        box-shadow: 0 1px 3px rgba(0,0,0,0.08);
    }


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
    
    /* Estilos AJAX Cliente */
    .cliente-ajax-wrapper { position: relative; }
    .cliente-item-card { padding: 10px 15px; display: flex; align-items: center; gap: 12px; cursor: pointer; transition: all 0.2s ease; border-bottom: 1px solid #f1f5f9; }
    .cliente-item-card:last-child { border-bottom: none; }
    .cliente-item-card:hover { background-color: #f8fafc; }
    .cliente-avatar-mini { width: 32px; height: 32px; border-radius: 50%; background: #e0f2fe; color: #0369a1; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 12px; flex-shrink: 0; transition: all 0.2s; }
    .cliente-item-card:hover .cliente-avatar-mini { background: var(--cycsa-azul); color: white; }
    .cliente-info-mini { display: flex; flex-direction: column; min-width: 0; }
    .cliente-nombre-mini { font-weight: 600; color: #1e293b; font-size: 13.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .cliente-ruc-mini { font-size: 11px; color: #64748b; margin-top: 1px; }
    .cliente-no-results { padding: 12px 15px; color: #94a3b8; font-size: 13.5px; text-align: center; }
    #cliente-clear:hover { color: #dc3545 !important; }
    #btn-cambiar-cliente:hover { background: #f1f5f9 !important; border-color: #cbd5e1 !important; color: #0f172a !important; }
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
                <div class="cliente-ajax-wrapper">
                    <!-- Contenedor del Buscador -->
                    <div id="cliente-search-container" style="position: relative; cursor: pointer;" onclick="abrirModalClientes()">
                        <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
                        <input type="text" id="cliente-search-placeholder" class="form-control" placeholder="Haga clic aquí para buscar cliente..." readonly style="cursor: pointer; padding-left: 40px; background: white;" required>
                    </div>
                    
                    <!-- Tarjeta de Cliente Seleccionado (Layout Premium) -->
                    <div id="cliente-seleccionado-card" style="display: none; align-items: center; justify-content: space-between; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 6px; padding: 12px 18px; margin-top: 5px; box-shadow: 0 4px 12px rgba(0,0,0,0.03); border-left: 4px solid var(--cycsa-azul);">
                        <div style="display: flex; align-items: center; gap: 15px; min-width: 0;">
                            <div id="cliente-card-avatar" style="width: 38px; height: 38px; border-radius: 50%; background: var(--cycsa-azul); color: white; display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 14px; flex-shrink: 0;">CL</div>
                            <div style="min-width: 0;">
                                <div id="cliente-card-nombre" style="font-weight: 700; color: #1e293b; font-size: 14.5px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">Nombre Cliente</div>
                                <div id="cliente-card-ruc" style="font-size: 11.5px; color: #64748b; margin-top: 2px;"><i class="fa-solid fa-id-card"></i> RUC: 123456</div>
                            </div>
                        </div>
                        <button type="button" id="btn-cambiar-cliente" style="background: white; border: 1px solid #cbd5e1; color: #475569; padding: 6px 12px; border-radius: 6px; cursor: pointer; font-size: 12px; font-weight: 600; transition: all 0.2s; display: flex; align-items: center; gap: 6px; flex-shrink: 0;">
                            <i class="fa-solid fa-arrows-rotate"></i> Cambiar
                        </button>
                    </div>

                    <input type="hidden" name="id_cliente" id="id_cliente" value="" required>
                </div>
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
                    <?php foreach ($condiciones_pago as $item): ?>
                        <option value="<?= htmlspecialchars($item['valor'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($item['valor'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Tiempo de Entrega *</label>
                <input type="text" name="tiempo_entrega" class="form-control" required placeholder="Ej: 5 a 7 días hábiles" list="tiempos-entrega-datalist">
                <datalist id="tiempos-entrega-datalist">
                    <?php foreach ($tiempos_entrega as $item): ?>
                        <option value="<?= htmlspecialchars($item['valor'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>

            <div class="form-group">
                <label class="form-label">Vigencia de la Oferta *</label>
                <input type="text" name="vigencia_oferta" class="form-control" required placeholder="Ej: 15 días calendario" value="15 días calendario" list="vigencias-oferta-datalist">
                <datalist id="vigencias-oferta-datalist">
                    <?php foreach ($vigencias_oferta as $item): ?>
                        <option value="<?= htmlspecialchars($item['valor'], ENT_QUOTES, 'UTF-8') ?>">
                    <?php endforeach; ?>
                </datalist>
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

        <div class="grid-2" style="margin-top: 10px;">
            <div class="form-group">
                <label class="form-label">Fecha de Entrega (Operaciones)</label>
                <input type="date" name="fecha_entrega" class="form-control">
            </div>
            <div class="form-group">
                <label class="form-label">Fecha de Seguimiento (Operaciones)</label>
                <input type="date" name="fecha_seguimiento" class="form-control">
            </div>
        </div>
    </div>

    <div class="seccion-form">
        <h3 class="seccion-titulo"><i class="fa-solid fa-flask"></i> Detalle de Ensayos / Servicios</h3>
        
        <table class="tabla-detalles" id="tabla-ensayos">
            <thead>
                <tr>
                    <th style="width: 48%;">Nombre Comercial / Descripción</th>
                    <th style="width: 20%;">Tiempo Entrega/Obs</th>
                    <th style="width: 8%;">Cantidad</th>
                    <th style="width: 10%;" id="th-precio-header">Precio Unit. (C$)</th>
                    <th style="width: 10%;" id="th-subtotal-header">Subtotal (C$)</th>
                    <th style="width: 4%; text-align: center;"><i class="fa-solid fa-trash"></i></th>
                </tr>
            </thead>
            <tbody>
                <tr>
                    <td>
                        <input type="hidden" name="ensayo_codigo[]" class="form-control spec-codigo" placeholder="Ej: CYC-01">
                        <input type="hidden" name="ensayo_norma[]" class="form-control spec-norma" placeholder="Ej: ASTM C39">
                        <input type="hidden" name="ensayo_formato[]" class="form-control spec-formato" placeholder="Ej: FR-CONC-01">
                        <input type="hidden" name="ensayo_id_producto[]" value="" class="prod-id-input">
                        <input type="text" name="ensayo_desc[]" class="form-control" required placeholder="Nombre Comercial..." list="productos-datalist" onchange="completarPrecio(this)">
                        <div style="margin-top: 3px; font-size: 10px;">
                            <span class="badge-tipo-row" style="font-weight: bold; padding: 1px 4px; border-radius: 3px; background: #fef3c7; color: #d97706; transition: all 0.2s;">
                                <i class="fa-solid fa-pen-fancy"></i> Campo Libre
                            </span>
                        </div>
                    </td>
                    <td>
                        <input type="text" name="ensayo_obs[]" class="form-control spec-obs" placeholder="Tiempo de entrega...">
                    </td>
                    <td><input type="number" name="ensayo_cant[]" class="form-control cant-input" step="0.01" min="0.01" value="1" required oninput="calcularFila(this)"></td>
                    <td><input type="number" name="ensayo_precio[]" class="form-control precio-input" step="0.01" min="0" value="0.00" required oninput="calcularFila(this)"></td>
                    <td style="vertical-align: middle; font-weight: 600;" class="subtotal-texto">C$ 0.00</td>
                    <td style="text-align: center; vertical-align: middle;">
                        <button type="button" class="btn-remover" onclick="eliminarFila(this)" disabled title="No puedes eliminar la primera fila"><i class="fa-solid fa-xmark"></i></button>
                    </td>
                </tr>
            </tbody>
        </table>

        <button type="button" onclick="abrirModalCatalogo()" style="background: var(--cycsa-azul); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; transition: all 0.2s; box-shadow: 0 4px 6px rgba(16, 52, 135, 0.15); display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-magnifying-glass"></i> Buscar en Catálogo (221 Items)
        </button>
        <button type="button" onclick="agregarFilaPersonalizada()" style="background: #f8fafc; border: 1px dashed #cbd5e1; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 600; color: #475569; font-family: 'Inter', sans-serif; transition: all 0.2s; margin-left: 10px; display: inline-flex; align-items: center; gap: 8px;">
            <i class="fa-solid fa-pen-fancy"></i> + Ensayo Personalizado (Campo Libre)
        </button>

        <div style="display: flex; justify-content: flex-end; margin-top: 20px;">
            <input type="hidden" name="tipo_moneda" id="input-tipo-moneda" value="1">
            <div class="caja-totales">

                <!-- Subtotal (Precio Base) -->
                <div class="fila-total" style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14.5px; color: #334155;">
                    <span style="font-weight: 500;">Precio Base (Subtotal):</span>
                    <span id="txt-subtotal" style="font-weight: 600; color: #0f172a;">C$ 0.00</span>
                </div>

                <!-- Descuento -->
                <div class="fila-total" style="align-items: center; justify-content: space-between; margin-bottom: 12px; padding-bottom: 12px; border-bottom: 1px dashed #e2e8f0;">
                    <span style="font-weight: 500; color: #334155; font-size: 14.5px;">Descuento:</span>
                    <div style="display: flex; gap: 6px; align-items: center;">
                        <input type="number" id="input-descuento-val" step="0.01" min="0" value="0.00" style="width: 80px; text-align: right; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-family: inherit; font-size: 13.5px; font-weight: 500; color: #1e293b;">
                        <select id="input-descuento-tipo" style="width: 70px; padding: 6px 10px; border-radius: 6px; border: 1px solid #cbd5e1; font-family: inherit; font-size: 13px; font-weight: 600; color: #475569; cursor: pointer; background-color: white;">
                            <option value="monto">C$</option>
                            <option value="porcentaje">%</option>
                        </select>
                    </div>
                </div>

                <!-- Descuento Calculado (Monto restado) -->
                <div class="fila-total" style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 13.5px; color: #dc2626;">
                    <span style="font-weight: 500;">Monto Descontado:</span>
                    <span id="txt-descuento-calculado" style="font-weight: 600;">-C$ 0.00</span>
                </div>

                <!-- Precio con Descuento -->
                <div class="fila-total" style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14.5px; color: #334155; background-color: #f8fafc; padding: 6px 10px; border-radius: 6px; border: 1px solid #e2e8f0;">
                    <span style="font-weight: 600;">Precio con Descuento:</span>
                    <span id="txt-neto" style="font-weight: 700; color: #0f172a;">C$ 0.00</span>
                </div>

                <!-- IVA -->
                <div class="fila-total" style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14.5px; color: #334155;">
                    <span style="font-weight: 500;">IVA (15%):</span>
                    <span id="txt-iva" style="font-weight: 600; color: #0f172a;">C$ 0.00</span>
                </div>

                <!-- Exonerado Toggle -->
                <div class="fila-total" style="align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 12px;">
                    <span style="font-weight: 500; color: #334155; font-size: 14.5px;">¿Exonerado de IVA?</span>
                    <label class="switch-toggle">
                        <input type="checkbox" name="exonerado" id="chk-exonerado" value="1">
                        <span class="slider-toggle"></span>
                    </label>
                </div>

                <!-- N° Exoneración -->
                <div id="wrapper-exoneracion" class="fila-total" style="display: none; align-items: center; justify-content: space-between; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; margin-bottom: 12px;">
                    <span style="font-weight: 500; color: #475569; font-size: 12.5px;">N° Exoneración:</span>
                    <input type="text" name="exoneracion_no" id="input-exoneracion-no" placeholder="Código / Aval" style="width: 140px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 6px 10px; font-family: inherit; font-size: 13px; color: #1e293b;">
                </div>

                <!-- IVA -->
                <div class="fila-total" style="display: flex; justify-content: space-between; margin-bottom: 12px; font-size: 14.5px; color: #334155;">
                    <span style="font-weight: 500;">IVA (15%):</span>
                    <span id="txt-iva" style="font-weight: 600; color: #0f172a;">C$ 0.00</span>
                </div>

                <!-- TOTAL -->
                <div class="fila-total gran-total">
                    <span>TOTAL:</span>
                    <span id="txt-total">C$ 0.00</span>
                </div>
                
                <input type="hidden" name="subtotal_general" id="input-subtotal" value="0.00">
                <input type="hidden" name="descuento" id="input-descuento-final" value="0.00">
                <input type="hidden" name="impuesto_general" id="input-iva" value="0.00">
                <input type="hidden" name="total_general" id="input-total" value="0.00">
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

        <div style="margin-top: 15px;">
            <label class="form-label" style="font-weight: 600; margin-bottom: 6px; display: block;"><i class="fa-solid fa-address-book"></i> Contactos del Proyecto (Se mostrarán en el PDF)</label>
            <textarea name="contactos" class="form-control" rows="8" style="width: 100%; border: 1px solid #cbd5e1; border-radius: 4px; padding: 10px; font-family: inherit; font-size: 13px; resize: vertical;"></textarea>
        </div>
    </div>

    <div style="display: flex; gap: 15px; justify-content: flex-end; margin-bottom: 50px;">
        <a href="/Cycsa/publico/cotizaciones" style="padding: 12px 25px; border-radius: 4px; text-decoration: none; color: #6c757d; font-weight: 500; background: #f8f9fa; border: 1px solid #ddd;">Cancelar</a>
        <button type="submit" style="background: var(--cycsa-azul); color: white; border: none; padding: 12px 25px; border-radius: 4px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; font-size: 15px; box-shadow: 0 4px 6px rgba(16, 52, 135, 0.2);">
            <i class="fa-solid fa-save"></i> Guardar Cotización
        </button>
    </div>
</form>

<script>
    // Gestión del Modal de Clientes y Selección Premium
    function abrirModalClientes() {
        document.getElementById('modal-clientes').style.display = 'flex';
        document.getElementById('modal-cliente-search-input').focus();
    }

    function cerrarModalClientes() {
        document.getElementById('modal-clientes').style.display = 'none';
        document.getElementById('modal-cliente-search-input').value = '';
        filtrarClientesModal();
    }

    function filtrarClientesModal() {
        const query = document.getElementById('modal-cliente-search-input').value.toLowerCase().trim();
        const rows = document.querySelectorAll('#modal-tabla-clientes tbody tr');
        
        rows.forEach(row => {
            const text = row.getAttribute('data-text') || '';
            if (text.includes(query)) {
                row.style.display = '';
            } else {
                row.style.display = 'none';
            }
        });
    }

    function seleccionarClienteDesdeModal(id, nombre, ruc, moneda, exonerado, descAuto, porcentajeDesc) {
        const idInput = document.getElementById('id_cliente');
        const searchContainer = document.getElementById('cliente-search-container');
        const selectedCard = document.getElementById('cliente-seleccionado-card');
        const cardNombre = document.getElementById('cliente-card-nombre');
        const cardRuc = document.getElementById('cliente-card-ruc');
        const cardAvatar = document.getElementById('cliente-card-avatar');

        function getInitials(name) {
            return name
                .split(' ')
                .filter(n => n.length > 0)
                .map(n => n[0])
                .join('')
                .substring(0, 2)
                .toUpperCase();
        }

        idInput.value = id;
        cardNombre.textContent = nombre;
        cardRuc.innerHTML = `<i class="fa-solid fa-id-card"></i> RUC/ID: ${ruc}`;
        cardAvatar.textContent = getInitials(nombre);
        
        searchContainer.style.display = 'none';
        selectedCard.style.display = 'flex';

        // Auto-seleccionar Moneda asignada al cliente
        cambiarMoneda(moneda || 1);

        // Auto-seleccionar Exoneración de IVA si el cliente está exonerado
        const chkExonerado = document.getElementById('chk-exonerado');
        if (chkExonerado) {
            chkExonerado.checked = (exonerado === 1);
            // Disparar evento change para recalcular e invocar efectos de la vista
            const eventChange = new Event('change');
            chkExonerado.dispatchEvent(eventChange);
        }

        // Auto-aplicar Descuento si está activado
        const inputDescVal = document.getElementById('input-descuento-val');
        const inputDescTipo = document.getElementById('input-descuento-tipo');
        if (inputDescVal && inputDescTipo) {
            if (descAuto === 1 && porcentajeDesc > 0) {
                inputDescTipo.value = 'porcentaje';
                inputDescVal.value = parseFloat(porcentajeDesc).toFixed(2);
            } else {
                inputDescVal.value = '0.00';
                inputDescTipo.value = 'monto';
            }
            // Disparar evento input para recalcular
            const eventInput = new Event('input');
            inputDescVal.dispatchEvent(eventInput);
        }

        // Recalcular totales
        calcularTotalesGenerales();
        
        cerrarModalClientes();
    }


    document.addEventListener('DOMContentLoaded', function() {
        const btnCambiar = document.getElementById('btn-cambiar-cliente');
        const idInput = document.getElementById('id_cliente');
        const searchContainer = document.getElementById('cliente-search-container');
        const selectedCard = document.getElementById('cliente-seleccionado-card');

        btnCambiar.addEventListener('click', function() {
            idInput.value = '';
            selectedCard.style.display = 'none';
            searchContainer.style.display = 'block';
        });

        // Event listeners para descuentos y exoneración de IVA
        const inputDesc = document.getElementById('input-descuento-val');
        const inputDescTipo = document.getElementById('input-descuento-tipo');
        if (inputDesc) {
            inputDesc.addEventListener('focus', function() {
                if (parseFloat(this.value) === 0) {
                    this.value = '';
                }
            });
            inputDesc.addEventListener('blur', function() {
                if (this.value.trim() === '') {
                    this.value = '0.00';
                } else {
                    this.value = parseFloat(this.value).toFixed(2);
                }
            });
            inputDesc.addEventListener('input', function() {
                calcularTotalesGenerales();
            });
        }
        if (inputDescTipo) {
            inputDescTipo.addEventListener('change', function() {
                calcularTotalesGenerales();
            });
        }

        const chkExonerado = document.getElementById('chk-exonerado');
        const wrapperEx = document.getElementById('wrapper-exoneracion');
        const inputEx = document.getElementById('input-exoneracion-no');
        if (chkExonerado && wrapperEx && inputEx) {
            chkExonerado.addEventListener('change', function() {
                if (this.checked) {
                    wrapperEx.style.display = 'flex';
                    inputEx.setAttribute('required', 'required');
                    inputEx.focus(); // Focus automatically!
                } else {
                    wrapperEx.style.display = 'none';
                    inputEx.removeAttribute('required');
                    inputEx.value = '';
                }
                calcularTotalesGenerales();
            });
        }
    });

    // Formateador de moneda nicaragüense (C$) e Internacional ($)
    const formatoMoneda = new Intl.NumberFormat('es-NI', { style: 'currency', currency: 'NIO' });
    function formatearMonto(monto) {
        const tipoMoneda = parseInt(document.getElementById('input-tipo-moneda').value || '1');
        if (tipoMoneda === 2) {
            const formatter = new Intl.NumberFormat('en-US', { style: 'currency', currency: 'USD' });
            return formatter.format(monto).replace('USD', '$');
        } else {
            return formatoMoneda.format(monto).replace('NIO', 'C$');
        }
    }

    function cambiarMoneda(tipo) {
        tipo = 1; // Forzar siempre C$ (Córdobas)
        document.getElementById('input-tipo-moneda').value = tipo;
        
        // Actualizar clase activa en los botones de moneda
        document.querySelectorAll('.btn-toggle-moneda').forEach(btn => {
            if (parseInt(btn.getAttribute('data-value')) === tipo) {
                btn.classList.add('active');
            } else {
                btn.classList.remove('active');
            }
        });
        
        // Cambiar leyenda de la opción fija de descuento
        const discountSelect = document.getElementById('input-descuento-tipo');
        if (discountSelect) {
            const fixedOption = discountSelect.querySelector('option[value="monto"]');
            if (fixedOption) {
                fixedOption.textContent = (tipo === 2) ? '$' : 'C$';
            }
        }

        // Cambiar símbolos de las columnas
        const symbol = (tipo === 2) ? '$' : 'C$';
        const thPrecio = document.getElementById('th-precio-header');
        if (thPrecio) thPrecio.textContent = `Precio Unit. (${symbol})`;
        const thSubtotal = document.getElementById('th-subtotal-header');
        if (thSubtotal) thSubtotal.textContent = `Subtotal (${symbol})`;
        
        // Recalcular todos los subtotales de fila existentes
        const filas = document.querySelectorAll('#tabla-ensayos tbody tr');
        filas.forEach(fila => {
            const precioInput = fila.querySelector('.precio-input');
            if (precioInput) {
                calcularFila(precioInput);
            }
        });
        
        // Recalcular totales generales
        calcularTotalesGenerales();
    }

    function toggleTechDetails(link) {
        const fieldsDiv = link.closest('td').querySelector('.technical-details-fields');
        if (fieldsDiv.style.display === 'none') {
            fieldsDiv.style.display = 'block';
            link.innerHTML = '<i class="fa-solid fa-circle-chevron-up"></i> Ocultar info técnica';
        } else {
            fieldsDiv.style.display = 'none';
            link.innerHTML = '<i class="fa-solid fa-circle-info"></i> Info técnica adicional (Opcional)';
        }
    }

    function agregarFila(expandTechDetails = false) {
        const tbody = document.querySelector('#tabla-ensayos tbody');
        const nuevaFila = document.createElement('tr');
        
        nuevaFila.innerHTML = `
            <td>
                <input type="hidden" name="ensayo_codigo[]" class="form-control spec-codigo" placeholder="Ej: CYC-01">
                <input type="hidden" name="ensayo_norma[]" class="form-control spec-norma" placeholder="Ej: ASTM C39">
                <input type="hidden" name="ensayo_formato[]" class="form-control spec-formato" placeholder="Ej: FR-CONC-01">
                <input type="hidden" name="ensayo_id_producto[]" value="" class="prod-id-input">
                <input type="text" name="ensayo_desc[]" class="form-control" required placeholder="Nombre Comercial..." list="productos-datalist" onchange="completarPrecio(this)">
                <div style="margin-top: 3px; font-size: 10px;">
                    <span class="badge-tipo-row" style="font-weight: bold; padding: 1px 4px; border-radius: 3px; background: #fef3c7; color: #d97706; transition: all 0.2s;">
                        <i class="fa-solid fa-pen-fancy"></i> Campo Libre
                    </span>
                </div>
            </td>
            <td>
                <input type="text" name="ensayo_obs[]" class="form-control spec-obs" placeholder="Tiempo de entrega...">
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

        if (expandTechDetails) {
            setTimeout(() => {
                const descInput = nuevaFila.querySelector('input[name="ensayo_desc[]"]');
                if (descInput) descInput.focus();
            }, 50);
        }
    }

    function agregarFilaPersonalizada() {
        agregarFila(true);
    }

    function eliminarFila(boton) {
        const tbody = document.querySelector('#tabla-ensayos tbody');
        const filas = tbody.querySelectorAll('tr');
        if (filas.length === 1) {
            // Si es la única fila, reseteamos todos sus valores
            const fila = filas[0];
            const idInput = fila.querySelector('.prod-id-input');
            const descInput = fila.querySelector('input[name="ensayo_desc[]"]');
            const precioInput = fila.querySelector('input[name="ensayo_precio[]"]');
            const cantInput = fila.querySelector('.cant-input');
            const codigoInput = fila.querySelector('.spec-codigo');
            const normaInput = fila.querySelector('.spec-norma');
            const formatoInput = fila.querySelector('.spec-formato');
            const obsInput = fila.querySelector('.spec-obs');
            const subtotalTexto = fila.querySelector('.subtotal-texto');
            const badge = fila.querySelector('.badge-tipo-row');

            if (idInput) idInput.value = '';
            if (descInput) descInput.value = '';
            if (precioInput) precioInput.value = '0.00';
            if (cantInput) cantInput.value = '1';
            if (codigoInput) codigoInput.value = '';
            if (normaInput) normaInput.value = '';
            if (formatoInput) formatoInput.value = '';
            if (obsInput) obsInput.value = '';
            if (subtotalTexto) subtotalTexto.textContent = 'C$ 0.00';
            if (badge) {
                badge.style.background = '#fef3c7';
                badge.style.color = '#d97706';
                badge.innerHTML = '<i class="fa-solid fa-pen-fancy"></i> Campo Libre';
            }
        } else {
            const fila = boton.closest('tr');
            fila.remove();
        }
        actualizarBotonesRemover();
        calcularTotalesGenerales(); // Recalcular todo al borrar
    }

    function actualizarBotonesRemover() {
        const botones = document.querySelectorAll('.btn-remover');
        botones.forEach(btn => {
            btn.disabled = false;
            btn.style.opacity = '1';
        });
    }

    function calcularFila(input) {
        const fila = input.closest('tr');
        const cant = parseFloat(fila.querySelector('.cant-input').value) || 0;
        const precio = parseFloat(fila.querySelector('.precio-input').value) || 0;
        const subtotal = cant * precio;
        
        // Mostrar el subtotal en la fila actual
        fila.querySelector('.subtotal-texto').textContent = formatearMonto(subtotal);
        
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

        // Obtener descuento
        const descInput = document.getElementById('input-descuento-val');
        const descTipo = document.getElementById('input-descuento-tipo').value;
        const descValor = parseFloat(descInput.value) || 0;
        let descuentoCalculado = 0;

        if (descTipo === 'porcentaje') {
            descuentoCalculado = subtotalGeneral * (descValor / 100);
        } else {
            descuentoCalculado = descValor;
        }

        // Asegurar que el descuento no supere el subtotal
        descuentoCalculado = Math.min(subtotalGeneral, descuentoCalculado);
        const netoSubtotal = Math.max(0, subtotalGeneral - descuentoCalculado);

        // Impuesto (15% IVA estándar si no es exonerado)
        const exonerado = document.getElementById('chk-exonerado').checked;
        const iva = exonerado ? 0.00 : netoSubtotal * 0.15;
        const totalFinal = netoSubtotal + iva;

        // Mostrar en pantalla (Visual)
        document.getElementById('txt-subtotal').textContent = formatearMonto(subtotalGeneral);
        document.getElementById('txt-descuento-calculado').textContent = '- ' + formatearMonto(descuentoCalculado);
        document.getElementById('txt-neto').textContent = formatearMonto(netoSubtotal);
        document.getElementById('txt-iva').textContent = formatearMonto(iva);
        document.getElementById('txt-total').textContent = formatearMonto(totalFinal);

        // Guardar en campos ocultos para mandarlos por POST a PHP
        document.getElementById('input-subtotal').value = subtotalGeneral.toFixed(2);
        document.getElementById('input-descuento-final').value = descuentoCalculado.toFixed(2);
        document.getElementById('input-iva').value = iva.toFixed(2);
        document.getElementById('input-total').value = totalFinal.toFixed(2);
    }

    function normalizarTexto(txt) {
        if (!txt) return '';
        return txt.toLowerCase()
            .normalize("NFD").replace(/[\u0300-\u036f]/g, "") // Quitar acentos
            .replace(/[–—−-]/g, '-') // Normalizar guiones
            .replace(/\s+/g, ' ') // Normalizar espacios
            .trim();
    }

    function completarPrecio(input) {
        const fila = input.closest('tr');
        const valor = input.value.trim();
        const datalist = document.getElementById('productos-datalist');
        
        const valorNorm = normalizarTexto(valor);
        const opcion = Array.from(datalist.options).find(opt => {
            const valNorm = normalizarTexto(opt.value);
            const textNorm = normalizarTexto(opt.textContent);
            const labelNorm = normalizarTexto(opt.getAttribute('label') || '');
            return valNorm === valorNorm || textNorm === valorNorm || labelNorm === valorNorm;
        });
        
        const idInput = fila.querySelector('.prod-id-input');
        const codigoInput = fila.querySelector('.spec-codigo');
        const normaInput = fila.querySelector('.spec-norma');
        const formatoInput = fila.querySelector('.spec-formato');
        const obsInput = fila.querySelector('.spec-obs');
        const badge = fila.querySelector('.badge-tipo-row');

        if (opcion) {
            const idProd = opcion.getAttribute('data-id') || '';
            if (idInput) idInput.value = idProd;
            
            const precio = parseFloat(opcion.getAttribute('data-precio')) || 0;
            const precioInput = fila.querySelector('.precio-input');
            precioInput.value = precio.toFixed(2);
            
            // Reemplazar la descripción con el nombre comercial del producto seleccionado
            input.value = opcion.value;
            
            // Auto-rellenar info técnica
            const codigo = opcion.getAttribute('data-codigo') || '';
            const norma = opcion.getAttribute('data-norma') || '';
            const formato = opcion.getAttribute('data-formato') || '';
            const obs = opcion.getAttribute('data-obs') || '';
            
            if (codigoInput) codigoInput.value = codigo;
            if (normaInput) normaInput.value = norma;
            if (formatoInput) formatoInput.value = formato;
            if (obsInput) obsInput.value = obs;
            
            // Cambiar el badge a Catálogo
            if (badge) {
                badge.style.background = '#e0f2fe';
                badge.style.color = '#0369a1';
                badge.innerHTML = '<i class="fa-solid fa-book"></i> Catálogo';
            }
            
            calcularFila(precioInput);
        } else {
            if (idInput) idInput.value = '';
            
            // Cambiar el badge a Campo Libre
            if (badge) {
                badge.style.background = '#fef3c7';
                badge.style.color = '#d97706';
                badge.innerHTML = '<i class="fa-solid fa-pen-fancy"></i> Campo Libre';
            }
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
        // Si el usuario hace clic directo en el checkbox, no interferimos
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

    function agregarFilaConDatos(descripcion, precio, idProducto = '', codigo = '', norma = '', formato = '', obs = '') {
        const tbody = document.querySelector('#tabla-ensayos tbody');
        
        // Si solo hay una fila y está vacía, la sobrescribimos
        const filas = tbody.querySelectorAll('tr');
        if (filas.length === 1) {
            const idInput = filas[0].querySelector('.prod-id-input');
            const descInput = filas[0].querySelector('input[name="ensayo_desc[]"]');
            const precioInput = filas[0].querySelector('input[name="ensayo_precio[]"]');
            const codigoInput = filas[0].querySelector('.spec-codigo');
            const normaInput = filas[0].querySelector('.spec-norma');
            const formatoInput = filas[0].querySelector('.spec-formato');
            const obsInput = filas[0].querySelector('.spec-obs');
            const badge = filas[0].querySelector('.badge-tipo-row');

            if (descInput && descInput.value.trim() === "" && (parseFloat(precioInput.value) || 0) === 0) {
                if (idInput) idInput.value = idProducto;
                descInput.value = descripcion;
                precioInput.value = precio.toFixed(2);
                
                if (codigoInput) codigoInput.value = codigo;
                if (normaInput) normaInput.value = norma;
                if (formatoInput) formatoInput.value = formato;
                if (obsInput) obsInput.value = obs;
                
                if (badge) {
                    if (idProducto) {
                        badge.style.background = '#e0f2fe';
                        badge.style.color = '#0369a1';
                        badge.innerHTML = '<i class="fa-solid fa-book"></i> Catálogo';
                    } else {
                        badge.style.background = '#fef3c7';
                        badge.style.color = '#d97706';
                        badge.innerHTML = '<i class="fa-solid fa-pen-fancy"></i> Campo Libre';
                    }
                }
                
                calcularFila(precioInput);
                return;
            }
        }
        
        const nuevaFila = document.createElement('tr');
        const hasTechInfo = (codigo || norma || formato);
        const isCatalogo = idProducto !== '';
        nuevaFila.innerHTML = `
            <td>
                <input type="hidden" name="ensayo_codigo[]" class="form-control spec-codigo" placeholder="Ej: CYC-01" value="${escapeHtml(codigo)}">
                <input type="hidden" name="ensayo_norma[]" class="form-control spec-norma" placeholder="Ej: ASTM C39" value="${escapeHtml(norma)}">
                <input type="hidden" name="ensayo_formato[]" class="form-control spec-formato" placeholder="Ej: FR-CONC-01" value="${escapeHtml(formato)}">
                <input type="hidden" name="ensayo_id_producto[]" value="${escapeHtml(idProducto.toString())}" class="prod-id-input">
                <input type="text" name="ensayo_desc[]" class="form-control" required placeholder="Nombre Comercial..." list="productos-datalist" onchange="completarPrecio(this)" value="${escapeHtml(descripcion)}">
                <div style="margin-top: 3px; font-size: 10px;">
                    <span class="badge-tipo-row" style="font-weight: bold; padding: 1px 4px; border-radius: 3px; background: ${isCatalogo ? '#e0f2fe' : '#fef3c7'}; color: ${isCatalogo ? '#0369a1' : '#d97706'}; transition: all 0.2s;">
                        ${isCatalogo ? '<i class="fa-solid fa-book"></i> Catálogo' : '<i class="fa-solid fa-pen-fancy"></i> Campo Libre'}
                    </span>
                </div>
            </td>
            <td>
                <input type="text" name="ensayo_obs[]" class="form-control spec-obs" placeholder="Tiempo de entrega..." value="${escapeHtml(obs)}">
            </td>
            <td><input type="number" name="ensayo_cant[]" class="form-control cant-input" step="0.01" min="0.01" value="1" required oninput="calcularFila(this)"></td>
            <td><input type="number" name="ensayo_precio[]" class="form-control precio-input" step="0.01" min="0" value="${precio.toFixed(2)}" required oninput="calcularFila(this)"></td>
            <td style="vertical-align: middle; font-weight: 600;" class="subtotal-texto">${formatearMonto(precio)}</td>
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
            const codigo = cb.getAttribute('data-codigo') || '';
            const norma = cb.getAttribute('data-norma') || '';
            const formato = cb.getAttribute('data-formato') || '';
            const obs = cb.getAttribute('data-obs') || '';
            agregarFilaConDatos(nombre, precio, id, codigo, norma, formato, obs);
            cb.checked = false; // reset
        });
        
        document.getElementById('modal-select-all').checked = false;
        actualizarContadorModal();
        cerrarModalCatalogo();
    }
</script>

<datalist id="productos-datalist">
    <?php foreach ($productos as $prod): ?>
        <?php 
        $nombre_comercial_solo = !empty($prod['nombre_comercial']) ? $prod['nombre_comercial'] : $prod['ensayo_servicio'];
        
        // Determinar código inteligente (sólo servicio) y formato de reporte
        $codigo_opcion = $prod['codigo_servicio'] ?? '';
        $formato_opcion = (!empty($prod['codigo_servicio']) && strpos($prod['codigo_servicio'], 'CYCSA-RT-') !== false) ? $prod['codigo_servicio'] : ($prod['formato_reporte'] ?? '');
        
        $nombre = $nombre_comercial_solo;
        if (!empty($codigo_opcion)) {
            $nombre = $codigo_opcion . ' - ' . $nombre;
        }
        ?>
        <option value="<?= htmlspecialchars($nombre_comercial_solo, ENT_QUOTES, 'UTF-8') ?>" 
                data-precio="<?= $prod['precio'] ?>" 
                data-id="<?= $prod['id'] ?>"
                data-codigo="<?= htmlspecialchars($codigo_opcion, ENT_QUOTES, 'UTF-8') ?>"
                data-norma="<?= htmlspecialchars($prod['norma_astm'] ?? '', ENT_QUOTES, 'UTF-8') ?>"
                data-formato="<?= htmlspecialchars($formato_opcion, ENT_QUOTES, 'UTF-8') ?>"
                data-obs="<?= htmlspecialchars($prod['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <?= htmlspecialchars($nombre, ENT_QUOTES, 'UTF-8') ?>
        </option>
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
                        
                        $codigo_opcion = $prod['codigo_servicio'] ?? '';
                        $formato_opcion = (!empty($prod['codigo_servicio']) && strpos($prod['codigo_servicio'], 'CYCSA-RT-') !== false) ? $prod['codigo_servicio'] : ($prod['formato_reporte'] ?? '');
                        
                        $busqueda_val = strtolower($nombre_completo . ' ' . $codigo_opcion . ' ' . $formato_opcion . ' ' . ($prod['norma_astm'] ?? '') . ' ' . ($prod['matriz_tipo'] ?? ''));
                        
                        $nombre_opcion = $nombre_completo;
                        if (!empty($codigo_opcion)) {
                            $nombre_opcion = $codigo_opcion . ' - ' . $nombre_completo;
                        }
                        ?>
                        <tr style="cursor: pointer;" onclick="toggleFilaCheck(this, event)" data-text="<?= htmlspecialchars($busqueda_val, ENT_QUOTES, 'UTF-8') ?>" data-cat="<?= htmlspecialchars(strtolower($prod['matriz_tipo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                            <td style="text-align: center;">
                                <input type="checkbox" class="modal-prod-checkbox" data-id="<?= $prod['id'] ?>" data-nombre="<?= htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8') ?>" data-precio="<?= $prod['precio'] ?>" data-codigo="<?= htmlspecialchars($codigo_opcion, ENT_QUOTES, 'UTF-8') ?>" data-norma="<?= htmlspecialchars($prod['norma_astm'] ?? '', ENT_QUOTES, 'UTF-8') ?>" data-formato="<?= htmlspecialchars($formato_opcion, ENT_QUOTES, 'UTF-8') ?>" data-obs="<?= htmlspecialchars($prod['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?>" onchange="actualizarContadorModal()">
                            </td>
                            <td style="font-family: monospace; font-weight: bold; color: #2d3748;"><?= htmlspecialchars($codigo_opcion !== '' ? $codigo_opcion : 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <strong style="color: #2d3748;"><?= htmlspecialchars($nombre_completo, ENT_QUOTES, 'UTF-8') ?></strong>
                                <?php if (!empty($prod['norma_astm'])): ?>
                                    <div style="font-size: 11px; color: var(--cycsa-azul); margin-top: 2px;"><i class="fa-solid fa-scroll"></i> Norma: <?= htmlspecialchars($prod['norma_astm'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            </td>
                            <td><span style="background: #ebf8ff; color: #2b6cb0; padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 500; display: inline-block;"><?= htmlspecialchars($prod['matriz_tipo'] ?? 'Otros', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td style="text-align: right; font-weight: bold; color: #2d3748;">C$ <?= number_format($prod['precio'], 2, '.', ',') ?></td>
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

<!-- MODAL DE BÚSQUEDA Y SELECCIÓN DE CLIENTES -->
<div class="modal-premium-bg" id="modal-clientes" style="display:none;">
    <div class="modal-premium-content" style="max-width: 750px;">
        <div class="modal-header">
            <h4 class="modal-title"><i class="fa-solid fa-address-book"></i> Buscar y Seleccionar Cliente</h4>
            <button type="button" class="modal-close" onclick="cerrarModalClientes()">&times;</button>
        </div>
        <div class="modal-search-wrapper" style="position: relative;">
            <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8;"></i>
            <input type="text" id="modal-cliente-search-input" class="form-control" placeholder="Buscar por nombre, RUC, correo o teléfono..." style="padding-left: 40px;" oninput="filtrarClientesModal()">
        </div>
        <div class="modal-tabla-container" style="margin-top: 15px;">
            <table class="modal-tabla" id="modal-tabla-clientes">
                <thead>
                    <tr>
                        <th style="width: 50%;">Nombre / Razón Social</th>
                        <th style="width: 25%;">Identificación (RUC)</th>
                        <th style="width: 25%;">Contacto</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($clientes as $cli): ?>
                        <?php if (($cli['activo'] ?? 1) == 1): ?>
                            <?php 
                            $busqueda_val = strtolower($cli['nombre_razon_social'] . ' ' . ($cli['identificacion'] ?? '') . ' ' . ($cli['email'] ?? '') . ' ' . ($cli['telefono'] ?? ''));
                            ?>
                            <tr style="cursor: pointer;" onclick="seleccionarClienteDesdeModal(<?= $cli['id'] ?>, '<?= htmlspecialchars($cli['nombre_razon_social'], ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars($cli['identificacion'] ?? 'Sin RUC', ENT_QUOTES, 'UTF-8') ?>', <?= (int)($cli['exonerado_impuestos'] ?? 0) ?>, <?= (int)($cli['descuento_automatico'] ?? 0) ?>, <?= (float)($cli['porcentaje_descuento'] ?? 0.00) ?>)" data-text="<?= htmlspecialchars($busqueda_val, ENT_QUOTES, 'UTF-8') ?>">
                                <td>
                                    <strong style="color: #2d3748;"><?= htmlspecialchars($cli['nombre_razon_social'], ENT_QUOTES, 'UTF-8') ?></strong>
                                </td>
                                <td style="font-family: monospace; font-weight: bold; color: #4a5568;">
                                    <?= htmlspecialchars($cli['identificacion'] ?? 'Sin RUC', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                                <td style="font-size: 12px; color: #718096;">
                                    <?php if (!empty($cli['email'])): ?>
                                        <div><i class="fa-regular fa-envelope"></i> <?= htmlspecialchars($cli['email'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                    <?php if (!empty($cli['telefono'])): ?>
                                        <div style="margin-top: 2px;"><i class="fa-solid fa-phone"></i> <?= htmlspecialchars($cli['telefono'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <div class="modal-footer">
            <button type="button" class="form-control" style="background: #f8f9fa; border: 1px solid #ddd; padding: 10px 20px; border-radius: 4px; color: #4a5568; font-size: 14px; font-weight: 600; width: auto; cursor: pointer;" onclick="cerrarModalClientes()">Cerrar</button>
        </div>
    </div>
</div>