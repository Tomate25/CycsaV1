<?php

/**
 * Migración 028: Soporte para enlace de versión específica de cotización en Órdenes de Servicio (Sprint D.2).
 * Permite registrar la versión exacta (v1, v2, etc.) con la que se formalizó la O/S.
 */

require_once dirname(__DIR__, 2) . '/app/Core/Conexion.php';

if (!function_exists('ejecutarMigracion028')) {
    function ejecutarMigracion028(PDO $db): void {
        $colsOS = $db->query("SHOW COLUMNS FROM ordenes_servicio")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('cotizacion_version', $colsOS, true)) {
            $db->exec("ALTER TABLE ordenes_servicio ADD COLUMN cotizacion_version INT NOT NULL DEFAULT 1 AFTER id_cotizacion");
            // Sincronizar versiones iniciales desde cotizaciones existentes
            $db->exec("UPDATE ordenes_servicio os 
                       INNER JOIN cotizaciones cot ON os.id_cotizacion = cot.id 
                       SET os.cotizacion_version = COALESCE(cot.version, 1) 
                       WHERE os.cotizacion_version = 1");
        }
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    ejecutarMigracion028($db);
    echo "[OK] Migración 028 ejecutada correctamente.\n";
}
