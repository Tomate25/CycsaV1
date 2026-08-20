<?php

namespace Cycsa\Modulos\OrdenesServicio\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\Cotizaciones\Modelos\CotizacionModelo;

class OrdenesServicioControlador extends ControladorBase {

    private function verificarSesion(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
    }

    /**
     * Lista general de Órdenes de Servicio
     */
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        $modelo = new OrdenServicioModelo();
        $busqueda = trim($_GET['q'] ?? '');
        $ordenes = $modelo->obtenerTodas($busqueda);

        $this->renderizar('OrdenesServicio/Vistas/index', [
            'titulo' => 'Órdenes de Servicio (CYCSA-RG-FM-39 V1)',
            'ordenes' => $ordenes,
            'busqueda' => $busqueda
        ]);
    }

    /**
     * Formulario para generar una nueva Orden de Servicio desde una Cotización aprobada
     */
    public function crear(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        $idCotizacion = (int)($_GET['id_cotizacion'] ?? 0);
        if ($idCotizacion <= 0) {
            $_SESSION['error'] = 'Debe seleccionar una cotización válida para generar una Orden de Servicio.';
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            return;
        }

        $cotizacionModelo = new CotizacionModelo();
        $cotizacion = $cotizacionModelo->obtenerPorId($idCotizacion);

        if (!$cotizacion) {
            $_SESSION['error'] = 'La cotización solicitada no existe.';
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
            return;
        }

        $detalles = $cotizacionModelo->obtenerDetalles($idCotizacion);
        $osModelo = new OrdenServicioModelo();
        $nuevoCodigoOS = $osModelo->generarCodigoOS();

        $this->renderizar('OrdenesServicio/Vistas/crear', [
            'titulo' => 'Nueva Orden de Servicio - CYCSA-RG-FM-39 V1',
            'cotizacion' => $cotizacion,
            'detalles' => $detalles,
            'codigo_os' => $nuevoCodigoOS
        ]);
    }

    /**
     * Procesar la creación de la Orden de Servicio y ejecutar la bifurcación (Paso 2)
     */
    public function guardar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $idCotizacion = (int)($datos['id_cotizacion'] ?? 0);
        $requiereMuestreo = isset($datos['requiere_muestreo']) && ($datos['requiere_muestreo'] === '1' || $datos['requiere_muestreo'] === 1);

        $osModelo = new OrdenServicioModelo();
        $codigoOS = !empty($datos['codigo_os']) ? trim($datos['codigo_os']) : $osModelo->generarCodigoOS();

        // Determinar estado inicial según la decisión de muestreo en campo
        $estadoInicial = $requiereMuestreo ? 'Pendiente de Muestreo' : 'Estado 1: Recepcion';

        $idOS = $osModelo->crear([
            'codigo_os' => $codigoOS,
            'id_cotizacion' => $idCotizacion,
            'id_cliente' => (int)($datos['id_cliente'] ?? 0),
            'elaborado_por' => $_SESSION['usuario_nombre'] ?? 'Administración',
            'fecha_emision' => $datos['fecha_emision'] ?? date('Y-m-d'),
            'atencion_a' => trim($datos['atencion_a'] ?? ''),
            'nombre_proyecto' => trim($datos['nombre_proyecto'] ?? ''),
            'forma_pago' => trim($datos['forma_pago'] ?? 'Pago contra entrega'),
            'notas_condiciones' => trim($datos['notas_condiciones'] ?? ''),
            'contactos_json' => $datos['contactos'] ?? [],
            'requiere_muestreo' => $requiereMuestreo ? 1 : 0,
            'estado' => $estadoInicial
        ]);

        if ($idOS > 0) {
            registrarBitacora('ordenes_servicio', 'crear', "Orden de Servicio creada: {$codigoOS} (Muestreo: " . ($requiereMuestreo ? 'Sí' : 'No') . ")", $idOS);

            // BIFURCACIÓN (PASO 2)
            if ($requiereMuestreo) {
                // SÍ requiere muestreo en campo -> Redirigir a Programación de Muestreo (Paso 3)
                $_SESSION['exito'] = 'Orden de Servicio registrada. Proceda con la programación de muestreo en campo.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/programar-muestreo?id=' . $idOS);
            } else {
                // NO requiere muestreo en campo -> Redirigir inmediatamente a Hojas de Servicio (CYCSA RT-FM-13)
                $_SESSION['exito'] = 'Orden de Servicio registrada sin muestreo en campo. Redirigido a la Hoja de Servicio.';
                $respuesta->redirigir('/Cycsa/publico/hojas-servicio?id_os=' . $idOS);
            }
        } else {
            $_SESSION['error'] = 'Error al registrar la Orden de Servicio.';
            $respuesta->redirigir('/Cycsa/publico/cotizaciones');
        }
    }

    /**
     * Vista de Programación de Muestreo en Campo (Logística - Paso 3)
     */
    public function programarMuestreo(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        $idOS = (int)($_GET['id'] ?? 0);
        $osModelo = new OrdenServicioModelo();
        $os = $osModelo->obtenerPorId($idOS);

        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $tecnicos = $osModelo->obtenerTecnicos();
        $vehiculos = $osModelo->obtenerVehiculos();

        $this->renderizar('OrdenesServicio/Vistas/programar_muestreo', [
            'titulo' => 'Programación de Muestreo en Campo - ' . $os['codigo_os'],
            'os' => $os,
            'tecnicos' => $tecnicos,
            'vehiculos' => $vehiculos
        ]);
    }

    /**
     * Guardar la asignación de técnico, vehículo y fechas de muestreo
     */
    public function guardarProgramacionMuestreo(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $idOS = (int)($datos['id_os'] ?? 0);

        $osModelo = new OrdenServicioModelo();
        $exito = $osModelo->guardarProgramacionMuestreo($idOS, [
            'fecha_ida' => $datos['fecha_ida'],
            'fecha_llegada' => $datos['fecha_llegada'],
            'id_tecnico' => (int)$datos['id_tecnico'],
            'id_vehiculo' => (int)$datos['id_vehiculo'],
            'observaciones_campo' => trim($datos['observaciones_campo'] ?? ''),
            'estado_muestreo' => 'En Proceso'
        ]);

        if ($exito) {
            $_SESSION['exito'] = 'Programación de muestreo registrada con éxito. El técnico ha sido asignado y se encuentra en salida a campo.';
        } else {
            $_SESSION['error'] = 'Ocurrió un error al guardar la programación de muestreo.';
        }

        // Redirige de vuelta al listado normal de Hojas de Servicio (no abre el modal todavía, porque el técnico apenas va saliendo)
        $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
    }

    /**
     * Acción para marcar "Muestreo Finalizado" (El regreso del técnico al laboratorio)
     */
    public function finalizarMuestreo(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/hojas-servicio');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $idOS = (int)($datos['id_os'] ?? 0);

        $osModelo = new OrdenServicioModelo();
        $exito = $osModelo->finalizarMuestreo($idOS);

        $esAjax = !empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest');

        if ($exito) {
            registrarBitacora('ordenes_servicio', 'finalizar_muestreo', "Muestreo finalizado para la Orden de Servicio ID: {$idOS}", $idOS);
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'success', 'message' => 'Muestreo finalizado con éxito.']);
                return;
            }
            $_SESSION['exito'] = 'Muestreo en campo finalizado con éxito. Abriendo Hoja de Servicio (CYCSA RT-FM-13)...';
            $respuesta->redirigir('/Cycsa/publico/hojas-servicio?id_os=' . $idOS);
        } else {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Error al finalizar el muestreo.']);
                return;
            }
            $_SESSION['error'] = 'Error al finalizar el muestreo.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/programar-muestreo?id=' . $idOS);
        }
    }

    /**
     * Marcar ingreso directo vía Ajax cuando no se requiere muestreo
     */
    public function marcarIngresoDirectoAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $idOS = (int)($_POST['id_os'] ?? 0);
        $osModelo = new OrdenServicioModelo();
        $exito = $osModelo->establecerIngresoDirecto($idOS);
        $respuesta->enviarJson(['status' => $exito ? 'success' : 'error']);
    }

    /**
     * Vista de detalle del documento CYCSA-RG-FM-39 V1
     */
    public function detalle(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        $idOS = (int)($_GET['id'] ?? 0);
        $osModelo = new OrdenServicioModelo();
        $os = $osModelo->obtenerPorId($idOS);

        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $this->renderizar('OrdenesServicio/Vistas/detalle', [
            'titulo' => 'Orden de Servicio ' . $os['codigo_os'],
            'os' => $os
        ]);
    }
}
