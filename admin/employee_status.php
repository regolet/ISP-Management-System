<?php
require_once '../config.php';
check_auth();

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    $_SESSION['error'] = "Missing parameters";
    header("Location: employees.php");
    exit();
}

$id = clean_input($_GET['id']);
$status = clean_input($_GET['status']);

// Validate status
if (!in_array($status, ['active', 'inactive'])) {
    $_SESSION['error'] = "Invalid status";
    header("Location: employees.php");
    exit();
}

// Update employee status
$stmt = $conn->prepare("UPDATE employees SET status = ? WHERE id = ?");
$stmt->bind_param("si", $status, $id);

if ($stmt->execute()) {
    $_SESSION['success'] = "Employee status updated successfully";
} else {
    $_SESSION['error'] = "Error updating employee status: " . $conn->error;
}

// Redirect back to employee view
header("Location: employee_view.php?id=" . $id);
exit();
?>
