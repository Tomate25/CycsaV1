-- ==============================================================================
-- ACTUALIZACIÓN INCREMENTAL DE BASE DE DATOS - CYCSA PRODUCCIÓN
-- Base de datos objetivo: cycsanic_cycsa_db (Bluehost cPanel / phpMyAdmin)
-- ==============================================================================

-- 1. TABLA PARA VERSIONAMIENTO DE COTIZACIONES (ISO/IEC 17025 Y TRAZABILIDAD)
CREATE TABLE IF NOT EXISTS `cotizacion_versiones` (
  `id` int(11) NOT NULL AUTO_INCREMENT,
  `id_cotizacion` int(11) NOT NULL,
  `version` int(11) NOT NULL,
  `fecha_creacion` timestamp NOT NULL DEFAULT current_timestamp(),
  `datos_json` longtext NOT NULL,
  `motivo_cambio` varchar(255) DEFAULT NULL,
  PRIMARY KEY (`id`),
  KEY `id_cotizacion` (`id_cotizacion`),
  CONSTRAINT `cotizacion_versiones_ibfk_1` FOREIGN KEY (`id_cotizacion`) REFERENCES `cotizaciones` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. CAMPOS PARA COTIZACIONES (CONTROL DE VERSIONES, OBSERVACIONES Y ADJUNTOS)
ALTER TABLE `cotizaciones` ADD COLUMN IF NOT EXISTS `version` int(11) NOT NULL DEFAULT 1;
ALTER TABLE `cotizaciones` ADD COLUMN IF NOT EXISTS `motivo_observacion` text DEFAULT NULL;
ALTER TABLE `cotizaciones` ADD COLUMN IF NOT EXISTS `motivo_rechazo_cliente` text DEFAULT NULL;
ALTER TABLE `cotizaciones` ADD COLUMN IF NOT EXISTS `token_seguridad` varchar(64) DEFAULT NULL;
ALTER TABLE `cotizaciones` ADD COLUMN IF NOT EXISTS `configuracion_notas` text DEFAULT NULL;
ALTER TABLE `cotizaciones` ADD COLUMN IF NOT EXISTS `contactos` text DEFAULT NULL;
ALTER TABLE `cotizaciones` ADD COLUMN IF NOT EXISTS `incluir_anexo_tecnico` tinyint(1) NOT NULL DEFAULT 0;
ALTER TABLE `cotizaciones` ADD COLUMN IF NOT EXISTS `anexo_tecnico` text DEFAULT NULL;
ALTER TABLE `cotizaciones` ADD COLUMN IF NOT EXISTS `archivo_adjunto` varchar(255) DEFAULT NULL;

-- 3. CAMPOS PARA FORMATOS DE ENSAYOS (EDITOR Y PLANTILLAS DE INFORMES)
ALTER TABLE `formatos_ensayos` ADD COLUMN IF NOT EXISTS `configuracion_json` longtext DEFAULT NULL;
ALTER TABLE `formatos_ensayos` ADD COLUMN IF NOT EXISTS `version_formato` varchar(50) NOT NULL DEFAULT 'V1R2';
ALTER TABLE `formatos_ensayos` ADD COLUMN IF NOT EXISTS `fecha_actualizacion` timestamp NULL DEFAULT NULL;

-- 4. CAMPOS PARA ÓRDENES DE SERVICIO (DETECCIÓN MC/MS Y TRAZABILIDAD DE CAMPO)
ALTER TABLE `ordenes_servicio` ADD COLUMN IF NOT EXISTS `requiere_muestreo` tinyint(1) DEFAULT NULL;
ALTER TABLE `ordenes_servicio` ADD COLUMN IF NOT EXISTS `horas_espera_requeridas` int(11) NOT NULL DEFAULT 24;
ALTER TABLE `ordenes_servicio` ADD COLUMN IF NOT EXISTS `hoja_campo_codigo` varchar(100) DEFAULT NULL;
ALTER TABLE `ordenes_servicio` ADD COLUMN IF NOT EXISTS `hoja_campo_operador` varchar(150) DEFAULT NULL;
ALTER TABLE `ordenes_servicio` ADD COLUMN IF NOT EXISTS `hoja_campo_notas` text DEFAULT NULL;

-- 5. CAMPOS PARA INFORMES DE CONTROL (CUMPLIMIENTO ISO/IEC 17025)
ALTER TABLE `informes_control` ADD COLUMN IF NOT EXISTS `ocultar_columna_cumplimiento` tinyint(1) NOT NULL DEFAULT 0;

-- 6. CAMPOS PARA HOJAS DE SOLICITUD (MÚLTIPLES HOJAS Y PARÁMETROS OPERATIVOS)
ALTER TABLE `hojas_solicitud` ADD COLUMN IF NOT EXISTS `condicion_muestreo_datos` text DEFAULT NULL;
ALTER TABLE `hojas_solicitud` ADD COLUMN IF NOT EXISTS `incluir_cumplimiento_pdf` tinyint(1) DEFAULT 0;
