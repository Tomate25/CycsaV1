<?php

$isTestingEnv = (getenv('APP_ENV') === 'testing' || ($_ENV['APP_ENV'] ?? '') === 'testing');

$envFiles = [
    dirname(__DIR__) . '/.env',
    dirname(__DIR__) . '/.env.local',
];

if ($isTestingEnv) {
    $envFiles[] = dirname(__DIR__) . '/.env.testing';
}

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

$defaultDb = $isTestingEnv ? 'cycsa_test' : 'cycsa_db';
$dbName = $_ENV['DB_NAME'] ?? $_ENV['DB_DATABASE'] ?? getenv('DB_NAME') ?: $defaultDb;
if ($isTestingEnv && empty($_ENV['DB_NAME']) && empty(getenv('DB_NAME'))) {
    $dbName = 'cycsa_test';
}

return [
    'driver' => 'mysql',
    'host' => $_ENV['DB_HOST'] ?? getenv('DB_HOST') ?: '127.0.0.1',
    'port' => $_ENV['DB_PORT'] ?? getenv('DB_PORT') ?: 3306,
    'database' => $dbName,
    'username' => $_ENV['DB_USER'] ?? $_ENV['DB_USERNAME'] ?? getenv('DB_USER') ?: 'root',
    'password' => $_ENV['DB_PASS'] ?? $_ENV['DB_PASSWORD'] ?? (getenv('DB_PASS') !== false ? getenv('DB_PASS') : ''),
    'charset' => 'utf8mb4',
    'collation' => 'utf8mb4_unicode_ci',
    'options' => [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES => false,
    ]
];
