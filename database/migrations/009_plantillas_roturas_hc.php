<?php
/**
 * Migración: 009_plantillas_roturas_hc.php
 * Fase 2: Sembrado de Hojas de Cálculo del resto del módulo de Roturas de Laboratorio:
 * - RT-FM-36: Bloques de mampostería de concreto (ASTM C140 / C90; NTON 12 008-16)
 * - RT-FM-38: Módulo de ruptura de viguetas de concreto a flexión (ASTM C78)
 * - RT-FM-57: Resistencia a compresión de cubos de mortero (ASTM C109)
 * - RT-FM-63: Adoquines de concreto: resistencia a compresión y absorción (NTON 12 008-16 / ASTM C140)
 * - RT-FM-64: Ensayo de tensión de varillas de acero de refuerzo (ASTM A370 / E8 / A615)
 *
 * Ejecución: php database/migrations/009_plantillas_roturas_hc.php
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Operaciones\Servicios\SembradorPlantillasHcServicio;

$db = Conexion::obtenerInstancia();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "--- Iniciando Migración 009: Plantillas de Roturas HC (36, 38, 57, 63, 64) ---\n";

$sembrador = new SembradorPlantillasHcServicio($db);
$formatosFase2 = ['RT-FM-36', 'RT-FM-38', 'RT-FM-57', 'RT-FM-63', 'RT-FM-64'];

$resultados = $sembrador->sembrarPlantillasFormatos($formatosFase2);
foreach ($resultados as $codigo => $id) {
    echo "[OK] Plantilla {$codigo} sembrada/actualizada con ID #{$id}.\n";
}

echo "--- Migración 009 finalizada con éxito ---\n";
