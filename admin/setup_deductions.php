<?php
require_once 'config.php';

try {
    // Create deduction types table
    $conn->query("CREATE TABLE IF NOT EXISTS deduction_types (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        type ENUM('government', 'loan', 'other') DEFAULT 'other',
        calculation_type ENUM('fixed', 'percentage') DEFAULT 'fixed',
        percentage_value DECIMAL(5,2) DEFAULT 0,
        is_active BOOLEAN DEFAULT true,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )");

    // Create employee deductions table
    $conn->query("CREATE TABLE IF NOT EXISTS employee_deductions (
        id INT AUTO_INCREMENT PRIMARY KEY,
        employee_id INT NOT NULL,
        deduction_type_id INT NOT NULL,
        amount DECIMAL(10,2) DEFAULT 0,
        frequency ENUM('onetime', 'monthly', 'bimonthly', 'quarterly', 'annual') DEFAULT 'monthly',
        start_date DATE NOT NULL,
        end_date DATE,
        remarks TEXT,
        status ENUM('active', 'completed', 'cancelled') DEFAULT 'active',
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (employee_id) REFERENCES employees(id),
        FOREIGN KEY (deduction_type_id) REFERENCES deduction_types(id)
    )");

    // Insert default government deductions
    $default_deductions = [
        ['SSS Contribution', 'Social Security System contribution', 'government', 'percentage', 3.63],
        ['PhilHealth', 'PhilHealth contribution', 'government', 'percentage', 3.50],
        ['Pag-IBIG', 'Pag-IBIG contribution', 'government', 'percentage', 2.00],
        ['Withholding Tax', 'Income tax deduction', 'government', 'percentage', 0.00]
    ];

    foreach ($default_deductions as $deduction) {
        $conn->query("INSERT INTO deduction_types (name, description, type, calculation_type, percentage_value) 
                     VALUES ('$deduction[0]', '$deduction[1]', '$deduction[2]', '$deduction[3]', $deduction[4])
                     ON DUPLICATE KEY UPDATE name = name");
    }

    echo "Deduction tables and default entries created successfully!";

} catch (Exception $e) {
    die("Setup error: " . $e->getMessage());
}
?>
