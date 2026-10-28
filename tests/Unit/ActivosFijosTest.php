<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\ActivosFijos\Modelos\ActivosFijosModelo;
use Cycsa\Modulos\ActivosFijos\Controladores\ActivosFijosControlador;
use Cycsa\Modulos\Contabilidad\Modelos\ConfigCuentasModelo;
use PDO;

/**
 * Suite de Pruebas Unitarias para Gestión de Activos Fijos y Depreciación Mensual (TAREA-051).
 */
class ActivosFijosTest extends TestCase {

    private PDO $db;
    private ActivosFijosModelo $modelo;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        // Asegurar que la migración 018 esté ejecutada
        $db = $this->db;
        $ruta018 = dirname(__DIR__, 2) . '/database/migrations/018_activos_fijos_depreciacion.php';
        if (file_exists($ruta018)) {
            ob_start();
            include $ruta018;
            ob_end_clean();
        }

        $this->modelo = new ActivosFijosModelo($this->db);
    }

    /**
     * Valida que las tablas relacionales existan y las claves contables estén sembradas.
     */
    public function testMigracion018CreaTablasYSiembraClavesConfigCuentas(): void {
        $tablasEsperadas = ['activos_fijos', 'activos_fijos_depreciaciones'];
        foreach ($tablasEsperadas as $tabla) {
            $check = $this->db->query("SHOW TABLES LIKE '{$tabla}'")->fetchAll(PDO::FETCH_COLUMN);
            $this->assertNotEmpty($check, "La tabla {$tabla} debe existir tras la migración 018.");
        }

        $claves = ['ACTIVOS_MAQUINARIA', 'DEP_ACUM_MAQUINARIA', 'GASTO_DEP_MAQUINARIA'];
        foreach ($claves as $clave) {
            $id = ConfigCuentasModelo::obtenerIdPorClave($clave, $this->db);
            $this->assertNotNull($id, "La clave contable {$clave} debe estar configurada en config_cuentas.");
            $this->assertGreaterThan(0, $id, "El ID de la cuenta para {$clave} debe ser un entero positivo.");
        }
    }

    /**
     * Valida la fórmula de depreciación mensual en línea recta y el cálculo de valor en libros.
     */
    public function testCalculoCuotaMensualEnLineaRectaYValorEnLibros(): void {
        // Caso 1: Valor Adq = 120,000, Residual = 12,000, Vida = 60 meses
        // Base = 108,000, Cuota = 108,000 / 60 = 1,800.00
        $cuota1 = $this->modelo->calcularCuotaMensual(120000.00, 12000.00, 60);
        $this->assertEquals(1800.00, $cuota1);

        $valorLibros1 = $this->modelo->calcularValorEnLibros(120000.00, 3600.00);
        $this->assertEquals(116400.00, $valorLibros1);

        // Caso 2: Sin valor residual
        $cuota2 = $this->modelo->calcularCuotaMensual(50000.00, 0.00, 50);
        $this->assertEquals(1000.00, $cuota2);

        // Caso 3: Vida útil cero o negativa protege contra división por cero
        $cuota3 = $this->modelo->calcularCuotaMensual(50000.00, 0.00, 0);
        $this->assertEquals(0.00, $cuota3);

        // Caso 4: Métricas enriquecidas
        $activoMock = [
            'valor_adquisicion' => 120000.00,
            'valor_residual' => 12000.00,
            'vida_util_meses' => 60,
            'depreciacion_acumulada' => 18000.00
        ];
        $metricas = $this->modelo->calcularMetricas($activoMock);
        $this->assertEquals(108000.00, $metricas['base_depreciable']);
        $this->assertEquals(1800.00, $metricas['cuota_mensual']);
        $this->assertEquals(102000.00, $metricas['valor_en_libros']);
        $this->assertEquals(90000.00, $metricas['pendiente_depreciar']);
        $this->assertEquals(10, $metricas['meses_depreciados']);
        $this->assertEquals(50, $metricas['meses_restantes']);
        $this->assertEquals(16.67, $metricas['porcentaje_depreciado']);
    }

    /**
     * Valida el CRUD completo de activos fijos.
     */
    public function testCrudActivosFijos(): void {
        $codigoTest = strtoupper('AF-TEST-' . bin2hex(random_bytes(3)));
        $datos = [
            'codigo' => $codigoTest,
            'descripcion' => 'Balanza Analítica Digital Precisión 0.001g',
            'categoria' => 'maquinaria_equipo',
            'fecha_adquisicion' => '2026-01-10',
            'valor_adquisicion' => 45000.00,
            'valor_residual' => 5000.00,
            'vida_util_meses' => 60,
            'depreciacion_acumulada' => 0.00,
            'estado' => 'activo'
        ];

        // 1. Crear
        $activoId = $this->modelo->crearActivo($datos);
        $this->assertGreaterThan(0, $activoId);

        // 2. Obtener por ID y por Código
        $activo = $this->modelo->obtenerActivoPorId($activoId);
        $this->assertNotNull($activo);
        $this->assertEquals($codigoTest, $activo['codigo']);
        $this->assertEquals(45000.00, (float)$activo['valor_adquisicion']);
        $this->assertEquals(5000.00, (float)$activo['valor_residual']);
        $this->assertEquals(666.67, $activo['cuota_mensual']); // (45,000 - 5,000) / 60 = 666.67
        $this->assertNotNull($activo['cuenta_activo_id']);
        $this->assertNotNull($activo['cuenta_depreciacion_acum_id']);
        $this->assertNotNull($activo['cuenta_gasto_depreciacion_id']);

        $activoPorCod = $this->modelo->obtenerActivoPorCodigo($codigoTest);
        $this->assertNotNull($activoPorCod);
        $this->assertEquals($activoId, $activoPorCod['id']);

        // 3. Actualizar
        $actualizado = $this->modelo->actualizarActivo($activoId, [
            'descripcion' => 'Balanza Analítica Digital Calibrada ISO/IEC 17025',
            'valor_residual' => 4000.00
        ]);
        $this->assertTrue($actualizado);

        $activoModificado = $this->modelo->obtenerActivoPorId($activoId);
        $this->assertEquals('Balanza Analítica Digital Calibrada ISO/IEC 17025', $activoModificado['descripcion']);
        $this->assertEquals(4000.00, (float)$activoModificado['valor_residual']);
        $this->assertEquals(683.33, $activoModificado['cuota_mensual']); // (45,000 - 4,000) / 60 = 683.33

        // 4. Eliminar (sin depreciaciones)
        $eliminado = $this->modelo->eliminarActivo($activoId);
        $this->assertTrue($eliminado);

        $activoBorrado = $this->modelo->obtenerActivoPorId($activoId);
        $this->assertNull($activoBorrado);
    }

    /**
     * Valida que la simulación proyecte la depreciación sin persistir cambios en la BD.
     */
    public function testSimulacionDepreciacionMensual(): void {
        $periodo = '2026-07';

        $codigoTest = strtoupper('AF-SIM-' . bin2hex(random_bytes(3)));
        $id = $this->modelo->crearActivo([
            'codigo' => $codigoTest,
            'descripcion' => 'Equipo de Cómputo Servidor Laboratorio',
            'categoria' => 'equipo_computo',
            'fecha_adquisicion' => '2026-01-01',
            'valor_adquisicion' => 36000.00,
            'valor_residual' => 0.00,
            'vida_util_meses' => 36,
            'depreciacion_acumulada' => 0.00,
            'estado' => 'activo'
        ]);

        $sim = $this->modelo->simularDepreciacion($periodo);
        $this->assertIsArray($sim);
        $this->assertEquals($periodo, $sim['periodo']);
        $this->assertGreaterThanOrEqual(1, $sim['total_activos']);
        $this->assertGreaterThan(0, $sim['total_depreciacion']);

        // Verificar que en la base de datos el activo aún NO tiene depreciación acumulada
        $activoEnBd = $this->modelo->obtenerActivoPorId($id);
        $this->assertEquals(0.00, (float)$activoEnBd['depreciacion_acumulada']);

        // Limpiar
        $this->modelo->eliminarActivo($id);
    }

    /**
     * Valida la ejecución de depreciación mensual automatizada y el balance estricto en partida doble del asiento contable.
     */
    public function testEjecucionDepreciacionMensualYBalanceContableAsiento(): void {
        $periodo = '2026-08';

        // 1. Crear dos activos activos para el test
        $cod1 = strtoupper('AF-EJ1-' . bin2hex(random_bytes(3)));
        $cod2 = strtoupper('AF-EJ2-' . bin2hex(random_bytes(3)));

        $id1 = $this->modelo->crearActivo([
            'codigo' => $cod1,
            'descripcion' => 'Camioneta Toyota Hilux Supervisión de Obra',
            'categoria' => 'vehiculos',
            'fecha_adquisicion' => '2026-01-01',
            'valor_adquisicion' => 600000.00,
            'valor_residual' => 60000.00, // Base: 540,000
            'vida_util_meses' => 60, // Cuota: 9,000.00
            'depreciacion_acumulada' => 0.00,
            'estado' => 'activo'
        ]);

        $id2 = $this->modelo->crearActivo([
            'codigo' => $cod2,
            'descripcion' => 'Espectrofotómetro de Absorción Atómica',
            'categoria' => 'maquinaria_equipo',
            'fecha_adquisicion' => '2026-01-01',
            'valor_adquisicion' => 240000.00,
            'valor_residual' => 0.00, // Base: 240,000
            'vida_util_meses' => 120, // Cuota: 2,000.00
            'depreciacion_acumulada' => 0.00,
            'estado' => 'activo'
        ]);

        // 2. Ejecutar depreciación mensual
        $res = $this->modelo->ejecutarDepreciacionMensual($periodo, 1);
        $this->assertTrue($res['exito']);
        $this->assertEquals($periodo, $res['periodo']);
        $this->assertGreaterThanOrEqual(2, $res['total_procesados']);
        $this->assertNotNull($res['asiento_id']);
        $this->assertGreaterThan(0, $res['asiento_id']);

        $asientoId = (int)$res['asiento_id'];

        // 3. Validar actualización de saldos en activos_fijos
        $act1 = $this->modelo->obtenerActivoPorId($id1);
        $this->assertEquals(9000.00, (float)$act1['depreciacion_acumulada']);
        $this->assertEquals(591000.00, (float)$act1['valor_en_libros']);

        $act2 = $this->modelo->obtenerActivoPorId($id2);
        $this->assertEquals(2000.00, (float)$act2['depreciacion_acumulada']);
        $this->assertEquals(238000.00, (float)$act2['valor_en_libros']);

        // 4. Validar histórico en activos_fijos_depreciaciones
        $deprecAct1 = $this->modelo->obtenerDepreciacionesPorActivo($id1);
        $this->assertNotEmpty($deprecAct1);
        $this->assertEquals($periodo, $deprecAct1[0]['periodo']);
        $this->assertEquals(9000.00, (float)$deprecAct1[0]['monto_depreciacion']);
        $this->assertEquals($asientoId, (int)$deprecAct1[0]['id_asiento']);

        // 5. Validar comprobación estricta de Partida Doble en partidas_diario
        $stmtAsiento = $this->db->prepare("
            SELECT SUM(debe) AS total_debe, SUM(haber) AS total_haber, COUNT(*) AS num_lineas
            FROM partidas_diario_detalles
            WHERE id_partida = :asiento
        ");
        $stmtAsiento->execute(['asiento' => $asientoId]);
        $balance = $stmtAsiento->fetch(PDO::FETCH_ASSOC);

        $totalDebe = round((float)$balance['total_debe'], 2);
        $totalHaber = round((float)$balance['total_haber'], 2);
        $numLineas = (int)$balance['num_lineas'];

        $this->assertGreaterThan(0, $totalDebe, "El total al Debe debe ser mayor a 0.");
        $this->assertEquals($totalDebe, $totalHaber, "El asiento contable debe estar estrictamente balanceado: Debe ({$totalDebe}) == Haber ({$totalHaber}).");
        $this->assertGreaterThanOrEqual(2, $numLineas, "El asiento debe constar de al menos 2 líneas contables.");

        // 6. Validar idempotencia: Ejecutar de nuevo para el mismo periodo no debe reprocesar ni duplicar
        $resReintento = $this->modelo->ejecutarDepreciacionMensual($periodo, 1);
        $this->assertTrue($resReintento['exito']);
        $this->assertEquals(0, $resReintento['total_procesados'], "No debe procesar activos que ya fueron depreciados en el periodo {$periodo}.");
    }

    /**
     * Valida que un activo que alcanza su valor residual limite la cuota final y pase automáticamente a estado 'depreciado'.
     */
    public function testTransicionAutomaticaAEstadoDepreciado(): void {
        $periodo = '2026-09';
        $codigoTest = strtoupper('AF-FIN-' . bin2hex(random_bytes(3)));

        // Activo con valor 10,000, residual 1,000 (Base 9,000), dep acumulada actual 8,700
        // Le restan 300 para llegar al residual.
        // Vida útil 10 meses => Cuota teórica: 900.
        // Sin embargo, este mes solo debe depreciar 300 (el saldo restante).
        $id = $this->modelo->crearActivo([
            'codigo' => $codigoTest,
            'descripcion' => 'Horno Eléctrico de Secado de Suelos',
            'categoria' => 'maquinaria_equipo',
            'fecha_adquisicion' => '2025-01-01',
            'valor_adquisicion' => 10000.00,
            'valor_residual' => 1000.00,
            'vida_util_meses' => 10,
            'depreciacion_acumulada' => 8700.00,
            'estado' => 'activo'
        ]);

        $res = $this->modelo->ejecutarDepreciacionMensual($periodo, 1);
        $this->assertTrue($res['exito']);

        $activo = $this->modelo->obtenerActivoPorId($id);
        $this->assertEquals(9000.00, (float)$activo['depreciacion_acumulada']);
        $this->assertEquals(1000.00, (float)$activo['valor_en_libros']);
        $this->assertEquals('depreciado', $activo['estado'], "El activo debe pasar a estado 'depreciado' al alcanzar su valor residual.");

        $deprecs = $this->modelo->obtenerDepreciacionesPorActivo($id);
        $deprecPeriodo = array_values(array_filter($deprecs, fn($d) => $d['periodo'] === $periodo))[0];
        $this->assertEquals(300.00, (float)$deprecPeriodo['monto_depreciacion'], "La cuota mensual debe ajustarse exactamente al remanente pendiente (300.00 en lugar de 900.00).");
    }

    /**
     * Valida que las rutas de Activos Fijos estén debidamente registradas con AuthMiddleware en rutas/web.php.
     */
    public function testRutasActivosFijosRegistradasEnWebPhp(): void {
        $rutas = file_get_contents(dirname(__DIR__, 2) . '/rutas/web.php');
        $rutasEsperadas = [
            '/activos-fijos',
            '/activos-fijos/crear',
            '/activos-fijos/guardar',
            '/activos-fijos/editar',
            '/activos-fijos/actualizar',
            '/activos-fijos/eliminar',
            '/activos-fijos/detalle',
            '/activos-fijos/depreciacion',
            '/activos-fijos/depreciacion/simular-ajax',
            '/activos-fijos/depreciacion/ejecutar'
        ];

        foreach ($rutasEsperadas as $r) {
            $this->assertStringContainsString($r, $rutas, "La ruta {$r} debe estar declarada en rutas/web.php");
        }
        $this->assertStringContainsString('ActivosFijosControlador', $rutas);
    }

    /**
     * Valida que el controlador rechace peticiones sin token CSRF válido.
     */
    public function testControladorRechazaCsrfInvalido(): void {
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_rol'] = 1;
        $_SESSION['csrf_token'] = 'TOKEN_REAL_VALIDO_' . bin2hex(random_bytes(16));

        $_POST = [
            'csrf_token' => 'TOKEN_FALSO_INCORRECTO',
            'es_ajax' => '1',
            'codigo' => 'AF-CSRF-TEST',
            'descripcion' => 'Intento CSRF'
        ];
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';

        $controlador = new ActivosFijosControlador();
        ob_start();
        $controlador->guardar(new Peticion(), new Respuesta());
        $output = ob_get_clean();

        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertFalse($json['ok']);
        $this->assertStringContainsString('CSRF', $json['error']);
    }

    /**
     * Valida que el controlador deniegue el acceso a usuarios sin permisos (RBAC).
     */
    public function testControladorRechazaAccesoSinPermisos(): void {
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION['usuario_id'] = 999;
        $_SESSION['usuario_rol'] = 3; // Usuario sin rol admin
        $_SESSION['usuario_permisos'] = []; // Sin permiso de activos_fijos ni contabilidad

        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';

        $controlador = new ActivosFijosControlador();
        ob_start();
        $controlador->index(new Peticion(), new Respuesta());
        $output = ob_get_clean();

        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertFalse($json['ok']);
        $this->assertStringContainsString('permisos', strtolower($json['error']));
    }

    /**
     * Valida la simulación de depreciación vía endpoint AJAX del controlador.
     */
    public function testControladorSimularDepreciacionAjax(): void {
        if (!defined('PHPUNIT_RUNNING')) {
            define('PHPUNIT_RUNNING', true);
        }
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        $_SESSION['usuario_id'] = 1;
        $_SESSION['usuario_rol'] = 1;

        $_POST = [
            'periodo' => '2026-10',
            'es_ajax' => '1'
        ];
        $_SERVER['HTTP_X_REQUESTED_WITH'] = 'xmlhttprequest';

        $controlador = new ActivosFijosControlador();
        ob_start();
        $controlador->simularDepreciacionAjax(new Peticion(), new Respuesta());
        $output = ob_get_clean();

        $json = json_decode($output, true);
        $this->assertIsArray($json);
        $this->assertTrue($json['ok']);
        $this->assertArrayHasKey('resultado', $json);
        $this->assertEquals('2026-10', $json['resultado']['periodo']);
    }
}
