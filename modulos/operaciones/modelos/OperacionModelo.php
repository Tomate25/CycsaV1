<?php

namespace Cycsa\Modulos\Operaciones\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;

class OperacionModelo extends ModeloBase {

    /**
     * Obtiene las cotizaciones aprobadas por el cliente junto con su información operativa.
     */
    public function obtenerOperaciones(string $busqueda = ''): array {
        $sql = "SELECT cot.id AS id_cotizacion, cot.codigo AS cot_codigo, cot.atencion_a, 
                       cot.nombre_proyecto, cot.direccion_proyecto, cot.fecha_creacion, cot.prioridad,
                       cot.fecha_entrega, cot.fecha_seguimiento, cot.estado_operativo, cot.notas_operativas,
                       cli.nombre_razon_social AS cliente_nombre
                FROM cotizaciones cot
                JOIN clientes cli ON cot.id_cliente = cli.id
                WHERE cot.estado = 'Aprobada por Cliente'";

        if ($busqueda !== '') {
            $sql .= " AND (cot.codigo LIKE :q1 
                        OR cot.nombre_proyecto LIKE :q2 
                        OR cli.nombre_razon_social LIKE :q3 
                        OR cot.estado_operativo LIKE :q4)
                     ORDER BY cot.id DESC";
            $stmt = $this->db->prepare($sql);
            $termino = '%' . trim($busqueda) . '%';
            $stmt->execute([
                'q1' => $termino,
                'q2' => $termino,
                'q3' => $termino,
                'q4' => $termino
            ]);
        } else {
            $sql .= " ORDER BY cot.id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Obtiene una sola cotización operativa por su ID.
     */
    public function obtenerOperacionPorId(int $idCotizacion): ?array {
        $sql = "SELECT cot.id AS id_cotizacion, cot.codigo AS cot_codigo, cot.atencion_a, 
                       cot.nombre_proyecto, cot.direccion_proyecto, cot.fecha_creacion, 
                       cot.prioridad, cot.tiempo_entrega, cot.vigencia_oferta, cot.condicion_pago,
                       cot.fecha_entrega, cot.fecha_seguimiento, cot.estado_operativo, cot.notas_operativas,
                       cli.nombre_razon_social AS cliente_nombre, cli.telefono AS cliente_telefono, cli.email AS cliente_email
                FROM cotizaciones cot
                JOIN clientes cli ON cot.id_cliente = cli.id
                WHERE cot.id = :id AND cot.estado = 'Aprobada por Cliente'";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $idCotizacion]);
        $res = $stmt->fetch(PDO::FETCH_ASSOC);
        return $res ?: null;
    }

    /**
     * Obtiene los ensayos/productos de una cotización sin incluir los precios comerciales.
     */
    public function obtenerDetallesCotizacion(int $idCotizacion): array {
        $sql = "SELECT cd.id, cd.descripcion_ensayo, cd.cantidad, p.codigo_servicio, p.norma_astm 
                FROM cotizacion_detalles cd
                LEFT JOIN productos p ON cd.id_producto = p.id
                WHERE cd.id_cotizacion = :id_cotizacion";
        
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_cotizacion' => $idCotizacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Guarda o actualiza los datos operativos directamente en la cotización.
     */
    public function guardarFechasOperacion(int $idCotizacion, ?string $fechaEntrega, ?string $fechaSeguimiento, string $estadoOperativo, ?string $notasOperativas): bool {
        $fEntrega = !empty($fechaEntrega) ? $fechaEntrega : null;
        $fSeguimiento = !empty($fechaSeguimiento) ? $fechaSeguimiento : null;
        $notas = !empty(trim($notasOperativas)) ? trim($notasOperativas) : null;

        $sql = "UPDATE cotizaciones 
                SET fecha_entrega = :fecha_entrega, 
                    fecha_seguimiento = :fecha_seguimiento, 
                    estado_operativo = :estado_operativo, 
                    notas_operativas = :notas_operativas 
                WHERE id = :id_cotizacion";

        $stmtSave = $this->db->prepare($sql);
        return $stmtSave->execute([
            'id_cotizacion' => $idCotizacion,
            'fecha_entrega' => $fEntrega,
            'fecha_seguimiento' => $fSeguimiento,
            'estado_operativo' => $estadoOperativo,
            'notas_operativas' => $notas
        ]);
    }

    /**
     * Obtiene los eventos para el calendario en un rango de fechas.
     */
    public function obtenerEventosCalendario(string $inicio, string $fin): array {
        // Obtener eventos de entrega
        $sqlEntregas = "SELECT cot.id AS id_cotizacion, cot.codigo AS cot_codigo, cot.nombre_proyecto, 
                               cot.fecha_entrega AS fecha_evento, 'entrega' AS tipo_evento, cot.estado_operativo,
                               cli.nombre_razon_social AS cliente_nombre
                        FROM cotizaciones cot
                        JOIN clientes cli ON cot.id_cliente = cli.id
                        WHERE cot.estado = 'Aprobada por Cliente' AND cot.fecha_entrega BETWEEN :ini1 AND :fin1";
        
        $stmt = $this->db->prepare($sqlEntregas);
        $stmt->execute(['ini1' => $inicio, 'fin1' => $fin]);
        $entregas = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Obtener eventos de seguimiento
        $sqlSeguimientos = "SELECT cot.id AS id_cotizacion, cot.codigo AS cot_codigo, cot.nombre_proyecto, 
                                   cot.fecha_seguimiento AS fecha_evento, 'seguimiento' AS tipo_evento, cot.estado_operativo,
                                   cli.nombre_razon_social AS cliente_nombre
                            FROM cotizaciones cot
                            JOIN clientes cli ON cot.id_cliente = cli.id
                            WHERE cot.estado = 'Aprobada por Cliente' AND cot.fecha_seguimiento BETWEEN :ini2 AND :fin2";
        
        $stmt = $this->db->prepare($sqlSeguimientos);
        $stmt->execute(['ini2' => $inicio, 'fin2' => $fin]);
        $seguimientos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        return array_merge($entregas, $seguimientos);
    }
}
