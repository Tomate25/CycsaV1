<?php

/**
 * Migración 033: Normalización de consecutivo único global para RT-FM-13 (01, 02, 03, 04 ... infinito).
 *
 * Reemplaza registros heredados con formato de O/S o sufijo compuesto (ej. 00001, 00001-02, 02-02)
 * por un consecutivo global único ordenado por id ASC ('01', '02', '03', ..., '99', '100', ...).
 * Es 100% idempotente: si todas las hojas ya tienen formato limpio y único, no altera nada.
 */

require_once dirname(__DIR__, 2) . '/app/Core/Conexion.php';

if (!function_exists('ejecutarMigracion033')) {
    function ejecutarMigracion033(PDO $db): void {
        $filas = $db->query("SELECT id, numero_registro FROM hojas_solicitud ORDER BY id ASC")->fetchAll(PDO::FETCH_ASSOC);
        if (empty($filas)) {
            return;
        }

        $requiereNormalizacion = false;
        $vistos = [];
        foreach ($filas as $f) {
            $num = trim((string)($f['numero_registro'] ?? ''));
            if ($num === '' || preg_match('/^\d+-\d+$/', $num) || preg_match('/^0{2,}\d{3,}$/', $num)) {
                $requiereNormalizacion = true;
                break;
            }
            if (preg_match('/^\d+$/', $num)) {
                if (isset($vistos[$num])) {
                    $requiereNormalizacion = true;
                    break;
                }
                $vistos[$num] = true;
            }
        }

        if (!$requiereNormalizacion) {
            return;
        }

        $transaccionPropia = !$db->inTransaction();
        if ($transaccionPropia) {
            $db->beginTransaction();
        }
        try {
            $consecutivo = 1;
            $stmtUpd = $db->prepare("UPDATE hojas_solicitud SET numero_registro = :num WHERE id = :id");
            foreach ($filas as $f) {
                $numActual = trim((string)($f['numero_registro'] ?? ''));
                // Preservar etiquetas no numéricas explícitas (p. ej. códigos manuales alfanuméricos de prueba)
                if ($numActual !== '' && !preg_match('/^\d+(?:-\d+)?$/', $numActual)) {
                    continue;
                }
                $nuevo = str_pad((string)$consecutivo, 2, '0', STR_PAD_LEFT);
                if ($numActual !== $nuevo) {
                    $stmtUpd->execute([
                        'num' => $nuevo,
                        'id' => (int)$f['id'],
                    ]);
                }
                $consecutivo++;
            }
            if ($transaccionPropia && $db->inTransaction()) {
                $db->commit();
            }
        } catch (\Throwable $e) {
            if ($transaccionPropia && $db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }
    }
}

if (PHP_SAPI === 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    try {
        $pdo = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        ejecutarMigracion033($pdo);
        echo "[OK] Migración 033 ejecutada exitosamente.\n";
    } catch (Throwable $e) {
        echo "[ERROR] Migración 033 falló: " . $e->getMessage() . "\n";
        exit(1);
    }
}
