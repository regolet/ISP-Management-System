<?php
require_once '../../app/init.php';
require_once '../../app/Controllers/InvoiceController.php';

use App\Controllers\InvoiceController;

// Database connection
$db = new PDO("sqlite:../../database/isp-management.sqlite");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialize Invoice Controller
$invoiceController = new InvoiceController($db);

// Get invoice ID from the query string
$invoice_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Get invoice data
$invoice = $invoiceController->getInvoiceById($invoice_id);

// If invoice not found, redirect to invoices page
if (!$invoice) {
    header("Location: /invoices.php");
    exit();
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Invoice - ISP Management System</title>
    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">
    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <div class="dashboard-container">
        <?php require_once '../../../views/layouts/sidebar.php'; renderSidebar('invoices'); ?>
        <div class="main-content p-4">
            <h2>Edit Invoice</h2>
            <form action="" method="POST">
                <div class="mb-3">
                    <label for="invoice_number" class="form-label">Invoice Number</label>
                    <input type="text" class="form-control" id="invoice_number" name="invoice_number" value="<?php echo htmlspecialchars($invoice['invoice_number']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="client_id" class="form-label">Client ID</label>
                    <input type="text" class="form-control" id="client_id" name="client_id" value="<?php echo htmlspecialchars($invoice['client_id']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="total_amount" class="form-label">Total Amount</label>
                    <input type="number" class="form-control" id="total_amount" name="total_amount" value="<?php echo htmlspecialchars($invoice['total_amount']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="due_date" class="form-label">Due Date</label>
                    <input type="date" class="form-control" id="due_date" name="due_date" value="<?php echo htmlspecialchars($invoice['due_date']); ?>" required>
                </div>
                <div class="mb-3">
                    <label for="status" class="form-label">Status</label>
                    <select class="form-select" id="status" name="status">
                        <option value="unpaid" <?php echo ($invoice['status'] === 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                        <option value="paid" <?php echo ($invoice['status'] === 'paid') ? 'selected' : ''; ?>>Paid</option>
                        <option value="overdue" <?php echo ($invoice['status'] === 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Update Invoice</button>
                <a href="/invoices.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>