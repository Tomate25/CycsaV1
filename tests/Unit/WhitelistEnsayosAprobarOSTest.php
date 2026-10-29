<?php

namespace Tests\Unit;

use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use Cycsa\Modulos\OrdenesServicio\Controladores\OrdenesServicioControlador;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Nucleo\Conexion;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use PDO;
use PHPUnit\Framework\TestCase;

class WhitelistEnsayosAprobarOSTest extends TestCase {

    private PDO $db;
    private OrdenServicioModelo $osModelo;
    private OperacionModelo $opModelo;

    /** @var array<int> */
    private array $osIds = [];
    /** @var array<int> */
    private array $cotIds = [];
    /** @var array<int> */
    private array $detalleIds = [];
    /** @var array<int> */
    private array $clienteIds = [];
    /** @var array<int> */
    private array $usuarioIds = [];

    private int $usuarioId = 0;

    protected function setUp(): void {
        parent::setUp();
        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }
        $_SESSION = [];
        $_POST = [];
        $_GET = [];
        $_SERVER['REQUEST_METHOD'] = 'GET';
        $_SERVER['REMOTE_ADDR'] = '127.0.0.1';
        $_SERVER['SCRIPT_NAME'] = '/Cycsa/publico/index.php';

        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->osModelo = new OrdenServicioModelo();
        $this->opModelo = new OperacionModelo();
        $this->asegurarEsquemaMinimo();
        $this->ejecutarUp();
        $this->usuarioId = $this->insertar('usuarios', [
            'id_rol' => 1,
            'nombre' => 'Supervisor C2',
            'email' => 'c2-wl-' . uniqid('', true) . '@test.local',
            'password' => 'x',
            'activo' => 1,
        ]);
    }

    protected function tearDown(): void {
        if (isset($this->db) && $this->db->inTransaction()) {
            $this->db->rollBack();
        }
        if (isset($this->db)) {
            $this->borrarIds('orden_servicio_ensayos_permitidos', 'id_os', $this->osIds);
            $this->borrarIds('bitacora', 'id_referencia', $this->osIds);
            $this->borrarIds('recepcion_muestras', 'id_os', $this->osIds);
            $this->borrarIds('ordenes_servicio', 'id', $this->osIds);
            $this->borrarIds('cotizacion_detalles', 'id', $this->detalleIds);
            $this->borrarIds('cotizaciones', 'id', $this->cotIds);
            $this->borrarIds('clientes', 'id', $this->clienteIds);
            $this->borrarIds('usuarios', 'id', $this->usuarioIds);
        }
        $_SESSION = [];
        $_POST = [];
        parent::tearDown();
    }

    public function testMigracion025EsIdempotenteYSoloAgregaLaTablaNueva(): void {
        $columnasOsAntes = $this->nombresColumnas('ordenes_servicio');
        $tipoDescripcionAntes = $this->tipoColumna('cotizacion_detalles', 'descripcion_ensayo');

        $this->ejecutarUp();
        $this->assertTrue($this->osModelo->tablaEnsayosPermitidosDisponible());

        $columnas = $this->nombresColumnas('orden_servicio_ensayos_permitidos');
        foreach (['id', 'id_os', 'id_cotizacion_detalle', 'creado_por', 'creado_en'] as $columna) {
            $this->assertContains($columna, $columnas);
        }

        $indices = $this->db->query("SHOW INDEX FROM orden_servicio_ensayos_permitidos")->fetchAll(PDO::FETCH_ASSOC);
        $nombresIndice = array_column($indices, 'Key_name');
        $this->assertContains('uq_os_ensayo_permitido', $nombresIndice);

        $fks = $this->db->query("SELECT CONSTRAINT_NAME FROM information_schema.TABLE_CONSTRAINTS
            WHERE TABLE_SCHEMA = DATABASE()
              AND TABLE_NAME = 'orden_servicio_ensayos_permitidos'
              AND CONSTRAINT_TYPE = 'FOREIGN KEY'")->fetchAll(PDO::FETCH_COLUMN);
        $this->assertContains('fk_osep_orden', $fks);
        $this->assertContains('fk_osep_detalle', $fks);

        $src = file_get_contents(dirname(__DIR__, 2) . '/database/migrations/025_orden_servicio_ensayos_permitidos.php');
        $this->assertNotFalse($src);
        $this->assertStringNotContainsString('formatos_schema', $src);
        $this->assertDoesNotMatchRegularExpression('/ALTER\s+TABLE\s+`?cotizacion_detalles`?/i', $src);

        if (function_exists('revertirMigracion025')) {
            ob_start();
            revertirMigracion025($this->db);
            ob_end_clean();
        }
        $this->assertFalse($this->osModelo->tablaEnsayosPermitidosDisponible());
        $this->assertNotEmpty($this->db->query("SHOW TABLES LIKE 'ordenes_servicio'")->fetch(PDO::FETCH_NUM));
        $this->assertNotEmpty($this->db->query("SHOW TABLES LIKE 'cotizacion_detalles'")->fetch(PDO::FETCH_NUM));

        $this->ejecutarUp();
        $this->assertTrue($this->osModelo->tablaEnsayosPermitidosDisponible());
        $this->assertSame($columnasOsAntes, $this->nombresColumnas('ordenes_servicio'));
        $this->assertSame($tipoDescripcionAntes, $this->tipoColumna('cotizacion_detalles', 'descripcion_ensayo'));
    }

    public function testAprobarSubconjuntoHabilitaSoloEsosEnsayosYNoTocaLaCotizacion(): void {
        $fixture = $this->crearOrdenConEnsayos(['Compresion', 'Granulometria', 'Proctor']);
        $habilitar = [$fixture['detalles'][0], $fixture['detalles'][2]];
        $antes = $this->filasDetalles($fixture['cotizacion']);

        $this->aprobar($fixture['os'], $habilitar, 3);

        $this->assertStringContainsString('aprobada', strtolower((string)($_SESSION['exito'] ?? '')));
        $this->assertSame('Aprobada', $this->estadoOs($fixture['os']));
        $this->assertSame($antes, $this->filasDetalles($fixture['cotizacion']));

        $ids = $this->osModelo->obtenerIdsPermitidos($fixture['os']);
        $this->assertSame($habilitar, $ids);
        $this->assertTrue($this->osModelo->esEnsayoPermitido($fixture['os'], $habilitar[0]));
        $this->assertFalse($this->osModelo->esEnsayoPermitido($fixture['os'], $fixture['detalles'][1]));

        $stmtCreador = $this->db->prepare("SELECT creado_por FROM orden_servicio_ensayos_permitidos WHERE id_os = :id");
        $stmtCreador->execute(['id' => $fixture['os']]);
        $creadores = array_map('intval', $stmtCreador->fetchAll(PDO::FETCH_COLUMN));
        $this->assertContains($this->usuarioId, $creadores);

        $items = $this->opModelo->obtenerItemsOS($fixture['os']);
        $this->assertCount(2, $items);
        $this->assertEqualsCanonicalizing($habilitar, array_map('intval', array_column($items, 'id')));
        foreach ($items as $item) {
            $this->assertSame(1, (int)$item['permitido']);
        }

        $os = $this->osModelo->obtenerPorId($fixture['os']);
        $this->assertNotNull($os);
        $this->assertCount(2, $os['ensayos']);
        $this->assertEqualsCanonicalizing($habilitar, array_map(static fn(array $ensayo): int => (int)$ensayo['id'], $os['ensayos']));

        $bitacora = $this->db->prepare("SELECT descripcion FROM bitacora WHERE modulo = 'ordenes_servicio' AND accion = 'aprobar' AND id_referencia = :id ORDER BY id DESC LIMIT 1");
        $bitacora->execute(['id' => $fixture['os']]);
        $texto = (string)$bitacora->fetchColumn();
        $this->assertStringContainsString('Ensayos habilitados (IDs): ' . implode(', ', $habilitar), $texto);

        $this->osModelo->guardarEnsayosPermitidos($fixture['os'], [$habilitar[0]], $this->usuarioId);
        $this->assertSame($habilitar, $this->osModelo->obtenerIdsPermitidos($fixture['os']));
    }

    public function testOrdenLegadaSinFilasPermiteTodosLosEnsayos(): void {
        $fixture = $this->crearOrdenConEnsayos(['A', 'B', 'C']);
        $this->assertNull($this->osModelo->obtenerIdsPermitidos($fixture['os']));
        foreach ($fixture['detalles'] as $idDetalle) {
            $this->assertTrue($this->osModelo->esEnsayoPermitido($fixture['os'], $idDetalle));
        }

        $items = $this->opModelo->obtenerItemsOS($fixture['os']);
        $this->assertCount(3, $items);
        $os = $this->osModelo->obtenerPorId($fixture['os']);
        $this->assertCount(3, $os['ensayos'] ?? []);

        $recibido = $this->opModelo->registrarRecepcion($this->payloadRecepcion($fixture['os'], $fixture['detalles'][0]));
        $this->assertTrue($recibido, (string)$this->opModelo->obtenerUltimoErrorRecepcion());
        $this->assertSame(1, $this->contarRecepciones($fixture['os']));
    }

    public function testRechazaIdAjenoSinAprobarNiInsertarWhitelist(): void {
        $fixture = $this->crearOrdenConEnsayos(['Local']);
        $ajena = $this->crearOrdenConEnsayos(['Ajena']);
        $estadoAntes = $this->estadoOs($fixture['os']);

        $this->aprobar($fixture['os'], [$fixture['detalles'][0], $ajena['detalles'][0]], 1);

        $this->assertStringContainsString('no pertenecen', (string)($_SESSION['error'] ?? ''));
        $this->assertSame($estadoAntes, $this->estadoOs($fixture['os']));
        $this->assertNull($this->osModelo->obtenerIdsPermitidos($fixture['os']));
        $this->assertSame(0, $this->contarWhitelist($fixture['os']));
        $this->assertSame(0, $this->contarWhitelist($ajena['os']));
    }

    public function testRecepcionRechazaEnsayoNoHabilitado(): void {
        $fixture = $this->crearOrdenConEnsayos(['Habilitado', 'Bloqueado']);
        $this->aprobar($fixture['os'], [$fixture['detalles'][0]], 1);
        $_SESSION['exito'] = null;

        $recibido = $this->opModelo->registrarRecepcion($this->payloadRecepcion($fixture['os'], $fixture['detalles'][1]));
        $this->assertFalse($recibido);
        $this->assertStringContainsString('no está habilitado', (string)$this->opModelo->obtenerUltimoErrorRecepcion());
        $this->assertSame(0, $this->contarRecepciones($fixture['os']));
        $this->assertSame('Aprobada', $this->estadoOs($fixture['os']));

        $permitido = $this->opModelo->registrarRecepcion($this->payloadRecepcion($fixture['os'], $fixture['detalles'][0]));
        $this->assertTrue($permitido, (string)$this->opModelo->obtenerUltimoErrorRecepcion());
        $this->assertSame(1, $this->contarRecepciones($fixture['os']));
    }

    public function testRolDistintoDeAdminOSupervisorNoAprueba(): void {
        $fixture = $this->crearOrdenConEnsayos(['Uno']);
        $this->aprobar($fixture['os'], [$fixture['detalles'][0]], 2);
        $this->assertStringContainsString('permisos', strtolower((string)($_SESSION['error'] ?? '')));
        $this->assertSame('Borrador', $this->estadoOs($fixture['os']));
        $this->assertSame(0, $this->contarWhitelist($fixture['os']));
    }

    public function testCsrfInvalidoNoAprueba(): void {
        $fixture = $this->crearOrdenConEnsayos(['Uno']);
        $this->aprobar($fixture['os'], [$fixture['detalles'][0]], 1, 'token-incorrecto');
        $this->assertStringContainsString('CSRF', (string)($_SESSION['error'] ?? ''));
        $this->assertSame('Borrador', $this->estadoOs($fixture['os']));
        $this->assertSame(0, $this->contarWhitelist($fixture['os']));
    }

    public function testSeleccionVaciaNoAprueba(): void {
        $fixture = $this->crearOrdenConEnsayos(['Uno', 'Dos']);
        $this->aprobar($fixture['os'], [], 1);
        $this->assertStringContainsString('al menos un ensayo', (string)($_SESSION['error'] ?? ''));
        $this->assertSame('Borrador', $this->estadoOs($fixture['os']));
        $this->assertSame(0, $this->contarWhitelist($fixture['os']));
    }

    public function testVistasFiltranPorEnsayoSinReescribirCasillasRtFm13(): void {
        $osIndex = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/OrdenesServicio/Vistas/index.php');
        $operaciones = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/index.php');
        $recepcion = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/Operaciones/Vistas/recepcion.php');
        $hojas = file_get_contents(dirname(__DIR__, 2) . '/app/Modulos/HojasServicio/Vistas/index.php');

        $this->assertNotFalse($osIndex);
        $this->assertStringContainsString('id="aprobar_os_ensayos"', $osIndex);
        $this->assertStringContainsString("chk.name = 'ensayos_permitidos[]'", $osIndex);
        $this->assertStringContainsString('chk.checked = true', $osIndex);
        $this->assertStringContainsString('data-ensayos=', $osIndex);
        $this->assertGreaterThanOrEqual(2, substr_count($osIndex, 'data-ensayos='));

        $this->assertStringContainsString("array_key_exists('permitido'", $operaciones);
        $this->assertStringContainsString('it.permitido !== 0', $operaciones);
        $this->assertStringContainsString("array_key_exists('permitido'", $recepcion);
        $this->assertStringContainsString('ens.permitido === 0', $hojas);
        $this->assertStringContainsString('name="req_resistencia_concreto"', $hojas);
        $this->assertStringContainsString('name="req_granulometria"', $hojas);
        $this->assertStringContainsString('name="req_proctor_sm"', $hojas);
        $this->assertStringContainsString('name="naturaleza_muestra[]"', $hojas);
    }

    /**
     * @param array<int, string> $nombres
     * @return array{os: int, cotizacion: int, cliente: int, detalles: array<int>}
     */
    private function crearOrdenConEnsayos(array $nombres): array {
        $clienteId = $this->insertar('clientes', [
            'nombre_razon_social' => 'Cliente C2 ' . uniqid(),
            'identificacion' => 'J031C2',
            'numero_ruc' => 'J031C2',
            'numero_cedula' => '001C2',
            'contacto_nombre' => 'Ing C2',
            'contacto' => 'Ing C2',
            'direccion' => 'Managua',
            'telefono' => '88880000',
            'email' => 'cliente-c2-' . uniqid('', true) . '@test.local',
            'contacto_correo' => 'cliente-c2@test.local',
            'activo' => 1,
        ]);
        $cotId = $this->insertar('cotizaciones', [
            'codigo' => 'COT-C2-' . uniqid(),
            'id_cliente' => $clienteId,
            'cliente_id' => $clienteId,
            'id_usuario_creador' => $this->usuarioId,
            'id_cliente_factura' => $clienteId,
            'id_cliente_informe' => $clienteId,
            'atencion_a' => 'Ing C2',
            'nombre_proyecto' => 'Proyecto C2',
            'direccion_proyecto' => 'Managua',
            'condicion_pago' => 'Contado',
            'condicion_comercial' => 'contado',
            'estado' => 'Aprobada por Cliente',
            'subtotal' => 100,
            'impuesto' => 0,
            'total' => 100,
            'fecha' => '2026-10-29',
            'folio' => 'COT-C2-' . uniqid(),
        ]);
        $detalles = [];
        foreach ($nombres as $nombre) {
            $detalles[] = $this->insertar('cotizacion_detalles', [
                'id_cotizacion' => $cotId,
                'descripcion_ensayo' => $nombre . "\nlinea C2",
                'cantidad' => 1,
                'precio_unitario' => 10,
                'subtotal' => 10,
                'codigo_servicio' => 'SRV-C2',
                'norma_astm' => 'ASTM C2',
            ]);
        }
        $osId = $this->insertar('ordenes_servicio', [
            'codigo_os' => 'OS-C2-' . uniqid(),
            'codigo' => 'OS-C2-' . uniqid(),
            'id_cotizacion' => $cotId,
            'cotizacion_id' => $cotId,
            'id_cliente' => $clienteId,
            'cliente_id' => $clienteId,
            'id_cliente_factura' => $clienteId,
            'id_cliente_informe' => $clienteId,
            'condicion_comercial' => 'contado',
            'elaborado_por' => 'Supervisor C2',
            'fecha_emision' => '2026-10-29',
            'fecha' => '2026-10-29',
            'atencion_a' => 'Ing C2',
            'nombre_proyecto' => 'Proyecto C2',
            'forma_pago' => 'Contado',
            'tipo_contrato' => 'Puntual',
            'estado' => 'Borrador',
            'requiere_muestreo' => 0,
        ]);

        return [
            'os' => $osId,
            'cotizacion' => $cotId,
            'cliente' => $clienteId,
            'detalles' => $detalles,
        ];
    }

    /**
     * @param array<int> $ids
     */
    private function aprobar(int $idOS, array $ids, int $rol, ?string $csrf = null): void {
        $_SERVER['REQUEST_METHOD'] = 'POST';
        $_SESSION['usuario_id'] = $this->usuarioId;
        $_SESSION['usuario_rol'] = $rol;
        $_SESSION['usuario_nombre'] = 'Supervisor C2';
        $_SESSION['csrf_token'] = 'token-c2-valido';
        $_SESSION['error'] = null;
        $_SESSION['exito'] = null;
        $_POST = [
            'id_os' => (string)$idOS,
            'csrf_token' => $csrf ?? 'token-c2-valido',
            'ensayos_permitidos' => array_map('strval', $ids),
        ];
        $controlador = new OrdenesServicioControlador();
        $controlador->aprobarOS(new Peticion(), new Respuesta());
    }

    /**
     * @return array<string, mixed>
     */
    private function payloadRecepcion(int $idOS, int $idDetalle): array {
        return [
            'id_os' => $idOS,
            'id_detalle_cotizacion' => $idDetalle,
            'recibido_por' => $this->usuarioId,
            'entregado_por' => 'Cliente C2',
            'fecha_recepcion' => '2026-10-29 12:00:00',
            'tipo_muestra' => 'Laboratorio',
            'muestras_recibidas_json' => json_encode([[
                'id_detalle_cotizacion' => $idDetalle,
                'codigo_campo' => 'C2-CAMPO',
                'nombre_lote' => 'Lote C2',
                'fecha_moldeo' => '2026-10-29',
                'edades_dias' => [],
                'edades_identificadores' => [],
            ]]),
        ];
    }

    private function estadoOs(int $idOS): string {
        $stmt = $this->db->prepare('SELECT estado FROM ordenes_servicio WHERE id = :id');
        $stmt->execute(['id' => $idOS]);
        return (string)$stmt->fetchColumn();
    }

    private function contarWhitelist(int $idOS): int {
        if (!$this->osModelo->tablaEnsayosPermitidosDisponible()) {
            return 0;
        }
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM orden_servicio_ensayos_permitidos WHERE id_os = :id');
        $stmt->execute(['id' => $idOS]);
        return (int)$stmt->fetchColumn();
    }

    private function contarRecepciones(int $idOS): int {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM recepcion_muestras WHERE id_os = :id');
        $stmt->execute(['id' => $idOS]);
        return (int)$stmt->fetchColumn();
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function filasDetalles(int $idCotizacion): array {
        $stmt = $this->db->prepare('SELECT id, descripcion_ensayo, cantidad, precio_unitario, subtotal FROM cotizacion_detalles WHERE id_cotizacion = :id ORDER BY id');
        $stmt->execute(['id' => $idCotizacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function ejecutarUp(): void {
        $ruta = dirname(__DIR__, 2) . '/database/migrations/025_orden_servicio_ensayos_permitidos.php';
        require_once $ruta;
        ob_start();
        ejecutarMigracion025($this->db);
        ob_end_clean();
    }

    private function asegurarEsquemaMinimo(): void {
        $this->crearSiNoExiste('usuarios', "CREATE TABLE `usuarios` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_rol` INT(11) NOT NULL DEFAULT 1,
            `nombre` VARCHAR(150) NOT NULL,
            `email` VARCHAR(150) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `activo` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->crearSiNoExiste('clientes', "CREATE TABLE `clientes` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `nombre_razon_social` VARCHAR(255) NOT NULL,
            `identificacion` VARCHAR(50) NULL,
            `numero_ruc` VARCHAR(50) NULL,
            `numero_cedula` VARCHAR(50) NULL,
            `contacto_nombre` VARCHAR(150) NULL,
            `contacto` VARCHAR(150) NULL,
            `direccion` TEXT NULL,
            `telefono` VARCHAR(50) NULL,
            `email` VARCHAR(150) NULL,
            `contacto_correo` VARCHAR(150) NULL,
            `activo` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->crearSiNoExiste('cotizaciones', "CREATE TABLE `cotizaciones` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `codigo` VARCHAR(50) NOT NULL,
            `folio` VARCHAR(50) NULL,
            `id_cliente` INT(11) NOT NULL,
            `cliente_id` INT(11) NULL,
            `id_usuario_creador` INT(11) NULL,
            `id_cliente_factura` INT(11) NULL,
            `id_cliente_informe` INT(11) NULL,
            `atencion_a` VARCHAR(150) NULL,
            `nombre_proyecto` VARCHAR(200) NULL,
            `direccion_proyecto` TEXT NULL,
            `condicion_pago` VARCHAR(100) NULL,
            `condicion_comercial` VARCHAR(50) NULL,
            `estado` VARCHAR(50) NULL,
            `subtotal` DECIMAL(12,2) NULL DEFAULT 0,
            `impuesto` DECIMAL(12,2) NULL DEFAULT 0,
            `total` DECIMAL(12,2) NULL DEFAULT 0,
            `fecha` DATE NULL,
            `version` INT(11) NULL DEFAULT 1,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        $this->asegurarColumna('cotizaciones', 'version', '`version` INT(11) NULL DEFAULT 1');

        $this->crearSiNoExiste('cotizacion_detalles', "CREATE TABLE `cotizacion_detalles` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_cotizacion` INT(11) NOT NULL,
            `id_producto` INT(11) NULL,
            `descripcion_ensayo` TEXT NOT NULL,
            `descripcion_adicional` TEXT NULL,
            `codigo_servicio` VARCHAR(100) NULL,
            `norma_astm` VARCHAR(150) NULL,
            `condiciones_muestra` TEXT NULL,
            `procedimiento` VARCHAR(255) NULL,
            `unidad_medida` VARCHAR(50) NULL,
            `cantidad` DECIMAL(10,2) NOT NULL DEFAULT 1,
            `precio_unitario` DECIMAL(12,2) NOT NULL DEFAULT 0,
            `subtotal` DECIMAL(12,2) NOT NULL DEFAULT 0,
            `resultados_json` TEXT NULL,
            PRIMARY KEY (`id`),
            KEY `id_cotizacion` (`id_cotizacion`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->crearSiNoExiste('ordenes_servicio', "CREATE TABLE `ordenes_servicio` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `codigo_os` VARCHAR(80) NOT NULL,
            `codigo` VARCHAR(80) NULL,
            `id_cotizacion` INT(11) NOT NULL,
            `cotizacion_id` INT(11) NULL,
            `id_cliente` INT(11) NULL,
            `cliente_id` INT(11) NULL,
            `id_cliente_factura` INT(11) NULL,
            `id_cliente_informe` INT(11) NULL,
            `condicion_comercial` VARCHAR(50) NULL,
            `elaborado_por` VARCHAR(150) NULL,
            `fecha_emision` DATE NOT NULL,
            `fecha` DATE NULL,
            `atencion_a` VARCHAR(150) NULL,
            `nombre_proyecto` VARCHAR(200) NULL,
            `forma_pago` VARCHAR(100) NULL,
            `tipo_contrato` VARCHAR(30) NULL,
            `estado` VARCHAR(100) NULL,
            `motivo_observacion` TEXT NULL,
            `requiere_muestreo` TINYINT(1) NULL,
            `tecnico_muestreo` VARCHAR(150) NULL,
            `vehiculo_muestreo` VARCHAR(100) NULL,
            `fecha_muestreo` DATE NULL,
            `hora_muestreo` TIME NULL,
            PRIMARY KEY (`id`),
            UNIQUE KEY `codigo_os` (`codigo_os`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->crearSiNoExiste('productos', "CREATE TABLE `productos` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `formato_id` INT(11) NULL,
            `ensayo_servicio` TEXT NULL,
            `nombre_comercial` VARCHAR(255) NULL,
            `codigo_servicio` VARCHAR(100) NULL,
            `norma_astm` VARCHAR(255) NULL,
            `condiciones_muestra` TEXT NULL,
            `procedimiento_muestreo` VARCHAR(255) NULL,
            `unidad_medida` VARCHAR(50) NULL,
            `codigo_hoja_campo` VARCHAR(100) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->crearSiNoExiste('formatos_ensayos', "CREATE TABLE `formatos_ensayos` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `nombre` VARCHAR(150) NULL,
            `archivo_markdown` VARCHAR(255) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->crearSiNoExiste('tecnicos', "CREATE TABLE `tecnicos` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `nombre` VARCHAR(150) NOT NULL,
            `activo` TINYINT(1) NOT NULL DEFAULT 1,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->crearSiNoExiste('vehiculos', "CREATE TABLE `vehiculos` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `marca` VARCHAR(80) NULL,
            `modelo` VARCHAR(80) NULL,
            `placa` VARCHAR(30) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->crearSiNoExiste('programacion_muestreo', "CREATE TABLE `programacion_muestreo` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_orden_servicio` INT(11) NOT NULL,
            `id_tecnico` INT(11) NULL,
            `id_vehiculo` INT(11) NULL,
            `fecha_ida` DATETIME NULL,
            `fecha_llegada` DATETIME NULL,
            `estado_muestreo` VARCHAR(40) NULL,
            `observaciones_campo` TEXT NULL,
            `lugar_muestreo` VARCHAR(255) NULL,
            `cantidad_muestras_est` VARCHAR(80) NULL,
            `num_muestreadores` INT(11) NULL,
            `fecha_finalizacion` DATETIME NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        foreach ([
            'estado_muestreo' => '`estado_muestreo` VARCHAR(40) NULL',
            'observaciones_campo' => '`observaciones_campo` TEXT NULL',
            'lugar_muestreo' => '`lugar_muestreo` VARCHAR(255) NULL',
            'cantidad_muestras_est' => '`cantidad_muestras_est` VARCHAR(80) NULL',
            'num_muestreadores' => '`num_muestreadores` INT(11) NULL',
            'fecha_finalizacion' => '`fecha_finalizacion` DATETIME NULL',
        ] as $columna => $definicion) {
            $this->asegurarColumna('programacion_muestreo', $columna, $definicion);
        }

        $this->crearSiNoExiste('recepcion_muestras', "CREATE TABLE `recepcion_muestras` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_os` INT(11) NULL,
            `id_hoja` INT(11) NULL,
            `correlativo_anual` INT(11) NULL,
            `anio` INT(11) NULL,
            `tipo_muestra` VARCHAR(30) NULL,
            `codigo_muestra` VARCHAR(50) NULL,
            `id_cilindro` VARCHAR(80) NULL,
            `codigo_campo` VARCHAR(80) NULL,
            `is_qa_qc` TINYINT(1) NULL,
            `replica_codigo` VARCHAR(20) NULL,
            `is_sealed` TINYINT(1) NULL,
            `fecha_recepcion` DATETIME NULL,
            `recibido_por` INT(11) NULL,
            `entregado_por` VARCHAR(150) NULL,
            `observaciones` TEXT NULL,
            `estado` VARCHAR(40) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        foreach ([
            'id_hoja' => '`id_hoja` INT(11) NULL',
            'correlativo_anual' => '`correlativo_anual` INT(11) NULL',
            'anio' => '`anio` INT(11) NULL',
            'tipo_muestra' => '`tipo_muestra` VARCHAR(30) NULL',
            'id_cilindro' => '`id_cilindro` VARCHAR(80) NULL',
            'codigo_campo' => '`codigo_campo` VARCHAR(80) NULL',
            'is_qa_qc' => '`is_qa_qc` TINYINT(1) NULL',
            'replica_codigo' => '`replica_codigo` VARCHAR(20) NULL',
            'is_sealed' => '`is_sealed` TINYINT(1) NULL',
            'fecha_recepcion' => '`fecha_recepcion` DATETIME NULL',
            'recibido_por' => '`recibido_por` INT(11) NULL',
            'entregado_por' => '`entregado_por` VARCHAR(150) NULL',
            'observaciones' => '`observaciones` TEXT NULL',
            'estado' => '`estado` VARCHAR(40) NULL',
        ] as $columna => $definicion) {
            $this->asegurarColumna('recepcion_muestras', $columna, $definicion);
        }

        $this->crearSiNoExiste('lotes_muestras', "CREATE TABLE `lotes_muestras` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_recepcion` INT(11) NULL,
            `nombre_lote` VARCHAR(150) NULL,
            `diseno_resistencia` VARCHAR(80) NULL,
            `fecha_moldeo` DATE NULL,
            `revenimiento_in` DECIMAL(8,2) NULL,
            `revenimiento_cm` DECIMAL(8,2) NULL,
            `temperatura_c` DECIMAL(8,2) NULL,
            `procedimiento_muestreo` VARCHAR(150) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        foreach ([
            'nombre_lote' => '`nombre_lote` VARCHAR(150) NULL',
            'diseno_resistencia' => '`diseno_resistencia` VARCHAR(80) NULL',
            'fecha_moldeo' => '`fecha_moldeo` DATE NULL',
            'revenimiento_in' => '`revenimiento_in` DECIMAL(8,2) NULL',
            'revenimiento_cm' => '`revenimiento_cm` DECIMAL(8,2) NULL',
            'temperatura_c' => '`temperatura_c` DECIMAL(8,2) NULL',
            'procedimiento_muestreo' => '`procedimiento_muestreo` VARCHAR(150) NULL',
        ] as $columna => $definicion) {
            $this->asegurarColumna('lotes_muestras', $columna, $definicion);
        }

        $this->crearSiNoExiste('ensayo_edades', "CREATE TABLE `ensayo_edades` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_lote` INT(11) NULL,
            `id_detalle_cotizacion` INT(11) NULL,
            `identificador_especimen` VARCHAR(80) NULL,
            `edad_dias` INT(11) NULL,
            `fecha_programada` DATE NULL,
            `estado` VARCHAR(40) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        foreach ([
            'identificador_especimen' => '`identificador_especimen` VARCHAR(80) NULL',
            'edad_dias' => '`edad_dias` INT(11) NULL',
            'fecha_programada' => '`fecha_programada` DATE NULL',
            'estado' => '`estado` VARCHAR(40) NULL',
        ] as $columna => $definicion) {
            $this->asegurarColumna('ensayo_edades', $columna, $definicion);
        }

        $this->crearSiNoExiste('secuencias_muestras', "CREATE TABLE `secuencias_muestras` (
            `anio` INT(11) NOT NULL,
            `tipo_muestra` VARCHAR(30) NOT NULL,
            `ultimo_correlativo` INT(11) NOT NULL DEFAULT 0,
            PRIMARY KEY (`anio`, `tipo_muestra`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->crearSiNoExiste('hojas_solicitud', "CREATE TABLE `hojas_solicitud` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_os` INT(11) NULL,
            `codigo_documento` VARCHAR(80) NULL,
            `numero_registro` VARCHAR(40) NULL,
            `muestras_json` TEXT NULL,
            `fecha_creacion` TIMESTAMP NULL DEFAULT CURRENT_TIMESTAMP,
            `fecha_hora_toma_muestra` DATETIME NULL,
            `fecha_hora_llegada_laboratorio` DATETIME NULL,
            `nombre_persona_toma_muestra` VARCHAR(150) NULL,
            `nombre_persona_entrega_muestra` VARCHAR(150) NULL,
            `procedencia_punto_muestreo` VARCHAR(255) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
        foreach ([
            'codigo_documento' => '`codigo_documento` VARCHAR(80) NULL',
            'numero_registro' => '`numero_registro` VARCHAR(40) NULL',
            'fecha_hora_toma_muestra' => '`fecha_hora_toma_muestra` DATETIME NULL',
            'fecha_hora_llegada_laboratorio' => '`fecha_hora_llegada_laboratorio` DATETIME NULL',
            'nombre_persona_toma_muestra' => '`nombre_persona_toma_muestra` VARCHAR(150) NULL',
            'nombre_persona_entrega_muestra' => '`nombre_persona_entrega_muestra` VARCHAR(150) NULL',
            'procedencia_punto_muestreo' => '`procedencia_punto_muestreo` VARCHAR(255) NULL',
        ] as $columna => $definicion) {
            $this->asegurarColumna('hojas_solicitud', $columna, $definicion);
        }

        $this->crearSiNoExiste('hojas_solicitud_detalles', "CREATE TABLE `hojas_solicitud_detalles` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `codigo_muestra` VARCHAR(80) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->crearSiNoExiste('especimenes', "CREATE TABLE `especimenes` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `codigo_lab` VARCHAR(80) NULL,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");

        $this->crearSiNoExiste('bitacora', "CREATE TABLE `bitacora` (
            `id` INT(11) NOT NULL AUTO_INCREMENT,
            `id_usuario` INT(11) NULL,
            `usuario_nombre` VARCHAR(150) NOT NULL,
            `modulo` VARCHAR(100) NOT NULL,
            `accion` VARCHAR(100) NOT NULL,
            `descripcion` TEXT NOT NULL,
            `id_referencia` INT(11) NULL,
            `ip` VARCHAR(45) NOT NULL,
            `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci");
    }

    private function crearSiNoExiste(string $tabla, string $sql): void {
        $existe = $this->db->query('SHOW TABLES LIKE ' . $this->db->quote($tabla))->fetch(PDO::FETCH_NUM);
        if (!$existe) {
            $this->db->exec($sql);
        }
    }

    private function asegurarColumna(string $tabla, string $columna, string $definicion): void {
        $meta = $this->metaColumnas($tabla);
        if (!isset($meta[$columna])) {
            $this->db->exec('ALTER TABLE `' . $tabla . '` ADD COLUMN ' . $definicion);
        }
    }

    /**
     * @param array<string, mixed> $datos
     */
    private function insertar(string $tabla, array $datos): int {
        $meta = $this->metaColumnas($tabla);
        $fila = [];
        foreach ($datos as $columna => $valor) {
            if (isset($meta[$columna])) {
                $fila[$columna] = $valor;
            }
        }
        foreach ($meta as $columna => $info) {
            if ($columna === 'id' || array_key_exists($columna, $fila)) {
                continue;
            }
            $extra = strtolower((string)($info['Extra'] ?? ''));
            $nullable = strtoupper((string)($info['Null'] ?? '')) === 'YES';
            $tieneDefault = array_key_exists('Default', $info) && $info['Default'] !== null;
            if (!$nullable && !$tieneDefault && !str_contains($extra, 'auto_increment')) {
                $fila[$columna] = $this->valorPorTipo((string)$info['Type'], $columna);
            }
        }
        if ($fila === []) {
            $this->fail("No hay columnas insertables en {$tabla}.");
        }
        $columnas = array_keys($fila);
        $marcas = array_map(static fn(string $columna): string => ':' . $columna, $columnas);
        $sql = 'INSERT INTO `' . $tabla . '` (`' . implode('`,`', $columnas) . '`) VALUES (' . implode(',', $marcas) . ')';
        $stmt = $this->db->prepare($sql);
        $params = [];
        foreach ($fila as $columna => $valor) {
            $params[$columna] = $valor;
        }
        $stmt->execute($params);
        $id = (int)$this->db->lastInsertId();
        $this->recordarId($tabla, $id);
        return $id;
    }

    private function recordarId(string $tabla, int $id): void {
        if ($id <= 0) {
            return;
        }
        match ($tabla) {
            'ordenes_servicio' => $this->osIds[] = $id,
            'cotizaciones' => $this->cotIds[] = $id,
            'cotizacion_detalles' => $this->detalleIds[] = $id,
            'clientes' => $this->clienteIds[] = $id,
            'usuarios' => $this->usuarioIds[] = $id,
            default => null,
        };
    }

    /**
     * @param array<int> $ids
     */
    private function borrarIds(string $tabla, string $columna, array $ids): void {
        if ($ids === []) {
            return;
        }
        $existe = $this->db->query('SHOW TABLES LIKE ' . $this->db->quote($tabla))->fetch(PDO::FETCH_NUM);
        if (!$existe) {
            return;
        }
        $ids = array_values(array_unique(array_map('intval', $ids)));
        $marcas = implode(',', array_fill(0, count($ids), '?'));
        $stmt = $this->db->prepare("DELETE FROM `{$tabla}` WHERE `{$columna}` IN ($marcas)");
        $stmt->execute($ids);
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    private function metaColumnas(string $tabla): array {
        $filas = $this->db->query('SHOW COLUMNS FROM `' . str_replace('`', '', $tabla) . '`')->fetchAll(PDO::FETCH_ASSOC);
        $meta = [];
        foreach ($filas as $fila) {
            $meta[$fila['Field']] = $fila;
        }
        return $meta;
    }

    /**
     * @return array<int, string>
     */
    private function nombresColumnas(string $tabla): array {
        return array_keys($this->metaColumnas($tabla));
    }

    private function tipoColumna(string $tabla, string $columna): ?string {
        $meta = $this->metaColumnas($tabla);
        return isset($meta[$columna]['Type']) ? (string)$meta[$columna]['Type'] : null;
    }

    private function valorPorTipo(string $tipo, string $columna): mixed {
        $tipo = strtolower($tipo);
        if (str_starts_with($tipo, 'enum(')) {
            if (preg_match("/enum\\('([^']+)'/i", $tipo, $m)) {
                return $m[1];
            }
            return '';
        }
        if (str_contains($tipo, 'int') || str_contains($tipo, 'decimal') || str_contains($tipo, 'float') || str_contains($tipo, 'double')) {
            return str_contains($columna, 'cliente') || str_contains($columna, 'usuario') ? $this->usuarioId : 0;
        }
        if (str_contains($tipo, 'date') && !str_contains($tipo, 'time')) {
            return '2026-10-29';
        }
        if (str_contains($tipo, 'time') || str_contains($tipo, 'timestamp') || str_contains($tipo, 'datetime')) {
            return '2026-10-29 12:00:00';
        }
        if (str_contains($tipo, 'json')) {
            return '{}';
        }
        return 'c2';
    }
}
