<?php

namespace Cycsa\Modulos\Configuracion\Servicios;

use PDO;
use Throwable;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Operaciones\Servicios\SembradorPlantillasHcServicio;

class MigradorServicio {

    /**
     * Verifica y ejecuta de forma idempotente las migraciones pendientes en la base de datos
     * (Especialmente útil en hosting compartido como Bluehost cPanel donde no se dispone de terminal CLI).
     */
    public static function asegurarTablas(PDO $db): array {
        $resultados = [];

        try {
            // =========================================================================
            // FASE 1: Migración 005 - Plantillas Dinámicas Relacionales (ISO/IEC 17025)
            // =========================================================================
            $tablas = $db->query("SHOW TABLES LIKE 'plantillas'")->fetchAll(PDO::FETCH_COLUMN);
            if (empty($tablas)) {
                $ruta005 = dirname(__DIR__, 4) . '/database/migrations/005_plantillas_dinamicas_relacionales.php';
                if (file_exists($ruta005)) {
                    ob_start();
                    include $ruta005;
                    $resultados['005'] = ob_get_clean();
                }
            }

            // =========================================================================
            // FASE 2: Migración 006 - Bandeja Anónima y Programa de Roturas
            // =========================================================================
            $tablasEsp = $db->query("SHOW TABLES LIKE 'especimenes'")->fetchAll(PDO::FETCH_COLUMN);
            if (empty($tablasEsp)) {
                $ruta006 = dirname(__DIR__, 4) . '/database/migrations/006_bandeja_anonima_y_programa_roturas.php';
                if (file_exists($ruta006)) {
                    ob_start();
                    include $ruta006;
                    $resultados['006'] = ob_get_clean();
                }
            }

            // =========================================================================
            // FASE 3: Migración 007 - id_hoja en recepcion_muestras
            // =========================================================================
            $colsRec = $db->query("SHOW COLUMNS FROM `recepcion_muestras` LIKE 'id_hoja'")->fetchAll();
            if (empty($colsRec)) {
                $ruta007 = dirname(__DIR__, 4) . '/database/migrations/007_recepcion_muestras_id_hoja.php';
                if (file_exists($ruta007)) {
                    ob_start();
                    include $ruta007;
                    $resultados['007'] = ob_get_clean();
                }
            }

            // =========================================================================
            // FASE 4: Migración 008 - Módulo de Equipos y Calibración + Hojas de Campo
            // =========================================================================
            $tablasEq = $db->query("SHOW TABLES LIKE 'equipos_calibracion'")->fetchAll(PDO::FETCH_COLUMN);
            if (empty($tablasEq)) {
                $ruta008 = dirname(__DIR__, 4) . '/database/migrations/008_equipos_calibracion_y_catalogo_hc.php';
                if (file_exists($ruta008)) {
                    ob_start();
                    include $ruta008;
                    $resultados['008'] = ob_get_clean();
                }
            }

            // =========================================================================
            // FASE 5: Migraciones 009, 010, 011 - Plantillas de Ensayos HC
            // =========================================================================
            $tablasPlt = $db->query("SHOW TABLES LIKE 'plantillas'")->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($tablasPlt)) {
                $countHc = (int)$db->query("SELECT COUNT(*) FROM plantillas WHERE codigo LIKE 'PL-RT-FM-%' OR codigo LIKE 'RT-FM-%'")->fetchColumn();
                if ($countHc < 5) {
                    foreach (['009_plantillas_roturas_hc.php', '010_hojas_campo_compactacion_hc.php', '011_plantillas_suelos_agregados_y_fm56.php'] as $migFile) {
                        $rutaMig = dirname(__DIR__, 4) . '/database/migrations/' . $migFile;
                        if (file_exists($rutaMig)) {
                            ob_start();
                            include $rutaMig;
                            $resultados[$migFile] = ob_get_clean();
                        }
                    }
                }
            }
        } catch (Throwable $e) {
            error_log("Error en MigradorServicio: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
            $resultados['error'] = $e->getMessage();
        }

        return $resultados;
    }
}
