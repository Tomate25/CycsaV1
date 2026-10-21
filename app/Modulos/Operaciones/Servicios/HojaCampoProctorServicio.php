<?php

namespace Cycsa\Modulos\Operaciones\Servicios;

use Cycsa\Nucleo\Conexion;
use InvalidArgumentException;
use PDO;

/**
 * Servicio de Hoja de Campo (RT-FM-54, RT-FM-55, RT-FM-62) con enlace automático al Proctor (RT-FM-27).
 *
 * Responsabilidades:
 * - Evaluar y persistir curvas Proctor (RT-FM-27: Estándar / Modificado y variantes campo / CASA MANAGUA)
 *   calculando PVSM (pvs_maximo) y Humedad Óptima (humedad_optima) mediante ajuste parabólico.
 * - Enlazar automáticamente el PVSM y la Humedad Óptima de RT-FM-27 hacia las Hojas de Campo
 *   de Reemplazo de Agua (RT-FM-54) y Cono de Arena (RT-FM-55) de la misma Orden de Servicio o muestra.
 * - Evaluar y persistir pruebas de Infiltración / Percolación (RT-FM-62).
 * - Generar filas estructuradas compatibles con el informe acreditado/no acreditado RT-FM-22 letra B.
 */
class HojaCampoProctorServicio {

    private PDO $db;
    private MotorHojasCalculoServicio $motor;

    private const FORMATOS_CAMPO = ['RT-FM-54', 'RT-FM-55', 'RT-FM-62'];
    private const VARIANTES_VALIDAS = ['ESTANDAR', 'campo', 'CASA MANAGUA'];

    public function __construct(?PDO $db = null, ?MotorHojasCalculoServicio $motor = null) {
        $this->db = $db ?? Conexion::obtenerInstancia();
        $this->motor = $motor ?? new MotorHojasCalculoServicio();
    }

    /**
     * Evalúa una hoja de cálculo Proctor (RT-FM-27) sobre sus puntos de compactación y guarda
     * el PVSM y Humedad Óptima para que las Hojas de Campo (RT-FM-54 y RT-FM-55) puedan enlazarlo.
     *
     * @param int $idOs ID de la Orden de Servicio
     * @param array<string, mixed> $datosEncabezado Datos globales (golpes, capas, masa_piston, altura_caida, volumen_molde, tipo_proctor)
     * @param array<int, array<string, mixed>> $puntosProctor Puntos de la curva Proctor (hasta 6 puntos)
     * @param int|null $muestraId ID de muestra asociada
     * @param int|null $idHojaSolicitud ID de hoja de solicitud si existe
     * @param int|null $creadoPor ID del usuario analista
     * @param string $variante 'ESTANDAR', 'campo' o 'CASA MANAGUA'
     * @return array Resultado completo de la evaluación y del registro persistido
     */
    public function registrarResultadoProctor(
        int $idOs,
        array $datosEncabezado,
        array $puntosProctor,
        ?int $muestraId = null,
        ?int $idHojaSolicitud = null,
        ?int $creadoPor = null,
        string $variante = 'ESTANDAR'
    ): array {
        $varianteNorm = $this->normalizarVariante($variante);
        $evaluacion = $this->motor->evaluarConjunto('RT-FM-27', $puntosProctor, $datosEncabezado);

        $pvsMaximo = isset($evaluacion['agregaciones']['pvs_maximo'])
            ? (float)$evaluacion['agregaciones']['pvs_maximo']
            : null;
        $humedadOptima = isset($evaluacion['agregaciones']['humedad_optima'])
            ? (float)$evaluacion['agregaciones']['humedad_optima']
            : null;

        // Permitir override explícito si el laboratorio leyó manualmente del gráfico
        if (isset($datosEncabezado['pvs_maximo_manual']) && is_numeric($datosEncabezado['pvs_maximo_manual'])) {
            $pvsMaximo = (float)$datosEncabezado['pvs_maximo_manual'];
        }
        if (isset($datosEncabezado['humedad_optima_manual']) && is_numeric($datosEncabezado['humedad_optima_manual'])) {
            $humedadOptima = (float)$datosEncabezado['humedad_optima_manual'];
        }

        $stmt = $this->db->prepare("
            INSERT INTO hojas_campo_registros (
                id_os, id_hoja_solicitud, muestra_id, proctor_muestra_id,
                codigo_formato, variante, datos_encabezado_json, datos_puntos_json,
                resultados_calculados_json, pvs_maximo_proctor, humedad_optima_proctor, creado_por
            ) VALUES (
                :id_os, :id_hoja, :muestra_id, :proctor_muestra_id,
                'RT-FM-27', :variante, :encabezado, :puntos,
                :resultados, :pvsm, :hum_opt, :creado_por
            )
        ");

        $stmt->execute([
            ':id_os' => $idOs,
            ':id_hoja' => $idHojaSolicitud,
            ':muestra_id' => $muestraId,
            ':proctor_muestra_id' => $muestraId,
            ':variante' => $varianteNorm,
            ':encabezado' => json_encode($datosEncabezado, JSON_UNESCAPED_UNICODE),
            ':puntos' => json_encode($puntosProctor, JSON_UNESCAPED_UNICODE),
            ':resultados' => json_encode($evaluacion, JSON_UNESCAPED_UNICODE),
            ':pvsm' => $pvsMaximo,
            ':hum_opt' => $humedadOptima,
            ':creado_por' => $creadoPor,
        ]);

        $registroId = (int)$this->db->lastInsertId();

        return [
            'registro_id' => $registroId,
            'codigo_formato' => 'RT-FM-27',
            'variante' => $varianteNorm,
            'pvs_maximo' => $pvsMaximo,
            'humedad_optima' => $humedadOptima,
            'evaluacion' => $evaluacion,
        ];
    }

    /**
     * Obtiene el Proctor (RT-FM-27) de referencia para una Orden de Servicio o muestra específica.
     *
     * @param int $idOs
     * @param int|null $proctorMuestraId ID de muestra Proctor o ID de registro en hojas_campo_registros
     * @return array{
     *   registro_id: int,
     *   muestra_id: int|null,
     *   pvs_maximo: float,
     *   humedad_optima: float|null,
     *   variante: string,
     *   tipo_proctor: string|null
     * }|null
     */
    public function obtenerProctorReferencia(int $idOs, ?int $proctorMuestraId = null): ?array {
        if ($proctorMuestraId !== null && $proctorMuestraId > 0) {
            $stmt = $this->db->prepare("
                SELECT *
                FROM hojas_campo_registros
                WHERE codigo_formato = 'RT-FM-27'
                  AND pvs_maximo_proctor IS NOT NULL
                  AND (muestra_id = :mid OR id = :rid)
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute([':mid' => $proctorMuestraId, ':rid' => $proctorMuestraId]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);
            if ($fila) {
                return $this->formatearFilaProctor($fila);
            }
        }

        $stmt = $this->db->prepare("
            SELECT *
            FROM hojas_campo_registros
            WHERE codigo_formato = 'RT-FM-27'
              AND id_os = :id_os
              AND pvs_maximo_proctor IS NOT NULL
            ORDER BY id DESC
            LIMIT 1
        ");
        $stmt->execute([':id_os' => $idOs]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);

        return $fila ? $this->formatearFilaProctor($fila) : null;
    }

    /**
     * Evalúa y registra una Hoja de Campo (RT-FM-54 Reemplazo de Agua, RT-FM-55 Cono de Arena o RT-FM-62 Infiltración).
     * Si el formato es RT-FM-54 o RT-FM-55 y no se suministró pvs_maximo explícito, lo enlaza automáticamente
     * desde el Proctor (RT-FM-27) de la Orden de Servicio o muestra indicada.
     *
     * @param int $idOs
     * @param string $codigoFormato 'RT-FM-54', 'RT-FM-55' o 'RT-FM-62'
     * @param array<int, array<string, mixed>> $puntosCampo Arreglo de puntos/sondeos evaluados
     * @param array<string, mixed> $datosEncabezado Datos generales del encabezado
     * @param int|null $proctorMuestraId Muestra o registro Proctor a enlazar
     * @param int|null $muestraId Muestra asociada a la hoja de campo
     * @param int|null $idHojaSolicitud ID en hojas_campo_solicitud (opcional)
     * @param int|null $creadoPor Usuario que registra
     * @param string $variante 'ESTANDAR', 'campo' o 'CASA MANAGUA'
     * @return array
     */
    public function evaluarYRegistrarHojaCampo(
        int $idOs,
        string $codigoFormato,
        array $puntosCampo,
        array $datosEncabezado = [],
        ?int $proctorMuestraId = null,
        ?int $muestraId = null,
        ?int $idHojaSolicitud = null,
        ?int $creadoPor = null,
        string $variante = 'ESTANDAR'
    ): array {
        $codigo = strtoupper(trim($codigoFormato));
        if (!in_array($codigo, self::FORMATOS_CAMPO, true)) {
            throw new InvalidArgumentException("El formato {$codigo} no pertenece al módulo de Hoja de Campo (esperado: RT-FM-54, RT-FM-55 o RT-FM-62).");
        }

        $varianteNorm = $this->normalizarVariante($variante);
        $proctorEnlazado = null;

        // Enlace automático con Proctor (RT-FM-27) para RT-FM-54 y RT-FM-55
        if (in_array($codigo, ['RT-FM-54', 'RT-FM-55'], true)) {
            $proctorEnlazado = $this->obtenerProctorReferencia($idOs, $proctorMuestraId);
            if ($proctorEnlazado !== null) {
                if (empty($datosEncabezado['pvs_maximo'])) {
                    $datosEncabezado['pvs_maximo'] = $proctorEnlazado['pvs_maximo'];
                }
                if (empty($datosEncabezado['humedad_optima']) && $proctorEnlazado['humedad_optima'] !== null) {
                    $datosEncabezado['humedad_optima'] = $proctorEnlazado['humedad_optima'];
                }
            }
        }

        $evaluacion = $this->motor->evaluarConjunto($codigo, $puntosCampo, $datosEncabezado);

        $pvsmUsado = isset($datosEncabezado['pvs_maximo']) && is_numeric($datosEncabezado['pvs_maximo'])
            ? (float)$datosEncabezado['pvs_maximo']
            : ($proctorEnlazado['pvs_maximo'] ?? null);
        $humOptUsada = isset($datosEncabezado['humedad_optima']) && is_numeric($datosEncabezado['humedad_optima'])
            ? (float)$datosEncabezado['humedad_optima']
            : ($proctorEnlazado['humedad_optima'] ?? null);

        // Si algún punto individual trajo su propio pvs_maximo y el encabezado no tenía
        if ($pvsmUsado === null && !empty($evaluacion['especimenes'][0]['valores']['pvs_maximo'])) {
            $pvsmUsado = (float)$evaluacion['especimenes'][0]['valores']['pvs_maximo'];
        }

        // Construir filas compatibles con RT-FM-22 B para compactación
        $filasRtFm22B = [];
        if (in_array($codigo, ['RT-FM-54', 'RT-FM-55'], true)) {
            $filasRtFm22B = $this->construirFilasRtFm22B($codigo, $evaluacion['especimenes'], $datosEncabezado, $pvsmUsado, $humOptUsada);
        }

        $stmt = $this->db->prepare("
            INSERT INTO hojas_campo_registros (
                id_os, id_hoja_solicitud, muestra_id, proctor_muestra_id,
                codigo_formato, variante, datos_encabezado_json, datos_puntos_json,
                resultados_calculados_json, pvs_maximo_proctor, humedad_optima_proctor, creado_por
            ) VALUES (
                :id_os, :id_hoja, :muestra_id, :proctor_muestra_id,
                :codigo, :variante, :encabezado, :puntos,
                :resultados, :pvsm, :hum_opt, :creado_por
            )
        ");

        $payloadResultados = [
            'evaluacion' => $evaluacion,
            'filas_rt_fm_22_b' => $filasRtFm22B,
            'proctor_enlazado' => $proctorEnlazado,
        ];

        $stmt->execute([
            ':id_os' => $idOs,
            ':id_hoja' => $idHojaSolicitud,
            ':muestra_id' => $muestraId,
            ':proctor_muestra_id' => $proctorMuestraId ?? ($proctorEnlazado['muestra_id'] ?? null),
            ':codigo' => $codigo,
            ':variante' => $varianteNorm,
            ':encabezado' => json_encode($datosEncabezado, JSON_UNESCAPED_UNICODE),
            ':puntos' => json_encode($puntosCampo, JSON_UNESCAPED_UNICODE),
            ':resultados' => json_encode($payloadResultados, JSON_UNESCAPED_UNICODE),
            ':pvsm' => $pvsmUsado,
            ':hum_opt' => $humOptUsada,
            ':creado_por' => $creadoPor,
        ]);

        $registroId = (int)$this->db->lastInsertId();

        return [
            'registro_id' => $registroId,
            'codigo_formato' => $codigo,
            'variante' => $varianteNorm,
            'pvs_maximo' => $pvsmUsado,
            'humedad_optima' => $humOptUsada,
            'proctor_enlazado' => $proctorEnlazado,
            'evaluacion' => $evaluacion,
            'filas_rt_fm_22_b' => $filasRtFm22B,
        ];
    }

    /**
     * Construye las filas de salida para alimentar los informes RT-FM-22 B
     * (Compactación por cono de arena y Compactación por reemplazo de agua).
     */
    private function construirFilasRtFm22B(
        string $codigoFormato,
        array $especimenesEvaluados,
        array $datosEncabezado,
        ?float $pvsmGlobal,
        ?float $humOptGlobal
    ): array {
        $filas = [];
        foreach ($especimenesEvaluados as $idx => $esp) {
            $v = $esp['valores'] ?? [];
            $c = $esp['calculados'] ?? [];
            $r = $esp['redondeados'] ?? [];

            $filas[] = [
                'item' => $idx + 1,
                'codigo_formato_origen' => $codigoFormato,
                'codigo_muestra' => $v['codigo_muestra'] ?? ($datosEncabezado['codigo_muestra'] ?? ''),
                'fecha_ensayo' => $v['fecha_ensayo'] ?? ($datosEncabezado['fecha_ensayo'] ?? date('Y-m-d')),
                'ubicacion_sondeo' => $v['procedencia'] ?? ($v['ubicacion'] ?? ($datosEncabezado['procedencia'] ?? '')),
                'coord_norte' => $v['coord_norte'] ?? ($datosEncabezado['coord_norte'] ?? null),
                'coord_este' => $v['coord_este'] ?? ($datosEncabezado['coord_este'] ?? null),
                'capa' => $v['capa'] ?? ($datosEncabezado['capa'] ?? ''),
                'profundidad_cm' => $v['profundidad'] ?? ($datosEncabezado['profundidad'] ?? ''),
                'pvh_in_situ_kg_m3' => $r['densidad_humeda'] ?? $c['densidad_humeda'] ?? null,
                'humedad_obtener_pct' => $r['humedad'] ?? $c['humedad'] ?? null,
                'pvs_in_situ_kg_m3' => $r['densidad_seca'] ?? $c['densidad_seca'] ?? null,
                'pvsm_proctor_kg_m3' => isset($v['pvs_maximo']) ? (float)$v['pvs_maximo'] : $pvsmGlobal,
                'humedad_optima_pct' => $humOptGlobal,
                'grado_compactacion_pct' => $r['compactacion'] ?? $c['compactacion'] ?? null,
                'valores_crudos' => $c,
            ];
        }
        return $filas;
    }

    private function formatearFilaProctor(array $fila): array {
        $enc = json_decode($fila['datos_encabezado_json'] ?? '{}', true) ?: [];
        return [
            'registro_id' => (int)$fila['id'],
            'muestra_id' => $fila['muestra_id'] !== null ? (int)$fila['muestra_id'] : null,
            'pvs_maximo' => (float)$fila['pvs_maximo_proctor'],
            'humedad_optima' => $fila['humedad_optima_proctor'] !== null ? (float)$fila['humedad_optima_proctor'] : null,
            'variante' => (string)($fila['variante'] ?? 'ESTANDAR'),
            'tipo_proctor' => $enc['tipo_proctor'] ?? 'Proctor Estandar',
        ];
    }

    private function normalizarVariante(string $variante): string {
        $limpia = trim($variante);
        foreach (self::VARIANTES_VALIDAS as $v) {
            if (strcasecmp($limpia, $v) === 0) {
                return $v;
            }
        }
        return 'ESTANDAR';
    }
}
