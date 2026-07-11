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
        $sql = "SELECT cd.id, cd.id_cotizacion, cd.id_producto, cd.descripcion_ensayo, cd.cantidad, cd.precio_unitario, cd.subtotal, cd.resultados_json,
                       COALESCE(p.codigo_servicio, cd.codigo_servicio) AS codigo_servicio,
                       COALESCE(cd.norma_astm, p.norma_astm) AS norma_astm,
                       COALESCE(cd.formato_reporte, f.codigo_formato) AS formato_reporte,
                       COALESCE(cd.observaciones, p.observaciones) AS observaciones,
                       p.tipo_muestra, f.archivo_markdown
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
        // Consultar el estado actual y motivo_rechazo_cliente para saber si amerita incremento de versión
        $sqlCheck = "SELECT version, motivo_rechazo_cliente FROM cotizaciones WHERE id = :id";
        $stmtCheck = $this->db->prepare($sqlCheck);
        $stmtCheck->execute(['id' => $id]);
        $cot = $stmtCheck->fetch(PDO::FETCH_ASSOC);

        $sql = "UPDATE cotizaciones SET estado = :estado, id_usuario_revisor = :revisor, motivo_observacion = :motivo, token_seguridad = :token";
        
        $params = ['estado' => $estado, 'revisor' => $id_revisor, 'motivo' => $motivo, 'token' => $token, 'id' => $id];
        
        if ($estado === 'Enviada al Cliente' && $cot && !empty($cot['motivo_rechazo_cliente'])) {
            $sql .= ", version = version + 1, motivo_rechazo_cliente = NULL";
        }
        
        $sql .= " WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute($params);
    }    public function registrarDecisionCliente(
        int $id, 
        string $estado, 
        ?string $motivo = null, 
        ?string $metodoPago = null, 
        ?int $idBancoCuenta = null, 
        ?string $referenciaPago = null,
        float $porcentajePagoInmediato = 100.00,
        float $montoPagoInmediato = 0.00,
        float $montoCredito = 0.00,
        ?float $efectivoRecibido = null,
        ?float $efectivoVuelto = null,
        int $diasCredito = 30
    ): bool {
        try {
            $this->db->beginTransaction();

            // 1. Actualizar el estado de la cotización
            $sql = "UPDATE cotizaciones 
                    SET estado = :estado, 
                        motivo_rechazo_cliente = :motivo, 
                        fecha_actualizacion = NOW() 
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'estado' => $estado, 
                'motivo' => $motivo, 
                'id' => $id
            ]);

            // 2. Si el estado es 'Aprobada por Cliente', crear Orden de Servicio automáticamente
            if ($estado === 'Aprobada por Cliente') {
                // Verificar si ya existe una O/S para esta cotización
                $stmtCheck = $this->db->prepare("SELECT id FROM ordenes_servicio WHERE id_cotizacion = :id_cot");
                $stmtCheck->execute(['id_cot' => $id]);
                if (!$stmtCheck->fetch()) {
                    $anio = (int)date('Y');
                    $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM ordenes_servicio WHERE YEAR(fecha_emision) = :anio");
                    $stmtCount->execute(['anio' => $anio]);
                    $consecutivo = (int)$stmtCount->fetchColumn() + 1;
                    $codigoOS = sprintf("OS-%d-%04d", $anio, $consecutivo);

                    $sqlOS = "INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, tipo_contrato, fecha_emision, estado) 
                              VALUES (:codigo_os, :id_cotizacion, 'Puntual', CURRENT_DATE, 'Estado 1: Recepcion')";
                    $stmtOS = $this->db->prepare($sqlOS);
                    $stmtOS->execute([
                        'codigo_os' => $codigoOS,
                        'id_cotizacion' => $id
                    ]);
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            error_log("Error en registrarDecisionCliente: " . $e->getMessage());
            return false;
        }
    }

    // Guardar (Nuevo)
    public function guardarCotizacionCompleta(array $cabecera, array $detalles): bool {
        try {
            $this->db->beginTransaction();
            $sqlCabecera = "INSERT INTO cotizaciones (codigo, id_cliente, tipo_moneda, id_usuario_creador, atencion_a, nombre_proyecto, direccion_proyecto, prioridad, fecha_limite, condicion_pago, tiempo_entrega, vigencia_oferta, configuracion_notas, contactos, subtotal, descuento, exonerado, exoneracion_no, impuesto, total, estado, version, fecha_entrega, fecha_seguimiento) VALUES (:codigo, :id_cliente, :tipo_moneda, :id_usuario_creador, :atencion_a, :nombre_proyecto, :direccion_proyecto, :prioridad, :fecha_limite, :condicion_pago, :tiempo_entrega, :vigencia_oferta, :configuracion_notas, :contactos, :subtotal, :descuento, :exonerado, :exoneracion_no, :impuesto, :total, 'Borrador', 0, :fecha_entrega, :fecha_seguimiento)";
            $stmtCabecera = $this->db->prepare($sqlCabecera);
            $stmtCabecera->execute($cabecera);
            $idCotizacion = $this->db->lastInsertId();

            $sqlDetalle = "INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, formato_reporte, observaciones, cantidad, precio_unitario, subtotal) VALUES (:id_cotizacion, :id_producto, :descripcion, :codigo_servicio, :norma_astm, :formato_reporte, :observaciones, :cantidad, :precio, :subtotal)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);
            foreach ($detalles as $detalle) {
                $stmtDetalle->execute([
                    'id_cotizacion' => $idCotizacion,
                    'id_producto' => $detalle['id_producto'],
                    'descripcion' => $detalle['descripcion'],
                    'codigo_servicio' => $detalle['codigo_servicio'] ?? null,
                    'norma_astm' => $detalle['norma_astm'] ?? null,
                    'formato_reporte' => $detalle['formato_reporte'] ?? null,
                    'observaciones' => $detalle['observaciones'] ?? null,
                    'cantidad' => $detalle['cantidad'],
                    'precio' => $detalle['precio'],
                    'subtotal' => $detalle['subtotal']
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) { $this->db->rollBack(); return false; }
    }

    // Actualizar (Corrección)
    public function actualizarCotizacionCompleta(int $id, array $cabecera, array $detalles): bool {
        try {
            $this->db->beginTransaction();

            // 1. Obtener la cotización actual antes de sobrescribirla
            $oldStmt = $this->db->prepare("SELECT * FROM cotizaciones WHERE id = :id");
            $oldStmt->execute(['id' => $id]);
            $oldCot = $oldStmt->fetch(PDO::FETCH_ASSOC);

            // 2. Si el estado actual es 'Rechazada por Cliente', guardamos la versión histórica (versión anterior)
            $token = $oldCot['token_seguridad'] ?? null;
            $nuevoEstado = 'En Revision';
            $nuevaVersion = $oldCot['version'] ?? 0;
            $motivoRechazo = $oldCot['motivo_rechazo_cliente'] ?? null;

            if ($oldCot && $oldCot['estado'] === 'Rechazada por Cliente') {
                $detStmt = $this->db->prepare("SELECT * FROM cotizacion_detalles WHERE id_cotizacion = :id");
                $detStmt->execute(['id' => $id]);
                $oldDets = $detStmt->fetchAll(PDO::FETCH_ASSOC);

                $snapshot = [
                    'atencion_a' => $oldCot['atencion_a'],
                    'nombre_proyecto' => $oldCot['nombre_proyecto'],
                    'direccion_proyecto' => $oldCot['direccion_proyecto'],
                    'prioridad' => $oldCot['prioridad'],
                    'fecha_limite' => $oldCot['fecha_limite'],
                    'condicion_pago' => $oldCot['condicion_pago'],
                    'tiempo_entrega' => $oldCot['tiempo_entrega'],
                    'vigencia_oferta' => $oldCot['vigencia_oferta'],
                    'subtotal' => $oldCot['subtotal'],
                    'impuesto' => $oldCot['impuesto'],
                    'total' => $oldCot['total'],
                    'fecha_entrega' => $oldCot['fecha_entrega'] ?? null,
                    'fecha_seguimiento' => $oldCot['fecha_seguimiento'] ?? null,
                    'detalles' => []
                ];
                foreach ($oldDets as $d) {
                    $snapshot['detalles'][] = [
                        'id_producto' => $d['id_producto'],
                        'descripcion_ensayo' => $d['descripcion_ensayo'],
                        'codigo_servicio' => $d['codigo_servicio'] ?? null,
                        'norma_astm' => $d['norma_astm'] ?? null,
                        'formato_reporte' => $d['formato_reporte'] ?? null,
                        'observaciones' => $d['observaciones'] ?? null,
                        'cantidad' => $d['cantidad'],
                        'precio_unitario' => $d['precio_unitario'],
                        'subtotal' => $d['subtotal']
                    ];
                }

                $insStmt = $this->db->prepare("INSERT INTO cotizacion_versiones (id_cotizacion, version, datos_json, motivo_cambio) VALUES (:id_cotizacion, :version, :datos_json, :motivo)");
                $motivoCambio = 'Devuelta por cliente: ' . ($oldCot['motivo_rechazo_cliente'] ?? 'Rechazo');
                $insStmt->execute([
                    'id_cotizacion' => $id,
                    'version' => $oldCot['version'],
                    'datos_json' => json_encode($snapshot),
                    'motivo' => $motivoCambio
                ]);

                // Si el cliente la rechazó, al corregirla se envía directamente al cliente de nuevo
                $nuevaVersion = $oldCot['version'] + 1;
                $nuevoEstado = 'Enviada al Cliente';
                $token = bin2hex(random_bytes(32));
                $motivoRechazo = null; // Se limpia la observación/motivo de rechazo de la versión vieja
            }

            // 3. Sobrescribir los datos de la cotización actual
            $sqlCabecera = "UPDATE cotizaciones SET id_cliente = :id_cliente, tipo_moneda = :tipo_moneda, estado = :estado, version = :version, token_seguridad = :token, motivo_rechazo_cliente = :motivo_rechazo, atencion_a = :atencion_a, nombre_proyecto = :nombre_proyecto, direccion_proyecto = :direccion_proyecto, condicion_pago = :condicion_pago, tiempo_entrega = :tiempo_entrega, vigencia_oferta = :vigencia_oferta, configuracion_notas = :configuracion_notas, contactos = :contactos, subtotal = :subtotal, descuento = :descuento, exonerado = :exonerado, exoneracion_no = :exoneracion_no, impuesto = :impuesto, total = :total, fecha_entrega = :fecha_entrega, fecha_seguimiento = :fecha_seguimiento WHERE id = :id";
            $stmtCabecera = $this->db->prepare($sqlCabecera);
            $stmtCabecera->execute(array_merge($cabecera, [
                'id' => $id,
                'estado' => $nuevoEstado,
                'version' => $nuevaVersion,
                'token' => $token,
                'motivo_rechazo' => $motivoRechazo
            ]));

            // 4. Eliminar los detalles antiguos para guardar los corregidos
            $delStmt = $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = :id");
            $delStmt->execute(['id' => $id]);

            $sqlDetalle = "INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, codigo_servicio, norma_astm, formato_reporte, observaciones, cantidad, precio_unitario, subtotal) VALUES (:id_cotizacion, :id_producto, :descripcion, :codigo_servicio, :norma_astm, :formato_reporte, :observaciones, :cantidad, :precio, :subtotal)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);
            foreach ($detalles as $detalle) {
                $stmtDetalle->execute([
                    'id_cotizacion' => $id,
                    'id_producto' => $detalle['id_producto'],
                    'descripcion' => $detalle['descripcion'],
                    'codigo_servicio' => $detalle['codigo_servicio'] ?? null,
                    'norma_astm' => $detalle['norma_astm'] ?? null,
                    'formato_reporte' => $detalle['formato_reporte'] ?? null,
                    'observaciones' => $detalle['observaciones'] ?? null,
                    'cantidad' => $detalle['cantidad'],
                    'precio' => $detalle['precio'],
                    'subtotal' => $detalle['subtotal']
                ]);
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) { $this->db->rollBack(); return false; }
    }

    // Re-enviar una cotización rechazada por el cliente (creando una nueva versión sin cambios manuales en la edición)
    public function volverEnviarRechazada(int $id): bool {
        try {
            $this->db->beginTransaction();

            // 1. Obtener la cotización actual
            $oldStmt = $this->db->prepare("SELECT * FROM cotizaciones WHERE id = :id");
            $oldStmt->execute(['id' => $id]);
            $oldCot = $oldStmt->fetch(PDO::FETCH_ASSOC);

            if (!$oldCot || $oldCot['estado'] !== 'Rechazada por Cliente') {
                $this->db->rollBack();
                return false;
            }

            // 2. Obtener detalles de la cotización actual
            $detStmt = $this->db->prepare("SELECT * FROM cotizacion_detalles WHERE id_cotizacion = :id");
            $detStmt->execute(['id' => $id]);
            $oldDets = $detStmt->fetchAll(PDO::FETCH_ASSOC);

            // 3. Crear el snapshot de la versión que el cliente rechazó
            $snapshot = [
                'atencion_a' => $oldCot['atencion_a'],
                'nombre_proyecto' => $oldCot['nombre_proyecto'],
                'direccion_proyecto' => $oldCot['direccion_proyecto'],
                'prioridad' => $oldCot['prioridad'],
                'fecha_limite' => $oldCot['fecha_limite'],
                'condicion_pago' => $oldCot['condicion_pago'],
                'tiempo_entrega' => $oldCot['tiempo_entrega'],
                'vigencia_oferta' => $oldCot['vigencia_oferta'],
                'subtotal' => $oldCot['subtotal'],
                'impuesto' => $oldCot['impuesto'],
                'total' => $oldCot['total'],
                'fecha_entrega' => $oldCot['fecha_entrega'] ?? null,
                'fecha_seguimiento' => $oldCot['fecha_seguimiento'] ?? null,
                'detalles' => []
            ];
            foreach ($oldDets as $d) {
                $snapshot['detalles'][] = [
                    'id_producto' => $d['id_producto'],
                    'descripcion_ensayo' => $d['descripcion_ensayo'],
                    'codigo_servicio' => $d['codigo_servicio'] ?? null,
                    'norma_astm' => $d['norma_astm'] ?? null,
                    'formato_reporte' => $d['formato_reporte'] ?? null,
                    'observaciones' => $d['observaciones'] ?? null,
                    'cantidad' => $d['cantidad'],
                    'precio_unitario' => $d['precio_unitario'],
                    'subtotal' => $d['subtotal']
                ];
            }

            // 4. Guardar en cotizacion_versiones
            $insStmt = $this->db->prepare("INSERT INTO cotizacion_versiones (id_cotizacion, version, datos_json, motivo_cambio) VALUES (:id_cotizacion, :version, :datos_json, :motivo)");
            $motivoCambio = 'Devuelta por cliente: ' . ($oldCot['motivo_rechazo_cliente'] ?? 'Rechazo');
            $insStmt->execute([
                'id_cotizacion' => $id,
                'version' => $oldCot['version'],
                'datos_json' => json_encode($snapshot),
                'motivo' => $motivoCambio
            ]);

            // 5. Actualizar la cotización actual a 'Enviada al Cliente', incrementando la versión y limpiando rechazo
            $nuevaVersion = $oldCot['version'] + 1;
            $nuevoToken = bin2hex(random_bytes(32));

            $sqlUpd = "UPDATE cotizaciones 
                       SET estado = 'Enviada al Cliente', 
                           version = :version, 
                           token_seguridad = :token, 
                           motivo_rechazo_cliente = NULL 
                       WHERE id = :id";
            $updStmt = $this->db->prepare($sqlUpd);
            $updStmt->execute([
                'version' => $nuevaVersion,
                'token' => $nuevoToken,
                'id' => $id
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            $this->db->rollBack();
            return false;
        }
    }

    // Obtener historial de versiones
    public function obtenerVersiones(int $id_cotizacion): array {
        $sql = "SELECT * FROM cotizacion_versiones WHERE id_cotizacion = :id ORDER BY version DESC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id_cotizacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // Obtener un detalle individual con sus datos de formato
    public function obtenerDetallePorId(int $id_detalle) {
        $sql = "SELECT cd.*, p.formato_id, f.nombre AS formato_nombre, f.codigo_formato, f.archivo_markdown, 
                       COALESCE(p.codigo_servicio, cd.codigo_servicio) AS codigo_servicio,
                       COALESCE(cd.norma_astm, p.norma_astm) AS norma_astm,
                       p.procedimiento_muestreo, p.tipo_muestra, p.matriz_tipo
                FROM cotizacion_detalles cd
                LEFT JOIN productos p ON cd.id_producto = p.id
                LEFT JOIN formatos_ensayos f ON p.formato_id = f.id
                WHERE cd.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id_detalle]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }
}