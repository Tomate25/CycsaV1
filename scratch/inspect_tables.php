<?php

require_once __DIR__ . '/../vendor/autoload.php';

try {
    $config = require __DIR__ . '/../config/database.php';
    $pdo = new PDO("mysql:host={$config['host']};dbname=cycsa_db;charset=utf8mb4", $config['username'], $config['password']);

    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    echo "Resumen de tablas en cycsa_db local:\n";
    foreach ($tables as $table) {
        $count = $pdo->query("SELECT COUNT(*) FROM `{$table}`")->fetchColumn();
        echo str_pad($table, 30) . " : " . $count . " filas\n";
    }
} catch (\Throwable $e) {
    echo "ERROR: " . $e->getMessage() . "\n";
}
