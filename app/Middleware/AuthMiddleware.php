<?php

namespace App\Middleware;

/**
 * Middleware para validar la sesión activa del usuario y tiempo de inactividad.
 */
class AuthMiddleware
{
    private const TIEMPO_INACTIVIDAD = 3600; // 1 hora en segundos

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

        if (!isset($_SESSION['usuario_id'])) {
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['ok' => false, 'mensaje' => 'No autorizado. Inicie sesión.', 'codigo' => 401]);
            exit;
        }

        if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad'] > self::TIEMPO_INACTIVIDAD)) {
            session_unset();
            session_destroy();
            header('HTTP/1.1 401 Unauthorized');
            echo json_encode(['ok' => false, 'mensaje' => 'Sesión expirada por inactividad.', 'codigo' => 401]);
            exit;
        }

        $_SESSION['ultima_actividad'] = time();
        return true;
    }
}
