<?php
$revisiones = $revisiones ?? [];
$anulados = $anulados ?? [];
$detalleActivo = $detalleActivo ?? null;
$checklistItems = $checklistItems ?? [];
$busqueda = $busqueda ?? '';
$seccionActiva = $seccionActiva ?? 'revisiones';
$usuarioActualId = (int)($usuarioActualId ?? 0);
$csrfToken = $csrfToken ?? ($_SESSION['csrf_token'] ?? '');

$resolverPillPt13 = static function (string $estado): array {
    return match ($estado) {
        'Autorizado' => ['clase' => 'cy-pill-ok', 'texto' => 'Autorizado'],
        'Listo Para Calidad', 'En Revision Forma', 'En Revision Tecnica' => ['clase' => 'cy-pill-warn', 'texto' => $estado],
        'Observado', 'Anulado' => ['clase' => 'cy-pill-danger', 'texto' => $estado],
        default => ['clase' => 'cy-pill-pend', 'texto' => $estado !== '' ? $estado : 'Pendiente'],
    };
};

// Agrupar los 25 ítems de CYCSA-RT-FM-65 por categoría
$checklistAgrupado = [];
foreach ($checklistItems as $clave => $metaItem) {
    $cat = (string)($metaItem['categoria'] ?? 'General');
    $checklistAgrupado[$cat][$clave] = $metaItem;
}

$esMismoAnalistaCaptura = false;
if ($detalleActivo && !empty($detalleActivo['usuario_captura_id']) && $usuarioActualId > 0) {
    $esMismoAnalistaCaptura = ((int)$detalleActivo['usuario_captura_id'] === $usuarioActualId);
}
?>
<link rel="stylesheet" href="/Cycsa/publico/css/cycsa-sobrio.css">
<link rel="stylesheet" href="/Cycsa/publico/css/remisiones-sobrio.css">

<div class="container-fluid px-0 py-0 cy-rem-wrap">
    <a href="/Cycsa/publico/remisiones" class="cy-rem-back-link">&larr; Volver a Remisiones (RG-FM-41)</a>

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

    <div class="cy-rem-header">
        <div class="cy-rem-header-left">
            <h1 class="cy-page-title">Control PT-13 Ver. 5 Rev. 2 (ISO/IEC 17025:2017)</h1>
            <div class="cy-meta">Revisión en 3 etapas, Checklist CYCSA-RT-FM-65 (25 ítems), Unificación por Suplencia y Registro de Informes Anulados.</div>
        </div>
        <div class="cy-rem-header-actions">
            <a href="/Cycsa/publico/remisiones/crear" class="btn btn-primary btn-sm">Nueva Remisión (RG-FM-41)</a>
        </div>
    </div>

    <!-- Pestañas sobrias subrayadas -->
    <ul class="nav nav-tabs mb-3" role="tablist">
        <li class="nav-item">
            <a class="nav-link <?= $seccionActiva === 'revisiones' ? 'active' : '' ?>" href="/Cycsa/publico/remisiones/revision-pt13?seccion=revisiones">
                Revisión 3 Etapas y Checklist RT-FM-65 <span class="cy-tab-count">(<?= count($revisiones) ?>)</span>
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link <?= $seccionActiva === 'anulados' ? 'active' : '' ?>" href="/Cycsa/publico/remisiones/revision-pt13?seccion=anulados">
                Informes Anulados — Sec. 5.6 b <span class="cy-tab-count">(<?= count($anulados) ?>)</span>
            </a>
        </li>
    </ul>

    <?php if ($seccionActiva === 'revisiones'): ?>
        <!-- Búsqueda rápida -->
        <form method="GET" action="/Cycsa/publico/remisiones/revision-pt13" class="cy-rem-filter-bar">
            <input type="hidden" name="seccion" value="revisiones">
            <input type="text" name="q" class="cy-rem-search-input" value="<?= htmlspecialchars($busqueda, ENT_QUOTES, 'UTF-8') ?>" placeholder="Buscar por O/S, cliente, ensayo o código de informe...">
            <button type="submit" class="btn btn-outline-secondary btn-sm">Buscar</button>
            <?php if ($busqueda !== ''): ?>
                <a href="/Cycsa/publico/remisiones/revision-pt13?seccion=revisiones" class="cy-action-link">Limpiar</a>
            <?php endif; ?>
        </form>

        <div class="cy-pt13-layout">
            <!-- Bandeja de ensayos/informes con captura -->
            <div class="cy-rem-card mb-0">
                <div class="cy-rem-card-head">
                    <span>Informes en Control PT-13</span>
                    <span class="cy-meta"><?= count($revisiones) ?></span>
                </div>
                <div>
                    <?php if (!empty($revisiones)): ?>
                        <?php foreach ($revisiones as $rev):
                            $pillRev = $resolverPillPt13((string)($rev['estado_pt13'] ?? 'Pendiente'));
                            $seleccionado = $detalleActivo && (int)$detalleActivo['id_detalle_cotizacion'] === (int)$rev['id_detalle_cotizacion'];
                        ?>
                        <a
                            href="/Cycsa/publico/remisiones/revision-pt13?seccion=revisiones&id_detalle=<?= (int)$rev['id_detalle_cotizacion'] ?><?= $busqueda !== '' ? '&q=' . urlencode($busqueda) : '' ?>"
                            class="cy-pt13-list-item <?= $seleccionado ? 'is-selected' : '' ?>"
                        >
                            <div class="d-flex justify-content-between align-items-start gap-2 mb-1">
                                <span class="fw-semibold" style="color: var(--cy-brand);">
                                    <?= htmlspecialchars((string)$rev['codigo_informe_resuelto'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                                <span class="cy-pill <?= htmlspecialchars($pillRev['clase'], ENT_QUOTES, 'UTF-8') ?>">
                                    <?= htmlspecialchars($pillRev['texto'], ENT_QUOTES, 'UTF-8') ?>
                                </span>
                            </div>
                            <div class="fw-medium mb-1"><?= htmlspecialchars((string)$rev['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="cy-meta">
                                <?= htmlspecialchars((string)($rev['codigo_os'] ?: 'Sin O/S'), ENT_QUOTES, 'UTF-8') ?>
                                &middot; <?= htmlspecialchars((string)($rev['cliente_nombre'] ?: 'Cliente'), ENT_QUOTES, 'UTF-8') ?>
                            </div>
                            <div class="cy-meta mt-1">
                                Checklist RT-FM-65: <strong><?= (int)$rev['checklist_completados'] ?>/<?= (int)$rev['checklist_total'] ?></strong>
                            </div>
                        </a>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="p-4 text-center cy-meta">
                            No hay ensayos con resultados capturados disponibles para revisión PT-13.
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Panel de Revisión 3 Etapas + Checklist CYCSA-RT-FM-65 -->
            <div>
                <?php if ($detalleActivo):
                    $pillAct = $resolverPillPt13((string)($detalleActivo['estado_pt13'] ?? 'Pendiente'));
                    $chkActual = is_array($detalleActivo['checklist'] ?? null) ? $detalleActivo['checklist'] : [];
                ?>
                <div class="cy-rem-card">
                    <div class="cy-rem-card-head">
                        <div>
                            <div><?= htmlspecialchars((string)$detalleActivo['codigo_informe_resuelto'], ENT_QUOTES, 'UTF-8') ?> — <?= htmlspecialchars((string)$detalleActivo['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="cy-meta">
                                O/S: <?= htmlspecialchars((string)($detalleActivo['codigo_os'] ?: 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                                &middot; Cliente: <?= htmlspecialchars((string)($detalleActivo['cliente_nombre'] ?: 'N/A'), ENT_QUOTES, 'UTF-8') ?>
                                <?php if (!empty($detalleActivo['usuario_captura_id'])): ?>
                                    &middot; Capturado por ID #<?= (int)$detalleActivo['usuario_captura_id'] ?>
                                    <?= !empty($detalleActivo['usuario_captura_nombre']) ? '(' . htmlspecialchars((string)$detalleActivo['usuario_captura_nombre'], ENT_QUOTES, 'UTF-8') . ')' : '' ?>
                                <?php endif; ?>
                            </div>
                        </div>
                        <span class="cy-pill <?= htmlspecialchars($pillAct['clase'], ENT_QUOTES, 'UTF-8') ?>">
                            <?= htmlspecialchars($pillAct['texto'], ENT_QUOTES, 'UTF-8') ?>
                        </span>
                    </div>

                    <div class="cy-rem-card-body">
                        <?php if ($esMismoAnalistaCaptura): ?>
                            <div class="cy-note mb-3 d-flex align-items-center gap-2" role="alert">
                                <span class="cy-pill cy-pill-danger">PT-13 Sec. 5.1 a</span>
                                <span style="color: var(--cy-text);">Usted registró la captura de resultados de este ensayo. Por regla anti-autoaprobación ISO/IEC 17025:2017 no puede ejecutar la Revisión Técnica ni la Autorización de Calidad.</span>
                            </div>
                        <?php endif; ?>

                        <!-- Estado de las 3 Etapas PT-13 -->
                        <div class="cy-pt13-stage-grid">
                            <div class="cy-pt13-stage-box <?= !empty($detalleActivo['fecha_rev_tecnica']) ? 'is-done' : '' ?>">
                                <div class="cy-meta">Etapa 1</div>
                                <div class="fw-semibold mb-1">Revisión Técnica</div>
                                <?php if (!empty($detalleActivo['fecha_rev_tecnica'])): ?>
                                    <div class="cy-meta"><?= htmlspecialchars((string)$detalleActivo['revisor_tecnico_nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="cy-meta"><?= date('d/m/Y H:i', strtotime((string)$detalleActivo['fecha_rev_tecnica'])) ?></div>
                                <?php else: ?>
                                    <span class="cy-pill cy-pill-pend">Pendiente</span>
                                <?php endif; ?>
                            </div>

                            <div class="cy-pt13-stage-box <?= !empty($detalleActivo['fecha_rev_forma']) ? 'is-done' : '' ?>">
                                <div class="cy-meta">Etapa 2</div>
                                <div class="fw-semibold mb-1">Revisión de Forma</div>
                                <?php if (!empty($detalleActivo['fecha_rev_forma'])): ?>
                                    <div class="cy-meta"><?= htmlspecialchars((string)$detalleActivo['asistente_coord_nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="cy-meta"><?= date('d/m/Y H:i', strtotime((string)$detalleActivo['fecha_rev_forma'])) ?></div>
                                <?php else: ?>
                                    <span class="cy-pill cy-pill-pend">Pendiente</span>
                                <?php endif; ?>
                            </div>

                            <div class="cy-pt13-stage-box <?= !empty($detalleActivo['fecha_autorizacion']) ? 'is-done' : '' ?>">
                                <div class="cy-meta">Etapa 3</div>
                                <div class="fw-semibold mb-1">Autorización Calidad</div>
                                <?php if (!empty($detalleActivo['fecha_autorizacion'])): ?>
                                    <div class="cy-meta"><?= htmlspecialchars((string)$detalleActivo['autorizador_calidad_nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                                    <div class="cy-meta"><?= date('d/m/Y H:i', strtotime((string)$detalleActivo['fecha_autorizacion'])) ?></div>
                                    <?php if (!empty($detalleActivo['es_unificacion_suplencia'])): ?>
                                        <div class="mt-1"><span class="cy-pill cy-pill-warn">Unificación / Suplencia</span></div>
                                    <?php endif; ?>
                                <?php else: ?>
                                    <span class="cy-pill cy-pill-pend">Pendiente</span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <form method="POST" action="/Cycsa/publico/remisiones/revision-pt13/guardar">
                            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                            <input type="hidden" name="id_detalle_cotizacion" value="<?= (int)$detalleActivo['id_detalle_cotizacion'] ?>">

                            <div class="cy-rem-grid-2 mb-3">
                                <div>
                                    <label for="pt13_codigo_informe" class="form-label">Código de Informe Controlado</label>
                                    <input
                                        type="text"
                                        id="pt13_codigo_informe"
                                        name="codigo_informe"
                                        class="form-control"
                                        value="<?= htmlspecialchars((string)$detalleActivo['codigo_informe_resuelto'], ENT_QUOTES, 'UTF-8') ?>"
                                    >
                                </div>
                                <div>
                                    <label for="pt13_etapa" class="form-label">Etapa a Registrar</label>
                                    <select id="pt13_etapa" name="etapa" class="form-select">
                                        <option value="tecnica">Etapa 1 — Revisión Técnica</option>
                                        <option value="forma">Etapa 2 — Revisión de Forma (Asistente Coordinación)</option>
                                        <option value="calidad">Etapa 3 — Autorización y Liberación de Calidad</option>
                                    </select>
                                </div>
                            </div>

                            <!-- Checklist CYCSA-RT-FM-65 (25 ítems oficiales) -->
                            <div class="cy-rem-card mb-3">
                                <div class="cy-rem-card-head">
                                    <span>Checklist CYCSA-RT-FM-65 (25 ítems — Tabla 1 PT-13 Ver. 5 Rev. 2)</span>
                                    <button type="button" class="btn btn-outline-secondary btn-sm" id="btn-marcar-todos-cumple">Marcar todos Cumple</button>
                                </div>
                                <div>
                                    <?php foreach ($checklistAgrupado as $categoria => $itemsCat): ?>
                                        <div class="cy-checklist-group-title"><?= htmlspecialchars($categoria, ENT_QUOTES, 'UTF-8') ?></div>
                                        <?php foreach ($itemsCat as $clave => $metaItem):
                                            $valItem = (string)($chkActual[$clave] ?? '');
                                        ?>
                                        <div class="cy-checklist-row">
                                            <div class="fw-semibold" style="color: var(--cy-brand);"><?= htmlspecialchars((string)$metaItem['clausula'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <div><?= htmlspecialchars((string)$metaItem['descripcion'], ENT_QUOTES, 'UTF-8') ?></div>
                                            <div>
                                                <select name="checklist[<?= htmlspecialchars($clave, ENT_QUOTES, 'UTF-8') ?>]" class="form-select cy-chk-select">
                                                    <option value="">Sin evaluar</option>
                                                    <option value="cumple" <?= $valItem === 'cumple' ? 'selected' : '' ?>>Cumple</option>
                                                    <option value="na" <?= $valItem === 'na' ? 'selected' : '' ?>>No Aplica (N/A)</option>
                                                    <option value="no_cumple" <?= $valItem === 'no_cumple' ? 'selected' : '' ?>>No Cumple</option>
                                                </select>
                                            </div>
                                        </div>
                                        <?php endforeach; ?>
                                    <?php endforeach; ?>
                                </div>
                            </div>

                            <!-- Declaraciones Técnicas PT-13 / ISO 17025 -->
                            <div class="cy-rem-card mb-3">
                                <div class="cy-rem-card-head">
                                    <span>Declaraciones Técnicas PT-13 (Laboratorio Externo, Desviaciones, Opiniones, Incertidumbre, Simplificación)</span>
                                </div>
                                <div class="cy-rem-card-body">
                                    <div class="cy-rem-grid-2 mb-3">
                                        <div>
                                            <label class="form-label d-flex align-items-center gap-2">
                                                <input type="checkbox" name="es_laboratorio_externo" value="1" <?= !empty($detalleActivo['es_laboratorio_externo']) ? 'checked' : '' ?>>
                                                Resultados provenientes de Laboratorio Externo (7.8.2.1 p)
                                            </label>
                                            <input
                                                type="text"
                                                name="laboratorio_externo_nombre"
                                                class="form-control"
                                                placeholder="Nombre del proveedor / laboratorio externo"
                                                value="<?= htmlspecialchars((string)($detalleActivo['laboratorio_externo_nombre'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                        </div>
                                        <div>
                                            <label for="pt13_incertidumbre" class="form-label">Incertidumbre de Medición (7.8.3.1 c)</label>
                                            <input
                                                type="text"
                                                id="pt13_incertidumbre"
                                                name="incertidumbre_medicion"
                                                class="form-control"
                                                placeholder="Ej. ± 1.8% (k=2, 95% confianza)"
                                                value="<?= htmlspecialchars((string)($detalleActivo['incertidumbre_medicion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                        </div>
                                    </div>

                                    <div class="cy-rem-grid-2 mb-3">
                                        <div>
                                            <label for="pt13_adiciones" class="form-label">Adiciones, Desviaciones o Exclusiones del Método (7.8.2.1 n)</label>
                                            <textarea id="pt13_adiciones" name="adiciones_desviaciones" class="form-control" rows="2" placeholder="Indique si hubo desviaciones o exclusiones documentadas"><?= htmlspecialchars((string)($detalleActivo['adiciones_desviaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                        </div>
                                        <div>
                                            <label for="pt13_opiniones" class="form-label">Opiniones e Interpretaciones (7.8.3.1 d)</label>
                                            <textarea id="pt13_opiniones" name="opiniones_interpretaciones" class="form-control" rows="2" placeholder="Base técnica de opiniones o interpretaciones autorizadas"><?= htmlspecialchars((string)($detalleActivo['opiniones_interpretaciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?></textarea>
                                        </div>
                                    </div>

                                    <div class="cy-rem-grid-2 mb-3">
                                        <div>
                                            <label for="pt13_simplificacion" class="form-label">Referencia de Solicitud de Simplificación del Cliente (7.8.1.3)</label>
                                            <input
                                                type="text"
                                                id="pt13_simplificacion"
                                                name="solicitud_simplificacion_ref"
                                                class="form-control"
                                                placeholder="Ej. Correo/Acta de acuerdo de informe simplificado"
                                                value="<?= htmlspecialchars((string)($detalleActivo['solicitud_simplificacion_ref'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                        </div>
                                        <div>
                                            <label for="pt13_obs_tecnica" class="form-label">Observaciones de Revisión Técnica / Forma</label>
                                            <input
                                                type="text"
                                                id="pt13_obs_tecnica"
                                                name="obs_rev_tecnica"
                                                class="form-control mb-2"
                                                placeholder="Observaciones de revisión técnica"
                                                value="<?= htmlspecialchars((string)($detalleActivo['obs_rev_tecnica'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                            <input
                                                type="text"
                                                name="obs_rev_forma"
                                                class="form-control"
                                                placeholder="Observaciones de revisión de forma"
                                                value="<?= htmlspecialchars((string)($detalleActivo['obs_rev_forma'] ?? ''), ENT_QUOTES, 'UTF-8') ?>"
                                            >
                                        </div>
                                    </div>

                                    <!-- Unificación por Suplencia (PT-13 Sec. 5.1 a) -->
                                    <div class="border rounded p-3" style="border-color: var(--cy-border); background: #F9FAFB;">
                                        <label class="form-label d-flex align-items-center gap-2 mb-2">
                                            <input type="checkbox" name="es_unificacion_suplencia" value="1" <?= !empty($detalleActivo['es_unificacion_suplencia']) ? 'checked' : '' ?>>
                                            <strong>Unificación por Suplencia Autorizada (PT-13 Sec. 5.1 a)</strong>
                                        </label>
                                        <div class="cy-meta mb-2">
                                            Solo aplicable en Etapa 3 (Autorización de Calidad) cuando una persona autorizada asume etapas faltantes por ausencia/suplencia. Nunca aplica al analista que capturó resultados.
                                        </div>
                                        <div class="cy-rem-grid-3">
                                            <div>
                                                <label class="form-label">Funciones Asumidas</label>
                                                <input type="text" name="suplencia_funciones" class="form-control" placeholder="Ej. Rev. Técnica + Rev. Forma + Calidad" value="<?= htmlspecialchars((string)($detalleActivo['suplencia_funciones'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div>
                                                <label class="form-label">Motivo de Suplencia</label>
                                                <input type="text" name="suplencia_motivo" class="form-control" placeholder="Motivo formal de la suplencia" value="<?= htmlspecialchars((string)($detalleActivo['suplencia_motivo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                            <div>
                                                <label class="form-label">Referencia de Autorización</label>
                                                <input type="text" name="suplencia_referencia_autorizacion" class="form-control" placeholder="Ej. MEMO-GER-2026-04" value="<?= htmlspecialchars((string)($detalleActivo['suplencia_referencia_autorizacion'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between align-items-center flex-wrap gap-2">
                                <label class="d-inline-flex align-items-center gap-2 cy-meta">
                                    <input type="checkbox" name="marcar_observado" value="1">
                                    Marcar como Observado (devolver para corrección en Etapa 1 o 2)
                                </label>
                                <button type="submit" class="btn btn-primary">Guardar Revisión PT-13</button>
                            </div>
                        </form>
                    </div>
                </div>
                <?php else: ?>
                <div class="cy-rem-card">
                    <div class="cy-rem-card-body text-center py-5">
                        <span class="cy-meta">Seleccione un ensayo de la bandeja izquierda para gestionar su control PT-13 y checklist CYCSA-RT-FM-65.</span>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>
    <?php else: ?>
        <!-- Sección (b): Informes Anulados (PT-13 Sec. 5.6 b) -->
        <div class="cy-rem-card">
            <div class="cy-rem-card-head">
                <span>Registrar Anulación Permanente de Informe (PT-13 Sec. 5.6 b)</span>
                <span class="cy-meta">El código anulado queda bloqueado permanentemente contra reutilización</span>
            </div>
            <div class="cy-rem-card-body">
                <form method="POST" action="/Cycsa/publico/remisiones/anular-informe">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                    <div class="cy-rem-grid-3 mb-3">
                        <div>
                            <label for="anul_codigo" class="form-label">Código de Informe a Anular <span style="color: var(--cy-danger);">*</span></label>
                            <input type="text" id="anul_codigo" name="codigo_informe" class="form-control" required placeholder="Ej. INF-OS-2026-0001-01">
                        </div>
                        <div>
                            <label for="anul_id_detalle" class="form-label">Detalle / Matriz Relacionada (Opcional)</label>
                            <select id="anul_id_detalle" name="id_detalle_cotizacion" class="form-select">
                                <option value="">— Ninguno / Código manual —</option>
                                <?php foreach ($revisiones as $rev): ?>
                                    <option value="<?= (int)$rev['id_detalle_cotizacion'] ?>">
                                        <?= htmlspecialchars((string)$rev['codigo_informe_resuelto'] . ' — ' . (string)$rev['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label for="anul_motivo" class="form-label">Motivo de Anulación <span style="color: var(--cy-danger);">*</span></label>
                            <input type="text" id="anul_motivo" name="motivo" class="form-control" required placeholder="Ej. Error de identificación de muestra / enmienda mayor">
                        </div>
                    </div>
                    <div class="d-flex justify-content-end">
                        <button type="submit" class="btn-cy-danger-outline btn-sm">Registrar Anulación Permanente</button>
                    </div>
                </form>
            </div>
        </div>

        <div class="card">
            <div class="table-responsive">
                <table class="table">
                    <thead>
                        <tr>
                            <th>Código Anulado (Bloqueado)</th>
                            <th>O/S Relacionada</th>
                            <th>Ensayo</th>
                            <th>Motivo Documentado</th>
                            <th>Registrado por</th>
                            <th>Fecha</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($anulados as $an): ?>
                        <tr>
                            <td>
                                <span class="cy-pill cy-pill-danger"><?= htmlspecialchars((string)$an['codigo_informe'], ENT_QUOTES, 'UTF-8') ?></span>
                            </td>
                            <td><?= htmlspecialchars((string)($an['codigo_os'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($an['descripcion_ensayo'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)$an['motivo'], ENT_QUOTES, 'UTF-8') ?></td>
                            <td><?= htmlspecialchars((string)($an['usuario_nombre'] ?: 'Sistema'), ENT_QUOTES, 'UTF-8') ?></td>
                            <td class="cy-meta"><?= !empty($an['created_at']) ? date('d/m/Y H:i', strtotime((string)$an['created_at'])) : '—' ?></td>
                        </tr>
                        <?php endforeach; ?>

                        <?php if (empty($anulados)): ?>
                        <tr>
                            <td colspan="6" class="text-center py-4">
                                <span class="cy-meta">No se registran informes anulados.</span>
                            </td>
                        </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    <?php endif; ?>
</div>

<script>
(function () {
    const btnTodos = document.getElementById('btn-marcar-todos-cumple');
    if (btnTodos) {
        btnTodos.addEventListener('click', function () {
            document.querySelectorAll('.cy-chk-select').forEach(function (sel) {
                sel.value = 'cumple';
            });
        });
    }
})();
</script>
