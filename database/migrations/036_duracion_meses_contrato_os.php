<?php

/**
 * Migración 036: Duración en meses y vigencia de contratos largos en Órdenes de Servicio.
 *
 * Agrega de forma idempotente la columna duracion_meses en ordenes_servicio
 * (junto a es_multimes, fecha_inicio, fecha_fin y periodicidad) y completa
 * la duración en meses calculada para contratos multi-mes existentes.
 */

require_once dirname(__DIR__, 2) . '/app/Core/Conexion.php';

if (!function_exists('ejecutarMigracion036')) {
    function ejecutarMigracion036(PDO $db): void {
        $cols = $db->query("SHOW COLUMNS FROM ordenes_servicio")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('es_multimes', $cols, true)) {
            $db->exec("ALTER TABLE ordenes_servicio ADD COLUMN es_multimes TINYINT(1) NOT NULL DEFAULT 0 AFTER tipo_contrato");
        }
        if (!in_array('duracion_meses', $cols, true)) {
            $db->exec("ALTER TABLE ordenes_servicio ADD COLUMN duracion_meses INT NULL DEFAULT NULL AFTER es_multimes");
        }
        if (!in_array('fecha_inicio', $cols, true)) {
            $db->exec("ALTER TABLE ordenes_servicio ADD COLUMN fecha_inicio DATE NULL DEFAULT NULL AFTER duracion_meses");
        }
        if (!in_array('fecha_fin', $cols, true)) {
            $db->exec("ALTER TABLE ordenes_servicio ADD COLUMN fecha_fin DATE NULL DEFAULT NULL AFTER fecha_inicio");
        }
        if (!in_array('periodicidad', $cols, true)) {
            $db->exec("ALTER TABLE ordenes_servicio ADD COLUMN periodicidad VARCHAR(50) NULL DEFAULT 'Mensual' AFTER fecha_fin");
        }

        // Backfill de duracion_meses para contratos multi-mes que ya tenían fecha_inicio y fecha_fin
        $db->exec("
            UPDATE ordenes_servicio
            SET duracion_meses = GREATEST(1, TIMESTAMPDIFF(MONTH, fecha_inicio, fecha_fin))
            WHERE es_multimes = 1
              AND duracion_meses IS NULL
              AND fecha_inicio IS NOT NULL
              AND fecha_fin IS NOT NULL
              AND fecha_fin >= fecha_inicio
        ");
    }
}

if (PHP_SAPI === 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    try {
        $pdo = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        ejecutarMigracion036($pdo);
        echo "[OK] Migración 036 ejecutada exitosamente.\n";
    } catch (Throwable $e) {
        echo "[ERROR] Migración 036 falló: " . $e->getMessage() . "\n";
        exit(1);
    }
}
