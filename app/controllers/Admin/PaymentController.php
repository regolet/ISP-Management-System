<?php
namespace App\Controllers\Admin;

use App\Core\Controller;
use App\Models\Admin\Payment;
use App\Models\Admin\Billing;

class PaymentController extends Controller {
    private $paymentModel;
    private $billingModel;

    public function __construct() {
        parent::__construct();
        $this->paymentModel = new Payment();
        $this->billingModel = new Billing();
    }

    /**
     * Display payments list
     */
    public function index() {
        $page = $_GET['page'] ?? 1;
        $filters = [
            'search' => $_GET['search'] ?? null,
            'status' => $_GET['status'] ?? null,
            'payment_method' => $_GET['payment_method'] ?? null,
            'date_range' => $_GET['date_range'] ?? null
        ];

        $result = $this->paymentModel->getPayments($filters, $page);

        return $this->view('admin/payments/index', [
            'payments' => $result['payments'],
            'totalPages' => $result['pages'],
            'page' => $page,
            'filters' => $filters,
            'paymentMethods' => Payment::getPaymentMethods()
        ]);
    }

    /**
     * Show payment creation form
     */
    public function create($billingId = null) {
        $billing = null;
        if ($billingId) {
            $billing = $this->billingModel->getBillingDetails($billingId);
            if (!$billing) {
                $this->setFlash('error', 'Invoice not found');
                return $this->redirect('/admin/billing');
            }

            if ($billing['status'] === 'paid') {
                $this->setFlash('error', 'Invoice is already paid');
                return $this->redirect("/admin/billing/view/{$billingId}");
            }
        }

        return $this->view('admin/payments/create', [
            'billing' => $billing,
            'paymentMethods' => Payment::getPaymentMethods()
        ]);
    }

    /**
     * Store new payment
     */
    public function store() {
        $data = $this->getRequestData();
        
        // Validate input
        $errors = $this->paymentModel->validate($data);
        if (!empty($errors)) {
            $billing = $this->billingModel->getBillingDetails($data['billing_id']);
            return $this->view('admin/payments/create', [
                'errors' => $errors,
                'data' => $data,
                'billing' => $billing,
                'paymentMethods' => Payment::getPaymentMethods()
            ]);
        }

        try {
            // Add current user as creator
            $data['created_by'] = $_SESSION['user_id'];
            $data['status'] = 'completed';

            $paymentId = $this->paymentModel->recordPayment($data);
            $this->setFlash('success', 'Payment recorded successfully');
            return $this->redirect("/admin/payments/view/{$paymentId}");

        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to record payment: ' . $e->getMessage());
            $billing = $this->billingModel->getBillingDetails($data['billing_id']);
            return $this->view('admin/payments/create', [
                'errors' => ['general' => $e->getMessage()],
                'data' => $data,
                'billing' => $billing,
                'paymentMethods' => Payment::getPaymentMethods()
            ]);
        }
    }

    /**
     * Show payment details
     */
    public function show($id) {
        $payment = $this->paymentModel->getPaymentDetails($id);
        if (!$payment) {
            $this->setFlash('error', 'Payment not found');
            return $this->redirect('/admin/payments');
        }

        return $this->view('admin/payments/show', [
            'payment' => $payment
        ]);
    }

    /**
     * Show payment edit form
     */
    public function edit($id) {
        $payment = $this->paymentModel->getPaymentDetails($id);
        if (!$payment) {
            $this->setFlash('error', 'Payment not found');
            return $this->redirect('/admin/payments');
        }

        if ($payment['status'] !== 'pending') {
            $this->setFlash('error', 'Only pending payments can be edited');
            return $this->redirect('/admin/payments');
        }

        return $this->view('admin/payments/edit', [
            'payment' => $payment,
            'paymentMethods' => Payment::getPaymentMethods()
        ]);
    }

    /**
     * Update payment
     */
    public function update($id) {
        $payment = $this->paymentModel->find($id);
        if (!$payment) {
            $this->setFlash('error', 'Payment not found');
            return $this->redirect('/admin/payments');
        }

        if ($payment['status'] !== 'pending') {
            $this->setFlash('error', 'Only pending payments can be updated');
            return $this->redirect('/admin/payments');
        }

        $data = $this->getRequestData();
        $data['id'] = $id;

        // Validate input
        $errors = $this->paymentModel->validate($data);
        if (!empty($errors)) {
            return $this->view('admin/payments/edit', [
                'errors' => $errors,
                'data' => $data,
                'payment' => $payment,
                'paymentMethods' => Payment::getPaymentMethods()
            ]);
        }

        try {
            $this->paymentModel->update($id, $data);
            $this->billingModel->updateStatus($payment['billing_id']);
            
            $this->setFlash('success', 'Payment updated successfully');
            return $this->redirect("/admin/payments/view/{$id}");

        } catch (\Exception $e) {
            $this->setFlash('error', 'Failed to update payment: ' . $e->getMessage());
            return $this->view('admin/payments/edit', [
                'errors' => ['general' => $e->getMessage()],
                'data' => $data,
                'payment' => $payment,
                'paymentMethods' => Payment::getPaymentMethods()
            ]);
        }
    }

    /**
     * Void payment
     */
    public function void($id) {
        $payment = $this->paymentModel->find($id);
        if (!$payment) {
            return $this->json(['error' => 'Payment not found'], 404);
        }

        if ($payment['status'] === 'void') {
            return $this->json(['error' => 'Payment is already voided'], 400);
        }

        try {
            $this->paymentModel->voidPayment($id);
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
            'billing_id' => $_POST['billing_id'] ?? null,
            'amount' => $_POST['amount'] ?? null,
            'payment_date' => $_POST['payment_date'] ?? date('Y-m-d H:i:s'),
            'payment_method' => $_POST['payment_method'] ?? null,
            'reference_no' => $_POST['reference_no'] ?? null,
            'notes' => $_POST['notes'] ?? null
        ];
    }
}
