<?php
require_once 'config.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    $_SESSION['error'] = "Invalid request method";
    header('Location: subscriptions.php');
    exit;
}

try {
    $id = isset($_POST['id']) ? clean_input($_POST['id']) : null;
    $customer_id = clean_input($_POST['customer_id']);
    
    // Fix plan_id handling
    if (!empty($_POST['plan_id'])) {
        $plan_id = clean_input($_POST['plan_id']);
    } else {
        // Get the current plan_id from the subscription
        $stmt = $conn->prepare("SELECT plan_id FROM subscriptions WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $result = $stmt->get_result();
        $current = $result->fetch_assoc();
        $plan_id = $current['plan_id'];
    }
    
    // Verify plan exists
    $plan_check = $conn->prepare("SELECT id FROM plans WHERE id = ?");
    $plan_check->bind_param("i", $plan_id);
    $plan_check->execute();
    if ($plan_check->get_result()->num_rows === 0) {
        throw new Exception("Invalid plan selected");
    }

    $billing_cycle = clean_input($_POST['billing_cycle']);
    $status = clean_input($_POST['status']);
    $notes = clean_input($_POST['notes']);
    $auto_renew = isset($_POST['auto_renew']) ? 1 : 0;

    $conn->begin_transaction();

    // Update subscription
    $stmt = $conn->prepare("UPDATE subscriptions SET 
        customer_id = ?, 
        plan_id = ?, 
        billing_cycle = ?, 
        status = ?, 
        auto_renew = ?,
        notes = ?
        WHERE id = ?");
    
    $stmt->bind_param("iissisi", 
        $customer_id,
        $plan_id,
        $billing_cycle,
        $status,
        $auto_renew,
        $notes,
        $id
    );
    $stmt->execute();

    // Also update customer's plan
    $update_customer = $conn->prepare("UPDATE customers SET plan_id = ? WHERE id = ?");
    $update_customer->bind_param("ii", $plan_id, $customer_id);
    $update_customer->execute();

    $conn->commit();
    $_SESSION['success'] = "Subscription updated successfully";
    header("Location: subscriptions.php");
    exit;

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = "Error: " . $e->getMessage();
    header("Location: subscription_form.php?id=" . $id);
    exit;
}