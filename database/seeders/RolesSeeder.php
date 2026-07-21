<?php
namespace Database\Seeders;

use PDO;

/**
 * Seeder para la tabla roles.
 */
class RolesSeeder
{
    /**
     * Ejecuta el seeder.
     *
     * @return void
     */
    public function run()
    {
        // Asumiendo una conexión PDO global o inyectada.
        global $pdo;

        $roles = [
            ['nombre' => 'Administrador', 'descripcion' => 'Acceso total al sistema'],
            ['nombre' => 'Gerente', 'descripcion' => 'Acceso a reportes y configuraciones'],
            ['nombre' => 'Laboratorista', 'descripcion' => 'Acceso a captura de resultados y muestras'],
            ['nombre' => 'Ventas', 'descripcion' => 'Acceso a clientes y cotizaciones']
        ];

        $stmt = $pdo->prepare("INSERT INTO roles (nombre, descripcion) VALUES (:nombre, :descripcion)");

        foreach ($roles as $rol) {
            $stmt->execute([
                ':nombre' => $rol['nombre'],
                ':descripcion' => $rol['descripcion']
            ]);
        }
    }
}
