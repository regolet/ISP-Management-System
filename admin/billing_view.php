<?php
require_once '../config.php';
check_auth();

if (!isset($_GET['id'])) {
    $_SESSION['error'] = "No billing ID provided";
    header('Location: billing.php');
    exit();
}

$billing_id = $_GET['id'];

// Get company settings
$settings_stmt = $conn->prepare("
    SELECT name, value 
    FROM settings 
    WHERE category = 'company' 
    AND name IN ('company_name', 'company_email', 'company_phone', 'company_website', 'company_address', 'tax_rate', 'currency')
");
$settings_stmt->execute();
$settings_result = $settings_stmt->get_result();

// Convert settings to associative array
$company_settings = [
    'company_name' => 'Your Company Name',
    'company_email' => '',
    'company_phone' => '',
    'company_website' => '',
    'company_address' => '',
    'tax_rate' => '0',
    'currency' => 'PHP'
];

while ($row = $settings_result->fetch_assoc()) {
    $company_settings[$row['name']] = $row['value'];
}

// Get billing information with total amount and customer details
$stmt = $conn->prepare("
    SELECT b.*, 
           c.name as customer_name,
           c.address as customer_address,
           c.contact_number as customer_contact,
           c.email as customer_email
    FROM billing b
    LEFT JOIN customers c ON b.customer_id = c.id
    WHERE b.id = ?
");

if (!$stmt) {
    error_log("Error preparing billing statement: " . $conn->error);
    $_SESSION['error'] = "System error occurred";
    header('Location: billing.php');
    exit();
}

$stmt->bind_param("i", $billing_id);
$stmt->execute();
$result = $stmt->get_result();
$billing = $result->fetch_assoc();

if (!$billing) {
    $_SESSION['error'] = "Billing not found";
    header('Location: billing.php');
    exit();
}

// Get billing items
$items_stmt = $conn->prepare("
    SELECT * FROM billingitems 
    WHERE billingid = ?
    ORDER BY id ASC
");

if (!$items_stmt) {
    error_log("Error preparing billing items statement: " . $conn->error);
    $_SESSION['error'] = "System error occurred";
    header('Location: billing.php');
    exit();
}

$items_stmt->bind_param("i", $billing_id);
$items_stmt->execute();
$billing_items = $items_stmt->get_result();

// Get payments with payment method names
$payments_query = $conn->prepare("
    SELECT p.*, 
           pm.name as payment_method_name
    FROM payments p
    LEFT JOIN payment_methods pm ON p.payment_method_id = pm.id
    WHERE p.billing_id = ?
    ORDER BY p.payment_date DESC
");

if (!$payments_query) {
    error_log("Error preparing payments query: " . $conn->error);
    $_SESSION['error'] = "System error occurred";
    header('Location: billing.php');
    exit();
}

$payments_query->bind_param("i", $billing_id);
$payments_query->execute();
$payments = $payments_query->get_result();

// Calculate totals
$total_paid = 0;
$payment_rows = [];
while ($payment = $payments->fetch_assoc()) {
    if ($payment['status'] == 'completed') {
        $total_paid += $payment['amount'];
    }
    $payment_rows[] = $payment;
}

// Calculate subtotal from billing items
$subtotal = 0;
$billing_items_array = [];
while ($item = $billing_items->fetch_assoc()) {
    $subtotal += $item['totalprice'];
    $billing_items_array[] = $item;
}

$total_amount = $subtotal + ($billing['late_fee'] ?? 0);
$remaining_balance = $total_amount - $total_paid;

// Set active menu for navbar
$_SESSION['active_menu'] = 'billing';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Billing View - <?php echo htmlspecialchars($billing['invoiceid']); ?></title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- BoxIcons -->
    <link href='https://unpkg.com/boxicons@2.1.4/css/boxicons.min.css' rel='stylesheet'>
    <style>
        @media print {
            /* Basic Reset */
            body {
                margin: 0;
                padding: 0;
                font-size: 10pt !important;
                line-height: 1.2;
                background: white;
            }
            
            /* Hide Non-Printable Elements */
            .no-print, nav, .navbar, footer {
                display: none !important;
            }
            
            /* Container Adjustments */
            .content-wrapper {
                margin: 0 !important;
                padding: 10mm !important;
                width: 100% !important;
            }
            
            /* Page Settings */
            @page {
                size: A4;
                margin: 10mm;
            }
            
            /* Compact Layout */
            .billing-info-group {
                padding: 3mm !important;
                margin-bottom: 5mm !important;
            }
            
            .info-grid {
                display: grid !important;
                grid-template-columns: repeat(3, 1fr) !important;
                gap: 3mm !important;
            }
            
            .table {
                font-size: 9pt !important;
                margin-bottom: 5mm !important;
            }
            
            .table th, .table td {
                padding: 2mm !important;
            }
            
            .card {
                border: none !important;
                margin-bottom: 5mm !important;
            }
            
            .card-body {
                padding: 3mm !important;
            }
            
            /* Font Sizes */
            .h2 { 
                font-size: 14pt !important; 
            }
            
            .small { 
                font-size: 8pt !important; 
            }
            
            .billing-info-group,
            .table,
            .card {
                page-break-inside: avoid !important;
            }
            
            /* Hide payment history in print */
            .payment-history {
                display: none !important;
            }
        }
        /* Remove conflicting content-wrapper styles and use navbar's spacing */
        .content-wrapper {
            padding: 20px;
        }
        .invoice-header {
            border-bottom: 2px solid #dee2e6;
            margin-bottom: 2rem;
            padding-bottom: 1rem;
        }
        .company-details, .customer-details {
            padding: 1.5rem;
            background: #f8f9fa;
            border-radius: 0.25rem;
            height: 100%;
        }
        .billing-dates {
            background: #fff;
            border: 1px solid #dee2e6;
            border-radius: 0.25rem;
            padding: 1rem;
            margin-bottom: 1.5rem;
        }
        .table-billing th {
            background: #f8f9fa;
            border-bottom: 2px solid #dee2e6;
        }
        .payment-methods-card {
            position: sticky;
            top: 20px;
        }
        .billing-info-group {
            background: #f8f9fa;
            border-radius: 0.25rem;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
        }
        .info-grid {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 1.5rem;
        }
        .payment-section {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1.5rem;
            margin-top: 2rem;
        }
        @media (max-width: 768px) {
            .info-grid {
                grid-template-columns: repeat(1, 1fr);
            }
            .payment-section {
                grid-template-columns: 1fr;
            }
        }
    </style>
</head>
<body>
    <?php include 'navbar.php'; ?>
    <?php include 'alerts.php'; ?>

    <div class="content-wrapper">
        <!-- Set active menu for navbar -->
        <?php $_SESSION['active_menu'] = 'billing'; ?>
        <div class="container-fluid">
            <!-- Header Section -->
            <div class="page-header d-flex justify-content-between align-items-center">
                <h1 class="h2">Invoice #<?php echo htmlspecialchars($billing['invoiceid']); ?></h1>
                <div class="d-flex align-items-center gap-2">
                    <span class="badge bg-<?php 
                        echo $billing['status'] == 'paid' ? 'success' : 
                            ($billing['status'] == 'partial' ? 'warning' : 
                            ($billing['status'] == 'overdue' ? 'danger' : 'light')); 
                    ?>">
                        <?php echo ucfirst($billing['status']); ?>
                    </span>
                    <div class="btn-group no-print">
                        <a href="billing.php" class="btn btn-sm btn-outline-secondary">Back</a>
                        <button type="button" class="btn btn-sm btn-outline-primary" onclick="optimizedPrint()">
                            <i class="bx bx-printer"></i> Print
                        </button>
                    </div>
                </div>
            </div>

            <!-- Billing Information Group -->
            <div class="billing-info-group">
                <div class="info-grid">
                    <div>
                        <h6 class="text-muted mb-2">Bill From</h6>
                        <p class="mb-0">
                            <strong><?php echo htmlspecialchars($company_settings['company_name']); ?></strong><br>
                            <?php echo nl2br(htmlspecialchars($company_settings['company_address'])); ?><br>
                            <?php if (!empty($company_settings['company_phone'])): ?>
                                Phone: <?php echo htmlspecialchars($company_settings['company_phone']); ?><br>
                            <?php endif; ?>
                            <?php if (!empty($company_settings['company_email'])): ?>
                                Email: <?php echo htmlspecialchars($company_settings['company_email']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <h6 class="text-muted mb-2">Bill To</h6>
                        <p class="mb-0">
                            <strong><?php echo htmlspecialchars($billing['customer_name']); ?></strong><br>
                            <?php echo nl2br(htmlspecialchars($billing['customer_address'])); ?><br>
                            <?php if (!empty($billing['customer_contact'])): ?>
                                Phone: <?php echo htmlspecialchars($billing['customer_contact']); ?><br>
                            <?php endif; ?>
                            <?php if (!empty($billing['customer_email'])): ?>
                                Email: <?php echo htmlspecialchars($billing['customer_email']); ?>
                            <?php endif; ?>
                        </p>
                    </div>
                    <div>
                        <h6 class="text-muted mb-2">Invoice Details</h6>
                        <p class="mb-1">Invoice Date: <?php echo date('F d, Y', strtotime($billing['created_at'])); ?></p>
                        <p class="mb-0">Due Date: <?php echo date('F d, Y', strtotime($billing['due_date'])); ?></p>
                    </div>
                </div>
            </div>

            <!-- Billing Details -->
            <div class="card mb-4">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered">
                            <thead>
                                <tr>
                                    <th style="width: 50%;">Description</th>
                                    <th style="width: 15%;">Qty</th>
                                    <th style="width: 15%;">Price</th>
                                    <th style="width: 20%;">Total</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($billing_items_array as $item): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($item['itemdescription']); ?></td>
                                    <td class="text-center"><?php echo $item['qty']; ?></td>
                                    <td class="text-end">₱<?php echo number_format($item['price'], 2); ?></td>
                                    <td class="text-end">₱<?php echo number_format($item['totalprice'], 2); ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Subtotal:</strong></td>
                                    <td class="text-end">₱<?php echo number_format($subtotal, 2); ?></td>
                                </tr>
                                <?php if (!empty($billing['late_fee']) && $billing['late_fee'] > 0): ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Late Fee:</strong></td>
                                    <td class="text-end">₱<?php echo number_format($billing['late_fee'], 2); ?></td>
                                </tr>
                                <?php endif; ?>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Total Amount:</strong></td>
                                    <td class="text-end">₱<?php echo number_format($total_amount, 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Amount Paid:</strong></td>
                                    <td class="text-end text-success">₱<?php echo number_format($total_paid, 2); ?></td>
                                </tr>
                                <tr>
                                    <td colspan="3" class="text-end"><strong>Balance Due:</strong></td>
                                    <td class="text-end text-danger">₱<?php echo number_format($remaining_balance, 2); ?></td>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>

            <!-- Payment Information and History Section -->
            <div class="payment-section">
                <!-- Payment Methods -->
                <div class="card">
                    <div class="card-header">
                        <h5 class="mb-0">Payment Information</h5>
                    </div>
                    <div class="card-body">
                        <h6>Payment Methods:</h6>
                        <div class="table-responsive">
                            <table class="table table-bordered">
                                <thead>
                                    <tr>
                                        <th>Method</th>
                                        <th>Details</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr>
                                        <td><strong>Bank Transfer</strong></td>
                                        <td>
                                            Bank: Sample Bank<br>
                                            Account Name: <?php echo htmlspecialchars($company_settings['company_name']); ?><br>
                                            Account Number: XXX-XXXX-XXXX
                                        </td>
                                    </tr>
                                    <tr>
                                        <td><strong>GCash</strong></td>
                                        <td>
                                            Account Name: <?php echo htmlspecialchars($company_settings['company_name']); ?><br>
                                            Number: 09XX-XXX-XXXX
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                        <div class="alert alert-info mt-3">
                            <i class='bx bx-info-circle'></i> Please include your Invoice Number (<?php echo htmlspecialchars($billing['invoiceid']); ?>) when making a payment.
                        </div>
                    </div>
                </div>

                <!-- Payment History -->
                <div class="card payment-history">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="mb-0">Payment History</h5>
                        <?php if ($billing['status'] !== 'paid'): ?>
                        <a href="billing_payment_form.php?id=<?php echo $billing['id']; ?>" class="btn btn-primary btn-sm no-print">
                            <i class="bx bx-plus"></i> Add Payment
                        </a>
                        <?php endif; ?>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-striped mb-0">
                                <thead>
                                    <tr>
                                        <th>Date</th>
                                        <th>Amount</th>
                                        <th>Method</th>
                                        <th>Reference</th>
                                        <th>Status</th>
                                        <th>Notes</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    if (!empty($payment_rows)):
                                        foreach ($payment_rows as $payment):
                                    ?>
                                    <tr>
                                        <td><?php echo date('M d, Y', strtotime($payment['payment_date'])); ?></td>
                                        <td>₱<?php echo number_format($payment['amount'], 2); ?></td>
                                        <td><?php echo htmlspecialchars($payment['payment_method_name'] ?? 'N/A'); ?></td>
                                        <td><?php echo htmlspecialchars($payment['reference_no'] ?? ''); ?></td>
                                        <td>
                                            <span class="badge bg-<?php 
                                                echo $payment['status'] == 'completed' ? 'success' : 
                                                    ($payment['status'] == 'pending' ? 'warning' : 'danger'); 
                                            ?>">
                                                <?php echo ucfirst($payment['status']); ?>
                                            </span>
                                        </td>
                                        <td><?php echo htmlspecialchars($payment['notes'] ?? ''); ?></td>
                                    </tr>
                                    <?php 
                                        endforeach;
                                    else:
                                    ?>
                                    <tr>
                                        <td colspan="6" class="text-center">No payments recorded</td>
                                    </tr>
                                    <?php endif; ?>
                                </tbody>
                                <tfoot>
                                    <tr class="table-active">
                                        <td><strong>Summary</strong></td>
                                        <td colspan="5">
                                            <strong>Total Amount: </strong>₱<?php echo number_format($total_amount, 2); ?><br>
                                            <strong>Total Paid: </strong>₱<?php echo number_format($total_paid, 2); ?><br>
                                            <strong>Balance: </strong>₱<?php echo number_format($remaining_balance, 2); ?>
                                        </td>
                                    </tr>
                                </tfoot>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <?php if (!empty($billing['notes'])): ?>
            <div class="card mt-4">
                <div class="card-body bg-light">
                    <h6 class="text-muted mb-2">Notes:</h6>
                    <?php echo nl2br(htmlspecialchars($billing['notes'])); ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Bootstrap Bundle with Popper -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Updated print function to handle payment history visibility
        function optimizedPrint() {
            // Show payment history when printing
            document.querySelectorAll('.payment-section').forEach(function(section) {
                section.style.display = 'block';
            });
            window.print();
        }
    </script>
</body>
</html>