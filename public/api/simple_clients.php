<?php
/**
 * Simple API endpoint for clients
 */

require_once dirname(dirname(__DIR__)) . '/app/init.php';
require_once dirname(dirname(__DIR__)) . '/config/database.php';
require_once dirname(dirname(__DIR__)) . '/app/Models/SimpleClient.php';

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Client Model
$client = new \App\Models\SimpleClient($db);

// Set headers
header('Content-Type: application/json');

// Handle different HTTP methods
$method = $_SERVER['REQUEST_METHOD'];

switch ($method) {
    case 'GET':
        // Get all clients or a specific client
        if (isset($_GET['id'])) {
            $clientData = $client->getById($_GET['id']);
            if ($clientData) {
                echo json_encode([
                    'success' => true,
                    'client' => $clientData
                ]);
            } else {
                echo json_encode([
                    'success' => false,
                    'message' => 'Client not found'
                ]);
            }
        } else {
            $clients = $client->getAll();
            echo json_encode([
                'success' => true,
                'clients' => $clients
            ]);
        }
        break;
        
    case 'POST':
        // Create a new client
        $data = [
            'first_name' => $_POST['first_name'] ?? null,
            'last_name' => $_POST['last_name'] ?? null,
            'email' => $_POST['email'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'status' => $_POST['status'] ?? 'active'
        ];
        
        // Validate required fields
        if (empty($data['first_name']) || empty($data['last_name'])) {
            echo json_encode([
                'success' => false,
                'message' => 'First name and last name are required'
            ]);
            exit;
        }
        
        $clientId = $client->create($data);
        
        if ($clientId) {
            $newClient = $client->getById($clientId);
            echo json_encode([
                'success' => true,
                'message' => 'Client created successfully',
                'client' => $newClient
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to create client'
            ]);
        }
        break;
        
    case 'DELETE':
        // Delete a client
        parse_str(file_get_contents('php://input'), $data);
        $id = $_GET['id'] ?? null;
        
        if (!$id) {
            echo json_encode([
                'success' => false,
                'message' => 'Client ID is required'
            ]);
            exit;
        }
        
        // Check if client exists
        $clientData = $client->getById($id);
        
        if (!$clientData) {
            echo json_encode([
                'success' => false,
                'message' => 'Client not found'
            ]);
            exit;
        }
        
        // Delete client
        $result = $client->delete($id);
        
        if ($result) {
            echo json_encode([
                'success' => true,
                'message' => 'Client deleted successfully'
            ]);
        } else {
            echo json_encode([
                'success' => false,
                'message' => 'Failed to delete client'
            ]);
        }
        break;
        
    default:
        echo json_encode([
            'success' => false,
            'message' => 'Method not allowed'
        ]);
        break;
}
?>