<?php

namespace Cycsa\Config;

/**
 * Configuración general de la aplicación.
 */
return [
    'name' => 'CYCSA ERP & LIMS',
    'env' => $_ENV['APP_ENV'] ?? 'produccion',
    'timezone' => 'America/Managua',
    'debug' => false,
    'url' => $_ENV['APP_URL'] ?? 'https://app.cycsanic.com',
];
