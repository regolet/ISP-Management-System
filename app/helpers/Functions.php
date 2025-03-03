<?php

/**
 * Format currency amount
 * @param float $amount Amount to format
 * @param string $currency Currency code (default: from config)
 * @return string Formatted amount
 */
function formatCurrency($amount, $currency = null) {
    try {
        $config = require dirname(__DIR__, 2) . '/app/config/app.php';
        
        // Default values if config is not available
        $defaults = [
            'code' => 'USD',
            'symbol' => '$',
            'position' => 'before',
            'decimals' => 2,
            'decimal_separator' => '.',
            'thousands_separator' => ',',
        ];

        // Get currency settings from config or use defaults
        $settings = $config['currency'] ?? $defaults;
        
        $currency = $currency ?? $settings['code'];
        $symbol = $settings['symbol'];
        $decimals = $settings['decimals'];
        $dec_point = $settings['decimal_separator'];
        $thousands_sep = $settings['thousands_separator'];
        $position = $settings['position'];

        $formatted = number_format($amount, $decimals, $dec_point, $thousands_sep);
        
        return $position === 'before' ? $symbol . $formatted : $formatted . $symbol;
    } catch (\Throwable $e) {
        // If anything goes wrong, return a basic formatted number
        return '$' . number_format($amount, 2);
    }
}

/**
 * Format date/time
 * @param string $datetime Date/time string
 * @param bool $withTime Include time in output
 * @return string Formatted date/time
 */
function formatDate($datetime, $withTime = false) {
    try {
        $config = require dirname(__DIR__, 2) . '/app/config/app.php';
        
        if (!$datetime) return '';
        
        // Default formats if config is not available
        $defaults = [
            'date_format' => 'Y-m-d',
            'datetime_format' => 'Y-m-d H:i:s',
            'time_format' => 'H:i:s'
        ];

        // Get datetime settings from config or use defaults
        $settings = $config['datetime'] ?? $defaults;
        
        $format = $withTime ? 
            $settings['datetime_format'] : 
            $settings['date_format'];
        
        return date($format, strtotime($datetime));
    } catch (\Throwable $e) {
        // If anything goes wrong, return a basic formatted date
        return $withTime ? 
            date('Y-m-d H:i:s', strtotime($datetime)) : 
            date('Y-m-d', strtotime($datetime));
    }
}

/**
 * Format file size
 * @param int $bytes Size in bytes
 * @param int $precision Decimal precision
 * @return string Formatted size
 */
function formatBytes($bytes, $precision = 2) {
    if ($bytes <= 0) return '0 B';

    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $base = log($bytes) / log(1024);
    $pow = floor($base);

    return round(pow(1024, $base - $pow), $precision) . ' ' . $units[$pow];
}

/**
 * Generate random string
 * @param int $length String length
 * @param string $type Type of string (alpha, numeric, alphanumeric, special)
 * @return string Random string
 */
function generateRandomString($length = 10, $type = 'alphanumeric') {
    $chars = match($type) {
        'alpha' => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ',
        'numeric' => '0123456789',
        'special' => '!@#$%^&*()_+-=[]{}|;:,.<>?',
        default => 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789'
    };

    $str = '';
    $max = strlen($chars) - 1;
    
    for ($i = 0; $i < $length; $i++) {
        $str .= $chars[random_int(0, $max)];
    }
    
    return $str;
}

/**
 * Clean input data
 * @param string $input Input to clean
 * @return string Cleaned input
 */
function cleanInput($input) {
    if (is_array($input)) {
        return array_map('cleanInput', $input);
    }
    
    $input = trim($input);
    $input = stripslashes($input);
    return htmlspecialchars($input, ENT_QUOTES, 'UTF-8');
}

/**
 * Get config value
 * @param string $key Config key (dot notation)
 * @param mixed $default Default value if not found
 * @return mixed Config value
 */
function config($key, $default = null) {
    static $config = null;
    
    if ($config === null) {
        $config = require dirname(__DIR__, 2) . '/app/config/app.php';
    }
    
    $keys = explode('.', $key);
    $value = $config;
    
    foreach ($keys as $k) {
        if (!isset($value[$k])) {
            return $default;
        }
        $value = $value[$k];
    }
    
    return $value;
}

/**
 * Get current authenticated user
 * @return array|null User data
 */
function auth() {
    if (!isset($_SESSION['user_id'])) {
        return null;
    }

    static $user = null;
    
    if ($user === null) {
        $db = new App\Core\Database();
        $stmt = $db->prepare("SELECT * FROM users WHERE id = ?");
        $stmt->bind_param('i', $_SESSION['user_id']);
        $stmt->execute();
        $user = $stmt->get_result()->fetch_assoc();
    }
    
    return $user;
}

/**
 * Check if user has permission
 * @param string $permission Permission slug
 * @return bool Has permission
 */
function hasPermission($permission) {
    if (!isset($_SESSION['user_id'])) {
        return false;
    }

    static $permissions = null;
    
    if ($permissions === null) {
        $permissionModel = new App\Models\Admin\Permission();
        $permissions = $permissionModel->getUserPermissions($_SESSION['user_id']);
        $permissions = array_column($permissions, 'slug');
    }
    
    return in_array($permission, $permissions);
}

/**
 * Get validation error
 * @param string $field Field name
 * @param array $errors Error array
 * @return string Error message
 */
function getError($field, $errors) {
    return $errors[$field] ?? '';
}

/**
 * Check if field has error
 * @param string $field Field name
 * @param array $errors Error array
 * @return bool Has error
 */
function hasError($field, $errors) {
    return isset($errors[$field]);
}

/**
 * Get old input value
 * @param string $field Field name
 * @param mixed $default Default value
 * @return mixed Old value
 */
function old($field, $default = '') {
    return $_POST[$field] ?? $default;
}

/**
 * Generate CSRF token
 * @return string CSRF token
 */
function csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Check if CSRF token is valid
 * @param string $token Token to check
 * @return bool Is valid
 */
function csrf_check($token) {
    return isset($_SESSION['csrf_token']) && hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Set flash message
 * @param string $type Message type (success, error, info, warning)
 * @param string $message Message content
 */
function setFlash($type, $message) {
    $_SESSION['flash'][$type] = $message;
}

/**
 * Get flash message
 * @param string $type Message type
 * @return string|null Message content
 */
function getFlash($type) {
    if (!isset($_SESSION['flash'][$type])) {
        return null;
    }
    
    $message = $_SESSION['flash'][$type];
    unset($_SESSION['flash'][$type]);
    return $message;
}

/**
 * Redirect to URL
 * @param string $url URL to redirect to
 */
function redirect($url) {
    header("Location: $url");
    exit;
}

/**
 * Get current URL
 * @return string Current URL
 */
function currentUrl() {
    return (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . 
           "://$_SERVER[HTTP_HOST]$_SERVER[REQUEST_URI]";
}

/**
 * Asset URL helper
 * @param string $path Asset path
 * @return string Asset URL
 */
function asset($path) {
    $config = require dirname(__DIR__, 2) . '/app/config/app.php';
    return rtrim($config['url'], '/') . '/' . ltrim($path, '/');
}
