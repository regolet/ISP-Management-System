<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Admin\Billing;
use App\Models\Admin\Customer;

class BillingController extends Controller {
    private $billingModel;
    private $customerModel;

    public function __construct() {
        parent::__construct();
        $this->billingModel = new Billing();
        $this->customerModel = new Customer();
    }

    /**
     * Display billing list
     */
    public function index() {
        $page = $_GET['page'] ?? 1;
        $filters = [
            'search' => $_GET['search'] ?? null,
            'status' => $_GET['status'] ?? null,
            'date_range' => $_GET['date_range'] ?? null
        ];

        $result = $this->billingModel->getBillingRecords($filters, $page);

        return $this->view('admin/billing/index', [
            'bills' => $result['bills'],
            'totalPages' => $result['pages'],
            'page' => $page,
            'filters' => $filters,
            'layout' => 'navbar',
            'title' => 'Billing Management'
            
        ]);
    }

    /**
     * Show billing creation form
     */
    public function create() {
        $customers = $this->customerModel->getActiveCustomers();
        
        return $this->view('admin/billing/create', [
            'customers' => $customers,
            'layout' => 'navbar',
            'title' => 'Create Invoice'
        ]);
    }

    /**
     * Store new billing record
     */
    public function store() {
        $data = $this->getRequestData();
        
        // Validate input
        $errors = $this->billingModel->validate($data);
        if (!empty($errors)) {
            $customers = $this->customerModel->getActiveCustomers();
            return $this->view('admin/billing/create', [
                'errors' => $errors,
                'data' => $data,
                'customers' => $customers,
                'layout' => 'navbar',
                'title' => 'Create Invoice'
            ]);
        }

        try {
            $billingId = $this->billingModel->createBilling($data);
            $this->setFlash('success', 'Invoice created successfully');
            return $this->redirect("/admin/billing/view/{$billingId}");

        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to create invoice: ' . $e->getMessage());
            $customers = $this->customerModel->getActiveCustomers();
            return $this->view('admin/billing/create', [
                'errors' => ['general' => $e->getMessage()],
                'data' => $data,
                'customers' => $customers,
                'layout' => 'navbar',
                'title' => 'Create Invoice'
            ]);
        }
    }

    /**
     * Show billing details
     */
    public function show($id) {
        $bill = $this->billingModel->getBillingDetails($id);
        if (!$bill) {
            $this->setFlash('error', 'Invoice not found');
            return $this->redirect('/admin/billing');
        }

        return $this->view('admin/billing/show', [
            'bill' => $bill,
            'layout' => 'navbar',
            'title' => 'Invoice Details'
        ]);
    }

    /**
     * Show billing edit form
     */
    public function edit($id) {
        $bill = $this->billingModel->getBillingDetails($id);
        if (!$bill) {
            $this->setFlash('error', 'Invoice not found');
            return $this->redirect('/admin/billing');
        }

        if ($bill['status'] === 'paid' || $bill['status'] === 'void') {
            $this->setFlash('error', 'Cannot edit paid or voided invoices');
            return $this->redirect('/admin/billing');
        }

        $customers = $this->customerModel->getActiveCustomers();

        return $this->view('admin/billing/edit', [
            'bill' => $bill,
            'customers' => $customers,
            'layout' => 'navbar',
            'title' => 'Edit Invoice'
        ]);
    }

    /**
     * Update billing record
     */
    public function update($id) {
        $bill = $this->billingModel->find($id);
        if (!$bill) {
            $this->setFlash('error', 'Invoice not found');
            return $this->redirect('/admin/billing');
        }

        if ($bill['status'] === 'paid' || $bill['status'] === 'void') {
            $this->setFlash('error', 'Cannot update paid or voided invoices');
            return $this->redirect('/admin/billing');
        }

        $data = $this->getRequestData();
        $data['id'] = $id;

        // Validate input
        $errors = $this->billingModel->validate($data);
        if (!empty($errors)) {
            $customers = $this->customerModel->getActiveCustomers();
            return $this->view('admin/billing/edit', [
                'errors' => $errors,
                'data' => $data,
                'bill' => $bill,
                'customers' => $customers,
                'layout' => 'navbar',
                'title' => 'Edit Invoice'
            ]);
        }

        try {
            $this->billingModel->update($id, $data);
            $this->setFlash('success', 'Invoice updated successfully');
            return $this->redirect("/admin/billing/view/{$id}");

        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to update invoice: ' . $e->getMessage());
            $customers = $this->customerModel->getActiveCustomers();
            return $this->view('admin/billing/edit', [
                'errors' => ['general' => $e->getMessage()],
                'data' => $data,
                'bill' => $bill,
                'customers' => $customers,
                'layout' => 'navbar',
                'title' => 'Edit Invoice'
            ]);
        }
    }

    /**
     * Void billing record
     */
    public function void($id) {
        $bill = $this->billingModel->find($id);
        if (!$bill) {
            return $this->json(['error' => 'Invoice not found'], 404);
        }

        if ($bill['status'] === 'paid') {
            return $this->json(['error' => 'Cannot void paid invoices'], 400);
        }

        try {
            $this->billingModel->update($id, ['status' => 'void']);
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
            'customer_id' => $_POST['customer_id'] ?? null,
            'amount' => $_POST['amount'] ?? null,
            'due_date' => $_POST['due_date'] ?? null,
            'description' => $_POST['description'] ?? null
        ];
    }
}
