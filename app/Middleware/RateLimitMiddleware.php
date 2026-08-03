<?php

namespace Cycsa\App\Middleware;

/**
 * Middleware para bloqueo/limitación de intentos de login o peticiones.
 */
class RateLimitMiddleware
{
    private const MAX_INTENTOS = 5;
    private const TIEMPO_BLOQUEO = 900; // 15 minutos

    /**
     * Maneja la petición entrante.
     *
     * @return bool
     */
    public function handle(): bool
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $keyBloqueo = "bloqueo_{$ip}";
        $keyIntentos = "intentos_{$ip}";

        if (isset($_SESSION[$keyBloqueo]) && $_SESSION[$keyBloqueo] > time()) {
            $faltan = $_SESSION[$keyBloqueo] - time();
            header('HTTP/1.1 429 Too Many Requests');
            echo json_encode(['ok' => false, 'mensaje' => "Demasiados intentos. Intente nuevamente en {$faltan} segundos.", 'codigo' => 429]);
            exit;
        }

        // Esta lógica normalmente se ejecuta DESPUÉS de un intento fallido
        // Aquí solo comprobamos si ya excedió. El incremento se haría en el controlador de Login.
        if (isset($_SESSION[$keyIntentos]) && $_SESSION[$keyIntentos] >= self::MAX_INTENTOS) {
            $_SESSION[$keyBloqueo] = time() + self::TIEMPO_BLOQUEO;
            $_SESSION[$keyIntentos] = 0; // Resetear intentos
            header('HTTP/1.1 429 Too Many Requests');
            echo json_encode(['ok' => false, 'mensaje' => 'Máximo de intentos superado. Su IP ha sido bloqueada temporalmente.', 'codigo' => 429]);
            exit;
        }

        return true;
    }
}

