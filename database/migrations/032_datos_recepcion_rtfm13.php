<?php

/**
 * Migración 032: Columna datos_recepcion_json por muestra en hojas_solicitud_detalles.
 *
 * Permite almacenar en Órdenes de Servicio los datos de recepción específicos
 * de cada Hoja RT-FM-13 y de cada una de sus N muestras (Revenimiento, Temperatura,
 * Diseño, Fecha de Fabricación, Fecha de Ensayo, Edad) para sincronizarlos
 * automáticamente hacia la Matriz Técnica en Operaciones y Laboratorio.
 *
 * 100% aditiva e idempotente.
 */

require_once dirname(__DIR__, 2) . '/app/Core/Conexion.php';

if (!function_exists('ejecutarMigracion032')) {
    function ejecutarMigracion032(PDO $db): void {
        $cols = $db->query("SHOW COLUMNS FROM hojas_solicitud_detalles")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('datos_recepcion_json', $cols, true)) {
            $db->exec("ALTER TABLE hojas_solicitud_detalles ADD COLUMN datos_recepcion_json LONGTEXT NULL AFTER observacion_edad");
        }
    }
}

if (PHP_SAPI === 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    try {
        $pdo = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        ejecutarMigracion032($pdo);
        echo "[OK] Migración 032 ejecutada exitosamente.\n";
    } catch (Throwable $e) {
        echo "[ERROR] Migración 032 falló: " . $e->getMessage() . "\n";
        exit(1);
    }
}
