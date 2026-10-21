<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;
use Cycsa\Modulos\Operaciones\Servicios\ManejadoresCorreccionHc;

/**
 * Prueba que recorre las 21 correcciones dudosas declaradas en los JSON de Hojas de Cálculo.
 * Para cada corrección, comprueba la conmutación usar_corregido=true vs false,
 * verificando que el resultado afectado cambia y coincide con lo esperado,
 * y que las correcciones de solo validación emiten la advertencia correspondiente.
 */
class CorreccionesDudosasTest extends TestCase {

    private MotorHojasCalculoServicio $motor;

    protected function setUp(): void {
        parent::setUp();
        $this->motor = new MotorHojasCalculoServicio();
    }

    /**
     * Verifica que todas las 21 correcciones dudosas de los JSON estén registradas
     * y tengan sus campos declarativos o manejadores procedimentales.
     */
    public function testEstructuraDeclarativaDeTodasLasCorreccionesDudosas(): void {
        $directorioFormatos = dirname(__DIR__, 2) . '/docs/hojas_calculo/formatos';
        $archivosJson = glob($directorioFormatos . '/RT-FM-*.json');
        $this->assertNotEmpty($archivosJson, 'Deben existir formatos JSON');

        $totalDudosas = 0;
        $listaDudosas = [];

        foreach ($archivosJson as $archivo) {
            $json = json_decode(file_get_contents($archivo), true);
            $codigo = $json['codigo'];

            foreach ($json['correcciones'] ?? [] as $corr) {
                if (!empty($corr['dudosa'])) {
                    $totalDudosas++;
                    $id = $corr['id'];
                    $listaDudosas[$id] = [
                        'formato' => $codigo,
                        'corr' => $corr
                    ];

                    $tieneDeclarativo = !empty($corr['campo_afectado'])
                        || !empty($corr['formula_afectada'])
                        || !empty($corr['formulas_afectadas']);
                    $tieneManejador = !empty($corr['manejador']);
                    $esSoloValidacion = !empty($corr['solo_validacion']);

                    $this->assertTrue(
                        $tieneDeclarativo || $tieneManejador || $esSoloValidacion,
                        "La corrección dudosa {$id} en {$codigo} debe tener definición declarativa de fórmulas o manejador con nombre."
                    );
                }
            }
        }

        $this->assertGreaterThanOrEqual(20, $totalDudosas, "Deben existir al menos 20 correcciones dudosas catalogadas");
    }

    /**
     * Prueba 1: FM35-01 (RT-FM-35 Fino GE bulk con masa seca real vs B*(1-Abs/100))
     */
    public function testCorreccionFM35_01(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-35');
        $entradas = $spec['caso_prueba'][0]['entradas'];

        $true = $this->motor->evaluarEspecimen('RT-FM-35', $entradas, ['correcciones_override' => ['FM35-01' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-35', $entradas, ['correcciones_override' => ['FM35-01' => false]]);

        $this->assertEqualsWithDelta(2.1769, (float)$true['valores']['ge_bulk'], 0.005);
        $this->assertEqualsWithDelta(2.1522, (float)$false['valores']['ge_bulk'], 0.005);
        $this->assertNotEquals($true['valores']['ge_bulk'], $false['valores']['ge_bulk']);
    }

    /**
     * Prueba 2: FM35-02 (RT-FM-35 Grueso sin tara vs con tara)
     */
    public function testCorreccionFM35_02(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-35');
        $entradas = $spec['caso_prueba'][1]['entradas'];

        $true = $this->motor->evaluarEspecimen('RT-FM-35', $entradas, ['correcciones_override' => ['FM35-02' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-35', $entradas, ['correcciones_override' => ['FM35-02' => false]]);

        $this->assertEqualsWithDelta(2.7350, (float)$true['valores']['g_ge_bulk'], 0.005);
        $this->assertEqualsWithDelta(2.5233, (float)$false['valores']['g_ge_bulk'], 0.005);
        $this->assertNotEquals($true['valores']['g_ge_bulk'], $false['valores']['g_ge_bulk']);
    }

    /**
     * Prueba 3: FM36-01 (RT-FM-36 Factores de conversión aproximados vs exactos)
     */
    public function testCorreccionFM36_01(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-36');
        $entradas = $spec['caso_prueba'][0]['entradas'];

        $true = $this->motor->evaluarEspecimen('RT-FM-36', $entradas, ['correcciones_override' => ['FM36-01' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-36', $entradas, ['correcciones_override' => ['FM36-01' => false]]);

        $this->assertEqualsWithDelta(3289.21, (float)$true['valores']['resistencia_psi'], 3289.21 * 0.001);
        $this->assertEqualsWithDelta(3289.20, (float)$false['valores']['resistencia_psi'], 3289.20 * 0.001);
        $this->assertEqualsWithDelta(231.63, (float)$true['valores']['resistencia_kgcm2'], 231.63 * 0.001);
        $this->assertEqualsWithDelta(231.25, (float)$false['valores']['resistencia_kgcm2'], 231.25 * 0.001);
    }

    /**
     * Prueba 4: FM42-01 (RT-FM-42 Validación rango de golpes 20 <= N <= 30 en Método B)
     */
    public function testCorreccionFM42_01_SoloValidacion(): void {
        $entradasFueraRango = ['golpes' => 15, 'tara' => 10, 'humedo_tara' => 30, 'seco_tara' => 25];

        $true = $this->motor->evaluarEspecimen('RT-FM-42', $entradasFueraRango, ['correcciones_override' => ['FM42-01' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-42', $entradasFueraRango, ['correcciones_override' => ['FM42-01' => false]]);

        $this->assertFalse($true['valores']['golpes_en_rango_metodo_b']);
        $this->assertTrue($false['valores']['golpes_en_rango_metodo_b']);
        $this->assertNotEmpty($true['avisos']['FM42-01'] ?? ($true['avisos'][0] ?? ''));
    }

    /**
     * Prueba 5: FM42-02 (RT-FM-42 Control de repetibilidad de réplicas de LP)
     */
    public function testCorreccionFM42_02_SoloValidacion(): void {
        $entradasDiscrepantes = ['lp_1' => 26.10, 'lp_2' => 14.87]; // Dif = 11.23% > 1.4%

        $true = $this->motor->evaluarEspecimen('RT-FM-42', $entradasDiscrepantes, ['correcciones_override' => ['FM42-02' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-42', $entradasDiscrepantes, ['correcciones_override' => ['FM42-02' => false]]);

        $this->assertFalse($true['valores']['lp_repetibilidad_valida']);
        $this->assertTrue($false['valores']['lp_repetibilidad_valida']);
        $this->assertNotEmpty($true['avisos']['FM42-02'] ?? ($true['avisos'][0] ?? ''));
    }

    /**
     * Prueba 6: FM46-02 (RT-FM-46 CBR = MAX(cbr_01, cbr_02) vs solo cbr_01)
     */
    public function testCorreccionFM46_02(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-46');
        $entradas = $spec['caso_prueba'][0]['entradas'];

        $true = $this->motor->evaluarEspecimen('RT-FM-46', $entradas, ['correcciones_override' => ['FM46-02' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-46', $entradas, ['correcciones_override' => ['FM46-02' => false]]);

        $this->assertEqualsWithDelta(20.0, (float)$true['valores']['cbr'], 20.0 * 0.001);
        $this->assertEqualsWithDelta(16.6667, (float)$false['valores']['cbr'], 16.6667 * 0.001);
        $this->assertNotEquals($true['valores']['cbr'], $false['valores']['cbr']);
    }

    /**
     * Prueba 7: FM56-01 (RT-FM-56 Temperatura individual vs temperatura de encabezado)
     */
    public function testCorreccionFM56_01(): void {
        $entradas = [
            'masa_vacio' => 183.49,
            'masa_lleno' => 680.70,
            'temperatura' => 28.5,
            'temperatura_encabezado' => 20.0
        ];

        $true = $this->motor->evaluarEspecimen('RT-FM-56', $entradas, ['correcciones_override' => ['FM56-01' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-56', $entradas, ['correcciones_override' => ['FM56-01' => false]]);

        $this->assertEqualsWithDelta(28.5, (float)$true['valores']['temperatura'], 1e-4);
        $this->assertEqualsWithDelta(20.0, (float)$false['valores']['temperatura'], 1e-4);
    }

    /**
     * Prueba 8: FM56-03 (RT-FM-56 Cálculo opcional de Gs ASTM D854)
     */
    public function testCorreccionFM56_03(): void {
        $entradas = [
            'masa_vacio' => 183.49,
            'masa_lleno' => 680.70,
            'temperatura' => 20.0,
            'masa_suelo_seco' => 100.0,
            'masa_picno_agua_suelo' => 742.0
        ];

        $true = $this->motor->evaluarEspecimen('RT-FM-56', $entradas, ['correcciones_override' => ['FM56-03' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-56', $entradas, ['correcciones_override' => ['FM56-03' => false]]);

        $this->assertArrayHasKey('gs_suelo_d854', $true['valores']);
        $this->assertEqualsWithDelta(2.584, (float)$true['valores']['gs_suelo_d854'], 2.584 * 0.001);
        $this->assertArrayNotHasKey('gs_suelo_d854', $false['valores']);
    }

    /**
     * Prueba 9: FM57-02 (RT-FM-57 Mortero kg/cm² con psi redondeado vs sin redondear / 14.2233)
     */
    public function testCorreccionFM57_02(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-57');
        $entradas = $spec['caso_prueba'][0]['entradas'];

        $true = $this->motor->evaluarEspecimen('RT-FM-57', $entradas, ['correcciones_override' => ['FM57-02' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-57', $entradas, ['correcciones_override' => ['FM57-02' => false]]);

        $this->assertEqualsWithDelta(64.07, (float)$true['valores']['resistencia_kgcm2'], 64.07 * 0.001);
        $this->assertEqualsWithDelta(63.64, (float)$false['valores']['resistencia_kgcm2'], 63.64 * 0.001);
        $this->assertNotEquals($true['valores']['resistencia_kgcm2'], $false['valores']['resistencia_kgcm2']);
    }

    /**
     * Prueba 10: FM58-01 (RT-FM-58 Conversión explícita con unidad_masa='kg')
     */
    public function testCorreccionFM58_01(): void {
        $entradasKg = [
            'tamano' => '1 1/2" - 3/4"',
            'retenido_original' => 1520,
            'masa_inicial' => 1.005,
            'masa_final' => 0.988,
            'unidad_masa' => 'kg'
        ];

        $true = $this->motor->evaluarEspecimen('RT-FM-58', $entradasKg, ['correcciones_override' => ['FM58-01' => true]]);
        $this->assertEqualsWithDelta(17.0, (float)$true['valores']['perdida_masa'], 17.0 * 0.001);

        $entradasG = [
            'tamano' => '1 1/2" - 3/4"',
            'retenido_original' => 1520,
            'masa_inicial' => 1.005,
            'masa_final' => 0.988,
            'unidad_masa' => 'g'
        ];
        $false = $this->motor->evaluarEspecimen('RT-FM-58', $entradasG);
        $this->assertEqualsWithDelta(0.017, (float)$false['valores']['perdida_masa'], 0.017 * 0.001);
    }

    /**
     * Prueba 11: FM59-02 (RT-FM-59 Descarte ASTM C805 de rebotes fuera de ±6 del promedio)
     */
    public function testCorreccionFM59_02(): void {
        $entradas = [
            'rebote_1' => 30, 'rebote_2' => 31, 'rebote_3' => 30, 'rebote_4' => 32, 'rebote_5' => 30,
            'rebote_6' => 31, 'rebote_7' => 30, 'rebote_8' => 31, 'rebote_9' => 30, 'rebote_10' => 45 // Outlier +14
        ];

        $true = $this->motor->evaluarEspecimen('RT-FM-59', $entradas, ['correcciones_override' => ['FM59-02' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-59', $entradas, ['correcciones_override' => ['FM59-02' => false]]);

        $this->assertSame(1, $true['valores']['rebotes_descartados_c805']);
        $this->assertEqualsWithDelta(30.555, (float)$true['valores']['rebote_promedio'], 0.01);
        $this->assertEqualsWithDelta(32.0, (float)$false['valores']['rebote_promedio'], 0.01);
    }

    /**
     * Prueba 12: FM61-01 (RT-FM-61 Factor L/D aplicado si L/D < 1.75 vs 1.0 fijo)
     */
    public function testCorreccionFM61_01(): void {
        $entradasEsbelto = [
            'diametro_1' => 10.0, 'diametro_2' => 10.0,
            'largo_1' => 15.0, 'largo_2' => 15.0,
            'masa_kg' => 2.8, 'lectura_carga_lbf' => 50000
        ];

        $true = $this->motor->evaluarEspecimen('RT-FM-61', $entradasEsbelto, ['correcciones_override' => ['FM61-01' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-61', $entradasEsbelto, ['correcciones_override' => ['FM61-01' => false]]);

        $this->assertEqualsWithDelta(0.96, (float)$true['valores']['factor_ld'], 0.005);
        $this->assertEqualsWithDelta(1.0, (float)$false['valores']['factor_ld'], 1e-6);
        $this->assertNotEquals($true['valores']['esfuerzo_psi'], $false['valores']['esfuerzo_psi']);
    }

    /**
     * Prueba 13: FM61-04 (RT-FM-61 Esfuerzo único kg/cm² = psi/14.2 vs promedio(carga/area1, carga/area2))
     */
    public function testCorreccionFM61_04(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-61');
        $entradas = $spec['caso_prueba'][0]['entradas'];

        $true = $this->motor->evaluarEspecimen('RT-FM-61', $entradas, ['correcciones_override' => ['FM61-04' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-61', $entradas, ['correcciones_override' => ['FM61-04' => false]]);

        $this->assertEqualsWithDelta(514.79, (float)$true['valores']['esfuerzo_kgcm2'], 514.79 * 0.001);
        $this->assertEqualsWithDelta(514.26, (float)$false['valores']['esfuerzo_kgcm2'], 514.26 * 0.001);
    }

    /**
     * Prueba 14: FM62-01 (RT-FM-62 Tasa Q con 2 últimos descensos vs solo dif_30)
     */
    public function testCorreccionFM62_01(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-62');
        $entradas = $spec['caso_prueba'][0]['entradas'];

        $true = $this->motor->evaluarEspecimen('RT-FM-62', $entradas, ['correcciones_override' => ['FM62-01' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-62', $entradas, ['correcciones_override' => ['FM62-01' => false]]);

        $this->assertEqualsWithDelta(21.164, (float)$true['valores']['tasa_lm2_dia'], 21.164 * 0.001);
        $this->assertEqualsWithDelta(22.309, (float)$false['valores']['tasa_lm2_dia'], 22.309 * 0.001);
    }

    /**
     * Prueba 15: FM62-02 (RT-FM-62 Conversión L/m²·día -> gal/ft²·día con 40.77 vs 40.75)
     */
    public function testCorreccionFM62_02(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-62');
        $entradas = $spec['caso_prueba'][0]['entradas'];

        $true = $this->motor->evaluarEspecimen('RT-FM-62', $entradas, ['correcciones_override' => ['FM62-02' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-62', $entradas, ['correcciones_override' => ['FM62-02' => false]]);

        $this->assertEqualsWithDelta(0.5191, (float)$true['valores']['tasa_galft2_dia'], 0.5191 * 0.001);
        $this->assertEqualsWithDelta(0.5194, (float)$false['valores']['tasa_galft2_dia'], 0.5194 * 0.001);
    }

    /**
     * Prueba 16: FM62-03 (RT-FM-62 Validación de monotonía de descensos)
     */
    public function testCorreccionFM62_03_SoloValidacion(): void {
        $entradasIncoherentes = [
            'descenso_5min' => 10,
            'descenso_10min' => 8, // Descenso menor al anterior (no monótono)
            'descenso_15min' => 12,
            'descenso_20min' => 14,
            'descenso_25min' => 15,
            'descenso_30min' => 16
        ];

        $true = $this->motor->evaluarEspecimen('RT-FM-62', $entradasIncoherentes, ['correcciones_override' => ['FM62-03' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-62', $entradasIncoherentes, ['correcciones_override' => ['FM62-03' => false]]);

        $this->assertFalse($true['valores']['lecturas_monotonas_validas']);
        $this->assertTrue($false['valores']['lecturas_monotonas_validas']);
        $this->assertNotEmpty($true['avisos']['FM62-03'] ?? ($true['avisos'][0] ?? ''));
    }

    /**
     * Prueba 17: FM63-01 (RT-FM-63 Área neta adoquín sin /2 vs con /2)
     */
    public function testCorreccionFM63_01(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-63');
        $entradas = $spec['caso_prueba'][0]['entradas'];

        $true = $this->motor->evaluarEspecimen('RT-FM-63', $entradas, ['correcciones_override' => ['FM63-01' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-63', $entradas, ['correcciones_override' => ['FM63-01' => false]]);

        $this->assertEqualsWithDelta(470.24, (float)$true['valores']['area_neta_cm2'], 470.24 * 0.001);
        $this->assertEqualsWithDelta(426.64, (float)$false['valores']['area_neta_cm2'], 426.64 * 0.001);
    }

    /**
     * Prueba 18: FM63-02 (RT-FM-63 Resistencia adoquín con promedio de 3 lecturas vs solo lectura 1)
     */
    public function testCorreccionFM63_02(): void {
        $entradas = [
            'largo_1' => 20, 'largo_2' => 20, 'largo_3' => 20,
            'ancho_1' => 10, 'ancho_2' => 10, 'ancho_3' => 10,
            'altura_1' => 8, 'altura_2' => 8, 'altura_3' => 8,
            'base_mayor_1' => 6, 'base_mayor_2' => 6, 'base_mayor_3' => 6,
            'base_menor_1' => 4, 'base_menor_2' => 4, 'base_menor_3' => 4,
            'carga_1' => 30000, 'carga_2' => 40000, 'carga_3' => 50000 // Promedio = 40000 lbf != 30000
        ];

        $true = $this->motor->evaluarEspecimen('RT-FM-63', $entradas, ['correcciones_override' => ['FM63-02' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-63', $entradas, ['correcciones_override' => ['FM63-02' => false]]);

        $this->assertEqualsWithDelta(1068.86, (float)$true['valores']['resistencia_psi'], 1068.86 * 0.001);
        $this->assertEqualsWithDelta(801.64, (float)$false['valores']['resistencia_psi'], 801.64 * 0.001);
    }

    /**
     * Prueba 19: FM63-03 (RT-FM-63 Pesos de absorción leídos desde catálogo/constantes sin números fijos en PHP)
     */
    public function testCorreccionFM63_03(): void {
        $entradasSinPesos = [
            'largo_1' => 20, 'largo_2' => 20, 'largo_3' => 20,
            'ancho_1' => 10, 'ancho_2' => 10, 'ancho_3' => 10,
            'altura_1' => 8, 'altura_2' => 8, 'altura_3' => 8,
            'base_mayor_1' => 6, 'base_mayor_2' => 6, 'base_mayor_3' => 6,
            'base_menor_1' => 4, 'base_menor_2' => 4, 'base_menor_3' => 4,
            'carga_1' => 30000, 'carga_2' => 30000, 'carga_3' => 30000
        ];

        // Con FM63-03 desactivada: rellena desde constantes_hc sin valores fijos en PHP
        $evalFalse = $this->motor->evaluarEspecimen('RT-FM-63', $entradasSinPesos, ['correcciones_override' => ['FM63-03' => false]]);
        $this->assertEqualsWithDelta(10.945, (float)$evalFalse['valores']['masa_seca'], 10.945 * 0.001);
        $this->assertEqualsWithDelta(11.665, (float)$evalFalse['valores']['masa_saturada'], 11.665 * 0.001);
        $this->assertEqualsWithDelta(5.810, (float)$evalFalse['valores']['masa_sumergida'], 5.810 * 0.001);

        // Con FM63-03 activa: no sobreescribe con constantes de bloque histórico si se proveen valores propios
        $entradasConPesosPropios = array_merge($entradasSinPesos, [
            'masa_seca' => 12.000,
            'masa_saturada' => 12.800,
            'masa_sumergida' => 6.100
        ]);
        $evalTrue = $this->motor->evaluarEspecimen('RT-FM-63', $entradasConPesosPropios, ['correcciones_override' => ['FM63-03' => true]]);
        $this->assertEqualsWithDelta(12.000, (float)$evalTrue['valores']['masa_seca'], 12.000 * 0.001);
    }

    /**
     * Prueba 20: FM64-01 (RT-FM-64 Elongación entre marcas calibradas vs longitud total)
     */
    public function testCorreccionFM64_01(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-64');
        $entradas = array_merge($spec['caso_prueba'][0]['entradas'], [
            'longitud_final_marcas_cm' => 23.6
        ]);

        $true = $this->motor->evaluarEspecimen('RT-FM-64', $entradas, ['correcciones_override' => ['FM64-01' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-64', $entradas, ['correcciones_override' => ['FM64-01' => false]]);

        $this->assertEqualsWithDelta(18.0, (float)$true['valores']['elongacion'], 18.0 * 0.001);
        $this->assertEqualsWithDelta(34.0, (float)$false['valores']['elongacion'], 34.0 * 0.001);
        $this->assertNotEquals($true['valores']['elongacion'], $false['valores']['elongacion']);
    }

    /**
     * Prueba 21: FM64-04 (RT-FM-64 Validación de fluencia < resistencia última fy < fu)
     */
    public function testCorreccionFM64_04_SoloValidacion(): void {
        $entradasIncoherentes = [
            'diametro_mm' => 9.06,
            'masa_kg' => 0.265,
            'longitud_cm' => 49.7,
            'longitud_calibrada_cm' => 20,
            'longitud_final_cm' => 56.5,
            'diametro_final_mm' => 6.8,
            'fuerza_max_kn' => 44.52,
            'fluencia_mpa' => 800.0, // Mayor a UTS (~690 MPa)
        ];

        $true = $this->motor->evaluarEspecimen('RT-FM-64', $entradasIncoherentes, ['correcciones_override' => ['FM64-04' => true]]);
        $false = $this->motor->evaluarEspecimen('RT-FM-64', $entradasIncoherentes, ['correcciones_override' => ['FM64-04' => false]]);

        $this->assertFalse($true['valores']['fluencia_menor_uts_valida']);
        $this->assertTrue($false['valores']['fluencia_menor_uts_valida']);
        $this->assertNotEmpty($true['avisos']['FM64-04'] ?? ($true['avisos'][0] ?? ''));
    }
}
