<?php
/**
 * Get configuration value using dot notation
 * @param string $key Dot notation key (e.g., 'app.name')
 * @param mixed $default Default value if key not found
 * @return mixed
 */
function config($key, $default = null) {
    static $config = null;
    
    // Load config if not loaded
    if ($config === null) {
        $config = require dirname(__DIR__) . '/config/app.php';
    }
    
    // Split the key into parts
    $parts = explode('.', $key);
    $value = $config;
    
    // Traverse the config array
    foreach ($parts as $part) {
        if (!isset($value[$part])) {
            return $default;
        }
        $value = $value[$part];
    }
    
    return $value;
}

/**
 * Format currency amount
 * @param float $amount
 * @param string $currency
 * @return string
 */
function format_currency($amount, $currency = null) {
    $currency = $currency ?? config('billing.currency', 'USD');
    $symbol = config('billing.currency_symbol', '$');
    return $symbol . number_format($amount, 2);
}

/**
 * Format file size
 * @param int $bytes
 * @return string
 */
function format_bytes($bytes) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    $bytes /= pow(1024, $pow);
    return round($bytes, 2) . ' ' . $units[$pow];
}

/**
 * Format date
 * @param string $date
 * @param string $format
 * @return string
 */
function format_date($date, $format = 'Y-m-d') {
    return date($format, strtotime($date));
}

/**
 * Format datetime
 * @param string $datetime
 * @param string $format
 * @return string
 */
function format_datetime($datetime, $format = 'Y-m-d H:i:s') {
    return date($format, strtotime($datetime));
}

/**
 * Get time ago string
 * @param string $datetime
 * @return string
 */
function time_ago($datetime) {
    $time = strtotime($datetime);
    $now = time();
    $diff = $now - $time;
    
    if ($diff < 60) {
        return 'just now';
    } elseif ($diff < 3600) {
        $mins = floor($diff / 60);
        return $mins . ' minute' . ($mins > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 86400) {
        $hours = floor($diff / 3600);
        return $hours . ' hour' . ($hours > 1 ? 's' : '') . ' ago';
    } elseif ($diff < 604800) {
        $days = floor($diff / 86400);
        return $days . ' day' . ($days > 1 ? 's' : '') . ' ago';
    } else {
        return format_date($datetime);
    }
}

/**
 * Generate random string
 * @param int $length
 * @return string
 */
function random_string($length = 10) {
    return bin2hex(random_bytes($length));
}

/**
 * Sanitize input
 * @param string $input
 * @return string
 */
function sanitize($input) {
    return htmlspecialchars(strip_tags($input), ENT_QUOTES, 'UTF-8');
}

/**
 * Get client IP address
 * @return string
 */
function get_client_ip() {
    $ipaddress = '';
    if (isset($_SERVER['HTTP_CLIENT_IP']))
        $ipaddress = $_SERVER['HTTP_CLIENT_IP'];
    else if(isset($_SERVER['HTTP_X_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_X_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_X_FORWARDED'];
    else if(isset($_SERVER['HTTP_FORWARDED_FOR']))
        $ipaddress = $_SERVER['HTTP_FORWARDED_FOR'];
    else if(isset($_SERVER['HTTP_FORWARDED']))
        $ipaddress = $_SERVER['HTTP_FORWARDED'];
    else if(isset($_SERVER['REMOTE_ADDR']))
        $ipaddress = $_SERVER['REMOTE_ADDR'];
    else
        $ipaddress = 'UNKNOWN';
    return $ipaddress;
}

/**
 * Log activity
 * @param string $type
 * @param string $description
 * @param int|null $user_id
 */
function log_activity_db($type, $description, $user_id = null) {
    global $db;
    try {
        $user_id = $user_id ?? ($_SESSION['user_id'] ?? null);
        $stmt = $db->prepare("
            INSERT INTO activity_logs 
            (user_id, activity_type, description, ip_address) 
            VALUES (?, ?, ?, ?)
        ");
        $stmt->execute([
            $user_id,
            $type,
            $description,
            get_client_ip()
        ]);
    } catch (Exception $e) {
        error_log("Error logging activity: " . $e->getMessage());
    }
}

/**
 * Redirect with flash message
 * @param string $url
 * @param string $message
 * @param string $type
 */
function redirect_with_message($url, $message, $type = 'success') {
    $_SESSION['flash_message'] = $message;
    $_SESSION['flash_type'] = $type;
    header("Location: $url");
    exit();
}

/**
 * Get flash message
 * @return array|null
 */
function get_flash_message() {
    if (isset($_SESSION['flash_message'])) {
        $message = [
            'message' => $_SESSION['flash_message'],
            'type' => $_SESSION['flash_type'] ?? 'info'
        ];
        unset($_SESSION['flash_message'], $_SESSION['flash_type']);
        return $message;
    }
    return null;
}

/**
 * Debug function
 * @param mixed $var
 * @param bool $die
 */
function dd($var, $die = true) {
    echo '<pre>';
    var_dump($var);
    echo '</pre>';
    if ($die) die();
}

/**
 * Check if user has a specific role
 * 
 * @param string $role The role to check for
 * @return bool Whether user has the role
 */
function has_role($role)
{
    return isset($_SESSION['role']) && $_SESSION['role'] === $role;
}

// require_auth function is already defined in app/init.php

/**
 * Log activity for audit purposes
 * 
 * @param string $action The action being performed
 * @param string $description Description of the activity
 * @param int|null $user_id The user ID (optional)
 * @return void
 */
function log_activity($action, $description, $user_id = null)
{
    // Simple logging implementation
    // In a real application, you would log to database
    $user_id = $user_id ?? ($_SESSION['user_id'] ?? 0);
    $log_entry = date('Y-m-d H:i:s') . " | User ID: $user_id | $action | $description\n";

    // Append to log file
    file_put_contents(dirname(__DIR__) . '/logs/activity.log', $log_entry, FILE_APPEND);
}