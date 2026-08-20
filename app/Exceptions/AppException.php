<?php

namespace Cycsa\App\Exceptions;

use Exception;

/**
 * Clase base para excepciones de la aplicación.
 */
class AppException extends Exception
{
    protected int $codigoHttp;

    public function __construct(string $message, int $codigoHttp = 500, \Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
        $this->codigoHttp = $codigoHttp;
    }

    public function getCodigoHttp(): int
    {
        return $this->codigoHttp;
    }
}
