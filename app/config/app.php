<?php
return [
    // Application settings
    'name' => 'ISP Management System',
    'version' => '1.0.0',
    'debug' => true, // Set to false in production
    'timezone' => 'Asia/Manila',
    'url' => 'http://localhost',
    'charset' => 'UTF-8',

    // Session configuration
    'session' => [
        'lifetime' => 1800, // 30 minutes
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true if using HTTPS
        'httponly' => true
    ],

    // File upload settings
    'upload' => [
        'max_size' => 5242880, // 5MB
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
            'application/vnd.ms-excel',
            'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet'
        ],
        'path' => 'uploads'
    ],

    // Email settings
    'mail' => [
        'from_address' => 'noreply@example.com',
        'from_name' => 'ISP Management System',
        'smtp_host' => 'smtp.mailtrap.io',
        'smtp_port' => 2525,
        'smtp_user' => '',
        'smtp_pass' => '',
        'smtp_crypto' => 'tls'
    ],

    // Authentication settings
    'auth' => [
        'password_min_length' => 8,
        'password_requires_special' => true,
        'password_requires_number' => true,
        'password_requires_mixed_case' => true,
        'login_attempts' => 5,
        'lockout_time' => 900 // 15 minutes
    ],

    // Backup settings
    'backup' => [
        'max_files' => 10,
        'path' => 'storage/backups',
        'filename_prefix' => 'backup_',
        'compress' => true
    ],

    // Audit log settings
    'audit' => [
        'enabled' => true,
        'retention_days' => 90,
        'log_request_data' => true,
        'log_response_data' => false
    ],

    // Currency settings
    'currency' => [
        'code' => 'PHP',
        'symbol' => '₱',
        'position' => 'before', // 'before' or 'after'
        'decimals' => 2,
        'decimal_separator' => '.',
        'thousands_separator' => ','
    ],

    // Date and time settings
    'datetime' => [
        'date_format' => 'Y-m-d',
        'time_format' => 'H:i:s',
        'datetime_format' => 'Y-m-d H:i:s'
    ],

    // Pagination settings
    'pagination' => [
        'per_page' => 10,
        'num_links' => 5
    ],

    // Cache settings
    'cache' => [
        'driver' => 'file', // file, redis, memcached
        'path' => 'storage/cache',
        'lifetime' => 3600 // 1 hour
    ],

    // API settings
    'api' => [
        'throttle' => [
            'enabled' => true,
            'max_attempts' => 60,
            'decay_minutes' => 1
        ],
        'token_lifetime' => 86400 // 24 hours
    ],

    // Security settings
    'security' => [
        'csrf_token_lifetime' => 7200, // 2 hours
        'jwt_secret' => 'your-secret-key',
        'jwt_algorithm' => 'HS256',
        'allowed_hosts' => ['localhost', '127.0.0.1'],
        'cors' => [
            'allowed_origins' => ['*'],
            'allowed_methods' => ['GET', 'POST', 'PUT', 'DELETE', 'OPTIONS'],
            'allowed_headers' => ['Content-Type', 'Authorization'],
            'expose_headers' => [],
            'max_age' => 0,
            'supports_credentials' => false
        ]
    ]
];
