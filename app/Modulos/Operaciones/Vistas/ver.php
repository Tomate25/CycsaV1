<?php
// Vista de página completa para una Operación (O/S) — Guía de Diseño Sobrio CYCSA
$rolSesion = (int)($_SESSION['usuario_rol'] ?? 0);
$esSupervisor = in_array($rolSesion, [1, 2, 3], true)
    || (function_exists('tienePermiso') && (tienePermiso('operaciones', 'aprobar') || tienePermiso('control_calidad', 'aprobar')));

$o = $o ?? ($orden ?? []);
$o['id'] = (int)($o['id'] ?? 0);
$o['estado'] = (string)($o['estado'] ?? '');
$o['codigo_os'] = (string)($o['codigo_os'] ?? '');
$o['items'] = array_values(array_filter($o['items'] ?? [], static function ($it): bool {
    return !is_array($it) || !array_key_exists('permitido', $it) || (int)$it['permitido'] === 1;
}));

$informes_unificados = $informes_unificados ?? [];
$consolidado_lab = $consolidado_lab ?? [];
$historial_versiones_lab = $historial_versiones_lab ?? [];
$tecnicos = $tecnicos ?? [];
$vehiculos = $vehiculos ?? [];
$csrfToken = (string)($_SESSION['csrf_token'] ?? '');

if (!function_exists('cycsaCalcularStepperOperacion')) {
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

$cliInfNom = (string)($o['cliente_informe_nombre'] ?? ($o['cliente_nombre'] ?? 'Cliente General'));
$cliFactNom = (string)($o['cliente_factura_nombre'] ?? ($o['cliente_nombre'] ?? $cliInfNom));
$condComAct = strtolower(trim((string)($o['condicion_comercial_activa'] ?? $o['condicion_comercial'] ?? $o['condicion_pago'] ?? $o['forma_pago'] ?? 'contado')));
$esCreditoFila = ($condComAct === 'credito');
$proyectoNom = trim((string)($o['nombre_proyecto'] ?? 'Sin proyecto'));
$cotCodigo = trim((string)($o['cot_codigo'] ?? ''));

$totalItemsOS = count($o['items']);
$matricesLlenadas = 0;
$matricesAprobadas = 0;
$matricesEnRevision = 0;
$matricesDevueltas = 0;
$primerItemId = null;
$primerItemPendienteId = null;
$primerItemRevisionId = null;

foreach ($o['items'] as $it) {
    $revIt = $it['revision_info'] ?? obtenerEstadoRevisionMatriz($it['resultados_json'] ?? null);
    $tieneResIt = !empty($revIt['tiene_resultados']) || (!empty($it['resultados_json']) && $it['resultados_json'] !== '[]');
    if ($tieneResIt) {
        $matricesLlenadas++;
    }
    if (($revIt['estado'] ?? '') === 'aprobada') {
        $matricesAprobadas++;
    } elseif (($revIt['estado'] ?? '') === 'en_revision') {
        $matricesEnRevision++;
        if ($primerItemRevisionId === null && !empty($it['id'])) {
            $primerItemRevisionId = (int)$it['id'];
        }
    } elseif (($revIt['estado'] ?? '') === 'devuelta') {
        $matricesDevueltas++;
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

$tieneTecnico = !empty($o['tecnico_muestreo']);
$hojasOS = !empty($o['hojas_solicitud']) ? $o['hojas_solicitud'] : (!empty($o['hoja_solicitud']) ? [$o['hoja_solicitud']] : []);
$tieneHojaServicio = !empty($hojasOS);
$hojaPrincipalVer = !empty($o['hoja_solicitud']) ? $o['hoja_solicitud'] : ($hojasOS[0] ?? null);
$idHojaVer = (int)($hojaPrincipalVer['id'] ?? 0);
$requiereMuestreo = !empty($o['requiere_muestreo']);
$esSoloCompactacion = esOrdenSoloCompactacion($o['items']);
$primerItemVerId = $primerItemId ?? 0;

$etapaFlujoVer = !empty($o['etapa_flujo_os'])
    ? (string)$o['etapa_flujo_os']
    : (!empty($hojaPrincipalVer['etapa_flujo'])
        ? (string)$hojaPrincipalVer['etapa_flujo']
        : (($totalItemsOS > 0 && $matricesAprobadas >= $totalItemsOS) ? 'informe' : ($tieneHojaServicio ? 'recepcion_borrador' : 'sin_hoja')));

$oCalc = $o;
$oCalc['total_ensayos'] = $totalItemsOS;
$oCalc['ensayos_aprobados'] = $o['ensayos_aprobados'] ?? $matricesAprobadas;
$oCalc['ensayos_en_revision'] = $o['ensayos_en_revision'] ?? $matricesEnRevision;
$oCalc['ensayos_devueltos'] = $o['ensayos_devueltos'] ?? $matricesDevueltas;
$oCalc['ensayos_con_resultados'] = $o['ensayos_con_resultados'] ?? $matricesLlenadas;
$oCalc['etapa_flujo_os'] = $etapaFlujoVer;
$oCalc['informes_emitidos'] = count($informes_unificados);

$stepperPasos = (!empty($o['stepper_pasos']) && is_array($o['stepper_pasos']))
    ? $o['stepper_pasos']
    : cycsaCalcularStepperOperacion($oCalc);

$etapaPill = cycsaResolverEtapaPill($oCalc);

$panelMetaPartes = array_filter([
    $cliInfNom,
    $proyectoNom,
    $cotCodigo,
    ucfirst($condComAct),
], static fn(string $v): bool => $v !== '');
$panelMetaLine = implode(' · ', $panelMetaPartes);

// Siguiente acción determinística para la caja .cy-next (único .btn-primary)
if (!empty($o['puede_cerrar']) && !in_array((string)($o['estado'] ?? ''), ['Finalizado', 'Archivado', 'Cerrado'], true)) {
    $sigAccionTipo = 'button';
    $sigAccionTexto = 'Cerrar y archivar orden';
    $sigAccionDetalle = '100% técnico y comercial cumplido.';
    $sigAccionOnclick = 'abrirModalCerrarOS(' . (int)$o['id'] . ", '" . htmlspecialchars(addslashes((string)($o['codigo_os'] ?? '')), ENT_QUOTES, 'UTF-8') . "')";
    $sigAccionHref = '#';
} elseif (!$tieneHojaServicio && !$esSoloCompactacion) {
    $sigAccionTipo = 'link';
    $sigAccionTexto = 'Registrar RT-FM-13';
    $sigAccionDetalle = 'Registre la hoja de solicitud de servicio para habilitar la recepción y las matrices.';
    $sigAccionHref = '/Cycsa/publico/ordenes-servicio?id_os=' . (int)$o['id'];
    $sigAccionOnclick = '';
} elseif ($matricesEnRevision > 0 && $esSupervisor && ($primerItemRevisionId ?? $primerItemVerId) > 0) {
    $targetRev = $primerItemRevisionId ?? $primerItemVerId;
    $sigAccionTipo = 'link';
    $sigAccionTexto = 'Revisar matriz';
    $sigAccionDetalle = $matricesAprobadas . ' de ' . $totalItemsOS . ' matrices aprobadas · ' . $matricesEnRevision . ' pendiente de revisión.';
    $sigAccionHref = '/Cycsa/publico/operaciones/captura-matriz?id_detalle=' . $targetRev;
    $sigAccionOnclick = '';
} elseif ($matricesAprobadas < $totalItemsOS && ($primerItemPendienteId ?? $primerItemVerId) > 0) {
    $targetCap = $primerItemPendienteId ?? $primerItemVerId;
    $sigAccionTipo = 'link';
    $sigAccionTexto = ($idHojaVer > 0 && !$esSoloCompactacion) ? 'Capturar en laboratorio' : 'Capturar matriz';
    $sigAccionDetalle = $matricesLlenadas . ' de ' . $totalItemsOS . ' matrices con resultados · ' . $matricesAprobadas . ' aprobadas.';
    $sigAccionHref = ($idHojaVer > 0 && !$esSoloCompactacion)
        ? ('/Cycsa/publico/laboratorio/ensayos-hoja?id_hoja=' . $idHojaVer)
        : ('/Cycsa/publico/operaciones/captura-matriz?id_detalle=' . $targetCap);
    $sigAccionOnclick = '';
} elseif ($primerItemVerId > 0) {
    $sigAccionTipo = 'link';
    $sigAccionTexto = 'Ver informe PDF';
    $sigAccionDetalle = 'Todas las matrices aprobadas (' . $matricesAprobadas . ' de ' . $totalItemsOS . ').';
    $sigAccionHref = '/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=' . $primerItemVerId;
    $sigAccionOnclick = '';
} else {
    $sigAccionTipo = 'link';
    $sigAccionTexto = 'Ver recepción';
    $sigAccionDetalle = $panelMetaLine;
    $sigAccionHref = '/Cycsa/publico/ordenes-servicio?id_os=' . (int)$o['id'];
    $sigAccionOnclick = '';
}
?>
<link rel="stylesheet" href="/Cycsa/publico/css/cycsa-sobrio.css">
<style>
    .cy-ver-wrap {
        max-width: 1120px;
        margin: 0 auto;
    }
    .cy-ver-card-body {
        padding: 24px 28px;
    }
    .dropdown-toggle-no-caret::after {
        display: none !important;
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

<div class="container-fluid px-4 py-4 cy-ver-wrap">
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

    <div class="mb-3">
        <a href="/Cycsa/publico/operaciones" class="cy-action-link">Volver a Operaciones</a>
    </div>

    <div class="card">
        <div class="cy-ver-card-body">
            <!-- Encabezado de la orden -->
            <div class="d-flex flex-wrap justify-content-between align-items-start gap-3 pb-3 mb-4 border-bottom">
                <div>
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <h1 class="cy-order-code"><?= htmlspecialchars((string)($o['codigo_os'] ?? ''), ENT_QUOTES, 'UTF-8') ?></h1>
                        <span class="cy-pill <?= htmlspecialchars($etapaPill['clase'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($etapaPill['texto'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>
                    <div class="cy-panel-meta-line">
                        <?= htmlspecialchars($panelMetaLine, ENT_QUOTES, 'UTF-8') ?>
                    </div>
                </div>

                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <?php if (!empty($hojasOS)): ?>
                        <a href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= (int)$o['id'] ?>&id_hoja=<?= (int)$hojasOS[0]['id'] ?>"
                           target="_blank"
                           class="btn btn-outline-secondary btn-sm">RT-FM-13</a>
                        <a href="/Cycsa/publico/laboratorio/imprimir-solicitud?id_hoja=<?= (int)$hojasOS[0]['id'] ?>"
                           target="_blank"
                           class="btn btn-outline-secondary btn-sm">Hoja RT-FM-60</a>
                    <?php else: ?>
                        <a href="/Cycsa/publico/ordenes-servicio?id_os=<?= (int)$o['id'] ?>" class="btn btn-outline-secondary btn-sm">Registrar RT-FM-13</a>
                    <?php endif; ?>

                    <div class="dropdown">
                        <button class="btn-more dropdown-toggle-no-caret" type="button" data-bs-toggle="dropdown" aria-expanded="false" aria-label="Más acciones">⋯</button>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <?php foreach ($hojasOS as $hOS): ?>
                                <li><a class="dropdown-item" href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= (int)$o['id'] ?>&id_hoja=<?= (int)$hOS['id'] ?>" target="_blank">Imprimir RT-FM-13 (<?= htmlspecialchars((string)($hOS['numero_registro'] ?? ('#' . $hOS['id'])), ENT_QUOTES, 'UTF-8') ?>)</a></li>
                                <li><a class="dropdown-item" href="/Cycsa/publico/laboratorio/imprimir-solicitud?id_hoja=<?= (int)$hOS['id'] ?>" target="_blank">Imprimir Hoja RT-FM-60 (<?= htmlspecialchars((string)($hOS['numero_registro'] ?? ('#' . $hOS['id'])), ENT_QUOTES, 'UTF-8') ?>)</a></li>
                            <?php endforeach; ?>
                            <?php if ($esSupervisor && !in_array((string)($o['estado'] ?? ''), ['Estado 7: Revision Resultados', 'Finalizado', 'Archivado', 'Cerrado'], true)): ?>
                                <li><button type="button" class="dropdown-item" onclick="abrirModalRevisionResultados(<?= (int)$o['id'] ?>, '<?= htmlspecialchars(addslashes((string)($o['codigo_os'] ?? '')), ENT_QUOTES, 'UTF-8') ?>')">Aprobar revisión técnica</button></li>
                            <?php endif; ?>
                            <?php if (!empty($o['puede_cerrar'])): ?>
                                <li><hr class="dropdown-divider"></li>
                                <li><button type="button" class="dropdown-item" onclick="abrirModalCerrarOS(<?= (int)$o['id'] ?>, '<?= htmlspecialchars(addslashes((string)($o['codigo_os'] ?? '')), ENT_QUOTES, 'UTF-8') ?>')">Cerrar y archivar orden</button></li>
                            <?php endif; ?>
                        </ul>
                    </div>
                </div>
            </div>

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

            <!-- Caja "Siguiente acción" (.cy-next) con el único botón .btn-primary -->
            <div class="cy-next">
                <div>
                    <div class="fw-semibold">Siguiente acción: <?= htmlspecialchars($sigAccionTexto, ENT_QUOTES, 'UTF-8') ?></div>
                    <div class="cy-meta"><?= htmlspecialchars($sigAccionDetalle, ENT_QUOTES, 'UTF-8') ?></div>
                </div>
                <?php if ($sigAccionTipo === 'button'): ?>
                    <button type="button" class="btn btn-primary" onclick="<?= $sigAccionOnclick ?>"><?= htmlspecialchars($sigAccionTexto, ENT_QUOTES, 'UTF-8') ?></button>
                <?php else: ?>
                    <a href="<?= htmlspecialchars($sigAccionHref, ENT_QUOTES, 'UTF-8') ?>" class="btn btn-primary"><?= htmlspecialchars($sigAccionTexto, ENT_QUOTES, 'UTF-8') ?></a>
                <?php endif; ?>
            </div>

            <!-- Tabs subrayadas simples (.nav-tabs) sin íconos -->
            <ul class="nav nav-tabs mb-3" role="tablist">
                <li class="nav-item" role="presentation">
                    <button type="button"
                            class="nav-link active btn-tab-full"
                            role="tab"
                            onclick="cambiarTabFull(this, 'tab-full-ensayos')">
                        Ensayos <span class="cy-tab-count">(<?= $totalItemsOS ?>)</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button"
                            class="nav-link btn-tab-full"
                            role="tab"
                            onclick="cambiarTabFull(this, 'tab-full-recepcion')">
                        Recepción <span class="cy-tab-count">(<?= count($hojasOS) ?>)</span>
                    </button>
                </li>
                <li class="nav-item" role="presentation">
                    <button type="button"
                            class="nav-link btn-tab-full"
                            role="tab"
                            onclick="cambiarTabFull(this, 'tab-full-informes')">
                        Informes y Versiones <span class="cy-tab-count">(<?= count($informes_unificados) ?>)</span>
                    </button>
                </li>
            </ul>

            <div class="lims-tab-content-wrapper">
                <!-- PESTAÑA 1: ENSAYOS -->
                <div id="tab-full-ensayos" class="tab-pane-full" style="display: block;">
                    <?php foreach ($o['items'] as $it):
                        $revIt = $it['revision_info'] ?? obtenerEstadoRevisionMatriz($it['resultados_json'] ?? null);
                        $tieneResIt = !empty($revIt['tiene_resultados']);
                        $estadoRevIt = (string)($revIt['estado'] ?? 'pendiente');
                        $pillEns = cycsaResolverPillEnsayo($revIt);
                        [$avanceX, $avanceY] = cycsaCalcularAvanceEnsayo($it, $revIt);
                        $esCompactacion = esItemCompactacion($it);
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
                                <?php if ($idHojaVer > 0): ?>
                                    <li><a class="dropdown-item" href="/Cycsa/publico/laboratorio/imprimir-solicitud?id_hoja=<?= $idHojaVer ?>" target="_blank">Imprimir Hoja RT-FM-60</a></li>
                                    <li><a class="dropdown-item" href="/Cycsa/publico/hojas-servicio/descargar?id_os=<?= (int)$o['id'] ?>&id_hoja=<?= $idHojaVer ?>" target="_blank">Imprimir RT-FM-13</a></li>
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

                    <?php
                    if (!empty($_GET['legacy_actions']) && file_exists(__DIR__ . '/parciales/acciones_ensayo.php')) {
                        include __DIR__ . '/parciales/acciones_ensayo.php';
                    }
                    ?>

                    <?php if (empty($o['items'])): ?>
                        <div class="cy-note">No hay ensayos habilitados para esta orden de servicio.</div>
                    <?php endif; ?>

                    <!-- Cierre bloqueado (.cy-note) al final, sin pestañas ni bandas grises anidadas -->
                    <div class="cy-note" id="tab-full-cierre">
                        <?php if (in_array((string)($o['estado'] ?? ''), ['Finalizado', 'Archivado', 'Cerrado'], true)): ?>
                            Orden cerrada y archivada en histórico LIMS.
                        <?php elseif (!empty($o['puede_cerrar'])): ?>
                            <div class="d-flex justify-content-between align-items-center gap-3 flex-wrap">
                                <span>100% técnico y comercial cumplido. Esta orden está lista para cierre y archivo definitivo.</span>
                                <button type="button"
                                        class="btn btn-outline-secondary btn-sm"
                                        onclick="abrirModalCerrarOS(<?= (int)$o['id'] ?>, '<?= htmlspecialchars(addslashes((string)($o['codigo_os'] ?? '')), ENT_QUOTES, 'UTF-8') ?>')">
                                    Cerrar y archivar orden
                                </button>
                            </div>
                        <?php else: ?>
                            El cierre de la orden se habilita cuando las <?= $totalItemsOS ?> matrices estén aprobadas y sus informes emitidos (actualmente <?= (int)$matricesAprobadas ?> de <?= $totalItemsOS ?> aprobadas<?= empty($o['comercial_100']) ? ($esCreditoFila ? ', pendiente CxC' : ', saldo pendiente C$ ' . number_format((float)($o['saldo_os'] ?? 0), 2)) : '' ?>).
                        <?php endif; ?>
                    </div>
                </div>

                <!-- PESTAÑA 2: RECEPCIÓN Y RESUMEN -->
                <div id="tab-full-recepcion" class="tab-pane-full" style="display: none;">
                    <div id="tab-full-resumen">
                        <?php if (empty($hojasOS)): ?>
                            <div class="cy-note mb-3">Aún no se ha registrado hoja RT-FM-13 para esta orden.</div>
                        <?php else: ?>
                            <?php foreach ($hojasOS as $hOS):
                                $hCompleta = !empty($hOS['recepcion_completa']) || in_array((string)($hOS['etapa_flujo'] ?? ''), ['recepcion_completa', 'en_ejecucion', 'tecnico_completo', 'matriz_en_revision', 'informe'], true);
                            ?>
                            <div class="cy-row">
                                <div class="flex-grow-1 min-w-0">
                                    <div class="cy-assay-name">Hoja RT-FM-13 · <?= htmlspecialchars((string)($hOS['numero_registro'] ?? ('#' . $hOS['id'])), ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="cy-assay-norm"><?= htmlspecialchars((string)($hOS['descripcion_ensayo'] ?? 'Solicitud general de análisis'), ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                                <span class="cy-pill <?= $hCompleta ? 'cy-pill-ok' : 'cy-pill-pend' ?>"><?= $hCompleta ? 'Completa' : 'En borrador' ?></span>
                                <a href="/Cycsa/publico/ordenes-servicio?id_os=<?= (int)$o['id'] ?>&id_hoja=<?= (int)$hOS['id'] ?>" class="btn btn-outline-secondary btn-sm">Ver datos</a>
                            </div>
                            <?php endforeach; ?>
                        <?php endif; ?>

                        <div class="cy-row">
                            <div class="flex-grow-1">
                                <div class="cy-assay-name">Identidad comercial</div>
                                <div class="cy-assay-norm">Informe: <?= htmlspecialchars($cliInfNom, ENT_QUOTES, 'UTF-8') ?> · Facturar a: <?= htmlspecialchars($cliFactNom, ENT_QUOTES, 'UTF-8') ?> · Condición: <?= ucfirst($condComAct) ?></div>
                            </div>
                        </div>

                        <div class="cy-row">
                            <div class="flex-grow-1">
                                <div class="cy-assay-name">Custodia y logística de muestras</div>
                                <div class="cy-assay-norm">
                                    <?php if ($esSoloCompactacion): ?>
                                        Ensayo in situ (Operaciones / Terreno)
                                    <?php elseif ($requiereMuestreo): ?>
                                        Muestreo en campo · Técnico asignado: <?= $tieneTecnico ? htmlspecialchars((string)$o['tecnico_muestreo'], ENT_QUOTES, 'UTF-8') : 'Sin asignar' ?>
                                    <?php else: ?>
                                        Entrega directa en Laboratorio Central
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="cy-row">
                            <div class="flex-grow-1">
                                <div class="cy-assay-name">Etapas del flujo operativo</div>
                                <div class="cy-assay-norm">Acceso directo a recepción, captura en línea y matriz de resultados</div>
                            </div>
                            <div class="d-flex gap-2 flex-wrap">
                                <a href="/Cycsa/publico/ordenes-servicio?id_os=<?= (int)$o['id'] ?><?= $idHojaVer > 0 ? '&id_hoja=' . $idHojaVer : '' ?>" class="btn btn-outline-secondary btn-sm">① Recepción</a>
                                <a href="<?= $idHojaVer > 0 ? '/Cycsa/publico/laboratorio/ensayos-hoja?id_hoja=' . $idHojaVer : '/Cycsa/publico/laboratorio' ?>" class="btn btn-outline-secondary btn-sm">② Ejecución Técnica (Laboratorio)</a>
                                <a href="<?= $primerItemVerId > 0 ? '/Cycsa/publico/operaciones/captura-matriz?id_detalle=' . $primerItemVerId : '#tab-full-ensayos' ?>" class="btn btn-outline-secondary btn-sm">③ Matriz RT-FM-22 (Auto-llenada / Solo lectura) y Aprobación</a>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- PESTAÑA 3: INFORMES Y VERSIONES -->
                <div id="tab-full-informes" class="tab-pane-full" style="display: none;">
                    <?php if (empty($informes_unificados)): ?>
                        <div class="cy-note">No se han emitido informes formales aún para esta orden de servicio.</div>
                    <?php else: ?>
                        <div class="card mb-4">
                            <div class="table-responsive">
                                <table class="table">
                                    <thead>
                                        <tr>
                                            <th>Código informe</th>
                                            <th>Tipo</th>
                                            <th>Versión</th>
                                            <th>Estado</th>
                                            <th>Fecha</th>
                                            <th>Integridad SHA-256</th>
                                            <th class="text-end">Documento</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($informes_unificados as $inf):
                                            $infEstado = (string)($inf['estado'] ?? 'pendiente');
                                            $infPill = $infEstado === 'aprobado' ? 'cy-pill-ok' : ($infEstado === 'anulado' ? 'cy-pill-danger' : 'cy-pill-warn');
                                        ?>
                                        <tr>
                                            <td class="fw-semibold"><?= htmlspecialchars((string)($inf['numero'] ?? ($inf['codigo_informe'] ?? 'S/N')), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td><?= strtoupper(htmlspecialchars((string)($inf['tipo'] ?? 'general'), ENT_QUOTES, 'UTF-8')) ?></td>
                                            <td>v<?= (int)($inf['version'] ?? 1) ?></td>
                                            <td><span class="cy-pill <?= $infPill ?>"><?= htmlspecialchars(ucfirst($infEstado), ENT_QUOTES, 'UTF-8') ?></span></td>
                                            <td class="cy-meta"><?= htmlspecialchars((string)($inf['fecha_aprobacion'] ?? ($inf['fecha_emision'] ?? '—')), ENT_QUOTES, 'UTF-8') ?></td>
                                            <td class="cy-meta"><?= !empty($inf['sha256']) ? htmlspecialchars(substr((string)$inf['sha256'], 0, 12), ENT_QUOTES, 'UTF-8') . '…' : 'Pendiente' ?></td>
                                            <td class="text-end">
                                                <?php if (!empty($inf['pdf'])): ?>
                                                    <a href="/Cycsa/publico/<?= htmlspecialchars((string)$inf['pdf'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" class="btn btn-outline-secondary btn-sm">Descargar PDF</a>
                                                <?php else: ?>
                                                    <span class="cy-meta">No adjunto</span>
                                                <?php endif; ?>
                                            </td>
                                        </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    <?php endif; ?>

                    <div class="cy-row">
                        <div class="flex-grow-1">
                            <div class="cy-assay-name">Consolidado multi-muestra</div>
                            <div class="cy-assay-norm">
                                Total muestras conectadas: <?= (int)($consolidado_lab['total_muestras'] ?? 0) ?> · Versión consolidada: v<?= (int)($consolidado_lab['version_consolidado'] ?? 1) ?>
                            </div>
                        </div>
                    </div>

                    <?php if (!empty($historial_versiones_lab)): ?>
                        <?php foreach ($historial_versiones_lab as $verItem): ?>
                        <div class="cy-row">
                            <div class="flex-grow-1">
                                <div class="cy-assay-name">Versión <?= (int)($verItem['version'] ?? 1) ?> · <?= htmlspecialchars((string)($verItem['motivo'] ?? 'Emisión oficial'), ENT_QUOTES, 'UTF-8') ?></div>
                                <div class="cy-assay-norm"><?= htmlspecialchars(substr((string)($verItem['fecha'] ?? ''), 0, 16), ENT_QUOTES, 'UTF-8') ?><?= !empty($verItem['sha256']) ? ' · SHA-256: ' . htmlspecialchars(substr((string)$verItem['sha256'], 0, 16), ENT_QUOTES, 'UTF-8') . '…' : '' ?></div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- MODALES COMPARTIDOS DE OPERACIONES LIMS -->
<div id="modalConfirmarCierreOS" class="modal-premium" style="display: none;">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Cierre definitivo de orden</h3>
            <button type="button" onclick="cerrarModalCierreOS()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/cerrar-operacion">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_os" id="cierre_id_os" value="<?= (int)$o['id'] ?>">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/ver?id_os=<?= (int)$o['id'] ?>">
            <p class="mb-3" style="font-size: 14px;">
                ¿Confirma el cierre y archivo formal de la orden <strong id="cierre_codigo_os"><?= htmlspecialchars((string)($o['codigo_os'] ?? ''), ENT_QUOTES, 'UTF-8') ?></strong>?
            </p>
            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" onclick="cerrarModalCierreOS()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Aprobar y archivar orden</button>
            </div>
        </form>
    </div>
</div>

<div id="modalRevisionResultados" class="modal-premium" style="display: none;">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Revisión técnica de calidad: <span id="qc_codigo_os"><?= htmlspecialchars((string)($o['codigo_os'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span></h3>
            <button type="button" onclick="cerrarModalRevisionResultados()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/procesar-revision-resultados" id="qc-form">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_os" id="qc_id_os" value="<?= (int)$o['id'] ?>">
            <input type="hidden" name="decision" id="qc_decision" value="Aprobar">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/ver?id_os=<?= (int)$o['id'] ?>">
            <p class="mb-3" style="font-size: 14px;">Valide si los resultados cargados cumplen las tolerancias para aprobar o devolver la orden.</p>
            <div class="mb-3" id="qc-group-obs" style="display: none;">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Detalle los resultados observados *</label>
                <textarea name="motivo_observacion" id="qc_motivo" class="form-control" rows="3"></textarea>
            </div>
            <div class="d-flex justify-content-between gap-2 pt-2 border-top">
                <button type="button" onclick="ejecutarQCObservar()" class="btn btn-outline-secondary btn-sm">Devolver / Observar</button>
                <div class="d-flex gap-2">
                    <button type="button" onclick="cerrarModalRevisionResultados()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                    <button type="button" onclick="ejecutarQCAprobar()" class="btn btn-primary btn-sm" id="btn-qc-aprobar">Aprobar revisión técnica</button>
                </div>
            </div>
        </form>
    </div>
</div>

<div id="modalAprobarMatrizIndex" class="modal-premium" style="display: none;">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Aprobar matriz técnica</h3>
            <button type="button" onclick="cerrarModalAprobarEnIndex()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <p class="mb-2" style="font-size: 14px;">¿Confirma la aprobación técnica de los resultados y cálculos para este ensayo?</p>
        <p class="mb-3" style="font-size: 14px;">Ensayo: <strong id="aprob_index_ensayo"></strong></p>
        <form method="POST" action="/Cycsa/publico/operaciones/aprobar-matriz-producto">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_detalle" id="aprob_index_id_detalle" value="">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/ver?id_os=<?= (int)$o['id'] ?>">
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Nota de aprobación (opcional)</label>
                <input type="text" name="nota_aprobacion" class="form-control" placeholder="Conforme con tolerancias y especificaciones ASTM.">
            </div>
            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" onclick="cerrarModalAprobarEnIndex()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Aprobar matriz</button>
            </div>
        </form>
    </div>
</div>

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
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/ver?id_os=<?= (int)$o['id'] ?>">
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

<div id="modalVerObservacionDevolucion" class="modal-premium" style="display: none;">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Observación del supervisor</h3>
            <button type="button" onclick="cerrarModalVerObservacionDevolucion()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <p class="mb-2" style="font-size: 14px;">Ensayo: <strong id="ver_obs_ensayo"></strong></p>
        <div class="cy-note mt-0 mb-3">
            <p id="ver_obs_motivo" class="m-0" style="color: var(--cy-text); white-space: pre-wrap;"></p>
        </div>
        <div class="cy-meta mb-3">
            Devuelto por <strong id="ver_obs_usuario"></strong> · <span id="ver_obs_fecha"></span>
        </div>
        <div class="d-flex justify-content-end pt-2 border-top">
            <button type="button" onclick="cerrarModalVerObservacionDevolucion()" class="btn btn-outline-secondary btn-sm">Cerrar</button>
        </div>
    </div>
</div>

<div id="modalEnviarMatriz" class="modal-premium" style="display: none;">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Enviar informe al cliente</h3>
            <button type="button" onclick="cerrarModalEnviarMatriz()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/enviar-matriz-cliente">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_detalle" id="env_index_id_detalle" value="">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/ver?id_os=<?= (int)$o['id'] ?>">
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Ensayo</label>
                <div id="env_index_ensayo" class="fw-semibold" style="font-size: 14px;"></div>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Cliente</label>
                <input type="text" id="env_index_cliente" name="cliente_nombre" class="form-control" readonly>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Correo(s) destinatario *</label>
                <input type="text" id="env_index_email" name="destinatario" class="form-control" required>
            </div>
            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" onclick="cerrarModalEnviarMatriz()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Enviar correo</button>
            </div>
        </form>
    </div>
</div>

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
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/ver?id_os=<?= (int)$o['id'] ?>">
            <div class="cy-note mt-0 mb-3">
                Se emitirá una nueva versión correlativa y la matriz pasará a revisión para su re-aprobación.
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Motivo obligatorio de la corrección *</label>
                <textarea name="motivo_correccion" id="corr_index_motivo" rows="2" class="form-control" required></textarea>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Nombre o razón social (para el informe)</label>
                <input type="text" name="cliente_nombre" id="corr_index_cliente" class="form-control">
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Proyecto</label>
                <input type="text" name="nombre_proyecto" id="corr_index_proyecto" class="form-control">
            </div>
            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" onclick="cerrarModalCorreccionAdminIndex()" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Guardar y reemitir</button>
            </div>
        </form>
    </div>
</div>

<script>
    function cambiarTabFull(btn, tabId) {
        document.querySelectorAll('.btn-tab-full').forEach(b => b.classList.remove('active'));
        if (btn) btn.classList.add('active');

        document.querySelectorAll('.tab-pane-full').forEach(p => {
            p.style.display = 'none';
        });
        const target = document.getElementById(tabId);
        if (target) target.style.display = 'block';
    }

    function abrirModalCerrarOS(idOS, codigoOS) {
        const idElem = document.getElementById('cierre_id_os');
        const codeElem = document.getElementById('cierre_codigo_os');
        if (idElem) idElem.value = idOS;
        if (codeElem && codigoOS) codeElem.innerText = codigoOS;
        const modal = document.getElementById('modalConfirmarCierreOS');
        if (modal) modal.style.display = 'block';
    }

    function cerrarModalCierreOS() {
        const modal = document.getElementById('modalConfirmarCierreOS');
        if (modal) modal.style.display = 'none';
    }

    function abrirModalRevisionResultados(id, code) {
        const idElem = document.getElementById('qc_id_os');
        const codeElem = document.getElementById('qc_codigo_os');
        if (idElem) idElem.value = id;
        if (codeElem && code) codeElem.innerText = code;
        const group = document.getElementById('qc-group-obs');
        if (group) group.style.display = 'none';
        const motivo = document.getElementById('qc_motivo');
        if (motivo) motivo.value = '';
        const btnAprob = document.getElementById('btn-qc-aprobar');
        if (btnAprob) btnAprob.style.display = 'inline-flex';
        const modal = document.getElementById('modalRevisionResultados');
        if (modal) modal.style.display = 'block';
    }

    function cerrarModalRevisionResultados() {
        const modal = document.getElementById('modalRevisionResultados');
        if (modal) modal.style.display = 'none';
    }

    function ejecutarQCAprobar() {
        const dec = document.getElementById('qc_decision');
        if (dec) dec.value = 'Aprobar';
        const form = document.getElementById('qc-form');
        if (form) form.submit();
    }

    function ejecutarQCObservar() {
        const group = document.getElementById('qc-group-obs');
        if (!group) return;
        if (group.style.display === 'none') {
            group.style.display = 'block';
            const motivo = document.getElementById('qc_motivo');
            if (motivo) motivo.focus();
            const btnAprob = document.getElementById('btn-qc-aprobar');
            if (btnAprob) btnAprob.style.display = 'none';
        } else {
            const motivo = document.getElementById('qc_motivo');
            if (!motivo || !motivo.value.trim()) {
                alert('Debe indicar un motivo u observación si desea devolver los resultados.');
                return;
            }
            const dec = document.getElementById('qc_decision');
            if (dec) dec.value = 'Rechazar';
            const form = document.getElementById('qc-form');
            if (form) form.submit();
        }
    }

    function abrirModalAprobarEnIndex(idDetalle, nombreEnsayo) {
        const idElem = document.getElementById('aprob_index_id_detalle');
        const nomElem = document.getElementById('aprob_index_ensayo');
        if (idElem) idElem.value = idDetalle;
        if (nomElem) nomElem.innerText = nombreEnsayo;
        const modal = document.getElementById('modalAprobarMatrizIndex');
        if (modal) modal.style.display = 'block';
    }

    function cerrarModalAprobarEnIndex() {
        const modal = document.getElementById('modalAprobarMatrizIndex');
        if (modal) modal.style.display = 'none';
    }

    function abrirModalDevolverEnIndex(idDetalle, nombreEnsayo) {
        const idElem = document.getElementById('dev_index_id_detalle');
        const nomElem = document.getElementById('dev_index_ensayo');
        if (idElem) idElem.value = idDetalle;
        if (nomElem) nomElem.innerText = nombreEnsayo;
        const modal = document.getElementById('modalDevolverMatrizIndex');
        if (modal) modal.style.display = 'block';
    }

    function cerrarModalDevolverEnIndex() {
        const modal = document.getElementById('modalDevolverMatrizIndex');
        if (modal) modal.style.display = 'none';
    }

    function verObservacionDevolucion(idDetalle, nombreEnsayo, motivo, usuario, fecha) {
        const ensElem = document.getElementById('ver_obs_ensayo');
        const motElem = document.getElementById('ver_obs_motivo');
        const usrElem = document.getElementById('ver_obs_usuario');
        const fecElem = document.getElementById('ver_obs_fecha');
        if (ensElem) ensElem.innerText = nombreEnsayo;
        if (motElem) motElem.innerText = motivo || 'Sin observaciones registradas.';
        if (usrElem) usrElem.innerText = usuario || 'Supervisor';
        if (fecElem) fecElem.innerText = fecha || '';
        const modal = document.getElementById('modalVerObservacionDevolucion');
        if (modal) modal.style.display = 'block';
    }

    function cerrarModalVerObservacionDevolucion() {
        const modal = document.getElementById('modalVerObservacionDevolucion');
        if (modal) modal.style.display = 'none';
    }

    function abrirModalEnviarMatriz(idDetalle, nombreEnsayo, clienteNombre, clienteEmail, codigoOS) {
        const idElem = document.getElementById('env_index_id_detalle');
        const ensElem = document.getElementById('env_index_ensayo');
        const cliElem = document.getElementById('env_index_cliente');
        const emlElem = document.getElementById('env_index_email');
        if (idElem) idElem.value = idDetalle;
        if (ensElem) ensElem.innerText = nombreEnsayo;
        if (cliElem) cliElem.value = clienteNombre || '';
        if (emlElem) emlElem.value = clienteEmail || '';
        const modal = document.getElementById('modalEnviarMatriz');
        if (modal) modal.style.display = 'block';
    }

    function cerrarModalEnviarMatriz() {
        const modal = document.getElementById('modalEnviarMatriz');
        if (modal) modal.style.display = 'none';
    }

    function abrirModalCorreccionAdminIndex(idDetalle, nombreEnsayo, cliente, proyecto, ubicacion) {
        const idElem = document.getElementById('corr_index_id_detalle');
        const ensElem = document.getElementById('corr_index_ensayo_nombre');
        const motElem = document.getElementById('corr_index_motivo');
        const cliElem = document.getElementById('corr_index_cliente');
        const proElem = document.getElementById('corr_index_proyecto');
        if (idElem) idElem.value = idDetalle;
        if (ensElem) ensElem.innerText = nombreEnsayo || '';
        if (motElem) motElem.value = '';
        if (cliElem) cliElem.value = cliente || '';
        if (proElem) proElem.value = proyecto || '';
        const modal = document.getElementById('modalCorreccionAdminIndex');
        if (modal) modal.style.display = 'block';
    }

    function cerrarModalCorreccionAdminIndex() {
        const modal = document.getElementById('modalCorreccionAdminIndex');
        if (modal) modal.style.display = 'none';
    }

    window.addEventListener('click', function(e) {
        const modales = [
            'modalConfirmarCierreOS',
            'modalRevisionResultados',
            'modalAprobarMatrizIndex',
            'modalDevolverMatrizIndex',
            'modalVerObservacionDevolucion',
            'modalEnviarMatriz',
            'modalCorreccionAdminIndex'
        ];
        modales.forEach(function(mId) {
            const m = document.getElementById(mId);
            if (m && e.target === m) {
                m.style.display = 'none';
            }
        });
    });
</script>

<?php
$bitacora_modulo_nombre = 'Operaciones LIMS';
include dirname(__DIR__, 3) . '/Views/parciales/bitacora_modulo.php';
?>
