-- ============================================================================
-- CYCSA ERP / LIMS — Script Incremental de Producción (2026-11-22)
-- Plan de Trabajo CYCSA: Acuerdos 16 Nov 2026 (Fases 1, 2, 3 y 4)
-- Compatible con MySQL 5.7 / 8.0 / MariaDB en Bluehost cPanel (sin DEFINER)
-- POLÍTICA ESTRICTA: CERO DROP, CERO TRUNCATE, CERO DELETE de datos vivos.
-- ============================================================================

SET NAMES utf8mb4;
SET @db_name = DATABASE();

-- ----------------------------------------------------------------------------
-- 1. Tabla `config_calculo_lab` (parámetros configurables de laboratorio y flujos)
-- ----------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS `config_calculo_lab` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clave` VARCHAR(100) NOT NULL UNIQUE,
    `valor` TEXT NULL,
    `descripcion` VARCHAR(255) NULL,
    `updated_at` DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT IGNORE INTO `config_calculo_lab` (`clave`, `valor`, `descripcion`) VALUES
('cilindros_divisor_kgcm2', '14.2029', 'Divisor oficial psi -> kgf/cm² (14.2029 hoja RT-FM-61, configurable a 14.2 o 14.223)'),
('cilindros_usar_prensa', '0', 'Aplicar corrección polinomial PRENSA-01 en cilindros (0=hoja campo oficial, 1=central)'),
('cilindros_usar_factor_ld', '0', 'Aplicar factor L/D ASTM C42/C39 en cilindros (0=hoja oficial, 1=activar)'),
('cilindros_redondear_psi_10', '1', 'Redondear resistencia a compresión a los 10 psi más cercanos (acuerdo 16 nov)'),
('cilindros_redondear_pv_10', '1', 'Redondear peso volumétrico a los 10 kg/m³ más cercanos'),
('proctor_metodo', 'maximo_medido', 'Método de reporte Proctor RT-FM-27: maximo_medido o regresion_parabolica'),
('cbr_modo_reporte', 'golpes', 'Regla CBR RT-FM-46: golpes (100/95/90 por 56/25/12 golpes) o interpolado'),
('martillo_posicion_defecto', 'A', 'Posición por defecto del polinomio de esclerómetro (A, B o C)'),
('martillo_usar_factor_cal', '0', 'Aplicar factor de calibración 0.976 en martillo suizo (0=no, 1=sí)'),
('martillo_redondeo_psi', '100', 'Paso de redondeo en martillo suizo (1, 10 o 100 psi)'),
('informe_incluir_incertidumbre', '0', 'Mostrar incertidumbre U junto a la resistencia en informe final (0=no, 1=sí)'),
('informe_envio_auto_aprobar', '0', 'Enviar automáticamente el informe PDF al aprobarlo (0=manual, 1=automático)'),
('informe_parcial_es_interno', '0', 'Marcar informes parciales por muestra rota como documento interno (0=cliente, 1=interno)'),
('informe_ubicacion_aditivos', 'pie', 'Ubicación de notas de aditivos en informe (pie, cabecera o ambos)'),
('cotizacion_responsable_interno_id', '', 'ID de usuario responsable interno autorizado para aprobar cotizaciones'),
('informe_observaciones_pendientes_catalogo', '["Pendiente firma de revisión técnica","Pendiente verificación de equipo calibrado","Pendiente confirmación de datos de recepción","Observación de cliente en trámite"]', 'Catálogo JSON de observaciones pendientes para sección de firmas');

-- ----------------------------------------------------------------------------
-- 2. Columnas en `roturas` y `plan_rotura_edades` (Fase 1: 1.3, 1.4, 1.8)
-- ----------------------------------------------------------------------------
SET @sql = (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'roturas' AND COLUMN_NAME = 'peso_volumetrico'),
        'SELECT 1',
        'ALTER TABLE `roturas` ADD COLUMN `peso_volumetrico` DECIMAL(10,2) NULL AFTER `area`'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'roturas' AND COLUMN_NAME = 'edad_calculada'),
        'SELECT 1',
        'ALTER TABLE `roturas` ADD COLUMN `edad_calculada` INT NULL AFTER `fecha_real`'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (
    SELECT IF(
        EXISTS(SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'plan_rotura_edades'),
        'ALTER TABLE `plan_rotura_edades` MODIFY COLUMN `tolerancia_horas` DECIMAL(5,2) NOT NULL DEFAULT 20.00',
        'SELECT 1'
    )
);
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- 3. Columnas en `informes_rotura` (Fase 2: 2.2, 2.7, 2.20)
-- ----------------------------------------------------------------------------
SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'informes_rotura' AND COLUMN_NAME = 'revisado_por'), 'SELECT 1', 'ALTER TABLE `informes_rotura` ADD COLUMN `revisado_por` INT NULL AFTER `estado`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'informes_rotura' AND COLUMN_NAME = 'fecha_revision'), 'SELECT 1', 'ALTER TABLE `informes_rotura` ADD COLUMN `fecha_revision` DATETIME NULL AFTER `revisado_por`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'informes_rotura' AND COLUMN_NAME = 'revisado_nombre'), 'SELECT 1', 'ALTER TABLE `informes_rotura` ADD COLUMN `revisado_nombre` VARCHAR(150) NULL AFTER `fecha_revision`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'informes_rotura' AND COLUMN_NAME = 'aprobado_nombre'), 'SELECT 1', 'ALTER TABLE `informes_rotura` ADD COLUMN `aprobado_nombre` VARCHAR(150) NULL AFTER `aprobado_por`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'informes_rotura' AND COLUMN_NAME = 'observaciones_pendientes'), 'SELECT 1', 'ALTER TABLE `informes_rotura` ADD COLUMN `observaciones_pendientes` TEXT NULL AFTER `observaciones`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'informes_rotura' AND COLUMN_NAME = 'es_interno'), 'SELECT 1', 'ALTER TABLE `informes_rotura` ADD COLUMN `es_interno` TINYINT(1) NOT NULL DEFAULT 0 AFTER `observaciones_pendientes`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- 4. Versionado de Órdenes de Servicio (Fase 2: 2.5)
-- ----------------------------------------------------------------------------
SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ordenes_servicio' AND COLUMN_NAME = 'version'), 'SELECT 1', 'ALTER TABLE `ordenes_servicio` ADD COLUMN `version` INT NOT NULL DEFAULT 1'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'ordenes_servicio' AND COLUMN_NAME = 'motivo_cambio_version'), 'SELECT 1', 'ALTER TABLE `ordenes_servicio` ADD COLUMN `motivo_cambio_version` TEXT NULL'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

CREATE TABLE IF NOT EXISTS `ordenes_servicio_versiones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `id_os` INT NOT NULL,
    `version` INT NOT NULL,
    `snapshot_json` LONGTEXT NOT NULL,
    `motivo_cambio` TEXT NULL,
    `usuario_id` INT NULL,
    `created_at` DATETIME DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_os_ver_id_os` (`id_os`),
    UNIQUE KEY `uq_os_ver` (`id_os`, `version`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ----------------------------------------------------------------------------
-- 5. Aprobación interna y responsable interno en `cotizaciones` (Fase 2: 2.9-2.11)
-- ----------------------------------------------------------------------------
SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'id_responsable_interno'), 'SELECT 1', 'ALTER TABLE `cotizaciones` ADD COLUMN `id_responsable_interno` INT NULL'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'aprobacion_interna_por'), 'SELECT 1', 'ALTER TABLE `cotizaciones` ADD COLUMN `aprobacion_interna_por` INT NULL'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'cotizaciones' AND COLUMN_NAME = 'fecha_aprobacion_interna'), 'SELECT 1', 'ALTER TABLE `cotizaciones` ADD COLUMN `fecha_aprobacion_interna` DATETIME NULL'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- 6. Cuota mensual en `orden_servicio_ensayos_permitidos` (Fase 2: 2.13-2.14)
-- ----------------------------------------------------------------------------
SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'orden_servicio_ensayos_permitidos' AND COLUMN_NAME = 'cuota_mensual'), 'SELECT 1', 'ALTER TABLE `orden_servicio_ensayos_permitidos` ADD COLUMN `cuota_mensual` INT NULL'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- 7. Corrección de unidades en `formatos_ensayos` (Fase 3: 3.8)
-- ----------------------------------------------------------------------------
UPDATE `formatos_ensayos`
SET `configuracion_json` = REPLACE(
    REPLACE(
        REPLACE(`configuracion_json`, 'P.V.S.Sitio (kg/cm²)', 'P.V.S.Sitio (kg/m³)'),
        'Módulo de finura ((%) (P/P))', 'Módulo de finura'
    ),
    '50 nm', '50 mm'
)
WHERE `configuracion_json` LIKE '%P.V.S.Sitio (kg/cm²)%'
   OR `configuracion_json` LIKE '%Módulo de finura ((%) (P/P))%'
   OR `configuracion_json` LIKE '%50 nm%';

-- ----------------------------------------------------------------------------
-- 8. Migración 040: Reordenamiento Flujo Recepción -> Técnico -> Matriz -> Lab
-- ----------------------------------------------------------------------------
SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'hojas_solicitud' AND COLUMN_NAME = 'etapa_flujo'), 'SELECT 1', 'ALTER TABLE `hojas_solicitud` ADD COLUMN `etapa_flujo` VARCHAR(40) NOT NULL DEFAULT ''recepcion_borrador'''));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'hojas_solicitud' AND COLUMN_NAME = 'recepcion_completa'), 'SELECT 1', 'ALTER TABLE `hojas_solicitud` ADD COLUMN `recepcion_completa` TINYINT(1) NOT NULL DEFAULT 0'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'hojas_solicitud' AND COLUMN_NAME = 'fecha_recepcion_completa'), 'SELECT 1', 'ALTER TABLE `hojas_solicitud` ADD COLUMN `fecha_recepcion_completa` DATETIME NULL'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'hojas_solicitud' AND COLUMN_NAME = 'recepcion_completada_por'), 'SELECT 1', 'ALTER TABLE `hojas_solicitud` ADD COLUMN `recepcion_completada_por` INT NULL'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'hojas_solicitud' AND COLUMN_NAME = 'motivo_reapertura_recepcion'), 'SELECT 1', 'ALTER TABLE `hojas_solicitud` ADD COLUMN `motivo_reapertura_recepcion` TEXT NULL'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'hojas_solicitud' AND COLUMN_NAME = 'autorizacion_urgente_supervisor'), 'SELECT 1', 'ALTER TABLE `hojas_solicitud` ADD COLUMN `autorizacion_urgente_supervisor` TINYINT(1) NOT NULL DEFAULT 0'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'hojas_solicitud' AND COLUMN_NAME = 'motivo_urgente_supervisor'), 'SELECT 1', 'ALTER TABLE `hojas_solicitud` ADD COLUMN `motivo_urgente_supervisor` TEXT NULL'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'recepcion_muestras' AND COLUMN_NAME = 'etapa_flujo'), 'SELECT 1', 'ALTER TABLE `recepcion_muestras` ADD COLUMN `etapa_flujo` VARCHAR(40) NOT NULL DEFAULT ''recepcion_completa'''));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- Backfill de hojas existentes con muestras en laboratorio para no trabar producción
UPDATE `hojas_solicitud` hs
INNER JOIN `recepcion_muestras` rm ON rm.id_hoja = hs.id
SET hs.recepcion_completa = 1,
    hs.fecha_recepcion_completa = COALESCE(hs.fecha_recepcion_completa, rm.fecha_recepcion, NOW()),
    hs.etapa_flujo = CASE WHEN hs.etapa_flujo = 'recepcion_borrador' THEN 'recepcion_completa' ELSE hs.etapa_flujo END
WHERE hs.recepcion_completa = 0 AND hs.motivo_reapertura_recepcion IS NULL;

-- Backfill tolerancias ASTM C39 en plan_rotura_edades
UPDATE `plan_rotura_edades`
SET `tolerancia_horas` = CASE `edad_dias`
    WHEN 1 THEN 0.50
    WHEN 3 THEN 2.00
    WHEN 7 THEN 6.00
    WHEN 14 THEN 12.00
    WHEN 28 THEN 20.00
    WHEN 56 THEN 36.00
    WHEN 90 THEN 48.00
    ELSE ROUND(GREATEST(2.0, `edad_dias` * 0.75), 2)
END
WHERE `tolerancia_horas` = 24.00 AND `edad_dias` IN (1, 3, 7, 14, 28, 56, 90);

-- ----------------------------------------------------------------------------
-- 9. Migración 041: Espacio de firma adicional desde RT-FM-13 hacia informe oficial
-- ----------------------------------------------------------------------------
SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'hojas_solicitud' AND COLUMN_NAME = 'incluir_firma_adicional'), 'SELECT 1', 'ALTER TABLE `hojas_solicitud` ADD COLUMN `incluir_firma_adicional` TINYINT(1) NOT NULL DEFAULT 0 AFTER `firma_cliente`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'hojas_solicitud' AND COLUMN_NAME = 'firma_adicional_nombre'), 'SELECT 1', 'ALTER TABLE `hojas_solicitud` ADD COLUMN `firma_adicional_nombre` VARCHAR(180) NULL DEFAULT NULL AFTER `incluir_firma_adicional`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'hojas_solicitud' AND COLUMN_NAME = 'firma_adicional_cargo'), 'SELECT 1', 'ALTER TABLE `hojas_solicitud` ADD COLUMN `firma_adicional_cargo` VARCHAR(180) NULL DEFAULT NULL AFTER `firma_adicional_nombre`'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

-- ----------------------------------------------------------------------------
-- 10. Migración 042: Asignación de producto/ensayo por muestra en RT-FM-13
-- ----------------------------------------------------------------------------
SET @sql = (SELECT IF(EXISTS(SELECT 1 FROM information_schema.COLUMNS WHERE TABLE_SCHEMA = @db_name AND TABLE_NAME = 'hojas_solicitud_detalles' AND COLUMN_NAME = 'id_detalle_cotizacion'), 'SELECT 1', 'ALTER TABLE `hojas_solicitud_detalles` ADD COLUMN `id_detalle_cotizacion` INT(11) NULL DEFAULT NULL AFTER `id_hoja_solicitud`, ADD INDEX `idx_hsd_detalle_cot` (`id_detalle_cotizacion`)'));
PREPARE stmt FROM @sql; EXECUTE stmt; DEALLOCATE PREPARE stmt;

UPDATE `hojas_solicitud_detalles` hsd
INNER JOIN `hojas_solicitud` hs ON hs.id = hsd.id_hoja_solicitud
SET hsd.id_detalle_cotizacion = hs.id_detalle_cotizacion
WHERE hsd.id_detalle_cotizacion IS NULL
  AND hs.id_detalle_cotizacion IS NOT NULL
  AND hs.id_detalle_cotizacion > 0;

SELECT 'UPDATE_PRODUCCION_20261122_OK' AS resultado;
