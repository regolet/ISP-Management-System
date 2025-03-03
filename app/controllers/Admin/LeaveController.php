<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Admin\Leave;
use App\Models\Admin\Employee;

class LeaveController extends Controller {
    private $leaveModel;
    private $employeeModel;

    public function __construct() {
        parent::__construct();
        $this->leaveModel = new Leave();
        $this->employeeModel = new Employee();
    }

    /**
     * Display leave applications
     */
    public function index() {
        $page = $_GET['page'] ?? 1;
        $filters = [
            'employee_id' => $_GET['employee_id'] ?? null,
            'status' => $_GET['status'] ?? null,
            'department' => $_GET['department'] ?? null,
            'date_range' => $_GET['date_range'] ?? null
        ];

        $result = $this->leaveModel->getLeaveApplications($filters, $page);
        $employees = $this->employeeModel->getActiveEmployees(true);

        return $this->view('admin/leaves/index', [
            'applications' => $result['applications'],
            'totalPages' => $result['pages'],
            'page' => $page,
            'filters' => $filters,
            'employees' => $employees,
            'departments' => Employee::getDepartments(),
            'leaveTypes' => Leave::getLeaveTypes(),
            'layout' => 'navbar',
            'title' => 'Leave Applications'
        ]);
    }

    /**
     * Show leave application form
     */
    public function create() {
        $employees = $this->employeeModel->getActiveEmployees(true);
        
        return $this->view('admin/leaves/create', [
            'employees' => $employees,
            'leaveTypes' => Leave::getLeaveTypes(),
            'layout' => 'navbar',
            'title' => 'Create Leave Application'
        ]);
    }

    /**
     * Store leave application
     */
    public function store() {
        $data = $this->getRequestData();
        
        // Validate input
        $errors = $this->leaveModel->validate($data);
        if (!empty($errors)) {
            $employees = $this->employeeModel->getActiveEmployees(true);
            return $this->view('admin/leaves/create', [
                'errors' => $errors,
                'data' => $data,
                'employees' => $employees,
                'leaveTypes' => Leave::getLeaveTypes(),
                'layout' => 'navbar',
                'title' => 'Create Leave Application'
            ]);
        }

        try {
            $this->leaveModel->applyLeave($data);
            $this->setFlash('success', 'Leave application submitted successfully');
            return $this->redirect('/admin/leaves');

        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to submit leave application: ' . $e->getMessage());
            $employees = $this->employeeModel->getActiveEmployees(true);
            return $this->view('admin/leaves/create', [
                'errors' => ['general' => $e->getMessage()],
                'data' => $data,
                'employees' => $employees,
                'leaveTypes' => Leave::getLeaveTypes(),
                'layout' => 'navbar',
                'title' => 'Create Leave Application'
            ]);
        }
    }

    /**
     * Show leave application details
     */
    public function show($id) {
        $application = $this->leaveModel->getLeaveDetails($id);
        if (!$application) {
            $this->setFlash('error', 'Leave application not found');
            return $this->redirect('/admin/leaves');
        }

        return $this->view('admin/leaves/show', [
            'application' => $application,
            'leaveTypes' => Leave::getLeaveTypes(),
            'layout' => 'navbar',
            'title' => 'Leave Application Details'
        ]);
    }

    /**
     * Update leave application status
     */
    public function updateStatus($id) {
        $application = $this->leaveModel->find($id);
        if (!$application) {
            return $this->json(['error' => 'Leave application not found'], 404);
        }

        if ($application['status'] !== 'pending') {
            return $this->json(['error' => 'Can only update pending applications'], 400);
        }

        $status = $_POST['status'] ?? null;
        if (!in_array($status, ['approved', 'rejected'])) {
            return $this->json(['error' => 'Invalid status'], 400);
        }

        try {
            $this->leaveModel->updateStatus($id, $status, $_SESSION['user_id']);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Show leave balances
     */
    public function balances() {
        $employees = $this->employeeModel->getActiveEmployees(true);
        $department = $_GET['department'] ?? null;

        $sql = "SELECT * FROM leave_balances WHERE employee_id IN (?)";
        $employeeIds = implode(',', array_column($employees, 'id'));
        $balances = $this->db->query(str_replace('?', $employeeIds, $sql))->fetch_all(MYSQLI_ASSOC);

        // Index balances by employee_id
        $balancesByEmployee = [];
        foreach ($balances as $balance) {
            $balancesByEmployee[$balance['employee_id']] = $balance;
        }

        return $this->view('admin/leaves/balances', [
            'employees' => $employees,
            'balances' => $balancesByEmployee,
            'departments' => Employee::getDepartments(),
            'department' => $department,
            'leaveTypes' => Leave::getLeaveTypes(),
            'layout' => 'navbar',
            'title' => 'Leave Balances'
        ]);
    }

    /**
     * Update leave balance
     */
    public function updateBalance() {
        $employeeId = $_POST['employee_id'] ?? null;
        $leaveType = $_POST['leave_type'] ?? null;
        $balance = $_POST['balance'] ?? null;

        if (!$employeeId || !$leaveType || !isset($balance)) {
            return $this->json(['error' => 'Missing required fields'], 400);
        }

        if (!array_key_exists($leaveType, Leave::getLeaveTypes())) {
            return $this->json(['error' => 'Invalid leave type'], 400);
        }

        try {
            $sql = "INSERT INTO leave_balances (employee_id, {$leaveType}) 
                    VALUES (?, ?)
                    ON DUPLICATE KEY UPDATE {$leaveType} = ?";
            
            $stmt = $this->db->prepare($sql);
            $stmt->bind_param('idd', $employeeId, $balance, $balance);
            $stmt->execute();

            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get request data
     */
    private function getRequestData() {
        return [
            'employee_id' => $_POST['employee_id'] ?? null,
            'leave_type' => $_POST['leave_type'] ?? null,
            'start_date' => $_POST['start_date'] ?? null,
            'end_date' => $_POST['end_date'] ?? null,
            'reason' => $_POST['reason'] ?? null
        ];
    }
}
