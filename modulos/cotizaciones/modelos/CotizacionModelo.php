<?php

namespace Cycsa\Modulos\Cotizaciones\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;
use Exception;

class CotizacionModelo extends ModeloBase {
    
    public function obtenerTodas(string $busqueda = ''): array {
        $sql = "SELECT c.id, c.codigo, c.version, c.estado, c.total, c.fecha_creacion, 
                       c.id_usuario_creador,
                       cl.nombre_razon_social AS cliente, 
                       u.nombre AS creador
                FROM cotizaciones c
                INNER JOIN clientes cl ON c.id_cliente = cl.id
                INNER JOIN usuarios u ON c.id_usuario_creador = u.id ";
                
        if ($busqueda !== '') {
            $sql .= "WHERE c.codigo LIKE :q1 OR cl.nombre_razon_social LIKE :q2 OR c.estado LIKE :q3 ";
            $sql .= "ORDER BY c.id DESC";
            $stmt = $this->db->prepare($sql);
            $termino = '%' . trim($busqueda) . '%';
            $stmt->execute(['q1' => $termino, 'q2' => $termino, 'q3' => $termino]);
        } else {
            $sql .= "ORDER BY c.id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerPorId(int $id) {
        $sql = "SELECT c.*, cl.nombre_razon_social AS cliente_nombre, cl.identificacion AS cliente_ruc, cl.email AS cliente_email, cl.telefono AS cliente_tel, u.nombre AS creador_nombre, u.email AS creador_email 
                FROM cotizaciones c 
                INNER JOIN clientes cl ON c.id_cliente = cl.id 
                INNER JOIN usuarios u ON c.id_usuario_creador = u.id 
                WHERE c.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerDetalles(int $id_cotizacion): array {
        $sql = "SELECT * FROM cotizacion_detalles WHERE id_cotizacion = :id ORDER BY id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id_cotizacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generarCodigoUnico(): string {
        $año = date('Y');
        $sql = "SELECT COUNT(*) FROM cotizaciones WHERE codigo LIKE :anio";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['anio' => "COT-{$año}-%"]);
        return "COT-{$año}-" . str_pad((string)((int)$stmt->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);
    }

    public function actualizarEstado(int $id, string $estado, int $id_revisor, string $motivo = null, string $token = null): bool {
        $sql = "UPDATE cotizaciones SET estado = :estado, id_usuario_revisor = :revisor, motivo_observacion = :motivo, token_seguridad = :token WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['estado' => $estado, 'revisor' => $id_revisor, 'motivo' => $motivo, 'token' => $token, 'id' => $id]);
    }

    public function registrarDecisionCliente(int $id, string $estado, ?string $motivo = null): bool {
        $sql = "UPDATE cotizaciones SET estado = :estado, motivo_rechazo_cliente = :motivo, fecha_actualizacion = NOW() WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute(['estado' => $estado, 'motivo' => $motivo, 'id' => $id]);
    }

    // Guardar (Nuevo)
    public function guardarCotizacionCompleta(array $cabecera, array $detalles): bool {
        try {
            $this->db->beginTransaction();
            $sqlCabecera = "INSERT INTO cotizaciones (codigo, id_cliente, id_usuario_creador, atencion_a, nombre_proyecto, direccion_proyecto, prioridad, fecha_limite, condicion_pago, tiempo_entrega, vigencia_oferta, configuracion_notas, subtotal, impuesto, total, estado) VALUES (:codigo, :id_cliente, :id_usuario_creador, :atencion_a, :nombre_proyecto, :direccion_proyecto, :prioridad, :fecha_limite, :condicion_pago, :tiempo_entrega, :vigencia_oferta, :configuracion_notas, :subtotal, :impuesto, :total, 'Borrador')";
            $stmtCabecera = $this->db->prepare($sqlCabecera);
            $stmtCabecera->execute($cabecera);
            $idCotizacion = $this->db->lastInsertId();

            $sqlDetalle = "INSERT INTO cotizacion_detalles (id_cotizacion, descripcion_ensayo, cantidad, precio_unitario, subtotal) VALUES (:id_cotizacion, :descripcion, :cantidad, :precio, :subtotal)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);
            foreach ($detalles as $detalle) {
                $detalle['id_cotizacion'] = $idCotizacion;
                $stmtDetalle->execute($detalle);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) { $this->db->rollBack(); return false; }
    }

    // Actualizar (Corrección)
    public function actualizarCotizacionCompleta(int $id, array $cabecera, array $detalles): bool {
        try {
            $this->db->beginTransaction();
            $sqlCabecera = "UPDATE cotizaciones SET version = version + 1, estado = 'En Revision', atencion_a = :atencion_a, nombre_proyecto = :nombre_proyecto, direccion_proyecto = :direccion_proyecto, condicion_pago = :condicion_pago, tiempo_entrega = :tiempo_entrega, vigencia_oferta = :vigencia_oferta, subtotal = :subtotal, impuesto = :impuesto, total = :total WHERE id = :id";
            $stmtCabecera = $this->db->prepare($sqlCabecera);
            $cabecera['id'] = $id;
            $stmtCabecera->execute($cabecera);

            $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = :id")->execute(['id' => $id]);
            $sqlDetalle = "INSERT INTO cotizacion_detalles (id_cotizacion, descripcion_ensayo, cantidad, precio_unitario, subtotal) VALUES (:id_cotizacion, :descripcion, :cantidad, :precio, :subtotal)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);
            foreach ($detalles as $detalle) {
                $detalle['id_cotizacion'] = $id;
                $stmtDetalle->execute($detalle);
            }
            $this->db->commit();
            return true;
        } catch (Exception $e) { $this->db->rollBack(); return false; }
    }
}