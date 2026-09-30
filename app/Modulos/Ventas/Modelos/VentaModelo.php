<?php

namespace Cycsa\Modulos\Ventas\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;

class VentaModelo extends ModeloBase {
    public function obtenerOrdenes(string $busqueda = '', string $estado = 'pendientes'): array {
        $sql = "SELECT os.id, os.codigo_os, os.estado AS estado_os, os.fecha_emision,
                       cot.id AS id_cotizacion, cot.codigo AS cot_codigo, cot.total AS cot_total,
                       cot.condicion_pago, cot.nombre_proyecto,
                       cli.id AS cliente_id, cli.nombre_razon_social AS cliente_nombre,
                       cxc.id AS cxc_id, cxc.factura_numero, cxc.monto AS cxc_monto,
                       cxc.saldo, cxc.estado AS estado_pago, cxc.fecha_emision AS fecha_factura,
                       cxc.fecha_vencimiento, cxc.notas
                FROM ordenes_servicio os
                JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                JOIN clientes cli ON cot.id_cliente = cli.id
                LEFT JOIN cuentas_por_cobrar cxc
                    ON cxc.factura_numero = CONCAT('FAC-', cot.codigo)";

        $where = [];
        $params = [];
        if ($busqueda !== '') {
            $where[] = "(os.codigo_os LIKE :q1 OR cot.codigo LIKE :q2 OR cli.nombre_razon_social LIKE :q3 OR cxc.factura_numero LIKE :q4)";
            $term = '%' . trim($busqueda) . '%';
            $params = ['q1' => $term, 'q2' => $term, 'q3' => $term, 'q4' => $term];
        }

        if ($estado === 'pagadas') {
            $where[] = "cxc.estado = 'Pagado' AND cxc.saldo <= 0.01";
        } elseif ($estado === 'parciales') {
            $where[] = "cxc.estado = 'Parcial' AND cxc.saldo > 0.01";
        } elseif ($estado === 'pendientes') {
            $where[] = "(cxc.id IS NULL OR cxc.estado <> 'Pagado' OR cxc.saldo > 0.01)";
        }

        if ($where !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $where);
        }
        $sql .= ' ORDER BY os.id DESC';

        $stmt = $this->db->prepare($sql);
        $stmt->execute($params);
        $ordenes = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($ordenes as &$orden) {
            $orden['factura_numero'] = $orden['factura_numero'] ?: ('FAC-' . $orden['cot_codigo']);
            $orden['monto_factura'] = (float)($orden['cxc_monto'] ?? $orden['cot_total'] ?? 0);
            $orden['saldo'] = $orden['cxc_id'] ? (float)$orden['saldo'] : (float)$orden['cot_total'];
            $orden['estado_pago'] = $orden['estado_pago'] ?: 'Pendiente';
        }
        unset($orden);

        return $ordenes;
    }

    public function obtenerConteos(): array {
        $sql = "SELECT
                    COUNT(*) AS total,
                    SUM(CASE WHEN cxc.estado = 'Pagado' AND cxc.saldo <= 0.01 THEN 1 ELSE 0 END) AS pagadas,
                    SUM(CASE WHEN cxc.estado = 'Parcial' AND cxc.saldo > 0.01 THEN 1 ELSE 0 END) AS parciales,
                    SUM(CASE WHEN cxc.id IS NULL OR cxc.estado <> 'Pagado' OR cxc.saldo > 0.01 THEN 1 ELSE 0 END) AS pendientes
                FROM ordenes_servicio os
                JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                LEFT JOIN cuentas_por_cobrar cxc ON cxc.factura_numero = CONCAT('FAC-', cot.codigo)";
        $fila = $this->db->query($sql)->fetch(PDO::FETCH_ASSOC) ?: [];
        return [
            'todas' => (int)($fila['total'] ?? 0),
            'pendientes' => (int)($fila['pendientes'] ?? 0),
            'parciales' => (int)($fila['parciales'] ?? 0),
            'pagadas' => (int)($fila['pagadas'] ?? 0),
        ];
    }

    public function obtenerBancosActivos(): array {
        $stmt = $this->db->query("SELECT id, banco_nombre, numero_cuenta, moneda, saldo_actual, id_cuenta_contable
                                  FROM bancos_cuentas WHERE activo = 1 ORDER BY banco_nombre ASC");
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}
