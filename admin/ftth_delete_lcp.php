<?php
require_once '../config.php';
check_auth();

header('Content-Type: application/json');

try {
    // Get JSON input
    $input = json_decode(file_get_contents('php://input'), true);
    
    if (!isset($input['id'])) {
        throw new Exception('LCP ID is required');
    }

    $db = get_db_connection();

    // Start transaction
    $db->beginTransaction();

    try {
        // Check if LCP exists
        $stmt = $db->prepare("SELECT id FROM olt_lcp WHERE id = ?");
        $stmt->execute([$input['id']]);
        if (!$stmt->fetch()) {
            throw new Exception('LCP not found');
        }

        // Check if this LCP is used as a parent by other LCPs
        $stmt = $db->prepare("
            SELECT id, name 
            FROM olt_lcp 
            WHERE mother_nap_type = 'LCP' 
            AND mother_nap_id = ?
        ");
        $stmt->execute([$input['id']]);
        $children = $stmt->fetchAll(PDO::FETCH_ASSOC);
        
        if (!empty($children)) {
            $child_names = array_map(function($child) {
                return $child['name'];
            }, $children);
            throw new Exception('Cannot delete LCP: It is being used as parent by: ' . implode(', ', $child_names));
        }

        // Check if this LCP has any NAPs connected
        $stmt = $db->prepare("SELECT id FROM nap WHERE lcp_id = ?");
        $stmt->execute([$input['id']]);
        if ($stmt->fetch()) {
            throw new Exception('Cannot delete LCP: It has NAP boxes connected');
        }

        // Delete the LCP
        $stmt = $db->prepare("DELETE FROM olt_lcp WHERE id = ?");
        $stmt->execute([$input['id']]);

        // Commit transaction
        $db->commit();

        echo json_encode([
            'success' => true,
            'message' => 'LCP deleted successfully'
        ]);

    } catch (Exception $e) {
        // Rollback transaction on error
        $db->rollBack();
        throw $e;
    }

} catch (Exception $e) {
    error_log('Error in ftth_delete_lcp.php: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
