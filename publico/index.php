<?php
// publico/index.php

// Modo producción: no mostrar errores al usuario
// En local/testing, activar temporalmente cambiando a 1
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../almacenamiento/logs/php_errors.log');

// 🌐 Reescribir dinámicamente las rutas hardcodeadas en las vistas
ob_start(function($buffer) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = dirname($scriptName);
    $basePath = str_replace('\\', '/', $basePath);
    $basePath = rtrim($basePath, '/');
    if ($basePath === '/') {
        $basePath = '';
    }
    return str_replace('/Cycsa/publico', $basePath, $buffer);
});

// 1. Cargar el Autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

use Cycsa\Nucleo\Aplicacion;

try {
    // 2. Instanciar la Aplicación principal
    $app = new Aplicacion();

    // 3. Cargar las rutas definidas en el sistema
    require_once __DIR__ . '/../rutas/web.php';

    // 4. Arrancar la aplicación
    $app->correr();
} catch (\Throwable $e) {
    error_log("FATAL: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
    http_response_code(500);
    $esLocal = ($_ENV['APP_ENV'] ?? 'produccion') === 'local';
    if ($esLocal) {
        echo "<h2>Error detectado:</h2>";
        echo "<p><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
        echo "<p>Archivo: " . htmlspecialchars($e->getFile()) . " linea " . $e->getLine() . "</p>";
        echo "<pre>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
    } else {
        require_once __DIR__ . '/../plantillas/header.php';
        echo '<div style="text-align:center;padding:60px 20px"><h2>Error interno</h2><p>Ha ocurrido un error inesperado. Intente de nuevo mas tarde.</p><p><a href="/">Volver al inicio</a></p></div>';
        require_once __DIR__ . '/../plantillas/footer.php';
    }
}