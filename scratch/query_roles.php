<?php
require_once __DIR__ . '/../vendor/autoload.php';
new Cycsa\Nucleo\Aplicacion();
$db = Cycsa\Nucleo\Conexion::obtenerInstancia();

echo "=== ROLES ===\n";
$roles = $db->query("SELECT * FROM roles")->fetchAll(PDO::FETCH_ASSOC);
print_r($roles);

echo "\n=== USUARIOS ===\n";
$usuarios = $db->query("SELECT id, nombre, email, id_rol FROM usuarios")->fetchAll(PDO::FETCH_ASSOC);
print_r($usuarios);
