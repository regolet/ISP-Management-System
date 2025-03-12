<?php
require_once '../../../app/init.php';
require_once '../../../app/Controllers/InvoiceController.php';

use App\Controllers\InvoiceController;

// Database connection
$db = new PDO("sqlite:../../../database/isp-management.sqlite");
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

// Handle deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if ($invoiceController->deleteInvoice($invoice_id)) {
        // Redirect to invoices page with success message
        header("Location: /invoices.php?message=Invoice deleted successfully");
        exit();
    } else {
        // Redirect to invoices page with error message
        header("Location: /invoices.php?error=Failed to delete invoice");
        exit();
    }
}

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Invoice - ISP Management System</title>
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
            <h2>Delete Invoice</h2>
            <p>Are you sure you want to delete invoice #<?php echo htmlspecialchars($invoice['invoice_number']); ?>?</p>
            <form method="POST">
                <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($_SESSION['csrf_token']); ?>">
                <button type="submit" class="btn btn-danger">Yes, Delete Invoice</button>
                <a href="/invoices.php" class="btn btn-secondary">Cancel</a>
            </form>
        </div>
    </div>
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>