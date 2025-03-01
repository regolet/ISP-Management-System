<?php
require_once '../config.php';
check_auth();

header('Content-Type: application/json');

try {
    // Validate input
    $required = ['name', 'lcp_id', 'port_no', 'port_count', 'meters_nap'];
    foreach ($required as $field) {
        if (!isset($_POST[$field]) || empty($_POST[$field])) {
            throw new Exception("Missing required field: $field");
        }
    }

    $name = trim($_POST['name']);
    $lcpId = intval($_POST['lcp_id']);
    $portNo = intval($_POST['port_no']);
    $portCount = intval($_POST['port_count']);
    $metersNap = floatval($_POST['meters_nap']);

    // Start transaction
    $db->beginTransaction();

    // Check if LCP exists and get its total ports
    $stmt = $db->prepare("
        SELECT total_ports, used_ports 
        FROM olt_lcps 
        WHERE id = ?
    ");
    $stmt->execute([$lcpId]);
    $lcp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lcp) {
        throw new Exception("Selected LCP not found");
    }

    // Validate port number
    if ($portNo < 1 || $portNo > $lcp['total_ports']) {
        throw new Exception("Invalid port number");
    }

    // Validate port count
    if ($portCount < 1 || $portCount > 24) {
        throw new Exception("Invalid number of ports (1-24 allowed)");
    }

    // Check for duplicate name
    $stmt = $db->prepare("
        SELECT id 
        FROM olt_naps 
        WHERE name = ?
    ");
    $stmt->execute([$name]);
    if ($stmt->fetch()) {
        throw new Exception("A NAP with this name already exists");
    }

    // Check if port is already in use
    $stmt = $db->prepare("
        SELECT id 
        FROM olt_naps 
        WHERE lcp_id = ? AND port_no = ?
    ");
    $stmt->execute([$lcpId, $portNo]);
    if ($stmt->fetch()) {
        throw new Exception("Selected LCP port is already in use");
    }

    // Insert NAP record
    $stmt = $db->prepare("
        INSERT INTO olt_naps (
            name,
            lcp_id,
            port_no,
            port_count,
            meters_nap,
            client_count
        )
        VALUES (?, ?, ?, ?, ?, 0)
    ");
    $stmt->execute([
        $name,
        $lcpId,
        $portNo,
        $portCount,
        $metersNap
    ]);
    $napId = $db->lastInsertId();

    // Update LCP used ports count
    $stmt = $db->prepare("
        UPDATE olt_lcps 
        SET used_ports = used_ports + 1 
        WHERE id = ?
    ");
    $stmt->execute([$lcpId]);

    $db->commit();

    echo json_encode([
        'success' => true,
        'message' => 'NAP added successfully',
        'data' => [
            'id' => $napId,
            'name' => $name,
            'lcp_id' => $lcpId,
            'port_no' => $portNo,
            'port_count' => $portCount,
            'meters_nap' => $metersNap,
            'client_count' => 0
        ]
    ]);

} catch (Exception $e) {
    if (isset($db) && $db->inTransaction()) {
        $db->rollBack();
    }
    
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
