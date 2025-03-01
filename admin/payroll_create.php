<?php
require_once 'config.php';
check_login();

// Check for required tables and columns
$tables_to_check = [
    'payroll_items' => "ALTER TABLE payroll_items 
                        ADD COLUMN IF NOT EXISTS deductions DECIMAL(10,2) DEFAULT 0.00 AFTER basic_salary,
                        ADD COLUMN IF NOT EXISTS deduction_details TEXT AFTER deductions",
    'deduction_transactions' => "CREATE TABLE IF NOT EXISTS deduction_transactions (
                                id INT AUTO_INCREMENT PRIMARY KEY,
                                deduction_id INT NOT NULL,
                                payroll_item_id INT NOT NULL,
                                amount DECIMAL(10,2) NOT NULL,
                                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                                FOREIGN KEY (deduction_id) REFERENCES employee_deductions(id),
                                FOREIGN KEY (payroll_item_id) REFERENCES payroll_items(id)
                            )",
    'positions' => "CREATE TABLE IF NOT EXISTS positions (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    name VARCHAR(100) NOT NULL,
                    amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
                    allowance DECIMAL(10,2) DEFAULT 0.00,
                    status ENUM('active','inactive') DEFAULT 'active'
                )"
];

foreach ($tables_to_check as $table => $query) {
    $table_check = $conn->query("SHOW TABLES LIKE '$table'");
    if ($table_check->num_rows == 0) {
        if (!$conn->multi_query($query)) {
            die("Error creating/updating $table: " . $conn->error);
        }
        while ($conn->more_results()) {
            $conn->next_result();
        }
    }
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    // Display form
    $emp_query = "SELECT e.*, 
                 COALESCE(p.amount, e.basic_salary) as basic_salary,
                 COALESCE(p.allowance, 0) as allowance,
                 p.name as position_name
                 FROM employees e 
                 LEFT JOIN positions p ON e.position_id = p.id 
                 WHERE e.status = 'active'";
    $employees = $conn->query($emp_query);
    
    include 'header.php';
    // ... rest of your display code ...
} else {
    // Process payroll creation
    try {
        $conn->begin_transaction();

        // Insert payroll period
        $period_stmt = $conn->prepare("INSERT INTO payroll_periods (period_start, period_end, pay_date, status) 
                                     VALUES (?, ?, ?, 'draft')");
        $period_stmt->bind_param("sss", $_POST['period_start'], $_POST['period_end'], $_POST['pay_date']);
        $period_stmt->execute();
        $period_id = $conn->insert_id;

        // Get employees with their basic salary from their position or default salary
        $emp_query = "SELECT e.*, 
                     COALESCE(p.amount, e.basic_salary) as basic_salary,
                     COALESCE(p.allowance, 0) as allowance
                     FROM employees e 
                     LEFT JOIN positions p ON e.position_id = p.id 
                     WHERE e.status = 'active'";
        $employees = $conn->query($emp_query);

        // Get active deductions for employee
        $deductions_query = "SELECT ed.*, dt.name as deduction_name, dt.calculation_type, dt.percentage_value
                            FROM employee_deductions ed
                            JOIN deduction_types dt ON ed.deduction_type_id = dt.id
                            WHERE ed.employee_id = ? 
                            AND ed.status = 'active' 
                            AND (ed.end_date IS NULL OR ed.end_date >= ?)
                            AND ed.start_date <= ?";
        $deduction_stmt = $conn->prepare($deductions_query);

        // Process each employee
        while ($employee = $employees->fetch_assoc()) {            
            // Calculate attendance-based deductions
            $attendance_query = "SELECT 
                COUNT(CASE WHEN status = 'absent' THEN 1 END) as absences,
                COUNT(CASE WHEN status = 'late' THEN 1 END) as lates,
                COUNT(CASE WHEN status = 'half_day' THEN 1 END) as half_days
                FROM attendance 
                WHERE employee_id = ? AND date BETWEEN ? AND ?";
            
            $att_stmt = $conn->prepare($attendance_query);
            $att_stmt->bind_param("iss", $employee['id'], $_POST['period_start'], $_POST['period_end']);
            $att_stmt->execute();
            $attendance = $att_stmt->get_result()->fetch_assoc();

            $basic_salary = (float)$employee['basic_salary'];
            $daily_rate = $basic_salary / 22; // Assuming 22 working days
            
            // Calculate attendance deductions
            $attendance_deduction = ($attendance['absences'] * $daily_rate) + 
                                  ($attendance['lates'] * ($daily_rate * 0.1)) + 
                                  ($attendance['half_days'] * ($daily_rate * 0.5));

            // Process deductions BEFORE saving payroll item
            $deduction_stmt->bind_param("iss", $employee['id'], $_POST['period_end'], $_POST['period_end']);
            $deduction_stmt->execute();
            $deductions = $deduction_stmt->get_result();
            
            $total_deductions = $attendance_deduction;
            $deduction_details = [
                [
                    'name' => 'Attendance Deductions',
                    'amount' => $attendance_deduction
                ]
            ];

            // Process all deductions
            while ($deduction = $deductions->fetch_assoc()) {
                $deduction_amount = 0;
                
                if ($deduction['calculation_type'] === 'percentage') {
                    $deduction_amount = $basic_salary * ($deduction['percentage_value'] / 100);
                } else {
                    $deduction_amount = $deduction['amount'];
                }

                // Adjust for frequency
                switch ($deduction['frequency']) {
                    case 'bimonthly':
                        $deduction_amount /= 2;
                        break;
                    case 'quarterly':
                        $deduction_amount /= 13;
                        break;
                    case 'annual':
                        $deduction_amount /= 52;
                        break;
                }
                
                $total_deductions += $deduction_amount;
                $deduction_details[] = [
                    'name' => $deduction['deduction_name'],
                    'amount' => $deduction_amount
                ];
            }

            // Calculate net salary AFTER all deductions
            $net_salary = $basic_salary - $total_deductions;

            // Now save the payroll record with correct net salary
            $save_query = "INSERT INTO payroll_items 
                          (payroll_period_id, employee_id, basic_salary, deductions, net_salary, deduction_details) 
                          VALUES (?, ?, ?, ?, ?, ?)";
            $stmt = $conn->prepare($save_query);
            $deduction_json = json_encode($deduction_details);
            $stmt->bind_param("iiddds", 
                $period_id, 
                $employee['id'], 
                $basic_salary, 
                $total_deductions, 
                $net_salary, 
                $deduction_json
            );
            $stmt->execute();
            $payroll_item_id = $conn->insert_id;

            // Record deduction transactions
            foreach ($deduction_details as $deduction) {
                if (isset($deduction['id'])) {
                    $trans_stmt = $conn->prepare("INSERT INTO deduction_transactions 
                        (deduction_id, payroll_item_id, amount) VALUES (?, ?, ?)");
                    $trans_stmt->bind_param("iid", $deduction['id'], $payroll_item_id, $deduction['amount']);
                    $trans_stmt->execute();
                }
            }
        }

        $conn->commit();
        $_SESSION['success'] = "Payroll period created successfully";
        
    } catch (Exception $e) {
        $conn->rollback();
        $_SESSION['error'] = "Error creating payroll: " . $e->getMessage();
    }

    header('Location: payroll.php');
    exit;
}
?>

<div class="container-fluid">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <h4 class="card-title mb-4">Create New Payroll Period</h4>
                    
                    <?php include 'alerts.php'; ?>
                    
                    <form id="payrollForm" method="POST" action="">
                        <div class="row mb-3">
                            <div class="col-md-4">
                                <label for="period_start" class="form-label">Period Start</label>
                                <input type="date" class="form-control" id="period_start" 
                                       name="period_start" value="<?php echo date('Y-m-01'); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="period_end" class="form-label">Period End</label>
                                <input type="date" class="form-control" id="period_end" 
                                       name="period_end" value="<?php echo date('Y-m-t'); ?>" required>
                            </div>
                            <div class="col-md-4">
                                <label for="pay_date" class="form-label">Payment Date</label>
                                <input type="date" class="form-control" id="pay_date" 
                                       name="pay_date" value="<?php echo date('Y-m-d', strtotime('+3 days')); ?>" required>
                            </div>
                        </div>

                        <div class="table-responsive mb-4">
                            <table class="table table-bordered">
                                <thead class="table-light">
                                    <tr>
                                        <th>Employee Code</th>
                                        <th>Name</th>
                                        <th>Position</th>
                                        <th>Department</th>
                                        <th class="text-end">Basic Salary</th>
                                        <th class="text-end">Allowance</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php while ($employee = $employees->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($employee['employee_code']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['full_name']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['position']); ?></td>
                                        <td><?php echo htmlspecialchars($employee['department']); ?></td>
                                        <td class="text-end">₱<?php echo number_format($employee['basic_salary'], 2); ?></td>
                                        <td class="text-end">₱<?php echo number_format($employee['allowance'], 2); ?></td>
                                    </tr>
                                    <?php endwhile; ?>
                                </tbody>
                            </table>
                        </div>

                        <div class="alert alert-info">
                            <i class='bx bx-info-circle'></i>
                            This will create a new payroll period and automatically add all active employees with their basic salaries and allowances.
                            You can adjust individual employee details after creation.
                        </div>

                        <div class="text-end mt-4">
                            <button type="button" class="btn btn-secondary me-2" onclick="window.location.href='payroll.php'">
                                Cancel
                            </button>
                            <button type="submit" class="btn btn-primary">
                                <i class="bx bx-save"></i> Create Payroll Period
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Form validation
    $('#payrollForm').on('submit', function(e) {
        var start = new Date($('#period_start').val());
        var end = new Date($('#period_end').val());
        var pay = new Date($('#pay_date').val());
        
        if (start > end) {
            e.preventDefault();
            alert('Period start date cannot be later than end date.');
            return false;
        }
        
        if (pay < end) {
            e.preventDefault();
            alert('Payment date should be after the period end date.');
            return false;
        }
    });
});
</script>

<?php include 'footer.php'; ?>
