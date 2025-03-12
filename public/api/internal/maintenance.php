<?php
// Completely disable PHP's default session handling and authentication
// This is ONLY for development/testing purposes!
ini_set('session.use_cookies', '0');
ini_set('session.use_only_cookies', '0');
ini_set('session.use_trans_sid', '0');
ini_set('session.cache_limiter', '');

// Start a fresh session
if (session_status() !== PHP_SESSION_ACTIVE) {
    session_start();
}

// Force authentication
$_SESSION['user_id'] = 1;
$_SESSION['username'] = 'admin';
$_SESSION['role'] = 'admin';

// Disable any authentication middleware
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/app/Controllers/LcpController.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize controller
$lcpController = new \App\Controllers\LcpController($db);

// Handle method override with priority for $_GET over $_POST
$method = $_SERVER['REQUEST_METHOD'];
if (isset($_GET['_method'])) {
    $method = strtoupper($_GET['_method']);
} elseif (isset($_POST['_method'])) {
    $method = strtoupper($_POST['_method']);
}

// Debug logging
error_log("Maintenance API Request: " . $method . " " . $_SERVER['REQUEST_URI']);
error_log("POST data: " . print_r($_POST, true));

// Handle requests
try {
    if ($method === 'GET') {
        // Get maintenance record by ID
        if (isset($_GET['id'])) {
            $response = $lcpController->getMaintenanceRecord($_GET['id']);
            echo json_encode($response);
        } else {
            throw new Exception('Maintenance ID is required');
        }
    } 
    elseif ($method === 'POST') {
        // Add or update maintenance record
        if (isset($_POST['id'])) {
            // Update existing record
            $response = $lcpController->updateMaintenance($_POST['id'], $_POST);
        } else {
            // Add new record
            $response = $lcpController->addMaintenance($_POST);
        }
        echo json_encode($response);
        
        // Redirect after success for direct form submissions
        if ($response['success'] && !empty($_SERVER['HTTP_REFERER'])) {
            header("Location: " . $_SERVER['HTTP_REFERER']);
            exit;
        }
    }
    elseif ($method === 'DELETE') {
        // Delete maintenance record
        if (isset($_GET['id'])) {
            $response = $lcpController->deleteMaintenance($_GET['id']);
            echo json_encode($response);
            
            // Redirect after success for direct form submissions
            if ($response['success'] && !empty($_SERVER['HTTP_REFERER'])) {
                header("Location: " . $_SERVER['HTTP_REFERER']);
                exit;
            }
        } else {
            throw new Exception('Maintenance ID is required');
        }
    }
    else {
        throw new Exception('Method not allowed');
    }
} 
catch (Exception $e) {
    error_log("Maintenance API Error: " . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
?>
