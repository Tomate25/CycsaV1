<?php

namespace Cycsa\Modulos\Usuarios\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Nucleo\Conexion;
use PDO;

class PanelControlador extends ControladorBase {
    
    public function index(Peticion $peticion, Respuesta $respuesta) {
        // 🔒 BARRERA DE SEGURIDAD: Si no hay sesión, lo mandamos al login
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            return;
        }

        $esAdmin = (($_SESSION['usuario_rol'] ?? 0) == 1);
        
        // Construir cajón de aplicaciones según los permisos del usuario
        $cajon_aplicaciones = [];
        
        if (tienePermiso('clientes', 'ver')) {
            $cajon_aplicaciones[] = [
                'nombre' => 'Clientes',
                'link' => '/Cycsa/publico/clientes',
                'icon' => 'fa-solid fa-address-book',
                'desc' => 'Gestión y catálogo de clientes comerciales.',
                'color' => 'linear-gradient(135deg, #2563eb, #1d4ed8)', // Azul
            ];
        }
        if (tienePermiso('productos', 'ver')) {
            $cajon_aplicaciones[] = [
                'nombre' => 'Productos / Ensayos',
                'link' => '/Cycsa/publico/productos',
                'icon' => 'fa-solid fa-flask-vial',
                'desc' => 'Catálogo de ensayos de laboratorio y tarifas.',
                'color' => 'linear-gradient(135deg, #059669, #047857)', // Verde
            ];
        }
        if (tienePermiso('cotizaciones', 'ver')) {
            $cajon_aplicaciones[] = [
                'nombre' => 'Cotizaciones',
                'link' => '/Cycsa/publico/cotizaciones',
                'icon' => 'fa-solid fa-file-invoice-dollar',
                'desc' => 'Creación y seguimiento de propuestas comerciales.',
                'color' => 'linear-gradient(135deg, #d97706, #b45309)', // Naranja
            ];
        }
        if (tienePermiso('operaciones', 'ver')) {
            $cajon_aplicaciones[] = [
                'nombre' => 'Hojas de Servicio',
                'link' => '/Cycsa/publico/hojas-servicio',
                'icon' => 'fa-solid fa-file-signature',
                'desc' => 'Recepción, registro y aprobación de hojas de servicio CYCSA-RT-FM-13.',
                'color' => 'linear-gradient(135deg, #4f46e5, #3730a3)', // Indigo
            ];
        }
        if (tienePermiso('operaciones', 'ver')) {
            $cajon_aplicaciones[] = [
                'nombre' => 'Operaciones LIMS',
                'link' => '/Cycsa/publico/operaciones',
                'icon' => 'fa-solid fa-gears',
                'desc' => 'Órdenes de servicio y recepción de muestras.',
                'color' => 'linear-gradient(135deg, #0284c7, #0369a1)', // Celeste
            ];
        }
        if (tienePermiso('laboratorio', 'ver')) {
            $cajon_aplicaciones[] = [
                'nombre' => 'Laboratorio',
                'link' => '/Cycsa/publico/laboratorio',
                'icon' => 'fa-solid fa-flask',
                'desc' => 'Registro de rupturas y control de calidad.',
                'color' => 'linear-gradient(135deg, #7c3aed, #5b21b6)', // Morado
            ];
        }
        if (tienePermiso('contabilidad', 'ver')) {
            $cajon_aplicaciones[] = [
                'nombre' => 'Contabilidad ERP',
                'link' => '/Cycsa/publico/contabilidad/cuentas',
                'icon' => 'fa-solid fa-calculator',
                'desc' => 'Catálogo de cuentas, diario y estados contables.',
                'color' => 'linear-gradient(135deg, #db2777, #be185d)', // Rosado
            ];
        }
        if (tienePermiso('usuarios', 'ver')) {
            $cajon_aplicaciones[] = [
                'nombre' => 'Gestión de Usuarios',
                'link' => '/Cycsa/publico/usuarios',
                'icon' => 'fa-solid fa-users',
                'desc' => 'Administración de usuarios, roles y accesos.',
                'color' => 'linear-gradient(135deg, #475569, #334155)', // Slate
            ];
        }
        
        // Ajustes y Bitácora solo para administradores
        if ($esAdmin) {
            $cajon_aplicaciones[] = [
                'nombre' => 'Condiciones Comerciales',
                'link' => '/Cycsa/publico/configuracion',
                'icon' => 'fa-solid fa-sliders',
                'desc' => 'Parámetros del sistema, vehículos y técnicos.',
                'color' => 'linear-gradient(135deg, #0d9488, #0f766e)', // Teal
            ];
            $cajon_aplicaciones[] = [
                'nombre' => 'Bitácora de Auditoría',
                'link' => '/Cycsa/publico/panel/bitacora',
                'icon' => 'fa-solid fa-list-check',
                'desc' => 'Historial de auditoría interna de actividades.',
                'color' => 'linear-gradient(135deg, #e11d48, #be123c)', // Rojo
            ];
        }

        // Si no tiene aplicaciones habilitadas, permitimos cargar el panel informando la falta de módulos asignados.

        // Valores por defecto
        $total_cotizaciones = 0;
        $total_clientes = 0;
        $total_monto_aprobado = 0.0;
        $total_en_revision = 0;
        $distribucion_estados = [];
        $tendencia_mensual = [];
        $top_clientes = [];
        $prioridades_dist = [
            'Alta' => 0,
            'Media' => 0,
            'Normal' => 0
        ];
        $recientes = [];

        // Consultas exclusivas para el Administrador (Analíticas del Dashboard)
        if ($esAdmin) {
            try {
                $db = Conexion::obtenerInstancia();
                
                // 1. KPI metrics
                $stmt = $db->query("SELECT COUNT(*) FROM cotizaciones");
                $total_cotizaciones = (int) $stmt->fetchColumn();
                
                $stmt = $db->query("SELECT COUNT(DISTINCT id_cliente) FROM cotizaciones");
                $total_clientes = (int) $stmt->fetchColumn();
                
                $stmt = $db->query("SELECT SUM(total) FROM cotizaciones WHERE estado IN ('Aprobada por Cliente', 'Aprobada Internamente', 'Enviada al Cliente')");
                $total_monto_aprobado = (float) ($stmt->fetchColumn() ?? 0.0);
                
                $stmt = $db->query("SELECT COUNT(*) FROM cotizaciones WHERE estado = 'En Revision'");
                $total_en_revision = (int) $stmt->fetchColumn();
                
                // 2. Status Distribution
                $stmt = $db->query("SELECT estado, COUNT(*) as cantidad FROM cotizaciones GROUP BY estado");
                $distribucion_estados = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 3. Monthly Trend (last 6 months)
                $meses_es = [
                    '01' => 'Ene', '02' => 'Feb', '03' => 'Mar', '04' => 'Abr', '05' => 'May', '06' => 'Jun',
                    '07' => 'Jul', '08' => 'Ago', '09' => 'Sep', '10' => 'Oct', '11' => 'Nov', '12' => 'Dic'
                ];
                
                for ($i = 5; $i >= 0; $i--) {
                    $timestamp = strtotime("-$i months");
                    $mes_key = date('Y-m', $timestamp);
                    $num_mes = date('m', $timestamp);
                    $tendencia_mensual[$mes_key] = [
                        'mes' => $mes_key,
                        'nombre_mes' => $meses_es[$num_mes] ?? date('M', $timestamp),
                        'total' => 0.0,
                        'cantidad' => 0
                    ];
                }
                
                $fecha_limite = date('Y-m-01', strtotime('-5 months')) . ' 00:00:00';
                $sql_trend = "SELECT DATE_FORMAT(fecha_creacion, '%Y-%m') as mes, SUM(total) as total, COUNT(*) as cantidad 
                              FROM cotizaciones 
                              WHERE fecha_creacion >= :fecha_limite
                              GROUP BY DATE_FORMAT(fecha_creacion, '%Y-%m')";
                $stmt = $db->prepare($sql_trend);
                $stmt->execute(['fecha_limite' => $fecha_limite]);
                $resultados_trend = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($resultados_trend as $row) {
                    $mes = $row['mes'];
                    if (isset($tendencia_mensual[$mes])) {
                        $tendencia_mensual[$mes]['total'] = (float)$row['total'];
                        $tendencia_mensual[$mes]['cantidad'] = (int)$row['cantidad'];
                    }
                }
                $tendencia_mensual = array_values($tendencia_mensual);
                
                // 4. Top Clients (Top 5 clients)
                $sql_top_clients = "SELECT cl.nombre_razon_social as cliente, SUM(c.total) as total_monto, COUNT(c.id) as cantidad_cotizaciones
                                    FROM cotizaciones c
                                    INNER JOIN clientes cl ON c.id_cliente = cl.id
                                    GROUP BY c.id_cliente, cl.nombre_razon_social
                                    ORDER BY total_monto DESC
                                    LIMIT 5";
                $stmt = $db->query($sql_top_clients);
                $top_clientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                // 5. Priority Distribution
                $stmt = $db->query("SELECT prioridad, COUNT(*) as cantidad FROM cotizaciones GROUP BY prioridad");
                $resultados_prioridad = $stmt->fetchAll(PDO::FETCH_ASSOC);
                
                foreach ($resultados_prioridad as $row) {
                    $prio = $row['prioridad'];
                    if (isset($prioridades_dist[$prio])) {
                        $prioridades_dist[$prio] = (int)$row['cantidad'];
                    }
                }
                
                // 6. Recent Activity
                $sql_recent = "SELECT c.codigo, c.total, c.estado, c.fecha_creacion, cl.nombre_razon_social as cliente
                               FROM cotizaciones c
                               INNER JOIN clientes cl ON c.id_cliente = cl.id
                               ORDER BY c.fecha_creacion DESC, c.id DESC
                               LIMIT 5";
                $stmt = $db->query($sql_recent);
                $recientes = $stmt->fetchAll(PDO::FETCH_ASSOC);
            } catch (\Exception $e) {
                error_log("Error al cargar KPIs del dashboard: " . $e->getMessage());
            }
        }
        
        // Mostramos la vista del panel y le pasamos los datos del usuario y del dashboard
        $this->renderizar('usuarios/vistas/panel', [
            'titulo' => 'Panel de Control - Cycsa',
            'nombre' => $_SESSION['usuario_nombre'],
            'rol_id' => $_SESSION['usuario_rol'],
            'esAdmin' => $esAdmin,
            'cajon_aplicaciones' => $cajon_aplicaciones,
            'kpis' => [
                'total_cotizaciones' => $total_cotizaciones,
                'total_clientes' => $total_clientes,
                'total_monto_aprobado' => $total_monto_aprobado,
                'total_en_revision' => $total_en_revision
            ],
            'distribucion_estados' => $distribucion_estados,
            'tendencia_mensual' => $tendencia_mensual,
            'top_clientes' => $top_clientes,
            'distribucion_prioridad' => $prioridades_dist,
            'recientes' => $recientes
        ]);
    }

    public function bitacora(Peticion $peticion, Respuesta $respuesta) {
        // 🔒 BARRERA DE SEGURIDAD: Solo Administradores (rol 1)
        if (!isset($_SESSION['usuario_id']) || ($_SESSION['usuario_rol'] ?? 0) != 1) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }
        
        $db = Conexion::obtenerInstancia();
        
        // Obtener filtros de la URL
        $busqueda = $_GET['q'] ?? '';
        $modulo_sel = $_GET['modulo'] ?? '';
        $usuario_sel = $_GET['usuario'] ?? '';
        
        // Construir la consulta con filtros
        $sql = "SELECT b.* 
                FROM bitacora b
                WHERE 1=1";
        
        $params = [];
        
        if (!empty($busqueda)) {
            $sql .= " AND (b.descripcion LIKE :q OR b.usuario_nombre LIKE :q OR b.accion LIKE :q OR b.ip LIKE :q)";
            $params['q'] = '%' . trim($busqueda) . '%';
        }
        
        if (!empty($modulo_sel)) {
            $sql .= " AND b.modulo = :modulo";
            $params['modulo'] = $modulo_sel;
        }
        
        if (!empty($usuario_sel)) {
            $sql .= " AND b.id_usuario = :usuario";
            $params['usuario'] = (int)$usuario_sel;
        }
        
        $sql .= " ORDER BY b.id DESC LIMIT 300";
        
        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $logs = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        // Obtener lista de módulos únicos para el filtro
        $stmtMod = $db->query("SELECT DISTINCT modulo FROM bitacora ORDER BY modulo ASC");
        $modulos = $stmtMod->fetchAll(PDO::FETCH_COLUMN);
        
        // Obtener lista de usuarios para el filtro
        $stmtUsr = $db->query("SELECT id, nombre FROM usuarios ORDER BY nombre ASC");
        $usuarios = $stmtUsr->fetchAll(PDO::FETCH_ASSOC);

        // Calcular estadísticas rápidas para la bitacora
        $totalLogsStmt = $db->query("SELECT COUNT(*) FROM bitacora");
        $total_logs = (int) $totalLogsStmt->fetchColumn();
        
        $hoyStmt = $db->query("SELECT COUNT(*) FROM bitacora WHERE DATE(fecha_creacion) = CURDATE()");
        $logs_hoy = (int) $hoyStmt->fetchColumn();

        $usuariosActivosStmt = $db->query("SELECT COUNT(DISTINCT id_usuario) FROM bitacora WHERE id_usuario IS NOT NULL");
        $usuarios_activos = (int) $usuariosActivosStmt->fetchColumn();

        $alertasStmt = $db->query("SELECT COUNT(*) FROM bitacora WHERE accion LIKE '%rechazar%' OR accion LIKE '%devolver%' OR accion LIKE '%observar%'");
        $criticos = (int) $alertasStmt->fetchColumn();
        
        $this->renderizar('usuarios/vistas/bitacora', [
            'titulo' => 'Bitácora de Auditoría - Cycsa',
            'logs' => $logs,
            'modulos_disponibles' => $modulos,
            'usuarios_disponibles' => $usuarios,
            'busqueda' => $busqueda,
            'modulo_seleccionado' => $modulo_sel,
            'usuario_seleccionado' => $usuario_sel,
            'stats' => [
                'total' => $total_logs,
                'hoy' => $logs_hoy,
                'usuarios_activos' => $usuarios_activos,
                'criticos' => $criticos
            ]
        ]);
    }
}