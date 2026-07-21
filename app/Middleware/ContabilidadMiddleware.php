<?php

namespace App\Middleware;

/**
 * Middleware para validar acceso financiero.
 */
class ContabilidadMiddleware
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

        $rolesFinancieros = ['admin', 'contador'];
        if (!isset($_SESSION['rol']) || !in_array($_SESSION['rol'], $rolesFinancieros)) {
            header('HTTP/1.1 403 Forbidden');
            echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado. Módulo de contabilidad.', 'codigo' => 403]);
            exit;
        }

        return true;
    }
}
