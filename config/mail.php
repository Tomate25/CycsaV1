<?php

return [
    'driver' => $_ENV['MAIL_DRIVER'] ?? 'smtp',
    'host' => $_ENV['MAIL_HOST'] ?? 'smtp.gmail.com',
    'port' => $_ENV['MAIL_PORT'] ?? 587,
    'username' => $_ENV['MAIL_USER'] ?? '',
    'password' => $_ENV['MAIL_PASS'] ?? '',
    'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? 'tls',
    'from' => [
        'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? 'notificaciones@cycsa.com.ni',
        'name' => $_ENV['MAIL_FROM_NAME'] ?? 'CYCSA ERP & LIMS'
    ]
];
