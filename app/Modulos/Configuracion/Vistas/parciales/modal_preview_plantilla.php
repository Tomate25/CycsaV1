<?php
$limpiarUtf8 = static function(?string $texto): string {
    if ($texto === null || $texto === '') return '';
    if (strpos($texto, 'Ã') !== false || strpos($texto, 'Â') !== false) {
        $reparado = @mb_convert_encoding($texto, 'ISO-8859-1', 'UTF-8');
        if ($reparado !== false && mb_check_encoding($reparado, 'UTF-8')) {
            $texto = $reparado;
        }
    }
    return htmlspecialchars($texto, ENT_QUOTES, 'UTF-8');
};

$esHorizontal = ($plantilla['orientacion'] ?? 'horizontal') === 'horizontal';
$bgUrl = $esHorizontal ? '/Cycsa/publico/img/hoja_membretada_horizontal.jpg' : '/Cycsa/publico/img/hoja_membretada_vertical.jpg';
$selloUrl = !empty($plantilla['sello_acreditacion_url']) ? $plantilla['sello_acreditacion_url'] : 'publico/img/sello_acreditacion_default.jpeg';
$tieneSello = !empty($plantilla['incluir_sello_acreditacion']);

// Coordenadas absolutas calibradas exactamente al membrete CYCSA
$sheetWidth = $esHorizontal ? '980px' : '720px';
$sheetHeight = $esHorizontal ? '757px' : '932px';

$selloLeft = $esHorizontal ? '165px' : '155px';
$selloWidth = $esHorizontal ? '100px' : '95px';

$cabeceraLeft = $tieneSello ? ($esHorizontal ? '275px' : '260px') : ($esHorizontal ? '175px' : '160px');
$cabeceraRight = $esHorizontal ? '38px' : '32px';

$cuerpoTop = $esHorizontal ? '136px' : '140px';
$cuerpoBottom = $esHorizontal ? '104px' : '110px';
$cuerpoSide = $esHorizontal ? '38px' : '32px';
?>

<style>
    .preview-wrap {
        font-family: Arial, Helvetica, sans-serif;
        color: #0f172a;
        display: flex;
        flex-direction: column;
        height: 100%;
    }
    .preview-nav {
        background: #ffffff;
        border-bottom: 1px solid #cbd5e1;
        padding: 8px 16px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        flex-wrap: wrap;
        gap: 10px;
    }
    .preview-meta-title {
        display: flex;
        align-items: center;
        gap: 8px;
    }
    .preview-badge-cod {
        font-family: monospace;
        background: #103487;
        color: #ffffff;
        padding: 3px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 700;
    }
    .preview-name-title {
        font-size: 13px;
        font-weight: 700;
        color: #0f172a;
    }
    .preview-badge-ver {
        font-family: monospace;
        background: #e2e8f0;
        color: #475569;
        padding: 2px 6px;
        border-radius: 4px;
        font-size: 11px;
    }
    .preview-badge-ori {
        background: #f8fafc;
        border: 1px solid #cbd5e1;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 11px;
        color: #475569;
    }
    .preview-controls-right {
        display: flex;
        align-items: center;
        gap: 12px;
    }
    .preview-zoom-group {
        display: flex;
        align-items: center;
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        border-radius: 6px;
        padding: 2px 4px;
        gap: 4px;
    }
    .preview-zoom-label {
        font-size: 11px;
        font-weight: 700;
        color: #475569;
        padding: 0 4px;
    }
    .preview-zoom-btn {
        background: transparent;
        border: none;
        color: #475569;
        padding: 4px 8px;
        border-radius: 4px;
        font-size: 11px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }
    .preview-zoom-btn:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .preview-zoom-btn.active {
        background: #103487;
        color: #ffffff;
    }
    .preview-tabs {
        display: flex;
        gap: 6px;
    }
    .preview-tab-btn {
        background: #f1f5f9;
        border: 1px solid #cbd5e1;
        color: #475569;
        padding: 5px 12px;
        border-radius: 6px;
        font-size: 11.5px;
        font-weight: 600;
        cursor: pointer;
        transition: all 0.15s;
    }
    .preview-tab-btn:hover {
        background: #e2e8f0;
        color: #0f172a;
    }
    .preview-tab-btn.active {
        background: #103487;
        color: #ffffff;
        border-color: #103487;
    }

    .preview-body-content {
        padding: 16px;
        background: #334155;
        overflow-y: auto;
        overflow-x: auto;
        display: flex;
        justify-content: center;
        align-items: flex-start;
        min-height: 540px;
    }
    .preview-viewport-container {
        width: 100%;
        display: flex;
        justify-content: center;
    }
    .preview-sheet-outer {
        display: flex;
        justify-content: center;
        align-items: flex-start;
        transition: height 0.2s ease;
    }
    .preview-sheet {
        position: relative;
        background-color: #ffffff;
        background-image: url('<?= $bgUrl ?>');
        background-size: 100% 100%;
        background-position: center;
        background-repeat: no-repeat;
        box-shadow: 0 10px 30px rgba(0,0,0,0.35);
        border-radius: 2px;
        box-sizing: border-box;
        overflow: hidden;
        flex-shrink: 0;
        width: <?= $sheetWidth ?>;
        height: <?= $sheetHeight ?>;
    }

    /* 1. SELLO DE ACREDITACIÓN (Fijo entre logo y textos institucionales) */
    .preview-sello-box {
        position: absolute;
        top: 18px;
        left: <?= $selloLeft ?>;
        width: <?= $selloWidth ?>;
        height: 85px;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        border-radius: 3px;
        padding: 3px;
        display: flex;
        flex-direction: column;
        justify-content: center;
        align-items: center;
        box-shadow: 0 1px 3px rgba(0,0,0,0.06);
        box-sizing: border-box;
        z-index: 10;
    }
    .preview-sello-box img {
        max-height: 58px;
        max-width: 90%;
        object-fit: contain;
        display: block;
    }
    .preview-sello-txt {
        font-size: 6.5px;
        font-weight: 800;
        color: #103487;
        margin-top: 2px;
        letter-spacing: 0.2px;
        text-transform: uppercase;
        text-align: center;
    }

    /* 2. ZONA CABECERA (Institucional, a la derecha del logo y sello) */
    .sheet-cabecera {
        position: absolute;
        top: 18px;
        left: <?= $cabeceraLeft ?>;
        right: <?= $cabeceraRight ?>;
        height: 85px;
        display: flex;
        justify-content: space-between;
        align-items: center;
        box-sizing: border-box;
        z-index: 5;
    }
    .cabecera-titulos {
        text-align: left;
        flex: 1;
        padding-right: 12px;
    }
    .cabecera-empresa {
        font-size: 13.5px;
        font-weight: 800;
        color: #000000;
        letter-spacing: 0.5px;
        text-transform: uppercase;
        margin: 0;
    }
    .cabecera-subtitulo {
        font-size: 8.5px;
        color: #333333;
        font-weight: 700;
        text-transform: uppercase;
        margin-top: 2px;
    }
    .cabecera-informe-titulo {
        font-size: 10.5px;
        font-weight: 800;
        color: #103487;
        text-transform: uppercase;
        margin-top: 3px;
    }
    .cabecera-badge-box {
        text-align: right;
        min-width: 110px;
    }
    .badge-informe-num {
        font-family: monospace;
        background: #ffffff;
        border: 1.5px solid #000000;
        color: #000000;
        padding: 2.5px 8px;
        border-radius: 2px;
        font-size: 10.5px;
        font-weight: 700;
        display: inline-block;
    }
    .badge-informe-ver {
        font-size: 8px;
        font-weight: 700;
        color: #333333;
        margin-top: 3px;
    }

    /* 3. ZONA CUERPO (Estrictamente debajo del logo y arriba del pie) */
    .preview-cuerpo {
        position: absolute;
        top: <?= $cuerpoTop ?>;
        left: <?= $cuerpoSide ?>;
        right: <?= $cuerpoSide ?>;
        bottom: <?= $cuerpoBottom ?>;
        display: flex;
        flex-direction: column;
        justify-content: space-between;
        box-sizing: border-box;
        overflow: hidden;
    }

    /* Metadata oficial */
    .tabla-metadatos-preview {
        width: 100%;
        border-collapse: collapse;
        font-size: 7.8px;
        background: transparent;
        margin-bottom: 4px;
    }
    .tabla-metadatos-preview td {
        padding: 1.5px 3px;
        vertical-align: top;
        color: #000000;
        line-height: 1.22;
    }
    .tabla-metadatos-preview .lbl-col {
        font-weight: 800;
        color: #000000;
        white-space: nowrap;
    }
    .tabla-metadatos-preview .val-col {
        color: #000000;
    }

    /* Contenedor central de matriz técnica */
    .preview-matriz-area {
        flex-grow: 1;
        margin: 4px 0 8px 0;
        overflow-y: auto;
        overflow-x: hidden;
        background: #ffffff;
        border: 1px solid #cbd5e1;
        padding: 6px;
        box-sizing: border-box;
    }
    .preview-sec-header {
        font-weight: 800;
        color: #103487;
        font-size: 9.5px;
        border-bottom: 1.5px solid #103487;
        padding-bottom: 2px;
        margin-bottom: 5px;
        text-transform: uppercase;
        display: flex;
        align-items: center;
        gap: 4px;
    }
    .preview-data-tbl {
        width: 100%;
        border-collapse: collapse;
        font-size: 7.4px;
        border: 1px solid #000000;
        margin-bottom: 8px;
    }
    .preview-data-tbl th {
        background: #f1f5f9;
        color: #000000;
        font-weight: 800;
        border: 1px solid #000000;
        padding: 3px 4px;
        text-align: center;
        vertical-align: middle;
    }
    .preview-data-tbl td {
        border: 1px solid #000000;
        padding: 3px 4px;
        color: #000000;
        vertical-align: middle;
    }

    /* Pie: Descargo de responsabilidad y firmas institucionales */
    .preview-pie-firmas {
        margin-top: auto;
        padding-top: 4px;
    }
    .preview-disclaimer {
        font-size: 6.8px;
        font-style: italic;
        color: #222222;
        line-height: 1.25;
        text-align: justify;
        margin-bottom: 10px;
        border-top: 0.5px solid #e2e8f0;
        padding-top: 3px;
    }
    .preview-firmas-grid {
        display: grid;
        grid-template-columns: 1fr 1fr;
        gap: 60px;
        text-align: center;
        padding: 0 20px;
    }
    .preview-firma-box {
        text-align: center;
    }
    .preview-firma-linea {
        border-top: 1px dashed #000000;
        width: 70%;
        margin: 12px auto 3px auto;
    }
    .preview-firma-nombre {
        font-size: 8.5px;
        font-weight: 800;
        color: #000000;
    }
    .preview-firma-cargo {
        font-size: 7.5px;
        color: #475569;
    }
</style>

<div class="preview-wrap">
    <!-- BARRA SUPERIOR DE NAVEGACIÓN Y ZOOM -->
    <div class="preview-nav">
        <div class="preview-meta-title">
            <span class="preview-badge-cod">
                <?= $limpiarUtf8($plantilla['codigo']) ?>
            </span>
            <strong class="preview-name-title"><?= $limpiarUtf8($plantilla['nombre']) ?></strong>
            <span class="preview-badge-ver">
                <?= $limpiarUtf8($plantilla['version_actual']) ?>
            </span>
            <span class="preview-badge-ori">
                <i class="fa-solid fa-file-<?= $esHorizontal ? 'invoice' : 'lines' ?>" style="margin-right:4px;"></i>
                <?= ucfirst($plantilla['orientacion']) ?>
            </span>
        </div>

        <div class="preview-controls-right">
            <!-- CONTROL DE ZOOM / AJUSTE DE PANTALLA -->
            <div class="preview-zoom-group">
                <span class="preview-zoom-label"><i class="fa-solid fa-magnifying-glass"></i> Zoom:</span>
                <button type="button" class="preview-zoom-btn active" id="btnZoom_fit" onclick="cambiarZoomPreview('fit')" title="Ajustar hoja completa sin desbordamiento">
                    <i class="fa-solid fa-expand" style="margin-right:2px;"></i> Ajustar Pantalla
                </button>
                <button type="button" class="preview-zoom-btn" id="btnZoom_85" onclick="cambiarZoomPreview('85')">
                    85%
                </button>
                <button type="button" class="preview-zoom-btn" id="btnZoom_100" onclick="cambiarZoomPreview('100')">
                    100%
                </button>
            </div>

            <!-- PESTAÑAS DE VISTA -->
            <div class="preview-tabs">
                <button type="button" class="preview-tab-btn active" onclick="cambiarPestanaPreview('tab-reporte')">
                    <i class="fa-solid fa-file-pdf text-danger" style="margin-right:4px;"></i> Informe Oficial Membretado
                </button>
                <button type="button" class="preview-tab-btn" onclick="cambiarPestanaPreview('tab-lims')">
                    <i class="fa-solid fa-flask text-primary" style="margin-right:4px;"></i> Captura LIMS
                </button>
                <button type="button" class="preview-tab-btn" onclick="cambiarPestanaPreview('tab-trazabilidad')">
                    <i class="fa-solid fa-fingerprint text-success" style="margin-right:4px;"></i> Trazabilidad ISO 17025
                </button>
            </div>
        </div>
    </div>

    <!-- CUERPO DE LA VISTA PREVIA -->
    <div class="preview-body-content" id="previewBodyContent">
        <!-- PESTAÑA 1: INFORME OFICIAL MEMBRETADO CON CALIBRACIÓN EXACTA -->
        <div id="tab-reporte" class="preview-tab-pane" style="width:100%;">
            <div class="preview-viewport-container" id="previewViewportContainer">
                <div class="preview-sheet-outer" id="previewSheetOuter">
                    <div class="preview-sheet" id="previewSheet">
                        
                        <!-- 1. SELLO DE ACREDITACIÓN (SI ESTÁ HABILITADO) -->
                        <?php if ($tieneSello): ?>
                            <div class="preview-sello-box">
                                <img src="/Cycsa/<?= $limpiarUtf8($selloUrl) ?>" alt="Sello Acreditación CYCSA">
                                <div class="preview-sello-txt">LABORATORIO ACREDITADO</div>
                            </div>
                        <?php endif; ?>

                        <!-- 2. ENCABEZADO INSTITUCIONAL A LA DERECHA DEL LOGO -->
                        <div class="sheet-cabecera">
                            <div class="cabecera-titulos">
                                <div class="cabecera-empresa">CONSULTORÍA Y CONSTRUCCIÓN S.A.</div>
                                <div class="cabecera-subtitulo">Laboratorio de Ensayos y Control de Calidad de Materiales y Suelos</div>
                                <div class="cabecera-informe-titulo">INFORME DE ENSAYO OFICIAL (<?= $limpiarUtf8($plantilla['codigo']) ?>)</div>
                            </div>
                            <div class="cabecera-badge-box">
                                <div class="badge-informe-num">INF-2026-XXXX</div>
                                <div class="badge-informe-ver">Versión: <?= $limpiarUtf8($plantilla['version_actual']) ?></div>
                            </div>
                        </div>

                        <!-- 3. ZONA DE CUERPO (METADATOS + TABLAS TÉCNICAS + FIRMAS) -->
                        <div class="preview-cuerpo">
                            
                            <!-- METADATOS TÉCNICOS EN 5 FILAS (CALIBRADOS ISO 17025) -->
                            <table class="tabla-metadatos-preview">
                                <tr>
                                    <td class="lbl-col" style="width: 14%;">INFORME DE ENSAYO:</td>
                                    <td class="val-col" style="width: 36%; font-weight:700;"><?= $limpiarUtf8($plantilla['nombre']) ?></td>
                                    <td class="lbl-col" style="width: 16%;">FECHA DE INGRESO:</td>
                                    <td class="val-col" style="width: 34%;"><?= date('d/m/Y') ?></td>
                                </tr>
                                <tr>
                                    <td class="lbl-col">MÉTODO DE MUESTREO:</td>
                                    <td class="val-col"><?= $limpiarUtf8($plantilla['norma'] ?: 'Norma ASTM Oficial') ?></td>
                                    <td class="lbl-col">FECHA DE MUESTREO:</td>
                                    <td class="val-col"><?= date('d/m/Y') ?></td>
                                </tr>
                                <tr>
                                    <td class="lbl-col">TIPO DE MUESTRA:</td>
                                    <td class="val-col">Especímenes / Muestras de Ensayo</td>
                                    <td class="lbl-col">FECHA DE EJECUCIÓN:</td>
                                    <td class="val-col"><?= date('d/m/Y') ?></td>
                                </tr>
                                <tr>
                                    <td class="lbl-col">NOMBRE CLIENTE:</td>
                                    <td class="val-col"><?= $limpiarUtf8($plantilla['cliente_nombre'] ?: 'Consorcio / Cliente Corporativo (Referencial)') ?></td>
                                    <td class="lbl-col">FECHA DE EMISIÓN:</td>
                                    <td class="val-col"><?= date('d/m/Y') ?></td>
                                </tr>
                                <tr>
                                    <td class="lbl-col">PROYECTO:</td>
                                    <td class="val-col"><?= $limpiarUtf8($plantilla['proyecto_nombre'] ?? 'Control de Calidad en Obra / Laboratorio') ?></td>
                                    <td class="lbl-col">UBICACIÓN:</td>
                                    <td class="val-col">Laboratorio Central CYCSA</td>
                                </tr>
                            </table>

                            <!-- MATRIZ TÉCNICA DINÁMICA -->
                            <div class="preview-matriz-area">
                                <?php 
                                $seccionesConCampos = array_filter($plantilla['secciones'] ?? [], function($s) {
                                    return !empty($s['campos']);
                                });
                                ?>

                                <?php if (!empty($seccionesConCampos)): ?>
                                    <?php foreach ($seccionesConCampos as $sec): ?>
                                        <div class="preview-sec-header">
                                            <i class="fa-solid fa-layer-group"></i> <?= $limpiarUtf8($sec['nombre']) ?>
                                        </div>
                                        <table class="preview-data-tbl">
                                            <thead>
                                                <tr>
                                                    <th style="width:24px;">#</th>
                                                    <th style="width:75px;">Código Lab</th>
                                                    <th style="width:120px;">Identificación Muestra</th>
                                                    <?php foreach ($sec['campos'] as $c): ?>
                                                        <?php if (!empty($c['visible_en_reporte'])): ?>
                                                            <th>
                                                                <div><?= $limpiarUtf8($c['variable_etiqueta']) ?></div>
                                                                <?php if (!empty($c['unidad_simbolo'])): ?>
                                                                    <div style="font-weight:normal; font-size:6.8px; color:#475569;">(<?= $limpiarUtf8($c['unidad_simbolo']) ?>)</div>
                                                                <?php endif; ?>
                                                            </th>
                                                        <?php endif; ?>
                                                    <?php endforeach; ?>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <?php 
                                                $ejemplosMuestras = [
                                                    ['cod' => 'CYC-01', 'id' => 'Espécimen 1 (Eje A-1)'],
                                                    ['cod' => 'CYC-02', 'id' => 'Espécimen 2 (Eje A-2)'],
                                                    ['cod' => 'CYC-03', 'id' => 'Espécimen 3 (Eje B-1)']
                                                ];
                                                ?>
                                                <?php foreach ($ejemplosMuestras as $idxM => $m): ?>
                                                    <tr>
                                                        <td style="text-align:center; font-weight:700;"><?= $idxM + 1 ?></td>
                                                        <td style="text-align:center; font-family:monospace; font-weight:700;"><?= $m['cod'] ?></td>
                                                        <td><?= $m['id'] ?></td>
                                                        <?php foreach ($sec['campos'] as $c): ?>
                                                            <?php if (!empty($c['visible_en_reporte'])): ?>
                                                                <td style="text-align:center;">
                                                                    <?php if (!empty($c['formula'])): ?>
                                                                        <span style="background:#ecfdf5; color:#047857; border:1px solid #a7f3d0; padding:1px 4px; border-radius:2px; font-size:7px; font-weight:700;" title="Fórmula: <?= $limpiarUtf8($c['formula']) ?>">
                                                                            f(x)
                                                                        </span>
                                                                    <?php else: ?>
                                                                        <span style="font-family:monospace; color:#334155; font-size:7.5px;">
                                                                            <?= number_format(124.50 + ($idxM * 12.8), (int)($c['decimales'] ?? 2)) ?>
                                                                        </span>
                                                                    <?php endif; ?>
                                                                </td>
                                                            <?php endif; ?>
                                                        <?php endforeach; ?>
                                                    </tr>
                                                <?php endforeach; ?>
                                            </tbody>
                                        </table>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <!-- TABLA PRELIMINAR REALISTA CUANDO NO SE HAN DEFINIDO CAMPOS AÚN -->
                                    <div class="preview-sec-header">
                                        <i class="fa-solid fa-list-check"></i> Parámetros y Lecturas de Ensayo Oficial
                                    </div>
                                    <table class="preview-data-tbl">
                                        <thead>
                                            <tr>
                                                <th style="width:24px;">#</th>
                                                <th style="width:80px;">Código Lab</th>
                                                <th style="width:130px;">Identificación Espécimen</th>
                                                <th>Carga Aplicada (lb)</th>
                                                <th>Área Transversal (in²)</th>
                                                <th>Resistencia Calculada (lb/in²)</th>
                                                <th>Resultado Oficial (kg/cm²)</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <tr>
                                                <td style="text-align:center; font-weight:700;">1</td>
                                                <td style="text-align:center; font-family:monospace; font-weight:700;">CYC-2026-001</td>
                                                <td>Cilindro C-1 · Losa Nivel 2</td>
                                                <td style="text-align:right;">45,250.00</td>
                                                <td style="text-align:right;">12.57</td>
                                                <td style="text-align:right; font-weight:700;">3,600.00</td>
                                                <td style="text-align:right; font-weight:700; color:#103487;">253.10</td>
                                            </tr>
                                            <tr>
                                                <td style="text-align:center; font-weight:700;">2</td>
                                                <td style="text-align:center; font-family:monospace; font-weight:700;">CYC-2026-002</td>
                                                <td>Cilindro C-2 · Losa Nivel 2</td>
                                                <td style="text-align:right;">46,120.00</td>
                                                <td style="text-align:right;">12.57</td>
                                                <td style="text-align:right; font-weight:700;">3,669.00</td>
                                                <td style="text-align:right; font-weight:700; color:#103487;">257.95</td>
                                            </tr>
                                            <tr>
                                                <td style="text-align:center; font-weight:700;">3</td>
                                                <td style="text-align:center; font-family:monospace; font-weight:700;">CYC-2026-003</td>
                                                <td>Cilindro C-3 · Losa Nivel 2</td>
                                                <td style="text-align:right;">45,880.00</td>
                                                <td style="text-align:right;">12.57</td>
                                                <td style="text-align:right; font-weight:700;">3,650.00</td>
                                                <td style="text-align:right; font-weight:700; color:#103487;">256.62</td>
                                            </tr>
                                        </tbody>
                                    </table>
                                    <div style="font-size:7px; color:#64748b; font-style:italic; margin-top:2px;">
                                        * Datos preliminares de simulación. Agregue o modifique variables en las secciones del lienzo para personalizar las columnas de cálculo de esta matriz técnica.
                                    </div>
                                <?php endif; ?>
                            </div>

                            <!-- PIE DE INFORME: DECLARACIÓN Y FIRMAS OFICIALES -->
                            <div class="preview-pie-firmas">
                                <div class="preview-disclaimer">
                                    Consultoría y Construcción SA. CYCSA es responsable únicamente de la exactitud de los resultados realizados en las muestras recibidas y analizadas. No debe reproducirse parcialmente sin autorización escrita de CYCSA. ** Información proporcionada por el cliente y está fuera del alcance de la acreditación.
                                </div>
                                <div class="preview-firmas-grid">
                                    <div class="preview-firma-box">
                                        <div class="preview-firma-linea"></div>
                                        <div class="preview-firma-nombre">Técnico Analista Responsable</div>
                                        <div class="preview-firma-cargo">Laboratorio de Ensayos CYCSA</div>
                                    </div>
                                    <div class="preview-firma-box">
                                        <div class="preview-firma-linea"></div>
                                        <div class="preview-firma-nombre">Ing. Noel Quintana Lira</div>
                                        <div class="preview-firma-cargo">Gerencia General / Control de Calidad</div>
                                    </div>
                                </div>
                            </div>

                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PESTAÑA 2: VISTA FORMULARIO LIMS -->
        <div id="tab-lims" class="preview-tab-pane" style="display:none; width:100%;">
            <div style="max-width:900px; margin:0 auto; background:#ffffff; padding:24px; border-radius:6px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
                <h4 style="margin:0 0 16px 0; font-size:14px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-keyboard text-primary"></i> Captura de Datos Crudos en Laboratorio (Simulación LIMS)
                </h4>
                <?php foreach (($plantilla['secciones'] ?? []) as $sec): ?>
                    <div style="border:1px solid #e2e8f0; border-radius:6px; margin-bottom:14px; overflow:hidden;">
                        <div style="background:#f8fafc; padding:8px 14px; font-weight:700; font-size:12px; color:#334155; border-bottom:1px solid #e2e8f0;">
                            <?= $limpiarUtf8($sec['nombre']) ?>
                        </div>
                        <div style="padding:14px; display:grid; grid-template-columns:repeat(auto-fit, minmax(220px, 1fr)); gap:12px;">
                            <?php foreach (($sec['campos'] ?? []) as $c): ?>
                                <div>
                                    <label style="display:block; font-size:11px; font-weight:700; color:#475569; margin-bottom:3px;">
                                        <?= $limpiarUtf8($c['variable_etiqueta']) ?>
                                        <?php if (!empty($c['unidad_simbolo'])): ?>
                                            <span style="color:#64748b; font-weight:normal;">[<?= $limpiarUtf8($c['unidad_simbolo']) ?>]</span>
                                        <?php endif; ?>
                                    </label>
                                    <?php if (!empty($c['formula'])): ?>
                                        <input type="text" style="width:100%; padding:6px 10px; border:1px solid #a7f3d0; background:#ecfdf5; font-family:monospace; color:#047857; font-weight:700; font-size:12px; border-radius:4px; box-sizing:border-box;" readonly placeholder="f(x): <?= $limpiarUtf8($c['formula']) ?>">
                                    <?php else: ?>
                                        <input type="text" style="width:100%; padding:6px 10px; border:1px solid #cbd5e1; border-radius:4px; font-size:12px; box-sizing:border-box;" placeholder="Valor crudo...">
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- PESTAÑA 3: TRAZABILIDAD Y HASH SHA-256 -->
        <div id="tab-trazabilidad" class="preview-tab-pane" style="display:none; width:100%;">
            <div style="max-width:900px; margin:0 auto; background:#ffffff; padding:24px; border-radius:6px; box-shadow:0 1px 4px rgba(0,0,0,0.06);">
                <h4 style="margin:0 0 16px 0; font-size:14px; font-weight:700; color:#0f172a; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-shield-halved text-success"></i> Trazabilidad Criptográfica de Versiones (ISO/IEC 17025)
                </h4>
                <table style="width:100%; border-collapse:collapse; font-size:12px; border:1px solid #e2e8f0;">
                    <thead>
                        <tr style="background:#f8fafc; color:#475569; font-size:11px; text-transform:uppercase;">
                            <th style="padding:10px 12px; border-bottom:2px solid #e2e8f0; text-align:left;">Versión</th>
                            <th style="padding:10px 12px; border-bottom:2px solid #e2e8f0; text-align:left;">Fecha Aprobación</th>
                            <th style="padding:10px 12px; border-bottom:2px solid #e2e8f0; text-align:left;">Aprobado Por</th>
                            <th style="padding:10px 12px; border-bottom:2px solid #e2e8f0; text-align:left;">Hash SHA-256</th>
                            <th style="padding:10px 12px; border-bottom:2px solid #e2e8f0; text-align:left;">Motivo</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (empty($plantilla['versiones'])): ?>
                            <tr>
                                <td colspan="5" style="text-align:center; padding:24px; color:#64748b;">
                                    No existen versiones inmutables congeladas aún para esta plantilla.
                                </td>
                            </tr>
                        <?php else: ?>
                            <?php foreach ($plantilla['versiones'] as $v): ?>
                                <tr>
                                    <td style="padding:10px 12px; border-bottom:1px solid #f1f5f9; font-family:monospace; font-weight:700; color:#103487;"><?= $limpiarUtf8($v['version']) ?></td>
                                    <td style="padding:10px 12px; border-bottom:1px solid #f1f5f9; color:#475569;"><?= $limpiarUtf8($v['fecha_aprobacion']) ?></td>
                                    <td style="padding:10px 12px; border-bottom:1px solid #f1f5f9;"><?= $limpiarUtf8($v['aprobado_por_nombre'] ?: 'Gerente de Calidad') ?></td>
                                    <td style="padding:10px 12px; border-bottom:1px solid #f1f5f9;">
                                        <code style="font-size:10.5px; color:#047857; background:#ecfdf5; padding:2px 6px; border-radius:3px; word-break:break-all;"><?= $limpiarUtf8($v['hash_integridad']) ?></code>
                                    </td>
                                    <td style="padding:10px 12px; border-bottom:1px solid #f1f5f9; color:#64748b;"><?= $limpiarUtf8($v['motivo_version'] ?: 'Versión Oficial') ?></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
let currentPreviewZoom = 'fit';

function aplicarEscalaPreview() {
    const sheet = document.getElementById('previewSheet');
    const container = document.getElementById('previewViewportContainer');
    const outer = document.getElementById('previewSheetOuter');
    if (!sheet || !container || !outer) return;

    const sheetW = sheet.offsetWidth || <?= $esHorizontal ? 980 : 720 ?>;
    const sheetH = sheet.offsetHeight || <?= $esHorizontal ? 757 : 932 ?>;
    const contW = container.clientWidth - 40;
    const contH = container.clientHeight - 20;

    let scale = 1;
    if (currentPreviewZoom === 'fit') {
        const scaleW = contW > 200 ? contW / sheetW : 1;
        const scaleH = contH > 200 ? contH / sheetH : 1;
        scale = Math.min(scaleW, scaleH, 1.0);
        if (scale < 0.45) scale = 0.45;
    } else if (currentPreviewZoom === '85') {
        scale = 0.85;
    } else if (currentPreviewZoom === '100') {
        scale = 1.0;
    }

    sheet.style.transform = `scale(${scale})`;
    sheet.style.transformOrigin = 'top center';
    outer.style.height = `${sheetH * scale + 24}px`;
    outer.style.width = '100%';
}

function cambiarZoomPreview(mode) {
    currentPreviewZoom = mode;
    document.querySelectorAll('.preview-zoom-btn').forEach(btn => btn.classList.remove('active'));
    const btn = document.getElementById('btnZoom_' + mode);
    if (btn) btn.classList.add('active');
    aplicarEscalaPreview();
}

function cambiarPestanaPreview(tabId) {
    document.querySelectorAll('.preview-tab-pane').forEach(el => el.style.display = 'none');
    document.querySelectorAll('.preview-tab-btn').forEach(el => el.classList.remove('active'));

    const targetPane = document.getElementById(tabId);
    if (targetPane) targetPane.style.display = 'block';

    const evtBtn = event.currentTarget;
    if (evtBtn) evtBtn.classList.add('active');

    if (tabId === 'tab-reporte') {
        setTimeout(aplicarEscalaPreview, 30);
    }
}

window.addEventListener('resize', () => {
    if (currentPreviewZoom === 'fit') {
        aplicarEscalaPreview();
    }
});

// Inicialización de auto-escala inmediata
setTimeout(aplicarEscalaPreview, 50);
</script>
