<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;

/**
 * Prueba unitaria para RT-FM-35 (Absorción y gravedades específicas bulk, SSS, aparente de agregado fino y grueso - ASTM C128 / C127)
 * Generada desde docs/hojas_calculo/formatos/RT-FM-35.json (tolerancia relativa 0.1% = 0.001)
 */
class RtFm35GravedadEspecificaTest extends TestCase {

    private MotorHojasCalculoServicio $motor;

    protected function setUp(): void {
        $this->motor = new MotorHojasCalculoServicio();
    }

    public function testCasosPruebaOficialesRtFm35ConEvaluadorFormulaReal(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-35');
        $this->assertSame('RT-FM-35', $spec['codigo']);
        $this->assertSame('Plantillas', $spec['modulo_destino']);
        $this->assertSame('G', $spec['formato_informe_rt_fm_22']['letra']);
        $this->assertCount(2, $spec['caso_prueba']);

        foreach ($spec['caso_prueba'] as $caso) {
            $entradas = $caso['entradas'];
            $esperados = $caso['esperados'];
            $toleranciaRel = (float)($caso['tolerancia_relativa'] ?? 0.001);

            $evaluacion = $this->motor->evaluarEspecimen('RT-FM-35', $entradas);

            foreach ($esperados as $campo => $valorEsperado) {
                $this->assertArrayHasKey($campo, $evaluacion['valores'], "Falta '{$campo}' en caso '{$caso['nombre']}'");
                $obtenido = (float)$evaluacion['valores'][$campo];
                $delta = max(abs((float)$valorEsperado) * $toleranciaRel, 1e-6);

                $this->assertEqualsWithDelta(
                    (float)$valorEsperado,
                    $obtenido,
                    $delta,
                    "Discrepancia en {$campo} de RT-FM-35 ({$caso['nombre']}): esperado {$valorEsperado}, obtenido {$obtenido}"
                );
            }
        }
    }

    public function testParametrizacionCorreccionDudosaFm3501Fino(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-35');
        $entradasFino = $spec['caso_prueba'][0]['entradas'];

        // Con FM35-01 activa (corregido: masa seca real 451.88 g) -> ge_bulk = 2.177
        $evalCorregido = $this->motor->evaluarEspecimen('RT-FM-35', $entradasFino, [
            'correcciones_override' => ['FM35-01' => true],
        ]);
        $this->assertEqualsWithDelta(2.1769, $evalCorregido['valores']['ge_bulk'], 0.001);
        $this->assertEqualsWithDelta(2.8338, $evalCorregido['valores']['ge_aparente'], 0.001);

        // Con FM35-01 desactivada (fórmula Excel: A = B*(1-Abs/100) = 446.76 g) -> ge_bulk = 2.1522 y ge_aparente = 2.8947
        $evalExcel = $this->motor->evaluarEspecimen('RT-FM-35', $entradasFino, [
            'correcciones_override' => ['FM35-01' => false],
        ]);
        $this->assertEqualsWithDelta(2.1522, $evalExcel['valores']['ge_bulk'], 0.001);
        $this->assertEqualsWithDelta(2.8947, $evalExcel['valores']['ge_aparente'], 0.001);
    }
}
