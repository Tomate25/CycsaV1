<?php

namespace Cycsa\Modulos\Operaciones\Servicios;

use Cycsa\Nucleo\EvaluadorFormula;

/**
 * Registro de manejadores con nombre para correcciones de Hojas de Cálculo
 * que requieren lógica procedimental (agregaciones, conversiones de unidad explícita,
 * validaciones normativas de réplicas, etc.) y que no se pueden expresar puramente
 * con EvaluadorFormula en el JSON.
 *
 * Cada manejador corresponde a un código referenciado opcionalmente desde el JSON
 * en el campo "manejador" (ej. "FM42_01", "FM42_02", "FM56_01", "FM56_03", "FM58_01",
 * "FM59_02", "FM62_03", "FM63_03", "FM64_04").
 */
class ManejadoresCorreccionHc {

    /**
     * Ejecuta preprocesamiento de entradas para un manejador.
     */
    public static function preprocesar(string $manejador, array $entradas, bool $usarCorregido, array $contexto = []): array {
        $metodo = 'pre_' . $manejador;
        if (method_exists(self::class, $metodo)) {
            return self::$metodo($entradas, $usarCorregido, $contexto);
        }
        return $entradas;
    }

    /**
     * Ejecuta postprocesamiento de valores calculados para un manejador.
     */
    public static function postprocesar(
        string $manejador,
        array &$valores,
        bool $usarCorregido,
        array $contexto = []
    ): void {
        $metodo = 'post_' . $manejador;
        if (method_exists(self::class, $metodo)) {
            self::$metodo($valores, $usarCorregido, $contexto);
        }
    }

    /**
     * Ejecuta validación normativa para un manejador.
     * Retorna array con ['valido' => bool, 'aviso' => ?string]
     */
    public static function validar(string $manejador, array $valores, bool $usarCorregido, array $contexto = []): ?array {
        $metodo = 'val_' . $manejador;
        if (method_exists(self::class, $metodo)) {
            return self::$metodo($valores, $usarCorregido, $contexto);
        }
        return null;
    }

    // =========================================================================
    // FM42-01: Validación de golpes en rango [20, 30] para Método B (ASTM D4318)
    // Solo validación normativa
    // =========================================================================
    public static function post_FM42_01(array &$valores, bool $usarCorregido, array $contexto = []): void {
        if (isset($valores['golpes'])) {
            $g = (float)$valores['golpes'];
            $valores['golpes_en_rango_metodo_b'] = $usarCorregido ? ($g >= 20.0 && $g <= 30.0) : true;
        }
    }

    /**
     * Preprocesamiento de especímenes a nivel de conjunto para un manejador.
     */
    public static function preprocesarConjunto(
        string $manejador,
        array &$listaEspecimenes,
        array $datosGlobales,
        bool $usarCorregido
    ): void {
        $metodo = 'pre_conjunto_' . $manejador;
        if (method_exists(self::class, $metodo)) {
            self::$metodo($listaEspecimenes, $datosGlobales, $usarCorregido);
        }
    }

    /**
     * Interceptación de cálculo de agregación para un manejador.
     */
    public static function evaluarAgregacion(
        string $manejador,
        string $codigoAgregacion,
        array $filasValores,
        array $datosGlobales,
        bool $usarCorregido,
        AgregadorEnsayosServicio $agregador
    ): ?float {
        $metodo = 'agregacion_' . $manejador;
        if (method_exists(self::class, $metodo)) {
            return self::$metodo($codigoAgregacion, $filasValores, $datosGlobales, $usarCorregido, $agregador);
        }
        return null;
    }

    /**
     * Postprocesamiento de resultados agregados para un manejador.
     */
    public static function postprocesarConjunto(
        string $manejador,
        array $filasValores,
        array &$agregacionesResultado,
        bool $usarCorregido,
        AgregadorEnsayosServicio $agregador
    ): void {
        $metodo = 'conjunto_' . $manejador;
        if (method_exists(self::class, $metodo)) {
            self::$metodo($filasValores, $agregacionesResultado, $usarCorregido, $agregador);
        }
    }

    public static function pre_conjunto_FM56_01(
        array &$listaEspecimenes,
        array $datosGlobales,
        bool $usarCorregido
    ): void {
        if (!$usarCorregido) {
            $tempFija = $datosGlobales['temperatura_encabezado'] ?? ($listaEspecimenes[0]['temperatura'] ?? null);
            if ($tempFija !== null) {
                foreach ($listaEspecimenes as &$rep) {
                    if (!isset($rep['temperatura_encabezado'])) {
                        $rep['temperatura_encabezado'] = $tempFija;
                    }
                    $rep['temperatura'] = $tempFija;
                }
                unset($rep);
            }
        }
    }

    public static function agregacion_FM42_01(
        string $codigoAgregacion,
        array $filasValores,
        array $datosGlobales,
        bool $usarCorregido,
        AgregadorEnsayosServicio $agregador
    ): ?float {
        if ($codigoAgregacion === 'll') {
            if (!$usarCorregido && count($filasValores) >= 2) {
                return $agregador->interpolar(25.0, $filasValores, 'golpes', 'humedad_ll', true);
            }
        }
        return null;
    }

    public static function conjunto_FM42_02(
        array $filasValores,
        array &$agregacionesResultado,
        bool $usarCorregido,
        AgregadorEnsayosServicio $agregador
    ): void {
        $lps = $agregador->extraerValoresNumericos($filasValores, 'humedad_lp');
        $rangoLp = count($lps) >= 2 ? (max($lps) - min($lps)) : 0.0;
        $agregacionesResultado['rango_replicas_lp'] = round($rangoLp, 3);
        $agregacionesResultado['advertencia_repetibilidad_lp'] = $usarCorregido && ($rangoLp > 2.0);
    }

    public static function val_FM42_01(array $valores, bool $usarCorregido, array $contexto = []): ?array {
        if (!isset($valores['golpes'])) {
            return null;
        }
        $g = (float)$valores['golpes'];
        $valido = ($g >= 20.0 && $g <= 30.0);
        if ($usarCorregido && !$valido) {
            return [
                'valido' => false,
                'aviso' => "El número de golpes ({$g}) está fuera del rango permitido [20, 30] para el Método B según ASTM D4318."
            ];
        }
        return ['valido' => true, 'aviso' => null];
    }

    // =========================================================================
    // FM42-02: Control de repetibilidad de réplicas de LP (ASTM D4318)
    // Solo validación normativa (tolerancia de diferencia entre réplicas <= 1.4%)
    // =========================================================================
    public static function post_FM42_02(array &$valores, bool $usarCorregido, array $contexto = []): void {
        $lp1 = isset($valores['lp_1']) ? (float)$valores['lp_1'] : (isset($valores['lp_replica_1']) ? (float)$valores['lp_replica_1'] : null);
        $lp2 = isset($valores['lp_2']) ? (float)$valores['lp_2'] : (isset($valores['lp_replica_2']) ? (float)$valores['lp_replica_2'] : null);

        if ($lp1 !== null && $lp2 !== null) {
            $dif = abs($lp1 - $lp2);
            $valores['diferencia_lp_replicas'] = round($dif, 2);
            $valores['lp_repetibilidad_valida'] = $usarCorregido ? ($dif <= 1.4) : true;
        } else {
            $valores['lp_repetibilidad_valida'] = true;
        }
    }

    public static function val_FM42_02(array $valores, bool $usarCorregido, array $contexto = []): ?array {
        $lp1 = isset($valores['lp_1']) ? (float)$valores['lp_1'] : (isset($valores['lp_replica_1']) ? (float)$valores['lp_replica_1'] : null);
        $lp2 = isset($valores['lp_2']) ? (float)$valores['lp_2'] : (isset($valores['lp_replica_2']) ? (float)$valores['lp_replica_2'] : null);

        if ($lp1 !== null && $lp2 !== null) {
            $dif = abs($lp1 - $lp2);
            if ($usarCorregido && $dif > 1.4) {
                return [
                    'valido' => false,
                    'aviso' => "La diferencia entre réplicas de LP (" . round($dif, 2) . "%) supera la repetibilidad máxima de ASTM D4318 (1.4%)."
                ];
            }
        }
        return ['valido' => true, 'aviso' => null];
    }

    // =========================================================================
    // FM56-01: Temperatura individual por réplica vs temperatura de encabezado
    // =========================================================================
    public static function pre_FM56_01(array $entradas, bool $usarCorregido, array $contexto = []): array {
        if (!$usarCorregido) {
            if (isset($entradas['temperatura_encabezado']) && is_numeric($entradas['temperatura_encabezado'])) {
                $entradas['temperatura'] = (float)$entradas['temperatura_encabezado'];
            }
        }
        return $entradas;
    }

    // =========================================================================
    // FM56-03: Cálculo procedimental opcional de Gs de muestra de suelo (ASTM D854)
    // =========================================================================
    public static function post_FM56_03(array &$valores, bool $usarCorregido, array $contexto = []): void {
        if ($usarCorregido) {
            if (isset($valores['masa_suelo_seco'], $valores['masa_lleno'], $valores['masa_picno_agua_suelo'])) {
                $ms = (float)$valores['masa_suelo_seco'];
                $mpw = (float)$valores['masa_lleno'];
                $mpws = (float)$valores['masa_picno_agua_suelo'];
                $denom = $ms + $mpw - $mpws;
                if (abs($denom) > 1e-9) {
                    $valores['gs_suelo_d854'] = round($ms / $denom, 3);
                }
            }
        } else {
            unset($valores['gs_suelo_d854']);
        }
    }

    // =========================================================================
    // FM58-01: Conversión de unidad de masa explícita en RT-FM-58
    // Regla: Si unidad_masa == 'kg', convertir multiplicando por 1000 a gramos.
    // Si unidad_masa == 'g' o falta, conservar los gramos exactos sin heurística (< 10).
    // =========================================================================
    public static function pre_FM58_01(array $entradas, bool $usarCorregido, array $contexto = []): array {
        $unidad = strtolower(trim((string)($entradas['unidad_masa'] ?? ($contexto['unidad_masa_defecto'] ?? 'g'))));

        if ($unidad === 'kg') {
            if (isset($entradas['masa_inicial']) && is_numeric($entradas['masa_inicial'])) {
                $entradas['masa_inicial'] = (float)$entradas['masa_inicial'] * 1000.0;
            }
            if (isset($entradas['masa_final']) && is_numeric($entradas['masa_final'])) {
                $entradas['masa_final'] = (float)$entradas['masa_final'] * 1000.0;
            }
        }
        // Cuando es 'g' (o si usarCorregido=false sin unidad_masa), no se aplica ninguna heurística arbitraria
        return $entradas;
    }

    // =========================================================================
    // FM59-02: Descarte de lecturas de rebote esclerómetro > ±6 del promedio (ASTM C805)
    // =========================================================================
    public static function post_FM59_02(
        array &$valores,
        bool $usarCorregido,
        array $contexto = []
    ): void {
        $clavesFijas = $contexto['claves_fijas'] ?? [];
        $formulasEfectivas = $contexto['formulas_efectivas'] ?? [];

        if ($usarCorregido && !isset($clavesFijas['rebote_promedio']) && isset($valores['rebote_promedio'])) {
            $promInicial = (float)$valores['rebote_promedio'];
            $rebotesValidos = [];
            $descartados = 0;
            for ($i = 1; $i <= 10; $i++) {
                $rk = 'rebote_' . $i;
                if (isset($valores[$rk]) && is_numeric($valores[$rk])) {
                    $rv = (float)$valores[$rk];
                    if (abs($rv - $promInicial) > 6.0) {
                        $descartados++;
                    } else {
                        $rebotesValidos[] = $rv;
                    }
                }
            }
            $valores['rebotes_descartados_c805'] = $descartados;
            $valores['conjunto_descartado_c805'] = ($descartados > 2);
            if ($descartados > 0 && !empty($rebotesValidos)) {
                $valores['rebote_promedio'] = array_sum($rebotesValidos) / count($rebotesValidos);
                foreach ($formulasEfectivas as $f) {
                    $codFormula = $f['codigo'];
                    if ($codFormula === 'rebote_promedio' || isset($clavesFijas[$codFormula])) {
                        continue;
                    }
                    try {
                        $res = EvaluadorFormula::evaluar($f['expresion'], $valores);
                        if ($res !== null) {
                            $valores[$codFormula] = $res;
                        }
                    } catch (\Throwable $e) {
                    }
                }
            }
        } else {
            $valores['rebotes_descartados_c805'] = 0;
            $valores['conjunto_descartado_c805'] = false;
        }
    }

    // =========================================================================
    // FM62-03: Validación de monotonía y rango físico de descensos (RT-FM-62)
    // Solo validación normativa
    // =========================================================================
    public static function post_FM62_03(array &$valores, bool $usarCorregido, array $contexto = []): void {
        $clavesDesc = ['descenso_5min', 'descenso_10min', 'descenso_15min', 'descenso_20min', 'descenso_25min', 'descenso_30min'];
        $prev = -INF;
        $monotona = true;
        foreach ($clavesDesc as $cd) {
            if (isset($valores[$cd]) && is_numeric($valores[$cd])) {
                $actual = (float)$valores[$cd];
                if ($actual < $prev || $actual < 0.0 || $actual > 150.0) {
                    $monotona = false;
                    break;
                }
                $prev = $actual;
            }
        }
        $valores['lecturas_monotonas_validas'] = $usarCorregido ? $monotona : true;
    }

    public static function val_FM62_03(array $valores, bool $usarCorregido, array $contexto = []): ?array {
        $clavesDesc = ['descenso_5min', 'descenso_10min', 'descenso_15min', 'descenso_20min', 'descenso_25min', 'descenso_30min'];
        $prev = -INF;
        foreach ($clavesDesc as $cd) {
            if (isset($valores[$cd]) && is_numeric($valores[$cd])) {
                $actual = (float)$valores[$cd];
                if ($actual < $prev || $actual < 0.0 || $actual > 150.0) {
                    if ($usarCorregido) {
                        return [
                            'valido' => false,
                            'aviso' => "Lecturas de descenso de percolación no monótonas o fuera de rango físico [0-150 cm]."
                        ];
                    }
                }
                $prev = $actual;
            }
        }
        return ['valido' => true, 'aviso' => null];
    }

    // =========================================================================
    // FM63-03: Pesos de absorción por muestra en RT-FM-63
    // Sin números fijos en código PHP: los datos se leen de las entradas o del
    // catálogo de constantes de la especificación JSON si no viene por muestra.
    // =========================================================================
    public static function pre_FM63_03(array $entradas, bool $usarCorregido, array $contexto = []): array {
        if (!$usarCorregido) {
            $constantes = $contexto['constantes'] ?? [];
            if (!isset($entradas['masa_seca'])) {
                $entradas['masa_seca'] = (float)($constantes['peso_absorcion_masa_seca_defecto'] ?? ($entradas['masa_seca_defecto'] ?? 0));
            }
            if (!isset($entradas['masa_saturada'])) {
                $entradas['masa_saturada'] = (float)($constantes['peso_absorcion_masa_saturada_defecto'] ?? ($entradas['masa_saturada_defecto'] ?? 0));
            }
            if (!isset($entradas['masa_sumergida'])) {
                $entradas['masa_sumergida'] = (float)($constantes['peso_absorcion_masa_sumergida_defecto'] ?? ($entradas['masa_sumergida_defecto'] ?? 0));
            }
        }
        return $entradas;
    }

    // =========================================================================
    // FM64-04: Validación de fluencia menor a resistencia última (fy < fu) en RT-FM-64
    // Solo validación normativa
    // =========================================================================
    public static function post_FM64_04(array &$valores, bool $usarCorregido, array $contexto = []): void {
        $fy = isset($valores['fluencia_mpa']) && is_numeric($valores['fluencia_mpa']) ? (float)$valores['fluencia_mpa'] : null;
        $fu = isset($valores['uts_mpa']) && is_numeric($valores['uts_mpa'])
            ? (float)$valores['uts_mpa']
            : (isset($valores['resistencia_tension_psi']) && is_numeric($valores['resistencia_tension_psi'])
                ? ((float)$valores['resistencia_tension_psi'] / 145.038)
                : (isset($valores['fuerza_max_kn'], $valores['area_mm2']) && (float)$valores['area_mm2'] > 0
                    ? ((float)$valores['fuerza_max_kn'] * 1000.0 / (float)$valores['area_mm2'])
                    : null));

        if ($fy !== null && $fu !== null) {
            $valores['fluencia_menor_uts_valida'] = $usarCorregido ? ($fy > 0.0 && $fy < $fu) : true;
        } else {
            $valores['fluencia_menor_uts_valida'] = true;
        }
    }

    public static function val_FM64_04(array $valores, bool $usarCorregido, array $contexto = []): ?array {
        $fy = isset($valores['fluencia_mpa']) && is_numeric($valores['fluencia_mpa']) ? (float)$valores['fluencia_mpa'] : null;
        $fu = isset($valores['uts_mpa']) && is_numeric($valores['uts_mpa'])
            ? (float)$valores['uts_mpa']
            : (isset($valores['resistencia_tension_psi']) && is_numeric($valores['resistencia_tension_psi'])
                ? ((float)$valores['resistencia_tension_psi'] / 145.038)
                : (isset($valores['fuerza_max_kn'], $valores['area_mm2']) && (float)$valores['area_mm2'] > 0
                    ? ((float)$valores['fuerza_max_kn'] * 1000.0 / (float)$valores['area_mm2'])
                    : null));

        if ($fy !== null && $fu !== null && $usarCorregido && ($fy <= 0.0 || $fy >= $fu)) {
            $fuFmt = round($fu, 2);
            return [
                'valido' => false,
                'aviso' => "El esfuerzo de fluencia ({$fy} MPa) debe ser menor a la resistencia a la tensión última ({$fuFmt} MPa)."
            ];
        }
        return ['valido' => true, 'aviso' => null];
    }
}
