<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;

/**
 * Prueba unitaria para RT-FM-25 (Contenido de humedad y granulometría por lavado y tamizado + SUCS)
 * Generada desde docs/hojas_calculo/formatos/RT-FM-25.json (tolerancia relativa 0.1% = 0.001)
 */
class RtFm25GranulometriaTest extends TestCase {

    private MotorHojasCalculoServicio $motor;

    protected function setUp(): void {
        $this->motor = new MotorHojasCalculoServicio();
    }

    public function testCasoPruebaOficialRtFm25ConEvaluadorFormulaReal(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-25');
        $this->assertSame('RT-FM-25', $spec['codigo']);
        $this->assertSame('Plantillas', $spec['modulo_destino']);
        $this->assertSame('F', $spec['formato_informe_rt_fm_22']['letra']);
        $this->assertNotEmpty($spec['caso_prueba']);

        foreach ($spec['caso_prueba'] as $caso) {
            $entradas = $caso['entradas'];
            $esperados = $caso['esperados'];
            $toleranciaRel = (float)($caso['tolerancia_relativa'] ?? 0.001);

            $evaluacion = $this->motor->evaluarEspecimen('RT-FM-25', $entradas);

            foreach ($esperados as $campo => $valorEsperado) {
                $this->assertArrayHasKey($campo, $evaluacion['valores'], "Falta '{$campo}' en caso '{$caso['nombre']}'");
                $obtenido = (float)$evaluacion['valores'][$campo];
                $delta = max(abs((float)$valorEsperado) * $toleranciaRel, 1e-6);

                $this->assertEqualsWithDelta(
                    (float)$valorEsperado,
                    $obtenido,
                    $delta,
                    "Discrepancia en {$campo} de RT-FM-25 ({$caso['nombre']}): esperado {$valorEsperado}, obtenido {$obtenido}"
                );
            }
        }
    }

    public function testCurvaGranulometricaYClasificacionSucs(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-25');
        $entradas = $spec['caso_prueba'][0]['entradas'];

        $conjunto = $this->motor->evaluarConjunto('RT-FM-25', [$entradas]);
        $this->assertIsArray($conjunto['agregaciones']['curva_granulometrica']);
        $this->assertArrayHasKey('tamices', $conjunto['agregaciones']['curva_granulometrica']);
        $this->assertIsArray($conjunto['agregaciones']['clasificacion_sucs']);
        $this->assertSame('SM', $conjunto['agregaciones']['clasificacion_sucs']['simbolo']);
    }
}
