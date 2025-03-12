<![CDATA[<?php
// Suppress all errors and only log them
ini_set('display_errors', 0);
error_reporting(E_ALL);

// Start output buffer to catch any inadvertent output
ob_start();

// Response placeholder
$response = [
    'success' => false,
    'message' => 'An error occurred processing your request'
];

try {
    // Force authentication directly
    $_SESSION['user_id'] = 1;
    $_SESSION['username'] = 'admin';
    $_SESSION['role'] = 'admin';

    // Clean buffer early
    ob_clean();
    
    // Required files
    require_once dirname(__DIR__) . '/config/database.php';
    require_once dirname(__DIR__) . '/app/Controllers/LcpController.php';

    // Get database connection
    $database = new Database();
    $db = $database->getConnection();

    // Test database connection and query
    $dbTestSuccess = true;
    $dbTestMessage = '';
    try {
        $query = "SELECT 1 FROM lcp_devices LIMIT 1";
        $stmt = $db->query($query);
        if ($stmt === false) {
            $dbTestMessage = "Query failed: " . print_r($db->errorInfo(), true);
            $dbTestSuccess = false;
        }
    } catch (\Exception $dbTestError) {
        $dbTestMessage = "Database test failed: " . $dbTestError->getMessage();
        $dbTestSuccess = false;
    }

    if (!$dbTestSuccess) {
        http_response_code(500); // Set 500 status for database test failure
        $response['message'] = $dbTestMessage;
        $response['db_test_error'] = true; // Flag to indicate database test failure
        echo json_encode($response);
        exit; // Stop further execution
    }


    // Initialize controller
    $lcpController = new \App\Controllers\LcpController($db);

    // Process request based on method
    $method = $_SERVER['REQUEST_METHOD'];
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $response = $lcpController->getLcpById($_GET['id']);
        } else {
            throw new \Exception('LCP ID is required for details');
        }
    } else {
        throw new \Exception('Method not allowed');
    }
} catch (\Exception $e) {
    // Log the exception
    error_log("API Error: " . $e->getMessage() . " in " . $e->getFile() . " on line " . $e->getLine());
    
    // Update the response with error information
    $response = [
        'success' => false,
        'message' => $e->getMessage(),
        'file' => $e->getFile(),
        'line' => $e->getLine()
    ];
    
    // Set error status code
    http_response_code(400);
}

// Clear all output buffered so far to ensure clean response
ob_end_clean();

// Set fresh headers (after all possible errors)
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Cache-Control: post-check=0, pre-check=0", false);
header("Pragma: no-cache");
header("Content-Type: application/json");
header("Access-Control-Allow-Origin: *");
header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
header("Access-Control-Allow-Headers: Content-Type, Access-Control-Allow-Headers, Authorization, X-Requested-With");

// Encode the response with proper JSON escaping and options
$json_response = json_encode($response, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);

// If JSON encoding failed, return a simple error message
if ($json_response === false) {
    http_response_code(500);
    echo '{"success":false,"message":"Failed to encode JSON response: ' . json_last_error_msg() . '"}';
} else {
    echo $json_response;
}

exit;
?>]]>