<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Application;

try {
    // Get database connection
    $db = Application::getInstance()->getDB()->getConnection();
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/migrations/create_users_table.sql');
    
    // Execute the SQL
    $result = $db->exec($sql);
    
    echo "Users table created/updated successfully.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
