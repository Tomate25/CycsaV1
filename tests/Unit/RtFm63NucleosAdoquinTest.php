<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;

class RtFm63NucleosAdoquinTest extends TestCase {

    private MotorHojasCalculoServicio $motor;

    protected function setUp(): void {
        parent::setUp();
        $this->motor = new MotorHojasCalculoServicio();
    }

    public function testCasosPruebaOficialesRtFm63(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-63');
        $this->assertNotEmpty($spec['caso_prueba']);

        foreach ($spec['caso_prueba'] as $caso) {
            $nombreCaso = $caso['nombre'];
            $tolRel = (float)($caso['tolerancia_relativa'] ?? 0.001);
            $resultado = $this->motor->evaluarEspecimen('RT-FM-63', $caso['entradas']);
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
                    "Fallo en RT-FM-63 caso '{$nombreCaso}' variable '{$variable}'"
                );
            }
        }
    }

    public function testParametrizacionCorreccionesDudosasRtFm63(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-63');
        $entradas = $spec['caso_prueba'][0]['entradas'];

        // Modo corregido (usa carga_prom = 202449)
        $evalCorr = $this->motor->evaluarEspecimen('RT-FM-63', $entradas, [
            'correcciones_override' => ['FM63-02' => true]
        ]);

        // Modo original Excel col B (usa solo carga_1 = 201240)
        $evalOrig = $this->motor->evaluarEspecimen('RT-FM-63', $entradas, [
            'correcciones_override' => ['FM63-02' => false]
        ]);

        $this->assertNotEquals($evalCorr['valores']['resistencia_psi'], $evalOrig['valores']['resistencia_psi']);
        $this->assertEqualsWithDelta(3201.957, (float)$evalOrig['valores']['resistencia_psi'], 0.5);
    }
}
