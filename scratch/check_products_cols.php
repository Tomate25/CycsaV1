<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = new \Cycsa\Nucleo\Aplicacion();
try {
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $cols = $db->query("DESCRIBE `productos`")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cols as $c) {
        echo "  {$c['Field']} ({$c['Type']})\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
