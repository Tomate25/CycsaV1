<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Cycsa\Nucleo\Aplicacion;
use Cycsa\Nucleo\Conexion;

$app = new Aplicacion();
try {
    $db = Conexion::obtenerInstancia();
    echo "=== SIMULANDO ENRUTAMIENTO POST-LOGIN PARA TODOS LOS USUARIOS ===\n\n";
    $users = $db->query("SELECT u.id, u.nombre, u.email, u.id_rol, r.nombre AS rol_nombre, r.permisos 
                         FROM usuarios u
                         LEFT JOIN roles r ON u.id_rol = r.id")->fetchAll(PDO::FETCH_ASSOC);
                         
    foreach ($users as $u) {
        $rolId = (int)$u['id_rol'];
        $permisos = json_decode($u['permisos'] ?? '', true) ?: [];
        
        // Simular tienePermiso
        $tienePermisoSim = function($modulo, $accion = 'ver') use ($rolId, $permisos) {
            if ($rolId === 1) return true;
            if ($modulo === 'usuarios') return false;
            return isset($permisos[$modulo][$accion]) && ($permisos[$modulo][$accion] == 1 || $permisos[$modulo][$accion] === true);
        };
        
        $destino = '';
        if ($rolId === 1) {
            $destino = '/Cycsa/publico/panel (Administración)';
        } else {
            if ($tienePermisoSim('cotizaciones', 'ver')) {
                $destino = '/Cycsa/publico/cotizaciones';
            } elseif ($tienePermisoSim('clientes', 'ver')) {
                $destino = '/Cycsa/publico/clientes';
            } elseif ($tienePermisoSim('productos', 'ver')) {
                $destino = '/Cycsa/publico/productos';
            } elseif ($tienePermisoSim('laboratorio', 'ver') || $tienePermisoSim('operaciones', 'ver')) {
                $destino = '/Cycsa/publico/operaciones';
            } elseif ($tienePermisoSim('contabilidad', 'ver')) {
                $destino = '/Cycsa/publico/contabilidad/cxc';
            } else {
                $destino = '/Cycsa/publico/logout (ERR: LOGOUT AUTOMÁTICO)';
            }
        }
        
        printf("Usuario: %-30s | Rol: %-15s | Destino: %s\n", 
            substr($u['nombre'], 0, 30), 
            $u['rol_nombre'] ?? 'Sin Rol', 
            $destino
        );
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
