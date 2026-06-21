<?php

namespace Cycsa\Nucleo;

class ControladorBase {
    
    public function renderizar(string $rutaVista, array $datos = []): void {
        extract($datos);

        $rutaCompleta = __DIR__ . '/../modulos/' . $rutaVista . '.php';

        if (file_exists($rutaCompleta)) {
            // 1. Empezamos a capturar el HTML en memoria
            ob_start();
            require $rutaCompleta;
            // 2. Guardamos todo ese HTML en la variable $contenido
            $contenido = ob_get_clean();
            
            // 3. Cargamos la plantilla maestra, pasándole el $contenido
            require __DIR__ . '/../plantillas/layout.php';
        } else {
            error_log("Error del Sistema: No se encontró la vista en la ruta {$rutaCompleta}");
            die("Error 500: Fallo interno.");
        }
    }

    // Renderiza una vista SIN el layout del sistema (para login, errores 404, etc.)
    public function renderizarSinLayout(string $rutaVista, array $datos = []): void {
        extract($datos);

        $rutaCompleta = __DIR__ . '/../modulos/' . $rutaVista . '.php';

        if (file_exists($rutaCompleta)) {
            require $rutaCompleta;
        } else {
            error_log("Error del Sistema: No se encontró la vista en la ruta {$rutaCompleta}");
            die("Error 500: Fallo interno.");
        }
    }
}