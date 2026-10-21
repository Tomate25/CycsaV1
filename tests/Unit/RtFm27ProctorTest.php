<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;

/**
 * Prueba unitaria para RT-FM-27 (Proctor estándar y modificado - ASTM D698 / D1557)
 * Generada desde docs/hojas_calculo/formatos/RT-FM-27.json (tolerancia relativa 0.1% = 0.001)
 */
class RtFm27ProctorTest extends TestCase {

    private MotorHojasCalculoServicio $motor;

    protected function setUp(): void {
        $this->motor = new MotorHojasCalculoServicio();
    }

    public function testCasosPruebaOficialesRtFm27ConEvaluadorFormulaReal(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-27');
        $this->assertSame('RT-FM-27', $spec['codigo']);
        $this->assertSame('Plantillas', $spec['modulo_destino']);
        $this->assertSame('C', $spec['formato_informe_rt_fm_22']['letra']);
        $this->assertCount(2, $spec['caso_prueba']);

        foreach ($spec['caso_prueba'] as $caso) {
            $entradas = $caso['entradas'];
            $esperados = $caso['esperados'];
            $toleranciaRel = (float)($caso['tolerancia_relativa'] ?? 0.001);

            $evaluacion = $this->motor->evaluarEspecimen('RT-FM-27', $entradas);

            foreach ($esperados as $campo => $valorEsperado) {
                $this->assertArrayHasKey($campo, $evaluacion['valores'], "Falta '{$campo}' en caso '{$caso['nombre']}'");
                $obtenido = (float)$evaluacion['valores'][$campo];
                $delta = max(abs((float)$valorEsperado) * $toleranciaRel, 1e-6);

                $this->assertEqualsWithDelta(
                    (float)$valorEsperado,
                    $obtenido,
                    $delta,
                    "Discrepancia en {$campo} de RT-FM-27 ({$caso['nombre']}): esperado {$valorEsperado}, obtenido {$obtenido}"
                );
            }
        }
    }

    public function testAgregacionesPvsmYHumedadOptimaEnConjunto(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-27');
        $p1 = $spec['caso_prueba'][0]['entradas'];
        $p5 = $spec['caso_prueba'][1]['entradas'];

        $conjunto = $this->motor->evaluarConjunto('RT-FM-27', [$p1, $p5]);
        $this->assertEqualsWithDelta(1437.66, $conjunto['agregaciones']['pvs_maximo'], 0.1);
        $this->assertEqualsWithDelta(24.1704, $conjunto['agregaciones']['humedad_optima'], 0.05);
    }
}
