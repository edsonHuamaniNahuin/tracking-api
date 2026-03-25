<?php

return [
    /*
    |--------------------------------------------------------------------------
    | CORS — Rutas que admiten peticiones cross-origin
    |--------------------------------------------------------------------------
    */
    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    /*
    | En producción se lee FRONTEND_URL del .env de Render
    | (ej: https://tracking-front.netlify.app).
    | El patrón de wildcard cubre previews de Netlify (*.netlify.app).
    */
    'allowed_origins' => [
        env('FRONTEND_URL', 'http://localhost:3000'),
        'https://nautic.run',
        'https://www.nautic.run',
    ],

    'allowed_origins_patterns' => [
        '#https://.*\.netlify\.app#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,
];
