<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = new \Cycsa\Nucleo\Aplicacion();
try {
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $prices = $db->query('SELECT id, nombre_comercial, precio FROM productos WHERE id IN (16, 8, 18)')->fetchAll(PDO::FETCH_ASSOC);
    print_r($prices);
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
