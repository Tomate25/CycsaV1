<?php

namespace Cycsa\Modulos\EquiposBodegas\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;
use InvalidArgumentException;
use Throwable;

/**
 * Inventario unificado de equipos de calibración por bodega y traslado trazable.
 * La ubicación vigente es una fila por equipo; el historial es append-only.
 */
class EquiposBodegasModelo extends ModeloBase {

    public const TIPOS = ['central', 'proyecto', 'taller', 'externo'];
    public const ESTADOS = ['disponible', 'en_uso', 'en_mantenimiento', 'en_calibracion', 'de_baja'];

    public function __construct(?PDO $db = null) {
        if ($db !== null) {
            $this->db = $db;
        } else {
            parent::__construct();
        }
    }

    public static function etiquetasTipo(): array {
        return [
            'central' => 'Central',
            'proyecto' => 'Proyecto / obra',
            'taller' => 'Taller',
            'externo' => 'Externo',
        ];
    }

    public static function etiquetasEstado(): array {
        return [
            'disponible' => 'Disponible',
            'en_uso' => 'En uso',
            'en_mantenimiento' => 'En mantenimiento',
            'en_calibracion' => 'En calibración',
            'de_baja' => 'De baja',
        ];
    }

    /**
     * @param array{tipo?:string,activo?:int|string,proyecto_id?:int,busqueda?:string} $filtros
     */
    public function listarBodegas(array $filtros = []): array {
        $sql = 'SELECT b.*,
                       (SELECT COUNT(*) FROM equipos_ubicacion u WHERE u.bodega_id = b.id) AS equipos_count';
        $params = [];

        if ($this->existeTabla('proyectos')) {
            $sql .= ', p.codigo AS proyecto_codigo, p.nombre AS proyecto_nombre
                     FROM equipos_bodegas b
                     LEFT JOIN proyectos p ON p.id = b.proyecto_id';
        } else {
            $sql .= ', NULL AS proyecto_codigo, NULL AS proyecto_nombre
                     FROM equipos_bodegas b';
        }

        $where = [];
        if (!empty($filtros['tipo']) && in_array($filtros['tipo'], self::TIPOS, true)) {
            $where[] = 'b.tipo = :tipo';
            $params['tipo'] = $filtros['tipo'];
        }
        if (isset($filtros['activo']) && $filtros['activo'] !== '') {
            $where[] = 'b.activo = :activo';
            $params['activo'] = (int)$filtros['activo'];
        }
        if (!empty($filtros['proyecto_id'])) {
            $where[] = 'b.proyecto_id = :proyecto_id';
            $params['proyecto_id'] = (int)$filtros['proyecto_id'];
        }
        if (!empty($filtros['busqueda'])) {
            $where[] = '(b.codigo LIKE :q OR b.nombre LIKE :q OR b.direccion LIKE :q)';
            $params['q'] = '%' . trim((string)$filtros['busqueda']) . '%';
        }
        if ($where) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY b.tipo ASC, b.nombre ASC, b.id ASC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerBodega(int $id): ?array {
        if ($id <= 0) {
            return null;
        }
        foreach ($this->listarBodegas() as $bodega) {
            if ((int)$bodega['id'] === $id) {
                return $bodega;
            }
        }
        return null;
    }

    public function buscarBodegaPorProyecto(int $proyectoId): ?array {
        if ($proyectoId <= 0) {
            return null;
        }
        $stmt = $this->db->prepare('SELECT * FROM equipos_bodegas WHERE proyecto_id = :proyecto LIMIT 1');
        $stmt->execute(['proyecto' => $proyectoId]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    /**
     * Crea una bodega. Una bodega de tipo proyecto exige proyecto_id y es única por proyecto.
     */
    public function crearBodega(array $datos): int {
        $codigo = trim((string)($datos['codigo'] ?? ''));
        $nombre = trim((string)($datos['nombre'] ?? ''));
        if ($codigo === '' || $nombre === '') {
            throw new InvalidArgumentException('El código y el nombre de la bodega son obligatorios.');
        }
        if (mb_strlen($codigo) > 50 || mb_strlen($nombre) > 200) {
            throw new InvalidArgumentException('El código o el nombre de la bodega exceden la longitud permitida.');
        }

        $tipo = (string)($datos['tipo'] ?? 'central');
        if (!in_array($tipo, self::TIPOS, true)) {
            throw new InvalidArgumentException('El tipo de bodega no es válido.');
        }

        $proyectoId = !empty($datos['proyecto_id']) ? (int)$datos['proyecto_id'] : null;
        if ($tipo === 'proyecto' && $proyectoId === null) {
            throw new InvalidArgumentException('Una bodega de proyecto requiere un proyecto.');
        }
        if ($proyectoId !== null) {
            $tipo = 'proyecto';
            $existente = $this->buscarBodegaPorProyecto($proyectoId);
            if ($existente) {
                throw new InvalidArgumentException('Ya existe una bodega para ese proyecto.');
            }
        }

        $stmtCod = $this->db->prepare('SELECT id FROM equipos_bodegas WHERE codigo = :codigo LIMIT 1');
        $stmtCod->execute(['codigo' => $codigo]);
        if ($stmtCod->fetch(PDO::FETCH_ASSOC)) {
            throw new InvalidArgumentException('Ya existe una bodega con el código ' . $codigo . '.');
        }

        $responsableId = !empty($datos['responsable_id']) ? (int)$datos['responsable_id'] : null;
        $direccion = $this->textoOpcional($datos['direccion'] ?? null, 255);
        $activo = array_key_exists('activo', $datos) ? ((int)$datos['activo'] === 1 ? 1 : 0) : 1;

        try {
            $stmt = $this->db->prepare(
                'INSERT INTO equipos_bodegas (codigo, nombre, tipo, proyecto_id, responsable_id, direccion, activo)
                 VALUES (:codigo, :nombre, :tipo, :proyecto, :responsable, :direccion, :activo)'
            );
            $stmt->execute([
                'codigo' => $codigo,
                'nombre' => $nombre,
                'tipo' => $tipo,
                'proyecto' => $proyectoId,
                'responsable' => $responsableId,
                'direccion' => $direccion,
                'activo' => $activo,
            ]);
        } catch (Throwable $e) {
            if ($this->esClaveDuplicada($e)) {
                throw new InvalidArgumentException('La bodega duplica un código o un proyecto ya registrado.', 0, $e);
            }
            throw $e;
        }

        return (int)$this->db->lastInsertId();
    }

    /**
     * Devuelve la bodega del proyecto o la crea. El proyecto queda con una sola bodega.
     */
    public function obtenerOCrearBodegaProyecto(int $proyectoId, array $extra = []): array {
        if ($proyectoId <= 0) {
            throw new InvalidArgumentException('El proyecto indicado no es válido.');
        }
        if (!$this->existeTabla('proyectos')) {
            throw new InvalidArgumentException('El catálogo de proyectos no está disponible.');
        }

        $existente = $this->buscarBodegaPorProyecto($proyectoId);
        if ($existente) {
            return $this->obtenerBodega((int)$existente['id']) ?? $existente;
        }

        $stmt = $this->db->prepare('SELECT id, codigo, nombre, ubicacion FROM proyectos WHERE id = :id LIMIT 1');
        $stmt->execute(['id' => $proyectoId]);
        $proyecto = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$proyecto) {
            throw new InvalidArgumentException('El proyecto indicado no existe.');
        }

        $codigoBase = 'OBRA-' . preg_replace('/[^A-Za-z0-9_-]/', '', (string)$proyecto['codigo']);
        if ($codigoBase === 'OBRA-') {
            $codigoBase = 'OBRA-' . $proyectoId;
        }
        $stmtCod = $this->db->prepare('SELECT id, proyecto_id FROM equipos_bodegas WHERE codigo = :codigo LIMIT 1');
        $stmtCod->execute(['codigo' => $codigoBase]);
        $codigoOcupado = $stmtCod->fetch(PDO::FETCH_ASSOC);
        if ($codigoOcupado && (int)($codigoOcupado['proyecto_id'] ?? 0) !== $proyectoId) {
            $codigoBase .= '-' . $proyectoId;
        }

        $nombre = trim((string)($extra['nombre'] ?? ''));
        if ($nombre === '') {
            $nombre = 'Bodega de obra ' . (string)$proyecto['nombre'];
        }
        $nombre = mb_substr($nombre, 0, 200);

        $direccion = $extra['direccion'] ?? ($proyecto['ubicacion'] ?? null);

        try {
            $id = $this->crearBodega([
                'codigo' => $codigoBase,
                'nombre' => $nombre,
                'tipo' => 'proyecto',
                'proyecto_id' => $proyectoId,
                'responsable_id' => $extra['responsable_id'] ?? null,
                'direccion' => $direccion,
                'activo' => 1,
            ]);
        } catch (InvalidArgumentException $e) {
            $otra = $this->buscarBodegaPorProyecto($proyectoId);
            if ($otra) {
                return $this->obtenerBodega((int)$otra['id']) ?? $otra;
            }
            throw $e;
        }

        $creada = $this->obtenerBodega($id);
        if (!$creada) {
            throw new InvalidArgumentException('No se pudo leer la bodega de proyecto recién creada.');
        }
        return $creada;
    }

    public function ubicacionActual(int $equipoId): ?array {
        if ($equipoId <= 0) {
            return null;
        }
        $stmt = $this->db->prepare(
            'SELECT u.*,
                    e.codigo AS equipo_codigo,
                    e.nombre AS equipo_nombre,
                    e.categoria AS equipo_categoria,
                    e.activo AS equipo_activo,
                    b.codigo AS bodega_codigo,
                    b.nombre AS bodega_nombre,
                    b.tipo AS bodega_tipo,
                    b.activo AS bodega_activa,
                    b.proyecto_id
             FROM equipos_ubicacion u
             INNER JOIN equipos_calibracion e ON e.id = u.equipo_id
             INNER JOIN equipos_bodegas b ON b.id = u.bodega_id
             WHERE u.equipo_id = :equipo
             LIMIT 1'
        );
        $stmt->execute(['equipo' => $equipoId]);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    /**
     * Mapa equipo_id => ubicación, para el listado de calibración.
     */
    public function mapaUbicacionPorEquipo(): array {
        $stmt = $this->db->query(
            'SELECT u.equipo_id, u.estado, u.desde, u.bodega_id,
                    b.codigo AS bodega_codigo, b.nombre AS bodega_nombre, b.tipo AS bodega_tipo
             FROM equipos_ubicacion u
             INNER JOIN equipos_bodegas b ON b.id = u.bodega_id'
        );
        $mapa = [];
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $fila) {
            $mapa[(int)$fila['equipo_id']] = $fila;
        }
        return $mapa;
    }

    public function equiposPorBodega(int $bodegaId): array {
        if ($bodegaId <= 0) {
            return [];
        }
        $stmt = $this->db->prepare(
            'SELECT u.*,
                    e.codigo AS equipo_codigo,
                    e.nombre AS equipo_nombre,
                    e.categoria AS equipo_categoria,
                    e.activo AS equipo_activo,
                    b.codigo AS bodega_codigo,
                    b.nombre AS bodega_nombre
             FROM equipos_ubicacion u
             INNER JOIN equipos_calibracion e ON e.id = u.equipo_id
             INNER JOIN equipos_bodegas b ON b.id = u.bodega_id
             WHERE u.bodega_id = :bodega
             ORDER BY e.codigo ASC, e.id ASC'
        );
        $stmt->execute(['bodega' => $bodegaId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function equiposPorProyecto(int $proyectoId): array {
        $bodega = $this->buscarBodegaPorProyecto($proyectoId);
        if (!$bodega) {
            return [];
        }
        return $this->equiposPorBodega((int)$bodega['id']);
    }

    /**
     * Historial append-only ordenado por fecha de traslado (cronológico).
     */
    public function historial(int $equipoId): array {
        if ($equipoId <= 0) {
            return [];
        }

        $usuarioSelect = 'NULL AS usuario_nombre';
        $usuarioJoin = '';
        if ($this->existeTabla('usuarios')) {
            $usuarioSelect = 'us.nombre AS usuario_nombre';
            $usuarioJoin = 'LEFT JOIN usuarios us ON us.id = t.usuario_id';
        }

        $sql = "SELECT t.*,
                       bo.codigo AS origen_codigo,
                       bo.nombre AS origen_nombre,
                       bd.codigo AS destino_codigo,
                       bd.nombre AS destino_nombre,
                       {$usuarioSelect}
                FROM equipos_traslados t
                LEFT JOIN equipos_bodegas bo ON bo.id = t.bodega_origen_id
                INNER JOIN equipos_bodegas bd ON bd.id = t.bodega_destino_id
                {$usuarioJoin}
                WHERE t.equipo_id = :equipo
                ORDER BY t.fecha_traslado ASC, t.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['equipo' => $equipoId]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Traslado atómico. Rechaza la misma bodega, destino inactivo y equipos de baja
     * sin dejar filas parciales.
     *
     * @param array{usuario_id?:int,motivo?:string,documento_ref?:string,observaciones?:string,fecha_traslado?:string} $meta
     */
    public function trasladar(int $equipoId, int $bodegaDestinoId, array $meta = []): int {
        if ($equipoId <= 0 || $bodegaDestinoId <= 0) {
            throw new InvalidArgumentException('Equipo o bodega de destino no válidos.');
        }

        $motivo = trim((string)($meta['motivo'] ?? ''));
        if ($motivo === '') {
            throw new InvalidArgumentException('El motivo del traslado es obligatorio.');
        }
        if (mb_strlen($motivo) > 255) {
            throw new InvalidArgumentException('El motivo del traslado excede la longitud permitida.');
        }

        $fecha = $this->normalizarFecha($meta['fecha_traslado'] ?? null);
        $usuarioId = !empty($meta['usuario_id']) ? (int)$meta['usuario_id'] : null;
        $documento = $this->textoOpcional($meta['documento_ref'] ?? null, 120);
        $observaciones = $this->textoOpcional($meta['observaciones'] ?? null, 4000);

        $propia = !$this->db->inTransaction();
        if ($propia) {
            $this->db->beginTransaction();
        }

        try {
            $ubicacion = $this->bloquearFila(
                'SELECT * FROM equipos_ubicacion WHERE equipo_id = :id LIMIT 1 FOR UPDATE',
                ['id' => $equipoId]
            );
            if (!$ubicacion) {
                throw new InvalidArgumentException('El equipo no tiene una ubicación registrada.');
            }

            $destino = $this->bloquearFila(
                'SELECT * FROM equipos_bodegas WHERE id = :id LIMIT 1 FOR UPDATE',
                ['id' => $bodegaDestinoId]
            );
            if (!$destino) {
                throw new InvalidArgumentException('La bodega de destino no existe.');
            }
            if ((int)($destino['activo'] ?? 0) !== 1) {
                throw new InvalidArgumentException('No se puede trasladar a una bodega inactiva.');
            }
            if ((int)$ubicacion['bodega_id'] === (int)$destino['id']) {
                throw new InvalidArgumentException('El equipo ya se encuentra en la bodega de destino.');
            }
            if (($ubicacion['estado'] ?? '') === 'de_baja') {
                throw new InvalidArgumentException('No se puede trasladar un equipo dado de baja.');
            }

            $ins = $this->db->prepare(
                'INSERT INTO equipos_traslados (
                    equipo_id, bodega_origen_id, bodega_destino_id, fecha_traslado,
                    usuario_id, motivo, documento_ref, observaciones
                 ) VALUES (
                    :equipo, :origen, :destino, :fecha, :usuario, :motivo, :documento, :observaciones
                 )'
            );
            $ins->execute([
                'equipo' => $equipoId,
                'origen' => (int)$ubicacion['bodega_id'],
                'destino' => (int)$destino['id'],
                'fecha' => $fecha,
                'usuario' => $usuarioId,
                'motivo' => $motivo,
                'documento' => $documento,
                'observaciones' => $observaciones,
            ]);
            $trasladoId = (int)$this->db->lastInsertId();
            if ($trasladoId <= 0) {
                throw new InvalidArgumentException('No se pudo registrar el traslado.');
            }

            $upd = $this->db->prepare(
                'UPDATE equipos_ubicacion
                 SET bodega_id = :bodega, desde = :desde, ultimo_traslado_id = :traslado
                 WHERE equipo_id = :equipo'
            );
            $upd->execute([
                'bodega' => (int)$destino['id'],
                'desde' => $fecha,
                'traslado' => $trasladoId,
                'equipo' => $equipoId,
            ]);
            if ($upd->rowCount() !== 1) {
                throw new InvalidArgumentException('No se pudo actualizar la ubicación del equipo.');
            }

            if ($propia) {
                $this->db->commit();
            }
            return $trasladoId;
        } catch (Throwable $e) {
            if ($propia && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    private function bloquearFila(string $sql, array $params): ?array {
        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $fila = $stmt->fetch(PDO::FETCH_ASSOC);
        return $fila ?: null;
    }

    private function normalizarFecha(mixed $valor): string {
        if ($valor === null || trim((string)$valor) === '') {
            return date('Y-m-d H:i:s');
        }
        $texto = str_replace('T', ' ', trim((string)$valor));
        $dt = \DateTime::createFromFormat('Y-m-d H:i:s', $texto)
            ?: \DateTime::createFromFormat('Y-m-d H:i', $texto)
            ?: \DateTime::createFromFormat('Y-m-d', $texto);
        if (!$dt) {
            throw new InvalidArgumentException('La fecha del traslado no es válida.');
        }
        if (strlen($texto) <= 10) {
            $dt->setTime(0, 0, 0);
        }
        return $dt->format('Y-m-d H:i:s');
    }

    private function textoOpcional(mixed $valor, int $max): ?string {
        $texto = trim((string)($valor ?? ''));
        if ($texto === '') {
            return null;
        }
        return mb_substr($texto, 0, $max);
    }

    private function existeTabla(string $tabla): bool {
        $permitidas = ['proyectos', 'activos_fijos', 'usuarios', 'equipos_calibracion', 'equipos_bodegas'];
        if (!in_array($tabla, $permitidas, true)) {
            return false;
        }
        try {
            $stmt = $this->db->query('SHOW TABLES LIKE ' . $this->db->quote($tabla));
            return (bool)$stmt->fetch(PDO::FETCH_NUM);
        } catch (Throwable $e) {
            return false;
        }
    }

    private function esClaveDuplicada(Throwable $e): bool {
        $codigo = (string)$e->getCode();
        if ($codigo === '23000') {
            return true;
        }
        $mensaje = $e->getMessage();
        return str_contains($mensaje, '1062') || str_contains($mensaje, 'Duplicate');
    }
}
