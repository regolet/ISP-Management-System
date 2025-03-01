<?php
require_once '../config.php';
check_auth();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = clean_input($_POST['name']);
    $description = clean_input($_POST['description']);
    $amount = floatval($_POST['amount']);
    $bandwidth = clean_input($_POST['bandwidth']);

    // Validate inputs
    $errors = [];
    if (empty($name)) $errors[] = "Plan name is required";
    if (empty($description)) $errors[] = "Description is required";
    if ($amount <= 0) $errors[] = "Amount must be greater than 0";
    if (empty($bandwidth)) $errors[] = "Bandwidth is required";

    if (empty($errors)) {
        $stmt = $conn->prepare("
            INSERT INTO plans (name, description, amount, bandwidth, status, created_at)
            VALUES (?, ?, ?, ?, 'active', NOW())
        ");

        $stmt->bind_param("ssds", $name, $description, $amount, $bandwidth);

        if ($stmt->execute()) {
            log_activity($_SESSION['user_id'], 'add_plan', "Added new plan: $name");
            $_SESSION['success'] = "Plan added successfully";
        } else {
            $_SESSION['error'] = "Error adding plan: " . $conn->error;
        }
    } else {
        $_SESSION['error'] = implode("<br>", $errors);
    }
}

header("Location: plans.php");
exit();
?>
