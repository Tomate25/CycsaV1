<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;

/**
 * Prueba unitaria para RT-FM-46 (Relación de soporte California CBR de laboratorio - AASHTO T193 / ASTM D1883)
 * Generada desde docs/hojas_calculo/formatos/RT-FM-46.json (tolerancia relativa 0.1% = 0.001)
 */
class RtFm46CbrTest extends TestCase {

    private MotorHojasCalculoServicio $motor;

    protected function setUp(): void {
        $this->motor = new MotorHojasCalculoServicio();
    }

    public function testCasoPruebaOficialRtFm46ConEvaluadorFormulaReal(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-46');
        $this->assertSame('RT-FM-46', $spec['codigo']);
        $this->assertSame('Plantillas', $spec['modulo_destino']);
        $this->assertTrue($spec['formato_informe_rt_fm_22']['nuevo']);
        $this->assertNull($spec['formato_informe_rt_fm_22']['letra']);
        $this->assertNotEmpty($spec['caso_prueba']);

        foreach ($spec['caso_prueba'] as $caso) {
            $entradas = $caso['entradas'];
            $esperados = $caso['esperados'];
            $toleranciaRel = (float)($caso['tolerancia_relativa'] ?? 0.001);

            $evaluacion = $this->motor->evaluarEspecimen('RT-FM-46', $entradas);

            foreach ($esperados as $campo => $valorEsperado) {
                $this->assertArrayHasKey($campo, $evaluacion['valores'], "Falta '{$campo}' en caso '{$caso['nombre']}'");
                $obtenido = (float)$evaluacion['valores'][$campo];
                $delta = max(abs((float)$valorEsperado) * $toleranciaRel, 1e-6);

                $this->assertEqualsWithDelta(
                    (float)$valorEsperado,
                    $obtenido,
                    $delta,
                    "Discrepancia en {$campo} de RT-FM-46 ({$caso['nombre']}): esperado {$valorEsperado}, obtenido {$obtenido}"
                );
            }
        }
    }

    public function testInterpolacionCbrDisenoEnConjuntoDe3Moldes(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-46');
        $moldeBase = $spec['caso_prueba'][0]['entradas'];

        $m12 = array_merge($moldeBase, ['golpes' => 12, 'masa_molde_suelo' => 12268, 'lectura_01' => 50, 'lectura_02' => 90]);
        $m25 = array_merge($moldeBase, ['golpes' => 25, 'masa_molde_suelo' => 12550, 'lectura_01' => 75, 'lectura_02' => 135]);
        $m56 = array_merge($moldeBase, ['golpes' => 56, 'masa_molde_suelo' => 12820, 'lectura_01' => 105, 'lectura_02' => 180]);

        $conjunto = $this->motor->evaluarConjunto('RT-FM-46', [$m12, $m25, $m56], [
            'pvs_maximo' => 1350,
            'compactacion_diseno' => 95.0,
        ]);

        $this->assertNotNull($conjunto['agregaciones']['cbr_diseno']);
        $this->assertGreaterThan(20.0, $conjunto['agregaciones']['cbr_diseno']);
    }
}
