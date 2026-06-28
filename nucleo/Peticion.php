<?php

namespace Cycsa\Nucleo;

class Peticion {
    public function obtenerMetodo(): string {
        return $_SERVER['REQUEST_METHOD'];
    }

    public function obtenerRuta(): string {
        $ruta = $_SERVER['REQUEST_URI'] ?? '/';
        
        // 1. Quitar parámetros GET (todo a partir del ?)
        $posicionInterrogacion = strpos($ruta, '?');
        if ($posicionInterrogacion !== false) {
            $ruta = substr($ruta, 0, $posicionInterrogacion);
        }
        
        // 2. Obtener el directorio del script actual (ej: /Cycsa/publico o /sistema/publico)
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $scriptDir = str_replace('\\', '/', dirname($scriptName));
        $scriptDir = rtrim($scriptDir, '/');
        
        // 3. Resolver la ruta según el contexto de despliegue
        $posicionPublico = strpos($ruta, '/publico');
        if ($posicionPublico !== false) {
            // Caso A: "/publico" está en la URL (ej: localhost) -> recortamos todo hasta /publico
            $ruta = substr($ruta, $posicionPublico + 8);
        } else {
            // Caso B: "/publico" fue ocultado (ej: Bluehost con .htaccess en subcarpeta)
            // Obtenemos la raíz del proyecto en la URL (ej: /Cycsa o /sistema o /portal)
            $proyectoRaizUrl = str_replace('/publico', '', $scriptDir);
            
            if ($proyectoRaizUrl !== '' && strpos($ruta, $proyectoRaizUrl) === 0) {
                $ruta = substr($ruta, strlen($proyectoRaizUrl));
            }
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