<?php
$title = 'Add Expense - ' . htmlspecialchars($asset['name']);
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2>Add Asset Expense</h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/assets/<?= $asset['id'] ?>" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Asset Details
        </a>
    </div>
</div>

<div class="row">
    <!-- Asset Information -->
    <div class="col-md-4">
        <div class="card mb-4">
            <div class="card-header">
                <h5 class="mb-0">Asset Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="40%">Name</th>
                        <td><?= htmlspecialchars($asset['name']) ?></td>
                    </tr>
                    <tr>
                        <th>Type</th>
                        <td><?= htmlspecialchars(ucfirst($asset['asset_type'])) ?></td>
                    </tr>
                    <tr>
                        <th>Serial Number</th>
                        <td><?= htmlspecialchars($asset['serial_number']) ?></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td>
                            <span class="badge bg-<?= getStatusBadgeClass($asset['status']) ?>">
                                <?= ucfirst(htmlspecialchars($asset['status'])) ?>
                            </span>
                        </td>
                    </tr>
                </table>
            </div>
        </div>

        <!-- Recent Expenses -->
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Recent Expenses</h5>
            </div>
            <div class="card-body">
                <?php if (empty($recentExpenses ?? [])): ?>
                    <div class="alert alert-info">No recent expenses.</div>
                <?php else: ?>
                    <div class="list-group list-group-flush">
                        <?php foreach ($recentExpenses as $expense): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1"><?= htmlspecialchars($expense['expense_type']) ?></h6>
                                    <small><?= formatCurrency($expense['amount']) ?></small>
                                </div>
                                <p class="mb-1"><?= htmlspecialchars($expense['description']) ?></p>
                                <small><?= date('Y-m-d', strtotime($expense['date'])) ?></small>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Expense Form -->
    <div class="col-md-8">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Expense Details</h5>
            </div>
            <div class="card-body">
                <?php if (isset($errors['general'])): ?>
                    <div class="alert alert-danger">
                        <?= htmlspecialchars($errors['general']) ?>
                    </div>
                <?php endif; ?>

                <form method="POST" action="/admin/assets/<?= $asset['id'] ?>/expenses/add" 
                      class="row g-3" enctype="multipart/form-data">
                    <?= \App\Middleware\CSRFMiddleware::generateTokenField() ?>

                    <!-- Expense Type -->
                    <div class="col-md-6">
                        <label for="expense_type" class="form-label">Expense Type *</label>
                        <select class="form-select <?= isset($errors['expense_type']) ? 'is-invalid' : '' ?>" 
                                id="expense_type" name="expense_type" required>
                            <option value="">Select Type</option>
                            <option value="maintenance" <?= ($data['expense_type'] ?? '') === 'maintenance' ? 'selected' : '' ?>>Maintenance</option>
                            <option value="repair" <?= ($data['expense_type'] ?? '') === 'repair' ? 'selected' : '' ?>>Repair</option>
                            <option value="upgrade" <?= ($data['expense_type'] ?? '') === 'upgrade' ? 'selected' : '' ?>>Upgrade</option>
                            <option value="license" <?= ($data['expense_type'] ?? '') === 'license' ? 'selected' : '' ?>>License/Subscription</option>
                            <option value="other" <?= ($data['expense_type'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                        </select>
                        <?php if (isset($errors['expense_type'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['expense_type']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Amount -->
                    <div class="col-md-6">
                        <label for="amount" class="form-label">Amount *</label>
                        <div class="input-group">
                            <span class="input-group-text">$</span>
                            <input type="number" step="0.01" class="form-control <?= isset($errors['amount']) ? 'is-invalid' : '' ?>" 
                                   id="amount" name="amount" 
                                   value="<?= htmlspecialchars($data['amount'] ?? '') ?>" required>
                            <?php if (isset($errors['amount'])): ?>
                                <div class="invalid-feedback">
                                    <?= htmlspecialchars($errors['amount']) ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Date -->
                    <div class="col-md-6">
                        <label for="date" class="form-label">Date *</label>
                        <input type="date" class="form-control <?= isset($errors['date']) ? 'is-invalid' : '' ?>" 
                               id="date" name="date" 
                               value="<?= htmlspecialchars($data['date'] ?? date('Y-m-d')) ?>" required>
                        <?php if (isset($errors['date'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['date']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Vendor -->
                    <div class="col-md-6">
                        <label for="vendor" class="form-label">Vendor *</label>
                        <input type="text" class="form-control <?= isset($errors['vendor']) ? 'is-invalid' : '' ?>" 
                               id="vendor" name="vendor" 
                               value="<?= htmlspecialchars($data['vendor'] ?? '') ?>" required>
                        <?php if (isset($errors['vendor'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['vendor']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Invoice Number -->
                    <div class="col-md-6">
                        <label for="invoice_number" class="form-label">Invoice Number</label>
                        <input type="text" class="form-control <?= isset($errors['invoice_number']) ? 'is-invalid' : '' ?>" 
                               id="invoice_number" name="invoice_number" 
                               value="<?= htmlspecialchars($data['invoice_number'] ?? '') ?>">
                        <?php if (isset($errors['invoice_number'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['invoice_number']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Payment Status -->
                    <div class="col-md-6">
                        <label for="payment_status" class="form-label">Payment Status *</label>
                        <select class="form-select <?= isset($errors['payment_status']) ? 'is-invalid' : '' ?>" 
                                id="payment_status" name="payment_status" required>
                            <option value="paid" <?= ($data['payment_status'] ?? '') === 'paid' ? 'selected' : '' ?>>Paid</option>
                            <option value="pending" <?= ($data['payment_status'] ?? '') === 'pending' ? 'selected' : '' ?>>Pending</option>
                        </select>
                        <?php if (isset($errors['payment_status'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['payment_status']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Description -->
                    <div class="col-12">
                        <label for="description" class="form-label">Description *</label>
                        <textarea class="form-control <?= isset($errors['description']) ? 'is-invalid' : '' ?>" 
                                  id="description" name="description" rows="3" required><?= htmlspecialchars($data['description'] ?? '') ?></textarea>
                        <?php if (isset($errors['description'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['description']) ?>
                            </div>
                        <?php endif; ?>
                    </div>

                    <!-- Receipt Upload -->
                    <div class="col-12">
                        <label for="receipt" class="form-label">Receipt</label>
                        <input type="file" class="form-control <?= isset($errors['receipt']) ? 'is-invalid' : '' ?>" 
                               id="receipt" name="receipt" accept="image/*,.pdf">
                        <?php if (isset($errors['receipt'])): ?>
                            <div class="invalid-feedback">
                                <?= htmlspecialchars($errors['receipt']) ?>
                            </div>
                        <?php endif; ?>
                        <div class="form-text">Accepted formats: Images (JPG, PNG) and PDF. Maximum size: 5MB</div>
                    </div>

                    <!-- Form Actions -->
                    <div class="col-12 text-end">
                        <button type="button" class="btn btn-secondary" onclick="history.back()">Cancel</button>
                        <button type="submit" class="btn btn-primary">Record Expense</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<?php
function getStatusBadgeClass($status) {
    return match ($status) {
        'available' => 'success',
        'collected' => 'warning',
        'maintenance' => 'danger',
        default => 'secondary'
    };
}

function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    // File size validation
    const receiptInput = document.getElementById('receipt');
    const maxSize = 5 * 1024 * 1024; // 5MB

    receiptInput.addEventListener('change', function() {
        if (this.files[0] && this.files[0].size > maxSize) {
            alert('File size exceeds 5MB limit');
            this.value = '';
        }
    });

    // Form validation
    const form = document.querySelector('form');
    form.addEventListener('submit', function(e) {
        let isValid = true;
        
        // Validate amount
        const amount = document.getElementById('amount');
        if (!amount.value || parseFloat(amount.value) <= 0) {
            amount.classList.add('is-invalid');
            isValid = false;
        } else {
            amount.classList.remove('is-invalid');
        }

        // Validate required fields
        ['expense_type', 'vendor', 'date', 'description'].forEach(field => {
            const input = document.getElementById(field);
            if (!input.value.trim()) {
                input.classList.add('is-invalid');
                isValid = false;
            } else {
                input.classList.remove('is-invalid');
            }
        });

        if (!isValid) {
            e.preventDefault();
        }
    });
});
</script>
