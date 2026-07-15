<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../nucleo/Aplicacion.php';
require_once __DIR__ . '/../nucleo/Conexion.php';
use Cycsa\Nucleo\Aplicacion;
use Cycsa\Nucleo\Conexion;

$app = new Aplicacion();

try {
    $db = Conexion::obtenerInstancia();
    
    // Get all users
    $users = $db->query("SELECT u.id, u.nombre, u.email, r.nombre AS rol_nombre, u.id_rol 
                         FROM usuarios u
                         LEFT JOIN roles r ON u.id_rol = r.id")->fetchAll(PDO::FETCH_ASSOC);
    
    $assignedCreds = [];
    $updateStmt = $db->prepare("UPDATE usuarios SET password = :hash WHERE id = :id");
    
    foreach ($users as $u) {
        $email = $u['email'];
        // Clean name to form a password
        $nameClean = preg_replace('/[^A-Za-z0-9]/', '', $u['nombre']);
        $plainPassword = substr($nameClean, 0, 8) . "2026!";
        
        // Hash it
        $hash = password_hash($plainPassword, PASSWORD_DEFAULT);
        
        // Update database
        $updateStmt->execute([
            'hash' => $hash,
            'id' => $u['id']
        ]);
        
        $assignedCreds[] = [
            'id' => $u['id'],
            'nombre' => $u['nombre'],
            'email' => $email,
            'rol' => $u['rol_nombre'] ?? 'Sin Rol',
            'rol_id' => $u['id_rol'],
            'password' => $plainPassword
        ];
    }
    
    // Write text file in Downloads
    $downloadsPath = 'C:/Users/abdia/Downloads/credenciales_usuarios.txt';
    $fileContent = "====================================================================\n";
    $fileContent .= "              CYCSA ERP - CREDENCIALES DE ACCESO (TEST)\n";
    $fileContent .= "====================================================================\n\n";
    $fileContent .= "Este archivo contiene la lista de usuarios y contraseñas actualizadas\n";
    $fileContent .= "en la base de datos local para pruebas de roles y permisos.\n\n";
    $fileContent .= sprintf("%-4s | %-32s | %-32s | %-16s | %-16s\n", "ID", "Nombre de Usuario", "Correo (Login)", "Rol", "Contraseña");
    $fileContent .= str_repeat("-", 110) . "\n";
    
    foreach ($assignedCreds as $c) {
        $fileContent .= sprintf("%-4d | %-32s | %-32s | %-16s | %-16s\n", 
            $c['id'], 
            substr($c['nombre'], 0, 32), 
            substr($c['email'], 0, 32), 
            substr($c['rol'], 0, 16), 
            $c['password']
        );
    }
    
    $fileContent .= "\n====================================================================\n";
    $fileContent .= "Nota: Para iniciar sesión, ingresa el Correo (Login) y la Contraseña correspondiente.\n";
    
    file_put_contents($downloadsPath, $fileContent);
    
    echo "¡Base de datos actualizada con éxito!\n";
    echo "Archivo de credenciales guardado en: " . $downloadsPath . "\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
