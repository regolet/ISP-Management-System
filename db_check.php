<?php

try {
    $db = new SQLite3('database/isp-management.sqlite');

    $query = "SELECT COUNT(*) AS total FROM plans";
    $result = $db->querySingle($query, true);

    if ($result) {
        echo "Database connection successful!\n";
        echo "Total number of plans: " . $result['total'] . "\n";
    } else {
        echo "Error retrieving data from the database.\n";
    }

    $db->close();
} catch (Exception $e) {
    echo "Error connecting to the database: " . $e->getMessage() . "\n";
}

?>