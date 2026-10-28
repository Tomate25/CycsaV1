<?php

namespace Cycsa\Modulos\Contabilidad\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo;
use Cycsa\Modulos\Contabilidad\Servicios\ExportadorCsv;
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

        $bitacora_logs = obtenerBitacoraModulo('contabilidad');

        $this->renderizar('contabilidad/vistas/cuentas', [
            'subnavActiva' => 'cuentas',
            'titulo' => 'Catálogo de Cuentas Contables - Cycsa',
            'cuentas' => $modelo->obtenerCuentas($busqueda),
            'cuentasMayor' => $modelo->obtenerCuentasMayor(),
            'busqueda' => $busqueda,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
            'bitacora_logs' => $bitacora_logs
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
        $busqueda = trim($_GET['q'] ?? '');
        $antiguedad = trim($_GET['antiguedad'] ?? '');

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Obtener listado base para métricas de resumen
        $todasCxc = $modelo->obtenerCxc($busqueda);
        $resumenAntiguedad = $modelo->obtenerResumenAntiguedadCxc($todasCxc);

        // Filtrar si el usuario seleccionó una cubeta de antigüedad específica
        $cxcList = ($antiguedad !== '') ? $modelo->obtenerCxc($busqueda, $antiguedad) : $todasCxc;

        $this->renderizar('contabilidad/vistas/cxc', [
            'subnavActiva' => 'cxc',
            'titulo' => 'Cuentas por Cobrar (CXC) - Cycsa',
            'cxcList' => $cxcList,
            'resumenAntiguedad' => $resumenAntiguedad,
            'filtroAntiguedad' => $antiguedad,
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
            'subnavActiva' => 'cxp',
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
            $esAjax = $peticion->esAjax();
            $datos = $peticion->obtenerDatos();
            if (empty($datos)) {
                $raw = file_get_contents('php://input');
                if (!empty($raw)) {
                    $json = json_decode($raw, true);
                    if (is_array($json)) {
                        $datos = $json;
                        $esAjax = true;
                    }
                }
            }

            $modelo = new ContabilidadModelo();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                if ($esAjax) {
                    $respuesta->enviarJson(['ok' => false, 'exito' => false, 'error' => 'Token CSRF inválido.'], 403);
                    return;
                }
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/cxp');
                return;
            }

            // Detectar si se recibieron partidas detalladas (array PHP o JSON)
            $lineas = [];
            if (!empty($datos['lineas'])) {
                if (is_string($datos['lineas'])) {
                    $dec = json_decode($datos['lineas'], true);
                    if (is_array($dec)) {
                        $lineas = $dec;
                    }
                } elseif (is_array($datos['lineas'])) {
                    $lineas = $datos['lineas'];
                }
            } elseif (!empty($datos['lineas_json']) && is_string($datos['lineas_json'])) {
                $dec = json_decode($datos['lineas_json'], true);
                if (is_array($dec)) {
                    $lineas = $dec;
                }
            } elseif (!empty($datos['detalle'])) {
                if (is_string($datos['detalle'])) {
                    $dec = json_decode($datos['detalle'], true);
                    if (is_array($dec)) {
                        $lineas = $dec;
                    }
                } elseif (is_array($datos['detalle'])) {
                    $lineas = $datos['detalle'];
                }
            } elseif (isset($datos['linea_cuenta_id']) && is_array($datos['linea_cuenta_id'])) {
                foreach ($datos['linea_cuenta_id'] as $idx => $ctaId) {
                    if (!empty($ctaId)) {
                        $lineas[] = [
                            'cuenta_contable_id' => (int)$ctaId,
                            'concepto'           => $datos['linea_concepto'][$idx] ?? '',
                            'monto'              => (float)($datos['linea_monto'][$idx] ?? 0),
                            'iva'                => (float)($datos['linea_iva'][$idx] ?? 0),
                            'retencion_ir'       => (float)($datos['linea_retencion_ir'][$idx] ?? 0),
                            'retencion_alcaldia' => (float)($datos['linea_retencion_alcaldia'][$idx] ?? 0),
                        ];
                    }
                }
            }

            // Si hay partidas multi-línea, procesar mediante guardarCxpConDetalle
            if (!empty($lineas)) {
                $resultado = $modelo->guardarCxpConDetalle($datos, $lineas);
                if ($resultado['exito']) {
                    registrarBitacora('contabilidad', 'crear_cxp_detalle', 'Creada cuenta por pagar N°: ' . ($datos['factura_numero'] ?? '') . ' con ' . count($lineas) . ' partidas.');
                    if ($esAjax) {
                        $respuesta->enviarJson([
                            'ok'                       => true,
                            'exito'                    => true,
                            'mensaje'                  => $resultado['mensaje'] ?? 'Cuenta por pagar registrada exitosamente.',
                            'cxp_id'                   => $resultado['cxp_id'] ?? null,
                            'id'                       => $resultado['cxp_id'] ?? null,
                            'asiento_id'               => $resultado['asiento_id'] ?? null,
                            'total_subtotal'           => $resultado['total_subtotal'] ?? null,
                            'total_iva'                => $resultado['total_iva'] ?? null,
                            'total_retencion_ir'       => $resultado['total_retencion_ir'] ?? null,
                            'total_retencion_alcaldia' => $resultado['total_retencion_alcaldia'] ?? null,
                            'total_neto'               => $resultado['total_neto'] ?? null,
                            'lineas_guardadas'         => $resultado['lineas_guardadas'] ?? count($lineas),
                        ]);
                        return;
                    }
                    $_SESSION['exito'] = 'Cuenta por pagar registrada exitosamente con partidas multi-línea.';
                    $respuesta->redirigir('/Cycsa/publico/contabilidad/cxp');
                    return;
                } else {
                    $errMsg = $resultado['mensaje'] ?? $resultado['error'] ?? 'Error al registrar la cuenta por pagar.';
                    if ($esAjax) {
                        $respuesta->enviarJson(['ok' => false, 'exito' => false, 'error' => $errMsg, 'mensaje' => $errMsg], 422);
                        return;
                    }
                    $_SESSION['error'] = $errMsg;
                    $respuesta->redirigir('/Cycsa/publico/contabilidad/cxp');
                    return;
                }
            }

            // Modo estándar / legado (una sola partida global)
            if ((empty($datos['proveedor_nombre']) && empty($datos['proveedor_id'])) || empty($datos['factura_numero']) || empty($datos['monto']) || empty($datos['fecha_emision'])) {
                $errMsg = 'Todos los campos excepto la cuenta y notas son obligatorios.';
                if ($esAjax) {
                    $respuesta->enviarJson(['ok' => false, 'exito' => false, 'error' => $errMsg, 'mensaje' => $errMsg], 400);
                    return;
                }
                $_SESSION['error'] = $errMsg;
                $respuesta->redirigir('/Cycsa/publico/contabilidad/cxp');
                return;
            }

            if ($modelo->guardarCxp($datos)) {
                registrarBitacora('contabilidad', 'crear_cxp', 'Creada cuenta por pagar N°: ' . $datos['factura_numero']);
                if ($esAjax) {
                    $respuesta->enviarJson(['ok' => true, 'exito' => true, 'mensaje' => 'Cuenta por pagar registrada exitosamente.']);
                    return;
                }
                $_SESSION['exito'] = 'Cuenta por pagar registrada exitosamente.';
            } else {
                if ($esAjax) {
                    $respuesta->enviarJson(['ok' => false, 'exito' => false, 'error' => 'Error al registrar la cuenta por pagar.'], 500);
                    return;
                }
                $_SESSION['error'] = 'Error al registrar la cuenta por pagar.';
            }

            $respuesta->redirigir('/Cycsa/publico/contabilidad/cxp');
        }
    }

    /**
     * TAREA-049: Consulta las partidas de detalle de una CXP vía API / AJAX.
     */
    public function detalleCxp(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $cxpId = (int)($peticion->obtenerParametro('id') ?? $_GET['id'] ?? 0);
        if ($cxpId <= 0) {
            $respuesta->enviarJson(['ok' => false, 'exito' => false, 'error' => 'ID de CXP no válido.'], 400);
            return;
        }

        $modelo = new ContabilidadModelo();
        $lineas = $modelo->obtenerDetalleCxp($cxpId);
        $cxp = $modelo->obtenerCxpPorId($cxpId);

        $respuesta->enviarJson([
            'ok'     => true,
            'exito'  => true,
            'cxp_id' => $cxpId,
            'cxp'    => $cxp,
            'lineas' => $lineas
        ]);
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
            'subnavActiva' => 'bancos',
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

    // ==========================================
    // 5. Diario Contable (Registro Diario)
    // ==========================================

    public function diario(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $modelo = new ContabilidadModelo();
        $busqueda = $_GET['q'] ?? '';

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Obtener partidas y sus detalles
        $asientosRaw = $modelo->obtenerAsientos($busqueda);
        $asientos = [];
        foreach ($asientosRaw as $as) {
            $as['detalles'] = $modelo->obtenerAsientoDetalles($as['id']);
            $as['referencia_origen'] = $modelo->obtenerReferenciaOrigen($as['origen'], $as['origen_id']);
            $as['banco_afectado'] = $modelo->obtenerBancoAfectado($as['id']);
            $asientos[] = $as;
        }

        $this->renderizar('contabilidad/vistas/diario', [
            'subnavActiva' => 'diario',
            'titulo' => 'Registro Diario Contable - Cycsa',
            'asientos' => $asientos,
            'cuentasDetalle' => $modelo->obtenerCuentasDetalle(),
            'busqueda' => $busqueda,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    public function guardarPartida(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new ContabilidadModelo();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/contabilidad/diario');
                return;
            }

            if ($modelo->guardarAsientoManual($datos)) {
                registrarBitacora('contabilidad', 'crear_asiento', 'Registrado asiento contable manual');
                $_SESSION['exito'] = 'Asiento contable registrado exitosamente.';
            } else {
                $_SESSION['error'] = 'Error al registrar el asiento. Verifique que las cuentas cuadren (Debe = Haber) y los campos obligatorios estén llenos.';
            }

            $respuesta->redirigir('/Cycsa/publico/contabilidad/diario');
        }
    }

    public function sincronizarDiario(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        $modelo = new ContabilidadModelo();
        if ($modelo->reconstruirDiario()) {
            registrarBitacora('contabilidad', 'sincronizar_diario', 'Diario contable reconstruido y sincronizado');
            $_SESSION['exito'] = 'Diario contable sincronizado e integrado con éxito con todos los módulos de bancos, CXC y CXP.';
        } else {
            $_SESSION['error'] = 'Error al sincronizar el diario contable.';
        }

        $respuesta->redirigir('/Cycsa/publico/contabilidad/diario');
    }

    // ==========================================
    // Exportaciones CSV para conciliación
    // ==========================================

    public function exportarCxcCsv(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $busqueda = trim((string)($_GET['q'] ?? ''));
        $antiguedad = trim((string)($_GET['antiguedad'] ?? ''));
        $filtrosPermitidos = ['', 'corriente', '1_30', '31_60', 'mas_60', 'pagado', 'vencido'];
        if (!in_array($antiguedad, $filtrosPermitidos, true)) {
            $antiguedad = '';
        }

        $filas = [];
        foreach ((new ContabilidadModelo())->obtenerCxc($busqueda, $antiguedad) as $cxc) {
            $filas[] = [
                $cxc['cliente_nombre'] ?? 'Cliente desconocido',
                $cxc['factura_numero'] ?? '',
                trim(($cxc['cuenta_codigo'] ?? '') . ' - ' . ($cxc['cuenta_nombre'] ?? ''), ' -'),
                number_format((float)($cxc['monto'] ?? 0), 2, '.', ''),
                number_format((float)($cxc['saldo'] ?? 0), 2, '.', ''),
                $cxc['estado'] ?? '',
                $cxc['fecha_emision'] ?? '',
                $cxc['fecha_vencimiento_calculada'] ?? ($cxc['fecha_vencimiento'] ?? ''),
                $cxc['antiguedad_label'] ?? '',
                $cxc['notas'] ?? '',
            ];
        }

        $this->enviarCsv(
            'cuentas_por_cobrar_' . date('Ymd_His') . '.csv',
            ['Cliente', 'Factura', 'Cuenta contable', 'Monto original', 'Saldo pendiente', 'Estado', 'Fecha de emisión', 'Fecha de vencimiento', 'Antigüedad', 'Notas'],
            $filas
        );
    }

    public function exportarCxpCsv(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $busqueda = trim((string)($_GET['q'] ?? ''));
        $filas = [];
        foreach ((new ContabilidadModelo())->obtenerCxp($busqueda) as $cxp) {
            $filas[] = [
                $cxp['proveedor_nombre'] ?? '',
                $cxp['factura_numero'] ?? '',
                trim(($cxp['cuenta_codigo'] ?? '') . ' - ' . ($cxp['cuenta_nombre'] ?? ''), ' -'),
                number_format((float)($cxp['monto'] ?? 0), 2, '.', ''),
                number_format((float)($cxp['saldo'] ?? 0), 2, '.', ''),
                $cxp['estado'] ?? '',
                $cxp['fecha_emision'] ?? '',
                $cxp['fecha_vencimiento'] ?? '',
                $cxp['notas'] ?? '',
            ];
        }

        $this->enviarCsv(
            'cuentas_por_pagar_' . date('Ymd_His') . '.csv',
            ['Proveedor', 'Factura', 'Cuenta contable', 'Monto original', 'Saldo pendiente', 'Estado', 'Fecha de emisión', 'Fecha de vencimiento', 'Notas'],
            $filas
        );
    }

    public function exportarDiarioCsv(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $busqueda = trim((string)($_GET['q'] ?? ''));
        $modelo = new ContabilidadModelo();
        $filas = [];

        foreach ($modelo->obtenerAsientos($busqueda) as $asiento) {
            $referencia = $modelo->obtenerReferenciaOrigen($asiento['origen'], $asiento['origen_id']);
            $banco = $modelo->obtenerBancoAfectado((int)$asiento['id']);
            $detalles = $modelo->obtenerAsientoDetalles((int)$asiento['id']);

            if ($detalles === []) {
                $detalles = [[]];
            }

            foreach ($detalles as $detalle) {
                $filas[] = [
                    $asiento['num_partida'] ?? '',
                    $asiento['fecha'] ?? '',
                    $asiento['concepto'] ?? '',
                    $asiento['origen'] ?? '',
                    $referencia['tercero'] ?? '',
                    $referencia['documento'] ?? '',
                    $banco ? trim(($banco['banco_nombre'] ?? '') . ' - ' . ($banco['numero_cuenta'] ?? ''), ' -') : '',
                    $detalle['cuenta_codigo'] ?? '',
                    $detalle['cuenta_nombre'] ?? '',
                    $detalle['categoria'] ?? '',
                    number_format((float)($detalle['debe'] ?? 0), 2, '.', ''),
                    number_format((float)($detalle['haber'] ?? 0), 2, '.', ''),
                ];
            }
        }

        $this->enviarCsv(
            'libro_diario_' . date('Ymd_His') . '.csv',
            ['Partida', 'Fecha', 'Concepto', 'Origen', 'Tercero', 'Documento', 'Banco/Caja', 'Código de cuenta', 'Nombre de cuenta', 'Categoría', 'Debe', 'Haber'],
            $filas
        );
    }

    private function enviarCsv(string $nombreArchivo, array $encabezados, array $filas): void {
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="' . $nombreArchivo . '"');
        header('Cache-Control: no-store, no-cache, must-revalidate');
        header('X-Content-Type-Options: nosniff');

        echo ExportadorCsv::generar($encabezados, $filas);
        exit;
    }

    // ==========================================
    // 6. Balance General
    // ==========================================

    public function balance(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $modelo = new ContabilidadModelo();
        $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

        $saldos = $modelo->obtenerSaldosCuentas($fechaHasta);

        $this->renderizar('contabilidad/vistas/balance', [
            'subnavActiva' => 'balance',
            'titulo' => 'Balance General - Cycsa',
            'saldos' => $saldos,
            'fechaHasta' => $fechaHasta,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    // ==========================================
    // 7. Estado de Resultados
    // ==========================================

    public function resultados(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $modelo = new ContabilidadModelo();
        $fechaDesde = $_GET['fecha_desde'] ?? date('Y-m-01');
        $fechaHasta = $_GET['fecha_hasta'] ?? date('Y-m-d');

        $saldos = $modelo->obtenerSaldosIngresosEgresos($fechaDesde, $fechaHasta);

        $this->renderizar('contabilidad/vistas/resultados', [
            'subnavActiva' => 'resultados',
            'titulo' => 'Estado de Resultados - Cycsa',
            'saldos' => $saldos,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }
}
