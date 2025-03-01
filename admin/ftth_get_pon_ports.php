<?php
require_once '../config.php';
check_auth();

header('Content-Type: application/json');

try {
    if (!isset($_GET['olt_id'])) {
        throw new Exception('OLT ID is required');
    }

    $oltId = $_GET['olt_id'];

    // Validate OLT ID
    if (!is_numeric($oltId) || $oltId < 1) {
        throw new Exception('Invalid OLT ID');
    }

    // Establish database connection
    $conn = get_db_connection();

    // First get the OLT details to know number of PONs
    $stmt = $conn->prepare("SELECT id, name, number_of_pons FROM olt_devices WHERE id = ?");
    $stmt->bindValue(1, (int)$oltId, PDO::PARAM_INT);
    $stmt->execute();
    $olt = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$olt) {
        error_log("OLT not found with ID: {$oltId}");
        throw new Exception('OLT not found');
    }

    error_log("Getting PON ports for OLT: {$olt['name']} (ID: {$oltId})");

    // Get list of PON ports already in use by NAP boxes
    $stmt = $conn->prepare("
        SELECT pon_port, name as napbox_name 
        FROM olt_napboxs 
        WHERE olt_id = ? 
        ORDER BY pon_port
    ");
    $stmt->bindValue(1, (int)$oltId, PDO::PARAM_INT);
    $stmt->execute();
    $usedPortsData = $stmt->fetchAll(PDO::FETCH_ASSOC);
    $usedPorts = array_column($usedPortsData, 'pon_port');

    error_log("Found " . count($usedPorts) . " used PON ports");

    // Create array of all ports with their status
    $allPorts = [];
    for ($i = 1; $i <= $olt['number_of_pons']; $i++) {
        $portInfo = ['port_number' => $i];
        if (in_array($i, $usedPorts)) {
            $napbox = current(array_filter($usedPortsData, function($p) use ($i) {
                return $p['pon_port'] == $i;
            }));
            $portInfo['status'] = 'used';
            $portInfo['napbox_name'] = $napbox['napbox_name'];
        } else {
            $portInfo['status'] = 'available';
        }
        $allPorts[] = $portInfo;
    }

    echo json_encode([
        'success' => true,
        'data' => $allPorts,
        'olt_info' => [
            'name' => $olt['name'],
            'total_ports' => $olt['number_of_pons'],
            'used_ports' => count($usedPorts),
            'available_ports' => $olt['number_of_pons'] - count($usedPorts)
        ]
    ]);
} catch (Exception $e) {
    error_log("Error in ftth_get_pon_ports.php: " . $e->getMessage());
    error_log("Stack trace: " . $e->getTraceAsString());
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage(),
        'details' => 'Check server logs for more information'
    ]);
}
?>
