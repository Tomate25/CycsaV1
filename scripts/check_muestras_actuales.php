<?php
$db = new PDO("mysql:host=localhost;dbname=cycsa_db;charset=utf8mb4", "root", "");

echo "--- COLUMNAS RECEPCION_MUESTRAS ---\n";
$st = $db->query("DESCRIBE recepcion_muestras");
print_r($st->fetchAll(PDO::FETCH_ASSOC));

echo "--- FILAS RECEPCION_MUESTRAS ---\n";
$st = $db->query("SELECT * FROM recepcion_muestras");
print_r($st->fetchAll(PDO::FETCH_ASSOC));

echo "--- COLUMNAS HOJAS_SOLICITUD ---\n";
$st = $db->query("DESCRIBE hojas_solicitud");
print_r($st->fetchAll(PDO::FETCH_ASSOC));

echo "--- FILAS HOJAS_SOLICITUD ---\n";
$st = $db->query("SELECT * FROM hojas_solicitud");
print_r($st->fetchAll(PDO::FETCH_ASSOC));

echo "--- FILAS ESPECIMENES ---\n";
$st = $db->query("SELECT * FROM especimenes");
print_r($st->fetchAll(PDO::FETCH_ASSOC));
