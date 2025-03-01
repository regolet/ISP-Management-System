<?php
require_once 'config.php';

try {
    $result = $conn->query("SHOW CREATE TABLE payroll_items");
    $table_def = $result->fetch_assoc();
    echo "<pre>";
    print_r($table_def);
    echo "</pre>";
} catch (Exception $e) {
    die("Error: " . $e->getMessage());
}
?>
