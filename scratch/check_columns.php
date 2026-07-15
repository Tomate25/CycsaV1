<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = new \Cycsa\Nucleo\Aplicacion();
try {
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    foreach (['cotizaciones', 'cotizacion_detalles', 'ordenes_servicio', 'recepcion_muestras', 'lotes_muestras', 'ensayo_edades'] as $table) {
        echo "=== $table ===\n";
        $cols = $db->query("DESCRIBE `$table`")->fetchAll(PDO::FETCH_ASSOC);
        foreach ($cols as $c) {
            echo "  {$c['Field']} ({$c['Type']})\n";
        }
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
