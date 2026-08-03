<?php

namespace Cycsa\Nucleo;

class ControladorBase {
    
    public function renderizar(string $rutaVista, array $datos = []): string {
        extract($datos);

        $rutaCompleta = $this->resolverRutaVista($rutaVista);

        if (file_exists($rutaCompleta)) {
            // 1. Empezamos a capturar el HTML en memoria
            ob_start();
            require $rutaCompleta;
            // 2. Guardamos todo ese HTML en la variable $contenido
            $contenido = ob_get_clean();
            
            // 3. Cargamos la plantilla maestra desde app/Views/layout.php
            $rutaLayout = __DIR__ . '/../Views/layout.php';
            if (!file_exists($rutaLayout)) {
                $rutaLayout = __DIR__ . '/../../plantillas/layout.php';
            }
            ob_start();
            require $rutaLayout;
            return ob_get_clean();
        } else {
            error_log("Error del Sistema: No se encontró la vista en la ruta {$rutaCompleta}");
            die("Error 500: Fallo interno.");
        }
    }

    // Renderiza una vista SIN el layout del sistema (para login, errores 404, etc.)
    public function renderizarSinLayout(string $rutaVista, array $datos = []): string {
        extract($datos);

        $rutaCompleta = $this->resolverRutaVista($rutaVista);

        if (file_exists($rutaCompleta)) {
            ob_start();
            require $rutaCompleta;
            return ob_get_clean();
        } else {
            error_log("Error del Sistema: No se encontró la vista en la ruta {$rutaCompleta}");
            die("Error 500: Fallo interno.");
        }
    }

    private function resolverRutaVista(string $rutaVista): string {
        // Normalizar separadores y variaciones de mayúsculas en 'vistas' / 'Vistas'
        $variaciones = [
            $rutaVista,
            ucfirst($rutaVista),
            str_replace('/vistas/', '/Vistas/', $rutaVista),
            str_replace('/Vistas/', '/vistas/', $rutaVista),
            ucfirst(str_replace('/vistas/', '/Vistas/', $rutaVista))
        ];

        foreach ($variaciones as $var) {
            $opciones = [
                __DIR__ . '/../Modulos/' . $var . '.php',
                __DIR__ . '/../../modulos/' . $var . '.php'
            ];
            foreach ($opciones as $opcion) {
                if (file_exists($opcion)) {
                    return $opcion;
                }
            }
        }

        return __DIR__ . '/../Modulos/' . $rutaVista . '.php';
    }
}