<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = new \Cycsa\Nucleo\Aplicacion();
try {
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $clients = $db->query('SELECT id, nombre_razon_social, identificacion FROM clientes')->fetchAll(PDO::FETCH_ASSOC);
    print_r($clients);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
