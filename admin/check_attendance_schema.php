<?php
require_once 'config.php';

try {
    // Check attendance table schema
    $result = $conn->query("SHOW TABLES LIKE 'attendance'");
    if ($result->num_rows === 0) {
        // Create attendance table with correct schema
        $conn->query("CREATE TABLE attendance (
            id INT AUTO_INCREMENT PRIMARY KEY,
            employee_id INT NOT NULL,
            date DATE NOT NULL,
            time_in TIME,
            time_out TIME,
            status ENUM('present', 'absent', 'late', 'half_day') DEFAULT 'present',
            notes TEXT,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (employee_id) REFERENCES employees(id),
            UNIQUE KEY unique_attendance (employee_id, date)
        )");
        echo "Attendance table created successfully!<br>";
    }

    // Show current schema
    echo "<h3>Current Attendance Table Structure:</h3>";
    $columns = $conn->query("SHOW COLUMNS FROM attendance");
    while ($col = $columns->fetch_assoc()) {
        echo "- {$col['Field']} ({$col['Type']})<br>";
    }

} catch (Exception $e) {
    die("Setup error: " . $e->getMessage());
}
