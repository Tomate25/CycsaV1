<?php

namespace Cycsa\Modulos\Ventas\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Ventas\Modelos\VentaModelo;
use PDO;

class VentasControlador extends ControladorBase {
    private function autorizar(Respuesta $respuesta, string $accion = 'ver'): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
        if (!tienePermiso('cotizaciones', $accion)) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
    }

    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->autorizar($respuesta);
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $estado = trim((string)($_GET['estado'] ?? 'pendientes'));
        if (!in_array($estado, ['pendientes', 'parciales', 'pagadas', 'todas'], true)) {
            $estado = 'pendientes';
        }
        $busqueda = trim((string)($_GET['q'] ?? ''));
        $modelo = new VentaModelo();

        $this->renderizar('ventas/vistas/index', [
            'titulo' => 'Ventas, Facturación y Cobro - CYCSA',
            'ordenes' => $modelo->obtenerOrdenes($busqueda, $estado),
            'conteos' => $modelo->obtenerConteos(),
            'bancos' => $modelo->obtenerBancosActivos(),
            'estado' => $estado,
            'busqueda' => $busqueda,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    public function procesarFacturacion(Peticion $peticion, Respuesta $respuesta): void {
        $this->autorizar($respuesta, 'crear_editar');
        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/ventas');
            return;
        }

        $datos = $peticion->obtenerDatos();
        if (!isset($datos['csrf_token']) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$datos['csrf_token'])) {
            $_SESSION['error'] = 'Token de seguridad inválido o sesión expirada.';
            $respuesta->redirigir('/Cycsa/publico/ventas');
            return;
        }

        $idOS = (int)($datos['id_os'] ?? 0);
        $metodoPago = strtolower(trim((string)($datos['metodo_pago'] ?? 'efectivo')));
        $monto = (float)($datos['monto'] ?? 0);
        $fecha = trim((string)($datos['fecha'] ?? date('Y-m-d')));
        $idBancoCuenta = (int)($datos['id_banco_cuenta'] ?? 0);
        $referencia = trim((string)($datos['referencia'] ?? ''));
        $diasCredito = max(0, (int)($datos['dias_credito'] ?? 0));
        $facturaPersonalizada = trim((string)($datos['factura_numero'] ?? ''));

        if ($idOS <= 0 || $monto <= 0 || !in_array($metodoPago, ['efectivo', 'transferencia', 'credito'], true)) {
            $_SESSION['error'] = 'Los datos de facturación no son válidos.';
            $respuesta->redirigir('/Cycsa/publico/ventas');
            return;
        }
        if ($metodoPago === 'transferencia' && $idBancoCuenta <= 0) {
            $_SESSION['error'] = 'Debe seleccionar una cuenta bancaria para el cobro por transferencia.';
            $respuesta->redirigir('/Cycsa/publico/ventas');
            return;
        }

        $db = Conexion::obtenerInstancia();
        $stmtOS = $db->prepare("SELECT os.*, cot.codigo AS cot_codigo, cot.total AS cot_total, cot.id_cliente,
                                      cli.nombre_razon_social AS cliente_nombre, cli.numero_ruc AS cliente_ruc
                               FROM ordenes_servicio os
                               JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                               JOIN clientes cli ON cot.id_cliente = cli.id
                               WHERE os.id = :id");
        $stmtOS->execute(['id' => $idOS]);
        $os = $stmtOS->fetch(PDO::FETCH_ASSOC);
        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/ventas');
            return;
        }

        $facturaNum = $facturaPersonalizada !== '' ? $facturaPersonalizada : ('FAC-' . $os['cot_codigo']);
        try {
            $db->beginTransaction();
            $stmtCxc = $db->prepare('SELECT * FROM cuentas_por_cobrar WHERE factura_numero = :fn FOR UPDATE');
            $stmtCxc->execute(['fn' => $facturaNum]);
            $cxc = $stmtCxc->fetch(PDO::FETCH_ASSOC);

            $saldoTotal = (float)$os['cot_total'];
            $saldoActual = $cxc ? (float)$cxc['saldo'] : $saldoTotal;
            $nuevoSaldo = $metodoPago === 'credito' ? $saldoActual : max(0, $saldoActual - $monto);
            $estadoCxc = $metodoPago === 'credito' ? 'Pendiente' : ($nuevoSaldo <= 0.01 ? 'Pagado' : 'Parcial');
            $fechaVencimiento = $metodoPago === 'credito' && $diasCredito > 0
                ? date('Y-m-d', strtotime('+' . $diasCredito . ' days', strtotime($fecha)))
                : $fecha;
            $notaMetodo = match ($metodoPago) {
                'efectivo' => 'Facturado en Efectivo (Caja Principal)',
                'transferencia' => 'Facturado vía Transferencia (Ref: ' . ($referencia ?: 'S/R') . ')',
                'credito' => "Factura a Crédito ($diasCredito días de plazo)",
            };

            if ($cxc) {
                $cxcId = (int)$cxc['id'];
                $stmt = $db->prepare("UPDATE cuentas_por_cobrar
                                      SET saldo = :saldo, estado = :estado, fecha_vencimiento = :venc,
                                          notas = CONCAT(IFNULL(notas, ''), ' | ', :nota)
                                      WHERE id = :id");
                $stmt->execute(['saldo' => $nuevoSaldo, 'estado' => $estadoCxc, 'venc' => $fechaVencimiento,
                    'nota' => $notaMetodo . ' el ' . date('d/m/Y H:i'), 'id' => $cxcId]);
            } else {
                $stmt = $db->prepare("INSERT INTO cuentas_por_cobrar
                    (id_cliente, factura_numero, monto, saldo, estado, fecha_emision, fecha_vencimiento, notas)
                    VALUES (:id_cliente, :factura_numero, :monto, :saldo, :estado, :fecha, :venc, :notas)");
                $stmt->execute(['id_cliente' => $os['id_cliente'], 'factura_numero' => $facturaNum,
                    'monto' => $saldoTotal, 'saldo' => $nuevoSaldo, 'estado' => $estadoCxc,
                    'fecha' => $fecha, 'venc' => $fechaVencimiento, 'notas' => $notaMetodo . ' el ' . date('d/m/Y H:i')]);
                $cxcId = (int)$db->lastInsertId();
            }

            $bancoInfo = null;
            if ($metodoPago === 'transferencia') {
                $stmt = $db->prepare('SELECT * FROM bancos_cuentas WHERE id = :id FOR UPDATE');
                $stmt->execute(['id' => $idBancoCuenta]);
                $bancoInfo = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$bancoInfo) {
                    throw new \RuntimeException('La cuenta bancaria seleccionada no existe.');
                }
                $stmt = $db->prepare('UPDATE bancos_cuentas SET saldo_actual = saldo_actual + :monto WHERE id = :id');
                $stmt->execute(['monto' => $monto, 'id' => $idBancoCuenta]);
                $stmt = $db->prepare("INSERT INTO bancos_transacciones
                    (id_banco_cuenta, tipo_transaccion, numero_documento, beneficiario, monto, fecha, estado, descripcion)
                    VALUES (:id_banco, 'TRANSFERENCIA', :doc, :beneficiario, :monto, :fecha, 'Cobrado', :descripcion)");
                $stmt->execute(['id_banco' => $idBancoCuenta, 'doc' => $referencia ?: ('TRANS-' . $facturaNum),
                    'beneficiario' => $os['cliente_nombre'], 'monto' => $monto, 'fecha' => $fecha,
                    'descripcion' => "Cobro de Factura $facturaNum (O/S {$os['codigo_os']}) - Banco {$bancoInfo['banco_nombre']}"]);
            }

            $idCuentaDebe = $metodoPago === 'credito' ? 13 : 4;
            if ($metodoPago === 'transferencia' && !empty($bancoInfo['id_cuenta_contable'])) {
                $idCuentaDebe = (int)$bancoInfo['id_cuenta_contable'];
            }
            $idCuentaHaber = 208;
            $stmt = $db->prepare('SELECT id FROM cuentas_contables WHERE id = :id');
            $stmt->execute(['id' => $idCuentaHaber]);
            if (!$stmt->fetchColumn()) {
                $idCuentaHaber = (int)($db->query("SELECT id FROM cuentas_contables WHERE codigo LIKE '40101%' AND tipo = 'DETALLE' LIMIT 1")->fetchColumn() ?: 206);
            }
            $concepto = match ($metodoPago) {
                'efectivo' => "Cobro Factura $facturaNum en Efectivo - O/S {$os['codigo_os']} - Cliente: {$os['cliente_nombre']}",
                'transferencia' => "Cobro Factura $facturaNum vía Transferencia - O/S {$os['codigo_os']} - Ref: " . ($referencia ?: 'S/R'),
                'credito' => "Emisión de Factura a Crédito $facturaNum ($diasCredito días) - O/S {$os['codigo_os']}",
            };
            $contabilidad = new \Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo();
            $partidaId = $contabilidad->registrarAsientoContable($fecha, $concepto, 'FACTURACION', $cxcId, [
                ['id_cuenta_contable' => $idCuentaDebe, 'debe' => $monto, 'haber' => 0.0],
                ['id_cuenta_contable' => $idCuentaHaber, 'debe' => 0.0, 'haber' => $monto],
            ]);
            $db->commit();

            $descripcion = "Facturación registrada en Ventas: $facturaNum | O/S: {$os['codigo_os']} | C$" . number_format($monto, 2) . ' | ' . ucfirst($metodoPago);
            if ($partidaId) {
                $descripcion .= ' | Asiento PD-' . str_pad((string)$partidaId, 5, '0', STR_PAD_LEFT);
            }
            registrarBitacora('ventas', 'facturacion', $descripcion, $idOS);
            registrarBitacora('contabilidad', 'facturacion', $descripcion, $cxcId);
            $_SESSION['exito'] = "Factura $facturaNum procesada correctamente desde Ventas.";
        } catch (\Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            error_log('Error en Ventas/procesarFacturacion: ' . $e->getMessage());
            $_SESSION['error'] = 'Error al procesar la facturación: ' . $e->getMessage();
        }
        $respuesta->redirigir('/Cycsa/publico/ventas');
    }

    public function imprimirFactura(Peticion $peticion, Respuesta $respuesta): void {
        $this->autorizar($respuesta);
        $idOS = (int)($_GET['id_os'] ?? 0);
        $facturaNumParam = trim((string)($_GET['factura'] ?? ''));
        $db = Conexion::obtenerInstancia();

        $sqlBase = "SELECT os.*, cot.codigo AS cot_codigo, cot.total AS cot_total, cot.id_cliente,
                           cot.subtotal AS cot_subtotal, cot.impuesto AS cot_iva, cot.condicion_pago,
                           cli.nombre_razon_social AS cliente_nombre, cli.numero_ruc AS cliente_ruc,
                           cli.direccion AS cliente_direccion, cli.telefono AS cliente_telefono,
                           cli.email AS cliente_email, cli.contacto_nombre
                    FROM ordenes_servicio os
                    JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                    JOIN clientes cli ON cot.id_cliente = cli.id";
        if ($idOS > 0) {
            $stmt = $db->prepare($sqlBase . ' WHERE os.id = :id');
            $stmt->execute(['id' => $idOS]);
        } else {
            $stmt = $db->prepare($sqlBase . " JOIN cuentas_por_cobrar cxc ON cxc.id_cliente = cli.id
                                             WHERE cxc.factura_numero = :fn LIMIT 1");
            $stmt->execute(['fn' => $facturaNumParam]);
        }
        $os = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$os) {
            $_SESSION['error'] = 'Factura u Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/ventas');
            return;
        }

        $facturaNum = $facturaNumParam ?: ('FAC-' . $os['cot_codigo']);
        $stmt = $db->prepare('SELECT * FROM cuentas_por_cobrar WHERE factura_numero = :fn LIMIT 1');
        $stmt->execute(['fn' => $facturaNum]);
        $cxc = $stmt->fetch(PDO::FETCH_ASSOC);
        $stmt = $db->prepare("SELECT cd.*, p.nombre_comercial FROM cotizacion_detalles cd
                              LEFT JOIN productos p ON cd.id_producto = p.id
                              WHERE cd.id_cotizacion = :id ORDER BY cd.id ASC");
        $stmt->execute(['id' => $os['id_cotizacion']]);
        $items = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $transaccionBancaria = null;
        $asientoDiario = null;
        if ($cxc) {
            $stmt = $db->prepare("SELECT bt.*, bc.banco_nombre, bc.numero_cuenta, bc.moneda
                                  FROM bancos_transacciones bt JOIN bancos_cuentas bc ON bt.id_banco_cuenta = bc.id
                                  WHERE bt.descripcion LIKE :pat ORDER BY bt.id DESC LIMIT 1");
            $stmt->execute(['pat' => '%' . $facturaNum . '%']);
            $transaccionBancaria = $stmt->fetch(PDO::FETCH_ASSOC);
            $stmt = $db->prepare("SELECT pd.*, pdd.debe, pdd.haber, cc.codigo AS cuenta_codigo, cc.nombre AS cuenta_nombre
                                  FROM partidas_diario pd JOIN partidas_diario_detalles pdd ON pd.id = pdd.id_partida
                                  JOIN cuentas_contables cc ON pdd.id_cuenta_contable = cc.id
                                  WHERE pd.origen_id = :id ORDER BY pd.id DESC");
            $stmt->execute(['id' => $cxc['id']]);
            $asientoDiario = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        require dirname(__DIR__) . '/Vistas/factura_print.php';
        exit;
    }
}
