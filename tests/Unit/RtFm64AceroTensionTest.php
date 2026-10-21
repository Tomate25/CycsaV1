<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;

class RtFm64AceroTensionTest extends TestCase {

    private MotorHojasCalculoServicio $motor;

    protected function setUp(): void {
        parent::setUp();
        $this->motor = new MotorHojasCalculoServicio();
    }

    public function testCasosPruebaOficialesRtFm64(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-64');
        $this->assertNotEmpty($spec['caso_prueba']);

        foreach ($spec['caso_prueba'] as $caso) {
            $nombreCaso = $caso['nombre'];
            $tolRel = (float)($caso['tolerancia_relativa'] ?? 0.001);
            $resultado = $this->motor->evaluarEspecimen('RT-FM-64', $caso['entradas']);
            $valores = $resultado['valores'];

            foreach ($caso['esperados'] as $variable => $esperado) {
                $this->assertArrayHasKey($variable, $valores, "Variable '{$variable}' faltante en caso '{$nombreCaso}'");
                $obtenido = (float)$valores[$variable];
                $espFloat = (float)$esperado;
                $delta = max(abs($espFloat) * $tolRel, 1e-6);

                $this->assertEqualsWithDelta(
                    $espFloat,
                    $obtenido,
                    $delta,
                    "Fallo en RT-FM-64 caso '{$nombreCaso}' variable '{$variable}'"
                );
            }
        }
    }

    public function testElongacionNormativaVsOriginalExcelEnRtFm64(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-64');
        $entradas = $spec['caso_prueba'][0]['entradas'];
        $entradas['longitud_final_marcas_cm'] = 23.6; // L0 = 20 cm -> elongación real = (23.6 - 20)/20 * 100 = 18.0%

        // Con FM64-01 activa: elongacion = 18.0%, elongacion_excel = 34.0%
        $evalCorr = $this->motor->evaluarEspecimen('RT-FM-64', $entradas, [
            'correcciones_override' => ['FM64-01' => true]
        ]);
        $this->assertEqualsWithDelta(18.0, (float)$evalCorr['valores']['elongacion'], 1e-4);
        $this->assertEqualsWithDelta(34.0, (float)$evalCorr['valores']['elongacion_excel'], 1e-4);

        // Con FM64-01 desactivada: elongacion toma elongacion_excel = 34.0%
        $evalOrig = $this->motor->evaluarEspecimen('RT-FM-64', $entradas, [
            'correcciones_override' => ['FM64-01' => false]
        ]);
        $this->assertEqualsWithDelta(34.0, (float)$evalOrig['valores']['elongacion'], 1e-4);
    }
}
