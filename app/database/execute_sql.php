<?php
namespace App\Database;

use App\Core\Database;

require_once '../core/Database.php';

// Load database configuration
$config = require_once '../config/database.php';

// Create a new database instance
$db = new Database($config);

// Check if SQL command is provided
if (isset($argv[1])) {
    $sql = $argv[1];

    // Execute the SQL command
    try {
        $result = $db->execute($sql);
        if ($result) {
            echo "SQL command executed successfully.\n";
        } else {
            echo "Failed to execute SQL command.\n";
        }
    } catch (\Exception $e) {
        echo "Error: " . $e->getMessage() . "\n";
    }
} else {
    echo "No SQL command provided.\n";
}
