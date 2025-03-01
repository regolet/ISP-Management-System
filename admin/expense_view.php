<?php
require_once 'config.php';
check_login();

$id = isset($_GET['id']) ? clean_input($_GET['id']) : 0;

if (!$id) {
    header("Location: expenses.php");
    exit;
}

// Get expense details
$stmt = $conn->prepare("
    SELECT e.*, 
           ec.name as category_name,
           u.username as created_by,
           a.username as approved_by_name
    FROM expenses e
    LEFT JOIN expense_categories ec ON e.category_id = ec.id
    LEFT JOIN users u ON e.user_id = u.id
    LEFT JOIN users a ON e.approved_by = a.id
    WHERE e.id = ?
");
$stmt->bind_param("i", $id);
$stmt->execute();
$expense = $stmt->get_result()->fetch_assoc();

if (!$expense) {
    header("Location: expenses.php");
    exit;
}

// Handle approve/reject actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $_SESSION['role'] === 'admin') {
    $action = clean_input($_POST['action']);
    $status = $action === 'approve' ? 'approved' : 'rejected';
    
    $stmt = $conn->prepare("
        UPDATE expenses 
        SET status = ?, 
            approved_by = ?,
            approved_at = NOW()
        WHERE id = ? AND status = 'pending'
    ");
    $stmt->bind_param("sii", $status, $_SESSION['user_id'], $id);
    
    if ($stmt->execute()) {
        log_activity(
            $_SESSION['user_id'],
            $status . '_expense',
            ucfirst($status) . " expense of ₱" . number_format($expense['amount'], 2)
        );
        header("Location: expense_view.php?id=$id&success=1");
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Expense - ISP Billing System</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/boxicons@2.0.7/css/boxicons.min.css" rel="stylesheet">
</head>
<body>
    <?php include 'navbar.php'; ?>

    <div class="container-fluid my-4">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <?php if (isset($_GET['success'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        Expense has been updated successfully!
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h5 class="card-title mb-0">Expense Details</h5>
                        <div>
                            <?php if ($expense['status'] === 'pending'): ?>
                                <?php if ($_SESSION['role'] === 'admin'): ?>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="approve">
                                        <button type="submit" class="btn btn-success" 
                                                onclick="return confirm('Are you sure you want to approve this expense?')">
                                            <i class='bx bx-check'></i> Approve
                                        </button>
                                    </form>
                                    <form method="POST" class="d-inline">
                                        <input type="hidden" name="action" value="reject">
                                        <button type="submit" class="btn btn-danger"
                                                onclick="return confirm('Are you sure you want to reject this expense?')">
                                            <i class='bx bx-x'></i> Reject
                                        </button>
                                    </form>
                                <?php endif; ?>
                                <?php if ($_SESSION['role'] === 'admin' || $expense['user_id'] === $_SESSION['user_id']): ?>
                                    <a href="expense_form.php?id=<?php echo $id; ?>" class="btn btn-primary">
                                        <i class='bx bx-edit'></i> Edit
                                    </a>
                                <?php endif; ?>
                            <?php endif; ?>
                            <a href="expenses.php" class="btn btn-secondary">
                                <i class='bx bx-arrow-back'></i> Back
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row mb-4">
                            <div class="col-md-6">
                                <h6 class="text-muted">Basic Information</h6>
                                <dl class="row">
                                    <dt class="col-sm-4">Category</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($expense['category_name']); ?></dd>
                                    
                                    <dt class="col-sm-4">Amount</dt>
                                    <dd class="col-sm-8">₱<?php echo number_format($expense['amount'], 2); ?></dd>
                                    
                                    <dt class="col-sm-4">Expense Date</dt>
                                    <dd class="col-sm-8"><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></dd>
                                    
                                    <dt class="col-sm-4">Status</dt>
                                    <dd class="col-sm-8">
                                        <span class="badge bg-<?php 
                                            echo $expense['status'] === 'approved' ? 'success' : 
                                                ($expense['status'] === 'pending' ? 'warning' : 'danger'); 
                                        ?>">
                                            <?php echo ucfirst($expense['status']); ?>
                                        </span>
                                    </dd>
                                </dl>
                            </div>
                            <div class="col-md-6">
                                <h6 class="text-muted">Payment Information</h6>
                                <dl class="row">
                                    <dt class="col-sm-4">Payment Method</dt>
                                    <dd class="col-sm-8"><?php echo ucwords(str_replace('_', ' ', $expense['payment_method'])); ?></dd>
                                    
                                    <dt class="col-sm-4">Reference Number</dt>
                                    <dd class="col-sm-8"><?php echo $expense['reference_number'] ?: '-'; ?></dd>
                                    
                                    <?php if ($expense['receipt_image']): ?>
                                        <dt class="col-sm-4">Receipt</dt>
                                        <dd class="col-sm-8">
                                            <a href="<?php echo htmlspecialchars($expense['receipt_image']); ?>" 
                                               target="_blank" class="btn btn-sm btn-info text-white">
                                                View Receipt
                                            </a>
                                        </dd>
                                    <?php endif; ?>
                                </dl>
                            </div>
                        </div>

                        <div class="mb-4">
                            <h6 class="text-muted">Description</h6>
                            <p class="mb-0"><?php echo nl2br(htmlspecialchars($expense['description'])); ?></p>
                        </div>

                        <div class="row">
                            <div class="col-md-6">
                                <h6 class="text-muted">Created By</h6>
                                <dl class="row">
                                    <dt class="col-sm-4">User</dt>
                                    <dd class="col-sm-8"><?php echo htmlspecialchars($expense['created_by']); ?></dd>
                                    
                                    <dt class="col-sm-4">Created At</dt>
                                    <dd class="col-sm-8"><?php echo date('M d, Y H:i:s', strtotime($expense['created_at'])); ?></dd>
                                </dl>
                            </div>
                            <?php if ($expense['approved_by']): ?>
                                <div class="col-md-6">
                                    <h6 class="text-muted">Approved/Rejected By</h6>
                                    <dl class="row">
                                        <dt class="col-sm-4">User</dt>
                                        <dd class="col-sm-8"><?php echo htmlspecialchars($expense['approved_by_name']); ?></dd>
                                        
                                        <dt class="col-sm-4">Date & Time</dt>
                                        <dd class="col-sm-8"><?php echo date('M d, Y H:i:s', strtotime($expense['approved_at'])); ?></dd>
                                    </dl>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
