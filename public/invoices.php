<?php
require_once '../app/init.php';
require_once '../app/Controllers/InvoiceController.php';
require_once '../app/Models/Invoice.php';

use App\Controllers\InvoiceController;

// Database connection
$db = new PDO("sqlite:../database/isp-management.sqlite");
$db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

// Initialize Invoice Controller
$invoiceController = new InvoiceController($db);

// Pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;

// Search parameters
$search = isset($_GET['search']) ? $_GET['search'] : '';
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Get invoices
$result = $invoiceController->getInvoices([
    'search' => $search,
    'status' => $status,
    'page' => $page,
    'per_page' => $per_page
]);

$invoices = $result['invoices'];
$pagination = $result['pagination'];

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Invoices - ISP Management System</title>

    <!-- Core CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css" rel="stylesheet">

    <!-- Custom CSS -->
    <link href="/assets/css/main.css" rel="stylesheet">
    <link href="/assets/css/dashboard.css" rel="stylesheet">
</head>
<body>
    <!-- Render Sidebar -->
    <?php require_once '../views/layouts/sidebar.php'; renderSidebar('invoices'); ?>

    <!-- Main Content -->
    <div class="dashboard-container">
        <div class="main-content p-4">
            <!-- Header -->
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h1 class="h3 mb-0">Invoices</h1>
                <a href="/forms/invoices/add.php" class="btn btn-primary">
                    <i class="fas fa-plus me-2"></i>Add New Invoice
                </a>
            </div>

            <!-- Filter Form -->
            <div class="card mb-4">
                <div class="card-body">
                    <form id="filterForm" method="GET" action="" class="row g-3">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" id="search" name="search" placeholder="Search invoices..." value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <select class="form-select" id="status" name="status">
                                <option value="">All Statuses</option>
                                <option value="paid" <?php echo ($status === 'paid') ? 'selected' : ''; ?>>Paid</option>
                                <option value="unpaid" <?php echo ($status === 'unpaid') ? 'selected' : ''; ?>>Unpaid</option>
                                <option value="overdue" <?php echo ($status === 'overdue') ? 'selected' : ''; ?>>Overdue</option>
                            </select>
                        </div>
                        <div class="col-md-5">
                            <div class="d-flex gap-2">
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-filter me-2"></i>Filter
                                </button>
                                <button type="button" class="btn btn-secondary" onclick="clearSearch()">
                                    <i class="fas fa-times me-2"></i>Clear
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Invoices Table -->
            <div class="card">
                <div class="card-body">
                    <?php if (empty($invoices)): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>No invoices found.
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Invoice Number</th>
                                        <th>Client Name</th>
                                        <th>Total Amount</th>
                                        <th>Due Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($invoices as $invoice): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($invoice['invoice_number']); ?></td>
                                            <td><?php echo htmlspecialchars($invoice['first_name'] . ' ' . $invoice['last_name']); ?></td>
                                            <td><?php echo htmlspecialchars($invoice['total_amount']); ?></td>
                                            <td><?php echo htmlspecialchars($invoice['due_date']); ?></td>
                                            <td><?php echo htmlspecialchars($invoice['status']); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="/forms/invoices/view.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-primary">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <a href="/forms/invoices/edit.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-secondary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="/forms/invoices/delete.php?id=<?php echo $invoice['id']; ?>" class="btn btn-sm btn-outline-danger">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>

                        <!-- Pagination -->
                        <nav aria-label="Page navigation">
                            <ul class="pagination">
                                <?php for ($i = 1; $i <= $pagination['last_page']; $i++): ?>
                                    <li class="page-item <?php echo ($i == $page) ? 'active' : ''; ?>">
                                        <a class="page-link" href="invoices.php?page=<?php echo $i; ?>&search=<?php echo htmlspecialchars($search); ?>&status=<?php echo htmlspecialchars($status); ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- Core JavaScript -->
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <!-- Custom JavaScript -->
    <script>
        function clearSearch() {
            document.getElementById('search').value = '';
            document.getElementById('status').value = '';
            document.getElementById('filterForm').submit();
        }
    </script>
</body>
</html>