<?php

namespace Cycsa\Modulos\Productos\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Productos\Modelos\ProductoModelo;
use Cycsa\Modulos\Productos\Servicios\ImportadorProductosCsv;

class ProductosControlador extends ControladorBase {
    
    // 🛡️ Verificar sesión activa
    private function verificarSesion(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit;
        }
    }

    // 🔍 INDEX CON BUSQUEDA Y FILTRADO POR CATEGORÍA
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('productos', 'ver')) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
        
        if (empty($_SESSION['csrf_token'])) {
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
        }

        $modelo = new ProductoModelo();
        $busqueda = $_GET['q'] ?? '';
        $categoria = $_GET['cat'] ?? '';

        $bitacora_logs = obtenerBitacoraModulo('productos');

        $this->renderizar('productos/vistas/index', [
            'titulo' => 'Catálogo de Ensayos y Servicios - Cycsa',
            'productos' => $modelo->obtenerTodos($busqueda, $categoria),
            'categorias' => $modelo->obtenerCategorias(),
            'busqueda' => $busqueda,
            'categoria_actual' => $categoria,
            'bitacora_logs' => $bitacora_logs,
            'bitacora_abierta' => !empty($_GET['historial'])
        ]);
    }

    // ➕ FORMULARIO CREAR
    public function crear(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('productos', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/productos');
            exit;
        }
        if (empty($_SESSION['csrf_token'])) { 
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
        }

        $modelo = new ProductoModelo();
        $this->renderizar('productos/vistas/crear', [
            'titulo' => 'Registrar Nuevo Ensayo / Servicio - Cycsa',
            'categorias' => $modelo->obtenerCategorias(),
            'formatos' => $modelo->obtenerFormatos()
        ]);
    }

    // 💾 GUARDAR NUEVO PRODUCTO
    public function guardar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('productos', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/productos');
            exit;
        }
        
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new ProductoModelo();

            // CSRF
            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $this->renderizar('productos/vistas/crear', [
                    'titulo' => 'Registrar Nuevo Ensayo / Servicio', 
                    'error' => 'Error: Token CSRF inválido.', 
                    'valores' => $datos,
                    'categorias' => $modelo->obtenerCategorias(),
                    'formatos' => $modelo->obtenerFormatos()
                ]); 
                return;
            }

            // Validar campos requeridos
            if (empty(trim($datos['ensayo_servicio']))) {
                $this->renderizar('productos/vistas/crear', [
                    'titulo' => 'Registrar Nuevo Ensayo / Servicio', 
                    'error' => 'La descripción o nombre del ensayo/servicio es obligatorio.', 
                    'valores' => $datos,
                    'categorias' => $modelo->obtenerCategorias(),
                    'formatos' => $modelo->obtenerFormatos()
                ]); 
                return;
            }

            // Validar precio
            if (!isset($datos['precio']) || $datos['precio'] === '' || floatval($datos['precio']) < 0) {
                $this->renderizar('productos/vistas/crear', [
                    'titulo' => 'Registrar Nuevo Ensayo / Servicio', 
                    'error' => 'El precio debe ser un número mayor o igual a 0.', 
                    'valores' => $datos,
                    'categorias' => $modelo->obtenerCategorias(),
                    'formatos' => $modelo->obtenerFormatos()
                ]); 
                return;
            }

            if ($modelo->guardar($datos)) {
                $db = \Cycsa\Nucleo\Conexion::obtenerInstancia();
                $lastId = $db->lastInsertId();
                registrarBitacora('productos', 'crear', 'Creado producto/servicio: ' . $datos['nombre_comercial'] . ' (' . ($datos['codigo_servicio'] ?? 'S/C') . ')', $lastId);
                $respuesta->redirigir('/Cycsa/publico/productos');
                return;
            } else {
                $this->renderizar('productos/vistas/crear', [
                    'titulo' => 'Registrar Nuevo Ensayo / Servicio', 
                    'error' => 'Error al intentar guardar el registro en la base de datos.', 
                    'valores' => $datos,
                    'categorias' => $modelo->obtenerCategorias(),
                    'formatos' => $modelo->obtenerFormatos()
                ]); 
                return;
            }
        }
    }

    // ✏️ MOSTRAR FORMULARIO DE EDICIÓN
    public function editar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('productos', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/productos');
            exit;
        }
        
        $id = decodificarId($_GET['id'] ?? '');
        if (!$id) { 
            $respuesta->redirigir('/Cycsa/publico/productos'); 
            return; 
        }

        $modelo = new ProductoModelo();
        $producto = $modelo->obtenerPorId((int)$id);

        if (!$producto) { 
            $respuesta->redirigir('/Cycsa/publico/productos'); 
            return; 
        }
        
        if (empty($_SESSION['csrf_token'])) { 
            $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); 
        }

        $this->renderizar('productos/vistas/editar', [
            'titulo' => 'Editar Ensayo / Servicio - Cycsa',
            'producto' => $producto,
            'categorias' => $modelo->obtenerCategorias(),
            'formatos' => $modelo->obtenerFormatos()
        ]);
    }

    // ✏️ GUARDAR EDICIÓN
    public function actualizar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('productos', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/productos');
            exit;
        }
        
        $id = decodificarId($_GET['id'] ?? '');
        if (!$id || !$peticion->esPost()) { 
            $respuesta->redirigir('/Cycsa/publico/productos'); 
            return; 
        }

        $datos = $peticion->obtenerDatos();
        $modelo = new ProductoModelo();

        // CSRF
        if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $this->renderizar('productos/vistas/editar', [
                'titulo' => 'Editar Ensayo / Servicio', 
                'error' => 'Error: Token CSRF inválido.', 
                'producto' => array_merge($datos, ['id' => $id]),
                'categorias' => $modelo->obtenerCategorias(),
                'formatos' => $modelo->obtenerFormatos()
            ]); 
            return;
        }

        // Validar campos requeridos
        if (empty(trim($datos['ensayo_servicio']))) {
            $this->renderizar('productos/vistas/editar', [
                'titulo' => 'Editar Ensayo / Servicio', 
                'error' => 'La descripción o nombre del ensayo/servicio es obligatorio.', 
                'producto' => array_merge($datos, ['id' => $id]),
                'categorias' => $modelo->obtenerCategorias(),
                'formatos' => $modelo->obtenerFormatos()
            ]); 
            return;
        }

        // Validar precio
        if (!isset($datos['precio']) || $datos['precio'] === '' || floatval($datos['precio']) < 0) {
            $this->renderizar('productos/vistas/editar', [
                'titulo' => 'Editar Ensayo / Servicio', 
                'error' => 'El precio debe ser un número mayor o igual a 0.', 
                'producto' => array_merge($datos, ['id' => $id]),
                'categorias' => $modelo->obtenerCategorias(),
                'formatos' => $modelo->obtenerFormatos()
            ]); 
            return;
        }

        if ($modelo->actualizar((int)$id, $datos)) {
            registrarBitacora('productos', 'editar', 'Actualizado producto/servicio: ' . $datos['nombre_comercial'] . ' (' . ($datos['codigo_servicio'] ?? 'S/C') . ')', (int)$id);
            $respuesta->redirigir('/Cycsa/publico/productos');
            return;
        } else {
            $this->renderizar('productos/vistas/editar', [
                'titulo' => 'Editar Ensayo / Servicio', 
                'error' => 'Error al intentar actualizar el registro en la base de datos.', 
                'producto' => array_merge($datos, ['id' => $id]),
                'categorias' => $modelo->obtenerCategorias(),
                'formatos' => $modelo->obtenerFormatos()
            ]); 
            return;
        }
    }

    // 🗑️ DESACTIVAR PRODUCTO (SOFT DELETE / TOGGLE ACTIVO)
    public function eliminar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('productos', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/productos');
            exit;
        }
        
        if (!$peticion->esPost()) {
            $respuesta->redirigir('/Cycsa/publico/productos');
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? '';
        if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
            $_SESSION['productos_error'] = 'Token CSRF inválido o faltante.';
            $respuesta->redirigir('/Cycsa/publico/productos');
            return;
        }

        $id = decodificarId($_POST['id'] ?? $_GET['id'] ?? '');
        if ($id) {
            $modelo = new ProductoModelo();
            $producto = $modelo->obtenerPorId((int)$id);
            $modelo->desactivar((int)$id);
            if ($producto) {
                registrarBitacora('productos', 'desactivar', 'Desactivado producto/servicio: ' . $producto['nombre_comercial'] . ' (' . ($producto['codigo_servicio'] ?? 'S/C') . ')', (int)$id);
            }
        }
        
        $respuesta->redirigir('/Cycsa/publico/productos');
        return;
    }

    // 📥 DESCARGAR PLANTILLA OFICIAL CSV PARA CARGA MASIVA
    public function descargarPlantilla(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('productos', 'ver') && !tienePermiso('productos', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/productos');
            exit;
        }

        $delimitadorParam = $_GET['delimitador'] ?? $_GET['formato'] ?? 'punto_y_coma';
        $delimitador = ($delimitadorParam === 'coma') ? ',' : ';';

        $csv = ImportadorProductosCsv::generarPlantillaCsv($delimitador);
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="CYCSA_Plantilla_Productos_Oficial.csv"');
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Length: ' . strlen($csv));
        echo $csv;
        exit;
    }

    // 📊 EXPORTAR CATÁLOGO COMPLETO CON IDENTIFICADORES PARA EDICIÓN EN EXCEL
    public function exportarCatalogoCsv(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('productos', 'ver') && !tienePermiso('productos', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/productos');
            exit;
        }

        $delimitadorParam = $_GET['delimitador'] ?? $_GET['formato'] ?? 'punto_y_coma';
        $delimitador = ($delimitadorParam === 'coma') ? ',' : ';';

        $modelo = new ProductoModelo();
        $csv = ImportadorProductosCsv::generarCatalogoCompletoCsv($modelo, $delimitador);
        $fecha = date('Y-m-d');

        header('Content-Type: text/csv; charset=UTF-8');
        header("Content-Disposition: attachment; filename=\"CYCSA_Catalogo_Productos_Completo_{$fecha}.csv\"");
        header('Cache-Control: no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('Expires: 0');
        header('Content-Length: ' . strlen($csv));
        echo $csv;
        exit;
    }

    // 🔍 PREVISUALIZAR Y VALIDAR ARCHIVO CSV/EXCEL
    public function previsualizarCarga(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('productos', 'crear_editar')) {
            $respuesta->enviarJson(['exito' => false, 'error' => 'No tiene permisos para importar productos.'], 403);
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
            $respuesta->enviarJson(['exito' => false, 'error' => 'Token CSRF inválido o expirado.'], 403);
            return;
        }

        $contenido = '';
        $extension = 'csv';
        $nombreArchivo = 'Carga_CSV';
        if (isset($_FILES['archivo_csv']) && is_uploaded_file($_FILES['archivo_csv']['tmp_name'])) {
            $archivo = $_FILES['archivo_csv'];
            if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $respuesta->enviarJson(['exito' => false, 'error' => 'El archivo no pudo cargarse correctamente.'], 400);
                return;
            }
            if (($archivo['size'] ?? 0) > 10 * 1024 * 1024) {
                $respuesta->enviarJson(['exito' => false, 'error' => 'El archivo supera el límite permitido de 10 MB.'], 413);
                return;
            }
            $nombreArchivo = (string)($archivo['name'] ?? 'archivo.csv');
            $extension = strtolower(pathinfo($nombreArchivo, PATHINFO_EXTENSION));
            if (!in_array($extension, ['csv', 'xlsx'], true)) {
                $respuesta->enviarJson(['exito' => false, 'error' => 'Formato no permitido. Suba un archivo .csv o .xlsx de Excel.'], 415);
                return;
            }
            $contenido = (string)file_get_contents($archivo['tmp_name']);
        } elseif (!empty($_POST['contenido_csv'])) {
            $contenido = (string)$_POST['contenido_csv'];
        }

        if (empty(trim($contenido))) {
            $respuesta->enviarJson(['exito' => false, 'error' => 'No se recibió ningún archivo o el archivo está vacío.'], 400);
            return;
        }

        $modelo = new ProductoModelo();
        $resultado = ImportadorProductosCsv::parsearArchivoYValidar($contenido, $extension, $modelo);

        if ($resultado['exito'] && !empty($resultado['filas'])) {
            $tokenLote = bin2hex(random_bytes(16));
            $_SESSION['carga_masiva_token'] = $tokenLote;
            $_SESSION['carga_masiva_filas'] = $resultado['filas'];
            $_SESSION['carga_masiva_meta'] = [
                'archivo' => $nombreArchivo,
                'hoja' => $resultado['hoja_origen'] ?? null
            ];
            $resultado['token_lote'] = $tokenLote;
            $resultado['archivo'] = $nombreArchivo;
        }

        $respuesta->enviarJson($resultado);
    }

    // 💾 CONFIRMAR TRANSACCIÓN DE CARGA MASIVA (UPSERT)
    public function confirmarCarga(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('productos', 'crear_editar')) {
            $respuesta->enviarJson(['exito' => false, 'error' => 'No tiene permisos para importar productos.'], 403);
            return;
        }

        $csrfToken = $_POST['csrf_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
        if (empty($csrfToken) || !hash_equals($_SESSION['csrf_token'] ?? '', $csrfToken)) {
            $respuesta->enviarJson(['exito' => false, 'error' => 'Token CSRF inválido o expirado.'], 403);
            return;
        }

        $tokenLote = $_POST['token_lote'] ?? '';
        $filas = [];

        if (!empty($tokenLote) && isset($_SESSION['carga_masiva_token']) && hash_equals($_SESSION['carga_masiva_token'], $tokenLote)) {
            $filas = $_SESSION['carga_masiva_filas'] ?? [];
        }

        if (empty($filas)) {
            $respuesta->enviarJson(['exito' => false, 'error' => 'No hay filas en cola de carga o la sesión expiró. Por favor previsualice el archivo nuevamente.'], 400);
            return;
        }

        $meta = $_SESSION['carga_masiva_meta'] ?? ['archivo' => 'Archivo CSV/Excel', 'hoja' => null];
        $modelo = new ProductoModelo();
        $resultado = ImportadorProductosCsv::ejecutarImportacion($filas, $modelo);

        if ($resultado['exito']) {
            unset($_SESSION['carga_masiva_token'], $_SESSION['carga_masiva_filas'], $_SESSION['carga_masiva_meta']);
            $nombreArchivo = (string)($meta['archivo'] ?? 'Archivo CSV/Excel');
            $hojaSuffix = !empty($meta['hoja']) ? " ({$meta['hoja']})" : '';
            $desc = sprintf(
                "Carga masiva desde '%s'%s: %d agregado(s), %d modificado(s), %d sin cambios (%d total).",
                $nombreArchivo,
                $hojaSuffix,
                (int)$resultado['creados'],
                (int)$resultado['actualizados'],
                (int)($resultado['sin_cambios'] ?? 0),
                (int)$resultado['total']
            );
            registrarBitacora('productos', 'importacion_masiva', $desc, null, [
                'entidad_tipo' => 'carga_masiva_productos',
                'datos' => [
                    'archivo' => $nombreArchivo,
                    'hoja' => $meta['hoja'] ?? null,
                    'totales' => [
                        'total' => (int)$resultado['total'],
                        'creados' => (int)$resultado['creados'],
                        'actualizados' => (int)$resultado['actualizados'],
                        'sin_cambios' => (int)($resultado['sin_cambios'] ?? 0),
                        'desactivados' => 0
                    ],
                    'agregados' => $resultado['agregados_detalle'] ?? [],
                    'modificados' => $resultado['modificados_detalle'] ?? [],
                    'desactivados' => []
                ]
            ]);
        }

        $respuesta->enviarJson($resultado);
    }
}
