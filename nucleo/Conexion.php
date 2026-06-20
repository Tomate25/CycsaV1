<?php

namespace Cycsa\Nucleo;

use PDO;
use PDOException;

class Conexion {
    private static ?PDO $instancia = null;

    private function __construct() {
        // Privado para evitar instanciación directa externa
    }

    public static function obtenerInstancia(): PDO {
        if (self::$instancia === null) {
            try {
                // Cargamos el archivo de configuración indexando la ruta
                $config = require __DIR__ . '/../configuracion/database.php';
                
                $dsn = sprintf(
                    "%s:host=%s;dbname=%s;charset=%s",
                    $config['driver'],
                    $config['host'],
                    $config['database'],
                    $config['charset']
                );

                self::$instancia = new PDO(
                    $dsn,
                    $config['username'],
                    $config['password'],
                    $config['options']
                );
            } catch (PDOException $e) {
                // En producción no debemos mostrar detalles internos de la base de datos
                if (($_ENV['APP_ENV'] ?? 'produccion') === 'local') {
                    die("Error de conexión crítica: " . $e->getMessage());
                } else {
                    die("Error temporal en el servidor de datos. Por favor, intente más tarde.");
                }
            }
        }

        return self::$instancia;
    }

    // Evitamos que se clone la instancia
    private function __clone() {}
}