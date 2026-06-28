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
        $sql = "INSERT INTO cuentas_por_cobrar (id_cliente, id_cuenta_contable, factura_numero, monto, saldo, estado, fecha_emision, fecha_vencimiento, notas) 
                VALUES (:id_cliente, :id_cuenta_contable, :factura_numero, :monto, :saldo, 'Pendiente', :fecha_emision, :fecha_vencimiento, :notas)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'id_cliente' => !empty($datos['id_cliente']) ? (int)$datos['id_cliente'] : null,
            'id_cuenta_contable' => !empty($datos['id_cuenta_contable']) ? (int)$datos['id_cuenta_contable'] : null,
            'factura_numero' => trim($datos['factura_numero']),
            'monto' => (float)$datos['monto'],
            'saldo' => (float)$datos['monto'], // Saldo inicial es igual al monto
            'fecha_emision' => $datos['fecha_emision'],
            'fecha_vencimiento' => !empty($datos['fecha_vencimiento']) ? $datos['fecha_vencimiento'] : null,
            'notas' => !empty($datos['notas']) ? trim($datos['notas']) : null
        ]);
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
        $sql = "INSERT INTO cuentas_por_pagar (proveedor_nombre, id_cuenta_contable, factura_numero, monto, saldo, estado, fecha_emision, fecha_vencimiento, notas) 
                VALUES (:proveedor_nombre, :id_cuenta_contable, :factura_numero, :monto, :saldo, 'Pendiente', :fecha_emision, :fecha_vencimiento, :notas)";
        
        $stmt = $this->db->prepare($sql);
        return $stmt->execute([
            'proveedor_nombre' => trim($datos['proveedor_nombre']),
            'id_cuenta_contable' => !empty($datos['id_cuenta_contable']) ? (int)$datos['id_cuenta_contable'] : null,
            'factura_numero' => trim($datos['factura_numero']),
            'monto' => (float)$datos['monto'],
            'saldo' => (float)$datos['monto'],
            'fecha_emision' => $datos['fecha_emision'],
            'fecha_vencimiento' => !empty($datos['fecha_vencimiento']) ? $datos['fecha_vencimiento'] : null,
            'notes' => !empty($datos['notas']) ? trim($datos['notas']) : null
        ]);
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
}
