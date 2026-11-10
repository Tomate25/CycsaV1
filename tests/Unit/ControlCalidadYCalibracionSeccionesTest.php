<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Operaciones\Controladores\OperacionesControlador;
use Cycsa\Modulos\Configuracion\Controladores\EquiposCalibracionControlador;
use Cycsa\Modulos\EquiposBodegas\Controladores\EquiposBodegasControlador;
use PDO;

class ControlCalidadYCalibracionSeccionesTest extends TestCase
{
    private PDO $db;

    protected function setUp(): void
    {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_nombre'] = 'Admin';
        $_SESSION['usuario_permisos'] = [
            'operaciones' => ['ver' => 1, 'crear_editar' => 1],
            'laboratorio' => ['ver' => 1, 'crear_editar' => 1]
        ];
        $_SESSION['csrf_token'] = 'token_test_qc_123';
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_POST = [];
        $_GET = [];
        $this->db = Conexion::obtenerInstancia();
    }

    protected function tearDown(): void
    {
        unset($_SESSION['error'], $_SESSION['exito'], $_SESSION['flash_exito'], $_SESSION['flash_error']);
        $_POST = [];
        $_GET = [];
    }

    /**
     * TEST 1: /control-calidad contiene las dos pestañas: Réplicas de Ensayos (-CR) y Calibración de Equipos (ISO 17025)
     */
    public function testControlCalidadRenderizaSubnavConReplicasYCalibracion(): void
    {
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/control-calidad';
        $peticion = new Peticion();
        $ctrl = new OperacionesControlador();
        $capturador = new class extends Respuesta {
            public ?string $urlRedir = null;
            public function redirigir(string $url): void {
                $this->urlRedir = $url;
            }
        };

        ob_start();
        $ctrl->controlCalidadReplicas($peticion, $capturador);
        $html = (string)ob_get_clean();

        $this->assertNull($capturador->urlRedir);
        $this->assertStringContainsString('Réplicas de Ensayos (-CR)', $html);
        $this->assertStringContainsString('Calibración de Equipos (ISO 17025)', $html);
        $this->assertStringContainsString('/control-calidad/calibracion', $html);
    }

    /**
     * TEST 2: /control-calidad/calibracion renderiza la sección completa de equipos y parámetros con subnav integrado
     */
    public function testControlCalidadCalibracionRenderizaEquiposYSubnav(): void
    {
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/control-calidad/calibracion';
        $peticion = new Peticion();
        $ctrl = new EquiposCalibracionControlador();
        $capturador = new class extends Respuesta {
            public ?string $urlRedir = null;
            public function redirigir(string $url): void {
                $this->urlRedir = $url;
            }
        };

        ob_start();
        $ctrl->index($peticion, $capturador);
        $html = (string)ob_get_clean();

        $this->assertNull($capturador->urlRedir);
        $this->assertStringContainsString('Equipos, Calibraciones y Parámetros Normativos', $html);
        $this->assertStringContainsString('Calibración de Picnómetros RT-FM-56', $html);
        $this->assertStringContainsString('Correcciones Parametrizables Calidad', $html);
        $this->assertStringContainsString('Réplicas de Ensayos (-CR)', $html);
        $this->assertStringContainsString('/control-calidad/calibracion', $html);
    }

    /**
     * TEST 3: /equipos-bodegas solo deja Bodegas y Traslados (ya no contiene Calibración)
     */
    public function testBodegasSoloDejaBodegasYTrasladosSinCalibracion(): void
    {
        $_SERVER['REQUEST_URI'] = '/Cycsa/publico/equipos-bodegas';
        $peticion = new Peticion();
        $ctrl = new EquiposBodegasControlador();
        $capturador = new class extends Respuesta {
            public ?string $urlRedir = null;
            public function redirigir(string $url): void {
                $this->urlRedir = $url;
            }
        };

        ob_start();
        $ctrl->index($peticion, $capturador);
        $html = (string)ob_get_clean();

        $this->assertNull($capturador->urlRedir);
        $this->assertStringContainsString('Bodegas y traslados', $html);
        $this->assertStringNotContainsString('equipos-calibracion', $html, 'No debe contener enlaces hacia calibración en la vista principal de bodegas.');
    }
}
