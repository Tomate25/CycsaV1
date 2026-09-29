<?php
require_once __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../app/Core/Conexion.php';
$db = \Cycsa\Nucleo\Conexion::obtenerInstancia();

echo "=== ORDENES DE SERVICIO ===\n";
$stmt = $db->query("SELECT os.id, os.codigo_os, os.id_cotizacion, os.estado, os.requiere_muestreo, c.codigo as cot_codigo, c.estado as cot_estado FROM ordenes_servicio os LEFT JOIN cotizaciones c ON c.id = os.id_cotizacion ORDER BY os.id DESC LIMIT 10");
while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) {
    echo "OS ID: {$r['id']} | Cod: {$r['codigo_os']} | CotID: {$r['id_cotizacion']} ({$r['cot_codigo']}) | Estado: {$r['estado']} | ReqMuestreo: {$r['requiere_muestreo']}\n";
    
    // Check Cotizacion Detalles
    $stmtCd = $db->prepare("SELECT id, descripcion_ensayo, cantidad, (resultados_json IS NOT NULL) as tiene_matriz FROM cotizacion_detalles WHERE id_cotizacion = :id_cot");
    $stmtCd->execute(['id_cot' => $r['id_cotizacion']]);
    while ($cd = $stmtCd->fetch(PDO::FETCH_ASSOC)) {
        echo "   -> Detalle #{$cd['id']}: {$cd['descripcion_ensayo']} (Cant: {$cd['cantidad']}) | TieneMatriz: {$cd['tiene_matriz']}\n";
    }

    // Check Hojas
    $stmtHs = $db->prepare("SELECT id, numero_registro, codigo_documento FROM hojas_solicitud WHERE id_os = :id_os");
    $stmtHs->execute(['id_os' => $r['id']]);
    while ($hs = $stmtHs->fetch(PDO::FETCH_ASSOC)) {
        echo "   -> Hoja #{$hs['id']}: {$hs['codigo_documento']} (Reg: {$hs['numero_registro']})\n";
    }
}
