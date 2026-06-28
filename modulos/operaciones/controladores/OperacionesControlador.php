<?php

namespace Cycsa\Modulos\Operaciones\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;

class OperacionesControlador extends ControladorBase {
    
    private function verificarSesion(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
    }

    private function verificarPermiso(Respuesta $respuesta, string $accion = 'ver'): void {
        if (!tienePermiso('operaciones', $accion)) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
    }

    /**
     * Listado de operaciones activas (cotizaciones aprobadas).
     */
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $modelo = new OperacionModelo();
        $busqueda = $_GET['q'] ?? '';

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('operaciones/vistas/index', [
            'titulo' => 'Módulo de Operaciones - Cycsa',
            'operaciones' => $modelo->obtenerOperaciones($busqueda),
            'busqueda' => $busqueda,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Guarda las fechas de entrega y seguimiento, estado y notas operativas.
     */
    public function guardar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new OperacionModelo();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $idCotizacion = (int)$datos['id_cotizacion'];
            $fechaEntrega = !empty($datos['fecha_entrega']) ? $datos['fecha_entrega'] : null;
            $fechaSeguimiento = !empty($datos['fecha_seguimiento']) ? $datos['fecha_seguimiento'] : null;
            $estadoOperativo = $datos['estado_operativo'] ?? 'Pendiente';
            $notasOperativas = $datos['notas_operativas'] ?? '';
            $redireccionarA = $datos['redireccionar_a'] ?? '/Cycsa/publico/operaciones';

            if (empty($idCotizacion)) {
                $_SESSION['error'] = 'ID de cotización inválido.';
                $respuesta->redirigir($redireccionarA);
                return;
            }

            if ($modelo->guardarFechasOperacion($idCotizacion, $fechaEntrega, $fechaSeguimiento, $estadoOperativo, $notasOperativas)) {
                registrarBitacora('operaciones', 'actualizar_fechas', 'Fechas operativas actualizadas para Cotización N°: ' . $idCotizacion);
                $_SESSION['exito'] = 'Fechas de entrega y seguimiento actualizadas exitosamente.';
            } else {
                $_SESSION['error'] = 'Error al actualizar las fechas operativas.';
            }

            $respuesta->redirigir($redireccionarA);
        }
    }

    /**
     * Devuelve los detalles de una cotización sin incluir los precios (Llamado vía AJAX).
     */
    public function detalleAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        $id = $_GET['id'] ?? null;
        if (!$id) {
            $respuesta->enviarJson(['error' => 'ID inválido']);
            return;
        }

        $modelo = new OperacionModelo();
        $cotizacion = $modelo->obtenerOperacionPorId((int)$id);
        
        if (!$cotizacion) {
            $respuesta->enviarJson(['error' => 'Cotización aprobada no encontrada.']);
            return;
        }

        $detalles = $modelo->obtenerDetallesCotizacion((int)$id);

        $respuesta->enviarJson([
            'cotizacion' => $cotizacion,
            'detalles' => $detalles
        ]);
    }

    /**
     * Muestra la vista de calendario operativo.
     */
    public function calendario(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'ver');

        $modelo = new OperacionModelo();

        // Obtener mes y año a mostrar
        $mes = isset($_GET['mes']) ? (int)$_GET['mes'] : (int)date('m');
        $anio = isset($_GET['anio']) ? (int)$_GET['anio'] : (int)date('Y');

        if ($mes < 1 || $mes > 12) { $mes = (int)date('m'); }
        if ($anio < 2000 || $anio > 2100) { $anio = (int)date('Y'); }

        // Definir rango del mes para cargar eventos
        $fechaInicio = sprintf('%04d-%02d-01', $anio, $mes);
        $ultimoDia = date('t', strtotime($fechaInicio));
        $fechaFin = sprintf('%04d-%02d-%02d', $anio, $mes, $ultimoDia);

        // Obtener eventos
        $eventosRaw = $modelo->obtenerEventosCalendario($fechaInicio, $fechaFin);

        // Agrupar eventos por día para facilitar su pintado
        $eventosPorDia = [];
        for ($i = 1; $i <= $ultimoDia; $i++) {
            $eventosPorDia[$i] = [];
        }

        foreach ($eventosRaw as $ev) {
            $dia = (int)date('d', strtotime($ev['fecha_evento']));
            $eventosPorDia[$dia][] = $ev;
        }

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('operaciones/vistas/calendario', [
            'titulo' => 'Calendario Operativo - Cycsa',
            'mes' => $mes,
            'anio' => $anio,
            'ultimoDia' => $ultimoDia,
            'eventosPorDia' => $eventosPorDia,
            'primerDiaSemana' => (int)date('w', strtotime($fechaInicio)) // 0 (domingo) a 6 (sábado)
        ]);
    }
}
