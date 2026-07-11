<?php
require_once __DIR__ . '/../vendor/autoload.php';
new Cycsa\Nucleo\Aplicacion();
$db = Cycsa\Nucleo\Conexion::obtenerInstancia();

echo "=== COLUMNS OF lotes_muestras ===\n";
$q = $db->query("DESCRIBE lotes_muestras")->fetchAll(PDO::FETCH_ASSOC);
foreach($q as $col) {
    echo $col['Field'] . " - " . $col['Type'] . "\n";
}
