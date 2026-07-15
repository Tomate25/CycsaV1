<?php
require_once __DIR__ . '/../vendor/autoload.php';
$app = new \Cycsa\Nucleo\Aplicacion();
try {
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $products = $db->query('SELECT p.id, p.nombre_comercial, fe.nombre AS formato, fe.archivo_markdown 
                            FROM productos p
                            LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                            LIMIT 30')->fetchAll(PDO::FETCH_ASSOC);
    foreach ($products as $p) {
        printf("ID: %d | Nombre: %s | Formato: %s (%s)\n", $p['id'], $p['nombre_comercial'], $p['formato'], $p['archivo_markdown']);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
