<?php
require_once 'config.php';

function getCurrentDailyRate($employee_id, $date) {
    global $conn;
    
    // Get days in the payroll month
    $days_in_month = date('t', strtotime($date));
    
    // Get current basic salary
    $query = "SELECT basic_salary FROM employees WHERE id = ?";
    $stmt = $conn->prepare($query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    
    // Calculate daily rate
    return $result['basic_salary'] / $days_in_month;
}

function calculatePayrollForEmployee($employee_id, $period_start, $period_end) {
    global $conn;
    
    // Get employee base info
    $emp_query = "SELECT basic_salary, allowance FROM employees WHERE id = ?";
    $stmt = $conn->prepare($emp_query);
    $stmt->bind_param("i", $employee_id);
    $stmt->execute();
    $employee = $stmt->get_result()->fetch_assoc();

    // Calculate daily rate based on the period start date
    $daily_rate = getCurrentDailyRate($employee_id, $period_start);

    // Get attendance records for the period
    $att_query = "SELECT 
        COUNT(CASE WHEN status = 'present' THEN 1 END) as present_days,
        COUNT(CASE WHEN status = 'absent' THEN 1 END) as absent_days,
        COUNT(CASE WHEN status = 'late' THEN 1 END) as late_days,
        COUNT(CASE WHEN status = 'half_day' THEN 1 END) as half_days
        FROM attendance 
        WHERE employee_id = ? AND date BETWEEN ? AND ?";
    
    $stmt = $conn->prepare($att_query);
    $stmt->bind_param("iss", $employee_id, $period_start, $period_end);
    $stmt->execute();
    $attendance = $stmt->get_result()->fetch_assoc();

    // Calculate basic pay based on attendance
    $working_days = $attendance['present_days'] + ($attendance['half_days'] * 0.5);
    $basic_pay = $daily_rate * $working_days;

    // Calculate deductions
    $late_deduction = $attendance['late_days'] * ($daily_rate * 0.1); // 10% per late
    $absence_deduction = $attendance['absent_days'] * $daily_rate;

    // Calculate gross salary
    $gross_salary = $basic_pay + $employee['allowance'];

    // Calculate government contributions
    $sss = calculateSSS($gross_salary);
    $philhealth = calculatePhilHealth($gross_salary);
    $pagibig = calculatePagibig($gross_salary);
    
    // Calculate tax after contributions
    $taxable_income = $gross_salary - ($sss + $philhealth + $pagibig);
    $tax = calculateTax($taxable_income);

    // Calculate total deductions
    $total_deductions = $sss + $philhealth + $pagibig + $tax + $late_deduction + $absence_deduction;

    // Calculate net salary
    $net_salary = $gross_salary - $total_deductions;

    // Get the days in month for the period
    $days_in_month = date('t', strtotime($period_start));

    return [
        'basic_salary' => $basic_pay,
        'allowance' => $employee['allowance'],
        'gross_salary' => $gross_salary,
        'present_days' => $attendance['present_days'],
        'absent_days' => $attendance['absent_days'],
        'late_days' => $attendance['late_days'],
        'half_days' => $attendance['half_days'],
        'late_deduction' => $late_deduction,
        'absence_deduction' => $absence_deduction,
        'sss_contribution' => $sss,
        'philhealth_contribution' => $philhealth,
        'pagibig_contribution' => $pagibig,
        'tax_contribution' => $tax,
        'total_deductions' => $total_deductions,
        'net_salary' => $net_salary,
        'daily_rate' => $daily_rate,
        'days_in_month' => $days_in_month,
        'monthly_basic_salary' => $employee['basic_salary']
    ];
}

function calculateSSS($salary) {
    if ($salary <= 3250) return 157.50;
    else if ($salary <= 3750) return 180.00;
    // ...add more brackets as needed
    return 1350.00; // maximum contribution
}

function calculatePhilHealth($salary) {
    return $salary * 0.035; // 3.5% of salary
}

function calculatePagibig($salary) {
    return min($salary * 0.02, 100); // 2% of salary, max of 100
}

function calculateTax($taxable_income) {
    $monthly_income = $taxable_income;
    
    if ($monthly_income <= 20833) {
        return 0;
    } elseif ($monthly_income <= 33333) {
        return ($monthly_income - 20833) * 0.20;
    } elseif ($monthly_income <= 66667) {
        return 2500 + ($monthly_income - 33333) * 0.25;
    } elseif ($monthly_income <= 166667) {
        return 10833 + ($monthly_income - 66667) * 0.30;
    } elseif ($monthly_income <= 666667) {
        return 40833.33 + ($monthly_income - 166667) * 0.32;
    } else {
        return 200833.33 + ($monthly_income - 666667) * 0.35;
    }
}

// Function to record salary history if needed
function recordSalaryHistory($employee_id, $basic_salary, $daily_rate, $effective_date) {
    global $conn;
    
    $days_in_month = date('t', strtotime($effective_date));
    
    $query = "
        INSERT INTO salary_history (
            employee_id, basic_salary, daily_rate, 
            effective_date, days_in_month, created_by
        ) VALUES (?, ?, ?, ?, ?, ?)
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param(
        "iddsis",
        $employee_id,
        $basic_salary,
        $daily_rate,
        $effective_date,
        $days_in_month,
        $_SESSION['user_id']
    );
    
    return $stmt->execute();
}
?>