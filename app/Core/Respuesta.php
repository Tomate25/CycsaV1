<?php

namespace Cycsa\Nucleo;

class Respuesta {
    public function establecerCodigoEstado(int $codigo): void {
        http_response_code($codigo);
    }

    public function redirigir(string $url): void {
        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = dirname($scriptName);
        $basePath = str_replace('\\', '/', $basePath);
        $basePath = rtrim($basePath, '/');
        if ($basePath === '/') {
            $basePath = '';
        }
        
        // Si la URL redirige a /Cycsa/publico, la adaptamos al base path real
        if (strpos($url, '/Cycsa/publico') === 0) {
            $url = $basePath . substr($url, 14);
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