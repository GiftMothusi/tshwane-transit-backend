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
        'http://10.0.2.2:19000',      // Android Emulator
        'http://10.0.2.2:19006',      // Android Emulator Web
        'http://localhost:8081',       // React Native development
        'capacitor://localhost',       // Mobile app
        'http://localhost',
        env('MOBILE_APP_URL', '*'),   // Production mobile URL
    ],

    'allowed_origins_patterns' => [
        'exp://.*',  // Allow all Expo development URLs
        'http://192.168.*.*:19000',    // Local network IPs for Expo
        'http://192.168.*.*:19006',    // Local network IPs for Expo web
    ],

    'allowed_headers' => [
        'X-Requested-With',
        'Content-Type',
        'X-Token-Auth',
        'Authorization',
        'Accept',
        'X-XSRF-TOKEN',
        'origin',
        'withcredentials',
        'credentials',
        'Access-Control-Allow-Credentials',
    ],

    'exposed_headers' => [
        'Cache-Control',
        'Content-Language',
        'Content-Type',
        'Expires',
        'Last-Modified',
        'Pragma',
        'Access-Control-Allow-Credentials',
    ],

    'max_age' => 60 * 60 * 24, // 24 hours cache for pre-flight requests

    'supports_credentials' => true, // Required for cookies/auth to work
];
