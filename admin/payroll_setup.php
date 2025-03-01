<?php
// Database connection
$conn = new mysqli('localhost', 'root', '', 'isp');

// Check connection
if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error . "\n");
}

$is_cli = php_sapi_name() === 'cli';
function output($message) {
    global $is_cli;
    if ($is_cli) {
        echo $message . "\n";
    } else {
        echo "<p>$message</p>";
    }
}

output("Setting up Payroll System...");

$sql_queries = [
    // Create employees table
    "CREATE TABLE IF NOT EXISTS employees (
        id INT PRIMARY KEY AUTO_INCREMENT,
        employee_code VARCHAR(20) UNIQUE NOT NULL,
        first_name VARCHAR(50) NOT NULL,
        last_name VARCHAR(50) NOT NULL,
        email VARCHAR(100) UNIQUE,
        phone VARCHAR(20),
        address TEXT,
        position VARCHAR(50) NOT NULL,
        department VARCHAR(50) NOT NULL,
        hire_date DATE NOT NULL,
        basic_salary DECIMAL(10,2) NOT NULL,
        allowance DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('active', 'inactive') DEFAULT 'active',
        sss_no VARCHAR(20),
        philhealth_no VARCHAR(20),
        pagibig_no VARCHAR(20),
        tin_no VARCHAR(20),
        bank_name VARCHAR(50),
        bank_account_no VARCHAR(50),
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )" => "Creating employees table",

    // Create payroll_periods table
    "CREATE TABLE IF NOT EXISTS payroll_periods (
        id INT PRIMARY KEY AUTO_INCREMENT,
        period_start DATE NOT NULL,
        period_end DATE NOT NULL,
        pay_date DATE NOT NULL,
        status ENUM('draft', 'processing', 'approved', 'paid') DEFAULT 'draft',
        created_by INT,
        approved_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (created_by) REFERENCES users(id),
        FOREIGN KEY (approved_by) REFERENCES users(id)
    )" => "Creating payroll periods table",

    // Create attendance table
    "CREATE TABLE IF NOT EXISTS attendance (
        id INT PRIMARY KEY AUTO_INCREMENT,
        employee_id INT NOT NULL,
        date DATE NOT NULL,
        time_in TIME,
        time_out TIME,
        status ENUM('present', 'absent', 'late', 'half_day', 'leave') DEFAULT 'present',
        notes TEXT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id)
    )" => "Creating attendance table",

    // Create leaves table
    "CREATE TABLE IF NOT EXISTS leaves (
        id INT PRIMARY KEY AUTO_INCREMENT,
        employee_id INT NOT NULL,
        leave_type ENUM('sick', 'vacation', 'emergency', 'others') NOT NULL,
        start_date DATE NOT NULL,
        end_date DATE NOT NULL,
        reason TEXT,
        status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending',
        approved_by INT,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id),
        FOREIGN KEY (approved_by) REFERENCES users(id)
    )" => "Creating leaves table",

    // Create payroll_items table
    "CREATE TABLE IF NOT EXISTS payroll_items (
        id INT PRIMARY KEY AUTO_INCREMENT,
        payroll_period_id INT NOT NULL,
        employee_id INT NOT NULL,
        basic_salary DECIMAL(10,2) NOT NULL,
        allowance DECIMAL(10,2) DEFAULT 0.00,
        overtime_hours DECIMAL(5,2) DEFAULT 0.00,
        overtime_amount DECIMAL(10,2) DEFAULT 0.00,
        late_hours DECIMAL(5,2) DEFAULT 0.00,
        late_deduction DECIMAL(10,2) DEFAULT 0.00,
        absences INT DEFAULT 0,
        absence_deduction DECIMAL(10,2) DEFAULT 0.00,
        gross_salary DECIMAL(10,2) DEFAULT 0.00,
        sss_contribution DECIMAL(10,2) DEFAULT 0.00,
        philhealth_contribution DECIMAL(10,2) DEFAULT 0.00,
        pagibig_contribution DECIMAL(10,2) DEFAULT 0.00,
        tax_contribution DECIMAL(10,2) DEFAULT 0.00,
        other_deductions DECIMAL(10,2) DEFAULT 0.00,
        net_salary DECIMAL(10,2) DEFAULT 0.00,
        status ENUM('draft', 'approved', 'paid') DEFAULT 'draft',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (payroll_period_id) REFERENCES payroll_periods(id),
        FOREIGN KEY (employee_id) REFERENCES employees(id)
    )" => "Creating payroll items table",

    // Create deductions table
    "CREATE TABLE IF NOT EXISTS deductions (
        id INT PRIMARY KEY AUTO_INCREMENT,
        employee_id INT NOT NULL,
        deduction_type VARCHAR(50) NOT NULL,
        amount DECIMAL(10,2) NOT NULL,
        start_date DATE,
        end_date DATE,
        recurring BOOLEAN DEFAULT 0,
        notes TEXT,
        status ENUM('active', 'inactive') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id)
    )" => "Creating deductions table",

    // Insert sample departments
    "INSERT IGNORE INTO employees (
        employee_code, first_name, last_name, email, phone, 
        position, department, hire_date, basic_salary, status
    ) VALUES 
    ('EMP001', 'John', 'Doe', 'john@example.com', '123-456-7890',
     'Network Engineer', 'Technical', '2024-01-01', 25000.00, 'active'),
    ('EMP002', 'Jane', 'Smith', 'jane@example.com', '098-765-4321',
     'Customer Service', 'Support', '2024-01-15', 20000.00, 'active'),
    ('EMP003', 'Mike', 'Johnson', 'mike@example.com', '555-123-4567',
     'Sales Executive', 'Sales', '2024-02-01', 22000.00, 'active')" => "Adding sample employees"
];

$success_count = 0;
$error_messages = [];

// Start transaction
$conn->begin_transaction();

try {
    foreach ($sql_queries as $sql => $description) {
        output($description . "...");
        if ($conn->query($sql)) {
            output("✓ Success!");
            $success_count++;
        } else {
            throw new Exception($conn->error);
        }
    }
    
    // If all queries successful, commit transaction
    $conn->commit();
    output("\nDatabase setup completed successfully! ($success_count queries executed)");
    
    if (!$is_cli) {
        echo "<p><a href='payroll.php' class='btn btn-primary'>Go to Payroll</a></p>";
    }
    
} catch (Exception $e) {
    // If error occurs, rollback changes
    $conn->rollback();
    output("Error setting up database: " . $e->getMessage());
}

$conn->close();
?>
