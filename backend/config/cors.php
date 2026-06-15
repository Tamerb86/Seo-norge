<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Locked to the SPA frontend origin(s). The API is bearer-token based
    | (no cookies), so we do NOT allow credentials and never use a wildcard
    | origin in production.
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie', 'webhooks/*'],

    'allowed_methods' => ['*'],

    // Comma-separated list in CORS_ALLOWED_ORIGINS, falling back to FRONTEND_URL.
    'allowed_origins' => array_filter(array_map('trim', explode(
        ',',
        env('CORS_ALLOWED_ORIGINS', env('FRONTEND_URL', 'http://localhost:3000'))
    ))),

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
