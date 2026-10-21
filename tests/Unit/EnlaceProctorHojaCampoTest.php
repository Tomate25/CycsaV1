<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\HojaCampoProctorServicio;

/**
 * Prueba unitaria de integración entre Proctor (RT-FM-27) y Hojas de Campo (RT-FM-54 y RT-FM-55),
 * verificando el enlace automático de PVSM (pvs_maximo) y Humedad Óptima (humedad_optima)
 * así como el soporte para las variantes 'ESTANDAR', 'campo' y 'CASA MANAGUA'.
 */
class EnlaceProctorHojaCampoTest extends TestCase {

    private HojaCampoProctorServicio $servicio;

    protected function setUp(): void {
        $this->servicio = new HojaCampoProctorServicio();
    }

    public function testEnlaceAutomaticoProctorRtFm27HaciaRtFm54YRtFm55(): void {
        $idOsPrueba = 999903;
        $muestraProctorId = 888803;

        $encabezadoProctor = [
            'codigo_muestra' => 'MS-0140-26',
            'fecha_ensayo' => '2026-10-20',
            'tipo_proctor' => 'Proctor Estandar',
            'golpes' => 25,
            'capas' => 3,
            'masa_piston' => 2.5,
            'altura_caida' => 30,
            'volumen_molde' => 947.26,
            'pvs_maximo_manual' => 1730.0,
            'humedad_optima_manual' => 14.5,
        ];

        $puntosProctor = [
            [
                'masa_molde_suelo' => 5573,
                'masa_molde' => 4161,
                'tara_suelo_humedo' => 254.7,
                'tara_suelo_seco' => 240.24,
                'tara' => 54.58,
            ],
            [
                'masa_molde_suelo' => 5852,
                'masa_molde' => 4161,
                'tara_suelo_humedo' => 275.94,
                'tara_suelo_seco' => 236.97,
                'tara' => 75.74,
            ],
        ];

        // 1. Registrar Proctor RT-FM-27 en variante CASA MANAGUA
        $resProctor = $this->servicio->registrarResultadoProctor(
            $idOsPrueba,
            $encabezadoProctor,
            $puntosProctor,
            $muestraProctorId,
            null,
            1,
            'CASA MANAGUA'
        );

        $this->assertGreaterThan(0, $resProctor['registro_id']);
        $this->assertSame('CASA MANAGUA', $resProctor['variante']);
        $this->assertEqualsWithDelta(1730.0, (float)$resProctor['pvs_maximo'], 1730.0 * 0.001);
        $this->assertEqualsWithDelta(14.5, (float)$resProctor['humedad_optima'], 14.5 * 0.001);

        // 2. Evaluar y registrar Hoja de Campo RT-FM-54 (Reemplazo de Agua) SIN pasar pvs_maximo manual
        // Debe enlazar automáticamente pvs_maximo = 1730 desde el Proctor de la misma OS
        $punto54 = [
            'vol_agua_anillo' => 57,
            'peso_excavado' => 496.5,
            'vol_agua_total' => 315,
            'rec_humedo' => 500.89,
            'rec_seco' => 450.1,
            'recipiente' => 66.11,
        ];

        $res54 = $this->servicio->evaluarYRegistrarHojaCampo(
            $idOsPrueba,
            'RT-FM-54',
            [$punto54],
            ['codigo_muestra' => 'MS-0419-26', 'capa' => 1, 'profundidad' => 25],
            $muestraProctorId,
            null,
            null,
            1,
            'campo'
        );

        $this->assertSame('RT-FM-54', $res54['codigo_formato']);
        $this->assertSame('campo', $res54['variante']);
        $this->assertEqualsWithDelta(1730.0, (float)$res54['pvs_maximo'], 1730.0 * 0.001);
        $this->assertNotEmpty($res54['proctor_enlazado']);
        $this->assertEqualsWithDelta(98.243494, (float)$res54['evaluacion']['especimenes'][0]['valores']['compactacion'], 98.243494 * 0.001);
        $this->assertCount(1, $res54['filas_rt_fm_22_b']);
        $this->assertEqualsWithDelta(98.2, (float)$res54['filas_rt_fm_22_b'][0]['grado_compactacion_pct'], 98.2 * 0.001);

        // 3. Evaluar y registrar Hoja de Campo RT-FM-55 (Cono de Arena) con pvs_maximo específico
        $punto55 = [
            'arena_calib_empleada' => 3000,
            'arena_calib_sobrante' => 1586,
            'material_tara' => 3156,
            'tara' => 15,
            'arena_empleada' => 5000,
            'arena_sobrante' => 788,
            'rec_humedo' => 257,
            'rec_seco' => 225,
            'recipiente' => 54,
            'pvs_maximo' => 1204,
        ];

        $res55 = $this->servicio->evaluarYRegistrarHojaCampo(
            $idOsPrueba,
            'RT-FM-55',
            [$punto55],
            ['codigo_muestra' => 'MC-0001-26', 'capa' => 'Base', 'profundidad' => '0.15 m']
        );

        $this->assertSame('RT-FM-55', $res55['codigo_formato']);
        $this->assertEqualsWithDelta(98.17596, (float)$res55['evaluacion']['especimenes'][0]['valores']['compactacion'], 98.17596 * 0.001);
        $this->assertCount(1, $res55['filas_rt_fm_22_b']);
    }
}
