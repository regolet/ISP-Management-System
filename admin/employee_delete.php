<?php
require_once 'config.php';
check_login();

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "Employee ID not provided";
    header("Location: employees.php");
    exit();
}

$id = clean_input($_GET['id']);

// Check if employee exists
$stmt = $conn->prepare("SELECT id FROM employees WHERE id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
if ($stmt->get_result()->num_rows === 0) {
    $_SESSION['error'] = "Employee not found";
    header("Location: employees.php");
    exit();
}

// Check if employee has any payroll records
$stmt = $conn->prepare("SELECT id FROM payroll_items WHERE employee_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
if ($stmt->get_result()->num_rows > 0) {
    // If employee has payroll records, just mark as inactive
    $stmt = $conn->prepare("UPDATE employees SET status = 'inactive' WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Employee marked as inactive";
    } else {
        $_SESSION['error'] = "Error marking employee as inactive: " . $conn->error;
    }
} else {
    // If no payroll records, delete the employee
    $stmt = $conn->prepare("DELETE FROM employees WHERE id = ?");
    $stmt->bind_param("i", $id);
    if ($stmt->execute()) {
        $_SESSION['success'] = "Employee deleted successfully";
    } else {
        $_SESSION['error'] = "Error deleting employee: " . $conn->error;
    }
}

header("Location: employees.php");
exit();
?>
