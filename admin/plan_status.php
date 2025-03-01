<?php
require_once '../config.php';
check_auth();

if (!isset($_GET['id']) || !isset($_GET['status'])) {
    $_SESSION['error'] = "Invalid request";
    header("Location: plans.php");
    exit();
}

try {
    $id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    $status = $_GET['status'] === 'active' ? 'active' : 'inactive';

    // Check if plan exists
    $check = $conn->prepare("SELECT name FROM plans WHERE id = ?");
    $check->bind_param("i", $id);
    $check->execute();
    $plan = $check->get_result()->fetch_assoc();

    if (!$plan) {
        throw new Exception("Plan not found");
    }

    // Update plan status
    $stmt = $conn->prepare("UPDATE plans SET status = ?, updated_at = NOW() WHERE id = ?");
    $stmt->bind_param("si", $status, $id);
    
    if (!$stmt->execute()) {
        throw new Exception("Error updating plan status: " . $conn->error);
    }

    $action = $status === 'active' ? 'activated' : 'deactivated';
    log_activity($_SESSION['user_id'], 'update_plan_status', "Plan {$plan['name']} $action");
    $_SESSION['success'] = "Plan successfully $action";

} catch (Exception $e) {
    $_SESSION['error'] = $e->getMessage();
}

header("Location: plans.php");
exit(); 