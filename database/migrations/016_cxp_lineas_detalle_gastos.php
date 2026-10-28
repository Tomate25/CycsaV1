<?php
/**
 * Migración: 016_cxp_lineas_detalle_gastos.php
 * TAREA-049: Fase 6 · Gastos y CXP con Cuenta por Línea (Partidas Multi-Cuenta y Retenciones).
 *
 * Crea la tabla 'cuentas_por_pagar_detalle' para desglose de gastos multi-cuenta
 * e impuestos / retenciones en cuentas por pagar (CXP).
 *
 * Ejecución CLI: php database/migrations/016_cxp_lineas_detalle_gastos.php
 */

if (!isset($db) || !($db instanceof PDO)) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

echo "--- Iniciando Migración 016: Detalle de Cuentas por Pagar (Gastos Multi-Línea y Retenciones) ---\n";

// 1. Crear tabla cuentas_por_pagar_detalle
$sql = "CREATE TABLE IF NOT EXISTS `cuentas_por_pagar_detalle` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `cxp_id` INT NOT NULL,
    `cuenta_contable_id` INT NOT NULL,
    `concepto` VARCHAR(255) NOT NULL,
    `monto` DECIMAL(14,2) NOT NULL,
    `iva` DECIMAL(14,2) DEFAULT 0.00,
    `retencion_ir` DECIMAL(14,2) DEFAULT 0.00,
    `retencion_alcaldia` DECIMAL(14,2) DEFAULT 0.00,
    `total` DECIMAL(14,2) NOT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_cxpd_cxp` (`cxp_id`),
    INDEX `idx_cxpd_cuenta` (`cuenta_contable_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$db->exec($sql);
echo "[OK] Tabla 'cuentas_por_pagar_detalle' verificada o creada exitosamente.\n";

// 2. Sembrar o verificar claves adicionales en config_cuentas si existe
try {
    $tablaCfg = $db->query("SHOW TABLES LIKE 'config_cuentas'")->fetch();
    if ($tablaCfg) {
        $clavesExtra = [
            ['clave' => 'RETENCION_ALCALDIA_1', 'codigo' => '2010507', 'desc' => 'Retención Alcaldía 1%', 'modulo' => 'impuestos'],
            ['clave' => 'IR_FUENTE_10',         'codigo' => '2010505', 'desc' => 'Retención IR Servicios Profesionales 10%', 'modulo' => 'impuestos'],
        ];

        $stmtIns = $db->prepare("
            INSERT IGNORE INTO `config_cuentas` (`clave`, `codigo_cuenta`, `descripcion`, `modulo`)
            VALUES (:clave, :codigo, :desc, :modulo)
        ");

        foreach ($clavesExtra as $ce) {
            $stmtIns->execute($ce);
        }

        // Resolver id_cuenta contra cuentas_contables / cuentas si está null
        $tablasCuentas = ['cuentas_contables', 'cuentas'];
        foreach ($tablasCuentas as $tc) {
            try {
                if ($db->query("SHOW TABLES LIKE '{$tc}'")->fetch()) {
                    $db->exec("
                        UPDATE `config_cuentas` cc
                        INNER JOIN `{$tc}` c ON cc.codigo_cuenta = c.codigo
                        SET cc.id_cuenta = c.id
                        WHERE cc.id_cuenta IS NULL
                    ");
                }
            } catch (Throwable $t) {
                // Silencioso
            }
        }
        echo "[OK] Claves de retenciones fiscales verificadas en 'config_cuentas'.\n";
    }
} catch (Throwable $e) {
    echo "[INFO] Verificación config_cuentas: " . $e->getMessage() . "\n";
}

echo "--- Migración 016 finalizada con éxito ---\n";
