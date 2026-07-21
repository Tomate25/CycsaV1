<?php

namespace Cycsa\App\Helpers;

/**
 * Helper de validación centralizada sin escribir empty($_POST) repetidamente.
 */
class ValidationHelper {

    public static function validarRequeridos(array $datos, array $camposRequeridos): array {
        $errores = [];
        foreach ($camposRequeridos as $campo) {
            if (!isset($datos[$campo]) || trim((string)$datos[$campo]) === '') {
                $errores[$campo] = "El campo '{$campo}' es obligatorio.";
            }
        }
        return $errores;
    }

    public static function esEmail(string $email): bool {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }

    public static function esRucNicaragua(string $ruc): bool {
        // Formato RUC Nicaragua: 14 caracteres (ej: J0310000000001)
        return (bool)preg_match('/^[J0-9][0-9]{13}$/i', trim($ruc));
    }
}
