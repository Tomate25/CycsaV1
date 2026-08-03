<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($titulo) ?></title>
    <!-- Google Fonts: Inter & Outfit -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    <!-- FontAwesome for Icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            --bg-primary: #f8fafc;
            --bg-card: #ffffff;
            --primary: #103487;
            --primary-hover: #0a2562;
            --primary-light: #e0eafd;
            --accent: #06b6d4;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --border: #e2e8f0;
            --shadow-sm: 0 1px 3px rgba(0,0,0,0.05);
            --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1), 0 2px 4px -1px rgba(0,0,0,0.06);
            --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1), 0 4px 6px -2px rgba(0,0,0,0.05);
            --radius-sm: 8px;
            --radius-md: 12px;
            --radius-lg: 16px;
            --success: #10b981;
            --danger: #ef4444;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-primary);
            color: var(--text-main);
            line-height: 1.6;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
        }

        h1, h2, h3, h4 {
            font-family: 'Outfit', sans-serif;
            font-weight: 700;
        }

        /* Company Letterhead (Membrete) Styles */
        .company-letterhead {
            background-color: #ffffff;
            border-bottom: 3px solid var(--primary);
            padding: 1.5rem;
            box-shadow: var(--shadow-sm);
        }

        .brand-header-container {
            max-width: 1200px;
            margin: 0 auto;
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 1.5rem;
            flex-wrap: wrap;
        }

        .brand-block {
            display: flex;
            align-items: center;
            gap: 1.25rem;
        }

        .company-logo {
            max-height: 75px;
            width: auto;
            display: block;
        }

        .brand-text {
            display: flex;
            flex-direction: column;
            justify-content: center;
            text-align: left;
        }

        .company-name {
            font-family: 'Outfit', sans-serif;
            font-size: 2.3rem;
            font-weight: 800;
            color: var(--primary);
            margin: 0;
            line-height: 1.0;
            letter-spacing: -1px;
        }

        .company-slogan {
            font-size: 0.85rem;
            font-weight: 700;
            color: var(--text-main);
            margin: 0.35rem 0 0 0;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .brand-contact-info {
            text-align: right;
            font-size: 0.8rem;
            color: var(--text-muted);
            line-height: 1.4;
        }

        .brand-contact-info span {
            display: block;
        }

        .brand-contact-info i {
            color: var(--primary);
            margin-right: 0.25rem;
        }

        .letterhead-divider {
            height: 1px;
            background-color: var(--border);
            max-width: 1200px;
            margin: 1.25rem auto;
        }

        .portal-title {
            max-width: 1200px;
            margin: 0 auto;
            text-align: center;
        }

        .portal-title h2 {
            font-family: 'Outfit', sans-serif;
            font-size: 1.8rem;
            color: var(--text-main);
            margin: 0 0 0.5rem 0;
        }

        .portal-title p {
            max-width: 800px;
            margin: 0 auto;
            font-size: 0.95rem;
            color: var(--text-muted);
            line-height: 1.5;
        }

        /* Container */
        .container {
            max-width: 1000px;
            width: 100%;
            margin: 2rem auto 3rem;
            padding: 0 1.5rem;
            flex: 1;
            z-index: 10;
        }

        .main-card {
            background-color: var(--bg-card);
            border-radius: var(--radius-lg);
            border: 1px solid var(--border);
            box-shadow: var(--shadow-lg);
            overflow: hidden;
        }

        /* Steps navigation */
        .steps-bar {
            display: flex;
            background-color: #f1f5f9;
            border-bottom: 1px solid var(--border);
            padding: 1rem 1.5rem;
        }

        .step-item {
            flex: 1;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            color: var(--text-muted);
            font-family: 'Outfit', sans-serif;
            font-weight: 600;
            font-size: 0.95rem;
            position: relative;
            padding: 0.5rem 0;
            transition: color 0.3s;
        }

        .step-item i {
            font-size: 1.1rem;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 28px;
            height: 28px;
            border-radius: 50%;
            border: 2px solid var(--text-muted);
            font-style: normal;
        }

        .step-item.active {
            color: var(--primary);
        }

        .step-item.active i {
            border-color: var(--primary);
            background-color: var(--primary);
            color: #ffffff;
        }

        .step-item.completed {
            color: var(--success);
        }

        .step-item.completed i {
            border-color: var(--success);
            background-color: var(--success);
            color: #ffffff;
        }

        .step-divider {
            width: 40px;
            height: 2px;
            background-color: var(--border);
            align-self: center;
        }

        /* Forms and step content */
        .step-content {
            padding: 2.5rem;
            display: none;
        }

        .step-content.active {
            display: block;
            animation: fadeIn 0.4s ease-in-out;
        }

        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .section-title {
            font-size: 1.4rem;
            color: var(--primary);
            margin-bottom: 1.5rem;
            border-bottom: 2px solid var(--primary-light);
            padding-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        /* Form groups */
        .form-row {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(240px, 1fr));
            gap: 1.5rem;
            margin-bottom: 1.5rem;
        }

        .form-group {
            display: flex;
            flex-direction: column;
            gap: 0.5rem;
        }

        .form-group.full-width {
            grid-column: 1 / -1;
        }

        label {
            font-weight: 600;
            font-size: 0.9rem;
            color: #334155;
        }

        input[type="text"],
        input[type="email"],
        select,
        textarea {
            width: 100%;
            padding: 0.8rem 1rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            font-family: inherit;
            font-size: 0.95rem;
            transition: all 0.3s;
            background-color: #f8fafc;
        }

        input[type="text"]:focus,
        input[type="email"]:focus,
        select:focus,
        textarea:focus {
            outline: none;
            border-color: var(--primary);
            background-color: #ffffff;
            box-shadow: 0 0 0 3px rgba(16, 52, 135, 0.15);
        }

        textarea {
            resize: vertical;
            min-height: 100px;
        }

        /* Identification search widget */
        .search-widget {
            display: flex;
            gap: 0.75rem;
            margin-bottom: 2rem;
            background: var(--primary-light);
            padding: 1.5rem;
            border-radius: var(--radius-md);
            border: 1px dashed rgba(16, 52, 135, 0.3);
            align-items: flex-end;
        }

        .search-widget .form-group {
            flex: 1;
        }

        /* Buttons */
        .btn {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
            padding: 0.8rem 1.8rem;
            font-size: 0.95rem;
            font-weight: 600;
            border-radius: var(--radius-sm);
            border: none;
            cursor: pointer;
            transition: all 0.3s;
            text-decoration: none;
            font-family: 'Outfit', sans-serif;
        }

        .btn-primary {
            background-color: var(--primary);
            color: #ffffff;
        }

        .btn-primary:hover:not(:disabled) {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(16, 52, 135, 0.2);
        }

        .btn-secondary {
            background-color: #e2e8f0;
            color: #334155;
        }

        .btn-secondary:hover:not(:disabled) {
            background-color: #cbd5e1;
        }

        .btn-success {
            background-color: var(--success);
            color: #ffffff;
        }

        .btn-success:hover:not(:disabled) {
            background-color: #059669;
            transform: translateY(-1px);
            box-shadow: 0 4px 10px rgba(16, 185, 129, 0.2);
        }

        .btn:disabled {
            opacity: 0.6;
            cursor: not-allowed;
        }

        /* Step Action Footer */
        .card-footer {
            padding: 1.5rem 2.5rem;
            background-color: #f8fafc;
            border-top: 1px solid var(--border);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        /* Product selection controls (Step 2) */
        .filter-bar {
            display: flex;
            flex-wrap: wrap;
            gap: 0.5rem;
            margin-bottom: 1.5rem;
            padding-bottom: 1rem;
            border-bottom: 1px solid var(--border);
            align-items: center;
        }

        .search-input-container {
            position: relative;
            flex: 1;
            min-width: 250px;
        }

        .search-input-container i {
            position: absolute;
            left: 1rem;
            top: 50%;
            transform: translateY(-50%);
            color: var(--text-muted);
        }

        .search-input-container input {
            padding-left: 2.5rem;
        }

        .filter-btn {
            padding: 0.5rem 1rem;
            border-radius: 20px;
            border: 1px solid var(--border);
            background: #ffffff;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: 500;
            transition: all 0.2s;
        }

        .filter-btn.active {
            background-color: var(--primary);
            color: #ffffff;
            border-color: var(--primary);
        }

        .product-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(290px, 1fr));
            gap: 1.5rem;
            max-height: 500px;
            overflow-y: auto;
            padding: 0.5rem;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            background: #f8fafc;
        }

        .product-card {
            background-color: #ffffff;
            border: 1px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
            position: relative;
        }

        .product-card:hover {
            box-shadow: var(--shadow-md);
            border-color: #cbd5e1;
        }

        .product-card.selected {
            border-color: var(--primary);
            background-color: #f0f7ff;
            box-shadow: 0 0 0 2px var(--primary);
        }

        .product-card .category-badge {
            align-self: flex-start;
            font-size: 0.75rem;
            text-transform: uppercase;
            font-weight: 700;
            padding: 0.2rem 0.6rem;
            border-radius: 12px;
            background-color: var(--primary-light);
            color: var(--primary);
            margin-bottom: 0.75rem;
        }

        .product-card .norm-badge {
            align-self: flex-start;
            font-size: 0.75rem;
            font-weight: 600;
            padding: 0.2rem 0.5rem;
            border-radius: 4px;
            background-color: #f1f5f9;
            color: #475569;
            margin-bottom: 0.5rem;
            font-family: monospace;
        }

        .product-card h4 {
            font-size: 1.1rem;
            margin-bottom: 0.5rem;
            color: var(--text-main);
        }

        .product-card p {
            font-size: 0.85rem;
            color: var(--text-muted);
            margin-bottom: 1rem;
            flex: 1;
        }

        .card-actions {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 0.5rem;
            margin-top: auto;
            border-top: 1px solid var(--border);
            padding-top: 0.75rem;
        }

        .qty-input {
            display: flex;
            align-items: center;
            border: 1px solid var(--border);
            border-radius: var(--radius-sm);
            overflow: hidden;
            width: 100px;
            height: 35px;
            background-color: #ffffff;
        }

        .qty-btn {
            width: 30px;
            height: 100%;
            border: none;
            background: #f1f5f9;
            cursor: pointer;
            font-size: 1rem;
            font-weight: bold;
            color: var(--text-main);
            transition: background 0.2s;
        }

        .qty-btn:hover {
            background-color: #e2e8f0;
        }

        .qty-val {
            flex: 1;
            text-align: center;
            font-weight: 600;
            font-size: 0.95rem;
            border: none;
            outline: none;
            width: 100%;
        }

        /* Cart summary overlay/section */
        .cart-summary {
            background-color: var(--primary-light);
            border: 1px solid var(--primary);
            border-radius: var(--radius-md);
            padding: 1.25rem;
            margin-top: 1.5rem;
        }

        .cart-summary h4 {
            color: var(--primary);
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .cart-summary ul {
            list-style: none;
            max-height: 150px;
            overflow-y: auto;
        }

        .cart-summary li {
            font-size: 0.9rem;
            display: flex;
            justify-content: space-between;
            padding: 0.3rem 0;
            border-bottom: 1px solid rgba(16, 52, 135, 0.1);
        }

        .cart-summary li:last-child {
            border-bottom: none;
        }

        /* Success & error styling */
        .alert {
            padding: 1rem 1.25rem;
            border-radius: var(--radius-sm);
            margin-bottom: 1.5rem;
            font-size: 0.95rem;
            display: flex;
            align-items: center;
            gap: 0.75rem;
        }

        .alert-error {
            background-color: #fef2f2;
            border: 1px solid #fecaca;
            color: var(--danger);
        }

        .success-container {
            text-align: center;
            padding: 4rem 2.5rem;
        }

        .success-icon {
            font-size: 5rem;
            color: var(--success);
            margin-bottom: 1.5rem;
            animation: scaleIn 0.5s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        @keyframes scaleIn {
            from { transform: scale(0); }
            to { transform: scale(1); }
        }

        .success-container h2 {
            font-size: 2.2rem;
            color: var(--primary);
            margin-bottom: 1rem;
        }

        .success-container p {
            color: var(--text-muted);
            max-width: 600px;
            margin: 0 auto 2rem;
            font-size: 1.1rem;
        }

        .folio-box {
            display: inline-block;
            background: #f0fdf4;
            border: 2px dashed var(--success);
            color: #065f46;
            font-size: 1.8rem;
            font-weight: 800;
            padding: 1rem 2.5rem;
            border-radius: var(--radius-md);
            letter-spacing: 1px;
            font-family: 'Outfit', sans-serif;
            margin-bottom: 2.5rem;
            box-shadow: 0 4px 6px -1px rgba(16, 185, 129, 0.1);
        }

        /* Footer */
        footer {
            text-align: center;
            padding: 2rem;
            color: var(--text-muted);
            font-size: 0.9rem;
            margin-top: auto;
            border-top: 1px solid var(--border);
            background-color: #ffffff;
        }

        /* Read-only existing client box */
        .existing-client-banner {
            background-color: #f0fdf4;
            border: 1px solid #bbf7d0;
            border-radius: var(--radius-md);
            padding: 1.25rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }
        
        .existing-client-banner .client-info {
            display: flex;
            flex-direction: column;
            gap: 0.25rem;
        }

        /* Loading spinner */
        .spinner {
            display: inline-block;
            width: 20px;
            height: 20px;
            border: 3px solid rgba(255,255,255,.3);
            border-radius: 50%;
            border-top-color: #fff;
            animation: spin 1s ease-in-out infinite;
        }

        @keyframes spin {
            to { transform: rotate(360deg); }
        }

        /* Option selector for Client type */
        .option-selector {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2rem;
        }

        .option-card {
            border: 2px solid var(--border);
            border-radius: var(--radius-md);
            padding: 1.5rem;
            text-align: center;
            cursor: pointer;
            transition: all 0.3s;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.5rem;
            background-color: #ffffff;
        }

        .option-card:hover {
            border-color: #cbd5e1;
            background-color: #f8fafc;
            transform: translateY(-2px);
            box-shadow: var(--shadow-sm);
        }

        .option-card.selected {
            border-color: var(--primary);
            background-color: var(--primary-light);
            color: var(--primary);
        }

        .option-card i {
            font-size: 2rem;
            color: var(--text-muted);
            transition: color 0.3s;
        }

        .option-card.selected i {
            color: var(--primary);
        }

        .option-card strong {
            font-family: 'Outfit', sans-serif;
            font-size: 1.1rem;
        }

        .option-card p {
            font-size: 0.85rem;
            color: var(--text-muted);
        }

        /* Checkmark Badge for Selected Products */
        .product-card .select-badge {
            position: absolute;
            top: 1rem;
            right: 1rem;
            color: var(--success);
            font-size: 1.3rem;
            opacity: 0;
            transform: scale(0.5);
            transition: all 0.25s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            pointer-events: none;
        }

        .product-card.selected .select-badge {
            opacity: 1;
            transform: scale(1);
        }

        /* 📱 RESPONSIVE ADJUSTMENTS FOR EVERYTHING */
        @media (max-width: 992px) {
            .brand-header-container {
                flex-direction: column;
                align-items: center;
                text-align: center;
                gap: 1.25rem;
            }
            .brand-contact-info {
                text-align: center;
                align-items: center;
                width: 100%;
            }
            .brand-contact-info span {
                display: inline-block;
                margin: 0 0.5rem;
            }
        }

        @media (max-width: 768px) {
            .company-letterhead {
                padding: 1.25rem 1rem;
            }
            .company-logo {
                max-height: 55px;
            }
            .company-name {
                font-size: 1.7rem;
            }
            .company-slogan {
                font-size: 0.75rem;
            }
            .container {
                margin-top: 1.5rem;
                padding: 0 0.75rem;
            }
            .steps-bar {
                flex-direction: column;
                gap: 0.5rem;
                padding: 0.75rem 1rem;
            }
            .step-divider {
                display: none;
            }
            .step-item {
                justify-content: flex-start;
                font-size: 0.85rem;
                padding: 0.2rem 0;
            }
            .step-content {
                padding: 1.25rem;
            }
            .search-widget {
                flex-direction: column;
                align-items: stretch;
                gap: 1rem;
            }
            .search-widget button {
                width: 100%;
                height: auto;
            }
            .filter-bar {
                flex-direction: column;
                align-items: stretch;
                gap: 0.75rem;
            }
            .search-input-container {
                width: 100%;
            }
            .product-grid {
                grid-template-columns: 1fr; /* Single column on mobile */
                max-height: none; /* No nested scrollbar scroll trap on mobile */
                gap: 1rem;
            }
            .product-card {
                padding: 1rem;
            }
            .card-footer {
                padding: 1rem 1.25rem;
                flex-direction: row;
                gap: 1rem;
            }
            .btn {
                padding: 0.75rem 1rem;
                font-size: 0.85rem;
                flex: 1;
            }
            .option-selector {
                grid-template-columns: 1fr;
                gap: 1rem;
            }
        }

        @media (max-width: 480px) {
            .brand-block {
                gap: 0.75rem;
            }
            .company-logo {
                max-height: 42px;
            }
            .company-name {
                font-size: 1.3rem;
            }
            .company-slogan {
                font-size: 0.65rem;
            }
            .brand-contact-info {
                font-size: 0.75rem;
            }
            .brand-contact-info span {
                display: block;
                margin: 0.2rem 0;
            }
            .portal-title h2 {
                font-size: 1.25rem;
            }
            .portal-title p {
                font-size: 0.8rem;
            }
        }
    </style>
</head>
<body>

    <header class="company-letterhead">
        <div class="brand-header-container">
            <div class="brand-block">
                <img src="/Cycsa/publico/img/logo.png" alt="Logo CYCSA" class="company-logo">
                <div class="brand-text">
                    <h1 class="company-name">CYCSA S.A.</h1>
                    <p class="company-slogan">Control y Calidad de Materiales de Construcción</p>
                </div>
            </div>
            <div class="brand-contact-info">
                <span><i class="fa-solid fa-certificate"></i> Laboratorio Acreditado ISO/IEC 17025</span>
                <span><i class="fa-solid fa-location-dot"></i> Km 9.5 Carretera Nueva a León, Managua, Nicaragua</span>
                <span><i class="fa-solid fa-phone"></i> +505 2269-0222 | <i class="fa-solid fa-envelope"></i> info@cycsanic.com</span>
            </div>
        </div>
        <div class="letterhead-divider"></div>
        <div class="portal-title">
            <h2>Solicitud de Cotización en Línea</h2>
            <p>Complete el siguiente formulario interactivo para registrar su solicitud. Nuestro personal comercial la revisará, aplicará las tarifas del catálogo y se pondrá en contacto con usted a la brevedad.</p>
        </div>
    </header>

    <div class="container">
        <div class="main-card">
            
            <?php if (isset($exitoCodigo) && $exitoCodigo !== null): ?>
                <!-- SUCCESS VIEW -->
                <div class="success-container">
                    <div class="success-icon">
                        <i class="fa-solid fa-circle-check"></i>
                    </div>
                    <h2>¡Solicitud Recibida!</h2>
                    <p>Hemos registrado su solicitud de cotización correctamente en nuestro sistema en estado borrador. Un asesor comercial revisará los ítems, aplicará tarifas correspondientes y se pondrá en contacto con usted.</p>
                    <div style="font-size: 1rem; color: var(--text-muted); margin-bottom: 0.5rem; font-weight: 600;">CÓDIGO DE SEGUIMIENTO:</div>
                    <div class="folio-box">
                        <?= htmlspecialchars($exitoCodigo) ?>
                    </div>
                    <div>
                        <a href="/Cycsa/publico/solicitar-cotizacion" class="btn btn-primary">
                            <i class="fa-solid fa-plus"></i> Nueva Solicitud
                        </a>
                    </div>
                </div>

            <?php else: ?>
                <!-- STEPS BAR -->
                <div class="steps-bar">
                    <div class="step-item active" id="step-tab-1">
                        <i>1</i> Datos de Cliente
                    </div>
                    <div class="step-divider"></div>
                    <div class="step-item" id="step-tab-2">
                        <i>2</i> Selección de Ensayos
                    </div>
                    <div class="step-divider"></div>
                    <div class="step-item" id="step-tab-3">
                        <i>3</i> Información del Proyecto
                    </div>
                </div>

                <form action="/Cycsa/publico/solicitar-cotizacion" method="POST" id="solicitudForm">
                    <!-- HIDDEN FIELD FOR CLIENT ID (either 'new' or numeric id) -->
                    <input type="hidden" name="id_cliente" id="id_cliente" value="new">

                    <!-- STEP 1: CLIENT IDENTIFICATION AND REGISTRATION -->
                    <div class="step-content active" id="step-content-1">
                        <h3 class="section-title"><i class="fa-solid fa-id-card"></i> Identificación del Cliente</h3>
                        
                        <?php if (isset($_GET['error'])): ?>
                            <div class="alert alert-error">
                                <i class="fa-solid fa-circle-exclamation"></i>
                                <?= htmlspecialchars($_GET['error']) ?>
                            </div>
                        <?php endif; ?>
                        
                        <div id="clientErrorMsg" class="alert alert-error" style="display: none;">
                            <i class="fa-solid fa-circle-exclamation"></i>
                            <span class="msg-text"></span>
                        </div>

                        <!-- 🗳️ Toggle Selector for Registered vs New Customer -->
                        <div class="option-selector">
                            <div class="option-card selected" id="optRegistered">
                                <i class="fa-solid fa-user-check"></i>
                                <strong>Ya soy cliente</strong>
                                <p>Ingresar RUC o Cédula registrado</p>
                            </div>
                            <div class="option-card" id="optNew">
                                <i class="fa-solid fa-user-plus"></i>
                                <strong>Soy cliente nuevo</strong>
                                <p>Registrar mis datos por primera vez</p>
                            </div>
                        </div>

                        <!-- Search Section (Only shown if "Ya soy cliente" is selected) -->
                        <div id="searchSection">
                            <p style="margin-bottom: 1.5rem; color: var(--text-muted);">
                                Ingrese su RUC o Cédula para verificar si ya está registrado en nuestra base de datos.
                            </p>

                            <div class="search-widget">
                                <div class="form-group">
                                    <label for="identificacion_buscar">Número de RUC o Cédula</label>
                                    <input type="text" id="identificacion_buscar" placeholder="Ej: 001-010101-0001A o RUC...">
                                </div>
                                <button type="button" class="btn btn-primary" id="btnBuscarCliente" style="height: 43px;">
                                    <i class="fa-solid fa-magnifying-glass"></i> Buscar
                                </button>
                            </div>
                        </div>

                        <!-- Existing Client Banner (Hidden by default) -->
                        <div class="existing-client-banner" id="existingClientBanner" style="display: none;">
                            <div class="client-info">
                                <span style="font-weight: 700; color: #15803d; font-size: 1.1rem;"><i class="fa-solid fa-circle-check"></i> Cliente Encontrado</span>
                                <strong id="lblNombreCliente">---</strong>
                                <span id="lblEmailCliente" style="font-size: 0.9rem; color: var(--text-muted);">---</span>
                                <span id="lblIdentCliente" style="font-size: 0.85rem; color: var(--text-muted); font-family: monospace;">---</span>
                            </div>
                            <button type="button" class="btn btn-secondary btn-sm" id="btnCambiarCliente">
                                <i class="fa-solid fa-xmark"></i> Cambiar
                            </button>
                        </div>
                        
                        <!-- Client Form Fields (Shown if client is new, disabled if client exists) -->
                        <div id="newClientForm" style="display: none;">
                            <h3 class="section-title" style="margin-top: 2rem;"><i class="fa-solid fa-user-plus"></i> Registrar Datos de Cliente</h3>
                            
                            <div class="form-row">
                                <div class="form-group">
                                    <label for="tipo_cliente">Tipo de Persona *</label>
                                    <select name="tipo_cliente" id="tipo_cliente">
                                        <option value="Jurídico">Jurídica / Empresa</option>
                                        <option value="Natural">Natural / Persona</option>
                                    </select>
                                </div>
                                <div class="form-group">
                                    <label for="identificacion" id="lblIdentificacionTipo">Número de RUC *</label>
                                    <input type="text" name="identificacion" id="identificacion" placeholder="Ej: J0310000000000" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group" style="grid-column: span 2;">
                                    <label for="nombre_cliente" id="lblNombreComercial">Nombre o Razón Social *</label>
                                    <input type="text" name="nombre_cliente" id="nombre_cliente" placeholder="Nombre completo de la empresa o persona" required>
                                </div>
                            </div>

                            <div class="form-row">
                                <div class="form-group">
                                    <label for="email">Correo Electrónico *</label>
                                    <input type="email" name="email" id="email" placeholder="Para enviarle la cotización final" required>
                                </div>
                                <div class="form-group">
                                    <label for="telefono">Teléfono *</label>
                                    <input type="text" name="telefono" id="telefono" placeholder="Número de contacto" required>
                                </div>
                            </div>

                            <div class="form-group" style="margin-bottom: 1.5rem;">
                                <label for="direccion">Dirección Fiscal / Domicilio *</label>
                                <textarea name="direccion" id="direccion" placeholder="Dirección física completa" required></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- STEP 2: SELECT SERVICES/PRODUCT -->
                    <div class="step-content" id="step-content-2">
                        <h3 class="section-title"><i class="fa-solid fa-flask"></i> Catálogo de Ensayos Disponibles</h3>
                        <p style="margin-bottom: 1.5rem; color: var(--text-muted);">
                            Seleccione los ensayos que desea cotizar y especifique la cantidad de muestras/ensayos requeridos.
                        </p>

                        <div class="filter-bar">
                            <div class="search-input-container">
                                <i class="fa-solid fa-magnifying-glass"></i>
                                <input type="text" id="txtBuscarProducto" placeholder="Buscar ensayo o servicio por nombre...">
                            </div>
                            <button type="button" class="filter-btn active" data-category="ALL">Todos</button>
                            <?php foreach ($categorias as $cat): ?>
                                <button type="button" class="filter-btn" data-category="<?= htmlspecialchars($cat) ?>">
                                    <?= htmlspecialchars($cat) ?>
                                </button>
                            <?php endforeach; ?>
                        </div>

                        <!-- Grid of Products -->
                        <div class="product-grid" id="productGridContainer">
                            <?php foreach ($productos as $p): ?>
                                <div class="product-card" data-id="<?= $p['id'] ?>" data-category="<?= htmlspecialchars($p['matriz_tipo'] ?? '') ?>" data-search="<?= htmlspecialchars(strtolower($p['ensayo_servicio'] . ' ' . ($p['nombre_comercial'] ?? '') . ' ' . ($p['norma_astm'] ?? ''))) ?>">
                                    <!-- select checkmark badge -->
                                    <div class="select-badge"><i class="fa-solid fa-circle-check"></i></div>
                                    <div>
                                        <div style="display: flex; gap: 0.5rem; flex-wrap: wrap;">
                                            <span class="category-badge"><?= htmlspecialchars($p['matriz_tipo'] ?? 'General') ?></span>
                                            <?php if (!empty($p['norma_astm'])): ?>
                                                <span class="norm-badge"><?= htmlspecialchars($p['norma_astm']) ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php 
                                        $nombreAMostrar = !empty($p['nombre_comercial']) ? $p['nombre_comercial'] : $p['ensayo_servicio'];
                                        ?>
                                        <h4><?= htmlspecialchars($nombreAMostrar) ?></h4>
                                    </div>
                                    <div class="card-actions">
                                        <label style="font-size:0.8rem; color: var(--text-muted);">Cantidad:</label>
                                        <div class="qty-input">
                                            <button type="button" class="qty-btn btn-minus" data-id="<?= $p['id'] ?>">-</button>
                                            <input type="text" class="qty-val" name="productos[<?= $p['id'] ?>][cantidad]" id="qty_<?= $p['id'] ?>" value="0" readonly>
                                            <button type="button" class="qty-btn btn-plus" data-id="<?= $p['id'] ?>">+</button>
                                        </div>
                                    </div>
                                    <!-- Inner optional notes for this item -->
                                    <div class="item-notes-container" style="margin-top: 0.75rem; display: none;" id="notes_container_<?= $p['id'] ?>">
                                        <textarea name="productos[<?= $p['id'] ?>][observaciones]" style="padding: 0.4rem 0.6rem; font-size: 0.8rem; min-height: 50px;" placeholder="Detalles específicos para este ensayo..."></textarea>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <!-- Summary of selected items -->
                        <div class="cart-summary" id="cartSummary" style="display: none;">
                            <h4><i class="fa-solid fa-list-check"></i> Ensayos Seleccionados</h4>
                            <ul id="selectedItemsList"></ul>
                        </div>
                    </div>

                    <!-- STEP 3: PROJECT INFORMATION -->
                    <div class="step-content" id="step-content-3">
                        <h3 class="section-title"><i class="fa-solid fa-folder-open"></i> Detalles del Proyecto</h3>
                        <p style="margin-bottom: 1.5rem; color: var(--text-muted);">
                            Proporcione información sobre la obra o proyecto donde se realizarán los ensayos para una cotización más precisa.
                        </p>

                        <div class="form-row">
                            <div class="form-group" style="grid-column: span 2;">
                                <label for="nombre_proyecto">Nombre del Proyecto / Obra *</label>
                                <input type="text" name="nombre_proyecto" id="nombre_proyecto" placeholder="Ej: Condominio Las Colinas - Fase 2" required>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group" style="grid-column: span 2;">
                                <label for="direccion_proyecto">Ubicación / Dirección de la Obra *</label>
                                <textarea name="direccion_proyecto" id="direccion_proyecto" placeholder="Dirección exacta donde se tomarán las muestras o se entregará el servicio" required></textarea>
                            </div>
                        </div>

                        <div class="form-row">
                            <div class="form-group">
                                <label for="atencion_a">Atención A (Destinatario) *</label>
                                <input type="text" name="atencion_a" id="atencion_a" placeholder="Ej: Ing. Juan Pérez" required>
                            </div>
                            <div class="form-group">
                                <label for="prioridad">Urgencia Requerida</label>
                                <select name="prioridad" id="prioridad">
                                    <option value="Normal">Normal</option>
                                    <option value="Media">Media / Moderada</option>
                                    <option value="Alta">Alta / Urgente</option>
                                </select>
                            </div>
                        </div>

                        <div class="form-group" style="margin-bottom: 1.5rem;">
                            <label for="contactos">Contactos Adicionales en Obra (Nombre y Teléfono)</label>
                            <textarea name="contactos" id="contactos" placeholder="Ej: Residente de Obra: Pedro Gómez (8888-8888)"></textarea>
                        </div>
                    </div>

                    <!-- CARD FOOTER ACTIONS -->
                    <div class="card-footer">
                        <button type="button" class="btn btn-secondary" id="btnBack" style="visibility: hidden;">
                            <i class="fa-solid fa-arrow-left"></i> Atrás
                        </button>
                        <button type="button" class="btn btn-primary" id="btnNext">
                            Siguiente <i class="fa-solid fa-arrow-right"></i>
                        </button>
                        <button type="submit" class="btn btn-success" id="btnSubmit" style="display: none;">
                            <i class="fa-solid fa-paper-plane"></i> Enviar Solicitud
                        </button>
                    </div>
                </form>
            <?php endif; ?>

        </div>
    </div>

    <footer>
        <p>&copy; 2026 CYCSA S.A. Laboratorio de Ensayos de Materiales. Todos los derechos reservados.</p>
        <p style="font-size: 0.8rem; margin-top: 0.5rem; opacity: 0.8;">Bajo norma internacional ISO/IEC 17025</p>
    </footer>

    <script>
        let currentStep = 1;
        const totalSteps = 3;

        document.addEventListener("DOMContentLoaded", function() {
            // STEP 1 WIZARD LOGIC
            const btnBuscar = document.getElementById("btnBuscarCliente");
            const btnCambiar = document.getElementById("btnCambiarCliente");
            const btnNext = document.getElementById("btnNext");
            const btnBack = document.getElementById("btnBack");
            const btnSubmit = document.getElementById("btnSubmit");
            const identBuscar = document.getElementById("identificacion_buscar");
            const inputIdCliente = document.getElementById("id_cliente");
            const existingClientBanner = document.getElementById("existingClientBanner");
            const newClientForm = document.getElementById("newClientForm");
            
            const txtNombreCliente = document.getElementById("nombre_cliente");
            const txtEmail = document.getElementById("email");
            const txtTelefono = document.getElementById("telefono");
            const txtDireccion = document.getElementById("direccion");
            const selectTipo = document.getElementById("tipo_cliente");
            const txtIdent = document.getElementById("identificacion");
            const clientErrorMsg = document.getElementById("clientErrorMsg");

            // Option selector elements
            const optRegistered = document.getElementById("optRegistered");
            const optNew = document.getElementById("optNew");
            const searchSection = document.getElementById("searchSection");

            // Función para activar o desactivar el atributo required y disabled en campos de nuevo cliente
            function setNewClientFieldsRequired(required) {
                txtIdent.required = required;
                txtNombreCliente.required = required;
                txtEmail.required = required;
                txtTelefono.required = required;
                txtDireccion.required = required;
                
                txtIdent.disabled = !required;
                txtNombreCliente.disabled = !required;
                txtEmail.disabled = !required;
                txtTelefono.disabled = !required;
                txtDireccion.disabled = !required;
                selectTipo.disabled = !required;
            }

            // Desactivar al inicio puesto que empieza en "Ya soy cliente" y el formulario nuevo está oculto
            setNewClientFieldsRequired(false);

            // Handle option toggles
            optRegistered.addEventListener("click", function() {
                optRegistered.classList.add("selected");
                optNew.classList.remove("selected");
                
                searchSection.style.display = "block";
                newClientForm.style.display = "none";
                existingClientBanner.style.display = "none";
                
                inputIdCliente.value = "new";
                identBuscar.value = "";
                identBuscar.focus();
                
                clientErrorMsg.style.display = "none";
                btnNext.disabled = false;
                txtIdent.style.borderColor = "var(--border)";
                
                setNewClientFieldsRequired(false);
            });

            optNew.addEventListener("click", function() {
                optNew.classList.add("selected");
                optRegistered.classList.remove("selected");
                
                searchSection.style.display = "none";
                newClientForm.style.display = "block";
                existingClientBanner.style.display = "none";
                
                inputIdCliente.value = "new";
                txtIdent.value = "";
                txtNombreCliente.value = "";
                txtEmail.value = "";
                txtTelefono.value = "";
                txtDireccion.value = "";
                
                clientErrorMsg.style.display = "none";
                btnNext.disabled = false;
                txtIdent.style.borderColor = "var(--border)";
                txtIdent.focus();
                
                setNewClientFieldsRequired(true);
            });

            // Async Validation for unique ID (RUC / Cédula) in new client form
            let identTimeout = null;
            txtIdent.addEventListener("input", function() {
                clearTimeout(identTimeout);
                identTimeout = setTimeout(validateUniqueIdent, 600);
            });
            txtIdent.addEventListener("blur", validateUniqueIdent);

            function validateUniqueIdent() {
                const val = txtIdent.value.trim();
                if (val === '') return;

                fetch(`/Cycsa/publico/api/clientes/buscar-por-identificacion?identificacion=${encodeURIComponent(val)}`)
                    .then(res => res.json())
                    .then(data => {
                        if (data.existe) {
                            showClientError(`Esta identificación (${val}) ya se encuentra registrada en el sistema. Por favor, marque "Ya soy cliente" para buscar sus datos.`);
                            txtIdent.style.borderColor = "var(--danger)";
                            btnNext.disabled = true;
                        } else {
                            if (clientErrorMsg.style.display === "flex" && clientErrorMsg.querySelector(".msg-text").innerText.includes("ya se encuentra registrada")) {
                                clientErrorMsg.style.display = "none";
                            }
                            txtIdent.style.borderColor = "var(--border)";
                            btnNext.disabled = false;
                        }
                    })
                    .catch(err => console.error(err));
            }

            // Change form elements according to natural/juridico
            selectTipo.addEventListener("change", function() {
                const isNatural = selectTipo.value === 'Natural';
                document.getElementById("lblNombreComercial").innerText = isNatural ? "Nombre Completo *" : "Nombre o Razón Social *";
                document.getElementById("lblIdentificacionTipo").innerText = isNatural ? "Número de Cédula *" : "Número de RUC *";
                txtIdent.placeholder = isNatural ? "Ej: 001-000000-0000A" : "Ej: J0310000000000";
                
                // Toggle required fields
                txtNombreCliente.required = true;
                txtEmail.required = true;
                txtTelefono.required = true;
                txtDireccion.required = true;
            });

            btnBuscar.addEventListener("click", function() {
                const val = identBuscar.value.trim();
                if (val === '') {
                    showClientError("Por favor ingrese un número de identificación válido.");
                    return;
                }
                
                // Show loading state
                btnBuscar.disabled = true;
                btnBuscar.innerHTML = '<span class="spinner"></span> Buscando...';
                clientErrorMsg.style.display = "none";

                fetch(`/Cycsa/publico/api/clientes/buscar-por-identificacion?identificacion=${encodeURIComponent(val)}`)
                    .then(res => res.json())
                    .then(data => {
                        btnBuscar.disabled = false;
                        btnBuscar.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Buscar';

                        if (data.existe) {
                            // Client exists in DB
                            inputIdCliente.value = data.cliente.id;
                            
                            document.getElementById("lblNombreCliente").innerText = data.cliente.nombre_razon_social;
                            document.getElementById("lblEmailCliente").innerText = data.cliente.email || 'Sin correo registrado';
                            document.getElementById("lblIdentCliente").innerText = data.cliente.numero_ruc || data.cliente.numero_cedula || data.cliente.identificacion;
                            
                            // Visuals
                            existingClientBanner.style.display = "flex";
                            newClientForm.style.display = "none";
                            searchSection.style.display = "none";
                            
                            setNewClientFieldsRequired(false);
                        } else {
                            // Client is new, show registry form
                            inputIdCliente.value = "new";
                            txtIdent.value = val;
                            
                            // Detect type based on identifier format
                            const cleanVal = val.replace(/[-]/g, '');
                            if (cleanVal.length >= 14 && isNaN(cleanVal.slice(-1))) {
                                selectTipo.value = "Natural";
                            } else {
                                selectTipo.value = "Jurídico";
                            }
                            selectTipo.dispatchEvent(new Event('change'));

                            existingClientBanner.style.display = "none";
                            newClientForm.style.display = "block";
                            searchSection.style.display = "none";
                            
                            setNewClientFieldsRequired(true);
                        }
                    })
                    .catch(err => {
                        console.error(err);
                        btnBuscar.disabled = false;
                        btnBuscar.innerHTML = '<i class="fa-solid fa-magnifying-glass"></i> Buscar';
                        showClientError("Error al conectar con el servidor. Intente de nuevo.");
                    });
            });

            btnCambiar.addEventListener("click", function() {
                inputIdCliente.value = "new";
                existingClientBanner.style.display = "none";
                
                if (optRegistered.classList.contains("selected")) {
                    searchSection.style.display = "block";
                    identBuscar.value = "";
                    identBuscar.focus();
                    setNewClientFieldsRequired(false);
                } else {
                    newClientForm.style.display = "block";
                    txtIdent.value = "";
                    txtIdent.focus();
                    setNewClientFieldsRequired(true);
                }
            });

            function showClientError(msg) {
                clientErrorMsg.style.display = "flex";
                clientErrorMsg.querySelector(".msg-text").innerText = msg;
            }

            // WIZARD NAVIGATION LOGIC
            btnNext.addEventListener("click", function() {
                if (currentStep === 1) {
                    // Validation of Step 1
                    if (inputIdCliente.value === 'new' && newClientForm.style.display === 'none' && existingClientBanner.style.display === 'none') {
                        showClientError("Debe verificar su identificación buscando su RUC/Cédula o seleccionar 'Soy cliente nuevo'.");
                        return;
                    }
                    if (inputIdCliente.value === 'new') {
                        // Check HTML5 inputs
                        if (!txtIdent.value.trim() || !txtNombreCliente.value.trim() || !txtEmail.value.trim() || !txtTelefono.value.trim() || !txtDireccion.value.trim()) {
                            showClientError("Por favor complete todos los campos obligatorios del cliente.");
                            return;
                        }
                        if (!txtEmail.validity.valid) {
                            showClientError("Ingrese un correo electrónico válido.");
                            return;
                        }
                        if (btnNext.disabled) {
                            showClientError("Por favor corrija la identificación duplicada antes de continuar.");
                            return;
                        }
                    }
                    clientErrorMsg.style.display = "none";
                } else if (currentStep === 2) {
                    // Validation of Step 2 (Selected at least one product)
                    const totalSelected = getSelectedProductsCount();
                    if (totalSelected === 0) {
                        alert("Por favor, seleccione al menos un ensayo/producto de la lista.");
                        return;
                    }
                }

                currentStep++;
                updateWizard();
            });

            btnBack.addEventListener("click", function() {
                currentStep--;
                updateWizard();
            });

            function updateWizard() {
                // Toggle active step content
                for (let i = 1; i <= totalSteps; i++) {
                    const content = document.getElementById(`step-content-${i}`);
                    const tab = document.getElementById(`step-tab-${i}`);
                    if (i === currentStep) {
                        content.classList.add("active");
                        tab.classList.add("active");
                        tab.classList.remove("completed");
                    } else {
                        content.classList.remove("active");
                        if (i < currentStep) {
                            tab.classList.add("completed");
                            tab.classList.remove("active");
                        } else {
                            tab.classList.remove("active", "completed");
                        }
                    }
                }

                // Adjust buttons
                btnBack.style.visibility = (currentStep === 1) ? "hidden" : "visible";
                if (currentStep === totalSteps) {
                    btnNext.style.display = "none";
                    btnSubmit.style.display = "inline-flex";
                } else {
                    btnNext.style.display = "inline-flex";
                    btnSubmit.style.display = "none";
                }

                // Scroll to top of card smoothly
                document.querySelector(".main-card").scrollIntoView({ behavior: 'smooth' });
            }

            // STEP 2 PRODUCT SELECTION LOGIC
            const searchInput = document.getElementById("txtBuscarProducto");
            const filterBtns = document.querySelectorAll(".filter-btn");
            const cards = document.querySelectorAll(".product-grid .product-card");

            // Search product name
            searchInput.addEventListener("input", filterProducts);

            // Filter category
            filterBtns.forEach(btn => {
                btn.addEventListener("click", function() {
                    filterBtns.forEach(b => b.classList.remove("active"));
                    btn.classList.add("active");
                    filterProducts();
                });
            });

            function filterProducts() {
                const query = searchInput.value.toLowerCase().trim();
                const activeCategoryBtn = document.querySelector(".filter-btn.active");
                const category = activeCategoryBtn ? activeCategoryBtn.dataset.category : "ALL";

                cards.forEach(card => {
                    const matchesSearch = card.dataset.search.includes(query);
                    const matchesCategory = (category === "ALL" || card.dataset.category === category);

                    if (matchesSearch && matchesCategory) {
                        card.style.display = "flex";
                    } else {
                        card.style.display = "none";
                    }
                });
            }

            // Plus & Minus buttons inside cards
            document.querySelectorAll(".btn-plus").forEach(btn => {
                btn.addEventListener("click", function() {
                    const id = btn.dataset.id;
                    const input = document.getElementById(`qty_${id}`);
                    let val = parseInt(input.value) || 0;
                    val++;
                    input.value = val;
                    
                    const card = document.querySelector(`.product-card[data-id="${id}"]`);
                    card.classList.add("selected");
                    document.getElementById(`notes_container_${id}`).style.display = "block";
                    
                    updateCartSummary();
                });
            });

            document.querySelectorAll(".btn-minus").forEach(btn => {
                btn.addEventListener("click", function() {
                    const id = btn.dataset.id;
                    const input = document.getElementById(`qty_${id}`);
                    let val = parseInt(input.value) || 0;
                    if (val > 0) {
                        val--;
                        input.value = val;
                    }
                    
                    if (val === 0) {
                        const card = document.querySelector(`.product-card[data-id="${id}"]`);
                        card.classList.remove("selected");
                        document.getElementById(`notes_container_${id}`).style.display = "none";
                    }
                    
                    updateCartSummary();
                });
            });

            // Toggle selection when tapping anywhere on the card body (excluding actions/inputs)
            cards.forEach(card => {
                card.style.cursor = "pointer"; // visual cue
                card.addEventListener("click", function(e) {
                    if (e.target.closest(".card-actions") || e.target.closest(".item-notes-container")) {
                        return; // Ignore clicks inside controls
                    }
                    const id = card.dataset.id;
                    const input = document.getElementById(`qty_${id}`);
                    let val = parseInt(input.value) || 0;
                    
                    if (val === 0) {
                        input.value = 1;
                        card.classList.add("selected");
                        document.getElementById(`notes_container_${id}`).style.display = "block";
                    } else {
                        input.value = 0;
                        card.classList.remove("selected");
                        document.getElementById(`notes_container_${id}`).style.display = "none";
                    }
                    updateCartSummary();
                });
            });

            function getSelectedProductsCount() {
                let count = 0;
                document.querySelectorAll(".qty-val").forEach(input => {
                    if (parseInt(input.value) > 0) count++;
                });
                return count;
            }

            function updateCartSummary() {
                const list = document.getElementById("selectedItemsList");
                const summaryDiv = document.getElementById("cartSummary");
                list.innerHTML = "";
                
                let selectedItems = [];
                document.querySelectorAll(".qty-val").forEach(input => {
                    const val = parseInt(input.value) || 0;
                    if (val > 0) {
                        const id = input.id.split("_")[1];
                        const card = document.querySelector(`.product-card[data-id="${id}"]`);
                        const title = card.querySelector("h4").innerText;
                        selectedItems.push({ title: title, qty: val });
                    }
                });

                if (selectedItems.length > 0) {
                    selectedItems.forEach(item => {
                        const li = document.createElement("li");
                        li.innerHTML = `<span>${item.title}</span> <strong>x${item.qty}</strong>`;
                        list.appendChild(li);
                    });
                    summaryDiv.style.display = "block";
                } else {
                    summaryDiv.style.display = "none";
                }
            }

            // Form Submit validation
            document.getElementById("solicitudForm").addEventListener("submit", function(e) {
                // Final validation of inputs
                const totalSelected = getSelectedProductsCount();
                if (totalSelected === 0) {
                    e.preventDefault();
                    alert("Debe seleccionar al menos un producto/ensayo.");
                    return;
                }
                const nameProj = document.getElementById("nombre_proyecto").value.trim();
                const dirProj = document.getElementById("direccion_proyecto").value.trim();
                const attn = document.getElementById("atencion_a").value.trim();
                if (!nameProj || !dirProj || !attn) {
                    e.preventDefault();
                    alert("Por favor complete los campos obligatorios del proyecto.");
                    return;
                }
            });

            // --- MÁSCARA AUTOMÁTICA DE IDENTIFICACIÓN (CÉDULA / RUC) ---
            function aplicarMascaraIdentificacion(input) {
                if (!input) return;
                
                input.addEventListener('input', function(e) {
                    let value = input.value.replace(/-/g, ''); // Eliminar guiones actuales para formatear
                    
                    // Si empieza con número, aplicamos formato de Cédula: ###-######-####A
                    if (/^\d/.test(value)) {
                        let formatted = '';
                        if (value.length > 0) {
                            formatted += value.substring(0, 3);
                        }
                        if (value.length > 3) {
                            formatted += '-' + value.substring(3, 9);
                        }
                        if (value.length > 9) {
                            formatted += '-' + value.substring(9, 13);
                        }
                        if (value.length > 13) {
                            formatted += value.substring(13, 14).toUpperCase();
                        }
                        input.value = formatted;
                    } else {
                        // Si es RUC Jurídico (comienza con letras), limitamos a 14 caracteres alfanuméricos
                        input.value = value.substring(0, 14);
                    }
                });

                // Permitir borrar guiones con Backspace de forma fluida sin atascos
                input.addEventListener('keydown', function(e) {
                    const key = e.key;
                    if (key === 'Backspace') {
                        const val = input.value;
                        const start = input.selectionStart;
                        if (start > 0 && val[start - 1] === '-') {
                            e.preventDefault();
                            input.value = val.substring(0, start - 2) + val.substring(start);
                            input.setSelectionRange(start - 2, start - 2);
                        }
                    }
                });
            }

            // Aplicar la máscara a los campos correspondientes
            aplicarMascaraIdentificacion(identBuscar);
            aplicarMascaraIdentificacion(txtIdent);
        });
    </script>
</body>
</html>
