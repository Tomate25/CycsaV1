<?php
// Vista Imprimible Oficial de Matriz de Ensayo / Cálculo con Membrete Horizontal CYCSA (Estilo Sencillo y Sobrio con Espacio Amplio para Firmas)
$archivoMd = $detalle['archivo_markdown'] ?? '';
$schemaInfo = $schemaInfo ?? obtenerEsquemaPlantillaEnsayo($archivoMd, isset($detalle['formato_id']) ? (int)$detalle['formato_id'] : null);
$listaVersiones = $listaVersiones ?? [];
$infoVersionImpresion = $infoVersionImpresion ?? ['version' => 1, 'es_actual' => true, 'motivo' => '', 'fecha' => '', 'usuario' => ''];
$versionActual = $versionActual ?? 1;
$columnas = $schemaInfo['columns'] ?? ($columnas ?? []);
$tituloInforme = $schemaInfo['titulo_informe'] ?? 'INFORME DE ENSAYO';
$subtituloLaboratorio = $schemaInfo['subtitulo_laboratorio'] ?? 'Laboratorio de Ensayos y Control de Calidad';
$firmanteNombre = $schemaInfo['firmante_nombre'] ?? 'Ing. Noel Quintana Lira';
$firmanteCargo = $schemaInfo['firmante_cargo'] ?? 'Gerente General';
$normaOficial = $schemaInfo['norma'] ?? ($detalle['norma_astm'] ?? '');

$codigoFormatoOficial = !empty($schemaInfo['codigo_formato']) ? $schemaInfo['codigo_formato'] : (!empty($detalle['codigo_documento']) ? $detalle['codigo_documento'] : 'CYCSA-RT-FM-22');
$ensayoTituloOficial = !empty($schemaInfo['ensayo_titulo']) ? $schemaInfo['ensayo_titulo'] : $detalle['descripcion_ensayo'];
$metodoMuestreoOficial = !empty($schemaInfo['metodo_muestreo']) ? $schemaInfo['metodo_muestreo'] : (!empty($detalle['norma_astm']) ? $detalle['norma_astm'] : 'Norma ASTM / AASHTO Oficial');
$tipoMuestraOficial = !empty($schemaInfo['tipo_muestra']) ? $schemaInfo['tipo_muestra'] : 'Especímenes / Muestras';
$colMethods = $schemaInfo['column_methods'] ?? [];

$disclaimerOficial = !empty($schemaInfo['disclaimer']) ? $schemaInfo['disclaimer'] : 'Consultoría y Construcción SA.CYCSA es responsable únicamente de la exactitud de los resultados realizados en las muestras recibidas y tomadas en campo. No se debe de reproducir este informe de ensayo sin la aprobación formal de Consultoría y Construcción SA. CYCSA. ** Información Proporcionada por el cliente y está fuera del alcance de la acreditación.';
$notasOficiales = !empty($schemaInfo['notas']) && is_array($schemaInfo['notas']) ? $schemaInfo['notas'] : [];

if (!array_key_exists('notas', $schemaInfo) && !empty($archivoMd)) {
    $rutaMdFallback = dirname(__DIR__, 4) . '/database/ensayos/' . $archivoMd;
    if (!file_exists($rutaMdFallback)) {
        $archSin = strtr(utf8_decode($archivoMd), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
        $rutaMdFallback = dirname(__DIR__, 4) . '/database/ensayos/' . $archSin;
    }
    if (file_exists($rutaMdFallback)) {
        $mdLines = file($rutaMdFallback, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        $collectingFallback = false;
        foreach ($mdLines as $lineaMd) {
            $lineaMd = trim($lineaMd);
            if (stripos($lineaMd, 'Consultoría y Construcción SA') !== false && stripos($lineaMd, 'es responsable únicamente') !== false) {
                $disclaimerOficial = $lineaMd;
                $collectingFallback = true;
                continue;
            }
            if ($collectingFallback) {
                if (preg_match('/^[-#]{2,}|Última Línea|Ing\.|Página/i', $lineaMd)) {
                    break;
                }
                if (!empty($lineaMd)) {
                    $partsFallback = preg_split('/(?<=[^\s])\s+(?=Nota(?:\s*\d+)?\s*:)/iu', $lineaMd);
                    foreach ($partsFallback as $pf) {
                        $pf = trim(preg_replace('/[-#]+$/', '', trim($pf)));
                        if (!empty($pf)) {
                            $notasOficiales[] = $pf;
                        }
                    }
                }
            }
        }
    }
}

$resultados = [];
$metadatosGuardados = [];
if (!empty($detalle['resultados_json'])) {
    $decoded = json_decode($detalle['resultados_json'], true) ?: [];
    if (isset($decoded['filas'])) {
        $resultados = $decoded['filas'];
        $metadatosGuardados = $decoded['metadatos'] ?? [];
    } else {
        $resultados = $decoded;
    }
}

if (empty($metadatos)) {
    $metadatos = resolverMetadatosEnsayo($detalle, $schemaInfo, $metadatosGuardados);
}

$codigoFormatoOficial = $metadatos['codigo_formato'];
$ensayoTituloOficial = $metadatos['ensayo_realizado'];
$metodoMuestreoOficial = $metadatos['metodo_muestreo'];
$tipoMuestraOficial = $metadatos['tipo_muestra'];
$clienteNom = $metadatos['cliente_nombre'];
$clienteDir = $metadatos['cliente_direccion'];
$proyNom = $metadatos['proyecto'];
$fechaIngreso = $metadatos['fecha_ingreso'];
$fechaMuestreo = $metadatos['fecha_muestreo'];
$fechaEjecucion = $metadatos['fecha_ejecucion'];
$fechaEmision = $metadatos['fecha_emision'];
$muestraTomadaPor = $metadatos['muestra_tomada_por'];
$procedimientoMuestreo = $metadatos['procedimiento_muestreo'];
$ubicacion = $metadatos['ubicacion'];
$ensayoRealizado = $metadatos['ensayo_realizado'];
$metodoMuestreo = $metadatos['metodo_muestreo'];

// Si está vacío, cargar las muestras seteadas
if (empty($resultados) && !empty($muestrasSeteadas)) {
    foreach ($muestrasSeteadas as $ms) {
        $row = [];
        foreach ($columnas as $col) {
            if ($col === 'Código laboratorio') $row[$col] = $ms['codigo_lab'] ?? '';
            elseif ($col === 'Nombre muestra') $row[$col] = $ms['nombre_muestra'] ?? '';
            else $row[$col] = '';
        }
        $resultados[] = $row;
    }
}

$datosParaCodigo = !empty($resultados) ? $resultados : (!empty($muestrasSeteadas) ? $muestrasSeteadas : ($detalle['resultados_json'] ?? []));
$codigoInformeConsecutivo = generarCodigoInformeEnsayo($datosParaCodigo, $metadatos['fecha_muestreo'] ?? ($metadatos['fecha_ingreso'] ?? null), $metadatos['tipo_muestra'] ?? ($detalle['descripcion_ensayo'] ?? ''));
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title><?= htmlspecialchars($codigoFormatoOficial . ' - ' . $detalle['codigo_os']) ?> | Matriz de Ensayo</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        @page {
            size: letter landscape;
            margin: 0;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            margin: 0;
            padding: 0;
            font-family: Arial, Helvetica, sans-serif;
            background-color: #1e293b;
            color: #000000;
            -webkit-font-smoothing: antialiased;
        }

        /* Barra Flotante de Control (Solo Pantalla) */
        .barra-pantalla {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            background: rgba(15, 23, 42, 0.97);
            backdrop-filter: blur(10px);
            padding: 10px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            z-index: 9999;
            box-shadow: 0 4px 20px rgba(0,0,0,0.35);
            color: white;
        }

        .btn-accion-top {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            padding: 8px 18px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: 700;
            text-decoration: none;
            cursor: pointer;
            transition: all 0.2s;
            border: none;
        }

        .btn-imprimir {
            background: #103487;
            color: white;
            box-shadow: 0 2px 8px rgba(16,52,135,0.4);
        }
        .btn-imprimir:hover {
            background: #1d4ed8;
            transform: translateY(-1px);
        }

        .btn-volver {
            background: #475569;
            color: #f1f5f9;
        }
        .btn-volver:hover {
            background: #64748b;
            color: white;
        }

        /* Contenedor Principal de la Hoja Membretada (Letter Landscape Exacto: 279.4mm x 215.9mm) */
        .pagina-hoja {
            width: 279.4mm;
            height: 215.9mm;
            max-height: 215.9mm;
            margin: 60px auto 40px auto;
            background-color: #ffffff;
            background-image: url('/Cycsa/publico/img/hoja_membretada_horizontal.jpg');
            background-size: 100% 100%;
            background-position: center;
            background-repeat: no-repeat;
            position: relative;
            box-shadow: 0 10px 35px rgba(0,0,0,0.4);
            overflow: hidden;
            box-sizing: border-box;
        }

        /* 1. ZONA SUPERIOR: Encabezado institucional situado a la DERECHA del logotipo */
        .zona-cabecera {
            position: absolute;
            top: 8mm;
            left: <?= !empty($metadatos['logo_acreditacion']) ? '83mm' : '56mm' ?>; /* Ajuste automático si existe logo de acreditación */
            right: 14mm;
            height: 28mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-sizing: border-box;
        }

        .titulo-central {
            text-align: center;
            flex-grow: 1;
            padding: 0 10px;
        }

        .titulo-empresa {
            font-size: 14px;
            font-weight: bold;
            color: #000000;
            letter-spacing: 0.5px;
            margin: 0 0 3px 0;
            text-transform: uppercase;
        }

        .subtitulo-doc {
            font-size: 11px;
            font-weight: bold;
            color: #333333;
            margin: 0;
            text-transform: uppercase;
        }

        .insignia-codigo-doc {
            text-align: right;
            min-width: 48mm;
        }

        .badge-doc-oficial {
            background: #ffffff;
            color: #000000;
            border: 1.5px solid #000000;
            font-weight: bold;
            font-size: 11px;
            padding: 3px 8px;
            border-radius: 2px;
            display: inline-block;
            font-family: monospace;
        }

        .version-doc {
            font-size: 8.5px;
            color: #333333;
            font-weight: bold;
            margin-top: 3px;
        }

        /* 2. ZONA DE CONTENIDO: Inicia DEBAJO del logo (Top: 40mm) y termina ARRIBA del pie de página (Bottom: 32mm) */
        .zona-cuerpo {
            position: absolute;
            top: 40mm;
            left: 14mm;
            right: 14mm;
            bottom: 32mm;
            display: flex;
            flex-direction: column;
            justify-content: flex-start;
            box-sizing: border-box;
        }

        /* Metadata del Informe de Ensayo Oficial (Captura 144015) */
        .tabla-metadatos-informe {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            background: transparent;
            margin-bottom: 4px;
        }

        .tabla-metadatos-informe td {
            padding: 1.5px 3px;
            border: none;
            vertical-align: top;
            line-height: 1.25;
            color: #000000;
        }

        .lbl-informe {
            font-weight: bold;
            color: #000000;
            white-space: nowrap;
        }

        .val-informe {
            color: #000000;
        }

        /* Tabla de Matriz Técnica de Datos */
        .contenedor-tabla-matriz {
            width: 100%;
            margin-bottom: 3px;
            background: #ffffff;
            flex-grow: 1;
        }

        .tabla-matriz-print {
            width: 100%;
            border-collapse: collapse;
            font-size: 8px;
            border: 1px solid #000000;
        }

        .tabla-matriz-print th {
            background: #f3f4f6;
            color: #000000;
            font-weight: bold;
            text-align: center;
            padding: 3px 2px;
            border: 1px solid #000000;
            vertical-align: middle;
            line-height: 1.15;
        }

        .tabla-matriz-print th .metodo-sub {
            display: block;
            font-size: 6.8px;
            font-weight: normal;
            color: #555555;
            margin-top: 1px;
            font-family: monospace;
        }

        .tabla-matriz-print td {
            border: 1px solid #000000;
            padding: 2.5px 3px;
            text-align: center;
            vertical-align: middle;
            color: #000000;
            font-size: 8px;
            background: #ffffff;
        }

        .td-left {
            text-align: left !important;
        }

        .td-code {
            font-family: monospace;
            font-weight: bold;
            color: #000000;
        }

        .td-num {
            text-align: right !important;
            font-family: monospace;
        }

        /* Disclaimer y Notas Oficiales del Formato (ISO 17025) */
        .bloque-normativo-formato {
            margin-top: 3px;
            margin-bottom: 3px;
            font-size: 7.2px;
            line-height: 1.25;
            color: #000000;
        }

        .disclaimer-formato {
            text-align: justify;
            font-style: italic;
            color: #222222;
            margin-bottom: 2px;
        }

        .notas-formato {
            font-weight: normal;
            color: #000000;
        }

        .nota-item {
            margin-bottom: 1.5px;
        }

        /* Observaciones y Declaración ISO 17025 */
        .seccion-observaciones {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-top: 3px;
            margin-bottom: 3px;
            font-size: 7.2px;
        }

        .caja-obs {
            border: 1px solid #000000;
            background: #ffffff;
            padding: 3px 6px;
            min-height: auto;
        }

        .caja-obs-titulo {
            font-weight: bold;
            color: #000000;
            margin-bottom: 2px;
            text-transform: uppercase;
            font-size: 7px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 2px;
        }

        .linea-cierre-doc {
            text-align: center;
            font-size: 7.5px;
            color: #333333;
            margin: 3px 0 3px 0;
            letter-spacing: 0.5px;
        }

        /* Bloque de Firmas Técnicas Calibrado sin solapamiento */
        .bloque-firmas {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 20px;
            margin-top: 2px;
        }

        .columna-firma {
            display: flex;
            flex-direction: column;
            text-align: center;
        }

        .espacio-para-firma {
            height: 16px; /* Espacio sobrio para firma/sello sin desbordar */
            background: transparent;
        }

        .linea-firma {
            border-top: 1px solid #000000;
            padding-top: 2px;
        }

        .firma-nombre {
            font-weight: bold;
            color: #000000;
            font-size: 8px;
        }

        .firma-cargo {
            color: #444444;
            font-size: 7px;
            text-transform: uppercase;
            margin-top: 1px;
        }

        /* Estilos de Impresión */
        @media print {
            .barra-pantalla {
                display: none !important;
            }
            body {
                background: white !important;
            }
            .pagina-hoja {
                margin: 0 !important;
                box-shadow: none !important;
                width: 279.4mm !important;
                height: 215.9mm !important;
                max-height: 215.9mm !important;
                page-break-inside: avoid !important;
                page-break-after: avoid !important;
            }
        }
    </style>
</head>
<body>

    <!-- BARRA FLOTANTE EN PANTALLA -->
    <div class="barra-pantalla">
        <div style="display: flex; align-items: center; gap: 12px;">
            <span style="font-weight: bold; font-size: 14.5px; color: #60a5fa; display: inline-flex; align-items: center; gap: 8px;">
                <i class="fa-solid fa-file-invoice"></i> Registro Oficial de Ensayo
            </span>
            <span style="background: #1e293b; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-family: monospace; color: #cbd5e1; border: 1px solid #334155;">
                <?= htmlspecialchars($codigoFormatoOficial) ?> &bull; O/S: <?= htmlspecialchars($detalle['codigo_os']) ?>
            </span>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <?php if (!empty($listaVersiones)): ?>
                <div style="display: flex; gap: 6px; align-items: center; background: #0f172a; padding: 4px 10px; border-radius: 6px; border: 1px solid #334155;">
                    <span style="font-size: 11px; color: #94a3b8; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; display: inline-flex; align-items: center; gap: 4px;">
                        <i class="fa-solid fa-code-branch" style="color: #60a5fa;"></i> Revisiones:
                    </span>
                    <?php foreach ($listaVersiones as $lv): ?>
                        <?php 
                        $lvNum = (int)($lv['version'] ?? 1); 
                        $esAct = (($infoVersionImpresion['version'] ?? 1) === $lvNum && empty($infoVersionImpresion['es_actual'])); 
                        $fmtLvNum = sprintf('%02d', $lvNum);
                        ?>
                        <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= $detalle['id'] ?>&version=<?= $lvNum ?>" 
                           style="padding: 3px 8px; font-size: 11px; border-radius: 4px; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 4px; border: 1px solid <?= $esAct ? '#ef4444' : '#475569' ?>; color: <?= $esAct ? '#fee2e2' : '#cbd5e1' ?>; background: <?= $esAct ? '#b91c1c' : '#1e293b' ?>;">
                            <i class="fa-solid fa-clock-rotate-left" style="font-size: 9px;"></i> Rev. <?= $fmtLvNum ?> (Dev)
                        </a>
                    <?php endforeach; ?>
                    <?php 
                    $esActv = !empty($infoVersionImpresion['es_actual']); 
                    $fmtVAct = sprintf('%02d', (int)($versionActual ?? 1));
                    ?>
                    <a href="/Cycsa/publico/operaciones/imprimir-matriz?id_detalle=<?= $detalle['id'] ?>" 
                       style="padding: 3px 10px; font-size: 11px; border-radius: 4px; text-decoration: none; font-weight: 700; display: inline-flex; align-items: center; gap: 5px; border: 1px solid <?= $esActv ? '#3b82f6' : '#475569' ?>; color: <?= $esActv ? '#ffffff' : '#cbd5e1' ?>; background: <?= $esActv ? '#1d4ed8' : '#1e293b' ?>;">
                        <i class="fa-solid fa-circle-check" style="font-size: 10px; color: <?= $esActv ? '#93c5fd' : '#94a3b8' ?>;"></i> Rev. <?= $fmtVAct ?> (Vigente)
                    </a>
                </div>
            <?php endif; ?>

            <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $detalle['id'] ?><?= !empty($infoVersionImpresion['version']) && empty($infoVersionImpresion['es_actual']) ? '&version=' . $infoVersionImpresion['version'] : '' ?>" class="btn-accion-top btn-volver">
                <i class="fa-solid fa-pen-to-square"></i> Volver a Matriz
            </a>
            <a href="/Cycsa/publico/operaciones/descargar-matriz-pdf?id_detalle=<?= $detalle['id'] ?><?= !empty($infoVersionImpresion['version']) ? '&version=' . $infoVersionImpresion['version'] : '' ?>" target="_blank" class="btn-accion-top" style="background: #334155; color: white;">
                <i class="fa-solid fa-file-pdf"></i> Descargar PDF
            </a>
            <button onclick="window.print()" class="btn-accion-top btn-imprimir">
                <i class="fa-solid fa-print"></i> Imprimir Matriz
            </button>
            <?php if (!empty($resultados)): ?>
                <button type="button" onclick="abrirModalEnviarPrint()" class="btn-accion-top" style="background: #059669; color: white; box-shadow: 0 2px 8px rgba(5,150,105,0.4);">
                    <i class="fa-solid fa-paper-plane"></i> Enviar al Cliente
                </button>
            <?php endif; ?>
        </div>
    </div>

    <!-- PÁGINA IMPRIMIBLE CON MEMBRETE AJUSTADA -->
    <div class="pagina-hoja">
        
        <?php if (!empty($metadatos['logo_acreditacion'])): ?>
            <!-- Sello / Logotipo Oficial de Acreditación (a la par del logotipo CYCSA) -->
            <div class="logo-acreditacion-print" style="position: absolute; top: 6mm; left: 47mm; height: 26mm; width: 34mm; display: flex; align-items: center; justify-content: center; z-index: 10;">
                <img src="<?= htmlspecialchars($metadatos['logo_acreditacion']) ?>" alt="Acreditación" style="max-height: 24mm; max-width: 34mm; object-fit: contain;">
            </div>
        <?php endif; ?>

        <!-- 1. ZONA SUPERIOR: Encabezado a la derecha del logo -->
        <div class="zona-cabecera">
            <div class="titulo-central">
                <h1 class="titulo-empresa" style="font-size: 16px; font-weight: 800; letter-spacing: 0.8px; margin: 0;"><?= htmlspecialchars($tituloInforme) ?></h1>
                <div class="codigo-informe-consecutivo" style="font-family: 'Consolas', 'Courier New', monospace; font-size: 13px; font-weight: 800; color: #103487; letter-spacing: 0.8px; margin-top: 2px; margin-bottom: 2px;">
                    <?= htmlspecialchars($codigoInformeConsecutivo) ?>
                </div>
                <div class="subtitulo-doc" style="font-size: 9px; font-weight: 600; color: #475569; margin-top: 1px;"><?= htmlspecialchars($subtituloLaboratorio) ?> &bull; Orden de Servicio: <strong style="color: #103487; font-family: monospace;"><?= htmlspecialchars($detalle['codigo_os']) ?></strong></div>
                <div class="subtitulo-doc" style="font-size: 8px;">Norma técnica: <?= htmlspecialchars($normaOficial) ?></div>
            </div>
            <div class="insignia-codigo-doc">
                <div class="badge-doc-oficial"><?= htmlspecialchars($codigoFormatoOficial) ?></div>
                <div class="version-doc">ISO/IEC 17025:2017</div>
                <div style="font-size: 8px; font-weight: 800; color: <?= !empty($infoVersionImpresion['es_actual']) ? '#103487' : '#b91c1c' ?>; margin-top: 2px;">
                    Versión: <?= sprintf("%02d", $infoVersionImpresion['version'] ?? 1) ?><?= !empty($infoVersionImpresion['es_actual']) ? ' (Actual)' : ' (Histórica Devuelta)' ?>
                </div>
            </div>
        </div>

        <!-- 2. ZONA DE CUERPO: Inicia debajo del logo y termina arriba del pie de página -->
        <div class="zona-cuerpo">
            
            <div>
                <?php if (!empty($infoVersionImpresion['motivo_devolucion'])): ?>
                    <div style="background: #fff1f2; border: 1.5px solid #fecaca; border-radius: 4px; padding: 4px 8px; margin-bottom: 6px; font-size: 8px; color: #991b1b; line-height: 1.3;">
                        <strong>⚠️ REGISTRO HISTÓRICO OBSERVADO / DEVUELTO (<?= htmlspecialchars($infoVersionImpresion['usuario_revisor'] ?? 'Supervisor') ?>):</strong> <?= htmlspecialchars($infoVersionImpresion['motivo_devolucion']) ?>
                    </div>
                <?php endif; ?>

                <!-- METADATOS OFICIALES 2 COLUMNAS (Captura de pantalla 2026-09-17 144015.png) -->
                <table class="tabla-metadatos-informe">
                    <tr>
                        <td class="lbl-informe" style="width: 18%;">** Nombre del cliente:</td>
                        <td class="val-informe" style="width: 32%;"><?= htmlspecialchars($clienteNom) ?></td>
                        <td class="lbl-informe" style="width: 16%;">** Proyecto:</td>
                        <td class="val-informe" style="width: 34%;"><?= htmlspecialchars($proyNom) ?></td>
                    </tr>
                    <tr>
                        <td class="lbl-informe">** Dirección:</td>
                        <td class="val-informe"><?= htmlspecialchars($clienteDir) ?></td>
                        <td class="lbl-informe">** Fecha muestreo:</td>
                        <td class="val-informe"><?= htmlspecialchars($fechaMuestreo) ?></td>
                    </tr>
                    <tr>
                        <td class="lbl-informe">Fecha de ingreso:</td>
                        <td class="val-informe"><?= htmlspecialchars($fechaIngreso) ?></td>
                        <td class="lbl-informe">Fecha de ejecución:</td>
                        <td class="val-informe"><?= htmlspecialchars($fechaEjecucion) ?></td>
                    </tr>
                    <tr>
                        <td class="lbl-informe">Tipo de muestra:</td>
                        <td class="val-informe"><?= htmlspecialchars($tipoMuestraOficial) ?></td>
                        <td class="lbl-informe">Fecha de emisión:</td>
                        <td class="val-informe"><?= htmlspecialchars($fechaEmision) ?></td>
                    </tr>
                    <tr>
                        <td class="lbl-informe">** Procedimiento de muestreo:</td>
                        <td class="val-informe"><?= htmlspecialchars($procedimientoMuestreo) ?></td>
                        <td class="lbl-informe">Muestra tomada por:</td>
                        <td class="val-informe"><?= htmlspecialchars($muestraTomadaPor) ?></td>
                    </tr>
                    <tr>
                        <td class="lbl-informe" style="vertical-align: top;">Ensayo realizado:</td>
                        <td class="val-informe" style="vertical-align: top; line-height: 1.25;"><?= nl2br(htmlspecialchars($ensayoRealizado)) ?></td>
                        <td class="lbl-informe" style="vertical-align: top;">
                            <div>** Ubicación:</div>
                            <div style="margin-top: 10px;">Método de muestreo:</div>
                        </td>
                        <td class="val-informe" style="vertical-align: top;">
                            <div><?= htmlspecialchars($ubicacion) ?></div>
                            <div style="margin-top: 10px; font-weight: bold; font-family: monospace;"><?= htmlspecialchars($metodoMuestreo) ?></div>
                        </td>
                    </tr>
                </table>

                <!-- TABLA DE RESULTADOS DE MATRIZ TÉCNICA (SENCILLA) -->
                <div class="contenedor-tabla-matriz">
                    <table class="tabla-matriz-print">
                        <thead>
                            <tr>
                                <th style="width: 20px;">#</th>
                                <?php foreach ($columnas as $col): 
                                    $metodo = $colMethods[$col] ?? '';
                                ?>
                                    <th>
                                        <?= htmlspecialchars($col) ?>
                                        <?php if (!empty($metodo)): ?>
                                            <span class="metodo-sub"><?= htmlspecialchars($metodo) ?></span>
                                        <?php endif; ?>
                                    </th>
                                <?php endforeach; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (!empty($resultados)): ?>
                                <?php foreach ($resultados as $idx => $fila): ?>
                                    <tr>
                                        <td style="font-weight: bold;"><?= $idx + 1 ?></td>
                                        <?php foreach ($columnas as $col): 
                                            $val = $fila[$col] ?? ($fila[$schemaInfo['column_aliases'][$col] ?? ''] ?? '');
                                            $isCode = ($col === 'Código laboratorio' || $col === 'Codigo Lab');
                                            $isNum = is_numeric(str_replace(['%', ',', ' '], '', (string)$val)) && !empty($val);
                                        ?>
                                            <td class="<?= $isCode ? 'td-code' : ($isNum ? 'td-num' : ($col === 'Nombre muestra' ? 'td-left' : '')) ?>">
                                                <?= htmlspecialchars((string)$val) ?>
                                            </td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <?php for ($k = 1; $k <= 4; $k++): ?>
                                    <tr>
                                        <td><?= $k ?></td>
                                        <?php foreach ($columnas as $col): ?>
                                            <td>&mdash;</td>
                                        <?php endforeach; ?>
                                    </tr>
                                <?php endfor; ?>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- DISCLAIMER Y NOTAS OFICIALES DEL FORMATO (ISO/IEC 17025) -->
                    <div class="bloque-normativo-formato">
                        <div class="disclaimer-formato">
                            <?= htmlspecialchars($disclaimerOficial) ?>
                        </div>
                        <?php if (!empty($notasOficiales)): ?>
                            <div class="notas-formato">
                                <?php foreach ($notasOficiales as $nota): ?>
                                    <div class="nota-item"><?= htmlspecialchars($nota) ?></div>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <!-- OBSERVACIONES Y NOTAS ISO 17025 -->
            <div class="seccion-observaciones">
                <div class="caja-obs">
                    <div class="caja-obs-titulo">Observaciones y Condiciones del Ensayo</div>
                    <div style="color: #1e293b; line-height: 1.25;">
                        <?= !empty($detalle['observaciones']) ? htmlspecialchars($detalle['observaciones']) : 'Ensayos ejecutados bajo condiciones ambientales y parámetros establecidos en la norma técnica correspondiente. Equipos con calibración trazable vigente.' ?>
                    </div>
                </div>
                <div class="caja-obs">
                    <div class="caja-obs-titulo">Declaración de Conformidad e Imparcialidad (ISO/IEC 17025)</div>
                    <div style="color: #333333; line-height: 1.25;">
                        Los resultados expresados corresponden única y exclusivamente a los especímenes y puntos sometidos a prueba. Prohibida la reproducción parcial sin autorización escrita de CYCSA.
                    </div>
                </div>
            </div>

            <!-- LÍNEA DE CIERRE OFICIAL (SERIE CYCSA-RT-FM-22) -->
            <div class="linea-cierre-doc">-------------------------------- Última Línea ---------------------------------</div>

            <!-- BLOQUE DE FIRMAS TÉCNICAS CON ESPACIO CALIBRADO -->
            <div class="bloque-firmas">
                <div class="columna-firma">
                    <div class="espacio-para-firma"></div>
                    <div class="linea-firma">
                        <div class="firma-nombre"><?= htmlspecialchars(!empty($detalle['tecnico_muestreo']) ? $detalle['tecnico_muestreo'] : 'Técnico de Ensayos') ?></div>
                        <div class="firma-cargo">Ejecutado por (Técnico Responsable)</div>
                    </div>
                </div>
                <div class="columna-firma">
                    <div class="espacio-para-firma"></div>
                    <div class="linea-firma">
                        <div class="firma-nombre">Supervisión de Ensayos</div>
                        <div class="firma-cargo">Revisado por (Supervisor de Área)</div>
                    </div>
                </div>
                <div class="columna-firma">
                    <div class="espacio-para-firma"></div>
                    <div class="linea-firma">
                        <div class="firma-nombre"><?= htmlspecialchars($firmanteNombre) ?></div>
                        <div class="firma-cargo">Aprobado (<?= htmlspecialchars($firmanteCargo) ?> / ISO 17025)</div>
                    </div>
                </div>
            </div>

        </div>

        <!-- Paginación Oficial -->
        <div style="position: absolute; bottom: 6mm; right: 14mm; font-size: 8px; color: #64748b;">
            Página 1 de 1
        </div>

    </div>

    <!-- MODAL PARA ENVIAR MATRIZ OFICIAL AL CLIENTE POR CORREO -->
    <div id="modalEnviarPrint" style="display:none; position:fixed; z-index:10005; left:0; top:0; width:100%; height:100%; overflow:auto; background-color:rgba(0,0,0,0.65); backdrop-filter:blur(4px);">
        <div style="width: 520px; max-width:90%; background:white; margin:8% auto; padding:25px; border-radius:12px; box-shadow:0 12px 35px rgba(0,0,0,0.35); font-family: Arial, sans-serif;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 15px; border-bottom: 1.5px solid #e2e8f0; padding-bottom: 10px;">
                <h3 style="margin: 0; color: #1e293b; font-size: 17px; font-weight: 700; display:flex; align-items:center; gap:8px;">
                    <i class="fa-solid fa-paper-plane" style="color: #059669;"></i> Enviar Informe Oficial al Cliente
                </h3>
                <button type="button" onclick="cerrarModalEnviarPrint()" style="background:none; border:none; font-size:22px; cursor:pointer; color:#64748b;">&times;</button>
            </div>

            <form method="POST" action="/Cycsa/publico/operaciones/enviar-matriz-cliente?retorno=print" onsubmit="prepararEnvioPrint(this)">
                <input type="hidden" name="csrf_token" value="<?= $_SESSION['csrf_token'] ?? '' ?>">
                <input type="hidden" name="id_detalle" value="<?= $detalle['id'] ?>">

                <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 12px; margin-bottom: 15px; font-size: 12.5px;">
                    <div style="font-weight: bold; color: #0f172a;"><?= htmlspecialchars($ensayoTituloOficial) ?></div>
                    <div style="color: #64748b; margin-top: 2px;">
                        O/S: <strong style="color: #103487; font-family: monospace;"><?= htmlspecialchars($detalle['codigo_os']) ?></strong> &bull; 
                        Cliente: <strong><?= htmlspecialchars($detalle['cliente_nombre'] ?? 'Cliente') ?></strong>
                    </div>
                </div>

                <div style="margin-bottom: 14px;">
                    <label style="font-size: 13px; font-weight: bold; color: #1e293b; display: block; margin-bottom: 5px;">
                        Correo Electrónico del Cliente:
                    </label>
                    <input type="email" name="destinatario" required value="<?= htmlspecialchars($detalle['cliente_email'] ?? '') ?>" style="width: 100%; padding: 9px 12px; font-size: 13.5px; border-radius: 6px; border: 1.5px solid #cbd5e1; box-sizing: border-box;">
                    <small style="color: #64748b; font-size: 11px; margin-top: 3px; display: block;">
                        * Correo del cliente registrado en el sistema.
                    </small>
                </div>

                <div style="margin-bottom: 16px;">
                    <label style="font-size: 13px; font-weight: bold; color: #1e293b; display: block; margin-bottom: 5px;">
                        Asunto del Mensaje:
                    </label>
                    <input type="text" name="asunto" required value="Informe Oficial de Ensayo - <?= htmlspecialchars($detalle['codigo_os']) ?> - <?= htmlspecialchars($ensayoTituloOficial) ?> - CYCSA" style="width: 100%; padding: 9px 12px; font-size: 13.5px; border-radius: 6px; border: 1.5px solid #cbd5e1; box-sizing: border-box;">
                </div>

                <div style="background: #ecfdf5; border: 1px solid #a7f3d0; border-radius: 6px; padding: 10px 12px; margin-bottom: 18px; font-size: 11.5px; color: #065f46;">
                    <strong>PDF Adjunto Oficial:</strong> Se generará y enviará la matriz técnica idéntica a esta vista con membrete horizontal y firmas técnicas (ISO/IEC 17025).
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 10px; border-top: 1px solid #e2e8f0; padding-top: 14px;">
                    <button type="button" onclick="cerrarModalEnviarPrint()" style="padding: 8px 16px; border-radius: 6px; border: 1px solid #cbd5e1; background: #f1f5f9; color: #334155; font-weight: bold; cursor: pointer;">
                        Cancelar
                    </button>
                    <button type="submit" id="btn-submit-print" style="padding: 8px 20px; border-radius: 6px; border: none; background: #059669; color: white; font-weight: bold; cursor: pointer; display: inline-flex; align-items: center; gap: 6px;">
                        <i class="fa-solid fa-paper-plane"></i> Enviar Informe al Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>

    <script>
        function abrirModalEnviarPrint() {
            document.getElementById('modalEnviarPrint').style.display = 'block';
        }
        function cerrarModalEnviarPrint() {
            document.getElementById('modalEnviarPrint').style.display = 'none';
        }
        function prepararEnvioPrint(form) {
            const btn = document.getElementById('btn-submit-print');
            if (btn) {
                btn.disabled = true;
                btn.innerHTML = '<i class="fa-solid fa-spinner fa-spin"></i> Enviando PDF por correo...';
            }
            return true;
        }
    </script>

</body>
</html>
