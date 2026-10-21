<?php
/**
 * Migración: 010_hojas_campo_compactacion_hc.php
 * Fase 3: Sembrado de Hojas de Campo (RT-FM-54, RT-FM-55, RT-FM-62) y Enlace Proctor (RT-FM-27)
 *
 * Ejecución: php database/migrations/010_hojas_campo_compactacion_hc.php
 */
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Operaciones\Servicios\SembradorPlantillasHcServicio;

$db = Conexion::obtenerInstancia();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "--- Iniciando Migración 010: Hoja de Campo (RT-FM-54, 55, 62) + Enlace Proctor (RT-FM-27) ---\n";

$sembrador = new SembradorPlantillasHcServicio($db);
$codigos = ['RT-FM-27', 'RT-FM-54', 'RT-FM-55', 'RT-FM-62'];
$sembradas = $sembrador->sembrarPlantillasFormatos($codigos);

foreach ($sembradas as $cod => $idPlantilla) {
    echo "[OK] Plantilla HC sembrada/actualizada: {$cod} -> ID #{$idPlantilla}\n";
}

echo "--- Migración 010 finalizada con éxito ---\n";
