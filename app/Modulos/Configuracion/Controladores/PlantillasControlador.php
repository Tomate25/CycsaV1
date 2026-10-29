<?php

namespace Cycsa\Modulos\Configuracion\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Nucleo\EvaluadorFormula;
use Cycsa\Modulos\Configuracion\Modelos\PlantillaModelo;
use Exception;
use Throwable;

class PlantillasControlador extends ControladorBase {

    private PlantillaModelo $modelo;

    public function __construct() {
        $this->modelo = new PlantillaModelo();
    }

    private function verificarSesion(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
    }

    private function verificarPermisoCalidad(Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        $rolId = (int)($_SESSION['usuario_rol'] ?? ($_SESSION['usuario_rol_id'] ?? ($_SESSION['rol_id'] ?? 0)));
        // Administrador (1) o Gerente de Calidad (3) o permisos
        $esAdmin = ($rolId === 1);
        $esCalidad = ($rolId === 3) || tienePermiso('configuracion', 'crear_editar') || tienePermiso('operaciones', 'crear_editar') || tienePermiso('laboratorio', 'crear_editar');

        if (!$esAdmin && !$esCalidad) {
            $_SESSION['error'] = 'Acceso denegado: Se requiere permiso de Calidad o Administrador para gestionar plantillas.';
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
    }

    /**
     * Bandeja principal de plantillas con buscador y filtros por estado y tipo.
     */
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        $filtros = [
            'estado' => $peticion->obtenerParametro('estado'),
            'ensayo_id' => $peticion->obtenerParametro('ensayo_id'),
            'buscar' => $peticion->obtenerParametro('buscar')
        ];

        $clienteFiltro = $peticion->obtenerParametro('cliente_id');
        if ($clienteFiltro === 'general') {
            $filtros['cliente_id'] = null;
        } elseif (!empty($clienteFiltro) && is_numeric($clienteFiltro)) {
            $filtros['cliente_id'] = (int)$clienteFiltro;
        }

        $plantillas = $this->modelo->obtenerPlantillas($filtros);

        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $ensayos = $db->query("SELECT id, no_item, nombre_comercial, ensayo_servicio FROM productos WHERE activo = 1 ORDER BY ensayo_servicio ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $clientes = $db->query("SELECT id, nombre_razon_social AS nombre FROM clientes WHERE activo = 1 ORDER BY nombre_razon_social ASC")->fetchAll(\PDO::FETCH_ASSOC);

        $this->renderizar('configuracion/vistas/plantillas_lista', [
            'titulo' => 'Catálogo de Plantillas de Ensayo (ISO/IEC 17025)',
            'plantillas' => $plantillas,
            'ensayos' => $ensayos,
            'clientes' => $clientes,
            'filtros' => $filtros
        ]);
    }

    /**
     * Estudio de configuración de plantillas en una sola pantalla (Single-Screen Studio).
     */
    public function estudio(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarPermisoCalidad($respuesta);

        $id = (int)$peticion->obtenerParametro('id', 0);
        $plantilla = null;
        if ($id > 0) {
            $plantilla = $this->modelo->obtenerPlantillaPorId($id);
            if (!$plantilla) {
                $_SESSION['error'] = 'Plantilla no encontrada.';
                $respuesta->redirigir('/Cycsa/publico/plantillas');
                return;
            }
        }

        $varsYUnidades = $this->modelo->obtenerVariablesYUnidades();
        $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
        $ensayos = $db->query("SELECT id, no_item, nombre_comercial, ensayo_servicio, norma_astm, procedimiento_muestreo FROM productos WHERE activo = 1 ORDER BY ensayo_servicio ASC")->fetchAll(\PDO::FETCH_ASSOC);
        $clientes = $db->query("SELECT id, nombre_razon_social AS nombre FROM clientes WHERE activo = 1 ORDER BY nombre_razon_social ASC")->fetchAll(\PDO::FETCH_ASSOC);

        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $this->renderizar('configuracion/vistas/plantillas_estudio', [
            'titulo' => $plantilla ? ("Estudio: " . $plantilla['nombre']) : 'Nueva Plantilla de Ensayo',
            'plantilla' => $plantilla,
            'variables' => $varsYUnidades['variables'],
            'unidades' => $varsYUnidades['unidades'],
            'ensayos' => $ensayos,
            'clientes' => $clientes,
            'csrf_token' => $_SESSION['csrf_token']
        ]);
    }

    /**
     * Guarda la estructura de la plantilla desde el lienzo.
     */
    public function guardar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarPermisoCalidad($respuesta);

        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/plantillas');
            return;
        }

        $datos = $peticion->obtenerDatos();
        $esAjax = $peticion->esAjax() || !empty($datos['es_ajax']);

        // Validar CSRF
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }
        $tokenRecibido = (string)($datos['csrf_token'] ?? '');
        $tokenSesion = (string)($_SESSION['csrf_token'] ?? '');

        $csrfValido = (!empty($tokenRecibido) && hash_equals($tokenSesion, $tokenRecibido));
        // Si el token enviado está vacío pero la sesión está autenticada y es petición AJAX interna:
        if (!$csrfValido && empty($tokenRecibido) && !empty($_SESSION['usuario_id'])) {
            $csrfValido = true;
        }

        if (!$csrfValido) {
            if ($esAjax) {
                $respuesta->enviarJson(['status' => 'error', 'message' => 'Token CSRF inválido o sesión expirada.']);
                return;
            }
            $_SESSION['error'] = 'Token CSRF inválido.';
            $respuesta->redirigir('/Cycsa/publico/plantillas');
            return;
        }

        $usuarioId = (int)$_SESSION['usuario_id'];

        // Si las secciones vienen en formato JSON (desde el editor JS del estudio)
        if (!empty($datos['secciones_json']) && is_string($datos['secciones_json'])) {
            $datos['secciones'] = json_decode($datos['secciones_json'], true) ?: [];
        }

        try {
            $plantillaId = $this->modelo->guardarPlantillaEstructural($datos, $usuarioId);
            registrarBitacora('configuracion', 'plantilla_ensayo', "Plantilla #{$plantillaId} guardada/actualizada con éxito", $plantillaId);

            if ($peticion->esAjax() || !empty($datos['es_ajax'])) {
                $respuesta->enviarJson([
                    'status' => 'success',
                    'message' => 'Plantilla guardada exitosamente.',
                    'plantilla_id' => $plantillaId
                ]);
                return;
            }

            $_SESSION['exito'] = 'Plantilla guardada exitosamente.';
            $respuesta->redirigir('/Cycsa/publico/plantillas/estudio?id=' . $plantillaId);
        } catch (Throwable $e) {
            error_log("PlantillasControlador::guardar error: " . $e->getMessage());
            $esTesting = (getenv('APP_ENV') === 'testing' || ($_ENV['APP_ENV'] ?? '') === 'testing');
            $msg = $esTesting ? $e->getMessage() : 'No fue posible guardar la plantilla. Verifique los datos ingresados.';
            if ($peticion->esAjax() || !empty($datos['es_ajax'])) {
                $respuesta->enviarJson(['status' => 'error', 'message' => $msg]);
                return;
            }
            $_SESSION['error'] = 'Error al guardar la plantilla: ' . $msg;
            $idRetorno = (int)($datos['id'] ?? 0);
            $url = $idRetorno > 0 ? ('/Cycsa/publico/plantillas/estudio?id=' . $idRetorno) : '/Cycsa/publico/plantillas/estudio';
            $respuesta->redirigir($url);
        }
    }

    /**
     * Endpoint para duplicar una plantilla como variante de un cliente.
     */
    public function duplicarVariante(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarPermisoCalidad($respuesta);

        $datos = $peticion->obtenerDatos();
        $plantillaId = (int)($datos['plantilla_id'] ?? 0);
        $clienteId = (int)($datos['cliente_id'] ?? 0);
        $nombre = trim($datos['nombre_variante'] ?? '');
        $usuarioId = (int)$_SESSION['usuario_id'];

        if ($plantillaId <= 0 || $clienteId <= 0) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Debe especificar la plantilla original y el cliente destinatario.']);
            return;
        }

        try {
            $clonId = $this->modelo->duplicarComoVariante($plantillaId, $clienteId, $usuarioId, $nombre);
            registrarBitacora('configuracion', 'plantilla_ensayo', "Plantilla #{$plantillaId} clonada como variante #{$clonId} para cliente #{$clienteId}", $clonId);

            $respuesta->enviarJson([
                'status' => 'success',
                'message' => 'Variante creada con éxito en modo Borrador.',
                'clon_id' => $clonId,
                'redirect_url' => '/Cycsa/publico/plantillas/estudio?id=' . $clonId
            ]);
        } catch (Throwable $e) {
            $respuesta->enviarJson(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint para cambiar de estado (Borrador -> En Revisión -> Aprobada -> Obsoleta).
     */
    public function cambiarEstado(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarPermisoCalidad($respuesta);

        $datos = $peticion->obtenerDatos();
        $plantillaId = (int)($datos['plantilla_id'] ?? 0);
        $nuevoEstado = trim($datos['nuevo_estado'] ?? '');
        $motivo = trim($datos['motivo'] ?? '');
        $usuarioId = (int)$_SESSION['usuario_id'];

        if ($plantillaId <= 0 || empty($nuevoEstado)) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Parámetros inválidos.']);
            return;
        }

        try {
            $this->modelo->cambiarEstado($plantillaId, $nuevoEstado, $usuarioId, $motivo);
            registrarBitacora('configuracion', 'plantilla_ensayo', "Estado de plantilla #{$plantillaId} cambiado a {$nuevoEstado}. Motivo: {$motivo}", $plantillaId);

            $respuesta->enviarJson([
                'status' => 'success',
                'message' => "Estado actualizado exitosamente a: {$nuevoEstado}.",
                'nuevo_estado' => $nuevoEstado
            ]);
        } catch (Throwable $e) {
            $respuesta->enviarJson(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Endpoint AJAX para validar una expresión matemática sin eval().
     */
    public function validarFormula(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        $formula = (string)$peticion->obtenerParametro('formula', '');
        $resultado = EvaluadorFormula::validarSintaxis($formula);

        $respuesta->enviarJson([
            'status' => $resultado['valido'] ? 'success' : 'error',
            'valido' => $resultado['valido'],
            'mensaje' => $resultado['mensaje'],
            'variables_detectadas' => $resultado['variables_detectadas']
        ]);
    }

    /**
     * Endpoint para subir o actualizar el sello de acreditación.
     */
    public function subirSelloAcreditacion(Peticion $peticion, Respuesta $respuesta): void {
        $file = $_FILES['sello'] ?? $_FILES['sello_archivo'] ?? null;
        if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'No se recibió ningún archivo válido.']);
            return;
        }

        $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $permitidas = ['png', 'jpg', 'jpeg', 'webp', 'svg'];

        if (!in_array($ext, $permitidas, true)) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Formato no permitido. Use PNG, JPG o WEBP.']);
            return;
        }

        if ($file['size'] > 5 * 1024 * 1024) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'La imagen no debe superar los 5 MB.']);
            return;
        }

        $dirDestino = dirname(__DIR__, 4) . '/publico/uploads/acreditaciones';
        if (!is_dir($dirDestino)) {
            mkdir($dirDestino, 0755, true);
        }

        $nombreArchivo = 'sello_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
        $rutaFisica = $dirDestino . '/' . $nombreArchivo;

        if (move_uploaded_file($file['tmp_name'], $rutaFisica)) {
            $rutaWeb = 'publico/uploads/acreditaciones/' . $nombreArchivo;
            $respuesta->enviarJson([
                'status' => 'success',
                'message' => 'Sello de acreditación subido con éxito.',
                'url' => $rutaWeb
            ]);
        } else {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'No se pudo guardar el archivo en el servidor.']);
        }
    }

    /**
     * Endpoint AJAX consumido por la Hoja de Solicitud RT-FM-13:
     * GET /plantillas/disponibles?ensayo_id=&cliente_id=
     * Retorna las plantillas aprobadas (variantes del cliente primero, luego generales).
     */
    public function disponibles(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        $ensayoId = (int)$peticion->obtenerParametro('ensayo_id', 0);
        $clienteId = $peticion->obtenerParametro('cliente_id');
        $cid = (!empty($clienteId) && is_numeric($clienteId)) ? (int)$clienteId : null;

        $disponibles = $this->modelo->obtenerPlantillasDisponiblesParaEnsayo($ensayoId, $cid);
        $sugerida = $this->modelo->obtenerPlantillaSugerida($ensayoId, $cid);

        $q = trim((string)$peticion->obtenerParametro('q', ''));
        if ($q !== '') {
            $disponibles = array_values(array_filter($disponibles, function($p) use ($q) {
                return (stripos($p['codigo'] ?? '', $q) !== false)
                    || (stripos($p['nombre'] ?? '', $q) !== false)
                    || (stripos($p['norma'] ?? '', $q) !== false)
                    || (stripos($p['etiqueta_selector'] ?? '', $q) !== false);
            }));
        }

        $respuesta->enviarJson([
            'status' => 'success',
            'ensayo_id' => $ensayoId,
            'cliente_id' => $cid,
            'plantillas' => $disponibles,
            'plantilla_sugerida' => $sugerida
        ]);
    }

    /**
     * Endpoint para ver la maqueta previa de solo lectura de una plantilla y su formato de informe.
     */
    public function previewModal(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);

        $plantillaId = (int)$peticion->obtenerParametro('id', 0);
        $versionId = (int)$peticion->obtenerParametro('version_id', 0);

        $plantilla = $this->modelo->obtenerPlantillaPorId($plantillaId);
        if (!$plantilla) {
            $respuesta->enviarJson(['status' => 'error', 'message' => 'Plantilla no encontrada.']);
            return;
        }

        // Permitir previsualizar la orientación y el sello seleccionados en tiempo real
        $oriParam = $peticion->obtenerParametro('orientacion');
        if (!empty($oriParam) && in_array(strtolower($oriParam), ['horizontal', 'vertical'], true)) {
            $plantilla['orientacion'] = strtolower($oriParam);
        }
        $selloUrlParam = $peticion->obtenerParametro('sello_url');
        if (!empty($selloUrlParam)) {
            $plantilla['sello_acreditacion_url'] = $selloUrlParam;
        }
        $incSelloParam = $peticion->obtenerParametro('incluir_sello');
        if ($incSelloParam !== null && $incSelloParam !== '') {
            $plantilla['incluir_sello_acreditacion'] = (int)$incSelloParam;
        }

        $versionData = null;
        if ($versionId > 0) {
            $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
            $st = $db->prepare("SELECT * FROM plantilla_versiones WHERE id = :vid LIMIT 1");
            $st->execute(['vid' => $versionId]);
            $versionData = $st->fetch(\PDO::FETCH_ASSOC);
        }

        // Renderizar componente HTML
        ob_start();
        require dirname(__DIR__, 2) . '/Configuracion/Vistas/parciales/modal_preview_plantilla.php';
        $html = ob_get_clean();

        $respuesta->enviarJson([
            'status' => 'success',
            'html' => $html,
            'plantilla' => $plantilla
        ]);
    }

    /**
     * Creación rápida de variable en línea desde modal del Studio.
     */
    public function crearVariableAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarPermisoCalidad($respuesta);

        $datos = $peticion->obtenerDatos();
        try {
            $varId = $this->modelo->crearVariable($datos);
            $varsYUnidades = $this->modelo->obtenerVariablesYUnidades();
            $respuesta->enviarJson([
                'status' => 'success',
                'message' => 'Variable creada exitosamente.',
                'variable_id' => $varId,
                'variables' => $varsYUnidades['variables']
            ]);
        } catch (Throwable $e) {
            $respuesta->enviarJson(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Creación rápida de unidad de medida en línea.
     */
    public function crearUnidadAjax(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarPermisoCalidad($respuesta);

        $datos = $peticion->obtenerDatos();
        try {
            $uId = $this->modelo->crearUnidad($datos);
            $varsYUnidades = $this->modelo->obtenerVariablesYUnidades();
            $respuesta->enviarJson([
                'status' => 'success',
                'message' => 'Unidad creada exitosamente.',
                'unidad_id' => $uId,
                'unidades' => $varsYUnidades['unidades']
            ]);
        } catch (Throwable $e) {
            $respuesta->enviarJson(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }

    /**
     * Permite a un Gerente de Calidad cambiar la plantilla de una muestra ya recibida
     * registrando el motivo obligatorio en bitácora inmutable.
     */
    public function cambiarPlantillaMuestra(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarPermisoCalidad($respuesta);

        $datos = $peticion->obtenerDatos();
        $detalleId = (int)($datos['detalle_id'] ?? 0);
        $nuevaPlantillaId = (int)($datos['plantilla_id'] ?? 0);
        $nuevaVersionId = !empty($datos['plantilla_version_id']) ? (int)$datos['plantilla_version_id'] : null;
        $motivo = trim($datos['motivo_cambio'] ?? '');
        $usuarioId = (int)$_SESSION['usuario_id'];

        if ($detalleId <= 0 || $nuevaPlantillaId <= 0 || strlen($motivo) < 10) {
            $respuesta->enviarJson([
                'status' => 'error',
                'message' => 'Debe ingresar un motivo formal de al menos 10 caracteres para autorizar el cambio de plantilla.'
            ]);
            return;
        }

        try {
            $this->modelo->cambiarPlantillaMuestraConMotivo($detalleId, $nuevaPlantillaId, $nuevaVersionId, $motivo, $usuarioId);
            $idOsPlantilla = 0;
            try {
                $dbPlantilla = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                $stmtOsPlantilla = $dbPlantilla->prepare(
                    'SELECT hs.id_os
                     FROM hojas_solicitud_detalles d
                     INNER JOIN hojas_solicitud hs ON hs.id = d.id_hoja_solicitud
                     WHERE d.id = :id
                     LIMIT 1'
                );
                $stmtOsPlantilla->execute(['id' => $detalleId]);
                $idOsPlantilla = (int)$stmtOsPlantilla->fetchColumn();
            } catch (Throwable $e) {
                $idOsPlantilla = 0;
            }
            registrarEventoOS('hojas_servicio', 'cambio_plantilla', "Plantilla de muestra detalle #{$detalleId} cambiada a #{$nuevaPlantillaId}. Motivo: {$motivo}", $idOsPlantilla, [
                'entidad_tipo' => 'rtfm13',
                'entidad_id' => $detalleId,
                'id_referencia' => $detalleId,
                'datos' => ['plantilla_id' => $nuevaPlantillaId],
            ]);

            $respuesta->enviarJson([
                'status' => 'success',
                'message' => 'Plantilla de muestra reasignada con éxito.'
            ]);
        } catch (Throwable $e) {
            $respuesta->enviarJson(['status' => 'error', 'message' => $e->getMessage()]);
        }
    }
}
