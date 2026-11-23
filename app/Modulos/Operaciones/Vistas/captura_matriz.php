<?php
// Vista de Captura de Matriz Técnica - Dinámica y Específica para los 21 Formatos CYCSA
$rolUsuario = (int)($_SESSION['usuario_rol'] ?? 0);
$esLaboratorista = ($rolUsuario === 6);
$modoSoloCapturaTecnica = !empty($modoSoloCapturaTecnica);
$permisosSesionVista = $_SESSION['usuario_permisos'] ?? [];
if (is_string($permisosSesionVista)) {
    $permisosSesionVista = json_decode($permisosSesionVista, true) ?: [];
}
$tieneAprobarExplicitoVista = is_array($permisosSesionVista) && (
    !empty($permisosSesionVista['operaciones']['aprobar'])
    || !empty($permisosSesionVista['control_calidad']['aprobar'])
);
$esSupervisor = !$modoSoloCapturaTecnica && (in_array($rolUsuario, [1, 2, 3], true) || (!$esLaboratorista && $tieneAprobarExplicitoVista));
$urlVolverCaptura = $modoSoloCapturaTecnica ? '/Cycsa/publico/captura-tecnica' : '/Cycsa/publico/operaciones';
$textoVolverCaptura = $modoSoloCapturaTecnica ? 'Volver a Captura Técnica' : 'Volver a Operaciones';
$idDetalleActivo = (int)(!empty($idDetalle) ? $idDetalle : (!empty($detalle['id']) ? $detalle['id'] : ($_GET['id_detalle'] ?? $_GET['id'] ?? 0)));
$idDetalle = $idDetalleActivo;
$detalle['id'] = $idDetalleActivo;
$detalle['id_detalle'] = $idDetalleActivo;
$revisionInfo = obtenerEstadoRevisionMatriz($detalle['resultados_json'] ?? '');
$archivoMd = !empty($detalle['archivo_markdown']) ? $detalle['archivo_markdown'] : resolverArchivoMarkdownEnsayo($detalle);
$detalle['archivo_markdown'] = $archivoMd;
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
$codigoInformeConsecutivo = generarCodigoInformeEnsayo($datosParaCodigo, $metadatos['fecha_muestreo'] ?? ($metadatos['fecha_ingreso'] ?? null), $metadatos['tipo_muestra'] ?? ($detalle['descripcion_ensayo'] ?? ''), $detalle['codigo_os'] ?? null);
$hojaTecnicaConfig = $hojaTecnicaConfig ?? \Cycsa\Modulos\Operaciones\Servicios\HojaTecnicaMatrizServicio::obtenerDefinicionPorFormato($archivoMd);
$capturaTecnicaInicial = $capturaTecnicaInicial ?? [];
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
        padding: 8px 16px 40px 16px;
        box-sizing: border-box;
    }

    .matriz-top-bar {
        position: sticky;
        top: 0;
        z-index: 1020;
        background: #ffffff;
        padding: 10px 16px;
        margin: -8px -16px 14px -16px;
        border-bottom: 2px solid #e2e8f0;
        box-shadow: 0 4px 12px rgba(0,0,0,0.05);
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 12px;
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
        min-width: 0;
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
        gap: 12px 20px;
    }
    @media (max-width: 900px) {
        .informe-metadatos-grid { grid-template-columns: 1fr; gap: 10px; }
        .informe-cabecera-row { flex-direction: column; text-align: center; }
        .informe-badge-box { text-align: center; }
    }
    .informe-col {
        display: flex;
        flex-direction: column;
        gap: 6px;
        min-width: 0;
    }
    .meta-row {
        display: grid;
        grid-template-columns: 195px 1fr;
        align-items: center;
        gap: 6px;
        min-width: 0;
    }
    @media (max-width: 1200px) {
        .meta-row { grid-template-columns: 175px 1fr; }
    }
    .meta-label-oficial {
        font-weight: 700;
        font-size: 12.5px;
        color: #000000;
        margin: 0;
        line-height: 1.25;
    }
    .meta-field-wrap {
        width: 100%;
        min-width: 0;
    }
    .meta-input-web {
        width: 100%;
        max-width: 100%;
        border: 1.5px solid #cbd5e1;
        border-radius: 6px;
        padding: 4px 8px;
        font-size: 12.5px;
        height: 30px;
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
        padding: 4px 8px;
        border-radius: 6px;
        color: #1d4ed8;
        font-size: 11.5px;
        font-weight: 700;
        display: flex;
        align-items: center;
        gap: 6px;
    }

    /* Tabla de Captura Densificada */
    .tabla-matriz-wrapper {
        width: 100%;
        overflow-x: auto;
        max-height: 580px;
        overflow-y: auto;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: white;
    }
    .tabla-matriz {
        width: 100%;
        min-width: 100%;
        border-collapse: separate;
        border-spacing: 0;
        font-size: 12.5px;
    }
    .tabla-matriz th {
        background: #f8fafc;
        color: #334155;
        font-weight: 700;
        padding: 7px 10px;
        text-align: left;
        border-bottom: 2px solid #cbd5e1;
        font-size: 11.5px;
        white-space: nowrap;
        position: sticky;
        top: 0;
        z-index: 5;
    }
    .tabla-matriz td {
        padding: 4px 6px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
    }
    .tabla-matriz tbody tr:hover {
        background: #f8fafc;
    }

    .matriz-input {
        width: 100%;
        min-width: 88px;
        height: 30px;
        padding: 4px 8px;
        font-size: 12.5px;
        border: 1.5px solid #cbd5e1;
        border-radius: 5px;
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
    .matriz-input-recepcion {
        background: #fff7ed;
        color: #9a3412;
        font-weight: 700;
        border-color: #fdba74;
    }
    .matriz-input-recepcion[readonly] {
        background: #ffedd5;
        color: #7c2d12;
        border-color: #fb923c;
        cursor: not-allowed;
    }
    .col-recepcion-oculta {
        display: none !important;
    }
</style>

<!-- TOP BAR STICKY (OPERACIONES LIMS - REDISEÑO PROFESIONAL) -->
<div class="matriz-top-bar" style="position: sticky; top: 0; z-index: 1020; background: #ffffff; padding: 14px 20px; margin: -10px -20px 20px -20px; border-bottom: 2px solid #e2e8f0; box-shadow: 0 4px 12px rgba(0,0,0,0.05); display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 15px;">
    <div>
        <a href="<?= htmlspecialchars($urlVolverCaptura, ENT_QUOTES, 'UTF-8') ?>" style="color: #64748b; text-decoration: none; font-size: 13.5px; font-weight: 600; display: inline-flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-arrow-left"></i> <?= htmlspecialchars($textoVolverCaptura, ENT_QUOTES, 'UTF-8') ?>
        </a>
        <div style="display: flex; align-items: center; gap: 10px; margin-top: 2px; flex-wrap: wrap;">
            <h2 style="margin: 0; color: #1e293b; font-size: 17px; font-weight: 800; display: flex; align-items: center; gap: 8px;" title="<?= htmlspecialchars($ensayoTituloOficial, ENT_QUOTES, 'UTF-8') ?>">
                <i class="fa-solid fa-flask-vial" style="color: var(--cycsa-azul);"></i> 
                <span><?= htmlspecialchars($schemaInfo['codigo_formato'] ?? $metadatos['codigo_formato'] ?? 'RT-FM-22') ?></span>
                <span style="font-family: monospace; font-size: 13px; font-weight: 700; color: var(--cycsa-azul); background: #eff6ff; border: 1px solid #bfdbfe; padding: 2px 7px; border-radius: 6px;">
                    <?= htmlspecialchars($detalle['codigo_os']) ?>
                </span>
            </h2>
            <span class="text-secondary d-none d-md-inline" style="font-size: 12.5px; max-width: 480px; white-space: nowrap; overflow: hidden; text-overflow: ellipsis;" title="<?= htmlspecialchars($ensayoTituloOficial, ENT_QUOTES, 'UTF-8') ?>">
                &bull; <?= htmlspecialchars($ensayoTituloOficial, ENT_QUOTES, 'UTF-8') ?>
            </span>
        </div>
    </div>

    <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap;">
        <!-- CHIP RT-FM-13 (CANDADO VERIFICADO) -->
        <span style="background: #f0fdf4; color: #166534; border: 1px solid #bbf7d0; font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-circle-check"></i> RT-FM-13: OK
        </span>

        <!-- CHIP DE FORMATO OFICIAL -->
        <span style="background: #0f172a; color: #ffffff; font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: 6px; font-family: monospace; display: inline-flex; align-items: center; gap: 6px;">
            <i class="fa-solid fa-file-lines"></i> <?= htmlspecialchars($metadatos['codigo_formato']) ?>
        </span>

        <!-- CHIP ESTADO REVISIÓN (NO CLICABLE, SOLO INFORMATIVO) -->
        <span style="background-color: <?= $revisionInfo['badge_bg'] ?>; color: <?= $revisionInfo['badge_color'] ?>; border: 1px solid <?= $revisionInfo['badge_border'] ?>; font-size: 12px; padding: 6px 14px; border-radius: 20px; font-weight: 800; display: inline-flex; align-items: center; gap: 6px;">
            <i class="<?= $revisionInfo['badge_icono'] ?>"></i> <?= htmlspecialchars($revisionInfo['estado_label']) ?>
        </span>

        <?php 
        $ccMatrizInfo = obtenerEstadoControlCalidadMatriz($detalle['resultados_json'] ?? '');
        if ($ccMatrizInfo['tiene_replicas']): 
        ?>
            <!-- CHIP ESTADO CONTROL DE CALIDAD DE RÉPLICAS -->
            <a href="/Cycsa/publico/control-calidad/ver?id_detalle=<?= (int)($detalle['id'] ?? ($idDetalle ?? 0)) ?>" 
               class="badge text-decoration-none <?= $ccMatrizInfo['badge_clase'] ?>" 
               style="font-size: 12px; font-weight: 700; padding: 6px 12px; border-radius: 20px; display: inline-flex; align-items: center; gap: 6px; box-shadow: 0 1px 2px rgba(0,0,0,0.05);" 
               title="Ver dictamen analítico en Control de Calidad: <?= htmlspecialchars($ccMatrizInfo['motivo_bloqueo'] ?? 'Todas las réplicas conformes') ?>">
                <i class="fa-solid fa-microscope"></i> <?= htmlspecialchars($ccMatrizInfo['estado_label']) ?>
            </a>
        <?php endif; ?>

        <?php if ($esSupervisor && $revisionInfo['tiene_resultados']): ?>
            <?php if ($revisionInfo['estado'] !== 'aprobada'): ?>
                <?php if ($ccMatrizInfo['tiene_replicas'] && !$ccMatrizInfo['puede_aprobar']): ?>
                    <button type="button" class="btn-matriz-secondary disabled" style="background: #f1f5f9; color: #94a3b8; border: 1.5px solid #cbd5e1; font-weight: 700; cursor: not-allowed; height: 38px; padding: 0 16px; display: inline-flex; align-items: center; gap: 6px; border-radius: 6px;" title="<?= htmlspecialchars($ccMatrizInfo['motivo_bloqueo']) ?>" disabled>
                        <i class="fa-solid fa-lock"></i> Aprobar Matriz
                    </button>
                <?php else: ?>
                    <button type="button" onclick="abrirModalAprobarEnMatriz()" class="btn-matriz-secondary" style="background: #ecfdf5; color: #047857; border: 1.5px solid #a7f3d0; font-weight: 700; cursor: pointer; height: 38px; padding: 0 16px; display: inline-flex; align-items: center; gap: 6px; border-radius: 6px;" title="Aprobar técnicamente los resultados y cálculos de este ensayo">
                        <i class="fa-solid fa-circle-check"></i> Aprobar Matriz
                    </button>
                <?php endif; ?>
            <?php else: ?>
                <!-- BOTÓN CORRECCIÓN ADMINISTRATIVA (FASE B) -->
                <button type="button" onclick="abrirModalCorreccionAdmin()" class="btn-matriz-secondary" style="background:#fffbeb; color:#b45309; border:1.5px solid #fde68a; font-weight:700; cursor:pointer; height:38px; padding:0 16px; display:inline-flex; align-items:center; gap:6px; border-radius:6px;" title="Solicitar corrección administrativa de datos (cliente, proyecto, etc.) emitiendo una nueva versión que sustituye a esta">
                    <i class="fa-solid fa-pen-ruler"></i> Solicitar Corrección Admin
                </button>
            <?php endif; ?>
            <?php if ($revisionInfo['estado'] !== 'devuelta' && $revisionInfo['estado'] !== 'aprobada'): ?>
                <button type="button" onclick="abrirModalDevolverEnMatriz()" class="btn-matriz-secondary" style="background: #fff1f2; color: #be123c; border: 1.5px solid #fecdd3; font-weight: 700; cursor: pointer; height: 38px; padding: 0 16px; display: inline-flex; align-items: center; gap: 6px; border-radius: 6px;" title="Devolver matriz al laboratorista con observaciones para corrección">
                    <i class="fa-solid fa-rotate-left"></i> Devolver Matriz
                </button>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($esModoHistorico): ?>
            <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $detalle['id'] ?>" class="btn-matriz-primary" style="background:#2563eb; text-decoration:none; height: 38px; padding: 0 18px;">
                <i class="fa-solid fa-pen-to-square"></i> Ir a Versión Actual (v<?= $versionActualNum ?>) para Modificar
            </a>
        <?php else: ?>
            <button type="button" onclick="guardarMatrizFullSubmit()" class="btn-matriz-primary" style="height: 38px; padding: 0 20px; font-size: 13.5px; border-radius: 6px;">
                <i class="fa-solid fa-floppy-disk"></i> Guardar Matriz del Producto
            </button>
            <?php if ($revisionInfo['estado'] !== 'aprobada'): ?>
                <button type="button" onclick="enviarMatrizARevisionSubmit()" class="btn-matriz-secondary" style="background:#eff6ff; color:#103487; border:1.5px solid #103487; font-weight:700; height: 38px; padding: 0 16px; font-size: 13px; border-radius: 6px; display:inline-flex; align-items:center; gap:6px; cursor:pointer;" title="Enviar matriz a la bandeja de revisión del supervisor">
                    <i class="fa-solid fa-paper-plane"></i> Enviar a Revisión del Supervisor
                </button>
            <?php endif; ?>
        <?php endif; ?>

        <?php if ($esSupervisor): ?>
        <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= $detalle['id'] ?><?= $esModoHistorico ? '&version=' . $versionSeleccionadaNum : '' ?>" target="_blank" class="btn-matriz-secondary" style="background:#f8fafc; color:#103487; border:1.5px solid #bfdbfe; font-weight:700; text-decoration:none; height: 38px; padding: 0 16px; display:inline-flex; align-items:center; gap:8px; border-radius: 6px;" title="Ver e imprimir formato técnico horizontal oficial con membrete CYCSA">
            <i class="fa-solid fa-print" style="color:#103487;"></i> Imprimir Matriz <?= $esModoHistorico ? "v{$versionSeleccionadaNum}" : "(Horizontal)" ?>
        </a>
        <?php endif; ?>
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

<?php if (!empty($detalle['id_os']) && function_exists('puedeVerBitacoraOS') && puedeVerBitacoraOS()): ?>
<div style="background:#f8fafc; border:1px solid #dbeafe; border-radius:8px; padding:10px 14px; margin:-8px 0 16px 0; display:flex; justify-content:space-between; align-items:center; gap:12px; flex-wrap:wrap;">
    <span style="font-size:12.5px; color:#334155;">
        <i class="fa-solid fa-clock-rotate-left" style="color:#103487;"></i>
        Auditoría de la orden <?= htmlspecialchars((string)($detalle['codigo_os'] ?? ''), ENT_QUOTES, 'UTF-8') ?> y de este ensayo.
    </span>
    <a href="/Cycsa/publico/ordenes-servicio/bitacora?id=<?= (int)$detalle['id_os'] ?>&entidad_tipo=matriz&entidad_id=<?= (int)$detalle['id'] ?>" style="font-size:12.5px; font-weight:700; color:#103487; text-decoration:none;">
        Ver bitácora de la O/S
    </a>
</div>
<?php endif; ?>

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

<form id="form-matriz-completa" method="POST" action="/Cycsa/publico/operaciones/guardar-matriz-producto" enctype="multipart/form-data" onsubmit="prepararEnvioMatriz(event)">
    <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?>">
    <input type="hidden" name="id_detalle" value="<?= $idDetalleActivo ?>">
    <input type="hidden" name="resultados_json" id="input_resultados_json" value="">
    <?php if ($modoSoloCapturaTecnica): ?>
    <input type="hidden" name="modo_solo_captura" value="1">
    <input type="hidden" name="redirect_to" value="/Cycsa/publico/captura-tecnica">
    <?php endif; ?>

    <!-- SECCIÓN 2: CABECERA DEL INFORME Y METADATOS OFICIALES (SOLO SUPERVISIÓN / ADMINISTRACIÓN) -->
    <?php if ($esSupervisor): ?>
    <details class="mb-3" style="background: white; border: 1.5px solid #cbd5e1; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.03); overflow: hidden;">
        <summary style="padding: 16px 22px; cursor: pointer; font-size: 15px; font-weight: 700; color: #1e293b; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; align-items: center; justify-content: space-between; list-style: none;">
            <span class="d-flex align-items-center gap-2">
                <i class="fa-solid fa-file-invoice text-primary"></i>
                <span>Metadatos Oficiales del Encabezado del Informe (CYCSA-RT-FM-22)</span>
            </span>
            <span class="badge bg-secondary-subtle text-secondary border" style="font-size: 12px; padding: 6px 12px; border-radius: 20px;">
                <i class="fa-solid fa-chevron-down me-1"></i> Desplegar / Editar Encabezado (~20 campos)
            </span>
        </summary>
        <div style="padding: 20px;">
            <!-- SECCIÓN 1: ENCABEZADO OFICIAL DE ENSAYO CYCSA-RT-FM-22 (Captura 144015) -->
    <div class="informe-oficial-card">
        <!-- Barra Superior Oficial con Logotipo CYCSA, Título Centrado y Código -->
        <div class="informe-cabecera-row">
            <div class="informe-logo-box" style="display: flex; align-items: center; gap: 12px;">
                <img src="/Cycsa/publico/img/logo.png" alt="CYCSA" style="height: 52px; max-width: 190px; object-fit: contain;">
                <div id="wrapper-header-logo-acreditacion" style="<?= !empty($metadatos['logo_acreditacion']) ? 'display: flex;' : 'display: none;' ?> align-items: center; border-left: 2px solid #cbd5e1; padding-left: 10px;">
                    <img id="header-logo-acreditacion-preview" src="<?= !empty($metadatos['logo_acreditacion']) ? htmlspecialchars($metadatos['logo_acreditacion'], ENT_QUOTES, 'UTF-8') : '' ?>" alt="Acreditación" style="height: 52px; max-width: 140px; object-fit: contain;" title="Sello / Logotipo de Acreditación">
                </div>
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
                    <label class="meta-label-oficial">Nombre del cliente:</label>
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
                    <label class="meta-label-oficial">Dirección:</label>
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
                    <label class="meta-label-oficial">Fecha  recepción ítems de ensayo:</label>
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
                    <label class="meta-label-oficial">Procedimiento de muestreo:</label>
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
                    <label class="meta-label-oficial">Proyecto:</label>
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
                    <label class="meta-label-oficial">Fecha muestreo:</label>
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
                    <label class="meta-label-oficial">Fecha de emisión del informe:</label>
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
                    <label class="meta-label-oficial">Ensayado por:</label>
                    <div class="meta-field-wrap">
                        <input type="text" name="metadatos[ensayado_por]" id="meta_ensayado_por" class="meta-input-web" value="<?= htmlspecialchars($metadatos['ensayado_por'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
                    </div>
                </div>

                <div class="meta-row">
                    <label class="meta-label-oficial">Ubicación:</label>
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

        <!-- Apartado Oficial para Sello / Logotipo de Acreditación (ISO/IEC 17025 / ONA) -->
        <div class="apartado-acreditacion-box" style="margin-top: 18px; padding: 14px 18px; background: #f8fafc; border: 1.5px dashed #94a3b8; border-radius: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 14px;">
            <div style="display: flex; align-items: center; gap: 14px; max-width: 65%;">
                <div style="background: #e0f2fe; color: #0284c7; width: 44px; height: 44px; border-radius: 8px; display: flex; align-items: center; justify-content: center; font-size: 20px; flex-shrink: 0;">
                    <i class="fa-solid fa-award"></i>
                </div>
                <div>
                    <div style="font-weight: 700; font-size: 13.5px; color: #0f172a; display: flex; align-items: center; gap: 8px;">
                        <span>Sello / Logotipo de Acreditación</span>
                        <span style="font-size: 11px; background: #e2e8f0; color: #334155; padding: 2px 7px; border-radius: 4px; font-weight: 600;">ISO/IEC 17025</span>
                    </div>
                    <div style="font-size: 12px; color: #64748b; margin-top: 2px;">
                        Cargue la imagen del organismo acreditador (ej. ONA). Se presentará a la par del logo de CYCSA con el mismo tamaño (52px de alto) en la cabecera, al imprimir y en el PDF enviado al cliente.
                    </div>
                </div>
            </div>
            <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                <input type="file" id="input_file_acreditacion" name="logo_acreditacion_file" accept="image/png,image/jpeg,image/jpg,image/webp,image/svg+xml" style="display: none;" onchange="manejarSubidaLogoAcreditacion(this)">
                <input type="hidden" name="metadatos[logo_acreditacion]" id="input_hidden_logo_acreditacion" value="<?= htmlspecialchars($metadatos['logo_acreditacion'] ?? '', ENT_QUOTES, 'UTF-8') ?>">

                <div id="badge-acreditacion-estado" style="<?= !empty($metadatos['logo_acreditacion']) ? '' : 'display: none;' ?> font-size: 12px; color: #059669; font-weight: 600; background: #ecfdf5; border: 1px solid #a7f3d0; padding: 5px 10px; border-radius: 6px;">
                    <i class="fa-solid fa-circle-check"></i> Logo asignado
                </div>

                <button type="button" class="btn btn-sm btn-outline-primary fw-bold" onclick="document.getElementById('input_file_acreditacion').click()" style="padding: 6px 14px; font-size: 12.5px; cursor: pointer; border-radius: 6px; background: #fff; border: 1.5px solid #2563eb; color: #2563eb;">
                    <i class="fa-solid fa-cloud-arrow-up me-1"></i> <span id="lbl-btn-subir-acreditacion"><?= !empty($metadatos['logo_acreditacion']) ? 'Cambiar imagen' : 'Subir imagen de acreditación' ?></span>
                </button>

                <button type="button" id="btn_quitar_acreditacion" class="btn btn-sm btn-outline-danger" style="<?= !empty($metadatos['logo_acreditacion']) ? '' : 'display: none;' ?> padding: 6px 12px; font-size: 12.5px; cursor: pointer; border-radius: 6px; background: #fff; border: 1.5px solid #dc2626; color: #dc2626;" onclick="quitarLogoAcreditacion()">
                    <i class="fa-solid fa-trash-can me-1"></i> Quitar
                </button>
            </div>
        </div>

        <!-- Apartado Oficial para Equipamiento Asignado (Nota 1 Densímetro Nuclear — Solo Supervisión/Recepción) -->
        <?php if ($esSupervisor): ?>
        <div class="apartado-equipo-box" style="margin-top: 14px; padding: 14px 18px; background: #f0fdf4; border: 1.5px solid #86efac; border-radius: 8px;">
            <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px; margin-bottom: 10px;">
                <div style="font-weight: 700; font-size: 13.5px; color: #166534; display: flex; align-items: center; gap: 8px;">
                    <i class="fa-solid fa-microchip"></i>
                    <span>Muestra tomada con equipo (Nota 1 Oficial de Ensayo / Densímetro Nuclear)</span>
                </div>
                <span style="font-size: 11px; background: #dcfce7; color: #15803d; border: 1px solid #bbf7d0; padding: 2px 8px; border-radius: 4px; font-weight: 600;">
                    CYCSA-PE-25 / PE-20
                </span>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(180px, 1fr)); gap: 12px;">
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #374151; display: block; margin-bottom: 4px;">Marca del Equipo:</label>
                    <input type="text" name="metadatos[equipo_marca]" id="meta_equipo_marca" class="meta-input-web" value="<?= htmlspecialchars($metadatos['equipo_marca'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. TROXLER">
                </div>
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #374151; display: block; margin-bottom: 4px;">Modelo:</label>
                    <input type="text" name="metadatos[equipo_modelo]" id="meta_equipo_modelo" class="meta-input-web" value="<?= htmlspecialchars($metadatos['equipo_modelo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. 3440">
                </div>
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #374151; display: block; margin-bottom: 4px;">Número de Serie (N/S):</label>
                    <input type="text" name="metadatos[equipo_serie]" id="meta_equipo_serie" class="meta-input-web" value="<?= htmlspecialchars($metadatos['equipo_serie'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. 62677">
                </div>
                <div>
                    <label style="font-size: 11.5px; font-weight: 700; color: #374151; display: block; margin-bottom: 4px;">Código Interno CYCSA:</label>
                    <input type="text" name="metadatos[equipo_codigo]" id="meta_equipo_codigo" class="meta-input-web" value="<?= htmlspecialchars($metadatos['equipo_codigo'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. Eq-6555">
                </div>
            </div>
            <div style="margin-top: 8px; font-size: 11.5px; color: #166534; font-style: italic;">
                * Se imprimirá en el informe oficial únicamente si se registran datos de equipo: <strong>Nota 1: Muestra tomada con equipo [Marca], Modelo: [Modelo], N/S : [Serie] [Código]</strong>
            </div>
        </div>
        <?php endif; ?>

        <!-- Apartado Aditivos, Observaciones Pendientes y Asteriscos (*) -->
        <div class="apartado-notas-informe-box" style="margin-top: 14px; padding: 14px 18px; background: #f8fafc; border: 1.5px solid #cbd5e1; border-radius: 8px;">
            <div style="font-weight: 700; font-size: 13px; color: #1e293b; margin-bottom: 10px; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-flask-vial text-primary"></i>
                <span>Aditivos, Observaciones Pendientes y Marcadores de Informe (*)</span>
            </div>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 12px;">
                <div>
                    <label for="meta_nota_aditivos" style="font-size: 11.5px; font-weight: 700; color: #374151; display: block; margin-bottom: 4px;">Nota de Aditivos:</label>
                    <input type="text" name="metadatos[nota_aditivos]" id="meta_nota_aditivos" class="meta-input-web" value="<?= htmlspecialchars($metadatos['nota_aditivos'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. Aditivo plastificante Sika 1.2% por peso de cemento">
                </div>
                <div>
                    <label for="meta_observaciones_pendientes" style="font-size: 11.5px; font-weight: 700; color: #374151; display: block; margin-bottom: 4px;">Observaciones Pendientes:</label>
                    <input type="text" name="metadatos[observaciones_pendientes]" id="meta_observaciones_pendientes" class="meta-input-web" value="<?= htmlspecialchars($metadatos['observaciones_pendientes'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. Pendiente confirmación de datos de recepción">
                </div>
                <div>
                    <label for="meta_columnas_asterisco" style="font-size: 11.5px; font-weight: 700; color: #374151; display: block; margin-bottom: 4px;">Columnas con Asterisco (*):</label>
                    <input type="text" name="metadatos[columnas_asterisco]" id="meta_columnas_asterisco" class="meta-input-web" value="<?= htmlspecialchars(is_array($metadatos['columnas_asterisco'] ?? null) ? implode(', ', $metadatos['columnas_asterisco']) : (string)($metadatos['columnas_asterisco'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej. Peso Volumétrico (kg/m³), Temp. (°C)">
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
        </div>
    </details>
    <?php else: ?>
    <!-- Técnico / Laboratorista: sin acceso visual a datos de cliente/recepción; el servidor preserva metadatos oficiales -->
    <?php if ($isGranulometria): ?>
    <div style="margin-bottom: 14px; display: flex; align-items: center; justify-content: flex-end;">
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
    </div>
    <?php endif; ?>
    <?php endif; ?>

    <style>
    /* SECCIÓN CAPTURA TÉCNICA EN LÍNEA DEL TÉCNICO (POR FORMATO / HOJA DE CÁLCULO) */
    .ht-lab-card {
        margin-bottom: 20px;
        padding: 20px 22px;
        border-radius: 10px;
        border: 1px solid #cbd5e1;
        border-top: 4px solid #1F3864;
        background: #ffffff;
    }
    .ht-lab-header {
        display: flex;
        justify-content: space-between;
        align-items: flex-start;
        flex-wrap: wrap;
        gap: 12px;
        margin-bottom: 14px;
        padding-bottom: 12px;
        border-bottom: 1px solid #e2e8f0;
    }
    .ht-lab-badge {
        display: inline-flex;
        align-items: center;
        gap: 6px;
        padding: 3px 10px;
        border-radius: 999px;
        background: #EEF1F7;
        color: #1F3864;
        font-size: 12px;
        font-weight: 700;
        border: 1px solid #d1d9e6;
    }
    .ht-table-wrap {
        width: 100%;
        overflow-x: auto;
        border: 1px solid #e2e8f0;
        border-radius: 8px;
        background: #ffffff;
    }
    .ht-table {
        width: 100%;
        border-collapse: collapse;
        font-size: 13.5px;
    }
    .ht-table thead th {
        background: #f8fafc;
        color: #334155;
        font-size: 12px;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.03em;
        padding: 11px 10px;
        border-bottom: 1px solid #e2e8f0;
        white-space: nowrap;
        text-align: left;
    }
    .ht-table thead th.ht-th-calc {
        background: #f1f5f9;
        color: #1F3864;
    }
    .ht-table tbody td {
        padding: 10px 8px;
        border-bottom: 1px solid #f1f5f9;
        vertical-align: middle;
        white-space: nowrap;
    }
    .ht-table tbody tr:last-child td {
        border-bottom: 0;
    }
    .ht-input,
    .ht-select {
        height: 38px;
        padding: 6px 10px;
        border: 1px solid #cbd5e1;
        border-radius: 8px;
        background: #ffffff;
        color: #111827;
        font-size: 13.5px;
        font-family: inherit;
        box-sizing: border-box;
        transition: border-color 0.15s ease, box-shadow 0.15s ease;
    }
    .ht-input:focus,
    .ht-select:focus {
        outline: none;
        border-color: #1F3864;
        box-shadow: 0 0 0 2px rgba(31, 56, 100, 0.14);
    }
    .ht-input-calc {
        height: 38px;
        padding: 6px 10px;
        border: 1px solid #dbe2ec;
        border-radius: 8px;
        background: #f1f5f9;
        color: #475569;
        font-size: 13.5px;
        font-weight: 600;
        text-align: center;
        box-sizing: border-box;
        cursor: not-allowed;
        user-select: none;
        pointer-events: none;
    }
    .ht-input-calc.is-highlight {
        background: #EEF1F7;
        color: #1F3864;
        border-color: #cbd5e1;
        font-weight: 700;
    }
    .matriz-input-calculated,
    .matriz-input-locked {
        cursor: not-allowed !important;
        user-select: none;
        background-color: #f1f5f9 !important;
        color: #334155 !important;
    }
    .ht-code-cell {
        font-weight: 700;
        color: #1F3864;
        font-size: 13px;
        padding-left: 12px !important;
    }
    </style>

    <!-- SECCIÓN 1A: CAPTURA TÉCNICA DEL TÉCNICO DE LABORATORIO (MEDICIONES CRUDAS POR PRODUCTO) -->
    <?php $esSoloRecepcionCompactacion = (!$esSupervisor && (function_exists('esItemCompactacion') ? esItemCompactacion($detalle) : (strtolower(trim((string)$archivoMd)) === 'compactacion_densimetro_nuclear.md'))); ?>
    <?php if ($esSoloRecepcionCompactacion): ?>
    <div id="seccion-captura-tecnica-lab" class="ht-lab-card">
        <div style="font-size: 13.5px; color: #334155;">
            <i class="fa-solid fa-circle-info me-1" style="color: #1F3864;"></i>
            Los ensayos de <strong>Compactación</strong> se registran directamente en <strong>Órdenes de Servicio (Recepción)</strong>. No requieren captura técnica adicional en este módulo.
        </div>
    </div>
    <?php else: ?>
    <div id="seccion-captura-tecnica-lab" class="ht-lab-card">
        <div class="ht-lab-header">
            <div>
                <div style="display: flex; align-items: center; gap: 10px; flex-wrap: wrap;">
                    <h3 style="margin: 0; color: #111827; font-size: 16.5px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                        <i class="fa-solid fa-flask" style="color: #1F3864;"></i>
                        <?= htmlspecialchars((string)($hojaTecnicaConfig['titulo'] ?? 'Captura Técnica de Laboratorio'), ENT_QUOTES, 'UTF-8') ?>
                    </h3>
                    <span class="ht-lab-badge"><?= htmlspecialchars((string)($hojaTecnicaConfig['codigo_hc'] ?? 'HC'), ENT_QUOTES, 'UTF-8') ?></span>
                    <span style="font-size: 12.5px; color: #64748b;"><?= htmlspecialchars((string)($hojaTecnicaConfig['norma'] ?? ''), ENT_QUOTES, 'UTF-8') ?></span>
                </div>
                <p style="margin: 5px 0 0 0; font-size: 13px; color: #475569;">
                    <?= htmlspecialchars((string)($hojaTecnicaConfig['subtitulo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>
                </p>
            </div>
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <?php if (!empty($hojaTecnicaConfig['permite_agregar_filas']) && !$esModoHistorico): ?>
                    <button type="button" class="btn-matriz-secondary" onclick="agregarFilaHojaTecnica()" style="background: #ffffff; color: #1F3864; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; font-size: 13px; font-weight: 600; cursor: pointer;">
                        + Agregar <?= htmlspecialchars((string)($hojaTecnicaConfig['etiqueta_fila'] ?? 'fila'), ENT_QUOTES, 'UTF-8') ?>
                    </button>
                <?php endif; ?>
                <button type="button" id="btn-toggle-ht-body" class="btn-matriz-secondary" onclick="togglePanelHojaTecnica()" style="background: #ffffff; color: #334155; border: 1px solid #cbd5e1; border-radius: 8px; padding: 6px 12px; font-size: 13px; font-weight: 600; cursor: pointer;">
                    Ocultar captura técnica
                </button>
            </div>
        </div>

        <div id="ht-lab-body">
            <div class="ht-table-wrap">
                <table class="ht-table" id="tabla-captura-tecnica-lab" data-motor="<?= htmlspecialchars((string)($hojaTecnicaConfig['motor_calculo'] ?? ''), ENT_QUOTES, 'UTF-8') ?>" data-codigo-hc="<?= htmlspecialchars((string)($hojaTecnicaConfig['codigo_hc'] ?? ''), ENT_QUOTES, 'UTF-8') ?>">
                    <thead id="ht-tabla-header"></thead>
                    <tbody id="ht-tabla-body"></tbody>
                </table>
            </div>
            <div id="ht-resumen-bar" style="display: none; margin-top: 10px; padding: 10px 14px; background: #EEF1F7; border: 1px solid #d1d9e6; border-radius: 8px; font-size: 13px; color: #1F3864; font-weight: 600;"></div>
        </div>
    </div>
    <?php endif; ?>

    <!-- SECCIÓN 1B: GRILLA DE CAPTURA Y MEDICIONES (INFORME OFICIAL RT-FM-22) -->
    <div class="seccion-form" style="margin-bottom: 24px; padding: 22px; border-radius: 8px; border: 1px solid #cbd5e1; border-top: 4px solid var(--cycsa-azul, #103487); background: #ffffff;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px; border-bottom: 1px solid #e2e8f0; padding-bottom: 12px;">
            <h3 style="margin: 0; color: #1e293b; font-size: 17px; font-weight: 700; display: flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-table-cells" style="color: var(--cycsa-azul);"></i> <?= $esSupervisor ? 'Grilla de Captura y Mediciones de Ensayo' : 'Resultados Técnicos del Ensayo (Solo Datos de Técnico)' ?>
            </h3>
            <span style="font-size: 12px; color: #64748b; font-weight: 600;">
                <i class="fa-solid fa-keyboard me-1"></i> <?= $esSupervisor ? 'Ingrese lecturas y réplicas directamente en la tabla' : 'Solo lectura · Se calcula automáticamente desde Captura Técnica' ?>
            </span>
        </div>
        <!-- CONTENEDOR DE LA TABLA DINÁMICA -->
            <?php if (!empty($tieneCamposRecepcion) && $esSupervisor): ?>
            <div id="recepcion-vs-tecnico-bar" style="margin: 0 0 14px; padding: 12px 15px; border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 9px; display: flex; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
                <div style="display: flex; align-items: center; gap: 12px; flex-wrap: wrap; font-size: 13px;">
                    <span style="display:inline-flex; align-items:center; gap:6px; background:#f1f5f9; color:#334155; border:1px solid #cbd5e1; padding:4px 10px; border-radius:999px; font-weight:800;">
                        <i class="fa-solid fa-inbox"></i> LLENA RECEPCIÓN (O/S RT-FM-13)
                    </span>
                    <span style="color:#475569;">Pre-cargado desde Recepción (<strong><?= (int)($muestrasRecepcionCompletas ?? 0) ?>/<?= count($muestrasSeteadas ?? []) ?></strong> muestras).</span>
                    <span style="display:inline-flex; align-items:center; gap:6px; background:#eff6ff; color:#103487; border:1px solid #bfdbfe; padding:4px 10px; border-radius:999px; font-weight:800;">
                        <i class="fa-solid fa-flask"></i> Viene del laboratorio
                    </span>
                    <span style="display:inline-flex; align-items:center; gap:6px; background:#f8fafc; color:#0f172a; border:1px solid #cbd5e1; padding:4px 10px; border-radius:999px; font-weight:800;">
                        <i class="fa-solid fa-calculator"></i> Calculado · Solo lectura
                    </span>
                </div>
                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                    <button type="button" id="btn-toggle-plegar-recepcion" class="btn-matriz-secondary" onclick="togglePlegarColumnasRecepcion()" style="background:#ffffff; color:#103487; border-color:#cbd5e1; font-weight:700; font-size:13px;">
                        <i class="fa-solid fa-table-columns"></i> Plegar columnas de recepción
                    </button>
                    <?php if ($esSupervisor && !$esModoHistorico): ?>
                    <button type="button" id="btn-toggle-recepcion-lock" class="btn-matriz-secondary" onclick="toggleEdicionRecepcionMatriz()" style="background:#fff; color:#334155; border-color:#cbd5e1; font-weight:700; font-size:13px;">
                        <i class="fa-solid fa-lock"></i> Desbloquear casillas de Recepción
                    </button>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>
            <div id="replicas-control-bar" style="display:none; margin: 0 0 14px; padding: 13px 15px; border: 1px solid #99f6e4; background: #f0fdfa; border-radius: 9px; align-items: center; justify-content: space-between; gap: 14px; flex-wrap: wrap;">
                <div style="font-size: 12.5px; color: #115e59; line-height: 1.45;">
                    <strong><i class="fa-solid fa-code-compare"></i> Réplicas de Control de Calidad</strong><br>
                    Marque la casilla de las muestras que requieren réplica. Se cargará automáticamente una fila duplicada con el sufijo <strong>-CR</strong> para capturar sus mediciones de forma independiente.
                </div>
                <?php if (!$esModoHistorico): ?>
                <button type="button" class="btn-matriz-secondary" onclick="sincronizarReplicasSeleccionadas()" style="background:#0f766e; color:#fff; border-color:#0f766e;">
                    <i class="fa-solid fa-arrows-rotate"></i> Actualizar réplicas
                </button>
                <?php endif; ?>
            </div>
            <?php if ($esSupervisor):
                $disclaimerDefaultCap = (string)($schemaInfo['disclaimer'] ?? 'Consultoría y Construcción SA.CYCSA es responsable únicamente de la exactitud de los resultados realizados en las muestras recibidas y tomadas en campo. No se debe de reproducir este informe de ensayo sin la aprobación formal de Consultoría y Construcción SA. CYCSA. ** Información Proporcionada por el cliente');
                $disclaimerActualCap = (array_key_exists('disclaimer_personalizado', $metadatos) && $metadatos['disclaimer_personalizado'] !== null)
                    ? (string)$metadatos['disclaimer_personalizado']
                    : $disclaimerDefaultCap;
                $notasDefaultCap = (isset($schemaInfo['notas']) && is_array($schemaInfo['notas'])) ? $schemaInfo['notas'] : [];
                $notasActualesCap = !empty($metadatos['notas_informe_personalizadas'])
                    ? (is_array($metadatos['notas_informe'] ?? null) ? $metadatos['notas_informe'] : [])
                    : $notasDefaultCap;
                $notasTextoCap = implode("\n", array_map(static fn($n) => trim((string)$n), $notasActualesCap));
            ?>
            <div id="panel-notas-matriz-arriba" style="margin: 0 0 14px; padding: 13px 15px; border: 1px solid #cbd5e1; background: #f8fafc; border-radius: 9px;">
                <div style="font-size: 13px; font-weight: 700; color: #1e293b; margin-bottom: 8px; display: flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-pen-to-square" style="color: #103487;"></i>
                    <span>Notas y Cláusula del Informe (Editable)</span>
                </div>
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 10px;">
                    <div>
                        <label for="meta_disclaimer_personalizado" style="font-size: 11.5px; font-weight: 700; color: #374151; display: block; margin-bottom: 4px;">Cláusula oficial / Responsabilidad:</label>
                        <textarea name="metadatos[disclaimer_personalizado]" id="meta_disclaimer_personalizado" rows="2" class="meta-input-web" style="resize: vertical;"><?= htmlspecialchars($disclaimerActualCap, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                    <div>
                        <label for="meta_notas_informe" style="font-size: 11.5px; font-weight: 700; color: #374151; display: block; margin-bottom: 4px;">Notas del ensayo (una por línea):</label>
                        <textarea name="metadatos[notas_informe]" id="meta_notas_informe" rows="2" class="meta-input-web" style="resize: vertical;" placeholder='Ej. Nota: Ensayos realizados in Situ: Ensayo "CYCSA-PE-09" "CYCSA-PE-27"'><?= htmlspecialchars($notasTextoCap, ENT_QUOTES, 'UTF-8') ?></textarea>
                    </div>
                </div>
            </div>
            <?php endif; ?>
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
            <?php if ($esSupervisor): ?>
            <div style="margin-top: 12px; color: #475569; font-size: 12px;">
                <div>Firmante oficial: <?= htmlspecialchars($schemaInfo['firmante_nombre'] ?? 'Ing. Noel Quintana Lira') ?> — <?= htmlspecialchars($schemaInfo['firmante_cargo'] ?? 'Gerente General') ?></div>
            </div>
            <?php endif; ?>

            <!-- ALERTA DE PÉRDIDA POR LAVADO -->
            <div id="alerta-lavado-norma" style="display:none; margin-top:15px; padding:12px 16px; border-radius:8px; font-size:13px; font-weight:600;"></div>

            <!-- BARRA INFORMATIVA INFERIOR (EL GUARDADO SE REALIZA DESDE LA BARRA STICKY SUPERIOR) -->
            <div style="display: flex; justify-content: space-between; align-items: center; margin-top: 20px; border-top: 1px solid #e2e8f0; padding-top: 16px; flex-wrap: wrap; gap: 12px;">
                <span style="font-size:13px; color:#0369a1; font-weight:600;">
                    <i class="fa-solid fa-shield-halved"></i> Formato técnico oficial sincronizado con <?= htmlspecialchars($detalle['archivo_markdown'] ?: 'ISO 17025') ?>
                </span>
                <div style="display: flex; gap: 10px; align-items: center;">
                    <a href="<?= htmlspecialchars($urlVolverCaptura, ENT_QUOTES, 'UTF-8') ?>" class="btn-matriz-back" style="height: 38px; padding: 0 16px; display: inline-flex; align-items: center;">
                        <i class="fa-solid fa-arrow-left me-1"></i> <?= htmlspecialchars($textoVolverCaptura, ENT_QUOTES, 'UTF-8') ?>
                    </a>

                </div>
            </div>
        </div>
    </div>

    
</form>

<script>
    const ARCHIVO_MARKDOWN = <?= json_encode($archivoMd, JSON_UNESCAPED_UNICODE) ?>;
    const FORMATOS_SCHEMA = <?= $formatosSchemaJson ?>;
    const RAW_RESULTADOS = <?= json_encode(json_decode($detalle['resultados_json'] ?? '[]', true) ?: [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const DATOS_INICIALES = Array.isArray(RAW_RESULTADOS) ? RAW_RESULTADOS : (RAW_RESULTADOS.filas || []);
    const MUESTRAS_SETEADAS = <?= json_encode($muestrasSeteadas ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const ES_MODO_HISTORICO = <?= $esModoHistorico ? 'true' : 'false' ?>;
    const ES_SUPERVISOR = <?= $esSupervisor ? 'true' : 'false' ?>;
    const LAB_DIVISOR_KGCM2 = <?= json_encode(\Cycsa\Modulos\Operaciones\Servicios\ConfigCalculoLabServicio::obtenerFloat('cilindros_divisor_kgcm2', 14.2029)) ?>;
    const LAB_REDONDEAR_PSI_10 = <?= \Cycsa\Modulos\Operaciones\Servicios\ConfigCalculoLabServicio::obtenerBool('cilindros_redondear_psi_10', true) ? 'true' : 'false' ?>;
    const PREFIJO_OFICIAL = <?= json_encode($prefijoMuestraOS ?? determinarPrefijoMuestraOS($detalle)) ?>;
    const CLASIFICACION_COLUMNAS = <?= json_encode($clasificacionColumnas ?? [], JSON_UNESCAPED_UNICODE) ?>;
    const TIENE_CAMPOS_RECEPCION = <?= !empty($tieneCamposRecepcion) ? 'true' : 'false' ?>;
    const ES_ENSAYO_ROTURA_LAB = <?= !empty($esEnsayoRoturaLab) ? 'true' : 'false' ?>;
    const HOJA_TECNICA_CONFIG = <?= json_encode($hojaTecnicaConfig ?? [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    const CAPTURA_TECNICA_INICIAL = <?= json_encode($capturaTecnicaInicial ?? [], JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT) ?>;
    let capturaTecnicaFilas = [];
    let permitirEdicionRecepcionMatriz = false;
    let columnasRecepcionPlegadas = true;
    let replicasHabilitadas = false;
    let columnaCodigoReplica = null;
    let columnaNombreReplica = null;

    const DEFAULT_ROWS_BY_FORMAT = {
        "formato_de_granulometria_de_suelo.md": [
            { "Malla": "3\"", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "2\"", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1 1/2\"", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1\"", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/4\"", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1/2\"", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/8\"", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 4", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 8", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 10", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 16", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 20", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 30", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 40", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 50", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 60", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 80", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 100", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 140", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 200", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Fondo", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Pérdida lavado", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Suma", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Límite Líquido", "P. Retenido parcial (g)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Límite Plástico", "P. Retenido parcial (g)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "I.P", "P. Retenido parcial (g)": "—", "% Retenido parcial": "—", "% Acumulativo": "—", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" }
        ],
        "granulomnetria_de_agregados.md": [
            { "Malla": "3\"", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "2\"", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1 1/2\"", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1\"", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/4\"", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "1/2\"", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "3/8\"", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 4", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 8", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 10", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 16", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 20", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 30", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 40", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 50", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 60", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 80", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 100", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 140", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "No. 200", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Fondo", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Pérdida lavado", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" },
            { "Malla": "Suma", "P. Retenido parcial (g)": "", "% Retenido parcial": "", "% Acumulativo": "", "% que pasa la malla": "", "Límite Mín": "", "Límite Máx": "" }
        ],
        "formato_de_resistencia_de_bloques.md": [
            { "Descripción": "Área bruta.", "Unidad": "in²", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Área neta.", "Unidad": "in²", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "50% A.B", "Límite Máx": "" },
            { "Descripción": "Carga", "Unidad": "lb", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "-", "Límite Máx": "-" },
            { "Descripción": "Largo", "Unidad": "cm", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Alto", "Unidad": "cm", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Espesor pared", "Unidad": "mm", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "25", "Límite Máx": "" },
            { "Descripción": "Espesor tabique", "Unidad": "mm", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "25", "Límite Máx": "" },
            { "Descripción": "Peso recibido", "Unidad": "g", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Peso sumergido", "Unidad": "g", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Peso saturado", "Unidad": "g", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
            { "Descripción": "Peso seco", "Unidad": "g", "Método de ensayo": "CYCSA-PE-32", "Resultado": "", "Límite Mín": "", "Límite Máx": "" },
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

    function normalizarTextoClave(txt) {
        if (!txt) return '';
        return String(txt)
            .replace(/²/g, '2')
            .replace(/³/g, '3')
            .normalize('NFD')
            .replace(/[\u0300-\u036f]/g, '')
            .toLowerCase()
            .replace(/[^a-z0-9]/g, '');
    }

    const DICCIONARIO_SINONIMOS = [
        ['carga', 'cargalb', 'cargakg', 'cargamaxima', 'cargamaximalb', 'cargamaximakg'],
        ['rcompresionkgcm2', 'resistenciakgcm2', 'rcompresionlbin2', 'rcompresionlbpin2', 'resistencialbin2', 'resistencialbpin2', 'rcompresionpsi', 'resistenciapsi', 'resistencia', 'rcompresion', 'resistenciaalacompresion'],
        ['fechadefabricacion', 'fechademoldeo', 'fechamoldeo', 'fechafabricacion', 'fechadecolado', 'fechacolado'],
        ['fechadeensayo', 'fechaensayo', 'fechaderuptura', 'fecharuptura'],
        ['edaddias', 'edad'],
        ['disenodeconcretolbin2', 'disenodeconcretokgcm2', 'fcespecificado', 'fckgcm2', 'fc', 'disenokgcm2', 'diseno'],
        ['areain2', 'areacm2', 'areain', 'areacm', 'area'],
        ['nombremuestra', 'nombredelamuestra', 'identificacion', 'muestra', 'codigodecampo', 'codigocampo'],
        ['revenin', 'revenimientoin', 'revenimiento'],
        ['revencm', 'revenimientocm'],
        ['tempc', 'temperaturac', 'temperatura', 'temp'],
        ['pesovolumetricokgm3', 'pesovolumetrico', 'pvs'],
        ['pvsmaxkgm3', 'pvsmax', 'pesovolumetricomaximo', 'pesovolumetricomaximokgm3'],
        ['pvssitiokgm3', 'pvssitiokgcm2', 'pvssitio', 'pesovolumetricositio', 'pesovolumetricositiokgm3'],
        ['humedaddepct', 'humedaddecampopct', 'humedadsitiopct', 'humedadcampo'],
        ['compactacionpp', 'compactacionpct', 'porcentajecompactacion'],
        ['diametrocm', 'diametroin', 'diametro'],
        ['porcentajeresistencia', 'porcentajecumplimiento'],
        ['tipodefractura', 'fractura']
    ];

    function extraerUnidadDeNombreColumna(nombreCol) {
        if (!nombreCol) return '';
        const m = String(nombreCol).match(/\(([^)]+)\)/);
        if (!m) return '';
        return m[1].trim().toLowerCase().replace(/²/g, '2').replace(/³/g, '3');
    }

    function convertirUnidadMedicion(valorNum, uOri, uDes) {
        if (isNaN(valorNum) || valorNum === null || valorNum === undefined || valorNum === '') return valorNum;
        uOri = (uOri || '').toLowerCase();
        uDes = (uDes || '').toLowerCase();
        if (uOri === uDes || !uOri || !uDes) return valorNum;

        // Carga / Masa: kg <-> lb
        if (uOri === 'kg' && uDes === 'lb') return parseFloat((valorNum * 2.20462262).toFixed(1));
        if (uOri === 'lb' && uDes === 'kg') return parseFloat((valorNum * 0.45359237).toFixed(1));

        // Área: cm² <-> in²
        if (uOri === 'cm2' && uDes === 'in2') return parseFloat((valorNum / 6.4516).toFixed(2));
        if (uOri === 'in2' && uDes === 'cm2') return parseFloat((valorNum * 6.4516).toFixed(2));

        // Resistencia / Presión: kg/cm² <-> lb/in² (psi)
        if ((uOri === 'kg/cm2' || uOri === 'kgcm2') && (uDes === 'lb/in2' || uDes === 'lbin2' || uDes === 'psi')) {
            return parseFloat((valorNum * 14.223343).toFixed(1));
        }
        if ((uOri === 'lb/in2' || uOri === 'lbin2' || uOri === 'psi') && (uDes === 'kg/cm2' || uDes === 'kgcm2')) {
            return parseFloat((valorNum * 0.070307).toFixed(1));
        }

        // Longitud: in <-> cm (Revenimiento ASTM C143 redondeado al 0.5 cm más cercano)
        if (uOri === 'in' && uDes === 'cm') return Math.round(valorNum * 2.54 * 2) / 2;
        if (uOri === 'cm' && uDes === 'in') return parseFloat((valorNum / 2.54).toFixed(2));

        return valorNum;
    }

    function buscarValorEnFila(rowData, col, calcCol) {
        if (!rowData || typeof rowData !== 'object') return '';
        
        // 1. Coincidencia directa exacta
        if (rowData[col] !== undefined && rowData[col] !== null && String(rowData[col]).trim() !== '') {
            return String(rowData[col]);
        }
        
        // 2. Coincidencia por alias
        if (calcCol && rowData[calcCol] !== undefined && rowData[calcCol] !== null && String(rowData[calcCol]).trim() !== '') {
            return String(rowData[calcCol]);
        }
        
        const colNorm = normalizarTextoClave(col);
        const calcNorm = calcCol ? normalizarTextoClave(calcCol) : '';
        const uDes = extraerUnidadDeNombreColumna(col) || extraerUnidadDeNombreColumna(calcCol);
        
        // 3. Coincidencia normalizada directa (ignora mayúsculas, tildes, signos y espacios)
        for (const key in rowData) {
            if (key.startsWith('_')) continue;
            const keyNorm = normalizarTextoClave(key);
            if (keyNorm === colNorm || (calcNorm && keyNorm === calcNorm)) {
                if (rowData[key] !== undefined && rowData[key] !== null && String(rowData[key]).trim() !== '') {
                    const uOri = extraerUnidadDeNombreColumna(key);
                    const rawVal = rowData[key];
                    if (uOri && uDes && uOri !== uDes && !isNaN(parseFloat(rawVal))) {
                        return String(convertirUnidadMedicion(parseFloat(rawVal), uOri, uDes));
                    }
                    return String(rawVal);
                }
            }
        }
        
        // 4. Coincidencia por grupo de sinónimos técnicos con conversión dimensional de unidades
        for (const grupo of DICCIONARIO_SINONIMOS) {
            if (grupo.includes(colNorm) || (calcNorm && grupo.includes(calcNorm))) {
                for (const key in rowData) {
                    if (key.startsWith('_')) continue;
                    const keyNorm = normalizarTextoClave(key);
                    if (grupo.includes(keyNorm)) {
                        if (rowData[key] !== undefined && rowData[key] !== null && String(rowData[key]).trim() !== '') {
                            const uOri = extraerUnidadDeNombreColumna(key);
                            const rawVal = rowData[key];
                            if (uOri && uDes && uOri !== uDes && !isNaN(parseFloat(rawVal))) {
                                return String(convertirUnidadMedicion(parseFloat(rawVal), uOri, uDes));
                            }
                            return String(rawVal);
                        }
                    }
                }
            }
        }
        
        // 5. Fallback para Nombre muestra / réplica
        if (colNorm === 'nombremuestra' || calcNorm === 'nombremuestra') {
            if (rowData['_muestra_origen']) return rowData['_muestra_origen'] + ' (Réplica CR)';
            if (rowData['Código laboratorio']) return String(rowData['Código laboratorio']);
        }
        
        return '';
    }

    function sincronizarSinonimosFila(fila, saveKey, val) {
        if (!fila || typeof fila !== 'object' || !saveKey) return;
        if (val === undefined || val === null || String(val).trim() === '' || String(val).trim() === '—') {
            return;
        }
        const keyNorm = normalizarTextoClave(saveKey);
        const uSrc = extraerUnidadDeNombreColumna(saveKey);
        const valNum = parseFloat(val);

        for (const grupo of DICCIONARIO_SINONIMOS) {
            if (grupo.includes(keyNorm)) {
                for (const k in fila) {
                    if (k.startsWith('_')) continue;
                    const targetNorm = normalizarTextoClave(k);
                    if (targetNorm !== keyNorm && grupo.includes(targetNorm)) {
                        const uDst = extraerUnidadDeNombreColumna(k);
                        if (uSrc && uDst && uSrc !== uDst && !isNaN(valNum)) {
                            fila[k] = String(convertirUnidadMedicion(valNum, uSrc, uDst));
                        } else if (!uSrc || !uDst || uSrc === uDst) {
                            fila[k] = val;
                        }
                    }
                }
            }
        }
    }

    let columnasActuales = [];

    function inicializarMatriz() {
        const schema = FORMATOS_SCHEMA[ARCHIVO_MARKDOWN] || { columns: [] };
        const columnasBase = (schema.columns && schema.columns.length > 0) 
            ? schema.columns 
            : ["Código laboratorio", "Nombre muestra", "Área (in²)", "Carga (lb)", "R. Compresión (lb/in²)", "R. Compresión (kg/cm²)"];
        // Técnicos (no supervisores) NUNCA ven columnas de Recepción (RT-FM-13 / O/S) en Operaciones
        columnasActuales = ES_SUPERVISOR
            ? columnasBase.slice()
            : columnasBase.filter(col => (CLASIFICACION_COLUMNAS[col] || 'tecnico') !== 'recepcion');
        const aliases = schema.column_aliases || {};
        columnaCodigoReplica = columnasActuales.find(col => ['Código laboratorio', 'Codigo laboratorio', 'codigo_lab', 'codigo_muestra'].includes(aliases[col] || col)) || null;
        columnaNombreReplica = columnasActuales.find(col => (aliases[col] || col) === 'Nombre muestra') || null;
        replicasHabilitadas = !!columnaCodigoReplica
            && !ARCHIVO_MARKDOWN.includes('granulometria')
            && !ARCHIVO_MARKDOWN.includes('granulomnetria')
            && !ARCHIVO_MARKDOWN.includes('bloques');
        const barraReplicas = document.getElementById('replicas-control-bar');
        if (barraReplicas) barraReplicas.style.display = replicasHabilitadas ? 'flex' : 'none';

        // 1. Renderizar encabezado
        const thead = document.getElementById('tabla-header');
        thead.innerHTML = '';

        // Fila 0: Indicador visual de origen (MUESTRA / LLENA RECEPCIÓN / RELLENA TÉCNICO)
        if (TIENE_CAMPOS_RECEPCION) {
            const trRoles = document.createElement('tr');
            trRoles.style.borderBottom = '1px solid #cbd5e1';

            const thNumRole = document.createElement('th');
            thNumRole.style.background = '#f1f5f9';
            thNumRole.innerText = '';
            trRoles.appendChild(thNumRole);

            if (replicasHabilitadas) {
                const thRepRole = document.createElement('th');
                thRepRole.style.background = '#f1f5f9';
                thRepRole.innerText = '';
                trRoles.appendChild(thRepRole);
            }

            columnasActuales.forEach(col => {
                const calcCol = aliases[col] || col;
                const thR = document.createElement('th');
                thR.style.textAlign = 'center';
                thR.style.padding = '5px 6px';
                thR.style.fontSize = '11px';
                thR.style.fontWeight = '800';
                thR.style.letterSpacing = '0.03em';
                const rol = CLASIFICACION_COLUMNAS[col] || 'tecnico';
                const esColumnaCalculada = (rol === 'calculado') || [
                    'Reven. (cm)', 'Área (in²)', 'Peso Volumétrico (kg/m³)',
                    'R. Compresión (lb/in²)', 'R. Compresión (kg/cm²)', 'R. compresión. (kg/cm²)',
                    'Resistencia a la flexión. (kg/cm²)', 'Edad (Días)', 'Compactación ((%) (P/P))'
                ].includes(calcCol);
                const esColumnaLabRotura = ES_ENSAYO_ROTURA_LAB && ['Carga (lb)', 'Carga (kg)', 'Fecha de Ensayo', 'Fecha de Ruptura'].includes(calcCol);
                if (rol === 'recepcion') {
                    thR.style.background = '#f1f5f9';
                    thR.style.color = '#334155';
                    thR.innerHTML = '<i class="fa-solid fa-inbox"></i> LLENA RECEPCIÓN';
                    thR.dataset.colRecepcion = '1';
                    if (!ES_SUPERVISOR || columnasRecepcionPlegadas) {
                        thR.classList.add('col-recepcion-oculta', 'd-none');
                    }
                } else if (rol === 'identidad' || rol === 'identificacion') {
                    thR.style.background = '#f1f5f9';
                    thR.style.color = '#334155';
                    thR.innerHTML = '<i class="fa-solid fa-tag"></i> MUESTRA';
                } else if (esColumnaCalculada) {
                    thR.style.background = '#f8fafc';
                    thR.style.color = '#0f172a';
                    thR.innerHTML = '<i class="fa-solid fa-calculator"></i> Calculado · Solo lectura';
                } else if (esColumnaLabRotura) {
                    thR.style.background = '#eff6ff';
                    thR.style.color = '#103487';
                    thR.innerHTML = '<i class="fa-solid fa-flask"></i> Viene del laboratorio';
                } else {
                    thR.style.background = '#dbeafe';
                    thR.style.color = '#1e40af';
                    thR.innerHTML = '<i class="fa-solid fa-microscope"></i> RELLENA TÉCNICO';
                }
                trRoles.appendChild(thR);
            });
            thead.appendChild(trRoles);
        }

        // Fila 1: Métodos de Ensayo (si existen definidos en el formato oficial)
        const hasMethods = schema.column_methods && Object.values(schema.column_methods).some(m => m && m.trim() !== '');
        if (hasMethods) {
            const trMethods = document.createElement('tr');
            trMethods.style.background = '#f8fafc';
            trMethods.style.borderBottom = '1px solid #cbd5e1';

            const tieneNombreEnIndice1 = !!(columnasActuales[1] && ['nombremuestra', 'nombredelamuestra'].includes(normalizarTextoClave(columnasActuales[1])));
            const inicioColsMetodo = tieneNombreEnIndice1 ? 2 : 1;
            const nombreOcultoEnInicio = tieneNombreEnIndice1 && (!ES_SUPERVISOR || columnasRecepcionPlegadas) && (CLASIFICACION_COLUMNAS[columnasActuales[1]] || 'tecnico') === 'recepcion';
            const thMethodTitle = document.createElement('th');
            thMethodTitle.id = 'th-method-title';
            thMethodTitle.dataset.tieneNombre = tieneNombreEnIndice1 ? '1' : '0';
            thMethodTitle.colSpan = (tieneNombreEnIndice1 ? (nombreOcultoEnInicio ? 2 : 3) : 2) + (replicasHabilitadas ? 1 : 0);
            thMethodTitle.style.textAlign = 'left';
            thMethodTitle.style.padding = '8px 12px';
            thMethodTitle.style.fontWeight = '700';
            thMethodTitle.style.fontSize = '12px';
            thMethodTitle.style.color = '#103487';
            thMethodTitle.innerHTML = '<i class="fa-solid fa-flask-vial"></i> Método de ensayo';
            trMethods.appendChild(thMethodTitle);

            columnasActuales.slice(inicioColsMetodo).forEach(col => {
                const thM = document.createElement('th');
                thM.style.textAlign = 'center';
                thM.style.padding = '6px 8px';
                thM.style.fontWeight = '600';
                thM.style.fontSize = '11px';
                if ((CLASIFICACION_COLUMNAS[col] || 'tecnico') === 'recepcion') {
                    thM.dataset.colRecepcion = '1';
                    if (!ES_SUPERVISOR || columnasRecepcionPlegadas) {
                        thM.classList.add('col-recepcion-oculta', 'd-none');
                    }
                }
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

        if (replicasHabilitadas) {
            const thReplica = document.createElement('th');
            thReplica.style.width = '110px';
            thReplica.style.textAlign = 'center';
            thReplica.innerText = 'Réplica CR';
            trH.appendChild(thReplica);
        }

        columnasActuales.forEach(col => {
            const th = document.createElement('th');
            th.innerText = col;
            if ((CLASIFICACION_COLUMNAS[col] || 'tecnico') === 'recepcion') {
                th.dataset.colRecepcion = '1';
                if (!ES_SUPERVISOR || columnasRecepcionPlegadas) {
                    th.classList.add('col-recepcion-oculta', 'd-none');
                }
            }
            trH.appendChild(th);
        });
        thead.appendChild(trH);

        // 2. Determinar filas iniciales
        let filas = [];
        const codigoCol = columnasActuales.find(col => (aliases[col] || col) === 'Código laboratorio') || 'Código laboratorio';
        const nombreCol = columnasActuales.find(col => (aliases[col] || col) === 'Nombre muestra') || 'Nombre muestra';

        const AREAS_PREFILL_PROHIBIDAS = ['14.137', '28.274', '28.2743', '12.566'];
        function limpiarAreaNominalSinCargaJS(row) {
            if (!row || typeof row !== 'object') return row;
            const cargaVal = String(row['Carga (lb)'] || row['Carga (kg)'] || row['Carga'] || '').trim();
            if (!cargaVal || cargaVal === '—' || parseFloat(cargaVal) <= 0) {
                ['Área (in²)', 'Area (in2)', 'Área (cm²)', 'area'].forEach(kA => {
                    if (row[kA] !== undefined && AREAS_PREFILL_PROHIBIDAS.includes(String(row[kA]).trim())) {
                        row[kA] = '';
                    }
                });
            }
            return row;
        }

        function aplicarDatosRecepcionEnFila(row, idx) {
            if (!Array.isArray(MUESTRAS_SETEADAS) || MUESTRAS_SETEADAS.length === 0) return row;
            const codRow = String(row[codigoCol] || row['Código laboratorio'] || row['_muestra_origen'] || '').trim().toUpperCase();
            let muestraRef = null;
            if (codRow) {
                muestraRef = MUESTRAS_SETEADAS.find(m => {
                    const mCod = String(m.codigo_lab || m.codigo_campo || '').trim().toUpperCase();
                    return mCod && (mCod === codRow || codRow === mCod + '-CR');
                }) || null;
            }
            if (!muestraRef && MUESTRAS_SETEADAS[idx]) {
                muestraRef = MUESTRAS_SETEADAS[idx];
            }
            if (!muestraRef || !muestraRef.datos_recepcion || typeof muestraRef.datos_recepcion !== 'object') {
                return row;
            }
            const bloqueadas = [];
            columnasActuales.forEach(col => {
                if ((CLASIFICACION_COLUMNAS[col] || 'tecnico') !== 'recepcion') return;
                const calcCol = aliases[col] || col;
                const valRec = buscarValorEnFila(muestraRef.datos_recepcion, col, calcCol);
                if (valRec !== '') {
                    bloqueadas.push(col);
                    const valActual = buscarValorEnFila(row, col, calcCol);
                    if (valActual === '') {
                        row[col] = valRec;
                    }
                }
            });
            if (bloqueadas.length > 0) {
                row._recepcion_bloqueadas = bloqueadas;
            }
            return limpiarAreaNominalSinCargaJS(row);
        }

        if (Array.isArray(DATOS_INICIALES) && DATOS_INICIALES.length > 0) {
            filas = DATOS_INICIALES.map((row, idx) => {
                if (row['Código laboratorio'] && row['Código laboratorio'].startsWith('OS-')) {
                    if (MUESTRAS_SETEADAS && MUESTRAS_SETEADAS[idx] && MUESTRAS_SETEADAS[idx].codigo_lab) {
                        row['Código laboratorio'] = MUESTRAS_SETEADAS[idx].codigo_lab;
                    } else {
                        row['Código laboratorio'] = PREFIJO_OFICIAL + '-' + String(idx + 1).padStart(4, '0') + '-26';
                    }
                }
                return aplicarDatosRecepcionEnFila(limpiarAreaNominalSinCargaJS(row), idx);
            });
        } else if (DEFAULT_ROWS_BY_FORMAT[ARCHIVO_MARKDOWN]) {
            filas = JSON.parse(JSON.stringify(DEFAULT_ROWS_BY_FORMAT[ARCHIVO_MARKDOWN]));
        } else if (Array.isArray(MUESTRAS_SETEADAS) && MUESTRAS_SETEADAS.length > 0) {
            filas = MUESTRAS_SETEADAS.map((m, i) => {
                const row = {};
                columnasActuales.forEach(col => { row[col] = ''; });
                if (row.hasOwnProperty(codigoCol)) row[codigoCol] = m.codigo_lab || '';
                if (row.hasOwnProperty(nombreCol)) row[nombreCol] = m.nombre_muestra || m.codigo_campo || '';
                return aplicarDatosRecepcionEnFila(row, i);
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
        actualizarBotonPlegarRecepcion();

        if (ARCHIVO_MARKDOWN.includes('granulometria') || ARCHIVO_MARKDOWN.includes('granulomnetria')) {
            recalculateGranulometria();
            recalculateCodigoInforme();
        } else {
            recalculateAll();
        }
    }

    function actualizarBotonPlegarRecepcion() {
        const btn = document.getElementById('btn-toggle-plegar-recepcion');
        if (!btn) return;
        btn.innerHTML = columnasRecepcionPlegadas
            ? '<i class="fa-solid fa-eye"></i> Mostrar columnas de recepción'
            : '<i class="fa-solid fa-eye-slash"></i> Plegar columnas de recepción';
    }

    function togglePlegarColumnasRecepcion() {
        if (!ES_SUPERVISOR) return;
        columnasRecepcionPlegadas = !columnasRecepcionPlegadas;
        document.querySelectorAll('[data-col-recepcion="1"]').forEach(el => {
            if (columnasRecepcionPlegadas) {
                el.classList.add('col-recepcion-oculta', 'd-none');
            } else {
                el.classList.remove('col-recepcion-oculta', 'd-none');
            }
        });
        const thMethodTitle = document.getElementById('th-method-title');
        if (thMethodTitle && thMethodTitle.dataset.tieneNombre === '1') {
            thMethodTitle.colSpan = (columnasRecepcionPlegadas ? 2 : 3) + (replicasHabilitadas ? 1 : 0);
        }
        actualizarBotonPlegarRecepcion();
    }

    function toggleEdicionRecepcionMatriz() {
        if (!ES_SUPERVISOR || ES_MODO_HISTORICO) return;
        permitirEdicionRecepcionMatriz = !permitirEdicionRecepcionMatriz;
        document.querySelectorAll('#tabla-body input[data-recepcion-prellena="1"]').forEach(inp => {
            inp.readOnly = !permitirEdicionRecepcionMatriz;
        });
        const btn = document.getElementById('btn-toggle-recepcion-lock');
        if (btn) {
            btn.innerHTML = permitirEdicionRecepcionMatriz
                ? '<i class="fa-solid fa-lock-open"></i> Bloquear casillas de Recepción'
                : '<i class="fa-solid fa-lock"></i> Desbloquear casillas de Recepción';
        }
    }

    function renderizarFilas(filas) {
        const tbody = document.getElementById('tabla-body');
        tbody.innerHTML = '';

        filas.forEach((rowData, rIdx) => {
            const tr = document.createElement('tr');
            tr.dataset.rowIndex = rIdx;
            const esReplica = rowData._es_replica === true || /-CR$/i.test(String(rowData[columnaCodigoReplica] || ''));
            tr.dataset.esReplica = esReplica ? '1' : '0';
            tr.dataset.muestraOrigen = esReplica ? String(rowData._muestra_origen || String(rowData[columnaCodigoReplica] || '').replace(/-CR$/i, '')) : '';
            if (esReplica) {
                tr.style.background = '#f0fdfa';
            }
            
            // Columna de numeración
            const tdNum = document.createElement('td');
            tdNum.style.textAlign = 'center';
            tdNum.style.fontWeight = '700';
            tdNum.style.color = '#64748b';
            tdNum.innerText = rIdx + 1;
            tr.appendChild(tdNum);

            if (replicasHabilitadas) {
                const tdReplica = document.createElement('td');
                tdReplica.style.textAlign = 'center';
                if (esReplica) {
                    const origenCode = tr.dataset.muestraOrigen || '';
                    tdReplica.innerHTML = '<span style="display:inline-flex;align-items:center;gap:5px;padding:3px 8px;border-radius:12px;background:#ccfbf1;color:#115e59;font-size:11px;font-weight:800;"><i class="fa-solid fa-vials"></i> RÉPLICA</span>';
                    if (!ES_MODO_HISTORICO) {
                        const btnQuitar = document.createElement('button');
                        btnQuitar.type = 'button';
                        btnQuitar.style.cssText = 'margin-left:6px;border:none;background:none;color:#ef4444;cursor:pointer;padding:2px 4px;font-size:12px;';
                        btnQuitar.title = 'Quitar esta réplica';
                        btnQuitar.innerHTML = '<i class="fa-solid fa-trash-can"></i>';
                        btnQuitar.addEventListener('click', () => quitarReplicaDe(origenCode));
                        tdReplica.appendChild(btnQuitar);
                    }
                } else {
                    const checkbox = document.createElement('input');
                    checkbox.type = 'checkbox';
                    checkbox.className = 'replica-selector';
                    checkbox.checked = filas.some(f => (f._es_replica === true || /-CR$/i.test(String(f[columnaCodigoReplica] || ''))) && String(f._muestra_origen || String(f[columnaCodigoReplica] || '').replace(/-CR$/i, '')).toUpperCase() === String(rowData[columnaCodigoReplica] || '').toUpperCase());
                    checkbox.disabled = ES_MODO_HISTORICO;
                    checkbox.title = 'Generar réplica de control para esta muestra';
                    checkbox.style.cssText = 'width:18px;height:18px;accent-color:#0f766e;cursor:pointer;';
                    checkbox.addEventListener('change', sincronizarReplicasSeleccionadas);
                    tdReplica.appendChild(checkbox);
                }
                tr.appendChild(tdReplica);
            }

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
                input.value = buscarValorEnFila(rowData, col, calcCol);
                input.dataset.col = calcCol;
                input.dataset.saveCol = col;
                input.dataset.row = rIdx;
                if (input.value) {
                    input.title = input.value;
                }
                input.addEventListener('change', () => { input.title = input.value; });

                const bloquearSoloLecturaEstricto = (inp, claseCss, etiquetaMotivo) => {
                    inp.readOnly = true;
                    inp.tabIndex = -1;
                    inp.classList.add(claseCss || 'matriz-input-calculated');
                    inp.dataset.calculadoReadonly = '1';
                    inp.style.cursor = 'not-allowed';
                    inp.addEventListener('keydown', e => { if (e.key !== 'Tab') e.preventDefault(); });
                    inp.addEventListener('paste', e => e.preventDefault());
                    inp.addEventListener('drop', e => e.preventDefault());
                    inp.addEventListener('mousedown', e => { e.preventDefault(); inp.blur(); });
                    if (etiquetaMotivo) {
                        inp.title = (inp.title ? inp.title + ' — ' : '') + etiquetaMotivo;
                    }
                };

                if (isGranulo) {
                    if (['Malla', 'Descripción', 'Unidad', 'Método de ensayo'].includes(calcCol)) {
                        bloquearSoloLecturaEstricto(input, 'matriz-input-locked', 'Columna fija de norma');
                    }
                    if (['% Retenido parcial', '% Acumulativo', 'Resultado'].includes(calcCol)) {
                        bloquearSoloLecturaEstricto(input, 'matriz-input-calculated', 'Calculado · Solo lectura');
                    }
                    if (calcCol === '% que pasa la malla') {
                        bloquearSoloLecturaEstricto(input, 'matriz-input-calculated', 'Calculado · Solo lectura');
                    }
                    if (calcCol === 'P. Retenido parcial (g)' || calcCol === 'P. Retenido parcial (gr)') {
                        if (['Límite Líquido', 'Límite Plástico', 'I.P'].includes(mallaName)) {
                            input.value = '—';
                            bloquearSoloLecturaEstricto(input, 'matriz-input-locked', 'Se calcula desde Captura Técnica superior');
                        } else if (mallaName === 'Suma') {
                            bloquearSoloLecturaEstricto(input, 'matriz-input-calculated', 'Calculado · Solo lectura');
                        } else {
                            input.addEventListener('input', recalculateGranulometria);
                        }
                    }
                } else if (ARCHIVO_MARKDOWN.includes('bloques')) {
                    if (['Descripción', 'Unidad', 'Método de ensayo'].includes(calcCol)) {
                        bloquearSoloLecturaEstricto(input, 'matriz-input-locked', 'Columna fija de norma');
                    } else if (calcCol === 'Resultado') {
                        bloquearSoloLecturaEstricto(input, 'matriz-input-calculated', 'Calculado automáticamente desde Captura Técnica');
                    }
                } else {
                    // Ensayos con captura técnica integrada arriba: bloquear TODAS las columnas calculadas, de identidad y auto-llenadas
                    const rolCol = CLASIFICACION_COLUMNAS[col] || 'tecnico';
                    const colNormL = normalizarTextoClave(calcCol);
                    const esColumnaPermitidaManualEnMatriz =
                        (ARCHIVO_MARKDOWN === 'proctor_estandar.md' && colNormL === 'tipodematerial')
                        || (ARCHIVO_MARKDOWN === 'ensayos_varios.md' && ['pvsskgm3', 'pvsckgm3', 'modulodefinura'].includes(colNormL))
                        || ['limitemin', 'limitemax', 'min', 'max'].includes(colNormL);

                    if (calcCol === 'Código laboratorio') {
                        bloquearSoloLecturaEstricto(input, 'matriz-input-locked', 'Código de muestra');
                    } else if (rolCol !== 'recepcion' && !esColumnaPermitidaManualEnMatriz) {
                        const esLabRot = ES_ENSAYO_ROTURA_LAB && ['Carga (lb)', 'Carga (kg)', 'Fecha de Ensayo', 'Fecha de Ruptura'].includes(calcCol);
                        if (esLabRot) {
                            input.dataset.vieneDeLaboratorio = '1';
                        }
                        bloquearSoloLecturaEstricto(
                            input,
                            esLabRot ? 'matriz-input-locked' : 'matriz-input-calculated',
                            esLabRot ? 'Viene de Captura Técnica / Laboratorio · Solo lectura' : 'Calculado · Solo lectura (llene los datos en Captura Técnica arriba)'
                        );
                    }
                    if (['Reven. (in)'].includes(calcCol)) {
                        input.addEventListener('input', recalculateRevenimiento);
                    }
                    if (['Fecha de Fabricación', 'Fecha de Ensayo', 'Fecha de Ruptura'].includes(calcCol)) {
                        input.addEventListener('change', recalculateEdades);
                        input.addEventListener('input', recalculateEdades);
                    }
                    if (['Código laboratorio', 'Codigo laboratorio', 'codigo_lab'].includes(calcCol) || col === 'Código laboratorio') {
                        input.addEventListener('input', recalculateCodigoInforme);
                    }
                }

                if ((CLASIFICACION_COLUMNAS[col] || 'tecnico') === 'recepcion') {
                    input.classList.add('matriz-input-recepcion');
                    input.dataset.rolRecepcion = '1';
                    td.dataset.colRecepcion = '1';
                    if (!ES_SUPERVISOR) {
                        td.classList.add('col-recepcion-oculta', 'd-none');
                        input.readOnly = true;
                    } else {
                        if (columnasRecepcionPlegadas) {
                            td.classList.add('col-recepcion-oculta', 'd-none');
                        }
                        const recBloqueadas = Array.isArray(rowData._recepcion_bloqueadas) ? rowData._recepcion_bloqueadas : [];
                        if (recBloqueadas.includes(col) && input.value.trim() !== '') {
                            input.dataset.recepcionPrellena = '1';
                            input.readOnly = !permitirEdicionRecepcionMatriz;
                            input.title = (input.title ? input.title + ' — ' : '') + 'Dato capturado en Recepción (O/S RT-FM-13)';
                        } else {
                            input.readOnly = !permitirEdicionRecepcionMatriz;
                        }
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

    function leerFilasMatrizActual() {
        const filas = [];
        document.querySelectorAll('#tabla-body tr').forEach(tr => {
            const fila = {};
            tr.querySelectorAll('input[data-col]').forEach(inp => {
                fila[inp.dataset.saveCol || inp.dataset.col] = inp.value;
            });
            if (tr.dataset.esReplica === '1') {
                fila._es_replica = true;
                fila._muestra_origen = tr.dataset.muestraOrigen || '';
            }
            filas.push(fila);
        });
        return filas;
    }

    function sincronizarReplicasSeleccionadas() {
        if (!replicasHabilitadas || ES_MODO_HISTORICO) return;

        const seleccionadas = new Set();
        document.querySelectorAll('#tabla-body tr').forEach(tr => {
            const selector = tr.querySelector('.replica-selector');
            const codigoInput = tr.querySelector(`input[data-save-col="${CSS.escape(columnaCodigoReplica)}"]`)
                || tr.querySelector('input[data-col="Código laboratorio"]');
            if (selector && selector.checked && codigoInput && codigoInput.value.trim()) {
                seleccionadas.add(codigoInput.value.trim().toUpperCase());
            }
        });

        const actuales = leerFilasMatrizActual();
        const originales = actuales.filter(f => !f._es_replica && !/-CR$/i.test(String(f[columnaCodigoReplica] || '')));
        const replicasExistentes = new Map();
        actuales.filter(f => f._es_replica || /-CR$/i.test(String(f[columnaCodigoReplica] || ''))).forEach(f => {
            const origen = String(f._muestra_origen || String(f[columnaCodigoReplica] || '').replace(/-CR$/i, '')).toUpperCase();
            if (origen && !replicasExistentes.has(origen)) replicasExistentes.set(origen, f);
        });

        const nuevasFilas = [];
        originales.forEach(original => {
            nuevasFilas.push(original);
            const codigo = String(original[columnaCodigoReplica] || '').trim();
            const clave = codigo.toUpperCase();
            if (!codigo || !seleccionadas.has(clave)) return;

            let replica = replicasExistentes.get(clave);
            if (!replica) {
                replica = {};
                columnasActuales.forEach(col => { replica[col] = ''; });
                if (columnaNombreReplica) {
                    const nombre = String(original[columnaNombreReplica] || '').trim();
                    replica[columnaNombreReplica] = nombre ? `${nombre} (Réplica CR)` : 'Réplica de control';
                }
            }
            replica[columnaCodigoReplica] = codigo + '-CR';
            replica._es_replica = true;
            replica._muestra_origen = codigo;
            nuevasFilas.push(replica);
        });

        renderizarFilas(nuevasFilas);
        if (typeof sincronizarHojaTecnicaConFilasMatriz === 'function') {
            sincronizarHojaTecnicaConFilasMatriz(nuevasFilas);
        }
        recalculateAll();
    }

    function quitarReplicaDe(origenCode) {
        if (!origenCode || ES_MODO_HISTORICO) return;
        document.querySelectorAll('#tabla-body tr').forEach(tr => {
            const selector = tr.querySelector('.replica-selector');
            const inp = tr.querySelector(`input[data-save-col="${(window.CSS && CSS.escape) ? CSS.escape(columnaCodigoReplica) : columnaCodigoReplica.replace(/"/g, '\\"')}"]`)
                || tr.querySelector('input[data-col="Código laboratorio"]');
            if (selector && inp && inp.value.trim().toUpperCase() === origenCode.toUpperCase()) {
                selector.checked = false;
            }
        });
        sincronizarReplicasSeleccionadas();
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
                const wInput = row['P. Retenido parcial (g)'] || row['P. Retenido parcial (gr)'];
                if (wInput) {
                    totalSuma += (parseFloat(wInput.value) || 0);
                }
            }
        });

        // Fila Suma
        const sumaRow = rowsArray.find(r => r['Malla'] && r['Malla'].value.trim() === 'Suma');
        const sumaPesoInput = sumaRow ? (sumaRow['P. Retenido parcial (g)'] || sumaRow['P. Retenido parcial (gr)']) : null;
        if (sumaRow && sumaPesoInput) {
            sumaPesoInput.value = totalSuma > 0 ? totalSuma.toFixed(4) : '';
            if (sumaRow['% Retenido parcial']) sumaRow['% Retenido parcial'].value = totalSuma > 0 ? '100.00' : '';
            if (sumaRow['% Acumulativo']) sumaRow['% Acumulativo'].value = totalSuma > 0 ? '100.00' : '';
            if (sumaRow['% que pasa la malla']) sumaRow['% que pasa la malla'].value = totalSuma > 0 ? '0.00' : '';
        }

        // Porcentajes de tamices
        let accumPercent = 0;
        rowsArray.forEach(row => {
            const malla = row['Malla'] ? row['Malla'].value.trim() : '';
            if (malla && !['Suma', 'Límite Líquido', 'Límite Plástico', 'I.P'].includes(malla)) {
                const wInput = row['P. Retenido parcial (g)'] || row['P. Retenido parcial (gr)'];
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

    function recalculateCompresion(ev) {
        const isUserEdit = !!(ev && (ev.type === 'input' || ev.type === 'change'));
        const rows = document.querySelectorAll('#tabla-body tr');
        rows.forEach(tr => {
            const cargaInput = tr.querySelector('input[data-col="Carga (lb)"]') || 
                               tr.querySelector('input[data-save-col="Carga (lb)"]') || 
                               tr.querySelector('input[data-col="Carga (kg)"]') || 
                               tr.querySelector('input[data-save-col="Carga (kg)"]');
            const areaInput = tr.querySelector('input[data-col="Área (in²)"]') || 
                              tr.querySelector('input[data-save-col="Área (in²)"]') || 
                              tr.querySelector('input[data-col="Área (cm²)"]') || 
                              tr.querySelector('input[data-save-col="Área (cm²)"]');
            const rPsiInput = tr.querySelector('input[data-col="R. Compresión (lb/in²)"]') || 
                              tr.querySelector('input[data-save-col="R. Compresión (lb/in²)"]') || 
                              tr.querySelector('input[data-col="Estimación R. compresión (lb/in²)"]') || 
                              tr.querySelector('input[data-save-col="Estimación R. compresión (lb/in²)"]');
            const rKgInput = tr.querySelector('input[data-col="R. Compresión (kg/cm²)"]') || 
                             tr.querySelector('input[data-save-col="R. Compresión (kg/cm²)"]') || 
                             tr.querySelector('input[data-col="R. Compresión. (kg/cm²)"]') || 
                             tr.querySelector('input[data-save-col="R. Compresión. (kg/cm²)"]') || 
                             tr.querySelector('input[data-col="R. compresión. (kg/cm²)"]') || 
                             tr.querySelector('input[data-save-col="R. compresión. (kg/cm²)"]') || 
                             tr.querySelector('input[data-col="R. a la compresión (kg/cm²)"]') || 
                             tr.querySelector('input[data-save-col="R. a la compresión (kg/cm²)"]') || 
                             tr.querySelector('input[data-col="Estimación R. compresión (kg/cm²)"]') || 
                             tr.querySelector('input[data-save-col="Estimación R. compresión (kg/cm²)"]');

            if (cargaInput && areaInput) {
                const carga = parseFloat(cargaInput.value) || 0;
                const area = parseFloat(areaInput.value) || 0;

                if (carga > 0 && area > 0) {
                    const isLb = (cargaInput.getAttribute('data-col') || '').includes('(lb)');
                    const isIn2 = (areaInput.getAttribute('data-col') || '').includes('(in²)');
                    let psi = 0;
                    let kgcm2 = 0;

                    if (isLb && isIn2) {
                        const psiBruto = carga / area;
                        psi = LAB_REDONDEAR_PSI_10 ? (Math.round(psiBruto / 10) * 10) : psiBruto;
                        kgcm2 = Math.round(psi / LAB_DIVISOR_KGCM2);
                    } else if (!isLb && !isIn2) {
                        // Carga en kg y Área en cm² -> ya está en kg/cm²
                        kgcm2 = carga / area;
                        psi = kgcm2 * LAB_DIVISOR_KGCM2;
                    } else if (isLb && !isIn2) {
                        // Carga en lb y Área en cm²
                        kgcm2 = (carga * 0.45359237) / area;
                        psi = kgcm2 * LAB_DIVISOR_KGCM2;
                    } else {
                        // Carga en kg y Área en in²
                        psi = (carga * 2.2046226) / area;
                        kgcm2 = psi / LAB_DIVISOR_KGCM2;
                    }

                    if (rPsiInput && (!rPsiInput.value || isUserEdit)) rPsiInput.value = (isLb && isIn2) ? String(Math.round(psi)) : psi.toFixed(1);
                    if (rKgInput && (!rKgInput.value || isUserEdit)) rKgInput.value = (isLb && isIn2) ? String(Math.round(kgcm2)) : kgcm2.toFixed(1);
                } else if (isUserEdit) {
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
                    revCmInput.value = String(Math.round(valIn * 2.54 * 2) / 2);
                } else {
                    revCmInput.value = '';
                }
            }
        });
    }

    function recalculateCompactacion(ev) {
        const isUserEdit = !!(ev && (ev.type === 'input' || ev.type === 'change'));
        const rows = document.querySelectorAll('#tabla-body tr');
        rows.forEach(tr => {
            const pvsMaxInput = tr.querySelector('input[data-col="P.V.S Max (kg/m³)"]') || 
                                tr.querySelector('input[data-save-col="P.V.S Max (kg/m³)"]') || 
                                tr.querySelector('input[data-col="**P.V.S Max (kg/m³)"]') ||
                                tr.querySelector('input[data-save-col="**P.V.S Max (kg/m³)"]');
            const pvsSitioInput = tr.querySelector('input[data-col="P.V.S.Sitio (kg/cm²)"]') || 
                                  tr.querySelector('input[data-save-col="P.V.S.Sitio (kg/cm²)"]') || 
                                  tr.querySelector('input[data-col="P.V.S.Sitio (kg/m³)"]') ||
                                  tr.querySelector('input[data-save-col="P.V.S.Sitio (kg/m³)"]');
            const compInput = tr.querySelector('input[data-col="Compactación ((%) (P/P))"]') ||
                              tr.querySelector('input[data-save-col="Compactación ((%) (P/P))"]');

            if (pvsMaxInput && pvsSitioInput && compInput) {
                const max = parseFloat(pvsMaxInput.value) || 0;
                const sitio = parseFloat(pvsSitioInput.value) || 0;
                if (max > 0 && sitio > 0) {
                    compInput.value = ((sitio / max) * 100).toFixed(1);
                } else if (isUserEdit) {
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

        rows.forEach((tr, rIdx) => {
            const originalRow = (Array.isArray(DATOS_INICIALES) && DATOS_INICIALES[rIdx]) ? { ...DATOS_INICIALES[rIdx] } : {};
            const rowObj = Object.assign({}, originalRow);
            let hasAnyVal = false;
            tr.querySelectorAll('input[data-col]').forEach(inp => {
                const colName = inp.dataset.saveCol || inp.dataset.col;
                const val = inp.value;
                rowObj[colName] = val;
                sincronizarSinonimosFila(rowObj, colName, val);
                if (val && val.trim() !== '' && val !== '—') {
                    hasAnyVal = true;
                }
            });
            if (hasAnyVal) {
                if (tr.dataset.esReplica === '1') {
                    rowObj._es_replica = true;
                    rowObj._muestra_origen = tr.dataset.muestraOrigen || '';
                }
                resultados.push(rowObj);
            }
        });

        const metaObj = {};
        if (ES_SUPERVISOR) {
            metaObj.cliente_nombre = document.getElementById('meta_cliente_nombre') ? document.getElementById('meta_cliente_nombre').value : '';
            metaObj.cliente_direccion = document.getElementById('meta_cliente_direccion') ? document.getElementById('meta_cliente_direccion').value : '';
            metaObj.fecha_ingreso = document.getElementById('meta_fecha_ingreso') ? document.getElementById('meta_fecha_ingreso').value : '';
            metaObj.tipo_muestra = document.getElementById('meta_tipo_muestra') ? document.getElementById('meta_tipo_muestra').value : '';
            metaObj.procedimiento_muestreo = document.getElementById('meta_procedimiento_muestreo') ? document.getElementById('meta_procedimiento_muestreo').value : '';
            metaObj.ensayo_realizado = document.getElementById('meta_ensayo_realizado') ? document.getElementById('meta_ensayo_realizado').value : '';
            metaObj.proyecto = document.getElementById('meta_proyecto') ? document.getElementById('meta_proyecto').value : '';
            metaObj.fecha_muestreo = document.getElementById('meta_fecha_muestreo') ? document.getElementById('meta_fecha_muestreo').value : '';
            metaObj.fecha_ejecucion = document.getElementById('meta_fecha_ejecucion') ? document.getElementById('meta_fecha_ejecucion').value : '';
            metaObj.fecha_emision = document.getElementById('meta_fecha_emision') ? document.getElementById('meta_fecha_emision').value : '';
            metaObj.muestra_tomada_por = document.getElementById('meta_muestra_tomada_por') ? document.getElementById('meta_muestra_tomada_por').value : '';
            metaObj.ubicacion = document.getElementById('meta_ubicacion') ? document.getElementById('meta_ubicacion').value : '';
            metaObj.metodo_muestreo = document.getElementById('meta_metodo_muestreo') ? document.getElementById('meta_metodo_muestreo').value : '';
            metaObj.codigo_formato = <?= json_encode($metadatos['codigo_formato'], JSON_UNESCAPED_UNICODE) ?>;
            metaObj.logo_acreditacion = document.getElementById('input_hidden_logo_acreditacion') ? document.getElementById('input_hidden_logo_acreditacion').value : '';
            metaObj.nota_aditivos = document.getElementById('meta_nota_aditivos') ? document.getElementById('meta_nota_aditivos').value : '';
            metaObj.observaciones_pendientes = document.getElementById('meta_observaciones_pendientes') ? document.getElementById('meta_observaciones_pendientes').value : '';
            metaObj.columnas_asterisco = document.getElementById('meta_columnas_asterisco') ? document.getElementById('meta_columnas_asterisco').value : '';
            metaObj.equipo_marca = document.getElementById('meta_equipo_marca') ? document.getElementById('meta_equipo_marca').value : '';
            metaObj.equipo_modelo = document.getElementById('meta_equipo_modelo') ? document.getElementById('meta_equipo_modelo').value : '';
            metaObj.equipo_serie = document.getElementById('meta_equipo_serie') ? document.getElementById('meta_equipo_serie').value : '';
            metaObj.equipo_codigo = document.getElementById('meta_equipo_codigo') ? document.getElementById('meta_equipo_codigo').value : '';
            if (document.getElementById('meta_disclaimer_personalizado')) {
                metaObj.disclaimer_personalizado = document.getElementById('meta_disclaimer_personalizado').value;
            }
            if (document.getElementById('meta_notas_informe')) {
                metaObj.notas_informe = document.getElementById('meta_notas_informe').value;
                metaObj.notas_informe_personalizadas = true;
            }
        }

        const payload = {
            filas: resultados,
            captura_tecnica: leerFilasCapturaTecnicaActual(),
            metadatos: metaObj
        };

        const inputJson = document.getElementById('input_resultados_json');
        if (inputJson) {
            inputJson.value = JSON.stringify(payload);
        }
    }

    let enviandoMatriz = false;
    function guardarMatrizFullSubmit() {
        if (enviandoMatriz) return;
        enviandoMatriz = true;
        prepararEnvioMatriz();
        const form = document.getElementById('form-matriz-completa');
        if (form) {
            form.submit();
        } else {
            enviandoMatriz = false;
        }
    }

    function enviarMatrizARevisionSubmit() {
        if (enviandoMatriz) return;
        enviandoMatriz = true;
        prepararEnvioMatriz();
        const form = document.getElementById('form-matriz-completa');
        if (form) {
            let inpEnv = form.querySelector('input[name="enviar_a_revision"]');
            if (!inpEnv) {
                inpEnv = document.createElement('input');
                inpEnv.type = 'hidden';
                inpEnv.name = 'enviar_a_revision';
                form.appendChild(inpEnv);
            }
            inpEnv.value = '1';
            form.submit();
        } else {
            enviandoMatriz = false;
        }
    }

    function manejarSubidaLogoAcreditacion(input) {
        if (!input.files || input.files.length === 0) return;
        const file = input.files[0];

        if (file.size > 5 * 1024 * 1024) {
            alert('La imagen no puede exceder 5 MB.');
            input.value = '';
            return;
        }

        const reader = new FileReader();
        reader.onload = function(e) {
            const preview = document.getElementById('header-logo-acreditacion-preview');
            const wrapper = document.getElementById('wrapper-header-logo-acreditacion');
            if (preview && wrapper) {
                preview.src = e.target.result;
                wrapper.style.display = 'flex';
            }
        };
        reader.readAsDataURL(file);

        const fd = new FormData();
        fd.append('archivo_logo', file);
        fd.append('id_detalle', '<?= (int)$detalle['id'] ?>');
        fd.append('csrf_token', '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>');

        const btnSubir = document.getElementById('lbl-btn-subir-acreditacion');
        if (btnSubir) btnSubir.textContent = 'Subiendo...';

        fetch('/Cycsa/publico/operaciones/subir-logo-acreditacion', {
            method: 'POST',
            body: fd
        })
        .then(res => res.json())
        .then(data => {
            if (data.exito) {
                document.getElementById('input_hidden_logo_acreditacion').value = data.url;
                const preview = document.getElementById('header-logo-acreditacion-preview');
                const wrapper = document.getElementById('wrapper-header-logo-acreditacion');
                if (preview && wrapper) {
                    preview.src = data.url;
                    wrapper.style.display = 'flex';
                }
                const btnQuitar = document.getElementById('btn_quitar_acreditacion');
                if (btnQuitar) btnQuitar.style.display = 'inline-block';
                const badge = document.getElementById('badge-acreditacion-estado');
                if (badge) badge.style.display = 'inline-block';
                if (btnSubir) btnSubir.textContent = 'Cambiar imagen';
            } else {
                alert('Error al subir logotipo: ' + (data.error || 'Error inesperado'));
                if (btnSubir) btnSubir.textContent = 'Subir imagen de acreditación';
            }
        })
        .catch(err => {
            console.error(err);
            alert('Error en la comunicación con el servidor al subir la imagen.');
            if (btnSubir) btnSubir.textContent = 'Subir imagen de acreditación';
        });
    }

    function quitarLogoAcreditacion() {
        if (!confirm('¿Desea retirar el logotipo de acreditación de este informe?')) return;

        document.getElementById('input_hidden_logo_acreditacion').value = '';
        const input = document.getElementById('input_file_acreditacion');
        if (input) input.value = '';

        const wrapper = document.getElementById('wrapper-header-logo-acreditacion');
        const preview = document.getElementById('header-logo-acreditacion-preview');
        if (wrapper && preview) {
            wrapper.style.display = 'none';
            preview.src = '';
        }

        const btnQuitar = document.getElementById('btn_quitar_acreditacion');
        if (btnQuitar) btnQuitar.style.display = 'none';
        const badge = document.getElementById('badge-acreditacion-estado');
        if (badge) badge.style.display = 'none';
        const btnSubir = document.getElementById('lbl-btn-subir-acreditacion');
        if (btnSubir) btnSubir.textContent = 'Subir imagen de acreditación';

        const fd = new FormData();
        fd.append('id_detalle', '<?= (int)$detalle['id'] ?>');
        fd.append('csrf_token', '<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>');

        fetch('/Cycsa/publico/operaciones/eliminar-logo-acreditacion', {
            method: 'POST',
            body: fd
        });
    }

    // =========================================================================
    // MOTOR EN VIVO DE CAPTURA TÉCNICA DEL TÉCNICO (100% DE LOS 28 FORMATOS)
    // =========================================================================
    function togglePanelHojaTecnica() {
        const body = document.getElementById('ht-lab-body');
        const btn = document.getElementById('btn-toggle-ht-body');
        if (!body) return;
        const oculto = body.style.display === 'none';
        body.style.display = oculto ? 'block' : 'none';
        if (btn) btn.textContent = oculto ? 'Ocultar captura técnica' : 'Mostrar captura técnica';
    }

    function htParseNum(v) {
        if (v === null || v === undefined) return NaN;
        const s = String(v).replace(',', '.').trim();
        if (s === '') return NaN;
        return parseFloat(s);
    }

    function htFechaIso(raw) {
        const s = String(raw || '').trim();
        if (!s) return '';
        const m = s.match(/^(\d{4}-\d{2}-\d{2})/);
        return m ? m[1] : '';
    }

    function htFactorLd(ld) {
        if (ld >= 1.75) return 1.0;
        if (ld <= 1.00) return 0.87;
        const pts = [[1.00, 0.87], [1.25, 0.93], [1.50, 0.96], [1.75, 0.98]];
        for (let i = 0; i < pts.length - 1; i++) {
            const [x0, y0] = pts[i];
            const [x1, y1] = pts[i + 1];
            if (ld >= x0 && ld <= x1) {
                return y0 + ((ld - x0) / (x1 - x0)) * (y1 - y0);
            }
        }
        return 1.0;
    }

    function htSetCeldaMatriz(tr, colNames, valor) {
        if (!tr || valor === null || valor === undefined) return;
        for (const cname of colNames) {
            const inp = tr.querySelector(`input[data-col="${cname}"]`) || tr.querySelector(`input[data-save-col="${cname}"]`);
            if (inp) {
                inp.value = String(valor);
                inp.title = String(valor);
                return;
            }
        }
    }

    function htBuscarTrMatriz(codLab, idxFallback) {
        const trs = Array.from(document.querySelectorAll('#tabla-body tr'));
        const codUp = String(codLab || '').trim().toUpperCase();
        if (codUp) {
            for (const tr of trs) {
                const inpCod = tr.querySelector('input[data-col="Código laboratorio"]') || tr.querySelector('input[data-save-col="Código laboratorio"]');
                if (inpCod && inpCod.value.trim().toUpperCase() === codUp) {
                    return tr;
                }
            }
        }
        return trs[idxFallback] || null;
    }

    function htDefaultDatetimeNow() {
        const d = new Date();
        const pad = n => String(n).padStart(2, '0');
        return `${d.getFullYear()}-${pad(d.getMonth() + 1)}-${pad(d.getDate())}T${pad(d.getHours())}:${pad(d.getMinutes())}`;
    }

    function construirFilasInicialesHojaTecnica() {
        const cfg = HOJA_TECNICA_CONFIG || {};
        const campos = cfg.campos_entrada || [];
        if (Array.isArray(CAPTURA_TECNICA_INICIAL) && CAPTURA_TECNICA_INICIAL.length > 0) {
            return JSON.parse(JSON.stringify(CAPTURA_TECNICA_INICIAL));
        }
        const filas = [];
        const modo = cfg.modo_filas || 'por_muestra';
        if (modo === 'por_muestra') {
            const trs = Array.from(document.querySelectorAll('#tabla-body tr'));
            const total = Math.max(trs.length, Array.isArray(MUESTRAS_SETEADAS) ? MUESTRAS_SETEADAS.length : 0, 1);
            for (let i = 0; i < total; i++) {
                let cod = '';
                if (trs[i]) {
                    const inpC = trs[i].querySelector('input[data-col="Código laboratorio"]') || trs[i].querySelector('input[data-save-col="Código laboratorio"]');
                    if (inpC) cod = inpC.value.trim();
                }
                if (!cod && MUESTRAS_SETEADAS && MUESTRAS_SETEADAS[i]) {
                    cod = MUESTRAS_SETEADAS[i].codigo_lab || MUESTRAS_SETEADAS[i].codigo_campo || '';
                }
                const row = { codigo_lab: cod || ('M-' + (i + 1)) };
                campos.forEach(c => {
                    row[c.clave] = c.default !== undefined ? c.default : '';
                });
                filas.push(row);
            }
        } else {
            const n = Math.max(1, parseInt(cfg.filas_default || 3, 10));
            const etq = cfg.etiqueta_fila || 'Fila';
            for (let i = 0; i < n; i++) {
                const row = { codigo_lab: `${etq} ${i + 1}` };
                campos.forEach(c => { row[c.clave] = c.default !== undefined ? c.default : ''; });
                filas.push(row);
            }
        }
        return filas;
    }

    function sincronizarHojaTecnicaConFilasMatriz(filasMatriz) {
        const cfg = HOJA_TECNICA_CONFIG || {};
        if ((cfg.modo_filas || 'por_muestra') !== 'por_muestra') return;
        const tbodyHt = document.getElementById('ht-tabla-body');
        if (!tbodyHt) return;

        // Preservar los valores actuales ya digitados en la tabla técnica
        const actualesHt = (tbodyHt.querySelectorAll('tr').length > 0)
            ? leerFilasCapturaTecnicaActual()
            : (Array.isArray(capturaTecnicaFilas) ? capturaTecnicaFilas.slice() : []);

        const mapaPorCod = new Map();
        actualesHt.forEach(r => {
            const cKey = String(r && r.codigo_lab ? r.codigo_lab : '').trim().toUpperCase();
            if (cKey && !mapaPorCod.has(cKey)) {
                mapaPorCod.set(cKey, r);
            }
        });

        const campos = cfg.campos_entrada || [];
        const origenLista = Array.isArray(filasMatriz) && filasMatriz.length > 0
            ? filasMatriz
            : leerFilasMatrizActual();
        if (!Array.isArray(origenLista) || origenLista.length === 0) return;

        const nuevasHt = origenLista.map((fMat, idx) => {
            const cod = String(
                (columnaCodigoReplica && fMat[columnaCodigoReplica] !== undefined ? fMat[columnaCodigoReplica] : '')
                || fMat['Código laboratorio']
                || fMat['Codigo laboratorio']
                || fMat['codigo_lab']
                || ('M-' + (idx + 1))
            ).trim();
            const codUp = cod.toUpperCase();
            const esRep = fMat._es_replica === true || /-CR$/i.test(cod);
            const origenRep = esRep ? String(fMat._muestra_origen || cod.replace(/-CR$/i, '')) : '';

            if (codUp && mapaPorCod.has(codUp)) {
                const existente = { ...mapaPorCod.get(codUp), codigo_lab: cod };
                if (esRep) {
                    existente._es_replica = true;
                    existente._muestra_origen = origenRep;
                } else {
                    delete existente._es_replica;
                    delete existente._muestra_origen;
                }
                return existente;
            }

            const nueva = { codigo_lab: cod };
            if (esRep) {
                nueva._es_replica = true;
                nueva._muestra_origen = origenRep;
            }
            campos.forEach(c => {
                nueva[c.clave] = c.default !== undefined ? c.default : '';
            });
            return nueva;
        });

        capturaTecnicaFilas = nuevasHt;
        renderizarTablaHojaTecnica();
        recalcularHojaTecnicaEnVivo(true);
    }

    function renderizarTablaHojaTecnica() {
        const cfg = HOJA_TECNICA_CONFIG || {};
        const thead = document.getElementById('ht-tabla-header');
        const tbody = document.getElementById('ht-tabla-body');
        if (!thead || !tbody || !cfg.campos_entrada) return;

        const tieneFechaEnsayoRecepcion = Object.entries(CLASIFICACION_COLUMNAS || {}).some(
            ([colName, rol]) => rol === 'recepcion' && ['fechadeensayo', 'fechaensayo', 'fechaderuptura', 'fecharuptura', 'fechadefinalizacion', 'fechafinalizacion', 'fechademuestreo', 'fechamuestreo'].includes(normalizarTextoClave(colName))
        );
        const camposIn = (cfg.campos_entrada || []).filter(c => {
            if (!ES_SUPERVISOR && tieneFechaEnsayoRecepcion && c.clave === 'fecha_ensayo') return false;
            return true;
        });
        const camposCalc = cfg.campos_calculados || [];

        thead.innerHTML = '';
        const trH = document.createElement('tr');
        const thCod = document.createElement('th');
        thCod.textContent = (cfg.modo_filas === 'por_muestra') ? 'MUESTRA / PROBETA' : (cfg.etiqueta_fila || 'ÍTEM').toUpperCase();
        thCod.style.paddingLeft = '12px';
        trH.appendChild(thCod);

        camposIn.forEach(c => {
            const th = document.createElement('th');
            th.textContent = c.etiqueta;
            trH.appendChild(th);
        });
        camposCalc.forEach(c => {
            const th = document.createElement('th');
            th.className = 'ht-th-calc';
            th.textContent = c.etiqueta;
            trH.appendChild(th);
        });
        thead.appendChild(trH);

        tbody.innerHTML = '';
        capturaTecnicaFilas.forEach((rowData, rIdx) => {
            const tr = document.createElement('tr');
            tr.dataset.htRow = rIdx;
            const codTxt = String(rowData.codigo_lab || (`#${rIdx + 1}`));
            const esReplicaHt = rowData._es_replica === true || /-CR$/i.test(codTxt);
            if (esReplicaHt) {
                tr.dataset.esReplica = '1';
                tr.style.background = '#f0fdfa';
            }

            const tdCod = document.createElement('td');
            tdCod.className = 'ht-code-cell';
            tdCod.textContent = codTxt;
            if (esReplicaHt) {
                const badgeRep = document.createElement('span');
                badgeRep.style.cssText = 'display:inline-block;margin-left:6px;padding:2px 7px;border-radius:999px;background:#ccfbf1;color:#115e59;font-size:10.5px;font-weight:800;vertical-align:middle;';
                badgeRep.textContent = 'RÉPLICA';
                tdCod.appendChild(badgeRep);
            }
            tr.appendChild(tdCod);

            camposIn.forEach(c => {
                const td = document.createElement('td');
                let el;
                if (c.tipo === 'select') {
                    el = document.createElement('select');
                    el.className = 'ht-select';
                    (c.opciones || []).forEach(optTxt => {
                        const opt = document.createElement('option');
                        opt.value = optTxt;
                        opt.textContent = optTxt;
                        if (String(rowData[c.clave] || c.default || '') === String(optTxt)) {
                            opt.selected = true;
                        }
                        el.appendChild(opt);
                    });
                } else {
                    el = document.createElement('input');
                    el.className = 'ht-input';
                    if (c.tipo === 'datetime') {
                        el.type = 'datetime-local';
                        el.value = rowData[c.clave] || '';
                    } else if (c.tipo === 'date') {
                        el.type = 'date';
                        el.value = rowData[c.clave] || '';
                    } else {
                        el.type = 'text';
                        if (c.tipo === 'decimal') el.setAttribute('inputmode', 'decimal');
                        if (c.tipo === 'entero') el.setAttribute('inputmode', 'numeric');
                        el.placeholder = c.placeholder || '';
                        el.value = rowData[c.clave] !== undefined && rowData[c.clave] !== null ? String(rowData[c.clave]) : '';
                    }
                }
                if (c.ancho) el.style.width = c.ancho + 'px';
                el.dataset.htKey = c.clave;
                el.dataset.htRow = rIdx;
                if (ES_MODO_HISTORICO) {
                    el.disabled = true;
                    el.readOnly = true;
                }
                el.addEventListener('input', () => {
                    capturaTecnicaFilas[rIdx][c.clave] = el.value;
                    recalcularHojaTecnicaEnVivo(true);
                });
                el.addEventListener('change', () => {
                    capturaTecnicaFilas[rIdx][c.clave] = el.value;
                    recalcularHojaTecnicaEnVivo(true);
                });
                td.appendChild(el);
                tr.appendChild(td);
            });

            camposCalc.forEach(c => {
                const td = document.createElement('td');
                const inp = document.createElement('input');
                inp.type = 'text';
                inp.readOnly = true;
                inp.disabled = true;
                inp.tabIndex = -1;
                inp.className = 'ht-input-calc' + (c.destacado ? ' is-highlight' : '');
                if (c.ancho) inp.style.width = c.ancho + 'px';
                inp.placeholder = c.placeholder || '--';
                inp.dataset.htCalcKey = c.clave;
                inp.dataset.htRow = rIdx;
                inp.value = rowData[c.clave] !== undefined && rowData[c.clave] !== null ? String(rowData[c.clave]) : '';
                td.appendChild(inp);
                tr.appendChild(td);
            });

            tbody.appendChild(tr);
        });
    }

    function agregarFilaHojaTecnica() {
        const cfg = HOJA_TECNICA_CONFIG || {};
        const idx = capturaTecnicaFilas.length + 1;
        const etq = cfg.etiqueta_fila || 'Fila';
        const row = { codigo_lab: `${etq} ${idx}` };
        (cfg.campos_entrada || []).forEach(c => {
            row[c.clave] = c.default !== undefined ? c.default : '';
        });
        capturaTecnicaFilas.push(row);
        renderizarTablaHojaTecnica();
        recalcularHojaTecnicaEnVivo(false);
    }

    function leerFilasCapturaTecnicaActual() {
        const tbody = document.getElementById('ht-tabla-body');
        if (!tbody) return [];
        const out = [];
        tbody.querySelectorAll('tr').forEach((tr, rIdx) => {
            const base = capturaTecnicaFilas[rIdx] ? { ...capturaTecnicaFilas[rIdx] } : {};
            tr.querySelectorAll('[data-ht-key]').forEach(el => {
                base[el.dataset.htKey] = el.value;
            });
            tr.querySelectorAll('[data-ht-calc-key]').forEach(el => {
                base[el.dataset.htCalcKey] = el.value;
            });
            out.push(base);
        });
        return out;
    }

    function recalcularHojaTecnicaEnVivo(sincronizarConMatriz) {
        const cfg = HOJA_TECNICA_CONFIG || {};
        const motor = cfg.motor_calculo || '';
        const tbody = document.getElementById('ht-tabla-body');
        const resumenBar = document.getElementById('ht-resumen-bar');
        if (!tbody || !motor) return;

        const setCalc = (tr, key, val) => {
            const inp = tr.querySelector(`[data-ht-calc-key="${key}"]`);
            if (inp) inp.value = (val !== null && val !== undefined && val !== '') ? String(val) : '';
        };

        const proctorPuntos = [];
        const bloqueAcum = [];
        let sanidadTotalRetOrig = 0;
        if (motor === 'sanidad') {
            capturaTecnicaFilas.forEach(r => {
                const v = htParseNum(r.retenido_original_g);
                if (!isNaN(v) && v > 0) sanidadTotalRetOrig += v;
            });
        }

        tbody.querySelectorAll('tr').forEach((tr, rIdx) => {
            const row = capturaTecnicaFilas[rIdx] || {};
            const codLab = row.codigo_lab || '';
            const trMat = htBuscarTrMatriz(codLab, rIdx);

            if (motor === 'cilindro_psi') {
                const d1 = htParseNum(row.diametro_1), d2 = htParseNum(row.diametro_2);
                const l1 = htParseNum(row.largo_1), l2 = htParseNum(row.largo_2);
                const masa = htParseNum(row.masa_kg), carga = htParseNum(row.carga_lb);
                const fEns = htFechaIso(row.fecha_ensayo);

                let areaIn2 = NaN, pv = NaN, psi = NaN, kgcm2 = NaN;
                if (!isNaN(d1) && d1 > 0 && !isNaN(d2) && d2 > 0) {
                    const dProm = (d1 + d2) / 2.0;
                    areaIn2 = (Math.PI * Math.pow(dProm / 2.0, 2)) / 6.4516;
                    setCalc(tr, 'area_in2', areaIn2.toFixed(2));
                } else {
                    setCalc(tr, 'area_in2', '');
                }
                if (!isNaN(areaIn2) && !isNaN(l1) && l1 > 0 && !isNaN(l2) && l2 > 0 && !isNaN(masa) && masa > 0) {
                    const dProm = (d1 + d2) / 2.0;
                    const lProm = (l1 + l2) / 2.0;
                    const volM3 = (Math.PI * Math.pow(dProm / 2.0, 2) * lProm) / 1000000.0;
                    pv = Math.round((masa / volM3) / 10.0) * 10;
                    setCalc(tr, 'peso_volumetrico', pv);
                } else {
                    setCalc(tr, 'peso_volumetrico', '');
                }
                if (!isNaN(areaIn2) && areaIn2 > 0 && !isNaN(carga) && carga > 0) {
                    let fLd = 1.0;
                    if (!isNaN(l1) && l1 > 0 && !isNaN(l2) && l2 > 0) {
                        const ld = ((l1 + l2) / 2.0) / ((d1 + d2) / 2.0);
                        if (ld <= 1.75) fLd = htFactorLd(ld);
                    }
                    const psiBruto = (carga / areaIn2) * fLd;
                    psi = LAB_REDONDEAR_PSI_10 ? (Math.round(psiBruto / 10.0) * 10) : Math.round(psiBruto);
                    kgcm2 = Math.round(psi / LAB_DIVISOR_KGCM2);
                    setCalc(tr, 'esfuerzo_psi', psi);
                    setCalc(tr, 'esfuerzo_kgcm2', kgcm2);
                } else {
                    setCalc(tr, 'esfuerzo_psi', '');
                    setCalc(tr, 'esfuerzo_kgcm2', '');
                }
                if (sincronizarConMatriz && trMat && (!isNaN(areaIn2) || !isNaN(carga) || !isNaN(pv) || fEns)) {
                    if (!isNaN(areaIn2)) htSetCeldaMatriz(trMat, ['Área (in²)'], areaIn2.toFixed(2));
                    if (!isNaN(carga) && carga > 0) htSetCeldaMatriz(trMat, ['Carga (lb)'], Math.round(carga));
                    if (!isNaN(pv)) htSetCeldaMatriz(trMat, ['Peso Volumétrico (kg/m³)'], pv);
                    if (!isNaN(psi)) htSetCeldaMatriz(trMat, ['R. Compresión (lb/in²)'], psi);
                    if (!isNaN(kgcm2)) htSetCeldaMatriz(trMat, ['R. Compresión (kg/cm²)', 'R. Compresión. (kg/cm²)'], kgcm2);
                    if (fEns) htSetCeldaMatriz(trMat, ['Fecha de Ensayo', 'Fecha de Ruptura'], fEns);
                }
            } else if (motor === 'cilindro_metrico') {
                const d1 = htParseNum(row.diametro_1), d2 = htParseNum(row.diametro_2);
                const l1 = htParseNum(row.largo_1), l2 = htParseNum(row.largo_2);
                const masa = htParseNum(row.masa_kg);
                let cargaKg = htParseNum(row.carga_kg);
                const cargaLb = htParseNum(row.carga_lb);
                if ((isNaN(cargaKg) || cargaKg <= 0) && !isNaN(cargaLb) && cargaLb > 0) cargaKg = cargaLb * 0.45359237;
                const fEns = htFechaIso(row.fecha_ensayo);

                let areaCm2 = NaN, pv = NaN, kgcm2 = NaN;
                if (!isNaN(d1) && d1 > 0 && !isNaN(d2) && d2 > 0) {
                    const dProm = (d1 + d2) / 2.0;
                    areaCm2 = Math.PI * Math.pow(dProm / 2.0, 2);
                    setCalc(tr, 'area_cm2', areaCm2.toFixed(2));
                } else setCalc(tr, 'area_cm2', '');
                if (!isNaN(areaCm2) && !isNaN(l1) && l1 > 0 && !isNaN(l2) && l2 > 0 && !isNaN(masa) && masa > 0) {
                    pv = Math.round((masa / ((areaCm2 * ((l1 + l2) / 2.0)) / 1000000.0)) / 10.0) * 10;
                    setCalc(tr, 'peso_volumetrico', pv);
                } else setCalc(tr, 'peso_volumetrico', '');
                if (!isNaN(areaCm2) && areaCm2 > 0 && !isNaN(cargaKg) && cargaKg > 0) {
                    kgcm2 = cargaKg / areaCm2;
                    setCalc(tr, 'esfuerzo_kgcm2', kgcm2.toFixed(1));
                } else setCalc(tr, 'esfuerzo_kgcm2', '');

                if (sincronizarConMatriz && trMat && (!isNaN(areaCm2) || !isNaN(cargaKg) || fEns)) {
                    if (!isNaN(areaCm2)) htSetCeldaMatriz(trMat, ['Área (cm²)'], areaCm2.toFixed(2));
                    if (!isNaN(cargaKg) && cargaKg > 0) htSetCeldaMatriz(trMat, ['Carga (kg)'], Math.round(cargaKg));
                    if (!isNaN(pv)) htSetCeldaMatriz(trMat, ['Peso Volumétrico (kg/m³)'], pv);
                    if (!isNaN(kgcm2)) htSetCeldaMatriz(trMat, ['R. compresión. (kg/cm²)', 'R. Compresión (kg/cm²)'], kgcm2.toFixed(1));
                    if (fEns) htSetCeldaMatriz(trMat, ['Fecha de Ensayo'], fEns);
                }
            } else if (motor === 'mortero') {
                const l = htParseNum(row.largo_cm), a = htParseNum(row.ancho_cm), h = htParseNum(row.alto_cm);
                const m = htParseNum(row.masa_kg), c = htParseNum(row.carga_lb);
                const fEns = htFechaIso(row.fecha_ensayo);
                let areaIn2 = NaN, pv = NaN, psi = NaN, kgcm2 = NaN;
                if (!isNaN(l) && l > 0 && !isNaN(a) && a > 0) {
                    areaIn2 = (l * a) / 6.4516;
                    setCalc(tr, 'area_in2', areaIn2.toFixed(2));
                } else setCalc(tr, 'area_in2', '');
                if (!isNaN(l) && l > 0 && !isNaN(a) && a > 0 && !isNaN(h) && h > 0 && !isNaN(m) && m > 0) {
                    pv = Math.round((m / ((l * a * h) / 1000000.0)) / 10.0) * 10;
                    setCalc(tr, 'peso_volumetrico', pv);
                } else setCalc(tr, 'peso_volumetrico', '');
                if (!isNaN(areaIn2) && areaIn2 > 0 && !isNaN(c) && c > 0) {
                    psi = LAB_REDONDEAR_PSI_10 ? Math.round((c / areaIn2) / 10.0) * 10 : Math.round(c / areaIn2);
                    kgcm2 = psi / LAB_DIVISOR_KGCM2;
                    setCalc(tr, 'esfuerzo_psi', psi);
                    setCalc(tr, 'esfuerzo_kgcm2', kgcm2.toFixed(1));
                } else { setCalc(tr, 'esfuerzo_psi', ''); setCalc(tr, 'esfuerzo_kgcm2', ''); }
                if (sincronizarConMatriz && trMat && (!isNaN(areaIn2) || !isNaN(c) || fEns)) {
                    if (!isNaN(areaIn2)) htSetCeldaMatriz(trMat, ['Área (in²)'], areaIn2.toFixed(2));
                    if (!isNaN(c) && c > 0) htSetCeldaMatriz(trMat, ['Carga (lb)'], Math.round(c));
                    if (!isNaN(pv)) htSetCeldaMatriz(trMat, ['Peso Volumétrico (kg/m³)'], pv);
                    if (!isNaN(psi)) htSetCeldaMatriz(trMat, ['R. Compresión (lb/in²)'], psi);
                    if (!isNaN(kgcm2)) htSetCeldaMatriz(trMat, ['R. Compresión. (kg/cm²)', 'R. Compresión (kg/cm²)'], kgcm2.toFixed(1));
                    if (fEns) htSetCeldaMatriz(trMat, ['Fecha de Ensayo'], fEns);
                }
            } else if (motor === 'bloque') {
                const l = htParseNum(row.largo_cm), h = htParseNum(row.alto_cm), a = htParseNum(row.ancho_cm);
                const eExt = htParseNum(row.esp_ext_cm), eInt = htParseNum(row.esp_int_cm);
                const mSec = htParseNum(row.masa_seca_kg), mSum = htParseNum(row.masa_sumergida_kg), mSat = htParseNum(row.masa_saturada_kg);
                const c = htParseNum(row.carga_lb);
                let ab = NaN, an = NaN, dens = NaN, abs = NaN, psi = NaN, kgcm2 = NaN;
                if (!isNaN(l) && l > 0 && !isNaN(a) && a > 0) { ab = (l * a) / 6.4516; setCalc(tr, 'area_bruta_in2', ab.toFixed(2)); } else setCalc(tr, 'area_bruta_in2', '');
                if (!isNaN(mSat) && !isNaN(mSum) && (mSat - mSum) > 0 && !isNaN(h) && h > 0) {
                    const volCm3 = (mSat - mSum) * 1000.0;
                    an = (volCm3 / h) / 6.4516;
                    setCalc(tr, 'area_neta_in2', an.toFixed(2));
                    if (!isNaN(mSec) && mSec > 0) {
                        dens = Math.round((mSec * 1000000.0) / volCm3);
                        abs = ((mSat - mSec) / mSec) * 100.0;
                        setCalc(tr, 'absorcion_pct', abs.toFixed(2));
                    }
                } else { setCalc(tr, 'area_neta_in2', ''); setCalc(tr, 'absorcion_pct', ''); }
                const aEf = !isNaN(an) && an > 0 ? an : ab;
                if (!isNaN(aEf) && aEf > 0 && !isNaN(c) && c > 0) {
                    psi = Math.round(c / aEf);
                    kgcm2 = psi / LAB_DIVISOR_KGCM2;
                    setCalc(tr, 'esfuerzo_psi', psi);
                    setCalc(tr, 'esfuerzo_kgcm2', kgcm2.toFixed(1));
                } else { setCalc(tr, 'esfuerzo_psi', ''); setCalc(tr, 'esfuerzo_kgcm2', ''); }
                if (!isNaN(ab) || !isNaN(c) || !isNaN(abs)) {
                    bloqueAcum.push({ ab, an, c, l, h, a, eInt, eExt, dens, abs, psi, kgcm2 });
                }
            } else if (motor === 'adoquin') {
                const l = htParseNum(row.largo_cm), a = htParseNum(row.ancho_cm), h = htParseNum(row.alto_cm), c = htParseNum(row.carga_lb);
                const mSec = htParseNum(row.masa_seca_kg), mSum = htParseNum(row.masa_sumergida_kg), mSat = htParseNum(row.masa_saturada_kg);
                const fEns = htFechaIso(row.fecha_ensayo);
                let areaIn2 = NaN, pv = NaN, abs = NaN, psi = NaN, kgcm2 = NaN;
                if (!isNaN(l) && l > 0 && !isNaN(a) && a > 0) { areaIn2 = (l * a) / 6.4516; setCalc(tr, 'area_in2', areaIn2.toFixed(2)); } else setCalc(tr, 'area_in2', '');
                if (!isNaN(mSec) && mSec > 0 && !isNaN(mSat) && !isNaN(mSum) && (mSat - mSum) > 0) {
                    pv = Math.round((mSec * 1000000.0) / ((mSat - mSum) * 1000.0));
                    abs = ((mSat - mSec) / mSec) * 100.0;
                    setCalc(tr, 'peso_volumetrico', pv);
                    setCalc(tr, 'absorcion_pct', abs.toFixed(2));
                } else { setCalc(tr, 'peso_volumetrico', ''); setCalc(tr, 'absorcion_pct', ''); }
                if (!isNaN(areaIn2) && areaIn2 > 0 && !isNaN(c) && c > 0) {
                    psi = Math.round(c / areaIn2);
                    kgcm2 = psi / LAB_DIVISOR_KGCM2;
                    setCalc(tr, 'esfuerzo_psi', psi);
                    setCalc(tr, 'esfuerzo_kgcm2', kgcm2.toFixed(1));
                } else { setCalc(tr, 'esfuerzo_psi', ''); setCalc(tr, 'esfuerzo_kgcm2', ''); }
                if (sincronizarConMatriz && trMat && (!isNaN(areaIn2) || !isNaN(c) || fEns)) {
                    if (!isNaN(areaIn2)) htSetCeldaMatriz(trMat, ['Área (in²)'], areaIn2.toFixed(2));
                    if (!isNaN(c) && c > 0) htSetCeldaMatriz(trMat, ['Carga (lb)'], Math.round(c));
                    if (!isNaN(pv)) htSetCeldaMatriz(trMat, ['Peso Volumétrico (kg/m³)'], pv);
                    if (!isNaN(psi)) htSetCeldaMatriz(trMat, ['R. Compresión (lb/in²)'], psi);
                    if (!isNaN(kgcm2)) htSetCeldaMatriz(trMat, ['R. Compresión. (kg/cm²)', 'R. Compresión (kg/cm²)'], kgcm2.toFixed(1));
                    if (!isNaN(abs)) htSetCeldaMatriz(trMat, ['Absorción ((%)(P/P))'], abs.toFixed(2));
                    if (fEns) htSetCeldaMatriz(trMat, ['Fecha de Ensayo'], fEns);
                }
            } else if (motor === 'flexion') {
                const bs = [htParseNum(row.ancho_1), htParseNum(row.ancho_2), htParseNum(row.ancho_3)].filter(v => !isNaN(v) && v > 0);
                const ds = [htParseNum(row.espesor_1), htParseNum(row.espesor_2), htParseNum(row.espesor_3)].filter(v => !isNaN(v) && v > 0);
                const luz = htParseNum(row.luz_in), c = htParseNum(row.carga_lb), distA = htParseNum(row.dist_a_in);
                const zona = String(row.en_tercio_medio || '');
                const fEns = htFechaIso(row.fecha_ensayo);
                const bProm = bs.length ? (bs.reduce((s, x) => s + x, 0) / bs.length) : NaN;
                const dProm = ds.length ? (ds.reduce((s, x) => s + x, 0) / ds.length) : NaN;
                setCalc(tr, 'ancho_prom', !isNaN(bProm) ? bProm.toFixed(2) : '');
                setCalc(tr, 'espesor_prom', !isNaN(dProm) ? dProm.toFixed(2) : '');
                let mrKg = NaN;
                const dict = zona.includes('Descartar') ? 'DESCARTAR' : 'VÁLIDO';
                if (!isNaN(bProm) && !isNaN(dProm) && !isNaN(luz) && luz > 0 && !isNaN(c) && c > 0) {
                    const mrPsi = (zona.includes('<5%') && !isNaN(distA) && distA > 0)
                        ? (3.0 * c * distA) / (bProm * dProm * dProm)
                        : (c * luz) / (bProm * dProm * dProm);
                    mrKg = mrPsi * 0.070307;
                    setCalc(tr, 'mr_kgcm2', mrKg.toFixed(2));
                    setCalc(tr, 'dictamen_falla', dict);
                } else { setCalc(tr, 'mr_kgcm2', ''); setCalc(tr, 'dictamen_falla', ''); }
                if (sincronizarConMatriz && trMat && (!isNaN(bProm) || !isNaN(c) || fEns)) {
                    if (!isNaN(bProm)) htSetCeldaMatriz(trMat, ['Ancho Promedio (in)'], bProm.toFixed(2));
                    if (!isNaN(dProm)) htSetCeldaMatriz(trMat, ['Espesor Promedio (in)'], dProm.toFixed(2));
                    if (!isNaN(luz) && luz > 0) htSetCeldaMatriz(trMat, ['Longitud de Apoyo (in)'], luz.toFixed(2));
                    if (!isNaN(c) && c > 0) htSetCeldaMatriz(trMat, ['Carga (lb)'], Math.round(c));
                    if (!isNaN(mrKg)) htSetCeldaMatriz(trMat, ['Resistencia a la flexión. (kg/cm²)'], mrKg.toFixed(2));
                    if (fEns) htSetCeldaMatriz(trMat, ['Fecha de Ensayo'], fEns);
                }
            } else if (motor === 'martillo_suizo') {
                const pos = String(row.posicion || 'B').toUpperCase();
                const vals = [];
                for (let k = 1; k <= 10; k++) {
                    const rv = htParseNum(row['r' + k]);
                    if (!isNaN(rv) && rv > 0) vals.push(rv);
                }
                if (vals.length > 0) {
                    const pRaw = vals.reduce((s, x) => s + x, 0) / vals.length;
                    const filt = vals.filter(v => Math.abs(v - pRaw) <= 6.0);
                    const prom = filt.length ? (filt.reduce((s, x) => s + x, 0) / filt.length) : pRaw;
                    const rC = prom * 0.9708737864;
                    const curvas = { A: [0.0085, 0.2512, 128.4, -1820], B: [0.0079, 0.2145, 118.2, -1610], C: [0.0072, 0.1910, 108.5, -1420] };
                    const [ca, cb, cc, cd] = curvas[pos] || curvas.B;
                    const psi = Math.round(Math.max(0, ca * rC * rC * rC + cb * rC * rC + cc * rC + cd) / 10.0) * 10;
                    const kgcm2 = Math.round(psi / LAB_DIVISOR_KGCM2);
                    setCalc(tr, 'rebote_promedio', prom.toFixed(1));
                    setCalc(tr, 'esfuerzo_psi', psi);
                    setCalc(tr, 'esfuerzo_kgcm2', kgcm2);
                    if (sincronizarConMatriz && trMat) {
                        htSetCeldaMatriz(trMat, ['Forma de Prueba'], 'Posición ' + pos);
                        htSetCeldaMatriz(trMat, ['Promedio de Rebotes (N°)'], prom.toFixed(1));
                        htSetCeldaMatriz(trMat, ['Estimación R. compresión (lb/in²)'], psi);
                        htSetCeldaMatriz(trMat, ['Estimación R. compresión (kg/cm²)'], kgcm2);
                    }
                } else { setCalc(tr, 'rebote_promedio', ''); setCalc(tr, 'esfuerzo_psi', ''); setCalc(tr, 'esfuerzo_kgcm2', ''); }
            } else if (motor === 'proctor') {
                const mh = htParseNum(row.molde_humedo_g), mm = htParseNum(row.masa_molde_g), vol = htParseNum(row.volumen_molde_ml) || 947.26;
                const th = htParseNum(row.tara_humedo_g), ts = htParseNum(row.tara_seco_g), tt = htParseNum(row.tara_g);
                let hum = NaN, pvh = NaN, pvs = NaN;
                if (!isNaN(th) && !isNaN(ts) && !isNaN(tt) && (ts - tt) > 0) {
                    hum = ((th - ts) / (ts - tt)) * 100.0;
                    setCalc(tr, 'humedad_pct', hum.toFixed(2));
                } else setCalc(tr, 'humedad_pct', '');
                if (!isNaN(mh) && !isNaN(mm) && (mh - mm) > 0 && vol > 0) {
                    pvh = ((mh - mm) / vol) * 1000.0;
                    setCalc(tr, 'densidad_humeda', Math.round(pvh));
                    if (!isNaN(hum)) {
                        pvs = pvh / (1.0 + hum / 100.0);
                        setCalc(tr, 'densidad_seca', Math.round(pvs));
                        proctorPuntos.push({ pvs, hum, metodo: row.metodo_proctor || 'Proctor Estándar A', fecha: htFechaIso(row.fecha_ensayo) });
                    } else setCalc(tr, 'densidad_seca', '');
                } else { setCalc(tr, 'densidad_humeda', ''); setCalc(tr, 'densidad_seca', ''); }
            } else if (motor === 'cono_arena' || motor === 'reemplazo_agua') {
                const rh = htParseNum(row.rec_humedo_g), rs = htParseNum(row.rec_seco_g), rt = htParseNum(row.rec_g);
                const pvsMax = htParseNum(row.pvs_max);
                const fEns = htFechaIso(row.fecha_ensayo);
                let hum = NaN, pvh = NaN, pvs = NaN, comp = NaN;
                if (!isNaN(rh) && !isNaN(rs) && !isNaN(rt) && (rs - rt) > 0) {
                    hum = ((rh - rs) / (rs - rt)) * 100.0;
                    setCalc(tr, 'humedad_pct', hum.toFixed(1));
                } else setCalc(tr, 'humedad_pct', '');
                if (motor === 'cono_arena') {
                    const aEmp = htParseNum(row.arena_empleada_g), aSob = htParseNum(row.arena_sobrante_g), aCono = htParseNum(row.arena_cono_g) || 0;
                    const pu = htParseNum(row.pu_arena) || 1250.0, exc = htParseNum(row.excavado_tara_g), tExc = htParseNum(row.tara_exc_g) || 0;
                    if (!isNaN(aEmp) && !isNaN(aSob) && !isNaN(exc) && (aEmp - aSob - aCono) > 0 && (exc - tExc) > 0) {
                        const volM3 = ((aEmp - aSob - aCono) / 1000.0) / pu;
                        pvh = Math.round(((exc - tExc) / 1000.0) / volM3);
                    }
                } else {
                    const agAn = htParseNum(row.agua_anillo_l) || 0, agP = htParseNum(row.agua_pozo_anillo_l), excKg = htParseNum(row.excavado_humedo_kg);
                    if (!isNaN(agP) && (agP - agAn) > 0 && !isNaN(excKg) && excKg > 0) {
                        pvh = Math.round(excKg / ((agP - agAn) / 1000.0));
                    }
                }
                if (!isNaN(pvh) && !isNaN(hum)) {
                    pvs = Math.round(pvh / (1.0 + hum / 100.0));
                    setCalc(tr, 'pvs_sitio', pvs);
                } else setCalc(tr, 'pvs_sitio', '');
                if (!isNaN(pvs) && !isNaN(pvsMax) && pvsMax > 0) {
                    comp = (pvs / pvsMax) * 100.0;
                    setCalc(tr, 'compactacion_pct', comp.toFixed(1));
                } else setCalc(tr, 'compactacion_pct', '');
                if (sincronizarConMatriz && trMat && (!isNaN(pvs) || !isNaN(hum) || fEns)) {
                    if (!isNaN(pvsMax) && pvsMax > 0) htSetCeldaMatriz(trMat, ['P.V.S Max (kg/m³)', '**P.V.S Max (kg/m³)'], Math.round(pvsMax));
                    if (!isNaN(pvs)) htSetCeldaMatriz(trMat, ['P.V.S.Sitio (kg/m³)'], pvs);
                    if (!isNaN(hum)) htSetCeldaMatriz(trMat, ['Humedad sitio ((%) (P/P))', 'Humedad Sitio ((%) (P/P))'], hum.toFixed(1));
                    if (!isNaN(pvh)) htSetCeldaMatriz(trMat, ['Humedad Sitio (kg/m³)'], pvh);
                    if (!isNaN(comp)) htSetCeldaMatriz(trMat, ['Compactación ((%) (P/P))'], comp.toFixed(1));
                    if (fEns) htSetCeldaMatriz(trMat, ['Fecha de muestreo'], fEns);
                }
            } else if (motor === 'densimetro') {
                const pMax = htParseNum(row.pvs_max), pSit = htParseNum(row.pvs_sitio);
                let comp = NaN;
                if (!isNaN(pMax) && pMax > 0 && !isNaN(pSit) && pSit > 0) {
                    comp = (pSit / pMax) * 100.0;
                    setCalc(tr, 'compactacion_pct', comp.toFixed(1));
                } else setCalc(tr, 'compactacion_pct', '');
                if (sincronizarConMatriz && trMat && (!isNaN(pSit) || !isNaN(pMax) || row.capa)) {
                    if (row.capa) htSetCeldaMatriz(trMat, ['Capa (N°)'], row.capa);
                    if (row.espesor_cm) htSetCeldaMatriz(trMat, ['Espesor (cm)'], row.espesor_cm);
                    if (row.profundidad_cm) htSetCeldaMatriz(trMat, ['Profundidad (cm)'], row.profundidad_cm);
                    if (!isNaN(pMax)) htSetCeldaMatriz(trMat, ['P.V.S Max (kg/m³)'], Math.round(pMax));
                    if (row.humedad_optima) htSetCeldaMatriz(trMat, ['Humedad Óptima ((% )(P/P))'], row.humedad_optima);
                    if (!isNaN(pSit)) htSetCeldaMatriz(trMat, ['P.V.S.Sitio (kg/m³)'], Math.round(pSit));
                    if (row.humedad_sitio_pct) htSetCeldaMatriz(trMat, ['Humedad Sitio ((%) (P/P))'], row.humedad_sitio_pct);
                    if (row.humedad_sitio_kgm3) htSetCeldaMatriz(trMat, ['Humedad Sitio (kg/m³)'], row.humedad_sitio_kgm3);
                    if (!isNaN(comp)) htSetCeldaMatriz(trMat, ['Compactación ((%) (P/P))'], comp.toFixed(1));
                    if (htFechaIso(row.fecha_ensayo)) htSetCeldaMatriz(trMat, ['Fecha de muestreo'], htFechaIso(row.fecha_ensayo));
                }
            } else if (motor === 'granulometria_suelo') {
                const tt = htParseNum(row.tara_g), th = htParseNum(row.humeda_tara_g), ts = htParseNum(row.seca_tara_g);
                const llT = htParseNum(row.ll_tara_g), llH = htParseNum(row.ll_hum_g), llS = htParseNum(row.ll_sec_g);
                const lpT = htParseNum(row.lp_tara_g), lpH = htParseNum(row.lp_hum_g), lpS = htParseNum(row.lp_sec_g);
                const hum = (!isNaN(th) && !isNaN(ts) && !isNaN(tt) && (ts - tt) > 0) ? ((th - ts) / (ts - tt) * 100.0) : NaN;
                const ll = (!isNaN(llH) && !isNaN(llS) && !isNaN(llT) && (llS - llT) > 0) ? ((llH - llS) / (llS - llT) * 100.0) : NaN;
                const lp = (!isNaN(lpH) && !isNaN(lpS) && !isNaN(lpT) && (lpS - lpT) > 0) ? ((lpH - lpS) / (lpS - lpT) * 100.0) : NaN;
                const ip = (!isNaN(ll) && !isNaN(lp)) ? Math.max(0, ll - lp) : NaN;
                setCalc(tr, 'humedad_pct', !isNaN(hum) ? hum.toFixed(2) : '');
                setCalc(tr, 'll_pct', !isNaN(ll) ? ll.toFixed(1) : '');
                setCalc(tr, 'lp_pct', !isNaN(lp) ? lp.toFixed(1) : '');
                setCalc(tr, 'ip_pct', !isNaN(ip) ? ip.toFixed(1) : '');
                if (sincronizarConMatriz && (!isNaN(ll) || !isNaN(lp) || !isNaN(hum))) {
                    document.querySelectorAll('#tabla-body tr').forEach(trM => {
                        const dInp = trM.querySelector('input[data-col="Malla"]') || trM.querySelector('input[data-col="Descripción"]');
                        if (!dInp) return;
                        const dTxt = dInp.value.trim().toLowerCase();
                        if (!isNaN(ll) && dTxt.includes('límite l')) htSetCeldaMatriz(trM, ['% que pasa la malla', 'Resultado'], ll.toFixed(1));
                        if (!isNaN(lp) && dTxt.includes('límite pl')) htSetCeldaMatriz(trM, ['% que pasa la malla', 'Resultado'], lp.toFixed(1));
                        if (!isNaN(ip) && (dTxt === 'i.p' || dTxt.includes('índice'))) htSetCeldaMatriz(trM, ['% que pasa la malla', 'Resultado'], ip.toFixed(1));
                        if (!isNaN(hum) && dTxt.includes('humedad')) htSetCeldaMatriz(trM, ['Resultado', '% que pasa la malla'], hum.toFixed(2));
                    });
                    if (typeof recalculateGranulometria === 'function') recalculateGranulometria();
                }
            } else if (motor === 'granulometria_agregado') {
                const mIni = htParseNum(row.masa_inicial_g), mLav = htParseNum(row.masa_lavada_g);
                if (!isNaN(mIni) && mIni > 0 && !isNaN(mLav) && mLav >= 0) {
                    const pG = Math.max(0, mIni - mLav);
                    const pPct = (pG / mIni) * 100.0;
                    setCalc(tr, 'perdida_lavado_g', pG.toFixed(2));
                    setCalc(tr, 'perdida_lavado_pct', pPct.toFixed(2));
                    if (sincronizarConMatriz) {
                        document.querySelectorAll('#tabla-body tr').forEach(trM => {
                            const dInp = trM.querySelector('input[data-col="Malla"]') || trM.querySelector('input[data-col="Descripción"]');
                            if (dInp && dInp.value.toLowerCase().includes('lavado')) {
                                htSetCeldaMatriz(trM, ['P. Retenido parcial (g)', 'Resultado'], pG.toFixed(2));
                            }
                        });
                        if (typeof recalculateGranulometria === 'function') recalculateGranulometria();
                    }
                } else { setCalc(tr, 'perdida_lavado_g', ''); setCalc(tr, 'perdida_lavado_pct', ''); }
            } else if (motor === 'ge_agregados') {
                const frac = String(row.fraccion || '').toLowerCase();
                const b = htParseNum(row.masa_sss_g), a = htParseNum(row.masa_seca_g);
                const c = htParseNum(row.masa_frasco_agua_g), d = htParseNum(row.masa_sumergida_g);
                if (!isNaN(a) && a > 0 && !isNaN(b) && b > 0) {
                    const abs = ((b - a) / a) * 100.0;
                    setCalc(tr, 'absorcion_pct', abs.toFixed(2));
                    let gb = NaN, gs = NaN, ga = NaN;
                    if (frac.includes('grueso') && !isNaN(d)) {
                        const sNet = d - (isNaN(c) ? 0 : c);
                        if ((b - sNet) > 0 && (a - sNet) > 0) { gb = a / (b - sNet); gs = b / (b - sNet); ga = a / (a - sNet); }
                    } else if (!isNaN(c) && !isNaN(d) && (b + c - d) > 0 && (a + c - d) > 0) {
                        gb = a / (b + c - d); gs = b / (b + c - d); ga = a / (a + c - d);
                    }
                    setCalc(tr, 'ge_bulk', !isNaN(gb) ? gb.toFixed(3) : '');
                    setCalc(tr, 'ge_sss', !isNaN(gs) ? gs.toFixed(3) : '');
                    setCalc(tr, 'ge_aparente', !isNaN(ga) ? ga.toFixed(3) : '');
                    if (sincronizarConMatriz && trMat) {
                        if (row.color) htSetCeldaMatriz(trMat, ['Color'], row.color);
                        if (row.humedad_natural) htSetCeldaMatriz(trMat, ['Humedad Natural ((%) (P/P))'], row.humedad_natural);
                        htSetCeldaMatriz(trMat, ['Absorción ((%)(P/P))'], abs.toFixed(2));
                        if (!isNaN(gb)) htSetCeldaMatriz(trMat, ['Gravedad Específica.'], gb.toFixed(3));
                        if (!isNaN(gs)) htSetCeldaMatriz(trMat, ['Gravedad Específica (SSS)'], gs.toFixed(3));
                        if (!isNaN(ga)) htSetCeldaMatriz(trMat, ['Gravedad Específica Aparente'], ga.toFixed(3));
                    }
                } else { setCalc(tr, 'absorcion_pct', ''); setCalc(tr, 'ge_bulk', ''); setCalc(tr, 'ge_sss', ''); setCalc(tr, 'ge_aparente', ''); }
            } else if (motor === 'cbr') {
                const mh = htParseNum(row.molde_humedo_g), mm = htParseNum(row.masa_molde_g) || 7849, vol = htParseNum(row.volumen_molde_cm3) || 3200;
                const th = htParseNum(row.tara_humedo_g), ts = htParseNum(row.tara_seco_g), tt = htParseNum(row.tara_g);
                const pMax = htParseNum(row.pvs_max), l01 = htParseNum(row.lectura_01), l02 = htParseNum(row.lectura_02), kA = htParseNum(row.constante_anillo) || 10;
                const hum = (!isNaN(th) && !isNaN(ts) && !isNaN(tt) && (ts - tt) > 0) ? ((th - ts) / (ts - tt) * 100.0) : NaN;
                const pvh = (!isNaN(mh) && (mh - mm) > 0 && vol > 0) ? (((mh - mm) / vol) * 1000.0) : NaN;
                const pvs = !isNaN(pvh) ? (!isNaN(hum) ? Math.round(pvh / (1.0 + hum / 100.0)) : Math.round(pvh)) : NaN;
                const comp = (!isNaN(pvs) && !isNaN(pMax) && pMax > 0) ? (pvs / pMax) * 100.0 : NaN;
                const c01 = (!isNaN(l01) && l01 > 0) ? (((l01 * kA) / 3.0) / 1000.0 * 100.0) : NaN;
                const c02 = (!isNaN(l02) && l02 > 0) ? (((l02 * kA) / 3.0) / 1500.0 * 100.0) : NaN;
                const cAd = (!isNaN(c01) || !isNaN(c02)) ? Math.max(isNaN(c01) ? 0 : c01, isNaN(c02) ? 0 : c02) : NaN;
                setCalc(tr, 'densidad_seca', !isNaN(pvs) ? pvs : '');
                setCalc(tr, 'compactacion_pct', !isNaN(comp) ? comp.toFixed(1) : '');
                setCalc(tr, 'cbr_01', !isNaN(c01) ? c01.toFixed(1) : '');
                setCalc(tr, 'cbr_02', !isNaN(c02) ? c02.toFixed(1) : '');
                setCalc(tr, 'cbr_adoptado', !isNaN(cAd) ? cAd.toFixed(1) : '');
                if (sincronizarConMatriz && trMat && (!isNaN(pvs) || !isNaN(cAd))) {
                    htSetCeldaMatriz(trMat, ['Golpes/Capa'], row.golpes || '56');
                    if (!isNaN(pvs)) htSetCeldaMatriz(trMat, ['Densidad Seca (kg/m³)'], pvs);
                    if (!isNaN(hum)) htSetCeldaMatriz(trMat, ['Humedad Moldeo (%)'], hum.toFixed(1));
                    if (!isNaN(comp)) htSetCeldaMatriz(trMat, ['Compactación (%)'], comp.toFixed(1));
                    if (!isNaN(c01)) htSetCeldaMatriz(trMat, ['CBR 0.1" (%)'], c01.toFixed(1));
                    if (!isNaN(c02)) htSetCeldaMatriz(trMat, ['CBR 0.2" (%)'], c02.toFixed(1));
                    if (!isNaN(cAd)) htSetCeldaMatriz(trMat, ['CBR Adoptado (%)'], cAd.toFixed(1));
                    if (htFechaIso(row.fecha_ensayo)) htSetCeldaMatriz(trMat, ['Fecha de Ensayo'], htFechaIso(row.fecha_ensayo));
                }
            } else if (motor === 'ge_suelos') {
                const temp = isNaN(htParseNum(row.temperatura_c)) ? 20.0 : htParseNum(row.temperatura_c);
                const ms = htParseNum(row.masa_suelo_seco_g), mpw = htParseNum(row.masa_picno_agua_g), mpws = htParseNum(row.masa_picno_agua_suelo_g);
                const kF = (1.00034038 - 0.00000777 * temp - 0.00000495 * temp * temp) / 0.9982071;
                setCalc(tr, 'factor_k', kF.toFixed(4));
                if (!isNaN(ms) && ms > 0 && !isNaN(mpw) && !isNaN(mpws) && (ms + mpw - mpws) > 0) {
                    const gs = (ms / (ms + mpw - mpws)) * kF;
                    setCalc(tr, 'gs', gs.toFixed(3));
                    if (sincronizarConMatriz && trMat) {
                        htSetCeldaMatriz(trMat, ['Picnómetro (N°)'], row.picnometro || 'PICNO-02');
                        htSetCeldaMatriz(trMat, ['Temperatura (°C)'], temp.toFixed(1));
                        htSetCeldaMatriz(trMat, ['Masa Suelo Seco (g)'], ms.toFixed(2));
                        htSetCeldaMatriz(trMat, ['Gravedad Específica (Gs)'], gs.toFixed(3));
                        if (htFechaIso(row.fecha_ensayo)) htSetCeldaMatriz(trMat, ['Fecha de Ensayo'], htFechaIso(row.fecha_ensayo));
                    }
                } else setCalc(tr, 'gs', '');
            } else if (motor === 'acero_tension') {
                const diam = htParseNum(row.diametro_mm), peso = htParseNum(row.peso_kg), largo = htParseNum(row.largo_cm);
                const l0 = htParseNum(row.l0_cm), lf = htParseNum(row.lf_cm), fm = htParseNum(row.fuerza_max_kn), flu = htParseNum(row.fluencia_mpa);
                const area = (!isNaN(diam) && diam > 0) ? Math.PI * Math.pow(diam / 2.0, 2) : NaN;
                const pUnit = (!isNaN(peso) && peso > 0 && !isNaN(largo) && largo > 0) ? (peso / (largo / 100.0)) : NaN;
                const fluPsi = (!isNaN(flu) && flu > 0) ? Math.round(flu * 145.0377) : NaN;
                const tensPsi = (!isNaN(fm) && fm > 0 && !isNaN(area) && area > 0) ? Math.round(((fm * 1000.0) / area) * 145.0377) : NaN;
                const tensKg = !isNaN(tensPsi) ? Math.round(tensPsi / LAB_DIVISOR_KGCM2) : NaN;
                const elong = (!isNaN(l0) && l0 > 0 && !isNaN(lf) && lf >= l0) ? (((lf - l0) / l0) * 100.0) : NaN;
                setCalc(tr, 'area_mm2', !isNaN(area) ? area.toFixed(2) : '');
                setCalc(tr, 'peso_unitario', !isNaN(pUnit) ? pUnit.toFixed(3) : '');
                setCalc(tr, 'fluencia_psi', !isNaN(fluPsi) ? fluPsi : '');
                setCalc(tr, 'tension_psi', !isNaN(tensPsi) ? tensPsi : '');
                setCalc(tr, 'elongacion_pct', !isNaN(elong) ? elong.toFixed(1) : '');
                if (sincronizarConMatriz && trMat && (!isNaN(area) || !isNaN(tensPsi) || !isNaN(fluPsi))) {
                    if (row.varilla_num) htSetCeldaMatriz(trMat, ['Varilla (N°)'], row.varilla_num);
                    if (!isNaN(diam)) htSetCeldaMatriz(trMat, ['Diámetro (mm)'], diam.toFixed(2));
                    if (!isNaN(area)) htSetCeldaMatriz(trMat, ['Área (mm²)'], area.toFixed(2));
                    if (!isNaN(pUnit)) htSetCeldaMatriz(trMat, ['Peso Unitario (kg/m)'], pUnit.toFixed(3));
                    if (!isNaN(flu)) htSetCeldaMatriz(trMat, ['Fluencia (MPa)'], flu.toFixed(1));
                    if (!isNaN(fluPsi)) htSetCeldaMatriz(trMat, ['Fluencia (lbf/in²)'], fluPsi);
                    if (!isNaN(tensPsi)) htSetCeldaMatriz(trMat, ['Tensión Máx. (lbf/in²)'], tensPsi);
                    if (!isNaN(tensKg)) htSetCeldaMatriz(trMat, ['Tensión Máx. (kg/cm²)'], tensKg);
                    if (!isNaN(elong)) htSetCeldaMatriz(trMat, ['Elongación (%)'], elong.toFixed(1));
                    if (htFechaIso(row.fecha_ensayo)) htSetCeldaMatriz(trMat, ['Fecha de Ensayo'], htFechaIso(row.fecha_ensayo));
                }
            } else if (motor === 'acero_doblado') {
                const res = String(row.inspeccion || '').includes('Falla') ? 'Falla' : 'Cumple';
                setCalc(tr, 'resultado', res);
                if (sincronizarConMatriz && trMat) {
                    if (row.varilla_num) htSetCeldaMatriz(trMat, ['Varilla (N°)'], row.varilla_num);
                    if (row.diametro_mm) htSetCeldaMatriz(trMat, ['Diámetro Nominal (mm)'], row.diametro_mm);
                    if (row.angulo_deg) htSetCeldaMatriz(trMat, ['Ángulo de Doblado (°)'], row.angulo_deg);
                    if (row.mandril) htSetCeldaMatriz(trMat, ['Diámetro de Mandril'], row.mandril);
                    htSetCeldaMatriz(trMat, ['Resultado (Cumple/Falla)'], res);
                    if (htFechaIso(row.fecha_ensayo)) htSetCeldaMatriz(trMat, ['Fecha de Ensayo'], htFechaIso(row.fecha_ensayo));
                }
            } else if (motor === 'sanidad') {
                const ro = htParseNum(row.retenido_original_g), mi = htParseNum(row.masa_inicial_g), mf = htParseNum(row.masa_final_g);
                if (!isNaN(mi) && mi > 0 && !isNaN(mf)) {
                    const pG = Math.max(0, mi - mf), pPct = (pG / mi) * 100.0;
                    const gPct = (sanidadTotalRetOrig > 0 && !isNaN(ro) && ro > 0) ? (ro / sanidadTotalRetOrig * 100.0) : 0;
                    const pond = (gPct * pPct) / 100.0;
                    setCalc(tr, 'gradacion_pct', gPct.toFixed(2));
                    setCalc(tr, 'perdida_g', pG.toFixed(2));
                    setCalc(tr, 'perdida_pct', pPct.toFixed(2));
                    setCalc(tr, 'perdida_ponderada_pct', pond.toFixed(2));
                    if (sincronizarConMatriz && trMat) {
                        htSetCeldaMatriz(trMat, ['Fracción'], row.fraccion || codLab);
                        if (!isNaN(ro)) htSetCeldaMatriz(trMat, ['Retenido Original (g)'], ro.toFixed(2));
                        htSetCeldaMatriz(trMat, ['% Gradación Original'], gPct.toFixed(2));
                        htSetCeldaMatriz(trMat, ['Masa Inicial (g)'], mi.toFixed(2));
                        htSetCeldaMatriz(trMat, ['Masa Final (g)'], mf.toFixed(2));
                        htSetCeldaMatriz(trMat, ['Pérdida (g)'], pG.toFixed(2));
                        htSetCeldaMatriz(trMat, ['% Pérdida Fracción'], pPct.toFixed(2));
                        htSetCeldaMatriz(trMat, ['% Pérdida Ponderada'], pond.toFixed(2));
                    }
                } else { setCalc(tr, 'gradacion_pct', ''); setCalc(tr, 'perdida_g', ''); setCalc(tr, 'perdida_pct', ''); setCalc(tr, 'perdida_ponderada_pct', ''); }
            } else if (motor === 'ladrillo') {
                const l = htParseNum(row.largo_cm), a = htParseNum(row.ancho_cm), h = htParseNum(row.alto_cm), m = htParseNum(row.masa_kg);
                let cKg = htParseNum(row.carga_kg);
                const cLb = htParseNum(row.carga_lb);
                if ((isNaN(cKg) || cKg <= 0) && !isNaN(cLb) && cLb > 0) cKg = cLb * 0.45359237;
                const area = (!isNaN(l) && l > 0 && !isNaN(a) && a > 0) ? (l * a) : NaN;
                const pv = (!isNaN(area) && !isNaN(h) && h > 0 && !isNaN(m) && m > 0) ? Math.round(m / ((area * h) / 1000000.0)) : NaN;
                const kgcm2 = (!isNaN(area) && area > 0 && !isNaN(cKg) && cKg > 0) ? (cKg / area) : NaN;
                setCalc(tr, 'area_cm2', !isNaN(area) ? area.toFixed(2) : '');
                setCalc(tr, 'peso_volumetrico', !isNaN(pv) ? pv : '');
                setCalc(tr, 'esfuerzo_kgcm2', !isNaN(kgcm2) ? kgcm2.toFixed(1) : '');
                if (sincronizarConMatriz && trMat && (!isNaN(area) || !isNaN(cKg))) {
                    if (!isNaN(area)) htSetCeldaMatriz(trMat, ['Área (cm²)'], area.toFixed(2));
                    if (!isNaN(cKg) && cKg > 0) htSetCeldaMatriz(trMat, ['Carga (kg)'], Math.round(cKg));
                    if (!isNaN(pv)) htSetCeldaMatriz(trMat, ['Peso Volumétrico (kg/m³)'], pv);
                    if (!isNaN(kgcm2)) htSetCeldaMatriz(trMat, ['R. a la compresión (kg/cm²)'], kgcm2.toFixed(1));
                    if (htFechaIso(row.fecha_ensayo)) htSetCeldaMatriz(trMat, ['Fecha de Ensayo'], htFechaIso(row.fecha_ensayo));
                }
            } else if (motor === 'roca') {
                const d1 = htParseNum(row.diametro_1), d2 = htParseNum(row.diametro_2), h1 = htParseNum(row.largo_1), h2 = htParseNum(row.largo_2), cLb = htParseNum(row.carga_lb);
                const dP = (!isNaN(d1) && !isNaN(d2)) ? (d1 + d2) / 2.0 : (!isNaN(d1) ? d1 : d2);
                const hP = (!isNaN(h1) && !isNaN(h2)) ? (h1 + h2) / 2.0 : (!isNaN(h1) ? h1 : h2);
                const ld = (!isNaN(dP) && dP > 0 && !isNaN(hP) && hP > 0) ? (hP / dP) : NaN;
                const area = (!isNaN(dP) && dP > 0) ? Math.PI * Math.pow(dP / 2.0, 2) : NaN;
                const psi = (!isNaN(area) && area > 0 && !isNaN(cLb) && cLb > 0) ? Math.round(cLb / (area / 6.4516)) : NaN;
                const kgcm2 = !isNaN(psi) ? (psi / LAB_DIVISOR_KGCM2) : NaN;
                const mpa = !isNaN(psi) ? (psi * 0.00689476) : NaN;
                setCalc(tr, 'relacion_ld', !isNaN(ld) ? ld.toFixed(2) : '');
                setCalc(tr, 'area_cm2', !isNaN(area) ? area.toFixed(2) : '');
                setCalc(tr, 'esfuerzo_mpa', !isNaN(mpa) ? mpa.toFixed(2) : '');
                setCalc(tr, 'esfuerzo_kgcm2', !isNaN(kgcm2) ? kgcm2.toFixed(1) : '');
                setCalc(tr, 'esfuerzo_psi', !isNaN(psi) ? psi : '');
                if (sincronizarConMatriz && trMat && (!isNaN(area) || !isNaN(psi))) {
                    if (!isNaN(dP)) htSetCeldaMatriz(trMat, ['Diámetro (cm)'], dP.toFixed(2));
                    if (!isNaN(hP)) htSetCeldaMatriz(trMat, ['Altura (cm)'], hP.toFixed(2));
                    if (!isNaN(ld)) htSetCeldaMatriz(trMat, ['Relación L/D'], ld.toFixed(2));
                    if (!isNaN(area)) htSetCeldaMatriz(trMat, ['Área (cm²)'], area.toFixed(2));
                    if (!isNaN(cLb)) htSetCeldaMatriz(trMat, ['Carga Máx. (lb)'], Math.round(cLb));
                    if (!isNaN(mpa)) htSetCeldaMatriz(trMat, ['Resistencia (MPa)'], mpa.toFixed(2));
                    if (!isNaN(kgcm2)) htSetCeldaMatriz(trMat, ['Resistencia (kg/cm²)'], kgcm2.toFixed(1));
                    if (!isNaN(psi)) htSetCeldaMatriz(trMat, ['Resistencia (lbf/in²)'], psi);
                    if (htFechaIso(row.fecha_ensayo)) htSetCeldaMatriz(trMat, ['Fecha de Ensayo'], htFechaIso(row.fecha_ensayo));
                }
            } else if (motor === 'pesos_volumetricos') {
                const volL = htParseNum(row.vol_recipiente_l), pR = htParseNum(row.peso_recipiente_kg), pS = htParseNum(row.peso_suelto_kg), pC = htParseNum(row.peso_compacto_kg);
                const th = htParseNum(row.tara_humedo_g), ts = htParseNum(row.tara_seco_g), tt = htParseNum(row.tara_g);
                const volM3 = (!isNaN(volL) && volL > 0) ? (volL / 1000.0) : NaN;
                const pvss = (!isNaN(volM3) && !isNaN(pR) && !isNaN(pS) && (pS - pR) > 0) ? Math.round((pS - pR) / volM3) : NaN;
                const pvsc = (!isNaN(volM3) && !isNaN(pR) && !isNaN(pC) && (pC - pR) > 0) ? Math.round((pC - pR) / volM3) : NaN;
                const hum = (!isNaN(th) && !isNaN(ts) && !isNaN(tt) && (ts - tt) > 0) ? ((th - ts) / (ts - tt) * 100.0) : NaN;
                setCalc(tr, 'pvss', !isNaN(pvss) ? pvss : '');
                setCalc(tr, 'pvsc', !isNaN(pvsc) ? pvsc : '');
                setCalc(tr, 'humedad_pct', !isNaN(hum) ? hum.toFixed(2) : '');
                if (sincronizarConMatriz && trMat && (!isNaN(pvss) || !isNaN(pvsc) || !isNaN(hum))) {
                    if (!isNaN(pvss)) htSetCeldaMatriz(trMat, ['PVSS (kg/m³)'], pvss);
                    if (!isNaN(pvsc)) htSetCeldaMatriz(trMat, ['PVSC (kg/m³)'], pvsc);
                    if (!isNaN(hum)) htSetCeldaMatriz(trMat, ['Humedad Natural ((%)(P/P))'], hum.toFixed(2));
                }
            } else if (motor === 'equivalente_arena') {
                const vals = [];
                for (let k = 1; k <= 3; k++) {
                    const arc = htParseNum(row['arcilla_' + k]), are = htParseNum(row['arena_' + k]);
                    if (!isNaN(arc) && arc > 0 && !isNaN(are) && are >= 0) vals.push(Math.ceil((are / arc) * 100.0));
                }
                if (vals.length > 0) {
                    const eq = Math.ceil(vals.reduce((s, x) => s + x, 0) / vals.length);
                    setCalc(tr, 'equiv_arena_pct', eq);
                    if (sincronizarConMatriz && trMat) htSetCeldaMatriz(trMat, ['Equivalente de arena. ((%)(P/P))'], eq);
                } else setCalc(tr, 'equiv_arena_pct', '');
            } else if (motor === 'particulas_desmenuzables') {
                const mi = htParseNum(row.masa_inicial_g), mr = htParseNum(row.masa_retenida_g);
                if (!isNaN(mi) && mi > 0 && !isNaN(mr) && mr >= 0) {
                    const pct = (Math.max(0, mi - mr) / mi) * 100.0;
                    setCalc(tr, 'terrones_pct', pct.toFixed(2));
                    if (sincronizarConMatriz && trMat) htSetCeldaMatriz(trMat, ['Terrones de arcilla. ((%)(P/P))'], pct.toFixed(2));
                } else setCalc(tr, 'terrones_pct', '');
            } else if (motor === 'revenimiento') {
                const rIn = htParseNum(row.revenimiento_in), tC = htParseNum(row.temperatura_c);
                const rCm = (!isNaN(rIn) && rIn > 0) ? (Math.round(rIn * 2.54 * 2) / 2) : NaN;
                setCalc(tr, 'revenimiento_cm', !isNaN(rCm) ? rCm : '');
                if (sincronizarConMatriz && trMat && (!isNaN(rIn) || !isNaN(tC))) {
                    if (!isNaN(rIn)) htSetCeldaMatriz(trMat, ['Reven. (in)'], rIn);
                    if (!isNaN(rCm)) htSetCeldaMatriz(trMat, ['Reven. (cm)'], rCm);
                    if (!isNaN(tC)) htSetCeldaMatriz(trMat, ['Temp. (°C)'], tC);
                    if (htFechaIso(row.fecha_ensayo)) htSetCeldaMatriz(trMat, ['Fecha de Finalización'], htFechaIso(row.fecha_ensayo));
                }
            } else if (motor === 'colorimetria') {
                const pl = String(row.placa_gardner || '');
                const mN = pl.match(/(\d+)/);
                const num = mN ? parseInt(mN[1], 10) : 1;
                const res = num <= 3 ? 'Aceptable (≤ Placa 3)' : 'Rechazado (> Placa 3)';
                setCalc(tr, 'resultado', res);
                if (sincronizarConMatriz && trMat) {
                    htSetCeldaMatriz(trMat, ['Color (Placa Orgánica)'], pl);
                    htSetCeldaMatriz(trMat, ['Resultado'], res);
                    if (htFechaIso(row.fecha_ensayo)) htSetCeldaMatriz(trMat, ['Fecha de Ensayo'], htFechaIso(row.fecha_ensayo));
                }
            }
        });

        // Resúmenes multi-punto (Proctor y Bloques)
        if (motor === 'proctor' && proctorPuntos.length > 0) {
            proctorPuntos.sort((a, b) => b.pvs - a.pvs);
            const best = proctorPuntos[0];
            if (resumenBar) {
                resumenBar.style.display = 'block';
                resumenBar.textContent = `Resumen Proctor (Máximo Medido): P.V.S. Máximo = ${Math.round(best.pvs)} kg/m³ · Humedad Óptima = ${best.hum.toFixed(1)}% · ${best.metodo}`;
            }
            if (sincronizarConMatriz) {
                const tr0 = document.querySelector('#tabla-body tr');
                if (tr0) {
                    htSetCeldaMatriz(tr0, ['P.Vol.Seco Máximo (kg/m³)'], Math.round(best.pvs));
                    htSetCeldaMatriz(tr0, ['Cont. Humedad Óptima ((%)(P/P))'], best.hum.toFixed(1));
                    htSetCeldaMatriz(tr0, ['Método Ensayo'], best.metodo);
                    if (best.fecha) htSetCeldaMatriz(tr0, ['Fecha de Ensayo'], best.fecha);
                }
            }
        } else if (motor === 'bloque' && bloqueAcum.length > 0) {
            const avgK = k => {
                const v = bloqueAcum.map(x => x[k]).filter(x => !isNaN(x) && x > 0);
                return v.length ? (v.reduce((s, x) => s + x, 0) / v.length) : NaN;
            };
            const ab = avgK('ab'), an = avgK('an'), c = avgK('c'), l = avgK('l'), h = avgK('h'), a = avgK('a');
            const eInt = avgK('eInt'), eExt = avgK('eExt'), dens = avgK('dens'), abs = avgK('abs'), psi = avgK('psi'), kgcm2 = avgK('kgcm2');
            if (resumenBar) {
                resumenBar.style.display = 'block';
                resumenBar.textContent = `Promedio (${bloqueAcum.length} bloques): A. Neta = ${isNaN(an) ? '--' : an.toFixed(2)} in² · R. Neta = ${isNaN(psi) ? '--' : Math.round(psi)} psi (${isNaN(kgcm2) ? '--' : kgcm2.toFixed(1)} kg/cm²) · Absorción = ${isNaN(abs) ? '--' : abs.toFixed(2)}%`;
            }
            if (sincronizarConMatriz) {
                document.querySelectorAll('#tabla-body tr').forEach(trM => {
                    const dInp = trM.querySelector('input[data-col="Descripción"]');
                    const uInp = trM.querySelector('input[data-col="Unidad"]');
                    if (!dInp) return;
                    const d = dInp.value.trim().toLowerCase();
                    const u = uInp ? uInp.value.trim().toLowerCase() : '';
                    if (d.includes('área bruta') && !isNaN(ab)) htSetCeldaMatriz(trM, ['Resultado'], ab.toFixed(2));
                    else if (d.includes('área neta') && !isNaN(an)) htSetCeldaMatriz(trM, ['Resultado'], an.toFixed(2));
                    else if (d === 'carga' && !isNaN(c)) htSetCeldaMatriz(trM, ['Resultado'], Math.round(c));
                    else if (d === 'largo' && !isNaN(l)) htSetCeldaMatriz(trM, ['Resultado'], l.toFixed(2));
                    else if (d === 'alto' && !isNaN(h)) htSetCeldaMatriz(trM, ['Resultado'], h.toFixed(2));
                    else if (d === 'ancho' && !isNaN(a)) htSetCeldaMatriz(trM, ['Resultado'], a.toFixed(2));
                    else if (d.includes('espesor interno') && !isNaN(eInt)) htSetCeldaMatriz(trM, ['Resultado'], eInt.toFixed(2));
                    else if (d.includes('espesor externo') && !isNaN(eExt)) htSetCeldaMatriz(trM, ['Resultado'], eExt.toFixed(2));
                    else if ((d.includes('peso volumétrico') || d.includes('densidad')) && !isNaN(dens)) htSetCeldaMatriz(trM, ['Resultado'], Math.round(dens));
                    else if (d.includes('absorción') && !isNaN(abs)) htSetCeldaMatriz(trM, ['Resultado'], abs.toFixed(2));
                    else if (d.includes('r. compresión')) {
                        if ((u.includes('lb') || u.includes('psi')) && !isNaN(psi)) htSetCeldaMatriz(trM, ['Resultado'], Math.round(psi));
                        else if (!isNaN(kgcm2)) htSetCeldaMatriz(trM, ['Resultado'], kgcm2.toFixed(1));
                    }
                });
            }
        } else if (resumenBar) {
            resumenBar.style.display = 'none';
        }

        if (sincronizarConMatriz && typeof recalculateEdades === 'function') {
            recalculateEdades();
        }
    }

    function inicializarHojaTecnica() {
        capturaTecnicaFilas = construirFilasInicialesHojaTecnica();
        renderizarTablaHojaTecnica();
        if ((HOJA_TECNICA_CONFIG && (HOJA_TECNICA_CONFIG.modo_filas || 'por_muestra') === 'por_muestra')) {
            sincronizarHojaTecnicaConFilasMatriz(leerFilasMatrizActual());
        } else {
            recalcularHojaTecnicaEnVivo(false);
        }
    }

    document.addEventListener('DOMContentLoaded', () => {
        inicializarMatriz();
        inicializarHojaTecnica();
    });
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

            <div style="margin-bottom:16px;">
                <label style="font-size:12.5px; font-weight:600; color:#334155; display:block; margin-bottom:6px;">Observaciones Pendientes para Informe (Opcional):</label>
                <input type="text" name="observaciones_pendientes" class="form-control" style="width:100%; box-sizing:border-box; padding:9px 12px; border:1px solid #cbd5e1; border-radius:6px; font-size:13px;" value="<?= htmlspecialchars($metadatos['observaciones_pendientes'] ?? '', ENT_QUOTES, 'UTF-8') ?>" placeholder="Ej: Pendiente verificación de equipo calibrado">
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

<!-- Modal Corrección Administrativa (Fase B TAREA-059) -->
<div id="modalCorreccionAdmin" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(15,23,42,0.6); z-index:9999; backdrop-filter:blur(3px);">
    <div style="background:white; border-radius:12px; max-width:540px; margin:60px auto; padding:24px; box-shadow:0 20px 25px -5px rgba(0,0,0,0.15); border:1px solid #e2e8f0; font-family:inherit;">
        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:15px; border-bottom:1px solid #f1f5f9; padding-bottom:12px;">
            <h3 style="margin:0; color:#1e293b; font-size:17px; font-weight:700; display:flex; align-items:center; gap:8px;">
                <i class="fa-solid fa-pen-ruler" style="color:#d97706;"></i> Solicitar Corrección Administrativa
            </h3>
            <button type="button" onclick="cerrarModalCorreccionAdmin()" style="background:none; border:none; font-size:20px; color:#94a3b8; cursor:pointer;">&times;</button>
        </div>
        <form method="POST" action="/Cycsa/publico/operaciones/solicitar-correccion-admin">
            <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($_SESSION['csrf_token'] ?? '', ENT_QUOTES, 'UTF-8') ?>">
            <input type="hidden" name="id_detalle" value="<?= (int)$detalle['id'] ?>">

            <div style="background:#fffbeb; border:1px solid #fde68a; color:#92400e; padding:10px 14px; border-radius:8px; font-size:12.5px; margin-bottom:14px; line-height:1.4;">
                <i class="fa-solid fa-triangle-exclamation"></i>
                Se emitirá la <strong>Versión <?= $versionActualNum + 1 ?></strong> que sustituirá a la versión actual. La matriz pasará a <strong>PENDIENTE DE REVISIÓN</strong> para su re-aprobación formal. Los datos técnicos de ensayos no se alteran.
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:4px;">
                    Motivo obligatorio de la corrección: <span style="color:#dc2626;">*</span>
                </label>
                <textarea name="motivo_correccion" rows="2" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 10px; font-size:13px; font-family:inherit; box-sizing:border-box;" placeholder="Ej. Corrección de razón social por solicitud formal del cliente..." required></textarea>
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:4px;">
                    Nombre o Razón Social (para el informe):
                </label>
                <input type="text" name="cliente_nombre" value="<?= htmlspecialchars($metadatos['cliente_nombre'] ?? '') ?>" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 10px; font-size:13px; box-sizing:border-box;">
            </div>

            <div style="margin-bottom:12px;">
                <label style="display:block; font-size:12.5px; font-weight:700; color:#334155; margin-bottom:4px;">
                    Proyecto:
                </label>
                <input type="text" name="nombre_proyecto" value="<?= htmlspecialchars($metadatos['proyecto'] ?? '') ?>" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 10px; font-size:13px; box-sizing:border-box;">
            </div>

            <div style="display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:16px;">
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:4px;">Ubicación:</label>
                    <input type="text" name="ubicacion" value="<?= htmlspecialchars($metadatos['ubicacion'] ?? '') ?>" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 10px; font-size:13px; box-sizing:border-box;">
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:700; color:#334155; margin-bottom:4px;">Atención a:</label>
                    <input type="text" name="atencion_a" value="<?= htmlspecialchars($metadatos['atencion_a'] ?? '') ?>" style="width:100%; border:1px solid #cbd5e1; border-radius:6px; padding:8px 10px; font-size:13px; box-sizing:border-box;">
                </div>
            </div>

            <div style="display:flex; justify-content:flex-end; gap:10px;">
                <button type="button" onclick="cerrarModalCorreccionAdmin()" class="btn-matriz-secondary" style="height:36px; padding:0 14px;">Cancelar</button>
                <button type="submit" class="btn-matriz-primary" style="background:#d97706; border-color:#b45309; height:36px; padding:0 16px;">
                    <i class="fa-solid fa-floppy-disk"></i> Guardar y Emitir Versión <?= $versionActualNum + 1 ?>
                </button>
            </div>
        </form>
    </div>
</div>

<script>
function abrirModalCorreccionAdmin() {
    document.getElementById('modalCorreccionAdmin').style.display = 'block';
}
function cerrarModalCorreccionAdmin() {
    document.getElementById('modalCorreccionAdmin').style.display = 'none';
}
window.addEventListener('click', function(e) {
    const modCorr = document.getElementById('modalCorreccionAdmin');
    if (modCorr && e.target === modCorr) cerrarModalCorreccionAdmin();
});
</script>

<?php endif; ?>

<script>
(function habilitarNavegacionFlechasMatrices() {
    const manejarTabla = (e) => {
        const key = e.key;
        if (!['ArrowUp', 'ArrowDown', 'ArrowLeft', 'ArrowRight', 'Enter'].includes(key)) return;
        const actual = e.target;
        if (!actual || !['INPUT', 'SELECT'].includes(actual.tagName)) return;
        const tbody = actual.closest('#tbody-captura-tecnica-lab, #tabla-body');
        if (!tbody) return;
        const trActual = actual.closest('tr');
        if (!trActual) return;

        const selector = 'input:not([type="hidden"]):not([type="checkbox"]):not([type="radio"]), select';
        const esEditable = (el) => el && !el.disabled && !el.readOnly && el.offsetParent !== null;
        const filas = Array.from(tbody.querySelectorAll('tr')).filter(r => r.querySelector(selector));
        const rowIdx = filas.indexOf(trActual);
        if (rowIdx < 0) return;

        const camposFila = Array.from(trActual.querySelectorAll(selector));
        const colIdx = camposFila.indexOf(actual);
        if (colIdx < 0) return;

        const tipo = (actual.type || 'text').toLowerCase();
        const enfocar = (el) => {
            if (!el) return;
            el.focus();
            if (typeof el.select === 'function' && (el.type === 'text' || el.type === 'number')) {
                try { el.select(); } catch (_) {}
            }
            if (typeof el.scrollIntoView === 'function') {
                el.scrollIntoView({ block: 'nearest', inline: 'nearest' });
            }
        };

        if (key === 'ArrowDown' || key === 'Enter') {
            if (actual.tagName === 'SELECT' && key === 'ArrowDown' && !e.ctrlKey && !e.altKey) return;
            e.preventDefault();
            for (let r = rowIdx + 1; r < filas.length; r++) {
                const cand = filas[r].querySelectorAll(selector)[colIdx];
                if (esEditable(cand)) { enfocar(cand); return; }
            }
            return;
        }
        if (key === 'ArrowUp') {
            if (actual.tagName === 'SELECT' && !e.ctrlKey && !e.altKey) return;
            e.preventDefault();
            for (let r = rowIdx - 1; r >= 0; r--) {
                const cand = filas[r].querySelectorAll(selector)[colIdx];
                if (esEditable(cand)) { enfocar(cand); return; }
            }
            return;
        }
        if (key === 'ArrowRight' || key === 'ArrowLeft') {
            if (tipo === 'date' && !e.ctrlKey && !e.altKey) return;
            if (tipo === 'text' && !e.ctrlKey && !e.altKey && typeof actual.selectionStart === 'number') {
                const len = (actual.value || '').length;
                const todoSel = (actual.selectionStart === 0 && actual.selectionEnd === len);
                if (key === 'ArrowRight' && !todoSel && actual.selectionEnd < len) return;
                if (key === 'ArrowLeft' && !todoSel && actual.selectionStart > 0) return;
            }
            const paso = (key === 'ArrowRight') ? 1 : -1;
            for (let c = colIdx + paso; c >= 0 && c < camposFila.length; c += paso) {
                if (esEditable(camposFila[c])) {
                    e.preventDefault();
                    enfocar(camposFila[c]);
                    return;
                }
            }
        }
    };
    document.addEventListener('keydown', manejarTabla);
})();
</script>
