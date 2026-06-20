<?php

namespace Cycsa\Modulos\Usuarios\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;

class PanelControlador extends ControladorBase {
    
    public function index(Peticion $peticion, Respuesta $respuesta) {
        // 🔒 BARRERA DE SEGURIDAD: Si no hay sesión, lo mandamos al login
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
        }
        
        // Si hay sesión, mostramos la vista del panel y le pasamos los datos del usuario
        $this->renderizar('usuarios/vistas/panel', [
            'titulo' => 'Panel de Control - Cycsa',
            'nombre' => $_SESSION['usuario_nombre'],
            'rol_id' => $_SESSION['usuario_rol']
        ]);
    }
}