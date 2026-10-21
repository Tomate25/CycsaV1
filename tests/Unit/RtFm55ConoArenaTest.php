<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;

/**
 * Prueba unitaria para RT-FM-55 (Densidad in situ y grado de compactación por cono de arena - ASTM D1556)
 * Generada desde docs/hojas_calculo/formatos/RT-FM-55.json (tolerancia relativa 0.1% = 0.001)
 */
class RtFm55ConoArenaTest extends TestCase {

    private MotorHojasCalculoServicio $motor;

    protected function setUp(): void {
        $this->motor = new MotorHojasCalculoServicio();
    }

    public function testCasoPruebaOficialRtFm55ConEvaluadorFormulaReal(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-55');
        $this->assertSame('RT-FM-55', $spec['codigo']);
        $this->assertSame('Hoja de Campo', $spec['modulo_destino']);
        $this->assertSame('B', $spec['formato_informe_rt_fm_22']['letra']);
        $this->assertNotEmpty($spec['caso_prueba']);

        foreach ($spec['caso_prueba'] as $caso) {
            $entradas = $caso['entradas'];
            $esperados = $caso['esperados'];
            $toleranciaRel = (float)($caso['tolerancia_relativa'] ?? 0.001);

            $evaluacion = $this->motor->evaluarEspecimen('RT-FM-55', $entradas);

            foreach ($esperados as $campo => $valorEsperado) {
                $this->assertArrayHasKey($campo, $evaluacion['valores'], "Falta '{$campo}' en caso '{$caso['nombre']}'");
                $obtenido = (float)$evaluacion['valores'][$campo];
                $delta = max(abs((float)$valorEsperado) * $toleranciaRel, 1e-6);

                $this->assertEqualsWithDelta(
                    (float)$valorEsperado,
                    $obtenido,
                    $delta,
                    "Discrepancia en {$campo} de RT-FM-55 ({$caso['nombre']}): esperado {$valorEsperado}, obtenido {$obtenido}"
                );
            }
        }
    }

    public function testConstantePuArenaInyectadaDesdeEquipoArenaCono01(): void {
        $constantes = $this->motor->resolverConstantes('RT-FM-55');
        $this->assertArrayHasKey('pu_arena', $constantes);
        $this->assertEqualsWithDelta(1250.0, $constantes['pu_arena'], 0.01);
    }
}
