<?php
// Vista para la decisión del cliente (aceptar/rechazar cotización)
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo ?? 'Decisión de Cotización - CYCSA', ENT_QUOTES, 'UTF-8') ?></title>
    <link rel="shortcut icon" href="/Cycsa/publico/img/logo.png" type="image/png">
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <style>
        :root {
            --primary: #103487;
            --primary-hover: #0c2766;
            --primary-light: #e6eefc;
            --success: #10b981;
            --success-hover: #059669;
            --success-light: #ecfdf5;
            --danger: #ef4444;
            --danger-hover: #dc2626;
            --danger-light: #fef2f2;
            --warning: #f59e0b;
            --warning-light: #fffbeb;
            --bg: #f8fafc;
            --card-bg: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --radius-sm: 6px;
            --radius-md: 12px;
            --radius-lg: 18px;
            --transition: all 0.3s ease;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg);
            color: var(--text-main);
            line-height: 1.6;
            padding: 40px 20px;
            display: flex;
            justify-content: center;
            align-items: center;
            min-height: 100vh;
        }

        .container {
            width: 100%;
            max-width: 850px;
        }

        /* Branding */
        .brand-header {
            text-align: center;
            margin-bottom: 30px;
        }

        .brand-logo {
            font-family: 'Outfit', sans-serif;
            font-size: 32px;
            font-weight: 800;
            color: var(--primary);
            letter-spacing: -1px;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
        }

        .brand-logo::before {
            content: '';
            display: inline-block;
            width: 16px;
            height: 16px;
            background-color: var(--primary);
            border-radius: 50%;
        }

        .brand-subtitle {
            font-size: 14px;
            color: var(--text-muted);
            font-weight: 500;
            text-transform: uppercase;
            letter-spacing: 2px;
            margin-top: 5px;
        }

        /* Card Container */
        .card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 8px 10px -6px rgba(0, 0, 0, 0.05);
            border: 1px solid var(--border);
            overflow: hidden;
            margin-bottom: 30px;
        }

        .card-header {
            background-color: var(--primary-light);
            padding: 25px 30px;
            border-bottom: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
        }

        .card-title-group h1 {
            font-family: 'Outfit', sans-serif;
            font-size: 22px;
            color: var(--primary);
            font-weight: 700;
        }

        .card-title-group p {
            font-size: 13px;
            color: var(--text-muted);
            margin-top: 2px;
        }

        .badge-status {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 12px;
            font-weight: 600;
            text-transform: uppercase;
        }

        .badge-status.pendiente {
            background-color: var(--warning-light);
            color: var(--warning);
        }

        .badge-status.aprobada {
            background-color: var(--success-light);
            color: var(--success);
        }

        .badge-status.rechazada {
            background-color: var(--danger-light);
            color: var(--danger);
        }

        /* Grid info */
        .card-body {
            padding: 30px;
        }

        .info-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 25px;
            margin-bottom: 35px;
        }

        @media (max-width: 600px) {
            .info-grid {
                grid-template-columns: 1fr;
            }
        }

        .info-section h3 {
            font-family: 'Outfit', sans-serif;
            font-size: 15px;
            color: var(--primary);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 12px;
            border-bottom: 1px solid var(--border);
            padding-bottom: 6px;
        }

        .info-item {
            display: flex;
            margin-bottom: 8px;
            font-size: 14px;
        }

        .info-label {
            font-weight: 500;
            color: var(--text-muted);
            width: 120px;
            flex-shrink: 0;
        }

        .info-value {
            color: var(--text-main);
            font-weight: 600;
        }

        /* Table Styling */
        .items-title {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 700;
            color: var(--text-main);
            margin-bottom: 15px;
        }

        .table-responsive {
            width: 100%;
            overflow-x: auto;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            margin-bottom: 35px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
            font-size: 14px;
        }

        th {
            background-color: #f1f5f9;
            color: var(--text-main);
            font-weight: 600;
            padding: 12px 16px;
            border-bottom: 2px solid var(--border);
        }

        td {
            padding: 14px 16px;
            border-bottom: 1px solid var(--border);
            color: var(--text-main);
        }

        tr:last-child td {
            border-bottom: none;
        }

        .text-right {
            text-align: right;
        }

        .total-row {
            background-color: #f8fafc;
            font-weight: 700;
        }

        .total-row td {
            border-top: 2px solid var(--border);
            color: var(--primary);
            font-size: 15px;
        }

        /* Conditions Panel */
        .conditions-panel {
            background-color: #f8fafc;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            padding: 20px;
            margin-bottom: 35px;
        }

        .conditions-title {
            font-family: 'Outfit', sans-serif;
            font-size: 14px;
            font-weight: 700;
            color: var(--primary);
            margin-bottom: 10px;
            text-transform: uppercase;
        }

        .conditions-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 15px;
        }

        .condition-item {
            font-size: 13px;
        }

        .condition-label {
            color: var(--text-muted);
            font-weight: 500;
        }

        .condition-val {
            font-weight: 600;
            color: var(--text-main);
            margin-top: 2px;
        }

        /* Action Buttons & Forms */
        .decision-actions {
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 20px;
            border-top: 1px solid var(--border);
            padding-top: 30px;
        }

        .buttons-row {
            display: flex;
            gap: 20px;
            width: 100%;
            justify-content: center;
        }

        @media (max-width: 480px) {
            .buttons-row {
                flex-direction: column;
            }
        }

        .btn {
            font-family: 'Outfit', sans-serif;
            font-size: 16px;
            font-weight: 600;
            padding: 14px 28px;
            border-radius: var(--radius-sm);
            cursor: pointer;
            border: none;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 10px;
            transition: var(--transition);
            text-decoration: none;
            width: 100%;
            max-width: 250px;
        }

        .btn-success {
            background-color: var(--success);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.2);
        }

        .btn-success:hover {
            background-color: var(--success-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px -1px rgba(16, 185, 129, 0.3);
        }

        .btn-danger {
            background-color: var(--danger);
            color: white;
            box-shadow: 0 4px 6px -1px rgba(239, 68, 68, 0.2);
        }

        .btn-danger:hover {
            background-color: var(--danger-hover);
            transform: translateY(-2px);
            box-shadow: 0 6px 8px -1px rgba(239, 68, 68, 0.3);
        }

        /* Collapsible Rejection Form */
        .rejection-form-container {
            width: 100%;
            max-height: 0;
            overflow: hidden;
            transition: max-height 0.4s ease-out, opacity 0.3s ease;
            opacity: 0;
            display: flex;
            flex-direction: column;
            gap: 15px;
            background-color: var(--danger-light);
            border-radius: var(--radius-sm);
            padding: 0;
            border: 0 solid transparent;
        }

        .rejection-form-container.active {
            max-height: 250px;
            opacity: 1;
            padding: 20px;
            border: 1px solid #fca5a5;
            margin-top: 10px;
        }

        .textarea-label {
            font-size: 14px;
            font-weight: 600;
            color: #b91c1c;
        }

        textarea {
            width: 100%;
            height: 100px;
            border: 1px solid #fca5a5;
            border-radius: var(--radius-sm);
            padding: 12px;
            font-family: inherit;
            font-size: 14px;
            resize: none;
            outline: none;
            transition: var(--transition);
        }

        textarea:focus {
            border-color: var(--danger);
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.15);
        }

        .rejection-actions {
            display: flex;
            justify-content: flex-end;
            gap: 15px;
        }

        .btn-sm {
            padding: 8px 16px;
            font-size: 14px;
            max-width: 150px;
        }

        .btn-secondary {
            background-color: #cbd5e1;
            color: #334155;
        }

        .btn-secondary:hover {
            background-color: #94a3b8;
        }

        /* Notifications & Error Cards */
        .alert-card {
            background: var(--card-bg);
            border-radius: var(--radius-md);
            padding: 40px;
            text-align: center;
            border: 1px solid var(--border);
            box-shadow: var(--shadow-md);
            width: 100%;
        }

        .alert-icon {
            width: 70px;
            height: 70px;
            border-radius: 50%;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            font-size: 32px;
            margin-bottom: 20px;
        }

        .alert-card.success .alert-icon {
            background-color: var(--success-light);
            color: var(--success);
        }

        .alert-card.danger .alert-icon {
            background-color: var(--danger-light);
            color: var(--danger);
        }

        .alert-card.info .alert-icon {
            background-color: var(--primary-light);
            color: var(--primary);
        }

        .alert-card h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 24px;
            margin-bottom: 10px;
        }

        .alert-card p {
            color: var(--text-muted);
            font-size: 16px;
            max-width: 500px;
            margin: 0 auto 25px auto;
        }

        /* Read Only Decision Banner */
        .decision-banner {
            padding: 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 30px;
            display: flex;
            align-items: flex-start;
            gap: 15px;
            border: 1px solid transparent;
        }

        .decision-banner.aprobada {
            background-color: var(--success-light);
            border-color: #a7f3d0;
            color: #065f46;
        }

        .decision-banner.rechazada {
            background-color: var(--danger-light);
            border-color: #fca5a5;
            color: #991b1b;
        }

        .decision-banner-icon {
            font-size: 24px;
            line-height: 1;
        }

        .decision-banner-title {
            font-weight: 700;
            font-size: 16px;
            margin-bottom: 4px;
        }

        .decision-banner-desc {
            font-size: 14px;
            opacity: 0.9;
        }

        /* Confirmation Modal */
        .modal {
            position: fixed;
            top: 0;
            left: 0;
            right: 0;
            bottom: 0;
            background-color: rgba(15, 23, 42, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 1000;
            opacity: 0;
            pointer-events: none;
            transition: opacity 0.3s ease;
            padding: 20px;
        }

        .modal.active {
            opacity: 1;
            pointer-events: auto;
        }

        .modal-content {
            background: white;
            border-radius: var(--radius-md);
            padding: 30px;
            max-width: 450px;
            width: 100%;
            text-align: center;
            box-shadow: var(--shadow-lg);
            transform: scale(0.9);
            transition: transform 0.3s ease;
        }

        .modal.active .modal-content {
            transform: scale(1);
        }

        .modal-title {
            font-family: 'Outfit', sans-serif;
            font-size: 20px;
            font-weight: 700;
            margin-bottom: 12px;
            color: var(--primary);
        }

        .modal-desc {
            font-size: 14px;
            color: var(--text-muted);
            margin-bottom: 25px;
        }

        .modal-actions {
            display: flex;
            gap: 15px;
            justify-content: center;
        }

        /* Error notification at top */
        .error-top {
            background-color: var(--danger-light);
            border: 1px solid #fca5a5;
            color: #b91c1c;
            padding: 15px 20px;
            border-radius: var(--radius-sm);
            margin-bottom: 25px;
            font-size: 14px;
            font-weight: 500;
            display: flex;
            align-items: center;
            gap: 10px;
        }
    </style>
</head>
<body>
    <div class="container">
        <!-- BRANDING -->
        <div class="brand-header">
            <div class="brand-logo">CYCSA</div>
            <div class="brand-subtitle">Laboratorio de Ensayos y Control de Calidad</div>
        </div>

        <?php if (isset($error) && !isset($cotizacion)): ?>
            <!-- CARD ERROR (ACCESO DENEGADO) -->
            <div class="alert-card danger">
                <div class="alert-icon">✕</div>
                <h2>Acceso Denegado</h2>
                <p><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></p>
                <div style="font-size: 14px; color: var(--text-muted);">
                    Si considera que esto es un error, por favor comuníquese con su asesor de CYCSA o envíe un correo a <a href="mailto:soporte@cycsa.com" style="color: var(--primary); font-weight: 600;">soporte@cycsa.com</a>.
                </div>
            </div>

        <?php elseif (isset($confirmacionExito) && $confirmacionExito === true): ?>
            <!-- CARD CONFIRMACION EXITO -->
            <div class="alert-card <?= $accion_exitosa === 'aceptar' ? 'success' : 'danger' ?>">
                <div class="alert-icon"><?= $accion_exitosa === 'aceptar' ? '✓' : '✕' ?></div>
                <h2><?= $accion_exitosa === 'aceptar' ? 'Propuesta Aprobada' : 'Propuesta Rechazada' ?></h2>
                
                <?php if ($accion_exitosa === 'aceptar'): ?>
                    <p>¡Muchas gracias por su aprobación! La cotización <strong><?= htmlspecialchars($cotizacion['codigo'], ENT_QUOTES, 'UTF-8') ?></strong> ha sido marcada como aprobada.</p>
                    <p style="font-size: 14px; margin-top: -15px;">Nuestro equipo técnico y comercial ha sido notificado y se pondrá en contacto con usted a la brevedad para coordinar los próximos pasos.</p>
                <?php else: ?>
                    <p>La cotización <strong><?= htmlspecialchars($cotizacion['codigo'], ENT_QUOTES, 'UTF-8') ?></strong> ha sido marcada como rechazada.</p>
                    <p style="font-size: 14px; margin-top: -15px;">Lamentamos no poder cumplir con sus expectativas en esta oportunidad. Agradecemos sus comentarios y los tendremos en cuenta para mejorar.</p>
                <?php endif; ?>

                <div style="margin-top: 25px; border-top: 1px solid var(--border); padding-top: 20px; font-size: 13px; color: var(--text-muted);">
                    CYCSA S.A. | Teléfono: +505 2244-1234 | Sitio Web: <a href="https://cycsalabs.com" target="_blank" style="color: var(--primary);">cycsalabs.com</a>
                </div>
            </div>

        <?php else: ?>
            <!-- VISTA DETALLE Y FORMULARIO -->
            
            <?php if (isset($error)): ?>
                <div class="error-top">
                    <span>⚠️</span>
                    <span><?= htmlspecialchars($error, ENT_QUOTES, 'UTF-8') ?></span>
                </div>
            <?php endif; ?>

            <!-- BANNER DE DECISIÓN YA TOMADA -->
            <?php if (isset($soloLectura) && $soloLectura === true): ?>
                <?php if ($cotizacion['estado'] === 'Aprobada por Cliente'): ?>
                    <div class="decision-banner aprobada">
                        <span class="decision-banner-icon">✓</span>
                        <div>
                            <div class="decision-banner-title">Cotización Aprobada</div>
                            <div class="decision-banner-desc">Esta propuesta económica fue <strong>APROBADA</strong> por usted. El estado actual es irreversible y ya está en proceso de ejecución.</div>
                        </div>
                    </div>
                <?php elseif ($cotizacion['estado'] === 'Rechazada por Cliente'): ?>
                    <div class="decision-banner rechazada">
                        <span class="decision-banner-icon">✕</span>
                        <div>
                            <div class="decision-banner-title">Cotización Rechazada</div>
                            <div class="decision-banner-desc">
                                Esta propuesta económica fue <strong>RECHAZADA</strong>. <br>
                                <strong>Motivo del rechazo registrado:</strong> <?= htmlspecialchars($cotizacion['motivo_rechazo_cliente'] ?? 'No especificado', ENT_QUOTES, 'UTF-8') ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
            <?php endif; ?>

            <div class="card">
                <!-- ENCABEZADO DE LA TARJETA -->
                <div class="card-header">
                    <div class="card-title-group">
                        <h1>Cotización <?= htmlspecialchars($cotizacion['codigo'], ENT_QUOTES, 'UTF-8') ?></h1>
                        <p>Versión: <?= htmlspecialchars($cotizacion['version'], ENT_QUOTES, 'UTF-8') ?> | Creado: <?= date('d/m/Y', strtotime($cotizacion['fecha_creacion'])) ?></p>
                    </div>
                    <div>
                        <?php
                        $estadoClass = 'pendiente';
                        $estadoLabel = 'Pendiente de Decisión';
                        if ($cotizacion['estado'] === 'Aprobada por Cliente') {
                            $estadoClass = 'aprobada';
                            $estadoLabel = 'Aprobada';
                        } elseif ($cotizacion['estado'] === 'Rechazada por Cliente') {
                            $estadoClass = 'rechazada';
                            $estadoLabel = 'Rechazada';
                        }
                        ?>
                        <span class="badge-status <?= $estadoClass ?>"><?= $estadoLabel ?></span>
                    </div>
                </div>

                <div class="card-body">
                    <!-- INFORMACIÓN GENERAL -->
                    <div class="info-grid">
                        <div class="info-section">
                            <h3>Información del Cliente</h3>
                            <div class="info-item">
                                <div class="info-label">Cliente:</div>
                                <div class="info-value"><?= htmlspecialchars($cotizacion['cliente_nombre'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <?php if (!empty($cotizacion['cliente_ruc'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Identificación:</div>
                                    <div class="info-value"><?= htmlspecialchars($cotizacion['cliente_ruc'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($cotizacion['atencion_a'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Atención a:</div>
                                    <div class="info-value"><?= htmlspecialchars($cotizacion['atencion_a'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            <?php endif; ?>
                            <?php if (!empty($cotizacion['cliente_email'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Email:</div>
                                    <div class="info-value"><?= htmlspecialchars($cotizacion['cliente_email'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <div class="info-section">
                            <h3>Detalles del Proyecto</h3>
                            <div class="info-item">
                                <div class="info-label">Proyecto:</div>
                                <div class="info-value"><?= htmlspecialchars($cotizacion['nombre_proyecto'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <?php if (!empty($cotizacion['direccion_proyecto'])): ?>
                                <div class="info-item">
                                    <div class="info-label">Dirección:</div>
                                    <div class="info-value"><?= htmlspecialchars($cotizacion['direccion_proyecto'], ENT_QUOTES, 'UTF-8') ?></div>
                                </div>
                            <?php endif; ?>
                            <div class="info-item">
                                <div class="info-label">Prioridad:</div>
                                <div class="info-value"><?= htmlspecialchars($cotizacion['prioridad'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- TABLA DE ITEMS -->
                    <div class="items-title">Ensayos y Servicios Solicitados</div>
                    <div class="table-responsive">
                        <table>
                            <thead>
                                <tr>
                                    <th>Descripción del Ensayo / Servicio</th>
                                    <th class="text-right" style="width: 80px;">Cant.</th>
                                    <th class="text-right" style="width: 140px;">Precio Unit.</th>
                                    <th class="text-right" style="width: 140px;">Subtotal</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($detalles as $item): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($item['descripcion_ensayo'], ENT_QUOTES, 'UTF-8') ?></td>
                                        <td class="text-right"><?= number_format($item['cantidad'], 1) ?></td>
                                        <td class="text-right">C$ <?= number_format($item['precio_unitario'], 2) ?></td>
                                        <td class="text-right">C$ <?= number_format($item['subtotal'], 2) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                                <tr class="total-row">
                                    <td colspan="3" class="text-right">Subtotal General:</td>
                                    <td class="text-right">C$ <?= number_format($cotizacion['subtotal'], 2) ?></td>
                                </tr>
                                <tr class="total-row">
                                    <td colspan="3" class="text-right">Impuesto (IVA 15%):</td>
                                    <td class="text-right">C$ <?= number_format($cotizacion['impuesto'], 2) ?></td>
                                </tr>
                                <tr class="total-row" style="font-size: 16px;">
                                    <td colspan="3" class="text-right" style="color: var(--primary);">Total a Pagar:</td>
                                    <td class="text-right" style="color: var(--primary);">C$ <?= number_format($cotizacion['total'], 2) ?></td>
                                </tr>
                            </tbody>
                        </table>
                    </div>

                    <!-- PANEL DE CONDICIONES -->
                    <div class="conditions-panel">
                        <div class="conditions-title">Condiciones Comerciales</div>
                        <div class="conditions-grid">
                            <div class="condition-item">
                                <div class="condition-label">Condición de Pago:</div>
                                <div class="condition-val"><?= htmlspecialchars($cotizacion['condicion_pago'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="condition-item">
                                <div class="condition-label">Tiempo de Entrega:</div>
                                <div class="condition-val"><?= htmlspecialchars($cotizacion['tiempo_entrega'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                            <div class="condition-item">
                                <div class="condition-label">Vigencia de Oferta:</div>
                                <div class="condition-val"><?= htmlspecialchars($cotizacion['vigencia_oferta'], ENT_QUOTES, 'UTF-8') ?></div>
                            </div>
                        </div>
                    </div>

                    <!-- FORMULARIO DE DECISIÓN (SI NO ES SOLO LECTURA) -->
                    <?php if (isset($soloLectura) && $soloLectura === false): ?>
                        <div class="decision-actions">
                            <div class="buttons-row" id="initial-buttons">
                                <button type="button" class="btn btn-success" onclick="confirmarAprobacion()">
                                    <span>✓</span> Aceptar Propuesta
                                </button>
                                <button type="button" class="btn btn-danger" onclick="mostrarFormularioRechazo()">
                                    <span>✕</span> Rechazar Propuesta
                                </button>
                            </div>

                            <!-- Formulario real oculto / colapsado para rechazo -->
                            <form id="decision-form" action="/Cycsa/publico/cotizaciones/decision-cliente" method="POST" style="width: 100%;">
                                <input type="hidden" name="id" value="<?= htmlspecialchars($cotizacion['id'], ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="token" value="<?= htmlspecialchars($token, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf_token, ENT_QUOTES, 'UTF-8') ?>">
                                <input type="hidden" name="accion" id="form-accion" value="">

                                <div class="rejection-form-container" id="rejection-container">
                                    <div class="textarea-label">Especifique el motivo del rechazo (Requerido):</div>
                                    <textarea name="motivo_rechazo" id="motivo_rechazo" placeholder="Escriba aquí sus comentarios o los motivos por los cuales no acepta la propuesta..."></textarea>
                                    <div class="rejection-actions">
                                        <button type="button" class="btn btn-secondary btn-sm" onclick="cancelarRechazo()">Cancelar</button>
                                        <button type="submit" class="btn btn-danger btn-sm" onclick="enviarRechazo(event)">Enviar Rechazo</button>
                                    </div>
                                </div>
                            </form>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- MODAL DE CONFIRMACIÓN PARA APROBACIÓN -->
    <div class="modal" id="confirm-modal">
        <div class="modal-content">
            <div class="modal-title">¿Confirmar Aprobación?</div>
            <div class="modal-desc">Al confirmar, aceptará formalmente las condiciones comerciales, los ensayos descritos y el monto total de esta cotización. El equipo de CYCSA iniciará las gestiones pertinentes.</div>
            <div class="modal-actions">
                <button class="btn btn-secondary btn-sm" onclick="cerrarModal()">Cancelar</button>
                <button class="btn btn-success btn-sm" onclick="enviarAprobacion()">Sí, Aprobar</button>
            </div>
        </div>
    </div>

    <script>
        function confirmarAprobacion() {
            document.getElementById('confirm-modal').classList.add('active');
        }

        function cerrarModal() {
            document.getElementById('confirm-modal').classList.remove('active');
        }

        function enviarAprobacion() {
            cerrarModal();
            document.getElementById('form-accion').value = 'aceptar';
            document.getElementById('decision-form').submit();
        }

        function mostrarFormularioRechazo() {
            document.getElementById('rejection-container').classList.add('active');
            document.getElementById('motivo_rechazo').focus();
            // Desplazar suavemente hasta el formulario de rechazo
            setTimeout(() => {
                document.getElementById('rejection-container').scrollIntoView({ behavior: 'smooth', block: 'nearest' });
            }, 150);
        }

        function cancelarRechazo() {
            document.getElementById('rejection-container').classList.remove('active');
            document.getElementById('motivo_rechazo').value = '';
        }

        function enviarRechazo(event) {
            const motivo = document.getElementById('motivo_rechazo').value.trim();
            if (motivo === '') {
                event.preventDefault();
                alert('Por favor, especifique el motivo del rechazo antes de enviar.');
                document.getElementById('motivo_rechazo').focus();
                return false;
            }
            document.getElementById('form-accion').value = 'rechazar';
            // Deja que el submit continúe
        }
    </script>
</body>
</html>
