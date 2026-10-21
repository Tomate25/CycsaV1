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

        // Molde 12 golpes: compactación = 87.9708%, CBR = 20.0%
        // Molde 25 golpes: compactación = 93.5847%, CBR = 30.0%
        // Molde 56 golpes: compactación = 98.9597%, CBR = 40.0%
        // Interpolación lineal en compactacion_diseno = 95.0%:
        // CBR(95%) = 30.0 + ((95.0 - 93.584722) / (98.959722 - 93.584722)) * (40.0 - 30.0) = 32.6331%
        $this->assertEqualsWithDelta(87.9708, $conjunto['especimenes'][0]['valores']['compactacion'], 0.01);
        $this->assertEqualsWithDelta(20.0, $conjunto['especimenes'][0]['valores']['cbr'], 0.01);
        $this->assertEqualsWithDelta(93.5847, $conjunto['especimenes'][1]['valores']['compactacion'], 0.01);
        $this->assertEqualsWithDelta(30.0, $conjunto['especimenes'][1]['valores']['cbr'], 0.01);
        $this->assertEqualsWithDelta(98.9597, $conjunto['especimenes'][2]['valores']['compactacion'], 0.01);
        $this->assertEqualsWithDelta(40.0, $conjunto['especimenes'][2]['valores']['cbr'], 0.01);
        $this->assertEqualsWithDelta(32.6331, (float)$conjunto['agregaciones']['cbr_diseno'], 0.01);
    }

    public function testParametrizacionCorreccionDudosaFm4602CbrMaxVsCbr01(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-46');
        $entradas = $spec['caso_prueba'][0]['entradas'];

        // Con FM46-02 activa (AASHTO T193: MAX(cbr_01, cbr_02)): cbr_01 = 16.6667%, cbr_02 = 20.0% -> cbr = 20.0%
        $evalCorregido = $this->motor->evaluarEspecimen('RT-FM-46', $entradas, [
            'correcciones_override' => ['FM46-02' => true],
        ]);
        $this->assertEqualsWithDelta(20.0, $evalCorregido['valores']['cbr'], 0.01);

        // Con FM46-02 desactivada (fórmula Excel sin MAX: cbr = cbr_01 = 16.6667%)
        $evalExcel = $this->motor->evaluarEspecimen('RT-FM-46', $entradas, [
            'correcciones_override' => ['FM46-02' => false],
        ]);
        $this->assertEqualsWithDelta(16.6667, $evalExcel['valores']['cbr'], 0.01);
    }
}
