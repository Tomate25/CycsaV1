<?php
$totalProductos = count($productos ?? []);
$totalAcreditados = 0;
$totalConPrecio = 0;
$valorCatalogo = 0.0;
foreach (($productos ?? []) as $productoResumen) {
    if (strtolower(trim((string)($productoResumen['estatus'] ?? ''))) === 'acreditado') {
        $totalAcreditados++;
    }
    $precioResumen = (float)($productoResumen['precio'] ?? 0);
    if ($precioResumen > 0) {
        $totalConPrecio++;
        $valorCatalogo += $precioResumen;
    }
}
?>

<style>
    .inventario-shell { background:#fff; border:1px solid #e2e8f0; border-radius:12px; padding:24px; box-shadow:0 3px 14px rgba(15,23,42,.05); }
    .inventario-header { display:flex; justify-content:space-between; align-items:flex-start; gap:18px; flex-wrap:wrap; margin-bottom:20px; }
    .inventario-title { margin:0; color:#0f172a; font:800 23px 'Outfit',sans-serif; display:flex; align-items:center; gap:10px; }
    .inventario-actions { display:flex; gap:9px; flex-wrap:wrap; }
    .btn-inv { display:inline-flex; align-items:center; justify-content:center; gap:7px; border-radius:7px; padding:9px 14px; font-size:12.5px; font-weight:700; text-decoration:none; cursor:pointer; border:1px solid transparent; }
    .btn-inv-primary { background:var(--cycsa-azul); color:#fff; }
    .btn-inv-secondary { background:#f8fafc; color:#475569; border-color:#cbd5e1; }
    .btn-inv-muted { background:#f1f5f9; color:#94a3b8; border-color:#e2e8f0; cursor:not-allowed; }
    .inventario-kpis { display:grid; grid-template-columns:repeat(4,minmax(150px,1fr)); gap:12px; margin-bottom:18px; }
    .inv-kpi { border:1px solid #e2e8f0; border-radius:9px; padding:13px 15px; background:#f8fafc; }
    .inv-kpi-label { color:#64748b; font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:.05em; }
    .inv-kpi-value { color:#0f172a; font-size:21px; font-weight:800; margin-top:4px; }
    .inventario-filtros { display:flex; gap:10px; align-items:center; flex-wrap:wrap; background:#f8fafc; border:1px solid #e2e8f0; padding:13px; border-radius:9px; margin-bottom:17px; }
    .inv-input,.inv-select { padding:9px 12px; border:1px solid #cbd5e1; border-radius:6px; background:#fff; color:#1e293b; font-size:13px; box-sizing:border-box; }
    .inv-input { flex:1; min-width:280px; }
    .inventario-table-wrap { overflow-x:auto; border:1px solid #e2e8f0; border-radius:10px; }
    .inventario-table { width:100%; min-width:1450px; border-collapse:collapse; background:#fff; }
    .inventario-table th { padding:11px 10px; color:#475569; background:#f8fafc; border-bottom:2px solid #e2e8f0; font-size:10.5px; font-weight:800; text-transform:uppercase; letter-spacing:.035em; white-space:nowrap; text-align:left; }
    .inventario-table td { padding:11px 10px; border-bottom:1px solid #edf2f7; color:#334155; font-size:12px; vertical-align:middle; }
    .producto-main-row { cursor:pointer; transition:background .15s ease; }
    .producto-main-row:hover,.producto-main-row.abierta { background:#f8fafc; }
    .producto-main-row.abierta .inv-chevron { transform:rotate(90deg); color:var(--cycsa-azul); }
    .inv-chevron { transition:transform .2s ease; color:#94a3b8; }
    .inv-code { display:inline-block; font:700 11.5px monospace; color:#103487; background:#eff6ff; border:1px solid #bfdbfe; border-radius:5px; padding:3px 6px; }
    .inv-name { color:#0f172a; font-weight:750; line-height:1.35; max-width:250px; }
    .inv-matrix { display:inline-block; background:#e0f2fe; color:#0369a1; padding:3px 7px; border-radius:10px; font-weight:700; font-size:10.5px; }
    .inv-status { display:inline-flex; align-items:center; gap:4px; border-radius:12px; padding:3px 7px; font-size:10.5px; font-weight:800; white-space:nowrap; }
    .inv-status-ok { color:#047857; background:#d1fae5; border:1px solid #a7f3d0; }
    .inv-status-other { color:#92400e; background:#fef3c7; border:1px solid #fde68a; }
    .inv-clamp { max-width:210px; line-height:1.35; display:-webkit-box; -webkit-line-clamp:2; -webkit-box-orient:vertical; overflow:hidden; }
    .inv-price { color:#047857; font-size:13px; font-weight:850; white-space:nowrap; }
    .producto-detail-row { display:none; background:#f8fafc; }
    .producto-detail-row.visible { display:table-row; }
    .producto-detail-container { padding:20px 24px; border-bottom:1px solid #dbe3ed; animation:invDown .18s ease-out; }
    .producto-detail-grid { display:grid; grid-template-columns:repeat(4,minmax(210px,1fr)); gap:12px; }
    .producto-detail-card { background:#fff; border:1px solid #e2e8f0; border-radius:8px; padding:14px; }
    .producto-detail-title { color:#103487; font-size:11px; font-weight:850; text-transform:uppercase; margin:0 0 10px; padding-bottom:7px; border-bottom:1px solid #eef2f7; }
    .producto-detail-item { display:flex; justify-content:space-between; align-items:flex-start; gap:12px; margin:7px 0; font-size:11.5px; line-height:1.4; }
    .producto-detail-label { color:#64748b; }
    .producto-detail-value { color:#1e293b; font-weight:700; text-align:right; word-break:break-word; }
    .producto-detail-wide { grid-column:span 2; }
    .inv-actions-cell { white-space:nowrap; text-align:right; }
    .inv-empty { padding:42px!important; text-align:center; color:#94a3b8!important; }
    @keyframes invDown { from{opacity:0;transform:translateY(-5px)} to{opacity:1;transform:translateY(0)} }
    @media(max-width:1100px){ .inventario-kpis{grid-template-columns:repeat(2,1fr)} .producto-detail-grid{grid-template-columns:repeat(2,1fr)} }
    @media(max-width:650px){ .inventario-shell{padding:16px} .inventario-kpis{grid-template-columns:1fr 1fr} .producto-detail-grid{grid-template-columns:1fr} .producto-detail-wide{grid-column:span 1} .inv-input{min-width:100%} }
</style>

<div class="inventario-shell">
    <div class="inventario-header">
        <div>
            <h2 class="inventario-title"><i class="fa-solid fa-boxes-stacked" style="color:var(--cycsa-azul);"></i> Inventario de Productos y Ensayos</h2>
            <p style="margin:5px 0 0;color:#64748b;font-size:13px;">Catálogo oficial en una sola vista. Presione cualquier producto para desplegar su ficha técnica completa.</p>
        </div>
        <div class="inventario-actions">
            <?php if (tienePermiso('productos', 'crear_editar')): ?>
                <button type="button" class="btn-inv btn-inv-secondary" onclick="abrirModalCargaMasiva()"><i class="fa-solid fa-file-excel text-success"></i> Carga masiva</button>
                <a href="/Cycsa/publico/productos/crear" class="btn-inv btn-inv-primary"><i class="fa-solid fa-plus"></i> Nuevo producto</a>
            <?php endif; ?>
        </div>
    </div>

    <?php if (!empty($_SESSION['productos_error'])): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($_SESSION['productos_error'], ENT_QUOTES, 'UTF-8') ?></div>
        <?php unset($_SESSION['productos_error']); ?>
    <?php endif; ?>

    <div class="inventario-kpis">
        <div class="inv-kpi"><div class="inv-kpi-label">Productos visibles</div><div class="inv-kpi-value"><?= $totalProductos ?></div></div>
        <div class="inv-kpi"><div class="inv-kpi-label">Ensayos acreditados</div><div class="inv-kpi-value" style="color:#047857;"><?= $totalAcreditados ?></div></div>
        <div class="inv-kpi"><div class="inv-kpi-label">Con precio oficial</div><div class="inv-kpi-value"><?= $totalConPrecio ?></div></div>
        <div class="inv-kpi"><div class="inv-kpi-label">Suma referencial</div><div class="inv-kpi-value" style="font-size:18px;">C$ <?= number_format($valorCatalogo, 2) ?></div></div>
    </div>

    <form method="GET" action="/Cycsa/publico/productos" class="inventario-filtros">
        <input class="inv-input" type="search" name="q" value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por código, nombre, matriz, norma ASTM o procedimiento...">
        <select class="inv-select" name="cat">
            <option value="">Todas las matrices</option>
            <?php foreach (($categorias ?? []) as $categoria): ?>
                <option value="<?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?>" <?= ($categoria_actual ?? '') === $categoria ? 'selected' : '' ?>><?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?></option>
            <?php endforeach; ?>
        </select>
        <button type="submit" class="btn-inv btn-inv-primary"><i class="fa-solid fa-magnifying-glass"></i> Buscar</button>
        <?php if (!empty($busqueda) || !empty($categoria_actual)): ?><a class="btn-inv btn-inv-secondary" href="/Cycsa/publico/productos"><i class="fa-solid fa-xmark"></i> Limpiar</a><?php endif; ?>
    </form>

    <div class="inventario-table-wrap">
        <table class="inventario-table" id="tabla-inventario-productos">
            <thead>
                <tr>
                    <th style="width:26px;"></th>
                    <th>No</th>
                    <th>Código</th>
                    <th>Nombre Comercial</th>
                    <th>Matriz</th>
                    <th>Norma ASTM</th>
                    <th>Estatus</th>
                    <th>Condiciones Muestra</th>
                    <th>Entrega / Obs</th>
                    <th>Precio Oficial</th>
                    <th style="text-align:right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
            <?php foreach (($productos ?? []) as $producto):
                $idProducto = (int)$producto['id'];
                $esAcreditado = strtolower(trim((string)($producto['estatus'] ?? ''))) === 'acreditado';
                $nombre = trim((string)($producto['nombre_comercial'] ?? '')) ?: trim((string)($producto['ensayo_servicio'] ?? ''));
            ?>
                <tr class="producto-main-row" id="producto-row-<?= $idProducto ?>" onclick="toggleProductoDetalle(<?= $idProducto ?>)">
                    <td><i class="fa-solid fa-chevron-right inv-chevron"></i></td>
                    <td style="font-weight:800;color:#64748b;"><?= htmlspecialchars((string)($producto['no_item'] ?? $idProducto), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="inv-code"><?= htmlspecialchars((string)($producto['codigo_servicio'] ?? 'S/C'), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><div class="inv-name"><?= htmlspecialchars($nombre ?: 'Sin nombre comercial', ENT_QUOTES, 'UTF-8') ?></div><small style="color:#2563eb;font-weight:700;">Ver ficha completa</small></td>
                    <td><span class="inv-matrix"><?= htmlspecialchars((string)($producto['matriz_tipo'] ?? 'Sin matriz'), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td style="font-family:monospace;font-weight:700;"><?= htmlspecialchars((string)($producto['norma_astm'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></td>
                    <td><span class="inv-status <?= $esAcreditado ? 'inv-status-ok' : 'inv-status-other' ?>"><i class="fa-solid <?= $esAcreditado ? 'fa-circle-check' : 'fa-circle-info' ?>"></i><?= htmlspecialchars((string)($producto['estatus'] ?? 'No acreditado'), ENT_QUOTES, 'UTF-8') ?></span></td>
                    <td><div class="inv-clamp" title="<?= htmlspecialchars((string)($producto['condiciones_muestra'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($producto['condiciones_muestra'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div></td>
                    <td><div class="inv-clamp" title="<?= htmlspecialchars((string)($producto['observaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)($producto['observaciones'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></div></td>
                    <td><span class="inv-price">C$ <?= number_format((float)($producto['precio'] ?? 0), 2) ?></span><br><small style="color:#64748b;"><?= htmlspecialchars((string)($producto['unidad_medida'] ?? 'Unidad'), ENT_QUOTES, 'UTF-8') ?></small></td>
                    <td class="inv-actions-cell" onclick="event.stopPropagation();">
                        <?php if (tienePermiso('productos', 'crear_editar')): ?>
                            <a class="btn-inv btn-inv-secondary" style="padding:6px 9px;" href="/Cycsa/publico/productos/editar?id=<?= codificarId($idProducto) ?>" title="Editar producto"><i class="fa-solid fa-pen-to-square"></i></a>
                            <form action="/Cycsa/publico/productos/eliminar" method="POST" style="display:inline;" onsubmit="return confirm('¿Desactivar este producto del inventario?');">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="id" value="<?= codificarId($idProducto) ?>">
                                <button class="btn-inv btn-inv-secondary" style="padding:6px 9px;color:#b91c1c;" type="submit" title="Desactivar producto"><i class="fa-solid fa-ban"></i></button>
                            </form>
                        <?php endif; ?>
                    </td>
                </tr>
                <tr id="producto-detail-<?= $idProducto ?>" class="producto-detail-row">
                    <td colspan="11">
                        <div class="producto-detail-container">
                            <div class="producto-detail-grid">
                                <section class="producto-detail-card producto-detail-wide">
                                    <h4 class="producto-detail-title"><i class="fa-solid fa-flask-vial"></i> Descripción técnica</h4>
                                    <div style="font-size:12.5px;color:#334155;line-height:1.55;"><?= nl2br(htmlspecialchars((string)($producto['ensayo_servicio'] ?? 'Sin descripción técnica.'), ENT_QUOTES, 'UTF-8')) ?></div>
                                </section>
                                <section class="producto-detail-card">
                                    <h4 class="producto-detail-title"><i class="fa-solid fa-vial"></i> Muestra y muestreo</h4>
                                    <div class="producto-detail-item"><span class="producto-detail-label">Tipo de muestra</span><span class="producto-detail-value"><?= htmlspecialchars((string)($producto['tipo_muestra'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></div>
                                    <div class="producto-detail-item"><span class="producto-detail-label">Tipo de muestreo</span><span class="producto-detail-value"><?= htmlspecialchars((string)($producto['tipo_muestreo'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></div>
                                    <div class="producto-detail-item"><span class="producto-detail-label">Procedimiento</span><span class="producto-detail-value"><?= htmlspecialchars((string)($producto['procedimiento_muestreo'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></div>
                                </section>
                                <section class="producto-detail-card">
                                    <h4 class="producto-detail-title"><i class="fa-solid fa-file-lines"></i> Formatos asociados</h4>
                                    <div class="producto-detail-item"><span class="producto-detail-label">Formato</span><span class="producto-detail-value"><?= htmlspecialchars((string)($producto['formato_reporte'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></div>
                                    <div class="producto-detail-item"><span class="producto-detail-label">Nombre formato</span><span class="producto-detail-value"><?= htmlspecialchars((string)($producto['formato_nombre'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></div>
                                    <div class="producto-detail-item"><span class="producto-detail-label">Hoja de campo</span><span class="producto-detail-value"><?= htmlspecialchars((string)($producto['codigo_hoja_campo'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></div>
                                </section>
                                <section class="producto-detail-card producto-detail-wide">
                                    <h4 class="producto-detail-title"><i class="fa-solid fa-box-archive"></i> Recepción, entrega y observaciones</h4>
                                    <div class="producto-detail-item"><span class="producto-detail-label">Condiciones de muestra</span><span class="producto-detail-value"><?= htmlspecialchars((string)($producto['condiciones_muestra'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></div>
                                    <div class="producto-detail-item"><span class="producto-detail-label">Entrega / observaciones</span><span class="producto-detail-value"><?= htmlspecialchars((string)($producto['observaciones'] ?? '-'), ENT_QUOTES, 'UTF-8') ?></span></div>
                                </section>
                                <section class="producto-detail-card">
                                    <h4 class="producto-detail-title"><i class="fa-solid fa-tags"></i> Datos comerciales</h4>
                                    <div class="producto-detail-item"><span class="producto-detail-label">Precio oficial</span><span class="producto-detail-value" style="color:#047857;">C$ <?= number_format((float)($producto['precio'] ?? 0), 2) ?></span></div>
                                    <div class="producto-detail-item"><span class="producto-detail-label">Unidad</span><span class="producto-detail-value"><?= htmlspecialchars((string)($producto['unidad_medida'] ?? 'Unidad'), ENT_QUOTES, 'UTF-8') ?></span></div>
                                    <div class="producto-detail-item"><span class="producto-detail-label">Registro</span><span class="producto-detail-value"><?= !empty($producto['fecha_creacion']) ? date('d/m/Y', strtotime($producto['fecha_creacion'])) : '-' ?></span></div>
                                </section>
                            </div>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            <?php if (empty($productos)): ?>
                <tr><td colspan="11" class="inv-empty"><i class="fa-regular fa-folder-open" style="font-size:28px;display:block;margin-bottom:8px;"></i>No se encontraron productos con los filtros seleccionados.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>

<script>
function toggleProductoDetalle(productoId) {
    const fila = document.getElementById('producto-row-' + productoId);
    const detalle = document.getElementById('producto-detail-' + productoId);
    const abrir = !detalle.classList.contains('visible');
    document.querySelectorAll('.producto-detail-row.visible').forEach(item => item.classList.remove('visible'));
    document.querySelectorAll('.producto-main-row.abierta').forEach(item => item.classList.remove('abierta'));
    if (abrir) {
        detalle.classList.add('visible');
        fila.classList.add('abierta');
    }
}
</script>

<?php
$bitacora_modulo_nombre = 'Inventario de Productos y Ensayos';
include dirname(__DIR__, 3) . '/Views/parciales/bitacora_modulo.php';
include __DIR__ . '/modal_carga_masiva.php';
?>
