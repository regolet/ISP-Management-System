<?php
// Allow from any origin
header("Access-Control-Allow-Origin: *");
header("Content-Type: application/json; charset=UTF-8");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Handle preflight request
if ($_SERVER['REQUEST_METHOD'] == 'OPTIONS') {
    http_response_code(200);
    exit();
}

// Include necessary files
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/app/Controllers/LcpController.php';
require_once dirname(dirname(__DIR__)) . '/app/Controllers/AuthController.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize controllers
$auth = new \App\Controllers\AuthController();
$lcpController = new \App\Controllers\LcpController($db);

// Check for authentication for write operations
if ($_SERVER['REQUEST_METHOD'] != 'GET' && $_SERVER['REQUEST_METHOD'] != 'OPTIONS') {
    if (!$auth->isLoggedIn()) {
        http_response_code(401);
        echo json_encode(['success' => false, 'message' => 'Unauthorized']);
        exit();
    }
}

// Process based on HTTP method
switch ($_SERVER['REQUEST_METHOD']) {
    case 'GET':
        handleGetRequest($lcpController);
        break;
    case 'POST':
        handlePostRequest($lcpController);
        break;
    case 'PUT':
        handlePutRequest($lcpController);
        break;
    case 'DELETE':
        handleDeleteRequest($lcpController);
        break;
    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'message' => 'Method not allowed']);
}

/**
 * Process GET requests
 */
function handleGetRequest($lcpController) {
    // Check for specific actions
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'port_utilization':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'LCP ID is required']);
                return;
            }
            $result = $lcpController->getPortUtilization($_GET['id']);
            echo json_encode($result);
            break;

        case 'available_olt_ports':
            $oltId = isset($_GET['olt_id']) ? $_GET['olt_id'] : null;
            $result = $lcpController->getAvailableOltPorts($oltId);
            echo json_encode($result);
            break;
            
        case 'get_port':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Port ID is required']);
                return;
            }
            $result = $lcpController->getPort($_GET['id']);
            echo json_encode($result);
            break;
            
        case 'connected_clients':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'LCP ID is required']);
                return;
            }
            $result = $lcpController->getConnectedClients($_GET['id']);
            echo json_encode($result);
            break;
            
        case 'maintenance_history':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'LCP ID is required']);
                return;
            }
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $result = $lcpController->getMaintenanceHistory($_GET['id'], $limit);
            echo json_encode($result);
            break;
            
        case 'upcoming_maintenance':
            $days = isset($_GET['days']) ? (int)$_GET['days'] : 7;
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 10;
            $result = $lcpController->getUpcomingMaintenance($days, $limit);
            echo json_encode($result);
            break;
            
        case 'spatial_data':
            $result = $lcpController->getSpatialData();
            echo json_encode($result);
            break;
            
        case 'find_nearest':
            if (!isset($_GET['lat']) || !isset($_GET['lng'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'Latitude and longitude are required']);
                return;
            }
            $limit = isset($_GET['limit']) ? (int)$_GET['limit'] : 5;
            $result = $lcpController->findNearestLcps($_GET['lat'], $_GET['lng'], $limit);
            echo json_encode($result);
            break;
            
        case 'fault_lcps':
            $result = $lcpController->getLcpsWithFaultPorts();
            echo json_encode($result);
            break;
            
        case 'stats':
            $stats = $lcpController->getLcpStats();
            echo json_encode(['success' => true, 'stats' => $stats]);
            break;
            
        case 'dashboard':
            $result = $lcpController->getDashboardData();
            echo json_encode($result);
            break;
            
        case 'health_report':
            if (!isset($_GET['id'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'LCP ID is required']);
                return;
            }
            $result = $lcpController->generateHealthReport($_GET['id']);
            echo json_encode($result);
            break;
            
        case 'export':
            $result = $lcpController->exportToCsv();
            
            if ($result['success']) {
                header('Content-Type: ' . $result['mime']);
                header('Content-Disposition: attachment; filename=' . $result['filename']);
                echo $result['data'];
            } else {
                echo json_encode($result);
            }
            break;

        default:
            // If ID is provided, get specific LCP
            if (isset($_GET['id'])) {
                $result = $lcpController->getLcpDevice($_GET['id']);
                echo json_encode($result);
            } else {
                // Otherwise, get all LCPs with pagination and filters
                $params = [
                    'page' => isset($_GET['page']) ? (int)$_GET['page'] : 1,
                    'per_page' => isset($_GET['per_page']) ? (int)$_GET['per_page'] : 10,
                    'search' => $_GET['search'] ?? '',
                    'status' => $_GET['status'] ?? '',
                    'sort' => $_GET['sort'] ?? 'id',
                    'order' => $_GET['order'] ?? 'ASC'
                ];
                
                $result = $lcpController->getLcpDevices($params);
                echo json_encode(['success' => true, 'lcps' => $result]);
            }
            break;
    }
}

/**
 * Process POST requests
 */
function handlePostRequest($lcpController) {
    // Get posted data
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }

    // Check for specific actions
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'add_maintenance':
            $result = $lcpController->addMaintenance($data);
            echo json_encode($result);
            break;
            
        case 'batch_update_status':
            if (!isset($data['ids']) || !isset($data['status'])) {
                http_response_code(400);
                echo json_encode(['success' => false, 'message' => 'IDs and status are required']);
                return;
            }
            $result = $lcpController->batchUpdateStatus($data['ids'], $data['status']);
            echo json_encode($result);
            break;

        default:
            // Create new LCP
            $result = $lcpController->createLcpDevice($data);
            echo json_encode($result);
    }
}

/**
 * Process PUT requests
 */
function handlePutRequest($lcpController) {
    // Get data from request body
    $data = json_decode(file_get_contents("php://input"), true);
    
    if (!$data) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'Invalid JSON data']);
        return;
    }

    // Check for required ID parameter
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID parameter is required']);
        return;
    }

    $id = $_GET['id'];
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'update_port':
            $result = $lcpController->updatePort($id, $data);
            echo json_encode($result);
            break;
            
        case 'update_maintenance':
            $result = $lcpController->updateMaintenance($id, $data);
            echo json_encode($result);
            break;

        default:
            // Update LCP device
            $result = $lcpController->updateLcpDevice($id, $data);
            echo json_encode($result);
    }
}

/**
 * Process DELETE requests
 */
function handleDeleteRequest($lcpController) {
    // Check for required ID parameter
    if (!isset($_GET['id'])) {
        http_response_code(400);
        echo json_encode(['success' => false, 'message' => 'ID parameter is required']);
        return;
    }

    $id = $_GET['id'];
    $action = $_GET['action'] ?? '';
    
    switch ($action) {
        case 'delete_maintenance':
            $result = $lcpController->deleteMaintenance($id);
            echo json_encode($result);
            break;

        default:
            // Delete LCP device
            $result = $lcpController->deleteLcpDevice($id);
            echo json_encode($result);
    }
}
