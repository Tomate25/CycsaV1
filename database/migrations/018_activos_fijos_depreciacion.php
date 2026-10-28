<?php
/**
 * Migración: 018_activos_fijos_depreciacion.php
 * TAREA-051: Fase 8 · Activos Fijos UI y Depreciación Mensual Automatizada.
 *
 * Crea las tablas:
 * - activos_fijos
 * - activos_fijos_depreciaciones
 *
 * Incorpora claves en config_cuentas para puente contable automático.
 *
 * Ejecución CLI: php database/migrations/018_activos_fijos_depreciacion.php
 */

if (!isset($db) || !($db instanceof PDO)) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

echo "--- Iniciando Migración 018: Activos Fijos y Depreciación Mensual (TAREA-051) ---\n";

// 1. Tabla activos_fijos
$db->exec("
CREATE TABLE IF NOT EXISTS `activos_fijos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(50) UNIQUE NOT NULL,
    `descripcion` VARCHAR(255) NOT NULL,
    `categoria` ENUM('edificios','vehiculos','maquinaria_equipo','muebles_enseres','equipo_computo') DEFAULT 'maquinaria_equipo',
    `fecha_adquisicion` DATE NOT NULL,
    `valor_adquisicion` DECIMAL(14,2) NOT NULL,
    `valor_residual` DECIMAL(14,2) DEFAULT 0.00,
    `vida_util_meses` INT NOT NULL,
    `depreciacion_acumulada` DECIMAL(14,2) DEFAULT 0.00,
    `cuenta_activo_id` INT NULL,
    `cuenta_depreciacion_acum_id` INT NULL,
    `cuenta_gasto_depreciacion_id` INT NULL,
    `estado` ENUM('activo','depreciado','en_mantenimiento','de_baja') DEFAULT 'activo',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_af_estado` (`estado`),
    INDEX `idx_af_categoria` (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla 'activos_fijos' verificada.\n";

// 2. Tabla activos_fijos_depreciaciones
$db->exec("
CREATE TABLE IF NOT EXISTS `activos_fijos_depreciaciones` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `activo_fijo_id` INT NOT NULL,
    `periodo` VARCHAR(7) NOT NULL,
    `monto_depreciacion` DECIMAL(14,2) NOT NULL,
    `depreciacion_acumulada_nueva` DECIMAL(14,2) NOT NULL,
    `valor_en_libros` DECIMAL(14,2) NOT NULL,
    `id_asiento` INT NULL,
    `fecha_proceso` DATE NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_afd_activo` (`activo_fijo_id`),
    INDEX `idx_afd_periodo` (`periodo`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla 'activos_fijos_depreciaciones' verificada.\n";

// 3. Sembrado de claves en config_cuentas
try {
    $checkCfg = $db->query("SHOW TABLES LIKE 'config_cuentas'")->fetch();
    if ($checkCfg) {
        $clavesActivos = [
            ['clave' => 'ACTIVOS_MAQUINARIA',   'codigo' => '1020601', 'desc' => 'Equipo de Laboratorio y Maquinaria',    'modulo' => 'activos'],
            ['clave' => 'DEP_ACUM_MAQUINARIA',  'codigo' => '1020905', 'desc' => 'Depreciación Acumulada Maquinaria/Lab', 'modulo' => 'activos'],
            ['clave' => 'GASTO_DEP_MAQUINARIA', 'codigo' => '5010306', 'desc' => 'Gasto Depreciación Maquinaria/Lab',     'modulo' => 'activos'],
            ['clave' => 'ACTIVOS_EDIFICIOS',    'codigo' => '1020201', 'desc' => 'Edificios',                             'modulo' => 'activos'],
            ['clave' => 'DEP_ACUM_EDIFICIOS',   'codigo' => '1020901', 'desc' => 'Depreciación Acumulada Edificios',      'modulo' => 'activos'],
            ['clave' => 'GASTO_DEP_EDIFICIOS',  'codigo' => '6010408', 'desc' => 'Depreciación Edificios',                 'modulo' => 'activos'],
            ['clave' => 'ACTIVOS_VEHICULOS',    'codigo' => '1020501', 'desc' => 'Vehículos',                             'modulo' => 'activos'],
            ['clave' => 'DEP_ACUM_VEHICULOS',   'codigo' => '1020904', 'desc' => 'Depreciación Acumulada Vehículos',      'modulo' => 'activos'],
            ['clave' => 'GASTO_DEP_VEHICULOS',  'codigo' => '6010207', 'desc' => 'Depreciación Vehículos',                 'modulo' => 'activos'],
            ['clave' => 'ACTIVOS_MUEBLES',      'codigo' => '1020301', 'desc' => 'Mobiliario y Equipo de Oficina',         'modulo' => 'activos'],
            ['clave' => 'DEP_ACUM_MUEBLES',     'codigo' => '1020902', 'desc' => 'Depreciación Acumulada Mobiliario',     'modulo' => 'activos'],
            ['clave' => 'GASTO_DEP_MUEBLES',    'codigo' => '6010404', 'desc' => 'Depreciación Mobiliario y Equipos',     'modulo' => 'activos'],
            ['clave' => 'ACTIVOS_COMPUTO',      'codigo' => '1020401', 'desc' => 'Equipo de Cómputo',                     'modulo' => 'activos'],
            ['clave' => 'DEP_ACUM_COMPUTO',     'codigo' => '1020903', 'desc' => 'Depreciación Acumulada Equipo Cómputo', 'modulo' => 'activos'],
            ['clave' => 'GASTO_DEP_COMPUTO',    'codigo' => '6010406', 'desc' => 'Depreciación Equipo de Cómputo',         'modulo' => 'activos'],
        ];

        $insCfg = $db->prepare("
            INSERT IGNORE INTO `config_cuentas` (`clave`, `codigo_cuenta`, `descripcion`, `modulo`)
            VALUES (:clave, :codigo, :desc, :modulo)
        ");

        foreach ($clavesActivos as $ca) {
            $insCfg->execute([
                'clave'  => $ca['clave'],
                'codigo' => $ca['codigo'],
                'desc'   => $ca['desc'],
                'modulo' => $ca['modulo']
            ]);
        }

        // Resolver id_cuenta en config_cuentas
        foreach (['cuentas_contables', 'cuentas'] as $tc) {
            $checkTc = $db->query("SHOW TABLES LIKE '{$tc}'")->fetch();
            if ($checkTc) {
                $db->exec("
                    UPDATE `config_cuentas` cc
                    INNER JOIN `{$tc}` c ON cc.codigo_cuenta = c.codigo
                    SET cc.id_cuenta = c.id
                    WHERE cc.id_cuenta IS NULL AND cc.modulo = 'activos'
                ");
            }
        }
        echo "[OK] Claves de Activos Fijos vinculadas en config_cuentas.\n";
    }
} catch (Throwable $e) {
    echo "[INFO] Fallo vinculando claves en config_cuentas: " . $e->getMessage() . "\n";
}

echo "--- Migración 018 Finalizada con Éxito ---\n";
