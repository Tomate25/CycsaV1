<?php

namespace Cycsa\Modulos\ActivosFijos\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\ActivosFijos\Modelos\ActivosFijosModelo;
use Cycsa\Modulos\Contabilidad\Modelos\ContabilidadModelo;
use Exception;
use Throwable;

/**
 * Controlador de Gestión de Activos Fijos y Depreciación Mensual Automatizada (TAREA-051).
 */
class ActivosFijosControlador extends ControladorBase {

    private function verificarSesion(Respuesta $respuesta): bool {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/login');
            if (!defined('PHPUNIT_RUNNING')) {
                exit;
            }
            return false;
        }
        return true;
    }

    private function verificarPermiso(Respuesta $respuesta, string $accion = 'ver'): bool {
        $rol = (int)($_SESSION['usuario_rol'] ?? 0);
        if ($rol === 1) {
            return true; // Administrador siempre tiene acceso total
        }

        if (!tienePermiso('activos_fijos', $accion) && !tienePermiso('contabilidad', $accion)) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson([
                    'ok' => false,
                    'error' => 'No tiene permisos suficientes para gestionar Activos Fijos.'
                ], 403);
                return false;
            }
            $_SESSION['flash_error'] = 'No tiene permisos suficientes para acceder a este módulo.';
            $respuesta->redirigir('/panel');
            if (!defined('PHPUNIT_RUNNING')) {
                exit;
            }
            return false;
        }

        return true;
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

    // =========================================================================
    // 1. LISTADO Y DETALLE DE ACTIVOS
    // =========================================================================

    public function index(Peticion $peticion, Respuesta $respuesta): void {
        if (!$this->verificarSesion($respuesta) || !$this->verificarPermiso($respuesta, 'ver')) {
            return;
        }

        $csrfToken = $this->asegurarCsrf();
        $busqueda = trim($_GET['q'] ?? '');
        $categoria = trim($_GET['categoria'] ?? '');
        $estado = trim($_GET['estado'] ?? '');

        $modelo = new ActivosFijosModelo();
        $activos = $modelo->listarActivos($busqueda, $categoria, $estado);

        $this->renderizar('ActivosFijos/Vistas/index', [
            'titulo' => 'Gestión de Activos Fijos y Depreciación - CYCSA ERP',
            'activos' => $activos,
            'busqueda' => $busqueda,
            'categoriaFiltro' => $categoria,
            'estadoFiltro' => $estado,
            'csrfToken' => $csrfToken
        ]);
    }

    public function detalle(Peticion $peticion, Respuesta $respuesta): void {
        if (!$this->verificarSesion($respuesta) || !$this->verificarPermiso($respuesta, 'ver')) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $modelo = new ActivosFijosModelo();
        $activo = $modelo->obtenerActivoPorId($id);

        if (!$activo) {
            $_SESSION['flash_error'] = "El activo fijo solicitado no existe.";
            $respuesta->redirigir('/activos-fijos');
            return;
        }

        $depreciaciones = $modelo->obtenerDepreciacionesPorActivo($id);

        $this->renderizar('ActivosFijos/Vistas/detalle', [
            'titulo' => 'Ficha Técnica de Activo: ' . $activo['codigo'] . ' - CYCSA ERP',
            'activo' => $activo,
            'depreciaciones' => $depreciaciones,
            'csrfToken' => $this->asegurarCsrf()
        ]);
    }

    // =========================================================================
    // 2. CREACIÓN Y EDICIÓN DE ACTIVOS
    // =========================================================================

    public function crear(Peticion $peticion, Respuesta $respuesta): void {
        if (!$this->verificarSesion($respuesta) || !$this->verificarPermiso($respuesta, 'crear')) {
            return;
        }

        $csrfToken = $this->asegurarCsrf();
        $modelo = new ActivosFijosModelo();
        $contabilidad = new ContabilidadModelo();
        $cuentasDetalle = $contabilidad->obtenerCuentasDetalle();

        // Sugerencia de código consecutivo
        $totalActivos = count($modelo->listarActivos());
        $sugerenciaCodigo = 'AF-' . str_pad((string)($totalActivos + 1), 4, '0', STR_PAD_LEFT);

        $this->renderizar('ActivosFijos/Vistas/crear', [
            'titulo' => 'Registrar Nuevo Activo Fijo - CYCSA ERP',
            'esEdicion' => false,
            'activo' => null,
            'cuentasDetalle' => $cuentasDetalle,
            'sugerenciaCodigo' => $sugerenciaCodigo,
            'csrfToken' => $csrfToken
        ]);
    }

    public function guardar(Peticion $peticion, Respuesta $respuesta): void {
        if (!$this->verificarSesion($respuesta) || !$this->verificarPermiso($respuesta, 'crear')) {
            return;
        }

        $datos = $_POST;
        if (!$this->validarCsrfPeticion($datos)) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
                return;
            }
            $_SESSION['flash_error'] = 'Token CSRF inválido. Intente nuevamente.';
            $respuesta->redirigir('/activos-fijos/crear');
            return;
        }

        try {
            $modelo = new ActivosFijosModelo();
            $id = $modelo->crearActivo($datos);

            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson([
                    'ok' => true,
                    'mensaje' => 'Activo fijo registrado exitosamente.',
                    'id' => $id
                ]);
                return;
            }

            $_SESSION['flash_exito'] = "Activo fijo '{$datos['codigo']}' registrado exitosamente.";
            $respuesta->redirigir('/activos-fijos');
        } catch (Throwable $e) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['flash_error'] = $e->getMessage();
            $respuesta->redirigir('/activos-fijos/crear');
        }
    }

    public function editar(Peticion $peticion, Respuesta $respuesta): void {
        if (!$this->verificarSesion($respuesta) || !$this->verificarPermiso($respuesta, 'editar')) {
            return;
        }

        $id = (int)($_GET['id'] ?? 0);
        $modelo = new ActivosFijosModelo();
        $activo = $modelo->obtenerActivoPorId($id);

        if (!$activo) {
            $_SESSION['flash_error'] = "El activo fijo solicitado no existe.";
            $respuesta->redirigir('/activos-fijos');
            return;
        }

        $contabilidad = new ContabilidadModelo();
        $cuentasDetalle = $contabilidad->obtenerCuentasDetalle();

        $this->renderizar('ActivosFijos/Vistas/crear', [
            'titulo' => 'Editar Activo Fijo: ' . $activo['codigo'] . ' - CYCSA ERP',
            'esEdicion' => true,
            'activo' => $activo,
            'cuentasDetalle' => $cuentasDetalle,
            'sugerenciaCodigo' => $activo['codigo'],
            'csrfToken' => $this->asegurarCsrf()
        ]);
    }

    public function actualizar(Peticion $peticion, Respuesta $respuesta): void {
        if (!$this->verificarSesion($respuesta) || !$this->verificarPermiso($respuesta, 'editar')) {
            return;
        }

        $datos = $_POST;
        if (!$this->validarCsrfPeticion($datos)) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
                return;
            }
            $_SESSION['flash_error'] = 'Token CSRF inválido.';
            $respuesta->redirigir('/activos-fijos');
            return;
        }

        $id = (int)($datos['id'] ?? 0);
        try {
            $modelo = new ActivosFijosModelo();
            $modelo->actualizarActivo($id, $datos);

            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson([
                    'ok' => true,
                    'mensaje' => 'Activo fijo actualizado exitosamente.'
                ]);
                return;
            }

            $_SESSION['flash_exito'] = "Activo fijo actualizado exitosamente.";
            $respuesta->redirigir('/activos-fijos');
        } catch (Throwable $e) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['flash_error'] = $e->getMessage();
            $respuesta->redirigir('/activos-fijos/editar?id=' . $id);
        }
    }

    public function eliminar(Peticion $peticion, Respuesta $respuesta): void {
        if (!$this->verificarSesion($respuesta) || !$this->verificarPermiso($respuesta, 'eliminar')) {
            return;
        }

        $datos = $_POST;
        if (!$this->validarCsrfPeticion($datos)) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
                return;
            }
            $_SESSION['flash_error'] = 'Token CSRF inválido.';
            $respuesta->redirigir('/activos-fijos');
            return;
        }

        $id = (int)($datos['id'] ?? 0);
        try {
            $modelo = new ActivosFijosModelo();
            $modelo->eliminarActivo($id);

            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => true, 'mensaje' => 'Activo eliminado o dado de baja exitosamente.']);
                return;
            }

            $_SESSION['flash_exito'] = "Activo eliminado o dado de baja exitosamente.";
            $respuesta->redirigir('/activos-fijos');
        } catch (Throwable $e) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['flash_error'] = $e->getMessage();
            $respuesta->redirigir('/activos-fijos');
        }
    }

    // =========================================================================
    // 3. DEPRECIACIÓN MENSUAL AUTOMATIZADA
    // =========================================================================

    public function depreciacion(Peticion $peticion, Respuesta $respuesta): void {
        if (!$this->verificarSesion($respuesta) || !$this->verificarPermiso($respuesta, 'ver')) {
            return;
        }

        $csrfToken = $this->asegurarCsrf();
        $busqueda = trim($_GET['q'] ?? '');
        $periodo = trim($_GET['periodo'] ?? '');
        $periodoDefecto = date('Y-m');

        $modelo = new ActivosFijosModelo();
        $historico = $modelo->obtenerHistoricoDepreciaciones($busqueda, $periodo);

        $this->renderizar('ActivosFijos/Vistas/depreciacion', [
            'titulo' => 'Depreciación Mensual de Activos Fijos - CYCSA ERP',
            'historico' => $historico,
            'busqueda' => $busqueda,
            'periodoFiltro' => $periodo,
            'periodoDefecto' => $periodoDefecto,
            'csrfToken' => $csrfToken
        ]);
    }

    public function simularDepreciacionAjax(Peticion $peticion, Respuesta $respuesta): void {
        if (!$this->verificarSesion($respuesta) || !$this->verificarPermiso($respuesta, 'ver')) {
            return;
        }

        $periodo = trim($_POST['periodo'] ?? ($_GET['periodo'] ?? date('Y-m')));

        try {
            $modelo = new ActivosFijosModelo();
            $simulacion = $modelo->simularDepreciacion($periodo);

            $respuesta->enviarJson([
                'ok' => true,
                'resultado' => $simulacion
            ]);
        } catch (Throwable $e) {
            $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
        }
    }

    public function ejecutarDepreciacion(Peticion $peticion, Respuesta $respuesta): void {
        if (!$this->verificarSesion($respuesta) || !$this->verificarPermiso($respuesta, 'crear')) {
            return;
        }

        $datos = $_POST;
        if (!$this->validarCsrfPeticion($datos)) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => 'Token CSRF inválido.'], 403);
                return;
            }
            $_SESSION['flash_error'] = 'Token CSRF inválido.';
            $respuesta->redirigir('/activos-fijos/depreciacion');
            return;
        }

        $periodo = trim($datos['periodo'] ?? date('Y-m'));
        $usuarioId = (int)($_SESSION['usuario_id'] ?? 1);

        try {
            $modelo = new ActivosFijosModelo();
            $resultado = $modelo->ejecutarDepreciacionMensual($periodo, $usuarioId);

            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson([
                    'ok' => true,
                    'mensaje' => $resultado['mensaje'],
                    'resultado' => $resultado
                ]);
                return;
            }

            $_SESSION['flash_exito'] = $resultado['mensaje'];
            $respuesta->redirigir('/activos-fijos/depreciacion?periodo=' . urlencode($periodo));
        } catch (Throwable $e) {
            if ($this->esPeticionAjax()) {
                $respuesta->enviarJson(['ok' => false, 'error' => $e->getMessage()], 400);
                return;
            }
            $_SESSION['flash_error'] = $e->getMessage();
            $respuesta->redirigir('/activos-fijos/depreciacion');
        }
    }
}
