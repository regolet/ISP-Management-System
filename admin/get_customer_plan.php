<?php
require_once 'config.php';

if (isset($_GET['customer_id'])) {
    $customer_id = intval($_GET['customer_id']);
    
    // Get customer's plan details
    $stmt = $conn->prepare("
        SELECT p.name as plan_name, p.amount as plan_amount, p.bandwidth
        FROM customers c
        JOIN plans p ON c.plan_id = p.id
        WHERE c.id = ?
    ");
    
    $stmt->bind_param("i", $customer_id);
    $stmt->execute();
    $result = $stmt->get_result();
    
    if ($plan = $result->fetch_assoc()) {
        echo json_encode([
            'success' => true,
            'plan_name' => $plan['plan_name'],
            'plan_amount' => $plan['plan_amount'],
            'bandwidth' => $plan['bandwidth']
        ]);
    } else {
        echo json_encode([
            'success' => false,
            'message' => 'No plan found for this customer'
        ]);
    }
} else {
    echo json_encode([
        'success' => false,
        'message' => 'Customer ID not provided'
    ]);
}
?>
