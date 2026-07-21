<?php

namespace Cycsa\App\Helpers;

/**
 * Clase para manejar la autenticación y seguridad de sesiones.
 */
class AuthHelper
{
    /**
     * Hashea una contraseña.
     *
     * @param string $password
     * @return string
     */
    public static function hashPassword(string $password): string
    {
        return password_hash($password, PASSWORD_BCRYPT);
    }

    /**
     * Verifica una contraseña contra su hash.
     *
     * @param string $password
     * @param string $hash
     * @return bool
     */
    public static function verifyPassword(string $password, string $hash): bool
    {
        return password_verify($password, $hash);
    }

    /**
     * Genera un token único de sesión.
     *
     * @return string
     */
    public static function generateSessionToken(): string
    {
        return bin2hex(random_bytes(32));
    }

    /**
     * Verifica si el usuario actual tiene una sesión válida y única.
     * 
     * @param string $storedSessionId
     * @return bool
     */
    public static function verifyUniqueSession(string $storedSessionId): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        return isset($_SESSION['session_id']) && $_SESSION['session_id'] === $storedSessionId;
    }
}
