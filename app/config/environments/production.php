<?php
return [
    'app' => [
        'debug' => false,
        'display_errors' => false,
        'log_level' => 'error',
        'maintenance_mode' => false,
        'force_ssl' => true,
        'session_secure' => true,
        'cookie_secure' => true,
        'cookie_httponly' => true,
        'cache_routes' => true,
        'cache_config' => true,
        'optimize_autoloader' => true
    ],

    'database' => [
        'strict' => true,
        'timezone' => '+00:00',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
        'persistent' => true,
        'pool' => [
            'min' => 5,
            'max' => 20
        ],
        'retry' => [
            'times' => 3,
            'sleep' => 100
        ]
    ],

    'cache' => [
        'default' => 'file',
        'prefix' => 'isp_prod_',
        'ttl' => 3600,
        'stores' => [
            'file' => [
                'driver' => 'file',
                'path' => STORAGE_PATH . '/cache'
            ],
            'redis' => [
                'driver' => 'redis',
                'connection' => 'default',
                'prefix' => 'isp_cache:'
            ]
        ]
    ],

    'session' => [
        'driver' => 'file',
        'lifetime' => 120,
        'expire_on_close' => false,
        'encrypt' => true,
        'lottery' => [2, 100],
        'cookie' => 'isp_session',
        'path' => '/',
        'domain' => null,
        'secure' => true,
        'http_only' => true,
        'same_site' => 'lax'
    ],

    'mail' => [
        'driver' => 'smtp',
        'host' => 'smtp.mailtrap.io',
        'port' => 2525,
        'encryption' => 'tls',
        'username' => null,
        'password' => null,
        'timeout' => 30
    ],

    'logging' => [
        'default' => 'daily',
        'channels' => [
            'daily' => [
                'driver' => 'daily',
                'path' => STORAGE_PATH . '/logs/isp.log',
                'level' => 'error',
                'days' => 7
            ],
            'syslog' => [
                'driver' => 'syslog',
                'level' => 'error'
            ]
        ]
    ],

    'security' => [
        'headers' => [
            'X-Frame-Options' => 'SAMEORIGIN',
            'X-XSS-Protection' => '1; mode=block',
            'X-Content-Type-Options' => 'nosniff',
            'Referrer-Policy' => 'strict-origin-when-cross-origin',
            'Content-Security-Policy' => "default-src 'self'; script-src 'self' 'unsafe-inline' 'unsafe-eval'; style-src 'self' 'unsafe-inline';",
            'Strict-Transport-Security' => 'max-age=31536000; includeSubDomains'
        ],
        'csrf_lifetime' => 7200,
        'password_timeout' => 10800,
        'max_login_attempts' => 5,
        'lockout_time' => 300
    ],

    'performance' => [
        'opcache' => true,
        'realpath_cache_size' => '4096K',
        'realpath_cache_ttl' => 600,
        'optimize_composer' => true,
        'preload' => [
            'enabled' => true,
            'path' => BASE_PATH . '/preload.php'
        ]
    ]
];
