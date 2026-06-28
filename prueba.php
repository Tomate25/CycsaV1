<?php
// prueba.php - Script para diagnosticar el Error 500 en Bluehost
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

echo "<h2>🔍 Diagnóstico de Error en Bluehost</h2>";
echo "<strong>Versión de PHP del Servidor:</strong> " . phpversion() . "<br>";

if (version_compare(phpversion(), '8.0.0', '<')) {
    echo "<span style='color:red;'>⚠️ Error: Tu servidor está usando una versión de PHP inferior a 8.0. Debes cambiarla en cPanel a PHP 8.1 o superior.</span><br>";
} else {
    echo "<span style='color:green;'>✔️ Versión de PHP compatible.</span><br>";
}

// Verificar existencia de archivos clave
$rutas = [
    'vendor/autoload.php' => __DIR__ . '/vendor/autoload.php',
    '.env' => __DIR__ . '/.env',
    'nucleo/Aplicacion.php' => __DIR__ . '/nucleo/Aplicacion.php',
    'configuracion/database.php' => __DIR__ . '/configuracion/database.php'
];

echo "<h3>Archivos en el Servidor:</h3>";
foreach ($rutas as $nombre => $path) {
    if (file_exists($path)) {
        echo "<span style='color:green;'>✔️ Encontrado: $nombre</span><br>";
    } else {
        echo "<span style='color:red;'>❌ No encontrado: $nombre</span><br>";
    }
}

echo "<h3>Probando carga de dependencias:</h3>";
try {
    if (file_exists(__DIR__ . '/vendor/autoload.php')) {
        require_once __DIR__ . '/vendor/autoload.php';
        echo "<span style='color:green;'>✔️ Autoload de Composer cargado correctamente.</span><br>";
    } else {
        throw new Exception("No se puede cargar el autoload porque no existe.");
    }
    
    // Probar inicialización del núcleo
    if (class_exists('Cycsa\Nucleo\Aplicacion')) {
        echo "<span style='color:green;'>✔️ Clase Aplicacion detectada.</span><br>";
        
        // Crear instancia
        $app = new \Cycsa\Nucleo\Aplicacion();
        echo "<span style='color:green;'>✔️ Instancia de Aplicación creada con éxito.</span><br>";
    } else {
        echo "<span style='color:red;'>❌ Error: No se puede resolver el namespace de Cycsa.</span><br>";
    }
} catch (Throwable $e) {
    echo "<div style='color:red; margin-top:20px; padding:10px; border:1px solid red; background:#fff5f5;'>";
    echo "<strong>Detalle del Error:</strong><br>";
    echo "Archivo: " . $e->getFile() . "<br>";
    echo "Línea: " . $e->getLine() . "<br>";
    echo "Mensaje: " . $e->getMessage() . "<br>";
    echo "Trace:<br><pre>" . $e->getTraceAsString() . "</pre>";
    echo "</div>";
}
