<?php

namespace Cycsa\App\Middleware;

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

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        $loginUrl = $basePath . '/login';

        if (!isset($_SESSION['usuario_id'])) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($requestUri, '/api/') !== false) {
                header('Content-Type: application/json');
                header('HTTP/1.1 401 Unauthorized');
                echo json_encode(['ok' => false, 'mensaje' => 'No autorizado. Inicie sesión.', 'codigo' => 401]);
            } else {
                header('Location: ' . $loginUrl);
            }
            exit;
        }

        if (isset($_SESSION['ultima_actividad']) && (time() - $_SESSION['ultima_actividad'] > self::TIEMPO_INACTIVIDAD)) {
            session_unset();
            session_destroy();
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($requestUri, '/api/') !== false) {
                header('Content-Type: application/json');
                header('HTTP/1.1 401 Unauthorized');
                echo json_encode(['ok' => false, 'mensaje' => 'Sesión expirada por inactividad.', 'codigo' => 401]);
            } else {
                header('Location: ' . $loginUrl);
            }
            exit;
        }

        $_SESSION['ultima_actividad'] = time();
        return true;
    }
}

