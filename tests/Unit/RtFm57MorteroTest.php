<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;

class RtFm57MorteroTest extends TestCase {

    private MotorHojasCalculoServicio $motor;

    protected function setUp(): void {
        parent::setUp();
        $this->motor = new MotorHojasCalculoServicio();
    }

    public function testCasosPruebaOficialesRtFm57(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-57');
        $this->assertNotEmpty($spec['caso_prueba']);

        foreach ($spec['caso_prueba'] as $caso) {
            $nombreCaso = $caso['nombre'];
            $tolRel = (float)($caso['tolerancia_relativa'] ?? 0.001);
            $resultado = $this->motor->evaluarEspecimen('RT-FM-57', $caso['entradas']);
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
                    "Fallo en RT-FM-57 caso '{$nombreCaso}' variable '{$variable}'"
                );
            }
        }
    }
}
