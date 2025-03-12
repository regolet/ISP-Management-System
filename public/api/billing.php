<?php
session_start();
header('Content-Type: application/json');

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

try {
    switch ($method) {
        case 'GET':
            // Get a specific invoice or list of invoices
            if (isset($_GET['id'])) {
                $invoiceId = (int)$_GET['id'];
                $invoice = $billingController->getInvoice($invoiceId);
                
                if ($invoice) {
                    echo json_encode([
                        'success' => true,
                        'invoice' => $invoice['invoice'],
                        'payments' => $invoice['payments']
                    ]);
                } else {
                    echo json_encode([
                        'success' => false,
                        'message' => 'Invoice not found'
                    ]);
                }
            } else {
                // Get list of invoices with pagination and filters
                $params = [
                    'page' => $_GET['page'] ?? 1,
                    'per_page' => $_GET['per_page'] ?? 10,
                    'search' => $_GET['search'] ?? '',
                    'status' => $_GET['status'] ?? '',
                    'sort' => $_GET['sort'] ?? 'billing_date',
                    'order' => $_GET['order'] ?? 'DESC'
                ];
                
                $invoices = $billingController->getInvoices($params);
                echo json_encode([
                    'success' => true,
                    'data' => $invoices
                ]);
            }
            break;
            
        case 'POST':
            // Create a new invoice
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                $data = $_POST;
            }
            
            if (isset($_GET['action']) && $_GET['action'] === 'generate') {
                // Generate invoices for all active subscriptions
                $result = $billingController->generateMonthlyInvoices();
                echo json_encode($result);
            } else {
                // Create new invoice
                $result = $billingController->createInvoice($data);
                echo json_encode($result);
            }
            break;
            
        case 'PUT':
            // Update existing invoice
            $data = json_decode(file_get_contents('php://input'), true);
            if (!$data) {
                parse_str(file_get_contents('php://input'), $data);
            }
            
            $invoiceId = $_GET['id'] ?? null;
            if (!$invoiceId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invoice ID is required'
                ]);
                break;
            }
            
            $result = $billingController->updateInvoice($invoiceId, $data);
            echo json_encode($result);
            break;
            
        case 'DELETE':
            // Delete an invoice
            $invoiceId = $_GET['id'] ?? null;
            if (!$invoiceId) {
                parse_str(file_get_contents('php://input'), $data);
                $invoiceId = $data['id'] ?? null;
            }
            
            if (!$invoiceId) {
                echo json_encode([
                    'success' => false,
                    'message' => 'Invoice ID is required'
                ]);
                break;
            }
            
            $result = $billingController->deleteInvoice($invoiceId);
            echo json_encode($result);
            break;
            
        default:
            echo json_encode([
                'success' => false,
                'message' => 'Unsupported request method'
            ]);
            break;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'Server error: ' . $e->getMessage()
    ]);
}
