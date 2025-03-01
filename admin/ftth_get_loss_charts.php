<?php
require_once '../config.php';
check_auth();

header('Content-Type: application/json');

try {
    $db = get_db_connection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Get PLC loss values
    $plcQuery = "SELECT id, ports, loss FROM olt_loss_plc ORDER BY ports ASC";
    $plcStmt = $db->query($plcQuery);
    if (!$plcStmt) {
        throw new Exception('Failed to fetch PLC data');
    }
    $plcData = $plcStmt->fetchAll(PDO::FETCH_ASSOC);

    // Get FBT loss values
    $fbtQuery = "SELECT id, value, loss FROM olt_loss_fbt ORDER BY value ASC";
    $fbtStmt = $db->query($fbtQuery);
    if (!$fbtStmt) {
        throw new Exception('Failed to fetch FBT data');
    }
    $fbtData = $fbtStmt->fetchAll(PDO::FETCH_ASSOC);

    // Return data in the expected format
    echo json_encode([
        'success' => true,
        'data' => [
            'plc' => $plcData,
            'fbt' => $fbtData
        ]
    ]);

} catch (PDOException $e) {
    error_log('Database error in ftth_get_loss_charts.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'details' => $e->getMessage()
    ]);
} catch (Exception $e) {
    error_log('General error in ftth_get_loss_charts.php: ' . $e->getMessage());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
