<?php

// Cargar archivo .env de la raíz si existe
$envFile = dirname(__DIR__) . '/.env';
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

return [
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1',
    'port' => $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306,
    'database' => $_ENV['DB_NAME'] ?? $_ENV['DB_DATABASE'] ?? getenv('DB_NAME') ?: 'cycsa_db',
    'username' => $_ENV['DB_USER'] ?? $_ENV['DB_USERNAME'] ?? getenv('DB_USER') ?: 'cycsa_user',
    'password' => $_ENV['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? getenv('DB_PASS') ?: 'CYCSA_Password_2026!',
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
