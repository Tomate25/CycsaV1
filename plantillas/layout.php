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
           ========================================================================== */
        @media (max-width: 768px) {
            /* 1. COMPORTAMIENTO DE SIDEBAR COMO CAJÓN DESLIZABLE (DRAWER) */
            .sidebar {
                position: fixed;
                left: -260px;
                height: 100%;
                width: 260px;
                box-shadow: 10px 0 30px rgba(0,0,0,0.25);
            }
            .sidebar.mostrar-movil {
                left: 0;
            }
            
            /* 2. REORDENAMIENTO DE REJILLAS Y COLUMNAS */
            .info-grid, .grid-form, .form-row, .row, .grid-2, .grid-3 {
                grid-template-columns: 1fr !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 15px !important;
            }
            
            .kpi-container {
                grid-template-columns: 1fr !important;
                gap: 15px !important;
            }
            
            .content-wrapper {
                padding: 15px !important;
                padding-bottom: 90px !important; /* Espacio extra abajo para evitar corte de scroll en móvil */
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
                padding: 15px !important;
                margin: 20px auto !important;
            }

            .modal-search-wrapper {
                flex-direction: column !important;
                gap: 10px !important;
            }

            .modal-search-wrapper .form-control {
                max-width: 100% !important;
            }

            /* Scroll para pestañas de cotizaciones */
            .tabs-container {
                overflow-x: auto !important;
                white-space: nowrap !important;
                display: flex !important;
                flex-direction: row !important;
                gap: 5px !important;
                padding-bottom: 8px !important;
                border-bottom: 1px solid var(--border-light) !important;
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
                border: 1px solid var(--border-light);
                border-radius: 6px;
            }
            
            .tabla-detalles, .tabla-premium, .tabla-visual, .modal-tabla, .tabla-cycsa {
                min-width: 700px !important; /* Forzar ancho mínimo interno */
            }

            /* 5. DISEÑO DE TOTALES */
            div[style*="display: flex; justify-content: flex-end;"] {
                justify-content: center !important;
            }
            div[style*="width: 300px;"] {
                width: 100% !important;
            }
        }
    </style>
</head>
<body>

    <aside class="sidebar" id="sidebar">
        <div class="sidebar-header">
            <img src="/Cycsa/publico/img/logo.png" alt="Logo Cycsa" class="logo-img">
            <span class="logo-texto">CYCSA</span>
        </div>
        
        <?php $rutaActual = $_SERVER['REQUEST_URI']; ?>
        
        <ul class="sidebar-menu">
            <?php if (($_SESSION['usuario_rol'] ?? 0) == 1): ?>
            <li class="menu-categoria">Principal</li>
            <li>
                <a href="/Cycsa/publico/panel" class="<?= strpos($rutaActual, '/panel') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span class="menu-texto">Vista General</span>
                </a>
            </li>
            <?php endif; ?>
            
            <li class="menu-categoria">Módulos</li>
            <?php if (tienePermiso('clientes', 'ver')): ?>
            <li>
                <a href="/Cycsa/publico/clientes" class="<?= strpos($rutaActual, '/clientes') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-address-book"></i>
                    <span class="menu-texto">Clientes</span>
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
                <a href="/Cycsa/publico/operaciones" class="<?= strpos($rutaActual, '/operaciones') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-gears"></i>
                    <span class="menu-texto">Operaciones LIMS</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (in_array($_SESSION['usuario_rol'] ?? 0, [1, 2, 3])): ?>
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
            <?php if (tienePermiso('usuarios', 'ver')): ?>
            <li>
                <a href="/Cycsa/publico/usuarios" class="<?= strpos($rutaActual, '/usuarios') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-users"></i>
                    <span class="menu-texto">Gestión de Usuarios</span>
                </a>
            </li>
            <?php endif; ?>
            <?php if (($_SESSION['usuario_rol'] ?? 0) == 1): ?>
            <li>
                <a href="/Cycsa/publico/panel/bitacora" class="<?= strpos($rutaActual, '/panel/bitacora') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-receipt"></i>
                    <span class="menu-texto">Bitácora</span>
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
            <button class="toggle-btn" id="btn-toggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            
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

            btnToggle.addEventListener('click', (e) => {
                e.stopPropagation();
                if (window.innerWidth <= 768) {
                    sidebar.classList.toggle('mostrar-movil');
                } else {
                    sidebar.classList.toggle('colapsado');
                }
            });

            // Cerrar el menú deslizante si se da clic fuera de él en dispositivos móviles
            document.addEventListener('click', (e) => {
                if (window.innerWidth <= 768) {
                    if (!sidebar.contains(e.target) && e.target !== btnToggle && !btnToggle.contains(e.target)) {
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
        });
    </script>
</body>
</html>