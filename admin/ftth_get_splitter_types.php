<?php
require_once '../config.php';
check_auth();

header('Content-Type: application/json');

try {
    // Get all splitter types
    $stmt = $db->query("
        SELECT 
            id,
            name,
            ports,
            loss
        FROM olt_splitter_types
        ORDER BY ports ASC
    ");

    $types = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $types[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'ports' => intval($row['ports']),
            'loss' => floatval($row['loss'])
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $types
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
