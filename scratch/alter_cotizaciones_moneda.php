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
                $_ENV[trim($parts[0])] = trim($parts[1]);
            }
        }
    }
}

loadEnvFile('C:/xampp/htdocs/Cycsa/.env');
loadEnvFile('C:/xampp/htdocs/Cycsa/.env.local');

try {
    $pdo = Conexion::obtenerInstancia();
    
    // Check if column already exists
    $check = $pdo->query("SHOW COLUMNS FROM cotizaciones LIKE 'tipo_moneda'");
    if ($check->rowCount() == 0) {
        $pdo->exec("ALTER TABLE cotizaciones ADD COLUMN tipo_moneda INT DEFAULT 1 AFTER id_cliente");
        echo "Successfully added 'tipo_moneda' column to 'cotizaciones' table!\n";
    } else {
        echo "Column 'tipo_moneda' already exists in 'cotizaciones' table.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
