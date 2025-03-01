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
        $ports = $_POST['ports'] ?? '';
        $loss = $_POST['loss'] ?? '';
        
        if (!is_numeric($ports) || !is_numeric($loss)) {
            throw new Exception('Invalid PLC values');
        }

        $stmt = $db->prepare("UPDATE olt_loss_plc SET ports = ?, loss = ? WHERE id = ?");
        $success = $stmt->execute([$ports, $loss, $id]);
        
        if (!$success) {
            throw new Exception('Failed to update PLC loss value');
        }
    } 
    else if ($type === 'fbt') {
        $value = $_POST['value'] ?? '';
        $loss = $_POST['loss'] ?? '';
        
        if (!is_numeric($value) || !is_numeric($loss)) {
            throw new Exception('Invalid FBT values');
        }

        $stmt = $db->prepare("UPDATE olt_loss_fbt SET value = ?, loss = ? WHERE id = ?");
        $success = $stmt->execute([$value, $loss, $id]);
        
        if (!$success) {
            throw new Exception('Failed to update FBT loss value');
        }
    } 
    else {
        throw new Exception('Invalid type specified');
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Loss value updated successfully'
    ]);

} catch (PDOException $e) {
    error_log('Database error in ftth_edit_loss_chart.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('General error in ftth_edit_loss_chart.php: ' . $e->getMessage());
    http_response_code(400);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
