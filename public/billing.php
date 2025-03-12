<?php
session_start();
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Controllers/BillingController.php';
require_once dirname(__DIR__) . '/views/layouts/sidebar.php';

// Initialize Auth Controller
$auth = new \App\Controllers\AuthController();

// Check if user is not logged in
if (!$auth->isLoggedIn()) {
    header("Location: login.php");
    exit();
}

// Get database connection
$database = new Database();
$db = $database->getConnection();

// Initialize Billing Controller
$billingController = new \App\Controllers\BillingController($db);

// Get page parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$search = $_GET['search'] ?? '';
$status = $_GET['status'] ?? '';
$sort = $_GET['sort'] ?? 'billing_date';
$order = $_GET['order'] ?? 'DESC';

// Check if viewing a specific invoice
$viewInvoice = false;
$invoiceId = isset($_GET['invoice']) ? (int)$_GET['invoice'] : null;

if ($invoiceId) {
    $viewInvoice = true;
    $invoiceData = $billingController->getInvoice($invoiceId);
    
    if (!$invoiceData) {
        $_SESSION['error'] = "Invoice not found";
        header("Location: billing.php");
        exit();
    }
} else {
    // Get invoices data
    $invoicesData = $billingController->getInvoices([
        'page' => $page,
        'per_page' => 10,
        'search' => $search,
        'status' => $status,
        'sort' => $sort,
        'order' => $order
    ]);
}

// Get billing statistics
$stats = $billingController->getBillingStats();

// Add helper function for payment status styling
function getPaymentStatusClass($status) {
    switch($status) {
        case 'completed': return 'success';
        case 'pending': return 'warning';
        case 'failed': return 'danger';
        case 'refunded': return 'info';
        default: return 'secondary';
    }
}

// Add helper function for invoice status styling
function getStatusClass($status) {
    switch($status) {
        case 'pending': return 'warning';
        case 'paid': return 'success';
        case 'overdue': return 'danger';
        case 'cancelled': return 'secondary';
        default: return 'info';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo $viewInvoice ? 'Invoice #' . $invoiceData['invoice']['invoice_number'] : 'Billing Management'; ?> - ISP Management System</title>
    
    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">

    <style>
        .status-badge {
            padding: 6px 12px;
            border-radius: 20px;
            font-size: 0.85em;
            font-weight: 500;
        }
        .status-pending { background-color: #ffeeba; color: #856404; }
        .status-paid { background-color: #d4edda; color: #155724; }
        .status-overdue { background-color: #f8d7da; color: #721c24; }
        .status-cancelled { background-color: #e2e3e5; color: #383d41; }
        
        .stats-card {
            border-radius: 10px;
            box-shadow: 0 4px 8px rgba(0, 0, 0, 0.1);
            transition: transform 0.2s;
        }
        .stats-card:hover {
            transform: translateY(-5px);
        }
        
        .invoice-header {
            border-bottom: 2px solid #f0f0f0;
            margin-bottom: 20px;
            padding-bottom: 20px;
        }
        
        .payment-item {
            background-color: #f9f9f9;
            border-radius: 5px;
            padding: 15px;
            margin-bottom: 10px;
        }
        
        .payment-item.completed {
            border-left: 4px solid #28a745;
        }
        
        .payment-item.pending {
            border-left: 4px solid #ffc107;
        }
        
        .payment-item.failed {
            border-left: 4px solid #dc3545;
        }
        
        .payment-item.refunded {
            border-left: 4px solid #17a2b8;
        }
        
        @media print {
            .no-print { display: none !important; }
            .print-only { display: block !important; }
        }
    </style>
</head>
<body>
    <!-- Render Sidebar -->
    <?php renderSidebar('billing'); ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <div class="main-content p-4">
            <?php if ($viewInvoice): ?>
                <!-- Invoice View -->
                <div class="row">
                    <div class="col-md-12">
                        <div class="d-flex justify-content-between align-items-center mb-4">
                            <h1 class="h3 mb-0">Invoice #<?php echo htmlspecialchars($invoiceData['invoice']['invoice_number']); ?></h1>
                            <div class="no-print">
                                <a href="billing.php" class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-arrow-left"></i> Back to Billing
                                </a>
                                <div class="btn-group">
                                    <button class="btn btn-outline-primary" onclick="window.print()">
                                        <i class="fas fa-print"></i> Print
                                    </button>
                                    <?php if ($invoiceData['invoice']['status'] === 'pending' || $invoiceData['invoice']['status'] === 'overdue'): ?>
                                        <button class="btn btn-outline-success" data-bs-toggle="modal" data-bs-target="#paymentModal">
                                            <i class="fas fa-money-bill"></i> Record Payment
                                        </button>
                                    <?php endif; ?>
                                    <?php if ($auth->hasRole('admin')): ?>
                                        <button class="btn btn-outline-warning" data-bs-toggle="modal" data-bs-target="#editInvoiceModal">
                                            <i class="fas fa-edit"></i> Edit
                                        </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>

                        <div class="card mb-4">
                            <div class="card-body">
                                <div class="row invoice-header">
                                    <div class="col-md-6">
                                        <h5>Billing Information</h5>
                                        <p>
                                            <strong>Client:</strong> <?php echo htmlspecialchars($invoiceData['invoice']['first_name'] . ' ' . $invoiceData['invoice']['last_name']); ?><br>
                                            <strong>Email:</strong> <?php echo htmlspecialchars($invoiceData['invoice']['email']); ?><br>
                                            <strong>Phone:</strong> <?php echo htmlspecialchars($invoiceData['invoice']['phone']); ?><br>
                                            <strong>Address:</strong> <?php echo htmlspecialchars($invoiceData['invoice']['address']); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-6 text-md-end">
                                        <h5>Invoice Details</h5>
                                        <p>
                                            <strong>Invoice Date:</strong> <?php echo date('M d, Y', strtotime($invoiceData['invoice']['billing_date'])); ?><br>
                                            <strong>Due Date:</strong> <?php echo date('M d, Y', strtotime($invoiceData['invoice']['due_date'])); ?><br>
                                            <strong>Status:</strong> 
                                            <span class="status-badge status-<?php echo $invoiceData['invoice']['status']; ?>">
                                                <?php echo ucfirst($invoiceData['invoice']['status']); ?>
                                            </span>
                                        </p>
                                    </div>
                                </div>

                                <div class="row">
                                    <div class="col-md-12">
                                        <h5>Subscription Details</h5>
                                        <table class="table table-bordered">
                                            <thead class="table-light">
                                                <tr>
                                                    <th>Description</th>
                                                    <th class="text-end">Amount</th>
                                                </tr>
                                            </thead>
                                            <tbody>
                                                <tr>
                                                    <td>
                                                        Subscription Fee
                                                        <br>
                                                        <small class="text-muted">Subscription #<?php echo htmlspecialchars($invoiceData['invoice']['subscription_number']); ?></small>
                                                    </td>
                                                    <td class="text-end">$<?php echo number_format($invoiceData['invoice']['amount'], 2); ?></td>
                                                </tr>
                                            </tbody>
                                            <tfoot>
                                                <tr>
                                                    <th class="text-end">Total:</th>
                                                    <th class="text-end">$<?php echo number_format($invoiceData['invoice']['total_amount'], 2); ?></th>
                                                </tr>
                                            </tfoot>
                                        </table>
                                    </div>
                                </div>

                                <?php if (!empty($invoiceData['payments'])): ?>
                                    <div class="row mt-4">
                                        <div class="col-md-12">
                                            <h5>Payment History</h5>
                                            <?php foreach ($invoiceData['payments'] as $payment): ?>
                                                <div class="payment-item <?php echo $payment['status']; ?>">
                                                    <div class="d-flex justify-content-between">
                                                        <div>
                                                            <strong><?php echo htmlspecialchars($payment['payment_number']); ?></strong>
                                                            <span class="badge bg-<?php echo getPaymentStatusClass($payment['status']); ?> ms-2">
                                                                <?php echo ucfirst($payment['status']); ?>
                                                            </span>
                                                            <br>
                                                            <small><?php echo date('M d, Y h:i A', strtotime($payment['payment_date'])); ?></small>
                                                        </div>
                                                        <div class="text-end">
                                                            <strong>$<?php echo number_format($payment['amount'], 2); ?></strong><br>
                                                            <small><?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?></small>
                                                            <?php if (!empty($payment['transaction_id'])): ?>
                                                                <br><small class="text-muted">Ref: <?php echo htmlspecialchars($payment['transaction_id']); ?></small>
                                                            <?php endif; ?>
                                                        </div>
                                                    </div>
                                                    <?php if (!empty($payment['notes'])): ?>
                                                        <div class="mt-2">
                                                            <small><?php echo htmlspecialchars($payment['notes']); ?></small>
                                                        </div>
                                                    <?php endif; ?>
                                                </div>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            <?php else: ?>
                <!-- Billing List View -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h1 class="h3 mb-0">Billing Management</h1>
                    <?php if ($auth->hasRole('admin')): ?>
                        <button class="btn btn-primary" data-bs-toggle="modal" data-bs-target="#generateInvoicesModal">
                            <i class="fas fa-file-invoice me-2"></i>Generate Invoices
                        </button>
                    <?php endif; ?>
                </div>

                <!-- Billing Stats -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="stats-card card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Outstanding Amount</h6>
                                <h3 class="card-text text-primary">$<?php echo number_format($stats['outstanding_amount'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Total Paid</h6>
                                <h3 class="card-text text-success">$<?php echo number_format($stats['collected_this_month'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Overdue Amount</h6>
                                <h3 class="card-text text-danger">$<?php echo number_format($stats['overdue_amount'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="stats-card card h-100">
                            <div class="card-body">
                                <h6 class="card-title text-muted">Cancelled Amount</h6>
                                <h3 class="card-text text-secondary">$<?php echo number_format($stats['cancelled_amount'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Filters -->
                <div class="card mb-4">
                    <div class="card-body">
                        <form id="filterForm" class="row g-3">
                            <div class="col-md-6">
                                <div class="search-box">
                                    <input type="text" class="form-control" id="search" name="search" 
                                           placeholder="Search invoices..." value="<?php echo htmlspecialchars($search); ?>">
                                    <?php if (!empty($search)): ?>
                                        <span class="clear-search" onclick="clearSearch()">×</span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="status" onchange="this.form.submit()">
                                    <option value="">All Status</option>
                                    <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                    <option value="paid" <?php echo $status === 'paid' ? 'selected' : ''; ?>>Paid</option>
                                    <option value="overdue" <?php echo $status === 'overdue' ? 'selected' : ''; ?>>Overdue</option>
                                    <option value="cancelled" <?php echo $status === 'cancelled' ? 'selected' : ''; ?>>Cancelled</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <select class="form-select" name="sort" onchange="this.form.submit()">
                                    <option value="billing_date" <?php echo $sort === 'billing_date' ? 'selected' : ''; ?>>Sort by Date</option>
                                    <option value="total_amount" <?php echo $sort === 'total_amount' ? 'selected' : ''; ?>>Sort by Amount</option>
                                    <option value="due_date" <?php echo $sort === 'due_date' ? 'selected' : ''; ?>>Sort by Due Date</option>
                                </select>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Invoices Table -->
                <div class="card">
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice #</th>
                                        <th>Client</th>
                                        <th>Subscription</th>
                                        <th>Amount</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($invoicesData['data'])): ?>
                                        <tr>
                                            <td colspan="7" class="text-center">No invoices found.</td>
                                        </tr>
                                    <?php else: ?>
                                        <?php foreach ($invoicesData['data'] as $invoice): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                                <td>
                                                    <?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?>
                                                    <br>
                                                    <small class="text-muted"><?php echo htmlspecialchars($invoice['email']); ?></small>
                                                </td>
                                                <td>
                                                    <?php echo htmlspecialchars($invoice['subscription_number']); ?>
                                                </td>
                                                <td>
                                                    $<?php echo number_format($invoice['total_amount'], 2); ?>
                                                </td>
                                                <td>
                                                    <?php echo date('M d, Y', strtotime($invoice['due_date'])); ?>
                                                    <br>
                                                    <small class="text-muted">
                                                        <?php
                                                        $daysUntilDue = (strtotime($invoice['due_date']) - time()) / (60 * 60 * 24);
                                                        if ($daysUntilDue < 0) {
                                                            echo '<span class="text-danger">Overdue by ' . abs(round($daysUntilDue)) . ' days</span>';
                                                        } else {
                                                            echo round($daysUntilDue) . ' days left';
                                                        }
                                                        ?>
                                                    </small>
                                                </td>
                                                <td>
                                                    <div class="d-flex align-items-center">
                                                        <?php 
                                                        $statusClass = '';
                                                        switch($invoice['status']) {
                                                            case 'pending': $statusClass = 'warning'; break;
                                                            case 'paid': $statusClass = 'success'; break;
                                                            case 'overdue': $statusClass = 'danger'; break;
                                                            case 'cancelled': $statusClass = 'secondary'; break;
                                                            default: $statusClass = 'info';
                                                        }
                                                        ?>
                                                        <span class="bg-<?php echo $statusClass; ?>" 
                                                             style="width: 10px; height: 10px; border-radius: 50%; margin-right: 5px;"></span>
                                                        <?php echo ucfirst($invoice['status']); ?>
                                                    </div>
                                                </td>
                                                <td>
                                                    <div class="btn-group">
                                                        <button type="button" class="btn btn-sm btn-outline-primary" 
                                                                onclick="viewInvoice(<?php echo $invoice['id']; ?>)">
                                                            <i class="fas fa-eye"></i>
                                                        </button>
                                                        <button type="button" class="btn btn-sm btn-outline-success"
                                                                onclick="recordPayment(<?php echo $invoice['id']; ?>)">
                                                            <i class="fas fa-dollar-sign"></i>
                                                        </button>
                                                        <?php if ($invoice['status'] !== 'paid'): ?>
                                                            <button type="button" class="btn btn-sm btn-outline-secondary"
                                                                    onclick="editInvoice(<?php echo $invoice['id']; ?>)">
                                                                <i class="fas fa-edit"></i>
                                                            </button>
                                                            <button type="button" class="btn btn-sm btn-outline-danger"
                                                                    onclick="deleteInvoice(<?php echo $invoice['id']; ?>)">
                                                                <i class="fas fa-trash"></i>
                                                            </button>
                                                        <?php endif; ?>
                                                    </div>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    <?php endif; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <?php if ($invoicesData['total_pages'] > 1): ?>
                            <nav aria-label="Page navigation" class="mt-4">
                                <ul class="pagination justify-content-center">
                                    <?php for ($i = 1; $i <= $invoicesData['total_pages']; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>&search=<?php echo urlencode($search); ?>&status=<?php echo urlencode($status); ?>&sort=<?php echo urlencode($sort); ?>&order=<?php echo urlencode($order); ?>">
                                                <?php echo $i; ?>
                                            </a>
                                        </li>
                                    <?php endfor; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- View Invoice Modal -->
    <div class="modal fade" id="viewInvoiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Invoice Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="invoiceDetails"></div>
                    <hr>
                    <h6>Payments</h6>
                    <div id="paymentsList"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printInvoice()">
                        <i class="fas fa-print me-2"></i>Print Invoice
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Record Payment Modal -->
    <div class="modal fade" id="paymentModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Record Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="paymentForm">
                        <input type="hidden" id="billingId" name="billing_id">
                        <div class="mb-3">
                            <label class="form-label">Amount</label>
                            <div class="input-group">
                                <span class="input-group-text">$</span>
                                <input type="number" class="form-control" id="amount" name="amount" step="0.01" required>
                            </div>
                         </div>
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="notes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="savePayment()">Record Payment</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Edit Invoice Modal -->
    <div class="modal fade" id="editInvoiceModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Edit Invoice</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="editInvoiceForm">
                        <input type="hidden" id="editInvoiceId" name="invoice_id">
                        
                        <div class="mb-3">
                            <label class="form-label">Notes</label>
                            <textarea class="form-control" id="editNotes" name="notes" rows="3"></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="saveInvoiceChanges()">Save Changes</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Add missing Generate Invoices Modal -->
    <div class="modal fade" id="generateInvoicesModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Generate Monthly Invoices</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <p>This will generate invoices for all active subscriptions that don't have an invoice for the current month.</p>
                    <p class="text-warning"><i class="fas fa-exclamation-triangle me-1"></i> This action should normally be run only once per billing cycle.</p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-primary" onclick="generateInvoices()">
                        <i class="fas fa-file-invoice me-1"></i> Generate Invoices
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Bootstrap and other JS libraries -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/sidebar.js"></script>
    
    <!-- Mobile Toggle Button -->
    <button type="button" id="sidebarToggle" class="btn btn-link d-md-none position-fixed" style="top: 1rem; left: 1rem; z-index: 1040;">
        <i class="fas fa-bars"></i>
    </button>
    
    <!-- Existing script block -->
    <script>
        window.testVar = 'test value';
        window.testFunc = function() {
            alert('Test function called!');
        };

        // Updated viewInvoice() function with simplified layout
        function viewInvoice(invoiceId) {
            console.log("Viewing invoice ID:", invoiceId);
            
            // Mock data for the invoice since the API endpoint doesn't exist yet
            const invoice = {
                id: invoiceId,
                invoice_number: `INV-${String(invoiceId).padStart(5, '0')}`,
                client_name: "John Smith",
                client_email: "john.smith@example.com",
                client_address: "123 Main St, Anytown, ST 12345",
                client_phone: "(555) 123-4567",
                subscription_number: "SUB-00123",
                billing_date: "2023-05-01",
                due_date: "2023-05-15",
                period_start: "2023-05-01",
                period_end: "2023-05-31",
                quantity: 1,
                unit_price: 89.99,
                discount: 0,
                amount: 89.99,
                total_amount: 89.99,
                status: "pending",
                notes: "Monthly subscription fee"
            };
            
            // Company information for Bill From section
            const company = {
                name: "ISP Management System",
                address: "456 Network Ave",
                city: "Tech City, ST 54321",
                email: "billing@ispmanagementsystem.com",
                phone: "(555) 987-6543",
                website: "www.ispmanagementsystem.com"
            };
            
            // Mock payment data
            const payments = [
                {
                    id: 101,
                    payment_date: "2023-05-10",
                    amount: 50.00,
                    payment_method: "Credit Card",
                    transaction_id: "TX-12345678",
                    status: "completed"
                },
                {
                    id: 102,
                    payment_date: "2023-05-12",
                    amount: 39.99,
                    payment_method: "Bank Transfer",
                    transaction_id: "BT-87654321",
                    status: "completed"
                }
            ];
            
            // Populate invoice details in the modal
            let invoiceDetailsHtml = `
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between">
                        <h5>Invoice #${invoice.invoice_number}</h5>
                        <span class="badge ${getBadgeClass(invoice.status)}">${invoice.status.toUpperCase()}</span>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6>Bill From</h6>
                                <p>
                                    <strong>${company.name}</strong><br>
                                    ${company.address}<br>
                                    ${company.city}<br>
                                    ${company.email}<br>
                                    ${company.phone}
                                </p>
                            </div>
                            <div class="col-md-6">
                                <h6>Bill To</h6>
                                <p>
                                    <strong>${invoice.client_name}</strong><br>
                                    ${invoice.client_address}<br>
                                    ${invoice.client_email}<br>
                                    ${invoice.client_phone}
                                </p>
                            </div>
                        </div>
                        
                        <div class="row mb-4">
                            <div class="col-md-12">
                                <div class="card bg-light">
                                    <div class="card-body d-flex justify-content-between align-items-center">
                                        <div>
                                            <p class="mb-1"><strong>Invoice Date:</strong> ${formatDate(invoice.billing_date)}</p>
                                            <p class="mb-1"><strong>Due Date:</strong> ${formatDate(invoice.due_date)}</p>
                                        </div>
                                        <div class="text-end">
                                            <h4 class="mb-1">Total Due</h4>
                                            <h3 class="mb-0">$${invoice.total_amount.toFixed(2)}</h3>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            <div class="col-md-12">
                                <h6>Invoice Details</h6>
                                <table class="table table-bordered">
                                    <thead>
                                        <tr>
                                            <th>Description</th>
                                            <th class="text-end">Amount</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td>
                                                Subscription Fee
                                                <br>
                                                <small class="text-muted">Subscription #: ${invoice.subscription_number}</small>
                                            </td>
                                            <td class="text-end">$${invoice.amount.toFixed(2)}</td>
                                        </tr>
                                    </tbody>
                                    <tfoot>
                                        <tr>
                                            <th class="text-end">Total:</th>
                                            <th class="text-end">$${invoice.total_amount.toFixed(2)}</th>
                                        </tr>
                                    </tfoot>
                                </table>
                            </div>
                        </div>
                        
                        <div class="row">
                            <div class="col-md-12">
                                <h6>Notes</h6>
                                <p>${invoice.notes || 'No notes provided'}</p>
                            </div>
                        </div>
                    </div>
                </div>
            `;
            
            // Populate payments in the modal
            let paymentsHtml = '';
            if (payments.length > 0) {
                paymentsHtml = `
                    <table class="table table-sm">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Amount</th>
                                <th>Method</th>
                                <th>Transaction ID</th>
                                <th>Status</th>
                            </tr>
                        </thead>
                        <tbody>
                `;
                
                // Calculate total payments
                const totalPaid = payments.reduce((sum, payment) => sum + payment.amount, 0);
                const remainingBalance = invoice.total_amount - totalPaid;
                
                payments.forEach(payment => {
                    paymentsHtml += `
                        <tr>
                            <td>${formatDate(payment.payment_date)}</td>
                            <td>$${payment.amount.toFixed(2)}</td>
                            <td>${payment.payment_method}</td>
                            <td>${payment.transaction_id}</td>
                            <td><span class="badge ${payment.status === 'completed' ? 'bg-success' : 'bg-warning'}">${payment.status}</span></td>
                        </tr>
                    `;
                });
                
                // Add summary row
                paymentsHtml += `
                    </tbody>
                    <tfoot>
                        <tr>
                            <th colspan="1" class="text-end">Total Paid:</th>
                            <th>$${totalPaid.toFixed(2)}</th>
                            <th colspan="1" class="text-end">Remaining:</th>
                            <th colspan="2">$${remainingBalance.toFixed(2)}</th>
                        </tr>
                    </tfoot>
                </table>
                `;
