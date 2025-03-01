<?php
require_once 'config.php';
check_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: assets.php");
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Get form data
    $id = isset($_POST['id']) ? clean_input($_POST['id']) : null;
    $name = clean_input($_POST['name']);
    $description = clean_input($_POST['description']);
    $address = clean_input($_POST['address']);
    $expected_amount = filter_input(INPUT_POST, 'expected_amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $collection_frequency = clean_input($_POST['collection_frequency']);
    $next_collection_date = clean_input($_POST['next_collection_date']);
    $notes = clean_input($_POST['notes']);
    $status = isset($_POST['status']) ? clean_input($_POST['status']) : 'active';

    // Validate each required field individually
    $errors = [];
    if (empty($name)) $errors[] = "Asset name is required";
    if (empty($address)) $errors[] = "Address is required";
    if (empty($expected_amount) || $expected_amount === false) $errors[] = "Expected amount is required and must be a valid number";
    if (empty($collection_frequency)) $errors[] = "Collection frequency is required";
    if (empty($next_collection_date)) $errors[] = "Next collection date is required";

    // If any errors, throw exception with specific messages
    if (!empty($errors)) {
        throw new Exception(implode("\n", $errors));
    }

    // Convert expected_amount to ensure it's a valid float
    $expected_amount = (float)str_replace(',', '', $expected_amount);
    if ($expected_amount <= 0) {
        throw new Exception("Expected amount must be greater than zero");
    }

    if ($id) {
        // Update existing asset
        $stmt = $conn->prepare("
            UPDATE assets 
            SET name = ?, description = ?, address = ?, expected_amount = ?,
                collection_frequency = ?, next_collection_date = ?, notes = ?, 
                status = ?, updated_at = NOW()
            WHERE id = ?
        ");
        
        $stmt->bind_param("sssdssssi", 
            $name, $description, $address, $expected_amount,
            $collection_frequency, $next_collection_date, $notes,
            $status, $id
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error updating asset: " . $conn->error);
        }

        log_activity($_SESSION['user_id'], 'update_asset', "Updated asset: $name");
        $_SESSION['success'] = "Asset updated successfully";
    } else {
        // Create new asset
        $stmt = $conn->prepare("
            INSERT INTO assets (
                name, description, address, expected_amount,
                collection_frequency, next_collection_date, notes, status
            ) VALUES (?, ?, ?, ?, ?, ?, ?, 'active')
        ");
        
        $stmt->bind_param("sssdsss", 
            $name, 
            $description, 
            $address, 
            $expected_amount,
            $collection_frequency, 
            $next_collection_date, 
            $notes
        );
        
        if (!$stmt->execute()) {
            throw new Exception("Error creating asset: " . $conn->error);
        }

        $asset_id = $conn->insert_id;
        log_activity($_SESSION['user_id'], 'create_asset', "Created new asset: $name");
        $_SESSION['success'] = "Asset created successfully";
    }

    $conn->commit();
    header("Location: assets.php");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
    header("Location: " . ($id ? "asset_form.php?id=$id" : "asset_form.php"));
    exit();
} 