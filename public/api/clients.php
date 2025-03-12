<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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
require_once dirname(__DIR__, 2) . '/app/Controllers/ClientController.php';

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

// Initialize Client Controller
$clientController = new \App\Controllers\ClientController($db);

// Get request method
$method = $_SERVER['REQUEST_METHOD'];

// Get client ID from URL parameters
$clientId = isset($_GET['id']) ? (int)$_GET['id'] : null;
$action = $_GET['action'] ?? null;

try {
    switch ($method) {
        case 'GET':
            if ($action === 'export') {
                // Export clients to CSV
                if (!$auth->hasRole('admin')) {
                    throw new Exception('Permission denied');
                }
                
                $result = $clientController->exportClientsToCSV();
                
                if ($result['success']) {
                    header('Content-Type: ' . $result['mime']);
                    header('Content-Disposition: attachment; filename=' . $result['filename']);
                    echo $result['data'];
                } else {
                    echo json_encode($result);
                }
            } else if ($action === 'stats') {
                // Get client statistics
                if (!$auth->hasRole('admin')) {
                    throw new Exception('Permission denied');
                }
                
                $stats = $clientController->getClientStats();
                echo json_encode(['success' => true, 'stats' => $stats]);
            } else if ($clientId) {
                // Get single client
                $result = $clientController->getClientById($clientId);
                echo json_encode(['success' => true, 'client' => $result]);
            } else {
                // Get all clients with filters
                $filters = [
                    'search' => $_GET['search'] ?? '',
                    'status' => $_GET['status'] ?? '',
                    'sort' => $_GET['sort'] ?? 'id',
                    'order' => $_GET['order'] ?? 'ASC',
                    'page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
                    'per_page' => isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10
                ];
                $result = $clientController->getClients($filters);
                echo json_encode(['success' => true, 'data' => $result]);
            }
            break;

        case 'POST':
            // Create new client
            if (!$auth->hasRole('admin')) {
                throw new Exception('Permission denied');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $requiredFields = ['first_name', 'last_name'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            $result = $clientController->createClient($data);
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Client created successfully',
                    'client' => $result['client']
                ]);
            } else {
                echo json_encode($result);
            }
            break;

        case 'PUT':
            // Update client
            if (!$auth->hasRole('admin')) {
                throw new Exception('Permission denied');
            }

            if (!$clientId) {
                throw new Exception('Client ID is required');
            }

            $data = json_decode(file_get_contents('php://input'), true);
            
            // Validate required fields
            $requiredFields = ['first_name', 'last_name'];
            foreach ($requiredFields as $field) {
                if (!isset($data[$field]) || empty($data[$field])) {
                    throw new Exception("Missing required field: $field");
                }
            }

            $result = $clientController->updateClient($clientId, $data);
            echo json_encode($result);
            break;

        case 'DELETE':
            // Delete client
            if (!$auth->hasRole('admin')) {
                throw new Exception('Permission denied');
            }

            if (!$clientId) {
                throw new Exception('Client ID is required');
            }

            $result = $clientController->deleteClient($clientId);
            if ($result['success']) {
                echo json_encode([
                    'success' => true,
                    'message' => $result['message']
                ]);
            } else {
                echo json_encode($result);
            }
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