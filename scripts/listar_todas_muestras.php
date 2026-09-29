<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Conexion.php';
$db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

echo "=== MUESTRAS EN HOJAS SOLICITUD ===\n";
$stmt = $db->query("SELECT id, id_os, numero_registro, muestras_json FROM hojas_solicitud ORDER BY id ASC");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $m = json_decode($r['muestras_json'], true) ?: [];
    $nombres = array_column($m, 'nombre_muestra');
    echo "Hoja #{$r['id']} (OS {$r['id_os']}, Reg: {$r['numero_registro']}): " . implode(', ', $nombres) . "\n";
}

echo "\n=== MUESTRAS EN RECEPCION MUESTRAS ===\n";
$stmt2 = $db->query("SELECT id, id_os, codigo_muestra, tipo_muestra FROM recepcion_muestras ORDER BY id ASC");
while ($r = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    echo "Recepcion #{$r['id']} (OS {$r['id_os']}): {$r['codigo_muestra']} ({$r['tipo_muestra']})\n";
}

echo "\n=== SECUENCIAS MUESTRAS ===\n";
$stmt3 = $db->query("SELECT * FROM secuencias_muestras");
while ($r = $stmt3->fetch(PDO::FETCH_ASSOC)) {
    echo json_encode($r) . "\n";
}
