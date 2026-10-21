<?php

namespace Cycsa\Modulos\Configuracion\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Nucleo\Conexion;
use Cycsa\Modulos\Configuracion\Modelos\EquipoCalibracionModelo;
use Cycsa\Modulos\Operaciones\Servicios\MotorHojasCalculoServicio;
use PDO;

/**
 * Controlador del Módulo de Equipos y Calibración ISO/IEC 17025 (Prensa PRENSA-01,
 * Factor L/D FACTOR-LD-C39, Esclerómetro ESCL-01, Moldes, Balanzas, Picnómetros RT-FM-56
 * y Parametrización de Correcciones de Hojas de Cálculo).
 *
 * Seguridad ISO/IEC 17025:
 * - Lectura restringida a personal con acceso a Configuración o Laboratorio.
 * - Modificación de constantes, picnómetros y correcciones restringida exclusivamente
 *   a Administrador (rol 1) o Gerente/Supervisor de Calidad (rol 3) con permiso crear_editar.
 * - Validación obligatoria de token CSRF en todos los formularios y peticiones POST.
 * - Sin fallback a usuario 1 cuando falta sesión válida.
 */
class EquiposCalibracionControlador extends ControladorBase {

    private function asegurarSesionIniciada(): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    private function obtenerUsuarioIdAutenticado(Respuesta $respuesta, bool $esAjax = false): int {
        $this->asegurarSesionIniciada();
        $uid = isset($_SESSION['usuario_id']) ? (int)$_SESSION['usuario_id'] : 0;
        if ($uid <= 0) {
            if ($esAjax) {
                $respuesta->enviarJson([
                    'status' => 'error',
                    'message' => 'Sesión no válida o expirada. Inicie sesión nuevamente.'
                ], 401);
                exit;
            }
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
        return $uid;
    }

    public function tienePermisoEdicionCalidad(): bool {
        $this->asegurarSesionIniciada();
        $rolId = (int)($_SESSION['usuario_rol'] ?? ($_SESSION['usuario_rol_id'] ?? ($_SESSION['rol_id'] ?? 0)));
        if (in_array($rolId, [1, 3], true)) {
            return true;
        }
        return function_exists('tienePermiso') && tienePermiso('configuracion', 'crear_editar');
    }

    private function verificarPermisoVista(Respuesta $respuesta): void {
        $this->obtenerUsuarioIdAutenticado($respuesta, false);
        $rolId = (int)($_SESSION['usuario_rol'] ?? ($_SESSION['usuario_rol_id'] ?? ($_SESSION['rol_id'] ?? 0)));
        $puedeVer = in_array($rolId, [1, 3], true)
            || (function_exists('tienePermiso') && (tienePermiso('configuracion', 'ver') || tienePermiso('laboratorio', 'ver')));

        if (!$puedeVer) {
            $_SESSION['error'] = 'Acceso denegado: No cuenta con permisos para consultar el módulo de Equipos y Calibración.';
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
    }

    private function verificarPermisoCalidad(Respuesta $respuesta, bool $esAjax = false): int {
        $usuarioId = $this->obtenerUsuarioIdAutenticado($respuesta, $esAjax);
        if (!$this->tienePermisoEdicionCalidad()) {
            if ($esAjax) {
                $respuesta->enviarJson([
                    'status' => 'error',
                    'message' => 'Acceso denegado (ISO/IEC 17025): Solo Calidad o Administración pueden modificar constantes de calibración o correcciones.'
                ], 403);
                exit;
            }
            $_SESSION['error'] = 'Acceso denegado (ISO/IEC 17025): Solo Calidad o Administración pueden modificar constantes de calibración o correcciones.';
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
        return $usuarioId;
    }

    private function verificarTokenCsrf(array $datos, Respuesta $respuesta, bool $esAjax = false): void {
        $this->asegurarSesionIniciada();
        $tokenSesion = isset($_SESSION['csrf_token']) ? (string)$_SESSION['csrf_token'] : '';
        $tokenRecibido = isset($datos['csrf_token']) ? (string)$datos['csrf_token'] : '';

        if ($tokenSesion === '' || $tokenRecibido === '' || !hash_equals($tokenSesion, $tokenRecibido)) {
            if ($esAjax) {
                $respuesta->enviarJson([
                    'status' => 'error',
                    'message' => 'Token de seguridad CSRF inválido o expirado.'
                ], 403);
                exit;
            }
            $_SESSION['error'] = 'Token de seguridad CSRF inválido o expirado. Recargue la página e intente nuevamente.';
            $respuesta->redirigir('/Cycsa/publico/equipos-calibracion');
            exit;
        }
    }

    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarPermisoVista($respuesta);

        $modelo = new EquipoCalibracionModelo();
        $db = Conexion::obtenerInstancia();

        $equipos = $modelo->obtenerEquipos();
        $picnometros = $modelo->obtenerPicnometros();
        $correcciones = $db->query("SELECT * FROM configuracion_correcciones_hc ORDER BY es_dudosa DESC, codigo_formato ASC, correccion_id ASC")->fetchAll(PDO::FETCH_ASSOC);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('configuracion/vistas/equipos_calibracion_index', [
            'titulo' => 'Equipos, Certificados de Calibración y Constantes (ISO/IEC 17025)',
            'equipos' => $equipos,
            'picnometros' => $picnometros,
            'correcciones' => $correcciones,
            'puedeEditarCalidad' => $this->tienePermisoEdicionCalidad(),
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    public function guardarConstante(Peticion $peticion, Respuesta $respuesta): void {
        $esAjax = $peticion->esAjax();
        $usuarioId = $this->verificarPermisoCalidad($respuesta, $esAjax);

        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/equipos-calibracion');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $this->verificarTokenCsrf($datos, $respuesta, $esAjax);

        $equipoId = (int)($datos['equipo_id'] ?? 0);
        $clave = trim((string)($datos['clave'] ?? ''));
        $valorNumerico = isset($datos['valor_numerico']) && $datos['valor_numerico'] !== '' && is_numeric($datos['valor_numerico'])
            ? (float)$datos['valor_numerico']
            : null;
        $refAlias = !empty($datos['ref_equipo_alias']) ? trim((string)$datos['ref_equipo_alias']) : null;
        $unidad = !empty($datos['unidad']) ? trim((string)$datos['unidad']) : null;
        $descripcion = !empty($datos['descripcion']) ? trim((string)$datos['descripcion']) : null;

        if ($equipoId <= 0 || $clave === '') {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Equipo o clave de constante no válidos.'], 422);
                return;
            }
            $_SESSION['error'] = 'Equipo o clave de constante no válidos.';
            $respuesta->redirigir('/Cycsa/publico/equipos-calibracion');
            return;
        }

        $modelo = new EquipoCalibracionModelo();
        $ok = $modelo->guardarConstanteEquipo($equipoId, $clave, $valorNumerico, null, $refAlias, $unidad, $descripcion);

        if ($ok && function_exists('registrarBitacora')) {
            registrarBitacora('equipos_calibracion', 'guardar_constante', "Constante {$clave} actualizada por usuario #{$usuarioId}", $equipoId);
        }

        if ($esAjax) {
            $respuesta->enviarJson([
                'status' => $ok ? 'success' : 'error',
                'message' => $ok ? 'Constante de calibración actualizada.' : 'No se pudo actualizar la constante.'
            ]);
            return;
        }

        $_SESSION[$ok ? 'exito' : 'error'] = $ok ? 'Constante de calibración actualizada correctamente.' : 'Error al actualizar constante.';
        $respuesta->redirigir('/Cycsa/publico/equipos-calibracion');
    }

    public function guardarPicnometro(Peticion $peticion, Respuesta $respuesta): void {
        $esAjax = $peticion->esAjax();
        $usuarioId = $this->verificarPermisoCalidad($respuesta, $esAjax);

        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/equipos-calibracion');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $this->verificarTokenCsrf($datos, $respuesta, $esAjax);

        $modelo = new EquipoCalibracionModelo();

        if (!empty($datos['evaluar_rt_fm_56'])) {
            $motor = new MotorHojasCalculoServicio($modelo);
            $eval = $motor->evaluarEspecimen('RT-FM-56', $datos);
            $vals = $eval['valores'];
            if (isset($vals['volumen'])) {
                $datos['volumen_calibrado_cm3'] = $vals['volumen'];
            }
            if (isset($vals['masa_vacio'])) {
                $datos['masa_picnometro_vacio_g'] = $vals['masa_vacio'];
            }
        }

        $id = $modelo->guardarPicnometroCalibrado($datos);

        if ($id > 0 && function_exists('registrarBitacora')) {
            registrarBitacora('equipos_calibracion', 'guardar_picnometro', "Picnómetro calibrado (RT-FM-56) actualizado por usuario #{$usuarioId}", $id);
        }

        if ($esAjax) {
            $respuesta->enviarJson([
                'status' => $id > 0 ? 'success' : 'error',
                'id' => $id,
                'message' => 'Calibración de picnómetro (RT-FM-56) registrada exitosamente.'
            ]);
            return;
        }

        $_SESSION['exito'] = 'Calibración de picnómetro (RT-FM-56) guardada correctamente.';
        $respuesta->redirigir('/Cycsa/publico/equipos-calibracion');
    }

    public function actualizarCorreccion(Peticion $peticion, Respuesta $respuesta): void {
        $esAjax = $peticion->esAjax();
        $usuarioId = $this->verificarPermisoCalidad($respuesta, $esAjax);

        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/equipos-calibracion');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $this->verificarTokenCsrf($datos, $respuesta, $esAjax);

        $codigoFormato = strtoupper(trim((string)($datos['codigo_formato'] ?? '')));
        $correccionId = strtoupper(trim((string)($datos['correccion_id'] ?? '')));
        $usarCorregido = !empty($datos['usar_corregido']);
        $confirmado = !empty($datos['confirmado_calidad']);

        if ($codigoFormato === '' || $correccionId === '') {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Formato o identificador de corrección inválido.'], 422);
                return;
            }
            $_SESSION['error'] = 'Formato o identificador de corrección inválido.';
            $respuesta->redirigir('/Cycsa/publico/equipos-calibracion');
            return;
        }

        $modelo = new EquipoCalibracionModelo();
        $ok = $modelo->actualizarEstadoCorreccion($codigoFormato, $correccionId, $usarCorregido, $confirmado, $usuarioId);

        if ($ok && function_exists('registrarBitacora')) {
            $modoTxt = $usarCorregido ? 'Fórmula Corregida' : 'Original Excel';
            registrarBitacora('equipos_calibracion', 'actualizar_correccion', "Corrección {$correccionId} ({$codigoFormato}) cambiada a {$modoTxt} por usuario #{$usuarioId}", 0);
        }

        if ($esAjax) {
            $respuesta->enviarJson([
                'status' => $ok ? 'success' : 'error',
                'message' => $ok ? 'Parámetro de corrección actualizado por Calidad.' : 'Error al actualizar parámetro.'
            ]);
            return;
        }

        $_SESSION[$ok ? 'exito' : 'error'] = $ok ? 'Parámetro de corrección actualizado.' : 'Error al actualizar parámetro.';
        $respuesta->redirigir('/Cycsa/publico/equipos-calibracion');
    }
}
