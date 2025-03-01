<?php
require_once '../config.php';
check_auth();

header('Content-Type: application/json');

try {
    // Get LCPs with their connections and usage info
    $stmt = $db->query("
        SELECT 
            l.id,
            l.name,
            l.mother_nap_type,
            l.mother_nap_id,
            l.pon_port,
            l.total_ports,
            l.used_ports,
            l.splitter_loss,
            l.meters_lcp,
            st.name as splitter_type,
            st.loss,
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
        FROM olt_lcps l
        LEFT JOIN olt_devices o ON l.mother_nap_type = 'OLT' AND l.mother_nap_id = o.id
        LEFT JOIN olt_lcps p_lcp ON l.mother_nap_type = 'LCP' AND l.mother_nap_id = p_lcp.id
        LEFT JOIN olt_splitter_types st ON l.splitter_type = st.id
        ORDER BY l.name
    ");

    $lcps = [];
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        // Calculate port usage percentage
        $portUsage = $row['total_ports'] > 0 
            ? ($row['used_ports'] / $row['total_ports']) * 100 
            : 0;

        // Calculate total loss including fiber
        $fiberLoss = ($row['meters_lcp'] / 1000) * 0.35; // 0.35 dB/km typical fiber loss
        $totalLoss = $row['splitter_loss'] + $fiberLoss;

        $lcps[] = [
            'id' => $row['id'],
            'name' => $row['name'],
            'mother_nap_type' => $row['mother_nap_type'],
            'mother_nap_id' => $row['mother_nap_id'],
            'mother_nap' => $row['mother_nap'],
            'pon_port' => $row['pon_port'],
            'pon_type' => $row['pon_type'],
            'tx_power' => $row['tx_power'] ? floatval($row['tx_power']) : null,
            'splitter_type' => $row['splitter_type'],
            'total_ports' => intval($row['total_ports']),
            'used_ports' => intval($row['used_ports']),
            'port_usage' => round($portUsage, 1),
            'splitter_loss' => floatval($row['splitter_loss']),
            'meters_lcp' => intval($row['meters_lcp']),
            'total_loss' => round($totalLoss, 1)
        ];
    }

    echo json_encode([
        'success' => true,
        'data' => $lcps
    ]);

} catch (Exception $e) {
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
