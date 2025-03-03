<?php
/**
 * ISP Management System - Root Redirect
 * 
 * This file redirects all requests to the public directory for security.
 * The application's actual entry point is in public/index.php.
 */

// Get the requested URI
$uri = urldecode(
    parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH) ?? ''
);

// Remove any dots from the URI to prevent directory traversal
$uri = str_replace('..', '', $uri);

// Build the path to the public directory
$publicPath = __DIR__ . DIRECTORY_SEPARATOR . 'public';

// Check if we're accessing a file directly
if ($uri !== '/' && file_exists($publicPath . $uri)) {
    // If it's a PHP file, include it
    if (substr($uri, -4) === '.php') {
        require_once $publicPath . $uri;
        exit;
    }
    
    // For other files, serve them with proper content type
    $mimeTypes = [
        'css' => 'text/css',
        'js' => 'application/javascript',
        'jpg' => 'image/jpeg',
        'jpeg' => 'image/jpeg',
        'png' => 'image/png',
        'gif' => 'image/gif',
        'svg' => 'image/svg+xml',
        'ico' => 'image/x-icon',
        'pdf' => 'application/pdf',
        'json' => 'application/json',
        'xml' => 'application/xml',
    ];
    
    $extension = strtolower(pathinfo($uri, PATHINFO_EXTENSION));
    if (isset($mimeTypes[$extension])) {
        header('Content-Type: ' . $mimeTypes[$extension]);
        readfile($publicPath . $uri);
        exit;
    }
}

// If no specific file is requested or it's not found,
// redirect to the main application entry point
require_once $publicPath . DIRECTORY_SEPARATOR . 'index.php';
