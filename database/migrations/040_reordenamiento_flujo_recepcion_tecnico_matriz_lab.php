<?php
/**
 * Migración 040: Reordenamiento del Flujo Recepción → Técnico → Matriz → Laboratorio y Rediseño de Operaciones
 * (Propuesta_Flujo_Operaciones_Laboratorio_CYCSA — 22 Nov 2026)
 *
 * Idempotente y segura para producción (cero bloqueo de muestras existentes):
 * 1) Agrega columnas de control de etapa a `hojas_solicitud`:
 *    - `etapa_flujo` ('recepcion_borrador','recepcion_completa','en_ejecucion','tecnico_completo','matriz_en_revision','informe')
 *    - `recepcion_completa` (TINYINT 0/1)
 *    - `fecha_recepcion_completa` (DATETIME NULL)
 *    - `recepcion_completada_por` (INT NULL)
 *    - `motivo_reapertura_recepcion` (TEXT NULL)
 *    - `autorizacion_urgente_supervisor` (TINYINT 0/1)
 *    - `motivo_urgente_supervisor` (TEXT NULL)
 * 2) Agrega `etapa_flujo` a `recepcion_muestras`.
 * 3) Backfill inteligente para producción: las hojas que ya tienen muestras en `recepcion_muestras`,
 *    roturas o matrices en revisión/aprobadas se marcan automáticamente con `recepcion_completa = 1`
 *    y su `etapa_flujo` real, para no trabar ninguna muestra viva.
 * 4) Recalcula `plan_rotura_edades.tolerancia_horas` según tabla ASTM C39 por edad (quitando 24h fijo heredado).
 * 5) Limpia áreas prellenadas `14.137` / `28.274` / `12.566` en filas de matriz sin rotura ni carga.
 * 6) Habilita permiso `recepcion` (`ver`, `crear_editar`, `aprobar`) en roles que ya gestionan O/S u Operaciones.
 */

use Cycsa\Modulos\Operaciones\Servicios\ConfigCalculoLabServicio;

if (!function_exists('ejecutarMigracion040')) {
    function ejecutarMigracion040(PDO $db): void {
        $columnaExiste = static function (PDO $pdo, string $tabla, string $columna): bool {
            try {
                $stmt = $pdo->query("SHOW COLUMNS FROM `{$tabla}` LIKE " . $pdo->quote($columna));
                return $stmt && (bool)$stmt->fetch(PDO::FETCH_ASSOC);
            } catch (Throwable $e) {
                return false;
            }
        };

        $tablaExiste = static function (PDO $pdo, string $tabla): bool {
            try {
                $stmt = $pdo->query("SHOW TABLES LIKE " . $pdo->quote($tabla));
                return $stmt && (bool)$stmt->fetch(PDO::FETCH_NUM);
            } catch (Throwable $e) {
                return false;
            }
        };

        // 1. Columnas de etapa y recepción completa en hojas_solicitud
        $recienCreadaColumna = false;
        if ($tablaExiste($db, 'hojas_solicitud')) {
            if (!$columnaExiste($db, 'hojas_solicitud', 'etapa_flujo')) {
                $db->exec("ALTER TABLE `hojas_solicitud` ADD COLUMN `etapa_flujo` VARCHAR(40) NOT NULL DEFAULT 'recepcion_borrador'");
                $recienCreadaColumna = true;
            }
            if (!$columnaExiste($db, 'hojas_solicitud', 'recepcion_completa')) {
                $db->exec("ALTER TABLE `hojas_solicitud` ADD COLUMN `recepcion_completa` TINYINT(1) NOT NULL DEFAULT 0");
                $recienCreadaColumna = true;
            }
            if (!$columnaExiste($db, 'hojas_solicitud', 'fecha_recepcion_completa')) {
                $db->exec("ALTER TABLE `hojas_solicitud` ADD COLUMN `fecha_recepcion_completa` DATETIME NULL");
            }
            if (!$columnaExiste($db, 'hojas_solicitud', 'recepcion_completada_por')) {
                $db->exec("ALTER TABLE `hojas_solicitud` ADD COLUMN `recepcion_completada_por` INT NULL");
            }
            if (!$columnaExiste($db, 'hojas_solicitud', 'motivo_reapertura_recepcion')) {
                $db->exec("ALTER TABLE `hojas_solicitud` ADD COLUMN `motivo_reapertura_recepcion` TEXT NULL");
            }
            if (!$columnaExiste($db, 'hojas_solicitud', 'autorizacion_urgente_supervisor')) {
                $db->exec("ALTER TABLE `hojas_solicitud` ADD COLUMN `autorizacion_urgente_supervisor` TINYINT(1) NOT NULL DEFAULT 0");
            }
            if (!$columnaExiste($db, 'hojas_solicitud', 'motivo_urgente_supervisor')) {
                $db->exec("ALTER TABLE `hojas_solicitud` ADD COLUMN `motivo_urgente_supervisor` TEXT NULL");
            }
        }

        // 2. Columna etapa_flujo en recepcion_muestras
        if ($tablaExiste($db, 'recepcion_muestras') && !$columnaExiste($db, 'recepcion_muestras', 'etapa_flujo')) {
            $db->exec("ALTER TABLE `recepcion_muestras` ADD COLUMN `etapa_flujo` VARCHAR(40) NOT NULL DEFAULT 'recepcion_completa'");
        }

        // 3. Backfill inteligente de hojas_solicitud existentes que ya ingresaron a recepcion_muestras o tienen matriz trabajada
        if ($tablaExiste($db, 'hojas_solicitud') && $tablaExiste($db, 'recepcion_muestras')) {
            // Toda hoja que ya tenga filas en recepcion_muestras no debe quedar bloqueada en borrador
            $db->exec("
                UPDATE `hojas_solicitud` hs
                INNER JOIN `recepcion_muestras` rm ON rm.id_hoja = hs.id
                SET hs.recepcion_completa = 1,
                    hs.fecha_recepcion_completa = COALESCE(hs.fecha_recepcion_completa, rm.fecha_recepcion, NOW()),
                    hs.etapa_flujo = CASE
                        WHEN hs.etapa_flujo = 'recepcion_borrador' THEN 'recepcion_completa'
                        ELSE hs.etapa_flujo
                    END
                WHERE hs.recepcion_completa = 0
                  AND hs.motivo_reapertura_recepcion IS NULL
            ");

            // Si tiene especímenes con roturas ejecutadas, avanzar a en_ejecucion o tecnico_completo
            if ($tablaExiste($db, 'especimenes') && $tablaExiste($db, 'roturas')) {
                $db->exec("
                    UPDATE `hojas_solicitud` hs
                    INNER JOIN `recepcion_muestras` rm ON rm.id_hoja = hs.id
                    INNER JOIN `especimenes` esp ON esp.muestra_id = rm.id
                    INNER JOIN `roturas` rot ON rot.especimen_id = esp.id
                    SET hs.recepcion_completa = 1,
                        hs.etapa_flujo = 'en_ejecucion'
                    WHERE hs.etapa_flujo IN ('recepcion_borrador', 'recepcion_completa')
                      AND hs.motivo_reapertura_recepcion IS NULL
                ");
            }

            // Si la matriz asociada ya está en_revision o aprobada, reflejar etapa correspondiente
            if ($tablaExiste($db, 'cotizacion_detalles')) {
                $stmtHsMat = $db->query("
                    SELECT hs.id, hs.etapa_flujo, cd.resultados_json
                    FROM hojas_solicitud hs
                    INNER JOIN cotizacion_detalles cd ON cd.id = hs.id_detalle_cotizacion
                    WHERE cd.resultados_json IS NOT NULL AND cd.resultados_json <> '' AND cd.resultados_json <> '[]'
                ");
                if ($stmtHsMat) {
                    $stmtUpEtapa = $db->prepare("UPDATE hojas_solicitud SET recepcion_completa = 1, etapa_flujo = :etapa WHERE id = :id");
                    while ($row = $stmtHsMat->fetch(PDO::FETCH_ASSOC)) {
                        $dec = json_decode((string)$row['resultados_json'], true);
                        $estRev = (string)($dec['revision']['estado'] ?? '');
                        if ($estRev === 'aprobada' || $estRev === 'aprobado') {
                            $stmtUpEtapa->execute(['etapa' => 'informe', 'id' => (int)$row['id']]);
                        } elseif ($estRev === 'en_revision') {
                            $stmtUpEtapa->execute(['etapa' => 'matriz_en_revision', 'id' => (int)$row['id']]);
                        }
                    }
                }
            }
        }

        // 4. Backfill de plan_rotura_edades.tolerancia_horas según tabla oficial ASTM C39
        if ($tablaExiste($db, 'plan_rotura_edades')) {
            $db->exec("
                UPDATE `plan_rotura_edades`
                SET `tolerancia_horas` = CASE `edad_dias`
                    WHEN 1 THEN 0.50
                    WHEN 3 THEN 2.00
                    WHEN 7 THEN 6.00
                    WHEN 14 THEN 12.00
                    WHEN 28 THEN 20.00
                    WHEN 56 THEN 36.00
                    WHEN 90 THEN 48.00
                    ELSE ROUND(GREATEST(2.0, `edad_dias` * 0.75), 2)
                END
                WHERE `tolerancia_horas` = 24.00 AND `edad_dias` IN (1, 3, 7, 14, 28, 56, 90)
            ");
        }

        // 5. Limpiar áreas prellenadas 14.137 / 28.274 / 12.566 en filas de matriz que aún no tienen Carga (lb)
        if ($tablaExiste($db, 'cotizacion_detalles')) {
            $stmtMatClean = $db->query("
                SELECT id, resultados_json
                FROM cotizacion_detalles
                WHERE resultados_json LIKE '%14.137%'
                   OR resultados_json LIKE '%28.274%'
                   OR resultados_json LIKE '%12.566%'
            ");
            if ($stmtMatClean) {
                $stmtUpMatClean = $db->prepare("UPDATE cotizacion_detalles SET resultados_json = :json WHERE id = :id");
                while ($row = $stmtMatClean->fetch(PDO::FETCH_ASSOC)) {
                    $dec = json_decode((string)$row['resultados_json'], true);
                    if (!is_array($dec) || empty($dec['filas']) || !is_array($dec['filas'])) {
                        continue;
                    }
                    $modificado = false;
                    foreach ($dec['filas'] as $idx => $f) {
                        if (!is_array($f)) continue;
                        $carga = trim((string)($f['Carga (lb)'] ?? ($f['Carga'] ?? '')));
                        $area = trim((string)($f['Área (in²)'] ?? ($f['Area (in2)'] ?? '')));
                        if (($carga === '' || $carga === '0') && in_array($area, ['14.137', '28.274', '28.2743', '12.566', '12.5664'], true)) {
                            if (isset($f['Área (in²)'])) {
                                $dec['filas'][$idx]['Área (in²)'] = '';
                                $modificado = true;
                            }
                            if (isset($f['Area (in2)'])) {
                                $dec['filas'][$idx]['Area (in2)'] = '';
                                $modificado = true;
                            }
                        }
                    }
                    if ($modificado) {
                        $stmtUpMatClean->execute([
                            'json' => json_encode($dec, JSON_UNESCAPED_UNICODE),
                            'id' => (int)$row['id'],
                        ]);
                    }
                }
            }
        }

        // 6. Habilitar permiso 'recepcion' en roles existentes que ya tienen ordenes_servicio u operaciones
        if ($tablaExiste($db, 'roles')) {
            $colPerm = $columnaExiste($db, 'roles', 'permisos_json') ? 'permisos_json' : ($columnaExiste($db, 'roles', 'permisos') ? 'permisos' : null);
            if ($colPerm !== null) {
                $stmtRoles = $db->query("SELECT id, `{$colPerm}` AS perm_raw FROM roles");
                if ($stmtRoles) {
                    $stmtUpRol = $db->prepare("UPDATE roles SET `{$colPerm}` = :p WHERE id = :id");
                    while ($r = $stmtRoles->fetch(PDO::FETCH_ASSOC)) {
                        $p = json_decode((string)($r['perm_raw'] ?? '{}'), true);
                        if (!is_array($p) || isset($p['recepcion'])) {
                            continue;
                        }
                        if (!empty($p['ordenes_servicio']) || !empty($p['operaciones'])) {
                            $ver = !empty($p['ordenes_servicio']['ver']) || !empty($p['operaciones']['ver']) ? 1 : 0;
                            $editar = !empty($p['ordenes_servicio']['crear_editar']) || !empty($p['operaciones']['crear_editar']) ? 1 : 0;
                            $aprobar = !empty($p['ordenes_servicio']['aprobar']) || !empty($p['operaciones']['aprobar']) ? 1 : 0;
                            $p['recepcion'] = [
                                'ver' => $ver,
                                'crear_editar' => $editar,
                                'aprobar' => $aprobar,
                            ];
                            $stmtUpRol->execute([
                                'p' => json_encode($p, JSON_UNESCAPED_UNICODE),
                                'id' => (int)$r['id'],
                            ]);
                        }
                    }
                }
            }
        }
    }
}

if (isset($db) && $db instanceof PDO && basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    ejecutarMigracion040($db);
    echo "[OK] Migración 040 aplicada exitosamente.\n";
}
