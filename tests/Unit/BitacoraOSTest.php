<?php

namespace Tests\Unit;

use Cycsa\Nucleo\Conexion;
use PDO;
use PHPUnit\Framework\TestCase;
use Throwable;

/**
 * Sprint C.5: bitácora cronológica de O/S, matriz y RT-FM-13 (migración 027).
 */
class BitacoraOSTest extends TestCase {

    private PDO $db;
    private int $idOS;
    private array $sessionPrevia = [];

    protected function setUp(): void {
        parent::setUp();
        $this->sessionPrevia = $_SESSION ?? [];
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $this->asegurarTablaBitacora();
        $this->incluirMigracion('027_bitacora_entidades.php');

        $this->idOS = 910000 + random_int(1, 80000);
        $_SESSION['usuario_id'] = null;
        $_SESSION['usuario_nombre'] = 'C5 Auditor';
        $_SESSION['usuario_rol'] = 3;
        $_SESSION['usuario_permisos'] = [];
        $this->limpiar();
    }

    protected function tearDown(): void {
        $this->limpiar();
        $_SESSION = $this->sessionPrevia;
        parent::tearDown();
    }

    public function testMigracion027AgregaColumnasEIndicesYEsIdempotente(): void {
        foreach (['entidad_tipo', 'entidad_id', 'id_os', 'usuario_rol', 'datos_json'] as $columna) {
            $this->assertContains($columna, $this->columnas());
            $meta = $this->db->query('SHOW COLUMNS FROM bitacora LIKE ' . $this->db->quote($columna))->fetch(PDO::FETCH_ASSOC);
            $this->assertSame('YES', $meta['Null'], $columna . ' debe ser nullable.');
        }
        $this->assertStringContainsString('varchar(40)', strtolower($this->tipo('entidad_tipo')));
        $this->assertStringContainsString('varchar(50)', strtolower($this->tipo('usuario_rol')));
        $this->assertStringContainsString('text', strtolower($this->tipo('datos_json')));

        foreach (['idx_bitacora_id_os', 'idx_bitacora_entidad', 'idx_bitacora_fecha'] as $indice) {
            $fila = $this->db->query('SHOW INDEX FROM bitacora WHERE Key_name = ' . $this->db->quote($indice))->fetch(PDO::FETCH_ASSOC);
            $this->assertNotFalse($fila, 'Falta el índice ' . $indice);
        }

        $antes = $this->conteoColumnasEntidad();
        $filasAntes = (int)$this->db->query('SELECT COUNT(*) FROM bitacora')->fetchColumn();
        $this->incluirMigracion('027_bitacora_entidades.php');
        $this->assertSame($antes, $this->conteoColumnasEntidad());
        $this->assertSame($filasAntes, (int)$this->db->query('SELECT COUNT(*) FROM bitacora')->fetchColumn());
    }

    public function testSinContextoConservaColumnasLegacy(): void {
        $ok = registrarBitacora('operaciones', 'cambiar_estado', $this->marca('legacy'), $this->idOS);
        $this->assertTrue($ok);

        $fila = $this->ultima('cambiar_estado');
        $this->assertSame('operaciones', $fila['modulo']);
        $this->assertSame('cambiar_estado', $fila['accion']);
        $this->assertSame($this->idOS, (int)$fila['id_referencia']);
        $this->assertSame('C5 Auditor', $fila['usuario_nombre']);
        $this->assertNull($fila['entidad_tipo']);
        $this->assertNull($fila['entidad_id']);
        $this->assertNull($fila['id_os']);
        $this->assertNull($fila['usuario_rol']);
        $this->assertNull($fila['datos_json']);
    }

    public function testConContextoEscribeEntidadRolYDatos(): void {
        $ok = registrarBitacora('operaciones', 'guardar_matriz', $this->marca('ctx'), 555, [
            'entidad_tipo' => 'matriz',
            'entidad_id' => 555,
            'id_os' => $this->idOS,
            'datos' => ['version' => 2],
        ]);
        $this->assertTrue($ok);

        $fila = $this->ultima('guardar_matriz');
        $this->assertSame(555, (int)$fila['id_referencia']);
        $this->assertSame('matriz', $fila['entidad_tipo']);
        $this->assertSame(555, (int)$fila['entidad_id']);
        $this->assertSame($this->idOS, (int)$fila['id_os']);
        $this->assertSame('3', (string)$fila['usuario_rol']);
        $datos = json_decode((string)$fila['datos_json'], true);
        $this->assertSame(2, $datos['version']);

        registrarEventoOS('hojas_servicio', 'congelar_plantillas', $this->marca('rtfm'), $this->idOS, [
            'entidad_tipo' => 'rtfm13',
            'entidad_id' => 77,
            'id_referencia' => 77,
            'usuario_rol' => 'Supervisor',
        ]);
        $hoja = $this->ultima('congelar_plantillas');
        $this->assertSame('rtfm13', $hoja['entidad_tipo']);
        $this->assertSame(77, (int)$hoja['entidad_id']);
        $this->assertSame($this->idOS, (int)$hoja['id_os']);
        $this->assertSame(77, (int)$hoja['id_referencia']);
        $this->assertSame('Supervisor', $hoja['usuario_rol']);
    }

    public function testObtenerBitacoraOSAgrupaLegadoYExcluyeIdsAmbiguos(): void {
        registrarBitacora('ordenes_servicio', 'crear', $this->marca('crear'), $this->idOS);
        registrarBitacora('operaciones', 'guardar_matriz', $this->marca('matriz-ambigua'), $this->idOS);
        registrarBitacora('hojas_servicio', 'congelar_plantillas', $this->marca('congelar-ambiguo'), $this->idOS);
        registrarBitacora('hojas_servicio', 'cambio_plantilla', $this->marca('plantilla-ambigua'), $this->idOS);
        registrarBitacora('laboratorio', 'modificar_plan_rotura', $this->marca('rotura'), $this->idOS);
        registrarBitacora('operaciones', 'aprobar_matriz', $this->marca('aprobar'), 880, [
            'entidad_tipo' => 'matriz',
            'entidad_id' => 880,
            'id_os' => $this->idOS,
        ]);
        registrarBitacora('operaciones', 'cambiar_estado', $this->marca('otra-os'), $this->idOS + 1, [
            'entidad_tipo' => 'os',
            'entidad_id' => $this->idOS + 1,
            'id_os' => $this->idOS + 1,
        ]);

        $logs = $this->soloPropios(obtenerBitacoraOS($this->idOS));
        $acciones = array_column($logs, 'accion');

        $this->assertSame(['crear', 'aprobar_matriz'], $acciones);
        $this->assertNotContains('guardar_matriz', $acciones);
        $this->assertNotContains('congelar_plantillas', $acciones);
        $this->assertNotContains('cambio_plantilla', $acciones);
        $this->assertNotContains('modificar_plan_rotura', $acciones);
        $this->assertNotContains('cambiar_estado', $acciones);

        $aprobar = $logs[1];
        $this->assertSame('matriz', $aprobar['entidad_tipo']);
        $this->assertSame(880, (int)$aprobar['entidad_id']);
        $this->assertSame($this->idOS, (int)$aprobar['id_os']);
        $this->assertSame('3', (string)$aprobar['usuario_rol']);
        $this->assertNotEmpty($aprobar['fecha_creacion']);
    }

    public function testFiltrosDeUsuarioFechaEntidadYAccion(): void {
        registrarBitacora('operaciones', 'aprobar_matriz', $this->marca('filtro-a'), 1, [
            'entidad_tipo' => 'matriz',
            'entidad_id' => 41,
            'id_os' => $this->idOS,
        ]);
        registrarBitacora('operaciones', 'cerrar_orden', $this->marca('filtro-b'), $this->idOS, [
            'entidad_tipo' => 'os',
            'entidad_id' => $this->idOS,
            'id_os' => $this->idOS,
        ]);

        $matriz = $this->ultima('aprobar_matriz');
        $cierre = $this->ultima('cerrar_orden');
        $this->fijarFecha((int)$matriz['id'], '2026-03-01 08:00:00');
        $this->fijarFecha((int)$cierre['id'], '2026-06-15 18:00:00');

        $porAccion = $this->soloPropios(obtenerBitacoraOS($this->idOS, 50, ['accion' => 'cerrar_orden']));
        $this->assertCount(1, $porAccion);
        $this->assertSame('cerrar_orden', $porAccion[0]['accion']);

        $porEntidad = $this->soloPropios(obtenerBitacoraOS($this->idOS, 50, [
            'entidad_tipo' => 'matriz',
            'entidad_id' => 41,
        ]));
        $this->assertCount(1, $porEntidad);
        $this->assertSame('aprobar_matriz', $porEntidad[0]['accion']);

        $porUsuario = $this->soloPropios(obtenerBitacoraOS($this->idOS, 50, ['usuario' => 'C5 Auditor']));
        $this->assertCount(2, $porUsuario);

        $porFecha = $this->soloPropios(obtenerBitacoraOS($this->idOS, 50, [
            'desde' => '2026-06-01',
            'hasta' => '2026-06-30',
        ]));
        $this->assertCount(1, $porFecha);
        $this->assertSame('cerrar_orden', $porFecha[0]['accion']);
    }

    public function testHistorialJsonDeMatrizSeAnexaSinReemplazarLaFuente(): void {
        $json = json_encode([
            'revision' => [
                'historial' => [[
                    'accion' => 'aprobada',
                    'fecha' => '2026-04-02 11:30:00',
                    'usuario' => 'Supervisor JSON',
                    'usuario_id' => 9,
                    'nota' => 'Conforme',
                ]],
            ],
            'filas' => [['muestra' => 'M-1']],
        ], JSON_UNESCAPED_UNICODE);

        $filas = bitacoraFilasDesdeHistorialMatriz($this->idOS, 88, 'Compresión', $json);
        $this->assertCount(1, $filas);
        $this->assertSame('json', $filas[0]['fuente']);
        $this->assertSame('matriz', $filas[0]['entidad_tipo']);
        $this->assertSame(88, $filas[0]['entidad_id']);
        $this->assertSame($this->idOS, $filas[0]['id_os']);
        $this->assertSame('aprobada', $filas[0]['accion']);
        $this->assertStringContainsString('Conforme', $filas[0]['descripcion']);
        $this->assertStringContainsString('M-1', $json);
    }

    public function testPermisoDeBitacoraYRutaSoloLectura(): void {
        $_SESSION['usuario_rol'] = 4;
        $_SESSION['usuario_permisos'] = [];
        $this->assertFalse(puedeVerBitacoraOS());

        $_SESSION['usuario_rol'] = 6;
        $_SESSION['usuario_permisos'] = ['bitacora' => ['ver' => 1]];
        $this->assertTrue(puedeVerBitacoraOS());

        foreach ([1, 2, 3] as $rol) {
            $_SESSION['usuario_rol'] = $rol;
            $_SESSION['usuario_permisos'] = [];
            $this->assertTrue(puedeVerBitacoraOS(), 'El rol ' . $rol . ' consulta la bitácora de la O/S.');
        }

        $rutas = (string)file_get_contents(dirname(__DIR__, 2) . '/rutas/web.php');
        $this->assertMatchesRegularExpression(
            "#enrutador->get\\('/ordenes-servicio/bitacora', \\[OrdenesServicioControlador::class, 'bitacora'\\], \\[AuthMiddleware::class\\]\\)#",
            $rutas
        );
        $this->assertDoesNotMatchRegularExpression(
            "#enrutador->(post|put|delete|patch)\\(\\s*'[^']*bitacora#i",
            $rutas
        );

        $app = (string)file_get_contents(dirname(__DIR__, 2) . '/app/Helpers/funciones.php');
        $this->assertDoesNotMatchRegularExpression('/(UPDATE|DELETE\\s+FROM)\\s+`?bitacora`?/i', $app);

        $controladores = dirname(__DIR__, 2) . '/app/Modulos';
        $iterator = new \RecursiveIteratorIterator(new \RecursiveDirectoryIterator($controladores));
        foreach ($iterator as $archivo) {
            if (!$archivo->isFile() || substr($archivo->getFilename(), -4) !== '.php') {
                continue;
            }
            $codigo = (string)file_get_contents($archivo->getPathname());
            $this->assertDoesNotMatchRegularExpression(
                '/(UPDATE|DELETE\\s+FROM)\\s+`?bitacora`?/i',
                $codigo,
                $archivo->getPathname()
            );
        }
    }

    public function testParcialMuestraEtiquetasDeMatrizYRtFm13(): void {
        $bitacora_logs = [[
            'accion' => 'congelar_plantillas',
            'usuario_nombre' => 'C5 Auditor',
            'usuario_rol' => '3',
            'descripcion' => 'Plantillas congeladas',
            'fecha_creacion' => '2026-10-29 12:00:00',
            'ip' => '127.0.0.1',
            'entidad_tipo' => 'rtfm13',
            'entidad_id' => 12,
            'id_os' => $this->idOS,
        ], [
            'accion' => 'guardar_matriz',
            'usuario_nombre' => 'C5 Auditor',
            'usuario_rol' => '3',
            'descripcion' => 'Matriz guardada',
            'fecha_creacion' => '2026-10-29 12:05:00',
            'ip' => '127.0.0.1',
            'entidad_tipo' => 'matriz',
            'entidad_id' => 4,
            'fuente' => 'tabla',
        ]];
        $bitacora_modulo_nombre = 'Orden de Servicio';
        $bitacora_limite = 20;
        $bitacora_abierta = true;

        ob_start();
        try {
            require dirname(__DIR__, 2) . '/app/Views/parciales/bitacora_modulo.php';
            $html = (string)ob_get_clean();
        } catch (Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        }

        $this->assertStringContainsString('Congelar Plantillas RT-FM-13', $html);
        $this->assertStringContainsString('Guardar Matriz', $html);
        $this->assertStringContainsString('rtfm13 #12', $html);
        $this->assertStringContainsString('rol 3', $html);
    }

    private function marca(string $sufijo): string {
        return 'C5-TEST-' . $this->idOS . '-' . $sufijo;
    }

    private function limpiar(): void {
        $stmt = $this->db->prepare("DELETE FROM bitacora WHERE descripcion LIKE :marca OR id_os IN (:a, :b) OR id_referencia IN (:c, :d)");
        $stmt->execute([
            'marca' => 'C5-TEST-' . $this->idOS . '-%',
            'a' => $this->idOS,
            'b' => $this->idOS + 1,
            'c' => $this->idOS,
            'd' => $this->idOS + 1,
        ]);
    }

    private function ultima(string $accion): array {
        $stmt = $this->db->prepare(
            "SELECT * FROM bitacora WHERE descripcion LIKE :marca AND accion = :accion ORDER BY id DESC LIMIT 1"
        );
        $stmt->execute([
            'marca' => 'C5-TEST-' . $this->idOS . '-%',
            'accion' => $accion,
        ]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertIsArray($fila);
        return $fila;
    }

    /**
     * @param array<int, array<string, mixed>> $logs
     * @return array<int, array<string, mixed>>
     */
    private function soloPropios(array $logs): array {
        $marca = 'C5-TEST-' . $this->idOS . '-';
        return array_values(array_filter($logs, static function (array $fila) use ($marca): bool {
            return str_starts_with((string)($fila['descripcion'] ?? ''), $marca);
        }));
    }

    private function fijarFecha(int $id, string $fecha): void {
        $stmt = $this->db->prepare('UPDATE bitacora SET fecha_creacion = :fecha WHERE id = :id AND descripcion LIKE :marca');
        $stmt->execute([
            'fecha' => $fecha,
            'id' => $id,
            'marca' => 'C5-TEST-' . $this->idOS . '-%',
        ]);
    }

    private function asegurarTablaBitacora(): void {
        $existe = $this->db->query("SHOW TABLES LIKE 'bitacora'")->fetch(PDO::FETCH_NUM);
        if ($existe) {
            return;
        }
        $this->db->exec(
            "CREATE TABLE `bitacora` (
                `id` INT NOT NULL AUTO_INCREMENT,
                `id_usuario` INT NULL,
                `usuario_nombre` VARCHAR(150) NOT NULL,
                `modulo` VARCHAR(100) NOT NULL,
                `accion` VARCHAR(100) NOT NULL,
                `descripcion` TEXT NOT NULL,
                `id_referencia` INT NULL,
                `ip` VARCHAR(45) NOT NULL,
                `fecha_creacion` TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci"
        );
    }

    private function incluirMigracion(string $archivo): void {
        $db = $this->db;
        $ruta = dirname(__DIR__, 2) . '/database/migrations/' . $archivo;
        $this->assertFileExists($ruta);
        ob_start();
        try {
            include $ruta;
        } catch (Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        }
        if (ob_get_level() > 0) {
            ob_end_clean();
        }
    }

    /**
     * @return array<int, string>
     */
    private function columnas(): array {
        return $this->db->query('SHOW COLUMNS FROM bitacora')->fetchAll(PDO::FETCH_COLUMN);
    }

    private function tipo(string $columna): string {
        $fila = $this->db->query('SHOW COLUMNS FROM bitacora LIKE ' . $this->db->quote($columna))->fetch(PDO::FETCH_ASSOC);
        return (string)($fila['Type'] ?? '');
    }

    private function conteoColumnasEntidad(): int {
        $stmt = $this->db->query(
            "SELECT COUNT(*) FROM information_schema.COLUMNS
             WHERE TABLE_SCHEMA = DATABASE()
               AND TABLE_NAME = 'bitacora'
               AND COLUMN_NAME IN ('entidad_tipo','entidad_id','id_os','usuario_rol','datos_json')"
        );
        return (int)$stmt->fetchColumn();
    }
}
