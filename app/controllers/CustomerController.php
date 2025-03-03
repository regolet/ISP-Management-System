<?php
namespace App\Controllers;

use App\Core\Controller;
use App\Models\Customer;

class CustomerController extends Controller {
    private $customerModel;

    public function __construct() {
        $this->customerModel = new Customer();
    }

    // List all customers
    public function index() {
        // Check authentication and permissions
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $searchCriteria = [
            'name' => $this->getQuery('search'),
            'status' => $this->getQuery('status'),
            'email' => $this->getQuery('email')
        ];

        $customers = $this->customerModel->search($searchCriteria);

        return $this->view('customers/index', [
            'customers' => $customers,
            'searchCriteria' => $searchCriteria,
            'layout' => 'navbar'
        ]);
    }

    // Show customer creation form
    public function create() {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        if ($this->isPost()) {
            $data = $this->getPost();
            
            // Validate input
            $errors = $this->customerModel->validate($data);
            
            if (empty($errors)) {
                // Add timestamps
                $data['created_at'] = date('Y-m-d H:i:s');
                $data['updated_at'] = date('Y-m-d H:i:s');
                
                if ($this->customerModel->create($data)) {
                    $_SESSION['success'] = 'Customer created successfully';
                    return $this->redirect('/customers');
                } else {
                    $errors['general'] = 'Failed to create customer';
                }
            }

            return $this->view('customers/create', [
                'errors' => $errors,
                'data' => $data,
                'layout' => 'navbar'
            ]);
        }

        return $this->view('customers/create', [
            'layout' => 'navbar'
        ]);
    }

    // Show customer details
    public function show($id) {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $customer = $this->customerModel->find($id);
        
        if (!$customer) {
            $_SESSION['error'] = 'Customer not found';
            return $this->redirect('/customers');
        }

        // Get related data
        $subscriptions = $this->customerModel->getSubscriptions();
        $billingHistory = $this->customerModel->getBillingHistory();
        $networkDevices = $this->customerModel->getNetworkDevices();
        $currentBalance = $this->customerModel->getCurrentBalance();

        return $this->view('customers/show', [
            'customer' => $customer,
            'subscriptions' => $subscriptions,
            'billingHistory' => $billingHistory,
            'networkDevices' => $networkDevices,
            'currentBalance' => $currentBalance,
            'layout' => 'navbar'
        ]);
    }

    // Show customer edit form
    public function edit($id) {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $customer = $this->customerModel->find($id);
        
        if (!$customer) {
            $_SESSION['error'] = 'Customer not found';
            return $this->redirect('/customers');
        }

        if ($this->isPost()) {
            $data = $this->getPost();
            
            // Validate input
            $errors = $this->customerModel->validate($data);
            
            if (empty($errors)) {
                // Add updated timestamp
                $data['updated_at'] = date('Y-m-d H:i:s');
                
                if ($this->customerModel->update($id, $data)) {
                    $_SESSION['success'] = 'Customer updated successfully';
                    return $this->redirect("/customers/{$id}");
                } else {
                    $errors['general'] = 'Failed to update customer';
                }
            }

            return $this->view('customers/edit', [
                'customer' => $customer,
                'errors' => $errors,
                'data' => $data,
                'layout' => 'navbar'
            ]);
        }

        return $this->view('customers/edit', [
            'customer' => $customer,
            'layout' => 'navbar'
        ]);
    }

    // Delete customer
    public function delete($id) {
        if (!$this->isAuthenticated()) {
            $this->json(['error' => 'Unauthorized']);
            return;
        }

        $customer = $this->customerModel->find($id);
        
        if (!$customer) {
            $this->json(['error' => 'Customer not found']);
            return;
        }

        if ($this->customerModel->delete($id)) {
            $this->json(['success' => true]);
        } else {
            $this->json(['error' => 'Failed to delete customer']);
        }
    }

    // Export customers to CSV
    public function export() {
        if (!$this->isAuthenticated()) {
            return $this->redirect('/login');
        }

        $customers = $this->customerModel->all();
        
        // Set headers for CSV download
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="customers.csv"');
        
        $output = fopen('php://output', 'w');
        
        // Add CSV headers
        fputcsv($output, ['ID', 'First Name', 'Last Name', 'Email', 'Phone', 'Address', 'Status', 'Created At']);
        
        // Add customer data
        foreach ($customers as $customer) {
            fputcsv($output, [
                $customer['id'],
                $customer['first_name'],
                $customer['last_name'],
                $customer['email'],
                $customer['phone'],
                $customer['address'],
                $customer['status'],
                $customer['created_at']
            ]);
        }
        
        fclose($output);
        exit;
    }
}
