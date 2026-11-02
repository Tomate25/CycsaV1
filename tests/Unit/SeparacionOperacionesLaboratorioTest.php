<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

/**
 * Prueba de separación estricta de módulos:
 * Operaciones LIMS (/operaciones) y Laboratorio ISO 17025 (/laboratorio).
 *
 * Garantiza que:
 * 1. Acceder a /operaciones NO redirige forzosamente a /laboratorio para ningún rol (incluyendo rol 6).
 * 2. Acceder a /operaciones/ver NO redirige a /laboratorio.
 * 3. Acceder a /operaciones/detalle-lote NO redirige a /laboratorio.
 * 4. Intentar capturar matriz sin custodia de laboratorio mantiene al usuario dentro de Operaciones.
 * 5. La interfaz JS de Operaciones (index.php) no expulsa al usuario hacia /laboratorio al pulsar acciones.
 */
class SeparacionOperacionesLaboratorioTest extends TestCase {

    private string $controladorPath;
    private string $indexViewPath;
    private string $accionesEnsayoPath;

    protected function setUp(): void {
        parent::setUp();
        $this->controladorPath = dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Controladores/OperacionesControlador.php';
        $this->indexViewPath = dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/index.php';
        $this->accionesEnsayoPath = dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/parciales/acciones_ensayo.php';
    }

    public function testOperacionesControladorNoContieneRedireccionesForzosasPorRol6(): void {
        $codigo = file_get_contents($this->controladorPath);

        // No debe existir el secuestro de ruta if ($rol == 6) { redirigir('/laboratorio'); }
        $this->assertDoesNotMatchString(
            '/if\s*\(\(\$_SESSION\[[\'"]usuario_rol[\'"]\]\s*\?\?\s*0\)\s*==\s*6\)\s*\{\s*\$respuesta->redirigir\([\'"]\/Cycsa\/publico\/laboratorio/',
            $codigo,
            'OperacionesControlador NO debe redirigir forzosamente a /laboratorio a usuarios con rol 6'
        );
    }

    public function testOperacionesIndexPermiteAccesoDirectoASuPropioModulo(): void {
        $codigo = file_get_contents($this->controladorPath);

        // Extraer método index
        preg_match('/public function index\(.*?\): void \{(.*?)(?=public function|\Z)/s', $codigo, $matches);
        $this->assertNotEmpty($matches, 'El método index debe existir en OperacionesControlador');

        $metodoIndex = $matches[1];
        $this->assertStringNotContainsString(
            "redirigir('/Cycsa/publico/laboratorio')",
            $metodoIndex,
            'index() en OperacionesControlador debe renderizar operaciones/vistas/index y NO redirigir a /laboratorio'
        );
    }

    public function testOperacionesVerPermiteVisualizarOrdenSinRedirigirALaboratorio(): void {
        $codigo = file_get_contents($this->controladorPath);

        preg_match('/public function ver\(.*?\): void \{(.*?)(?=public function|\Z)/s', $codigo, $matches);
        $this->assertNotEmpty($matches, 'El método ver debe existir en OperacionesControlador');

        $metodoVer = $matches[1];
        $this->assertStringNotContainsString(
            "redirigir('/Cycsa/publico/laboratorio')",
            $metodoVer,
            'ver() en OperacionesControlador debe mostrar la O/S en Operaciones y NO expulsar al usuario a /laboratorio'
        );
    }

    public function testCapturaMatrizSinCustodiaRedirigeAOperacionesVer(): void {
        $codigo = file_get_contents($this->controladorPath);

        preg_match('/public function capturaMatrizProducto\(.*?\): void \{(.*?)(?=public function|\Z)/s', $codigo, $matches);
        $this->assertNotEmpty($matches, 'El método capturaMatrizProducto debe existir');

        $metodoCaptura = $matches[1];
        $this->assertStringNotContainsString(
            "redirigir('/Cycsa/publico/laboratorio?tab=kanban')",
            $metodoCaptura,
            'capturaMatrizProducto NO debe enviar al usuario a /laboratorio?tab=kanban'
        );
        $this->assertStringContainsString(
            "redirigir('/Cycsa/publico/operaciones/ver?id_os='",
            $metodoCaptura,
            'capturaMatrizProducto debe mantener al usuario en Operaciones'
        );
    }

    public function testJsIntentarRellenarMatrizPermaneceEnOperaciones(): void {
        $vista = file_get_contents($this->indexViewPath);

        preg_match('/function intentarRellenarMatrizProducto\(.*?\)\s*\{(.*?)\}/s', $vista, $matches);
        $this->assertNotEmpty($matches, 'La función JS intentarRellenarMatrizProducto debe existir');

        $cuerpoJs = $matches[1];
        $this->assertStringNotContainsString(
            "window.location.href = '/Cycsa/publico/laboratorio?tab=kanban'",
            $cuerpoJs,
            'intentarRellenarMatrizProducto NO debe forzar navegación hacia /laboratorio'
        );
    }

    public function testEnlacesAModuloLaboratorioTienenTargetBlank(): void {
        $vista = file_get_contents($this->indexViewPath);
        $acciones = file_get_contents($this->accionesEnsayoPath);

        // Si existen enlaces directos de asistencia al laboratorio, deben abrirse en pestaña separada
        // para no sacar al usuario de su flujo actual en Operaciones
        if (strpos($vista, 'href="/Cycsa/publico/laboratorio?tab=kanban"') !== false) {
            $this->assertStringContainsString(
                'href="/Cycsa/publico/laboratorio?tab=kanban" target="_blank"',
                $vista,
                'El enlace al módulo de Laboratorio en Operaciones debe abrirse con target="_blank"'
            );
        }

        if (strpos($acciones, 'href="/Cycsa/publico/laboratorio?tab=kanban"') !== false) {
            $this->assertMatchesRegularExpression(
                '/href="\/Cycsa\/publico\/laboratorio\?tab=kanban"[\s\r\n]+target="_blank"/',
                $acciones,
                'El enlace de aprobación en Laboratorio desde Operaciones debe abrirse con target="_blank"'
            );
        }
    }

    private function assertDoesNotMatchString(string $pattern, string $string, string $message = ''): void {
        $this->assertFalse((bool)preg_match($pattern, $string), $message);
    }
}
