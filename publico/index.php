<?php
// publico/index.php

// 🌐 Reescribir dinámicamente las rutas hardcodeadas en las vistas para compatibilidad con cualquier carpeta
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

// 2. Instanciar la Aplicación principal
$app = new Aplicacion();

// 3. Cargar las rutas definidas en el sistema
require_once __DIR__ . '/../rutas/web.php';

// 4. Arrancar la aplicación
$app->correr();