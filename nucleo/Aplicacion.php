<?php

namespace Cycsa\Nucleo;

class Aplicacion {
    public static Aplicacion $app;
    public Enrutador $enrutador;
    public Peticion $peticion;
    public Respuesta $respuesta;

    public function __construct() {
        self::$app = $this;
        
        // 1. Iniciamos las sesiones con configuración de seguridad para producción
        $secure = !empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off';
        session_start([
            'cookie_httponly' => true,   // Impide acceso a la cookie desde JavaScript (anti-XSS)
            'cookie_secure'   => $secure,  // Solo envía la cookie por HTTPS si está habilitado
            'cookie_samesite' => 'Strict', // Bloquea envío de cookie desde sitios externos (anti-CSRF)
            'use_strict_mode' => true,   // Rechaza IDs de sesión no generados por el servidor
        ]); 

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
            $lines = file($rutaEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
            if ($lines !== false) {
                foreach ($lines as $line) {
                    $line = trim($line);
                    if ($line === '' || $line[0] === '#' || $line[0] === ';') {
                        continue;
                    }
                    
                    $parts = explode('=', $line, 2);
                    if (count($parts) === 2) {
                        $clave = trim($parts[0]);
                        $valor = trim($parts[1]);
                        
                        // Quitar comillas si están presentes al inicio y final
                        $len = strlen($valor);
                        if ($len >= 2 && (
                            ($valor[0] === '"' && $valor[$len - 1] === '"') ||
                            ($valor[0] === "'" && $valor[$len - 1] === "'")
                        )) {
                            $valor = substr($valor, 1, -1);
                        }
                        
                        $_ENV[$clave] = $valor;
                    }
                }
            }
        } else {
            error_log("Error Crítico: No se encontró el archivo .env en la ruta: " . $rutaEnv);
            die("Error 500: Fallo interno.");
        }
    }

    public function correr(): void {
        echo $this->enrutador->resolver();
    }
}