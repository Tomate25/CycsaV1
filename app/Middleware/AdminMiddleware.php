<?php

namespace Cycsa\App\Middleware;

/**
 * Middleware para validar el rol de administrador.
 */
class AdminMiddleware
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

        if (!isset($_SESSION['usuario_id']) || !isset($_SESSION['usuario_rol']) || (int)$_SESSION['usuario_rol'] !== 1) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($requestUri, '/api/') !== false) {
                header('Content-Type: application/json');
                header('HTTP/1.1 403 Forbidden');
                echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado. Se requiere rol de administrador.', 'codigo' => 403]);
            } else {
                header('Location: /Cycsa/publico/panel');
            }
            exit;
        }

        return true;
    }
}

