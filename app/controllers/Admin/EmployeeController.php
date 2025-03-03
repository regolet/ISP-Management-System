<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Admin\Employee;

class EmployeeController extends Controller {
    private $employeeModel;

    public function __construct() {
        parent::__construct();
        $this->employeeModel = new Employee();
    }

    /**
     * Display employees list
     */
    public function index() {
        $page = $_GET['page'] ?? 1;
        $filters = [
            'search' => $_GET['search'] ?? null,
            'department' => $_GET['department'] ?? null,
            'status' => $_GET['status'] ?? null
        ];

        $result = $this->employeeModel->getEmployees($filters, $page);

        return $this->view('admin/employees/index', [
            'employees' => $result['employees'],
            'totalPages' => $result['pages'],
            'page' => $page,
            'filters' => $filters,
            'departments' => Employee::getDepartments(),
            'layout' => 'navbar',
            'title' => 'Employee Management'
        ]);
    }

    /**
     * Show employee creation form
     */
    public function create() {
        return $this->view('admin/employees/create', [
            'departments' => Employee::getDepartments(),
            'layout' => 'navbar',
            'title' => 'Add Employee'
        ]);
    }

    /**
     * Store new employee
     */
    public function store() {
        $data = $this->getRequestData();
        
        // Validate input
        $errors = $this->employeeModel->validate($data);
        if (!empty($errors)) {
            return $this->view('admin/employees/create', [
                'errors' => $errors,
                'data' => $data,
                'departments' => Employee::getDepartments(),
                'layout' => 'navbar',
                'title' => 'Add Employee'
            ]);
        }

        try {
            $employeeId = $this->employeeModel->createEmployee($data);
            $this->setFlash('success', 'Employee created successfully');
            return $this->redirect("/admin/employees/view/{$employeeId}");

        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to create employee: ' . $e->getMessage());
            return $this->view('admin/employees/create', [
                'errors' => ['general' => $e->getMessage()],
                'data' => $data,
                'departments' => Employee::getDepartments(),
                'layout' => 'navbar',
                'title' => 'Add Employee'
            ]);
        }
    }

    /**
     * Show employee details
     */
    public function show($id) {
        $employee = $this->employeeModel->getEmployeeDetails($id);
        if (!$employee) {
            $this->setFlash('error', 'Employee not found');
            return $this->redirect('/admin/employees');
        }

        return $this->view('admin/employees/show', [
            'employee' => $employee,
            'layout' => 'navbar',
            'title' => 'Employee Details'
        ]);
    }

    /**
     * Show employee edit form
     */
    public function edit($id) {
        $employee = $this->employeeModel->find($id);
        if (!$employee) {
            $this->setFlash('error', 'Employee not found');
            return $this->redirect('/admin/employees');
        }

        return $this->view('admin/employees/edit', [
            'employee' => $employee,
            'departments' => Employee::getDepartments(),
            'layout' => 'navbar',
            'title' => 'Edit Employee'
        ]);
    }

    /**
     * Update employee
     */
    public function update($id) {
        $employee = $this->employeeModel->find($id);
        if (!$employee) {
            $this->setFlash('error', 'Employee not found');
            return $this->redirect('/admin/employees');
        }

        $data = $this->getRequestData();
        $data['id'] = $id;

        // Validate input
        $errors = $this->employeeModel->validate($data);
        if (!empty($errors)) {
            return $this->view('admin/employees/edit', [
                'errors' => $errors,
                'data' => $data,
                'employee' => $employee,
                'departments' => Employee::getDepartments(),
                'layout' => 'navbar',
                'title' => 'Edit Employee'
            ]);
        }

        try {
            $this->employeeModel->update($id, $data);
            $this->setFlash('success', 'Employee updated successfully');
            return $this->redirect("/admin/employees/view/{$id}");

        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to update employee: ' . $e->getMessage());
            return $this->view('admin/employees/edit', [
                'errors' => ['general' => $e->getMessage()],
                'data' => $data,
                'employee' => $employee,
                'departments' => Employee::getDepartments(),
                'layout' => 'navbar',
                'title' => 'Edit Employee'
            ]);
        }
    }

    /**
     * Update employee status
     */
    public function updateStatus($id) {
        $employee = $this->employeeModel->find($id);
        if (!$employee) {
            return $this->json(['error' => 'Employee not found'], 404);
        }

        $status = $_POST['status'] ?? null;
        if (!$status) {
            return $this->json(['error' => 'Status is required'], 400);
        }

        try {
            $this->employeeModel->updateStatus($id, $status);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Create user account for employee
     */
    public function createUserAccount($id) {
        $employee = $this->employeeModel->find($id);
        if (!$employee) {
            return $this->json(['error' => 'Employee not found'], 404);
        }

        if ($employee['user_id']) {
            return $this->json(['error' => 'Employee already has a user account'], 400);
        }

        try {
            // Generate username from email or employee code
            $username = $employee['email'] ? 
                       explode('@', $employee['email'])[0] : 
                       strtolower($employee['employee_code']);

            // Generate random password
            $password = bin2hex(random_bytes(8));

            // Create user account
            $userId = $this->createUser([
                'username' => $username,
                'password' => password_hash($password, PASSWORD_DEFAULT),
                'email' => $employee['email'],
                'role' => 'employee'
            ]);

            // Update employee with user_id
            $this->employeeModel->update($id, ['user_id' => $userId]);

            // Send credentials via email if email exists
            if ($employee['email']) {
                $this->sendCredentials($employee['email'], $username, $password);
            }

            return $this->json([
                'success' => true,
                'username' => $username,
                'password' => $password
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get request data
     */
    private function getRequestData() {
        return [
            'first_name' => $_POST['first_name'] ?? null,
            'last_name' => $_POST['last_name'] ?? null,
            'position' => $_POST['position'] ?? null,
            'department' => $_POST['department'] ?? null,
            'daily_rate' => $_POST['daily_rate'] ?? null,
            'hire_date' => $_POST['hire_date'] ?? null,
            'email' => $_POST['email'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'address' => $_POST['address'] ?? null,
            'sss_number' => $_POST['sss_number'] ?? null,
            'philhealth_number' => $_POST['philhealth_number'] ?? null,
            'pagibig_number' => $_POST['pagibig_number'] ?? null,
            'tin_number' => $_POST['tin_number'] ?? null,
            'emergency_contact_name' => $_POST['emergency_contact_name'] ?? null,
            'emergency_contact_phone' => $_POST['emergency_contact_phone'] ?? null
        ];
    }

    /**
     * Create user account
     */
    private function createUser($data) {
        // Implementation depends on your User model
        // This is just a placeholder
        return 1;
    }

    /**
     * Send credentials via email
     */
    private function sendCredentials($email, $username, $password) {
        // Email configuration and sending logic here
        // This is just a placeholder
        $to = $email;
        $subject = 'Your Employee Account Credentials';
        $message = "Hello,\n\n"
                . "Your employee account has been created. Here are your login credentials:\n\n"
                . "Username: {$username}\n"
                . "Password: {$password}\n\n"
                . "Please change your password after first login.\n\n"
                . "Best regards,\n"
                . "HR Department";
        
        mail($to, $subject, $message);
    }
}
