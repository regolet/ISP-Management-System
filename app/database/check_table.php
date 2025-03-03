<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Application;

try {
    // Get database connection
    $db = Application::getInstance()->getDB()->getConnection();
    
    // Check if table exists
    $result = $db->query("SHOW TABLES LIKE 'users'");
    if ($result->rowCount() > 0) {
        echo "Users table exists.\n";
        
        // Show table structure
        $result = $db->query("DESCRIBE users");
        echo "\nTable structure:\n";
        while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
            echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']}\n";
        }
    } else {
        echo "Users table does not exist.\n";
        
        // Create table
        echo "\nCreating users table...\n";
        $sql = file_get_contents(__DIR__ . '/migrations/create_users_table.sql');
        $db->exec($sql);
        echo "Users table created successfully.\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
