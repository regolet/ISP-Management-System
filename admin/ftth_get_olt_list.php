<?php
require_once '../config.php';
check_auth();

header('Content-Type: application/json');

try {
    // Get OLTs with their port usage statistics
    $stmt = $db->query("
        SELECT 
            o.id,
            o.name,
            o.pon_type,
            o.tx_power,
            o.number_of_pons,
            o.created_at,
            o.updated_at,
            COUNT(DISTINCT p.id) as total_ports,
            SUM(CASE WHEN p.status = 'in_use' THEN 1 ELSE 0 END) as used_ports,
            COUNT(DISTINCT l.id) as connected_lcps,
            COALESCE(SUM(n.client_count), 0) as total_clients,
            ROUND(
                (SUM(CASE WHEN p.status = 'in_use' THEN 1 ELSE 0 END) / COUNT(DISTINCT p.id) * 100),
                1
            ) as port_utilization
        FROM olt_devices o
        LEFT JOIN olt_ports p ON o.id = p.olt_device_id
        LEFT JOIN olt_lcps l ON l.mother_nap_type = 'OLT' AND l.mother_nap_id = o.id
        LEFT JOIN olt_naps n ON n.lcp_id = l.id
        GROUP BY o.id
        ORDER BY o.name
    ");

    $olts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Get additional details for each OLT
    foreach ($olts as &$olt) {
        // Get available PON ports
        $stmt = $db->prepare("
            SELECT port_no
            FROM olt_ports
            WHERE olt_device_id = ? AND status = 'available'
            ORDER BY port_no
        ");
        $stmt->execute([$olt['id']]);
        $olt['available_ports'] = $stmt->fetchAll(PDO::FETCH_COLUMN);

        // Get LCP connections
        $stmt = $db->prepare("
            SELECT 
                l.id,
                l.name,
                l.pon_port,
                st.name as splitter_type,
                l.total_ports,
                l.used_ports,
                l.meters_lcp,
                COUNT(n.id) as connected_naps,
                SUM(n.client_count) as client_count
            FROM olt_lcps l
            JOIN olt_splitter_types st ON l.splitter_type = st.id
            LEFT JOIN olt_naps n ON n.lcp_id = l.id
            WHERE l.mother_nap_type = 'OLT' AND l.mother_nap_id = ?
            GROUP BY l.id
            ORDER BY l.pon_port
        ");
        $stmt->execute([$olt['id']]);
        $olt['lcps'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Calculate power budgets for each connection
        foreach ($olt['lcps'] as &$lcp) {
            // Get NAPs connected to this LCP
            $stmt = $db->prepare("
                SELECT 
                    n.id,
                    n.name,
                    n.port_no,
                    n.port_count,
                    n.client_count,
                    n.meters_nap,
                    (? - l.splitter_loss - ((l.meters_lcp + n.meters_nap) / 1000 * 0.35) - 3) as power_budget
                FROM olt_naps n
                JOIN olt_lcps l ON n.lcp_id = l.id
                WHERE l.id = ?
                ORDER BY n.port_no
            ");
            $stmt->execute([$olt['tx_power'], $lcp['id']]);
            $lcp['naps'] = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // Add power budget warnings
            $lcp['warnings'] = [];
            foreach ($lcp['naps'] as $nap) {
                if ($nap['power_budget'] < 0) {
                    $lcp['warnings'][] = [
                        'type' => 'critical',
                        'message' => "Critical power budget for NAP {$nap['name']}: {$nap['power_budget']} dB"
                    ];
                } elseif ($nap['power_budget'] < 2) {
                    $lcp['warnings'][] = [
                        'type' => 'warning',
                        'message' => "Low power budget for NAP {$nap['name']}: {$nap['power_budget']} dB"
                    ];
                }
            }
        }

        // Add status based on utilization
        $olt['status'] = [
            'code' => $olt['port_utilization'] > 80 ? 'warning' : 
                     ($olt['port_utilization'] > 0 ? 'active' : 'inactive'),
            'message' => $olt['port_utilization'] > 80 ? 'High utilization' : 
                        ($olt['port_utilization'] > 0 ? 'Active' : 'Inactive')
        ];

        // Calculate total fiber distance
        $olt['total_fiber_km'] = array_reduce($olt['lcps'], function($total, $lcp) {
            return $total + ($lcp['meters_lcp'] / 1000) + array_reduce($lcp['naps'], function($sum, $nap) {
                return $sum + ($nap['meters_nap'] / 1000);
            }, 0);
        }, 0);
    }

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $olts,
        'summary' => [
            'total_olts' => count($olts),
            'total_clients' => array_sum(array_column($olts, 'total_clients')),
            'total_fiber_km' => array_sum(array_column($olts, 'total_fiber_km')),
            'average_utilization' => array_sum(array_column($olts, 'port_utilization')) / count($olts)
        ]
    ]);

} catch (Exception $e) {
    // Log error
    error_log('Error getting OLT list: ' . $e->getMessage());

    // Return error response
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
