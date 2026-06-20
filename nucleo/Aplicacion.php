<?php

namespace Cycsa\Nucleo;

class Aplicacion {
    public static Aplicacion $app;
    public Enrutador $enrutador;
    public Peticion $peticion;
    public Respuesta $respuesta;

    public function __construct() {
        self::$app = $this;
        
        // 1. Iniciamos las sesiones
        session_start(); 

        // 2. Cargamos las variables de entorno (.env)
        $this->cargarEntorno();
        
        $this->peticion = new Peticion();
        $this->respuesta = new Respuesta();
        $this->enrutador = new Enrutador($this->peticion, $this->respuesta);
    }

    // Función que lee el archivo .env y lo guarda en $_ENV
    private function cargarEntorno(): void {
        $rutaEnv = __DIR__ . '/../.env';
        
        if (file_exists($rutaEnv)) {
            $variables = parse_ini_file($rutaEnv);
            if ($variables) {
                foreach ($variables as $clave => $valor) {
                    $_ENV[$clave] = $valor;
                }
            }
        } else {
            die("Error Crítico: No se encontró el archivo .env en la raíz del proyecto.");
        }
    }

    public function correr(): void {
        echo $this->enrutador->resolver();
    }
}