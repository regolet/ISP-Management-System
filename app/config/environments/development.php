<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in development mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */
    'debug' => true,

    /*
    |--------------------------------------------------------------------------
    | Error Reporting
    |--------------------------------------------------------------------------
    |
    | In development mode, we show all errors to help with debugging.
    |
    */
    'error_reporting' => E_ALL,
    'display_errors' => true,

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Development database settings.
    |
    */
    'database' => [
        'debug' => true,
        'log_queries' => true,
        'strict' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Configuration
    |--------------------------------------------------------------------------
    |
    | Development cache settings.
    |
    */
    'cache' => [
        'enabled' => true,
        'ttl' => 60, // 1 minute in development
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    |
    | Development session settings.
    |
    */
    'session' => [
        'secure' => false,
        'lifetime' => 120,
    ],

    /*
    |--------------------------------------------------------------------------
    | Mail Configuration
    |--------------------------------------------------------------------------
    |
    | Development mail settings.
    |
    */
    'mail' => [
        'debug' => true,
        'pretend' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | API Configuration
    |--------------------------------------------------------------------------
    |
    | Development API settings.
    |
    */
    'api' => [
        'debug' => true,
        'throttle' => [
            'enabled' => false,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | CORS Configuration
    |--------------------------------------------------------------------------
    |
    | Development CORS settings.
    |
    */
    'cors' => [
        'allowed_origins' => ['*'],
        'allowed_methods' => ['*'],
        'allowed_headers' => ['*'],
        'exposed_headers' => [],
        'max_age' => 0,
        'supports_credentials' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration
    |--------------------------------------------------------------------------
    |
    | Development security settings.
    |
    */
    'security' => [
        'csrf_protection' => true,
        'ssl_required' => false,
        'password_min_length' => 6,
    ],

    /*
    |--------------------------------------------------------------------------
    | Assets Configuration
    |--------------------------------------------------------------------------
    |
    | Development asset settings.
    |
    */
    'assets' => [
        'minify' => false,
        'cache_busting' => false,
        'source_maps' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Logging Configuration
    |--------------------------------------------------------------------------
    |
    | Development logging settings.
    |
    */
    'logging' => [
        'level' => 'debug',
        'detailed' => true,
        'query_log' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | Network Monitor Configuration
    |--------------------------------------------------------------------------
    |
    | Development network monitor settings.
    |
    */
    'network' => [
        'monitor' => [
            'enabled' => true,
            'interval' => 60, // 1 minute in development
            'timeout' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Development payment gateway settings.
    |
    */
    'payment' => [
        'test_mode' => true,
        'debug' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Development SMS gateway settings.
    |
    */
    'sms' => [
        'test_mode' => true,
        'debug' => true,
    ],

    /*
    |--------------------------------------------------------------------------
    | OLT Configuration
    |--------------------------------------------------------------------------
    |
    | Development OLT settings.
    |
    */
    'olt' => [
        'simulation_mode' => true,
        'debug' => true,
    ],
];
