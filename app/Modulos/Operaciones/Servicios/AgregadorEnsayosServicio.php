<?php

namespace Cycsa\Modulos\Operaciones\Servicios;

/**
 * Servicio de Agregaciones entre Especímenes, Puntos o Filas para las 17 Hojas de Cálculo
 * de Laboratorio CYCSA (RT-FM-25 al RT-FM-64).
 *
 * Implementa las funciones declaradas en la propiedad "agregaciones" de cada formato JSON:
 * promedio, suma, max, min, desv_est, acumulado, max_curva, x_de_max_curva,
 * interpolar, regresion_cuadratica, clasificar_sucs.
 */
class AgregadorEnsayosServicio {

    /**
     * Ejecuta una función de agregación por nombre sobre un conjunto de datos.
     *
     * @param string $funcion Nombre de la función (promedio, suma, max, min, desv_est, etc.)
     * @param array $datos Lista de valores escalares o filas asociativas
     * @param string|null $campo Clave a extraer si $datos es un arreglo de filas asociativas
     * @param array $parametrosExtras Parámetros adicionales para interpolación, curvas o SUCS
     * @return mixed
     */
    public function ejecutarAgregacion(string $funcion, array $datos, ?string $campo = null, array $parametrosExtras = []): mixed {
        if ($funcion === 'acumulado' && $campo === 'ret_*') {
            $fila = isset($datos[0]) && is_array($datos[0]) ? $datos[0] : $parametrosExtras;
            return $this->calcularCurvaGranulometrica($fila);
        }

        if ($funcion === 'interpolar') {
            $campoX = $parametrosExtras['campo_x'] ?? 'x';
            $campoY = $parametrosExtras['campo_y'] ?? ($campo ?? 'y');
            if ($campo !== null && str_contains($campo, ' vs ')) {
                [$partY, $partX] = array_map('trim', explode(' vs ', $campo, 2));
                $campoX = $parametrosExtras['campo_x'] ?? $partX;
                $campoY = $parametrosExtras['campo_y'] ?? $partY;
            }
            $xObj = (float)($parametrosExtras['x_objetivo'] ?? $parametrosExtras['compactacion_diseno'] ?? ($campoX === 'compactacion' ? 95.0 : 20.0));
            return $this->interpolar($xObj, $datos, $campoX, $campoY, !empty($parametrosExtras['log_x']));
        }

        if ($funcion === 'regresion_cuadratica') {
            $campoX = $parametrosExtras['campo_x'] ?? 'humedad_pct';
            $campoY = $parametrosExtras['campo_y'] ?? ($campo ?? 'densidad_seca_kg_m3');
            if ($campo !== null && str_contains($campo, ' vs ')) {
                [$partY, $partX] = array_map('trim', explode(' vs ', $campo, 2));
                $campoX = $parametrosExtras['campo_x'] ?? $partX;
                $campoY = $parametrosExtras['campo_y'] ?? $partY;
            }
            return $this->regresionCuadratica($datos, $campoX, $campoY);
        }

        if ($funcion === 'clasificar_sucs') {
            $fila = isset($datos[0]) && is_array($datos[0]) ? array_merge($datos[0], $parametrosExtras) : ($parametrosExtras ?: $datos);
            return $this->clasificarSucs($fila);
        }

        $valores = $this->extraerValoresNumericos($datos, $campo);

        return match ($funcion) {
            'promedio' => $this->promedio($valores),
            'suma' => $this->suma($valores),
            'max' => $this->max($valores),
            'min' => $this->min($valores),
            'desv_est' => $this->desviacionEstandar($valores),
            'acumulado' => $this->acumulado($valores),
            'max_curva' => $this->maxCurva($datos, $campo, $parametrosExtras),
            'x_de_max_curva' => $this->xDeMaxCurva($datos, $campo, $parametrosExtras),
            default => null,
        };
    }

    /**
     * Calcula la curva granulométrica completa (% retenido acumulado y % que pasa por tamiz)
     * para RT-FM-25 a partir de las masas retenidas ret_* y la masa_seca.
     */
    public function calcularCurvaGranulometrica(array $fila): array {
        $tamices = [
            'ret_2in' => ['etiqueta' => '2"', 'abertura_mm' => 50.0],
            'ret_1_1_2in' => ['etiqueta' => '1 1/2"', 'abertura_mm' => 37.5],
            'ret_1in' => ['etiqueta' => '1"', 'abertura_mm' => 25.0],
            'ret_3_4in' => ['etiqueta' => '3/4"', 'abertura_mm' => 19.0],
            'ret_1_2in' => ['etiqueta' => '1/2"', 'abertura_mm' => 12.5],
            'ret_3_8in' => ['etiqueta' => '3/8"', 'abertura_mm' => 9.5],
            'ret_no4' => ['etiqueta' => 'No. 4', 'abertura_mm' => 4.75],
            'ret_no8' => ['etiqueta' => 'No. 8', 'abertura_mm' => 2.36],
            'ret_no10' => ['etiqueta' => 'No. 10', 'abertura_mm' => 2.00],
            'ret_no16' => ['etiqueta' => 'No. 16', 'abertura_mm' => 1.18],
            'ret_no20' => ['etiqueta' => 'No. 20', 'abertura_mm' => 0.85],
            'ret_no30' => ['etiqueta' => 'No. 30', 'abertura_mm' => 0.60],
            'ret_no40' => ['etiqueta' => 'No. 40', 'abertura_mm' => 0.425],
            'ret_no50' => ['etiqueta' => 'No. 50', 'abertura_mm' => 0.30],
            'ret_no60' => ['etiqueta' => 'No. 60', 'abertura_mm' => 0.25],
            'ret_no80' => ['etiqueta' => 'No. 80', 'abertura_mm' => 0.18],
            'ret_no100' => ['etiqueta' => 'No. 100', 'abertura_mm' => 0.15],
            'ret_no140' => ['etiqueta' => 'No. 140', 'abertura_mm' => 0.106],
            'ret_no200' => ['etiqueta' => 'No. 200', 'abertura_mm' => 0.075],
        ];

        $masaSeca = (float)($fila['masa_seca'] ?? 0.0);
        if ($masaSeca <= 0 && isset($fila['masa_seca_tara'], $fila['tara'])) {
            $masaSeca = (float)$fila['masa_seca_tara'] - (float)$fila['tara'];
        }

        $acumuladoG = 0.0;
        $detalleTamices = [];
        foreach ($tamices as $cod => $meta) {
            $retG = isset($fila[$cod]) && is_numeric($fila[$cod]) ? (float)$fila[$cod] : 0.0;
            $acumuladoG += $retG;
            $pctRetAcum = $masaSeca > 0 ? ($acumuladoG * 100.0 / $masaSeca) : 0.0;
            $pctPasa = max(0.0, 100.0 - $pctRetAcum);
            $detalleTamices[$cod] = [
                'tamiz' => $meta['etiqueta'],
                'abertura_mm' => $meta['abertura_mm'],
                'masa_retenida_g' => $retG,
                'masa_acumulada_g' => round($acumuladoG, 2),
                'pct_retenido_acumulado' => round($pctRetAcum, 2),
                'pct_pasa' => round($pctPasa, 2),
            ];
        }

        $pctPasaNo4 = $detalleTamices['ret_no4']['pct_pasa'] ?? 100.0;
        $pctPasaNo200 = isset($fila['pasa_no200']) && is_numeric($fila['pasa_no200'])
            ? (float)$fila['pasa_no200']
            : ($detalleTamices['ret_no200']['pct_pasa'] ?? 0.0);
        $pctGrava = max(0.0, 100.0 - $pctPasaNo4);
        $pctArena = max(0.0, $pctPasaNo4 - $pctPasaNo200);

        return [
            'masa_seca_g' => $masaSeca,
            'tamices' => $detalleTamices,
            'pct_grava' => round($pctGrava, 2),
            'pct_arena' => round($pctArena, 2),
            'pct_finos' => round($pctPasaNo200, 2),
        ];
    }

    /**
     * Extrae valores numéricos válidos (no nulos y numéricos) de un arreglo escalar o de filas.
     *
     * @param array $datos
     * @param string|null $campo
     * @return array<float>
     */
    public function extraerValoresNumericos(array $datos, ?string $campo = null): array {
        $resultado = [];
        foreach ($datos as $item) {
            if (is_array($item)) {
                if ($campo !== null && array_key_exists($campo, $item) && $item[$campo] !== null && $item[$campo] !== '' && is_numeric($item[$campo])) {
                    $resultado[] = (float)$item[$campo];
                }
            } elseif ($item !== null && $item !== '' && is_numeric($item)) {
                $resultado[] = (float)$item;
            }
        }
        return $resultado;
    }

    public function promedio(array $valores): ?float {
        $limpios = $this->extraerValoresNumericos($valores);
        if (empty($limpios)) {
            return null;
        }
        return array_sum($limpios) / count($limpios);
    }

    public function suma(array $valores): float {
        $limpios = $this->extraerValoresNumericos($valores);
        if (empty($limpios)) {
            return 0.0;
        }
        return (float)array_sum($limpios);
    }

    public function max(array $valores): ?float {
        $limpios = $this->extraerValoresNumericos($valores);
        if (empty($limpios)) {
            return null;
        }
        return (float)max($limpios);
    }

    public function min(array $valores): ?float {
        $limpios = $this->extraerValoresNumericos($valores);
        if (empty($limpios)) {
            return null;
        }
        return (float)min($limpios);
    }

    /**
     * Desviación estándar muestral (n - 1). Si hay menos de 2 datos, retorna 0.0.
     */
    public function desviacionEstandar(array $valores): ?float {
        $limpios = $this->extraerValoresNumericos($valores);
        $n = count($limpios);
        if ($n === 0) {
            return null;
        }
        if ($n === 1) {
            return 0.0;
        }
        $media = array_sum($limpios) / $n;
        $sumaCuadrados = 0.0;
        foreach ($limpios as $v) {
            $sumaCuadrados += ($v - $media) ** 2;
        }
        return sqrt($sumaCuadrados / ($n - 1));
    }

    /**
     * Retorna el arreglo de sumas acumuladas parciales [v1, v1+v2, v1+v2+v3, ...].
     *
     * @param array $valores
     * @return array<float>
     */
    public function acumulado(array $valores): array {
        $limpios = $this->extraerValoresNumericos($valores);
        $acum = [];
        $running = 0.0;
        foreach ($limpios as $v) {
            $running += $v;
            $acum[] = $running;
        }
        return $acum;
    }

    /**
     *Ajuste por mínimos cuadrados de parábola y = a*x^2 + b*x + c (ej. Curva Proctor ASTM D1557/D698).
     *
     * @param array $puntos Arreglo de filas con claves $campoX y $campoY
     * @param string $campoX
     * @param string $campoY
     * @return array{a: float, b: float, c: float, x_vertice: float, y_vertice: float}|null
     */
    public function regresionCuadratica(array $puntos, string $campoX = 'humedad_pct', string $campoY = 'densidad_seca_kg_m3'): ?array {
        $xs = [];
        $ys = [];
        foreach ($puntos as $p) {
            if (is_array($p) && isset($p[$campoX], $p[$campoY]) && is_numeric($p[$campoX]) && is_numeric($p[$campoY])) {
                $xs[] = (float)$p[$campoX];
                $ys[] = (float)$p[$campoY];
            }
        }

        $n = count($xs);
        if ($n < 3) {
            return null;
        }

        $sx = 0.0; $sx2 = 0.0; $sx3 = 0.0; $sx4 = 0.0;
        $sy = 0.0; $sxy = 0.0; $sx2y = 0.0;

        for ($i = 0; $i < $n; $i++) {
            $x = $xs[$i];
            $y = $ys[$i];
            $x2 = $x * $x;
            $sx += $x;
            $sx2 += $x2;
            $sx3 += $x2 * $x;
            $sx4 += $x2 * $x2;
            $sy += $y;
            $sxy += $x * $y;
            $sx2y += $x2 * $y;
        }

        // Resolver sistema 3x3:
        // [ n    sx   sx2 ] [ c ]   [ sy   ]
        // [ sx   sx2  sx3 ] [ b ] = [ sxy  ]
        // [ sx2  sx3  sx4 ] [ a ]   [ sx2y ]
        $det = $n * ($sx2 * $sx4 - $sx3 * $sx3)
             - $sx * ($sx * $sx4 - $sx3 * $sx2)
             + $sx2 * ($sx * $sx3 - $sx2 * $sx2);

        if (abs($det) < 1e-12) {
            return null;
        }

        $detC = $sy * ($sx2 * $sx4 - $sx3 * $sx3)
              - $sx * ($sxy * $sx4 - $sx3 * $sx2y)
              + $sx2 * ($sxy * $sx3 - $sx2 * $sx2y);

        $detB = $n * ($sxy * $sx4 - $sx3 * $sx2y)
              - $sy * ($sx * $sx4 - $sx3 * $sx2)
              + $sx2 * ($sx * $sx2y - $sxy * $sx2);

        $detA = $n * ($sx2 * $sx2y - $sxy * $sx3)
              - $sx * ($sx * $sx2y - $sxy * $sx2)
              + $sy * ($sx * $sx3 - $sx2 * $sx2);

        $c = $detC / $det;
        $b = $detB / $det;
        $a = $detA / $det;

        if (abs($a) < 1e-12) {
            return null;
        }

        $xVertice = -$b / (2.0 * $a);
        $yVertice = $a * ($xVertice ** 2) + $b * $xVertice + $c;

        return [
            'a' => $a,
            'b' => $b,
            'c' => $c,
            'x_vertice' => $xVertice,
            'y_vertice' => $yVertice
        ];
    }

    /**
     * Obtiene el valor máximo de la curva (ej. P.V.S. Máximo en Proctor RT-FM-27).
     * Si hay >= 3 puntos con parábola cóncava hacia abajo (a < 0) y vértice dentro del rango razonable,
     * usa el vértice de la parábola; de lo contrario retorna el máximo de los puntos medidos.
     */
    public function maxCurva(array $datos, ?string $campoY = 'densidad_seca', array $parametrosExtras = []): ?float {
        $campoX = $parametrosExtras['campo_x'] ?? (isset($datos[0]['humedad']) ? 'humedad' : 'humedad_pct');
        $campoY = $campoY ?: ($parametrosExtras['campo_y'] ?? (isset($datos[0]['densidad_seca']) ? 'densidad_seca' : 'densidad_seca_kg_m3'));

        if (!empty($parametrosExtras['usar_regresion']) && count($datos) >= 3) {
            $reg = $this->regresionCuadratica($datos, $campoX, $campoY);
            if ($reg && $reg['a'] < 0) {
                return $reg['y_vertice'];
            }
        }

        $valoresY = $this->extraerValoresNumericos($datos, $campoY);
        return !empty($valoresY) ? (float)max($valoresY) : null;
    }

    /**
     * Obtiene la abscisa X correspondiente al máximo de la curva (ej. Humedad Óptima en Proctor RT-FM-27).
     */
    public function xDeMaxCurva(array $datos, ?string $campoX = 'humedad', array $parametrosExtras = []): ?float {
        $campoX = $campoX ?: ($parametrosExtras['campo_x'] ?? (isset($datos[0]['humedad']) ? 'humedad' : 'humedad_pct'));
        $campoY = $parametrosExtras['campo_y'] ?? (isset($datos[0]['densidad_seca']) ? 'densidad_seca' : 'densidad_seca_kg_m3');

        if (!empty($parametrosExtras['usar_regresion']) && count($datos) >= 3) {
            $reg = $this->regresionCuadratica($datos, $campoX, $campoY);
            if ($reg && $reg['a'] < 0) {
                return $reg['x_vertice'];
            }
        }

        $maxY = null;
        $mejorX = null;
        foreach ($datos as $fila) {
            if (is_array($fila) && isset($fila[$campoX], $fila[$campoY]) && is_numeric($fila[$campoX]) && is_numeric($fila[$campoY])) {
                $y = (float)$fila[$campoY];
                if ($maxY === null || $y > $maxY) {
                    $maxY = $y;
                    $mejorX = (float)$fila[$campoX];
                }
            }
        }

        if ($mejorX !== null) {
            return $mejorX;
        }

        $valoresX = $this->extraerValoresNumericos($datos, $campoX);
        return !empty($valoresX) ? $valoresX[0] : null;
    }

    /**
     * Interpolación lineal (o logarítmica en X para curvas granulométricas D10/D30/D60)
     * para obtener Y dado un X objetivo (ej. Límite Líquido a N=25 golpes o CBR a 0.1"/0.2").
     */
    public function interpolar(float $xObjetivo, array $puntos, string $campoX = 'x', string $campoY = 'y', bool $logX = false): ?float {
        $pares = [];
        foreach ($puntos as $p) {
            if (is_array($p) && isset($p[$campoX], $p[$campoY]) && is_numeric($p[$campoX]) && is_numeric($p[$campoY])) {
                $pares[] = ['x' => (float)$p[$campoX], 'y' => (float)$p[$campoY]];
            }
        }

        if (empty($pares)) {
            return null;
        }
        if (count($pares) === 1) {
            return $pares[0]['y'];
        }

        usort($pares, fn($a, $b) => $a['x'] <=> $b['x']);

        // Buscar intervalo que contiene a $xObjetivo
        $n = count($pares);
        for ($i = 0; $i < $n - 1; $i++) {
            $x0 = $pares[$i]['x'];
            $y0 = $pares[$i]['y'];
            $x1 = $pares[$i + 1]['x'];
            $y1 = $pares[$i + 1]['y'];

            if ($xObjetivo >= $x0 && $xObjetivo <= $x1) {
                if (abs($x1 - $x0) < 1e-12) {
                    return $y0;
                }
                if ($logX && $x0 > 0 && $x1 > 0 && $xObjetivo > 0) {
                    $lx0 = log10($x0);
                    $lx1 = log10($x1);
                    $lx = log10($xObjetivo);
                    return $y0 + (($lx - $lx0) / ($lx1 - $lx0)) * ($y1 - $y0);
                }
                return $y0 + (($xObjetivo - $x0) / ($x1 - $x0)) * ($y1 - $y0);
            }
        }

        // Si está fuera del rango, extrapolar linealmente con los 2 puntos más cercanos
        if ($xObjetivo < $pares[0]['x']) {
            $x0 = $pares[0]['x']; $y0 = $pares[0]['y'];
            $x1 = $pares[1]['x']; $y1 = $pares[1]['y'];
        } else {
            $x0 = $pares[$n - 2]['x']; $y0 = $pares[$n - 2]['y'];
            $x1 = $pares[$n - 1]['x']; $y1 = $pares[$n - 1]['y'];
        }

        if (abs($x1 - $x0) < 1e-12) {
            return $y0;
        }
        return $y0 + (($xObjetivo - $x0) / ($x1 - $x0)) * ($y1 - $y0);
    }

    /**
     * Clasificación de Suelos según el Sistema Unificado de Clasificación de Suelos (SUCS - ASTM D2487).
     *
     * @param array $datos Puede contener: pct_pasa_200 (o pct_finos), pct_grava, pct_arena, ll (o limite_liquido), ip (o indice_plasticidad), cu, cc
     * @return array{simbolo: string, descripcion: string}
     */
    public function clasificarSucs(array $datos): array {
        $finos = (float)($datos['pct_pasa_200'] ?? $datos['pct_finos'] ?? 0.0);
        $grava = (float)($datos['pct_grava'] ?? 0.0);
        $arena = (float)($datos['pct_arena'] ?? max(0.0, 100.0 - $finos - $grava));
        $ll = isset($datos['ll']) ? (float)$datos['ll'] : (isset($datos['limite_liquido']) ? (float)$datos['limite_liquido'] : 0.0);
        $ip = isset($datos['ip']) ? (float)$datos['ip'] : (isset($datos['indice_plasticidad']) ? (float)$datos['indice_plasticidad'] : 0.0);
        $cu = isset($datos['cu']) ? (float)$datos['cu'] : null;
        $cc = isset($datos['cc']) ? (float)$datos['cc'] : null;

        // Línea A de Casagrande: IP_A = 0.73 * (LL - 20)
        $lineaA = 0.73 * ($ll - 20.0);
        $sobreLineaA = ($ip >= $lineaA);

        // 1. Suelos de grano fino (>= 50% pasa el tamiz N° 200)
        if ($finos >= 50.0) {
            if ($ll < 50.0) {
                if ($ip >= 4.0 && $ip <= 7.0 && $sobreLineaA) {
                    return ['simbolo' => 'CL-ML', 'descripcion' => 'Arcilla limosa de baja plasticidad'];
                }
                if ($ip > 7.0 && $sobreLineaA) {
                    return ['simbolo' => 'CL', 'descripcion' => 'Arcilla de baja plasticidad'];
                }
                return ['simbolo' => 'ML', 'descripcion' => 'Limo de baja plasticidad'];
            } else {
                if ($sobreLineaA) {
                    return ['simbolo' => 'CH', 'descripcion' => 'Arcilla de alta plasticidad'];
                }
                return ['simbolo' => 'MH', 'descripcion' => 'Limo de alta plasticidad (elástico)'];
            }
        }

        // 2. Suelos de grano grueso (< 50% pasa el tamiz N° 200)
        $esGrava = ($grava > $arena);
        $prefijo = $esGrava ? 'G' : 'S';
        $nombreBase = $esGrava ? 'Grava' : 'Arena';

        $bienGraduado = false;
        if ($cu !== null && $cc !== null) {
            if ($esGrava) {
                $bienGraduado = ($cu >= 4.0 && $cc >= 1.0 && $cc <= 3.0);
            } else {
                $bienGraduado = ($cu >= 6.0 && $cc >= 1.0 && $cc <= 3.0);
            }
        }

        $sufijoGrad = $bienGraduado ? 'W' : 'P';
        $descGrad = $bienGraduado ? 'bien graduada' : 'mal graduada';

        $esArcilloso = ($ip > 7.0 && $sobreLineaA);
        $sufijoFinos = $esArcilloso ? 'C' : 'M';
        $descFinos = $esArcilloso ? 'arcillosa' : 'limosa';

        if ($finos < 5.0) {
            $simbolo = $prefijo . $sufijoGrad;
            return ['simbolo' => $simbolo, 'descripcion' => "{$nombreBase} {$descGrad}"];
        }

        if ($finos > 12.0) {
            $simbolo = $prefijo . $sufijoFinos;
            return ['simbolo' => $simbolo, 'descripcion' => "{$nombreBase} {$descFinos}"];
        }

        // Símbolo doble (5% <= finos <= 12%)
        $simbolo = "{$prefijo}{$sufijoGrad}-{$prefijo}{$sufijoFinos}";
        $conectorFinos = $esArcilloso ? 'con arcilla' : 'con limo';
        return ['simbolo' => $simbolo, 'descripcion' => "{$nombreBase} {$descGrad} {$conectorFinos}"];
    }
}
