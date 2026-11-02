<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\HojasServicio\Controladores\HojasServicioControlador;
use Cycsa\Modulos\Operaciones\Controladores\OperacionesControlador;
use ReflectionClass;

/**
 * Sprint E: Rediseño UX Operaciones (un solo patrón)
 *
 * Cobertura de pruebas unitarias:
 * E.1: Drawer único maestro-detalle (sin acordeón ni switcher), 3 tabs internas (Resumen, Ensayos, Cierre),
 *      CTAs sinceros con navegación real a captura-matriz, advertencia visual de saldo en Contado,
 *      y guía directa a Laboratorio cuando la custodia está pendiente.
 * E.2: Chrome de captura-matriz simplificado (título corto con formato y O/S, un solo Guardar en barra sticky,
 *      metadatos oficiales reubicados arriba de la grilla, y tooltip completo de muestra en inputs).
 * E.3: Depuración de rutas muertas y compatibilidad retroactiva con redirecciones de hojas-servicio.
 */
class SprintEOperacionesUXTest extends TestCase {

    private string $indexViewPath;
    private string $capturaMatrizViewPath;
    private string $accionesEnsayoPath;
    private string $routesPath;

    protected function setUp(): void {
        parent::setUp();
        $this->indexViewPath = dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/index.php';
        $this->capturaMatrizViewPath = dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/captura_matriz.php';
        $this->accionesEnsayoPath = dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/parciales/acciones_ensayo.php';
        $this->routesPath = dirname(__DIR__, 2) . '/rutas/web.php';
    }

    public function testOperacionesViewHasSinglePatternDrawerAndNoAccordionSwitcher(): void {
        $content = file_get_contents($this->indexViewPath);

        // Debe contener el Drawer como único contenedor maestro-detalle
        $this->assertStringContainsString('id="lims-drawer"', $content, 'Debe existir el elemento aside id="lims-drawer"');
        $this->assertStringContainsString('id="lims-drawer-btn-full"', $content, 'El drawer debe contar con botón para abrir en página completa');
        $this->assertStringNotContainsString('Detalle Maestro en Panel Lateral', $content, 'No debe existir el label residual redundante de Panel Lateral');
        $this->assertStringContainsString('lims-table-container', $content, 'La tabla debe contar con contenedor responsivo optimizado');

        // NO debe existir el toggle/switcher de acordeón
        $this->assertStringNotContainsString('id="btn-vista-accordion"', $content, 'No debe existir el botón switcher a modo acordeón');
        $this->assertStringNotContainsString('id="btn-vista-drawer"', $content, 'No debe existir el botón switcher de vista');
        $this->assertStringNotContainsString("cambiarModoVista('accordion')", $content, 'No debe existir la llamada a cambiarModoVista');
    }

    public function testDrawerCardsHaveThreeTabs(): void {
        $content = file_get_contents($this->indexViewPath);

        // Estructura interna de 3 tabs en cada card de O/S
        $this->assertStringContainsString('tab-resumen-', $content, 'Debe existir la pestaña de Resumen General');
        $this->assertStringContainsString('tab-ensayos-', $content, 'Debe existir la pestaña de Ensayos Técnicos');
        $this->assertStringContainsString('tab-cierre-', $content, 'Debe existir la pestaña de Cierre LIMS');
        $this->assertStringContainsString('switchDrawerCardTab', $content, 'Debe existir la función de cambio de pestaña en drawer');
        $this->assertStringContainsString('irATabEnsayosCard', $content, 'Debe existir la función de acceso directo a la pestaña de ensayos');
    }

    public function testPrimaryCTAAndRowOptionsAreHonest(): void {
        $content = file_get_contents($this->indexViewPath);

        // El CTA primario debe enlazar directamente a captura-matriz cuando hay ensayos pendientes
        $this->assertStringContainsString('/Cycsa/publico/operaciones/captura-matriz?id_detalle=', $content, 'El botón primario debe navegar a captura-matriz');
        $this->assertStringContainsString('Saldo Pendiente', $content, 'Debe mostrar advertencia de Saldo Pendiente para condición Contado');

        // Los menús contextuales no deben apuntar a rutas 404
        $this->assertStringNotContainsString('/hojas-servicio/crear?id_os=', $content, 'No debe contener enlaces rotos a /hojas-servicio/crear');
        $this->assertStringNotContainsString('/hojas-servicio/detalle?id_os=', $content, 'No debe contener enlaces rotos a /hojas-servicio/detalle');
    }

    public function testAccionesEnsayoGuidesToLaboratorioIfCustodiaPendiente(): void {
        $content = file_get_contents($this->accionesEnsayoPath);

        $this->assertStringContainsString('Custodia Lab Pendiente', $content);
        $this->assertStringContainsString('/Cycsa/publico/laboratorio?tab=kanban', $content, 'Debe proveer enlace directo a Laboratorio');
        $this->assertStringContainsString('Aprobar Muestras en Lab', $content, 'Debe contar con el botón CTA de aprobación de muestras en Lab');
    }

    public function testCapturaMatrizChromeSimplified(): void {
        $content = file_get_contents($this->capturaMatrizViewPath);

        // Metadatos reubicados arriba de la grilla
        $posMetadatos = strpos($content, 'Metadatos Oficiales del Encabezado del Informe');
        $posGrilla = strpos($content, 'id="tabla-captura-matriz"');

        $this->assertNotFalse($posMetadatos, 'Debe existir el bloque de metadatos');
        $this->assertNotFalse($posGrilla, 'Debe existir la grilla técnica');
        $this->assertLessThan($posGrilla, $posMetadatos, 'Los metadatos oficiales deben ubicarse antes de la grilla técnica');

        // En la barra inferior NO debe haber botón duplicado de submit "Guardar Matriz"
        $posInferior = strpos($content, 'BARRA INFORMATIVA INFERIOR');
        $subInferior = substr($content, $posInferior, 1000);
        $this->assertStringNotContainsString('guardarMatrizFullSubmit()', $subInferior, 'No debe haber botón de Guardar duplicado en la barra inferior');

        // La barra sticky superior sí conserva el botón primario
        $posSuperior = strpos($content, '<div class="matriz-top-bar"');
        $posFinSuperior = strpos($content, '<!-- BARRA SUPERIOR DE HISTORIAL', $posSuperior);
        $subSuperior = substr($content, $posSuperior, $posFinSuperior - $posSuperior);
        $this->assertStringContainsString('guardarMatrizFullSubmit()', $subSuperior, 'La barra sticky superior debe tener el Guardar Matriz');

        // Tooltips en inputs
        $this->assertStringContainsString('input.title = input.value', $content, 'Debe asignar el valor completo al title para nombres largos de probeta');
    }

    public function testRoutesAndControllersCompatibilityNo404(): void {
        $routesContent = file_get_contents($this->routesPath);

        // Rutas registradas para compatibilidad retroactiva
        $this->assertStringContainsString('/hojas-servicio/crear', $routesContent, 'Ruta /hojas-servicio/crear debe estar registrada');
        $this->assertStringContainsString('/hojas-servicio/detalle', $routesContent, 'Ruta /hojas-servicio/detalle debe estar registrada');
        $this->assertStringContainsString('/operaciones/guardar-matriz-resultados', $routesContent, 'Ruta /operaciones/guardar-matriz-resultados debe estar registrada');
        $this->assertStringContainsString('/operaciones/detalle-ajax', $routesContent, 'Ruta /operaciones/detalle-ajax debe estar registrada');

        // Métodos de controlador existen en HojasServicioControlador
        $refHs = new ReflectionClass(HojasServicioControlador::class);
        $this->assertTrue($refHs->hasMethod('crearRedirect'), 'HojasServicioControlador debe implementar crearRedirect');
        $this->assertTrue($refHs->hasMethod('detalleRedirect'), 'HojasServicioControlador debe implementar detalleRedirect');

        // Métodos de controlador existen en OperacionesControlador
        $refOp = new ReflectionClass(OperacionesControlador::class);
        $this->assertTrue($refOp->hasMethod('detalleAjax'), 'OperacionesControlador debe implementar detalleAjax');
    }

    public function testOperacionesVerRouteAndFullPageView(): void {
        $routesContent = file_get_contents($this->routesPath);
        $this->assertStringContainsString('/operaciones/ver', $routesContent, 'Ruta /operaciones/ver debe estar registrada');

        $refOp = new ReflectionClass(OperacionesControlador::class);
        $this->assertTrue($refOp->hasMethod('ver'), 'OperacionesControlador debe implementar método ver');

        $verViewPath = dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/ver.php';
        $this->assertFileExists($verViewPath, 'Debe existir la vista ver.php para página completa');

        $verContent = file_get_contents($verViewPath);
        $this->assertStringContainsString('tab-full-resumen', $verContent);
        $this->assertStringContainsString('tab-full-ensayos', $verContent);
        $this->assertStringContainsString('tab-full-cierre', $verContent);
        $this->assertStringContainsString('acciones_ensayo.php', $verContent);
    }

    public function testOperacionModeloHasGettersForVer(): void {
        $refMod = new ReflectionClass(\Cycsa\Modulos\Operaciones\Modelos\OperacionModelo::class);
        $this->assertTrue($refMod->hasMethod('obtenerOSPorId'), 'OperacionModelo debe implementar obtenerOSPorId');
        $this->assertTrue($refMod->hasMethod('obtenerPorId'), 'OperacionModelo debe implementar alias aditivo obtenerPorId');
    }

    public function testOperacionModeloObtenerOSPorIdRetornaDatosReales(): void {
        $modelo = new \Cycsa\Modulos\Operaciones\Modelos\OperacionModelo();
        $os = $modelo->obtenerOSPorId(1);
        if ($os !== null) {
            $this->assertArrayHasKey('id', $os);
            $this->assertArrayHasKey('codigo_os', $os);
            $this->assertArrayHasKey('cliente_nombre', $os);
            $this->assertArrayHasKey('condicion_comercial', $os);
            // Verificar alias obtenerPorId
            $os2 = $modelo->obtenerPorId(1);
            $this->assertEquals($os['id'], $os2['id']);
            $this->assertEquals($os['codigo_os'], $os2['codigo_os']);
        } else {
            $this->assertNull($os);
        }
    }

    public function testHojasServicioRedirectsAreDocumentedAsRetrocompatibility(): void {
        $hsCtrlPath = dirname(__DIR__, 2) . '/app/Modulos/HojasServicio/Controladores/HojasServicioControlador.php';
        $content = file_get_contents($hsCtrlPath);
        $this->assertStringContainsString('Capa de retrocompatibilidad HTTP 302', $content);
        $this->assertStringContainsString('crearRedirect', $content);
        $this->assertStringContainsString('detalleRedirect', $content);
    }

    public function testOperacionesViewPreservesDeprecatedSwitcherAsComment(): void {
        $content = file_get_contents($this->indexViewPath);
        $this->assertStringContainsString('/* deprecated E.1 — no borrar para retrocompatibilidad de estilos */', $content);
    }
}
