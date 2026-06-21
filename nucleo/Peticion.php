<?php

namespace Cycsa\Nucleo;

class Peticion {
    public function obtenerMetodo(): string {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function obtenerRuta(): string {
        $ruta = $_SERVER['REQUEST_URI'] ?? '/';
        $posicionDirectorio = strpos($ruta, '/publico');
        
        if ($posicionDirectorio !== false) {
            $ruta = substr($ruta, $posicionDirectorio + 8); // 8 es la longitud de '/publico'
        }
        
        $posicionInterrogacion = strpos($ruta, '?');
        if ($posicionInterrogacion !== false) {
            $ruta = substr($ruta, 0, $posicionInterrogacion);
        }
        
        return $ruta === '' ? '/' : $ruta;
    }

    public function esPost(): bool {
        return $this->obtenerMetodo() === 'POST';
    }

    public function esGet(): bool {
        return $this->obtenerMetodo() === 'GET';
    }

    public function obtenerDatos(): array {
        $datos = [];
        if ($this->esGet()) {
            foreach ($_GET as $clave => $valor) {
                $datos[$clave] = $valor; // Datos crudos, sin sanitización destructiva
            }
        }
        if ($this->esPost()) {
            foreach ($_POST as $clave => $valor) {
                $datos[$clave] = $valor; // Datos crudos, listos para bindParam en PDO
            }
        }
        return $datos;
    }
}