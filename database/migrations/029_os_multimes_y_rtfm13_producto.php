<?php

/**
 * Migración 029: Soporte para Órdenes de Servicio Multi-mes y Vinculación
 * de Hoja de Solicitud CYCSA-RT-FM-13 por Producto / Ítem individual.
 * 
 * 100% aditiva e idempotente.
 */

require_once dirname(__DIR__, 2) . '/app/Core/Conexion.php';

if (!function_exists('ejecutarMigracion029')) {
    function ejecutarMigracion029(PDO $db): void {
        // 1. Ampliar ordenes_servicio con soporte multi-mes
        $colsOS = $db->query("SHOW COLUMNS FROM ordenes_servicio")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('es_multimes', $colsOS, true)) {
            $db->exec("ALTER TABLE ordenes_servicio ADD COLUMN es_multimes TINYINT(1) NOT NULL DEFAULT 0 AFTER tipo_contrato");
        }
        if (!in_array('fecha_inicio', $colsOS, true)) {
            $db->exec("ALTER TABLE ordenes_servicio ADD COLUMN fecha_inicio DATE NULL DEFAULT NULL AFTER es_multimes");
        }
        if (!in_array('fecha_fin', $colsOS, true)) {
            $db->exec("ALTER TABLE ordenes_servicio ADD COLUMN fecha_fin DATE NULL DEFAULT NULL AFTER fecha_inicio");
        }
        if (!in_array('periodicidad', $colsOS, true)) {
            $db->exec("ALTER TABLE ordenes_servicio ADD COLUMN periodicidad VARCHAR(50) NULL DEFAULT 'Mensual' AFTER fecha_fin");
        }

        // 2. Ampliar hojas_solicitud con soporte de producto/ítem individual
        $colsHS = $db->query("SHOW COLUMNS FROM hojas_solicitud")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('id_detalle_cotizacion', $colsHS, true)) {
            $db->exec("ALTER TABLE hojas_solicitud ADD COLUMN id_detalle_cotizacion INT(11) NULL DEFAULT NULL AFTER id_os");
        }
        if (!in_array('id_producto', $colsHS, true)) {
            $db->exec("ALTER TABLE hojas_solicitud ADD COLUMN id_producto INT(11) NULL DEFAULT NULL AFTER id_detalle_cotizacion");
        }
        if (!in_array('ensayo_id', $colsHS, true)) {
            $db->exec("ALTER TABLE hojas_solicitud ADD COLUMN ensayo_id INT(11) NULL DEFAULT NULL AFTER id_producto");
        }

        // 3. Índices aditivos en hojas_solicitud
        $indicesHS = $db->query("SHOW INDEX FROM hojas_solicitud")->fetchAll(PDO::FETCH_ASSOC);
        $nombresIndices = array_column($indicesHS, 'Key_name');

        if (!in_array('idx_hs_os_detalle', $nombresIndices, true)) {
            $db->exec("ALTER TABLE hojas_solicitud ADD INDEX idx_hs_os_detalle (id_os, id_detalle_cotizacion)");
        }
        if (!in_array('idx_hs_producto', $nombresIndices, true)) {
            $db->exec("ALTER TABLE hojas_solicitud ADD INDEX idx_hs_producto (id_producto)");
        }

        // 4. Normalizar O/S activas históricas con estado legado a 'Aprobada'
        $db->exec("UPDATE ordenes_servicio SET estado = 'Aprobada' WHERE estado = 'Estado 5: Solicitud Tecnicos'");
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    ejecutarMigracion029($db);
    echo "[OK] Migración 029 ejecutada correctamente.\n";
}
