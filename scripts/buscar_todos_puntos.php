<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Conexion.php';
$db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

echo "--- BUSCANDO 'Punto' EN HOJAS SOLICITUD ---\n";
$stmt = $db->query("SELECT id, id_os, numero_registro, muestras_json FROM hojas_solicitud");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $m = json_decode($r['muestras_json'], true);
    if (is_array($m)) {
        foreach ($m as $item) {
            $nom = $item['nombre_muestra'] ?? '';
            if (stripos($nom, 'punto') !== false) {
                echo "Hoja ID {$r['id']} (OS {$r['id_os']}): nombre_muestra = '$nom'\n";
            }
        }
    }
}

echo "\n--- BUSCANDO 'Punto' EN COTIZACION DETALLES (filas de matriz) ---\n";
$stmt2 = $db->query("SELECT id, id_cotizacion, descripcion_ensayo, resultados_json FROM cotizacion_detalles WHERE resultados_json IS NOT NULL");
while ($r = $stmt2->fetch(PDO::FETCH_ASSOC)) {
    $res = json_decode($r['resultados_json'], true);
    if (is_array($res) && isset($res['filas'])) {
        foreach ($res['filas'] as $f) {
            $codLab = $f['Código laboratorio'] ?? ($f['Codigo laboratorio'] ?? '');
            if (stripos($codLab, 'punto') !== false) {
                echo "Detalle ID {$r['id']} (Cot {$r['id_cotizacion']}): Código laboratorio = '$codLab'\n";
            }
        }
    }
}
