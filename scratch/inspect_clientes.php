<?php
require_once 'C:/xampp/htdocs/Cycsa/vendor/autoload.php';
use Cycsa\Nucleo\Aplicacion;
use Cycsa\Nucleo\Conexion;

try {
    $app = new Aplicacion();
    $db = Conexion::obtenerInstancia();
    $stmt = $db->query("DESCRIBE clientes");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "COLUMNS OF clientes:\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']}: {$col['Type']} (Null: {$col['Null']}, Key: {$col['Key']}, Default: {$col['Default']})\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
