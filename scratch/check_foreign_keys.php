<?php
require_once 'C:/xampp/htdocs/Cycsa/vendor/autoload.php';
use Cycsa\Nucleo\Aplicacion;
use Cycsa\Nucleo\Conexion;

try {
    $app = new Aplicacion();
    $db = Conexion::obtenerInstancia();
    
    // Check foreign keys referencing clientes
    $sql = "SELECT TABLE_NAME, COLUMN_NAME, CONSTRAINT_NAME, REFERENCED_TABLE_NAME, REFERENCED_COLUMN_NAME 
            FROM INFORMATION_SCHEMA.KEY_COLUMN_USAGE 
            WHERE REFERENCED_TABLE_NAME = 'clientes'";
    $stmt = $db->query($sql);
    $fks = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo "FOREIGN KEYS REFERENCING clientes:\n";
    foreach ($fks as $fk) {
        echo "- Table: {$fk['TABLE_NAME']}, Col: {$fk['COLUMN_NAME']}, Constraint: {$fk['CONSTRAINT_NAME']}\n";
    }
} catch (Exception $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
