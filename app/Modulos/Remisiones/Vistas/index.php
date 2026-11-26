<?php
$tabActual = $tabActual ?? 'todas';
$busqueda = $busqueda ?? '';
$modalidadFiltro = $modalidadFiltro ?? '';
$kpis = $kpis ?? [
    'total' => 0,
    'emitidas' => 0,
    'entregadas' => 0,
    'anuladas' => 0,
    'revisiones_pt13' => 0,
    'informes_anulados' => 0,
];
$remisiones = $remisiones ?? [];

$urlListado = static function (array $cambios = []) use ($tabActual, $busqueda, $modalidadFiltro): string {
    $params = array_merge([
        'tab' => $tabActual,
        'q' => $busqueda,
        'modalidad' => $modalidadFiltro,
    ], $cambios);
    $params = array_filter($params, static fn($v) => $v !== '' && $v !== null);
    $query = http_build_query($params);
    return '/Cycsa/publico/remisiones' . ($query !== '' ? '?' . $query : '');
};

$resolverPillRemision = static function (string $estado): array {
    return match ($estado) {
        'Entregada' => ['clase' => 'cy-pill-ok', 'texto' => 'Entregada'],
        'Emitida' => ['clase' => 'cy-pill-warn', 'texto' => 'Emitida'],
        'Anulada' => ['clase' => 'cy-pill-danger', 'texto' => 'Anulada'],
        default => ['clase' => 'cy-pill-pend', 'texto' => $estado !== '' ? $estado : 'Emitida'],
    };
};
?>
<link rel="stylesheet" href="/Cycsa/publico/css/cycsa-sobrio.css">
<link rel="stylesheet" href="/Cycsa/publico/css/remisiones-sobrio.css">

<div class="container-fluid px-0 py-0 cy-rem-wrap">
    <?php if (isset($_SESSION['exito'])): ?>
        <div class="cy-note mb-3 d-flex align-items-center gap-2" role="status">
            <span class="cy-pill cy-pill-ok">Listo</span>
            <span style="color: var(--cy-text);"><?= htmlspecialchars((string)$_SESSION['exito'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php unset($_SESSION['exito']); ?>
    <?php endif; ?>

    <?php if (isset($_SESSION['error'])): ?>
        <div class="cy-note mb-3 d-flex align-items-center gap-2" role="alert">
            <span class="cy-pill cy-pill-danger">Aviso</span>
            <span style="color: var(--cy-text);"><?= htmlspecialchars((string)$_SESSION['error'], ENT_QUOTES, 'UTF-8') ?></span>
        </div>
        <?php unset($_SESSION['error']); ?>
    <?php endif; ?>

    <!-- Encabezado sobrio + pestañas segmentadas .cy-seg -->
    <div class="cy-rem-header">
        <div class="cy-rem-header-left">
            <h1 class="cy-page-title">Remisiones y Control PT-13</h1>
        </div>

        <div class="btn-group cy-seg" role="group" aria-label="Filtro de estado de remisiones">
            <a href="<?= htmlspecialchars($urlListado(['tab' => 'todas']), ENT_QUOTES, 'UTF-8') ?>" class="btn <?= $tabActual === 'todas' ? 'is-active' : '' ?>">
                Todas (<?= (int)$kpis['total'] ?>)
            </a>
            <a href="<?= htmlspecialchars($urlListado(['tab' => 'emitidas']), ENT_QUOTES, 'UTF-8') ?>" class="btn <?= $tabActual === 'emitidas' ? 'is-active' : '' ?>">
                Emitidas (<?= (int)$kpis['emitidas'] ?>)
            </a>
            <a href="<?= htmlspecialchars($urlListado(['tab' => 'entregadas']), ENT_QUOTES, 'UTF-8') ?>" class="btn <?= $tabActual === 'entregadas' ? 'is-active' : '' ?>">
                Entregadas (<?= (int)$kpis['entregadas'] ?>)
            </a>
            <a href="<?= htmlspecialchars($urlListado(['tab' => 'anuladas']), ENT_QUOTES, 'UTF-8') ?>" class="btn <?= $tabActual === 'anuladas' ? 'is-active' : '' ?>">
                Anuladas (<?= (int)$kpis['anuladas'] ?>)
            </a>
        </div>
    </div>

    <!-- Subbarra con accesos directos a Control PT-13 y Nueva Remisión -->
    <div class="cy-rem-subbar">
        <div class="cy-meta">
            Entrega trazable de informes de ensayo (CYCSA-RG-FM-41 / RLAB) y control de liberación ISO/IEC 17025:2017 (PT-13 Ver. 5 Rev. 2).
        </div>
        <div class="cy-rem-header-actions">
            <a href="/Cycsa/publico/remisiones/revision-pt13" class="btn btn-outline-secondary btn-sm">
                Control PT-13 / RT-FM-65 (<?= (int)$kpis['revisiones_pt13'] ?>)
            </a>
            <a href="/Cycsa/publico/remisiones/crear" class="btn btn-primary btn-sm">
                Nueva Remisión (RG-FM-41)
            </a>
        </div>
    </div>

    <!-- Barra plana de filtros -->
    <form method="GET" action="/Cycsa/publico/remisiones" class="cy-rem-filter-bar">
        <input type="hidden" name="tab" value="<?= htmlspecialchars($tabActual, ENT_QUOTES, 'UTF-8') ?>">
        <input
            type="text"
            name="q"
            class="cy-rem-search-input"
            placeholder="Buscar por RLAB, O/S, cliente, informe o receptor..."
            value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>"
            aria-label="Buscar remisiones"
        >
        <select name="modalidad" class="cy-rem-select" aria-label="Modalidad de entrega">
            <option value="">Todas las modalidades</option>
            <option value="laboratorio" <?= $modalidadFiltro === 'laboratorio' ? 'selected' : '' ?>>Entrega en Laboratorio</option>
            <option value="domicilio" <?= $modalidadFiltro === 'domicilio' ? 'selected' : '' ?>>Entrega a Domicilio</option>
        </select>
        <button type="submit" class="btn btn-outline-secondary btn-sm">Filtrar</button>
        <?php if ($busqueda !== '' || $modalidadFiltro !== ''): ?>
            <a href="<?= htmlspecialchars($urlListado(['q' => '', 'modalidad' => '']), ENT_QUOTES, 'UTF-8') ?>" class="cy-action-link">Limpiar</a>
        <?php endif; ?>
        <div class="ms-auto cy-meta">
            Informes anulados bloqueados (Sec. 5.6 b): <strong><?= (int)$kpis['informes_anulados'] ?></strong>
        </div>
    </form>

    <!-- Tabla plana de remisiones (crece fila por fila sin caja min-height) -->
    <div class="card">
        <div class="table-responsive">
            <table class="table" id="tabla-remisiones">
                <thead>
                    <tr>
                        <th>Código RLAB</th>
                        <th>Orden de Servicio</th>
                        <th>Cliente / Empresa</th>
                        <th>Modalidad</th>
                        <th>Informes</th>
                        <th>Receptor</th>
                        <th>Fecha Entrega</th>
                        <th>Estado</th>
                        <th class="text-end">Acciones</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($remisiones as $rem):
                        $pill = $resolverPillRemision((string)($rem['estado'] ?? 'Emitida'));
                        $modalidadTxt = (($rem['modalidad_entrega'] ?? 'laboratorio') === 'domicilio') ? 'Domicilio' : 'Laboratorio';
                    ?>
                    <tr>
                        <td>
                            <a href="/Cycsa/publico/remisiones/detalle?id=<?= (int)$rem['id'] ?>" class="fw-semibold" style="color: var(--cy-brand); text-decoration: none;">
                                <?= htmlspecialchars((string)$rem['codigo_remision'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                        </td>
                        <td>
                            <?php if (!empty($rem['codigo_os'])): ?>
                                <div class="fw-medium"><?= htmlspecialchars((string)$rem['codigo_os'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if (!empty($rem['nombre_proyecto'])): ?>
                                    <div class="cy-meta"><?= htmlspecialchars((string)$rem['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="cy-meta">Sin O/S vinculada</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <div class="fw-medium"><?= htmlspecialchars((string)($rem['empresa_nombre'] ?: $rem['cliente_nombre']), ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td><?= htmlspecialchars($modalidadTxt, ENT_QUOTES, 'UTF-8') ?></td>
                        <td>
                            <span class="fw-semibold"><?= (int)($rem['total_informes'] ?? 0) ?></span>
                            <span class="cy-meta">informe(s)</span>
                        </td>
                        <td>
                            <?php if (!empty($rem['persona_recibe'])): ?>
                                <div><?= htmlspecialchars((string)$rem['persona_recibe'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php if (!empty($rem['cedula_recibe'])): ?>
                                    <div class="cy-meta">Céd.: <?= htmlspecialchars((string)$rem['cedula_recibe'], ENT_QUOTES, 'UTF-8') ?></div>
                                <?php endif; ?>
                            <?php else: ?>
                                <span class="cy-meta">Pendiente de recepción</span>
                            <?php endif; ?>
                        </td>
                        <td>
                            <span class="cy-meta">
                                <?= !empty($rem['fecha_entrega']) ? date('d/m/Y H:i', strtotime((string)$rem['fecha_entrega'])) : '—' ?>
                            </span>
                        </td>
                        <td>
                            <span class="cy-pill <?= htmlspecialchars($pill['clase'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($pill['texto'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td class="text-end">
                            <div class="cy-rem-row-actions">
                                <a href="/Cycsa/publico/remisiones/detalle?id=<?= (int)$rem['id'] ?>" class="btn btn-outline-secondary btn-sm">Ver RG-FM-41</a>
                                <a href="/Cycsa/publico/remisiones/pdf?id=<?= (int)$rem['id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm">PDF</a>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($remisiones)): ?>
                    <tr>
                        <td colspan="9" class="text-center py-4">
                            <span class="cy-meta">No hay remisiones registradas con los criterios seleccionados.</span>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
