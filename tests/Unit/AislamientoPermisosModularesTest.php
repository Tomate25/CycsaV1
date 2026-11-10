<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Usuarios\Controladores\PanelControlador;
use Cycsa\Modulos\Ventas\Controladores\VentasControlador;
use Cycsa\Modulos\EquiposBodegas\Controladores\EquiposBodegasControlador;
use Cycsa\Modulos\Proyectos\Controladores\ProyectosControlador;
use Cycsa\Modulos\Configuracion\Controladores\PlantillasControlador;
use Cycsa\Modulos\Inventario\Controladores\InventarioControlador;
use Cycsa\Modulos\Nomina\Controladores\NominaControlador;
use Cycsa\Modulos\ActivosFijos\Controladores\ActivosFijosControlador;
use Cycsa\Modulos\Prestamos\Controladores\PrestamosControlador;
use Cycsa\Modulos\OrdenesServicio\Controladores\OrdenesServicioControlador;
use ReflectionClass;

/**
 * Suite de Pruebas para Aislamiento Estricto de Módulos y Permisos RBAC.
 * Garantiza que si a un usuario se le da Admin tiene acceso total,
 * y si se le da un módulo específico solo puede ver y operar en ese módulo y lo correspondiente a él,
 * sin ver ni poder acceder a otros módulos.
 */
class AislamientoPermisosModularesTest extends TestCase {

    private array $sessionBackup = [];

    protected function setUp(): void {
        parent::setUp();
        $this->sessionBackup = $_SESSION ?? [];
    }

    protected function tearDown(): void {
        $_SESSION = $this->sessionBackup;
        parent::tearDown();
    }

    /**
     * Valida que un Administrador (Rol 1) tiene acceso universal a todos los módulos y acciones.
     */
    public function testAdminRol1TieneAccesoUniversalATodosLosModulos(): void {
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['usuario_permisos'] = [];

        $modulos = [
            'clientes', 'productos', 'cotizaciones', 'ordenes_servicio', 'ventas',
            'operaciones', 'control_calidad', 'laboratorio', 'equipos_bodegas',
            'plantillas', 'inventario', 'compras', 'contabilidad', 'nomina',
            'prestamos', 'activos_fijos', 'proyectos'
        ];

        foreach ($modulos as $mod) {
            $this->assertTrue(tienePermiso($mod, 'ver'), "Admin debe tener permiso 'ver' en {$mod}");
            $this->assertTrue(tienePermiso($mod, 'crear_editar'), "Admin debe tener permiso 'crear_editar' en {$mod}");
            $this->assertTrue(tienePermiso($mod, 'aprobar'), "Admin debe tener permiso 'aprobar' en {$mod}");
        }
    }

    /**
     * Valida que un usuario con solo 'contabilidad' NO pueda ver ni acceder a otros módulos como Nómina, Préstamos, Activos Fijos o Proyectos.
     */
    public function testUsuarioConSoloContabilidadNoTieneAccesoAOtroModulos(): void {
        $_SESSION['usuario_id'] = 50;
        $_SESSION['usuario_rol'] = 2; // Rol no-admin
        $_SESSION['usuario_permisos'] = [
            'contabilidad' => ['ver' => 1, 'crear_editar' => 1]
        ];

        // Debe tener acceso a contabilidad
        $this->assertTrue(tienePermiso('contabilidad', 'ver'));
        $this->assertTrue(tienePermiso('contabilidad', 'crear_editar'));

        // NO debe tener acceso a otros módulos
        $modulosProhibidos = [
            'nomina', 'prestamos', 'activos_fijos', 'proyectos',
            'compras', 'inventario', 'laboratorio', 'operaciones',
            'cotizaciones', 'ordenes_servicio', 'ventas', 'clientes',
            'productos', 'control_calidad', 'equipos_bodegas', 'plantillas'
        ];

        foreach ($modulosProhibidos as $mod) {
            $this->assertFalse(tienePermiso($mod, 'ver'), "Usuario solo con contabilidad NO debe ver {$mod}");
            $this->assertFalse(tienePermiso($mod, 'crear_editar'), "Usuario solo con contabilidad NO debe editar {$mod}");
        }
    }

    /**
     * Valida que un usuario con solo 'inventario' NO pueda ver Compras, Contabilidad ni otros módulos.
     */
    public function testUsuarioConSoloInventarioNoTieneAccesoAOtroModulos(): void {
        $_SESSION['usuario_id'] = 51;
        $_SESSION['usuario_rol'] = 2;
        $_SESSION['usuario_permisos'] = [
            'inventario' => ['ver' => 1, 'crear_editar' => 1]
        ];

        $this->assertTrue(tienePermiso('inventario', 'ver'));
        $this->assertTrue(tienePermiso('inventario', 'crear_editar'));

        $this->assertFalse(tienePermiso('compras', 'ver'));
        $this->assertFalse(tienePermiso('contabilidad', 'ver'));
        $this->assertFalse(tienePermiso('nomina', 'ver'));
        $this->assertFalse(tienePermiso('proyectos', 'ver'));
        $this->assertFalse(tienePermiso('laboratorio', 'ver'));
    }

    /**
     * Valida que un usuario con solo 'nomina' NO pueda ver Contabilidad ni Préstamos.
     */
    public function testUsuarioConSoloNominaNoTieneAccesoAContabilidad(): void {
        $_SESSION['usuario_id'] = 52;
        $_SESSION['usuario_rol'] = 2;
        $_SESSION['usuario_permisos'] = [
            'nomina' => ['ver' => 1, 'crear_editar' => 1]
        ];

        $this->assertTrue(tienePermiso('nomina', 'ver'));
        $this->assertTrue(tienePermiso('nomina', 'crear_editar'));

        $this->assertFalse(tienePermiso('contabilidad', 'ver'));
        $this->assertFalse(tienePermiso('prestamos', 'ver'));
        $this->assertFalse(tienePermiso('activos_fijos', 'ver'));
    }

    /**
     * Valida que un usuario con solo 'proyectos' NO pueda ver Contabilidad ni Operaciones.
     */
    public function testUsuarioConSoloProyectosNoTieneAccesoAContabilidadNiOperaciones(): void {
        $_SESSION['usuario_id'] = 53;
        $_SESSION['usuario_rol'] = 2;
        $_SESSION['usuario_permisos'] = [
            'proyectos' => ['ver' => 1, 'crear_editar' => 1]
        ];

        $this->assertTrue(tienePermiso('proyectos', 'ver'));
        $this->assertFalse(tienePermiso('contabilidad', 'ver'));
        $this->assertFalse(tienePermiso('operaciones', 'ver'));
    }

    /**
     * Valida que el Cajón de Aplicaciones en el Panel no muestre tarjetas de módulos no autorizados.
     */
    public function testCajonAplicacionesAislaEstrictamenteModulosSegunPermisos(): void {
        $_SESSION['usuario_id'] = 77;
        $_SESSION['usuario_nombre'] = 'Usuario Limitado';
        $_SESSION['usuario_rol'] = 2;
        $_SESSION['usuario_permisos'] = [
            'clientes' => ['ver' => 1],
            'cotizaciones' => ['ver' => 1]
        ];

        $controlador = new PanelControlador();
        $reflector = new ReflectionClass($controlador);
        $metodoIndex = $reflector->getMethod('index');
        $metodoIndex->setAccessible(true);

        ob_start();
        $controlador->index(new Peticion(), new Respuesta());
        $salidaHtml = ob_get_clean();

        // Debe ver Clientes y Cotizaciones
        $this->assertStringContainsString('Cotizaciones', $salidaHtml);
        $this->assertStringContainsString('Clientes', $salidaHtml);

        // NO debe ver módulos no asignados
        $this->assertStringNotContainsString('Contabilidad ERP', $salidaHtml);
        $this->assertStringNotContainsString('Nómina & RRHH', $salidaHtml);
        $this->assertStringNotContainsString('Préstamos Bancarios', $salidaHtml);
        $this->assertStringNotContainsString('Activos Fijos', $salidaHtml);
        $this->assertStringNotContainsString('Compras y Proveedores', $salidaHtml);
        $this->assertStringNotContainsString('Inventario y Kárdex', $salidaHtml);
        $this->assertStringNotContainsString('Plantillas de Ensayos', $salidaHtml);
        $this->assertStringNotContainsString('Gestión de Usuarios', $salidaHtml);
    }

    /**
     * Valida que controladores redirijan a usuarios sin permisos que intentan acceder por URL directa.
     */
    public function testControladoresRechazanAccesoPorUrlSinPermisos(): void {
        $_SESSION['usuario_id'] = 88;
        $_SESSION['usuario_rol'] = 2;
        $_SESSION['usuario_permisos'] = []; // Sin permisos

        // 1. ProyectosControlador
        $proyCtrl = new ProyectosControlador();
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';
        ob_start();
        $proyCtrl->index(new Peticion(), new Respuesta());
        $outProy = ob_get_clean();
        $jsonProy = json_decode($outProy, true);
        $this->assertIsArray($jsonProy);
        $this->assertFalse($jsonProy['exito']);
        $this->assertStringContainsString('permisos', strtolower($jsonProy['error']));

        // 2. EquiposBodegasControlador
        $bodCtrl = new EquiposBodegasControlador();
        ob_start();
        $bodCtrl->index(new Peticion(), new Respuesta());
        $outBod = ob_get_clean();
        $this->assertStringContainsString('No tiene permisos', $_SESSION['flash_error'] ?? '');
    }
}
