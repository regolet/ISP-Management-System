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
$sort = $_GET['sort'] ?? 'payment_date';
$order = $_GET['order'] ?? 'DESC';

// Get payments data
$paymentsData = $billingController->getPayments([
    'page' => $page,
    'per_page' => 10,
    'search' => $search,
    'status' => $status,
    'sort' => $sort,
    'order' => $order
]);

// Get billing statistics
$stats = $billingController->getBillingStats();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Payment History - ISP Management System</title>
    
    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    
    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Render Sidebar -->
    <?php renderSidebar('payments'); ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <div class="main-content p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Payment History</h1>
            </div>
            
            <!-- Display Messages -->
            <?php if (isset($_SESSION['success'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <i class="fas fa-check-circle me-2"></i>
                    <?php echo $_SESSION['success']; unset($_SESSION['success']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <i class="fas fa-exclamation-circle me-2"></i>
                    <?php echo $_SESSION['error']; unset($_SESSION['error']); ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form id="filterForm" class="row g-3">
                        <div class="col-md-6">
                            <div class="search-box">
                                <input type="text" class="form-control" id="search" name="search" 
                                       placeholder="Search payments..." value="<?php echo htmlspecialchars($search); ?>">
                                <?php if (!empty($search)): ?>
                                    <span class="clear-search" onclick="clearSearch()">×</span>
                                <?php endif; ?>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="status" onchange="this.form.submit()">
                                <option value="">All Status</option>
                                <option value="completed" <?php echo $status === 'completed' ? 'selected' : ''; ?>>Completed</option>
                                <option value="pending" <?php echo $status === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                <option value="failed" <?php echo $status === 'failed' ? 'selected' : ''; ?>>Failed</option>
                                <option value="refunded" <?php echo $status === 'refunded' ? 'selected' : ''; ?>>Refunded</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" name="sort" onchange="this.form.submit()">
                                <option value="payment_date" <?php echo $sort === 'payment_date' ? 'selected' : ''; ?>>Sort by Date</option>
                                <option value="amount" <?php echo $sort === 'amount' ? 'selected' : ''; ?>>Sort by Amount</option>
                                <option value="payment_method" <?php echo $sort === 'payment_method' ? 'selected' : ''; ?>>Sort by Method</option>
                            </select>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Payments Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Payment #</th>
                                    <th>Invoice #</th>
                                    <th>Client</th>
                                    <th>Method</th>
                                    <th>Amount</th>
                                    <th>Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($paymentsData['data'] as $payment): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($payment['payment_number']); ?></td>
                                        <td>
                                            <a href="#" onclick="viewInvoice(<?php echo $payment['billing_id']; ?>)">
                                                <?php echo htmlspecialchars($payment['invoice_number']); ?>
                                            </a>
                                        </td>
                                        <td>
                                            <?php echo htmlspecialchars($payment['first_name'] . ' ' . $payment['last_name']); ?>
                                            <br>
                                            <small class="text-muted"><?php echo htmlspecialchars($payment['email']); ?></small>
                                        </td>
                                        <td>
                                            <?php
                                            $methodIcon = [
                                                'cash' => 'money-bill-wave',
                                                'credit_card' => 'credit-card',
                                                'bank_transfer' => 'university',
                                                'online' => 'globe'
                                            ][$payment['payment_method']];
                                            ?>
                                            <i class="fas fa-<?php echo $methodIcon; ?> payment-method-icon"></i>
                                            <?php echo ucwords(str_replace('_', ' ', $payment['payment_method'])); ?>
                                            <?php if ($payment['transaction_id']): ?>
                                                <br>
                                                <small class="text-muted">Ref: <?php echo htmlspecialchars($payment['transaction_id']); ?></small>
                                            <?php endif; ?>
                                        </td>
                                        <td>$<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td>
                                            <?php echo date('M d, Y', strtotime($payment['payment_date'])); ?>
                                            <br>
                                            <small class="text-muted"><?php echo date('h:i A', strtotime($payment['payment_date'])); ?></small>
                                        </td>
                                        <td>
                                            <span class="payment-status status-<?php echo $payment['status']; ?>"></span>
                                            <?php echo ucfirst($payment['status']); ?>
                                        </td>
                                        <td>
                                            <div class="btn-group">
                                                <button type="button" class="btn btn-sm btn-outline-primary" 
                                                        onclick="viewPayment(<?php echo $payment['id']; ?>)">
                                                    <i class="fas fa-eye"></i>
                                                </button>
                                                <?php if ($payment['status'] === 'pending'): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-success"
                                                            onclick="updatePaymentStatus(<?php echo $payment['id']; ?>, 'completed')">
                                                        <i class="fas fa-check"></i>
                                                    </button>
                                                    <button type="button" class="btn btn-sm btn-outline-danger"
                                                            onclick="updatePaymentStatus(<?php echo $payment['id']; ?>, 'failed')">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($payment['status'] === 'completed' && $auth->hasRole('admin')): ?>
                                                    <button type="button" class="btn btn-sm btn-outline-warning"
                                                            onclick="refundPayment(<?php echo $payment['id']; ?>)">
                                                        <i class="fas fa-undo"></i>
                                                    </button>
                                                <?php endif; ?>
                                                <?php if ($auth->hasRole('admin')): ?>
                                                    <a href="/forms/payments/delete.php?id=<?php echo $payment['id']; ?>" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                <?php endif; ?>
                                            </div>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($paymentsData['total_pages'] > 1): ?>
                        <nav aria-label="Page navigation" class="mt-4">
                            <ul class="pagination justify-content-center">
                                <?php for ($i = 1; $i <= $paymentsData['total_pages']; $i++): ?>
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
        </div>
    </div>

    <!-- View Payment Modal -->
    <div class="modal fade" id="viewPaymentModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Payment Details</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <div id="paymentDetails"></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                    <button type="button" class="btn btn-primary" onclick="printReceipt()">
                        <i class="fas fa-print me-2"></i>Print Receipt
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Refund Payment Modal -->
    <div class="modal fade" id="refundModal" tabindex="-1">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Refund Payment</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body">
                    <form id="refundForm">
                        <input type="hidden" id="paymentId" name="payment_id">
                        <div class="mb-3">
                            <label class="form-label">Refund Reason</label>
                            <textarea class="form-control" id="refundReason" name="reason" rows="3" required></textarea>
                        </div>
                    </form>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="button" class="btn btn-warning" onclick="confirmRefund()">Process Refund</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="/assets/js/sidebar.js"></script>
    
    <!-- Mobile Toggle Button -->
    <button type="button" id="sidebarToggle" class="btn btn-link d-md-none position-fixed" style="top: 1rem; left: 1rem; z-index: 1040;">
        <i class="fas fa-bars"></i>
    </button>
    <script>
    // Add formatDate function
    function formatDate(dateString) {
        const date = new Date(dateString);
        return date.toLocaleDateString('en-US', { 
            year: 'numeric', 
            month: 'short', 
            day: 'numeric' 
        });
    }

    /**
     * View payment details
     */
    function viewPayment(paymentId) {
        console.log("Viewing payment ID:", paymentId);
        
        // Fetch payment details from API
        fetch(`/api/payments.php?id=${paymentId}`)
            .then(response => response.json())
            .then(data => {
                if (data.success && data.payment) {
                    const payment = data.payment;
                    
                    // Populate payment details in the modal
                    let paymentDetailsHtml = `
                        <div class="card">
                            <div class="card-header d-flex justify-content-between">
                                <h5>Payment #${payment.payment_number}</h5>
                                <span class="badge ${getStatusBadgeClass(payment.status)}">${payment.status.toUpperCase()}</span>
                            </div>
                            <div class="card-body">
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <p><strong>Amount:</strong> $${parseFloat(payment.amount).toFixed(2)}</p>
                                        <p><strong>Payment Date:</strong> ${formatDate(payment.payment_date)}</p>
                                        <p><strong>Payment Method:</strong> ${payment.payment_method.replace('_', ' ')}</p>
                                        <p><strong>Transaction ID:</strong> ${payment.transaction_id || 'N/A'}</p>
                                    </div>
                                    <div class="col-md-6">
                                        <p><strong>Invoice Number:</strong> ${payment.invoice_number}</p>
                                        <p><strong>Client:</strong> ${payment.first_name} ${payment.last_name}</p>
                                        <p><strong>Status:</strong> ${payment.status}</p>
                                    </div>
                                </div>
                                
                                <div class="row">
                                    <div class="col-md-12">
                                        <h6>Notes</h6>
                                        <p>${payment.notes || 'No notes provided'}</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Update modal content
                    document.getElementById('paymentDetails').innerHTML = paymentDetailsHtml;
                    
                    // Show modal
                    var viewModal = new bootstrap.Modal(document.getElementById('viewPaymentModal'));
                    viewModal.show();
                } else {
                    alert('Failed to load payment details');
                }
            })
            .catch(error => {
                console.error('Error fetching payment details:', error);
                alert('An error occurred while loading payment details');
            });
    }

    // Helper function for status badge classes
    function getStatusBadgeClass(status) {
        switch(status) {
            case 'pending': return 'bg-warning text-dark';
            case 'completed': return 'bg-success';
            case 'failed': return 'bg-danger';
            case 'refunded': return 'bg-info';
            default: return 'bg-secondary';
        }
    }

    /**
     * Print payment receipt
     */
    function printReceipt() {
        console.log("Printing receipt...");
        
        // Get the payment details content
        var receiptContent = document.getElementById('paymentDetails').innerHTML;
        
        // Open a new window for printing
        var printWindow = window.open('', '_blank');
        printWindow.document.write('<html><head><title>Payment Receipt</title>');
        printWindow.document.write('<link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">');
        printWindow.document.write('<style>body { padding: 20px; } .card { border: none; } @media print { .no-print { display: none; } }</style>');
        printWindow.document.write('</head><body>');
        printWindow.document.write('<div class="container">');
        printWindow.document.write('<h1 class="mb-4">Payment Receipt</h1>');
        printWindow.document.write('<div class="mb-4">');
        printWindow.document.write('<p><strong>Date:</strong> ' + new Date().toLocaleDateString() + '</p>');
        printWindow.document.write('</div>');
        printWindow.document.write(receiptContent);
        printWindow.document.write('<div class="mt-5 pt-5">');
        printWindow.document.write('<p class="text-center">Thank you for your payment!</p>');
        printWindow.document.write('</div>');
        printWindow.document.write('</div>');
        printWindow.document.write('</body></html>');
        printWindow.document.close();
        
        // Delay to ensure content is loaded
        setTimeout(function() {
            printWindow.print();
        }, 500);
    }

    // Function to view invoice details
    function viewInvoice(invoiceId) {
        // Redirect to the invoice details page
        window.location.href = `billing.php?invoice=${invoiceId}`;
    }
    
    /**
     * Update payment status
     */
    function updatePaymentStatus(paymentId, status) {
        if (!confirm(`Are you sure you want to mark this payment as ${status}?`)) {
            return;
        }
        
        // Send update request to API
        fetch(`/api/payments.php?id=${paymentId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                status: status
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Payment status updated successfully');
                location.reload();
            } else {
                alert('Failed to update payment status: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error updating payment status:', error);
            alert('An error occurred while updating payment status');
        });
    }
    
    /**
     * Refund payment
     */
    function refundPayment(paymentId) {
        // Set payment ID in the refund form
        document.getElementById('paymentId').value = paymentId;
        
        // Show refund modal
        var refundModal = new bootstrap.Modal(document.getElementById('refundModal'));
        refundModal.show();
    }
    
    /**
     * Confirm refund
     */
    function confirmRefund() {
        const paymentId = document.getElementById('paymentId').value;
        const reason = document.getElementById('refundReason').value;
        
        if (!reason.trim()) {
            alert('Please provide a reason for the refund');
            return;
        }
        
        // Send refund request to API
        fetch(`/api/payments.php?id=${paymentId}`, {
            method: 'PUT',
            headers: {
                'Content-Type': 'application/json',
            },
            body: JSON.stringify({
                status: 'refunded',
                notes: reason
            })
        })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                alert('Payment refunded successfully');
                location.reload();
            } else {
                alert('Failed to refund payment: ' + (data.message || 'Unknown error'));
            }
        })
        .catch(error => {
            console.error('Error refunding payment:', error);
            alert('An error occurred while processing refund');
        });
    }
    
    /**
     * Clear search
     */
    function clearSearch() {
        document.getElementById('search').value = '';
        document.getElementById('filterForm').submit();
    }
    </script>
</body>
</html>
