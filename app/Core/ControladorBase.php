<?php

namespace Cycsa\Nucleo;

class ControladorBase {
    
    public function renderizar(string $rutaVista, array $datos = []): void {
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
            require $rutaLayout;
        } else {
            error_log("Error del Sistema: No se encontró la vista en la ruta {$rutaCompleta}");
            die("Error 500: Fallo interno.");
        }
    }

    // Renderiza una vista SIN el layout del sistema (para login, errores 404, etc.)
    public function renderizarSinLayout(string $rutaVista, array $datos = []): void {
        extract($datos);

        $rutaCompleta = $this->resolverRutaVista($rutaVista);

        if (file_exists($rutaCompleta)) {
            require $rutaCompleta;
        } else {
            error_log("Error del Sistema: No se encontró la vista en la ruta {$rutaCompleta}");
            die("Error 500: Fallo interno.");
        }
    }

    private function resolverRutaVista(string $rutaVista): string {
        $rutaLimpia = ltrim($rutaVista, '/\\');
        
        // 1. Si es ruta directa absoluta o relativa al proyecto
        if (file_exists($rutaVista)) {
            return $rutaVista;
        }
        $rutaDesdeRaiz = dirname(__DIR__, 2) . '/' . $rutaLimpia;
        if (file_exists($rutaDesdeRaiz)) {
            return $rutaDesdeRaiz;
        }
        if (file_exists($rutaDesdeRaiz . '.php')) {
            return $rutaDesdeRaiz . '.php';
        }

        // Quitar prefijo 'app/Modulos/' o 'app/' si viene incluido
        if (str_starts_with($rutaLimpia, 'app/Modulos/')) {
            $rutaLimpia = substr($rutaLimpia, strlen('app/Modulos/'));
        } elseif (str_starts_with($rutaLimpia, 'app/modulos/')) {
            $rutaLimpia = substr($rutaLimpia, strlen('app/modulos/'));
        } elseif (str_starts_with($rutaLimpia, 'app/')) {
            $rutaLimpia = substr($rutaLimpia, strlen('app/'));
        }
        if (str_ends_with($rutaLimpia, '.php')) {
            $rutaLimpia = substr($rutaLimpia, 0, -4);
        }

        $partes = explode('/', $rutaLimpia);
        if (count($partes) >= 2) {
            $modOrig = $partes[0];
            $modStudly = str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $modOrig)));
            $modUc = ucfirst($modOrig);
            
            $resto = implode('/', array_slice($partes, 1));
            $restoVistas = str_replace('vistas/', 'Vistas/', $resto);

            $modulosProbar = array_unique([$modOrig, $modStudly, $modUc]);
            $restosProbar = array_unique([$resto, $restoVistas]);

            foreach ($modulosProbar as $mod) {
                foreach ($restosProbar as $rst) {
                    $opcion = __DIR__ . '/../Modulos/' . $mod . '/' . $rst . '.php';
                    if (file_exists($opcion)) {
                        return $opcion;
                    }
                    $opcionOld = __DIR__ . '/../../modulos/' . $mod . '/' . $rst . '.php';
                    if (file_exists($opcionOld)) {
                        return $opcionOld;
                    }
                }
            }
        }

        return __DIR__ . '/../Modulos/' . $rutaLimpia . '.php';
    }
}