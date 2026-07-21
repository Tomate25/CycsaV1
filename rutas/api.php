<?php

use Cycsa\App\Controllers\Api\ClienteApiController;

/**
 * Registrador de rutas de la API REST (/api/v1/...)
 */
return [
    'GET' => [
        '/api/v1/clientes' => [ClienteApiController::class, 'listar'],
        '/api/v1/cotizaciones' => [ClienteApiController::class, 'listarCotizaciones'],
    ],
    'POST' => [
        '/api/v1/clientes' => [ClienteApiController::class, 'crear'],
    ]
];
