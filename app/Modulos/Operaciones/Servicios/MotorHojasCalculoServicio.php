<?php

namespace Cycsa\Modulos\Operaciones\Servicios;

use Cycsa\Nucleo\EvaluadorFormula;
use Cycsa\Modulos\Configuracion\Modelos\EquipoCalibracionModelo;
use InvalidArgumentException;
use RuntimeException;

/**
 * Motor Central de Evaluación de las 17 Hojas de Cálculo de Laboratorio (RT-FM-25 al RT-FM-64).
 *
 * Responsabilidades:
 * - Cargar las especificaciones canónicas desde docs/hojas_calculo/formatos/RT-FM-xx.json
 * - Inyectar constantes fijas y constantes referenciadas a equipos (ref_equipo) desde BD
 * - Aplicar parametrización activa para las 21 correcciones marcadas como "dudosa" hasta confirmación de Calidad
 * - Evaluar fórmulas en cascada mediante EvaluadorFormula::evaluar() (sin eval(), sin IF/SUM/AVERAGE)
 * - Ejecutar agregaciones entre especímenes/puntos/capas mediante AgregadorEnsayosServicio
 */
class MotorHojasCalculoServicio {

    private string $rutaBaseHc;
    private EquipoCalibracionModelo $equipoModelo;
    private AgregadorEnsayosServicio $agregador;
    /** @var array<string, array> Caché en memoria de especificaciones JSON */
    private array $cacheFormatos = [];

    public function __construct(
        ?EquipoCalibracionModelo $equipoModelo = null,
        ?AgregadorEnsayosServicio $agregador = null,
        ?string $rutaBaseHc = null
    ) {
        $this->rutaBaseHc = $rutaBaseHc ?? dirname(__DIR__, 4) . '/docs/hojas_calculo';
        $this->equipoModelo = $equipoModelo ?? new EquipoCalibracionModelo();
        $this->agregador = $agregador ?? new AgregadorEnsayosServicio();
    }

    public function obtenerRutaBase(): string {
        return $this->rutaBaseHc;
    }

    /**
     * Lista los códigos de los 17 formatos disponibles en docs/hojas_calculo/formatos.
     *
     * @return array<string>
     */
    public function listarCodigosFormatos(): array {
        $archivos = glob($this->rutaBaseHc . '/formatos/RT-FM-*.json') ?: [];
        $codigos = [];
        foreach ($archivos as $archivo) {
            $codigos[] = pathinfo($archivo, PATHINFO_FILENAME);
        }
        sort($codigos);
        return $codigos;
    }

    /**
     * Carga y decodifica la especificación JSON de un formato (ej. "RT-FM-61").
     */
    public function cargarEspecificacion(string $codigoFormato): array {
        $codigo = strtoupper(trim($codigoFormato));
        if (!preg_match('/^RT-FM-\d{2}$/', $codigo)) {
            throw new InvalidArgumentException("Código de formato no válido: {$codigoFormato}");
        }
        if (isset($this->cacheFormatos[$codigo])) {
            return $this->cacheFormatos[$codigo];
        }

        $ruta = $this->rutaBaseHc . '/formatos/' . $codigo . '.json';
        if (!file_exists($ruta)) {
            throw new InvalidArgumentException("No existe la especificación de hoja de cálculo para el formato {$codigo}");
        }

        $contenido = file_get_contents($ruta);
        $spec = json_decode((string)$contenido, true);
        if (!is_array($spec)) {
            throw new RuntimeException("El archivo JSON del formato {$codigo} contiene errores de sintaxis.");
        }

        $this->cacheFormatos[$codigo] = $spec;
        return $spec;
    }

    /**
     * Resuelve todas las constantes declaradas en el formato (fijas y ref_equipo desde BD).
     *
     * @param string $codigoFormato
     * @return array<string, float>
     */
    public function resolverConstantes(string $codigoFormato): array {
        $spec = $this->cargarEspecificacion($codigoFormato);
        $constantes = [];

        foreach ($spec['constantes'] ?? [] as $c) {
            $cod = $c['codigo'];
            if (array_key_exists('valor', $c) && $c['valor'] !== null) {
                $constantes[$cod] = (float)$c['valor'];
            } elseif (!empty($c['ref_equipo'])) {
                $fallback = isset($c['valor_referencia']) && $c['valor_referencia'] !== null ? (float)$c['valor_referencia'] : null;
                $aliasCompuesto = $c['ref_equipo'] . '.' . $cod;
                $valEquipo = $this->equipoModelo->obtenerValorPorRefEquipo($aliasCompuesto, null);
                if ($valEquipo === null) {
                    $valEquipo = $this->equipoModelo->obtenerValorPorRefEquipo($c['ref_equipo'], $fallback);
                }
                if (is_numeric($valEquipo)) {
                    $constantes[$cod] = (float)$valEquipo;
                } elseif ($fallback !== null) {
                    $constantes[$cod] = $fallback;
                }
            } elseif (isset($c['valor_referencia']) && $c['valor_referencia'] !== null) {
                $constantes[$cod] = (float)$c['valor_referencia'];
            }
        }

        return $constantes;
    }

    /**
     * Obtiene el mapa de correcciones activas/inactivas para el formato.
     *
     * @param string $codigoFormato
     * @param array $opciones
     * @return array<string, bool> Mapa [correccion_id => usar_corregido]
     */
    public function obtenerMapaCorrecciones(string $codigoFormato, array $opciones = []): array {
        $spec = $this->cargarEspecificacion($codigoFormato);
        $mapa = [];

        foreach ($spec['correcciones'] ?? [] as $corr) {
            $mapa[$corr['id']] = true;
        }

        $enBd = $this->equipoModelo->obtenerCorreccionesFormato($codigoFormato);
        foreach ($enBd as $cid => $fila) {
            $mapa[$cid] = ((int)($fila['usar_corregido'] ?? 1)) === 1;
        }

        if (!empty($opciones['correcciones_override']) && is_array($opciones['correcciones_override'])) {
            foreach ($opciones['correcciones_override'] as $cid => $val) {
                $mapa[$cid] = (bool)$val;
            }
        }

        return $mapa;
    }

    /**
     * Evalúa todas las fórmulas de un espécimen / fila / determinación usando EvaluadorFormula.
     */
    public function evaluarEspecimen(string $codigoFormato, array $entradas, array $opciones = []): array {
        $spec = $this->cargarEspecificacion($codigoFormato);
        $codigo = $spec['codigo'];
        $constantes = $this->resolverConstantes($codigo);
        $mapaCorrecciones = $this->obtenerMapaCorrecciones($codigo, $opciones);

        // Preprocesamiento de entradas según correcciones dudosas de captura/unidades
        $entradasEfectivas = $this->preprocesarEntradasPorCorreccion($codigo, $entradas, $mapaCorrecciones);

        $valores = $constantes;
        $clavesFijas = [];
        foreach ($entradasEfectivas as $k => $v) {
            if ($v !== null && $v !== '') {
                $valores[$k] = is_numeric($v) ? (float)$v : $v;
                $clavesFijas[$k] = true;
            }
        }

        $formulas = $spec['formulas'] ?? [];
        $formulasEfectivas = $this->aplicarParametrizacionCorrecciones($codigo, $formulas, $mapaCorrecciones);

        $numPasadas = count($formulasEfectivas) + 2;
        for ($pasada = 0; $pasada < $numPasadas; $pasada++) {
            foreach ($formulasEfectivas as $f) {
                $codFormula = $f['codigo'];
                if (isset($clavesFijas[$codFormula])) {
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

        // Postprocesamiento de validaciones y reglas dudosas (FM56-03, FM59-02, FM62-03, FM64-04)
        $this->postprocesarValoresPorCorreccion($codigo, $valores, $clavesFijas, $formulasEfectivas, $mapaCorrecciones);

        $calculados = [];
        $redondeados = [];
        foreach ($formulasEfectivas as $f) {
            $cod = $f['codigo'];
            $val = isset($valores[$cod]) && is_numeric($valores[$cod]) ? (float)$valores[$cod] : null;
            $calculados[$cod] = $val;
            if ($val !== null) {
                $decimales = SembradorPlantillasHcServicio::extraerDecimalesRedondeo($f['redondeo'] ?? null, 4);
                $redondeados[$cod] = round($val, $decimales);
            } else {
                $redondeados[$cod] = null;
            }
        }

        return [
            'codigo_formato' => $codigo,
            'entradas' => $entradas,
            'constantes' => $constantes,
            'valores' => $valores,
            'calculados' => $calculados,
            'redondeados' => $redondeados,
            'correcciones_aplicadas' => $mapaCorrecciones,
        ];
    }

    /**
     * Evalúa un conjunto de especímenes / filas / puntos de una hoja de cálculo y calcula
     * tanto las fórmulas globales (por_especimen = false) como las agregaciones declaradas.
     */
    public function evaluarConjunto(string $codigoFormato, array $listaEspecimenes, array $datosGlobales = [], array $opciones = []): array {
        $spec = $this->cargarEspecificacion($codigoFormato);
        $codigo = $spec['codigo'];
        $mapaCorrecciones = $this->obtenerMapaCorrecciones($codigo, $opciones);

        // FM56-01: si está desactivada (modo Excel original), todas las réplicas usan la temperatura del encabezado o réplica 1
        if ($codigo === 'RT-FM-56' && isset($mapaCorrecciones['FM56-01']) && !$mapaCorrecciones['FM56-01']) {
            $tempFija = $datosGlobales['temperatura_encabezado'] ?? ($listaEspecimenes[0]['temperatura'] ?? null);
            if ($tempFija !== null) {
                foreach ($listaEspecimenes as &$rep) {
                    $rep['temperatura'] = $tempFija;
                }
                unset($rep);
            }
        }

        $resultadosEspecimenes = [];
        $filasValores = [];

        foreach ($listaEspecimenes as $idx => $entradaEsp) {
            $entradaCombinada = array_merge($datosGlobales, $entradaEsp);
            $evalEsp = $this->evaluarEspecimen($codigo, $entradaCombinada, $opciones);
            $resultadosEspecimenes[$idx] = $evalEsp;
            $filasValores[$idx] = $evalEsp['valores'];
        }

        $agregacionesResultado = [];
        foreach ($spec['agregaciones'] ?? [] as $ag) {
            $codAg = $ag['codigo'];
            $funcion = $ag['funcion'];
            $campoOrigen = $ag['de'] ?? null;

            // FM42-01: en RT-FM-42, si FM42-01 = true, usa promedio de ll_por_factor (Excel);
            // si FM42-01 = false (regresión ASTM D4318 Método A), interpola humedad_ll a N=25 en escala log10(golpes)
            if ($codigo === 'RT-FM-42' && $codAg === 'll' && isset($mapaCorrecciones['FM42-01']) && !$mapaCorrecciones['FM42-01'] && count($filasValores) >= 2) {
                $valAg = $this->agregador->interpolar(25.0, $filasValores, 'golpes', 'humedad_ll', true);
            } else {
                $valAg = $this->agregador->ejecutarAgregacion($funcion, $filasValores, $campoOrigen, $datosGlobales);
            }
            $agregacionesResultado[$codAg] = $valAg;

            if (is_numeric($valAg)) {
                $datosGlobales[$codAg] = (float)$valAg;
                foreach ($listaEspecimenes as $idx => $entradaEsp) {
                    $entradaCombinada = array_merge($datosGlobales, $entradaEsp);
                    $evalEsp = $this->evaluarEspecimen($codigo, $entradaCombinada, $opciones);
                    $resultadosEspecimenes[$idx] = $evalEsp;
                    $filasValores[$idx] = $evalEsp['valores'];
                }
            } elseif (is_array($valAg) && isset($valAg['pct_grava'])) {
                $datosGlobales['pct_grava'] = $valAg['pct_grava'];
                $datosGlobales['pct_arena'] = $valAg['pct_arena'];
                $datosGlobales['pct_finos'] = $valAg['pct_finos'];
            }
        }

        // FM42-02: Control de repetibilidad entre réplicas de LP en RT-FM-42
        if ($codigo === 'RT-FM-42') {
            $lps = $this->agregador->extraerValoresNumericos($filasValores, 'humedad_lp');
            $rangoLp = count($lps) >= 2 ? (max($lps) - min($lps)) : 0.0;
            $agregacionesResultado['rango_replicas_lp'] = round($rangoLp, 3);
            $agregacionesResultado['advertencia_repetibilidad_lp'] = !empty($mapaCorrecciones['FM42-02']) && ($rangoLp > 2.0);
        }

        $contextoGlobal = array_merge(
            $this->resolverConstantes($codigo),
            $datosGlobales
        );
        foreach ($agregacionesResultado as $k => $v) {
            if (is_numeric($v)) {
                $contextoGlobal[$k] = (float)$v;
            }
        }
        if (count($filasValores) === 1) {
            foreach ($filasValores[0] as $k => $v) {
                if (!isset($contextoGlobal[$k]) && is_numeric($v)) {
                    $contextoGlobal[$k] = (float)$v;
                }
            }
        }

        $formulasGlobales = array_filter($spec['formulas'] ?? [], fn($f) => empty($f['por_especimen']));
        if (!empty($formulasGlobales)) {
            $evalGlobal = $this->evaluarEspecimen($codigo, $contextoGlobal, $opciones);
            foreach ($formulasGlobales as $fg) {
                $codFg = $fg['codigo'];
                if (isset($evalGlobal['calculados'][$codFg]) && $evalGlobal['calculados'][$codFg] !== null) {
                    $contextoGlobal[$codFg] = $evalGlobal['calculados'][$codFg];
                }
            }
            foreach ($spec['agregaciones'] ?? [] as $ag) {
                $codAg = $ag['codigo'];
                if ($agregacionesResultado[$codAg] === null || $ag['funcion'] === 'clasificar_sucs') {
                    $agregacionesResultado[$codAg] = $this->agregador->ejecutarAgregacion(
                        $ag['funcion'],
                        $filasValores,
                        $ag['de'] ?? null,
                        $contextoGlobal
                    );
                }
            }
        }

        return [
            'codigo_formato' => $codigo,
            'nombre_formato' => $spec['nombre'],
            'norma' => $spec['norma']['texto'] ?? '',
            'formato_informe_rt_fm_22' => $spec['formato_informe_rt_fm_22'] ?? null,
            'especimenes' => $resultadosEspecimenes,
            'agregaciones' => $agregacionesResultado,
            'contexto_global' => $contextoGlobal,
        ];
    }

    /**
     * Preprocesa las entradas antes de evaluar fórmulas según correcciones dudosas:
     * - FM56-01 (RT-FM-56): Temperatura por réplica vs temperatura fija de encabezado
     * - FM58-01 (RT-FM-58): Conversión de fracciones capturadas en kg (< 10) a gramos (×1000)
     * - FM63-03 (RT-FM-63): Pesos de absorción por muestra vs pesos fijos del bloque FM-36
     */
    private function preprocesarEntradasPorCorreccion(string $codigoFormato, array $entradas, array $mapaCorrecciones): array {
        // FM56-01: si está desactivada y viene temperatura_encabezado, usarla en lugar de la temperatura individual
        if ($codigoFormato === 'RT-FM-56' && isset($mapaCorrecciones['FM56-01']) && !$mapaCorrecciones['FM56-01']) {
            if (isset($entradas['temperatura_encabezado']) && is_numeric($entradas['temperatura_encabezado'])) {
                $entradas['temperatura'] = (float)$entradas['temperatura_encabezado'];
            }
        }

        // FM58-01: si está activa (unidad única g) y masa_inicial fue capturada en kg (< 10 con retenido_original > 100), convertir a g
        if ($codigoFormato === 'RT-FM-58' && !empty($mapaCorrecciones['FM58-01'])) {
            if (isset($entradas['masa_inicial'], $entradas['masa_final'])
                && is_numeric($entradas['masa_inicial']) && is_numeric($entradas['masa_final'])
                && (float)$entradas['masa_inicial'] > 0 && (float)$entradas['masa_inicial'] < 10.0
                && (!isset($entradas['retenido_original']) || (float)$entradas['retenido_original'] >= 50.0)
            ) {
                $entradas['masa_inicial'] = (float)$entradas['masa_inicial'] * 1000.0;
                $entradas['masa_final'] = (float)$entradas['masa_final'] * 1000.0;
            }
        }

        // FM63-03: si está desactivada (comportamiento Excel original), rellenar pesos de absorción con los fijos de FM-36
        if ($codigoFormato === 'RT-FM-63' && isset($mapaCorrecciones['FM63-03']) && !$mapaCorrecciones['FM63-03']) {
            $entradas['masa_seca'] = 10.945;
            $entradas['masa_saturada'] = 11.665;
            $entradas['masa_sumergida'] = 5.810;
        }

        return $entradas;
    }

    /**
     * Aplica conmutación de expresiones para todas las correcciones marcadas como "dudosa"
     * en los 17 formatos de Hojas de Cálculo.
     */
    private function aplicarParametrizacionCorrecciones(string $codigoFormato, array $formulas, array $mapaCorrecciones): array {
        foreach ($formulas as &$f) {
            // 1. RT-FM-35 | FM35-01: Fino GE bulk y aparente con masa seca real (true: 2.177) vs B*(1-Abs/100) (false: 2.152)
            if ($codigoFormato === 'RT-FM-35' && isset($mapaCorrecciones['FM35-01']) && !$mapaCorrecciones['FM35-01']) {
                if ($f['codigo'] === 'ge_bulk') {
                    $f['expresion'] = '({masa_sss}*(1-{absorcion}/100))/({volumen_frasco}-{masa_agua_agregada})';
                } elseif ($f['codigo'] === 'ge_aparente') {
                    $f['expresion'] = '({masa_sss}*(1-{absorcion}/100))/(({volumen_frasco}-{masa_agua_agregada})-({masa_sss}-({masa_sss}*(1-{absorcion}/100))))';
                }
            }

            // 2. RT-FM-35 | FM35-02: Grueso sin tara (true) vs con tara incluida en E y A = E*(1-Abs/100) (false)
            if ($codigoFormato === 'RT-FM-35' && isset($mapaCorrecciones['FM35-02']) && !$mapaCorrecciones['FM35-02']) {
                if ($f['codigo'] === 'g_ge_bulk') {
                    $f['expresion'] = '({g_masa_sss_tara}*(1-{g_absorcion}/100))/({g_masa_sss_tara}-{g_masa_sumergida})';
                } elseif ($f['codigo'] === 'g_ge_sss') {
                    $f['expresion'] = '{g_masa_sss_tara}/({g_masa_sss_tara}-{g_masa_sumergida})';
                } elseif ($f['codigo'] === 'g_ge_aparente') {
                    $f['expresion'] = '({g_masa_sss_tara}*(1-{g_absorcion}/100))/(({g_masa_sss_tara}*(1-{g_absorcion}/100))-{g_masa_sumergida})';
                }
            }

            // 3. RT-FM-36 | FM36-01: Factores de conversión aproximados de laboratorio (true: 0.155, 14.2) vs exactos SI/ASTM (false: 0.15500031, 14.2233)
            if ($codigoFormato === 'RT-FM-36' && isset($mapaCorrecciones['FM36-01']) && !$mapaCorrecciones['FM36-01']) {
                if ($f['codigo'] === 'resistencia_psi') {
                    $f['expresion'] = '{carga_lbf}/({area_neta}*0.15500031)';
                } elseif ($f['codigo'] === 'resistencia_kgcm2') {
                    $f['expresion'] = '{resistencia_psi}/14.2233';
                }
            }

            // 4. RT-FM-46 | FM46-02: CBR AASHTO T193 (true: MAX(cbr_01, cbr_02)) vs factores Excel sin MAX (false)
            if ($codigoFormato === 'RT-FM-46' && isset($mapaCorrecciones['FM46-02']) && !$mapaCorrecciones['FM46-02']) {
                if ($f['codigo'] === 'cbr_02') {
                    $f['expresion'] = '{esfuerzo_02}*0.0667';
                } elseif ($f['codigo'] === 'cbr') {
                    $f['expresion'] = '{cbr_01}';
                }
            }

            // 5. RT-FM-57 | FM57-02: Mortero kg/cm² con psi redondeado / (2.202*6.45) (true) vs psi sin redondear / 14.2233 (false)
            if ($codigoFormato === 'RT-FM-57' && isset($mapaCorrecciones['FM57-02']) && !$mapaCorrecciones['FM57-02']) {
                if ($f['codigo'] === 'carga_kg') {
                    $f['expresion'] = '{carga_lbf}/2.20462';
                } elseif ($f['codigo'] === 'resistencia_kgcm2') {
                    $f['expresion'] = '({carga_lbf}/{area_in2})/14.2233';
                }
            }

            // 6. RT-FM-61 | FM61-01: Factor L/D aplicado si L/D < 1.75 (true) vs nunca aplicado = 1.0 (false)
            if ($codigoFormato === 'RT-FM-61' && $f['codigo'] === 'factor_ld' && isset($mapaCorrecciones['FM61-01']) && !$mapaCorrecciones['FM61-01']) {
                $f['expresion'] = '1';
            }

            // 7. RT-FM-61 | FM61-04: Esfuerzo único psi/14.2 (true) vs promedio(carga_kg/area1, carga_kg/area2) (false)
            if ($codigoFormato === 'RT-FM-61' && $f['codigo'] === 'esfuerzo_kgcm2' && isset($mapaCorrecciones['FM61-04']) && !$mapaCorrecciones['FM61-04']) {
                $f['expresion'] = '(({carga_real_kg}/{area_1_cm2})+({carga_real_kg}/{area_2_cm2}))/2';
            }

            // 8. RT-FM-62 | FM62-01: Tasa Q con promedio de 2 últimos intervalos (true) vs solo último intervalo dif_30 (false)
            if ($codigoFormato === 'RT-FM-62' && $f['codigo'] === 'tasa_lm2_dia' && isset($mapaCorrecciones['FM62-01']) && !$mapaCorrecciones['FM62-01']) {
                $f['expresion'] = '315.5*SQRT({dif_30}/300)';
            }

            // 9. RT-FM-62 | FM62-02: Conversión L/m²·día -> gal/ft²·día con 40.77 (true) vs 40.75 exacto (false)
            if ($codigoFormato === 'RT-FM-62' && $f['codigo'] === 'tasa_galft2_dia' && isset($mapaCorrecciones['FM62-02']) && !$mapaCorrecciones['FM62-02']) {
                $f['expresion'] = '{tasa_lm2_dia}/40.75';
            }

            // 10. RT-FM-63 | FM63-01: Área neta adoquín sin /2 (true: col D/E) vs con /2 (false: col B/C)
            if ($codigoFormato === 'RT-FM-63' && $f['codigo'] === 'area_neta_cm2' && isset($mapaCorrecciones['FM63-01']) && !$mapaCorrecciones['FM63-01']) {
                $f['expresion'] = '{largo_prom}*{ancho_prom}+(2*(({base_mayor_prom}+{base_menor_prom})/2)*{altura_prom})/2';
            }

            // 11. RT-FM-63 | FM63-02: Resistencia con promedio de 3 lecturas (true) vs solo lectura 1 (false)
            if ($codigoFormato === 'RT-FM-63' && $f['codigo'] === 'resistencia_psi' && isset($mapaCorrecciones['FM63-02']) && !$mapaCorrecciones['FM63-02']) {
                $f['expresion'] = '{carga_1}/{area_neta_in2}*{factor_acabado}';
            }

            // 12. RT-FM-64 | FM64-01: Elongación con longitud entre marcas (true) vs largo total de varilla (false)
            if ($codigoFormato === 'RT-FM-64' && $f['codigo'] === 'elongacion' && isset($mapaCorrecciones['FM64-01']) && !$mapaCorrecciones['FM64-01']) {
                $f['expresion'] = '{elongacion_excel}';
            }
        }
        unset($f);

        return $formulas;
    }

    /**
     * Ejecuta las reglas y validaciones de postprocesamiento para las correcciones dudosas restantes:
     * - FM42-01 (RT-FM-42): Validación de rango de golpes 20 <= N <= 30 en Método B
     * - FM56-03 (RT-FM-56): Cálculo opcional de Gs de muestra de suelo (ASTM D854) cuando se proveen masas de suelo
     * - FM59-02 (RT-FM-59): Descarte de lecturas de rebote que difieran > 6 unidades del promedio (ASTM C805)
     * - FM62-03 (RT-FM-62): Validación de monotonía creciente y rango razonable de lecturas de descenso
     * - FM64-04 (RT-FM-64): Validación de fluencia < resistencia última (fy < fu)
     */
    private function postprocesarValoresPorCorreccion(
        string $codigoFormato,
        array &$valores,
        array $clavesFijas,
        array $formulasEfectivas,
        array $mapaCorrecciones
    ): void {
        // RT-FM-42 | FM42-01: Validación de golpes en rango [20, 30] para método de un punto
        if ($codigoFormato === 'RT-FM-42' && isset($valores['golpes'])) {
            $g = (float)$valores['golpes'];
            $valores['golpes_en_rango_metodo_b'] = !empty($mapaCorrecciones['FM42-01']) ? ($g >= 20.0 && $g <= 30.0) : true;
        }

        // RT-FM-56 | FM56-03: Cálculo de Gs de suelo (ASTM D854) si está habilitado y se enviaron masas de suelo
        if ($codigoFormato === 'RT-FM-56' && !empty($mapaCorrecciones['FM56-03'])) {
            if (isset($valores['masa_suelo_seco'], $valores['masa_lleno'], $valores['masa_picno_agua_suelo'])) {
                $ms = (float)$valores['masa_suelo_seco'];
                $mpw = (float)$valores['masa_lleno'];
                $mpws = (float)$valores['masa_picno_agua_suelo'];
                $denom = $ms + $mpw - $mpws;
                if (abs($denom) > 1e-9) {
                    $valores['gs_suelo_d854'] = round($ms / $denom, 3);
                }
            }
        }

        // RT-FM-59 | FM59-02: Norma ASTM C805 descarta lecturas que difieran más de 6 unidades del promedio inicial
        if ($codigoFormato === 'RT-FM-59' && !empty($mapaCorrecciones['FM59-02']) && !isset($clavesFijas['rebote_promedio']) && isset($valores['rebote_promedio'])) {
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
        }

        // RT-FM-62 | FM62-03: Validación de monotonía y rango de lecturas de descenso (5 a 30 min)
        if ($codigoFormato === 'RT-FM-62') {
            if (!empty($mapaCorrecciones['FM62-03'])) {
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
                $valores['lecturas_monotonas_validas'] = $monotona;
            } else {
                $valores['lecturas_monotonas_validas'] = true;
            }
        }

        // RT-FM-64 | FM64-04: Validación de que el esfuerzo de fluencia sea menor al esfuerzo último (fy < fu)
        if ($codigoFormato === 'RT-FM-64') {
            $fy = isset($valores['fluencia_mpa']) && is_numeric($valores['fluencia_mpa']) ? (float)$valores['fluencia_mpa'] : null;
            $fu = isset($valores['uts_mpa']) && is_numeric($valores['uts_mpa']) ? (float)$valores['uts_mpa'] : null;
            if (!empty($mapaCorrecciones['FM64-04']) && $fy !== null && $fu !== null) {
                $valores['fluencia_menor_uts_valida'] = ($fy > 0.0 && $fy < $fu);
            } else {
                $valores['fluencia_menor_uts_valida'] = true;
            }
        }
    }
}
