<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;

class HojaSolicitudPrefillTest extends TestCase {

    private HojasServicioControlador $controlador;

    protected function setUp(): void {
        parent::setUp();
        $this->controlador = new HojasServicioControlador();
    }

    public function testDetectarParametrosYNaturalezaSueloGranulometria(): void {
        $ensayos = [
            [
                'descripcion_ensayo' => 'Granulometría de Suelos – ASTM D6913/D6913M-17 (2025)',
                'nombre_ensayo' => 'Granulometría de Suelos – ASTM D6913/D6913M-17 (2025)',
                'procedimiento' => 'CYCSA-PE-16',
                'norma_astm' => 'ASTM D6913/ D6913 M-17 (2025)',
                'codigo_servicio' => 'CYCSA-RT-FM-22 F',
                'codigo_hoja_campo' => 'CYCSA-RT-FM-09 (SPT)'
            ]
        ];

        $res = $this->controlador->detectarParametrosYNaturaleza($ensayos);

        $this->assertContains('Suelo', $res['naturalezas']);
        $this->assertNotContains('Concreto', $res['naturalezas']);
        $this->assertEquals('Suelo', $res['naturaleza_muestra_str']);
        $this->assertEquals(1, $res['flags']['req_granulometria']);
        $this->assertEquals(0, $res['flags']['req_resistencia_concreto']);
    }

    public function testDetectarParametrosYNaturalezaConcreto(): void {
        $ensayos = [
            [
                'descripcion_ensayo' => 'Resistencia a Compresión del Concreto – ASTM C39/C39M-23',
                'nombre_ensayo' => 'Resistencia a Compresión del Concreto',
                'procedimiento' => 'CYCSA-PE-07',
                'norma_astm' => 'ASTM C39/C39M-23',
                'codigo_servicio' => 'CYCSA-RT-FM-22 A',
                'codigo_hoja_campo' => ''
            ]
        ];

        $res = $this->controlador->detectarParametrosYNaturaleza($ensayos);

        $this->assertContains('Concreto', $res['naturalezas']);
        $this->assertEquals(1, $res['flags']['req_resistencia_concreto']);
        $this->assertEquals(0, $res['flags']['req_granulometria']);
    }

    public function testDetectarParametrosMultiples(): void {
        $ensayos = [
            [
                'descripcion_ensayo' => 'Contenido de Humedad en Suelos – ASTM D2216-19',
                'nombre_ensayo' => 'Contenido de Humedad en Suelos',
                'procedimiento' => 'CYCSA-PE-13',
                'norma_astm' => 'ASTM D2216-19',
                'codigo_servicio' => 'CYCSA-RT-FM-22 F'
            ],
            [
                'descripcion_ensayo' => 'Límites de Atterberg de Suelos – ASTM D4318-17e1',
                'nombre_ensayo' => 'Límites de Atterberg',
                'procedimiento' => 'CYCSA-PE-17',
                'norma_astm' => 'ASTM D4318-17e1',
                'codigo_servicio' => 'CYCSA-RT-FM-22 F'
            ],
            [
                'descripcion_ensayo' => 'Adoquines de Concreto – ASTM C936',
                'nombre_ensayo' => 'Resistencia de Adoquines',
                'procedimiento' => 'CYCSA-PE-01',
                'norma_astm' => 'ASTM C936',
                'codigo_servicio' => ''
            ]
        ];

        $res = $this->controlador->detectarParametrosYNaturaleza($ensayos);

        $this->assertContains('Suelo', $res['naturalezas']);
        $this->assertContains('Adoquines', $res['naturalezas']);
        $this->assertEquals(1, $res['flags']['req_humedad']);
        $this->assertEquals(1, $res['flags']['req_limites_atterberg']);
        $this->assertEquals(1, $res['flags']['req_resistencia_adoquin']);
        $this->assertEquals(0, $res['flags']['req_resistencia_concreto']);
    }
}
