<?php
require_once 'config.php';
check_login();

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $conn->begin_transaction();

    // Validate required fields
    $required_fields = ['asset_id', 'collection_date', 'amount', 'payment_method'];
    foreach ($required_fields as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    // Sanitize and validate input
    $asset_id = filter_input(INPUT_POST, 'asset_id', FILTER_SANITIZE_NUMBER_INT);
    $collection_date = filter_input(INPUT_POST, 'collection_date', FILTER_SANITIZE_STRING);
    $amount = filter_input(INPUT_POST, 'amount', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $payment_method = filter_input(INPUT_POST, 'payment_method', FILTER_SANITIZE_STRING);
    $reference_number = filter_input(INPUT_POST, 'reference_number', FILTER_SANITIZE_STRING);
    $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
    $collection_id = filter_input(INPUT_POST, 'collection_id', FILTER_SANITIZE_NUMBER_INT);

    // Additional validation
    if (!$asset_id || !strtotime($collection_date) || $amount <= 0) {
        throw new Exception('Invalid input values');
    }

    // Validate reference number for non-cash payments
    if ($payment_method !== 'cash' && empty($reference_number)) {
        throw new Exception('Reference number is required for non-cash payments');
    }

    // Check if asset exists
    $check_asset = $conn->prepare("SELECT id, name FROM assets WHERE id = ?");
    $check_asset->bind_param("i", $asset_id);
    $check_asset->execute();
    $asset = $check_asset->get_result()->fetch_assoc();

    if (!$asset) {
        throw new Exception('Asset not found');
    }

    if ($collection_id) {
        // Update existing collection
        $stmt = $conn->prepare("
            UPDATE asset_collections 
            SET collection_date = ?, amount = ?, payment_method = ?, 
                reference_number = ?, notes = ?, updated_at = CURRENT_TIMESTAMP
            WHERE id = ? AND asset_id = ?
        ");
        $stmt->bind_param(
            "sdsssis",
            $collection_date, $amount, $payment_method,
            $reference_number, $notes, $collection_id, $asset_id
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to update collection');
        }

        $action = "Updated collection";
        $details = sprintf(
            "Updated collection of ₱%s for asset '%s' dated %s",
            number_format($amount, 2),
            $asset['name'],
            date('M d, Y', strtotime($collection_date))
        );
    } else {
        // Insert new collection
        $stmt = $conn->prepare("
            INSERT INTO asset_collections 
            (asset_id, collection_date, amount, payment_method, reference_number, notes, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->bind_param(
            "isdsssi",
            $asset_id, $collection_date, $amount, $payment_method,
            $reference_number, $notes, $_SESSION['user_id']
        );
        
        if (!$stmt->execute()) {
            throw new Exception('Failed to save collection');
        }

        $action = "Added collection";
        $details = sprintf(
            "Added new collection of ₱%s for asset '%s' dated %s",
            number_format($amount, 2),
            $asset['name'],
            date('M d, Y', strtotime($collection_date))
        );
    }

    // Log activity
    log_activity($action, 'asset_collections', $details);

    $conn->commit();
    $_SESSION['success'] = 'Collection saved successfully';

} catch (Exception $e) {
    $conn->rollback();
    error_log("Collection Save Error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

header("Location: asset_collections.php?id=" . $asset_id);
exit();