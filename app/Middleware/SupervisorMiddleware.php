<?php

namespace App\Middleware;

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

        $rolesPermitidos = ['admin', 'supervisor'];
        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $rolesPermitidos)) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado. Se requiere rol de supervisor.', 'codigo' => 403]);
            exit;
        }

        return true;
    }
}
