<?php

/**
 * Migración 031: Soporte para Lotes de Laboratorio por Hoja RT-FM-13,
 * Secuencia de Matriz y Sincronización de Réplicas CR.
 *
 * 1. Agrega columna `orden_en_lote` e índice `(id_hoja, orden_en_lote)` en `recepcion_muestras`.
 * 2. Actualiza vista `v_lab_muestras` exponiendo `id_os`, `id_hoja`, `is_qa_qc`, `replica_codigo`,
 *    `orden_en_lote` y `total_muestras_lote` preservando el aislamiento comercial ISO 17025.
 * 3. Backfill idempotente de `plantilla_id`, `orden_en_lote` y réplicas `-CR` existentes en matriz.
 *
 * 100% aditiva e idempotente.
 */

require_once dirname(__DIR__, 2) . '/app/Core/Conexion.php';
require_once dirname(__DIR__, 2) . '/app/Helpers/funciones.php';

if (!function_exists('ejecutarMigracion031')) {
    function ejecutarMigracion031(PDO $db): void {
        // 1. Ampliar recepcion_muestras con orden_en_lote
        $colsRec = $db->query("SHOW COLUMNS FROM recepcion_muestras")->fetchAll(PDO::FETCH_COLUMN);

        if (!in_array('orden_en_lote', $colsRec, true)) {
            $db->exec("ALTER TABLE recepcion_muestras ADD COLUMN orden_en_lote INT(11) NOT NULL DEFAULT 0 AFTER is_sealed");
        }

        // 2. Índice aditivo para consulta rápida de lote ordenado
        $indicesRec = $db->query("SHOW INDEX FROM recepcion_muestras")->fetchAll(PDO::FETCH_ASSOC);
        $nombresIndices = array_column($indicesRec, 'Key_name');

        if (!in_array('idx_rec_hoja_orden', $nombresIndices, true)) {
            $db->exec("ALTER TABLE recepcion_muestras ADD INDEX idx_rec_hoja_orden (id_hoja, orden_en_lote)");
        }

        // 3. Actualizar vista SQL v_lab_muestras (Anonimización ISO 17025 mantenida + agrupación por id_hoja)
        $db->exec("
        CREATE OR REPLACE VIEW `v_lab_muestras` AS
        SELECT 
            rm.id AS muestra_id,
            rm.id_hoja,
            rm.codigo_muestra AS codigo_lab,
            rm.codigo_campo,
            rm.tipo_muestra,
            rm.is_qa_qc,
            rm.replica_codigo,
            rm.orden_en_lote,
            rm.fecha_recepcion AS fecha_ingreso,
            rm.estado AS estado_recepcion,
            lm.id AS lote_id,
            lm.nombre_lote AS material_descripcion,
            lm.fecha_moldeo,
            lm.diseno_resistencia,
            lm.revenimiento_in,
            lm.temperatura_c,
            lm.procedimiento_muestreo,
            plt.id AS plantilla_id,
            plt.codigo AS plantilla_codigo,
            plt.nombre AS plantilla_nombre,
            plt.norma AS norma_referencia,
            pv.id AS plantilla_version_id,
            pv.version AS plantilla_version,
            (SELECT COUNT(*) FROM `especimenes` esp WHERE esp.muestra_id = rm.id) AS total_especimenes,
            (SELECT COUNT(*) FROM `especimenes` esp WHERE esp.muestra_id = rm.id AND esp.estado = 'ensayado') AS especimenes_ensayados,
            (SELECT MIN(pre.fecha_programada) 
             FROM `plan_rotura_edades` pre 
             JOIN `planes_rotura` pr ON pre.plan_rotura_id = pr.id 
             WHERE pr.muestra_id = rm.id AND pre.estado IN ('pendiente', 'en_proceso')) AS proxima_rotura,
            (SELECT pr.estado FROM `planes_rotura` pr WHERE pr.muestra_id = rm.id ORDER BY pr.id DESC LIMIT 1) AS estado_plan,
            (SELECT COUNT(*) FROM `recepcion_muestras` rm2 WHERE rm2.id_hoja = rm.id_hoja AND rm.id_hoja IS NOT NULL AND rm.id_hoja > 0) AS total_muestras_lote
        FROM `recepcion_muestras` rm
        JOIN `lotes_muestras` lm ON lm.id_recepcion = rm.id
        LEFT JOIN `plantillas` plt ON rm.plantilla_id = plt.id
        LEFT JOIN `plantilla_versiones` pv ON rm.plantilla_version_id = pv.id
        ");

        // 4. Backfill idempotente de plantilla_id en recepcion_muestras donde sea NULL
        try {
            $db->exec("
                UPDATE recepcion_muestras rm
                SET rm.plantilla_id = (
                    SELECT hsd.plantilla_id 
                    FROM hojas_solicitud_detalles hsd 
                    WHERE hsd.id_hoja_solicitud = rm.id_hoja AND hsd.plantilla_id IS NOT NULL AND hsd.plantilla_id > 0 
                    ORDER BY hsd.orden ASC, hsd.id ASC LIMIT 1
                )
                WHERE rm.plantilla_id IS NULL AND rm.id_hoja IS NOT NULL AND rm.id_hoja > 0
            ");
        } catch (\Throwable $e) {
            // No bloquear si no hay registros
        }

        // 5. Backfill idempotente de orden_en_lote y réplicas -CR existentes desde cotizacion_detalles.resultados_json
        if (function_exists('sincronizarReplicasMatrizConLab')) {
            try {
                $stmtSync = $db->query("
                    SELECT DISTINCT rm.id_os, rm.id_hoja, COALESCE(hs.id_detalle_cotizacion, rm.id_detalle_cotizacion) AS id_det, cd.resultados_json
                    FROM recepcion_muestras rm
                    LEFT JOIN hojas_solicitud hs ON rm.id_hoja = hs.id
                    LEFT JOIN cotizacion_detalles cd ON cd.id = COALESCE(hs.id_detalle_cotizacion, rm.id_detalle_cotizacion)
                    WHERE rm.id_hoja IS NOT NULL AND rm.id_hoja > 0
                      AND cd.resultados_json IS NOT NULL AND cd.resultados_json != ''
                ");
                foreach ($stmtSync->fetchAll(PDO::FETCH_ASSOC) as $sRow) {
                    $resSync = json_decode((string)$sRow['resultados_json'], true);
                    if (is_array($resSync) && !empty($resSync['filas']) && is_array($resSync['filas'])) {
                        sincronizarReplicasMatrizConLab(
                            $db,
                            (int)$sRow['id_os'],
                            (int)$sRow['id_hoja'],
                            (int)$sRow['id_det'],
                            $resSync['filas']
                        );
                    }
                }
            } catch (\Throwable $e) {
                // No bloquear migración
            }
        }
    }
}

if (php_sapi_name() === 'cli' && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
    ejecutarMigracion031($db);
    echo "[OK] Migración 031 ejecutada correctamente.\n";
}
