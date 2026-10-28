<?php

namespace Cycsa\Modulos\Inventario\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Inventario\Modelos\InventarioModelo;
use Exception;

/**
 * Controlador de Inventario, Lotes, Kárdex y Consumos por Ensayo (TAREA-048).
 * Incorpora verificación de sesión, RBAC y protección CSRF.
 */
class InventarioControlador extends ControladorBase {

    private function verificarSesion(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/login');
            if (!defined('PHPUNIT_RUNNING')) {
                exit;
            }
        }
    }

    private function verificarPermiso(Respuesta $respuesta, string $accion = 'ver'): void {
        if (!tienePermiso('inventario', $accion) && !tienePermiso('operaciones', $accion) && !tienePermiso('laboratorio', $accion)) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson([
                    'ok' => false,
                    'error' => 'No tiene permisos suficientes para ejecutar esta acción en Inventario.'
                ], 403);
                return;
            }
            $respuesta->redirigir('/panel');
            if (!defined('PHPUNIT_RUNNING')) {
                exit;
            }
        }
    }

    private function asegurarCsrf(): string {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        return $_SESSION['csrf_token'];
    }

    private function validarCsrfPeticion(array $datos): bool {
        $token = $datos['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? null);
        if (empty($token) || empty($_SESSION['csrf_token'])) {
            return false;
        }
        return hash_equals($_SESSION['csrf_token'], $token);
    }

    private function esPeticionAjax(): bool {
        return (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
            || (strpos($_SERVER['HTTP_ACCEPT'] ?? '', 'application/json') !== false)
            || (!empty($_POST['es_ajax']))
            || (!empty($_GET['es_ajax']));
    }

    /**
     * Vista principal: Resumen de stock, insumos y badges de alerta.
     */
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $csrfToken = $this->asegurarCsrf();
        $filtro = trim($_GET['q'] ?? '');

        $modelo = new InventarioModelo();
        $insumos = $modelo->listarInsumos($filtro);
        $alertas = $modelo->obtenerAlertas();

        $this->renderizar('Inventario/Vistas/index', [
            'subnavActiva' => 'stock',
            'titulo' => 'Gestión de Inventario y Kárdex - CYCSA',
            'insumos' => $insumos,
            'alertas' => $alertas,
            'filtro' => $filtro,
            'csrf_token' => $csrfToken,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Control y caducidades de lotes.
     */
    public function lotes(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $csrfToken = $this->asegurarCsrf();
        $insumoId = (int)($_GET['insumo_id'] ?? 0);

        $modelo = new InventarioModelo();
        $insumos = $modelo->listarInsumos();
        $lotes = $modelo->listarLotes($insumoId);
        $alertas = $modelo->obtenerAlertas();
        $insumoSeleccionado = $insumoId > 0 ? $modelo->obtenerInsumo($insumoId) : null;

        $this->renderizar('Inventario/Vistas/lotes', [
            'subnavActiva' => 'lotes',
            'titulo' => 'Control de Lotes y Vencimientos - CYCSA',
            'lotes' => $lotes,
            'insumos' => $insumos,
            'insumoId' => $insumoId,
            'insumoSeleccionado' => $insumoSeleccionado,
            'alertas' => $alertas,
            'csrf_token' => $csrfToken,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Trazabilidad de movimientos (Kárdex).
     */
    public function kardex(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $csrfToken = $this->asegurarCsrf();
        $insumoId = (int)($_GET['insumo_id'] ?? 0);
        $fechaDesde = !empty($_GET['fecha_desde']) ? trim($_GET['fecha_desde']) : null;
        $fechaHasta = !empty($_GET['fecha_hasta']) ? trim($_GET['fecha_hasta']) : null;

        $modelo = new InventarioModelo();
        $insumos = $modelo->listarInsumos();
        $movimientos = $modelo->listarKardex($insumoId, $fechaDesde, $fechaHasta);
        $insumoSeleccionado = $insumoId > 0 ? $modelo->obtenerInsumo($insumoId) : null;

        $this->renderizar('Inventario/Vistas/kardex', [
            'subnavActiva' => 'kardex',
            'titulo' => 'Kárdex de Movimientos de Inventario - CYCSA',
            'movimientos' => $movimientos,
            'insumos' => $insumos,
            'insumoId' => $insumoId,
            'fechaDesde' => $fechaDesde,
            'fechaHasta' => $fechaHasta,
            'insumoSeleccionado' => $insumoSeleccionado,
            'csrf_token' => $csrfToken,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Registra un ajuste de inventario positivo o negativo.
     */
    public function registrarAjuste(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if (!$peticion->esPost()) {
            $respuesta->redirigir('/inventario');
            return;
        }

        $datos = $peticion->obtenerDatos();
        if (empty($datos)) {
            $input = json_decode(file_get_contents('php://input'), true);
            if (is_array($input)) {
                $datos = $input;
            }
        }

        if (!$this->validarCsrfPeticion($datos)) {
            if ($this->esPeticionAjax() || $peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido o expirado.'], 403);
                return;
            }
            $_SESSION['error'] = 'Token CSRF inválido o expirado.';
            $respuesta->redirigir('/inventario');
            return;
        }

        $insumoId = (int)($datos['insumo_id'] ?? 0);
        $loteId = !empty($datos['lote_id']) ? (int)$datos['lote_id'] : null;
        $tipoAjuste = trim($datos['tipo_ajuste'] ?? 'ajuste_positivo');
        $cantidad = (float)($datos['cantidad'] ?? 0);
        $costoUnitario = (float)($datos['costo_unitario'] ?? 0);
        $usuarioId = (int)($_SESSION['usuario_id'] ?? 1);
        $motivo = trim($datos['motivo'] ?? 'Ajuste manual de inventario');

        if ($insumoId <= 0 || $cantidad <= 0) {
            $mensaje = 'Debe seleccionar un insumo válido y especificar una cantidad mayor a 0.';
            if ($this->esPeticionAjax() || $peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $mensaje], 422);
                return;
            }
            $_SESSION['error'] = $mensaje;
            $respuesta->redirigir('/inventario');
            return;
        }

        try {
            $modelo = new InventarioModelo();
            $resultado = $modelo->registrarAjuste(
                $insumoId,
                $loteId,
                $tipoAjuste,
                $cantidad,
                $costoUnitario,
                $usuarioId,
                $motivo
            );

            if ($this->esPeticionAjax() || $peticion->esAjax()) {
                $respuesta->enviarJson([
                    'ok' => true,
                    'mensaje' => 'Ajuste de inventario procesado exitosamente.',
                    'resultado' => $resultado
                ]);
                return;
            }

            $_SESSION['exito'] = 'Ajuste de inventario registrado correctamente en el Kárdex.';
            $respuesta->redirigir('/inventario/kardex?insumo_id=' . $insumoId);

        } catch (Exception $e) {
            if ($this->esPeticionAjax() || $peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['error'] = 'Error al registrar ajuste: ' . $e->getMessage();
            $respuesta->redirigir('/inventario');
        }
    }

    /**
     * Consume insumos por ejecución de ensayo usando PEPS/FIFO.
     */
    public function registrarConsumoEnsayo(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if (!$peticion->esPost()) {
            $respuesta->redirigir('/inventario');
            return;
        }

        $datos = $peticion->obtenerDatos();
        if (empty($datos)) {
            $input = json_decode(file_get_contents('php://input'), true);
            if (is_array($input)) {
                $datos = $input;
            }
        }

        if (isset($datos['csrf_token']) && !$this->validarCsrfPeticion($datos)) {
            if ($this->esPeticionAjax() || $peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
                return;
            }
            $_SESSION['error'] = 'Token CSRF inválido.';
            $respuesta->redirigir('/inventario');
            return;
        }

        $insumoId = (int)($datos['insumo_id'] ?? 0);
        $cantidad = (float)($datos['cantidad'] ?? 0);
        $ensayoId = (int)($datos['ensayo_id'] ?? 0);
        $tipoEnsayo = trim($datos['tipo_ensayo'] ?? 'Ensayo');
        $usuarioId = (int)($_SESSION['usuario_id'] ?? 1);
        $observaciones = !empty($datos['observaciones']) ? trim($datos['observaciones']) : null;

        if ($insumoId <= 0 || $cantidad <= 0 || $ensayoId <= 0) {
            $msg = 'Parámetros obligatorios faltantes: insumo_id, cantidad y ensayo_id.';
            if ($this->esPeticionAjax() || $peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $msg], 422);
                return;
            }
            $_SESSION['error'] = $msg;
            $respuesta->redirigir('/inventario');
            return;
        }

        try {
            $modelo = new InventarioModelo();
            $resultado = $modelo->registrarConsumoEnsayo(
                $insumoId,
                $cantidad,
                $ensayoId,
                $tipoEnsayo,
                $usuarioId,
                $observaciones
            );

            if ($this->esPeticionAjax() || $peticion->esAjax()) {
                $respuesta->enviarJson([
                    'ok' => true,
                    'mensaje' => 'Consumo de ensayo registrado con valoración PEPS.',
                    'resultado' => $resultado
                ]);
                return;
            }

            $_SESSION['exito'] = 'Consumo de ensayo registrado correctamente.';
            $respuesta->redirigir('/inventario/kardex?insumo_id=' . $insumoId);

        } catch (Exception $e) {
            if ($this->esPeticionAjax() || $peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['error'] = 'Error al registrar consumo: ' . $e->getMessage();
            $respuesta->redirigir('/inventario');
        }
    }

    /**
     * Endpoint JSON para consultar alertas activas de inventario.
     */
    public function alertasJson(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $modelo = new InventarioModelo();
        $alertas = $modelo->obtenerAlertas();

        $respuesta->enviarJson([
            'ok' => true,
            'alertas' => $alertas,
            'timestamp' => date('c')
        ]);
    }
}
