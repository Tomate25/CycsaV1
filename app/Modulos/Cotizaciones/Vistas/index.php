<?php
$filtros = $filtros ?? [];
$parametrosPersistentes = [
    'q' => $busqueda ?? '',
    'fecha_desde' => $filtros['fecha_desde'] ?? '',
    'fecha_hasta' => $filtros['fecha_hasta'] ?? '',
    'id_cliente' => (int)($filtros['id_cliente'] ?? 0),
    'estado' => $filtros['estado'] ?? '',
];
$urlListado = static function (array $cambios = []) use ($parametrosPersistentes): string {
    $parametros = array_merge($parametrosPersistentes, $cambios);
    $parametros = array_filter($parametros, static fn($valor) => $valor !== '' && $valor !== 0 && $valor !== null);
    $query = http_build_query($parametros);
    return '/Cycsa/publico/cotizaciones' . ($query !== '' ? '?' . $query : '');
};
$hayFiltrosAvanzados = !empty($filtros['fecha_desde'])
    || !empty($filtros['fecha_hasta'])
    || !empty($filtros['id_cliente'])
    || !empty($filtros['estado']);
?>
<style>
    .tabla-cycsa { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
    .tabla-cycsa th { background-color: #f8f9fa; color: #6c757d; padding: 12px 15px; text-align: left; font-weight: 600; border-bottom: 2px solid #dee2e6; text-transform: uppercase; font-size: 12px; }
    .tabla-cycsa td { padding: 15px; border-bottom: 1px solid #e9ecef; vertical-align: middle; color: #333; }
    .tabla-cycsa tbody tr:hover { background-color: #f8f9fa; }
    
    /* Colores dinámicos para la máquina de estados */
    .badge { padding: 5px 10px; border-radius: 20px; font-size: 12px; font-weight: 600; display: inline-block; text-align: center;}
    .bg-borrador { background-color: #e2e8f0; color: #475569; }
    .bg-revision { background-color: #fef3c7; color: #b45309; }
    .bg-observada { background-color: #fee2e2; color: #b91c1c; }
    .bg-aprobada-int { background-color: #dbeafe; color: #1d4ed8; }
    .bg-enviada { background-color: #f3e8ff; color: #7e22ce; }
    .bg-aprobada-cli { background-color: #dcfce7; color: #15803d; }
    .bg-rechazada-cli { background-color: #fca5a5; color: #7f1d1d; }
    
    .btn-accion { border: none; background: none; cursor: pointer; padding: 5px 10px; font-size: 16px; transition: color 0.2s; text-decoration: none; display: inline-block;}
    .btn-ver { color: #103487; }
    .btn-ver:hover { color: #0a225c; }
    
    /* Navigation Tabs */
    .tabs-container { display: flex; border-bottom: 1px solid #dee2e6; margin-bottom: 20px; gap: 5px; }
    .tab-link { padding: 10px 15px; text-decoration: none; color: #64748b; font-weight: 500; border-bottom: 2px solid transparent; transition: all 0.2s; font-size: 14px; display: flex; align-items: center; gap: 8px; }
    .tab-link:hover { color: var(--cycsa-azul); }
    .tab-link.active { color: var(--cycsa-azul); border-bottom-color: var(--cycsa-azul); font-weight: 600; }
    .filtros-avanzados { display: grid; grid-template-columns: repeat(4, minmax(150px, 1fr)) auto; gap: 12px; align-items: end; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 16px; margin: 18px 0; }
    .filtro-campo { display: flex; flex-direction: column; gap: 5px; }
    .filtro-campo label { color: #475569; font-size: 12px; font-weight: 700; }
    .filtro-control { min-height: 38px; border: 1px solid #cbd5e1; border-radius: 6px; padding: 8px 10px; background: white; color: #1e293b; font-family: 'Inter', sans-serif; font-size: 13px; }
    .filtro-control:focus { outline: none; border-color: var(--cycsa-azul); box-shadow: 0 0 0 2px rgba(16, 52, 135, 0.1); }
    @media (max-width: 1050px) { .filtros-avanzados { grid-template-columns: repeat(2, minmax(180px, 1fr)); } }
    @media (max-width: 620px) { .filtros-avanzados { grid-template-columns: 1fr; } }
</style>

<div style="background: white; padding: 25px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.03);">
    
    <div class="header-flex">
        <div>
            <h2 style="margin: 0; color: #333; font-size: 20px;">Gestión de Cotizaciones</h2>
            <p style="color: #6c757d; margin-top: 5px; font-size: 14px;">Administra el ciclo de vida de las propuestas económicas.</p>
        </div>
        
        <div class="actions-flex">
            <form method="GET" action="/Cycsa/publico/cotizaciones" style="display: flex;">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tabActual ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="fecha_desde" value="<?= htmlspecialchars($filtros['fecha_desde'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="fecha_hasta" value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id_cliente" value="<?= (int)($filtros['id_cliente'] ?? 0) ?>">
                <input type="hidden" name="estado" value="<?= htmlspecialchars($filtros['estado'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="text" name="q" placeholder="Buscar por código, cliente..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" style="padding: 8px 15px; border: 1px solid #ced4da; border-radius: 4px 0 0 4px; font-family: 'Inter', sans-serif; width: 260px; outline: none;">
                <button type="submit" style="background: #e9ecef; border: 1px solid #ced4da; border-left: none; padding: 8px 15px; border-radius: 0 4px 4px 0; cursor: pointer; color: #495057;"><i class="fa-solid fa-magnifying-glass"></i></button>
                <?php if(!empty($busqueda)): ?>
                    <a href="<?= htmlspecialchars($urlListado(['q' => '', 'tab' => $tabActual ?? 'todas']), ENT_QUOTES, 'UTF-8') ?>" style="margin-left: 10px; color: #e31837; text-decoration: none; padding-top: 8px;" title="Limpiar búsqueda"><i class="fa-solid fa-xmark"></i></a>
                <?php endif; ?>
            </form>

            <?php if (tienePermiso('cotizaciones', 'crear_editar')): ?>
            <a href="/Cycsa/publico/cotizaciones/crear" style="background: var(--cycsa-azul); color: white; border: none; padding: 10px 20px; border-radius: 6px; cursor: pointer; font-weight: 500; font-family: 'Inter', sans-serif; text-decoration: none; display: inline-block; transition: background 0.3s; margin-left: 10px;">
                <i class="fa-solid fa-plus"></i> Nueva Cotización
            </a>
            <?php endif; ?>
        </div>
    </div>

    <form method="GET" action="/Cycsa/publico/cotizaciones" class="filtros-avanzados">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tabActual ?? 'todas', ENT_QUOTES, 'UTF-8') ?>">
        <input type="hidden" name="q" value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>">
        <div class="filtro-campo">
            <label for="fecha_desde">Fecha desde</label>
            <input class="filtro-control" type="date" id="fecha_desde" name="fecha_desde" value="<?= htmlspecialchars($filtros['fecha_desde'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="filtro-campo">
            <label for="fecha_hasta">Fecha hasta</label>
            <input class="filtro-control" type="date" id="fecha_hasta" name="fecha_hasta" value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
        </div>
        <div class="filtro-campo">
            <label for="id_cliente">Cliente</label>
            <select class="filtro-control" id="id_cliente" name="id_cliente">
                <option value="0">Todos los clientes</option>
                <?php foreach (($clientes ?? []) as $cliente): ?>
                    <option value="<?= (int)$cliente['id'] ?>" <?= (int)($filtros['id_cliente'] ?? 0) === (int)$cliente['id'] ? 'selected' : '' ?>>
                        <?= htmlspecialchars($cliente['nombre_razon_social'], ENT_QUOTES, 'UTF-8') ?>
                    </option>
                <?php endforeach; ?>
            </select>
        </div>
        <div class="filtro-campo">
            <label for="estado">Estado</label>
            <select class="filtro-control" id="estado" name="estado">
                <option value="">Todos los estados</option>
                <option value="revision" <?= ($filtros['estado'] ?? '') === 'revision' ? 'selected' : '' ?>>En revisión</option>
                <option value="aprobadas" <?= ($filtros['estado'] ?? '') === 'aprobadas' ? 'selected' : '' ?>>Aprobadas / enviadas</option>
                <option value="rechazadas" <?= ($filtros['estado'] ?? '') === 'rechazadas' ? 'selected' : '' ?>>Rechazadas</option>
                <option value="observadas" <?= ($filtros['estado'] ?? '') === 'observadas' ? 'selected' : '' ?>>Observadas</option>
                <option value="borradores" <?= ($filtros['estado'] ?? '') === 'borradores' ? 'selected' : '' ?>>Borradores</option>
            </select>
        </div>
        <div style="display: flex; gap: 8px; align-items: center;">
            <button type="submit" style="min-height: 38px; background: var(--cycsa-azul); color: white; border: none; border-radius: 6px; padding: 8px 16px; cursor: pointer; font-weight: 600; white-space: nowrap;">
                <i class="fa-solid fa-filter"></i> Aplicar
            </button>
            <?php if ($hayFiltrosAvanzados): ?>
                <a href="<?= htmlspecialchars($urlListado(['fecha_desde' => '', 'fecha_hasta' => '', 'id_cliente' => 0, 'estado' => '', 'tab' => $tabActual ?? 'todas']), ENT_QUOTES, 'UTF-8') ?>" style="color: #b91c1c; text-decoration: none; font-size: 13px; font-weight: 600; white-space: nowrap;" title="Limpiar filtros avanzados">
                    <i class="fa-solid fa-xmark"></i> Limpiar
                </a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Pestañas Filtros del Ciclo de Vida de Cotizaciones -->
    <div class="tabs-container">
        <a href="<?= htmlspecialchars($urlListado(['tab' => 'todas']), ENT_QUOTES, 'UTF-8') ?>" class="tab-link <?= $tabActual === 'todas' ? 'active' : '' ?>">
            <i class="fa-solid fa-list"></i> Todas
        </a>
        <a href="<?= htmlspecialchars($urlListado(['tab' => 'borradores']), ENT_QUOTES, 'UTF-8') ?>" class="tab-link <?= $tabActual === 'borradores' ? 'active' : '' ?>">
            <i class="fa-solid fa-file-signature"></i> Borradores
        </a>
        <a href="<?= htmlspecialchars($urlListado(['tab' => 'revision']), ENT_QUOTES, 'UTF-8') ?>" class="tab-link <?= $tabActual === 'revision' ? 'active' : '' ?>">
            <i class="fa-solid fa-clock-rotate-left"></i> En Revisión
        </a>
        <a href="<?= htmlspecialchars($urlListado(['tab' => 'observadas']), ENT_QUOTES, 'UTF-8') ?>" class="tab-link <?= $tabActual === 'observadas' ? 'active' : '' ?>">
            <i class="fa-solid fa-triangle-exclamation"></i> Observadas
        </a>
        <a href="<?= htmlspecialchars($urlListado(['tab' => 'aprobadas']), ENT_QUOTES, 'UTF-8') ?>" class="tab-link <?= $tabActual === 'aprobadas' ? 'active' : '' ?>">
            <i class="fa-solid fa-circle-check"></i> Aprobadas / Enviadas
        </a>
    </div>

    <div style="overflow-x: auto;">
        <table class="tabla-cycsa">
            <thead>
                <tr>
                    <th>Código</th>
                    <th>Cliente</th>
                    <th>Total</th>
                    <th>Estado</th>
                    <th>Fecha</th>
                    <th style="text-align: right;">Acciones</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($cotizaciones as $cot): ?>
                <tr>
                    <td style="font-weight: 700; color: #103487;"><?= htmlspecialchars($cot['codigo'], ENT_QUOTES, 'UTF-8') ?> <span style="font-size: 11px; color: #888;">(v<?= $cot['version'] ?>)</span></td>
                    <td style="font-weight: 500;"><?= htmlspecialchars($cot['cliente'], ENT_QUOTES, 'UTF-8') ?></td>
                    <td style="font-weight: 600;">C$ <?= number_format($cot['total'], 2, '.', ',') ?></td>
                    <td>
                        <?php 
                            // Asignar color según la máquina de estados
                            $claseBadge = 'bg-borrador';
                            if($cot['estado'] == 'En Revision') $claseBadge = 'bg-revision';
                            if($cot['estado'] == 'Observada') $claseBadge = 'bg-observada';
                            if($cot['estado'] == 'Aprobada Internamente') $claseBadge = 'bg-aprobada-int';
                            if($cot['estado'] == 'Enviada al Cliente') $claseBadge = 'bg-enviada';
                            if($cot['estado'] == 'Aprobada por Cliente') $claseBadge = 'bg-aprobada-cli';
                            if($cot['estado'] == 'Rechazada por Cliente') $claseBadge = 'bg-rechazada-cli';
                        ?>
                        <span class="badge <?= $claseBadge ?>"><?= htmlspecialchars($cot['estado'], ENT_QUOTES, 'UTF-8') ?></span>
                    </td>
                    <td style="color: #6c757d; font-size: 13px;"><?= date('d/m/Y', strtotime($cot['fecha_creacion'])) ?></td>
                    <td style="text-align: right;">
                        <a href="/Cycsa/publico/cotizaciones/detalle?id=<?= codificarId($cot['id']) ?>" class="btn-accion btn-ver" title="Ver Detalle"><i class="fa-solid fa-eye"></i></a>
                        <a href="/Cycsa/publico/cotizaciones/imprimir?id=<?= codificarId($cot['id']) ?>&t=<?= time() ?>" target="_blank" class="btn-accion btn-ver" style="color: #e31837;" title="Imprimir PDF"><i class="fa-solid fa-file-pdf"></i></a>
                    </td>
                </tr>
                <?php endforeach; ?>
                
                <?php if(empty($cotizaciones)): ?>
                <tr>
                    <td colspan="6" style="text-align: center; padding: 30px; color: #6c757d;">No hay cotizaciones generadas.</td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
$bitacora_modulo_nombre = 'Cotizaciones';
include dirname(__DIR__, 3) . '/Views/parciales/bitacora_modulo.php';
?>
