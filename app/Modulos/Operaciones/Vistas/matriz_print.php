<?php
// Vista Imprimible Oficial de Matriz de Ensayo / Cálculo con Membrete Horizontal CYCSA (Estilo Sencillo y Sobrio con Espacio Amplio para Firmas)
$archivoMd = $detalle['archivo_markdown'] ?? '';
$formatosSchemaArray = json_decode($formatosSchemaJson ?? '{}', true);

// Normalizar búsqueda en schema
$schemaInfo = $formatosSchemaArray[$archivoMd] ?? [];
if (empty($schemaInfo)) {
    $archSinAcentos = strtr(utf8_decode($archivoMd), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
    foreach ($formatosSchemaArray as $k => $v) {
        $kSin = strtr(utf8_decode($k), utf8_decode('àáâãäçèéêëìíîïñòóôõöùúûüýÿÀÁÂÃÄÇÈÉÊËÌÍÎÏÑÒÓÔÕÖÙÚÛÜÝ'), 'aaaaaceeeeiiiinooooouuuuyyAAAAACEEEEIIIINOOOOOUUUUY');
        if ($kSin === $archSinAcentos || strpos($kSin, $archSinAcentos) !== false || strpos($archSinAcentos, $kSin) !== false) {
            $schemaInfo = $v;
            break;
        }
    }
}

$codigoFormatoOficial = !empty($schemaInfo['codigo_formato']) ? $schemaInfo['codigo_formato'] : (!empty($detalle['codigo_documento']) ? $detalle['codigo_documento'] : 'CYCSA-RT-FM-22');
$ensayoTituloOficial = !empty($schemaInfo['ensayo_titulo']) ? $schemaInfo['ensayo_titulo'] : $detalle['descripcion_ensayo'];
$metodoMuestreoOficial = !empty($schemaInfo['metodo_muestreo']) ? $schemaInfo['metodo_muestreo'] : (!empty($detalle['norma_astm']) ? $detalle['norma_astm'] : 'Norma ASTM / AASHTO Oficial');
$tipoMuestraOficial = !empty($schemaInfo['tipo_muestra']) ? $schemaInfo['tipo_muestra'] : 'Especímenes / Muestras';
$colMethods = $schemaInfo['column_methods'] ?? [];

$resultados = [];
if (!empty($detalle['resultados_json'])) {
    $resultados = json_decode($detalle['resultados_json'], true) ?: [];
}

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
            left: 58mm; /* Libre de colisión con el logotipo CYCSA */
            right: 14mm;
            height: 33mm;
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding-bottom: 3px;
            border-bottom: 1.5px solid #000000;
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

        /* 2. ZONA DE CONTENIDO: Inicia DEBAJO del logo (Top: 45mm) y termina ARRIBA del pie de página (Bottom: 34mm) */
        .zona-cuerpo {
            position: absolute;
            top: 45mm;
            left: 14mm;
            right: 14mm;
            bottom: 34mm;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            box-sizing: border-box;
        }

        /* Metadata de la Orden de Servicio - Tabla Sencilla */
        .tabla-metadatos {
            width: 100%;
            border-collapse: collapse;
            font-size: 8.5px;
            background: #ffffff;
            border: 1px solid #000000;
            margin-bottom: 3px;
        }

        .tabla-metadatos td {
            padding: 2px 5px;
            border: 1px solid #000000;
            vertical-align: middle;
            line-height: 1.15;
            color: #000000;
        }

        .meta-header-cell {
            background: #f3f4f6;
            font-weight: bold;
            color: #000000;
            width: 14%;
        }

        .meta-val-cell {
            color: #000000;
            font-weight: normal;
        }

        /* Banner de Procedimiento y Norma - Caja Sencilla */
        .banner-ensayo {
            background: #ffffff;
            border: 1px solid #000000;
            padding: 2.5px 6px;
            margin-bottom: 3px;
            font-size: 8px;
            line-height: 1.2;
            color: #000000;
        }

        .banner-ensayo strong {
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

        /* Observaciones y Declaración ISO 17025 */
        .seccion-observaciones {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 6px;
            margin-bottom: 6px;
            font-size: 7.5px;
        }

        .caja-obs {
            border: 1px solid #000000;
            background: #ffffff;
            padding: 2px 5px;
            min-height: 22px;
        }

        .caja-obs-titulo {
            font-weight: bold;
            color: #000000;
            margin-bottom: 1px;
            text-transform: uppercase;
            font-size: 7.5px;
            border-bottom: 1px solid #cccccc;
            padding-bottom: 1px;
        }

        /* Bloque de Firmas Técnicas con Espacio Amplio para Firmar */
        .bloque-firmas {
            display: grid;
            grid-template-columns: 1fr 1fr 1fr;
            gap: 25px;
            margin-top: 4px;
        }

        .columna-firma {
            display: flex;
            flex-direction: column;
            text-align: center;
        }

        .espacio-para-firma {
            height: 38px; /* Espacio libre de 38px para firmar y sellar físicamente */
            background: transparent;
        }

        .linea-firma {
            border-top: 1px solid #000000;
            padding-top: 3px;
        }

        .firma-nombre {
            font-weight: bold;
            color: #000000;
            font-size: 8.5px;
        }

        .firma-cargo {
            color: #444444;
            font-size: 7.5px;
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
            <span style="font-weight: bold; font-size: 15px; color: #60a5fa;">
                <i class="fa-solid fa-file-invoice"></i> Registro Oficial de Ensayo
            </span>
            <span style="background: #1e293b; padding: 4px 10px; border-radius: 4px; font-size: 12px; font-family: monospace; color: #cbd5e1;">
                <?= htmlspecialchars($codigoFormatoOficial) ?> &bull; O/S: <?= htmlspecialchars($detalle['codigo_os']) ?>
            </span>
        </div>
        <div style="display: flex; gap: 10px; align-items: center;">
            <a href="/Cycsa/publico/operaciones/captura-matriz?id_detalle=<?= $detalle['id'] ?>" class="btn-accion-top btn-volver">
                <i class="fa-solid fa-pen-to-square"></i> Volver a Matriz
            </a>
            <a href="/Cycsa/publico/operaciones/descargar-matriz-pdf?id_detalle=<?= $detalle['id'] ?>" target="_blank" class="btn-accion-top" style="background: #334155; color: white;">
                <i class="fa-solid fa-file-pdf"></i> Descargar PDF
            </a>
            <button onclick="window.print()" class="btn-accion-top btn-imprimir">
                <i class="fa-solid fa-print"></i> Imprimir Matriz (Horizontal)
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
        
        <!-- 1. ZONA SUPERIOR: Encabezado a la derecha del logo -->
        <div class="zona-cabecera">
            <div class="titulo-central">
                <h1 class="titulo-empresa">Consultoría y Construcción S.A. (CYCSA)</h1>
                <div class="subtitulo-doc">Registro Técnico de Ensayo / Matriz de Cálculo</div>
            </div>
            <div class="insignia-codigo-doc">
                <div class="badge-doc-oficial"><?= htmlspecialchars($codigoFormatoOficial) ?></div>
                <div class="version-doc">ISO/IEC 17025:2017</div>
            </div>
        </div>

        <!-- 2. ZONA DE CUERPO: Inicia debajo del logo y termina arriba del pie de página -->
        <div class="zona-cuerpo">
            
            <div>
                <!-- METADATOS DE LA ORDEN DE SERVICIO -->
                <table class="tabla-metadatos">
                    <tr>
                        <td class="meta-header-cell">No. Orden Servicio:</td>
                        <td class="meta-val-cell" style="font-family: monospace; font-weight: bold;">
                            <?= htmlspecialchars($detalle['codigo_os']) ?>
                        </td>
                        <td class="meta-header-cell">Fecha de Ensaye:</td>
                        <td class="meta-val-cell">
                            <?= !empty($detalle['fecha_hora_toma_muestra']) ? date('d/m/Y H:i', strtotime($detalle['fecha_hora_toma_muestra'])) : date('d/m/Y') ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-header-cell">Cliente / Solicitante:</td>
                        <td class="meta-val-cell">
                            <?= htmlspecialchars($detalle['cliente_nombre'] ?? 'Cliente Confidencial (ISO 17025)') ?>
                        </td>
                        <td class="meta-header-cell">Responsable Técnico:</td>
                        <td class="meta-val-cell">
                            <?= htmlspecialchars(!empty($detalle['tecnico_muestreo']) ? $detalle['tecnico_muestreo'] : 'Personal Técnico Autorizado') ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-header-cell">Nombre del Proyecto:</td>
                        <td class="meta-val-cell">
                            <?= htmlspecialchars($detalle['nombre_proyecto'] ?? 'Proyecto no especificado') ?>
                        </td>
                        <td class="meta-header-cell">Matriz / Muestra:</td>
                        <td class="meta-val-cell">
                            <?= htmlspecialchars($tipoMuestraOficial) ?>
                        </td>
                    </tr>
                    <tr>
                        <td class="meta-header-cell">Punto / Procedencia:</td>
                        <td class="meta-val-cell" colspan="3">
                            <?= htmlspecialchars(!empty($detalle['procedencia_punto_muestreo']) ? $detalle['procedencia_punto_muestreo'] : ($detalle['nombre_proyecto'] ?? 'Sitio de Proyecto')) ?>
                        </td>
                    </tr>
                </table>

                <!-- BANNER DE PROCEDIMIENTO TÉCNICO Y NORMA -->
                <div class="banner-ensayo">
                    <div><strong>Procedimiento Técnico:</strong> <?= htmlspecialchars($ensayoTituloOficial) ?></div>
                    <div style="margin-top: 1px;"><strong>Norma de Referencia:</strong> <span style="font-family: monospace; font-weight: bold;"><?= htmlspecialchars($metodoMuestreoOficial) ?></span></div>
                </div>

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
                                            $val = $fila[$col] ?? '';
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
                </div>
            </div>

            <div>
                <!-- OBSERVACIONES Y NOTAS ISO 17025 -->
                <div class="seccion-observaciones">
                    <div class="caja-obs">
                        <div class="caja-obs-titulo">Observaciones y Condiciones del Ensayo</div>
                        <div style="color: #000000; line-height: 1.2;">
                            <?= !empty($detalle['observaciones']) ? htmlspecialchars($detalle['observaciones']) : 'Ensayos ejecutados bajo condiciones ambientales y parámetros establecidos en la norma técnica correspondiente. Equipos con calibración trazable vigente.' ?>
                        </div>
                    </div>
                    <div class="caja-obs">
                        <div class="caja-obs-titulo">Declaración de Conformidad e Imparcialidad (ISO/IEC 17025)</div>
                        <div style="color: #333333; line-height: 1.2;">
                            Los resultados expresados corresponden única y exclusivamente a los especímenes y puntos sometidos a prueba. Prohibida la reproducción parcial sin autorización escrita de CYCSA.
                        </div>
                    </div>
                </div>

                <!-- BLOQUE DE FIRMAS TÉCNICAS CON ESPACIO REAL PARA FIRMA / SELLO -->
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
                            <div class="firma-nombre">Gerencia Técnica / Calidad</div>
                            <div class="firma-cargo">Aprobado (Aseguramiento Calidad ISO 17025)</div>
                        </div>
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
