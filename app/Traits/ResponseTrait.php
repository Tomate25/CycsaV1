<?php

namespace Cycsa\App\Traits;

/**
 * Trait para estandarización unificada de respuestas JSON en controladores y APIs.
 */
trait ResponseTrait {

    protected function responderJson(bool $ok, string $mensaje = '', array $datos = [], int $codigoHttp = 200): void {
        header('Content-Type: application/json; charset=utf-8');
        http_response_code($codigoHttp);

        echo json_encode([
            'ok' => $ok,
            'mensaje' => $mensaje,
            'datos' => $datos,
            'timestamp' => date('Y-m-d H:i:s')
        ], JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
        exit;
    }

    protected function respuestaExito(string $mensaje = 'Operación realizada con éxito', array $datos = [], int $codigoHttp = 200): void {
        $this->responderJson(true, $mensaje, $datos, $codigoHttp);
    }

    protected function respuestaError(string $mensaje = 'Error al procesar la solicitud', array $datos = [], int $codigoHttp = 400): void {
        $this->responderJson(false, $mensaje, $datos, $codigoHttp);
    }
}
