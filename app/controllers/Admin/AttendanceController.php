<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Admin\Attendance;
use App\Models\Admin\Employee;

class AttendanceController extends Controller {
    private $attendanceModel;
    private $employeeModel;

    public function __construct() {
        parent::__construct();
        $this->attendanceModel = new Attendance();
        $this->employeeModel = new Employee();
    }

    /**
     * Display attendance records
     */
    public function index() {
        $page = $_GET['page'] ?? 1;
        $filters = [
            'date' => $_GET['date'] ?? date('Y-m-d'),
            'employee_id' => $_GET['employee_id'] ?? null,
            'status' => $_GET['status'] ?? null,
            'department' => $_GET['department'] ?? null
        ];

        $result = $this->attendanceModel->getAttendanceRecords($filters, $page);
        $employees = $this->employeeModel->getActiveEmployees(true);

        return $this->view('admin/attendance/index', [
            'records' => $result['records'],
            'totalPages' => $result['pages'],
            'page' => $page,
            'filters' => $filters,
            'employees' => $employees,
            'departments' => Employee::getDepartments(),
            'layout' => 'navbar',
            'title' => 'Attendance Records'
        ]);
    }

    /**
     * Show attendance record form
     */
    public function create() {
        $employees = $this->employeeModel->getActiveEmployees(true);
        
        return $this->view('admin/attendance/create', [
            'employees' => $employees,
            'date' => date('Y-m-d'),
            'layout' => 'navbar',
            'title' => 'Record Attendance'
        ]);
    }

    /**
     * Store attendance record
     */
    public function store() {
        $data = $this->getRequestData();
        
        // Validate input
        $errors = $this->attendanceModel->validate($data);
        if (!empty($errors)) {
            $employees = $this->employeeModel->getActiveEmployees(true);
            return $this->view('admin/attendance/create', [
                'errors' => $errors,
                'data' => $data,
                'employees' => $employees,
                'layout' => 'navbar',
                'title' => 'Record Attendance'
            ]);
        }

        try {
            $this->attendanceModel->recordAttendance($data);
            $this->setFlash('success', 'Attendance recorded successfully');
            return $this->redirect('/admin/attendance');

        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to record attendance: ' . $e->getMessage());
            $employees = $this->employeeModel->getActiveEmployees(true);
            return $this->view('admin/attendance/create', [
                'errors' => ['general' => $e->getMessage()],
                'data' => $data,
                'employees' => $employees,
                'layout' => 'navbar',
                'title' => 'Record Attendance'
            ]);
        }
    }

    /**
     * Show attendance edit form
     */
    public function edit($id) {
        $record = $this->attendanceModel->getAttendanceDetails($id);
        if (!$record) {
            $this->setFlash('error', 'Attendance record not found');
            return $this->redirect('/admin/attendance');
        }

        $employees = $this->employeeModel->getActiveEmployees(true);

        return $this->view('admin/attendance/edit', [
            'record' => $record,
            'employees' => $employees,
            'layout' => 'navbar',
            'title' => 'Edit Attendance Record'
        ]);
    }

    /**
     * Update attendance record
     */
    public function update($id) {
        $record = $this->attendanceModel->find($id);
        if (!$record) {
            $this->setFlash('error', 'Attendance record not found');
            return $this->redirect('/admin/attendance');
        }

        $data = $this->getRequestData();
        $data['id'] = $id;

        // Validate input
        $errors = $this->attendanceModel->validate($data);
        if (!empty($errors)) {
            $employees = $this->employeeModel->getActiveEmployees(true);
            return $this->view('admin/attendance/edit', [
                'errors' => $errors,
                'data' => $data,
                'record' => $record,
                'employees' => $employees,
                'layout' => 'navbar',
                'title' => 'Edit Attendance Record'
            ]);
        }

        try {
            $this->attendanceModel->updateAttendance($id, $data);
            $this->setFlash('success', 'Attendance updated successfully');
            return $this->redirect('/admin/attendance');

        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to update attendance: ' . $e->getMessage());
            $employees = $this->employeeModel->getActiveEmployees(true);
            return $this->view('admin/attendance/edit', [
                'errors' => ['general' => $e->getMessage()],
                'data' => $data,
                'record' => $record,
                'employees' => $employees,
                'layout' => 'navbar',
                'title' => 'Edit Attendance Record'
            ]);
        }
    }

    /**
     * Show attendance report
     */
    public function report() {
        $month = $_GET['month'] ?? date('m');
        $year = $_GET['year'] ?? date('Y');
        $employeeId = $_GET['employee_id'] ?? null;
        $department = $_GET['department'] ?? null;

        $employees = $this->employeeModel->getActiveEmployees(true);
        $summaries = [];

        foreach ($employees as $employee) {
            if ((!$employeeId || $employee['id'] == $employeeId) && 
                (!$department || $employee['department'] == $department)) {
                $summary = $this->attendanceModel->getMonthlyAttendanceSummary(
                    $employee['id'], 
                    $month, 
                    $year
                );
                $summaries[$employee['id']] = array_merge($employee, $summary);
            }
        }

        return $this->view('admin/attendance/report', [
            'month' => $month,
            'year' => $year,
            'employeeId' => $employeeId,
            'department' => $department,
            'employees' => $employees,
            'departments' => Employee::getDepartments(),
            'summaries' => $summaries,
            'layout' => 'navbar',
            'title' => 'Attendance Report'
        ]);
    }

    /**
     * Get request data
     */
    private function getRequestData() {
        return [
            'employee_id' => $_POST['employee_id'] ?? null,
            'date' => $_POST['date'] ?? date('Y-m-d'),
            'time_in' => $_POST['time_in'] ?? null,
            'time_out' => $_POST['time_out'] ?? null,
            'notes' => $_POST['notes'] ?? null
        ];
    }
}
