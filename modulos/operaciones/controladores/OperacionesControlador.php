<?php

namespace Cycsa\Modulos\Operaciones\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Operaciones\Modelos\OperacionModelo;
use PDO;

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
     * Muestra el panel principal de Operaciones LIMS.
     */
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        if (($_SESSION['usuario_rol'] ?? 0) == 3) {
            $respuesta->redirigir('/Cycsa/publico/laboratorio');
            return;
        }

        $this->verificarPermiso($respuesta, 'ver');

        $modelo = new OperacionModelo();
        $busqueda = $_GET['q'] ?? '';

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        // Obtener cotizaciones aprobadas listas para generar O/S, y las O/S activas
        $cotizacionesParaOS = $modelo->obtenerCotizacionesParaOS($busqueda);
        $ordenesActivas = $modelo->obtenerOSActivas($busqueda);
        
        foreach ($ordenesActivas as &$o) {
            $o['items'] = $modelo->obtenerItemsOS((int)$o['id']);
        }
        
        $recepciones = $modelo->obtenerRecepciones($busqueda);

        $this->renderizar('operaciones/vistas/index', [
            'titulo' => 'Operaciones LIMS - Cycsa',
            'cotizaciones' => $cotizacionesParaOS,
            'ordenes' => $ordenesActivas,
            'recepciones' => $recepciones,
            'busqueda' => $busqueda,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Crea una Orden de Servicio (O/S) en base a una cotización.
     */
    public function crearOS(Peticion $peticion, Respuesta $respuesta): void {
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

            $idCotizacion = (int)($datos['id_cotizacion'] ?? 0);
            $tipoContrato = $datos['tipo_contrato'] ?? 'Puntual';
            $fechaM = $datos['fecha_muestreo'] ?? null;
            $horaM = $datos['hora_muestreo'] ?? null;
            $tecnicoM = $datos['tecnico_muestreo'] ?? null;
            $vehiculoM = $datos['vehiculo_muestreo'] ?? null;

            if ($idCotizacion <= 0) {
                $_SESSION['error'] = 'ID de cotización inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            $codigoOS = $modelo->crearOS($idCotizacion, $tipoContrato, $fechaM, $horaM, $tecnicoM, $vehiculoM);
            if ($codigoOS) {
                registrarBitacora('operaciones', 'crear_os', 'Orden de Servicio creada: ' . $codigoOS . ' con programación de muestreo.');
                $_SESSION['exito'] = "Orden de Servicio $codigoOS creada exitosamente.";
            } else {
                $_SESSION['error'] = 'Error al crear la Orden de Servicio.';
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones');
        }
    }

    /**
     * Muestra la pantalla para registrar la recepción de una muestra bajo una O/S.
     */
    public function recepcionForm(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        $idOS = (int)($_GET['id_os'] ?? 0);
        $idDetalle = (int)($_GET['id_detalle'] ?? 0);
        if ($idOS <= 0) {
            $_SESSION['error'] = 'Orden de Servicio inválida.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $modelo = new OperacionModelo();
        $os = $modelo->obtenerOSPorId($idOS);
        if (!$os) {
            $_SESSION['error'] = 'Orden de Servicio no encontrada.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        // Obtener los detalles de la cotización para saber qué servicios/ensayos de compresión se cobraron
        $servicios = $modelo->obtenerDetallesCotizacion((int)$os['id_cotizacion']);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('operaciones/vistas/recepcion', [
            'titulo' => 'Recepción de Muestras - LIMS',
            'os' => $os,
            'servicios' => $servicios,
            'idDetalle' => $idDetalle,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Guarda la recepción de la muestra y genera la Hoja de Solicitud física.
     */
    public function guardarRecepcion(Peticion $peticion, Respuesta $respuesta): void {
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

            if ($modelo->registrarRecepcion($datos)) {
                $_SESSION['exito'] = 'Recepción de muestras registrada exitosamente. Se ha generado la Hoja de Solicitud de Análisis Ciega para laboratorio.';
            } else {
                $_SESSION['error'] = 'Error al registrar la recepción de muestras.';
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones');
        }
    }

    /**
     * Muestra la vista de calendario operativo enfocado en el cronograma de rupturas.
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

        $fechaInicio = sprintf('%04d-%02d-01', $anio, $mes);
        $ultimoDia = date('t', strtotime($fechaInicio));
        $fechaFin = sprintf('%04d-%02d-%02d', $anio, $mes, $ultimoDia);

        // Obtener eventos del cronograma de rupturas
        $eventosRaw = $modelo->obtenerEventosCalendario($fechaInicio, $fechaFin);

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
            'titulo' => 'Calendario de Rupturas LIMS - Cycsa',
            'mes' => $mes,
            'anio' => $anio,
            'ultimoDia' => $ultimoDia,
            'eventosPorDia' => $eventosPorDia,
            'primerDiaSemana' => (int)date('w', strtotime($fechaInicio))
        ]);
    }

    /**
     * Muestra el detalle del lote y especímenes para cargar datos crudos y gestionar informes.
     */
    public function detalleLote(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        $idLote = (int)($_GET['id_lote'] ?? 0);
        
        if (($_SESSION['usuario_rol'] ?? 0) == 3) {
            $respuesta->redirigir('/Cycsa/publico/laboratorio/detalle-muestra?id_lote=' . $idLote);
            return;
        }

        $this->verificarPermiso($respuesta, 'ver');

        if ($idLote <= 0) {
            $_SESSION['error'] = 'Lote de muestra inválido.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        
        // Obtener datos del lote, recepción, cliente y O/S
        // El Técnico de Laboratorio NO debe ver la información del cliente.
        // Aplicamos la política ciega de visibilidad.
        $esTecnico = ($_SESSION['usuario_rol'] ?? 0) == 3; // Suponiendo rol 3 = Técnico

        $sqlLote = "SELECT lm.*, rm.codigo_muestra, rm.codigo_campo, rm.fecha_recepcion, os.codigo_os, os.id_cotizacion,
                           cot.nombre_proyecto, cot.direccion_proyecto, cot.atencion_a,
                           cli.nombre_razon_social AS cliente_nombre, cli.identificacion AS cliente_ruc
                    FROM lotes_muestras lm
                    JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                    JOIN ordenes_servicio os ON rm.id_os = os.id
                    JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                    JOIN clientes cli ON cot.id_cliente = cli.id
                    WHERE lm.id = :id_lote";
        
        $stmt = $db->prepare($sqlLote);
        $stmt->execute(['id_lote' => $idLote]);
        $lote = $stmt->fetch(\PDO::FETCH_ASSOC);

        if (!$lote) {
            $_SESSION['error'] = 'Lote de muestra no encontrado.';
            $respuesta->redirigir('/Cycsa/publico/operaciones');
            return;
        }

        $modelo = new OperacionModelo();
        $especimenes = $modelo->obtenerDetallesLote($idLote);
        $historialInformes = $modelo->obtenerHistorialInformes($idLote);

        // Cargar todos los ensayos cotizados de esta O/S para poder capturar sus matrices
        $stmtItems = $db->prepare("SELECT cd.*, fe.archivo_markdown, fe.nombre AS formato_nombre
                                   FROM cotizacion_detalles cd
                                   LEFT JOIN productos p ON cd.id_producto = p.id
                                   LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                                   WHERE cd.id_cotizacion = :id_cot");
        $stmtItems->execute(['id_cot' => $lote['id_cotizacion']]);
        $itemsOS = $stmtItems->fetchAll(\PDO::FETCH_ASSOC);

        // Cargar el JSON del esquema de los formatos
        $schemaPath = __DIR__ . '/../../../datos_ensayos_markdown/formatos_schema.json';
        $formatosSchemaJson = file_exists($schemaPath) ? file_get_contents($schemaPath) : '{}';

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('operaciones/vistas/detalle_lote', [
            'titulo' => 'Detalle del Lote - LIMS',
            'lote' => $lote,
            'especimenes' => $especimenes,
            'historial' => $historialInformes,
            'esTecnico' => $esTecnico,
            'itemsOS' => $itemsOS,
            'formatosSchemaJson' => $formatosSchemaJson,
            'exito' => $_SESSION['exito'] ?? null,
            'error' => $_SESSION['error'] ?? null
        ]);

        unset($_SESSION['exito'], $_SESSION['error']);
    }

    /**
     * Carga el resultado de ensaye (Ruptura física).
     */
    public function guardarRuptura(Peticion $peticion, Respuesta $respuesta): void {
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

            $idEnsayo = (int)($datos['id_ensayo'] ?? 0);
            $idLote = (int)($datos['id_lote'] ?? 0);

            if ($idEnsayo <= 0) {
                $_SESSION['error'] = 'ID de ensayo inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            if ($modelo->guardarResultadoRuptura($idEnsayo, $datos)) {
                $_SESSION['exito'] = 'Resultado de ensaye cargado correctamente. Se ejecutó la validación contra la norma estándar.';
            } else {
                $_SESSION['error'] = 'Error al registrar el resultado de ensaye.';
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones/detalle-lote?id_lote=' . $idLote);
        }
    }

    /**
     * Genera e imprime un informe PDF (Parcial o Consolidado) y lo versiona.
     */
    public function generarInforme(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idLote = (int)($datos['id_lote'] ?? 0);
            $idDetalle = (int)($datos['id_detalle'] ?? 0);
            $tipoInforme = $datos['tipo_informe'] ?? 'Parcial';
            $motivoReemplazo = trim($datos['motivo_reemplazo'] ?? '');

            if ($idLote <= 0 || $idDetalle <= 0) {
                $_SESSION['error'] = 'Datos inválidos para generar informe.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones/detalle-lote?id_lote=' . $idLote);
                return;
            }

            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            
            // Obtener datos del detalle de cotización
            $stmtDet = $db->prepare("SELECT cd.*, p.nombre_comercial, fe.archivo_markdown, fe.nombre AS formato_nombre
                                     FROM cotizacion_detalles cd
                                     LEFT JOIN productos p ON cd.id_producto = p.id
                                     LEFT JOIN formatos_ensayos fe ON p.formato_id = fe.id
                                     WHERE cd.id = :id");
            $stmtDet->execute(['id' => $idDetalle]);
            $detalle = $stmtDet->fetch(PDO::FETCH_ASSOC);

            if (!$detalle) {
                $_SESSION['error'] = 'Detalle de ensayo no encontrado.';
                $respuesta->redirigir('/Cycsa/publico/operaciones/detalle-lote?id_lote=' . $idLote);
                return;
            }

            // Obtener datos de la cotización
            $stmtCot = $db->prepare("SELECT c.*, cli.nombre_razon_social AS cliente_nombre, cli.identificacion AS cliente_ruc, cli.telefono AS cliente_telefono
                                     FROM cotizaciones c
                                     JOIN clientes cli ON c.id_cliente = cli.id
                                     WHERE c.id = :id");
            $stmtCot->execute(['id' => $detalle['id_cotizacion']]);
            $cotizacion = $stmtCot->fetch(PDO::FETCH_ASSOC);

            // Generar PDF usando el helper existente
            $columnas = $this->obtenerColumnasFormato($detalle['archivo_markdown']);
            if (empty($columnas)) {
                $columnas = ["Código laboratorio", "Nombre muestra", "Resultado"];
            }
            $filas = json_decode($detalle['resultados_json'] ?? '', true) ?: [];

            // Generación real del PDF
            require_once __DIR__ . '/../../../ayudantes/funciones.php';
            $pdfContenido = generarReporteEnsayoPDF($cotizacion, $detalle, $columnas, $filas);

            // Versionado
            $stmtVer = $db->prepare("SELECT MAX(version) FROM informes_control WHERE id_lote = :id_lote AND tipo_informe = :tipo_informe");
            $stmtVer->execute(['id_lote' => $idLote, 'tipo_informe' => $tipoInforme]);
            $maxVersion = $stmtVer->fetchColumn();
            
            $version = ($maxVersion === null) ? 0 : (int)$maxVersion + 1;
            $anio = date('Y');

            // Determinar código base
            if ($version > 0) {
                $stmtBase = $db->prepare("SELECT codigo_informe FROM informes_control WHERE id_lote = :id_lote AND tipo_informe = :tipo_informe ORDER BY version ASC LIMIT 1");
                $stmtBase->execute(['id_lote' => $idLote, 'tipo_informe' => $tipoInforme]);
                $codigoInforme = $stmtBase->fetchColumn();
            } else {
                $stmtSec = $db->prepare("SELECT COUNT(DISTINCT codigo_informe) FROM informes_control WHERE YEAR(fecha_generacion) = :anio");
                $stmtSec->execute(['anio' => $anio]);
                $consecutivo = (int)$stmtSec->fetchColumn() + 1;
                $codigoInforme = sprintf("INF-%d-%04d", $anio, $consecutivo);
            }

            $codigoCompleto = sprintf("%s-%02d", $codigoInforme, $version);

            // Guardar archivo PDF en disco
            $rutaCarpeta = __DIR__ . '/../../../almacenamiento/informes';
            if (!file_exists($rutaCarpeta)) {
                mkdir($rutaCarpeta, 0777, true);
            }
            $nombrePdf = $codigoCompleto . ".pdf";
            $rutaPdfFisica = $rutaCarpeta . '/' . $nombrePdf;
            file_put_contents($rutaPdfFisica, $pdfContenido);

            // Guardar registro en base de datos
            $modelo = new OperacionModelo();
            $rutaRelativa = 'almacenamiento/informes/' . $nombrePdf;
            $exitoId = $modelo->registrarInforme($idLote, $codigoInforme, $version, $codigoCompleto, $tipoInforme, $motivoReemplazo, $rutaRelativa);

            if ($exitoId) {
                $_SESSION['exito'] = "Informe $codigoCompleto generado y versionado correctamente en PDF.";
            } else {
                $_SESSION['error'] = 'Error al registrar el informe generado.';
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones/detalle-lote?id_lote=' . $idLote);
        }
    }

    /**
     * Permite a Coordinación revisar o aprobar un informe.
     */
    public function cambiarEstadoInforme(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $this->verificarPermiso($respuesta, 'crear_editar');

        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $idInforme = (int)($datos['id_informe'] ?? 0);
            $idLote = (int)($datos['id_lote'] ?? 0);
            $nuevoEstado = $datos['nuevo_estado'] ?? '';

            if ($idInforme <= 0 || $idLote <= 0 || !in_array($nuevoEstado, ['Revisado', 'Aprobado', 'Rechazado'])) {
                $_SESSION['error'] = 'Parámetros inválidos para cambiar estado de informe.';
                $respuesta->redirigir('/Cycsa/publico/operaciones');
                return;
            }

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $_SESSION['error'] = 'Token CSRF inválido.';
                $respuesta->redirigir('/Cycsa/publico/operaciones/detalle-lote?id_lote=' . $idLote);
                return;
            }

            $modelo = new OperacionModelo();
            $exito = $modelo->cambiarEstadoInforme($idInforme, $nuevoEstado, $_SESSION['usuario_id']);

            if ($exito) {
                $_SESSION['exito'] = "El informe ha sido marcado como '$nuevoEstado' exitosamente.";
            } else {
                $_SESSION['error'] = 'Error al actualizar el estado de aprobación del informe.';
            }

            $respuesta->redirigir('/Cycsa/publico/operaciones/detalle-lote?id_lote=' . $idLote);
        }
    }

    public function descargarInforme(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        
        $id = (int)($_GET['id'] ?? 0);
        if ($id <= 0) {
            die("ID de informe no válido.");
        }

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $stmt = $db->prepare("SELECT ic.*, cot.condicion_pago, cot.codigo AS cot_codigo
                              FROM informes_control ic
                              JOIN lotes_muestras lm ON ic.id_lote = lm.id
                              JOIN recepcion_muestras rm ON lm.id_recepcion = rm.id
                              JOIN ordenes_servicio os ON rm.id_os = os.id
                              JOIN cotizaciones cot ON os.id_cotizacion = cot.id
                              WHERE ic.id = :id");
        $stmt->execute(['id' => $id]);
        $informe = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$informe) {
            die("Informe no encontrado.");
        }

        // Restricción de pago: Si el cliente es de CONTADO y tiene saldo pendiente en cuentas_por_cobrar
        if ($informe['condicion_pago'] === 'Contado') {
            $stmtCxC = $db->prepare("SELECT estado FROM cuentas_por_cobrar WHERE factura_numero = :codigo");
            $stmtCxC->execute(['codigo' => $informe['cot_codigo']]);
            $cxcEstado = $stmtCxC->fetchColumn();
            
            // Si tiene registro de cobro y no está Pagado, bloquear la descarga del PDF
            if ($cxcEstado && $cxcEstado !== 'Pagado') {
                $_SESSION['error'] = "Descarga denegada: Este cliente paga de Contado y posee saldo pendiente de pago.";
                $respuesta->redirigir('/Cycsa/publico/operaciones/detalle-lote?id_lote=' . $informe['id_lote']);
                return;
            }
        }

        // Servir el archivo PDF
        $rutaPdf = __DIR__ . '/../../../' . $informe['ruta_archivo_pdf'];
        if (file_exists($rutaPdf)) {
            header('Content-Type: application/pdf');
            header('Content-Disposition: inline; filename="' . basename($rutaPdf) . '"');
            readfile($rutaPdf);
            exit;
        } else {
            die("El archivo PDF físico del informe no se encuentra en el servidor.");
        }
    }

    private function obtenerColumnasFormato(?string $archivo_markdown): array {
        if (empty($archivo_markdown)) return [];
        $rutaJson = __DIR__ . '/../../../datos_ensayos_markdown/formatos_schema.json';
        if (file_exists($rutaJson)) {
            $data = json_decode(file_get_contents($rutaJson), true);
            return $data[$archivo_markdown]['columns'] ?? [];
        }
        return [];
    }
}
