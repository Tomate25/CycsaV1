<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars((string)($titulo ?? 'Cycsa ERP'), ENT_QUOTES, 'UTF-8') ?></title>

    <link rel="shortcut icon" href="/Cycsa/publico/img/logo.png" type="image/png">

    <!-- FontAwesome para íconos heredados de módulos ERP -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">

    <!-- Bootstrap 5.3.3 CSS (Estandarización UI para todos los módulos) -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">

    <!-- Guía de Diseño Sobrio CYCSA (Operaciones y Navegación Unificada) -->
    <link rel="stylesheet" href="/Cycsa/publico/css/cycsa-sobrio.css">

    <!-- Estilos de Pantalla Completa para Hoja RT-FM-13 (CYCSA-RT-FM-13) -->
    <link rel="stylesheet" href="/Cycsa/publico/css/rtfm13_fullscreen.css">

    <!-- SweetAlert2 para modales y decisiones interactivas -->
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>

    <style>
        :root {
            --cycsa-azul: var(--cy-brand, #1F3864);
            --cycsa-azul-hover: var(--cy-brand-hover, #182C4F);
            --fondo-app: var(--cy-bg, #F9FAFB);
            --texto-principal: var(--cy-text, #111827);
            --border-light: var(--cy-border, #E5E7EB);
        }

        * {
            box-sizing: border-box;
        }

        /* TAREA-056: Desactivar flechas / spinners en inputs numéricos */
        input[type=number]::-webkit-outer-spin-button,
        input[type=number]::-webkit-inner-spin-button {
            -webkit-appearance: none;
            margin: 0;
        }
        input[type=number] {
            -moz-appearance: textfield;
            appearance: textfield;
        }

        body {
            display: flex;
            flex-direction: column;
            height: 100vh;
            overflow: hidden;
            margin: 0;
        }

        a {
            text-decoration: none;
        }

        .cy-topbar {
            flex-shrink: 0;
        }

        .cy-crumb {
            min-width: 0;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        .cy-logout-link {
            font-size: 13px;
            font-weight: 500;
            color: var(--cy-muted);
            padding-left: 12px;
            border-left: 1px solid var(--cy-border);
            text-decoration: none;
            transition: color 0.15s ease;
        }
        .cy-logout-link:hover {
            color: var(--cy-brand);
            text-decoration: underline;
        }

        #cyModulos {
            width: min(440px, 100vw);
            border-right: 1px solid var(--cy-border);
            box-shadow: none;
        }

        .main-content {
            flex: 1;
            min-height: 0;
            display: flex;
            flex-direction: column;
            overflow: hidden;
        }

        .content-wrapper {
            padding: 24px;
            overflow-y: auto;
            flex: 1;
            min-height: 0;
            background: var(--cy-bg, #F9FAFB);
        }

        .content-wrapper > .container-fluid {
            padding-left: 0;
            padding-right: 0;
        }

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

        .modal-header-sobrio {
            background: #FFFFFF !important;
            color: var(--cy-text, #111827) !important;
            border-bottom: 1px solid var(--cy-border, #E5E7EB) !important;
        }

        .badge-etapa-sobria {
            font-size: 13px;
            font-weight: 600;
            padding: 3px 10px;
            border-radius: 999px;
            display: inline-flex;
            align-items: center;
            gap: 5px;
        }

        @media (max-width: 576px) {
            .cy-topbar {
                padding: 0 12px;
                gap: 10px;
            }
            .cy-user-label {
                display: none;
            }
            .cy-logout-link {
                border-left: 0;
                padding-left: 0;
            }
            .content-wrapper {
                padding: 12px !important;
                padding-bottom: 72px !important;
            }
            .actions-flex,
            .actions-flex form {
                flex-direction: column !important;
                width: 100% !important;
            }
            .actions-flex a,
            .actions-flex button {
                justify-content: center !important;
                text-align: center !important;
                width: 100% !important;
            }
        }

        @media (max-width: 992px) {
            .info-grid, .grid-form, .form-row, .grid-2 {
                grid-template-columns: 1fr !important;
                display: flex !important;
                flex-direction: column !important;
                gap: 15px !important;
            }
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
                padding: 16px;
                padding-bottom: 72px;
            }
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
            .filtro-barra .cat-select {
                width: 100% !important;
            }
            .modal-premium-content {
                width: 95% !important;
                padding: 20px !important;
                margin: 20px auto !important;
            }
            .tabs-container {
                overflow-x: auto !important;
                white-space: nowrap !important;
                display: flex !important;
                flex-direction: row !important;
                gap: 8px !important;
                padding-bottom: 8px !important;
                border-bottom: 1px solid var(--cy-border, #E5E7EB) !important;
                -webkit-overflow-scrolling: touch;
            }
            .tab-link {
                flex-shrink: 0 !important;
                display: inline-flex !important;
            }
            .seccion-form, .tabla-container, .table-responsive, .modal-tabla-container {
                overflow-x: auto !important;
                -webkit-overflow-scrolling: touch;
                width: 100%;
                margin-bottom: 20px;
                border: 1px solid var(--cy-border, #E5E7EB);
                border-radius: 8px;
            }
            .tabla-detalles, .tabla-premium, .tabla-visual, .modal-tabla, .tabla-cycsa {
                min-width: 850px !important;
            }
        }

        @media (min-width: 993px) and (max-width: 1366px) {
            .grid-3 {
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 15px !important;
            }
            .kpi-container {
                grid-template-columns: repeat(4, 1fr) !important;
                gap: 15px !important;
            }
        }

        @media print {
            body,
            .main-content,
            .content-wrapper {
                height: auto !important;
                overflow: visible !important;
                padding: 0 !important;
                background: #FFFFFF !important;
            }
        }
    </style>
</head>
<body>
<?php
$uriActual = $_SERVER['REQUEST_URI'] ?? '';
$rutaPath = rtrim((string)(parse_url($uriActual, PHP_URL_PATH) ?: ''), '/');
$rolUsuarioLayout = (int)($_SESSION['usuario_rol'] ?? 0);
$esAdminLayout = ($rolUsuarioLayout === 1);
$nombreUsuarioLayout = (string)($_SESSION['usuario_nombre'] ?? 'Usuario');
$nombreRol = function_exists('obtenerNombreRol')
    ? obtenerNombreRol($_SESSION['usuario_rol'] ?? 0)
    : ($esAdminLayout ? 'Administrador' : 'Usuario');
$puedeVer = static fn(string $mod): bool => function_exists('tienePermiso') && tienePermiso($mod, 'ver');

// Catálogo maestro de módulos del lanzador #cyModulos (estrictamente filtrado por RBAC)
$modulosCatalogo = [
    [
        'titulo'    => 'Cajón de Aplicaciones',
        'categoria' => 'Principal',
        'href'      => '/Cycsa/publico/panel',
        'visible'   => true,
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/panel') !== false && strpos($rutaPath, '/panel/bitacora') === false),
    ],
    [
        'titulo'    => 'Clientes',
        'categoria' => 'Comercial',
        'href'      => '/Cycsa/publico/clientes',
        'visible'   => $puedeVer('clientes'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/clientes') !== false),
    ],
    [
        'titulo'    => 'Productos / Ensayos',
        'categoria' => 'Comercial',
        'href'      => '/Cycsa/publico/productos',
        'visible'   => $puedeVer('productos'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/productos') !== false),
    ],
    [
        'titulo'    => 'Cotizaciones',
        'categoria' => 'Comercial',
        'href'      => '/Cycsa/publico/cotizaciones',
        'visible'   => $puedeVer('cotizaciones'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/cotizaciones') !== false),
    ],
    [
        'titulo'    => 'Órdenes de Servicio',
        'categoria' => 'Comercial',
        'href'      => '/Cycsa/publico/ordenes-servicio',
        'visible'   => $puedeVer('ordenes_servicio') || $puedeVer('recepcion'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/ordenes-servicio') !== false),
    ],
    [
        'titulo'    => 'Ventas y Facturación',
        'categoria' => 'Comercial',
        'href'      => '/Cycsa/publico/ventas',
        'visible'   => $puedeVer('ventas'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/ventas') !== false),
    ],
    [
        'titulo'    => 'Operaciones',
        'categoria' => 'LIMS',
        'href'      => '/Cycsa/publico/operaciones',
        'visible'   => $puedeVer('operaciones'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/operaciones') !== false),
    ],
    [
        'titulo'    => 'Laboratorio',
        'categoria' => 'LIMS',
        'href'      => '/Cycsa/publico/laboratorio',
        'visible'   => $puedeVer('laboratorio'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/laboratorio') !== false),
    ],
    [
        'titulo'    => 'Control de Calidad',
        'categoria' => 'LIMS',
        'href'      => '/Cycsa/publico/control-calidad',
        'visible'   => $puedeVer('control_calidad'),
        'activo'    => ($rutaPath !== '' && (strpos($rutaPath, '/control-calidad') !== false || strpos($rutaPath, '/equipos-calibracion') !== false)),
    ],
    [
        'titulo'    => 'Bodegas y Traslados',
        'categoria' => 'LIMS',
        'href'      => '/Cycsa/publico/equipos-bodegas',
        'visible'   => $puedeVer('equipos_bodegas'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/equipos-bodegas') !== false),
    ],
    [
        'titulo'    => 'Formatos e Informes PDF',
        'categoria' => 'LIMS',
        'href'      => '/Cycsa/publico/configuracion/plantillas-ensayos',
        'visible'   => $puedeVer('plantillas'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/configuracion/plantillas-ensayos') !== false),
    ],
    [
        'titulo'    => 'Plantillas ISO 17025',
        'categoria' => 'LIMS',
        'href'      => '/Cycsa/publico/plantillas',
        'visible'   => $puedeVer('plantillas'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/plantillas') !== false && strpos($rutaPath, '/configuracion/plantillas-ensayos') === false),
    ],
    [
        'titulo'    => 'Contabilidad',
        'categoria' => 'ERP',
        'href'      => '/Cycsa/publico/contabilidad/cuentas',
        'visible'   => $puedeVer('contabilidad'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/contabilidad') !== false),
    ],
    [
        'titulo'    => 'Compras y Proveedores',
        'categoria' => 'ERP',
        'href'      => '/Cycsa/publico/compras/proveedores',
        'visible'   => $puedeVer('compras'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/compras') !== false),
    ],
    [
        'titulo'    => 'Inventario y Kárdex',
        'categoria' => 'ERP',
        'href'      => '/Cycsa/publico/inventario',
        'visible'   => $puedeVer('inventario'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/inventario') !== false),
    ],
    [
        'titulo'    => 'Nómina y RRHH',
        'categoria' => 'ERP',
        'href'      => '/Cycsa/publico/nomina',
        'visible'   => $puedeVer('nomina'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/nomina') !== false),
    ],
    [
        'titulo'    => 'Préstamos Bancarios',
        'categoria' => 'ERP',
        'href'      => '/Cycsa/publico/prestamos',
        'visible'   => $puedeVer('prestamos'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/prestamos') !== false),
    ],
    [
        'titulo'    => 'Activos Fijos',
        'categoria' => 'ERP',
        'href'      => '/Cycsa/publico/activos-fijos',
        'visible'   => $puedeVer('activos_fijos'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/activos-fijos') !== false),
    ],
    [
        'titulo'    => 'Proyectos y Obras',
        'categoria' => 'ERP',
        'href'      => '/Cycsa/publico/proyectos',
        'visible'   => $puedeVer('proyectos'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/proyectos') !== false),
    ],
    [
        'titulo'    => 'Centro de Reportes',
        'categoria' => 'ERP',
        'href'      => '/Cycsa/publico/reportes',
        'visible'   => $puedeVer('reportes'),
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/reportes') !== false),
    ],
    [
        'titulo'    => 'Gestión de Usuarios',
        'categoria' => 'Sistema',
        'href'      => '/Cycsa/publico/usuarios',
        'visible'   => $esAdminLayout,
        'activo'    => ($rutaPath !== '' && (strpos($rutaPath, '/usuarios') !== false || strpos($rutaPath, '/roles') !== false)),
    ],
    [
        'titulo'    => 'Condiciones Comerciales',
        'categoria' => 'Sistema',
        'href'      => '/Cycsa/publico/configuracion',
        'visible'   => $esAdminLayout,
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/configuracion') !== false && strpos($rutaPath, '/configuracion/plantillas-ensayos') === false),
    ],
    [
        'titulo'    => 'Bitácora de Auditoría',
        'categoria' => 'Sistema',
        'href'      => '/Cycsa/publico/panel/bitacora',
        'visible'   => $esAdminLayout,
        'activo'    => ($rutaPath !== '' && strpos($rutaPath, '/panel/bitacora') !== false),
    ],
];

$modulosHabilitados = array_values(array_filter($modulosCatalogo, static fn(array $m): bool => !empty($m['visible'])));
$totalModulosHabilitados = count($modulosHabilitados);

// Resolución dinámica de la miga de pan (breadcrumb) desde $uriActual y $titulo
$codigoOsContexto = (string)($orden['codigo_os'] ?? $o['codigo_os'] ?? $os['codigo_os'] ?? $detalle['codigo_os'] ?? '');
$mapaRutasMiga = [
    '/configuracion/plantillas-ensayos' => ['Formatos e Informes PDF', '/Cycsa/publico/configuracion/plantillas-ensayos', ''],
    '/configuracion'                    => ['Condiciones Comerciales', '/Cycsa/publico/configuracion', ''],
    '/control-calidad/calibracion'      => ['Control de Calidad', '/Cycsa/publico/control-calidad', 'Calibración de Equipos'],
    '/control-calidad/ver'              => ['Control de Calidad', '/Cycsa/publico/control-calidad', (string)($codigoInformeConsecutivo ?? 'Detalle')],
    '/control-calidad/detalle'          => ['Control de Calidad', '/Cycsa/publico/control-calidad', (string)($codigoInformeConsecutivo ?? 'Detalle')],
    '/control-calidad'                  => ['Control de Calidad', '/Cycsa/publico/control-calidad', ''],
    '/equipos-calibracion'              => ['Control de Calidad', '/Cycsa/publico/control-calidad', 'Calibración de Equipos'],
    '/operaciones/ver'                  => ['Operaciones', '/Cycsa/publico/operaciones', $codigoOsContexto !== '' ? $codigoOsContexto : 'Detalle de Orden'],
    '/operaciones/captura-matriz'       => ['Operaciones', '/Cycsa/publico/operaciones', $codigoOsContexto !== '' ? ($codigoOsContexto . ' · Captura de Matriz') : 'Captura de Matriz'],
    '/operaciones/recepcion'            => ['Operaciones', '/Cycsa/publico/operaciones', 'Recepción de Muestras'],
    '/operaciones/detalle-lote'         => ['Operaciones', '/Cycsa/publico/operaciones', 'Detalle de Lote'],
    '/operaciones/calendario'           => ['Operaciones', '/Cycsa/publico/operaciones', 'Calendario de Rupturas'],
    '/operaciones'                      => ['Operaciones', '/Cycsa/publico/operaciones', ''],
    '/hojas-servicio'                   => ['Recepción RT-FM-13', '/Cycsa/publico/ordenes-servicio', ''],
    '/laboratorio/detalle-lote'         => ['Laboratorio', '/Cycsa/publico/laboratorio', 'Detalle de Lote'],
    '/laboratorio/detalle-muestra'      => ['Laboratorio', '/Cycsa/publico/laboratorio', 'Detalle de Muestra'],
    '/laboratorio'                      => ['Laboratorio', '/Cycsa/publico/laboratorio', ''],
    '/equipos-bodegas/bodega'           => ['Bodegas y Traslados', '/Cycsa/publico/equipos-bodegas', (string)($bodega['nombre'] ?? 'Detalle de Bodega')],
    '/equipos-bodegas/equipo'           => ['Bodegas y Traslados', '/Cycsa/publico/equipos-bodegas', 'Historial de Equipo'],
    '/equipos-bodegas'                  => ['Bodegas y Traslados', '/Cycsa/publico/equipos-bodegas', ''],
    '/cotizaciones/crear'               => ['Cotizaciones', '/Cycsa/publico/cotizaciones', 'Nueva Cotización'],
    '/cotizaciones/editar'              => ['Cotizaciones', '/Cycsa/publico/cotizaciones', 'Editar Cotización'],
    '/cotizaciones/detalle'             => ['Cotizaciones', '/Cycsa/publico/cotizaciones', (string)($cotizacion['codigo'] ?? 'Detalle')],
    '/cotizaciones'                     => ['Cotizaciones', '/Cycsa/publico/cotizaciones', ''],
    '/ordenes-servicio/crear'           => ['Órdenes de Servicio', '/Cycsa/publico/ordenes-servicio', 'Nueva Orden'],
    '/ordenes-servicio/editar'          => ['Órdenes de Servicio', '/Cycsa/publico/ordenes-servicio', 'Editar Orden'],
    '/ordenes-servicio/detalle'         => ['Órdenes de Servicio', '/Cycsa/publico/ordenes-servicio', $codigoOsContexto !== '' ? $codigoOsContexto : 'Detalle'],
    '/ordenes-servicio/bitacora'        => ['Órdenes de Servicio', '/Cycsa/publico/ordenes-servicio', 'Bitácora'],
    '/ordenes-servicio/programar-muestreo' => ['Órdenes de Servicio', '/Cycsa/publico/ordenes-servicio', 'Programar Muestreo'],
    '/ordenes-servicio'                 => ['Órdenes de Servicio', '/Cycsa/publico/ordenes-servicio', ''],
    '/ventas'                           => ['Ventas y Facturación', '/Cycsa/publico/ventas', ''],
    '/compras/proveedores'              => ['Compras y Proveedores', '/Cycsa/publico/compras/proveedores', 'Proveedores'],
    '/compras/ordenes/crear'            => ['Compras y Proveedores', '/Cycsa/publico/compras/ordenes', 'Nueva Orden de Compra'],
    '/compras/ordenes/detalle'          => ['Compras y Proveedores', '/Cycsa/publico/compras/ordenes', 'Detalle de Orden'],
    '/compras/ordenes'                  => ['Compras y Proveedores', '/Cycsa/publico/compras/ordenes', 'Órdenes de Compra'],
    '/compras/recepciones/crear'        => ['Compras y Proveedores', '/Cycsa/publico/compras/recepciones', 'Nueva Recepción'],
    '/compras/recepciones'              => ['Compras y Proveedores', '/Cycsa/publico/compras/recepciones', 'Recepciones'],
    '/compras'                          => ['Compras y Proveedores', '/Cycsa/publico/compras/proveedores', ''],
    '/inventario/lotes'                 => ['Inventario y Kárdex', '/Cycsa/publico/inventario', 'Lotes'],
    '/inventario/kardex'                => ['Inventario y Kárdex', '/Cycsa/publico/inventario', 'Kárdex'],
    '/inventario'                       => ['Inventario y Kárdex', '/Cycsa/publico/inventario', ''],
    '/contabilidad/cuentas'             => ['Contabilidad', '/Cycsa/publico/contabilidad/cuentas', 'Catálogo de Cuentas'],
    '/contabilidad/cxc'                 => ['Contabilidad', '/Cycsa/publico/contabilidad/cuentas', 'Cuentas por Cobrar'],
    '/contabilidad/cxp/detalle'         => ['Contabilidad', '/Cycsa/publico/contabilidad/cxp', 'Detalle CXP'],
    '/contabilidad/cxp'                 => ['Contabilidad', '/Cycsa/publico/contabilidad/cuentas', 'Cuentas por Pagar'],
    '/contabilidad/bancos'              => ['Contabilidad', '/Cycsa/publico/contabilidad/cuentas', 'Bancos y Caja'],
    '/contabilidad/diario'              => ['Contabilidad', '/Cycsa/publico/contabilidad/cuentas', 'Libro Diario'],
    '/contabilidad/balance'             => ['Contabilidad', '/Cycsa/publico/contabilidad/cuentas', 'Balance General'],
    '/contabilidad/resultados'          => ['Contabilidad', '/Cycsa/publico/contabilidad/cuentas', 'Estado de Resultados'],
    '/contabilidad'                     => ['Contabilidad', '/Cycsa/publico/contabilidad/cuentas', ''],
    '/nomina/empleados'                 => ['Nómina y RRHH', '/Cycsa/publico/nomina', 'Colaboradores'],
    '/nomina/anticipos'                 => ['Nómina y RRHH', '/Cycsa/publico/nomina', 'Anticipos'],
    '/nomina/crear'                     => ['Nómina y RRHH', '/Cycsa/publico/nomina', 'Nueva Nómina'],
    '/nomina/detalle'                   => ['Nómina y RRHH', '/Cycsa/publico/nomina', 'Detalle de Nómina'],
    '/nomina'                           => ['Nómina y RRHH', '/Cycsa/publico/nomina', ''],
    '/prestamos/crear'                  => ['Préstamos Bancarios', '/Cycsa/publico/prestamos', 'Nuevo Préstamo'],
    '/prestamos/detalle'                => ['Préstamos Bancarios', '/Cycsa/publico/prestamos', 'Detalle de Préstamo'],
    '/prestamos'                        => ['Préstamos Bancarios', '/Cycsa/publico/prestamos', ''],
    '/activos-fijos/crear'              => ['Activos Fijos', '/Cycsa/publico/activos-fijos', 'Nuevo Activo'],
    '/activos-fijos/editar'             => ['Activos Fijos', '/Cycsa/publico/activos-fijos', 'Editar Activo'],
    '/activos-fijos/detalle'            => ['Activos Fijos', '/Cycsa/publico/activos-fijos', 'Detalle de Activo'],
    '/activos-fijos/depreciacion'       => ['Activos Fijos', '/Cycsa/publico/activos-fijos', 'Depreciación'],
    '/activos-fijos'                    => ['Activos Fijos', '/Cycsa/publico/activos-fijos', ''],
    '/proyectos/crear'                  => ['Proyectos y Obras', '/Cycsa/publico/proyectos', 'Nuevo Proyecto'],
    '/proyectos/editar'                 => ['Proyectos y Obras', '/Cycsa/publico/proyectos', 'Editar Proyecto'],
    '/proyectos/detalle'                => ['Proyectos y Obras', '/Cycsa/publico/proyectos', 'Detalle de Proyecto'],
    '/proyectos'                        => ['Proyectos y Obras', '/Cycsa/publico/proyectos', ''],
    '/reportes/ver'                     => ['Centro de Reportes', '/Cycsa/publico/reportes', 'Detalle de Reporte'],
    '/reportes'                         => ['Centro de Reportes', '/Cycsa/publico/reportes', ''],
    '/productos/crear'                  => ['Productos / Ensayos', '/Cycsa/publico/productos', 'Nuevo Producto / Ensayo'],
    '/productos/editar'                 => ['Productos / Ensayos', '/Cycsa/publico/productos', 'Editar Producto / Ensayo'],
    '/productos'                        => ['Productos / Ensayos', '/Cycsa/publico/productos', ''],
    '/clientes/crear'                   => ['Clientes', '/Cycsa/publico/clientes', 'Nuevo Cliente'],
    '/clientes/editar'                  => ['Clientes', '/Cycsa/publico/clientes', 'Editar Cliente'],
    '/clientes'                         => ['Clientes', '/Cycsa/publico/clientes', ''],
    '/usuarios/crear'                   => ['Gestión de Usuarios', '/Cycsa/publico/usuarios', 'Nuevo Usuario'],
    '/usuarios/editar'                  => ['Gestión de Usuarios', '/Cycsa/publico/usuarios', 'Editar Usuario'],
    '/usuarios'                         => ['Gestión de Usuarios', '/Cycsa/publico/usuarios', ''],
    '/roles/crear'                      => ['Roles y Permisos', '/Cycsa/publico/roles', 'Nuevo Rol'],
    '/roles/editar'                     => ['Roles y Permisos', '/Cycsa/publico/roles', 'Editar Rol'],
    '/roles'                            => ['Roles y Permisos', '/Cycsa/publico/roles', ''],
    '/plantillas/estudio'               => ['Plantillas ISO 17025', '/Cycsa/publico/plantillas', 'Estudio de Plantilla'],
    '/plantillas'                       => ['Plantillas ISO 17025', '/Cycsa/publico/plantillas', ''],
    '/panel/bitacora'                   => ['Panel de Control', '/Cycsa/publico/panel', 'Bitácora de Auditoría'],
    '/panel'                            => ['Panel de Control', '/Cycsa/publico/panel', ''],
];

$moduloActualNombre = '';
$moduloActualUrl = '/Cycsa/publico/panel';
$moduloSubNombre = '';

if ($rutaPath !== '') {
    foreach ($mapaRutasMiga as $segmento => $datosMiga) {
        if (strpos($rutaPath, $segmento) !== false) {
            [$moduloActualNombre, $moduloActualUrl, $moduloSubNombre] = $datosMiga;
            break;
        }
    }
}

if ($moduloActualNombre === '') {
    $tituloLimpio = trim((string)preg_replace('/\s*[-·]\s*(Cycsa|CYCSA|LIMS)(\s+ERP)?\s*$/iu', '', (string)($titulo ?? 'Panel de Control')));
    $moduloActualNombre = $tituloLimpio !== '' ? $tituloLimpio : 'Panel de Control';
}
if (!empty($crumbModulo)) {
    $moduloActualNombre = (string)$crumbModulo;
}
if (!empty($crumbModuloUrl)) {
    $moduloActualUrl = (string)$crumbModuloUrl;
}
if (isset($crumbSub) && $crumbSub !== '') {
    $moduloSubNombre = (string)$crumbSub;
}
?>

    <!-- Barra Superior Única (4 elementos: Marca, Botón Módulos, Miga de Pan, Usuario) -->
    <header class="cy-topbar">
        <a class="cy-logo" href="/Cycsa/publico/panel">CYCSA</a>
        <button class="cy-btn-mod" type="button" data-bs-toggle="offcanvas" data-bs-target="#cyModulos" aria-controls="cyModulos">
            <svg width="16" height="16" viewBox="0 0 16 16" fill="currentColor" aria-hidden="true">
                <rect x="1" y="1" width="6" height="6" rx="1.5"/>
                <rect x="9" y="1" width="6" height="6" rx="1.5"/>
                <rect x="1" y="9" width="6" height="6" rx="1.5"/>
                <rect x="9" y="9" width="6" height="6" rx="1.5"/>
            </svg>
            Módulos
        </button>
        <nav class="cy-crumb" aria-label="Ruta">
            <span class="sep">/</span>
            <?php if ($moduloSubNombre !== ''): ?>
                <a href="<?= htmlspecialchars($moduloActualUrl, ENT_QUOTES, 'UTF-8') ?>" id="crumb-modulo"><?= htmlspecialchars($moduloActualNombre, ENT_QUOTES, 'UTF-8') ?></a>
                <span class="sep" id="crumb-sep-sub">/</span>
                <span class="cur" id="crumb-cur-sub"><?= htmlspecialchars($moduloSubNombre, ENT_QUOTES, 'UTF-8') ?></span>
            <?php else: ?>
                <span class="cur" id="crumb-modulo"><?= htmlspecialchars($moduloActualNombre, ENT_QUOTES, 'UTF-8') ?></span>
            <?php endif; ?>
        </nav>
        <span class="cy-user">
            <span class="cy-user-label"><?= htmlspecialchars($nombreUsuarioLayout, ENT_QUOTES, 'UTF-8') ?> · <?= htmlspecialchars($nombreRol, ENT_QUOTES, 'UTF-8') ?></span>
            <a href="/Cycsa/publico/logout" class="cy-logout-link" title="Cerrar Sesión" aria-label="Cerrar Sesión">Salir</a>
        </span>
    </header>

    <!-- Lanzador de Módulos (Offcanvas #cyModulos cerrado por defecto, filtrado por permisos RBAC) -->
    <div class="offcanvas offcanvas-start" tabindex="-1" id="cyModulos" aria-labelledby="cyModulosLabel">
        <div class="offcanvas-header">
            <div>
                <h2 class="offcanvas-title" id="cyModulosLabel">Módulos del Sistema</h2>
                <div class="cy-panel-meta-line">
                    <?= $totalModulosHabilitados ?> <?= $totalModulosHabilitados === 1 ? 'módulo habilitado' : 'módulos habilitados' ?> para <?= htmlspecialchars($nombreRol, ENT_QUOTES, 'UTF-8') ?>
                </div>
            </div>
            <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Cerrar"></button>
        </div>
        <div class="offcanvas-body">
            <div class="cy-modulos-grid">
                <?php foreach ($modulosHabilitados as $moduloItem): ?>
                    <a class="cy-modulo-card<?= !empty($moduloItem['activo']) ? ' is-active' : '' ?>" href="<?= htmlspecialchars($moduloItem['href'], ENT_QUOTES, 'UTF-8') ?>">
                        <span class="cy-modulo-title"><?= htmlspecialchars($moduloItem['titulo'], ENT_QUOTES, 'UTF-8') ?></span>
                        <span class="cy-modulo-cat"><?= htmlspecialchars($moduloItem['categoria'], ENT_QUOTES, 'UTF-8') ?></span>
                    </a>
                <?php endforeach; ?>
            </div>
        </div>
    </div>

    <main class="main-content">
        <div class="content-wrapper">
            <?= $contenido ?? '' ?>
        </div>
    </main>

    <script>
        document.addEventListener('DOMContentLoaded', () => {
            // Verificar cada 5 segundos si otra sesión activa cerró la nuestra en segundo plano (con cache-busting y JSON)
            setInterval(() => {
                fetch('<?= obtenerBaseUrl() ?>/verificar-sesion-activa?_t=' + Date.now())
                    .then(res => res.json())
                    .then(data => {
                        if (data && data.status === 'kicked') {
                            window.location.href = '<?= obtenerBaseUrl() ?>/login';
                        }
                    })
                    .catch(err => console.error('Error de conexión al validar sesión única:', err));
            }, 5000);

            // Protección universal contra doble clic / doble envío de formularios (Bancos, CXC, Diario, LIMS, etc.)
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

            // TAREA-056: Prevenir cambio accidental por scroll de rueda o flechas en inputs numéricos
            document.addEventListener('wheel', function(e) {
                const el = document.activeElement;
                if (el && (el.type === 'number' || el.getAttribute('inputmode') === 'decimal' || el.getAttribute('inputmode') === 'numeric')) {
                    el.blur();
                }
            }, { passive: true });

            document.addEventListener('keydown', function(e) {
                if ((e.key === 'ArrowUp' || e.key === 'ArrowDown') && document.activeElement && document.activeElement.type === 'number') {
                    e.preventDefault();
                }
            });

            // Normalización automática de coma a punto en campos decimales
            document.addEventListener('blur', function(e) {
                const el = e.target;
                if (el && el.getAttribute && el.getAttribute('inputmode') === 'decimal' && typeof el.value === 'string') {
                    el.value = el.value.replace(',', '.').trim();
                }
            }, true);
        });
    </script>

    <!-- Bootstrap 5.3.3 JS Bundle con Popper (Modales, tooltips, tabs, offcanvas y dropdowns interactivos) -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
</body>
</html>
