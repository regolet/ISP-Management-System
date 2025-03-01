<?php
require_once '../../config.php';
require_once '../staff_auth.php';

$search = clean_input($_GET['search'] ?? '');
$status = clean_input($_GET['status'] ?? '');
$payment_method = clean_input($_GET['payment_method'] ?? '');
$date_from = clean_input($_GET['date_from'] ?? '');
$date_to = clean_input($_GET['date_to'] ?? '');

// Get employee ID
$stmt = $conn->prepare("SELECT id FROM employees WHERE user_id = ?");
$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$employee = $stmt->get_result()->fetch_assoc();

// Build query
$query = "SELECT p.*, c.name as customer_name 
          FROM payments p 
          LEFT JOIN customers c ON p.customer_id = c.id
          WHERE p.created_by = ?";
$params = [$employee['id']];
$types = "i";

if ($search) {
    $query .= " AND (c.name LIKE ? OR p.reference_number LIKE ?)";
    $search_param = "%$search%";
    $params = array_merge($params, [$search_param, $search_param]);
    $types .= "ss";
}

if ($status) {
    $query .= " AND p.status = ?";
    $params[] = $status;
    $types .= "s";
}

if ($payment_method) {
    $query .= " AND p.payment_method = ?";
    $params[] = $payment_method;
    $types .= "s";
}

if ($date_from) {
    $query .= " AND DATE(p.payment_date) >= ?";
    $params[] = $date_from;
    $types .= "s";
}

if ($date_to) {
    $query .= " AND DATE(p.payment_date) <= ?";
    $params[] = $date_to;
    $types .= "s";
}

$query .= " ORDER BY p.payment_date DESC";

// Prepare and execute query
$stmt = $conn->prepare($query);
if (!empty($params)) {
    $stmt->bind_param($types, ...$params);
}
$stmt->execute();
$payments = $stmt->get_result();

// Return JSON response
$results = [];
while ($payment = $payments->fetch_assoc()) {
    $results[] = [
        'id' => $payment['id'],
        'date' => date('M d, Y', strtotime($payment['payment_date'])),
        'customer' => $payment['customer_name'],
        'amount' => number_format($payment['amount'], 2),
        'method' => ucfirst(str_replace('_', ' ', $payment['payment_method'])),
        'reference' => $payment['reference_number'],
        'status' => $payment['status'],
        'actions' => [
            'view' => "view.php?id={$payment['id']}",
            'edit' => $payment['status'] === 'pending' ? "edit.php?id={$payment['id']}" : null,
            'receipt' => "generate_receipt.php?id={$payment['id']}"
        ]
    ];
}

header('Content-Type: application/json');
echo json_encode(['data' => $results]);
