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

            // =========================================================================
            // FASE 6: Migración 012 - Compras, Inventario y Maestro Proveedores
            // =========================================================================
            $tablasProv = $db->query("SHOW TABLES LIKE 'proveedores'")->fetchAll(PDO::FETCH_COLUMN);
            if (empty($tablasProv)) {
                $ruta012 = dirname(__DIR__, 4) . '/database/migrations/012_compras_inventario_proveedores.php';
                if (file_exists($ruta012)) {
                    ob_start();
                    include $ruta012;
                    $resultados['012'] = ob_get_clean();
                }
            }

            // =========================================================================
            // FASE 7: Migración 014 - Reemisión de Informes de Rotura (TAREA-044)
            // =========================================================================
            $tablasInf = $db->query("SHOW TABLES LIKE 'informes_rotura'")->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($tablasInf)) {
                $colsSha = $db->query("SHOW COLUMNS FROM `informes_rotura` LIKE 'sha256'")->fetchAll();
                $colsMot = $db->query("SHOW COLUMNS FROM `informes_rotura` LIKE 'motivo_reemision'")->fetchAll();
                if (empty($colsSha) || empty($colsMot)) {
                    $ruta014 = dirname(__DIR__, 4) . '/database/migrations/014_reemision_informes_rotura.php';
                    if (file_exists($ruta014)) {
                        ob_start();
                        include $ruta014;
                        $resultados['014'] = ob_get_clean();
                    } else {
                        try {
                            $db->exec("ALTER TABLE `informes_rotura` MODIFY COLUMN `estado` ENUM('borrador','en_revision','aprobado','devuelto','anulado') DEFAULT 'borrador'");
                        } catch (Throwable $t) {
                            error_log("MigradorServicio FASE 7 aviso estado: " . $t->getMessage());
                        }
                        if (empty($colsSha)) {
                            $db->exec("ALTER TABLE `informes_rotura` ADD COLUMN `sha256` VARCHAR(64) NULL AFTER `pdf`");
                        }
                        if (empty($colsMot)) {
                            $db->exec("ALTER TABLE `informes_rotura` ADD COLUMN `motivo_reemision` TEXT NULL AFTER `motivo_devolucion`");
                        }
                        $resultados['014'] = "[OK] Esquema informes_rotura actualizado directamente.";
                    }
                }
            }

            // =========================================================================
            // FASE 8: Migración 013 - Puente Contable Dinámico config_cuentas (TAREA-046)
            // =========================================================================
            $tablasCfg = $db->query("SHOW TABLES LIKE 'config_cuentas'")->fetchAll(PDO::FETCH_COLUMN);
            if (empty($tablasCfg)) {
                $ruta013Cfg = dirname(__DIR__, 4) . '/database/migrations/013_puente_contable_config_cuentas.php';
                if (file_exists($ruta013Cfg)) {
                    ob_start();
                    include $ruta013Cfg;
                    $resultados['013_config_cuentas'] = ob_get_clean();
                }
            }

            // =========================================================================
            // FASE 9: Migración 015 - Enlace CXP a Proveedores, OC y Recepciones (TAREA-047)
            // =========================================================================
            $tablasCxp = $db->query("SHOW TABLES LIKE 'cuentas_por_pagar'")->fetchAll(PDO::FETCH_COLUMN);
            if (!empty($tablasCxp)) {
                $colsCxp = $db->query("SHOW COLUMNS FROM `cuentas_por_pagar`")->fetchAll(PDO::FETCH_COLUMN);
                $colsCxpLower = array_map('strtolower', $colsCxp);
                if (!in_array('proveedor_id', $colsCxpLower) || !in_array('orden_compra_id', $colsCxpLower) || !in_array('recepcion_id', $colsCxpLower)) {
                    $ruta015 = dirname(__DIR__, 4) . '/database/migrations/015_cxp_proveedores_enlace.php';
                    if (file_exists($ruta015)) {
                        ob_start();
                        include $ruta015;
                        $resultados['015_cxp_enlace'] = ob_get_clean();
                    }
                }
            }

            // =========================================================================
            // FASE 10: Migración 016 - Detalle CXP Multi-Línea y Retenciones (TAREA-049)
            // =========================================================================
            $tablasCxpDetalle = $db->query("SHOW TABLES LIKE 'cuentas_por_pagar_detalle'")->fetchAll(PDO::FETCH_COLUMN);
            if (empty($tablasCxpDetalle)) {
                $ruta016 = dirname(__DIR__, 4) . '/database/migrations/016_cxp_lineas_detalle_gastos.php';
                if (file_exists($ruta016)) {
                    ob_start();
                    include $ruta016;
                    $resultados['016_cxp_lineas_detalle'] = ob_get_clean();
                }
            }

            // =========================================================================
            // FASE 11: Migración 017 - Nómina, Anticipos y Provisiones Laborales (TAREA-050)
            // =========================================================================
            $tablasNom = $db->query("SHOW TABLES LIKE 'rrhh_nominas'")->fetchAll(PDO::FETCH_COLUMN);
            if (empty($tablasNom)) {
                $ruta017 = dirname(__DIR__, 4) . '/database/migrations/017_rrhh_nomina_anticipos.php';
                if (file_exists($ruta017)) {
                    ob_start();
                    include $ruta017;
                    $resultados['017_rrhh_nomina'] = ob_get_clean();
                }
            }

            // =========================================================================
            // FASE 12: Migración 018 - Activos Fijos y Depreciación Mensual (TAREA-051)
            // =========================================================================
            $tablasAf = $db->query("SHOW TABLES LIKE 'activos_fijos'")->fetchAll(PDO::FETCH_COLUMN);
            if (empty($tablasAf)) {
                $ruta018 = dirname(__DIR__, 4) . '/database/migrations/018_activos_fijos_depreciacion.php';
                if (file_exists($ruta018)) {
                    ob_start();
                    include $ruta018;
                    $resultados['018_activos_fijos'] = ob_get_clean();
                }
            }

            // =========================================================================
            // FASE 13: Migración 019 - Préstamos Bancarios y Cuotas (TAREA-052)
            // =========================================================================
            $tablasPrestamos = $db->query("SHOW TABLES LIKE 'prestamos_bancarios'")->fetchAll(PDO::FETCH_COLUMN);
            if (empty($tablasPrestamos)) {
                $ruta019 = dirname(__DIR__, 4) . '/database/migrations/019_prestamos_bancarios.php';
                if (file_exists($ruta019)) {
                    ob_start();
                    include $ruta019;
                    $resultados['019_prestamos_bancarios'] = ob_get_clean();
                }
            }

            // =========================================================================
            // FASE 14: Migración 020 - Proyectos y Órdenes de Obra (TAREA-053)
            // =========================================================================
            $tablasProy = $db->query("SHOW TABLES LIKE 'proyectos'")->fetchAll(PDO::FETCH_COLUMN);
            $ejecutar020 = empty($tablasProy);
            if (!$ejecutar020) {
                // Verificar si falta columna proyecto_id en alguna de las tablas relacionadas
                try {
                    $colOc = $db->query("SHOW COLUMNS FROM `ordenes_compra` LIKE 'proyecto_id'")->fetch();
                    $colCxp = $db->query("SHOW COLUMNS FROM `cuentas_por_pagar_detalle` LIKE 'proyecto_id'")->fetch();
                    if (!$colOc || !$colCxp) {
                        $ejecutar020 = true;
                    }
                } catch (Throwable $e) {
                    $ejecutar020 = true;
                }
            }

            if ($ejecutar020) {
                $ruta020 = dirname(__DIR__, 4) . '/database/migrations/020_proyectos_ordenes_obra.php';
                if (file_exists($ruta020)) {
                    ob_start();
                    include $ruta020;
                    $resultados['020_proyectos'] = ob_get_clean();
                }
            }
        } catch (Throwable $e) {
            error_log("Error en MigradorServicio: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
            $resultados['error'] = $e->getMessage();
        }

        return $resultados;
    }
}
