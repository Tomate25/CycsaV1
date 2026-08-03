<?php

namespace Cycsa\Modulos\Productos\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Productos\Modelos\ProductoModelo;

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
        
        $id = decodificarId($_GET['id'] ?? '');
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
}
