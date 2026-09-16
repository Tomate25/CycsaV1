<?php
namespace Database\Seeders;

use Cycsa\Nucleo\Conexion;
use PDO;

/**
 * Seeder para la tabla permisos y asignación a roles.
 */
class PermisosSeeder
{
    public function run()
    {
        $pdo = Conexion::obtenerInstancia();

        $pdo->exec("CREATE TABLE IF NOT EXISTS permisos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL UNIQUE,
            descripcion TEXT
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS rol_permiso (
            rol_id INT,
            permiso_id INT,
            PRIMARY KEY(rol_id, permiso_id),
            FOREIGN KEY(rol_id) REFERENCES roles(id) ON DELETE CASCADE,
            FOREIGN KEY(permiso_id) REFERENCES permisos(id) ON DELETE CASCADE
        )");

        $permisos = [
            ['nombre' => 'crear_cotizacion', 'descripcion' => 'Permite crear cotizaciones'],
            ['nombre' => 'aprobar_cotizacion', 'descripcion' => 'Permite aprobar cotizaciones'],
            ['nombre' => 'capturar_resultados', 'descripcion' => 'Permite capturar resultados de laboratorio'],
            ['nombre' => 'ver_reportes', 'descripcion' => 'Permite visualizar reportes gerenciales']
        ];

        $stmt = $pdo->prepare("INSERT INTO permisos (nombre, descripcion) VALUES (:nombre, :descripcion) ON DUPLICATE KEY UPDATE descripcion = VALUES(descripcion)");

        foreach ($permisos as $permiso) {
            $stmt->execute([
                ':nombre' => $permiso['nombre'],
                ':descripcion' => $permiso['descripcion']
            ]);
        }
    }
}
