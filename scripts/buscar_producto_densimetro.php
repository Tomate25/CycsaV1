<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Conexion.php';
$db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

$cols = $db->query("SHOW COLUMNS FROM productos")->fetchAll(PDO::FETCH_COLUMN);
echo "Cols productos: " . implode(', ', $cols) . "\n";
$stmt = $db->query("SELECT id, no_item, formato_id, tipo_muestra, ensayo_servicio, nombre_comercial, norma_astm, precio FROM productos WHERE ensayo_servicio LIKE '%Densímetro%' OR nombre_comercial LIKE '%Densímetro%' OR ensayo_servicio LIKE '%Densimetro%'");
$prods = $stmt->fetchAll(PDO::FETCH_ASSOC);
print_r($prods);

// Also check clients
$stmt2 = $db->query("SELECT id, nombre_razon_social, contacto_nombre, email, telefono, direccion FROM clientes LIMIT 5");
$clientes = $stmt2->fetchAll(PDO::FETCH_ASSOC);
echo "\n--- CLIENTES ---\n";
print_r($clientes);
