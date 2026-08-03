<?php

namespace Cycsa\Nucleo;

class Enrutador {
    private array $rutas = [];
    private Peticion $peticion;
    private Respuesta $respuesta;

    public function __construct(Peticion $peticion, Respuesta $respuesta) {
        $this->peticion = $peticion;
        $this->respuesta = $respuesta;
    }

    public function get(string $ruta, $callback, array $middlewares = []): void {
        $this->rutas['GET'][$ruta] = [
            'callback' => $callback,
            'middlewares' => $middlewares
        ];
    }

    public function post(string $ruta, $callback, array $middlewares = []): void {
        $this->rutas['POST'][$ruta] = [
            'callback' => $callback,
            'middlewares' => $middlewares
        ];
    }

    public function resolver() {
        $metodo = $this->peticion->obtenerMetodo();
        $ruta = $this->peticion->obtenerRuta();
        
        $rutaConfig = $this->rutas[$metodo][$ruta] ?? false;

        // Si la ruta no existe, devolvemos un error 404
        if ($rutaConfig === false) {
            $this->respuesta->establecerCodigoEstado(404);
            error_log("404 Error: Metodo=" . $metodo . ", Ruta=" . $ruta . ", Original URI=" . ($_SERVER['REQUEST_URI'] ?? ''));
            return "Error 404: La página que buscas no existe. (Ruta: " . htmlspecialchars($ruta) . ")";
        }

        $callback = $rutaConfig;
        $middlewares = [];

        if (is_array($rutaConfig) && isset($rutaConfig['callback'])) {
            $callback = $rutaConfig['callback'];
            $middlewares = $rutaConfig['middlewares'] ?? [];
        }

        // Ejecutar la lista de middlewares en orden
        foreach ($middlewares as $middlewareClass) {
            if (class_exists($middlewareClass)) {
                $middleware = new $middlewareClass();
                if (method_exists($middleware, 'handle')) {
                    $middleware->handle();
                }
            } else {
                error_log("Error: Middleware class $middlewareClass no existe.");
            }
        }

        // Si el callback es un arreglo (ej. [Controlador::class, 'metodo'])
        if (is_array($callback)) {
            $controlador = new $callback[0]();
            $callback[0] = $controlador;
        }

        // Ejecutamos la función o controlador asociado a la ruta
        return call_user_func($callback, $this->peticion, $this->respuesta);
    }
}