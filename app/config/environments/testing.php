<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in testing mode, detailed error messages with
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
    | In testing mode, we show all errors to help with debugging.
    |
    */
    'error_reporting' => E_ALL,
    'display_errors' => true,

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    |
    | Testing database settings.
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
    | Testing cache settings.
    |
    */
    'cache' => [
        'enabled' => false,
        'ttl' => 60, // 1 minute in testing
    ],

    /*
    |--------------------------------------------------------------------------
    | Session Configuration
    |--------------------------------------------------------------------------
    |
    | Testing session settings.
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
    | Testing mail settings.
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
    | Testing API settings.
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
    | Testing CORS settings.
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
    | Testing security settings.
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
    | Testing asset settings.
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
    | Testing logging settings.
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
    | Testing network monitor settings.
    |
    */
    'network' => [
        'monitor' => [
            'enabled' => false,
            'interval' => 60, // 1 minute in testing
            'timeout' => 5,
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Configuration
    |--------------------------------------------------------------------------
    |
    | Testing payment gateway settings.
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
    | Testing SMS gateway settings.
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
    | Testing OLT settings.
    |
    */
    'olt' => [
        'simulation_mode' => true,
        'debug' => true,
    ],
];
