<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Here you may configure your settings for cross-origin resource sharing
    | or "CORS". This determines what cross-origin operations may execute
    | in web browsers. You are free to adjust these settings as needed.
    |
    | To learn more: https://developer.mozilla.org/en-US/docs/Web/HTTP/CORS
    |
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'https://rifas.club',
        'https://www.rifas.club',
        'http://localhost:3000',  // Next.js default
        'http://localhost:3001',  // Alternative Next.js port
        'http://localhost:8080',  // Vue.js/Vite default
        'http://localhost:8081',  // Alternative front-end port
        'http://localhost:5173',  // Vite default port
        'http://127.0.0.1:3000',
        'http://127.0.0.1:8080',
        'http://127.0.0.1:8081',
        'http://127.0.0.1:5173',
    ],

    'allowed_origins_patterns' => [
        // Permite subdomínios em desenvolvimento
        '/^http:\/\/localhost:\d+$/',
        '/^http:\/\/127\.0\.0\.1:\d+$/',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [
        'X-RateLimit-Limit',
        'X-RateLimit-Remaining',
        'X-RateLimit-Reset',
    ],

    'max_age' => 0,

    'supports_credentials' => true,

];