<?php
// Migration 021: Corrección Administrativa, Versionamiento BASE-N y Sustitución de Documentos (TAREA-059 FASE B)
require_once dirname(__DIR__, 2) . '/app/Core/Conexion.php';
$db = Cycsa\Nucleo\Conexion::obtenerInstancia();

try {
    // 1. Tabla relacional para registro inmutable de reemisiones y correcciones documentales
    $db->exec("
        CREATE TABLE IF NOT EXISTS `documentos_reemisiones` (
            `id` INT(11) NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `tipo_documento` ENUM('matriz_ensayo', 'informe_rotura') NOT NULL DEFAULT 'matriz_ensayo',
            `documento_base_id` INT(11) NOT NULL,
            `version` INT(11) NOT NULL DEFAULT 1,
            `codigo_version` VARCHAR(100) NOT NULL,
            `sustituye_a` VARCHAR(100) NULL,
            `tipo_correccion` ENUM('administrativa', 'tecnica') NOT NULL DEFAULT 'administrativa',
            `motivo` TEXT NOT NULL,
            `campos_modificados_json` LONGTEXT NULL,
            `estado` ENUM('en_revision', 'aprobado', 'anulado') NOT NULL DEFAULT 'en_revision',
            `solicitado_por` INT(11) NULL,
            `aprobado_por` INT(11) NULL,
            `fecha_solicitud` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `fecha_aprobacion` DATETIME NULL,
            `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            INDEX `idx_doc_base` (`tipo_documento`, `documento_base_id`),
            INDEX `idx_codigo_version` (`codigo_version`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
    ");
    echo "[OK] Tabla 'documentos_reemisiones' creada o verificada.\n";
} catch (Exception $e) {
    echo "[ERROR] Error creando tabla 'documentos_reemisiones': " . $e->getMessage() . "\n";
}

try {
    // 2. Columna sustituye_a en informes_rotura si no existe
    $cols = $db->query("SHOW COLUMNS FROM `informes_rotura` LIKE 'sustituye_a'")->fetchAll();
    if (empty($cols)) {
        $db->exec("ALTER TABLE `informes_rotura` ADD COLUMN `sustituye_a` VARCHAR(100) NULL AFTER `numero`");
        echo "[OK] Columna 'sustituye_a' agregada a 'informes_rotura'.\n";
    } else {
        echo "[INFO] Columna 'sustituye_a' ya existe en 'informes_rotura'.\n";
    }
} catch (Exception $e) {
    echo "[ERROR] Error alterando 'informes_rotura': " . $e->getMessage() . "\n";
}
