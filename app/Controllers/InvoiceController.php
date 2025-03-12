<?php
namespace App\Controllers;

use App\Models\Invoice;
use PDO;

class InvoiceController {
    private $db;
    private $invoiceModel;

    public function __construct($db) {
        $this->db = $db;
        $this->invoiceModel = new Invoice($this->db);
    }

    /**
     * Get all invoices with optional filtering
     */
    public function getInvoices($params = []) {
        $search = $params['search'] ?? '';
        $status = $params['status'] ?? '';
        $page = $params['page'] ?? 1;
        $per_page = $params['per_page'] ?? 10;
        $sort = $params['sort'] ?? 'due_date';
        $order = $params['order'] ?? 'DESC';

        $invoices = $this->invoiceModel->getAll($page, $per_page, $search, $status, $sort, $order);
        $total_invoices = $this->invoiceModel->getTotal($search, $status);
        $total_pages = ceil($total_invoices / $per_page);

        $pagination = [
            'current_page' => (int)$page,
            'last_page' => (int)$total_pages
        ];

        return [
            'invoices' => $invoices,
            'pagination' => $pagination
        ];
    }

    /**
     * Get single invoice by ID
     */
    public function getInvoiceById($id) {
        return $this->invoiceModel->getById($id);
    }

    /**
     * Create invoice
     */
    public function createInvoice($client_id, $invoice_number, $total_amount, $due_date, $status, $billing_items = []) {
        // Create invoice
        $invoice_id = $this->invoiceModel->create($client_id, $invoice_number, $total_amount, $due_date, $status, $total_amount);

        // Store billing items
        if ($invoice_id) {
            foreach ($billing_items as $item) {
                $this->invoiceModel->createInvoiceItem($invoice_id, $item['description'], $item['quantity'], $item['price'], $item['total']);
            }
            return true;
        } else {
            return false;
        }
    }

    /**
     * Delete invoice
     */
    public function deleteInvoice($id) {
        return $this->invoiceModel->delete($id);
    }
}