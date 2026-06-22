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
        $sql = "SELECT cd.*, p.codigo_servicio, p.norma_astm, p.tipo_muestra, f.codigo_formato AS formato_reporte
                FROM cotizacion_detalles cd
                LEFT JOIN productos p ON cd.id_producto = p.id
                LEFT JOIN formatos_ensayos f ON p.formato_id = f.id
                WHERE cd.id_cotizacion = :id 
                ORDER BY cd.id ASC";
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

            $sqlDetalle = "INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, cantidad, precio_unitario, subtotal) VALUES (:id_cotizacion, :id_producto, :descripcion, :cantidad, :precio, :subtotal)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);
            foreach ($detalles as $detalle) {
                $detalle['id_cotizacion'] = $idCotizacion;
                $stmtDetalle->execute($detalle);
            }

            // Guardar instantánea (Versión 1)
            $verStmt = $this->db->prepare("SELECT * FROM cotizaciones WHERE id = :id");
            $verStmt->execute(['id' => $idCotizacion]);
            $cot = $verStmt->fetch(PDO::FETCH_ASSOC);

            $snapshot = [
                'atencion_a' => $cot['atencion_a'],
                'nombre_proyecto' => $cot['nombre_proyecto'],
                'direccion_proyecto' => $cot['direccion_proyecto'],
                'prioridad' => $cot['prioridad'],
                'fecha_limite' => $cot['fecha_limite'],
                'condicion_pago' => $cot['condicion_pago'],
                'tiempo_entrega' => $cot['tiempo_entrega'],
                'vigencia_oferta' => $cot['vigencia_oferta'],
                'subtotal' => $cot['subtotal'],
                'impuesto' => $cot['impuesto'],
                'total' => $cot['total'],
                'detalles' => []
            ];
            foreach ($detalles as $d) {
                $snapshot['detalles'][] = [
                    'id_producto' => $d['id_producto'],
                    'descripcion_ensayo' => $d['descripcion'],
                    'cantidad' => $d['cantidad'],
                    'precio_unitario' => $d['precio'],
                    'subtotal' => $d['subtotal']
                ];
            }
            $insStmt = $this->db->prepare("INSERT INTO cotizacion_versiones (id_cotizacion, version, datos_json, motivo_cambio) VALUES (:id_cotizacion, 1, :datos_json, 'Creación de cotización')");
            $insStmt->execute([
                'id_cotizacion' => $idCotizacion,
                'datos_json' => json_encode($snapshot)
            ]);

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
            $sqlDetalle = "INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, cantidad, precio_unitario, subtotal) VALUES (:id_cotizacion, :id_producto, :descripcion, :cantidad, :precio, :subtotal)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);
            foreach ($detalles as $detalle) {
                $detalle['id_cotizacion'] = $id;
                $stmtDetalle->execute($detalle);
            }

            // Guardar instantánea de la nueva versión
            $verStmt = $this->db->prepare("SELECT * FROM cotizaciones WHERE id = :id");
            $verStmt->execute(['id' => $id]);
            $cot = $verStmt->fetch(PDO::FETCH_ASSOC);

            $snapshot = [
                'atencion_a' => $cot['atencion_a'],
                'nombre_proyecto' => $cot['nombre_proyecto'],
                'direccion_proyecto' => $cot['direccion_proyecto'],
                'prioridad' => $cot['prioridad'],
                'fecha_limite' => $cot['fecha_limite'],
                'condicion_pago' => $cot['condicion_pago'],
                'tiempo_entrega' => $cot['tiempo_entrega'],
                'vigencia_oferta' => $cot['vigencia_oferta'],
                'subtotal' => $cot['subtotal'],
                'impuesto' => $cot['impuesto'],
                'total' => $cot['total'],
                'detalles' => []
            ];
            foreach ($detalles as $d) {
                $snapshot['detalles'][] = [
                    'id_producto' => $d['id_producto'],
                    'descripcion_ensayo' => $d['descripcion'],
                    'cantidad' => $d['cantidad'],
                    'precio_unitario' => $d['precio'],
                    'subtotal' => $d['subtotal']
                ];
            }
            $insStmt = $this->db->prepare("INSERT INTO cotizacion_versiones (id_cotizacion, version, datos_json, motivo_cambio) VALUES (:id_cotizacion, :version, :datos_json, 'Corrección de cotización')");
            $insStmt->execute([
                'id_cotizacion' => $id,
                'version' => $cot['version'],
                'datos_json' => json_encode($snapshot)
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) { $this->db->rollBack(); return false; }
    }

    // Obtener historial de versiones
    public function obtenerVersiones(int $id_cotizacion): array {
        $sql = "SELECT * FROM cotizacion_versiones WHERE id_cotizacion = :id ORDER BY version DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id_cotizacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
}