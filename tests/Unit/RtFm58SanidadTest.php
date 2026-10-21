<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;

/**
 * Prueba unitaria para RT-FM-58 (Sanidad de agregados por intemperismo acelerado con sulfatos - ASTM C88)
 * Generada desde docs/hojas_calculo/formatos/RT-FM-58.json (tolerancia relativa 0.1% = 0.001)
 */
class RtFm58SanidadTest extends TestCase {

    private MotorHojasCalculoServicio $motor;

    protected function setUp(): void {
        $this->motor = new MotorHojasCalculoServicio();
    }

    public function testCasoPruebaOficialRtFm58ConEvaluadorFormulaReal(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-58');
        $this->assertSame('RT-FM-58', $spec['codigo']);
        $this->assertSame('Plantillas', $spec['modulo_destino']);
        $this->assertTrue($spec['formato_informe_rt_fm_22']['nuevo']);
        $this->assertNull($spec['formato_informe_rt_fm_22']['letra']);
        $this->assertNotEmpty($spec['caso_prueba']);

        foreach ($spec['caso_prueba'] as $caso) {
            $entradas = $caso['entradas'];
            $esperados = $caso['esperados'];
            $toleranciaRel = (float)($caso['tolerancia_relativa'] ?? 0.001);

            $evaluacion = $this->motor->evaluarEspecimen('RT-FM-58', $entradas);

            foreach ($esperados as $campo => $valorEsperado) {
                $this->assertArrayHasKey($campo, $evaluacion['valores'], "Falta '{$campo}' en caso '{$caso['nombre']}'");
                $obtenido = (float)$evaluacion['valores'][$campo];
                $delta = max(abs((float)$valorEsperado) * $toleranciaRel, 1e-6);

                $this->assertEqualsWithDelta(
                    (float)$valorEsperado,
                    $obtenido,
                    $delta,
                    "Discrepancia en {$campo} de RT-FM-58 ({$caso['nombre']}): esperado {$valorEsperado}, obtenido {$obtenido}"
                );
            }
        }
    }

    public function testAgregacionPerdidaTotalPonderadaMultiFraccion(): void {
        $f1 = ['tamano' => '2 1/2" - 1 1/2"', 'retenido_original' => 4767, 'masa_inicial' => 4767, 'masa_final' => 4621];
        $f2 = ['tamano' => '1 1/2" - 3/4"', 'retenido_original' => 1520, 'masa_inicial' => 1005, 'masa_final' => 988];

        $conjunto = $this->motor->evaluarConjunto('RT-FM-58', [$f1, $f2]);
        $this->assertEqualsWithDelta(6287.0, $conjunto['agregaciones']['total_retenido_original'], 0.001);
        $this->assertEqualsWithDelta(75.8231, $conjunto['especimenes'][0]['valores']['gradacion_pct'], 0.001);
        $this->assertEqualsWithDelta(2.3223, $conjunto['especimenes'][0]['valores']['perdida_corregida'], 0.001);
        $this->assertEqualsWithDelta(24.1769, $conjunto['especimenes'][1]['valores']['gradacion_pct'], 0.001);
        $this->assertEqualsWithDelta(0.4090, $conjunto['especimenes'][1]['valores']['perdida_corregida'], 0.001);
        $this->assertEqualsWithDelta(2.7312, (float)$conjunto['agregaciones']['perdida_total'], 0.001);
    }

    public function testParametrizacionCorreccionDudosaFm5801ConversionUnidadKgAGramos(): void {
        // Si se indica unidad_masa = 'kg', convierte multiplicando por 1000
        $fraccionEnKg = [
            'tamano' => '2 1/2" - 1 1/2"',
            'retenido_original' => 4767,
            'masa_inicial' => 4.767,
            'masa_final' => 4.621,
            'unidad_masa' => 'kg'
        ];

        $evalCorregido = $this->motor->evaluarEspecimen('RT-FM-58', $fraccionEnKg, [
            'correcciones_override' => ['FM58-01' => true],
        ]);
        $this->assertEqualsWithDelta(146.0, (float)$evalCorregido['valores']['perdida_masa'], 146.0 * 0.001);
        $this->assertEqualsWithDelta(3.0627, (float)$evalCorregido['valores']['perdida_pct'], 3.0627 * 0.001);

        // Con unidad_masa = 'g' o sin especificar, mantiene el valor sin convertir
        $fraccionEnG = [
            'tamano' => '2 1/2" - 1 1/2"',
            'retenido_original' => 4767,
            'masa_inicial' => 4.767,
            'masa_final' => 4.621,
            'unidad_masa' => 'g'
        ];
        $evalSinConvertir = $this->motor->evaluarEspecimen('RT-FM-58', $fraccionEnG);
        $this->assertEqualsWithDelta(0.146, (float)$evalSinConvertir['valores']['perdida_masa'], 0.146 * 0.001);
    }

    /**
     * Regla FM58-01: Masas menores de 10 g capturadas explícitamente en gramos
     * nunca deben multiplicarse por 1000 con heurísticas.
     */
    public function testMasasMenoresDe10EnGramosNuncaSeAlteranSinHeuristica(): void {
        $fraccionGramosPequena = [
            'tamano' => 'No.4 - No.8',
            'retenido_original' => 150,
            'masa_inicial' => 8.50,
            'masa_final' => 7.90,
            'unidad_masa' => 'g'
        ];

        $eval = $this->motor->evaluarEspecimen('RT-FM-58', $fraccionGramosPequena, [
            'correcciones_override' => ['FM58-01' => true]
        ]);

        $this->assertEqualsWithDelta(8.50, (float)$eval['valores']['masa_inicial'], 8.50 * 0.001);
        $this->assertEqualsWithDelta(7.90, (float)$eval['valores']['masa_final'], 7.90 * 0.001);
        $this->assertEqualsWithDelta(0.60, (float)$eval['valores']['perdida_masa'], 0.60 * 0.001);

        // Sin indicar unidad_masa explícita (usa default del JSON que es 'g')
        unset($fraccionGramosPequena['unidad_masa']);
        $evalDefault = $this->motor->evaluarEspecimen('RT-FM-58', $fraccionGramosPequena);
        $this->assertEqualsWithDelta(8.50, (float)$evalDefault['valores']['masa_inicial'], 8.50 * 0.001);
        $this->assertEqualsWithDelta(7.90, (float)$evalDefault['valores']['masa_final'], 7.90 * 0.001);
    }
}
