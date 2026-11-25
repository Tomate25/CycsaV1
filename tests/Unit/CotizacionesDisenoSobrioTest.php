<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class CotizacionesDisenoSobrioTest extends TestCase
{
    public function testTodasLasPantallasDeCotizacionesUsanDisenoSobrioUnificado(): void
    {
        $root = dirname(__DIR__, 2);
        $this->assertFileExists($root . '/publico/css/cycsa-sobrio.css');
        $this->assertFileExists($root . '/publico/css/cotizaciones-sobrio.css');

        $vistas = [
            'index.php' => $root . '/app/Modulos/Cotizaciones/Vistas/index.php',
            'crear.php' => $root . '/app/Modulos/Cotizaciones/Vistas/crear.php',
            'editar.php' => $root . '/app/Modulos/Cotizaciones/Vistas/editar.php',
            'detalle.php' => $root . '/app/Modulos/Cotizaciones/Vistas/detalle.php',
        ];

        foreach ($vistas as $nombre => $ruta) {
            $src = (string)file_get_contents($ruta);
            $this->assertStringContainsString('/Cycsa/publico/css/cycsa-sobrio.css', $src, "{$nombre} debe cargar cycsa-sobrio.css");
            $this->assertStringContainsString('/Cycsa/publico/css/cotizaciones-sobrio.css', $src, "{$nombre} debe cargar cotizaciones-sobrio.css");
            $this->assertStringContainsString('cy-cot-wrap', $src, "{$nombre} debe envolverse en .cy-cot-wrap");
            $this->assertStringContainsString('cy-page-title', $src, "{$nombre} debe usar .cy-page-title");
            $this->assertStringNotContainsString('style=\\"', $src, "{$nombre} no debe tener atributos style escapados");
        }

        // index.php debe usar filtro segmentado .cy-seg y pills .cy-pill
        $indexSrc = (string)file_get_contents($vistas['index.php']);
        $this->assertStringContainsString('cy-seg', $indexSrc);
        $this->assertStringContainsString('cy-pill', $indexSrc);

        // editar.php no debe tener sección duplicada de Detalle de Ensayos
        $editarSrc = (string)file_get_contents($vistas['editar.php']);
        $this->assertSame(1, substr_count($editarSrc, 'Detalle de Ensayos / Servicios'));
    }
}
