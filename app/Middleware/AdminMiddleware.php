<?php

namespace App\Middleware;

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

        if (!isset($_SESSION['rol']) || $_SESSION['rol'] !== 'admin') {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado. Se requiere rol de administrador.', 'codigo' => 403]);
            exit;
        }

        return true;
    }
}
