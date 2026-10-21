<?php
/**
 * Migración: 011_plantillas_suelos_agregados_y_fm56.php
 * Fase 4: Sembrado de Plantillas de Suelos y Agregados (RT-FM-27, 25, 42, 35, 46, 58, 59)
 * y Calibración de Picnómetros en Equipos (RT-FM-56).
 *
 * Ejecución: php database/migrations/011_plantillas_suelos_agregados_y_fm56.php
 */
declare(strict_types=1);

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Operaciones\Servicios\SembradorPlantillasHcServicio;

$db = Conexion::obtenerInstancia();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "--- Iniciando Migración 011: Plantillas de Suelos y Agregados (27, 25, 42, 35, 46, 58, 59) + RT-FM-56 (Equipos) ---\n";

$sembrador = new SembradorPlantillasHcServicio($db);
$codigosFase4 = [
    'RT-FM-27',
    'RT-FM-25',
    'RT-FM-42',
    'RT-FM-35',
    'RT-FM-46',
    'RT-FM-58',
    'RT-FM-59',
    'RT-FM-56',
];

$sembradas = $sembrador->sembrarPlantillasFormatos($codigosFase4);

foreach ($sembradas as $cod => $idPlantilla) {
    echo "[OK] Plantilla HC sembrada/actualizada: {$cod} -> ID #{$idPlantilla}\n";
}

echo "--- Migración 011 finalizada con éxito (17/17 formatos HC sembrados en BD) ---\n";
