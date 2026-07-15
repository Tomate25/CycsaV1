<?php
require_once __DIR__ . '/../vendor/autoload.php';

use Cycsa\Nucleo\Aplicacion;
use Cycsa\Nucleo\Conexion;

$app = new Aplicacion();
try {
    $db = Conexion::obtenerInstancia();
    echo "=== FORMATOS DE ENSAYOS ===\n";
    $formats = $db->query("SELECT * FROM formatos_ensayos")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($formats as $f) {
        printf("ID: %d | Nombre: %s | Markdown: %s\n", $f['id'], $f['nombre'], $f['archivo_markdown']);
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
