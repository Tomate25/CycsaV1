<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class OrdenesServicioDisenoSobrioTest extends TestCase
{
    public function testTodasLasPantallasDeOrdenesServicioUsanDisenoSobrioUnificado(): void
    {
        $root = dirname(__DIR__, 2);
        $this->assertFileExists($root . '/publico/css/cycsa-sobrio.css');
        $this->assertFileExists($root . '/publico/css/ordenes-servicio-sobrio.css');

        $vistas = [
            'index.php' => $root . '/app/Modulos/OrdenesServicio/Vistas/index.php',
            'crear.php' => $root . '/app/Modulos/OrdenesServicio/Vistas/crear.php',
            'editar.php' => $root . '/app/Modulos/OrdenesServicio/Vistas/editar.php',
            'detalle.php' => $root . '/app/Modulos/OrdenesServicio/Vistas/detalle.php',
            'programar_muestreo.php' => $root . '/app/Modulos/OrdenesServicio/Vistas/programar_muestreo.php',
            'bitacora.php' => $root . '/app/Modulos/OrdenesServicio/Vistas/bitacora.php',
        ];

        foreach ($vistas as $nombre => $ruta) {
            $src = (string)file_get_contents($ruta);
            $this->assertStringContainsString('/Cycsa/publico/css/cycsa-sobrio.css', $src, "{$nombre} debe cargar cycsa-sobrio.css");
            $this->assertStringContainsString('/Cycsa/publico/css/ordenes-servicio-sobrio.css', $src, "{$nombre} debe cargar ordenes-servicio-sobrio.css");
            $this->assertStringContainsString('cy-os-wrap', $src, "{$nombre} debe envolverse en .cy-os-wrap");
        }

        // Verificar que index.php mantiene las clases funcionales clave + header sobrio
        $indexSrc = (string)file_get_contents($vistas['index.php']);
        $this->assertStringContainsString('cy-page-title', $indexSrc);
        $this->assertStringContainsString('lims-toolbar-top', $indexSrc);
        $this->assertStringContainsString('lims-quick-tabs', $indexSrc);
        $this->assertStringContainsString('quick-tab-link', $indexSrc);
        $this->assertStringNotContainsString('background:linear-gradient(135deg, #0f172a', $indexSrc, 'El modal FM-40B debe usar cabecera sobria blanca');
        $this->assertStringNotContainsString('background:linear-gradient(135deg, #1e1b4b', $indexSrc, 'El modal de Período debe usar cabecera sobria blanca');

        // Verificar que la tabla crece naturalmente como Cotizaciones (sin cajón vacío ni acordeón en fila)
        $this->assertStringNotContainsString('min-height: 340px', $indexSrc, 'La tabla de O/S no debe tener cajón vacío min-height: 340px');
        $this->assertStringNotContainsString('<tr class="fila-desplegable"', $indexSrc, 'Las opciones de O/S deben abrirse en ventana modal, no en acordeón en fila');
        $this->assertStringContainsString('id="modalOpcionesOS"', $indexSrc, 'Debe existir la ventana modal #modalOpcionesOS');
        $this->assertStringContainsString('abrirModalOpcionesOS(', $indexSrc, 'Debe existir el botón/función abrirModalOpcionesOS');

        $cssOsSrc = (string)file_get_contents($root . '/publico/css/ordenes-servicio-sobrio.css');
        $this->assertStringContainsString('min-height: 0 !important;', $cssOsSrc);

        // Verificar que detalle.php conserva intacto el bloque @media print y oculta la tabla extra de Hojas RT-FM-13/RT-FM-60
        $detSrc = (string)file_get_contents($vistas['detalle.php']);
        $this->assertStringContainsString('@media print', $detSrc);
        $this->assertStringNotContainsString('Hojas de Servicio (RT-FM-13) y Solicitudes a Laboratorio (RT-FM-60)', $detSrc);
    }
}
