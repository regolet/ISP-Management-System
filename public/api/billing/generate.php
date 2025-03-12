<?php
session_start();
header('Content-Type: application/json');

require_once dirname(__DIR__, 3) . '/config/database.php';
require_once dirname(__DIR__, 3) . '/app/Controllers/BillingController.php';
require_once dirname(__DIR__, 3) . '/app/Controllers/AuthController.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Check if user is logged in and is admin
if (!$auth->isLoggedIn() || !$auth->hasRole('admin')) {
    http_response_code(403);
    echo json_encode([
        'success' => false,
        'message' => 'Unauthorized. Admin privileges required.'
    ]);
    exit;
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Billing Controller
$billingController = new \App\Controllers\BillingController($db);

// This endpoint only accepts POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode([
        'success' => false,
        'message' => 'Method not allowed. Only POST requests are accepted.'
    ]);
    exit;
}

try {
    // Generate monthly invoices for all active subscriptions
    $result = $billingController->generateInvoices();
    
    if (isset($result['generated'])) {
        echo json_encode([
            'success' => true,
            'message' => 'Invoices generated successfully',
            'generated' => $result['generated'],
            'total_amount' => $result['total_amount'] ?? 0
        ]);
    } else {
        echo json_encode([
            'success' => $result['success'],
            'message' => $result['message'] ?? 'Error generating invoices'
        ]);
    }
} catch (\Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
