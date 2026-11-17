<?php

/**
 * Migración 037: Ingreso automático a Laboratorio de Hojas RT-FM-13 pendientes sin paso manual de Aceptar/Observar.
 *
 * Sincroniza de forma idempotente cualquier hoja RT-FM-13 existente (no compactación)
 * de órdenes aprobadas que aún no hubiera ingresado a recepcion_muestras.
 */

require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
require_once dirname(__DIR__, 2) . '/app/Core/Conexion.php';
require_once dirname(__DIR__, 2) . '/app/Helpers/funciones.php';
require_once dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Modelos/OperacionModelo.php';

if (!function_exists('ejecutarMigracion037')) {
    function ejecutarMigracion037(PDO $db): void {
        $stmtPend = $db->query("
            SELECT hs.id AS id_hoja, hs.id_os, os.estado
            FROM hojas_solicitud hs
            INNER JOIN ordenes_servicio os ON hs.id_os = os.id
            WHERE hs.muestras_json IS NOT NULL
              AND hs.muestras_json <> ''
              AND hs.muestras_json <> '[]'
              AND os.estado NOT IN ('Finalizado', 'Archivado', 'Cerrado', 'Cancelada', 'Borrador')
              AND NOT EXISTS (
                  SELECT 1 FROM recepcion_muestras rm
                  WHERE rm.id_os = hs.id_os AND rm.id_hoja = hs.id
              )
            ORDER BY hs.id ASC
        ");
        $pendientes = $stmtPend->fetchAll(PDO::FETCH_ASSOC);
        if (empty($pendientes)) {
            return;
        }

        $opModelo = new \Cycsa\Modulos\Operaciones\Modelos\OperacionModelo();
        foreach ($pendientes as $row) {
            if (!function_exists('esOrdenServicioAprobada') || !esOrdenServicioAprobada((string)($row['estado'] ?? ''))) {
                continue;
            }
            $opModelo->ingresarMuestrasHojaALaboratorio((int)$row['id_os'], (int)$row['id_hoja'], 1, false);
        }
    }
}

if (PHP_SAPI === 'cli' && basename($_SERVER['SCRIPT_FILENAME'] ?? '') === basename(__FILE__)) {
    try {
        $pdo = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        ejecutarMigracion037($pdo);
        echo "[OK] Migración 037 ejecutada exitosamente.\n";
    } catch (Throwable $e) {
        echo "[ERROR] Migración 037 falló: " . $e->getMessage() . "\n";
        exit(1);
    }
}
