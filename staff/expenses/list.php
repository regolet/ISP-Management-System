<?php
session_start();
require_once '../../config.php';

// Check if user is logged in and is a staff member
if (!isset($_SESSION['user_id']) || !isset($_SESSION['role']) || $_SESSION['role'] !== 'staff') {
    header("Location: ../../login.php");
    exit();
}

// Make sure staff is linked to an employee
if (!isset($_SESSION['employee_id'])) {
    header("Location: ../../login.php");
    exit();
}

$page_title = "My Expenses";
$_SESSION['active_menu'] = 'expenses';

// Get expenses list with categories
$stmt = $conn->prepare("
    SELECT e.*, c.name as category_name
    FROM expenses e
    LEFT JOIN expense_categories c ON e.category_id = c.id
    WHERE e.user_id = ?
    ORDER BY e.expense_date DESC
");

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$expenses = $stmt->get_result();

// Get total expenses
$stmt = $conn->prepare("
    SELECT 
        SUM(CASE WHEN status = 'approved' THEN amount ELSE 0 END) as total_approved,
        SUM(CASE WHEN status = 'pending' THEN amount ELSE 0 END) as total_pending,
        SUM(CASE WHEN status = 'rejected' THEN amount ELSE 0 END) as total_rejected
    FROM expenses 
    WHERE user_id = ?
");

$stmt->bind_param("i", $_SESSION['user_id']);
$stmt->execute();
$totals = $stmt->get_result()->fetch_assoc();

include '../../header.php';
include '../staff_navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <div class="row mb-4">
            <div class="col-md-6">
                <h1 class="h3 mb-0">My Expenses</h1>
            </div>
            <div class="col-md-6 text-end">
                <a href="add.php" class="btn btn-primary">
                    <i class='bx bx-plus'></i> Add New Expense
                </a>
            </div>
        </div>

        <!-- Summary Cards -->
        <div class="row mb-4">
            <div class="col-md-4">
                <div class="card bg-success bg-opacity-10 border-0">
                    <div class="card-body">
                        <h6 class="card-title text-success">Approved Expenses</h6>
                        <h3 class="mb-0">₱<?php echo number_format($totals['total_approved'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-warning bg-opacity-10 border-0">
                    <div class="card-body">
                        <h6 class="card-title text-warning">Pending Expenses</h6>
                        <h3 class="mb-0">₱<?php echo number_format($totals['total_pending'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
            <div class="col-md-4">
                <div class="card bg-danger bg-opacity-10 border-0">
                    <div class="card-body">
                        <h6 class="card-title text-danger">Rejected Expenses</h6>
                        <h3 class="mb-0">₱<?php echo number_format($totals['total_rejected'] ?? 0, 2); ?></h3>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses List -->
        <div class="card">
            <div class="card-body">
                <div class="table-responsive">
                    <table class="table table-hover">
                        <thead>
                            <tr>
                                <th>Date</th>
                                <th>Category</th>
                                <th>Description</th>
                                <th>Amount</th>
                                <th>Status</th>
                                <th>Payment Method</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($expenses->num_rows > 0):
                                while ($expense = $expenses->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($expense['category_name']); ?></td>
                                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                    <td>₱<?php echo number_format($expense['amount'], 2); ?></td>
                                    <td>
                                        <span class="badge bg-<?php 
                                            echo match($expense['status']) {
                                                'approved' => 'success',
                                                'rejected' => 'danger',
                                                default => 'warning'
                                            };
                                        ?>">
                                            <?php echo ucfirst($expense['status']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo ucfirst(str_replace('_', ' ', $expense['payment_method'])); ?></td>
                                    <td>
                                        <div class="btn-group">
                                            <a href="view.php?id=<?php echo $expense['id']; ?>" 
                                               class="btn btn-sm btn-info">
                                                <i class='bx bx-show'></i>
                                            </a>
                                            <?php if ($expense['status'] === 'pending'): ?>
                                            <a href="edit.php?id=<?php echo $expense['id']; ?>" 
                                               class="btn btn-sm btn-primary">
                                                <i class='bx bx-edit'></i>
                                            </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endwhile;
                            else: ?>
                                <tr></tr>
                                    <td colspan="7" class="text-center">No expenses found</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../../footer.php'; ?>
