<?php
// Vista de Operaciones: Visualización del Documento Oficial RT-FM-22 ya terminado con TODOS los datos (Recepción + Técnico)
$esSupervisor = function_exists('esSupervisorOperacionesCYCSA')
    ? esSupervisorOperacionesCYCSA()
    : in_array((int)($_SESSION['usuario_rol'] ?? 0), [1, 2, 3], true);

$idDetalleActivo = (int)($detalle['id'] ?? ($idDetalle ?? 0));
$idOsActivo = (int)($detalle['id_os'] ?? 0);
$archivoMd = (string)($detalle['archivo_markdown'] ?? '');
$revisionInfo = obtenerEstadoRevisionMatriz($detalle['resultados_json'] ?? '');
$ccMatrizInfo = obtenerEstadoControlCalidadMatriz($detalle['resultados_json'] ?? '');
$csrfToken = (string)($_SESSION['csrf_token'] ?? '');

$filas = $filasDocumento ?? [];
$columnas = $columnasDocumento ?? [];
$columnMethods = (isset($schemaInfo['column_methods']) && is_array($schemaInfo['column_methods'])) ? $schemaInfo['column_methods'] : [];
$tieneMetodos = !empty(array_filter($columnMethods, static fn($m) => trim((string)$m) !== ''));
$aliases = (isset($schemaInfo['column_aliases']) && is_array($schemaInfo['column_aliases'])) ? $schemaInfo['column_aliases'] : [];

$cliInfNom = (string)($metadatos['cliente_nombre'] ?? ($detalle['cliente_nombre'] ?? ''));
$cliMail = (string)($detalle['cliente_email'] ?? '');
$codOS = (string)($detalle['codigo_os'] ?? '');
$descEns = (string)($detalle['descripcion_ensayo'] ?? '');
$versionActualNum = (int)($versionActual ?? 1);
$disclaimerDefault = (string)($schemaInfo['disclaimer'] ?? 'Consultoría y Construcción SA.CYCSA es responsable únicamente de la exactitud de los resultados realizados en las muestras recibidas y tomadas en campo. No se debe de reproducir este informe de ensayo sin la aprobación formal de Consultoría y Construcción SA. CYCSA. ** Información Proporcionada por el cliente');
$disclaimerOficial = (array_key_exists('disclaimer_personalizado', $metadatos) && $metadatos['disclaimer_personalizado'] !== null)
    ? (string)$metadatos['disclaimer_personalizado']
    : $disclaimerDefault;
$notasDefault = (isset($schemaInfo['notas']) && is_array($schemaInfo['notas'])) ? $schemaInfo['notas'] : [];
$notasOficiales = !empty($metadatos['notas_informe_personalizadas'])
    ? (is_array($metadatos['notas_informe'] ?? null) ? $metadatos['notas_informe'] : [])
    : $notasDefault;
$notasTextoEditable = implode("\n", array_map(static fn($n) => trim((string)$n), $notasOficiales));
?>
<link rel="stylesheet" href="/Cycsa/publico/css/cycsa-sobrio.css">
<style>
    .doc-term-wrap { width: 100%; max-width: 100%; margin: 0; padding: 0 0 40px 0; box-sizing: border-box; }
    .doc-top-bar {
        position: sticky; top: 0; z-index: 1015;
        background: #ffffff; border-bottom: 1px solid #e2e8f0;
        padding: 12px 18px; margin: 0 0 18px 0; border-radius: 10px;
        display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px;
    }
    .doc-sheet {
        background: #ffffff; border: 1px solid #cbd5e1; border-radius: 10px;
        padding: 26px 28px; box-shadow: 0 2px 10px rgba(15, 23, 42, 0.04);
    }
    .doc-header {
        display: flex; justify-content: space-between; align-items: center; gap: 16px; flex-wrap: wrap;
        border-bottom: 2px solid #1F3864; padding-bottom: 16px; margin-bottom: 20px;
    }
    .doc-meta-grid {
        display: grid; grid-template-columns: repeat(auto-fit, minmax(340px, 1fr));
        gap: 10px 28px; margin-bottom: 22px; padding: 16px 18px;
        background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px;
    }
    .doc-meta-item { display: flex; gap: 8px; font-size: 13.5px; line-height: 1.45; }
    .doc-meta-label { font-weight: 700; color: #334155; min-width: 190px; flex-shrink: 0; }
    .doc-meta-val { color: #0f172a; word-break: break-word; }
    .doc-table-wrap { width: 100%; overflow-x: auto; border: 1px solid #cbd5e1; border-radius: 8px; }
    .doc-table { width: 100%; border-collapse: collapse; font-size: 13.5px; }
    .doc-table thead th {
        background: #f1f5f9; color: #0f172a; font-weight: 700; padding: 10px 10px;
        border-bottom: 1px solid #cbd5e1; border-right: 1px solid #e2e8f0; text-align: center; white-space: nowrap;
    }
    .doc-table thead tr.row-origen th { font-size: 11px; padding: 5px 8px; text-transform: uppercase; letter-spacing: 0.03em; }
    .doc-table thead tr.row-metodos th { font-size: 11.5px; background: #f8fafc; color: #1F3864; font-weight: 600; padding: 6px 8px; }
    .doc-table tbody td {
        padding: 10px 10px; border-bottom: 1px solid #e2e8f0; border-right: 1px solid #f1f5f9;
        color: #111827; text-align: center; white-space: nowrap;
    }
    .doc-table tbody tr:nth-child(even) { background: #fafafa; }
    .doc-col-rec-badge { background: #fff7ed !important; color: #9a3412 !important; }
    .doc-col-tec-badge { background: #eff6ff !important; color: #1e40af !important; }
    .doc-col-id-badge { background: #f1f5f9 !important; color: #334155 !important; }
    .doc-footer-notes { margin-top: 18px; padding-top: 14px; border-top: 1px solid #e2e8f0; font-size: 12.5px; color: #475569; line-height: 1.55; }
    .modal-premium { display: none; position: fixed; z-index: 1060; inset: 0; overflow: auto; background: rgba(17, 24, 39, 0.35); }
    .modal-premium-content { background: #ffffff; margin: 5% auto; padding: 24px 28px; border: 1px solid #e2e8f0; border-radius: 10px; width: min(520px, 94vw); }
    .btn-cerrar { background: none; border: 0; color: #64748b; font-size: 20px; line-height: 1; cursor: pointer; padding: 4px 8px; }
</style>

<div class="doc-term-wrap">
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

    <!-- Barra superior de acciones sobre el documento terminado -->
    <div class="doc-top-bar">
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <a href="<?= $idOsActivo > 0 ? '/Cycsa/publico/operaciones/ver?id_os=' . $idOsActivo : '/Cycsa/publico/operaciones' ?>" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-arrow-left me-1"></i> Volver a Operaciones
            </a>
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap">
                    <strong style="font-size: 16px; color: #111827;"><?= htmlspecialchars($codOS, ENT_QUOTES, 'UTF-8') ?></strong>
                    <span class="cy-pill <?= ($revisionInfo['estado'] === 'aprobada') ? 'cy-pill-ok' : (($revisionInfo['estado'] === 'devuelta') ? 'cy-pill-danger' : (($revisionInfo['estado'] === 'en_revision') ? 'cy-pill-warn' : 'cy-pill-pend')) ?>">
                        <?= htmlspecialchars((string)($revisionInfo['estado_label'] ?? 'Pendiente'), ENT_QUOTES, 'UTF-8') ?>
                    </span>
                    <span class="cy-meta">Documento consolidado · v<?= $versionActualNum ?></span>
                </div>
                <div class="cy-meta"><?= htmlspecialchars($descEns, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
        </div>

        <div class="d-flex align-items-center gap-2 flex-wrap">
            <?php if ($esSupervisor && $revisionInfo['tiene_resultados'] && $revisionInfo['estado'] !== 'aprobada'): ?>
                <?php if ($ccMatrizInfo['tiene_replicas'] && !$ccMatrizInfo['puede_aprobar']): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm" disabled title="<?= htmlspecialchars((string)$ccMatrizInfo['motivo_bloqueo'], ENT_QUOTES, 'UTF-8') ?>">
                        <i class="fa-solid fa-lock me-1"></i> Aprobar (CC pendiente)
                    </button>
                <?php else: ?>
                    <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('modalDocAprobar').style.display='block'">
                        <i class="fa-solid fa-circle-check me-1"></i> Aprobar documento
                    </button>
                <?php endif; ?>
                <?php if ($revisionInfo['estado'] !== 'devuelta'): ?>
                    <button type="button" class="btn btn-outline-secondary btn-sm" onclick="document.getElementById('modalDocDevolver').style.display='block'">
                        <i class="fa-solid fa-rotate-left me-1"></i> Devolver al técnico
                    </button>
                <?php endif; ?>
            <?php endif; ?>

            <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= $idDetalleActivo ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-print me-1"></i> Imprimir RT-FM-22
            </a>
            <a href="/Cycsa/publico/operaciones/descargar-matriz-pdf?id_detalle=<?= $idDetalleActivo ?>" target="_blank" class="btn btn-outline-secondary btn-sm">
                <i class="fa-solid fa-file-pdf me-1"></i> Descargar PDF
            </a>

            <?php if ($revisionInfo['estado'] === 'aprobada'): ?>
                <button type="button" class="btn btn-primary btn-sm" onclick="document.getElementById('modalDocEnviar').style.display='block'">
                    <i class="fa-solid fa-paper-plane me-1"></i> Enviar al cliente
                </button>
            <?php endif; ?>

            <?php if ($esSupervisor): ?>
                <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $idDetalleActivo ?>" class="btn btn-outline-secondary btn-sm" title="Editar encabezado o ajustes administrativos">
                    <i class="fa-solid fa-sliders me-1"></i> Editar encabezado
                </a>
            <?php endif; ?>
        </div>
    </div>

    <!-- Hoja Oficial Completa RT-FM-22 (Visualización con TODOS los datos: Recepción + Técnico) -->
    <div class="doc-sheet" id="documento-oficial-terminado">
        <div class="doc-header">
            <div class="d-flex align-items-center gap-3">
                <img src="/Cycsa/publico/img/logo.png" alt="CYCSA" style="height: 52px; max-width: 190px; object-fit: contain;">
                <?php if (!empty($metadatos['logo_acreditacion'])): ?>
                    <div style="border-left: 2px solid #cbd5e1; padding-left: 12px;">
                        <img src="<?= htmlspecialchars((string)$metadatos['logo_acreditacion'], ENT_QUOTES, 'UTF-8') ?>" alt="Acreditación" style="height: 52px; max-width: 140px; object-fit: contain;">
                    </div>
                <?php endif; ?>
            </div>
            <div style="text-align: center; flex: 1; min-width: 240px;">
                <h1 style="margin: 0; font-size: 20px; font-weight: 800; color: #1F3864;">
                    <?= htmlspecialchars((string)($schemaInfo['titulo_informe'] ?? 'INFORME DE ENSAYO'), ENT_QUOTES, 'UTF-8') ?>
                </h1>
                <div style="font-size: 14px; font-weight: 700; color: #103487; margin-top: 2px;">
                    <?= htmlspecialchars((string)($codigoInformeConsecutivo ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div style="font-size: 12.5px; color: #475569; margin-top: 2px;">
                    <?= htmlspecialchars((string)($schemaInfo['subtitulo_laboratorio'] ?? 'Laboratorio de Ensayos y Control de Calidad'), ENT_QUOTES, 'UTF-8') ?> · O/S: <strong><?= htmlspecialchars($codOS, ENT_QUOTES, 'UTF-8') ?></strong>
                </div>
                <div style="font-size: 12.5px; color: #64748b;">
                    Norma técnica: <?= htmlspecialchars((string)($schemaInfo['norma'] ?? ($detalle['norma_astm'] ?? '')), ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <div style="text-align: right;">
                <div style="display: inline-block; background: #1F3864; color: #ffffff; padding: 5px 12px; border-radius: 6px; font-weight: 700; font-size: 12.5px;">
                    <?= htmlspecialchars((string)($metadatos['codigo_formato'] ?? 'CYCSA-RT-FM-22'), ENT_QUOTES, 'UTF-8') ?>
                </div>
                <div style="font-size: 11.5px; color: #64748b; margin-top: 4px;">ISO/IEC 17025:2017</div>
            </div>
        </div>

        <!-- Metadatos completos del informe -->
        <div class="doc-meta-grid">
            <div class="doc-meta-item"><span class="doc-meta-label">Nombre del cliente:</span><span class="doc-meta-val"><?= htmlspecialchars((string)($metadatos['cliente_nombre'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="doc-meta-item"><span class="doc-meta-label">Proyecto:</span><span class="doc-meta-val"><?= htmlspecialchars((string)($metadatos['proyecto'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="doc-meta-item"><span class="doc-meta-label">Dirección:</span><span class="doc-meta-val"><?= htmlspecialchars((string)($metadatos['cliente_direccion'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="doc-meta-item"><span class="doc-meta-label">Ubicación:</span><span class="doc-meta-val"><?= htmlspecialchars((string)($metadatos['ubicacion'] ?? '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="doc-meta-item"><span class="doc-meta-label">Fecha recepción ítems:</span><span class="doc-meta-val"><?= htmlspecialchars((string)($metadatos['fecha_ingreso'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="doc-meta-item"><span class="doc-meta-label">Fecha muestreo:</span><span class="doc-meta-val"><?= htmlspecialchars((string)($metadatos['fecha_muestreo'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="doc-meta-item"><span class="doc-meta-label">Tipo de muestra:</span><span class="doc-meta-val"><?= htmlspecialchars((string)($metadatos['tipo_muestra'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="doc-meta-item"><span class="doc-meta-label">Fecha de ejecución:</span><span class="doc-meta-val"><?= htmlspecialchars((string)($metadatos['fecha_ejecucion'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="doc-meta-item"><span class="doc-meta-label">Procedimiento de muestreo:</span><span class="doc-meta-val"><?= htmlspecialchars((string)($metadatos['procedimiento_muestreo'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="doc-meta-item"><span class="doc-meta-label">Fecha emisión informe:</span><span class="doc-meta-val"><?= htmlspecialchars((string)($metadatos['fecha_emision'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="doc-meta-item"><span class="doc-meta-label">Ensayo realizado:</span><span class="doc-meta-val"><?= htmlspecialchars((string)($metadatos['ensayo_realizado'] ?: $descEns), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="doc-meta-item"><span class="doc-meta-label">Muestra tomada por:</span><span class="doc-meta-val"><?= htmlspecialchars((string)($metadatos['muestra_tomada_por'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="doc-meta-item"><span class="doc-meta-label">Método de muestreo:</span><span class="doc-meta-val"><?= htmlspecialchars((string)($metadatos['metodo_muestreo'] ?: '—'), ENT_QUOTES, 'UTF-8') ?></span></div>
            <div class="doc-meta-item"><span class="doc-meta-label">Ensayado por:</span><span class="doc-meta-val"><?= htmlspecialchars((string)($metadatos['ensayado_por'] ?? 'Personal Técnico de Laboratorio'), ENT_QUOTES, 'UTF-8') ?></span></div>
        </div>

        <!-- Apartado Editable de Cláusula y Notas del Informe (Arriba de la Tabla) -->
        <div id="panel-notas-documento-arriba" style="margin-bottom: 18px; padding: 14px 18px; background: #f8fafc; border: 1px solid #cbd5e1; border-radius: 8px;">
            <form method="POST" action="/Cycsa/publico/operaciones/guardar-notas-documento">
                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
                <input type="hidden" name="id_detalle" value="<?= $idDetalleActivo ?>">
                <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/documento?id_detalle=<?= $idDetalleActivo ?>">
                <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-2">
                    <div style="font-size: 13.5px; font-weight: 700; color: #1F3864;">
                        <i class="fa-solid fa-pen-to-square me-1"></i> Notas y Cláusula del Informe (Editable)
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm">
                        <i class="fa-solid fa-floppy-disk me-1"></i> Guardar notas
                    </button>
                </div>
                <div class="row g-2">
                    <div class="col-12 col-lg-7">
                        <label for="doc_disclaimer_personalizado" class="form-label mb-1" style="font-size: 12.5px; font-weight: 600; color: #334155;">Cláusula oficial / Responsabilidad</label>
                        <textarea id="doc_disclaimer_personalizado" name="disclaimer_personalizado" rows="2" class="form-control form-control-sm" style="font-size: 12.5px;"><?= htmlspecialchars($disclaimerOficial, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div class="col-12 col-lg-5">
                        <label for="doc_notas_informe" class="form-label mb-1" style="font-size: 12.5px; font-weight: 600; color: #334155;">Notas del ensayo (una por línea)</label>
                        <textarea id="doc_notas_informe" name="notas_informe" rows="2" class="form-control form-control-sm" style="font-size: 12.5px;" placeholder='Ej. Nota: Ensayos realizados in Situ: Ensayo "CYCSA-PE-09" "CYCSA-PE-27"'><?= htmlspecialchars($notasTextoEditable, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
                <?php if (!empty($metadatos['equipo_marca']) || !empty($metadatos['equipo_modelo']) || !empty($metadatos['nota_aditivos']) || !empty($metadatos['observaciones_pendientes'])): ?>
                    <div class="mt-2 pt-2 border-top" style="font-size: 12.5px; color: #475569; line-height: 1.5;">
                        <?php if (!empty($metadatos['equipo_marca']) || !empty($metadatos['equipo_modelo'])): ?>
                            <div>
                                <strong>Nota 1:</strong> Muestra tomada con equipo <?= htmlspecialchars((string)($metadatos['equipo_marca'] ?? ''), ENT_QUOTES, 'UTF-8') ?>,
                                Modelo: <?= htmlspecialchars((string)($metadatos['equipo_modelo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>,
                                N/S: <?= htmlspecialchars((string)($metadatos['equipo_serie'] ?? ''), ENT_QUOTES, 'UTF-8') ?> <?= htmlspecialchars((string)($metadatos['equipo_codigo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>.
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($metadatos['nota_aditivos'])): ?>
                            <div><strong>Aditivos:</strong> <?= htmlspecialchars((string)$metadatos['nota_aditivos'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                        <?php if (!empty($metadatos['observaciones_pendientes'])): ?>
                            <div><strong>Observaciones:</strong> <?= htmlspecialchars((string)$metadatos['observaciones_pendientes'], ENT_QUOTES, 'UTF-8') ?></div>
                        <?php endif; ?>
                    </div>
                <?php endif; ?>
            </form>
        </div>

        <!-- Tabla Oficial Completa con TODOS los datos unidos (Recepción + Técnico) -->
        <div class="doc-table-wrap">
            <table class="doc-table" id="tabla-documento-terminado">
                <thead>
                    <tr class="row-origen">
                        <th class="doc-col-id-badge">N°</th>
                        <?php foreach ($columnas as $col):
                            $rolCol = clasificarColumnaRecepcionOTecnico((string)$col, $archivoMd);
                            $clsBadge = $rolCol === 'recepcion' ? 'doc-col-rec-badge' : ($rolCol === 'identidad' ? 'doc-col-id-badge' : 'doc-col-tec-badge');
                            $lblRol = $rolCol === 'recepcion' ? 'Recepción (O/S)' : ($rolCol === 'identidad' ? 'Muestra' : 'Técnico (Lab)');
                        ?>
                            <th class="<?= $clsBadge ?>"><?= $lblRol ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <?php if ($tieneMetodos): ?>
                    <tr class="row-metodos">
                        <th colspan="3" style="text-align: left;">Método de ensayo</th>
                        <?php foreach (array_slice($columnas, 2) as $colM): ?>
                            <th><?= htmlspecialchars((string)($columnMethods[$colM] ?? '—'), ENT_QUOTES, 'UTF-8') ?></th>
                        <?php endforeach; ?>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th style="width: 44px;">N°</th>
                        <?php foreach ($columnas as $col): ?>
                            <th><?= htmlspecialchars((string)$col, ENT_QUOTES, 'UTF-8') ?></th>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($filas)): ?>
                    <tr>
                        <td colspan="<?= count($columnas) + 1 ?>" style="padding: 28px; color: #64748b;">
                            Aún no se registran filas de resultados para este ensayo.
                        </td>
                    </tr>
                    <?php else: ?>
                        <?php foreach ($filas as $idxF => $fila): ?>
                        <tr>
                            <td style="font-weight: 700; color: #64748b;"><?= $idxF + 1 ?></td>
                            <?php foreach ($columnas as $col):
                                $calcCol = $aliases[$col] ?? $col;
                                $val = buscarValorEnFilaPHP($fila, (string)$col);
                                if ($val === '' && $calcCol !== $col) {
                                    $val = buscarValorEnFilaPHP($fila, (string)$calcCol);
                                }
                            ?>
                                <td style="<?= $col === 'Código laboratorio' ? 'font-weight: 700; color: #1F3864;' : '' ?>">
                                    <?= htmlspecialchars($val !== '' ? $val : '—', ENT_QUOTES, 'UTF-8') ?>
                                </td>
                            <?php endforeach; ?>
                        </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- Firmante y Revisión -->
        <div class="doc-footer-notes">
            <div class="d-flex justify-content-between flex-wrap gap-2">
                <span>Firmante oficial: <strong><?= htmlspecialchars((string)($schemaInfo['firmante_nombre'] ?? 'Ing. Noel Quintana Lira'), ENT_QUOTES, 'UTF-8') ?></strong> — <?= htmlspecialchars((string)($schemaInfo['firmante_cargo'] ?? 'Gerente General'), ENT_QUOTES, 'UTF-8') ?></span>
                <?php if (!empty($revisionInfo['usuario_revisor'])): ?>
                    <span>Revisado por: <strong><?= htmlspecialchars((string)$revisionInfo['usuario_revisor'], ENT_QUOTES, 'UTF-8') ?></strong> (<?= htmlspecialchars((string)($revisionInfo['fecha_revision'] ?? ''), ENT_QUOTES, 'UTF-8') ?>)</span>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- MODALES DE APROBACIÓN / DEVOLUCIÓN / ENVÍO -->
<?php if ($esSupervisor): ?>
<div id="modalDocAprobar" class="modal-premium" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Aprobar documento oficial</h3>
            <button type="button" onclick="document.getElementById('modalDocAprobar').style.display='none'" class="btn-cerrar">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/aprobar-matriz-producto">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_detalle" value="<?= $idDetalleActivo ?>">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/documento?id_detalle=<?= $idDetalleActivo ?>">
            <p class="mb-3" style="font-size: 14px;">¿Confirma la aprobación técnica de los resultados y cálculos para <strong><?= htmlspecialchars($descEns, ENT_QUOTES, 'UTF-8') ?></strong>?</p>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Nota de aprobación (opcional)</label>
                <input type="text" name="nota_aprobacion" class="form-control" placeholder="Conforme con tolerancias y especificaciones ASTM.">
            </div>
            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" onclick="document.getElementById('modalDocAprobar').style.display='none'" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Aprobar documento</button>
            </div>
        </form>
    </div>
</div>

<div id="modalDocDevolver" class="modal-premium" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Devolver ensayo al técnico</h3>
            <button type="button" onclick="document.getElementById('modalDocDevolver').style.display='none'" class="btn-cerrar">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/devolver-matriz-producto">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_detalle" value="<?= $idDetalleActivo ?>">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/documento?id_detalle=<?= $idDetalleActivo ?>">
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Observaciones para el técnico *</label>
                <textarea name="motivo_devolucion" rows="4" required class="form-control" placeholder="Detalle las correcciones necesarias para Captura Técnica..."></textarea>
            </div>
            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" onclick="document.getElementById('modalDocDevolver').style.display='none'" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Devolver al técnico</button>
            </div>
        </form>
    </div>
</div>
<?php endif; ?>

<div id="modalDocEnviar" class="modal-premium" onclick="if(event.target===this)this.style.display='none'">
    <div class="modal-premium-content">
        <div class="d-flex justify-content-between align-items-center mb-3 pb-2 border-bottom">
            <h3 class="m-0" style="font-size: 17px; font-weight: 600;">Enviar informe al cliente</h3>
            <button type="button" onclick="document.getElementById('modalDocEnviar').style.display='none'" class="btn-cerrar">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/enviar-matriz-cliente">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken, ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_detalle" value="<?= $idDetalleActivo ?>">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/documento?id_detalle=<?= $idDetalleActivo ?>">
            <div class="cy-note mt-0 mb-3">
                <div class="fw-semibold text-dark"><?= htmlspecialchars($descEns, ENT_QUOTES, 'UTF-8') ?></div>
                <div class="cy-meta">Orden: <?= htmlspecialchars($codOS, ENT_QUOTES, 'UTF-8') ?> · Cliente: <?= htmlspecialchars($cliInfNom, ENT_QUOTES, 'UTF-8') ?></div>
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Correo(s) del destinatario *</label>
                <input type="text" name="destinatario" class="form-control" required value="<?= htmlspecialchars($cliMail, ENT_QUOTES, 'UTF-8') ?>" placeholder="cliente@empresa.com, copia@empresa.com">
            </div>
            <div class="mb-3">
                <label class="form-label" style="font-size: 13px; font-weight: 500;">Asunto del correo *</label>
                <input type="text" name="asunto" class="form-control" required value="<?= htmlspecialchars('Informe Oficial de Ensayo CYCSA - ' . $codOS . ' (' . $descEns . ')', ENT_QUOTES, 'UTF-8') ?>">
            </div>
            <div class="d-flex justify-content-end gap-2 pt-2 border-top">
                <button type="button" onclick="document.getElementById('modalDocEnviar').style.display='none'" class="btn btn-outline-secondary btn-sm">Cancelar</button>
                <button type="submit" class="btn btn-primary btn-sm">Enviar por correo</button>
            </div>
        </form>
    </div>
</div>
