<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Configuracion\Modelos\EquipoCalibracionModelo;
use Cycsa\Modulos\Operaciones\Servicios\AgregadorEnsayosServicio;
use Cycsa\Modulos\Operaciones\Servicios\SembradorPlantillasHcServicio;

class EquiposCalibracionTest extends TestCase {

    private EquipoCalibracionModelo $modelo;
    private AgregadorEnsayosServicio $agregador;

    protected function setUp(): void {
        parent::setUp();
        $this->modelo = new EquipoCalibracionModelo();
        $this->agregador = new AgregadorEnsayosServicio();

        // Asegurar sembrado idempotente
        $sembrador = new SembradorPlantillasHcServicio();
        $sembrador->sembrarEquiposCalibracion();
        $sembrador->sembrarCorreccionesFormatos(['RT-FM-61']);
    }

    public function testCatalogoEquiposCalibracionSembradoCompleto(): void {
        $equipos = $this->modelo->obtenerEquipos();
        $this->assertGreaterThanOrEqual(12, count($equipos), 'Deben existir los 12 equipos de equipos_calibracion.json');

        $prensa = $this->modelo->obtenerEquipoPorCodigo('PRENSA-01');
        $this->assertNotNull($prensa);
        $this->assertEquals('maquina_fuerza', $prensa['categoria']);

        // Verificar resolución de constantes de PRENSA-01 por ref_equipo
        $this->assertEqualsWithDelta(-4.6765, (float)$this->modelo->obtenerValorPorRefEquipo('PRENSA-01.prensa_a_e14'), 1e-6);
        $this->assertEqualsWithDelta(2.5071, (float)$this->modelo->obtenerValorPorRefEquipo('PRENSA-01.prensa_b_e8'), 1e-6);
        $this->assertEqualsWithDelta(0.99583, (float)$this->modelo->obtenerValorPorRefEquipo('PRENSA-01.prensa_c'), 1e-6);
        $this->assertEqualsWithDelta(3.7217, (float)$this->modelo->obtenerValorPorRefEquipo('PRENSA-01.prensa_d'), 1e-6);
    }

    public function testFactorCorreccionLdAstmC39(): void {
        // Sin corrección para L/D > 1.75
        $this->assertEqualsWithDelta(1.0, $this->modelo->calcularFactorLdAstmC39(2.00), 1e-4);
        $this->assertEqualsWithDelta(1.0, $this->modelo->calcularFactorLdAstmC39(1.80), 1e-4);

        // Valores exactos tabulados ASTM C39
        $this->assertEqualsWithDelta(0.98, $this->modelo->calcularFactorLdAstmC39(1.75), 1e-4);
        $this->assertEqualsWithDelta(0.96, $this->modelo->calcularFactorLdAstmC39(1.50), 1e-4);
        $this->assertEqualsWithDelta(0.93, $this->modelo->calcularFactorLdAstmC39(1.25), 1e-4);
        $this->assertEqualsWithDelta(0.87, $this->modelo->calcularFactorLdAstmC39(1.00), 1e-4);

        // Interpolación lineal intermedia (ej. L/D = 1.625 -> punto medio entre 1.50 y 1.75 -> 0.97)
        $this->assertEqualsWithDelta(0.97, $this->modelo->calcularFactorLdAstmC39(1.625), 1e-4);
    }

    public function testPicnometrosCalibradosRtFm56(): void {
        $picnos = $this->modelo->obtenerPicnometros();
        $this->assertGreaterThanOrEqual(9, count($picnos), 'Deben existir los 9 picnómetros PICNO-02 a PICNO-10');

        $codigos = array_column($picnos, 'identificacion');
        $this->assertContains('PICNO-02', $codigos);
        $this->assertContains('PICNO-10', $codigos);
    }

    public function testServicioAgregadorEnsayosFuncionesCompletas(): void {
        $valores = [10.0, 20.0, 30.0];
        $this->assertEqualsWithDelta(20.0, $this->agregador->promedio($valores), 1e-6);
        $this->assertEqualsWithDelta(60.0, $this->agregador->suma($valores), 1e-6);
        $this->assertEqualsWithDelta(30.0, $this->agregador->max($valores), 1e-6);
        $this->assertEqualsWithDelta(10.0, $this->agregador->min($valores), 1e-6);
        $this->assertEqualsWithDelta(10.0, $this->agregador->desviacionEstandar($valores), 1e-6);
        $this->assertEquals([10.0, 30.0, 60.0], $this->agregador->acumulado($valores));

        // Regresión cuadrática sobre parábola y = -1*(x - 12)^2 + 2100 = -x^2 + 24x + 1956
        $puntosProctor = [
            ['humedad_pct' => 10.0, 'densidad_seca_kg_m3' => 2096.0],
            ['humedad_pct' => 12.0, 'densidad_seca_kg_m3' => 2100.0],
            ['humedad_pct' => 14.0, 'densidad_seca_kg_m3' => 2096.0],
        ];
        $reg = $this->agregador->regresionCuadratica($puntosProctor);
        $this->assertNotNull($reg);
        $this->assertEqualsWithDelta(12.0, $reg['x_vertice'], 1e-4);
        $this->assertEqualsWithDelta(2100.0, $reg['y_vertice'], 1e-4);

        // Clasificación SUCS
        $sucsCl = $this->agregador->clasificarSucs(['pct_pasa_200' => 65.0, 'll' => 38.0, 'ip' => 18.0]);
        $this->assertEquals('CL', $sucsCl['simbolo']);

        $sucsSm = $this->agregador->clasificarSucs(['pct_pasa_200' => 22.0, 'pct_grava' => 15.0, 'pct_arena' => 63.0, 'll' => 28.0, 'ip' => 4.0]);
        $this->assertEquals('SM', $sucsSm['simbolo']);
    }
}
