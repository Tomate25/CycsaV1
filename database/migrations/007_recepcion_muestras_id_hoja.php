<?php
// Migration 007: Soporte Integral para Múltiples Hojas RT-FM-13 por Orden de Servicio en Recepción de Muestras
require_once dirname(__DIR__, 2) . '/app/Core/Conexion.php';
$db = Cycsa\Nucleo\Conexion::obtenerInstancia();

$check = $db->query("SHOW COLUMNS FROM `recepcion_muestras` LIKE 'id_hoja'")->fetch();
if (!$check) {
    $db->exec("ALTER TABLE `recepcion_muestras` ADD COLUMN `id_hoja` INT NULL AFTER `id_os`, ADD INDEX `idx_rm_id_hoja` (`id_hoja`);");
    echo "[OK] Columna 'id_hoja' agregada exitosamente a 'recepcion_muestras'.\n";
} else {
    echo "[INFO] La columna 'id_hoja' ya existe en 'recepcion_muestras'.\n";
}
