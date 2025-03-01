<?php
require_once '../config.php';
check_auth();

header('Content-Type: application/json');

try {
    $conn = get_db_connection();
    if (!$conn) {
        throw new Exception('Database connection failed');
    }

    // Get NAP box list with connection details
    $query = "
        SELECT 
            n.*,
            CASE 
                WHEN n.mother_nap_type = 'OLT' THEN (
                    SELECT name
                    FROM olts o 
                    WHERE o.id = n.mother_nap
                )
                WHEN n.mother_nap_type = 'LCP' THEN (
                    SELECT name
                    FROM ftth_lcp p 
                    WHERE p.id = n.mother_nap
                )
            END as mother_nap_name
        FROM olt_napboxs n
        ORDER BY n.name ASC
    ";

    $stmt = $conn->query($query);
    if (!$stmt) {
        throw new Exception('Failed to execute NAP box query');
    }

    $napboxes = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Return success response
    echo json_encode([
        'success' => true,
        'data' => $napboxes
    ]);

} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Database error occurred',
        'debug_message' => $e->getMessage()
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => $e->getMessage()
    ]);
}
?>
