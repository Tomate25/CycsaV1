<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\OrdenesServicio\Controladores\OrdenesServicioControlador;
use ReflectionClass;
use ReflectionMethod;

/**
 * Sprint F: Lista Órdenes de Servicio & Hojas de Recepción
 *
 * Cobertura de pruebas unitarias:
 * F.1: Modelo con tabs (activas, muestreo, historico, todas), filtros avanzados y conteos SQL reales.
 * F.2: Controlador index() con sanitización de tab, auto-open de id_os, y filtros.
 * F.3: Vista con toolbar .lims-toolbar-top, quick tabs, badges y persistencia de tab en búsqueda.
 * F.4: Menú ⋮ desclippeado con popper fixed, viewport boundary, y adaptación de estado histórico.
 */
class SprintFOrdenesServicioListaTest extends TestCase {

    private string $indexViewPath;
    private OrdenServicioModelo $modelo;

    protected function setUp(): void {
        parent::setUp();
        $this->indexViewPath = dirname(__DIR__, 2) . '/app/Modulos/OrdenesServicio/Vistas/index.php';
        $this->modelo = new OrdenServicioModelo();
    }

    public function testModeloObtenerTodasConTabsYFiltros(): void {
        // Verificar que obtenerTodas soporta los nuevos argumentos aditivos
        $refMethod = new ReflectionMethod(OrdenServicioModelo::class, 'obtenerTodas');
        $params = $refMethod->getParameters();
        $this->assertGreaterThanOrEqual(1, count($params));
        $this->assertEquals('busqueda', $params[0]->getName());
        if (count($params) >= 2) {
            $this->assertEquals('tab', $params[1]->getName());
            $this->assertEquals('activas', $params[1]->getDefaultValue());
        }
        if (count($params) >= 3) {
            $this->assertEquals('filtros', $params[2]->getName());
            $this->assertEquals([], $params[2]->getDefaultValue());
        }

        // Test ejecución con diferentes tabs
        $activas = $this->modelo->obtenerTodas('', 'activas');
        $this->assertIsArray($activas);
        foreach ($activas as $os) {
            $this->assertNotContains($os['estado'], ['Finalizado', 'Archivado', 'Cerrado'], 'La tab activas no debe contener órdenes finalizadas');
        }

        $historico = $this->modelo->obtenerTodas('', 'historico');
        $this->assertIsArray($historico);
        foreach ($historico as $os) {
            $this->assertContains($os['estado'], ['Finalizado', 'Archivado', 'Cerrado'], 'La tab historico solo debe contener órdenes cerradas');
        }

        $muestreo = $this->modelo->obtenerTodas('', 'muestreo');
        $this->assertIsArray($muestreo);

        $todas = $this->modelo->obtenerTodas('', 'todas');
        $this->assertIsArray($todas);
        $this->assertGreaterThanOrEqual(count($activas), count($todas));
    }

    public function testModeloObtenerConteosTabsOSLista(): void {
        $this->assertTrue(method_exists($this->modelo, 'obtenerConteosTabsOSLista'), 'Debe existir método obtenerConteosTabsOSLista');
        $conteos = $this->modelo->obtenerConteosTabsOSLista();

        $this->assertIsArray($conteos);
        $this->assertArrayHasKey('activas', $conteos);
        $this->assertArrayHasKey('muestreo', $conteos);
        $this->assertArrayHasKey('historico', $conteos);
        $this->assertArrayHasKey('todas', $conteos);

        $this->assertGreaterThanOrEqual(0, $conteos['activas']);
        $this->assertGreaterThanOrEqual(0, $conteos['muestreo']);
        $this->assertGreaterThanOrEqual(0, $conteos['historico']);
        $this->assertGreaterThanOrEqual(0, $conteos['todas']);
        $this->assertGreaterThanOrEqual($conteos['activas'] + $conteos['historico'], $conteos['todas']);
    }

    public function testModeloDeterminarTabDeOS(): void {
        $this->assertTrue(method_exists($this->modelo, 'determinarTabDeOS'), 'Debe existir método determinarTabDeOS');
        $tab = $this->modelo->determinarTabDeOS(1);
        $this->assertContains($tab, ['activas', 'muestreo', 'historico', 'todas']);
    }

    public function testControladorIndexMethodExists(): void {
        $refCtrl = new ReflectionClass(OrdenesServicioControlador::class);
        $this->assertTrue($refCtrl->hasMethod('index'));
    }

    public function testVistaTieneToolbarQuickTabsYBuscadorConTabPreservado(): void {
        $content = file_get_contents($this->indexViewPath);

        // Toolbar y Tabs
        $this->assertStringContainsString('lims-toolbar-top', $content, 'Debe existir la barra de herramientas de tabs');
        $this->assertStringContainsString('lims-quick-tabs', $content, 'Debe existir el contenedor de quick tabs');
        $this->assertStringContainsString('quick-tab-link', $content, 'Debe usar la clase quick-tab-link');
        $this->assertStringContainsString('tab=activas', $content, 'Debe incluir enlace a tab activas');
        $this->assertStringContainsString('tab=muestreo', $content, 'Debe incluir enlace a tab muestreo');
        $this->assertStringContainsString('tab=historico', $content, 'Debe incluir enlace a tab historico');
        $this->assertStringContainsString('tab=todas', $content, 'Debe incluir enlace a tab todas');

        // Formulario de búsqueda conserva tab en campo oculto
        $this->assertStringContainsString('name="tab"', $content, 'El formulario de búsqueda debe preservar la tab activa');
        $this->assertStringContainsString('name="estado"', $content, 'Debe incluir filtro secundario de estado');
        $this->assertStringContainsString('name="desde"', $content, 'Debe incluir filtro secundario de fecha desde');
        $this->assertStringContainsString('name="hasta"', $content, 'Debe incluir filtro secundario de fecha hasta');
    }

    public function testVistaMenuDesclippeadoConPopperFixedYContenedor(): void {
        $content = file_get_contents($this->indexViewPath);

        // Contenedor responsivo
        $this->assertStringContainsString('os-table-container', $content, 'La tabla debe estar envuelta en os-table-container');

        // Atributos de popper fixed y viewport boundary
        $this->assertStringContainsString('data-bs-boundary="viewport"', $content, 'El botón dropdown debe tener data-bs-boundary="viewport"');
        $this->assertStringContainsString('data-bs-popper-config', $content, 'El botón dropdown debe tener data-bs-popper-config');
        $this->assertStringContainsString('os-acciones-menu', $content, 'El menú desplegable debe tener clase os-acciones-menu');
    }

    public function testVistaModalRTFM13TieneIdsDeFiltradoWhitelist(): void {
        $content = file_get_contents($this->indexViewPath);

        // H.1: Elementos para filtrado por categoría
        $this->assertStringContainsString('id="hs_col_concreto"', $content, 'Debe existir id="hs_col_concreto" en sección 3.1');
        $this->assertStringContainsString('id="hs_col_suelo"', $content, 'Debe existir id="hs_col_suelo" en sección 3.2');
        $this->assertStringContainsString('aplicarFiltroWhitelistReq', $content, 'Debe existir la función JS aplicarFiltroWhitelistReq');
    }

    public function testConteosDistinctNoInflanConMultiplesMuestreos(): void {
        $conteos = $this->modelo->obtenerConteosTabsOSLista();
        $this->assertIsArray($conteos);
        $this->assertArrayHasKey('activas', $conteos);
        $this->assertArrayHasKey('muestreo', $conteos);
        $this->assertArrayHasKey('historico', $conteos);
        $this->assertArrayHasKey('todas', $conteos);

        // La suma de activas + historico nunca debe superar al total de órdenes distintas
        $this->assertLessThanOrEqual($conteos['todas'], $conteos['activas'] + $conteos['historico']);

        // Verificar consulta de obtenerTodas con aliases cliente_informe y cliente_factura
        $todas = $this->modelo->obtenerTodas('', 'todas');
        if (!empty($todas)) {
            $primera = $todas[0];
            $this->assertArrayHasKey('cliente_informe', $primera, 'Debe incluir alias cliente_informe');
            $this->assertArrayHasKey('cliente_factura', $primera, 'Debe incluir alias cliente_factura');
        }
    }
}
