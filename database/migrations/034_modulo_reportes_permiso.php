<?php

/**
 * Migración 034: Habilita la clave RBAC 'reportes' en roles existentes con permisos configurados.
 *
 * Agrega `"reportes": {"ver": 1}` de manera aditiva e idempotente a los roles no-admin
 * que aún no tengan definida la entrada 'reportes' en su JSON de permisos.
 */

require_once dirname(__DIR__, 2) . '/app/Core/Conexion.php';

if (!function_exists('ejecutarMigracion034')) {
    function ejecutarMigracion034(PDO $db): void {
        $roles = $db->query("SELECT id, permisos FROM roles WHERE id > 1 AND permisos IS NOT NULL AND permisos <> ''")->fetchAll(PDO::FETCH_ASSOC);
        $stmtUpd = $db->prepare("UPDATE roles SET permisos = :perm WHERE id = :id");
        foreach ($roles as $rol) {
            $perm = json_decode((string)$rol['permisos'], true);
            if (!is_array($perm)) {
                continue;
            }
            if (!array_key_exists('reportes', $perm)) {
                $perm['reportes'] = ['ver' => 1];
                $stmtUpd->execute([
                    'perm' => json_encode($perm, JSON_UNESCAPED_UNICODE),
                    'id' => (int)$rol['id'],
                ]);
            }
        }
    }
}

if (PHP_SAPI === 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    try {
        $pdo = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        ejecutarMigracion034($pdo);
        echo "[OK] Migración 034 ejecutada exitosamente.\n";
    } catch (Throwable $e) {
        echo "[ERROR] Migración 034 falló: " . $e->getMessage() . "\n";
        exit(1);
    }
}
