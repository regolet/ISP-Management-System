<?php
require_once '../config.php';
check_auth();

header('Content-Type: application/json');

try {
    $db = get_db_connection();
    
    // Get all OLTs
    $stmt = $db->query("SELECT id, name, pon_type, number_of_pons FROM olt_devices ORDER BY name");
    $olts = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    echo json_encode(['success' => true, 'data' => $olts]);
} catch (Exception $e) {
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}