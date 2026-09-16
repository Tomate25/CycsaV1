<?php
/**
 * Vista Imprimible Oficial de Factura Comercial de Servicios de Laboratorio (CYCSA S.A.)
 */
$montoTotal = (float)($os['cot_total'] ?? 0.0);
$subtotal = (float)($os['cot_subtotal'] ?? ($montoTotal / 1.15));
$iva = (float)($os['cot_iva'] ?? ($montoTotal - $subtotal));
$saldoActual = $cxc ? (float)$cxc['saldo'] : $montoTotal;
$estadoFactura = $cxc ? $cxc['estado'] : 'Pendiente';
$estaPagada = ($estadoFactura === 'Pagado' || $saldoActual <= 0.01);
$montoPagado = max(0.0, $montoTotal - $saldoActual);

// Conversión a letras
$totalEnLetras = function_exists('numeroALetras') ? numeroALetras($montoTotal, 'C$') : (number_format($montoTotal, 2) . ' CÓRDOBAS');

// Asiento contable vinculado
$numAsiento = !empty($asientoDiario[0]['num_partida']) ? $asientoDiario[0]['num_partida'] : null;

// Logo en base64
$rutaLogo = dirname(__DIR__, 4) . '/publico/img/logo.png';
$logoBase64 = file_exists($rutaLogo) ? 'data:image/png;base64,' . base64_encode(file_get_contents($rutaLogo)) : '';
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Factura <?= htmlspecialchars($facturaNum) ?> - CYCSA</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <style>
        @page {
            size: letter portrait;
            margin: 10mm;
        }

        * {
            box-sizing: border-box;
            -webkit-print-color-adjust: exact !important;
            print-color-adjust: exact !important;
        }

        body {
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
            background-color: #f1f5f9;
            color: #1e293b;
            margin: 0;
            padding: 20px;
            font-size: 12px;
            line-height: 1.4;
        }

        .no-print-bar {
            max-width: 820px;
            margin: 0 auto 15px auto;
            background: #ffffff;
            padding: 12px 20px;
            border-radius: 8px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 8px rgba(0,0,0,0.06);
            border: 1px solid #e2e8f0;
        }

        .btn-print {
            background-color: #0f3b68;
            color: #ffffff;
            border: none;
            padding: 9px 18px;
            border-radius: 6px;
            font-weight: 700;
            font-size: 13px;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 8px;
            text-decoration: none;
            transition: all 0.2s;
        }

        .btn-print:hover {
            background-color: #0c2d50;
        }

        .btn-back {
            background-color: #f8fafc;
            color: #475569;
            border: 1px solid #cbd5e1;
            padding: 9px 16px;
            border-radius: 6px;
            font-weight: 600;
            font-size: 13px;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 6px;
        }

        .factura-container {
            max-width: 820px;
            margin: 0 auto;
            background: #ffffff;
            padding: 35px 40px;
            border-radius: 10px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.08);
            border: 1px solid #e2e8f0;
            position: relative;
        }

        /* Encabezado */
        .factura-header {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            border-bottom: 2.5px solid #0f3b68;
            padding-bottom: 20px;
            margin-bottom: 20px;
        }

        .empresa-info {
            display: flex;
            gap: 15px;
            align-items: center;
        }

        .empresa-logo {
            max-width: 120px;
            max-height: 70px;
            object-fit: contain;
        }

        .empresa-texto h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 18px;
            font-weight: 800;
            color: #0f3b68;
            margin: 0 0 4px 0;
            letter-spacing: 0.5px;
        }

        .empresa-texto p {
            margin: 2px 0;
            color: #475569;
            font-size: 11px;
        }

        .factura-meta-box {
            text-align: right;
            background: #f8fafc;
            border: 1.5px solid #0f3b68;
            border-radius: 8px;
            padding: 12px 18px;
            min-width: 240px;
        }

        .factura-meta-box .doc-tipo {
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 800;
            color: #0f3b68;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .factura-meta-box .doc-numero {
            font-family: monospace;
            font-size: 16px;
            font-weight: 800;
            color: #b91c1c;
            margin: 4px 0 8px 0;
        }

        .factura-meta-box .meta-fila {
            font-size: 11px;
            color: #334155;
            margin-bottom: 3px;
        }

        /* Datos Cliente y Proyecto */
        .seccion-datos-cliente {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 15px;
            margin-bottom: 20px;
        }

        .card-info {
            background: #f8fafc;
            border: 1px solid #e2e8f0;
            border-radius: 8px;
            padding: 12px 16px;
        }

        .card-info-titulo {
            font-family: 'Outfit', sans-serif;
            font-size: 11.5px;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #0f3b68;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 4px;
            margin-bottom: 8px;
            display: flex;
            align-items: center;
            gap: 6px;
        }

        .card-info-fila {
            font-size: 11.5px;
            margin-bottom: 4px;
            display: flex;
        }

        .card-info-fila strong {
            width: 105px;
            color: #64748b;
            flex-shrink: 0;
        }

        .card-info-fila span {
            color: #0f172a;
            font-weight: 600;
        }

        /* Tabla de Ítems */
        .tabla-items {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }

        .tabla-items th {
            background-color: #0f3b68;
            color: #ffffff;
            font-size: 11px;
            font-weight: 700;
            text-transform: uppercase;
            padding: 8px 10px;
            letter-spacing: 0.5px;
            border: 1px solid #0f3b68;
        }

        .tabla-items td {
            padding: 9px 10px;
            border: 1px solid #e2e8f0;
            font-size: 11.5px;
        }

        .tabla-items tr:nth-child(even) td {
            background-color: #f8fafc;
        }

        /* Bloque de Totales y Sello */
        .seccion-liquidacion {
            display: grid;
            grid-template-columns: 1.2fr 1fr;
            gap: 20px;
            align-items: flex-start;
            margin-bottom: 25px;
        }

        .bloque-letras-pago {
            background: #f8fafc;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            padding: 12px 16px;
        }

        .tabla-totales {
            width: 100%;
            border-collapse: collapse;
        }

        .tabla-totales td {
            padding: 6px 12px;
            font-size: 12px;
            border: 1px solid #e2e8f0;
        }

        .tabla-totales .fila-total td {
            background-color: #0f3b68;
            color: #ffffff;
            font-weight: 800;
            font-size: 14px;
            border-color: #0f3b68;
        }

        /* Sello Pagado */
        .stamp-pagado {
            display: inline-block;
            padding: 6px 16px;
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 800;
            color: #166534;
            background: #dcfce7;
            border: 2px solid #22c55e;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            transform: rotate(-3deg);
            box-shadow: 0 2px 8px rgba(34,197,94,0.2);
            margin-bottom: 8px;
        }

        .stamp-pendiente {
            display: inline-block;
            padding: 6px 16px;
            font-family: 'Outfit', sans-serif;
            font-size: 13px;
            font-weight: 800;
            color: #991b1b;
            background: #fee2e2;
            border: 2px dashed #ef4444;
            border-radius: 8px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 8px;
        }

        /* Firmas */
        .seccion-firmas {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 60px;
            margin-top: 40px;
            padding-top: 20px;
        }

        .firma-caja {
            text-align: center;
            border-top: 1.5px solid #64748b;
            padding-top: 8px;
        }

        .firma-caja .firma-nombre {
            font-weight: 700;
            color: #0f172a;
            font-size: 12px;
        }

        .firma-caja .firma-cargo {
            font-size: 10.5px;
            color: #64748b;
        }

        .pie-legal {
            margin-top: 30px;
            border-top: 1px solid #e2e8f0;
            padding-top: 12px;
            text-align: center;
            font-size: 9.5px;
            color: #94a3b8;
            line-height: 1.3;
        }

        @media print {
            body {
                background: white;
                padding: 0;
            }
            .no-print-bar {
                display: none !important;
            }
            .factura-container {
                box-shadow: none;
                border: none;
                padding: 0;
                max-width: 100%;
            }
        }
    </style>
</head>
<body>

    <!-- Barra de acciones en pantalla (Oculta al imprimir) -->
    <div class="no-print-bar">
        <div style="display: flex; align-items: center; gap: 10px;">
            <a href="/Cycsa/publico/operaciones" class="btn-back">
                <i class="fa-solid fa-arrow-left"></i> Volver a Operaciones LIMS
            </a>
            <span style="font-size: 12px; color: #64748b;">
                O/S: <strong style="color: #0f3b68;"><?= htmlspecialchars($os['codigo_os']) ?></strong>
            </span>
        </div>
        <div style="display: flex; gap: 10px;">
            <button onclick="window.print()" class="btn-print">
                <i class="fa-solid fa-print"></i> Imprimir / Guardar en PDF
            </button>
        </div>
    </div>

    <!-- Contenedor Oficial de la Factura -->
    <div class="factura-container">
        
        <!-- Encabezado de la Empresa y Factura -->
        <div class="factura-header">
            <div class="empresa-info">
                <?php if (!empty($logoBase64)): ?>
                    <img src="<?= $logoBase64 ?>" alt="Logo CYCSA" class="empresa-logo">
                <?php endif; ?>
                <div class="empresa-texto">
                    <h1>CORPORACIÓN Y CONSTRUCCIONES S.A.</h1>
                    <p><strong>CYCSA</strong> &bull; RUC: <strong>J0310000185934</strong></p>
                    <p>Servicios de Laboratorio de Suelos, Concretos, Asfaltos y Materiales</p>
                    <p><i class="fa-solid fa-location-dot"></i> Km 83.5 Carretera León-Managua &bull; Tel: +505 2310-3988 / 8851-6377</p>
                    <p><i class="fa-solid fa-envelope"></i> facturacion@cycsanic.com &bull; www.cycsanic.com</p>
                </div>
            </div>

            <div class="factura-meta-box">
                <div class="doc-tipo">Factura Comercial</div>
                <div class="doc-numero"><?= htmlspecialchars($facturaNum) ?></div>
                <div class="meta-fila"><strong>Fecha Emisión:</strong> <?= date('d/m/Y', strtotime($cxc['fecha_emision'] ?? $os['fecha_emision'])) ?></div>
                <div class="meta-fila"><strong>Fecha Vencimiento:</strong> <?= !empty($cxc['fecha_vencimiento']) ? date('d/m/Y', strtotime($cxc['fecha_vencimiento'])) : date('d/m/Y') ?></div>
                <div class="meta-fila"><strong>Orden de Servicio:</strong> <?= htmlspecialchars($os['codigo_os']) ?></div>
                <div class="meta-fila"><strong>Cotización:</strong> <?= htmlspecialchars($os['cot_codigo']) ?></div>
            </div>
        </div>

        <!-- Información del Cliente y Proyecto -->
        <div class="seccion-datos-cliente">
            <div class="card-info">
                <div class="card-info-titulo">
                    <i class="fa-solid fa-building"></i> Datos del Cliente / Facturado a:
                </div>
                <div class="card-info-fila">
                    <strong>Razón Social:</strong>
                    <span><?= htmlspecialchars($os['cliente_nombre']) ?></span>
                </div>
                <div class="card-info-fila">
                    <strong>RUC / Cédula:</strong>
                    <span><?= htmlspecialchars($os['cliente_ruc'] ?? 'S/R') ?></span>
                </div>
                <div class="card-info-fila">
                    <strong>Contacto:</strong>
                    <span><?= htmlspecialchars($os['contacto_nombre'] ?? 'Atención al Cliente') ?></span>
                </div>
                <div class="card-info-fila">
                    <strong>Teléfono / Email:</strong>
                    <span><?= htmlspecialchars($os['cliente_telefono'] ?? '-') ?> &bull; <?= htmlspecialchars($os['cliente_email'] ?? '-') ?></span>
                </div>
                <div class="card-info-fila">
                    <strong>Dirección:</strong>
                    <span><?= htmlspecialchars($os['cliente_direccion'] ?? 'Nicaragua') ?></span>
                </div>
            </div>

            <div class="card-info">
                <div class="card-info-titulo">
                    <i class="fa-solid fa-briefcase"></i> Datos del Proyecto y Condiciones:
                </div>
                <div class="card-info-fila">
                    <strong>Proyecto:</strong>
                    <span><?= htmlspecialchars($os['nombre_proyecto']) ?></span>
                </div>
                <div class="card-info-fila">
                    <strong>Condición Pago:</strong>
                    <span><?= htmlspecialchars($os['condicion_pago'] ?? 'Contado') ?></span>
                </div>
                <div class="card-info-fila">
                    <strong>Atención a:</strong>
                    <span><?= htmlspecialchars($os['atencion_a'] ?? $os['cliente_nombre']) ?></span>
                </div>
                <div class="card-info-fila">
                    <strong>Moneda:</strong>
                    <span>Córdobas (C$)</span>
                </div>
                <?php if ($numAsiento): ?>
                <div class="card-info-fila">
                    <strong>Asiento Diario:</strong>
                    <span style="font-family: monospace; color: #0f3b68;"><?= htmlspecialchars($numAsiento) ?> (Libro Diario)</span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Tabla Detalle de Ensayos / Servicios -->
        <table class="tabla-items">
            <thead>
                <tr>
                    <th style="width: 35px; text-align: center;">#</th>
                    <th style="width: 100px;">Código</th>
                    <th>Descripción del Ensayo / Servicio</th>
                    <th style="width: 130px;">Norma / Procedimiento</th>
                    <th style="width: 55px; text-align: center;">Cant.</th>
                    <th style="width: 95px; text-align: right;">Precio Unit.</th>
                    <th style="width: 105px; text-align: right;">Subtotal</th>
                </tr>
            </thead>
            <tbody>
                <?php 
                $numItem = 1;
                foreach ($items as $it): 
                    $cantIt = (float)($it['cantidad'] ?? 1.0);
                    $precioIt = (float)($it['precio_unitario'] ?? 0.0);
                    $subtotalIt = (float)($it['subtotal'] ?? ($cantIt * $precioIt));
                ?>
                <tr>
                    <td style="text-align: center; color: #64748b; font-weight: 600;"><?= $numItem++ ?></td>
                    <td style="font-family: monospace; font-size: 11px; color: #0f3b68;"><?= htmlspecialchars($it['codigo_servicio'] ?? 'LAB-' . $it['id']) ?></td>
                    <td>
                        <div style="font-weight: 700; color: #0f172a;"><?= htmlspecialchars($it['descripcion_ensayo']) ?></div>
                        <?php if (!empty($it['observaciones'])): ?>
                            <div style="font-size: 10.5px; color: #64748b; margin-top: 2px;"><?= htmlspecialchars($it['observaciones']) ?></div>
                        <?php endif; ?>
                    </td>
                    <td style="font-family: monospace; font-size: 11px; color: #475569;"><?= htmlspecialchars($it['norma_astm'] ?? '-') ?></td>
                    <td style="text-align: center; font-weight: 700;"><?= number_format($cantIt, 0) ?></td>
                    <td style="text-align: right;">C$ <?= number_format($precioIt, 2) ?></td>
                    <td style="text-align: right; font-weight: 700;">C$ <?= number_format($subtotalIt, 2) ?></td>
                </tr>
                <?php endforeach; ?>
                <?php if (empty($items)): ?>
                <tr>
                    <td colspan="7" style="text-align: center; color: #64748b; padding: 20px;">Servicios de laboratorio según Orden de Servicio <?= htmlspecialchars($os['codigo_os']) ?></td>
                </tr>
                <?php endif; ?>
            </tbody>
        </table>

        <!-- Sección de Liquidación, Totales y Estado -->
        <div class="seccion-liquidacion">
            <div class="bloque-letras-pago">
                <div style="margin-bottom: 10px;">
                    <?php if ($estaPagada): ?>
                        <div class="stamp-pagado">
                            <i class="fa-solid fa-circle-check"></i> FACTURA PAGADA
                        </div>
                    <?php else: ?>
                        <div class="stamp-pendiente">
                            <i class="fa-solid fa-clock"></i> PENDIENTE DE COBRO (Saldo: C$ <?= number_format($saldoActual, 2) ?>)
                        </div>
                    <?php endif; ?>
                </div>

                <div style="font-size: 11.5px; color: #334155; margin-bottom: 8px;">
                    <strong>MONTO EN LETRAS:</strong><br>
                    <span style="font-style: italic; color: #0f3b68; font-weight: 600;"><?= htmlspecialchars($totalEnLetras) ?></span>
                </div>

                <div style="font-size: 11px; color: #64748b; border-top: 1px dashed #cbd5e1; padding-top: 6px; margin-top: 6px;">
                    <strong>Detalle de Cobranza:</strong><br>
                    <?php if ($transaccionBancaria): ?>
                        <span>Pago vía Transferencia Bancaria &bull; Banco: <strong><?= htmlspecialchars($transaccionBancaria['banco_nombre']) ?></strong> (Cuenta: <?= htmlspecialchars($transaccionBancaria['numero_cuenta']) ?>) &bull; Ref / Doc: <strong><?= htmlspecialchars($transaccionBancaria['numero_documento']) ?></strong></span>
                    <?php elseif (!empty($cxc['notas']) && stripos($cxc['notas'], 'Efectivo') !== false): ?>
                        <span>Pago registrado en <strong>Efectivo</strong> ingresado en <strong>Caja Principal</strong> (Cuenta Contable: 1010101).</span>
                    <?php elseif (!empty($cxc['notas'])): ?>
                        <span><?= htmlspecialchars($cxc['notas']) ?></span>
                    <?php else: ?>
                        <span>Condición: <?= htmlspecialchars($os['condicion_pago'] ?? 'Crédito') ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <div>
                <table class="tabla-totales">
                    <tr>
                        <td style="font-weight: 600; color: #475569;">SUBTOTAL:</td>
                        <td style="text-align: right; font-weight: 700;">C$ <?= number_format($subtotal, 2) ?></td>
                    </tr>
                    <tr>
                        <td style="font-weight: 600; color: #475569;">I.V.A. (15%):</td>
                        <td style="text-align: right; font-weight: 700;">C$ <?= number_format($iva, 2) ?></td>
                    </tr>
                    <tr class="fila-total">
                        <td>TOTAL FACTURADO:</td>
                        <td style="text-align: right;">C$ <?= number_format($montoTotal, 2) ?></td>
                    </tr>
                    <?php if ($estaPagada): ?>
                    <tr>
                        <td style="color: #166534; font-weight: 600;">Monto Pagado:</td>
                        <td style="text-align: right; color: #166534; font-weight: 700;">C$ <?= number_format($montoTotal, 2) ?></td>
                    </tr>
                    <tr>
                        <td style="color: #475569; font-weight: 600;">Saldo Pendiente:</td>
                        <td style="text-align: right; color: #475569; font-weight: 700;">C$ 0.00</td>
                    </tr>
                    <?php endif; ?>
                </table>
            </div>
        </div>

        <!-- Firmas y Conformidad -->
        <div class="seccion-firmas">
            <div class="firma-caja">
                <div class="firma-nombre">CYCSA S.A.</div>
                <div class="firma-cargo">Dpto. de Facturación y Finanzas</div>
            </div>
            <div class="firma-caja">
                <div class="firma-nombre"><?= htmlspecialchars($os['cliente_nombre']) ?></div>
                <div class="firma-cargo">Recibido Conforme (Firma y Sello)</div>
            </div>
        </div>

        <!-- Pie de página legal -->
        <div class="pie-legal">
            Esta factura comercial ampara los servicios de ensayo de laboratorio técnico brindados bajo normativas ASTM / AASHTO por CYCSA S.A.
            Documento válido para efectos contables y tributarios bajo la legislación nicaragüense.
        </div>

    </div>

</body>
</html>
