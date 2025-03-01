<?php
// Prevent any HTML error output
ini_set('display_errors', 0);
error_reporting(E_ALL);

header('Content-Type: application/json');

require_once 'config.php';
require_once 'auth_check.php';

function sendJsonResponse($data, $statusCode = 200) {
    http_response_code($statusCode);
    echo json_encode($data, JSON_PRETTY_PRINT);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST' || !isset($_POST['id'])) {
    sendJsonResponse(['error' => 'Invalid request'], 400);
}

try {
    $customer_id = intval($_POST['id']);
    
    if ($customer_id <= 0) {
        sendJsonResponse(['error' => 'Invalid customer ID'], 400);
    }
    
    // Get customer details with plan and user information
    $query = "
        SELECT 
            c.*,
            p.name as plan_name,
            p.bandwidth,
            p.amount as plan_amount,
            u.username,
            u.email,
            u.status as portal_status
        FROM customers c
        LEFT JOIN plans p ON c.plan_id = p.id
        LEFT JOIN users u ON c.user_id = u.id
        WHERE c.id = ?
    ";
    
    $stmt = $conn->prepare($query);
    if (!$stmt) {
        sendJsonResponse(['error' => 'Database error: ' . $conn->error], 500);
    }
    
    $stmt->bind_param("i", $customer_id);
    if (!$stmt->execute()) {
        sendJsonResponse(['error' => 'Database error: ' . $stmt->error], 500);
    }
    
    $result = $stmt->get_result();
    $data = $result->fetch_assoc();
    
    if (!$data) {
        sendJsonResponse(['error' => 'Customer not found'], 404);
    }
    
    $response = [
        'customer' => [
            'id' => $data['id'],
            'name' => $data['name'],
            'address' => $data['address'],
            'contact' => $data['contact'],
            'balance' => $data['balance'],
            'status' => $data['status'],
            'due_date' => $data['due_date']
        ],
        'plan' => [
            'name' => $data['plan_name'],
            'bandwidth' => $data['bandwidth'],
            'amount' => $data['plan_amount']
        ]
    ];
    
    // Add user information if exists
    if ($data['username']) {
        $response['user'] = [
            'username' => $data['username'],
            'email' => $data['email'],
            'status' => $data['portal_status']
        ];
    }
    
    // Get recent billing records
    $billing_query = "
        SELECT 
            b.id, b.invoiceid, b.amount, b.status,
            b.due_date, b.created_at
        FROM billing b
        WHERE b.customer_id = ?
        ORDER BY b.created_at DESC
        LIMIT 5
    ";
    
    $stmt = $conn->prepare($billing_query);
    if (!$stmt) {
        sendJsonResponse(['error' => 'Database error: ' . $conn->error], 500);
    }
    
    $stmt->bind_param("i", $customer_id);
    if (!$stmt->execute()) {
        sendJsonResponse(['error' => 'Database error: ' . $stmt->error], 500);
    }
    
    $billing_result = $stmt->get_result();
    $response['billing'] = $billing_result->fetch_all(MYSQLI_ASSOC);
    
    // Get recent activity history
    $activity_query = "
        SELECT 
            al.type, al.description, al.created_at,
            CONCAT(u.username, ' (', u.role, ')') as user
        FROM activity_log al
        JOIN users u ON al.user_id = u.id
        WHERE al.user_id = ?
        ORDER BY al.created_at DESC
        LIMIT 10
    ";
    
    $stmt = $conn->prepare($activity_query);
    if (!$stmt) {
        sendJsonResponse(['error' => 'Database error: ' . $conn->error], 500);
    }
    
    $stmt->bind_param("i", $data['user_id']);
    if (!$stmt->execute()) {
        sendJsonResponse(['error' => 'Database error: ' . $stmt->error], 500);
    }
    
    $activity_result = $stmt->get_result();
    $response['activity'] = $activity_result->fetch_all(MYSQLI_ASSOC);
    
    sendJsonResponse($response);
    
} catch (Exception $e) {
    // Log the error for debugging
    error_log("Error in get_customer_details.php: " . $e->getMessage());
    sendJsonResponse(['error' => 'An unexpected error occurred'], 500);
}