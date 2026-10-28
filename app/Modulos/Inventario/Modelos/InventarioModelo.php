<?php

namespace Cycsa\Modulos\Inventario\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;
use Exception;

/**
 * Modelo para la gestión integral de Inventario, Lotes, Kárdex y Consumos por Ensayo (TAREA-048)
 * Cumple con trazabilidad ISO/IEC 17025 y valoración PEPS / FIFO.
 */
class InventarioModelo extends ModeloBase {

    public function __construct() {
        parent::__construct();
        try {
            $check = $this->db->query("SHOW TABLES LIKE 'inventario_insumos'")->fetchAll(PDO::FETCH_COLUMN);
            if (empty($check)) {
                \Cycsa\Modulos\Configuracion\Servicios\MigradorServicio::asegurarTablas($this->db);
            }
        } catch (\Throwable $e) {
            error_log("Auto-migracion InventarioModelo: " . $e->getMessage());
        }
    }

    /**
     * Lista de insumos con saldo total acumulado de lotes activos, stock_minimo y bandera alerta_stock_minimo (saldo <= stock_minimo).
     *
     * @param string $filtro Texto opcional para buscar por código o nombre
     * @return array
     */
    public function listarInsumos(string $filtro = ''): array {
        $sql = "
            SELECT 
                i.*,
                COALESCE(SUM(CASE WHEN l.estado = 'activo' AND l.saldo > 0 THEN l.saldo ELSE 0 END), 0) AS saldo_lotes,
                COUNT(CASE WHEN l.estado = 'activo' AND l.saldo > 0 THEN l.id ELSE NULL END) AS total_lotes_activos
            FROM inventario_insumos i
            LEFT JOIN inventario_lotes l ON l.insumo_id = i.id
        ";

        $params = [];
        if (trim($filtro) !== '') {
            $sql .= " WHERE (i.codigo LIKE :f1 OR i.nombre LIKE :f2) ";
            $params['f1'] = '%' . trim($filtro) . '%';
            $params['f2'] = '%' . trim($filtro) . '%';
        }

        $sql .= " GROUP BY i.id ORDER BY i.nombre ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $insumos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($insumos as &$insumo) {
            $saldo = (float)$insumo['saldo_lotes'];
            $stockMinimo = (float)($insumo['stock_minimo'] ?? 0);
            
            $insumo['saldo'] = $saldo;
            $insumo['saldo_total'] = $saldo;
            $insumo['stock_minimo'] = $stockMinimo;
            $insumo['alerta_stock_minimo'] = ($saldo <= $stockMinimo);
        }
        unset($insumo);

        return $insumos;
    }

    /**
     * Obtiene un insumo por su ID con existencias acumuladas.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerInsumo(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT 
                i.*,
                COALESCE(SUM(CASE WHEN l.estado = 'activo' AND l.saldo > 0 THEN l.saldo ELSE 0 END), 0) AS saldo_lotes
            FROM inventario_insumos i
            LEFT JOIN inventario_lotes l ON l.insumo_id = i.id
            WHERE i.id = :id
            GROUP BY i.id
        ");
        $stmt->execute([':id' => $id]);
        $insumo = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$insumo) {
            return null;
        }

        $saldo = (float)$insumo['saldo_lotes'];
        $stockMinimo = (float)($insumo['stock_minimo'] ?? 0);
        $insumo['saldo'] = $saldo;
        $insumo['saldo_total'] = $saldo;
        $insumo['stock_minimo'] = $stockMinimo;
        $insumo['alerta_stock_minimo'] = ($saldo <= $stockMinimo);

        return $insumo;
    }

    /**
     * Listado de lotes en inventario_lotes con fecha_vencimiento, costo, saldo, y cálculo de alerta de vencimiento
     * (vencido si < hoy, por_vencer si <= 30 días).
     *
     * @param int $insumoId Filtrar por ID de insumo específico (0 para listar todos)
     * @return array
     */
    public function listarLotes(int $insumoId = 0): array {
        $sql = "
            SELECT 
                l.*,
                i.codigo AS insumo_codigo,
                i.nombre AS insumo_nombre,
                i.unidad_medida,
                i.stock_minimo AS insumo_stock_minimo
            FROM inventario_lotes l
            JOIN inventario_insumos i ON i.id = l.insumo_id
        ";

        $params = [];
        if ($insumoId > 0) {
            $sql .= " WHERE l.insumo_id = :insumo_id ";
            $params['insumo_id'] = $insumoId;
        }

        $sql .= " ORDER BY CASE WHEN l.fecha_vencimiento IS NULL THEN 1 ELSE 0 END, l.fecha_vencimiento ASC, l.id ASC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $lotes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $hoy = date('Y-m-d');
        $hoyTs = strtotime($hoy);

        foreach ($lotes as &$lote) {
            $saldo = (float)$lote['saldo'];
            $costo = (float)$lote['costo_unitario'];
            $lote['saldo'] = $saldo;
            $lote['costo'] = $costo;
            $lote['costo_unitario'] = $costo;

            $vencimiento = $lote['fecha_vencimiento'];
            $vencido = false;
            $porVencer = false;
            $diasParaVencer = null;
            $alertaVencimiento = 'vigente';

            if (!empty($vencimiento)) {
                $vencTs = strtotime($vencimiento);
                $dias = (int)floor(($vencTs - $hoyTs) / 86400);
                $diasParaVencer = $dias;

                if ($vencimiento < $hoy) {
                    $vencido = true;
                    $alertaVencimiento = 'vencido';
                } elseif ($dias <= 30) {
                    $porVencer = true;
                    $alertaVencimiento = 'por_vencer';
                } else {
                    $alertaVencimiento = 'vigente';
                }
            } else {
                $alertaVencimiento = 'sin_vencimiento';
            }

            $lote['vencido'] = $vencido;
            $lote['por_vencer'] = $porVencer;
            $lote['dias_para_vencer'] = $diasParaVencer;
            $lote['alerta_vencimiento'] = $alertaVencimiento;
        }
        unset($lote);

        return $lotes;
    }

    /**
     * Obtiene un lote por su ID.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerLote(int $id): ?array {
        $stmt = $this->db->prepare("
            SELECT 
                l.*,
                i.codigo AS insumo_codigo,
                i.nombre AS insumo_nombre,
                i.unidad_medida
            FROM inventario_lotes l
            JOIN inventario_insumos i ON i.id = l.insumo_id
            WHERE l.id = :id
        ");
        $stmt->execute([':id' => $id]);
        $lote = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$lote) {
            return null;
        }

        $lote['saldo'] = (float)$lote['saldo'];
        $lote['costo'] = (float)$lote['costo_unitario'];
        $lote['costo_unitario'] = (float)$lote['costo_unitario'];

        $hoy = date('Y-m-d');
        $hoyTs = strtotime($hoy);
        $vencimiento = $lote['fecha_vencimiento'];
        $lote['vencido'] = false;
        $lote['por_vencer'] = false;
        $lote['dias_para_vencer'] = null;
        $lote['alerta_vencimiento'] = 'vigente';

        if (!empty($vencimiento)) {
            $vencTs = strtotime($vencimiento);
            $dias = (int)floor(($vencTs - $hoyTs) / 86400);
            $lote['dias_para_vencer'] = $dias;

            if ($vencimiento < $hoy) {
                $lote['vencido'] = true;
                $lote['alerta_vencimiento'] = 'vencido';
            } elseif ($dias <= 30) {
                $lote['por_vencer'] = true;
                $lote['alerta_vencimiento'] = 'por_vencer';
            }
        } else {
            $lote['alerta_vencimiento'] = 'sin_vencimiento';
        }

        return $lote;
    }

    /**
     * Movimientos históricos en inventario_movimientos con insumo, lote, tipo, cantidad, saldos anterior/nuevo y usuario.
     *
     * @param int $insumoId
     * @param string|null $fechaDesde
     * @param string|null $fechaHasta
     * @return array
     */
    public function listarKardex(int $insumoId = 0, ?string $fechaDesde = null, ?string $fechaHasta = null): array {
        $sql = "
            SELECT 
                m.*,
                i.codigo AS insumo_codigo,
                i.nombre AS insumo_nombre,
                i.unidad_medida,
                l.lote AS lote_codigo,
                l.fecha_vencimiento AS lote_vencimiento,
                u.nombre AS usuario_nombre,
                u.email AS usuario_email
            FROM inventario_movimientos m
            JOIN inventario_insumos i ON i.id = m.insumo_id
            LEFT JOIN inventario_lotes l ON l.id = m.lote_id
            LEFT JOIN usuarios u ON u.id = m.usuario_id
            WHERE 1=1
        ";

        $params = [];
        if ($insumoId > 0) {
            $sql .= " AND m.insumo_id = :insumo_id ";
            $params['insumo_id'] = $insumoId;
        }
        if (!empty($fechaDesde)) {
            $sql .= " AND DATE(m.created_at) >= :fecha_desde ";
            $params['fecha_desde'] = $fechaDesde;
        }
        if (!empty($fechaHasta)) {
            $sql .= " AND DATE(m.created_at) <= :fecha_hasta ";
            $params['fecha_hasta'] = $fechaHasta;
        }

        $sql .= " ORDER BY m.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $movimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($movimientos as &$mov) {
            $mov['cantidad'] = (float)$mov['cantidad'];
            $mov['costo_unitario'] = (float)$mov['costo_unitario'];
            $mov['saldo_anterior'] = (float)$mov['saldo_anterior'];
            $mov['saldo_nuevo'] = (float)$mov['saldo_nuevo'];
        }
        unset($mov);

        return $movimientos;
    }

    /**
     * Realiza ajuste positivo o negativo, actualiza saldo de lote e inserta movimiento en kardex.
     *
     * @param int $insumoId
     * @param int|null $loteId
     * @param string $tipoAjuste ('ajuste_positivo' o 'ajuste_negativo')
     * @param float $cantidad
     * @param float $costoUnitario
     * @param int $usuarioId
     * @param string $motivo
     * @return array
     * @throws Exception
     */
    public function registrarAjuste(
        int $insumoId,
        ?int $loteId,
        string $tipoAjuste,
        float $cantidad,
        float $costoUnitario,
        int $usuarioId,
        string $motivo
    ): array {
        if ($cantidad <= 0) {
            throw new Exception("La cantidad del ajuste debe ser estrictamente mayor a 0.");
        }

        // Normalizar tipos comunes de ajuste
        if ($tipoAjuste === 'positivo' || $tipoAjuste === '+') {
            $tipoAjuste = 'ajuste_positivo';
        } elseif ($tipoAjuste === 'negativo' || $tipoAjuste === '-') {
            $tipoAjuste = 'ajuste_negativo';
        }

        if (!in_array($tipoAjuste, ['ajuste_positivo', 'ajuste_negativo'], true)) {
            throw new Exception("Tipo de ajuste inválido: '{$tipoAjuste}'. Debe ser 'ajuste_positivo' o 'ajuste_negativo'.");
        }

        $iniciadaTransaccion = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $iniciadaTransaccion = true;
        }

        try {
            // Validar que el insumo exista
            $stmtIns = $this->db->prepare("SELECT * FROM inventario_insumos WHERE id = :id FOR UPDATE");
            $stmtIns->execute([':id' => $insumoId]);
            $insumo = $stmtIns->fetch(PDO::FETCH_ASSOC);
            if (!$insumo) {
                throw new Exception("El insumo ID {$insumoId} no existe.");
            }

            $lote = null;
            if (!empty($loteId) && $loteId > 0) {
                $stmtLote = $this->db->prepare("SELECT * FROM inventario_lotes WHERE id = :id AND insumo_id = :insumo_id FOR UPDATE");
                $stmtLote->execute([':id' => $loteId, ':insumo_id' => $insumoId]);
                $lote = $stmtLote->fetch(PDO::FETCH_ASSOC);
                if (!$lote) {
                    throw new Exception("El lote ID {$loteId} no pertenece al insumo ID {$insumoId}.");
                }
            } else {
                if ($tipoAjuste === 'ajuste_positivo') {
                    // Generar un lote específico para el ajuste positivo
                    $codigoLote = 'AJUSTE-' . date('Ymd-His');
                    $costo = $costoUnitario > 0 ? $costoUnitario : 0.0;
                    $stmtNuevo = $this->db->prepare("
                        INSERT INTO inventario_lotes (insumo_id, lote, fecha_vencimiento, costo_unitario, cantidad_inicial, saldo, estado)
                        VALUES (:insumo_id, :lote, NULL, :costo, :cantidad, 0, 'activo')
                    ");
                    $stmtNuevo->execute([
                        ':insumo_id' => $insumoId,
                        ':lote' => $codigoLote,
                        ':costo' => $costo,
                        ':cantidad' => $cantidad,
                    ]);
                    $loteId = (int)$this->db->lastInsertId();

                    $stmtLote = $this->db->prepare("SELECT * FROM inventario_lotes WHERE id = :id FOR UPDATE");
                    $stmtLote->execute([':id' => $loteId]);
                    $lote = $stmtLote->fetch(PDO::FETCH_ASSOC);
                } else {
                    // Ajuste negativo sin lote especificado: buscar el lote activo más antiguo con saldo
                    $stmtLote = $this->db->prepare("
                        SELECT * FROM inventario_lotes 
                        WHERE insumo_id = :insumo_id AND estado = 'activo' AND saldo > 0 
                        ORDER BY CASE WHEN fecha_vencimiento IS NULL THEN 1 ELSE 0 END, fecha_vencimiento ASC, id ASC 
                        LIMIT 1 FOR UPDATE
                    ");
                    $stmtLote->execute([':insumo_id' => $insumoId]);
                    $lote = $stmtLote->fetch(PDO::FETCH_ASSOC);
                    if (!$lote) {
                        throw new Exception("No hay lotes con saldo activo disponibles para realizar el ajuste negativo.");
                    }
                    $loteId = (int)$lote['id'];
                }
            }

            $saldoAnterior = (float)$lote['saldo'];
            $costoFinal = $costoUnitario > 0 ? $costoUnitario : (float)$lote['costo_unitario'];

            if ($tipoAjuste === 'ajuste_positivo') {
                $saldoNuevo = round($saldoAnterior + $cantidad, 4);
                $nuevoEstado = 'activo';
            } else {
                if ($saldoAnterior < $cantidad) {
                    throw new Exception("Saldo insuficiente en el lote '{$lote['lote']}'. Saldo actual: {$saldoAnterior}, Solicitado: {$cantidad}");
                }
                $saldoNuevo = round($saldoAnterior - $cantidad, 4);
                $nuevoEstado = ($saldoNuevo <= 0.0001) ? 'agotado' : 'activo';
            }

            // Actualizar lote
            $stmtUpdLote = $this->db->prepare("
                UPDATE inventario_lotes 
                SET saldo = :saldo, estado = :estado, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");
            $stmtUpdLote->execute([
                ':saldo' => $saldoNuevo,
                ':estado' => $nuevoEstado,
                ':id' => $loteId,
            ]);

            // Registrar movimiento en Kárdex
            $stmtMov = $this->db->prepare("
                INSERT INTO inventario_movimientos (
                    insumo_id, lote_id, tipo_movimiento, cantidad, costo_unitario,
                    saldo_anterior, saldo_nuevo, referencia_tipo, referencia_id, usuario_id, observaciones
                ) VALUES (
                    :insumo_id, :lote_id, :tipo_movimiento, :cantidad, :costo_unitario,
                    :saldo_anterior, :saldo_nuevo, 'ajuste_manual', NULL, :usuario_id, :observaciones
                )
            ");
            $stmtMov->execute([
                ':insumo_id' => $insumoId,
                ':lote_id' => $loteId,
                ':tipo_movimiento' => $tipoAjuste,
                ':cantidad' => $cantidad,
                ':costo_unitario' => $costoFinal,
                ':saldo_anterior' => $saldoAnterior,
                ':saldo_nuevo' => $saldoNuevo,
                ':usuario_id' => $usuarioId > 0 ? $usuarioId : null,
                ':observaciones' => $motivo,
            ]);
            $movimientoId = (int)$this->db->lastInsertId();

            // Sincronizar columna cantidad del insumo
            $this->sincronizarStockInsumo($insumoId);

            if ($iniciadaTransaccion) {
                $this->db->commit();
            }

            return [
                'exito' => true,
                'movimiento_id' => $movimientoId,
                'insumo_id' => $insumoId,
                'lote_id' => $loteId,
                'lote_codigo' => $lote['lote'],
                'tipo_ajuste' => $tipoAjuste,
                'cantidad' => $cantidad,
                'costo_unitario' => $costoFinal,
                'saldo_anterior' => $saldoAnterior,
                'saldo_nuevo' => $saldoNuevo,
                'nuevo_estado' => $nuevoEstado,
            ];

        } catch (Exception $e) {
            if ($iniciadaTransaccion && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Consume inventario usando FIFO/PEPS (lotes activos ordenados por fecha_vencimiento ASC, id ASC).
     * Descuenta saldo del lote o lotes necesarios hasta completar la cantidad requerida.
     * Si el saldo del lote llega a 0, actualiza estado a 'agotado'.
     * Inserta registro en inventario_movimientos con tipo_movimiento = 'consumo_ensayo', referencia_tipo = 'ensayo', referencia_id = $ensayoId.
     * Retorna el costo total del consumo y el detalle de lotes afectados.
     *
     * @param int $insumoId
     * @param float $cantidadRequerida
     * @param int $ensayoId
     * @param string $tipoEnsayo
     * @param int $usuarioId
     * @param string|null $observaciones
     * @return array
     * @throws Exception
     */
    public function registrarConsumoEnsayo(
        int $insumoId,
        float $cantidadRequerida,
        int $ensayoId,
        string $tipoEnsayo,
        int $usuarioId,
        ?string $observaciones = null
    ): array {
        if ($cantidadRequerida <= 0) {
            throw new Exception("La cantidad requerida de consumo debe ser estrictamente mayor a 0.");
        }

        $iniciadaTransaccion = false;
        if (!$this->db->inTransaction()) {
            $this->db->beginTransaction();
            $iniciadaTransaccion = true;
        }

        try {
            // Verificar insumo
            $stmtIns = $this->db->prepare("SELECT * FROM inventario_insumos WHERE id = :id FOR UPDATE");
            $stmtIns->execute([':id' => $insumoId]);
            $insumo = $stmtIns->fetch(PDO::FETCH_ASSOC);
            if (!$insumo) {
                throw new Exception("El insumo ID {$insumoId} no existe.");
            }

            // Obtener lotes activos con saldo disponible ordenados por PEPS/FIFO:
            // Fechas no nulas primero (fecha_vencimiento ASC), luego sin fecha, luego id ASC
            $sqlLotes = "
                SELECT id, lote, fecha_vencimiento, costo_unitario, saldo 
                FROM inventario_lotes 
                WHERE insumo_id = :insumo_id AND estado = 'activo' AND saldo > 0 
                ORDER BY CASE WHEN fecha_vencimiento IS NULL THEN 1 ELSE 0 END, fecha_vencimiento ASC, id ASC 
                FOR UPDATE
            ";
            $stmtLotes = $this->db->prepare($sqlLotes);
            $stmtLotes->execute([':insumo_id' => $insumoId]);
            $lotes = $stmtLotes->fetchAll(PDO::FETCH_ASSOC);

            $stockDisponible = 0.0;
            foreach ($lotes as $l) {
                $stockDisponible += (float)$l['saldo'];
            }

            if ($stockDisponible < $cantidadRequerida) {
                throw new Exception("Stock insuficiente para el insumo '{$insumo['nombre']}'. Disponible: {$stockDisponible}, Requerido: {$cantidadRequerida}");
            }

            $pendiente = $cantidadRequerida;
            $costoTotal = 0.0;
            $lotesAfectados = [];

            $stmtUpdLote = $this->db->prepare("
                UPDATE inventario_lotes 
                SET saldo = :saldo, estado = :estado, updated_at = CURRENT_TIMESTAMP 
                WHERE id = :id
            ");

            $stmtMov = $this->db->prepare("
                INSERT INTO inventario_movimientos (
                    insumo_id, lote_id, tipo_movimiento, cantidad, costo_unitario,
                    saldo_anterior, saldo_nuevo, referencia_tipo, referencia_id, usuario_id, observaciones
                ) VALUES (
                    :insumo_id, :lote_id, 'consumo_ensayo', :cantidad, :costo_unitario,
                    :saldo_anterior, :saldo_nuevo, 'ensayo', :referencia_id, :usuario_id, :observaciones
                )
            ");

            foreach ($lotes as $lote) {
                if ($pendiente <= 0.00001) {
                    break;
                }

                $loteId = (int)$lote['id'];
                $saldoActual = (float)$lote['saldo'];
                $costoUnitario = (float)$lote['costo_unitario'];

                $aDescontar = min($pendiente, $saldoActual);
                $nuevoSaldo = round($saldoActual - $aDescontar, 4);
                $nuevoEstado = ($nuevoSaldo <= 0.0001) ? 'agotado' : 'activo';
                $subtotalCosto = round($aDescontar * $costoUnitario, 4);

                $costoTotal += $subtotalCosto;

                // Actualizar lote
                $stmtUpdLote->execute([
                    ':saldo' => $nuevoSaldo,
                    ':estado' => $nuevoEstado,
                    ':id' => $loteId,
                ]);

                // Registrar en kardex
                $obsTexto = "Consumo ensayo {$tipoEnsayo} (#{$ensayoId})";
                if (!empty($observaciones)) {
                    $obsTexto .= " - " . $observaciones;
                }

                $stmtMov->execute([
                    ':insumo_id' => $insumoId,
                    ':lote_id' => $loteId,
                    ':cantidad' => $aDescontar,
                    ':costo_unitario' => $costoUnitario,
                    ':saldo_anterior' => $saldoActual,
                    ':saldo_nuevo' => $nuevoSaldo,
                    ':referencia_id' => $ensayoId,
                    ':usuario_id' => $usuarioId > 0 ? $usuarioId : null,
                    ':observaciones' => $obsTexto,
                ]);

                $movimientoId = (int)$this->db->lastInsertId();

                $lotesAfectados[] = [
                    'movimiento_id' => $movimientoId,
                    'lote_id' => $loteId,
                    'lote' => $lote['lote'],
                    'fecha_vencimiento' => $lote['fecha_vencimiento'],
                    'cantidad' => $aDescontar,
                    'costo_unitario' => $costoUnitario,
                    'subtotal' => $subtotalCosto,
                    'saldo_anterior' => $saldoActual,
                    'saldo_nuevo' => $nuevoSaldo,
                    'estado' => $nuevoEstado,
                ];

                $pendiente -= $aDescontar;
            }

            // Sincronizar stock total del insumo
            $this->sincronizarStockInsumo($insumoId);

            if ($iniciadaTransaccion) {
                $this->db->commit();
            }

            return [
                'exito' => true,
                'insumo_id' => $insumoId,
                'insumo_nombre' => $insumo['nombre'],
                'cantidad_consumida' => $cantidadRequerida,
                'costo_total' => round($costoTotal, 4),
                'ensayo_id' => $ensayoId,
                'tipo_ensayo' => $tipoEnsayo,
                'lotes_afectados' => $lotesAfectados,
            ];

        } catch (Exception $e) {
            if ($iniciadaTransaccion && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            throw $e;
        }
    }

    /**
     * Retorna contadores y listados de insumos bajo stock mínimo y lotes próximos a vencer (< 30 días).
     *
     * @return array
     */
    public function obtenerAlertas(): array {
        // 1. Insumos bajo stock mínimo
        $insumos = $this->listarInsumos();
        $insumosBajoStock = [];
        foreach ($insumos as $ins) {
            if (!empty($ins['alerta_stock_minimo'])) {
                $insumosBajoStock[] = $ins;
            }
        }

        // 2. Lotes próximos a vencer y vencidos (solo lotes activos con saldo > 0)
        $lotes = $this->listarLotes();
        $lotesPorVencer = [];
        $lotesVencidos = [];

        foreach ($lotes as $lot) {
            if ($lot['estado'] === 'activo' && (float)$lot['saldo'] > 0) {
                if ($lot['vencido']) {
                    $lotesVencidos[] = $lot;
                } elseif ($lot['por_vencer']) {
                    $lotesPorVencer[] = $lot;
                }
            }
        }

        return [
            'conteo_bajo_stock' => count($insumosBajoStock),
            'conteo_por_vencer' => count($lotesPorVencer),
            'conteo_vencidos' => count($lotesVencidos),
            'insumos_bajo_stock' => $insumosBajoStock,
            'lotes_por_vencer' => $lotesPorVencer,
            'lotes_vencidos' => $lotesVencidos,
        ];
    }

    /**
     * Crea un insumo en la base de datos.
     *
     * @param array $datos
     * @return int ID creado
     */
    public function crearInsumo(array $datos): int {
        $stmt = $this->db->prepare("
            INSERT INTO inventario_insumos (codigo, nombre, cantidad, unidad_medida, stock_minimo, cuenta_inventario_id)
            VALUES (:codigo, :nombre, :cantidad, :unidad_medida, :stock_minimo, :cuenta_inventario_id)
        ");
        $stmt->execute([
            ':codigo' => trim($datos['codigo']),
            ':nombre' => trim($datos['nombre']),
            ':cantidad' => (float)($datos['cantidad'] ?? 0),
            ':unidad_medida' => $datos['unidad_medida'] ?? 'unidad',
            ':stock_minimo' => (float)($datos['stock_minimo'] ?? 0),
            ':cuenta_inventario_id' => !empty($datos['cuenta_inventario_id']) ? (int)$datos['cuenta_inventario_id'] : null,
        ]);
        return (int)$this->db->lastInsertId();
    }

    /**
     * Crea un lote para un insumo.
     *
     * @param array $datos
     * @return int ID creado
     */
    public function crearLote(array $datos): int {
        $cantidadInicial = (float)($datos['cantidad_inicial'] ?? 0);
        $saldo = isset($datos['saldo']) ? (float)$datos['saldo'] : $cantidadInicial;
        $estado = $datos['estado'] ?? ($saldo > 0 ? 'activo' : 'agotado');

        $stmt = $this->db->prepare("
            INSERT INTO inventario_lotes (insumo_id, lote, fecha_vencimiento, costo_unitario, cantidad_inicial, saldo, estado)
            VALUES (:insumo_id, :lote, :fecha_vencimiento, :costo_unitario, :cantidad_inicial, :saldo, :estado)
        ");
        $stmt->execute([
            ':insumo_id' => (int)$datos['insumo_id'],
            ':lote' => trim($datos['lote']),
            ':fecha_vencimiento' => !empty($datos['fecha_vencimiento']) ? $datos['fecha_vencimiento'] : null,
            ':costo_unitario' => (float)($datos['costo_unitario'] ?? 0),
            ':cantidad_inicial' => $cantidadInicial,
            ':saldo' => $saldo,
            ':estado' => $estado,
        ]);
        $loteId = (int)$this->db->lastInsertId();

        $this->sincronizarStockInsumo((int)$datos['insumo_id']);

        return $loteId;
    }

    /**
     * Sincroniza la columna cantidad de inventario_insumos con la suma de saldos de lotes activos.
     *
     * @param int $insumoId
     * @return void
     */
    public function sincronizarStockInsumo(int $insumoId): void {
        $stmt = $this->db->prepare("
            UPDATE inventario_insumos 
            SET cantidad = (
                SELECT COALESCE(SUM(saldo), 0) 
                FROM inventario_lotes 
                WHERE insumo_id = :insumo_id AND estado = 'activo'
            )
            WHERE id = :id
        ");
        $stmt->execute([':insumo_id' => $insumoId, ':id' => $insumoId]);
    }
}
