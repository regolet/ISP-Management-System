<?php
require_once '../config.php';
check_auth();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header("Location: employees.php");
    exit();
}

try {
    // Start transaction
    $conn->begin_transaction();

    // Common fields - sanitize only when displaying, not when saving to database
    $first_name = trim($_POST['first_name']);
    $last_name = trim($_POST['last_name']);
    $email = filter_input(INPUT_POST, 'email', FILTER_SANITIZE_EMAIL);
    $phone = trim($_POST['phone']);
    $address = trim($_POST['address']);
    $position = trim($_POST['position']);
    $department = trim($_POST['department']);
    
    // Validate and format hire date
    $hire_date = date('Y-m-d', strtotime($_POST['hire_date']));
    if (!$hire_date || $hire_date === '1970-01-01') {
        throw new Exception("Invalid hire date format");
    }

    $basic_salary = filter_input(INPUT_POST, 'basic_salary', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION);
    $allowance = filter_input(INPUT_POST, 'allowance', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?? 0;
    $daily_rate = filter_input(INPUT_POST, 'daily_rate', FILTER_SANITIZE_NUMBER_FLOAT, FILTER_FLAG_ALLOW_FRACTION) ?? 0;
    
    // Optional fields
    $sss_no = trim($_POST['sss_no'] ?? '');
    $philhealth_no = trim($_POST['philhealth_no'] ?? '');
    $pagibig_no = trim($_POST['pagibig_no'] ?? '');
    $tin_no = trim($_POST['tin_no'] ?? '');
    $bank_name = trim($_POST['bank_name'] ?? '');
    $bank_account_no = trim($_POST['bank_account_no'] ?? '');

    // Validate required fields
    if (!$first_name || !$last_name || !$position || !$department || !$hire_date || !$basic_salary) {
        throw new Exception("Please fill in all required fields");
    }

    // Generate employee code
    $year = date('Y');
    $stmt = $conn->prepare("
        SELECT COUNT(*) as count 
        FROM employees 
        WHERE employee_code LIKE ?
    ");
    $code_prefix = "EMP$year-";
    $like_pattern = "$code_prefix%";
    $stmt->bind_param("s", $like_pattern);
    $stmt->execute();
    $result = $stmt->get_result()->fetch_assoc();
    $count = $result['count'] + 1;
    $employee_code = $code_prefix . str_pad($count, 4, '0', STR_PAD_LEFT);

    // Create user account
    $username = strtolower($first_name[0] . $last_name); // First letter of first name + last name
    $password = password_hash($employee_code, PASSWORD_DEFAULT); // Use employee code as initial password
    
    // Check if username exists
    $stmt = $conn->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->bind_param("s", $username);
    $stmt->execute();
    if ($stmt->get_result()->num_rows > 0) {
        // Append number if username exists
        $i = 1;
        $new_username = $username . $i;
        while ($stmt->get_result()->num_rows > 0) {
            $new_username = $username . ++$i;
            $stmt->bind_param("s", $new_username);
            $stmt->execute();
        }
        $username = $new_username;
    }

    // Insert user
    $stmt = $conn->prepare("
        INSERT INTO users (username, password, email, role, status) 
        VALUES (?, ?, ?, 'staff', 'active')
    ");
    $stmt->bind_param("sss", $username, $password, $email);
    if (!$stmt->execute()) {
        throw new Exception("Error creating user account: " . $conn->error);
    }
    $user_id = $conn->insert_id;

    // Insert employee with user_id
    $stmt = $conn->prepare("
        INSERT INTO employees (
            employee_code, user_id, first_name, last_name, email, phone,
            address, position, department, hire_date,
            basic_salary, allowance, daily_rate,
            sss_no, philhealth_no, pagibig_no, tin_no,
            bank_name, bank_account_no, status
        ) VALUES (
            ?, ?, ?, ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, ?,
            ?, ?, ?, ?,
            ?, ?, 'active'
        )
    ");

    // Changed binding types to match the correct data types
    // s = string, i = integer, d = double/float
    if (!$stmt->bind_param("sissssssssdddssssss",
        $employee_code, $user_id, $first_name, $last_name, $email, $phone,
        $address, $position, $department, $hire_date,
        $basic_salary, $allowance, $daily_rate,
        $sss_no, $philhealth_no, $pagibig_no, $tin_no,
        $bank_name, $bank_account_no
    )) {
        throw new Exception("Error binding parameters: " . $stmt->error);
    }

    if (!$stmt->execute()) {
        throw new Exception("Error creating employee: " . $conn->error);
    }

    $employee_id = $conn->insert_id;

    // Log activity
    log_activity($_SESSION['user_id'], 'create_employee', "Created new employee: $first_name $last_name ($employee_code)");
    
    // Set success message with login credentials
    $_SESSION['success'] = "Employee created successfully.<br>Login Credentials:<br>Username: $username<br>Password: $employee_code";

    $conn->commit();
    header("Location: employee_view.php?id=" . $employee_id . "&edit=true");
    exit();

} catch (Exception $e) {
    $conn->rollback();
    $_SESSION['error'] = $e->getMessage();
    header("Location: employee_add.php");
    exit();
} 