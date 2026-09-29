<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Conexion.php';
$db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

echo "=== TABLES ===\n";
$tables = $db->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
echo implode(', ', $tables) . "\n\n";

echo "=== HOJAS SOLICITUD ===\n";
$stmt = $db->query("SELECT id, id_os, codigo_documento, numero_registro, procedencia_punto_muestreo, muestras_json FROM hojas_solicitud ORDER BY id DESC LIMIT 5");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "ID: {$r['id']} | OS: {$r['id_os']} | Reg: {$r['numero_registro']} | Procedencia: {$r['procedencia_punto_muestreo']}\n";
    echo "  Muestras: {$r['muestras_json']}\n";
}

echo "\n=== COTIZACION DETALLES CON RESULTADOS_JSON ===\n";
$rowsCd = $db->query("SELECT id, id_cotizacion, descripcion_ensayo, cantidad, SUBSTRING(resultados_json, 1, 300) as res_json FROM cotizacion_detalles WHERE resultados_json IS NOT NULL ORDER BY id DESC LIMIT 5")->fetchAll(PDO::FETCH_ASSOC);
foreach ($rowsCd as $cd) {
    echo json_encode($cd) . "\n";
}
