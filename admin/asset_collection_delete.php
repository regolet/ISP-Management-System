<?php
require_once 'config.php';
check_login();

try {
    $collection_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
    $asset_id = filter_input(INPUT_GET, 'asset_id', FILTER_SANITIZE_NUMBER_INT);

    if (!$collection_id || !$asset_id) {
        throw new Exception("Invalid collection or asset ID");
    }

    $conn->begin_transaction();

    // Get collection details for logging
    $get_collection = $conn->prepare("
        SELECT c.*, a.name as asset_name 
        FROM asset_collections c
        LEFT JOIN assets a ON c.asset_id = a.id
        WHERE c.id = ?
    ");
    $get_collection->bind_param("i", $collection_id);
    $get_collection->execute();
    $collection = $get_collection->get_result()->fetch_assoc();

    if (!$collection) {
        throw new Exception("Collection not found");
    }

    // Delete the collection
    $delete_stmt = $conn->prepare("DELETE FROM asset_collections WHERE id = ?");
    $delete_stmt->bind_param("i", $collection_id);
    
    if (!$delete_stmt->execute()) {
        throw new Exception("Failed to delete collection");
    }

    // Log with all required parameters
    $action = "Deleted collection";
    $details = sprintf(
        "Deleted collection of ₱%s for asset '%s' dated %s",
        number_format($collection['amount'], 2),
        $collection['asset_name'],
        date('M d, Y', strtotime($collection['collection_date']))
    );
    log_activity($action, 'asset_collections', $details);

    $conn->commit();
    $_SESSION['success'] = "Collection deleted successfully";

} catch (Exception $e) {
    $conn->rollback();
    error_log("Collection Delete Error: " . $e->getMessage());
    $_SESSION['error'] = $e->getMessage();
}

header("Location: asset_collections.php?id=" . $asset_id);
exit();