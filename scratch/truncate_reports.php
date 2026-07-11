<?php
require_once __DIR__ . '/../vendor/autoload.php';
new Cycsa\Nucleo\Aplicacion();
$db = Cycsa\Nucleo\Conexion::obtenerInstancia();

try {
    $db->exec("TRUNCATE TABLE informes_control");
    echo "Truncated informes_control successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
