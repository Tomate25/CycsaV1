<?php

namespace Cycsa\Nucleo;

class Respuesta {
    public function establecerCodigoEstado(int $codigo): void {
        http_response_code($codigo);
    }

    public function redirigir(string $url): void {
        // 🔒 1. Sanitizar contra CRLF / Header Injection
        $url = str_replace(["\r", "\n"], '', trim($url));

        // 🔒 2. Mitigar Open Redirect: Si es URL absoluta externa, validar que pertenezca al mismo host
        if (preg_match('#^https?://#i', $url)) {
            $hostActual = $_SERVER['HTTP_HOST'] ?? '';
            $partesUrl = parse_url($url);
            if (!empty($partesUrl['host']) && $partesUrl['host'] === $hostActual) {
                header('Location: ' . $url);
                exit;
            }
            // Si intenta redirigir a un host externo, forzar a ruta interna segura
            $url = '/';
        }

        // 🔒 3. Bloquear redirecciones maliciosas de protocolo relativo (ej. //evil.com o /\evil.com)
        if (strpos($url, '//') === 0 || strpos($url, '/\\') === 0 || strpos($url, '\\') === 0) {
            $url = '/';
        }

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);
        $basePath = str_replace('\\', '/', $basePath);
        $basePath = rtrim($basePath, '/');
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        
        // Si la URL redirige a /Cycsa/publico, quitarlo
        if (strpos($url, '/Cycsa/publico') === 0) {
            $url = substr($url, 14);
        }
        
        // Asegurar que la ruta comience con /
        if ($url === '' || $url[0] !== '/') {
            $url = '/' . $url;
        }

        // Si la ruta no empieza ya con el basePath (y basePath no está vacío), anteponer basePath
        if ($basePath !== '' && strpos($url, $basePath . '/') !== 0 && $url !== $basePath) {
            $url = $basePath . $url;
        }
        
        header('Location: ' . $url);
        exit;
    }

    public function enviarJson(array $datos, int $codigoEstado = 200): void {
        $this->establecerCodigoEstado($codigoEstado);
        header('Content-Type: application/json; charset=utf-8');
        $json = json_encode($datos, JSON_UNESCAPED_UNICODE | JSON_INVALID_UTF8_SUBSTITUTE);
        if ($json === false) {
            $json = json_encode(['status' => 'error', 'message' => 'Error al codificar respuesta JSON: ' . json_last_error_msg()]);
        }
        echo $json;
        exit;
    }
}