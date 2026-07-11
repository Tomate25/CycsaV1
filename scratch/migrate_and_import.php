<?php
require_once 'C:/xampp/htdocs/Cycsa/vendor/autoload.php';
use Cycsa\Nucleo\Aplicacion;
use Cycsa\Nucleo\Conexion;

try {
    $app = new Aplicacion();
    $db = Conexion::obtenerInstancia();
    
    // 1. Obtener columnas actuales de la tabla clientes
    $stmt = $db->query("DESCRIBE clientes");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    
    // 2. Definir las nuevas columnas a agregar
    $nuevasColumnas = [
        'tipo_cliente' => "VARCHAR(50) NULL AFTER id",
        'codigo_cliente' => "VARCHAR(50) NULL AFTER tipo_cliente",
        'nombre_cliente' => "VARCHAR(150) NULL AFTER activo",
        'primer_apellido' => "VARCHAR(100) NULL AFTER nombre_cliente",
        'segundo_apellido' => "VARCHAR(100) NULL AFTER primer_apellido",
        'sucursal_sede' => "VARCHAR(100) NULL AFTER segundo_apellido",
        'clasificacion' => "VARCHAR(100) NULL AFTER sucursal_sede",
        'sub_clasificacion' => "VARCHAR(100) NULL AFTER clasificacion",
        'vendedor' => "VARCHAR(100) NULL AFTER sub_clasificacion",
        'numero_cedula' => "VARCHAR(50) NULL AFTER vendedor",
        'numero_ruc' => "VARCHAR(50) NULL AFTER numero_cedula",
        'contacto' => "VARCHAR(150) NULL AFTER numero_ruc",
        'notas' => "TEXT NULL AFTER contacto",
        'fax' => "VARCHAR(50) NULL AFTER notas",
        'cuenta_cxc' => "VARCHAR(100) NULL AFTER fax",
        'cuenta_cxp' => "VARCHAR(100) NULL AFTER cuenta_cxc",
        'exonerado_impuestos' => "TINYINT(1) DEFAULT 0 AFTER cuenta_cxp",
        'cuenta_ingresos_exonerados' => "VARCHAR(100) NULL AFTER exonerado_impuestos",
        'exportacion' => "TINYINT(1) DEFAULT 0 AFTER cuenta_ingresos_exonerados",
        'tipo_moneda' => "TINYINT(1) DEFAULT 1 AFTER exportacion",
        'activar_prorroga_credito' => "TINYINT(1) DEFAULT 0 AFTER tipo_moneda",
        'limite_credito' => "DECIMAL(15, 2) DEFAULT 0.00 AFTER activar_prorroga_credito",
        'dias_credito' => "INT DEFAULT 0 AFTER limite_credito",
        'facturas_vencidas_permitidas' => "INT DEFAULT 0 AFTER dias_credito",
        'descuento_automatico' => "TINYINT(1) DEFAULT 0 AFTER facturas_vencidas_permitidas",
        'porcentaje_descuento' => "DECIMAL(5, 2) DEFAULT 0.00 AFTER descuento_automatico",
        'predeterminado_pos' => "TINYINT(1) DEFAULT 0 AFTER porcentaje_descuento",
        'facturacion_correo' => "TINYINT(1) DEFAULT 0 AFTER predeterminado_pos",
        'contacto_nombre' => "VARCHAR(100) NULL AFTER facturacion_correo",
        'contacto_apellido' => "VARCHAR(100) NULL AFTER contacto_nombre",
        'contacto_cargo' => "VARCHAR(100) NULL AFTER contacto_apellido",
        'contacto_correo' => "VARCHAR(100) NULL AFTER contacto_cargo"
    ];
    
    // 3. Ejecutar ALTER TABLE para las columnas que falten
    foreach ($nuevasColumnas as $colName => $colDef) {
        if (!in_array($colName, $columns)) {
            $sql = "ALTER TABLE clientes ADD COLUMN `{$colName}` {$colDef}";
            $db->exec($sql);
            echo "Added column: {$colName}\n";
        } else {
            echo "Column already exists: {$colName}\n";
        }
    }
    
    // 4. Cargar archivo JSON
    $jsonPath = 'C:/xampp/htdocs/Cycsa/scratch/clientes_import.json';
    if (!file_exists($jsonPath)) {
        throw new Exception("No se encontró el archivo JSON en: " . $jsonPath);
    }
    
    $jsonData = json_decode(file_get_contents($jsonPath), true);
    if ($jsonData === null) {
        throw new Exception("Error al decodificar JSON.");
    }
    
    // 5. Desactivar FK checks y limpiar tabla
    echo "Disabling foreign keys and truncating clients...\n";
    $db->exec("SET FOREIGN_KEY_CHECKS = 0");
    $db->exec("TRUNCATE TABLE clientes");
    
    // 6. Preparar sentencia de inserción con todos los campos nuevos y viejos
    $fields = [
        'id', 'tipo_cliente', 'codigo_cliente', 'activo', 'nombre_razon_social',
        'nombre_cliente', 'primer_apellido', 'segundo_apellido', 'sucursal_sede',
        'clasificacion', 'sub_clasificacion', 'vendedor', 'numero_cedula',
        'numero_ruc', 'identificacion', 'contacto', 'direccion', 'notas',
        'telefono', 'fax', 'email', 'cuenta_cxc', 'cuenta_cxp',
        'exonerado_impuestos', 'cuenta_ingresos_exonerados', 'exportacion',
        'tipo_moneda', 'activar_prorroga_credito', 'limite_credito',
        'dias_credito', 'facturas_vencidas_permitidas', 'descuento_automatico',
        'porcentaje_descuento', 'predeterminado_pos', 'facturacion_correo',
        'contacto_nombre', 'contacto_apellido', 'contacto_cargo', 'contacto_correo'
    ];
    
    $placeholders = implode(', ', array_map(fn($f) => ":{$f}", $fields));
    $insertSql = "INSERT INTO clientes (" . implode(', ', array_map(fn($f) => "`{$f}`", $fields)) . ") VALUES ({$placeholders})";
    $stmtInsert = $db->prepare($insertSql);
    
    echo "Importing clients...\n";
    $count = 0;
    foreach ($jsonData as $client) {
        // Build execute array
        $execData = [];
        foreach ($fields as $field) {
            $execData[$field] = $client[$field] ?? null;
        }
        $stmtInsert->execute($execData);
        $count++;
    }
    
    // 7. Volver a activar FK checks
    $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    echo "Imported {$count} clients successfully.\n";
    
} catch (Exception $e) {
    // Asegurar que reactivamos las FK checks en caso de error
    if (isset($db)) {
        $db->exec("SET FOREIGN_KEY_CHECKS = 1");
    }
    echo "ERROR: " . $e->getMessage() . "\n";
}
