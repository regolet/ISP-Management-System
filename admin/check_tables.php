<?php
require_once 'config.php';

try {
    echo "<h3>Database Table Check</h3>";
    
    // Check if tables exist
    $tables = ["employees", "payroll_periods", "payroll_items"];
    foreach ($tables as $table) {
        $result = $conn->query("SHOW TABLES LIKE '$table'");
        echo "<br>Table '$table': " . ($result->num_rows > 0 ? "EXISTS" : "MISSING");
        
        if ($result->num_rows > 0) {
            // Show table structure
            echo "<br>Structure:<br>";
            $columns = $conn->query("SHOW COLUMNS FROM $table");
            while ($col = $columns->fetch_assoc()) {
                echo "- {$col['Field']} ({$col['Type']})<br>";
            }
        }
        echo "<hr>";
    }

    // Show any sample data
    $emp_count = $conn->query("SELECT COUNT(*) as count FROM employees")->fetch_assoc();
    echo "<br>Number of employees: " . $emp_count['count'];

} catch (Exception $e) {
    die("<br>Error: " . $e->getMessage());
}
?>
