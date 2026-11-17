<?php

/**
 * Migración 035: Asociación de documentos CYCSA-RT-FM-40 B (programacion_muestreo) con su Hoja RT-FM-13.
 *
 * Agrega de forma idempotente la columna id_hoja_solicitud en programacion_muestreo
 * para permitir generar, editar y cerrar el documento 40 B directamente desde Órdenes de Servicio
 * vinculado a su hoja RT-FM-13 correspondiente.
 */

require_once dirname(__DIR__, 2) . '/app/Core/Conexion.php';

if (!function_exists('ejecutarMigracion035')) {
    function ejecutarMigracion035(PDO $db): void {
        $cols = $db->query("SHOW COLUMNS FROM programacion_muestreo")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('id_hoja_solicitud', $cols, true)) {
            $db->exec("ALTER TABLE programacion_muestreo ADD COLUMN id_hoja_solicitud INT NULL DEFAULT NULL AFTER id_orden_servicio");
        }
        $idx = $db->query("SHOW INDEX FROM programacion_muestreo WHERE Key_name = 'idx_pm_hoja_solicitud'")->fetchAll();
        if (empty($idx)) {
            $db->exec("ALTER TABLE programacion_muestreo ADD INDEX idx_pm_hoja_solicitud (id_hoja_solicitud)");
        }
    }
}

if (PHP_SAPI === 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    try {
        $pdo = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        ejecutarMigracion035($pdo);
        echo "[OK] Migración 035 ejecutada exitosamente.\n";
    } catch (Throwable $e) {
        echo "[ERROR] Migración 035 falló: " . $e->getMessage() . "\n";
        exit(1);
    }
}
