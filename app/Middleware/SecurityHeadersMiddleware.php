<?php

namespace Cycsa\App\Middleware;

/**
 * Middleware para agregar cabeceras de seguridad.
 */
class SecurityHeadersMiddleware
{
    /**
     * Maneja la petición entrante.
     *
     * @return bool
     */
    public function handle(): bool
    {
        header("X-Frame-Options: DENY");
        header("X-XSS-Protection: 1; mode=block");
        header("X-Content-Type-Options: nosniff");
        header("Strict-Transport-Security: max-age=31536000; includeSubDomains");
        header("Content-Security-Policy: default-src 'self'; script-src 'self' 'unsafe-inline'; style-src 'self' 'unsafe-inline';");
        header("Referrer-Policy: no-referrer-when-downgrade");

        return true;
    }
}

