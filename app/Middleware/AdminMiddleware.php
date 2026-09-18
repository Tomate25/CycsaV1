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

        $scriptName = $_SERVER['SCRIPT_NAME'] ?? '';
        $basePath = rtrim(str_replace('\\', '/', dirname($scriptName)), '/');
        if ($basePath === '/' || $basePath === '\\') {
            $basePath = '';
        }
        $panelUrl = $basePath . '/panel';

        $ruta = parse_url($_SERVER['REQUEST_URI'] ?? '', PHP_URL_PATH) ?: '';
        $plantillas = (bool)preg_match('~(?:^|/)configuracion/plantillas-ensayos(?:/(?:obtener-ajax|guardar|restablecer))?$~', $ruta);
        $rol = (int)($_SESSION['usuario_rol'] ?? 0);
        $autorizado = $rol === 1 || ($plantillas && $rol === 2);
        if (!isset($_SESSION['usuario_id']) || !$autorizado) {
            $requestUri = $_SERVER['REQUEST_URI'] ?? '';
            if (strpos($requestUri, '/api/') !== false) {
                header('Content-Type: application/json');
                header('HTTP/1.1 403 Forbidden');
                echo json_encode(['ok' => false, 'mensaje' => 'Acceso denegado. Se requiere rol de administrador.', 'codigo' => 403]);
            } else {
                header('Location: ' . $panelUrl);
            }
            exit;
        }

        return true;
    }
}

