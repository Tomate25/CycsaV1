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
            'bitacora_logs' => $bitacora_logs
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

        $csv = ImportadorProductosCsv::generarPlantillaCsv();
        header('Content-Type: text/csv; charset=UTF-8');
        header('Content-Disposition: attachment; filename="CYCSA_Plantilla_Productos_Oficial.csv"');
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
        if (isset($_FILES['archivo_csv']) && is_uploaded_file($_FILES['archivo_csv']['tmp_name'])) {
            $archivo = $_FILES['archivo_csv'];
            if (($archivo['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
                $respuesta->enviarJson(['exito' => false, 'error' => 'El archivo no pudo cargarse correctamente.'], 400);
                return;
            }
            if (($archivo['size'] ?? 0) > 5 * 1024 * 1024) {
                $respuesta->enviarJson(['exito' => false, 'error' => 'El archivo supera el límite permitido de 5 MB.'], 413);
                return;
            }
            if (strtolower(pathinfo((string)($archivo['name'] ?? ''), PATHINFO_EXTENSION)) !== 'csv') {
                $respuesta->enviarJson(['exito' => false, 'error' => 'Formato no permitido. Guarde el archivo de Excel como CSV.'], 415);
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
        $resultado = ImportadorProductosCsv::parsearYValidar($contenido, $modelo);

        if ($resultado['exito'] && !empty($resultado['filas'])) {
            $tokenLote = bin2hex(random_bytes(16));
            $_SESSION['carga_masiva_token'] = $tokenLote;
            $_SESSION['carga_masiva_filas'] = $resultado['filas'];
            $resultado['token_lote'] = $tokenLote;
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

        $modelo = new ProductoModelo();
        $resultado = ImportadorProductosCsv::ejecutarImportacion($filas, $modelo);

        if ($resultado['exito']) {
            unset($_SESSION['carga_masiva_token'], $_SESSION['carga_masiva_filas']);
            registrarBitacora('productos', 'importacion_masiva', "Carga masiva completada: {$resultado['creados']} creados, {$resultado['actualizados']} actualizados ({$resultado['total']} total).");
        }

        $respuesta->enviarJson($resultado);
    }
}
