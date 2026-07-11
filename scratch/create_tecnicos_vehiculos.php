<?php
require_once 'C:/xampp/htdocs/Cycsa/nucleo/Conexion.php';
use Cycsa\Nucleo\Conexion;

function loadEnvFile($path) {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($lines !== false) {
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                continue;
            }
            $parts = explode('=', $line, 2);
            if (count($parts) === 2) {
                $clave = trim($parts[0]);
                $valor = trim($parts[1]);
                $len = strlen($valor);
                if ($len >= 2 && (
                    ($valor[0] === '"' && $valor[$len - 1] === '"') ||
                    ($valor[0] === "'" && $valor[$len - 1] === "'")
                )) {
                    $valor = substr($valor, 1, -1);
                }
                $_ENV[$clave] = $valor;
            }
        }
    }
}

loadEnvFile('C:/xampp/htdocs/Cycsa/.env');
loadEnvFile('C:/xampp/htdocs/Cycsa/.env.local');

try {
    $pdo = Conexion::obtenerInstancia();
    
    // Crear tabla de técnicos
    $sqlTecnicos = "CREATE TABLE IF NOT EXISTS tecnicos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        nombre VARCHAR(150) NOT NULL,
        activo TINYINT(1) DEFAULT 1,
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlTecnicos);
    echo "✓ Tabla `tecnicos` creada con éxito.\n";

    // Crear tabla de vehículos
    $sqlVehiculos = "CREATE TABLE IF NOT EXISTS vehiculos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        placa VARCHAR(50) NOT NULL UNIQUE,
        marca VARCHAR(100) NULL,
        modelo VARCHAR(100) NULL,
        activo TINYINT(1) DEFAULT 1,
        fecha_registro TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;";
    $pdo->exec($sqlVehiculos);
    echo "✓ Tabla `vehiculos` creada con éxito.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
