<?php
// Vista de Captura de Matriz Técnica - Dinámica y Específica para los 21 Formatos CYCSA
$rolUsuario = (int)($_SESSION['usuario_rol'] ?? 0);
$esLaboratorista = ($rolUsuario === 6);
$esSupervisor = in_array($rolUsuario, [1, 2, 3]);
$revisionInfo = obtenerEstadoRevisionMatriz($detalle['resultados_json'] ?? '');
$archivoMd = $detalle['archivo_markdown'] ?? '';
$isGranulometria = (strpos($archivoMd, 'granulometria') !== false || strpos($archivoMd, 'granulomnetria') !== false);

$listaVersiones = $listaVersiones ?? [];
$versionActualNum = (int)($versionActual ?? 1);
$versionSeleccionadaNum = (int)($versionSolicitada ?? $versionActualNum);
$esModoHistorico = !empty($esHistorica);
$totalVersiones = count($listaVersiones);

$schemaInfo = $schemaInfo ?? obtenerEsquemaPlantillaEnsayo($archivoMd, isset($detalle['formato_id']) ? (int)$detalle['formato_id'] : null);
if (empty($metadatos)) {
    $metadatos = resolverMetadatosEnsayo($detalle, $schemaInfo);
}
$codigoFormatoOficial = $metadatos['codigo_formato'];
$ensayoTituloOficial = $metadatos['ensayo_realizado'];
$metodoMuestreoOficial = $metadatos['metodo_muestreo'];
$tipoMuestraOficial = $metadatos['tipo_muestra'];
$tomaMuestraOficial = $metadatos['muestra_tomada_por'];

$datosParaCodigo = [];
if (!empty($detalle['resultados_json'])) {
    $dec = json_decode($detalle['resultados_json'], true);
    if (is_array($dec)) {
        $datosParaCodigo = $dec['filas'] ?? $dec;
    }
}
if (empty($datosParaCodigo) && !empty($muestrasSeteadas)) {
    $datosParaCodigo = $muestrasSeteadas;
}
$codigoInformeConsecutivo = generarCodigoInformeEnsayo($datosParaCodigo, $metadatos['fecha_muestreo'] ?? ($metadatos['fecha_ingreso'] ?? null), $metadatos['tipo_muestra'] ?? ($detalle['descripcion_ensayo'] ?? ''));
?>
<style>
    :root {
        --cycsa-azul: #103487;
        --cycsa-azul-light: #eef2ff;
        --cycsa-azul-hover: #0c2766;
        --cycsa-rojo: #e31837;
        --color-success: #10b981;
        --color-slate-50: #f8fafc;
        --color-slate-100: #f1f5f9;
        --color-slate-200: #e2e8f0;
        --color-slate-300: #cbd5e1;
        --color-slate-600: #475569;
        --color-slate-700: #334155;
        --color-slate-800: #1e293b;
        --color-slate-900: #0f172a;
    }

    .matriz-container {
        width: 100%;
        max-width: 100%;
        margin: 0;
        padding: 10px 20px 60px 20px;
        box-sizing: border-box;
    }

    .matriz-top-bar {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 20px;
    }

    .btn-matriz-back {
        background: #f1f5f9;
        color: #475569;
        border: 1px solid #cbd5e1;
        padding: 9px 18px;
        border-radius: 8px;
        text-decoration: none;
        font-weight: 700;
        font-size: 13.5px;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        transition: all 0.2s;
    }
    .btn-matriz-back:hover {
        background: #e2e8f0;
        color: #0f172a;
    }

    .btn-matriz-primary {
        background: #103487;
        color: white;
        border: 1px solid #103487;
        padding: 10px 24px;
        border-radius: 8px;
        font-weight: 700;
        font-size: 13.5px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 8px;
        box-shadow: 0 4px 12px rgba(16, 52, 135, 0.25);
        transition: all 0.2s;
    }
    .btn-matriz-primary:hover {
        background: #0c2766;
        transform: translateY(-1px);
    }

    .btn-matriz-secondary {
        background: #f8fafc;
        color: #334155;
        border: 1px solid #cbd5e1;
        padding: 9px 16px;
        border-radius: 8px;
        font-weight: 600;
        font-size: 13px;
        cursor: pointer;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        transition: all 0.2s;
    }
    .btn-matriz-secondary:hover {
        background: #e2e8f0;
    }

    .seccion-form {
        background: white;
        padding: 25px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.03);
        margin-bottom: 20px;
        border-top: 4px solid var(--cycsa-azul);
        width: 100%;
        box-sizing: border-box;
    }
    .seccion-titulo {
        margin: 0 0 18px 0;
        color: #1e293b;
        font-size: 17px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 10px;
        border-bottom: 1px solid #e2e8f0;
        padding-bottom: 12px;
    }

    .matriz-pills-row {
        display: flex;
        flex-wrap: wrap;
        gap: 8px;
        margin-bottom: 14px;
    }
    .pill-item {
        font-size: 11.5px;
        font-weight: 700;
        padding: 4px 12px;
        border-radius: 20px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
    }
    .pill-os { background: #eff6ff; color: #1e40af; border: 1px solid #bfdbfe; font-family: monospace; }
    .pill-formato { background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1; }
    .pill-astm { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }

    /* Tarjeta Oficial de Metadatos del Informe de Ensayo (Captura 144015) */
    .informe-oficial-card {
        background: #ffffff;
        padding: 22px 26px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.04);
        margin-bottom: 20px;
        border: 1px solid #cbd5e1;
        border-top: 4px solid var(--cycsa-azul);
    }
    .informe-cabecera-row {
        display: flex;
        justify-content: space-between;
        align-items: center;
        border-bottom: 2px solid #0f172a;
        padding-bottom: 12px;
        margin-bottom: 18px;
        gap: 15px;
    }
    .informe-logo-box {
        flex-shrink: 0;
    }
    .informe-titulo-box {
        text-align: center;
        flex-grow: 1;
    }
    .informe-titulo-texto {
        font-size: 20px;
        font-weight: 800;
        letter-spacing: 0.8px;
        margin: 0;
        color: #0f172a;
        text-transform: uppercase;
    }
    .informe-subtitulo-texto {
        font-size: 12px;
        color: #475569;
        margin-top: 3px;
        font-weight: 600;
    }
    .informe-badge-box {
        text-align: right;
        flex-shrink: 0;
    }
    .codigo-formato-badge {
        background: #0f172a;
        color: #ffffff;
        font-weight: 800;
        font-size: 12.5px;
        padding: 5px 12px;
        border-radius: 4px;
        font-family: monospace;
        display: inline-block;
        letter-spacing: 0.5px;
    }
    .norma-iso-sub {
        font-size: 10.5px;
        font-weight: 700;
        color: #64748b;
        margin-top: 3px;
    }
    .informe-metadatos-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 25px;
    }
    @media (max-width: 900px) {
        .informe-metadatos-grid { grid-template-columns: 1fr; gap: 14px; }
        .informe-cabecera-row { flex-direction: column; text-align: center; }
        .informe-badge-box { text-align: center; }
    }
    .informe-col {
        display: flex;
        flex-direction: column;
        gap: 8px;
    }
    .meta-row {
        display: grid;
        grid-template-columns: 215px 1fr;
        align-items: center;
        gap: 8px;
    }
    @media (max-width: 1200px) {
        .meta-row { grid-template-columns: 185px 1fr; }
    }
    .meta-label-oficial {
        font-weight: 700;
        font-size: 13px;
        color: #000000;
        margin: 0;
        line-height: 1.25;
    }
    .meta-field-wrap {
        width: 100%;
    }
    .meta-input-web {
        width: 100%;
        border: 1.5px solid #cbd5e1;
        border-radius: 6px;
        padding: 5px 9px;
        font-size: 13px;
        color: #1e293b;
        background: #ffffff;
        box-sizing: border-box;
        transition: border-color 0.15s, box-shadow 0.15s;
        font-family: inherit;
    }
    .meta-input-web:focus {
        border-color: var(--cycsa-azul);
        box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.12);
        outline: none;
    }
    .meta-blindness {
        background: #eff6ff;
        border: 1px dashed #93c5fd;
        padding: 5px 9px;
        border-radius: 6px;
        color: #1d4ed8;
        font-size: 12px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Tabla de Captura */
    .tabla-matriz-wrapper {
        width: 100%;
        overflow-x: auto;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: white;
    }
    .tabla-matriz {
        width: 100%;
        min-width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }
    .tabla-matriz th {
        background: #f8fafc;
        color: #334155;
        font-weight: 700;
        padding: 12px 14px;
        text-align: left;
        border-bottom: 2px solid #cbd5e1;
        font-size: 12.5px;
        white-space: nowrap;
    }
    .tabla-matriz td {
        padding: 8px 10px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .tabla-matriz tbody tr:hover {
        background: #f8fafc;
    }

    .matriz-input {
        width: 100%;
        min-width: 105px;
        padding: 8px 12px;
        font-size: 13.5px;
        border: 1.5px solid #cbd5e1;
        border-radius: 6px;
        outline: none;
        box-sizing: border-box;
        font-family: inherit;
        background: white;
        transition: all 0.15s;
    }
    .matriz-input:focus {
        border-color: #103487;
        box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.12);
    }
    .matriz-input-locked {
        background: #f1f5f9;
        color: #0f172a;
        font-weight: 700;
        border-color: #e2e8f0;
        cursor: not-allowed;
    }
    .matriz-input-calculated {
        background: #f8fafc;
        color: #0f172a;
        font-weight: 700;
        border-color: #cbd5e1;
    }
</style>

<!-- TOP BAR (ESTILO COTIZACIONES) -->
<div style="margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div>
        <a href="/Cycsa/publico/operaciones" style="color: #6c757d; text-decoration: none; font-size: 14px; display: inline-flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-arrow-left"></i> Volver a Operaciones
        </a>
        <h2 style="margin: 8px 0 0 0; color: #1e293b; font-size: 22px; font-weight: 700; display: flex; align-items: center; gap: 10px;">
            <i class="fa-solid fa-flask-vial" style="color: var(--cycsa-azul);"></i> <?= htmlspecialchars($ensayoTituloOficial, ENT_QUOTES, 'UTF-8') ?>
        </h2>
    </div>

    <div style="display: flex; gap: 12px; align-items: center; flex-wrap: wrap;">
        <span style="background-color: <?= $revisionInfo['badge_bg'] ?>; color: <?= $revisionInfo['badge_color'] ?>; border: 1.5px solid <?= $revisionInfo['badge_border'] ?>; font-size: 12px; padding: 7px 15px; border-radius: 20px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
            <i class="<?= $revisionInfo['badge_icono'] ?>"></i> <?= htmlspecialchars($revisionInfo['estado_label']) ?>
        </span>

        <?php if ($esSupervisor && $revisionInfo['tiene_resultados']): ?>
            <?php if ($revisionInfo['estado'] !== 'aprobada'): ?>
                <button type="button" onclick="abrirModalAprobarEnMatriz()" class="btn-matriz-secondary" style="background: #ecfdf5; color: #047857; border: 1.5px solid #a7f3d0; font-weight: 700; cursor: pointer; padding: 10px 18px; display: inline-flex; align-items: center; gap: 6px;" title="Aprobar técnicamente los resultados y cálculos de este ensayo">
                    <i class="fa-solid fa-circle-check"></i> Aprobar Matriz
                </button>
            <?php endif; ?>
            <?php if ($revisionInfo['estado'] !== 'devuelta' && $revisionInfo['estado'] !== 'aprobada'): ?>
                <button type="button" onclick="abrirModalDevolverEnMatriz()" class="btn-matriz-secondary" style="background: #fff1f2; color: #be123c; border: 1.5px solid #fecdd3; font-weight: 700; cursor: pointer; padding: 10px 18px; display: inline-flex; align-items: center; gap: 6px;" title="Devolver matriz al laboratorista con observaciones para corrección">
                    <i class="fa-solid fa-rotate-left"></i> Devolver Matriz
                </button>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($esModoHistorico): ?>
            <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $detalle['id'] ?>" class="btn-matriz-primary" style="background:#2563eb; text-decoration:none;">
                <i class="fa-solid fa-pen-to-square"></i> Ir a Versión Actual (v<?= $versionActualNum ?>) para Modificar
            </a>
        <?php else: ?>
            <button type="button" onclick="guardarMatrizFullSubmit()" class="btn-matriz-primary">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Matriz del Producto
            </button>
        <?php endif; ?>

        <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= $detalle['id'] ?><?= $esModoHistorico ? '&version=' . $versionSeleccionadaNum : '' ?>" target="_blank" class="btn-matriz-secondary" style="background:#f8fafc; color:#103487; border:1.5px solid #bfdbfe; font-weight:700; text-decoration:none; padding:10px 18px; display:inline-flex; align-items:center; gap:8px;" title="Ver e imprimir formato técnico horizontal oficial con membrete CYCSA">
            <i class="fa-solid fa-print" style="color:#103487;"></i> Imprimir Matriz <?= $esModoHistorico ? "v{$versionSeleccionadaNum}" : "(Horizontal)" ?>
        </a>
    </div>
</div>


<!-- BARRA SUPERIOR DE HISTORIAL Y CONTROL DE VERSIONES (ISO/IEC 17025) -->
<div style="background: #ffffff; border: 1.5px solid #e2e8f0; border-radius: 8px; padding: 10px 16px; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 12px; box-shadow: 0 1px 4px rgba(0,0,0,0.04);">
    <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
        <span style="font-size: 11.5px; font-weight: 700; color: #475569; text-transform: uppercase; letter-spacing: 0.6px; display: inline-flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-code-branch" style="color: #103487; font-size: 12px;"></i> Revisiones de Matriz:
        </span>

        <!-- Lista de Chips de Versión -->
        <div style="display: inline-flex; gap: 6px; flex-wrap: wrap; align-items: center;">
            <?php if (!empty($listaVersiones)): ?>
                <?php foreach ($listaVersiones as $v): ?>
                    <?php 
                    $vNum = (int)($v['version'] ?? 1);
                    $esEstaSeleccionada = ($esModoHistorico && $versionSeleccionadaNum === $vNum);
                    $esDevuelta = (($v['estado'] ?? '') === 'devuelta');
                    $chipBg = $esEstaSeleccionada ? '#fef2f2' : '#f8fafc';
                    $chipColor = $esEstaSeleccionada ? '#991b1b' : ($esDevuelta ? '#7f1d1d' : '#475569');
                    $chipBorder = $esEstaSeleccionada ? '#dc2626' : '#e2e8f0';
                    $fmtVNum = sprintf('%02d', $vNum);
                    ?>
                    <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $detalle['id'] ?>&version=<?= $vNum ?>" 
                       style="padding: 4px 10px; font-size: 11.5px; font-weight: 600; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 5px; border: 1.5px solid <?= $chipBorder ?>; background: <?= $chipBg ?>; color: <?= $chipColor ?>; transition: all 0.15s;"
                       title="Ver snapshot histórico de Revisión <?= $fmtVNum ?> (<?= !empty($v['fecha']) ? date('d/m/Y H:i', strtotime($v['fecha'])) : '' ?>)">
                        <i class="fa-solid fa-clock-rotate-left" style="font-size: 10px; color: <?= $esDevuelta ? '#dc2626' : '#64748b' ?>;"></i>
                        <span>Rev. <?= $fmtVNum ?></span>
                        <span style="font-size: 9.5px; background: <?= $esDevuelta ? '#fee2e2' : 'rgba(0,0,0,0.06)' ?>; color: <?= $esDevuelta ? '#991b1b' : '#475569' ?>; padding: 1px 5px; border-radius: 3px; text-transform: uppercase; font-weight: 700;">
                            <?= $esDevuelta ? 'Devuelta' : 'Histórica' ?>
                        </span>
                    </a>
                <?php endforeach; ?>
            <?php endif; ?>

            <!-- Versión Actual Activa (Sin estrella, estilo sobrio y formal) -->
            <?php 
            $esActualSeleccionada = (!$esModoHistorico);
            $bgActual = $esActualSeleccionada ? '#eff6ff' : '#f8fafc';
            $colorActual = $esActualSeleccionada ? '#103487' : '#475569';
            $borderActual = $esActualSeleccionada ? '#103487' : '#cbd5e1';
            $fmtVActual = sprintf('%02d', $versionActualNum);
            ?>
            <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $detalle['id'] ?>" 
               style="padding: 4px 12px; font-size: 12px; font-weight: 700; border-radius: 5px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; border: 1.5px solid <?= $borderActual ?>; background: <?= $bgActual ?>; color: <?= $colorActual ?>; transition: all 0.15s; box-shadow: <?= $esActualSeleccionada ? '0 1px 3px rgba(16,52,135,0.1)' : 'none' ?>;"
               title="Versión actual activa de trabajo">
                <i class="fa-solid fa-circle-check" style="color: #2563eb; font-size: 11px;"></i>
                <span>Rev. <?= $fmtVActual ?></span>
                <span style="font-size: 9.5px; background: #103487; color: white; padding: 1px 6px; border-radius: 3px; text-transform: uppercase; font-weight: 700; letter-spacing: 0.3px;">
                    ACTUAL
                </span>
            </a>

            <?php if (empty($listaVersiones)): ?>
                <span style="font-size: 11px; color: #64748b; margin-left: 2px;">
                    (Versión inicial original)
                </span>
            <?php endif; ?>
        </div>
    </div>

    <!-- Acciones Rápidas de la Versión Seleccionada -->
    <div style="display: flex; gap: 8px; align-items: center;">
        <?php if ($esModoHistorico): ?>
            <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= $detalle['id'] ?>&version=<?= $versionSeleccionadaNum ?>" target="_blank" class="btn-matriz-secondary" style="background: white; color: #b91c1c; border: 1.5px solid #fca5a5; font-size: 12px; padding: 5px 12px; font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; border-radius: 5px;">
                <i class="fa-solid fa-print"></i> Imprimir Rev. <?= sprintf('%02d', $versionSeleccionadaNum) ?>
            </a>
            <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $detalle['id'] ?>" class="btn-matriz-primary" style="background: #103487; color: white; font-size: 12px; padding: 5px 14px; font-weight: 700; text-decoration: none; display: inline-flex; align-items: center; gap: 6px; border-radius: 5px;">
                <i class="fa-solid fa-arrow-left"></i> Ir a Versión Actual (v<?= $versionActualNum ?>)
            </a>
        <?php else: ?>
            <div style="display: inline-flex; align-items: center; gap: 6px; font-size: 11.5px; color: #475569; background: #f8fafc; border: 1px solid #e2e8f0; padding: 4px 10px; border-radius: 5px;">
                <i class="fa-solid fa-circle" style="color: #10b981; font-size: 8px;"></i>
                <span>Editando: <strong>Rev. <?= $fmtVActual ?></strong></span>
            </div>
        <?php endif; ?>
    </div>
</div>

<?php if ($esModoHistorico): ?>
    <!-- ALERTA MODO LECTURA HISTÓRICA -->
    <div style="background: #fff1f2; border: 2px solid #f87171; border-radius: 10px; padding: 16px 20px; margin-bottom: 22px; display: flex; gap: 14px; align-items: flex-start; box-shadow: 0 4px 10px rgba(239, 68, 68, 0.1);">
        <i class="fa-solid fa-history" style="color: #dc2626; font-size: 28px; margin-top: 2px;"></i>
        <div style="flex: 1;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 6px; flex-wrap: wrap; gap: 8px;">
                <h4 style="margin: 0; color: #991b1b; font-size: 16px; font-weight: 800;">
                    VISUALIZANDO VERSIÓN HISTÓRICA v<?= $versionSeleccionadaNum ?> (SOLO LECTURA)
                </h4>
                <span style="font-size: 11.5px; font-weight: 700; background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; padding: 3px 10px; border-radius: 12px;">
                    Registrada el <?= !empty($versionActivaInfo['fecha']) ? htmlspecialchars($versionActivaInfo['fecha']) : 'N/A' ?>
                </span>
            </div>
            <?php if (!empty($versionActivaInfo['motivo_devolucion'])): ?>
                <div style="background: white; border: 1px solid #fecaca; border-radius: 6px; padding: 10px 14px; margin: 8px 0; font-size: 13.5px; color: #1e293b;">
                    <strong style="color: #dc2626;"><i class="fa-solid fa-comment-dots"></i> Motivo de la Devolución por <?= htmlspecialchars($versionActivaInfo['usuario_revisor'] ?? 'Supervisor') ?>:</strong>
                    <div style="margin-top: 4px; font-style: italic; color: #475569;"><?= nl2br(htmlspecialchars($versionActivaInfo['motivo_devolucion'])) ?></div>
                </div>
            <?php endif; ?>
            <p style="margin: 4px 0 0 0; font-size: 12px; color: #7f1d1d;">
                * Está visualizando el registro histórico exacto de la versión devuelta. Para corregir cálculos y enviar a nueva revisión, presione <strong>"Regresar a Versión Actual"</strong>.
            </p>
        </div>
    </div>
<?php elseif ($revisionInfo['estado'] === 'devuelta'): ?>
    <!-- BANNER MATRIZ DEVUELTA POR SUPERVISIÓN -->
    <div style="background: #fef2f2; border: 1.5px solid #f87171; border-radius: 10px; padding: 16px 20px; margin-bottom: 22px; display: flex; gap: 14px; align-items: flex-start; box-shadow: 0 4px 6px -1px rgba(220, 38, 38, 0.08);">
        <i class="fa-solid fa-triangle-exclamation" style="color: #dc2626; font-size: 26px; margin-top: 2px;"></i>
        <div style="flex: 1;">
            <h4 style="margin: 0 0 6px 0; color: #991b1b; font-size: 15.5px; font-weight: 800; letter-spacing: 0.3px;">
                ⚠️ MATRIZ DEVUELTA POR SUPERVISIÓN TÉCNICA (REQUIERE CORRECCIÓN)
            </h4>
            <div style="background: white; border: 1px solid #fecaca; border-radius: 6px; padding: 10px 14px; margin-bottom: 8px; color: #1e293b; font-size: 14px; font-weight: 600; line-height: 1.45;">
                <i class="fa-solid fa-comment-dots" style="color: #dc2626; margin-right: 6px;"></i>
                <?= nl2br(htmlspecialchars($revisionInfo['motivo_devolucion'] ?? 'Favor verificar los datos de ensayo ingresados.')) ?>
            </div>
            <div style="font-size: 12.5px; color: #7f1d1d; display: flex; align-items: center; gap: 18px; flex-wrap: wrap;">
                <span><i class="fa-solid fa-user-shield"></i> Observado por: <strong><?= htmlspecialchars($revisionInfo['usuario_revisor'] ?? 'Supervisor') ?></strong></span>
                <span><i class="fa-solid fa-calendar-check"></i> Fecha: <strong><?= htmlspecialchars($revisionInfo['fecha_revision'] ?? '') ?></strong></span>
                <span style="color: #b91c1c; font-style: italic;"><i class="fa-solid fa-pen-nib"></i> Corrija los valores observados en la matriz inferior y presione <strong>"Guardar Matriz del Producto"</strong> para reenviarla a supervisión.</span>
            </div>
        </div>
    </div>
<?php elseif ($revisionInfo['estado'] === 'aprobada'): ?>
    <!-- BANNER MATRIZ APROBADA -->
    <div style="background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 10px; padding: 14px 20px; margin-bottom: 22px; display: flex; gap: 14px; align-items: center; box-shadow: 0 4px 6px -1px rgba(22, 163, 74, 0.08);">
        <i class="fa-solid fa-circle-check" style="color: #16a34a; font-size: 24px;"></i>
        <div style="flex: 1;">
            <strong style="color: #166534; font-size: 15px;"><i class="fa-solid fa-shield-check"></i> MATRIZ TÉCNICA APROBADA POR SUPERVISIÓN</strong>
            <div style="font-size: 12.5px; color: #15803d; margin-top: 3px;">
                Aprobada por: <strong><?= htmlspecialchars($revisionInfo['usuario_revisor'] ?? 'Supervisor') ?></strong> el <?= htmlspecialchars($revisionInfo['fecha_revision'] ?? '') ?>. Esta matriz cumple con los estándares técnicos y está formalmente autorizada para despacho y emisión oficial.
            </div>
        </div>
    </div>
<?php elseif ($revisionInfo['estado'] === 'en_revision'): ?>
    <!-- BANNER MATRIZ PENDIENTE DE REVISIÓN -->
    <div style="background: #fffbeb; border: 1.5px solid #fcd34d; border-radius: 10px; padding: 14px 20px; margin-bottom: 22px; display: flex; gap: 14px; align-items: center; box-shadow: 0 4px 6px -1px rgba(217, 119, 6, 0.08);">
        <i class="fa-solid fa-clock-rotate-left" style="color: #d97706; font-size: 24px;"></i>
        <div style="flex: 1;">
            <strong style="color: #92400e; font-size: 15px;"><i class="fa-solid fa-hourglass-half"></i> MATRIZ PENDIENTE DE REVISIÓN</strong>
            <div style="font-size: 12.5px; color: #b45309; margin-top: 3px;">
                Remitida por: <strong><?= htmlspecialchars($revisionInfo['usuario_envio'] ?? 'Laboratorio') ?></strong> el <?= htmlspecialchars($revisionInfo['fecha_envio'] ?? '') ?>. Pendiente de verificación y visto bueno por supervisión técnica para habilitar su envío al cliente.
            </div>
        </div>
    </div>
<?php endif; ?>

<form id="form-matriz-completa" method="POST" action="/Cycsa/publico/operaciones/guardar-matriz-producto" onsubmit="prepararEnvioMatriz(event)">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="id_detalle" value="<?= $detalle['id'] ?>">
    <input type="hidden" name="resultados_json" id="input_resultados_json" value="">

    <!-- SECCIÓN 1: ENCABEZADO OFICIAL DE ENSAYO CYCSA-RT-FM-22 (Captura 144015) -->
    <div class="informe-oficial-card">
        <!-- Barra Superior Oficial con Logotipo CYCSA, Título Centrado y Código -->
        <div class="informe-cabecera-row">
            <div class="informe-logo-box">
                <img src="/Cycsa/publico/img/logo.png" alt="CYCSA" style="height: 52px; max-width: 190px; object-fit: contain;">
            </div>
            <div class="informe-titulo-box">
                <h1 class="informe-titulo-texto"><?= htmlspecialchars($schemaInfo['titulo_informe'] ?? 'INFORME DE ENSAYO') ?></h1>
                <div id="informe-codigo-consecutivo" style="font-family: 'Consolas', 'Courier New', monospace; font-size: 13px; font-weight: 800; color: #103487; letter-spacing: 0.8px; margin-top: 2px; margin-bottom: 2px;">
                    <?= htmlspecialchars($codigoInformeConsecutivo) ?>
                </div>
                <div class="informe-subtitulo-texto"><?= htmlspecialchars($schemaInfo['subtitulo_laboratorio'] ?? 'Laboratorio de Ensayos y Control de Calidad') ?> &bull; Orden de Servicio: <strong style="color: #103487; font-family: monospace;"><?= htmlspecialchars($detalle['codigo_os']) ?></strong></div>
                <div class="informe-subtitulo-texto">Norma técnica: <?= htmlspecialchars($schemaInfo['norma'] ?? ($detalle['norma_astm'] ?? '')) ?></div>
            </div>
            <div class="informe-badge-box">
                <div class="codigo-formato-badge"><?= htmlspecialchars($metadatos['codigo_formato']) ?></div>
                <div class="norma-iso-sub">ISO/IEC 17025:2017</div>
            </div>
        </div>

        <!-- Bloque Oficial de Metadatos 2 Columnas (1:1 con Formatos Word/Excel) -->
        <div class="informe-metadatos-grid">
            <!-- Columna 1 (Izquierda) -->
            <div class="informe-col">
                <div class="meta-row">
                    <label class="meta-label-oficial">** Nombre del cliente:</label>
                    <div class="meta-field-wrap">
                        <?php if (!$esLaboratorista): ?>
                            <input type="text" name="metadatos[cliente_nombre]" id="meta_cliente_nombre" class="meta-input-web" value="<?= htmlspecialchars($metadatos['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <div class="meta-blindness"><i class="fa-solid fa-eye-slash"></i> Cliente Confidencial (ISO/IEC 17025)</div>
                            <input type="hidden" name="metadatos[cliente_nombre]" id="meta_cliente_nombre" value="<?= htmlspecialchars($metadatos['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="meta-row">
                    <label class="meta-label-oficial">** Dirección:</label>
                    <div class="meta-field-wrap">
                        <?php if (!$esLaboratorista): ?>
                            <input type="text" name="metadatos[cliente_direccion]" id="meta_cliente_direccion" class="meta-input-web" value="<?= htmlspecialchars($metadatos['cliente_direccion'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <div class="meta-blindness"><i class="fa-solid fa-eye-slash"></i> Dirección Confidencial (ISO/IEC 17025)</div>
                            <input type="hidden" name="metadatos[cliente_direccion]" id="meta_cliente_direccion" value="<?= htmlspecialchars($metadatos['cliente_direccion'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="meta-row">
                    <label class="meta-label-oficial">Fecha de ingreso:</label>
                    <div class="meta-field-wrap">
                        <input type="date" name="metadatos[fecha_ingreso]" id="meta_fecha_ingreso" class="meta-input-web" value="<?= htmlspecialchars($metadatos['fecha_ingreso'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="meta-row">
                    <label class="meta-label-oficial">Tipo de muestra:</label>
                    <div class="meta-field-wrap">
                        <input type="text" name="metadatos[tipo_muestra]" id="meta_tipo_muestra" class="meta-input-web" value="<?= htmlspecialchars($metadatos['tipo_muestra'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="meta-row">
                    <label class="meta-label-oficial">** Procedimiento de muestreo:</label>
                    <div class="meta-field-wrap">
                        <input type="text" name="metadatos[procedimiento_muestreo]" id="meta_procedimiento_muestreo" class="meta-input-web" value="<?= htmlspecialchars($metadatos['procedimiento_muestreo'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="meta-row" style="align-items: flex-start;">
                    <label class="meta-label-oficial" style="padding-top: 6px;">Ensayo realizado:</label>
                    <div class="meta-field-wrap">
                        <textarea name="metadatos[ensayo_realizado]" id="meta_ensayo_realizado" class="meta-input-web" rows="3" style="resize: vertical; font-size: 12.5px;"><?= htmlspecialchars($metadatos['ensayo_realizado'], ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>

            <!-- Columna 2 (Derecha) -->
            <div class="informe-col">
                <div class="meta-row">
                    <label class="meta-label-oficial">** Proyecto:</label>
                    <div class="meta-field-wrap">
                        <?php if (!$esLaboratorista): ?>
                            <input type="text" name="metadatos[proyecto]" id="meta_proyecto" class="meta-input-web" value="<?= htmlspecialchars($metadatos['proyecto'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php else: ?>
                            <div class="meta-blindness"><i class="fa-solid fa-eye-slash"></i> Proyecto Confidencial (ISO/IEC 17025)</div>
                            <input type="hidden" name="metadatos[proyecto]" id="meta_proyecto" value="<?= htmlspecialchars($metadatos['proyecto'], ENT_QUOTES, 'UTF-8') ?>">
                        <?php endif; ?>
                    </div>
                </div>

                <div class="meta-row">
                    <label class="meta-label-oficial">** Fecha muestreo:</label>
                    <div class="meta-field-wrap">
                        <input type="date" name="metadatos[fecha_muestreo]" id="meta_fecha_muestreo" class="meta-input-web" value="<?= htmlspecialchars($metadatos['fecha_muestreo'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="meta-row">
                    <label class="meta-label-oficial">Fecha de ejecución:</label>
                    <div class="meta-field-wrap">
                        <input type="date" name="metadatos[fecha_ejecucion]" id="meta_fecha_ejecucion" class="meta-input-web" value="<?= htmlspecialchars($metadatos['fecha_ejecucion'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="meta-row">
                    <label class="meta-label-oficial">Fecha de emisión:</label>
                    <div class="meta-field-wrap">
                        <input type="date" name="metadatos[fecha_emision]" id="meta_fecha_emision" class="meta-input-web" value="<?= htmlspecialchars($metadatos['fecha_emision'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="meta-row">
                    <label class="meta-label-oficial">Muestra tomada por:</label>
                    <div class="meta-field-wrap">
                        <input type="text" name="metadatos[muestra_tomada_por]" id="meta_muestra_tomada_por" class="meta-input-web" value="<?= htmlspecialchars($metadatos['muestra_tomada_por'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="meta-row">
                    <label class="meta-label-oficial">** Ubicación:</label>
                    <div class="meta-field-wrap">
                        <input type="text" name="metadatos[ubicacion]" id="meta_ubicacion" class="meta-input-web" value="<?= htmlspecialchars($metadatos['ubicacion'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="meta-row">
                    <label class="meta-label-oficial">Método de muestreo:</label>
                    <div class="meta-field-wrap">
                        <input type="text" name="metadatos[metodo_muestreo]" id="meta_metodo_muestreo" class="meta-input-web" style="font-family: monospace; font-weight: bold; color: #103487;" value="<?= htmlspecialchars($metadatos['metodo_muestreo'], ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- SECCIÓN 2: TABLA DE CAPTURA TÉCNICA (PANTALLA COMPLETA) -->
    <div class="seccion-form">

        <div class="seccion-titulo" style="justify-content: space-between; flex-wrap: wrap;">
            <div>
                <i class="fa-solid fa-table-cells" style="color: var(--cycsa-azul);"></i> Matriz de Captura Técnica y Cálculos
                <span style="font-size: 12.5px; font-weight: normal; color: #64748b; margin-left: 8px;">(Actualización en tiempo real)</span>
            </div>

                <!-- SELECTOR DE LÍMITES GEOTÉCNICOS (Para Ensayos de Granulometría) -->
                <?php if ($isGranulometria): ?>
                <div style="display: flex; align-items: center; gap: 10px; background: #f8fafc; border: 1px solid #cbd5e1; padding: 8px 14px; border-radius: 8px;">
                    <label style="font-size: 12px; font-weight: 700; color: #334155; margin: 0; white-space: nowrap;">
                        <i class="fa-solid fa-sliders"></i> Aplicar Límites por Material:
                    </label>
                    <select id="select-limites-material" class="matriz-input" style="width: auto; padding: 5px 10px; font-size: 12.5px; background: white;" onchange="aplicarLimitesMaterial(this.value)">
                        <option value="">-- Seleccionar Especificación --</option>
                        <option value="Arena colchon">Arena colchón</option>
                        <option value="Material Cero">Material Cero</option>
                        <option value="Material 1 1/2">Material 1 1/2"</option>
                        <option value="Material 1">Material 1"</option>
                        <option value="Material 3/4">Material 3/4"</option>
                        <option value="Material 1/2">Material 1/2"</option>
                        <option value="Material 3/8">Material 3/8"</option>
                        <option value="Suelo">Suelo</option>
                        <option value="Selecto">Selecto</option>
                        <option value="Selecto relleno tipo 1">Selecto relleno tipo 1</option>
                        <option value="Selecto relleno tipo 2">Selecto relleno tipo 2</option>
                        <option value="Mezcla relleno 1-2">Mezcla relleno 1-2</option>
                        <option value="Selecto Base A">Selecto Base A</option>
                        <option value="Selecto Base B">Selecto Base B</option>
                        <option value="Selecto Base C">Selecto Base C</option>
                        <option value="Selecto Base D">Selecto Base D</option>
                        <option value="Sub base A-1">Sub base A-1</option>
                    </select>
                </div>
                <?php endif; ?>
            </div>

            <!-- CONTENEDOR DE LA TABLA DINÁMICA -->
            <div class="tabla-matriz-wrapper">
                <table class="tabla-matriz" id="tabla-captura-matriz">
                    <thead id="tabla-header">
                        <!-- Generado por JavaScript según el archivo markdown -->
                    </thead>
                    <tbody id="tabla-body">
                        <!-- Filas generadas por JavaScript -->
                    </tbody>
                </table>
            </div>
            <div style="margin-top: 14px; color: #475569; font-size: 12px;">
                <strong><?= htmlspecialchars($schemaInfo['disclaimer'] ?? '') ?></strong>
                <?php foreach (($schemaInfo['notas'] ?? []) as $nota): ?>
                    <div><?= htmlspecialchars($nota) ?></div>
                <?php endforeach; ?>
                <div style="margin-top: 8px;">Firmante oficial: <?= htmlspecialchars($schemaInfo['firmante_nombre'] ?? 'Ing. Noel Quintana Lira') ?> — <?= htmlspecialchars($schemaInfo['firmante_cargo'] ?? 'Gerente General') ?></div>
            </div>

            <!-- ALERTA DE PÉRDIDA POR LAVADO -->
            <div id="alerta-lavado-norma" style="display:none; margin-top:15px; padding:12px 16px; border-radius:8px; font-size:13px; font-weight:600;"></div>

            <!-- BARRA DE ACCIÓN INFERIOR -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 25px; border-top: 1px solid #e2e8f0; padding-top: 18px;">
                <span style="font-size:12.5px; color:#0369a1; font-weight:600;">
                    <i class="fa-solid fa-lock"></i> Formato técnico oficial sincronizado con <?= htmlspecialchars($detalle['archivo_markdown'] ?: 'ISO 17025') ?>
                </span>
                <div style="display: flex; gap: 12px;">
                    <a href="/Cycsa/publico/operaciones" class="btn-matriz-back">
                        Cancelar
                    </a>
                    <?php if ($esModoHistorico): ?>
                        <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $detalle['id'] ?>" class="btn-matriz-primary" style="background:#2563eb; text-decoration:none;">
                            <i class="fa-solid fa-pen-to-square"></i> Ir a Versión Actual (v<?= $versionActualNum ?>)
                        </a>
                    <?php else: ?>
                        <button type="submit" class="btn-matriz-primary">
                            <i class="fa-solid fa-floppy-disk"></i> Guardar Matriz del Producto
                        </button>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    </div>

<script>
    const ARCHIVO_MARKDOWN = <?= json_encode($archivoMd, JSON_UNESCAPED_UNICODE) ?>;
    const FORMATOS_SCHEMA = <?= $formatosSchemaJson ?>;
    const RAW_RESULTADOS = <?= json_encode(json_decode($detalle['resultados_json'] ?? '[]', true) ?: [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const DATOS_INICIALES = Array.isArray(RAW_RESULTADOS) ? RAW_RESULTADOS : (RAW_RESULTADOS.filas || []);
    const MUESTRAS_SETEADAS = <?= json_encode($muestrasSeteadas ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const ES_MODO_HISTORICO = <?= $esModoHistorico ? 'true' : 'false' ?>;
    const PREFIJO_OFICIAL = <?= json_encode($prefijoMuestraOS ?? determinarPrefijoMuestraOS($detalle)) ?>;

    const DEFAULT_ROWS_BY_FORMAT = {
        "formato_de_granulometria_de_suelo.md": [
            { "Malla": "2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1 1/2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/4\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1/2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/8\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 4", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 8", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 10", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 16", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 20", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 30", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 40", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 50", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 60", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 80", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 100", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 140", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 200", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Fondo", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Pérdida lavado", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Suma", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Límite Líquido", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Límite Plástico", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "I.P", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" }
        ],
        "granulomnetria_de_agregados.md": [
            { "Malla": "2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1 1/2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/4\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1/2\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/8\"", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 4", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 8", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 10", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 16", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 20", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 30", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 40", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 50", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 60", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 80", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 100", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 140", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 200", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Fondo", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Pérdida lavado", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Suma", "P. Retenido parcial (gr)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Límite Líquido", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Límite Plástico", "P. Retenido parcial (gr)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
        ],
        "formato_de_resistencia_de_bloques.md": [
            { "Descripción": "Área bruta.", "Unidad": "in²", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Área neta.", "Unidad": "in²", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "50% A.B", "Límite Máx": "" },
            { "Descripción": "Carga", "Unidad": "lb", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "-", "Límite Máx": "-" },
            { "Descripción": "Largo", "Unidad": "cm", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Alto", "Unidad": "cm", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Espesor pared", "Unidad": "mm", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "25", "Límite Máx": "" },
            { "Descripción": "Espesor tabique", "Unidad": "mm", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "25", "Límite Máx": "" },
            { "Descripción": "Peso recibido", "Unidad": "gr", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Peso sumergido", "Unidad": "gr", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Peso saturado", "Unidad": "gr", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Peso seco", "Unidad": "gr", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Absorción", "Unidad": "%", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "12.00" },
            { "Descripción": "Humedad", "Unidad": "%", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Densidad", "Unidad": "kg/m³", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "2000", "Límite Máx": "" },
            { "Descripción": "R. Compresión área bruta", "Unidad": "kg/cm²", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "70.00", "Límite Máx": "" },
            { "Descripción": "R. Compresión área neta", "Unidad": "kg/cm²", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "133.00", "Límite Máx": "" }
        ]
    };

    const LIMITS_DB = {
        "Arena colchon": {
            "min": { "3/8\"": 100, "No. 4": 100, "No. 10": 85, "No. 200": 0 },
            "max": { "No. 200": 3 }
        },
        "Material Cero": {
            "min": { "1 1/2\"": 100, "1\"": 90, "3/4\"": 35, "1/2\"": 20, "3/8\"": 10, "No. 4": 0, "No. 8": 80, "No. 16": 50, "No. 30": 25, "No. 50": 5, "No. 100": 0, "No. 200": 0 },
            "max": { "1\"": 100, "3/4\"": 70, "1/2\"": 50, "3/8\"": 30, "No. 4": 100, "No. 8": 100, "No. 16": 85, "No. 30": 60, "No. 50": 30, "No. 100": 10, "No. 200": 5 }
        },
        "Material 1 1/2": {
            "min": { "2\"": 100, "1 1/2\"": 71, "1\"": 75, "3/4\"": 38, "1/2\"": 53, "3/8\"": 30, "No. 4": 25, "No. 10": 15, "No. 40": 8, "No. 200": 2 },
            "max": { "2\"": 100, "1\"": 100, "3/4\"": 100, "1/2\"": 77.5, "3/8\"": 55, "No. 4": 5, "No. 8": 0, "No. 200": 0 }
        },
        "Material 1": {
            "min": { "1\"": 100, "3/4\"": 90, "1/2\"": 55, "3/8\"": 40, "No. 4": 25, "No. 10": 15, "No. 40": 8, "No. 200": 2 },
            "max": { "1\"": 100, "3/4\"": 100, "1/2\"": 100, "3/8\"": 55, "No. 4": 10, "No. 8": 5, "No. 200": 0 }
        },
        "Material 3/4": {
            "min": { "3/4\"": 100, "1/2\"": 90, "3/8\"": 85, "No. 4": 0, "No. 8": 0, "No. 10": 0, "No. 16": 0, "No. 200": 0 },
            "max": { "3/4\"": 100, "1/2\"": 100, "3/8\"": 70, "No. 4": 15, "No. 8": 5, "No. 16": 0 }
        },
        "Material 1/2": {
            "min": { "1/2\"": 100, "3/8\"": 100, "No. 4": 10, "No. 8": 0, "No. 16": 0, "No. 200": 0 },
            "max": { "1/2\"": 100, "3/8\"": 100, "No. 4": 30, "No. 8": 10, "No. 16": 0 }
        },
        "Material 3/8": {
            "min": { "3/8\"": 100, "No. 4": 100, "No. 10": 100, "No. 16": 100, "No. 30": 100, "No. 40": 100, "No. 50": 100, "No. 60": 100, "No. 80": 100, "No. 100": 100, "No. 140": 100, "No. 200": 100 },
            "max": { "3/8\"": 100, "No. 4": 100, "No. 10": 100, "No. 16": 100, "No. 30": 100, "No. 40": 100, "No. 50": 100, "No. 60": 100, "No. 80": 100, "No. 100": 100, "No. 140": 100, "No. 200": 100 }
        },
        "Suelo": {
            "min": { "2\"": 100, "No. 4": 12, "No. 10": 7, "No. 40": 4, "No. 200": 0 },
            "max": { "2\"": 100, "No. 4": 40, "No. 10": 29, "No. 40": 21, "No. 200": 16 }
        },
        "Selecto": {
            "min": { "1\"": 75, "1/2\"": 50, "No. 4": 30, "No. 10": 20, "No. 40": 10, "No. 200": 0 },
            "max": { "1\"": 95, "1/2\"": 80, "No. 4": 65, "No. 10": 50, "No. 40": 35, "No. 200": 16 }
        },
        "Selecto relleno tipo 1": {
            "min": { "2\"": 100, "1\"": 75, "No. 4": 90, "No. 10": 75, "No. 40": 50, "No. 200": 0 },
            "max": { "2\"": 100, "1\"": 100, "No. 4": 100, "No. 10": 90, "No. 40": 65, "No. 200": 35 }
        },
        "Selecto relleno tipo 2": {
            "min": { "2\"": 100, "1\"": 65, "No. 4": 50, "No. 10": 30, "No. 40": 23, "No. 200": 0 },
            "max": { "2\"": 100, "1\"": 90, "No. 4": 80, "No. 10": 63, "No. 40": 46, "No. 200": 20 }
        },
        "Mezcla relleno 1-2": {
            "min": { "2\"": 97, "No. 4": 25, "No. 10": 15, "No. 40": 8, "No. 200": 2 },
            "max": { "2\"": 100, "No. 4": 55, "No. 10": 40, "No. 40": 20, "No. 200": 8 }
        },
        "Selecto Base A": {
            "min": { "No. 4": 30, "No. 10": 20, "No. 40": 15, "No. 200": 5 },
            "max": { "No. 4": 60, "No. 10": 45, "No. 40": 30, "No. 200": 15 }
        },
        "Selecto Base B": {
            "min": { "No. 4": 35, "No. 10": 25, "No. 40": 15, "No. 200": 5 },
            "max": { "No. 4": 65, "No. 10": 50, "No. 40": 30, "No. 200": 15 }
        },
        "Selecto Base C": {
            "min": { "No. 4": 50, "No. 10": 40, "No. 40": 25, "No. 200": 8 },
            "max": { "No. 4": 85, "No. 10": 70, "No. 40": 45, "No. 200": 15 }
        },
        "Selecto Base D": {
            "min": { "No. 4": 28, "No. 10": 22, "No. 200": 5 },
            "max": { "No. 4": 40, "No. 10": 52, "No. 200": 20 }
        },
        "Sub base A-1": {
            "min": { "2\"": 100, "1\"": 65, "No. 4": 28, "No. 10": 22, "No. 200": 5 },
            "max": { "2\"": 100, "1\"": 79, "No. 4": 40, "No. 10": 52, "No. 200": 20 }
        }
    };

    let columnasActuales = [];

    function inicializarMatriz() {
        const schema = FORMATOS_SCHEMA[ARCHIVO_MARKDOWN] || { columns: [] };
        columnasActuales = (schema.columns && schema.columns.length > 0) 
            ? schema.columns 
            : ["Código laboratorio", "Nombre muestra", "Área (in²)", "Carga (lb)", "R. Compresión (lb/in²)", "R. Compresión (kg/cm²)"];

        // 1. Renderizar encabezado
        const thead = document.getElementById('tabla-header');
        thead.innerHTML = '';

        // Fila 1: Métodos de Ensayo (si existen definidos en el formato oficial)
        const hasMethods = schema.column_methods && Object.values(schema.column_methods).some(m => m && m.trim() !== '');
        if (hasMethods) {
            const trMethods = document.createElement('tr');
            trMethods.style.background = '#f8fafc';
            trMethods.style.borderBottom = '1px solid #cbd5e1';

            const thMethodTitle = document.createElement('th');
            thMethodTitle.colSpan = 3;
            thMethodTitle.style.textAlign = 'left';
            thMethodTitle.style.padding = '8px 12px';
            thMethodTitle.style.fontWeight = '700';
            thMethodTitle.style.fontSize = '12px';
            thMethodTitle.style.color = '#0284c7';
            thMethodTitle.innerHTML = '<i class="fa-solid fa-flask-vial"></i> Método de ensayo';
            trMethods.appendChild(thMethodTitle);

            columnasActuales.slice(2).forEach(col => {
                const thM = document.createElement('th');
                thM.style.textAlign = 'center';
                thM.style.padding = '6px 8px';
                thM.style.fontWeight = '600';
                thM.style.fontSize = '11px';
                const methodCode = schema.column_methods[col] || '';
                if (methodCode) {
                    const badgeMetodo = document.createElement('span');
                    badgeMetodo.style.cssText = 'background:#e0f2fe; color:#0369a1; padding:2px 7px; border-radius:4px; border:1px solid #bae6fd; font-family:monospace; font-size:11px;';
                    badgeMetodo.textContent = methodCode;
                    thM.appendChild(badgeMetodo);
                } else {
                    thM.innerText = '—';
                    thM.style.color = '#94a3b8';
                }
                trMethods.appendChild(thM);
            });
            thead.appendChild(trMethods);
        }

        // Fila 2: Nombres de Columnas
        const trH = document.createElement('tr');
        
        const thNum = document.createElement('th');
        thNum.style.width = '40px';
        thNum.style.textAlign = 'center';
        thNum.innerText = 'N°';
        trH.appendChild(thNum);

        columnasActuales.forEach(col => {
            const th = document.createElement('th');
            th.innerText = col;
            trH.appendChild(th);
        });
        thead.appendChild(trH);

        // 2. Determinar filas iniciales
        let filas = [];
        const aliases = schema.column_aliases || {};
        const codigoCol = columnasActuales.find(col => (aliases[col] || col) === 'Código laboratorio') || 'Código laboratorio';
        const nombreCol = columnasActuales.find(col => (aliases[col] || col) === 'Nombre muestra') || 'Nombre muestra';
        if (Array.isArray(DATOS_INICIALES) && DATOS_INICIALES.length > 0) {
            filas = DATOS_INICIALES.map((row, idx) => {
                if (row['Código laboratorio'] && row['Código laboratorio'].startsWith('OS-')) {
                    if (MUESTRAS_SETEADAS && MUESTRAS_SETEADAS[idx] && MUESTRAS_SETEADAS[idx].codigo_lab) {
                        row['Código laboratorio'] = MUESTRAS_SETEADAS[idx].codigo_lab;
                    } else {
                        row['Código laboratorio'] = PREFIJO_OFICIAL + '-' + String(idx + 1).padStart(4, '0') + '-26';
                    }
                }
                return row;
            });
        } else if (DEFAULT_ROWS_BY_FORMAT[ARCHIVO_MARKDOWN]) {
            filas = JSON.parse(JSON.stringify(DEFAULT_ROWS_BY_FORMAT[ARCHIVO_MARKDOWN]));
        } else if (Array.isArray(MUESTRAS_SETEADAS) && MUESTRAS_SETEADAS.length > 0) {
            filas = MUESTRAS_SETEADAS.map((m, i) => {
                const row = {};
                columnasActuales.forEach(col => { row[col] = ''; });
                if (row.hasOwnProperty(codigoCol)) row[codigoCol] = m.codigo_lab || '';
                if (row.hasOwnProperty(nombreCol)) row[nombreCol] = m.nombre_muestra || m.codigo_campo || '';
                return row;
            });
        } else {
            // 3 filas genéricas
            for (let i = 1; i <= 3; i++) {
                const row = {};
                columnasActuales.forEach(col => { row[col] = ''; });
                if (row.hasOwnProperty(codigoCol)) row[codigoCol] = PREFIJO_OFICIAL + '-' + String(i).padStart(4, '0') + '-26';
                if (row.hasOwnProperty(nombreCol)) row[nombreCol] = 'Muestra ' + i;
                filas.push(row);
            }
        }

        // 3. Renderizar cuerpo
        renderizarFilas(filas);

        if (ARCHIVO_MARKDOWN.includes('granulometria') || ARCHIVO_MARKDOWN.includes('granulomnetria')) {
            recalculateGranulometria();
            recalculateCodigoInforme();
        } else {
            recalculateAll();
        }
    }

    function renderizarFilas(filas) {
        const tbody = document.getElementById('tabla-body');
        tbody.innerHTML = '';

        filas.forEach((rowData, rIdx) => {
            const tr = document.createElement('tr');
            
            // Columna de numeración
            const tdNum = document.createElement('td');
            tdNum.style.textAlign = 'center';
            tdNum.style.fontWeight = '700';
            tdNum.style.color = '#64748b';
            tdNum.innerText = rIdx + 1;
            tr.appendChild(tdNum);

            const isGranulo = (ARCHIVO_MARKDOWN.includes('granulometria') || ARCHIVO_MARKDOWN.includes('granulomnetria'));
            const aliases = (FORMATOS_SCHEMA[ARCHIVO_MARKDOWN] || {}).column_aliases || {};
            const mallaCol = columnasActuales.find(col => (aliases[col] || col) === 'Malla') || 'Malla';
            const mallaName = rowData[mallaCol] || rowData['Malla'] || '';

            columnasActuales.forEach(col => {
                const calcCol = aliases[col] || col;
                const td = document.createElement('td');
                const input = document.createElement('input');
                input.type = 'text';
                input.className = 'matriz-input';
                input.value = rowData[col] !== undefined ? rowData[col] : (rowData[calcCol] !== undefined ? rowData[calcCol] : '');
                input.dataset.col = calcCol;
                input.dataset.saveCol = col;
                input.dataset.row = rIdx;

                if (isGranulo) {
                    if (calcCol === 'Malla') {
                        input.readOnly = true;
                        input.classList.add('matriz-input-locked');
                    }
                    if (['% Retenido parcial', '% Acumulativo'].includes(calcCol)) {
                        input.readOnly = true;
                        input.classList.add('matriz-input-calculated');
                    }
                    if (calcCol === '% que pasa la malla') {
                        if (['Límite Líquido', 'Límite Plástico'].includes(mallaName)) {
                            input.style.fontWeight = 'bold';
                            input.addEventListener('input', recalculateGranulometria);
                        } else if (mallaName === 'I.P') {
                            input.readOnly = true;
                            input.classList.add('matriz-input-calculated');
                        } else {
                            input.readOnly = true;
                            input.classList.add('matriz-input-calculated');
                        }
                    }
                    if (calcCol === 'P. Retenido parcial (gr)') {
                        if (['Límite Líquido', 'Límite Plástico', 'I.P'].includes(mallaName)) {
                            input.readOnly = true;
                            input.classList.add('matriz-input-locked');
                            input.value = '—';
                        } else if (mallaName === 'Suma') {
                            input.readOnly = true;
                            input.classList.add('matriz-input-calculated');
                        } else {
                            input.addEventListener('input', recalculateGranulometria);
                        }
                    }
                } else if (ARCHIVO_MARKDOWN.includes('bloques')) {
                    if (['Descripción', 'Unidad', 'Método de ensayo'].includes(calcCol)) {
                        input.readOnly = true;
                        input.classList.add('matriz-input-locked');
                    }
                } else {
                    // Ensayos de compresión / cilindros / probetas / compactación / otros
                    if (calcCol === 'Código laboratorio') {
                        input.readOnly = true;
                        input.classList.add('matriz-input-locked');
                    }
                    if (['Compactación ((%) (P/P))', 'R. Compresión (lb/in²)', 'R. Compresión (kg/cm²)', 'R. compresión. (kg/cm²)', 'Resistencia a la flexión. (kg/cm²)', 'Reven. (cm)', 'Edad (Días)'].includes(calcCol)) {
                        input.classList.add('matriz-input-calculated');
                    }
                    if (['Carga (lb)', 'Carga (kg)', 'Área (in²)', 'Área (cm²)'].includes(calcCol)) {
                        input.addEventListener('input', recalculateCompresion);
                    }
                    if (['Reven. (in)'].includes(calcCol)) {
                        input.addEventListener('input', recalculateRevenimiento);
                    }
                    if (['P.V.S Max (kg/m³)', 'P.V.S.Sitio (kg/cm²)', 'P.V.S.Sitio (kg/m³)'].includes(calcCol)) {
                        input.addEventListener('input', recalculateCompactacion);
                    }
                    if (['Ancho Promedio (in)', 'Espesor Promedio (in)', 'Longitud de Apoyo (in)', 'Carga (lb)'].includes(calcCol)) {
                        input.addEventListener('input', recalculateFlexion);
                    }
                    if (['Fecha de Fabricación', 'Fecha de Ensayo', 'Fecha de Ruptura'].includes(calcCol)) {
                        input.addEventListener('change', recalculateEdades);
                        input.addEventListener('input', recalculateEdades);
                    }
                    if (['Código laboratorio', 'Codigo laboratorio', 'codigo_lab'].includes(calcCol) || col === 'Código laboratorio') {
                        input.addEventListener('input', recalculateCodigoInforme);
                    }
                }

                if (ES_MODO_HISTORICO) {
                    input.readOnly = true;
                    input.disabled = true;
                    input.classList.add('matriz-input-locked');
                    input.style.backgroundColor = '#f8fafc';
                    input.style.cursor = 'not-allowed';
                }

                td.appendChild(input);
                tr.appendChild(td);
            });

            tbody.appendChild(tr);
        });

        if (ES_MODO_HISTORICO) {
            document.querySelectorAll('.meta-input-web, textarea.meta-input-web, select.matriz-input').forEach(el => {
                el.readOnly = true;
                el.disabled = true;
                el.style.backgroundColor = '#f8fafc';
                el.style.cursor = 'not-allowed';
            });
        }
    }

    function aplicarLimitesMaterial(matName) {
        if (!matName) return;
        const limits = LIMITS_DB[matName];
        if (!limits) return;

        const rows = document.querySelectorAll('#tabla-body tr');
        rows.forEach(tr => {
            const descInput = tr.querySelector('input[data-col="Malla"]');
            const minInput = tr.querySelector('input[data-col="Límite Mín"]');
            const maxInput = tr.querySelector('input[data-col="Límite Máx"]');
            if (descInput && minInput && maxInput) {
                const malla = descInput.value.trim();
                minInput.value = (limits.min && limits.min[malla] !== undefined) ? limits.min[malla] : '';
                maxInput.value = (limits.max && limits.max[malla] !== undefined) ? limits.max[malla] : '';
            }
        });
    }

    function recalculateGranulometria() {
        const rows = document.querySelectorAll('#tabla-body tr');
        let totalSuma = 0;
        let rowsArray = [];

        rows.forEach(tr => {
            const rowData = {};
            tr.querySelectorAll('input').forEach(inp => {
                rowData[inp.dataset.col] = inp;
            });
            rowsArray.push(rowData);
        });

        // 1. Suma de pesos
        rowsArray.forEach(row => {
            const malla = row['Malla'] ? row['Malla'].value.trim() : '';
            if (malla && !['Suma', 'Límite Líquido', 'Límite Plástico', 'I.P'].includes(malla)) {
                const wInput = row['P. Retenido parcial (gr)'];
                if (wInput) {
                    totalSuma += (parseFloat(wInput.value) || 0);
                }
            }
        });

        // Fila Suma
        const sumaRow = rowsArray.find(r => r['Malla'] && r['Malla'].value.trim() === 'Suma');
        if (sumaRow && sumaRow['P. Retenido parcial (gr)']) {
            sumaRow['P. Retenido parcial (gr)'].value = totalSuma > 0 ? totalSuma.toFixed(4) : '';
            if (sumaRow['% Retenido parcial']) sumaRow['% Retenido parcial'].value = totalSuma > 0 ? '100.00' : '';
            if (sumaRow['% Acumulativo']) sumaRow['% Acumulativo'].value = totalSuma > 0 ? '100.00' : '';
            if (sumaRow['% que pasa la malla']) sumaRow['% que pasa la malla'].value = totalSuma > 0 ? '0.00' : '';
        }

        // Porcentajes de tamices
        let accumPercent = 0;
        rowsArray.forEach(row => {
            const malla = row['Malla'] ? row['Malla'].value.trim() : '';
            if (malla && !['Suma', 'Límite Líquido', 'Límite Plástico', 'I.P'].includes(malla)) {
                const wInput = row['P. Retenido parcial (gr)'];
                const rpInput = row['% Retenido parcial'];
                const acInput = row['% Acumulativo'];
                const qpInput = row['% que pasa la malla'];

                if (wInput && wInput.value !== '') {
                    const w = parseFloat(wInput.value) || 0;
                    const percent = totalSuma > 0 ? (w / totalSuma) * 100 : 0;
                    if (rpInput) rpInput.value = percent.toFixed(2);
                    accumPercent += percent;
                    if (acInput) acInput.value = accumPercent.toFixed(2);
                    if (qpInput) qpInput.value = Math.max(0, 100 - accumPercent).toFixed(2);
                } else {
                    if (rpInput) rpInput.value = '';
                    if (acInput) acInput.value = '';
                    if (qpInput) qpInput.value = '';
                }
            }
        });

        // Índice de Plasticidad (IP)
        const llRow = rowsArray.find(r => r['Malla'] && r['Malla'].value.trim() === 'Límite Líquido');
        const lpRow = rowsArray.find(r => r['Malla'] && r['Malla'].value.trim() === 'Límite Plástico');
        const ipRow = rowsArray.find(r => r['Malla'] && r['Malla'].value.trim() === 'I.P');

        if (llRow && lpRow && ipRow) {
            const llVal = parseFloat(llRow['% que pasa la malla'] ? llRow['% que pasa la malla'].value : 0) || 0;
            const lpVal = parseFloat(lpRow['% que pasa la malla'] ? lpRow['% que pasa la malla'].value : 0) || 0;
            const ipVal = Math.max(0, llVal - lpVal);
            if (ipRow['% que pasa la malla']) {
                ipRow['% que pasa la malla'].value = (llVal > 0 || lpVal > 0) ? ipVal.toFixed(2) : '';
            }
        }

        // Validación de Pérdida por Lavado
        const alertaLavado = document.getElementById('alerta-lavado-norma');
        const perdidaRow = rowsArray.find(r => r['Malla'] && (r['Malla'].value.trim().toLowerCase().includes('pérdida') || r['Malla'].value.trim().toLowerCase().includes('perdida')));
        if (perdidaRow && alertaLavado) {
            const valPercent = parseFloat(perdidaRow['% Retenido parcial']?.value || 0);
            if (valPercent > 0.30) {
                alertaLavado.style.display = 'block';
                alertaLavado.style.backgroundColor = '#fef2f2';
                alertaLavado.style.color = '#991b1b';
                alertaLavado.style.border = '1px solid #fecaca';
                alertaLavado.innerHTML = '<i class="fa-solid fa-triangle-exclamation"></i> <strong>RECHAZO DE NORMA:</strong> Pérdida por Lavado (' + valPercent.toFixed(2) + '%) supera el límite de 0.30%.';
            } else if (valPercent > 0) {
                alertaLavado.style.display = 'block';
                alertaLavado.style.backgroundColor = '#f0fdf4';
                alertaLavado.style.color = '#166534';
                alertaLavado.style.border = '1px solid #bbf7d0';
                alertaLavado.innerHTML = '<i class="fa-solid fa-circle-check"></i> <strong>CONFORME:</strong> Pérdida por Lavado (' + valPercent.toFixed(2) + '%) cumple dentro del máximo permitido de 0.30%.';
            } else {
                alertaLavado.style.display = 'none';
            }
        }
    }

    function recalculateCompresion() {
        const rows = document.querySelectorAll('#tabla-body tr');
        rows.forEach(tr => {
            const cargaInput = tr.querySelector('input[data-col="Carga (lb)"]') || tr.querySelector('input[data-col="Carga (kg)"]');
            const areaInput = tr.querySelector('input[data-col="Área (in²)"]') || tr.querySelector('input[data-col="Área (cm²)"]');
            const rPsiInput = tr.querySelector('input[data-col="R. Compresión (lb/in²)"]') || 
                              tr.querySelector('input[data-col="Estimación R. compresión (lb/in²)"]');
            const rKgInput = tr.querySelector('input[data-col="R. Compresión (kg/cm²)"]') || 
                             tr.querySelector('input[data-col="R. Compresión. (kg/cm²)"]') || 
                             tr.querySelector('input[data-col="R. compresión. (kg/cm²)"]') || 
                             tr.querySelector('input[data-col="R. a la compresión (kg/cm²)"]') || 
                             tr.querySelector('input[data-col="Estimación R. compresión (kg/cm²)"]');

            if (cargaInput && areaInput) {
                const carga = parseFloat(cargaInput.value) || 0;
                const area = parseFloat(areaInput.value) || 0;

                if (carga > 0 && area > 0) {
                    const isLb = (cargaInput.getAttribute('data-col') || '').includes('(lb)');
                    const isIn2 = (areaInput.getAttribute('data-col') || '').includes('(in²)');
                    let psi = 0;
                    let kgcm2 = 0;

                    if (isLb && isIn2) {
                        psi = carga / area;
                        kgcm2 = psi * 0.070307;
                    } else if (!isLb && !isIn2) {
                        // Carga en kg y Área en cm² -> ya está en kg/cm²
                        kgcm2 = carga / area;
                        psi = kgcm2 * 14.223343;
                    } else if (isLb && !isIn2) {
                        // Carga en lb y Área en cm²
                        kgcm2 = (carga * 0.45359237) / area;
                        psi = kgcm2 * 14.223343;
                    } else {
                        // Carga en kg y Área en in²
                        psi = (carga * 2.2046226) / area;
                        kgcm2 = psi * 0.070307;
                    }

                    if (rPsiInput) rPsiInput.value = psi.toFixed(2);
                    if (rKgInput) rKgInput.value = kgcm2.toFixed(2);
                } else {
                    if (rPsiInput) rPsiInput.value = '';
                    if (rKgInput) rKgInput.value = '';
                }
            }
        });
    }

    function recalculateRevenimiento() {
        const rows = document.querySelectorAll('#tabla-body tr');
        rows.forEach(tr => {
            const revInInput = tr.querySelector('input[data-col="Reven. (in)"]');
            const revCmInput = tr.querySelector('input[data-col="Reven. (cm)"]');
            if (revInInput && revCmInput) {
                const valIn = parseFloat(revInInput.value) || 0;
                if (valIn > 0) {
                    revCmInput.value = (valIn * 2.54).toFixed(1);
                } else {
                    revCmInput.value = '';
                }
            }
        });
    }

    function recalculateCompactacion() {
        const rows = document.querySelectorAll('#tabla-body tr');
        rows.forEach(tr => {
            const pvsMaxInput = tr.querySelector('input[data-col="P.V.S Max (kg/m³)"]') || tr.querySelector('input[data-col="**P.V.S Max (kg/m³)"]');
            const pvsSitioInput = tr.querySelector('input[data-col="P.V.S.Sitio (kg/cm²)"]') || tr.querySelector('input[data-col="P.V.S.Sitio (kg/m³)"]');
            const compInput = tr.querySelector('input[data-col="Compactación ((%) (P/P))"]');

            if (pvsMaxInput && pvsSitioInput && compInput) {
                const max = parseFloat(pvsMaxInput.value) || 0;
                const sitio = parseFloat(pvsSitioInput.value) || 0;
                if (max > 0 && sitio > 0) {
                    compInput.value = ((sitio / max) * 100).toFixed(1);
                } else {
                    compInput.value = '';
                }
            }
        });
    }

    function recalculateFlexion() {
        const rows = document.querySelectorAll('#tabla-body tr');
        rows.forEach(tr => {
            const cargaInput = tr.querySelector('input[data-col="Carga (lb)"]');
            const bInput = tr.querySelector('input[data-col="Ancho Promedio (in)"]');
            const dInput = tr.querySelector('input[data-col="Espesor Promedio (in)"]');
            const lInput = tr.querySelector('input[data-col="Longitud de Apoyo (in)"]');
            const mrInput = tr.querySelector('input[data-col="Resistencia a la flexión. (kg/cm²)"]');

            if (cargaInput && bInput && dInput && lInput && mrInput) {
                const p = parseFloat(cargaInput.value) || 0;
                const b = parseFloat(bInput.value) || 0;
                const d = parseFloat(dInput.value) || 0;
                const l = parseFloat(lInput.value) || 0;

                if (p > 0 && b > 0 && d > 0 && l > 0) {
                    const mrPsi = (p * l) / (b * Math.pow(d, 2));
                    const mrKg = mrPsi * 0.070307;
                    mrInput.value = mrKg.toFixed(2);
                } else {
                    mrInput.value = '';
                }
            }
        });
    }

    function recalculateEdades() {
        const rows = document.querySelectorAll('#tabla-body tr');
        rows.forEach(tr => {
            const fFabInput = tr.querySelector('input[data-col="Fecha de Fabricación"]');
            const fEnsInput = tr.querySelector('input[data-col="Fecha de Ensayo"]') || tr.querySelector('input[data-col="Fecha de Ruptura"]') || tr.querySelector('input[data-col="Fecha de Finalización"]');
            const edadInput = tr.querySelector('input[data-col="Edad (Días)"]');

            if (fFabInput && fEnsInput && edadInput && fFabInput.value && fEnsInput.value) {
                const dFab = new Date(fFabInput.value);
                const dEns = new Date(fEnsInput.value);
                if (!isNaN(dFab) && !isNaN(dEns)) {
                    const diffTime = dEns - dFab;
                    const diffDays = Math.round(diffTime / (1000 * 60 * 60 * 24));
                    if (diffDays >= 0) {
                        edadInput.value = diffDays;
                    }
                }
            }
        });
    }

    function recalculateCodigoInforme() {
        const rows = document.querySelectorAll('#tabla-body tr');
        const codigos = [];
        rows.forEach(tr => {
            const inp = tr.querySelector('input[data-col="Código laboratorio"]') || 
                        tr.querySelector('input[data-save-col="Código laboratorio"]') ||
                        tr.querySelector('input[data-col="Codigo laboratorio"]') ||
                        tr.querySelector('input[data-col="codigo_lab"]');
            if (inp && inp.value.trim()) {
                codigos.push(inp.value.trim());
            }
        });

        const anioRef = (new Date()).getFullYear().toString().slice(-2);
        const tipoDefault = PREFIJO_OFICIAL || 'MS';

        const grupos = {};
        codigos.forEach(cod => {
            const m = cod.match(/([A-Za-z]{2,3})[-_](\d+)(?:[-_](\d{2,4}))?/);
            if (m) {
                const pref = m[1].toUpperCase();
                const num = parseInt(m[2], 10);
                const anio = m[3] ? m[3].slice(-2) : anioRef;
                if (!grupos[pref]) grupos[pref] = {};
                if (!grupos[pref][anio]) grupos[pref][anio] = [];
                grupos[pref][anio].push(num);
            } else {
                const digits = cod.match(/\d+/);
                if (digits) {
                    const num = parseInt(digits[0], 10);
                    if (!grupos[tipoDefault]) grupos[tipoDefault] = {};
                    if (!grupos[tipoDefault][anioRef]) grupos[tipoDefault][anioRef] = [];
                    grupos[tipoDefault][anioRef].push(num);
                }
            }
        });

        const bloques = [];
        for (const pref in grupos) {
            for (const anio in grupos[pref]) {
                const nums = Array.from(new Set(grupos[pref][anio])).sort((a, b) => a - b);
                if (nums.length === 0) continue;
                if (nums.length === 1) {
                    bloques.push(`CYCSA-INF-${pref}-${String(nums[0]).padStart(4, '0')}-${anio}`);
                    continue;
                }
                let esConsecutivo = true;
                for (let i = 0; i < nums.length - 1; i++) {
                    if (nums[i + 1] !== nums[i] + 1) {
                        esConsecutivo = false;
                        break;
                    }
                }
                if (esConsecutivo) {
                    const desde = String(nums[0]).padStart(4, '0');
                    const hasta = String(nums[nums.length - 1]).padStart(4, '0');
                    bloques.push(`CYCSA-INF-${pref}-${desde}-${hasta}-${anio}`);
                } else {
                    const lista = nums.map(n => String(n).padStart(4, '0')).join(', ');
                    bloques.push(`CYCSA-INF-${pref}-${lista}-${anio}`);
                }
            }
        }

        const nuevoCodigo = bloques.length > 0 ? bloques.join(' / ') : `CYCSA-INF-${tipoDefault}-0001-${anioRef}`;
        const elCodigo = document.getElementById('informe-codigo-consecutivo');
        if (elCodigo) {
            elCodigo.textContent = nuevoCodigo;
        }
    }

    function recalculateAll() {
        recalculateCompresion();
        recalculateRevenimiento();
        recalculateCompactacion();
        recalculateFlexion();
        recalculateEdades();
        recalculateCodigoInforme();
    }


    function prepararEnvioMatriz(e) {
        const rows = document.querySelectorAll('#tabla-body tr');
        const resultados = [];

        rows.forEach(tr => {
            const rowObj = {};
            let hasAnyVal = false;
            tr.querySelectorAll('input').forEach(inp => {
                const colName = inp.dataset.saveCol || inp.dataset.col;
                const val = inp.value;
                rowObj[colName] = val;
                if (val && val.trim() !== '' && val !== '—') {
                    hasAnyVal = true;
                }
            });
            if (hasAnyVal) {
                resultados.push(rowObj);
            }
        });

        const metaObj = {
            cliente_nombre: document.getElementById('meta_cliente_nombre') ? document.getElementById('meta_cliente_nombre').value : '',
            cliente_direccion: document.getElementById('meta_cliente_direccion') ? document.getElementById('meta_cliente_direccion').value : '',
            fecha_ingreso: document.getElementById('meta_fecha_ingreso') ? document.getElementById('meta_fecha_ingreso').value : '',
            tipo_muestra: document.getElementById('meta_tipo_muestra') ? document.getElementById('meta_tipo_muestra').value : '',
            procedimiento_muestreo: document.getElementById('meta_procedimiento_muestreo') ? document.getElementById('meta_procedimiento_muestreo').value : '',
            ensayo_realizado: document.getElementById('meta_ensayo_realizado') ? document.getElementById('meta_ensayo_realizado').value : '',
            proyecto: document.getElementById('meta_proyecto') ? document.getElementById('meta_proyecto').value : '',
            fecha_muestreo: document.getElementById('meta_fecha_muestreo') ? document.getElementById('meta_fecha_muestreo').value : '',
            fecha_ejecucion: document.getElementById('meta_fecha_ejecucion') ? document.getElementById('meta_fecha_ejecucion').value : '',
            fecha_emision: document.getElementById('meta_fecha_emision') ? document.getElementById('meta_fecha_emision').value : '',
            muestra_tomada_por: document.getElementById('meta_muestra_tomada_por') ? document.getElementById('meta_muestra_tomada_por').value : '',
            ubicacion: document.getElementById('meta_ubicacion') ? document.getElementById('meta_ubicacion').value : '',
            metodo_muestreo: document.getElementById('meta_metodo_muestreo') ? document.getElementById('meta_metodo_muestreo').value : '',
            codigo_formato: <?= json_encode($metadatos['codigo_formato'], JSON_UNESCAPED_UNICODE) ?>
        };

        const payload = {
            filas: resultados,
            metadatos: metaObj
        };

        document.getElementById('input_resultados_json').value = JSON.stringify(payload);
    }

    function guardarMatrizFullSubmit() {
        document.getElementById('form-matriz-completa').dispatchEvent(new Event('submit', { cancelable: true }));
        document.getElementById('form-matriz-completa').submit();
    }

    document.addEventListener('DOMContentLoaded', inicializarMatriz);
</script>

<?php if ($esSupervisor): ?>
<!-- MODAL SUPERVISOR: APROBAR MATRIZ TÉCNICA -->
<div id="modalAprobarMatriz" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); z-index:9999; backdrop-filter:blur(3px);">
    <div style="background:white; border-radius:12px; max-width:480px; margin:80px auto; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.15); border:1px solid #e2e8f0; font-family:inherit;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
            <h3 style="margin:0; color:#1e293b; font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-circle-check" style="color:#10b981;"></i> Aprobar Matriz Técnica
            </h3>
            <button type="button" onclick="cerrarModalAprobarEnMatriz()" style="background:none; border:none; font-size:22px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <p style="font-size:13.5px; color:#475569; margin:0 0 15px 0; line-height:1.5;">
            ¿Está seguro de que desea aprobar formalmente los resultados y cálculos de este ensayo? Al aprobar, se autorizará la emisión oficial y envío al cliente.
        </p>
        <form method="POST" action="/Cycsa/publico/operaciones/aprobar-matriz-producto">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_detalle" value="<?= $detalle['id'] ?>">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $detalle['id'] ?>">
            
            <div style="margin-bottom:16px;">
                <label style="font-size:12.5px; font-weight:600; color:#334155; display:block; margin-bottom:6px;">Nota de Aprobación (Opcional):</label>
                <input type="text" name="nota_aprobacion" class="form-control" style="width:100%; box-sizing:border-box; padding:9px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;" placeholder="Ej: Conforme a norma y tolerancias ASTM.">
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="cerrarModalAprobarEnMatriz()" class="btn-matriz-back" style="cursor:pointer; margin:0; padding:9px 18px;">Cancelar</button>
                <button type="submit" class="btn-matriz-primary" style="background:#059669; border-color:#059669; cursor:pointer; padding:9px 20px;">
                    <i class="fa-solid fa-check"></i> Confirmar Aprobación
                </button>
            </div>
        </form>
    </div>
</div>

<!-- MODAL SUPERVISOR: DEVOLVER MATRIZ TÉCNICA -->
<div id="modalDevolverMatriz" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); z-index:9999; backdrop-filter:blur(3px);">
    <div style="background:white; border-radius:12px; max-width:520px; margin:80px auto; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.15); border:1px solid #e2e8f0; font-family:inherit;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #fee2e2; padding-bottom:12px;">
            <h3 style="margin:0; color:#b91c1c; font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-rotate-left" style="color:#dc2626;"></i> Devolver Matriz Técnica (Observaciones)
            </h3>
            <button type="button" onclick="cerrarModalDevolverEnMatriz()" style="background:none; border:none; font-size:22px; cursor:pointer; color:#94a3b8;">&times;</button>
        </div>
        <p style="font-size:13.5px; color:#475569; margin:0 0 15px 0; line-height:1.5;">
            Indique claramente las observaciones o anomalías encontradas para que el personal técnico realice las correcciones requeridas.
        </p>
        <form method="POST" action="/Cycsa/publico/operaciones/devolver-matriz-producto">
            <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
            <input type="hidden" name="id_detalle" value="<?= $detalle['id'] ?>">
            <input type="hidden" name="redirect_to" value="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $detalle['id'] ?>">
            
            <div style="margin-bottom:16px;">
                <label style="font-size:12.5px; font-weight:700; color:#1e293b; display:block; margin-bottom:6px;">Motivo u Observaciones Técnicas * :</label>
                <textarea name="motivo_devolucion" required rows="4" style="width:100%; box-sizing:border-box; padding:10px 12px; border:1.5px solid #f87171; border-radius:6px; font-size:13.5px; font-family:inherit; resize:vertical;" placeholder="Ej: Corregir el valor de carga en la probeta #2. Se observa discrepancia con la aguja de la prensa."></textarea>
            </div>
            
            <div style="display:flex; justify-content:flex-end; gap:10px; margin-top:20px;">
                <button type="button" onclick="cerrarModalDevolverEnMatriz()" class="btn-matriz-back" style="cursor:pointer; margin:0; padding:9px 18px;">Cancelar</button>
                <button type="submit" class="btn-matriz-primary" style="background:#dc2626; border-color:#dc2626; cursor:pointer; padding:9px 20px;">
                    <i class="fa-solid fa-rotate-left"></i> Devolver Matriz al Laboratorio
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalAprobarEnMatriz() {
    document.getElementById('modalAprobarMatriz').style.display = 'block';
}
function cerrarModalAprobarEnMatriz() {
    document.getElementById('modalAprobarMatriz').style.display = 'none';
}
function abrirModalDevolverEnMatriz() {
    document.getElementById('modalDevolverMatriz').style.display = 'block';
}
function cerrarModalDevolverEnMatriz() {
    document.getElementById('modalDevolverMatriz').style.display = 'none';
}
window.addEventListener('click', (e) => {
    const modAp = document.getElementById('modalAprobarMatriz');
    const modDev = document.getElementById('modalDevolverMatriz');
    if (modAp && e.target === modAp) cerrarModalAprobarEnMatriz();
    if (modDev && e.target === modDev) cerrarModalDevolverEnMatriz();
});
</script>
<?php endif; ?>
