<?php

namespace Cycsa\Modulos\Usuarios\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Usuarios\Modelos\UsuarioModelo;

class UsuariosControlador extends ControladorBase {
    
    public function index(Peticion $peticion, Respuesta $respuesta) {
        if (!isset($_SESSION['usuario_id'])) { $respuesta->redirigir('/Cycsa/publico/login'); }
        
        $modelo = new UsuarioModelo();
        $this->renderizar('usuarios/vistas/index', [
            'titulo' => 'Gestión de Usuarios - Cycsa',
            'usuarios' => $modelo->obtenerTodos()
        ]);
    }

    public function crear(Peticion $peticion, Respuesta $respuesta) {
        if (!isset($_SESSION['usuario_id'])) { $respuesta->redirigir('/Cycsa/publico/login'); }
        
        $modelo = new UsuarioModelo();
        $this->renderizar('usuarios/vistas/crear', [
            'titulo' => 'Nuevo Usuario - Cycsa',
            'roles' => $modelo->obtenerRoles()
        ]);
    }

    public function guardar(Peticion $peticion, Respuesta $respuesta) {
        if (!isset($_SESSION['usuario_id'])) { $respuesta->redirigir('/Cycsa/publico/login'); }
        
        if ($peticion->esPost()) {
            $datos = $peticion->obtenerDatos();
            $modelo = new UsuarioModelo();
            
            $modelo->guardarUsuario(
                $datos['nombre'], 
                $datos['email'], 
                $datos['password'], 
                $datos['id_rol']
            );
            
            $respuesta->redirigir('/Cycsa/publico/usuarios');
        }
    }
}