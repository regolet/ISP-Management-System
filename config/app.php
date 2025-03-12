<?php
// Application Configuration

return [
    // Application Settings
    'app' => [
        'name' => 'ISP Management System',
        'version' => '1.0.0',
        'debug' => true, // Set to false in production
        'timezone' => 'UTC',
        'locale' => 'en',
        'url' => 'http://localhost:8000',
    ],

    // Database Settings
    'database' => [
        'host' => 'localhost',
        'name' => 'isp-management',
        'user' => 'root',
        'password' => '',
        'charset' => 'utf8mb4',
        'collation' => 'utf8mb4_unicode_ci',
    ],

    // Session Settings
    'session' => [
        'lifetime' => 1800, // 30 minutes
        'refresh_after' => 1740, // 29 minutes - show warning before expiry
        'path' => '/',
        'domain' => '',
        'secure' => false, // Set to true in production with HTTPS
        'httponly' => true,
    ],

    // Security Settings
    'security' => [
        'csrf_protection' => true,
        'password_min_length' => 8,
        'password_requires_special' => true,
        'password_requires_number' => true,
        'password_requires_uppercase' => true,
        'max_login_attempts' => 5,
        'lockout_time' => 900, // 15 minutes
    ],

    // Pagination Settings
    'pagination' => [
        'items_per_page' => 10,
        'max_items_per_page' => 100,
    ],

    // File Upload Settings
    'uploads' => [
        'max_size' => 5242880, // 5MB
        'allowed_types' => [
            'image/jpeg',
            'image/png',
            'image/gif',
            'application/pdf',
            'application/msword',
            'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        ],
        'path' => 'uploads/',
    ],

    // Email Settings
    'mail' => [
        'from_address' => 'noreply@ispmanager.com',
        'from_name' => 'ISP Management System',
        'smtp_host' => 'smtp.mailtrap.io',
        'smtp_port' => 2525,
        'smtp_username' => '',
        'smtp_password' => '',
        'smtp_encryption' => 'tls',
    ],

    // Billing Settings
    'billing' => [
        'currency' => 'USD',
        'currency_symbol' => '$',
        'tax_rate' => 0.10, // 10%
        'payment_grace_period' => 5, // days
        'auto_suspend_after' => 15, // days
    ],

    // Support Ticket Settings
    'support' => [
        'default_priority' => 'medium',
        'auto_close_after' => 72, // hours
        'notify_admin_priority' => ['high', 'urgent'],
    ],

    // Network Settings
    'network' => [
        'ip_range' => '192.168.0.0/24',
        'dns_servers' => [
            'primary' => '8.8.8.8',
            'secondary' => '8.8.4.4',
        ],
        'speed_units' => 'Mbps',
        'data_units' => 'GB',
    ],

    // Notification Settings
    'notifications' => [
        'channels' => ['email', 'database', 'sms'],
        'email_notifications' => [
            'payment_received' => true,
            'payment_due' => true,
            'ticket_update' => true,
            'service_interruption' => true,
        ],
        'sms_notifications' => [
            'payment_due' => true,
            'service_interruption' => true,
        ],
    ],

    // Logging Settings
    'logging' => [
        'enabled' => true,
        'level' => 'debug', // debug, info, warning, error
        'file' => 'logs/app.log',
        'max_files' => 30,
    ],

    // Cache Settings
    'cache' => [
        'driver' => 'file', // file, redis, memcached
        'path' => 'cache/',
        'lifetime' => 3600, // 1 hour
    ],

    // Assets
    'assets' => [
        'css' => [
            'main' => '/assets/css/main.css',
            'dashboard' => '/assets/css/dashboard.css',
            'sidebar' => '/assets/css/sidebar.css',
        ],
        'js' => [
            'main' => '/assets/js/main.js',
            'dashboard' => '/assets/js/dashboard.js',
            'sidebar' => '/assets/js/sidebar.js',
        ],
    ],
];
