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
    $stmt = $pdo->query("DESCRIBE cotizaciones");
    echo "Columns in 'cotizaciones':\n";
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "  {$row['Field']} - {$row['Type']}\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
