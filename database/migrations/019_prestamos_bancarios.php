<?php
/**
 * Migración: 019_prestamos_bancarios.php
 * TAREA-052: Fase 9 · Préstamos Bancarios (Amortización, Cuotas e Intereses).
 *
 * Crea las tablas:
 * - prestamos_bancarios
 * - prestamos_cuotas
 *
 * Incorpora claves en config_cuentas:
 * - PRESTAMOS_BANCARIOS
 * - GASTOS_INTERESES
 *
 * Ejecución CLI: php database/migrations/019_prestamos_bancarios.php
 */

if (!isset($db) || !($db instanceof PDO)) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

echo "--- Iniciando Migración 019: Préstamos Bancarios y Cuotas (TAREA-052) ---\n";

// 1. Tabla prestamos_bancarios
$db->exec("
CREATE TABLE IF NOT EXISTS `prestamos_bancarios` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(50) UNIQUE NOT NULL,
    `institucion_financiera` VARCHAR(150) NOT NULL,
    `monto_prestamo` DECIMAL(14,2) NOT NULL,
    `tasa_interes_anual` DECIMAL(5,2) NOT NULL,
    `plazo_meses` INT NOT NULL,
    `fecha_desembolso` DATE NOT NULL,
    `cuenta_banco_id` INT NOT NULL,
    `cuenta_pasivo_id` INT NULL,
    `saldo_capital` DECIMAL(14,2) NOT NULL,
    `estado` ENUM('activo','cancelado','anulado') DEFAULT 'activo',
    `id_asiento_desembolso` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_pb_estado` (`estado`),
    INDEX `idx_pb_banco` (`cuenta_banco_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla 'prestamos_bancarios' verificada.\n";

// 2. Tabla prestamos_cuotas
$db->exec("
CREATE TABLE IF NOT EXISTS `prestamos_cuotas` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `prestamo_id` INT NOT NULL,
    `numero_cuota` INT NOT NULL,
    `fecha_vencimiento` DATE NOT NULL,
    `cuota_total` DECIMAL(14,2) NOT NULL,
    `amortizacion_capital` DECIMAL(14,2) NOT NULL,
    `interes` DECIMAL(14,2) NOT NULL,
    `saldo_pendiente` DECIMAL(14,2) NOT NULL,
    `estado` ENUM('pendiente','pagada') DEFAULT 'pendiente',
    `fecha_pago` DATE NULL,
    `id_asiento_pago` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX `idx_pc_prestamo` (`prestamo_id`),
    INDEX `idx_pc_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla 'prestamos_cuotas' verificada.\n";

// 3. Sembrado y resolución de claves en config_cuentas
try {
    $checkCfg = $db->query("SHOW TABLES LIKE 'config_cuentas'")->fetch();
    if ($checkCfg) {
        $clavesPrestamos = [
            [
                'clave'  => 'PRESTAMOS_BANCARIOS',
                'codigo' => '2010402',
                'desc'   => 'Préstamos Bancarios por Pagar',
                'modulo' => 'prestamos'
            ],
            [
                'clave'  => 'GASTOS_INTERESES',
                'codigo' => '6020101',
                'desc'   => 'Gastos Financieros por Intereses de Préstamos',
                'modulo' => 'prestamos'
            ]
        ];

        $insCfg = $db->prepare("
            INSERT IGNORE INTO `config_cuentas` (`clave`, `codigo_cuenta`, `descripcion`, `modulo`)
            VALUES (:clave, :codigo, :desc, :modulo)
        ");

        foreach ($clavesPrestamos as $cp) {
            $insCfg->execute([
                'clave'  => $cp['clave'],
                'codigo' => $cp['codigo'],
                'desc'   => $cp['desc'],
                'modulo' => $cp['modulo']
            ]);
        }

        // Resolver id_cuenta en config_cuentas contra cuentas_contables o cuentas
        foreach (['cuentas_contables', 'cuentas'] as $tc) {
            $checkTc = $db->query("SHOW TABLES LIKE '{$tc}'")->fetch();
            if ($checkTc) {
                $db->exec("
                    UPDATE `config_cuentas` cc
                    INNER JOIN `{$tc}` c ON cc.codigo_cuenta = c.codigo
                    SET cc.id_cuenta = c.id
                    WHERE cc.id_cuenta IS NULL AND cc.clave IN ('PRESTAMOS_BANCARIOS', 'GASTOS_INTERESES')
                ");
            }
        }

        // Si id_cuenta sigue null para PRESTAMOS_BANCARIOS, buscar fallback en pasivo 20104%
        $stmtCheckPB = $db->prepare("SELECT id_cuenta FROM `config_cuentas` WHERE `clave` = 'PRESTAMOS_BANCARIOS' LIMIT 1");
        $stmtCheckPB->execute();
        $idPb = $stmtCheckPB->fetchColumn();
        if (!$idPb) {
            foreach (['cuentas_contables', 'cuentas'] as $tc) {
                $checkTc = $db->query("SHOW TABLES LIKE '{$tc}'")->fetch();
                if ($checkTc) {
                    $idCta = $db->query("SELECT id FROM `{$tc}` WHERE codigo LIKE '20104%' AND tipo = 'DETALLE' ORDER BY id ASC LIMIT 1")->fetchColumn();
                    if ($idCta) {
                        $upd = $db->prepare("UPDATE `config_cuentas` SET `id_cuenta` = :id WHERE `clave` = 'PRESTAMOS_BANCARIOS'");
                        $upd->execute(['id' => (int)$idCta]);
                        break;
                    }
                }
            }
        }

        // Si id_cuenta sigue null para GASTOS_INTERESES, buscar fallback en egreso 60201%
        $stmtCheckGI = $db->prepare("SELECT id_cuenta FROM `config_cuentas` WHERE `clave` = 'GASTOS_INTERESES' LIMIT 1");
        $stmtCheckGI->execute();
        $idGi = $stmtCheckGI->fetchColumn();
        if (!$idGi) {
            foreach (['cuentas_contables', 'cuentas'] as $tc) {
                $checkTc = $db->query("SHOW TABLES LIKE '{$tc}'")->fetch();
                if ($checkTc) {
                    $idCta = $db->query("SELECT id FROM `{$tc}` WHERE codigo LIKE '60201%' AND tipo = 'DETALLE' ORDER BY id ASC LIMIT 1")->fetchColumn();
                    if ($idCta) {
                        $upd = $db->prepare("UPDATE `config_cuentas` SET `id_cuenta` = :id WHERE `clave` = 'GASTOS_INTERESES'");
                        $upd->execute(['id' => (int)$idCta]);
                        break;
                    }
                }
            }
        }

        echo "[OK] Claves de Préstamos Bancarios vinculadas en config_cuentas.\n";
    }
} catch (Throwable $e) {
    echo "[INFO] Fallo vinculando claves en config_cuentas: " . $e->getMessage() . "\n";
}

echo "--- Migración 019 Finalizada con Éxito ---\n";
