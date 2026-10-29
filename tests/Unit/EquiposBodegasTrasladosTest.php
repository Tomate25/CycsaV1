<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\EquiposBodegas\Modelos\EquiposBodegasModelo;
use Cycsa\Modulos\Proyectos\Modelos\ProyectosModelo;
use PDO;
use InvalidArgumentException;
use Throwable;

/**
 * Sprint C.4: bodegas de equipos, ubicación vigente y traslados trazables (migración 026).
 */
class EquiposBodegasTrasladosTest extends TestCase {

    private PDO $db;
    private EquiposBodegasModelo $modelo;

    protected function setUp(): void {
        parent::setUp();
        $this->db = Conexion::obtenerInstancia();
        $this->db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

        $this->incluirMigracion('008_equipos_calibracion_y_catalogo_hc.php');
        $this->incluirMigracion('018_activos_fijos_depreciacion.php');
        $this->incluirMigracion('020_proyectos_ordenes_obra.php');
        $this->incluirMigracion('026_equipos_bodegas_traslados.php');

        $this->modelo = new EquiposBodegasModelo($this->db);
    }

    public function testTablasSemillaYColumnas(): void {
        foreach (['equipos_bodegas', 'equipos_ubicacion', 'equipos_traslados'] as $tabla) {
            $existe = $this->db->query('SHOW TABLES LIKE ' . $this->db->quote($tabla))->fetchAll(PDO::FETCH_COLUMN);
            $this->assertNotEmpty($existe, "La tabla {$tabla} debe existir.");
        }

        $columnasBodega = $this->columnas('equipos_bodegas');
        foreach (['codigo', 'nombre', 'tipo', 'proyecto_id', 'responsable_id', 'direccion', 'activo', 'created_at', 'updated_at'] as $col) {
            $this->assertContains($col, $columnasBodega);
        }
        $tipo = $this->tipoColumna('equipos_bodegas', 'tipo');
        $this->assertStringContainsString('central', $tipo);
        $this->assertStringContainsString('proyecto', $tipo);
        $this->assertStringContainsString('taller', $tipo);
        $this->assertStringContainsString('externo', $tipo);

        $columnasUbic = $this->columnas('equipos_ubicacion');
        foreach (['equipo_id', 'bodega_id', 'activo_fijo_id', 'estado', 'desde', 'ultimo_traslado_id'] as $col) {
            $this->assertContains($col, $columnasUbic);
        }
        $estado = $this->tipoColumna('equipos_ubicacion', 'estado');
        foreach (['disponible', 'en_uso', 'en_mantenimiento', 'en_calibracion', 'de_baja'] as $valor) {
            $this->assertStringContainsString($valor, $estado);
        }

        $columnasTras = $this->columnas('equipos_traslados');
        foreach (['equipo_id', 'bodega_origen_id', 'bodega_destino_id', 'fecha_traslado', 'usuario_id', 'motivo', 'documento_ref', 'observaciones'] as $col) {
            $this->assertContains($col, $columnasTras);
        }

        $origen = $this->db->query("SHOW COLUMNS FROM equipos_traslados LIKE 'bodega_origen_id'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('YES', $origen['Null']);

        $af = $this->db->query("SHOW COLUMNS FROM equipos_ubicacion LIKE 'activo_fijo_id'")->fetch(PDO::FETCH_ASSOC);
        $this->assertSame('YES', $af['Null']);

        $this->assertSame(0, (int)$this->indice('equipos_bodegas', 'uk_eqbod_codigo')['Non_unique']);
        $this->assertSame(0, (int)$this->indice('equipos_bodegas', 'uk_eqbod_proyecto')['Non_unique']);
        $this->assertSame(0, (int)$this->indice('equipos_ubicacion', 'uk_equbic_equipo')['Non_unique']);

        $lab = $this->bodegaCentral();
        $this->assertNotNull($lab);
        $this->assertSame('central', $lab['tipo']);
        $this->assertSame(1, (int)$lab['activo']);
        $this->assertSame(
            1,
            (int)$this->db->query("SELECT COUNT(*) FROM equipos_bodegas WHERE codigo = 'LAB-CENTRAL'")->fetchColumn()
        );
        $this->assertSame(
            0,
            (int)$this->db->query("SELECT COUNT(*) FROM equipos_bodegas WHERE tipo = 'proyecto' AND codigo LIKE 'OBRA-%' AND nombre LIKE 'Semilla%'")->fetchColumn()
        );
    }

    public function testMigracion026EsIdempotente(): void {
        $antes = $this->conteos();
        $this->incluirMigracion('026_equipos_bodegas_traslados.php');
        $despues = $this->conteos();

        $this->assertSame($antes, $despues);
        $this->assertSame(1, $despues['lab_central']);
    }

    public function testEquiposActivosQuedanEnLabCentralConAlta(): void {
        $nuevo = $this->insertarEquipo(1);
        $inactivo = $this->insertarEquipo(0);
        $this->incluirMigracion('026_equipos_bodegas_traslados.php');

        $faltan = $this->db->query(
            'SELECT e.id
             FROM equipos_calibracion e
             LEFT JOIN equipos_ubicacion u ON u.equipo_id = e.id
             WHERE e.activo = 1 AND u.id IS NULL'
        )->fetchAll(PDO::FETCH_COLUMN);
        $this->assertSame([], $faltan);

        $sinAlta = $this->db->query(
            'SELECT e.id
             FROM equipos_calibracion e
             WHERE e.activo = 1
               AND NOT EXISTS (
                   SELECT 1 FROM equipos_traslados t
                   WHERE t.equipo_id = e.id AND t.bodega_origen_id IS NULL
               )'
        )->fetchAll(PDO::FETCH_COLUMN);
        $this->assertSame([], $sinAlta);

        $ubicacion = $this->modelo->ubicacionActual($nuevo);
        $this->assertNotNull($ubicacion);
        $this->assertSame('LAB-CENTRAL', $ubicacion['bodega_codigo']);
        $this->assertSame('disponible', $ubicacion['estado']);
        $this->assertNull($ubicacion['activo_fijo_id']);

        $historial = $this->modelo->historial($nuevo);
        $this->assertCount(1, $historial);
        $this->assertNull($historial[0]['bodega_origen_id']);
        $this->assertSame((int)$ubicacion['bodega_id'], (int)$historial[0]['bodega_destino_id']);
        $this->assertSame((int)$historial[0]['id'], (int)$ubicacion['ultimo_traslado_id']);

        $this->assertNull($this->modelo->ubicacionActual($inactivo));
    }

    public function testTrasladoActualizaUbicacionEHistorial(): void {
        $equipoId = $this->equipoEnCentral();
        $origen = $this->modelo->ubicacionActual($equipoId);
        $destinoId = $this->modelo->crearBodega([
            'codigo' => 'TALLER-C4-' . bin2hex(random_bytes(3)),
            'nombre' => 'Taller de metrología',
            'tipo' => 'taller',
            'direccion' => 'Planta baja',
            'activo' => 1,
        ]);

        $trasladoId = $this->modelo->trasladar($equipoId, $destinoId, [
            'usuario_id' => 1,
            'motivo' => 'Envío a taller para verificación',
            'documento_ref' => 'VALE-100',
            'observaciones' => 'Con estuche',
            'fecha_traslado' => '2026-11-02 09:15:00',
        ]);

        $actual = $this->modelo->ubicacionActual($equipoId);
        $this->assertSame($destinoId, (int)$actual['bodega_id']);
        $this->assertSame($trasladoId, (int)$actual['ultimo_traslado_id']);
        $this->assertSame('2026-11-02 09:15:00', $actual['desde']);

        $fila = $this->db->prepare('SELECT * FROM equipos_traslados WHERE id = :id');
        $fila->execute(['id' => $trasladoId]);
        $traslado = $fila->fetch(PDO::FETCH_ASSOC);
        $this->assertSame((int)$origen['bodega_id'], (int)$traslado['bodega_origen_id']);
        $this->assertSame($destinoId, (int)$traslado['bodega_destino_id']);
        $this->assertSame(1, (int)$traslado['usuario_id']);
        $this->assertSame('Envío a taller para verificación', $traslado['motivo']);
        $this->assertSame('VALE-100', $traslado['documento_ref']);

        $enTaller = $this->modelo->equiposPorBodega($destinoId);
        $ids = array_map('intval', array_column($enTaller, 'equipo_id'));
        $this->assertContains($equipoId, $ids);
    }

    public function testTrasladoALaMismaBodegaFallaSinFilaNueva(): void {
        $equipoId = $this->equipoEnCentral();
        $antes = $this->modelo->ubicacionActual($equipoId);
        $trasladosAntes = $this->contarTraslados($equipoId);

        try {
            $this->modelo->trasladar($equipoId, (int)$antes['bodega_id'], [
                'motivo' => 'Intento inválido a la misma bodega',
                'usuario_id' => 1,
            ]);
            $this->fail('Trasladar a la misma bodega debe fallar.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('ya se encuentra', $e->getMessage());
        }

        $despues = $this->modelo->ubicacionActual($equipoId);
        $this->assertSame((int)$antes['bodega_id'], (int)$despues['bodega_id']);
        $this->assertSame((int)$antes['ultimo_traslado_id'], (int)$despues['ultimo_traslado_id']);
        $this->assertSame($trasladosAntes, $this->contarTraslados($equipoId));
    }

    public function testTrasladoABodegaInactivaNoDejaParciales(): void {
        $equipoId = $this->equipoEnCentral();
        $antes = $this->modelo->ubicacionActual($equipoId);
        $inactivaId = $this->modelo->crearBodega([
            'codigo' => 'OFF-C4-' . bin2hex(random_bytes(3)),
            'nombre' => 'Bodega cerrada',
            'tipo' => 'externo',
            'activo' => 0,
        ]);
        $trasladosAntes = (int)$this->db->query('SELECT COUNT(*) FROM equipos_traslados')->fetchColumn();

        try {
            $this->modelo->trasladar($equipoId, $inactivaId, [
                'motivo' => 'Intento a bodega inactiva',
                'usuario_id' => 1,
            ]);
            $this->fail('Trasladar a una bodega inactiva debe fallar.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('inactiva', $e->getMessage());
        }

        $despues = $this->modelo->ubicacionActual($equipoId);
        $this->assertSame((int)$antes['bodega_id'], (int)$despues['bodega_id']);
        $this->assertSame((int)$antes['ultimo_traslado_id'], (int)$despues['ultimo_traslado_id']);
        $this->assertSame('disponible', $despues['estado']);
        $this->assertSame($trasladosAntes, (int)$this->db->query('SELECT COUNT(*) FROM equipos_traslados')->fetchColumn());
        $haciaInactiva = $this->db->prepare('SELECT COUNT(*) FROM equipos_traslados WHERE bodega_destino_id = :id');
        $haciaInactiva->execute(['id' => $inactivaId]);
        $this->assertSame(0, (int)$haciaInactiva->fetchColumn());
    }

    public function testTrasladoDeEquipoDeBajaFalla(): void {
        $equipoId = $this->equipoEnCentral();
        $this->db->prepare("UPDATE equipos_ubicacion SET estado = 'de_baja' WHERE equipo_id = :id")
            ->execute(['id' => $equipoId]);
        $antes = $this->modelo->ubicacionActual($equipoId);
        $destinoId = $this->modelo->crearBodega([
            'codigo' => 'EXT-C4-' . bin2hex(random_bytes(3)),
            'nombre' => 'Calibración externa',
            'tipo' => 'externo',
            'activo' => 1,
        ]);
        $trasladosAntes = $this->contarTraslados($equipoId);

        try {
            $this->modelo->trasladar($equipoId, $destinoId, [
                'motivo' => 'No debe salir un equipo de baja',
                'usuario_id' => 1,
            ]);
            $this->fail('Un equipo de baja no se traslada.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('baja', $e->getMessage());
        }

        $despues = $this->modelo->ubicacionActual($equipoId);
        $this->assertSame('de_baja', $despues['estado']);
        $this->assertSame((int)$antes['bodega_id'], (int)$despues['bodega_id']);
        $this->assertSame($trasladosAntes, $this->contarTraslados($equipoId));
        $haciaDestino = $this->db->prepare('SELECT COUNT(*) FROM equipos_traslados WHERE equipo_id = :equipo AND bodega_destino_id = :destino');
        $haciaDestino->execute(['equipo' => $equipoId, 'destino' => $destinoId]);
        $this->assertSame(0, (int)$haciaDestino->fetchColumn());
    }

    public function testBodegaDeProyectoEsUnica(): void {
        $proyectos = new ProyectosModelo($this->db);
        $proyectoId = $proyectos->crear([
            'codigo' => 'PRY-C4-' . bin2hex(random_bytes(3)),
            'nombre' => 'Obra puente C4',
            'ubicacion' => 'Km 12',
            'presupuesto_estimado' => 1000,
            'fecha_inicio' => '2026-11-01',
            'estado' => 'en_ejecucion',
        ]);

        $primera = $this->modelo->obtenerOCrearBodegaProyecto($proyectoId);
        $segunda = $this->modelo->obtenerOCrearBodegaProyecto($proyectoId);
        $this->assertSame((int)$primera['id'], (int)$segunda['id']);
        $this->assertSame('proyecto', $primera['tipo']);
        $this->assertSame($proyectoId, (int)$primera['proyecto_id']);

        try {
            $this->modelo->crearBodega([
                'codigo' => 'OBRA-DUP-' . bin2hex(random_bytes(2)),
                'nombre' => 'Segunda bodega indebida',
                'tipo' => 'proyecto',
                'proyecto_id' => $proyectoId,
            ]);
            $this->fail('No puede haber dos bodegas para el mismo proyecto.');
        } catch (InvalidArgumentException $e) {
            $this->assertStringContainsString('proyecto', mb_strtolower($e->getMessage()));
        }

        $total = $this->db->prepare('SELECT COUNT(*) FROM equipos_bodegas WHERE proyecto_id = :id');
        $total->execute(['id' => $proyectoId]);
        $this->assertSame(1, (int)$total->fetchColumn());

        $enObra = $this->modelo->equiposPorProyecto($proyectoId);
        $this->assertSame([], $enObra);
    }

    public function testHistorialQuedaOrdenadoPorFecha(): void {
        $equipoId = $this->equipoEnCentral();
        $taller = $this->modelo->crearBodega([
            'codigo' => 'HIS-A-' . bin2hex(random_bytes(2)),
            'nombre' => 'Taller historial',
            'tipo' => 'taller',
        ]);
        $obra = $this->modelo->crearBodega([
            'codigo' => 'HIS-B-' . bin2hex(random_bytes(2)),
            'nombre' => 'Patio historial',
            'tipo' => 'externo',
        ]);

        $this->modelo->trasladar($equipoId, $taller, [
            'motivo' => 'Primera salida',
            'fecha_traslado' => '2026-11-05 08:00:00',
            'usuario_id' => 1,
        ]);
        $this->modelo->trasladar($equipoId, $obra, [
            'motivo' => 'Segunda salida',
            'fecha_traslado' => '2026-12-01 16:45:00',
            'usuario_id' => 1,
        ]);

        $historial = $this->modelo->historial($equipoId);
        $this->assertGreaterThanOrEqual(3, count($historial));
        $fechas = array_column($historial, 'fecha_traslado');
        $ordenadas = $fechas;
        sort($ordenadas);
        $this->assertSame($ordenadas, $fechas);
        $this->assertSame('2026-11-05 08:00:00', $historial[count($historial) - 2]['fecha_traslado']);
        $this->assertSame('2026-12-01 16:45:00', $historial[count($historial) - 1]['fecha_traslado']);
        $this->assertSame($taller, (int)$historial[count($historial) - 2]['bodega_destino_id']);
        $this->assertSame($obra, (int)$historial[count($historial) - 1]['bodega_destino_id']);
    }

    public function testPuenteActivoFijoEsNullableYSeLiberaAlBorrar(): void {
        $regla = $this->db->prepare(
            "SELECT DELETE_RULE
             FROM information_schema.REFERENTIAL_CONSTRAINTS
             WHERE CONSTRAINT_SCHEMA = DATABASE()
               AND CONSTRAINT_NAME = 'fk_equbic_activo'"
        );
        $regla->execute();
        $this->assertSame('SET NULL', $regla->fetchColumn());

        $equipoId = $this->equipoEnCentral();
        $inicial = $this->modelo->ubicacionActual($equipoId);
        $this->assertNull($inicial['activo_fijo_id']);

        $codigoAf = 'AF-C4-' . strtoupper(bin2hex(random_bytes(3)));
        $colsAf = $this->columnas('activos_fijos');
        if (in_array('valor_adquisicion', $colsAf, true) && in_array('vida_util_meses', $colsAf, true)) {
            $this->db->prepare(
                'INSERT INTO activos_fijos (codigo, descripcion, fecha_adquisicion, valor_adquisicion, vida_util_meses)
                 VALUES (:codigo, :desc, :fecha, :valor, :vida)'
            )->execute([
                'codigo' => $codigoAf,
                'desc' => 'Prensa vinculada a equipo de calibración',
                'fecha' => '2026-01-15',
                'valor' => 25000,
                'vida' => 60,
            ]);
        } else {
            $this->db->prepare(
                'INSERT INTO activos_fijos (codigo, descripcion, fecha_adquisicion, valor)
                 VALUES (:codigo, :desc, :fecha, :valor)'
            )->execute([
                'codigo' => $codigoAf,
                'desc' => 'Prensa vinculada a equipo de calibración',
                'fecha' => '2026-01-15',
                'valor' => 25000,
            ]);
        }
        $activoId = (int)$this->db->lastInsertId();

        $this->db->prepare('UPDATE equipos_ubicacion SET activo_fijo_id = :af WHERE equipo_id = :eq')
            ->execute(['af' => $activoId, 'eq' => $equipoId]);
        $this->assertSame($activoId, (int)$this->modelo->ubicacionActual($equipoId)['activo_fijo_id']);

        $this->db->prepare('DELETE FROM activos_fijos WHERE id = :id')->execute(['id' => $activoId]);
        $liberada = $this->modelo->ubicacionActual($equipoId);
        $this->assertNotNull($liberada);
        $this->assertNull($liberada['activo_fijo_id']);
    }

    public function testVistasDeBodegasYAnclasDeCalibracionYProyecto(): void {
        $raiz = dirname(__DIR__, 2);
        $bodega = $this->bodegaCentral();
        $equipoId = (int)$this->db->query(
            'SELECT equipo_id FROM equipos_ubicacion ORDER BY id ASC LIMIT 1'
        )->fetchColumn();
        $ubicacion = $this->modelo->ubicacionActual($equipoId);

        $htmlBodegas = $this->renderizarVista($raiz . '/app/Modulos/EquiposBodegas/Vistas/index.php', [
            'bodegas' => $this->modelo->listarBodegas(),
            'filtros' => ['busqueda' => '', 'tipo' => '', 'activo' => ''],
            'csrfToken' => 'token-prueba',
            'tipos' => EquiposBodegasModelo::etiquetasTipo(),
            'subnavActiva' => 'bodegas',
        ]);
        $this->assertStringContainsString('LAB-CENTRAL', $htmlBodegas);
        $this->assertStringContainsString('csrf_token', $htmlBodegas);

        $htmlBodega = $this->renderizarVista($raiz . '/app/Modulos/EquiposBodegas/Vistas/bodega.php', [
            'bodega' => $this->modelo->obtenerBodega((int)$bodega['id']),
            'equipos' => $this->modelo->equiposPorBodega((int)$bodega['id']),
            'csrfToken' => 'token-prueba',
            'estados' => EquiposBodegasModelo::etiquetasEstado(),
            'subnavActiva' => 'bodegas',
        ]);
        $this->assertStringContainsString('Equipos en esta bodega', $htmlBodega);

        $htmlEquipo = $this->renderizarVista($raiz . '/app/Modulos/EquiposBodegas/Vistas/equipo.php', [
            'ubicacion' => $ubicacion,
            'historial' => $this->modelo->historial($equipoId),
            'bodegas' => $this->modelo->listarBodegas(['activo' => 1]),
            'csrfToken' => 'token-prueba',
            'estados' => EquiposBodegasModelo::etiquetasEstado(),
            'subnavActiva' => 'bodegas',
        ]);
        $this->assertStringContainsString('Registrar traslado', $htmlEquipo);
        $this->assertStringContainsString('name="motivo"', $htmlEquipo);

        $calibracion = file_get_contents($raiz . '/app/Modulos/Configuracion/Vistas/equipos_calibracion_index.php');
        $this->assertStringContainsString('>Ubicación<', $calibracion);
        $detalle = file_get_contents($raiz . '/app/Modulos/Proyectos/Vistas/detalle.php');
        $this->assertStringContainsString('Equipos en obra', $detalle);
        $this->assertStringContainsString('id="tab-insumos"', $detalle);
        $this->assertStringContainsString('Insumos', $detalle);
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

    private function insertarEquipo(int $activo): int {
        $codigo = 'EQ-C4-' . bin2hex(random_bytes(4));
        $this->db->prepare(
            'INSERT INTO equipos_calibracion (codigo, nombre, categoria, activo)
             VALUES (:codigo, :nombre, :categoria, :activo)'
        )->execute([
            'codigo' => $codigo,
            'nombre' => 'Equipo de prueba ' . $codigo,
            'categoria' => 'general',
            'activo' => $activo,
        ]);
        return (int)$this->db->lastInsertId();
    }

    private function equipoEnCentral(): int {
        $id = $this->insertarEquipo(1);
        $this->incluirMigracion('026_equipos_bodegas_traslados.php');
        $ubicacion = $this->modelo->ubicacionActual($id);
        $this->assertNotNull($ubicacion);
        $this->assertSame('LAB-CENTRAL', $ubicacion['bodega_codigo']);
        return $id;
    }

    private function bodegaCentral(): ?array {
        $stmt = $this->db->query("SELECT * FROM equipos_bodegas WHERE codigo = 'LAB-CENTRAL' LIMIT 1");
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function conteos(): array {
        return [
            'bodegas' => (int)$this->db->query('SELECT COUNT(*) FROM equipos_bodegas')->fetchColumn(),
            'ubicaciones' => (int)$this->db->query('SELECT COUNT(*) FROM equipos_ubicacion')->fetchColumn(),
            'traslados' => (int)$this->db->query('SELECT COUNT(*) FROM equipos_traslados')->fetchColumn(),
            'lab_central' => (int)$this->db->query("SELECT COUNT(*) FROM equipos_bodegas WHERE codigo = 'LAB-CENTRAL'")->fetchColumn(),
        ];
    }

    private function contarTraslados(int $equipoId): int {
        $stmt = $this->db->prepare('SELECT COUNT(*) FROM equipos_traslados WHERE equipo_id = :id');
        $stmt->execute(['id' => $equipoId]);
        return (int)$stmt->fetchColumn();
    }

    private function columnas(string $tabla): array {
        return $this->db->query("SHOW COLUMNS FROM `{$tabla}`")->fetchAll(PDO::FETCH_COLUMN);
    }

    private function tipoColumna(string $tabla, string $columna): string {
        $stmt = $this->db->query("SHOW COLUMNS FROM `{$tabla}` LIKE " . $this->db->quote($columna));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return (string)($fila['Type'] ?? '');
    }

    private function indice(string $tabla, string $nombre): array {
        $stmt = $this->db->query("SHOW INDEX FROM `{$tabla}` WHERE Key_name = " . $this->db->quote($nombre));
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        $this->assertNotFalse($fila, "Falta el índice {$nombre} en {$tabla}.");
        return $fila;
    }

    private function renderizarVista(string $ruta, array $datos): string {
        extract($datos);
        ob_start();
        try {
            require $ruta;
            return (string)ob_get_clean();
        } catch (Throwable $e) {
            if (ob_get_level() > 0) {
                ob_end_clean();
            }
            throw $e;
        }
    }
}
