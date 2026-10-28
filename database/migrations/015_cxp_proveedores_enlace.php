<?php
/**
 * Migración: 015_cxp_proveedores_enlace.php
 * TAREA-047: Fase 3 · Enlace de Cuentas por Pagar (CXP) con Proveedores, OC y Recepciones.
 *
 * Columnas añadidas a 'cuentas_por_pagar':
 * - proveedor_id: INT NULL AFTER proveedor_nombre
 * - orden_compra_id: INT NULL AFTER proveedor_id
 * - recepcion_id: INT NULL AFTER orden_compra_id
 *
 * Ejecución CLI: php database/migrations/015_cxp_proveedores_enlace.php
 */

if (!isset($db) || !($db instanceof PDO)) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
}

echo "--- Iniciando Migración 015: Enlace CXP a Proveedores, OC y Recepciones ---\n";

if (!function_exists('asegurarIndice015')) {
    function asegurarIndice015(PDO $db, string $tabla, string $nombreIndice, string $columnas): void {
        try {
            $stmt = $db->query("SHOW INDEX FROM `{$tabla}` WHERE Key_name = '{$nombreIndice}'");
            if (!$stmt->fetch()) {
                $db->exec("ALTER TABLE `{$tabla}` ADD INDEX `{$nombreIndice}` ({$columnas})");
            }
        } catch (Throwable $e) {
            echo "[INFO] Índice {$nombreIndice}: " . $e->getMessage() . "\n";
        }
    }
}

// 1. Verificar existencia de la tabla cuentas_por_pagar
$tablaExiste = $db->query("SHOW TABLES LIKE 'cuentas_por_pagar'")->fetch();
if (!$tablaExiste) {
    echo "[AVISO] La tabla 'cuentas_por_pagar' no existe aún. Omitiendo migración 015.\n";
    return;
}

// 2. Obtener columnas existentes en cuentas_por_pagar
$cols = $db->query("SHOW COLUMNS FROM `cuentas_por_pagar`")->fetchAll(PDO::FETCH_COLUMN);
$colsLower = array_map('strtolower', $cols);

// 3. Columna proveedor_id
if (!in_array('proveedor_id', $colsLower)) {
    $db->exec("ALTER TABLE `cuentas_por_pagar` ADD COLUMN `proveedor_id` INT NULL AFTER `proveedor_nombre`");
    echo "[OK] Columna 'proveedor_id' agregada a 'cuentas_por_pagar'.\n";
} else {
    echo "[INFO] Columna 'proveedor_id' ya existe.\n";
}

// 4. Columna orden_compra_id
if (!in_array('orden_compra_id', $colsLower)) {
    $db->exec("ALTER TABLE `cuentas_por_pagar` ADD COLUMN `orden_compra_id` INT NULL AFTER `proveedor_id`");
    echo "[OK] Columna 'orden_compra_id' agregada a 'cuentas_por_pagar'.\n";
} else {
    echo "[INFO] Columna 'orden_compra_id' ya existe.\n";
}

// 5. Columna recepcion_id
if (!in_array('recepcion_id', $colsLower)) {
    $db->exec("ALTER TABLE `cuentas_por_pagar` ADD COLUMN `recepcion_id` INT NULL AFTER `orden_compra_id`");
    echo "[OK] Columna 'recepcion_id' agregada a 'cuentas_por_pagar'.\n";
} else {
    echo "[INFO] Columna 'recepcion_id' ya existe.\n";
}

// 6. Asegurar índices para acelerar búsquedas y reportes
asegurarIndice015($db, 'cuentas_por_pagar', 'idx_cxp_proveedor_id', '`proveedor_id`');
asegurarIndice015($db, 'cuentas_por_pagar', 'idx_cxp_orden_compra_id', '`orden_compra_id`');
asegurarIndice015($db, 'cuentas_por_pagar', 'idx_cxp_recepcion_id', '`recepcion_id`');
echo "[OK] Índices de enlace verificados en 'cuentas_por_pagar'.\n";

echo "--- Migración 015 finalizada con éxito ---\n";
