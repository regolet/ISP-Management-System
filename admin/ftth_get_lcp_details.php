<?php
require_once '../config.php';
check_auth();

header('Content-Type: application/json');

try {
    if (!isset($_GET['id'])) {
        throw new Exception("LCP ID is required");
    }

    $lcpId = intval($_GET['id']);

    // Get LCP details with connections
    $stmt = $db->prepare("
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
            st.name as splitter_name,
            st.ports as splitter_ports,
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
        WHERE l.id = ?
    ");
    $stmt->execute([$lcpId]);
    $lcp = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$lcp) {
        throw new Exception("LCP not found");
    }

    // Calculate port usage percentage
    $portUsage = $lcp['total_ports'] > 0 
        ? ($lcp['used_ports'] / $lcp['total_ports']) * 100 
        : 0;

    // Get connected NAPs
    $stmt = $db->prepare("
        SELECT 
            n.id,
            n.name,
            n.port_no,
            n.port_count,
            n.client_count,
            n.meters_nap
        FROM olt_naps n
        WHERE n.lcp_id = ?
        ORDER BY n.port_no
    ");
    $stmt->execute([$lcpId]);
    $naps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Format response
    $response = [
        'id' => $lcp['id'],
        'name' => $lcp['name'],
        'mother_nap_type' => $lcp['mother_nap_type'],
        'mother_nap_id' => $lcp['mother_nap_id'],
        'mother_nap' => $lcp['mother_nap'],
        'pon_port' => $lcp['pon_port'],
        'pon_type' => $lcp['pon_type'],
        'tx_power' => $lcp['tx_power'] ? floatval($lcp['tx_power']) : null,
        'splitter_name' => $lcp['splitter_name'],
        'total_ports' => intval($lcp['total_ports']),
        'used_ports' => intval($lcp['used_ports']),
        'port_usage' => round($portUsage, 1),
        'splitter_loss' => floatval($lcp['splitter_loss']),
        'meters_lcp' => intval($lcp['meters_lcp']),
        'naps' => array_map(function($nap) {
            return [
                'id' => $nap['id'],
                'name' => $nap['name'],
                'port_no' => intval($nap['port_no']),
                'port_count' => intval($nap['port_count']),
                'client_count' => intval($nap['client_count']),
                'meters_nap' => intval($nap['meters_nap'])
            ];
        }, $naps)
    ];

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
