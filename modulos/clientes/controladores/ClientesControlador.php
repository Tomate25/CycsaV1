<?php

namespace Cycsa\Modulos\Clientes\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Clientes\Modelos\ClienteModelo;

class ClientesControlador extends ControladorBase {
    
    private function verificarSesion(Respuesta $respuesta): void {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
            exit; // Usamos exit para asegurar que el script muere
        }
    }

    // 🔍 INDEX CON BUSCADOR
    public function index(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('clientes', 'ver')) {
            $respuesta->redirigir('/Cycsa/publico/panel');
            exit;
        }
        
        $modelo = new ClienteModelo();
        // Capturamos lo que el usuario escribió en el buscador (si hay algo)
        $busqueda = $_GET['q'] ?? ''; 

        $this->renderizar('clientes/vistas/index', [
            'titulo' => 'Módulo de Clientes - Cycsa',
            'clientes' => $modelo->obtenerTodos($busqueda),
            'busqueda' => $busqueda
        ]);
    }

    public function crear(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('clientes', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/clientes');
            exit;
        }
        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

        $this->renderizar('clientes/vistas/crear', [
            'titulo' => 'Registrar Cliente - Cycsa'
        ]);
    }

    public function guardar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('clientes', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/clientes');
            exit;
        }
        
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new ClienteModelo();

            if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
                $this->renderizar('clientes/vistas/crear', ['titulo' => 'Registrar Cliente', 'error' => 'Error: Token CSRF inválido.', 'valores' => $datos]); return;
            }
            if (empty(trim($datos['nombre_razon_social']))) {
                $this->renderizar('clientes/vistas/crear', ['titulo' => 'Registrar Cliente', 'error' => 'El nombre o razón social es obligatorio.', 'valores' => $datos]); return;
            }
            if (!empty($datos['email']) && $modelo->emailExiste($datos['email'])) {
                $this->renderizar('clientes/vistas/crear', ['titulo' => 'Registrar Cliente', 'error' => 'El correo electrónico ya está registrado.', 'valores' => $datos]); return;
            }
            if (!empty($datos['identificacion']) && $modelo->identificacionExiste($datos['identificacion'])) {
                $this->renderizar('clientes/vistas/crear', ['titulo' => 'Registrar Cliente', 'error' => 'La identificación ya está registrada.', 'valores' => $datos]); return;
            }

            if ($modelo->guardar($datos)) {
                $respuesta->redirigir('/Cycsa/publico/clientes');
                return;
            }
        }
    }

    // ✏️ MOSTRAR FORMULARIO DE EDICIÓN
    public function editar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('clientes', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/clientes');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        if (!$id) { $respuesta->redirigir('/Cycsa/publico/clientes'); return; }

        $modelo = new ClienteModelo();
        $cliente = $modelo->obtenerPorId((int)$id);

        if (!$cliente) { $respuesta->redirigir('/Cycsa/publico/clientes'); return; }
        if (empty($_SESSION['csrf_token'])) { $_SESSION['csrf_token'] = bin2hex(random_bytes(32)); }

        $this->renderizar('clientes/vistas/editar', [
            'titulo' => 'Editar Cliente - Cycsa',
            'cliente' => $cliente
        ]);
    }

    // ✏️ GUARDAR LOS CAMBIOS DE EDICIÓN
    public function actualizar(Peticion $peticion, Respuesta $respuesta): void {
        $this->verificarSesion($respuesta);
        if (!tienePermiso('clientes', 'crear_editar')) {
            $respuesta->redirigir('/Cycsa/publico/clientes');
            exit;
        }
        
        $id = $_GET['id'] ?? null;
        if (!$id || !$peticion->esPost()) { $respuesta->redirigir('/Cycsa/publico/clientes'); return; }

        $datos = $peticion->obtenerDatos();
        $modelo = new ClienteModelo();

        if (!isset($datos['csrf_token']) || $datos['csrf_token'] !== ($_SESSION['csrf_token'] ?? '')) {
            $this->renderizar('clientes/vistas/editar', ['titulo' => 'Editar Cliente', 'error' => 'Error: Token CSRF inválido.', 'cliente' => $datos]); return;
        }
        if (empty(trim($datos['nombre_razon_social']))) {
            $this->renderizar('clientes/vistas/editar', ['titulo' => 'Editar Cliente', 'error' => 'El nombre es obligatorio.', 'cliente' => $datos]); return;
        }
        
        // Verificamos duplicados PERO excluimos el ID del cliente actual
        if (!empty($datos['email']) && $modelo->emailExiste($datos['email'], (int)$id)) {
            $this->renderizar('clientes/vistas/editar', ['titulo' => 'Editar Cliente', 'error' => 'Este correo ya pertenece a otro cliente.', 'cliente' => $datos]); return;
        }
        if (!empty($datos['identificacion']) && $modelo->identificacionExiste($datos['identificacion'], (int)$id)) {
            $this->renderizar('clientes/vistas/editar', ['titulo' => 'Editar Cliente', 'error' => 'Esta identificación ya pertenece a otro cliente.', 'cliente' => $datos]); return;
        }

        if ($modelo->actualizar((int)$id, $datos)) {
            $respuesta->redirigir('/Cycsa/publico/clientes');
            return;
        }
    }
}