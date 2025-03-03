<?php
return [
    /*
    |--------------------------------------------------------------------------
    | Application Name
    |--------------------------------------------------------------------------
    |
    | This value is the name of your application. This value is used when the
    | framework needs to place the application's name in a notification or
    | any other location as required by the application or its packages.
    |
    */
    'name' => getenv('APP_NAME', 'ISP Management System'),

    /*
    |--------------------------------------------------------------------------
    | Application Environment
    |--------------------------------------------------------------------------
    |
    | This value determines the "environment" your application is currently
    | running in. This may determine how you prefer to configure various
    | services the application utilizes. Set this in your ".env" file.
    |
    */
    'env' => getenv('APP_ENV', 'production'),

    /*
    |--------------------------------------------------------------------------
    | Application Debug Mode
    |--------------------------------------------------------------------------
    |
    | When your application is in debug mode, detailed error messages with
    | stack traces will be shown on every error that occurs within your
    | application. If disabled, a simple generic error page is shown.
    |
    */
    'debug' => (bool) getenv('APP_DEBUG', false),

    /*
    |--------------------------------------------------------------------------
    | Application URL
    |--------------------------------------------------------------------------
    |
    | This URL is used by the console to properly generate URLs when using
    | the Artisan command line tool. You should set this to the root of
    | your application so that it is used when running Artisan tasks.
    |
    */
    'url' => getenv('APP_URL', 'http://localhost'),

    /*
    |--------------------------------------------------------------------------
    | Application Timezone
    |--------------------------------------------------------------------------
    |
    | Here you may specify the default timezone for your application, which
    | will be used by the PHP date and date-time functions. We have gone
    | ahead and set this to a sensible default for you out of the box.
    |
    */
    'timezone' => getenv('APP_TIMEZONE', 'UTC'),

    /*
    |--------------------------------------------------------------------------
    | Application Locale Configuration
    |--------------------------------------------------------------------------
    |
    | The application locale determines the default locale that will be used
    | by the translation service provider. You are free to set this value
    | to any of the locales which will be supported by the application.
    |
    */
    'locale' => getenv('APP_LOCALE', 'en'),

    /*
    |--------------------------------------------------------------------------
    | Encryption Key
    |--------------------------------------------------------------------------
    |
    | This key is used by the encryption service and should be set
    | to a random, 32 character string, otherwise these encrypted strings
    | will not be safe. Please do this before deploying an application!
    |
    */
    'key' => getenv('APP_KEY'),

    /*
    |--------------------------------------------------------------------------
    | Autoloaded Service Providers
    |--------------------------------------------------------------------------
    |
    | The service providers listed here will be automatically loaded on the
    | request to your application. Feel free to add your own services to
    | this array to grant expanded functionality to your applications.
    |
    */
    'providers' => [
        // Core Service Providers
        \App\Core\Container\ServiceProvider::class,
        
        // Application Service Providers
        \App\Providers\AppServiceProvider::class,
        \App\Providers\AuthServiceProvider::class,
        \App\Providers\EventServiceProvider::class,
        \App\Providers\RouteServiceProvider::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Class Aliases
    |--------------------------------------------------------------------------
    |
    | This array of class aliases will be registered when this application
    | is started. However, feel free to register as many as you wish as
    | the aliases are "lazy" loaded so they don't hinder performance.
    |
    */
    'aliases' => [
        'App' => \App\Core\Application::class,
        'Config' => \App\Core\Config::class,
        'DB' => \App\Core\Database::class,
        'Route' => \App\Core\Router::class,
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Settings
    |--------------------------------------------------------------------------
    |
    | Various security settings for the application.
    |
    */
    'security' => [
        'csrf_token_lifetime' => (int) getenv('CSRF_TOKEN_LIFETIME', 7200),
        'session_lifetime' => (int) getenv('SESSION_LIFETIME', 120),
        'password_timeout' => (int) getenv('PASSWORD_TIMEOUT', 10800),
    ],

    /*
    |--------------------------------------------------------------------------
    | Cache Settings
    |--------------------------------------------------------------------------
    |
    | Cache configuration settings.
    |
    */
    'cache' => [
        'driver' => getenv('CACHE_DRIVER', 'file'),
        'prefix' => getenv('CACHE_PREFIX', 'isp_'),
        'ttl' => (int) getenv('CACHE_TTL', 3600),
    ],

    /*
    |--------------------------------------------------------------------------
    | Storage Settings
    |--------------------------------------------------------------------------
    |
    | Storage configuration settings.
    |
    */
    'storage' => [
        'driver' => getenv('STORAGE_DRIVER', 'local'),
        'path' => getenv('STORAGE_PATH', 'storage'),
        'url' => getenv('STORAGE_URL', '/storage'),
    ],

    /*
    |--------------------------------------------------------------------------
    | API Settings
    |--------------------------------------------------------------------------
    |
    | API configuration settings.
    |
    */
    'api' => [
        'debug' => (bool) getenv('API_DEBUG', false),
        'throttle' => [
            'enabled' => (bool) getenv('API_THROTTLE_ENABLED', true),
            'max_attempts' => (int) getenv('API_THROTTLE_MAX_ATTEMPTS', 60),
            'decay_minutes' => (int) getenv('API_THROTTLE_DECAY_MINUTES', 1),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Network Monitor Settings
    |--------------------------------------------------------------------------
    |
    | Network monitoring configuration settings.
    |
    */
    'network' => [
        'monitor' => [
            'enabled' => (bool) getenv('NETWORK_MONITOR_ENABLED', true),
            'interval' => (int) getenv('NETWORK_MONITOR_INTERVAL', 300),
            'timeout' => (int) getenv('NETWORK_MONITOR_TIMEOUT', 30),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Backup Settings
    |--------------------------------------------------------------------------
    |
    | Backup configuration settings.
    |
    */
    'backup' => [
        'enabled' => (bool) getenv('BACKUP_ENABLED', true),
        'path' => getenv('BACKUP_PATH', 'storage/backups'),
        'disks' => explode(',', getenv('BACKUP_DISKS', 'local')),
        'notification_email' => getenv('BACKUP_NOTIFICATION_EMAIL'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Payment Gateway Settings
    |--------------------------------------------------------------------------
    |
    | Payment gateway configuration settings.
    |
    */
    'payment' => [
        'gateway' => getenv('PAYMENT_GATEWAY', 'stripe'),
        'stripe' => [
            'key' => getenv('STRIPE_KEY'),
            'secret' => getenv('STRIPE_SECRET'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | SMS Gateway Settings
    |--------------------------------------------------------------------------
    |
    | SMS gateway configuration settings.
    |
    */
    'sms' => [
        'gateway' => getenv('SMS_GATEWAY', 'twilio'),
        'twilio' => [
            'sid' => getenv('TWILIO_SID'),
            'token' => getenv('TWILIO_TOKEN'),
            'from' => getenv('TWILIO_FROM'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | OLT Settings
    |--------------------------------------------------------------------------
    |
    | OLT configuration settings.
    |
    */
    'olt' => [
        'enabled' => (bool) getenv('OLT_ENABLED', true),
        'host' => getenv('OLT_HOST', 'localhost'),
        'port' => (int) getenv('OLT_PORT', 23),
        'username' => getenv('OLT_USERNAME', 'admin'),
        'password' => getenv('OLT_PASSWORD', 'admin'),
    ],
];
