<style>
    .seccion-form { background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03); margin-bottom: 20px; border-top: 4px solid var(--cycsa-azul); }
    .seccion-titulo { margin: 0 0 20px 0; color: #333; font-size: 18px; display: flex; align-items: center; gap: 10px; border-bottom: 1px solid #eee; padding-bottom: 10px; }
    
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .grid-3 { display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 20px; }
    
    .form-group { margin-bottom: 15px; }
    .form-label { display: block; margin-bottom: 8px; color: #495057; font-weight: 600; font-size: 13px; text-transform: uppercase; letter-spacing: 0.5px; }
    .form-control { width: 100%; padding: 10px 15px; border: 1px solid #ced4da; border-radius: 4px; font-family: 'Inter', sans-serif; font-size: 14px; transition: border-color 0.2s; }
    .form-control:focus { outline: none; border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.1); }
    
    .alert-danger { background: #f8d7da; color: #721c24; padding: 15px; border-radius: 4px; border: 1px solid #f5c6cb; margin-bottom: 20px; }
</style>

<div style="margin-bottom: 20px;">
    <a href="/Cycsa/publico/productos" style="color: #6c757d; text-decoration: none; font-size: 14px;"><i class="fa-solid fa-arrow-left"></i> Volver al catálogo</a>
</div>

<?php if (isset($error)): ?>
    <div class="alert-danger">
        <i class="fa-solid fa-triangle-exclamation"></i> <?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?>
    </div>
<?php endif; ?>

<form action="/Cycsa/publico/productos/editar?id=<?= codificarId($producto['id']) ?>" method="POST">
    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

    <div class="seccion-form">
        <h3 class="seccion-titulo"><i class="fa-solid fa-pen-to-square"></i> Editar Información General</h3>
        
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">No. de Ítem</label>
                <input type="text" name="no_item" class="form-control" placeholder="Ej: 15" value="<?= htmlspecialchars($producto['no_item'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <div class="form-group">
                <label class="form-label">Nombre Comercial *</label>
                <input type="text" name="nombre_comercial" class="form-control" required placeholder="Ej: Humedad en Agregados" value="<?= htmlspecialchars($producto['nombre_comercial'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Código de Servicio / Formato</label>
                <input type="text" name="codigo_servicio" class="form-control" placeholder="Ej: CYCSA-RT-FM-22G" value="<?= htmlspecialchars($producto['codigo_servicio'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="form-group">
            <label class="form-label">Descripción Técnica o Servicio a Realizar *</label>
            <textarea name="ensayo_servicio" class="form-control" required rows="2" placeholder="Ej: CYCSA-PE-01-Determinación de Humedad..."><?= htmlspecialchars($producto['ensayo_servicio'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
        </div>

        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Matriz / Grupo</label>
                <input type="text" name="matriz_tipo" id="matriz_tipo" class="form-control" placeholder="Ej: Agregados" list="categorias_existentes" value="<?= htmlspecialchars($producto['matriz_tipo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <datalist id="categorias_existentes">
                    <?php foreach ($categorias as $cat): ?>
                        <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>">
                    <?php endforeach; ?>
                </datalist>
            </div>
            
            <div class="form-group">
                <label class="form-label">Tipo de Muestra</label>
                <input type="text" name="tipo_muestra" class="form-control" placeholder="Ej: Agregados Finos" value="<?= htmlspecialchars($producto['tipo_muestra'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Tipo de Muestreo</label>
                <input type="text" name="tipo_muestreo" class="form-control" placeholder="Ej: Aleatorio / Puntual" value="<?= htmlspecialchars($producto['tipo_muestreo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>
    </div>

    <div class="seccion-form">
        <h3 class="seccion-titulo"><i class="fa-solid fa-list-check"></i> Especificaciones Técnicas y Precios</h3>
        
        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Estatus de Acreditación</label>
                <select name="estatus" class="form-control">
                    <option value="No acreditado" <?= (isset($producto['estatus']) && strtolower($producto['estatus']) === 'no acreditado') ? 'selected' : '' ?>>No Acreditado</option>
                    <option value="Acreditado" <?= (isset($producto['estatus']) && strtolower($producto['estatus']) === 'acreditado') ? 'selected' : '' ?>>Acreditado (ISO 17025)</option>
                </select>
            </div>

            <div class="form-group">
                <label class="form-label">Norma ASTM</label>
                <input type="text" name="norma_astm" class="form-control" placeholder="Ej: ASTM C566-25" value="<?= htmlspecialchars($producto['norma_astm'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Procedimiento Muestreo (CYCSA-PE)</label>
                <input type="text" name="procedimiento_muestreo" class="form-control" placeholder="Ej: CYCSA-PE-01" value="<?= htmlspecialchars($producto['procedimiento_muestreo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Código Hoja de Campo</label>
                <input type="text" name="codigo_hoja_campo" class="form-control" placeholder="Ej: CYCSA-RT-FM-13" value="<?= htmlspecialchars($producto['codigo_hoja_campo'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Unidad de Medida *</label>
                <input type="text" name="unidad_medida" class="form-control" required placeholder="Ej: Unidad" value="<?= htmlspecialchars($producto['unidad_medida'] ?? 'Unidad', ENT_QUOTES, 'UTF-8') ?>">
            </div>

            <div class="form-group">
                <label class="form-label">Precio Unitario (C$) *</label>
                <input type="number" name="precio" class="form-control" step="0.01" min="0" required placeholder="Ej: 900.00" value="<?= htmlspecialchars($producto['precio'] ?? '0.00', ENT_QUOTES, 'UTF-8') ?>">
            </div>
        </div>

        <div class="grid-3">
            <div class="form-group">
                <label class="form-label">Estado del Producto *</label>
                <select name="activo" class="form-control" required>
                    <option value="1" <?= (int)$producto['activo'] === 1 ? 'selected' : '' ?>>Activo (Visible en Catálogo)</option>
                    <option value="0" <?= (int)$producto['activo'] === 0 ? 'selected' : '' ?>>Inactivo (Oculto)</option>
                </select>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group" style="grid-column: span 2;">
                <label class="form-label">Formato de Reporte de Ensayo Relacionado (Calidad PDF)</label>
                <select name="formato_id" class="form-control" style="background-color: white;">
                    <option value="">-- Sin Formato Específico (No imprime reporte) --</option>
                    <?php foreach ($formatos as $f): ?>
                        <option value="<?= $f['id'] ?>" <?= (isset($producto['formato_id']) && (int)$producto['formato_id'] === (int)$f['id']) ? 'selected' : '' ?>>
                            <?= htmlspecialchars($f['nombre'], ENT_QUOTES, 'UTF-8') ?> - [<?= htmlspecialchars($f['codigo_formato'], ENT_QUOTES, 'UTF-8') ?>]
                        </option>
                    <?php endforeach; ?>
                </select>
                <span class="text-muted-small" style="color: var(--cycsa-azul); margin-top: 5px;"><i class="fa-solid fa-circle-info"></i> Selecciona cuál de los 21 formatos de ensayo oficiales corresponde a este producto para su posterior impresión en PDF.</span>
            </div>
        </div>

        <div class="grid-2">
            <div class="form-group">
                <label class="form-label">Condiciones de Recepción de la Muestra</label>
                <textarea name="condiciones_muestra" class="form-control" rows="3" placeholder="Ej: Tienen que venir en bolsas..."><?= htmlspecialchars($producto['condiciones_muestra'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>

            <div class="form-group">
                <label class="form-label">Tiempo de Entrega / Observaciones</label>
                <textarea name="observaciones" class="form-control" rows="3" placeholder="Ej: (2 días hábiles) o A convenir"><?= htmlspecialchars($producto['observaciones'] ?? '', ENT_QUOTES, 'UTF-8') ?></textarea>
            </div>
        </div>
    </div>

    <div style="display: flex; gap: 15px; justify-content: flex-end; margin-bottom: 50px;">
        <a href="/Cycsa/publico/productos" style="padding: 12px 25px; border-radius: 4px; text-decoration: none; color: #6c757d; font-weight: 500; background: #f8f9fa; border: 1px solid #ddd;">Cancelar</a>
        <button type="submit" style="background: var(--cycsa-azul); color: white; border: none; padding: 12px 25px; border-radius: 4px; cursor: pointer; font-weight: 600; font-family: 'Inter', sans-serif; font-size: 15px; box-shadow: 0 4px 6px rgba(16, 52, 135, 0.2);">
            <i class="fa-solid fa-save"></i> Guardar Cambios
        </button>
    </div>
</form>
