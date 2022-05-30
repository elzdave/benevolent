<?php

return [
    /*
    |--------------------------------------------------------------------------
    | Benevolent External REST API Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the configuration of external REST API services
    | used by this package.
    |
    */

    // The external service's base URL. Required.
    'base_url' => rtrim(env('EXT_API_BASE_URI','http://localhost'), '/') . '/',

    // The application login page route name.
    'login_route' => 'login',

    'paths' => [
        // Login path relative to the base URL. Required.
        'login' => 'auth/signin',

        // Refresh token path relative to the base URL.
        // Set to null if you plan to not using this feature.
        'refresh_token' => 'auth/refresh',
    ],

    'cache' => [
        // The cache key name prefix.
        'prefix' => 'benevolent.cached',

        // The cache repository expiration time. Set this according to your needs.
        'expiration_time' => \DateInterval::createFromDateString('1 week'),
    ],

    'keys' => [
        // The result wrapper key name.
        'wrapper' => 'result',

        // The access token key name.
        'access_token' => 'access_token',

        // The refresh token key name.
        'refresh_token' => 'refresh_token',

        // The token schema key name.
        'token_schema' => 'token_schema',

        // The userdata key name.
        'user_data' => 'userdata',
    ],

    'features' => [
        // Set this to true if you don't wrap the result into any key.
        'without_wrapper' => false,

        // Enable refresh token feature. Set this to false
        // if you do not using refresh token
        // (eg: single token/access token live forever)
        'enable_refresh_token' => true,
    ],
];
