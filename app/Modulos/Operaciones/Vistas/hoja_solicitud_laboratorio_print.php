<?php
// Vista imprimible de la Hoja de Solicitud de Laboratorio - Conforme a ISO 17025
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Hoja de Solicitud de Laboratorio - <?= htmlspecialchars($os['codigo_os'] ?? '') ?></title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=Outfit:wght@600;700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <style>
        * { box-sizing: border-box; margin: 0; padding: 0; }
        body {
            font-family: 'Inter', Arial, sans-serif;
            background-color: #f1f5f9;
            color: #0f172a;
            padding: 20px;
            font-size: 13px;
        }

        .no-print {
            max-width: 850px;
            margin: 0 auto 15px auto;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .btn-imprimir {
            background-color: #103487;
            color: white;
            padding: 10px 20px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: none;
            cursor: pointer;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .btn-imprimir:hover { background-color: #0c2766; }

        .btn-volver {
            background-color: #ffffff;
            color: #475569;
            padding: 10px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: 600;
            font-size: 13.5px;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            border: 1px solid #cbd5e1;
        }
        .btn-volver:hover { background-color: #f8fafc; }

        .hoja-papel {
            max-width: 850px;
            margin: 0 auto;
            background: white;
            padding: 40px 45px;
            border-radius: 4px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
            min-height: 1050px;
            position: relative;
        }

        /* ENCABEZADO */
        .encabezado-lab {
            display: grid;
            grid-template-columns: 120px 1fr 120px;
            align-items: center;
            gap: 15px;
            margin-bottom: 25px;
            border-bottom: 2px solid #0f172a;
            padding-bottom: 15px;
        }

        .logo-box {
            text-align: left;
        }
        .logo-box img {
            max-width: 110px;
            height: auto;
        }

        .titulos-box {
            text-align: center;
        }
        .titulos-box h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            color: #e31837;
            font-weight: 800;
            letter-spacing: 0.5px;
            margin-bottom: 3px;
            text-transform: uppercase;
        }
        .titulos-box h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            color: #103487;
            font-weight: 700;
            margin-bottom: 4px;
            text-transform: uppercase;
        }
        .titulos-box h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            color: #0f172a;
            font-weight: 800;
            text-decoration: underline;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        /* TABLA DE METADATOS SUPERIOR */
        .tabla-metadatos {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 25px;
            font-size: 12.5px;
        }
        .tabla-metadatos td {
            border: 1px solid #000;
            padding: 8px 12px;
            vertical-align: middle;
        }
        .tabla-metadatos .etiqueta-col {
            font-weight: 700;
            background-color: #ffffff;
            width: 30%;
        }
        .tabla-metadatos .valor-col {
            width: 70%;
        }

        /* TABLA PRINCIPAL DE DESCRIPCIÓN DE ANÁLISIS */
        .tabla-analisis-titulo {
            background-color: #ffffff;
            font-weight: 800;
            text-align: center;
            text-transform: uppercase;
            padding: 6px;
            border: 1px solid #000;
            border-bottom: none;
            font-size: 12px;
            letter-spacing: 0.5px;
        }
        .tabla-analisis {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 30px;
            font-size: 12.5px;
        }
        .tabla-analisis th {
            border: 1px solid #000;
            padding: 8px 10px;
            text-align: center;
            font-weight: 700;
            background-color: #ffffff;
        }
        .tabla-analisis td {
            border: 1px solid #000;
            padding: 10px;
            text-align: center;
        }

        /* SECCIÓN OBSERVACIONES */
        .seccion-observaciones {
            margin-bottom: 50px;
            font-size: 12.5px;
            line-height: 1.6;
        }
        .seccion-observaciones strong {
            display: inline-block;
            margin-bottom: 4px;
        }

        /* FIRMAS ISO 17025 */
        .bloque-firmas {
            margin-top: 40px;
        }
        .fila-firmas {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            margin-bottom: 45px;
        }
        .linea-firma {
            border-top: 1px solid #000;
            text-align: center;
            padding-top: 6px;
            font-size: 11.5px;
            font-weight: 600;
            color: #0f172a;
        }

        .pie-pagina-doc {
            position: absolute;
            bottom: 25px;
            right: 45px;
            font-size: 11px;
            color: #64748b;
        }

        @media print {
            body {
                background-color: white;
                padding: 0;
            }
            .no-print {
                display: none !important;
            }
            .hoja-papel {
                box-shadow: none;
                padding: 20px 25px;
                max-width: 100%;
                min-height: auto;
            }
        }
    </style>
</head>
<body>

    <div class="no-print">
        <a href="/Cycsa/publico/laboratorio" class="btn-volver">
            <i class="fa-solid fa-arrow-left"></i> Volver a Laboratorio
        </a>
        <button onclick="window.print()" class="btn-imprimir">
            <i class="fa-solid fa-print"></i> Imprimir Hoja de Solicitud
        </button>
    </div>

    <div class="hoja-papel">
        <!-- ENCABEZADO FORMAL (CYCSA-RT-FM-60) -->
        <div class="encabezado-lab">
            <div class="logo-box">
                <img src="/Cycsa/publico/img/logo.png" alt="CYCSA" onerror="this.style.display='none'">
            </div>
            <div class="titulos-box">
                <h2>CONSULTORIA Y CONSTRUCCION S.A (CYCSA)</h2>
                <h3>LABORATORIO DE MATERIALES Y SUELO</h3>
                <h1>HOJA DE SOLICITUD</h1>
            </div>
            <div style="text-align: right;">
                <span style="border: 1.5px solid #103487; padding: 4px 8px; font-weight: 800; font-size: 11px; font-family: monospace; color: #103487; border-radius: 4px; display: inline-block; white-space: nowrap;">
                    CYCSA-RT-FM-60
                </span>
                <div style="font-size: 9.5px; color: #64748b; margin-top: 3px; white-space: nowrap;">Edición 1 / Rev. 0</div>
            </div>
        </div>

        <!-- METADATOS DE RECEPCIÓN Y MUESTREO -->
        <table class="tabla-metadatos">
            <tr>
                <td class="etiqueta-col">Fecha de recepción de muestra:</td>
                <td class="valor-col" style="font-weight: 600;">
                    <?= !empty($recepcion['fecha_recepcion']) ? date('Y-m-d', strtotime($recepcion['fecha_recepcion'])) : date('Y-m-d') ?>
                </td>
            </tr>
            <tr>
                <td class="etiqueta-col">Fecha de muestreo:</td>
                <td class="valor-col">
                    <?= !empty($hoja['fecha_hora_toma_muestra']) ? date('Y-m-d', strtotime($hoja['fecha_hora_toma_muestra'])) : (!empty($os['fecha_muestreo']) ? date('Y-m-d', strtotime($os['fecha_muestreo'])) : date('Y-m-d')) ?>
                </td>
            </tr>
            <tr>
                <td class="etiqueta-col">Ensayo a realizar:</td>
                <td class="valor-col" style="font-weight: 700;">
                    <?php 
                    $nombresEnsayos = [];
                    foreach ($ensayos as $e) {
                        $nombresEnsayos[] = htmlspecialchars($e['descripcion_ensayo'] ?? $e['nombre_ensayo'] ?? '');
                    }
                    echo implode(' / ', array_filter($nombresEnsayos)) ?: 'Ensayos de Laboratorio Solicitados';
                    ?>
                </td>
            </tr>
            <tr>
                <td class="etiqueta-col">Cantidad de muestras totales del lote:</td>
                <td class="valor-col" style="font-weight: 700;">
                    <?= count($muestrasLote) > 0 ? count($muestrasLote) : 1 ?>
                </td>
            </tr>
        </table>

        <!-- TABLA DESCRIPCIÓN DE ANÁLISIS -->
        <div class="tabla-analisis-titulo">DESCRIPCIÓN DE ANÁLISIS</div>
        <table class="tabla-analisis">
            <thead>
                <tr>
                    <th style="width: 45%;">Ensayo a Realizar</th>
                    <th style="width: 25%;">Fecha de Elaboración</th>
                    <th style="width: 30%;">Código de la muestra</th>
                </tr>
            </thead>
            <tbody>
                <?php if (!empty($muestrasLote)): ?>
                    <?php foreach ($muestrasLote as $m): ?>
                        <tr>
                            <td style="text-align: left; font-weight: 600; padding-left: 15px;">
                                <?= htmlspecialchars($m['nombre_ensayo'] ?? ($ensayos[0]['descripcion_ensayo'] ?? 'Ensayo Estándar')) ?>
                            </td>
                            <td>
                                <?= !empty($m['fecha_elaboracion']) ? date('Y-m-d', strtotime($m['fecha_elaboracion'])) : date('Y-m-d') ?>
                            </td>
                            <td style="font-family: monospace; font-weight: 800; font-size: 13.5px; color: #0f172a;">
                                <?= htmlspecialchars($m['codigo_muestra'] ?? 'MS-0001-26') ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                <?php else: ?>
                    <tr>
                        <td style="text-align: left; font-weight: 600; padding-left: 15px;">
                            <?= htmlspecialchars($ensayos[0]['descripcion_ensayo'] ?? 'Ensayo Estándar') ?>
                        </td>
                        <td><?= date('Y-m-d') ?></td>
                        <td style="font-family: monospace; font-weight: 800; font-size: 13.5px;">
                            <?= htmlspecialchars($recepcion['codigo_muestra'] ?? 'MS-0001-26') ?>
                        </td>
                    </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- SECCIÓN DE OBSERVACIONES -->
        <div class="seccion-observaciones">
            <strong>OBSERVACIONES:</strong><br>
            <div>
                <?php if (!empty($hoja['codigo_documento'])): ?>
                    <?= htmlspecialchars($hoja['codigo_documento']) ?>
                <?php else: ?>
                    CYCSA-RT-FM-13
                <?php endif; ?>
                <?php if (!empty($recepcion['observaciones'])): ?>
                    - <?= htmlspecialchars($recepcion['observaciones']) ?>
                <?php endif; ?>
                <?php if (!empty($hoja['observaciones'])): ?>
                    - <?= htmlspecialchars($hoja['observaciones']) ?>
                <?php endif; ?>
            </div>
            <div style="margin-top: 4px; color: #475569;">
                HSS-<?= htmlspecialchars($os['codigo_os'] ?? '') ?>
            </div>
        </div>

        <!-- FIRMAS DE CUSTODIA ISO 17025 -->
        <div class="bloque-firmas">
            <!-- Primera Fila: Entrega y Recepción Inicial -->
            <div class="fila-firmas">
                <div>
                    <div style="height: 50px;"></div>
                    <div class="linea-firma">Firma de quien entrega la muestra</div>
                </div>
                <div>
                    <div style="height: 50px;"></div>
                    <div class="linea-firma">Firma de quien recibe la muestra</div>
                </div>
            </div>

            <!-- Segunda Fila: Entrega y Recepción Finalizada -->
            <div class="fila-firmas" style="margin-bottom: 0;">
                <div>
                    <div style="height: 50px;"></div>
                    <div class="linea-firma">Firma de quien entrega la muestra finalizada</div>
                </div>
                <div>
                    <div style="height: 50px;"></div>
                    <div class="linea-firma">Firma de quien recibe la muestra finalizada</div>
                </div>
            </div>
        </div>

        <div class="pie-pagina-doc">
            Página 1 de 1
        </div>
    </div>

</body>
</html>