<?php

namespace Cycsa\Modulos\Compras\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;
use Throwable;

class ComprasModelo extends ModeloBase {

    public function __construct(?PDO $db = null) {
        if ($db !== null) {
            $this->db = $db;
        } else {
            parent::__construct();
        }
    }

    // =========================================================================
    // 1. MAESTRO DE PROVEEDORES (ISO/IEC 17025 §6.6)
    // =========================================================================

    /**
     * Lista los proveedores con filtros opcionales de búsqueda.
     *
     * @param string|array $filtro
     * @return array
     */
    public function listarProveedores($filtro = ''): array {
        $sql = "SELECT p.*, cc.nombre AS cuenta_nombre, cc.codigo AS cuenta_codigo
                FROM proveedores p
                LEFT JOIN cuentas_contables cc ON p.id_cuenta_contable = cc.id";
        
        $params = [];
        $where = [];

        if (is_array($filtro)) {
            if (!empty($filtro['busqueda'])) {
                $term = '%' . trim($filtro['busqueda']) . '%';
                $where[] = "(p.razon_social LIKE :q_nom OR p.identificacion LIKE :q_ruc OR p.contacto LIKE :q_con)";
                $params['q_nom'] = $term;
                $params['q_ruc'] = $term;
                $params['q_con'] = $term;
            }
            if (!empty($filtro['estado'])) {
                $where[] = "p.estado = :estado";
                $params['estado'] = $filtro['estado'];
            }
            if (!empty($filtro['categoria'])) {
                $where[] = "p.categoria = :categoria";
                $params['categoria'] = $filtro['categoria'];
            }
            if (isset($filtro['evaluado']) && $filtro['evaluado'] !== '') {
                $where[] = "p.evaluado = :evaluado";
                $params['evaluado'] = (int)$filtro['evaluado'];
            }
        } elseif (is_string($filtro) && trim($filtro) !== '') {
            $term = '%' . trim($filtro) . '%';
            $where[] = "(p.razon_social LIKE :q1 OR p.identificacion LIKE :q2 OR p.contacto LIKE :q3 OR p.categoria LIKE :q4 OR p.estado LIKE :q5)";
            $params['q1'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
            $params['q4'] = $term;
            $params['q5'] = $term;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY p.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene un proveedor por su ID con datos contables asociados.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerProveedorPorId($id): ?array {
        $stmt = $this->db->prepare("
            SELECT p.*, cc.nombre AS cuenta_nombre, cc.codigo AS cuenta_codigo
            FROM proveedores p
            LEFT JOIN cuentas_contables cc ON p.id_cuenta_contable = cc.id
            WHERE p.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => (int)$id]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
    }

    /**
     * Guarda o actualiza un proveedor en el catálogo.
     *
     * @param array $datos
     * @return int ID del proveedor
     */
    public function guardarProveedor(array $datos): int {
        $id = !empty($datos['id']) ? (int)$datos['id'] : null;
        $tipoId = in_array($datos['tipo_id'] ?? '', ['RUC', 'cedula', 'extranjero']) ? $datos['tipo_id'] : 'RUC';
        $identificacion = trim($datos['identificacion'] ?? '');
        $razonSocial = trim($datos['razon_social'] ?? '');
        $contacto = !empty($datos['contacto']) ? trim($datos['contacto']) : null;
        $telefono = !empty($datos['telefono']) ? trim($datos['telefono']) : null;
        $email = !empty($datos['email']) ? trim($datos['email']) : null;
        $banco = !empty($datos['banco']) ? trim($datos['banco']) : null;
        $cuentaBancaria = !empty($datos['cuenta_bancaria']) ? trim($datos['cuenta_bancaria']) : null;
        $plazoPago = !empty($datos['plazo_pago_dias']) ? (int)$datos['plazo_pago_dias'] : 30;
        $idCuenta = !empty($datos['id_cuenta_contable']) ? (int)$datos['id_cuenta_contable'] : null;
        $cuentaAnticipo = !empty($datos['cuenta_anticipo_id']) ? (int)$datos['cuenta_anticipo_id'] : null;
        $categoria = !empty($datos['categoria']) ? trim($datos['categoria']) : 'insumos';
        $evaluado = !empty($datos['evaluado']) ? 1 : 0;
        $fechaEvaluacion = !empty($datos['fecha_evaluacion']) ? $datos['fecha_evaluacion'] : null;
        $estado = in_array($datos['estado'] ?? '', ['activo', 'inactivo', 'bloqueado']) ? $datos['estado'] : 'activo';

        if ($id) {
            $sql = "UPDATE proveedores SET 
                        tipo_id = :tipo_id,
                        identificacion = :identificacion,
                        razon_social = :razon_social,
                        contacto = :contacto,
                        telefono = :telefono,
                        email = :email,
                        banco = :banco,
                        cuenta_bancaria = :cuenta_bancaria,
                        plazo_pago_dias = :plazo_pago_dias,
                        id_cuenta_contable = :id_cuenta_contable,
                        cuenta_anticipo_id = :cuenta_anticipo_id,
                        categoria = :categoria,
                        evaluado = :evaluado,
                        fecha_evaluacion = :fecha_evaluacion,
                        estado = :estado
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'tipo_id' => $tipoId,
                'identificacion' => $identificacion,
                'razon_social' => $razonSocial,
                'contacto' => $contacto,
                'telefono' => $telefono,
                'email' => $email,
                'banco' => $banco,
                'cuenta_bancaria' => $cuentaBancaria,
                'plazo_pago_dias' => $plazoPago,
                'id_cuenta_contable' => $idCuenta,
                'cuenta_anticipo_id' => $cuentaAnticipo,
                'categoria' => $categoria,
                'evaluado' => $evaluado,
                'fecha_evaluacion' => $fechaEvaluacion,
                'estado' => $estado,
                'id' => $id
            ]);
            return $id;
        } else {
            $sql = "INSERT INTO proveedores (
                        tipo_id, identificacion, razon_social, contacto, telefono, email,
                        banco, cuenta_bancaria, plazo_pago_dias, id_cuenta_contable,
                        cuenta_anticipo_id, categoria, evaluado, fecha_evaluacion, estado
                    ) VALUES (
                        :tipo_id, :identificacion, :razon_social, :contacto, :telefono, :email,
                        :banco, :cuenta_bancaria, :plazo_pago_dias, :id_cuenta_contable,
                        :cuenta_anticipo_id, :categoria, :evaluado, :fecha_evaluacion, :estado
                    )";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'tipo_id' => $tipoId,
                'identificacion' => $identificacion,
                'razon_social' => $razonSocial,
                'contacto' => $contacto,
                'telefono' => $telefono,
                'email' => $email,
                'banco' => $banco,
                'cuenta_bancaria' => $cuentaBancaria,
                'plazo_pago_dias' => $plazoPago,
                'id_cuenta_contable' => $idCuenta,
                'cuenta_anticipo_id' => $cuentaAnticipo,
                'categoria' => $categoria,
                'evaluado' => $evaluado,
                'fecha_evaluacion' => $fechaEvaluacion,
                'estado' => $estado
            ]);
            return (int)$this->db->lastInsertId();
        }
    }

    /**
     * Evalúa a un proveedor bajo la norma ISO/IEC 17025 §6.6.
     *
     * @param int $id
     * @param bool $evaluado
     * @param string|null $fecha
     * @param int $usuarioId
     * @return bool
     */
    public function evaluarProveedor(int $id, bool $evaluado, ?string $fecha = null, int $usuarioId = 1): bool {
        if ($evaluado && empty($fecha)) {
            $fecha = date('Y-m-d');
        } elseif (!$evaluado) {
            $fecha = null;
        }

        $stmt = $this->db->prepare("
            UPDATE proveedores 
            SET evaluado = :evaluado, fecha_evaluacion = :fecha 
            WHERE id = :id
        ");
        $resultado = $stmt->execute([
            'evaluado' => $evaluado ? 1 : 0,
            'fecha' => $fecha,
            'id' => $id
        ]);

        if (function_exists('registrarBitacora')) {
            $desc = "Evaluación ISO 17025 §6.6 de Proveedor #{$id}: " . ($evaluado ? "APROBADO ({$fecha})" : "NO APROBADO");
            registrarBitacora('compras', 'evaluar_proveedor', $desc, $id);
        }

        return $resultado;
    }

    // =========================================================================
    // 2. ÓRDENES DE COMPRA (OC)
    // =========================================================================

    /**
     * Lista las órdenes de compra con proveedor y usuario aprobador.
     *
     * @param string|array $filtro
     * @return array
     */
    public function listarOrdenesCompra($filtro = ''): array {
        $sql = "SELECT oc.*, p.razon_social AS proveedor_nombre, p.identificacion AS proveedor_identificacion,
                       u.nombre AS aprobado_por_nombre
                FROM ordenes_compra oc
                LEFT JOIN proveedores p ON oc.proveedor_id = p.id
                LEFT JOIN usuarios u ON oc.aprobado_por = u.id";

        $params = [];
        $where = [];

        if (is_array($filtro)) {
            if (!empty($filtro['busqueda'])) {
                $term = '%' . trim($filtro['busqueda']) . '%';
                $where[] = "(oc.id LIKE :q_id OR oc.proveedor LIKE :q_prov OR p.razon_social LIKE :q_rs)";
                $params['q_id'] = $term;
                $params['q_prov'] = $term;
                $params['q_rs'] = $term;
            }
            if (!empty($filtro['estado'])) {
                $where[] = "oc.estado = :estado";
                $params['estado'] = $filtro['estado'];
            }
            if (!empty($filtro['proveedor_id'])) {
                $where[] = "oc.proveedor_id = :prov_id";
                $params['prov_id'] = (int)$filtro['proveedor_id'];
            }
        } elseif (is_string($filtro) && trim($filtro) !== '') {
            $term = '%' . trim($filtro) . '%';
            $where[] = "(oc.id LIKE :q1 OR oc.proveedor LIKE :q2 OR p.razon_social LIKE :q3 OR oc.estado LIKE :q4)";
            $params['q1'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
            $params['q4'] = $term;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY oc.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una orden de compra completa con sus detalles.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerOrdenCompraPorId($id): ?array {
        $stmt = $this->db->prepare("
            SELECT oc.*, p.razon_social AS proveedor_nombre, p.identificacion AS proveedor_identificacion,
                   p.contacto AS proveedor_contacto, p.telefono AS proveedor_telefono, p.email AS proveedor_email,
                   u.nombre AS aprobado_por_nombre
            FROM ordenes_compra oc
            LEFT JOIN proveedores p ON oc.proveedor_id = p.id
            LEFT JOIN usuarios u ON oc.aprobado_por = u.id
            WHERE oc.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => (int)$id]);
        $oc = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$oc) {
            return null;
        }

        $stmtDet = $this->db->prepare("
            SELECT ocd.*, ins.nombre AS insumo_nombre, ins.codigo AS insumo_codigo, ins.unidad_medida
            FROM ordenes_compra_detalle ocd
            LEFT JOIN inventario_insumos ins ON ocd.insumo_id = ins.id
            WHERE ocd.orden_compra_id = :id
            ORDER BY ocd.id ASC
        ");
        $stmtDet->execute(['id' => (int)$id]);
        $oc['detalles'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        return $oc;
    }

    /**
     * Guarda o actualiza una orden de compra y sus líneas de detalle calculando totales e IVA.
     *
     * @param array $cabecera
     * @param array $detalles
     * @return int ID de la orden de compra
     */
    public function guardarOrdenCompra(array $cabecera, array $detalles): int {
        try {
            $this->db->beginTransaction();

            $id = !empty($cabecera['id']) ? (int)$cabecera['id'] : null;
            $proveedorId = !empty($cabecera['proveedor_id']) ? (int)$cabecera['proveedor_id'] : null;
            $proveedor = trim($cabecera['proveedor'] ?? '');

            // Si viene proveedor_id y el nombre está vacío, resolver de proveedores
            if ($proveedorId && $proveedor === '') {
                $stmtProv = $this->db->prepare("SELECT razon_social FROM proveedores WHERE id = :id LIMIT 1");
                $stmtProv->execute(['id' => $proveedorId]);
                $provNom = $stmtProv->fetchColumn();
                if ($provNom) {
                    $proveedor = (string)$provNom;
                }
            }

            $fecha = !empty($cabecera['fecha']) ? $cabecera['fecha'] : date('Y-m-d');
            $estado = !empty($cabecera['estado']) ? strtoupper(trim($cabecera['estado'])) : 'SOLICITADA';

            // Calcular subtotales e IVA acumulados
            $subtotalAcumulado = 0.0;
            $ivaAcumulado = 0.0;
            $totalAcumulado = 0.0;

            $lineasProcesadas = [];
            foreach ($detalles as $det) {
                $cant = (float)($det['cantidad'] ?? 0);
                $costo = (float)($det['costo_unitario'] ?? 0);
                $lineSubtotal = round($cant * $costo, 2);
                
                // Si el detalle especifica iva directamente o si aplica tasa del 15%
                if (isset($det['iva']) && $det['iva'] !== '') {
                    $lineIva = round((float)$det['iva'], 2);
                } elseif (!empty($det['aplica_iva'])) {
                    $lineIva = round($lineSubtotal * 0.15, 2);
                } else {
                    $lineIva = 0.0;
                }

                $lineTotal = round($lineSubtotal + $lineIva, 2);

                $subtotalAcumulado += $lineSubtotal;
                $ivaAcumulado += $lineIva;
                $totalAcumulado += $lineTotal;

                $lineasProcesadas[] = [
                    'insumo_id' => !empty($det['insumo_id']) ? (int)$det['insumo_id'] : null,
                    'descripcion' => trim($det['descripcion'] ?? ''),
                    'cantidad' => $cant,
                    'costo_unitario' => $costo,
                    'subtotal' => $lineSubtotal,
                    'iva' => $lineIva,
                    'total' => $lineTotal,
                    'cuenta_contable_id' => !empty($det['cuenta_contable_id']) ? (int)$det['cuenta_contable_id'] : null,
                ];
            }

            // Permitir sobrescribir totales si la cabecera ya los suministró explícitamente
            $subtotalFinal = isset($cabecera['subtotal']) ? (float)$cabecera['subtotal'] : $subtotalAcumulado;
            $ivaFinal = isset($cabecera['iva']) ? (float)$cabecera['iva'] : $ivaAcumulado;
            $totalFinal = isset($cabecera['total']) ? (float)$cabecera['total'] : $totalAcumulado;

            if ($id) {
                $stmt = $this->db->prepare("
                    UPDATE ordenes_compra 
                    SET proveedor_id = :proveedor_id,
                        proveedor = :proveedor,
                        fecha = :fecha,
                        subtotal = :subtotal,
                        iva = :iva,
                        total = :total,
                        estado = :estado
                    WHERE id = :id
                ");
                $stmt->execute([
                    'proveedor_id' => $proveedorId,
                    'proveedor' => $proveedor,
                    'fecha' => $fecha,
                    'subtotal' => $subtotalFinal,
                    'iva' => $ivaFinal,
                    'total' => $totalFinal,
                    'estado' => $estado,
                    'id' => $id
                ]);

                // Limpiar detalles previos para sincronizar
                $del = $this->db->prepare("DELETE FROM ordenes_compra_detalle WHERE orden_compra_id = :id");
                $del->execute(['id' => $id]);
                $ocId = $id;
            } else {
                $stmt = $this->db->prepare("
                    INSERT INTO ordenes_compra (
                        proveedor_id, proveedor, fecha, subtotal, iva, total, estado
                    ) VALUES (
                        :proveedor_id, :proveedor, :fecha, :subtotal, :iva, :total, :estado
                    )
                ");
                $stmt->execute([
                    'proveedor_id' => $proveedorId,
                    'proveedor' => $proveedor,
                    'fecha' => $fecha,
                    'subtotal' => $subtotalFinal,
                    'iva' => $ivaFinal,
                    'total' => $totalFinal,
                    'estado' => $estado
                ]);
                $ocId = (int)$this->db->lastInsertId();
            }

            // Insertar líneas de detalle
            $stmtLine = $this->db->prepare("
                INSERT INTO ordenes_compra_detalle (
                    orden_compra_id, insumo_id, descripcion, cantidad, cantidad_recibida,
                    costo_unitario, subtotal, iva, total, cuenta_contable_id
                ) VALUES (
                    :orden_compra_id, :insumo_id, :descripcion, :cantidad, 0,
                    :costo_unitario, :subtotal, :iva, :total, :cuenta_contable_id
                )
            ");

            foreach ($lineasProcesadas as $linea) {
                $stmtLine->execute([
                    'orden_compra_id' => $ocId,
                    'insumo_id' => $linea['insumo_id'],
                    'descripcion' => $linea['descripcion'],
                    'cantidad' => $linea['cantidad'],
                    'costo_unitario' => $linea['costo_unitario'],
                    'subtotal' => $linea['subtotal'],
                    'iva' => $linea['iva'],
                    'total' => $linea['total'],
                    'cuenta_contable_id' => $linea['cuenta_contable_id']
                ]);
            }

            $this->db->commit();
            return $ocId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en guardarOrdenCompra: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Cambia el estado de una orden de compra (e.g. SOLICITADA -> APROBADA).
     *
     * @param int $id
     * @param string $nuevoEstado
     * @param int|null $aprobadoPor
     * @return bool
     */
    public function cambiarEstadoOrdenCompra(int $id, string $nuevoEstado, ?int $aprobadoPor = null): bool {
        $nuevoEstado = strtoupper(trim($nuevoEstado));

        if ($nuevoEstado === 'APROBADA') {
            $aprobadoPor = $aprobadoPor ?? ($_SESSION['usuario_id'] ?? 1);
            $stmt = $this->db->prepare("
                UPDATE ordenes_compra 
                SET estado = :estado,
                    aprobado_por = :aprobado_por,
                    fecha_aprobacion = NOW()
                WHERE id = :id
            ");
            return $stmt->execute([
                'estado' => $nuevoEstado,
                'aprobado_por' => $aprobadoPor,
                'id' => $id
            ]);
        } else {
            $stmt = $this->db->prepare("UPDATE ordenes_compra SET estado = :estado WHERE id = :id");
            return $stmt->execute([
                'estado' => $nuevoEstado,
                'id' => $id
            ]);
        }
    }

    // =========================================================================
    // 3. RECEPCIONES DE COMPRA E INVENTARIO
    // =========================================================================

    /**
     * Registra la recepción física/técnica de una compra, actualiza kárdex, lotes y saldos de OC.
     *
     * @param int $ordenCompraId
     * @param array $cabecera
     * @param array $detalles
     * @return int ID de la recepción
     */
    public function registrarRecepcionCompra(int $ordenCompraId, array $cabecera, array $detalles): int {
        try {
            $this->db->beginTransaction();

            // Si hay orden_compra_id, asegurar datos de cabecera
            $proveedorId = !empty($cabecera['proveedor_id']) ? (int)$cabecera['proveedor_id'] : null;
            if ($ordenCompraId > 0 && !$proveedorId) {
                $stmtOc = $this->db->prepare("SELECT proveedor_id FROM ordenes_compra WHERE id = :id LIMIT 1");
                $stmtOc->execute(['id' => $ordenCompraId]);
                $proveedorId = (int)$stmtOc->fetchColumn();
            }

            $numeroFactura = !empty($cabecera['numero_factura']) ? trim($cabecera['numero_factura']) : null;
            $fechaRecepcion = !empty($cabecera['fecha_recepcion']) ? $cabecera['fecha_recepcion'] : date('Y-m-d');
            $recibidoPor = !empty($cabecera['recibido_por']) ? (int)$cabecera['recibido_por'] : ($_SESSION['usuario_id'] ?? 1);
            $observaciones = !empty($cabecera['observaciones']) ? trim($cabecera['observaciones']) : null;
            $estado = in_array($cabecera['estado'] ?? '', ['completada', 'anulada']) ? $cabecera['estado'] : 'completada';

            // 1. Insertar cabecera de recepción
            $stmtRec = $this->db->prepare("
                INSERT INTO recepciones_compra (
                    orden_compra_id, proveedor_id, numero_factura, fecha_recepcion,
                    recibido_por, observaciones, estado
                ) VALUES (
                    :orden_compra_id, :proveedor_id, :numero_factura, :fecha_recepcion,
                    :recibido_por, :observaciones, :estado
                )
            ");
            $stmtRec->execute([
                'orden_compra_id' => $ordenCompraId > 0 ? $ordenCompraId : null,
                'proveedor_id' => $proveedorId,
                'numero_factura' => $numeroFactura,
                'fecha_recepcion' => $fechaRecepcion,
                'recibido_por' => $recibidoPor,
                'observaciones' => $observaciones,
                'estado' => $estado
            ]);
            $recepcionId = (int)$this->db->lastInsertId();

            // 2. Procesar cada ítem recibido
            $stmtDetRec = $this->db->prepare("
                INSERT INTO recepciones_compra_detalle (
                    recepcion_id, orden_compra_detalle_id, insumo_id,
                    cantidad_recibida, costo_unitario, lote, fecha_vencimiento
                ) VALUES (
                    :recepcion_id, :orden_compra_detalle_id, :insumo_id,
                    :cantidad_recibida, :costo_unitario, :lote, :fecha_vencimiento
                )
            ");

            $stmtUpdOcLine = $this->db->prepare("
                UPDATE ordenes_compra_detalle 
                SET cantidad_recibida = cantidad_recibida + :cant 
                WHERE id = :id
            ");

            $stmtInsumoLock = $this->db->prepare("SELECT cantidad FROM inventario_insumos WHERE id = :id FOR UPDATE");
            $stmtInsumoUpd = $this->db->prepare("UPDATE inventario_insumos SET cantidad = cantidad + :cant WHERE id = :id");

            $stmtLoteCheck = $this->db->prepare("SELECT id, saldo FROM inventario_lotes WHERE insumo_id = :insumo_id AND lote = :lote LIMIT 1");
            $stmtLoteUpd = $this->db->prepare("UPDATE inventario_lotes SET saldo = saldo + :cant, estado = 'activo' WHERE id = :id");
            $stmtLoteIns = $this->db->prepare("
                INSERT INTO inventario_lotes (
                    insumo_id, lote, fecha_vencimiento, costo_unitario, cantidad_inicial, saldo, estado
                ) VALUES (
                    :insumo_id, :lote, :fecha_vencimiento, :costo_unitario, :cant_inicial, :saldo, 'activo'
                )
            ");

            $stmtMovIns = $this->db->prepare("
                INSERT INTO inventario_movimientos (
                    insumo_id, lote_id, tipo_movimiento, cantidad, costo_unitario,
                    saldo_anterior, saldo_nuevo, referencia_tipo, referencia_id,
                    usuario_id, observaciones
                ) VALUES (
                    :insumo_id, :lote_id, 'entrada_compra', :cantidad, :costo_unitario,
                    :saldo_anterior, :saldo_nuevo, 'recepcion_compra', :referencia_id,
                    :usuario_id, :observaciones
                )
            ");

            foreach ($detalles as $det) {
                $insumoId = (int)($det['insumo_id'] ?? 0);
                $cantRec = (float)($det['cantidad_recibida'] ?? 0);
                $costo = (float)($det['costo_unitario'] ?? 0);
                $lote = !empty($det['lote']) ? trim($det['lote']) : null;
                $venc = !empty($det['fecha_vencimiento']) ? $det['fecha_vencimiento'] : null;
                $ocDetId = !empty($det['orden_compra_detalle_id']) ? (int)$det['orden_compra_detalle_id'] : null;

                if ($cantRec <= 0) {
                    continue;
                }

                // Guardar línea de recepción
                $stmtDetRec->execute([
                    'recepcion_id' => $recepcionId,
                    'orden_compra_detalle_id' => $ocDetId,
                    'insumo_id' => $insumoId,
                    'cantidad_recibida' => $cantRec,
                    'costo_unitario' => $costo,
                    'lote' => $lote,
                    'fecha_vencimiento' => $venc
                ]);

                // Actualizar línea de OC si aplica
                if ($ocDetId) {
                    $stmtUpdOcLine->execute([
                        'cant' => $cantRec,
                        'id' => $ocDetId
                    ]);
                }

                // Actualizar stock de insumo y kárdex si el insumo existe
                $saldoAnt = 0.0;
                $saldoNuevo = $cantRec;
                $loteId = null;

                if ($insumoId > 0) {
                    $stmtInsumoLock->execute(['id' => $insumoId]);
                    $currStock = $stmtInsumoLock->fetchColumn();
                    if ($currStock !== false && $currStock !== null) {
                        $saldoAnt = (float)$currStock;
                        $saldoNuevo = $saldoAnt + $cantRec;
                        $stmtInsumoUpd->execute([
                            'cant' => $cantRec,
                            'id' => $insumoId
                        ]);
                    }

                    // Manejo de lotes
                    if (!empty($lote)) {
                        $stmtLoteCheck->execute(['insumo_id' => $insumoId, 'lote' => $lote]);
                        $loteRow = $stmtLoteCheck->fetch(PDO::FETCH_ASSOC);
                        if ($loteRow) {
                            $loteId = (int)$loteRow['id'];
                            $stmtLoteUpd->execute([
                                'cant' => $cantRec,
                                'id' => $loteId
                            ]);
                        } else {
                            $stmtLoteIns->execute([
                                'insumo_id' => $insumoId,
                                'lote' => $lote,
                                'fecha_vencimiento' => $venc,
                                'costo_unitario' => $costo,
                                'cant_inicial' => $cantRec,
                                'saldo' => $cantRec
                            ]);
                            $loteId = (int)$this->db->lastInsertId();
                        }
                    }

                    // Registrar movimiento en Kárdex
                    $stmtMovIns->execute([
                        'insumo_id' => $insumoId,
                        'lote_id' => $loteId,
                        'cantidad' => $cantRec,
                        'costo_unitario' => $costo,
                        'saldo_anterior' => $saldoAnt,
                        'saldo_nuevo' => $saldoNuevo,
                        'referencia_id' => $recepcionId,
                        'usuario_id' => $recibidoPor,
                        'observaciones' => "Recepción compra #{$recepcionId} Factura: " . ($numeroFactura ?: 'S/N')
                    ]);
                }
            }

            // 3. Evaluar estado de la Orden de Compra (parcial o recibida total)
            if ($ordenCompraId > 0) {
                $stmtLines = $this->db->prepare("
                    SELECT cantidad, cantidad_recibida 
                    FROM ordenes_compra_detalle 
                    WHERE orden_compra_id = :id
                ");
                $stmtLines->execute(['id' => $ordenCompraId]);
                $todasLineas = $stmtLines->fetchAll(PDO::FETCH_ASSOC);

                $totalRequerido = 0.0;
                $totalRecibido = 0.0;
                $todasCompletas = true;

                foreach ($todasLineas as $linea) {
                    $pedida = (float)$linea['cantidad'];
                    $recibida = (float)$linea['cantidad_recibida'];
                    $totalRequerido += $pedida;
                    $totalRecibido += $recibida;

                    if ($recibida < $pedida) {
                        $todasCompletas = false;
                    }
                }

                if ($todasCompletas && count($todasLineas) > 0) {
                    $nuevoEstadoOc = 'RECIBIDA';
                } elseif ($totalRecibido > 0) {
                    $nuevoEstadoOc = 'RECIBIDA_PARCIAL';
                } else {
                    $nuevoEstadoOc = 'APROBADA';
                }

                $stmtOcStatus = $this->db->prepare("UPDATE ordenes_compra SET estado = :estado WHERE id = :id");
                $stmtOcStatus->execute([
                    'estado' => $nuevoEstadoOc,
                    'id' => $ordenCompraId
                ]);
            }

            $this->db->commit();
            return $recepcionId;
        } catch (Throwable $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en registrarRecepcionCompra: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Lista las recepciones registradas.
     *
     * @param string|array $filtro
     * @return array
     */
    public function listarRecepciones($filtro = ''): array {
        $sql = "SELECT rc.*, p.razon_social AS proveedor_nombre, p.identificacion AS proveedor_identificacion,
                       oc.id AS orden_compra_numero, u.nombre AS recibido_por_nombre
                FROM recepciones_compra rc
                LEFT JOIN proveedores p ON rc.proveedor_id = p.id
                LEFT JOIN ordenes_compra oc ON rc.orden_compra_id = oc.id
                LEFT JOIN usuarios u ON rc.recibido_por = u.id";

        $params = [];
        $where = [];

        if (is_array($filtro)) {
            if (!empty($filtro['busqueda'])) {
                $term = '%' . trim($filtro['busqueda']) . '%';
                $where[] = "(rc.id LIKE :q_id OR rc.numero_factura LIKE :q_fac OR p.razon_social LIKE :q_rs)";
                $params['q_id'] = $term;
                $params['q_fac'] = $term;
                $params['q_rs'] = $term;
            }
            if (!empty($filtro['proveedor_id'])) {
                $where[] = "rc.proveedor_id = :prov_id";
                $params['prov_id'] = (int)$filtro['proveedor_id'];
            }
            if (!empty($filtro['estado'])) {
                $where[] = "rc.estado = :estado";
                $params['estado'] = $filtro['estado'];
            }
        } elseif (is_string($filtro) && trim($filtro) !== '') {
            $term = '%' . trim($filtro) . '%';
            $where[] = "(rc.id LIKE :q1 OR rc.numero_factura LIKE :q2 OR p.razon_social LIKE :q3 OR rc.estado LIKE :q4)";
            $params['q1'] = $term;
            $params['q2'] = $term;
            $params['q3'] = $term;
            $params['q4'] = $term;
        }

        if (!empty($where)) {
            $sql .= " WHERE " . implode(' AND ', $where);
        }

        $sql .= " ORDER BY rc.id DESC";

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una recepción con sus detalles e información de insumos.
     *
     * @param int $id
     * @return array|null
     */
    public function obtenerRecepcionPorId($id): ?array {
        $stmt = $this->db->prepare("
            SELECT rc.*, p.razon_social AS proveedor_nombre, p.identificacion AS proveedor_identificacion,
                   p.contacto AS proveedor_contacto, p.telefono AS proveedor_telefono,
                   oc.id AS orden_compra_numero, u.nombre AS recibido_por_nombre
            FROM recepciones_compra rc
            LEFT JOIN proveedores p ON rc.proveedor_id = p.id
            LEFT JOIN ordenes_compra oc ON rc.orden_compra_id = oc.id
            LEFT JOIN usuarios u ON rc.recibido_por = u.id
            WHERE rc.id = :id
            LIMIT 1
        ");
        $stmt->execute(['id' => (int)$id]);
        $rec = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$rec) {
            return null;
        }

        $stmtDet = $this->db->prepare("
            SELECT rcd.*, ins.nombre AS insumo_nombre, ins.codigo AS insumo_codigo, ins.unidad_medida
            FROM recepciones_compra_detalle rcd
            LEFT JOIN inventario_insumos ins ON rcd.insumo_id = ins.id
            WHERE rcd.recepcion_id = :id
            ORDER BY rcd.id ASC
        ");
        $stmtDet->execute(['id' => (int)$id]);
        $rec['detalles'] = $stmtDet->fetchAll(PDO::FETCH_ASSOC);

        return $rec;
    }

    // =========================================================================
    // 4. MÉTODOS DE APOYO PARA VISTAS Y COMBOS
    // =========================================================================

    /**
     * Lista insumos disponibles para selección en órdenes de compra.
     */
    public function listarInsumosActivos(): array {
        try {
            $stmt = $this->db->query("SELECT id, codigo, nombre, unidad_medida, cantidad AS stock_actual FROM inventario_insumos ORDER BY nombre ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }

    /**
     * Lista cuentas contables del catálogo para selección.
     */
    public function listarCuentasDetalle(): array {
        try {
            $stmt = $this->db->query("SELECT id, codigo, nombre FROM cuentas_contables WHERE tipo = 'DETALLE' AND activo = 1 ORDER BY codigo ASC");
            return $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (Throwable $e) {
            return [];
        }
    }
}
