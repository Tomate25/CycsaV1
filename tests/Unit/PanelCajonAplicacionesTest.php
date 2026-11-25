<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;

class PanelCajonAplicacionesTest extends TestCase
{
    private function renderPanelConApps(array $apps): string
    {
        $nombre = 'Usuario Prueba';
        $esAdmin = false;
        $cajon_aplicaciones = $apps;
        $kpis = ['total_cotizaciones' => 0, 'total_monto_aprobado' => 0, 'total_en_revision' => 0, 'total_clientes' => 0];
        $tendencia_mensual = [];
        $distribucion_estados = [];
        $distribucion_prioridad = ['Alta' => 0, 'Media' => 0, 'Normal' => 0];
        $top_clientes = [];
        $recientes = [];

        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Usuarios/Vistas/panel.php';
        return (string)ob_get_clean();
    }

    private function crearApps(int $cantidad): array
    {
        $apps = [];
        for ($i = 1; $i <= $cantidad; $i++) {
            $apps[] = [
                'nombre' => "Modulo {$i}",
                'link' => "/Cycsa/publico/modulo-{$i}",
                'icon' => 'fa-solid fa-cube',
                'desc' => "Descripción del módulo {$i}",
                'color' => 'linear-gradient(135deg, #103487, #1e40af)',
            ];
        }
        return $apps;
    }

    public function testHastaCincoModulosSeMuestranDirectosSinCajonAdicional(): void
    {
        $html = $this->renderPanelConApps($this->crearApps(5));

        for ($i = 1; $i <= 5; $i++) {
            $this->assertStringContainsString("Modulo {$i}", $html);
        }
        $this->assertStringNotContainsString('id="btnAbrirCajonApps"', $html, 'Con 5 o menos módulos no debe aparecer botón de Más Aplicaciones.');
        $this->assertStringNotContainsString('id="modalCajonApps"', $html, 'Con 5 o menos módulos no debe renderizarse el modal del cajón.');
    }

    public function testMasDeCincoModulosMuestraCincoPrincipalesYRestantesEnCajonDeAplicaciones(): void
    {
        $html = $this->renderPanelConApps($this->crearApps(9));

        $this->assertStringContainsString('id="btnAbrirCajonApps"', $html);
        $this->assertStringContainsString('+4', $html, 'Debe mostrar badge +4 para los 4 módulos restantes.');
        $this->assertStringContainsString('id="modalCajonApps"', $html);
        $this->assertStringContainsString('id="buscarModuloCajon"', $html);

        // Separar bloque principal (#app-drawer-section) y bloque modal (#modalCajonApps)
        $posModal = strpos($html, 'id="modalCajonApps"');
        $this->assertNotFalse($posModal);
        $partePrincipal = substr($html, 0, (int)$posModal);
        $parteModal = substr($html, (int)$posModal);

        // Los 5 primeros van en el bloque principal y NO en el modal
        for ($i = 1; $i <= 5; $i++) {
            $this->assertStringContainsString("/Cycsa/publico/modulo-{$i}", $partePrincipal);
            $this->assertStringNotContainsString("/Cycsa/publico/modulo-{$i}", $parteModal);
        }

        // Del 6 al 9 van dentro del modal Cajón de Aplicaciones y NO como tarjetas sueltas en el bloque principal
        for ($i = 6; $i <= 9; $i++) {
            $this->assertStringNotContainsString("/Cycsa/publico/modulo-{$i}", $partePrincipal);
            $this->assertStringContainsString("/Cycsa/publico/modulo-{$i}", $parteModal);
        }
    }
}
