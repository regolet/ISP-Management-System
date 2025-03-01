<?php
require_once '../config.php';
check_auth();

header('Content-Type: application/json');

try {
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        throw new Exception('Invalid request method');
    }

    $db = get_db_connection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Validate input
    $type = $_POST['type'] ?? '';
    $id = $_POST['id'] ?? '';
    
    if (!$id || !is_numeric($id)) {
        throw new Exception('Invalid ID');
    }

    if ($type === 'plc') {
        // Check if PLC value is in use
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM lcp WHERE splitter_type = ?");
        $checkStmt->execute([$id]);
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception('Cannot delete: This PLC splitter is being used by one or more LCPs');
        }

        $stmt = $db->prepare("DELETE FROM olt_loss_plc WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        if (!$success) {
            throw new Exception('Failed to delete PLC loss value');
        }
    } 
    else if ($type === 'fbt') {
        // Check if FBT value is in use
        $checkStmt = $db->prepare("SELECT COUNT(*) FROM lcp WHERE fbt_value = ?");
        $checkStmt->execute([$id]);
        if ($checkStmt->fetchColumn() > 0) {
            throw new Exception('Cannot delete: This FBT value is being used by one or more LCPs');
        }

        $stmt = $db->prepare("DELETE FROM olt_loss_fbt WHERE id = ?");
        $success = $stmt->execute([$id]);
        
        if (!$success) {
            throw new Exception('Failed to delete FBT loss value');
        }
    } 
    else {
        throw new Exception('Invalid type specified');
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Loss value deleted successfully'
    ]);

} catch (PDOException $e) {
    error_log('Database error in ftth_delete_loss_value.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('General error in ftth_delete_loss_value.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
