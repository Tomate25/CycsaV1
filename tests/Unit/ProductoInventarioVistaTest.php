<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class ProductoInventarioVistaTest extends TestCase {
    public function testInventarioUsaUnaSolaTablaConFilasExpandibles(): void {
        $ruta = dirname(__DIR__, 2) . '/app/Modulos/Productos/Vistas/index.php';
        $vista = file_get_contents($ruta);

        $this->assertNotFalse($vista);
        foreach ([
            'No', 'Código', 'Nombre Comercial', 'Matriz', 'Norma ASTM', 'Estatus',
            'Condiciones Muestra', 'Entrega / Obs', 'Precio Oficial', 'Acciones'
        ] as $encabezado) {
            $this->assertStringContainsString($encabezado, $vista);
        }

        $this->assertStringContainsString('id="tabla-inventario-productos"', $vista);
        $this->assertStringContainsString('class="producto-detail-row"', $vista);
        $this->assertStringContainsString('function toggleProductoDetalle', $vista);
        $this->assertStringNotContainsString('function cambiarVista', $vista);
        $this->assertStringNotContainsString('id="contenedor-fichas"', $vista);
    }
}
