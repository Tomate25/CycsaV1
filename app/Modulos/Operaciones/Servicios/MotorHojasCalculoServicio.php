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
     * Carga la tabla de coeficientes polinomiales por posición (A/B/C) para el esclerómetro ESCL-01 (RT-FM-59)
     * desde BD (`equipos_calibracion_constantes.valor_json`) o catálogo `equipos_calibracion.json`.
     */
    private function obtenerCoeficientesEsclerometroPorPosicion(string $posicion, string $codigoEquipo = 'ESCL-01'): ?array {
        $pos = strtoupper(trim($posicion));
        if (!in_array($pos, ['A', 'B', 'C'], true)) {
            return null;
        }

        // 1. Intentar desde BD (equipos_calibracion_constantes.valor_json)
        $desdeBd = $this->equipoModelo->obtenerValorPorRefEquipo($codigoEquipo . '.coeficientes_por_posicion', null);
        if (!is_array($desdeBd)) {
            $desdeBd = $this->equipoModelo->obtenerValorPorRefEquipo($codigoEquipo, null);
        }
        if (is_array($desdeBd)) {
            $mapaPos = $desdeBd['coeficientes_por_posicion'] ?? $desdeBd;
            if (isset($mapaPos[$pos]) && is_array($mapaPos[$pos]) && isset($mapaPos[$pos]['a'], $mapaPos[$pos]['b'], $mapaPos[$pos]['c'], $mapaPos[$pos]['d'])) {
                return [
                    'coef_a' => (float)$mapaPos[$pos]['a'],
                    'coef_b' => (float)$mapaPos[$pos]['b'],
                    'coef_c' => (float)$mapaPos[$pos]['c'],
                    'coef_d' => (float)$mapaPos[$pos]['d'],
                ];
            }
        }

        // 2. Fallback al catálogo canónico equipos_calibracion.json
        $rutaCat = $this->rutaBaseHc . '/catalogos/equipos_calibracion.json';
        if (file_exists($rutaCat)) {
            $cat = json_decode((string)file_get_contents($rutaCat), true);
            foreach ($cat['equipos'] ?? [] as $eq) {
                if (($eq['codigo'] ?? '') === $codigoEquipo && isset($eq['coeficientes_por_posicion'][$pos])) {
                    $c = $eq['coeficientes_por_posicion'][$pos];
                    return [
                        'coef_a' => (float)$c['a'],
                        'coef_b' => (float)$c['b'],
                        'coef_c' => (float)$c['c'],
                        'coef_d' => (float)$c['d'],
                    ];
                }
            }
        }

        return null;
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
        $entradasEfectivas = $this->preprocesarEntradasPorCorreccion($spec, $entradas, $mapaCorrecciones, $constantes);

        // Fase 1.12 (RT-FM-59 Martillo Suizo): selección dinámica de polinomio por posición A/B/C y toggle de factor_calibracion
        if ($codigo === 'RT-FM-59') {
            $posSolicitada = strtoupper(trim((string)($entradasEfectivas['posicion'] ?? ($opciones['posicion'] ?? ''))));
            if ($posSolicitada === '') {
                $posSolicitada = strtoupper(trim(ConfigCalculoLabServicio::obtener('martillo_posicion_defecto', 'A')));
            }
            $tieneCoefsExplicitos = isset($entradasEfectivas['coef_a'], $entradasEfectivas['coef_b'], $entradasEfectivas['coef_c'], $entradasEfectivas['coef_d']);
            if (!$tieneCoefsExplicitos && in_array($posSolicitada, ['A', 'B', 'C'], true)) {
                $eqCod = trim((string)($entradasEfectivas['esclerometro_id'] ?? 'ESCL-01')) ?: 'ESCL-01';
                $coefsPos = $this->obtenerCoeficientesEsclerometroPorPosicion($posSolicitada, $eqCod);
                if ($coefsPos !== null) {
                    $constantes = array_merge($constantes, $coefsPos);
                }
            }

            if (!array_key_exists('factor_calibracion', $entradasEfectivas) || $entradasEfectivas['factor_calibracion'] === null || $entradasEfectivas['factor_calibracion'] === '') {
                $usarFactorCal = array_key_exists('usar_factor_cal', $opciones)
                    ? (bool)$opciones['usar_factor_cal']
                    : ConfigCalculoLabServicio::obtenerBool('martillo_usar_factor_cal', false);
                if (!$usarFactorCal) {
                    $constantes['factor_calibracion'] = 1.0;
                }
            }
        }

        $valores = $constantes;
        $clavesFijas = [];
        foreach ($entradasEfectivas as $k => $v) {
            if ($v !== null && $v !== '') {
                $valores[$k] = is_numeric($v) ? (float)$v : $v;
                $clavesFijas[$k] = true;
            }
        }

        // RT-FM-25: asegurar que ret_3in por defecto sea 0.0 si no se ingresó, preservando total_retenido
        if ($codigo === 'RT-FM-25' && !array_key_exists('ret_3in', $valores)) {
            $valores['ret_3in'] = 0.0;
        }

        $formulas = $spec['formulas'] ?? [];
        $formulasEfectivas = $this->aplicarParametrizacionCorrecciones($spec, $formulas, $mapaCorrecciones);

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

        // Postprocesamiento de validaciones y reglas dudosas delegadas en manejadores del JSON
        $this->postprocesarValoresPorCorreccion($spec, $valores, $clavesFijas, $formulasEfectivas, $mapaCorrecciones);

        // Fase 1.12 (RT-FM-59): redondeo configurable de resistencia estimada (1 / 10 / 100 psi)
        if ($codigo === 'RT-FM-59' && isset($valores['resistencia_psi']) && is_numeric($valores['resistencia_psi'])) {
            $pasoRed = isset($opciones['redondeo_psi']) && is_numeric($opciones['redondeo_psi']) ? (int)$opciones['redondeo_psi'] : null;
            $valores['resistencia_psi_redondeada'] = ConfigCalculoLabServicio::redondearMartilloPsi((float)$valores['resistencia_psi'], $pasoRed);
        }

        $avisos = [];
        $validaciones = [];
        foreach ($spec['correcciones'] ?? [] as $corr) {
            $cid = $corr['id'];
            $usarCorregido = !empty($mapaCorrecciones[$cid]);
            if (!empty($corr['manejador'])) {
                $valRes = ManejadoresCorreccionHc::validar($corr['manejador'], $valores, $usarCorregido, ['spec' => $spec]);
                if ($valRes !== null) {
                    $validaciones[$cid] = $valRes['valido'];
                    if (!$valRes['valido'] && !empty($valRes['aviso'])) {
                        $avisos[$cid] = $valRes['aviso'];
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
        if ($codigo === 'RT-FM-59' && isset($valores['resistencia_psi_redondeada'])) {
            $calculados['resistencia_psi_redondeada'] = (float)$valores['resistencia_psi_redondeada'];
            $redondeados['resistencia_psi_redondeada'] = (float)$valores['resistencia_psi_redondeada'];
        }

        return [
            'codigo_formato' => $codigo,
            'entradas' => $entradas,
            'constantes' => $constantes,
            'valores' => $valores,
            'calculados' => $calculados,
            'redondeados' => $redondeados,
            'correcciones_aplicadas' => $mapaCorrecciones,
            'avisos' => $avisos,
            'validaciones' => $validaciones,
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

        // Preprocesamiento de especímenes a nivel de conjunto mediante manejadores del JSON
        foreach ($spec['correcciones'] ?? [] as $corr) {
            $cid = $corr['id'];
            $usarCorregido = !empty($mapaCorrecciones[$cid]);
            if (!empty($corr['manejador'])) {
                ManejadoresCorreccionHc::preprocesarConjunto($corr['manejador'], $listaEspecimenes, $datosGlobales, $usarCorregido);
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

            $valAg = null;
            foreach ($spec['correcciones'] ?? [] as $corr) {
                $cid = $corr['id'];
                $usarCorregido = !empty($mapaCorrecciones[$cid]);
                if (!empty($corr['manejador'])) {
                    $valAg = ManejadoresCorreccionHc::evaluarAgregacion($corr['manejador'], $codAg, $filasValores, $datosGlobales, $usarCorregido, $this->agregador);
                    if ($valAg !== null) {
                        break;
                    }
                }
            }

            if ($valAg === null) {
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

        // Postprocesamiento de agregaciones del conjunto mediante manejadores del JSON
        foreach ($spec['correcciones'] ?? [] as $corr) {
            $cid = $corr['id'];
            $usarCorregido = !empty($mapaCorrecciones[$cid]);
            if (!empty($corr['manejador'])) {
                ManejadoresCorreccionHc::postprocesarConjunto($corr['manejador'], $filasValores, $agregacionesResultado, $usarCorregido, $this->agregador);
            }
        }

        if ($codigo === 'RT-FM-46') {
            $compDiseno = (float)($datosGlobales['compactacion_diseno'] ?? 95.0);
            $agregacionesResultado['resumen_cbr_golpes'] = $this->agregador->resumenCbrPorGolpes($filasValores, $compDiseno);
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
     * Preprocesa las entradas antes de evaluar fórmulas delegando en los manejadores
     * declarados en el JSON.
     */
    private function preprocesarEntradasPorCorreccion(array $spec, array $entradas, array $mapaCorrecciones, array $constantes): array {
        foreach ($spec['correcciones'] ?? [] as $corr) {
            $cid = $corr['id'];
            $usarCorregido = !empty($mapaCorrecciones[$cid]);
            if (!empty($corr['manejador'])) {
                $entradas = ManejadoresCorreccionHc::preprocesar($corr['manejador'], $entradas, $usarCorregido, [
                    'constantes' => $constantes,
                    'spec' => $spec
                ]);
            }
        }
        return $entradas;
    }

    /**
     * Aplica conmutación de expresiones leyendo declarativamente del JSON (campo_afectado,
     * formula_afectada o formulas_afectadas) según el estado de usar_corregido.
     * Cero lógica cableada por código de formato.
     */
    private function aplicarParametrizacionCorrecciones(array $spec, array $formulas, array $mapaCorrecciones): array {
        $overrides = [];
        foreach ($spec['correcciones'] ?? [] as $corr) {
            $cid = $corr['id'];
            $usarCorregido = !empty($mapaCorrecciones[$cid]);

            if (!empty($corr['formulas_afectadas']) && is_array($corr['formulas_afectadas'])) {
                foreach ($corr['formulas_afectadas'] as $fa) {
                    $cod = $fa['codigo'] ?? null;
                    if ($cod) {
                        $overrides[$cod] = $usarCorregido ? ($fa['expresion_corregida'] ?? null) : ($fa['expresion_original_excel'] ?? null);
                    }
                }
            }

            $codSimple = $corr['formula_afectada'] ?? ($corr['campo_afectado'] ?? null);
            if ($codSimple) {
                $overrides[$codSimple] = $usarCorregido ? ($corr['expresion_corregida'] ?? null) : ($corr['expresion_original_excel'] ?? null);
            }
        }

        foreach ($formulas as &$f) {
            $cod = $f['codigo'];
            if (isset($overrides[$cod]) && $overrides[$cod] !== null) {
                $f['expresion'] = $overrides[$cod];
            }
        }
        unset($f);

        return $formulas;
    }

    /**
     * Ejecuta reglas y validaciones de postprocesamiento delegando en los manejadores
     * declarados en el JSON.
     */
    private function postprocesarValoresPorCorreccion(
        array $spec,
        array &$valores,
        array $clavesFijas,
        array $formulasEfectivas,
        array $mapaCorrecciones
    ): void {
        foreach ($spec['correcciones'] ?? [] as $corr) {
            $cid = $corr['id'];
            $usarCorregido = !empty($mapaCorrecciones[$cid]);
            if (!empty($corr['manejador'])) {
                ManejadoresCorreccionHc::postprocesar($corr['manejador'], $valores, $usarCorregido, [
                    'claves_fijas' => $clavesFijas,
                    'formulas_efectivas' => $formulasEfectivas,
                    'spec' => $spec
                ]);
            }
        }
    }
}
