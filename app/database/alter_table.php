<?php
require_once __DIR__ . '/../../vendor/autoload.php';

use App\Core\Application;

try {
    // Get database connection
    $db = Application::getInstance()->getDB()->getConnection();
    
    echo "Altering users table...\n";
    
    // Read and execute the SQL file
    $sql = file_get_contents(__DIR__ . '/migrations/alter_users_table.sql');
    $db->exec($sql);
    
    echo "Table altered successfully.\n";
    
    // Show updated table structure
    $result = $db->query("DESCRIBE users");
    echo "\nUpdated table structure:\n";
    while ($row = $result->fetch(PDO::FETCH_ASSOC)) {
        echo "{$row['Field']} - {$row['Type']} - {$row['Null']} - {$row['Key']} - {$row['Default']}\n";
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
