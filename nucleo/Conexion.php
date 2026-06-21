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
                // Registrar el error real en el archivo de logs del servidor
                error_log("Error de conexión a DB: " . $e->getMessage());
                
                // Mostrar solo un mensaje genérico al usuario final
                die("Error 500: Fallo interno de conexión. Por favor, intente más tarde.");
            }
        }

        return self::$instancia;
    }

    // Evitamos que se clone la instancia
    private function __clone() {}
}