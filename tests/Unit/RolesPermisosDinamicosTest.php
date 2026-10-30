<?php

namespace Tests\Unit;

use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Usuarios\Controladores\RolesControlador;
use Cycsa\Modulos\Usuarios\Controladores\UsuariosControlador;
use Cycsa\Modulos\Autenticacion\Controladores\AutenticacionControlador;
use PDO;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class RolesPermisosDinamicosTest extends TestCase {

    private PDO $db;
    private array $sessionPrevia = [];

    protected function setUp(): void {
        parent::setUp();
        $this->sessionPrevia = $_SESSION ?? [];
        $this->db = Conexion::obtenerInstancia();
    }

    protected function tearDown(): void {
        $_SESSION = $this->sessionPrevia;
        parent::tearDown();
    }

    public function testTienePermisoAdminSiempreRetornaTrue(): void {
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_permisos'] = [];

        $this->assertTrue(tienePermiso('cotizaciones', 'ver'));
        $this->assertTrue(tienePermiso('operaciones', 'crear_editar'));
        $this->assertTrue(tienePermiso('cualquier_modulo_inexistente', 'ver'));
    }

    public function testTienePermisoDirectoFunciona(): void {
        $_SESSION['usuario_rol'] = 2;
        $_SESSION['usuario_permisos'] = [
            'operaciones' => ['ver' => 1, 'crear_editar' => 1],
            'nomina' => ['ver' => 1]
        ];

        $this->assertTrue(tienePermiso('operaciones', 'ver'));
        $this->assertTrue(tienePermiso('operaciones', 'crear_editar'));
        $this->assertTrue(tienePermiso('nomina', 'ver'));
        $this->assertFalse(tienePermiso('nomina', 'crear_editar'));
        $this->assertFalse(tienePermiso('inventario', 'ver'));
    }

    public function testTienePermisoFallbackAliasRetrocompatibles(): void {
        $_SESSION['usuario_rol'] = 2;
        // Usuario con solo cotizaciones y laboratorio
        $_SESSION['usuario_permisos'] = [
            'cotizaciones' => ['ver' => 1],
            'laboratorio' => ['ver' => 1]
        ];

        // Ventas y Órdenes de Servicio heredan por fallback desde cotizaciones
        $this->assertTrue(tienePermiso('ventas', 'ver'), 'Ventas debe heredar fallback de cotizaciones');
        $this->assertTrue(tienePermiso('ordenes_servicio', 'ver'), 'Órdenes de servicio debe heredar fallback de cotizaciones');

        // Control de Calidad y Equipos Bodegas heredan fallback de laboratorio
        $this->assertTrue(tienePermiso('control_calidad', 'ver'), 'Control de calidad debe heredar fallback de laboratorio');
        $this->assertTrue(tienePermiso('equipos_bodegas', 'ver'), 'Equipos bodegas debe heredar fallback de laboratorio');
    }

    public function testNormalizarPermisosRolesControladorNoDescartaModulos(): void {
        $controlador = new RolesControlador();
        $ref = new ReflectionMethod($controlador, 'normalizarPermisos');
        $ref->setAccessible(true);

        $input = [
            'clientes' => ['ver' => '1', 'crear_editar' => '1'],
            'operaciones' => ['ver' => 1],
            'ordenes_servicio' => ['ver' => '1', 'aprobar' => '1'],
            'control_calidad' => ['ver' => '1'],
            'nomina' => ['ver' => 1, 'crear_editar' => 1],
            'activos_fijos' => ['ver' => 1],
            'modulo_invalido!' => ['ver' => 1], // Debe sanitizarse a modulo_invalido
            'vacio' => []
        ];

        $resultado = $ref->invoke($controlador, $input);

        $this->assertArrayHasKey('clientes', $resultado);
        $this->assertArrayHasKey('operaciones', $resultado);
        $this->assertArrayHasKey('ordenes_servicio', $resultado);
        $this->assertArrayHasKey('control_calidad', $resultado);
        $this->assertArrayHasKey('nomina', $resultado);
        $this->assertArrayHasKey('activos_fijos', $resultado);
        $this->assertArrayHasKey('modulo_invalido', $resultado);
        $this->assertSame(1, $resultado['ordenes_servicio']['aprobar']);
    }

    public function testNormalizarPermisosUsuariosControladorNoDescartaModulos(): void {
        $controlador = new UsuariosControlador();
        $ref = new ReflectionMethod($controlador, 'normalizarPermisos');
        $ref->setAccessible(true);

        $input = [
            'ventas' => ['ver' => 1],
            'prestamos' => ['ver' => 1],
            'proyectos' => ['ver' => 1, 'crear_editar' => 1]
        ];

        $resultado = $ref->invoke($controlador, $input);

        $this->assertArrayHasKey('ventas', $resultado);
        $this->assertArrayHasKey('prestamos', $resultado);
        $this->assertArrayHasKey('proyectos', $resultado);
        $this->assertSame(1, $resultado['proyectos']['crear_editar']);
    }

    public function testAutenticacionCombinaPermisosRolYUsuario(): void {
        $ctrl = new AutenticacionControlador();
        $ref = new ReflectionMethod($ctrl, 'resolverPermisosUsuario');
        $ref->setAccessible(true);

        // Simulamos un usuario con rol 6 (Laboratorio) que en BD tiene operaciones y laboratorio
        // y el usuario tiene además clientes:ver
        $usuarioSimulado = [
            'id' => 99999,
            'id_rol' => 6,
            'permisos' => json_encode(['clientes' => ['ver' => 1]])
        ];

        $res = $ref->invoke($ctrl, $usuarioSimulado, $this->db);

        $this->assertIsArray($res);
        $this->assertArrayHasKey('clientes', $res, 'Debe incluir permisos específicos del usuario');
        $this->assertArrayHasKey('laboratorio', $res, 'Debe incluir permisos base del rol');
        $this->assertArrayHasKey('operaciones', $res, 'Debe incluir permisos de operaciones del rol');
    }

    public function testChecklistPermisosVistaContiene16ModulosYBotones(): void {
        $permisos = [];
        ob_start();
        include dirname(__DIR__, 2) . '/app/Modulos/Usuarios/Vistas/roles/_permisos_checklist.php';
        $html = ob_get_clean();

        $this->assertStringContainsString('id="btn-marcar-todos-permisos"', $html);
        $this->assertStringContainsString('id="btn-desmarcar-todos-permisos"', $html);
        $this->assertStringContainsString('permisos[clientes][ver]', $html);
        $this->assertStringContainsString('permisos[productos][ver]', $html);
        $this->assertStringContainsString('permisos[cotizaciones][ver]', $html);
        $this->assertStringContainsString('permisos[ordenes_servicio][ver]', $html);
        $this->assertStringContainsString('permisos[ventas][ver]', $html);
        $this->assertStringContainsString('permisos[operaciones][ver]', $html);
        $this->assertStringContainsString('permisos[control_calidad][ver]', $html);
        $this->assertStringContainsString('permisos[laboratorio][ver]', $html);
        $this->assertStringContainsString('permisos[equipos_bodegas][ver]', $html);
        $this->assertStringContainsString('permisos[plantillas][ver]', $html);
        $this->assertStringContainsString('permisos[inventario][ver]', $html);
        $this->assertStringContainsString('permisos[compras][ver]', $html);
        $this->assertStringContainsString('permisos[contabilidad][ver]', $html);
        $this->assertStringContainsString('permisos[nomina][ver]', $html);
        $this->assertStringContainsString('permisos[prestamos][ver]', $html);
        $this->assertStringContainsString('permisos[activos_fijos][ver]', $html);
        $this->assertStringContainsString('permisos[proyectos][ver]', $html);
    }
}
