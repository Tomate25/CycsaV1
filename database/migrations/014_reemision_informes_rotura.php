<?php
// Migration 014: Reemisión N+1, Bloqueo Post-Aprobación, PDF con Hash sha256 e Invalidación de Consolidado (TAREA-044)
require_once dirname(__DIR__, 2) . '/app/Core/Conexion.php';
$db = Cycsa\Nucleo\Conexion::obtenerInstancia();

try {
    $db->exec("ALTER TABLE `informes_rotura` MODIFY COLUMN `estado` ENUM('borrador','en_revision','aprobado','devuelto','anulado') DEFAULT 'borrador'");
    echo "[OK] Columna 'estado' modificada para incluir 'anulado'.\n";
} catch (Exception $e) {
    echo "[INFO] Estado enum: " . $e->getMessage() . "\n";
}

$colsSha = $db->query("SHOW COLUMNS FROM `informes_rotura` LIKE 'sha256'")->fetchAll();
if (empty($colsSha)) {
    $db->exec("ALTER TABLE `informes_rotura` ADD COLUMN `sha256` VARCHAR(64) NULL AFTER `pdf`");
    echo "[OK] Columna 'sha256' agregada a 'informes_rotura'.\n";
} else {
    echo "[INFO] Columna 'sha256' ya existe.\n";
}

$colsMot = $db->query("SHOW COLUMNS FROM `informes_rotura` LIKE 'motivo_reemision'")->fetchAll();
if (empty($colsMot)) {
    $db->exec("ALTER TABLE `informes_rotura` ADD COLUMN `motivo_reemision` TEXT NULL AFTER `motivo_devolucion`");
    echo "[OK] Columna 'motivo_reemision' agregada a 'informes_rotura'.\n";
} else {
    echo "[INFO] Columna 'motivo_reemision' ya existe.\n";
}
