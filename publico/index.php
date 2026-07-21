<?php
// publico/index.php

// Modo producción: no mostrar errores al usuario
// En local/testing, activar temporalmente cambiando a 1
ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ALL);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../storage/logs/php_errors.log');

// 🌐 Reescribir dinámicamente las rutas hardcodeadas en las vistas
ob_start(function($buffer) {
    $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
    $basePath = dirname($scriptName);
    $basePath = str_replace('\\', '/', $basePath);
    $basePath = rtrim($basePath, '/');
    if ($basePath === '/') {
        $basePath = '';
    }
    $assetBase = (strpos($basePath, '/publico') !== false) ? $basePath : $basePath . '/publico';
    return str_replace('/Cycsa/publico', $assetBase, $buffer);
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
    error_log("FATAL: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine() . "\n" . $e->getTraceAsString());
    
    $mostrarDetalle = (isset($_GET['debug']) && $_GET['debug'] === '1') || (($_ENV['APP_ENV'] ?? 'produccion') === 'local');
    
    if ($mostrarDetalle) {
        echo "<div style='padding:40px; font-family:sans-serif; background:#fff; color:#991b1b; max-width:900px; margin:20px auto; border:2px solid #fca5a5; border-radius:8px;'>";
        echo "<h2>🔍 Detalle del Error en el Servidor:</h2>";
        echo "<p style='font-size:16px;'><strong>" . htmlspecialchars($e->getMessage()) . "</strong></p>";
        echo "<p><strong>Archivo:</strong> " . htmlspecialchars($e->getFile()) . " (Línea " . $e->getLine() . ")</p>";
        echo "<pre style='background:#f8fafc; padding:15px; border-radius:6px; border:1px solid #cbd5e1; overflow-x:auto; font-size:13px; color:#1e293b;'>" . htmlspecialchars($e->getTraceAsString()) . "</pre>";
        echo "</div>";
        exit;
    } else {
        http_response_code(500);
        $headerPath = __DIR__ . '/../app/Views/header.php';
        if (!file_exists($headerPath)) $headerPath = __DIR__ . '/../plantillas/header.php';
        $footerPath = __DIR__ . '/../app/Views/footer.php';
        if (!file_exists($footerPath)) $footerPath = __DIR__ . '/../plantillas/footer.php';

        if (file_exists($headerPath)) require_once $headerPath;
        echo '<div style="text-align:center;padding:60px 20px"><h2>Error interno</h2><p>Ha ocurrido un error inesperado. Intente de nuevo mas tarde.</p><p><a href="/">Volver al inicio</a></p></div>';
        if (file_exists($footerPath)) require_once $footerPath;
    }
}