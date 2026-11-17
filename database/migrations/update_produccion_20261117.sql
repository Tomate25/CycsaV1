-- ==============================================================================
-- ACTUALIZACIÓN INCREMENTAL NO DESTRUCTIVA - CYCSA ERP / LIMS (PRODUCCIÓN)
-- Base de datos destino: cycsanic_cycsa_db (Bluehost cPanel / MySQL 5.7+ / MariaDB)
-- Preserva el 100% de los datos existentes en producción.
-- Incluye cambios acumulados hasta Migraciones 032 - 036:
--   - 032: Columna datos_recepcion_json en hojas_solicitud_detalles
--   - 033: Normalización de consecutivo único global RT-FM-13 (01, 02, 03...)
--   - 034: Permiso RBAC "reportes" en roles no-admin existentes
--   - 035: Enlace id_hoja_solicitud + índice en programacion_muestreo (RT-FM-40 B)
--   - 036: Columnas de vigencia/duración en ordenes_servicio (duracion_meses, etc.)
-- Nota: Luego de subir los archivos PHP, abra /migraciones?token=cycsa_deploy_2026
--       para ejecutar también la Migración 037 (auto-ingreso de muestras RT-FM-13).
-- ==============================================================================

SET NAMES utf8mb4;

-- ------------------------------------------------------------------------------
-- 1. Migración 032: hojas_solicitud_detalles.datos_recepcion_json
-- ------------------------------------------------------------------------------
SET @col_032 := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'hojas_solicitud_detalles'
      AND COLUMN_NAME = 'datos_recepcion_json'
);
SET @sql_032 := IF(
    @col_032 = 0,
    'ALTER TABLE `hojas_solicitud_detalles` ADD COLUMN `datos_recepcion_json` LONGTEXT NULL AFTER `observacion_edad`',
    'SELECT ''[OK] hojas_solicitud_detalles.datos_recepcion_json ya existe'' AS info'
);
PREPARE stmt_032 FROM @sql_032;
EXECUTE stmt_032;
DEALLOCATE PREPARE stmt_032;

-- ------------------------------------------------------------------------------
-- 2. Migración 035: programacion_muestreo.id_hoja_solicitud + índice
-- ------------------------------------------------------------------------------
SET @col_035 := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'programacion_muestreo'
      AND COLUMN_NAME = 'id_hoja_solicitud'
);
SET @sql_035 := IF(
    @col_035 = 0,
    'ALTER TABLE `programacion_muestreo` ADD COLUMN `id_hoja_solicitud` INT NULL DEFAULT NULL AFTER `id_orden_servicio`',
    'SELECT ''[OK] programacion_muestreo.id_hoja_solicitud ya existe'' AS info'
);
PREPARE stmt_035 FROM @sql_035;
EXECUTE stmt_035;
DEALLOCATE PREPARE stmt_035;

SET @idx_035 := (
    SELECT COUNT(*) FROM information_schema.STATISTICS
    WHERE TABLE_SCHEMA = DATABASE()
      AND TABLE_NAME = 'programacion_muestreo'
      AND INDEX_NAME = 'idx_pm_hoja_solicitud'
);
SET @sql_idx_035 := IF(
    @idx_035 = 0,
    'ALTER TABLE `programacion_muestreo` ADD INDEX `idx_pm_hoja_solicitud` (`id_hoja_solicitud`)',
    'SELECT ''[OK] idx_pm_hoja_solicitud ya existe'' AS info'
);
PREPARE stmt_idx_035 FROM @sql_idx_035;
EXECUTE stmt_idx_035;
DEALLOCATE PREPARE stmt_idx_035;

-- ------------------------------------------------------------------------------
-- 3. Migración 036: ordenes_servicio (es_multimes, duracion_meses, fechas, periodicidad)
-- ------------------------------------------------------------------------------
SET @col_036_em := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ordenes_servicio' AND COLUMN_NAME = 'es_multimes'
);
SET @sql_036_em := IF(
    @col_036_em = 0,
    'ALTER TABLE `ordenes_servicio` ADD COLUMN `es_multimes` TINYINT(1) NOT NULL DEFAULT 0 AFTER `tipo_contrato`',
    'SELECT ''[OK] ordenes_servicio.es_multimes ya existe'' AS info'
);
PREPARE stmt_036_em FROM @sql_036_em;
EXECUTE stmt_036_em;
DEALLOCATE PREPARE stmt_036_em;

SET @col_036_dm := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ordenes_servicio' AND COLUMN_NAME = 'duracion_meses'
);
SET @sql_036_dm := IF(
    @col_036_dm = 0,
    'ALTER TABLE `ordenes_servicio` ADD COLUMN `duracion_meses` INT NULL DEFAULT NULL AFTER `es_multimes`',
    'SELECT ''[OK] ordenes_servicio.duracion_meses ya existe'' AS info'
);
PREPARE stmt_036_dm FROM @sql_036_dm;
EXECUTE stmt_036_dm;
DEALLOCATE PREPARE stmt_036_dm;

SET @col_036_fi := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ordenes_servicio' AND COLUMN_NAME = 'fecha_inicio'
);
SET @sql_036_fi := IF(
    @col_036_fi = 0,
    'ALTER TABLE `ordenes_servicio` ADD COLUMN `fecha_inicio` DATE NULL DEFAULT NULL AFTER `duracion_meses`',
    'SELECT ''[OK] ordenes_servicio.fecha_inicio ya existe'' AS info'
);
PREPARE stmt_036_fi FROM @sql_036_fi;
EXECUTE stmt_036_fi;
DEALLOCATE PREPARE stmt_036_fi;

SET @col_036_ff := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ordenes_servicio' AND COLUMN_NAME = 'fecha_fin'
);
SET @sql_036_ff := IF(
    @col_036_ff = 0,
    'ALTER TABLE `ordenes_servicio` ADD COLUMN `fecha_fin` DATE NULL DEFAULT NULL AFTER `fecha_inicio`',
    'SELECT ''[OK] ordenes_servicio.fecha_fin ya existe'' AS info'
);
PREPARE stmt_036_ff FROM @sql_036_ff;
EXECUTE stmt_036_ff;
DEALLOCATE PREPARE stmt_036_ff;

SET @col_036_per := (
    SELECT COUNT(*) FROM information_schema.COLUMNS
    WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'ordenes_servicio' AND COLUMN_NAME = 'periodicidad'
);
SET @sql_036_per := IF(
    @col_036_per = 0,
    'ALTER TABLE `ordenes_servicio` ADD COLUMN `periodicidad` VARCHAR(50) NULL DEFAULT ''Mensual'' AFTER `fecha_fin`',
    'SELECT ''[OK] ordenes_servicio.periodicidad ya existe'' AS info'
);
PREPARE stmt_036_per FROM @sql_036_per;
EXECUTE stmt_036_per;
DEALLOCATE PREPARE stmt_036_per;

UPDATE `ordenes_servicio`
SET `duracion_meses` = GREATEST(1, TIMESTAMPDIFF(MONTH, `fecha_inicio`, `fecha_fin`))
WHERE `es_multimes` = 1
  AND `duracion_meses` IS NULL
  AND `fecha_inicio` IS NOT NULL
  AND `fecha_fin` IS NOT NULL
  AND `fecha_fin` >= `fecha_inicio`;

-- ------------------------------------------------------------------------------
-- 4. Migración 034: Habilitar permiso "reportes" en roles existentes no-admin
-- ------------------------------------------------------------------------------
UPDATE `roles`
SET `permisos` = JSON_SET(`permisos`, '$.reportes', JSON_OBJECT('ver', 1))
WHERE `id` > 1
  AND `permisos` IS NOT NULL
  AND `permisos` <> ''
  AND JSON_VALID(`permisos`) = 1
  AND JSON_EXTRACT(`permisos`, '$.reportes') IS NULL;

-- ------------------------------------------------------------------------------
-- 5. Migración 033: Normalización idempotente de consecutivo RT-FM-13 (01, 02...)
-- ------------------------------------------------------------------------------
SET @necesita_033 := (
    SELECT COUNT(*) FROM `hojas_solicitud`
    WHERE `numero_registro` IS NULL
       OR `numero_registro` = ''
       OR `numero_registro` REGEXP '^[0-9]+-[0-9]+$'
       OR `numero_registro` REGEXP '^0{2,}[0-9]{3,}$'
);
SET @rn_033 := 0;
UPDATE `hojas_solicitud`
SET `numero_registro` = LPAD((@rn_033 := @rn_033 + 1), 2, '0')
WHERE @necesita_033 > 0
  AND (`numero_registro` IS NULL OR `numero_registro` = '' OR `numero_registro` REGEXP '^[0-9]+(-[0-9]+)?$')
ORDER BY `id` ASC;
