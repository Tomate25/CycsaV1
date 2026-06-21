<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $titulo ?? 'Cycsa ERP' ?></title>
    
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    
    <style>
        :root {
            /* Colores corporativos integrados a un diseño oscuro/claro */
            --cycsa-azul: #103487;
            --sidebar-bg: #21252d; 
            --sidebar-hover: #2c313c;
            --cycsa-rojo: #e31837;
            --cycsa-amarillo: #ffd100;
            --fondo-app: #f4f7f6;
            --texto-principal: #333;
            --texto-sidebar: #a1a7b3;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', sans-serif; background: var(--fondo-app); display: flex; height: 100vh; overflow: hidden; }
        
        /* BARRA LATERAL (SIDEBAR) */
        .sidebar { 
            width: 250px; 
            background: var(--sidebar-bg); 
            display: flex; 
            flex-direction: column; 
            transition: width 0.3s ease; 
            z-index: 10;
            overflow-x: hidden;
        }
        
        /* EL TRUCO DEL COLAPSO */
        .sidebar.colapsado { width: 70px; }
        .sidebar.colapsado .menu-texto, 
        .sidebar.colapsado .menu-categoria,
        .sidebar.colapsado .logo-texto { display: none; }
        .sidebar.colapsado .sidebar-menu li a { justify-content: center; padding: 15px 0; }
        .sidebar.colapsado .sidebar-menu li a i { margin-right: 0; font-size: 20px; }

        /* Cabecera del Sidebar */
        .sidebar-header { 
            height: 70px; 
            display: flex; 
            align-items: center; 
            padding: 0 20px;
            background: rgba(0,0,0,0.1);
            color: white;
            border-bottom: 2px solid var(--cycsa-azul);
        }
        .sidebar-header .logo-img { max-height: 40px; margin-right: 10px; object-fit: contain; }
        .sidebar.colapsado .sidebar-header { padding: 0; justify-content: center; }
        .sidebar.colapsado .logo-img { margin-right: 0; max-height: 35px; }
        .logo-texto { font-size: 20px; font-weight: 700; letter-spacing: 1px; font-style: italic; }

        /* Menú y Categorías */
        .sidebar-menu { list-style: none; padding: 15px 0; flex: 1; overflow-y: auto; }
        
        .menu-categoria {
            font-size: 11px;
            text-transform: uppercase;
            color: #6c757d;
            padding: 15px 20px 5px 20px;
            letter-spacing: 1px;
            font-weight: 600;
        }

        .sidebar-menu li a { 
            display: flex; 
            align-items: center;
            padding: 12px 20px; 
            color: var(--texto-sidebar); 
            text-decoration: none; 
            font-weight: 500;
            font-size: 14px;
            border-left: 3px solid transparent;
            transition: all 0.2s ease;
            white-space: nowrap; 
        }
        
        .sidebar-menu li a i { width: 30px; font-size: 16px; text-align: center; margin-right: 10px; }
        .sidebar-menu li a:hover { background: var(--sidebar-hover); color: white; }
        .sidebar-menu li a.activo { background: var(--sidebar-hover); color: white; border-left: 3px solid var(--cycsa-azul); }

        /* ÁREA PRINCIPAL */
        .main-content { flex: 1; display: flex; flex-direction: column; overflow: hidden; }
        
        .topbar { 
            background: white; 
            height: 70px;
            padding: 0 25px; 
            display: flex; 
            justify-content: space-between; 
            align-items: center; 
            border-bottom: 1px solid #e2e8f0;
        }

        .toggle-btn {
            background: none;
            border: none;
            font-size: 20px;
            color: #64748b;
            cursor: pointer;
            padding: 10px;
            border-radius: 4px;
            transition: background 0.2s;
        }
        .toggle-btn:hover { background: #f1f5f9; color: var(--cycsa-azul); }

        .user-info { display: flex; align-items: center; gap: 15px; }
        .user-role { font-size: 12px; background: #e0e7ff; color: var(--cycsa-azul); padding: 4px 8px; border-radius: 4px; font-weight: 600; }

        .btn-salir { color: #64748b; font-size: 18px; transition: color 0.2s; }
        .btn-salir:hover { color: var(--cycsa-rojo); }

        .content-wrapper { padding: 30px; overflow-y: auto; flex: 1; }
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
            <li class="menu-categoria">Principal</li>
            <li>
                <a href="/Cycsa/publico/panel" class="<?= strpos($rutaActual, '/panel') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span class="menu-texto">Vista General</span>
                </a>
            </li>
            
            <li class="menu-categoria">Módulos</li>
            <li>
                <a href="/Cycsa/publico/clientes" class="<?= strpos($rutaActual, '/clientes') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-address-book"></i>
                    <span class="menu-texto">Clientes</span>
                </a>
            </li>
            <li>
                <a href="/Cycsa/publico/cotizaciones" class="<?= strpos($rutaActual, '/cotizaciones') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-file-invoice-dollar"></i>
                    <span class="menu-texto">Cotizaciones</span>
                </a>
            </li>
            <li>
                <a href="/Cycsa/publico/usuarios" class="<?= strpos($rutaActual, '/usuarios') !== false ? 'activo' : '' ?>">
                    <i class="fa-solid fa-users"></i>
                    <span class="menu-texto">Gestión de Usuarios</span>
                </a>
            </li>
            
            <li class="menu-categoria">Ajustes</li>
            <li>
                <a href="#" onclick="alert('⚙️ Módulo en producción. ¡Estará disponible pronto!'); return false;">
                    <i class="fa-solid fa-gear"></i>
                    <span class="menu-texto">Configuración</span>
                </a>
            </li>
        </ul>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <button class="toggle-btn" id="btn-toggle">
                <i class="fa-solid fa-bars"></i>
            </button>
            
            <div class="user-info">
                <div style="text-align: right;">
                    <div style="font-weight: 600; font-size: 14px;"><?= htmlspecialchars($_SESSION['usuario_nombre'] ?? 'Usuario', ENT_QUOTES, 'UTF-8') ?></div>
                    <span class="user-role"><?= ($_SESSION['usuario_rol'] ?? 0) == 1 ? 'Administrador' : 'Vendedor' ?></span>
                </div>
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

            btnToggle.addEventListener('click', () => {
                sidebar.classList.toggle('colapsado');
            });
        });
    </script>
</body>
</html>