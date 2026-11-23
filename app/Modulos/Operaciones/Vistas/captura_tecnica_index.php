<?php
// Módulo Captura Técnica — Mismo diseño sobrio de Operaciones, 100% ciego ISO/IEC 17025 (solo llenado técnico)
$ordenes = $ordenes ?? [];
$filtroActivo = $filtroActivo ?? 'pendientes';
$conteos = $conteos ?? ['pendientes' => 0, 'devueltas' => 0, 'en_revision' => 0, 'todas' => 0];
$busqueda = $busqueda ?? '';

$resolverEstadoPillCT = static function (array $o): array {
    if ((int)($o['devueltas'] ?? 0) > 0) {
        return ['clase' => 'cy-pill-danger', 'texto' => 'Devuelta · Corregir'];
    }
    $tot = (int)($o['total_ensayos'] ?? 0);
    $apr = (int)($o['aprobadas'] ?? 0);
    if ($tot > 0 && $apr >= $tot) {
        return ['clase' => 'cy-pill-ok', 'texto' => 'Aprobada'];
    }
    if (!empty($o['solo_en_revision'])) {
        return ['clase' => 'cy-pill-warn', 'texto' => 'En revisión'];
    }
    if ((int)($o['llenadas'] ?? 0) > 0) {
        return ['clase' => 'cy-pill-warn', 'texto' => 'En captura'];
    }
    return ['clase' => 'cy-pill-pend', 'texto' => 'Pendiente de captura'];
};

$resolverPillEnsayoCT = static function (string $estado): array {
    return match ($estado) {
        'aprobada' => ['clase' => 'cy-pill-ok', 'texto' => 'Aprobado'],
        'devuelta' => ['clase' => 'cy-pill-danger', 'texto' => 'Devuelta'],
        'en_revision' => ['clase' => 'cy-pill-warn', 'texto' => 'En revisión'],
        'borrador' => ['clase' => 'cy-pill-warn', 'texto' => 'En captura'],
        default => ['clase' => 'cy-pill-pend', 'texto' => 'Pendiente'],
    };
};
?>
<link rel="stylesheet" href="/Cycsa/publico/css/cycsa-sobrio.css">
<style>
    .cy-op-wrap { width: 100%; max-width: 100%; margin: 0; }
    .cy-op-subbar { display: flex; flex-wrap: wrap; justify-content: space-between; align-items: center; gap: 12px; margin-bottom: 16px; }
    .cy-op-search { width: 320px; max-width: 100%; font-size: 14px; padding: 7px 12px; border: 1px solid var(--cy-border); border-radius: 8px; background: var(--cy-surface); color: var(--cy-text); }
    #tabla-captura-tecnica-os tbody tr[id^="ct-row-"] { cursor: pointer; }
    .lims-drawer-fallback-backdrop { position: fixed; inset: 0; background: rgba(17, 24, 39, 0.25); z-index: 1040; display: none; }
    .lims-drawer-fallback-backdrop.active { display: block; }
    .modal-premium { display: none; position: fixed; z-index: 1060; inset: 0; overflow: auto; background: rgba(17, 24, 39, 0.35); }
    .modal-premium-content { background: #FFFFFF; margin: 5% auto; padding: 24px 28px; border: 1px solid var(--cy-border); border-radius: 10px; width: min(520px, 94vw); }
    .btn-cerrar { background: none; border: 0; color: var(--cy-muted); font-size: 20px; line-height: 1; cursor: pointer; padding: 4px 8px; }
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

    <!-- Encabezado sobrio + filtro segmentado (.cy-seg) -->
    <div class="d-flex flex-wrap justify-content-between align-items-center gap-3 mb-3">
        <div>
            <h1 class="cy-page-title">Captura Técnica</h1>
        </div>

        <div class="btn-group cy-seg" role="group" aria-label="Filtro de bandeja técnica">
            <a href="/Cycsa/publico/captura-tecnica?filtro=pendientes" class="btn <?= $filtroActivo === 'pendientes' ? 'is-active' : '' ?>">
                Pendientes (<?= (int)($conteos['pendientes'] ?? 0) ?>)
            </a>
            <a href="/Cycsa/publico/captura-tecnica?filtro=devueltas" class="btn <?= $filtroActivo === 'devueltas' ? 'is-active' : '' ?>">
                Devueltas (<?= (int)($conteos['devueltas'] ?? 0) ?>)
            </a>
            <a href="/Cycsa/publico/captura-tecnica?filtro=en_revision" class="btn <?= $filtroActivo === 'en_revision' ? 'is-active' : '' ?>">
                En revisión (<?= (int)($conteos['en_revision'] ?? 0) ?>)
            </a>
            <a href="/Cycsa/publico/captura-tecnica?filtro=todas" class="btn <?= $filtroActivo === 'todas' ? 'is-active' : '' ?>">
                Todas (<?= (int)($conteos['todas'] ?? 0) ?>)
            </a>
        </div>
    </div>

    <!-- Barra de búsqueda -->
    <div class="cy-op-subbar">
        <div class="cy-meta">Seleccione una orden para ver sus ensayos y llenar los datos técnicos.</div>
        <div>
            <input type="text" id="ct-search" value="<?= htmlspecialchars((string)$busqueda, ENT_QUOTES, 'UTF-8') ?>" placeholder="Filtrar por código de orden o ensayo..." class="form-control cy-op-search" aria-label="Filtrar órdenes técnicas">
        </div>
    </div>

    <!-- Tabla plana de órdenes técnicas (100% ciega) -->
    <div class="card lims-table-container">
        <div class="table-responsive">
            <table class="table" id="tabla-captura-tecnica-os">
                <thead>
                    <tr>
                        <th>Orden</th>
                        <th>Ensayos asignados</th>
                        <th>Estado</th>
                        <th>Avance</th>
                        <th class="text-end">Acción</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($ordenes as $o):
                        $idOS = (int)$o['id'];
                        $total = (int)$o['total_ensayos'];
                        $llenadas = (int)$o['llenadas'];
                        $pct = $total > 0 ? (int)round(($llenadas / $total) * 100) : 0;
                        $pill = $resolverEstadoPillCT($o);
                        $resumenEns = implode(', ', array_slice(array_map(static fn($it) => (string)$it['descripcion_ensayo'], $o['items']), 0, 2));
                        if ($total > 2) {
                            $resumenEns .= ' (+' . ($total - 2) . ')';
                        }
                        $idAcc = (int)($o['id_accion_principal'] ?? 0);
                    ?>
                    <tr id="ct-row-<?= $idOS ?>" class="fila-ct-os" data-detail-id="ct-detail-<?= $idOS ?>">
                        <td>
                            <div class="fw-semibold lims-os-code"><?= htmlspecialchars((string)$o['codigo_os'], ENT_QUOTES, 'UTF-8') ?></div>
                        </td>
                        <td>
                            <div class="fw-medium"><?= htmlspecialchars($resumenEns, ENT_QUOTES, 'UTF-8') ?></div>
                            <div class="cy-meta"><?= $total ?> <?= $total === 1 ? 'ensayo técnico' : 'ensayos técnicos' ?></div>
                        </td>
                        <td>
                            <span class="cy-pill <?= htmlspecialchars($pill['clase'], ENT_QUOTES, 'UTF-8') ?>">
                                <?= htmlspecialchars($pill['texto'], ENT_QUOTES, 'UTF-8') ?>
                            </span>
                        </td>
                        <td>
                            <div class="cy-progress-cell">
                                <div class="cy-bar"><i style="width: <?= $pct ?>%;"></i></div>
                                <span class="cy-meta"><?= $llenadas ?> de <?= $total ?> capturados</span>
                            </div>
                        </td>
                        <td class="text-end" style="white-space: nowrap;">
                            <div class="d-inline-flex align-items-center justify-content-end gap-2">
                                <?php if ($total === 1 && $idAcc > 0): ?>
                                    <a href="/Cycsa/publico/captura-tecnica/llenar?id_detalle=<?= $idAcc ?>"
                                       class="btn btn-primary btn-sm"
                                       onclick="event.stopPropagation();">
                                        <?= (int)$o['devueltas'] > 0 ? 'Corregir datos' : 'Llenar datos' ?>
                                    </a>
                                <?php else: ?>
                                    <button type="button"
                                            class="btn btn-outline-secondary btn-sm"
                                            onclick="event.stopPropagation(); abrirDrawerCapturaTecnica(<?= $idOS ?>);">
                                        Ver ensayos (<?= $llenadas ?>/<?= $total ?>)
                                    </button>
                                <?php endif; ?>
                            </div>
                        </td>
                    </tr>

                    <!-- Contenido oculto del panel lateral drawer -->
                    <tr id="ct-detail-<?= $idOS ?>" style="display: none !important;">
                        <td colspan="5" style="padding: 0; border: 0;">
                            <div class="detalle-ct-card">
                                <div class="cy-next mb-3">
                                    <div>
                                        <div class="fw-semibold"><?= (int)$o['devueltas'] > 0 ? 'Corrección solicitada por Supervisión' : 'Captura técnica pendiente' ?></div>
                                        <div class="cy-meta"><?= $llenadas ?> de <?= $total ?> ensayos capturados · <?= (int)$o['aprobadas'] ?> aprobados.</div>
                                    </div>
                                    <?php if ($idAcc > 0): ?>
                                        <a href="/Cycsa/publico/captura-tecnica/llenar?id_detalle=<?= $idAcc ?>" class="btn btn-primary">
                                            <?= (int)$o['devueltas'] > 0 ? 'Corregir datos' : 'Llenar datos' ?>
                                        </a>
                                    <?php endif; ?>
                                </div>

                                <?php foreach ($o['items'] as $it):
                                    $pillIt = $resolverPillEnsayoCT((string)$it['estado']);
                                    $idDet = (int)$it['id'];
                                ?>
                                <div class="cy-row">
                                    <div class="flex-grow-1 min-w-0">
                                        <div class="cy-assay-name"><?= htmlspecialchars((string)$it['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?></div>
                                        <div class="cy-assay-norm">
                                            <?= htmlspecialchars((string)($it['norma_astm'] !== '' ? $it['norma_astm'] : 'Ensayo de laboratorio'), ENT_QUOTES, 'UTF-8') ?> · <?= (int)$it['cantidad'] ?> <?= (int)$it['cantidad'] === 1 ? 'muestra' : 'muestras' ?>
                                        </div>
                                    </div>
                                    <span class="cy-pill <?= htmlspecialchars($pillIt['clase'], ENT_QUOTES, 'UTF-8') ?>"><?= htmlspecialchars($pillIt['texto'], ENT_QUOTES, 'UTF-8') ?></span>
                                    <?php if ($it['estado'] === 'devuelta'): ?>
                                        <button type="button"
                                                class="btn btn-outline-secondary btn-sm"
                                                onclick="verObsDevolucionCT(<?= $idDet ?>, '<?= htmlspecialchars(addslashes((string)$it['descripcion_ensayo']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes((string)$it['motivo_devolucion']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes((string)$it['usuario_revisor']), ENT_QUOTES, 'UTF-8') ?>', '<?= htmlspecialchars(addslashes((string)$it['fecha_revision']), ENT_QUOTES, 'UTF-8') ?>')">
                                            Observación
                                        </button>
                                    <?php endif; ?>
                                    <a href="/Cycsa/publico/captura-tecnica/llenar?id_detalle=<?= $idDet ?>" class="btn btn-outline-secondary btn-sm">
                                        <?= $it['estado'] === 'aprobada' ? 'Ver captura' : ($it['tiene_resultados'] ? 'Editar datos' : 'Llenar datos') ?>
                                    </a>
                                </div>
                                <?php endforeach; ?>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>

                    <?php if (empty($ordenes)): ?>
                    <tr>
                        <td colspan="5" class="text-center py-5">
                            <span class="cy-meta">No hay ensayos pendientes en esta bandeja de Captura Técnica.</span>
                        </td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- PANEL LATERAL OFFCANVAS (640px, mismo diseño sobrio que Operaciones) -->
<div class="lims-drawer-fallback-backdrop" id="ct-drawer-overlay" onclick="cerrarDrawerCapturaTecnica()"></div>
<aside class="offcanvas offcanvas-end" tabindex="-1" id="ct-drawer" aria-labelledby="ct-drawer-title">
    <div class="offcanvas-header">
        <div class="w-100">
            <div class="d-flex justify-content-between align-items-start gap-3">
                <h2 class="cy-order-code" id="ct-drawer-title">Orden</h2>
                <button type="button" class="btn-close" onclick="cerrarDrawerCapturaTecnica()" aria-label="Cerrar"></button>
            </div>
            <div class="cy-panel-meta-line">Captura Técnica</div>
        </div>
    </div>
    <div class="offcanvas-body" id="ct-drawer-body"></div>
</aside>

<!-- MODAL OBSERVACIÓN DE DEVOLUCIÓN -->
<div id="modalObsCT" class="modal-premium" style="display: none;">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Observación de Supervisión</h3>
            <button type="button" onclick="cerrarModalObsCT()" class="btn-cerrar" aria-label="Cerrar">&times;</button>
        </div>
        <p class="mb-2" style="font-size: 14px;">Ensayo: <strong id="ct_obs_ensayo"></strong></p>
        <div class="cy-note mt-0 mb-3">
            <div id="ct_obs_motivo" style="color: var(--cy-text); white-space: pre-wrap;"></div>
        </div>
        <div class="d-flex justify-content-between cy-meta mb-3">
            <span>Revisor: <strong id="ct_obs_supervisor"></strong></span>
            <span>Fecha: <strong id="ct_obs_fecha"></strong></span>
        </div>
        <div class="d-flex justify-content-between align-items-center pt-2 border-top">
            <a id="ct_obs_btn_ir" href="#" class="btn btn-primary btn-sm">Ir a corregir datos</a>
            <button type="button" onclick="cerrarModalObsCT()" class="btn btn-outline-secondary btn-sm">Cerrar</button>
        </div>
    </div>
</div>

<script>
    let ctDrawerBs = null;
    function abrirDrawerCapturaTecnica(idOS) {
        const trRow = document.getElementById('ct-row-' + idOS);
        const trDet = document.getElementById('ct-detail-' + idOS);
        const drawer = document.getElementById('ct-drawer');
        const body = document.getElementById('ct-drawer-body');
        if (!trRow || !trDet || !drawer || !body) return;

        document.querySelectorAll('#tabla-captura-tecnica-os tbody tr.is-selected').forEach(r => r.classList.remove('is-selected'));
        trRow.classList.add('is-selected');

        const codeEl = trRow.querySelector('.lims-os-code');
        const titleEl = document.getElementById('ct-drawer-title');
        if (titleEl && codeEl) titleEl.textContent = codeEl.textContent.trim();

        const card = trDet.querySelector('.detalle-ct-card');
        body.innerHTML = card ? card.innerHTML : '';

        if (window.bootstrap && window.bootstrap.Offcanvas) {
            ctDrawerBs = window.bootstrap.Offcanvas.getOrCreateInstance(drawer);
            ctDrawerBs.show();
        } else {
            drawer.classList.add('show');
            drawer.style.visibility = 'visible';
            const ov = document.getElementById('ct-drawer-overlay');
            if (ov) ov.classList.add('active');
        }
    }

    function cerrarDrawerCapturaTecnica() {
        const drawer = document.getElementById('ct-drawer');
        if (ctDrawerBs) {
            ctDrawerBs.hide();
        } else if (drawer) {
            drawer.classList.remove('show');
            drawer.style.visibility = 'hidden';
            const ov = document.getElementById('ct-drawer-overlay');
            if (ov) ov.classList.remove('active');
        }
    }

    function verObsDevolucionCT(idDet, ensayo, motivo, sup, fecha) {
        document.getElementById('ct_obs_ensayo').textContent = ensayo || '';
        document.getElementById('ct_obs_motivo').textContent = motivo || 'Revisar mediciones capturadas.';
        document.getElementById('ct_obs_supervisor').textContent = sup || 'Supervisor';
        document.getElementById('ct_obs_fecha').textContent = fecha || '';
        document.getElementById('ct_obs_btn_ir').href = '/Cycsa/publico/captura-tecnica/llenar?id_detalle=' + encodeURIComponent(idDet);
        document.getElementById('modalObsCT').style.display = 'block';
    }

    function cerrarModalObsCT() {
        document.getElementById('modalObsCT').style.display = 'none';
    }

    document.addEventListener('DOMContentLoaded', () => {
        document.querySelectorAll('#tabla-captura-tecnica-os tbody tr.fila-ct-os').forEach(tr => {
            tr.addEventListener('click', (e) => {
                if (e.target.closest('a, button')) return;
                const id = tr.id.replace('ct-row-', '');
                if (id) abrirDrawerCapturaTecnica(id);
            });
        });

        const searchInput = document.getElementById('ct-search');
        if (searchInput) {
            searchInput.addEventListener('input', () => {
                const q = searchInput.value.trim().toLowerCase();
                document.querySelectorAll('#tabla-captura-tecnica-os tbody tr.fila-ct-os').forEach(tr => {
                    const txt = tr.textContent.toLowerCase();
                    tr.style.display = (!q || txt.includes(q)) ? '' : 'none';
                });
            });
        }
    });
</script>
