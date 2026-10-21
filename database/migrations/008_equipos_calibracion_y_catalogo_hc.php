<?php
/**
 * Migración: 008_equipos_calibracion_y_catalogo_hc.php
 * Fase 1: Módulo de Equipos y Calibración, Catálogo de Variables Comunes,
 * Parametrización de Correcciones ("dudosas") y Plantilla RT-FM-61 (Cilindros ASTM C39).
 *
 * Cambio 100% aditivo: no modifica ni elimina tablas existentes (activos_fijos,
 * mantenimiento_equipos, plantillas legacy RT-FM-22, etc.).
 *
 * Ejecución: php database/migrations/008_equipos_calibracion_y_catalogo_hc.php
 */

require dirname(__DIR__, 2) . '/vendor/autoload.php';

use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Operaciones\Servicios\SembradorPlantillasHcServicio;

$db = Conexion::obtenerInstancia();
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

echo "--- Iniciando Migración 008: Equipos y Calibración + Catálogo HC + RT-FM-61 ---\n";

// 1. Tabla equipos_calibracion
$db->exec("
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
");
echo "[OK] Tabla equipos_calibracion verificada.\n";

// 2. Tabla equipos_calibracion_constantes
$db->exec("
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
");
echo "[OK] Tabla equipos_calibracion_constantes verificada.\n";

// 3. Tabla equipos_calibracion_picnometros (RT-FM-56)
$db->exec("
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
");
echo "[OK] Tabla equipos_calibracion_picnometros verificada.\n";

// 4. Tabla configuracion_correcciones_hc (parametrización de correcciones dudosas por Calidad)
$db->exec("
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
");
echo "[OK] Tabla configuracion_correcciones_hc verificada.\n";

// 5. Tabla hojas_campo_registros (para soporte estructurado de RT-FM-54, RT-FM-55, RT-FM-62 con enlace a Proctor RT-FM-27)
$db->exec("
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
");
echo "[OK] Tabla hojas_campo_registros verificada.\n";

// 6. Sembrar catálogo de variables comunes, equipos de calibración y plantilla RT-FM-61
$sembrador = new SembradorPlantillasHcServicio($db);

$resVars = $sembrador->sembrarCatalogoVariablesComunes();
echo "[OK] Catálogo de variables comunes sembrado ({$resVars['variables_creadas']} variables nuevas, {$resVars['unidades_creadas']} unidades nuevas).\n";

$resEq = $sembrador->sembrarEquiposCalibracion();
echo "[OK] Equipos y calibración sembrados ({$resEq['equipos']} equipos, {$resEq['constantes']} constantes, {$resEq['picnometros']} picnómetros).\n";

$resCorr = $sembrador->sembrarCorreccionesFormatos();
echo "[OK] Correcciones parametrizables registradas ({$resCorr} nuevas).\n";

$resPlt = $sembrador->sembrarPlantillasFormatos(['RT-FM-61']);
echo "[OK] Plantilla RT-FM-61 sembrada exitosamente (ID: " . ($resPlt['RT-FM-61'] ?? 'N/A') . ").\n";

echo "--- Migración 008 finalizada con éxito ---\n";
