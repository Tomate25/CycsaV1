<?php
namespace Database\Seeders;

use PDO;

/**
 * Seeder para la tabla permisos y asignación a roles.
 */
class PermisosSeeder
{
    /**
     * Ejecuta el seeder.
     *
     * @return void
     */
    public function run()
    {
        global $pdo;

        // Crear tabla de permisos si no existe
        $pdo->exec("CREATE TABLE IF NOT EXISTS permisos (
            id INT AUTO_INCREMENT PRIMARY KEY,
            nombre VARCHAR(100) NOT NULL,
            descripcion TEXT
        )");

        $pdo->exec("CREATE TABLE IF NOT EXISTS rol_permiso (
            rol_id INT,
            permiso_id INT,
            PRIMARY KEY(rol_id, permiso_id),
            FOREIGN KEY(rol_id) REFERENCES roles(id),
            FOREIGN KEY(permiso_id) REFERENCES permisos(id)
        )");

        $permisos = [
            ['nombre' => 'crear_cotizacion', 'descripcion' => 'Permite crear cotizaciones'],
            ['nombre' => 'aprobar_cotizacion', 'descripcion' => 'Permite aprobar cotizaciones'],
            ['nombre' => 'capturar_resultados', 'descripcion' => 'Permite capturar resultados de laboratorio'],
            ['nombre' => 'ver_reportes', 'descripcion' => 'Permite visualizar reportes gerenciales']
        ];

        $stmt = $pdo->prepare("INSERT INTO permisos (nombre, descripcion) VALUES (:nombre, :descripcion)");

        foreach ($permisos as $permiso) {
            $stmt->execute([
                ':nombre' => $permiso['nombre'],
                ':descripcion' => $permiso['descripcion']
            ]);
        }
    }
}
