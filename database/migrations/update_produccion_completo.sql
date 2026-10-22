-- ==============================================================================
-- ACTUALIZACIÓN INCREMENTAL DE BASE DE DATOS - CYCSA ERP / LIMS
-- Base de datos objetivo: cycsanic_cycsa_db (Bluehost cPanel / phpMyAdmin)
-- Incluye: Módulo de Plantillas Dinámicas (ISO/IEC 17025), Bandeja Anónima,
--          Programa de Roturas Periódicas y Módulo de Equipos/Calibración.
-- ==============================================================================

-- 1. TABLA UNIDADES DE MEDIDA
CREATE TABLE IF NOT EXISTS `unidades_medida` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `nombre` VARCHAR(100) NOT NULL,
  `simbolo` VARCHAR(25) NOT NULL,
  `magnitud` VARCHAR(50) DEFAULT 'General',
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 2. TABLA VARIABLES DINÁMICAS
CREATE TABLE IF NOT EXISTS `variables` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(60) NOT NULL UNIQUE,
  `etiqueta` VARCHAR(150) NOT NULL,
  `tipo` ENUM('numero','texto','fecha','lista','calculado','tabla') NOT NULL DEFAULT 'numero',
  `unidad_id` INT NULL,
  `decimales` TINYINT DEFAULT 2,
  `minimo` DECIMAL(12,4) NULL,
  `maximo` DECIMAL(12,4) NULL,
  `opciones_lista` JSON NULL,
  `requerido` TINYINT(1) DEFAULT 1,
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  CONSTRAINT `fk_var_unidad` FOREIGN KEY (`unidad_id`) REFERENCES `unidades_medida` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 3. TABLA PLANTILLAS
CREATE TABLE IF NOT EXISTS `plantillas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(80) NOT NULL,
  `nombre` VARCHAR(200) NOT NULL,
  `ensayo_id` INT NOT NULL,
  `norma` VARCHAR(150) NOT NULL,
  `procedimiento_interno` VARCHAR(150) NULL,
  `orientacion` ENUM('vertical','horizontal') DEFAULT 'horizontal',
  `fondo_reporte_id` VARCHAR(100) DEFAULT 'hoja_membretada_horizontal.jpg',
  `incluir_logo_cycsa` TINYINT(1) DEFAULT 1,
  `incluir_sello_acreditacion` TINYINT(1) DEFAULT 1,
  `sello_acreditacion_url` VARCHAR(255) DEFAULT 'publico/img/sello_acreditacion_default.jpeg',
  `cliente_id` INT NULL,
  `plantilla_padre_id` INT NULL,
  `estado` ENUM('borrador','en_revision','aprobada','obsoleta') DEFAULT 'borrador',
  `version_actual` VARCHAR(20) DEFAULT 'Rev. 01',
  `es_por_defecto` TINYINT(1) DEFAULT 0,
  `creado_por` INT NOT NULL,
  `aprobado_por` INT NULL,
  `fecha_aprobacion` DATETIME NULL,
  `notas_version` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_plt_ensayo` FOREIGN KEY (`ensayo_id`) REFERENCES `productos` (`id`),
  CONSTRAINT `fk_plt_cliente` FOREIGN KEY (`cliente_id`) REFERENCES `clientes` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plt_padre` FOREIGN KEY (`plantilla_padre_id`) REFERENCES `plantillas` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_plt_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_plt_aprobador` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 4. TABLA PLANTILLA SECCIONES
CREATE TABLE IF NOT EXISTS `plantilla_secciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plantilla_id` INT NOT NULL,
  `nombre` VARCHAR(100) NOT NULL,
  `orden` INT DEFAULT 1,
  `tipo_layout` ENUM('formulario','tabla_matriz') DEFAULT 'formulario',
  CONSTRAINT `fk_sec_plantilla` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 5. TABLA PLANTILLA CAMPOS
CREATE TABLE IF NOT EXISTS `plantilla_campos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plantilla_id` INT NOT NULL,
  `seccion_id` INT NOT NULL,
  `variable_id` INT NOT NULL,
  `orden` INT DEFAULT 1,
  `fila` INT DEFAULT 1,
  `columna` INT DEFAULT 1,
  `ancho_cols` TINYINT DEFAULT 4,
  `formula` TEXT NULL,
  `visible_en_reporte` TINYINT(1) DEFAULT 1,
  `solo_lectura` TINYINT(1) DEFAULT 0,
  `texto_ayuda` VARCHAR(255) NULL,
  CONSTRAINT `fk_cmp_plantilla` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cmp_seccion` FOREIGN KEY (`seccion_id`) REFERENCES `plantilla_secciones` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_cmp_variable` FOREIGN KEY (`variable_id`) REFERENCES `variables` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 6. TABLA PLANTILLA VERSIONES (INMUTABILIDAD ISO/IEC 17025)
CREATE TABLE IF NOT EXISTS `plantilla_versiones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plantilla_id` INT NOT NULL,
  `version` VARCHAR(20) NOT NULL,
  `esquema_json` LONGTEXT NOT NULL,
  `formato_legacy_json` LONGTEXT NOT NULL,
  `hash_integridad` VARCHAR(64) NOT NULL,
  `aprobado_por` INT NOT NULL,
  `fecha_aprobacion` DATETIME NOT NULL,
  `motivo_version` TEXT NULL,
  CONSTRAINT `fk_ver_plantilla` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_ver_aprobador` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 7. TABLA HOJAS DE SOLICITUD DETALLES (DETALLE DE MUESTRAS RT-FM-13)
CREATE TABLE IF NOT EXISTS `hojas_solicitud_detalles` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_hoja_solicitud` INT NOT NULL,
  `orden` INT DEFAULT 1,
  `codigo_muestra` VARCHAR(50) NOT NULL,
  `descripcion_ubicacion` VARCHAR(255) NOT NULL,
  `observacion_edad` VARCHAR(255) NULL,
  `ensayo_id` INT NOT NULL,
  `plantilla_id` INT NOT NULL,
  `plantilla_version_id` INT NULL,
  `seleccion_manual` TINYINT(1) DEFAULT 0,
  `motivo_cambio` TEXT NULL,
  `bloqueado_recepcion` TINYINT(1) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  CONSTRAINT `fk_hsd_hoja` FOREIGN KEY (`id_hoja_solicitud`) REFERENCES `hojas_solicitud` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_hsd_ensayo` FOREIGN KEY (`ensayo_id`) REFERENCES `productos` (`id`),
  CONSTRAINT `fk_hsd_plantilla` FOREIGN KEY (`plantilla_id`) REFERENCES `plantillas` (`id`),
  CONSTRAINT `fk_hsd_version` FOREIGN KEY (`plantilla_version_id`) REFERENCES `plantilla_versiones` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 8. TABLA ESPECIMENES (BANDEJA ANÓNIMA DE LABORATORIO)
CREATE TABLE IF NOT EXISTS `especimenes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `muestra_id` INT NOT NULL,
  `codigo_lab` VARCHAR(60) NOT NULL UNIQUE,
  `tipo` VARCHAR(50) DEFAULT 'cilindro_concreto',
  `dimensiones` VARCHAR(100) DEFAULT '15 x 30 cm',
  `fecha_moldeo` DATE NOT NULL,
  `estado` ENUM('en_curado', 'programado', 'ensayado', 'testigo', 'descartado') DEFAULT 'en_curado',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_esp_muestra` (`muestra_id`),
  INDEX `idx_esp_estado` (`estado`),
  CONSTRAINT `fk_esp_muestra` FOREIGN KEY (`muestra_id`) REFERENCES `recepcion_muestras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 9. TABLA PLANES DE ROTURA
CREATE TABLE IF NOT EXISTS `planes_rotura` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `muestra_id` INT NOT NULL,
  `plantilla_version_id` INT NULL,
  `estado` ENUM('borrador', 'activo', 'cerrado') DEFAULT 'borrador',
  `creado_por` INT NOT NULL,
  `aprobado_por` INT NULL,
  `fecha_aprobacion` DATETIME NULL,
  `motivo_modificacion` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_plan_muestra` (`muestra_id`),
  INDEX `idx_plan_estado` (`estado`),
  CONSTRAINT `fk_plan_muestra` FOREIGN KEY (`muestra_id`) REFERENCES `recepcion_muestras` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_plan_version` FOREIGN KEY (`plantilla_version_id`) REFERENCES `plantilla_versiones` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_plan_creador` FOREIGN KEY (`creado_por`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_plan_aprobador` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 10. TABLA PLAN ROTURA EDADES
CREATE TABLE IF NOT EXISTS `plan_rotura_edades` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plan_rotura_id` INT NOT NULL,
  `edad_dias` INT NOT NULL,
  `fecha_programada` DATE NOT NULL,
  `cantidad_especimenes` INT NOT NULL DEFAULT 1,
  `porcentaje_esperado` DECIMAL(5,2) NULL,
  `estado` ENUM('pendiente', 'ejecutado', 'vencido', 'omitido') DEFAULT 'pendiente',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pre_plan` (`plan_rotura_id`),
  INDEX `idx_pre_fecha` (`fecha_programada`),
  CONSTRAINT `fk_pre_plan` FOREIGN KEY (`plan_rotura_id`) REFERENCES `planes_rotura` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 11. TABLA PLAN ROTURA ESPECIMENES
CREATE TABLE IF NOT EXISTS `plan_rotura_especimenes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plan_edad_id` INT NOT NULL,
  `especimen_id` INT NOT NULL,
  `rotura_id` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_edad_esp` (`plan_edad_id`, `especimen_id`),
  CONSTRAINT `fk_pre_esp_edad` FOREIGN KEY (`plan_edad_id`) REFERENCES `plan_rotura_edades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pre_esp_esp` FOREIGN KEY (`especimen_id`) REFERENCES `especimenes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 12. TABLA CATÁLOGO DE FALLAS
CREATE TABLE IF NOT EXISTS `roturas_fallas_catalogo` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(20) NOT NULL UNIQUE,
  `nombre` VARCHAR(100) NOT NULL,
  `norma` VARCHAR(50) DEFAULT 'ASTM C39',
  `activo` TINYINT(1) DEFAULT 1
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 13. TABLA EQUIPOS DE CALIBRACIÓN
CREATE TABLE IF NOT EXISTS `equipos_calibracion` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(80) NOT NULL UNIQUE,
  `nombre` VARCHAR(200) NOT NULL,
  `categoria` VARCHAR(80) NOT NULL DEFAULT 'general',
  `marca_modelo` VARCHAR(150) NULL,
  `norma` VARCHAR(150) NULL,
  `usado_en_json` JSON NULL,
  `metadatos_json` LONGTEXT NULL,
  `vigente_desde` DATE NULL,
  `vigente_hasta` DATE NULL,
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_eqcal_cat` (`categoria`),
  INDEX `idx_eqcal_activo` (`activo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 14. TABLA EQUIPOS CALIBRACIÓN CONSTANTES
CREATE TABLE IF NOT EXISTS `equipos_calibracion_constantes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `equipo_id` INT NOT NULL,
  `clave` VARCHAR(80) NOT NULL,
  `ref_equipo_alias` VARCHAR(160) NULL,
  `valor_numerico` DECIMAL(18,8) NULL,
  `valor_json` LONGTEXT NULL,
  `unidad` VARCHAR(40) NULL,
  `descripcion` VARCHAR(255) NULL,
  `vigente` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_eqconst_equipo` (`equipo_id`),
  INDEX `idx_eqconst_alias` (`ref_equipo_alias`),
  CONSTRAINT `fk_eqconst_equipo` FOREIGN KEY (`equipo_id`) REFERENCES `equipos_calibracion` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 15. TABLA PICNÓMETROS
CREATE TABLE IF NOT EXISTS `equipos_calibracion_picnometros` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `identificacion` VARCHAR(60) NOT NULL UNIQUE,
  `volumen_calibrado_cm3` DECIMAL(12,4) NOT NULL DEFAULT 500.0000,
  `masa_picnometro_vacio_g` DECIMAL(12,4) NOT NULL,
  `coef_expansion_termica` DECIMAL(14,8) NOT NULL DEFAULT 0.00002500,
  `tabla_temperatura_json` LONGTEXT NOT NULL,
  `fecha_calibracion` DATE NULL,
  `activo` TINYINT(1) DEFAULT 1,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 16. TABLA CONFIGURACIÓN CORRECCIONES HC
CREATE TABLE IF NOT EXISTS `configuracion_correcciones_hc` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo_formato` VARCHAR(30) NOT NULL,
  `correccion_id` VARCHAR(40) NOT NULL,
  `original_excel` TEXT NULL,
  `corregido` TEXT NULL,
  `justificacion` TEXT NULL,
  `es_dudosa` TINYINT(1) DEFAULT 0,
  `requiere_confirmacion_lab` TINYINT(1) DEFAULT 0,
  `usar_corregido` TINYINT(1) DEFAULT 1,
  `confirmado_calidad` TINYINT(1) DEFAULT 0,
  `confirmado_por` INT NULL,
  `fecha_confirmacion` DATETIME NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  UNIQUE KEY `uk_corr_fmt_id` (`codigo_formato`, `correccion_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 17. TABLA HOJAS DE CAMPO REGISTROS
CREATE TABLE IF NOT EXISTS `hojas_campo_registros` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `id_os` INT NOT NULL,
  `id_hoja_solicitud` INT NULL,
  `muestra_id` INT NULL,
  `proctor_muestra_id` INT NULL,
  `codigo_formato` VARCHAR(30) NOT NULL,
  `variante` VARCHAR(50) DEFAULT 'ESTANDAR',
  `datos_encabezado_json` LONGTEXT NULL,
  `datos_puntos_json` LONGTEXT NULL,
  `resultados_calculados_json` LONGTEXT NULL,
  `pvs_maximo_proctor` DECIMAL(12,4) NULL,
  `humedad_optima_proctor` DECIMAL(10,4) NULL,
  `creado_por` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_hcr_os` (`id_os`),
  INDEX `idx_hcr_formato` (`codigo_formato`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- 18. COLUMNAS ADITIVAS EN TABLAS EXISTENTES
ALTER TABLE `recepcion_muestras` ADD COLUMN IF NOT EXISTS `plantilla_id` INT NULL AFTER `codigo_campo`;
ALTER TABLE `recepcion_muestras` ADD COLUMN IF NOT EXISTS `plantilla_version_id` INT NULL AFTER `plantilla_id`;
ALTER TABLE `recepcion_muestras` ADD COLUMN IF NOT EXISTS `id_hoja` INT NULL AFTER `id_os`;
ALTER TABLE `roturas` ADD COLUMN IF NOT EXISTS `especimen_id` INT NULL;
ALTER TABLE `roturas` ADD COLUMN IF NOT EXISTS `plan_edad_id` INT NULL;
ALTER TABLE `roturas` ADD COLUMN IF NOT EXISTS `codigo_laboratorio_secuencial` VARCHAR(60) NULL;
ALTER TABLE `roturas` ADD COLUMN IF NOT EXISTS `falla_tipo_id` INT NULL;
ALTER TABLE `roturas` ADD COLUMN IF NOT EXISTS `densidad_calculada` DECIMAL(10,2) NULL;
ALTER TABLE `roturas` ADD COLUMN IF NOT EXISTS `resistencia_calculada_psi` DECIMAL(10,2) NULL;
ALTER TABLE `roturas` ADD COLUMN IF NOT EXISTS `resistencia_calculada_kgcm2` DECIMAL(10,2) NULL;
ALTER TABLE `roturas` ADD COLUMN IF NOT EXISTS `porcentaje_cumplimiento` DECIMAL(6,2) NULL;
