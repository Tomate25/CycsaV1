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
        $this->assertEqualsWithDelta(6287.0, $conjunto['agregaciones']['total_retenido_original'], 0.01);
        $this->assertGreaterThan(2.32, $conjunto['agregaciones']['perdida_total']);
    }
}
