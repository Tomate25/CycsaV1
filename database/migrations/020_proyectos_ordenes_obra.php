<?php
/**
 * Migración: 020_proyectos_ordenes_obra.php
 * TAREA-053: Fase 10 · Órdenes de Obra y Proyectos (Imputación Transversal de Costos).
 *
 * Crea la tabla 'proyectos' para seguimiento de proyectos y órdenes de obra,
 * y añade columnas aditivas 'proyecto_id' a las tablas transaccionales:
 * - ordenes_compra
 * - ordenes_compra_detalle
 * - cuentas_por_pagar_detalle
 * - inventario_movimientos
 * - rrhh_nomina_detalles
 *
 * Además, siembra claves mnemónicas en config_cuentas:
 * - OBRAS_EN_PROCESO
 * - COSTOS_CONSTRUCCION
 *
 * Ejecución CLI: php database/migrations/020_proyectos_ordenes_obra.php
 */

if (!isset($db) || !($db instanceof PDO)) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

echo "--- Iniciando Migración 020: Proyectos y Órdenes de Obra (TAREA-053) ---\n";

if (!function_exists('asegurarColumna020')) {
    function asegurarColumna020(PDO $db, string $tabla, string $columna, string $definicion): void {
        $stmt = $db->query("SHOW COLUMNS FROM `{$tabla}` LIKE '{$columna}'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE `{$tabla}` ADD COLUMN `{$columna}` {$definicion}");
        }
    }
}

if (!function_exists('asegurarIndice020')) {
    function asegurarIndice020(PDO $db, string $tabla, string $nombreIndice, string $columna): void {
        $stmt = $db->query("SHOW INDEX FROM `{$tabla}` WHERE Key_name = '{$nombreIndice}'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE `{$tabla}` ADD INDEX `{$nombreIndice}` (`{$columna}`)");
        }
    }
}

// 1. Tabla proyectos
$sqlProyectos = "CREATE TABLE IF NOT EXISTS `proyectos` (
    `id` INT AUTO_INCREMENT PRIMARY KEY,
    `codigo` VARCHAR(50) UNIQUE NOT NULL,
    `nombre` VARCHAR(255) NOT NULL,
    `cliente_id` INT NULL,
    `ubicacion` VARCHAR(255) NULL,
    `presupuesto_estimado` DECIMAL(14,2) DEFAULT 0.00,
    `fecha_inicio` DATE NOT NULL,
    `fecha_fin` DATE NULL,
    `estado` ENUM('planificado','en_ejecucion','completado','suspendido') DEFAULT 'planificado',
    `cuenta_obra_proceso_id` INT NULL,
    `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX `idx_proy_cliente` (`cliente_id`),
    INDEX `idx_proy_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;";

$db->exec($sqlProyectos);
echo "[OK] Tabla 'proyectos' verificada o creada exitosamente.\n";

// 2. Columnas aditivas proyecto_id con verificación segura
$tablasAditivas = [
    'ordenes_compra'            => 'idx_oc_proyecto_id',
    'ordenes_compra_detalle'    => 'idx_ocd_proyecto_id',
    'cuentas_por_pagar_detalle' => 'idx_cxpd_proyecto_id',
    'inventario_movimientos'    => 'idx_invm_proyecto_id',
    'rrhh_nomina_detalles'      => 'idx_nomd_proyecto_id',
];

foreach ($tablasAditivas as $tabla => $nombreIndice) {
    try {
        $existe = $db->query("SHOW TABLES LIKE '{$tabla}'")->fetch();
        if ($existe) {
            asegurarColumna020($db, $tabla, 'proyecto_id', 'INT NULL');
            asegurarIndice020($db, $tabla, $nombreIndice, 'proyecto_id');
            echo "[OK] Columna 'proyecto_id' e índice '{$nombreIndice}' verificados en '{$tabla}'.\n";
        }
    } catch (Throwable $e) {
        echo "[AVISO] Advertencia en tabla {$tabla}: " . $e->getMessage() . "\n";
    }
}

// 3. Sembrado de claves en config_cuentas
try {
    $tablaCfg = $db->query("SHOW TABLES LIKE 'config_cuentas'")->fetch();
    if ($tablaCfg) {
        $clavesProyectos = [
            [
                'clave'  => 'OBRAS_EN_PROCESO',
                'codigo' => '10104',
                'desc'   => 'Obras en Proceso (Activo Proyectos)',
                'modulo' => 'proyectos'
            ],
            [
                'clave'  => 'COSTOS_CONSTRUCCION',
                'codigo' => '50101',
                'desc'   => 'Costos de Construcción y Órdenes de Obra',
                'modulo' => 'proyectos'
            ],
        ];

        $stmtIns = $db->prepare("
            INSERT IGNORE INTO `config_cuentas` (`clave`, `codigo_cuenta`, `descripcion`, `modulo`)
            VALUES (:clave, :codigo, :desc, :modulo)
        ");

        foreach ($clavesProyectos as $cp) {
            $stmtIns->execute($cp);
        }

        // Resolver id_cuenta contra cuentas_contables o cuentas
        $tablasCuentas = ['cuentas_contables', 'cuentas'];
        foreach ($tablasCuentas as $tc) {
            $existeTc = $db->query("SHOW TABLES LIKE '{$tc}'")->fetch();
            if ($existeTc) {
                $db->exec("
                    UPDATE `config_cuentas` cc
                    INNER JOIN `{$tc}` c ON cc.codigo_cuenta = c.codigo
                    SET cc.id_cuenta = c.id
                    WHERE cc.id_cuenta IS NULL AND cc.clave IN ('OBRAS_EN_PROCESO', 'COSTOS_CONSTRUCCION')
                ");
            }
        }
        echo "[OK] Sembrado de claves OBRAS_EN_PROCESO y COSTOS_CONSTRUCCION verificado en config_cuentas.\n";
    }
} catch (Throwable $e) {
    echo "[AVISO] Error en sembrado config_cuentas: " . $e->getMessage() . "\n";
}

echo "--- Migración 020 finalizada con éxito ---\n";
