<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'Cycsa ERP' ?></title>
    
    <link rel="shortcut icon" href="/Cycsa/publico/img/logo.png" type="image/png">
    
    <!-- Google Fonts: Inter & Outfit -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&family=Outfit:wght@400;500;600;700;800&display=swap" rel="stylesheet">
    
    <!-- FontAwesome para íconos -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            /* Variables de diseño premium y corporativo */
            --cycsa-azul: #103487;
            --cycsa-azul-hover: #1e40af;
            --sidebar-bg: #0f172a; /* Slate 900 */
            --sidebar-hover: rgba(255, 255, 255, 0.04);
            --sidebar-active: rgba(255, 255, 255, 0.07);
            --cycsa-rojo: #ef4444;
            --cycsa-amarillo: #f59e0b;
            --fondo-app: #f8fafc; /* Slate 50 */
            --texto-principal: #1e293b; /* Slate 800 */
            --texto-sidebar: #94a3b8; /* Slate 400 */
            --border-light: #e2e8f0; /* Slate 200 */
        }

        * { 
            box-sizing: border-box; 
            margin: 0; 
            padding: 0; 
        }

        body { 
            font-family: 'Inter', sans-serif; 
            background: var(--fondo-app); 
            display: flex; 
            height: 100vh; 
            overflow: hidden; 
            color: var(--texto-principal);
        }
        
        /* BARRA LATERAL (SIDEBAR) */
        .sidebar { 
            width: 260px; 
            background: var(--sidebar-bg); 
            display: flex; 
            flex-direction: column; 
            transition: width 0.3s cubic-bezier(0.4, 0, 0.2, 1); 
            z-index: 10;
            overflow-x: hidden;
            border-right: 1px solid rgba(255, 255, 255, 0.05);
        }
        
        .sidebar.oculto-panel {
            display: none !important;
        }
        
        /* Sidebar Colapsado */
        .sidebar.colapsado { width: 70px; }
        .sidebar.colapsado .menu-texto, 
        .sidebar.colapsado .menu-categoria,
        .sidebar.colapsado .logo-texto { display: none; }
        .sidebar.colapsado .sidebar-menu li a { justify-content: center; padding: 14px 0; }
        .sidebar.colapsado .sidebar-menu li a i { margin-right: 0; font-size: 18px; }
        .sidebar.colapsado .logo-img { margin-right: 0; max-height: 32px; }

        /* Cabecera del Sidebar */
        .sidebar-header { 
            height: 70px; 
            display: flex; 
            align-items: center; 
            padding: 0 20px;
            background: rgba(0,0,0,0.15);
            color: white;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }
        .sidebar-header .logo-img { max-height: 38px; margin-right: 12px; object-fit: contain; }
        .sidebar.colapsado .sidebar-header { padding: 0; justify-content: center; }
        
        .logo-texto { 
            font-family: 'Outfit', sans-serif;
            font-size: 20px; 
            font-weight: 800; 
            letter-spacing: 0.5px;
            color: white;
        }

        /* Menú y Categorías */
        .sidebar-menu { list-style: none; padding: 20px 0; flex: 1; overflow-y: auto; }
        
        .menu-categoria {
            font-size: 10px;
            text-transform: uppercase;
            color: #64748b;
            padding: 18px 20px 6px 20px;
            letter-spacing: 1.5px;
            font-weight: 700;
        }

        .sidebar-menu li a { 
            display: flex; 
            align-items: center;
            padding: 11px 20px; 
            color: var(--texto-sidebar); 
            text-decoration: none; 
            font-weight: 500;
            font-size: 14px;
            border-left: 4px solid transparent;
            transition: all 0.2s ease;
            white-space: nowrap; 
        }
        
        .sidebar-menu li a i { width: 28px; font-size: 15px; text-align: center; margin-right: 10px; opacity: 0.8; }
        .sidebar-menu li a:hover { background: var(--sidebar-hover); color: white; }
        
        .sidebar-menu li a.activo { 
            background: var(--sidebar-active); 
            color: white; 
            border-left: 4px solid #38bdf8; /* Azul claro brillante premium */
            font-weight: 600;
        }
        .sidebar-menu li a.activo i { opacity: 1; color: #38bdf8; }

        /* ÁREA PRINCIPAL */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        
        .topbar { 
            background: white; 
            height: 70px;
            padding: 0 25px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid var(--border-light);
            box-shadow: 0 1px 3px rgba(0,0,0,0.02);
        }

        .toggle-btn {
            background: none;
            border: none;
            font-size: 18px;
            color: #64748b;
            cursor: pointer;
            padding: 10px;
            border-radius: 6px;
            transition: background 0.2s, color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .toggle-btn:hover { background: #f1f5f9; color: var(--cycsa-azul); }

        .user-info { display: flex; align-items: center; gap: 20px; }
        
        .user-profile-badge {
            display: flex;
            align-items: center;
            gap: 10px;
        }

        .user-avatar {
            width: 36px;
            height: 36px;
            border-radius: 50%;
            background: linear-gradient(135deg, var(--cycsa-azul) 0%, #1e40af 100%);
            color: white;
            display: flex;
            align-items: center;
            justify-content: center;
            font-weight: 700;
            font-size: 14px;
            font-family: 'Outfit', sans-serif;
            box-shadow: 0 2px 8px rgba(16, 52, 135, 0.2);
            border: 2px solid white;
        }

        .user-role { 
            font-size: 11px; 
            background: #f1f5f9; 
            color: #475569; 
            padding: 3px 8px; 
            border-radius: 4px; 
            font-weight: 600; 
            display: inline-block;
            margin-top: 2px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .btn-salir { 
            color: #64748b; 
            font-size: 18px; 
            transition: color 0.2s, transform 0.2s; 
            padding: 5px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .btn-salir:hover { 
            color: var(--cycsa-rojo); 
            transform: scale(1.05);
        }

        .content-wrapper { 
            padding: 30px; 
            overflow-y: auto; 
            flex: 1; 
            background: #f8fafc;
        }

        /* Clases auxiliares para flexbox responsivo */
        .header-flex {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 20px;
            flex-wrap: wrap;
            gap: 15px;
        }
        .actions-flex {
            display: flex;
            gap: 10px;
            align-items: center;
            flex-wrap: wrap;
        }

        /* ==========================================================================
           RESPONSIVIDAD Y ADAPTACIÓN A DISPOSITIVOS MÓVILES (TELÉFONOS Y TABLETS)
           ======================================================================        /* ==========================================================================
           SISTEMA DE RESPONSIVIDAD PREMIUM - MULTIPLES BREAKPOINTS (MÓVILES, TABLETS, LAPTOPS)
           ========================================================================== */
        
        /* 📱 1. TELÉFONOS MÓVILES (max-width: 576px) */
        @media (max-width: 576px) {
            .content-wrapper {
                padding: 10px !important;
                padding-bottom: 80px !important;
            }
            /* Ocultar nombre de usuario para ahorrar espacio en la barra superior */
            .user-profile-badge div:last-child {
                display: none !important;
            }
            .user-info {
                gap: 12px !important;
            }
            /* Ajustar tamaño de cabecera/logotipo de la barra superior */
            .topbar a img {
                max-height: 30px !important;
            }
            .topbar a span[style*="font-size: 20px"] {
                font-size: 16px !important;
            }
            .topbar a span[style*="font-size: 12.5px"] {
                font-size: 10px !important;
                padding: 3px 8px !important;
                margin-left: 6px !important;
            }
            /* Formularios colapsados al 100% */
            .form-control, input, select, textarea, button {
                width: 100% !important;
                box-sizing: border-box !important;
            }
            /* Botones de acción principales */
            .actions-flex, .actions-flex form {
                flex-direction: column !important;
                width: 100% !important;
            }
            .btn-action, button, .actions-flex a {
                justify-content: center !important;
                text-align: center !important;
                width: 100% !important;
            }
        }

        /* 📱 2. TABLETAS Y MÓVILES EN GENERAL (max-width: 992px) */
        @media (max-width: 992px) {
            /* 1. COMPORTAMIENTO DE SIDEBAR COMO CAJÓN DESLIZABLE (DRAWER) */
            .sidebar {
                position: fixed;
                left: -260px;
                height: 100%;
                width: 260px;
                z-index: 1000;
                box-shadow: 10px 0 30px rgba(0,0,0,0.2);
                transition: left 0.3s cubic-bezier(0.4, 0, 0.2, 1);
            }
            .sidebar.mostrar-movil {
                left: 0;
            }
            
            /* 2. REORDENAMIENTO DE REJILLAS Y COLUMNAS */
            .info-grid, .grid-form, .form-row, .row, .grid-2 {
                grid-template-columns: 1fr !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 15px !important;
            }
            
            /* Grid de 3 columnas pasa a auto-fit en tabletas */
            .grid-3 {
                display: grid !important;
                grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)) !important;
                gap: 15px !important;
            }
            
            .kpi-container {
                grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)) !important;
                gap: 15px !important;
            }
            
            .content-wrapper {
                padding: 20px;
                padding-bottom: 80px; /* Espacio extra para evitar problemas de scroll en móviles */
            }
            
            .topbar {
                padding: 0 15px !important;
            }
            
            /* 3. ADAPTACIÓN DE FILTROS Y ENCABEZADOS */
            .catalogo-header, .doc-header, .header-flex {
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 15px !important;
                text-align: left !important;
            }
            
            .filtro-barra, .actions-flex {
                max-width: 100% !important;
                flex-direction: column !important;
                align-items: stretch !important;
                gap: 10px !important;
                width: 100% !important;
            }
            
            .filtro-barra .search-input-wrapper, .actions-flex form {
                width: 100% !important;
                display: flex !important;
            }
            
            .actions-flex form input {
                flex: 1 !important;
                width: auto !important;
            }
            
            .filtro-barra .cat-select {
                width: 100% !important;
            }
            
            .filtro-barra .btn-premium-azul, .actions-flex a {
                width: 100% !important;
                margin-left: 0 !important;
                justify-content: center !important;
                text-align: center !important;
            }

            .pildoras-container {
                padding-bottom: 8px !important;
                margin-bottom: 15px !important;
            }

            .modal-premium-content {
                width: 95% !important;
                padding: 20px !important;
                margin: 20px auto !important;
            }

            .modal-search-wrapper {
                flex-direction: column !important;
                gap: 10px !important;
            }

            .modal-search-wrapper .form-control {
                max-width: 100% !important;
            }

            /* Scroll para pestañas de módulos y contabilidad */
            .tabs-container {
                overflow-x: auto !important;
                white-space: nowrap !important;
                display: flex !important;
                flex-direction: row !important;
                gap: 8px !important;
                padding-bottom: 8px !important;
                border-bottom: 1px solid #e2e8f0 !important;
                -webkit-overflow-scrolling: touch;
            }
            .tab-link {
                flex-shrink: 0 !important;
                display: inline-flex !important;
            }
            
            /* 4. PROTECCIÓN DE TABLAS CONTRA DESBORDAMIENTOS (SCROLL HORIZONTAL) */
            .seccion-form, .tabla-container, .table-responsive, .modal-tabla-container, div[style*="overflow-x: auto"] {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
                width: 100%;
                margin-bottom: 20px;
                border: 1px solid #e2e8f0;
                border-radius: 8px;
            }
            
            .tabla-detalles, .tabla-premium, .tabla-visual, .modal-tabla, .tabla-cycsa {
                min-width: 850px !important; /* Forzar ancho mínimo interno para legibilidad de columnas */
            }

            /* 5. DISEÑO DE TOTALES */
            div[style*="display: flex; justify-content: flex-end;"] {
                justify-content: center !important;
            }
            div[style*="width: 300px;"] {
                width: 100% !important;
            }
        }

        /* 💻 3. PORTÁTILES Y LAPTOPS (min-width: 993px) and (max-width: 1366px) */
        @media (min-width: 993px) and (max-width: 1366px) {
            .content-wrapper {
                padding: 24px;
            }
            .grid-3 {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 15px !important;
            }
            .kpi-container {
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 15px !important;
        }
    </style>
</head>
<body>

    <?php 
    $rutaActual = $_SERVER['REQUEST_URI']; 
    $esVistaGeneral = true; // Desactivar la barra lateral (sidebar) de forma global en todo el sistema
    ?>

    <aside class="sidebar <?= $esVistaGeneral ? 'oculto-panel' : '' ?>" id="sidebar">
        <div class="sidebar-header">
            <img src="/Cycsa/publico/img/logo.png" alt="Logo Cycsa" class="logo-img">
            <span class="logo-texto">CYCSA</span>
        </div>
        
        <ul class="sidebar-menu">
            <li class="menu-categoria">Principal</li>
            <li>
                <a href="/Cycsa/publico/panel" class="<?= strpos($rutaActual, '/panel') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-cubes"></i>
                    <span class="menu-texto">Cajón de Aplicaciones</span>
                </a>
            </li>
            
            <li class="menu-categoria">Módulos</li>
            <?php if (tienePermiso('cotizaciones', 'ver')): ?>
            <li>
                <a href="/Cycsa/publico/cotizaciones" class="<?= strpos($rutaActual, '/cotizaciones') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span class="menu-texto">Cotizaciones</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (tienePermiso('operaciones', 'ver')): ?>
            <li>
                <a href="/Cycsa/publico/hojas-servicio" class="<?= strpos($rutaActual, '/hojas-servicio') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-file-signature"></i>
                    <span class="menu-texto">Hojas de Servicio</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (tienePermiso('operaciones', 'ver')): ?>
            <li>
                <a href="/Cycsa/publico/operaciones" class="<?= strpos($rutaActual, '/operaciones') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-gears"></i>
                    <span class="menu-texto">Operaciones LIMS</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (tienePermiso('laboratorio', 'ver')): ?>
            <li>
                <a href="/Cycsa/publico/laboratorio" class="<?= strpos($rutaActual, '/laboratorio') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-flask-vial"></i>
                    <span class="menu-texto">Laboratorio</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (tienePermiso('contabilidad', 'ver')): ?>
            <li>
                <a href="/Cycsa/publico/contabilidad/cuentas" class="<?= strpos($rutaActual, '/contabilidad') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-calculator"></i>
                    <span class="menu-texto">Contabilidad</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (tienePermiso('productos', 'ver')): ?>
            <li>
                <a href="/Cycsa/publico/productos" class="<?= strpos($rutaActual, '/productos') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-flask-vial"></i>
                    <span class="menu-texto">Productos / Ensayos</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (tienePermiso('clientes', 'ver')): ?>
            <li>
                <a href="/Cycsa/publico/clientes" class="<?= strpos($rutaActual, '/clientes') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-address-book"></i>
                    <span class="menu-texto">Clientes</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (tienePermiso('usuarios', 'ver')): ?>
            <li>
                <a href="/Cycsa/publico/usuarios" class="<?= strpos($rutaActual, '/usuarios') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-users"></i>
                    <span class="menu-texto">Gestión de Usuarios</span>
                </a>
            </li>
            <?php endif; ?>
            

            <?php if (($_SESSION['usuario_rol'] ?? 0) == 1): ?>
            <li class="menu-categoria">Ajustes</li>
            <li>
                <a href="/Cycsa/publico/configuracion" class="<?= strpos($rutaActual, '/configuracion') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-gears"></i>
                    <span class="menu-texto">Condiciones Comerciales</span>
                </a>
            </li>
            <?php endif; ?>
        </ul>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <?php if (!$esVistaGeneral): ?>
            <button class="toggle-btn" id="btn-toggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            <?php else: ?>
            <a href="/Cycsa/publico/panel" style="display: flex; align-items: center; gap: 12px; text-decoration: none; transition: transform 0.2s;" onmouseover="this.style.transform='scale(1.015)'" onmouseout="this.style.transform='scale(1)'" title="Regresar al Cajón de Aplicaciones">
                <img src="/Cycsa/publico/img/logo.png" alt="Logo Cycsa" style="max-height: 38px; object-fit: contain;">
                <span style="font-family: 'Outfit', sans-serif; font-size: 20px; font-weight: 800; color: var(--cycsa-azul); letter-spacing: 0.5px;">CYCSA ERP</span>
                <span style="color: #475569; font-size: 12.5px; margin-left: 10px; padding: 5px 12px; border-radius: 20px; background: #e2e8f0; display: flex; align-items: center; gap: 6px; font-weight: 600; font-family: 'Inter', sans-serif; border: 1px solid rgba(203,213,225,0.5);">
                    <i class="fa-solid fa-cubes"></i> Módulos
                </span>
            </a>
            <?php endif; ?>
            
            <div class="user-info">
                <div class="user-profile-badge">
                    <!-- Avatar con la inicial del usuario -->
                    <div class="user-avatar">
                        <?= strtoupper(substr($_SESSION['usuario_nombre'] ?? 'U', 0, 1)) ?>
                    </div>
                    <div style="text-align: left;">
                        <div style="font-weight: 600; font-size: 13.5px; color: #1e293b; line-height: 1.2;">
                            <?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?>
                        </div>
                        <span class="user-role">
                            <?= ($_SESSION['usuario_rol'] ?? 0) == 1 ? 'Administrador' : 'Vendedor' ?>
                        </span>
                    </div>
                </div>
                
                <div style="height: 24px; width: 1px; background: var(--border-light);"></div>
                
                <a href="/Cycsa/publico/logout" class="btn-salir" title="Cerrar Sesión">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </a>
            </div>
        </header>

        <div class="content-wrapper">
            <?= $contenido ?> 
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            const btnToggle = document.getElementById('btn-toggle');
            const sidebar = document.getElementById('sidebar');

            if (btnToggle && sidebar) {
                btnToggle.addEventListener('click', (e) => {
                    e.stopPropagation();
                    if (window.innerWidth <= 768) {
                        sidebar.classList.toggle('mostrar-movil');
                    } else {
                        sidebar.classList.toggle('colapsado');
                    }
                });
            }

            // Cerrar el menú deslizante si se da clic fuera de él en dispositivos móviles
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 768 && sidebar) {
                    if (!sidebar.contains(e.target) && e.target !== btnToggle && (!btnToggle || !btnToggle.contains(e.target))) {
                        sidebar.classList.remove('mostrar-movil');
                    }
                }
            });

            // 🔒 Verificar cada 5 segundos si otra sesión activa cerró la nuestra en segundo plano (con cache-busting y JSON)
            setInterval(() => {
                fetch('<?= obtenerBaseUrl() ?>/verificar-sesion-activa?_t=' + Date.now())
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.status === 'kicked') {
                            window.location.href = '<?= obtenerBaseUrl() ?>/login';
                        }
                    })
                    .catch(err => console.error("Error de conexión al validar sesión única:", err));
            }, 5000);

            // 🛑 Protección universal contra doble clic / doble envío de formularios (Bancos, CXC, Diario, LIMS, etc.)
            document.addEventListener('submit', function(e) {
                const form = e.target;
                if (form.tagName === 'FORM') {
                    if (form.checkValidity && !form.checkValidity()) {
                        return;
                    }
                    setTimeout(() => {
                        if (!e.defaultPrevented) {
                            const btn = form.querySelector('button[type="submit"], input[type="submit"]');
                            if (btn) {
                                btn.disabled = true;
                                if (btn.tagName === 'BUTTON') {
                                    btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin"></i> Procesando...';
                                } else {
                                    btn.value = 'Procesando...';
                                }
                                btn.style.opacity = '0.7';
                                btn.style.cursor = 'not-allowed';
                            }
                        }
                    }, 10);
                }
            }, true);
        });
    </script>
</body>
</html>