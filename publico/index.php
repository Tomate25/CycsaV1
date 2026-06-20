<?php
// publico/index.php

// 1. Cargar el Autoloader de Composer
require_once __DIR__ . '/../vendor/autoload.php';

use Cycsa\Nucleo\Aplicacion;

// 2. Instanciar la Aplicación principal
$app = new Aplicacion();

// 3. Cargar las rutas definidas en el sistema
require_once __DIR__ . '/../rutas/web.php';

// 4. Arrancar la aplicación
$app->correr();