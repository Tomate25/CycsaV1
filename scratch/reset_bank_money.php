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
    $stmt = $pdo->prepare("UPDATE bancos_cuentas SET saldo_inicial = 0.00, saldo_actual = 0.00");
    $stmt->execute();
    echo "¡Éxito! Saldo inicial y actual de todas las cuentas bancarias restablecidos a 0.00.\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
