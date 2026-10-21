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
 */
class EquiposCalibracionControlador extends ControladorBase {

    private function verificarSesion(Respuesta $respuesta): void {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
    }

    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

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
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null,
        ]);
        unset($_SESSION['exito'], $_SESSION['error']);
    }

    public function guardarConstante(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/equipos-calibracion');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $equipoId = (int)($datos['equipo_id'] ?? 0);
        $clave = trim($datos['clave'] ?? '');
        $valorNumerico = isset($datos['valor_numerico']) && $datos['valor_numerico'] !== '' ? (float)$datos['valor_numerico'] : null;
        $refAlias = !empty($datos['ref_equipo_alias']) ? trim($datos['ref_equipo_alias']) : null;
        $unidad = !empty($datos['unidad']) ? trim($datos['unidad']) : null;
        $descripcion = !empty($datos['descripcion']) ? trim($datos['descripcion']) : null;

        $modelo = new EquipoCalibracionModelo();
        $ok = $modelo->guardarConstanteEquipo($equipoId, $clave, $valorNumerico, null, $refAlias, $unidad, $descripcion);

        if ($peticion->esAjax()) {
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
        $this->verificarSesion($respuesta);
        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/equipos-calibracion');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $modelo = new EquipoCalibracionModelo();

        // Si se enviaron lecturas de RT-FM-56, evaluar con MotorHojasCalculoServicio primero
        if (!empty($datos['evaluar_rt_fm_56'])) {
            $motor = new MotorHojasCalculoServicio($modelo);
            $eval = $motor->evaluarEspecimen('RT-FM-56', $datos);
            $vals = $eval['valores'];
            if (isset($vals['volumen_prom_ml'])) {
                $datos['volumen_calibrado_cm3'] = $vals['volumen_prom_ml'];
            }
            if (isset($vals['masa_vacio_prom'])) {
                $datos['masa_picnometro_vacio_g'] = $vals['masa_vacio_prom'];
            }
        }

        $id = $modelo->guardarPicnometroCalibrado($datos);

        if ($peticion->esAjax()) {
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
        $this->verificarSesion($respuesta);
        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/equipos-calibracion');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $codigoFormato = trim($datos['codigo_formato'] ?? '');
        $correccionId = trim($datos['correccion_id'] ?? '');
        $usarCorregido = !empty($datos['usar_corregido']);
        $confirmado = !empty($datos['confirmado_calidad']);
        $usuarioId = (int)($_SESSION['usuario_id'] ?? 1);

        $modelo = new EquipoCalibracionModelo();
        $ok = $modelo->actualizarEstadoCorreccion($codigoFormato, $correccionId, $usarCorregido, $confirmado, $usuarioId);

        if ($peticion->esAjax()) {
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
