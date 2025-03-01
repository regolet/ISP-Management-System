<?php
// Start output buffering
ob_start();

require_once '../config.php';
check_auth();

// Clear any previous output and set JSON header
ob_clean();
header('Content-Type: application/json');

// Disable error display for this script
error_reporting(0);
ini_set('display_errors', 0);

try {
    // Log incoming data for debugging
    error_log('POST data: ' . print_r($_POST, true));

    if (!isset($_POST['name']) || !isset($_POST['pon_type']) || !isset($_POST['number_of_pons']) || !isset($_POST['tx_power'])) {
        throw new Exception('Missing required fields');
    }

    $name = $_POST['name'];
    $pon_type = $_POST['pon_type'];
    $number_of_pons = intval($_POST['number_of_pons']);
    $tx_power = floatval($_POST['tx_power']);

    // Validate inputs
    if (empty($name)) {
        throw new Exception('OLT name is required');
    }
    if (!in_array($pon_type, ['GPON', 'EPON', 'XGS-PON'])) {
        throw new Exception('Invalid PON type');
    }
    if ($number_of_pons < 1) {
        throw new Exception('Number of PON ports must be at least 1');
    }
    
    // For non-EPON types, force TX power to 9 dBm
    if ($pon_type !== 'EPON') {
        $tx_power = 9.0;
    }

    $db = get_db_connection();
    if (!$db) {
        throw new Exception('Database connection failed');
    }

    // Using PDO instead of mysqli
    $stmt = $db->prepare("INSERT INTO olt_devices (name, pon_type, number_of_pons, tx_power) VALUES (:name, :pon_type, :number_of_pons, :tx_power)");
    if (!$stmt) {
        throw new Exception('Failed to prepare statement: ' . implode(', ', $db->errorInfo()));
    }

    $stmt->bindParam(':name', $name, PDO::PARAM_STR);
    $stmt->bindParam(':pon_type', $pon_type, PDO::PARAM_STR);
    $stmt->bindParam(':number_of_pons', $number_of_pons, PDO::PARAM_INT);
    $stmt->bindParam(':tx_power', $tx_power, PDO::PARAM_STR);
    
    if (!$stmt->execute()) {
        throw new Exception('Failed to add OLT: ' . implode(', ', $stmt->errorInfo()));
    }

    $newId = $db->lastInsertId();
    if (!$newId) {
        throw new Exception('Failed to get new OLT ID');
    }

    // Clear any potential output before sending JSON response
    while (ob_get_level()) {
        ob_end_clean();
    }

    echo json_encode([
        'success' => true,
        'message' => 'OLT added successfully',
        'data' => [
            'id' => $newId,
            'name' => $name,
            'pon_type' => $pon_type,
            'number_of_pons' => $number_of_pons,
            'tx_power' => $tx_power
        ]
    ]);

} catch (Exception $e) {
    error_log('Error in ftth_add_olt.php: ' . $e->getMessage());
    
    // Clear any potential output before sending JSON response
    while (ob_get_level()) {
        ob_end_clean();
    }

    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => 'Check server logs for more information'
    ]);
} catch (Error $e) {
    error_log('Fatal error in ftth_add_olt.php: ' . $e->getMessage());
    
    // Clear any potential output before sending JSON response
    while (ob_get_level()) {
        ob_end_clean();
    }

    echo json_encode([
        'success' => false,
        'error' => 'Internal server error'
    ]);
}

// Ensure no further output
exit();
