<?php

$envFiles = [
    dirname(__DIR__) . '/.env',
    dirname(__DIR__) . '/.env.local',
];

foreach ($envFiles as $envFile) {
    if (file_exists($envFile)) {
        $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
        foreach ($lines as $line) {
            $line = trim($line);
            if ($line === '' || $line[0] === '#') continue;
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value, " \t\n\r\0\x0B\"'");
                $_ENV[$key] = $value;
                putenv("{$key}={$value}");
            }
        }
    }
}

return [
    'driver' => $_ENV['MAIL_DRIVER'] ?? 'smtp',
    'host' => $_ENV['MAIL_HOST'] ?? 'mail.cycsanic.com',
    'port' => (int)($_ENV['MAIL_PORT'] ?? 465),
    'username' => $_ENV['MAIL_USER'] ?? 'asistente@cycsanic.com',
    'password' => $_ENV['MAIL_PASS'] ?? 'Cycsa73465@',
    'encryption' => $_ENV['MAIL_ENCRYPTION'] ?? $_ENV['MAIL_SECURE'] ?? 'ssl',
    'from' => [
        'address' => $_ENV['MAIL_FROM_ADDRESS'] ?? $_ENV['MAIL_FROM'] ?? 'asistente@cycsanic.com',
        'name' => $_ENV['MAIL_FROM_NAME'] ?? $_ENV['APP_NAME'] ?? 'CYCSA ERP'
    ]
];
