<?php

namespace Cycsa\Modulos\Cotizaciones\Controladores;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;

class CotizacionesControlador extends ControladorBase {
    
    public function index(Peticion $peticion, Respuesta $respuesta) {
        if (!isset($_SESSION['usuario_id'])) {
            $respuesta->redirigir('/Cycsa/publico/login');
        }
        
        $this->renderizar('cotizaciones/vistas/index', [
            'titulo' => 'Cotizaciones - Cycsa'
        ]);
    }
}