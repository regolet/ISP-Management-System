<?php
require_once 'config.php';
check_auth('admin');

// Function to get current daily rate
function getCurrentDailyRate($employee_id, $payroll_date) {
    global $conn;
    
    // Get days in the payroll month
    $days_in_month = date('t', strtotime($payroll_date));
    
    // Get current basic salary
    $query = "SELECT basic_salary FROM employees WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    // Calculate daily rate
    return $result['basic_salary'] / $days_in_month;
}

// Process payroll
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    try {
        $conn->begin_transaction();

        $employee_id = filter_input(INPUT_POST, 'employee_id', FILTER_SANITIZE_NUMBER_INT);
        $payroll_period_start = $_POST['period_start'];
        $payroll_period_end = $_POST['period_end'];
        $days_worked = filter_input(INPUT_POST, 'days_worked', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
        
        // Validate inputs
        if (!$employee_id || !$payroll_period_start || !$payroll_period_end || !isset($days_worked)) {
            throw new Exception("Missing required fields");
        }

        // Get employee details
        $emp_query = "SELECT * FROM employees WHERE id = ? AND status = 'active'";
        $emp_stmt = $conn->prepare($emp_query);
        $emp_stmt->bind_param("i", $employee_id);
        $emp_stmt->execute();
        $employee = $emp_stmt->get_result()->fetch_assoc();

        if (!$employee) {
            throw new Exception("Employee not found or inactive");
        }

        // Calculate daily rate based on current month
        $daily_rate = getCurrentDailyRate($employee_id, $payroll_period_start);
        
        // Calculate gross pay
        $gross_pay = $daily_rate * $days_worked;

        // Get deductions
        $deductions_query = "
            SELECT dt.name, ed.amount, ed.type 
            FROM employee_deductions ed
            JOIN deduction_types dt ON ed.deduction_type_id = dt.id
            WHERE ed.employee_id = ? AND ed.status = 'active'
        ";
        $deductions_stmt = $conn->prepare($deductions_query);
        $deductions_stmt->bind_param("i", $employee_id);
        $deductions_stmt->execute();
        $deductions_result = $deductions_stmt->get_result();

        $total_deductions = 0;
        $deductions = [];

        while ($deduction = $deductions_result->fetch_assoc()) {
            $amount = $deduction['type'] === 'percentage' 
                ? ($gross_pay * ($deduction['amount'] / 100)) 
                : $deduction['amount'];
            
            $deductions[] = [
                'name' => $deduction['name'],
                'amount' => $amount
            ];
            
            $total_deductions += $amount;
        }

        // Calculate net pay
        $net_pay = $gross_pay - $total_deductions;

        // Insert payroll record
        $payroll_query = "
            INSERT INTO payroll (
                employee_id, period_start, period_end, days_worked, 
                daily_rate, gross_pay, total_deductions, net_pay,
                processed_by, processed_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ";
        
        $payroll_stmt = $conn->prepare($payroll_query);
        $payroll_stmt->bind_param(
            "issdddddi",
            $employee_id,
            $payroll_period_start,
            $payroll_period_end,
            $days_worked,
            $daily_rate,
            $gross_pay,
            $total_deductions,
            $net_pay,
            $_SESSION['user_id']
        );
        
        if (!$payroll_stmt->execute()) {
            throw new Exception("Error saving payroll record");
        }
        
        $payroll_id = $conn->insert_id;

        // Save deduction details
        if (!empty($deductions)) {
            $deduction_detail_query = "
                INSERT INTO payroll_deductions (
                    payroll_id, deduction_name, amount
                ) VALUES (?, ?, ?)
            ";
            
            $detail_stmt = $conn->prepare($deduction_detail_query);
            
            foreach ($deductions as $deduction) {
                $detail_stmt->bind_param(
                    "isd",
                    $payroll_id,
                    $deduction['name'],
                    $deduction['amount']
                );
                $detail_stmt->execute();
            }
        }

        // Log the activity
        log_activity(
            $_SESSION['user_id'], 
            'process_payroll', 
            "Processed payroll for {$employee['first_name']} {$employee['last_name']} - Period: $payroll_period_start to $payroll_period_end"
        );

        $conn->commit();
        $_SESSION['success'] = "Payroll processed successfully";
        
        // Return JSON response for AJAX requests
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {
            echo json_encode([
                'success' => true,
                'payroll_id' => $payroll_id,
                'message' => 'Payroll processed successfully'
            ]);
            exit;
        }

        header("Location: payroll_view.php?id=" . $payroll_id);
        exit;

    } catch (Exception $e) {
        $conn->rollback();
        
        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && 
            strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest'
        ) {
            http_response_code(400);
            echo json_encode([
                'success' => false,
                'message' => $e->getMessage()
            ]);
            exit;
        }

        $_SESSION['error'] = "Error: " . $e->getMessage();
        header("Location: payroll.php");
        exit;
    }
}
?>