<?php
require_once __DIR__ . '/../vendor/autoload.php';
new Cycsa\Nucleo\Aplicacion();
$db = Cycsa\Nucleo\Conexion::obtenerInstancia();

try {
    $db->exec("ALTER TABLE informes_control ADD COLUMN edad_evaluada INT NULL AFTER tipo_informe;");
    echo "Altered table informes_control successfully!\n";
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
