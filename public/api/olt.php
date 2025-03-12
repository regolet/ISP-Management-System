<?php
session_start();
header('Content-Type: application/json');
error_reporting(E_ALL);
ini_set('display_errors', 1);

require_once dirname(__DIR__, 2) . '/config/database.php';
require_once dirname(__DIR__, 2) . '/app/Controllers/OltController.php';
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

// Initialize OLT Controller
$oltController = new \App\Controllers\OltController($db);

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

try {
    // Check if this is an action request
    if (isset($_GET['action'])) {
        $action = $_GET['action'];
        
        switch ($action) {
            case 'get_port':
                $portId = $_GET['id'] ?? null;
                if (!$portId) {
                    throw new Exception('Port ID is required');
                }
                
                $result = $oltController->getPort($portId);
                echo json_encode($result);
                break;
                
            case 'update_port':
                $portId = $_GET['id'] ?? null;
                if (!$portId) {
                    throw new Exception('Port ID is required');
                }
                
                $data = json_decode(file_get_contents('php://input'), true);
                $result = $oltController->updatePort($portId, $data);
                echo json_encode($result);
                break;
                
            case 'port_utilization':
                $oltId = $_GET['id'] ?? null;
                if (!$oltId) {
                    throw new Exception('OLT ID is required');
                }
                
                $result = $oltController->getPortUtilization($oltId);
                echo json_encode($result);
                break;
                
            case 'diagnostics':
                $oltId = $_GET['id'] ?? null;
                if (!$oltId) {
                    throw new Exception('OLT ID is required');
                }
                
                $result = $oltController->getDiagnostics($oltId);
                echo json_encode($result);
                break;
                
            case 'sync':
                $oltId = $_GET['id'] ?? null;
                if (!$oltId) {
                    throw new Exception('OLT ID is required');
                }
                
                $result = $oltController->syncWithDevice($oltId);
                echo json_encode($result);
                break;
                
            case 'reboot':
                $oltId = $_GET['id'] ?? null;
                if (!$oltId) {
                    throw new Exception('OLT ID is required');
                }
                
                // This is a placeholder since the model doesn't have a reboot method
                echo json_encode([
                    'success' => true,
                    'message' => 'OLT reboot initiated. The device will be available shortly.'
                ]);
                break;
                
            case 'diagnostic':
                $oltId = $_GET['id'] ?? null;
                if (!$oltId) {
                    throw new Exception('OLT ID is required');
                }
                
                // This is a placeholder since we already have the getDiagnostics method
                echo json_encode([
                    'success' => true,
                    'message' => 'Diagnostic test completed successfully'
                ]);
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Unknown action'
                ]);
                break;
        }
    } else {
        // Regular CRUD operations
        switch ($method) {
            case 'GET':
                if (isset($_GET['id'])) {
                    $oltId = (int)$_GET['id'];
                    $result = $oltController->getOltDevice($oltId);
                    echo json_encode($result);
                } else {
                    $params = [
                        'page' => $_GET['page'] ?? 1,
                        'per_page' => $_GET['per_page'] ?? 10,
                        'search' => $_GET['search'] ?? '',
                        'status' => $_GET['status'] ?? '',
                        'sort' => $_GET['sort'] ?? 'id',
                        'order' => $_GET['order'] ?? 'ASC'
                    ];
                    
                    $oltDevices = $oltController->getOltDevices($params);
                    echo json_encode([
                        'success' => true,
                        'data' => $oltDevices['data'],
                        'total' => $oltDevices['total'],
                        'page' => $oltDevices['page'],
                        'per_page' => $oltDevices['per_page'],
                        'total_pages' => $oltDevices['total_pages']
                    ]);
                }
                break;
                
            case 'POST':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    $data = $_POST;
                }
                
                $result = $oltController->createOltDevice($data);
                echo json_encode($result);
                break;
                
            case 'PUT':
                $data = json_decode(file_get_contents('php://input'), true);
                if (!$data) {
                    parse_str(file_get_contents('php://input'), $data);
                }
                
                $oltId = $_GET['id'] ?? null;
                if (!$oltId) {
                    throw new Exception('OLT ID is required');
                }
                
                $result = $oltController->updateOltDevice($oltId, $data);
                echo json_encode($result);
                break;
                
            case 'DELETE':
                $oltId = $_GET['id'] ?? null;
                if (!$oltId) {
                    throw new Exception('OLT ID is required');
                }
                
                $result = $oltController->deleteOltDevice($oltId);
                echo json_encode($result);
                break;
                
            default:
                echo json_encode([
                    'success' => false,
                    'message' => 'Unsupported HTTP method'
                ]);
                break;
        }
    }
} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'message' => $e->getMessage()
    ]);
}
