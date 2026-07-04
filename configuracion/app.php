<?php
return [
    'nombre' => $_ENV['APP_NAME'] ?? 'CYCSA',
    'entorno' => $_ENV['APP_ENV'] ?? 'produccion',
    'url' => $_ENV['APP_URL'] ?? 'http://localhost/Cycsa/publico',
    'zona_horaria' => 'America/Managua',
    'debug' => ($_ENV['APP_ENV'] ?? '') === 'local',
    'version' => '1.0.0',
    'sesion' => [
        'tiempo_vida' => 7200,        // 2 horas
        'unica_por_usuario' => true,
        'cookie_httponly' => true,
        'cookie_secure' => ($_ENV['APP_ENV'] ?? '') !== 'local',
        'cookie_samesite' => 'Strict',
    ],
    'correo' => [
        'remitente' => $_ENV['MAIL_FROM'] ?? 'no-reply@cycsanic.com',
        'usar_smtp' => !empty($_ENV['MAIL_HOST']),
    ],
    'pdf' => [
        'papel' => 'letter',
        'orientacion_defecto' => 'portrait',
        'margenes' => [10, 10, 10, 10],
    ],
    'paginacion' => [
        'por_pagina' => 15,
    ],
    'seguridad' => [
        'bcrypt_costo' => 12,
        'csrf_expiracion' => 7200,
        'token_cliente_expiracion' => 86400 * 7, // 7 días
    ],
    'contabilidad' => [
        'moneda' => 'NIO',
        'simbolo_moneda' => 'C$',
        'decimales' => 2,
    ],
];