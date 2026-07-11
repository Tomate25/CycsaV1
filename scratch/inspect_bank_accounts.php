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
    $stmt = $pdo->query("DESCRIBE bancos_cuentas");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']}\n";
    }
    
    // Veamos los datos actuales
    echo "\nRegistros actuales en bancos_cuentas:\n";
    $stmtData = $pdo->query("SELECT * FROM bancos_cuentas");
    $accounts = $stmtData->fetchAll(PDO::FETCH_ASSOC);
    print_r($accounts);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
