<?php

namespace Cycsa\Modulos\Autenticacion\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Autenticacion\Modelos\UsuarioModelo;

class AutenticacionControlador extends ControladorBase {
    
    public function mostrarLogin(Peticion $peticion, Respuesta $respuesta) {
        // Si ya está logueado, no le mostramos el login, lo mandamos al panel
        if (isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/panel');
        }
        $this->renderizarSinLayout('autenticacion/vistas/login', ['titulo' => 'Iniciar Sesión - Cycsa']);
    }

    public function procesarLogin(Peticion $peticion, Respuesta $respuesta) {
        $datos = $peticion->obtenerDatos();
        $email = $datos['email'] ?? '';
        $password = $datos['password'] ?? '';

        $modeloUsuario = new UsuarioModelo();
        $usuario = $modeloUsuario->buscarPorEmail($email);

        if ($usuario && password_verify($password, $usuario['password'])) {
            if ($usuario['activo'] == 1) {
                // Prevenir Session Fixation regenerando el ID
                session_regenerate_id(true);
                
                $_SESSION['usuario_id'] = $usuario['id'];
                $_SESSION['usuario_nombre'] = $usuario['nombre'];
                $_SESSION['usuario_rol'] = $usuario['id_rol'];
                
                // 🚀 REDIRIGIR AL PANEL DE CONTROL
                $respuesta->redirigir('/Cycsa/publico/panel');
            } else {
                $this->renderizarSinLayout('autenticacion/vistas/login', [
                    'titulo' => 'Iniciar Sesión - Cycsa',
                    'error' => 'Tu cuenta está inactiva.'
                ]);
            }
        } else {
            $this->renderizarSinLayout('autenticacion/vistas/login', [
                'titulo' => 'Iniciar Sesión - Cycsa',
                'error' => 'Credenciales incorrectas.'
            ]);
        }
    }

    // 🔒 NUEVA FUNCIÓN: Destruir la sesión
    public function cerrarSesion(Peticion $peticion, Respuesta $respuesta) {
        session_destroy();
        $_SESSION = [];
        $respuesta->redirigir('/Cycsa/publico/login');
    }
}