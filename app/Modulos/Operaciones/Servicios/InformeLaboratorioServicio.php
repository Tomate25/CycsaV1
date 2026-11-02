<?php

namespace Cycsa\Modulos\Operaciones\Servicios;

use Cycsa\Nucleo\Conexion;
use PDO;

/**
 * Servicio / Fachada Unificada de Informes de Laboratorio (Lab P2)
 *
 * Unifica la lectura y gestión de:
 * 1. informes_control (Control de calidad, compactación y control operativo)
 * 2. informes_rotura (Roturas de cilindros, núcleos, flexión y compresión)
 * 3. Consolidado multi-muestra analítico (estadísticas agregadas de ensayos)
 * 4. Historial inmutable de trazabilidad de versiones (ISO/IEC 17025)
 */
class InformeLaboratorioServicio {

    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?: Conexion::obtenerInstancia();
    }

    /**
     * Retorna una lista normalizada de todos los informes técnicos (control + roturas) de una O/S.
     *
     * @param int $idOS ID de la Orden de Servicio
     * @return array<int, array<string, mixed>> Lista unificada ordenada cronológicamente descendente
     */
    public function obtenerInformesUnificadosOS(int $idOS): array {
        if ($idOS <= 0) {
            return [];
        }

        $informes = [];

        // 1. Obtener informes_control asociados a la O/S
        try {
            $stmtCtrl = $this->db->prepare("
                SELECT 
                    ic.id,
                    'control' AS fuente,
                    COALESCE(ic.tipo_informe, 'control') AS tipo,
                    ic.codigo_informe,
                    ic.version,
                    ic.estado_aprobacion AS estado,
                    ic.motivo_reemplazo AS motivo,
                    NULL AS sustituye_a,
                    NULL AS sha256,
                    ic.ruta_archivo_pdf AS pdf,
                    ic.fecha_generacion AS fecha,
                    ic.id_lote
                FROM informes_control ic
                JOIN lotes_muestras lm ON ic.id_lote = lm.id
                JOIN recepcion_muestras rm ON lm.id_muestra = rm.id
                WHERE rm.id_os = :id_os
                ORDER BY ic.id DESC
            ");
            $stmtCtrl->execute(['id_os' => $idOS]);
            $ctrlRows = $stmtCtrl->fetchAll(PDO::FETCH_ASSOC);
            foreach ($ctrlRows as $row) {
                $informes[] = [
                    'id' => (int)$row['id'],
                    'fuente' => 'control',
                    'tipo' => $row['tipo'],
                    'codigo_informe' => $row['codigo_informe'],
                    'version' => (int)($row['version'] ?? 1),
                    'estado' => $row['estado'] ?? 'borrador',
                    'motivo' => $row['motivo'],
                    'sustituye_a' => $row['sustituye_a'],
                    'sha256' => $row['sha256'],
                    'pdf' => $row['pdf'],
                    'fecha' => $row['fecha'],
                    'id_referencia' => (int)$row['id_lote']
                ];
            }
        } catch (\Throwable $e) {
            error_log("Error cargando informes_control en InformeLaboratorioServicio: " . $e->getMessage());
        }

        // 2. Obtener informes_rotura asociados a la O/S
        try {
            $stmtRot = $this->db->prepare("
                SELECT 
                    ir.id,
                    'rotura' AS fuente,
                    ir.tipo,
                    ir.numero AS codigo_informe,
                    ir.version,
                    ir.estado,
                    ir.motivo_reemision AS motivo,
                    ir.sustituye_a,
                    ir.sha256,
                    ir.pdf,
                    ir.created_at AS fecha,
                    ir.muestra_id
                FROM informes_rotura ir
                JOIN recepcion_muestras rm ON ir.muestra_id = rm.id
                WHERE rm.id_os = :id_os
                ORDER BY ir.id DESC
            ");
            $stmtRot->execute(['id_os' => $idOS]);
            $rotRows = $stmtRot->fetchAll(PDO::FETCH_ASSOC);
            foreach ($rotRows as $row) {
                $informes[] = [
                    'id' => (int)$row['id'],
                    'fuente' => 'rotura',
                    'tipo' => $row['tipo'],
                    'codigo_informe' => $row['codigo_informe'],
                    'version' => (int)($row['version'] ?? 1),
                    'estado' => $row['estado'] ?? 'borrador',
                    'motivo' => $row['motivo'],
                    'sustituye_a' => $row['sustituye_a'],
                    'sha256' => $row['sha256'],
                    'pdf' => $row['pdf'],
                    'fecha' => $row['fecha'],
                    'id_referencia' => (int)$row['muestra_id']
                ];
            }
        } catch (\Throwable $e) {
            error_log("Error cargando informes_rotura en InformeLaboratorioServicio: " . $e->getMessage());
        }

        // Ordenar unificado por fecha descendente
        usort($informes, function ($a, $b) {
            return strcmp((string)($b['fecha'] ?? ''), (string)($a['fecha'] ?? ''));
        });

        return $informes;
    }

    /**
     * Genera un análisis consolidado multi-muestra para un conjunto de especímenes de una O/S.
     *
     * @param int $idOS ID de la Orden de Servicio
     * @param array<int> $muestraIds IDs específicos de muestra (opcional; si vacío toma todas las de la O/S)
     * @return array<string, mixed> Resumen estadístico consolidado multi-muestra
     */
    public function obtenerConsolidadoMultiMuestra(int $idOS, array $muestraIds = []): array {
        if ($idOS <= 0) {
            return ['exito' => false, 'error' => 'Orden de Servicio inválida'];
        }

        $whereMuestras = "rm.id_os = :id_os";
        $params = ['id_os' => $idOS];

        if (!empty($muestraIds)) {
            $inPlaceholders = implode(',', array_map('intval', $muestraIds));
            $whereMuestras .= " AND rm.id IN ($inPlaceholders)";
        }

        // Obtener roturas realizadas por muestra
        $sql = "
            SELECT 
                rm.id AS muestra_id,
                rm.codigo_muestra,
                rm.observaciones AS muestra_descripcion,
                pre.edad_dias,
                pre.fecha_programada,
                r.fecha_real AS fecha_rotura_real,
                ROUND(r.esfuerzo_kgcm2 * 0.0980665, 2) AS esfuerzo_mpa,
                r.esfuerzo_kgcm2 AS esfuerzo_kg_cm2,
                r.esfuerzo_psi,
                r.porcentaje_fc AS porcentaje_cumplimiento,
                r.tipo_falla
            FROM recepcion_muestras rm
            JOIN planes_rotura pr ON pr.muestra_id = rm.id
            JOIN plan_rotura_edades pre ON pre.plan_rotura_id = pr.id
            LEFT JOIN roturas r ON r.plan_edad_id = pre.id
            WHERE $whereMuestras
            ORDER BY rm.id ASC, pre.edad_dias ASC
        ";

        try {
            $stmt = $this->db->prepare($sql);
            $stmt->execute($params);
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);

            if (empty($filas)) {
                return [
                    'exito' => true,
                    'id_os' => $idOS,
                    'version_consolidado' => 1,
                    'total_muestras' => 0,
                    'total_ensayos' => 0,
                    'promedio_esfuerzo_mpa' => 0.0,
                    'promedio_cumplimiento' => 0.0,
                    'desviacion_estandar_mpa' => 0.0,
                    'coeficiente_variacion' => 0.0,
                    'muestras' => []
                ];
            }

            $esfuerzosMpa = [];
            $cumplimientos = [];
            $muestrasAgrupadas = [];

            foreach ($filas as $f) {
                $mId = (int)$f['muestra_id'];
                if (!isset($muestrasAgrupadas[$mId])) {
                    $muestrasAgrupadas[$mId] = [
                        'muestra_id' => $mId,
                        'codigo_muestra' => $f['codigo_muestra'],
                        'descripcion' => $f['muestra_descripcion'],
                        'edades' => []
                    ];
                }

                if ($f['esfuerzo_mpa'] !== null) {
                    $valMpa = (float)$f['esfuerzo_mpa'];
                    $esfuerzosMpa[] = $valMpa;
                    $cumplimientos[] = (float)($f['porcentaje_cumplimiento'] ?? 0);
                    $muestrasAgrupadas[$mId]['edades'][] = [
                        'edad_dias' => (int)$f['edad_dias'],
                        'fecha_rotura' => $f['fecha_rotura_real'] ?: $f['fecha_programada'],
                        'esfuerzo_mpa' => $valMpa,
                        'esfuerzo_kg_cm2' => (float)($f['esfuerzo_kg_cm2'] ?? 0),
                        'porcentaje_cumplimiento' => (float)($f['porcentaje_cumplimiento'] ?? 0),
                        'tipo_falla' => $f['tipo_falla']
                    ];
                }
            }

            $n = count($esfuerzosMpa);
            $promedioMpa = $n > 0 ? array_sum($esfuerzosMpa) / $n : 0.0;
            $promedioCump = $n > 0 ? array_sum($cumplimientos) / $n : 0.0;

            // Desviación estándar muestral
            $desvEstandar = 0.0;
            if ($n > 1) {
                $sumaDifCuad = 0.0;
                foreach ($esfuerzosMpa as $val) {
                    $sumaDifCuad += pow($val - $promedioMpa, 2);
                }
                $desvEstandar = sqrt($sumaDifCuad / ($n - 1));
            }

            $coefVariacion = $promedioMpa > 0 ? ($desvEstandar / $promedioMpa) * 100 : 0.0;

            // Versión real máxima acumulada del consolidado
            $stmtVer = $this->db->prepare("
                SELECT COALESCE(MAX(ir.version), 1)
                FROM informes_rotura ir
                JOIN recepcion_muestras rm ON ir.muestra_id = rm.id
                WHERE rm.id_os = :id_os AND ir.tipo = 'consolidado'
            ");
            $stmtVer->execute(['id_os' => $idOS]);
            $versionConsolidado = (int)$stmtVer->fetchColumn() ?: 1;

            return [
                'exito' => true,
                'id_os' => $idOS,
                'version_consolidado' => $versionConsolidado,
                'total_muestras' => count($muestrasAgrupadas),
                'total_ensayos' => $n,
                'promedio_esfuerzo_mpa' => round($promedioMpa, 2),
                'promedio_cumplimiento' => round($promedioCump, 2),
                'desviacion_estandar_mpa' => round($desvEstandar, 2),
                'coeficiente_variacion' => round($coefVariacion, 2),
                'muestras' => array_values($muestrasAgrupadas)
            ];
        } catch (\Throwable $e) {
            error_log("Error en obtenerConsolidadoMultiMuestra: " . $e->getMessage());
            return ['exito' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * Traza la cadena de versiones históricas (N, N-1, ...) de un informe emitido.
     *
     * @param int $informeId ID del informe
     * @param string $fuente 'rotura' o 'control'
     * @return array<int, array<string, mixed>> Lista cronológica de versiones históricas
     */
    public function obtenerHistorialVersiones(int $informeId, string $fuente = 'rotura'): array {
        if ($informeId <= 0) {
            return [];
        }

        $historial = [];

        try {
            if ($fuente === 'rotura') {
                $stmt = $this->db->prepare("SELECT * FROM informes_rotura WHERE id = :id");
                $stmt->execute(['id' => $informeId]);
                $actual = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$actual) {
                    return [];
                }

                $numeroBase = preg_replace('/-V\d+$/i', '', $actual['numero']);

                // Buscar todos los informes relacionados con este número o sustitución
                $stmtChain = $this->db->prepare("
                    SELECT ir.*, u.nombre AS usuario_aprobador_nombre
                    FROM informes_rotura ir
                    LEFT JOIN usuarios u ON ir.aprobado_por = u.id
                    WHERE ir.muestra_id = :mid AND (ir.numero LIKE :pat OR ir.sustituye_a IS NOT NULL)
                    ORDER BY ir.version DESC, ir.id DESC
                ");
                $stmtChain->execute([
                    'mid' => $actual['muestra_id'],
                    'pat' => $numeroBase . '%'
                ]);
                $filas = $stmtChain->fetchAll(PDO::FETCH_ASSOC);

                foreach ($filas as $f) {
                    $historial[] = [
                        'id' => (int)$f['id'],
                        'numero' => $f['numero'],
                        'version' => (int)$f['version'],
                        'estado' => $f['estado'],
                        'motivo' => $f['motivo_reemision'] ?: 'Emisión inicial de informe',
                        'sustituye_a' => $f['sustituye_a'],
                        'sha256' => $f['sha256'],
                        'usuario' => $f['usuario_aprobador_nombre'] ?: 'Calidad / Administrador',
                        'fecha' => $f['fecha_aprobacion'] ?: $f['created_at']
                    ];
                }
            } else {
                // Para informes_control
                $stmt = $this->db->prepare("SELECT * FROM informes_control WHERE id = :id");
                $stmt->execute(['id' => $informeId]);
                $actual = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($actual) {
                    $stmtAll = $this->db->prepare("
                        SELECT ic.*, u.nombre AS usuario_supervisor_nombre
                        FROM informes_control ic
                        LEFT JOIN usuarios u ON ic.usuario_supervisor_id = u.id
                        WHERE ic.id_lote = :lote AND ic.tipo_informe = :tipo
                        ORDER BY ic.version DESC
                    ");
                    $stmtAll->execute([
                        'lote' => $actual['id_lote'],
                        'tipo' => $actual['tipo_informe']
                    ]);
                    $filas = $stmtAll->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($filas as $f) {
                        $historial[] = [
                            'id' => (int)$f['id'],
                            'numero' => $f['codigo_informe'],
                            'version' => (int)$f['version'],
                            'estado' => $f['estado_aprobacion'],
                            'motivo' => $f['motivo_reemplazo'] ?: 'Emisión de informe de control',
                            'sustituye_a' => null,
                            'sha256' => null,
                            'usuario' => $f['usuario_supervisor_nombre'] ?: 'Supervisor',
                            'fecha' => $f['fecha_generacion']
                        ];
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log("Error obteniendo historial versiones en InformeLaboratorioServicio: " . $e->getMessage());
        }

        return $historial;
    }
}
