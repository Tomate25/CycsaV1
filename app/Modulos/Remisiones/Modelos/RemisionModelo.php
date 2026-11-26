<?php

declare(strict_types=1);

namespace Cycsa\Modulos\Remisiones\Modelos;

use Cycsa\Nucleo\ModeloBase;
use InvalidArgumentException;
use PDO;
use RuntimeException;
use Throwable;

/**
 * Modelo para gestión de Remisiones de Entrega de Informes (CYCSA-RG-FM-41 / RLAB-MM-AAAA-N).
 */
class RemisionModelo extends ModeloBase
{
    /**
     * Genera el siguiente código correlativo mensual en formato exacto RLAB-MM-AAAA-N
     * (ej. RLAB-11-2026-1, RLAB-11-2026-2), evitando colisiones.
     */
    public function generarCodigoRemision(?string $fecha = null): string
    {
        $timestamp = time();
        if ($fecha !== null && trim($fecha) !== '') {
            $parsed = strtotime($fecha);
            if ($parsed !== false) {
                $timestamp = $parsed;
            }
        }

        $mes = date('m', $timestamp);
        $anio = date('Y', $timestamp);
        $prefijo = sprintf('RLAB-%s-%s-', $mes, $anio);

        $stmt = $this->db->prepare(
            "SELECT codigo_remision
             FROM remisiones_informes
             WHERE codigo_remision LIKE :prefijo"
        );
        $stmt->execute(['prefijo' => $prefijo . '%']);
        $codigos = $stmt->fetchAll(PDO::FETCH_COLUMN);

        $maximo = 0;
        $patron = '/^RLAB-' . preg_quote($mes, '/') . '-' . preg_quote($anio, '/') . '-(\d+)$/';
        foreach ($codigos as $codigo) {
            if (is_string($codigo) && preg_match($patron, trim($codigo), $matches) === 1) {
                $numero = (int)$matches[1];
                if ($numero > $maximo) {
                    $maximo = $numero;
                }
            }
        }

        $siguiente = $maximo + 1;
        $candidato = $prefijo . $siguiente;

        // Verificación defensiva adicional ante inserciones concurrentes
        $stmtCheck = $this->db->prepare(
            "SELECT COUNT(*) FROM remisiones_informes WHERE codigo_remision = :codigo"
        );
        while (true) {
            $stmtCheck->execute(['codigo' => $candidato]);
            if ((int)$stmtCheck->fetchColumn() === 0) {
                return $candidato;
            }
            $siguiente++;
            $candidato = $prefijo . $siguiente;
        }
    }

    /**
     * Lista remisiones con conteo de informes y código de Orden de Servicio.
     */
    public function obtenerTodas(string $busqueda = '', string $estado = '', string $modalidad = ''): array
    {
        $sql = "SELECT r.*,
                       os.codigo_os,
                       os.nombre_proyecto,
                       COALESCE(NULLIF(r.empresa_nombre, ''), cl.nombre_razon_social, '') AS cliente_nombre,
                       u.nombre AS usuario_nombre,
                       (SELECT COUNT(*)
                        FROM remisiones_informes_detalle d
                        WHERE d.id_remision = r.id) AS total_informes
                FROM remisiones_informes r
                LEFT JOIN ordenes_servicio os ON r.id_os = os.id
                LEFT JOIN clientes cl ON r.id_cliente = cl.id
                LEFT JOIN usuarios u ON r.usuario_id = u.id";

        $condiciones = [];
        $params = [];

        $q = trim($busqueda);
        if ($q !== '') {
            $termino = '%' . $q . '%';
            $condiciones[] = "(r.codigo_remision LIKE :q1
                              OR r.empresa_nombre LIKE :q2
                              OR os.codigo_os LIKE :q3
                              OR r.persona_recibe LIKE :q4
                              OR EXISTS (
                                  SELECT 1
                                  FROM remisiones_informes_detalle d2
                                  WHERE d2.id_remision = r.id
                                    AND (d2.codigo_informe LIKE :q5 OR d2.descripcion_ensayo LIKE :q6)
                              ))";
            $params['q1'] = $termino;
            $params['q2'] = $termino;
            $params['q3'] = $termino;
            $params['q4'] = $termino;
            $params['q5'] = $termino;
            $params['q6'] = $termino;
        }

        $estadoNorm = trim($estado);
        if (in_array($estadoNorm, ['Emitida', 'Entregada', 'Anulada'], true)) {
            $condiciones[] = 'r.estado = :estado';
            $params['estado'] = $estadoNorm;
        }

        $modalidadNorm = strtolower(trim($modalidad));
        if (in_array($modalidadNorm, ['laboratorio', 'domicilio'], true)) {
            $condiciones[] = 'r.modalidad_entrega = :modalidad';
            $params['modalidad'] = $modalidadNorm;
        }

        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $sql .= ' ORDER BY r.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una remisión completa por ID incluyendo sus detalles, código O/S y proyecto.
     */
    public function obtenerPorId(int $id): ?array
    {
        if ($id <= 0) {
            return null;
        }

        $sql = "SELECT r.*,
                       os.codigo_os,
                       os.nombre_proyecto,
                       os.ubicacion_proyecto,
                       os.atencion_a,
                       cl.nombre_razon_social AS cliente_razon_social,
                       cl.identificacion AS cliente_ruc,
                       cl.telefono AS cliente_telefono,
                       cl.direccion AS cliente_direccion,
                       u.nombre AS usuario_nombre
                FROM remisiones_informes r
                LEFT JOIN ordenes_servicio os ON r.id_os = os.id
                LEFT JOIN clientes cl ON r.id_cliente = cl.id
                LEFT JOIN usuarios u ON r.usuario_id = u.id
                WHERE r.id = :id
                LIMIT 1";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        $remision = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$remision) {
            return null;
        }

        $stmtDet = $this->db->prepare(
            "SELECT d.*
             FROM remisiones_informes_detalle d
             WHERE d.id_remision = :id_remision
             ORDER BY d.id ASC"
        );
        $stmtDet->execute(['id_remision' => $id]);
        $remision['detalles'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        return $remision;
    }

    /**
     * Obtiene todas las remisiones vinculadas a una Orden de Servicio.
     */
    public function obtenerPorOs(int $idOs): array
    {
        if ($idOs <= 0) {
            return [];
        }

        $sql = "SELECT r.*,
                       os.codigo_os,
                       os.nombre_proyecto,
                       (SELECT COUNT(*)
                        FROM remisiones_informes_detalle d
                        WHERE d.id_remision = r.id) AS total_informes,
                       (SELECT GROUP_CONCAT(d.codigo_informe ORDER BY d.id SEPARATOR ', ')
                        FROM remisiones_informes_detalle d
                        WHERE d.id_remision = r.id) AS codigos_informes
                FROM remisiones_informes r
                LEFT JOIN ordenes_servicio os ON r.id_os = os.id
                WHERE r.id_os = :id_os
                ORDER BY r.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_os' => $idOs]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Lista las Órdenes de Servicio disponibles con su cliente y cantidad de informes/ensayos asociados.
     */
    public function obtenerOrdenesConInformes(): array
    {
        $sql = "SELECT os.id,
                       os.codigo_os,
                       os.id_cotizacion,
                       COALESCE(os.id_cliente_informe, os.id_cliente) AS id_cliente,
                       os.nombre_proyecto,
                       os.atencion_a,
                       os.direccion_cliente,
                       os.estado,
                       COALESCE(cl.nombre_razon_social, '') AS cliente_nombre,
                       COALESCE(cl.telefono, '') AS cliente_telefono,
                       COALESCE(cl.direccion, '') AS cliente_direccion,
                       (SELECT COUNT(*)
                        FROM cotizacion_detalles cd
                        WHERE cd.id_cotizacion = os.id_cotizacion) AS total_ensayos,
                       (SELECT COUNT(*)
                        FROM informes_control ic
                        INNER JOIN lotes_muestras lm ON lm.id = ic.id_lote
                        INNER JOIN recepcion_muestras rm ON rm.id = lm.id_recepcion
                        WHERE rm.id_os = os.id
                          AND ic.estado_aprobacion NOT IN ('Rechazado', 'Reemplazado')) AS total_informes_control
                FROM ordenes_servicio os
                LEFT JOIN clientes cl ON cl.id = COALESCE(os.id_cliente_informe, os.id_cliente)
                ORDER BY os.id DESC";

        $stmt = $this->db->query($sql);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene los datos de la O/S y los informes disponibles (de informes_control y cotizacion_detalles)
     * para autopoblar el formulario de nueva remisión.
     */
    public function obtenerInformesDisponiblesPorOs(int $idOs): array
    {
        if ($idOs <= 0) {
            return ['orden' => null, 'informes' => []];
        }

        $stmtOs = $this->db->prepare(
            "SELECT os.id,
                    os.codigo_os,
                    os.id_cotizacion,
                    COALESCE(os.id_cliente_informe, os.id_cliente) AS id_cliente,
                    os.nombre_proyecto,
                    os.ubicacion_proyecto,
                    os.atencion_a,
                    os.direccion_cliente,
                    os.estado,
                    COALESCE(cl.nombre_razon_social, '') AS empresa_nombre,
                    COALESCE(cl.telefono, '') AS cliente_telefono,
                    COALESCE(NULLIF(os.direccion_cliente, ''), cl.direccion, '') AS direccion_entrega
             FROM ordenes_servicio os
             LEFT JOIN clientes cl ON cl.id = COALESCE(os.id_cliente_informe, os.id_cliente)
             WHERE os.id = :id_os
             LIMIT 1"
        );
        $stmtOs->execute(['id_os' => $idOs]);
        $orden = $stmtOs->fetch(PDO::FETCH_ASSOC);

        if (!$orden) {
            return ['orden' => null, 'informes' => []];
        }

        // Cargar códigos anulados para no ofrecer informes anulados en entregas (PT-13 Sec. 5.6 b)
        $anulados = [];
        try {
            $rowsAnul = $this->db->query("SELECT codigo_informe FROM informes_anulados")->fetchAll(PDO::FETCH_COLUMN);
            foreach ($rowsAnul as $codAnul) {
                $anulados[mb_strtolower(trim((string)$codAnul))] = true;
            }
        } catch (Throwable $e) {
            $anulados = [];
        }

        $informes = [];
        $vistos = [];

        // 1. Informes controlados en `informes_control`
        try {
            $stmtIc = $this->db->prepare(
                "SELECT ic.id AS id_informe_control,
                        ic.codigo_completo,
                        ic.codigo_informe,
                        ic.tipo_informe,
                        ic.estado_aprobacion,
                        lm.nombre_lote,
                        rm.codigo_muestra
                 FROM informes_control ic
                 INNER JOIN lotes_muestras lm ON lm.id = ic.id_lote
                 INNER JOIN recepcion_muestras rm ON rm.id = lm.id_recepcion
                 WHERE rm.id_os = :id_os
                   AND ic.estado_aprobacion NOT IN ('Rechazado', 'Reemplazado')
                 ORDER BY ic.id DESC"
            );
            $stmtIc->execute(['id_os' => $idOs]);
            foreach ($stmtIc->fetchAll(PDO::FETCH_ASSOC) as $rowIc) {
                $codigo = trim((string)($rowIc['codigo_completo'] ?: $rowIc['codigo_informe']));
                $clave = mb_strtolower($codigo);
                if ($codigo === '' || isset($anulados[$clave]) || isset($vistos[$clave])) {
                    continue;
                }
                $vistos[$clave] = true;
                $desc = trim((string)($rowIc['nombre_lote'] ?? ''));
                if (!empty($rowIc['tipo_informe'])) {
                    $desc = ($desc !== '' ? $desc . ' — ' : '') . 'Informe ' . $rowIc['tipo_informe'];
                }
                if (!empty($rowIc['codigo_muestra'])) {
                    $desc .= ' (' . $rowIc['codigo_muestra'] . ')';
                }
                $informes[] = [
                    'origen' => 'informes_control',
                    'id_informe_control' => (int)$rowIc['id_informe_control'],
                    'id_detalle_cotizacion' => null,
                    'codigo_informe' => $codigo,
                    'descripcion_ensayo' => $desc !== '' ? $desc : 'Informe de ensayo de laboratorio',
                    'estado_pt13' => (string)($rowIc['estado_aprobacion'] ?? 'Pendiente'),
                    'formato_entrega' => 'Digital PDF',
                ];
            }
        } catch (Throwable $e) {
            // Si alguna relación de lotes no aplica, continuar con cotizacion_detalles
        }

        // 2. Matrices/ensayos desde `cotizacion_detalles` + estado PT-13
        $idCotizacion = (int)($orden['id_cotizacion'] ?? 0);
        if ($idCotizacion > 0) {
            $stmtCd = $this->db->prepare(
                "SELECT cd.id AS id_detalle_cotizacion,
                        cd.descripcion_ensayo,
                        cd.norma_astm,
                        cd.formato_reporte,
                        cd.resultados_json,
                        pt.codigo_informe AS pt_codigo_informe,
                        pt.estado_pt13
                 FROM cotizacion_detalles cd
                 LEFT JOIN informes_revisiones_pt13 pt ON pt.id_detalle_cotizacion = cd.id
                 WHERE cd.id_cotizacion = :id_cotizacion
                 ORDER BY cd.id ASC"
            );
            $stmtCd->execute(['id_cotizacion' => $idCotizacion]);
            $idx = 1;
            foreach ($stmtCd->fetchAll(PDO::FETCH_ASSOC) as $rowCd) {
                if (($rowCd['estado_pt13'] ?? '') === 'Anulado') {
                    $idx++;
                    continue;
                }

                $dec = [];
                if (!empty($rowCd['resultados_json'])) {
                    $tmp = json_decode((string)$rowCd['resultados_json'], true);
                    if (is_array($tmp)) {
                        $dec = $tmp;
                    }
                }

                $codigo = trim((string)(
                    $rowCd['pt_codigo_informe']
                    ?? $dec['codigo_informe']
                    ?? $dec['numero_informe']
                    ?? $dec['datos_generales']['codigo_informe']
                    ?? ''
                ));

                if ($codigo === '') {
                    $codigo = sprintf('INF-%s-%02d', (string)$orden['codigo_os'], $idx);
                }

                $clave = mb_strtolower($codigo);
                if (isset($anulados[$clave]) || isset($vistos[$clave])) {
                    $idx++;
                    continue;
                }
                $vistos[$clave] = true;

                $desc = trim((string)($rowCd['descripcion_ensayo'] ?? 'Ensayo de laboratorio'));
                if (!empty($rowCd['norma_astm'])) {
                    $desc .= ' [' . trim((string)$rowCd['norma_astm']) . ']';
                }

                $informes[] = [
                    'origen' => 'cotizacion_detalles',
                    'id_informe_control' => null,
                    'id_detalle_cotizacion' => (int)$rowCd['id_detalle_cotizacion'],
                    'codigo_informe' => $codigo,
                    'descripcion_ensayo' => $desc,
                    'estado_pt13' => (string)($rowCd['estado_pt13'] ?? (!empty($rowCd['resultados_json']) ? 'Pendiente' : 'Sin Captura')),
                    'formato_entrega' => 'Digital PDF',
                ];
                $idx++;
            }
        }

        return [
            'orden' => $orden,
            'informes' => $informes,
        ];
    }

    /**
     * Crea una remisión y su detalle de informes de forma transaccional.
     */
    public function crear(array $datos, array $items): int
    {
        $empresaNombre = trim((string)($datos['empresa_nombre'] ?? ''));
        if ($empresaNombre === '') {
            throw new InvalidArgumentException('El nombre de la empresa o cliente es obligatorio.');
        }

        $itemsLimpios = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                continue;
            }
            $codInf = trim((string)($item['codigo_informe'] ?? ''));
            if ($codInf === '') {
                continue;
            }
            $formato = trim((string)($item['formato_entrega'] ?? 'Digital PDF'));
            if (!in_array($formato, ['Digital PDF', 'Impreso Original', 'Ambos'], true)) {
                $formato = 'Digital PDF';
            }
            $itemsLimpios[] = [
                'id_detalle_cotizacion' => !empty($item['id_detalle_cotizacion']) ? (int)$item['id_detalle_cotizacion'] : null,
                'id_informe_control' => !empty($item['id_informe_control']) ? (int)$item['id_informe_control'] : null,
                'codigo_informe' => $codInf,
                'descripcion_ensayo' => trim((string)($item['descripcion_ensayo'] ?? '')),
                'formato_entrega' => $formato,
            ];
        }

        if ($itemsLimpios === []) {
            throw new InvalidArgumentException('Debe incluir al menos un informe en la remisión de entrega.');
        }

        // Bloquear entrega de códigos de informes anulados (PT-13 Sec. 5.6 b)
        $stmtAnul = $this->db->prepare("SELECT COUNT(*) FROM informes_anulados WHERE codigo_informe = :codigo");
        foreach ($itemsLimpios as $it) {
            $stmtAnul->execute(['codigo' => $it['codigo_informe']]);
            if ((int)$stmtAnul->fetchColumn() > 0) {
                throw new RuntimeException(
                    "El informe '{$it['codigo_informe']}' se encuentra anulado (PT-13 Sec. 5.6 b) y no puede incluirse en una remisión."
                );
            }
        }

        $modalidad = strtolower(trim((string)($datos['modalidad_entrega'] ?? 'laboratorio')));
        if (!in_array($modalidad, ['laboratorio', 'domicilio'], true)) {
            $modalidad = 'laboratorio';
        }

        $fechaEntrega = $this->normalizarFechaHora($datos['fecha_entrega'] ?? null);
        $codigoRemision = trim((string)($datos['codigo_remision'] ?? ''));
        if ($codigoRemision === '') {
            $codigoRemision = $this->generarCodigoRemision($fechaEntrega);
        }

        $estado = trim((string)($datos['estado'] ?? 'Emitida'));
        if (!in_array($estado, ['Emitida', 'Entregada', 'Anulada'], true)) {
            $estado = 'Emitida';
        }

        $iniciadaAqui = !$this->db->inTransaction();
        if ($iniciadaAqui) {
            $this->db->beginTransaction();
        }

        try {
            $stmt = $this->db->prepare(
                "INSERT INTO remisiones_informes (
                    codigo_remision, id_os, id_cliente, empresa_nombre, modalidad_entrega,
                    fecha_entrega, persona_recibe, cedula_recibe, telefono_recibe,
                    direccion_entrega, entregado_por, observaciones, estado, usuario_id
                 ) VALUES (
                    :codigo_remision, :id_os, :id_cliente, :empresa_nombre, :modalidad_entrega,
                    :fecha_entrega, :persona_recibe, :cedula_recibe, :telefono_recibe,
                    :direccion_entrega, :entregado_por, :observaciones, :estado, :usuario_id
                 )"
            );
            $stmt->execute([
                'codigo_remision' => $codigoRemision,
                'id_os' => !empty($datos['id_os']) ? (int)$datos['id_os'] : null,
                'id_cliente' => !empty($datos['id_cliente']) ? (int)$datos['id_cliente'] : null,
                'empresa_nombre' => $empresaNombre,
                'modalidad_entrega' => $modalidad,
                'fecha_entrega' => $fechaEntrega,
                'persona_recibe' => $this->nullSiVacio($datos['persona_recibe'] ?? null),
                'cedula_recibe' => $this->nullSiVacio($datos['cedula_recibe'] ?? null),
                'telefono_recibe' => $this->nullSiVacio($datos['telefono_recibe'] ?? null),
                'direccion_entrega' => $this->nullSiVacio($datos['direccion_entrega'] ?? null),
                'entregado_por' => $this->nullSiVacio($datos['entregado_por'] ?? null),
                'observaciones' => $this->nullSiVacio($datos['observaciones'] ?? null),
                'estado' => $estado,
                'usuario_id' => !empty($datos['usuario_id']) ? (int)$datos['usuario_id'] : null,
            ]);

            $idRemision = (int)$this->db->lastInsertId();

            $stmtDet = $this->db->prepare(
                "INSERT INTO remisiones_informes_detalle (
                    id_remision, id_detalle_cotizacion, id_informe_control,
                    codigo_informe, descripcion_ensayo, formato_entrega
                 ) VALUES (
                    :id_remision, :id_detalle_cotizacion, :id_informe_control,
                    :codigo_informe, :descripcion_ensayo, :formato_entrega
                 )"
            );

            foreach ($itemsLimpios as $it) {
                $stmtDet->execute([
                    'id_remision' => $idRemision,
                    'id_detalle_cotizacion' => $it['id_detalle_cotizacion'],
                    'id_informe_control' => $it['id_informe_control'],
                    'codigo_informe' => $it['codigo_informe'],
                    'descripcion_ensayo' => $it['descripcion_ensayo'] !== '' ? $it['descripcion_ensayo'] : null,
                    'formato_entrega' => $it['formato_entrega'],
                ]);
            }

            if ($iniciadaAqui) {
                $this->db->commit();
            }

            return $idRemision;
        } catch (Throwable $e) {
            if ($iniciadaAqui && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Registra la entrega efectiva de una remisión (persona receptora, cédula, teléfono y fecha/hora).
     */
    public function marcarEntregada(
        int $id,
        string $personaRecibe,
        ?string $cedulaRecibe = null,
        ?string $telefonoRecibe = null,
        ?string $fechaEntrega = null
    ): bool {
        $persona = trim($personaRecibe);
        if ($id <= 0 || $persona === '') {
            return false;
        }

        $actual = $this->obtenerPorId($id);
        if (!$actual || ($actual['estado'] ?? '') === 'Anulada') {
            return false;
        }

        $fecha = $this->normalizarFechaHora($fechaEntrega);

        $stmt = $this->db->prepare(
            "UPDATE remisiones_informes
             SET estado = 'Entregada',
                 persona_recibe = :persona_recibe,
                 cedula_recibe = :cedula_recibe,
                 telefono_recibe = :telefono_recibe,
                 fecha_entrega = :fecha_entrega
             WHERE id = :id
               AND estado != 'Anulada'"
        );

        return $stmt->execute([
            'persona_recibe' => $persona,
            'cedula_recibe' => $this->nullSiVacio($cedulaRecibe),
            'telefono_recibe' => $this->nullSiVacio($telefonoRecibe),
            'fecha_entrega' => $fecha,
            'id' => $id,
        ]) && $stmt->rowCount() > 0;
    }

    /**
     * Anula una remisión indicando el motivo obligatorio.
     */
    public function anular(int $id, string $motivo): bool
    {
        $motivoLimpio = trim($motivo);
        if ($id <= 0 || $motivoLimpio === '') {
            return false;
        }

        $stmt = $this->db->prepare(
            "UPDATE remisiones_informes
             SET estado = 'Anulada',
                 motivo_anulacion = :motivo
             WHERE id = :id
               AND estado != 'Anulada'"
        );

        return $stmt->execute([
            'motivo' => $motivoLimpio,
            'id' => $id,
        ]) && $stmt->rowCount() > 0;
    }

    /**
     * KPIs consolidados para el tablero de Remisiones y Control PT-13.
     */
    public function obtenerResumenKpis(): array
    {
        $resumen = [
            'total' => 0,
            'emitidas' => 0,
            'entregadas' => 0,
            'anuladas' => 0,
            'revisiones_pt13' => 0,
            'informes_anulados' => 0,
        ];

        $stmt = $this->db->query(
            "SELECT estado, COUNT(*) AS cantidad
             FROM remisiones_informes
             GROUP BY estado"
        );
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
            $cant = (int)$row['cantidad'];
            $resumen['total'] += $cant;
            if ($row['estado'] === 'Emitida') {
                $resumen['emitidas'] = $cant;
            } elseif ($row['estado'] === 'Entregada') {
                $resumen['entregadas'] = $cant;
            } elseif ($row['estado'] === 'Anulada') {
                $resumen['anuladas'] = $cant;
            }
        }

        $resumen['revisiones_pt13'] = (int)$this->db
            ->query("SELECT COUNT(*) FROM informes_revisiones_pt13")
            ->fetchColumn();

        $resumen['informes_anulados'] = (int)$this->db
            ->query("SELECT COUNT(*) FROM informes_anulados")
            ->fetchColumn();

        return $resumen;
    }

    private function normalizarFechaHora(?string $valor): string
    {
        if ($valor === null || trim($valor) === '') {
            return date('Y-m-d H:i:s');
        }
        $limpio = str_replace('T', ' ', trim($valor));
        $ts = strtotime($limpio);
        if ($ts === false) {
            return date('Y-m-d H:i:s');
        }
        return date('Y-m-d H:i:s', $ts);
    }

    private function nullSiVacio(?string $valor): ?string
    {
        if ($valor === null) {
            return null;
        }
        $limpio = trim($valor);
        return $limpio === '' ? null : $limpio;
    }
}
