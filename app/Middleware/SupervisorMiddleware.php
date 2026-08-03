<?php

namespace Cycsa\App\Middleware;

/**
 * Middleware para validar el rol de supervisor o superior.
 */
class SupervisorMiddleware
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

        $rol = isset($_SESSION['usuario_rol']) ? (int)$_SESSION['usuario_rol'] : 0;
        $rolesPermitidos = [1, 3]; // Admin y Supervisor
        if (!isset($_SESSION['usuario_id']) || !in_array($rol, $rolesPermitidos)) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($requestUri, '/api/') !== false) {
                header('Content-Type: application/json');
                header('HTTP/1.1 403 Forbidden');
                echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado. Se requiere rol de supervisor.', 'codigo' => 403]);
            } else {
                header('Location: /Cycsa/publico/panel');
            }
            exit;
        }

        return true;
    }
}

