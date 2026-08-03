<?php
$pdo = new PDO('mysql:host=localhost;dbname=cycsa_db;charset=utf8mb4', 'root', '');
$tables = $pdo->query('SHOW TABLES')->fetchAll(PDO::FETCH_COLUMN);

$sqlDump = "-- SCHEMA EXPORT FOR DRAWSQL (CYCSA ERP & LIMS)\n\n";

foreach ($tables as $t) {
    $create = $pdo->query("SHOW CREATE TABLE `$t`")->fetch(PDO::FETCH_ASSOC);
    $sqlDump .= $create['Create Table'] . ";\n\n";
}

file_put_contents('storage/docs/schema_cycsa_drawsql.sql', $sqlDump);
echo "SCHEMA EXPORTADO EXITOSAMENTE A storage/docs/schema_cycsa_drawsql.sql (" . strlen($sqlDump) . " bytes)\n";
