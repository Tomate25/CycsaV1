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
 * - Aplicar parametrización de correcciones marcadas como "dudosa" hasta confirmación de Calidad
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

    /**
     * Obtiene la ruta base de las especificaciones de hojas de cálculo.
     */
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
        if (isset($this->cacheFormatos[$codigo])) {
            return $this->cacheFormatos[$codigo];
        }

        $ruta = $this->rutaBaseHc . '/formatos/' . $codigo . '.json';
        if (!file_exists($ruta)) {
            throw new InvalidArgumentException("No existe la especificación de hoja de cálculo para el formato {$codigo} en {$ruta}");
        }

        $contenido = file_get_contents($ruta);
        $spec = json_decode($contenido, true);
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
     * Obtiene el mapa de correcciones dudosas activas/inactivas para el formato.
     * Por defecto todas las correcciones validadas están activas (usar_corregido = true),
     * pero pueden conmutarse desde BD (configuracion_correcciones_hc) o por $opciones['correcciones_override'].
     *
     * @param string $codigoFormato
     * @param array $opciones
     * @return array<string, bool> Mapa [correccion_id => usar_corregido]
     */
    public function obtenerMapaCorrecciones(string $codigoFormato, array $opciones = []): array {
        $spec = $this->cargarEspecificacion($codigoFormato);
        $mapa = [];

        foreach ($spec['correcciones'] ?? [] as $corr) {
            $mapa[$corr['id']] = true; // Por defecto usa la fórmula corregida validada
        }

        // Consultar overrides persistidos en BD por Calidad
        $enBd = $this->equipoModelo->obtenerCorreccionesFormato($codigoFormato);
        foreach ($enBd as $cid => $fila) {
            $mapa[$cid] = ((int)($fila['usar_corregido'] ?? 1)) === 1;
        }

        // Overrides explícitos en tiempo de ejecución
        if (!empty($opciones['correcciones_override']) && is_array($opciones['correcciones_override'])) {
            foreach ($opciones['correcciones_override'] as $cid => $val) {
                $mapa[$cid] = (bool)$val;
            }
        }

        return $mapa;
    }

    /**
     * Evalúa todas las fórmulas de un espécimen / fila / determinación usando EvaluadorFormula.
     *
     * Respeta valores explícitamente provistos en $entradas (sin sobrescribirlos si son variables
     * intermedias provistas manualmente, tal como en los casos de prueba con lecturas directas).
     *
     * @param string $codigoFormato Ej: "RT-FM-61"
     * @param array<string, mixed> $entradas Valores de entrada medidos
     * @param array $opciones Opciones adicionales (ej. correcciones_override)
     * @return array{
     *   codigo_formato: string,
     *   entradas: array<string, mixed>,
     *   constantes: array<string, float>,
     *   valores: array<string, mixed>,
     *   calculados: array<string, float|null>,
     *   redondeados: array<string, float|null>,
     *   correcciones_aplicadas: array<string, bool>
     * }
     */
    public function evaluarEspecimen(string $codigoFormato, array $entradas, array $opciones = []): array {
        $spec = $this->cargarEspecificacion($codigoFormato);
        $constantes = $this->resolverConstantes($codigoFormato);
        $mapaCorrecciones = $this->obtenerMapaCorrecciones($codigoFormato, $opciones);

        // Valores iniciales: constantes primero, luego entradas explícitas encima
        $valores = $constantes;
        $clavesFijas = [];
        foreach ($entradas as $k => $v) {
            if ($v !== null && $v !== '') {
                $valores[$k] = is_numeric($v) ? (float)$v : $v;
                $clavesFijas[$k] = true;
            }
        }

        $formulas = $spec['formulas'] ?? [];

        // Ajustar expresiones si alguna corrección dudosa fue desactivada explícitamente por Calidad
        $formulasEfectivas = $this->aplicarParametrizacionCorrecciones($codigoFormato, $formulas, $mapaCorrecciones);

        $numPasadas = count($formulasEfectivas) + 2;
        for ($pasada = 0; $pasada < $numPasadas; $pasada++) {
            foreach ($formulasEfectivas as $f) {
                $codFormula = $f['codigo'];

                // Si el valor fue provisto explícitamente en las entradas, no sobrescribirlo
                if (isset($clavesFijas[$codFormula])) {
                    continue;
                }

                $expresion = $f['expresion'];
                try {
                    $res = EvaluadorFormula::evaluar($expresion, $valores);
                    if ($res !== null) {
                        $valores[$codFormula] = $res;
                    }
                } catch (\Throwable $e) {
                    // Si alguna variable aún no está disponible o falla en esta pasada, continuar
                }
            }
        }

        // RT-FM-59 FM59-02: Norma ASTM C805 descarta lecturas que difieran más de 6 unidades del promedio inicial
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
            'codigo_formato' => $spec['codigo'],
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
     *
     * @param string $codigoFormato
     * @param array<int, array<string, mixed>> $listaEspecimenes Arreglo de entradas por espécimen/fila
     * @param array<string, mixed> $datosGlobales Datos generales de la muestra/ensayo
     * @param array $opciones
     * @return array
     */
    public function evaluarConjunto(string $codigoFormato, array $listaEspecimenes, array $datosGlobales = [], array $opciones = []): array {
        $spec = $this->cargarEspecificacion($codigoFormato);
        $resultadosEspecimenes = [];
        $filasValores = [];

        foreach ($listaEspecimenes as $idx => $entradaEsp) {
            $entradaCombinada = array_merge($datosGlobales, $entradaEsp);
            $evalEsp = $this->evaluarEspecimen($codigoFormato, $entradaCombinada, $opciones);
            $resultadosEspecimenes[$idx] = $evalEsp;
            $filasValores[$idx] = $evalEsp['valores'];
        }

        // Ejecutar agregaciones declaradas en la especificación (con segunda pasada para dependencias entre agregaciones y fórmulas por espécimen)
        $agregacionesResultado = [];
        foreach ($spec['agregaciones'] ?? [] as $ag) {
            $codAg = $ag['codigo'];
            $funcion = $ag['funcion'];
            $campoOrigen = $ag['de'] ?? null;

            $valAg = $this->agregador->ejecutarAgregacion($funcion, $filasValores, $campoOrigen, $datosGlobales);
            $agregacionesResultado[$codAg] = $valAg;

            if (is_numeric($valAg)) {
                $datosGlobales[$codAg] = (float)$valAg;
                // Re-evaluar especímenes por si alguna fórmula por espécimen depende de esta agregación (ej. RT-FM-58 gradacion_pct depende de total_retenido_original)
                foreach ($listaEspecimenes as $idx => $entradaEsp) {
                    $entradaCombinada = array_merge($datosGlobales, $entradaEsp);
                    $evalEsp = $this->evaluarEspecimen($codigoFormato, $entradaCombinada, $opciones);
                    $resultadosEspecimenes[$idx] = $evalEsp;
                    $filasValores[$idx] = $evalEsp['valores'];
                }
            } elseif (is_array($valAg)) {
                if (isset($valAg['pct_grava'])) {
                    $datosGlobales['pct_grava'] = $valAg['pct_grava'];
                    $datosGlobales['pct_arena'] = $valAg['pct_arena'];
                    $datosGlobales['pct_finos'] = $valAg['pct_finos'];
                }
            }
        }

        // Si existen fórmulas globales (por_especimen === false) que dependen de agregaciones o datosGlobales,
        // evaluarlas sobre el contexto combinado
        $contextoGlobal = array_merge(
            $this->resolverConstantes($codigoFormato),
            $datosGlobales
        );
        foreach ($agregacionesResultado as $k => $v) {
            if (is_numeric($v)) {
                $contextoGlobal[$k] = (float)$v;
            }
        }
        // También incorporar valores del primer espécimen si hay un único registro global
        if (count($filasValores) === 1) {
            foreach ($filasValores[0] as $k => $v) {
                if (!isset($contextoGlobal[$k]) && is_numeric($v)) {
                    $contextoGlobal[$k] = (float)$v;
                }
            }
        }

        $formulasGlobales = array_filter($spec['formulas'] ?? [], fn($f) => empty($f['por_especimen']));
        if (!empty($formulasGlobales)) {
            $evalGlobal = $this->evaluarEspecimen($codigoFormato, $contextoGlobal, $opciones);
            foreach ($formulasGlobales as $fg) {
                $codFg = $fg['codigo'];
                if (isset($evalGlobal['calculados'][$codFg]) && $evalGlobal['calculados'][$codFg] !== null) {
                    $contextoGlobal[$codFg] = $evalGlobal['calculados'][$codFg];
                }
            }
            // Re-ejecutar agregaciones que dependan de fórmulas globales o viceversa
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
            'codigo_formato' => $spec['codigo'],
            'nombre_formato' => $spec['nombre'],
            'norma' => $spec['norma']['texto'] ?? '',
            'formato_informe_rt_fm_22' => $spec['formato_informe_rt_fm_22'] ?? null,
            'especimenes' => $resultadosEspecimenes,
            'agregaciones' => $agregacionesResultado,
            'contexto_global' => $contextoGlobal,
        ];
    }

    /**
     * Aplica conmutación de expresiones para las correcciones marcadas como "dudosa"
     * en caso de que Calidad desactive temporalmente alguna corrección.
     */
    private function aplicarParametrizacionCorrecciones(string $codigoFormato, array $formulas, array $mapaCorrecciones): array {
        foreach ($formulas as &$f) {
            // RT-FM-61 FM61-01: Factor L/D aplicado si L/D < 1.75 (corregido) vs nunca aplicado = 1.0 (original excel)
            if ($codigoFormato === 'RT-FM-61' && $f['codigo'] === 'factor_ld' && isset($mapaCorrecciones['FM61-01']) && !$mapaCorrecciones['FM61-01']) {
                $f['expresion'] = '1';
            }

            // RT-FM-63 FM63-01: Área neta sin /2 (corregido D/E) vs con /2 (original B/C)
            if ($codigoFormato === 'RT-FM-63' && $f['codigo'] === 'area_neta_cm2' && isset($mapaCorrecciones['FM63-01']) && !$mapaCorrecciones['FM63-01']) {
                $f['expresion'] = '{largo_prom}*{ancho_prom}+(2*(({base_mayor_prom}+{base_menor_prom})/2)*{altura_prom})/2';
            }

            // RT-FM-63 FM63-02: Resistencia reportada usa promedio de 3 lecturas (corregido) vs solo lectura 1 (original excel)
            if ($codigoFormato === 'RT-FM-63' && $f['codigo'] === 'resistencia_psi' && isset($mapaCorrecciones['FM63-02']) && !$mapaCorrecciones['FM63-02']) {
                $f['expresion'] = '{carga_1}/{area_neta_in2}*{factor_acabado}';
            }

            // RT-FM-35 FM35-01: Agregado fino usa masa seca real (corregido: 2.177 y 2.834) vs B - B*Abs/100 (original excel: 2.152 y 2.895)
            if ($codigoFormato === 'RT-FM-35' && isset($mapaCorrecciones['FM35-01']) && !$mapaCorrecciones['FM35-01']) {
                if ($f['codigo'] === 'ge_bulk') {
                    $f['expresion'] = '({masa_sss}*(1-{absorcion}/100))/({volumen_frasco}-{masa_agua_agregada})';
                } elseif ($f['codigo'] === 'ge_aparente') {
                    $f['expresion'] = '({masa_sss}*(1-{absorcion}/100))/(({volumen_frasco}-{masa_agua_agregada})-({masa_sss}-({masa_sss}*(1-{absorcion}/100))))';
                }
            }

            // RT-FM-64 FM64-01: Elongación usa marcas calibradas (corregido) vs largo total varilla (original excel)
            if ($codigoFormato === 'RT-FM-64' && $f['codigo'] === 'elongacion' && isset($mapaCorrecciones['FM64-01']) && !$mapaCorrecciones['FM64-01']) {
                $f['expresion'] = '{elongacion_excel}';
            }

            // RT-FM-62 FM62-02: Factor L/m²·día -> gal/ft²·día (40.77 mantenido en Excel vs 40.75 con 1m = 3.2808ft)
            if ($codigoFormato === 'RT-FM-62' && $f['codigo'] === 'tasa_galft2_dia' && isset($mapaCorrecciones['FM62-02']) && !$mapaCorrecciones['FM62-02']) {
                $f['expresion'] = '{tasa_lm2_dia}/40.75';
            }
        }
        unset($f);

        return $formulas;
    }
}
