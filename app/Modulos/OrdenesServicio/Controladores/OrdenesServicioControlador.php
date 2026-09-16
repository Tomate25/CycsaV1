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
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
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

        // Cargar técnicos activos para el autocompletado en la Hoja RT-FM-13
        $opModelo = new \Cycsa\Modulos\Operaciones\Modelos\OperacionModelo();
        $tecnicos = $opModelo->obtenerTecnicosActivos();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('OrdenesServicio/Vistas/index', [
            'titulo' => 'Órdenes de Servicio & Hojas de Recepción - CYCSA',
            'ordenes' => $ordenes,
            'tecnicos' => $tecnicos,
            'busqueda' => $busqueda,
            'id_os_auto' => (int)($_GET['id_os'] ?? 0)
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

        // 🔒 Validar CSRF
        $csrfToken = $datos['csrf_token'] ?? '';
        if (empty($csrfToken) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
            $_SESSION['error'] = 'Token de seguridad inválido o sesión expirada.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

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
                // NO requiere muestreo en campo -> Redirigir inmediatamente a Órdenes de Servicio con apertura de Hoja RT-FM-13
                $_SESSION['exito'] = 'Orden de Servicio registrada sin muestreo en campo. Redirigido a la Hoja de Servicio.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . $idOS);
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

        // Asegurar que la orden está marcada como que requiere muestreo en campo
        $osModelo->marcarRequiereMuestreo($idOS);

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
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $datos = $peticion->obtenerDatos();

        // 🔒 Validar CSRF
        $csrfToken = $datos['csrf_token'] ?? $_POST['csrf_token'] ?? '';
        $csrfValido = !empty($csrfToken) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $csrfToken);
        if (!$csrfValido && empty($_SESSION['usuario_id'])) {
            $_SESSION['error'] = 'Token de seguridad inválido o sesión expirada.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $idOS = (int)($datos['id_os'] ?? 0);
        $accionMuestreo = $datos['accion_muestreo'] ?? 'guardar';
        $esFinalizar = ($accionMuestreo === 'finalizar');

        // Procesar lista de chequeo si viene en el post
        $checklist = $datos['chk'] ?? [];
        $checklistJson = !empty($checklist) ? json_encode($checklist, JSON_UNESCAPED_UNICODE) : null;

        $estadoMuestreo = $esFinalizar ? 'Finalizado' : 'En Campo';

        $osModelo = new OrdenServicioModelo();
        $exito = $osModelo->guardarProgramacionMuestreo($idOS, [
            'fecha_ida' => $datos['fecha_ida'],
            'fecha_llegada' => $datos['fecha_llegada'],
            'id_tecnico' => (int)$datos['id_tecnico'],
            'id_vehiculo' => (int)$datos['id_vehiculo'],
            'lugar_muestreo' => trim($datos['lugar_muestreo'] ?? ''),
            'cantidad_muestras_est' => trim($datos['cantidad_muestras_est'] ?? ''),
            'num_muestreadores' => (int)($datos['num_muestreadores'] ?? 1),
            'observaciones_campo' => trim($datos['observaciones_campo'] ?? ''),
            'checklist_json' => $checklistJson,
            'estado_muestreo' => $estadoMuestreo
        ]);

        if ($exito) {
            if ($esFinalizar) {
                registrarBitacora('ordenes_servicio', 'finalizar_muestreo', "Muestreo finalizado (retorno al lab) y guardado para la Orden de Servicio ID: {$idOS}", $idOS);
                $_SESSION['exito'] = 'Muestreo en campo finalizado con éxito. El técnico retornó con los especímenes al laboratorio. Abriendo Hoja de Servicio (CYCSA-RT-FM-13)...';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . $idOS);
                return;
            } else {
                registrarBitacora('ordenes_servicio', 'programar_muestreo', "Programación de muestreo y checklist guardados para la Orden de Servicio ID: {$idOS}", $idOS);
                $_SESSION['exito'] = 'Programación de muestreo y Lista de Chequeo CYCSA-RT-FM-40 B guardada con éxito.';
            }
        } else {
            $_SESSION['error'] = 'Ocurrió un error al guardar la programación de muestreo.';
        }

        // Si se solicitó imprimir, redirige a la vista imprimible
        if (!empty($datos['accion_imprimir'])) {
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/imprimir-checklist?id=' . $idOS);
            return;
        }

        // Redirige de vuelta a programar muestreo
        $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/programar-muestreo?id=' . $idOS);
    }

    /**
     * Acción para marcar "Muestreo Finalizado" (El regreso del técnico al laboratorio)
     */
    public function finalizarMuestreo(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $esAjax = !empty($_POST['ajax']) || (isset($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') || !empty($datos['ajax']);

        // 🔒 Validar CSRF
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $csrfHeader = $headers['X-CSRF-TOKEN'] ?? $headers['X-Csrf-Token'] ?? $headers['x-csrf-token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $csrfToken = $datos['csrf_token'] ?? $_POST['csrf_token'] ?? $csrfHeader;
        $csrfValido = !empty($csrfToken) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $csrfToken);

        if (!$csrfValido && empty($_SESSION['usuario_id'])) {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Token de seguridad inválido o sesión expirada.']);
                return;
            }
            $_SESSION['error'] = 'Token de seguridad inválido o sesión expirada.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $idOS = (int)($datos['id_os'] ?? 0);

        $osModelo = new OrdenServicioModelo();
        $exito = $osModelo->finalizarMuestreo($idOS);

        if ($exito) {
            registrarBitacora('ordenes_servicio', 'finalizar_muestreo', "Muestreo finalizado para la Orden de Servicio ID: {$idOS}", $idOS);
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'success', 'message' => 'Muestreo finalizado con éxito.']);
                return;
            }
            $_SESSION['exito'] = 'Muestreo en campo finalizado con éxito. Abriendo Hoja de Servicio (CYCSA RT-FM-13)...';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . $idOS);
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

        // 🔒 Validar CSRF
        $headers = function_exists('getallheaders') ? getallheaders() : [];
        $csrfHeader = $headers['X-CSRF-TOKEN'] ?? $headers['X-Csrf-Token'] ?? $headers['x-csrf-token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        $csrfToken = $_POST['csrf_token'] ?? $csrfHeader;
        $csrfValido = !empty($csrfToken) && !empty($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $csrfToken);

        if (!$csrfValido && empty($_SESSION['usuario_id'])) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Token de seguridad inválido o sesión expirada.']);
            return;
        }

        $idOS = (int)($_POST['id_os'] ?? 0);
        $osModelo = new OrdenServicioModelo();
        $exito = $osModelo->establecerIngresoDirecto($idOS);
        $respuesta->enviarJson(['status' => $exito ? 'success' : 'error']);
    }

    /**
     * Imprimir Formato Oficial CYCSA-RT-FM-40 B (Lista de Chequeo para Muestreos de Compactación)
     */
    public function imprimirListaChequeo(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        $idOS = (int)($_GET['id'] ?? 0);
        $osModelo = new OrdenServicioModelo();
        $os = $osModelo->obtenerPorId($idOS);

        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        require __DIR__ . '/../Vistas/imprimir_checklist.php';
        exit;
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
