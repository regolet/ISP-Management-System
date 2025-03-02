<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Admin\Customer;
use App\Models\Admin\Payment;
use App\Models\Admin\Employee;
use App\Models\Admin\Subscription;
use App\Models\Admin\AuditLog;

class DashboardController extends Controller 
{
    private $customerModel;
    private $paymentModel;
    private $employeeModel;
    private $subscriptionModel;
    private $auditLogModel;

    public function __construct() 
    {
        parent::__construct();
        $this->customerModel = new Customer();
        $this->paymentModel = new Payment();
        $this->employeeModel = new Employee();
        $this->subscriptionModel = new Subscription();
        $this->auditLogModel = new AuditLog();
    }

    /**
     * Display admin dashboard
     */
    public function index() 
    {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        // Get summary statistics
        $stats = [
            'total_customers' => $this->customerModel->getTotalCustomers(),
            'active_customers' => $this->customerModel->getActiveCustomers(),
            'total_revenue' => $this->paymentModel->getTotalRevenue(),
            'monthly_revenue' => $this->paymentModel->getMonthlyRevenue(),
            'total_employees' => $this->employeeModel->getTotalEmployees(),
            'active_employees' => $this->employeeModel->getActiveEmployees(),
            'total_subscriptions' => $this->subscriptionModel->getTotalSubscriptions(),
            'active_subscriptions' => $this->subscriptionModel->getActiveSubscriptions()
        ];

        // Get recent activities
        $activities = $this->auditLogModel->getLogs([], 1, 10)['logs'];

        return $this->view('admin/dashboard/index', [
            'title' => 'Admin Dashboard',
            'stats' => $stats,
            'activities' => $activities,
            'layout' => 'navbar'
        ]);
    }

    /**
     * Get chart data (AJAX)
     */
    public function getChartData() 
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        $type = $this->getQuery('type');
        $period = $this->getQuery('period', 'month');

        try {
            $data = match($type) {
                'revenue' => $this->getRevenueData($period),
                'customers' => $this->getCustomerData($period),
                'subscriptions' => $this->getSubscriptionData(),
                'payments' => $this->getPaymentData(),
                default => throw new \Exception('Invalid chart type')
            };

            return $this->json(['success' => true, 'data' => $data]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get recent activities (AJAX)
     */
    public function getActivities() 
    {
        if (!$this->isAuthenticated()) {
            return $this->json(['error' => 'Unauthorized'], 401);
        }

        try {
            $page = $this->getQuery('page', 1);
            $activities = $this->auditLogModel->getLogs([], $page, 10);

            return $this->json([
                'success' => true,
                'activities' => $activities['logs'],
                'hasMore' => $activities['pages'] > $page
            ]);

        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get revenue chart data
     */
    private function getRevenueData($period) 
    {
        $labels = [];
        $data = [];

        switch ($period) {
            case 'week':
                for ($i = 6; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $labels[] = date('D', strtotime($date));
                    $data[] = $this->paymentModel->getDailyRevenue($date);
                }
                break;

            case 'month':
                for ($i = 29; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $labels[] = date('M j', strtotime($date));
                    $data[] = $this->paymentModel->getDailyRevenue($date);
                }
                break;

            case 'year':
                for ($i = 11; $i >= 0; $i--) {
                    $date = date('Y-m', strtotime("-$i months"));
                    $labels[] = date('M Y', strtotime($date));
                    $data[] = $this->paymentModel->getMonthlyRevenue($date);
                }
                break;
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'Revenue',
                'data' => $data,
                'borderColor' => '#0d6efd',
                'backgroundColor' => 'rgba(13, 110, 253, 0.1)',
                'fill' => true
            ]]
        ];
    }

    /**
     * Get customer growth chart data
     */
    private function getCustomerData($period) 
    {
        $labels = [];
        $data = [];

        switch ($period) {
            case 'week':
                for ($i = 6; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $labels[] = date('D', strtotime($date));
                    $data[] = $this->customerModel->getNewCustomers($date);
                }
                break;

            case 'month':
                for ($i = 29; $i >= 0; $i--) {
                    $date = date('Y-m-d', strtotime("-$i days"));
                    $labels[] = date('M j', strtotime($date));
                    $data[] = $this->customerModel->getNewCustomers($date);
                }
                break;

            case 'year':
                for ($i = 11; $i >= 0; $i--) {
                    $date = date('Y-m', strtotime("-$i months"));
                    $labels[] = date('M Y', strtotime($date));
                    $data[] = $this->customerModel->getNewCustomersByMonth($date);
                }
                break;
        }

        return [
            'labels' => $labels,
            'datasets' => [[
                'label' => 'New Customers',
                'data' => $data,
                'borderColor' => '#198754',
                'backgroundColor' => 'rgba(25, 135, 84, 0.1)',
                'fill' => true
            ]]
        ];
    }

    /**
     * Get subscription distribution chart data
     */
    private function getSubscriptionData() 
    {
        $plans = $this->subscriptionModel->getSubscriptionsByPlan();
        
        return [
            'labels' => array_column($plans, 'name'),
            'datasets' => [[
                'data' => array_column($plans, 'count'),
                'backgroundColor' => [
                    '#0d6efd', '#198754', '#dc3545', '#ffc107', 
                    '#0dcaf0', '#6610f2', '#fd7e14', '#20c997'
                ]
            ]]
        ];
    }

    /**
     * Get payment status distribution chart data
     */
    private function getPaymentData() 
    {
        $statuses = $this->paymentModel->getPaymentsByStatus();
        
        return [
            'labels' => array_column($statuses, 'status'),
            'datasets' => [[
                'data' => array_column($statuses, 'count'),
                'backgroundColor' => [
                    '#198754', '#ffc107', '#dc3545'
                ]
            ]]
        ];
    }
}
