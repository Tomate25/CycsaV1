<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;

/**
 * Prueba unitaria para RT-FM-59 (Número de rebote con esclerómetro / martillo suizo y resistencia estimada - ASTM C805)
 * Generada desde docs/hojas_calculo/formatos/RT-FM-59.json (tolerancia relativa 0.1% = 0.001)
 */
class RtFm59AbrasionTest extends TestCase {

    private MotorHojasCalculoServicio $motor;

    protected function setUp(): void {
        $this->motor = new MotorHojasCalculoServicio();
    }

    public function testCasoPruebaOficialRtFm59ConEvaluadorFormulaReal(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-59');
        $this->assertSame('RT-FM-59', $spec['codigo']);
        $this->assertSame('Plantillas', $spec['modulo_destino']);
        $this->assertSame('D', $spec['formato_informe_rt_fm_22']['letra']);
        $this->assertNotEmpty($spec['caso_prueba']);

        foreach ($spec['caso_prueba'] as $caso) {
            $entradas = $caso['entradas'];
            $esperados = $caso['esperados'];
            $toleranciaRel = (float)($caso['tolerancia_relativa'] ?? 0.001);

            $evaluacion = $this->motor->evaluarEspecimen('RT-FM-59', $entradas);

            foreach ($esperados as $campo => $valorEsperado) {
                $this->assertArrayHasKey($campo, $evaluacion['valores'], "Falta '{$campo}' en caso '{$caso['nombre']}'");
                $obtenido = (float)$evaluacion['valores'][$campo];
                $delta = max(abs((float)$valorEsperado) * $toleranciaRel, 1e-6);

                $this->assertEqualsWithDelta(
                    (float)$valorEsperado,
                    $obtenido,
                    $delta,
                    "Discrepancia en {$campo} de RT-FM-59 ({$caso['nombre']}): esperado {$valorEsperado}, obtenido {$obtenido}"
                );
            }
        }
    }

    public function testParametrizacionDescarteC805Fm5902(): void {
        $entradasConOutlier = [
            'rebote_1' => 22, 'rebote_2' => 23, 'rebote_3' => 20, 'rebote_4' => 23, 'rebote_5' => 20,
            'rebote_6' => 22, 'rebote_7' => 24, 'rebote_8' => 23, 'rebote_9' => 21, 'rebote_10' => 35, // outlier > prom+6
        ];

        // Con FM59-02 activa (C805 descarta el 35 porque difiere > 6 del promedio inicial 23.3) -> promedio de los 9 restantes = 22.0
        $evalC805 = $this->motor->evaluarEspecimen('RT-FM-59', $entradasConOutlier, [
            'correcciones_override' => ['FM59-02' => true],
        ]);
        $this->assertSame(1, $evalC805['valores']['rebotes_descartados_c805']);
        $this->assertEqualsWithDelta(22.0, $evalC805['valores']['rebote_promedio'], 0.01);

        // Con FM59-02 desactivada (original Excel no descarta) -> promedio de los 10 = 23.3
        $evalExcel = $this->motor->evaluarEspecimen('RT-FM-59', $entradasConOutlier, [
            'correcciones_override' => ['FM59-02' => false],
        ]);
        $this->assertEqualsWithDelta(23.3, $evalExcel['valores']['rebote_promedio'], 0.01);
    }
}
