<?php
require_once 'config.php';
check_login();

// Only admin can change user status
if ($_SESSION['role'] !== 'admin') {
    $_SESSION['error'] = "Unauthorized access";
    header("Location: users.php");
    exit;
}

try {
    $user_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    $status = filter_var($_GET['status'], FILTER_SANITIZE_STRING);

    if (!$user_id || !in_array($status, ['active', 'inactive'])) {
        throw new Exception("Invalid parameters");
    }

    // Prevent self-deactivation
    if ($user_id == $_SESSION['user_id']) {
        throw new Exception("You cannot change your own status");
    }

    // Get user details for logging
    $user_query = "SELECT username FROM users WHERE id = ?";
    $stmt = $conn->prepare($user_query);
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $user = $stmt->get_result()->fetch_assoc();

    if (!$user) {
        throw new Exception("User not found");
    }

    // Begin transaction
    $conn->begin_transaction();

    // Update user status
    $update_query = "UPDATE users SET status = ?, updated_at = CURRENT_TIMESTAMP WHERE id = ?";
    $stmt = $conn->prepare($update_query);
    $stmt->bind_param("si", $status, $user_id);

    if (!$stmt->execute()) {
        throw new Exception("Error updating user status: " . $conn->error);
    }

    // Log the activity
    log_activity(
        $_SESSION['user_id'],
        'update_user_status',
        "Changed status of user {$user['username']} to $status"
    );

    $conn->commit();
    $_SESSION['success'] = "User status updated successfully";

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
}

header("Location: users.php");
exit;