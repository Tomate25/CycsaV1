<?php

namespace Cycsa\App\Validators;

use Cycsa\App\Helpers\ValidationHelper;

/**
 * Motor centralizado de validación de entradas.
 */
class Validator {

    private array $datos;
    private array $errores = [];

    public function __construct(array $datos) {
        $this->datos = $datos;
    }

    public static function make(array $datos): self {
        return new self($datos);
    }

    public function requerido(array $campos): self {
        $errores = ValidationHelper::validarRequeridos($this->datos, $campos);
        $this->errores = array_merge($this->errores, $errores);
        return $this;
    }

    public function email(string $campo): self {
        if (!empty($this->datos[$campo]) && !ValidationHelper::esEmail($this->datos[$campo])) {
            $this->errores[$campo] = "El campo '{$campo}' no es un correo electrónico válido.";
        }
        return $this;
    }

    public function ruc(string $campo): self {
        if (!empty($this->datos[$campo]) && !ValidationHelper::esRucNicaragua($this->datos[$campo])) {
            $this->errores[$campo] = "El campo '{$campo}' debe tener un RUC válido de Nicaragua.";
        }
        return $this;
    }

    public function esValido(): bool {
        return empty($this->errores);
    }

    public function obtenerErrores(): array {
        return $this->errores;
    }
}
