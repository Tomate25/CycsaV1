<?php
$views = [
    'C:/xampp/htdocs/Cycsa/modulos/cotizaciones/vistas/index.php' => 'Cotizaciones',
    'C:/xampp/htdocs/Cycsa/modulos/clientes/vistas/index.php' => 'Clientes',
    'C:/xampp/htdocs/Cycsa/modulos/productos/vistas/index.php' => 'Catálogo de Ensayos',
    'C:/xampp/htdocs/Cycsa/modulos/contabilidad/vistas/index.php' => 'Contabilidad',
    'C:/xampp/htdocs/Cycsa/modulos/usuarios/vistas/index.php' => 'Usuarios',
    'C:/xampp/htdocs/Cycsa/modulos/configuracion/vistas/index.php' => 'Configuración'
];

foreach ($views as $filePath => $nombreModulo) {
    if (!file_exists($filePath)) {
        echo "File not found: $filePath\n";
        continue;
    }
    $content = file_get_contents($filePath);
    if (strpos($content, 'bitacora_modulo.php') !== false) {
        echo "Already exists in: $filePath\n";
        continue;
    }
    
    // Append the inclusion code at the very end
    $append = "\n\n<?php\n\$bitacora_modulo_nombre = '" . $nombreModulo . "';\ninclude __DIR__ . '/../../../plantillas/parciales/bitacora_modulo.php';\n?>\n";
    file_put_contents($filePath, $content . $append);
    echo "Successfully appended to: $filePath\n";
    
    // Check syntax
    exec("php -l \"" . $filePath . "\"", $output, $returnVar);
    if ($returnVar !== 0) {
        echo "SYNTAX ERROR in $filePath!\n";
    } else {
        echo "Syntax OK: $filePath\n";
    }
}
