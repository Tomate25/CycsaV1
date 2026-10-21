<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;

/**
 * Prueba unitaria para RT-FM-42 (Límites de consistencia Atterberg: LL, LP, IP - ASTM D4318)
 * Generada desde docs/hojas_calculo/formatos/RT-FM-42.json (tolerancia relativa 0.1% = 0.001)
 */
class RtFm42AgregadoFinoTest extends TestCase {

    private MotorHojasCalculoServicio $motor;

    protected function setUp(): void {
        $this->motor = new MotorHojasCalculoServicio();
    }

    public function testCasoPruebaOficialRtFm42ConEvaluadorFormulaReal(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-42');
        $this->assertSame('RT-FM-42', $spec['codigo']);
        $this->assertSame('Plantillas', $spec['modulo_destino']);
        $this->assertSame('F', $spec['formato_informe_rt_fm_22']['letra']);
        $this->assertNotEmpty($spec['caso_prueba']);

        foreach ($spec['caso_prueba'] as $caso) {
            $entradas = $caso['entradas'];
            $esperados = $caso['esperados'];
            $toleranciaRel = (float)($caso['tolerancia_relativa'] ?? 0.001);

            $evaluacion = $this->motor->evaluarEspecimen('RT-FM-42', $entradas);

            foreach ($esperados as $campo => $valorEsperado) {
                $this->assertArrayHasKey($campo, $evaluacion['valores'], "Falta '{$campo}' en caso '{$caso['nombre']}'");
                $obtenido = (float)$evaluacion['valores'][$campo];
                $delta = max(abs((float)$valorEsperado) * $toleranciaRel, 1e-6);

                $this->assertEqualsWithDelta(
                    (float)$valorEsperado,
                    $obtenido,
                    $delta,
                    "Discrepancia en {$campo} de RT-FM-42 ({$caso['nombre']}): esperado {$valorEsperado}, obtenido {$obtenido}"
                );
            }
        }
    }
}
