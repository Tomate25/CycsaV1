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

    public function testTodasLasCorreccionesDudosasSonConmutablesEnMotorYModelo(): void {
        $motor = new \Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio();
        $sembrador = new SembradorPlantillasHcServicio();
        $sembrador->sembrarCorreccionesFormatos($motor->listarCodigosFormatos());

        $dudosas = $this->modelo->obtenerCorrecciones(null, true);
        $this->assertGreaterThanOrEqual(20, count($dudosas), 'Deben estar registradas todas las correcciones dudosas');

        // 1. Verificar que una corrección dudosa se puede desactivar y reactivar en BD
        $primeraDudosa = $dudosas[0];
        $fmtDudosa = (string)$primeraDudosa['codigo_formato'];
        $cidDudosa = (string)$primeraDudosa['correccion_id'];
        $this->assertTrue($this->modelo->actualizarEstadoCorreccion($fmtDudosa, $cidDudosa, false, false, 1));
        $this->assertFalse($this->modelo->usarFormulaCorregida($fmtDudosa, $cidDudosa));
        $this->assertTrue($this->modelo->actualizarEstadoCorreccion($fmtDudosa, $cidDudosa, true, false, 1));
        $this->assertTrue($this->modelo->usarFormulaCorregida($fmtDudosa, $cidDudosa));

        // 2. Verificar que una corrección NO dudosa (es_dudosa = 0) NO se puede desactivar
        $todas = $this->modelo->obtenerCorrecciones(null, false);
        $noDudosas = array_values(array_filter($todas, fn($c) => (int)($c['es_dudosa'] ?? 0) === 0));
        if (!empty($noDudosas)) {
            $nd = $noDudosas[0];
            $this->assertFalse($this->modelo->actualizarEstadoCorreccion((string)$nd['codigo_formato'], (string)$nd['correccion_id'], false, false, 1));
        }

        // 3. Verificar que las correcciones dudosas cambian efectivamente el cálculo en MotorHojasCalculoServicio
        // FM36-01 (Bloques: factores 0.155 y 14.2 vs 0.15500031 y 14.2233)
        $spec36 = $motor->cargarEspecificacion('RT-FM-36');
        $in36 = $spec36['caso_prueba'][0]['entradas'];
        $ev36True = $motor->evaluarEspecimen('RT-FM-36', $in36, ['correcciones_override' => ['FM36-01' => true]]);
        $ev36False = $motor->evaluarEspecimen('RT-FM-36', $in36, ['correcciones_override' => ['FM36-01' => false]]);
        $this->assertNotEquals($ev36True['valores']['resistencia_kgcm2'], $ev36False['valores']['resistencia_kgcm2']);

        // FM57-02 (Mortero: kg/cm² con factor 2.202*6.45 vs 14.2233)
        $spec57 = $motor->cargarEspecificacion('RT-FM-57');
        $in57 = $spec57['caso_prueba'][0]['entradas'];
        $ev57True = $motor->evaluarEspecimen('RT-FM-57', $in57, ['correcciones_override' => ['FM57-02' => true]]);
        $ev57False = $motor->evaluarEspecimen('RT-FM-57', $in57, ['correcciones_override' => ['FM57-02' => false]]);
        $this->assertNotEquals($ev57True['valores']['resistencia_kgcm2'], $ev57False['valores']['resistencia_kgcm2']);

        // FM63-01 (Adoquines área neta sin /2 vs con /2) y FM63-02 (promedio de 3 lecturas vs solo carga_1) y FM63-03 (absorción)
        $spec63 = $motor->cargarEspecificacion('RT-FM-63');
        $in63 = array_merge($spec63['caso_prueba'][0]['entradas'], [
            'carga_1' => 100000.0,
            'carga_2' => 120000.0,
            'carga_3' => 110000.0,
        ]);
        $ev63True = $motor->evaluarEspecimen('RT-FM-63', $in63, ['correcciones_override' => ['FM63-01' => true, 'FM63-02' => true, 'FM63-03' => true]]);
        $ev63False = $motor->evaluarEspecimen('RT-FM-63', $in63, ['correcciones_override' => ['FM63-01' => false, 'FM63-02' => false, 'FM63-03' => false]]);
        $this->assertNotEquals($ev63True['valores']['area_neta_cm2'], $ev63False['valores']['area_neta_cm2']);
        $this->assertNotEquals($ev63True['valores']['resistencia_psi'], $ev63False['valores']['resistencia_psi']);

        // FM64-01 (Elongación acero con longitud entre marcas vs elongacion_excel) y FM64-04 (validación fy < fu)
        $spec64 = $motor->cargarEspecificacion('RT-FM-64');
        $in64 = array_merge($spec64['caso_prueba'][0]['entradas'], ['longitud_final_marcas_cm' => 23.6]);
        $ev64True = $motor->evaluarEspecimen('RT-FM-64', $in64, ['correcciones_override' => ['FM64-01' => true, 'FM64-04' => true]]);
        $ev64False = $motor->evaluarEspecimen('RT-FM-64', $in64, ['correcciones_override' => ['FM64-01' => false, 'FM64-04' => false]]);
        $this->assertEqualsWithDelta(18.0, (float)$ev64True['valores']['elongacion'], 0.01);
        $this->assertEqualsWithDelta(34.0, (float)$ev64False['valores']['elongacion'], 0.01);
        $this->assertArrayHasKey('fluencia_menor_uts_valida', $ev64True['valores']);

        // FM62-01, FM62-02, FM62-03 (Infiltración)
        $spec62 = $motor->cargarEspecificacion('RT-FM-62');
        $in62 = array_merge($spec62['caso_prueba'][0]['entradas'], ['descenso_25min' => 10.0, 'descenso_30min' => 13.0]);
        $ev62True = $motor->evaluarEspecimen('RT-FM-62', $in62, ['correcciones_override' => ['FM62-01' => true, 'FM62-02' => true, 'FM62-03' => true]]);
        $ev62False = $motor->evaluarEspecimen('RT-FM-62', $in62, ['correcciones_override' => ['FM62-01' => false, 'FM62-02' => false, 'FM62-03' => false]]);
        $this->assertNotEquals($ev62True['valores']['tasa_lm2_dia'], $ev62False['valores']['tasa_lm2_dia']);
        $this->assertNotEquals($ev62True['valores']['tasa_galft2_dia'], $ev62False['valores']['tasa_galft2_dia']);
        $this->assertTrue($ev62True['valores']['lecturas_monotonas_validas']);

        // FM56-01 y FM56-03 (Picnómetros temperatura por réplica vs encabezado y Gs de suelo)
        $in56 = ['masa_vacio' => 165.0, 'masa_lleno' => 663.0, 'temperatura' => 23.0, 'temperatura_encabezado' => 20.0, 'masa_suelo_seco' => 50.0, 'masa_picno_agua_suelo' => 694.5];
        $ev56True = $motor->evaluarEspecimen('RT-FM-56', $in56, ['correcciones_override' => ['FM56-01' => true, 'FM56-03' => true]]);
        $ev56False = $motor->evaluarEspecimen('RT-FM-56', $in56, ['correcciones_override' => ['FM56-01' => false, 'FM56-03' => false]]);
        $this->assertEqualsWithDelta(23.0, (float)$ev56True['valores']['temperatura'], 0.01);
        $this->assertEqualsWithDelta(20.0, (float)$ev56False['valores']['temperatura'], 0.01);
        $this->assertArrayHasKey('gs_suelo_d854', $ev56True['valores']);
        $this->assertArrayNotHasKey('gs_suelo_d854', $ev56False['valores']);

        // FM59-02 (Martillo suizo descarte de lecturas que difieran > 6 unidades del promedio inicial según ASTM C805)
        $spec59 = $motor->cargarEspecificacion('RT-FM-59');
        $in59 = array_merge($spec59['caso_prueba'][0]['entradas'], ['rebote_1' => 10.0]); // outlier > 6 unidades del promedio (~37)
        $ev59True = $motor->evaluarEspecimen('RT-FM-59', $in59, ['correcciones_override' => ['FM59-02' => true]]);
        $ev59False = $motor->evaluarEspecimen('RT-FM-59', $in59, ['correcciones_override' => ['FM59-02' => false]]);
        $this->assertSame(1, $ev59True['valores']['rebotes_descartados_c805']);
        $this->assertGreaterThan($ev59False['valores']['rebote_promedio'], $ev59True['valores']['rebote_promedio']);
    }
}
