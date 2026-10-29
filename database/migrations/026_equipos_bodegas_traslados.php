<?php
/**
 * Migración: 026_equipos_bodegas_traslados.php
 * Sprint C.4: Bodegas de equipos de laboratorio y traslados trazables.
 *
 * Crea, sin alterar tablas existentes:
 * - equipos_bodegas
 * - equipos_traslados   (append-only; origen NULL = alta)
 * - equipos_ubicacion   (una fila por equipo)
 *
 * FK a proyectos y activos_fijos solo si esas tablas ya existen.
 * Semilla: bodega LAB-CENTRAL y alta de cada equipo activo sin ubicación.
 * No siembra bodegas de proyecto.
 *
 * Idempotente: puede ejecutarse dos veces.
 * Ejecución CLI: php database/migrations/026_equipos_bodegas_traslados.php
 */

if (!isset($db) || !($db instanceof PDO)) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

if (!function_exists('cycsa_mig026_tabla')) {
    function cycsa_mig026_tabla(PDO $db, string $tabla): bool {
        $stmt = $db->query('SHOW TABLES LIKE ' . $db->quote($tabla));
        return (bool)$stmt->fetch(PDO::FETCH_NUM);
    }
}

if (!function_exists('cycsa_mig026_fk')) {
    /**
     * Agrega una FK solo sobre las tablas nuevas de esta migración.
     */
    function cycsa_mig026_fk(PDO $db, string $tabla, string $nombre, string $definicion): void {
        $permitidas = ['equipos_bodegas', 'equipos_ubicacion', 'equipos_traslados'];
        if (!in_array($tabla, $permitidas, true)) {
            throw new RuntimeException('La migración 026 no altera tablas existentes.');
        }

        $stmt = $db->prepare(
            'SELECT CONSTRAINT_NAME
             FROM information_schema.TABLE_CONSTRAINTS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = :tabla
               AND CONSTRAINT_NAME = :nombre
             LIMIT 1'
        );
        $stmt->execute(['tabla' => $tabla, 'nombre' => $nombre]);
        if ($stmt->fetch(PDO::FETCH_NUM)) {
            return;
        }

        $db->exec("ALTER TABLE `{$tabla}` ADD CONSTRAINT `{$nombre}` {$definicion}");
    }
}

echo "--- Iniciando Migración 026: Bodegas y traslados de equipos (Sprint C.4) ---\n";

if (!cycsa_mig026_tabla($db, 'equipos_calibracion')) {
    echo "[INFO] Tabla equipos_calibracion ausente. Migración 026 omitida.\n";
    return;
}

$db->exec("
CREATE TABLE IF NOT EXISTS `equipos_bodegas` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `codigo` VARCHAR(50) NOT NULL,
    `nombre` VARCHAR(200) NOT NULL,
    `tipo` ENUM('central','proyecto','taller','externo') NOT NULL DEFAULT 'central',
    `proyecto_id` INT NULL,
    `responsable_id` INT NULL,
    `direccion` VARCHAR(255) NULL,
    `activo` TINYINT(1) NOT NULL DEFAULT 1,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_eqbod_codigo` (`codigo`),
    UNIQUE KEY `uk_eqbod_proyecto` (`proyecto_id`),
    KEY `idx_eqbod_tipo` (`tipo`),
    KEY `idx_eqbod_activo` (`activo`),
    KEY `idx_eqbod_responsable` (`responsable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "[OK] Tabla 'equipos_bodegas' verificada.\n";

$db->exec("
CREATE TABLE IF NOT EXISTS `equipos_traslados` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `equipo_id` INT NOT NULL,
    `bodega_origen_id` INT NULL,
    `bodega_destino_id` INT NOT NULL,
    `fecha_traslado` DATETIME NOT NULL,
    `usuario_id` INT NULL,
    `motivo` VARCHAR(255) NULL,
    `documento_ref` VARCHAR(120) NULL,
    `observaciones` TEXT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    KEY `idx_eqtras_equipo_fecha` (`equipo_id`, `fecha_traslado`),
    KEY `idx_eqtras_origen` (`bodega_origen_id`),
    KEY `idx_eqtras_destino` (`bodega_destino_id`),
    KEY `idx_eqtras_usuario` (`usuario_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "[OK] Tabla 'equipos_traslados' verificada.\n";

$db->exec("
CREATE TABLE IF NOT EXISTS `equipos_ubicacion` (
    `id` INT NOT NULL AUTO_INCREMENT,
    `equipo_id` INT NOT NULL,
    `bodega_id` INT NOT NULL,
    `activo_fijo_id` INT NULL,
    `estado` ENUM('disponible','en_uso','en_mantenimiento','en_calibracion','de_baja') NOT NULL DEFAULT 'disponible',
    `desde` DATETIME NOT NULL,
    `ultimo_traslado_id` INT NULL,
    `created_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (`id`),
    UNIQUE KEY `uk_equbic_equipo` (`equipo_id`),
    KEY `idx_equbic_bodega` (`bodega_id`),
    KEY `idx_equbic_activo` (`activo_fijo_id`),
    KEY `idx_equbic_estado` (`estado`),
    KEY `idx_equbic_ultimo` (`ultimo_traslado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
");
echo "[OK] Tabla 'equipos_ubicacion' verificada.\n";

cycsa_mig026_fk(
    $db,
    'equipos_traslados',
    'fk_eqtras_equipo',
    'FOREIGN KEY (`equipo_id`) REFERENCES `equipos_calibracion` (`id`) ON DELETE CASCADE'
);
cycsa_mig026_fk(
    $db,
    'equipos_traslados',
    'fk_eqtras_origen',
    'FOREIGN KEY (`bodega_origen_id`) REFERENCES `equipos_bodegas` (`id`) ON DELETE RESTRICT'
);
cycsa_mig026_fk(
    $db,
    'equipos_traslados',
    'fk_eqtras_destino',
    'FOREIGN KEY (`bodega_destino_id`) REFERENCES `equipos_bodegas` (`id`) ON DELETE RESTRICT'
);
cycsa_mig026_fk(
    $db,
    'equipos_ubicacion',
    'fk_equbic_equipo',
    'FOREIGN KEY (`equipo_id`) REFERENCES `equipos_calibracion` (`id`) ON DELETE CASCADE'
);
cycsa_mig026_fk(
    $db,
    'equipos_ubicacion',
    'fk_equbic_bodega',
    'FOREIGN KEY (`bodega_id`) REFERENCES `equipos_bodegas` (`id`) ON DELETE RESTRICT'
);

if (cycsa_mig026_tabla($db, 'proyectos')) {
    cycsa_mig026_fk(
        $db,
        'equipos_bodegas',
        'fk_eqbod_proyecto',
        'FOREIGN KEY (`proyecto_id`) REFERENCES `proyectos` (`id`) ON DELETE SET NULL'
    );
    echo "[OK] FK equipos_bodegas.proyecto_id → proyectos verificada.\n";
} else {
    echo "[INFO] Tabla proyectos ausente: FK de proyecto omitida.\n";
}

if (cycsa_mig026_tabla($db, 'activos_fijos')) {
    cycsa_mig026_fk(
        $db,
        'equipos_ubicacion',
        'fk_equbic_activo',
        'FOREIGN KEY (`activo_fijo_id`) REFERENCES `activos_fijos` (`id`) ON DELETE SET NULL'
    );
    echo "[OK] FK equipos_ubicacion.activo_fijo_id → activos_fijos verificada.\n";
} else {
    echo "[INFO] Tabla activos_fijos ausente: puente de activo fijo omitido.\n";
}

$mig026TxPropia = !$db->inTransaction();
if ($mig026TxPropia) {
    $db->beginTransaction();
}
try {
    $db->exec("
        INSERT IGNORE INTO `equipos_bodegas` (`codigo`, `nombre`, `tipo`, `direccion`, `activo`)
        VALUES (
            'LAB-CENTRAL',
            'Laboratorio Central',
            'central',
            'Laboratorio central de control de calidad',
            1
        )
    ");

    $db->exec("
        INSERT INTO `equipos_ubicacion` (`equipo_id`, `bodega_id`, `estado`, `desde`)
        SELECT e.id, b.id, 'disponible', NOW()
        FROM `equipos_calibracion` e
        INNER JOIN `equipos_bodegas` b ON b.codigo = 'LAB-CENTRAL'
        WHERE e.activo = 1
          AND NOT EXISTS (
              SELECT 1 FROM `equipos_ubicacion` u WHERE u.equipo_id = e.id
          )
    ");

    $db->exec("
        INSERT INTO `equipos_traslados` (
            `equipo_id`, `bodega_origen_id`, `bodega_destino_id`, `fecha_traslado`, `usuario_id`, `motivo`
        )
        SELECT u.equipo_id, NULL, u.bodega_id, u.desde, NULL, 'Alta inicial en Laboratorio Central'
        FROM `equipos_ubicacion` u
        INNER JOIN `equipos_bodegas` b ON b.id = u.bodega_id AND b.codigo = 'LAB-CENTRAL'
        WHERE NOT EXISTS (
            SELECT 1
            FROM `equipos_traslados` t
            WHERE t.equipo_id = u.equipo_id
              AND t.bodega_origen_id IS NULL
        )
    ");

    $db->exec("
        UPDATE `equipos_ubicacion` u
        INNER JOIN `equipos_traslados` t
            ON t.equipo_id = u.equipo_id
           AND t.bodega_origen_id IS NULL
           AND t.bodega_destino_id = u.bodega_id
        SET u.ultimo_traslado_id = t.id
        WHERE u.ultimo_traslado_id IS NULL
    ");

    if ($mig026TxPropia) {
        $db->commit();
    }
    echo "[OK] Semilla LAB-CENTRAL y altas de equipos activos verificada.\n";
} catch (Throwable $e) {
    if ($mig026TxPropia && $db->inTransaction()) {
        $db->rollBack();
    }
    throw $e;
}

echo "--- Migración 026 finalizada con éxito ---\n";
