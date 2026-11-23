<?php
// Vista principal de Operaciones LIMS — Guía de Diseño Sobrio CYCSA
$rolSesion = (int)($_SESSION['usuario_rol'] ?? 0);
$esSupervisor = in_array($rolSesion, [1, 2, 3], true)
    || (function_exists('tienePermiso') && (tienePermiso('operaciones', 'aprobar') || tienePermiso('control_calidad', 'aprobar')));

$ordenes = $ordenes ?? [];
$tabActiva = $tabActiva ?? ($_GET['tab'] ?? 'activas');
$bandejaActiva = $bandejaActiva ?? ($_GET['bandeja'] ?? 'todas');
$conteoBandejas = $conteoBandejas ?? [
    'todas' => count($ordenes),
    'recepcion' => 0,
    'tecnico' => 0,
    'supervisor' => 0,
    'informes' => 0,
];
$conteosTabs = $conteosTabs ?? [
    'activas' => count($ordenes),
    'muestreo' => 0,
    'ensayos' => 0,
    'historico' => 0,
];
$tecnicos = $tecnicos ?? [];
$vehiculos = $vehiculos ?? [];
$csrfToken = (string)($_SESSION['csrf_token'] ?? '');

if (!function_exists('cycsaCalcularStepperOperacion')) {
    /**
     * Calcula de forma determinística el stepper horizontal de 5 pasos de una O/S.
     * Regla estricta: el paso 5 (Informe) jamás recibe `is-done` mientras existan matrices sin aprobar.
     */
    function cycsaCalcularStepperOperacion(array $o): array {
        $items = array_values(array_filter($o['items'] ?? [], static function ($it): bool {
            return !is_array($it) || !array_key_exists('permitido', $it) || (int)$it['permitido'] === 1;
        }));

        $totalEnsayos = isset($o['total_ensayos']) ? (int)$o['total_ensayos'] : count($items);
        if ($totalEnsayos === 0 && !empty($items)) {
            $totalEnsayos = count($items);
        }

        $aprobadasCalc = 0;
        $enRevisionCalc = 0;
        $devueltasCalc = 0;
        $conResCalc = 0;
        $enEjecucionCalc = 0;

        foreach ($items as $it) {
            $rev = (isset($it['revision_info']) && is_array($it['revision_info']))
                ? $it['revision_info']
                : (function_exists('obtenerEstadoRevisionMatriz')
                    ? obtenerEstadoRevisionMatriz($it['resultados_json'] ?? null)
                    : ['estado' => 'pendiente', 'tiene_resultados' => false]);
            $estRev = (string)($rev['estado'] ?? 'pendiente');
            $tieneResIt = !empty($rev['tiene_resultados']) || (!empty($it['resultados_json']) && $it['resultados_json'] !== '[]');
            if ($tieneResIt) {
                $conResCalc++;
            }
            if ($estRev === 'aprobada') {
                $aprobadasCalc++;
            } elseif ($estRev === 'en_revision') {
                $enRevisionCalc++;
            } elseif ($estRev === 'devuelta') {
                $devueltasCalc++;
            } elseif ($estRev === 'borrador') {
                $enEjecucionCalc++;
            }
        }

        $ensayosAprobados = isset($o['ensayos_aprobados']) ? (int)$o['ensayos_aprobados'] : $aprobadasCalc;
        $ensayosEnRevision = isset($o['ensayos_en_revision']) ? (int)$o['ensayos_en_revision'] : $enRevisionCalc;
        $ensayosDevueltos = isset($o['ensayos_devueltos']) ? (int)$o['ensayos_devueltos'] : $devueltasCalc;
        $ensayosConRes = isset($o['ensayos_con_resultados']) ? (int)$o['ensayos_con_resultados'] : $conResCalc;

        $esSoloComp = function_exists('esOrdenSoloCompactacion') ? esOrdenSoloCompactacion($items) : false;
        $tieneHoja = !empty($o['hoja_solicitud']) || !empty($o['hojas_solicitud']) || !empty($o['tiene_hoja']);
        $etapaFlujo = trim((string)($o['etapa_flujo_os'] ?? ($o['etapa_flujo'] ?? ($o['hoja_solicitud']['etapa_flujo'] ?? ''))));

        $done1 = $tieneHoja
            || $esSoloComp
            || in_array($etapaFlujo, ['recepcion_borrador', 'recepcion_completa', 'en_ejecucion', 'tecnico_completo', 'matriz_en_revision', 'informe'], true);

        $recCompletaFlag = !empty($o['recepcion_completa_os'])
            || !empty($o['recepcion_completa'])
            || !empty($o['hoja_solicitud']['recepcion_completa'])
            || in_array($etapaFlujo, ['recepcion_completa', 'en_ejecucion', 'tecnico_completo', 'matriz_en_revision', 'informe'], true)
            || $esSoloComp
            || $ensayosConRes > 0
            || !empty($o['muestras_aceptadas_lab']);
        $done2 = $done1 && $recCompletaFlag;

        $matricesEjecutadas = $ensayosAprobados + $ensayosEnRevision;
        if ($totalEnsayos > 0 && !empty($items)) {
            $ejecucionTerminada = ($ensayosDevueltos === 0 && $enEjecucionCalc === 0 && $matricesEjecutadas >= $totalEnsayos);
        } else {
            $ejecucionTerminada = ($totalEnsayos > 0) && (
                ($ensayosDevueltos === 0 && $matricesEjecutadas >= $totalEnsayos)
                || ($ensayosDevueltos === 0 && $ensayosConRes >= $totalEnsayos && in_array($etapaFlujo, ['tecnico_completo', 'matriz_en_revision', 'informe'], true))
                || ($ensayosAprobados >= $totalEnsayos)
            );
        }
        $done3 = $done2 && $ejecucionTerminada;

        $todasAprobadas = ($totalEnsayos > 0 && $ensayosAprobados >= $totalEnsayos);
        $done4 = $done3 && $todasAprobadas;

        $informeEmitidoFlag = array_key_exists('informe_emitido', $o)
            ? !empty($o['informe_emitido'])
            : ($etapaFlujo === 'informe' || !empty($o['informes_emitidos']) || $todasAprobadas);
        $done5 = $done4 && $todasAprobadas && $informeEmitidoFlag;

        $pasos = [
            [
                'numero' => 1,
                'label' => '1. RT-FM-13',
                'compat_title' => '1. RT-FM-13',
                'estado' => $done1 ? ($esSoloComp && !$tieneHoja ? 'In Situ' : 'Emitida') : 'Pendiente',
                'done' => $done1,
            ],
            [
                'numero' => 2,
                'label' => '2. Recepción',
                'compat_title' => '2. Recepción Completa',
                'estado' => $done2 ? 'Completa' : ($tieneHoja ? 'En borrador' : 'Pendiente'),
                'done' => $done2,
            ],
            [
                'numero' => 3,
                'label' => '3. Ejecución',
                'compat_title' => '3. Ejecución Lab',
                'estado' => $totalEnsayos > 0
                    ? ($done3 ? ($totalEnsayos . ' de ' . $totalEnsayos . ' matrices') : ($matricesEjecutadas . ' de ' . $totalEnsayos . ' matrices'))
                    : 'Pendiente',
                'done' => $done3,
            ],
            [
                'numero' => 4,
                'label' => '4. Revisión',
                'compat_title' => '4. Revisión Supervisor',
                'estado' => $totalEnsayos > 0
                    ? ($done4 ? 'Aprobada' : ($ensayosAprobados . ' de ' . $totalEnsayos . ' aprobadas'))
                    : 'Pendiente',
                'done' => $done4,
            ],
            [
                'numero' => 5,
                'label' => '5. Informe',
                'compat_title' => '5. Informe PDF',
                'estado' => $done5 ? 'Emitido' : ($done4 ? 'Por emitir' : 'Pendiente'),
                'done' => $done5,
            ],
        ];

        $primerIncompleto = false;
        foreach ($pasos as &$paso) {
            if ($paso['done']) {
                $paso['clase'] = 'is-done';
            } elseif (!$primerIncompleto) {
                $paso['clase'] = 'is-current';
                $primerIncompleto = true;
            } else {
                $paso['clase'] = '';
            }
        }
        unset($paso);

        return $pasos;
    }
}

if (!function_exists('cycsaResolverEtapaPill')) {
    function cycsaResolverEtapaPill(array $o): array {
        $estadoOrden = (string)($o['estado'] ?? '');
        if (in_array($estadoOrden, ['Finalizado', 'Archivado', 'Cerrado'], true)) {
            return ['clase' => 'cy-pill-ok', 'texto' => 'Cerrada'];
        }
        if ((int)($o['ensayos_devueltos'] ?? 0) > 0) {
            return ['clase' => 'cy-pill-danger', 'texto' => 'Matriz devuelta'];
        }
        $totalEns = (int)($o['total_ensayos'] ?? count($o['items'] ?? []));
        $aprobEns = (int)($o['ensayos_aprobados'] ?? 0);
        if ($totalEns > 0 && $aprobEns >= $totalEns) {
            return ['clase' => 'cy-pill-ok', 'texto' => 'Informe emitido'];
        }
        $etapa = (string)($o['etapa_flujo_os'] ?? ($o['hoja_solicitud']['etapa_flujo'] ?? ''));
        if ($etapa === 'matriz_en_revision' || (int)($o['ensayos_en_revision'] ?? 0) > 0) {
            return ['clase' => 'cy-pill-warn', 'texto' => 'Matriz en revisión'];
        }
        if ($etapa === 'tecnico_completo') {
            return ['clase' => 'cy-pill-warn', 'texto' => 'Por enviar a revisión'];
        }
        if ($etapa === 'en_ejecucion' || (int)($o['ensayos_con_resultados'] ?? 0) > 0 || (int)($o['muestras_aceptadas_lab'] ?? 0) > 0) {
            return ['clase' => 'cy-pill-warn', 'texto' => 'Ejecución técnica'];
        }
        if (!empty($o['recepcion_completa_os']) || $etapa === 'recepcion_completa') {
            return ['clase' => 'cy-pill-pend', 'texto' => 'Recepción completa'];
        }
        if (!empty($o['hoja_solicitud']) || !empty($o['hojas_solicitud'])) {
            return ['clase' => 'cy-pill-pend', 'texto' => 'Recepción en borrador'];
        }
        return ['clase' => 'cy-pill-pend', 'texto' => 'Pendiente RT-FM-13'];
    }
}

if (!function_exists('cycsaCalcularAvanceEnsayo')) {
    function cycsaCalcularAvanceEnsayo(array $it, array $rev): array {
        $raw = $it['resultados_json'] ?? '';
        $filas = [];
        if (is_string($raw) && $raw !== '' && $raw !== '[]') {
            $dec = json_decode($raw, true);
            if (is_array($dec)) {
                $filas = isset($dec['filas']) && is_array($dec['filas'])
                    ? $dec['filas']
                    : (isset($dec[0]) ? $dec : []);
            }
        }
        $cant = max(1, (int)($it['cantidad'] ?? 1));
        $total = max($cant, count($filas));
        $estado = (string)($rev['estado'] ?? 'pendiente');

        if ($estado === 'pendiente' || empty($rev['tiene_resultados'])) {
            return [0, $total];
        }
        if ($estado === 'aprobada') {
            return [$total, $total];
        }

        $clavesIdentidad = [
            'código laboratorio',
            'codigo laboratorio',
            'codigo_lab',
            'nombre muestra',
            'fecha de fabricación',
            'fecha de fabricacion',
            'diseño de concreto (lb/in²)',
            'muestra',
        ];
        $completas = 0;
        foreach ($filas as $f) {
            if (!is_array($f)) {
                continue;
            }
            $tieneDatoTecnico = false;
            foreach ($f as $k => $v) {
                if (trim((string)$v) === '') {
                    continue;
                }
                $kNorm = mb_strtolower(trim((string)$k), 'UTF-8');
                if (!in_array($kNorm, $clavesIdentidad, true)) {
                    $tieneDatoTecnico = true;
                    break;
                }
            }
            if ($tieneDatoTecnico) {
                $completas++;
            }
        }
        if ($completas === 0 && $estado === 'en_revision') {
            $completas = max(1, count($filas));
        }
        return [min($total, $completas), $total];
    }
}

if (!function_exists('cycsaResolverPillEnsayo')) {
    function cycsaResolverPillEnsayo(array $rev): array {
        $estado = (string)($rev['estado'] ?? 'pendiente');
        switch ($estado) {
            case 'aprobada':
                return ['clase' => 'cy-pill-ok', 'texto' => 'Aprobado'];
            case 'devuelta':
                return ['clase' => 'cy-pill-danger', 'texto' => 'Devuelta'];
            case 'en_revision':
                return ['clase' => 'cy-pill-warn', 'texto' => 'En revisión'];
            case 'borrador':
                return ['clase' => 'cy-pill-warn', 'texto' => 'En ejecución'];
            default:
                return ['clase' => 'cy-pill-pend', 'texto' => 'Pendiente'];
        }
    }
}
?>
<link rel="stylesheet" href="/Cycsa/publico/css/cycsa-sobrio.css">
<style>
    /* deprecated E.1 — no borrar para retrocompatibilidad de estilos */
    .view-switcher-group { display: none; }

    .cy-op-wrap {
        width: 100%;
        max-width: 100%;
        margin: 0;
    }
    .cy-op-subbar {
        display: flex;
        flex-wrap: wrap;
        justify-content: space-between;
        align-items: center;
        gap: 12px;
        margin-bottom: 16px;
    }
    .cy-op-views {
        display: flex;
        flex-wrap: wrap;
        align-items: center;
        gap: 18px;
    }
    .cy-op-view-link {
        font-size: 13px;
        color: var(--cy-muted);
        text-decoration: none;
        padding: 4px 0;
        border-bottom: 2px solid transparent;
        font-weight: 500;
    }
    .cy-op-view-link:hover {
        color: var(--cy-text);
    }
    .cy-op-view-link.is-active {
        color: var(--cy-brand);
        border-bottom-color: var(--cy-brand);
        font-weight: 600;
    }
    .cy-op-search {
        width: 280px;
        max-width: 100%;
        font-size: 14px;
        padding: 7px 12px;
        border: 1px solid var(--cy-border);
        border-radius: 8px;
        background: var(--cy-surface);
        color: var(--cy-text);
    }
    #tabla-lims-os tbody tr[id^="os-row-"] {
        cursor: pointer;
    }
    .dropdown-toggle-no-caret::after {
        display: none !important;
    }
    .lims-drawer-fallback-backdrop {
        position: fixed;
        inset: 0;
        background: rgba(17, 24, 39, 0.25);
        z-index: 1040;
        display: none;
    }
    .lims-drawer-fallback-backdrop.active {
        display: block;
    }
    .modal-premium {
        display: none;
        position: fixed;
        z-index: 1060;
        inset: 0;
        overflow: auto;
        background: rgba(17, 24, 39, 0.35);
    }
    .modal-premium-content {
        background: #FFFFFF;
        margin: 5% auto;
        padding: 24px 28px;
        border: 1px solid var(--cy-border);
        border-radius: 10px;
        width: min(520px, 94vw);
        box-shadow: none;
    }
    .btn-cerrar {
        background: none;
        border: 0;
        color: var(--cy-muted);
        font-size: 20px;
        line-height: 1;
        cursor: pointer;
        padding: 4px 8px;
    }
    .btn-cerrar:hover {
        color: var(--cy-text);
    }
</style>

<div class="container-fluid px-0 py-0 cy-op-wrap">
    <?php if (!empty($exito)): ?>
        <div class="cy-note mb-3 d-flex align-items-center gap-2" role="status">
            <span class="cy-pill cy-pill-ok">Listo</span>
            <span><?= htmlspecialchars((string)$exito, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>
    <?php if (!empty($error)): ?>
        <div class="cy-note mb-3 d-flex align-items-center gap-2" role="alert">
            <span class="cy-pill cy-pill-danger">Aviso</span>
            <span><?= htmlspecialchars((string)$error, ENT_QUOTES, 'UTF-8') ?></span>
        </div>
    <?php endif; ?>

    <!-- Encabezado de página + filtro por rol segmentado (.cy-seg) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h1 class="cy-page-title">Operaciones</h1>
        </div>

        <?php $tabQuery = ($tabActiva !== 'activas') ? ('&tab=' . urlencode((string)$tabActiva)) : ''; ?>
        <div class="btn-group cy-seg" role="group" aria-label="Filtro por bandeja de rol">
            <a href="/Cycsa/publico/operaciones?bandeja=todas<?= $tabQuery ?>"
               class="btn <?= ($bandejaActiva === 'todas') ? 'is-active' : '' ?>"
               data-bandeja="todas"
               title="Todas las Órdenes">Todas (<?= (int)($conteoBandejas['todas'] ?? 0) ?>)</a>
            <a href="/Cycsa/publico/operaciones?bandeja=recepcion<?= $tabQuery ?>"
               class="btn <?= ($bandejaActiva === 'recepcion') ? 'is-active' : '' ?>"
               data-bandeja="recepcion"
               title="① Bandeja de Recepción"
               aria-label="① Bandeja de Recepción">Recepción (<?= (int)($conteoBandejas['recepcion'] ?? 0) ?>)</a>
            <a href="/Cycsa/publico/operaciones?bandeja=tecnico<?= $tabQuery ?>"
               class="btn <?= ($bandejaActiva === 'tecnico') ? 'is-active' : '' ?>"
               data-bandeja="tecnico"
               title="② Bandeja del Técnico"
               aria-label="② Bandeja del Técnico">Técnico (<?= (int)($conteoBandejas['tecnico'] ?? 0) ?>)</a>
            <a href="/Cycsa/publico/operaciones?bandeja=supervisor<?= $tabQuery ?>"
               class="btn <?= ($bandejaActiva === 'supervisor') ? 'is-active' : '' ?>"
               data-bandeja="supervisor"
               title="③ Bandeja del Supervisor"
               aria-label="③ Bandeja del Supervisor">Supervisor (<?= (int)($conteoBandejas['supervisor'] ?? 0) ?>)</a>
            <a href="/Cycsa/publico/operaciones?bandeja=informes<?= $tabQuery ?>"
               class="btn <?= ($bandejaActiva === 'informes') ? 'is-active' : '' ?>"
               data-bandeja="informes"
               title="④ Bandeja de Informes"
               aria-label="④ Bandeja de Informes">Informes (<?= (int)($conteoBandejas['informes'] ?? 0) ?>)</a>
        </div>
    </div>

    <!-- Sub-barra sobria de estado y búsqueda -->
    <div id="tab-os" class="tab-content-active">
        <div class="cy-op-subbar">
            <nav class="cy-op-views" aria-label="Vistas de operaciones">
                <a href="/Cycsa/publico/operaciones?tab=activas" class="cy-op-view-link <?= ($tabActiva === 'activas') ? 'is-active' : '' ?>">
                    Activas (<?= (int)($conteosTabs['activas'] ?? 0) ?>)
                </a>
                <a href="/Cycsa/publico/operaciones?tab=muestreo" class="cy-op-view-link <?= ($tabActiva === 'muestreo') ? 'is-active' : '' ?>">
                    Pendientes de muestreo (<?= (int)($conteosTabs['muestreo'] ?? 0) ?>)
                </a>
                <a href="/Cycsa/publico/operaciones?tab=ensayos" class="cy-op-view-link <?= ($tabActiva === 'ensayos') ? 'is-active' : '' ?>">
                    En ensayos (<?= (int)($conteosTabs['ensayos'] ?? 0) ?>)
                </a>
                <a href="/Cycsa/publico/operaciones?tab=historico" class="cy-op-view-link <?= ($tabActiva === 'historico') ? 'is-active' : '' ?>">
                    Histórico (<?= (int)($conteosTabs['historico'] ?? 0) ?>)
                </a>
                <a href="/Cycsa/publico/operaciones/calendario" class="cy-op-view-link">
                    Calendario de rupturas
                </a>
            </nav>

            <div>
                <input type="text" id="lims-search" placeholder="Filtrar orden, cliente o proyecto..." class="form-control cy-op-search" aria-label="Filtrar órdenes">
            </div>
        </div>

        <!-- Tabla Plana de Órdenes -->
        <div class="card lims-table-container">
            <div class="table-responsive">
                <table class="table" id="tabla-lims-os">
                    <thead>
                        <tr>
                            <th>Orden</th>
                            <th>Cliente y proyecto</th>
                            <th>Etapa</th>
                            <th>Avance</th>
                            <th>Próxima acción</th>
                            <th class="text-end"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($ordenes as $o):
                            $o['id'] = (int)($o['id'] ?? 0);
                            $o['estado'] = (string)($o['estado'] ?? '');
                            $o['codigo_os'] = (string)($o['codigo_os'] ?? '');
                            $o['items'] = array_values(array_filter($o['items'] ?? [], static function ($it): bool {
                                return !is_array($it) || !array_key_exists('permitido', $it) || (int)$it['permitido'] === 1;
                            }));
                            $totalItemsOS = count($o['items']);
                            $matricesLlenadas = 0;
                            $matricesAprobadas = 0;
                            $primerItemId = null;
                            $primerItemPendienteId = null;
                            $primerItemRevisionId = null;
                            $primerItemAprobadoId = null;

                            foreach ($o['items'] as $it) {
                                $revIt = $it['revision_info'] ?? obtenerEstadoRevisionMatriz($it['resultados_json'] ?? null);
                                $tieneResIt = !empty($revIt['tiene_resultados']) || (!empty($it['resultados_json']) && $it['resultados_json'] !== '[]');
                                if ($tieneResIt) {
                                    $matricesLlenadas++;
                                }
                                if (($revIt['estado'] ?? '') === 'aprobada') {
                                    $matricesAprobadas++;
                                    if ($primerItemAprobadoId === null && !empty($it['id'])) {
                                        $primerItemAprobadoId = (int)$it['id'];
                                    }
                                }
                                if (($revIt['estado'] ?? '') === 'en_revision' && $primerItemRevisionId === null && !empty($it['id'])) {
                                    $primerItemRevisionId = (int)$it['id'];
                                }
                                if (!empty($it['id'])) {
                                    if ($primerItemId === null) {
                                        $primerItemId = (int)$it['id'];
                                    }
                                    if (!$tieneResIt && $primerItemPendienteId === null) {
                                        $primerItemPendienteId = (int)$it['id'];
                                    }
                                }
                            }

                            $matrizCompleta = ($totalItemsOS > 0 && $matricesLlenadas === $totalItemsOS);
                            $tieneTecnico = !empty($o['tecnico_muestreo']);
                            $tieneHojaServicio = !empty($o['hoja_solicitud']) || !empty($o['hojas_solicitud']);
                            $hojasOS = !empty($o['hojas_solicitud']) ? $o['hojas_solicitud'] : (!empty($o['hoja_solicitud']) ? [$o['hoja_solicitud']] : []);
                            $idHojaPrincipal = (int)($o['hoja_solicitud']['id'] ?? ($hojasOS[0]['id'] ?? 0));
                            $requiereMuestreo = !empty($o['requiere_muestreo']);
                            $tieneMuestrasAceptadas = !empty($o['muestras_aceptadas_lab']) && (int)$o['muestras_aceptadas_lab'] > 0;
                            $esSoloCompactacion = esOrdenSoloCompactacion($o['items']);
                            $idObjetivoMatriz = $primerItemPendienteId ?? $primerItemId;

                            // Compatibilidad con botones primarios de matrices sin apertura directa
                            if ($requiereMuestreo && !$tieneTecnico) {
                                $btnPrimario = [
                                    'tipo' => 'button',
                                    'onclick' => "abrirModalMuestreo(" . (int)$o['id'] . ", '" . htmlspecialchars(addslashes((string)$o['codigo_os']), ENT_QUOTES, 'UTF-8') . "')",
                                    'texto' => 'Asignar Técnico',
                                ];
                            } elseif (!$tieneHojaServicio && !$esSoloCompactacion) {
                                $btnPrimario = [
                                    'tipo' => 'link',
                                    'href' => '/Cycsa/publico/ordenes-servicio?id_os=' . (int)$o['id'],
                                    'texto' => 'Registrar RT-FM-13',
                                ];
                            } elseif ($matricesLlenadas === 0 && $idObjetivoMatriz) {
                                $btnPrimario = [
                                    'tipo' => 'button',
                                    'onclick' => 'abrirMatricesOS(' . (int)$o['id'] . ')',
                                    'texto' => 'Ver Matrices (' . $totalItemsOS . ')',
                                ];
                            } elseif (!$matrizCompleta && $idObjetivoMatriz) {
                                $btnPrimario = [
                                    'tipo' => 'button',
                                    'onclick' => 'abrirMatricesOS(' . (int)$o['id'] . ')',
                                    'texto' => 'Ver Matrices (' . $matricesLlenadas . '/' . $totalItemsOS . ')',
                                ];
                            } else {
                                $btnPrimario = [
                                    'tipo' => 'button',
                                    'onclick' => 'abrirMatricesOS(' . (int)$o['id'] . ')',
                                    'texto' => 'Ver Matrices (' . $matricesLlenadas . '/' . $totalItemsOS . ')',
                                ];
                            }

                            $cliInfNom = (string)($o['cliente_informe_nombre'] ?? ($o['cliente_nombre'] ?? 'Cliente'));
                            $cliFactNom = (string)($o['cliente_factura_nombre'] ?? $cliInfNom);
                            $condComAct = strtolower(trim((string)($o['condicion_comercial_activa'] ?? $o['condicion_comercial'] ?? $o['condicion_pago'] ?? $o['forma_pago'] ?? 'contado')));
                            $esCreditoFila = ($condComAct === 'credito');
                            $proyectoNom = trim((string)($o['nombre_proyecto'] ?? 'Sin proyecto'));
                            $cotCodigo = trim((string)($o['cot_codigo'] ?? ''));

                            $panelMetaPartes = array_filter([
                                $cliInfNom,
                                $proyectoNom,
                                $cotCodigo,
                                ucfirst($condComAct),
                            ], static fn(string $v): bool => $v !== '');
                            $panelMetaLine = implode(' · ', $panelMetaPartes);

                            $oCalc = $o;
                            $oCalc['total_ensayos'] = $totalItemsOS;
                            $oCalc['ensayos_aprobados'] = $o['ensayos_aprobados'] ?? $matricesAprobadas;
                            $oCalc['ensayos_con_resultados'] = $o['ensayos_con_resultados'] ?? $matricesLlenadas;
                            $oCalc['tecnico_100'] = ($totalItemsOS > 0 && (int)$oCalc['ensayos_aprobados'] >= $totalItemsOS);

                            $stepperPasos = (!empty($o['stepper_pasos']) && is_array($o['stepper_pasos']))
                                ? $o['stepper_pasos']
                                : cycsaCalcularStepperOperacion($oCalc);

                            $etapaPill = cycsaResolverEtapaPill($oCalc);
                            $pctAvance = $totalItemsOS > 0 ? (int)round(($matricesLlenadas / $totalItemsOS) * 100) : 0;

                            $sigPasoTexto = (string)($o['siguiente_paso_texto'] ?? ($btnPrimario['texto'] ?? 'Ver detalle'));
                            $sigPasoUrl = (string)($o['siguiente_paso_url'] ?? ($btnPrimario['href'] ?? ('/Cycsa/publico/operaciones/ver?id_os=' . (int)$o['id'])));

                            // Subtítulo para la caja .cy-next dentro del panel
                            if (!empty($o['puede_cerrar'])) {
                                $sigPasoDetalle = 'Todas las matrices aprobadas y condición comercial verificada.';
                            } elseif (($oCalc['ensayos_aprobados'] ?? 0) < $totalItemsOS) {
                                $sigPasoDetalle = (int)$oCalc['ensayos_aprobados'] . ' de ' . $totalItemsOS . ' matrices aprobadas.';
                            } else {
                                $sigPasoDetalle = $panelMetaLine;
                            }
                        ?>
                        <tr id="os-row-<?= (int)$o['id'] ?>"
                            data-detail-id="os-detail-<?= (int)$o['id'] ?>"
                            data-panel-meta="<?= htmlspecialchars($panelMetaLine, ENT_QUOTES, 'UTF-8') ?>"
                            class="fila-os-lims">
                            <td>
                                <div class="fw-semibold lims-os-code"><?= htmlspecialchars((string)$o['codigo_os'], ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="cy-meta">
                                    <?= htmlspecialchars($cotCodigo !== '' ? $cotCodigo : 'Sin cotización', ENT_QUOTES, 'UTF-8') ?> · <?= ucfirst($condComAct) ?><?= (!$esCreditoFila && empty($o['comercial_100']) && (float)($o['saldo_os'] ?? 0) > 0) ? ' · Saldo Pendiente' : '' ?>
                                </div>
                            </td>
                            <td>
                                <div class="fw-medium"><?= htmlspecialchars($cliInfNom, ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="cy-meta" data-col="cliente-sub">
                                    <?= htmlspecialchars($proyectoNom, ENT_QUOTES, 'UTF-8') ?><?= ($cliFactNom !== '' && $cliFactNom !== $cliInfNom) ? ' · Facturar a: ' . htmlspecialchars($cliFactNom, ENT_QUOTES, 'UTF-8') : '' ?>
                                </div>
                            </td>
                            <td>
                                <span class="cy-pill <?= htmlspecialchars($etapaPill['clase'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($etapaPill['texto'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </td>
                            <td>
                                <div class="cy-progress-cell">
                                    <div class="cy-bar"><i style="width: <?= $pctAvance ?>%;"></i></div>
                                    <span class="cy-meta"><?= $matricesLlenadas ?> de <?= $totalItemsOS ?> matrices</span>
                                </div>
                            </td>
                            <td>
                                <a href="<?= htmlspecialchars($sigPasoUrl, ENT_QUOTES, 'UTF-8') ?>" class="cy-action-link">
                                    Siguiente paso: <?= htmlspecialchars($sigPasoTexto, ENT_QUOTES, 'UTF-8') ?>
                                </a>
                            </td>
                            <td class="text-end" style="white-space: nowrap;">
                                <div class="d-inline-flex align-items-center justify-content-end gap-2">
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-sm"
                                            onclick="abrirMatricesOS(<?= (int)$o['id'] ?>)">
                                        Ver Matrices (<?= $matricesLlenadas ?>/<?= $totalItemsOS ?>)
                                    </button>

                                    <div class="dropdown d-inline-block">
                                        <button class="btn-more dropdown-toggle-no-caret"
                                                type="button"
                                                data-bs-toggle="dropdown"
                                                data-bs-boundary="viewport"
                                                data-bs-popper-config='{"strategy":"fixed"}'
                                                aria-expanded="false"
                                                aria-label="Más acciones">⋯</button>
                                        <ul class="dropdown-menu dropdown-menu-end">
                                            <li><a class="dropdown-item" href="javascript:void(0)" onclick="abrirDetalleOS(<?= (int)$o['id'] ?>)">Ver detalle</a></li>
                                            <li><a class="dropdown-item" href="/Cycsa/publico/operaciones/ver?id_os=<?= (int)$o['id'] ?>">Abrir página completa</a></li>
                                            <?php if ($tieneHojaServicio): ?>
                                                <li><a class="dropdown-item" href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= (int)$o['id'] ?>" target="_blank">Imprimir RT-FM-13</a></li>
                                                <?php if ($idHojaPrincipal > 0): ?>
                                                    <li><a class="dropdown-item" href="/Cycsa/publico/laboratorio/imprimir-solicitud?id_hoja=<?= $idHojaPrincipal ?>" target="_blank">Imprimir Hoja RT-FM-60</a></li>
                                                <?php endif; ?>
                                            <?php else: ?>
                                                <li><a class="dropdown-item" href="/Cycsa/publico/ordenes-servicio?id_os=<?= (int)$o['id'] ?>">Registrar RT-FM-13</a></li>
                                            <?php endif; ?>
                                            <?php if ($requiereMuestreo): ?>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="abrirModalMuestreo(<?= (int)$o['id'] ?>, '<?= htmlspecialchars(addslashes((string)$o['codigo_os']), ENT_QUOTES, 'UTF-8') ?>')"><?= $tieneTecnico ? 'Reasignar muestreo' : 'Programar muestreo' ?></a></li>
                                            <?php endif; ?>
                                            <?php if (!$esSoloCompactacion && function_exists('tienePermiso') && tienePermiso('laboratorio', 'ver')): ?>
                                                <li><a class="dropdown-item" href="/Cycsa/publico/laboratorio?tab=kanban" target="_blank">Módulo Laboratorio (Recepción)</a></li>
                                            <?php endif; ?>
                                            <?php if (!empty($o['puede_cerrar']) && !in_array($o['estado'], ['Finalizado', 'Archivado', 'Cerrado'], true)): ?>
                                                <li><hr class="dropdown-divider"></li>
                                                <li><a class="dropdown-item" href="javascript:void(0)" onclick="abrirModalCerrarOS(<?= (int)$o['id'] ?>, '<?= htmlspecialchars(addslashes((string)$o['codigo_os']), ENT_QUOTES, 'UTF-8') ?>')">Cerrar y archivar orden</a></li>
                                            <?php endif; ?>
                                        </ul>
                                    </div>
                                </div>
                            </td>
                        </tr>

                        <!-- Contenedor oculto que se monta dinámicamente en el Panel Lateral -->
                        <tr class="detalle-os-row" id="os-detail-<?= (int)$o['id'] ?>" style="display: none !important;">
                            <td colspan="6" style="padding: 0; border: 0;">
                                <div class="detalle-os-card">
                                    <!-- Stepper horizontal de 5 pasos (.cy-stepper) -->
                                    <ol class="cy-stepper" aria-label="Progreso de la orden">
                                        <?php foreach ($stepperPasos as $idxPaso => $paso):
                                            $pasoClase = trim((string)($paso['clase'] ?? ''));
                                            $pasoNum = (int)($paso['numero'] ?? ($paso['paso'] ?? ($idxPaso + 1)));
                                            $pasoLabel = (string)($paso['label'] ?? ($paso['titulo'] ?? ('Paso ' . $pasoNum)));
                                            $pasoEstado = (string)($paso['estado'] ?? ($paso['detalle'] ?? ''));
                                            $compatTitles = [
                                                1 => '1. RT-FM-13',
                                                2 => '2. Recepción Completa',
                                                3 => '3. Ejecución Lab',
                                                4 => '4. Revisión Supervisor',
                                                5 => '5. Informe PDF',
                                            ];
                                            $pasoTitle = (string)($paso['compat_title'] ?? ($compatTitles[$pasoNum] ?? $pasoLabel));
                                        ?>
                                        <li class="cy-step <?= htmlspecialchars($pasoClase, ENT_QUOTES, 'UTF-8') ?>" title="<?= htmlspecialchars($pasoTitle, ENT_QUOTES, 'UTF-8') ?>">
                                            <div class="cy-dot"><?= $pasoNum ?></div>
                                            <div class="cy-step-label"><?= htmlspecialchars($pasoLabel, ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="cy-step-state"><?= htmlspecialchars($pasoEstado, ENT_QUOTES, 'UTF-8') ?></div>
                                        </li>
                                        <?php endforeach; ?>
                                    </ol>

                                    <!-- Caja "Siguiente acción" (.cy-next) con el ÚNICO .btn-primary del panel -->
                                    <div class="cy-next">
                                        <div>
                                            <div class="fw-semibold">Siguiente acción: <?= htmlspecialchars($sigPasoTexto, ENT_QUOTES, 'UTF-8') ?></div>
                                            <div class="cy-meta"><?= htmlspecialchars($sigPasoDetalle, ENT_QUOTES, 'UTF-8') ?></div>
                                        </div>
                                        <?php if (!empty($o['puede_cerrar']) && !in_array($o['estado'], ['Finalizado', 'Archivado', 'Cerrado'], true)): ?>
                                            <button type="button" class="btn btn-primary" onclick="abrirModalCerrarOS(<?= (int)$o['id'] ?>, '<?= htmlspecialchars(addslashes((string)$o['codigo_os']), ENT_QUOTES, 'UTF-8') ?>')">Cerrar orden</button>
                                        <?php else: ?>
                                            <a href="<?= htmlspecialchars($sigPasoUrl, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary"><?= htmlspecialchars($sigPasoTexto, ENT_QUOTES, 'UTF-8') ?></a>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Tabs subrayadas simples (.nav-tabs) sin íconos -->
                                    <ul class="nav nav-tabs mb-3" role="tablist">
                                        <li class="nav-item" role="presentation">
                                            <button type="button"
                                                    class="nav-link active btn-drawer-tab"
                                                    role="tab"
                                                    onclick="switchDrawerCardTab(this, 'tab-ensayos-<?= (int)$o['id'] ?>')">
                                                Ensayos <span class="cy-tab-count">(<?= $totalItemsOS ?>)</span>
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button type="button"
                                                    class="nav-link btn-drawer-tab"
                                                    role="tab"
                                                    onclick="switchDrawerCardTab(this, 'tab-recepcion-<?= (int)$o['id'] ?>')">
                                                Recepción <span class="cy-tab-count">(<?= count($hojasOS) ?>)</span>
                                            </button>
                                        </li>
                                        <li class="nav-item" role="presentation">
                                            <button type="button"
                                                    class="nav-link btn-drawer-tab"
                                                    role="tab"
                                                    onclick="switchDrawerCardTab(this, 'tab-informes-<?= (int)$o['id'] ?>')">
                                                Informes <span class="cy-tab-count">(<?= $matricesAprobadas ?>)</span>
                                            </button>
                                        </li>
                                    </ul>

                                    <div class="lims-drawer-tabs-content">
                                        <!-- TAB 1: ENSAYOS -->
                                        <div id="tab-ensayos-<?= (int)$o['id'] ?>" class="drawer-card-tab-pane">
                                            <div class="cy-meta mb-2">
                                                <?= $totalItemsOS ?> <?= $totalItemsOS === 1 ? 'matriz técnica' : 'matrices técnicas' ?> en esta orden:
                                                <?= $matricesLlenadas ?> completada<?= $matricesLlenadas === 1 ? '' : 's' ?>, <?= max(0, $totalItemsOS - $matricesLlenadas) ?> pendiente<?= ($totalItemsOS - $matricesLlenadas) === 1 ? '' : 's' ?> de captura.
                                            </div>

                                            <?php foreach ($o['items'] as $it):
                                                $revIt = $it['revision_info'] ?? obtenerEstadoRevisionMatriz($it['resultados_json'] ?? null);
                                                $tieneResIt = !empty($revIt['tiene_resultados']);
                                                $estadoRevIt = (string)($revIt['estado'] ?? 'pendiente');
                                                $pillEns = cycsaResolverPillEnsayo($revIt);
                                                [$avanceX, $avanceY] = cycsaCalcularAvanceEnsayo($it, $revIt);
                                                $descEnsEsc = htmlspecialchars(addslashes((string)($it['descripcion_ensayo'] ?? '')), ENT_QUOTES, 'UTF-8');
                                                $cliNomEsc = htmlspecialchars(addslashes($cliInfNom), ENT_QUOTES, 'UTF-8');
                                                $cliMailEsc = htmlspecialchars(addslashes((string)($o['cliente_email'] ?? '')), ENT_QUOTES, 'UTF-8');
                                                $codOsEsc = htmlspecialchars(addslashes((string)($o['codigo_os'] ?? '')), ENT_QUOTES, 'UTF-8');
                                                $proyEsc = htmlspecialchars(addslashes($proyectoNom), ENT_QUOTES, 'UTF-8');
                                                $ubicEsc = htmlspecialchars(addslashes((string)($o['ubicacion'] ?? '')), ENT_QUOTES, 'UTF-8');
                                                $normaTxt = trim((string)($it['norma_astm'] ?? ''));
                                                $idDet = (int)($it['id'] ?? 0);
                                            ?>
                                            <div class="cy-row">
                                                <div class="flex-grow-1 min-w-0">
                                                    <div class="cy-assay-name"><?= htmlspecialchars((string)($it['descripcion_ensayo'] ?? 'Ensayo'), ENT_QUOTES, 'UTF-8') ?></div>
                                                    <div class="cy-assay-norm">
                                                        <?= htmlspecialchars($normaTxt !== '' ? $normaTxt : 'Sin norma asignada', ENT_QUOTES, 'UTF-8') ?> · <?= $avanceY ?> <?= $avanceY === 1 ? 'muestra' : 'muestras' ?><?= !empty($it['codigo_muestra']) ? ' · ' . htmlspecialchars((string)$it['codigo_muestra'], ENT_QUOTES, 'UTF-8') : '' ?>
                                                    </div>
                                                </div>
                                                <span class="cy-pill <?= htmlspecialchars($pillEns['clase'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($pillEns['texto'], ENT_QUOTES, 'UTF-8') ?></span>
                                                <span class="cy-meta"><?= $avanceX ?>/<?= $avanceY ?></span>

                                                <?php if ($estadoRevIt === 'aprobada'): ?>
                                                    <button type="button"
                                                            class="btn btn-outline-secondary btn-sm"
                                                            onclick="abrirModalEnviarMatriz(<?= $idDet ?>, '<?= $descEnsEsc ?>', '<?= $cliNomEsc ?>', '<?= $cliMailEsc ?>', '<?= $codOsEsc ?>')">Enviar al cliente</button>
                                                <?php elseif (!$tieneHojaServicio && !$tieneResIt && !$esSoloCompactacion): ?>
                                                    <a href="/Cycsa/publico/ordenes-servicio?id_os=<?= (int)$o['id'] ?>" class="btn btn-outline-secondary btn-sm">Registrar RT-FM-13</a>
                                                <?php else: ?>
                                                    <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $idDet ?>" class="btn btn-outline-secondary btn-sm">Capturar</a>
                                                <?php endif; ?>

                                                <div class="dropdown">
                                                    <button class="btn-more dropdown-toggle-no-caret"
                                                            type="button"
                                                            data-bs-toggle="dropdown"
                                                            aria-expanded="false"
                                                            aria-label="Más acciones">⋯</button>
                                                    <ul class="dropdown-menu dropdown-menu-end">
                                                        <li><a class="dropdown-item" href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $idDet ?>"><?= $tieneResIt ? 'Editar matriz' : 'Ver matriz' ?></a></li>
                                                        <?php if ($tieneResIt): ?>
                                                            <li><a class="dropdown-item" href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= $idDet ?>" target="_blank">Imprimir RT-FM-22</a></li>
                                                        <?php endif; ?>
                                                        <?php if ($idHojaPrincipal > 0): ?>
                                                            <li><a class="dropdown-item" href="/Cycsa/publico/laboratorio/imprimir-solicitud?id_hoja=<?= $idHojaPrincipal ?>" target="_blank">Imprimir Hoja RT-FM-60</a></li>
                                                            <li><a class="dropdown-item" href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= (int)$o['id'] ?>&id_hoja=<?= $idHojaPrincipal ?>" target="_blank">Imprimir RT-FM-13</a></li>
                                                        <?php endif; ?>
                                                        <?php if ($esSupervisor && $tieneResIt && $estadoRevIt !== 'aprobada'): ?>
                                                            <li><button type="button" class="dropdown-item" onclick="abrirModalAprobarEnIndex(<?= $idDet ?>, '<?= $descEnsEsc ?>')">Aprobar matriz</button></li>
                                                        <?php endif; ?>
                                                        <?php if ($esSupervisor && $estadoRevIt === 'aprobada'): ?>
                                                            <li><button type="button" class="dropdown-item" onclick="abrirModalCorreccionAdminIndex(<?= $idDet ?>, '<?= $descEnsEsc ?>', '<?= $cliNomEsc ?>', '<?= $proyEsc ?>', '<?= $ubicEsc ?>')">Reemitir</button></li>
                                                        <?php endif; ?>
                                                        <?php if ($estadoRevIt === 'devuelta'): ?>
                                                            <li><button type="button" class="dropdown-item" onclick="verObservacionDevolucion(<?= $idDet ?>, '<?= $descEnsEsc ?>', '<?= htmlspecialchars(addslashes((string)($revIt['motivo_devolucion'] ?? '')), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes((string)($revIt['usuario_revisor'] ?? 'Supervisor')), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes((string)($revIt['fecha_revision'] ?? '')), ENT_QUOTES, 'UTF-8') ?>')">Ver observación</button></li>
                                                        <?php endif; ?>
                                                        <?php if ($esSupervisor && $tieneResIt && $estadoRevIt !== 'devuelta'): ?>
                                                            <li><hr class="dropdown-divider"></li>
                                                            <li><a class="dropdown-item text-danger" href="javascript:void(0)" onclick="abrirModalDevolverEnIndex(<?= $idDet ?>, '<?= $descEnsEsc ?>')">Devolver</a></li>
                                                        <?php endif; ?>
                                                    </ul>
                                                </div>
                                            </div>
                                            <?php endforeach; ?>

                                            <!-- Cierre bloqueado (.cy-note) al final, sin pestañas ni bandas grises anidadas -->
                                            <div class="cy-note" id="tab-cierre-<?= (int)$o['id'] ?>">
                                                <?php if (in_array((string)$o['estado'], ['Finalizado', 'Archivado', 'Cerrado'], true)): ?>
                                                    Orden cerrada y archivada en histórico LIMS.
                                                <?php elseif (!empty($o['puede_cerrar'])): ?>
                                                    <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                                                        <span>100% técnico y comercial cumplido. Esta orden está lista para cierre y archivo.</span>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="abrirModalCerrarOS(<?= (int)$o['id'] ?>, '<?= htmlspecialchars(addslashes((string)$o['codigo_os']), ENT_QUOTES, 'UTF-8') ?>')">Cerrar orden</button>
                                                    </div>
                                                <?php else: ?>
                                                    El cierre de la orden se habilita cuando las <?= $totalItemsOS ?> matrices estén aprobadas y sus informes emitidos (actualmente <?= (int)$oCalc['ensayos_aprobados'] ?> de <?= $totalItemsOS ?> aprobadas<?= empty($o['comercial_100']) ? ($esCreditoFila ? ', CxC pendiente' : ', saldo pendiente C$ ' . number_format((float)($o['saldo_os'] ?? 0), 2)) : '' ?>).
                                                <?php endif; ?>
                                            </div>
                                        </div>

                                        <!-- TAB 2: RECEPCIÓN Y DATOS DE LA ORDEN -->
                                        <div id="tab-recepcion-<?= (int)$o['id'] ?>" class="drawer-card-tab-pane" style="display: none;">
                                            <div id="tab-resumen-<?= (int)$o['id'] ?>">
                                                <?php if (empty($hojasOS)): ?>
                                                    <div class="cy-note mb-3">
                                                        Esta orden aún no tiene hoja de solicitud RT-FM-13 registrada.
                                                    </div>
                                                <?php else: ?>
                                                    <?php foreach ($hojasOS as $hOS):
                                                        $hCompleta = !empty($hOS['recepcion_completa']) || in_array((string)($hOS['etapa_flujo'] ?? ''), ['recepcion_completa', 'en_ejecucion', 'tecnico_completo', 'matriz_en_revision', 'informe'], true);
                                                    ?>
                                                    <div class="cy-row">
                                                        <div class="flex-grow-1 min-w-0">
                                                            <div class="cy-assay-name">Hoja RT-FM-13 · <?= htmlspecialchars((string)($hOS['numero_registro'] ?? ('#' . $hOS['id'])), ENT_QUOTES, 'UTF-8') ?></div>
                                                            <div class="cy-assay-norm">
                                                                <?= htmlspecialchars((string)($hOS['descripcion_ensayo'] ?? 'Solicitud general de análisis'), ENT_QUOTES, 'UTF-8') ?>
                                                            </div>
                                                        </div>
                                                        <span class="cy-pill <?= $hCompleta ? 'cy-pill-ok' : 'cy-pill-pend' ?>"><?= $hCompleta ? 'Completa' : 'En borrador' ?></span>
                                                        <a href="/Cycsa/publico/ordenes-servicio?id_os=<?= (int)$o['id'] ?>&id_hoja=<?= (int)$hOS['id'] ?>" class="btn btn-outline-secondary btn-sm">Ver datos</a>
                                                    </div>
                                                    <?php endforeach; ?>
                                                <?php endif; ?>

                                                <div class="cy-row">
                                                    <div class="flex-grow-1">
                                                        <div class="cy-assay-name">Cliente para informe y facturación</div>
                                                        <div class="cy-assay-norm">Informe: <?= htmlspecialchars($cliInfNom, ENT_QUOTES, 'UTF-8') ?> · Factura: <?= htmlspecialchars($cliFactNom, ENT_QUOTES, 'UTF-8') ?> · <?= ucfirst($condComAct) ?></div>
                                                    </div>
                                                </div>

                                                <div class="cy-row">
                                                    <div class="flex-grow-1">
                                                        <div class="cy-assay-name">Logística y custodia de muestras</div>
                                                        <div class="cy-assay-norm">
                                                            <?php if ($esSoloCompactacion): ?>
                                                                Ensayo in situ (Operaciones / Terreno)
                                                            <?php elseif ($requiereMuestreo): ?>
                                                                Muestreo en campo · Técnico: <?= $tieneTecnico ? htmlspecialchars((string)$o['tecnico_muestreo'], ENT_QUOTES, 'UTF-8') : 'Sin asignar' ?>
                                                            <?php else: ?>
                                                                Entrega en ventanilla de Laboratorio Central
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php if ($requiereMuestreo && !$tieneTecnico): ?>
                                                        <button type="button" class="btn btn-outline-secondary btn-sm" onclick="abrirModalMuestreo(<?= (int)$o['id'] ?>, '<?= htmlspecialchars(addslashes((string)$o['codigo_os']), ENT_QUOTES, 'UTF-8') ?>')">Asignar técnico</button>
                                                    <?php endif; ?>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- TAB 3: INFORMES -->
                                        <div id="tab-informes-<?= (int)$o['id'] ?>" class="drawer-card-tab-pane" style="display: none;">
                                            <?php
                                            $itemsAprobados = array_values(array_filter($o['items'], static function ($it): bool {
                                                $revIt = $it['revision_info'] ?? obtenerEstadoRevisionMatriz($it['resultados_json'] ?? null);
                                                return ($revIt['estado'] ?? '') === 'aprobada';
                                            }));
                                            ?>
                                            <?php if (empty($itemsAprobados)): ?>
                                                <div class="cy-note">
                                                    Aún no hay informes emitidos. Se habilitan cuando una matriz es aprobada por supervisión.
                                                </div>
                                            <?php else: ?>
                                                <?php foreach ($itemsAprobados as $itApr):
                                                    $revApr = $itApr['revision_info'] ?? obtenerEstadoRevisionMatriz($itApr['resultados_json'] ?? null);
                                                ?>
                                                <div class="cy-row">
                                                    <div class="flex-grow-1 min-w-0">
                                                        <div class="cy-assay-name"><?= htmlspecialchars((string)($itApr['descripcion_ensayo'] ?? 'Informe'), ENT_QUOTES, 'UTF-8') ?></div>
                                                        <div class="cy-assay-norm">
                                                            <?= htmlspecialchars((string)($itApr['formato_nombre'] ?? 'Matriz RT-FM-22'), ENT_QUOTES, 'UTF-8') ?><?= !empty($revApr['usuario_revisor']) ? ' · Aprobado por ' . htmlspecialchars((string)$revApr['usuario_revisor'], ENT_QUOTES, 'UTF-8') : '' ?>
                                                        </div>
                                                    </div>
                                                    <span class="cy-pill cy-pill-ok">Emitido</span>
                                                    <a href="/Cycsa/publico/operaciones/descargar-matriz-pdf?id_detalle=<?= (int)$itApr['id'] ?>" target="_blank" class="btn btn-outline-secondary btn-sm">Descargar PDF</a>
                                                </div>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            </td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (empty($ordenes)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-5">
                                <span class="cy-meta">No se encontraron órdenes de servicio en esta bandeja.</span>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- PANEL LATERAL OFFCANVAS (640px, padding 24-28px) -->
<div class="lims-drawer-fallback-backdrop" id="lims-drawer-overlay" onclick="cerrarLimsDrawer()"></div>
<aside class="offcanvas offcanvas-end" tabindex="-1" id="lims-drawer" aria-labelledby="lims-drawer-title">
    <div class="offcanvas-header">
        <div class="w-100">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <h2 class="cy-order-code" id="lims-drawer-title">Orden</h2>
                <div class="d-flex align-items-center gap-3 flex-shrink-0">
                    <a id="lims-drawer-btn-full" href="#" class="cy-action-link">Abrir página completa</a>
                    <button type="button" class="btn-close" onclick="cerrarLimsDrawer()" aria-label="Cerrar"></button>
                </div>
            </div>
            <div class="cy-panel-meta-line" id="lims-drawer-subtitle"></div>
        </div>
    </div>
    <div class="offcanvas-body" id="lims-drawer-body"></div>
</aside>

<!-- MODAL CONFIRMAR CIERRE DE OPERACIÓN -->
<div id="modalConfirmarCierreOS" class="modal-premium" style="display: none;">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Cierre definitivo de orden</h3>
            <button type="button" onclick="cerrarModalCierreOS()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/cerrar-operacion">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_os" id="cierre_id_os" value="">
            <p class="mb-3" style="font-size: 14px;">
                ¿Confirma el cierre y archivo de la orden <strong id="cierre_codigo_os"></strong>? Pasará al histórico LIMS.
            </p>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Observaciones de cierre (opcional)</label>
                <textarea name="notas_cierre" class="form-control" rows="2" placeholder="Notas de cierre..."></textarea>
            </div>
            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" onclick="cerrarModalCierreOS()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Confirmar cierre</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL PROGRAMACIÓN MUESTREO -->
<div id="modalMuestreo" class="modal-premium" style="display: none;">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Programación de muestreo: <span id="mues_codigo_os"></span></h3>
            <button type="button" onclick="cerrarModalMuestreo()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/programar-muestreo">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_os" id="mues_id_os">
            <div class="mb-3">
                <label class="form-label d-block" style="font-size: 13px; font-weight: 500;">Modalidad de entrega / muestreo</label>
                <div class="d-flex gap-3 flex-wrap">
                    <label class="d-inline-flex align-items-center gap-2" style="font-size: 14px; cursor: pointer;">
                        <input type="radio" name="modalidad_muestreo" value="tecnico" checked onclick="toggleModalidadMuestreo('tecnico')">
                        Técnico CYCSA (muestreo en sitio)
                    </label>
                    <label class="d-inline-flex align-items-center gap-2" style="font-size: 14px; cursor: pointer;">
                        <input type="radio" name="modalidad_muestreo" value="cliente" onclick="toggleModalMuestreo('cliente')">
                        Cliente entrega en laboratorio
                    </label>
                </div>
            </div>
            <div class="row g-3 mb-3">
                <div class="col-6">
                    <label id="lbl_fecha_mues" class="form-label" style="font-size: 13px;">Fecha</label>
                    <input type="date" name="fecha_muestreo" id="input_fecha_mues" required class="form-control" value="<?= date('Y-m-d') ?>">
                </div>
                <div class="col-6">
                    <label id="lbl_hora_mues" class="form-label" style="font-size: 13px;">Hora</label>
                    <input type="time" name="hora_muestreo" id="input_hora_mues" required class="form-control" value="<?= date('H:i') ?>">
                </div>
            </div>
            <div class="mb-3" id="grp_tecnico_cycsa">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label m-0" style="font-size: 13px;">Técnico asignado</label>
                    <a href="/Cycsa/publico/configuracion" class="cy-action-link" style="font-size: 13px;" target="_blank">Gestionar técnicos</a>
                </div>
                <select name="tecnico_muestreo_select" id="select_tecnico_muestreo" class="form-select">
                    <option value="">-- Seleccionar técnico --</option>
                    <?php foreach ($tecnicos as $t): ?>
                        <option value="<?= htmlspecialchars((string)$t['nombre'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars((string)$t['nombre'], ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="mb-3" id="grp_cliente_entrega" style="display: none;">
                <label class="form-label" style="font-size: 13px;">Persona que entrega la muestra</label>
                <input type="text" name="cliente_entrega_nombre" id="input_cliente_entrega" class="form-control" placeholder="Nombre del entregante">
            </div>
            <div class="mb-3" id="grp_vehiculo_cycsa">
                <div class="d-flex justify-content-between align-items-center mb-1">
                    <label class="form-label m-0" style="font-size: 13px;">Vehículo asignado</label>
                    <a href="/Cycsa/publico/configuracion" class="cy-action-link" style="font-size: 13px;" target="_blank">Gestionar vehículos</a>
                </div>
                <select name="vehiculo_muestreo" id="select_vehiculo_muestreo" class="form-select">
                    <option value="">-- Seleccionar vehículo --</option>
                    <?php foreach ($vehiculos as $v):
                        $lblVehiculo = $v['placa'] . (!empty($v['marca']) ? ' - ' . $v['marca'] . ' ' . $v['modelo'] : '');
                    ?>
                        <option value="<?= htmlspecialchars((string)$v['placa'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($lblVehiculo, ENT_QUOTES, 'UTF-8') ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <input type="hidden" name="tecnico_muestreo" id="final_tecnico_muestreo" value="">
            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" onclick="cerrarModalMuestreo()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                <button type="submit" onclick="prepararSubmitMuestreo(event)" class="btn btn-primary btn-sm">Guardar programación</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL HOJA DE CAMPO RT-FM-07 -->
<div id="modalHojaCampo" class="modal-premium" style="display: none;">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Hoja de campo RT-FM-07: <span id="field_codigo_os"></span></h3>
            <button type="button" onclick="cerrarModalHojaCampo()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/guardar-hoja-campo">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_os" id="field_id_os">
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px;">Código de hoja de campo</label>
                <input type="text" name="hoja_campo_codigo" id="field_hoja_campo_codigo" required class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px;">Operador / muestreador responsable</label>
                <input type="text" name="hoja_campo_operador" id="field_hoja_campo_operador" list="lista-tecnicos-cycsa" required class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px;">Horas de espera requeridas</label>
                <input type="text" inputmode="numeric" pattern="[0-9]+" autocomplete="off" name="horas_espera_requeridas" id="field_horas_espera" class="form-control" value="24">
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px;">Observaciones del muestreo</label>
                <textarea name="hoja_campo_notas" id="field_hoja_campo_notas" class="form-control" rows="2"></textarea>
            </div>
            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" onclick="cerrarModalHojaCampo()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Guardar hoja de campo</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL REVISIÓN DE RESULTADOS CALIDAD -->
<div id="modalRevisionResultados" class="modal-premium" style="display: none;">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Revisión técnica de calidad: <span id="qc_codigo_os"></span></h3>
            <button type="button" onclick="cerrarModalRevisionResultados()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/procesar-revision-resultados">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_os" id="qc_id_os">
            <input type="hidden" name="decision" id="qc_decision" value="">
            <p class="mb-3" style="font-size: 14px;">Valide si los resultados cargados cumplen la normativa para aprobar o devolver el expediente.</p>
            <div class="mb-3" id="qc-group-obs" style="display: none;">
                <label class="form-label" style="font-size: 13px;">Detalle los resultados observados</label>
                <textarea name="motivo_observacion" id="qc_motivo" class="form-control" rows="3"></textarea>
            </div>
            <div class="d-flex justify-content-between gap-2 pt-2 border-top">
                <button type="button" onclick="ejecutarQCObservar()" class="btn btn-outline-secondary btn-sm">Devolver / Observar</button>
                <div class="d-flex gap-2">
                    <button type="button" onclick="cerrarModalRevisionResultados()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                    <button type="button" onclick="ejecutarQCAprobar()" class="btn btn-primary btn-sm" id="btn-qc-aprobar">Aprobar revisión</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL LEGACY MATRIZ POR PRODUCTO -->
<div id="modalMatrizEnsayosOS" class="modal-premium" style="display: none;">
    <div class="modal-premium-content" style="width: min(960px, 94vw);">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Matrices de resultados — <span id="m_matriz_codigo_os"></span></h3>
            <button type="button" onclick="cerrarModalMatrizEnsayosOS()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <div id="m_matriz_loading" class="text-center py-4 cy-meta">Cargando ensayos...</div>
        <div id="m_matriz_contenido" style="display: none;">
            <div class="table-responsive border rounded">
                <table class="table" id="tabla-matriz-os-body">
                    <thead>
                        <tr>
                            <th>Ensayo</th>
                            <th>Norma</th>
                            <th>Formato</th>
                            <th class="text-center">Cantidad</th>
                            <th class="text-end">Acción</th>
                        </tr>
                    </thead>
                    <tbody id="tbody-matriz-items-os"></tbody>
                </table>
            </div>
        </div>
        <div class="d-flex justify-content-end mt-3">
            <button type="button" onclick="cerrarModalMatrizEnsayosOS()" class="btn btn-outline-secondary btn-sm">Cerrar</button>
        </div>
    </div>
</div>

<!-- MODAL DE CAPTURA DIRECTA -->
<div id="modalResultadosDirecto" class="modal-premium" style="display: none;">
    <div class="modal-premium-content" style="width: min(1040px, 94vw);">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" id="m_res_titulo_ensayo" style="font-size: 17px; font-weight: 600;">Capturar matriz técnica</h3>
            <button type="button" onclick="cerrarModalResultadosDirecto()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <form id="form-matriz-directa" onsubmit="return guardarFormularioMatrizDirecta(event)">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_detalle" id="m_res_id_detalle" value="">
            <input type="hidden" name="resultados_json" id="m_res_json_input" value="">
            <div class="table-responsive border rounded mb-3">
                <table class="table">
                    <thead id="tabla-directa-header"></thead>
                    <tbody id="tabla-directa-body"></tbody>
                </table>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                <button type="button" class="btn btn-outline-secondary btn-sm" onclick="agregarFilaDirecta()">Agregar fila</button>
                <div class="d-flex gap-2">
                    <button type="button" onclick="cerrarModalResultadosDirecto()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                    <button type="submit" class="btn btn-primary btn-sm">Guardar matriz</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL ENVÍO DE INFORME PDF AL CLIENTE -->
<div id="modalEnviarInformeCliente" class="modal-premium" style="display: none;">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Enviar informe al cliente</h3>
            <button type="button" onclick="cerrarModalEnviarMatriz()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/enviar-matriz-cliente" id="form-enviar-matriz-cliente" onsubmit="prepararEnvioMatriz(this)">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_detalle" id="envio_id_detalle" value="">
            <div class="cy-note mt-0 mb-3">
                <div class="fw-semibold text-dark" id="envio_txt_ensayo"></div>
                <div class="cy-meta">Orden: <span id="envio_txt_os"></span> · Cliente: <span id="envio_txt_cliente"></span></div>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Correo(s) del destinatario</label>
                <input type="text" name="destinatario" id="envio_destinatario" class="form-control" required placeholder="cliente@empresa.com, copia@empresa.com">
                <div class="cy-meta mt-1">Separe múltiples destinatarios con coma.</div>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Asunto del correo</label>
                <input type="text" name="asunto" id="envio_asunto" class="form-control" required>
            </div>
            <div class="d-flex justify-content-between align-items-center pt-2 border-top">
                <a href="javascript:void(0)" id="btn-previsualizar-pdf" target="_blank" class="btn btn-outline-secondary btn-sm">Previsualizar PDF</a>
                <div class="d-flex gap-2">
                    <button type="button" onclick="cerrarModalEnviarMatriz()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                    <button type="submit" id="btn-submit-envio" class="btn btn-primary btn-sm">Enviar por correo</button>
                </div>
            </div>
        </form>
    </div>
</div>

<!-- MODAL VER OBSERVACIÓN DE DEVOLUCIÓN -->
<div id="modalVerObservacionDevolucion" class="modal-premium" style="display: none;">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Observación de devolución</h3>
            <button type="button" onclick="cerrarModalVerObservacionDevolucion()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <p class="mb-2" style="font-size: 14px;">Ensayo: <strong id="ver_obs_ensayo"></strong></p>
        <div class="cy-note mt-0 mb-3">
            <div id="ver_obs_motivo" style="color: var(--cy-text); white-space: pre-wrap;"></div>
        </div>
        <div class="d-flex justify-content-between cy-meta mb-3">
            <span>Supervisor: <strong id="ver_obs_supervisor"></strong></span>
            <span>Fecha: <strong id="ver_obs_fecha"></strong></span>
        </div>
        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
            <a id="ver_obs_btn_editar" href="#" class="btn btn-primary btn-sm">Abrir y corregir matriz</a>
            <button type="button" onclick="cerrarModalVerObservacionDevolucion()" class="btn btn-outline-secondary btn-sm">Cerrar</button>
        </div>
    </div>
</div>

<!-- MODAL SUPERVISOR: APROBAR MATRIZ EN INDEX -->
<div id="modalAprobarMatrizIndex" class="modal-premium" style="display: none;">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Aprobar matriz técnica</h3>
            <button type="button" onclick="cerrarModalAprobarEnIndex()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <p class="mb-2" style="font-size: 14px;">¿Confirma la aprobación técnica de resultados para este ensayo?</p>
        <p class="mb-3" style="font-size: 14px;">Ensayo: <strong id="aprob_index_ensayo"></strong></p>
        <form method="POST" action="/Cycsa/publico/operaciones/aprobar-matriz-producto">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_detalle" id="aprob_index_id_detalle" value="">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones">
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Nota de aprobación (opcional)</label>
                <input type="text" name="nota_aprobacion" class="form-control" placeholder="Conforme con tolerancias y especificaciones.">
            </div>
            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" onclick="cerrarModalAprobarEnIndex()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Aprobar matriz</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL SUPERVISOR: DEVOLVER MATRIZ EN INDEX -->
<div id="modalDevolverMatrizIndex" class="modal-premium" style="display: none;">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Devolver matriz técnica</h3>
            <button type="button" onclick="cerrarModalDevolverEnIndex()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <p class="mb-2" style="font-size: 14px;">Ensayo: <strong id="dev_index_ensayo"></strong></p>
        <form method="POST" action="/Cycsa/publico/operaciones/devolver-matriz-producto">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_detalle" id="dev_index_id_detalle" value="">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones">
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Motivo u observaciones técnicas *</label>
                <textarea name="motivo_devolucion" required rows="4" class="form-control" placeholder="Indique las correcciones necesarias..."></textarea>
            </div>
            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" onclick="cerrarModalDevolverEnIndex()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Devolver al laboratorio</button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL SUPERVISOR: REEMITIR INFORME (CORRECCIÓN ADMINISTRATIVA) -->
<div id="modalCorreccionAdminIndex" class="modal-premium" style="display: none;">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Reemitir informe</h3>
            <button type="button" onclick="cerrarModalCorreccionAdminIndex()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <p class="mb-2" style="font-size: 14px;">Ensayo: <strong id="corr_index_ensayo_nombre"></strong></p>
        <form method="POST" action="/Cycsa/publico/operaciones/solicitar-correccion-admin">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_detalle" id="corr_index_id_detalle" value="">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones">
            <div class="cy-note mt-0 mb-3">
                Se emitirá una nueva versión correlativa y la matriz pasará a revisión para su re-aprobación.
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Motivo de la corrección *</label>
                <textarea name="motivo_correccion" id="corr_index_motivo" rows="2" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Nombre o razón social</label>
                <input type="text" name="cliente_nombre" id="corr_index_cliente" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Proyecto</label>
                <input type="text" name="nombre_proyecto" id="corr_index_proyecto" class="form-control">
            </div>
            <div class="row g-2 mb-3">
                <div class="col-6">
                    <label class="form-label" style="font-size: 13px; font-weight: 500;">Ubicación</label>
                    <input type="text" name="ubicacion" id="corr_index_ubicacion" class="form-control">
                </div>
                <div class="col-6">
                    <label class="form-label" style="font-size: 13px; font-weight: 500;">Atención a</label>
                    <input type="text" name="atencion_a" id="corr_index_atencion" class="form-control">
                </div>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Identificación / RUC</label>
                <input type="text" name="identificacion" id="corr_index_identificacion" class="form-control">
            </div>
            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" onclick="cerrarModalCorreccionAdminIndex()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Guardar y reemitir</button>
            </div>
        </form>
    </div>
</div>

<script>
    function toggleModalidadMuestreo(tipo) {
        const grpTec = document.getElementById('grp_tecnico_cycsa');
        const grpCli = document.getElementById('grp_cliente_entrega');
        const grpVeh = document.getElementById('grp_vehiculo_cycsa');
        const selTec = document.getElementById('select_tecnico_muestreo');
        const selVeh = document.getElementById('select_vehiculo_muestreo');

        if (tipo === 'cliente') {
            grpTec.style.display = 'none';
            grpVeh.style.display = 'none';
            grpCli.style.display = 'block';
            selTec.removeAttribute('required');
            selVeh.removeAttribute('required');
        } else {
            grpTec.style.display = 'block';
            grpVeh.style.display = 'block';
            grpCli.style.display = 'none';
            selTec.setAttribute('required', 'required');
            selVeh.setAttribute('required', 'required');
        }
    }

    function prepararSubmitMuestreo(e) {
        const checkedRadio = document.querySelector('input[name="modalidad_muestreo"]:checked');
        const mod = checkedRadio ? checkedRadio.value : 'tecnico';
        const finalInput = document.getElementById('final_tecnico_muestreo');

        if (mod === 'cliente') {
            const nomCli = document.getElementById('input_cliente_entrega').value.trim();
            finalInput.value = nomCli !== '' ? ('Cliente: ' + nomCli) : 'Cliente (Entregado en Recepción)';
        } else {
            finalInput.value = document.getElementById('select_tecnico_muestreo').value;
        }
    }

    function intentarGenerarHojaLaboratorio(idOS, codigoOS, tieneTecnico) {
        if (!tieneTecnico) {
            alert('Debe asignar primero un técnico muestreador de visita antes de continuar.');
            abrirModalMuestreo(idOS, codigoOS);
            return;
        }
        alert('Debe capturar primero la matriz técnica de los productos cotizados para habilitar la Hoja de Laboratorio.');
        abrirMatricesOS(idOS);
    }

    function intentarRellenarMatrizProducto(tieneHoja, tieneAceptacionLab, idOS, codigoOS, idDetalle) {
        if (!tieneHoja) {
            alert('Debe generar primero la Hoja de Servicio RT-FM-13 para la orden ' + codigoOS + '.');
            window.location.href = '/Cycsa/publico/ordenes-servicio?id_os=' + idOS;
            return;
        }
        if (!tieneAceptacionLab) {
            alert('Las muestras físicas de la orden ' + codigoOS + ' aún están pendientes de custodia y recepción formal en el módulo de Laboratorio.');
            return;
        }
        window.location.href = '/Cycsa/publico/operaciones/captura-matriz?id_detalle=' + idDetalle;
    }

    let currentDrawerOSId = null;
    let currentDetailCardNode = null;
    let currentDetailCardParent = null;
    let bsOffcanvasDrawer = null;

    function switchDrawerCardTab(btn, targetPaneId) {
        const card = btn.closest('.detalle-os-card');
        if (!card) return;
        card.querySelectorAll('.btn-drawer-tab').forEach(b => b.classList.remove('active'));
        btn.classList.add('active');

        card.querySelectorAll('.drawer-card-tab-pane').forEach(p => {
            p.style.display = 'none';
        });
        const target = card.querySelector('#' + targetPaneId);
        if (target) {
            target.style.display = 'block';
        }
    }

    function irATabEnsayosCard(idOS) {
        const card = document.querySelector('#lims-drawer-body .detalle-os-card');
        if (!card) return;
        const btn = card.querySelectorAll('.btn-drawer-tab')[0];
        if (btn) switchDrawerCardTab(btn, 'tab-ensayos-' + idOS);
    }

    function abrirDetalleOS(idOS, btn) {
        abrirLimsDrawer(idOS);
    }

    function abrirMatricesOS(idOS) {
        abrirLimsDrawer(idOS, 'tab-ensayos-' + idOS);
    }

    function abrirLimsDrawer(idOS, tabInicial = null) {
        if (currentDrawerOSId !== null && currentDrawerOSId !== idOS && currentDetailCardNode && currentDetailCardParent) {
            currentDetailCardParent.appendChild(currentDetailCardNode);
            currentDetailCardNode = null;
            currentDetailCardParent = null;
        }

        if (currentDrawerOSId === idOS && currentDetailCardNode) {
            const tabBtns = currentDetailCardNode.querySelectorAll('.btn-drawer-tab');
            if (tabInicial && tabInicial.startsWith('tab-recepcion-')) {
                if (tabBtns[1]) switchDrawerCardTab(tabBtns[1], tabInicial);
            } else if (tabInicial && tabInicial.startsWith('tab-informes-')) {
                if (tabBtns[2]) switchDrawerCardTab(tabBtns[2], tabInicial);
            } else {
                if (tabBtns[0]) switchDrawerCardTab(tabBtns[0], 'tab-ensayos-' + idOS);
            }
            return;
        }

        const row = document.getElementById('os-row-' + idOS);
        const detailRow = document.getElementById('os-detail-' + idOS);
        if (!detailRow) return;

        const card = detailRow.querySelector('.detalle-os-card');
        if (!card) return;

        document.querySelectorAll('#tabla-lims-os tbody tr.is-selected').forEach(r => r.classList.remove('is-selected'));
        if (row) row.classList.add('is-selected');

        currentDetailCardNode = card;
        currentDetailCardParent = card.parentNode;
        currentDrawerOSId = idOS;

        const drawerBody = document.getElementById('lims-drawer-body');
        const drawerTitle = document.getElementById('lims-drawer-title');
        const drawerSubtitle = document.getElementById('lims-drawer-subtitle');
        const btnFull = document.getElementById('lims-drawer-btn-full');

        const codigoOS = row ? (row.querySelector('.lims-os-code')?.innerText.trim() || ('OS #' + idOS)) : ('OS #' + idOS);
        const metaLine = row ? (row.getAttribute('data-panel-meta') || '') : '';

        if (drawerTitle) drawerTitle.textContent = codigoOS;
        if (drawerSubtitle) drawerSubtitle.textContent = metaLine;
        if (btnFull) btnFull.href = '/Cycsa/publico/operaciones/ver?id_os=' + idOS;

        drawerBody.innerHTML = '';
        drawerBody.appendChild(card);

        const tabBtns = card.querySelectorAll('.btn-drawer-tab');
        if (tabInicial && tabInicial.startsWith('tab-recepcion-')) {
            if (tabBtns[1]) switchDrawerCardTab(tabBtns[1], tabInicial);
        } else if (tabInicial && tabInicial.startsWith('tab-informes-')) {
            if (tabBtns[2]) switchDrawerCardTab(tabBtns[2], tabInicial);
        } else {
            if (tabBtns[0]) switchDrawerCardTab(tabBtns[0], 'tab-ensayos-' + idOS);
        }

        const drawerEl = document.getElementById('lims-drawer');
        if (typeof bootstrap !== 'undefined' && bootstrap.Offcanvas && drawerEl) {
            bsOffcanvasDrawer = bootstrap.Offcanvas.getOrCreateInstance(drawerEl);
            bsOffcanvasDrawer.show();
        } else if (drawerEl) {
            drawerEl.classList.add('show');
            drawerEl.style.visibility = 'visible';
            const overlay = document.getElementById('lims-drawer-overlay');
            if (overlay) overlay.classList.add('active');
        }
    }

    function cerrarLimsDrawer(fromOffcanvasEvent = false) {
        if (currentDetailCardNode && currentDetailCardParent) {
            currentDetailCardParent.appendChild(currentDetailCardNode);
            currentDetailCardNode = null;
            currentDetailCardParent = null;
        }
        currentDrawerOSId = null;

        document.querySelectorAll('#tabla-lims-os tbody tr.is-selected').forEach(r => r.classList.remove('is-selected'));

        const drawerEl = document.getElementById('lims-drawer');
        if (!fromOffcanvasEvent && bsOffcanvasDrawer && drawerEl && drawerEl.classList.contains('show')) {
            bsOffcanvasDrawer.hide();
        } else if (drawerEl && (!bsOffcanvasDrawer)) {
            drawerEl.classList.remove('show');
            drawerEl.style.visibility = '';
        }
        const overlay = document.getElementById('lims-drawer-overlay');
        if (overlay) overlay.classList.remove('active');
    }

    function abrirModalCerrarOS(idOS, codigoOS) {
        document.getElementById('cierre_id_os').value = idOS;
        document.getElementById('cierre_codigo_os').innerText = codigoOS;
        const mod = document.getElementById('modalConfirmarCierreOS');
        if (mod) mod.style.display = 'block';
    }

    function cerrarModalCierreOS() {
        const mod = document.getElementById('modalConfirmarCierreOS');
        if (mod) mod.style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', function() {
        const drawerEl = document.getElementById('lims-drawer');
        if (drawerEl) {
            drawerEl.addEventListener('hidden.bs.offcanvas', function() {
                cerrarLimsDrawer(true);
            });
        }

        if (typeof bootstrap !== 'undefined' && bootstrap.Dropdown) {
            document.querySelectorAll('#tabla-lims-os .dropdown-toggle-no-caret').forEach(function(btn) {
                try {
                    new bootstrap.Dropdown(btn, {
                        boundary: 'viewport',
                        popperConfig: function(defaultBsPopperConfig) {
                            return Object.assign({}, defaultBsPopperConfig, { strategy: 'fixed' });
                        }
                    });
                } catch (e) {}
            });
        }

        document.querySelectorAll('tr[id^="os-row-"]').forEach(row => {
            row.addEventListener('click', function(e) {
                if (e.target.closest('button') || e.target.closest('a') || e.target.closest('input') || e.target.closest('textarea') || e.target.closest('.dropdown-menu')) {
                    return;
                }
                const id = this.id.replace('os-row-', '');
                abrirLimsDrawer(id);
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                cerrarLimsDrawer();
                cerrarModalCierreOS();
            }
        });
    });

    const modMues = document.getElementById('modalMuestreo');
    function abrirModalMuestreo(id, code) {
        document.getElementById('mues_id_os').value = id;
        document.getElementById('mues_codigo_os').innerText = code;
        if (modMues) modMues.style.display = 'block';
    }
    function cerrarModalMuestreo() {
        if (modMues) modMues.style.display = 'none';
    }

    const modField = document.getElementById('modalHojaCampo');
    function abrirModalHojaCampo(id, code, tecnico = '', autoCodigo = '', horas = 24) {
        document.getElementById('field_id_os').value = id;
        document.getElementById('field_codigo_os').innerText = code;
        if (autoCodigo) document.getElementById('field_hoja_campo_codigo').value = autoCodigo;
        if (tecnico) document.getElementById('field_hoja_campo_operador').value = tecnico;
        if (horas !== undefined && horas !== null) document.getElementById('field_horas_espera').value = horas;
        if (modField) modField.style.display = 'block';
    }
    function cerrarModalHojaCampo() {
        if (modField) modField.style.display = 'none';
    }

    const modQC = document.getElementById('modalRevisionResultados');
    function abrirModalRevisionResultados(id, code) {
        document.getElementById('qc_id_os').value = id;
        document.getElementById('qc_codigo_os').innerText = code;
        document.getElementById('qc-group-obs').style.display = 'none';
        document.getElementById('qc_motivo').value = '';
        document.getElementById('btn-qc-aprobar').style.display = 'inline-flex';
        if (modQC) modQC.style.display = 'block';
    }
    function cerrarModalRevisionResultados() {
        if (modQC) modQC.style.display = 'none';
    }
    function ejecutarQCAprobar() {
        document.getElementById('qc_decision').value = 'Aprobar';
        modQC.querySelector('form').submit();
    }
    function ejecutarQCObservar() {
        const group = document.getElementById('qc-group-obs');
        if (group.style.display === 'none') {
            group.style.display = 'block';
            document.getElementById('qc_motivo').focus();
            document.getElementById('btn-qc-aprobar').style.display = 'none';
        } else {
            const motivo = document.getElementById('qc_motivo').value.trim();
            if (motivo === '') {
                alert('Debe detallar los resultados observados para devolver el expediente.');
                document.getElementById('qc_motivo').focus();
                return;
            }
            document.getElementById('qc_decision').value = 'Rechazar';
            modQC.querySelector('form').submit();
        }
    }

    const searchInput = document.getElementById('lims-search');
    if (searchInput) {
        searchInput.addEventListener('input', function(e) {
            filtrarTabla(e.target.value);
        });
    }

    function filtrarTabla(query) {
        const val = query.toLowerCase().trim();
        const rows = document.querySelectorAll('#tabla-lims-os tbody tr:not(.detalle-os-row)');
        rows.forEach(row => {
            const text = row.textContent.toLowerCase();
            row.style.display = text.includes(val) ? '' : 'none';
        });
    }

    window.addEventListener('click', (e) => {
        if (e.target === modMues) cerrarModalMuestreo();
        if (e.target === modField) cerrarModalHojaCampo();
        if (e.target === modQC) cerrarModalRevisionResultados();
        if (e.target === document.getElementById('modalMatrizEnsayosOS')) cerrarModalMatrizEnsayosOS();
        if (e.target === document.getElementById('modalVerObservacionDevolucion')) cerrarModalVerObservacionDevolucion();
        if (e.target === document.getElementById('modalAprobarMatrizIndex')) cerrarModalAprobarEnIndex();
        if (e.target === document.getElementById('modalDevolverMatrizIndex')) cerrarModalDevolverEnIndex();
        if (e.target === document.getElementById('modalCorreccionAdminIndex')) cerrarModalCorreccionAdminIndex();
    });

    function abrirModalMatrizEnsayosOS(idOS, code) {
        document.getElementById('m_matriz_codigo_os').innerText = code;
        document.getElementById('m_matriz_loading').style.display = 'block';
        document.getElementById('m_matriz_contenido').style.display = 'none';
        document.getElementById('modalMatrizEnsayosOS').style.display = 'block';

        fetch('/Cycsa/publico/operaciones/obtener-matriz-os?id_os=' + idOS)
            .then(res => res.json())
            .then(data => {
                document.getElementById('m_matriz_loading').style.display = 'none';
                if (data.status !== 'success' || !data.items) {
                    alert(data.message || 'Error al obtener matriz de la O/S');
                    return;
                }

                const tbody = document.getElementById('tbody-matriz-items-os');
                tbody.innerHTML = '';
                data.items = (data.items || []).filter(it => it.permitido !== 0 && it.permitido !== '0' && it.permitido !== false);

                if (data.items.length === 0) {
                    tbody.innerHTML = '<tr><td colspan="5" class="text-center py-4 cy-meta">No hay ensayos registrados para esta orden.</td></tr>';
                } else {
                    data.items.forEach(it => {
                        const tr = document.createElement('tr');
                        const tieneResultados = !!(it.resultados_json && it.resultados_json !== '[]');
                        const btnHtml = `<a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=${it.id}" class="btn btn-outline-secondary btn-sm">${tieneResultados ? 'Editar matriz' : 'Capturar'}</a>`;
                        tr.innerHTML = `
                            <td class="fw-medium">${it.descripcion_ensayo || ''}</td>
                            <td class="cy-meta">${it.norma_astm || 'N/A'}</td>
                            <td>${it.formato_nombre || 'Matriz técnica'}</td>
                            <td class="text-center">${parseInt(it.cantidad || 1)}</td>
                            <td class="text-end">${btnHtml}</td>
                        `;
                        tbody.appendChild(tr);
                    });
                }

                document.getElementById('m_matriz_contenido').style.display = 'block';
            })
            .catch(() => {
                alert('Error al conectar con el servidor.');
                cerrarModalMatrizEnsayosOS();
            });
    }

    function cerrarModalMatrizEnsayosOS() {
        document.getElementById('modalMatrizEnsayosOS').style.display = 'none';
    }

    let columnasDirectas = [];
    function abrirModalResultadosDirecto(idDetalle, nombreEnsayo) {
        document.getElementById('m_res_id_detalle').value = idDetalle;
        document.getElementById('m_res_titulo_ensayo').innerText = 'Capturar matriz: ' + nombreEnsayo;
        columnasDirectas = ['Código laboratorio', 'Nombre muestra', 'Lectura / Resultado'];
        document.getElementById('tabla-directa-header').innerHTML = '<tr>' + columnasDirectas.map(c => '<th>' + c + '</th>').join('') + '</tr>';
        document.getElementById('tabla-directa-body').innerHTML = '';
        agregarFilaDirecta();
        document.getElementById('modalResultadosDirecto').style.display = 'block';
    }
    function agregarFilaDirecta() {
        const bodyContainer = document.getElementById('tabla-directa-body');
        const tr = document.createElement('tr');
        columnasDirectas.forEach(col => {
            const td = document.createElement('td');
            const input = document.createElement('input');
            input.type = 'text';
            input.className = 'form-control';
            input.dataset.col = col;
            td.appendChild(input);
            tr.appendChild(td);
        });
        bodyContainer.appendChild(tr);
    }
    function cerrarModalResultadosDirecto() {
        document.getElementById('modalResultadosDirecto').style.display = 'none';
    }
    function guardarFormularioMatrizDirecta(e) {
        e.preventDefault();
        const rows = document.getElementById('tabla-directa-body').querySelectorAll('tr');
        const data = [];
        rows.forEach(tr => {
            const obj = {};
            let hasVal = false;
            tr.querySelectorAll('input').forEach(inp => {
                obj[inp.dataset.col] = inp.value.trim();
                if (inp.value.trim() !== '') hasVal = true;
            });
            if (hasVal) data.push(obj);
        });
        document.getElementById('m_res_json_input').value = JSON.stringify(data);
        fetch('/Cycsa/publico/operaciones/guardar-matriz-resultados', {
            method: 'POST',
            body: new FormData(document.getElementById('form-matriz-directa'))
        }).then(r => r.json()).then(res => {
            if (res.status === 'success') {
                cerrarModalResultadosDirecto();
                location.reload();
            } else {
                alert(res.message || 'Error al guardar la matriz.');
            }
        }).catch(() => alert('Error al guardar datos en el servidor.'));
        return false;
    }

    function abrirModalEnviarMatriz(idDetalle, nombreEnsayo, clienteNombre, clienteEmail, codigoOS) {
        document.getElementById('envio_id_detalle').value = idDetalle;
        document.getElementById('envio_txt_ensayo').innerText = nombreEnsayo;
        document.getElementById('envio_txt_os').innerText = codigoOS;
        document.getElementById('envio_txt_cliente').innerText = clienteNombre;
        document.getElementById('envio_destinatario').value = clienteEmail || '';
        document.getElementById('envio_asunto').value = 'Informe Oficial de Ensayo - ' + codigoOS + ' - ' + nombreEnsayo + ' - CYCSA';
        document.getElementById('btn-previsualizar-pdf').href = '/Cycsa/publico/operaciones/descargar-matriz-pdf?id_detalle=' + idDetalle;
        const modal = document.getElementById('modalEnviarInformeCliente');
        if (modal) modal.style.display = 'block';
    }
    function cerrarModalEnviarMatriz() {
        const modal = document.getElementById('modalEnviarInformeCliente');
        if (modal) modal.style.display = 'none';
    }
    function prepararEnvioMatriz(form) {
        const btn = document.getElementById('btn-submit-envio');
        if (btn) {
            btn.disabled = true;
            btn.textContent = 'Enviando...';
        }
        return true;
    }
</script>

<script>
    function verObservacionDevolucion(idDetalle, nombreEnsayo, motivo, supervisor, fecha) {
        document.getElementById('ver_obs_ensayo').innerText = nombreEnsayo;
        document.getElementById('ver_obs_motivo').innerText = motivo || 'Sin observaciones detalladas.';
        document.getElementById('ver_obs_supervisor').innerText = supervisor || 'Supervisor';
        document.getElementById('ver_obs_fecha').innerText = fecha || 'N/A';
        document.getElementById('ver_obs_btn_editar').href = '/Cycsa/publico/operaciones/captura-matriz?id_detalle=' + idDetalle;
        document.getElementById('modalVerObservacionDevolucion').style.display = 'block';
    }

    function cerrarModalVerObservacionDevolucion() {
        document.getElementById('modalVerObservacionDevolucion').style.display = 'none';
    }

    function abrirModalAprobarEnIndex(idDetalle, nombreEnsayo) {
        document.getElementById('aprob_index_id_detalle').value = idDetalle;
        document.getElementById('aprob_index_ensayo').innerText = nombreEnsayo;
        document.getElementById('modalAprobarMatrizIndex').style.display = 'block';
    }

    function cerrarModalAprobarEnIndex() {
        document.getElementById('modalAprobarMatrizIndex').style.display = 'none';
    }

    function abrirModalDevolverEnIndex(idDetalle, nombreEnsayo) {
        document.getElementById('dev_index_id_detalle').value = idDetalle;
        document.getElementById('dev_index_ensayo').innerText = nombreEnsayo;
        document.getElementById('modalDevolverMatrizIndex').style.display = 'block';
    }

    function cerrarModalDevolverEnIndex() {
        document.getElementById('modalDevolverMatrizIndex').style.display = 'none';
    }

    function abrirModalCorreccionAdminIndex(idDetalle, nombreEnsayo, cliente, proyecto, ubicacion) {
        document.getElementById('corr_index_id_detalle').value = idDetalle;
        document.getElementById('corr_index_ensayo_nombre').innerText = nombreEnsayo || '';
        document.getElementById('corr_index_motivo').value = '';
        document.getElementById('corr_index_cliente').value = cliente || '';
        document.getElementById('corr_index_proyecto').value = proyecto || '';
        document.getElementById('corr_index_ubicacion').value = ubicacion || '';
        document.getElementById('modalCorreccionAdminIndex').style.display = 'block';
    }

    function cerrarModalCorreccionAdminIndex() {
        document.getElementById('modalCorreccionAdminIndex').style.display = 'none';
    }
</script>

<?php
$bitacora_modulo_nombre = 'Operaciones LIMS';
include dirname(__DIR__, 3) . '/Views/parciales/bitacora_modulo.php';
?>
