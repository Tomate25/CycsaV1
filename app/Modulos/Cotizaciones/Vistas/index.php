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

$resolverPillCotizacion = static function (string $estado): array {
    return match ($estado) {
        'Aprobada por Cliente', 'Aprobada Internamente' => ['clase' => 'cy-pill-ok', 'texto' => $estado],
        'En Revision', 'Enviada al Cliente' => ['clase' => 'cy-pill-warn', 'texto' => $estado],
        'Observada', 'Rechazada por Cliente' => ['clase' => 'cy-pill-danger', 'texto' => $estado],
        default => ['clase' => 'cy-pill-pend', 'texto' => $estado !== '' ? $estado : 'Borrador'],
    };
};
?>
<link rel="stylesheet" href="/Cycsa/publico/css/cycsa-sobrio.css">
<link rel="stylesheet" href="/Cycsa/publico/css/cotizaciones-sobrio.css">

<div class="container-fluid px-0 py-0 cy-cot-wrap">
    <?php if (isset($_SESSION['exito'])): ?>
        <div class="cy-note mb-3 d-flex align-items-center gap-2" role="status">
            <span class="cy-pill cy-pill-ok">Listo</span>
            <span style="color: var(--cy-text);"><?= htmlspecialchars($_SESSION['exito'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php unset($_SESSION['exito']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="cy-note mb-3 d-flex align-items-center gap-2" role="alert">
            <span class="cy-pill cy-pill-danger">Aviso</span>
            <span style="color: var(--cy-text);"><?= htmlspecialchars($_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Encabezado sobrio + filtro segmentado (.cy-seg) idéntico a Captura Técnica -->
    <div class="cy-cot-header">
        <div class="cy-cot-header-left">
            <h1 class="cy-page-title">Cotizaciones</h1>
        </div>

        <div class="btn-group cy-seg" role="group" aria-label="Filtro de cotizaciones">
            <a href="<?= htmlspecialchars($urlListado(['tab' => 'todas']), ENT_QUOTES, 'UTF-8') ?>" class="btn <?= ($tabActual ?? 'todas') === 'todas' ? 'is-active' : '' ?>">
                Todas
            </a>
            <a href="<?= htmlspecialchars($urlListado(['tab' => 'borradores']), ENT_QUOTES, 'UTF-8') ?>" class="btn <?= ($tabActual ?? '') === 'borradores' ? 'is-active' : '' ?>">
                Borradores
            </a>
            <a href="<?= htmlspecialchars($urlListado(['tab' => 'revision']), ENT_QUOTES, 'UTF-8') ?>" class="btn <?= ($tabActual ?? '') === 'revision' ? 'is-active' : '' ?>">
                En Revisión
            </a>
            <a href="<?= htmlspecialchars($urlListado(['tab' => 'observadas']), ENT_QUOTES, 'UTF-8') ?>" class="btn <?= ($tabActual ?? '') === 'observadas' ? 'is-active' : '' ?>">
                Observadas
            </a>
            <a href="<?= htmlspecialchars($urlListado(['tab' => 'aprobadas']), ENT_QUOTES, 'UTF-8') ?>" class="btn <?= ($tabActual ?? '') === 'aprobadas' ? 'is-active' : '' ?>">
                Aprobadas / Enviadas
            </a>
        </div>
    </div>

    <!-- Subbarra: descripción, búsqueda rápida y acción primaria -->
    <div class="cy-cot-subbar">
        <div class="cy-meta">Administra el ciclo de vida de las propuestas económicas y su envío al cliente.</div>
        <div class="cy-cot-header-actions">
            <form method="GET" action="/Cycsa/publico/cotizaciones" class="cy-cot-search-form">
                <input type="hidden" name="tab" value="<?= htmlspecialchars($tabActual ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="fecha_desde" value="<?= htmlspecialchars($filtros['fecha_desde'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="fecha_hasta" value="<?= htmlspecialchars($filtros['fecha_hasta'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id_cliente" value="<?= (int)($filtros['id_cliente'] ?? 0) ?>">
                <input type="hidden" name="estado" value="<?= htmlspecialchars($filtros['estado'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                <input type="text" name="q" placeholder="Buscar por código o cliente..." value="<?= htmlspecialchars($busqueda ?? '', ENT_QUOTES, 'UTF-8') ?>" class="cy-cot-search-input" aria-label="Buscar cotizaciones">
                <button type="submit" class="btn btn-outline-secondary btn-sm">Buscar</button>
                <?php if (!empty($busqueda)): ?>
                    <a href="<?= htmlspecialchars($urlListado(['q' => '', 'tab' => $tabActual ?? 'todas']), ENT_QUOTES, 'UTF-8') ?>" class="cy-action-link" title="Limpiar búsqueda">Limpiar</a>
                <?php endif; ?>
            </form>

            <?php if (tienePermiso('cotizaciones', 'crear_editar')): ?>
                <a href="/Cycsa/publico/cotizaciones/crear" class="btn btn-primary btn-sm">
                    Nueva Cotización
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Filtros avanzados en tarjeta plana -->
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
        <div class="d-flex align-items-center gap-2">
            <button type="submit" class="btn btn-outline-secondary btn-sm" style="min-height: 38px;">Aplicar</button>
            <?php if ($hayFiltrosAvanzados): ?>
                <a href="<?= htmlspecialchars($urlListado(['fecha_desde' => '', 'fecha_hasta' => '', 'id_cliente' => 0, 'estado' => '', 'tab' => $tabActual ?? 'todas']), ENT_QUOTES, 'UTF-8') ?>" class="cy-action-link" title="Limpiar filtros avanzados">Limpiar</a>
            <?php endif; ?>
        </div>
    </form>

    <!-- Tabla plana de cotizaciones -->
    <div class="card">
        <div class="table-responsive">
            <table class="table" id="tabla-cotizaciones">
                <thead>
                    <tr>
                        <th>Código</th>
                        <th>Cliente</th>
                        <th>Total</th>
                        <th>Estado</th>
                        <th>Fecha</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($cotizaciones as $cot):
                        $pillCot = $resolverPillCotizacion((string)($cot['estado'] ?? 'Borrador'));
                        $simbMon = ((int)($cot['tipo_moneda'] ?? 1) === 2) ? '$' : 'C$';
                    ?>
                    <tr>
                        <td>
                            <a href="/Cycsa/publico/cotizaciones/detalle?id=<?= codificarId($cot['id']) ?>" class="fw-semibold" style="color: var(--cy-brand); text-decoration: none;">
                                <?= htmlspecialchars($cot['codigo'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                            <span class="cy-meta ms-1">v<?= max(1, (int)($cot['version'] ?? 1)) ?></span>
                        </td>
                        <td>
                            <div class="fw-medium"><?= htmlspecialchars($cot['cliente'], ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td class="fw-semibold">
                            <?= $simbMon ?> <?= number_format((float)$cot['total'], 2, '.', ',') ?>
                        </td>
                        <td>
                            <span class="cy-pill <?= htmlspecialchars($pillCot['clase'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($pillCot['texto'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <span class="cy-meta"><?= date('d/m/Y', strtotime($cot['fecha_creacion'])) ?></span>
                        </td>
                        <td class="text-end" style="white-space: nowrap;">
                            <div class="cy-cot-row-actions">
                                <a href="/Cycsa/publico/cotizaciones/detalle?id=<?= codificarId($cot['id']) ?>" class="btn btn-outline-secondary btn-sm">Ver detalle</a>
                                <?php if (tienePermiso('cotizaciones', 'crear_editar')): ?>
                                    <a href="/Cycsa/publico/cotizaciones/duplicar?id=<?= codificarId($cot['id']) ?>"
                                       class="btn btn-outline-secondary btn-sm"
                                       title="Duplicar Cotización (Genera nueva con número correlativo copiando todos los datos)"
                                       onclick="return confirm('¿Desea duplicar la cotización <?= htmlspecialchars($cot['codigo'], ENT_QUOTES, 'UTF-8') ?>?\n\nSe creará una nueva cotización con el siguiente número consecutivo conservando cliente, proyecto, notas, opciones e ítems cotizados.');">
                                        Duplicar
                                    </a>
                                <?php endif; ?>
                                <a href="/Cycsa/publico/cotizaciones/imprimir?id=<?= codificarId($cot['id']) ?>&t=<?= time() ?>" target="_blank" class="btn btn-outline-secondary btn-sm" title="Imprimir PDF">PDF</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($cotizaciones)): ?>
                    <tr>
                        <td colspan="6" class="text-center py-5">
                            <span class="cy-meta">No hay cotizaciones registradas con los filtros actuales.</span>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<?php
$bitacora_modulo_nombre = 'Cotizaciones';
include dirname(__DIR__, 3) . '/Views/parciales/bitacora_modulo.php';
?>
