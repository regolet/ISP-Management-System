<?php
// Database connection parameters
$host = 'localhost';
$user = 'root';
$pass = '';
$dbname = 'isp';

// Create connection with error handling
try {
    $conn = new mysqli($host, $user, $pass);
    if ($conn->connect_error) {
        throw new Exception("Connection failed: " . $conn->connect_error);
    }

    // Create database if not exists and select it
    $conn->query("CREATE DATABASE IF NOT EXISTS $dbname");
    $conn->select_db($dbname);

    // Get all tables if no specific tables are specified
    $tables_query = $conn->query("SHOW TABLES");
    $tables = [];
    while ($table = $tables_query->fetch_array()) {
        $tables[] = $table[0];
    }

    foreach ($tables as $tableName) {
        echo "\nTable: $tableName\n";
        echo str_repeat("=", strlen($tableName) + 7) . "\n";
        
        // Get table creation info
        $create_table = $conn->query("SHOW CREATE TABLE `$tableName`")->fetch_array();
        echo "\nCreate Table Statement:\n";
        echo $create_table[1] . "\n\n";
        
        // Get columns
        $describe = $conn->query("DESCRIBE `$tableName`");
        if ($describe) {
            echo "Columns:\n";
            echo str_repeat("-", 80) . "\n";
            printf("%-20s %-30s %-8s %-8s %s\n", 
                "Field", "Type", "Null", "Key", "Default");
            echo str_repeat("-", 80) . "\n";
            
            while ($col = $describe->fetch_assoc()) {
                printf("%-20s %-30s %-8s %-8s %s\n",
                    $col['Field'],
                    $col['Type'],
                    $col['Null'],
                    $col['Key'],
                    $col['Default'] ? $col['Default'] : 'NULL'
                );
            }
        }
        
        // Get foreign keys
        $foreignKeys = $conn->query("
            SELECT 
                COLUMN_NAME,
                REFERENCED_TABLE_NAME,
                REFERENCED_COLUMN_NAME,
                CONSTRAINT_NAME
            FROM information_schema.KEY_COLUMN_USAGE 
            WHERE TABLE_SCHEMA = '$dbname'
            AND TABLE_NAME = '$tableName' 
            AND REFERENCED_TABLE_NAME IS NOT NULL
        ");
        
        if ($foreignKeys && $foreignKeys->num_rows > 0) {
            echo "\nForeign Keys:\n";
            echo str_repeat("-", 80) . "\n";
            while ($fk = $foreignKeys->fetch_assoc()) {
                echo sprintf("%-20s -> %-20s (%-20s) [%s]\n",
                    $fk['COLUMN_NAME'],
                    $fk['REFERENCED_TABLE_NAME'],
                    $fk['REFERENCED_COLUMN_NAME'],
                    $fk['CONSTRAINT_NAME']
                );
            }
        }
        
        // Get indexes
        $indexes = $conn->query("SHOW INDEX FROM `$tableName`");
        if ($indexes && $indexes->num_rows > 0) {
            echo "\nIndexes:\n";
            echo str_repeat("-", 80) . "\n";
            $current_key = '';
            while ($idx = $indexes->fetch_assoc()) {
                if ($current_key != $idx['Key_name']) {
                    if ($current_key != '') echo "\n";
                    echo "- {$idx['Key_name']}: ";
                    $current_key = $idx['Key_name'];
                } else {
                    echo ", ";
                }
                echo $idx['Column_name'];
                if ($idx['Sub_part']) echo "({$idx['Sub_part']})";
            }
            echo "\n";
        }
        
        // Get table status
        $status = $conn->query("SHOW TABLE STATUS LIKE '$tableName'")->fetch_assoc();
        echo "\nTable Status:\n";
        echo str_repeat("-", 80) . "\n";
        echo sprintf("Engine: %-15s Rows: %-10s Data Length: %-10s\n",
            $status['Engine'],
            $status['Rows'],
            formatBytes($status['Data_length'])
        );
        
        echo "\n" . str_repeat("=", 80) . "\n";
    }

} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
} finally {
    if (isset($conn)) {
        $conn->close();
    }
}

function formatBytes($bytes, $precision = 2) {
    $units = ['B', 'KB', 'MB', 'GB', 'TB'];
    $bytes = max($bytes, 0);
    $pow = floor(($bytes ? log($bytes) : 0) / log(1024));
    $pow = min($pow, count($units) - 1);
    return round($bytes / pow(1024, $pow), $precision) . ' ' . $units[$pow];
}
?>
