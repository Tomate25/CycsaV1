<?php

namespace Cycsa\Modulos\Contabilidad\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo;
use Cycsa\Modulos\Clientes\Modelos\ClienteModelo;

class ContabilidadControlador extends ControladorBase {
    
    private function verificarSesion(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
    }

    private function verificarPermiso(Respuesta $respuesta, string $accion = 'ver'): void {
        if (!tienePermiso('contabilidad', $accion)) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
    }

    // ==========================================
    // 1. Cuentas Contables (Catálogo de Cuentas)
    // ==========================================

    public function cuentas(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $modelo = new ContabilidadModelo();
        $busqueda = $_GET['q'] ?? '';

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('contabilidad/vistas/cuentas', [
            'titulo' => 'Catálogo de Cuentas Contables - Cycsa',
            'cuentas' => $modelo->obtenerCuentas($busqueda),
            'cuentasMayor' => $modelo->obtenerCuentasMayor(),
            'busqueda' => $busqueda,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    public function guardarCuenta(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new ContabilidadModelo();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/cuentas');
                return;
            }

            if (empty(trim($datos['codigo'])) || empty(trim($datos['nombre']))) {
                $_SESSION['error'] = 'Código y Nombre son obligatorios.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/cuentas');
                return;
            }

            if ($modelo->codigoExiste($datos['codigo'])) {
                $_SESSION['error'] = 'El código de cuenta ya está registrado.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/cuentas');
                return;
            }

            if ($modelo->guardarCuenta($datos)) {
                registrarBitacora('contabilidad', 'crear_cuenta', 'Creada cuenta contable: ' . $datos['codigo'] . ' - ' . $datos['nombre']);
                $_SESSION['exito'] = 'Cuenta contable registrada exitosamente.';
            } else {
                $_SESSION['error'] = 'Error al registrar la cuenta contable.';
            }

            $respuesta->redirigir('/Cycsa/publico/contabilidad/cuentas');
        }
    }

    // ==========================================
    // 2. Cuentas por Cobrar (CXC)
    // ==========================================

    public function cxc(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $modelo = new ContabilidadModelo();
        $clienteModelo = new ClienteModelo();
        $busqueda = $_GET['q'] ?? '';

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('contabilidad/vistas/cxc', [
            'titulo' => 'Cuentas por Cobrar (CXC) - Cycsa',
            'cxcList' => $modelo->obtenerCxc($busqueda),
            'clientes' => $clienteModelo->obtenerTodos(),
            'cuentasDetalle' => $modelo->obtenerCuentasDetalle(),
            'bancos' => $modelo->obtenerCuentasBancarias(),
            'busqueda' => $busqueda,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    public function guardarCxc(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new ContabilidadModelo();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/cxc');
                return;
            }

            if (empty($datos['id_cliente']) || empty($datos['factura_numero']) || empty($datos['monto']) || empty($datos['fecha_emision'])) {
                $_SESSION['error'] = 'Todos los campos excepto la cuenta y notas son obligatorios.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/cxc');
                return;
            }

            if ($modelo->guardarCxc($datos)) {
                registrarBitacora('contabilidad', 'crear_cxc', 'Creada cuenta por cobrar N°: ' . $datos['factura_numero']);
                $_SESSION['exito'] = 'Cuenta por cobrar registrada exitosamente.';
            } else {
                $_SESSION['error'] = 'Error al registrar la cuenta por cobrar.';
            }

            $respuesta->redirigir('/Cycsa/publico/contabilidad/cxc');
        }
    }

    public function pagarCxc(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new ContabilidadModelo();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/cxc');
                return;
            }

            $idCxc = (int)$datos['id_cxc'];
            $monto = (float)$datos['monto_pago'];
            $idBanco = (int)$datos['id_banco_cuenta'];
            $ref = $datos['referencia'];
            $fecha = $datos['fecha_pago'];

            if (empty($idCxc) || empty($monto) || empty($idBanco) || empty($fecha)) {
                $_SESSION['error'] = 'Todos los campos del pago son requeridos.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/cxc');
                return;
            }

            if ($modelo->registrarPagoCxc($idCxc, $monto, $idBanco, $ref, $fecha)) {
                registrarBitacora('contabilidad', 'pago_cxc', 'Registrado cobro de C$' . $monto . ' para CXC N° ' . $idCxc);
                $_SESSION['exito'] = 'Cobro registrado y banco actualizado exitosamente.';
            } else {
                $_SESSION['error'] = 'Error al procesar el cobro. Verifique saldos o conexión.';
            }

            $respuesta->redirigir('/Cycsa/publico/contabilidad/cxc');
        }
    }

    // ==========================================
    // 3. Cuentas por Pagar (CXP)
    // ==========================================

    public function cxp(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $modelo = new ContabilidadModelo();
        $busqueda = $_GET['q'] ?? '';

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('contabilidad/vistas/cxp', [
            'titulo' => 'Cuentas por Pagar (CXP) - Cycsa',
            'cxpList' => $modelo->obtenerCxp($busqueda),
            'cuentasDetalle' => $modelo->obtenerCuentasDetalle(),
            'bancos' => $modelo->obtenerCuentasBancarias(),
            'busqueda' => $busqueda,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    public function guardarCxp(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new ContabilidadModelo();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/cxp');
                return;
            }

            if (empty($datos['proveedor_nombre']) || empty($datos['factura_numero']) || empty($datos['monto']) || empty($datos['fecha_emision'])) {
                $_SESSION['error'] = 'Todos los campos excepto la cuenta y notas son obligatorios.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/cxp');
                return;
            }

            if ($modelo->guardarCxp($datos)) {
                registrarBitacora('contabilidad', 'crear_cxp', 'Creada cuenta por pagar N°: ' . $datos['factura_numero']);
                $_SESSION['exito'] = 'Cuenta por pagar registrada exitosamente.';
            } else {
                $_SESSION['error'] = 'Error al registrar la cuenta por pagar.';
            }

            $respuesta->redirigir('/Cycsa/publico/contabilidad/cxp');
        }
    }

    public function pagarCxp(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new ContabilidadModelo();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/cxp');
                return;
            }

            $idCxp = (int)$datos['id_cxp'];
            $monto = (float)$datos['monto_pago'];
            $idBanco = (int)$datos['id_banco_cuenta'];
            $ref = $datos['referencia'];
            $fecha = $datos['fecha_pago'];
            $tipoTransaccion = $datos['tipo_transaccion_pago'] ?? 'RETIRAR'; // CHEQUE or RETIRO

            if (empty($idCxp) || empty($monto) || empty($idBanco) || empty($fecha)) {
                $_SESSION['error'] = 'Todos los campos del pago son requeridos.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/cxp');
                return;
            }

            if ($modelo->registrarPagoCxp($idCxp, $monto, $idBanco, $ref, $fecha, $tipoTransaccion)) {
                registrarBitacora('contabilidad', 'pago_cxp', 'Registrado pago de C$' . $monto . ' para CXP N° ' . $idCxp);
                $_SESSION['exito'] = 'Pago registrado, egreso del banco y documento emitido exitosamente.';
            } else {
                $_SESSION['error'] = 'Error al procesar el pago. Verifique saldos o conexión.';
            }

            $respuesta->redirigir('/Cycsa/publico/contabilidad/cxp');
        }
    }

    // ==========================================
    // 4. Bancos / Chequera
    // ==========================================

    public function bancos(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $modelo = new ContabilidadModelo();
        $idBancoCuenta = isset($_GET['banco_id']) ? (int)$_GET['banco_id'] : 0;

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('contabilidad/vistas/bancos', [
            'titulo' => 'Bancos y Chequera - Cycsa',
            'bancos' => $modelo->obtenerCuentasBancarias(),
            'transacciones' => $modelo->obtenerTransaccionesBancarias($idBancoCuenta),
            'cuentasDetalle' => $modelo->obtenerCuentasDetalle(),
            'filtroBancoId' => $idBancoCuenta,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    public function guardarBanco(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new ContabilidadModelo();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/bancos');
                return;
            }

            if (empty($datos['banco_nombre']) || empty($datos['numero_cuenta']) || empty($datos['moneda'])) {
                $_SESSION['error'] = 'Banco, número de cuenta y moneda son obligatorios.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/bancos');
                return;
            }

            if ($modelo->guardarCuentaBancaria($datos)) {
                registrarBitacora('contabilidad', 'crear_banco', 'Registrada cuenta bancaria: ' . $datos['banco_nombre'] . ' - ' . $datos['numero_cuenta']);
                $_SESSION['exito'] = 'Cuenta bancaria registrada exitosamente.';
            } else {
                $_SESSION['error'] = 'Error al registrar la cuenta bancaria.';
            }

            $respuesta->redirigir('/Cycsa/publico/contabilidad/bancos');
        }
    }

    public function guardarTransaccion(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new ContabilidadModelo();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/bancos');
                return;
            }

            if (empty($datos['id_banco_cuenta']) || empty($datos['tipo_transaccion']) || empty($datos['monto']) || empty($datos['fecha'])) {
                $_SESSION['error'] = 'Cuenta, tipo de transacción, monto y fecha son obligatorios.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/bancos');
                return;
            }

            if ($modelo->guardarTransaccionManual($datos)) {
                registrarBitacora('contabilidad', 'crear_tx_banco', 'Registrada transacción en banco ID: ' . $datos['id_banco_cuenta'] . ' por C$' . $datos['monto']);
                $_SESSION['exito'] = 'Transacción registrada y saldo de banco actualizado.';
            } else {
                $_SESSION['error'] = 'Error al registrar la transacción en banco.';
            }

            $respuesta->redirigir('/Cycsa/publico/contabilidad/bancos' . ($datos['id_banco_cuenta'] ? '?banco_id=' . $datos['id_banco_cuenta'] : ''));
        }
    }
}
