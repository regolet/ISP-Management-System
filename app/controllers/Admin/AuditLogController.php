<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Admin\AuditLog;

class AuditLogController extends Controller 
{
    private $auditLogModel;

    public function __construct() 
    {
        parent::__construct();
        $this->auditLogModel = new AuditLog();
    }

    /**
     * Display audit logs
     */
    public function index() 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $page = $this->getQuery('page', 1);
        $filters = [
            'user_id' => $this->getQuery('user_id'),
            'action' => $this->getQuery('action'),
            'module' => $this->getQuery('module'),
            'date_range' => $this->getQuery('date_range')
        ];

        $result = $this->auditLogModel->getLogs($filters, $page);

        return $this->view('admin/audit/index', [
            'logs' => $result['logs'],
            'total_pages' => $result['pages'],
            'current_page' => $page,
            'filters' => $filters,
            'modules' => $this->auditLogModel->getModules(),
            'actions' => $this->auditLogModel->getActions(),
            'layout' => 'navbar',
            'title' => 'Audit Logs'
        ]);
    }

    /**
     * View log details
     */
    public function show($id) 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $log = $this->auditLogModel->find($id);
        if (!$log) {
            $_SESSION['error'] = 'Log entry not found';
            return $this->redirect('/admin/audit');
        }

        // Format JSON data for display
        if ($log['old_values']) {
            $log['old_values'] = json_decode($log['old_values'], true);
        }
        if ($log['new_values']) {
            $log['new_values'] = json_decode($log['new_values'], true);
        }

        return $this->view('admin/audit/show', [
            'log' => $log,
            'layout' => 'navbar',
            'title' => 'Audit Log Details'
        ]);
    }

    /**
     * Export audit logs
     */
    public function export() 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $filters = [
            'user_id' => $this->getQuery('user_id'),
            'action' => $this->getQuery('action'),
            'module' => $this->getQuery('module'),
            'date_range' => $this->getQuery('date_range')
        ];

        // Get all logs without pagination
        $result = $this->auditLogModel->getLogs($filters, 1, PHP_INT_MAX);
        $logs = $result['logs'];

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="audit_logs_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // Add CSV headers
        fputcsv($output, [
            'Date',
            'User',
            'Role',
            'Action',
            'Module',
            'IP Address',
            'User Agent',
            'Old Values',
            'New Values'
        ]);

        // Add log data
        foreach ($logs as $log) {
            fputcsv($output, [
                $log['created_at'],
                $log['username'],
                $log['role'],
                $log['action'],
                $log['module'],
                $log['ip_address'],
                $log['user_agent'],
                $log['old_values'],
                $log['new_values']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Clean old logs
     */
    public function clean() 
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $days = (int)$this->getPost('days', 90);
            $this->auditLogModel->cleanOldLogs($days);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }
}
