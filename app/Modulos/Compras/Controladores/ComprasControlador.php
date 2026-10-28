<?php

namespace Cycsa\Modulos\Compras\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Compras\Modelos\ComprasModelo;

class ComprasControlador extends ControladorBase {

    protected ComprasModelo $modelo;

    public function __construct(?ComprasModelo $modelo = null) {
        $this->modelo = $modelo ?? new ComprasModelo();
    }

    private function verificarSesion(Respuesta $respuesta): void {
        if (session_status() === PHP_SESSION_NONE && !headers_sent()) {
            @session_start();
        }
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            if (!defined('PHPUNIT_RUNNING')) {
                exit;
            }
        }
    }

    private function verificarPermiso(string $accion, Respuesta $respuesta): bool {
        if (function_exists('tienePermiso')) {
            if (!tienePermiso('compras', $accion)) {
                if (!isset($_SESSION['usuario_rol']) || $_SESSION['usuario_rol'] != 1) {
                    $_SESSION['error'] = 'No cuenta con permisos para acceder a esta función.';
                    $respuesta->redirigir('/Cycsa/publico/panel');
                    if (!defined('PHPUNIT_RUNNING')) {
                        exit;
                    }
                    return false;
                }
            }
        }
        return true;
    }

    private function verificarCsrf(array $datos): bool {
        $token = $datos['csrf_token'] ?? $_POST['csrf_token'] ?? '';
        $sesionToken = $_SESSION['csrf_token'] ?? '';
        return !empty($token) && !empty($sesionToken) && hash_equals($sesionToken, (string)$token);
    }

    private function asegurarTokenCsrf(): void {
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
    }

    // =========================================================================
    // 1. PROVEEDORES (ISO/IEC 17025 §6.6)
    // =========================================================================

    public function proveedoresIndex(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!$this->verificarPermiso('ver', $respuesta)) return;
        $this->asegurarTokenCsrf();

        $busqueda = trim($_GET['q'] ?? '');
        $estado = trim($_GET['estado'] ?? '');
        $categoria = trim($_GET['categoria'] ?? '');
        $evaluado = isset($_GET['evaluado']) && $_GET['evaluado'] !== '' ? $_GET['evaluado'] : '';

        $filtro = [];
        if ($busqueda !== '') $filtro['busqueda'] = $busqueda;
        if ($estado !== '') $filtro['estado'] = $estado;
        if ($categoria !== '') $filtro['categoria'] = $categoria;
        if ($evaluado !== '') $filtro['evaluado'] = $evaluado;

        $proveedores = $this->modelo->listarProveedores($filtro);
        $cuentasContables = $this->modelo->listarCuentasDetalle();

        $this->renderizar('compras/vistas/proveedores_index', [
            'subnavActiva' => 'proveedores',
            'titulo' => 'Maestro de Proveedores - CYCSA LIMS',
            'proveedores' => $proveedores,
            'cuentasContables' => $cuentasContables,
            'busqueda' => $busqueda,
            'estado' => $estado,
            'categoria' => $categoria,
            'evaluado' => $evaluado,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    public function proveedoresGuardar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!$this->verificarPermiso('crear_editar', $respuesta)) return;

        $datos = $peticion->obtenerDatos();

        if (!$this->verificarCsrf($datos)) {
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
                return;
            }
            $_SESSION['error'] = 'Token CSRF inválido.';
            $respuesta->redirigir('/Cycsa/publico/compras/proveedores');
            return;
        }

        if (empty(trim($datos['identificacion'] ?? '')) || empty(trim($datos['razon_social'] ?? ''))) {
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Identificación y Razón Social son requeridos.'], 422);
                return;
            }
            $_SESSION['error'] = 'Identificación y Razón Social son campos requeridos.';
            $respuesta->redirigir('/Cycsa/publico/compras/proveedores');
            return;
        }

        try {
            $id = $this->modelo->guardarProveedor($datos);
            if (function_exists('registrarBitacora')) {
                registrarBitacora('compras', 'guardar_proveedor', 'Proveedor guardado: ' . trim($datos['razon_social']), $id);
            }

            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => true, 'id' => $id, 'mensaje' => 'Proveedor guardado correctamente.']);
                return;
            }

            $_SESSION['exito'] = 'Proveedor guardado exitosamente.';
            $respuesta->redirigir('/Cycsa/publico/compras/proveedores');
        } catch (\Throwable $e) {
            error_log("Error al guardar proveedor: " . $e->getMessage());
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Error al guardar proveedor: ' . $e->getMessage()], 500);
                return;
            }
            $_SESSION['error'] = 'Error al registrar proveedor: ' . $e->getMessage();
            $respuesta->redirigir('/Cycsa/publico/compras/proveedores');
        }
    }

    public function proveedoresEvaluar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!$this->verificarPermiso('evaluar', $respuesta)) return;

        $datos = $peticion->obtenerDatos();

        if (!$this->verificarCsrf($datos)) {
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
                return;
            }
            $_SESSION['error'] = 'Token CSRF inválido.';
            $respuesta->redirigir('/Cycsa/publico/compras/proveedores');
            return;
        }

        $id = (int)($datos['id'] ?? 0);
        $evaluado = !empty($datos['evaluado']) && ($datos['evaluado'] === '1' || $datos['evaluado'] === 1 || $datos['evaluado'] === true || $datos['evaluado'] === 'true');
        $fecha = !empty($datos['fecha_evaluacion']) ? trim($datos['fecha_evaluacion']) : date('Y-m-d');

        if ($id <= 0) {
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'ID de proveedor inválido.'], 422);
                return;
            }
            $_SESSION['error'] = 'ID de proveedor inválido.';
            $respuesta->redirigir('/Cycsa/publico/compras/proveedores');
            return;
        }

        $usuarioId = $_SESSION['usuario_id'] ?? 1;
        $this->modelo->evaluarProveedor($id, $evaluado, $fecha, $usuarioId);

        if ($peticion->esAjax()) {
            $respuesta->enviarJson([
                'ok' => true,
                'evaluado' => $evaluado,
                'fecha_evaluacion' => $evaluado ? $fecha : null,
                'mensaje' => 'Evaluación de proveedor actualizada (ISO 17025 §6.6).'
            ]);
            return;
        }

        $_SESSION['exito'] = 'Evaluación ISO 17025 §6.6 actualizada correctamente.';
        $respuesta->redirigir('/Cycsa/publico/compras/proveedores');
    }

    // =========================================================================
    // 2. ÓRDENES DE COMPRA
    // =========================================================================

    public function ordenesIndex(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!$this->verificarPermiso('ver', $respuesta)) return;
        $this->asegurarTokenCsrf();

        $busqueda = trim($_GET['q'] ?? '');
        $estado = trim($_GET['estado'] ?? '');
        $proveedorId = !empty($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : '';

        $filtro = [];
        if ($busqueda !== '') $filtro['busqueda'] = $busqueda;
        if ($estado !== '') $filtro['estado'] = $estado;
        if ($proveedorId !== '') $filtro['proveedor_id'] = $proveedorId;

        $ordenes = $this->modelo->listarOrdenesCompra($filtro);
        $proveedores = $this->modelo->listarProveedores(['estado' => 'activo']);

        $this->renderizar('compras/vistas/ordenes_index', [
            'subnavActiva' => 'ordenes',
            'titulo' => 'Órdenes de Compra - CYCSA LIMS',
            'ordenes' => $ordenes,
            'proveedores' => $proveedores,
            'busqueda' => $busqueda,
            'estado' => $estado,
            'proveedorId' => $proveedorId,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    public function ordenesCrear(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!$this->verificarPermiso('crear_editar', $respuesta)) return;
        $this->asegurarTokenCsrf();

        $proveedores = $this->modelo->listarProveedores(['estado' => 'activo']);
        $insumos = $this->modelo->listarInsumosActivos();
        $cuentas = $this->modelo->listarCuentasDetalle();

        $this->renderizar('compras/vistas/ordenes_form', [
            'subnavActiva' => 'ordenes',
            'titulo' => 'Nueva Orden de Compra - CYCSA LIMS',
            'proveedores' => $proveedores,
            'insumos' => $insumos,
            'cuentas' => $cuentas,
            'orden' => null,
            'detalles' => [],
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    public function ordenesGuardar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!$this->verificarPermiso('crear_editar', $respuesta)) return;

        $datos = $peticion->obtenerDatos();

        if (!$this->verificarCsrf($datos)) {
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
                return;
            }
            $_SESSION['error'] = 'Token CSRF inválido.';
            $respuesta->redirigir('/Cycsa/publico/compras/ordenes');
            return;
        }

        // Extracción de detalles estructurados (form tradicional o JSON)
        $detalles = [];
        if (!empty($datos['detalles'])) {
            $detalles = is_array($datos['detalles']) ? $datos['detalles'] : json_decode($datos['detalles'], true);
        } elseif (!empty($_POST['item_descripcion']) && is_array($_POST['item_descripcion'])) {
            foreach ($_POST['item_descripcion'] as $i => $desc) {
                if (trim($desc) === '' && empty($_POST['item_insumo_id'][$i])) continue;
                $detalles[] = [
                    'insumo_id' => !empty($_POST['item_insumo_id'][$i]) ? (int)$_POST['item_insumo_id'][$i] : null,
                    'descripcion' => trim($desc),
                    'cantidad' => (float)($_POST['item_cantidad'][$i] ?? 1),
                    'costo_unitario' => (float)($_POST['item_costo'][$i] ?? 0),
                    'aplica_iva' => !empty($_POST['item_aplica_iva'][$i]),
                    'iva' => isset($_POST['item_iva'][$i]) ? (float)$_POST['item_iva'][$i] : null,
                    'cuenta_contable_id' => !empty($_POST['item_cuenta_id'][$i]) ? (int)$_POST['item_cuenta_id'][$i] : null,
                ];
            }
        }

        if (empty($detalles)) {
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Debe ingresar al menos una línea de detalle en la orden.'], 422);
                return;
            }
            $_SESSION['error'] = 'Debe ingresar al menos una línea de detalle en la orden de compra.';
            $respuesta->redirigir('/Cycsa/publico/compras/ordenes/crear');
            return;
        }

        try {
            $ocId = $this->modelo->guardarOrdenCompra($datos, $detalles);

            if (function_exists('registrarBitacora')) {
                registrarBitacora('compras', 'crear_orden', "Orden de compra #{$ocId} registrada.", $ocId);
            }

            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => true, 'id' => $ocId, 'mensaje' => 'Orden de compra guardada con éxito.']);
                return;
            }

            $_SESSION['exito'] = "Orden de compra #{$ocId} guardada con éxito.";
            $respuesta->redirigir("/Cycsa/publico/compras/ordenes/detalle?id={$ocId}");
        } catch (\Throwable $e) {
            error_log("Error al guardar orden de compra: " . $e->getMessage());
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Error al guardar orden: ' . $e->getMessage()], 500);
                return;
            }
            $_SESSION['error'] = 'Error al registrar orden de compra: ' . $e->getMessage();
            $respuesta->redirigir('/Cycsa/publico/compras/ordenes/crear');
        }
    }

    public function ordenesDetalle(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!$this->verificarPermiso('ver', $respuesta)) return;
        $this->asegurarTokenCsrf();

        $id = (int)($_GET['id'] ?? 0);
        $orden = $this->modelo->obtenerOrdenCompraPorId($id);

        if (!$orden) {
            $_SESSION['error'] = 'Orden de compra no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/compras/ordenes');
            return;
        }

        $this->renderizar('compras/vistas/ordenes_detalle', [
            'subnavActiva' => 'ordenes',
            'titulo' => "Orden de Compra #{$id} - CYCSA LIMS",
            'orden' => $orden,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    public function ordenesAprobar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!$this->verificarPermiso('aprobar', $respuesta)) return;

        $datos = $peticion->obtenerDatos();

        if (!$this->verificarCsrf($datos)) {
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
                return;
            }
            $_SESSION['error'] = 'Token CSRF inválido.';
            $respuesta->redirigir('/Cycsa/publico/compras/ordenes');
            return;
        }

        $id = (int)($datos['id'] ?? 0);
        if ($id <= 0) {
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'ID de orden inválido.'], 422);
                return;
            }
            $_SESSION['error'] = 'ID de orden inválido.';
            $respuesta->redirigir('/Cycsa/publico/compras/ordenes');
            return;
        }

        $usuarioId = $_SESSION['usuario_id'] ?? 1;
        $this->modelo->cambiarEstadoOrdenCompra($id, 'APROBADA', $usuarioId);

        if (function_exists('registrarBitacora')) {
            registrarBitacora('compras', 'aprobar_orden', "Orden de compra #{$id} aprobada.", $id);
        }

        if ($peticion->esAjax()) {
            $respuesta->enviarJson(['ok' => true, 'id' => $id, 'estado' => 'APROBADA', 'mensaje' => 'Orden aprobada con éxito.']);
            return;
        }

        $_SESSION['exito'] = "Orden de compra #{$id} aprobada con éxito.";
        $respuesta->redirigir("/Cycsa/publico/compras/ordenes/detalle?id={$id}");
    }

    // =========================================================================
    // 3. RECEPCIONES DE COMPRA
    // =========================================================================

    public function recepcionesIndex(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!$this->verificarPermiso('ver', $respuesta)) return;
        $this->asegurarTokenCsrf();

        $busqueda = trim($_GET['q'] ?? '');
        $proveedorId = !empty($_GET['proveedor_id']) ? (int)$_GET['proveedor_id'] : '';
        $estado = trim($_GET['estado'] ?? '');

        $filtro = [];
        if ($busqueda !== '') $filtro['busqueda'] = $busqueda;
        if ($proveedorId !== '') $filtro['proveedor_id'] = $proveedorId;
        if ($estado !== '') $filtro['estado'] = $estado;

        $recepciones = $this->modelo->listarRecepciones($filtro);
        $proveedores = $this->modelo->listarProveedores(['estado' => 'activo']);
        $ordenesAprobadas = $this->modelo->listarOrdenesCompra(['estado' => 'APROBADA']);
        $insumos = $this->modelo->listarInsumosActivos();

        $this->renderizar('compras/vistas/recepciones_index', [
            'subnavActiva' => 'recepciones',
            'titulo' => 'Recepciones de Compra - CYCSA LIMS',
            'recepciones' => $recepciones,
            'proveedores' => $proveedores,
            'ordenesAprobadas' => $ordenesAprobadas,
            'insumos' => $insumos,
            'busqueda' => $busqueda,
            'proveedorId' => $proveedorId,
            'estado' => $estado,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    public function recepcionesCrear(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!$this->verificarPermiso('crear_editar', $respuesta)) return;
        $this->asegurarTokenCsrf();

        $ordenCompraId = (int)($_GET['orden_compra_id'] ?? 0);
        $orden = $ordenCompraId > 0 ? $this->modelo->obtenerOrdenCompraPorId($ordenCompraId) : null;
        $proveedores = $this->modelo->listarProveedores(['estado' => 'activo']);
        $insumos = $this->modelo->listarInsumosActivos();

        $this->renderizar('compras/vistas/recepciones_index', [
            'subnavActiva' => 'recepciones',
            'titulo' => 'Registrar Recepción de Compra - CYCSA LIMS',
            'recepciones' => $this->modelo->listarRecepciones(),
            'proveedores' => $proveedores,
            'ordenesAprobadas' => $this->modelo->listarOrdenesCompra(['estado' => 'APROBADA']),
            'insumos' => $insumos,
            'ordenSeleccionada' => $orden,
            'abrirModalRecepcion' => true,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    public function recepcionesGuardar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!$this->verificarPermiso('crear_editar', $respuesta)) return;

        $datos = $peticion->obtenerDatos();

        if (!$this->verificarCsrf($datos)) {
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
                return;
            }
            $_SESSION['error'] = 'Token CSRF inválido.';
            $respuesta->redirigir('/Cycsa/publico/compras/recepciones');
            return;
        }

        $ordenCompraId = (int)($datos['orden_compra_id'] ?? 0);
        $detalles = [];

        if (!empty($datos['detalles'])) {
            $detalles = is_array($datos['detalles']) ? $datos['detalles'] : json_decode($datos['detalles'], true);
        } elseif (!empty($_POST['rec_insumo_id']) && is_array($_POST['rec_insumo_id'])) {
            foreach ($_POST['rec_insumo_id'] as $i => $insId) {
                $cant = (float)($_POST['rec_cantidad'][$i] ?? 0);
                if ($cant <= 0) continue;
                $detalles[] = [
                    'orden_compra_detalle_id' => !empty($_POST['rec_oc_detalle_id'][$i]) ? (int)$_POST['rec_oc_detalle_id'][$i] : null,
                    'insumo_id' => (int)$insId,
                    'cantidad_recibida' => $cant,
                    'costo_unitario' => (float)($_POST['rec_costo'][$i] ?? 0),
                    'lote' => !empty($_POST['rec_lote'][$i]) ? trim($_POST['rec_lote'][$i]) : null,
                    'fecha_vencimiento' => !empty($_POST['rec_vencimiento'][$i]) ? $_POST['rec_vencimiento'][$i] : null,
                ];
            }
        }

        if (empty($detalles)) {
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Debe registrar al menos un insumo con cantidad recibida mayor a cero.'], 422);
                return;
            }
            $_SESSION['error'] = 'Debe registrar al menos un insumo con cantidad recibida mayor a cero.';
            $respuesta->redirigir('/Cycsa/publico/compras/recepciones');
            return;
        }

        try {
            $recId = $this->modelo->registrarRecepcionCompra($ordenCompraId, $datos, $detalles);

            if (function_exists('registrarBitacora')) {
                registrarBitacora('compras', 'registrar_recepcion', "Recepción de compra #{$recId} registrada.", $recId);
            }

            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => true, 'id' => $recId, 'mensaje' => 'Recepción registrada con éxito e inventario actualizado.']);
                return;
            }

            $_SESSION['exito'] = "Recepción de compra #{$recId} registrada con éxito.";
            $respuesta->redirigir('/Cycsa/publico/compras/recepciones');
        } catch (\Throwable $e) {
            error_log("Error al registrar recepción de compra: " . $e->getMessage());
            if ($peticion->esAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Error al registrar recepción: ' . $e->getMessage()], 500);
                return;
            }
            $_SESSION['error'] = 'Error al registrar recepción: ' . $e->getMessage();
            $respuesta->redirigir('/Cycsa/publico/compras/recepciones');
        }
    }
}
