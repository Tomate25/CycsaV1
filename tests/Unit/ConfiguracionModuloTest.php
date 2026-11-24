<?php

declare(strict_types=1);

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Configuracion\Modelos\ConfiguracionModelo;
use Cycsa\Modulos\Configuracion\Controladores\ConfiguracionControlador;
use PDO;

require_once dirname(__DIR__, 2) . '/app/Helpers/funciones.php';

class ConfiguracionModuloTest extends TestCase
{
    private PDO $db;
    private array $limpiar = [
        'configuracion_comercial' => [],
        'tecnicos' => [],
        'vehiculos' => [],
    ];

    protected function setUp(): void
    {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $this->db = Conexion::obtenerInstancia();
    }

    protected function tearDown(): void
    {
        foreach ($this->limpiar['configuracion_comercial'] as $id) {
            $this->db->prepare("DELETE FROM configuracion_comercial WHERE id = ?")->execute([$id]);
        }
        foreach ($this->limpiar['tecnicos'] as $id) {
            $this->db->prepare("DELETE FROM tecnicos WHERE id = ?")->execute([$id]);
        }
        foreach ($this->limpiar['vehiculos'] as $id) {
            $this->db->prepare("DELETE FROM vehiculos WHERE id = ?")->execute([$id]);
        }
        parent::tearDown();
    }

    public function testCrudComercialTecnicosYVehiculosEnConfiguracionModelo(): void
    {
        $modelo = new ConfiguracionModelo();
        $suf = substr(uniqid(), -5);

        // 1. Configuración comercial (guardar, obtenerPorId, actualizar, eliminar)
        $this->assertTrue($modelo->guardar('condicion_pago', "Pago Test {$suf}"));
        $idConf = (int)$this->db->lastInsertId();
        $this->limpiar['configuracion_comercial'][] = $idConf;

        $rowConf = $modelo->obtenerPorId($idConf);
        $this->assertIsArray($rowConf);
        $this->assertSame("Pago Test {$suf}", $rowConf['valor']);
        $this->assertTrue($modelo->actualizar($idConf, "Pago Editado {$suf}"));
        $this->assertSame("Pago Editado {$suf}", $modelo->obtenerPorId($idConf)['valor']);
        $this->assertTrue($modelo->eliminar($idConf));

        // 2. Técnicos (obtenerTecnicos, agregarTecnico, actualizarTecnico, eliminarTecnico)
        $this->assertTrue($modelo->agregarTecnico("Técnico Test {$suf}"));
        $idTec = (int)$this->db->lastInsertId();
        $this->limpiar['tecnicos'][] = $idTec;

        $tecnicos = $modelo->obtenerTecnicos();
        $this->assertContains("Técnico Test {$suf}", array_column($tecnicos, 'nombre'));
        $this->assertTrue($modelo->actualizarTecnico($idTec, "Técnico Editado {$suf}"));
        $this->assertContains("Técnico Editado {$suf}", array_column($modelo->obtenerTecnicos(), 'nombre'));
        $this->assertTrue($modelo->eliminarTecnico($idTec));

        // 3. Vehículos (obtenerVehiculos, agregarVehiculo, actualizarVehiculo, eliminarVehiculo)
        $placa = 'TST-' . strtoupper($suf);
        $this->assertTrue($modelo->agregarVehiculo($placa, 'Toyota', 'Hilux'));
        $idVeh = (int)$this->db->lastInsertId();
        $this->limpiar['vehiculos'][] = $idVeh;

        $vehiculos = $modelo->obtenerVehiculos();
        $this->assertContains($placa, array_column($vehiculos, 'placa'));
        $this->assertTrue($modelo->actualizarVehiculo($idVeh, $placa, 'Nissan', 'Frontier'));
        $vehEdit = array_values(array_filter($modelo->obtenerVehiculos(), fn($v) => (int)$v['id'] === $idVeh));
        $this->assertCount(1, $vehEdit);
        $this->assertSame('Nissan', $vehEdit[0]['marca']);
        $this->assertSame('Frontier', $vehEdit[0]['modelo']);
        $this->assertTrue($modelo->eliminarVehiculo($idVeh));
    }

    public function testConfiguracionControladorIndexRenderizaSinError(): void
    {
        $idAdmin = (int)($this->db->query("SELECT id FROM usuarios WHERE id_rol = 1 ORDER BY id ASC LIMIT 1")->fetchColumn() ?: 1);
        $_SESSION['usuario_id'] = $idAdmin;
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_nombre'] = 'Admin Test';
        $_SESSION['usuario_permisos'] = ['configuracion' => ['ver' => 1, 'crear_editar' => 1]];
        $_GET = ['tab' => 'logistica'];
        $_SERVER['REQUEST_METHOD'] = 'GET';

        $ctrl = new ConfiguracionControlador();
        ob_start();
        $ctrl->index(new Peticion(), new Respuesta());
        $html = (string)ob_get_clean();

        $this->assertStringContainsString('Configuración', $html);
        $this->assertStringContainsString('modalNuevoTecnico', $html);
        $this->assertStringContainsString('modalNuevoVehiculo', $html);
    }
}
