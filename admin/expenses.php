<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                Are you sure you want to delete this expense? This action cannot be undone.
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Delete</button>
            </div>
        </div>
    </div>
</div>

<!-- Hidden input field for storing the ID to be deleted -->
<input type="hidden" id="deleteId" value="">

<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
<script>
function confirmDelete(id) {
    document.getElementById('deleteId').value = id;
    var deleteModal = new bootstrap.Modal(document.getElementById('deleteModal'));
    deleteModal.show();
}

document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
    var id = document.getElementById('deleteId').value;
    var xhr = new XMLHttpRequest();
    xhr.open("POST", "expenses_delete.php", true);
    xhr.setRequestHeader("Content-Type", "application/json;charset=UTF-8");
    xhr.onreadystatechange = function () {
        if (xhr.readyState === 4 && xhr.status === 200) {
            var response = JSON.parse(xhr.responseText);
            if (response.success) {
                alert(response.message);
                location.reload(); // Reload the page to reflect changes
            } else {
                alert(response.message);
            }
        }
    };
    var data = JSON.stringify({ id: id });
    xhr.send(data);
});
</script>
<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

require_once 'config.php';
check_login();
$page_title = 'Expenses';
$_SESSION['active_menu'] = 'expenses';

// Get current page number and pagination setup
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$limit = 10;
$offset = ($page - 1) * $limit;

// Calculate total pages
$total_query = "SELECT COUNT(*) as count FROM expenses";
$total_result = $conn->query($total_query)->fetch_assoc();
$total_records = $total_result['count'];
$total_pages = ceil($total_records / $limit);

// Get expense summary with fixed query
$summary_query = "SELECT
    COUNT(*) as total_expenses,
    COALESCE(SUM(amount), 0) as total_amount,
    COUNT(CASE WHEN DATE_FORMAT(expense_date, '%Y-%m') = DATE_FORMAT(CURRENT_DATE(), '%Y-%m') THEN 1 END) as current_month_count,
    COALESCE(SUM(CASE WHEN DATE_FORMAT(expense_date, '%Y-%m') = DATE_FORMAT(CURRENT_DATE(), '%Y-%m') THEN amount ELSE 0 END), 0) as current_month_amount,
    COUNT(CASE WHEN DATE_FORMAT(expense_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m') THEN 1 END) as last_month_count,
    COALESCE(SUM(CASE WHEN DATE_FORMAT(expense_date, '%Y-%m') = DATE_FORMAT(DATE_SUB(CURRENT_DATE(), INTERVAL 1 MONTH), '%Y-%m') THEN amount ELSE 0 END), 0) as last_month_amount
    FROM expenses";

$summary_result = $conn->query($summary_query);
if (!$summary_result) {
    die("Error in summary query: " . $conn->error);
}
$summary_data = $summary_result->fetch_assoc();

// Calculate monthly average
$avg_query = "SELECT
    COUNT(DISTINCT DATE_FORMAT(expense_date, '%Y-%m')) as num_months,
    COALESCE(SUM(amount), 0) as total_amount
    FROM expenses";
$avg_result = $conn->query($avg_query);
if (!$avg_result) {
    die("Error in average calculation query: " . $conn->error);
}
$avg_data = $avg_result->fetch_assoc();
$monthly_avg = $avg_data['num_months'] > 0 ? $avg_data['total_amount'] / $avg_data['num_months'] : 0;

// Get expenses with pagination
$query = "SELECT e.*,
          u.username as added_by_name,
          ec.name as category_name,
          COALESCE(ec.name, 'Uncategorized') as category_name,
          a.username as approved_by_name
          FROM expenses e
          LEFT JOIN users u ON e.user_id = u.id
          LEFT JOIN expense_categories ec ON e.category_id = ec.id
          LEFT JOIN users a ON e.approved_by = a.id
          ORDER BY e.expense_date DESC
          LIMIT ? OFFSET ?";
$stmt = $conn->prepare($query);
$stmt->bind_param("ii", $limit, $offset);
$stmt->execute();
$result = $stmt->get_result();

include 'header.php';
?>

<?php include 'navbar.php'; ?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php include 'alerts.php'; ?>

        <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
            <h1 class="h2">Expense Management</h1>
        </div>

        <!-- Search and Filter Section -->
        <div class="card shadow-sm mb-4">
            <div class="card-body">
                <div class="row g-3">
                    <div class="col-md-4">
                        <div class="input-group">
                            <span class="input-group-text"><i class="bx bx-search"></i></span>
                            <input type="text" class="form-control" id="searchInput" placeholder="Search expenses...">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <select class="form-select" id="statusFilter">
                            <option value="">All Status</option>
                            <option value="pending">Pending</option>
                            <option value="approved">Approved</option>
                            <option value="rejected">Rejected</option>
                            <option value="cancelled">Cancelled</option>
                        </select>
                    </div>
                    <div class="col-md-3">
                        <input type="month" class="form-control" id="monthFilter"
                               value="<?php echo date('Y-m'); ?>">
                    </div>
                </div>
            </div>
        </div>

        <?php if (isset($_SESSION['success'])): ?>
            <div class="alert alert-success alert-dismissible fade show" role="alert">
                <?php
                echo $_SESSION['success'];
                unset($_SESSION['success']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if (isset($_SESSION['error'])): ?>
            <div class="alert alert-danger alert-dismissible fade show" role="alert">
                <?php
                echo $_SESSION['error'];
                unset($_SESSION['error']);
                ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <!-- Expense Summary Cards -->
        <div class="row mb-4">
            <!-- Total Expenses -->
            <div class="col-md-3">
                <div class="card border-0 h-100" style="background: rgba(13, 110, 253, 0.1);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-primary text-white rounded-3" style="width: 64px; height: 64px;">
                                <i class="bx bx-receipt fs-1"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle mb-2 text-primary fw-bold">Total Expenses</h6>
                                <h3 class="card-title mb-1">₱<?php echo number_format($summary_data['total_amount'], 2); ?></h3>
                                <small class="text-muted"><?php echo $summary_data['total_expenses']; ?> expenses</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Current Month -->
            <div class="col-md-3">
                <div class="card border-0 h-100" style="background: rgba(25, 135, 84, 0.1);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-success text-white rounded-3" style="width: 64px; height: 64px;">
                                <i class="bx bx-calendar fs-1"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle mb-2 text-success fw-bold">This Month</h6>
                                <h3 class="card-title mb-1">₱<?php echo number_format($summary_data['current_month_amount'], 2); ?></h3>
                                <small class="text-muted"><?php echo $summary_data['current_month_count']; ?> expenses</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Last Month -->
            <div class="col-md-3">
                <div class="card border-0 h-100" style="background: rgba(13, 202, 240, 0.1);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-info text-white rounded-3" style="width: 64px; height: 64px;">
                                <i class="bx bx-calendar-x fs-1"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle mb-2 text-info fw-bold">Last Month</h6>
                                <h3 class="card-title mb-1">₱<?php echo number_format($summary_data['last_month_amount'], 2); ?></h3>
                                <small class="text-muted"><?php echo $summary_data['last_month_count']; ?> expenses</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Average Monthly -->
            <div class="col-md-3">
                <div class="card border-0 h-100" style="background: rgba(255, 193, 7, 0.1);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-warning text-white rounded-3" style="width: 64px; height: 64px;">
                                <i class="bx bx-line-chart fs-1"></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle mb-2 text-warning fw-bold">Monthly Average</h6>
                                <h3 class="card-title mb-1">₱<?php echo number_format($monthly_avg, 2); ?></h3>
                                <small class="text-muted">over <?php echo $avg_data['num_months']; ?> months</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Table -->
        <div class="card shadow-sm">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover align-middle">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Description</th>
                                <th>Expense Category</th>
                                <th>Amount</th>
                                <th>Added By</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php while ($expense = $result->fetch_assoc()): ?>
                            <tr>
                                <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                <td><?php echo htmlspecialchars($expense['description'] ?? ''); ?></td>
                                <td><?php echo htmlspecialchars($expense['category_name'] ?? 'Uncategorized'); ?></td>
                                <td>₱<?php echo number_format($expense['amount'], 2); ?></td>
                                <td><?php echo htmlspecialchars($expense['added_by_name'] ?? 'System'); ?></td>
                                <td>
                                    <?php
                                    $status_class = '';
                                    switch($expense['status']) {
                                        case 'approved':
                                            $status_class = 'bg-success';
                                            break;
                                        case 'rejected':
                                            $status_class = 'bg-danger';
                                            break;
                                        case 'cancelled':
                                            $status_class = 'bg-secondary';
                                            break;
                                        default:
                                            $status_class = 'bg-warning';
                                    }
                                    ?>
                                    <span class="badge <?php echo $status_class; ?>">
                                        <?php echo ucfirst($expense['status'] ?? 'pending'); ?>
                                    </span>
                                </td>
                                <td>
                                    <div class="btn-group">
                                        <a href="expenses_read.php?id=<?php echo $expense['id']; ?>"
                                           class="btn btn-sm btn-info" title="View">
                                            <i class='bx bx-show'></i>
                                        </a>
                                        <?php if ($expense['status'] === 'pending'): ?>
                                        <a href="expense_form.php?id=<?php echo $expense['id']; ?>"
                                           class="btn btn-sm btn-primary" title="Edit">
                                            <i class='bx bx-edit'></i>
                                        </a>
                                        <button type="button" class="btn btn-sm btn-danger"
                                                onclick="confirmDelete(<?php echo $expense['id']; ?>)"
                                                title="Delete">
                                            <i class='bx bx-trash'></i>
                                        </button>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endwhile; ?>
                            <?php if ($result->num_rows === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center">No expenses found</td>
                            </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Pagination -->
        <?php if ($total_pages > 1): ?>
        <nav aria-label="Page navigation" class="mt-4">
            <ul class="pagination justify-content-center">
                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                    <a class="page-link" href="?page=<?php echo $i; ?>">
                        <?php echo $i; ?>
                    </a>
                </li>
                <?php endfor; ?>
            </ul>
        </nav>
        <?php endif; ?>
    </div>
</div>

<!-- Required JavaScript -->
<script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.datatables.net/1.11.5/js/dataTables.bootstrap5.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

<link rel="stylesheet" href="https://cdn.datatables.net/1.11.5/css/dataTables.bootstrap5.min.css">

<script>
$(document).ready(function() {
    try {
        const table = $('.table').DataTable({
            pageLength: 10,
            lengthMenu: [[10, 25, 50, -1], [10, 25, 50, "All"]],
            order: [[0, 'desc']], // Date column
            columnDefs: [
                {
                    targets: [3], // Amount column
                    type: 'num'
                },
                {
                    targets: -1, // Actions column
                    orderable: false,
                    searchable: false
                }
            ],
            language: {
                emptyTable: "No expenses found",
                zeroRecords: "No matching records found",
                info: "Showing _START_ to _END_ of _TOTAL_ expenses",
                infoEmpty: "Showing 0 to 0 of 0 expenses",
                infoFiltered: "(filtered from _MAX_ total expenses)",
                lengthMenu: "Show _MENU_ expenses per page",
                search: "Search expenses:",
                paginate: {
                    first: "First",
                    last: "Last",
                    next: "Next",
                    previous: "Previous"
                }
            }
        });

        // Handle search input with debounce
        let searchTimer;
        $('#searchInput').on('keyup', function() {
            clearTimeout(searchTimer);
            const searchValue = $(this).val();
            searchTimer = setTimeout(() => {
                table.search(searchValue).draw();
            }, 500);
        });

        // Handle status filter
        $('#statusFilter').on('change', function() {
            const status = $(this).val();
            table.column(5).search(status).draw();
        });

        // Handle month filter
        $('#monthFilter').on('change', function() {
            const month = $(this).val();
            if (month) {
                table.column(0).search(month).draw();
            } else {
                table.column(0).search('').draw();
            }
        });

        console.log('DataTable initialized successfully');
    } catch (error) {
        console.error('Error initializing DataTable:', error);
    }
});
</script>

<style>
/* DataTables Styles */
.dataTables_wrapper .dataTables_length select {
    padding: 0.375rem 2.25rem 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
}

.dataTables_wrapper .dataTables_filter input {
    padding: 0.375rem 0.75rem;
    font-size: 1rem;
    font-weight: 400;
    line-height: 1.5;
    border: 1px solid #ced4da;
    border-radius: 0.25rem;
}

.dataTables_wrapper .dataTables_paginate .paginate_button {
    padding: 0.375rem 0.75rem;
    margin-left: -1px;
    border: 1px solid #dee2e6;
}

.dataTables_wrapper .dataTables_paginate .paginate_button.current {
    background-color: #0d6efd;
    border-color: #0d6efd;
    color: white !important;
}

.dataTables_wrapper .dataTables_paginate .paginate_button:hover {
    background-color: #e9ecef;
    border-color: #dee2e6;
    color: #0a58ca !important;
}

.dataTables_wrapper .dataTables_info {
    padding-top: 0.5rem;
}

/* Hide default DataTables search since we have our own */
.dataTables_filter {
    display: none;
}

/* Button Group Styles */
.btn-group .btn {
    width: 32px;
    height: 32px;
    padding: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
}

.btn-group .btn i {
    font-size: 16px;
    line-height: 1;
}
</style>

<!-- Floating Action Button -->
<a href="expense_form.php" class="btn btn-primary floating-action-button">
    <i class='bx bx-plus'></i>
    <span class="fab-label">Add Expense</span>
</a>

<style>
.floating-action-button {
    position: fixed;
    bottom: 2rem;
    right: 2rem;
    width: auto;
    height: auto;
    padding: 0.75rem 1.5rem;
    border-radius: 50px;
    box-shadow: 0 4px 10px rgba(0, 0, 0, 0.3);
    z-index: 1000;
    transition: all 0.3s ease;
    display: flex;
    align-items: center;
    gap: 0.5rem;
}

.floating-action-button:hover {
    transform: translateY(-3px);
    box-shadow: 0 6px 15px rgba(0, 0, 0, 0.35);
}

.floating-action-button i {
    font-size: 1.25rem;
}

@media (max-width: 768px) {
    .floating-action-button {
        padding: 1rem;
        border-radius: 50%;
        width: 56px;
        height: 56px;
        display: flex;
        align-items: center;
        justify-content: center;
    }

    .floating-action-button .fab-label {
        display: none;
    }

    .floating-action-button i {
        margin: 0;
    }
}
</style>

</body>
</html>