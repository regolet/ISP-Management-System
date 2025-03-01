<?php
require_once '../config.php';
check_auth();

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception("NAP ID is required");
    }

    $napId = intval($_GET['id']);

    // Get NAP details with LCP connection
    $stmt = $db->prepare("
        SELECT 
            n.id,
            n.name,
            n.lcp_id,
            n.port_no,
            n.port_count,
            n.client_count,
            n.meters_nap,
            l.name as lcp_name,
            l.mother_nap_type,
            l.mother_nap_id,
            l.pon_port,
            l.splitter_loss,
            l.meters_lcp,
            st.name as splitter_name,
            st.loss as splitter_loss,
            CASE 
                WHEN l.mother_nap_type = 'OLT' THEN o.name
                ELSE p_lcp.name
            END as mother_nap,
            CASE 
                WHEN l.mother_nap_type = 'OLT' THEN o.pon_type
                ELSE NULL
            END as pon_type,
            CASE 
                WHEN l.mother_nap_type = 'OLT' THEN o.tx_power
                ELSE NULL
            END as tx_power
        FROM olt_naps n
        JOIN olt_lcps l ON n.lcp_id = l.id
        LEFT JOIN olt_devices o ON l.mother_nap_type = 'OLT' AND l.mother_nap_id = o.id
        LEFT JOIN olt_lcps p_lcp ON l.mother_nap_type = 'LCP' AND l.mother_nap_id = p_lcp.id
        LEFT JOIN olt_splitter_types st ON l.splitter_type = st.id
        WHERE n.id = ?
    ");
    $stmt->execute([$napId]);
    $nap = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$nap) {
        throw new Exception("NAP not found");
    }

    // Calculate total loss including fiber lengths
    $lcpFiberLoss = ($nap['meters_lcp'] / 1000) * 0.35; // 0.35 dB/km typical fiber loss
    $napFiberLoss = ($nap['meters_nap'] / 1000) * 0.35;
    $totalLoss = $nap['splitter_loss'] + $lcpFiberLoss + $napFiberLoss;

    // Calculate power budget if connected to OLT
    $powerBudget = null;
    if ($nap['mother_nap_type'] === 'OLT' && $nap['tx_power']) {
        $receiverSensitivity = -28; // dBm (typical value)
        $marginRequired = 3; // dB (safety margin)
        $powerBudget = $nap['tx_power'] - $receiverSensitivity - $totalLoss - $marginRequired;
    }

    // Format response
    $response = [
        'id' => $nap['id'],
        'name' => $nap['name'],
        'lcp' => [
            'id' => $nap['lcp_id'],
            'name' => $nap['lcp_name'],
            'port_no' => intval($nap['port_no']),
            'splitter_name' => $nap['splitter_name'],
            'splitter_loss' => floatval($nap['splitter_loss']),
            'meters_lcp' => intval($nap['meters_lcp']),
            'mother_nap_type' => $nap['mother_nap_type'],
            'mother_nap' => $nap['mother_nap'],
            'pon_port' => intval($nap['pon_port']),
            'pon_type' => $nap['pon_type'],
            'tx_power' => $nap['tx_power'] ? floatval($nap['tx_power']) : null
        ],
        'port_count' => intval($nap['port_count']),
        'client_count' => intval($nap['client_count']),
        'meters_nap' => intval($nap['meters_nap']),
        'loss_details' => [
            'splitter_loss' => floatval($nap['splitter_loss']),
            'lcp_fiber_loss' => round($lcpFiberLoss, 1),
            'nap_fiber_loss' => round($napFiberLoss, 1),
            'total_loss' => round($totalLoss, 1)
        ]
    ];

    if ($powerBudget !== null) {
        $response['power_budget'] = [
            'value' => round($powerBudget, 1),
            'status' => $powerBudget >= 0 ? 'ok' : 'insufficient'
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $response
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
