<?php

namespace Cycsa\Modulos\Cotizaciones\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;
use Exception;

class CotizacionModelo extends ModeloBase {
    
    public function obtenerTodas(string $busqueda = '', array $filtros = []): array {
        $sql = "SELECT c.id, c.codigo, c.version, c.estado, c.total, c.fecha_creacion,
                       c.id_cliente, c.id_usuario_creador,
                       cl.nombre_razon_social AS cliente, 
                       u.nombre AS creador
                FROM cotizaciones c
                INNER JOIN clientes cl ON c.id_cliente = cl.id
                INNER JOIN usuarios u ON c.id_usuario_creador = u.id";

        $condiciones = [];
        $parametros = [];

        if ($busqueda !== '') {
            $termino = '%' . trim($busqueda) . '%';
            $condiciones[] = '(c.codigo LIKE :q1 OR cl.nombre_razon_social LIKE :q2 OR c.estado LIKE :q3)';
            $parametros['q1'] = $termino;
            $parametros['q2'] = $termino;
            $parametros['q3'] = $termino;
        }

        $fechaDesde = trim((string)($filtros['fecha_desde'] ?? ''));
        if ($this->esFechaFiltroValida($fechaDesde)) {
            $condiciones[] = 'DATE(c.fecha_creacion) >= :fecha_desde';
            $parametros['fecha_desde'] = $fechaDesde;
        }

        $fechaHasta = trim((string)($filtros['fecha_hasta'] ?? ''));
        if ($this->esFechaFiltroValida($fechaHasta)) {
            $condiciones[] = 'DATE(c.fecha_creacion) <= :fecha_hasta';
            $parametros['fecha_hasta'] = $fechaHasta;
        }

        $idCliente = (int)($filtros['id_cliente'] ?? 0);
        if ($idCliente > 0) {
            $condiciones[] = 'c.id_cliente = :id_cliente';
            $parametros['id_cliente'] = $idCliente;
        }

        $gruposEstado = [
            'borradores' => ['Borrador'],
            'revision' => ['En Revision'],
            'observadas' => ['Observada'],
            'aprobadas' => ['Aprobada Internamente', 'Enviada al Cliente', 'Aprobada por Cliente'],
            'rechazadas' => ['Rechazada por Cliente'],
        ];
        $estado = trim((string)($filtros['estado'] ?? ''));
        if (isset($gruposEstado[$estado])) {
            $marcadores = [];
            foreach ($gruposEstado[$estado] as $indice => $valorEstado) {
                $clave = 'estado_filtro_' . $indice;
                $marcadores[] = ':' . $clave;
                $parametros[$clave] = $valorEstado;
            }
            $condiciones[] = 'c.estado IN (' . implode(', ', $marcadores) . ')';
        }

        if ($condiciones !== []) {
            $sql .= ' WHERE ' . implode(' AND ', $condiciones);
        }

        $sql .= ' ORDER BY c.id DESC';
        $stmt = $this->db->prepare($sql);
        $stmt->execute($parametros);

        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    private function esFechaFiltroValida(string $fecha): bool {
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha) !== 1) {
            return false;
        }

        [$anio, $mes, $dia] = array_map('intval', explode('-', $fecha));
        return checkdate($mes, $dia, $anio);
    }

    public function obtenerPorId(int $id) {
        $sql = "SELECT c.*, 
                       cl.nombre_razon_social AS cliente_nombre, 
                       cl.identificacion AS cliente_ruc, 
                       COALESCE(NULLIF(c.direccion_cliente, ''), NULLIF(cl.direccion, ''), '') AS direccion_cliente,
                       COALESCE(NULLIF(cl.direccion, ''), '') AS cliente_direccion,
                       COALESCE(NULLIF(cl.email, ''), NULLIF(cl.contacto_correo, ''), '') AS cliente_email, 
                       COALESCE(NULLIF(cl.telefono, ''), '') AS cliente_tel, 
                       COALESCE(NULLIF(u.nombre, ''), 'Personal Autorizado') AS creador_nombre, 
                       COALESCE(NULLIF(u.email, ''), 'admon@cycsanic.com') AS creador_email,
                       COALESCE(NULLIF(u_resp.nombre, ''), '') AS responsable_interno_nombre,
                       COALESCE(NULLIF(u_aprob.nombre, ''), '') AS aprobacion_interna_nombre
                FROM cotizaciones c 
                INNER JOIN clientes cl ON c.id_cliente = cl.id 
                LEFT JOIN usuarios u ON c.id_usuario_creador = u.id 
                LEFT JOIN usuarios u_resp ON c.id_responsable_interno = u_resp.id
                LEFT JOIN usuarios u_aprob ON c.aprobacion_interna_por = u_aprob.id
                WHERE c.id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id]);
        return $stmt->fetch(PDO::FETCH_ASSOC);
    }

    public function obtenerDetalles(int $id_cotizacion, bool $incluirAnexoOperativo = false): array {
        $filtroAnexo = '';
        if (!$incluirAnexoOperativo) {
            $tieneColAnexoFlag = false;
            try {
                $chkColA = $this->db->query("SHOW COLUMNS FROM cotizacion_detalles LIKE 'es_incluido_anexo_mensual'");
                $tieneColAnexoFlag = (bool)($chkColA && $chkColA->fetch(PDO::FETCH_ASSOC));
            } catch (\Throwable $e) {
                $tieneColAnexoFlag = false;
            }
            $filtroAnexo = $tieneColAnexoFlag
                ? " AND COALESCE(cd.es_incluido_anexo_mensual, 0) = 0 AND COALESCE(cd.observaciones, '') <> 'Incluido en Servicio Mensual'"
                : " AND COALESCE(cd.observaciones, '') <> 'Incluido en Servicio Mensual'";
        }
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
                WHERE cd.id_cotizacion = :id{$filtroAnexo}
                ORDER BY cd.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id' => $id_cotizacion]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function generarCodigoUnico(): string {
        $año = date('Y');
        // Obtener el correlativo máximo del año actual para garantizar secuencia estricta sin colisiones
        $sql = "SELECT MAX(CAST(SUBSTRING_INDEX(codigo, '-', -1) AS UNSIGNED)) FROM cotizaciones WHERE codigo LIKE :anio";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['anio' => "COT-{$año}-%"]);
        $max = (int)$stmt->fetchColumn();
        return sprintf("COT-%s-%04d", $año, $max + 1);
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
    }

    /**
     * 2.10 / 2.11: Registra la aprobación interna sin enviar aún al cliente ni incrementar versión PDF.
     */
    public function registrarAprobacionInterna(int $id, int $usuarioId): bool {
        $sql = "UPDATE cotizaciones
                SET estado = 'Aprobada Internamente',
                    id_usuario_revisor = :revisor,
                    aprobacion_interna_por = :aprobador,
                    fecha_aprobacion_interna = NOW(),
                    motivo_observacion = NULL,
                    fecha_actualizacion = NOW()
                WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'revisor' => $usuarioId,
            'aprobador' => $usuarioId,
            'id' => $id,
        ]);
    }

    /**
     * 2.9: Marca la cotización como 'Enviada al Cliente' e incrementa la versión PDF solo al salir al cliente.
     * - Si versión actual es 0 (primera salida al cliente), pasa a versión 1.
     * - Si versión actual >= 1 y el estado previo no era ya 'Enviada al Cliente', archiva snapshot (si falta) y sube a versión + 1.
     */
    public function marcarEnviadaAlCliente(int $id, int $usuarioId, ?string $token = null, ?string $motivoSnapshot = null): bool {
        $yaEnTransaccion = $this->db->inTransaction();
        try {
            if (!$yaEnTransaccion) {
                $this->db->beginTransaction();
            }

            $oldCot = $this->obtenerPorId($id);
            if (!$oldCot || $oldCot['estado'] === 'Rechazada por Cliente') {
                if (!$yaEnTransaccion && $this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return false;
            }

            $estadoPrev = (string)($oldCot['estado'] ?? '');
            $versionActual = (int)($oldCot['version'] ?? 0);
            $tokenFinal = !empty($token)
                ? $token
                : (!empty($oldCot['token_seguridad']) ? $oldCot['token_seguridad'] : bin2hex(random_bytes(32)));

            $nuevaVersion = $versionActual;
            if ($estadoPrev === 'Enviada al Cliente') {
                // Re-envío del mismo correo/documento ya enviado al cliente: no sube versión nuevamente
                $nuevaVersion = max(1, $versionActual);
            } else {
                if ($versionActual <= 0) {
                    // Primera salida al cliente -> v1
                    $nuevaVersion = 1;
                } else {
                    // Ya había salido antes (ej. v1 fue rechazada/revisada) -> archivar snapshot si aún no existe y subir a v+1
                    $existeSnap = $this->obtenerVersionHistorica($id, $versionActual);
                    if (!$existeSnap) {
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
                                'subtotal' => $d['subtotal'],
                            ];
                        }
                        $snapshot = array_merge($oldCot, ['detalles' => $detallesSnapshot]);
                        $motivoSnap = $motivoSnapshot ?: (!empty($oldCot['motivo_rechazo_cliente'])
                            ? ('Devuelta por cliente: ' . $oldCot['motivo_rechazo_cliente'])
                            : 'Versión anterior archivada al emitir nueva versión al cliente');

                        $insStmt = $this->db->prepare("INSERT INTO cotizacion_versiones (id_cotizacion, version, datos_json, motivo_cambio) VALUES (:id_cotizacion, :version, :datos_json, :motivo)");
                        $insStmt->execute([
                            'id_cotizacion' => $id,
                            'version' => $versionActual,
                            'datos_json' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                            'motivo' => $motivoSnap,
                        ]);
                    }
                    $nuevaVersion = $versionActual + 1;
                }
            }

            $sql = "UPDATE cotizaciones
                    SET estado = 'Enviada al Cliente',
                        version = :version,
                        id_usuario_revisor = COALESCE(id_usuario_revisor, :revisor),
                        token_seguridad = :token,
                        motivo_rechazo_cliente = NULL,
                        fecha_actualizacion = NOW()
                    WHERE id = :id";
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'version' => $nuevaVersion,
                'revisor' => $usuarioId > 0 ? $usuarioId : null,
                'token' => $tokenFinal,
                'id' => $id,
            ]);

            if (!$yaEnTransaccion && $this->db->inTransaction()) {
                $this->db->commit();
            }
            return true;
        } catch (\Throwable $e) {
            if (!$yaEnTransaccion && $this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log('Error en marcarEnviadaAlCliente: ' . $e->getMessage());
            return false;
        }
    }

    public function registrarDecisionCliente(
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
                    $idClienteFactura = !empty($cotInfo['id_cliente_factura']) ? (int)$cotInfo['id_cliente_factura'] : $idCliente;
                    $idClienteInforme = !empty($cotInfo['id_cliente_informe']) ? (int)$cotInfo['id_cliente_informe'] : $idCliente;
                    $condComercial = !empty($cotInfo['condicion_comercial']) ? $cotInfo['condicion_comercial'] : determinarCondicionComercial($cotInfo['condicion_pago'] ?? '');
                    $formaPagoOS = !empty($cotInfo['condicion_pago']) ? trim($cotInfo['condicion_pago']) : ($condComercial === 'credito' ? 'Crédito' : 'Pago contra entrega');

                    if (!$stmtCheck->fetch()) {
                        $anio = (int)date('Y');
                        $stmtCount = $this->db->prepare("SELECT COUNT(*) FROM ordenes_servicio WHERE YEAR(fecha_emision) = :anio");
                        $stmtCount->execute(['anio' => $anio]);
                        $consecutivo = (int)$stmtCount->fetchColumn() + 1;
                        $codigoOS = sprintf("OS-%d-%04d", $anio, $consecutivo);

                        $esMensualCot = !empty($cotInfo['es_mensual']) ? 1 : 0;
                        $tipoContratoOS = $esMensualCot ? 'Marco' : 'Puntual';
                        $duracionMesesOS = $esMensualCot && !empty($cotInfo['duracion_meses']) ? max(1, (int)$cotInfo['duracion_meses']) : null;
                        $fechaInicioOS = $esMensualCot
                            ? (!empty($cotInfo['fecha_inicio_periodo']) ? trim((string)$cotInfo['fecha_inicio_periodo']) : date('Y-m-d'))
                            : null;
                        $fechaFinOS = $esMensualCot
                            ? (!empty($cotInfo['fecha_fin_periodo']) ? trim((string)$cotInfo['fecha_fin_periodo']) : null)
                            : null;
                        if ($esMensualCot && $fechaFinOS === null && $fechaInicioOS !== null && $duracionMesesOS !== null) {
                            $fechaFinOS = function_exists('calcularFechaFinPorMesesOS')
                                ? calcularFechaFinPorMesesOS($fechaInicioOS, $duracionMesesOS)
                                : date('Y-m-d', strtotime("+{$duracionMesesOS} months", strtotime($fechaInicioOS)));
                        }
                        $periodicidadOS = !empty($cotInfo['periodicidad']) ? trim((string)$cotInfo['periodicidad']) : 'Mensual';

                        $sqlOS = "INSERT INTO ordenes_servicio (
                                    codigo_os, id_cotizacion, cotizacion_version, id_cliente, id_cliente_factura, id_cliente_informe,
                                    condicion_comercial, forma_pago, atencion_a, direccion_cliente, nombre_proyecto, ubicacion_proyecto,
                                    contactos_json, tipo_contrato, es_multimes, duracion_meses, fecha_inicio, fecha_fin, periodicidad,
                                    fecha_emision, estado, requiere_muestreo
                                  ) VALUES (
                                    :codigo_os, :id_cotizacion, :cotizacion_version, :id_cliente, :id_cliente_factura, :id_cliente_informe,
                                    :condicion_comercial, :forma_pago, :atencion_a, :direccion_cliente, :nombre_proyecto, :ubicacion_proyecto,
                                    :contactos_json, :tipo_contrato, :es_multimes, :duracion_meses, :fecha_inicio, :fecha_fin, :periodicidad,
                                    CURRENT_DATE, 'Estado 1: Recepcion', NULL
                                  )";
                        $stmtOS = $this->db->prepare($sqlOS);
                        $stmtOS->execute([
                            'codigo_os' => $codigoOS,
                            'id_cotizacion' => $id,
                            'cotizacion_version' => !empty($cotInfo['version']) ? (int)$cotInfo['version'] : 1,
                            'id_cliente' => $idClienteFactura,
                            'id_cliente_factura' => $idClienteFactura,
                            'id_cliente_informe' => $idClienteInforme,
                            'condicion_comercial' => $condComercial,
                            'forma_pago' => $formaPagoOS,
                            'atencion_a' => (string)($cotInfo['atencion_a'] ?? ''),
                            'direccion_cliente' => (string)($cotInfo['direccion_cliente'] ?? ''),
                            'nombre_proyecto' => (string)($cotInfo['nombre_proyecto'] ?? ''),
                            'ubicacion_proyecto' => (string)($cotInfo['direccion_proyecto'] ?? ''),
                            'contactos_json' => !empty($cotInfo['contactos']) ? (string)$cotInfo['contactos'] : '[]',
                            'tipo_contrato' => $tipoContratoOS,
                            'es_multimes' => $esMensualCot,
                            'duracion_meses' => $duracionMesesOS,
                            'fecha_inicio' => $fechaInicioOS,
                            'fecha_fin' => $fechaFinOS,
                            'periodicidad' => $periodicidadOS,
                        ]);
                        $idOsNueva = (int)$this->db->lastInsertId();
                        if ($esMensualCot === 1 && class_exists(\Cycsa\Modulos\Cotizaciones\Servicios\AnexoServicioMensualServicio::class)) {
                            \Cycsa\Modulos\Cotizaciones\Servicios\AnexoServicioMensualServicio::sincronizarEnsayosOperativosEnCotizacion($this->db, $id, $idOsNueva > 0 ? $idOsNueva : null);
                        }
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
                            'id_cliente' => $idClienteFactura,
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
            $cabecera['id_cliente_factura'] = !empty($cabecera['id_cliente_factura']) ? (int)$cabecera['id_cliente_factura'] : (int)$cabecera['id_cliente'];
            $cabecera['id_cliente_informe'] = !empty($cabecera['id_cliente_informe']) ? (int)$cabecera['id_cliente_informe'] : (int)$cabecera['id_cliente'];
            $cabecera['condicion_comercial'] = !empty($cabecera['condicion_comercial']) ? trim($cabecera['condicion_comercial']) : (str_contains(strtolower($cabecera['condicion_pago'] ?? ''), 'credito') ? 'credito' : 'contado');
            $cabecera['id_responsable_interno'] = !empty($cabecera['id_responsable_interno']) ? (int)$cabecera['id_responsable_interno'] : null;
            $cabecera['direccion_cliente'] = array_key_exists('direccion_cliente', $cabecera)
                ? (trim((string)$cabecera['direccion_cliente']) !== '' ? trim((string)$cabecera['direccion_cliente']) : null)
                : null;
            $cabecera['es_mensual'] = !empty($cabecera['es_mensual']) ? 1 : 0;
            $cabecera['duracion_meses'] = ($cabecera['es_mensual'] === 1 && !empty($cabecera['duracion_meses'])) ? max(1, (int)$cabecera['duracion_meses']) : null;
            $cabecera['fecha_inicio_periodo'] = ($cabecera['es_mensual'] === 1 && !empty($cabecera['fecha_inicio_periodo'])) ? trim((string)$cabecera['fecha_inicio_periodo']) : null;
            $cabecera['fecha_fin_periodo'] = ($cabecera['es_mensual'] === 1 && !empty($cabecera['fecha_fin_periodo'])) ? trim((string)$cabecera['fecha_fin_periodo']) : null;
            $cabecera['periodicidad'] = !empty($cabecera['periodicidad']) ? trim((string)$cabecera['periodicidad']) : 'Mensual';

            $sqlCabecera = "INSERT INTO cotizaciones (codigo, id_cliente, id_cliente_factura, id_cliente_informe, condicion_comercial, tipo_moneda, id_usuario_creador, id_responsable_interno, atencion_a, direccion_cliente, nombre_proyecto, direccion_proyecto, prioridad, fecha_limite, condicion_pago, tiempo_entrega, vigencia_oferta, es_mensual, duracion_meses, fecha_inicio_periodo, fecha_fin_periodo, periodicidad, configuracion_notas, contactos, incluir_anexo_tecnico, anexo_tecnico, archivo_adjunto, subtotal, descuento, exonerado, exoneracion_no, impuesto, total, estado, version, fecha_entrega, fecha_seguimiento) VALUES (:codigo, :id_cliente, :id_cliente_factura, :id_cliente_informe, :condicion_comercial, :tipo_moneda, :id_usuario_creador, :id_responsable_interno, :atencion_a, :direccion_cliente, :nombre_proyecto, :direccion_proyecto, :prioridad, :fecha_limite, :condicion_pago, :tiempo_entrega, :vigencia_oferta, :es_mensual, :duracion_meses, :fecha_inicio_periodo, :fecha_fin_periodo, :periodicidad, :configuracion_notas, :contactos, :incluir_anexo_tecnico, :anexo_tecnico, :archivo_adjunto, :subtotal, :descuento, :exonerado, :exoneracion_no, :impuesto, :total, 'Borrador', 0, :fecha_entrega, :fecha_seguimiento)";
            $stmtCabecera = $this->db->prepare($sqlCabecera);
            $stmtCabecera->execute($cabecera);
            $idCotizacion = $this->db->lastInsertId();

            $sqlDetalle = "INSERT INTO cotizacion_detalles (id_cotizacion, id_producto, descripcion_ensayo, condiciones_muestra, procedimiento, unidad_medida, codigo_servicio, norma_astm, formato_reporte, observaciones, descripcion_adicional, cantidad, precio_unitario, subtotal) VALUES (:id_cotizacion, :id_producto, :descripcion, :condiciones_muestra, :procedimiento, :unidad_medida, :codigo_servicio, :norma_astm, :formato_reporte, :observaciones, :descripcion_adicional, :cantidad, :precio, :subtotal)";
            $stmtDetalle = $this->db->prepare($sqlDetalle);
            foreach ($detalles as $detalle) {
                $stmtDetalle->execute([
                    'id_cotizacion' => $idCotizacion,
                    'id_producto' => $detalle['id_producto'],
                    'descripcion' => $detalle['descripcion'] ?? ($detalle['descripcion_ensayo'] ?? ''),
                    'condiciones_muestra' => $detalle['condiciones_muestra'] ?? null,
                    'procedimiento' => $detalle['procedimiento'] ?? null,
                    'unidad_medida' => $detalle['unidad_medida'] ?? 'Unidad',
                    'codigo_servicio' => $detalle['codigo_servicio'] ?? null,
                    'norma_astm' => $detalle['norma_astm'] ?? null,
                    'formato_reporte' => $detalle['formato_reporte'] ?? null,
                    'observaciones' => $detalle['observaciones'] ?? null,
                    'descripcion_adicional' => $detalle['descripcion_adicional'] ?? null,
                    'cantidad' => $detalle['cantidad'],
                    'precio' => $detalle['precio'] ?? ($detalle['precio_unitario'] ?? 0.0),
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
            if (!$oldCot || !in_array($oldCot['estado'], ['Borrador', 'Observada', 'Rechazada por Cliente'], true)) {
                if (!$yaEnTransaccion && $this->db->inTransaction()) {
                    $this->db->rollBack();
                }
                return false;
            }

            // 2. Resolver estado, versiones y auditoría según estado previo
            $token = $oldCot['token_seguridad'] ?? null;
            $motivoRechazo = $oldCot['motivo_rechazo_cliente'] ?? null;
            $motivoObs = $oldCot['motivo_observacion'] ?? null;

            if ($oldCot['estado'] === 'Borrador') {
                // Borrador: se conserva estado Borrador, sin crear versión histórica y sin bump de versión
                $nuevoEstado = 'Borrador';
                $nuevaVersion = (int)($oldCot['version'] ?? 0);
            } elseif ($oldCot['estado'] === 'Observada') {
                // Regla 2.9: Observación interna (antes de salir al cliente) NO incrementa la versión PDF ni archiva snapshot
                $nuevoEstado = 'En Revision';
                $nuevaVersion = (int)($oldCot['version'] ?? 0);
                $motivoObs = null;
            } elseif ($oldCot['estado'] === 'Rechazada por Cliente') {
                // La versión que salió al cliente se archiva antes de sobreescribirla; la nueva versión sube al enviarse nuevamente al cliente (2.9)
                $vNumParaArchivar = max(1, (int)($oldCot['version'] ?? 1));
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

                $motivoCambio = 'Devuelta por cliente: ' . ($oldCot['motivo_rechazo_cliente'] ?? 'Rechazo');

                if (!$this->obtenerVersionHistorica($id, $vNumParaArchivar)) {
                    $insStmt = $this->db->prepare("INSERT INTO cotizacion_versiones (id_cotizacion, version, datos_json, motivo_cambio) VALUES (:id_cotizacion, :version, :datos_json, :motivo)");
                    $insStmt->execute([
                        'id_cotizacion' => $id,
                        'version' => $vNumParaArchivar,
                        'datos_json' => json_encode($snapshot, JSON_UNESCAPED_UNICODE),
                        'motivo' => $motivoCambio
                    ]);
                }

                // Se conserva versión actual hasta que salga nuevamente al cliente en enviarCliente()
                $nuevaVersion = $vNumParaArchivar;
                $nuevoEstado = 'En Revision';
                $token = bin2hex(random_bytes(32));
                $motivoRechazo = null; // Se limpia el motivo de rechazo de la versión vieja
            }

            // 3. Sobrescribir los datos de la cotización actual
            $cabecera['id_cliente_factura'] = !empty($cabecera['id_cliente_factura']) ? (int)$cabecera['id_cliente_factura'] : (int)$cabecera['id_cliente'];
            $cabecera['id_cliente_informe'] = !empty($cabecera['id_cliente_informe']) ? (int)$cabecera['id_cliente_informe'] : (int)$cabecera['id_cliente'];
            $cabecera['condicion_comercial'] = !empty($cabecera['condicion_comercial']) ? trim($cabecera['condicion_comercial']) : (str_contains(strtolower($cabecera['condicion_pago'] ?? ''), 'credito') ? 'credito' : 'contado');
            $idRespInterno = array_key_exists('id_responsable_interno', $cabecera)
                ? (!empty($cabecera['id_responsable_interno']) ? (int)$cabecera['id_responsable_interno'] : null)
                : (!empty($oldCot['id_responsable_interno']) ? (int)$oldCot['id_responsable_interno'] : null);
            $cabecera['id_responsable_interno'] = $idRespInterno;
            $cabecera['direccion_cliente'] = array_key_exists('direccion_cliente', $cabecera)
                ? (trim((string)$cabecera['direccion_cliente']) !== '' ? trim((string)$cabecera['direccion_cliente']) : null)
                : (!empty($oldCot['direccion_cliente']) ? trim((string)$oldCot['direccion_cliente']) : null);
            $esMensualUpd = array_key_exists('es_mensual', $cabecera)
                ? (!empty($cabecera['es_mensual']) ? 1 : 0)
                : (!empty($oldCot['es_mensual']) ? 1 : 0);
            $cabecera['es_mensual'] = $esMensualUpd;
            $cabecera['duracion_meses'] = ($esMensualUpd === 1)
                ? (!empty($cabecera['duracion_meses']) ? max(1, (int)$cabecera['duracion_meses']) : (!empty($oldCot['duracion_meses']) ? max(1, (int)$oldCot['duracion_meses']) : null))
                : null;
            $cabecera['fecha_inicio_periodo'] = ($esMensualUpd === 1)
                ? (array_key_exists('fecha_inicio_periodo', $cabecera) ? (!empty($cabecera['fecha_inicio_periodo']) ? trim((string)$cabecera['fecha_inicio_periodo']) : null) : ($oldCot['fecha_inicio_periodo'] ?? null))
                : null;
            $cabecera['fecha_fin_periodo'] = ($esMensualUpd === 1)
                ? (array_key_exists('fecha_fin_periodo', $cabecera) ? (!empty($cabecera['fecha_fin_periodo']) ? trim((string)$cabecera['fecha_fin_periodo']) : null) : ($oldCot['fecha_fin_periodo'] ?? null))
                : null;
            $cabecera['periodicidad'] = !empty($cabecera['periodicidad'])
                ? trim((string)$cabecera['periodicidad'])
                : (!empty($oldCot['periodicidad']) ? trim((string)$oldCot['periodicidad']) : 'Mensual');

            $sqlCabecera = "UPDATE cotizaciones SET id_cliente = :id_cliente, id_cliente_factura = :id_cliente_factura, id_cliente_informe = :id_cliente_informe, condicion_comercial = :condicion_comercial, tipo_moneda = :tipo_moneda, id_responsable_interno = :id_responsable_interno, estado = :estado, version = :version, token_seguridad = :token, motivo_rechazo_cliente = :motivo_rechazo, motivo_observacion = :motivo_observacion, atencion_a = :atencion_a, direccion_cliente = :direccion_cliente, nombre_proyecto = :nombre_proyecto, direccion_proyecto = :direccion_proyecto, condicion_pago = :condicion_pago, tiempo_entrega = :tiempo_entrega, vigencia_oferta = :vigencia_oferta, es_mensual = :es_mensual, duracion_meses = :duracion_meses, fecha_inicio_periodo = :fecha_inicio_periodo, fecha_fin_periodo = :fecha_fin_periodo, periodicidad = :periodicidad, configuracion_notas = :configuracion_notas, contactos = :contactos, incluir_anexo_tecnico = :incluir_anexo_tecnico, anexo_tecnico = :anexo_tecnico, archivo_adjunto = :archivo_adjunto, subtotal = :subtotal, descuento = :descuento, exonerado = :exonerado, exoneracion_no = :exoneracion_no, impuesto = :impuesto, total = :total, fecha_entrega = :fecha_entrega, fecha_seguimiento = :fecha_seguimiento WHERE id = :id";
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
                    'descripcion' => $det['descripcion'] ?? ($det['descripcion_ensayo'] ?? ''),
                    'condiciones_muestra' => $det['condiciones_muestra'] ?? null,
                    'procedimiento' => $det['procedimiento'] ?? null,
                    'unidad_medida' => $det['unidad_medida'] ?? 'Unidad',
                    'codigo_servicio' => $det['codigo_servicio'] ?? null,
                    'norma_astm' => $det['norma_astm'] ?? null,
                    'formato_reporte' => $det['formato_reporte'] ?? null,
                    'observaciones' => $det['observaciones'] ?? null,
                    'descripcion_adicional' => $det['descripcion_adicional'] ?? null,
                    'cantidad' => $det['cantidad'],
                    'precio' => $det['precio'] ?? ($det['precio_unitario'] ?? 0.0),
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
                       SET estado = 'En Revision', 
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

    /**
     * Duplica íntegramente una cotización existente, copiando todos sus datos comerciales,
     * proyecto, notas, configuraciones, anexos y líneas de ensayo, asignándole el siguiente
     * código consecutivo disponible en estado Borrador.
     *
     * @param int $idOrigen ID de la cotización a duplicar
     * @param int $idUsuarioCreador ID del usuario que realiza la duplicación
     * @return int ID de la nueva cotización creada, o 0 en caso de fallo
     */
    public function duplicarCotizacion(int $idOrigen, int $idUsuarioCreador = 0): int {
        $origen = $this->obtenerPorId($idOrigen);
        if (!$origen) {
            return 0;
        }

        $detalles = $this->obtenerDetalles($idOrigen);
        if (!is_array($detalles)) {
            $detalles = [];
        }

        $db = $this->db;
        $yaEnTransaccion = $db->inTransaction();

        try {
            if (!$yaEnTransaccion) {
                $db->beginTransaction();
            }

            $nuevoCodigo = $this->generarCodigoUnico();
            $creadorId = ($idUsuarioCreador > 0) ? $idUsuarioCreador : (int)($origen['id_usuario_creador'] ?? 1);

            $idClienteFactura = !empty($origen['id_cliente_factura']) ? (int)$origen['id_cliente_factura'] : (int)$origen['id_cliente'];
            $idClienteInforme = !empty($origen['id_cliente_informe']) ? (int)$origen['id_cliente_informe'] : (int)$origen['id_cliente'];
            $condComercial = !empty($origen['condicion_comercial']) 
                ? trim($origen['condicion_comercial']) 
                : (str_contains(strtolower($origen['condicion_pago'] ?? ''), 'credito') ? 'credito' : 'contado');

            $sqlCabecera = "INSERT INTO cotizaciones (
                codigo, id_cliente, id_cliente_factura, id_cliente_informe, condicion_comercial,
                tipo_moneda, id_usuario_creador, atencion_a, direccion_cliente, nombre_proyecto, direccion_proyecto,
                prioridad, fecha_limite, condicion_pago, tiempo_entrega, vigencia_oferta,
                es_mensual, duracion_meses, fecha_inicio_periodo, fecha_fin_periodo, periodicidad,
                configuracion_notas, contactos, incluir_anexo_tecnico, anexo_tecnico, archivo_adjunto,
                subtotal, descuento, exonerado, exoneracion_no, impuesto, total,
                estado, version, fecha_entrega, fecha_seguimiento, dias_credito
            ) VALUES (
                :codigo, :id_cliente, :id_cliente_factura, :id_cliente_informe, :condicion_comercial,
                :tipo_moneda, :id_usuario_creador, :atencion_a, :direccion_cliente, :nombre_proyecto, :direccion_proyecto,
                :prioridad, :fecha_limite, :condicion_pago, :tiempo_entrega, :vigencia_oferta,
                :es_mensual, :duracion_meses, :fecha_inicio_periodo, :fecha_fin_periodo, :periodicidad,
                :configuracion_notas, :contactos, :incluir_anexo_tecnico, :anexo_tecnico, :archivo_adjunto,
                :subtotal, :descuento, :exonerado, :exoneracion_no, :impuesto, :total,
                'Borrador', 0, NULL, NULL, :dias_credito
            )";

            $stmtCab = $db->prepare($sqlCabecera);
            $stmtCab->execute([
                'codigo' => $nuevoCodigo,
                'id_cliente' => (int)$origen['id_cliente'],
                'id_cliente_factura' => $idClienteFactura,
                'id_cliente_informe' => $idClienteInforme,
                'condicion_comercial' => $condComercial,
                'tipo_moneda' => (int)($origen['tipo_moneda'] ?? 1),
                'id_usuario_creador' => $creadorId,
                'atencion_a' => $origen['atencion_a'] ?? '',
                'direccion_cliente' => !empty($origen['direccion_cliente']) ? trim((string)$origen['direccion_cliente']) : null,
                'nombre_proyecto' => $origen['nombre_proyecto'] ?? '',
                'direccion_proyecto' => $origen['direccion_proyecto'] ?? '',
                'prioridad' => !empty($origen['prioridad']) ? $origen['prioridad'] : 'Normal',
                'fecha_limite' => null,
                'condicion_pago' => $origen['condicion_pago'] ?? '',
                'tiempo_entrega' => $origen['tiempo_entrega'] ?? '',
                'vigencia_oferta' => $origen['vigencia_oferta'] ?? '',
                'es_mensual' => !empty($origen['es_mensual']) ? 1 : 0,
                'duracion_meses' => !empty($origen['es_mensual']) && !empty($origen['duracion_meses']) ? (int)$origen['duracion_meses'] : null,
                'fecha_inicio_periodo' => !empty($origen['es_mensual']) && !empty($origen['fecha_inicio_periodo']) ? $origen['fecha_inicio_periodo'] : null,
                'fecha_fin_periodo' => !empty($origen['es_mensual']) && !empty($origen['fecha_fin_periodo']) ? $origen['fecha_fin_periodo'] : null,
                'periodicidad' => !empty($origen['periodicidad']) ? $origen['periodicidad'] : 'Mensual',
                'configuracion_notas' => $origen['configuracion_notas'] ?? null,
                'contactos' => $origen['contactos'] ?? null,
                'incluir_anexo_tecnico' => (int)($origen['incluir_anexo_tecnico'] ?? 0),
                'anexo_tecnico' => $origen['anexo_tecnico'] ?? null,
                'archivo_adjunto' => $origen['archivo_adjunto'] ?? null,
                'subtotal' => (float)($origen['subtotal'] ?? 0),
                'descuento' => (float)($origen['descuento'] ?? 0),
                'exonerado' => (int)($origen['exonerado'] ?? 0),
                'exoneracion_no' => $origen['exoneracion_no'] ?? null,
                'impuesto' => (float)($origen['impuesto'] ?? 0),
                'total' => (float)($origen['total'] ?? 0),
                'dias_credito' => (int)($origen['dias_credito'] ?? 30)
            ]);

            $nuevoId = (int)$db->lastInsertId();

            if (!empty($detalles)) {
                $sqlDetalle = "INSERT INTO cotizacion_detalles (
                    id_cotizacion, id_producto, descripcion_ensayo, condiciones_muestra,
                    procedimiento, unidad_medida, codigo_servicio, norma_astm,
                    formato_reporte, observaciones, descripcion_adicional, cantidad,
                    precio_unitario, subtotal
                ) VALUES (
                    :id_cotizacion, :id_producto, :descripcion_ensayo, :condiciones_muestra,
                    :procedimiento, :unidad_medida, :codigo_servicio, :norma_astm,
                    :formato_reporte, :observaciones, :descripcion_adicional, :cantidad,
                    :precio_unitario, :subtotal
                )";

                $stmtDet = $db->prepare($sqlDetalle);
                foreach ($detalles as $det) {
                    $stmtDet->execute([
                        'id_cotizacion' => $nuevoId,
                        'id_producto' => (int)($det['id_producto'] ?? 0),
                        'descripcion_ensayo' => $det['descripcion_ensayo'] ?? ($det['nombre_comercial'] ?? ''),
                        'condiciones_muestra' => $det['condiciones_muestra'] ?? null,
                        'procedimiento' => $det['procedimiento'] ?? null,
                        'unidad_medida' => $det['unidad_medida'] ?? 'Unidad',
                        'codigo_servicio' => $det['codigo_servicio'] ?? null,
                        'norma_astm' => $det['norma_astm'] ?? null,
                        'formato_reporte' => $det['formato_reporte'] ?? null,
                        'observaciones' => $det['observaciones'] ?? null,
                        'descripcion_adicional' => $det['descripcion_adicional'] ?? null,
                        'cantidad' => (float)($det['cantidad'] ?? 1),
                        'precio_unitario' => (float)($det['precio_unitario'] ?? 0),
                        'subtotal' => (float)($det['subtotal'] ?? 0)
                    ]);
                }
            }

            if (!$yaEnTransaccion && $db->inTransaction()) {
                $db->commit();
            }

            return $nuevoId;
        } catch (\Throwable $e) {
            if (!$yaEnTransaccion && $db->inTransaction()) {
                $db->rollBack();
            }
            error_log("Error al duplicar cotizacion {$idOrigen}: " . $e->getMessage());
            return 0;
        }
    }
}
