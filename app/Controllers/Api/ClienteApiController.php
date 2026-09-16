<?php

namespace Cycsa\App\Controllers\Api;

use Cycsa\Nucleo\ControladorBase;
use Cycsa\Nucleo\Peticion;
use Cycsa\Nucleo\Respuesta;
use Cycsa\Modulos\Clientes\Modelos\ClienteModelo;

/**
 * Controlador API REST para Clientes.
 * Adaptado a la arquitectura nativa Cycsa (eliminadas dependencias fantasma de Laravel).
 */
class ClienteApiController extends ControladorBase
{
    private ClienteModelo $modelo;

    public function __construct()
    {
        $this->modelo = new ClienteModelo();
    }

    public function index(Peticion $peticion, Respuesta $respuesta): void
    {
        $clientes = $this->modelo->obtenerTodos();
        $respuesta->enviarJson(['status' => 'success', 'data' => $clientes]);
    }
}
