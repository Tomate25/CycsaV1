<?php

namespace Cycsa\Modulos\Configuracion\Modelos;

use Cycsa\Nucleo\Conexion;
use PDO;
use Exception;

/**
 * Modelo para la gestión de Equipos de Laboratorio, Constantes de Calibración,
 * Tablas Normativas (Factor L/D ASTM C39, Densidad del Agua ASTM D854) y
 * Calibración de Picnómetros (RT-FM-56).
 */
class EquipoCalibracionModelo {

    private PDO $db;

    public function __construct(?PDO $db = null) {
        $this->db = $db ?? Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    }

    /**
     * Obtiene todos los equipos de calibración registrados con sus constantes vigentes.
     */
    public function obtenerEquipos(array $filtros = []): array {
        $sql = "SELECT * FROM equipos_calibracion WHERE 1=1";
        $params = [];

        if (!empty($filtros['categoria'])) {
            $sql .= " AND categoria = :cat";
            $params['cat'] = $filtros['categoria'];
        }
        if (isset($filtros['activo'])) {
            $sql .= " AND activo = :act";
            $params['act'] = (int)$filtros['activo'];
        }
        if (!empty($filtros['codigo'])) {
            $sql .= " AND codigo = :cod";
            $params['cod'] = $filtros['codigo'];
        }

        $sql .= " ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $equipos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        if (empty($equipos)) {
            return [];
        }

        $stmtConst = $this->db->prepare("
            SELECT * FROM equipos_calibracion_constantes
            WHERE equipo_id = :eid AND vigente = 1
            ORDER BY id ASC
        ");

        foreach ($equipos as &$eq) {
            $eq['usado_en'] = !empty($eq['usado_en_json']) ? (json_decode($eq['usado_en_json'], true) ?: []) : [];
            $eq['metadatos'] = !empty($eq['metadatos_json']) ? (json_decode($eq['metadatos_json'], true) ?: []) : [];
            $stmtConst->execute(['eid' => $eq['id']]);
            $constantes = $stmtConst->fetchAll(PDO::FETCH_ASSOC);
            foreach ($constantes as &$c) {
                $c['valor_json_decoded'] = !empty($c['valor_json']) ? json_decode($c['valor_json'], true) : null;
            }
            unset($c);
            $eq['constantes'] = $constantes;
        }
        unset($eq);

        return $equipos;
    }

    /**
     * Obtiene un equipo por su código único junto con sus constantes vigentes.
     */
    public function obtenerEquipoPorCodigo(string $codigo): ?array {
        $equipos = $this->obtenerEquipos(['codigo' => $codigo]);
        return $equipos[0] ?? null;
    }

    /**
     * Resuelve el valor de una constante de equipo a partir de su ruta ref_equipo
     * (ej. "equipos_calibracion.prensa_hidraulica_forney.pendiente").
     * Si no existe en BD o es nulo, retorna el valor de referencia ($fallback).
     *
     * @param string $refEquipo
     * @param mixed $fallback
     * @return mixed
     */
    public function obtenerValorPorRefEquipo(string $refEquipo, mixed $fallback = null): mixed {
        try {
            $stmt = $this->db->prepare("
                SELECT valor_numerico, valor_json
                FROM equipos_calibracion_constantes
                WHERE ref_equipo_alias = :ref AND vigente = 1
                ORDER BY id DESC
                LIMIT 1
            ");
            $stmt->execute(['ref' => $refEquipo]);
            $fila = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($fila) {
                if ($fila['valor_numerico'] !== null && $fila['valor_numerico'] !== '') {
                    return (float)$fila['valor_numerico'];
                }
                if (!empty($fila['valor_json'])) {
                    $decoded = json_decode($fila['valor_json'], true);
                    if ($decoded !== null) {
                        return $decoded;
                    }
                }
            }
        } catch (\Throwable $e) {
            // Si las tablas aún no están migradas o hay error de conexión, usar fallback
        }

        return $fallback;
    }

    /**
     * Actualiza o registra una constante de calibración de un equipo.
     */
    public function guardarConstanteEquipo(int $equipoId, string $clave, ?float $valorNumerico, ?array $valorJson = null, ?string $refAlias = null, ?string $unidad = null, ?string $descripcion = null): bool {
        $stmtCheck = $this->db->prepare("
            SELECT id FROM equipos_calibracion_constantes
            WHERE equipo_id = :eid AND clave = :clave AND vigente = 1
            LIMIT 1
        ");
        $stmtCheck->execute(['eid' => $equipoId, 'clave' => $clave]);
        $existenteId = (int)$stmtCheck->fetchColumn();

        $jsonStr = $valorJson !== null ? json_encode($valorJson, JSON_UNESCAPED_UNICODE) : null;

        if ($existenteId > 0) {
            $sql = "UPDATE equipos_calibracion_constantes
                    SET valor_numerico = :val_num,
                        valor_json = COALESCE(:val_json, valor_json),
                        ref_equipo_alias = COALESCE(:alias, ref_equipo_alias),
                        unidad = COALESCE(:uni, unidad),
                        descripcion = COALESCE(:desc, descripcion),
                        updated_at = NOW()
                    WHERE id = :id";
            $stmtUpd = $this->db->prepare($sql);
            return $stmtUpd->execute([
                'val_num' => $valorNumerico,
                'val_json' => $jsonStr,
                'alias' => $refAlias,
                'uni' => $unidad,
                'desc' => $descripcion,
                'id' => $existenteId
            ]);
        }

        $stmtIns = $this->db->prepare("
            INSERT INTO equipos_calibracion_constantes
                (equipo_id, clave, ref_equipo_alias, valor_numerico, valor_json, unidad, descripcion, vigente)
            VALUES
                (:eid, :clave, :alias, :val_num, :val_json, :uni, :desc, 1)
        ");
        return $stmtIns->execute([
            'eid' => $equipoId,
            'clave' => $clave,
            'alias' => $refAlias,
            'val_num' => $valorNumerico,
            'val_json' => $jsonStr,
            'uni' => $unidad,
            'desc' => $descripcion
        ]);
    }

    /**
     * Calcula el factor de corrección por esbeltez L/D según ASTM C39 / C42.
     * Regla normativa:
     * - Si L/D > 1.75: factor = 1.0 (sin corrección)
     * - Si 1.00 <= L/D <= 1.75: interpolación lineal en la tabla ASTM C39:
     *   [1.75 => 0.98, 1.50 => 0.96, 1.25 => 0.93, 1.00 => 0.87]
     * - Si L/D < 1.00: retorna 0.87 (valor mínimo tabulado).
     */
    public function calcularFactorLdAstmC39(float $relacionLd): float {
        if ($relacionLd > 1.75) {
            return 1.0;
        }

        $tablaDefault = [
            ['ld' => 1.75, 'factor' => 0.98],
            ['ld' => 1.50, 'factor' => 0.96],
            ['ld' => 1.25, 'factor' => 0.93],
            ['ld' => 1.00, 'factor' => 0.87],
        ];

        $tabla = $this->obtenerValorPorRefEquipo('equipos_calibracion.factor_correccion_ld_astm_c39', $tablaDefault);
        if (!is_array($tabla) || empty($tabla)) {
            $tabla = $tablaDefault;
        }

        // Ordenar de menor a mayor según ld
        usort($tabla, fn($a, $b) => ($a['ld'] ?? 0) <=> ($b['ld'] ?? 0));

        $n = count($tabla);
        if ($relacionLd <= (float)$tabla[0]['ld']) {
            return (float)$tabla[0]['factor'];
        }
        if ($relacionLd >= (float)$tabla[$n - 1]['ld']) {
            return (float)$tabla[$n - 1]['factor'];
        }

        for ($i = 0; $i < $n - 1; $i++) {
            $x0 = (float)$tabla[$i]['ld'];
            $y0 = (float)$tabla[$i]['factor'];
            $x1 = (float)$tabla[$i + 1]['ld'];
            $y1 = (float)$tabla[$i + 1]['factor'];

            if ($relacionLd >= $x0 && $relacionLd <= $x1) {
                if (abs($x1 - $x0) < 1e-12) {
                    return $y0;
                }
                $factor = $y0 + (($relacionLd - $x0) / ($x1 - $x0)) * ($y1 - $y0);
                return round($factor, 4);
            }
        }

        return 1.0;
    }

    /**
     * Obtiene los picnómetros calibrados (RT-FM-56).
     */
    public function obtenerPicnometros(): array {
        try {
            $stmt = $this->db->query("SELECT * FROM equipos_calibracion_picnometros WHERE activo = 1 ORDER BY id ASC");
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            foreach ($filas as &$f) {
                $f['tabla_temperatura_masa'] = !empty($f['tabla_temperatura_json']) ? (json_decode($f['tabla_temperatura_json'], true) ?: []) : [];
            }
            unset($f);
            return $filas;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Obtiene la masa del picnómetro lleno de agua a una temperatura dada (°C),
     * interpolando o buscando en su tabla de calibración RT-FM-56.
     */
    public function obtenerMasaPicnometroLlenoAgua(string $identificacion = 'N°2', float $temperaturaC = 24.0, float $fallback = 667.97): float {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM equipos_calibracion_picnometros
                WHERE identificacion = :id AND activo = 1
                LIMIT 1
            ");
            $stmt->execute(['id' => $identificacion]);
            $pic = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($pic && !empty($pic['tabla_temperatura_json'])) {
                $tabla = json_decode($pic['tabla_temperatura_json'], true) ?: [];
                foreach ($tabla as $fila) {
                    if (abs((float)($fila['temp_c'] ?? 0) - $temperaturaC) < 0.01) {
                        return (float)$fila['masa_picnometro_mas_agua_g'];
                    }
                }
            }
        } catch (\Throwable $e) {
            // Fallback
        }
        return $fallback;
    }

    /**
     * Registra o actualiza un picnómetro calibrado bajo formato RT-FM-56.
     */
    public function guardarPicnometroCalibrado(array $datos): int {
        $identificacion = trim($datos['identificacion'] ?? 'N°2');
        $volumen = (float)($datos['volumen_calibrado_cm3'] ?? 500.0);
        $masaVacio = (float)($datos['masa_picnometro_vacio_g'] ?? 169.3);
        $coefExp = (float)($datos['coef_expansion_termica_vidrio'] ?? 0.000025);
        $tabla = $datos['tabla_temperatura_masa'] ?? [];

        // Si no viene la tabla precalculada, generarla entre 15°C y 30°C según ASTM D854 / RT-FM-56
        if (empty($tabla)) {
            $densidades = [
                15 => 0.99910, 16 => 0.99895, 17 => 0.99878, 18 => 0.99860,
                19 => 0.99841, 20 => 0.99821, 21 => 0.99799, 22 => 0.99777,
                23 => 0.99754, 24 => 0.99730, 25 => 0.99705, 26 => 0.99679,
                27 => 0.99652, 28 => 0.99624, 29 => 0.99595, 30 => 0.99565
            ];
            foreach ($densidades as $temp => $rhoW) {
                $masaTotal = round($masaVacio + ($volumen * $rhoW), 2);
                $tabla[] = [
                    'temp_c' => $temp,
                    'densidad_agua_g_cm3' => $rhoW,
                    'masa_picnometro_mas_agua_g' => $masaTotal
                ];
            }
        }

        $jsonTabla = json_encode($tabla, JSON_UNESCAPED_UNICODE);

        $stmtCheck = $this->db->prepare("SELECT id FROM equipos_calibracion_picnometros WHERE identificacion = :id LIMIT 1");
        $stmtCheck->execute(['id' => $identificacion]);
        $idExistente = (int)$stmtCheck->fetchColumn();

        if ($idExistente > 0) {
            $stmtUpd = $this->db->prepare("
                UPDATE equipos_calibracion_picnometros
                SET volumen_calibrado_cm3 = :vol,
                    masa_picnometro_vacio_g = :mvac,
                    coef_expansion_termica = :coef,
                    tabla_temperatura_json = :tabla,
                    fecha_calibracion = COALESCE(:fcal, fecha_calibracion),
                    updated_at = NOW()
                WHERE id = :id
            ");
            $stmtUpd->execute([
                'vol' => $volumen,
                'mvac' => $masaVacio,
                'coef' => $coefExp,
                'tabla' => $jsonTabla,
                'fcal' => $datos['fecha_calibracion'] ?? date('Y-m-d'),
                'id' => $idExistente
            ]);
            return $idExistente;
        }

        $stmtIns = $this->db->prepare("
            INSERT INTO equipos_calibracion_picnometros
                (identificacion, volumen_calibrado_cm3, masa_picnometro_vacio_g, coef_expansion_termica, tabla_temperatura_json, fecha_calibracion, activo)
            VALUES
                (:id, :vol, :mvac, :coef, :tabla, :fcal, 1)
        ");
        $stmtIns->execute([
            'id' => $identificacion,
            'vol' => $volumen,
            'mvac' => $masaVacio,
            'coef' => $coefExp,
            'tabla' => $jsonTabla,
            'fcal' => $datos['fecha_calibracion'] ?? date('Y-m-d')
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Evalúa una hoja de calibración de picnómetros RT-FM-56 (ASTM D854) sobre sus réplicas
     * y actualiza el volumen calibrado y la curva Mpw,t del balón en equipos_calibracion_picnometros.
     *
     * @param string $identificacion Ej. 'PICNO-03'
     * @param array<int, array<string, mixed>> $replicas Arreglo de réplicas con [masa_vacio, masa_lleno, temperatura]
     * @param string|null $fechaCalibracion
     * @return array Resultado de la evaluación RT-FM-56 y el ID del picnómetro actualizado
     */
    public function calibrarPicnometroConRtFm56(string $identificacion, array $replicas, ?string $fechaCalibracion = null): array {
        $motor = new \Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio($this);
        $evaluacion = $motor->evaluarConjunto('RT-FM-56', $replicas, [
            'balon_id' => $identificacion,
            'fecha_calibracion' => $fechaCalibracion ?? date('Y-m-d'),
        ]);

        $volPromedio = (float)($evaluacion['agregaciones']['volumen_promedio'] ?? 500.0);
        $volDesv = (float)($evaluacion['agregaciones']['volumen_desv'] ?? 0.0);
        $curvaMpw = $evaluacion['agregaciones']['curva_mpw'] ?? null;
        $masaVacio = isset($replicas[0]['masa_vacio']) ? (float)$replicas[0]['masa_vacio'] : 183.49;

        $idPicno = $this->guardarPicnometroCalibrado([
            'identificacion' => $identificacion,
            'volumen_calibrado_cm3' => round($volPromedio, 4),
            'masa_picnometro_vacio_g' => $masaVacio,
            'fecha_calibracion' => $fechaCalibracion ?? date('Y-m-d'),
            'tabla_temperatura_masa' => [
                'replicas' => $evaluacion['especimenes'],
                'curva_mpw' => $curvaMpw,
                'volumen_desv_ml' => $volDesv,
                'cumple_d854' => ($volDesv <= 0.05),
            ],
        ]);

        return [
            'picnometro_id' => $idPicno,
            'identificacion' => $identificacion,
            'volumen_promedio' => $volPromedio,
            'volumen_desv' => $volDesv,
            'cumple_d854' => ($volDesv <= 0.05),
            'curva_mpw' => $curvaMpw,
            'evaluacion' => $evaluacion,
        ];
    }

    /**
     * Obtiene la configuración de correcciones dudosas para un formato específico (ej. RT-FM-61).
     * Retorna array indexado por id de corrección: ['CORR-61-02' => ['usar_corregido' => true, ...]]
     */
    public function obtenerCorreccionesFormato(string $codigoFormato): array {
        try {
            $stmt = $this->db->prepare("
                SELECT * FROM configuracion_correcciones_hc
                WHERE codigo_formato = :fmt
                ORDER BY id ASC
            ");
            $stmt->execute(['fmt' => $codigoFormato]);
            $filas = $stmt->fetchAll(PDO::FETCH_ASSOC);
            $resultado = [];
            foreach ($filas as $f) {
                $resultado[$f['correccion_id']] = $f;
            }
            return $resultado;
        } catch (\Throwable $e) {
            return [];
        }
    }

    /**
     * Actualiza el parámetro de una corrección dudosa (usar_corregido = 1 o 0) y si fue confirmada por Calidad.
     */
    public function actualizarEstadoCorreccion(string $codigoFormato, string $correccionId, bool $usarCorregido, bool $confirmadoCalidad = false, ?int $usuarioId = null): bool {
        $stmt = $this->db->prepare("
            UPDATE configuracion_correcciones_hc
            SET usar_corregido = :usar,
                confirmado_calidad = :conf,
                confirmado_por = :uid,
                fecha_confirmacion = CASE WHEN :conf2 = 1 THEN NOW() ELSE fecha_confirmacion END,
                updated_at = NOW()
            WHERE codigo_formato = :fmt AND correccion_id = :cid
        ");
        return $stmt->execute([
            'usar' => $usarCorregido ? 1 : 0,
            'conf' => $confirmadoCalidad ? 1 : 0,
            'uid' => $usuarioId,
            'conf2' => $confirmadoCalidad ? 1 : 0,
            'fmt' => $codigoFormato,
            'cid' => $correccionId
        ]);
    }
}
