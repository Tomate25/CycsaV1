<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Conexion.php';
$db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

$os6 = $db->query("SELECT * FROM ordenes_servicio WHERE id = 6")->fetch(PDO::FETCH_ASSOC);
echo "OS 6: " . json_encode($os6) . "\n";
