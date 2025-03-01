<?php
require_once '../config.php';
check_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: plans.php");
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get form data
    $id = filter_input(INPUT_POST, 'id', FILTER_SANITIZE_NUMBER_INT);
    $name = trim($_POST['name']);
    $description = trim($_POST['description']);
    $bandwidth = filter_input(INPUT_POST, 'bandwidth', FILTER_SANITIZE_NUMBER_INT);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);

    // Validate required fields
    if (!$name || !$description || !$bandwidth || !$amount) {
        throw new Exception("Please fill in all required fields");
    }

    if ($id) {
        // Update existing plan
        $stmt = $conn->prepare("
            UPDATE plans 
            SET name = ?, description = ?, bandwidth = ?, amount = ?, updated_at = NOW()
            WHERE id = ?
        ");
        $stmt->bind_param("ssidi", $name, $description, $bandwidth, $amount, $id);
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating plan: " . $conn->error);
        }

        log_activity($_SESSION['user_id'], 'update_plan', "Updated plan: $name");
        $_SESSION['success'] = "Plan updated successfully";
    } else {
        // Create new plan
        $stmt = $conn->prepare("
            INSERT INTO plans (name, description, bandwidth, amount, status) 
            VALUES (?, ?, ?, ?, 'active')
        ");
        $stmt->bind_param("ssid", $name, $description, $bandwidth, $amount);
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating plan: " . $conn->error);
        }

        log_activity($_SESSION['user_id'], 'create_plan', "Created new plan: $name");
        $_SESSION['success'] = "Plan created successfully";
    }

    $conn->commit();
    header("Location: plans.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
    header("Location: plans.php");
    exit();
} 