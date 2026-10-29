<?php
/**
 * Migración 027: entidades de la bitácora (Sprint C.5).
 *
 * ALTER aditivo sobre `bitacora`. No reescribe filas ni toca otras tablas.
 * Columnas nuevas, todas NULL:
 * - entidad_tipo  VARCHAR(40)  os|matriz|rtfm13|muestra|otro
 * - entidad_id    INT
 * - id_os         INT
 * - usuario_rol   VARCHAR(50)
 * - datos_json    TEXT
 *
 * Índices: idx_bitacora_id_os, idx_bitacora_entidad, idx_bitacora_fecha.
 * Idempotente: ADD COLUMN / ADD INDEX solo si faltan (INFORMATION_SCHEMA).
 *
 * Ejecución CLI: php database/migrations/027_bitacora_entidades.php
 */

if (!isset($db) || !($db instanceof PDO)) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

if (!function_exists('cycsa_mig027_tabla')) {
    function cycsa_mig027_tabla(PDO $db, string $tabla): bool {
        $stmt = $db->prepare(
            'SELECT TABLE_NAME
             FROM information_schema.TABLES
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :tabla
             LIMIT 1'
        );
        $stmt->execute(['tabla' => $tabla]);
        return (bool)$stmt->fetch(PDO::FETCH_NUM);
    }
}

if (!function_exists('cycsa_mig027_columna')) {
    function cycsa_mig027_columna(PDO $db, string $columna): bool {
        $stmt = $db->prepare(
            'SELECT COLUMN_NAME
             FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :tabla
               AND COLUMN_NAME = :columna
             LIMIT 1'
        );
        $stmt->execute(['tabla' => 'bitacora', 'columna' => $columna]);
        return (bool)$stmt->fetch(PDO::FETCH_NUM);
    }
}

if (!function_exists('cycsa_mig027_indice')) {
    function cycsa_mig027_indice(PDO $db, string $indice): bool {
        $stmt = $db->prepare(
            'SELECT INDEX_NAME
             FROM information_schema.STATISTICS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :tabla
               AND INDEX_NAME = :indice
             LIMIT 1'
        );
        $stmt->execute(['tabla' => 'bitacora', 'indice' => $indice]);
        return (bool)$stmt->fetch(PDO::FETCH_NUM);
    }
}

echo "--- Iniciando Migración 027: Entidades de bitácora (Sprint C.5) ---\n";

if (!cycsa_mig027_tabla($db, 'bitacora')) {
    echo "[INFO] Tabla bitacora ausente. Migración 027 omitida.\n";
    return;
}

$columnas = [
    'entidad_tipo' => 'ALTER TABLE `bitacora` ADD COLUMN `entidad_tipo` VARCHAR(40) NULL',
    'entidad_id' => 'ALTER TABLE `bitacora` ADD COLUMN `entidad_id` INT NULL',
    'id_os' => 'ALTER TABLE `bitacora` ADD COLUMN `id_os` INT NULL',
    'usuario_rol' => 'ALTER TABLE `bitacora` ADD COLUMN `usuario_rol` VARCHAR(50) NULL',
    'datos_json' => 'ALTER TABLE `bitacora` ADD COLUMN `datos_json` TEXT NULL',
];

foreach ($columnas as $nombre => $sql) {
    if (cycsa_mig027_columna($db, $nombre)) {
        echo "[OK] Columna bitacora.{$nombre} ya existe.\n";
        continue;
    }
    $db->exec($sql);
    echo "[OK] Columna bitacora.{$nombre} agregada.\n";
}

$indices = [
    'idx_bitacora_id_os' => 'ALTER TABLE `bitacora` ADD INDEX `idx_bitacora_id_os` (`id_os`)',
    'idx_bitacora_entidad' => 'ALTER TABLE `bitacora` ADD INDEX `idx_bitacora_entidad` (`entidad_tipo`, `entidad_id`)',
    'idx_bitacora_fecha' => 'ALTER TABLE `bitacora` ADD INDEX `idx_bitacora_fecha` (`fecha_creacion`)',
];

foreach ($indices as $nombre => $sql) {
    if (cycsa_mig027_indice($db, $nombre)) {
        echo "[OK] Índice {$nombre} ya existe.\n";
        continue;
    }
    $db->exec($sql);
    echo "[OK] Índice {$nombre} agregado.\n";
}

echo "--- Migración 027 finalizada ---\n";
