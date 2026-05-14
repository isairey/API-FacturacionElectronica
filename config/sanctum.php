<?php

use Laravel\Sanctum\Sanctum;

return [

    /*
    |--------------------------------------------------------------------------
    | Stateful Domains
    |--------------------------------------------------------------------------
    |
    | Requests from the following domains / hosts will receive stateful API
    | authentication cookies. Typically, these should include your local
    | and production domains which access your API via a frontend SPA.
    |
    */

    'stateful' => explode(',', env('SANCTUM_STATEFUL_DOMAINS', sprintf(
        '%s%s',
        'localhost,localhost:3000,127.0.0.1,127.0.0.1:8000,::1',
        Sanctum::currentApplicationUrlWithPort(),
        // Sanctum::currentRequestHost(),
    ))),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Guards
    |--------------------------------------------------------------------------
    |
    | This array contains the authentication guards that will be checked when
    | Sanctum is trying to authenticate a request. If none of these guards
    | are able to authenticate the request, Sanctum will use the bearer
    | token that's present on an incoming request for authentication.
    |
    */

    'guard' => ['web'],

    /*
    |--------------------------------------------------------------------------
    | Expiration Minutes
    |--------------------------------------------------------------------------
    |
    | This value controls the number of minutes until an issued token will be
    | considered expired. This will override any values set in the token's
    | "expires_at" attribute, but first-party sessions are not affected.
    |
    */

    'expiration' => env('SANCTUM_EXPIRATION', 1440), // 24 horas por defecto

    /*
    |--------------------------------------------------------------------------
    | Token Prefix
    |--------------------------------------------------------------------------
    |
    | Sanctum can prefix new tokens in order to take advantage of numerous
    | security scanning initiatives maintained by open source platforms
    | that notify developers if they commit tokens into repositories.
    |
    | See: https://docs.github.com/en/code-security/secret-scanning/about-secret-scanning
    |
    */

    'token_prefix' => env('SANCTUM_TOKEN_PREFIX', 'sunat_'),

    /*
    |--------------------------------------------------------------------------
    | Sanctum Middleware
    |--------------------------------------------------------------------------
    |
    | When authenticating your first-party SPA with Sanctum you may need to
    | customize some of the middleware Sanctum uses while processing the
    | request. You may change the middleware listed below as required.
    |
    */

    'middleware' => [
        'authenticate_session' => Laravel\Sanctum\Http\Middleware\AuthenticateSession::class,
        'encrypt_cookies' => Illuminate\Cookie\Middleware\EncryptCookies::class,
        'validate_csrf_token' => Illuminate\Foundation\Http\Middleware\ValidateCsrfToken::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Security Configuration
    |--------------------------------------------------------------------------
    |
    | Configuraciones adicionales de seguridad para tokens API
    |
    */

    'security' => [
        // Habilitar verificación de IP por token
        'verify_ip' => env('SANCTUM_VERIFY_IP', false),
        
        // Habilitar logging de uso de tokens
        'log_token_usage' => env('SANCTUM_LOG_USAGE', true),
        
        // Tiempo máximo de inactividad antes de requerir reautenticación (minutos)
        'max_inactivity' => env('SANCTUM_MAX_INACTIVITY', 120), // 2 horas
        
        // Límite de tokens concurrentes por usuario
        'max_tokens_per_user' => env('SANCTUM_MAX_TOKENS', 10),
        
        // Habilitar rotación de tokens
        'rotate_tokens' => env('SANCTUM_ROTATE_TOKENS', false),
        
        // Tiempo antes de expirar tokens inactivos (días)
        'purge_inactive_tokens_days' => env('SANCTUM_PURGE_DAYS', 30),
    ],

    /*
    |--------------------------------------------------------------------------
    | Token Types Configuration
    |--------------------------------------------------------------------------
    |
    | Configuración de diferentes tipos de tokens con diferentes duraciones
    |
    */

    'token_types' => [
        'api' => [
            'expiration' => 1440, // 24 horas
            'abilities' => ['*'],
        ],
        'web' => [
            'expiration' => 480, // 8 horas
            'abilities' => ['*'],
        ],
        'mobile' => [
            'expiration' => 10080, // 7 días
            'abilities' => ['*'],
        ],
        'integration' => [
            'expiration' => 43200, // 30 días
            'abilities' => [
                'invoices.create',
                'invoices.view',
                'boletas.create',
                'boletas.view',
            ],
        ],
        'read_only' => [
            'expiration' => 1440, // 24 horas
            'abilities' => [
                'invoices.view',
                'boletas.view',
                'reports.view',
            ],
        ],
    ],

];
