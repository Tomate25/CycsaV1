<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OperacionesVerMatricesSinAperturaDirectaTest extends TestCase {

    private string $indexViewPath;
    private string $accionesEnsayoPath;

    protected function setUp(): void {
        parent::setUp();
        $this->indexViewPath = dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/index.php';
        $this->accionesEnsayoPath = dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/parciales/acciones_ensayo.php';
    }

    public function testIndexViewNoContieneTextoRellenarMatrizEnBotonPrimario(): void {
        $content = file_get_contents($this->indexViewPath);

        // No debe decir 'Rellenar Matriz' en los botones
        $this->assertStringNotContainsString("'texto' => 'Rellenar Matriz'", $content, "No debe existir 'Rellenar Matriz' en el botón primario de operaciones.");
        
        // Debe tener botones de 'Ver Matrices' con contador
        $this->assertStringContainsString("abrirMatricesOS", $content, "Debe existir la llamada a abrirMatricesOS.");
        $this->assertStringContainsString("'texto' => 'Ver Matrices ('", $content, "El texto del botón primario debe ser 'Ver Matrices (X)'.");
    }

    public function testAccionesEnsayoNoDiceRellenarMatrizSinoCapturarMatriz(): void {
        $content = file_get_contents($this->accionesEnsayoPath);

        $this->assertStringNotContainsString("Rellenar Matriz", $content, "acciones_ensayo.php no debe decir 'Rellenar Matriz'.");
        $this->assertStringContainsString("Capturar Matriz", $content, "acciones_ensayo.php debe usar 'Capturar Matriz'.");
    }

    public function testFuncionJsAbrirMatricesOSAbrePestanaEnsayos(): void {
        $content = file_get_contents($this->indexViewPath);

        $this->assertMatchesRegularExpression(
            '/function\s+abrirMatricesOS\(idOS\)\s*\{\s*abrirLimsDrawer\(idOS,\s*[\'"]tab-ensayos-[\'"]\s*\+\s*idOS\);\s*\}/',
            $content,
            "abrirMatricesOS debe abrir directamente el drawer en la pestaña de ensayos."
        );
    }

    public function testTabEnsayosMuestraResumenDeMatricesPendientes(): void {
        $content = file_get_contents($this->indexViewPath);

        $this->assertStringContainsString("matriz técnica' : 'matrices técnicas' ?> en esta orden:", $content);
        $this->assertStringContainsString("pendiente", $content);
        $this->assertStringContainsString("completada", $content);
    }
}
