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
        $sql = "SELECT c.*, 
                       cl.nombre_razon_social AS cliente_nombre, 
                       cl.identificacion AS cliente_ruc, 
                       COALESCE(NULLIF(cl.email, ''), NULLIF(cl.contacto_correo, ''), '') AS cliente_email, 
                       COALESCE(NULLIF(cl.telefono, ''), '') AS cliente_tel, 
                       COALESCE(NULLIF(u.nombre, ''), 'Personal Autorizado') AS creador_nombre, 
                       COALESCE(NULLIF(u.email, ''), 'admon@cycsanic.com') AS creador_email 
                FROM cotizaciones c 
                INNER JOIN clientes cl ON c.id_cliente = cl.id 
                LEFT JOIN usuarios u ON c.id_usuario_creador = u.id 
                WHERE c.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerDetalles(int $id_cotizacion): array {
        $sql = "SELECT cd.id, cd.id_cotizacion, cd.id_producto, cd.descripcion_ensayo, cd.cantidad, cd.precio_unitario, cd.subtotal, cd.resultados_json, cd.descripcion_adicional,
                       COALESCE(cd.condiciones_muestra, p.condiciones_muestra) AS condiciones_muestra,
                       COALESCE(cd.procedimiento, p.procedimiento_muestreo, p.norma_astm) AS procedimiento,
                       COALESCE(cd.unidad_medida, p.unidad_medida, 'Unidad') AS unidad_medida,
                       COALESCE(p.codigo_servicio, cd.codigo_servicio) AS codigo_servicio,
                       COALESCE(cd.norma_astm, p.norma_astm) AS norma_astm,
                       COALESCE(cd.formato_reporte, f.codigo_formato) AS formato_reporte,
                       COALESCE(cd.observaciones, p.observaciones) AS observaciones,
                       p.nombre_comercial, p.tipo_muestra, f.archivo_markdown
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

            // 2. Si el estado es 'Aprobada por Cliente', crear Orden de Servicio automáticamente y gestionar Facturación / CXC / Bancos
            if ($estado === 'Aprobada por Cliente') {
                // Obtener datos completos de la cotización y cliente
                $stmtCot = $this->db->prepare("SELECT c.*, cl.nombre_razon_social AS cliente_nombre FROM cotizaciones c JOIN clientes cl ON c.id_cliente = cl.id WHERE c.id = :id");
                $stmtCot->execute(['id' => $id]);
                $cotInfo = $stmtCot->fetch(PDO::FETCH_ASSOC);

                if ($cotInfo) {
                    $idCliente = (int)$cotInfo['id_cliente'];
                    $codigoCot = $cotInfo['codigo'];
                    $totalCot = (float)$cotInfo['total'];
                    $facturaNum = "FAC-" . $codigoCot;

                    // A. Crear Orden de Servicio si no existe
                    $stmtCheck = $this->db->prepare("SELECT id FROM ordenes_servicio WHERE id_cotizacion = :id_cot");
                    $stmtCheck->execute(['id_cot' => $id]);
                    if (!$stmtCheck->fetch()) {
                        $anio = (int)date('Y');
                        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM ordenes_servicio WHERE YEAR(fecha_emision) = :anio");
                        $stmtCount->execute(['anio' => $anio]);
                        $consecutivo = (int)$stmtCount->fetchColumn() + 1;
                        $codigoOS = sprintf("OS-%d-%04d", $anio, $consecutivo);

                        $sqlOS = "INSERT INTO ordenes_servicio (codigo_os, id_cotizacion, tipo_contrato, fecha_emision, estado, requiere_muestreo) 
                                  VALUES (:codigo_os, :id_cotizacion, 'Puntual', CURRENT_DATE, 'Estado 1: Recepcion', NULL)";
                        $stmtOS = $this->db->prepare($sqlOS);
                        $stmtOS->execute([
                            'codigo_os' => $codigoOS,
                            'id_cotizacion' => $id
                        ]);
                    }

                    // B. Gestionar Factura en Cuentas por Cobrar (cuentas_por_cobrar)
                    $stmtCheckCxc = $this->db->prepare("SELECT id FROM cuentas_por_cobrar WHERE factura_numero = :fn");
                    $stmtCheckCxc->execute(['fn' => $facturaNum]);
                    if (!$stmtCheckCxc->fetch()) {
                        $montoPago = max(0.00, (float)$montoPagoInmediato);
                        $saldoPendiente = max(0.00, $totalCot - $montoPago);
                        
                        $estadoCxc = 'Pendiente';
                        if ($saldoPendiente <= 0.01) {
                            $estadoCxc = 'Pagado';
                        } elseif ($montoPago > 0) {
                            $estadoCxc = 'Parcial';
                        }

                        $diasVal = max(0, (int)$diasCredito);
                        $fechaVenc = date('Y-m-d', strtotime("+$diasVal days"));
                        $notasCxc = ($estadoCxc === 'Pagado') ? "Pago Contado Inmediato 100% - Ref: " . ($referenciaPago ?: 'Transferencia') : "Factura a Crédito ($diasVal días de plazo)";

                        $sqlCxc = "INSERT INTO cuentas_por_cobrar (id_cliente, factura_numero, monto, saldo, estado, fecha_emision, fecha_vencimiento, notas)
                                   VALUES (:id_cliente, :factura_numero, :monto, :saldo, :estado, CURRENT_DATE, :fecha_venc, :notas)";
                        $stmtCxc = $this->db->prepare($sqlCxc);
                        $stmtCxc->execute([
                            'id_cliente' => $idCliente,
                            'factura_numero' => $facturaNum,
                            'monto' => $totalCot,
                            'saldo' => $saldoPendiente,
                            'estado' => $estadoCxc,
                            'fecha_venc' => $fechaVenc,
                            'notas' => $notasCxc
                        ]);

                        // C. Si hubo pago inmediato/anticipo y se seleccionó banco, registrar en bancos_transacciones e incrementar saldo bancario
                        if ($montoPago > 0 && $idBancoCuenta && $idBancoCuenta > 0) {
                            $tipoTx = !empty($metodoPago) ? strtoupper($metodoPago) : 'TRANSFERENCIA';
                            if (!in_array($tipoTx, ['DEPOSITO', 'RETIRO', 'CHEQUE', 'TRANSFERENCIA'])) {
                                $tipoTx = 'TRANSFERENCIA';
                            }

                            $sqlTx = "INSERT INTO bancos_transacciones (id_banco_cuenta, tipo_transaccion, numero_documento, beneficiario, monto, fecha, estado, descripcion)
                                      VALUES (:id_banco, :tipo, :doc, :beneficiario, :monto, CURRENT_DATE, 'Cobrado', :desc)";
                            $stmtTx = $this->db->prepare($sqlTx);
                            $stmtTx->execute([
                                'id_banco' => $idBancoCuenta,
                                'tipo' => $tipoTx,
                                'doc' => $referenciaPago ?: $facturaNum,
                                'beneficiario' => $cotInfo['cliente_nombre'],
                                'monto' => $montoPago,
                                'desc' => "Cobro de Factura $facturaNum (" . ($estadoCxc === 'Pagado' ? 'Pago Contado' : 'Anticipo') . ")"
                            ]);

                            // Actualizar saldo de la cuenta bancaria
                            $this->db->prepare("UPDATE bancos_cuentas SET saldo_actual = saldo_actual + :monto WHERE id = :id_banco")
                                     ->execute(['monto' => $montoPago, 'id_banco' => $idBancoCuenta]);
                        }
                    }
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
            $sqlCabecera = "INSERT INTO cotizaciones (codigo, id_cliente, tipo_moneda, id_usuario_creador, atencion_a, nombre_proyecto, direccion_proyecto, prioridad, fecha_limite, condicion_pago, tiempo_entrega, vigencia_oferta, configuracion_notas, contactos, incluir_anexo_tecnico, anexo_tecnico, archivo_adjunto, subtotal, descuento, exonerado, exoneracion_no, impuesto, total, estado, version, fecha_entrega, fecha_seguimiento) VALUES (:codigo, :id_cliente, :tipo_moneda, :id_usuario_creador, :atencion_a, :nombre_proyecto, :direccion_proyecto, :prioridad, :fecha_limite, :condicion_pago, :tiempo_entrega, :vigencia_oferta, :configuracion_notas, :contactos, :incluir_anexo_tecnico, :anexo_tecnico, :archivo_adjunto, :subtotal, :descuento, :exonerado, :exoneracion_no, :impuesto, :total, 'Borrador', 0, :fecha_entrega, :fecha_seguimiento)";
            $stmtCabecera = $this->db->prepare($sqlCabecera);
            $stmtCabecera->execute($cabecera);
            $idCotizacion = $this->db->lastInsertId();

            $sqlDetalle = "INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, condiciones_muestra, procedimiento, unidad_medida, codigo_servicio, norma_astm, formato_reporte, observaciones, descripcion_adicional, cantidad, precio_unitario, subtotal) VALUES (:id_cotizacion, :id_producto, :descripcion, :condiciones_muestra, :procedimiento, :unidad_medida, :codigo_servicio, :norma_astm, :formato_reporte, :observaciones, :descripcion_adicional, :cantidad, :precio, :subtotal)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);
            foreach ($detalles as $detalle) {
                $stmtDetalle->execute([
                    'id_cotizacion' => $idCotizacion,
                    'id_producto' => $detalle['id_producto'],
                    'descripcion' => $detalle['descripcion'],
                    'condiciones_muestra' => $detalle['condiciones_muestra'] ?? null,
                    'procedimiento' => $detalle['procedimiento'] ?? null,
                    'unidad_medida' => $detalle['unidad_medida'] ?? 'Unidad',
                    'codigo_servicio' => $detalle['codigo_servicio'] ?? null,
                    'norma_astm' => $detalle['norma_astm'] ?? null,
                    'formato_reporte' => $detalle['formato_reporte'] ?? null,
                    'observaciones' => $detalle['observaciones'] ?? null,
                    'descripcion_adicional' => $detalle['descripcion_adicional'] ?? null,
                    'cantidad' => $detalle['cantidad'],
                    'precio' => $detalle['precio'],
                    'subtotal' => $detalle['subtotal']
                ]);
            }

            $this->db->commit();
            return true;
        } catch (\Throwable $e) {
            $this->db->rollBack();
            error_log("FATAL: Error al guardar cotizacion completa: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
            return false;
        }
    }

    // Actualizar (Corrección)
    public function actualizarCotizacionCompleta(int $id, array $cabecera, array $detalles): bool {
        $yaEnTransaccion = $this->db->inTransaction();
        try {
            if (!$yaEnTransaccion) {
                $this->db->beginTransaction();
            }

            // 1. Obtener la cotización actual antes de sobrescribirla
            $oldCot = $this->obtenerPorId($id);

            // 2. Si el estado actual es 'Rechazada por Cliente' u 'Observada', guardamos la versión histórica (versión anterior)
            $token = $oldCot['token_seguridad'] ?? null;
            $nuevoEstado = 'En Revision';
            $vNumParaArchivar = max(1, (int)($oldCot['version'] ?? 1));
            $nuevaVersion = $vNumParaArchivar;
            $motivoRechazo = $oldCot['motivo_rechazo_cliente'] ?? null;
            $motivoObs = $oldCot['motivo_observacion'] ?? null;

            if ($oldCot && ($oldCot['estado'] === 'Rechazada por Cliente' || $oldCot['estado'] === 'Observada')) {
                $oldDets = $this->obtenerDetalles($id);

                $detallesSnapshot = [];
                foreach ($oldDets as $d) {
                    $detallesSnapshot[] = [
                        'id_producto' => $d['id_producto'],
                        'descripcion_ensayo' => $d['descripcion_ensayo'],
                        'condiciones_muestra' => $d['condiciones_muestra'] ?? null,
                        'procedimiento' => $d['procedimiento'] ?? null,
                        'unidad_medida' => $d['unidad_medida'] ?? 'Unidad',
                        'codigo_servicio' => $d['codigo_servicio'] ?? null,
                        'norma_astm' => $d['norma_astm'] ?? null,
                        'formato_reporte' => $d['formato_reporte'] ?? null,
                        'observaciones' => $d['observaciones'] ?? null,
                        'descripcion_adicional' => $d['descripcion_adicional'] ?? null,
                        'cantidad' => $d['cantidad'],
                        'precio_unitario' => $d['precio_unitario'],
                        'subtotal' => $d['subtotal']
                    ];
                }

                $snapshot = array_merge($oldCot, [
                    'detalles' => $detallesSnapshot
                ]);

                $motivoCambio = ($oldCot['estado'] === 'Rechazada por Cliente')
                    ? ('Devuelta por cliente: ' . ($oldCot['motivo_rechazo_cliente'] ?? 'Rechazo'))
                    : ('Observada por gerencia: ' . ($oldCot['motivo_observacion'] ?? 'Observación'));

                $insStmt = $this->db->prepare("INSERT INTO cotizacion_versiones (id_cotizacion, version, datos_json, motivo_cambio) VALUES (:id_cotizacion, :version, :datos_json, :motivo)");
                $insStmt->execute([
                    'id_cotizacion' => $id,
                    'version' => $vNumParaArchivar,
                    'datos_json' => json_encode($snapshot),
                    'motivo' => $motivoCambio
                ]);

                // Al corregir una cotización observada o rechazada, se incrementa la versión oficial
                $nuevaVersion = $vNumParaArchivar + 1;
                if ($oldCot['estado'] === 'Rechazada por Cliente') {
                    $nuevoEstado = 'Enviada al Cliente';
                    $token = bin2hex(random_bytes(32));
                    $motivoRechazo = null; // Se limpia la observación/motivo de rechazo de la versión vieja
                } elseif ($oldCot['estado'] === 'Observada') {
                    $nuevoEstado = 'En Revision';
                    $motivoObs = null; // Se limpia la observación al guardar y re-enviar la nueva versión
                }
            }

            // 3. Sobrescribir los datos de la cotización actual
            $sqlCabecera = "UPDATE cotizaciones SET id_cliente = :id_cliente, tipo_moneda = :tipo_moneda, estado = :estado, version = :version, token_seguridad = :token, motivo_rechazo_cliente = :motivo_rechazo, motivo_observacion = :motivo_observacion, atencion_a = :atencion_a, nombre_proyecto = :nombre_proyecto, direccion_proyecto = :direccion_proyecto, condicion_pago = :condicion_pago, tiempo_entrega = :tiempo_entrega, vigencia_oferta = :vigencia_oferta, configuracion_notas = :configuracion_notas, contactos = :contactos, incluir_anexo_tecnico = :incluir_anexo_tecnico, anexo_tecnico = :anexo_tecnico, archivo_adjunto = :archivo_adjunto, subtotal = :subtotal, descuento = :descuento, exonerado = :exonerado, exoneracion_no = :exoneracion_no, impuesto = :impuesto, total = :total, fecha_entrega = :fecha_entrega, fecha_seguimiento = :fecha_seguimiento WHERE id = :id";
            $stmtCabecera = $this->db->prepare($sqlCabecera);
            $stmtCabecera->execute(array_merge($cabecera, [
                'id' => $id,
                'estado' => $nuevoEstado,
                'version' => $nuevaVersion,
                'token' => $token,
                'motivo_rechazo' => $motivoRechazo,
                'motivo_observacion' => $motivoObs
            ]));

            // 4. Eliminar los detalles antiguos para guardar los corregidos
            $delStmt = $this->db->prepare("DELETE FROM cotizacion_detalles WHERE id_cotizacion = :id");
            $delStmt->execute(['id' => $id]);

            $sqlDetalle = "INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, condiciones_muestra, procedimiento, unidad_medida, codigo_servicio, norma_astm, formato_reporte, observaciones, descripcion_adicional, cantidad, precio_unitario, subtotal) VALUES (:id_cotizacion, :id_producto, :descripcion, :condiciones_muestra, :procedimiento, :unidad_medida, :codigo_servicio, :norma_astm, :formato_reporte, :observaciones, :descripcion_adicional, :cantidad, :precio, :subtotal)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);
            foreach ($detalles as $det) {
                $stmtDetalle->execute([
                    'id_cotizacion' => $id,
                    'id_producto' => $det['id_producto'],
                    'descripcion' => $det['descripcion'],
                    'condiciones_muestra' => $det['condiciones_muestra'] ?? null,
                    'procedimiento' => $det['procedimiento'] ?? null,
                    'unidad_medida' => $det['unidad_medida'] ?? 'Unidad',
                    'codigo_servicio' => $det['codigo_servicio'] ?? null,
                    'norma_astm' => $det['norma_astm'] ?? null,
                    'formato_reporte' => $det['formato_reporte'] ?? null,
                    'observaciones' => $det['observaciones'] ?? null,
                    'descripcion_adicional' => $det['descripcion_adicional'] ?? null,
                    'cantidad' => $det['cantidad'],
                    'precio' => $det['precio'],
                    'subtotal' => $det['subtotal']
                ]);
            }

            if (!$yaEnTransaccion && $this->db->inTransaction()) {
                $this->db->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if (!$yaEnTransaccion && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("FATAL: Error al actualizar cotización completa: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
            return false;
        }
    }

    // Re-enviar una cotización rechazada por el cliente (creando una nueva versión sin cambios manuales en la edición)
    public function volverEnviarRechazada(int $id): bool {
        $yaEnTransaccion = $this->db->inTransaction();
        try {
            if (!$yaEnTransaccion) {
                $this->db->beginTransaction();
            }

            // 1. Obtener la cotización actual con datos completos de cliente y creador
            $oldCot = $this->obtenerPorId($id);

            if (!$oldCot || $oldCot['estado'] !== 'Rechazada por Cliente') {
                if (!$yaEnTransaccion && $this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return false;
            }

            // 2. Obtener detalles de la cotización actual
            $oldDets = $this->obtenerDetalles($id);

            // 3. Crear el snapshot de la versión que el cliente rechazó
            $detallesSnapshot = [];
            foreach ($oldDets as $d) {
                $detallesSnapshot[] = [
                    'id_producto' => $d['id_producto'],
                    'descripcion_ensayo' => $d['descripcion_ensayo'],
                    'condiciones_muestra' => $d['condiciones_muestra'] ?? null,
                    'procedimiento' => $d['procedimiento'] ?? null,
                    'unidad_medida' => $d['unidad_medida'] ?? 'Unidad',
                    'codigo_servicio' => $d['codigo_servicio'] ?? null,
                    'norma_astm' => $d['norma_astm'] ?? null,
                    'formato_reporte' => $d['formato_reporte'] ?? null,
                    'observaciones' => $d['observaciones'] ?? null,
                    'descripcion_adicional' => $d['descripcion_adicional'] ?? null,
                    'cantidad' => $d['cantidad'],
                    'precio_unitario' => $d['precio_unitario'],
                    'subtotal' => $d['subtotal']
                ];
            }

            $snapshot = array_merge($oldCot, [
                'detalles' => $detallesSnapshot
            ]);

            // 4. Guardar en cotizacion_versiones
            $vNumParaArchivar = max(1, (int)($oldCot['version'] ?? 1));
            $insStmt = $this->db->prepare("INSERT INTO cotizacion_versiones (id_cotizacion, version, datos_json, motivo_cambio) VALUES (:id_cotizacion, :version, :datos_json, :motivo)");
            $motivoCambio = 'Devuelta por cliente: ' . ($oldCot['motivo_rechazo_cliente'] ?? 'Rechazo');
            $insStmt->execute([
                'id_cotizacion' => $id,
                'version' => $vNumParaArchivar,
                'datos_json' => json_encode($snapshot),
                'motivo' => $motivoCambio
            ]);

            // 5. Actualizar la cotización actual a 'Enviada al Cliente', incrementando la versión y limpiando rechazo
            $nuevaVersion = $vNumParaArchivar + 1;
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

            if (!$yaEnTransaccion && $this->db->inTransaction()) {
                $this->db->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if (!$yaEnTransaccion && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("FATAL: Error al volver a enviar cotización rechazada: " . $e->getMessage() . " en " . $e->getFile() . ":" . $e->getLine());
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

    // Obtener una versión histórica específica por número de versión
    public function obtenerVersionHistorica(int $id_cotizacion, int $version): ?array {
        $sql = "SELECT * FROM cotizacion_versiones WHERE id_cotizacion = :id AND version = :version LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id_cotizacion, 'version' => $version]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);
        return $row ?: null;
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