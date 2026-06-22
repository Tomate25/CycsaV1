<?php
// Calcular algunas métricas rápidas para las tarjetas de KPI
$totalEnsayos = count($productos);
$acreditados = 0;
$noAcreditados = 0;
$sumaPrecios = 0;
$conPrecio = 0;

foreach ($productos as $p) {
    if (strtolower($p['estatus']) === 'acreditado') {
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
    
    /* Buscador y Filtros */
    .filtro-barra { display: flex; gap: 15px; width: 100%; max-width: 600px; flex-wrap: wrap; }
    .search-input-wrapper { position: relative; flex: 1; min-width: 250px; }
    .search-input-wrapper i { position: absolute; left: 15px; top: 50%; transform: translateY(-50%); color: #a0aec0; }
    .search-input { width: 100%; padding: 11px 15px 11px 45px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px; outline: none; transition: all 0.2s; }
    .search-input:focus { border-color: var(--cycsa-azul); box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.1); }
    
    .cat-select { padding: 11px 15px; border: 1px solid #e2e8f0; border-radius: 6px; font-size: 14px; background: white; outline: none; min-width: 180px; }
    
    /* Categorías como Píldoras */
    .pildoras-container { display: flex; gap: 10px; overflow-x: auto; padding: 5px 0 15px 0; margin-bottom: 20px; border-bottom: 1px solid #edf2f7; scrollbar-width: thin; }
    .pildora-link { display: inline-block; padding: 8px 16px; background: #edf2f7; color: #4a5568; border-radius: 20px; font-size: 13px; font-weight: 600; text-decoration: none; transition: all 0.2s; white-space: nowrap; }
    .pildora-link:hover { background: #e2e8f0; color: #2d3748; }
    .pildora-link.activa { background: var(--cycsa-azul); color: white; box-shadow: 0 4px 6px rgba(16, 52, 135, 0.15); }
    
    /* Tabla Estilizada */
    .tabla-container { overflow-x: auto; }
    .tabla-premium { width: 100%; border-collapse: collapse; text-align: left; }
    .tabla-premium th { padding: 15px; font-size: 12px; font-weight: 700; text-transform: uppercase; color: #718096; background: #f8fafc; border-bottom: 2px solid #e2e8f0; }
    .tabla-premium td { padding: 16px 15px; border-bottom: 1px solid #edf2f7; font-size: 14px; color: #4a5568; vertical-align: middle; }
    .tabla-premium tr:hover { background-color: #fcfdfe; }
    
    /* Badges */
    .badge-acred { background-color: rgba(46, 196, 182, 0.1); color: #0f9f90; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; display: inline-block; }
    .badge-no-acred { background-color: rgba(160, 174, 192, 0.1); color: #718096; padding: 4px 10px; border-radius: 20px; font-weight: 600; font-size: 11px; text-transform: uppercase; letter-spacing: 0.3px; display: inline-block; }
    .badge-category { background-color: #ebf8ff; color: #2b6cb0; padding: 4px 8px; border-radius: 4px; font-weight: 500; font-size: 12px; display: inline-block; }
    
    /* Acciones */
    .btn-premium-azul { background: var(--cycsa-azul); color: white; border: none; padding: 10px 20px; border-radius: 6px; font-weight: 600; font-size: 14px; text-decoration: none; cursor: pointer; display: inline-flex; align-items: center; gap: 8px; box-shadow: 0 4px 6px rgba(16, 52, 135, 0.15); transition: background 0.2s; }
    .btn-premium-azul:hover { background: #0c2766; }
    .btn-accion { color: #718096; background: none; border: none; font-size: 16px; padding: 5px; cursor: pointer; transition: color 0.2s; text-decoration: none; margin-right: 8px; }
    .btn-accion.editar:hover { color: var(--cycsa-azul); }
    .btn-accion.eliminar:hover { color: var(--cycsa-rojo); }
    
    .text-muted-small { font-size: 12px; color: #a0aec0; display: block; margin-top: 4px; }
</style>

<!-- Tarjetas KPI -->
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
        <h3 class="catalogo-titulo">Catálogo General</h3>
        
        <form method="GET" action="/Cycsa/publico/productos" class="filtro-barra" id="form-busqueda">
            <div class="search-input-wrapper">
                <i class="fa-solid fa-magnifying-glass"></i>
                <input type="text" name="q" class="search-input" placeholder="Buscar por nombre, código ASTM, procedimiento..." value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>">
            </div>
            
            <select name="cat" class="cat-select" onchange="document.getElementById('form-busqueda').submit()">
                <option value="">Todas las Matrices</option>
                <?php foreach ($categorias as $cat): ?>
                    <option value="<?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>" <?= $categoria_actual === $cat ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <?php if (tienePermiso('productos', 'crear_editar')): ?>
            <a href="/Cycsa/publico/productos/crear" class="btn-premium-azul">
                <i class="fa-solid fa-plus"></i> Nuevo Ensayo
            </a>
            <?php endif; ?>
        </form>
    </div>

    <!-- Píldoras de Categoría Rápidas -->
    <div class="pildoras-container">
        <a href="/Cycsa/publico/productos?q=<?= urlencode($busqueda) ?>" class="pildora-link <?= $categoria_actual === '' ? 'activa' : '' ?>">
            Todos
        </a>
        <?php foreach ($categorias as $cat): ?>
            <a href="/Cycsa/publico/productos?cat=<?= urlencode($cat) ?>&q=<?= urlencode($busqueda) ?>" class="pildora-link <?= $categoria_actual === $cat ? 'activa' : '' ?>">
                <?= htmlspecialchars($cat, ENT_QUOTES, 'UTF-8') ?>
            </a>
        <?php endforeach; ?>
    </div>

    <!-- Tabla de Contenido -->
    <div class="tabla-container">
        <?php if (empty($productos)): ?>
            <div style="text-align: center; padding: 40px; color: #a0aec0;">
                <i class="fa-solid fa-box-open" style="font-size: 48px; margin-bottom: 15px; display: block;"></i>
                No se encontraron ensayos o servicios registrados con los criterios seleccionados.
            </div>
        <?php else: ?>
            <table class="tabla-premium">
                <thead>
                    <tr>
                        <th style="width: 5%; text-align: center;">No.</th>
                        <th style="width: 10%;">Código Servicio</th>
                        <th style="width: 35%;">Ensayo y/o Servicio</th>
                        <th style="width: 15%;">Matriz / Tipo</th>
                        <th style="width: 12%;">Estatus</th>
                        <th style="width: 13%; text-align: right;">Precio</th>
                        <?php if (tienePermiso('productos', 'crear_editar')): ?>
                        <th style="width: 10%; text-align: center;">Acciones</th>
                        <?php endif; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($productos as $p): ?>
                        <tr>
                            <td style="text-align: center; font-weight: 600; color: #718096;"><?= htmlspecialchars($p['no_item'] ?? $p['id'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td style="font-family: monospace; font-weight: bold; color: #2d3748;">
                                <?= htmlspecialchars($p['codigo_servicio'] ?? 'N/A', ENT_QUOTES, 'UTF-8') ?>
                            </td>
                            <td>
                                <div style="font-weight: 600; color: #2d3748;"><?= htmlspecialchars($p['nombre_comercial'] ?? $p['ensayo_servicio'], ENT_QUOTES, 'UTF-8') ?></div>
                                <span class="text-muted-small"><?= htmlspecialchars($p['ensayo_servicio'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php if (!empty($p['norma_astm'])): ?>
                                    <span class="text-muted-small" style="color: var(--cycsa-azul);"><i class="fa-solid fa-scroll"></i> Norma: <?= htmlspecialchars($p['norma_astm'], ENT_QUOTES, 'UTF-8') ?></span>
                                <?php endif; ?>
                            </td>
                            <td>
                                <span class="badge-category"><?= htmlspecialchars($p['matriz_tipo'] ?? 'Otros', ENT_QUOTES, 'UTF-8') ?></span>
                                <span class="text-muted-small"><?= htmlspecialchars($p['tipo_muestra'] ?? '', ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td>
                                <?php if (strtolower($p['estatus']) === 'acreditado'): ?>
                                    <span class="badge-acred"><i class="fa-solid fa-certificate"></i> Acreditado</span>
                                <?php else: ?>
                                    <span class="badge-no-acred">No Acreditado</span>
                                <?php endif; ?>
                            </td>
                            <td style="text-align: right; font-weight: 700; color: #2d3748; font-size: 15px;">
                                C$ <?= number_format($p['precio'], 2) ?>
                                <span class="text-muted-small" style="font-weight: normal;">por <?= htmlspecialchars($p['unidad_medida'] ?? 'Unidad', ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <?php if (tienePermiso('productos', 'crear_editar')): ?>
                            <td style="text-align: center;">
                                <a href="/Cycsa/publico/productos/editar?id=<?= $p['id'] ?>" class="btn-accion editar" title="Editar ensayo">
                                    <i class="fa-solid fa-pen-to-square"></i>
                                </a>
                                <a href="/Cycsa/publico/productos/eliminar?id=<?= $p['id'] ?>" class="btn-accion eliminar" title="Desactivar ensayo" onclick="return confirm('¿Está seguro de que desea desactivar este ensayo del catálogo?')">
                                    <i class="fa-solid fa-trash-can"></i>
                                </a>
                            </td>
                            <?php endif; ?>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        <?php endif; ?>
    </div>
</div>
