<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Modulos\Productos\Modelos\ProductoModelo;

class ProductoSearchTest extends TestCase {
    private ?ProductoModelo $modelo = null;

    protected function setUp(): void {
        parent::setUp();
        try {
            $this->modelo = new ProductoModelo();
        } catch (\Throwable $e) {
            $this->markTestSkipped('Base de datos no disponible para pruebas de busqueda: ' . $e->getMessage());
        }
    }

    public function testBusquedaPorProcedimientoCompactoEncuentraItem(): void {
        $resultados = $this->modelo->obtenerTodos('pe25');
        $this->assertNotEmpty($resultados, "La búsqueda compacta 'pe25' debe arrojar al menos un resultado.");

        $encontrado = false;
        foreach ($resultados as $item) {
            if (strpos($item['procedimiento_muestreo'] ?? '', 'CYCSA-PE-25') !== false) {
                $encontrado = true;
                break;
            }
        }
        $this->assertTrue($encontrado, "El procedimiento CYCSA-PE-25 debe encontrarse al buscar 'pe25'.");
    }

    public function testBusquedaPorProcedimientoSeparadoEncuentraItem(): void {
        $resultados = $this->modelo->obtenerTodos('pe 25');
        $this->assertNotEmpty($resultados, "La búsqueda con espacio 'pe 25' debe arrojar resultados.");

        $encontrado = false;
        foreach ($resultados as $item) {
            if (strpos($item['procedimiento_muestreo'] ?? '', 'CYCSA-PE-25') !== false) {
                $encontrado = true;
                break;
            }
        }
        $this->assertTrue($encontrado, "El procedimiento CYCSA-PE-25 debe encontrarse al buscar 'pe 25'.");
    }

    public function testBusquedaInsensibleAAcentos(): void {
        $resultados = $this->modelo->obtenerTodos('densimetro');
        $this->assertNotEmpty($resultados, "La búsqueda sin tilde 'densimetro' debe encontrar 'Densímetro'.");

        $encontrado = false;
        foreach ($resultados as $item) {
            $texto = ($item['nombre_comercial'] ?? '') . ' ' . ($item['ensayo_servicio'] ?? '');
            if (stripos($texto, 'densímetro') !== false || stripos($texto, 'densimetro') !== false) {
                $encontrado = true;
                break;
            }
        }
        $this->assertTrue($encontrado, "Debe encontrarse el ensayo con 'Densímetro' al buscar 'densimetro'.");
    }

    public function testBusquedaMultiTokenNormaAstm(): void {
        $resultados = $this->modelo->obtenerTodos('astm 6938');
        $this->assertNotEmpty($resultados, "La búsqueda multi-token 'astm 6938' debe encontrar la norma ASTM D6938.");

        $encontrado = false;
        foreach ($resultados as $item) {
            if (stripos($item['norma_astm'] ?? '', '6938') !== false) {
                $encontrado = true;
                break;
            }
        }
        $this->assertTrue($encontrado, "Debe encontrarse la norma ASTM D6938 al buscar 'astm 6938'.");
    }
}
