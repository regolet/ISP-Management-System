<?php
session_start();

// Set response headers
header('Content-Type: application/json');
header('Access-Control-Allow-Origin: ' . (isset($_SERVER['HTTP_ORIGIN']) ? $_SERVER['HTTP_ORIGIN'] : '*'));
header('Access-Control-Allow-Credentials: true');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, X-CSRF-TOKEN');
header('Access-Control-Max-Age: 3600');

// Handle preflight requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__, 2) . '/app/Controllers/SubscriptionController.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Check authentication
if (!$auth->isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit();
}

// Verify CSRF token
if (!isset($_SERVER['HTTP_X_CSRF_TOKEN']) || 
    !isset($_SESSION['csrf_token']) || 
    $_SERVER['HTTP_X_CSRF_TOKEN'] !== $_SESSION['csrf_token']) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Invalid CSRF token']);
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Subscription Controller
$subscriptionController = new \App\Controllers\SubscriptionController($db);

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get subscription ID and action from URL parameters
$subscriptionId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($subscriptionId) {
                // Get single subscription
                $result = $subscriptionController->getSubscription($subscriptionId);
                echo json_encode(['success' => true, 'subscription' => $result]);
            } else {
                // Get all subscriptions with filters
                $filters = [
                    'search' => $_GET['search'] ?? '',
                    'status' => $_GET['status'] ?? '',
                    'client_id' => $_GET['client_id'] ?? null,

                    'sort' => $_GET['sort'] ?? 'id',
                    'order' => $_GET['order'] ?? 'ASC',
                    'page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
                    'per_page' => isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10
                ];
                $result = $subscriptionController->getSubscriptions($filters);
                echo json_encode(['success' => true, 'data' => $result]);
            }
            break;

        case 'POST':
            // Create new subscription
            if (!$auth->hasRole('admin')) {
                throw new Exception('Permission denied');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $requiredFields = ['client_id', 'plan_name', 'start_date', 'billing_cycle'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            $result = $subscriptionController->createSubscription($data);
            echo json_encode(['success' => true, 'subscription' => $result]);
            break;

        case 'PUT':
            // Update subscription
            if (!$auth->hasRole('admin')) {
                throw new Exception('Permission denied');
            }

            if (!$subscriptionId) {
                throw new Exception('Subscription ID is required');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            $result = $subscriptionController->updateSubscription($subscriptionId, $data);
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => $result['message'],
                    'subscription' => $result['subscription']
                ]);
            } else {
                throw new Exception($result['message']);
            }
            break;

        case 'DELETE':
            // Delete subscription
            if (!$auth->hasRole('admin')) {
                throw new Exception('Permission denied');
            }

            if (!$subscriptionId) {
                throw new Exception('Subscription ID is required');
            }

            $result = $subscriptionController->deleteSubscription($subscriptionId);
            echo json_encode(['success' => true, 'message' => 'Subscription deleted successfully']);
            break;

        default:
            throw new Exception('Method not allowed');
    }

} catch (Exception $e) {
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}

// Special endpoints for subscription actions
if ($subscriptionId && isset($urlParts[3])) {
    try {
        switch ($urlParts[3]) {
            case 'suspend':
                if (!$auth->hasRole('admin')) {
                    throw new Exception('Permission denied');
                }
                $result = $subscriptionController->suspendSubscription($subscriptionId);
                echo json_encode(['success' => true, 'message' => 'Subscription suspended']);
                break;

            case 'activate':
                if (!$auth->hasRole('admin')) {
                    throw new Exception('Permission denied');
                }
                $result = $subscriptionController->activateSubscription($subscriptionId);
                echo json_encode(['success' => true, 'message' => 'Subscription activated']);
                break;

            case 'cancel':
                if (!$auth->hasRole('admin')) {
                    throw new Exception('Permission denied');
                }
                $data = json_decode(file_get_contents('php://input'), true);
                $result = $subscriptionController->cancelSubscription($subscriptionId, $data['reason'] ?? null);
                echo json_encode(['success' => true, 'message' => 'Subscription cancelled']);
                break;

            case 'renew':
                if (!$auth->hasRole('admin')) {
                    throw new Exception('Permission denied');
                }
                $data = json_decode(file_get_contents('php://input'), true);
                $result = $subscriptionController->renewSubscription($subscriptionId, $data);
                echo json_encode(['success' => true, 'message' => 'Subscription renewed']);
                break;

            case 'change-plan':
                if (!$auth->hasRole('admin')) {
                    throw new Exception('Permission denied');
                }
                $data = json_decode(file_get_contents('php://input'), true);
                if (!isset($data['plan_name']) || !isset($data['speed_mbps']) || !isset($data['price'])) {
                    throw new Exception('New plan details are required');
                }
                $result = $subscriptionController->changePlan($subscriptionId, $data);
                echo json_encode(['success' => true, 'message' => 'Plan changed successfully']);
                break;

            default:
                throw new Exception('Invalid action');
        }
    } catch (Exception $e) {
        http_response_code(400);
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
}
