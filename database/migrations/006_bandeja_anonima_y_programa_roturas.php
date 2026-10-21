<?php
/**
 * Migración: 006_bandeja_anonima_y_programa_roturas.php
 * Crea las tablas relacionales para el Programa de Roturas Periódicas y la Vista Anónima
 * de Laboratorio bajo norma ISO/IEC 17025 (cláusulas 4.1 Imparcialidad y 4.2 Confidencialidad).
 *
 * Ejecución: php database/migrations/006_bandeja_anonima_y_programa_roturas.php
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';

$db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "--- Iniciando Migración 006: Bandeja Anónima y Programa de Roturas ISO 17025 ---\n";

// 1. Tabla especimenes
$db->exec("
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
");
echo "[OK] Tabla especimenes creada o existente.\n";

// 2. Tabla planes_rotura
$db->exec("
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
");
echo "[OK] Tabla planes_rotura creada o existente.\n";

// 3. Tabla plan_rotura_edades
$db->exec("
CREATE TABLE IF NOT EXISTS `plan_rotura_edades` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plan_rotura_id` INT NOT NULL,
  `edad_dias` INT NOT NULL,
  `cantidad` INT NOT NULL,
  `fecha_programada` DATE NOT NULL,
  `tolerancia_horas` INT DEFAULT 24,
  `estado` ENUM('pendiente', 'en_proceso', 'completado', 'cancelado') DEFAULT 'pendiente',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pre_plan` (`plan_rotura_id`),
  INDEX `idx_pre_fecha` (`fecha_programada`),
  INDEX `idx_pre_estado` (`estado`),
  CONSTRAINT `fk_pre_plan` FOREIGN KEY (`plan_rotura_id`) REFERENCES `planes_rotura` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla plan_rotura_edades creada o existente.\n";

// 4. Tabla plan_rotura_asignaciones
$db->exec("
CREATE TABLE IF NOT EXISTS `plan_rotura_asignaciones` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `plan_edad_id` INT NOT NULL,
  `especimen_id` INT NOT NULL UNIQUE,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_pra_edad` (`plan_edad_id`),
  INDEX `idx_pra_esp` (`especimen_id`),
  CONSTRAINT `fk_pra_edad` FOREIGN KEY (`plan_edad_id`) REFERENCES `plan_rotura_edades` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_pra_esp` FOREIGN KEY (`especimen_id`) REFERENCES `especimenes` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla plan_rotura_asignaciones creada o existente.\n";

// 5. Tabla roturas
$db->exec("
CREATE TABLE IF NOT EXISTS `roturas` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `especimen_id` INT NOT NULL,
  `plan_edad_id` INT NOT NULL,
  `fecha_real` DATETIME NOT NULL,
  `carga` DECIMAL(12,2) NOT NULL,
  `area` DECIMAL(10,4) NOT NULL,
  `esfuerzo_psi` DECIMAL(10,2) NOT NULL,
  `esfuerzo_kgcm2` DECIMAL(10,2) NOT NULL,
  `porcentaje_fc` DECIMAL(6,2) NULL,
  `tipo_falla` VARCHAR(50) DEFAULT 'Tipo 1',
  `tecnico_id` INT NOT NULL,
  `fuera_tolerancia` TINYINT(1) DEFAULT 0,
  `justificacion` TEXT NULL,
  `datos_crudos` JSON NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_rot_esp` (`especimen_id`),
  INDEX `idx_rot_edad` (`plan_edad_id`),
  CONSTRAINT `fk_rot_esp` FOREIGN KEY (`especimen_id`) REFERENCES `especimenes` (`id`),
  CONSTRAINT `fk_rot_edad` FOREIGN KEY (`plan_edad_id`) REFERENCES `plan_rotura_edades` (`id`),
  CONSTRAINT `fk_rot_tecnico` FOREIGN KEY (`tecnico_id`) REFERENCES `usuarios` (`id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla roturas creada o existente.\n";

// 6. Tabla informes_rotura
$db->exec("
CREATE TABLE IF NOT EXISTS `informes_rotura` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipo` ENUM('parcial', 'consolidado') NOT NULL DEFAULT 'parcial',
  `muestra_id` INT NOT NULL,
  `plan_edad_id` INT NULL,
  `numero` VARCHAR(50) NOT NULL,
  `version` INT DEFAULT 1,
  `estado` ENUM('borrador', 'en_revision', 'aprobado', 'devuelto') DEFAULT 'borrador',
  `motivo_devolucion` TEXT NULL,
  `pdf` VARCHAR(255) NULL,
  `aprobado_por` INT NULL,
  `fecha_aprobacion` DATETIME NULL,
  `observaciones` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_inf_muestra` (`muestra_id`),
  INDEX `idx_inf_edad` (`plan_edad_id`),
  INDEX `idx_inf_numero` (`numero`),
  CONSTRAINT `fk_inf_muestra` FOREIGN KEY (`muestra_id`) REFERENCES `recepcion_muestras` (`id`) ON DELETE CASCADE,
  CONSTRAINT `fk_inf_edad` FOREIGN KEY (`plan_edad_id`) REFERENCES `plan_rotura_edades` (`id`) ON DELETE SET NULL,
  CONSTRAINT `fk_inf_aprobador` FOREIGN KEY (`aprobado_por`) REFERENCES `usuarios` (`id`) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla informes_rotura creada o existente.\n";

// 7. Tabla bitacora_reidentificacion (registro especializado para auditoría ISO 17025)
$db->exec("
CREATE TABLE IF NOT EXISTS `bitacora_reidentificacion` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `usuario_id` INT NOT NULL,
  `usuario_nombre` VARCHAR(150) NOT NULL,
  `usuario_rol` VARCHAR(100) NOT NULL,
  `muestra_id` INT NOT NULL,
  `codigo_lab` VARCHAR(60) NOT NULL,
  `motivo` TEXT NOT NULL,
  `ip` VARCHAR(45) NOT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_reid_usuario` (`usuario_id`),
  INDEX `idx_reid_muestra` (`muestra_id`),
  INDEX `idx_reid_codigo` (`codigo_lab`),
  CONSTRAINT `fk_reid_usuario` FOREIGN KEY (`usuario_id`) REFERENCES `usuarios` (`id`),
  CONSTRAINT `fk_reid_muestra` FOREIGN KEY (`muestra_id`) REFERENCES `recepcion_muestras` (`id`) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla bitacora_reidentificacion creada o existente.\n";

// 8. Vista SQL v_lab_muestras (Anonimización Absoluta - Cero joins a clientes, proyectos, cotizaciones ni O/S)
$db->exec("
CREATE OR REPLACE VIEW `v_lab_muestras` AS
SELECT 
    rm.id AS muestra_id,
    rm.codigo_muestra AS codigo_lab,
    rm.codigo_campo,
    rm.tipo_muestra,
    rm.fecha_recepcion AS fecha_ingreso,
    rm.estado AS estado_recepcion,
    lm.id AS lote_id,
    lm.nombre_lote AS material_descripcion,
    lm.fecha_moldeo,
    lm.diseno_resistencia,
    lm.revenimiento_in,
    lm.temperatura_c,
    lm.procedimiento_muestreo,
    plt.id AS plantilla_id,
    plt.codigo AS plantilla_codigo,
    plt.nombre AS plantilla_nombre,
    plt.norma AS norma_referencia,
    pv.id AS plantilla_version_id,
    pv.version AS plantilla_version,
    (SELECT COUNT(*) FROM `especimenes` esp WHERE esp.muestra_id = rm.id) AS total_especimenes,
    (SELECT COUNT(*) FROM `especimenes` esp WHERE esp.muestra_id = rm.id AND esp.estado = 'ensayado') AS especimenes_ensayados,
    (SELECT MIN(pre.fecha_programada) 
     FROM `plan_rotura_edades` pre 
     JOIN `planes_rotura` pr ON pre.plan_rotura_id = pr.id 
     WHERE pr.muestra_id = rm.id AND pre.estado IN ('pendiente', 'en_proceso')) AS proxima_rotura,
    (SELECT pr.estado FROM `planes_rotura` pr WHERE pr.muestra_id = rm.id ORDER BY pr.id DESC LIMIT 1) AS estado_plan
FROM `recepcion_muestras` rm
JOIN `lotes_muestras` lm ON lm.id_recepcion = rm.id
LEFT JOIN `plantillas` plt ON rm.plantilla_id = plt.id
LEFT JOIN `plantilla_versiones` pv ON rm.plantilla_version_id = pv.id;
");
echo "[OK] Vista SQL v_lab_muestras creada exitosamente.\n";

// 9. Vista SQL v_lab_especimenes
$db->exec("
CREATE OR REPLACE VIEW `v_lab_especimenes` AS
SELECT 
    esp.id AS especimen_id,
    esp.muestra_id,
    esp.codigo_lab AS especimen_codigo_lab,
    esp.tipo AS especimen_tipo,
    esp.dimensiones AS especimen_dimensiones,
    esp.fecha_moldeo,
    esp.estado AS especimen_estado,
    pra.plan_edad_id,
    pre.edad_dias,
    pre.fecha_programada,
    pre.tolerancia_horas,
    rot.id AS rotura_id,
    rot.fecha_real AS rotura_fecha_real,
    rot.carga AS rotura_carga,
    rot.area AS rotura_area,
    rot.esfuerzo_psi AS rotura_esfuerzo_psi,
    rot.esfuerzo_kgcm2 AS rotura_esfuerzo_kgcm2,
    rot.porcentaje_fc AS rotura_porcentaje_fc,
    rot.tipo_falla AS rotura_tipo_falla,
    rot.fuera_tolerancia AS rotura_fuera_tolerancia,
    rot.justificacion AS rotura_justificacion
FROM `especimenes` esp
LEFT JOIN `plan_rotura_asignaciones` pra ON pra.especimen_id = esp.id
LEFT JOIN `plan_rotura_edades` pre ON pra.plan_edad_id = pre.id
LEFT JOIN `roturas` rot ON rot.especimen_id = esp.id;
");
echo "[OK] Vista SQL v_lab_especimenes creada exitosamente.\n";

echo "--- Migración 006 completada con éxito ---\n";
