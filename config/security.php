<?php

return [
    'max_login_attempts' => 5,
    'lockout_time_seconds' => 900, // 15 minutos
    'session_lifetime' => 7200, // 2 horas
    'csrf_token_name' => '_csrf_token',
    'single_session_per_user' => true,
    'force_password_change_on_reset' => true,
    'http_headers' => [
        'X-Frame-Options' => 'SAMEORIGIN',
        'X-Content-Type-Options' => 'nosniff',
        'X-XSS-Protection' => '1; mode=block',
        'Referrer-Policy' => 'strict-origin-when-cross-origin',
        'Content-Security-Policy' => "default-src 'self' 'unsafe-inline' 'unsafe-eval' https:; img-src 'self' data: https:;"
    ]
];
