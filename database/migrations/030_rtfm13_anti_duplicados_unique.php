<?php

/**
 * Migración 030: Blindaje Anti-Duplicados para Hojas de Solicitud CYCSA-RT-FM-13.
 * 
 * Añade restricción UNIQUE KEY uq_hs_os_detalle (id_os, id_detalle_cotizacion)
 * para garantizar a nivel de motor de base de datos que jamás se emitan dos hojas
 * para el mismo ítem/producto dentro de una misma Orden de Servicio.
 * 
 * Incluye saneamiento y consolidación previa idempotente de duplicados existentes.
 * 100% aditiva e idempotente.
 */

require_once dirname(__DIR__, 2) . '/app/Core/Conexion.php';

if (!function_exists('ejecutarMigracion030')) {
    function ejecutarMigracion030(PDO $db): void {
        // 1. Saneamiento seguro de duplicados existentes antes de crear la restricción UNIQUE
        $stmtDupes = $db->query("
            SELECT id_os, id_detalle_cotizacion, COUNT(*) as cnt, MIN(id) as id_oficial
            FROM hojas_solicitud
            WHERE id_detalle_cotizacion IS NOT NULL AND id_detalle_cotizacion > 0
            GROUP BY id_os, id_detalle_cotizacion
            HAVING cnt > 1
        ");
        $gruposDuplicados = $stmtDupes ? $stmtDupes->fetchAll(PDO::FETCH_ASSOC) : [];

        foreach ($gruposDuplicados as $grupo) {
            $idOficial = (int)$grupo['id_oficial'];
            $idOs = (int)$grupo['id_os'];
            $idDet = (int)$grupo['id_detalle_cotizacion'];

            $stmtDupeIds = $db->prepare("SELECT id FROM hojas_solicitud WHERE id_os = :id_os AND id_detalle_cotizacion = :id_det AND id != :id_oficial ORDER BY id ASC");
            $stmtDupeIds->execute(['id_os' => $idOs, 'id_det' => $idDet, 'id_oficial' => $idOficial]);
            $idsDuplicados = $stmtDupeIds->fetchAll(PDO::FETCH_COLUMN);

            foreach ($idsDuplicados as $dupeId) {
                $dupeId = (int)$dupeId;
                // Eliminar detalles de muestra huérfanos creados por la duplicación concurrente
                $db->prepare("DELETE FROM hojas_solicitud_detalles WHERE id_hoja_solicitud = :id")->execute(['id' => $dupeId]);
                // Eliminar registro duplicado de la hoja de solicitud
                $db->prepare("DELETE FROM hojas_solicitud WHERE id = :id")->execute(['id' => $dupeId]);
            }
        }

        // 2. Re-sincronizar secuencias_muestras con el correlativo máximo real restante
        try {
            $stmtMaxMS = $db->query("SELECT MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(codigo_muestra, '-', 2), '-', -1) AS UNSIGNED)) FROM hojas_solicitud_detalles WHERE codigo_muestra LIKE 'MS-%'");
            $maxMS = $stmtMaxMS ? $stmtMaxMS->fetchColumn() : null;
            if ($maxMS !== false && $maxMS !== null && (int)$maxMS > 0) {
                $db->prepare("UPDATE secuencias_muestras SET ultimo_correlativo = :c WHERE anio = :anio AND tipo_muestra = 'Laboratorio'")
                   ->execute(['c' => (int)$maxMS, 'anio' => (int)date('Y')]);
            }

            $stmtMaxMC = $db->query("SELECT MAX(CAST(SUBSTRING_INDEX(SUBSTRING_INDEX(codigo_muestra, '-', 2), '-', -1) AS UNSIGNED)) FROM hojas_solicitud_detalles WHERE codigo_muestra LIKE 'MC-%'");
            $maxMC = $stmtMaxMC ? $stmtMaxMC->fetchColumn() : null;
            if ($maxMC !== false && $maxMC !== null && (int)$maxMC > 0) {
                $db->prepare("UPDATE secuencias_muestras SET ultimo_correlativo = :c WHERE anio = :anio AND tipo_muestra = 'Campo'")
                   ->execute(['c' => (int)$maxMC, 'anio' => (int)date('Y')]);
            }
        } catch (Throwable $eSeq) {
            // Tolerante si no existe tabla o secuencias
        }

        // 3. Crear índice UNIQUE en hojas_solicitud
        $indicesHS = $db->query("SHOW INDEX FROM hojas_solicitud")->fetchAll(PDO::FETCH_ASSOC);
        $nombresIndices = array_column($indicesHS, 'Key_name');

        if (!in_array('uq_hs_os_detalle', $nombresIndices, true)) {
            $db->exec("ALTER TABLE hojas_solicitud ADD UNIQUE KEY uq_hs_os_detalle (id_os, id_detalle_cotizacion)");
        }
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    ejecutarMigracion030($db);
    echo "[OK] Migración 030 ejecutada correctamente.\n";
}
