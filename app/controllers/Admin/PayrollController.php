<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Staff\Payroll;
use App\Models\Admin\Employee;
use App\Models\Admin\Deduction;

class PayrollController extends Controller 
{
    private $payrollModel;
    private $employeeModel;
    private $deductionModel;

    public function __construct() 
    {
        parent::__construct();
        $this->payrollModel = new Payroll();
        $this->employeeModel = new Employee();
        $this->deductionModel = new Deduction();
    }

    /**
     * Display payroll list
     */
    public function index() 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $page = $this->getQuery('page', 1);
        $periodStart = $this->getQuery('period_start', date('Y-m-01'));
        $periodEnd = $this->getQuery('period_end', date('Y-m-t'));
        $department = $this->getQuery('department');
        $status = $this->getQuery('status');

        $filters = [
            'period_start' => $periodStart,
            'period_end' => $periodEnd,
            'department' => $department,
            'status' => $status
        ];

        $payrolls = $this->payrollModel->getPayrollList($filters, $page);
        $departments = $this->employeeModel->getDepartments();

        return $this->view('admin/payroll/index', [
            'payrolls' => $payrolls['records'],
            'total_pages' => $payrolls['pages'],
            'current_page' => $page,
            'filters' => $filters,
            'departments' => $departments,
            'layout' => 'navbar',
            'title' => 'Payroll Management'
        ]);
    }

    /**
     * Show payroll creation form
     */
    public function create() 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $employees = $this->employeeModel->getActiveEmployees(true);
        $deductions = $this->deductionModel->getActiveDeductions();

        return $this->view('admin/payroll/create', [
            'employees' => $employees,
            'deductions' => $deductions,
            'layout' => 'navbar',
            'title' => 'Create Payroll'
        ]);
    }

    /**
     * Store new payroll record
     */
    public function store() 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $data = $this->getPost();
        $errors = $this->payrollModel->validate($data);

        if (!empty($errors)) {
            $employees = $this->employeeModel->getActiveEmployees(true);
            $deductions = $this->deductionModel->getActiveDeductions();

            return $this->view('admin/payroll/create', [
                'errors' => $errors,
                'data' => $data,
                'employees' => $employees,
                'deductions' => $deductions,
                'layout' => 'navbar',
                'title' => 'Create Payroll'
            ]);
        }

        try {
            // Calculate salary components
            $calculation = $this->payrollModel->calculateNetSalary(
                $data['basic_salary'],
                $data['allowances'] ?? 0,
                $data['deductions'] ?? 0,
                $data['overtime_hours'] ?? 0,
                $data['overtime_rate'] ?? 0
            );

            $data = array_merge($data, $calculation);
            $payrollId = $this->payrollModel->create($data);

            $_SESSION['success'] = 'Payroll record created successfully';
            return $this->redirect("/admin/payroll/{$payrollId}");

        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to create payroll record: ' . $e->getMessage();
            return $this->redirect('/admin/payroll/create');
        }
    }

    /**
     * Show payroll details
     */
    public function show($id) 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $payroll = $this->payrollModel->getPayrollDetails($id);
        if (!$payroll) {
            $_SESSION['error'] = 'Payroll record not found';
            return $this->redirect('/admin/payroll');
        }

        return $this->view('admin/payroll/show', [
            'payroll' => $payroll,
            'layout' => 'navbar',
            'title' => 'Payroll Details'
        ]);
    }

    /**
     * Generate payslip
     */
    public function payslip($id) 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $payslip = $this->payrollModel->generatePayslip($id);
        if (!$payslip) {
            $_SESSION['error'] = 'Failed to generate payslip';
            return $this->redirect("/admin/payroll/{$id}");
        }

        return $this->view('admin/payroll/payslip', [
            'payslip' => $payslip,
            'layout' => 'print',
            'title' => 'Employee Payslip'
        ]);
    }

    /**
     * Generate payroll report
     */
    public function report() 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $startDate = $this->getQuery('start_date', date('Y-m-01'));
        $endDate = $this->getQuery('end_date', date('Y-m-t'));
        $department = $this->getQuery('department');

        $filters = [
            'start_date' => $startDate,
            'end_date' => $endDate,
            'department' => $department
        ];

        $report = $this->payrollModel->generateReport($filters);
        $departments = $this->employeeModel->getDepartments();

        return $this->view('admin/payroll/report', [
            'report' => $report,
            'filters' => $filters,
            'departments' => $departments,
            'layout' => 'navbar',
            'title' => 'Payroll Report'
        ]);
    }

    /**
     * Process payroll payments
     */
    public function process() 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $ids = $this->getPost('payroll_ids', []);
        if (empty($ids)) {
            $_SESSION['error'] = 'No payroll records selected';
            return $this->redirect('/admin/payroll');
        }

        try {
            foreach ($ids as $id) {
                $this->payrollModel->update($id, [
                    'payment_status' => 'paid',
                    'payment_date' => date('Y-m-d H:i:s')
                ]);
            }

            $_SESSION['success'] = 'Payroll payments processed successfully';
        } catch (\Exception $e) {
            $_SESSION['error'] = 'Failed to process payments: ' . $e->getMessage();
        }

        return $this->redirect('/admin/payroll');
    }
}
