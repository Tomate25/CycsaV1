<?php
require_once 'C:/xampp/htdocs/Cycsa/vendor/autoload.php';
use Cycsa\Nucleo\Aplicacion;
use Cycsa\Nucleo\Conexion;

try {
    $app = new Aplicacion();
    $db = Conexion::obtenerInstancia();
    
    $stmt = $db->query("DESCRIBE cuentas_contables");
    $columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
    echo "COLUMNS OF cuentas_contables:\n";
    foreach ($columns as $col) {
        echo "- {$col['Field']}: {$col['Type']} (Null: {$col['Null']}, Key: {$col['Key']})\n";
    }
    
    echo "\nFIRST 10 ACCOUNTS:\n";
    $stmtAcc = $db->query("SELECT codigo, nombre FROM cuentas_contables LIMIT 15");
    $accs = $stmtAcc->fetchAll(PDO::FETCH_ASSOC);
    foreach ($accs as $acc) {
        echo "  {$acc['codigo']} - {$acc['nombre']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
