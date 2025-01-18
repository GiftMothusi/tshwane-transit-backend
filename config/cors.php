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
    */

    'paths' => ['api/*', 'sanctum/csrf-cookie'],

    'allowed_methods' => ['*'],

    'allowed_origins' => [
        'http://localhost:19006',     // Expo web
        'http://localhost:3000',      // If you use a web version
        'exp://localhost:19000',      // Expo development
        env('MOBILE_APP_URL', '*'),   // Production mobile URL
    ],

    'allowed_origins_patterns' => [
        'exp://.*',  // Allow all Expo development URLs
    ],

    'allowed_headers' => [
        'X-Requested-With',
        'Content-Type',
        'X-Token-Auth',
        'Authorization',
        'Accept',
        'X-XSRF-TOKEN',
    ],

    'exposed_headers' => [
        'Cache-Control',
        'Content-Language',
        'Content-Type',
        'Expires',
        'Last-Modified',
        'Pragma',
    ],

    'max_age' => 60 * 60 * 24, // 24 hours cache for pre-flight requests

    'supports_credentials' => true, // Required for cookies/auth to work
];
