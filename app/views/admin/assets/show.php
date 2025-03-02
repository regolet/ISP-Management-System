<?php
$title = htmlspecialchars($asset['name']) . ' - Asset Details';
?>

<div class="row mb-4">
    <div class="col-md-6">
        <h2><?= htmlspecialchars($asset['name']) ?></h2>
    </div>
    <div class="col-md-6 text-end">
        <a href="/admin/assets" class="btn btn-secondary">
            <i class="fa fa-arrow-left"></i> Back to Assets
        </a>
        <?php if ($asset['status'] === 'available'): ?>
            <a href="/admin/assets/<?= $asset['id'] ?>/collect" class="btn btn-success">
                <i class="fa fa-hand-holding"></i> Collect Asset
            </a>
        <?php endif; ?>
        <a href="/admin/assets/<?= $asset['id'] ?>/expenses/add" class="btn btn-warning">
            <i class="fa fa-receipt"></i> Add Expense
        </a>
    </div>
</div>

<!-- Asset Information -->
<div class="row mb-4">
    <!-- Basic Details -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Asset Details</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="35%">Asset Type</th>
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
                    <tr>
                        <th>Location</th>
                        <td><?= htmlspecialchars($asset['location']) ?></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td><?= nl2br(htmlspecialchars($asset['description'])) ?></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>

    <!-- Financial Information -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header">
                <h5 class="mb-0">Financial Information</h5>
            </div>
            <div class="card-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="35%">Purchase Date</th>
                        <td><?= date('Y-m-d', strtotime($asset['purchase_date'])) ?></td>
                    </tr>
                    <tr>
                        <th>Purchase Price</th>
                        <td><?= formatCurrency($asset['purchase_price']) ?></td>
                    </tr>
                    <tr>
                        <th>Current Value</th>
                        <td><?= formatCurrency($depreciation['current_value']) ?></td>
                    </tr>
                    <tr>
                        <th>Total Depreciation</th>
                        <td><?= formatCurrency($depreciation['total_depreciation']) ?></td>
                    </tr>
                    <tr>
                        <th>Warranty Expiry</th>
                        <td>
                            <?php if ($asset['warranty_expiry']): ?>
                                <?= date('Y-m-d', strtotime($asset['warranty_expiry'])) ?>
                                <?php if (strtotime($asset['warranty_expiry']) < time()): ?>
                                    <span class="badge bg-danger">Expired</span>
                                <?php endif; ?>
                            <?php else: ?>
                                N/A
                            <?php endif; ?>
                        </td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Expenses and Collections -->
<div class="row">
    <!-- Expense History -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Expense History</h5>
                <a href="/admin/assets/<?= $asset['id'] ?>/expenses/add" class="btn btn-sm btn-primary">
                    <i class="fa fa-plus"></i> Add Expense
                </a>
            </div>
            <div class="card-body">
                <?php if (empty($expenses)): ?>
                    <div class="alert alert-info">No expenses recorded.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Date</th>
                                    <th>Type</th>
                                    <th>Amount</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($expenses as $expense): ?>
                                    <tr>
                                        <td><?= date('Y-m-d', strtotime($expense['date'])) ?></td>
                                        <td><?= htmlspecialchars($expense['expense_type']) ?></td>
                                        <td><?= formatCurrency($expense['amount']) ?></td>
                                        <td>
                                            <span class="badge bg-<?= getPaymentStatusBadgeClass($expense['payment_status']) ?>">
                                                <?= ucfirst(htmlspecialchars($expense['payment_status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info view-expense" 
                                                    data-expense='<?= json_encode($expense) ?>'>
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Collection History -->
    <div class="col-md-6">
        <div class="card">
            <div class="card-header d-flex justify-content-between align-items-center">
                <h5 class="mb-0">Collection History</h5>
                <?php if ($asset['status'] === 'available'): ?>
                    <a href="/admin/assets/<?= $asset['id'] ?>/collect" class="btn btn-sm btn-primary">
                        <i class="fa fa-hand-holding"></i> Collect Asset
                    </a>
                <?php endif; ?>
            </div>
            <div class="card-body">
                <?php if (empty($collections)): ?>
                    <div class="alert alert-info">No collection history.</div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-striped">
                            <thead>
                                <tr>
                                    <th>Collected By</th>
                                    <th>Collection Date</th>
                                    <th>Return Date</th>
                                    <th>Status</th>
                                    <th>Actions</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($collections as $collection): ?>
                                    <tr>
                                        <td><?= htmlspecialchars($collection['collected_by']) ?></td>
                                        <td><?= date('Y-m-d', strtotime($collection['collection_date'])) ?></td>
                                        <td>
                                            <?= $collection['return_date'] ? 
                                                date('Y-m-d', strtotime($collection['return_date'])) : 
                                                'Not returned' ?>
                                        </td>
                                        <td>
                                            <span class="badge bg-<?= getCollectionStatusBadgeClass($collection['status']) ?>">
                                                <?= ucfirst(htmlspecialchars($collection['status'])) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <button type="button" class="btn btn-sm btn-info view-collection" 
                                                    data-collection='<?= json_encode($collection) ?>'>
                                                <i class="fa fa-eye"></i>
                                            </button>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- Expense Details Modal -->
<div class="modal fade" id="expenseModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Expense Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="35%">Date</th>
                        <td id="expense-date"></td>
                    </tr>
                    <tr>
                        <th>Type</th>
                        <td id="expense-type"></td>
                    </tr>
                    <tr>
                        <th>Amount</th>
                        <td id="expense-amount"></td>
                    </tr>
                    <tr>
                        <th>Vendor</th>
                        <td id="expense-vendor"></td>
                    </tr>
                    <tr>
                        <th>Invoice Number</th>
                        <td id="expense-invoice"></td>
                    </tr>
                    <tr>
                        <th>Description</th>
                        <td id="expense-description"></td>
                    </tr>
                    <tr>
                        <th>Status</th>
                        <td id="expense-status"></td>
                    </tr>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Collection Details Modal -->
<div class="modal fade" id="collectionModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title">Collection Details</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
            </div>
            <div class="modal-body">
                <table class="table table-bordered">
                    <tr>
                        <th width="35%">Collected By</th>
                        <td id="collection-user"></td>
                    </tr>
                    <tr>
                        <th>Collection Date</th>
                        <td id="collection-date"></td>
                    </tr>
                    <tr>
                        <th>Return Date</th>
                        <td id="collection-return"></td>
                    </tr>
                    <tr>
                        <th>Purpose</th>
                        <td id="collection-purpose"></td>
                    </tr>
                    <tr>
                        <th>Condition on Collection</th>
                        <td id="collection-condition"></td>
                    </tr>
                    <tr>
                        <th>Notes</th>
                        <td id="collection-notes"></td>
                    </tr>
                </table>
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

function getPaymentStatusBadgeClass($status) {
    return match ($status) {
        'paid' => 'success',
        'pending' => 'warning',
        'overdue' => 'danger',
        default => 'secondary'
    };
}

function getCollectionStatusBadgeClass($status) {
    return match ($status) {
        'collected' => 'warning',
        'returned' => 'success',
        'overdue' => 'danger',
        default => 'secondary'
    };
}

function formatCurrency($amount) {
    return '$' . number_format($amount, 2);
}
?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const expenseModal = new bootstrap.Modal(document.getElementById('expenseModal'));
    const collectionModal = new bootstrap.Modal(document.getElementById('collectionModal'));

    // Handle expense view clicks
    document.querySelectorAll('.view-expense').forEach(button => {
        button.addEventListener('click', function() {
            const expense = JSON.parse(this.dataset.expense);
            document.getElementById('expense-date').textContent = new Date(expense.date).toLocaleDateString();
            document.getElementById('expense-type').textContent = expense.expense_type;
            document.getElementById('expense-amount').textContent = formatCurrency(expense.amount);
            document.getElementById('expense-vendor').textContent = expense.vendor;
            document.getElementById('expense-invoice').textContent = expense.invoice_number || 'N/A';
            document.getElementById('expense-description').textContent = expense.description || 'N/A';
            document.getElementById('expense-status').textContent = expense.payment_status;
            expenseModal.show();
        });
    });

    // Handle collection view clicks
    document.querySelectorAll('.view-collection').forEach(button => {
        button.addEventListener('click', function() {
            const collection = JSON.parse(this.dataset.collection);
            document.getElementById('collection-user').textContent = collection.collected_by;
            document.getElementById('collection-date').textContent = new Date(collection.collection_date).toLocaleDateString();
            document.getElementById('collection-return').textContent = collection.return_date ? 
                new Date(collection.return_date).toLocaleDateString() : 'Not returned';
            document.getElementById('collection-purpose').textContent = collection.purpose;
            document.getElementById('collection-condition').textContent = collection.condition_on_collection;
            document.getElementById('collection-notes').textContent = collection.notes || 'N/A';
            collectionModal.show();
        });
    });

    function formatCurrency(amount) {
        return '$' + parseFloat(amount).toFixed(2).replace(/\d(?=(\d{3})+\.)/g, '$&,');
    }
});
</script>
