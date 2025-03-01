<?php
require_once 'config.php';
check_login();

// Create attendance settings table
$conn->query("CREATE TABLE IF NOT EXISTS attendance_settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    work_start_time TIME NOT NULL DEFAULT '08:00:00',
    work_end_time TIME NOT NULL DEFAULT '17:00:00',
    late_threshold_minutes INT NOT NULL DEFAULT 15,
    half_day_threshold_minutes INT NOT NULL DEFAULT 240,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
)");

// Insert default settings if not exists
$conn->query("INSERT IGNORE INTO attendance_settings (id, work_start_time, work_end_time) VALUES (1, '08:00:00', '17:00:00')");

// Create attendance table
$conn->query("CREATE TABLE IF NOT EXISTS attendance (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    date DATE NOT NULL,
    time_in DATETIME,
    time_out DATETIME,
    status ENUM('present', 'absent', 'late', 'half_day') DEFAULT 'present',
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    created_by INT,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    UNIQUE KEY unique_attendance (employee_id, date)
)");

// Create leave types table
$conn->query("CREATE TABLE IF NOT EXISTS leave_types (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(50) NOT NULL,
    description TEXT,
    paid BOOLEAN DEFAULT TRUE,
    max_days_per_year INT DEFAULT 0,
    status ENUM('active', 'inactive') DEFAULT 'active',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
)");

// Insert default leave types
$leave_types = [
    ['Sick Leave', 'For medical and health-related absences', 1, 15],
    ['Vacation Leave', 'For personal time off and recreation', 1, 15],
    ['Emergency Leave', 'For urgent personal matters', 1, 5],
    ['Unpaid Leave', 'Leave without pay', 0, 0]
];

foreach ($leave_types as $type) {
    $conn->query("INSERT IGNORE INTO leave_types (name, description, paid, max_days_per_year) 
                 VALUES ('{$type[0]}', '{$type[1]}', {$type[2]}, {$type[3]})");
}

// Create leave balances table with standardized column names
$conn->query("CREATE TABLE IF NOT EXISTS leave_balances (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    year INT NOT NULL,
    sick_leave DECIMAL(5,1) DEFAULT 15.0,
    vacation_leave DECIMAL(5,1) DEFAULT 15.0,
    emergency_leave DECIMAL(5,1) DEFAULT 5.0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    UNIQUE KEY unique_balance (employee_id, year)
)");

// Create leaves table
$conn->query("CREATE TABLE IF NOT EXISTS leaves (
    id INT PRIMARY KEY AUTO_INCREMENT,
    employee_id INT NOT NULL,
    leave_type_id INT NOT NULL,
    start_date DATE NOT NULL,
    end_date DATE NOT NULL,
    days DECIMAL(5,1) NOT NULL,
    reason TEXT,
    status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
    approved_by INT,
    approved_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (employee_id) REFERENCES employees(id),
    FOREIGN KEY (leave_type_id) REFERENCES leave_types(id),
    FOREIGN KEY (approved_by) REFERENCES users(id)
)");

// Add daily_rate column to employees table if not exists
$result = $conn->query("SHOW COLUMNS FROM employees LIKE 'daily_rate'");
if ($result->num_rows === 0) {
    $conn->query("ALTER TABLE employees ADD COLUMN daily_rate DECIMAL(10,2) DEFAULT 0.00 AFTER basic_salary");
}

// Create initial leave balances for existing employees
$employees = $conn->query("SELECT id FROM employees WHERE status = 'active'");
$year = date('Y');

while ($employee = $employees->fetch_assoc()) {
    $conn->query("INSERT IGNORE INTO leave_balances (employee_id, year) VALUES ({$employee['id']}, {$year})");
}

echo "Attendance system tables created successfully!";
?>