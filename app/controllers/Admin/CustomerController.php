<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Admin\Customer;

class CustomerController extends Controller {
    private $customerModel;

    public function __construct() {
        parent::__construct();
        $this->customerModel = new Customer();
    }

    /**
     * Display customer list
     */
    public function index() {
        $page = $_GET['page'] ?? 1;
        $filters = [
            'search' => $_GET['search'] ?? null,
            'status' => $_GET['status'] ?? null,
            'plan' => $_GET['plan'] ?? null,
            'date_range' => $_GET['date_range'] ?? null
        ];

        $result = $this->customerModel->getCustomers($filters, $page);

        $queryString = http_build_query(array_filter($filters));

        return $this->view('admin/customers/index', [
            'customers' => $result['customers'],
            'totalPages' => $result['pages'],
            'page' => $page,
            'filters' => $filters,
            'queryString' => $queryString ? '&' . $queryString : '',
            'plans' => $this->getPlansList(),
            'layout' => 'navbar',
            'title' => 'Manage Customers'
        ]);
    }

    /**
     * Show customer creation form
     */
    public function create() {
        return $this->view('admin/customers/create', [
            'plans' => $this->getPlansList(),
            'routers' => $this->getRoutersList(),
            'onts' => $this->getONTsList(),
            'layout' => 'navbar',
            'title' => 'Add Customer'
        ]);
    }

    /**
     * Store new customer
     */
    public function store() {
        $data = $this->getRequestData();
        
        // Validate input
        $errors = $this->customerModel->validate($data);
        if (!empty($errors)) {
            return $this->view('admin/customers/create', [
                'errors' => $errors,
                'data' => $data,
                'plans' => $this->getPlansList(),
                'routers' => $this->getRoutersList(),
                'onts' => $this->getONTsList(),
                'layout' => 'navbar',
                'title' => 'Add Customer'
            ]);
        }

        try {
            $customerId = $this->customerModel->createCustomer($data);

            if ($data['send_credentials'] ?? false) {
                $this->sendCredentials($data['email'], $data['username'], $data['password']);
            }

            $this->setFlash('success', 'Customer created successfully');
            return $this->redirect('/admin/customers');

        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to create customer: ' . $e->getMessage());
            return $this->view('admin/customers/create', [
                'errors' => ['general' => $e->getMessage()],
                'data' => $data,
                'plans' => $this->getPlansList(),
                'routers' => $this->getRoutersList(),
                'onts' => $this->getONTsList(),
                'layout' => 'navbar',
                'title' => 'Add Customer'
            ]);
        }
    }

    /**
     * Handle bulk actions
     */
    public function bulkAction() {
        $data = json_decode(file_get_contents('php://input'), true);
        
        if (empty($data['ids']) || empty($data['action'])) {
            return $this->json(['error' => 'Invalid request'], 400);
        }

        try {
            $this->customerModel->bulkAction($data['ids'], $data['action']);
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Suspend customer
     */
    public function suspend($id) {
        try {
            $this->customerModel->bulkAction([$id], 'suspend');
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Delete customer
     */
    public function delete($id) {
        try {
            $this->customerModel->bulkAction([$id], 'delete');
            return $this->json(['success' => true]);
        } catch (\Exception $e) {
            return $this->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Export customers
     */
    public function export() {
        $filters = [
            'search' => $_GET['search'] ?? null,
            'status' => $_GET['status'] ?? null,
            'plan' => $_GET['plan'] ?? null,
            'date_range' => $_GET['date_range'] ?? null
        ];

        $result = $this->customerModel->getCustomers($filters, 1, PHP_INT_MAX);
        $customers = $result['customers'];

        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="customers_' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // Add CSV headers
        fputcsv($output, [
            'Account Number',
            'Name',
            'Email',
            'Phone',
            'Address',
            'Plan',
            'Installation Date',
            'Contract End Date',
            'Status',
            'Created At'
        ]);

        // Add customer data
        foreach ($customers as $customer) {
            fputcsv($output, [
                $customer['account_number'],
                $customer['first_name'] . ' ' . $customer['last_name'],
                $customer['email'],
                $customer['phone'],
                $customer['address'],
                $customer['plan_name'],
                $customer['installation_date'],
                $customer['contract_end_date'],
                $customer['status'],
                $customer['created_at']
            ]);
        }

        fclose($output);
        exit;
    }

    /**
     * Get request data
     */
    private function getRequestData() {
        return [
            'first_name' => $_POST['first_name'] ?? null,
            'last_name' => $_POST['last_name'] ?? null,
            'email' => $_POST['email'] ?? null,
            'phone' => $_POST['phone'] ?? null,
            'address' => $_POST['address'] ?? null,
            'installation_address' => $_POST['installation_address'] ?? null,
            'plan_id' => $_POST['plan_id'] ?? null,
            'installation_date' => $_POST['installation_date'] ?? null,
            'contract_period' => $_POST['contract_period'] ?? null,
            'ip_type' => $_POST['ip_type'] ?? null,
            'router_model' => $_POST['router_model'] ?? null,
            'router_serial' => $_POST['router_serial'] ?? null,
            'ont_model' => $_POST['ont_model'] ?? null,
            'ont_serial' => $_POST['ont_serial'] ?? null,
            'username' => $_POST['username'] ?? null,
            'password' => $_POST['password'] ?? null,
            'send_credentials' => isset($_POST['send_credentials']),
            'notes' => $_POST['notes'] ?? null
        ];
    }

    /**
     * Get available plans
     */
    private function getPlansList() {
        $sql = "SELECT id, name, amount FROM plans WHERE status = 'active' ORDER BY amount";
        return $this->db()->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get available routers
     */
    private function getRoutersList() {
        $sql = "SELECT * FROM routers WHERE status = 'active' ORDER BY model";
        return $this->db()->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Get available ONTs
     */
    private function getONTsList() {
        $sql = "SELECT * FROM onts WHERE status = 'active' ORDER BY model";
        return $this->db()->query($sql)->fetch_all(MYSQLI_ASSOC);
    }

    /**
     * Send login credentials via email
     */
    private function sendCredentials($email, $username, $password) {
        // Email configuration and sending logic here
        // This is just a placeholder - implement actual email sending
        $to = $email;
        $subject = 'Your ISP Account Credentials';
        $message = "Hello,\n\n"
                . "Your ISP account has been created. Here are your login credentials:\n\n"
                . "Username: {$username}\n"
                . "Password: {$password}\n\n"
                . "Please change your password after first login.\n\n"
                . "Best regards,\n"
                . "ISP Management System";
        
        mail($to, $subject, $message);
    }
}
