<?php

return [
    'name' => env('APP_NAME', 'Intakely'),
    'env' => env('APP_ENV', 'production'),
    'url' => env('APP_URL', 'http://localhost:8000'),
    'key' => env('APP_KEY', ''),
    'demo_mode' => env('DEMO_MODE', 'false') === 'true',
    'demo_logins' => env('DEMO_LOGINS', 'false') === 'true',

    'middleware' => [
        App\Middleware\SecurityHeadersMiddleware::class,
        App\Middleware\TenantResolutionMiddleware::class,
        App\Middleware\CsrfMiddleware::class,
        App\Middleware\AuthMiddleware::class,
    ],
];
