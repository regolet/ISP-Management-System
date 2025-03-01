<?php
require_once '../config.php';
check_auth();

try {
    $db = get_db_connection();
    
    // Get export format from query string (default to CSV)
    $format = isset($_GET['format']) ? strtolower($_GET['format']) : 'csv';
    
    // Fetch LCP data with related information
    $query = "
        SELECT 
            l.id,
            l.name as lcp_name,
            l.mother_nap_type as connection_type,
            CASE 
                WHEN l.mother_nap_type = 'OLT' THEN o.name
                WHEN l.mother_nap_type = 'LCP' THEN pl.name
            END as connected_to,
            l.pon_port,
            plc.ports as splitter_ports,
            plc.loss as splitter_loss,
            l.fiber_length,
            l.connector_count,
            (
                SELECT COUNT(*) 
                FROM olt_napboxs 
                WHERE mother_nap = l.id AND mother_nap_type = 'LCP'
            ) as used_ports,
            l.created_at,
            l.updated_at,
            (
                SELECT GROUP_CONCAT(CONCAT(n.name, ':', n.port_no))
                FROM olt_napboxs n
                WHERE n.mother_nap = l.id AND n.mother_nap_type = 'LCP'
            ) as connected_naps,
            (
                SELECT COUNT(DISTINCT o.id)
                FROM olt_onus o
                JOIN olt_napboxs n ON o.nap_id = n.id
                WHERE n.mother_nap = l.id AND n.mother_nap_type = 'LCP'
            ) as total_onus
        FROM lcp l
        LEFT JOIN olts o ON l.mother_nap_id = o.id AND l.mother_nap_type = 'OLT'
        LEFT JOIN lcp pl ON l.mother_nap_id = pl.id AND l.mother_nap_type = 'LCP'
        LEFT JOIN olt_loss_plc plc ON l.splitter_type = plc.id
        ORDER BY l.name
    ";

    $stmt = $db->query($query);
    $lcps = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate additional metrics
    foreach ($lcps as &$lcp) {
        // Calculate total loss
        $lcp['total_loss'] = $lcp['splitter_loss'];
        if ($lcp['fiber_length']) {
            $lcp['total_loss'] += $lcp['fiber_length'] * 0.35; // 0.35 dB/km fiber loss
        }
        if ($lcp['connector_count']) {
            $lcp['total_loss'] += $lcp['connector_count'] * 0.3; // 0.3 dB connector loss
        }
        
        // Calculate port usage percentage
        $lcp['port_usage'] = $lcp['splitter_ports'] > 0 ? 
            round(($lcp['used_ports'] / $lcp['splitter_ports']) * 100, 2) : 0;
        
        // Format dates
        $lcp['created_at'] = date('Y-m-d H:i:s', strtotime($lcp['created_at']));
        $lcp['updated_at'] = $lcp['updated_at'] ? 
            date('Y-m-d H:i:s', strtotime($lcp['updated_at'])) : 'Never';
    }

    // Export based on format
    switch ($format) {
        case 'json':
            header('Content-Type: application/json');
            header('Content-Disposition: attachment; filename="lcp_export_' . date('Y-m-d') . '.json"');
            echo json_encode([
                'export_date' => date('Y-m-d H:i:s'),
                'total_lcps' => count($lcps),
                'data' => $lcps
            ], JSON_PRETTY_PRINT);
            break;

        case 'excel':
            require_once 'vendor/autoload.php'; // Make sure PHPSpreadsheet is installed
            
            $spreadsheet = new \PhpOffice\PhpSpreadsheet\Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            
            // Set headers
            $headers = [
                'LCP Name', 'Connection Type', 'Connected To', 'PON Port',
                'Splitter Ports', 'Splitter Loss (dB)', 'Total Loss (dB)',
                'Used Ports', 'Port Usage %', 'Total ONUs',
                'Connected NAPs', 'Created At', 'Last Updated'
            ];
            
            $col = 'A';
            foreach ($headers as $header) {
                $sheet->setCellValue($col . '1', $header);
                $sheet->getColumnDimension($col)->setAutoSize(true);
                $col++;
            }
            
            // Add data
            $row = 2;
            foreach ($lcps as $lcp) {
                $sheet->setCellValue('A' . $row, $lcp['lcp_name']);
                $sheet->setCellValue('B' . $row, $lcp['connection_type']);
                $sheet->setCellValue('C' . $row, $lcp['connected_to']);
                $sheet->setCellValue('D' . $row, $lcp['pon_port']);
                $sheet->setCellValue('E' . $row, $lcp['splitter_ports']);
                $sheet->setCellValue('F' . $row, $lcp['splitter_loss']);
                $sheet->setCellValue('G' . $row, $lcp['total_loss']);
                $sheet->setCellValue('H' . $row, $lcp['used_ports']);
                $sheet->setCellValue('I' . $row, $lcp['port_usage']);
                $sheet->setCellValue('J' . $row, $lcp['total_onus']);
                $sheet->setCellValue('K' . $row, $lcp['connected_naps']);
                $sheet->setCellValue('L' . $row, $lcp['created_at']);
                $sheet->setCellValue('M' . $row, $lcp['updated_at']);
                $row++;
            }
            
            // Style the spreadsheet
            $styleArray = [
                'font' => ['bold' => true],
                'fill' => [
                    'fillType' => \PhpOffice\PhpSpreadsheet\Style\Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E0E0E0']
                ]
            ];
            $sheet->getStyle('A1:M1')->applyFromArray($styleArray);
            
            header('Content-Type: application/vnd.openxmlformats-officedocument.spreadsheetml.sheet');
            header('Content-Disposition: attachment; filename="lcp_export_' . date('Y-m-d') . '.xlsx"');
            
            $writer = new \PhpOffice\PhpSpreadsheet\Writer\Xlsx($spreadsheet);
            $writer->save('php://output');
            break;

        default: // CSV
            header('Content-Type: text/csv');
            header('Content-Disposition: attachment; filename="lcp_export_' . date('Y-m-d') . '.csv"');
            
            $output = fopen('php://output', 'w');
            
            // Add headers
            fputcsv($output, [
                'LCP Name', 'Connection Type', 'Connected To', 'PON Port',
                'Splitter Ports', 'Splitter Loss (dB)', 'Total Loss (dB)',
                'Used Ports', 'Port Usage %', 'Total ONUs',
                'Connected NAPs', 'Created At', 'Last Updated'
            ]);
            
            // Add data
            foreach ($lcps as $lcp) {
                fputcsv($output, [
                    $lcp['lcp_name'],
                    $lcp['connection_type'],
                    $lcp['connected_to'],
                    $lcp['pon_port'],
                    $lcp['splitter_ports'],
                    $lcp['splitter_loss'],
                    $lcp['total_loss'],
                    $lcp['used_ports'],
                    $lcp['port_usage'],
                    $lcp['total_onus'],
                    $lcp['connected_naps'],
                    $lcp['created_at'],
                    $lcp['updated_at']
                ]);
            }
            
            fclose($output);
            break;
    }

} catch (Exception $e) {
    error_log('Error in ftth_export_lcp.php: ' . $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'success' => false,
        'error' => 'Failed to export LCP data: ' . $e->getMessage()
    ]);
}
