<?php
/**
 * Migración: 017_rrhh_nomina_anticipos.php
 * TAREA-050: Fase 7 · Nómina, Anticipos a Empleados y Provisiones Laborales.
 *
 * Crea las tablas:
 * - rrhh_departamentos (con sembrado base de LAB, ADM, VTAS)
 * - rrhh_empleados
 * - rrhh_anticipos
 * - rrhh_nominas
 * - rrhh_nomina_detalles
 *
 * Incorpora claves en config_cuentas para puente contable automático.
 *
 * Ejecución CLI: php database/migrations/017_rrhh_nomina_anticipos.php
 */

if (!isset($db) || !($db instanceof PDO)) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

echo "--- Iniciando Migración 017: Nómina, Anticipos y Provisiones Laborales (TAREA-050) ---\n";

// 1. Tabla rrhh_departamentos
$db->exec("
CREATE TABLE IF NOT EXISTS `rrhh_departamentos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(20) UNIQUE NOT NULL,
    `nombre` VARCHAR(100) NOT NULL,
    `cuenta_gasto_id` INT NULL,
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla 'rrhh_departamentos' verificada.\n";

// 2. Sembrado inicial de departamentos base resolviendo cuenta_gasto_id
$cuentaLab = null;
$cuentaAdm = null;
try {
    $tablasCuentas = ['cuentas_contables', 'cuentas'];
    foreach ($tablasCuentas as $tc) {
        $check = $db->query("SHOW TABLES LIKE '{$tc}'")->fetch();
        if ($check) {
            $stmtLab = $db->query("SELECT id FROM `{$tc}` WHERE `codigo` = '5010201' LIMIT 1");
            $cLab = $stmtLab->fetchColumn();
            if ($cLab) {
                $cuentaLab = (int)$cLab;
            }
            $stmtAdm = $db->query("SELECT id FROM `{$tc}` WHERE `codigo` = '6010101' LIMIT 1");
            $cAdm = $stmtAdm->fetchColumn();
            if ($cAdm) {
                $cuentaAdm = (int)$cAdm;
            }
            if ($cuentaLab && $cuentaAdm) {
                break;
            }
        }
    }
} catch (Throwable $e) {
    echo "[INFO] Fallo resolviendo cuentas contables depto: " . $e->getMessage() . "\n";
}

$deptosBase = [
    ['codigo' => 'LAB',  'nombre' => 'LABORATORIO',    'cuenta_gasto_id' => $cuentaLab],
    ['codigo' => 'ADM',  'nombre' => 'ADMINISTRACION',  'cuenta_gasto_id' => $cuentaAdm],
    ['codigo' => 'VTAS', 'nombre' => 'VENTAS',          'cuenta_gasto_id' => $cuentaAdm],
];

$stmtDepto = $db->prepare("
    INSERT IGNORE INTO `rrhh_departamentos` (`codigo`, `nombre`, `cuenta_gasto_id`)
    VALUES (:codigo, :nombre, :cuenta_gasto_id)
");

foreach ($deptosBase as $d) {
    $stmtDepto->execute([
        'codigo' => $d['codigo'],
        'nombre' => $d['nombre'],
        'cuenta_gasto_id' => $d['cuenta_gasto_id']
    ]);
}
echo "[OK] Sembrado de departamentos base completado.\n";

// 3. Tabla rrhh_empleados
$db->exec("
CREATE TABLE IF NOT EXISTS `rrhh_empleados` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(50) UNIQUE NOT NULL,
    `nombre` VARCHAR(255) NOT NULL,
    `cedula` VARCHAR(50) UNIQUE NULL,
    `departamento_id` INT NOT NULL,
    `puesto` VARCHAR(100) NOT NULL,
    `salario_base` DECIMAL(12,2) NOT NULL DEFAULT 0.00,
    `cuenta_anticipo_id` INT NULL,
    `fecha_ingreso` DATE NOT NULL,
    `estado` ENUM('activo','inactivo','liquidado') DEFAULT 'activo',
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_emp_depto` (`departamento_id`),
    INDEX `idx_emp_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla 'rrhh_empleados' verificada.\n";

// 4. Tabla rrhh_anticipos
$db->exec("
CREATE TABLE IF NOT EXISTS `rrhh_anticipos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `empleado_id` INT NOT NULL,
    `monto` DECIMAL(12,2) NOT NULL,
    `saldo` DECIMAL(12,2) NOT NULL,
    `fecha` DATE NOT NULL,
    `motivo` VARCHAR(255) NULL,
    `estado` ENUM('pendiente','descontado_parcial','descontado_total','anulado') DEFAULT 'pendiente',
    `id_asiento` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_ant_emp` (`empleado_id`),
    INDEX `idx_ant_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla 'rrhh_anticipos' verificada.\n";

// 5. Tabla rrhh_nominas
$db->exec("
CREATE TABLE IF NOT EXISTS `rrhh_nominas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(50) UNIQUE NOT NULL,
    `periodo_inicio` DATE NOT NULL,
    `periodo_fin` DATE NOT NULL,
    `total_devengado` DECIMAL(14,2) DEFAULT 0.00,
    `total_deducciones` DECIMAL(14,2) DEFAULT 0.00,
    `total_neto` DECIMAL(14,2) DEFAULT 0.00,
    `estado` ENUM('borrador','procesada','pagada','anulada') DEFAULT 'borrador',
    `id_asiento` INT NULL,
    `procesada_por` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla 'rrhh_nominas' verificada.\n";

// 6. Tabla rrhh_nomina_detalles
$db->exec("
CREATE TABLE IF NOT EXISTS `rrhh_nomina_detalles` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `nomina_id` INT NOT NULL,
    `empleado_id` INT NOT NULL,
    `salario_base` DECIMAL(12,2) NOT NULL,
    `horas_extras_monto` DECIMAL(12,2) DEFAULT 0.00,
    `total_devengado` DECIMAL(12,2) NOT NULL,
    `inss_laboral` DECIMAL(12,2) DEFAULT 0.00,
    `ir_laboral` DECIMAL(12,2) DEFAULT 0.00,
    `anticipos_descontados` DECIMAL(12,2) DEFAULT 0.00,
    `total_deducciones` DECIMAL(12,2) NOT NULL,
    `neto_a_pagar` DECIMAL(12,2) NOT NULL,
    `inss_patronal` DECIMAL(12,2) DEFAULT 0.00,
    `inatec_patronal` DECIMAL(12,2) DEFAULT 0.00,
    `vacaciones_provision` DECIMAL(12,2) DEFAULT 0.00,
    `aguinaldo_provision` DECIMAL(12,2) DEFAULT 0.00,
    `indemnizacion_provision` DECIMAL(12,2) DEFAULT 0.00,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_nomd_nom` (`nomina_id`),
    INDEX `idx_nomd_emp` (`empleado_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla 'rrhh_nomina_detalles' verificada.\n";

// 7. Enriquecer config_cuentas con claves para Nómina
try {
    $checkCfg = $db->query("SHOW TABLES LIKE 'config_cuentas'")->fetch();
    if ($checkCfg) {
        $clavesNomina = [
            ['clave' => 'ANTICIPO_EMPLEADOS',    'codigo' => '1010310', 'desc' => 'Adelantos de Salario a Empleados', 'modulo' => 'nomina'],
            ['clave' => 'INSS_LABORAL_PAGAR',    'codigo' => '2010504', 'desc' => 'INSS Cuota Laboral por Pagar',     'modulo' => 'nomina'],
            ['clave' => 'IR_SALARIOS_PAGAR',     'codigo' => '2010506', 'desc' => 'IR sobre Salarios por Pagar',       'modulo' => 'nomina'],
            ['clave' => 'SUELDOS_POR_PAGAR',     'codigo' => '2010806', 'desc' => 'Sueldos por Pagar',                'modulo' => 'nomina'],
            ['clave' => 'INSS_PATRONAL_PAGAR',   'codigo' => '2010804', 'desc' => 'INSS Cuota Patronal por Pagar',    'modulo' => 'nomina'],
            ['clave' => 'INATEC_PAGAR',          'codigo' => '2010805', 'desc' => 'INATEC por Pagar',                 'modulo' => 'nomina'],
            ['clave' => 'PROV_VACACIONES',       'codigo' => '2010801', 'desc' => 'Provisión Vacaciones por Pagar',   'modulo' => 'nomina'],
            ['clave' => 'PROV_AGUINALDO',        'codigo' => '2010802', 'desc' => 'Provisión Treceavo Mes por Pagar', 'modulo' => 'nomina'],
            ['clave' => 'PROV_INDEMNIZACION',    'codigo' => '2010803', 'desc' => 'Provisión Indemnización por Pagar','modulo' => 'nomina'],
            ['clave' => 'GASTO_SUELDOS_LAB',     'codigo' => '5010201', 'desc' => 'Mano de Obra Directa Lab',         'modulo' => 'nomina'],
            ['clave' => 'GASTO_SUELDOS_ADM',     'codigo' => '6010101', 'desc' => 'Sueldos Administración',           'modulo' => 'nomina'],
            ['clave' => 'GASTO_INSS_PAT_LAB',    'codigo' => '5010206', 'desc' => 'INSS Patronal Mano de Obra Lab',   'modulo' => 'nomina'],
            ['clave' => 'GASTO_INSS_PAT_ADM',    'codigo' => '6010106', 'desc' => 'INSS Patronal Administración',     'modulo' => 'nomina'],
            ['clave' => 'GASTO_INATEC_LAB',      'codigo' => '5010207', 'desc' => 'INATEC Laboratorio',               'modulo' => 'nomina'],
            ['clave' => 'GASTO_INATEC_ADM',      'codigo' => '6010107', 'desc' => 'INATEC Administración',            'modulo' => 'nomina'],
            ['clave' => 'GASTO_VACACIONES_LAB',  'codigo' => '5010203', 'desc' => 'Gasto Vacaciones Laboratorio',     'modulo' => 'nomina'],
            ['clave' => 'GASTO_VACACIONES_ADM',  'codigo' => '6010103', 'desc' => 'Gasto Vacaciones Administración',  'modulo' => 'nomina'],
            ['clave' => 'GASTO_AGUINALDO_LAB',   'codigo' => '5010204', 'desc' => 'Gasto Aguinaldo Laboratorio',      'modulo' => 'nomina'],
            ['clave' => 'GASTO_AGUINALDO_ADM',   'codigo' => '6010104', 'desc' => 'Gasto Aguinaldo Administración',   'modulo' => 'nomina'],
            ['clave' => 'GASTO_INDEMNIZACION_LAB','codigo' => '5010205', 'desc' => 'Gasto Indemnización Laboratorio',  'modulo' => 'nomina'],
            ['clave' => 'GASTO_INDEMNIZACION_ADM','codigo' => '6010105', 'desc' => 'Gasto Indemnización Administración','modulo' => 'nomina'],
        ];

        $insCfg = $db->prepare("
            INSERT IGNORE INTO `config_cuentas` (`clave`, `codigo_cuenta`, `descripcion`, `modulo`)
            VALUES (:clave, :codigo, :desc, :modulo)
        ");

        foreach ($clavesNomina as $cn) {
            $insCfg->execute([
                'clave'  => $cn['clave'],
                'codigo' => $cn['codigo'],
                'desc'   => $cn['desc'],
                'modulo' => $cn['modulo']
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
                    WHERE cc.id_cuenta IS NULL AND cc.modulo = 'nomina'
                ");
            }
        }
        echo "[OK] Claves de Nómina vinculadas en config_cuentas.\n";
    }
} catch (Throwable $e) {
    echo "[INFO] Fallo vinculando claves en config_cuentas: " . $e->getMessage() . "\n";
}

echo "--- Migración 017 Finalizada con Éxito ---\n";
