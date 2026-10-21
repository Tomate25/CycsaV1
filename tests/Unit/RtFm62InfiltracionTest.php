<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;

/**
 * Prueba unitaria para RT-FM-62 (Infiltración / percolación de suelo)
 * Generada desde docs/hojas_calculo/formatos/RT-FM-62.json (tolerancia relativa 0.1% = 0.001)
 */
class RtFm62InfiltracionTest extends TestCase {

    private MotorHojasCalculoServicio $motor;

    protected function setUp(): void {
        $this->motor = new MotorHojasCalculoServicio();
    }

    public function testCasosPruebaOficialesRtFm62ConEvaluadorFormulaReal(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-62');
        $this->assertSame('RT-FM-62', $spec['codigo']);
        $this->assertSame('Hoja de Campo', $spec['modulo_destino']);
        $this->assertTrue($spec['formato_informe_rt_fm_22']['nuevo']);
        $this->assertNull($spec['formato_informe_rt_fm_22']['letra']);
        $this->assertCount(2, $spec['caso_prueba']);

        foreach ($spec['caso_prueba'] as $caso) {
            $entradas = $caso['entradas'];
            $esperados = $caso['esperados'];
            $toleranciaRel = (float)($caso['tolerancia_relativa'] ?? 0.001);

            $evaluacion = $this->motor->evaluarEspecimen('RT-FM-62', $entradas);

            foreach ($esperados as $campo => $valorEsperado) {
                $this->assertArrayHasKey($campo, $evaluacion['valores'], "Falta '{$campo}' en caso '{$caso['nombre']}'");
                $obtenido = (float)$evaluacion['valores'][$campo];
                $delta = max(abs((float)$valorEsperado) * $toleranciaRel, 1e-6);

                $this->assertEqualsWithDelta(
                    (float)$valorEsperado,
                    $obtenido,
                    $delta,
                    "Discrepancia en {$campo} de RT-FM-62 ({$caso['nombre']}): esperado {$valorEsperado}, obtenido {$obtenido}"
                );
            }
        }
    }

    public function testParametrizacionCorreccionDudosaFm6202(): void {
        $entradas = [
            'descenso_5min' => 2.5,
            'descenso_10min' => 3.0,
            'descenso_15min' => 4.0,
            'descenso_20min' => 5.5,
            'descenso_25min' => 6.7,
            'descenso_30min' => 8.2,
        ];

        $eval4077 = $this->motor->evaluarEspecimen('RT-FM-62', $entradas, [
            'correcciones_override' => ['FM62-02' => true],
        ]);
        $this->assertEqualsWithDelta(0.51911659, $eval4077['valores']['tasa_galft2_dia'], 0.0005);

        $eval4075 = $this->motor->evaluarEspecimen('RT-FM-62', $entradas, [
            'correcciones_override' => ['FM62-02' => false],
        ]);
        $this->assertEqualsWithDelta(21.1643834 / 40.75, $eval4075['valores']['tasa_galft2_dia'], 0.0005);
    }
}
