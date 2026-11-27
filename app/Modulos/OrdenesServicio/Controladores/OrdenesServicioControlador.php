<?php

namespace Cycsa\Modulos\OrdenesServicio\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\OrdenesServicio\Modelos\OrdenServicioModelo;
use Cycsa\Modulos\OrdenesServicio\Servicios\DocumentoRgFm39Servicio;
use Cycsa\Modulos\OrdenesServicio\Servicios\ValidacionOrdenServicioServicio;
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
        if (!tienePermiso('ordenes_servicio', 'ver')) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            return;
        }
        
        $modelo = new OrdenServicioModelo();
        $busqueda = trim($_GET['q'] ?? '');
        $tabSolicitada = strtolower(trim($_GET['tab'] ?? 'activas'));
        $whitelistTabs = ['activas', 'muestreo', 'historico', 'todas'];
        if (!in_array($tabSolicitada, $whitelistTabs, true)) {
            $tabSolicitada = 'activas';
        }

        $idOSAuto = (int)($_GET['id_os'] ?? 0);
        if ($idOSAuto > 0) {
            $tabDeOS = $modelo->determinarTabDeOS($idOSAuto);
            if (!isset($_GET['tab']) || ($tabSolicitada !== 'todas' && $tabSolicitada !== $tabDeOS)) {
                $tabSolicitada = $tabDeOS;
            }
        }

        $filtros = [
            'estado' => trim($_GET['estado'] ?? ''),
            'desde' => trim($_GET['desde'] ?? ''),
            'hasta' => trim($_GET['hasta'] ?? '')
        ];

        $ordenes = $modelo->obtenerTodas($busqueda, $tabSolicitada, $filtros);
        $conteosTabs = $modelo->obtenerConteosTabsOSLista();

        // Cargar técnicos y vehículos activos para RT-FM-13 y documento FM-40 B en línea
        $opModelo = new \Cycsa\Modulos\Operaciones\Modelos\OperacionModelo();
        $tecnicos = $opModelo->obtenerTecnicosActivos();
        $vehiculos = $modelo->obtenerVehiculos();

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        require_once dirname(__DIR__, 3) . '/Helpers/funciones.php';

        // Evaluar vencimiento automático de plazo en órdenes mensuales abiertas y cargar notificaciones pendientes (Puntos 9, 10, 13)
        $notificacionesOs = [];
        try {
            $dbOs = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            if (ValidacionOrdenServicioServicio::esquemaDisponible($dbOs)) {
                $stmtVenc = $dbOs->query("
                    SELECT id FROM ordenes_servicio
                    WHERE (es_multimes = 1 OR tipo_contrato IN ('Marco', 'Contrato Activo', 'Contrato Largo', 'Abierto'))
                      AND fecha_fin IS NOT NULL AND fecha_fin <= CURRENT_DATE
                      AND devuelto_sistema_fecha IS NULL
                      AND estado NOT IN ('Ejecutada', 'Finalizado', 'Cerrado', 'Archivado')
                    LIMIT 25
                ");
                foreach ($stmtVenc->fetchAll(\PDO::FETCH_COLUMN) as $idVenc) {
                    ValidacionOrdenServicioServicio::evaluarYActualizarEjecucionAutomatica($dbOs, (int)$idVenc);
                }
                $uIdSes = (int)($_SESSION['usuario_id'] ?? 0);
                $rIdSes = (int)($_SESSION['usuario_rol'] ?? 0);
                if (ValidacionOrdenServicioServicio::puedeRecibirAdminFinal($dbOs, $uIdSes, $rIdSes)) {
                    $notificacionesOs = ValidacionOrdenServicioServicio::obtenerNotificacionesPendientes(
                        $dbOs,
                        ValidacionOrdenServicioServicio::AREA_ADMIN_CONTABILIDAD,
                        15
                    );
                } elseif (ValidacionOrdenServicioServicio::puedeRecibirLaboratorioORecepcion($dbOs, $uIdSes, $rIdSes)) {
                    $notificacionesOs = ValidacionOrdenServicioServicio::obtenerNotificacionesPendientes(
                        $dbOs,
                        ValidacionOrdenServicioServicio::AREA_LABORATORIO,
                        15
                    );
                }
            }
        } catch (\Throwable $e) {
            $notificacionesOs = [];
        }

        $this->renderizar('OrdenesServicio/Vistas/index', [
            'titulo' => 'Órdenes de Servicio & Hojas de Recepción - CYCSA',
            'ordenes' => $ordenes,
            'tecnicos' => $tecnicos,
            'vehiculos' => $vehiculos,
            'busqueda' => $busqueda,
            'tabActiva' => $tabSolicitada,
            'conteosTabs' => $conteosTabs,
            'filtros' => $filtros,
            'id_os_auto' => $idOSAuto,
            'notificaciones_os' => $notificacionesOs,
            'csrfToken' => (string)($_SESSION['csrf_token'] ?? '')
        ]);
    }

    /**
     * Formulario para generar una nueva Orden de Servicio desde una Cotización aprobada
     */
    public function crear(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('ordenes_servicio', 'crear_editar')) {
            $_SESSION['error'] = 'No cuenta con permisos para crear órdenes de servicio.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }
        
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
            'codigo_os' => $nuevoCodigoOS,
            'clientes' => \Cycsa\Nucleo\Conexion::obtenerInstancia()->query("SELECT id, nombre_razon_social, numero_ruc FROM clientes ORDER BY nombre_razon_social ASC")->fetchAll(\PDO::FETCH_ASSOC)
        ]);
    }

    private function extraerContactosDesdePost(array $datos, $fallback = []): array {
        $nombres = $datos['contacto_nombre'] ?? null;
        $telefonos = $datos['contacto_telefono'] ?? [];
        $correos = $datos['contacto_correo'] ?? [];
        if (is_array($nombres)) {
            $lista = [];
            foreach ($nombres as $idx => $nom) {
                $n = trim((string)$nom);
                $t = trim((string)($telefonos[$idx] ?? ''));
                $c = trim((string)($correos[$idx] ?? ''));
                if ($n !== '' || $t !== '' || $c !== '') {
                    $lista[] = ['nombre' => $n, 'telefono' => $t, 'correo' => $c];
                }
            }
            return $lista;
        }
        if (isset($datos['contactos']) && is_array($datos['contactos'])) {
            return $datos['contactos'];
        }
        if (!empty($datos['contactos_json'])) {
            if (is_array($datos['contactos_json'])) {
                return $datos['contactos_json'];
            }
            $dec = json_decode((string)$datos['contactos_json'], true);
            if (is_array($dec)) {
                return $dec;
            }
        }
        if (is_string($fallback) && trim($fallback) !== '') {
            $decFb = json_decode($fallback, true);
            if (is_array($decFb)) {
                return $decFb;
            }
            $lineas = array_filter(array_map('trim', preg_split('/\r?\n/', $fallback) ?: []));
            $lista = [];
            foreach ($lineas as $ln) {
                $partes = array_map('trim', explode('|', $ln));
                $lista[] = [
                    'nombre' => $partes[0] ?? $ln,
                    'telefono' => $partes[1] ?? '',
                    'correo' => $partes[2] ?? '',
                ];
            }
            return $lista;
        }
        return is_array($fallback) ? $fallback : [];
    }

    private function extraerDocumentosVinculadosDesdePost(array $datos, $fallback = []): array {
        $base = is_array($fallback) ? $fallback : (is_string($fallback) ? (json_decode($fallback, true) ?: []) : []);
        if (array_key_exists('doc_solicitud_cliente', $datos) || array_key_exists('doc_otros_anexos', $datos)) {
            return [
                'solicitud_cliente' => trim((string)($datos['doc_solicitud_cliente'] ?? ($base['solicitud_cliente'] ?? ''))),
                'otros_anexos' => trim((string)($datos['doc_otros_anexos'] ?? ($base['otros_anexos'] ?? ''))),
            ];
        }
        return $base;
    }

    /**
     * Procesar la creación de la Orden de Servicio y ejecutar la bifurcación (Paso 2)
     */
    public function guardar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('ordenes_servicio', 'crear_editar')) {
            $_SESSION['error'] = 'No cuenta con permisos para registrar órdenes de servicio.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

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

        $cotizacionModelo = new CotizacionModelo();
        $cotizacion = $cotizacionModelo->obtenerPorId($idCotizacion);

        $formaPago = !empty(trim($datos['forma_pago'] ?? ''))
            ? trim($datos['forma_pago'])
            : (!empty($cotizacion['condicion_pago']) ? trim($cotizacion['condicion_pago']) : 'Pago contra entrega');
        $condicionComercial = !empty($datos['condicion_comercial'])
            ? trim($datos['condicion_comercial'])
            : (!empty($cotizacion['condicion_comercial']) ? trim($cotizacion['condicion_comercial']) : determinarCondicionComercial($formaPago));

        // Determinar estado inicial según la decisión de muestreo en campo
        $estadoInicial = $requiereMuestreo ? 'Pendiente de Muestreo' : 'Estado 1: Recepcion';

        $esMultimes = isset($datos['es_multimes'])
            ? (($datos['es_multimes'] === '1' || $datos['es_multimes'] === 1 || $datos['es_multimes'] === 'on') ? 1 : 0)
            : ((!isset($datos['tipo_contrato']) && !empty($cotizacion['es_mensual'])) ? 1 : 0);
        $duracionMeses = !empty($datos['duracion_meses'])
            ? max(1, (int)$datos['duracion_meses'])
            : ($esMultimes && !empty($cotizacion['duracion_meses']) ? max(1, (int)$cotizacion['duracion_meses']) : null);
        $fechaInicio = !empty($datos['fecha_inicio'])
            ? trim($datos['fecha_inicio'])
            : ($esMultimes && !empty($cotizacion['fecha_inicio_periodo']) ? trim((string)$cotizacion['fecha_inicio_periodo']) : null);
        $fechaFin = !empty($datos['fecha_fin'])
            ? trim($datos['fecha_fin'])
            : ($esMultimes && !empty($cotizacion['fecha_fin_periodo']) ? trim((string)$cotizacion['fecha_fin_periodo']) : null);
        $periodicidad = !empty($datos['periodicidad'])
            ? trim($datos['periodicidad'])
            : (!empty($cotizacion['periodicidad']) ? trim((string)$cotizacion['periodicidad']) : 'Mensual');

        $contactosOS = $this->extraerContactosDesdePost($datos, $cotizacion['contactos'] ?? []);
        $docsVinculadosOS = $this->extraerDocumentosVinculadosDesdePost($datos, []);

        $idOS = $osModelo->crear([
            'codigo_os' => $codigoOS,
            'id_cotizacion' => $idCotizacion,
            'cotizacion_version' => (int)($datos['cotizacion_version'] ?? ($cotizacion['version'] ?? 1)),
            'id_cliente' => (int)($datos['id_cliente'] ?? ($cotizacion['id_cliente'] ?? 0)),
            'id_cliente_factura' => !empty($datos['id_cliente_factura']) ? (int)$datos['id_cliente_factura'] : (int)($cotizacion['id_cliente_factura'] ?? ($datos['id_cliente'] ?? 0)),
            'id_cliente_informe' => !empty($datos['id_cliente_informe']) ? (int)$datos['id_cliente_informe'] : (int)($cotizacion['id_cliente_informe'] ?? ($datos['id_cliente'] ?? 0)),
            'condicion_comercial' => $condicionComercial,
            'elaborado_por' => $_SESSION['usuario_nombre'] ?? 'Administración',
            'fecha_emision' => $datos['fecha_emision'] ?? date('Y-m-d'),
            'atencion_a' => trim($datos['atencion_a'] ?? ($cotizacion['atencion_a'] ?? '')),
            'direccion_cliente' => trim($datos['direccion_cliente'] ?? ($cotizacion['direccion_cliente'] ?? ($cotizacion['cliente_direccion'] ?? ''))),
            'nombre_proyecto' => trim($datos['nombre_proyecto'] ?? ($cotizacion['nombre_proyecto'] ?? '')),
            'ubicacion_proyecto' => trim($datos['ubicacion_proyecto'] ?? ($cotizacion['direccion_proyecto'] ?? '')),
            'forma_pago' => $formaPago,
            'notas_condiciones' => trim($datos['notas_condiciones'] ?? ''),
            'contactos_json' => $contactosOS,
            'documentos_vinculados_json' => $docsVinculadosOS,
            'requiere_muestreo' => $requiereMuestreo ? 1 : 0,
            'tipo_contrato' => !empty($datos['tipo_contrato']) ? trim($datos['tipo_contrato']) : ($esMultimes ? 'Marco' : 'Puntual'),
            'es_multimes' => $esMultimes,
            'duracion_meses' => $duracionMeses,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'periodicidad' => $periodicidad,
            'estado' => $estadoInicial
        ]);

        if ($idOS > 0) {
            registrarEventoOS('ordenes_servicio', 'crear', "Orden de Servicio creada: {$codigoOS} (Muestreo: " . ($requiereMuestreo ? 'Sí' : 'No') . ")", $idOS);

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
     * Formulario para editar una Orden de Servicio existente
     */
    public function editar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        $idOS = (int)($_GET['id_os'] ?? ($_GET['id'] ?? 0));
        if ($idOS <= 0) {
            $_SESSION['error'] = 'Debe especificar una Orden de Servicio válida.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $osModelo = new OrdenServicioModelo();
        $os = $osModelo->obtenerPorId($idOS);

        if (!$os) {
            $_SESSION['error'] = 'La Orden de Servicio solicitada no existe.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        // Bloquea si estado está en ['Finalizado', 'Archivado', 'Cerrado']
        if (in_array($os['estado'] ?? '', ['Finalizado', 'Archivado', 'Cerrado'], true)) {
            $_SESSION['error'] = 'La Orden de Servicio está cerrada o archivada y no admite modificaciones.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $cotizacionModelo = new CotizacionModelo();
        $cotizacion = $cotizacionModelo->obtenerPorId((int)$os['id_cotizacion']);
        $detalles = $cotizacion ? $cotizacionModelo->obtenerDetalles((int)$os['id_cotizacion']) : ($os['ensayos'] ?? []);

        $clientes = \Cycsa\Nucleo\Conexion::obtenerInstancia()
            ->query("SELECT id, nombre_razon_social, numero_ruc FROM clientes ORDER BY nombre_razon_social ASC")
            ->fetchAll(\PDO::FETCH_ASSOC);

        $ensayosPermitidosActuales = [];
        try {
            $ensayosPermitidosActuales = $osModelo->obtenerIdsPermitidos($idOS) ?? [];
        } catch (\Throwable $e) {
            $ensayosPermitidosActuales = [];
        }

        $this->renderizar('OrdenesServicio/Vistas/editar', [
            'titulo' => 'Editar Orden de Servicio - ' . ($os['codigo_os'] ?? ''),
            'os' => $os,
            'cotizacion' => $cotizacion ?: ['codigo' => 'N/A', 'version' => 1, 'cliente_nombre' => $os['cliente_nombre'] ?? ''],
            'detalles' => $detalles,
            'clientes' => $clientes,
            'ensayosPermitidosActuales' => $ensayosPermitidosActuales,
        ]);
    }

    /**
     * Procesar la actualización de una Orden de Servicio
     */
    public function actualizar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $datos = $peticion->obtenerDatos();

        // 🔒 Validar CSRF
        $csrfToken = $datos['csrf_token'] ?? '';
        if (empty($csrfToken) || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], (string)$csrfToken)) {
            $_SESSION['error'] = 'Token de seguridad inválido o sesión expirada.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $idOS = (int)($datos['id_os'] ?? ($datos['id'] ?? 0));
        if ($idOS <= 0) {
            $_SESSION['error'] = 'Orden de Servicio inválida.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $osModelo = new OrdenServicioModelo();
        $os = $osModelo->obtenerPorId($idOS);
        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        // Bloquea si la O/S está en ['Finalizado', 'Archivado', 'Cerrado']
        if (in_array($os['estado'] ?? '', ['Finalizado', 'Archivado', 'Cerrado'], true)) {
            $_SESSION['error'] = 'La Orden de Servicio está cerrada o archivada y no admite modificaciones.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        require_once dirname(__DIR__, 3) . '/Helpers/funciones.php';
        $formaPago = trim($datos['forma_pago'] ?? ($os['forma_pago'] ?? 'Pago contra entrega'));
        $condicionComercial = !empty($datos['condicion_comercial'])
            ? trim($datos['condicion_comercial'])
            : (function_exists('determinarCondicionComercial') ? determinarCondicionComercial($formaPago) : 'contado');

        $esMultimes = isset($datos['es_multimes']) && ($datos['es_multimes'] === '1' || $datos['es_multimes'] === 1 || $datos['es_multimes'] === 'on') ? 1 : 0;
        $duracionMeses = !empty($datos['duracion_meses']) ? max(1, (int)$datos['duracion_meses']) : null;
        $fechaInicio = !empty($datos['fecha_inicio']) ? trim($datos['fecha_inicio']) : null;
        $fechaFin = !empty($datos['fecha_fin']) ? trim($datos['fecha_fin']) : null;
        $periodicidad = !empty($datos['periodicidad']) ? trim($datos['periodicidad']) : 'Mensual';

        $nuevoAtencionA = trim($datos['atencion_a'] ?? ($os['atencion_a'] ?? ''));
        $nuevoProyecto = trim($datos['nombre_proyecto'] ?? ($os['nombre_proyecto'] ?? ''));
        $nuevoCliFac = !empty($datos['id_cliente_factura']) ? (int)$datos['id_cliente_factura'] : (int)($os['id_cliente_factura'] ?? $os['id_cliente']);
        $nuevoCliInf = !empty($datos['id_cliente_informe']) ? (int)$datos['id_cliente_informe'] : (int)($os['id_cliente_informe'] ?? $os['id_cliente']);
        $nuevasNotas = trim($datos['notas_condiciones'] ?? ($os['notas_condiciones'] ?? ''));

        $cambioComercialCore = (
            $nuevoAtencionA !== trim((string)($os['atencion_a'] ?? '')) ||
            $nuevoProyecto !== trim((string)($os['nombre_proyecto'] ?? '')) ||
            $formaPago !== trim((string)($os['forma_pago'] ?? '')) ||
            $condicionComercial !== trim((string)($os['condicion_comercial'] ?? '')) ||
            $nuevoCliFac !== (int)($os['id_cliente_factura'] ?? $os['id_cliente']) ||
            $nuevoCliInf !== (int)($os['id_cliente_informe'] ?? $os['id_cliente']) ||
            $nuevasNotas !== trim((string)($os['notas_condiciones'] ?? ''))
        );

        // Regla de consistencia: Si ya estaba aprobada pero solo se ajusta el período mensual/contrato largo, conservar estado sin pedir re-aprobación
        $estadoActual = $os['estado'] ?? '';
        $requiereReaprobacion = false;
        $nuevoEstado = $estadoActual;
        $motivoCambioVersion = trim((string)($datos['motivo_cambio_version'] ?? ''));
        if (esOrdenServicioAprobada($estadoActual) && $cambioComercialCore) {
            $nuevoEstado = 'Estado 2: Revision';
            $requiereReaprobacion = true;
            if ($motivoCambioVersion === '') {
                $motivoCambioVersion = 'Modificación comercial en O/S aprobada';
            }
        }

        $contactosActualizados = $this->extraerContactosDesdePost($datos, $os['contactos_json'] ?? []);
        $docsActualizados = $this->extraerDocumentosVinculadosDesdePost($datos, $os['documentos_vinculados_json'] ?? []);

        $datosActualizar = [
            'atencion_a' => $nuevoAtencionA,
            'direccion_cliente' => array_key_exists('direccion_cliente', $datos) ? trim((string)$datos['direccion_cliente']) : ($os['direccion_cliente'] ?? ''),
            'nombre_proyecto' => $nuevoProyecto,
            'ubicacion_proyecto' => array_key_exists('ubicacion_proyecto', $datos) ? trim((string)$datos['ubicacion_proyecto']) : ($os['ubicacion_proyecto'] ?? ''),
            'forma_pago' => $formaPago,
            'condicion_comercial' => $condicionComercial,
            'id_cliente_factura' => $nuevoCliFac,
            'id_cliente_informe' => $nuevoCliInf,
            'notas_condiciones' => $nuevasNotas,
            'contactos_json' => $contactosActualizados,
            'documentos_vinculados_json' => $docsActualizados,
            'tipo_contrato' => !empty($datos['tipo_contrato']) ? trim($datos['tipo_contrato']) : ($esMultimes ? 'Marco' : ($os['tipo_contrato'] ?? 'Puntual')),
            'es_multimes' => $esMultimes,
            'duracion_meses' => $duracionMeses,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'periodicidad' => $periodicidad,
            'estado' => $nuevoEstado,
            'nueva_version' => $requiereReaprobacion,
            'motivo_cambio_version' => $requiereReaprobacion ? $motivoCambioVersion : '',
        ];

        $actualizado = $osModelo->actualizar($idOS, $datosActualizar);

        // Si el supervisor eligió "Guardar y Aprobar O/S" desde la pantalla de edición completa:
        $solicitaAprobarAlGuardar = !empty($datos['aprobar_al_guardar']);
        if ($actualizado && $solicitaAprobarAlGuardar) {
            $rolAct = (int)($_SESSION['usuario_rol'] ?? 0);
            if (!in_array($rolAct, [1, 3], true) && !tienePermiso('ordenes_servicio', 'aprobar')) {
                $_SESSION['error'] = 'Los cambios fueron guardados, pero no tiene permisos de supervisor para aprobar la Orden de Servicio.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/editar?id_os=' . $idOS);
                return;
            }

            $idsPermitidos = $this->normalizarIdsEnsayosPermitidos($datos['ensayos_permitidos'] ?? []);
            if ($idsPermitidos === []) {
                $_SESSION['error'] = 'Los cambios fueron guardados, pero debe habilitar al menos un ensayo de la cotización para aprobar la Orden de Servicio.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/editar?id_os=' . $idOS);
                return;
            }
            if (!$osModelo->ensayosPertenecenACotizacion((int)$os['id_cotizacion'], $idsPermitidos)) {
                $_SESSION['error'] = 'Uno o más ensayos seleccionados no pertenecen a la cotización de esta Orden de Servicio.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/editar?id_os=' . $idOS);
                return;
            }

            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $osModelo->asegurarTablaEnsayosPermitidos();

            // Si la O/S corresponde a un servicio mensual con ensayos operativos incluidos desde el Anexo Mensual,
            // incluirlos automáticamente en la whitelist al aprobar la O/S para que no marquen "fuera de alcance"
            try {
                $stAnxMens = $db->prepare("
                    SELECT cd.id
                    FROM cotizacion_detalles cd
                    INNER JOIN cotizaciones c ON cd.id_cotizacion = c.id
                    WHERE cd.id_cotizacion = :id_cot
                      AND c.es_mensual = 1
                      AND (COALESCE(cd.es_incluido_anexo_mensual, 0) = 1 OR COALESCE(cd.observaciones, '') = 'Incluido en Servicio Mensual')
                ");
                $stAnxMens->execute(['id_cot' => (int)$os['id_cotizacion']]);
                foreach ($stAnxMens->fetchAll(\PDO::FETCH_COLUMN) as $idDetAnx) {
                    $idDetAnxInt = (int)$idDetAnx;
                    if ($idDetAnxInt > 0 && !in_array($idDetAnxInt, $idsPermitidos, true)) {
                        $idsPermitidos[] = $idDetAnxInt;
                    }
                }
            } catch (\Throwable $e) {
            }

            try {
                $db->beginTransaction();
                $stmtApr = $db->prepare("UPDATE ordenes_servicio SET estado = 'Aprobada', motivo_observacion = NULL WHERE id = :id");
                $stmtApr->execute(['id' => $idOS]);
                $osModelo->guardarEnsayosPermitidos($idOS, $idsPermitidos, (int)($_SESSION['usuario_id'] ?? 0));
                $db->commit();
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log('Error al aprobar O/S desde editar ' . $idOS . ': ' . $e->getMessage());
                $_SESSION['error'] = 'Los cambios se guardaron, pero ocurrió un error al aprobar la Orden de Servicio.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/editar?id_os=' . $idOS);
                return;
            }

            $listaIds = implode(', ', $idsPermitidos);
            registrarEventoOS(
                'ordenes_servicio',
                'aprobar',
                'Orden de Servicio ' . $os['codigo_os'] . ' revisada, actualizada y aprobada formalmente por ' . ($_SESSION['usuario_nombre'] ?? 'Supervisor') . '. Ensayos habilitados (IDs): ' . $listaIds,
                $idOS,
                ['datos' => ['ensayos_permitidos' => $idsPermitidos, 'origen' => 'editar_y_aprobar']]
            );
            $_SESSION['exito'] = 'Orden de Servicio ' . ($os['codigo_os'] ?? '') . ' guardada y aprobada con éxito. Ya puede registrar Hojas de Solicitud CYCSA-RT-FM-13.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        if ($actualizado) {
            if ($requiereReaprobacion) {
                $osPost = $osModelo->obtenerPorId($idOS);
                $nuevaVer = (int)($osPost['version'] ?? ((int)($os['version'] ?? 1) + 1));
                registrarEventoOS(
                    'ordenes_servicio',
                    'actualizar',
                    "O/S actualizada a v{$nuevaVer}; requiere re-aprobación del supervisor. Motivo: {$motivoCambioVersion}",
                    $idOS,
                    [
                        'datos' => [
                            'estado_anterior' => $estadoActual,
                            'estado_nuevo' => $nuevoEstado,
                            'version_anterior' => (int)($os['version'] ?? 1),
                            'version_nueva' => $nuevaVer,
                            'motivo_cambio_version' => $motivoCambioVersion,
                        ],
                    ]
                );
                $_SESSION['exito'] = 'Orden de Servicio ' . $os['codigo_os'] . " actualizada a versión v{$nuevaVer}. Al estar aprobada previamente, pasó a Revisión y requiere re-aprobación del supervisor.";
            } else {
                registrarEventoOS(
                    'ordenes_servicio',
                    'actualizar',
                    'Orden de Servicio ' . $os['codigo_os'] . ' actualizada exitosamente',
                    $idOS
                );
                $_SESSION['exito'] = 'Orden de Servicio ' . $os['codigo_os'] . ' actualizada exitosamente.';
            }
        } else {
            $_SESSION['error'] = 'Error al actualizar la Orden de Servicio.';
        }

        $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/detalle?id=' . $idOS);
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
                registrarEventoOS('ordenes_servicio', 'finalizar_muestreo', "Muestreo finalizado (retorno al lab) y guardado para la Orden de Servicio ID: {$idOS}", $idOS);
                $_SESSION['exito'] = 'Muestreo en campo finalizado con éxito. El técnico retornó con los especímenes al laboratorio. Abriendo Hoja de Servicio (CYCSA-RT-FM-13)...';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio?id_os=' . $idOS . '&abrir_hoja=1&prefijo=MC');
                return;
            } else {
                registrarEventoOS('ordenes_servicio', 'programar_muestreo', "Programación de muestreo y checklist guardados para la Orden de Servicio ID: {$idOS}", $idOS);
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
            registrarEventoOS('ordenes_servicio', 'finalizar_muestreo', "Muestreo finalizado para la Orden de Servicio ID: {$idOS}", $idOS);
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
     * Marcar requerimiento de muestreo en campo vía Ajax
     */
    public function marcarRequiereMuestreoAjax(Peticion $peticion, Respuesta $respuesta): void {
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
        $exito = $osModelo->marcarRequiereMuestreo($idOS);
        $respuesta->enviarJson(['status' => $exito ? 'success' : 'error']);
    }

    /**
     * Imprimir Formato Oficial CYCSA-RT-FM-40 B (Lista de Chequeo para Muestreos de Compactación)
     */
    public function imprimirListaChequeo(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        $idOS = (int)($_GET['id'] ?? 0);
        $idHoja = (int)($_GET['id_hoja'] ?? 0);
        $osModelo = new OrdenServicioModelo();
        $os = $osModelo->obtenerPorId($idOS, $idHoja);

        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        require __DIR__ . '/../Vistas/imprimir_checklist.php';
        exit;
    }

    /**
     * Carga vía AJAX el documento CYCSA-RT-FM-40 B para crearlo/editarlo/cerrarlo
     * directamente en Órdenes de Servicio y asociarlo a su RT-FM-13.
     */
    public function fm40bDatosAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        $idOS = (int)($_GET['id_os'] ?? 0);
        $idHoja = (int)($_GET['id_hoja'] ?? 0);
        if ($idOS <= 0) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Orden de Servicio inválida.'], 400);
            return;
        }

        $osModelo = new OrdenServicioModelo();
        $os = $osModelo->obtenerPorId($idOS, $idHoja);
        if (!$os) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Orden de Servicio no encontrada.'], 404);
            return;
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $stmtH = $db->prepare("
            SELECT hs.id, hs.numero_registro, hs.codigo_documento,
                   COALESCE(cd.descripcion_ensayo, p.nombre_comercial, 'Ensayo') AS descripcion_ensayo
            FROM hojas_solicitud hs
            LEFT JOIN cotizacion_detalles cd ON hs.id_detalle_cotizacion = cd.id
            LEFT JOIN productos p ON COALESCE(hs.id_producto, cd.id_producto) = p.id
            WHERE hs.id_os = :id_os
            ORDER BY hs.id ASC
        ");
        $stmtH->execute(['id_os' => $idOS]);
        $hojas = $stmtH->fetchAll(\PDO::FETCH_ASSOC);

        // Mapear estado 40 B existente por hoja
        $pmPorHoja = [];
        try {
            $stmtPms = $db->prepare("SELECT * FROM programacion_muestreo WHERE id_orden_servicio = :id_os ORDER BY id ASC");
            $stmtPms->execute(['id_os' => $idOS]);
            foreach ($stmtPms->fetchAll(\PDO::FETCH_ASSOC) as $pmRow) {
                $hid = (int)($pmRow['id_hoja_solicitud'] ?? 0);
                $pmPorHoja[$hid] = $pmRow;
            }
        } catch (\Throwable $e) {
            $pmPorHoja = [];
        }

        foreach ($hojas as &$h) {
            $hid = (int)$h['id'];
            $h['fm40b_id'] = !empty($pmPorHoja[$hid]['id']) ? (int)$pmPorHoja[$hid]['id'] : 0;
            $h['fm40b_estado'] = $pmPorHoja[$hid]['estado_muestreo'] ?? '';
        }
        unset($h);

        $pmSeleccionado = ($idHoja > 0 && isset($pmPorHoja[$idHoja]))
            ? $pmPorHoja[$idHoja]
            : ($idHoja === 0 && isset($pmPorHoja[0]) ? $pmPorHoja[0] : null);

        $chk = [];
        if ($pmSeleccionado && !empty($pmSeleccionado['checklist_json'])) {
            $dec = json_decode((string)$pmSeleccionado['checklist_json'], true);
            if (is_array($dec)) {
                $chk = $dec;
            }
        }

        $respuesta->enviarJson([
            'status' => 'success',
            'os' => [
                'id' => (int)$os['id'],
                'codigo_os' => $os['codigo_os'],
                'cliente_nombre' => $os['cliente_nombre'] ?? '',
                'nombre_proyecto' => $os['nombre_proyecto'] ?? '',
                'solo_lectura' => in_array((string)($os['estado'] ?? ''), ['Finalizado', 'Archivado', 'Cerrado'], true),
            ],
            'id_hoja_seleccionada' => $idHoja,
            'hojas' => $hojas,
            'fm40b' => $pmSeleccionado ? [
                'id' => (int)$pmSeleccionado['id'],
                'id_hoja_solicitud' => (int)($pmSeleccionado['id_hoja_solicitud'] ?? 0),
                'fecha_ida' => !empty($pmSeleccionado['fecha_ida']) ? date('Y-m-d\TH:i', strtotime($pmSeleccionado['fecha_ida'])) : date('Y-m-d\TH:00'),
                'fecha_llegada' => !empty($pmSeleccionado['fecha_llegada']) ? date('Y-m-d\TH:i', strtotime($pmSeleccionado['fecha_llegada'])) : date('Y-m-d\TH:00', strtotime('+4 hours')),
                'id_tecnico' => (int)($pmSeleccionado['id_tecnico'] ?? 0),
                'id_vehiculo' => (int)($pmSeleccionado['id_vehiculo'] ?? 0),
                'lugar_muestreo' => $pmSeleccionado['lugar_muestreo'] ?? ($os['nombre_proyecto'] ?? ''),
                'cantidad_muestras_est' => $pmSeleccionado['cantidad_muestras_est'] ?? '',
                'num_muestreadores' => (int)($pmSeleccionado['num_muestreadores'] ?? 1),
                'observaciones_campo' => $pmSeleccionado['observaciones_campo'] ?? '',
                'estado_muestreo' => $pmSeleccionado['estado_muestreo'] ?? 'Programado',
                'checklist' => $chk,
            ] : [
                'id' => 0,
                'id_hoja_solicitud' => $idHoja,
                'fecha_ida' => date('Y-m-d\TH:00'),
                'fecha_llegada' => date('Y-m-d\TH:00', strtotime('+4 hours')),
                'id_tecnico' => 0,
                'id_vehiculo' => 0,
                'lugar_muestreo' => $os['nombre_proyecto'] ?? '',
                'cantidad_muestras_est' => '',
                'num_muestreadores' => 1,
                'observaciones_campo' => '',
                'estado_muestreo' => 'Programado',
                'checklist' => [],
            ],
            'tecnicos' => $osModelo->obtenerTecnicos(),
            'vehiculos' => $osModelo->obtenerVehiculos(),
        ]);
    }

    /**
     * Guarda o cierra ("Finalizado") un documento CYCSA-RT-FM-40 B desde Órdenes de Servicio
     * asociado a su Hoja RT-FM-13 correspondiente.
     */
    public function guardarFm40bAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('ordenes_servicio', 'crear_editar')) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'No tiene permisos para gestionar documentos FM-40 B.'], 403);
            return;
        }
        if (!$peticion->esPost()) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Método no permitido.'], 405);
            return;
        }

        $datos = $peticion->obtenerDatos();
        $csrfToken = trim((string)($datos['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')));
        if ($csrfToken === '' || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrfToken)) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido o sesión expirada.'], 403);
            return;
        }

        $idOS = (int)($datos['id_os'] ?? 0);
        $idHojaSolicitud = (int)($datos['id_hoja_solicitud'] ?? 0);
        $idTecnico = (int)($datos['id_tecnico'] ?? 0);
        $idVehiculo = (int)($datos['id_vehiculo'] ?? 0);
        if ($idOS <= 0) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Orden de Servicio inválida.'], 400);
            return;
        }
        if ($idTecnico <= 0 || $idVehiculo <= 0) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Debe seleccionar el técnico responsable y el vehículo asignado para el documento 40 B.'], 422);
            return;
        }

        $osModelo = new OrdenServicioModelo();
        $os = $osModelo->obtenerPorId($idOS);
        if (!$os) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Orden de Servicio no encontrada.'], 404);
            return;
        }

        $accionMuestreo = trim((string)($datos['accion_muestreo'] ?? 'guardar'));
        $estadoMuestreo = ($accionMuestreo === 'finalizar') ? 'Finalizado' : (($accionMuestreo === 'programar') ? 'Programado' : 'En Campo');

        $chkRaw = $datos['chk'] ?? [];
        if (is_string($chkRaw)) {
            $decChk = json_decode($chkRaw, true);
            $chkRaw = is_array($decChk) ? $decChk : [];
        }
        $checklistJson = !empty($chkRaw) && is_array($chkRaw) ? json_encode($chkRaw, JSON_UNESCAPED_UNICODE) : null;

        $exito = $osModelo->guardarProgramacionMuestreo($idOS, [
            'id_hoja_solicitud' => $idHojaSolicitud > 0 ? $idHojaSolicitud : null,
            'fecha_ida' => $datos['fecha_ida'] ?? date('Y-m-d H:i:s'),
            'fecha_llegada' => $datos['fecha_llegada'] ?? date('Y-m-d H:i:s', strtotime('+4 hours')),
            'id_tecnico' => $idTecnico,
            'id_vehiculo' => $idVehiculo,
            'lugar_muestreo' => trim((string)($datos['lugar_muestreo'] ?? '')),
            'cantidad_muestras_est' => trim((string)($datos['cantidad_muestras_est'] ?? '')),
            'num_muestreadores' => max(1, (int)($datos['num_muestreadores'] ?? 1)),
            'observaciones_campo' => trim((string)($datos['observaciones_campo'] ?? '')),
            'checklist_json' => $checklistJson,
            'estado_muestreo' => $estadoMuestreo,
        ]);

        if (!$exito) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'No fue posible guardar el documento CYCSA-RT-FM-40 B.'], 500);
            return;
        }

        $sufijoHoja = $idHojaSolicitud > 0 ? " asociado a Hoja RT-FM-13 #{$idHojaSolicitud}" : "";
        registrarEventoOS(
            'ordenes_servicio',
            $estadoMuestreo === 'Finalizado' ? 'cerrar_fm40b' : 'guardar_fm40b',
            "Documento CYCSA-RT-FM-40 B ({$estadoMuestreo}){$sufijoHoja} guardado en O/S #{$idOS}",
            $idOS,
            ['entidad_tipo' => 'fm40b', 'entidad_id' => $idHojaSolicitud ?: $idOS]
        );

        $imprimirUrl = '/Cycsa/publico/ordenes-servicio/imprimir-checklist?id=' . $idOS . ($idHojaSolicitud > 0 ? '&id_hoja=' . $idHojaSolicitud : '');
        $respuesta->enviarJson([
            'status' => 'success',
            'message' => ($estadoMuestreo === 'Finalizado')
                ? 'Documento CYCSA-RT-FM-40 B cerrado y finalizado correctamente.'
                : 'Documento CYCSA-RT-FM-40 B guardado correctamente.',
            'estado_muestreo' => $estadoMuestreo,
            'id_os' => $idOS,
            'id_hoja_solicitud' => $idHojaSolicitud,
            'imprimir_url' => $imprimirUrl,
        ]);
    }

    /**
     * Devuelve los datos actuales de período mensual / contrato largo de una O/S.
     */
    public function periodoContratoDatosAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('ordenes_servicio', 'ver')) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'No tiene permisos para ver esta Orden de Servicio.'], 403);
            return;
        }

        $idOS = (int)($_GET['id_os'] ?? ($_GET['id'] ?? 0));
        if ($idOS <= 0) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'ID de Orden de Servicio inválido.'], 400);
            return;
        }

        $osModelo = new OrdenServicioModelo();
        $os = $osModelo->obtenerPorId($idOS);
        if (!$os) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Orden de Servicio no encontrada.'], 404);
            return;
        }

        require_once dirname(__DIR__, 3) . '/Helpers/funciones.php';
        $vigencia = function_exists('calcularVigenciaContratoOS') ? calcularVigenciaContratoOS($os) : [];
        $esAprobada = function_exists('esOrdenServicioAprobada') ? esOrdenServicioAprobada((string)($os['estado'] ?? '')) : false;

        $respuesta->enviarJson([
            'status' => 'success',
            'os' => [
                'id' => (int)$os['id'],
                'codigo_os' => $os['codigo_os'],
                'cliente_nombre' => $os['cliente_nombre'] ?? '',
                'nombre_proyecto' => $os['nombre_proyecto'] ?? '',
                'estado' => $os['estado'] ?? '',
                'es_aprobada' => $esAprobada,
                'solo_lectura' => in_array((string)($os['estado'] ?? ''), ['Finalizado', 'Archivado', 'Cerrado'], true),
                'tipo_contrato' => $os['tipo_contrato'] ?? 'Puntual',
                'es_multimes' => !empty($os['es_multimes']) ? 1 : 0,
                'duracion_meses' => $vigencia['duracion_meses'] ?? (!empty($os['duracion_meses']) ? (int)$os['duracion_meses'] : 3),
                'fecha_inicio' => $os['fecha_inicio'] ?: ($os['fecha_emision'] ?: date('Y-m-d')),
                'fecha_fin' => $vigencia['fecha_fin'] ?? ($os['fecha_fin'] ?: date('Y-m-d', strtotime('+3 months'))),
                'periodicidad' => $os['periodicidad'] ?: 'Mensual',
            ],
            'vigencia' => $vigencia,
        ]);
    }

    /**
     * Configura o actualiza el período mensual / contrato largo de una O/S
     * (permite hacerlo incluso cuando ya está aceptada, sin exigir re-aprobación).
     */
    public function guardarPeriodoContratoAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('ordenes_servicio', 'crear_editar')) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'No tiene permisos para configurar el período de Órdenes de Servicio.'], 403);
            return;
        }
        if (!$peticion->esPost()) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Método no permitido.'], 405);
            return;
        }

        $datos = $peticion->obtenerDatos();
        $csrfToken = trim((string)($datos['csrf_token'] ?? ($_SERVER['HTTP_X_CSRF_TOKEN'] ?? '')));
        if ($csrfToken === '' || empty($_SESSION['csrf_token']) || !hash_equals((string)$_SESSION['csrf_token'], $csrfToken)) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido o sesión expirada.'], 403);
            return;
        }

        $idOS = (int)($datos['id_os'] ?? 0);
        if ($idOS <= 0) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Orden de Servicio inválida.'], 400);
            return;
        }

        $osModelo = new OrdenServicioModelo();
        $os = $osModelo->obtenerPorId($idOS);
        if (!$os) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Orden de Servicio no encontrada.'], 404);
            return;
        }
        if (in_array((string)($os['estado'] ?? ''), ['Finalizado', 'Archivado', 'Cerrado'], true)) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'La Orden de Servicio está cerrada o archivada y no admite cambios.'], 422);
            return;
        }

        $esMultimes = !empty($datos['es_multimes']) && ($datos['es_multimes'] === '1' || $datos['es_multimes'] === 1 || $datos['es_multimes'] === 'on' || $datos['es_multimes'] === true);
        $fechaInicio = !empty($datos['fecha_inicio']) ? trim((string)$datos['fecha_inicio']) : date('Y-m-d');
        $fechaFin = !empty($datos['fecha_fin']) ? trim((string)$datos['fecha_fin']) : null;
        $duracionMeses = !empty($datos['duracion_meses']) ? max(1, (int)$datos['duracion_meses']) : null;

        if ($esMultimes && $fechaFin !== null && $fechaInicio !== '' && strtotime($fechaFin) < strtotime($fechaInicio)) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'La fecha de finalización no puede ser anterior a la fecha de inicio.'], 422);
            return;
        }

        $ok = $osModelo->actualizarPeriodoContrato($idOS, [
            'es_multimes' => $esMultimes ? 1 : 0,
            'tipo_contrato' => $esMultimes ? 'Marco' : 'Puntual',
            'duracion_meses' => $duracionMeses,
            'fecha_inicio' => $fechaInicio,
            'fecha_fin' => $fechaFin,
            'periodicidad' => trim((string)($datos['periodicidad'] ?? 'Mensual')) ?: 'Mensual',
        ]);

        if (!$ok) {
            $respuesta->enviarJson(['status' => 'error', 'message' => $osModelo->obtenerUltimoError() ?: 'No se pudo actualizar el período del contrato.'], 500);
            return;
        }

        $osActualizada = $osModelo->obtenerPorId($idOS);
        $vigencia = $osActualizada['vigencia_contrato'] ?? [];

        registrarEventoOS(
            'ordenes_servicio',
            'periodo_contrato',
            $esMultimes
                ? "Período de contrato largo configurado: {$vigencia['etiqueta_corta']} (estado conservado: {$os['estado']})"
                : "Modalidad cambiada a Puntual (estado conservado: {$os['estado']})",
            $idOS,
            ['datos' => $vigencia]
        );

        $respuesta->enviarJson([
            'status' => 'success',
            'message' => $esMultimes
                ? 'Período mensual de contrato largo guardado correctamente sin alterar la aprobación de la O/S.'
                : 'Orden de Servicio configurada como Puntual sin alterar su aprobación.',
            'estado_conservado' => $osActualizada['estado'] ?? $os['estado'],
            'vigencia' => $vigencia,
        ]);
    }

    /**
     * Vista de detalle del documento CYCSA-RG-FM-39 V1R1
     */
    public function detalle(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        $idOS = (int)($_GET['id'] ?? 0);
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        if ($idOS > 0) {
            try {
                ValidacionOrdenServicioServicio::evaluarYActualizarEjecucionAutomatica($db, $idOS);
            } catch (\Throwable $e) {
            }
        }

        $osModelo = new OrdenServicioModelo();
        $os = $osModelo->obtenerPorId($idOS);

        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $uIdSes = (int)($_SESSION['usuario_id'] ?? 0);
        $rIdSes = (int)($_SESSION['usuario_rol'] ?? 0);
        $historialValidaciones = ValidacionOrdenServicioServicio::obtenerHistorial($db, $idOS);
        $usuariosActivos = [];
        if (ValidacionOrdenServicioServicio::puedeCorregirValidacion($db, $uIdSes, $rIdSes)) {
            try {
                $usuariosActivos = $db->query("SELECT id, nombre FROM usuarios WHERE activo = 1 ORDER BY nombre ASC")->fetchAll(\PDO::FETCH_ASSOC) ?: [];
            } catch (\Throwable $e) {
                $usuariosActivos = [];
            }
        }

        $mostrarBitacora = puedeVerBitacoraOS();
        $this->renderizar('OrdenesServicio/Vistas/detalle', [
            'titulo' => 'Orden de Servicio ' . $os['codigo_os'] . ' (CYCSA-RG-FM-39 V1R1)',
            'os' => $os,
            'mostrar_bitacora' => $mostrarBitacora,
            'bitacora_logs' => $mostrarBitacora ? obtenerBitacoraOS($idOS, 200) : [],
            'bitacora_modulo_nombre' => 'Orden de Servicio ' . ($os['codigo_os'] ?? ''),
            'bitacora_limite' => 200,
            'bitacora_abierta' => false,
            'historial_validaciones' => $historialValidaciones,
            'puede_autorizar_admin' => ValidacionOrdenServicioServicio::puedeAutorizarAdmin($db, $uIdSes, $rIdSes),
            'puede_recibir_lab' => ValidacionOrdenServicioServicio::puedeRecibirLaboratorioORecepcion($db, $uIdSes, $rIdSes),
            'puede_recibir_admin_final' => ValidacionOrdenServicioServicio::puedeRecibirAdminFinal($db, $uIdSes, $rIdSes),
            'puede_corregir_validacion' => ValidacionOrdenServicioServicio::puedeCorregirValidacion($db, $uIdSes, $rIdSes),
            'usuarios_activos_os' => $usuariosActivos,
            'csrfToken' => (string)($_SESSION['csrf_token'] ?? ''),
        ]);
    }

    /**
     * PDF oficial CYCSA-RG-FM-39 V1R1. Mismo HTML que la vista de detalle / impresión.
     */
    public function descargarPdf(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        $idOS = (int)($_GET['id'] ?? 0);
        if ($idOS > 0) {
            try {
                $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                ValidacionOrdenServicioServicio::evaluarYActualizarEjecucionAutomatica($db, $idOS);
            } catch (\Throwable $e) {
            }
        }
        $osModelo = new OrdenServicioModelo();
        $os = $osModelo->obtenerPorId($idOS);

        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $pdf = DocumentoRgFm39Servicio::pdf($os);
        $nombre = preg_replace('/[^A-Za-z0-9._-]+/', '_', (string)($os['codigo_os'] ?? 'orden'));
        $nombre = trim((string)$nombre, '._-');
        if ($nombre === '') {
            $nombre = 'orden-servicio';
        }
        $nombre .= '-RG-FM-39-V1R1.pdf';

        if (!headers_sent()) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . $nombre . '"');
        }
        echo $pdf;
        if (!defined('PHPUNIT_RUNNING')) {
            exit;
        }
    }

    public function autorizarAdmin(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $datos = $peticion->obtenerDatos();
        $idOS = (int)($datos['id_os'] ?? 0);
        if (!isset($datos['csrf_token']) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$datos['csrf_token'])) {
            $_SESSION['error'] = 'Token de seguridad inválido.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/detalle?id=' . $idOS);
            return;
        }
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $res = ValidacionOrdenServicioServicio::registrarAutorizacionAdmin(
            $db,
            $idOS,
            (int)($_SESSION['usuario_id'] ?? 0),
            (int)($_SESSION['usuario_rol'] ?? 0),
            (string)($_SESSION['usuario_nombre'] ?? '')
        );
        if ($res['ok']) {
            registrarEventoOS('ordenes_servicio', 'autorizar_admin', $res['mensaje'], $idOS);
            $_SESSION['exito'] = $res['mensaje'];
        } else {
            $_SESSION['error'] = $res['mensaje'];
        }
        $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/detalle?id=' . $idOS);
    }

    public function recibirLaboratorio(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $datos = $peticion->obtenerDatos();
        $idOS = (int)($datos['id_os'] ?? 0);
        if (!isset($datos['csrf_token']) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$datos['csrf_token'])) {
            $_SESSION['error'] = 'Token de seguridad inválido.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/detalle?id=' . $idOS);
            return;
        }
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $res = ValidacionOrdenServicioServicio::registrarRecepcionLab(
            $db,
            $idOS,
            (int)($_SESSION['usuario_id'] ?? 0),
            (int)($_SESSION['usuario_rol'] ?? 0),
            (string)($_SESSION['usuario_nombre'] ?? '')
        );
        if ($res['ok']) {
            registrarEventoOS('ordenes_servicio', 'recibir_laboratorio', $res['mensaje'], $idOS);
            $_SESSION['exito'] = $res['mensaje'];
        } else {
            $_SESSION['error'] = $res['mensaje'];
        }
        $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/detalle?id=' . $idOS);
    }

    public function recibirAdminFinal(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $datos = $peticion->obtenerDatos();
        $idOS = (int)($datos['id_os'] ?? 0);
        if (!isset($datos['csrf_token']) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$datos['csrf_token'])) {
            $_SESSION['error'] = 'Token de seguridad inválido.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/detalle?id=' . $idOS);
            return;
        }
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $res = ValidacionOrdenServicioServicio::registrarRecepcionAdminFinal(
            $db,
            $idOS,
            (int)($_SESSION['usuario_id'] ?? 0),
            (int)($_SESSION['usuario_rol'] ?? 0),
            (string)($_SESSION['usuario_nombre'] ?? '')
        );
        if ($res['ok']) {
            registrarEventoOS('ordenes_servicio', 'recibir_admin_final', $res['mensaje'], $idOS);
            $_SESSION['exito'] = $res['mensaje'];
        } else {
            $_SESSION['error'] = $res['mensaje'];
        }
        $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/detalle?id=' . $idOS);
    }

    public function corregirValidacionAutorizada(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $datos = $peticion->obtenerDatos();
        $idOS = (int)($datos['id_os'] ?? 0);
        if (!isset($datos['csrf_token']) || !hash_equals((string)($_SESSION['csrf_token'] ?? ''), (string)$datos['csrf_token'])) {
            $_SESSION['error'] = 'Token de seguridad inválido.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/detalle?id=' . $idOS);
            return;
        }
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $res = ValidacionOrdenServicioServicio::corregirValidacionAutorizada(
            $db,
            $idOS,
            trim((string)($datos['campo_validacion'] ?? '')),
            trim((string)($datos['motivo'] ?? '')),
            (int)($_SESSION['usuario_id'] ?? 0),
            (int)($_SESSION['usuario_rol'] ?? 0),
            !empty($datos['nuevo_usuario_id']) ? (int)$datos['nuevo_usuario_id'] : null
        );
        if ($res['ok']) {
            registrarEventoOS('ordenes_servicio', 'correccion_validacion', $res['mensaje'], $idOS);
            $_SESSION['exito'] = $res['mensaje'];
        } else {
            $_SESSION['error'] = $res['mensaje'];
        }
        $respuesta->redirigir('/Cycsa/publico/ordenes-servicio/detalle?id=' . $idOS);
    }

    /**
     * Aprueba formalmente la Orden de Servicio para autorizar la emisión de Hojas RT-FM-13
     */
    public function aprobarOS(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        // Rol supervisor (3) o administrador (1) o con permiso explícito
        $rol = (int)($_SESSION['usuario_rol'] ?? 0);
        if (!in_array($rol, [1, 3]) && !tienePermiso('ordenes_servicio', 'aprobar')) {
            $_SESSION['error'] = 'No tiene permisos de supervisor para aprobar la Orden de Servicio.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idOS = (int)($datos['id_os'] ?? 0);

            // Validar CSRF
            $csrfToken = $datos['csrf_token'] ?? '';
            if (empty($csrfToken) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
                $_SESSION['error'] = 'Token CSRF inválido o sesión expirada.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            if ($idOS <= 0) {
                $_SESSION['error'] = 'Orden de Servicio inválida.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            $opModelo = new \Cycsa\Modulos\Operaciones\Modelos\OperacionModelo();
            $os = $opModelo->obtenerOSPorId($idOS);
            if (!$os) {
                $_SESSION['error'] = 'Orden de Servicio no encontrada.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            if (in_array($os['estado'] ?? '', ['Finalizado', 'Archivado', 'Cerrado'], true)) {
                $_SESSION['error'] = 'La Orden de Servicio está cerrada o archivada y no admite modificaciones.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            $idsPermitidos = $this->normalizarIdsEnsayosPermitidos($datos['ensayos_permitidos'] ?? []);
            if ($idsPermitidos === []) {
                $_SESSION['error'] = 'Debe habilitar al menos un ensayo de la cotización para aprobar la Orden de Servicio.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            $osModelo = new OrdenServicioModelo();
            if (!$osModelo->ensayosPertenecenACotizacion((int)$os['id_cotizacion'], $idsPermitidos)) {
                $_SESSION['error'] = 'Uno o más ensayos no pertenecen a la cotización de esta Orden de Servicio.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $osModelo->asegurarTablaEnsayosPermitidos();

            // Si la O/S corresponde a un servicio mensual con ensayos operativos incluidos desde el Anexo Mensual,
            // incluirlos automáticamente en la whitelist al aprobar la O/S para que no marquen "fuera de alcance"
            try {
                $stAnxMens = $db->prepare("
                    SELECT cd.id
                    FROM cotizacion_detalles cd
                    INNER JOIN cotizaciones c ON cd.id_cotizacion = c.id
                    WHERE cd.id_cotizacion = :id_cot
                      AND c.es_mensual = 1
                      AND (COALESCE(cd.es_incluido_anexo_mensual, 0) = 1 OR COALESCE(cd.observaciones, '') = 'Incluido en Servicio Mensual')
                ");
                $stAnxMens->execute(['id_cot' => (int)$os['id_cotizacion']]);
                foreach ($stAnxMens->fetchAll(\PDO::FETCH_COLUMN) as $idDetAnx) {
                    $idDetAnxInt = (int)$idDetAnx;
                    if ($idDetAnxInt > 0 && !in_array($idDetAnxInt, $idsPermitidos, true)) {
                        $idsPermitidos[] = $idDetAnxInt;
                    }
                }
            } catch (\Throwable $e) {
            }

            try {
                $db->beginTransaction();
                $stmt = $db->prepare("UPDATE ordenes_servicio SET estado = 'Aprobada', motivo_observacion = NULL WHERE id = :id");
                $stmt->execute(['id' => $idOS]);
                $osModelo->guardarEnsayosPermitidos($idOS, $idsPermitidos, (int)($_SESSION['usuario_id'] ?? 0));
                $db->commit();
            } catch (\Throwable $e) {
                if ($db->inTransaction()) {
                    $db->rollBack();
                }
                error_log('Error al aprobar O/S ' . $idOS . ': ' . $e->getMessage());
                $_SESSION['error'] = 'Error al aprobar la Orden de Servicio.';
                $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
                return;
            }

            require_once dirname(__DIR__, 3) . '/Helpers/funciones.php';
            $codigoTexto = $os['codigo_os'] . (!empty($os['cliente_nombre']) ? ' (' . $os['cliente_nombre'] . ')' : '');
            $listaIds = implode(', ', $idsPermitidos);
            registrarEventoOS(
                'ordenes_servicio',
                'aprobar',
                'Orden de Servicio ' . $codigoTexto . ' aprobada formalmente por supervisor ' . ($_SESSION['usuario_nombre'] ?? 'Supervisor') . '. Ensayos habilitados (IDs): ' . $listaIds,
                $idOS,
                ['datos' => ['ensayos_permitidos' => $idsPermitidos]]
            );
            $_SESSION['exito'] = 'Orden de Servicio ' . ($os['codigo_os'] ?? '') . ' aprobada con éxito. Ensayos habilitados: ' . $listaIds . '. Ya puede registrar Hojas de Solicitud CYCSA-RT-FM-13.';

            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
        }
    }

    /**
     * Línea de tiempo de una O/S. Solo lectura.
     * Acceso: rol de sesión 1, 2 o 3, o tienePermiso('bitacora','ver').
     * GET no muta datos; CSRF no aplica. AuthMiddleware exige sesión.
     */
    public function bitacora(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        if (!puedeVerBitacoraOS()) {
            $_SESSION['error'] = 'No tiene permiso para consultar la bitácora de la Orden de Servicio.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $idOS = (int)($_GET['id'] ?? 0);
        $osModelo = new OrdenServicioModelo();
        $os = $osModelo->obtenerPorId($idOS);
        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/ordenes-servicio');
            return;
        }

        $filtros = [
            'usuario' => trim((string)($_GET['usuario'] ?? '')),
            'accion' => trim((string)($_GET['accion'] ?? '')),
            'entidad_tipo' => trim((string)($_GET['entidad_tipo'] ?? '')),
            'entidad_id' => (int)($_GET['entidad_id'] ?? 0),
            'desde' => trim((string)($_GET['desde'] ?? '')),
            'hasta' => trim((string)($_GET['hasta'] ?? '')),
        ];

        $this->renderizar('OrdenesServicio/Vistas/bitacora', [
            'titulo' => 'Bitácora ' . ($os['codigo_os'] ?? ''),
            'os' => $os,
            'filtros' => $filtros,
            'bitacora_logs' => obtenerBitacoraOS($idOS, 300, $filtros),
            'bitacora_modulo_nombre' => 'Orden de Servicio ' . ($os['codigo_os'] ?? ''),
            'bitacora_limite' => 300,
            'bitacora_abierta' => true,
        ]);
    }

    /**
     * @param mixed $valor
     * @return array<int>
     */
    private function normalizarIdsEnsayosPermitidos(mixed $valor): array {
        if (!is_array($valor)) {
            $valor = ($valor === null || $valor === '') ? [] : [$valor];
        }
        $ids = [];
        foreach ($valor as $id) {
            $id = (int)$id;
            if ($id > 0) {
                $ids[$id] = $id;
            }
        }
        $lista = array_values($ids);
        sort($lista, SORT_NUMERIC);
        return $lista;
    }
}
