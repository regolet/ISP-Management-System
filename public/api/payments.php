<?php
// Disable PHP's default error display to avoid HTML in JSON output
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Ensure all output is captured for JSON
ob_start();

session_start();
header('Content-Type: application/json');

// Proper error handling to ensure we always return JSON
try {
    require_once dirname(__DIR__, 2) . '/config/database.php';
    require_once dirname(__DIR__, 2) . '/app/Controllers/BillingController.php';
    require_once dirname(__DIR__, 2) . '/app/Controllers/AuthController.php';

    // Initialize Auth Controller
    $auth = new \App\Controllers\AuthController();

    // Check if user is logged in
    if (!$auth->isLoggedIn()) {
        echo json_encode([
            'success' => false,
            'message' => 'Unauthorized'
        ]);
        exit;
    }

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Initialize Billing Controller
    $billingController = new \App\Controllers\BillingController($db);

    // Handle different HTTP methods
    $method = $_SERVER['REQUEST_METHOD'];

    switch ($method) {
        case 'GET':
            // Get a specific payment or list of payments
            if (isset($_GET['id'])) {
                $paymentId = (int)$_GET['id'];
                $payment = $billingController->getPayment($paymentId);
                
                if ($payment) {
                    echo json_encode([
                        'success' => true,
                        'payment' => $payment
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Payment not found'
                    ]);
                }
            } else {
                // Get list of payments with pagination and filters
                $params = [
                    'page' => $_GET['page'] ?? 1,
                    'per_page' => $_GET['per_page'] ?? 10,
                    'search' => $_GET['search'] ?? '',
                    'status' => $_GET['status'] ?? '',
                    'sort' => $_GET['sort'] ?? 'payment_date',
                    'order' => $_GET['order'] ?? 'DESC'
                ];
                
                $payments = $billingController->getPayments($params);
                echo json_encode([
                    'success' => true,
                    'data' => $payments
                ]);
            }
            break;
            
        case 'POST':
            // Create a new payment
            // For POST requests, read from php://input to get the raw POST data
            $rawData = file_get_contents('php://input');
            $data = json_decode($rawData, true);
            
            // If JSON parsing failed or is empty, fallback to POST array
            if ($data === null) {
                $data = $_POST;
            }
            
            // Check if we have the necessary data
            if (empty($data) || empty($data['billing_id']) || !isset($data['amount'])) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Required payment data is missing',
                    'data_received' => $data
                ]);
                break;
            }
            
            // Add default status if not provided
            if (empty($data['status'])) {
                $data['status'] = 'completed';
            }
            
            // Log the data we're about to process
            error_log('Processing payment data: ' . json_encode($data));
            
            // Create the payment
            $result = $billingController->createPayment($data);
            
            // Return the result
            echo json_encode($result);
            break;
            
        case 'PUT':
            // Update existing payment
            $data = json_decode(file_get_contents('php://input'), true);
            if ($data === null) {
                parse_str(file_get_contents('php://input'), $data);
            }
            
            $paymentId = $_GET['id'] ?? null;
            if (!$paymentId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment ID is required'
                ]);
                break;
            }
            
            $result = $billingController->updatePayment($paymentId, $data);
            echo json_encode($result);
            break;
            
        case 'DELETE':
            // Delete a payment
            $paymentId = $_GET['id'] ?? null;
            if (!$paymentId) {
                parse_str(file_get_contents('php://input'), $data);
                $paymentId = $data['id'] ?? null;
            }
            
            if (!$paymentId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Payment ID is required'
                ]);
                break;
            }
            
            $result = $billingController->deletePayment($paymentId);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Unsupported request method'
            ]);
            break;
    }
    
    // Clear any buffered output to ensure clean JSON response
    ob_end_flush();
    
} catch (Exception $e) {
    // Discard any output so far to avoid mixing HTML and JSON
    ob_end_clean();
    
    // Ensure we always return JSON even when errors happen
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ]);
}
