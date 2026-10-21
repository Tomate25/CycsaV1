<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;

class RtFm36BloquesTest extends TestCase {

    private MotorHojasCalculoServicio $motor;

    protected function setUp(): void {
        parent::setUp();
        $this->motor = new MotorHojasCalculoServicio();
    }

    public function testCasosPruebaOficialesRtFm36(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-36');
        $this->assertNotEmpty($spec['caso_prueba']);

        foreach ($spec['caso_prueba'] as $caso) {
            $nombreCaso = $caso['nombre'];
            $tolRel = (float)($caso['tolerancia_relativa'] ?? 0.001);
            $resultado = $this->motor->evaluarEspecimen('RT-FM-36', $caso['entradas']);
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
                    "Fallo en RT-FM-36 caso '{$nombreCaso}' variable '{$variable}'"
                );
            }
        }
    }

    public function testAgregacionesPromediosEntreBloquesRtFm36(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-36');
        $entradasConjunto = array_map(fn($c) => $c['entradas'], $spec['caso_prueba']);

        $resConjunto = $this->motor->evaluarConjunto('RT-FM-36', $entradasConjunto);
        $this->assertArrayHasKey('resistencia_promedio_psi', $resConjunto['agregaciones']);
        // Promedio de 3289.2123 y 2547.0335 = 2918.1229
        $this->assertEqualsWithDelta(2918.1229, (float)$resConjunto['agregaciones']['resistencia_promedio_psi'], 1.0);
    }
}
