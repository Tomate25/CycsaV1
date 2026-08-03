<?php

namespace Cycsa\App\Middleware;

/**
 * Middleware para validar token CSRF.
 */
class CsrfMiddleware
{
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

        $metodo = $_SERVER['REQUEST_METHOD'] ?? 'GET';
        if (in_array($metodo, ['POST', 'PUT', 'DELETE', 'PATCH'])) {
            $headers = function_exists('getallheaders') ? getallheaders() : [];
            $tokenRecibido = $_POST['csrf_token'] ?? $headers['X-CSRF-TOKEN'] ?? '';

            if (empty($tokenRecibido) || empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $tokenRecibido)) {
                header('HTTP/1.1 403 Forbidden');
                echo json_encode(['ok' => false, 'mensaje' => 'Token CSRF inválido o faltante.', 'codigo' => 403]);
                exit;
            }
        } else {
            // Generar token en GET si no existe
            if (empty($_SESSION['csrf_token'])) {
                $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
            }
        }

        return true;
    }
}

