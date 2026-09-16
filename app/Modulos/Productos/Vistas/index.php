<?php
// Calcular métricas rápidas para las tarjetas de KPI
$totalEnsayos = count($productos);
$acreditados = 0;
$noAcreditados = 0;
$sumaPrecios = 0;
$conPrecio = 0;

foreach ($productos as $p) {
    if (strtolower($p['estatus'] ?? '') === 'acreditado') {
        $acreditados++;
    } else {
        $noAcreditados++;
    }
    if ($p['precio'] > 0) {
        $sumaPrecios += $p['precio'];
        $conPrecio++;
    }
}
$precioPromedio = $conPrecio > 0 ? $sumaPrecios / $conPrecio : 0;
?>

<style>
    /* Estilos Premium para Catálogo */
    .kpi-container { display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 20px; margin-bottom: 25px; }
    .kpi-card { background: white; padding: 20px; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); border-top: 4px solid var(--cycsa-azul); display: flex; align-items: center; justify-content: space-between; }
    .kpi-card.acreditado { border-top-color: #2ec4b6; }
    .kpi-card.no-acreditado { border-top-color: #ff9f1c; }
    .kpi-card.precio-prom { border-top-color: #e31837; }
    .kpi-num { font-size: 28px; font-weight: 700; color: #2d3748; margin-top: 5px; }
    .kpi-title { font-size: 12px; font-weight: 600; text-transform: uppercase; color: #a0aec0; letter-spacing: 0.5px; }
    .kpi-icon { font-size: 32px; opacity: 0.2; color: #4a5568; }

    .card-principal { background: white; border-radius: 10px; box-shadow: 0 4px 15px rgba(0,0,0,0.02); padding: 25px; margin-bottom: 30px; }
    .catalogo-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; gap: 20px; flex-wrap: wrap; }
    .catalogo-titulo { font-size: 20px; color: #2d3748; font-weight: 700; margin: 0; }
    
    /* Barra de Control y Filtros */
    .controles-barra { display: flex; justify-content: space-between; align-items: center; gap: 15px; margin-bottom: 20px; flex-wrap: wrap; }
    .filtro-barra { display: flex; gap: 12px; flex: 1; max-width: 650px; flex-wrap: wrap; }
    .search-input-wrapper { position: relative; flex: 1; min-width: 260px; }
    .search-input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; }
    .search-input { width: 100%; padding: 10px 15px 10px 42px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; outline: none; transition: all 0.2s; }
    .search-input:focus { border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.1); }
    
    .cat-select { padding: 10px 15px; border: 1px solid #cbd5e1; border-radius: 6px; font-size: 13.5px; background: white; outline: none; min-width: 180px; }

    /* Switch de Vistas (Tarjetas con Viñetas vs Lista/Tabla) */
    .view-mode-selector { display: flex; background: #f1f5f9; padding: 4px; border-radius: 8px; gap: 4px; }
    .view-btn { border: none; background: transparent; padding: 7px 14px; border-radius: 6px; font-size: 13px; font-weight: 600; color: #64748b; cursor: pointer; display: inline-flex; align-items: center; gap: 6px; transition: all 0.2s; }
    .view-btn.activo { background: white; color: var(--cycsa-azul); box-shadow: 0 2px 4px rgba(0,0,0,0.06); }
    
    /* Categorías como Píldoras */
    .pildoras-container { display: flex; gap: 8px; overflow-x: auto; padding: 5px 0 15px 0; margin-bottom: 25px; border-bottom: 1px solid #f1f5f9; scrollbar-width: thin; }
    .pildora-link { display: inline-block; padding: 6px 14px; background: #f1f5f9; color: #475569; border-radius: 20px; font-size: 12.5px; font-weight: 600; text-decoration: none; transition: all 0.2s; white-space: nowrap; }
    .pildora-link:hover { background: #e2e8f0; color: #0f172a; }
    .pildora-link.activa { background: var(--cycsa-azul); color: white; box-shadow: 0 3px 6px rgba(16, 52, 135, 0.2); }
    
    /* Badges */
    .badge-no-item { background: #0f172a; color: white; font-weight: 800; font-size: 12px; padding: 4px 10px; border-radius: 6px; display: inline-block; }
    .badge-acred { background-color: #d1fae5; color: #047857; border: 1px solid #a7f3d0; padding: 3px 9px; border-radius: 12px; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; display: inline-flex; align-items: center; gap: 4px; }
    .badge-no-acred { background-color: #f1f5f9; color: #64748b; border: 1px solid #e2e8f0; padding: 3px 9px; border-radius: 12px; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; display: inline-block; }
    .badge-category { background-color: #e0f2fe; color: #0369a1; padding: 3px 8px; border-radius: 4px; font-weight: 600; font-size: 11.5px; display: inline-block; }
    .badge-code { background-color: #f8fafc; color: #0f172a; border: 1px solid #cbd5e1; font-family: monospace; font-weight: 700; font-size: 12px; padding: 2px 7px; border-radius: 4px; display: inline-block; }

    /* =========================================================================
       🎴 MODO 1: GRID DE FICHAS / TARJETAS CON VIÑETAS (VISTA PRINCIPAL)
       ========================================================================= */
    .fichas-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(540px, 1fr)); gap: 22px; }
    @media (max-width: 768px) {
        .fichas-grid { grid-template-columns: 1fr; }
    }

    .ficha-card { background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; padding: 22px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); transition: all 0.25s ease; display: flex; flex-direction: column; justify-content: space-between; }
    .ficha-card:hover { transform: translateY(-3px); box-shadow: 0 10px 25px rgba(0,0,0,0.07); border-color: #cbd5e1; }
    
    .ficha-card-header { display: flex; justify-content: space-between; align-items: flex-start; gap: 12px; margin-bottom: 12px; border-bottom: 1px solid #f1f5f9; padding-bottom: 12px; }
    .ficha-card-titulo { font-size: 16px; font-weight: 700; color: #0f172a; margin: 0 0 4px 0; line-height: 1.35; }
    .ficha-card-precio { text-align: right; white-space: nowrap; }
    .ficha-precio-val { font-size: 18px; font-weight: 800; color: #0f172a; }
    .ficha-precio-unid { font-size: 11.5px; color: #64748b; font-weight: 500; display: block; }

    .ficha-ensayo-desc { font-size: 13px; color: #334155; line-height: 1.45; margin-bottom: 14px; background: #f8fafc; padding: 10px 14px; border-radius: 6px; border-left: 3px solid var(--cycsa-azul); }

    /* 📦 DESTACADO DE CONDICIONES DE MUESTRA */
    .box-condiciones { background: #fffbeb; border: 1px solid #fde68a; border-radius: 8px; padding: 10px 14px; margin-bottom: 14px; }
    .box-condiciones-title { font-size: 11px; font-weight: 800; text-transform: uppercase; color: #92400e; letter-spacing: 0.5px; margin-bottom: 4px; display: flex; align-items: center; gap: 6px; }
    .box-condiciones-text { font-size: 12.5px; color: #78350f; line-height: 1.4; margin: 0; }

    /* 📋 LISTA DE VIÑETAS ESTRUCTURADAS */
    .vinetas-lista { list-style: none; padding: 0; margin: 0 0 16px 0; display: grid; grid-template-columns: 1fr 1fr; gap: 8px 14px; }
    .vineta-item { font-size: 12.5px; color: #475569; display: flex; align-items: baseline; gap: 6px; line-height: 1.35; }
    .vineta-item.full { grid-column: span 2; }
    .vineta-icon { color: var(--cycsa-azul); font-size: 12px; width: 16px; text-align: center; flex-shrink: 0; }
    .vineta-label { font-weight: 600; color: #1e293b; margin-right: 4px; }
    .vineta-val { color: #334155; word-break: break-word; }

    .ficha-card-footer { display: flex; justify-content: space-between; align-items: center; border-top: 1px solid #f1f5f9; padding-top: 12px; margin-top: auto; }
    
    /* =========================================================================
       📑 MODO 2: LISTA / ACORDEÓN DESPLEGABLE
       ========================================================================= */
    .acordeon-container { display: flex; flex-direction: column; gap: 12px; }
    .acordeon-item { background: white; border: 1px solid #e2e8f0; border-radius: 8px; overflow: hidden; transition: all 0.2s ease; }
    .acordeon-item:hover { border-color: #cbd5e1; }
    .acordeon-header { padding: 14px 18px; display: flex; justify-content: space-between; align-items: center; cursor: pointer; gap: 15px; background: #ffffff; user-select: none; }
    .acordeon-header:hover { background: #f8fafc; }
    .acordeon-body { display: none; padding: 18px; background: #fafbfc; border-top: 1px solid #edf2f7; }
    .acordeon-body.open { display: block; animation: fadeIn 0.2s ease; }

    /* =========================================================================
       📊 MODO 3: TABLA RESUMEN COMPACTA
       ========================================================================= */
    .tabla-container { overflow-x: auto; }
    .tabla-premium { width: 100%; min-width: 1000px; border-collapse: collapse; text-align: left; }
    .tabla-premium th { padding: 12px 14px; font-size: 11.5px; font-weight: 700; text-transform: uppercase; color: #475569; background: #f1f5f9; border-bottom: 2px solid #cbd5e1; white-space: nowrap; }
    .tabla-premium td { padding: 12px 14px; border-bottom: 1px solid #edf2f7; font-size: 13px; color: #334155; vertical-align: middle; }
    .tabla-premium tr:hover { background-color: #f8fafc; }

    /* Botones de acción */
    .btn-premium-azul { background: var(--cycsa-azul); color: white; border: none; padding: 10px 18px; border-radius: 6px; font-weight: 600; font-size: 13.5px; text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 3px 6px rgba(16, 52, 135, 0.15); transition: background 0.2s; white-space: nowrap; }
    .btn-premium-azul:hover { background: #0c2766; }
    .btn-accion { color: #64748b; background: none; border: none; font-size: 14px; padding: 6px 10px; cursor: pointer; transition: all 0.2s; text-decoration: none; border-radius: 4px; display: inline-flex; align-items: center; gap: 5px; }
    .btn-accion:hover { background: #e2e8f0; color: #1e293b; }
    .btn-accion.ver { color: #2563eb; background: #eff6ff; font-weight: 600; font-size: 12px; }
    .btn-accion.ver:hover { background: #dbeafe; }
    .btn-accion.editar:hover { color: var(--cycsa-azul); }
    .btn-accion.eliminar:hover { color: var(--cycsa-rojo); }

    /* Modal Ficha Técnica Integral */
    .modal-overlay { position: fixed; top: 0; left: 0; right: 0; bottom: 0; background: rgba(15, 23, 42, 0.6); backdrop-filter: blur(4px); display: none; justify-content: center; align-items: center; z-index: 9999; padding: 20px; }
    .modal-card { background: white; border-radius: 12px; max-width: 800px; width: 100%; max-height: 90vh; overflow-y: auto; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); border: 1px solid #e2e8f0; }
    .modal-header { padding: 18px 24px; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; align-items: center; background: #f8fafc; }
    .modal-title { font-size: 17px; font-weight: 700; color: #0f172a; margin: 0; display: flex; align-items: center; gap: 10px; }
    .modal-close { background: none; border: none; font-size: 24px; color: #94a3b8; cursor: pointer; line-height: 1; transition: color 0.2s; }
    .modal-close:hover { color: #ef4444; }
    .modal-body { padding: 24px; }
    .ficha-grid-modal { display: grid; grid-template-columns: repeat(2, 1fr); gap: 16px; }
    .ficha-box-modal { background: #f8fafc; padding: 12px 16px; border-radius: 8px; border: 1px solid #e2e8f0; }
    .ficha-box-modal.full { grid-column: span 2; }
    .ficha-label-modal { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; letter-spacing: 0.5px; margin-bottom: 5px; display: flex; align-items: center; gap: 6px; }
    .ficha-val-modal { font-size: 13.5px; color: #1e293b; font-weight: 500; word-break: break-word; }

    @keyframes fadeIn {
        from { opacity: 0; transform: translateY(-4px); }
        to { opacity: 1; transform: translateY(0); }
    }
</style>

<!-- Tarjetas KPI Resumen -->
<div class="kpi-container">
    <div class="kpi-card">
        <div>
            <div class="kpi-title">Total Ensayos / Servicios</div>
            <div class="kpi-num"><?= $totalEnsayos ?></div>
        </div>
        <i class="fa-solid fa-flask-vial kpi-icon"></i>
    </div>
    <div class="kpi-card acreditado">
        <div>
            <div class="kpi-title">Acreditados (ISO 17025)</div>
            <div class="kpi-num" style="color: #2ec4b6;"><?= $acreditados ?></div>
        </div>
        <i class="fa-solid fa-circle-check kpi-icon"></i>
    </div>
    <div class="kpi-card no-acreditado">
        <div>
            <div class="kpi-title">No Acreditados</div>
            <div class="kpi-num" style="color: #ff9f1c;"><?= $noAcreditados ?></div>
        </div>
        <i class="fa-solid fa-triangle-exclamation kpi-icon"></i>
    </div>
    <div class="kpi-card precio-prom">
        <div>
            <div class="kpi-title">Precio Promedio</div>
            <div class="kpi-num" style="color: #e31837;">C$ <?= number_format($precioPromedio, 2) ?></div>
        </div>
        <i class="fa-solid fa-tags kpi-icon"></i>
    </div>
</div>

<!-- Contenedor Principal del Catálogo -->
<div class="card-principal">
    <div class="catalogo-header">
        <div>
            <h3 class="catalogo-titulo">Catálogo General de Ensayos y Servicios</h3>
            <p style="color: #64748b; font-size: 13px; margin-top: 4px; margin-bottom: 0;">Lista oficial RG-LI-05 (112 ítems normativos organizados en viñetas y fichas).</p>
        </div>
        
        <?php if (tienePermiso('productos', 'crear_editar')): ?>
        <a href="/Cycsa/publico/productos/crear" class="btn-premium-azul">
            <i class="fa-solid fa-plus"></i> Nuevo Ensayo
        </a>
        <?php endif; ?>
    </div>

    <!-- Barra de Controles (Búsqueda + Selector de Modo de Vista) -->
    <div class="controles-barra">
        <form method="GET" action="/Cycsa/publico/productos" class="filtro-barra" id="form-busqueda">
            <div class="search-input-wrapper" style="position: relative; flex: 1; min-width: 260px;">
                <i class="fa-solid fa-magnifying-glass" style="position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #94a3b8; pointer-events: none;"></i>
                <input type="text" name="q" id="search-productos-input" class="search-input" placeholder="Escribe para buscar: ej. 'pe 25', 'pe25', 'densimetro', 'astm 6938'..." value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>" oninput="filtrarProductosEnVivo()" autocomplete="off" style="padding-right: 36px;">
                <button type="button" id="btn-clear-search-productos" onclick="limpiarBusquedaProductos()" style="display: <?= !empty($busqueda) ? 'inline-block' : 'none' ?>; position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; font-size: 18px; cursor: pointer; padding: 4px; line-height: 1;" title="Limpiar búsqueda">&times;</button>
            </div>
            
            <select name="cat" id="select-cat-productos" class="cat-select" onchange="filtrarPorCategoriaEnVivo(this.value)">
                <option value="">Todas las Matrices</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= $categoria_actual === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </form>

        <!-- Selector de Modo de Visualización -->
        <div class="view-mode-selector">
            <button type="button" class="view-btn activo" id="btn-vista-fichas" onclick="cambiarVista('fichas')">
                <i class="fa-solid fa-grip"></i> Fichas con Viñetas
            </button>
            <button type="button" class="view-btn" id="btn-vista-acordeon" onclick="cambiarVista('acordeon')">
                <i class="fa-solid fa-list-ul"></i> Lista Desplegable
            </button>
            <button type="button" class="view-btn" id="btn-vista-tabla" onclick="cambiarVista('tabla')">
                <i class="fa-solid fa-table"></i> Tabla Resumen
            </button>
        </div>
    </div>

    <!-- Contador Dinámico en Vivo -->
    <div id="contador-productos-vivo" style="font-size: 12.5px; color: #64748b; margin-top: -10px; margin-bottom: 16px; padding: 0 4px; display: flex; align-items: center; gap: 6px;">
        Mostrando <?= !empty($busqueda) ? "resultados para \"<strong>" . htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') . "</strong>\" (" . count($productos) . ")" : "todos los ensayos (<strong>" . count($productos) . "</strong>)" ?>
    </div>

    <!-- Píldoras de Categoría Rápidas -->
    <div class="pildoras-container">
        <a href="/Cycsa/publico/productos?q=<?= urlencode($busqueda) ?>" class="pildora-link <?= $categoria_actual === '' ? 'activa' : '' ?>">
            Todos (<?= $totalEnsayos ?>)
        </a>
        <?php foreach ($categorias as $cat): ?>
            <a href="/Cycsa/publico/productos?cat=<?= urlencode($cat) ?>&q=<?= urlencode($busqueda) ?>" class="pildora-link <?= $categoria_actual === $cat ? 'activa' : '' ?>">
                <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (empty($productos)): ?>
        <div style="text-align: center; padding: 50px 20px; color: #94a3b8;">
            <i class="fa-solid fa-box-open" style="font-size: 52px; margin-bottom: 15px; display: block; opacity: 0.4;"></i>
            <h4 style="margin: 0 0 5px 0; color: #475569;">No se encontraron ensayos o servicios</h4>
            <p style="font-size: 13.5px; margin: 0;">Prueba modificando el término de búsqueda o cambiando la matriz seleccionada.</p>
        </div>
    <?php else: ?>

        <!-- =======================================================================
             🎴 VISTA 1: GRID DE FICHAS / TARJETAS CON VIÑETAS (POR DEFECTO)
             ======================================================================= -->
        <div id="contenedor-fichas" class="fichas-grid">
            <div id="no-results-fichas" style="display: none; grid-column: 1 / -1; text-align: center; padding: 45px 20px; color: #64748b; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1;">
                <i class="fa-solid fa-flask-vial" style="font-size: 38px; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                <div style="font-weight: 700; font-size: 15px; color: #334155; margin-bottom: 4px;">No se encontraron ensayos coincidentes</div>
                <div style="font-size: 13px; color: #64748b;">Prueba buscando sin guiones o con palabras clave (ej: "pe 25", "densidad", "nuclear", "astm").</div>
                <button type="button" class="btn-premium-azul" style="margin-top: 14px; font-size: 12.5px; padding: 7px 16px; display: inline-flex; align-items: center; gap: 6px;" onclick="limpiarBusquedaProductos()">
                    <i class="fa-solid fa-arrow-rotate-left"></i> Restablecer búsqueda
                </button>
            </div>
            <?php foreach ($productos as $p): ?>
                <?php
                $nom_c = !empty($p['nombre_comercial']) ? trim($p['nombre_comercial']) : trim($p['ensayo_servicio']);
                $ensayo_t = trim($p['ensayo_servicio']);
                $proc_t = $p['procedimiento_muestreo'] ?? '';
                $cod_t = $p['codigo_servicio'] ?? '';
                $norma_t = $p['norma_astm'] ?? '';
                $matriz_t = $p['matriz_tipo'] ?? '';
                $tipo_m = $p['tipo_muestra'] ?? '';
                $hoja_c = $p['codigo_hoja_campo'] ?? '';
                $cond_m = $p['condiciones_muestra'] ?? '';
                $no_item_t = $p['no_item'] ?? $p['id'];
                
                $proc_sp = str_replace(['-', '_', '/', '.'], ' ', $proc_t);
                $cod_sp = str_replace(['-', '_', '/', '.'], ' ', $cod_t);
                $norma_sp = str_replace(['-', '_', '/', '.'], ' ', $norma_t);
                
                $busq_item = strtolower("{$no_item_t} {$nom_c} {$ensayo_t} {$cod_t} {$cod_sp} {$proc_t} {$proc_sp} {$norma_t} {$norma_sp} {$matriz_t} {$tipo_m} {$hoja_c} {$cond_m}");
                ?>
                <div class="ficha-card item-catalogo-filtrable" data-text="<?= htmlspecialchars($busq_item, ENT_QUOTES, 'UTF-8') ?>" data-cat="<?= htmlspecialchars(strtolower($matriz_t), ENT_QUOTES, 'UTF-8') ?>">
                    <div>
                        <!-- Cabecera de Tarjeta -->
                        <div class="ficha-card-header">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <span class="badge-no-item">#<?= htmlspecialchars($p['no_item'] ?? $p['id'], ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="badge-code"><?= htmlspecialchars($p['codigo_servicio'] ?? 'S/C', ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (strtolower($p['estatus'] ?? '') === 'acreditado'): ?>
                                    <span class="badge-acred"><i class="fa-solid fa-certificate"></i> Acreditado ISO 17025</span>
                                <?php else: ?>
                                    <span class="badge-no-acred">No Acreditado</span>
                                <?php endif; ?>
                            </div>
                            <div class="ficha-card-precio">
                                <span class="ficha-precio-val">C$ <?= number_format($p['precio'], 2) ?></span>
                                <span class="ficha-precio-unid">por <?= htmlspecialchars($p['unidad_medida'] ?? 'Unidad', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                        </div>

                        <!-- Título / Nombre Comercial -->
                        <h4 class="ficha-card-titulo">
                            <?= htmlspecialchars($p['nombre_comercial'] ?? 'Sin nombre comercial', ENT_QUOTES, 'UTF-8') ?>
                        </h4>

                        <!-- Descripción Técnica / Ensayo -->
                        <div class="ficha-ensayo-desc">
                            <strong><i class="fa-solid fa-microscope" style="color: var(--cycsa-azul);"></i> Ensayo a Realizar:</strong>
                            <?= htmlspecialchars($p['ensayo_servicio'], ENT_QUOTES, 'UTF-8') ?>
                        </div>

                        <!-- 📦 CONDICIONES DE MUESTRA (DESTACADO Y BIEN VISIBLE) -->
                        <div class="box-condiciones">
                            <div class="box-condiciones-title">
                                <i class="fa-solid fa-box-archive"></i> Condiciones de Recepción de la Muestra:
                            </div>
                            <p class="box-condiciones-text">
                                <?= !empty($p['condiciones_muestra']) ? htmlspecialchars($p['condiciones_muestra'], ENT_QUOTES, 'UTF-8') : '<em>Sin condiciones especiales de empaque o traslado especificadas.</em>' ?>
                            </p>
                        </div>

                        <!-- 📋 VIÑETAS DE ESPECIFICACIONES TÉCNICAS -->
                        <ul class="vinetas-lista">
                            <!-- Matriz / Tipo -->
                            <li class="vineta-item">
                                <i class="fa-solid fa-layer-group vineta-icon"></i>
                                <div><span class="vineta-label">Matriz:</span> <span class="badge-category"><?= htmlspecialchars($p['matriz_tipo'] ?? 'Otros', ENT_QUOTES, 'UTF-8') ?></span></div>
                            </li>

                            <!-- Tipo de Muestra -->
                            <li class="vineta-item">
                                <i class="fa-solid fa-vial vineta-icon"></i>
                                <div><span class="vineta-label">Tipo Muestra:</span> <span class="vineta-val"><?= htmlspecialchars($p['tipo_muestra'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span></div>
                            </li>

                            <!-- Tipo de Muestreo -->
                            <li class="vineta-item">
                                <i class="fa-solid fa-hand-holding-droplet vineta-icon"></i>
                                <div><span class="vineta-label">Muestreo:</span> <span class="vineta-val"><?= htmlspecialchars($p['tipo_muestreo'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span></div>
                            </li>

                            <!-- Norma ASTM -->
                            <li class="vineta-item">
                                <i class="fa-solid fa-scroll vineta-icon"></i>
                                <div><span class="vineta-label">Norma ASTM:</span> <span class="vineta-val" style="color: #92400e; font-weight: 600;"><?= htmlspecialchars($p['norma_astm'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span></div>
                            </li>

                            <!-- Procedimiento CYCSA-PE -->
                            <li class="vineta-item">
                                <i class="fa-solid fa-gears vineta-icon"></i>
                                <div><span class="vineta-label">Proc. PE:</span> <span class="vineta-val" style="font-family: monospace; font-weight: 600; color: #1e40af;"><?= htmlspecialchars($p['procedimiento_muestreo'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span></div>
                            </li>

                            <!-- Hoja de Campo -->
                            <li class="vineta-item">
                                <i class="fa-solid fa-clipboard-list vineta-icon"></i>
                                <div><span class="vineta-label">Hoja Campo:</span> <span class="vineta-val"><?= htmlspecialchars($p['codigo_hoja_campo'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></span></div>
                            </li>

                            <!-- Tiempo de Entrega / Observaciones -->
                            <li class="vineta-item full">
                                <i class="fa-solid fa-clock-rotate-left vineta-icon" style="color: #ea580c;"></i>
                                <div><span class="vineta-label">Tiempo Entrega / Obs:</span> <span class="vineta-val" style="font-weight: 600; color: #0f172a;"><?= htmlspecialchars($p['observaciones'] ?? 'A convenir', ENT_QUOTES, 'UTF-8') ?></span></div>
                            </li>

                            <?php if (!empty($p['formato_nombre'])): ?>
                            <li class="vineta-item full">
                                <i class="fa-solid fa-file-pdf vineta-icon" style="color: #0d9488;"></i>
                                <div><span class="vineta-label">Formato Calidad:</span> <span class="vineta-val" style="color: #0f766e;"><?= htmlspecialchars($p['formato_nombre'], ENT_QUOTES, 'UTF-8') ?> (<?= htmlspecialchars($p['formato_reporte'], ENT_QUOTES, 'UTF-8') ?>)</span></div>
                            </li>
                            <?php endif; ?>
                        </ul>
                    </div>

                    <!-- Pie de Tarjeta / Botones de Acción -->
                    <div class="ficha-card-footer">
                        <button type="button" class="btn-accion ver" onclick='abrirFichaTecnica(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)'>
                            <i class="fa-solid fa-eye"></i> Ver Ficha Técnica
                        </button>
                        
                        <div style="display: flex; gap: 4px;">
                            <?php if (tienePermiso('productos', 'crear_editar')): ?>
                            <a href="/Cycsa/publico/productos/editar?id=<?= codificarId($p['id']) ?>" class="btn-accion editar" title="Editar Ensayo">
                                <i class="fa-solid fa-pen-to-square"></i> Editar
                            </a>
                            <form action="/Cycsa/publico/productos/eliminar" method="POST" style="display: inline;" onsubmit="return confirm('¿Está seguro de que desea desactivar este ensayo?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id" value="<?= codificarId($p['id']) ?>">
                                <button type="submit" class="btn-accion eliminar" title="Desactivar" style="background: none; border: none; cursor: pointer; padding: 0 4px; display: inline-flex; align-items: center;">
                                    <i class="fa-solid fa-trash-can"></i>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- =======================================================================
             📑 VISTA 2: LISTA / ACORDEÓN DESPLEGABLE CON VIÑETAS
             ======================================================================= -->
        <div id="contenedor-acordeon" class="acordeon-container" style="display: none;">
            <div id="no-results-acordeon" style="display: none; text-align: center; padding: 45px 20px; color: #64748b; background: #f8fafc; border-radius: 12px; border: 1px dashed #cbd5e1; width: 100%;">
                <i class="fa-solid fa-flask-vial" style="font-size: 38px; color: #cbd5e1; margin-bottom: 12px; display: block;"></i>
                <div style="font-weight: 700; font-size: 15px; color: #334155; margin-bottom: 4px;">No se encontraron ensayos coincidentes</div>
                <div style="font-size: 13px; color: #64748b;">Prueba buscando sin guiones o con palabras clave (ej: "pe 25", "densidad", "nuclear", "astm").</div>
                <button type="button" class="btn-premium-azul" style="margin-top: 14px; font-size: 12.5px; padding: 7px 16px; display: inline-flex; align-items: center; gap: 6px;" onclick="limpiarBusquedaProductos()">
                    <i class="fa-solid fa-arrow-rotate-left"></i> Restablecer búsqueda
                </button>
            </div>
            <?php foreach ($productos as $p): ?>
                <?php
                $nom_c = !empty($p['nombre_comercial']) ? trim($p['nombre_comercial']) : trim($p['ensayo_servicio']);
                $ensayo_t = trim($p['ensayo_servicio']);
                $proc_t = $p['procedimiento_muestreo'] ?? '';
                $cod_t = $p['codigo_servicio'] ?? '';
                $norma_t = $p['norma_astm'] ?? '';
                $matriz_t = $p['matriz_tipo'] ?? '';
                $tipo_m = $p['tipo_muestra'] ?? '';
                $hoja_c = $p['codigo_hoja_campo'] ?? '';
                $cond_m = $p['condiciones_muestra'] ?? '';
                $no_item_t = $p['no_item'] ?? $p['id'];
                
                $proc_sp = str_replace(['-', '_', '/', '.'], ' ', $proc_t);
                $cod_sp = str_replace(['-', '_', '/', '.'], ' ', $cod_t);
                $norma_sp = str_replace(['-', '_', '/', '.'], ' ', $norma_t);
                
                $busq_item = strtolower("{$no_item_t} {$nom_c} {$ensayo_t} {$cod_t} {$cod_sp} {$proc_t} {$proc_sp} {$norma_t} {$norma_sp} {$matriz_t} {$tipo_m} {$hoja_c} {$cond_m}");
                ?>
                <div class="acordeon-item item-catalogo-filtrable" data-text="<?= htmlspecialchars($busq_item, ENT_QUOTES, 'UTF-8') ?>" data-cat="<?= htmlspecialchars(strtolower($matriz_t), ENT_QUOTES, 'UTF-8') ?>">
                    <div class="acordeon-header" onclick="toggleAcordeon('acordeon-<?= $p['id'] ?>')">
                        <div style="display: flex; align-items: center; gap: 12px; flex: 1;">
                            <span class="badge-no-item">#<?= htmlspecialchars($p['no_item'] ?? $p['id'], ENT_QUOTES, 'UTF-8') ?></span>
                            <span class="badge-code"><?= htmlspecialchars($p['codigo_servicio'] ?? 'S/C', ENT_QUOTES, 'UTF-8') ?></span>
                            <div style="font-weight: 700; color: #0f172a; font-size: 14.5px;">
                                <?= htmlspecialchars($p['nombre_comercial'] ?? 'Sin nombre comercial', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <span class="badge-category"><?= htmlspecialchars($p['matriz_tipo'] ?? 'Otros', ENT_QUOTES, 'UTF-8') ?></span>
                            <?php if (strtolower($p['estatus'] ?? '') === 'acreditado'): ?>
                                <span class="badge-acred"><i class="fa-solid fa-certificate"></i> Acreditado</span>
                            <?php endif; ?>
                        </div>

                        <div style="display: flex; align-items: center; gap: 20px;">
                            <div style="text-align: right;">
                                <span style="font-weight: 800; font-size: 15px; color: #0f172a;">C$ <?= number_format($p['precio'], 2) ?></span>
                                <span style="font-size: 11px; color: #64748b; display: block;">/ <?= htmlspecialchars($p['unidad_medida'] ?? 'Unidad', ENT_QUOTES, 'UTF-8') ?></span>
                            </div>
                            <i class="fa-solid fa-chevron-down" id="arrow-acordeon-<?= $p['id'] ?>" style="color: #94a3b8; transition: transform 0.2s;"></i>
                        </div>
                    </div>

                    <!-- Contenido Desplegable del Acordeón con Viñetas -->
                    <div class="acordeon-body" id="acordeon-<?= $p['id'] ?>">
                        <div style="margin-bottom: 12px; font-size: 13.5px; color: #1e293b; background: white; padding: 12px 16px; border-radius: 6px; border: 1px solid #e2e8f0;">
                            <strong><i class="fa-solid fa-microscope" style="color: var(--cycsa-azul);"></i> Descripción Técnica:</strong> <?= htmlspecialchars($p['ensayo_servicio'], ENT_QUOTES, 'UTF-8') ?>
                        </div>

                        <!-- Bloque Condiciones de Muestra -->
                        <div class="box-condiciones">
                            <div class="box-condiciones-title">
                                <i class="fa-solid fa-box-archive"></i> Condiciones de Recepción de la Muestra:
                            </div>
                            <p class="box-condiciones-text">
                                <?= !empty($p['condiciones_muestra']) ? htmlspecialchars($p['condiciones_muestra'], ENT_QUOTES, 'UTF-8') : '<em>Sin condiciones especiales de empaque o traslado especificadas.</em>' ?>
                            </p>
                        </div>

                        <ul class="vinetas-lista" style="background: white; padding: 14px; border-radius: 8px; border: 1px solid #e2e8f0;">
                            <li class="vineta-item"><i class="fa-solid fa-vial vineta-icon"></i> <div><span class="vineta-label">Tipo de Muestra:</span> <?= htmlspecialchars($p['tipo_muestra'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div></li>
                            <li class="vineta-item"><i class="fa-solid fa-hand-holding-droplet vineta-icon"></i> <div><span class="vineta-label">Tipo Muestreo:</span> <?= htmlspecialchars($p['tipo_muestreo'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div></li>
                            <li class="vineta-item"><i class="fa-solid fa-scroll vineta-icon"></i> <div><span class="vineta-label">Norma ASTM:</span> <?= htmlspecialchars($p['norma_astm'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div></li>
                            <li class="vineta-item"><i class="fa-solid fa-gears vineta-icon"></i> <div><span class="vineta-label">Procedimiento CYCSA-PE:</span> <?= htmlspecialchars($p['procedimiento_muestreo'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div></li>
                            <li class="vineta-item"><i class="fa-solid fa-clipboard-list vineta-icon"></i> <div><span class="vineta-label">Hoja de Campo:</span> <?= htmlspecialchars($p['codigo_hoja_campo'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></div></li>
                            <li class="vineta-item"><i class="fa-solid fa-clock vineta-icon"></i> <div><span class="vineta-label">Tiempo Entrega:</span> <?= htmlspecialchars($p['observaciones'] ?? 'A convenir', ENT_QUOTES, 'UTF-8') ?></div></li>
                        </ul>

                        <div style="display: flex; justify-content: flex-end; gap: 10px; margin-top: 12px;">
                            <button type="button" class="btn-accion ver" onclick='abrirFichaTecnica(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)'>
                                <i class="fa-solid fa-eye"></i> Ficha Completa
                            </button>
                            <?php if (tienePermiso('productos', 'crear_editar')): ?>
                            <a href="/Cycsa/publico/productos/editar?id=<?= codificarId($p['id']) ?>" class="btn-accion editar"><i class="fa-solid fa-pen-to-square"></i> Editar</a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>

        <!-- =======================================================================
             📊 VISTA 3: TABLA RESUMEN COMPACTA
             ======================================================================= -->
        <div id="contenedor-tabla" class="tabla-container" style="display: none;">
            <table class="tabla-premium">
                <thead>
                    <tr>
                        <th style="width: 45px; text-align: center;">No</th>
                        <th>Código</th>
                        <th>Nombre Comercial</th>
                        <th>Matriz</th>
                        <th>Norma ASTM</th>
                        <th>Estatus</th>
                        <th>Condiciones Muestra</th>
                        <th>Entrega / Obs</th>
                        <th style="text-align: right;">Precio Oficial</th>
                        <th style="text-align: center;">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <tr id="no-results-tabla" style="display: none;">
                        <td colspan="10" style="text-align: center; padding: 40px 20px; color: #64748b; background: #f8fafc;">
                            <i class="fa-solid fa-flask-vial" style="font-size: 32px; color: #cbd5e1; margin-bottom: 10px; display: block;"></i>
                            <div style="font-weight: 600; font-size: 15px; color: #334155; margin-bottom: 4px;">No se encontraron ensayos coincidentes</div>
                            <div style="font-size: 12.5px; color: #64748b;">Prueba buscando sin guiones o con palabras clave (ej: "pe 25", "densidad", "nuclear", "astm").</div>
                            <button type="button" class="btn-premium-azul" style="margin-top: 12px; font-size: 12px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;" onclick="limpiarBusquedaProductos()">
                                <i class="fa-solid fa-arrow-rotate-left"></i> Restablecer búsqueda
                            </button>
                        </td>
                    </tr>
                    <?php foreach ($productos as $p): ?>
                        <?php
                        $nom_c = !empty($p['nombre_comercial']) ? trim($p['nombre_comercial']) : trim($p['ensayo_servicio']);
                        $ensayo_t = trim($p['ensayo_servicio']);
                        $proc_t = $p['procedimiento_muestreo'] ?? '';
                        $cod_t = $p['codigo_servicio'] ?? '';
                        $norma_t = $p['norma_astm'] ?? '';
                        $matriz_t = $p['matriz_tipo'] ?? '';
                        $tipo_m = $p['tipo_muestra'] ?? '';
                        $hoja_c = $p['codigo_hoja_campo'] ?? '';
                        $cond_m = $p['condiciones_muestra'] ?? '';
                        $no_item_t = $p['no_item'] ?? $p['id'];
                        
                        $proc_sp = str_replace(['-', '_', '/', '.'], ' ', $proc_t);
                        $cod_sp = str_replace(['-', '_', '/', '.'], ' ', $cod_t);
                        $norma_sp = str_replace(['-', '_', '/', '.'], ' ', $norma_t);
                        
                        $busq_item = strtolower("{$no_item_t} {$nom_c} {$ensayo_t} {$cod_t} {$cod_sp} {$proc_t} {$proc_sp} {$norma_t} {$norma_sp} {$matriz_t} {$tipo_m} {$hoja_c} {$cond_m}");
                        ?>
                        <tr class="item-catalogo-filtrable" data-text="<?= htmlspecialchars($busq_item, ENT_QUOTES, 'UTF-8') ?>" data-cat="<?= htmlspecialchars(strtolower($matriz_t), ENT_QUOTES, 'UTF-8') ?>">
                            <td style="font-weight: 800; text-align: center; color: #0f172a;">
                                <?= htmlspecialchars($p['no_item'] ?? $p['id'], ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td><span class="badge-code"><?= htmlspecialchars($p['codigo_servicio'] ?? 'S/C', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td style="font-weight: 600; color: #0f172a; min-width: 180px;">
                                <?= htmlspecialchars($p['nombre_comercial'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td><span class="badge-category"><?= htmlspecialchars($p['matriz_tipo'] ?? 'Otros', ENT_QUOTES, 'UTF-8') ?></span></td>
                            <td style="font-size: 12px; font-weight: 600; color: #92400e;"><?= htmlspecialchars($p['norma_astm'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?></td>
                            <td>
                                <?php if (strtolower($p['estatus'] ?? '') === 'acreditado'): ?>
                                    <span class="badge-acred">Acreditado</span>
                                <?php else: ?>
                                    <span class="badge-no-acred">No Acreditado</span>
                                <?php endif; ?>
                            </td>
                            <td style="max-width: 220px; font-size: 11.5px; color: #475569;">
                                <?= !empty($p['condiciones_muestra']) ? htmlspecialchars($p['condiciones_muestra'], ENT_QUOTES, 'UTF-8') : '<span style="color:#94a3b8;">N/A</span>' ?>
                            </td>
                            <td style="font-size: 12px; font-weight: 600;"><?= htmlspecialchars($p['observaciones'] ?? 'A convenir', ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="text-align: right; font-weight: 800; color: #0f172a; white-space: nowrap;">
                                C$ <?= number_format($p['precio'], 2) ?>
                                <span style="font-size: 11px; color: #64748b; font-weight: normal; display: block;">/ <?= htmlspecialchars($p['unidad_medida'] ?? 'Unidad', ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td style="text-align: center; white-space: nowrap;">
                                <button type="button" class="btn-accion ver" onclick='abrirFichaTecnica(<?= json_encode($p, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_HEX_AMP | JSON_UNESCAPED_UNICODE) ?>)' title="Ver Ficha">
                                    <i class="fa-solid fa-eye"></i>
                                </button>
                                <?php if (tienePermiso('productos', 'crear_editar')): ?>
                                <a href="/Cycsa/publico/productos/editar?id=<?= codificarId($p['id']) ?>" class="btn-accion editar" title="Editar"><i class="fa-solid fa-pen-to-square"></i></a>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>

    <?php endif; ?>
</div>

<!-- =======================================================================
     MODAL FICHA TÉCNICA INTEGRAL (15 SECCIONES DEL EXCEL)
     ======================================================================= -->
<div id="modal-ficha-tecnica" class="modal-overlay" onclick="cerrarFichaTecnica(event)">
    <div class="modal-card" onclick="event.stopPropagation()">
        <div class="modal-header">
            <div>
                <h4 class="modal-title" id="ft-titulo">
                    <i class="fa-solid fa-flask-vial" style="color: var(--cycsa-azul);"></i> Ficha Técnica del Ensayo
                </h4>
                <div style="font-size: 12px; color: #64748b; margin-top: 3px;" id="ft-subtitulo">
                    Código de Formato / Servicio: <strong id="ft-codigo">-</strong>
                </div>
            </div>
            <button type="button" class="modal-close" onclick="cerrarFichaTecnica()">&times;</button>
        </div>
        <div class="modal-body">
            <div class="ficha-grid-modal">
                <!-- 1. No & Estatus -->
                <div class="ficha-box-modal">
                    <div class="ficha-label-modal"><i class="fa-solid fa-hashtag"></i> 1. No. de Ítem & Estatus</div>
                    <div class="ficha-val-modal" style="display: flex; align-items: center; gap: 10px;">
                        <span style="font-size: 16px; font-weight: 800; color: #0f172a;" id="ft-no">-</span>
                        <span id="ft-estatus-badge">-</span>
                    </div>
                </div>

                <!-- 8. Código Servicio -->
                <div class="ficha-box-modal">
                    <div class="ficha-label-modal"><i class="fa-solid fa-barcode"></i> 8. Código de Formato / Servicio</div>
                    <div class="ficha-val-modal" id="ft-codigo-serv">-</div>
                </div>

                <!-- 6. Nombre Comercial -->
                <div class="ficha-box-modal full">
                    <div class="ficha-label-modal"><i class="fa-solid fa-tag"></i> 6. Nombre Comercial</div>
                    <div class="ficha-val-modal" style="font-weight: 700; font-size: 15px; color: #0f172a;" id="ft-nombre-comercial">-</div>
                </div>

                <!-- 5. Ensayo / Servicio -->
                <div class="ficha-box-modal full">
                    <div class="ficha-label-modal"><i class="fa-solid fa-microscope"></i> 5. Ensayo y/o Servicio a Realizar (Descripción Técnica)</div>
                    <div class="ficha-val-modal" id="ft-ensayo-servicio" style="line-height: 1.45;">-</div>
                </div>

                <!-- 2. Tipo Muestra & 3. Matriz -->
                <div class="ficha-box-modal">
                    <div class="ficha-label-modal"><i class="fa-solid fa-vial"></i> 2. Tipo de Muestras</div>
                    <div class="ficha-val-modal" id="ft-tipo-muestra">-</div>
                </div>

                <div class="ficha-box-modal">
                    <div class="ficha-label-modal"><i class="fa-solid fa-layer-group"></i> 3. Matriz / Tipo</div>
                    <div class="ficha-val-modal" id="ft-matriz-tipo">-</div>
                </div>

                <!-- 4. Tipo Muestreo & 10. Norma ASTM -->
                <div class="ficha-box-modal">
                    <div class="ficha-label-modal"><i class="fa-solid fa-hand-holding-droplet"></i> 4. Tipo de Muestreo</div>
                    <div class="ficha-val-modal" id="ft-tipo-muestreo">-</div>
                </div>

                <div class="ficha-box-modal">
                    <div class="ficha-label-modal"><i class="fa-solid fa-book-bookmark"></i> 10. Norma ASTM / Método</div>
                    <div class="ficha-val-modal" id="ft-norma-astm">-</div>
                </div>

                <!-- 11. Proc CYCSA-PE & 12. Código Hoja Campo -->
                <div class="ficha-box-modal">
                    <div class="ficha-label-modal"><i class="fa-solid fa-gears"></i> 11. Procedimiento de Muestreo CYCSA-PE</div>
                    <div class="ficha-val-modal" id="ft-proc-muestreo">-</div>
                </div>

                <div class="ficha-box-modal">
                    <div class="ficha-label-modal"><i class="fa-solid fa-clipboard-list"></i> 12. Código Hoja de Campo</div>
                    <div class="ficha-val-modal" id="ft-codigo-hoja-campo">-</div>
                </div>

                <!-- 13. Unidad & 14. Precio -->
                <div class="ficha-box-modal">
                    <div class="ficha-label-modal"><i class="fa-solid fa-ruler"></i> 13. Unidad de Medida</div>
                    <div class="ficha-val-modal" id="ft-unidad-medida">-</div>
                </div>

                <div class="ficha-box-modal" style="background: #f0fdf4; border-color: #bbf7d0;">
                    <div class="ficha-label-modal" style="color: #166534;"><i class="fa-solid fa-money-bill-wave"></i> 14. Precio Unitario Oficial</div>
                    <div class="ficha-val-modal" style="font-size: 18px; font-weight: 800; color: #15803d;" id="ft-precio">-</div>
                </div>

                <!-- 7. Condiciones de Muestra -->
                <div class="ficha-box-modal full" style="background: #fffbeb; border-color: #fde68a;">
                    <div class="ficha-label-modal" style="color: #92400e;"><i class="fa-solid fa-box-archive"></i> 7. Condiciones de Muestra (Recepción / Custodia)</div>
                    <div class="ficha-val-modal" id="ft-condiciones-muestra" style="line-height: 1.45; color: #78350f;">-</div>
                </div>

                <!-- 15. Tiempo de Entrega / Observaciones -->
                <div class="ficha-box-modal full">
                    <div class="ficha-label-modal"><i class="fa-solid fa-clock-rotate-left"></i> 15. Tiempo de Entrega / Observaciones</div>
                    <div class="ficha-val-modal" id="ft-observaciones" style="line-height: 1.45;">-</div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Normalizador de texto para búsqueda flexible (remueve tildes, mayúsculas y diacríticos)
    function normalizarTextoBusqueda(str) {
        if (!str) return '';
        return str
            .toString()
            .toLowerCase()
            .normalize("NFD")
            .replace(/[\u0300-\u036f]/g, "")
            .trim();
    }

    function filtrarProductosEnVivo() {
        const inputEl = document.getElementById('search-productos-input');
        const queryRaw = inputEl ? inputEl.value : '';
        const selectCat = document.getElementById('select-cat-productos');
        const cat = selectCat ? normalizarTextoBusqueda(selectCat.value) : '';
        const btnClear = document.getElementById('btn-clear-search-productos');
        const counterEl = document.getElementById('contador-productos-vivo');
        
        if (btnClear) {
            btnClear.style.display = queryRaw.trim().length > 0 ? 'inline-block' : 'none';
        }

        const queryNorm = normalizarTextoBusqueda(queryRaw);
        const queryCompact = queryNorm.replace(/[^a-z0-9]/g, '');
        const tokens = queryNorm.replace(/[-_/.,;:()+*]/g, ' ').split(/\s+/).filter(t => t.length > 0);

        const items = document.querySelectorAll('.item-catalogo-filtrable');
        const noResultsFichas = document.getElementById('no-results-fichas');
        const noResultsAcordeon = document.getElementById('no-results-acordeon');
        const noResultsTabla = document.getElementById('no-results-tabla');

        let visiblesFichas = 0;
        let visiblesAcordeon = 0;
        let visiblesTabla = 0;

        items.forEach(el => {
            if (!el._normText) {
                const raw = (el.getAttribute('data-text') || '') + ' ' + (el.innerText || '');
                const norm = normalizarTextoBusqueda(raw);
                el._normText = norm;
                el._spacedText = norm.replace(/[-_/.,;:()+*]/g, ' ');
                el._compactText = norm.replace(/[^a-z0-9]/g, '');
            }

            const itemCat = normalizarTextoBusqueda(el.getAttribute('data-cat') || '');
            const matchesCat = (cat === '' || itemCat === cat || itemCat.includes(cat));

            let matchesQuery = true;
            if (queryNorm !== '') {
                const matchesCompact = (queryCompact.length >= 2 && el._compactText.includes(queryCompact));
                const matchesTokens = tokens.length > 0 && tokens.every(tok => {
                    const tokCompact = tok.replace(/[^a-z0-9]/g, '');
                    return el._spacedText.includes(tok) || 
                           el._normText.includes(tok) || 
                           (tokCompact.length >= 2 && el._compactText.includes(tokCompact));
                });
                matchesQuery = matchesCompact || matchesTokens;
            }

            if (matchesQuery && matchesCat) {
                el.style.display = '';
                if (el.classList.contains('ficha-card')) visiblesFichas++;
                else if (el.classList.contains('acordeon-item')) visiblesAcordeon++;
                else if (el.tagName === 'TR') visiblesTabla++;
            } else {
                el.style.display = 'none';
            }
        });

        if (noResultsFichas) noResultsFichas.style.display = (visiblesFichas === 0) ? 'block' : 'none';
        if (noResultsAcordeon) noResultsAcordeon.style.display = (visiblesAcordeon === 0) ? 'block' : 'none';
        if (noResultsTabla) noResultsTabla.style.display = (visiblesTabla === 0) ? '' : 'none';

        const totalVisibles = Math.max(visiblesFichas, visiblesAcordeon, visiblesTabla);
        const totalItems = document.querySelectorAll('#contenedor-fichas .ficha-card').length;

        if (counterEl) {
            if (queryNorm !== '' || cat !== '') {
                counterEl.innerHTML = `<i class="fa-solid fa-filter" style="color:var(--cycsa-azul);"></i> Mostrando <strong>${totalVisibles}</strong> de ${totalItems} ensayos coincidentes`;
            } else {
                counterEl.innerHTML = `Mostrando todos los ensayos (<strong>${totalItems}</strong>)`;
            }
        }
    }

    function limpiarBusquedaProductos() {
        const input = document.getElementById('search-productos-input');
        if (input) {
            input.value = '';
            input.focus();
        }
        filtrarProductosEnVivo();
    }

    function filtrarPorCategoriaEnVivo(catVal) {
        filtrarProductosEnVivo();
    }

    // Gestión del cambio de modo de vista
    function cambiarVista(modo) {
        const f = document.getElementById('contenedor-fichas');
        const a = document.getElementById('contenedor-acordeon');
        const t = document.getElementById('contenedor-tabla');
        
        const btnF = document.getElementById('btn-vista-fichas');
        const btnA = document.getElementById('btn-vista-acordeon');
        const btnT = document.getElementById('btn-vista-tabla');

        if (btnF) btnF.classList.remove('activo');
        if (btnA) btnA.classList.remove('activo');
        if (btnT) btnT.classList.remove('activo');

        if (f) f.style.display = 'none';
        if (a) a.style.display = 'none';
        if (t) t.style.display = 'none';

        if (modo === 'fichas' && f) {
            f.style.display = 'grid';
            if (btnF) btnF.classList.add('activo');
            localStorage.setItem('cycsa_productos_view', 'fichas');
        } else if (modo === 'acordeon' && a) {
            a.style.display = 'flex';
            if (btnA) btnA.classList.add('activo');
            localStorage.setItem('cycsa_productos_view', 'acordeon');
        } else if (modo === 'tabla' && t) {
            t.style.display = 'block';
            if (btnT) btnT.classList.add('activo');
            localStorage.setItem('cycsa_productos_view', 'tabla');
        }
    }

    // Recordar preferencia de vista del usuario
    document.addEventListener('DOMContentLoaded', function() {
        const savedView = localStorage.getItem('cycsa_productos_view') || 'fichas';
        cambiarVista(savedView);
        
        const input = document.getElementById('search-productos-input');
        if (input && input.value.trim().length > 0) {
            filtrarProductosEnVivo();
        }
    });

    // Toggle para el modo acordeón
    function toggleAcordeon(id) {
        const body = document.getElementById(id);
        const arrow = document.getElementById('arrow-' + id);
        if (!body) return;
        
        if (body.classList.contains('open')) {
            body.classList.remove('open');
            if (arrow) arrow.style.transform = 'rotate(0deg)';
        } else {
            body.classList.add('open');
            if (arrow) arrow.style.transform = 'rotate(180deg)';
        }
    }

    // Modal de Ficha Técnica
    function abrirFichaTecnica(p) {
        if (!p) return;
        document.getElementById('ft-no').textContent = '#' + (p.no_item || p.id || '-');
        document.getElementById('ft-codigo').textContent = p.codigo_servicio || 'S/C';
        document.getElementById('ft-codigo-serv').textContent = p.codigo_servicio || 'S/C';
        document.getElementById('ft-nombre-comercial').textContent = p.nombre_comercial || 'N/A';
        document.getElementById('ft-ensayo-servicio').textContent = p.ensayo_servicio || 'N/A';
        document.getElementById('ft-tipo-muestra').textContent = p.tipo_muestra || 'N/A';
        document.getElementById('ft-matriz-tipo').textContent = p.matriz_tipo || 'N/A';
        document.getElementById('ft-tipo-muestreo').textContent = p.tipo_muestreo || 'N/A';
        document.getElementById('ft-norma-astm').textContent = p.norma_astm || 'N/A';
        document.getElementById('ft-proc-muestreo').textContent = p.procedimiento_muestreo || 'N/A';
        document.getElementById('ft-codigo-hoja-campo').textContent = p.codigo_hoja_campo || 'N/A';
        document.getElementById('ft-unidad-medida').textContent = p.unidad_medida || 'Unidad';
        document.getElementById('ft-condiciones-muestra').textContent = p.condiciones_muestra || 'Sin condiciones especiales especificadas.';
        document.getElementById('ft-observaciones').textContent = p.observaciones || 'A convenir';
        
        const precioFormatted = parseFloat(p.precio || 0).toLocaleString('es-NI', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
        document.getElementById('ft-precio').textContent = 'C$ ' + precioFormatted + ' / ' + (p.unidad_medida || 'Unidad');

        const estBadge = document.getElementById('ft-estatus-badge');
        if (p.estatus && p.estatus.toLowerCase() === 'acreditado') {
            estBadge.className = 'badge-acred';
            estBadge.innerHTML = '<i class="fa-solid fa-certificate"></i> Acreditado (ISO 17025)';
        } else {
            estBadge.className = 'badge-no-acred';
            estBadge.textContent = 'No Acreditado';
        }

        document.getElementById('modal-ficha-tecnica').style.display = 'flex';
    }

    function cerrarFichaTecnica(event) {
        if (event && event.target && event.target.id !== 'modal-ficha-tecnica') return;
        document.getElementById('modal-ficha-tecnica').style.display = 'none';
    }
</script>

<?php
$bitacora_modulo_nombre = 'Catálogo de Ensayos';
include dirname(__DIR__, 3) . '/Views/parciales/bitacora_modulo.php';
?>
