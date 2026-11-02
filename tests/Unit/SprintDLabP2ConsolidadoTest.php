<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Operaciones\Servicios\InformeLaboratorioServicio;
use Cycsa\Modulos\Operaciones\Modelos\RoturasModelo;
use Cycsa\Nucleo\Conexion;
use PDO;

/**
 * Sprint D / Epic D: AGY-LAB-P2 — Informes Unificados, Multi-Muestra y Versiones
 *
 * Cobertura de pruebas unitarias:
 * 1. Fachada InformeLaboratorioServicio unifica informes_control e informes_rotura.
 * 2. Consolidado multi-muestra calcula métricas agregadas (promedio, desviación estándar, CV).
 * 3. RoturasModelo::generarInformeConsolidado asigna versión dinámica N+1 (no hardcode 1).
 * 4. Historial de versiones expone trazabilidad con sha256, motivo y sustitución.
 */
class SprintDLabP2ConsolidadoTest extends TestCase {

    private PDO $db;
    private InformeLaboratorioServicio $servicio;
    private RoturasModelo $roturasModelo;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->servicio = new InformeLaboratorioServicio($this->db);
        $this->roturasModelo = new RoturasModelo();
    }

    public function testServicioUnificaInformesDeControlYRotura(): void {
        $this->assertTrue(method_exists($this->servicio, 'obtenerInformesUnificadosOS'));

        // Consulta sobre una O/S existente o ID seguro
        $informes = $this->servicio->obtenerInformesUnificadosOS(1);
        $this->assertIsArray($informes);

        // Si hay informes, verificar esquema normalizado
        foreach ($informes as $inf) {
            $this->assertArrayHasKey('id', $inf);
            $this->assertArrayHasKey('fuente', $inf);
            $this->assertContains($inf['fuente'], ['control', 'rotura']);
            $this->assertArrayHasKey('tipo', $inf);
            $this->assertArrayHasKey('codigo_informe', $inf);
            $this->assertArrayHasKey('version', $inf);
            $this->assertArrayHasKey('estado', $inf);
        }
    }

    public function testConsolidadoMultiMuestraCalculaEstadisticasAgregadas(): void {
        $this->assertTrue(method_exists($this->servicio, 'obtenerConsolidadoMultiMuestra'));

        $res = $this->servicio->obtenerConsolidadoMultiMuestra(1);
        $this->assertIsArray($res);
        $this->assertTrue($res['exito']);
        $this->assertArrayHasKey('total_muestras', $res);
        $this->assertArrayHasKey('total_ensayos', $res);
        $this->assertArrayHasKey('promedio_esfuerzo_mpa', $res);
        $this->assertArrayHasKey('desviacion_estandar_mpa', $res);
        $this->assertArrayHasKey('coeficiente_variacion', $res);
        $this->assertArrayHasKey('version_consolidado', $res);
    }

    public function testConsolidadoVersionDinamicaNoFijaVersionUno(): void {
        $reflection = new \ReflectionMethod(RoturasModelo::class, 'generarInformeConsolidado');
        $this->assertTrue($reflection->isPublic());

        // Verificar en el código fuente de RoturasModelo que no exista 'version = 1' fijo en el insert
        $source = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Modelos/RoturasModelo.php');

        $this->assertStringNotContainsString(
            "VALUES ('consolidado', :muestra_id, NULL, :numero, 1, 'aprobado'",
            $source,
            'RoturasModelo no debe hardcodear version = 1 en el INSERT del informe consolidado'
        );

        $this->assertStringContainsString(
            "SELECT COALESCE(MAX(version), 0) + 1",
            $source,
            'RoturasModelo debe consultar la versión N+1 dinámicamente'
        );
    }

    public function testHistorialVersionesRetornaTrazabilidad(): void {
        $this->assertTrue(method_exists($this->servicio, 'obtenerHistorialVersiones'));

        $historial = $this->servicio->obtenerHistorialVersiones(1, 'rotura');
        $this->assertIsArray($historial);

        foreach ($historial as $item) {
            $this->assertArrayHasKey('version', $item);
            $this->assertArrayHasKey('motivo', $item);
            $this->assertArrayHasKey('sha256', $item);
            $this->assertArrayHasKey('usuario', $item);
        }
    }
}
