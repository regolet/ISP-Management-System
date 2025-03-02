<?php
return [
    // Default database connection
    'default' => 'mysql',

    // Available database connections
    'connections' => [
        'mysql' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'isp',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ],

        'testing' => [
            'driver' => 'mysql',
            'host' => 'localhost',
            'port' => 3306,
            'database' => 'isp_testing',
            'username' => 'root',
            'password' => '',
            'charset' => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
            'prefix' => '',
            'strict' => true,
            'engine' => 'InnoDB',
            'options' => [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
                PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
            ]
        ]
    ],

    // Migration settings
    'migrations' => [
        'table' => 'migrations',
        'path' => APP_ROOT . '/database/migrations'
    ],

    // Backup settings
    'backup' => [
        'path' => APP_ROOT . '/storage/backups',
        'filename_prefix' => 'backup_',
        'compress' => true
    ],

    // Connection pool settings
    'pool' => [
        'min' => 1,
        'max' => 20,
        'timeout' => 5
    ],

    // Query logging
    'query_log' => [
        'enabled' => true,
        'threshold' => 100, // Log queries taking longer than 100ms
        'path' => APP_ROOT . '/storage/logs/query.log'
    ],

    // Database error logging
    'error_log' => [
        'enabled' => true,
        'path' => APP_ROOT . '/storage/logs/database.log'
    ],

    // Connection retry settings
    'retry' => [
        'max_attempts' => 3,
        'delay' => 100, // milliseconds
        'multiplier' => 2 // exponential backoff
    ]
];
