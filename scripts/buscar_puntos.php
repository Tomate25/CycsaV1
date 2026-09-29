<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Conexion.php';
$db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

echo "=== HOJAS CON 'Punto' EN muestras_json ===\n";
$stmt = $db->query("SELECT id, id_os, numero_registro, muestras_json FROM hojas_solicitud WHERE muestras_json LIKE '%Punto%'");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "Hoja ID: {$r['id']} | OS: {$r['id_os']} | Reg: {$r['numero_registro']}\n";
    echo "  " . $r['muestras_json'] . "\n";
}

echo "\n=== COTIZACION DETALLES CON 'Punto' EN resultados_json ===\n";
$stmt2 = $db->query("SELECT id, id_cotizacion, descripcion_ensayo, resultados_json FROM cotizacion_detalles WHERE resultados_json LIKE '%Punto%'");
while ($r = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    echo "Detalle ID: {$r['id']} | Cotizacion: {$r['id_cotizacion']} | Ensayo: {$r['descripcion_ensayo']}\n";
    echo "  " . $r['resultados_json'] . "\n";
}
