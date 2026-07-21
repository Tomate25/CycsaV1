<?php

namespace Cycsa\Modulos\Contabilidad\Modelos;

use Cycsa\Nucleo\ModeloBase;
use PDO;
use Exception;

class ContabilidadModelo extends ModeloBase {

    // ==========================================
    // 1. Cuentas Contables (Catálogo de Cuentas)
    // ==========================================

    public function obtenerCuentas(string $busqueda = ''): array {
        if ($busqueda !== '') {
            $sql = "SELECT c1.*, c2.nombre AS nombre_padre, c2.codigo AS codigo_padre 
                    FROM cuentas_contables c1
                    LEFT JOIN cuentas_contables c2 ON c1.id_padre = c2.id
                    WHERE c1.codigo LIKE :q1 
                       OR c1.nombre LIKE :q2 
                       OR c1.categoria LIKE :q3
                    ORDER BY c1.codigo ASC";
            $stmt = $this->db->prepare($sql);
            $termino = '%' . trim($busqueda) . '%';
            $stmt->execute([
                'q1' => $termino,
                'q2' => $termino,
                'q3' => $termino
            ]);
        } else {
            $sql = "SELECT c1.*, c2.nombre AS nombre_padre, c2.codigo AS codigo_padre 
                    FROM cuentas_contables c1
                    LEFT JOIN cuentas_contables c2 ON c1.id_padre = c2.id
                    ORDER BY c1.codigo ASC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCuentasDetalle(): array {
        $sql = "SELECT id, codigo, nombre, categoria 
                FROM cuentas_contables 
                WHERE tipo = 'DETALLE' AND activo = 1 
                ORDER BY codigo ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerCuentasMayor(): array {
        $sql = "SELECT id, codigo, nombre, categoria 
                FROM cuentas_contables 
                WHERE tipo = 'MAYOR' AND activo = 1 
                ORDER BY codigo ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function codigoExiste(string $codigo): bool {
        $sql = "SELECT COUNT(*) FROM cuentas_contables WHERE codigo = :codigo";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['codigo' => trim($codigo)]);
        return $stmt->fetchColumn() > 0;
    }

    public function guardarCuenta(array $datos): bool {
        $sql = "INSERT INTO cuentas_contables (codigo, nombre, tipo, categoria, id_padre, tipo_cuenta_detalle, tipo_cuenta_mayor) 
                VALUES (:codigo, :nombre, :tipo, :categoria, :id_padre, :tipo_cuenta_detalle, :tipo_cuenta_mayor)";
        
        $id_padre = !empty($datos['id_padre']) ? (int)$datos['id_padre'] : null;
        $tipo_cuenta_detalle = !empty(trim($datos['tipo_cuenta_detalle'])) ? trim($datos['tipo_cuenta_detalle']) : null;
        $tipo_cuenta_mayor = !empty(trim($datos['tipo_cuenta_mayor'])) ? trim($datos['tipo_cuenta_mayor']) : null;

        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'codigo' => trim($datos['codigo']),
            'nombre' => trim($datos['nombre']),
            'tipo' => $datos['tipo'],
            'categoria' => $datos['categoria'],
            'id_padre' => $id_padre,
            'tipo_cuenta_detalle' => $tipo_cuenta_detalle,
            'tipo_cuenta_mayor' => $tipo_cuenta_mayor
        ]);
    }

    // ==========================================
    // 2. Cuentas por Cobrar (CXC)
    // ==========================================

    public function obtenerCxc(string $busqueda = ''): array {
        $sql = "SELECT cxc.*, cl.nombre_razon_social AS cliente_nombre, cc.nombre AS cuenta_nombre, cc.codigo AS cuenta_codigo
                FROM cuentas_por_cobrar cxc
                LEFT JOIN clientes cl ON cxc.id_cliente = cl.id
                LEFT JOIN cuentas_contables cc ON cxc.id_cuenta_contable = cc.id";
        
        if ($busqueda !== '') {
            $sql .= " WHERE cl.nombre_razon_social LIKE :q1 
                        OR cxc.factura_numero LIKE :q2 
                        OR cxc.estado LIKE :q3
                     ORDER BY cxc.id DESC";
            $stmt = $this->db->prepare($sql);
            $termino = '%' . trim($busqueda) . '%';
            $stmt->execute([
                'q1' => $termino,
                'q2' => $termino,
                'q3' => $termino
            ]);
        } else {
            $sql .= " ORDER BY cxc.id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardarCxc(array $datos): bool {
        try {
            $this->db->beginTransaction();
            $sql = "INSERT INTO cuentas_por_cobrar (id_cliente, id_cuenta_contable, factura_numero, monto, saldo, estado, fecha_emision, fecha_vencimiento, notas) 
                    VALUES (:id_cliente, :id_cuenta_contable, :factura_numero, :monto, :saldo, 'Pendiente', :fecha_emision, :fecha_vencimiento, :notas)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'id_cliente' => !empty($datos['id_cliente']) ? (int)$datos['id_cliente'] : null,
                'id_cuenta_contable' => !empty($datos['id_cuenta_contable']) ? (int)$datos['id_cuenta_contable'] : null,
                'factura_numero' => trim($datos['factura_numero']),
                'monto' => (float)$datos['monto'],
                'saldo' => (float)$datos['monto'],
                'fecha_emision' => $datos['fecha_emision'],
                'fecha_vencimiento' => !empty($datos['fecha_vencimiento']) ? $datos['fecha_vencimiento'] : null,
                'notas' => !empty($datos['notas']) ? trim($datos['notas']) : null
            ]);

            $cxcId = (int)$this->db->lastInsertId();
            $cxcAcc = !empty($datos['id_cuenta_contable']) ? (int)$datos['id_cuenta_contable'] : null;
            
            // Buscar la cuenta contable de cobro asignada al cliente
            if (!$cxcAcc && !empty($datos['id_cliente'])) {
                $stmtCl = $this->db->prepare("SELECT cuenta_cxc FROM clientes WHERE id = :id");
                $stmtCl->execute(['id' => $datos['id_cliente']]);
                $cuentaCxcStr = $stmtCl->fetchColumn();
                if ($cuentaCxcStr) {
                    $parts = explode(' / ', $cuentaCxcStr);
                    $codigo = trim($parts[0]);
                    if (!empty($codigo)) {
                        $stmtCta = $this->db->prepare("SELECT id FROM cuentas_contables WHERE codigo = :codigo LIMIT 1");
                        $stmtCta->execute(['codigo' => $codigo]);
                        $cxcAccVal = $stmtCta->fetchColumn();
                        if ($cxcAccVal) {
                            $cxcAcc = (int)$cxcAccVal;
                        }
                    }
                }
            }

            if (!$cxcAcc) {
                // Encontrar la cuenta estándar 10102 o 103 de cobro
                $stmtF = $this->db->prepare("SELECT id FROM cuentas_contables WHERE (codigo LIKE '10102%' OR codigo LIKE '103%') AND tipo = 'DETALLE' ORDER BY codigo ASC LIMIT 1");
                $stmtF->execute();
                $cxcAcc = (int)($stmtF->fetchColumn() ?: 13);
            }


            // Register Journal Entry (Debit Client / Credit Sales Revenue 4010104)
            $this->registrarAsientoContable(
                $datos['fecha_emision'],
                "Registro de Venta / Factura N° " . $datos['factura_numero'],
                'CXC',
                $cxcId,
                [
                    ['id_cuenta_contable' => $cxcAcc, 'debe' => $datos['monto'], 'haber' => 0.0],
                    ['id_cuenta_contable' => 206, 'debe' => 0.0, 'haber' => $datos['monto']]
                ]
            );

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en guardarCxc: " . $e->getMessage());
            return false;
        }
    }

    public function registrarPagoCxc(int $idCxc, float $monto, int $idBancoCuenta, string $referencia, string $fecha): bool {
        try {
            $this->db->beginTransaction();

            // 1. Obtener la CXC y validar saldo
            $stmt = $this->db->prepare("SELECT * FROM cuentas_por_cobrar WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $idCxc]);
            $cxc = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cxc) {
                throw new Exception("Cuenta por cobrar no encontrada.");
            }
            
            if ($monto <= 0) {
                throw new Exception("El monto debe ser mayor a cero.");
            }

            if ($monto > $cxc['saldo']) {
                throw new Exception("El monto del pago excede el saldo pendiente (" . $cxc['saldo'] . ").");
            }

            $nuevoSaldo = $cxc['saldo'] - $monto;
            $nuevoEstado = $nuevoSaldo <= 0 ? 'Pagado' : 'Parcial';

            // 2. Actualizar la CXC
            $updCxc = $this->db->prepare("UPDATE cuentas_por_cobrar SET saldo = :saldo, estado = :estado WHERE id = :id");
            $updCxc->execute([
                'saldo' => $nuevoSaldo,
                'estado' => $nuevoEstado,
                'id' => $idCxc
            ]);

            // 3. Obtener el banco para registrar el ingreso
            $stmtBco = $this->db->prepare("SELECT * FROM bancos_cuentas WHERE id = :id FOR UPDATE");
            $stmtBco->execute(['id' => $idBancoCuenta]);
            $banco = $stmtBco->fetch(PDO::FETCH_ASSOC);

            if (!$banco) {
                throw new Exception("Cuenta bancaria no encontrada.");
            }

            $nuevoSaldoBanco = $banco['saldo_actual'] + $monto;

            // 4. Actualizar el saldo del banco
            $updBco = $this->db->prepare("UPDATE bancos_cuentas SET saldo_actual = :saldo WHERE id = :id");
            $updBco->execute([
                'saldo' => $nuevoSaldoBanco,
                'id' => $idBancoCuenta
            ]);

            // 5. Insertar la transacción bancaria
            $insTx = $this->db->prepare("
                INSERT INTO bancos_transacciones (id_banco_cuenta, tipo_transaccion, numero_documento, beneficiario, monto, fecha, estado, descripcion)
                VALUES (:id_banco_cuenta, 'DEPOSITO', :numero_documento, :beneficiario, :monto, :fecha, 'Cobrado', :descripcion)
            ");
            
            // Obtener el nombre del cliente
            $stmtCli = $this->db->prepare("SELECT nombre_razon_social FROM clientes WHERE id = :id");
            $stmtCli->execute(['id' => $cxc['id_cliente']]);
            $clienteNombre = $stmtCli->fetchColumn() ?: 'Cliente General';

            $insTx->execute([
                'id_banco_cuenta' => $idBancoCuenta,
                'numero_documento' => !empty($referencia) ? trim($referencia) : 'REF-' . $idCxc,
                'beneficiario' => $clienteNombre,
                'monto' => $monto,
                'fecha' => $fecha,
                'descripcion' => "Abono a Factura / Cotización N° " . $cxc['factura_numero']
            ]);

            // Register Asiento for Pago CXC
            // Debit: Bank Account ($idBancoCuenta -> id_cuenta_contable)
            // Credit: Client Account ($cxc['id_cuenta_contable'])
            $cxcAcc = $cxc['id_cuenta_contable'];
            if (!$cxcAcc) {
                $stmtF = $this->db->prepare("SELECT id FROM cuentas_contables WHERE (codigo LIKE '10102%' OR codigo LIKE '103%') AND tipo = 'DETALLE' ORDER BY codigo ASC LIMIT 1");
                $stmtF->execute();
                $cxcAcc = (int)($stmtF->fetchColumn() ?: 13);
            }

            $this->registrarAsientoContable(
                $fecha,
                "Abono a Factura N° " . $cxc['factura_numero'] . " - Ref: " . $referencia,
                'CXC_PAGO',
                $idCxc,
                [
                    ['id_cuenta_contable' => $banco['id_cuenta_contable'], 'debe' => $monto, 'haber' => 0.0],
                    ['id_cuenta_contable' => $cxcAcc, 'debe' => 0.0, 'haber' => $monto]
                ]
            );

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en registrarPagoCxc: " . $e->getMessage());
            return false;
        }
    }

    // ==========================================
    // 3. Cuentas por Pagar (CXP)
    // ==========================================

    public function obtenerCxp(string $busqueda = ''): array {
        $sql = "SELECT cxp.*, cc.nombre AS cuenta_nombre, cc.codigo AS cuenta_codigo
                FROM cuentas_por_pagar cxp
                LEFT JOIN cuentas_contables cc ON cxp.id_cuenta_contable = cc.id";
        
        if ($busqueda !== '') {
            $sql .= " WHERE cxp.proveedor_nombre LIKE :q1 
                        OR cxp.factura_numero LIKE :q2 
                        OR cxp.estado LIKE :q3
                     ORDER BY cxp.id DESC";
            $stmt = $this->db->prepare($sql);
            $termino = '%' . trim($busqueda) . '%';
            $stmt->execute([
                'q1' => $termino,
                'q2' => $termino,
                'q3' => $termino
            ]);
        } else {
            $sql .= " ORDER BY cxp.id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardarCxp(array $datos): bool {
        try {
            $this->db->beginTransaction();
            $sql = "INSERT INTO cuentas_por_pagar (proveedor_nombre, id_cuenta_contable, factura_numero, monto, saldo, estado, fecha_emision, fecha_vencimiento, notas) 
                    VALUES (:proveedor_nombre, :id_cuenta_contable, :factura_numero, :monto, :saldo, 'Pendiente', :fecha_emision, :fecha_vencimiento, :notas)";
            
            $stmt = $this->db->prepare($sql);
            $stmt->execute([
                'proveedor_nombre' => trim($datos['proveedor_nombre']),
                'id_cuenta_contable' => !empty($datos['id_cuenta_contable']) ? (int)$datos['id_cuenta_contable'] : null,
                'factura_numero' => trim($datos['factura_numero']),
                'monto' => (float)$datos['monto'],
                'saldo' => (float)$datos['monto'],
                'fecha_emision' => $datos['fecha_emision'],
                'fecha_vencimiento' => !empty($datos['fecha_vencimiento']) ? $datos['fecha_vencimiento'] : null,
                'notes' => !empty($datos['notas']) ? trim($datos['notas']) : null
            ]);

            $cxpId = (int)$this->db->lastInsertId();
            $expenseAcc = !empty($datos['id_cuenta_contable']) ? (int)$datos['id_cuenta_contable'] : null;
            if (!$expenseAcc) {
                // Find standard 501/601 expense account (default 221 Materiales)
                $expenseAcc = 221;
            }

            // Register Journal Entry (Debit Expense / Credit Accounts Payable 121)
            $this->registrarAsientoContable(
                $datos['fecha_emision'],
                "Registro de Gasto / Factura N° " . $datos['factura_numero'] . " de " . $datos['proveedor_nombre'],
                'CXP',
                $cxpId,
                [
                    ['id_cuenta_contable' => $expenseAcc, 'debe' => $datos['monto'], 'haber' => 0.0],
                    ['id_cuenta_contable' => 121, 'debe' => 0.0, 'haber' => $datos['monto']]
                ]
            );

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en guardarCxp: " . $e->getMessage());
            return false;
        }
    }

    public function registrarPagoCxp(int $idCxp, float $monto, int $idBancoCuenta, string $referencia, string $fecha, string $tipoTransaccion = 'RETIRAR'): bool {
        try {
            $this->db->beginTransaction();

            // 1. Obtener la CXP y validar saldo
            $stmt = $this->db->prepare("SELECT * FROM cuentas_por_pagar WHERE id = :id FOR UPDATE");
            $stmt->execute(['id' => $idCxp]);
            $cxp = $stmt->fetch(PDO::FETCH_ASSOC);
            
            if (!$cxp) {
                throw new Exception("Cuenta por pagar no encontrada.");
            }
            
            if ($monto <= 0) {
                throw new Exception("El monto debe ser mayor a cero.");
            }

            if ($monto > $cxp['saldo']) {
                throw new Exception("El monto del pago excede el saldo pendiente (" . $cxp['saldo'] . ").");
            }

            $nuevoSaldo = $cxp['saldo'] - $monto;
            $nuevoEstado = $nuevoSaldo <= 0 ? 'Pagado' : 'Parcial';

            // 2. Actualizar la CXP
            $updCxp = $this->db->prepare("UPDATE cuentas_por_pagar SET saldo = :saldo, estado = :estado WHERE id = :id");
            $updCxp->execute([
                'saldo' => $nuevoSaldo,
                'estado' => $nuevoEstado,
                'id' => $idCxp
            ]);

            // 3. Obtener el banco para registrar el retiro
            $stmtBco = $this->db->prepare("SELECT * FROM bancos_cuentas WHERE id = :id FOR UPDATE");
            $stmtBco->execute(['id' => $idBancoCuenta]);
            $banco = $stmtBco->fetch(PDO::FETCH_ASSOC);

            if (!$banco) {
                throw new Exception("Cuenta bancaria no encontrada.");
            }

            $nuevoSaldoBanco = $banco['saldo_actual'] - $monto;

            // 4. Actualizar el saldo del banco
            $updBco = $this->db->prepare("UPDATE bancos_cuentas SET saldo_actual = :saldo WHERE id = :id");
            $updBco->execute([
                'saldo' => $nuevoSaldoBanco,
                'id' => $idBancoCuenta
            ]);

            // 5. Insertar la transacción bancaria (CHEQUE o RETIRO/TRANSFERENCIA)
            $tipoTx = ($tipoTransaccion === 'CHEQUE') ? 'CHEQUE' : 'RETIRO';
            
            $insTx = $this->db->prepare("
                INSERT INTO bancos_transacciones (id_banco_cuenta, tipo_transaccion, numero_documento, beneficiario, monto, fecha, estado, descripcion)
                VALUES (:id_banco_cuenta, :tipo_transaccion, :numero_documento, :beneficiario, :monto, :fecha, 'Emitido', :descripcion)
            ");

            $insTx->execute([
                'id_banco_cuenta' => $idBancoCuenta,
                'tipo_transaccion' => $tipoTx,
                'numero_documento' => !empty($referencia) ? trim($referencia) : 'CHQ-' . $idCxp,
                'beneficiario' => $cxp['proveedor_nombre'],
                'monto' => $monto,
                'fecha' => $fecha,
                'descripcion' => "Pago a Proveedor Factura N° " . $cxp['factura_numero']
            ]);

            // Register Asiento for Pago CXP
            // Debit: Suppliers Payable Liability (ID 121 / '2010101 - CUENTAS POR PAGAR PROVEEDORES')
            // Credit: Bank Account ($idBancoCuenta -> id_cuenta_contable)
            $this->registrarAsientoContable(
                $fecha,
                "Pago a Proveedor Factura N° " . $cxp['factura_numero'] . " - Ref: " . $referencia,
                'CXP_PAGO',
                $idCxp,
                [
                    ['id_cuenta_contable' => 121, 'debe' => $monto, 'haber' => 0.0],
                    ['id_cuenta_contable' => $banco['id_cuenta_contable'], 'debe' => 0.0, 'haber' => $monto]
                ]
            );

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en registrarPagoCxp: " . $e->getMessage());
            return false;
        }
    }


    // ==========================================
    // 4. Bancos / Cuentas Bancarias / Transacciones
    // ==========================================

    public function obtenerCuentasBancarias(): array {
        $sql = "SELECT bc.*, cc.nombre AS cuenta_nombre, cc.codigo AS cuenta_codigo
                FROM bancos_cuentas bc
                LEFT JOIN cuentas_contables cc ON bc.id_cuenta_contable = cc.id
                ORDER BY bc.banco_nombre ASC, bc.numero_cuenta ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute();
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardarCuentaBancaria(array $datos): bool {
        $sql = "INSERT INTO bancos_cuentas (id_cuenta_contable, banco_nombre, numero_cuenta, moneda, saldo_inicial, saldo_actual, activo) 
                VALUES (:id_cuenta_contable, :banco_nombre, :numero_cuenta, :moneda, :saldo_inicial, :saldo_inicial, 1)";
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id_cuenta_contable' => !empty($datos['id_cuenta_contable']) ? (int)$datos['id_cuenta_contable'] : null,
            'banco_nombre' => trim($datos['banco_nombre']),
            'numero_cuenta' => trim($datos['numero_cuenta']),
            'moneda' => $datos['moneda'],
            'saldo_inicial' => (float)$datos['saldo_inicial']
        ]);
    }

    public function obtenerTransaccionesBancarias(int $idBancoCuenta = 0): array {
        $sql = "SELECT bt.*, bc.banco_nombre, bc.numero_cuenta, bc.moneda
                FROM bancos_transacciones bt
                JOIN bancos_cuentas bc ON bt.id_banco_cuenta = bc.id";
        
        if ($idBancoCuenta > 0) {
            $sql .= " WHERE bt.id_banco_cuenta = :id_banco_cuenta ORDER BY bt.fecha DESC, bt.id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute(['id_banco_cuenta' => $idBancoCuenta]);
        } else {
            $sql .= " ORDER BY bt.fecha DESC, bt.id DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardarTransaccionManual(array $datos): bool {
        try {
            $this->db->beginTransaction();

            $idBancoCuenta = (int)$datos['id_banco_cuenta'];
            $tipoTx = $datos['tipo_transaccion']; // DEPOSITO, RETIRO, CHEQUE, TRANSFERENCIA
            $monto = (float)$datos['monto'];

            // 1. Obtener y bloquear la cuenta bancaria
            $stmtBco = $this->db->prepare("SELECT * FROM bancos_cuentas WHERE id = :id FOR UPDATE");
            $stmtBco->execute(['id' => $idBancoCuenta]);
            $banco = $stmtBco->fetch(PDO::FETCH_ASSOC);

            if (!$banco) {
                throw new Exception("Cuenta bancaria no encontrada.");
            }

            if ($monto <= 0) {
                throw new Exception("El monto debe ser positivo.");
            }

            // Calcular nuevo saldo
            if ($tipoTx === 'DEPOSITO') {
                $nuevoSaldo = $banco['saldo_actual'] + $monto;
            } else {
                // RETIRO, CHEQUE, TRANSFERENCIA restan
                $nuevoSaldo = $banco['saldo_actual'] - $monto;
            }

            // 2. Actualizar cuenta bancaria
            $updBco = $this->db->prepare("UPDATE bancos_cuentas SET saldo_actual = :saldo WHERE id = :id");
            $updBco->execute(['saldo' => $nuevoSaldo, 'id' => $idBancoCuenta]);

            // 3. Insertar transacción
            $insTx = $this->db->prepare("
                INSERT INTO bancos_transacciones (id_banco_cuenta, tipo_transaccion, numero_documento, beneficiario, monto, fecha, estado, descripcion)
                VALUES (:id_banco_cuenta, :tipo_transaccion, :numero_documento, :beneficiario, :monto, :fecha, :estado, :descripcion)
            ");

            $estado = ($tipoTx === 'CHEQUE') ? 'Emitido' : 'Cobrado';

            $insTx->execute([
                'id_banco_cuenta' => $idBancoCuenta,
                'tipo_transaccion' => $tipoTx,
                'numero_documento' => !empty($datos['numero_documento']) ? trim($datos['numero_documento']) : null,
                'beneficiario' => !empty($datos['beneficiario']) ? trim($datos['beneficiario']) : null,
                'monto' => $monto,
                'fecha' => $datos['fecha'],
                'estado' => $estado,
                'descripcion' => !empty($datos['descripcion']) ? trim($datos['descripcion']) : 'Transacción manual registrada.'
            ]);

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en guardarTransaccionManual: " . $e->getMessage());
            return false;
        }
    }

    // =========================================================================
    // 5. ASINTOS CONTABLES (REGISTROS DIARIOS) Y ESTADOS FINANCIEROS (BALANCES)
    // =========================================================================

    public function registrarAsientoContable(string $fecha, string $concepto, string $origen, ?int $origen_id, array $lineas): ?int {
        // Validar descuadre
        $totalDebe = 0.0;
        $totalHaber = 0.0;
        foreach ($lineas as $l) {
            $totalDebe += (float)($l['debe'] ?? 0.0);
            $totalHaber += (float)($l['haber'] ?? 0.0);
        }
        
        if (abs($totalDebe - $totalHaber) > 0.01) {
            error_log("No se puede registrar asiento: Descuadrado (Debe: $totalDebe, Haber: $totalHaber)");
            return null;
        }

        // Generar número de partida consecutivo
        $stmtNum = $this->db->query("SELECT MAX(id) FROM partidas_diario");
        $nextId = ((int)$stmtNum->fetchColumn()) + 1;
        $numPartida = "PD-" . str_pad($nextId, 5, '0', STR_PAD_LEFT);

        $sql = "INSERT INTO partidas_diario (num_partida, fecha, concepto, origen, origen_id)
                VALUES (:num, :fecha, :concepto, :origen, :origen_id)";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            'num' => $numPartida,
            'fecha' => $fecha,
            'concepto' => $concepto,
            'origen' => $origen,
            'origen_id' => $origen_id
        ]);
        
        $partidaId = (int)$this->db->lastInsertId();

        $sqlLine = "INSERT INTO partidas_diario_detalles (id_partida, id_cuenta_contable, debe, haber)
                    VALUES (:id_partida, :id_cuenta, :debe, :haber)";
        $stmtLine = $this->db->prepare($sqlLine);
        foreach ($lineas as $l) {
            if (((float)($l['debe'] ?? 0) == 0) && ((float)($l['haber'] ?? 0) == 0)) {
                continue;
            }
            $stmtLine->execute([
                'id_partida' => $partidaId,
                'id_cuenta' => $l['id_cuenta_contable'],
                'debe' => (float)($l['debe'] ?? 0),
                'haber' => (float)($l['haber'] ?? 0)
            ]);
        }

        return $partidaId;
    }

    public function obtenerAsientos(string $busqueda = ''): array {
        $sql = "SELECT p.*, 
                       (SELECT SUM(debe) FROM partidas_diario_detalles WHERE id_partida = p.id) AS total_debe,
                       (SELECT SUM(haber) FROM partidas_diario_detalles WHERE id_partida = p.id) AS total_haber
                FROM partidas_diario p";
        if ($busqueda !== '') {
            $sql .= " WHERE p.num_partida LIKE :q1 OR p.concepto LIKE :q2 OR p.origen LIKE :q3";
            $sql .= " ORDER BY p.fecha DESC, p.num_partida DESC";
            $stmt = $this->db->prepare($sql);
            $termino = '%' . trim($busqueda) . '%';
            $stmt->execute(['q1' => $termino, 'q2' => $termino, 'q3' => $termino]);
        } else {
            $sql .= " ORDER BY p.fecha DESC, p.num_partida DESC";
            $stmt = $this->db->prepare($sql);
            $stmt->execute();
        }
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function obtenerAsientoDetalles(int $idPartida): array {
        $sql = "SELECT d.*, c.codigo AS cuenta_codigo, c.nombre AS cuenta_nombre, c.categoria
                FROM partidas_diario_detalles d
                JOIN cuentas_contables c ON d.id_cuenta_contable = c.id
                WHERE d.id_partida = :id_partida
                ORDER BY d.debe DESC, d.id ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute(['id_partida' => $idPartida]);
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    public function guardarAsientoManual(array $datos): bool {
        try {
            $this->db->beginTransaction();

            $fecha = $datos['fecha'];
            $concepto = trim($datos['concepto']);
            $cuentas = $datos['cuentas_linea'] ?? [];
            $debes = $datos['debe_linea'] ?? [];
            $habers = $datos['haber_linea'] ?? [];

            $lineas = [];
            for ($i = 0; $i < count($cuentas); $i++) {
                if (empty($cuentas[$i])) continue;
                $lineas[] = [
                    'id_cuenta_contable' => (int)$cuentas[$i],
                    'debe' => (float)($debes[$i] ?? 0.0),
                    'haber' => (float)($habers[$i] ?? 0.0)
                ];
            }

            $partidaId = $this->registrarAsientoContable($fecha, $concepto, 'MANUAL', null, $lineas);
            if (!$partidaId) {
                throw new Exception("Error al registrar asiento contable (posible descuadre).");
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en guardarAsientoManual: " . $e->getMessage());
            return false;
        }
    }

    public function eliminarAsiento(int $idPartida): bool {
        $stmt = $this->db->prepare("DELETE FROM partidas_diario WHERE id = :id AND origen = 'MANUAL'");
        return $stmt->execute(['id' => $idPartida]);
    }

    public function obtenerSaldosCuentas(string $fechaHasta): array {
        $sqlCuentas = "SELECT id, codigo, nombre, tipo, categoria, id_padre FROM cuentas_contables WHERE activo = 1 ORDER BY codigo ASC";
        $cuentas = $this->db->query($sqlCuentas)->fetchAll(PDO::FETCH_ASSOC);

        $stmtMovs = $this->db->prepare("
            SELECT d.id_cuenta_contable, SUM(d.debe) AS total_debe, SUM(d.haber) AS total_haber 
            FROM partidas_diario_detalles d
            JOIN partidas_diario p ON d.id_partida = p.id
            WHERE p.fecha <= :fecha
            GROUP BY d.id_cuenta_contable
        ");
        $stmtMovs->execute(['fecha' => $fechaHasta]);
        $saldosDetalle = [];
        while ($row = $stmtMovs->fetch(PDO::FETCH_ASSOC)) {
            $saldosDetalle[$row['id_cuenta_contable']] = [
                'debe' => (float)$row['total_debe'],
                'haber' => (float)$row['total_haber']
            ];
        }

        $plan = [];
        foreach ($cuentas as $c) {
            $id = (int)$c['id'];
            $plan[$id] = [
                'id' => $id,
                'codigo' => $c['codigo'],
                'nombre' => $c['nombre'],
                'tipo' => $c['tipo'],
                'categoria' => $c['categoria'],
                'id_padre' => $c['id_padre'] ? (int)$c['id_padre'] : null,
                'debe' => 0.0,
                'haber' => 0.0,
                'saldo' => 0.0
            ];
            if (isset($saldosDetalle[$id])) {
                $plan[$id]['debe'] = $saldosDetalle[$id]['debe'];
                $plan[$id]['haber'] = $saldosDetalle[$id]['haber'];
                
                $cat = $c['categoria'];
                if ($cat === 'ACTIVO' || $cat === 'EGRESO') {
                    $plan[$id]['saldo'] = $plan[$id]['debe'] - $plan[$id]['haber'];
                } else {
                    $plan[$id]['saldo'] = $plan[$id]['haber'] - $plan[$id]['debe'];
                }
            }
        }

        // Sumar jerárquicamente de abajo hacia arriba (mayor longitud de código a menor)
        usort($cuentas, function($a, $b) {
            return strlen($b['codigo']) - strlen($a['codigo']);
        });

        foreach ($cuentas as $c) {
            $id = (int)$c['id'];
            $id_padre = $c['id_padre'] ? (int)$c['id_padre'] : null;
            if ($id_padre && isset($plan[$id_padre])) {
                $plan[$id_padre]['debe'] += $plan[$id]['debe'];
                $plan[$id_padre]['haber'] += $plan[$id]['haber'];
                
                $pCat = $plan[$id_padre]['categoria'];
                if ($pCat === 'ACTIVO' || $pCat === 'EGRESO') {
                    $plan[$id_padre]['saldo'] = $plan[$id_padre]['debe'] - $plan[$id_padre]['haber'];
                } else {
                    $plan[$id_padre]['saldo'] = $plan[$id_padre]['haber'] - $plan[$id_padre]['debe'];
                }
            }
        }

        usort($plan, function($a, $b) {
            return strcmp($a['codigo'], $b['codigo']);
        });

        return $plan;
    }

    public function obtenerSaldosIngresosEgresos(string $fechaDesde, string $fechaHasta): array {
        $sqlCuentas = "SELECT id, codigo, nombre, tipo, categoria, id_padre FROM cuentas_contables WHERE activo = 1 ORDER BY codigo ASC";
        $cuentas = $this->db->query($sqlCuentas)->fetchAll(PDO::FETCH_ASSOC);

        $stmtMovs = $this->db->prepare("
            SELECT d.id_cuenta_contable, SUM(d.debe) AS total_debe, SUM(d.haber) AS total_haber 
            FROM partidas_diario_detalles d
            JOIN partidas_diario p ON d.id_partida = p.id
            WHERE p.fecha BETWEEN :desde AND :hasta
            GROUP BY d.id_cuenta_contable
        ");
        $stmtMovs->execute(['desde' => $fechaDesde, 'hasta' => $fechaHasta]);
        $saldosDetalle = [];
        while ($row = $stmtMovs->fetch(PDO::FETCH_ASSOC)) {
            $saldosDetalle[$row['id_cuenta_contable']] = [
                'debe' => (float)$row['total_debe'],
                'haber' => (float)$row['total_haber']
            ];
        }

        $plan = [];
        foreach ($cuentas as $c) {
            $id = (int)$c['id'];
            $plan[$id] = [
                'id' => $id,
                'codigo' => $c['codigo'],
                'nombre' => $c['nombre'],
                'tipo' => $c['tipo'],
                'categoria' => $c['categoria'],
                'id_padre' => $c['id_padre'] ? (int)$c['id_padre'] : null,
                'debe' => 0.0,
                'haber' => 0.0,
                'saldo' => 0.0
            ];
            if (isset($saldosDetalle[$id])) {
                $plan[$id]['debe'] = $saldosDetalle[$id]['debe'];
                $plan[$id]['haber'] = $saldosDetalle[$id]['haber'];
                
                $cat = $c['categoria'];
                if ($cat === 'ACTIVO' || $cat === 'EGRESO') {
                    $plan[$id]['saldo'] = $plan[$id]['debe'] - $plan[$id]['haber'];
                } else {
                    $plan[$id]['saldo'] = $plan[$id]['haber'] - $plan[$id]['debe'];
                }
            }
        }

        usort($cuentas, function($a, $b) {
            return strlen($b['codigo']) - strlen($a['codigo']);
        });

        foreach ($cuentas as $c) {
            $id = (int)$c['id'];
            $id_padre = $c['id_padre'] ? (int)$c['id_padre'] : null;
            if ($id_padre && isset($plan[$id_padre])) {
                $plan[$id_padre]['debe'] += $plan[$id]['debe'];
                $plan[$id_padre]['haber'] += $plan[$id]['haber'];
                
                $pCat = $plan[$id_padre]['categoria'];
                if ($pCat === 'ACTIVO' || $pCat === 'EGRESO') {
                    $plan[$id_padre]['saldo'] = $plan[$id_padre]['debe'] - $plan[$id_padre]['haber'];
                } else {
                    $plan[$id_padre]['saldo'] = $plan[$id_padre]['haber'] - $plan[$id_padre]['debe'];
                }
            }
        }

        usort($plan, function($a, $b) {
            return strcmp($a['codigo'], $b['codigo']);
        });

        return $plan;
    }

    public function reconstruirDiario(): bool {
        try {
            $this->db->beginTransaction();

            // 1. Limpiar asientos contables viejos
            $this->db->exec("DELETE FROM partidas_diario_detalles");
            $this->db->exec("DELETE FROM partidas_diario");

            // Función ayudante para encontrar el ID de una cuenta por código
            $findAccount = function(string $prefix, string $fallbackName) {
                $stmt = $this->db->prepare("SELECT id FROM cuentas_contables WHERE codigo LIKE :pref LIMIT 1");
                $stmt->execute(['pref' => $prefix . '%']);
                $id = $stmt->fetchColumn();
                if ($id) return (int)$id;
                
                $stmtName = $this->db->prepare("SELECT id FROM cuentas_contables WHERE nombre LIKE :name LIMIT 1");
                $stmtName->execute(['name' => '%' . $fallbackName . '%']);
                $id = $stmtName->fetchColumn();
                return $id ? (int)$id : 4; // Caja Principal por defecto si no hay nada
            };

            $cxcDefaultAcc = $findAccount('10102', 'CLIENTES');
            $cxpDefaultAcc = $findAccount('201', 'PROVEEDORES');
            $incomeDefaultAcc = 206; // PROYECTOS GRABADOS
            $expenseDefaultAcc = 221; // MATERIALES DE CONSTRUCCION
            $capitalDefaultAcc = 183; // CAPITAL PAGADO

            // 2. Saldos Iniciales de Bancos
            $bancos = $this->db->query("SELECT id, banco_nombre, numero_cuenta, id_cuenta_contable, saldo_inicial, fecha_registro FROM bancos_cuentas")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($bancos as $b) {
                if ((float)$b['saldo_inicial'] > 0) {
                    $fecha = date('Y-m-d', strtotime($b['fecha_registro']));
                    $bancoCuentaId = $b['id_cuenta_contable'] ?: $findAccount('102', 'BANCO');
                    $this->registrarAsientoContable(
                        $fecha,
                        "Partida de Apertura / Saldo Inicial - Banco " . $b['banco_nombre'] . " " . $b['numero_cuenta'],
                        'BANCO_APERTURA',
                        $b['id'],
                        [
                            ['id_cuenta_contable' => $bancoCuentaId, 'debe' => $b['saldo_inicial'], 'haber' => 0.0],
                            ['id_cuenta_contable' => $capitalDefaultAcc, 'debe' => 0.0, 'haber' => $b['saldo_inicial']]
                        ]
                    );
                }
            }

            // 3. Facturación / Invoices de CXC (Separando IVA 15%)
            $cxcList = $this->db->query("SELECT id, id_cuenta_contable, factura_numero, monto, fecha_emision FROM cuentas_por_cobrar")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cxcList as $cxc) {
                $debitAcc = $cxc['id_cuenta_contable'] ?: $cxcDefaultAcc;
                $total = (float)$cxc['monto'];
                $subtotal = round($total / 1.15, 2);
                $iva = round($total - $subtotal, 2);
                
                $this->registrarAsientoContable(
                    $cxc['fecha_emision'],
                    "Registro de Venta / Factura N° " . $cxc['factura_numero'],
                    'CXC',
                    $cxc['id'],
                    [
                        ['id_cuenta_contable' => $debitAcc, 'debe' => $total, 'haber' => 0.0],
                        ['id_cuenta_contable' => $incomeDefaultAcc, 'debe' => 0.0, 'haber' => $subtotal],
                        ['id_cuenta_contable' => 154, 'debe' => 0.0, 'haber' => $iva] // 2010508 - IVA POR PAGAR
                    ]
                );
            }

            // 4. Invoices de Gastos / CXP (Separando IVA 15%)
            $cxpList = $this->db->query("SELECT id, id_cuenta_contable, proveedor_nombre, factura_numero, monto, fecha_emision FROM cuentas_por_pagar")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($cxpList as $cxp) {
                $debitAcc = $cxp['id_cuenta_contable'] ?: $expenseDefaultAcc;
                $total = (float)$cxp['monto'];
                $subtotal = round($total / 1.15, 2);
                $iva = round($total - $subtotal, 2);
                
                $this->registrarAsientoContable(
                    $cxp['fecha_emision'],
                    "Registro de Gasto / Factura N° " . $cxp['factura_numero'] . " - " . $cxp['proveedor_nombre'],
                    'CXP',
                    $cxp['id'],
                    [
                        ['id_cuenta_contable' => $debitAcc, 'debe' => $subtotal, 'haber' => 0.0],
                        ['id_cuenta_contable' => 82, 'debe' => $iva, 'haber' => 0.0], // 1010601 - IVA 15% ACREDITABLE
                        ['id_cuenta_contable' => $cxpDefaultAcc, 'debe' => 0.0, 'haber' => $total]
                    ]
                );
            }

            // 5. Transacciones de Banco (Cobros, Pagos y Manuales)
            $txs = $this->db->query("
                SELECT bt.*, bc.id_cuenta_contable AS banco_cuenta_id, bc.banco_nombre 
                FROM bancos_transacciones bt
                JOIN bancos_cuentas bc ON bt.id_banco_cuenta = bc.id
            ")->fetchAll(PDO::FETCH_ASSOC);

            foreach ($txs as $tx) {
                $desc = $tx['descripcion'];
                $monto = (float)$tx['monto'];
                $fecha = $tx['fecha'];
                $bancoAcc = $tx['banco_cuenta_id'] ?: $findAccount('102', 'BANCO');
                
                if (stripos($desc, 'Abono a Factura') !== false || stripos($desc, 'Cobro') !== false) {
                    $cxcAcc = $cxcDefaultAcc;
                    preg_match('/N° ([\w-]+)/i', $desc, $matches);
                    if (!empty($matches[1])) {
                        $stmtCxc = $this->db->prepare("SELECT id_cuenta_contable FROM cuentas_por_cobrar WHERE factura_numero = :num LIMIT 1");
                        $stmtCxc->execute(['num' => trim($matches[1])]);
                        $foundCxcAcc = $stmtCxc->fetchColumn();
                        if ($foundCxcAcc) $cxcAcc = (int)$foundCxcAcc;
                    }
                    
                    $this->registrarAsientoContable(
                        $fecha,
                        "Cobro / " . $desc,
                        'CXC_PAGO',
                        $tx['id'],
                        [
                            ['id_cuenta_contable' => $bancoAcc, 'debe' => $monto, 'haber' => 0.0],
                            ['id_cuenta_contable' => $cxcAcc, 'debe' => 0.0, 'haber' => $monto]
                        ]
                    );
                } 
                elseif (stripos($desc, 'Pago a Proveedor') !== false || stripos($desc, 'Pago') !== false) {
                    $this->registrarAsientoContable(
                        $fecha,
                        "Pago / " . $desc,
                        'CXP_PAGO',
                        $tx['id'],
                        [
                            ['id_cuenta_contable' => $cxpDefaultAcc, 'debe' => $monto, 'haber' => 0.0],
                            ['id_cuenta_contable' => $bancoAcc, 'debe' => 0.0, 'haber' => $monto]
                        ]
                    );
                } 
                else {
                    if ($tx['tipo_transaccion'] === 'DEPOSITO') {
                        $this->registrarAsientoContable(
                            $fecha,
                            $desc,
                            'BANCO_TX',
                            $tx['id'],
                            [
                                ['id_cuenta_contable' => $bancoAcc, 'debe' => $monto, 'haber' => 0.0],
                                ['id_cuenta_contable' => $incomeDefaultAcc, 'debe' => 0.0, 'haber' => $monto]
                            ]
                        );
                    } else {
                        $this->registrarAsientoContable(
                            $fecha,
                            $desc,
                            'BANCO_TX',
                            $tx['id'],
                            [
                                ['id_cuenta_contable' => $expenseDefaultAcc, 'debe' => $monto, 'haber' => 0.0],
                                ['id_cuenta_contable' => $bancoAcc, 'debe' => 0.0, 'haber' => $monto]
                            ]
                        );
                    }
                }
            }

            $this->db->commit();
            return true;
        } catch (Exception $e) {
            if ($this->db->inTransaction()) {
                $this->db->rollBack();
            }
            error_log("Error en reconstruirDiario: " . $e->getMessage());
            return false;
        }
    }

    public function obtenerReferenciaOrigen(string $origen, ?int $origenId): ?array {
        if (!$origenId) return null;
        
        try {
            switch (strtoupper(trim($origen))) {
                case 'CXC':
                    $stmt = $this->db->prepare("
                        SELECT cxc.factura_numero AS documento, cxc.monto, cl.nombre_razon_social AS tercero, cxc.notas AS notas
                        FROM cuentas_por_cobrar cxc
                        LEFT JOIN clientes cl ON cxc.id_cliente = cl.id
                        WHERE cxc.id = :id
                    ");
                    $stmt->execute(['id' => $origenId]);
                    $res = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($res) {
                        return [
                            'tipo' => 'Factura de Cliente',
                            'tercero' => $res['tercero'],
                            'documento' => $res['documento'],
                            'monto' => $res['monto'],
                            'detalle' => $res['notas']
                        ];
                    }
                    break;
                case 'CXC_PAGO':
                    $stmt = $this->db->prepare("
                        SELECT cxc.factura_numero AS documento, cxc.monto AS monto_total, cl.nombre_razon_social AS tercero
                        FROM cuentas_por_cobrar cxc
                        LEFT JOIN clientes cl ON cxc.id_cliente = cl.id
                        WHERE cxc.id = :id
                    ");
                    $stmt->execute(['id' => $origenId]);
                    $res = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($res) {
                        return [
                            'tipo' => 'Abono / Pago de Cliente',
                            'tercero' => $res['tercero'],
                            'documento' => $res['documento'],
                            'monto' => null,
                            'detalle' => 'Cobro realizado para Factura N° ' . $res['documento']
                        ];
                    }
                    break;
                case 'CXP':
                    $stmt = $this->db->prepare("
                        SELECT cxp.factura_numero AS documento, cxp.monto, prov.nombre_razon_social AS tercero, cxp.notas AS notas
                        FROM cuentas_por_pagar cxp
                        LEFT JOIN proveedores prov ON cxp.id_proveedor = prov.id
                        WHERE cxp.id = :id
                    ");
                    $stmt->execute(['id' => $origenId]);
                    $res = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($res) {
                        return [
                            'tipo' => 'Factura de Proveedor',
                            'tercero' => $res['tercero'],
                            'documento' => $res['documento'],
                            'monto' => $res['monto'],
                            'detalle' => $res['notas']
                        ];
                    }
                    break;
                case 'CXP_PAGO':
                    $stmt = $this->db->prepare("
                        SELECT cxp.factura_numero AS documento, cxp.monto AS monto_total, prov.nombre_razon_social AS tercero
                        FROM cuentas_por_pagar cxp
                        LEFT JOIN proveedores prov ON cxp.id_proveedor = prov.id
                        WHERE cxp.id = :id
                    ");
                    $stmt->execute(['id' => $origenId]);
                    $res = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($res) {
                        return [
                            'tipo' => 'Pago a Proveedor',
                            'tercero' => $res['tercero'],
                            'documento' => $res['documento'],
                            'monto' => null,
                            'detalle' => 'Desembolso realizado para Factura N° ' . $res['documento']
                        ];
                    }
                    break;
                case 'BANCO_TX':
                    $stmt = $this->db->prepare("
                        SELECT bt.tipo_transaccion, bt.numero_documento AS documento, bt.beneficiario AS tercero, bt.monto, bt.descripcion AS detalle, bc.banco_nombre, bc.numero_cuenta
                        FROM bancos_transacciones bt
                        LEFT JOIN bancos_cuentas bc ON bt.id_banco_cuenta = bc.id
                        WHERE bt.id = :id
                    ");
                    $stmt->execute(['id' => $origenId]);
                    $res = $stmt->fetch(PDO::FETCH_ASSOC);
                    if ($res) {
                        return [
                            'tipo' => 'Transacción Bancaria (' . $res['tipo_transaccion'] . ')',
                            'tercero' => $res['tercero'],
                            'documento' => $res['documento'],
                            'monto' => $res['monto'],
                            'detalle' => $res['detalle'] . ' (Cta: ' . $res['banco_nombre'] . ' - ' . $res['numero_cuenta'] . ')'
                        ];
                    }
                    break;
            }
        } catch (Exception $e) {
            error_log("Error en obtenerReferenciaOrigen: " . $e->getMessage());
        }
        
        return null;
    }

    public function obtenerBancoAfectado(int $idPartida): ?array {
        try {
            $stmt = $this->db->prepare("
                SELECT bc.banco_nombre, bc.numero_cuenta, bc.moneda
                FROM partidas_diario_detalles pdd
                JOIN bancos_cuentas bc ON pdd.id_cuenta_contable = bc.id_cuenta_contable
                WHERE pdd.id_partida = :id_partida
                LIMIT 1
            ");
            $stmt->execute(['id_partida' => $idPartida]);
            return $stmt->fetch(PDO::FETCH_ASSOC) ?: null;
        } catch (Exception $e) {
            error_log("Error en obtenerBancoAfectado: " . $e->getMessage());
            return null;
        }
    }
}

