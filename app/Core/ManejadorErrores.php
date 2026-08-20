<?php

namespace Cycsa\Nucleo;

use Cycsa\App\Services\LogService;
use Cycsa\App\Traits\ResponseTrait;

/**
 * Manejador global de errores y excepciones.
 */
class ManejadorErrores
{
    use ResponseTrait;

    /**
     * Registra el manejador de excepciones y errores.
     */
    public function registrar(): void
    {
        set_error_handler([$this, 'manejarError']);
        set_exception_handler([$this, 'manejarExcepcion']);
    }

    /**
     * Convierte errores de PHP en ErrorException.
     */
    public function manejarError(int $nivel, string $mensaje, string $archivo, int $linea): bool
    {
        if (error_reporting() & $nivel) {
            throw new \ErrorException($mensaje, 0, $nivel, $archivo, $linea);
        }
        return false;
    }

    /**
     * Maneja las excepciones no capturadas.
     */
    public function manejarExcepcion(\Throwable $e): void
    {
        $logService = new LogService();
        $codigoHttp = 500;
        
        if (method_exists($e, 'getCodigoHttp')) {
            $codigoHttp = $e->getCodigoHttp();
        }
        
        $mensajeLog = sprintf(
            "%s: %s en %s:%d\nStack trace:\n%s",
            get_class($e),
            $e->getMessage(),
            $e->getFile(),
            $e->getLine(),
            $e->getTraceAsString()
        );
        
        $logService->error('app', $mensajeLog);

        // En desarrollo se podría mostrar el mensaje real, en producción uno genérico
        $mostrarDetalles = defined('ENTORNO') && ENTORNO === 'desarrollo';
        $mensajeUsuario = $mostrarDetalles ? $e->getMessage() : 'Ha ocurrido un error interno en el servidor.';

        $this->jsonResponse(false, $mensajeUsuario, null, $codigoHttp);
    }
}
