<?php
require_once 'config.php';
check_login();

$page_title = 'Asset Expenses';
$_SESSION['active_menu'] = 'assets';

// Get asset ID
$asset_id = filter_input(INPUT_GET, 'id', FILTER_SANITIZE_NUMBER_INT);
if (!$asset_id) {
    $_SESSION['error'] = "Invalid asset ID";
    header("Location: assets.php");
    exit();
}

// Get asset details
$stmt = $conn->prepare("SELECT * FROM assets WHERE id = ?");
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$asset = $stmt->get_result()->fetch_assoc();

if (!$asset) {
    $_SESSION['error'] = "Asset not found";
    header("Location: assets.php");
    exit();
}

// Get expenses for this asset
$expenses_query = "
    SELECT e.*, u.username as recorded_by
    FROM asset_expenses e
    LEFT JOIN users u ON e.created_by = u.id
    WHERE e.asset_id = ?
    ORDER BY e.expense_date DESC
";

$stmt = $conn->prepare($expenses_query);
$stmt->bind_param("i", $asset_id);
$stmt->execute();
$expenses = $stmt->get_result();

// Get expense statistics
$stats_stmt = $conn->prepare("
    SELECT 
        COUNT(*) as total_expenses,
        SUM(amount) as total_amount,
        MAX(expense_date) as last_expense,
        SUM(CASE WHEN MONTH(expense_date) = MONTH(CURRENT_DATE) 
            AND YEAR(expense_date) = YEAR(CURRENT_DATE) 
            THEN amount ELSE 0 END) as this_month_expenses
    FROM asset_expenses 
    WHERE asset_id = ?
");
$stats_stmt->bind_param("i", $asset_id);
$stats_stmt->execute();
$stats = $stats_stmt->get_result()->fetch_assoc();

include 'header.php';
include 'navbar.php';
?>

<div class="content-wrapper">
    <div class="container-fluid">
        <?php include 'alerts.php'; ?>

        <!-- Header -->
        <div class="d-flex justify-content-between align-items-center mb-4">
            <div>
                <h1 class="h2 mb-1">Expenses: <?php echo htmlspecialchars($asset['name']); ?></h1>
                <p class="text-muted mb-0"><?php echo htmlspecialchars($asset['address']); ?></p>
            </div>
            <div class="btn-toolbar gap-2">
                <a href="assets.php" class="btn btn-secondary">
                    <i class='bx bx-arrow-back'></i> Back to Assets
                </a>
            </div>
        </div>

        <!-- Statistics Cards -->
        <div class="row mb-4">
            <div class="col-md-3">
                <div class="card border-0 h-100" style="background: rgba(13, 110, 253, 0.1);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-primary text-white rounded-3" style="width: 64px; height: 64px;">
                                <i class='bx bx-money fs-1'></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle mb-2 text-primary fw-bold">Total Expenses</h6>
                                <h3 class="card-title mb-0">₱<?php echo number_format($stats['total_amount'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 h-100" style="background: rgba(25, 135, 84, 0.1);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-success text-white rounded-3" style="width: 64px; height: 64px;">
                                <i class='bx bx-calendar fs-1'></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle mb-2 text-success fw-bold">This Month</h6>
                                <h3 class="card-title mb-0">₱<?php echo number_format($stats['this_month_expenses'] ?? 0, 2); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 h-100" style="background: rgba(13, 202, 240, 0.1);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-info text-white rounded-3" style="width: 64px; height: 64px;">
                                <i class='bx bx-list-check fs-1'></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle mb-2 text-info fw-bold">Total Records</h6>
                                <h3 class="card-title mb-0"><?php echo number_format($stats['total_expenses'] ?? 0); ?></h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="col-md-3">
                <div class="card border-0 h-100" style="background: rgba(220, 53, 69, 0.1);">
                    <div class="card-body p-4">
                        <div class="d-flex align-items-center">
                            <div class="flex-shrink-0 me-3 d-flex align-items-center justify-content-center bg-danger text-white rounded-3" style="width: 64px; height: 64px;">
                                <i class='bx bx-time fs-1'></i>
                            </div>
                            <div>
                                <h6 class="card-subtitle mb-2 text-danger fw-bold">Last Expense</h6>
                                <h3 class="card-title mb-0">
                                    <?php echo $stats['last_expense'] ? date('M d, Y', strtotime($stats['last_expense'])) : 'Never'; ?>
                                </h3>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Expenses Table -->
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
                                <th>Payment Method</th>
                                <th>Reference #</th>
                                <th>Recorded By</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($expenses && $expenses->num_rows > 0): ?>
                                <?php while ($expense = $expenses->fetch_assoc()): ?>
                                <tr>
                                    <td><?php echo date('M d, Y', strtotime($expense['expense_date'])); ?></td>
                                    <td><?php echo htmlspecialchars($expense['category']); ?></td>
                                    <td><?php echo htmlspecialchars($expense['description']); ?></td>
                                    <td>₱<?php echo number_format($expense['amount'], 2); ?></td>
                                    <td><?php echo ucfirst($expense['payment_method']); ?></td>
                                    <td><?php echo htmlspecialchars($expense['reference_number'] ?? 'N/A'); ?></td>
                                    <td>
                                        <small>
                                            <?php echo htmlspecialchars($expense['recorded_by']); ?><br>
                                            <?php echo date('M d, Y h:i A', strtotime($expense['created_at'])); ?>
                                        </small>
                                    </td>
                                    <td>
                                        <div class="btn-group">
                                            <button type="button" class="btn btn-sm btn-primary" 
                                                    onclick="editExpense(<?php echo htmlspecialchars(json_encode($expense)); ?>)">
                                                <i class='bx bx-edit'></i>
                                            </button>
                                            <button type="button" class="btn btn-sm btn-danger" 
                                                    onclick="deleteExpense(<?php echo $expense['id']; ?>)">
                                                <i class='bx bx-trash'></i>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="8" class="text-center py-4">No expenses recorded yet</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <!-- Floating Action Button -->
        <button type="button" class="btn btn-primary floating-action-button" data-bs-toggle="modal" data-bs-target="#addExpenseModal">
            <i class='bx bx-plus'></i>
            <span class="fab-label">Add Expense</span>
        </button>
    </div>
</div>

<!-- Add/Edit Expense Modal -->
<div class="modal fade" id="addExpenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Add Expense</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <form action="asset_expense_save.php" method="POST" class="needs-validation" novalidate>
                <input type="hidden" name="asset_id" value="<?php echo $asset_id; ?>">
                <input type="hidden" name="expense_id" id="expense_id">
                
                <div class="modal-body">
                    <div class="mb-3">
                        <label class="form-label">Expense Date</label>
                        <input type="date" class="form-control" name="expense_date" 
                               value="<?php echo date('Y-m-d'); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Category</label>
                        <select class="form-select" name="category" required>
                            <option value="">Select Category</option>
                            <option value="maintenance">Maintenance</option>
                            <option value="repair">Repair</option>
                            <option value="utilities">Utilities</option>
                            <option value="taxes">Taxes</option>
                            <option value="insurance">Insurance</option>
                            <option value="others">Others</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Description</label>
                        <textarea class="form-control" name="description" rows="2" required></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Amount</label>
                        <div class="input-group">
                            <span class="input-group-text">₱</span>
                            <input type="number" class="form-control" name="amount" 
                                   step="0.01" min="0" required>
                        </div>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Payment Method</label>
                        <select class="form-select" name="payment_method" required>
                            <option value="cash">Cash</option>
                            <option value="check">Check</option>
                            <option value="bank_transfer">Bank Transfer</option>
                            <option value="gcash">GCash</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label class="form-label">Reference Number</label>
                        <input type="text" class="form-control" name="reference_number">
                        <div class="form-text">Required for non-cash payments</div>
                    </div>
                </div>
                
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Save Expense</button>
                </div>
            </form>
        </div>
    </div>
</div>

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

    .btn-group {
        display: flex;
        flex-direction: column;
        gap: 0.25rem;
    }

    .btn-group .btn {
        width: 100%;
        margin: 0;
    }
}
</style>

<script>
// Form validation
(function() {
    'use strict';
    
    const paymentMethodSelect = document.querySelector('select[name="payment_method"]');
    const refNumberInput = document.querySelector('input[name="reference_number"]');
    const amountInput = document.querySelector('input[name="amount"]');
    const dateInput = document.querySelector('input[name="expense_date"]');
    const form = document.querySelector('.needs-validation');

    // Set max date to today
    dateInput.max = new Date().toISOString().split('T')[0];

    // Dynamic reference number validation
    paymentMethodSelect.addEventListener('change', function() {
        const isCash = this.value === 'cash';
        refNumberInput.required = !isCash;
        refNumberInput.disabled = isCash;
        if (isCash) {
            refNumberInput.value = '';
        }
        updateValidationUI(refNumberInput);
    });

    // Amount validation
    amountInput.addEventListener('input', function() {
        this.value = this.value.replace(/[^0-9.]/g, '');
        if (parseFloat(this.value) <= 0) {
            this.setCustomValidity('Amount must be greater than 0');
        } else {
            this.setCustomValidity('');
        }
        updateValidationUI(this);
    });

    // Date validation
    dateInput.addEventListener('change', function() {
        const selectedDate = new Date(this.value);
        const today = new Date();
        if (selectedDate > today) {
            this.setCustomValidity('Expense date cannot be in the future');
        } else {
            this.setCustomValidity('');
        }
        updateValidationUI(this);
    });

    // Form submission handler
    form.addEventListener('submit', function(event) {
        if (!this.checkValidity()) {
            event.preventDefault();
            event.stopPropagation();
            
            // Show first error message
            const firstInvalid = form.querySelector(':invalid');
            if (firstInvalid) {
                firstInvalid.focus();
            }
        }
        this.classList.add('was-validated');
    });

    // Helper function to update validation UI
    function updateValidationUI(element) {
        if (element.validity.valid) {
            element.classList.remove('is-invalid');
            element.classList.add('is-valid');
        } else {
            element.classList.remove('is-valid');
            element.classList.add('is-invalid');
        }
    }
})();

function editExpense(expense) {
    const modal = new bootstrap.Modal(document.getElementById('addExpenseModal'));
    const form = document.querySelector('#addExpenseModal form');
    
    // Reset form state
    form.classList.remove('was-validated');
    form.reset();
    
    // Set values
    document.getElementById('expense_id').value = expense.id;
    document.querySelector('input[name="expense_date"]').value = expense.expense_date;
    document.querySelector('select[name="category"]').value = expense.category;
    document.querySelector('textarea[name="description"]').value = expense.description;
    document.querySelector('input[name="amount"]').value = expense.amount;
    document.querySelector('select[name="payment_method"]').value = expense.payment_method;
    document.querySelector('input[name="reference_number"]').value = expense.reference_number || '';
    
    // Update reference number field state
    const refNumberInput = document.querySelector('input[name="reference_number"]');
    refNumberInput.disabled = expense.payment_method === 'cash';
    refNumberInput.required = expense.payment_method !== 'cash';
    
    document.querySelector('#addExpenseModal .modal-title').textContent = 'Edit Expense';
    modal.show();
}

function deleteExpense(id) {
    if (confirm('Are you sure you want to delete this expense? This action cannot be undone.')) {
        window.location.href = `asset_expense_delete.php?id=${id}&asset_id=<?php echo $asset_id; ?>`;
    }
}
</script>

<?php include 'footer.php'; ?>