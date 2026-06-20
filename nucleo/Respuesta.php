<?php

namespace Cycsa\Nucleo;

class Respuesta {
    public function establecerCodigoEstado(int $codigo): void {
        http_response_code($codigo);
    }

    public function redirigir(string $url): void {
        header('Location: ' . $url);
        exit;
    }

    public function enviarJson(array $datos, int $codigoEstado = 200): void {
        $this->establecerCodigoEstado($codigoEstado);
        header('Content-Type: application/json');
        echo json_encode($datos);
        exit;
    }
}