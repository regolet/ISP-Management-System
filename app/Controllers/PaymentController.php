<?php
namespace App\Controllers;

require_once dirname(__DIR__) . '/Models/Payment.php';

class PaymentController {
    private $db;
    private $payment;

    public function __construct($db) {
        $this->db = $db;
        $this->payment = new \App\Models\Payment($db);
    }

    public function getPayments($params = []) {
        $page = isset($params['page']) ? (int)$params['page'] : 1;
        $per_page = isset($params['per_page']) ? (int)$params['per_page'] : 10;
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? '';
        $sort = $params['sort'] ?? 'id';
        $order = $params['order'] ?? 'ASC';

        $payments = $this->payment->getAll($page, $per_page, $search, $status, $sort, $order);
        $total = $this->payment->getTotal($search, $status);

        return [
            'data' => $payments,
            'total' => $total,
            'page' => $page,
            'per_page' => $per_page,
            'total_pages' => ceil($total / $per_page)
        ];
    }

    public function getPayment($id) {
        return $this->payment->getById($id);
    }

    public function createPayment($data) {
        try {
            $this->validatePaymentData($data);

            foreach ($data as $key => $value) {
                if (property_exists($this->payment, $key)) {
                    $this->payment->$key = $value;
                }
            }

            $this->payment->status = $data['status'] ?? 'pending';
            $this->payment->payment_date = $data['payment_date'] ?? date('Y-m-d H:i:s');

            if ($this->payment->create()) {
                return [
                    'success' => true,
                    'message' => 'Payment recorded successfully',
                    'payment_id' => $this->payment->id
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to record payment'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function updatePayment($id, $data) {
        try {
            $this->validatePaymentData($data, true);

            $this->payment->id = $id;
            foreach ($data as $key => $value) {
                if (property_exists($this->payment, $key)) {
                    $this->payment->$key = $value;
                }
            }

            if ($this->payment->update()) {
                return [
                    'success' => true,
                    'message' => 'Payment updated successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to update payment'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function deletePayment($id) {
        try {
            $paymentData = $this->payment->getById($id);
            if (!$paymentData) {
                throw new \Exception('Payment not found');
            }

            if ($this->payment->delete($id)) {
                return [
                    'success' => true,
                    'message' => 'Payment deleted successfully'
                ];
            }

            return [
                'success' => false,
                'message' => 'Failed to delete payment'
            ];

        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    private function validatePaymentData($data, $isUpdate = false) {
        $errors = [];

        if (!$isUpdate) {
            if (empty($data['billing_id']) || !is_numeric($data['billing_id'])) {
                $errors[] = 'Valid billing ID is required';
            }
        }

        if (empty($data['amount']) || !is_numeric($data['amount'])) {
            $errors[] = 'Valid amount is required';
        }

        if (empty($data['payment_method']) || !in_array($data['payment_method'], ['cash', 'credit_card', 'bank_transfer', 'online'])) {
            $errors[] = 'Valid payment method is required';
        }

        if (!empty($data['status']) && !in_array($data['status'], ['pending', 'completed', 'failed', 'refunded'])) {
            $errors[] = 'Invalid status value';
        }

        if (!empty($errors)) {
            throw new \Exception(implode(', ', $errors));
        }
    }
}
