<?php
/**
 * Migración 025: whitelist de ensayos habilitados al aprobar una Orden de Servicio (Sprint C.2).
 *
 * La lista queda fija en la aprobación. Una O/S sin filas conserva el
 * comportamiento legado: todos los ensayos de la cotización siguen permitidos.
 * No modifica cotizacion_detalles ni catálogos de formatos.
 *
 * up:   ejecutarMigracion025($db)  — idempotente
 * down: revertirMigracion025($db)  — elimina solo esta tabla
 *
 * Ejecución CLI: php database/migrations/025_orden_servicio_ensayos_permitidos.php
 */

if (!function_exists('ejecutarMigracion025')) {
    function ejecutarMigracion025(PDO $db): void
    {
        $tieneOs = $db->query("SHOW TABLES LIKE 'ordenes_servicio'")->fetch(PDO::FETCH_NUM);
        $tieneDet = $db->query("SHOW TABLES LIKE 'cotizacion_detalles'")->fetch(PDO::FETCH_NUM);
        if (!$tieneOs || !$tieneDet) {
            echo "[INFO] Tablas padre ausentes. Migración 025 omitida.\n";
            return;
        }

        $tabla = $db->query("SHOW TABLES LIKE 'orden_servicio_ensayos_permitidos'")->fetch(PDO::FETCH_NUM);
        if (!$tabla) {
            $db->exec("CREATE TABLE `orden_servicio_ensayos_permitidos` (
                `id` INT(11) NOT NULL AUTO_INCREMENT,
                `id_os` INT(11) NOT NULL,
                `id_cotizacion_detalle` INT(11) NOT NULL,
                `creado_por` INT(11) NULL DEFAULT NULL,
                `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uq_os_ensayo_permitido` (`id_os`, `id_cotizacion_detalle`),
                KEY `idx_osep_detalle` (`id_cotizacion_detalle`),
                CONSTRAINT `fk_osep_orden` FOREIGN KEY (`id_os`) REFERENCES `ordenes_servicio` (`id`) ON DELETE CASCADE ON UPDATE CASCADE,
                CONSTRAINT `fk_osep_detalle` FOREIGN KEY (`id_cotizacion_detalle`) REFERENCES `cotizacion_detalles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
            echo "[OK] Tabla orden_servicio_ensayos_permitidos creada.\n";
            return;
        }

        $columnas = $db->query("SHOW COLUMNS FROM `orden_servicio_ensayos_permitidos`")->fetchAll(PDO::FETCH_COLUMN);
        $agregar = [
            'id_os' => "ALTER TABLE `orden_servicio_ensayos_permitidos` ADD COLUMN `id_os` INT(11) NOT NULL",
            'id_cotizacion_detalle' => "ALTER TABLE `orden_servicio_ensayos_permitidos` ADD COLUMN `id_cotizacion_detalle` INT(11) NOT NULL",
            'creado_por' => "ALTER TABLE `orden_servicio_ensayos_permitidos` ADD COLUMN `creado_por` INT(11) NULL DEFAULT NULL",
            'creado_en' => "ALTER TABLE `orden_servicio_ensayos_permitidos` ADD COLUMN `creado_en` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP",
        ];
        foreach ($agregar as $columna => $sql) {
            if (!in_array($columna, $columnas, true)) {
                $db->exec($sql);
                echo "[OK] Columna {$columna} agregada.\n";
            }
        }

        $indices = $db->query("SHOW INDEX FROM `orden_servicio_ensayos_permitidos`")->fetchAll(PDO::FETCH_ASSOC);
        $nombres = array_column($indices, 'Key_name');
        if (!in_array('uq_os_ensayo_permitido', $nombres, true)) {
            $db->exec("ALTER TABLE `orden_servicio_ensayos_permitidos` ADD UNIQUE KEY `uq_os_ensayo_permitido` (`id_os`, `id_cotizacion_detalle`)");
            echo "[OK] Índice único uq_os_ensayo_permitido creado.\n";
        }

        $fks = $db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'orden_servicio_ensayos_permitidos'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'")->fetchAll(PDO::FETCH_COLUMN);
        if (!in_array('fk_osep_orden', $fks, true)) {
            $db->exec("ALTER TABLE `orden_servicio_ensayos_permitidos` ADD CONSTRAINT `fk_osep_orden` FOREIGN KEY (`id_os`) REFERENCES `ordenes_servicio` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
            echo "[OK] FK fk_osep_orden creada.\n";
        }
        if (!in_array('fk_osep_detalle', $fks, true)) {
            $db->exec("ALTER TABLE `orden_servicio_ensayos_permitidos` ADD CONSTRAINT `fk_osep_detalle` FOREIGN KEY (`id_cotizacion_detalle`) REFERENCES `cotizacion_detalles` (`id`) ON DELETE CASCADE ON UPDATE CASCADE");
            echo "[OK] FK fk_osep_detalle creada.\n";
        }

        echo "[OK] Tabla orden_servicio_ensayos_permitidos verificada (idempotente).\n";
    }
}

if (!function_exists('revertirMigracion025')) {
    function revertirMigracion025(PDO $db): void
    {
        $db->exec("DROP TABLE IF EXISTS `orden_servicio_ensayos_permitidos`");
        echo "[OK] Tabla orden_servicio_ensayos_permitidos eliminada (down).\n";
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    ejecutarMigracion025($db);
    echo "[OK] Migracion 025 ejecutada correctamente.\n";
}
