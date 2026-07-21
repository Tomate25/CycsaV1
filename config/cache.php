<?php

namespace Cycsa\Config;

/**
 * Configuración de caché.
 */
return [
    'driver' => 'file', // Opciones: 'file', 'redis', 'memcached'
    'ttl' => 3600, // Tiempo de vida en segundos
    'path' => __DIR__ . '/../storage/cache',
];
