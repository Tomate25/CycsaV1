<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;
use Cycsa\Modulos\Configuracion\Modelos\EquipoCalibracionModelo;

/**
 * Prueba unitaria para RT-FM-56 (Calibración de picnómetros / balones para gravedad específica de suelos - ASTM D854)
 * Generada desde docs/hojas_calculo/formatos/RT-FM-56.json (tolerancia relativa 0.1% = 0.001)
 */
class RtFm56CalibracionPicnometroTest extends TestCase {

    private MotorHojasCalculoServicio $motor;
    private EquipoCalibracionModelo $equipoModelo;

    protected function setUp(): void {
        $this->equipoModelo = new EquipoCalibracionModelo();
        $this->motor = new MotorHojasCalculoServicio($this->equipoModelo);
    }

    public function testCasoPruebaOficialRtFm56ConEvaluadorFormulaReal(): void {
        $spec = $this->motor->cargarEspecificacion('RT-FM-56');
        $this->assertSame('RT-FM-56', $spec['codigo']);
        $this->assertSame('Equipos y Calibracion', $spec['modulo_destino']);
        $this->assertTrue($spec['formato_informe_rt_fm_22']['nuevo']);
        $this->assertNull($spec['formato_informe_rt_fm_22']['letra']);
        $this->assertNotEmpty($spec['caso_prueba']);

        foreach ($spec['caso_prueba'] as $caso) {
            $entradas = $caso['entradas'];
            $esperados = $caso['esperados'];
            $toleranciaRel = (float)($caso['tolerancia_relativa'] ?? 0.001);

            $evaluacion = $this->motor->evaluarEspecimen('RT-FM-56', $entradas);

            foreach ($esperados as $campo => $valorEsperado) {
                $this->assertArrayHasKey($campo, $evaluacion['valores'], "Falta '{$campo}' en caso '{$caso['nombre']}'");
                $obtenido = (float)$evaluacion['valores'][$campo];
                $delta = max(abs((float)$valorEsperado) * $toleranciaRel, 1e-6);

                $this->assertEqualsWithDelta(
                    (float)$valorEsperado,
                    $obtenido,
                    $delta,
                    "Discrepancia en {$campo} de RT-FM-56 ({$caso['nombre']}): esperado {$valorEsperado}, obtenido {$obtenido}"
                );
            }
        }
    }

    public function testIntegracionCalibracionPicnometroConModuloEquipos(): void {
        $replicas = [
            ['masa_vacio' => 183.49, 'masa_lleno' => 680.70, 'temperatura' => 27.9],
            ['masa_vacio' => 183.49, 'masa_lleno' => 680.56, 'temperatura' => 28.9],
            ['masa_vacio' => 183.49, 'masa_lleno' => 680.42, 'temperatura' => 29.8],
        ];

        $res = $this->equipoModelo->calibrarPicnometroConRtFm56('PICNO-03', $replicas, '2026-10-20');
        $this->assertIsInt($res['picnometro_id']);
        $this->assertSame('PICNO-03', $res['identificacion']);
        $this->assertEqualsWithDelta(499.07, $res['volumen_promedio'], abs(499.07) * 0.001);
        $this->assertTrue($res['cumple_d854']);
        $this->assertIsArray($res['curva_mpw']);
        $this->assertArrayHasKey('a', $res['curva_mpw']);
        $this->assertArrayHasKey('b', $res['curva_mpw']);
        $this->assertArrayHasKey('c', $res['curva_mpw']);
    }
}
