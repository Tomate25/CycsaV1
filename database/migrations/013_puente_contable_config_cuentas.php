<?php
/**
 * Migración: 013_puente_contable_config_cuentas.php
 * TAREA-046: Fase 5 · Puente Contable Dinámico config_cuentas.
 *
 * Crea la tabla 'config_cuentas' para mapear claves mnemónicas del sistema
 * hacia cuentas contables sin atar IDs fijos en el código duro.
 * 
 * Sembrado inicial idempotente con 14 claves primarias y resolución
 * automática de id_cuenta contra cuentas_contables / cuentas.
 *
 * Ejecución CLI: php database/migrations/013_puente_contable_config_cuentas.php
 */

if (!isset($db) || !($db instanceof PDO)) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

echo "--- Iniciando Migración 013: Puente Contable Dinámico (config_cuentas) ---\n";

// 1. Crear tabla config_cuentas
$db->exec("
CREATE TABLE IF NOT EXISTS `config_cuentas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `clave` VARCHAR(50) NOT NULL UNIQUE,
    `codigo_cuenta` VARCHAR(50) NOT NULL,
    `descripcion` VARCHAR(255) NOT NULL,
    `id_cuenta` INT NULL,
    `modulo` VARCHAR(50) DEFAULT 'general',
    `activo` TINYINT(1) DEFAULT 1,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_cc_clave` (`clave`),
    INDEX `idx_cc_codigo` (`codigo_cuenta`),
    INDEX `idx_cc_modulo` (`modulo`),
    INDEX `idx_cc_id_cuenta` (`id_cuenta`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla 'config_cuentas' verificada.\n";

// 2. Sembrado inicial idempotente de 14 claves mnemónicas primarias
$clavesSemilla = [
    ['clave' => 'CAJA_PRINCIPAL',     'codigo' => '1010101', 'desc' => 'Caja Principal',                      'modulo' => 'caja'],
    ['clave' => 'CAJA_CHICA',         'codigo' => '1010102', 'desc' => 'Caja Chica',                          'modulo' => 'caja'],
    ['clave' => 'CLIENTES',           'codigo' => '1010201', 'desc' => 'Clientes Nacionales',                 'modulo' => 'cxc'],
    ['clave' => 'ANTICIPO_PROVEEDORES','codigo' => '1010501', 'desc' => 'Anticipo a Proveedores',             'modulo' => 'cxp'],
    ['clave' => 'IVA_ACREDITABLE',    'codigo' => '1010601', 'desc' => 'IVA 15% Acreditable',                'modulo' => 'impuestos'],
    ['clave' => 'INVENTARIO_LAB',     'codigo' => '1010701', 'desc' => 'Inventario Laboratorio',              'modulo' => 'inventario'],
    ['clave' => 'PROVEEDORES',        'codigo' => '2010101', 'desc' => 'Cuentas por Pagar Proveedores',       'modulo' => 'cxp'],
    ['clave' => 'ANTICIPO_CLIENTES',  'codigo' => '2010303', 'desc' => 'Anticipo Clientes',                  'modulo' => 'cxc'],
    ['clave' => 'IR_FUENTE_2',        'codigo' => '2010502', 'desc' => 'Retención IR Fuente 2%',             'modulo' => 'impuestos'],
    ['clave' => 'IVA_POR_PAGAR',      'codigo' => '2010508', 'desc' => 'IVA 15% por Pagar',                 'modulo' => 'impuestos'],
    ['clave' => 'INGRESO_LAB_G',      'codigo' => '4010106', 'desc' => 'Consultorías y Laboratorio Gravado',  'modulo' => 'operaciones'],
    ['clave' => 'INGRESO_LAB_E',      'codigo' => '4010107', 'desc' => 'Consultorías y Laboratorio Exonerado','modulo' => 'operaciones'],
    ['clave' => 'COSTO_MAT_LAB',      'codigo' => '5010308', 'desc' => 'Materiales y Suministros de Laboratorio','modulo' => 'costos'],
    ['clave' => 'COSTO_CALIBRACION',  'codigo' => '5010303', 'desc' => 'Calibraciones, Acreditación y Permisos','modulo' => 'costos'],
];

$stmtInsert = $db->prepare("
    INSERT IGNORE INTO `config_cuentas` (`clave`, `codigo_cuenta`, `descripcion`, `modulo`)
    VALUES (:clave, :codigo, :desc, :modulo)
");

foreach ($clavesSemilla as $c) {
    $stmtInsert->execute([
        'clave'  => $c['clave'],
        'codigo' => $c['codigo'],
        'desc'   => $c['desc'],
        'modulo' => $c['modulo'],
    ]);
}
echo "[OK] Sembrado de 14 claves mnemónicas completado.\n";

// 3. Actualizar id_cuenta resolviendo contra la tabla 'cuentas' o 'cuentas_contables'
$tablasCuentas = ['cuentas_contables', 'cuentas'];
$actualizados = 0;

foreach ($tablasCuentas as $tabla) {
    try {
        $existe = $db->query("SHOW TABLES LIKE '{$tabla}'")->fetch();
        if ($existe) {
            $sql = "
                UPDATE `config_cuentas` cc
                INNER JOIN `{$tabla}` c ON cc.codigo_cuenta = c.codigo
                SET cc.id_cuenta = c.id
                WHERE cc.id_cuenta IS NULL
            ";
            $actualizados += $db->exec($sql);
        }
    } catch (Throwable $e) {
        echo "[INFO] Resolución en '{$tabla}': " . $e->getMessage() . "\n";
    }
}

echo "[OK] Resolución de id_cuenta finalizada ({$actualizados} registros actualizados).\n";
