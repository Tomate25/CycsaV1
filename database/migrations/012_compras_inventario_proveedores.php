<?php
/**
 * Migración: 012_compras_inventario_proveedores.php
 * TAREA-045: Fase 2 · Módulo de Compras, Inventario y Maestro Proveedores.
 * 
 * Tablas creadas/actualizadas:
 * 1. proveedores (Maestro de proveedores, RUC/cédula, datos bancarios, evaluación, estado)
 * 2. ordenes_compra (ALTER TABLE para columnas de proveedor_id, montos, aprobación)
 * 3. ordenes_compra_detalle (Ítems/insumos solicitados, cantidades, costos, subtotales)
 * 4. recepciones_compra (Recepción de pedidos y factura del proveedor)
 * 5. recepciones_compra_detalle (Detalle de recepción física, lotes y vencimiento)
 * 6. inventario_insumos (ALTER TABLE para cuenta_inventario_id, stock_minimo, unidad_medida)
 * 7. inventario_lotes (Control de lotes, saldos y fechas de vencimiento)
 * 8. inventario_movimientos (Kárdex de movimientos: entradas, consumos, ajustes, mermas)
 *
 * Ejecución CLI: php database/migrations/012_compras_inventario_proveedores.php
 */

if (!isset($db) || !($db instanceof PDO)) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

echo "--- Iniciando Migración 012: Compras, Inventario y Maestro Proveedores ---\n";

if (!function_exists('asegurarIndice012')) {
    function asegurarIndice012(PDO $db, string $tabla, string $nombreIndice, string $columnas): void {
        $stmt = $db->query("SHOW INDEX FROM `{$tabla}` WHERE Key_name = '{$nombreIndice}'");
        if (!$stmt->fetch()) {
            $db->exec("ALTER TABLE `{$tabla}` ADD INDEX `{$nombreIndice}` ({$columnas})");
        }
    }
}

// 1. Tabla proveedores
$db->exec("
CREATE TABLE IF NOT EXISTS `proveedores` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `tipo_id` ENUM('RUC','cedula','extranjero') NOT NULL DEFAULT 'RUC',
  `identificacion` VARCHAR(50) NOT NULL UNIQUE,
  `razon_social` VARCHAR(255) NOT NULL,
  `contacto` VARCHAR(150) NULL,
  `telefono` VARCHAR(50) NULL,
  `email` VARCHAR(100) NULL,
  `banco` VARCHAR(100) NULL,
  `cuenta_bancaria` VARCHAR(100) NULL,
  `plazo_pago_dias` INT DEFAULT 30,
  `id_cuenta_contable` INT NULL,
  `cuenta_anticipo_id` INT NULL,
  `categoria` VARCHAR(50) DEFAULT 'insumos',
  `evaluado` TINYINT(1) DEFAULT 0,
  `fecha_evaluacion` DATE NULL,
  `estado` ENUM('activo','inactivo','bloqueado') DEFAULT 'activo',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_prov_estado` (`estado`),
  INDEX `idx_prov_categoria` (`categoria`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla proveedores verificada.\n";

// 2. Tabla base ordenes_compra (si no existe) y ALTER TABLE
$db->exec("
CREATE TABLE IF NOT EXISTS `ordenes_compra` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `proveedor` VARCHAR(255) NULL,
  `fecha` DATE NULL,
  `total` DECIMAL(12,2) DEFAULT 0,
  `estado` VARCHAR(50) DEFAULT 'SOLICITADA',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$db->exec("
ALTER TABLE `ordenes_compra`
  ADD COLUMN IF NOT EXISTS `proveedor_id` INT NULL,
  ADD COLUMN IF NOT EXISTS `subtotal` DECIMAL(12,2) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `iva` DECIMAL(12,2) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `total` DECIMAL(12,2) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `aprobado_por` INT NULL,
  ADD COLUMN IF NOT EXISTS `fecha_aprobacion` DATETIME NULL;
");
asegurarIndice012($db, 'ordenes_compra', 'idx_oc_proveedor_id', '`proveedor_id`');
asegurarIndice012($db, 'ordenes_compra', 'idx_oc_estado', '`estado`');
echo "[OK] Tabla ordenes_compra y columnas verificadas.\n";

// 3. Tabla ordenes_compra_detalle
$db->exec("
CREATE TABLE IF NOT EXISTS `ordenes_compra_detalle` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orden_compra_id` INT NOT NULL,
  `insumo_id` INT NULL,
  `descripcion` VARCHAR(255) NULL,
  `cantidad` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `cantidad_recibida` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `costo_unitario` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `iva` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `total` DECIMAL(12,2) NOT NULL DEFAULT 0,
  `cuenta_contable_id` INT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_ocd_orden_compra_id` (`orden_compra_id`),
  INDEX `idx_ocd_insumo_id` (`insumo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla ordenes_compra_detalle verificada.\n";

// 4. Tabla recepciones_compra
$db->exec("
CREATE TABLE IF NOT EXISTS `recepciones_compra` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `orden_compra_id` INT NULL,
  `proveedor_id` INT NOT NULL,
  `numero_factura` VARCHAR(100) NULL,
  `fecha_recepcion` DATE NOT NULL,
  `recibido_por` INT NULL,
  `observaciones` TEXT NULL,
  `estado` ENUM('completada','anulada') DEFAULT 'completada',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_rc_orden_compra_id` (`orden_compra_id`),
  INDEX `idx_rc_proveedor_id` (`proveedor_id`),
  INDEX `idx_rc_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla recepciones_compra verificada.\n";

// 5. Tabla recepciones_compra_detalle
$db->exec("
CREATE TABLE IF NOT EXISTS `recepciones_compra_detalle` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `recepcion_id` INT NOT NULL,
  `orden_compra_detalle_id` INT NULL,
  `insumo_id` INT NOT NULL,
  `cantidad_recibida` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `costo_unitario` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `lote` VARCHAR(100) NULL,
  `fecha_vencimiento` DATE NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_rcd_recepcion_id` (`recepcion_id`),
  INDEX `idx_rcd_oc_detalle_id` (`orden_compra_detalle_id`),
  INDEX `idx_rcd_insumo_id` (`insumo_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla recepciones_compra_detalle verificada.\n";

// 6. Tabla base inventario_insumos (si no existe) y ALTER TABLE
$db->exec("
CREATE TABLE IF NOT EXISTS `inventario_insumos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `codigo` VARCHAR(50) UNIQUE NOT NULL,
  `nombre` VARCHAR(255) NOT NULL,
  `cantidad` DECIMAL(10,2) DEFAULT 0,
  `unidad_medida` VARCHAR(50) DEFAULT 'unidad',
  `stock_minimo` DECIMAL(10,2) DEFAULT 0,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");

$db->exec("
ALTER TABLE `inventario_insumos`
  ADD COLUMN IF NOT EXISTS `cuenta_inventario_id` INT NULL,
  ADD COLUMN IF NOT EXISTS `stock_minimo` DECIMAL(10,2) DEFAULT 0,
  ADD COLUMN IF NOT EXISTS `unidad_medida` VARCHAR(50) DEFAULT 'unidad';
");
asegurarIndice012($db, 'inventario_insumos', 'idx_inv_cuenta', '`cuenta_inventario_id`');
echo "[OK] Tabla inventario_insumos y columnas verificadas.\n";

// 7. Tabla inventario_lotes
$db->exec("
CREATE TABLE IF NOT EXISTS `inventario_lotes` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `insumo_id` INT NOT NULL,
  `lote` VARCHAR(100) NOT NULL,
  `fecha_vencimiento` DATE NULL,
  `costo_unitario` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `cantidad_inicial` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `saldo` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `estado` ENUM('activo','agotado','vencido') DEFAULT 'activo',
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  INDEX `idx_lotes_insumo_id` (`insumo_id`),
  INDEX `idx_lotes_lote` (`lote`),
  INDEX `idx_lotes_estado` (`estado`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla inventario_lotes verificada.\n";

// 8. Tabla inventario_movimientos
$db->exec("
CREATE TABLE IF NOT EXISTS `inventario_movimientos` (
  `id` INT AUTO_INCREMENT PRIMARY KEY,
  `insumo_id` INT NOT NULL,
  `lote_id` INT NULL,
  `tipo_movimiento` ENUM('entrada_compra','salida_consumo','ajuste_positivo','ajuste_negativo','merma','consumo_ensayo') NOT NULL,
  `cantidad` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `costo_unitario` DECIMAL(12,4) NOT NULL DEFAULT 0,
  `saldo_anterior` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `saldo_nuevo` DECIMAL(10,2) NOT NULL DEFAULT 0,
  `referencia_tipo` VARCHAR(50) NULL,
  `referencia_id` INT NULL,
  `usuario_id` INT NULL,
  `observaciones` TEXT NULL,
  `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
  INDEX `idx_mov_insumo_id` (`insumo_id`),
  INDEX `idx_mov_lote_id` (`lote_id`),
  INDEX `idx_mov_tipo` (`tipo_movimiento`),
  INDEX `idx_mov_ref` (`referencia_tipo`, `referencia_id`)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
");
echo "[OK] Tabla inventario_movimientos verificada.\n";

echo "--- Migración 012 finalizada con éxito ---\n";
